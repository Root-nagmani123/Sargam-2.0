<?php

namespace App\DataTables\FC;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side student list for the enrolment screen (Enrollment → Create).
 *
 * The page previously loaded every matching student in one response (661 rows /
 * ~141 KB for two courses) and paged them client-side. This serves one page per
 * request instead.
 *
 * The underlying query is intentionally identical to
 * EnrollementController::filterStudents() — same joins, same active-student
 * filter, same DISTINCT — so the rows offered for enrolment do not change.
 */
class FcEnrollmentStudentsDataTable
{
    /**
     * Base query shared by the grid, the row count and the "select all" pk list.
     *
     * @param  list<int|string>  $previousCourses
     * @param  list<int|string>  $services
     */
    public static function baseQuery(array $previousCourses, array $services = []): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('student_master_course__map as smcm')
            ->join('student_master as sm', 'smcm.student_master_pk', '=', 'sm.pk')
            ->join('course_master as cm', 'smcm.course_master_pk', '=', 'cm.pk')
            ->leftJoin('service_master as svm', 'sm.service_master_pk', '=', 'svm.pk')
            ->whereIn('smcm.course_master_pk', $previousCourses)
            ->where('sm.active_inactive', 1); // Only active students

        if ($services !== []) {
            $query->whereIn('sm.service_master_pk', $services);
        }

        return $query;
    }

    /**
     * Every student pk matching the current filters — used by "select all" so a
     * selection can span pages without loading every row into the browser.
     *
     * @param  list<int|string>  $previousCourses
     * @param  list<int|string>  $services
     * @return list<int>
     */
    public static function allMatchingPks(array $previousCourses, array $services = []): array
    {
        return self::baseQuery($previousCourses, $services)
            ->distinct()
            ->pluck('sm.pk')
            ->map(fn ($pk) => (int) $pk)
            ->all();
    }

    /**
     * DataTables JSON for one page of students.
     */
    public function ajax(Request $request)
    {
        $previousCourses = (array) $request->input('previous_courses', []);
        $services = array_filter((array) $request->input('services', []));

        if ($previousCourses === []) {
            // Same guard as filterStudents(): nothing to show without a course.
            return DataTables::of(
                self::baseQuery([0])->selectRaw('NULL as student_pk')->whereRaw('1 = 0')
            )->make(true);
        }

        $query = self::baseQuery($previousCourses, $services)
            ->select(
                'sm.pk as student_pk',
                DB::raw("CONCAT(sm.first_name, ' ', COALESCE(sm.middle_name, ''), ' ', sm.last_name) as student_name"),
                'sm.generated_OT_code as ot_code',
                'cm.course_name',
                'cm.couse_short_name as course_short_name',
                'svm.service_name',
                'svm.service_short_name'
            )
            ->distinct();

        return DataTables::of($query)
            ->addIndexColumn()
            // Checkbox carries only the pk; the browser keeps the selected set.
            ->addColumn('select', fn ($row) => '<input type="checkbox" class="form-check-input student-checkbox" value="'.(int) $row->student_pk.'">')
            ->editColumn('course_name', fn ($row) => self::withShort($row->course_name, $row->course_short_name))
            ->editColumn('student_name', fn ($row) => e(trim(preg_replace('/\s+/', ' ', (string) $row->student_name))) ?: 'N/A')
            ->editColumn('ot_code', fn ($row) => e($row->ot_code ?: 'N/A'))
            ->addColumn('service', fn ($row) => self::withShort($row->service_name, $row->service_short_name))
            ->filterColumn('student_name', function ($q, $keyword) {
                $q->whereRaw("CONCAT(sm.first_name, ' ', COALESCE(sm.middle_name, ''), ' ', sm.last_name) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('course_name', fn ($q, $k) => $q->where('cm.course_name', 'like', "%{$k}%"))
            ->filterColumn('ot_code', fn ($q, $k) => $q->where('sm.generated_OT_code', 'like', "%{$k}%"))
            ->filterColumn('service', fn ($q, $k) => $q->where('svm.service_name', 'like', "%{$k}%"))
            // Order by the SELECTed aliases: the query uses DISTINCT, and MySQL
            // rejects an ORDER BY on a column that is not in the select list
            // (error 3065) — e.g. sm.first_name behind the student_name CONCAT.
            // Every sort carries student_pk as a unique tie-breaker. The visible sort
            // columns are not unique, and a non-deterministic ORDER BY with
            // LIMIT/OFFSET can repeat a row on one page and skip another — which on
            // this screen would mean a student silently never offered for enrolment.
            ->orderColumn('student_name', 'student_name $1, student_pk asc')
            ->orderColumn('course_name', 'course_name $1, student_pk asc')
            ->orderColumn('ot_code', 'ot_code $1, student_pk asc')
            ->orderColumn('service', 'service_name $1, student_pk asc')
            ->rawColumns(['select', 'course_name', 'service'])
            ->make(true);
    }

    /** "Name (SHORT)" — mirrors formatNameWithShort() in the blade. */
    private static function withShort(?string $name, ?string $short): string
    {
        $name = trim((string) $name);
        $short = trim((string) $short);

        if ($name === '') {
            return 'N/A';
        }

        return $short !== '' && $short !== $name
            ? e($name).' <small class="text-muted">('.e($short).')</small>'
            : e($name);
    }
}
