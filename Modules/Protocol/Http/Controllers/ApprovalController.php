<?php

namespace Modules\Protocol\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Http\Requests\ReviewProtocolRequest;
use App\Models\HostelBuildingMaster;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Modules\Protocol\Services\ApprovalService;

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
        abort_if($protocolRequest->status !== ProtocolRequest::STATUS_PENDING, 403,
            'This request is no longer awaiting Protocol Staff review.');

        $protocolRequest->loadFullRequestable();
        $protocolRequest->load('batch.guests', 'logs.actionBy');
        $GuestHouse = HostelBuildingMaster::orderBy('building_name', 'asc')->pluck('building_name', 'pk');

        // Supply the list of managers/staff that can be recommended to.
        // Swap this for your own role-based user query.
        // $managers = config('auth.providers.users.model')::role(config('protocol.roles.manager'))
        //     ->get(['id', 'name']);

        $managers = null;

        return view('protocol::approval.review', compact('protocolRequest', 'managers','GuestHouse'));
    }

    /**
     * Handle the Protocol Staff decision: Approve directly, or Recommend
     * to a selected manager/staff member.
     */
    public function decide(ReviewProtocolRequest $request, ProtocolRequest $protocolRequest)
    {
        abort_if($protocolRequest->status !== ProtocolRequest::STATUS_PENDING, 403,
            'This request has already been actioned.');

        $data = $request->validated();

        // If Protocol Staff assigned a physical guest house, persist it
        // on the type-specific row (employee never sets this themselves).
        if ($protocolRequest->request_type === 'guesthouse' && ! empty($data['assigned_guest_house'])) {
            $protocolRequest->requestable?->update([
                'assigned_guest_house' => $data['assigned_guest_house'],
            ]);
        }

        if ($data['decision'] === 'approve') {
            $protocolRequest->approveDirectly(Auth::id(), $data['remarks'] ?? null);
            $message = "Request {$protocolRequest->request_number} approved.";
        } else {
            $protocolRequest->recommendTo(Auth::id(), (int) $data['recommended_to_id'], $data['remarks'] ?? null);
            $message = "Request {$protocolRequest->request_number} recommended for further approval.";
        }

        return redirect()
            ->route('protocol.approval.queue')
            ->with('success', $message);
    }

}
    
