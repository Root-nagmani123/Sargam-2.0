<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\SecVehiclePassConfig;
use App\Models\SecVehicleType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehiclePassConfigController extends Controller
{
    public function index()
    {
        $configs = SecVehiclePassConfig::with('vehicleType')->orderBy('pk', 'desc')->paginate(10);
        return view('admin.security.vehicle_pass_config.index', compact('configs'));
    }

    public function create()
    {
        $vehicleTypes = SecVehicleType::active()->get();
        return view('admin.security.vehicle_pass_config.create', compact('vehicleTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sec_vehicle_type_pk' => ['required', 'exists:sec_vehicle_type,pk', Rule::unique('sec_vehcl_pass_config', 'sec_vehicle_type_pk')],
            'charges' => ['required', 'numeric', 'min:0'],
            'start_counter' => ['required', 'integer', 'min:1'],
        ]);

        $config = new SecVehiclePassConfig();
        $config->sec_vehicle_type_pk = $validated['sec_vehicle_type_pk'];
        $config->charges = $validated['charges'];
        $config->start_counter = $validated['start_counter'];
        $config->active_inactive = 1;
        $config->created_date = now();
        $config->save();

        return redirect()->route('admin.security.vehicle_pass_config.index')->with('success', 'Vehicle Pass Configuration created successfully');
    }

    public function edit($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $config = SecVehiclePassConfig::findOrFail($pk);
        $vehicleTypes = SecVehicleType::active()->get();
        return view('admin.security.vehicle_pass_config.edit', compact('config', 'vehicleTypes'));
    }

    public function update(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $config = SecVehiclePassConfig::findOrFail($pk);

        $validated = $request->validate([
            'sec_vehicle_type_pk' => ['required', 'exists:sec_vehicle_type,pk', Rule::unique('sec_vehcl_pass_config', 'sec_vehicle_type_pk')->ignore($config->pk, 'pk')],
            'charges' => ['required', 'numeric', 'min:0'],
            'start_counter' => ['required', 'integer', 'min:1'],
        ]);

        $config->sec_vehicle_type_pk = $validated['sec_vehicle_type_pk'];
        $config->charges = $validated['charges'];
        $config->start_counter = $validated['start_counter'];
        $config->modified_date = now();
        $config->save();

        return redirect()->route('admin.security.vehicle_pass_config.index')->with('success', 'Vehicle Pass Configuration updated successfully');
    }

    public function delete($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $config = SecVehiclePassConfig::findOrFail($pk);
        $config->delete();

        return redirect()->route('admin.security.vehicle_pass_config.index')->with('success', 'Vehicle Pass Configuration deleted successfully');
    }

    public function toggleStatus($id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid ID'], 404);
        }

        $config = SecVehiclePassConfig::findOrFail($pk);
        $config->active_inactive = request('status', 0);
        $config->modified_date = now();
        $config->save();

        return response()->json([
            'message' => 'Status updated successfully',
            'status' => $config->active_inactive
        ]);
    }
}
