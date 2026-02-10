<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\SellingVoucherDateRangeReport;
use App\Models\Mess\SellingVoucherDateRangeReportItem;
use App\Models\Mess\Store;
use App\Models\Mess\SubStore;
use App\Models\Mess\ItemSubcategory;
use App\Models\Mess\ClientType;
use App\Models\FacultyMaster;
use App\Models\EmployeeMaster;
use App\Models\DepartmentMaster;
use App\Models\CourseMaster;
use App\Models\StudentMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Selling Voucher with Date Range - standalone module (design/pattern like Selling Voucher, data/logic separate).
 */
class SellingVoucherDateRangeController extends Controller
{
    public function index(Request $request)
    {
        $query = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items.itemSubcategory']);

        if ($request->filled('store')) {
            $query->where('store_id', $request->store);
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->where('date_from', '<=', $request->end_date)
                  ->where('date_to', '>=', $request->start_date);
        }

        $reports = $query->orderBy('date_from', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        // Get active stores and sub-stores
        $stores = Store::active()->get()->map(function ($store) {
            return [
                'id' => $store->id,
                'store_name' => $store->store_name,
                'type' => 'store'
            ];
        });
        
        $subStores = SubStore::active()->get()->map(function ($subStore) {
            return [
                'id' => 'sub_' . $subStore->id,
                'store_name' => $subStore->sub_store_name . ' (Sub-Store)',
                'type' => 'sub_store',
                'original_id' => $subStore->id
            ];
        });
        
        // Combine stores and sub-stores
        $stores = $stores->concat($subStores)->sortBy('store_name')->values();
        
        $itemSubcategories = ItemSubcategory::active()->orderBy('name')->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'item_name' => $s->item_name ?? $s->name ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
                'standard_cost' => $s->standard_cost ?? 0,
            ];
        });
        $clientTypes = ClientType::clientTypes();
        $clientNamesByType = ClientType::active()->orderBy('client_type')->orderBy('client_name')->get()->groupBy('client_type');
        $faculties = FacultyMaster::whereNotNull('full_name')->where('full_name', '!=', '')->orderBy('full_name')->get(['pk', 'full_name']);
        $employees = EmployeeMaster::when(Schema::hasColumn('employee_master', 'status'), fn($q) => $q->where('status', 1))
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['pk', 'first_name', 'middle_name', 'last_name'])
            ->map(function ($e) {
                $fullName = trim(($e->first_name ?? '') . ' ' . ($e->middle_name ?? '') . ' ' . ($e->last_name ?? ''));
                return (object) ['pk' => $e->pk, 'full_name' => $fullName ?: '—'];
            })
            ->filter(fn($e) => $e->full_name !== '—')
            ->values();

        $otCourses = CourseMaster::where('active_inactive', 1)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);

        $officersMessDept = DepartmentMaster::where('department_name', 'Officers Mess')->first();
        $messStaff = $officersMessDept
            ? EmployeeMaster::when(Schema::hasColumn('employee_master', 'status'), fn($q) => $q->where('status', 1))
                ->where('department_master_pk', $officersMessDept->pk)
                ->orderBy('first_name')->orderBy('last_name')
                ->get(['pk', 'first_name', 'middle_name', 'last_name'])
                ->map(function ($e) {
                    $fullName = trim(($e->first_name ?? '') . ' ' . ($e->middle_name ?? '') . ' ' . ($e->last_name ?? ''));
                    return (object) ['pk' => $e->pk, 'full_name' => $fullName ?: '—'];
                })
                ->filter(fn($e) => $e->full_name !== '—')
                ->values()
            : collect();

        return view('mess.selling-voucher-date-range.index', compact('reports', 'stores', 'itemSubcategories', 'clientTypes', 'clientNamesByType', 'faculties', 'employees', 'messStaff', 'otCourses'));
    }

    /**
     * Get students by course_pk for OT Client Name flow.
     * Match: course.pk = student_master_course__map.course_master_pk
     * Return student display_name from student_master.
     */
    public function getStudentsByCourse(Request $request, $course_pk)
    {
        $students = StudentMaster::join('student_master_course__map', 'student_master.pk', '=', 'student_master_course__map.student_master_pk')
            ->where('student_master_course__map.course_master_pk', $course_pk)
            ->select('student_master.pk', 'student_master.display_name', 'student_master.generated_OT_code')
            ->orderBy('student_master.display_name')
            ->get();

        return response()->json([
            'students' => $students->map(function($s) {
                $displayName = $s->display_name ?? '—';
                // Append OT code in brackets if available
                if (!empty($s->generated_OT_code)) {
                    $displayName .= ' (' . $s->generated_OT_code . ')';
                }
                return ['pk' => $s->pk, 'display_name' => $displayName];
            })->filter(fn($s) => $s['display_name'] !== '—')->values(),
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.mess.selling-voucher-date-range.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'inve_store_master_pk' => 'required|exists:mess_stores,id',
            'payment_type' => 'required|integer|in:0,1,2,5',
            'client_type_slug' => 'required|string|in:employee,ot,course,other',
            'client_type_pk' => 'nullable|exists:mess_client_types,id',
            'client_name' => in_array($request->client_type_slug, ['ot', 'course']) ? 'required|string|max:255' : 'nullable|string|max:255',
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

            $issueDate = $request->issue_date;
            $report = SellingVoucherDateRangeReport::create([
                'date_from' => $issueDate,
                'date_to' => $issueDate,
                'store_id' => $request->inve_store_master_pk,
                'report_title' => null,
                'status' => SellingVoucherDateRangeReport::STATUS_APPROVED,
                'total_amount' => 0,
                'remarks' => $request->remarks,
                'client_type_slug' => $request->client_type_slug,
                'client_type_pk' => $request->client_type_pk,
                'client_name' => $request->client_name,
                'payment_type' => (int) $request->payment_type,
                'issue_date' => $issueDate,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->pluck('item_subcategory_id'))->get()->keyBy('id');
            $grandTotal = 0;

            foreach ($request->items as $row) {
                $sub = $subcategories->get($row['item_subcategory_id']);
                $qty = (float) ($row['quantity'] ?? 0);
                $rate = (float) ($row['rate'] ?? 0);
                $avail = (float) ($row['available_quantity'] ?? 0);
                $amount = $qty * $rate;
                $grandTotal += $amount;
                SellingVoucherDateRangeReportItem::create([
                    'sv_date_range_report_id' => $report->id,
                    'item_subcategory_id' => $row['item_subcategory_id'],
                    'item_name' => $sub ? ($sub->item_name ?? $sub->name ?? '') : '',
                    'quantity' => $qty,
                    'available_quantity' => $avail,
                    'return_quantity' => 0,
                    'rate' => $rate,
                    'amount' => $amount,
                    'unit' => $sub->unit_measurement ?? '',
                ]);
            }

            $report->update(['total_amount' => $grandTotal]);

            DB::commit();

            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->with('success', 'Date Range Report created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->withInput()
                ->with('error', 'Failed to create report: ' . $e->getMessage())
                ->with('open_add_modal', true);
        }
    }

    public function show(Request $request, $id)
    {
        $report = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items.itemSubcategory'])->findOrFail($id);

        if ($request->wantsJson()) {
            $issueDateFormatted = $report->issue_date ? $report->issue_date->format('d/m/Y') : '—';
            $voucher = [
                'id' => $report->id,
                'request_date' => $report->date_from ? $report->date_from->format('d/m/Y') : '—',
                'date_from' => $report->date_from ? $report->date_from->format('d/m/Y') : '—',
                'date_to' => $report->date_to ? $report->date_to->format('d/m/Y') : '—',
                'store_name' => $report->store->store_name ?? '—',
                'report_title' => $report->report_title ?? '—',
                'status' => $report->status,
                'status_label' => SellingVoucherDateRangeReport::statusLabels()[$report->status] ?? '—',
                'client_type' => $report->clientTypeCategory ? ucfirst($report->clientTypeCategory->client_type ?? '') : ($report->client_type_slug ? ucfirst($report->client_type_slug) : '—'),
                'client_name' => $report->clientTypeCategory ? ($report->clientTypeCategory->client_name ?? '—') : '—',
                'client_name_text' => $report->client_name ?? '—',
                'payment_type' => $report->payment_type == 1 ? 'Credit' : ($report->payment_type == 0 ? 'Cash' : ($report->payment_type == 2 ? 'Online' : '—')),
                'issue_date' => $issueDateFormatted,
                'remarks' => $report->remarks ?? '',
                'created_at' => $report->created_at ? $report->created_at->format('d/m/Y H:i') : '—',
                'updated_at' => $report->updated_at ? $report->updated_at->format('d/m/Y H:i') : null,
            ];
            $items = $report->items->map(function ($item) use ($issueDateFormatted) {
                return [
                    'item_name' => $item->item_name ?: ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                    'unit' => $item->unit ?? '—',
                    'quantity' => (float) $item->quantity,
                    'available_quantity' => (float) ($item->available_quantity ?? 0),
                    'return_quantity' => (float) ($item->return_quantity ?? 0),
                    'issue_date' => $issueDateFormatted,
                    'rate' => number_format($item->rate, 2),
                    'amount' => number_format($item->amount, 2),
                ];
            })->values()->toArray();
            $grand_total = $report->items->sum('amount');
            return response()->json([
                'voucher' => $voucher,
                'items' => $items,
                'grand_total' => number_format($grand_total, 2),
                'has_items' => $report->items->isNotEmpty(),
            ]);
        }

        return redirect()->route('admin.mess.selling-voucher-date-range.index');
    }

    public function edit(Request $request, $id)
    {
        $report = SellingVoucherDateRangeReport::with(['items.itemSubcategory'])->findOrFail($id);

        if ($request->wantsJson()) {
            $clientTypeSlug = $report->clientTypeCategory ? $report->clientTypeCategory->client_type : ($report->client_type_slug ?? 'employee');
            $voucher = [
                'id' => $report->id,
                'date_from' => $report->date_from ? $report->date_from->format('Y-m-d') : '',
                'date_to' => $report->date_to ? $report->date_to->format('Y-m-d') : '',
                'store_id' => $report->store_id,
                'report_title' => $report->report_title,
                'status' => (int) $report->status,
                'remarks' => $report->remarks,
                'client_type_slug' => $report->client_type_slug ?? $clientTypeSlug,
                'client_type_pk' => $report->client_type_pk,
                'client_name' => $report->client_name,
                'payment_type' => (int) $report->payment_type,
                'issue_date' => $report->issue_date ? $report->issue_date->format('Y-m-d') : '',
            ];
            $items = $report->items->map(function ($item) {
                return [
                    'item_subcategory_id' => $item->item_subcategory_id,
                    'item_name' => $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
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

        return redirect()->route('admin.mess.selling-voucher-date-range.index');
    }

    public function update(Request $request, $id)
    {
        $report = SellingVoucherDateRangeReport::findOrFail($id);

        $request->validate([
            'inve_store_master_pk' => 'required|exists:mess_stores,id',
            'payment_type' => 'required|integer|in:0,1,2,5',
            'client_type_slug' => 'required|string|in:employee,ot,course,other',
            'client_type_pk' => 'nullable|exists:mess_client_types,id',
            'client_name' => in_array($request->client_type_slug, ['ot', 'course']) ? 'required|string|max:255' : 'nullable|string|max:255',
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

            $issueDate = $request->issue_date;
            $report->update([
                'date_from' => $issueDate,
                'date_to' => $issueDate,
                'store_id' => $request->inve_store_master_pk,
                'report_title' => null,
                'status' => SellingVoucherDateRangeReport::STATUS_APPROVED,
                'remarks' => $request->remarks,
                'client_type_slug' => $request->client_type_slug,
                'client_type_pk' => $request->client_type_pk,
                'client_name' => $request->client_name,
                'payment_type' => (int) $request->payment_type,
                'issue_date' => $issueDate,
                'updated_by' => Auth::id(),
            ]);

            $report->items()->delete();

            $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->pluck('item_subcategory_id'))->get()->keyBy('id');
            $grandTotal = 0;

            foreach ($request->items as $row) {
                $sub = $subcategories->get($row['item_subcategory_id']);
                $qty = (float) ($row['quantity'] ?? 0);
                $rate = (float) ($row['rate'] ?? 0);
                $avail = (float) ($row['available_quantity'] ?? 0);
                $amount = $qty * $rate;
                $grandTotal += $amount;
                SellingVoucherDateRangeReportItem::create([
                    'sv_date_range_report_id' => $report->id,
                    'item_subcategory_id' => $row['item_subcategory_id'],
                    'item_name' => $sub ? ($sub->item_name ?? $sub->name ?? '') : '',
                    'quantity' => $qty,
                    'available_quantity' => $avail,
                    'return_quantity' => (float) ($row['return_quantity'] ?? 0),
                    'rate' => $rate,
                    'amount' => $amount,
                    'unit' => $sub->unit_measurement ?? '',
                ]);
            }

            $report->update(['total_amount' => $grandTotal]);

            DB::commit();

            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->with('success', 'Date Range Report updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->with('error', 'Failed to update report: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $report = SellingVoucherDateRangeReport::findOrFail($id);
        $report->items()->delete();
        $report->delete();
        return redirect()->route('admin.mess.selling-voucher-date-range.index')
            ->with('success', 'Date Range Report deleted successfully.');
    }

    /**
     * Return modal data (JSON): store name and items with return fields.
     */
    public function returnData(Request $request, $id)
    {
        $report = SellingVoucherDateRangeReport::with(['store', 'items.itemSubcategory'])->findOrFail($id);

        if (!$request->wantsJson()) {
            return redirect()->route('admin.mess.selling-voucher-date-range.index');
        }

        $items = $report->items->map(function ($item) {
            return [
                'id' => $item->id,
                'item_name' => $item->item_name ?: ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit ?? '—',
                'return_quantity' => (float) ($item->return_quantity ?? 0),
                'return_date' => $item->return_date ? $item->return_date->format('Y-m-d') : '',
            ];
        })->values()->toArray();

        return response()->json([
            'store_name' => $report->store->store_name ?? '—',
            'items' => $items,
        ]);
    }

    /**
     * Update return quantities and dates for a selling voucher with date range.
     */
    public function updateReturn(Request $request, $id)
    {
        $report = SellingVoucherDateRangeReport::with('items')->findOrFail($id);

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:sv_date_range_report_items,id',
            'items.*.return_quantity' => 'required|numeric|min:0',
            'items.*.return_date' => 'nullable|date',
        ]);

        $itemIds = $report->items->pluck('id')->toArray();

        try {
            DB::beginTransaction();
            foreach ($request->items as $row) {
                $itemId = (int) $row['id'];
                if (!in_array($itemId, $itemIds, true)) {
                    continue;
                }
                $item = SellingVoucherDateRangeReportItem::find($itemId);
                if (!$item || $item->sv_date_range_report_id != $report->id) {
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
            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->with('success', 'Return updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update return: ' . $e->getMessage());
        }
    }

    /**
     * Get items available in a specific store (main store or sub-store)
     */
    public function getStoreItems($storeIdentifier)
    {
        $items = collect();
        
        // Check if it's a sub-store (prefixed with 'sub_')
        if (strpos($storeIdentifier, 'sub_') === 0) {
            // Sub-store: get items from store allocations
            $subStoreId = (int) str_replace('sub_', '', $storeIdentifier);
            
            // Get items with their allocated quantities
            $allocatedItems = DB::table('mess_store_allocation_items as sai')
                ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
                ->where('sa.sub_store_id', $subStoreId)
                ->select('sai.item_subcategory_id', DB::raw('SUM(sai.quantity) as total_quantity'))
                ->groupBy('sai.item_subcategory_id')
                ->get()
                ->keyBy('item_subcategory_id');
            
            if ($allocatedItems->isNotEmpty()) {
                $itemIds = $allocatedItems->keys();
                $items = ItemSubcategory::whereIn('id', $itemIds)
                    ->active()
                    ->get()
                    ->map(function ($s) use ($allocatedItems) {
                        $allocated = $allocatedItems->get($s->id);
                        return [
                            'id' => $s->id,
                            'item_name' => $s->item_name ?? $s->name ?? '—',
                            'unit_measurement' => $s->unit_measurement ?? '—',
                            'standard_cost' => $s->standard_cost ?? 0,
                            'available_quantity' => $allocated ? (float) $allocated->total_quantity : 0,
                        ];
                    });
            }
        } else {
            // Main store: get items from purchase orders
            $storeId = (int) $storeIdentifier;
            
            // Get items with their purchased quantities
            $purchasedItems = DB::table('mess_purchase_order_items as poi')
                ->join('mess_purchase_orders as po', 'poi.purchase_order_id', '=', 'po.id')
                ->where('po.store_id', $storeId)
                ->where('po.status', 'approved')
                ->select('poi.item_subcategory_id', DB::raw('SUM(poi.quantity) as total_quantity'))
                ->groupBy('poi.item_subcategory_id')
                ->get()
                ->keyBy('item_subcategory_id');
            
            if ($purchasedItems->isNotEmpty()) {
                $itemIds = $purchasedItems->keys();
                $items = ItemSubcategory::whereIn('id', $itemIds)
                    ->active()
                    ->get()
                    ->map(function ($s) use ($purchasedItems) {
                        $purchased = $purchasedItems->get($s->id);
                        return [
                            'id' => $s->id,
                            'item_name' => $s->item_name ?? $s->name ?? '—',
                            'unit_measurement' => $s->unit_measurement ?? '—',
                            'standard_cost' => $s->standard_cost ?? 0,
                            'available_quantity' => $purchased ? (float) $purchased->total_quantity : 0,
                        ];
                    });
            }
        }
        
        return response()->json($items->values());
    }
}
