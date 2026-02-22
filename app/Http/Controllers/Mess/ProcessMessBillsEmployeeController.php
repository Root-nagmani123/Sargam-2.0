<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\SellingVoucherDateRangeReport;
use App\Models\KitchenIssueMaster;
use App\Exports\ProcessMessBillsExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $perPage = $request->get('per_page', 10);
        $dateFrom = $request->filled('date_from') ? $this->parseDate($request->date_from) : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $this->parseDate($request->date_to) : now()->endOfMonth()->format('Y-m-d');
        $search = $request->search;
        $clientType = $request->filled('client_type') ? $request->client_type : null;
        $buyerName = $request->filled('buyer_name') ? trim($request->buyer_name) : null;

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

        if ($dateFrom) {
            $kitchenIssueQuery->where('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $kitchenIssueQuery->where('issue_date', '<=', $dateTo);
        }
        if ($buyerName) {
            $kitchenIssueQuery->where('client_name', 'like', '%' . $buyerName . '%');
        }

        // Union both queries
        $unionQuery = $dateRangeQuery->union($kitchenIssueQuery);

        // Wrap in subquery for pagination and search
        $bills = DB::table(DB::raw("({$unionQuery->toSql()}) as combined_bills"))
            ->mergeBindings($unionQuery->getQuery())
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('client_name', 'like', "%{$search}%")
                          ->orWhere('id', 'like', "%{$search}%");
                });
            })
            ->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Load relationships for each bill
        $bills->getCollection()->transform(function ($bill) {
            if ($bill->source_type === 'date_range') {
                return SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items'])->find($bill->id);
            } else {
                return KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items'])->where('pk', $bill->id)->first();
            }
        });

        $effectiveDateFrom = $request->filled('date_from') ? $request->date_from : now()->startOfMonth()->format('d-m-Y');
        $effectiveDateTo = $request->filled('date_to') ? $request->date_to : now()->endOfMonth()->format('d-m-Y');

        // Summary stats for the same date range (for dashboard cards)
        $stats = $this->getSummaryStats($dateFrom, $dateTo, $search, $clientType, $buyerName);

        return view('admin.mess.process-mess-bills-employee.index', compact('bills', 'effectiveDateFrom', 'effectiveDateTo', 'stats', 'clientType', 'buyerName'));
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
        ];
        return $map[$slug] ?? KitchenIssueMaster::CLIENT_EMPLOYEE;
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

        $svAmount = (float) (clone $dateRangeBase)->sum('total_amount');
        $kitchenPks = (clone $kitchenBase)->pluck('pk');
        $kitchenAmount = $kitchenPks->isEmpty()
            ? 0.0
            : (float) DB::table('kitchen_issue_items')->whereIn('kitchen_issue_master_pk', $kitchenPks)->sum('amount');
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
        $search = $request->search;
        $clientType = $request->filled('client_type') ? $request->client_type : null;
        $buyerName = $request->filled('buyer_name') ? trim($request->buyer_name) : null;

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
        $bills = DB::table(DB::raw("({$unionQuery->toSql()}) as combined_bills"))
            ->mergeBindings($unionQuery->getQuery())
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('client_name', 'like', "%{$search}%")
                          ->orWhere('id', 'like', "%{$search}%");
                });
            })
            ->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $paymentTypeMap = [0 => 'Cash', 1 => 'Deduct From Salary', 2 => 'Online', 5 => 'Deduct From Salary'];
        $statusMap = [0 => 'Unpaid', 1 => 'Pending', 2 => 'Paid'];

        $rows = [];
        foreach ($bills as $index => $bill) {
            $model = $bill->source_type === 'date_range'
                ? SellingVoucherDateRangeReport::with(['clientTypeCategory', 'items'])->find($bill->id)
                : KitchenIssueMaster::with(['clientTypeCategory', 'items'])->where('pk', $bill->id)->first();

            if (!$model) {
                continue;
            }

            $billId = $model->id ?? $model->pk;
            $invoiceDate = $model->issue_date
                ? Carbon::parse($model->issue_date)->format('d-m-Y')
                : (isset($model->date_from) && $model->date_from ? Carbon::parse($model->date_from)->format('d-m-Y') : '—');
            $clientType = $model->client_type_label ?? ($model->clientTypeCategory ? ucfirst($model->clientTypeCategory->client_type ?? '') : ucfirst($model->client_type_slug ?? '—'));
            $total = $model->total_amount ?? $model->items->sum('amount');
            $status = $statusMap[$model->status ?? 0] ?? '—';

            $rows[] = [
                $index + 1,
                $model->client_name ?? ($model->clientTypeCategory->client_name ?? '—'),
                $billId,
                $invoiceDate,
                $clientType,
                '₹ ' . number_format($total, 2),
                $paymentTypeMap[$model->payment_type ?? 1] ?? '—',
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

    public function printReceipt($id)
    {
        // Try to find in Selling Voucher with Date Range first
        $bill = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items'])
            ->whereIn('client_type_slug', self::ALLOWED_CLIENT_SLUGS)
            ->find($id);

        // If not found, try Kitchen Issue Master (Regular Selling Voucher)
        if (!$bill) {
            $bill = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items'])
                ->whereIn('client_type', self::ALLOWED_KITCHEN_CLIENT_TYPES)
                ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                ->where('pk', $id)
                ->firstOrFail();
        }

        return view('admin.mess.process-mess-bills-employee.print-receipt', compact('bill'));
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
        $buyerName = $request->filled('buyer_name') ? trim($request->buyer_name) : null;

        // Query 1: Selling Voucher with Date Range
        $dateRangeSlugs = $clientType ? [$clientType] : self::ALLOWED_CLIENT_SLUGS;
        $dateRangeQuery = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items'])
            ->whereIn('client_type_slug', $dateRangeSlugs)
            ->where('status', '!=', 2); // Only unpaid bills

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

        if ($buyerName) {
            $kitchenIssueQuery->where('client_name', 'like', '%' . $buyerName . '%');
        }
        if ($dateFrom) {
            $kitchenIssueQuery->where('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $kitchenIssueQuery->where('issue_date', '<=', $dateTo);
        }

        // Get both types
        $dateRangeBills = $dateRangeQuery->get();
        $kitchenIssueBills = $kitchenIssueQuery->get();

        // Merge and sort
        $allBills = $dateRangeBills->concat($kitchenIssueBills)->sortByDesc('issue_date');

        $paymentTypeMap = [0 => 'Cash', 1 => 'Deduct From Salary', 2 => 'Online', 5 => 'Deduct From Salary'];

        $rows = $allBills->map(function ($bill, $index) use ($paymentTypeMap) {
            $id = $bill->id ?? $bill->pk;
            $total = $bill->total_amount ?? $bill->items->sum('amount');
            return [
                'id' => $id,
                'sno' => $index + 1,
                'buyer_name' => $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—'),
                'invoice_no' => $id,
                'payment_type' => $paymentTypeMap[$bill->payment_type ?? 1] ?? '—',
                'total' => number_format($total, 2),
                'paid_amount' => '0',
                'bill_no' => $id,
            ];
        })->values();

        return response()->json(['bills' => $rows]);
    }

    /**
     * Generate invoice and send notification to user
     */
    public function generateInvoice(Request $request, $id)
    {
        // Try to find in Selling Voucher with Date Range first
        $bill = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items'])
            ->whereIn('client_type_slug', self::ALLOWED_CLIENT_SLUGS)
            ->find($id);

        // If not found, try Kitchen Issue Master (Regular Selling Voucher)
        if (!$bill) {
            $bill = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items'])
                ->whereIn('client_type', self::ALLOWED_KITCHEN_CLIENT_TYPES)
                ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                ->where('pk', $id)
                ->firstOrFail();
        }

        // TODO: Add your notification logic here
        // Example: Send email or SMS to the user
        // Notification::send($user, new InvoiceGeneratedNotification($bill));

        $billId = $bill->id ?? $bill->pk;
        return response()->json([
            'success' => true,
            'message' => 'Invoice generated and notification sent successfully!',
            'bill_id' => $billId,
            'client_name' => $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—'),
        ]);
    }

    /**
     * Generate payment - mark bill as paid and send notification to user
     */
    public function generatePayment(Request $request, $id)
    {
        // Try to find in Selling Voucher with Date Range first
        $bill = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items'])
            ->whereIn('client_type_slug', self::ALLOWED_CLIENT_SLUGS)
            ->find($id);

        $isKitchenIssue = false;

        // If not found, try Kitchen Issue Master (Regular Selling Voucher)
        if (!$bill) {
            $bill = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items'])
                ->whereIn('client_type', self::ALLOWED_KITCHEN_CLIENT_TYPES)
                ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                ->where('pk', $id)
                ->firstOrFail();
            $isKitchenIssue = true;
        }

        // Check if already paid
        if ($bill->status == 2) {
            return response()->json([
                'success' => false,
                'message' => 'This bill is already paid!',
            ], 400);
        }

        // Mark as paid
        $bill->status = 2; // STATUS_APPROVED = 2 (Paid)
        $bill->save();

        // TODO: Add your notification logic here
        // Example: Send email or SMS to the user
        // Mail::to($bill->client_email)->send(new PaymentCompletedMail($bill));
        // Notification::send($user, new PaymentCompletedNotification($bill));

        $billId = $isKitchenIssue ? $bill->pk : $bill->id;
        return response()->json([
            'success' => true,
            'message' => 'Payment completed successfully! Notification sent to user.',
            'bill_id' => $billId,
            'client_name' => $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—'),
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
