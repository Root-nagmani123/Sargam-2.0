<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Services\COE\ResultMarksService;
use Illuminate\Http\Request;

/**
 * COE → Result Management → Merit & Ranking.
 *
 * The fully evaluated OTs of one course, ranked by Grand Total (A+B): subjects (A)
 * plus Director Assessment (B), with ICT / Hindi shown as qualifying papers.
 * Rules: ResultMarksService::merit().
 */
class MeritRankingController extends Controller
{
    public function __construct(private ResultMarksService $results)
    {
    }

    /**
     * ?scope=active|archived&course=<pk>&drive=<main drive id>&status=Pass|Fail|Pending&reexam=<re-exam drive id>
     *
     * Merit is drive-wise: Drive shows that examination drive only (its subjects, its marks,
     * ranked on them; a course picked without one opens on its first drive) and narrows the
     * Re-exam list. Picking a re-exam
     * shows its OTs with the re-exam marks in the subjects they re-sat, main marks beside
     * them, rank unchanged (ResultMarksService::reexamMerit). Status filters the rows.
     */
    public function index(Request $request)
    {
        $scope = $request->query('scope') === 'archived' ? 'archived' : 'active';
        $coursePk = $request->integer('course') ?: null;
        $filters = $this->results->meritReexamFilters($scope);

        $drive = $request->integer('drive') ?: null;
        $reexam = $request->integer('reexam') ?: null;
        if ($reexam && (! isset($filters['reexams'][$reexam]) || ($drive && $filters['reexams'][$reexam]['main'] !== $drive))) {
            $reexam = null;
        }
        // A re-exam belongs to one course (and drive).
        if ($reexam) {
            $coursePk = $filters['reexams'][$reexam]['course'];
            $drive = $filters['reexams'][$reexam]['main'];
        }
        if ($drive && (! isset($filters['drives'][$drive]) || ($coursePk && $filters['drives'][$drive]['course'] !== $coursePk))) {
            $drive = null;
        }

        $data = ($reexam ? $this->results->reexamMerit($scope, $reexam) : null)
            // No course in the address (menu link, Remove Filter): the first course. "All" in the
            // dropdown sends course= (empty) and lists every course.
            ?? $this->results->merit($scope, $coursePk, $drive, ! $request->has('course')) + ['reexam' => null];
        $coursePk = $data['coursePk'];

        // Merit is drive-wise (agreed 2026-11-27): a course picked opens on its first drive.
        // Course "All" lists every drive, each ranked within itself.
        if (! $data['reexam'] && $coursePk && ! $drive) {
            // The first drive (by name) with an exam over, i.e. one merit lists.
            $first = array_key_first(array_filter($filters['drives'], fn ($d, $id) => $d['course'] === $coursePk && isset($data['drives'][$id]), ARRAY_FILTER_USE_BOTH));
            if ($first !== null) {
                $drive = (int) $first;
                $data = $this->results->merit($scope, $coursePk, $drive) + ['reexam' => null];
            }
        }

        $statuses = $data['reexam'] ? ['Pass', 'Fail', 'Pending'] : ['Pass', 'Fail'];
        $status = in_array($request->query('status'), $statuses, true) ? $request->query('status') : null;
        if ($status) {
            $data['rows'] = array_values(array_filter($data['rows'], fn ($r) => ($r['result'] ?? null) === $status));
        }

        // Options follow the choices above them: drives of the course, re-exams of the drive.
        $driveOptions = array_filter($filters['drives'], fn ($d) => ! $coursePk || $d['course'] === $coursePk);
        $reexamOptions = array_filter($filters['reexams'], fn ($r) => (! $coursePk || $r['course'] === $coursePk) && (! $drive || $r['main'] === $drive));

        return view('admin.coe.merit_ranking.index', $data + [
            'scope' => $scope,
            'drivePk' => $drive,
            'reexamPk' => $data['reexam'] ? $reexam : null,
            'status' => $status,
            'statuses' => $statuses,
            'driveOptions' => $driveOptions,
            'reexamOptions' => $reexamOptions,
        ]);
    }
}
