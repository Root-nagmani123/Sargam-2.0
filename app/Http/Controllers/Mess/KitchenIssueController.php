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
use App\Models\FacultyMaster;
use App\Models\EmployeeMaster;
use App\Models\DepartmentMaster;
use App\Models\CourseMaster;
use App\Models\StudentMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class KitchenIssueController extends Controller
{
    /**
     * Display a listing of selling vouchers (kitchen issues)
     */
    public function index(Request $request)
    {
        $query = KitchenIssueMaster::with(['store', 'items.itemSubcategory', 'clientTypeCategory', 'employee', 'student']);

        if ($request->filled('store')) {
            $query->where('store_id', $request->store);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }
        if ($request->filled('client_type')) {
            $query->where('client_type', $request->client_type);
        }
        if ($request->filled('kitchen_issue_type')) {
            $query->where('kitchen_issue_type', $request->kitchen_issue_type);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('issue_date', [$request->start_date, $request->end_date]);
        }

        $kitchenIssues = $query->orderBy('issue_date', 'desc')
            ->orderBy('pk', 'desc')
            ->paginate(20);

        $otCourses = CourseMaster::where('active_inactive', 1)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);

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

        return view('mess.kitchen-issues.index', compact('kitchenIssues', 'stores', 'itemSubcategories', 'clientTypes', 'clientNamesByType', 'faculties', 'employees', 'messStaff', 'otCourses'));
    }

    /**
     * Get students by course_pk for OT Client Name flow.
     * Match: course.pk = student_master_course__map.course_master_pk
     * Return student display_name from student_master.
     */
    public function getStudentsByCourse(Request $request, $course_pk)
    {
        $students = StudentMaster::join(
            'student_master_course__map',
            'student_master.pk',
            '=',
            'student_master_course__map.student_master_pk'
        )
        ->where('student_master_course__map.course_master_pk', $course_pk)
        ->select('student_master.pk', 'student_master.display_name')
        ->orderBy('student_master.display_name')
        ->get();

        return response()->json([
            'students' => $students->map(fn($s) => ['pk' => $s->pk, 'display_name' => $s->display_name ?? '—'])->filter(fn($s) => $s['display_name'] !== '—')->values(),
        ]);
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

        return view('mess.kitchen-issues.create', compact('stores', 'itemSubcategories', 'clientTypes', 'clientNamesByType', 'faculties', 'employees', 'messStaff'));
    }

    /**
     * Store a newly created selling voucher
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:mess_stores,id',
            'payment_type' => 'required|integer|in:0,1,2',
            'client_type_slug' => 'required|string|in:employee,ot,course,other',
            'client_type_pk' => 'nullable|exists:mess_client_types,id',
            'client_id' => 'nullable|integer',
            'name_id' => 'nullable|integer',
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

            // Map client_type_slug to numeric value
            $clientTypeMap = [
                'employee' => KitchenIssueMaster::CLIENT_EMPLOYEE,
                'ot' => KitchenIssueMaster::CLIENT_OT,
                'course' => KitchenIssueMaster::CLIENT_COURSE,
                'other' => KitchenIssueMaster::CLIENT_OTHER,
            ];

            $master = KitchenIssueMaster::create([
                'store_id' => $request->store_id,
                'payment_type' => $request->payment_type,
                'client_type' => $clientTypeMap[$request->client_type_slug] ?? KitchenIssueMaster::CLIENT_EMPLOYEE,
                'client_type_pk' => $request->client_type_pk,
                'client_id' => $request->client_id,
                'name_id' => $request->name_id,
                'client_name' => $request->client_name,
                'issue_date' => $request->issue_date,
                'kitchen_issue_type' => KitchenIssueMaster::TYPE_SELLING_VOUCHER,
                'status' => KitchenIssueMaster::STATUS_APPROVED,
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
            'store',
            'items.itemSubcategory',
            'clientTypeCategory',
            'employee',
            'student'
        ])->findOrFail($id);

        if ($request->wantsJson()) {
            $voucher = [
                'pk' => $kitchenIssue->pk,
                'request_date' => $kitchenIssue->created_at ? $kitchenIssue->created_at->format('d/m/Y') : '—',
                'issue_date' => $kitchenIssue->issue_date ? $kitchenIssue->issue_date->format('d/m/Y') : '—',
                'store_name' => $kitchenIssue->store->store_name ?? 'N/A',
                'client_type' => $kitchenIssue->client_type_label ?? '-',
                'client_name' => $kitchenIssue->client_name ?? '-',
                'payment_type' => $kitchenIssue->payment_type_label ?? '-',
                'kitchen_issue_type' => $kitchenIssue->kitchen_issue_type_label ?? '-',
                'status' => $kitchenIssue->status,
                'status_label' => $kitchenIssue->status_label ?? '-',
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

        if ($request->wantsJson()) {
            // Map numeric client_type back to slug
            $clientTypeSlugMap = [
                KitchenIssueMaster::CLIENT_EMPLOYEE => 'employee',
                KitchenIssueMaster::CLIENT_OT => 'ot',
                KitchenIssueMaster::CLIENT_COURSE => 'course',
                KitchenIssueMaster::CLIENT_OTHER => 'other',
            ];
            $clientTypeSlug = $clientTypeSlugMap[$kitchenIssue->client_type] ?? 'employee';
            
            $voucher = [
                'pk' => $kitchenIssue->pk,
                'payment_type' => (int) $kitchenIssue->payment_type,
                'client_type' => (int) $kitchenIssue->client_type,
                'client_type_pk' => $kitchenIssue->client_type_pk,
                'client_type_slug' => $clientTypeSlug,
                'client_id' => $kitchenIssue->client_id,
                'name_id' => $kitchenIssue->name_id,
                'client_name' => $kitchenIssue->client_name,
                'issue_date' => $kitchenIssue->issue_date ? $kitchenIssue->issue_date->format('Y-m-d') : '',
                'store_id' => $kitchenIssue->store_id,
                'inve_store_master_pk' => $kitchenIssue->store_id, // For backward compatibility with view
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

        $request->validate([
            'store_id' => 'required|exists:mess_stores,id',
            'payment_type' => 'required|integer|in:0,1,2',
            'client_type_slug' => 'required|string|in:employee,ot,course,other',
            'client_type_pk' => 'nullable|exists:mess_client_types,id',
            'client_id' => 'nullable|integer',
            'name_id' => 'nullable|integer',
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

            // Map client_type_slug to numeric value
            $clientTypeMap = [
                'employee' => KitchenIssueMaster::CLIENT_EMPLOYEE,
                'ot' => KitchenIssueMaster::CLIENT_OT,
                'course' => KitchenIssueMaster::CLIENT_COURSE,
                'other' => KitchenIssueMaster::CLIENT_OTHER,
            ];

            $kitchenIssue->update([
                'store_id' => $request->store_id,
                'payment_type' => $request->payment_type,
                'client_type' => $clientTypeMap[$request->client_type_slug] ?? KitchenIssueMaster::CLIENT_EMPLOYEE,
                'client_type_pk' => $request->client_type_pk,
                'client_id' => $request->client_id,
                'name_id' => $request->name_id,
                'client_name' => $request->client_name,
                'issue_date' => $request->issue_date,
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

        try {
            $kitchenIssue->delete();

            return redirect()->route('admin.mess.material-management.index')
                           ->with('success', 'Selling Voucher deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete Selling Voucher: ' . $e->getMessage());
        }
    }

    /**
     * Return modal data (JSON): store name and items with return fields.
     */
    public function returnData(Request $request, $id)
    {
        $kitchenIssue = KitchenIssueMaster::with(['store', 'items.itemSubcategory'])->findOrFail($id);

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
        $query = KitchenIssueMaster::with(['store', 'employee', 'student']);

        if ($request->filled('messId')) {
            $query->where('store_id', $request->messId);
        }

        if ($request->filled('sDate') && $request->filled('eDate')) {
            $query->whereBetween('issue_date', [$request->sDate, $request->eDate]);
        }

        if ($request->filled('paymode')) {
            $query->where('payment_type', $request->paymode);
        }

        if ($request->filled('action')) {
            if ($request->action == 'approved') {
                $query->where('status', KitchenIssueMaster::STATUS_APPROVED);
            } elseif ($request->action == 'pending') {
                $query->where('status', KitchenIssueMaster::STATUS_PENDING);
            }
        }

        $records = $query->orderBy('issue_date', 'desc')->get();

        return response()->json($records);
    }

    /**
     * Send kitchen issue for approval
     */
    public function sendForApproval($id)
    {
        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

        if ($kitchenIssue->status == KitchenIssueMaster::STATUS_APPROVED) {
            return back()->with('error', 'Material Management already approved');
        }

        try {
            $kitchenIssue->update([
                'status' => KitchenIssueMaster::STATUS_PROCESSING,
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
        $query = KitchenIssueMaster::with(['store', 'employee', 'student']);

        if ($request->filled('messId')) {
            $query->where('store_id', $request->messId);
        }

        if ($request->filled('empId')) {
            $query->where('client_id', $request->empId);
        }

        if ($request->filled('sDate') && $request->filled('eDate')) {
            $query->whereBetween('issue_date', [$request->sDate, $request->eDate]);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('client_type')) {
            $query->where('client_type', $request->client_type);
        }

        $kitchenIssues = $query->orderBy('issue_date', 'desc')->get();

        $stores = Store::all();

        return view('mess.kitchen-issues.bill-report', compact('kitchenIssues', 'stores'));
    }
}
