<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\CoeFloorMasterDataTable;
use App\Http\Controllers\Admin\COE\Concerns\ExportsCoeMaster;
use App\Http\Controllers\Controller;
use App\Models\BuildingFloorRoomMapping;
use App\Models\FloorMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * COE → Master Data → Floor Master.
 *
 * The shared floor_master. Floors are universal (not per building), so every
 * floor is listed. `floor_name` required|max:50|unique. Total Rooms is blank
 * until floors are mapped for the COE. Status is stored as the table does:
 * 1 active, 2 inactive.
 */
class FloorController extends Controller
{
    use ExportsCoeMaster;

    public function index(CoeFloorMasterDataTable $dataTable)
    {
        $master = [
            'title' => 'Floor Master',
            'entity' => 'Floor',
            'prefix' => 'efm',
            'colvisKey' => 'coeFloor:hiddenColumns:v2',
            'labels' => [
                'createButton' => 'Add Floor',
                'createTitle' => 'Add Floor',
                'editTitle' => 'Edit Floor',
                'createSubmit' => 'Add Floor',
                'editSubmit' => 'Save',
            ],
            'sno' => ['show' => true, 'label' => 'S. No.'],
            'filters' => [],
            'statusFilter' => true,
            'pinActions' => true,
            'fields' => [
                ['key' => 'name', 'name' => 'floor_name', 'label' => 'Floor Name', 'column' => 'Floor Name',
                    'type' => 'text', 'placeholder' => 'eg. 1', 'required' => true, 'max' => 50],
                // Blank for now: not mapped yet.
                ['key' => 'rooms', 'name' => 'total_rooms', 'label' => 'Total Rooms', 'column' => 'Total Rooms',
                    'computed' => true, 'align' => 'center'],
            ],
            'endpoints' => [
                'list' => route('admin.coe.floor.index'),
                'export' => route('admin.coe.floor.export', ['format' => '__FORMAT__']),
                'store' => route('admin.coe.floor.store'),
                'destroy' => route('admin.coe.floor.destroy', ['id' => '__ID__']),
                'toggle' => route('admin.coe.floor.toggle-status', ['id' => '__ID__']),
            ],
        ];
        $master['formOrder'] = ['name'];

        return $dataTable->render('admin.coe.examination_term.index', ['master' => $master]);
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->filled('pk') ? decrypt($request->input('pk')) : null;

        $validated = $request->validate([
            'floor_name' => [
                'required', 'string', 'max:50',
                Rule::unique('floor_master', 'floor_name')->ignore($id, 'pk'),
            ],
            'active_inactive' => ['required', 'in:0,1'],
        ], [
            'floor_name.unique' => 'This floor already exists.',
        ], [
            'floor_name' => 'floor name',
        ]);

        $model = $id ? FloorMaster::findOrFail($id) : new FloorMaster();
        $model->floor_name = trim($validated['floor_name']);
        $model->active_inactive = $validated['active_inactive'] === '1' ? 1 : 2;
        if (! $id) {
            $model->created_date = now();
        }
        $model->modified_date = now();
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Floor ' . ($id ? 'updated' : 'created') . ' successfully.',
        ]);
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $model = FloorMaster::findOrFail(decrypt($id));
        $model->active_inactive = $request->boolean('active') ? 1 : 2;
        $model->modified_date = now();
        $model->save();

        return response()->json(['status' => 'success']);
    }

    /** Only an inactive floor no room (of any building) is on can be deleted. */
    public function destroy(string $id): JsonResponse
    {
        $model = FloorMaster::findOrFail(decrypt($id));

        if ((int) $model->active_inactive === 1) {
            return response()->json(['status' => 'error', 'message' => 'An active floor cannot be deleted. Deactivate it first.'], 422);
        }
        $rooms = BuildingFloorRoomMapping::where('floor_master_pk', $model->pk)->count();
        if ($rooms > 0) {
            return response()->json(['status' => 'error',
                'message' => "{$rooms} room(s) are on this floor, so it cannot be deleted."], 422);
        }

        $model->delete();

        return response()->json(['status' => 'success', 'message' => 'Floor deleted successfully.']);
    }

    /** CSV / Excel / PDF / Print of the grid as filtered on screen (ExportsCoeMaster). */
    public function export(Request $request, string $format)
    {
        return $this->exportCoeMaster(
            $request,
            $format,
            FloorMaster::query()->orderBy('pk'),
            [
                'sno' => $this->coeSnoColumn(),
                'name' => ['heading' => 'Floor Name', 'width' => '40%', 'align' => 'left', 'value' => fn ($row) => trim((string) $row->floor_name)],
                'rooms' => ['heading' => 'Total Rooms', 'width' => '20%', 'align' => 'center', 'value' => fn () => ''],
                'status' => $this->coeStatusColumn(),
            ],
            fn ($query, string $term) => $query->where('floor_name', 'like', '%' . $term . '%'),
            'Floor Master',
            'FloorMaster'
        );
    }
}
