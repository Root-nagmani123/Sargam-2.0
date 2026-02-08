<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Mess\VendorItemMapping;
use App\Models\Mess\Vendor;
use App\Models\Mess\ItemCategory;
use App\Models\Mess\ItemSubcategory;

class VendorItemMappingController extends Controller
{
    public function index()
    {
        $mappings = VendorItemMapping::with(['vendor', 'itemCategory', 'itemSubcategory'])
            ->orderBy('vendor_id')
            ->orderBy('id')
            ->paginate(20);
        $vendors = Vendor::when(
            \Illuminate\Support\Facades\Schema::hasColumn('mess_vendors', 'is_active'),
            fn ($q) => $q->where('is_active', true)
        )->orderBy('name')->get();
        $itemCategories = ItemCategory::active()->orderBy('category_name')->get();
        $itemSubcategories = ItemSubcategory::active()->orderBy('id')->get();

        return view('admin.mess.vendor-item-mappings.index', compact(
            'mappings', 'vendors', 'itemCategories', 'itemSubcategories'
        ));
    }

    public function create(Request $request)
    {
        $vendors = Vendor::when(
            \Illuminate\Support\Facades\Schema::hasColumn('mess_vendors', 'is_active'),
            fn ($q) => $q->where('is_active', true)
        )->orderBy('name')->get();
        $itemCategories = ItemCategory::active()->orderBy('category_name')->get();
        $itemSubcategories = ItemSubcategory::active()->orderBy('id')->get();

        if ($request->ajax()) {
            return view('admin.mess.vendor-item-mappings._form', compact(
                'vendors', 'itemCategories', 'itemSubcategories'
            ));
        }

        return view('admin.mess.vendor-item-mappings.create', compact(
            'vendors', 'itemCategories', 'itemSubcategories'
        ));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'vendor_id' => 'required|exists:mess_vendors,id',
                'mapping_type' => 'required|in:item_category,item_sub_category',
                'item_category_ids' => 'required_if:mapping_type,item_category|array',
                'item_category_ids.*' => 'exists:mess_item_categories,id',
                'item_subcategory_ids' => 'required_if:mapping_type,item_sub_category|array',
                'item_subcategory_ids.*' => 'exists:mess_item_subcategories,id',
            ], [
                'vendor_id.required' => 'Please select a vendor.',
                'mapping_type.required' => 'Please select a mapping type.',
                'item_category_ids.required_if' => 'Please select at least one Item Category.',
                'item_subcategory_ids.required_if' => 'Please select at least one Item Sub Category.',
            ]);
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                $request->flash();
                $vendors = Vendor::when(
                    \Illuminate\Support\Facades\Schema::hasColumn('mess_vendors', 'is_active'),
                    fn ($q) => $q->where('is_active', true)
                )->orderBy('name')->get();
                $itemCategories = ItemCategory::active()->orderBy('category_name')->get();
                $itemSubcategories = ItemSubcategory::active()->orderBy('id')->get();
                return response()->view('admin.mess.vendor-item-mappings._form', compact(
                    'vendors', 'itemCategories', 'itemSubcategories'
                ), 422)->withErrors($e->errors());
            }
            throw $e;
        }

        $vendorId = (int) $request->vendor_id;
        $mappingType = $request->mapping_type;

        if ($mappingType === VendorItemMapping::MAPPING_TYPE_ITEM_CATEGORY) {
            $ids = array_filter((array) $request->item_category_ids);
            foreach ($ids as $categoryId) {
                VendorItemMapping::create([
                    'vendor_id' => $vendorId,
                    'mapping_type' => $mappingType,
                    'item_category_id' => (int) $categoryId,
                    'item_subcategory_id' => null,
                ]);
            }
        } else {
            $ids = array_filter((array) $request->item_subcategory_ids);
            foreach ($ids as $subcategoryId) {
                VendorItemMapping::create([
                    'vendor_id' => $vendorId,
                    'mapping_type' => $mappingType,
                    'item_category_id' => null,
                    'item_subcategory_id' => (int) $subcategoryId,
                ]);
            }
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
        $itemCategories = ItemCategory::active()->orderBy('category_name')->get();
        $itemSubcategories = ItemSubcategory::active()->orderBy('id')->get();

        if ($request->ajax()) {
            return view('admin.mess.vendor-item-mappings._form', compact(
                'mapping', 'vendors', 'itemCategories', 'itemSubcategories'
            ));
        }

        return view('admin.mess.vendor-item-mappings.edit', compact(
            'mapping', 'vendors', 'itemCategories', 'itemSubcategories'
        ));
    }

    public function update(Request $request, $id)
    {
        $mapping = VendorItemMapping::findOrFail($id);

        try {
            $request->validate([
                'vendor_id' => 'required|exists:mess_vendors,id',
                'mapping_type' => 'required|in:item_category,item_sub_category',
                'item_category_ids' => 'required_if:mapping_type,item_category|array',
                'item_category_ids.*' => 'exists:mess_item_categories,id',
                'item_subcategory_ids' => 'required_if:mapping_type,item_sub_category|array',
                'item_subcategory_ids.*' => 'exists:mess_item_subcategories,id',
            ], [
                'vendor_id.required' => 'Please select a vendor.',
                'mapping_type.required' => 'Please select a mapping type.',
                'item_category_ids.required_if' => 'Please select at least one Item Category.',
                'item_subcategory_ids.required_if' => 'Please select at least one Item Sub Category.',
            ]);
        } catch (ValidationException $e) {
            if ($request->ajax()) {
                $request->flash();
                $vendors = Vendor::when(
                    \Illuminate\Support\Facades\Schema::hasColumn('mess_vendors', 'is_active'),
                    fn ($q) => $q->where('is_active', true)
                )->orderBy('name')->get();
                $itemCategories = ItemCategory::active()->orderBy('category_name')->get();
                $itemSubcategories = ItemSubcategory::active()->orderBy('id')->get();
                return response()->view('admin.mess.vendor-item-mappings._form', compact(
                    'mapping', 'vendors', 'itemCategories', 'itemSubcategories'
                ), 422)->withErrors($e->errors());
            }
            throw $e;
        }

        $vendorId = (int) $request->vendor_id;
        $mappingType = $request->mapping_type;

        $mapping->delete();

        if ($mappingType === VendorItemMapping::MAPPING_TYPE_ITEM_CATEGORY) {
            $ids = array_filter((array) $request->item_category_ids);
            foreach ($ids as $categoryId) {
                VendorItemMapping::create([
                    'vendor_id' => $vendorId,
                    'mapping_type' => $mappingType,
                    'item_category_id' => (int) $categoryId,
                    'item_subcategory_id' => null,
                ]);
            }
        } else {
            $ids = array_filter((array) $request->item_subcategory_ids);
            foreach ($ids as $subcategoryId) {
                VendorItemMapping::create([
                    'vendor_id' => $vendorId,
                    'mapping_type' => $mappingType,
                    'item_category_id' => null,
                    'item_subcategory_id' => (int) $subcategoryId,
                ]);
            }
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
