<?php

namespace Modules\Protocol\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Modules\Protocol\Entities\VehicleType;

class VehicleTypesController extends Controller
{

    public function index(Request $request)
    {
        $types = VehicleType::all();
        return view('protocol::admin.vehicles.types',compact('types'));
    }

    public function store(Request $request){
        try {
            $request->validate([
                'name' => 'required|string|max:50|unique:vehicle_types,name',
            ]);

            $data = VehicleType::create([
                'name' => $request->name,
            ]);

            if ($data) {
                return redirect()->back()->with('success', 'Vehicle Type created successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to create Vehicle Type');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $id){
        try {
            $request->validate([
                'name' => 'required|string|max:50|unique:vehicle_types,name,'.$id,
            ]);

            $data = VehicleType::where('id', $id)->update([
                'name' => $request->name,
            ]);

            if ($data) {
                return redirect()->back()->with('success', 'Vehicle Type updated successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to update Vehicle Type');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request, $id){
        try {
            $data = VehicleType::where('id', $id)->delete();

            if ($data) {
                return redirect()->back()->with('success', 'Vehicle Type deleted successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to delete Vehicle Type');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}