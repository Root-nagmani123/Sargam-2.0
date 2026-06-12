<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\KitchenIssueMaster;
use App\Models\KitchenIssuePaymentDetail;
use App\Models\Mess\ClientType;
use App\Models\Mess\SellingVoucherDateRangeReport;
use App\Models\Mess\SvDateRangePaymentDetail;
use App\Models\FacultyMaster;
use App\Models\EmployeeMaster;
use App\Models\DepartmentMaster;
use App\Models\CourseMaster;
use App\Models\Notification;
use App\Exports\ProcessMessBillsExport;
use App\Services\NotificationService;
use App\Support\DataTableSearchHelper;
use App\Support\MessBuyerClientFilter;
use App\Support\RedisBackedCache;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Process Mess Bills - displays mess bills for Employee, OT, Course, and Other client types.
 * Shows both Regular Selling Voucher (from kitchen_issue_master) and Selling Voucher with Date Range (from sv_date_range_reports).
 */
class ProcessMessBillsEmployeeController extends Controller
{
    /** Client type slugs used in SellingVoucherDateRangeReport */
    private const ALLOWED_CLIENT_SLUGS = ['employee', 'ot', 'course', 'other'];

    /** Cache grouped bills so DataTables page/sort/search does not re-query the union on every request. */
    private const COMBINED_BILLS_CACHE_TTL_SECONDS = 300;

    /** Redis-backed combined bills cache TTL; store is resolved via {@see RedisBackedCache}. */

    private const COMBINED_BILLS_CACHE_VERSION_KEY = 'process_mess_bills_combined_cache_version';

    /** Max rows returned for print/export (avoids multi‑MB JSON responses). */
    private const PRINT_MAX_ROWS = 500;

    /** Client type constants for KitchenIssueMaster (Employee, OT, Course, Other) */
    private const ALLOWED_KITCHEN_CLIENT_TYPES = [
        KitchenIssueMaster::CLIENT_EMPLOYEE,
        KitchenIssueMaster::CLIENT_OT,
        KitchenIssueMaster::CLIENT_COURSE,
        KitchenIssueMaster::CLIENT_OTHER,
    ];

    /** Process-mess kitchen rows: standard selling voucher + date-range variant stored on kitchen_issue_master */
    private const KITCHEN_MESS_SELLING_ISSUE_TYPES = [
        KitchenIssueMaster::TYPE_SELLING_VOUCHER,
        KitchenIssueMaster::TYPE_SELLING_VOUCHER_DATE_RANGE,
    ];

    /** Per-request memoization for FIFO / allocation (avoids duplicate heavy loads per buyer). */
    private array $combinedBillFinancialsCache = [];

    private array $buyerBillsForAllocationCache = [];

    private array $messCombinedNotificationsByReceiver = [];

    /** First working store per request: redis, then file if Redis extension/server unavailable. */
    private ?string $processMessBillsResolvedCacheStore = null;

    public function index(Request $request)
    {
        // DataTables serverSide sends `draw` on every AJAX request; do not require X-Requested-With.
        $isDataTableRequest = $request->filled('draw');
        $dateFrom = $request->filled('date_from') ? $this->parseDate($request->date_from) : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $this->parseDate($request->date_to) : now()->endOfMonth()->format('Y-m-d');
        $unionCollation = 'utf8mb4_unicode_ci';
        
        // Handle multiselect for client_type (employee/ot/course/other)
        $clientTypeRaw = $request->input('client_type');
        $clientTypes = $this->normalizeFilterArrayValues($clientTypeRaw);
        $clientType = $clientTypes[0] ?? null; // For backward compatibility
        
        // Handle multiselect for client_type_pk
        $clientTypePkRaw = $request->input('client_type_pk');
        $clientTypePks = $this->normalizeFilterArrayValues($clientTypePkRaw);
        $clientTypePk = $clientTypePks[0] ?? null; // For backward compatibility
        
        $buyerNames = $this->normalizeBuyerNames($request->input('buyer_name'));
        $buyerNames = $this->normalizeBuyerNamesToClientIds($buyerNames, $clientTypes, $clientTypePks);
        $buyerName = $buyerNames[0] ?? null;
        $statusFilter = $request->filled('status') ? $request->status : null;
        $invoiceSentFilter = $this->resolveInvoiceSentFilter($request);

        // Query 1: Selling Voucher with Date Range (sv_date_range_reports)
        $dateRangeQuery = SellingVoucherDateRangeReport::query()
            ->select([
                'id',
                DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
                'issue_date',
                DB::raw("CONVERT(client_type_slug USING utf8mb4) COLLATE {$unionCollation} as client_type_slug"),
                'client_type_pk',
                'total_amount',
                'payment_type',
                'status',
                'store_id',
                DB::raw("CONVERT('date_range' USING utf8mb4) COLLATE {$unionCollation} as source_type")
            ])
            ->whereIn('client_type_slug', self::ALLOWED_CLIENT_SLUGS);

        if (!empty($clientTypes)) {
            $dateRangeQuery->whereIn('client_type_slug', $clientTypes);
        }
        if (!empty($clientTypePks)) {
            $dateRangeQuery->whereIn('client_type_pk', $clientTypePks);
        }
        // Match Sale Voucher Report: filter SV date-range vouchers by line item request dates, not header dates only.
        $dateRangeQuery->whereIn('status', $this->sellingVoucherDateRangeReportSaleVoucherStatuses());
        $this->applySellingVoucherDateRangeItemIssueDateFilter($dateRangeQuery, $dateFrom, $dateTo);
        $this->applyBuyerNameFilter($dateRangeQuery, $buyerNames, $clientTypes, $clientTypePks);

        // Query 2: Regular Selling Voucher (kitchen_issue_master)
        $kitchenClientTypes = !empty($clientTypes)
            ? array_map([$this, 'clientTypeSlugToKitchenId'], $clientTypes)
            : self::ALLOWED_KITCHEN_CLIENT_TYPES;

        $kitchenIssueQuery = KitchenIssueMaster::query()
            ->select([
                'pk as id',
                DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
                'issue_date',
                DB::raw("CONVERT((CASE client_type WHEN 1 THEN 'employee' WHEN 2 THEN 'ot' WHEN 3 THEN 'course' WHEN 4 THEN 'other' END) USING utf8mb4) COLLATE {$unionCollation} as client_type_slug"),
                'client_type_pk',
                DB::raw('NULL as total_amount'),
                'payment_type',
                'status',
                'store_id',
                DB::raw("CONVERT('kitchen_issue' USING utf8mb4) COLLATE {$unionCollation} as source_type")
            ])
            ->whereIn('client_type', $kitchenClientTypes)
            ->whereIn('kitchen_issue_type', self::KITCHEN_MESS_SELLING_ISSUE_TYPES)
            ->where('status', '!=', KitchenIssueMaster::STATUS_REJECTED);

        if (!empty($clientTypePks)) {
            $kitchenIssueQuery->whereIn('client_type_pk', $clientTypePks);
        }

        if ($dateFrom) {
            $kitchenIssueQuery->where('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $kitchenIssueQuery->where('issue_date', '<=', $dateTo);
        }
        $this->applyBuyerNameFilter($kitchenIssueQuery, $buyerNames, $clientTypes, $clientTypePks);

        $effectiveDateFrom = $request->filled('date_from') ? $request->date_from : now()->startOfMonth()->format('d-m-Y');
        $effectiveDateTo = $request->filled('date_to') ? $request->date_to : now()->endOfMonth()->format('d-m-Y');
        $effectiveDateFromYmd = $dateFrom;
        $effectiveDateToYmd = $dateTo;

        if ($isDataTableRequest) {
            if ($invoiceSentFilter === 'sent') {
                $sentBuyerAllowlist = $this->getSentInvoiceBuyerAllowlistForDateRange($dateFrom, $dateTo);
                if ($sentBuyerAllowlist === []) {
                    return $this->processMessBillsDatatableResponse(
                        $request,
                        collect(),
                        $effectiveDateFromYmd,
                        $effectiveDateToYmd
                    );
                }
                $this->applySentInvoiceBuyerAllowlistFilter($dateRangeQuery, $kitchenIssueQuery, $sentBuyerAllowlist);
            }

            [$combinedBills] = $this->getCombinedBillsForProcessIndexCached(
                $dateFrom,
                $dateTo,
                $dateRangeQuery,
                $kitchenIssueQuery,
                [
                    'client_types' => $clientTypes,
                    'client_type_pks' => $clientTypePks,
                    'buyer_names' => $buyerNames,
                    'status_filter' => $statusFilter,
                    'invoice_sent_filter' => $invoiceSentFilter,
                ]
            );

            if ($statusFilter !== null && $statusFilter !== '') {
                $statusMap = [
                    'unpaid' => 0,
                    'partial' => 1,
                    'paid' => 2,
                    0 => 0,
                    1 => 1,
                    2 => 2,
                ];
                $normalized = $statusMap[$statusFilter] ?? null;
                if ($normalized !== null) {
                    $combinedBills = $combinedBills->where('status', $normalized)->values();
                }
            }

            $combinedBills = $this->filterCombinedBillsByInvoiceSent($combinedBills, $invoiceSentFilter, $dateFrom, $dateTo);

            return $this->processMessBillsDatatableResponse(
                $request,
                $combinedBills,
                $effectiveDateFromYmd,
                $effectiveDateToYmd
            );
        }

        [
            $otBuyerNames,
            $courseBuyerNames,
            $otherBuyerNames,
            $sectionBuyerNames,
            $allBuyerNames,
        ] = $this->processMessBillsBuyerNameCollectionsFromUnion($dateRangeQuery, $kitchenIssueQuery);

        // Summary cards: populated after the first DataTables AJAX response (see JSON `stats`).
        $stats = [
            'total_bills' => 0,
            'paid_count' => 0,
            'unpaid_count' => 0,
            'total_amount' => 0.0,
            'total_due_amount' => 0.0,
        ];

        $combinedBills = collect();

        // Filters for Client Type / Buyer dropdowns (reuse Sale Voucher Report logic)
        $clientTypes = ClientType::clientTypes();
        $clientTypeCategories = ClientType::active()
            ->orderBy('client_type')
            ->orderBy('client_name')
            ->get()
            ->groupBy('client_type');

        $faculties = FacultyMaster::whereNotNull('full_name')
            ->where('full_name', '!=', '')
            ->where('faculty_type', 1)
            ->whereNotNull('employee_master_pk')
            ->whereIn('employee_master_pk', EmployeeMaster::active()->select('pk'))
            ->orderBy('full_name')
            ->get(['pk', 'full_name', 'faculty_code']);

        $departmentNamesByPk = DepartmentMaster::pluck('department_name', 'pk');
        $buildEmployeeLabel = function ($fullName, $departmentPk) use ($departmentNamesByPk) {
            $fullName = trim((string) $fullName);
            if ($fullName === '') {
                $fullName = '—';
            }
            $departmentName = trim((string) ($departmentNamesByPk[$departmentPk] ?? ''));
            return $departmentName !== '' ? ($fullName . ' (' . $departmentName . ')') : $fullName;
        };

        $employees = EmployeeMaster::when(Schema::hasColumn('employee_master', 'status'), fn($q) => $q->where('status', 1))
            ->orderBy('first_name')
            ->orderBy('last_name')
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
            ? EmployeeMaster::when(Schema::hasColumn('employee_master', 'status'), fn($q) => $q->where('status', 1))
                ->where('department_master_pk', $officersMessDept->pk)
                ->orderBy('first_name')
                ->orderBy('last_name')
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

        $filterEmployeeBuyerOptions = MessBuyerClientFilter::employeeBuyerOptions($employees);
        $filterFacultyBuyerOptions = MessBuyerClientFilter::facultyBuyerOptions($faculties);
        $filterMessStaffBuyerOptions = MessBuyerClientFilter::employeeBuyerOptions($messStaff);

        $otCourses = CourseMaster::where('active_inactive', 1)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);

        return view('admin.mess.process-mess-bills-employee.index', compact(
            'combinedBills',
            'effectiveDateFrom',
            'effectiveDateTo',
            'effectiveDateFromYmd',
            'effectiveDateToYmd',
            'stats',
            'clientType',
            'clientTypes',
            'statusFilter',
            'invoiceSentFilter',
            'clientTypePk',
            'clientTypePks',
            'buyerName',
            'buyerNames',
            'clientTypes',
            'clientTypeCategories',
            'faculties',
            'employees',
            'messStaff',
            'otCourses',
            'otBuyerNames',
            'courseBuyerNames',
            'otherBuyerNames',
            'sectionBuyerNames',
            'allBuyerNames',
            'filterEmployeeBuyerOptions',
            'filterFacultyBuyerOptions',
            'filterMessStaffBuyerOptions'
        ));
    }

    private function processMessBillsDatatableResponse(
        Request $request,
        Collection $combinedBills,
        string $effectiveDateFromYmd,
        string $effectiveDateToYmd
    ) {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $forPrint = $request->boolean('for_print') || $request->input('for_print') === '1';

        $recordsTotal = $combinedBills->count();

        $searchRaw = '';
        $searchPayload = $request->input('search');
        if (is_array($searchPayload) && isset($searchPayload['value'])) {
            $searchRaw = (string) $searchPayload['value'];
        }

        $searchTokens = DataTableSearchHelper::tokens($searchRaw);

        $filteredBills = $combinedBills;
        if ($searchTokens !== []) {
            $filteredBills = $filteredBills->filter(function ($cb) use ($searchTokens) {
                $statusLabel = ((int) ($cb->status ?? 0)) === 2
                    ? 'paid'
                    : (((int) ($cb->status ?? 0)) === 1 ? 'partial' : 'unpaid');

                $haystack = implode(' ', [
                    (string) ($cb->buyer_name ?? ''),
                    (string) ($cb->combined_invoice_no ?? ''),
                    (string) ($cb->invoice_date_range ?? ''),
                    (string) ($cb->client_type_display ?? ''),
                    (string) ($cb->payment_type ?? ''),
                    (string) number_format((float) ($cb->total ?? 0), 2, '.', ''),
                    (string) number_format((float) ($cb->total_due_amount ?? 0), 2, '.', ''),
                    $statusLabel,
                ]);

                return DataTableSearchHelper::haystackMatchesAllTokens($haystack, $searchTokens);
            })->values();
        }

        $recordsFiltered = $filteredBills->count();

        if ($forPrint) {
            $start = 0;
            $length = min(max(1, $recordsFiltered), self::PRINT_MAX_ROWS);
        } elseif ($length < 1 || $length > 100) {
            $length = 10;
        }

        $orderColumn = (int) $request->input('order.0.column', 1);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortMap = [
            1 => 'buyer_name',
            2 => 'combined_invoice_no',
            3 => 'invoice_date_range',
            4 => 'client_type_display',
            5 => 'total',
            6 => 'total_due_amount',
            7 => 'payment_type',
            8 => 'status',
        ];
        if ($orderColumn === 0) {
            $filteredBills = $orderDir === 'desc'
                ? $filteredBills->reverse()->values()
                : $filteredBills->values();
        } elseif (isset($sortMap[$orderColumn])) {
            $field = $sortMap[$orderColumn];
            $filteredBills = $filteredBills->sortBy(function ($cb) use ($field) {
                $value = $cb->{$field} ?? '';
                if (in_array($field, ['total', 'total_due_amount', 'status'], true)) {
                    return (float) $value;
                }

                return mb_strtolower((string) $value);
            }, SORT_REGULAR, $orderDir === 'desc')->values();
        }

        $statsPayload = [
            'total_bills' => $combinedBills->count(),
            'paid_count' => $combinedBills->where('status', 2)->count(),
            'unpaid_count' => $combinedBills->count() - $combinedBills->where('status', 2)->count(),
            'total_amount' => (float) $combinedBills->sum('total'),
            'total_due_amount' => (float) $combinedBills->sum('total_due_amount'),
        ];

        $rows = $filteredBills->slice($start, $length)->values();
        $data = [];
        foreach ($rows as $idx => $cb) {
            $status = (int) ($cb->status ?? 0);
            if ($status === 2) {
                $statusBadge = '<span class="badge rounded-pill text-bg-success shadow-sm px-3 py-2">✓ Paid</span>';
            } elseif ($status === 1) {
                $statusBadge = '<span class="badge rounded-pill text-bg-warning text-dark shadow-sm px-3 py-2">⏱ Partial</span>';
            } else {
                $statusBadge = '<span class="badge rounded-pill text-bg-secondary shadow-sm px-3 py-2">○ Unpaid</span>';
            }

            $row = [
                (string) ($start + $idx + 1),
                e((string) ($cb->buyer_name ?? '—')),
                e((string) ($cb->combined_invoice_no ?? '—')),
                e((string) ($cb->invoice_date_range ?? '—')),
                e((string) ($cb->client_type_display ?? '—')),
                '₹ ' . number_format((float) ($cb->total ?? 0), 2),
                '₹ ' . number_format((float) ($cb->total_due_amount ?? 0), 2),
                e((string) ($cb->payment_type ?? '—')),
                $statusBadge,
            ];

            if (! $forPrint) {
                $receiptUrl = route('admin.mess.process-mess-bills-employee.print-receipt', ['id' => $cb->combined_id])
                    . '?date_from=' . rawurlencode($effectiveDateFromYmd)
                    . '&date_to=' . rawurlencode($effectiveDateToYmd);

                $row[] = '<a href="' . e($receiptUrl) . '" target="_blank"'
                    . ' class="btn btn-sm btn-outline-primary shadow-sm d-inline-flex align-items-center justify-content-center gap-1 px-3"'
                    . ' title="Print receipt (' . e((string) ($cb->combined_invoice_no ?? 'Invoice')) . ')">'
                    . '<i class="material-symbols-rounded" style="font-size: 1.1rem;">receipt</i>'
                    . '<span class="d-none d-sm-inline">Receipt</span>'
                    . '</a>';
            }

            $data[] = $row;
        }

        $payload = [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];

        if (! $forPrint) {
            $payload['stats'] = $statsPayload;
        } elseif ($recordsFiltered > self::PRINT_MAX_ROWS) {
            $payload['print_truncated'] = true;
            $payload['print_max_rows'] = self::PRINT_MAX_ROWS;
        }

        return response()->json($payload);
    }

    /**
     * @return list<string>
     */
    private function processMessBillsCacheStoreNames(): array
    {
        $primary = RedisBackedCache::projectDefaultStoreName();
        $stores = [];
        if ($primary === 'redis' && ! extension_loaded('redis')) {
            if (array_key_exists('file', config('cache.stores', []))) {
                $stores[] = 'file';
            }
            $stores[] = $primary;
        } else {
            $stores[] = $primary;
            if ($primary !== 'file' && array_key_exists('file', config('cache.stores', []))) {
                $stores[] = 'file';
            }
        }

        return array_values(array_unique($stores));
    }

    private function processMessBillsCacheRepository(): Repository
    {
        if ($this->processMessBillsResolvedCacheStore !== null) {
            return RedisBackedCache::repositoryForStore($this->processMessBillsResolvedCacheStore);
        }

        foreach ($this->processMessBillsCacheStoreNames() as $storeName) {
            try {
                $repo = RedisBackedCache::repositoryForStore($storeName);
                $repo->put('process_mess_bills_cache_probe', 1, 10);
                $this->processMessBillsResolvedCacheStore = $storeName;

                return $repo;
            } catch (\Throwable $e) {
                continue;
            }
        }

        $this->processMessBillsResolvedCacheStore = (string) config('cache.default', 'file');

        return RedisBackedCache::repositoryForStore($this->processMessBillsResolvedCacheStore);
    }

    /**
     * @param  callable(): mixed  $callback
     * @return mixed
     */
    private function rememberProcessMessBillsCombined(string $cacheKey, callable $callback)
    {
        $ttl = max(30, self::COMBINED_BILLS_CACHE_TTL_SECONDS);

        try {
            return $this->processMessBillsCacheRepository()->remember($cacheKey, $ttl, $callback);
        } catch (\Throwable $e) {
            Log::warning('ProcessMessBillsEmployeeController@combinedBills: cache store failed, using DB only.', [
                'store' => $this->processMessBillsResolvedCacheStore,
                'message' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    private function processMessBillsCombinedCacheVersion(): int
    {
        $version = 1;
        foreach ($this->processMessBillsCacheStoreNames() as $storeName) {
            try {
                $v = (int) RedisBackedCache::repositoryForStore($storeName)->get(self::COMBINED_BILLS_CACHE_VERSION_KEY, 0);
                if ($v > $version) {
                    $version = $v;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $version > 0 ? $version : 1;
    }

    private function bumpProcessMessBillsCombinedCache(): void
    {
        foreach ($this->processMessBillsCacheStoreNames() as $storeName) {
            try {
                $repo = RedisBackedCache::repositoryForStore($storeName);
                if (! $repo->has(self::COMBINED_BILLS_CACHE_VERSION_KEY)) {
                    $repo->put(self::COMBINED_BILLS_CACHE_VERSION_KEY, 2, self::COMBINED_BILLS_CACHE_TTL_SECONDS * 10);

                    continue;
                }
                $repo->increment(self::COMBINED_BILLS_CACHE_VERSION_KEY);
            } catch (\Throwable $e) {
                Log::warning('ProcessMessBillsEmployeeController: failed to bump combined bills cache version.', [
                    'store' => $storeName,
                    'message' => $e->getMessage(),
                ]);
            }
        }
        $this->processMessBillsResolvedCacheStore = null;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function combinedBillsCacheKey(string $dateFrom, string $dateTo, array $filters): string
    {
        return 'process_mess_bills_combined_v8:'
            . $this->processMessBillsCombinedCacheVersion()
            . ':'
            . md5(json_encode([
                'from' => $dateFrom,
                'to' => $dateTo,
                'client_types' => $filters['client_types'] ?? [],
                'client_type_pks' => $filters['client_type_pks'] ?? [],
                'buyer_names' => $filters['buyer_names'] ?? [],
                'status_filter' => $filters['status_filter'] ?? null,
                'invoice_sent_filter' => $filters['invoice_sent_filter'] ?? null,
                'context' => $filters['context'] ?? 'index',
            ]));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: \Illuminate\Support\Collection}
     */
    private function getCombinedBillsForProcessIndexCached(
        string $dateFrom,
        string $dateTo,
        $dateRangeQuery,
        $kitchenIssueQuery,
        array $filters
    ): array {
        $cacheKey = $this->combinedBillsCacheKey($dateFrom, $dateTo, $filters);

        $combinedBills = $this->rememberProcessMessBillsCombined(
            $cacheKey,
            function () use ($dateFrom, $dateTo, $dateRangeQuery, $kitchenIssueQuery) {
                $combined = $this->queryAndGroupBillsForProcessIndexLight(
                    $dateFrom,
                    $dateTo,
                    $dateRangeQuery,
                    $kitchenIssueQuery
                )[0];

                return $this->enrichProcessIndexCombinedBillsLifetimeDue($combined, $dateTo);
            }
        );

        if ($combinedBills instanceof Collection) {
            return [$combinedBills];
        }

        return [collect($combinedBills)];
    }

    /**
     * @param  array<int, string>  $clientTypes
     * @param  array<int, string|int>  $clientTypePks
     * @param  array<int, string>  $buyerNames
     */
    private function modalBillsDatasetCacheKey(
        string $dateFrom,
        string $dateTo,
        array $clientTypes,
        array $clientTypePks,
        array $buyerNames
    ): string {
        return 'process_mess_bills_modal_dataset_v3:'
            . $this->processMessBillsCombinedCacheVersion()
            . ':'
            . md5(json_encode([
                'from' => $dateFrom,
                'to' => $dateTo,
                'client_types' => $clientTypes,
                'client_type_pks' => $clientTypePks,
                'buyer_names' => $buyerNames,
            ]));
    }

    /**
     * Cached modal rows (due > 0, notification status included). Pagination/search/sort are in-memory only.
     *
     * @param  array<int, string>  $clientTypes
     * @param  array<int, string|int>  $clientTypePks
     * @param  array<int, string>  $buyerNames
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function getModalBillsDatasetCached(
        string $dateFrom,
        string $dateTo,
        $dateRangeQuery,
        $kitchenIssueQuery,
        array $clientTypes,
        array $clientTypePks,
        array $buyerNames
    ): Collection {
        $cacheKey = $this->modalBillsDatasetCacheKey($dateFrom, $dateTo, $clientTypes, $clientTypePks, $buyerNames);

        $rows = $this->rememberProcessMessBillsCombined(
            $cacheKey,
            function () use ($dateFrom, $dateTo, $dateRangeQuery, $kitchenIssueQuery) {
                return $this->buildModalBillsDatasetUncached(
                    $dateFrom,
                    $dateTo,
                    $dateRangeQuery,
                    $kitchenIssueQuery
                );
            }
        );

        if ($rows instanceof Collection) {
            return $rows;
        }

        return collect(is_array($rows) ? $rows : []);
    }

    /**
     * Build modal listing once (no lifetime FIFO here; no nested combined-bills cache).
     *
     * @return list<array<string, mixed>>
     */
    private function buildModalBillsDatasetUncached(
        string $dateFrom,
        string $dateTo,
        $dateRangeQuery,
        $kitchenIssueQuery
    ): array {
        [$groupedRows] = $this->queryAndGroupBillsForProcessIndexLight(
            $dateFrom,
            $dateTo,
            $dateRangeQuery,
            $kitchenIssueQuery
        );

        $groupedRows = $groupedRows->filter(function ($row) {
            return trim((string) ($row->buyer_name ?? '')) !== ''
                && (float) ($row->due ?? 0) > 0;
        })->values();

        $receiverIds = [];
        foreach ($groupedRows as $cb) {
            $receiverId = (int) ($cb->receiver_user_id ?? 0);
            if ($receiverId > 0) {
                $receiverIds[$receiverId] = true;
            }
        }
        $this->preloadMessCombinedNotificationsForReceivers(array_keys($receiverIds));

        return $groupedRows
            ->map(fn ($cb) => $this->buildModalBillCachedRow($cb, $dateFrom, $dateTo, []))
            ->values()
            ->all();
    }

    /**
     * Lifetime FIFO due for the current modal page only (not all buyers).
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $pageRows
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function enrichModalPageRowsLifetimeDue(Collection $pageRows, string $dateToYmd): Collection
    {
        if ($pageRows->isEmpty()) {
            return $pageRows;
        }

        $buyerKeys = $pageRows
            ->map(function (array $row) {
                return [
                    'name' => trim((string) ($row['buyer_name'] ?? '')),
                    'slug' => (string) ($row['client_type_slug'] ?? 'employee'),
                ];
            })
            ->filter(fn (array $b) => $b['name'] !== '' && $b['name'] !== '—')
            ->unique(fn (array $b) => $b['name'] . '|' . $b['slug'])
            ->values();

        $this->preloadBuyerBillsForPaymentAllocationBatch($buyerKeys, $dateToYmd);

        return $pageRows->map(function (array $row) use ($dateToYmd) {
            $buyerName = trim((string) ($row['buyer_name'] ?? ''));
            if ($buyerName === '' || $buyerName === '—') {
                return $row;
            }
            $clientTypeSlug = (string) ($row['client_type_slug'] ?? 'employee');
            $row['total_due_amount'] = $this->computeCombinedBillFinancials($buyerName, $clientTypeSlug, null, $dateToYmd)['due'];

            return $row;
        });
    }

    /**
     * @param  array<string, array{read: bool, fully_sent: bool, partial: bool, pending_count: int}>  $notificationSentKeys
     * @return array<string, mixed>
     */
    private function buildModalBillCachedRow(
        object $cb,
        string $dateFrom,
        string $dateTo,
        array $notificationSentKeys
    ): array {
        $invoiceNo = (string) ($cb->combined_invoice_no ?? '');
        if ($invoiceNo === '') {
            $invoiceNo = $this->generateCombinedInvoiceNo(
                trim((string) ($cb->buyer_name ?? '')),
                (string) ($cb->client_type_slug ?? 'employee')
            );
        }

        $receiverId = (int) ($cb->receiver_user_id ?? 0);

        $notificationStatus = [
            'sent' => false,
            'fully_sent' => false,
            'partial' => false,
            'pending_count' => 0,
            'read' => false,
        ];
        if ($receiverId > 0) {
            $mapKey = $receiverId . '|' . (string) ($cb->combined_id ?? '');
            if (isset($notificationSentKeys[$mapKey])) {
                $n = $notificationSentKeys[$mapKey];
                $notificationStatus = [
                    'sent' => true,
                    'fully_sent' => (bool) ($n['fully_sent'] ?? false),
                    'partial' => (bool) ($n['partial'] ?? false),
                    'pending_count' => (int) ($n['pending_count'] ?? 0),
                    'read' => (bool) ($n['read'] ?? false),
                ];
            } else {
                $precomputedKeys = isset($cb->line_item_keys) && is_array($cb->line_item_keys)
                    ? $cb->line_item_keys
                    : [];
                $notificationStatus = $this->resolveMessCombinedInvoiceNotificationStatus(
                    $receiverId,
                    (string) ($cb->combined_id ?? ''),
                    $dateFrom,
                    $dateTo,
                    [],
                    $precomputedKeys
                );
            }
        }

        $periodDue = (float) ($cb->due ?? 0);

        return [
            'id' => (string) ($cb->combined_id ?? ''),
            'bill_id' => (string) ($cb->combined_id ?? ''),
            'slip_no' => $invoiceNo,
            'buyer_name' => (string) ($cb->buyer_name ?? ''),
            'client_type_slug' => (string) ($cb->client_type_slug ?? 'employee'),
            'invoice_no' => $invoiceNo,
            'payment_type' => (string) ($cb->payment_type ?? '—'),
            'total' => (float) ($cb->total ?? 0),
            'due' => (float) $periodDue,
            'paid' => (float) ($cb->paid ?? 0),
            'total_due_amount' => $periodDue,
            'bill_no' => $invoiceNo,
            'invoice_notification_sent' => $notificationStatus['sent'],
            'invoice_notification_fully_sent' => $notificationStatus['fully_sent'],
            'invoice_notification_partial' => $notificationStatus['partial'],
            'invoice_notification_pending_count' => $notificationStatus['pending_count'],
            'invoice_notification_read' => $notificationStatus['read'],
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatModalBillRowForJson(array $row, int $sno): array
    {
        return [
            'id' => $row['id'],
            'bill_id' => $row['bill_id'],
            'slip_no' => $row['slip_no'],
            'sno' => $sno,
            'buyer_name' => $row['buyer_name'],
            'invoice_no' => $row['invoice_no'],
            'payment_type' => $row['payment_type'],
            'total' => number_format((float) ($row['total'] ?? 0), 2),
            'due_amount' => number_format((float) ($row['due'] ?? 0), 2),
            'total_due_amount' => number_format((float) ($row['total_due_amount'] ?? 0), 2),
            'paid_amount' => number_format((float) ($row['paid'] ?? 0), 2),
            'bill_no' => $row['bill_no'],
            'invoice_notification_sent' => (bool) ($row['invoice_notification_sent'] ?? false),
            'invoice_notification_fully_sent' => (bool) ($row['invoice_notification_fully_sent'] ?? false),
            'invoice_notification_partial' => (bool) ($row['invoice_notification_partial'] ?? false),
            'invoice_notification_pending_count' => (int) ($row['invoice_notification_pending_count'] ?? 0),
            'invoice_notification_read' => (bool) ($row['invoice_notification_read'] ?? false),
            'date_from' => $row['date_from'],
            'date_to' => $row['date_to'],
        ];
    }

    /**
     * Distinct buyer names from the union (no model hydration) for filter dropdowns on full page load.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: \Illuminate\Support\Collection, 3: \Illuminate\Support\Collection, 4: \Illuminate\Support\Collection}
     */
    private function processMessBillsBuyerNameCollectionsFromUnion($dateRangeQuery, $kitchenIssueQuery): array
    {
        $unionQuery = $dateRangeQuery->union($kitchenIssueQuery);
        $rows = DB::table(DB::raw("({$unionQuery->toSql()}) as combined_bills"))
            ->mergeBindings($unionQuery->getQuery())
            ->select('client_name', 'client_type_slug')
            ->distinct()
            ->orderBy('client_name')
            ->get();

        $bySlug = $rows->groupBy('client_type_slug');
        $namesForSlug = function (string $slug) use ($bySlug): \Illuminate\Support\Collection {
            $group = $bySlug->get($slug);
            if ($group === null) {
                return collect();
            }

            return $group->pluck('client_name')->map(fn ($n) => trim((string) $n))->filter()->unique()->sort()->values();
        };

        $allBuyerNames = $rows->pluck('client_name')->map(fn ($n) => trim((string) $n))->filter()->unique()->sort()->values();

        return [
            $namesForSlug(ClientType::TYPE_OT),
            $namesForSlug(ClientType::TYPE_COURSE),
            $namesForSlug(ClientType::TYPE_OTHER),
            $namesForSlug(ClientType::TYPE_SECTION),
            $allBuyerNames,
        ];
    }

    /**
     * Self-service: current user's combined mess bills for the selected period (same date logic as Process Mess Bills).
     */
    public function myBillsIndex(Request $request)
    {
        abort_unless(\canSeeMessSelfServiceSetup(), 403);

        $dateFrom = $request->filled('date_from') ? $this->parseDate($request->date_from) : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $this->parseDate($request->date_to) : now()->endOfMonth()->format('Y-m-d');
        $unionCollation = 'utf8mb4_unicode_ci';

        $authUid = (int) (auth()->user()->user_id ?? 0);
        $authLinkedUserIds = $this->authLinkedUserIdsForMessSelfService();
        if ($authUid <= 0) {
            $combinedBills = collect();
            $effectiveDateFrom = $request->filled('date_from') ? $request->date_from : now()->startOfMonth()->format('d-m-Y');
            $effectiveDateTo = $request->filled('date_to') ? $request->date_to : now()->endOfMonth()->format('d-m-Y');
            $effectiveDateFromYmd = $dateFrom;
            $effectiveDateToYmd = $dateTo;
            $stats = [
                'total_bills' => 0,
                'paid_count' => 0,
                'unpaid_count' => 0,
                'total_amount' => 0.0,
            ];

            return view('mess.my-bills.index', compact(
                'combinedBills',
                'effectiveDateFrom',
                'effectiveDateTo',
                'effectiveDateFromYmd',
                'effectiveDateToYmd',
                'stats'
            ));
        }

        $nameCandidates = $this->messBillBuyerNameCandidatesForCurrentUser();
        $nameLikePatterns = $this->buildMessSelfServiceClientNameLikePatterns($nameCandidates);

        $dateRangeQuery = SellingVoucherDateRangeReport::query()
            ->select([
                'id',
                DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
                'issue_date',
                DB::raw("CONVERT(client_type_slug USING utf8mb4) COLLATE {$unionCollation} as client_type_slug"),
                'client_type_pk',
                'total_amount',
                'payment_type',
                'status',
                'store_id',
                DB::raw("CONVERT('date_range' USING utf8mb4) COLLATE {$unionCollation} as source_type"),
            ])
            ->whereIn('client_type_slug', self::ALLOWED_CLIENT_SLUGS);
        $dateRangeQuery->whereIn('status', $this->sellingVoucherDateRangeReportSaleVoucherStatuses());
        $this->applySellingVoucherDateRangeItemIssueDateFilter($dateRangeQuery, $dateFrom, $dateTo);
        $this->applyMyBillsClientNameOrStubFalse($dateRangeQuery, $nameLikePatterns);

        $kitchenIssueQuery = KitchenIssueMaster::query()
            ->select([
                'pk as id',
                DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
                'issue_date',
                DB::raw("CONVERT((CASE client_type WHEN 1 THEN 'employee' WHEN 2 THEN 'ot' WHEN 3 THEN 'course' WHEN 4 THEN 'other' END) USING utf8mb4) COLLATE {$unionCollation} as client_type_slug"),
                'client_type_pk',
                DB::raw('NULL as total_amount'),
                'payment_type',
                'status',
                'store_id',
                DB::raw("CONVERT('kitchen_issue' USING utf8mb4) COLLATE {$unionCollation} as source_type"),
            ])
            ->whereIn('client_type', self::ALLOWED_KITCHEN_CLIENT_TYPES)
            ->whereIn('kitchen_issue_type', self::KITCHEN_MESS_SELLING_ISSUE_TYPES);
        if ($dateFrom) {
            $kitchenIssueQuery->where('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $kitchenIssueQuery->where('issue_date', '<=', $dateTo);
        }
        $this->applyMyBillsKitchenClientNameOrClientId($kitchenIssueQuery, $nameLikePatterns, $authLinkedUserIds);

        [$combinedBills, $unusedBills] = $this->queryAndGroupBillsForProcessIndex(
            $dateFrom,
            $dateTo,
            $dateRangeQuery,
            $kitchenIssueQuery
        );

        $combinedBills = $combinedBills
            ->filter(function ($cb) use ($authLinkedUserIds) {
                $rid = $this->resolveReceiverUserIdFromAnyBill($cb->bills->all());
                return $rid !== null && in_array((int) $rid, $authLinkedUserIds, true);
            })
            ->values();

        $effectiveDateFrom = $request->filled('date_from') ? $request->date_from : now()->startOfMonth()->format('d-m-Y');
        $effectiveDateTo = $request->filled('date_to') ? $request->date_to : now()->endOfMonth()->format('d-m-Y');
        $effectiveDateFromYmd = $dateFrom;
        $effectiveDateToYmd = $dateTo;

        $stats = [
            'total_bills' => $combinedBills->count(),
            'paid_count' => $combinedBills->where('status', 2)->count(),
            'unpaid_count' => $combinedBills->count() - $combinedBills->where('status', 2)->count(),
            'total_amount' => (float) $combinedBills->sum('total'),
        ];

        return view('mess.my-bills.index', compact(
            'combinedBills',
            'effectiveDateFrom',
            'effectiveDateTo',
            'effectiveDateFromYmd',
            'effectiveDateToYmd',
            'stats'
        ));
    }

    /**
     * Union SV date-range + kitchen rows, hydrate models, group into one combined bill per buyer+type.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    private function queryAndGroupBillsForProcessIndex(
        string $dateFrom,
        string $dateTo,
        $dateRangeQuery,
        $kitchenIssueQuery
    ): array {
        $unionQuery = $dateRangeQuery->union($kitchenIssueQuery);
        $rows = DB::table(DB::raw("({$unionQuery->toSql()}) as combined_bills"))
            ->mergeBindings($unionQuery->getQuery())
            ->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5000)
            ->get();

        $drIds = [];
        $kiIds = [];
        foreach ($rows as $bill) {
            if (($bill->source_type ?? '') === 'date_range') {
                $drIds[] = (int) $bill->id;
            } else {
                $kiIds[] = (int) $bill->id;
            }
        }
        $drIds = array_values(array_unique(array_filter($drIds)));
        $kiIds = array_values(array_unique(array_filter($kiIds)));

        $drModels = collect();
        if ($drIds !== []) {
            $drModels = SellingVoucherDateRangeReport::with([
                'store',
                'clientTypeCategory',
                'items' => function ($itemQ) use ($dateFrom, $dateTo) {
                    $this->applySvDateRangeReportItemsIssueDateConstraint($itemQ, $dateFrom, $dateTo);
                },
                'items.itemSubcategory',
            ])->whereIn('id', $drIds)->get()->keyBy('id');
        }

        $kiModels = collect();
        if ($kiIds !== []) {
            $kiModels = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items'])
                ->whereIn('pk', $kiIds)
                ->get()
                ->keyBy('pk');
        }

        $bills = $rows->map(function ($bill) use ($drModels, $kiModels) {
            if (($bill->source_type ?? '') === 'date_range') {
                $model = $drModels->get((int) $bill->id);
                if ($model) {
                    $model->setAttribute('source_type', 'date_range');
                }

                return $model;
            }
            $model = $kiModels->get((int) $bill->id);
            if ($model) {
                $model->setAttribute('source_type', 'kitchen_issue');
            }

            return $model;
        })->filter()->values();

        $combinedBills = $this->groupBillsByBuyer($bills, $dateFrom, $dateTo);

        return [$combinedBills, $bills];
    }

    /**
     * Same filters as queryAndGroupBillsForProcessIndex, but aggregates net/paid totals in SQL
     * and skips loading line items — used for index DataTables cache and export.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    private function queryAndGroupBillsForProcessIndexLight(
        string $dateFrom,
        string $dateTo,
        $dateRangeQuery,
        $kitchenIssueQuery
    ): array {
        $unionQuery = $dateRangeQuery->union($kitchenIssueQuery);
        $rows = DB::table(DB::raw("({$unionQuery->toSql()}) as combined_bills"))
            ->mergeBindings($unionQuery->getQuery())
            ->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5000)
            ->get();

        $drIds = [];
        $kiIds = [];
        foreach ($rows as $bill) {
            if (($bill->source_type ?? '') === 'date_range') {
                $drIds[] = (int) $bill->id;
            } else {
                $kiIds[] = (int) $bill->id;
            }
        }
        $drIds = array_values(array_unique(array_filter($drIds)));
        $kiIds = array_values(array_unique(array_filter($kiIds)));

        $svNetTotals = $this->fetchProcessIndexSvNetTotalsByReportIds($drIds, $dateFrom, $dateTo);
        $svItemDateExtents = $this->fetchProcessIndexSvItemDateExtentsByReportIds($drIds, $dateFrom, $dateTo);
        $svHeaders = $drIds !== []
            ? SellingVoucherDateRangeReport::query()
                ->whereIn('id', $drIds)
                ->get(['id', 'client_name', 'client_id', 'client_type_slug', 'client_type_pk', 'payment_type', 'paid_amount', 'issue_date'])
                ->keyBy('id')
            : collect();

        $kiNetTotals = $this->fetchProcessIndexKitchenNetTotalsByPk($kiIds);
        $kiPaidTotals = $this->fetchProcessIndexKitchenPaidTotalsByPk($kiIds);
        $kiHeaders = $kiIds !== []
            ? KitchenIssueMaster::query()
                ->whereIn('pk', $kiIds)
                ->get(['pk', 'client_name', 'client_type', 'client_type_pk', 'client_id', 'payment_type', 'issue_date'])
                ->keyBy('pk')
            : collect();

        $clientTypePks = $svHeaders->pluck('client_type_pk')
            ->merge($kiHeaders->pluck('client_type_pk'))
            ->map(fn ($pk) => (int) $pk)
            ->filter(fn ($pk) => $pk > 0)
            ->unique()
            ->values()
            ->all();
        $clientTypeCategories = $clientTypePks !== []
            ? ClientType::query()->whereIn('id', $clientTypePks)->get(['id', 'client_type', 'client_name'])->keyBy('id')
            : collect();

        $voucherStubs = $rows->map(function ($row) use (
            $svHeaders,
            $svNetTotals,
            $svItemDateExtents,
            $kiHeaders,
            $kiNetTotals,
            $kiPaidTotals,
            $clientTypeCategories
        ) {
            if (($row->source_type ?? '') === 'date_range') {
                $header = $svHeaders->get((int) $row->id);
                if (! $header) {
                    return null;
                }
                $reportId = (int) $header->id;
                $slug = (string) ($header->client_type_slug ?? 'employee');
                $category = $clientTypeCategories->get((int) ($header->client_type_pk ?? 0));

                return (object) [
                    'source_type' => 'date_range',
                    'id' => $reportId,
                    'pk' => null,
                    'client_name' => trim((string) ($header->client_name ?? '')),
                    'client_type_slug' => $slug,
                    'client_type' => null,
                    'client_type_pk' => (int) ($header->client_type_pk ?? 0),
                    'client_id' => isset($header->client_id) ? (int) $header->client_id : null,
                    'payment_type' => $header->payment_type,
                    'issue_date' => $header->issue_date,
                    'net_total' => (float) ($svNetTotals[$reportId] ?? 0),
                    'paid_amount' => (float) ($header->paid_amount ?? 0),
                    'kitchen_paid_total' => 0.0,
                    'item_date_min' => $svItemDateExtents[$reportId]['min'] ?? null,
                    'item_date_max' => $svItemDateExtents[$reportId]['max'] ?? null,
                    'client_type_category' => $category,
                ];
            }

            $header = $kiHeaders->get((int) $row->id);
            if (! $header) {
                return null;
            }
            $pk = (int) $header->pk;
            $clientType = (int) ($header->client_type ?? 0);
            $category = $clientTypeCategories->get((int) ($header->client_type_pk ?? 0));

            return (object) [
                'source_type' => 'kitchen_issue',
                'id' => $pk,
                'pk' => $pk,
                'client_name' => trim((string) ($header->client_name ?? '')),
                'client_type_slug' => $this->kitchenClientTypeIdToSlug($clientType),
                'client_type' => $clientType,
                'client_type_pk' => (int) ($header->client_type_pk ?? 0),
                'client_id' => isset($header->client_id) ? (int) $header->client_id : null,
                'payment_type' => $header->payment_type,
                'issue_date' => $header->issue_date,
                'net_total' => (float) ($kiNetTotals[$pk] ?? 0),
                'paid_amount' => 0.0,
                'kitchen_paid_total' => (float) ($kiPaidTotals[$pk] ?? 0),
                'item_date_min' => null,
                'item_date_max' => null,
                'client_type_category' => $category,
            ];
        })->filter()->values();

        $stubLineKeysMap = $this->batchLineItemKeysByProcessIndexStubKey($voucherStubs);

        return [$this->groupProcessIndexVouchersByBuyer($voucherStubs, $dateTo, true, $stubLineKeysMap), $voucherStubs];
    }

    /**
     * Load all line-item keys for process-index stubs in bulk (avoids per-voucher DB queries during invoice-sent filter).
     *
     * @param  \Illuminate\Support\Collection<int, object>  $stubs
     * @return array<string, array<int, string>>
     */
    private function batchLineItemKeysByProcessIndexStubKey(Collection $stubs): array
    {
        $drIds = [];
        $kiPks = [];
        foreach ($stubs as $stub) {
            if (($stub->source_type ?? '') === 'date_range') {
                $id = (int) ($stub->id ?? 0);
                if ($id > 0) {
                    $drIds[$id] = true;
                }
            } else {
                $pk = (int) ($stub->pk ?? $stub->id ?? 0);
                if ($pk > 0) {
                    $kiPks[$pk] = true;
                }
            }
        }

        $map = [];
        $drIdList = array_keys($drIds);
        $kiPkList = array_keys($kiPks);

        foreach (array_chunk($drIdList, 500) as $chunk) {
            foreach (DB::table('sv_date_range_report_items')
                ->whereIn('sv_date_range_report_id', $chunk)
                ->select('sv_date_range_report_id', 'id')
                ->get() as $row) {
                $reportId = (int) $row->sv_date_range_report_id;
                $itemId = (int) $row->id;
                if ($reportId > 0 && $itemId > 0) {
                    $map['dr-' . $reportId][] = 'dr-' . $itemId;
                }
            }
        }

        foreach (array_chunk($kiPkList, 500) as $chunk) {
            $itemsByMaster = DB::table('kitchen_issue_items')
                ->whereIn('kitchen_issue_master_pk', $chunk)
                ->select('kitchen_issue_master_pk', 'pk')
                ->get()
                ->groupBy('kitchen_issue_master_pk');

            foreach ($chunk as $masterPk) {
                $masterPk = (int) $masterPk;
                $rows = $itemsByMaster->get($masterPk);
                if ($rows !== null && $rows->isNotEmpty()) {
                    foreach ($rows as $row) {
                        $itemPk = (int) $row->pk;
                        if ($itemPk > 0) {
                            $map['ki-' . $masterPk][] = 'ki-' . $itemPk;
                        }
                    }
                } else {
                    $map['ki-' . $masterPk] = ['ki-bill-' . $masterPk];
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<int, SellingVoucherDateRangeReport|KitchenIssueMaster|object>  $bills
     * @param  array<string, array<int, string>>  $stubLineKeysMap
     * @return array<int, string>
     */
    private function collectMessBillLineItemKeysWithStubMap(array $bills, array $stubLineKeysMap): array
    {
        $keys = [];
        foreach ($bills as $b) {
            if ($b instanceof SellingVoucherDateRangeReport) {
                foreach ($b->items ?? [] as $item) {
                    $id = (int) ($item->id ?? 0);
                    if ($id > 0) {
                        $keys[] = 'dr-' . $id;
                    }
                }
            } elseif ($b instanceof KitchenIssueMaster) {
                $items = $b->relationLoaded('items') ? $b->items : collect();
                if ($items->isNotEmpty()) {
                    foreach ($items as $item) {
                        $pk = (int) ($item->pk ?? 0);
                        if ($pk > 0) {
                            $keys[] = 'ki-' . $pk;
                        }
                    }
                } else {
                    $masterPk = (int) ($b->pk ?? 0);
                    if ($masterPk > 0) {
                        $keys[] = 'ki-bill-' . $masterPk;
                    }
                }
            } elseif ($this->isProcessIndexVoucherStub($b)) {
                $stubKey = ($b->source_type ?? '') === 'date_range'
                    ? 'dr-' . (int) ($b->id ?? 0)
                    : 'ki-' . (int) ($b->pk ?? $b->id ?? 0);
                foreach ($stubLineKeysMap[$stubKey] ?? [] as $key) {
                    $keys[] = $key;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Compute lifetime FIFO due for grid rows (batched buyer loads + cached inside combined-bills Redis key).
     *
     * @param  \Illuminate\Support\Collection<int, object>  $combinedBills
     */
    private function enrichProcessIndexCombinedBillsLifetimeDue(Collection $combinedBills, ?string $dateToYmd): Collection
    {
        if (! $dateToYmd || $combinedBills->isEmpty()) {
            return $combinedBills;
        }

        $buyerKeys = $combinedBills
            ->map(function ($cb) {
                $buyerName = trim((string) ($cb->buyer_name ?? ''));

                return [
                    'name' => $buyerName,
                    'slug' => (string) ($cb->client_type_slug ?? 'employee'),
                ];
            })
            ->filter(fn (array $b) => $b['name'] !== '' && $b['name'] !== '—')
            ->unique(fn (array $b) => $b['name'] . '|' . $b['slug'])
            ->values();

        $this->preloadBuyerBillsForPaymentAllocationBatch($buyerKeys, $dateToYmd);

        return $combinedBills->map(function ($cb) use ($dateToYmd) {
            $buyerName = trim((string) ($cb->buyer_name ?? ''));
            if ($buyerName === '' || $buyerName === '—') {
                return $cb;
            }
            $clientTypeSlug = (string) ($cb->client_type_slug ?? 'employee');
            $cb->total_due_amount = $this->computeCombinedBillFinancials($buyerName, $clientTypeSlug, null, $dateToYmd)['due'];

            return $cb;
        });
    }

    /**
     * @param  array<int, int>  $reportIds
     * @return array<int, float>
     */
    private function fetchProcessIndexSvNetTotalsByReportIds(array $reportIds, ?string $dateFromYmd, ?string $dateToYmd): array
    {
        if ($reportIds === []) {
            return [];
        }

        $netExpr = 'SUM(GREATEST(0, COALESCE(quantity, 0) - COALESCE(return_quantity, 0)) * COALESCE(rate, 0))';
        $totals = [];

        foreach (array_chunk($reportIds, 500) as $chunk) {
            $query = DB::table('sv_date_range_report_items')
                ->select('sv_date_range_report_id', DB::raw("{$netExpr} as net_total"))
                ->whereIn('sv_date_range_report_id', $chunk)
                ->groupBy('sv_date_range_report_id');
            $this->applySvDateRangeReportItemsIssueDateConstraint($query, $dateFromYmd, $dateToYmd);

            foreach ($query->get() as $row) {
                $totals[(int) $row->sv_date_range_report_id] = (float) $row->net_total;
            }
        }

        return $totals;
    }

    /**
     * @param  array<int, int>  $reportIds
     * @return array<int, array{min: ?string, max: ?string}>
     */
    private function fetchProcessIndexSvItemDateExtentsByReportIds(array $reportIds, ?string $dateFromYmd, ?string $dateToYmd): array
    {
        if ($reportIds === []) {
            return [];
        }

        $extents = [];

        foreach (array_chunk($reportIds, 500) as $chunk) {
            $query = DB::table('sv_date_range_report_items')
                ->select(
                    'sv_date_range_report_id',
                    DB::raw('MIN(issue_date) as date_min'),
                    DB::raw('MAX(issue_date) as date_max')
                )
                ->whereIn('sv_date_range_report_id', $chunk)
                ->groupBy('sv_date_range_report_id');
            $this->applySvDateRangeReportItemsIssueDateConstraint($query, $dateFromYmd, $dateToYmd);

            foreach ($query->get() as $row) {
                $id = (int) $row->sv_date_range_report_id;
                $extents[$id] = [
                    'min' => $row->date_min ? (string) $row->date_min : null,
                    'max' => $row->date_max ? (string) $row->date_max : null,
                ];
            }
        }

        return $extents;
    }

    /**
     * @param  array<int, int>  $kitchenPks
     * @return array<int, float>
     */
    private function fetchProcessIndexKitchenNetTotalsByPk(array $kitchenPks): array
    {
        if ($kitchenPks === []) {
            return [];
        }

        $netExpr = 'SUM(GREATEST(0, COALESCE(quantity, 0) - COALESCE(return_quantity, 0)) * COALESCE(rate, 0))';
        $totals = [];

        foreach (array_chunk($kitchenPks, 500) as $chunk) {
            foreach (DB::table('kitchen_issue_items')
                ->select('kitchen_issue_master_pk', DB::raw("{$netExpr} as net_total"))
                ->whereIn('kitchen_issue_master_pk', $chunk)
                ->groupBy('kitchen_issue_master_pk')
                ->get() as $row) {
                $totals[(int) $row->kitchen_issue_master_pk] = (float) $row->net_total;
            }
        }

        return $totals;
    }

    /**
     * @param  array<int, int>  $kitchenPks
     * @return array<int, float>
     */
    private function fetchProcessIndexKitchenPaidTotalsByPk(array $kitchenPks): array
    {
        if ($kitchenPks === []) {
            return [];
        }

        $totals = [];

        foreach (array_chunk($kitchenPks, 500) as $chunk) {
            foreach (DB::table('kitchen_issue_payment_details')
                ->select('kitchen_issue_master_pk', DB::raw('SUM(COALESCE(paid_amount, 0)) as paid_total'))
                ->whereIn('kitchen_issue_master_pk', $chunk)
                ->groupBy('kitchen_issue_master_pk')
                ->get() as $row) {
                $totals[(int) $row->kitchen_issue_master_pk] = (float) $row->paid_total;
            }
        }

        return $totals;
    }

    /**
     * Group lightweight voucher stubs (no Eloquent line items) into combined bill rows for the grid.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $vouchers
     */
    private function groupProcessIndexVouchersByBuyer(
        Collection $vouchers,
        ?string $dateToYmd = null,
        bool $deferLifetimeDue = false,
        array $stubLineKeysMap = []
    ): Collection {
        $paymentTypeMap = [0 => 'Cash', 1 => 'Deduct From Salary', 2 => 'Online', 5 => 'Deduct From Salary'];

        return $vouchers->groupBy(fn ($bill) => $this->messBillBuyerGroupKey($bill))
            ->map(function ($group) use ($paymentTypeMap, $dateToYmd, $deferLifetimeDue, $stubLineKeysMap) {
            $first = $group->first();
            $buyerName = $this->resolveMessBillBuyerDisplayName($first, $group);
            $clientTypeSlug = $this->getBillClientTypeSlug($first);
            $combinedId = 'combined-' . rawurlencode($buyerName) . '-' . $clientTypeSlug;
            $combinedInvoiceNo = $this->generateCombinedInvoiceNo($buyerName, $clientTypeSlug);

            $total = $this->roundMoney((float) $group->sum(fn ($b) => (float) ($b->net_total ?? 0)));
            $paid = 0.0;
            foreach ($group as $b) {
                $paid += $this->getBillPaidAmount($b, ($b->source_type ?? '') === 'date_range');
            }
            $paid = $this->roundMoney($paid);
            $due = $this->billDueAmount($total, $paid);
            $totalDueAmount = $deferLifetimeDue
                ? $due
                : (($buyerName !== '' && $buyerName !== '—' && $dateToYmd)
                    ? $this->computeCombinedBillFinancials($buyerName, $clientTypeSlug, null, $dateToYmd)['due']
                    : $due);

            $dateStrings = collect();
            foreach ($group as $b) {
                if (($b->source_type ?? '') === 'date_range') {
                    if (! empty($b->item_date_min)) {
                        $dateStrings->push((string) $b->item_date_min);
                    }
                    if (! empty($b->item_date_max) && $b->item_date_max !== $b->item_date_min) {
                        $dateStrings->push((string) $b->item_date_max);
                    }
                } elseif (! empty($b->issue_date)) {
                    $dateStrings->push($b->issue_date instanceof Carbon
                        ? $b->issue_date->format('Y-m-d')
                        : Carbon::parse($b->issue_date)->format('Y-m-d'));
                }
            }
            $dateStrings = $dateStrings->filter()->unique()->sort()->values();
            $dateMin = $dateStrings->first();
            $dateMax = $dateStrings->last();
            $invoiceDateRange = $dateMin && $dateMax
                ? (Carbon::parse($dateMin)->format('d-m-Y') . ($dateMin !== $dateMax ? ' to ' . Carbon::parse($dateMax)->format('d-m-Y') : ''))
                : '—';

            $allPaid = $group->every(function ($b) {
                $isDr = ($b->source_type ?? '') === 'date_range';

                return $this->isBillFullyPaid(
                    $this->getBillPaidAmount($b, $isDr),
                    (float) ($b->net_total ?? 0)
                );
            });
            $anyPaid = $group->contains(function ($b) {
                $isDr = ($b->source_type ?? '') === 'date_range';

                return $this->getBillPaidAmount($b, $isDr) > 0;
            });
            $status = $allPaid ? 2 : ($anyPaid ? 1 : 0);

            $firstReceipt = $group->first();
            $firstReceiptId = ($firstReceipt->source_type ?? '') === 'date_range'
                ? 'dr-' . ($firstReceipt->id ?? 0)
                : 'ki-' . ($firstReceipt->pk ?? $firstReceipt->id ?? 0);

            $category = $first->client_type_category ?? null;
            $typeLabel = $category
                ? ucfirst((string) ($category->client_type ?? ''))
                : ucfirst($clientTypeSlug);
            $categoryName = $category->client_name ?? null;
            $clientTypeDisplay = ($categoryName !== null && $categoryName !== '')
                ? $typeLabel . '(' . strtoupper((string) $categoryName) . ')'
                : ($typeLabel ?: '—');

            $receiverUserId = $this->resolveReceiverUserIdFromAnyBill($group->all());
            $lineItemKeys = $stubLineKeysMap !== []
                ? $this->collectMessBillLineItemKeysWithStubMap($group->all(), $stubLineKeysMap)
                : $this->collectMessBillLineItemKeys($group->all());

            return (object) [
                'combined_id' => $combinedId,
                'combined_invoice_no' => $combinedInvoiceNo,
                'buyer_name' => $buyerName,
                'client_type_slug' => $clientTypeSlug,
                'client_type_display' => $clientTypeDisplay,
                'invoice_date_range' => $invoiceDateRange,
                'total' => $total,
                'paid' => $paid,
                'due' => $due,
                'total_due_amount' => $totalDueAmount,
                'status' => $status,
                'payment_type' => $paymentTypeMap[$first->payment_type ?? 1] ?? '—',
                'first_receipt_id' => $firstReceiptId,
                'receiver_user_id' => $receiverUserId,
                'line_item_keys' => $lineItemKeys,
                'bills' => $group->values(),
            ];
        })->values();
    }

    private function kitchenClientTypeIdToSlug(int $clientType): string
    {
        $map = [
            KitchenIssueMaster::CLIENT_EMPLOYEE => 'employee',
            KitchenIssueMaster::CLIENT_OT => 'ot',
            KitchenIssueMaster::CLIENT_COURSE => 'course',
            KitchenIssueMaster::CLIENT_OTHER => 'other',
            KitchenIssueMaster::CLIENT_SECTION => 'section',
        ];

        return $map[$clientType] ?? 'employee';
    }

    /**
     * Export/search box: match buyer, slip no, status label, amounts (same haystack as DataTables).
     *
     * @param  \Illuminate\Support\Collection<int, object>  $combinedBills
     */
    private function filterCombinedBillsForProcessIndexSearch(Collection $combinedBills, string $searchRaw): Collection
    {
        $searchTokens = DataTableSearchHelper::tokens($searchRaw);
        if ($searchTokens === []) {
            return $combinedBills;
        }

        return $combinedBills->filter(function ($cb) use ($searchTokens, $searchRaw) {
            $statusLabel = ((int) ($cb->status ?? 0)) === 2
                ? 'paid'
                : (((int) ($cb->status ?? 0)) === 1 ? 'partial' : 'unpaid');

            $haystack = implode(' ', [
                (string) ($cb->buyer_name ?? ''),
                (string) ($cb->combined_invoice_no ?? ''),
                (string) ($cb->invoice_date_range ?? ''),
                (string) ($cb->client_type_display ?? ''),
                (string) ($cb->payment_type ?? ''),
                (string) number_format((float) ($cb->total ?? 0), 2, '.', ''),
                $statusLabel,
            ]);

            if (DataTableSearchHelper::haystackMatchesAllTokens($haystack, $searchTokens)) {
                return true;
            }

            if (is_numeric($searchRaw) && str_contains((string) ($cb->first_receipt_id ?? ''), (string) (int) $searchRaw)) {
                return true;
            }

            if (preg_match('/^(?:SV|DR)-(\d+)$/i', $searchRaw, $m)
                && str_contains((string) ($cb->first_receipt_id ?? ''), $m[1])) {
                return true;
            }

            return false;
        });
    }

    /**
     * Map client type slug to KitchenIssueMaster client_type constant.
     */
    private function clientTypeSlugToKitchenId(string $slug): int
    {
        $map = [
            'employee' => KitchenIssueMaster::CLIENT_EMPLOYEE,
            'ot' => KitchenIssueMaster::CLIENT_OT,
            'course' => KitchenIssueMaster::CLIENT_COURSE,
            'other' => KitchenIssueMaster::CLIENT_OTHER,
            'section' => KitchenIssueMaster::CLIENT_SECTION,
        ];
        return $map[$slug] ?? KitchenIssueMaster::CLIENT_EMPLOYEE;
    }

    /**
     * Get client_type_slug from a bill model (KitchenIssueMaster or SellingVoucherDateRangeReport).
     */
    private function getBillClientId($bill): int
    {
        if ($bill instanceof SellingVoucherDateRangeReport) {
            return (int) ($bill->client_id ?? 0);
        }
        if ($bill instanceof KitchenIssueMaster) {
            return (int) ($bill->client_id ?? 0);
        }
        if (is_object($bill) && isset($bill->client_id) && (int) $bill->client_id > 0) {
            return (int) $bill->client_id;
        }

        $name = trim((string) ($bill->client_name ?? ''));
        if ($name === '') {
            return 0;
        }

        $slug = $this->getBillClientTypeSlug($bill);
        $resolved = MessBuyerClientFilter::resolveClientId($name, [$slug]);

        return ($resolved !== null && $resolved > 0) ? $resolved : 0;
    }

    /**
     * Stable group key: one combined bill per client_id (employee/OT/course), not per historical client_name.
     */
    private function messBillBuyerGroupKey($bill): string
    {
        $slug = $this->getBillClientTypeSlug($bill);
        $clientId = $this->getBillClientId($bill);
        if ($clientId > 0 && in_array($slug, self::ALLOWED_CLIENT_SLUGS, true)) {
            return 'cid:' . $clientId . '|' . $slug;
        }

        $name = trim((string) ($bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '')));

        return 'name:' . $name . '|' . $slug;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>|null  $group
     */
    private function resolveMessBillBuyerDisplayName($bill, $group = null): string
    {
        $clientTypePk = (int) ($bill->client_type_pk ?? 0);
        $clientId = 0;
        if ($group instanceof Collection) {
            foreach ($group as $row) {
                $clientId = max($clientId, $this->getBillClientId($row));
            }
        } else {
            $clientId = $this->getBillClientId($bill);
        }

        if ($clientId > 0) {
            $display = MessBuyerClientFilter::resolveDisplayNameForClient($clientId, $clientTypePk);
            if ($display !== '') {
                return $display;
            }
        }

        return trim((string) ($bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—')));
    }

    private function applyMessBillBuyerScope($query, string $buyerName, string $clientTypeSlug, int $clientTypePk = 0): void
    {
        $buyerName = trim($buyerName);
        if ($buyerName === '' || $buyerName === '—') {
            return;
        }

        MessBuyerClientFilter::apply($query, [$buyerName], [$clientTypeSlug], $clientTypePk);
    }

    /**
     * @param  array{name: string, slug: string, client_id: int}  $entry
     */
    private function billMatchesMessBuyerEntry($bill, array $entry): bool
    {
        $slug = $this->getBillClientTypeSlug($bill);
        if ($slug !== $entry['slug']) {
            return false;
        }

        $billClientId = $this->getBillClientId($bill);
        if ($entry['client_id'] > 0) {
            if ($billClientId > 0 && $billClientId === $entry['client_id']) {
                return true;
            }

            $variants = MessBuyerClientFilter::nameVariants($entry['name'], $entry['client_id']);

            return in_array(trim((string) ($bill->client_name ?? '')), $variants, true);
        }

        $billName = trim((string) ($bill->client_name ?? ''));

        return $billName === $entry['name'];
    }

    private function getBillClientTypeSlug($bill): string
    {
        if (is_object($bill) && isset($bill->client_type_slug) && trim((string) $bill->client_type_slug) !== '') {
            return (string) $bill->client_type_slug;
        }
        if ($bill instanceof SellingVoucherDateRangeReport) {
            return (string) ($bill->client_type_slug ?? 'employee');
        }
        $map = [
            KitchenIssueMaster::CLIENT_EMPLOYEE => 'employee',
            KitchenIssueMaster::CLIENT_OT => 'ot',
            KitchenIssueMaster::CLIENT_COURSE => 'course',
            KitchenIssueMaster::CLIENT_OTHER => 'other',
            KitchenIssueMaster::CLIENT_SECTION => 'section',
        ];
        return $map[(int) ($bill->client_type ?? 0)] ?? 'employee';
    }

    /**
     * Generate a single invoice number for a combined bill (like other invoices).
     * Format: CB-YYYYMMDD-XXXXX (deterministic per buyer + client type per day).
     */
    private function generateCombinedInvoiceNo(string $buyerName, string $clientTypeSlug): string
    {
        return mess_combined_bill_slip_no($buyerName, $clientTypeSlug);
    }

    /**
     * Group bills by buyer (client_id when available, else client_name + client_type_slug) and build combined bill rows.
     * Returns array of combined bill objects for display and payment.
     * Uses a single combined_invoice_no (e.g. CB-20260312-00123) so slip/receipt shows one invoice like others.
     */
    private function groupBillsByBuyer($bills, ?string $dateFromYmd = null, ?string $dateToYmd = null): \Illuminate\Support\Collection
    {
        $groups = collect($bills)->groupBy(fn ($bill) => $this->messBillBuyerGroupKey($bill));

        $paymentTypeMap = [0 => 'Cash', 1 => 'Deduct From Salary', 2 => 'Online', 5 => 'Deduct From Salary'];

        return $groups->map(function ($group, $key) use ($paymentTypeMap, $dateFromYmd, $dateToYmd) {
            $first = $group->first();
            $buyerName = $this->resolveMessBillBuyerDisplayName($first, $group);
            $clientTypeSlug = $this->getBillClientTypeSlug($first);
            $combinedId = 'combined-' . rawurlencode($buyerName) . '-' . $clientTypeSlug;
            $combinedInvoiceNo = $this->generateCombinedInvoiceNo($buyerName, $clientTypeSlug);

            $financials = $this->computeCombinedBillFinancials($buyerName, $clientTypeSlug, $dateFromYmd, $dateToYmd);
            $total = $financials['total'];
            $paid = $financials['paid'];
            $due = $financials['due'];
            $totalDueAmount = ($buyerName !== '' && $buyerName !== '—' && $dateToYmd)
                ? $this->computeCombinedBillFinancials($buyerName, $clientTypeSlug, null, $dateToYmd)['due']
                : $due;

            // Invoice date range: use line request dates for SV date-range (filtered items); else voucher issue_date (kitchen / header).
            $dateStrings = collect();
            foreach ($group as $b) {
                if ($b instanceof SellingVoucherDateRangeReport && $b->relationLoaded('items') && $b->items->isNotEmpty()) {
                    foreach ($b->items as $item) {
                        if (! empty($item->issue_date)) {
                            $d = $item->issue_date instanceof Carbon
                                ? $item->issue_date->format('Y-m-d')
                                : Carbon::parse($item->issue_date)->format('Y-m-d');
                            $dateStrings->push($d);
                        }
                    }
                } elseif ($b->issue_date) {
                    $dateStrings->push($b->issue_date instanceof Carbon
                        ? $b->issue_date->format('Y-m-d')
                        : Carbon::parse($b->issue_date)->format('Y-m-d'));
                }
            }
            $dateStrings = $dateStrings->filter()->unique()->sort()->values();
            $dateMin = $dateStrings->first();
            $dateMax = $dateStrings->last();
            $invoiceDateRange = $dateMin && $dateMax
                ? (Carbon::parse($dateMin)->format('d-m-Y') . ($dateMin !== $dateMax ? ' to ' . Carbon::parse($dateMax)->format('d-m-Y') : ''))
                : '—';

            $status = $this->isBillFullyPaid($paid, $total) ? 2 : ($paid > 0 ? 1 : 0);

            $firstReceiptId = $group->first();
            $firstReceiptId = $firstReceiptId instanceof SellingVoucherDateRangeReport
                ? 'dr-' . $firstReceiptId->id
                : 'ki-' . ($firstReceiptId->pk ?? $firstReceiptId->id);

            return (object) [
                'combined_id' => $combinedId,
                'combined_invoice_no' => $combinedInvoiceNo,
                'buyer_name' => $buyerName,
                'client_type_slug' => $clientTypeSlug,
                'client_type_display' => $first->client_type_display ?? ($first->client_type_label ?? ($first->clientTypeCategory ? ucfirst($first->clientTypeCategory->client_type ?? '') : ucfirst($clientTypeSlug))),
                'invoice_date_range' => $invoiceDateRange,
                'total' => $total,
                'paid' => $paid,
                'due' => $due,
                'total_due_amount' => $totalDueAmount,
                'status' => $status,
                'payment_type' => $paymentTypeMap[$first->payment_type ?? 1] ?? '—',
                'first_receipt_id' => $firstReceiptId,
                'bills' => $group->values(),
            ];
        })->values();
    }

    /**
     * Stable keys for mess bill line items (SV date-range item id or kitchen issue item pk).
     *
     * @param  array<int, SellingVoucherDateRangeReport|KitchenIssueMaster>  $bills
     * @return array<int, string>
     */
    private function collectMessBillLineItemKeys(array $bills): array
    {
        $keys = [];
        foreach ($bills as $b) {
            if ($b instanceof SellingVoucherDateRangeReport) {
                foreach ($b->items ?? [] as $item) {
                    $id = (int) ($item->id ?? 0);
                    if ($id > 0) {
                        $keys[] = 'dr-' . $id;
                    }
                }
            } elseif ($b instanceof KitchenIssueMaster) {
                $items = $b->relationLoaded('items') ? $b->items : collect();
                if ($items->isNotEmpty()) {
                    foreach ($items as $item) {
                        $pk = (int) ($item->pk ?? 0);
                        if ($pk > 0) {
                            $keys[] = 'ki-' . $pk;
                        }
                    }
                } else {
                    $masterPk = (int) ($b->pk ?? 0);
                    if ($masterPk > 0) {
                        $keys[] = 'ki-bill-' . $masterPk;
                    }
                }
            } elseif ($this->isProcessIndexVoucherStub($b)) {
                foreach ($this->collectMessBillLineItemKeysFromProcessIndexStub($b) as $key) {
                    $keys[] = $key;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    private function isProcessIndexVoucherStub($bill): bool
    {
        return is_object($bill)
            && ! ($bill instanceof SellingVoucherDateRangeReport)
            && ! ($bill instanceof KitchenIssueMaster)
            && in_array((string) ($bill->source_type ?? ''), ['date_range', 'kitchen_issue'], true);
    }

    /**
     * Line-item keys for lightweight process-index voucher rows (no Eloquent items loaded).
     *
     * @return array<int, string>
     */
    private function collectMessBillLineItemKeysFromProcessIndexStub(object $bill): array
    {
        $keys = [];
        if (($bill->source_type ?? '') === 'date_range') {
            $reportId = (int) ($bill->id ?? 0);
            if ($reportId > 0) {
                foreach (DB::table('sv_date_range_report_items')
                    ->where('sv_date_range_report_id', $reportId)
                    ->pluck('id') as $itemId) {
                    $id = (int) $itemId;
                    if ($id > 0) {
                        $keys[] = 'dr-' . $id;
                    }
                }
            }
        } elseif (($bill->source_type ?? '') === 'kitchen_issue') {
            $masterPk = (int) ($bill->pk ?? $bill->id ?? 0);
            if ($masterPk > 0) {
                $itemPks = DB::table('kitchen_issue_items')
                    ->where('kitchen_issue_master_pk', $masterPk)
                    ->pluck('pk');
                if ($itemPks->isNotEmpty()) {
                    foreach ($itemPks as $itemPk) {
                        $pk = (int) $itemPk;
                        if ($pk > 0) {
                            $keys[] = 'ki-' . $pk;
                        }
                    }
                } else {
                    $keys[] = 'ki-bill-' . $masterPk;
                }
            }
        }

        return $keys;
    }

    /**
     * For notifications created before line-item tracking: items issued on/before notification date.
     *
     * @param  array<int, SellingVoucherDateRangeReport|KitchenIssueMaster>  $bills
     * @return array<int, string>
     */
    private function collectMessBillLineItemKeysIssuedOnOrBefore(array $bills, string $onOrBeforeYmd): array
    {
        $keys = [];
        foreach ($bills as $b) {
            if ($b instanceof SellingVoucherDateRangeReport) {
                foreach ($b->items ?? [] as $item) {
                    $issueYmd = null;
                    if (! empty($item->issue_date)) {
                        try {
                            $issueYmd = $item->issue_date instanceof Carbon
                                ? $item->issue_date->format('Y-m-d')
                                : Carbon::parse($item->issue_date)->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $issueYmd = null;
                        }
                    }
                    if ($issueYmd !== null && $issueYmd <= $onOrBeforeYmd) {
                        $id = (int) ($item->id ?? 0);
                        if ($id > 0) {
                            $keys[] = 'dr-' . $id;
                        }
                    }
                }
            } elseif ($b instanceof KitchenIssueMaster) {
                $billIssueYmd = null;
                if (! empty($b->issue_date)) {
                    try {
                        $billIssueYmd = $b->issue_date instanceof Carbon
                            ? $b->issue_date->format('Y-m-d')
                            : Carbon::parse($b->issue_date)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $billIssueYmd = null;
                    }
                }
                if ($billIssueYmd === null || $billIssueYmd > $onOrBeforeYmd) {
                    continue;
                }
                $items = $b->relationLoaded('items') ? $b->items : collect();
                if ($items->isNotEmpty()) {
                    foreach ($items as $item) {
                        $pk = (int) ($item->pk ?? 0);
                        if ($pk > 0) {
                            $keys[] = 'ki-' . $pk;
                        }
                    }
                } else {
                    $masterPk = (int) ($b->pk ?? 0);
                    if ($masterPk > 0) {
                        $keys[] = 'ki-bill-' . $masterPk;
                    }
                }
            } elseif ($this->isProcessIndexVoucherStub($b)) {
                if (($b->source_type ?? '') === 'kitchen_issue') {
                    $billIssueYmd = null;
                    if (! empty($b->issue_date)) {
                        try {
                            $billIssueYmd = $b->issue_date instanceof Carbon
                                ? $b->issue_date->format('Y-m-d')
                                : Carbon::parse($b->issue_date)->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $billIssueYmd = null;
                        }
                    }
                    if ($billIssueYmd === null || $billIssueYmd > $onOrBeforeYmd) {
                        continue;
                    }
                    foreach ($this->collectMessBillLineItemKeysFromProcessIndexStub($b) as $key) {
                        $keys[] = $key;
                    }
                } elseif (($b->source_type ?? '') === 'date_range') {
                    $reportId = (int) ($b->id ?? 0);
                    if ($reportId > 0) {
                        foreach (DB::table('sv_date_range_report_items')
                            ->where('sv_date_range_report_id', $reportId)
                            ->where('issue_date', '<=', $onOrBeforeYmd)
                            ->pluck('id') as $itemId) {
                            $id = (int) $itemId;
                            if ($id > 0) {
                                $keys[] = 'dr-' . $id;
                            }
                        }
                    }
                }
            }
        }

        return array_values(array_unique($keys));
    }

    private function messCombinedDateRangesOverlap(string $fromA, string $toA, string $fromB, string $toB): bool
    {
        if ($fromA === '' || $toA === '' || $fromB === '' || $toB === '') {
            return true;
        }

        return $fromA <= $toB && $fromB <= $toA;
    }

    /**
     * Union of line-item keys already notified for this buyer/combined bill (overlapping statement ranges).
     *
     * @param  array<int, SellingVoucherDateRangeReport|KitchenIssueMaster>|null  $billsForLegacy
     * @return array<int, string>
     */
    private function getMessCombinedNotifiedLineItemKeys(
        int $receiverUserId,
        string $combinedId,
        string $dateFromYmd,
        string $dateToYmd,
        ?array $billsForLegacy = null
    ): array {
        if ($receiverUserId <= 0 || $combinedId === '') {
            return [];
        }

        $notified = [];

        foreach ($this->messCombinedNotificationsForReceiver($receiverUserId) as $n) {
            $parsed = NotificationService::parseMessCombinedReceiptPayload($n->message);
            if ($parsed === null || $parsed['i'] !== $combinedId) {
                continue;
            }
            $nf = (string) ($parsed['f'] ?? '');
            $nt = (string) ($parsed['t'] ?? '');
            if (! $this->messCombinedDateRangesOverlap($nf, $nt, $dateFromYmd, $dateToYmd)) {
                continue;
            }
            $itemKeys = $parsed['n'] ?? [];
            if ($itemKeys === []) {
                if ($billsForLegacy !== null && $n->created_at) {
                    $cutoffYmd = $n->created_at instanceof Carbon
                        ? $n->created_at->format('Y-m-d')
                        : Carbon::parse($n->created_at)->format('Y-m-d');
                    foreach ($this->collectMessBillLineItemKeysIssuedOnOrBefore($billsForLegacy, $cutoffYmd) as $legacyKey) {
                        $notified[$legacyKey] = true;
                    }
                }

                continue;
            }
            foreach ($itemKeys as $key) {
                if ($key !== '') {
                    $notified[$key] = true;
                }
            }
        }

        return array_keys($notified);
    }

    /**
     * @param  array<int, SellingVoucherDateRangeReport|KitchenIssueMaster>  $bills
     * @return array{sent: bool, fully_sent: bool, partial: bool, pending_count: int, read: bool}
     */
    private function resolveMessCombinedInvoiceNotificationStatus(
        int $receiverUserId,
        string $combinedId,
        string $dateFromYmd,
        string $dateToYmd,
        array $bills,
        ?array $currentKeys = null
    ): array {
        $currentKeys = $currentKeys ?? $this->collectMessBillLineItemKeys($bills);
        $notifiedKeys = $this->getMessCombinedNotifiedLineItemKeys(
            $receiverUserId,
            $combinedId,
            $dateFromYmd,
            $dateToYmd,
            $bills
        );

        $notifiedSet = array_fill_keys($notifiedKeys, true);
        $notifiedAmongCurrent = 0;
        foreach ($currentKeys as $key) {
            if (isset($notifiedSet[$key])) {
                $notifiedAmongCurrent++;
            }
        }
        $pendingCount = count($currentKeys) - $notifiedAmongCurrent;

        // Only items still on this bill count — stale keys from old notifications must not show "partial".
        $sent = $notifiedAmongCurrent > 0;
        $fullySent = $currentKeys !== [] && $pendingCount === 0;
        $partial = $sent && ! $fullySent && $pendingCount > 0;

        // Legacy notifications without line-item keys (or index stubs before keys are resolved).
        if (! $sent && $this->messCombinedHasInvoiceNotificationInDateRange($receiverUserId, $combinedId, $dateFromYmd, $dateToYmd)) {
            $sent = true;
            $fullySent = true;
            $partial = false;
            $pendingCount = 0;
        }

        return [
            'sent' => $sent,
            'fully_sent' => $fullySent,
            'partial' => $partial,
            'pending_count' => $pendingCount,
            'read' => $sent ? $this->messCombinedInvoiceLatestRead($receiverUserId, $combinedId) : false,
        ];
    }

    private function messCombinedHasInvoiceNotificationInDateRange(
        int $receiverUserId,
        string $combinedId,
        string $dateFromYmd,
        string $dateToYmd
    ): bool {
        if ($receiverUserId <= 0 || $combinedId === '') {
            return false;
        }

        foreach ($this->messCombinedNotificationsForReceiver($receiverUserId) as $n) {
            $parsed = NotificationService::parseMessCombinedReceiptPayload($n->message);
            if ($parsed === null || $parsed['i'] !== $combinedId) {
                continue;
            }
            $nf = (string) ($parsed['f'] ?? '');
            $nt = (string) ($parsed['t'] ?? '');
            if ($this->messCombinedDateRangesOverlap($nf, $nt, $dateFromYmd, $dateToYmd)) {
                return true;
            }
        }

        return false;
    }

    private function messCombinedInvoiceLatestRead(int $receiverUserId, string $combinedId): bool
    {
        foreach ($this->messCombinedNotificationsForReceiver($receiverUserId) as $n) {
            $parsed = NotificationService::parseMessCombinedReceiptPayload($n->message);
            if ($parsed !== null && $parsed['i'] === $combinedId) {
                return (int) $n->is_read === 1;
            }
        }

        return false;
    }

    /**
     * For modal rows: map "receiver_user_id|combined_id" => notification status.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $combinedBills
     * @param  string|null  $dateFromYmd
     * @param  string|null  $dateToYmd
     * @return array<string, array{read: bool, fully_sent: bool, partial: bool, pending_count: int}>
     */
    private function messCombinedInvoiceNotificationSentKeys(
        $combinedBills,
        ?string $dateFromYmd = null,
        ?string $dateToYmd = null
    ): array {
        $dateFromYmd = $dateFromYmd ?? now()->startOfMonth()->format('Y-m-d');
        $dateToYmd = $dateToYmd ?? now()->endOfMonth()->format('Y-m-d');
        $keys = [];

        $receiverIds = [];
        foreach ($combinedBills as $cb) {
            $receiverId = isset($cb->receiver_user_id) && (int) $cb->receiver_user_id > 0
                ? (int) $cb->receiver_user_id
                : (int) ($this->resolveReceiverUserIdFromAnyBill($cb->bills->all()) ?? 0);
            if ($receiverId > 0) {
                $receiverIds[$receiverId] = true;
            }
        }
        $this->preloadMessCombinedNotificationsForReceivers(array_keys($receiverIds));

        foreach ($combinedBills as $cb) {
            $receiverId = isset($cb->receiver_user_id) && (int) $cb->receiver_user_id > 0
                ? (int) $cb->receiver_user_id
                : (int) ($this->resolveReceiverUserIdFromAnyBill($cb->bills->all() ?? []) ?? 0);
            if ($receiverId <= 0) {
                continue;
            }
            $mapKey = $receiverId . '|' . $cb->combined_id;
            if (isset($keys[$mapKey])) {
                continue;
            }
            $precomputedKeys = isset($cb->line_item_keys) && is_array($cb->line_item_keys)
                ? $cb->line_item_keys
                : null;
            $status = $this->resolveMessCombinedInvoiceNotificationStatus(
                (int) $receiverId,
                (string) $cb->combined_id,
                $dateFromYmd,
                $dateToYmd,
                $cb->bills->all(),
                $precomputedKeys
            );
            if (! $status['sent']) {
                continue;
            }
            $keys[$mapKey] = [
                'read' => $status['read'],
                'fully_sent' => $status['fully_sent'],
                'partial' => $status['partial'],
                'pending_count' => $status['pending_count'],
            ];
        }

        return $keys;
    }

    /**
     * Buyers with a MessInvoiceCombined notification overlapping the filter period (SQL pre-filter).
     *
     * @return array<int, array{name: string, slug: string}>
     */
    private function getSentInvoiceBuyerAllowlistForDateRange(?string $dateFromYmd, ?string $dateToYmd): array
    {
        $dateFromYmd = $dateFromYmd ?? now()->startOfMonth()->format('Y-m-d');
        $dateToYmd = $dateToYmd ?? now()->endOfMonth()->format('Y-m-d');
        $allowlist = [];

        foreach (Notification::query()
            ->where('type', 'mess')
            ->where('module_name', 'MessInvoiceCombined')
            ->get(['message']) as $notification) {
            $parsed = NotificationService::parseMessCombinedReceiptPayload($notification->message);
            if ($parsed === null || empty($parsed['i'])) {
                continue;
            }
            $nf = (string) ($parsed['f'] ?? '');
            $nt = (string) ($parsed['t'] ?? '');
            if (! $this->messCombinedDateRangesOverlap($nf, $nt, $dateFromYmd, $dateToYmd)) {
                continue;
            }
            $buyer = $this->parseProcessMessCombinedBillId((string) $parsed['i']);
            if ($buyer === null) {
                continue;
            }
            $key = $buyer['name'] . '|' . $buyer['slug'];
            $allowlist[$key] = $buyer;
        }

        return array_values($allowlist);
    }

    /**
     * @return array{name: string, slug: string}|null
     */
    private function parseProcessMessCombinedBillId(string $combinedId): ?array
    {
        if (! str_starts_with($combinedId, 'combined-')) {
            return null;
        }

        $rest = substr($combinedId, strlen('combined-'));
        foreach (['employee', 'ot', 'course', 'other', 'section'] as $slug) {
            $suffix = '-' . $slug;
            if (! str_ends_with($rest, $suffix)) {
                continue;
            }
            $encodedName = substr($rest, 0, -strlen($suffix));
            $name = trim(rawurldecode($encodedName));

            return $name !== '' ? ['name' => $name, 'slug' => $slug] : null;
        }

        return null;
    }

    /**
     * @param  array<int, array{name: string, slug: string}>  $allowlist
     */
    private function applySentInvoiceBuyerAllowlistFilter($dateRangeQuery, $kitchenIssueQuery, array $allowlist): void
    {
        if ($allowlist === []) {
            $dateRangeQuery->whereRaw('0 = 1');
            $kitchenIssueQuery->whereRaw('0 = 1');

            return;
        }

        $dateRangeQuery->where(function ($query) use ($allowlist) {
            foreach ($allowlist as $entry) {
                $query->orWhere(function ($inner) use ($entry) {
                    $inner->where('client_name', $entry['name'])
                        ->where('client_type_slug', $entry['slug']);
                });
            }
        });

        $kitchenIssueQuery->where(function ($query) use ($allowlist) {
            foreach ($allowlist as $entry) {
                $kitchenId = $this->clientTypeSlugToKitchenId($entry['slug']);
                $query->orWhere(function ($inner) use ($entry, $kitchenId) {
                    $inner->where('client_name', $entry['name'])
                        ->where('client_type', $kitchenId);
                });
            }
        });
    }

    /**
     * Default: invoice_sent=sent. Explicit invoice_sent= (empty) means show all.
     */
    private function resolveInvoiceSentFilter(Request $request): ?string
    {
        if (! $request->has('invoice_sent')) {
            return 'sent';
        }

        $value = $request->input('invoice_sent');

        return ($value !== null && $value !== '') ? (string) $value : null;
    }

    /**
     * When invoice_sent=sent, keep only combined bills with a MessInvoiceCombined notification.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $combinedBills
     */
    private function filterCombinedBillsByInvoiceSent(
        Collection $combinedBills,
        ?string $invoiceSentFilter,
        ?string $dateFromYmd = null,
        ?string $dateToYmd = null
    ): Collection {
        if ($invoiceSentFilter !== 'sent') {
            return $combinedBills;
        }

        $invoiceSentKeys = $this->messCombinedInvoiceNotificationSentKeys($combinedBills, $dateFromYmd, $dateToYmd);

        return $combinedBills->filter(function ($cb) use ($invoiceSentKeys) {
            $receiverId = isset($cb->receiver_user_id) && (int) $cb->receiver_user_id > 0
                ? (int) $cb->receiver_user_id
                : $this->resolveReceiverUserIdFromAnyBill($cb->bills->all());
            if ($receiverId === null || $receiverId <= 0) {
                return false;
            }

            return isset($invoiceSentKeys[$receiverId . '|' . $cb->combined_id]);
        })->values();
    }

    /**
     * True if a MessInvoice notification was already sent for this bill reference and receiver.
     */
    private function messSingleInvoiceAlreadySent(int $receiverUserId, int $referencePk): bool
    {
        if ($receiverUserId <= 0 || $referencePk <= 0) {
            return false;
        }

        return Notification::query()
            ->where('type', 'mess')
            ->where('module_name', 'MessInvoice')
            ->where('receiver_user_id', $receiverUserId)
            ->where('reference_pk', $referencePk)
            ->exists();
    }

    /**
     * Get summary statistics for mess bills (Employee, OT, Course, Other) in the given date range.
     */
    private function getSummaryStats(?string $dateFrom, ?string $dateTo, ?string $search, ?string $clientType = null, ?string $buyerName = null): array
    {
        $dateRangeSlugs = $clientType ? [$clientType] : self::ALLOWED_CLIENT_SLUGS;
        $dateRangeBase = SellingVoucherDateRangeReport::query()
            ->whereIn('client_type_slug', $dateRangeSlugs);
        if ($buyerName) {
            $dateRangeBase->where('client_name', 'like', '%' . $buyerName . '%');
        }
        $dateRangeBase->whereIn('status', $this->sellingVoucherDateRangeReportSaleVoucherStatuses());
        $this->applySellingVoucherDateRangeItemIssueDateFilter($dateRangeBase, $dateFrom, $dateTo);

        $kitchenClientTypes = $clientType
            ? [$this->clientTypeSlugToKitchenId($clientType)]
            : self::ALLOWED_KITCHEN_CLIENT_TYPES;
        $kitchenBase = KitchenIssueMaster::query()
            ->whereIn('client_type', $kitchenClientTypes)
            ->whereIn('kitchen_issue_type', self::KITCHEN_MESS_SELLING_ISSUE_TYPES);
        if ($buyerName) {
            $kitchenBase->where('client_name', 'like', '%' . $buyerName . '%');
        }
        if ($dateFrom) {
            $kitchenBase->where('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $kitchenBase->where('issue_date', '<=', $dateTo);
        }

        $totalBills = (clone $dateRangeBase)->count() + (clone $kitchenBase)->count();
        $paidCount = (clone $dateRangeBase)->where('status', 2)->count() + (clone $kitchenBase)->where('status', 2)->count();
        $unpaidCount = $totalBills - $paidCount;

        $svAmount = (float) (clone $dateRangeBase)->with([
            'items' => function ($itemQ) use ($dateFrom, $dateTo) {
                $this->applySvDateRangeReportItemsIssueDateConstraint($itemQ, $dateFrom, $dateTo);
            },
        ])->get()->sum(fn ($r) => $r->net_total);
        $kitchenAmount = (float) (clone $kitchenBase)->with('items')->get()->sum(fn ($b) => $b->net_total);
        $totalAmount = $svAmount + $kitchenAmount;

        return [
            'total_bills' => $totalBills,
            'paid_count' => $paidCount,
            'unpaid_count' => $unpaidCount,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Export Process Mess Bills to Excel.
     */
    public function export(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? $this->parseDate($request->date_from) : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $this->parseDate($request->date_to) : now()->endOfMonth()->format('Y-m-d');
        $unionCollation = 'utf8mb4_unicode_ci';
        $search = $request->filled('search') ? trim((string) $request->search) : null;
        $search = ($search !== null && $search !== '') ? $search : null;
        $clientTypes = $this->normalizeFilterArrayValues($request->input('client_type'));
        $clientType = $clientTypes[0] ?? ($request->filled('client_type') ? $request->client_type : null);
        $clientTypePks = $this->normalizeFilterArrayValues($request->input('client_type_pk'));
        $buyerNames = $this->normalizeBuyerNames($request->input('buyer_name'));
        $buyerNames = $this->normalizeBuyerNamesToClientIds($buyerNames, $clientTypes, $clientTypePks);
        $buyerName = $buyerNames[0] ?? null;
        $statusFilter = $request->filled('status') ? $request->status : null;
        $invoiceSentFilter = $this->resolveInvoiceSentFilter($request);

        // Same union query as index, but get all results
        $dateRangeQuery = SellingVoucherDateRangeReport::query()
            ->select([
                'id',
                DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
                'issue_date',
                'date_from',
                DB::raw("CONVERT(client_type_slug USING utf8mb4) COLLATE {$unionCollation} as client_type_slug"),
                'client_type_pk',
                'total_amount',
                'payment_type',
                'status',
                'store_id',
                DB::raw("CONVERT('date_range' USING utf8mb4) COLLATE {$unionCollation} as source_type")
            ])
            ->whereIn('client_type_slug', $clientType ? [$clientType] : self::ALLOWED_CLIENT_SLUGS);

        $this->applyBuyerNameFilter($dateRangeQuery, $buyerNames, $clientTypes, $clientTypePks);
        $dateRangeQuery->whereIn('status', $this->sellingVoucherDateRangeReportSaleVoucherStatuses());
        $this->applySellingVoucherDateRangeItemIssueDateFilter($dateRangeQuery, $dateFrom, $dateTo);

        $kitchenClientTypes = $clientType
            ? [$this->clientTypeSlugToKitchenId($clientType)]
            : self::ALLOWED_KITCHEN_CLIENT_TYPES;
        $kitchenIssueQuery = KitchenIssueMaster::query()
            ->select([
                'pk as id',
                DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
                'issue_date',
                DB::raw('NULL as date_from'),
                DB::raw("CONVERT((CASE client_type WHEN 1 THEN 'employee' WHEN 2 THEN 'ot' WHEN 3 THEN 'course' WHEN 4 THEN 'other' END) USING utf8mb4) COLLATE {$unionCollation} as client_type_slug"),
                'client_type_pk',
                DB::raw('NULL as total_amount'),
                'payment_type',
                'status',
                'store_id',
                DB::raw("CONVERT('kitchen_issue' USING utf8mb4) COLLATE {$unionCollation} as source_type")
            ])
            ->whereIn('client_type', $kitchenClientTypes)
            ->whereIn('kitchen_issue_type', self::KITCHEN_MESS_SELLING_ISSUE_TYPES);

        $this->applyBuyerNameFilter($kitchenIssueQuery, $buyerNames, $clientTypes, $clientTypePks);
        if ($dateFrom) {
            $kitchenIssueQuery->where('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $kitchenIssueQuery->where('issue_date', '<=', $dateTo);
        }

        [$combinedBills] = $this->queryAndGroupBillsForProcessIndexLight(
            $dateFrom,
            $dateTo,
            $dateRangeQuery,
            $kitchenIssueQuery
        );

        if ($search) {
            $combinedBills = $this->filterCombinedBillsForProcessIndexSearch($combinedBills, $search)->values();
        }
        $bills = $rowsRaw->map(function ($bill) use ($dateFrom, $dateTo) {
            if ($bill->source_type === 'date_range') {
                $model = SellingVoucherDateRangeReport::with([
                    'clientTypeCategory',
                    'items' => function ($itemQ) use ($dateFrom, $dateTo) {
                        $this->applySvDateRangeReportItemsIssueDateConstraint($itemQ, $dateFrom, $dateTo);
                    },
                    'items.itemSubcategory',
                ])->find($bill->id);
                if ($model) $model->setAttribute('source_type', 'date_range');
                return $model;
            }
            $model = KitchenIssueMaster::with(['clientTypeCategory', 'items'])->where('pk', $bill->id)->first();
            if ($model) $model->setAttribute('source_type', 'kitchen_issue');
            return $model;
        })->filter()->values();

        $combinedBills = $this->groupBillsByBuyer($bills, $dateFrom, $dateTo);

        // Optional status filter on combined bills for export as well
        if ($statusFilter !== null && $statusFilter !== '') {
            $statusMap = [
                'unpaid' => 0,
                'partial' => 1,
                'paid' => 2,
                0 => 0,
                1 => 1,
                2 => 2,
            ];
            $normalized = $statusMap[$statusFilter] ?? null;
            if ($normalized !== null) {
                $combinedBills = $combinedBills->where('status', $normalized)->values();
            }
        }
        $combinedBills = $this->filterCombinedBillsByInvoiceSent($combinedBills, $invoiceSentFilter, $dateFrom, $dateTo);
        $paymentTypeMap = [0 => 'Cash', 1 => 'Deduct From Salary', 2 => 'Online', 5 => 'Deduct From Salary'];
        $statusMap = [0 => 'Unpaid', 1 => 'Pending', 2 => 'Paid'];

        $visibleIndexes = ProcessMessBillsExport::parseVisibleColumnIndexes(
            $request->query('visible_columns')
        );
        $headings = ProcessMessBillsExport::headingsForIndexes($visibleIndexes);

        $rows = [];
        foreach ($combinedBills as $index => $cb) {
            $status = $statusMap[$cb->status ?? 0] ?? '—';
            $fullRow = [
                $index + 1,
                $cb->buyer_name ?? '—',
                $cb->combined_invoice_no ?? '—',
                $cb->invoice_date_range ?? '—',
                $cb->client_type_display ?? '—',
                '₹ ' . number_format($cb->total ?? 0, 2),
                '₹ ' . number_format($cb->total_due_amount ?? 0, 2),
                $cb->payment_type ?? '—',
                $status,
            ];
            $rows[] = ProcessMessBillsExport::filterRowByIndexes($fullRow, $visibleIndexes);
        }

        $effectiveDateFrom = $request->filled('date_from') ? $request->date_from : Carbon::parse($dateFrom)->format('d-m-Y');
        $effectiveDateTo = $request->filled('date_to') ? $request->date_to : Carbon::parse($dateTo)->format('d-m-Y');

        $fileName = 'process-mess-bills-' . $dateFrom . '-to-' . $dateTo . '-' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(
            new ProcessMessBillsExport($rows, $effectiveDateFrom, $effectiveDateTo, $headings),
            $fileName
        );
    }

    public function printReceipt(Request $request, $id)
    {
        $paymentOnly = $request->boolean('payment_only');
        $receiptPaymentAmount = $request->filled('amount')
            ? $this->roundMoney((float) $request->input('amount'))
            : null;

        // Combined bill: single invoice number (CB-...), no individual slip numbers on receipt
        if (is_string($id) && strpos($id, 'combined-') === 0) {
            $filterDateFromYmd = $request->filled('date_from') ? $this->parseDate($request->date_from) : now()->startOfMonth()->format('Y-m-d');
            $filterDateToYmd = $request->filled('date_to') ? $this->parseDate($request->date_to) : now()->endOfMonth()->format('Y-m-d');
            $bills = $this->resolveCombinedBillBills($request, $id);
            if (empty($bills)) {
                abort(404, 'No bills found for this buyer in the selected date range.');
            }
            $this->assertCurrentUserCanAccessBills($bills);
            $items = [];
            $storeNames = [];
            $dateMin = null;
            $referenceNumbers = [];
            $orderBys = [];
            $remarksList = [];
            $courseName = null;
            $buyerName = trim((string) ($bills[0]->client_name ?? ($bills[0]->clientTypeCategory->client_name ?? '—')));
            $clientTypeSlug = $bills[0] instanceof SellingVoucherDateRangeReport
                ? (string) ($bills[0]->client_type_slug ?? 'employee')
                : $this->getBillClientTypeSlug($bills[0]);
            $financials = $this->computeCombinedBillFinancials($buyerName, $clientTypeSlug, $filterDateFromYmd, $filterDateToYmd);
            $totalAmount = $financials['total'];
            $paidAmount = $financials['paid'];
            $invoiceNo = $this->generateCombinedInvoiceNo($buyerName, $clientTypeSlug);
            $clientTypeDisplay = $bills[0]->client_type_display ?? ($bills[0]->client_type_label ?? '—');
            $courseName = null;

            if ($paymentOnly) {
                try {
                    $first = $bills[0];
                    if ($first instanceof SellingVoucherDateRangeReport) {
                        if (in_array($first->client_type_slug ?? '', ['ot', 'course'], true)) {
                            $courseName = optional($first->course)->course_name;
                        }
                    } elseif ($first instanceof KitchenIssueMaster) {
                        if (in_array((int) ($first->client_type ?? 0), [KitchenIssueMaster::CLIENT_OT, KitchenIssueMaster::CLIENT_COURSE], true)) {
                            $courseName = optional($first->course)->course_name;
                        }
                    }
                } catch (\Throwable $e) {
                    $courseName = null;
                }
                $clientNameCourse = $courseName ? trim($buyerName . ' – ' . $courseName) : $buyerName;
                $bill = (object) [
                    'items' => collect(),
                    'client_name' => $buyerName,
                    'client_name_course' => $clientNameCourse,
                    'client_type_display' => $clientTypeDisplay,
                    'clientTypeCategory' => $bills[0]->clientTypeCategory ?? null,
                    'client_type_label' => $bills[0]->client_type_label ?? null,
                    'client_type_slug' => $clientTypeSlug,
                    'resolved_store_name' => '—',
                    'date_from' => Carbon::parse($filterDateFromYmd),
                    'date_to' => Carbon::parse($filterDateToYmd),
                    'issue_date' => null,
                    'net_total' => $totalAmount,
                    'reference_number' => null,
                    'order_by' => null,
                    'remarks' => null,
                    'course_name' => $courseName,
                ];
                $displayPaid = $receiptPaymentAmount ?? $paidAmount;

                return view('admin.mess.process-mess-bills-employee.print-receipt', [
                    'bill' => $bill,
                    'paidAmount' => $displayPaid,
                    'dueAmount' => $financials['due'],
                    'totalDueAmount' => $this->computeCombinedBillFinancials($buyerName, $clientTypeSlug, null, $filterDateToYmd)['due'],
                    'paymentStatusLabel' => $this->isBillFullyPaid($paidAmount, $totalAmount) ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Unpaid'),
                    'invoiceNo' => $invoiceNo,
                    'receiptNo' => $invoiceNo,
                    'paymentOnly' => true,
                    'receiptPaymentAmount' => $displayPaid,
                ]);
            }

            foreach ($bills as $b) {
                $storeName = $b->resolved_store_name ?? '—';
                $storeNames[$storeName] = true;
                $purchaseDateStr = $b->issue_date ? $b->issue_date->format('d-m-Y') : '—';
                if (!empty($b->reference_number)) {
                    $referenceNumbers[] = (string) $b->reference_number;
                }
                if (!empty($b->order_by)) {
                    $orderBys[] = (string) $b->order_by;
                }
                if (!empty($b->remarks)) {
                    $remarksList[] = trim((string) $b->remarks);
                }
                foreach ($b->items ?? [] as $item) {
                    $itemIssueDate = null;
                    $itemIssueYmd = null;
                    try {
                        if (isset($item->issue_date) && $item->issue_date) {
                            $idt = $item->issue_date instanceof Carbon
                                ? $item->issue_date
                                : Carbon::parse($item->issue_date);
                            $itemIssueDate = $idt->format('d-m-Y');
                            $itemIssueYmd = $idt->format('Y-m-d');
                        }
                    } catch (\Throwable $e) {
                        $itemIssueDate = null;
                    }
                    if ($itemIssueYmd !== null) {
                        if ($dateMin === null || $itemIssueYmd < $dateMin) {
                            $dateMin = $itemIssueYmd;
                        }
                    }
                    $items[] = (object) [
                        'item_name' => $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                        'quantity' => $item->quantity,
                        'return_quantity' => $item->return_quantity ?? 0,
                        'rate' => $item->rate ?? 0,
                        'amount' => $item->amount ?? 0,
                        'itemSubcategory' => null,
                        'store_name' => $storeName,
                        'issue_date' => $itemIssueDate ?: $purchaseDateStr,
                    ];
                }
                if ($b->issue_date) {
                    $billIssueYmd = $b->issue_date->format('Y-m-d');
                    if ($dateMin === null || $billIssueYmd < $dateMin) {
                        $dateMin = $billIssueYmd;
                    }
                }
                // Capture course name once (for OT / Course types)
                if ($courseName === null) {
                    try {
                        if ($b instanceof SellingVoucherDateRangeReport) {
                            if (in_array($b->client_type_slug ?? '', ['ot', 'course'], true)) {
                                $courseName = optional($b->course)->course_name;
                            }
                        } elseif ($b instanceof KitchenIssueMaster) {
                            if (in_array((int) ($b->client_type ?? 0), [KitchenIssueMaster::CLIENT_OT, KitchenIssueMaster::CLIENT_COURSE], true)) {
                                $courseName = optional($b->course)->course_name;
                            }
                        }
                    } catch (\Throwable $e) {
                        // ignore – optional enhancement only
                    }
                }
            }
            $referenceNumber = collect($referenceNumbers)->filter()->unique()->implode(', ');
            $orderBy = collect($orderBys)->filter()->unique()->implode(', ');
            $remarks = collect($remarksList)->filter()->unique()->implode(' | ');
            $dueAmount = $financials['due'];
            $totalDueAmount = $this->computeCombinedBillFinancials($buyerName, $clientTypeSlug, null, $filterDateToYmd)['due'];
            $paymentStatusLabel = $this->isBillFullyPaid($paidAmount, $totalAmount) ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Unpaid');
            $invoiceNo = $this->generateCombinedInvoiceNo($buyerName, $clientTypeSlug);
            $clientNameCourse = $courseName ? trim($buyerName . ' – ' . $courseName) : $buyerName;
            $bill = (object) [
                'items' => collect($items),
                'client_name' => $buyerName,
                'client_name_course' => $clientNameCourse,
                'client_type_display' => $bills[0]->client_type_display ?? ($bills[0]->client_type_label ?? '—'),
                'clientTypeCategory' => $bills[0]->clientTypeCategory ?? null,
                'client_type_label' => $bills[0]->client_type_label ?? null,
                'client_type_slug' => $clientTypeSlug,
                'resolved_store_name' => implode(', ', array_keys($storeNames)),
                'date_from' => Carbon::parse($filterDateFromYmd),
                'date_to' => Carbon::parse($filterDateToYmd),
                'issue_date' => $dateMin ? Carbon::parse($dateMin) : null,
                'net_total' => $totalAmount,
                'reference_number' => $referenceNumber ?: null,
                'order_by' => $orderBy ?: null,
                'remarks' => $remarks ?: null,
                'course_name' => $courseName,
            ];
            return view('admin.mess.process-mess-bills-employee.print-receipt', [
                'bill' => $bill,
                'paidAmount' => $paidAmount,
                'dueAmount' => $dueAmount,
                'totalDueAmount' => $totalDueAmount,
                'paymentStatusLabel' => $paymentStatusLabel,
                'invoiceNo' => $invoiceNo,
                'receiptNo' => $invoiceNo,
                'paymentOnly' => false,
            ]);
        }

        $isDateRange = null;
        $numericId = $id;
        if (is_string($id) && preg_match('/^(dr|ki)-(\d+)$/i', $id, $m)) {
            $isDateRange = (strtolower($m[1]) === 'dr');
            $numericId = (int) $m[2];
        }

        if ($isDateRange === true) {
            $bill = SellingVoucherDateRangeReport::with(['store', 'subStore', 'clientTypeCategory', 'course', 'items.itemSubcategory'])
                ->whereIn('client_type_slug', self::ALLOWED_CLIENT_SLUGS)
                ->findOrFail($numericId);
            $isDateRange = true;
        } elseif ($isDateRange === false) {
            $bill = KitchenIssueMaster::with(['store', 'subStore', 'clientTypeCategory', 'course', 'items.itemSubcategory'])
                ->whereIn('client_type', self::ALLOWED_KITCHEN_CLIENT_TYPES)
                ->whereIn('kitchen_issue_type', self::KITCHEN_MESS_SELLING_ISSUE_TYPES)
                ->where('pk', $numericId)
                ->firstOrFail();
            $isDateRange = false;
        } else {
            // Legacy: numeric id – try date range first, then kitchen
            $bill = SellingVoucherDateRangeReport::with(['store', 'subStore', 'clientTypeCategory', 'course', 'items.itemSubcategory'])
                ->whereIn('client_type_slug', self::ALLOWED_CLIENT_SLUGS)
                ->find($id);
            $isDateRange = (bool) $bill;
            if (!$bill) {
                $bill = KitchenIssueMaster::with(['store', 'subStore', 'clientTypeCategory', 'course', 'items.itemSubcategory'])
                    ->whereIn('client_type', self::ALLOWED_KITCHEN_CLIENT_TYPES)
                    ->whereIn('kitchen_issue_type', self::KITCHEN_MESS_SELLING_ISSUE_TYPES)
                    ->where('pk', $id)
                    ->firstOrFail();
            }
        }

        $this->assertCurrentUserCanAccessSingleBill($bill, $isDateRange);

        $totalAmount = $this->roundMoney((float) $bill->net_total);
        $paidAmount = $this->getBillPaidAmount($bill, $isDateRange);
        $dueAmount = $this->billDueAmount($totalAmount, $paidAmount);
        $paymentStatusLabel = $this->isBillFullyPaid($paidAmount, $totalAmount) ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Unpaid');

        $invoiceNo = $isDateRange
            ? 'DR-' . str_pad($bill->id, 6, '0', STR_PAD_LEFT)
            : 'SV-' . str_pad($bill->pk ?? $bill->id, 6, '0', STR_PAD_LEFT);
        $receiptNo = $invoiceNo;

        $singleBuyerName = trim((string) ($bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '')));
        $singleClientTypeSlug = $isDateRange
            ? (string) ($bill->client_type_slug ?? 'employee')
            : $this->getBillClientTypeSlug($bill);
        $singleDateToYmd = $request->filled('date_to')
            ? $this->parseDate($request->date_to)
            : ($bill->issue_date
                ? ($bill->issue_date instanceof Carbon ? $bill->issue_date->format('Y-m-d') : Carbon::parse($bill->issue_date)->format('Y-m-d'))
                : now()->format('Y-m-d'));
        $totalDueAmount = $singleBuyerName !== ''
            ? $this->computeCombinedBillFinancials($singleBuyerName, $singleClientTypeSlug, null, $singleDateToYmd)['due']
            : $dueAmount;

        $displayPaid = $paymentOnly
            ? ($receiptPaymentAmount ?? $paidAmount)
            : $paidAmount;

        return view('admin.mess.process-mess-bills-employee.print-receipt', [
            'bill' => $bill,
            'paidAmount' => $displayPaid,
            'dueAmount' => $dueAmount,
            'totalDueAmount' => $totalDueAmount,
            'paymentStatusLabel' => $paymentStatusLabel,
            'invoiceNo' => $invoiceNo,
            'receiptNo' => $receiptNo,
            'paymentOnly' => $paymentOnly,
            'receiptPaymentAmount' => $paymentOnly ? $displayPaid : null,
        ]);
    }

    /**
     * Resolve combined bill id (combined-{urlencoded_name}-{slug}) to list of bills for the buyer in date range.
     * Request should contain date_from and date_to (Y-m-d or d-m-Y).
     */
    private function resolveCombinedBillBills(Request $request, string $combinedId): array
    {
        if (strpos($combinedId, 'combined-') !== 0) {
            return [];
        }
        $rest = substr($combinedId, strlen('combined-'));
        $lastDash = strrpos($rest, '-');
        if ($lastDash === false) {
            return [];
        }
        $buyerName = rawurldecode(substr($rest, 0, $lastDash));
        $clientTypeSlug = substr($rest, $lastDash + 1);
        $dateFrom = $request->filled('date_from') ? $this->parseDate($request->date_from) : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $this->parseDate($request->date_to) : now()->endOfMonth()->format('Y-m-d');

        $drBillQuery = SellingVoucherDateRangeReport::with([
            'store',
            'clientTypeCategory',
            'course',
            'items' => function ($itemQ) use ($dateFrom, $dateTo) {
                $this->applySvDateRangeReportItemsIssueDateConstraint($itemQ, $dateFrom, $dateTo);
            },
            'items.itemSubcategory',
        ])
            ->where('client_type_slug', $clientTypeSlug)
            ->whereIn('status', $this->sellingVoucherDateRangeReportSaleVoucherStatuses());
        $this->applyMessBillBuyerScope($drBillQuery, $buyerName, $clientTypeSlug);
        $this->applySellingVoucherDateRangeItemIssueDateFilter($drBillQuery, $dateFrom, $dateTo);
        $dateRangeBills = $drBillQuery->orderBy('issue_date')->get();

        $kitchenBillQuery = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'course', 'items'])
            ->whereIn('kitchen_issue_type', self::KITCHEN_MESS_SELLING_ISSUE_TYPES)
            ->where('client_type', $this->clientTypeSlugToKitchenId($clientTypeSlug))
            ->where('issue_date', '>=', $dateFrom)
            ->where('issue_date', '<=', $dateTo);
        $this->applyMessBillBuyerScope($kitchenBillQuery, $buyerName, $clientTypeSlug);
        $kitchenBills = $kitchenBillQuery->orderBy('issue_date')->get();

        return $dateRangeBills->concat($kitchenBills)->sortBy('issue_date')->values()->all();
    }

    /**
     * Return JSON for the Payment Details modal (bill receipt view).
     * Used when user clicks "Payment" to show full bill and then Pay Now / Print / Cancel.
     * Supports combined bill id (combined-{name}-{slug}) with date_from, date_to query params.
     */
    public function paymentDetails(Request $request, $id)
    {
        if (is_string($id) && strpos($id, 'combined-') === 0) {
            $bills = $this->resolveCombinedBillBills($request, $id);
            if (empty($bills)) {
                return response()->json(['error' => 'No bills found for this buyer in the selected date range.'], 404);
            }
            if (!$this->currentUserCanAdminMessBills()) {
                $rid = $this->resolveReceiverUserIdFromAnyBill($bills);
                $uid = (int) (auth()->user()->user_id ?? 0);
                if ($rid === null || $rid <= 0 || (int) $rid !== $uid) {
                    return response()->json(['error' => 'You do not have access to this bill.'], 403);
                }
            }
            $filterDateFromYmd = $request->filled('date_from') ? $this->parseDate($request->date_from) : now()->startOfMonth()->format('Y-m-d');
            $filterDateToYmd = $request->filled('date_to') ? $this->parseDate($request->date_to) : now()->endOfMonth()->format('Y-m-d');
            $dateFromStr = Carbon::parse($filterDateFromYmd)->format('d-m-Y');
            $dateToStr = Carbon::parse($filterDateToYmd)->format('d-m-Y');
            $items = [];
            $clientTypeDisplay = '';
            $storeNames = [];
            $buyerName = trim((string) ($bills[0]->client_name ?? ($bills[0]->clientTypeCategory->client_name ?? '—')));
            $clientTypeSlug = $bills[0] instanceof SellingVoucherDateRangeReport
                ? (string) ($bills[0]->client_type_slug ?? 'employee')
                : $this->getBillClientTypeSlug($bills[0]);
            $financials = $this->computeCombinedBillFinancials($buyerName, $clientTypeSlug, $filterDateFromYmd, $filterDateToYmd);
            $totalAmount = $financials['total'];
            $paidAmount = $financials['paid'];
            foreach ($bills as $bill) {
                $storeName = $bill->resolved_store_name ?? '—';
                $storeNames[$storeName] = true;
                $purchaseDate = $bill->issue_date ? $bill->issue_date->format('d-m-Y') : '—';
                if ($clientTypeDisplay === '') {
                    $clientTypeDisplay = $bill->client_type_display ?? ($bill->client_type_label ?? ($bill->clientTypeCategory ? ucfirst($bill->clientTypeCategory->client_type ?? '') : '—'));
                }
                foreach ($bill->items ?? [] as $item) {
                    $itemIssueDate = null;
                    try {
                        if (isset($item->issue_date) && $item->issue_date) {
                            $idt = $item->issue_date instanceof Carbon
                                ? $item->issue_date
                                : Carbon::parse($item->issue_date);
                            $itemIssueDate = $idt->format('d-m-Y');
                        }
                    } catch (\Throwable $e) {
                        $itemIssueDate = null;
                    }
                    $items[] = [
                        'store_name' => $storeName,
                        'item_name' => $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                        'issue_date' => $itemIssueDate ?: $purchaseDate,
                        'price' => number_format($item->rate ?? 0, 1),
                        'quantity' => $item->quantity,
                        'amount' => number_format($item->amount ?? 0, 2),
                    ];
                }
            }
            $dueAmount = $financials['due'];
            $totalDueAmount = $this->computeCombinedBillFinancials($buyerName, $clientTypeSlug, null, $filterDateToYmd)['due'];
            $combinedInvoiceNo = $this->generateCombinedInvoiceNo($buyerName, $clientTypeSlug);

            // Collect header-level meta fields
            $referenceNumbers = collect($bills)->pluck('reference_number')->filter()->unique()->values();
            $orderBys = collect($bills)->pluck('order_by')->filter()->unique()->values();
            $remarksList = collect($bills)->pluck('remarks')->filter()->unique()->values();
            $referenceNumber = $referenceNumbers->implode(', ');
            $orderBy = $orderBys->implode(', ');
            $remarks = $remarksList->implode(' | ');

            $courseName = null;
            try {
                $first = $bills[0];
                if ($first instanceof SellingVoucherDateRangeReport) {
                    if (in_array($first->client_type_slug ?? '', ['ot', 'course'], true)) {
                        $courseName = optional($first->course)->course_name;
                    }
                } elseif ($first instanceof KitchenIssueMaster) {
                    if (in_array((int) ($first->client_type ?? 0), [KitchenIssueMaster::CLIENT_OT, KitchenIssueMaster::CLIENT_COURSE], true)) {
                        $courseName = optional($first->course)->course_name;
                    }
                }
            } catch (\Throwable $e) {
                $courseName = null;
            }
            $clientNameCourse = $courseName ? trim($buyerName . ' – ' . $courseName) : $buyerName;

            $firstReceiptId = $bills[0] instanceof SellingVoucherDateRangeReport
                ? 'dr-' . $bills[0]->id
                : 'ki-' . ($bills[0]->pk ?? $bills[0]->id);

            return response()->json([
                'bill_id' => $id,
                'receipt_no' => $combinedInvoiceNo,
                'invoice_no' => $combinedInvoiceNo,
                'client_name' => $buyerName,
                'client_name_course' => $clientNameCourse,
                'client_type' => $clientTypeDisplay,
                'date_from' => $dateFromStr,
                'date_to' => $dateToStr,
                'store_name' => implode(', ', array_keys($storeNames)),
                'items' => $items,
                'total_amount' => number_format($totalAmount, 1),
                'paid_amount' => number_format($paidAmount, 1),
                'due_amount' => number_format($dueAmount, 1),
                'due_amount_raw' => $dueAmount,
                'total_due_amount' => number_format($totalDueAmount, 1),
                'total_due_amount_raw' => $totalDueAmount,
                'first_receipt_id' => $firstReceiptId,
                'reference_number' => $referenceNumber ?: null,
                'order_by' => $orderBy ?: null,
                'remarks' => $remarks ?: null,
                'course_name' => $courseName,
            ]);
        }

        [$bill, $isDateRange] = $this->resolveBillById($id);

        if (!$this->currentUserCanAdminMessBills()) {
            $isKitchen = !$isDateRange;
            $rid = $this->getReceiverUserIdForBill($bill, $isKitchen);
            $uid = (int) (auth()->user()->user_id ?? 0);
            if ($rid === null || $rid <= 0 || (int) $rid !== $uid) {
                return response()->json(['error' => 'You do not have access to this bill.'], 403);
            }
        }

        $storeName = $bill->resolved_store_name ?? '—';
        $rawClientName = $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—');
        $courseName = null;
        try {
            if ($isDateRange) {
                if (in_array($bill->client_type_slug ?? '', ['ot', 'course'], true)) {
                    $courseName = optional($bill->course)->course_name;
                }
            } else {
                if (in_array((int) ($bill->client_type ?? 0), [KitchenIssueMaster::CLIENT_OT, KitchenIssueMaster::CLIENT_COURSE], true)) {
                    $courseName = optional($bill->course)->course_name;
                }
            }
        } catch (\Throwable $e) {
            $courseName = null;
        }
        $clientNameCourse = $courseName ? trim($rawClientName . ' – ' . $courseName) : $rawClientName;

        $dateFrom = isset($bill->date_from) && $bill->date_from
            ? Carbon::parse($bill->date_from)->format('d-m-Y')
            : ($bill->issue_date ? $bill->issue_date->format('d-m-Y') : '—');
        $dateTo = isset($bill->date_to) && $bill->date_to
            ? Carbon::parse($bill->date_to)->format('d-m-Y')
            : ($bill->issue_date ? $bill->issue_date->format('d-m-Y') : '—');

        $purchaseDate = $bill->issue_date ? $bill->issue_date->format('d-m-Y') : $dateFrom;
        $items = [];
        foreach ($bill->items ?? [] as $item) {
            $items[] = [
                'store_name' => $storeName,
                'item_name' => $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                'purchase_date' => $purchaseDate,
                'price' => number_format($item->rate ?? 0, 1),
                'quantity' => $item->quantity,
                'amount' => number_format($item->amount ?? 0, 2),
            ];
        }

        $totalAmount = $this->roundMoney((float) $bill->net_total);
        $paidAmount = $this->getBillPaidAmount($bill, $isDateRange);
        $dueAmount = $this->billDueAmount($totalAmount, $paidAmount);

        $clientTypeDisplay = $bill->client_type_display ?? ($bill->client_type_label ?? ($bill->clientTypeCategory ? ucfirst($bill->clientTypeCategory->client_type ?? '') : ucfirst($bill->client_type_slug ?? '—')));

        $invoiceNo = $isDateRange
            ? 'DR-' . str_pad($bill->id, 6, '0', STR_PAD_LEFT)
            : 'SV-' . str_pad($bill->pk ?? $bill->id, 6, '0', STR_PAD_LEFT);
        $receiptNo = $invoiceNo;

        $singleBuyerName = trim((string) ($bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '')));
        $singleClientTypeSlug = $isDateRange
            ? (string) ($bill->client_type_slug ?? 'employee')
            : $this->getBillClientTypeSlug($bill);
        $singleDateToYmd = $request->filled('date_to')
            ? $this->parseDate($request->date_to)
            : ($bill->issue_date
                ? ($bill->issue_date instanceof Carbon ? $bill->issue_date->format('Y-m-d') : Carbon::parse($bill->issue_date)->format('Y-m-d'))
                : now()->format('Y-m-d'));
        $totalDueAmount = $singleBuyerName !== ''
            ? $this->computeCombinedBillFinancials($singleBuyerName, $singleClientTypeSlug, null, $singleDateToYmd)['due']
            : $dueAmount;

        return response()->json([
            'bill_id' => $isDateRange ? 'dr-' . $bill->id : 'ki-' . $bill->pk,
            'receipt_no' => $receiptNo,
            'invoice_no' => $invoiceNo,
            'client_name' => $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—'),
            'client_name_course' => $clientNameCourse,
            'client_type' => $clientTypeDisplay,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'store_name' => $storeName,
            'items' => $items,
            'total_amount' => number_format($totalAmount, 1),
            'paid_amount' => number_format($paidAmount, 1),
            'due_amount' => number_format($dueAmount, 1),
            'due_amount_raw' => $dueAmount,
            'total_due_amount' => number_format($totalDueAmount, 1),
            'total_due_amount_raw' => $totalDueAmount,
            'reference_number' => $bill->reference_number ?? null,
            'order_by' => $bill->order_by ?? null,
            'remarks' => $bill->remarks ?? null,
        ]);
    }

    /**
     * Sort modal bill groups (buyer + client type) before pagination.
     *
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>  $groupedRows
     */
    private function sortModalBillGroupedRows(Collection $groupedRows, string $sortColumn, string $sortDir, array $paymentTypeMap): Collection
    {
        $allowed = ['sno', 'buyer_name', 'invoice_no', 'payment_type', 'total', 'status'];
        if (! in_array($sortColumn, $allowed, true)) {
            $sortColumn = 'buyer_name';
        }
        $desc = strtolower($sortDir) === 'desc';

        if ($sortColumn === 'sno') {
            return $desc ? $groupedRows->reverse()->values() : $groupedRows->values();
        }

        return $groupedRows->sortBy(function ($group) use ($sortColumn, $paymentTypeMap) {
            $first = $group->first();
            $buyerName = trim((string) ($first->client_name ?? ''));
            $clientTypeSlug = (string) ($first->client_type_slug ?? 'employee');
            $invoiceNo = $this->generateCombinedInvoiceNo($buyerName, $clientTypeSlug);

            if ($sortColumn === 'buyer_name') {
                return mb_strtolower($buyerName);
            }
            if ($sortColumn === 'invoice_no') {
                return mb_strtolower($invoiceNo);
            }
            if ($sortColumn === 'payment_type') {
                return mb_strtolower((string) ($paymentTypeMap[$first->payment_type ?? 1] ?? ''));
            }
            if ($sortColumn === 'total') {
                return (float) $group->sum(function ($bill) {
                    return (float) ($bill->total_amount ?? 0);
                });
            }

            return mb_strtolower($invoiceNo);
        }, SORT_REGULAR, $desc)->values();
    }

    /**
     * Sort modal rows (cached arrays or combined objects) before pagination.
     *
     * @param  \Illuminate\Support\Collection<int, object|array<string, mixed>>  $combinedRows
     */
    private function sortModalCombinedRows(Collection $combinedRows, string $sortColumn, string $sortDir): Collection
    {
        $allowed = ['sno', 'buyer_name', 'invoice_no', 'payment_type', 'total', 'status'];
        if (! in_array($sortColumn, $allowed, true)) {
            $sortColumn = 'buyer_name';
        }
        $desc = strtolower($sortDir) === 'desc';

        if ($sortColumn === 'sno') {
            return $desc ? $combinedRows->reverse()->values() : $combinedRows->values();
        }

        return $combinedRows->sortBy(function ($row) use ($sortColumn) {
            $isArray = is_array($row);
            if ($sortColumn === 'buyer_name') {
                return mb_strtolower((string) ($isArray ? ($row['buyer_name'] ?? '') : ($row->buyer_name ?? '')));
            }
            if ($sortColumn === 'invoice_no' || $sortColumn === 'status') {
                $invoiceNo = $isArray
                    ? ($row['invoice_no'] ?? '')
                    : ($row->combined_invoice_no ?? '');

                return mb_strtolower((string) $invoiceNo);
            }
            if ($sortColumn === 'payment_type') {
                return mb_strtolower((string) ($isArray ? ($row['payment_type'] ?? '') : ($row->payment_type ?? '')));
            }
            if ($sortColumn === 'total') {
                return (float) ($isArray ? ($row['total'] ?? 0) : ($row->total ?? 0));
            }

            return mb_strtolower((string) ($isArray ? ($row['buyer_name'] ?? '') : ($row->buyer_name ?? '')));
        }, SORT_REGULAR, $desc)->values();
    }

    /**
     * Return JSON data for the ADD modal table (employee bills for date range).
     * Includes approved SV/kitchen vouchers (same as main index); keeps buyers with period due > 0.
     */
    public function modalData(Request $request)
    {
        $dateFrom = $request->filled('date_from')
            ? ($this->parseDate($request->date_from) ?? now()->startOfMonth()->format('Y-m-d'))
            : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to')
            ? ($this->parseDate($request->date_to) ?? now()->endOfMonth()->format('Y-m-d'))
            : now()->endOfMonth()->format('Y-m-d');
        $clientTypes = $this->normalizeFilterArrayValues($request->input('client_type'));
        $clientTypePks = $this->normalizeFilterArrayValues($request->input('client_type_pk'));
        $buyerNames = $this->normalizeBuyerNames($request->input('buyer_name'));
        $buyerNames = $this->normalizeBuyerNamesToClientIds($buyerNames, $clientTypes, $clientTypePks);
        $forPrint = $request->boolean('for_print') || $request->input('for_print') === '1';
        $page = max(1, (int) $request->input('page', 1));
        $perPage = (int) $request->input('per_page', 10);
        if ($forPrint) {
            $page = 1;
            $perPage = min(max(1, $perPage), 10000);
        } elseif ($perPage < 1 || $perPage > 100) {
            $perPage = 10;
        }
        $search = trim((string) $request->input('search', ''));
        $sortColumn = (string) $request->input('sort_column', 'buyer_name');
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc'));
        $unionCollation = 'utf8mb4_unicode_ci';

        // Query 1: Selling Voucher with Date Range
        $dateRangeSlugs = !empty($clientTypes) ? $clientTypes : self::ALLOWED_CLIENT_SLUGS;
        $dateRangeQuery = SellingVoucherDateRangeReport::query()
            ->select([
                'id',
                DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
                'issue_date',
                DB::raw("CONVERT(client_type_slug USING utf8mb4) COLLATE {$unionCollation} as client_type_slug"),
                'client_type_pk',
                'payment_type',
                'status',
                DB::raw("CONVERT('date_range' USING utf8mb4) COLLATE {$unionCollation} as source_type"),
            ])
            ->whereIn('client_type_slug', $dateRangeSlugs)
            ->whereIn('status', $this->sellingVoucherDateRangeReportSaleVoucherStatuses());

        if (!empty($clientTypePks)) {
            $dateRangeQuery->whereIn('client_type_pk', $clientTypePks);
        }
        $this->applyBuyerNameFilter($dateRangeQuery, $buyerNames, $clientTypes, $clientTypePks);
        $this->applySellingVoucherDateRangeItemIssueDateFilter($dateRangeQuery, $dateFrom, $dateTo);

        // Query 2: Regular Selling Voucher (Kitchen Issue)
        $kitchenClientTypes = !empty($clientTypes)
            ? array_map([$this, 'clientTypeSlugToKitchenId'], $clientTypes)
            : self::ALLOWED_KITCHEN_CLIENT_TYPES;
        $kitchenIssueQuery = KitchenIssueMaster::query()
            ->select([
                'pk as id',
                DB::raw("CONVERT(client_name USING utf8mb4) COLLATE {$unionCollation} as client_name"),
                'issue_date',
                DB::raw("CONVERT((CASE client_type WHEN 1 THEN 'employee' WHEN 2 THEN 'ot' WHEN 3 THEN 'course' WHEN 4 THEN 'other' WHEN 5 THEN 'section' END) USING utf8mb4) COLLATE {$unionCollation} as client_type_slug"),
                'client_type_pk',
                'payment_type',
                'status',
                DB::raw("CONVERT('kitchen_issue' USING utf8mb4) COLLATE {$unionCollation} as source_type"),
            ])
            ->whereIn('client_type', $kitchenClientTypes)
            ->whereIn('kitchen_issue_type', self::KITCHEN_MESS_SELLING_ISSUE_TYPES)
            ->where('status', '!=', KitchenIssueMaster::STATUS_REJECTED);

        if (!empty($clientTypePks)) {
            $kitchenIssueQuery->whereIn('client_type_pk', $clientTypePks);
        }
        $this->applyBuyerNameFilter($kitchenIssueQuery, $buyerNames, $clientTypes, $clientTypePks);
        if ($dateFrom) {
            $kitchenIssueQuery->where('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $kitchenIssueQuery->where('issue_date', '<=', $dateTo);
        }

        $groupedRows = $this->getModalBillsDatasetCached(
            $dateFrom,
            $dateTo,
            $dateRangeQuery,
            $kitchenIssueQuery,
            $clientTypes,
            $clientTypePks,
            $buyerNames
        );

        $searchTokens = DataTableSearchHelper::tokens($search);
        if ($searchTokens !== []) {
            $groupedRows = $groupedRows->filter(function (array $row) use ($searchTokens) {
                $haystack = trim((string) ($row['buyer_name'] ?? '')) . ' '
                    . (string) ($row['invoice_no'] ?? '') . ' '
                    . (string) ($row['payment_type'] ?? '');

                return DataTableSearchHelper::haystackMatchesAllTokens($haystack, $searchTokens);
            })->values();
        }

        $groupedRows = $this->sortModalCombinedRows($groupedRows, $sortColumn, $sortDir);

        $total = $groupedRows->count();
        if ($total > 0 && (($page - 1) * $perPage) >= $total) {
            $page = (int) ceil($total / $perPage);
        }
        $offset = ($page - 1) * $perPage;
        $pageGroups = $groupedRows->slice($offset, $perPage)->values();
        if (! $forPrint || $pageGroups->count() <= 100) {
            $pageGroups = $this->enrichModalPageRowsLifetimeDue($pageGroups, $dateTo);
        }

        $rows = $pageGroups->map(function (array $row, int $index) use ($offset) {
            return $this->formatModalBillRowForJson($row, $offset + $index + 1);
        })->values();

        return response()->json([
            'bills' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    /**
     * Generate invoice and send notification to user.
     * For combined bill id, sends one notification for the combined bill (use date_from, date_to from request).
     */
    public function generateInvoice(Request $request, $id)
    {
        if (is_string($id) && strpos($id, 'combined-') === 0) {
            $bills = $this->resolveCombinedBillBills($request, $id);
            if (empty($bills)) {
                return response()->json(['success' => false, 'message' => 'No bills found for this buyer in the selected date range.'], 404);
            }
            $first = $bills[0];
            $receiverUserId = $this->resolveReceiverUserIdFromAnyBill($bills);
            if ($receiverUserId === null || $receiverUserId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice generated, but notification could not be sent because user mapping was not found.',
                ], 422);
            }
            $dateFromYmd = $request->filled('date_from')
                ? $this->parseDate($request->date_from)
                : now()->startOfMonth()->format('Y-m-d');
            $dateToYmd = $request->filled('date_to')
                ? $this->parseDate($request->date_to)
                : now()->endOfMonth()->format('Y-m-d');
            $allLineKeys = $this->collectMessBillLineItemKeys($bills);
            $notifiedLineKeys = $this->getMessCombinedNotifiedLineItemKeys(
                (int) $receiverUserId,
                (string) $id,
                $dateFromYmd,
                $dateToYmd,
                $bills
            );
            $notifiedSet = array_fill_keys($notifiedLineKeys, true);
            $pendingLineKeys = [];
            $notifiedAmongCurrent = 0;
            foreach ($allLineKeys as $key) {
                if (isset($notifiedSet[$key])) {
                    $notifiedAmongCurrent++;
                } else {
                    $pendingLineKeys[] = $key;
                }
            }
            if ($pendingLineKeys === []) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already sent invoice for all items in this date range.',
                ], 422);
            }
            $isFollowUp = $notifiedAmongCurrent > 0;
            $visibleMessage = $isFollowUp
                ? 'New mess charges have been added to your bill. Please review and pay.'
                : 'Your combined mess bill is pending. Please review and pay via Process Mess Bills.';
            $invoiceMessage = NotificationService::appendMessCombinedReceiptPayload(
                $visibleMessage,
                $id,
                $dateFromYmd,
                $dateToYmd,
                null,
                $pendingLineKeys
            );
            try {
                app(NotificationService::class)->create(
                    $receiverUserId,
                    'mess',
                    'MessInvoiceCombined',
                    0,
                    'Mess Payment Pending',
                    $invoiceMessage
                );
            } catch (\Throwable $e) {
                report($e);
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice generated, but notification could not be saved.',
                ], 500);
            }
            $clientName = trim((string) ($first->client_name ?? ($first->clientTypeCategory->client_name ?? '—')));
            $this->bumpProcessMessBillsCombinedCache();

            return response()->json([
                'success' => true,
                'message' => $isFollowUp
                    ? 'Invoice notification sent for ' . count($pendingLineKeys) . ' new item(s).'
                    : 'Invoice notification sent for combined bill.',
                'bill_id' => $id,
                'client_name' => $clientName,
                'pending_items_notified' => count($pendingLineKeys),
            ]);
        }

        [$bill, $isDateRange] = $this->resolveBillById($id);
        $isKitchenIssue = !$isDateRange;
        $billId = $bill->id ?? $bill->pk;
        $receiverUserId = $this->getReceiverUserIdForBill($bill, $isKitchenIssue);
        if ($receiverUserId === null || $receiverUserId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice generated, but notification could not be sent because user mapping was not found.',
            ], 422);
        }
        if ($this->messSingleInvoiceAlreadySent((int) $receiverUserId, (int) $billId)) {
            return response()->json([
                'success' => false,
                'message' => 'Already sent invoice.',
            ], 422);
        }
        try {
            app(NotificationService::class)->create(
                $receiverUserId,
                'mess',
                'MessInvoice',
                (int) $billId,
                'Mess Payment Pending',
                'Your mess payment is pending. Please review your invoice.'
            );
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Invoice generated, but notification could not be saved.',
            ], 500);
        }

        $this->bumpProcessMessBillsCombinedCache();

        return response()->json([
            'success' => true,
            'message' => 'Invoice generated and notification sent successfully!',
            'bill_id' => $billId,
            'client_name' => $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—'),
        ]);
    }

    /**
     * Resolve bill by id (numeric or composite 'dr-123' / 'ki-123').
     * Returns [bill model, isDateRange].
     */
    private function resolveBillById($id): array
    {
        $numericId = $id;
        $preferDateRange = null;
        if (is_string($id) && preg_match('/^(dr|ki)-(\d+)$/i', $id, $m)) {
            $preferDateRange = (strtolower($m[1]) === 'dr');
            $numericId = (int) $m[2];
        }

        if ($preferDateRange === true) {
            $bill = SellingVoucherDateRangeReport::with(['store', 'subStore', 'clientTypeCategory', 'items'])
                ->whereIn('client_type_slug', self::ALLOWED_CLIENT_SLUGS)
                ->findOrFail($numericId);
            return [$bill, true];
        }
        if ($preferDateRange === false) {
            $bill = KitchenIssueMaster::with(['store', 'subStore', 'clientTypeCategory', 'items'])
                ->whereIn('client_type', self::ALLOWED_KITCHEN_CLIENT_TYPES)
                ->whereIn('kitchen_issue_type', self::KITCHEN_MESS_SELLING_ISSUE_TYPES)
                ->where('pk', $numericId)
                ->firstOrFail();
            return [$bill, false];
        }

        $bill = SellingVoucherDateRangeReport::with(['store', 'subStore', 'clientTypeCategory', 'items'])
            ->whereIn('client_type_slug', self::ALLOWED_CLIENT_SLUGS)
            ->find($numericId);
        if ($bill) {
            return [$bill, true];
        }
        $bill = KitchenIssueMaster::with(['store', 'subStore', 'clientTypeCategory', 'items'])
            ->whereIn('client_type', self::ALLOWED_KITCHEN_CLIENT_TYPES)
            ->whereIn('kitchen_issue_type', self::KITCHEN_MESS_SELLING_ISSUE_TYPES)
            ->where('pk', $numericId)
            ->firstOrFail();
        return [$bill, false];
    }

    /**
     * Resolve receiver user_id (user_credentials.user_id) for the bill's buyer for notifications.
     *
     * Employee: user_credentials.user_id = employee_master.pk (from client_id or by name lookup).
     * OT/Course (Kitchen only): user_credentials.user_id = student_master.pk (client_id); students use user_category='S'.
     * Returns null if the buyer cannot be mapped to a single user.
     */
    private function getReceiverUserIdForBill($bill, bool $isKitchenIssue): ?int
    {
        $categoryName = null;
        if ($bill instanceof KitchenIssueMaster || $bill instanceof SellingVoucherDateRangeReport) {
            $categoryName = $bill->clientTypeCategory->client_name ?? null;
        } elseif (isset($bill->client_type_category)) {
            $categoryName = $bill->client_type_category->client_name ?? null;
        }

        if ($isKitchenIssue) {
            $clientType = (int) ($bill->client_type ?? 0);
            $clientId = isset($bill->client_id) ? (int) $bill->client_id : null;

            // Employee (1): client_id = employee_master.pk = user_credentials.user_id
            if ($clientType === KitchenIssueMaster::CLIENT_EMPLOYEE) {
                if ($clientId > 0) {
                    return $clientId;
                }
                $clientName = trim($bill->client_name ?? ($categoryName ?? ''));
                return $clientName !== '' ? $this->resolveReceiverUserIdByClientName($clientName) : null;
            }

            // OT (2) / Course (3): client_id = student_master.pk = user_credentials.user_id (user_category='S')
            if (in_array($clientType, [KitchenIssueMaster::CLIENT_OT, KitchenIssueMaster::CLIENT_COURSE], true) && $clientId > 0) {
                return $clientId;
            }

            // Other: often a named buyer without student/employee pk — try employee directory by name
            if ($clientType === KitchenIssueMaster::CLIENT_OTHER) {
                $clientName = trim($bill->client_name ?? ($categoryName ?? ''));
                return $clientName !== '' ? $this->resolveReceiverUserIdByClientName($clientName) : null;
            }

            return null;
        }

        // Selling Voucher Date Range: map buyer name to app user (employee or student by type)
        $slug = (string) ($bill->client_type_slug ?? '');
        $clientName = trim($bill->client_name ?? ($categoryName ?? ''));
        if ($clientName === '') {
            return null;
        }

        if ($slug === 'employee' || $slug === 'other') {
            return $this->resolveReceiverUserIdByClientName($clientName);
        }

        if (in_array($slug, ['ot', 'course'], true)) {
            return $this->resolveReceiverUserIdByStudentName($clientName);
        }

        return null;
    }

    /**
     * For combined mess bills: use the first bill that maps to a notification receiver.
     *
     * @param  array<int, KitchenIssueMaster|SellingVoucherDateRangeReport>|\Illuminate\Support\Collection  $bills
     */
    private function resolveReceiverUserIdFromAnyBill($bills): ?int
    {
        $list = is_array($bills) ? $bills : $bills->all();
        foreach ($list as $bill) {
            $isKitchen = $bill instanceof KitchenIssueMaster
                || (($bill->source_type ?? '') === 'kitchen_issue');
            $uid = $this->getReceiverUserIdForBill($bill, $isKitchen);
            if ($uid !== null && $uid > 0) {
                return $uid;
            }
        }

        return null;
    }

    /**
     * Normalized full name expression (matches typical "First Last" bill text without double spaces when middle is empty).
     *
     * @param  'e'|'s'  $tableAlias  employee_master or student_master alias
     */
    private function sqlNormalizedPersonFullName(string $tableAlias): string
    {
        return "TRIM(CONCAT_WS(' ', NULLIF(TRIM({$tableAlias}.first_name), ''), NULLIF(TRIM({$tableAlias}.middle_name), ''), NULLIF(TRIM({$tableAlias}.last_name), '')))";
    }

    /**
     * Resolve user_credentials.user_id from buyer client name (employee full name).
     * Tries exact match first, then LIKE match; returns null if no single match.
     */
    private function resolveReceiverUserIdByClientName(string $clientName): ?int
    {
        $candidates = [
            trim($clientName),
            $this->sanitizeBuyerNameForUserLookup($clientName),
        ];
        $candidates = array_values(array_unique(array_filter($candidates, fn ($name) => $name !== '')));

        $empNameExpr = $this->sqlNormalizedPersonFullName('e');

        foreach ($candidates as $candidateName) {
            $escaped = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $candidateName);

            $row = DB::table('user_credentials as uc')
                ->join('employee_master as e', 'uc.user_id', '=', 'e.pk')
                ->whereRaw("{$empNameExpr} = ?", [$candidateName])
                ->where('uc.user_category', '!=', 'S')
                ->value('uc.user_id');
            if ($row !== null) {
                return (int) $row;
            }

            $normCandidate = preg_replace('/\s+/u', ' ', $candidateName) ?? $candidateName;
            $row = DB::table('user_credentials as uc')
                ->join('employee_master as e', 'uc.user_id', '=', 'e.pk')
                ->whereRaw("{$empNameExpr} = ?", [trim($normCandidate)])
                ->where('uc.user_category', '!=', 'S')
                ->value('uc.user_id');
            if ($row !== null) {
                return (int) $row;
            }

            $row = DB::table('user_credentials as uc')
                ->join('employee_master as e', 'uc.user_id', '=', 'e.pk')
                ->whereRaw("{$empNameExpr} LIKE ?", [$escaped . '%'])
                ->where('uc.user_category', '!=', 'S')
                ->value('uc.user_id');
            if ($row !== null) {
                return (int) $row;
            }

            if (Schema::hasColumn('user_credentials', 'name')) {
                $row = DB::table('user_credentials')
                    ->where('name', $candidateName)
                    ->where('user_category', '!=', 'S')
                    ->value('user_id');
                if ($row !== null) {
                    return (int) $row;
                }
            }
        }

        return null;
    }

    /**
     * Resolve student portal user (user_credentials.user_id = student_master.pk, user_category S) from buyer name.
     */
    private function resolveReceiverUserIdByStudentName(string $clientName): ?int
    {
        if (!Schema::hasTable('student_master')) {
            return null;
        }

        $candidates = [
            trim($clientName),
            $this->sanitizeBuyerNameForUserLookup($clientName),
        ];
        $candidates = array_values(array_unique(array_filter($candidates, fn ($name) => $name !== '')));

        $stuNameExpr = $this->sqlNormalizedPersonFullName('s');

        foreach ($candidates as $candidateName) {
            $escaped = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $candidateName);

            $row = DB::table('user_credentials as uc')
                ->join('student_master as s', 'uc.user_id', '=', 's.pk')
                ->where('uc.user_category', 'S')
                ->whereRaw("{$stuNameExpr} = ?", [$candidateName])
                ->value('uc.user_id');
            if ($row !== null) {
                return (int) $row;
            }

            $normCandidate = preg_replace('/\s+/u', ' ', $candidateName) ?? $candidateName;
            $row = DB::table('user_credentials as uc')
                ->join('student_master as s', 'uc.user_id', '=', 's.pk')
                ->where('uc.user_category', 'S')
                ->whereRaw("{$stuNameExpr} = ?", [trim($normCandidate)])
                ->value('uc.user_id');
            if ($row !== null) {
                return (int) $row;
            }

            $row = DB::table('user_credentials as uc')
                ->join('student_master as s', 'uc.user_id', '=', 's.pk')
                ->where('uc.user_category', 'S')
                ->whereRaw("{$stuNameExpr} LIKE ?", [$escaped . '%'])
                ->value('uc.user_id');
            if ($row !== null) {
                return (int) $row;
            }

            if (Schema::hasColumn('student_master', 'display_name')) {
                $row = DB::table('user_credentials as uc')
                    ->join('student_master as s', 'uc.user_id', '=', 's.pk')
                    ->where('uc.user_category', 'S')
                    ->where('s.display_name', $candidateName)
                    ->value('uc.user_id');
                if ($row !== null) {
                    return (int) $row;
                }
            }
        }

        return null;
    }

    /**
     * Normalize buyer display name to improve employee mapping from user_credentials.
     */
    private function sanitizeBuyerNameForUserLookup(string $name): string
    {
        $normalized = (string) $name;

        // Remove nested parenthesized suffixes safely, e.g. "AWADH DABAS (Training (Induction))".
        do {
            $before = $normalized;
            $normalized = preg_replace('/\([^()]*\)/', '', $normalized) ?? $normalized;
        } while ($normalized !== $before);

        // If malformed text leaves stray brackets, strip those too.
        $normalized = str_replace(['(', ')'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        return trim((string) $normalized);
    }

    /**
     * Round money to 2 decimals (avoids float precision issues in payment totals/comparisons).
     */
    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }

    /**
     * Remaining due on a bill (never negative, 2 decimals).
     */
    private function billDueAmount(float $totalAmount, float $paidAmount): float
    {
        return max(0, $this->roundMoney($totalAmount - $paidAmount));
    }

    /**
     * Preload FIFO allocation bills for many buyers in a few queries (process index lifetime due).
     *
     * @param  \Illuminate\Support\Collection<int, array{name: string, slug: string}>  $buyerKeys
     */
    private function preloadBuyerBillsForPaymentAllocationBatch(Collection $buyerKeys, ?string $dateToYmd): void
    {
        if ($buyerKeys->isEmpty()) {
            return;
        }

        $dateToSuffix = $dateToYmd ?? '';

        $buyerEntries = $buyerKeys
            ->map(function (array $buyerKey) use ($dateToSuffix) {
                $name = trim((string) ($buyerKey['name'] ?? ''));
                $slug = (string) ($buyerKey['slug'] ?? 'employee');
                if ($name === '' || $name === '—') {
                    return null;
                }
                $clientId = MessBuyerClientFilter::resolveClientId($name, [$slug]) ?? 0;

                return [
                    'name' => $name,
                    'slug' => $slug,
                    'client_id' => $clientId,
                    'cache_key' => $name . '|' . $slug . '|' . $dateToSuffix,
                ];
            })
            ->filter()
            ->unique(fn (array $entry) => $entry['cache_key'])
            ->values();

        if ($buyerEntries->isEmpty()) {
            return;
        }

        $slugs = $buyerEntries->pluck('slug')->unique()->values()->all();

        $drQuery = SellingVoucherDateRangeReport::with([
            'items' => function ($itemQ) use ($dateToYmd) {
                $this->applySvDateRangeReportItemsIssueDateConstraint($itemQ, null, $dateToYmd);
            },
            'items.itemSubcategory',
        ])
            ->whereIn('client_type_slug', $slugs)
            ->whereIn('status', $this->sellingVoucherDateRangeReportSaleVoucherStatuses())
            ->where(function ($outer) use ($buyerEntries) {
                foreach ($buyerEntries as $entry) {
                    $outer->orWhere(function ($single) use ($entry) {
                        $single->where('client_type_slug', $entry['slug']);
                        $buyerValue = $entry['client_id'] > 0 ? (string) $entry['client_id'] : $entry['name'];
                        MessBuyerClientFilter::apply($single, [$buyerValue], [$entry['slug']]);
                    });
                }
            });
        $this->applySellingVoucherDateRangeItemIssueDateFilter($drQuery, null, $dateToYmd);

        $drBills = $drQuery->orderBy('issue_date')->get();

        $kitchenClientTypes = collect($slugs)
            ->map(fn (string $slug) => $this->clientTypeSlugToKitchenId($slug))
            ->unique()
            ->values()
            ->all();

        $kitchenQuery = KitchenIssueMaster::with(['items', 'paymentDetails'])
            ->whereIn('kitchen_issue_type', self::KITCHEN_MESS_SELLING_ISSUE_TYPES)
            ->whereIn('client_type', $kitchenClientTypes)
            ->where(function ($outer) use ($buyerEntries) {
                foreach ($buyerEntries as $entry) {
                    $outer->orWhere(function ($single) use ($entry) {
                        $single->where('client_type', $this->clientTypeSlugToKitchenId($entry['slug']));
                        $buyerValue = $entry['client_id'] > 0 ? (string) $entry['client_id'] : $entry['name'];
                        MessBuyerClientFilter::apply($single, [$buyerValue], [$entry['slug']]);
                    });
                }
            });
        if ($dateToYmd) {
            $kitchenQuery->where('issue_date', '<=', $dateToYmd);
        }

        $kiBills = $kitchenQuery->orderBy('issue_date')->get();

        foreach ($buyerEntries as $entry) {
            if (isset($this->buyerBillsForAllocationCache[$entry['cache_key']])) {
                continue;
            }

            $allocationBills = $drBills
                ->filter(fn ($bill) => $this->billMatchesMessBuyerEntry($bill, $entry))
                ->concat($kiBills->filter(fn ($bill) => $this->billMatchesMessBuyerEntry($bill, $entry)))
                ->sortBy(function ($bill) {
                    $issueDate = $bill->issue_date ?? null;
                    if ($issueDate instanceof Carbon) {
                        return $issueDate->format('Y-m-d');
                    }

                    return $issueDate ? (string) $issueDate : '';
                })
                ->values();

            $this->buyerBillsForAllocationCache[$entry['cache_key']] = $allocationBills;
        }
    }

    /**
     * Load all buyer bills (with line items up to date_to) for FIFO payment allocation.
     */
    private function resolveBuyerBillsForPaymentAllocation(string $buyerName, string $clientTypeSlug, ?string $dateToYmd): Collection
    {
        $cacheKey = $buyerName . '|' . $clientTypeSlug . '|' . ($dateToYmd ?? '');
        if (isset($this->buyerBillsForAllocationCache[$cacheKey])) {
            return $this->buyerBillsForAllocationCache[$cacheKey];
        }

        $drQuery = SellingVoucherDateRangeReport::with([
            'items' => function ($itemQ) use ($dateToYmd) {
                $this->applySvDateRangeReportItemsIssueDateConstraint($itemQ, null, $dateToYmd);
            },
            'items.itemSubcategory',
        ])
            ->where('client_type_slug', $clientTypeSlug)
            ->whereIn('status', $this->sellingVoucherDateRangeReportSaleVoucherStatuses());
        $this->applyMessBillBuyerScope($drQuery, $buyerName, $clientTypeSlug);
        $this->applySellingVoucherDateRangeItemIssueDateFilter($drQuery, null, $dateToYmd);

        $kitchenQuery = KitchenIssueMaster::with(['items', 'paymentDetails'])
            ->whereIn('kitchen_issue_type', self::KITCHEN_MESS_SELLING_ISSUE_TYPES)
            ->where('client_type', $this->clientTypeSlugToKitchenId($clientTypeSlug));
        $this->applyMessBillBuyerScope($kitchenQuery, $buyerName, $clientTypeSlug);
        if ($dateToYmd) {
            $kitchenQuery->where('issue_date', '<=', $dateToYmd);
        }

        $allocationBills = $drQuery->orderBy('issue_date')->get()
            ->concat($kitchenQuery->orderBy('issue_date')->get());
        $this->buyerBillsForAllocationCache[$cacheKey] = $allocationBills;

        return $allocationBills;
    }

    /**
     * @param  array<int, int>  $receiverUserIds
     */
    private function preloadMessCombinedNotificationsForReceivers(array $receiverUserIds): void
    {
        $missing = [];
        foreach ($receiverUserIds as $receiverUserId) {
            $receiverUserId = (int) $receiverUserId;
            if ($receiverUserId > 0 && ! isset($this->messCombinedNotificationsByReceiver[$receiverUserId])) {
                $missing[$receiverUserId] = true;
            }
        }

        if ($missing === []) {
            return;
        }

        foreach (array_keys($missing) as $receiverUserId) {
            $this->messCombinedNotificationsByReceiver[$receiverUserId] = collect();
        }

        foreach (Notification::query()
            ->where('type', 'mess')
            ->where('module_name', 'MessInvoiceCombined')
            ->whereIn('receiver_user_id', array_keys($missing))
            ->orderByDesc('pk')
            ->get(['receiver_user_id', 'message', 'created_at', 'is_read']) as $notification) {
            $receiverUserId = (int) $notification->receiver_user_id;
            $this->messCombinedNotificationsByReceiver[$receiverUserId]->push($notification);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Notification>
     */
    private function messCombinedNotificationsForReceiver(int $receiverUserId): Collection
    {
        if ($receiverUserId <= 0) {
            return collect();
        }

        if (! isset($this->messCombinedNotificationsByReceiver[$receiverUserId])) {
            $this->messCombinedNotificationsByReceiver[$receiverUserId] = Notification::query()
                ->where('type', 'mess')
                ->where('module_name', 'MessInvoiceCombined')
                ->where('receiver_user_id', $receiverUserId)
                ->orderByDesc('pk')
                ->get(['message', 'created_at', 'is_read']);
        }

        return $this->messCombinedNotificationsByReceiver[$receiverUserId];
    }

    /**
     * Net line amount after returns (matches SellingVoucherDateRangeReport::getNetTotalAttribute).
     */
    private function lineItemNetAmount($item): float
    {
        $qty = (float) ($item->quantity ?? 0);
        $returnQty = (float) ($item->return_quantity ?? 0);
        $rate = (float) ($item->rate ?? 0);

        return $this->roundMoney(max(0, $qty - $returnQty) * $rate);
    }

    /**
     * Resolve issue_date (Y-m-d) for a line item, falling back to bill header date.
     */
    private function lineItemIssueDateYmd($item, $bill): ?string
    {
        try {
            if (! empty($item->issue_date)) {
                $dt = $item->issue_date instanceof Carbon
                    ? $item->issue_date
                    : Carbon::parse($item->issue_date);

                return $dt->format('Y-m-d');
            }
        } catch (\Throwable $e) {
            // ignore malformed dates
        }
        if (! empty($bill->issue_date)) {
            return $bill->issue_date instanceof Carbon
                ? $bill->issue_date->format('Y-m-d')
                : Carbon::parse($bill->issue_date)->format('Y-m-d');
        }

        return null;
    }

    /**
     * Stable key for combined-bill payment allocation (matches dr-/ki- receipt ids).
     */
    private function billPaymentAllocationKey($bill): string
    {
        if ($bill instanceof SellingVoucherDateRangeReport) {
            return 'dr-' . $bill->id;
        }

        return 'ki-' . ($bill->pk ?? $bill->id);
    }

    /**
     * @return list<array{issue_date_ymd: string, amount: float}>
     */
    private function extractLineItemsFromBill($bill): array
    {
        $lines = [];
        if ($bill instanceof SellingVoucherDateRangeReport) {
            foreach ($bill->items ?? [] as $item) {
                $amount = $this->lineItemNetAmount($item);
                if ($amount <= 0) {
                    continue;
                }
                $ymd = $this->lineItemIssueDateYmd($item, $bill);
                if ($ymd === null) {
                    continue;
                }
                $lines[] = ['issue_date_ymd' => $ymd, 'amount' => $amount];
            }

            return $lines;
        }
        if ($bill instanceof KitchenIssueMaster) {
            $fallbackYmd = ! empty($bill->issue_date)
                ? ($bill->issue_date instanceof Carbon
                    ? $bill->issue_date->format('Y-m-d')
                    : Carbon::parse($bill->issue_date)->format('Y-m-d'))
                : null;
            foreach ($bill->items ?? [] as $item) {
                $amount = $this->lineItemNetAmount($item);
                if ($amount <= 0) {
                    continue;
                }
                $ymd = $this->lineItemIssueDateYmd($item, $bill) ?? $fallbackYmd;
                if ($ymd === null) {
                    continue;
                }
                $lines[] = ['issue_date_ymd' => $ymd, 'amount' => $amount];
            }
            if ($lines === [] && $fallbackYmd !== null) {
                $net = $this->roundMoney((float) $bill->net_total);
                if ($net > 0) {
                    $lines[] = ['issue_date_ymd' => $fallbackYmd, 'amount' => $net];
                }
            }
        }

        return $lines;
    }

    /**
     * FIFO-allocated line items for a buyer (includes bill_key for payment distribution).
     *
     * @return list<array{issue_date_ymd: string, amount: float, allocated_paid: float, bill_key: string}>
     */
    private function buildCombinedFifoAllocatedLines(
        string $buyerName,
        string $clientTypeSlug,
        ?string $dateToYmd
    ): array {
        $allocationBills = $this->resolveBuyerBillsForPaymentAllocation($buyerName, $clientTypeSlug, $dateToYmd);

        $lineItems = [];
        $totalPaidPool = 0.0;
        foreach ($allocationBills as $bill) {
            $isDr = $bill instanceof SellingVoucherDateRangeReport;
            $totalPaidPool += $this->getBillPaidAmount($bill, $isDr);
            $billKey = $this->billPaymentAllocationKey($bill);
            foreach ($this->extractLineItemsFromBill($bill) as $line) {
                $line['bill_key'] = $billKey;
                $lineItems[] = $line;
            }
        }

        return $this->allocatePaidAmountFifo($lineItems, $this->roundMoney($totalPaidPool));
    }

    private function lineItemInDateRange(string $issueDateYmd, ?string $dateFromYmd, ?string $dateToYmd): bool
    {
        if ($dateFromYmd && $issueDateYmd < $dateFromYmd) {
            return false;
        }
        if ($dateToYmd && $issueDateYmd > $dateToYmd) {
            return false;
        }

        return true;
    }

    /**
     * Per-voucher unpaid balance in the filtered period (after FIFO), keyed by dr-/ki- id.
     *
     * @return array<string, float>
     */
    private function computeCombinedBillPeriodDuesByBill(
        string $buyerName,
        string $clientTypeSlug,
        ?string $dateFromYmd,
        ?string $dateToYmd
    ): array {
        $lineItems = $this->buildCombinedFifoAllocatedLines($buyerName, $clientTypeSlug, $dateToYmd);
        $dues = [];
        foreach ($lineItems as $line) {
            if (! $this->lineItemInDateRange($line['issue_date_ymd'], $dateFromYmd, $dateToYmd)) {
                continue;
            }
            $unpaid = $this->roundMoney($line['amount'] - (float) ($line['allocated_paid'] ?? 0));
            if ($unpaid <= 0) {
                continue;
            }
            $key = $line['bill_key'];
            $dues[$key] = $this->roundMoney(($dues[$key] ?? 0) + $unpaid);
        }

        return $dues;
    }

    /**
     * Bill keys with period due > 0, oldest issue_date first.
     *
     * @param  array<string, float>  $billPeriodDues
     * @return list<string>
     */
    private function billKeysSortedForPeriodPayment(array $billPeriodDues, array $fifoLines, ?string $dateFromYmd, ?string $dateToYmd): array
    {
        $earliestDate = [];
        foreach ($fifoLines as $line) {
            if (! $this->lineItemInDateRange($line['issue_date_ymd'], $dateFromYmd, $dateToYmd)) {
                continue;
            }
            $unpaid = $this->roundMoney($line['amount'] - (float) ($line['allocated_paid'] ?? 0));
            if ($unpaid <= 0) {
                continue;
            }
            $key = $line['bill_key'];
            $ymd = $line['issue_date_ymd'];
            if (! isset($earliestDate[$key]) || $ymd < $earliestDate[$key]) {
                $earliestDate[$key] = $ymd;
            }
        }

        $keys = array_keys(array_filter($billPeriodDues, fn ($due) => $due > 0));
        usort($keys, function (string $a, string $b) use ($earliestDate): int {
            $da = $earliestDate[$a] ?? '9999-12-31';
            $db = $earliestDate[$b] ?? '9999-12-31';
            $cmp = strcmp($da, $db);

            return $cmp !== 0 ? $cmp : strcmp($a, $b);
        });

        return $keys;
    }

    /**
     * Apply total paid to line items in issue_date order (FIFO).
     *
     * @param  list<array{issue_date_ymd: string, amount: float}>  $lineItems
     * @return list<array{issue_date_ymd: string, amount: float, allocated_paid: float}>
     */
    private function allocatePaidAmountFifo(array $lineItems, float $totalPaid): array
    {
        usort($lineItems, function (array $a, array $b): int {
            $cmp = strcmp($a['issue_date_ymd'], $b['issue_date_ymd']);

            return $cmp !== 0 ? $cmp : 0;
        });

        $remaining = $this->roundMoney($totalPaid);
        foreach ($lineItems as $index => $line) {
            $alloc = $this->roundMoney(min($remaining, $line['amount']));
            $lineItems[$index]['allocated_paid'] = $alloc;
            $remaining = $this->roundMoney($remaining - $alloc);
        }

        return $lineItems;
    }

    /**
     * Combined bill total / paid / due for a date-filtered statement.
     * Paid is allocated FIFO across all buyer charges (including before date_from) so due stays consistent.
     *
     * @return array{total: float, paid: float, due: float}
     */
    private function computeCombinedBillFinancials(
        string $buyerName,
        string $clientTypeSlug,
        ?string $dateFromYmd,
        ?string $dateToYmd
    ): array {
        $cacheKey = $buyerName . '|' . $clientTypeSlug . '|' . ($dateFromYmd ?? '') . '|' . ($dateToYmd ?? '');
        if (isset($this->combinedBillFinancialsCache[$cacheKey])) {
            return $this->combinedBillFinancialsCache[$cacheKey];
        }

        $lineItems = $this->buildCombinedFifoAllocatedLines($buyerName, $clientTypeSlug, $dateToYmd);

        $total = 0.0;
        $paid = 0.0;
        foreach ($lineItems as $line) {
            if (! $this->lineItemInDateRange($line['issue_date_ymd'], $dateFromYmd, $dateToYmd)) {
                continue;
            }
            $total += $line['amount'];
            $paid += (float) ($line['allocated_paid'] ?? 0);
        }

        $total = $this->roundMoney($total);
        $paid = $this->roundMoney($paid);

        $result = [
            'total' => $total,
            'paid' => $paid,
            'due' => $this->billDueAmount($total, $paid),
        ];

        $this->combinedBillFinancialsCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Resolve dr-/ki- key to bill model from allocation set.
     *
     * @param  \Illuminate\Support\Collection<int, SellingVoucherDateRangeReport|KitchenIssueMaster>  $allocationBills
     */
    private function resolveBillFromPaymentKey(string $billKey, Collection $allocationBills)
    {
        if (preg_match('/^(dr|ki)-(\d+)$/i', $billKey, $m)) {
            $isDr = strtolower($m[1]) === 'dr';
            $numericId = (int) $m[2];
            foreach ($allocationBills as $bill) {
                if ($isDr && $bill instanceof SellingVoucherDateRangeReport && (int) $bill->id === $numericId) {
                    return $bill;
                }
                if (! $isDr && $bill instanceof KitchenIssueMaster && (int) ($bill->pk ?? $bill->id) === $numericId) {
                    return $bill;
                }
            }
        }

        return null;
    }

    /**
     * Whether paid amount satisfies the bill total (2-decimal comparison).
     */
    private function isBillFullyPaid(float $paidAmount, float $totalAmount): bool
    {
        return $this->roundMoney($paidAmount) >= $this->roundMoney($totalAmount);
    }

    /**
     * Get paid amount for a bill (date range or kitchen issue).
     */
    private function getBillPaidAmount($bill, bool $isDateRange): float
    {
        if ($isDateRange) {
            return $this->roundMoney((float) ($bill->paid_amount ?? 0));
        }
        if ($bill instanceof KitchenIssueMaster) {
            $bill->load('paymentDetails');

            return $this->roundMoney((float) $bill->paymentDetails->sum('paid_amount'));
        }

        return $this->roundMoney((float) ($bill->kitchen_paid_total ?? 0));
    }

    /**
     * Map frontend payment_mode string to kitchen_issue_payment_details payment_mode (0=Cash, 1=Online, 2=Cheque).
     */
    private function kitchenPaymentModeValue(?string $mode): int
    {
        $map = ['cash' => 0, 'online' => 1, 'cheque' => 2, 'deduct_from_salary' => 0];
        return $map[strtolower((string) $mode)] ?? 0;
    }

    /**
     * kitchen_issue_payment_details.kitchen_issue_master_pk must reference an existing kitchen_issue_master.pk.
     */
    private function kitchenIssueMasterPkForPayment(KitchenIssueMaster $bill): ?int
    {
        $pk = (int) ($bill->getAttribute('pk') ?? 0);
        if ($pk > 0 && KitchenIssueMaster::query()->where('pk', $pk)->exists()) {
            return $pk;
        }
        if (Schema::hasColumn('kitchen_issue_master', 'id')) {
            $legacyId = (int) ($bill->getAttribute('id') ?? 0);
            if ($legacyId > 0) {
                $resolved = KitchenIssueMaster::query()->where('id', $legacyId)->value('pk');
                if ($resolved !== null) {
                    return (int) $resolved;
                }
            }
        }

        return null;
    }

    /**
     * Generate payment - supports partial and full payment; sends notifications and updates status when fully paid.
     * For combined bill id, distributes payment across underlying vouchers (oldest first).
     */
    public function generatePayment(Request $request, $id)
    {
        $amount = $request->input('amount');
        if ($amount === null || $amount === '') {
            return response()->json([
                'success' => false,
                'message' => 'Please enter the payment amount.',
            ], 400);
        }
        $amount = $this->roundMoney((float) $amount);
        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount must be greater than zero.',
            ], 400);
        }

        $paymentMode = $request->input('payment_mode', 'cash');
        $paymentDate = $request->filled('payment_date') ? $this->parseDate($request->payment_date) : now()->format('Y-m-d');

        // Combined bill: distribute payment across vouchers (oldest first, FIFO period due)
        if (is_string($id) && strpos($id, 'combined-') === 0) {
            $bills = $this->resolveCombinedBillBills($request, $id);
            if (empty($bills)) {
                return response()->json(['success' => false, 'message' => 'No bills found for this buyer in the selected date range.'], 404);
            }
            $buyerName = trim((string) ($bills[0]->client_name ?? ($bills[0]->clientTypeCategory->client_name ?? '')));
            $clientTypeSlug = $bills[0] instanceof SellingVoucherDateRangeReport
                ? (string) ($bills[0]->client_type_slug ?? 'employee')
                : $this->getBillClientTypeSlug($bills[0]);
            $dateFromYmd = $request->filled('date_from')
                ? $this->parseDate($request->date_from)
                : now()->startOfMonth()->format('Y-m-d');
            $dateToYmd = $request->filled('date_to')
                ? $this->parseDate($request->date_to)
                : now()->endOfMonth()->format('Y-m-d');
            $actualTotalDue = $this->computeCombinedBillFinancials($buyerName, $clientTypeSlug, null, $dateToYmd)['due'];
            if ($actualTotalDue <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This bill is already fully paid.',
                ], 400);
            }
            if ($amount > $actualTotalDue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount cannot exceed total due amount.',
                ], 400);
            }
            $billPeriodDues = $this->computeCombinedBillPeriodDuesByBill($buyerName, $clientTypeSlug, null, $dateToYmd);
            $fifoLines = $this->buildCombinedFifoAllocatedLines($buyerName, $clientTypeSlug, $dateToYmd);
            $paymentBillKeys = $this->billKeysSortedForPeriodPayment($billPeriodDues, $fifoLines, null, $dateToYmd);
            $allocationBills = $this->resolveBuyerBillsForPaymentAllocation($buyerName, $clientTypeSlug, $dateToYmd);
            try {
                DB::beginTransaction();
                $remaining = $amount;
                foreach ($paymentBillKeys as $billKey) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $billDue = $this->roundMoney((float) ($billPeriodDues[$billKey] ?? 0));
                    if ($billDue <= 0) {
                        continue;
                    }
                    $bill = $this->resolveBillFromPaymentKey($billKey, $allocationBills);
                    if ($bill === null) {
                        continue;
                    }
                    $isDr = $bill instanceof SellingVoucherDateRangeReport;
                    $payThis = $this->roundMoney(min($remaining, $billDue));
                    $billTotal = $this->roundMoney((float) $bill->net_total);
                    if ($isDr) {
                        SvDateRangePaymentDetail::create([
                            'sv_date_range_report_id' => $bill->id,
                            'paid_amount' => $payThis,
                            'payment_date' => $paymentDate,
                            'payment_mode' => $paymentMode,
                            'bank_name' => $request->input('bank_name'),
                            'cheque_number' => $request->input('cheque_number'),
                            'cheque_date' => $request->filled('cheque_date') ? $this->parseDate($request->cheque_date) : null,
                            'remarks' => $request->input('remarks'),
                        ]);
                        $bill->paid_amount = $this->roundMoney((float) ($bill->paid_amount ?? 0) + $payThis);
                        $bill->status = $this->isBillFullyPaid((float) $bill->paid_amount, $billTotal) ? 2 : 1;
                        $bill->save();
                    } else {
                        /** @var KitchenIssueMaster $bill */
                        $parentPk = $this->kitchenIssueMasterPkForPayment($bill);
                        if ($parentPk === null) {
                            DB::rollBack();
                            $rawPk = (int) ($bill->getAttribute('pk') ?? 0);

                            return response()->json([
                                'success' => false,
                                'message' => 'Cannot record payment: kitchen selling voucher (reference pk ' . ($rawPk > 0 ? $rawPk : 'missing') . ') does not exist in the database. It may have been deleted or mismatched. Reload the bill list and try again.',
                            ], 422);
                        }
                        $bill->load('paymentDetails');
                        KitchenIssuePaymentDetail::create([
                            'kitchen_issue_master_pk' => $parentPk,
                            'paid_amount' => $payThis,
                            'payment_date' => $paymentDate,
                            'payment_mode' => $this->kitchenPaymentModeValue($paymentMode),
                            'transaction_ref' => $request->input('cheque_number'),
                            'remarks' => $request->input('remarks'),
                        ]);
                        $newPaid = $this->getBillPaidAmount($bill, false);
                        $bill->status = $this->isBillFullyPaid($newPaid, $billTotal) ? 2 : 1;
                        $bill->save();
                    }
                    $remaining = $this->roundMoney($remaining - $payThis);
                }
                if ($this->roundMoney($remaining) > 0) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Amount cannot exceed total due amount.',
                    ], 400);
                }
                DB::commit();
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                report($e);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment could not be saved due to a database error. If this mentions a missing kitchen voucher, reload the page and verify the bill still exists.',
                ], 422);
            }
            $clientName = trim((string) ($bills[0]->client_name ?? ($bills[0]->clientTypeCategory->client_name ?? '—')));
            $receiverUserId = $this->resolveReceiverUserIdFromAnyBill($bills);
            if ($receiverUserId !== null && $receiverUserId > 0) {
                try {
                    $isFullPayment = $this->isBillFullyPaid($amount, $actualTotalDue);
                    $dateFromYmd = $request->filled('date_from')
                        ? $this->parseDate($request->date_from)
                        : now()->startOfMonth()->format('Y-m-d');
                    $dateToYmd = $request->filled('date_to')
                        ? $this->parseDate($request->date_to)
                        : now()->endOfMonth()->format('Y-m-d');
                    $paymentVisible = $isFullPayment
                        ? '₹' . number_format($amount, 2) . ' payment received successfully.'
                        : '₹' . number_format($amount, 2) . ' payment received.';
                    $paymentMessage = NotificationService::appendMessCombinedReceiptPayload(
                        $paymentVisible,
                        $id,
                        $dateFromYmd,
                        $dateToYmd,
                        $amount
                    );
                    app(NotificationService::class)->create(
                        $receiverUserId,
                        'mess',
                        'MessPaymentCombined',
                        0,
                        $isFullPayment ? 'Payment Successfully Done' : 'Partial Payment Received',
                        $paymentMessage
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            $remainingDueCombined = $this->billDueAmount($actualTotalDue, $amount);
            $this->bumpProcessMessBillsCombinedCache();


            return response()->json([
                'success' => true,
                'full_payment' => $this->isBillFullyPaid($amount, $actualTotalDue),
                'message' => $remainingDueCombined <= 0
                    ? 'Payment completed successfully. Confirmation sent to user.'
                    : 'Partial payment recorded. Remaining due: ₹ ' . number_format($remainingDueCombined, 2),
                'remaining_due' => $remainingDueCombined,
                'paid_amount' => $amount,
                'bill_id' => $id,
                'client_name' => $clientName,
            ]);
        }

        [$bill, $isDateRange] = $this->resolveBillById($id);
        $isKitchenIssue = !$isDateRange;
        if ($isKitchenIssue) {
            $bill->load('paymentDetails');
        }

        $totalAmount = $this->roundMoney((float) $bill->net_total);
        $paidBefore = $this->getBillPaidAmount($bill, !$isKitchenIssue);
        $dueBefore = $this->billDueAmount($totalAmount, $paidBefore);
        $singleBuyerName = trim((string) ($bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '')));
        $singleClientTypeSlug = $isDateRange
            ? (string) ($bill->client_type_slug ?? 'employee')
            : $this->getBillClientTypeSlug($bill);
        $singleDateToYmd = $request->filled('date_to')
            ? $this->parseDate($request->date_to)
            : ($bill->issue_date
                ? ($bill->issue_date instanceof Carbon ? $bill->issue_date->format('Y-m-d') : Carbon::parse($bill->issue_date)->format('Y-m-d'))
                : now()->format('Y-m-d'));
        $actualTotalDue = $singleBuyerName !== ''
            ? $this->computeCombinedBillFinancials($singleBuyerName, $singleClientTypeSlug, null, $singleDateToYmd)['due']
            : $dueBefore;

        if ($actualTotalDue <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'This bill is already fully paid!',
            ], 400);
        }

        if ($amount > $actualTotalDue) {
            return response()->json([
                'success' => false,
                'message' => 'Amount cannot exceed total due amount.',
            ], 400);
        }

        if ($amount > $dueBefore) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount cannot exceed the balance due on this voucher (₹ ' . number_format($dueBefore, 2) . ').',
            ], 400);
        }

        if ($isKitchenIssue) {
            $parentPk = $this->kitchenIssueMasterPkForPayment($bill);
            if ($parentPk === null) {
                $rawPk = (int) ($bill->getAttribute('pk') ?? 0);

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot record payment: kitchen selling voucher (reference pk ' . ($rawPk > 0 ? $rawPk : 'missing') . ') does not exist in the database. Reload and try again.',
                ], 422);
            }
            KitchenIssuePaymentDetail::create([
                'kitchen_issue_master_pk' => $parentPk,
                'paid_amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_mode' => $this->kitchenPaymentModeValue($paymentMode),
                'transaction_ref' => $request->input('cheque_number'),
                'remarks' => $request->input('remarks'),
            ]);
            $paidAfter = $this->roundMoney($paidBefore + $amount);
            $isFullPayment = $this->isBillFullyPaid($paidAfter, $totalAmount);
            $bill->status = $isFullPayment ? 2 : 1;
            $bill->save();
        } else {
            SvDateRangePaymentDetail::create([
                'sv_date_range_report_id' => $bill->id,
                'paid_amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_mode' => $paymentMode,
                'bank_name' => $request->input('bank_name'),
                'cheque_number' => $request->input('cheque_number'),
                'cheque_date' => $request->filled('cheque_date') ? $this->parseDate($request->cheque_date) : null,
                'remarks' => $request->input('remarks'),
            ]);
            $paidAfter = $this->roundMoney((float) ($bill->paid_amount ?? 0) + $amount);
            $bill->paid_amount = $paidAfter;
            $isFullPayment = $this->isBillFullyPaid($paidAfter, $totalAmount);
            $bill->status = $isFullPayment ? 2 : 1;
            $bill->save();
        }

        $remainingDue = $this->billDueAmount($totalAmount, $paidAfter);
        $receiverUserId = $this->getReceiverUserIdForBill($bill, $isKitchenIssue);
        $billId = $isKitchenIssue ? $bill->pk : $bill->id;
        $clientName = $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—');

        if ($receiverUserId !== null && $receiverUserId > 0) {
            try {
                $singleBillReceiptId = $isKitchenIssue
                    ? 'ki-' . (int) $billId
                    : 'dr-' . (int) $billId;
                $singleDateFromYmd = $request->filled('date_from')
                    ? $this->parseDate($request->date_from)
                    : now()->startOfMonth()->format('Y-m-d');
                $singleDateToNotify = $request->filled('date_to')
                    ? $this->parseDate($request->date_to)
                    : $singleDateToYmd;
                $paymentVisible = $isFullPayment
                    ? '₹' . number_format($amount, 2) . ' payment received successfully.'
                    : '₹' . number_format($amount, 2) . ' payment received.';
                $paymentMessage = NotificationService::appendMessCombinedReceiptPayload(
                    $paymentVisible,
                    $singleBillReceiptId,
                    $singleDateFromYmd,
                    $singleDateToNotify,
                    $amount
                );
                app(NotificationService::class)->create(
                    $receiverUserId,
                    'mess',
                    'MessPayment',
                    (int) $billId,
                    $isFullPayment ? 'Payment Successfully Done' : 'Partial Payment Received',
                    $paymentMessage
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->bumpProcessMessBillsCombinedCache();

        return response()->json([
            'success' => true,
            'full_payment' => $isFullPayment,
            'message' => $isFullPayment
                ? 'Payment completed successfully. Confirmation sent to user. Report status: Payment Successfully Done.'
                : 'Partial payment recorded. Notification sent. Remaining due: ₹ ' . number_format($remainingDue, 2),
            'remaining_due' => $remainingDue,
            'paid_amount' => $paidAfter,
            'bill_id' => $billId,
            'client_name' => $clientName,
        ]);
    }

    /**
     * SV date-range report statuses included on Sale Voucher Report (category-wise print slip).
     *
     * @return array<int, int>
     */
    private function sellingVoucherDateRangeReportSaleVoucherStatuses(): array
    {
        return [
            SellingVoucherDateRangeReport::STATUS_DRAFT,
            SellingVoucherDateRangeReport::STATUS_FINAL,
            SellingVoucherDateRangeReport::STATUS_APPROVED,
        ];
    }

    /**
     * Limit SV date-range line items by request date (issue_date), same as Sale Voucher Report item scope.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $itemQuery
     */
    private function applySvDateRangeReportItemsIssueDateConstraint($itemQuery, ?string $dateFromYmd, ?string $dateToYmd): void
    {
        if ($dateFromYmd && $dateToYmd) {
            $itemQuery->whereBetween('issue_date', [$dateFromYmd, $dateToYmd]);

            return;
        }
        if ($dateFromYmd) {
            $itemQuery->whereDate('issue_date', '>=', $dateFromYmd);

            return;
        }
        if ($dateToYmd) {
            $itemQuery->whereDate('issue_date', '<=', $dateToYmd);

            return;
        }
    }

    /**
     * Same date logic as ReportController::buildCategoryWisePrintSlipReportData:
     * include a selling voucher (date range) header only if it has line items whose request date
     * (sv_date_range_report_items.issue_date) falls in the selected range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    private function applySellingVoucherDateRangeItemIssueDateFilter($query, ?string $dateFromYmd, ?string $dateToYmd): void
    {
        $table = (new SellingVoucherDateRangeReport())->getTable();

        if ($dateFromYmd || $dateToYmd) {
            $query->whereExists(function ($sub) use ($dateFromYmd, $dateToYmd, $table) {
                $sub->select(DB::raw('1'))
                    ->from('sv_date_range_report_items')
                    ->whereColumn('sv_date_range_report_items.sv_date_range_report_id', $table . '.id');
                $this->applySvDateRangeReportItemsIssueDateConstraint($sub, $dateFromYmd, $dateToYmd);
            });

            return;
        }

        $query->whereExists(function ($sub) use ($table) {
            $sub->select(DB::raw('1'))
                ->from('sv_date_range_report_items')
                ->whereColumn('sv_date_range_report_items.sv_date_range_report_id', $table . '.id');
        });
    }

    private function parseDate(string $value): ?string
    {
        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }
        return null;
    }

    private function normalizeBuyerNames($value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($name) => trim((string) $name))
                ->filter(fn ($name) => $name !== '')
                ->values()
                ->all();
        }

        $name = trim((string) ($value ?? ''));
        return $name !== '' ? [$name] : [];
    }

    /**
     * Normalize filter input from single or multi-select controls by dropping blank placeholders.
     *
     * @param  mixed  $value
     * @return array<int, string>
     */
    private function normalizeFilterArrayValues($value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item) => trim((string) $item))
                ->filter(fn ($item) => $item !== '')
                ->values()
                ->all();
        }

        $single = trim((string) ($value ?? ''));
        return $single !== '' ? [$single] : [];
    }

    private function applyBuyerNameFilter($query, array $buyerNames, array $clientTypeSlugs = [], array $clientTypePks = []): void
    {
        MessBuyerClientFilter::apply(
            $query,
            $buyerNames,
            $clientTypeSlugs,
            (int) ($clientTypePks[0] ?? 0)
        );
    }

    /**
     * @param  array<int, string>  $buyerNames
     * @param  array<int, string>  $clientTypeSlugs
     * @param  array<int, string>  $clientTypePks
     * @return array<int, string>
     */
    private function normalizeBuyerNamesToClientIds(array $buyerNames, array $clientTypeSlugs, array $clientTypePks): array
    {
        return collect($buyerNames)
            ->map(function ($buyerValue) use ($clientTypeSlugs, $clientTypePks) {
                $buyerValue = trim((string) $buyerValue);
                if ($buyerValue === '') {
                    return null;
                }
                $resolved = MessBuyerClientFilter::resolveClientId($buyerValue, $clientTypeSlugs);

                return ($resolved !== null && $resolved > 0) ? (string) $resolved : $buyerValue;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function escapeSqlLikeValue(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }

    /**
     * LIKE patterns for My Mess Bills: same words with any run of spaces between
     * (e.g. bill text "Vipul  Tomar (Medical…)" vs master name "Vipul Tomar").
     *
     * @param  array<int, string>  $baseNames
     * @return array<int, string>
     */
    private function buildMessSelfServiceClientNameLikePatterns(array $baseNames): array
    {
        $patterns = [];
        foreach ($baseNames as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $norm = preg_replace('/\s+/u', ' ', $name) ?? $name;
            $patterns[] = '%' . $this->escapeSqlLikeValue($norm) . '%';
            $words = preg_split('/\s+/u', $norm, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($words) >= 2) {
                $escaped = array_map(fn ($w) => $this->escapeSqlLikeValue($w), $words);
                $patterns[] = '%' . implode('%', $escaped) . '%';
            } elseif (count($words) === 1) {
                $patterns[] = '%' . $this->escapeSqlLikeValue($words[0]) . '%';
            }
        }

        return array_values(array_unique($patterns));
    }

    private function applyMyBillsClientNameOrStubFalse($query, array $likePatterns): void
    {
        if ($likePatterns === []) {
            $query->whereRaw('0 = 1');

            return;
        }
        $query->where(function ($q) use ($likePatterns) {
            foreach ($likePatterns as $pat) {
                $q->orWhere('client_name', 'like', $pat);
            }
        });
    }

    private function applyMyBillsKitchenClientNameOrClientId($query, array $likePatterns, array $authLinkedUserIds): void
    {
        $query->where(function ($outer) use ($likePatterns, $authLinkedUserIds) {
            if ($likePatterns !== []) {
                $outer->where(function ($q) use ($likePatterns) {
                    foreach ($likePatterns as $pat) {
                        $q->orWhere('client_name', 'like', $pat);
                    }
                });
            }
            if ($authLinkedUserIds !== []) {
                $outer->orWhere(function ($q) use ($authLinkedUserIds) {
                    $q->whereIn('client_type', [
                        KitchenIssueMaster::CLIENT_EMPLOYEE,
                        KitchenIssueMaster::CLIENT_OT,
                        KitchenIssueMaster::CLIENT_COURSE,
                    ])->whereIn('client_id', $authLinkedUserIds);
                });
            }
            if ($likePatterns === [] && $authLinkedUserIds === []) {
                $outer->whereRaw('0 = 1');
            }
        });
    }

    /**
     * Return all user_ids that can represent the current logged-in person in mess mappings
     * (handles employee_master.pk and employee_master.pk_old swaps).
     *
     * @return array<int, int>
     */
    private function authLinkedUserIdsForMessSelfService(): array
    {
        $uid = (int) (auth()->user()->user_id ?? 0);
        if ($uid <= 0) {
            return [];
        }

        $linked = [$uid];
        try {
            $user = auth()->user();
            if (($user->user_category ?? '') !== 'S') {
                $employee = EmployeeMaster::query()
                    ->where(function ($q) use ($uid) {
                        $q->where('pk', $uid);
                        if (Schema::hasColumn('employee_master', 'pk_old')) {
                            $q->orWhere('pk_old', $uid);
                        }
                    })
                    ->first(['pk', 'pk_old']);

                if ($employee) {
                    $pk = isset($employee->pk) ? (int) $employee->pk : 0;
                    $pkOld = isset($employee->pk_old) ? (int) $employee->pk_old : 0;
                    if ($pk > 0) {
                        $linked[] = $pk;
                    }
                    if ($pkOld > 0) {
                        $linked[] = $pkOld;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fall back to current user_id only.
        }

        return array_values(array_unique(array_filter($linked, fn ($id) => (int) $id > 0)));
    }

    private function currentUserCanAdminMessBills(): bool
    {
        return canSeeLowStockAlert();
    }

    /**
     * @param  array<int, KitchenIssueMaster|SellingVoucherDateRangeReport>  $bills
     */
    private function assertCurrentUserCanAccessBills(array $bills): void
    {
        if ($this->currentUserCanAdminMessBills()) {
            return;
        }
        $rid = $this->resolveReceiverUserIdFromAnyBill($bills);
        $uid = (int) (auth()->user()->user_id ?? 0);
        if ($rid === null || $rid <= 0 || (int) $rid !== $uid) {
            abort(403);
        }
    }

    private function assertCurrentUserCanAccessSingleBill($bill, bool $isDateRange): void
    {
        if ($this->currentUserCanAdminMessBills()) {
            return;
        }
        $isKitchen = !($bill instanceof SellingVoucherDateRangeReport);
        $rid = $this->getReceiverUserIdForBill($bill, $isKitchen);
        $uid = (int) (auth()->user()->user_id ?? 0);
        if ($rid === null || $rid <= 0 || (int) $rid !== $uid) {
            abort(403);
        }
    }

    /**
     * Display names to narrow mess bill queries for the logged-in portal user.
     *
     * @return array<int, string>
     */
    private function messBillBuyerNameCandidatesForCurrentUser(): array
    {
        $user = auth()->user();
        if (!$user || empty($user->user_id)) {
            return [];
        }
        $uid = (int) $user->user_id;
        $names = [];

        if (($user->user_category ?? '') === 'S' && Schema::hasTable('student_master')) {
            $s = DB::table('student_master')->where('pk', $uid)->first(['first_name', 'middle_name', 'last_name', 'display_name']);
            if ($s) {
                if (!empty($s->display_name)) {
                    $names[] = trim((string) $s->display_name);
                }
                $full = trim(implode(' ', array_filter([
                    trim((string) ($s->first_name ?? '')),
                    trim((string) ($s->middle_name ?? '')),
                    trim((string) ($s->last_name ?? '')),
                ], fn ($p) => $p !== '')));
                if ($full !== '') {
                    $names[] = $full;
                }
            }
        } else {
            $empQuery = EmployeeMaster::query()->where(function ($q) use ($uid) {
                $q->where('pk', $uid);
                if (Schema::hasColumn('employee_master', 'pk_old')) {
                    $q->orWhere('pk_old', $uid);
                }
            });
            $e = $empQuery->first(['first_name', 'middle_name', 'last_name']);
            if ($e) {
                $full = trim(implode(' ', array_filter([
                    trim((string) ($e->first_name ?? '')),
                    trim((string) ($e->middle_name ?? '')),
                    trim((string) ($e->last_name ?? '')),
                ], fn ($p) => $p !== '')));
                if ($full !== '') {
                    $names[] = $full;
                }
            }
            if (Schema::hasColumn('user_credentials', 'name')) {
                $n = DB::table('user_credentials')->where('user_id', $uid)->value('name');
                if ($n !== null && trim((string) $n) !== '') {
                    $names[] = trim((string) $n);
                }
            }
        }

        $extra = [];
        foreach ($names as $n) {
            $s = $this->sanitizeBuyerNameForUserLookup($n);
            if ($s !== '' && $s !== $n) {
                $extra[] = $s;
            }
        }
        $names = array_merge($names, $extra);

        return array_values(array_unique(array_filter(array_map('trim', $names), fn ($n) => $n !== '')));
    }
}
