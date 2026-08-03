<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\SecVehicleType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class VehicleTypeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SecVehicleType::query()->orderBy('pk', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('description', function ($vt) {
                    return $vt->description ?? '--';
                })
                ->addColumn('actions', function ($vt) {
                    $editUrl = route('admin.security.vehicle_type.edit', encrypt($vt->pk));
                    $deleteUrl = route('admin.security.vehicle_type.delete', encrypt($vt->pk));
                    $token = csrf_token();

                    return '<div class="d-flex gap-2">'
                        . '<a href="' . e($editUrl) . '" class="text-success" title="Edit">'
                        . '<i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i></a>'
                        . '<form action="' . e($deleteUrl) . '" method="POST" onsubmit="return confirm(\'Delete this Vehicle Type?\')">'
                        . '<input type="hidden" name="_token" value="' . e($token) . '">'
                        . '<input type="hidden" name="_method" value="DELETE">'
                        . '<button type="submit" class="btn btn-link p-0 text-danger" title="Delete">'
                        . '<i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button>'
                        . '</form></div>';
                })
                ->addColumn('status', function ($vt) {
                    $toggleUrl = route('admin.security.vehicle_type.toggle.status', encrypt($vt->pk));
                    $checked = $vt->active_inactive == 1 ? 'checked' : '';

                    return '<div class="form-check form-switch d-inline-block">'
                        . '<input class="form-check-input status-toggle" type="checkbox" role="switch" data-url="' . e($toggleUrl) . '" ' . $checked . '>'
                        . '</div>';
                })
                ->rawColumns(['actions', 'status'])
                ->setRowAttr(['data-pk' => fn ($vt) => $vt->pk])
                ->make(true);
        }

        return view('admin.security.vehicle_type.index');
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
