<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\ExaminationTermMasterDataTable;
use App\Http\Controllers\Admin\COE\Concerns\ExportsCoeMaster;
use App\Http\Controllers\Admin\COE\Concerns\RendersSimpleMaster;
use App\Http\Controllers\Controller;
use App\Models\ExaminationDrive;
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
    use ExportsCoeMaster;

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
            // Active / Inactive filter and the Action column pinned to the right.
            'statusFilter' => true,
            'pinActions' => true,
            'fields' => [
                $this->nameField('term_name', 'Examination Term', 'eg. Mid term'),
            ],
            'endpoints' => [
                'list' => route('admin.coe.examination-term.index'),
                'export' => route('admin.coe.examination-term.export', ['format' => '__FORMAT__']),
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

    /** Only an inactive term that no examination drive uses can be deleted. */
    public function destroy(string $id): JsonResponse
    {
        $model = TermMaster::findOrFail(decrypt($id));

        if ((int) $model->active_inactive === 1) {
            return response()->json(['status' => 'error', 'message' => 'An active examination term cannot be deleted. Deactivate it first.'], 422);
        }
        $drives = ExaminationDrive::where('term_master_pk', $model->pk)->count();
        if ($drives > 0) {
            return response()->json(['status' => 'error',
                'message' => "This examination term is used by {$drives} examination drive(s) and cannot be deleted."], 422);
        }

        $model->delete();

        return response()->json(['status' => 'success', 'message' => 'Examination term deleted successfully.']);
    }

    /** CSV / Excel / PDF / Print of the grid as filtered on screen (ExportsCoeMaster). */
    public function export(Request $request, string $format)
    {
        return $this->exportCoeMaster(
            $request,
            $format,
            TermMaster::query()->orderBy('term_name'),
            [
                'sno' => $this->coeSnoColumn(),
                'name' => ['heading' => 'Examination Term', 'width' => '68%', 'align' => 'left', 'value' => fn ($row) => $row->term_name],
                'status' => $this->coeStatusColumn(),
            ],
            fn ($query, string $term) => $query->where('term_name', 'like', '%' . $term . '%'),
            'Examination Term Master',
            'ExaminationTermMaster'
        );
    }
}
