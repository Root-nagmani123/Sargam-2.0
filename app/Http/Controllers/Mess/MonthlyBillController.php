<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\MonthlyBill;
use App\Models\User;
use Illuminate\Http\Request;

class MonthlyBillController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = MonthlyBill::with('user');
        
        // Filter by month/year
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Search by user name or bill number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%");
                  });
            });
        }
        
        $bills = $query->orderBy('year', 'desc')
                      ->orderBy('month', 'desc')
                      ->paginate(20);
        
        return view('admin.mess.monthly-bills.index', compact('bills'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $users = User::select('pk', 'user_name', 'first_name', 'last_name', 'email_id')->get();
        return view('admin.mess.monthly-bills.create', compact('users'));
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
            'user_id' => 'required|exists:user_credentials,pk',
            'month_year' => 'required|date_format:Y-m',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|in:unpaid,paid,partial',
            'paid_amount' => 'nullable|numeric|min:0',
            'paid_date' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);
        
        // Parse month and year from month_year
        $monthYear = explode('-', $validated['month_year']);
        $year = (int)$monthYear[0];
        $month = (int)$monthYear[1];
        
        // Check if bill already exists for this user/month/year
        $existing = MonthlyBill::where('user_id', $validated['user_id'])
            ->where('month', $month)
            ->where('year', $year)
            ->first();
        
        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'A monthly bill for this user and month already exists.');
        }
        
        // Generate unique bill number with counter
        $count = MonthlyBill::where('month', $month)
            ->where('year', $year)
            ->count();
        $billNumber = 'MB-' . $year . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);
        
        // Calculate balance
        $paidAmount = $validated['paid_amount'] ?? 0;
        $balance = $validated['total_amount'] - $paidAmount;
        
        MonthlyBill::create([
            'user_id' => $validated['user_id'],
            'bill_number' => $billNumber,
            'month' => $month,
            'year' => $year,
            'month_year' => $validated['month_year'] . '-01',
            'total_amount' => $validated['total_amount'],
            'paid_amount' => $paidAmount,
            'balance' => $balance,
            'status' => $validated['status'],
            'paid_date' => $validated['paid_date'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ]);
        
        return redirect()->route('admin.mess.monthly-bills.index')
            ->with('success', 'Monthly bill created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $bill = MonthlyBill::with('user')->findOrFail($id);
        return view('admin.mess.monthly-bills.show', compact('bill'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $bill = MonthlyBill::with('user')->findOrFail($id);
        $users = User::select('pk', 'user_name', 'first_name', 'last_name', 'email_id')->get();
        return view('admin.mess.monthly-bills.edit', compact('bill', 'users'));
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
        $bill = MonthlyBill::findOrFail($id);
        
        $validated = $request->validate([
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,paid,partial,overdue',
            'paid_date' => 'nullable|date',
        ]);
        
        if (isset($validated['paid_amount'])) {
            $bill->paid_amount = $validated['paid_amount'];
            $bill->balance = $bill->total_amount - $validated['paid_amount'];
        }
        
        $bill->status = $validated['status'];
        
        if (isset($validated['paid_date'])) {
            $bill->paid_date = $validated['paid_date'];
        }
        
        $bill->save();
        
        return redirect()->route('admin.mess.monthly-bills.index')
            ->with('success', 'Monthly bill updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $bill = MonthlyBill::findOrFail($id);
        $bill->delete();
        
        return redirect()->route('admin.mess.monthly-bills.index')
            ->with('success', 'Monthly bill deleted successfully.');
    }
    
    /**
     * Generate monthly bills for all users
     */
    public function generateBills(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
            'amount' => 'required|numeric|min:0',
        ]);
        
        $users = User::all();
        $count = 0;
        
        foreach ($users as $user) {
            // Check if bill already exists
            $exists = MonthlyBill::where('user_id', $user->pk)
                ->where('month', $validated['month'])
                ->where('year', $validated['year'])
                ->exists();
            
            if (!$exists) {
                $billNumber = 'MB-' . $validated['year'] . str_pad($validated['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($user->pk, 6, '0', STR_PAD_LEFT);
                
                MonthlyBill::create([
                    'user_id' => $user->pk,
                    'bill_number' => $billNumber,
                    'month' => $validated['month'],
                    'year' => $validated['year'],
                    'total_amount' => $validated['amount'],
                    'paid_amount' => 0,
                    'balance' => $validated['amount'],
                    'status' => 'pending',
                ]);
                
                $count++;
            }
        }
        
        return redirect()->route('admin.mess.monthly-bills.index')
            ->with('success', "Generated {$count} monthly bills successfully.");
    }
}
