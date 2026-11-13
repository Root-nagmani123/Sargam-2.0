<?php

namespace App\Http\Controllers\Admin\COE;

use App\Exports\COE\EvaluationMarksTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\COE\EvaluationMarksImport;
use App\Services\COE\ResultMarksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

/**
 * COE → Examination Management → Evaluation & Marks Entry (faculty end).
 *
 * The faculty's marks sheet for one course: one row per participant × component
 * they evaluate on the course's drives (open once the Course Coordinator approves
 * the drive), split into Pending and Completed (approved). Save as Draft keeps
 * the marks editable; Approved locks them and counts them in Result Compilation.
 * Only the evaluating faculty approve. See ResultMarksService for who evaluates what.
 */
class FacultyResultEntryController extends Controller
{
    public const IMPORT_MAX_MB = 5;

    public const IMPORT_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    public function __construct(private ResultMarksService $results)
    {
    }

    /** ?course=<pk>&subject=<pk>&component=<column key>&tab=pending|completed */
    public function index(Request $request)
    {
        $sheet = $this->sheet($request);

        return view('admin.coe.faculty_result_entry.index', $sheet + [
            'tab' => $request->query('tab') === 'completed' ? 'completed' : 'pending',
            'marksHead' => $this->marksHead($sheet['entries']),
            'withRemarks' => ResultMarksService::remarksEnabled(),
            'importExtensions' => self::IMPORT_EXTENSIONS,
            'importMaxMb' => self::IMPORT_MAX_MB,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'course' => ['required', 'integer'],
            'subject' => ['nullable', 'integer'],
            'component' => ['nullable', 'string', 'max:64'],
            'marks' => ['array'],
            'remarks' => ['array'],
            'approve' => ['boolean'],
        ]);

        $sheet = $this->results->entrySheet(
            $request->user(),
            (int) $data['course'],
            isset($data['subject']) ? (int) $data['subject'] : null,
            $data['component'] ?? null,
        );
        // The sheet falls back to another course, all subjects or all components when the
        // asked one is not the user's; saving must never land on columns other than those asked for.
        if ($sheet['coursePk'] !== (int) $data['course']
            || (isset($data['subject']) && $sheet['subjectPk'] !== (int) $data['subject'])
            || (isset($data['component']) && $sheet['componentKey'] !== $data['component'])) {
            return response()->json(['message' => 'You are not assigned to marks entry on this course, subject or component.'], 403);
        }

        $approve = (bool) ($data['approve'] ?? false);
        $result = $this->results->save($request->user(), $sheet['columns'], $data['marks'] ?? [], $approve, $data['remarks'] ?? []);

        if ($result['errors']) {
            $errors = $result['errors'];

            return response()->json([
                'message' => $errors['_'] ?? 'Some marks are not valid. Fix the highlighted cells.',
                'errors' => array_diff_key($errors, ['_' => true]),
            ], 422);
        }

        return response()->json(['message' => $approve
            ? "Marks approved ({$result['approved']})."
            : ($result['saved'] ? "Marks saved as draft ({$result['saved']})." : 'Nothing changed.')]);
    }

    /** The whole pending table of the sheet on screen, pre-filled, as the import template. */
    public function template(Request $request)
    {
        $sheet = $this->sheet($request);
        $pending = array_values(array_filter($sheet['entries'], fn ($e) => ! $e['mark']?->isApproved()));

        return Excel::download(
            new EvaluationMarksTemplateExport($pending, $this->marksHead($sheet['entries'])),
            'Evaluation_Marks_Template_'.now()->format('YmdHis').'.xlsx'
        );
    }

    /** Fills marks of the sheet on screen from the template, as a draft. All or nothing, like Save as Draft. */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:'.(self::IMPORT_MAX_MB * 1024)],
            'course' => ['nullable', 'integer'],
            'subject' => ['nullable', 'integer'],
            'component' => ['nullable', 'string', 'max:64'],
        ], [
            'file.required' => 'Choose an .xlsx, .xls or .csv file first.',
            'file.mimes' => 'The file must be .xlsx, .xls or .csv.',
            'file.max' => 'The file must be '.self::IMPORT_MAX_MB.' MB or smaller.',
        ]);

        $sheet = $this->sheet($request);
        $columns = $sheet['columns'];

        $import = new EvaluationMarksImport($sheet['entries']);
        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            Log::error('COE evaluation marks import failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'That file could not be read. Download the template and check the column headings.',
            ], 422);
        }

        if ($import->failures) {
            return response()->json(['message' => 'Nothing was imported. Fix these rows and import again.', 'failures' => $import->failures], 422);
        }
        if ($import->marks === []) {
            return response()->json(['message' => $import->locked
                ? 'Nothing to import: the marks in the file are already approved, or blank.'
                : 'Nothing to import: no row of the file has marks.'], 422);
        }

        $touched = array_values(array_filter($columns, fn ($c) => isset($import->marks[$c['key']])));
        $result = $this->results->save($request->user(), $touched, $import->marks, false, $import->remarks);

        if ($result['errors']) {
            $failures = [];
            foreach ($result['errors'] as $cell => $message) {
                $failures[] = isset($import->rowOf[$cell]) ? "Row {$import->rowOf[$cell]}: {$message}" : $message;
            }

            return response()->json(['message' => 'Nothing was imported. Fix these rows and import again.', 'failures' => $failures], 422);
        }

        return response()->json([
            'message' => ($result['saved'] ? "{$result['saved']} mark(s) saved as draft." : 'Nothing changed.')
                .($import->skipped ? " {$import->skipped} row(s) without marks skipped." : '')
                .($import->locked ? " {$import->locked} approved row(s) left unchanged." : '')
                .' Approve them once checked.',
            'saved' => $result['saved'],
        ]);
    }

    /** "Marks / 50" when every row of the sheet shares one maximum, as on most sheets. */
    private function marksHead(array $entries): string
    {
        $maxes = array_unique(array_map(fn ($e) => $e['max'], $entries));

        return count($maxes) === 1 ? 'Marks / '.ResultMarksService::number(reset($maxes)) : 'Marks';
    }

    private function sheet(Request $request): array
    {
        return $this->results->entrySheet(
            $request->user(),
            $request->integer('course') ?: null,
            $request->integer('subject') ?: null,
            $request->string('component')->trim()->value() ?: null,
        );
    }
}
