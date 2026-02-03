<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\KitchenIssueMaster;
use App\Models\KitchenIssueItem;
use App\Models\KitchenIssuePaymentDetail;
use App\Models\Mess\Store;
use App\Models\Mess\Inventory;
use App\Models\Mess\ItemSubcategory;
use App\Models\Mess\ClientType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KitchenIssueController extends Controller
{
    /**
     * Display a listing of selling vouchers (kitchen issues)
     */
    public function index(Request $request)
    {
        $query = KitchenIssueMaster::with(['storeMaster', 'items.itemSubcategory', 'clientTypeCategory', 'employee', 'student']);

        if ($request->filled('store')) {
            $query->where('inve_store_master_pk', $request->store);
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
            $query->whereBetween('request_date', [$request->start_date, $request->end_date]);
        }

        $kitchenIssues = $query->orderBy('request_date', 'desc')
            ->orderBy('pk', 'desc')
            ->paginate(20);

        $stores = Store::active()->get();
        $itemSubcategories = ItemSubcategory::active()->orderBy('name')->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'item_name' => $s->item_name ?? $s->name ?? '—',
                'item_code' => $s->item_code ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
            ];
        });
        $clientTypes = ClientType::clientTypes();
        $clientNamesByType = ClientType::active()->orderBy('client_type')->orderBy('client_name')->get()
            ->groupBy('client_type');

        return view('mess.kitchen-issues.index', compact('kitchenIssues', 'stores', 'itemSubcategories', 'clientTypes', 'clientNamesByType'));
    }

    /**
     * Show the form for creating a new selling voucher
     */
    public function create()
    {
        $stores = Store::active()->get();
        $itemSubcategories = ItemSubcategory::active()->orderBy('name')->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'item_name' => $s->item_name ?? $s->name ?? '—',
                'item_code' => $s->item_code ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
            ];
        });
        $clientTypes = ClientType::clientTypes();
        $clientNamesByType = ClientType::active()->orderBy('client_type')->orderBy('client_name')->get()
            ->groupBy('client_type');

        return view('mess.kitchen-issues.create', compact('stores', 'itemSubcategories', 'clientTypes', 'clientNamesByType'));
    }

    /**
     * Store a newly created selling voucher
     */
    public function store(Request $request)
    {
        $request->validate([
            'inve_store_master_pk' => 'required|exists:mess_stores,id',
            'payment_type' => 'required|integer|in:0,1,2,5',
            'client_type_slug' => 'required|string|in:employee,ot,course,section,other',
            'client_type_pk' => 'nullable|exists:mess_client_types,id',
            'client_name' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'transfer_to' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.available_quantity' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $master = KitchenIssueMaster::create([
                'inve_item_master_pk' => 0, // 0 = multi-item voucher (items in kitchen_issue_items). Run migration 2026_02_02_120000_make_kitchen_issue_master_inve_item_nullable to allow NULL.
                'inve_store_master_pk' => $request->inve_store_master_pk,
                'quantity' => 0,
                'unit_price' => 0,
                'payment_type' => $request->payment_type,
                'client_type' => 0,
                'client_type_pk' => $request->client_type_pk,
                'client_name' => $request->client_name,
                'employee_student_pk' => 0,
                'issue_date' => $request->issue_date,
                'request_date' => now(),
                'user_id' => Auth::id(),
                'created_by' => Auth::id(),
                'status' => KitchenIssueMaster::STATUS_APPROVED,
                'approve_status' => KitchenIssueMaster::APPROVE_APPROVED,
                'send_for_approval' => 0,
                'notify_status' => 0,
                'paid_unpaid' => KitchenIssueMaster::UNPAID,
                'transfer_to' => $request->transfer_to ?? 0,
                'remarks' => $request->remarks,
            ]);

            $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->pluck('item_subcategory_id'))->get()->keyBy('id');

            foreach ($request->items as $row) {
                $sub = $subcategories->get($row['item_subcategory_id']);
                $qty = (float) ($row['quantity'] ?? 0);
                $rate = (float) ($row['rate'] ?? 0);
                $avail = (float) ($row['available_quantity'] ?? 0);
                KitchenIssueItem::create([
                    'kitchen_issue_master_pk' => $master->pk,
                    'item_subcategory_id' => $row['item_subcategory_id'],
                    'item_name' => $sub ? ($sub->item_name ?? $sub->name ?? '') : '',
                    'quantity' => $qty,
                    'available_quantity' => $avail,
                    'return_quantity' => 0,
                    'rate' => $rate,
                    'amount' => $qty * $rate,
                    'unit' => $sub->unit_measurement ?? '',
                ]);
            }

            DB::commit();

            return redirect()->route('admin.mess.material-management.index')
                ->with('success', 'Selling Voucher created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.mess.material-management.index')
                ->withInput()
                ->with('error', 'Failed to create Selling Voucher: ' . $e->getMessage())
                ->with('open_selling_voucher_modal', true);
        }
    }

    /**
     * Display the specified kitchen issue (JSON for view modal, view for direct URL)
     */
    public function show(Request $request, $id)
    {
        $kitchenIssue = KitchenIssueMaster::with([
            'storeMaster',
            'itemMaster',
            'items.itemSubcategory',
            'clientTypeCategory',
            'paymentDetails',
            'approvals.approver',
            'employee',
            'student'
        ])->findOrFail($id);

        if ($request->wantsJson()) {
            $voucher = [
                'pk' => $kitchenIssue->pk,
                'request_date' => $kitchenIssue->request_date ? $kitchenIssue->request_date->format('d/m/Y') : '-',
                'issue_date' => $kitchenIssue->issue_date ? $kitchenIssue->issue_date->format('d/m/Y') : '-',
                'store_name' => $kitchenIssue->storeMaster->store_name ?? 'N/A',
                'client_type' => $kitchenIssue->clientTypeCategory ? ucfirst($kitchenIssue->clientTypeCategory->client_type ?? '') : '-',
                'client_name' => $kitchenIssue->client_name ?? '-',
                'payment_type' => $kitchenIssue->payment_type == 1 ? 'Credit' : ($kitchenIssue->payment_type == 0 ? 'Cash' : ($kitchenIssue->payment_type == 2 ? 'Online' : '-')),
                'status' => $kitchenIssue->status,
                'status_label' => $kitchenIssue->status == 0 ? 'Pending' : ($kitchenIssue->status == 2 ? 'Approved' : ($kitchenIssue->status == 4 ? 'Completed' : (string)$kitchenIssue->status)),
                'remarks' => $kitchenIssue->remarks ?? '',
                'created_at' => $kitchenIssue->created_at ? $kitchenIssue->created_at->format('d/m/Y H:i') : '-',
                'updated_at' => $kitchenIssue->updated_at ? $kitchenIssue->updated_at->format('d/m/Y H:i') : null,
            ];
            $items = $kitchenIssue->items->map(function ($item) {
                return [
                    'item_name' => $item->item_name ?: ($item->itemSubcategory->item_name ?? '—'),
                    'unit' => $item->unit ?? '—',
                    'quantity' => (float) $item->quantity,
                    'return_quantity' => (float) ($item->return_quantity ?? 0),
                    'rate' => number_format($item->rate, 2),
                    'amount' => number_format($item->amount, 2),
                ];
            })->values()->toArray();
            $grand_total = $kitchenIssue->items->sum('amount');
            $has_items = $kitchenIssue->items->isNotEmpty();
            return response()->json([
                'voucher' => $voucher,
                'items' => $items,
                'grand_total' => number_format($grand_total, 2),
                'has_items' => $has_items,
            ]);
        }

        return view('mess.kitchen-issues.show', compact('kitchenIssue'));
    }

    /**
     * Show the form for editing the specified kitchen issue (JSON for modal, view for direct URL)
     */
    public function edit(Request $request, $id)
    {
        $kitchenIssue = KitchenIssueMaster::with(['items.itemSubcategory', 'clientTypeCategory'])->findOrFail($id);

        if ($kitchenIssue->approve_status == KitchenIssueMaster::APPROVE_APPROVED) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Cannot edit approved voucher'], 403);
            }
            return redirect()->route('admin.mess.material-management.index')
                           ->with('error', 'Cannot edit approved kitchen issue');
        }

        if ($request->wantsJson()) {
            $clientTypeSlug = $kitchenIssue->clientTypeCategory ? $kitchenIssue->clientTypeCategory->client_type : 'employee';
            $voucher = [
                'pk' => $kitchenIssue->pk,
                'payment_type' => (int) $kitchenIssue->payment_type,
                'client_type_pk' => $kitchenIssue->client_type_pk,
                'client_type_slug' => $clientTypeSlug,
                'client_name' => $kitchenIssue->client_name,
                'issue_date' => $kitchenIssue->issue_date ? $kitchenIssue->issue_date->format('Y-m-d') : '',
                'inve_store_master_pk' => $kitchenIssue->inve_store_master_pk,
                'remarks' => $kitchenIssue->remarks,
            ];
            $items = $kitchenIssue->items->map(function ($item) {
                return [
                    'item_subcategory_id' => $item->item_subcategory_id,
                    'item_name' => $item->item_name ?? ($item->itemSubcategory->item_name ?? '—'),
                    'unit' => $item->unit ?? '',
                    'quantity' => (float) $item->quantity,
                    'available_quantity' => (float) ($item->available_quantity ?? 0),
                    'return_quantity' => (float) ($item->return_quantity ?? 0),
                    'rate' => (float) $item->rate,
                    'amount' => (float) $item->amount,
                ];
            })->values()->toArray();
            return response()->json(['voucher' => $voucher, 'items' => $items]);
        }

        $stores = Store::active()->get();
        $items = Inventory::all();
        return view('mess.kitchen-issues.edit', compact('kitchenIssue', 'stores', 'items'));
    }

    /**
     * Update the specified kitchen issue (supports Selling Voucher multi-item)
     */
    public function update(Request $request, $id)
    {
        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

        if ($kitchenIssue->approve_status == KitchenIssueMaster::APPROVE_APPROVED) {
            return redirect()->route('admin.mess.material-management.index')
                           ->with('error', 'Cannot edit approved kitchen issue');
        }

        $request->validate([
            'inve_store_master_pk' => 'required|exists:mess_stores,id',
            'payment_type' => 'required|integer|in:0,1,2,5',
            'client_type_slug' => 'required|string|in:employee,ot,course,section,other',
            'client_type_pk' => 'nullable|exists:mess_client_types,id',
            'client_name' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.available_quantity' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $kitchenIssue->update([
                'inve_store_master_pk' => $request->inve_store_master_pk,
                'payment_type' => $request->payment_type,
                'client_type_pk' => $request->client_type_pk,
                'client_name' => $request->client_name,
                'issue_date' => $request->issue_date,
                'modified_by' => Auth::id(),
                'remarks' => $request->remarks,
            ]);

            $kitchenIssue->items()->delete();
            $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->pluck('item_subcategory_id'))->get()->keyBy('id');
            foreach ($request->items as $row) {
                $sub = $subcategories->get($row['item_subcategory_id']);
                $qty = (float) ($row['quantity'] ?? 0);
                $rate = (float) ($row['rate'] ?? 0);
                $avail = (float) ($row['available_quantity'] ?? 0);
                KitchenIssueItem::create([
                    'kitchen_issue_master_pk' => $kitchenIssue->pk,
                    'item_subcategory_id' => $row['item_subcategory_id'],
                    'item_name' => $sub ? ($sub->item_name ?? $sub->name ?? '') : '',
                    'quantity' => $qty,
                    'available_quantity' => $avail,
                    'return_quantity' => 0,
                    'rate' => $rate,
                    'amount' => $qty * $rate,
                    'unit' => $sub->unit_measurement ?? '',
                ]);
            }

            DB::commit();

            return redirect()->route('admin.mess.material-management.index')
                           ->with('success', 'Selling Voucher updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                        ->with('error', 'Failed to update Selling Voucher: ' . $e->getMessage());
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
     * Return modal data (JSON): store name and items with return fields.
     */
    public function returnData(Request $request, $id)
    {
        $kitchenIssue = KitchenIssueMaster::with(['storeMaster', 'items.itemSubcategory'])->findOrFail($id);

        if (!$request->wantsJson()) {
            return redirect()->route('admin.mess.material-management.index');
        }

        $items = $kitchenIssue->items->map(function ($item) {
            return [
                'id' => $item->pk,
                'item_name' => $item->item_name ?: ($item->itemSubcategory->item_name ?? '—'),
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit ?? '—',
                'return_quantity' => (float) ($item->return_quantity ?? 0),
                'return_date' => $item->return_date ? $item->return_date->format('Y-m-d') : '',
            ];
        })->values()->toArray();

        return response()->json([
            'store_name' => $kitchenIssue->storeMaster->store_name ?? '—',
            'items' => $items,
        ]);
    }

    /**
     * Update return quantities and dates for a selling voucher.
     */
    public function updateReturn(Request $request, $id)
    {
        $kitchenIssue = KitchenIssueMaster::with('items')->findOrFail($id);

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:kitchen_issue_items,pk',
            'items.*.return_quantity' => 'required|numeric|min:0',
            'items.*.return_date' => 'nullable|date',
        ]);

        $itemIds = $kitchenIssue->items->pluck('pk')->toArray();

        try {
            DB::beginTransaction();
            foreach ($request->items as $row) {
                $itemPk = (int) $row['id'];
                if (!in_array($itemPk, $itemIds, true)) {
                    continue;
                }
                $item = KitchenIssueItem::find($itemPk);
                if (!$item || $item->kitchen_issue_master_pk != $kitchenIssue->pk) {
                    continue;
                }
                $returnQty = (float) ($row['return_quantity'] ?? 0);
                $returnDate = !empty($row['return_date']) ? $row['return_date'] : null;
                $item->update([
                    'return_quantity' => $returnQty,
                    'return_date' => $returnDate,
                ]);
            }
            DB::commit();
            return redirect()->route('admin.mess.material-management.index')
                ->with('success', 'Return updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update return: ' . $e->getMessage());
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
