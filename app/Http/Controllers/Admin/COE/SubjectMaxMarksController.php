<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\SubjectMaxMarksDataTable;
use App\Http\Controllers\Admin\COE\Concerns\ExportsCoeMaster;
use App\Http\Controllers\Controller;
use App\Models\SubjectMaster;
use App\Models\SubjectMaxMarksMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * COE → Master Data → Subject Max Marks.
 *
 * One row per subject_master row: `subject_master_pk` required|unique,
 * `max_marks` 0..9999.99, `active_inactive` 0/1. Same page and endpoints
 * shape as ComponentMasterController.
 */
class SubjectMaxMarksController extends Controller
{
    use ExportsCoeMaster;

    public function index(SubjectMaxMarksDataTable $dataTable)
    {
        $master = [
            'title' => 'Subject Max Marks Master',
            'entity' => 'Subject Max Marks',
            'prefix' => 'smm',
            'colvisKey' => 'coeSubjectMaxMarks:hiddenColumns:v1',
            'labels' => [
                'createButton' => 'Add Subject Max Marks',
                'createTitle' => 'Add Subject Max Marks',
                'editTitle' => 'Edit Subject Max Marks',
                'createSubmit' => 'Save',
                'editSubmit' => 'Update',
            ],
            'sno' => ['show' => true, 'label' => 'S. No.'],
            'filters' => [],
            // Active / Inactive filter (exports follow it) and the Action column pinned to the right.
            'statusFilter' => true,
            'pinActions' => true,
            'fields' => [
                [
                    'key' => 'subject', 'name' => 'subject_master_pk', 'label' => 'Subject', 'column' => 'Subject',
                    'type' => 'select', 'placeholder' => 'Select Subject', 'required' => true,
                    'options' => $this->subjectOptions(),
                ],
                [
                    'key' => 'marks', 'name' => 'max_marks', 'label' => 'Max Marks', 'column' => 'Max Marks',
                    'type' => 'number', 'placeholder' => '100', 'required' => true, 'maxValue' => 9999.99,
                ],
            ],
            'endpoints' => [
                'list' => route('admin.coe.subject-max-marks.index'),
                'export' => route('admin.coe.subject-max-marks.export', ['format' => '__FORMAT__']),
                'store' => route('admin.coe.subject-max-marks.store'),
                'destroy' => route('admin.coe.subject-max-marks.destroy', ['id' => '__ID__']),
                'toggle' => route('admin.coe.subject-max-marks.toggle-status', ['id' => '__ID__']),
            ],
        ];
        $master['formOrder'] = array_column($master['fields'], 'key');

        return $dataTable->render('admin.coe.examination_term.index', ['master' => $master]);
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->filled('pk') ? decrypt($request->input('pk')) : null;

        $validated = $request->validate([
            'subject_master_pk' => [
                'required', 'integer', Rule::exists('subject_master', 'pk'),
                Rule::unique('subject_max_marks_master', 'subject_master_pk')->ignore($id, 'pk'),
            ],
            'max_marks' => ['required', 'numeric', 'min:0', 'max:9999.99', 'decimal:0,2'],
            'active_inactive' => ['required', 'in:0,1'],
        ], [
            'subject_master_pk.unique' => 'Max marks for this subject already exist.',
            'max_marks.decimal' => 'Enter a number with at most 2 decimals.',
        ], [
            'subject_master_pk' => 'subject',
            'max_marks' => 'max marks',
        ]);

        $model = $id ? SubjectMaxMarksMaster::findOrFail($id) : new SubjectMaxMarksMaster();
        $model->subject_master_pk = $validated['subject_master_pk'];
        $model->max_marks = $validated['max_marks'];
        $model->active_inactive = $validated['active_inactive'];
        if (! $id) {
            $model->created_date = now();
        }
        $model->modified_date = now();
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Subject max marks ' . ($id ? 'updated' : 'created') . ' successfully.',
        ]);
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $model = SubjectMaxMarksMaster::findOrFail(decrypt($id));
        $model->active_inactive = $request->boolean('active') ? 1 : 0;
        $model->modified_date = now();
        $model->save();

        return response()->json(['status' => 'success']);
    }

    /** Only an inactive entry can be deleted: faculty setup reads the active one as the subject's limit. */
    public function destroy(string $id): JsonResponse
    {
        $model = SubjectMaxMarksMaster::findOrFail(decrypt($id));

        if ((int) $model->active_inactive === 1) {
            return response()->json(['status' => 'error', 'message' => 'Active subject max marks cannot be deleted. Deactivate them first.'], 422);
        }

        $model->delete();

        return response()->json(['status' => 'success', 'message' => 'Subject max marks deleted successfully.']);
    }

    /**
     * Active subjects, plus any inactive one a row already uses so its Edit
     * dialog can still show it. [pk => name].
     */
    private function subjectOptions(): array
    {
        return SubjectMaster::query()
            ->where('active_inactive', 1)
            ->orWhereIn('pk', SubjectMaxMarksMaster::select('subject_master_pk'))
            ->orderBy('subject_name')
            ->pluck('subject_name', 'pk')
            ->map(fn ($name) => trim($name))
            ->all();
    }

    /** CSV / Excel / PDF / Print of the grid as shown on screen (ExportsCoeMaster). */
    public function export(Request $request, string $format)
    {
        return $this->exportCoeMaster(
            $request,
            $format,
            SubjectMaxMarksMaster::query()->with('subject')->orderByDesc('pk'),
            [
                'sno' => $this->coeSnoColumn(),
                'subject' => ['heading' => 'Subject', 'width' => '48%', 'align' => 'left',
                    'value' => fn ($row) => trim((string) ($row->subject->subject_name ?? ''))],
                'marks' => ['heading' => 'Max Marks', 'width' => '20%', 'align' => 'center',
                    'value' => fn ($row) => SubjectMaxMarksDataTable::marks($row->max_marks)],
                'status' => $this->coeStatusColumn(),
            ],
            fn ($query, string $term) => $query->whereHas('subject', fn ($q) => $q->where('subject_name', 'like', '%' . $term . '%')),
            'Subject Max Marks Master',
            'SubjectMaxMarks'
        );
    }
}
