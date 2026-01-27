<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\VendorItemMapping;
use App\Models\Mess\Vendor;
use App\Models\Mess\Inventory;

class VendorItemMappingController extends Controller
{
    public function index()
    {
        $mappings = VendorItemMapping::with(['vendor', 'inventory'])->paginate(20);
        return view('admin.mess.vendor-item-mappings.index', compact('mappings'));
    }

    public function create()
    {
        $vendors = Vendor::where('is_active', true)->get();
        $items = Inventory::where('is_active', true)->get();
        return view('admin.mess.vendor-item-mappings.create', compact('vendors', 'items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:mess_vendors,id',
            'inventory_id' => 'required|exists:mess_inventories,id',
            'rate' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        VendorItemMapping::create($request->all());

        return redirect()->route('admin.mess.vendor-item-mappings.index')
            ->with('success', 'Vendor item mapping created successfully.');
    }

    public function show($id)
    {
        $mapping = VendorItemMapping::with(['vendor', 'inventory'])->findOrFail($id);
        return view('admin.mess.vendor-item-mappings.show', compact('mapping'));
    }

    public function edit($id)
    {
        $mapping = VendorItemMapping::findOrFail($id);
        $vendors = Vendor::where('is_active', true)->get();
        $items = Inventory::where('is_active', true)->get();
        return view('admin.mess.vendor-item-mappings.edit', compact('mapping', 'vendors', 'items'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'vendor_id' => 'required|exists:mess_vendors,id',
            'inventory_id' => 'required|exists:mess_inventories,id',
            'rate' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $mapping = VendorItemMapping::findOrFail($id);
        $mapping->update($request->all());

        return redirect()->route('admin.mess.vendor-item-mappings.index')
            ->with('success', 'Vendor item mapping updated successfully.');
    }

    public function destroy($id)
    {
        $mapping = VendorItemMapping::findOrFail($id);
        $mapping->delete();

        return redirect()->route('admin.mess.vendor-item-mappings.index')
            ->with('success', 'Vendor item mapping deleted successfully.');
    }
}
