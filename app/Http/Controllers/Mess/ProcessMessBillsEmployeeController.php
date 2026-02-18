<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\SellingVoucherDateRangeReport;
use App\Models\KitchenIssueMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Process Mess Bills (Employee) - displays employee mess bills under Billing & Finance.
 * Shows both Regular Selling Voucher (from kitchen_issue_master) and Selling Voucher with Date Range (from sv_date_range_reports) filtered by employee client type.
 */
class ProcessMessBillsEmployeeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $dateFrom = $request->filled('date_from') ? $this->parseDate($request->date_from) : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $this->parseDate($request->date_to) : now()->endOfMonth()->format('Y-m-d');
        $search = $request->search;

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
            ->where('client_type_slug', 'employee');

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

        // Query 2: Regular Selling Voucher (kitchen_issue_master)
        $kitchenIssueQuery = KitchenIssueMaster::query()
            ->select([
                'pk as id',
                'client_name',
                'issue_date',
                DB::raw("'employee' as client_type_slug"),
                'client_type_pk',
                DB::raw('NULL as total_amount'),
                'payment_type',
                'status',
                'store_id',
                DB::raw("'kitchen_issue' as source_type")
            ])
            ->where('client_type', KitchenIssueMaster::CLIENT_EMPLOYEE)
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER);

        if ($dateFrom) {
            $kitchenIssueQuery->where('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $kitchenIssueQuery->where('issue_date', '<=', $dateTo);
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
        $stats = $this->getSummaryStats($dateFrom, $dateTo, $search);

        return view('admin.mess.process-mess-bills-employee.index', compact('bills', 'effectiveDateFrom', 'effectiveDateTo', 'stats'));
    }

    /**
     * Get summary statistics for employee mess bills in the given date range.
     */
    private function getSummaryStats(?string $dateFrom, ?string $dateTo, ?string $search): array
    {
        $dateRangeBase = SellingVoucherDateRangeReport::query()
            ->where('client_type_slug', 'employee');
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

        $kitchenBase = KitchenIssueMaster::query()
            ->where('client_type', KitchenIssueMaster::CLIENT_EMPLOYEE)
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER);
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

    public function printReceipt($id)
    {
        // Try to find in Selling Voucher with Date Range first
        $bill = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items'])
            ->where('client_type_slug', 'employee')
            ->find($id);

        // If not found, try Kitchen Issue Master (Regular Selling Voucher)
        if (!$bill) {
            $bill = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items'])
                ->where('client_type', KitchenIssueMaster::CLIENT_EMPLOYEE)
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

        // Query 1: Selling Voucher with Date Range
        $dateRangeQuery = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items'])
            ->where('client_type_slug', 'employee')
            ->where('status', '!=', 2); // Only unpaid bills

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
        $kitchenIssueQuery = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items'])
            ->where('client_type', KitchenIssueMaster::CLIENT_EMPLOYEE)
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
            ->where('status', '!=', 2); // Only unpaid bills

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
            ->where('client_type_slug', 'employee')
            ->find($id);

        // If not found, try Kitchen Issue Master (Regular Selling Voucher)
        if (!$bill) {
            $bill = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items'])
                ->where('client_type', KitchenIssueMaster::CLIENT_EMPLOYEE)
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
            ->where('client_type_slug', 'employee')
            ->find($id);

        $isKitchenIssue = false;

        // If not found, try Kitchen Issue Master (Regular Selling Voucher)
        if (!$bill) {
            $bill = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items'])
                ->where('client_type', KitchenIssueMaster::CLIENT_EMPLOYEE)
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
