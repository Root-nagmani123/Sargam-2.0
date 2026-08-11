<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\SecVehiclePassConfig;
use App\Models\SecVehicleType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class VehiclePassConfigController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SecVehiclePassConfig::with('vehicleType')->orderBy('pk', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('vehicle_type_name', function ($config) {
                    return $config->vehicleType ? e($config->vehicleType->vehicle_type) : '<span class="text-muted">--</span>';
                })
                ->addColumn('charges', function ($config) {
                    return number_format($config->charges, 2);
                })
                ->addColumn('preview', function ($config) {
                    return '<span class="badge bg-info text-dark">VP' . now()->format('Ymd') . str_pad($config->start_counter, 4, '0', STR_PAD_LEFT) . '</span>';
                })
                ->addColumn('actions', function ($config) {
                    $editUrl = route('admin.security.vehicle_pass_config.edit', encrypt($config->pk));
                    $deleteUrl = route('admin.security.vehicle_pass_config.delete', encrypt($config->pk));
                    $token = csrf_token();

                    return '<div class="d-flex gap-2">'
                        . '<a href="' . e($editUrl) . '" class="text-success" title="Edit">'
                        . '<i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i></a>'
                        . '<form action="' . e($deleteUrl) . '" method="POST" onsubmit="return confirm(\'Delete this configuration?\')">'
                        . '<input type="hidden" name="_token" value="' . e($token) . '">'
                        . '<input type="hidden" name="_method" value="DELETE">'
                        . '<button type="submit" class="btn btn-link p-0 text-danger" title="Delete">'
                        . '<i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button>'
                        . '</form></div>';
                })
                ->addColumn('status', function ($config) {
                    $toggleUrl = route('admin.security.vehicle_pass_config.toggle.status', encrypt($config->pk));
                    $checked = $config->active_inactive == 1 ? 'checked' : '';

                    return '<div class="form-check form-switch d-inline-block">'
                        . '<input class="form-check-input status-toggle" type="checkbox" role="switch" data-url="' . e($toggleUrl) . '" ' . $checked . '>'
                        . '</div>';
                })
                ->rawColumns(['vehicle_type_name', 'preview', 'actions', 'status'])
                ->setRowAttr(['data-pk' => fn ($config) => $config->pk])
                ->make(true);
        }

        return view('admin.security.vehicle_pass_config.index');
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
