<?php

namespace Modules\Protocol\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Http\Requests\ManagerDecisionRequest;

class ManagerApprovalController extends Controller
{
    /**
     * Requests that Protocol Staff has recommended to the logged-in manager.
     */
    public function queue()
    {
        $requests = ProtocolRequest::with('requestable', 'batch', 'protocolStaff')
            ->awaitingManager(Auth::id())
            ->latest('recommended_at')
            ->paginate(config('protocol.per_page'));

        return view('protocol::manager.queue', compact('requests'));
    }

    /**
     * Final decision screen for a single recommended request.
     */
    public function review(ProtocolRequest $protocolRequest)
    {
        abort_if(
            $protocolRequest->status !== ProtocolRequest::STATUS_RECOMMENDED
                || $protocolRequest->recommended_to_id !== Auth::id(),
            403,
            'This request is not awaiting your decision.'
        );

        $protocolRequest->loadFullRequestable();
        $protocolRequest->load('batch.guests', 'logs.actionBy');

        return view('protocol::manager.review', compact('protocolRequest'));
    }

    /**
     * Handle the manager's final Approve / Reject decision.
     */
    public function decide(ManagerDecisionRequest $request, ProtocolRequest $protocolRequest)
    {
        abort_if(
            $protocolRequest->status !== ProtocolRequest::STATUS_RECOMMENDED
                || $protocolRequest->recommended_to_id !== Auth::id(),
            403,
            'This request is not awaiting your decision.'
        );

        $data = $request->validated();

        // Actor ids on the log and decided_by_id are employee_master pks (user_credentials.user_id),
        // the same domain Protocol Staff and the employee are recorded in
        $managerEmployeeId = (int) Auth::user()->user_id;

        if ($data['decision'] === 'approve') {
            $protocolRequest->approveByManager($managerEmployeeId, $data['remarks'] ?? null);
            $message = "Request {$protocolRequest->request_number} approved.";
        } else {
            $protocolRequest->rejectByManager($managerEmployeeId, $data['remarks'] ?? null);
            $message = "Request {$protocolRequest->request_number} rejected.";
        }

        return redirect()
            ->route('protocol.manager.queue')
            ->with('success', $message);
    }
}
