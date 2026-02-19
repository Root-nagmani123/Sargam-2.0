<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\SellingVoucherDateRangeReport;
use App\Models\Mess\SellingVoucherDateRangeReportItem;
use App\Services\Mess\AvailableQuantityService;
use App\Models\Mess\Store;
use App\Models\Mess\SubStore;
use App\Models\Mess\ItemSubcategory;
use App\Models\Mess\ClientType;
use App\Models\FacultyMaster;
use App\Models\EmployeeMaster;
use App\Models\DepartmentMaster;
use App\Models\CourseMaster;
use App\Models\StudentMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;

/**
 * Selling Voucher with Date Range - standalone module (design/pattern like Selling Voucher, data/logic separate).
 */
class SellingVoucherDateRangeController extends Controller
{
    public function index(Request $request)
    {
        $query = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'course', 'items.itemSubcategory']);

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

        // DataTables handles pagination/search on the client; return full filtered set.
        $reports = $query->orderBy('date_from', 'desc')
            ->orderBy('id', 'desc')
            ->get();

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
            'inve_store_master_pk' => ['required', function ($attribute, $value, $fail) {
                if (str_starts_with($value, 'sub_')) {
                    $subStoreId = str_replace('sub_', '', $value);
                    if (!\App\Models\Mess\SubStore::where('id', $subStoreId)->exists()) {
                        $fail('The selected store is invalid.');
                    }
                } else {
                    if (!\App\Models\Mess\Store::where('id', $value)->exists()) {
                        $fail('The selected store is invalid.');
                    }
                }
            }],
            'payment_type' => 'required|integer|in:0,1,2,5',
            'client_type_slug' => 'required|string|in:employee,ot,course,other',
            'client_type_pk' => ['nullable', function ($attribute, $value, $fail) use ($request) {
                if ($value === null || $value === '') return;
                $slug = $request->client_type_slug ?? '';
                if (in_array($slug, ['employee', 'other']) && !\App\Models\Mess\ClientType::where('id', $value)->exists()) {
                    $fail('The selected client is invalid.');
                }
                if (in_array($slug, ['ot', 'course']) && !CourseMaster::where('pk', $value)->exists()) {
                    $fail('The selected course is invalid.');
                }
            }],
            'client_name' => in_array($request->client_type_slug, ['ot', 'course']) ? 'required|string|max:255' : 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.available_quantity' => 'nullable|numeric|min:0',
        ]);

        // Enforce: Issue Qty cannot exceed available qty (server-side, cannot be bypassed)
        $storeIdRaw = $request->inve_store_master_pk;
        $storeType = 'store';
        if (str_starts_with($storeIdRaw, 'sub_')) {
            $storeIdRaw = str_replace('sub_', '', $storeIdRaw);
            $storeType = 'sub_store';
        }
        $storeId = (int) $storeIdRaw;
        $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId);

        $requestedByItem = [];
        foreach ((array) $request->items as $row) {
            $itemId = (int) ($row['item_subcategory_id'] ?? 0);
            $qty = (float) ($row['quantity'] ?? 0);
            if ($itemId > 0) $requestedByItem[$itemId] = ($requestedByItem[$itemId] ?? 0) + $qty;
        }

        $subcategories = ItemSubcategory::whereIn('id', array_keys($requestedByItem))->get()->keyBy('id');
        $qtyErrors = [];
        foreach ($requestedByItem as $itemId => $totalQty) {
            $avail = (float) ($availableMap[$itemId] ?? 0);
            if ($totalQty > $avail) {
                $sub = $subcategories->get($itemId);
                $name = $sub ? ($sub->item_name ?? $sub->name ?? ('Item #' . $itemId)) : ('Item #' . $itemId);
                $qtyErrors[] = "{$name}: issue {$totalQty} cannot exceed available {$avail}.";
            }
        }
        if (!empty($qtyErrors)) {
            $bag = new MessageBag(['items' => implode(' ', $qtyErrors)]);
            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->withInput()
                ->withErrors($bag)
                ->with('open_add_modal', true);
        }

        try {
            DB::beginTransaction();

            // storeId + storeType already normalized above

            $issueDate = $request->issue_date;
            $report = SellingVoucherDateRangeReport::create([
                'date_from' => $issueDate,
                'date_to' => $issueDate,
                'store_id' => $storeId,
                'store_type' => $storeType,
                'report_title' => null,
                'status' => SellingVoucherDateRangeReport::STATUS_DRAFT,
                'total_amount' => 0,
                'remarks' => $request->remarks,
                'client_type_slug' => $request->client_type_slug,
                'client_type_pk' => $request->filled('client_type_pk') ? (int) $request->client_type_pk : null,
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
        $report = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'course', 'items.itemSubcategory'])->findOrFail($id);

        if ($request->wantsJson()) {
            $issueDateFormatted = $report->issue_date ? $report->issue_date->format('d/m/Y') : '—';
            $voucher = [
                'id' => $report->id,
                'request_date' => $report->date_from ? $report->date_from->format('d/m/Y') : '—',
                'date_from' => $report->date_from ? $report->date_from->format('d/m/Y') : '—',
                'date_to' => $report->date_to ? $report->date_to->format('d/m/Y') : '—',
                'store_name' => $report->resolved_store_name,
                'report_title' => $report->report_title ?? '—',
                'status' => $report->status,
                'status_label' => SellingVoucherDateRangeReport::statusLabels()[$report->status] ?? '—',
                'client_type' => $report->clientTypeCategory ? ucfirst($report->clientTypeCategory->client_type ?? '') : ($report->client_type_slug ? ucfirst($report->client_type_slug) : '—'),
                'client_name' => $report->display_client_name,
                'client_name_text' => $report->client_name ?? '—',
                'payment_type' => $report->payment_type == 1 ? 'Credit' : ($report->payment_type == 0 ? 'Cash' : ($report->payment_type == 2 ? 'Online' : '—')),
                'issue_date' => $issueDateFormatted,
                'remarks' => $report->remarks ?? '',
                'created_at' => $report->created_at ? $report->created_at->format('d/m/Y H:i') : '—',
                'updated_at' => $report->updated_at ? $report->updated_at->format('d/m/Y H:i') : null,
            ];
            $items = $report->items->map(function ($item) use ($issueDateFormatted) {
                $qty = (float) $item->quantity;
                $retQty = (float) ($item->return_quantity ?? 0);
                $rate = (float) $item->rate;
                $amount = max(0, $qty - $retQty) * $rate;
                return [
                    'item_name' => $item->item_name ?: ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                    'unit' => $item->unit ?? '—',
                    'quantity' => $qty,
                    'available_quantity' => (float) ($item->available_quantity ?? 0),
                    'return_quantity' => $retQty,
                    'issue_date' => $issueDateFormatted,
                    'rate' => number_format($item->rate, 2),
                    'amount' => number_format($amount, 2),
                ];
            })->values()->toArray();
            $grand_total = $report->items->sum(function ($item) {
                $qty = (float) $item->quantity;
                $retQty = (float) ($item->return_quantity ?? 0);
                return max(0, $qty - $retQty) * (float) $item->rate;
            });
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
            $storeType = $report->store_type ?? 'store';
            $storeId = (int) $report->store_id;
            $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId);

            $voucher = [
                'id' => $report->id,
                'date_from' => $report->date_from ? $report->date_from->format('Y-m-d') : '',
                'date_to' => $report->date_to ? $report->date_to->format('Y-m-d') : '',
                'store_id' => $storeType === 'sub_store' ? 'sub_' . $storeId : (string) $storeId,
                'report_title' => $report->report_title,
                'status' => (int) $report->status,
                'remarks' => $report->remarks,
                'client_type_slug' => $report->client_type_slug ?? $clientTypeSlug,
                'client_type_pk' => $report->client_type_pk,
                'client_name' => $report->client_name,
                'payment_type' => (int) $report->payment_type,
                'issue_date' => $report->issue_date ? $report->issue_date->format('Y-m-d') : '',
            ];
            $items = $report->items->map(function ($item) use ($availableMap) {
                $itemId = (int) ($item->item_subcategory_id ?? 0);
                $currentAvailable = $itemId > 0 ? (float) ($availableMap[$itemId] ?? 0) : (float) ($item->available_quantity ?? 0);
                return [
                    'item_subcategory_id' => $item->item_subcategory_id,
                    'item_name' => $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                    'unit' => $item->unit ?? '',
                    'quantity' => (float) $item->quantity,
                    'available_quantity' => $currentAvailable,
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
            'inve_store_master_pk' => ['required', function ($attribute, $value, $fail) {
                if (str_starts_with($value, 'sub_')) {
                    $subStoreId = str_replace('sub_', '', $value);
                    if (!\App\Models\Mess\SubStore::where('id', $subStoreId)->exists()) {
                        $fail('The selected store is invalid.');
                    }
                } else {
                    if (!\App\Models\Mess\Store::where('id', $value)->exists()) {
                        $fail('The selected store is invalid.');
                    }
                }
            }],
            'payment_type' => 'required|integer|in:0,1,2,5',
            'client_type_slug' => 'required|string|in:employee,ot,course,other',
            'client_type_pk' => ['nullable', function ($attribute, $value, $fail) use ($request) {
                if ($value === null || $value === '') return;
                $slug = $request->client_type_slug ?? '';
                if (in_array($slug, ['employee', 'other']) && !\App\Models\Mess\ClientType::where('id', $value)->exists()) {
                    $fail('The selected client is invalid.');
                }
                if (in_array($slug, ['ot', 'course']) && !CourseMaster::where('pk', $value)->exists()) {
                    $fail('The selected course is invalid.');
                }
            }],
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

            $storeId = $request->inve_store_master_pk;
            $storeType = 'store';
            if (str_starts_with($storeId, 'sub_')) {
                $storeId = str_replace('sub_', '', $storeId);
                $storeType = 'sub_store';
            }
            $storeId = (int) $storeId;

            // Enforce: Issue Qty cannot exceed available qty (server-side)
            $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId);
            $requestedByItem = [];
            foreach ((array) $request->items as $row) {
                $itemId = (int) ($row['item_subcategory_id'] ?? 0);
                $qty = (float) ($row['quantity'] ?? 0);
                if ($itemId > 0) $requestedByItem[$itemId] = ($requestedByItem[$itemId] ?? 0) + $qty;
            }
            $subcategoriesForMsg = ItemSubcategory::whereIn('id', array_keys($requestedByItem))->get()->keyBy('id');
            foreach ($requestedByItem as $itemId => $totalQty) {
                $avail = (float) ($availableMap[$itemId] ?? 0);
                if ($totalQty > $avail) {
                    $sub = $subcategoriesForMsg->get($itemId);
                    $name = $sub ? ($sub->item_name ?? $sub->name ?? ('Item #' . $itemId)) : ('Item #' . $itemId);
                    DB::rollBack();
                    return redirect()->route('admin.mess.selling-voucher-date-range.index')
                        ->withInput()
                        ->with('error', "{$name}: issue {$totalQty} cannot exceed available {$avail}.");
                }
            }

            $issueDate = $request->issue_date;
            $report->update([
                'date_from' => $issueDate,
                'date_to' => $issueDate,
                'store_id' => $storeId,
                'store_type' => $storeType,
                'report_title' => null,
                'status' => SellingVoucherDateRangeReport::STATUS_DRAFT,
                'remarks' => $request->remarks,
                'client_type_slug' => $request->client_type_slug,
                'client_type_pk' => $request->filled('client_type_pk') ? (int) $request->client_type_pk : null,
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
            'store_name' => $report->resolved_store_name,
            'issue_date' => $report->issue_date ? $report->issue_date->format('Y-m-d') : '',
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
                $issuedQty = (float) ($item->quantity ?? 0);
                if ($returnQty > $issuedQty) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Return quantity cannot be greater than issued quantity.');
                }
                if (!empty($returnDate) && $report->issue_date) {
                    try {
                        $ret = Carbon::parse($returnDate)->startOfDay();
                        $iss = Carbon::parse($report->issue_date)->startOfDay();
                        if ($ret->lt($iss)) {
                            DB::rollBack();
                            return back()->withInput()->with('error', 'Return date cannot be earlier than issue date.');
                        }
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'Invalid return date.');
                    }
                }
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
        $storeType = 'store';
        $storeId = (int) $storeIdentifier;

        // Check if it's a sub-store (prefixed with 'sub_')
        if (strpos($storeIdentifier, 'sub_') === 0) {
            $storeType = 'sub_store';
            $storeId = (int) str_replace('sub_', '', $storeIdentifier);

            // Get items with their allocated quantities and store-specific rate (weighted avg unit_price)
            $allocatedItems = DB::table('mess_store_allocation_items as sai')
                ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
                ->where('sa.sub_store_id', $storeId)
                ->select(
                    'sai.item_subcategory_id',
                    DB::raw('SUM(sai.quantity) as total_quantity'),
                    DB::raw('SUM(sai.quantity * sai.unit_price) / NULLIF(SUM(sai.quantity), 0) as avg_unit_price')
                )
                ->groupBy('sai.item_subcategory_id')
                ->get()
                ->keyBy('item_subcategory_id');

            $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId);

            if ($allocatedItems->isNotEmpty()) {
                $itemIds = $allocatedItems->keys();
                $items = ItemSubcategory::whereIn('id', $itemIds)
                    ->active()
                    ->get()
                    ->map(function ($s) use ($allocatedItems, $availableMap) {
                        $allocated = $allocatedItems->get($s->id);
                        $storeRate = $allocated && isset($allocated->avg_unit_price) ? (float) $allocated->avg_unit_price : null;
                        $rawTiers = $tiersByItem[$s->id] ?? [];
                        $available = (float) ($availableMap[$s->id] ?? 0);
                        $totalAllocated = array_sum(array_column($rawTiers, 'quantity'));
                        $issued = max(0, $totalAllocated - $available);
                        $adjustedTiers = [];
                        $remainingIssued = $issued;
                        foreach ($rawTiers as $t) {
                            $qty = (float) ($t['quantity'] ?? 0);
                            $take = min($remainingIssued, $qty);
                            $remaining = $qty - $take;
                            $remainingIssued -= $take;
                            if ($remaining > 0) {
                                $adjustedTiers[] = ['quantity' => $remaining, 'unit_price' => (float) ($t['unit_price'] ?? 0)];
                            }
                        }
                        $tiers = $adjustedTiers;
                        $firstPrice = !empty($tiers) ? $tiers[0]['unit_price'] : null;
                        return [
                            'id' => $s->id,
                            'item_name' => $s->item_name ?? $s->name ?? '—',
                            'unit_measurement' => $s->unit_measurement ?? '—',
                            'standard_cost' => $storeRate !== null ? $storeRate : ($s->standard_cost ?? 0),
                            'available_quantity' => (float) ($availableMap[$s->id] ?? 0),
                        ];
                    });
            }
        } else {
            // Main store: get items from purchase orders
            $purchasedItems = DB::table('mess_purchase_order_items as poi')
                ->join('mess_purchase_orders as po', 'poi.purchase_order_id', '=', 'po.id')
                ->where('po.store_id', $storeId)
                ->where('po.status', 'approved')
                ->select(
                    'poi.item_subcategory_id',
                    DB::raw('SUM(poi.quantity) as total_quantity'),
                    DB::raw('SUM(poi.quantity * poi.unit_price) / NULLIF(SUM(poi.quantity), 0) as avg_unit_price')
                )
                ->groupBy('poi.item_subcategory_id')
                ->get()
                ->keyBy('item_subcategory_id');

            $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId);

            if ($purchasedItems->isNotEmpty()) {
                $itemIds = $purchasedItems->keys();
                $items = ItemSubcategory::whereIn('id', $itemIds)
                    ->active()
                    ->get()
                    ->map(function ($s) use ($purchasedItems, $availableMap) {
                        $purchased = $purchasedItems->get($s->id);
                        $storeRate = $purchased && isset($purchased->avg_unit_price) ? (float) $purchased->avg_unit_price : null;
                        $rawTiers = $tiersByItem[$s->id] ?? [];
                        $available = (float) ($availableMap[$s->id] ?? 0);
                        // Adjust tiers: subtract already-sold qty (FIFO) to get remaining per tier
                        $totalPurchased = array_sum(array_column($rawTiers, 'quantity'));
                        $issued = max(0, $totalPurchased - $available);
                        $adjustedTiers = [];
                        $remainingIssued = $issued;
                        foreach ($rawTiers as $t) {
                            $qty = (float) ($t['quantity'] ?? 0);
                            $take = min($remainingIssued, $qty);
                            $remaining = $qty - $take;
                            $remainingIssued -= $take;
                            if ($remaining > 0) {
                                $adjustedTiers[] = ['quantity' => $remaining, 'unit_price' => (float) ($t['unit_price'] ?? 0)];
                            }
                        }
                        $tiers = $adjustedTiers;
                        $firstPrice = !empty($tiers) ? $tiers[0]['unit_price'] : null;
                        return [
                            'id' => $s->id,
                            'item_name' => $s->item_name ?? $s->name ?? '—',
                            'unit_measurement' => $s->unit_measurement ?? '—',
                            'standard_cost' => $storeRate !== null ? $storeRate : ($s->standard_cost ?? 0),
                            'available_quantity' => (float) ($availableMap[$s->id] ?? 0),
                        ];
                    });
            }
        }

        return response()->json($items->values());
    }
}
