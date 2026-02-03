<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\Invoice;
use App\Models\Mess\KitchenIssue;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessMessBillController extends Controller
{
    /**
     * Display the main process bills page
     */
    public function index()
    {
        return view('admin.mess.process-bills.index');
    }

    /**
     * Display all employee bills for processing (bulk invoice generation)
     */
    public function allEmployeeBills(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $paymentMode = $request->input('payment_mode', 'salary_deduction');
        $action = $request->input('action', 'view');

        try {
            // Check if mess_kitchen_issues table exists and has data
            if (!DB::getSchemaBuilder()->hasTable('mess_kitchen_issues')) {
                $bills = collect([]);
            } else {
                // Get all bills for employees in the date range
                // Simplified query to avoid issues with missing columns
                $bills = DB::table('mess_kitchen_issues as ki')
                    ->leftJoin('users', 'ki.buyer_id', '=', 'users.id')
                    ->leftJoin('mess_invoices as inv', function($join) {
                        $join->on('ki.bill_no', '=', 'inv.bill_no')
                             ->whereColumn('inv.is_deleted', '=', DB::raw('0'));
                    })
                    ->select(
                        'ki.buyer_id',
                        DB::raw('COALESCE(users.name, ki.buyer_name, "Unknown") as buyer_name'),
                        DB::raw('COALESCE(inv.invoice_no, "N/A") as invoice_no'),
                        DB::raw('COALESCE(inv.payment_type, "' . $paymentMode . '") as payment_type'),
                        DB::raw('SUM(ki.total_amount) as total'),
                        DB::raw('SUM(CASE WHEN inv.id IS NOT NULL THEN ki.total_amount ELSE 0 END) as paid_amount'),
                        DB::raw('GROUP_CONCAT(DISTINCT ki.bill_no SEPARATOR ",") as bill_numbers')
                    )
                    ->whereBetween('ki.issue_date', [$startDate, $endDate])
                    ->where(function($query) {
                        $query->where('ki.client_type', '!=', 'guest')
                              ->orWhereNull('ki.client_type');
                    })
                    ->where('ki.is_deleted', 0)
                    ->groupBy('ki.buyer_id', 'users.name', 'ki.buyer_name', 'inv.invoice_no', 'inv.payment_type')
                    ->get();
            }
        } catch (\Exception $e) {
            \Log::error('Error fetching employee bills: ' . $e->getMessage());
            $bills = collect([]);
        }

        return view('admin.mess.process-bills.employee-bills', compact(
            'bills',
            'startDate',
            'endDate',
            'paymentMode',
            'action'
        ));
    }

    /**
     * Get employee bill details
     */
    public function employeeBillDetails(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $details = DB::table('mess_kitchen_issues')
            ->where('buyer_id', $employeeId)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->where('is_deleted', 0)
            ->get();

        return response()->json($details);
    }

    /**
     * Generate bulk invoices for employees
     */
    public function generateBulkInvoices(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'payment_mode' => 'required|in:salary_deduction,cash,online,card',
            'invoice_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $selectedBills = $request->input('selected_bills', []);
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $paymentMode = $request->input('payment_mode');
            $invoiceDate = $request->input('invoice_date');

            $invoicesGenerated = 0;

            // Get unique buyers from kitchen issues in the date range
            $buyers = DB::table('mess_kitchen_issues')
                ->select('buyer_id')
                ->whereBetween('issue_date', [$startDate, $endDate])
                ->where('is_deleted', 0)
                ->where('client_type', '!=', 'guest')
                ->distinct()
                ->pluck('buyer_id');

            foreach ($buyers as $buyerId) {
                // Get all bills for this buyer
                $bills = DB::table('mess_kitchen_issues')
                    ->where('buyer_id', $buyerId)
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->where('is_deleted', 0)
                    ->get();

                if ($bills->isEmpty()) {
                    continue;
                }

                // Calculate total amount
                $totalAmount = $bills->sum('total_amount');
                $billNumbers = $bills->pluck('bill_no')->unique()->implode(',');

                // Check if invoice already exists
                $existingInvoice = Invoice::where('buyer_id', $buyerId)
                    ->whereBetween('invoice_date', [$startDate, $endDate])
                    ->where('is_deleted', 0)
                    ->first();

                if ($existingInvoice) {
                    // Update existing invoice
                    $existingInvoice->update([
                        'total_amount' => $totalAmount,
                        'bill_no' => $billNumbers,
                        'payment_type' => $paymentMode,
                    ]);
                } else {
                    // Generate new invoice number
                    $invoiceNo = $this->generateInvoiceNumber();

                    // Create new invoice
                    Invoice::create([
                        'invoice_no' => $invoiceNo,
                        'buyer_id' => $buyerId,
                        'bill_no' => $billNumbers,
                        'invoice_date' => $invoiceDate,
                        'total_amount' => $totalAmount,
                        'payment_type' => $paymentMode,
                        'status' => 'pending',
                        'is_deleted' => 0,
                        'created_by' => auth()->id(),
                    ]);

                    $invoicesGenerated++;
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.mess.process-bills.employee-bills', [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'payment_mode' => $paymentMode
                ])
                ->with('success', "Successfully generated {$invoicesGenerated} bulk invoices for employees.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error generating bulk invoices: ' . $e->getMessage());
        }
    }

    /**
     * Generate single invoice
     */
    public function generateSingleInvoice(Request $request)
    {
        $request->validate([
            'buyer_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'payment_mode' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $buyerId = $request->input('buyer_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $paymentMode = $request->input('payment_mode');
            $invoiceDate = $request->input('invoice_date', now()->format('Y-m-d'));

            // Get all bills for this buyer
            $bills = DB::table('mess_kitchen_issues')
                ->where('buyer_id', $buyerId)
                ->whereBetween('issue_date', [$startDate, $endDate])
                ->where('is_deleted', 0)
                ->get();

            if ($bills->isEmpty()) {
                return back()->with('error', 'No bills found for this employee in the selected date range.');
            }

            $totalAmount = $bills->sum('total_amount');
            $billNumbers = $bills->pluck('bill_no')->unique()->implode(',');
            $invoiceNo = $this->generateInvoiceNumber();

            Invoice::create([
                'invoice_no' => $invoiceNo,
                'buyer_id' => $buyerId,
                'bill_no' => $billNumbers,
                'invoice_date' => $invoiceDate,
                'total_amount' => $totalAmount,
                'payment_type' => $paymentMode,
                'status' => 'pending',
                'is_deleted' => 0,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return back()->with('success', 'Invoice generated successfully. Invoice No: ' . $invoiceNo);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error generating invoice: ' . $e->getMessage());
        }
    }

    /**
     * Generate invoice for guests/others
     */
    public function generateGuestInvoice(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'invoice_date' => 'required|date',
            'payment_mode' => 'required',
        ]);

        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $paymentMode = $request->input('payment_mode');
            $invoiceDate = $request->input('invoice_date');

            // Redirect to list with filter to show guest invoices
            return redirect()->route('admin.mess.process-bills.guest-list', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'payment_mode' => $paymentMode,
                'invoice_date' => $invoiceDate
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Get invoices by date range
     */
    public function getInvoicesByDate(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $invoices = Invoice::with('buyer')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->where('is_deleted', 0)
            ->get();

        return response()->json($invoices);
    }

    /**
     * Get bills by employee
     */
    public function getBillsByEmployee(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $bills = DB::table('mess_kitchen_issues')
            ->where('buyer_id', $employeeId)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->where('is_deleted', 0)
            ->get();

        return response()->json($bills);
    }

    /**
     * Approve invoices
     */
    public function approveInvoices(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids', []);

        try {
            Invoice::whereIn('id', $invoiceIds)
                ->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

            return back()->with('success', 'Invoices approved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error approving invoices: ' . $e->getMessage());
        }
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        
        $lastInvoice = Invoice::where('invoice_no', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('invoice_no', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_no, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }
}
