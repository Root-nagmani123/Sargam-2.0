<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
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
use App\Models\KitchenIssueMaster;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;

/**
 * Selling Voucher with Date Range - standalone module (design/pattern like Selling Voucher, data/logic separate).
 */
class SellingVoucherDateRangeController extends Controller
{
    private const SV_DATE_RANGE_DT_LIST_EPOCH = 'selling_voucher_date_range_dt_list_epoch';

    /**
     * Invalidate Redis-backed Selling Voucher (Date Range) DataTables JSON after listing mutations.
     */
    public static function bumpSellingVoucherDateRangeListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::SV_DATE_RANGE_DT_LIST_EPOCH, 'SellingVoucherDateRangeController@datatable');
        AvailableQuantityService::bumpCacheEpoch();
    }

    /**
     * Historical mess_client_types.id values still stored on sv_date_range_reports rows
     * after employee categories were re-seeded (Academy Staff=3, Mess Staff=4, Faculty=5).
     *
     * @var array<string, list<int>>
     */
    private const LEGACY_EMPLOYEE_CLIENT_TYPE_PK = [
        'academy staff' => [2, 12],
        'faculty' => [1],
        'mess staff' => [],
    ];

    public function index(Request $request)
    {
        // Get active stores and sub-stores
        $stores = Store::active()->get(['id', 'store_name'])->map(function ($store) {
            return [
                'id' => $store->id,
                'store_name' => $store->store_name,
                'type' => 'store'
            ];
        });
        
        $subStores = SubStore::active()->get(['id', 'sub_store_name'])->map(function ($subStore) {
            return [
                'id' => 'sub_' . $subStore->id,
                'store_name' => $subStore->sub_store_name . ' (Sub-Store)',
                'type' => 'sub_store',
                'original_id' => $subStore->id
            ];
        });
        
        // Combine stores and sub-stores
        $stores = $stores->concat($subStores)->sortBy('store_name')->values();
        
        $itemSubcategories = ItemSubcategory::active()->orderedByDisplayName()->get(ItemSubcategory::listSelectColumns())->map(function ($s) {
            return [
                'id' => $s->id,
                'item_name' => $s->item_name ?? $s->name ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
                'standard_cost' => $s->standard_cost ?? 0,
            ];
        });
        $clientTypes = ClientType::clientTypes();
        $clientNamesByType = ClientType::active()->orderBy('client_type')->orderBy('client_name')->get(['id', 'client_type', 'client_name'])->groupBy('client_type');

        // Academy Staff should exclude:
        // 1) Mess Staff (Officers Mess department)
        // 2) Employees mapped as Faculty (FacultyMaster.employee_master_pk)
        $officersMessDept = DepartmentMaster::where('department_name', 'Officers Mess')->first(['pk']);

        $faculties = FacultyMaster::whereNotNull('full_name')
            ->where('full_name', '!=', '')
            ->where('faculty_type', 1)
            ->whereNotNull('employee_master_pk')
            ->whereIn('employee_master_pk', EmployeeMaster::active()->select('pk'))
            ->orderBy('full_name')
            ->get(['pk', 'full_name', 'faculty_code', 'employee_master_pk'])
            ->map(function ($f) {
                $fullName = trim((string) ($f->full_name ?? ''));
                $facultyCode = trim((string) ($f->faculty_code ?? ''));
                $f->full_name_with_code = $facultyCode !== '' ? ($fullName . ' (' . $facultyCode . ')') : $fullName;
                return $f;
            });

        $facultyEmployeePks = $faculties
            ->pluck('employee_master_pk')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $departmentNamesByPk = DepartmentMaster::query()->select(['pk', 'department_name'])->pluck('department_name', 'pk');

        $buildEmployeeLabel = function ($fullName, $departmentPk) use ($departmentNamesByPk) {
            $fullName = trim((string) $fullName);
            if ($fullName === '') {
                $fullName = '—';
            }
            $departmentName = trim((string) ($departmentNamesByPk[$departmentPk] ?? ''));
            return $departmentName !== '' ? ($fullName . ' (' . $departmentName . ')') : $fullName;
        };

        $employees = EmployeeMaster::active()
            ->when($officersMessDept, function ($q) use ($officersMessDept) {
                $q->where(function ($sub) use ($officersMessDept) {
                    $sub->whereNull('department_master_pk')
                        ->orWhere('department_master_pk', '!=', $officersMessDept->pk);
                });
            })
            ->when(!empty($facultyEmployeePks), function ($q) use ($facultyEmployeePks) {
                $q->whereNotIn('pk', $facultyEmployeePks);
            })
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['pk', 'first_name', 'middle_name', 'last_name', 'department_master_pk'])
            ->map(function ($e) use ($buildEmployeeLabel) {
                $fullName = trim(($e->first_name ?? '') . ' ' . ($e->middle_name ?? '') . ' ' . ($e->last_name ?? ''));
                $fullName = $fullName ?: '—';
                return (object) [
                    'pk' => $e->pk,
                    'full_name' => $fullName,
                    'full_name_with_department' => $buildEmployeeLabel($fullName, $e->department_master_pk ?? null),
                ];
            })
            ->filter(fn($e) => $e->full_name !== '—')
            ->values();

        // All courses (active + archived) for Client Name dropdown; label in UI via active_inactive.
        // Course end date before today is treated as archived (same idea as course list filters).
        $otCourses = CourseMaster::orderByDesc('active_inactive')
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'active_inactive', 'end_date']);
        $today = Carbon::today();
        $otCourses->each(function ($course) use ($today) {
            if (filled($course->end_date) && Carbon::parse($course->end_date)->lt($today)) {
                $course->active_inactive = 0;
            }
        });
        $messStaff = $officersMessDept
            ? EmployeeMaster::active()
                ->where('department_master_pk', $officersMessDept->pk)
                ->orderBy('first_name')->orderBy('last_name')
                ->get(['pk', 'first_name', 'middle_name', 'last_name', 'department_master_pk'])
                ->map(function ($e) use ($buildEmployeeLabel) {
                    $fullName = trim(($e->first_name ?? '') . ' ' . ($e->middle_name ?? '') . ' ' . ($e->last_name ?? ''));
                    $fullName = $fullName ?: '—';
                    return (object) [
                        'pk' => $e->pk,
                        'full_name' => $fullName,
                        'full_name_with_department' => $buildEmployeeLabel($fullName, $e->department_master_pk ?? null),
                    ];
                })
                ->filter(fn($e) => $e->full_name !== '—')
                ->values()
            : collect();

        $selectedClientType = (string) $request->input('client_type', '');
        $selectedClientTypePk = (string) $request->input('client_type_pk', '');
        $selectedBuyerName = trim((string) $request->input('buyer_name', ''));
        $resolvedBuyerClientId = $this->resolveClientIdForBuyerFilter($selectedBuyerName, $selectedClientType);
        if ($resolvedBuyerClientId !== null && $resolvedBuyerClientId > 0) {
            $selectedBuyerName = (string) $resolvedBuyerClientId;
        }

        $filterClientTypePkOptions = collect();
        if (in_array($selectedClientType, ['ot', 'course'], true)) {
            $filterClientTypePkOptions = $otCourses->map(function ($course) {
                return [
                    'value' => (string) $course->pk,
                    'text' => (string) $course->course_name,
                ];
            })->values();
        } elseif ($selectedClientType !== '' && isset($clientNamesByType[$selectedClientType])) {
            $filterClientTypePkOptions = $clientNamesByType[$selectedClientType]
                ->map(function ($category) {
                    return [
                        'value' => (string) $category->id,
                        'text' => (string) $category->client_name,
                    ];
                })
                ->values();
        }

        $filterEmployeeBuyerOptions = $this->employeeBuyerFilterOptions($employees);
        $filterFacultyBuyerOptions = $this->facultyBuyerFilterOptions($faculties);
        $filterMessStaffBuyerOptions = $this->employeeBuyerFilterOptions($messStaff);

        $filterBuyerNames = collect();
        if ($selectedClientType === 'employee' && $selectedClientTypePk !== '') {
            $selectedEmployeeBucket = strtolower(trim((string) $filterClientTypePkOptions
                ->firstWhere('value', $selectedClientTypePk)['text'] ?? ''));
            if ($selectedEmployeeBucket === 'academy staff') {
                $filterBuyerNames = collect($filterEmployeeBuyerOptions);
            } elseif ($selectedEmployeeBucket === 'faculty') {
                $filterBuyerNames = collect($filterFacultyBuyerOptions);
            } elseif ($selectedEmployeeBucket === 'mess staff') {
                $filterBuyerNames = collect($filterMessStaffBuyerOptions);
            }
        } elseif ($selectedClientType === 'ot' && $selectedClientTypePk !== '') {
            $filterBuyerNames = StudentMaster::join('student_master_course__map', 'student_master.pk', '=', 'student_master_course__map.student_master_pk')
                ->where('student_master_course__map.course_master_pk', (int) $selectedClientTypePk)
                ->select('student_master.display_name', 'student_master.generated_OT_code')
                ->orderBy('student_master.display_name')
                ->get()
                ->map(function ($student) {
                    $displayName = trim((string) ($student->display_name ?? ''));
                    $otCode = trim((string) ($student->generated_OT_code ?? ''));
                    if ($displayName === '') {
                        return null;
                    }
                    return $otCode !== '' ? ($displayName . ' (' . $otCode . ')') : $displayName;
                })
                ->filter()
                ->values();
        } elseif (in_array($selectedClientType, ['course', 'section', 'other'], true) && $selectedClientTypePk !== '') {
            $filterBuyerNames = SellingVoucherDateRangeReport::query()
                ->where('client_type_slug', $selectedClientType)
                ->where('client_type_pk', (int) $selectedClientTypePk)
                ->whereHas('items')
                ->whereNotNull('client_name')
                ->where('client_name', '!=', '')
                ->distinct()
                ->orderBy('client_name')
                ->pluck('client_name')
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->values();
        }

        return view('mess.selling-voucher-date-range.index', compact(
            'stores',
            'itemSubcategories',
            'clientTypes',
            'clientNamesByType',
            'faculties',
            'employees',
            'messStaff',
            'otCourses',
            'selectedClientType',
            'selectedClientTypePk',
            'selectedBuyerName',
            'filterClientTypePkOptions',
            'filterBuyerNames',
            'filterEmployeeBuyerOptions',
            'filterFacultyBuyerOptions',
            'filterMessStaffBuyerOptions'
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $employees
     * @return list<array{value: string, text: string}>
     */
    private function employeeBuyerFilterOptions($employees): array
    {
        return $employees->map(function ($employee) {
            $value = (string) ($employee->pk ?? '');
            $text = (string) ($employee->full_name_with_department ?? $employee->full_name ?? '');
            if ($value === '' || $text === '') {
                return null;
            }

            return ['value' => $value, 'text' => $text];
        })->filter()->values()->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $faculties
     * @return list<array{value: string, text: string}>
     */
    private function facultyBuyerFilterOptions($faculties): array
    {
        return $faculties->map(function ($faculty) {
            $value = (string) ($faculty->pk ?? '');
            $text = (string) ($faculty->full_name ?? '');
            if ($value === '' || $text === '') {
                return null;
            }

            return ['value' => $value, 'text' => $text];
        })->filter()->values()->all();
    }

    /**
     * Server-side DataTables JSON for Selling Voucher with Date Range listing (one row per item line).
     * Cached via Redis ({@see DataTableRedisCache}); tune with SELLING_VOUCHER_DATE_RANGE_DATATABLE_CACHE_* in .env.
     */
    public function datatable(Request $request): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $request,
            'sv_date_range_dt:v2:',
            self::SV_DATE_RANGE_DT_LIST_EPOCH,
            [
                'enabled' => 'SELLING_VOUCHER_DATE_RANGE_DATATABLE_CACHE_ENABLED',
                'seconds' => 'SELLING_VOUCHER_DATE_RANGE_DATATABLE_CACHE_SECONDS',
            ],
            'SellingVoucherDateRangeController@datatable',
            fn () => $this->buildSellingVoucherDateRangeDatatableResponse($request),
            $this->sellingVoucherDateRangeDatatableFilterFingerprint($request)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sellingVoucherDateRangeDatatableFilterFingerprint(Request $request): array
    {
        $store = collect((array) $request->input('store', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $status = collect((array) $request->input('status', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        return [
            'store' => $store,
            'status' => $status,
            'client_type' => $request->input('client_type'),
            'client_type_pk' => $request->input('client_type_pk'),
            'buyer_name' => trim((string) $request->input('buyer_name', '')),
            'return_status' => strtolower(trim((string) $request->input('return_status', ''))),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'can_delete' => function_exists('hasRole') && (hasRole('Admin') || hasRole('Mess-Admin')),
        ];
    }

    private function buildSellingVoucherDateRangeDatatableResponse(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        if ($length < 1 || $length > 100) {
            $length = 10;
        }

        $searchRaw = '';
        $searchPayload = $request->input('search');
        if (is_array($searchPayload) && isset($searchPayload['value'])) {
            $searchRaw = (string) $searchPayload['value'];
        }

        $base = $this->sellingVoucherDateRangeItemRowsBaseQuery($request);
        $recordsTotal = (clone $base)->count('sri.id');

        $filtered = clone $base;
        $this->applySellingVoucherDateRangeItemSearch($filtered, $searchRaw);

        $searchTrimmed = DataTableSearchHelper::normalizeRaw($searchRaw);
        $recordsFiltered = $searchTrimmed === ''
            ? $recordsTotal
            : (clone $filtered)->count('sri.id');

        $ordered = clone $filtered;
        $this->applySellingVoucherDateRangeDatatableOrder($ordered, $request);

        $rows = $ordered
            ->offset($start)
            ->limit($length)
            ->get();

        $canDeleteSellingVoucherDateRange = hasRole('Admin') || hasRole('Mess-Admin');
        $data = [];
        foreach ($rows as $idx => $row) {
            $data[] = $this->buildSellingVoucherDateRangeDatatableRow(
                $row,
                $start + $idx + 1,
                $canDeleteSellingVoucherDateRange
            );
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
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

    /**
     * AJAX: previously used buyer names for Selling Voucher (Date Range).
     *
     * Query params:
     * - client_type_slug: course|section|other
     * - client_type_pk: for course => course_master.pk, for section/other => mess_client_types.id
     */
    public function getBuyerNames(Request $request)
    {
        $slug = strtolower(trim((string) $request->query('client_type_slug', '')));
        if (!in_array($slug, ['course', 'section', 'other'], true)) {
            return response()->json(['buyers' => []]);
        }

        $clientTypePk = (int) $request->query('client_type_pk', 0);
        if ($clientTypePk <= 0) {
            return response()->json(['buyers' => []]);
        }

        $svBuyers = SellingVoucherDateRangeReport::query()
            ->where('client_type_slug', $slug)
            ->where('client_type_pk', $clientTypePk)
            ->whereHas('items')
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->distinct()
            ->pluck('client_name')
            ->map(fn ($n) => trim((string) $n));

        $slugToKiType = [
            'course' => KitchenIssueMaster::CLIENT_COURSE,
            'section' => KitchenIssueMaster::CLIENT_SECTION,
            'other' => KitchenIssueMaster::CLIENT_OTHER,
        ];
        $kiBuyers = KitchenIssueMaster::query()
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
            ->where('client_type', $slugToKiType[$slug])
            ->where('client_type_pk', $clientTypePk)
            ->whereHas('items')
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->distinct()
            ->pluck('client_name')
            ->map(fn ($n) => trim((string) $n));

        $buyers = $svBuyers->concat($kiBuyers)
            ->filter(fn ($n) => $n !== '')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['buyers' => $buyers]);
    }

    /**
     * Distinct buyer (client) names for the list filter — respects date/store/status/return filters;
     * does not require client category when only client type is selected.
     */
    public function filterBuyerNames(Request $request)
    {
        $filterRequest = Request::create(
            $request->url(),
            'GET',
            collect($request->query())->except(['buyer_name', 'draw', 'start', 'length', 'search'])->all()
        );

        $buyers = (clone $this->sellingVoucherDateRangeItemRowsBaseQuery($filterRequest))
            ->whereNotNull('sv.client_name')
            ->where('sv.client_name', '!=', '')
            ->select('sv.client_name')
            ->distinct()
            ->orderBy('sv.client_name')
            ->limit(500)
            ->pluck('client_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->values();

        return response()->json(['buyers' => $buyers]);
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
            'client_type_slug' => 'required|string|in:employee,ot,course,section,other',
            'client_type_pk' => ['required', 'integer', 'min:1', function ($attribute, $value, $fail) use ($request) {
                $slug = $request->client_type_slug ?? '';
                if (in_array($slug, ['employee', 'section', 'other']) && !\App\Models\Mess\ClientType::where('id', $value)->exists()) {
                    $fail('The selected client is invalid.');
                }
                if (in_array($slug, ['ot', 'course']) && !CourseMaster::where('pk', $value)->exists()) {
                    $fail('The selected course is invalid.');
                }
            }],
            'client_id' => ['required_if:client_type_slug,employee,ot', 'nullable', 'integer'],
            'client_name' => in_array($request->client_type_slug, ['ot', 'course']) ? 'required|string|max:255' : 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'reference_number' => 'nullable|string|max:100',
            'order_by' => 'nullable|string|max:100',
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

        // Enforce: Issue Qty cannot exceed available qty (server-side, cannot be bypassed)
        $storeIdRaw = $request->inve_store_master_pk;
        $storeType = 'store';
        if (str_starts_with($storeIdRaw, 'sub_')) {
            $storeIdRaw = str_replace('sub_', '', $storeIdRaw);
            $storeType = 'sub_store';
        }
        $storeId = (int) $storeIdRaw;
        $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId, true);

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

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => ['items' => [implode(' ', $qtyErrors)]],
                ], 422);
            }

            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->withInput()
                ->withErrors($bag)
                ->with('open_add_modal', true);
        }

        try {
            DB::beginTransaction();

            $issueDate = now()->toDateString();
            $clientTypePk = $request->filled('client_type_pk') ? (int) $request->client_type_pk : null;
            $clientId = (in_array((string) $request->client_type_slug, ['employee', 'ot'], true) && $request->filled('client_id'))
                ? (int) $request->client_id
                : null;
            $clientName = trim((string) $request->client_name) ?: null;

            // One bill per person: reuse existing unpaid report for same buyer (same store + client)
            $report = SellingVoucherDateRangeReport::query()
                ->where('store_id', $storeId)
                ->where('store_type', $storeType)
                ->where('client_type_slug', $request->client_type_slug)
                ->where('status', '!=', SellingVoucherDateRangeReport::STATUS_APPROVED)
                ->where(function ($q) use ($clientTypePk, $clientId, $clientName) {
                    if ($clientTypePk !== null) {
                        $q->where('client_type_pk', $clientTypePk);
                    } else {
                        $q->whereNull('client_type_pk');
                    }

                    if ($clientId !== null) {
                        $q->where('client_id', $clientId);
                    } else {
                        $q->whereNull('client_id');
                    }

                    if ($clientName !== null) {
                        $q->where('client_name', $clientName);
                    } else {
                        $q->whereNull('client_name');
                    }
                })
                ->orderByDesc('id')
                ->first();

            if (!$report) {
                $report = SellingVoucherDateRangeReport::create([
                    'date_from' => $issueDate,
                    'date_to' => $issueDate,
                    'store_id' => $storeId,
                    'store_type' => $storeType,
                    'report_title' => null,
                    'status' => SellingVoucherDateRangeReport::STATUS_DRAFT,
                    'total_amount' => 0,
                    'remarks' => $request->remarks,
                    'reference_number' => $request->reference_number,
                    'order_by' => $request->order_by,
                    'client_type_slug' => $request->client_type_slug,
                    'client_type_pk' => $clientTypePk,
                    'client_id' => $clientId,
                    'client_name' => $request->client_name,
                    'payment_type' => (int) $request->payment_type,
                    'issue_date' => $issueDate,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                if ($request->hasFile('bill_file')) {
                    $path = $request->file('bill_file')->store('mess/selling-voucher/bills', 'public');
                    $report->update(['bill_path' => $path]);
                }
            } else {
                // Add to existing bill: extend date_to and issue_date to latest
                $newDate = Carbon::parse($issueDate);
                if ($report->date_to < $newDate) {
                    $report->update(['date_to' => $newDate, 'issue_date' => $newDate, 'updated_by' => Auth::id()]);
                }
                if ($request->hasFile('bill_file')) {
                    $path = $request->file('bill_file')->store('mess/selling-voucher/bills', 'public');
                    $report->update(['bill_path' => $path]);
                }
            }

            $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->pluck('item_subcategory_id'))->get()->keyBy('id');
            $grandTotal = 0;

            foreach ($request->items as $row) {
                $sub = $subcategories->get($row['item_subcategory_id']);
                $qty = (float) ($row['quantity'] ?? 0);
                $rate = (float) ($row['rate'] ?? 0);
                $avail = (float) ($row['available_quantity'] ?? 0);
                $itemIssueDate = $row['issue_date'] ?? $issueDate;
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
                    'issue_date' => $itemIssueDate,
                ]);
            }

            $report->increment('total_amount', $grandTotal);

            DB::commit();
            self::bumpSellingVoucherDateRangeListingCacheEpoch();

            // AJAX request: return JSON so frontend can keep modal open without full page reload
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Date Range Report created successfully.',
                    'report_id' => $report->id,
                ]);
            }

            // Normal request: redirect back and reopen Add modal
            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->with('success', 'Date Range Report created successfully.')
                ->with('open_add_modal', true);
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create report: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->withInput()
                ->with('error', 'Failed to create report: ' . $e->getMessage())
                ->with('open_add_modal', true);
        }
    }

    public function show(Request $request, $id)
    {
        $report = SellingVoucherDateRangeReport::with(['store', 'subStore', 'clientTypeCategory', 'course', 'items.itemSubcategory'])->findOrFail($id);

        if ($request->wantsJson()) {
            if ($this->sellingVoucherDateRangeListingFiltersActive($request)) {
                return $this->buildFilteredSellingVoucherDateRangeShowResponse($report, $request);
            }

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
                'reference_number' => $report->reference_number ?? '',
                'order_by' => $report->order_by ?? '',
                'created_at' => $report->created_at ? $report->created_at->format('d/m/Y H:i') : '—',
                'updated_at' => $report->updated_at ? $report->updated_at->format('d/m/Y H:i') : null,
                'bill_path' => $report->bill_path,
                'bill_url' => $report->bill_path ? asset('storage/' . $report->bill_path) : null,
            ];
            $items = $report->items->map(function ($item) use ($issueDateFormatted) {
                $qty = (float) $item->quantity;
                $retQty = (float) ($item->return_quantity ?? 0);
                $rate = (float) $item->rate;
                $amount = max(0, $qty - $retQty) * $rate;
                $itemIssueDate = $item->issue_date
                    ? (Carbon::parse($item->issue_date)->format('d/m/Y'))
                    : $issueDateFormatted;
                return [
                    'item_name' => $item->item_name ?: ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                    'unit' => $item->unit ?? '—',
                    'quantity' => $qty,
                    'available_quantity' => (float) ($item->available_quantity ?? 0),
                    'return_quantity' => $retQty,
                    'issue_date' => $itemIssueDate,
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

    private function sellingVoucherDateRangeListingFiltersActive(Request $request): bool
    {
        $store = collect((array) $request->input('store', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->values();

        $status = collect((array) $request->input('status', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->values();

        return $request->filled('start_date')
            || $request->filled('end_date')
            || $store->isNotEmpty()
            || $status->isNotEmpty()
            || $request->filled('client_type')
            || $request->filled('client_type_pk')
            || trim((string) $request->input('buyer_name', '')) !== ''
            || trim((string) $request->input('return_status', '')) !== '';
    }

    private function buildFilteredSellingVoucherDateRangeShowResponse(SellingVoucherDateRangeReport $report, Request $request): JsonResponse
    {
        $rows = $this->sellingVoucherDateRangeFilteredBuyerItemRows($report, $request);
        $issueDateFormatted = $report->issue_date ? $report->issue_date->format('d/m/Y') : '—';

        $storeNames = $this->resolveFilteredStoreNames($rows, $report);

        $voucher = [
            'id' => $report->id,
            'request_date' => $this->resolveFilteredShowRequestDate($request, $rows),
            'date_from' => $report->date_from ? $report->date_from->format('d/m/Y') : '—',
            'date_to' => $report->date_to ? $report->date_to->format('d/m/Y') : '—',
            'store_name' => $storeNames,
            'report_title' => $report->report_title ?? '—',
            'status' => $report->status,
            'status_label' => SellingVoucherDateRangeReport::statusLabels()[$report->status] ?? '—',
            'client_type' => $report->clientTypeCategory ? ucfirst($report->clientTypeCategory->client_type ?? '') : ($report->client_type_slug ? ucfirst($report->client_type_slug) : '—'),
            'client_name' => $report->display_client_name,
            'client_name_text' => $report->client_name ?? '—',
            'payment_type' => $report->payment_type == 1 ? 'Credit' : ($report->payment_type == 0 ? 'Cash' : ($report->payment_type == 2 ? 'Online' : '—')),
            'issue_date' => $issueDateFormatted,
            'remarks' => $report->remarks ?? '',
            'reference_number' => $report->reference_number ?? '',
            'order_by' => $report->order_by ?? '',
            'created_at' => $report->created_at ? $report->created_at->format('d/m/Y H:i') : '—',
            'updated_at' => $report->updated_at ? $report->updated_at->format('d/m/Y H:i') : null,
            'bill_path' => $report->bill_path,
            'bill_url' => $report->bill_path ? asset('storage/' . $report->bill_path) : null,
        ];

        $grandTotal = 0.0;
        $items = $rows->map(function ($row) use ($issueDateFormatted, &$grandTotal) {
            $qty = (float) ($row->quantity ?? 0);
            $retQty = (float) ($row->return_quantity ?? 0);
            $rate = (float) ($row->rate ?? 0);
            $amount = max(0, $qty - $retQty) * $rate;
            $grandTotal += $amount;

            $effectiveIssueDate = $row->issue_date ?? $row->date_from ?? null;
            $itemIssueDate = '—';
            if (!empty($effectiveIssueDate)) {
                try {
                    $itemIssueDate = Carbon::parse($effectiveIssueDate)->format('d/m/Y');
                } catch (\Exception $e) {
                    $itemIssueDate = $issueDateFormatted;
                }
            }

            return [
                'item_name' => trim((string) ($row->item_name ?? '')) ?: '—',
                'unit' => trim((string) ($row->unit ?? '')) ?: '—',
                'quantity' => $qty,
                'available_quantity' => 0,
                'return_quantity' => $retQty,
                'issue_date' => $itemIssueDate,
                'rate' => number_format($rate, 2),
                'amount' => number_format($amount, 2),
            ];
        })->values()->toArray();

        return response()->json([
            'voucher' => $voucher,
            'items' => $items,
            'grand_total' => number_format($grandTotal, 2),
            'has_items' => $rows->isNotEmpty(),
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    private function sellingVoucherDateRangeFilteredBuyerItemRows(SellingVoucherDateRangeReport $report, Request $request)
    {
        // Use listing date/store/status filters, but buyer identity comes from the opened voucher.
        // Do not reuse listing client_type_pk/buyer_name — same person can have legacy category pks.
        $filterRequest = Request::create(
            $request->url(),
            'GET',
            array_filter(
                [
                    'start_date' => $request->input('start_date'),
                    'end_date' => $request->input('end_date'),
                    'store' => $request->input('store'),
                    'status' => $request->input('status'),
                    'return_status' => $request->input('return_status'),
                    'client_type' => $request->input('client_type'),
                ],
                fn ($value) => $value !== null && $value !== '' && $value !== []
            )
        );

        $query = $this->sellingVoucherDateRangeItemRowsBaseQuery($filterRequest);
        $this->applySellingVoucherDateRangeAnchorBuyerScope($query, $report);
        $query->addSelect([
            'sri.rate',
            'sri.unit',
            'sri.return_date',
            'sri.item_subcategory_id',
            'sri.available_quantity',
            'sri.amount',
            'sv.store_id',
            'sv.store_type',
        ]);
        $query->orderByDesc(DB::raw('COALESCE(sri.issue_date, sv.date_from)'))
            ->orderByDesc('sv.id')
            ->orderByDesc('sri.id');

        return $query->get();
    }

    private function resolveFilteredStoreNames($rows, SellingVoucherDateRangeReport $report): string
    {
        $storeNames = $rows->pluck('resolved_store_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '' && $name !== 'N/A')
            ->unique()
            ->sort()
            ->values();

        return $storeNames->isNotEmpty()
            ? $storeNames->implode(', ')
            : ($report->resolved_store_name ?? '—');
    }

    private function buildFilteredSellingVoucherDateRangeReturnResponse(SellingVoucherDateRangeReport $report, Request $request): JsonResponse
    {
        $rows = $this->sellingVoucherDateRangeFilteredBuyerItemRows($report, $request);

        $items = $rows->map(function ($row) use ($report) {
            $effectiveIssueDate = $row->issue_date ?? $row->date_from ?? $report->issue_date;
            $issueDateYmd = '';
            if (!empty($effectiveIssueDate)) {
                try {
                    $issueDateYmd = Carbon::parse($effectiveIssueDate)->format('Y-m-d');
                } catch (\Exception $e) {
                    $issueDateYmd = '';
                }
            }

            $returnDateYmd = '';
            if (!empty($row->return_date)) {
                try {
                    $returnDateYmd = Carbon::parse($row->return_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    $returnDateYmd = '';
                }
            }

            return [
                'id' => (int) ($row->item_pk ?? 0),
                'item_name' => trim((string) ($row->item_name ?? '')) ?: '—',
                'quantity' => (float) ($row->quantity ?? 0),
                'unit' => trim((string) ($row->unit ?? '')) ?: '—',
                'issue_date' => $issueDateYmd,
                'return_quantity' => (float) ($row->return_quantity ?? 0),
                'return_date' => $returnDateYmd,
            ];
        })->values()->toArray();

        $firstIssueDate = $items[0]['issue_date'] ?? '';

        return response()->json([
            'store_name' => $this->resolveFilteredStoreNames($rows, $report),
            'client_name' => trim((string) ($report->client_name ?? '')),
            'client_type_slug' => (string) ($report->client_type_slug ?? ''),
            'issue_date' => $firstIssueDate !== ''
                ? $firstIssueDate
                : ($report->issue_date ? $report->issue_date->format('Y-m-d') : ''),
            'items' => $items,
        ]);
    }

    private function buildFilteredSellingVoucherDateRangeEditResponse(SellingVoucherDateRangeReport $report, Request $request): JsonResponse
    {
        $rows = $this->sellingVoucherDateRangeFilteredBuyerItemRows($report, $request);
        $availabilityCache = [];

        $clientTypeSlug = strtolower(trim((string) ($report->client_type_slug ?? 'employee')));
        if ($clientTypeSlug === '') {
            $clientTypeSlug = 'employee';
        }

        $storeType = $report->store_type ?? 'store';
        $storeId = (int) $report->store_id;
        $storeIdentifier = $storeType === 'sub_store' ? 'sub_'.$storeId : (string) $storeId;

        $uniqueStoreKeys = $rows->map(function ($row) {
            $type = (string) ($row->store_type ?? 'store');
            $id = (int) ($row->store_id ?? 0);

            return $type.'_'.$id;
        })->unique()->values();

        $items = $rows->map(function ($row) use ($report, &$availabilityCache) {
            $rowStoreType = (string) ($row->store_type ?? 'store');
            $rowStoreId = (int) ($row->store_id ?? 0);
            $cacheKey = $rowStoreType.'_'.$rowStoreId;
            if (!isset($availabilityCache[$cacheKey])) {
                $availabilityCache[$cacheKey] = AvailableQuantityService::availableQuantitiesForStore($rowStoreType, $rowStoreId);
            }

            $itemSubId = (int) ($row->item_subcategory_id ?? 0);
            $currentAvailable = $itemSubId > 0
                ? (float) ($availabilityCache[$cacheKey][$itemSubId] ?? 0)
                : (float) ($row->available_quantity ?? 0);

            $effectiveIssueDate = $row->issue_date ?? $row->date_from ?? $report->issue_date;
            $issueDateYmd = '';
            if (!empty($effectiveIssueDate)) {
                try {
                    $issueDateYmd = Carbon::parse($effectiveIssueDate)->format('Y-m-d');
                } catch (\Exception $e) {
                    $issueDateYmd = '';
                }
            }

            $rowStoreIdentifier = $rowStoreType === 'sub_store' ? 'sub_'.$rowStoreId : (string) $rowStoreId;

            $qty = (float) ($row->quantity ?? 0);
            $retQty = (float) ($row->return_quantity ?? 0);
            $netQty = max(0, $qty - $retQty);
            $rate = (float) ($row->rate ?? 0);

            return [
                'id' => (int) ($row->item_pk ?? 0),
                'report_id' => (int) ($row->report_id ?? 0),
                'item_subcategory_id' => $itemSubId > 0 ? $itemSubId : null,
                'item_name' => trim((string) ($row->item_name ?? '')) ?: '—',
                'unit' => trim((string) ($row->unit ?? '')) ?: '',
                'quantity' => $netQty,
                'original_quantity' => $qty,
                'available_quantity' => $currentAvailable,
                'return_quantity' => $retQty,
                'rate' => $rate,
                'amount' => $netQty * $rate,
                'issue_date' => $issueDateYmd,
                'store_id' => $rowStoreIdentifier,
                'store_name' => trim((string) ($row->resolved_store_name ?? '')),
            ];
        })->values()->toArray();

        $voucher = [
            'id' => $report->id,
            'filtered_view' => true,
            'multi_store' => $uniqueStoreKeys->count() > 1,
            'date_from' => $report->date_from ? $report->date_from->format('Y-m-d') : '',
            'date_to' => $report->date_to ? $report->date_to->format('Y-m-d') : '',
            'store_id' => $storeIdentifier,
            'inve_store_master_pk' => $storeIdentifier,
            'store_name_display' => $this->resolveFilteredStoreNames($rows, $report),
            'report_title' => $report->report_title,
            'status' => (int) $report->status,
            'remarks' => $report->remarks,
            'reference_number' => $report->reference_number,
            'order_by' => $report->order_by,
            'client_type_slug' => $clientTypeSlug,
            'client_type_pk' => $report->client_type_pk,
            'client_id' => $report->client_id,
            'client_name' => $report->client_name,
            'payment_type' => (int) $report->payment_type,
            'issue_date' => $report->issue_date ? $report->issue_date->format('Y-m-d') : '',
            'bill_path' => $report->bill_path,
            'bill_url' => $report->bill_path ? asset('storage/'.$report->bill_path) : null,
        ];

        return response()->json(['voucher' => $voucher, 'items' => $items]);
    }

    private function resolveFilteredShowRequestDate(Request $request, $rows): string
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            try {
                return Carbon::parse($request->start_date)->format('d/m/Y')
                    .' - '
                    .Carbon::parse($request->end_date)->format('d/m/Y');
            } catch (\Exception $e) {
                // Fall through to item-derived range.
            }
        }

        $dates = $rows->map(function ($row) {
            $raw = $row->issue_date ?? $row->date_from ?? null;
            if (empty($raw)) {
                return null;
            }
            try {
                return Carbon::parse($raw);
            } catch (\Exception $e) {
                return null;
            }
        })->filter()->values();

        if ($dates->isEmpty()) {
            return '—';
        }

        $min = $dates->min();
        $max = $dates->max();
        if ($min->isSameDay($max)) {
            return $min->format('d/m/Y');
        }

        return $min->format('d/m/Y').' - '.$max->format('d/m/Y');
    }

    private function applySellingVoucherDateRangeAnchorBuyerScope(Builder $q, SellingVoucherDateRangeReport $report): void
    {
        $slug = strtolower(trim((string) ($report->client_type_slug ?? '')));
        if ($slug !== '') {
            $q->where('sv.client_type_slug', $slug);
        }

        $clientTypePk = (int) ($report->client_type_pk ?? 0);
        $clientId = ($report->client_id !== null && (int) $report->client_id > 0)
            ? (int) $report->client_id
            : null;
        $clientName = trim((string) ($report->client_name ?? ''));

        // Same buyer can have vouchers under re-seeded/legacy client_type_pk values.
        // Listing matches by buyer name; View/Edit/Return must do the same or items disappear.
        if ($clientTypePk > 0) {
            if ($slug === ClientType::TYPE_EMPLOYEE) {
                $pkValues = $this->employeeClientTypePkFilterValues($clientTypePk);
                if ($pkValues !== []) {
                    $q->whereIn('sv.client_type_pk', $pkValues);
                }
            } elseif ($clientName !== '' || $clientId !== null) {
                $relatedPkQuery = SellingVoucherDateRangeReport::query()
                    ->whereNotNull('client_type_pk')
                    ->where('client_type_pk', '>', 0);
                if ($slug !== '') {
                    $relatedPkQuery->where('client_type_slug', $slug);
                }
                if ($clientId !== null) {
                    $relatedPkQuery->where(function ($bq) use ($clientId, $clientName) {
                        $bq->where('client_id', $clientId);
                        if ($clientName !== '') {
                            $bq->orWhere('client_name', $clientName)
                                ->orWhere('client_name', 'LIKE', $clientName.' (%');
                        }
                    });
                } else {
                    $relatedPkQuery->where(function ($bq) use ($clientName) {
                        $bq->where('client_name', $clientName)
                            ->orWhere('client_name', 'LIKE', $clientName.' (%');
                    });
                }
                $relatedPks = $relatedPkQuery
                    ->distinct()
                    ->pluck('client_type_pk')
                    ->map(fn ($pk) => (int) $pk)
                    ->filter(fn ($pk) => $pk > 0)
                    ->push($clientTypePk)
                    ->unique()
                    ->values()
                    ->all();
                $q->whereIn('sv.client_type_pk', $relatedPks !== [] ? $relatedPks : [$clientTypePk]);
            } else {
                $q->where('sv.client_type_pk', $clientTypePk);
            }
        }

        if ($clientId !== null) {
            $nameVariants = $clientName !== ''
                ? $this->buyerNameVariantsForClientFilter($clientName, $clientId, $clientTypePk)
                : [];

            $q->where(function ($bq) use ($clientId, $nameVariants) {
                $bq->where('sv.client_id', $clientId);

                if ($nameVariants !== []) {
                    $bq->orWhere(function ($fallback) use ($nameVariants) {
                        $fallback->where(function ($nullId) {
                            $nullId->whereNull('sv.client_id')->orWhere('sv.client_id', '<=', 0);
                        });
                        $fallback->where(function ($nameQ) use ($nameVariants) {
                            foreach ($nameVariants as $variant) {
                                $nameQ->orWhere(function ($nq) use ($variant) {
                                    $this->applyBuyerNamePatternFilter($nq, $variant);
                                });
                            }
                        });
                    });
                }
            });

            return;
        }

        if ($clientName !== '') {
            $q->where(function ($bq) use ($clientName) {
                $this->applyBuyerNamePatternFilter($bq, $clientName);
            });
        }
    }

    public function edit(Request $request, $id)
    {
        $report = SellingVoucherDateRangeReport::with(['store', 'subStore', 'items.itemSubcategory', 'course', 'clientTypeCategory'])->findOrFail($id);

        if ($report->status == SellingVoucherDateRangeReport::STATUS_APPROVED) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Edit is disabled for approved voucher.'], 403);
            }
            return redirect()->route('admin.mess.selling-voucher-date-range.index')->with('error', 'Edit is disabled for approved voucher.');
        }

        if ($request->wantsJson()) {
            if ($this->sellingVoucherDateRangeListingFiltersActive($request)) {
                return $this->buildFilteredSellingVoucherDateRangeEditResponse($report, $request);
            }

            $storeType = $report->store_type ?? 'store';
            $storeId = (int) $report->store_id;
            $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId);

            // Always use persisted slug. client_type_pk is mess_client_types.id for employee/section/other
            // but course_master.pk for ot/course — do not infer slug from clientTypeCategory for ot/course.
            $clientTypeSlug = strtolower(trim((string) ($report->client_type_slug ?? 'employee')));
            if ($clientTypeSlug === '') {
                $clientTypeSlug = 'employee';
            }

            $storeIdentifier = $storeType === 'sub_store' ? 'sub_' . $storeId : (string) $storeId;

            $voucher = [
                'id' => $report->id,
                'date_from' => $report->date_from ? $report->date_from->format('Y-m-d') : '',
                'date_to' => $report->date_to ? $report->date_to->format('Y-m-d') : '',
                'store_id' => $storeIdentifier,
                'inve_store_master_pk' => $storeIdentifier,
                'report_title' => $report->report_title,
                'status' => (int) $report->status,
                'remarks' => $report->remarks,
                'reference_number' => $report->reference_number,
                'order_by' => $report->order_by,
                'client_type_slug' => $clientTypeSlug,
                'client_type_pk' => $report->client_type_pk,
                'client_id' => $report->client_id,
                'client_name' => $report->client_name,
                'payment_type' => (int) $report->payment_type,
                'issue_date' => $report->issue_date ? $report->issue_date->format('Y-m-d') : '',
                'bill_path' => $report->bill_path,
                'bill_url' => $report->bill_path ? asset('storage/' . $report->bill_path) : null,
            ];
            $items = $report->items->map(function ($item) use ($availableMap, $report) {
                $itemId = (int) ($item->item_subcategory_id ?? 0);
                $currentAvailable = $itemId > 0 ? (float) ($availableMap[$itemId] ?? 0) : (float) ($item->available_quantity ?? 0);
                $qty = (float) $item->quantity;
                $retQty = (float) ($item->return_quantity ?? 0);
                $netQty = max(0, $qty - $retQty);
                $rate = (float) $item->rate;
                return [
                    'item_subcategory_id' => $item->item_subcategory_id,
                    'item_name' => $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                    'unit' => $item->unit ?? '',
                    // Edit form shows net issued qty (same billable basis as View total).
                    'quantity' => $netQty,
                    'original_quantity' => $qty,
                    'available_quantity' => $currentAvailable,
                    'return_quantity' => $retQty,
                    'rate' => $rate,
                    'amount' => $netQty * $rate,
                    'issue_date' => $item->issue_date ? $item->issue_date->format('Y-m-d') : '',
                    'store_name' => $report->resolved_store_name,
                    'store_id' => ($report->store_type === 'sub_store' ? 'sub_' : '') . (int) $report->store_id,
                ];
            })->values()->toArray();
            return response()->json(['voucher' => $voucher, 'items' => $items]);
        }

        return redirect()->route('admin.mess.selling-voucher-date-range.index');
    }

    public function update(Request $request, $id)
    {
        $report = SellingVoucherDateRangeReport::findOrFail($id);

        if ($report->status == SellingVoucherDateRangeReport::STATUS_APPROVED) {
            return redirect()->route('admin.mess.selling-voucher-date-range.index')->with('error', 'Edit is disabled for approved voucher.');
        }

        // Client identity is frozen on edit — always keep the voucher's stored values.
        $this->mergeLockedSellingVoucherClientIdentity($request, $report);

        // Multi-store / filtered edit must run before strict single-report validation
        // (legacy client_type_pk values may no longer exist in mess_client_types).
        if ($this->shouldUseFilteredSellingVoucherDateRangeUpdate($request)) {
            return $this->updateFilteredSellingVoucherDateRange($request, $report);
        }

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
            'client_type_slug' => 'required|string|in:employee,ot,course,section,other',
            'client_type_pk' => ['required', 'integer', 'min:1', function ($attribute, $value, $fail) use ($request, $report) {
                if ((int) $value === (int) ($report->client_type_pk ?? 0)) {
                    return;
                }
                $slug = $request->client_type_slug ?? '';
                if (in_array($slug, ['employee', 'section', 'other']) && !\App\Models\Mess\ClientType::where('id', $value)->exists()) {
                    $fail('The selected client is invalid.');
                }
                if (in_array($slug, ['ot', 'course']) && !CourseMaster::where('pk', $value)->exists()) {
                    $fail('The selected course is invalid.');
                }
            }],
            'client_id' => ['required_if:client_type_slug,employee,ot', 'nullable', 'integer'],
            'client_name' => in_array($request->client_type_slug, ['ot', 'course']) ? 'required|string|max:255' : 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'reference_number' => 'nullable|string|max:100',
            'order_by' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.available_quantity' => 'nullable|numeric|min:0',
            'bill_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            'remove_bill' => 'nullable|string|in:0,1',
        ], [
            'bill_file.mimes' => 'Bill must be PDF or image (jpg, jpeg, png, webp).',
            'bill_file.max' => 'Bill size must not exceed 5 MB.',
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
            $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId, true);
            $requestedByItem = [];
            foreach ((array) $request->items as $row) {
                $itemId = (int) ($row['item_subcategory_id'] ?? 0);
                $qty = (float) ($row['quantity'] ?? 0);
                if ($itemId > 0) $requestedByItem[$itemId] = ($requestedByItem[$itemId] ?? 0) + $qty;
            }

            // When updating: effective available = current stock + this voucher's existing net issue qty per item
            // (so saving without changes or adding different items does not fail)
            $existingQtyByItem = [];
            foreach ($report->items as $existingItem) {
                $itemId = (int) ($existingItem->item_subcategory_id ?? 0);
                if ($itemId > 0) {
                    $grossQty = (float) ($existingItem->quantity ?? 0);
                    $retQty = (float) ($existingItem->return_quantity ?? 0);
                    $existingQtyByItem[$itemId] = ($existingQtyByItem[$itemId] ?? 0) + max(0, $grossQty - $retQty);
                }
            }

            $subcategoriesForMsg = ItemSubcategory::whereIn('id', array_keys($requestedByItem))->get()->keyBy('id');
            foreach ($requestedByItem as $itemId => $totalQty) {
                $currentStock = (float) ($availableMap[$itemId] ?? 0);
                $existingInVoucher = (float) ($existingQtyByItem[$itemId] ?? 0);
                $avail = $currentStock + $existingInVoucher;
                if ($totalQty > $avail) {
                    $sub = $subcategoriesForMsg->get($itemId);
                    $name = $sub ? ($sub->item_name ?? $sub->name ?? ('Item #' . $itemId)) : ('Item #' . $itemId);
                    DB::rollBack();
                    return redirect()->route('admin.mess.selling-voucher-date-range.index')
                        ->withInput()
                        ->with('error', "{$name}: issue {$totalQty} cannot exceed available {$avail}.");
                }
            }

            // If header issue_date not sent from form, keep existing date
            $issueDate = $request->issue_date
                ?: ($report->issue_date
                    ? $report->issue_date->format('Y-m-d')
                    : ($report->date_from ? $report->date_from->format('Y-m-d') : now()->toDateString()));
            $clientId = (in_array((string) $request->client_type_slug, ['employee', 'ot'], true) && $request->filled('client_id'))
                ? (int) $request->client_id
                : null;
            $report->update([
                'date_from' => $issueDate,
                'date_to' => $issueDate,
                'store_id' => $storeId,
                'store_type' => $storeType,
                'report_title' => null,
                'status' => SellingVoucherDateRangeReport::STATUS_DRAFT,
                'remarks' => $request->remarks,
                'reference_number' => $request->reference_number,
                'order_by' => $request->order_by,
                'client_type_slug' => $request->client_type_slug,
                'client_type_pk' => $request->filled('client_type_pk') ? (int) $request->client_type_pk : null,
                'client_id' => $clientId,
                'client_name' => $request->client_name,
                'payment_type' => (int) $request->payment_type,
                'issue_date' => $issueDate,
                'updated_by' => Auth::id(),
            ]);

            if ($request->hasFile('bill_file')) {
                if ($report->bill_path && Storage::disk('public')->exists($report->bill_path)) {
                    Storage::disk('public')->delete($report->bill_path);
                }
                $path = $request->file('bill_file')->store('mess/selling-voucher/bills', 'public');
                $report->update(['bill_path' => $path]);
            } elseif ($request->filled('remove_bill') && $request->remove_bill === '1') {
                if ($report->bill_path && Storage::disk('public')->exists($report->bill_path)) {
                    Storage::disk('public')->delete($report->bill_path);
                }
                $report->update(['bill_path' => null]);
            }

            $report->items()->delete();

            $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->pluck('item_subcategory_id'))->get()->keyBy('id');
            $grandTotal = 0;

            foreach ($request->items as $row) {
                $sub = $subcategories->get($row['item_subcategory_id']);
                // Form quantity is net (billable). Preserve return and store gross = net + return.
                $netQty = (float) ($row['quantity'] ?? 0);
                $returnQty = (float) ($row['return_quantity'] ?? 0);
                if ($returnQty < 0) {
                    $returnQty = 0;
                }
                $grossQty = $netQty + $returnQty;
                $rate = (float) ($row['rate'] ?? 0);
                $avail = (float) ($row['available_quantity'] ?? 0);
                $itemIssueDate = $row['issue_date'] ?? $issueDate;
                $amount = $netQty * $rate;
                $grandTotal += $amount;
                SellingVoucherDateRangeReportItem::create([
                    'sv_date_range_report_id' => $report->id,
                    'item_subcategory_id' => $row['item_subcategory_id'],
                    'item_name' => $sub ? ($sub->item_name ?? $sub->name ?? '') : '',
                    'quantity' => $grossQty,
                    'available_quantity' => $avail,
                    'return_quantity' => $returnQty,
                    'rate' => $rate,
                    'amount' => $amount,
                    'unit' => $sub->unit_measurement ?? '',
                    'issue_date' => $itemIssueDate,
                ]);
            }

            $report->update(['total_amount' => $grandTotal]);

            DB::commit();
            self::bumpSellingVoucherDateRangeListingCacheEpoch();

            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->with('success', 'Date Range Report updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->with('error', 'Failed to update report: ' . $e->getMessage());
        }
    }

    private function shouldUseFilteredSellingVoucherDateRangeUpdate(Request $request): bool
    {
        if ($this->sellingVoucherDateRangeListingFiltersActive($request)) {
            return true;
        }

        if ((string) $request->input('multi_store', '0') === '1') {
            return true;
        }

        if ((string) $request->input('filtered_edit', '0') === '1') {
            return true;
        }

        foreach ((array) $request->input('items', []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            // Filtered edit payloads include line_id for existing rows.
            if ((int) ($row['line_id'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Edit must not change client type / client name / person identity.
     */
    private function mergeLockedSellingVoucherClientIdentity(Request $request, SellingVoucherDateRangeReport $report): void
    {
        $request->merge([
            'client_type_slug' => (string) ($report->client_type_slug ?? ''),
            'client_type_pk' => $report->client_type_pk,
            'client_id' => $report->client_id,
            'client_name' => $report->client_name,
        ]);
    }

    private function updateFilteredSellingVoucherDateRange(Request $request, SellingVoucherDateRangeReport $anchorReport)
    {
        // Client identity is frozen on edit — always keep the voucher's stored values.
        $this->mergeLockedSellingVoucherClientIdentity($request, $anchorReport);

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
            'client_type_slug' => 'required|string|in:employee,ot,course,section,other',
            'client_type_pk' => ['required', 'integer', 'min:1', function ($attribute, $value, $fail) use ($request, $anchorReport) {
                // Allow legacy pk still stored on the voucher even if category row was removed/re-seeded.
                if ((int) $value === (int) ($anchorReport->client_type_pk ?? 0)) {
                    return;
                }
                $slug = $request->client_type_slug ?? '';
                if (in_array($slug, ['employee', 'section', 'other']) && !\App\Models\Mess\ClientType::where('id', $value)->exists()) {
                    $fail('The selected client is invalid.');
                }
                if (in_array($slug, ['ot', 'course']) && !CourseMaster::where('pk', $value)->exists()) {
                    $fail('The selected course is invalid.');
                }
            }],
            'client_id' => ['required_if:client_type_slug,employee,ot', 'nullable', 'integer'],
            'client_name' => in_array($request->client_type_slug, ['ot', 'course']) ? 'required|string|max:255' : 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'reference_number' => 'nullable|string|max:100',
            'order_by' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.line_id' => 'nullable|integer|exists:sv_date_range_report_items,id',
            'items.*.store_id' => 'nullable|string',
            'items.*.item_subcategory_id' => ['required', 'integer', function ($attribute, $value, $fail) use ($request) {
                $value = (int) $value;
                if ($value > 0 && ItemSubcategory::where('id', $value)->exists()) {
                    return;
                }
                // Allow legacy subcategory ids still stored on existing lines.
                if (preg_match('/^items\.(\d+)\.item_subcategory_id$/', (string) $attribute, $m)) {
                    $lineId = (int) data_get($request->input('items'), ((int) $m[1]).'.line_id');
                    if ($lineId > 0) {
                        $existingSubId = (int) SellingVoucherDateRangeReportItem::where('id', $lineId)->value('item_subcategory_id');
                        if ($existingSubId > 0 && $existingSubId === $value) {
                            return;
                        }
                    }
                }
                $fail('The selected item is invalid.');
            }],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.available_quantity' => 'nullable|numeric|min:0',
            'items.*.issue_date' => 'nullable|date',
            'multi_store' => 'nullable|string|in:0,1',
            'filtered_edit' => 'nullable|string|in:0,1',
            'bill_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            'remove_bill' => 'nullable|string|in:0,1',
        ], [
            'bill_file.mimes' => 'Bill must be PDF or image (jpg, jpeg, png, webp).',
            'bill_file.max' => 'Bill size must not exceed 5 MB.',
        ]);

        $allowedRows = $this->sellingVoucherDateRangeFilteredBuyerItemRows($anchorReport, $request)
            ->keyBy('item_pk');
        $availabilityCache = [];
        $multiStore = $request->input('multi_store') === '1';
        $targetReportCache = [];

        try {
            DB::beginTransaction();

            $headerUpdate = [
                'remarks' => $request->remarks,
                'reference_number' => $request->reference_number,
                'order_by' => $request->order_by,
                'client_type_slug' => $request->client_type_slug,
                'client_type_pk' => $request->filled('client_type_pk') ? (int) $request->client_type_pk : null,
                'client_id' => (in_array((string) $request->client_type_slug, ['employee', 'ot'], true) && $request->filled('client_id'))
                    ? (int) $request->client_id
                    : null,
                'client_name' => $request->client_name,
                'payment_type' => (int) $request->payment_type,
                'updated_by' => Auth::id(),
            ];

            if (!$multiStore) {
                $storeIdRaw = $request->inve_store_master_pk;
                $storeType = 'store';
                if (str_starts_with((string) $storeIdRaw, 'sub_')) {
                    $storeIdRaw = str_replace('sub_', '', $storeIdRaw);
                    $storeType = 'sub_store';
                }
                $headerUpdate['store_id'] = (int) $storeIdRaw;
                $headerUpdate['store_type'] = $storeType;
            }

            $anchorReport->update($headerUpdate);

            if ($request->hasFile('bill_file')) {
                if ($anchorReport->bill_path && Storage::disk('public')->exists($anchorReport->bill_path)) {
                    Storage::disk('public')->delete($anchorReport->bill_path);
                }
                $path = $request->file('bill_file')->store('mess/selling-voucher/bills', 'public');
                $anchorReport->update(['bill_path' => $path]);
            } elseif ($request->filled('remove_bill') && $request->remove_bill === '1') {
                if ($anchorReport->bill_path && Storage::disk('public')->exists($anchorReport->bill_path)) {
                    Storage::disk('public')->delete($anchorReport->bill_path);
                }
                $anchorReport->update(['bill_path' => null]);
            }

            $subcategoryIds = collect($request->items)->pluck('item_subcategory_id')->filter()->unique()->values()->all();
            $subcategories = ItemSubcategory::whereIn('id', $subcategoryIds)->get()->keyBy('id');
            $updatedReportIds = [];

            foreach ((array) $request->items as $row) {
                $lineId = (int) ($row['line_id'] ?? 0);
                $itemSubId = (int) ($row['item_subcategory_id'] ?? 0);
                $netQty = (float) ($row['quantity'] ?? 0);
                $returnQty = (float) ($row['return_quantity'] ?? 0);
                if ($returnQty < 0) {
                    $returnQty = 0;
                }
                $grossQty = $netQty + $returnQty;
                $rate = (float) ($row['rate'] ?? 0);
                $avail = (float) ($row['available_quantity'] ?? 0);
                $sub = $subcategories->get($itemSubId);

                // New item row (no line_id): create on selected store's buyer report.
                if ($lineId <= 0) {
                    $storeIdentifier = trim((string) ($row['store_id'] ?? ''));
                    if ($storeIdentifier === '') {
                        DB::rollBack();

                        return redirect()->route('admin.mess.selling-voucher-date-range.index')
                            ->withInput()
                            ->with('error', 'Store is required for newly added items.');
                    }

                    $parsedStore = $this->parseStoreIdentifier($storeIdentifier);
                    if ($parsedStore['store_id'] <= 0) {
                        DB::rollBack();

                        return redirect()->route('admin.mess.selling-voucher-date-range.index')
                            ->withInput()
                            ->with('error', 'The selected store is invalid.');
                    }

                    $rowStoreType = $parsedStore['store_type'];
                    $rowStoreId = $parsedStore['store_id'];
                    $cacheKey = $rowStoreType.'_'.$rowStoreId;
                    if (!isset($availabilityCache[$cacheKey])) {
                        $availabilityCache[$cacheKey] = AvailableQuantityService::availableQuantitiesForStore($rowStoreType, $rowStoreId, true);
                    }

                    $currentStock = (float) ($availabilityCache[$cacheKey][$itemSubId] ?? 0);
                    if ($netQty > $currentStock) {
                        $name = $sub ? ($sub->item_name ?? $sub->name ?? ('Item #'.$itemSubId)) : ('Item #'.$itemSubId);
                        DB::rollBack();

                        return redirect()->route('admin.mess.selling-voucher-date-range.index')
                            ->withInput()
                            ->with('error', "{$name}: issue {$netQty} cannot exceed available {$currentStock}.");
                    }

                    $itemIssueDate = trim((string) ($row['issue_date'] ?? ''));
                    if ($itemIssueDate === '') {
                        if ($request->filled('end_date')) {
                            $itemIssueDate = (string) $request->input('end_date');
                        } elseif ($request->filled('start_date')) {
                            $itemIssueDate = (string) $request->input('start_date');
                        } elseif ($anchorReport->issue_date) {
                            $itemIssueDate = $anchorReport->issue_date->format('Y-m-d');
                        } elseif ($anchorReport->date_from) {
                            $itemIssueDate = $anchorReport->date_from->format('Y-m-d');
                        } else {
                            $itemIssueDate = now()->toDateString();
                        }
                    }
                    $targetReport = $this->findOrCreateBuyerStoreReport(
                        $anchorReport,
                        $request,
                        $rowStoreType,
                        $rowStoreId,
                        $itemIssueDate,
                        $targetReportCache
                    );

                    $amount = $netQty * $rate;
                    SellingVoucherDateRangeReportItem::create([
                        'sv_date_range_report_id' => $targetReport->id,
                        'item_subcategory_id' => $itemSubId,
                        'item_name' => $sub ? ($sub->item_name ?? $sub->name ?? '') : '',
                        'quantity' => $grossQty,
                        'available_quantity' => $avail,
                        'return_quantity' => $returnQty,
                        'rate' => $rate,
                        'amount' => $amount,
                        'unit' => $sub->unit_measurement ?? '',
                        'issue_date' => $itemIssueDate,
                    ]);

                    $updatedReportIds[$targetReport->id] = true;
                    continue;
                }

                if (!$allowedRows->has($lineId)) {
                    continue;
                }

                $item = SellingVoucherDateRangeReportItem::with('report')->find($lineId);
                if (!$item) {
                    continue;
                }

                $itemReport = $item->report;
                if (!$itemReport) {
                    continue;
                }

                $itemIssueDate = $row['issue_date'] ?? ($item->issue_date ? $item->issue_date->format('Y-m-d') : null);

                $rowStoreType = (string) ($itemReport->store_type ?? 'store');
                $rowStoreId = (int) ($itemReport->store_id ?? 0);
                $cacheKey = $rowStoreType.'_'.$rowStoreId;
                if (!isset($availabilityCache[$cacheKey])) {
                    $availabilityCache[$cacheKey] = AvailableQuantityService::availableQuantitiesForStore($rowStoreType, $rowStoreId, true);
                }

                $currentStock = (float) ($availabilityCache[$cacheKey][$itemSubId] ?? 0);
                $existingNetQty = max(0, (float) ($item->quantity ?? 0) - (float) ($item->return_quantity ?? 0));
                $effectiveAvailable = $currentStock + $existingNetQty;
                if ($netQty > $effectiveAvailable) {
                    $name = $sub ? ($sub->item_name ?? $sub->name ?? ('Item #'.$itemSubId)) : ('Item #'.$itemSubId);
                    DB::rollBack();

                    return redirect()->route('admin.mess.selling-voucher-date-range.index')
                        ->withInput()
                        ->with('error', "{$name}: issue {$netQty} cannot exceed available {$effectiveAvailable}.");
                }

                $amount = $netQty * $rate;
                $item->update([
                    'item_subcategory_id' => $itemSubId,
                    'item_name' => $sub ? ($sub->item_name ?? $sub->name ?? '') : ($item->item_name ?? ''),
                    'quantity' => $grossQty,
                    'available_quantity' => $avail,
                    'return_quantity' => $returnQty,
                    'rate' => $rate,
                    'amount' => $amount,
                    'unit' => $sub->unit_measurement ?? ($item->unit ?? ''),
                    'issue_date' => $itemIssueDate,
                ]);

                $updatedReportIds[$itemReport->id] = true;
            }

            foreach (array_keys($updatedReportIds) as $reportId) {
                $reportModel = SellingVoucherDateRangeReport::with('items')->find($reportId);
                if (!$reportModel) {
                    continue;
                }
                $grandTotal = $reportModel->items->sum(function ($line) {
                    $lineQty = (float) ($line->quantity ?? 0);
                    $lineRet = (float) ($line->return_quantity ?? 0);
                    $lineRate = (float) ($line->rate ?? 0);

                    return max(0, $lineQty - $lineRet) * $lineRate;
                });
                $reportModel->update(['total_amount' => $grandTotal]);
            }

            DB::commit();
            self::bumpSellingVoucherDateRangeListingCacheEpoch();

            $redirectParams = array_filter(
                $request->only(['status', 'store', 'client_type', 'client_type_pk', 'buyer_name', 'start_date', 'end_date', 'return_status']),
                fn ($value) => $value !== null && $value !== '' && $value !== []
            );

            return redirect()->route('admin.mess.selling-voucher-date-range.index', $redirectParams)
                ->with('success', 'Date Range Report updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->with('error', 'Failed to update report: '.$e->getMessage());
        }
    }

    /**
     * @return array{store_type: string, store_id: int}
     */
    private function parseStoreIdentifier(string $value): array
    {
        $value = trim($value);
        if (str_starts_with($value, 'sub_')) {
            return [
                'store_type' => 'sub_store',
                'store_id' => (int) str_replace('sub_', '', $value),
            ];
        }

        return [
            'store_type' => 'store',
            'store_id' => (int) $value,
        ];
    }

    /**
     * Reuse unpaid buyer+store report (same as Add Voucher), or create one.
     *
     * @param  array<string, SellingVoucherDateRangeReport>  $cache
     */
    private function findOrCreateBuyerStoreReport(
        SellingVoucherDateRangeReport $anchorReport,
        Request $request,
        string $storeType,
        int $storeId,
        string $itemIssueDate,
        array &$cache
    ): SellingVoucherDateRangeReport {
        $cacheKey = $storeType.'_'.$storeId;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $clientTypeSlug = (string) $request->input('client_type_slug', $anchorReport->client_type_slug);
        $clientTypePk = $request->filled('client_type_pk')
            ? (int) $request->client_type_pk
            : (($anchorReport->client_type_pk !== null) ? (int) $anchorReport->client_type_pk : null);
        $clientId = (in_array($clientTypeSlug, ['employee', 'ot'], true) && $request->filled('client_id'))
            ? (int) $request->client_id
            : (($anchorReport->client_id !== null && (int) $anchorReport->client_id > 0) ? (int) $anchorReport->client_id : null);
        $clientName = trim((string) ($request->input('client_name', $anchorReport->client_name) ?? '')) ?: null;

        $report = SellingVoucherDateRangeReport::query()
            ->where('store_id', $storeId)
            ->where('store_type', $storeType)
            ->where('client_type_slug', $clientTypeSlug)
            ->where('status', '!=', SellingVoucherDateRangeReport::STATUS_APPROVED)
            ->where(function ($q) use ($clientTypePk, $clientId, $clientName) {
                if ($clientTypePk !== null) {
                    $q->where('client_type_pk', $clientTypePk);
                } else {
                    $q->whereNull('client_type_pk');
                }

                if ($clientId !== null) {
                    $q->where('client_id', $clientId);
                } else {
                    $q->whereNull('client_id');
                }

                if ($clientName !== null) {
                    $q->where('client_name', $clientName);
                } else {
                    $q->whereNull('client_name');
                }
            })
            ->orderByDesc('id')
            ->first();

        if (!$report) {
            $report = SellingVoucherDateRangeReport::create([
                'date_from' => $itemIssueDate,
                'date_to' => $itemIssueDate,
                'store_id' => $storeId,
                'store_type' => $storeType,
                'report_title' => null,
                'status' => SellingVoucherDateRangeReport::STATUS_DRAFT,
                'total_amount' => 0,
                'remarks' => $request->remarks ?? $anchorReport->remarks,
                'reference_number' => $request->reference_number ?? $anchorReport->reference_number,
                'order_by' => $request->order_by ?? $anchorReport->order_by,
                'client_type_slug' => $clientTypeSlug,
                'client_type_pk' => $clientTypePk,
                'client_id' => $clientId,
                'client_name' => $clientName,
                'payment_type' => (int) $request->input('payment_type', $anchorReport->payment_type ?? 1),
                'issue_date' => $itemIssueDate,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        } else {
            $newDate = Carbon::parse($itemIssueDate);
            $updates = ['updated_by' => Auth::id()];
            if (!$report->date_to || $report->date_to->lt($newDate)) {
                $updates['date_to'] = $newDate;
                $updates['issue_date'] = $newDate;
            }
            if (!$report->date_from || $report->date_from->gt($newDate)) {
                $updates['date_from'] = $newDate;
            }
            $report->update($updates);
        }

        $cache[$cacheKey] = $report;

        return $report;
    }

    public function destroy($id)
    {
        $report = SellingVoucherDateRangeReport::findOrFail($id);
        $report->items()->delete();
        $report->delete();
        self::bumpSellingVoucherDateRangeListingCacheEpoch();

        return redirect()->route('admin.mess.selling-voucher-date-range.index')
            ->with('success', 'Date Range Report deleted successfully.');
    }

    /**
     * Return modal data (JSON): store name and items with return fields.
     */
    public function returnData(Request $request, $id)
    {
        $report = SellingVoucherDateRangeReport::with(['store', 'subStore', 'items.itemSubcategory'])->findOrFail($id);

        if (!$request->wantsJson()) {
            return redirect()->route('admin.mess.selling-voucher-date-range.index');
        }

        if ($this->sellingVoucherDateRangeListingFiltersActive($request)) {
            return $this->buildFilteredSellingVoucherDateRangeReturnResponse($report, $request);
        }

        $items = $report->items->map(function ($item) {
            return [
                'id' => $item->id,
                'item_name' => $item->item_name ?: ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit ?? '—',
                'issue_date' => $item->issue_date ? $item->issue_date->format('Y-m-d') : '',
                'return_quantity' => (float) ($item->return_quantity ?? 0),
                'return_date' => $item->return_date ? $item->return_date->format('Y-m-d') : '',
            ];
        })->values()->toArray();

        return response()->json([
            'store_name' => $report->resolved_store_name,
            'client_name' => trim((string) ($report->client_name ?? '')),
            'client_type_slug' => (string) ($report->client_type_slug ?? ''),
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
            'items.*.return_date' => 'nullable|date|before_or_equal:today',
        ]);

        $useFilteredScope = $this->sellingVoucherDateRangeListingFiltersActive($request);
        $allowedItemIds = $useFilteredScope
            ? $this->sellingVoucherDateRangeFilteredBuyerItemRows($report, $request)
                ->pluck('item_pk')
                ->map(fn ($itemId) => (int) $itemId)
                ->all()
            : $report->items->pluck('id')->map(fn ($itemId) => (int) $itemId)->all();

        try {
            DB::beginTransaction();
            foreach ($request->items as $row) {
                $itemId = (int) $row['id'];
                if (!in_array($itemId, $allowedItemIds, true)) {
                    continue;
                }
                $item = SellingVoucherDateRangeReportItem::with('report')->find($itemId);
                if (!$item) {
                    continue;
                }
                if (!$useFilteredScope && $item->sv_date_range_report_id != $report->id) {
                    continue;
                }
                $itemReport = $item->report ?? $report;
                $returnQty = (float) ($row['return_quantity'] ?? 0);
                $returnDate = !empty($row['return_date']) ? $row['return_date'] : null;
                $issuedQty = (float) ($item->quantity ?? 0);
                if ($returnQty > $issuedQty) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'Return quantity cannot be greater than issued quantity.');
                }
                if (!empty($returnDate)) {
                    try {
                        $ret = Carbon::parse($returnDate)->startOfDay();
                        if ($ret->gt(now()->startOfDay())) {
                            DB::rollBack();
                            return back()->withInput()->with('error', 'Return date cannot be in the future.');
                        }
                        $effectiveIssue = $item->issue_date ?: $itemReport->issue_date;
                        if ($effectiveIssue) {
                            $iss = Carbon::parse($effectiveIssue)->startOfDay();
                            if ($ret->lt($iss)) {
                                DB::rollBack();
                                return back()->withInput()->with('error', 'Return date cannot be earlier than issue date.');
                            }
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
            self::bumpSellingVoucherDateRangeListingCacheEpoch();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Return updated successfully.',
                    'client_name' => trim((string) ($report->client_name ?? '')),
                    'client_id' => $report->client_id,
                    'client_type_slug' => (string) ($report->client_type_slug ?? ''),
                ]);
            }

            $redirectParams = array_filter(
                $request->only(['status', 'store', 'client_type', 'client_type_pk', 'start_date', 'end_date', 'return_status']),
                fn ($value) => $value !== null && $value !== '' && $value !== []
            );
            $redirectParams['buyer_name'] = ($report->client_id !== null && (int) $report->client_id > 0)
                ? (string) (int) $report->client_id
                : trim((string) ($report->client_name ?? ''));
            $redirectParams['return_status'] = 'returned';

            return redirect()->route('admin.mess.selling-voucher-date-range.index', $redirectParams)
                ->with('success', 'Return updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update return: ' . $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', 'Failed to update return: ' . $e->getMessage());
        }
    }

    /**
     * @return Builder
     */
    private function applySellingVoucherDateRangeDatatableOrder(Builder $query, Request $request): void
    {
        $orderCol = DataTableSearchHelper::orderColumnIndex($request, 9);
        $orderDir = DataTableSearchHelper::orderDirection($request, 'desc');

        $effectiveDateExpr = DB::raw('COALESCE(sri.issue_date, sv.date_from)');

        $sortMap = [
            0 => $effectiveDateExpr,
            1 => 'sri.item_name',
            2 => 'sri.quantity',
            3 => 'sri.return_quantity',
            4 => DB::raw("(CASE
                WHEN sv.store_type = 'sub_store' AND mss.sub_store_name IS NOT NULL THEN mss.sub_store_name
                WHEN sv.store_type = 'store' AND ms.store_name IS NOT NULL THEN ms.store_name
                ELSE 'N/A' END)"),
            5 => 'sv.client_type_slug',
            6 => DB::raw("(CASE
                WHEN sv.client_type_slug IN ('ot', 'course') AND cm.course_name IS NOT NULL THEN cm.course_name
                ELSE COALESCE(mct.client_name, '') END)"),
            7 => 'sv.client_name',
            8 => 'sv.payment_type',
            9 => $effectiveDateExpr,
            10 => 'sv.status',
            11 => 'sri.return_quantity',
        ];

        if (isset($sortMap[$orderCol])) {
            $query->orderBy($sortMap[$orderCol], $orderDir);
        } else {
            $query->orderByDesc($effectiveDateExpr);
        }

        $query->orderByDesc('sv.id')->orderByDesc('sri.id');
    }

    private function sellingVoucherDateRangeItemRowsBaseQuery(Request $request)
    {
        $q = DB::table('sv_date_range_report_items as sri')
            ->join('sv_date_range_reports as sv', 'sri.sv_date_range_report_id', '=', 'sv.id')
            ->leftJoin('mess_stores as ms', function ($join) {
                $join->on('sv.store_id', '=', 'ms.id')
                    ->where('sv.store_type', '=', 'store');
            })
            ->leftJoin('mess_sub_stores as mss', function ($join) {
                $join->on('sv.store_id', '=', 'mss.id')
                    ->where('sv.store_type', '=', 'sub_store');
            })
            ->leftJoin('mess_client_types as mct', function ($join) {
                $join->on('sv.client_type_pk', '=', 'mct.id')
                    ->whereNotIn('sv.client_type_slug', ['ot', 'course']);
            })
            ->leftJoin('course_master as cm', function ($join) {
                $join->on('sv.client_type_pk', '=', 'cm.pk')
                    ->whereIn('sv.client_type_slug', ['ot', 'course']);
            })
            ->whereIn('sv.status', [
                SellingVoucherDateRangeReport::STATUS_DRAFT,
                SellingVoucherDateRangeReport::STATUS_FINAL,
                SellingVoucherDateRangeReport::STATUS_APPROVED,
            ])
            ->select([
                'sri.id as item_pk',
                'sv.id as report_id',
                'sri.item_name',
                'sri.quantity',
                'sri.return_quantity',
                'sv.client_name as voucher_client_name',
                'sv.payment_type',
                'sv.status',
                'sv.date_from',
                'sri.issue_date',
                'sv.client_type_slug',
                'mct.client_type as category_client_type',
                'mct.client_name as category_client_name',
                'cm.course_name',
                DB::raw("(CASE
                    WHEN sv.store_type = 'sub_store' AND mss.sub_store_name IS NOT NULL THEN CONCAT(mss.sub_store_name, ' (Sub-Store)')
                    WHEN sv.store_type = 'store' AND ms.store_name IS NOT NULL THEN ms.store_name
                    ELSE 'N/A' END) as resolved_store_name"),
            ]);

        $storeFilters = collect((array) $request->input('store', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (string) $value)
            ->values();
        if ($storeFilters->isNotEmpty()) {
            $storeIds = $storeFilters
                ->reject(fn ($value) => str_starts_with($value, 'sub_'))
                ->map(fn ($value) => (int) $value)
                ->filter(fn ($value) => $value > 0)
                ->values();

            $subStoreIds = $storeFilters
                ->filter(fn ($value) => str_starts_with($value, 'sub_'))
                ->map(fn ($value) => (int) str_replace('sub_', '', $value))
                ->filter(fn ($value) => $value > 0)
                ->values();

            $q->where(function ($storeQuery) use ($storeIds, $subStoreIds) {
                if ($storeIds->isNotEmpty()) {
                    $storeQuery->where(function ($nestedQuery) use ($storeIds) {
                        $nestedQuery->where('sv.store_type', 'store')
                            ->whereIn('sv.store_id', $storeIds->all());
                    });
                }

                if ($subStoreIds->isNotEmpty()) {
                    $method = $storeIds->isNotEmpty() ? 'orWhere' : 'where';
                    $storeQuery->{$method}(function ($nestedQuery) use ($subStoreIds) {
                        $nestedQuery->where('sv.store_type', 'sub_store')
                            ->whereIn('sv.store_id', $subStoreIds->all());
                    });
                }
            });
        }

        $statusFilters = collect((array) $request->input('status', []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (string) $value)
            ->values();
        if ($statusFilters->isNotEmpty()) {
            $q->whereIn('sv.status', $statusFilters->all());
        }

        $clientTypeSlug = (string) $request->input('client_type', '');
        if ($clientTypeSlug !== '' && in_array($clientTypeSlug, array_keys(ClientType::clientTypes()), true)) {
            $q->where('sv.client_type_slug', $clientTypeSlug);
        }
        if ($request->filled('client_type_pk')) {
            $clientTypePk = (int) $request->input('client_type_pk');
            if ($clientTypeSlug === ClientType::TYPE_EMPLOYEE) {
                $pkValues = $this->employeeClientTypePkFilterValues($clientTypePk);
                if ($pkValues !== []) {
                    $q->whereIn('sv.client_type_pk', $pkValues);
                }
            } else {
                $q->where('sv.client_type_pk', $clientTypePk);
            }
        }
        $this->applySellingVoucherDateRangeBuyerNameFilter($q, $request);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $q->whereBetween(DB::raw('COALESCE(sri.issue_date, sv.date_from)'), [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $q->whereRaw('COALESCE(sri.issue_date, sv.date_from) >= ?', [$request->start_date]);
        } elseif ($request->filled('end_date')) {
            $q->whereRaw('COALESCE(sri.issue_date, sv.date_from) <= ?', [$request->end_date]);
        }

        $returnStatus = strtolower(trim((string) $request->input('return_status', '')));
        if ($returnStatus === 'returned') {
            $q->where(DB::raw('COALESCE(sri.return_quantity, 0)'), '>', 0);
        } elseif ($returnStatus === 'not_returned') {
            $q->where(function ($rq) {
                $rq->whereNull('sri.return_quantity')->orWhere('sri.return_quantity', '<=', 0);
            });
        }

        return $q;
    }

    /**
     * Employee category filter must match current mess_client_types.id and legacy ids on old vouchers.
     *
     * @return list<int>
     */
    private function employeeClientTypePkFilterValues(int $selectedPk): array
    {
        if ($selectedPk <= 0) {
            return [];
        }

        $pks = [$selectedPk];
        $category = ClientType::query()
            ->where('id', $selectedPk)
            ->where('client_type', ClientType::TYPE_EMPLOYEE)
            ->value('client_name');

        if ($category !== null) {
            $legacy = self::LEGACY_EMPLOYEE_CLIENT_TYPE_PK[strtolower(trim((string) $category))] ?? [];
            $pks = array_merge($pks, $legacy);
        }

        return array_values(array_unique(array_map('intval', $pks)));
    }

    /**
     * Match buyer by client_id when available; otherwise fall back to client_name patterns.
     */
    private function applySellingVoucherDateRangeBuyerNameFilter(Builder $q, Request $request): void
    {
        $buyerName = trim((string) $request->input('buyer_name', ''));
        if ($buyerName === '') {
            return;
        }

        $clientTypeSlug = strtolower(trim((string) $request->input('client_type', '')));
        $clientTypePk = (int) $request->input('client_type_pk', 0);

        if (in_array($clientTypeSlug, [ClientType::TYPE_OT, ClientType::TYPE_COURSE], true)) {
            if (ctype_digit($buyerName)) {
                $q->where('sv.client_id', (int) $buyerName);
            } else {
                $q->where(function ($bq) use ($buyerName) {
                    $this->applyBuyerNamePatternFilter($bq, $buyerName);
                });
            }

            return;
        }

        $clientId = $this->resolveClientIdForBuyerFilter($buyerName, $clientTypeSlug);
        if ($clientId !== null && $clientId > 0) {
            $nameVariants = $this->buyerNameVariantsForClientFilter($buyerName, $clientId, $clientTypePk);

            $q->where(function ($bq) use ($clientId, $nameVariants) {
                $bq->where('sv.client_id', $clientId);

                if ($nameVariants !== []) {
                    $bq->orWhere(function ($fallback) use ($nameVariants) {
                        $fallback->where(function ($nullId) {
                            $nullId->whereNull('sv.client_id')->orWhere('sv.client_id', '<=', 0);
                        });
                        $fallback->where(function ($nameQ) use ($nameVariants) {
                            foreach ($nameVariants as $variant) {
                                $nameQ->orWhere(function ($nq) use ($variant) {
                                    $this->applyBuyerNamePatternFilter($nq, $variant);
                                });
                            }
                        });
                    });
                }
            });

            return;
        }

        $q->where(function ($bq) use ($buyerName) {
            $this->applyBuyerNamePatternFilter($bq, $buyerName);
        });
    }

    private function applyBuyerNamePatternFilter(Builder $q, string $buyerName): void
    {
        $q->where('sv.client_name', $buyerName)
            ->orWhere('sv.client_name', 'LIKE', $buyerName.' (%');
    }

    private function resolveClientIdForBuyerFilter(string $buyerName, string $clientTypeSlug): ?int
    {
        $buyerName = trim($buyerName);
        if ($buyerName === '') {
            return null;
        }

        if (ctype_digit($buyerName)) {
            return (int) $buyerName;
        }

        $baseName = trim((string) preg_replace('/\s*\([^)]+\)\s*$/', '', $buyerName));

        $existingClientIdQuery = SellingVoucherDateRangeReport::query()
            ->whereNotNull('client_id')
            ->where('client_id', '>', 0)
            ->where(function ($nameQ) use ($buyerName, $baseName) {
                $nameQ->where('client_name', $buyerName)
                    ->orWhere('client_name', 'LIKE', $buyerName.' (%');
                if ($baseName !== '' && $baseName !== $buyerName) {
                    $nameQ->orWhere('client_name', $baseName)
                        ->orWhere('client_name', 'LIKE', $baseName.' (%');
                }
            });

        if ($clientTypeSlug !== '' && in_array($clientTypeSlug, array_keys(ClientType::clientTypes()), true)) {
            $existingClientIdQuery->where('client_type_slug', $clientTypeSlug);
        }

        $existingClientId = $existingClientIdQuery->value('client_id');
        if ($existingClientId !== null && (int) $existingClientId > 0) {
            return (int) $existingClientId;
        }

        if ($clientTypeSlug !== '' && ! in_array($clientTypeSlug, [ClientType::TYPE_EMPLOYEE, ''], true)) {
            return null;
        }

        $employeePk = $this->findEmployeePkByDisplayName($buyerName, $baseName);
        if ($employeePk !== null && $employeePk > 0) {
            return $employeePk;
        }

        $facultyPk = FacultyMaster::query()
            ->where(function ($q) use ($buyerName, $baseName) {
                $q->where('full_name', $buyerName);
                if ($baseName !== '' && $baseName !== $buyerName) {
                    $q->orWhere('full_name', $baseName);
                }
            })
            ->value('pk');

        return ($facultyPk !== null && (int) $facultyPk > 0) ? (int) $facultyPk : null;
    }

    /**
     * @return list<string>
     */
    private function buyerNameVariantsForClientFilter(string $buyerName, int $clientId, int $clientTypePk): array
    {
        $variants = array_values(array_unique(array_filter([
            trim($buyerName),
            trim((string) preg_replace('/\s*\([^)]+\)\s*$/', '', $buyerName)),
            trim($this->resolveEmployeeBuyerNameForFilter($clientId, $clientTypePk)),
        ], fn ($name) => $name !== '')));

        $historicalNames = SellingVoucherDateRangeReport::query()
            ->where('client_id', $clientId)
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->distinct()
            ->pluck('client_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();

        return array_values(array_unique(array_merge($variants, $historicalNames)));
    }

    private function findEmployeePkByDisplayName(string $buyerName, string $baseName): ?int
    {
        $nameForMatch = trim($baseName !== '' ? $baseName : $buyerName);
        if ($nameForMatch === '') {
            return null;
        }

        $pk = EmployeeMaster::query()
            ->whereRaw(
                "TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(middle_name,''), ' ', COALESCE(last_name,''))) = ?",
                [$nameForMatch]
            )
            ->value('pk');

        return ($pk !== null && (int) $pk > 0) ? (int) $pk : null;
    }

    private function resolveEmployeeBuyerNameForFilter(int $employeePk, int $clientTypePk): string
    {
        if ($employeePk <= 0) {
            return '';
        }

        $categoryName = '';
        if ($clientTypePk > 0) {
            $categoryName = strtolower(trim((string) ClientType::query()
                ->where('id', $clientTypePk)
                ->where('client_type', ClientType::TYPE_EMPLOYEE)
                ->value('client_name')));
        }

        if ($categoryName === 'faculty') {
            return trim((string) FacultyMaster::query()
                ->where('pk', $employeePk)
                ->value('full_name'));
        }

        $employee = EmployeeMaster::query()
            ->select('first_name', 'middle_name', 'last_name', 'department_master_pk')
            ->where('pk', $employeePk)
            ->first();

        if (!$employee) {
            return '';
        }

        $fullName = trim(($employee->first_name ?? '').' '.($employee->middle_name ?? '').' '.($employee->last_name ?? ''));
        if ($fullName === '') {
            return '';
        }

        if (in_array($categoryName, ['academy staff', 'mess staff'], true)) {
            $departmentName = trim((string) DepartmentMaster::query()
                ->where('pk', $employee->department_master_pk)
                ->value('department_name'));
            if ($departmentName !== '') {
                return $fullName.' ('.$departmentName.')';
            }
        }

        return $fullName;
    }

    private function applySellingVoucherDateRangeItemSearch(Builder $q, string $search): void
    {
        $tokens = DataTableSearchHelper::tokens($search);
        if ($tokens === []) {
            return;
        }

        $q->where(function ($outer) use ($tokens) {
            foreach ($tokens as $token) {
                $term = DataTableSearchHelper::likePattern($token);
                $tokenLower = strtolower($token);

                $outer->where(function ($w) use ($term, $token, $tokenLower) {
                    $w->where('sri.item_name', 'like', $term)
                        ->orWhere('sv.client_name', 'like', $term)
                        ->orWhere('mct.client_name', 'like', $term)
                        ->orWhere('cm.course_name', 'like', $term)
                        ->orWhere('ms.store_name', 'like', $term)
                        ->orWhere('mss.sub_store_name', 'like', $term)
                        ->orWhereRaw(
                            '(CASE
                                WHEN sv.client_type_slug IN (?, ?) THEN ?
                                ELSE COALESCE(mct.client_type, sv.client_type_slug, ?)
                            END) LIKE ?',
                            ['ot', 'course', 'course', '', $term]
                        );

                    if (is_numeric($token)) {
                        $w->orWhere('sri.quantity', 'like', $term)->orWhere('sri.return_quantity', 'like', $term);
                    }

                    if (str_contains($tokenLower, 'credit')) {
                        $w->orWhere('sv.payment_type', 1);
                    }
                    if (str_contains($tokenLower, 'cash')) {
                        $w->orWhere('sv.payment_type', 0);
                    }
                    if (str_contains($tokenLower, 'upi') || str_contains($tokenLower, 'online')) {
                        $w->orWhere('sv.payment_type', 2);
                    }
                    if (str_contains($tokenLower, 'pending')) {
                        $w->orWhere('sv.status', SellingVoucherDateRangeReport::STATUS_DRAFT);
                    }
                    if (str_contains($tokenLower, 'approv')) {
                        $w->orWhere('sv.status', SellingVoucherDateRangeReport::STATUS_APPROVED);
                    }
                    if (str_contains($tokenLower, 'final')) {
                        $w->orWhere('sv.status', SellingVoucherDateRangeReport::STATUS_FINAL);
                    }
                    if (str_contains($tokenLower, 'return')) {
                        $w->orWhere(DB::raw('COALESCE(sri.return_quantity, 0)'), '>', 0);
                    }
                });
            }
        });
    }

    /**
     * @param  \stdClass  $row
     * @return array<int, string>
     */
    private function buildSellingVoucherDateRangeDatatableRow(\stdClass $row, int $serial, bool $canDeleteSellingVoucherDateRange): array
    {
        $reportId = (int) $row->report_id;
        $rq = isset($row->return_quantity) ? (float) $row->return_quantity : 0.0;
        $status = isset($row->status) ? (int) $row->status : -1;
        $paymentType = isset($row->payment_type) ? (int) $row->payment_type : -1;

        $clientTypeLabel = trim((string) ($row->category_client_type ?? ''));
        if ($clientTypeLabel === '') {
            $clientTypeLabel = trim((string) ($row->client_type_slug ?? ''));
        }
        $clientTypeLabel = $clientTypeLabel !== '' ? ucfirst($clientTypeLabel) : '—';

        $displayClientName = '—';
        if (in_array((string) ($row->client_type_slug ?? ''), ['ot', 'course'], true)) {
            $displayClientName = trim((string) ($row->course_name ?? ''));
        } else {
            $displayClientName = trim((string) ($row->category_client_name ?? ''));
        }
        if ($displayClientName === '') {
            $displayClientName = '—';
        }

        $paymentHtml = '<span class="badge text-bg-light border border-light-subtle fw-semibold">—</span>';
        if ($paymentType === 1) {
            $paymentHtml = '<span class="badge text-bg-light border border-light-subtle fw-semibold">Credit</span>';
        } elseif ($paymentType === 0) {
            $paymentHtml = '<span class="badge text-bg-light border border-light-subtle fw-semibold">Cash</span>';
        } elseif ($paymentType === 2) {
            $paymentHtml = '<span class="badge text-bg-light border border-light-subtle fw-semibold">UPI</span>';
        }

        $statusHtml = '<span class="badge rounded-1 text-bg-secondary">Final</span>';
        if ($status === 0) {
            $statusHtml = '<span class="badge rounded-1 text-bg-warning">Pending</span>';
        } elseif ($status === 2) {
            $statusHtml = '<span class="badge rounded-1 text-bg-success">Approved</span>';
        } elseif ($status === 4) {
            $statusHtml = '<span class="badge rounded-1 text-bg-primary">Completed</span>';
        }

        $requestDate = '—';
        $effectiveRequestDate = $row->issue_date ?? $row->date_from ?? null;
        if (!empty($effectiveRequestDate)) {
            try {
                $requestDate = Carbon::parse($effectiveRequestDate)->format('d/m/Y');
            } catch (\Exception $e) {
                $requestDate = '—';
            }
        }

        // View / Edit / Return / Delete are voucher-level (report_id). Show on every item
        // row so actions stay visible regardless of sort order or which line is MIN(id).
        $returnHtml = '<div class="d-flex flex-wrap align-items-center gap-1">';
        if ($rq > 0) {
            $returnHtml .= '<span class="badge rounded-1 text-bg-info">Returned</span>';
        }
        $returnHtml .= '<button type="button" class="btn btn-sm btn-outline-secondary btn-return-report d-inline-flex align-items-center gap-1 rounded-2 px-2" data-report-id="'.e((string) $reportId).'" title="Return"><i class="material-symbols-rounded" style="font-size: 1rem;">assignment_return</i><span>Return</span></button>';
        $returnHtml .= '</div>';

        $editDisabled = $status === SellingVoucherDateRangeReport::STATUS_APPROVED ? ' disabled' : '';
        $editTitle = $status === SellingVoucherDateRangeReport::STATUS_APPROVED
            ? e('Edit is disabled for approved voucher')
            : 'Edit';

        $actionHtml = '<div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">'
            .'<button type="button" class="btn btn-sm btn-outline-primary btn-view-report voucher-icon-btn rounded-2" data-report-id="'.e((string) $reportId).'" title="View"><i class="material-symbols-rounded">visibility</i></button>'
            .'<button type="button" class="btn btn-sm btn-outline-warning btn-edit-report voucher-icon-btn rounded-2" data-report-id="'.e((string) $reportId).'" title="'.$editTitle.'"'.$editDisabled.'><i class="material-symbols-rounded">edit</i></button>';

        if ($canDeleteSellingVoucherDateRange) {
            $destroyUrl = route('admin.mess.selling-voucher-date-range.destroy', $reportId);
            $actionHtml .= '<form action="'.e($destroyUrl).'" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this report?\');">'
                .csrf_field()
                .method_field('DELETE')
                .'<button type="submit" class="btn btn-sm btn-outline-danger voucher-icon-btn rounded-2" title="Delete"><i class="material-symbols-rounded">delete</i></button></form>';
        }

        $actionHtml .= '</div>';

        return [
            '<span class="text-body-secondary">'.e((string) $serial).'</span>',
            '<span class="cell-item-name fw-semibold text-wrap text-break">'.e((string) ($row->item_name ?? '—')).'</span>',
            '<span class="text-end font-monospace d-block">'.e((string) ($row->quantity ?? 0)).'</span>',
            '<span class="text-end font-monospace d-block">'.e((string) ($row->return_quantity ?? 0)).'</span>',
            '<span class="text-wrap text-break">'.e((string) ($row->resolved_store_name ?? 'N/A')).'</span>',
            e($clientTypeLabel),
            '<span class="text-wrap text-break">'.e($displayClientName).'</span>',
            '<span class="text-wrap text-break">'.e((string) ($row->voucher_client_name ?? '—')).'</span>',
            $paymentHtml,
            '<span class="text-body-secondary">'.e($requestDate).'</span>',
            '<div class="text-center">'.$statusHtml.'</div>',
            $returnHtml,
            '<div class="text-end pe-3">'.$actionHtml.'</div>',
        ];
    }

    /**
     * Get items available in a specific store (main store or sub-store)
     */
    public function getStoreItems($storeIdentifier)
    {
        try {
            return response()->json($this->getStoreItemsData($storeIdentifier));
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, array>
     */
    private function getStoreItemsData($storeIdentifier)
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
            // IMPORTANT: Use unit price INCLUDING tax so that date-range selling vouchers
            // reflect the tax-applied purchase cost in their Rate / Total.
            $fifoRows = DB::table('mess_purchase_order_items as poi')
                ->join('mess_purchase_orders as po', 'poi.purchase_order_id', '=', 'po.id')
                ->where('po.store_id', $storeId)
                ->where('po.status', 'approved')
                ->whereNotNull('poi.item_subcategory_id')
                ->where('poi.item_subcategory_id', '>', 0)
                ->orderBy('po.po_date', 'asc')
                ->orderBy('po.id')
                ->orderBy('poi.id')
                ->select(
                    'poi.item_subcategory_id',
                    'poi.quantity',
                    'poi.unit_price',
                    'poi.tax_percent'
                )
                ->get();

            $tiersByItem = [];
            foreach ($fifoRows as $r) {
                $id = (int) ($r->item_subcategory_id ?? 0);
                if ($id <= 0) continue;
                if (!isset($tiersByItem[$id])) {
                    $tiersByItem[$id] = [];
                }
                $unitPrice = (float) $r->unit_price;
                $taxPercent = isset($r->tax_percent) ? (float) $r->tax_percent : 0.0;
                $effectiveUnitPrice = $unitPrice * (1 + $taxPercent / 100);
                $tiersByItem[$id][] = [
                    'quantity' => (float) $r->quantity,
                    'unit_price' => $effectiveUnitPrice,
                ];
            }

            $purchasedItems = DB::table('mess_purchase_order_items as poi')
                ->join('mess_purchase_orders as po', 'poi.purchase_order_id', '=', 'po.id')
                ->where('po.store_id', $storeId)
                ->where('po.status', 'approved')
                ->select(
                    'poi.item_subcategory_id',
                    DB::raw('SUM(poi.quantity) as total_quantity'),
                    // Average unit price INCLUDING tax, matching FIFO tiers above
                    DB::raw('SUM(poi.quantity * poi.unit_price * (1 + COALESCE(poi.tax_percent, 0) / 100)) / NULLIF(SUM(poi.quantity), 0) as avg_unit_price')
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

        return $items->values();
    }

    /**
     * Limit listing rows to items matching return status (per-line return_quantity).
     *
     * @param  \Illuminate\Support\Collection<int, SellingVoucherDateRangeReport>  $reports
     * @return \Illuminate\Support\Collection<int, SellingVoucherDateRangeReport>
     */
    private function filterSellingVoucherDateRangeRowsByReturnStatus($reports, string $returnStatus)
    {
        $returnStatus = strtolower(trim($returnStatus));
        if ($returnStatus === '' || $returnStatus === 'all') {
            return $reports;
        }

        $wantReturned = $returnStatus === 'returned';
        if (! $wantReturned && $returnStatus !== 'not_returned') {
            return $reports;
        }

        return $reports->map(function ($report) use ($wantReturned) {
            $filtered = $report->items->filter(function ($item) use ($wantReturned) {
                $rq = (float) ($item->return_quantity ?? 0);

                return $wantReturned ? $rq > 0 : $rq <= 0;
            });
            $report->setRelation('items', $filtered);

            return $report;
        })->filter(fn ($report) => $report->items->isNotEmpty())->values();
    }
}
