<?php

namespace Modules\Protocol\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Http\Requests\ReviewProtocolRequest;
use App\Models\HostelBuildingMaster;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use App\Models\User;
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
    public function review(ProtocolRequest $protocolRequest)
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
            ]);
          
        $GuestHouse = HostelBuildingMaster::orderBy('building_name', 'asc')->pluck('building_name', 'pk');
       

        // The list of Protocol Approvers that can be recommended to
        $managers = app(ProtocolDirectory::class)->approvers();

        return view('protocol::approval.review', compact('protocolRequest', 'managers','GuestHouse'));
    }

    /**
     * Handle the Protocol Staff decision: Approve directly, or Recommend
     * to a selected manager/staff member.
     */
    public function decide(ReviewProtocolRequest $request, ProtocolRequest $protocolRequest)
    {
        abort_if($protocolRequest->status !== ProtocolRequest::STATUS_PENDING, 403,
            'This request is no longer awaiting Protocol Staff review.');

        $data = $request->validated();

        // One transaction, so the guest house assignment rolls back if another
        // staff member decided the request first (the model re-checks under a lock)
        $message = DB::transaction(function () use ($data, $protocolRequest) {
            // If Protocol Staff assigned a physical guest house, persist it
            // on the type-specific row (employee never sets this themselves).
            if ($protocolRequest->request_type === 'guesthouse' && ! empty($data['assigned_guest_house'])) {
                $protocolRequest->requestable?->update([
                    'assigned_guest_house' => $data['assigned_guest_house'],
                ]);
            }

            $employee = User::select('user_id')->find(Auth::id());
            if ($data['decision'] === 'approve') {
                $protocolRequest->approveDirectly($employee->user_id, $data['remarks'] ?? null);
                return "Request {$protocolRequest->request_number} approved.";
            }

            $protocolRequest->recommendTo($employee->user_id, (int) $data['recommended_to_id'], $data['remarks'] ?? null);
            return "Request {$protocolRequest->request_number} recommended for further approval.";
        });

        return redirect()
            ->route('protocol.requests.all')
            ->with('success', $message);
    }

}
    
