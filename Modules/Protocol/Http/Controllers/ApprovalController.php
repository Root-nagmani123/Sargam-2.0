<?php

namespace Modules\Protocol\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Http\Requests\AssignProtocolRequest;
use Modules\Protocol\Http\Requests\ReviewProtocolRequest;
use Modules\Protocol\Http\Requests\StoreProtocolTicketFilesRequest;
use App\Models\HostelBuildingMaster;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Modules\Protocol\Services\{ApprovalService,ProtocolDirectory};

class ApprovalController extends Controller
{
    /**
     * Read-only list of every request, for Protocol Staff oversight.
    */

    private $service;
    public function __construct(ApprovalService $service) {
        $this->service = $service;
    }

    public function allRequests(Request $request)
    {
        if($request->ajax()){
            return $this->service->getDatatable($request);
        }
        $pageData = $this->service->pageData();
        return view('protocol::approval.all-requests', compact('pageData'));
    }

    /**
     * All Requests locked to status=recommended, for holders of protocol.recommended_booking.
     */
    public function recommendedRequests(Request $request)
    {
        $request->merge(['status' => ProtocolRequest::STATUS_RECOMMENDED]);

        return $this->allRequests($request);
    }

    /**
     * Queue of requests awaiting Protocol Staff review.
     */
    public function queue()
    {
        $requests = ProtocolRequest::with('requestable', 'batch')
            ->awaitingProtocolStaff(Auth::id())
            ->latest()
            ->paginate(config('protocol.per_page'));
     

        return view('protocol::approval.queue', compact('requests'));
    }

    /**
     * Review screen for a single pending request.
     */
    public function review(Request $request, ProtocolRequest $protocolRequest)
    {
        if ($protocolRequest->request_type === 'guesthouse') {
            $protocolRequest->load([
                'requestable.assignedGuestHouse',
            ]);
        } elseif ($protocolRequest->request_type === 'vehicle') {
            $protocolRequest->load([
                'requestable.vehicle',
            ]);
        }

            $protocolRequest->load([
                'batch.guests',
                'logs.actionBy',
                'vehicleDriver',
            ]);

            if ($request->boolean('pdf')) {
                return Pdf::loadView('protocol::approval.request-pdf', compact('protocolRequest'))
                    ->setPaper('a4', 'landscape')
                    ->setOptions([
                        'isRemoteEnabled' => false,
                        'isPhpEnabled' => false,
                    ])
                    ->stream($protocolRequest->request_number . '.pdf');
            }

        $GuestHouse = HostelBuildingMaster::orderBy('building_name', 'asc')->where('active_room', 1)->pluck('building_name', 'pk');
       

        // The list of Protocol Approvers that can be recommended to
        $managers = app(ProtocolDirectory::class)->approvers();

        return view('protocol::approval.review', compact('protocolRequest', 'managers','GuestHouse'));
    }

    public function assign(AssignProtocolRequest $request, ProtocolRequest $protocolRequest)
    {
        $data = $request->validated();
        $staffEmployeeId = app(ProtocolDirectory::class)->employeePkFor(Auth::user());
        abort_if($staffEmployeeId === null, 403, 'Your login is not linked to a single employee record. Please contact the administrator.');

        DB::transaction(function () use ($data, $protocolRequest, $staffEmployeeId) {
            $lockedRequest = ProtocolRequest::whereKey($protocolRequest->getKey())->lockForUpdate()->firstOrFail();
            abort_if(
                $lockedRequest->status !== ProtocolRequest::STATUS_APPROVED
                    || ! in_array($lockedRequest->request_type, ['vehicle', 'guesthouse'], true),
                403,
                'Only approved vehicle and guest house requests can be assigned.'
            );

            if ($lockedRequest->request_type === 'vehicle') {
                $lockedRequest->vehicleDriver()->updateOrCreate([], [
                    'vehicle_no' => $data['vehicle_no'],
                    'vehicle_name' => $data['vehicle_name'],
                    'driver_name' => $data['driver_name'],
                    'driver_number' => $data['driver_number'],
                ]);
            } else {
                $requestable = $lockedRequest->requestable;
                abort_if($requestable === null, 404, 'The guest house request details could not be found.');
                $requestable->update(['assigned_guest_house' => $data['assigned_guest_house']]);
            }

            $lockedRequest->logs()->create([
                'action' => 'assigned',
                'action_by_id' => $staffEmployeeId,
                'action_by_role' => 'Protocol Staff',
                'remarks' => null,
                'action_at' => now(),
            ]);
        });

        return redirect()
            ->route('protocol.approval.review', $protocolRequest)
            ->with('success', "Assignment saved for request {$protocolRequest->request_number}.");
    }

    /**
     * Handle Protocol Staff decisions and onward recommendations from the assigned approver.
     */
    public function decide(ReviewProtocolRequest $request, ProtocolRequest $protocolRequest)
    {
        abort_if($protocolRequest->status === ProtocolRequest::STATUS_APPROVED, 403,
            'This request is already approved.');

        $data = $request->validated();
        $isAssignedApprover = $protocolRequest->status === ProtocolRequest::STATUS_RECOMMENDED
            && (int) $protocolRequest->recommended_to_id === (int) Auth::id();

        if (in_array($data['decision'], ['approve', 'reject'], true)) {
            abort_if($protocolRequest->status !== ProtocolRequest::STATUS_PENDING, 403,
                'Only pending requests can be decided directly.');
        } elseif ($protocolRequest->status !== ProtocolRequest::STATUS_PENDING) {
            abort_unless(Auth::user()->can('protocol.protocol_requests') || $isAssignedApprover, 403,
                'Only Protocol Staff or the assigned approver may recommend this request further.');
        }

        // Recorded as the employee the login resolves to (pk, else a unique pk_old)
        $staffEmployeeId = app(ProtocolDirectory::class)->employeePkFor(Auth::user());
        abort_if($staffEmployeeId === null, 403, 'Your login is not linked to a single employee record. Please contact the administrator.');

        $message = DB::transaction(function () use ($data, $protocolRequest, $staffEmployeeId) {
            if ($data['decision'] === 'approve') {
                $protocolRequest->approveDirectly($staffEmployeeId, $data['remarks'] ?? null);
                return "Request {$protocolRequest->request_number} approved.";
            }

            if ($data['decision'] === 'reject') {
                $protocolRequest->rejectDirectly($staffEmployeeId, $data['remarks']);
                return "Request {$protocolRequest->request_number} rejected.";
            }

            $recommendationRole = $protocolRequest->status === ProtocolRequest::STATUS_RECOMMENDED
                && (int) $protocolRequest->recommended_to_id === (int) Auth::id()
                    ? 'Manager'
                    : 'Protocol Staff';
            $protocolRequest->recommendTo(
                $staffEmployeeId,
                (int) $data['recommended_to_id'],
                $data['remarks'] ?? null,
                $recommendationRole
            );
            return "Request {$protocolRequest->request_number} recommended for further approval.";
        });

        return redirect()
            ->route('protocol.requests.all')
            ->with('success', $message);
    }

    public function storeTicketFiles(StoreProtocolTicketFilesRequest $request, ProtocolRequest $protocolRequest)
    {
        $storedFiles = [];
        $files = $request->validated()['ticket_files'];

        try {
            foreach ($files as $file) {
                $path = $file->store('protocol/tickets/' . $protocolRequest->getKey(), 'private');
                if (! $path) {
                    throw new \RuntimeException('Unable to store an uploaded ticket file.');
                }

                $storedFiles[] = [
                    'file' => $file,
                    'path' => $path,
                ];
            }

            DB::transaction(function () use ($storedFiles, $protocolRequest) {
                foreach ($storedFiles as $storedFile) {
                    $file = $storedFile['file'];

                    $protocolRequest->ticketFiles()->create([
                        'uploaded_by_user_id' => Auth::id(),
                        'file_path' => $storedFile['path'],
                        'original_name' => Str::substr($file->getClientOriginalName(), 0, 255),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            if ($storedFiles !== []) {
                Storage::disk('private')->delete(array_column($storedFiles, 'path'));
            }

            throw $exception;
        }

        return redirect()
            ->route('protocol.requests.all')
            ->with('success', count($storedFiles) . ' ticket file(s) uploaded successfully.');
    }

}
    
