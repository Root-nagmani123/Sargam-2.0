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
use App\Exports\ProcessMessBillsExport;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /** Client type constants for KitchenIssueMaster (Employee, OT, Course, Other) */
    private const ALLOWED_KITCHEN_CLIENT_TYPES = [
        KitchenIssueMaster::CLIENT_EMPLOYEE,
        KitchenIssueMaster::CLIENT_OT,
        KitchenIssueMaster::CLIENT_COURSE,
        KitchenIssueMaster::CLIENT_OTHER,
    ];
    public function index(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? $this->parseDate($request->date_from) : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $this->parseDate($request->date_to) : now()->endOfMonth()->format('Y-m-d');
        $clientType = $request->filled('client_type') ? $request->client_type : null;
        $clientTypePk = $request->filled('client_type_pk') ? $request->client_type_pk : null;
        $buyerName = $request->filled('buyer_name') ? trim($request->buyer_name) : null;
        $statusFilter = $request->filled('status') ? $request->status : null;
        $statusFilter = $request->filled('status') ? $request->status : null;

        // Query 1: Selling Voucher with Date Range (sv_date_range_reports)
        $dateRangeQuery = SellingVoucherDateRangeReport::query()
            ->select([
                'id',
                'client_name',
                'issue_date',
                'client_type_slug',
                'client_type_pk',
                'total_amount',
                'payment_type',
                'status',
                'store_id',
                DB::raw("'date_range' as source_type")
            ])
            ->whereIn('client_type_slug', self::ALLOWED_CLIENT_SLUGS);

        if ($clientType) {
            $dateRangeQuery->where('client_type_slug', $clientType);
        }
        if ($clientTypePk) {
            $dateRangeQuery->where('client_type_pk', $clientTypePk);
        }
        if ($dateFrom) {
            $dateRangeQuery->where(function ($q) use ($dateFrom) {
                $q->where('issue_date', '>=', $dateFrom)
                  ->orWhere('date_from', '>=', $dateFrom);
            });
        }
        if ($dateTo) {
            $dateRangeQuery->where(function ($q) use ($dateTo) {
                $q->where('issue_date', '<=', $dateTo)
                  ->orWhere('date_to', '<=', $dateTo);
            });
        }
        if ($buyerName) {
            $dateRangeQuery->where('client_name', 'like', '%' . $buyerName . '%');
        }

        // Query 2: Regular Selling Voucher (kitchen_issue_master)
        $kitchenClientTypes = $clientType
            ? [$this->clientTypeSlugToKitchenId($clientType)]
            : self::ALLOWED_KITCHEN_CLIENT_TYPES;

        $kitchenIssueQuery = KitchenIssueMaster::query()
            ->select([
                'pk as id',
                'client_name',
                'issue_date',
                DB::raw("CASE client_type WHEN 1 THEN 'employee' WHEN 2 THEN 'ot' WHEN 3 THEN 'course' WHEN 4 THEN 'other' END as client_type_slug"),
                'client_type_pk',
                DB::raw('NULL as total_amount'),
                'payment_type',
                'status',
                'store_id',
                DB::raw("'kitchen_issue' as source_type")
            ])
            ->whereIn('client_type', $kitchenClientTypes)
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER);

        if ($clientTypePk) {
            $kitchenIssueQuery->where('client_type_pk', $clientTypePk);
        }

        if ($dateFrom) {
            $kitchenIssueQuery->where('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $kitchenIssueQuery->where('issue_date', '<=', $dateTo);
        }
        if ($buyerName) {
            $kitchenIssueQuery->where('client_name', 'like', '%' . $buyerName . '%');
        }

        // Union both queries – load all rows for date range so DataTables can search/sort client-side
        $unionQuery = $dateRangeQuery->union($kitchenIssueQuery);
        $rows = DB::table(DB::raw("({$unionQuery->toSql()}) as combined_bills"))
            ->mergeBindings($unionQuery->getQuery())
            ->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5000)
            ->get();

        // Load full models for each row so we can group by buyer
        $bills = $rows->map(function ($bill) {
            if ($bill->source_type === 'date_range') {
                $model = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items'])->find($bill->id);
                if ($model) {
                    $model->setAttribute('source_type', 'date_range');
                }
                return $model;
            }
            $model = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items'])->where('pk', $bill->id)->first();
            if ($model) {
                $model->setAttribute('source_type', 'kitchen_issue');
            }
            return $model;
        })->filter()->values();

        // Group by buyer so one combined bill per user (Selling Voucher + Selling Voucher with Date Range)
        $combinedBills = $this->groupBillsByBuyer($bills);

        // Distinct buyer names per type for filters (Employee / OT / Course / Other / Section etc.)
        $bySlug = $bills->groupBy(function ($bill) {
            return $this->getBillClientTypeSlug($bill);
        });
        $otBuyerNames = isset($bySlug[ClientType::TYPE_OT])
            ? $bySlug[ClientType::TYPE_OT]->pluck('client_name')->filter()->unique()->sort()->values()
            : collect();
        $courseBuyerNames = isset($bySlug[ClientType::TYPE_COURSE])
            ? $bySlug[ClientType::TYPE_COURSE]->pluck('client_name')->filter()->unique()->sort()->values()
            : collect();
        $otherBuyerNames = isset($bySlug['other'])
            ? $bySlug['other']->pluck('client_name')->filter()->unique()->sort()->values()
            : collect();
        $sectionBuyerNames = isset($bySlug['section'])
            ? $bySlug['section']->pluck('client_name')->filter()->unique()->sort()->values()
            : collect();

        // All distinct buyer names across both sources (for modal fallback)
        $allBuyerNames = $combinedBills
            ->pluck('client_name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Optional status filter on combined bills (0=Unpaid, 1=Partial, 2=Paid)
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

        $effectiveDateFrom = $request->filled('date_from') ? $request->date_from : now()->startOfMonth()->format('d-m-Y');
        $effectiveDateTo = $request->filled('date_to') ? $request->date_to : now()->endOfMonth()->format('d-m-Y');
        $effectiveDateFromYmd = $dateFrom;
        $effectiveDateToYmd = $dateTo;

        // Stats based on (optionally filtered) combined bills (one per buyer)
        // Stats based on (optionally filtered) combined bills (one per buyer)
        $stats = [
            'total_bills' => $combinedBills->count(),
            'paid_count' => $combinedBills->where('status', 2)->count(),
            'unpaid_count' => $combinedBills->count() - $combinedBills->where('status', 2)->count(),
            'total_amount' => (float) $combinedBills->sum('total'),
        ];

        // Filters for Client Type / Buyer dropdowns (reuse Sale Voucher Report logic)
        $clientTypes = ClientType::clientTypes();
        $clientTypeCategories = ClientType::active()
            ->orderBy('client_type')
            ->orderBy('client_name')
            ->get()
            ->groupBy('client_type');

        $faculties = FacultyMaster::whereNotNull('full_name')
            ->where('full_name', '!=', '')
            ->orderBy('full_name')
            ->get(['pk', 'full_name']);

        $employees = EmployeeMaster::when(Schema::hasColumn('employee_master', 'status'), fn($q) => $q->where('status', 1))
            ->orderBy('first_name')
            ->orderBy('last_name')
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
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['pk', 'first_name', 'middle_name', 'last_name'])
                ->map(function ($e) {
                    $fullName = trim(($e->first_name ?? '') . ' ' . ($e->middle_name ?? '') . ' ' . ($e->last_name ?? ''));
                    return (object) ['pk' => $e->pk, 'full_name' => $fullName ?: '—'];
                })
                ->filter(fn($e) => $e->full_name !== '—')
                ->values()
            : collect();

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
            'statusFilter',
            'clientTypePk',
            'buyerName',
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
            'allBuyerNames'
        ));
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
    private function getBillClientTypeSlug($bill): string
    {
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
        $seed = trim($buyerName) . '|' . $clientTypeSlug;
        $num = abs(crc32($seed)) % 100000;
        return 'CB-' . date('Ymd') . '-' . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Group bills by buyer (client_name + client_type_slug) and build combined bill rows.
     * Returns array of combined bill objects for display and payment.
     * Uses a single combined_invoice_no (e.g. CB-20260312-00123) so slip/receipt shows one invoice like others.
     */
    private function groupBillsByBuyer($bills): \Illuminate\Support\Collection
    {
        $groups = collect($bills)->groupBy(function ($bill) {
            $name = trim((string) ($bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '')));
            $slug = $this->getBillClientTypeSlug($bill);
            return $name . '|' . $slug;
        });

        $paymentTypeMap = [0 => 'Cash', 1 => 'Deduct From Salary', 2 => 'Online', 5 => 'Deduct From Salary'];

        return $groups->map(function ($group, $key) use ($paymentTypeMap) {
            $first = $group->first();
            $buyerName = trim((string) ($first->client_name ?? ($first->clientTypeCategory->client_name ?? '—')));
            $clientTypeSlug = $this->getBillClientTypeSlug($first);
            $combinedId = 'combined-' . rawurlencode($buyerName) . '-' . $clientTypeSlug;
            $combinedInvoiceNo = $this->generateCombinedInvoiceNo($buyerName, $clientTypeSlug);

            $total = (float) $group->sum(fn ($b) => (float) $b->net_total);
            $paid = 0.0;
            foreach ($group as $b) {
                $isDr = $b instanceof SellingVoucherDateRangeReport;
                $paid += $this->getBillPaidAmount($b, $isDr);
            }
            $due = max(0, $total - $paid);

            $dates = $group->map(fn ($b) => $b->issue_date ? $b->issue_date->format('Y-m-d') : null)->filter()->unique()->sort()->values();
            $dateMin = $dates->first();
            $dateMax = $dates->last();
            $invoiceDateRange = $dateMin && $dateMax
                ? (Carbon::parse($dateMin)->format('d-m-Y') . ($dateMin !== $dateMax ? ' to ' . Carbon::parse($dateMax)->format('d-m-Y') : ''))
                : '—';

            $allPaid = $group->every(fn ($b) => ((int) ($b->status ?? 0)) === 2);
            $anyPaid = $group->contains(fn ($b) => ((int) ($b->status ?? 0)) >= 1);
            $status = $allPaid ? 2 : ($anyPaid ? 1 : 0);

            $firstReceiptId = $group->first();
            $firstReceiptId = $firstReceiptId instanceof SellingVoucherDateRangeReport
                ? 'dr-' . $firstReceiptId->id
                : 'ki-' . ($firstReceiptId->pk ?? $firstReceiptId->id);

            return (object) [
                'combined_id' => $combinedId,
                'combined_invoice_no' => $combinedInvoiceNo,
                'buyer_name' => $buyerName,
                'client_type_display' => $first->client_type_display ?? ($first->client_type_label ?? ($first->clientTypeCategory ? ucfirst($first->clientTypeCategory->client_type ?? '') : ucfirst($clientTypeSlug))),
                'invoice_date_range' => $invoiceDateRange,
                'total' => $total,
                'paid' => $paid,
                'due' => $due,
                'status' => $status,
                'payment_type' => $paymentTypeMap[$first->payment_type ?? 1] ?? '—',
                'first_receipt_id' => $firstReceiptId,
                'bills' => $group->values(),
            ];
        })->values();
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
        if ($dateFrom) {
            $dateRangeBase->where(function ($q) use ($dateFrom) {
                $q->where('issue_date', '>=', $dateFrom)->orWhere('date_from', '>=', $dateFrom);
            });
        }
        if ($dateTo) {
            $dateRangeBase->where(function ($q) use ($dateTo) {
                $q->where('issue_date', '<=', $dateTo)->orWhere('date_to', '<=', $dateTo);
            });
        }

        $kitchenClientTypes = $clientType
            ? [$this->clientTypeSlugToKitchenId($clientType)]
            : self::ALLOWED_KITCHEN_CLIENT_TYPES;
        $kitchenBase = KitchenIssueMaster::query()
            ->whereIn('client_type', $kitchenClientTypes)
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER);
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

        $svAmount = (float) (clone $dateRangeBase)->with('items')->get()->sum(fn ($r) => $r->net_total);
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
        $search = $request->filled('search') ? trim((string) $request->search) : null;
        $search = ($search !== null && $search !== '') ? $search : null;
        $clientType = $request->filled('client_type') ? $request->client_type : null;
        $buyerName = $request->filled('buyer_name') ? trim($request->buyer_name) : null;
        $statusFilter = $request->filled('status') ? $request->status : null;
        $statusFilter = $request->filled('status') ? $request->status : null;

        // Same union query as index, but get all results
        $dateRangeQuery = SellingVoucherDateRangeReport::query()
            ->select([
                'id',
                'client_name',
                'issue_date',
                'date_from',
                'client_type_slug',
                'client_type_pk',
                'total_amount',
                'payment_type',
                'status',
                'store_id',
                DB::raw("'date_range' as source_type")
            ])
            ->whereIn('client_type_slug', $clientType ? [$clientType] : self::ALLOWED_CLIENT_SLUGS);

        if ($buyerName) {
            $dateRangeQuery->where('client_name', 'like', '%' . $buyerName . '%');
        }
        if ($dateFrom) {
            $dateRangeQuery->where(function ($q) use ($dateFrom) {
                $q->where('issue_date', '>=', $dateFrom)->orWhere('date_from', '>=', $dateFrom);
            });
        }
        if ($dateTo) {
            $dateRangeQuery->where(function ($q) use ($dateTo) {
                $q->where('issue_date', '<=', $dateTo)->orWhere('date_to', '<=', $dateTo);
            });
        }

        $kitchenClientTypes = $clientType
            ? [$this->clientTypeSlugToKitchenId($clientType)]
            : self::ALLOWED_KITCHEN_CLIENT_TYPES;
        $kitchenIssueQuery = KitchenIssueMaster::query()
            ->select([
                'pk as id',
                'client_name',
                'issue_date',
                DB::raw('NULL as date_from'),
                DB::raw("CASE client_type WHEN 1 THEN 'employee' WHEN 2 THEN 'ot' WHEN 3 THEN 'course' WHEN 4 THEN 'other' END as client_type_slug"),
                'client_type_pk',
                DB::raw('NULL as total_amount'),
                'payment_type',
                'status',
                'store_id',
                DB::raw("'kitchen_issue' as source_type")
            ])
            ->whereIn('client_type', $kitchenClientTypes)
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER);

        if ($buyerName) {
            $kitchenIssueQuery->where('client_name', 'like', '%' . $buyerName . '%');
        }
        if ($dateFrom) {
            $kitchenIssueQuery->where('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $kitchenIssueQuery->where('issue_date', '<=', $dateTo);
        }

        $unionQuery = $dateRangeQuery->union($kitchenIssueQuery);
        $rowsRaw = DB::table(DB::raw("({$unionQuery->toSql()}) as combined_bills"))
            ->mergeBindings($unionQuery->getQuery())
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('client_name', 'like', '%' . $search . '%');
                    if (is_numeric($search)) {
                        $query->orWhere('id', '=', (int) $search);
                    }
                    if (preg_match('/^(?:SV|DR)-(\d+)$/i', $search, $m)) {
                        $query->orWhere('id', '=', (int) $m[1]);
                    }
                });
            })
            ->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $bills = $rowsRaw->map(function ($bill) {
            if ($bill->source_type === 'date_range') {
                $model = SellingVoucherDateRangeReport::with(['clientTypeCategory', 'items'])->find($bill->id);
                if ($model) $model->setAttribute('source_type', 'date_range');
                return $model;
            }
            $model = KitchenIssueMaster::with(['clientTypeCategory', 'items'])->where('pk', $bill->id)->first();
            if ($model) $model->setAttribute('source_type', 'kitchen_issue');
            return $model;
        })->filter()->values();

        $combinedBills = $this->groupBillsByBuyer($bills);

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
        $paymentTypeMap = [0 => 'Cash', 1 => 'Deduct From Salary', 2 => 'Online', 5 => 'Deduct From Salary'];
        $statusMap = [0 => 'Unpaid', 1 => 'Pending', 2 => 'Paid'];

        $rows = [];
        foreach ($combinedBills as $index => $cb) {
            $status = $statusMap[$cb->status ?? 0] ?? '—';
            $rows[] = [
                $index + 1,
                $cb->buyer_name ?? '—',
                $cb->combined_invoice_no ?? '—',
                $cb->invoice_date_range ?? '—',
                $cb->client_type_display ?? '—',
                '₹ ' . number_format($cb->total ?? 0, 2),
                $cb->payment_type ?? '—',
                $status,
            ];
        }

        $effectiveDateFrom = $request->filled('date_from') ? $request->date_from : Carbon::parse($dateFrom)->format('d-m-Y');
        $effectiveDateTo = $request->filled('date_to') ? $request->date_to : Carbon::parse($dateTo)->format('d-m-Y');

        $fileName = 'process-mess-bills-' . $dateFrom . '-to-' . $dateTo . '-' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(
            new ProcessMessBillsExport($rows, $effectiveDateFrom, $effectiveDateTo),
            $fileName
        );
    }

    public function printReceipt(Request $request, $id)
    {
        // Combined bill: single invoice number (CB-...), no individual slip numbers on receipt
        if (is_string($id) && strpos($id, 'combined-') === 0) {
            $bills = $this->resolveCombinedBillBills($request, $id);
            if (empty($bills)) {
                abort(404, 'No bills found for this buyer in the selected date range.');
            }
            $items = [];
            $totalAmount = 0.0;
            $paidAmount = 0.0;
            $storeNames = [];
            $dateMin = $dateMax = null;
            $referenceNumbers = [];
            $orderBys = [];
            $remarksList = [];
            $courseName = null;
            foreach ($bills as $b) {
                $isDr = $b instanceof SellingVoucherDateRangeReport;
                $totalAmount += (float) $b->net_total;
                $paidAmount += $this->getBillPaidAmount($b, $isDr);
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
                    // Prefer per-item issue_date (for Selling Voucher Date Range items),
                    // otherwise fall back to the voucher's issue_date.
                    $itemIssueDate = null;
                    try {
                        if (isset($item->issue_date) && $item->issue_date) {
                            $itemIssueDate = $item->issue_date instanceof Carbon
                                ? $item->issue_date->format('d-m-Y')
                                : Carbon::parse($item->issue_date)->format('d-m-Y');
                        }
                    } catch (\Throwable $e) {
                        $itemIssueDate = null;
                    }
                    // Prefer per-item issue_date (for Selling Voucher Date Range items),
                    // otherwise fall back to the voucher's issue_date.
                    $itemIssueDate = null;
                    try {
                        if (isset($item->issue_date) && $item->issue_date) {
                            $itemIssueDate = $item->issue_date instanceof Carbon
                                ? $item->issue_date->format('d-m-Y')
                                : Carbon::parse($item->issue_date)->format('d-m-Y');
                        }
                    } catch (\Throwable $e) {
                        $itemIssueDate = null;
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
                        'issue_date' => $itemIssueDate ?: $purchaseDateStr,
                    ];
                }
                if ($b->issue_date) {
                    $d = $b->issue_date->format('Y-m-d');
                    if ($dateMin === null || $d < $dateMin) $dateMin = $d;
                    if ($dateMax === null || $d > $dateMax) $dateMax = $d;
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
            $dueAmount = max(0, $totalAmount - $paidAmount);
            $paymentStatusLabel = $paidAmount >= $totalAmount ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Unpaid');
            $buyerName = trim((string) ($bills[0]->client_name ?? ($bills[0]->clientTypeCategory->client_name ?? '—')));
            $clientTypeSlug = $bills[0] instanceof SellingVoucherDateRangeReport
                ? (string) ($bills[0]->client_type_slug ?? 'employee')
                : $this->getBillClientTypeSlug($bills[0]);
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
                'date_from' => $dateMin ? Carbon::parse($dateMin) : null,
                'date_to' => $dateMax ? Carbon::parse($dateMax) : null,
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
                'paymentStatusLabel' => $paymentStatusLabel,
                'invoiceNo' => $invoiceNo,
                'receiptNo' => $invoiceNo,
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
                ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
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
                    ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                    ->where('pk', $id)
                    ->firstOrFail();
            }
        }

        $totalAmount = (float) $bill->net_total;
        $paidAmount = $this->getBillPaidAmount($bill, $isDateRange);
        $dueAmount = max(0, $totalAmount - $paidAmount);
        $paymentStatusLabel = $paidAmount >= $totalAmount ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Unpaid');

        $invoiceNo = $isDateRange
            ? 'DR-' . str_pad($bill->id, 6, '0', STR_PAD_LEFT)
            : 'SV-' . str_pad($bill->pk ?? $bill->id, 6, '0', STR_PAD_LEFT);
        $receiptNo = $invoiceNo;

        return view('admin.mess.process-mess-bills-employee.print-receipt', compact('bill', 'paidAmount', 'dueAmount', 'paymentStatusLabel', 'invoiceNo', 'receiptNo'));
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

            $dateRangeBills = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'course', 'items'])
            ->where('client_type_slug', $clientTypeSlug)
            ->where('client_name', $buyerName)
            ->where(function ($q) use ($dateFrom) {
                $q->where('issue_date', '>=', $dateFrom)->orWhere('date_from', '>=', $dateFrom);
            })
            ->where(function ($q) use ($dateTo) {
                $q->where('issue_date', '<=', $dateTo)->orWhere('date_to', '<=', $dateTo);
            })
            ->orderBy('issue_date')
            ->get();

        $kitchenBills = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'course', 'items'])
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
            ->where('client_type', $this->clientTypeSlugToKitchenId($clientTypeSlug))
            ->where('client_name', $buyerName)
            ->where('issue_date', '>=', $dateFrom)
            ->where('issue_date', '<=', $dateTo)
            ->orderBy('issue_date')
            ->get();

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
            $items = [];
            $totalAmount = 0.0;
            $paidAmount = 0.0;
            $dateMin = null;
            $dateMax = null;
            $clientTypeDisplay = '';
            $storeNames = [];
            foreach ($bills as $bill) {
                $isDr = $bill instanceof SellingVoucherDateRangeReport;
                $totalAmount += (float) $bill->net_total;
                $paidAmount += $this->getBillPaidAmount($bill, $isDr);
                $storeName = $bill->resolved_store_name ?? '—';
                $storeNames[$storeName] = true;
                $purchaseDate = $bill->issue_date ? $bill->issue_date->format('d-m-Y') : '—';
                if ($bill->issue_date) {
                    $d = $bill->issue_date->format('Y-m-d');
                    if ($dateMin === null || $d < $dateMin) $dateMin = $d;
                    if ($dateMax === null || $d > $dateMax) $dateMax = $d;
                }
                if ($clientTypeDisplay === '') {
                    $clientTypeDisplay = $bill->client_type_display ?? ($bill->client_type_label ?? ($bill->clientTypeCategory ? ucfirst($bill->clientTypeCategory->client_type ?? '') : '—'));
                }
                foreach ($bill->items ?? [] as $item) {
                    // Prefer per-item issue_date where available (Selling Voucher Date Range items);
                    // otherwise fall back to the voucher-level issue date.
                    $itemIssueDate = null;
                    try {
                        if (isset($item->issue_date) && $item->issue_date) {
                            $itemIssueDate = $item->issue_date instanceof Carbon
                                ? $item->issue_date->format('d-m-Y')
                                : Carbon::parse($item->issue_date)->format('d-m-Y');
                        }
                    } catch (\Throwable $e) {
                        $itemIssueDate = null;
                    }
                    // Prefer per-item issue_date where available (Selling Voucher Date Range items);
                    // otherwise fall back to the voucher-level issue date.
                    $itemIssueDate = null;
                    try {
                        if (isset($item->issue_date) && $item->issue_date) {
                            $itemIssueDate = $item->issue_date instanceof Carbon
                                ? $item->issue_date->format('d-m-Y')
                                : Carbon::parse($item->issue_date)->format('d-m-Y');
                        }
                    } catch (\Throwable $e) {
                        $itemIssueDate = null;
                    }
                    $items[] = [
                        'store_name' => $storeName,
                        'item_name' => $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                        'issue_date' => $itemIssueDate ?: $purchaseDate,
                        'issue_date' => $itemIssueDate ?: $purchaseDate,
                        'price' => number_format($item->rate ?? 0, 1),
                        'quantity' => $item->quantity,
                        'amount' => number_format($item->amount ?? 0, 2),
                    ];
                }
            }
            $dueAmount = max(0, $totalAmount - $paidAmount);
            $dateFromStr = $dateMin ? Carbon::parse($dateMin)->format('d-m-Y') : '—';
            $dateToStr = $dateMax ? Carbon::parse($dateMax)->format('d-m-Y') : '—';
            $buyerName = trim((string) ($bills[0]->client_name ?? ($bills[0]->clientTypeCategory->client_name ?? '—')));
            $clientTypeSlug = $bills[0] instanceof SellingVoucherDateRangeReport
                ? (string) ($bills[0]->client_type_slug ?? 'employee')
                : $this->getBillClientTypeSlug($bills[0]);
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
                'first_receipt_id' => $firstReceiptId,
                'reference_number' => $referenceNumber ?: null,
                'order_by' => $orderBy ?: null,
                'remarks' => $remarks ?: null,
                'course_name' => $courseName,
            ]);
        }

        [$bill, $isDateRange] = $this->resolveBillById($id);

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

        $totalAmount = (float) $bill->net_total;
        $paidAmount = $this->getBillPaidAmount($bill, $isDateRange);
        $dueAmount = max(0, $totalAmount - $paidAmount);

        $clientTypeDisplay = $bill->client_type_display ?? ($bill->client_type_label ?? ($bill->clientTypeCategory ? ucfirst($bill->clientTypeCategory->client_type ?? '') : ucfirst($bill->client_type_slug ?? '—')));

        $invoiceNo = $isDateRange
            ? 'DR-' . str_pad($bill->id, 6, '0', STR_PAD_LEFT)
            : 'SV-' . str_pad($bill->pk ?? $bill->id, 6, '0', STR_PAD_LEFT);
        $receiptNo = $invoiceNo;

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
            'reference_number' => $bill->reference_number ?? null,
            'order_by' => $bill->order_by ?? null,
            'remarks' => $bill->remarks ?? null,
        ]);
    }

    /**
     * Return JSON data for the ADD modal table (employee bills for date range).
     * Only shows unpaid bills (status != 2).
     */
    public function modalData(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? $this->parseDate($request->date_from) : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $this->parseDate($request->date_to) : now()->endOfMonth()->format('Y-m-d');
        $clientType = $request->filled('client_type') ? $request->client_type : null;
        $clientTypePk = $request->filled('client_type_pk') ? $request->client_type_pk : null;
        $buyerName = $request->filled('buyer_name') ? trim($request->buyer_name) : null;

        // Query 1: Selling Voucher with Date Range
        $dateRangeSlugs = $clientType ? [$clientType] : self::ALLOWED_CLIENT_SLUGS;
        $dateRangeQuery = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items'])
            ->whereIn('client_type_slug', $dateRangeSlugs)
            ->where('status', '!=', 2); // Only unpaid bills

        if ($clientTypePk) {
            $dateRangeQuery->where('client_type_pk', $clientTypePk);
        }
        if ($buyerName) {
            $dateRangeQuery->where('client_name', 'like', '%' . $buyerName . '%');
        }
        if ($dateFrom) {
            $dateRangeQuery->where(function ($q) use ($dateFrom) {
                $q->where('issue_date', '>=', $dateFrom)->orWhere('date_from', '>=', $dateFrom);
            });
        }
        if ($dateTo) {
            $dateRangeQuery->where(function ($q) use ($dateTo) {
                $q->where('issue_date', '<=', $dateTo)->orWhere('date_to', '<=', $dateTo);
            });
        }

        // Query 2: Regular Selling Voucher (Kitchen Issue)
        $kitchenClientTypes = $clientType
            ? [$this->clientTypeSlugToKitchenId($clientType)]
            : self::ALLOWED_KITCHEN_CLIENT_TYPES;
        $kitchenIssueQuery = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items'])
            ->whereIn('client_type', $kitchenClientTypes)
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
            ->where('status', '!=', 2); // Only unpaid bills

        if ($clientTypePk) {
            $kitchenIssueQuery->where('client_type_pk', $clientTypePk);
        }
        if ($buyerName) {
            $kitchenIssueQuery->where('client_name', 'like', '%' . $buyerName . '%');
        }
        if ($dateFrom) {
            $kitchenIssueQuery->where('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $kitchenIssueQuery->where('issue_date', '<=', $dateTo);
        }

        // Get both types and group by buyer (one combined bill per user)
        $dateRangeBills = $dateRangeQuery->get();
        $kitchenIssueBills = $kitchenIssueQuery->get();
        $allBills = $dateRangeBills->concat($kitchenIssueBills)->sortByDesc('issue_date');
        $combinedBills = $this->groupBillsByBuyer($allBills);

        $paymentTypeMap = [0 => 'Cash', 1 => 'Deduct From Salary', 2 => 'Online', 5 => 'Deduct From Salary'];

        $rows = $combinedBills->map(function ($cb, $index) use ($paymentTypeMap) {
            $invoiceNo = $cb->combined_invoice_no ?? ('CB-' . date('Ymd') . '-' . str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT));
            return [
                'id' => $cb->combined_id,
                'bill_id' => $cb->combined_id,
                'slip_no' => $invoiceNo,
                'sno' => $index + 1,
                'buyer_name' => $cb->buyer_name,
                'invoice_no' => $invoiceNo,
                'payment_type' => $cb->payment_type,
                'total' => number_format($cb->total, 2),
                'paid_amount' => number_format($cb->paid, 2),
                'bill_no' => $invoiceNo,
            ];
        })->values();

        return response()->json(['bills' => $rows]);
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
            $referencePk = (int) ($first->id ?? $first->pk ?? 0);
            $receiverUserId = $this->getReceiverUserIdForBill($first, !($first instanceof SellingVoucherDateRangeReport));
            if ($receiverUserId !== null && $receiverUserId > 0) {
                try {
                    app(NotificationService::class)->create(
                        $receiverUserId,
                        'mess',
                        'MessInvoice',
                        $referencePk,
                        'Mess Payment Pending',
                        'Your combined mess bill is pending. Please review and pay via Process Mess Bills.'
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            $clientName = trim((string) ($first->client_name ?? ($first->clientTypeCategory->client_name ?? '—')));
            return response()->json([
                'success' => true,
                'message' => 'Invoice notification sent for combined bill.',
                'bill_id' => $id,
                'client_name' => $clientName,
            ]);
        }

        [$bill, $isDateRange] = $this->resolveBillById($id);
        $isKitchenIssue = !$isDateRange;
        $billId = $bill->id ?? $bill->pk;
        $receiverUserId = $this->getReceiverUserIdForBill($bill, $isKitchenIssue);
        if ($receiverUserId !== null && $receiverUserId > 0) {
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
            }
        }

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
                ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
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
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
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
        if ($isKitchenIssue) {
            $clientType = (int) ($bill->client_type ?? 0);
            $clientId = isset($bill->client_id) ? (int) $bill->client_id : null;

            // Employee (1): client_id = employee_master.pk = user_credentials.user_id
            if ($clientType === KitchenIssueMaster::CLIENT_EMPLOYEE) {
                if ($clientId > 0) {
                    return $clientId;
                }
                $clientName = trim($bill->client_name ?? ($bill->clientTypeCategory->client_name ?? ''));
                return $clientName !== '' ? $this->resolveReceiverUserIdByClientName($clientName) : null;
            }

            // OT (2) / Course (3): client_id = student_master.pk = user_credentials.user_id (user_category='S')
            if (in_array($clientType, [KitchenIssueMaster::CLIENT_OT, KitchenIssueMaster::CLIENT_COURSE], true) && $clientId > 0) {
                return $clientId;
            }

            return null;
        }

        // Selling Voucher Date Range: only employee has single-user mapping (by client_name -> employee)
        $clientName = trim($bill->client_name ?? ($bill->clientTypeCategory->client_name ?? ''));
        if (($bill->client_type_slug ?? '') !== 'employee' || $clientName === '') {
            return null;
        }
        return $this->resolveReceiverUserIdByClientName($clientName);
    }

    /**
     * Resolve user_credentials.user_id from buyer client name (employee full name).
     * Tries exact match first, then LIKE match; returns null if no single match.
     */
    private function resolveReceiverUserIdByClientName(string $clientName): ?int
    {
        $escaped = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $clientName);
        $row = DB::table('user_credentials as uc')
            ->join('employee_master as e', 'uc.user_id', '=', 'e.pk')
            ->whereRaw('TRIM(CONCAT(COALESCE(e.first_name, \'\'), \' \', COALESCE(e.middle_name, \'\'), \' \', COALESCE(e.last_name, \'\'))) = ?', [$clientName])
            ->where('uc.user_category', '!=', 'S')
            ->value('uc.user_id');
        if ($row !== null) {
            return (int) $row;
        }
        $row = DB::table('user_credentials as uc')
            ->join('employee_master as e', 'uc.user_id', '=', 'e.pk')
            ->whereRaw('TRIM(CONCAT(COALESCE(e.first_name, \'\'), \' \', COALESCE(e.middle_name, \'\'), \' \', COALESCE(e.last_name, \'\'))) LIKE ?', [$escaped . '%'])
            ->where('uc.user_category', '!=', 'S')
            ->value('uc.user_id');
        if ($row !== null) {
            return (int) $row;
        }
        if (Schema::hasColumn('user_credentials', 'name')) {
            $row = DB::table('user_credentials')
                ->where('name', $clientName)
                ->where('user_category', '!=', 'S')
                ->value('user_id');
            return $row !== null ? (int) $row : null;
        }
        return null;
    }

    /**
     * Get paid amount for a bill (date range or kitchen issue).
     */
    private function getBillPaidAmount($bill, bool $isDateRange): float
    {
        if ($isDateRange) {
            return (float) ($bill->paid_amount ?? 0);
        }
        $bill->load('paymentDetails');
        return (float) $bill->paymentDetails->sum('paid_amount');
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
        $amount = (float) $amount;
        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount must be greater than zero.',
            ], 400);
        }

        $paymentMode = $request->input('payment_mode', 'cash');
        $paymentDate = $request->filled('payment_date') ? $this->parseDate($request->payment_date) : now()->format('Y-m-d');

        // Combined bill: distribute payment across vouchers (oldest first)
        if (is_string($id) && strpos($id, 'combined-') === 0) {
            $bills = $this->resolveCombinedBillBills($request, $id);
            if (empty($bills)) {
                return response()->json(['success' => false, 'message' => 'No bills found for this buyer in the selected date range.'], 404);
            }
            $totalDue = 0.0;
            foreach ($bills as $b) {
                $isDr = $b instanceof SellingVoucherDateRangeReport;
                $paid = $this->getBillPaidAmount($b, $isDr);
                $totalDue += max(0, (float) $b->net_total - $paid);
            }
            if ($amount > $totalDue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount cannot exceed the balance due (₹ ' . number_format($totalDue, 2) . ').',
                ], 400);
            }
            $remaining = $amount;
            foreach ($bills as $bill) {
                if ($remaining <= 0) break;
                $isDr = $bill instanceof SellingVoucherDateRangeReport;
                $billTotal = (float) $bill->net_total;
                $billPaid = $this->getBillPaidAmount($bill, $isDr);
                $billDue = max(0, $billTotal - $billPaid);
                if ($billDue <= 0) continue;
                $payThis = min($remaining, $billDue);
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
                    $bill->paid_amount = ($bill->paid_amount ?? 0) + $payThis;
                    $bill->status = ($bill->paid_amount >= $billTotal) ? 2 : 1;
                    $bill->save();
                } else {
                    $bill->load('paymentDetails');
                    KitchenIssuePaymentDetail::create([
                        'kitchen_issue_master_pk' => $bill->pk,
                        'paid_amount' => $payThis,
                        'payment_date' => $paymentDate,
                        'payment_mode' => $this->kitchenPaymentModeValue($paymentMode),
                        'transaction_ref' => $request->input('cheque_number'),
                        'remarks' => $request->input('remarks'),
                    ]);
                    $newPaid = $this->getBillPaidAmount($bill, false);
                    $bill->status = ($newPaid >= $billTotal) ? 2 : 1;
                    $bill->save();
                }
                $remaining -= $payThis;
            }
            $clientName = trim((string) ($bills[0]->client_name ?? ($bills[0]->clientTypeCategory->client_name ?? '—')));
            $referencePk = (int) ($bills[0]->id ?? $bills[0]->pk ?? 0);
            $receiverUserId = $this->getReceiverUserIdForBill($bills[0], !($bills[0] instanceof SellingVoucherDateRangeReport));
            if ($receiverUserId !== null && $receiverUserId > 0) {
                try {
                    $isFullPayment = ($remaining <= 0 && $amount >= $totalDue);
                    app(NotificationService::class)->create(
                        $receiverUserId,
                        'mess',
                        'MessPayment',
                        $referencePk,
                        $isFullPayment ? 'Payment Successfully Done' : 'Partial Payment Received',
                        $isFullPayment
                            ? 'Your combined payment of ₹' . number_format($amount, 2) . ' has been successfully completed.'
                            : '₹' . number_format($amount, 2) . ' payment received. Remaining due: ₹ ' . number_format(max(0, $totalDue - $amount), 2)
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            return response()->json([
                'success' => true,
                'full_payment' => $amount >= $totalDue,
                'message' => $amount >= $totalDue
                    ? 'Payment completed successfully. Confirmation sent to user.'
                    : 'Partial payment recorded. Remaining due: ₹ ' . number_format($totalDue - $amount, 2),
                'remaining_due' => max(0, $totalDue - $amount),
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

        $totalAmount = (float) $bill->net_total;
        $paidBefore = $this->getBillPaidAmount($bill, !$isKitchenIssue);
        $dueBefore = max(0, $totalAmount - $paidBefore);

        if ($dueBefore <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'This bill is already fully paid!',
            ], 400);
        }

        if ($amount > $dueBefore) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount cannot exceed the balance due (₹ ' . number_format($dueBefore, 2) . ').',
            ], 400);
        }

        if ($isKitchenIssue) {
            KitchenIssuePaymentDetail::create([
                'kitchen_issue_master_pk' => $bill->pk,
                'paid_amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_mode' => $this->kitchenPaymentModeValue($paymentMode),
                'transaction_ref' => $request->input('cheque_number'),
                'remarks' => $request->input('remarks'),
            ]);
            $paidAfter = $paidBefore + $amount;
            $isFullPayment = $paidAfter >= $totalAmount;
            if ($isFullPayment) {
                $bill->status = 2;
            } else {
                $bill->status = 1; // Partial
            }
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
            $bill->paid_amount = ($bill->paid_amount ?? 0) + $amount;
            $paidAfter = (float) $bill->paid_amount;
            $isFullPayment = $paidAfter >= $totalAmount;
            if ($isFullPayment) {
                $bill->status = 2;
            } else {
                $bill->status = 1; // Partial
            }
            $bill->save();
        }

        $remainingDue = max(0, $totalAmount - $paidAfter);
        $receiverUserId = $this->getReceiverUserIdForBill($bill, $isKitchenIssue);
        $billId = $isKitchenIssue ? $bill->pk : $bill->id;
        $clientName = $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—');

        if ($receiverUserId !== null && $receiverUserId > 0) {
            try {
                if ($isFullPayment) {
                    app(NotificationService::class)->create(
                        $receiverUserId,
                        'mess',
                        'MessPayment',
                        (int) $billId,
                        'Payment Successfully Done',
                        'Your payment of ₹' . number_format($paidAfter, 2) . ' has been successfully completed.'
                    );
                } else {
                    app(NotificationService::class)->create(
                        $receiverUserId,
                        'mess',
                        'MessPayment',
                        (int) $billId,
                        'Partial Payment Received',
                        '₹' . number_format($amount, 2) . ' payment received. ₹' . number_format($remainingDue, 2) . ' is still pending.'
                    );
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

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
}
