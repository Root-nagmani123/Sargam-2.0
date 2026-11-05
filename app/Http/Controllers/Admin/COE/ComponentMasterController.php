<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\ComponentMasterDataTable;
use App\Http\Controllers\Admin\COE\Concerns\ExportsCoeMaster;
use App\Http\Controllers\Admin\COE\Concerns\RendersSimpleMaster;
use App\Http\Controllers\Controller;
use App\Models\ComponentMaster;
use App\Models\ExaminationDriveComponentMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * COE → Master Data → Component Master.
 *
 * Backed by the existing `component_master` table: `component_name` required|max:100|unique,
 * `active_inactive` 0/1, custom PK column `pk`. Mirrors ExaminationTermController exactly.
 */
class ComponentMasterController extends Controller
{
    use RendersSimpleMaster;
    use ExportsCoeMaster;

    public function index(ComponentMasterDataTable $dataTable)
    {
        $master = [
            'title' => 'Component Master',
            'entity' => 'Component',
            'prefix' => 'cmp',
            'colvisKey' => 'coeComponentMaster:hiddenColumns:v1',
            'labels' => [
                'createButton' => 'Create Component',
                'createTitle' => 'Create Component',
                'editTitle' => 'Edit Component',
                'createSubmit' => 'Create',
                'editSubmit' => 'Update',
            ],
            'sno' => ['show' => true, 'label' => 'S. No.'],
            'filters' => [],
            // Active / Inactive filter and the Action column pinned to the right.
            'statusFilter' => true,
            'pinActions' => true,
            'fields' => [
                $this->nameField('component_name', 'Component', 'eg. Discipline'),
            ],
            'endpoints' => [
                'list' => route('admin.coe.component-master.index'),
                'export' => route('admin.coe.component-master.export', ['format' => '__FORMAT__']),
                'store' => route('admin.coe.component-master.store'),
                'destroy' => route('admin.coe.component-master.destroy', ['id' => '__ID__']),
                'toggle' => route('admin.coe.component-master.toggle-status', ['id' => '__ID__']),
            ],
        ];
        $master['formOrder'] = array_column($master['fields'], 'key');

        return $dataTable->render('admin.coe.examination_term.index', ['master' => $master]);
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->filled('pk') ? decrypt($request->input('pk')) : null;

        $validated = $request->validate([
            'component_name' => [
                'required', 'string', 'max:100',
                Rule::unique('component_master', 'component_name')->ignore($id, 'pk'),
            ],
            'active_inactive' => ['required', 'in:0,1'],
        ]);

        $model = $id ? ComponentMaster::findOrFail($id) : new ComponentMaster();
        $model->component_name = $validated['component_name'];
        $model->active_inactive = $validated['active_inactive'];
        if (! $id) {
            $model->created_date = now();
        }
        $model->modified_date = now();
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Component ' . ($id ? 'updated' : 'created') . ' successfully.',
            'row' => [
                'pk' => encrypt($model->pk),
                'values' => ['name' => $model->component_name],
                'active' => (bool) $model->active_inactive,
            ],
        ]);
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $model = ComponentMaster::findOrFail(decrypt($id));
        $model->active_inactive = $request->boolean('active') ? 1 : 0;
        $model->modified_date = now();
        $model->save();

        return response()->json(['status' => 'success']);
    }

    /** Only an inactive component that no examination drive uses can be deleted. */
    public function destroy(string $id): JsonResponse
    {
        $model = ComponentMaster::findOrFail(decrypt($id));

        if ((int) $model->active_inactive === 1) {
            return response()->json(['status' => 'error', 'message' => 'An active component cannot be deleted. Deactivate it first.'], 422);
        }
        $drives = ExaminationDriveComponentMap::where('component_master_pk', $model->pk)->distinct()->count('examination_drive_id');
        if ($drives > 0) {
            return response()->json(['status' => 'error',
                'message' => "This component is used by {$drives} examination drive(s) and cannot be deleted."], 422);
        }

        $model->delete();

        return response()->json(['status' => 'success', 'message' => 'Component deleted successfully.']);
    }

    /** CSV / Excel / PDF / Print of the grid as filtered on screen (ExportsCoeMaster). */
    public function export(Request $request, string $format)
    {
        return $this->exportCoeMaster(
            $request,
            $format,
            ComponentMaster::query()->orderBy('component_name'),
            [
                'sno' => $this->coeSnoColumn(),
                'name' => ['heading' => 'Component', 'width' => '68%', 'align' => 'left', 'value' => fn ($row) => $row->component_name],
                'status' => $this->coeStatusColumn(),
            ],
            fn ($query, string $term) => $query->where('component_name', 'like', '%' . $term . '%'),
            'Component Master',
            'ComponentMaster'
        );
    }
}
