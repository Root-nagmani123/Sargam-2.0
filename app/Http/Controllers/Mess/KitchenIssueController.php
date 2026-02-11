<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\KitchenIssueMaster;
use App\Models\KitchenIssueItem;
use App\Models\KitchenIssuePaymentDetail;
use App\Models\Mess\Store;
use App\Models\Mess\SubStore;
use App\Models\Mess\Inventory;
<<<<<<< HEAD
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
=======
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
use Carbon\Carbon;

class KitchenIssueController extends Controller
{
    /**
<<<<<<< HEAD
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

        // Load all filtered records for client-side DataTable (sorting, search, pagination)
        $kitchenIssues = $query->orderBy('issue_date', 'desc')
            ->orderBy('pk', 'desc')
            ->limit(5000)
            ->get();

        $otCourses = CourseMaster::where('active_inactive', 1)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);

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
                'item_code' => $s->item_code ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
                'standard_cost' => $s->standard_cost ?? 0,
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

    /**
     * Show the form for creating a new selling voucher
     */
    public function create()
    {
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
                'item_code' => $s->item_code ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
                'standard_cost' => $s->standard_cost ?? 0,
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
=======
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
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
        ]);

        try {
            DB::beginTransaction();

<<<<<<< HEAD
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
=======
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

            return redirect()->route('admin.mess.kitchen-issues.index')
                           ->with('success', 'Kitchen Issue created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                        ->with('error', 'Failed to create Kitchen Issue: ' . $e->getMessage());
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
        }
    }

    /**
<<<<<<< HEAD
     * Display the specified kitchen issue (JSON for view modal, view for direct URL)
     */
    public function show(Request $request, $id)
    {
        $kitchenIssue = KitchenIssueMaster::with([
            'store',
            'items.itemSubcategory',
            'clientTypeCategory',
=======
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
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
            'employee',
            'student'
        ])->findOrFail($id);

<<<<<<< HEAD
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

=======
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
        return view('mess.kitchen-issues.show', compact('kitchenIssue'));
    }

    /**
<<<<<<< HEAD
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
        
        $items = Inventory::all();
=======
     * Show the form for editing the specified kitchen issue
     */
    public function edit($id)
    {
        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

        // Only allow editing if not approved
        if ($kitchenIssue->approve_status == KitchenIssueMaster::APPROVE_APPROVED) {
            return redirect()->route('admin.mess.kitchen-issues.index')
                           ->with('error', 'Cannot edit approved kitchen issue');
        }

        $stores = Store::all();
        $items = Inventory::all();

>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
        return view('mess.kitchen-issues.edit', compact('kitchenIssue', 'stores', 'items'));
    }

    /**
<<<<<<< HEAD
     * Update the specified kitchen issue (supports Selling Voucher multi-item)
=======
     * Update the specified kitchen issue
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
     */
    public function update(Request $request, $id)
    {
        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

<<<<<<< HEAD
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
=======
        // Only allow editing if not approved
        if ($kitchenIssue->approve_status == KitchenIssueMaster::APPROVE_APPROVED) {
            return redirect()->route('admin.mess.kitchen-issues.index')
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
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
        ]);

        try {
            DB::beginTransaction();

<<<<<<< HEAD
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
=======
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

            return redirect()->route('admin.mess.kitchen-issues.index')
                           ->with('success', 'Kitchen Issue updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                        ->with('error', 'Failed to update Kitchen Issue: ' . $e->getMessage());
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
        }
    }

    /**
     * Remove the specified kitchen issue
     */
    public function destroy($id)
    {
        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

<<<<<<< HEAD
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
=======
        // Only allow deletion if not approved or completed
        if ($kitchenIssue->approve_status == KitchenIssueMaster::APPROVE_APPROVED ||
            $kitchenIssue->status == KitchenIssueMaster::STATUS_COMPLETED) {
            return redirect()->route('admin.mess.kitchen-issues.index')
                           ->with('error', 'Cannot delete approved or completed kitchen issue');
        }

        try {
            $kitchenIssue->delete();

            return redirect()->route('admin.mess.kitchen-issues.index')
                           ->with('success', 'Kitchen Issue deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete Kitchen Issue: ' . $e->getMessage());
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
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
<<<<<<< HEAD
        $query = KitchenIssueMaster::with(['store', 'employee', 'student']);

        if ($request->filled('messId')) {
            $query->where('store_id', $request->messId);
        }

        if ($request->filled('sDate') && $request->filled('eDate')) {
            $query->whereBetween('issue_date', [$request->sDate, $request->eDate]);
=======
        $query = KitchenIssueMaster::with(['storeMaster', 'itemMaster', 'employee', 'student', 'paymentDetails']);

        if ($request->filled('messId')) {
            $query->where('inve_store_master_pk', $request->messId);
        }

        if ($request->filled('sDate') && $request->filled('eDate')) {
            $query->whereBetween('request_date', [$request->sDate, $request->eDate]);
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
        }

        if ($request->filled('paymode')) {
            $query->where('payment_type', $request->paymode);
        }

        if ($request->filled('action')) {
            if ($request->action == 'approved') {
<<<<<<< HEAD
                $query->where('status', KitchenIssueMaster::STATUS_APPROVED);
            } elseif ($request->action == 'pending') {
                $query->where('status', KitchenIssueMaster::STATUS_PENDING);
            }
        }

        $records = $query->orderBy('issue_date', 'desc')->get();
=======
                $query->where('approve_status', KitchenIssueMaster::APPROVE_APPROVED);
            } elseif ($request->action == 'pending') {
                $query->where('approve_status', KitchenIssueMaster::APPROVE_PENDING);
            }
        }

        $records = $query->orderBy('request_date', 'desc')->get();
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)

        return response()->json($records);
    }

    /**
     * Send kitchen issue for approval
     */
    public function sendForApproval($id)
    {
        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

<<<<<<< HEAD
        if ($kitchenIssue->status == KitchenIssueMaster::STATUS_APPROVED) {
            return back()->with('error', 'Material Management already approved');
=======
        if ($kitchenIssue->send_for_approval == 1) {
            return back()->with('error', 'Kitchen Issue already sent for approval');
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
        }

        try {
            $kitchenIssue->update([
<<<<<<< HEAD
                'status' => KitchenIssueMaster::STATUS_PROCESSING,
                'modified_by' => Auth::id(),
            ]);

            return back()->with('success', 'Material Management sent for approval successfully');
=======
                'send_for_approval' => 1,
                'notify_status' => 0,
                'modified_by' => Auth::id(),
            ]);

            return back()->with('success', 'Kitchen Issue sent for approval successfully');
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send for approval: ' . $e->getMessage());
        }
    }

    /**
     * Generate bill report
     */
    public function billReport(Request $request)
    {
<<<<<<< HEAD
        $query = KitchenIssueMaster::with(['store', 'employee', 'student']);

        if ($request->filled('messId')) {
            $query->where('store_id', $request->messId);
        }

        if ($request->filled('empId')) {
            $query->where('client_id', $request->empId);
        }

        if ($request->filled('sDate') && $request->filled('eDate')) {
            $query->whereBetween('issue_date', [$request->sDate, $request->eDate]);
=======
        $query = KitchenIssueMaster::with(['storeMaster', 'itemMaster', 'employee', 'student', 'paymentDetails']);

        if ($request->filled('messId')) {
            $query->where('inve_store_master_pk', $request->messId);
        }

        if ($request->filled('empId')) {
            $query->where('employee_student_pk', $request->empId);
        }

        if ($request->filled('sDate') && $request->filled('eDate')) {
            $query->whereBetween('request_date', [$request->sDate, $request->eDate]);
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

<<<<<<< HEAD
        if ($request->filled('client_type')) {
            $query->where('client_type', $request->client_type);
        }

        $kitchenIssues = $query->orderBy('issue_date', 'desc')->get();
=======
        if ($request->filled('invoice')) {
            $query->whereHas('paymentDetails', function ($q) use ($request) {
                $q->where('invoice_no', $request->invoice);
            });
        }

        $kitchenIssues = $query->orderBy('request_date', 'desc')->get();
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)

        $stores = Store::all();

        return view('mess.kitchen-issues.bill-report', compact('kitchenIssues', 'stores'));
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
