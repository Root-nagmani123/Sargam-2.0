<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\CoeGradeMasterDataTable;
use App\DataTables\SubjectMaxMarksDataTable;
use App\Http\Controllers\Admin\COE\Concerns\ExportsCoeMaster;
use App\Http\Controllers\Controller;
use App\Models\COE\GradeMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * COE → Master Data → Grade Master.
 *
 * coe_grade_master: `grade` required|unique (10), `classification` (100),
 * `min_percent` / `max_percent` 0..100 with min ≤ max, `result_status` Pass /
 * Not Qualified, `active_inactive` 0/1. Active grades may not overlap (both ends
 * inclusive), so a percentage has at most one active grade. Same page and
 * endpoints shape as SubjectMaxMarksController.
 */
class GradeMasterController extends Controller
{
    use ExportsCoeMaster;

    public function index(CoeGradeMasterDataTable $dataTable)
    {
        $master = [
            'title' => 'Grade Master',
            'entity' => 'Grade',
            'prefix' => 'cgm',
            'colvisKey' => 'coeGradeMaster:hiddenColumns:v2',
            'labels' => [
                'createButton' => 'Add Grade',
                'createTitle' => 'Add Grade',
                'editTitle' => 'Edit Grade',
                'createSubmit' => 'Save',
                'editSubmit' => 'Update',
            ],
            'sno' => ['show' => true, 'label' => 'S. No.'],
            'filters' => [],
            'statusFilter' => true,
            'pinActions' => true,
            'fields' => [
                ['key' => 'grade', 'name' => 'grade', 'label' => 'Grade', 'column' => 'Grade',
                    'type' => 'text', 'placeholder' => 'eg. A+', 'required' => true, 'max' => 10],
                ['key' => 'classification', 'name' => 'classification', 'label' => 'Classification', 'column' => 'Classification',
                    'type' => 'text', 'placeholder' => 'eg. Outstanding', 'required' => true, 'max' => 100],
                ['key' => 'min', 'name' => 'min_percent', 'label' => 'Minimum %', 'column' => 'Minimum %',
                    'type' => 'number', 'placeholder' => 'eg. 85', 'required' => true, 'maxValue' => 100, 'align' => 'center'],
                ['key' => 'max', 'name' => 'max_percent', 'label' => 'Maximum %', 'column' => 'Maximum %',
                    'type' => 'number', 'placeholder' => 'eg. 100', 'required' => true, 'maxValue' => 100, 'align' => 'center'],
                ['key' => 'result', 'name' => 'result_status', 'label' => 'Result Status', 'column' => 'Result Status',
                    'type' => 'select', 'placeholder' => 'Select Result Status', 'required' => true,
                    'options' => array_combine(GradeMaster::RESULT_STATUSES, GradeMaster::RESULT_STATUSES)],
            ],
            'endpoints' => [
                'list' => route('admin.coe.grade-master.index'),
                'export' => route('admin.coe.grade-master.export', ['format' => '__FORMAT__']),
                'store' => route('admin.coe.grade-master.store'),
                'destroy' => route('admin.coe.grade-master.destroy', ['id' => '__ID__']),
                'toggle' => route('admin.coe.grade-master.toggle-status', ['id' => '__ID__']),
            ],
        ];
        $master['formOrder'] = array_column($master['fields'], 'key');

        return $dataTable->render('admin.coe.examination_term.index', ['master' => $master]);
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->filled('pk') ? decrypt($request->input('pk')) : null;
        $request->merge(['grade' => trim((string) $request->input('grade')), 'classification' => trim((string) $request->input('classification'))]);

        $validated = $request->validate([
            'grade' => ['required', 'string', 'max:10', Rule::unique('coe_grade_master', 'grade')->ignore($id, 'pk')],
            'classification' => ['required', 'string', 'max:100'],
            'min_percent' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'max_percent' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2', 'gte:min_percent'],
            'result_status' => ['required', Rule::in(GradeMaster::RESULT_STATUSES)],
            'active_inactive' => ['required', 'in:0,1'],
        ], [
            'grade.unique' => 'This grade already exists.',
            'min_percent.decimal' => 'Enter a number with at most 2 decimals.',
            'max_percent.decimal' => 'Enter a number with at most 2 decimals.',
            'max_percent.gte' => 'Maximum % cannot be less than minimum %.',
        ], [
            'min_percent' => 'minimum %',
            'max_percent' => 'maximum %',
            'result_status' => 'result status',
        ]);

        $model = $id ? GradeMaster::findOrFail($id) : new GradeMaster();
        if ((int) $validated['active_inactive'] === 1) {
            $this->refuseOverlap($validated['min_percent'], $validated['max_percent'], $id, 'min_percent');
        }

        $model->grade = $validated['grade'];
        $model->classification = $validated['classification'];
        $model->min_percent = $validated['min_percent'];
        $model->max_percent = $validated['max_percent'];
        $model->result_status = $validated['result_status'];
        $model->active_inactive = (int) $validated['active_inactive'];
        if (! $id) {
            $model->created_date = now();
        }
        $model->modified_date = now();
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Grade ' . ($id ? 'updated' : 'created') . ' successfully.',
        ]);
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $model = GradeMaster::findOrFail(decrypt($id));

        if ($request->boolean('active')) {
            $clash = GradeMaster::activeOverlapping($model->min_percent, $model->max_percent, $model->pk)->first();
            if ($clash) {
                return response()->json(['status' => 'error', 'message' => $this->overlapMessage($clash)], 422);
            }
        }

        $model->active_inactive = $request->boolean('active') ? 1 : 0;
        $model->modified_date = now();
        $model->save();

        return response()->json(['status' => 'success']);
    }

    /** Only an inactive grade can be deleted. */
    public function destroy(string $id): JsonResponse
    {
        $model = GradeMaster::findOrFail(decrypt($id));

        if ((int) $model->active_inactive === 1) {
            return response()->json(['status' => 'error', 'message' => 'An active grade cannot be deleted. Deactivate it first.'], 422);
        }

        $model->delete();

        return response()->json(['status' => 'success', 'message' => 'Grade deleted successfully.']);
    }

    /** CSV / Excel / PDF / Print of the grid as shown on screen (ExportsCoeMaster). */
    public function export(Request $request, string $format)
    {
        $percent = fn ($value) => SubjectMaxMarksDataTable::marks($value);

        return $this->exportCoeMaster(
            $request,
            $format,
            GradeMaster::query()->orderByDesc('max_percent'),
            [
                'sno' => $this->coeSnoColumn(),
                'grade' => ['heading' => 'Grade', 'width' => '12%', 'align' => 'left', 'value' => fn ($row) => $row->grade],
                'classification' => ['heading' => 'Classification', 'width' => '28%', 'align' => 'left', 'value' => fn ($row) => $row->classification],
                'min' => ['heading' => 'Minimum %', 'width' => '13%', 'align' => 'center', 'value' => fn ($row) => $percent($row->min_percent)],
                'max' => ['heading' => 'Maximum %', 'width' => '13%', 'align' => 'center', 'value' => fn ($row) => $percent($row->max_percent)],
                'result' => ['heading' => 'Result Status', 'width' => '16%', 'align' => 'left', 'value' => fn ($row) => $row->result_status],
                'status' => $this->coeStatusColumn(),
            ],
            fn ($query, string $term) => $query->where(fn ($q) => $q->where('grade', 'like', '%' . $term . '%')
                ->orWhere('classification', 'like', '%' . $term . '%')
                ->orWhere('result_status', 'like', '%' . $term . '%')),
            'Grade Master',
            'GradeMaster'
        );
    }

    /** @throws ValidationException when an active grade already covers part of min..max */
    private function refuseOverlap($min, $max, $exceptPk, string $field): void
    {
        $clash = GradeMaster::activeOverlapping($min, $max, $exceptPk)->first();
        if ($clash) {
            throw ValidationException::withMessages([$field => $this->overlapMessage($clash)]);
        }
    }

    private function overlapMessage(GradeMaster $clash): string
    {
        return 'This range overlaps active grade ' . $clash->grade . ' ('
            . SubjectMaxMarksDataTable::marks($clash->min_percent) . '–' . SubjectMaxMarksDataTable::marks($clash->max_percent) . '%).';
    }
}
