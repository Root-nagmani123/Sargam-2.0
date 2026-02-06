<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\StoreAllocation;
use App\Models\Mess\StoreAllocationItem;
use App\Models\Mess\SubStore;
use App\Models\Mess\ItemSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreAllocationController extends Controller
{
    public function index()
    {
        $allocations = StoreAllocation::with(['subStore', 'items.itemSubcategory.category'])
            ->whereNotNull('sub_store_id')
            ->latest('allocation_date')
            ->latest('id')
            ->get();

        $subStores = SubStore::active()->orderBy('sub_store_name')->get();
        $itemSubcategories = ItemSubcategory::active()->orderBy('name')->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'item_name' => $s->item_name ?? $s->name ?? '—',
                'item_code' => $s->item_code ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
            ]);

        return view('mess.storeallocations.index', compact('allocations', 'subStores', 'itemSubcategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sub_store_id' => 'required|exists:mess_sub_stores,id',
            'allocation_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $allocation = StoreAllocation::create([
                'sub_store_id' => $request->sub_store_id,
                'allocation_date' => $request->allocation_date,
            ]);

            foreach ($request->items as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $totalPrice = round($qty * $unitPrice, 2);
                $sub = ItemSubcategory::find($item['item_subcategory_id']);
                StoreAllocationItem::create([
                    'store_allocation_id' => $allocation->id,
                    'item_subcategory_id' => $item['item_subcategory_id'],
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? ($sub ? ($sub->unit_measurement ?? null) : null),
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);
            }
        });

        return redirect()->route('admin.mess.storeallocations.index')->with('success', 'Store allocation added successfully.');
    }

    public function edit($id)
    {
        $allocation = StoreAllocation::with(['subStore', 'items.itemSubcategory'])->whereNotNull('sub_store_id')->findOrFail($id);
        $data = [
            'id' => $allocation->id,
            'sub_store_id' => $allocation->sub_store_id,
            'allocation_date' => $allocation->allocation_date ? $allocation->allocation_date->format('Y-m-d') : null,
        ];
        $items = $allocation->items->map(function ($item) {
            return [
                'item_subcategory_id' => $item->item_subcategory_id,
                'item_name' => optional($item->itemSubcategory)->item_name ?? '—',
                'item_code' => optional($item->itemSubcategory)->item_code ?? '—',
                'unit' => $item->unit ?? '—',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ];
        })->values()->toArray();
        return response()->json(['allocation' => $data, 'items' => $items]);
    }

    public function update(Request $request, $id)
    {
        $allocation = StoreAllocation::whereNotNull('sub_store_id')->findOrFail($id);
        $request->validate([
            'sub_store_id' => 'required|exists:mess_sub_stores,id',
            'allocation_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $allocation) {
            $allocation->update([
                'sub_store_id' => $request->sub_store_id,
                'allocation_date' => $request->allocation_date,
            ]);
            $allocation->items()->delete();
            foreach ($request->items as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $totalPrice = round($qty * $unitPrice, 2);
                $sub = ItemSubcategory::find($item['item_subcategory_id']);
                StoreAllocationItem::create([
                    'store_allocation_id' => $allocation->id,
                    'item_subcategory_id' => $item['item_subcategory_id'],
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? ($sub ? ($sub->unit_measurement ?? null) : null),
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);
            }
        });

        return redirect()->route('admin.mess.storeallocations.index')->with('success', 'Store allocation updated successfully.');
    }

    public function destroy($id)
    {
        $allocation = StoreAllocation::whereNotNull('sub_store_id')->findOrFail($id);
        $allocation->items()->delete();
        $allocation->delete();
        return redirect()->route('admin.mess.storeallocations.index')->with('success', 'Store allocation deleted successfully.');
    }
}
