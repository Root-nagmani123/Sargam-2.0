<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\EmployeeIDCardRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeIDCardApprovalController extends Controller
{
    /**
     * Approval I - List requests awaiting Approver 1.
     */
    public function approval1(Request $request)
    {
        $query = EmployeeIDCardRequest::awaitingApprover1()
            ->with(['approver1', 'approver2'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('card_type', 'like', "%{$search}%")
                    ->orWhere('request_for', 'like', "%{$search}%");
            });
        }

        if ($request->filled('card_type')) {
            $query->where('card_type', $request->card_type);
        }

        $requests = $query->paginate(10)->withQueryString();

        return view('admin.security.employee_idcard_approval.approval1', compact('requests'));
    }

    /**
     * Approval II - List requests awaiting Approver 2.
     */
    public function approval2(Request $request)
    {
        $query = EmployeeIDCardRequest::awaitingApprover2()
            ->with(['approver1', 'approver2'])
            ->orderBy('approved_by_a1_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('card_type', 'like', "%{$search}%")
                    ->orWhere('request_for', 'like', "%{$search}%");
            });
        }

        if ($request->filled('card_type')) {
            $query->where('card_type', $request->card_type);
        }

        $requests = $query->paginate(10)->withQueryString();

        return view('admin.security.employee_idcard_approval.approval2', compact('requests'));
    }

    /**
     * Show single request details for approval.
     */
    public function show($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $request = EmployeeIDCardRequest::with(['approver1', 'approver2', 'rejectedByUser'])
            ->findOrFail($pk);

        return view('admin.security.employee_idcard_approval.show', compact('request'));
    }

    /**
     * Approve by Approver 1.
     */
    public function approve1(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $idcardRequest = EmployeeIDCardRequest::findOrFail($pk);

        if ($idcardRequest->approved_by_a1 !== null || $idcardRequest->rejected_by !== null || $idcardRequest->status !== 'Pending') {
            return redirect()->back()->with('error', 'This request is not pending your approval.');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $idcardRequest->approved_by_a1 = $employeePk;
        $idcardRequest->approved_by_a1_at = now();
        $idcardRequest->save();

        return redirect()->route('admin.security.employee_idcard_approval.approval1')
            ->with('success', 'Request approved successfully. It will now move to Approver 2.');
    }

    /**
     * Approve by Approver 2.
     */
    public function approve2(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $idcardRequest = EmployeeIDCardRequest::findOrFail($pk);

        if ($idcardRequest->approved_by_a1 === null || $idcardRequest->approved_by_a2 !== null || $idcardRequest->rejected_by !== null || $idcardRequest->status !== 'Pending') {
            return redirect()->back()->with('error', 'This request is not pending your approval.');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $idcardRequest->approved_by_a2 = $employeePk;
        $idcardRequest->approved_by_a2_at = now();
        $idcardRequest->status = 'Approved';
        $idcardRequest->save();

        return redirect()->route('admin.security.employee_idcard_approval.approval2')
            ->with('success', 'Request approved successfully. ID card is now fully approved.');
    }

    /**
     * Reject by Approver 1.
     */
    public function reject1(Request $request, $id)
    {
        return $this->reject($request, $id, 1);
    }

    /**
     * Reject by Approver 2.
     */
    public function reject2(Request $request, $id)
    {
        return $this->reject($request, $id, 2);
    }

    /**
     * Common reject logic.
     */
    protected function reject(Request $request, $id, int $stage)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $idcardRequest = EmployeeIDCardRequest::findOrFail($pk);

        $isValid = $stage === 1
            ? ($idcardRequest->approved_by_a1 === null && $idcardRequest->rejected_by === null && $idcardRequest->status === 'Pending')
            : ($idcardRequest->approved_by_a1 !== null && $idcardRequest->approved_by_a2 === null && $idcardRequest->rejected_by === null && $idcardRequest->status === 'Pending');
        if (!$isValid) {
            return redirect()->back()->with('error', 'This request is not pending your action.');
        }

        $user = Auth::user();
        $employeePk = $user->user_id ?? null;

        $idcardRequest->rejection_reason = $validated['rejection_reason'];
        $idcardRequest->rejected_by = $employeePk;
        $idcardRequest->rejected_at = now();
        $idcardRequest->status = 'Rejected';
        $idcardRequest->save();

        $route = $stage === 1 ? 'admin.security.employee_idcard_approval.approval1' : 'admin.security.employee_idcard_approval.approval2';

        return redirect()->route($route)->with('success', 'Request rejected.');
    }

    /**
     * All ID card requests with approval status (summary view).
     */
    public function all(Request $request)
    {
        $query = EmployeeIDCardRequest::with(['approver1', 'approver2', 'rejectedByUser'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('card_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'Pending_A1') {
                $query->awaitingApprover1();
            } elseif ($request->status === 'Pending_A2') {
                $query->awaitingApprover2();
            } else {
                $query->where('status', $request->status);
            }
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('admin.security.employee_idcard_approval.all', compact('requests'));
    }
}
