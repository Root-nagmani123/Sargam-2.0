<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Services\COE\ResultMarksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * COE → Result Management → Result Compilation.
 *
 * One row per course × subject over the course's examination drives: how many
 * participants have every component of the subject approved by faculty, how
 * many are pending, and the average score. Approved / Pending open the
 * participants list for that subject; without a subject it shows the whole
 * course, every subject's components. Marks come from Faculty Result Entry
 * (see ResultMarksService).
 */
class ResultCompilationController extends Controller
{
    public function __construct(private ResultMarksService $results)
    {
    }

    public function index(Request $request)
    {
        $scope = $request->query('scope') === 'archived' ? 'archived' : 'active';
        $rows = $this->results->compilation($scope);

        return view('admin.coe.result_compilation.index', [
            'scope' => $scope,
            'rows' => $rows,
            'courses' => collect($rows)->pluck('course')->unique()->values()->all(),
        ]);
    }

    /** ?course=<pk>&subject=<pk>&status=evaluated|pending&scope=active|archived */
    public function participants(Request $request)
    {
        $scope = $request->query('scope') === 'archived' ? 'archived' : 'active';
        $status = $request->query('status') === 'pending' ? 'pending' : 'evaluated';

        $sheets = $this->results->courseSheets($scope);
        $coursePk = (int) $request->query('course');
        if (! isset($sheets[$coursePk])) {
            $coursePk = (int) array_key_first($sheets);
        }
        $sheet = $sheets[$coursePk] ?? ['course_pk' => 0, 'course' => '', 'subjects' => [], 'rows' => []];

        $subjectPk = (int) $request->query('subject');
        $subjectPk = isset($sheet['subjects'][$subjectPk]) ? $subjectPk : null;

        $rows = array_values(array_filter($sheet['rows'], function ($r) use ($subjectPk, $status) {
            $isPending = $subjectPk ? in_array($subjectPk, $r['pending'], true) : $r['pending'] !== [];

            return $status === 'pending' ? $isPending : ! $isPending;
        }));

        return view('admin.coe.result_compilation.participants', [
            'scope' => $scope,
            'courses' => array_map(fn ($s) => $s['course'], $sheets),
            'coursePk' => $coursePk,
            'subjects' => $sheet['subjects'],
            'subjectPk' => $subjectPk,
            'status' => $status,
            // One subject: its components. Whole course: every subject, grouped.
            'columns' => $subjectPk ? [$subjectPk => $sheet['subjects'][$subjectPk]] : $sheet['subjects'],
            'rows' => $rows,
        ]);
    }

    /** Notify for Pending: the course (optionally one subject / one participant). */
    public function notify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'course' => ['required', 'integer'],
            'subject' => ['nullable', 'integer'],
            'student' => ['nullable', 'integer'],
        ]);

        $result = $this->results->notifyPending(
            (int) $data['course'],
            isset($data['subject']) ? (int) $data['subject'] : null,
            isset($data['student']) ? (int) $data['student'] : null,
        );

        if ($result['pending'] === 0) {
            return response()->json(['message' => 'Nothing is pending on a drive open for marks entry. Marks entry opens once the Course Coordinator approves the drive.'], 422);
        }
        if ($result['notified'] === 0) {
            return response()->json(['message' => 'No faculty with a login is mapped to the pending marks, so nobody could be notified.'], 422);
        }

        $message = $result['notified'] === 1 ? '1 faculty notified.' : "{$result['notified']} faculty notified.";
        if ($result['unreachable'] > 0) {
            $message .= " {$result['unreachable']} could not be notified (no login linked).";
        }

        return response()->json(['message' => $message]);
    }
}
