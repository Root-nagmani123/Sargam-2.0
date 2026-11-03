<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Admin\COE\Concerns\SampleResultData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * COE → Result Management → Result Compilation.
 *
 * Design-first (see SampleResultData). One row per course × subject: how many OTs
 * the faculty have finished evaluating, how many are pending, and the average
 * score. Approved / Pending open the participants list for that subject; the
 * list without a subject shows the whole course, every subject's components.
 */
class ResultCompilationController extends Controller
{
    use SampleResultData;

    public function index(Request $request)
    {
        $scope = $request->query('scope') === 'archived' ? 'archived' : 'active';
        $rows = [];

        foreach ($this->scopedCourses($scope) as $key => $course) {
            $sheet = $this->resultSheet($key);
            foreach ($this->resultSubjects() as $subject => $s) {
                $evaluated = array_filter($sheet, fn ($r) => ! in_array($subject, $r['pending'], true));
                $max = $this->subjectMax($subject);
                $rows[] = [
                    'course_key' => $key,
                    'course' => $course['name'],
                    'subject' => $subject,
                    'code' => $s['code'],
                    'max' => $max,
                    'approved' => count($evaluated),
                    'pending' => count($sheet) - count($evaluated),
                    'total' => count($sheet),
                    'average' => $evaluated
                        ? round(array_sum(array_map(fn ($r) => $r['subjects'][$subject], $evaluated)) / count($evaluated) / $max * 100, 1)
                        : null,
                ];
            }
        }

        return view('admin.coe.result_compilation.index', [
            'scope' => $scope,
            'rows' => $rows,
            'courses' => $this->scopedCourses($scope),
        ]);
    }

    /** ?course=…&subject=…&status=evaluated|pending */
    public function participants(Request $request)
    {
        $courses = $this->resultCourses();
        $subjects = $this->resultSubjects();

        $courseKey = $request->query('course');
        if (! isset($courses[$courseKey])) {
            $courseKey = array_key_first($courses);
        }
        $subject = isset($subjects[$request->query('subject')]) ? $request->query('subject') : null;
        $status = $request->query('status') === 'pending' ? 'pending' : 'evaluated';

        $rows = array_values(array_filter($this->resultSheet($courseKey), function ($r) use ($subject, $status) {
            $isPending = $subject ? in_array($subject, $r['pending'], true) : $r['pending'] !== [];

            return $status === 'pending' ? $isPending : ! $isPending;
        }));

        return view('admin.coe.result_compilation.participants', [
            'courses' => $courses,
            'courseKey' => $courseKey,
            'subjects' => $subjects,
            'subject' => $subject,
            'status' => $status,
            // One subject: its components. Whole course: every subject, grouped.
            'columns' => $subject ? [$subject => $subjects[$subject]] : $subjects,
            'rows' => $rows,
        ]);
    }
}
