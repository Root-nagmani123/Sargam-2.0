<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\SalesTransaction;
use App\Models\Mess\SalesTransactionItem;
use App\Models\Mess\PaymentHistory;
use App\Models\Mess\BuyerCreditLimit;
use App\Models\Mess\Store;
use App\Models\Mess\Inventory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    /**
     * Display a listing of sales/bills
     */
    public function index(Request $request)
    {
        $query = SalesTransaction::with(['buyer', 'store', 'items']);
        
        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('sale_date', [
                $request->start_date,
                $request->end_date
            ]);
        }
        
        // Filter by buyer type
        if ($request->filled('buyer_type')) {
            $query->where('buyer_type', $request->buyer_type);
        }
        
        // Filter by payment status
        if ($request->filled('payment_status')) {
            if ($request->payment_status == 'paid') {
                $query->where('due_amount', 0);
            } elseif ($request->payment_status == 'unpaid') {
                $query->where('due_amount', '>', 0)->where('paid_amount', 0);
            } elseif ($request->payment_status == 'partial') {
                $query->where('due_amount', '>', 0)->where('paid_amount', '>', 0);
            }
        }
        
        // Search by bill number or buyer name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhereHas('buyer', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%");
                  });
            });
        }
        
        $sales = $query->orderBy('sale_date', 'desc')
                      ->paginate(20);
        
        return view('admin.mess.billing.index', compact('sales'));
    }

    /**
     * Show form to create a new sale/bill
     */
    public function create()
    {
        $stores = Store::all();
        $buyers = User::select('pk', 'user_name', 'first_name', 'last_name', 'email_id')->get();
        
        return view('admin.mess.billing.create', compact('stores', 'buyers'));
    }
    
    /**
     * Get items by store (AJAX)
     */
    public function getItemsByStore(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:mess_stores,id'
        ]);
        
        $items = Inventory::where('store_id', $request->store_id)
            ->where('quantity', '>', 0)
            ->select('id', 'item_name', 'price', 'quantity')
            ->get();
        
        return response()->json([
            'success' => true,
            'items' => $items
        ]);
    }
    
    /**
     * Find buyers by type (AJAX)
     */
    public function findBuyers(Request $request)
    {
        $request->validate([
            'buyer_type' => 'required|integer'
        ]);
        
        $buyerType = $request->buyer_type;
        $buyers = [];
        
        // 2=OT, 3=Section, 4=Guest, 5=Employee, 6=Other
        if (in_array($buyerType, [2, 3, 4, 5])) {
            $buyers = User::select('pk', 'user_name', 'first_name', 'last_name', 'email_id')
                ->get();
        }
        
        return response()->json([
            'success' => true,
            'buyers' => $buyers
        ]);
    }
    
    /**
     * Get item price (AJAX)
     */
    public function getItemPrice(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:mess_inventories,id'
        ]);
        
        $item = Inventory::findOrFail($request->item_id);
        
        return response()->json([
            'success' => true,
            'price' => $item->price,
            'quantity' => $item->quantity,
            'item_name' => $item->item_name
        ]);
    }
    
    /**
     * Check buyer credit limit (AJAX)
     */
    public function checkCreditLimit(Request $request)
    {
        $request->validate([
            'buyer_id' => 'required',
            'buyer_type' => 'required|integer',
            'amount' => 'required|numeric'
        ]);
        
        $creditLimit = BuyerCreditLimit::where('buyer_id', $request->buyer_id)
            ->where('buyer_type', $request->buyer_type)
            ->first();
        
        if (!$creditLimit) {
            return response()->json([
                'success' => true,
                'has_limit' => false,
                'can_purchase' => true
            ]);
        }
        
        $canPurchase = $creditLimit->available_limit >= $request->amount;
        
        return response()->json([
            'success' => true,
            'has_limit' => true,
            'max_limit' => $creditLimit->max_limit,
            'used_amount' => $creditLimit->used_amount,
            'available_limit' => $creditLimit->available_limit,
            'can_purchase' => $canPurchase
        ]);
    }

    /**
     * Store a new sale/bill
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:mess_stores,id',
            'buyer_type' => 'required|integer',
            'buyer_id' => 'required_unless:buyer_type,6',
            'buyer_name' => 'required_if:buyer_type,6',
            'sale_date' => 'required|date',
            'payment_mode' => 'required|in:cash,cheque,credit',
            'payment_type' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:mess_inventories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0'
        ]);
        
        try {
            DB::beginTransaction();
            
            // Calculate total amount
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['rate'];
            }
            
            // Generate bill number
            $billNumber = 'BILL-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Determine paid/due amounts based on payment type
            $paidAmount = $validated['payment_type'] == 1 ? $totalAmount : 0;
            $dueAmount = $totalAmount - $paidAmount;
            
            // Create sale transaction
            $sale = SalesTransaction::create([
                'bill_number' => $billNumber,
                'store_id' => $validated['store_id'],
                'buyer_id' => $validated['buyer_id'] ?? 0,
                'buyer_type' => $validated['buyer_type'],
                'buyer_name' => $validated['buyer_name'] ?? null,
                'sale_date' => $validated['sale_date'],
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_mode' => $validated['payment_mode'],
                'payment_type' => $validated['payment_type'],
                'paid_unpaid' => $validated['payment_type'] == 1 ? 1 : 0,
                'created_by' => Auth::user()->pk
            ]);
            
            // Create sale items and update inventory
            foreach ($validated['items'] as $item) {
                $amount = $item['quantity'] * $item['rate'];
                
                SalesTransactionItem::create([
                    'sale_transaction_id' => $sale->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'amount' => $amount
                ]);
                
                // Update inventory quantity
                $inventory = Inventory::findOrFail($item['item_id']);
                $inventory->quantity -= $item['quantity'];
                $inventory->save();
            }
            
            // If payment is made, create payment history
            if ($paidAmount > 0) {
                PaymentHistory::create([
                    'sale_transaction_id' => $sale->id,
                    'payment_amount' => $paidAmount,
                    'payment_date' => $validated['sale_date'],
                    'payment_mode' => $validated['payment_mode'],
                    'received_by' => Auth::user()->pk
                ]);
            }
            
            // Update credit limit if applicable
            if ($validated['payment_type'] == 2 && $validated['buyer_type'] != 6) {
                $creditLimit = BuyerCreditLimit::firstOrCreate(
                    [
                        'buyer_id' => $validated['buyer_id'],
                        'buyer_type' => $validated['buyer_type']
                    ],
                    [
                        'max_limit' => 50000,
                        'used_amount' => 0,
                        'available_limit' => 50000
                    ]
                );
                
                $creditLimit->updateUsage($dueAmount);
            }
            
            DB::commit();
            
            return redirect()->route('admin.mess.billing.index')
                ->with('success', 'Bill created successfully. Bill Number: ' . $billNumber);
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bill creation failed: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Failed to create bill. Please try again.');
        }
    }
    
    /**
     * Display the specified sale/bill
     */
    public function show($id)
    {
        $sale = SalesTransaction::with(['buyer', 'store', 'items.item', 'paymentHistory'])
            ->findOrFail($id);
            
        return view('admin.mess.billing.show', compact('sale'));
    }
    
    /**
     * Make payment on a bill
     */
    public function makePayment(Request $request, $id)
    {
        $sale = SalesTransaction::findOrFail($id);
        
        $validated = $request->validate([
            'payment_amount' => 'required|numeric|min:0|max:' . $sale->due_amount,
            'payment_date' => 'required|date',
            'payment_mode' => 'required|in:cash,cheque,online',
            'cheque_number' => 'required_if:payment_mode,cheque',
            'reference_number' => 'nullable|string',
            'remarks' => 'nullable|string'
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create payment history
            PaymentHistory::create([
                'sale_transaction_id' => $sale->id,
                'payment_amount' => $validated['payment_amount'],
                'payment_date' => $validated['payment_date'],
                'payment_mode' => $validated['payment_mode'],
                'cheque_number' => $validated['cheque_number'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'received_by' => Auth::user()->pk
            ]);
            
            // Update sale transaction
            $sale->paid_amount += $validated['payment_amount'];
            $sale->due_amount -= $validated['payment_amount'];
            
            if ($sale->due_amount == 0) {
                $sale->paid_unpaid = 1;
            }
            
            $sale->save();
            
            // Update credit limit if applicable
            if ($sale->buyer_type != 6) {
                $creditLimit = BuyerCreditLimit::where('buyer_id', $sale->buyer_id)
                    ->where('buyer_type', $sale->buyer_type)
                    ->first();
                
                if ($creditLimit) {
                    $creditLimit->updateUsage($validated['payment_amount'], true);
                }
            }
            
            DB::commit();
            
            return redirect()->route('admin.mess.billing.show', $id)
                ->with('success', 'Payment recorded successfully');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment recording failed: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to record payment. Please try again.');
        }
    }
    
    /**
     * Generate due report
     */
    public function dueReport(Request $request)
    {
        $query = SalesTransaction::with(['buyer', 'store'])
            ->where('due_amount', '>', 0);
        
        if ($request->filled('buyer_type')) {
            $query->where('buyer_type', $request->buyer_type);
        }
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('sale_date', [
                $request->start_date,
                $request->end_date
            ]);
        }
        
        $dueTransactions = $query->orderBy('sale_date', 'desc')->get();
        
        $totalDue = $dueTransactions->sum('due_amount');
        
        return view('admin.mess.billing.due-report', compact('dueTransactions', 'totalDue'));
    }
}
