<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\KitchenIssueMaster;
use App\Models\KitchenIssueItem;
use App\Models\KitchenIssuePaymentDetail;
use App\Models\Mess\Store;
use App\Models\Mess\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KitchenIssueController extends Controller
{
    /**
     * Display a listing of kitchen issues
     */
    public function index(Request $request)
    {
        $query = KitchenIssueMaster::with(['storeMaster', 'itemMaster', 'employee', 'student']);

        // Filters
        if ($request->filled('store_id')) {
            $query->where('inve_store_master_pk', $request->store_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('approve_status')) {
            $query->where('approve_status', $request->approve_status);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('request_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $kitchenIssues = $query->orderBy('request_date', 'desc')
                               ->orderBy('pk', 'desc')
                               ->paginate(20);

        $stores = Store::all();

        return view('mess.kitchen-issues.index', compact('kitchenIssues', 'stores'));
    }

    /**
     * Show the form for creating a new kitchen issue
     */
    public function create()
    {
        $stores = Store::all();
        $items = Inventory::all();

        return view('mess.kitchen-issues.create', compact('stores', 'items'));
    }

    /**
     * Store a newly created kitchen issue
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inve_store_master_pk' => 'required|exists:canteen_store_master,pk',
            'inve_item_master_pk' => 'required',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'payment_type' => 'required|integer|in:0,1,2,5',
            'client_type' => 'nullable|integer',
            'client_name' => 'nullable|string|max:255',
            'employee_student_pk' => 'nullable|integer',
            'issue_date' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $kitchenIssue = KitchenIssueMaster::create([
                'inve_item_master_pk' => $request->inve_item_master_pk,
                'inve_store_master_pk' => $request->inve_store_master_pk,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'payment_type' => $request->payment_type,
                'client_type' => $request->client_type ?? 0,
                'client_name' => $request->client_name,
                'employee_student_pk' => $request->employee_student_pk ?? 0,
                'issue_date' => $request->issue_date ?? now(),
                'request_date' => now(),
                'user_id' => Auth::id(),
                'created_by' => Auth::id(),
                'status' => KitchenIssueMaster::STATUS_PENDING,
                'approve_status' => KitchenIssueMaster::APPROVE_PENDING,
                'send_for_approval' => 0,
                'notify_status' => 0,
                'paid_unpaid' => KitchenIssueMaster::UNPAID,
                'remarks' => $request->remarks,
            ]);

            DB::commit();

            return redirect()->route('admin.mess.material-management.index')
                           ->with('success', 'Material Management created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                        ->with('error', 'Failed to create Material Management: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified kitchen issue
     */
    public function show($id)
    {
        $kitchenIssue = KitchenIssueMaster::with([
            'storeMaster',
            'itemMaster',
            'items',
            'paymentDetails',
            'approvals.approver',
            'employee',
            'student'
        ])->findOrFail($id);

        return view('mess.kitchen-issues.show', compact('kitchenIssue'));
    }

    /**
     * Show the form for editing the specified kitchen issue
     */
    public function edit($id)
    {
        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

        // Only allow editing if not approved
        if ($kitchenIssue->approve_status == KitchenIssueMaster::APPROVE_APPROVED) {
            return redirect()->route('admin.mess.material-management.index')
                           ->with('error', 'Cannot edit approved kitchen issue');
        }

        $stores = Store::all();
        $items = Inventory::all();

        return view('mess.kitchen-issues.edit', compact('kitchenIssue', 'stores', 'items'));
    }

    /**
     * Update the specified kitchen issue
     */
    public function update(Request $request, $id)
    {
        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

        // Only allow editing if not approved
        if ($kitchenIssue->approve_status == KitchenIssueMaster::APPROVE_APPROVED) {
            return redirect()->route('admin.mess.material-management.index')
                           ->with('error', 'Cannot edit approved kitchen issue');
        }

        $validated = $request->validate([
            'inve_store_master_pk' => 'required|exists:canteen_store_master,pk',
            'inve_item_master_pk' => 'required',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'payment_type' => 'required|integer|in:0,1,2,5',
            'client_type' => 'nullable|integer',
            'client_name' => 'nullable|string|max:255',
            'employee_student_pk' => 'nullable|integer',
            'issue_date' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $kitchenIssue->update([
                'inve_item_master_pk' => $request->inve_item_master_pk,
                'inve_store_master_pk' => $request->inve_store_master_pk,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'payment_type' => $request->payment_type,
                'client_type' => $request->client_type ?? 0,
                'client_name' => $request->client_name,
                'employee_student_pk' => $request->employee_student_pk ?? 0,
                'issue_date' => $request->issue_date,
                'modified_by' => Auth::id(),
                'remarks' => $request->remarks,
            ]);

            DB::commit();

            return redirect()->route('admin.mess.material-management.index')
                           ->with('success', 'Material Management updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                        ->with('error', 'Failed to update Material Management: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified kitchen issue
     */
    public function destroy($id)
    {
        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

        // Only allow deletion if not approved or completed
        if ($kitchenIssue->approve_status == KitchenIssueMaster::APPROVE_APPROVED ||
            $kitchenIssue->status == KitchenIssueMaster::STATUS_COMPLETED) {
            return redirect()->route('admin.mess.material-management.index')
                           ->with('error', 'Cannot delete approved or completed kitchen issue');
        }

        try {
            $kitchenIssue->delete();

            return redirect()->route('admin.mess.material-management.index')
                           ->with('success', 'Material Management deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete Material Management: ' . $e->getMessage());
        }
    }

    /**
     * Get kitchen issues by store and date range (AJAX)
     */
    public function getKitchenIssueRecords(Request $request)
    {
        $query = KitchenIssueMaster::with(['storeMaster', 'itemMaster', 'employee', 'student', 'paymentDetails']);

        if ($request->filled('messId')) {
            $query->where('inve_store_master_pk', $request->messId);
        }

        if ($request->filled('sDate') && $request->filled('eDate')) {
            $query->whereBetween('request_date', [$request->sDate, $request->eDate]);
        }

        if ($request->filled('paymode')) {
            $query->where('payment_type', $request->paymode);
        }

        if ($request->filled('action')) {
            if ($request->action == 'approved') {
                $query->where('approve_status', KitchenIssueMaster::APPROVE_APPROVED);
            } elseif ($request->action == 'pending') {
                $query->where('approve_status', KitchenIssueMaster::APPROVE_PENDING);
            }
        }

        $records = $query->orderBy('request_date', 'desc')->get();

        return response()->json($records);
    }

    /**
     * Send kitchen issue for approval
     */
    public function sendForApproval($id)
    {
        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

        if ($kitchenIssue->send_for_approval == 1) {
            return back()->with('error', 'Material Management already sent for approval');
        }

        try {
            $kitchenIssue->update([
                'send_for_approval' => 1,
                'notify_status' => 0,
                'modified_by' => Auth::id(),
            ]);

            return back()->with('success', 'Material Management sent for approval successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send for approval: ' . $e->getMessage());
        }
    }

    /**
     * Generate bill report
     */
    public function billReport(Request $request)
    {
        $query = KitchenIssueMaster::with(['storeMaster', 'itemMaster', 'employee', 'student', 'paymentDetails']);

        if ($request->filled('messId')) {
            $query->where('inve_store_master_pk', $request->messId);
        }

        if ($request->filled('empId')) {
            $query->where('employee_student_pk', $request->empId);
        }

        if ($request->filled('sDate') && $request->filled('eDate')) {
            $query->whereBetween('request_date', [$request->sDate, $request->eDate]);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('invoice')) {
            $query->whereHas('paymentDetails', function ($q) use ($request) {
                $q->where('invoice_no', $request->invoice);
            });
        }

        $kitchenIssues = $query->orderBy('request_date', 'desc')->get();

        $stores = Store::all();

        return view('mess.kitchen-issues.bill-report', compact('kitchenIssues', 'stores'));
    }
}
