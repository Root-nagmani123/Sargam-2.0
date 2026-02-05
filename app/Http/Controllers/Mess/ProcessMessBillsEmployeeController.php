<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\SellingVoucherDateRangeReport;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Process Mess Bills (Employee) - displays employee mess bills under Billing & Finance.
 * Uses Selling Voucher Date Range reports filtered by employee client type.
 */
class ProcessMessBillsEmployeeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $query = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items'])
            ->where('client_type_slug', 'employee');

        $dateFrom = $request->filled('date_from') ? $this->parseDate($request->date_from) : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $this->parseDate($request->date_to) : now()->endOfMonth()->format('Y-m-d');

        if ($dateFrom) {
            $query->where(function ($q) use ($dateFrom) {
                $q->where('issue_date', '>=', $dateFrom)
                  ->orWhere('date_from', '>=', $dateFrom);
            });
        }
        if ($dateTo) {
            $query->where(function ($q) use ($dateTo) {
                $q->where('issue_date', '<=', $dateTo)
                  ->orWhere('date_to', '<=', $dateTo);
            });
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('clientTypeCategory', function ($q2) use ($search) {
                      $q2->where('client_name', 'like', "%{$search}%")
                         ->orWhere('client_type', 'like', "%{$search}%");
                  });
            });
        }

        $bills = $query->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $effectiveDateFrom = $request->filled('date_from') ? $request->date_from : now()->startOfMonth()->format('d-m-Y');
        $effectiveDateTo = $request->filled('date_to') ? $request->date_to : now()->endOfMonth()->format('d-m-Y');

        return view('admin.mess.process-mess-bills-employee.index', compact('bills', 'effectiveDateFrom', 'effectiveDateTo'));
    }

    public function printReceipt($id)
    {
        $bill = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items'])
            ->where('client_type_slug', 'employee')
            ->findOrFail($id);

        return view('admin.mess.process-mess-bills-employee.print-receipt', compact('bill'));
    }

    /**
     * Return JSON data for the ADD modal table (employee bills for date range).
     */
    public function modalData(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? $this->parseDate($request->date_from) : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->filled('date_to') ? $this->parseDate($request->date_to) : now()->endOfMonth()->format('Y-m-d');

        $query = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items'])
            ->where('client_type_slug', 'employee');

        if ($dateFrom) {
            $query->where(function ($q) use ($dateFrom) {
                $q->where('issue_date', '>=', $dateFrom)->orWhere('date_from', '>=', $dateFrom);
            });
        }
        if ($dateTo) {
            $query->where(function ($q) use ($dateTo) {
                $q->where('issue_date', '<=', $dateTo)->orWhere('date_to', '<=', $dateTo);
            });
        }

        $bills = $query->orderBy('issue_date', 'desc')->orderBy('id', 'desc')->get();

        $paymentTypeMap = [0 => 'Cash', 1 => 'Deduct From Salary', 2 => 'Online', 5 => 'Deduct From Salary'];

        $rows = $bills->map(function ($bill, $index) use ($paymentTypeMap) {
            $total = $bill->total_amount ?? $bill->items->sum('amount');
            return [
                'id' => $bill->id,
                'sno' => $index + 1,
                'buyer_name' => $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—'),
                'invoice_no' => $bill->id,
                'payment_type' => $paymentTypeMap[$bill->payment_type ?? 1] ?? '—',
                'total' => number_format($total, 2),
                'paid_amount' => '0',
                'bill_no' => $bill->id,
            ];
        });

        return response()->json(['bills' => $rows]);
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
