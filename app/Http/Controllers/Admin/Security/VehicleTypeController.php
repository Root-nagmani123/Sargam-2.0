<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\SecVehicleType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleTypeController extends Controller
{
    public function index()
    {
        $vehicleTypes = SecVehicleType::orderBy('pk', 'desc')->paginate(10);
        return view('admin.security.vehicle_type.index', compact('vehicleTypes'));
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            return view('admin.security.vehicle_type._form');
        }
        return redirect()->route('admin.security.vehicle_type.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_type' => ['required', 'string', 'max:100', Rule::unique('sec_vehicle_type', 'vehicle_type')],
            'description' => ['nullable', 'string'],
        ]);

        $vehicleType = new SecVehicleType();
        $vehicleType->vehicle_type = $validated['vehicle_type'];
        $vehicleType->description = $validated['description'] ?? null;
        $vehicleType->active_inactive = 1;
        $vehicleType->created_date = now();
        $vehicleType->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'action' => 'create',
                'data' => [
                    'pk' => $vehicleType->pk,
                    'encrypted_pk' => encrypt($vehicleType->pk),
                    'vehicle_type' => $vehicleType->vehicle_type,
                    'description' => $vehicleType->description,
                    'active_inactive' => $vehicleType->active_inactive,
                ]
            ]);
        }

        return redirect()->route('admin.security.vehicle_type.index')->with('success', 'Vehicle Type created successfully');
    }

    public function edit(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $vehicleType = SecVehicleType::findOrFail($pk);

        if ($request->ajax()) {
            return view('admin.security.vehicle_type._form', compact('vehicleType'));
        }

        return redirect()->route('admin.security.vehicle_type.index');
    }

    public function update(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $vehicleType = SecVehicleType::findOrFail($pk);

        $validated = $request->validate([
            'vehicle_type' => ['required', 'string', 'max:100', Rule::unique('sec_vehicle_type', 'vehicle_type')->ignore($vehicleType->pk, 'pk')],
            'description' => ['nullable', 'string'],
        ]);

        $vehicleType->vehicle_type = $validated['vehicle_type'];
        $vehicleType->description = $validated['description'] ?? null;
        $vehicleType->modified_date = now();
        $vehicleType->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'action' => 'update',
                'data' => [
                    'pk' => $vehicleType->pk,
                    'encrypted_pk' => encrypt($vehicleType->pk),
                    'vehicle_type' => $vehicleType->vehicle_type,
                    'description' => $vehicleType->description,
                    'active_inactive' => $vehicleType->active_inactive,
                ]
            ]);
        }

        return redirect()->route('admin.security.vehicle_type.index')->with('success', 'Vehicle Type updated successfully');
    }

    public function delete(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $vehicleType = SecVehicleType::findOrFail($pk);
        $vehicleType->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'deleted' => true]);
        }

        return redirect()->route('admin.security.vehicle_type.index')->with('success', 'Vehicle Type deleted successfully');
    }

    public function toggleStatus(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $vehicleType = SecVehicleType::findOrFail($pk);
        $vehicleType->active_inactive = $vehicleType->active_inactive == 1 ? 0 : 1;
        $vehicleType->modified_date = now();
        $vehicleType->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'active_inactive' => $vehicleType->active_inactive
            ]);
        }

        return redirect()->route('admin.security.vehicle_type.index')->with('success', 'Status updated successfully');
    }
}
