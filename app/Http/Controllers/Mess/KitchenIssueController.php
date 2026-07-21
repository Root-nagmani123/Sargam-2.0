<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use App\Models\KitchenIssueMaster;
use App\Services\Mess\AvailableQuantityService;
use App\Models\KitchenIssueItem;
use App\Models\KitchenIssuePaymentDetail;
use App\Models\Mess\Store;
use App\Models\Mess\SubStore;
use App\Models\Mess\Inventory;
use App\Models\Mess\ItemSubcategory;
use App\Models\Mess\ClientType;
use App\Models\Mess\SellingVoucherDateRangeReport;
use App\Models\FacultyMaster;
use App\Models\EmployeeMaster;
use App\Models\DepartmentMaster;
use App\Models\CourseMaster;
use App\Models\StudentMaster;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class KitchenIssueController extends Controller
{
    private const SELLING_VOUCHER_DT_LIST_EPOCH = 'selling_voucher_dt_list_epoch';

    private const INDEX_MASTER_CACHE_EPOCH = 'kitchen_issue_index_master_epoch';

    /**
     * Invalidate Redis-backed Selling Voucher DataTables JSON after listing mutations.
     */
    public static function bumpSellingVoucherListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::SELLING_VOUCHER_DT_LIST_EPOCH, 'KitchenIssueController@sellingVouchersDatatable');
        AvailableQuantityService::bumpCacheEpoch();
        DataTableRedisCache::bumpListEpoch(self::INDEX_MASTER_CACHE_EPOCH, 'KitchenIssueController@indexMasterData');
    }

    /**
     * Historical mess_client_types.id values still stored on kitchen_issue_master rows
     * after employee categories were re-seeded (Academy Staff=3, Mess Staff=4, Faculty=5).
     *
     * @var array<string, list<int>>
     */
    private const LEGACY_EMPLOYEE_CLIENT_TYPE_PK = [
        'academy staff' => [2, 12],
        'faculty' => [1],
        'mess staff' => [],
    ];

    /**
     * Display a listing of selling vouchers (kitchen issues)
     */
    public function index(Request $request)
    {
        // Listing rows load via AJAX (server-side DataTables — sellingVouchersDatatable).
        $master = $this->loadIndexMasterFormData();
        $stores = collect($master['stores']);
        $itemSubcategories = collect($master['itemSubcategories']);
        $clientTypes = $master['clientTypes'];
        $clientNamesByType = collect($master['clientNamesByType'])->map(
            fn ($group) => collect($group)->map(fn ($row) => (object) $row)
        );
        $faculties = collect($master['faculties'])->map(fn ($row) => (object) $row);
        $employees = collect($master['employees'])->map(fn ($row) => (object) $row);
        $messStaff = collect($master['messStaff'])->map(fn ($row) => (object) $row);
        $otCourses = collect($master['otCourses'])->map(fn ($row) => (object) $row);

        $selectedClientType = (string) $request->input('client_type', '');
        $selectedClientTypePk = (string) $request->input('client_type_pk', '');
        $selectedBuyerName = trim((string) $request->input('buyer_name', ''));

        $typeSlugMap = [
            (string) KitchenIssueMaster::CLIENT_EMPLOYEE => 'employee',
            (string) KitchenIssueMaster::CLIENT_OT => 'ot',
            (string) KitchenIssueMaster::CLIENT_COURSE => 'course',
            (string) KitchenIssueMaster::CLIENT_SECTION => 'section',
            (string) KitchenIssueMaster::CLIENT_OTHER => 'other',
        ];
        $selectedTypeSlug = $typeSlugMap[$selectedClientType] ?? '';

        // When the user filtered by buyer only, restore type/category for the filter UI from the latest voucher.
        if ($selectedBuyerName !== '' && $selectedClientType === '') {
            $clientIdForInference = $this->resolveClientIdForBuyerFilter($selectedBuyerName, '');
            $inferredQuery = KitchenIssueMaster::query()
                ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER);

            if ($clientIdForInference !== null && $clientIdForInference > 0) {
                $inferredQuery->where('client_id', $clientIdForInference);
            } else {
                $inferredQuery->where(function ($q) use ($selectedBuyerName) {
                    $q->where('client_name', $selectedBuyerName)
                        ->orWhere('client_name', 'LIKE', $selectedBuyerName.' (%');
                });
            }

            $inferred = $inferredQuery
                ->orderByDesc('issue_date')
                ->orderByDesc('pk')
                ->first(['client_type', 'client_type_pk']);

            if ($inferred && $inferred->client_type) {
                $selectedClientType = (string) $inferred->client_type;
                $selectedClientTypePk = (string) ($inferred->client_type_pk ?? '');
                $selectedTypeSlug = $typeSlugMap[$selectedClientType] ?? '';
            }
        }

        $resolvedBuyerClientId = $this->resolveClientIdForBuyerFilter($selectedBuyerName, $selectedTypeSlug);
        if ($resolvedBuyerClientId !== null && $resolvedBuyerClientId > 0) {
            $selectedBuyerName = (string) $resolvedBuyerClientId;
        }

        $filterClientTypePkOptions = collect();
        if (in_array($selectedTypeSlug, ['ot', 'course'], true)) {
            $filterClientTypePkOptions = $otCourses->map(function ($course) {
                return [
                    'value' => (string) $course->pk,
                    'text' => (string) $course->course_name,
                ];
            })->values();
        } elseif ($selectedTypeSlug !== '' && isset($clientNamesByType[$selectedTypeSlug])) {
            $filterClientTypePkOptions = $clientNamesByType[$selectedTypeSlug]
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
        if ($selectedTypeSlug === 'employee' && $selectedClientTypePk !== '') {
            $selectedEmployeeBucket = strtolower(trim((string) $filterClientTypePkOptions
                ->firstWhere('value', $selectedClientTypePk)['text'] ?? ''));
            if ($selectedEmployeeBucket === 'academy staff') {
                $filterBuyerNames = collect($filterEmployeeBuyerOptions);
            } elseif ($selectedEmployeeBucket === 'faculty') {
                $filterBuyerNames = collect($filterFacultyBuyerOptions);
            } elseif ($selectedEmployeeBucket === 'mess staff') {
                $filterBuyerNames = collect($filterMessStaffBuyerOptions);
            }
        } elseif ($selectedTypeSlug === 'ot' && $selectedClientTypePk !== '') {
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
        } elseif (in_array($selectedTypeSlug, ['course', 'section', 'other'], true) && $selectedClientTypePk !== '') {
            $typeToNumber = [
                'course' => KitchenIssueMaster::CLIENT_COURSE,
                'section' => KitchenIssueMaster::CLIENT_SECTION,
                'other' => KitchenIssueMaster::CLIENT_OTHER,
            ];
            $pk = (int) $selectedClientTypePk;
            $kiBuyerNames = KitchenIssueMaster::query()
                ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                ->where('client_type', $typeToNumber[$selectedTypeSlug])
                ->where('client_type_pk', $pk)
                ->whereNotNull('client_name')
                ->where('client_name', '!=', '')
                ->distinct()
                ->orderBy('client_name')
                ->pluck('client_name')
                ->map(fn ($name) => trim((string) $name))
                ->filter();
            $drBuyerNames = SellingVoucherDateRangeReport::query()
                ->where('client_type_slug', $selectedTypeSlug)
                ->where('client_type_pk', $pk)
                ->whereHas('items')
                ->whereNotNull('client_name')
                ->where('client_name', '!=', '')
                ->distinct()
                ->pluck('client_name')
                ->map(fn ($name) => trim((string) $name))
                ->filter();
            $filterBuyerNames = $kiBuyerNames->concat($drBuyerNames)
                ->unique()
                ->sort()
                ->values();
        }

        if ($selectedBuyerName !== '' && ! $filterBuyerNames->contains(function ($item) use ($selectedBuyerName) {
            $value = is_array($item) ? (string) ($item['value'] ?? '') : (string) $item;

            return $value === (string) $selectedBuyerName;
        })) {
            $label = $selectedBuyerName;
            if (ctype_digit($selectedBuyerName)) {
                $resolvedLabel = $this->resolveEmployeeBuyerNameForFilter((int) $selectedBuyerName, (int) $selectedClientTypePk);
                if ($resolvedLabel !== '') {
                    $label = $resolvedLabel;
                }
            }
            $filterBuyerNames = $filterBuyerNames->prepend([
                'value' => $selectedBuyerName,
                'text' => $label,
            ])->values();
        }

        if ($selectedClientTypePk !== '' && $selectedClientType !== '') {
            $hasSelectedCategory = $filterClientTypePkOptions->contains(
                fn (array $option) => (string) ($option['value'] ?? '') === $selectedClientTypePk
            );
            if (! $hasSelectedCategory) {
                $categoryLabel = '';
                if (in_array($selectedTypeSlug, ['employee', 'section', 'other'], true)) {
                    $categoryLabel = (string) (ClientType::find((int) $selectedClientTypePk)?->client_name ?? '');
                } elseif (in_array($selectedTypeSlug, ['ot', 'course'], true)) {
                    $categoryLabel = (string) ($otCourses->firstWhere('pk', (int) $selectedClientTypePk)?->course_name ?? '');
                }
                if ($categoryLabel !== '') {
                    $filterClientTypePkOptions = $filterClientTypePkOptions->prepend([
                        'value' => $selectedClientTypePk,
                        'text' => $categoryLabel,
                    ])->values();
                }
            }
        }

        return view('mess.kitchen-issues.index', compact(
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
     * Cached dropdown/master payload for the Selling Voucher index form.
     *
     * @return array<string, mixed>
     */
    private function loadIndexMasterFormData(): array
    {
        $epoch = DataTableRedisCache::readListEpoch(self::INDEX_MASTER_CACHE_EPOCH);
        $cacheKey = 'kitchen_issue_index_master:v1:' . md5(json_encode(['epoch' => $epoch]));

        /** @var array<string, mixed> $payload */
        $payload = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'MESS_KITCHEN_ISSUE_INDEX_MASTER_CACHE_ENABLED',
                'seconds' => 'MESS_KITCHEN_ISSUE_INDEX_MASTER_CACHE_SECONDS',
            ],
            'KitchenIssueController@indexMasterData',
            fn () => $this->buildIndexMasterFormData()
        );

        return is_array($payload) ? $payload : $this->buildIndexMasterFormData();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIndexMasterFormData(): array
    {
        $otCourses = CourseMaster::orderByDesc('active_inactive')
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'active_inactive', 'end_date']);
        $today = Carbon::today();
        $otCourses->each(function ($course) use ($today) {
            if (filled($course->end_date) && Carbon::parse($course->end_date)->lt($today)) {
                $course->active_inactive = 0;
            }
        });

        $stores = Store::active()->get(['id', 'store_name'])->map(function ($store) {
            return [
                'id' => $store->id,
                'store_name' => $store->store_name,
                'type' => 'store',
            ];
        });

        $subStores = SubStore::active()->get(['id', 'sub_store_name'])->map(function ($subStore) {
            return [
                'id' => 'sub_' . $subStore->id,
                'store_name' => $subStore->sub_store_name . ' (Sub-Store)',
                'type' => 'sub_store',
                'original_id' => $subStore->id,
            ];
        });

        $stores = $stores->concat($subStores)->sortBy('store_name')->values();

        $itemSubcategories = ItemSubcategory::active()
            ->orderedByDisplayName()
            ->get($this->itemSubcategorySelectColumns())
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'item_name' => $s->item_name ?? $s->name ?? '—',
                    'item_code' => $s->item_code ?? $s->subcategory_code ?? '—',
                    'unit_measurement' => $s->unit_measurement ?? '—',
                    'standard_cost' => $s->standard_cost ?? 0,
                ];
            });

        $clientNamesByType = ClientType::active()
            ->orderBy('client_type')
            ->orderBy('client_name')
            ->get(['id', 'client_type', 'client_name'])
            ->groupBy('client_type')
            ->map(fn ($group) => $group->values()->all())
            ->all();

        $officersMessDept = DepartmentMaster::query()
            ->where('department_name', 'Officers Mess')
            ->first(['pk']);

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

                return [
                    'pk' => $f->pk,
                    'full_name' => $fullName,
                    'faculty_code' => $facultyCode,
                    'employee_master_pk' => $f->employee_master_pk,
                    'full_name_with_code' => $facultyCode !== '' ? ($fullName . ' (' . $facultyCode . ')') : $fullName,
                ];
            })
            ->values()
            ->all();

        $facultyEmployeePks = collect($faculties)
            ->pluck('employee_master_pk')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $departmentNamesByPk = DepartmentMaster::query()
            ->select(['pk', 'department_name'])
            ->pluck('department_name', 'pk');

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
            ->when(! empty($facultyEmployeePks), function ($q) use ($facultyEmployeePks) {
                $q->whereNotIn('pk', $facultyEmployeePks);
            })
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['pk', 'first_name', 'middle_name', 'last_name', 'department_master_pk'])
            ->map(function ($e) use ($buildEmployeeLabel) {
                $fullName = trim(($e->first_name ?? '') . ' ' . ($e->middle_name ?? '') . ' ' . ($e->last_name ?? ''));
                $fullName = $fullName ?: '—';

                return [
                    'pk' => $e->pk,
                    'full_name' => $fullName,
                    'full_name_with_department' => $buildEmployeeLabel($fullName, $e->department_master_pk ?? null),
                ];
            })
            ->filter(fn ($e) => $e['full_name'] !== '—')
            ->values()
            ->all();

        $messStaff = $officersMessDept
            ? EmployeeMaster::active()
                ->where('department_master_pk', $officersMessDept->pk)
                ->orderBy('first_name')->orderBy('last_name')
                ->get(['pk', 'first_name', 'middle_name', 'last_name', 'department_master_pk'])
                ->map(function ($e) use ($buildEmployeeLabel) {
                    $fullName = trim(($e->first_name ?? '') . ' ' . ($e->middle_name ?? '') . ' ' . ($e->last_name ?? ''));
                    $fullName = $fullName ?: '—';

                    return [
                        'pk' => $e->pk,
                        'full_name' => $fullName,
                        'full_name_with_department' => $buildEmployeeLabel($fullName, $e->department_master_pk ?? null),
                    ];
                })
                ->filter(fn ($e) => $e['full_name'] !== '—')
                ->values()
                ->all()
            : [];

        return [
            'stores' => $stores->values()->all(),
            'itemSubcategories' => $itemSubcategories->values()->all(),
            'clientTypes' => ClientType::clientTypes(),
            'clientNamesByType' => $clientNamesByType,
            'faculties' => $faculties,
            'employees' => $employees,
            'messStaff' => $messStaff,
            'otCourses' => $otCourses->map(fn ($course) => [
                'pk' => $course->pk,
                'course_name' => $course->course_name,
                'active_inactive' => $course->active_inactive,
                'end_date' => $course->end_date,
            ])->values()->all(),
        ];
    }

    /**
     * @return list<string>
     */
    private function itemSubcategorySelectColumns(): array
    {
        $columns = ['id', 'unit_measurement', 'standard_cost'];
        foreach (['item_name', 'name', 'subcategory_name', 'item_code', 'subcategory_code'] as $column) {
            if (Schema::hasColumn('mess_item_subcategories', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $employees
     * @return list<array{value: string, text: string}>
     */
    private function employeeBuyerFilterOptions($employees): array
    {
        return $employees->map(function ($employee) {
            $value = (string) (is_array($employee) ? ($employee['pk'] ?? '') : ($employee->pk ?? ''));
            $text = (string) (is_array($employee)
                ? ($employee['full_name_with_department'] ?? $employee['full_name'] ?? '')
                : ($employee->full_name_with_department ?? $employee->full_name ?? ''));
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
            $value = (string) (is_array($faculty) ? ($faculty['pk'] ?? '') : ($faculty->pk ?? ''));
            $text = (string) (is_array($faculty)
                ? ($faculty['full_name_with_code'] ?? $faculty['full_name'] ?? '')
                : ($faculty->full_name_with_code ?? $faculty->full_name ?? ''));
            if ($value === '' || $text === '') {
                return null;
            }

            return ['value' => $value, 'text' => $text];
        })->filter()->values()->all();
    }

    /**
     * Server-side DataTables JSON for Selling Voucher listing (one row per item line).
     * Cached via Redis ({@see DataTableRedisCache}); tune with SELLING_VOUCHER_DATATABLE_CACHE_* in .env.
     */
    public function sellingVouchersDatatable(Request $request): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $request,
            'selling_voucher_dt:v1:',
            self::SELLING_VOUCHER_DT_LIST_EPOCH,
            [
                'enabled' => 'SELLING_VOUCHER_DATATABLE_CACHE_ENABLED',
                'seconds' => 'SELLING_VOUCHER_DATATABLE_CACHE_SECONDS',
            ],
            'KitchenIssueController@sellingVouchersDatatable',
            fn () => $this->buildSellingVouchersDatatableResponse($request),
            $this->sellingVoucherDatatableFilterFingerprint($request)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sellingVoucherDatatableFilterFingerprint(Request $request): array
    {
        $store = $request->input('store');
        $status = $request->input('status');

        return [
            'store' => is_array($store) ? array_values($store) : (($store !== null && $store !== '') ? [$store] : []),
            'status' => is_array($status) ? array_values($status) : (($status !== null && $status !== '') ? [$status] : []),
            'payment_type' => $request->input('payment_type'),
            'client_type' => $request->input('client_type'),
            'client_type_pk' => $request->input('client_type_pk'),
            'buyer_name' => trim((string) $request->input('buyer_name', '')),
            'return_status' => strtolower(trim((string) $request->input('return_status', ''))),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'can_delete' => function_exists('hasRole') && (hasRole('Admin') || hasRole('Mess-Admin')),
        ];
    }

    private function buildSellingVouchersDatatableResponse(Request $request): JsonResponse
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

        $base = $this->sellingVoucherItemRowsBaseQuery($request);
        $recordsTotal = (clone $base)->count('kii.pk');

        $filtered = clone $base;
        $this->applySellingVoucherItemSearch($filtered, $searchRaw);

        // When the DataTables global search is empty, the filtered query matches `$base`; avoid a second COUNT.
        $searchTrimmed = DataTableSearchHelper::normalizeRaw($searchRaw);
        $recordsFiltered = $searchTrimmed === ''
            ? $recordsTotal
            : (clone $filtered)->count('kii.pk');

        $ordered = clone $filtered;
        $this->applySellingVoucherDatatableOrder($ordered, $request);

        $rows = $ordered
            ->offset($start)
            ->limit($length)
            ->get();

        $canDeleteSellingVoucher = hasRole('Admin') || hasRole('Mess-Admin');

        $data = [];
        foreach ($rows as $idx => $row) {
            $data[] = $this->buildSellingVoucherDatatableRow(
                $row,
                $start + $idx + 1,
                $canDeleteSellingVoucher
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
     * Apply DataTables column sort to selling voucher item query (action column excluded on frontend).
     */
    private function applySellingVoucherDatatableOrder(Builder $query, Request $request): void
    {
        $orderCol = DataTableSearchHelper::orderColumnIndex($request, 9);
        $orderDir = DataTableSearchHelper::orderDirection($request, 'desc');
        $misLabelSql = $this->messSubcategoryDisplayCoalesceSql();

        $sortMap = [
            0 => 'kim.issue_date',
            1 => DB::raw("COALESCE(NULLIF(TRIM(kii.item_name), ''), {$misLabelSql})"),
            2 => 'kii.quantity',
            3 => 'kii.return_quantity',
            4 => DB::raw("(CASE
                WHEN kim.store_type = 'sub_store' AND mss.sub_store_name IS NOT NULL THEN mss.sub_store_name
                WHEN kim.store_type = 'store' AND ms.store_name IS NOT NULL THEN ms.store_name
                ELSE 'N/A' END)"),
            5 => 'kim.client_type',
            6 => DB::raw("(CASE
                WHEN kim.client_type IN (2, 3) AND cm.course_name IS NOT NULL THEN cm.course_name
                ELSE COALESCE(mct.client_name, '') END)"),
            7 => 'kim.client_name',
            8 => 'kim.payment_type',
            9 => 'kim.issue_date',
            10 => 'kim.status',
            11 => 'kii.return_quantity',
        ];

        if (isset($sortMap[$orderCol])) {
            $query->orderBy($sortMap[$orderCol], $orderDir);
        } else {
            $query->orderByDesc('kim.issue_date');
        }

        $query->orderByDesc('kim.pk')->orderByDesc('kii.pk');
    }

    /**
     * SQL fragment for subcategory display label (DBs may have item_name, subcategory_name, or name only).
     */
    private function messSubcategoryDisplayCoalesceSql(): string
    {
        $parts = [];
        if (Schema::hasColumn('mess_item_subcategories', 'item_name')) {
            $parts[] = 'mis.item_name';
        }
        if (Schema::hasColumn('mess_item_subcategories', 'subcategory_name')) {
            $parts[] = 'mis.subcategory_name';
        }
        if (Schema::hasColumn('mess_item_subcategories', 'name')) {
            $parts[] = 'mis.name';
        }

        return $parts === [] ? 'NULL' : ('COALESCE(' . implode(',', $parts) . ')');
    }

    /** @return string[] Qualified columns on alias mis for LIKE search */
    private function messSubcategorySearchColumns(): array
    {
        $cols = [];
        foreach (['item_name', 'subcategory_name', 'name'] as $c) {
            if (Schema::hasColumn('mess_item_subcategories', $c)) {
                $cols[] = 'mis.' . $c;
            }
        }

        return $cols;
    }

    /**
     * @return Builder
     */
    private function sellingVoucherItemRowsBaseQuery(Request $request)
    {
        $q = DB::table('kitchen_issue_items as kii')
            ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
            ->leftJoin('mess_item_subcategories as mis', 'kii.item_subcategory_id', '=', 'mis.id')
            ->leftJoin('mess_stores as ms', function ($join) {
                $join->on('kim.store_id', '=', 'ms.id')
                    ->where('kim.store_type', '=', 'store');
            })
            ->leftJoin('mess_sub_stores as mss', function ($join) {
                $join->on('kim.store_id', '=', 'mss.id')
                    ->where('kim.store_type', '=', 'sub_store');
            })
            ->leftJoin('course_master as cm', function ($join) {
                $join->on('kim.client_type_pk', '=', 'cm.pk')
                    ->whereIn('kim.client_type', [KitchenIssueMaster::CLIENT_OT, KitchenIssueMaster::CLIENT_COURSE]);
            })
            ->leftJoin('mess_client_types as mct', function ($join) {
                $join->on('kim.client_type_pk', '=', 'mct.id')
                    ->whereNotIn('kim.client_type', [KitchenIssueMaster::CLIENT_OT, KitchenIssueMaster::CLIENT_COURSE]);
            })
            ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER);

        $misLabelSql = $this->messSubcategoryDisplayCoalesceSql();

        $q->select([
                'kii.pk as item_pk',
                'kim.pk as voucher_pk',
                'kii.item_name',
                'kii.quantity',
                'kii.return_quantity',
                'kim.client_name as voucher_client_name',
                'kim.payment_type',
                'kim.status',
                'kim.issue_date',
                'kim.created_at',
                DB::raw($misLabelSql . ' as sub_item_name'),
                DB::raw("(CASE
                    WHEN kim.store_type = 'sub_store' AND mss.sub_store_name IS NOT NULL THEN CONCAT(mss.sub_store_name, ' (Sub-Store)')
                    WHEN kim.store_type = 'store' AND ms.store_name IS NOT NULL THEN ms.store_name
                    ELSE 'N/A' END) as resolved_store_name"),
                DB::raw('(CASE kim.client_type
                    WHEN 1 THEN \'Employee\' WHEN 2 THEN \'OT\' WHEN 3 THEN \'Course\'
                    WHEN 4 THEN \'Other\' WHEN 5 THEN \'Section\' ELSE \'Unknown\' END) as client_type_label'),
                DB::raw("(CASE
                    WHEN kim.client_type IN (2, 3) AND cm.course_name IS NOT NULL THEN cm.course_name
                    ELSE COALESCE(mct.client_name, '—') END) as display_client_name"),
            ]);

        if ($request->filled('store')) {
            $storeFilter = $request->store;
            $storeFilters = is_array($storeFilter) ? $storeFilter : [$storeFilter];
            $q->where(function ($sub) use ($storeFilters) {
                foreach ($storeFilters as $filter) {
                    if (str_starts_with((string) $filter, 'sub_')) {
                        $sub->orWhere(function ($subQ) use ($filter) {
                            $subQ->where('kim.store_type', 'sub_store')
                                ->where('kim.store_id', (int) str_replace('sub_', '', $filter));
                        });
                    } else {
                        $sub->orWhere(function ($subQ) use ($filter) {
                            $subQ->where('kim.store_type', 'store')
                                ->where('kim.store_id', $filter);
                        });
                    }
                }
            });
        }

        if ($request->filled('status')) {
            $statusFilter = $request->status;
            if (is_array($statusFilter)) {
                $q->whereIn('kim.status', $statusFilter);
            } else {
                $q->where('kim.status', $statusFilter);
            }
        }

        if ($request->filled('payment_type')) {
            $q->where('kim.payment_type', $request->payment_type);
        }

        if ($request->filled('client_type')) {
            $q->where('kim.client_type', $request->client_type);
        }
        if ($request->filled('client_type_pk')) {
            $clientTypePk = (int) $request->input('client_type_pk');
            $clientType = (int) $request->input('client_type', 0);
            if ($clientType === KitchenIssueMaster::CLIENT_EMPLOYEE) {
                $q->whereIn('kim.client_type_pk', $this->employeeClientTypePkFilterValues($clientTypePk));
            } else {
                $q->where('kim.client_type_pk', $clientTypePk);
            }
        }
        $this->applySellingVoucherBuyerNameFilter($q, $request);

        // Date filter: default last 30 days when blank; skip default when user searches or targets a buyer/category.
        // Use sargable range predicates (no DATE()) so indexes on issue_date can be used.
        if (! $request->filled('start_date') && ! $request->filled('end_date')) {
            if (! $this->sellingVoucherShouldSkipDefaultDateWindow($request)) {
                $q->where('kim.issue_date', '>=', now()->subDays(30)->startOfDay());
            }
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $q->where('kim.issue_date', '>=', Carbon::parse($request->start_date)->startOfDay())
                ->where('kim.issue_date', '<', Carbon::parse($request->end_date)->addDay()->startOfDay());
        } elseif ($request->filled('start_date')) {
            $q->where('kim.issue_date', '>=', Carbon::parse($request->start_date)->startOfDay());
        } elseif ($request->filled('end_date')) {
            $q->where('kim.issue_date', '<', Carbon::parse($request->end_date)->addDay()->startOfDay());
        }

        $returnStatus = strtolower(trim((string) $request->input('return_status', '')));
        if ($returnStatus === 'returned') {
            $q->where(DB::raw('COALESCE(kii.return_quantity, 0)'), '>', 0);
        } elseif ($returnStatus === 'not_returned') {
            $q->where(function ($rq) {
                $rq->whereNull('kii.return_quantity')->orWhere('kii.return_quantity', '<=', 0);
            });
        }

        return $q;
    }

    /**
     * Skip the default 30-day issue_date window when the user is clearly looking for specific records.
     */
    private function sellingVoucherShouldSkipDefaultDateWindow(Request $request): bool
    {
        if ($request->filled('client_type_pk') || trim((string) $request->input('buyer_name', '')) !== '') {
            return true;
        }

        $searchPayload = $request->input('search');
        if (is_array($searchPayload) && trim((string) ($searchPayload['value'] ?? '')) !== '') {
            return true;
        }

        return false;
    }

    /**
     * Employee category filter must match current mess_client_types.id and legacy ids still on old vouchers.
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
    private function applySellingVoucherBuyerNameFilter(Builder $q, Request $request): void
    {
        $buyerName = trim((string) $request->input('buyer_name', ''));
        if ($buyerName === '') {
            return;
        }

        $clientTypeSlug = $this->sellingVoucherClientTypeSlugFromRequest($request);
        $clientTypePk = (int) $request->input('client_type_pk', 0);

        if (in_array($clientTypeSlug, [ClientType::TYPE_OT, ClientType::TYPE_COURSE], true)) {
            if (ctype_digit($buyerName)) {
                $q->where('kim.client_id', (int) $buyerName);
            } else {
                $q->where(function ($bq) use ($buyerName) {
                    $this->applyKitchenIssueBuyerNamePatternFilter($bq, $buyerName);
                });
            }

            return;
        }

        $clientId = $this->resolveClientIdForBuyerFilter($buyerName, $clientTypeSlug);
        if ($clientId !== null && $clientId > 0) {
            $nameVariants = $this->buyerNameVariantsForClientFilter($buyerName, $clientId, $clientTypePk);

            $q->where(function ($bq) use ($clientId, $nameVariants) {
                $bq->where('kim.client_id', $clientId);

                if ($nameVariants !== []) {
                    $bq->orWhere(function ($fallback) use ($nameVariants) {
                        $fallback->where(function ($nullId) {
                            $nullId->whereNull('kim.client_id')->orWhere('kim.client_id', '<=', 0);
                        });
                        $fallback->where(function ($nameQ) use ($nameVariants) {
                            foreach ($nameVariants as $variant) {
                                $nameQ->orWhere(function ($nq) use ($variant) {
                                    $this->applyKitchenIssueBuyerNamePatternFilter($nq, $variant);
                                });
                            }
                        });
                    });
                }
            });

            return;
        }

        $q->where(function ($bq) use ($buyerName) {
            $this->applyKitchenIssueBuyerNamePatternFilter($bq, $buyerName);
        });
    }

    private function applyKitchenIssueBuyerNamePatternFilter(Builder $q, string $buyerName): void
    {
        $q->where('kim.client_name', $buyerName)
            ->orWhere('kim.client_name', 'LIKE', $buyerName.' (%');
    }

    private function sellingVoucherClientTypeSlugFromRequest(Request $request): string
    {
        $map = [
            (string) KitchenIssueMaster::CLIENT_EMPLOYEE => ClientType::TYPE_EMPLOYEE,
            (string) KitchenIssueMaster::CLIENT_OT => ClientType::TYPE_OT,
            (string) KitchenIssueMaster::CLIENT_COURSE => ClientType::TYPE_COURSE,
            (string) KitchenIssueMaster::CLIENT_SECTION => ClientType::TYPE_SECTION,
            (string) KitchenIssueMaster::CLIENT_OTHER => ClientType::TYPE_OTHER,
        ];

        return $map[(string) $request->input('client_type', '')] ?? '';
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

        $existingClientIdQuery = KitchenIssueMaster::query()
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
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

        if ($clientTypeSlug !== '') {
            $clientTypeMap = [
                ClientType::TYPE_EMPLOYEE => KitchenIssueMaster::CLIENT_EMPLOYEE,
                ClientType::TYPE_OT => KitchenIssueMaster::CLIENT_OT,
                ClientType::TYPE_COURSE => KitchenIssueMaster::CLIENT_COURSE,
                ClientType::TYPE_SECTION => KitchenIssueMaster::CLIENT_SECTION,
                ClientType::TYPE_OTHER => KitchenIssueMaster::CLIENT_OTHER,
            ];
            if (isset($clientTypeMap[$clientTypeSlug])) {
                $existingClientIdQuery->where('client_type', $clientTypeMap[$clientTypeSlug]);
            }
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

        $historicalNames = KitchenIssueMaster::query()
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
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

        if (! $employee) {
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

    private function applySellingVoucherItemSearch(Builder $q, string $search): void
    {
        $tokens = DataTableSearchHelper::tokens($search);
        if ($tokens === []) {
            return;
        }

        $misCols = $this->messSubcategorySearchColumns();

        $q->where(function ($outer) use ($tokens, $misCols) {
            foreach ($tokens as $token) {
                $term = DataTableSearchHelper::likePattern($token);
                $tokenLower = strtolower($token);

                $outer->where(function ($w) use ($term, $token, $tokenLower, $misCols) {
                    $w->where('kii.item_name', 'like', $term);
                    foreach ($misCols as $col) {
                        $w->orWhere($col, 'like', $term);
                    }
                    $w->orWhere('kim.client_name', 'like', $term)
                        ->orWhere('ms.store_name', 'like', $term)
                        ->orWhere('mss.sub_store_name', 'like', $term)
                        ->orWhere('cm.course_name', 'like', $term)
                        ->orWhere('mct.client_name', 'like', $term)
                        ->orWhereRaw(
                            '(CASE kim.client_type
                                WHEN ? THEN \'employee\' WHEN ? THEN \'ot\' WHEN ? THEN \'course\'
                                WHEN ? THEN \'section\' WHEN ? THEN \'other\' ELSE \'\' END) LIKE ?',
                            [
                                KitchenIssueMaster::CLIENT_EMPLOYEE,
                                KitchenIssueMaster::CLIENT_OT,
                                KitchenIssueMaster::CLIENT_COURSE,
                                KitchenIssueMaster::CLIENT_SECTION,
                                KitchenIssueMaster::CLIENT_OTHER,
                                $term,
                            ]
                        );

                    if (is_numeric($token)) {
                        $w->orWhere('kii.quantity', 'like', $term)->orWhere('kii.return_quantity', 'like', $term);
                    }

                    if (str_contains($tokenLower, 'credit')) {
                        $w->orWhere('kim.payment_type', KitchenIssueMaster::PAYMENT_CREDIT);
                    }
                    if (str_contains($tokenLower, 'cash')) {
                        $w->orWhere('kim.payment_type', KitchenIssueMaster::PAYMENT_CASH);
                    }
                    if (str_contains($tokenLower, 'upi') || str_contains($tokenLower, 'online')) {
                        $w->orWhere('kim.payment_type', KitchenIssueMaster::PAYMENT_ONLINE);
                    }
                    if (str_contains($tokenLower, 'pending')) {
                        $w->orWhere('kim.status', KitchenIssueMaster::STATUS_PENDING);
                    }
                    if (str_contains($tokenLower, 'approv')) {
                        $w->orWhere('kim.status', KitchenIssueMaster::STATUS_APPROVED);
                    }
                    if (str_contains($tokenLower, 'complet')) {
                        $w->orWhere('kim.status', KitchenIssueMaster::STATUS_COMPLETED);
                    }
                    if (str_contains($tokenLower, 'return')) {
                        $w->orWhere(DB::raw('COALESCE(kii.return_quantity, 0)'), '>', 0);
                    }
                });
            }
        });
    }

    /**
     * @param  \stdClass  $row  Selected columns + joined fields
     * @return array<int, string>
     */
    private function buildSellingVoucherDatatableRow(\stdClass $row, int $serial, bool $canDeleteSellingVoucher): array
    {
        $pk = (int) $row->voucher_pk;
        $rq = isset($row->return_quantity) ? (float) $row->return_quantity : 0.0;
        $status = isset($row->status) ? (int) $row->status : 0;
        $paymentType = isset($row->payment_type) ? (int) $row->payment_type : -1;

        $itemCell = '';
        $rawItemName = trim((string) ($row->item_name ?? ''));
        if ($rawItemName !== '') {
            $itemCell = e($rawItemName);
        } else {
            $fallback = trim((string) ($row->sub_item_name ?? ''));
            $itemCell = $fallback !== '' ? e($fallback) : '—';
        }

        $paymentHtml = '<span class="text-muted">—</span>';
        if ($paymentType === KitchenIssueMaster::PAYMENT_CREDIT) {
            $paymentHtml = '<span class="badge rounded-1 text-bg-warning">Credit</span>';
        } elseif ($paymentType === KitchenIssueMaster::PAYMENT_CASH) {
            $paymentHtml = '<span class="badge rounded-1 text-bg-secondary">Cash</span>';
        } elseif ($paymentType === KitchenIssueMaster::PAYMENT_ONLINE) {
            $paymentHtml = '<span class="badge rounded-1 text-bg-info">UPI</span>';
        }

        $statusHtml = '<span class="badge rounded-1 text-bg-secondary">'.e((string) $status).'</span>';
        if ($status === KitchenIssueMaster::STATUS_PENDING) {
            $statusHtml = '<span class="badge rounded-1 text-bg-warning">Pending</span>';
        } elseif ($status === KitchenIssueMaster::STATUS_APPROVED) {
            $statusHtml = '<span class="badge rounded-1 text-bg-success">Approved</span>';
        } elseif ($status === KitchenIssueMaster::STATUS_COMPLETED) {
            $statusHtml = '<span class="badge rounded-1 text-bg-primary">Completed</span>';
        }

        $reqDateRaw = $row->issue_date ?? $row->created_at ?? '';
        $reqDate = '—';
        if ($reqDateRaw) {
            try {
                $reqDate = Carbon::parse($reqDateRaw)->format('d/m/Y');
            } catch (\Exception $e) {
                $reqDate = '—';
            }
        }

        $returnBadge = '';
        if ($rq > 0) {
            $returnBadge = '<span class="badge rounded-1 text-bg-info">Returned</span>';
        }

        $deleteForm = '';
        if ($canDeleteSellingVoucher) {
            $destroyUrl = route('admin.mess.material-management.destroy', $pk);
            $deleteForm = '<form action="'.e($destroyUrl).'" method="POST" class="d-inline m-0" onsubmit="return confirm(\'Are you sure you want to delete this Selling Voucher?\');">'
                .csrf_field()
                .method_field('DELETE')
                .'<button type="submit" class="btn btn-sm btn-light border rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 2.25rem; height: 2.25rem;" title="Delete" aria-label="Delete voucher"><i class="material-symbols-rounded text-danger" style="font-size: 1.125rem;">delete</i></button></form>';
        }

        $editDisabled = $status === KitchenIssueMaster::STATUS_APPROVED ? ' disabled' : '';

        return [
            '<span class="text-muted font-monospace">'.e((string) $serial).'</span>',
            '<span class="fw-medium">'.$itemCell.'</span>',
            '<span class="font-monospace">'.e((string) ($row->quantity ?? '')).'</span>',
            '<span class="font-monospace">'.e((string) ($row->return_quantity ?? 0)).'</span>',
            e((string) ($row->resolved_store_name ?? 'N/A')),
            e((string) ($row->client_type_label ?? '—')),
            e((string) ($row->display_client_name ?? '—')),
            e((string) ($row->voucher_client_name ?? '—')),
            $paymentHtml,
            '<span class="text-nowrap">'.e($reqDate).'</span>',
            $statusHtml,
            '<div class="d-flex flex-wrap gap-2 align-items-center justify-content-center">'.$returnBadge
                .'<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 btn-return-sv" data-voucher-id="'.e((string) $pk).'" title="Return">Return</button></div>',
            '<div class="d-inline-flex align-items-center justify-content-center gap-1">'
                .'<button type="button" class="btn btn-sm btn-light border btn-view-sv rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 2.25rem; height: 2.25rem;" data-voucher-id="'.e((string) $pk).'" title="View" aria-label="View voucher"><i class="material-symbols-rounded text-primary" style="font-size: 1.125rem;">visibility</i></button>'
                .'<button type="button" class="btn btn-sm btn-light border btn-edit-sv rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 2.25rem; height: 2.25rem;" data-voucher-id="'.e((string) $pk).'" title="'.($status === KitchenIssueMaster::STATUS_APPROVED ? e('Edit is disabled for approved voucher') : 'Edit').'" aria-label="Edit voucher"'.$editDisabled.'><i class="material-symbols-rounded text-warning" style="font-size: 1.125rem;">edit</i></button>'
                .$deleteForm
                .'</div>',
        ];
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
     * AJAX: previously used buyer names for Selling Voucher.
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

        $clientType = match ($slug) {
            'course' => KitchenIssueMaster::CLIENT_COURSE,
            'section' => KitchenIssueMaster::CLIENT_SECTION,
            'other' => KitchenIssueMaster::CLIENT_OTHER,
        };

        $svBuyers = SellingVoucherDateRangeReport::query()
            ->where('client_type_slug', $slug)
            ->where('client_type_pk', $clientTypePk)
            ->whereHas('items')
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->distinct()
            ->pluck('client_name')
            ->map(fn ($n) => trim((string) $n));

        $kiBuyers = KitchenIssueMaster::query()
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
            ->where('client_type', $clientType)
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
        $faculties = FacultyMaster::whereNotNull('full_name')
            ->where('full_name', '!=', '')
            ->where('faculty_type', 1)
            ->whereNotNull('employee_master_pk')
            ->whereIn('employee_master_pk', EmployeeMaster::active()->select('pk'))
            ->orderBy('full_name')
            ->get(['pk', 'full_name', 'faculty_code'])
            ->map(function ($f) {
                $fullName = trim((string) ($f->full_name ?? ''));
                $facultyCode = trim((string) ($f->faculty_code ?? ''));
                $f->full_name_with_code = $facultyCode !== '' ? ($fullName . ' (' . $facultyCode . ')') : $fullName;
                return $f;
            });
        $departmentNamesByPk = DepartmentMaster::pluck('department_name', 'pk');

        $buildEmployeeLabel = function ($fullName, $departmentPk) use ($departmentNamesByPk) {
            $fullName = trim((string) $fullName);
            if ($fullName === '') {
                $fullName = '—';
            }
            $departmentName = trim((string) ($departmentNamesByPk[$departmentPk] ?? ''));
            return $departmentName !== '' ? ($fullName . ' (' . $departmentName . ')') : $fullName;
        };

        $employees = EmployeeMaster::active()
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

        $officersMessDept = DepartmentMaster::where('department_name', 'Officers Mess')->first();
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
            'name_id' => 'nullable|integer',
            'client_name' => in_array($request->client_type_slug, ['ot', 'course']) ? 'required|string|max:255' : 'nullable|string|max:255',
            'issue_date' => 'required|date',
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

            $storeId = $request->store_id;
            $storeType = 'store';
            if (str_starts_with($storeId, 'sub_')) {
                $storeId = str_replace('sub_', '', $storeId);
                $storeType = 'sub_store';
            }
            $storeId = (int) $storeId;

            // Server-side enforcement: issue qty cannot exceed available qty (per store + item)
            $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId, true);
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
                'section' => KitchenIssueMaster::CLIENT_SECTION,
                'other' => KitchenIssueMaster::CLIENT_OTHER,
            ];
            $clientType = $clientTypeMap[$request->client_type_slug] ?? KitchenIssueMaster::CLIENT_EMPLOYEE;
            $clientTypePk = $request->filled('client_type_pk') ? (int) $request->client_type_pk : null;
            
            // For Employee (type 1) and OT (type 2), store the actual pk (employee_master.pk or student_master.pk)
            $clientId = null;
            if (in_array($request->client_type_slug, ['employee', 'ot']) && $request->filled('client_id')) {
                $clientId = (int) $request->client_id;
            }

            // Always create a fresh Selling Voucher entry.
            // Earlier logic tried to "reuse" an existing pending voucher for the same
            // buyer and store, which caused later items to appear in multiple
            // entries and also updated the original voucher's date.
            $master = KitchenIssueMaster::create([
                'store_id' => $storeId,
                'store_type' => $storeType,
                'payment_type' => $request->payment_type,
                'client_type' => $clientType,
                'client_type_pk' => $clientTypePk,
                'client_id' => $clientId,
                'name_id' => $request->name_id,
                'client_name' => $request->client_name,
                'issue_date' => $request->issue_date,
                'kitchen_issue_type' => KitchenIssueMaster::TYPE_SELLING_VOUCHER,
                'status' => KitchenIssueMaster::STATUS_PENDING,
                'remarks' => $request->remarks,
                'reference_number' => $request->reference_number,
                'order_by' => $request->order_by,
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
            self::bumpSellingVoucherListingCacheEpoch();

            // Modal / fetch: return JSON so the form can reset without full page reload (header + respond_json flag)
            $returnJson = $request->ajax()
                || $request->wantsJson()
                || $request->expectsJson()
                || $request->boolean('respond_json');
            if ($returnJson) {
                return response()->json([
                    'success' => true,
                    'message' => 'Selling Voucher created successfully',
                    'voucher_id' => $master->pk,
                ]);
            }

            return redirect()->route('admin.mess.material-management.index')
                ->with('success', 'Selling Voucher created successfully')
                ->with('open_selling_voucher_modal', true);
        } catch (ValidationException $e) {
            DB::rollBack();

            $returnJson = $request->ajax()
                || $request->wantsJson()
                || $request->expectsJson()
                || $request->boolean('respond_json');
            if ($returnJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }

            return redirect()->route('admin.mess.material-management.index')
                ->withErrors($e->errors())
                ->withInput()
                ->with('open_selling_voucher_modal', true);
        } catch (\Exception $e) {
            DB::rollBack();

            $returnJson = $request->ajax()
                || $request->wantsJson()
                || $request->expectsJson()
                || $request->boolean('respond_json');
            if ($returnJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create Selling Voucher: ' . $e->getMessage(),
                ], 500);
            }

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
                'reference_number' => $kitchenIssue->reference_number ?? '',
                'order_by' => $kitchenIssue->order_by ?? '',
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
        $kitchenIssue = KitchenIssueMaster::with([
            'items.itemSubcategory',
            'clientTypeCategory',
            'course',
            'employee',
            'student',
            'store',
            'subStore',
        ])->findOrFail($id);

        if ($kitchenIssue->status == KitchenIssueMaster::STATUS_APPROVED) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Edit is disabled for approved voucher.'], 403);
            }
            return redirect()->route('admin.mess.material-management.index')->with('error', 'Edit is disabled for approved voucher.');
        }

        if ($request->wantsJson()) {
            // Map numeric client_type back to slug
            $clientTypeSlugMap = [
                KitchenIssueMaster::CLIENT_EMPLOYEE => 'employee',
                KitchenIssueMaster::CLIENT_OT => 'ot',
                KitchenIssueMaster::CLIENT_COURSE => 'course',
                KitchenIssueMaster::CLIENT_OTHER => 'other',
                KitchenIssueMaster::CLIENT_SECTION => 'section',
            ];
            $clientTypeSlug = $clientTypeSlugMap[$kitchenIssue->client_type] ?? 'employee';

            $storeType = $kitchenIssue->store_type ?? 'store';
            $storeId = (int) $kitchenIssue->store_id;
            $storeIdentifier = $storeType === 'sub_store' ? 'sub_' . $storeId : (string) $storeId;
            $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId);

            $resolvedClientName = trim((string) ($kitchenIssue->client_name ?? ''));
            if ($resolvedClientName === '') {
                $resolvedClientName = trim((string) ($kitchenIssue->client_full_name ?? ''));
            }
            
            $voucher = [
                'pk' => $kitchenIssue->pk,
                'payment_type' => (int) $kitchenIssue->payment_type,
                'client_type' => (int) $kitchenIssue->client_type,
                'client_type_pk' => $kitchenIssue->client_type_pk,
                'client_type_slug' => $clientTypeSlug,
                'client_id' => $kitchenIssue->client_id,
                'name_id' => $kitchenIssue->name_id,
                'client_name' => $resolvedClientName,
                'issue_date' => $kitchenIssue->issue_date ? $kitchenIssue->issue_date->format('Y-m-d') : '',
                'store_id' => $storeIdentifier,
                'inve_store_master_pk' => $storeIdentifier, // For backward compatibility with view
                'remarks' => $kitchenIssue->remarks,
                'reference_number' => $kitchenIssue->reference_number,
                'order_by' => $kitchenIssue->order_by,
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

        if ($kitchenIssue->status == KitchenIssueMaster::STATUS_APPROVED) {
            return redirect()->route('admin.mess.material-management.index')->with('error', 'Edit is disabled for approved voucher.');
        }

        // Edit form may omit client_id when identity fields are unchanged; keep existing buyer for employee/OT.
        $incomingSlug = strtolower(trim((string) $request->input('client_type_slug', '')));
        if (
            !$request->filled('client_id')
            && in_array($incomingSlug, ['employee', 'ot'], true)
            && !empty($kitchenIssue->client_id)
            && in_array((int) $kitchenIssue->client_type, [
                KitchenIssueMaster::CLIENT_EMPLOYEE,
                KitchenIssueMaster::CLIENT_OT,
            ], true)
        ) {
            $request->merge(['client_id' => (int) $kitchenIssue->client_id]);
        }

        // Historical vouchers may reference item_subcategory_id values that no longer exist
        // (e.g. the subcategory was later deleted). Don't block saving the rest of the voucher
        // over a row the user didn't touch — only require a live subcategory for ids that are
        // new to this voucher (i.e. the user actually picked them in this edit).
        $preExistingSubcategoryIds = $kitchenIssue->items()->pluck('item_subcategory_id')->all();

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
            'name_id' => 'nullable|integer',
            'client_name' => in_array($request->client_type_slug, ['ot', 'course']) ? 'required|string|max:255' : 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'remarks' => 'nullable|string',
            'reference_number' => 'nullable|string|max:100',
            'order_by' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => ['required', function ($attribute, $value, $fail) use ($preExistingSubcategoryIds) {
                if (in_array($value, $preExistingSubcategoryIds, false)) {
                    // Already on this voucher before this edit — preserve it even if the
                    // subcategory record was since deleted.
                    return;
                }
                if (!\App\Models\Mess\ItemSubcategory::where('id', $value)->exists()) {
                    $fail('The selected item is invalid.');
                }
            }],
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

            $storeId = $request->store_id;
            $storeType = 'store';
            if (str_starts_with($storeId, 'sub_')) {
                $storeId = str_replace('sub_', '', $storeId);
                $storeType = 'sub_store';
            }
            $storeId = (int) $storeId;

            // Server-side enforcement: issue qty cannot exceed available qty (per store + item)
            $availableMap = AvailableQuantityService::availableQuantitiesForStore($storeType, $storeId, true);
            $requestedByItem = [];
            foreach ((array) $request->items as $row) {
                $itemId = (int) ($row['item_subcategory_id'] ?? 0);
                $qty = (float) ($row['quantity'] ?? 0);
                if ($itemId > 0) {
                    $requestedByItem[$itemId] = ($requestedByItem[$itemId] ?? 0) + $qty;
                }
            }

            // When updating: effective available = current stock + this voucher's existing issue qty per item
            // (so saving without changes does not fail)
            $existingQtyByItem = [];
            $existingItemDisplayById = [];
            foreach ($kitchenIssue->items as $existingItem) {
                $itemId = (int) ($existingItem->item_subcategory_id ?? 0);
                if ($itemId > 0) {
                    $existingQtyByItem[$itemId] = ($existingQtyByItem[$itemId] ?? 0) + (float) ($existingItem->quantity ?? 0);
                    if (!isset($existingItemDisplayById[$itemId])) {
                        $existingItemDisplayById[$itemId] = [
                            'item_name' => $existingItem->item_name ?? '',
                            'unit' => $existingItem->unit ?? '',
                        ];
                    }
                }
            }

            // Map client_type_slug to numeric value
            $clientTypeMap = [
                'employee' => KitchenIssueMaster::CLIENT_EMPLOYEE,
                'ot' => KitchenIssueMaster::CLIENT_OT,
                'course' => KitchenIssueMaster::CLIENT_COURSE,
                'section' => KitchenIssueMaster::CLIENT_SECTION,
                'other' => KitchenIssueMaster::CLIENT_OTHER,
            ];

            // For Employee (type 1) and OT (type 2), store the actual pk (employee_master.pk or student_master.pk)
            $clientId = null;
            if (in_array($request->client_type_slug, ['employee', 'ot']) && $request->filled('client_id')) {
                $clientId = (int) $request->client_id;
            } elseif (
                in_array($request->client_type_slug, ['employee', 'ot'], true)
                && in_array((int) $kitchenIssue->client_type, [
                    KitchenIssueMaster::CLIENT_EMPLOYEE,
                    KitchenIssueMaster::CLIENT_OT,
                ], true)
                && !empty($kitchenIssue->client_id)
            ) {
                // Preserve existing buyer when edit form omits client_id (e.g. identity fields unchanged)
                $clientId = (int) $kitchenIssue->client_id;
            }

            $kitchenIssue->update([
                'store_id' => $storeId,
                'store_type' => $storeType,
                'payment_type' => $request->payment_type,
                'client_type' => $clientTypeMap[$request->client_type_slug] ?? KitchenIssueMaster::CLIENT_EMPLOYEE,
                'client_type_pk' => $request->filled('client_type_pk') ? (int) $request->client_type_pk : null,
                'client_id' => $clientId,
                'name_id' => $request->name_id,
                'client_name' => $request->client_name,
                'issue_date' => $request->issue_date,
                'remarks' => $request->remarks,
                'reference_number' => $request->reference_number,
                'order_by' => $request->order_by,
            ]);

            if ($request->hasFile('bill_file')) {
                if ($kitchenIssue->bill_path && Storage::disk('public')->exists($kitchenIssue->bill_path)) {
                    Storage::disk('public')->delete($kitchenIssue->bill_path);
                }
                $path = $request->file('bill_file')->store('mess/selling-voucher/bills', 'public');
                $kitchenIssue->update(['bill_path' => $path]);
            } elseif ($request->filled('remove_bill') && $request->remove_bill === '1') {
                if ($kitchenIssue->bill_path && Storage::disk('public')->exists($kitchenIssue->bill_path)) {
                    Storage::disk('public')->delete($kitchenIssue->bill_path);
                }
                $kitchenIssue->update(['bill_path' => null]);
            }

            $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->pluck('item_subcategory_id'))->get()->keyBy('id');

            // Validate requested qty vs available (aggregated across duplicate rows)
            // On update: use effective available = current stock + this voucher's existing qty per item
            $qtyErrors = [];
            foreach ($requestedByItem as $itemId => $totalQty) {
                $currentStock = (float) ($availableMap[$itemId] ?? 0);
                $existingInVoucher = (float) ($existingQtyByItem[$itemId] ?? 0);
                $avail = $currentStock + $existingInVoucher;
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

            $kitchenIssue->items()->delete();

            foreach ($request->items as $row) {
                $sub = $subcategories->get($row['item_subcategory_id']);
                $qty = (float) ($row['quantity'] ?? 0);
                $rate = (float) ($row['rate'] ?? 0);
                $avail = (float) ($row['available_quantity'] ?? 0);
                $fallbackDisplay = $existingItemDisplayById[(int) $row['item_subcategory_id']] ?? ['item_name' => '', 'unit' => ''];
                KitchenIssueItem::create([
                    'kitchen_issue_master_pk' => $kitchenIssue->pk,
                    'item_subcategory_id' => $row['item_subcategory_id'],
                    'item_name' => $sub ? ($sub->item_name ?? $sub->name ?? '') : $fallbackDisplay['item_name'],
                    'quantity' => $qty,
                    'available_quantity' => $avail,
                    'return_quantity' => 0,
                    'rate' => $rate,
                    'amount' => $qty * $rate,
                    'unit' => $sub ? ($sub->unit_measurement ?? '') : $fallbackDisplay['unit'],
                ]);
            }

            DB::commit();
            self::bumpSellingVoucherListingCacheEpoch();

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
            $kitchenIssue->items()->delete();
            $kitchenIssue->delete();
            self::bumpSellingVoucherListingCacheEpoch();

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

        $issueYmd = $kitchenIssue->issue_date ? $kitchenIssue->issue_date->format('Y-m-d') : '';

        $items = $kitchenIssue->items->map(function ($item) use ($issueYmd) {
            return [
                'id' => $item->pk,
                'item_name' => $item->item_name ?: ($item->itemSubcategory->item_name ?? '—'),
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit ?? '—',
                'issue_date' => $issueYmd,
                'return_quantity' => (float) ($item->return_quantity ?? 0),
                'return_date' => $item->return_date ? $item->return_date->format('Y-m-d') : '',
            ];
        })->values()->toArray();

        return response()->json([
            'store_name' => $kitchenIssue->resolved_store_name,
            'issue_date' => $issueYmd,
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
            'items.*.return_date' => 'nullable|date|before_or_equal:today',
        ]);

        $itemIds = $kitchenIssue->items->pluck('pk')->toArray();
        $itemsByPk = $kitchenIssue->items->keyBy('pk');

        try {
            DB::beginTransaction();
            foreach ($request->items as $row) {
                $itemPk = (int) $row['id'];
                if (!in_array($itemPk, $itemIds, true)) {
                    continue;
                }
                $item = $itemsByPk->get($itemPk);
                if (!$item) {
                    continue;
                }
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
                        $effectiveIssue = $item->issue_date ?? $kitchenIssue->issue_date;
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
            self::bumpSellingVoucherListingCacheEpoch();

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

        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));

        $records = $query->orderBy('issue_date', 'desc')
            ->paginate($perPage)
            ->withQueryString();

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
            self::bumpSellingVoucherListingCacheEpoch();

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

        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));

        $reportSummary = [
            'total_issues' => (clone $query)->count(),
            'paid_count' => (clone $query)->where('payment_type', 1)->count(),
            'unpaid_count' => (clone $query)->where('payment_type', 0)->count(),
            'total_amount' => (float) KitchenIssueItem::query()
                ->whereIn('kitchen_issue_master_pk', (clone $query)->select('pk'))
                ->sum('amount'),
        ];

        $kitchenIssues = $query->orderBy('issue_date', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $stores = Store::all();

        return view('mess.kitchen-issues.bill-report', compact('kitchenIssues', 'stores', 'reportSummary'));
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
            // IMPORTANT: Use unit price INCLUDING tax so that selling vouchers
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

        return response()->json($items->values());
    }

}
