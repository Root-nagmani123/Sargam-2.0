<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\SaleCounter;
use App\Models\Mess\Store;

class SaleCounterController extends Controller
{
    public function index()
    {
        $counters = SaleCounter::with('store')->paginate(20);
        return view('admin.mess.sale-counters.index', compact('counters'));
    }

    public function create()
    {
        $stores = Store::where('is_active', true)->get();
        return view('admin.mess.sale-counters.create', compact('stores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'counter_name' => 'required|string|max:255',
            'counter_code' => 'required|string|unique:mess_sale_counters',
            'store_id' => 'required|exists:mess_stores,id',
            'location' => 'nullable|string'
        ]);

        SaleCounter::create($request->all());
        return redirect()->route('admin.mess.sale-counters.index')->with('success', 'Sale counter created successfully.');
    }

    public function show($id)
    {
        $counter = SaleCounter::with(['store', 'mappings.inventory'])->findOrFail($id);
        return view('admin.mess.sale-counters.show', compact('counter'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $counter = SaleCounter::findOrFail($id);
        $stores = Store::where('is_active', true)->get();
        return view('admin.mess.sale-counters.edit', compact('counter', 'stores'));
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
        $counter = SaleCounter::findOrFail($id);

        $request->validate([
            'counter_name' => 'required|string|max:255',
            'counter_code' => 'required|string|unique:mess_sale_counters,counter_code,' . $id,
            'store_id' => 'required|exists:mess_stores,id',
            'location' => 'nullable|string'
        ]);

        $counter->update([
            'counter_name' => $request->counter_name,
            'counter_code' => $request->counter_code,
            'store_id' => $request->store_id,
            'location' => $request->location,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('admin.mess.sale-counters.index')
            ->with('success', 'Sale counter updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $counter = SaleCounter::findOrFail($id);
        $counter->delete();

        return redirect()->route('admin.mess.sale-counters.index')
            ->with('success', 'Sale counter deleted successfully.');
    }
}
