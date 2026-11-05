<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Services\COE\ResultMarksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * COE → Result Management → Result Entry (faculty end).
 *
 * The faculty's marks sheet for one course: every component they evaluate on the
 * course's drives (open once the Course Coordinator approves the drive), one row
 * per participant they evaluate. Save keeps a draft; Save & Approve locks the
 * marks and counts them in Result Compilation. See ResultMarksService for who
 * evaluates what.
 */
class FacultyResultEntryController extends Controller
{
    public function __construct(private ResultMarksService $results)
    {
    }

    /** ?course=<pk>&subject=<pk> */
    public function index(Request $request)
    {
        $sheet = $this->results->entrySheet(
            $request->user(),
            $request->integer('course') ?: null,
            $request->integer('subject') ?: null,
        );

        return view('admin.coe.faculty_result_entry.index', $sheet);
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'course' => ['required', 'integer'],
            'subject' => ['nullable', 'integer'],
            'marks' => ['array'],
            'approve' => ['boolean'],
        ]);

        $sheet = $this->results->entrySheet($request->user(), (int) $data['course'], isset($data['subject']) ? (int) $data['subject'] : null);
        // The sheet falls back to another course or all subjects when the asked one is
        // not the user's; saving must never land on columns other than those asked for.
        if ($sheet['coursePk'] !== (int) $data['course'] || (isset($data['subject']) && $sheet['subjectPk'] !== (int) $data['subject'])) {
            return response()->json(['message' => 'You are not assigned to marks entry on this course or subject.'], 403);
        }

        $result = $this->results->save($request->user(), $sheet['columns'], $data['marks'] ?? [], (bool) ($data['approve'] ?? false));

        if ($result['errors']) {
            $errors = $result['errors'];

            return response()->json([
                'message' => $errors['_'] ?? 'Some marks are not valid. Fix the highlighted cells.',
                'errors' => array_diff_key($errors, ['_' => true]),
            ], 422);
        }

        return response()->json(['message' => $data['approve'] ?? false
            ? "Marks approved ({$result['approved']})."
            : ($result['saved'] ? "Marks saved ({$result['saved']})." : 'Nothing changed.')]);
    }
}
