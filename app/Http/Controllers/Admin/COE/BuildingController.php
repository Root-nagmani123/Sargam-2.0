<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\CoeBuildingMasterDataTable;
use App\Http\Controllers\Admin\COE\Concerns\ExportsCoeMaster;
use App\Http\Controllers\Controller;
use App\Models\BuildingFloorRoomMapping;
use App\Models\BuildingMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * COE → Master Data → Building Master (examination buildings).
 *
 * The shared building_master, building_type = Classroom only: the COE lists, adds
 * and edits only those, and every building it adds is a Classroom. `building_name`
 * required|max:255, unique across building_master; Total Floors / Total Rooms are
 * the building's no_of_floors / no_of_rooms. Status is stored as the table does:
 * 1 active, 2 inactive.
 */
class BuildingController extends Controller
{
    use ExportsCoeMaster;

    public function index(CoeBuildingMasterDataTable $dataTable)
    {
        $master = [
            'title' => 'Building Master',
            'entity' => 'Building',
            'prefix' => 'ebm',
            'colvisKey' => 'coeBuilding:hiddenColumns:v3',
            'labels' => [
                'createButton' => 'Add Building',
                'createTitle' => 'Add Building',
                'editTitle' => 'Edit Building',
                'createSubmit' => 'Add Building',
                'editSubmit' => 'Save',
            ],
            'sno' => ['show' => true, 'label' => 'S. No.'],
            'filters' => [],
            'statusFilter' => true,
            'pinActions' => true,
            'fields' => [
                ['key' => 'name', 'name' => 'building_name', 'label' => 'Building Name', 'column' => 'Building Name',
                    'type' => 'text', 'placeholder' => 'eg. Gyanshila', 'required' => true, 'max' => 255],
                ['key' => 'floors', 'name' => 'no_of_floors', 'label' => 'Total Floors', 'column' => 'Total Floors',
                    'type' => 'number', 'placeholder' => 'eg. 4', 'required' => true, 'maxValue' => 999, 'step' => 1, 'align' => 'center'],
                ['key' => 'rooms', 'name' => 'no_of_rooms', 'label' => 'Total Rooms', 'column' => 'Total Rooms',
                    'type' => 'number', 'placeholder' => 'eg. 10', 'required' => true, 'maxValue' => 9999, 'step' => 1, 'align' => 'center'],
            ],
            'endpoints' => [
                'list' => route('admin.coe.building.index'),
                'export' => route('admin.coe.building.export', ['format' => '__FORMAT__']),
                'store' => route('admin.coe.building.store'),
                'destroy' => route('admin.coe.building.destroy', ['id' => '__ID__']),
                'toggle' => route('admin.coe.building.toggle-status', ['id' => '__ID__']),
            ],
        ];
        $master['formOrder'] = ['name', 'floors', 'rooms'];

        return $dataTable->render('admin.coe.examination_term.index', ['master' => $master]);
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->filled('pk') ? decrypt($request->input('pk')) : null;

        $validated = $request->validate([
            'building_name' => [
                'required', 'string', 'max:255',
                Rule::unique('building_master', 'building_name')->ignore($id, 'pk'),
            ],
            'no_of_floors' => ['required', 'integer', 'min:0', 'max:999'],
            'no_of_rooms' => ['required', 'integer', 'min:0', 'max:9999'],
            'active_inactive' => ['required', 'in:0,1'],
        ], [
            'building_name.unique' => 'This building name already exists.',
        ], [
            'building_name' => 'building name',
            'no_of_floors' => 'total floors',
            'no_of_rooms' => 'total rooms',
        ]);

        // Only Classroom buildings are the COE's to edit.
        $model = $id ? BuildingMaster::classroom()->findOrFail($id) : new BuildingMaster(['building_type' => BuildingMaster::TYPE_CLASSROOM]);
        $model->building_name = trim($validated['building_name']);
        $model->no_of_floors = (int) $validated['no_of_floors'];
        $model->no_of_rooms = (int) $validated['no_of_rooms'];
        $model->active_inactive = $validated['active_inactive'] === '1' ? 1 : 2;
        if (! $id) {
            $model->created_date = now();
        }
        $model->updated_date = now();
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Building ' . ($id ? 'updated' : 'created') . ' successfully.',
        ]);
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $model = BuildingMaster::classroom()->findOrFail(decrypt($id));
        $model->active_inactive = $request->boolean('active') ? 1 : 2;
        $model->updated_date = now();
        $model->save();

        return response()->json(['status' => 'success']);
    }

    /** Only an inactive building with no rooms mapped to it can be deleted. */
    public function destroy(string $id): JsonResponse
    {
        $model = BuildingMaster::classroom()->findOrFail(decrypt($id));

        if ((int) $model->active_inactive === 1) {
            return response()->json(['status' => 'error', 'message' => 'An active building cannot be deleted. Deactivate it first.'], 422);
        }
        $rooms = BuildingFloorRoomMapping::where('building_master_pk', $model->pk)->count();
        if ($rooms > 0) {
            return response()->json(['status' => 'error',
                'message' => "{$rooms} room(s) are mapped to this building, so it cannot be deleted."], 422);
        }

        $model->delete();

        return response()->json(['status' => 'success', 'message' => 'Building deleted successfully.']);
    }

    /** CSV / Excel / PDF / Print of the grid as filtered on screen (ExportsCoeMaster). */
    public function export(Request $request, string $format)
    {
        return $this->exportCoeMaster(
            $request,
            $format,
            BuildingMaster::query()->classroom()->orderBy('building_name'),
            [
                'sno' => $this->coeSnoColumn(),
                'name' => ['heading' => 'Building Name', 'width' => '36%', 'align' => 'left', 'value' => fn ($row) => trim((string) $row->building_name)],
                'floors' => ['heading' => 'Total Floors', 'width' => '16%', 'align' => 'center', 'value' => fn ($row) => (string) ($row->no_of_floors ?? '')],
                'rooms' => ['heading' => 'Total Rooms', 'width' => '16%', 'align' => 'center', 'value' => fn ($row) => (string) ($row->no_of_rooms ?? '')],
                'status' => $this->coeStatusColumn(),
            ],
            fn ($query, string $term) => $query->where('building_name', 'like', '%' . $term . '%'),
            'Building Master',
            'BuildingMaster'
        );
    }
}
