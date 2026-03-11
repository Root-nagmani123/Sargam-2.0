<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\SaleCounterMapping;
use App\Models\Mess\SaleCounter;
use App\Models\Mess\Inventory;
use Illuminate\Http\Request;

class SaleCounterMappingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $mappings = SaleCounterMapping::with(['saleCounter', 'inventory'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('admin.mess.sale-counter-mappings.index', compact('mappings'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $counters = SaleCounter::where('is_active', true)->get();
        $items = Inventory::where('is_active', true)->get();
        
        return view('admin.mess.sale-counter-mappings.create', compact('counters', 'items'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'sale_counter_id' => 'required|exists:mess_sale_counters,id',
            'inventory_id' => 'required|exists:mess_inventories,id',
            'available_quantity' => 'required|integer|min:0',
        ]);

        SaleCounterMapping::create([
            'sale_counter_id' => $request->sale_counter_id,
            'inventory_id' => $request->inventory_id,
            'available_quantity' => $request->available_quantity,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.mess.sale-counter-mappings.index')
            ->with('success', 'Sale Counter Mapping created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $mapping = SaleCounterMapping::with(['saleCounter', 'inventory'])->findOrFail($id);
        return view('admin.mess.sale-counter-mappings.show', compact('mapping'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $mapping = SaleCounterMapping::findOrFail($id);
        $counters = SaleCounter::where('is_active', true)->get();
        $items = Inventory::where('is_active', true)->get();
        
        return view('admin.mess.sale-counter-mappings.edit', compact('mapping', 'counters', 'items'));
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
        $mapping = SaleCounterMapping::findOrFail($id);

        $request->validate([
            'sale_counter_id' => 'required|exists:mess_sale_counters,id',
            'inventory_id' => 'required|exists:mess_inventories,id',
            'available_quantity' => 'required|integer|min:0',
        ]);

        $mapping->update([
            'sale_counter_id' => $request->sale_counter_id,
            'inventory_id' => $request->inventory_id,
            'available_quantity' => $request->available_quantity,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.mess.sale-counter-mappings.index')
            ->with('success', 'Sale Counter Mapping updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $mapping = SaleCounterMapping::findOrFail($id);
        $mapping->delete();

        return redirect()->route('admin.mess.sale-counter-mappings.index')
            ->with('success', 'Sale Counter Mapping deleted successfully.');
    }
}
