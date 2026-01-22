<?php

namespace App\Http\Controllers;

use App\Models\EstateBilling;
use App\Models\EstatePossession;
use App\Models\EstateMeterReading;
use App\Models\EstatePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EstateBillingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = EstateBilling::with(['possession.unit', 'possession.employee'])
                ->orderBy('bill_date', 'desc')
                ->get();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('employee_name', function($row){
                    return $row->possession && $row->possession->employee ? $row->possession->employee->name : 'N/A';
                })
                ->addColumn('unit_name', function($row){
                    return $row->possession && $row->possession->unit ? $row->possession->unit->unit_name : 'N/A';
                })
                ->addColumn('status', function($row){
                    if ($row->payment_status == 'Paid') {
                        return '<span class="badge bg-success">Paid</span>';
                    } elseif ($row->payment_status == 'Partial') {
                        return '<span class="badge bg-warning">Partial</span>';
                    }
                    return '<span class="badge bg-danger">Unpaid</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<a href="'.route('estate.billing.show', $row->pk).'" class="btn btn-info btn-sm">View</a>';
                    if ($row->payment_status != 'Paid') {
                        $btn .= ' <a href="'.route('estate.billing.payment', $row->pk).'" class="btn btn-success btn-sm">Record Payment</a>';
                    }
                    $btn .= ' <button class="btn btn-danger btn-sm delete-btn" data-id="'.$row->pk.'">Delete</button>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        
        return view('estate.billing.index');
    }

    public function create()
    {
        $possessions = EstatePossession::whereNull('vacation_date')
            ->with(['unit', 'employee'])
            ->get();
        
        return view('estate.billing.create', compact('possessions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'estate_possession_pk' => 'required|exists:estate_possession,pk',
            'bill_month' => 'required|string|max:20',
            'bill_year' => 'required|integer|min:2000',
            'bill_date' => 'required|date',
            'due_date' => 'required|date|after:bill_date',
        ]);

        // Get possession details
        $possession = EstatePossession::with(['unit', 'meterReadings' => function($query) use ($validated) {
            $query->whereYear('reading_date', $validated['bill_year'])
                  ->whereMonth('reading_date', date('m', strtotime($validated['bill_month'])))
                  ->orderBy('reading_date', 'desc')
                  ->limit(1);
        }])->findOrFail($validated['estate_possession_pk']);

        $meterReading = $possession->meterReadings->first();

        $validated['licence_fee'] = $possession->licence_fee ?? 0;
        $validated['water_charge'] = $possession->water_charge ?? 0;
        $validated['electric_charge'] = $meterReading ? $meterReading->electric_charge : 0;
        $validated['total_amount'] = $validated['licence_fee'] + $validated['water_charge'] + $validated['electric_charge'];
        $validated['paid_amount'] = 0;
        $validated['balance_amount'] = $validated['total_amount'];
        $validated['payment_status'] = 'Unpaid';
        $validated['created_by'] = Auth::id();
        $validated['created_date'] = now();

        EstateBilling::create($validated);

        return redirect()->route('estate.billing.index')
            ->with('success', 'Bill generated successfully.');
    }

    public function show(string $id)
    {
        $billing = EstateBilling::with(['possession.unit', 'possession.employee', 'payments'])
            ->findOrFail($id);
        
        return view('estate.billing.show', compact('billing'));
    }

    public function payment(string $id)
    {
        $billing = EstateBilling::with(['possession.unit', 'possession.employee'])
            ->findOrFail($id);
        
        return view('estate.billing.payment', compact('billing'));
    }

    public function storePayment(Request $request, string $id)
    {
        $billing = EstateBilling::findOrFail($id);
        
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0|max:'.$billing->balance_amount,
            'payment_mode' => 'required|in:Cash,Cheque,Online,DD',
            'transaction_reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Create payment record
            $validated['estate_billing_pk'] = $billing->pk;
            $validated['created_by'] = Auth::id();
            $validated['created_date'] = now();
            
            EstatePayment::create($validated);

            // Update billing record
            $paidAmount = $billing->paid_amount + $validated['amount'];
            $balanceAmount = $billing->total_amount - $paidAmount;
            
            $paymentStatus = 'Unpaid';
            if ($balanceAmount == 0) {
                $paymentStatus = 'Paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'Partial';
            }

            $billing->update([
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'payment_status' => $paymentStatus,
                'modify_by' => Auth::id(),
                'modify_date' => now(),
            ]);

            DB::commit();

            return redirect()->route('estate.billing.show', $id)
                ->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        try {
            $billing = EstateBilling::findOrFail($id);
            
            // Delete related payments first
            EstatePayment::where('estate_billing_pk', $id)->delete();
            
            $billing->delete();
            
            return response()->json(['success' => true, 'message' => 'Bill deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting bill: ' . $e->getMessage()], 500);
        }
    }
}
