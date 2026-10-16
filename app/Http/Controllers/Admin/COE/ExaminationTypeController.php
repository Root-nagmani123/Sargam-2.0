<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\ExaminationTypeMasterDataTable;
use App\Http\Controllers\Admin\COE\Concerns\RendersSimpleMaster;
use App\Http\Controllers\Controller;
use App\Models\ExaminationTypeMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * COE → Master Data → Examination Type Master.
 *
 * Backed by the existing `examination_type_master` table: `exam_type_name`
 * required|max:100|unique, `active_inactive` 0/1, custom PK column `pk`.
 * The grid itself is server-side (Yajra); create/edit/toggle/delete stay the
 * same AJAX endpoints as before.
 */
class ExaminationTypeController extends Controller
{
    use RendersSimpleMaster;

    public function index(ExaminationTypeMasterDataTable $dataTable)
    {
        $master = [
            'title' => 'Examination Type Master',
            'entity' => 'Examination Type',
            'prefix' => 'ext',
            'colvisKey' => 'coeExaminationType:hiddenColumns:v1',
            'labels' => [
                'createButton' => 'Create Examination Type',
                'createTitle' => 'Create Examination Type',
                'editTitle' => 'Edit Examination Type',
                'createSubmit' => 'Create',
                'editSubmit' => 'Update',
            ],
            'sno' => ['show' => true, 'label' => 'S. No.'],
            'filters' => [],
            'fields' => [
                $this->nameField('exam_type_name', 'Examination Type', 'eg. Written Test'),
            ],
            'endpoints' => [
                'list' => route('admin.coe.examination-type.index'),
                'store' => route('admin.coe.examination-type.store'),
                'destroy' => route('admin.coe.examination-type.destroy', ['id' => '__ID__']),
                'toggle' => route('admin.coe.examination-type.toggle-status', ['id' => '__ID__']),
            ],
        ];
        $master['formOrder'] = array_column($master['fields'], 'key');

        return $dataTable->render('admin.coe.examination_type.index', ['master' => $master]);
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->filled('pk') ? decrypt($request->input('pk')) : null;

        $validated = $request->validate([
            'exam_type_name' => [
                'required', 'string', 'max:100',
                Rule::unique('examination_type_master', 'exam_type_name')->ignore($id, 'pk'),
            ],
            'active_inactive' => ['required', 'in:0,1'],
        ]);

        $model = $id ? ExaminationTypeMaster::findOrFail($id) : new ExaminationTypeMaster();
        $model->exam_type_name = $validated['exam_type_name'];
        $model->active_inactive = $validated['active_inactive'];
        if (! $id) {
            $model->created_date = now();
        }
        $model->modified_date = now();
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Examination type ' . ($id ? 'updated' : 'created') . ' successfully.',
            'row' => [
                'pk' => encrypt($model->pk),
                'values' => ['name' => $model->exam_type_name],
                'active' => (bool) $model->active_inactive,
            ],
        ]);
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $model = ExaminationTypeMaster::findOrFail(decrypt($id));
        $model->active_inactive = $request->boolean('active') ? 1 : 0;
        $model->modified_date = now();
        $model->save();

        return response()->json(['status' => 'success']);
    }

    public function destroy(string $id): JsonResponse
    {
        ExaminationTypeMaster::where('pk', decrypt($id))->delete();

        return response()->json(['status' => 'success', 'message' => 'Examination type deleted successfully.']);
    }
}
