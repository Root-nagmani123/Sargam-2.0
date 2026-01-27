<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\PurchaseOrder;
use App\Models\Mess\PurchaseOrderItem;
use App\Models\Mess\Vendor;
use App\Models\Mess\Store;
use App\Models\Mess\Inventory;
use App\Models\Mess\MaterialRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['vendor', 'store', 'creator', 'approver', 'items'])->latest()->get();
        return view('mess.purchaseorders.index', compact('purchaseOrders'));
    }

    public function create(Request $request)
    {
        $vendors = Vendor::all();
        $stores = Store::where('is_active', true)->get();
        $inventories = Inventory::all();
        $materialRequest = null;
        
        if ($request->has('material_request_id')) {
            $materialRequest = MaterialRequest::with('items.inventory')->findOrFail($request->material_request_id);
        }
        
        $po_number = 'PO' . date('Ymd') . str_pad(PurchaseOrder::count() + 1, 4, '0', STR_PAD_LEFT);
        return view('mess.purchaseorders.create', compact('vendors', 'stores', 'inventories', 'po_number', 'materialRequest'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'po_number' => 'required|unique:mess_purchase_orders,po_number',
            'vendor_id' => 'required|exists:mess_vendors,id',
            'store_id' => 'nullable|exists:mess_stores,id',
            'po_date' => 'required|date',
            'delivery_date' => 'nullable|date',
            'items' => 'required|array',
            'items.*.inventory_id' => 'required|exists:mess_inventories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $request->po_number,
                'vendor_id' => $request->vendor_id,
                'store_id' => $request->store_id,
                'po_date' => $request->po_date,
                'delivery_date' => $request->delivery_date,
                'total_amount' => $totalAmount,
                'remarks' => $request->remarks,
                'created_by' => Auth::id(),
                'status' => 'pending',
            ]);

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'inventory_id' => $item['inventory_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'description' => $item['description'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.mess.purchaseorders.index')->with('success', 'Purchase order created successfully');
    }

    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with(['vendor', 'store', 'creator', 'approver', 'items.inventory'])->findOrFail($id);
        return view('mess.purchaseorders.show', compact('purchaseOrder'));
    }

    public function approve($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        return redirect()->route('admin.mess.purchaseorders.index')->with('success', 'Purchase order approved successfully');
    }

    public function reject($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $purchaseOrder->update(['status' => 'rejected']);
        return redirect()->route('admin.mess.purchaseorders.index')->with('success', 'Purchase order rejected');
    }
}
