<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\Invoice;
use App\Models\Mess\Vendor;
use App\Models\Mess\SaleCounterTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices with date range filter
     */
    public function index(Request $request)
    {
        $query = Invoice::with('vendor');
        
        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('invoice_date', [
                $request->start_date,
                $request->end_date
            ]);
        }
        
        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        // Search by invoice number
        if ($request->filled('search')) {
            $query->where('invoice_no', 'like', '%' . $request->search . '%');
        }
        
        $invoices = $query->orderBy('invoice_date', 'desc')
                          ->paginate(20);
        
        $vendors = Vendor::all();
        
        return view('admin.mess.invoices.index', compact('invoices', 'vendors'));
    }
    
    /**
     * List invoices within a date range
     */
    public function listInvoiceWithDateRange(Request $request)
    {
        return view('admin.mess.invoices.date-range');
    }
    
    /**
     * Get invoice list by date range (AJAX)
     */
    public function getInvoiceList(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);
        
        $invoices = Invoice::with(['vendor', 'buyer'])
            ->whereBetween('invoice_date', [
                $request->start_date,
                $request->end_date
            ])
            ->orderBy('invoice_date', 'desc')
            ->get();
        
        return response()->json($invoices);
    }

    public function create()
    {
        $vendors = Vendor::all();
        $buyers = User::select('pk', 'user_name', 'first_name', 'last_name', 'email_id')->get();
        
        return view('admin.mess.invoices.create', compact('vendors', 'buyers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_no' => 'required|unique:mess_invoices,invoice_no',
            'vendor_id' => 'required|exists:mess_vendors,id',
            'buyer_id' => 'nullable|exists:user_credentials,pk',
            'invoice_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'required|in:cash,cheque,online,credit',
            'payment_status' => 'required|in:pending,paid,partial,overdue',
            'due_date' => 'nullable|date|after:invoice_date',
            'remarks' => 'nullable|string'
        ]);
        
        try {
            DB::beginTransaction();
            
            $invoice = Invoice::create([
                'invoice_no' => $validated['invoice_no'],
                'vendor_id' => $validated['vendor_id'],
                'buyer_id' => $validated['buyer_id'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'amount' => $validated['amount'],
                'paid_amount' => 0,
                'balance' => $validated['amount'],
                'payment_type' => $validated['payment_type'],
                'payment_status' => $validated['payment_status'],
                'due_date' => $validated['due_date'] ?? null,
                'remarks' => $validated['remarks'] ?? null
            ]);
            
            DB::commit();
            
            return redirect()->route('admin.mess.invoices.index')
                ->with('success', 'Invoice created successfully');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice creation failed: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Failed to create invoice. Please try again.');
        }
    }
    
    /**
     * Display the specified invoice
     */
    public function show($id)
    {
        $invoice = Invoice::with(['vendor', 'buyer'])->findOrFail($id);
        return view('admin.mess.invoices.show', compact('invoice'));
    }
    
    /**
     * Edit invoice
     */
    public function edit($id)
    {
        $invoice = Invoice::findOrFail($id);
        $vendors = Vendor::all();
        $buyers = User::select('pk', 'user_name', 'first_name', 'last_name', 'email_id')->get();
        
        return view('admin.mess.invoices.edit', compact('invoice', 'vendors', 'buyers'));
    }
    
    /**
     * Update invoice
     */
    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        
        $validated = $request->validate([
            'payment_type' => 'required|in:cash,cheque,online,credit',
            'payment_status' => 'required|in:pending,paid,partial,overdue',
            'paid_amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string'
        ]);
        
        try {
            DB::beginTransaction();
            
            if (isset($validated['paid_amount'])) {
                $invoice->paid_amount = $validated['paid_amount'];
                $invoice->balance = $invoice->amount - $validated['paid_amount'];
                
                // Auto-update status based on payment
                if ($invoice->balance == 0) {
                    $invoice->payment_status = 'paid';
                } elseif ($invoice->paid_amount > 0 && $invoice->balance > 0) {
                    $invoice->payment_status = 'partial';
                }
            }
            
            $invoice->payment_type = $validated['payment_type'];
            if (isset($validated['payment_status'])) {
                $invoice->payment_status = $validated['payment_status'];
            }
            $invoice->remarks = $validated['remarks'] ?? $invoice->remarks;
            
            $invoice->save();
            
            DB::commit();
            
            return redirect()->route('admin.mess.invoices.index')
                ->with('success', 'Invoice updated successfully');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice update failed: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Failed to update invoice. Please try again.');
        }
    }
    
    /**
     * Check if invoice can be edited (AJAX)
     */
    public function checkEditForInvoice(Request $request)
    {
        $request->validate([
            'invoice_no' => 'required',
            'payment_type' => 'required'
        ]);
        
        $invoice = Invoice::where('invoice_no', $request->invoice_no)
            ->where('payment_type', $request->payment_type)
            ->first();
        
        if ($invoice) {
            return response()->json([
                'success' => true,
                'invoice' => $invoice,
                'editable' => $invoice->payment_status !== 'paid'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Invoice not found'
        ], 404);
    }
    
    /**
     * Save invoice payment type
     */
    public function saveInvoicePaymentType(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:mess_invoices,id',
            'payment_type' => 'required|in:cash,cheque,online,credit'
        ]);
        
        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $invoice->payment_type = $validated['payment_type'];
            $invoice->save();
            
            return redirect()->route('admin.mess.invoices.listInvoiceWithDateRange')
                ->with('success', 'Payment type updated successfully');
                
        } catch (\Exception $e) {
            Log::error('Payment type update failed: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to update payment type');
        }
    }
    
    /**
     * Delete invoice
     */
    public function destroy($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            
            // Check if invoice can be deleted
            if ($invoice->payment_status === 'paid') {
                return back()->with('error', 'Cannot delete a paid invoice');
            }
            
            $invoice->delete();
            
            return redirect()->route('admin.mess.invoices.index')
                ->with('success', 'Invoice deleted successfully');
                
        } catch (\Exception $e) {
            Log::error('Invoice deletion failed: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to delete invoice');
        }
    }
}
