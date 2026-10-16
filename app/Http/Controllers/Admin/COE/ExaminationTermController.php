<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\ExaminationTermMasterDataTable;
use App\Http\Controllers\Admin\COE\Concerns\RendersSimpleMaster;
use App\Http\Controllers\Controller;
use App\Models\TermMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * COE → Master Data → Examination Term Master.
 *
 * Backed by the existing `term_master` table: `term_name` required|max:100|unique,
 * `active_inactive` 0/1, custom PK column `pk`.
 * The grid itself is server-side (Yajra); create/edit/toggle/delete stay the
 * same AJAX endpoints as before.
 */
class ExaminationTermController extends Controller
{
    use RendersSimpleMaster;

    public function index(ExaminationTermMasterDataTable $dataTable)
    {
        $master = [
            'title' => 'Examination Term Master',
            'entity' => 'Examination Term',
            'prefix' => 'etr',
            'colvisKey' => 'coeExaminationTerm:hiddenColumns:v1',
            'labels' => [
                'createButton' => 'Create Examination Term',
                'createTitle' => 'Create Examination Term',
                'editTitle' => 'Edit Examination Term',
                'createSubmit' => 'Create',
                'editSubmit' => 'Update',
            ],
            'sno' => ['show' => true, 'label' => 'S. No.'],
            'filters' => [],
            'fields' => [
                $this->nameField('term_name', 'Examination Term', 'eg. Mid term'),
            ],
            'endpoints' => [
                'list' => route('admin.coe.examination-term.index'),
                'store' => route('admin.coe.examination-term.store'),
                'destroy' => route('admin.coe.examination-term.destroy', ['id' => '__ID__']),
                'toggle' => route('admin.coe.examination-term.toggle-status', ['id' => '__ID__']),
            ],
        ];
        $master['formOrder'] = array_column($master['fields'], 'key');

        return $dataTable->render('admin.coe.examination_term.index', ['master' => $master]);
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->filled('pk') ? decrypt($request->input('pk')) : null;

        $validated = $request->validate([
            'term_name' => [
                'required', 'string', 'max:100',
                Rule::unique('term_master', 'term_name')->ignore($id, 'pk'),
            ],
            'active_inactive' => ['required', 'in:0,1'],
        ]);

        $model = $id ? TermMaster::findOrFail($id) : new TermMaster();
        $model->term_name = $validated['term_name'];
        $model->active_inactive = $validated['active_inactive'];
        if (! $id) {
            $model->created_date = now();
        }
        $model->modified_date = now();
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Examination term ' . ($id ? 'updated' : 'created') . ' successfully.',
            'row' => [
                'pk' => encrypt($model->pk),
                'values' => ['name' => $model->term_name],
                'active' => (bool) $model->active_inactive,
            ],
        ]);
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $model = TermMaster::findOrFail(decrypt($id));
        $model->active_inactive = $request->boolean('active') ? 1 : 0;
        $model->modified_date = now();
        $model->save();

        return response()->json(['status' => 'success']);
    }

    public function destroy(string $id): JsonResponse
    {
        TermMaster::where('pk', decrypt($id))->delete();

        return response()->json(['status' => 'success', 'message' => 'Examination term deleted successfully.']);
    }
}
