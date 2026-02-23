<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\KitchenIssueMaster;
use App\Services\Mess\AvailableQuantityService;
use App\Models\KitchenIssueItem;
use App\Models\KitchenIssuePaymentDetail;
use App\Models\Mess\Store;
use App\Models\Mess\SubStore;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class KitchenIssueController extends Controller
{
    /**
     * Display a listing of selling vouchers (kitchen issues)
     */
    public function index(Request $request)
    {
        $query = KitchenIssueMaster::with(['store', 'items.itemSubcategory', 'clientTypeCategory', 'course', 'employee', 'student']);

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

        // DataTables handles pagination/search on the client; return full filtered set.
        $kitchenIssues = $query->orderBy('issue_date', 'desc')
            ->orderBy('pk', 'desc')
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
            'store_id' => ['required', function ($attribute, $value, $fail) {
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
            'payment_type' => 'required|integer|in:0,1,2',
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
            'bill_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ], [
            'bill_file.mimes' => 'Bill must be PDF or image (jpg, jpeg, png, webp).',
            'bill_file.max' => 'Bill size must not exceed 5 MB.',
        ]);

        try {
            DB::beginTransaction();

            $storeId = $request->store_id;
            $storeType = 'store';
            if (str_starts_with($storeId, 'sub_')) {
                $storeId = str_replace('sub_', '', $storeId);
                $storeType = 'sub_store';
            }
            $storeId = (int) $storeId;

            // Server-side enforcement: issue qty cannot exceed available qty (per store + item)
            $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId);
            $requestedByItem = [];
            foreach ((array) $request->items as $row) {
                $itemId = (int) ($row['item_subcategory_id'] ?? 0);
                $qty = (float) ($row['quantity'] ?? 0);
                if ($itemId > 0) {
                    $requestedByItem[$itemId] = ($requestedByItem[$itemId] ?? 0) + $qty;
                }
            }

            // Map client_type_slug to numeric value
            $clientTypeMap = [
                'employee' => KitchenIssueMaster::CLIENT_EMPLOYEE,
                'ot' => KitchenIssueMaster::CLIENT_OT,
                'course' => KitchenIssueMaster::CLIENT_COURSE,
                'other' => KitchenIssueMaster::CLIENT_OTHER,
            ];

            $master = KitchenIssueMaster::create([
                'store_id' => $storeId,
                'store_type' => $storeType,
                'payment_type' => $request->payment_type,
                'client_type' => $clientTypeMap[$request->client_type_slug] ?? KitchenIssueMaster::CLIENT_EMPLOYEE,
                'client_type_pk' => $request->filled('client_type_pk') ? (int) $request->client_type_pk : null,
                'client_id' => $request->client_id,
                'name_id' => $request->name_id,
                'client_name' => $request->client_name,
                'issue_date' => $request->issue_date,
                'kitchen_issue_type' => KitchenIssueMaster::TYPE_SELLING_VOUCHER,
                'status' => KitchenIssueMaster::STATUS_PENDING, // Unpaid by default; Process Mess Bills "Generate Payment" sets to APPROVED (Paid)
                'remarks' => $request->remarks,
            ]);

            if ($request->hasFile('bill_file')) {
                $path = $request->file('bill_file')->store('mess/selling-voucher/bills', 'public');
                $master->update(['bill_path' => $path]);
            }

            $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->pluck('item_subcategory_id'))->get()->keyBy('id');

            // Validate requested qty vs available (aggregated across duplicate rows)
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
                throw ValidationException::withMessages([
                    'items' => implode(' ', $qtyErrors),
                ]);
            }

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
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
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
            'course',
            'employee',
            'student'
        ])->findOrFail($id);

        if ($request->wantsJson()) {
            $voucher = [
                'pk' => $kitchenIssue->pk,
                'request_date' => $kitchenIssue->created_at ? $kitchenIssue->created_at->format('d/m/Y') : '—',
                'issue_date' => $kitchenIssue->issue_date ? $kitchenIssue->issue_date->format('d/m/Y') : '—',
                'store_name' => $kitchenIssue->resolved_store_name,
                'client_type' => $kitchenIssue->client_type_label ?? '-',
                'client_name' => $kitchenIssue->display_client_name,
                'payment_type' => $kitchenIssue->payment_type_label ?? '-',
                'kitchen_issue_type' => $kitchenIssue->kitchen_issue_type_label ?? '-',
                'status' => $kitchenIssue->status,
                'status_label' => $kitchenIssue->status_label ?? '-',
                'remarks' => $kitchenIssue->remarks ?? '',
                'created_at' => $kitchenIssue->created_at ? $kitchenIssue->created_at->format('d/m/Y H:i') : '-',
                'updated_at' => $kitchenIssue->updated_at ? $kitchenIssue->updated_at->format('d/m/Y H:i') : null,
                'bill_path' => $kitchenIssue->bill_path,
                'bill_url' => $kitchenIssue->bill_path ? asset('storage/' . $kitchenIssue->bill_path) : null,
            ];
            $items = $kitchenIssue->items->map(function ($item) {
                $qty = (float) $item->quantity;
                $retQty = (float) ($item->return_quantity ?? 0);
                $rate = (float) $item->rate;
                $amount = max(0, $qty - $retQty) * $rate;
                return [
                    'item_name' => $item->item_name ?: ($item->itemSubcategory->item_name ?? '—'),
                    'unit' => $item->unit ?? '—',
                    'quantity' => $qty,
                    'return_quantity' => $retQty,
                    'rate' => number_format($item->rate, 2),
                    'amount' => number_format($amount, 2),
                ];
            })->values()->toArray();
            $grand_total = $kitchenIssue->items->sum(function ($item) {
                $qty = (float) $item->quantity;
                $retQty = (float) ($item->return_quantity ?? 0);
                return max(0, $qty - $retQty) * (float) $item->rate;
            });
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

            $storeType = $kitchenIssue->store_type ?? 'store';
            $storeId = (int) $kitchenIssue->store_id;
            $storeIdentifier = $storeType === 'sub_store' ? 'sub_' . $storeId : (string) $storeId;
            $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId);
            
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
                'store_id' => $storeIdentifier,
                'inve_store_master_pk' => $storeIdentifier, // For backward compatibility with view
                'remarks' => $kitchenIssue->remarks,
                'bill_path' => $kitchenIssue->bill_path,
                'bill_url' => $kitchenIssue->bill_path ? asset('storage/' . $kitchenIssue->bill_path) : null,
            ];
            $items = $kitchenIssue->items->map(function ($item) use ($availableMap) {
                $itemId = (int) ($item->item_subcategory_id ?? 0);
                $currentAvailable = $itemId > 0 ? (float) ($availableMap[$itemId] ?? 0) : (float) ($item->available_quantity ?? 0);
                return [
                    'item_subcategory_id' => $item->item_subcategory_id,
                    'item_name' => $item->item_name ?? ($item->itemSubcategory->item_name ?? '—'),
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
        return view('mess.kitchen-issues.edit', compact('kitchenIssue', 'stores', 'items'));
    }

    /**
     * Update the specified kitchen issue (supports Selling Voucher multi-item)
     */
    public function update(Request $request, $id)
    {
        $kitchenIssue = KitchenIssueMaster::findOrFail($id);

        $request->validate([
            'store_id' => ['required', function ($attribute, $value, $fail) {
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
            'payment_type' => 'required|integer|in:0,1,2',
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
            'bill_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ], [
            'bill_file.mimes' => 'Bill must be PDF or image (jpg, jpeg, png, webp).',
            'bill_file.max' => 'Bill size must not exceed 5 MB.',
        ]);

        try {
            DB::beginTransaction();

            $storeId = $request->store_id;
            $storeType = 'store';
            if (str_starts_with($storeId, 'sub_')) {
                $storeId = str_replace('sub_', '', $storeId);
                $storeType = 'sub_store';
            }
            $storeId = (int) $storeId;

            // Server-side enforcement: issue qty cannot exceed available qty (per store + item)
            $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId);
            $requestedByItem = [];
            foreach ((array) $request->items as $row) {
                $itemId = (int) ($row['item_subcategory_id'] ?? 0);
                $qty = (float) ($row['quantity'] ?? 0);
                if ($itemId > 0) {
                    $requestedByItem[$itemId] = ($requestedByItem[$itemId] ?? 0) + $qty;
                }
            }

            // Map client_type_slug to numeric value
            $clientTypeMap = [
                'employee' => KitchenIssueMaster::CLIENT_EMPLOYEE,
                'ot' => KitchenIssueMaster::CLIENT_OT,
                'course' => KitchenIssueMaster::CLIENT_COURSE,
                'other' => KitchenIssueMaster::CLIENT_OTHER,
            ];

            $kitchenIssue->update([
                'store_id' => $storeId,
                'store_type' => $storeType,
                'payment_type' => $request->payment_type,
                'client_type' => $clientTypeMap[$request->client_type_slug] ?? KitchenIssueMaster::CLIENT_EMPLOYEE,
                'client_type_pk' => $request->filled('client_type_pk') ? (int) $request->client_type_pk : null,
                'client_id' => $request->client_id,
                'name_id' => $request->name_id,
                'client_name' => $request->client_name,
                'issue_date' => $request->issue_date,
                'remarks' => $request->remarks,
            ]);

            if ($request->hasFile('bill_file')) {
                if ($kitchenIssue->bill_path && Storage::disk('public')->exists($kitchenIssue->bill_path)) {
                    Storage::disk('public')->delete($kitchenIssue->bill_path);
                }
                $path = $request->file('bill_file')->store('mess/selling-voucher/bills', 'public');
                $kitchenIssue->update(['bill_path' => $path]);
            }

            $kitchenIssue->items()->delete();
            $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->pluck('item_subcategory_id'))->get()->keyBy('id');

            // Validate requested qty vs available (aggregated across duplicate rows)
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
                throw ValidationException::withMessages([
                    'items' => implode(' ', $qtyErrors),
                ]);
            }

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
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
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
            'store_name' => $kitchenIssue->resolved_store_name,
            'issue_date' => $kitchenIssue->issue_date ? $kitchenIssue->issue_date->format('Y-m-d') : '',
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
                $issuedQty = (float) ($item->quantity ?? 0);
                if ($returnQty > $issuedQty) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Return quantity cannot be greater than issued quantity.');
                }
                if (!empty($returnDate) && $kitchenIssue->issue_date) {
                    try {
                        $ret = Carbon::parse($returnDate)->startOfDay();
                        $iss = Carbon::parse($kitchenIssue->issue_date)->startOfDay();
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

            // FIFO: get allocation items ordered by date (oldest first) for price tiers
            $fifoRows = DB::table('mess_store_allocation_items as sai')
                ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
                ->where('sa.sub_store_id', $storeId)
                ->orderByRaw('COALESCE(sa.allocation_date, sa.created_at) ASC')
                ->orderBy('sa.id')
                ->orderBy('sai.id')
                ->select('sai.item_subcategory_id', 'sai.quantity', 'sai.unit_price')
                ->get();

            $tiersByItem = [];
            foreach ($fifoRows as $r) {
                $id = (int) ($r->item_subcategory_id ?? 0);
                if ($id <= 0) continue;
                if (!isset($tiersByItem[$id])) $tiersByItem[$id] = [];
                $tiersByItem[$id][] = ['quantity' => (float) $r->quantity, 'unit_price' => (float) $r->unit_price];
            }

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
                    ->map(function ($s) use ($allocatedItems, $availableMap, $tiersByItem) {
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
                            'standard_cost' => $firstPrice ?? ($storeRate !== null ? $storeRate : ($s->standard_cost ?? 0)),
                            'available_quantity' => $available,
                            'price_tiers' => $tiers,
                        ];
                    });
            }
        } else {
            // Main store: FIFO from purchase orders (oldest first by po_date = purchase date)
            $fifoRows = DB::table('mess_purchase_order_items as poi')
                ->join('mess_purchase_orders as po', 'poi.purchase_order_id', '=', 'po.id')
                ->where('po.store_id', $storeId)
                ->where('po.status', 'approved')
                ->whereNotNull('poi.item_subcategory_id')
                ->where('poi.item_subcategory_id', '>', 0)
                ->orderBy('po.po_date', 'asc')
                ->orderBy('po.id')
                ->orderBy('poi.id')
                ->select('poi.item_subcategory_id', 'poi.quantity', 'poi.unit_price')
                ->get();

            $tiersByItem = [];
            foreach ($fifoRows as $r) {
                $id = (int) ($r->item_subcategory_id ?? 0);
                if ($id <= 0) continue;
                if (!isset($tiersByItem[$id])) $tiersByItem[$id] = [];
                $tiersByItem[$id][] = ['quantity' => (float) $r->quantity, 'unit_price' => (float) $r->unit_price];
            }

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
                    ->map(function ($s) use ($purchasedItems, $availableMap, $tiersByItem) {
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
                            'standard_cost' => $firstPrice ?? ($storeRate !== null ? $storeRate : ($s->standard_cost ?? 0)),
                            'available_quantity' => $available,
                            'price_tiers' => $tiers,
                        ];
                    });
            }
        }

        return response()->json($items->values());
    }
}
