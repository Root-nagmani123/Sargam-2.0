<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\PurchaseOrder;
use App\Models\Mess\PurchaseOrderItem;
use App\Models\Mess\Vendor;
use App\Models\Mess\VendorItemMapping;
use App\Models\Mess\Store;
use App\Models\Mess\Inventory;
use App\Models\Mess\ItemSubcategory;
use App\Models\Mess\MaterialRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['vendor', 'store', 'creator', 'approver', 'items'])->latest()->get();
        $vendors = Vendor::orderBy('name')->get();
        $stores = Store::where('status', 1)->orderBy('store_name')->get();
        $itemSubcategories = ItemSubcategory::active()->orderBy('name')->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'item_name' => $s->item_name ?? $s->name ?? '—',
                'item_code' => $s->item_code ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
            ]);
        $po_number = $this->generatePoNumber();
        $paymentModes = ['Cash' => 'Cash', 'Card' => 'Card', 'UPI' => 'UPI', 'Bank Transfer' => 'Bank Transfer', 'Credit' => 'Credit', 'Other' => 'Other'];
        return view('mess.purchaseorders.index', compact('purchaseOrders', 'vendors', 'stores', 'itemSubcategories', 'po_number', 'paymentModes'));
    }

    public function create(Request $request)
    {
        $vendors = Vendor::all();
        $stores = Store::where('status', 1)->get();
        $inventories = Inventory::all();
        $materialRequest = null;
        
        if ($request->has('material_request_id')) {
            $materialRequest = MaterialRequest::with('items.inventory')->findOrFail($request->material_request_id);
        }
        
        $po_number = $this->generatePoNumber();
        return view('mess.purchaseorders.create', compact('vendors', 'stores', 'inventories', 'po_number', 'materialRequest'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'po_number' => 'required|unique:mess_purchase_orders,po_number',
            'vendor_id' => 'required|exists:mess_vendors,id',
            'store_id' => 'nullable|exists:mess_stores,id',
            'po_date' => 'required|date|before_or_equal:today',
            'delivery_date' => 'nullable|date',
            'payment_code' => 'nullable|string|max:50',
            'delivery_address' => 'nullable|string|max:500',
            'contact_number' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($request) {
            $grandTotal = 0;
            foreach ($request->items as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $taxPercent = isset($item['tax_percent']) ? (float) $item['tax_percent'] : 0;
                $lineTotal = $qty * $unitPrice * (1 + $taxPercent / 100);
                $grandTotal += $lineTotal;
            }

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $request->po_number,
                'vendor_id' => $request->vendor_id,
                'store_id' => $request->store_id ?: null,
                'po_date' => $request->po_date,
                'delivery_date' => $request->delivery_date ?? null,
                'total_amount' => round($grandTotal, 2),
                'payment_code' => $request->payment_code,
                'delivery_address' => $request->delivery_address,
                'contact_number' => $request->contact_number,
                'remarks' => $request->remarks,
                'created_by' => Auth::id(),
                'status' => 'approved',
            ]);

            foreach ($request->items as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $taxPercent = isset($item['tax_percent']) ? (float) $item['tax_percent'] : 0;
                $lineTotal = round($qty * $unitPrice * (1 + $taxPercent / 100), 2);
                $sub = ItemSubcategory::find($item['item_subcategory_id']);
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'inventory_id' => null,
                    'item_subcategory_id' => $item['item_subcategory_id'],
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? ($sub ? ($sub->unit_measurement ?? null) : null),
                    'unit_price' => $unitPrice,
                    'tax_percent' => $taxPercent,
                    'total_price' => $lineTotal,
                    'description' => $item['description'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.mess.purchaseorders.index')->with('success', 'Purchase order created successfully');
    }

    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with(['vendor', 'store', 'creator', 'approver', 'items.inventory', 'items.itemSubcategory'])->findOrFail($id);
        return view('mess.purchaseorders.show', compact('purchaseOrder'));
    }

    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::with(['vendor', 'store', 'items.itemSubcategory'])->findOrFail($id);
        $po = [
            'id' => $purchaseOrder->id,
            'po_number' => $purchaseOrder->po_number,
            'po_date' => $purchaseOrder->po_date->format('Y-m-d'),
            'store_id' => $purchaseOrder->store_id,
            'vendor_id' => $purchaseOrder->vendor_id,
            'store_name' => $purchaseOrder->store->store_name ?? '—',
            'vendor_name' => $purchaseOrder->vendor->name ?? '—',
            'payment_code' => $purchaseOrder->payment_code,
            'contact_number' => $purchaseOrder->contact_number,
            'delivery_address' => $purchaseOrder->delivery_address,
            'remarks' => $purchaseOrder->remarks,
            'status' => $purchaseOrder->status,
        ];
        $items = $purchaseOrder->items->map(function ($item) {
            return [
                'item_subcategory_id' => $item->item_subcategory_id,
                'item_name' => optional($item->itemSubcategory)->item_name ?? '—',
                'item_code' => optional($item->itemSubcategory)->item_code ?? '—',
                'unit' => $item->unit ?? '—',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'tax_percent' => (float) ($item->tax_percent ?? 0),
                'total_price' => (float) $item->total_price,
            ];
        })->values()->toArray();
        return response()->json(['po' => $po, 'items' => $items]);
    }

    public function update(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $request->validate([
            'vendor_id' => 'required|exists:mess_vendors,id',
            'store_id' => 'nullable|exists:mess_stores,id',
            'po_date' => 'required|date|before_or_equal:today',
            'delivery_date' => 'nullable|date',
            'payment_code' => 'nullable|string|max:50',
            'delivery_address' => 'nullable|string|max:500',
            'contact_number' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($request, $purchaseOrder) {
            $grandTotal = 0;
            foreach ($request->items as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $taxPercent = isset($item['tax_percent']) ? (float) $item['tax_percent'] : 0;
                $lineTotal = $qty * $unitPrice * (1 + $taxPercent / 100);
                $grandTotal += $lineTotal;
            }

            $purchaseOrder->update([
                'vendor_id' => $request->vendor_id,
                'store_id' => $request->store_id ?: null,
                'po_date' => $request->po_date,
                'delivery_date' => $request->delivery_date ?? null,
                'total_amount' => round($grandTotal, 2),
                'payment_code' => $request->payment_code,
                'delivery_address' => $request->delivery_address,
                'contact_number' => $request->contact_number,
                'remarks' => $request->remarks,
            ]);

            $purchaseOrder->items()->delete();
            foreach ($request->items as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $taxPercent = isset($item['tax_percent']) ? (float) $item['tax_percent'] : 0;
                $lineTotal = round($qty * $unitPrice * (1 + $taxPercent / 100), 2);
                $sub = ItemSubcategory::find($item['item_subcategory_id']);
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'inventory_id' => null,
                    'item_subcategory_id' => $item['item_subcategory_id'],
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? ($sub ? ($sub->unit_measurement ?? null) : null),
                    'unit_price' => $unitPrice,
                    'tax_percent' => $taxPercent,
                    'total_price' => $lineTotal,
                    'description' => $item['description'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.mess.purchaseorders.index')->with('success', 'Purchase order updated successfully');
    }

    public function destroy($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();
        return redirect()->route('admin.mess.purchaseorders.index')->with('success', 'Purchase order deleted successfully');
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

    public function getVendorItems($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);
        
        // Get vendor item mappings
        $mappings = VendorItemMapping::where('vendor_id', $vendorId)->get();
        
        // Get all mapped item subcategories
        $itemSubcategoryIds = [];
        
        foreach ($mappings as $mapping) {
            if ($mapping->mapping_type === VendorItemMapping::MAPPING_TYPE_ITEM_SUB_CATEGORY && $mapping->item_subcategory_id) {
                // Direct subcategory mapping
                $itemSubcategoryIds[] = $mapping->item_subcategory_id;
            } elseif ($mapping->mapping_type === VendorItemMapping::MAPPING_TYPE_ITEM_CATEGORY && $mapping->item_category_id) {
                // Category mapping - get all subcategories in this category
                $categorySubcategories = ItemSubcategory::where('category_id', $mapping->item_category_id)
                    ->active()
                    ->pluck('id')
                    ->toArray();
                $itemSubcategoryIds = array_merge($itemSubcategoryIds, $categorySubcategories);
            }
        }
        
        // Remove duplicates
        $itemSubcategoryIds = array_unique($itemSubcategoryIds);
        
        // Get the actual item subcategories
        $items = ItemSubcategory::whereIn('id', $itemSubcategoryIds)
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'item_name' => $s->item_name ?? $s->name ?? '—',
                'item_code' => $s->item_code ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
            ]);
        
        return response()->json($items);
    }

    /**
     * Generate a unique Purchase Order number in format PO/{number}/NM.
     */
    protected function generatePoNumber(): string
    {
        $next = ((int) PurchaseOrder::max('id')) + 1;
        $code = 'PO/' . $next . '/NM';

        while (PurchaseOrder::where('po_number', $code)->exists()) {
            $next++;
            $code = 'PO/' . $next . '/NM';
        }

        return $code;
    }
}
