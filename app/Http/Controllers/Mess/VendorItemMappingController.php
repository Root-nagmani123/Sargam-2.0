<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\DataTables\Mess\VendorItemMappingDataTable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Mess\VendorItemMapping;
use App\Models\Mess\Vendor;
use App\Models\Mess\ItemSubcategory;

class VendorItemMappingController extends Controller
{
    public function index(VendorItemMappingDataTable $dataTable)
    {
<<<<<<< HEAD
        $mappings = VendorItemMapping::with(['vendor', 'itemCategory', 'itemSubcategory'])
            ->orderBy('vendor_id')
            ->orderBy('id')
            ->paginate(20);
        $vendors = Vendor::when(
            \Illuminate\Support\Facades\Schema::hasColumn('mess_vendors', 'is_active'),
            fn ($q) => $q->where('is_active', true)
        )->orderBy('name')->get();
        $itemSubcategories = ItemSubcategory::active()->orderBy('id')->get();

        return view('admin.mess.vendor-item-mappings.index', compact(
            'mappings', 'vendors', 'itemSubcategories'
        ));
=======
        return $dataTable->render('admin.mess.vendor-item-mappings.index');
>>>>>>> 051cf8b3 (vendor-mapping)
    }

    public function create(Request $request)
    {
        $vendors = Vendor::when(
            \Illuminate\Support\Facades\Schema::hasColumn('mess_vendors', 'is_active'),
            fn ($q) => $q->where('is_active', true)
        )->orderBy('name')->get();
        $itemSubcategories = ItemSubcategory::active()->orderBy('id')->get();

        if ($request->ajax()) {
            return view('admin.mess.vendor-item-mappings._form', compact(
                'vendors', 'itemSubcategories'
            ));
        }

        return view('admin.mess.vendor-item-mappings.create', compact(
            'vendors', 'itemSubcategories'
        ));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'vendor_id' => 'required|exists:mess_vendors,id',
                'item_subcategory_ids' => 'required|array',
                'item_subcategory_ids.*' => 'exists:mess_item_subcategories,id',
            ], [
                'vendor_id.required' => 'Please select a vendor.',
                'item_subcategory_ids.required' => 'Please select at least one Item.',
            ]);
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                $request->flash();
                $vendors = Vendor::when(
                    \Illuminate\Support\Facades\Schema::hasColumn('mess_vendors', 'is_active'),
                    fn ($q) => $q->where('is_active', true)
                )->orderBy('name')->get();
                $itemSubcategories = ItemSubcategory::active()->orderBy('id')->get();
                return response()->view('admin.mess.vendor-item-mappings._form', compact(
                    'vendors', 'itemSubcategories'
                ), 422)->withErrors($e->errors());
            }
            throw $e;
        }

        $vendorId = (int) $request->vendor_id;
        $ids = array_filter((array) $request->item_subcategory_ids);
        foreach ($ids as $subcategoryId) {
            VendorItemMapping::create([
                'vendor_id' => $vendorId,
                'mapping_type' => VendorItemMapping::MAPPING_TYPE_ITEM_SUB_CATEGORY,
                'item_category_id' => null,
                'item_subcategory_id' => (int) $subcategoryId,
            ]);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'reload' => true, 'message' => 'Vendor mapping(s) created successfully.']);
        }

        return redirect()->route('admin.mess.vendor-item-mappings.index')
            ->with('success', 'Vendor mapping(s) created successfully.');
    }

    public function show($id)
    {
        $mapping = VendorItemMapping::with(['vendor', 'itemCategory', 'itemSubcategory'])->findOrFail($id);
        return view('admin.mess.vendor-item-mappings.show', compact('mapping'));
    }

    public function edit(Request $request, $id)
    {
        $mapping = VendorItemMapping::with(['vendor', 'itemCategory', 'itemSubcategory'])->findOrFail($id);
        $vendors = Vendor::when(
            \Illuminate\Support\Facades\Schema::hasColumn('mess_vendors', 'is_active'),
            fn ($q) => $q->where('is_active', true)
        )->orderBy('name')->get();
        $itemSubcategories = ItemSubcategory::active()->orderBy('id')->get();

        if ($request->ajax()) {
            return view('admin.mess.vendor-item-mappings._form', compact(
                'mapping', 'vendors', 'itemSubcategories'
            ));
        }

        return view('admin.mess.vendor-item-mappings.edit', compact(
            'mapping', 'vendors', 'itemSubcategories'
        ));
    }

    public function update(Request $request, $id)
    {
        $mapping = VendorItemMapping::findOrFail($id);

        try {
            $request->validate([
                'vendor_id' => 'required|exists:mess_vendors,id',
                'item_subcategory_ids' => 'required|array',
                'item_subcategory_ids.*' => 'exists:mess_item_subcategories,id',
            ], [
                'vendor_id.required' => 'Please select a vendor.',
                'item_subcategory_ids.required' => 'Please select at least one Item.',
            ]);
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                $request->flash();
                $vendors = Vendor::when(
                    \Illuminate\Support\Facades\Schema::hasColumn('mess_vendors', 'is_active'),
                    fn ($q) => $q->where('is_active', true)
                )->orderBy('name')->get();
                $itemSubcategories = ItemSubcategory::active()->orderBy('id')->get();
                return response()->view('admin.mess.vendor-item-mappings._form', compact(
                    'mapping', 'vendors', 'itemSubcategories'
                ), 422)->withErrors($e->errors());
            }
            throw $e;
        }

        $vendorId = (int) $request->vendor_id;
        $mapping->delete();

        $ids = array_filter((array) $request->item_subcategory_ids);
        foreach ($ids as $subcategoryId) {
            VendorItemMapping::create([
                'vendor_id' => $vendorId,
                'mapping_type' => VendorItemMapping::MAPPING_TYPE_ITEM_SUB_CATEGORY,
                'item_category_id' => null,
                'item_subcategory_id' => (int) $subcategoryId,
            ]);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'reload' => true, 'message' => 'Vendor mapping updated successfully.']);
        }

        return redirect()->route('admin.mess.vendor-item-mappings.index')
            ->with('success', 'Vendor mapping updated successfully.');
    }

    public function destroy($id)
    {
        $mapping = VendorItemMapping::findOrFail($id);
        $mapping->delete();

        return redirect()->route('admin.mess.vendor-item-mappings.index')
            ->with('success', 'Vendor mapping deleted successfully.');
    }
}
