<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\FinanceBooking;
use App\Models\Mess\Invoice;
use App\Models\User;

class FinanceBookingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bookings = FinanceBooking::with(['invoice', 'user', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.mess.finance-bookings.index', compact('bookings'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $invoices = Invoice::where('payment_status', '!=', 'paid')->get();
        $users = User::select('pk', 'name', 'email')->get();
        
        return view('admin.mess.finance-bookings.create', compact('invoices', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:mess_invoices,id',
            'user_id' => 'required|exists:user_credentials,pk',
            'booking_amount' => 'required|numeric|min:0',
            'booking_date' => 'required|date',
            'remarks' => 'nullable|string'
        ]);
        
        FinanceBooking::create([
            'invoice_id' => $validated['invoice_id'],
            'user_id' => $validated['user_id'],
            'booking_amount' => $validated['booking_amount'],
            'booking_date' => $validated['booking_date'],
            'remarks' => $validated['remarks'] ?? null,
            'status' => 'pending',
        ]);
        
        return redirect()->route('admin.mess.finance-bookings.index')
            ->with('success', 'Finance booking created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $booking = FinanceBooking::with(['invoice', 'user', 'approver'])->findOrFail($id);
        return view('admin.mess.finance-bookings.show', compact('booking'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $booking = FinanceBooking::findOrFail($id);
        $invoices = Invoice::all();
        $users = User::select('pk', 'name', 'email')->get();
        
        return view('admin.mess.finance-bookings.edit', compact('booking', 'invoices', 'users'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $booking = FinanceBooking::findOrFail($id);
        
        $validated = $request->validate([
            'booking_amount' => 'required|numeric|min:0',
            'booking_date' => 'required|date',
            'remarks' => 'nullable|string'
        ]);
        
        $booking->update($validated);
        
        return redirect()->route('admin.mess.finance-bookings.index')
            ->with('success', 'Finance booking updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $booking = FinanceBooking::findOrFail($id);
        $booking->delete();
        
        return redirect()->route('admin.mess.finance-bookings.index')
            ->with('success', 'Finance booking deleted successfully.');
    }
    
    /**
     * Approve a finance booking
     */
    public function approve($id)
    {
        $booking = FinanceBooking::findOrFail($id);
        $booking->update([
            'status' => 'approved',
            'approved_by' => auth()->user()->pk,
            'approved_at' => now()
        ]);
        
        return redirect()->route('admin.mess.finance-bookings.index')
            ->with('success', 'Finance booking approved successfully.');
    }
    
    /**
     * Reject a finance booking
     */
    public function reject($id)
    {
        $booking = FinanceBooking::findOrFail($id);
        $booking->update([
            'status' => 'rejected',
            'approved_by' => auth()->user()->pk,
            'approved_at' => now()
        ]);
        
        return redirect()->route('admin.mess.finance-bookings.index')
            ->with('success', 'Finance booking rejected successfully.');
    }
}
