<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\InboundTransaction;
use App\Models\Mess\InboundTransactionItem;
use App\Models\Mess\PurchaseOrder;
use App\Models\Mess\Vendor;
use App\Models\Mess\Store;
use App\Models\Mess\Inventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InboundTransactionController extends Controller
{
    public function index()
    {
        $transactions = InboundTransaction::with(['purchaseOrder', 'vendor', 'store', 'receiver', 'items'])->latest()->get();
        return view('mess.inboundtransactions.index', compact('transactions'));
    }

    public function create(Request $request)
    {
        $purchaseOrders = PurchaseOrder::where('status', 'approved')->with('vendor', 'items.inventory')->get();
        $vendors = Vendor::all();
        $stores = Store::where('status', 'active')->get();
        $inventories = Inventory::all();
        
        $selectedPO = null;
        if ($request->has('purchase_order_id')) {
            $selectedPO = PurchaseOrder::with('items.inventory')->findOrFail($request->purchase_order_id);
        }
        
        $transaction_number = 'IBT' . date('Ymd') . str_pad(InboundTransaction::count() + 1, 4, '0', STR_PAD_LEFT);
        return view('mess.inboundtransactions.create', compact('purchaseOrders', 'vendors', 'stores', 'inventories', 'transaction_number', 'selectedPO'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_number' => 'required|unique:mess_inbound_transactions,transaction_number',
            'purchase_order_id' => 'nullable|exists:mess_purchase_orders,id',
            'vendor_id' => 'required|exists:mess_vendors,id',
            'store_id' => 'required|exists:mess_stores,id',
            'receipt_date' => 'required|date',
            'invoice_number' => 'nullable',
            'invoice_amount' => 'nullable|numeric',
            'items' => 'required|array',
            'items.*.inventory_id' => 'required|exists:mess_inventories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($request) {
            $inboundTransaction = InboundTransaction::create([
                'transaction_number' => $request->transaction_number,
                'purchase_order_id' => $request->purchase_order_id,
                'vendor_id' => $request->vendor_id,
                'store_id' => $request->store_id,
                'receipt_date' => $request->receipt_date,
                'invoice_number' => $request->invoice_number,
                'invoice_amount' => $request->invoice_amount,
                'remarks' => $request->remarks,
                'received_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                $totalPrice = isset($item['unit_price']) ? $item['quantity'] * $item['unit_price'] : null;
                
                InboundTransactionItem::create([
                    'inbound_transaction_id' => $inboundTransaction->id,
                    'inventory_id' => $item['inventory_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'] ?? null,
                    'total_price' => $totalPrice,
                    'remarks' => $item['remarks'] ?? null,
                ]);
                
                // Update inventory stock
                $inventory = Inventory::find($item['inventory_id']);
                if ($inventory) {
                    $inventory->increment('quantity', $item['quantity']);
                }
            }

            // Update PO status if linked
            if ($request->purchase_order_id) {
                $po = PurchaseOrder::find($request->purchase_order_id);
                if ($po) {
                    $po->update(['status' => 'completed']);
                }
            }
        });

        return redirect()->route('mess.inboundtransactions.index')->with('success', 'Inbound transaction created successfully');
    }

    public function show($id)
    {
        $transaction = InboundTransaction::with(['purchaseOrder', 'vendor', 'store', 'receiver', 'items.inventory'])->findOrFail($id);
        return view('mess.inboundtransactions.show', compact('transaction'));
    }
}
