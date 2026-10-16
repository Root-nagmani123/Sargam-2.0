<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\ComponentMasterDataTable;
use App\Http\Controllers\Admin\COE\Concerns\RendersSimpleMaster;
use App\Http\Controllers\Controller;
use App\Models\ComponentMaster;
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
            'fields' => [
                $this->nameField('component_name', 'Component', 'eg. Discipline'),
            ],
            'endpoints' => [
                'list' => route('admin.coe.component-master.index'),
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

    public function destroy(string $id): JsonResponse
    {
        ComponentMaster::where('pk', decrypt($id))->delete();

        return response()->json(['status' => 'success', 'message' => 'Component deleted successfully.']);
    }
}
