<?php

namespace Modules\Protocol\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Modules\Protocol\Entities\VehicleCategory;

class VehicleCategoryController extends Controller
{

    public function index(Request $request)
    {
        $categories = VehicleCategory::all();
        return view('protocol::admin.vehicles.category',compact('categories'));
    }

    public function store(Request $request){
        try {
            $request->validate([
                'name' => 'required|string|max:50|unique:vehicle_categories,name',
            ]);

            $data = VehicleCategory::create([
                'name' => $request->name,
            ]);

            if ($data) {
                return redirect()->back()->with('success', 'Vehicle Category created successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to create Vehicle Category');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $id){
        try {
            $request->validate([
                'name' => 'required|string|max:50|unique:vehicle_categories,name,'.$id,
            ]);

            $data = VehicleCategory::where('id', $id)->update([
                'name' => $request->name,
            ]);

            if ($data) {
                return redirect()->back()->with('success', 'Vehicle Category updated successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to update Vehicle Category');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request, $id){
        try {
            $data = VehicleCategory::where('id', $id)->delete();

            if ($data) {
                return redirect()->back()->with('success', 'Vehicle Category deleted successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to delete Vehicle Category');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}