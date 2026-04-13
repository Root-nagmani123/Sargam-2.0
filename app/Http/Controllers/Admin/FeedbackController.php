<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\FC\PendingFeedbackDataTable;
use App\DataTables\FC\PendingFeedbackSummaryDataTable;
use App\DataTables\FC\SessionTimetableReportDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CourseMaster;
use App\Models\FacultyMaster;
use App\Models\VenueMaster;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\FacultyFeedbackExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str; // Add this import
use App\Exports\PendingFeedbackExport;
use App\Exports\FacultyFeedback_AvgExport;
use App\Exports\PendingFeedbackSummaryExport;
use App\Exports\FeedbackDatabaseExport;
use App\Exports\SessionTimetableReportExport;




class FeedbackController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function database(Request $request)
    {
        try {
            $courseType = $request->get('course_type', 'current');
            if (! in_array($courseType, ['current', 'archived'], true)) {
                $courseType = 'current';
            }

            $courses = $this->coursesForFeedbackDatabase($courseType);

            // Fetch all active faculties for dropdown
            $faculties = FacultyMaster::where('active_inactive', 1)
                ->select('pk', 'full_name')
                ->orderBy('full_name')
                ->get();

            return view('admin.feedback.feedback_database', compact('courses', 'faculties', 'courseType'));
        } catch (\Exception $e) {
            \Log::error('Error in FeedbackController@database: ' . $e->getMessage());

            // Fallback empty collections
            $courses = collect();
            $faculties = collect();
            $courseType = 'current';

            return view('admin.feedback.feedback_database', compact('courses', 'faculties', 'courseType'));
        }
    }

    /**
     * Program list for Feedback Database — same rules as Faculty Feedback Average (current vs archived).
     */
    private function coursesForFeedbackDatabase(string $courseType)
    {
        $userId = auth()->id();
        $data_course_id = get_Role_by_course();
        $currentDate = now()->toDateString();

        $coursesQuery = CourseMaster::where('active_inactive', 1)
            ->when($courseType === 'current', function ($q) use ($currentDate) {
                $q->where(function ($q2) use ($currentDate) {
                    $q2->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $currentDate);
                });
            })
            ->when($courseType === 'archived', function ($q) use ($currentDate) {
                $q->whereDate('end_date', '<', $currentDate);
            });

        if (! empty($data_course_id)) {
            $coursesQuery->whereIn('pk', $data_course_id);
        }

        if (auth()->user()->role == 'student') {
            $coursesQuery->whereHas('students', function ($q) use ($userId) {
                $q->where('student_master_pk', $userId)
                    ->where('active_inactive', 1);
            });
        }

        return $coursesQuery->select('pk', 'course_name')
            ->orderBy('course_name')
            ->get();
    }

    /**
     * JSON course options when switching Active / Archived on Feedback Database.
     */
    public function getDatabaseCourses(Request $request)
    {
        $request->validate([
            'course_type' => 'required|string|in:current,archived',
        ]);

        try {
            $courses = $this->coursesForFeedbackDatabase($request->course_type);

            return response()->json([
                'success' => true,
                'course_type' => $request->course_type,
                'courses' => $courses,
            ]);
        } catch (\Exception $e) {
            \Log::error('getDatabaseCourses: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'courses' => [],
            ], 500);
        }
    }

    /**
     * Timetable session listing (faculty JSON, groups, venue) — DataTables server-side.
     */
    public function sessionTimetableReport(Request $request, SessionTimetableReportDataTable $dataTable)
    {
        try {
            $courseType = $request->get('course_type', 'current');
            if (! in_array($courseType, ['current', 'archived'], true)) {
                $courseType = 'current';
            }

            $courses = $this->coursesForFeedbackDatabase($courseType);

            $faculties = FacultyMaster::where('active_inactive', 1)
                ->select('pk', 'full_name')
                ->orderBy('full_name')
                ->get();

            $venues = VenueMaster::where('active_inactive', 1)
                ->select('venue_id', 'venue_name')
                ->orderBy('venue_name')
                ->get();

            $defaultCourseId = $courses->isNotEmpty() ? (int) $courses->first()->pk : null;

            return $dataTable->render('admin.feedback.session_timetable_report', compact(
                'courses',
                'faculties',
                'venues',
                'courseType',
                'defaultCourseId'
            ));
        } catch (\Exception $e) {
            \Log::error('sessionTimetableReport: ' . $e->getMessage());

            return $dataTable->render('admin.feedback.session_timetable_report', [
                'courses' => collect(),
                'faculties' => collect(),
                'venues' => collect(),
                'courseType' => 'current',
                'defaultCourseId' => null,
            ]);
        }
    }

    public function sessionTimetableReportDatatable(SessionTimetableReportDataTable $dataTable)
    {
        try {
            return $dataTable->ajax();
        } catch (\Exception $e) {
            \Log::error('sessionTimetableReportDatatable: ' . $e->getMessage());

            return response()->json([
                'draw' => (int) request()->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Could not load data.',
            ], 500);
        }
    }

    /**
     * Base query for session timetable report (DataTables AJAX sends filter_* params).
     */
    public function makeSessionTimetableReportQueryBuilder(Request $request): \Illuminate\Database\Query\Builder
    {
        $courseType = $request->input('filter_course_type', $request->input('course_type', 'current'));
        if (! in_array($courseType, ['current', 'archived'], true)) {
            $courseType = 'current';
        }

        $filters = [
            'course_type' => $courseType,
            'course_id' => $request->filled('filter_course_id') ? (int) $request->input('filter_course_id') : 0,
        ];

        if ($request->filled('filter_faculty_id')) {
            $filters['faculty_id'] = (int) $request->input('filter_faculty_id');
        }

        $ft = $request->input('filter_faculty_type');
        if ($ft !== null && $ft !== '' && in_array((string) $ft, ['1', '2', '3'], true)) {
            $filters['faculty_type'] = (string) $ft;
        }

        if ($request->filled('filter_venue_id')) {
            $filters['venue_id'] = (string) $request->input('filter_venue_id');
        }

        $topic = $request->input('filter_subject_topic');
        if (is_string($topic) && trim($topic) !== '') {
            $filters['subject_topic'] = Str::limit(trim($topic), 500, '');
        }

        $mod = $request->input('filter_subject_module');
        if (is_string($mod) && trim($mod) !== '') {
            $filters['subject_module'] = Str::limit(trim($mod), 500, '');
        }

        if ($request->filled('filter_date_from')) {
            $filters['date_from'] = $request->input('filter_date_from');
        }

        if ($request->filled('filter_date_to')) {
            $filters['date_to'] = $request->input('filter_date_to');
        }

        $accessibleCourseIds = $this->coursesForFeedbackDatabase($courseType)
            ->pluck('pk')
            ->map(fn ($pk) => (int) $pk)
            ->filter()
            ->values()
            ->all();

        if ($accessibleCourseIds === []) {
            return DB::table('timetable as t')->whereRaw('1 = 0');
        }

        return $this->buildSessionTimetableReportQuery($filters, $accessibleCourseIds);
    }

    /**
     * Human-readable filter lines for print / PDF / Excel (LBSNAA reports).
     *
     * @return array{lines: array<int, string>, summary: string}
     */
    private function sessionTimetableExportFilterDescriptions(Request $request): array
    {
        $lines = [];
        $ct = $request->input('filter_course_type', $request->input('course_type', 'current'));
        if (! in_array($ct, ['current', 'archived'], true)) {
            $ct = 'current';
        }
        $lines[] = $ct === 'archived'
            ? 'Program scope: Archived courses'
            : 'Program scope: Active (current) courses';

        if ($request->filled('filter_course_id')) {
            $name = DB::table('course_master')->where('pk', (int) $request->input('filter_course_id'))->value('course_name');
            $lines[] = 'Course: '.($name ?? '—');
        } else {
            $lines[] = 'Course: All programs in list';
        }

        if ($request->filled('filter_faculty_id')) {
            $name = DB::table('faculty_master')->where('pk', (int) $request->input('filter_faculty_id'))->value('full_name');
            $lines[] = 'Faculty: '.($name ?? '—');
        }

        if ($request->filled('filter_faculty_type')) {
            $map = ['1' => 'Internal', '2' => 'Guest', '3' => 'Research'];
            $ft = (string) $request->input('filter_faculty_type');
            $lines[] = 'Faculty type: '.($map[$ft] ?? $ft);
        }

        if ($request->filled('filter_venue_id')) {
            $name = DB::table('venue_master')->where('venue_id', $request->input('filter_venue_id'))->value('venue_name');
            $lines[] = 'Venue: '.($name ?? $request->input('filter_venue_id'));
        }

        if ($request->filled('filter_date_from')) {
            $lines[] = 'Date from: '.$request->input('filter_date_from');
        }
        if ($request->filled('filter_date_to')) {
            $lines[] = 'Date to: '.$request->input('filter_date_to');
        }
        if ($request->filled('filter_subject_topic')) {
            $lines[] = 'Topic contains: '.Str::limit((string) $request->input('filter_subject_topic'), 100);
        }
        if ($request->filled('filter_subject_module')) {
            $lines[] = 'Module contains: '.Str::limit((string) $request->input('filter_subject_module'), 100);
        }

        return [
            'lines' => $lines,
            'summary' => implode('  |  ', $lines),
        ];
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, filter_lines: array<int, string>, filter_summary: string, record_count: int, export_date: string}
     */
    private function buildSessionTimetableExportContext(Request $request): array
    {
        $request->validate([
            'filter_course_type' => 'nullable|string|in:current,archived',
            'filter_course_id' => 'nullable|integer',
            'filter_faculty_id' => 'nullable|integer',
            'filter_faculty_type' => 'nullable|string|in:1,2,3',
            'filter_venue_id' => 'nullable|string|max:64',
            'filter_subject_topic' => 'nullable|string|max:500',
            'filter_subject_module' => 'nullable|string|max:500',
            'filter_date_from' => 'nullable|date',
            'filter_date_to' => 'nullable|date',
        ]);

        $filterMeta = $this->sessionTimetableExportFilterDescriptions($request);
        $data = $this->makeSessionTimetableReportQueryBuilder($request)
            ->orderByDesc('t.START_DATE')
            ->get();

        $rows = [];
        foreach ($data as $i => $item) {
            $rows[] = [
                's_no' => $i + 1,
                'start_date' => ! empty($item->start_date) ? Carbon::parse($item->start_date)->format('d-m-Y H:i') : '—',
                'end_date' => ! empty($item->end_date) ? Carbon::parse($item->end_date)->format('d-m-Y H:i') : '—',
                'subject_topic' => $item->subject_topic ?? '—',
                'faculty_name' => $item->faculty_name ?? '—',
                'faculty_code' => $item->faculty_code ?? '—',
                'faculty_type' => $item->faculty_type ?? '—',
                'course_name' => $item->course_name ?? '—',
                'course_short' => $item->couse_short_name ?? '—',
                'prog_type' => $item->course_group_type_master ?? '—',
                'groups' => $item->group_name ?? '—',
                'class_session' => $item->class_session ?? '—',
                'venue' => $item->venue_name ?? '—',
                'subject' => $item->subject_master_name ?? '—',
                'module' => $item->subject_module_name ?? '—',
            ];
        }

        return [
            'rows' => $rows,
            'filter_lines' => $filterMeta['lines'],
            'filter_summary' => $filterMeta['summary'],
            'record_count' => count($rows),
            'export_date' => now()->format('d-m-Y H:i'),
        ];
    }

    public function printSessionTimetableReport(Request $request)
    {
        try {
            $ctx = $this->buildSessionTimetableExportContext($request);

            return view('admin.feedback.session_timetable_report_export', array_merge($ctx, ['mode' => 'print']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.feedback.session_timetable_report')->with('error', 'Invalid export parameters.');
        } catch (\Exception $e) {
            \Log::error('printSessionTimetableReport: '.$e->getMessage());

            return redirect()->route('admin.feedback.session_timetable_report')->with('error', 'Could not open print view.');
        }
    }

    public function exportSessionTimetableReportPdf(Request $request)
    {
        try {
            $ctx = $this->buildSessionTimetableExportContext($request);

            $pdf = Pdf::loadView('admin.feedback.session_timetable_report_export', array_merge($ctx, ['mode' => 'pdf']))
                ->setPaper('A4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'sans-serif',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'dpi' => 96,
                    'margin_top' => 6,
                    'margin_right' => 6,
                    'margin_bottom' => 6,
                    'margin_left' => 6,
                ]);

            return $pdf->download('timetable_sessions_'.date('Y-m-d_His').'.pdf');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.feedback.session_timetable_report')->with('error', 'Invalid export parameters.');
        } catch (\Exception $e) {
            \Log::error('exportSessionTimetableReportPdf: '.$e->getMessage());

            return redirect()->route('admin.feedback.session_timetable_report')->with('error', 'PDF export failed.');
        }
    }

    public function exportSessionTimetableReportExcel(Request $request)
    {
        try {
            $ctx = $this->buildSessionTimetableExportContext($request);

            return Excel::download(
                new SessionTimetableReportExport(
                    $ctx['rows'],
                    $ctx['filter_summary'],
                    $ctx['export_date'],
                    $ctx['record_count']
                ),
                'timetable_sessions_'.now()->format('Y-m-d_H-i').'.xlsx'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.feedback.session_timetable_report')->with('error', 'Invalid export parameters.');
        } catch (\Exception $e) {
            \Log::error('exportSessionTimetableReportExcel: '.$e->getMessage());

            return redirect()->route('admin.feedback.session_timetable_report')->with('error', 'Excel export failed.');
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, int>  $accessibleCourseIds
     */
    private function buildSessionTimetableReportQuery(array $filters, array $accessibleCourseIds)
    {
        $courseId = isset($filters['course_id']) ? (int) $filters['course_id'] : 0;

        // Faculty / group expansion: JSON arrays (new) or plain numeric pk (legacy).
        // MariaDB does not support CAST(x AS JSON); use CAST(pk AS CHAR) as JSON_CONTAINS needle (numeric JSON).
        $facultyMatchSql = '(JSON_VALID(t.faculty_master) AND JSON_CONTAINS(t.faculty_master, CAST(fm.pk AS CHAR), \'$\')) OR (NOT JSON_VALID(t.faculty_master) AND TRIM(COALESCE(t.faculty_master, \'\')) <> \'\' AND CAST(t.faculty_master AS UNSIGNED) = fm.pk)';
        $groupMatchSql = '(JSON_VALID(t.group_name) AND JSON_CONTAINS(t.group_name, CAST(gcm.pk AS CHAR), \'$\')) OR (NOT JSON_VALID(t.group_name) AND TRIM(COALESCE(t.group_name, \'\')) <> \'\' AND CAST(t.group_name AS UNSIGNED) = gcm.pk)';

        $query = DB::table('timetable as t')
            ->leftJoin('course_master as c', 't.course_master_pk', '=', 'c.pk')
            ->leftJoin('course_group_type_master as cgtm', 't.course_group_type_master', '=', 'cgtm.pk')
            ->leftJoin('venue_master as vm', 't.venue_id', '=', 'vm.venue_id')
            ->leftJoin('subject_master as sm', 't.subject_master_pk', '=', 'sm.pk')
            ->leftJoin('subject_module_master as smm', 't.subject_module_master_pk', '=', 'smm.pk')
            ->select([
                't.pk',
                DB::raw('t.START_DATE AS start_date'),
                DB::raw('t.END_DATE AS end_date'),
                't.subject_topic',
                DB::raw('COALESCE(
                    (SELECT GROUP_CONCAT(fm.full_name ORDER BY fm.full_name SEPARATOR \', \')
                     FROM faculty_master fm
                     WHERE '.$facultyMatchSql.'),
                    \'No Faculty Assigned\'
                ) AS faculty_name'),
                DB::raw('COALESCE(
                    (SELECT GROUP_CONCAT(fm.faculty_code ORDER BY fm.faculty_code SEPARATOR \', \')
                     FROM faculty_master fm
                     WHERE '.$facultyMatchSql.'),
                    \'No Faculty Code\'
                ) AS faculty_code'),
                DB::raw('COALESCE(
                    (SELECT GROUP_CONCAT(
                        CASE fm.faculty_type
                            WHEN 1 THEN \'Internal\'
                            WHEN 2 THEN \'Guest\'
                            WHEN 3 THEN \'Research\'
                            ELSE \'Unknown\'
                        END ORDER BY fm.pk SEPARATOR \', \')
                     FROM faculty_master fm
                     WHERE '.$facultyMatchSql.'),
                    \'No Faculty Type\'
                ) AS faculty_type'),
                'c.course_name',
                'c.couse_short_name',
                'cgtm.type_name as course_group_type_master',
                DB::raw('COALESCE(
                    (SELECT GROUP_CONCAT(gcm.group_name ORDER BY gcm.group_name SEPARATOR \', \')
                     FROM group_type_master_course_master_map gcm
                     WHERE '.$groupMatchSql.'),
                    \'No Group Assigned\'
                ) AS group_name'),
                't.class_session',
                'vm.venue_name as venue_name',
                'sm.subject_name as subject_master_name',
                'smm.module_name as subject_module_name',
            ]);

        if ($courseId > 0) {
            if (! in_array($courseId, $accessibleCourseIds, true)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('t.course_master_pk', $courseId);
            }
        } else {
            $query->whereIn('t.course_master_pk', $accessibleCourseIds);
        }

        if (! empty($filters['faculty_id'])) {
            $fid = (int) $filters['faculty_id'];
            $query->whereRaw(
                '((JSON_VALID(t.faculty_master) AND JSON_CONTAINS(t.faculty_master, ?, \'$\'))
                OR (NOT JSON_VALID(t.faculty_master) AND TRIM(COALESCE(t.faculty_master, \'\')) <> \'\' AND CAST(t.faculty_master AS UNSIGNED) = ?))',
                [json_encode((int) $fid), $fid]
            );
        }

        if (! empty($filters['faculty_type'])) {
            $ftype = (int) $filters['faculty_type'];
            $query->whereRaw(
                'EXISTS (
                    SELECT 1 FROM faculty_master fmft
                    WHERE fmft.faculty_type = ?
                    AND (
                        (JSON_VALID(t.faculty_master) AND JSON_CONTAINS(t.faculty_master, CAST(fmft.pk AS CHAR), \'$\'))
                        OR (NOT JSON_VALID(t.faculty_master) AND TRIM(COALESCE(t.faculty_master, \'\')) <> \'\' AND CAST(t.faculty_master AS UNSIGNED) = fmft.pk)
                    )
                )',
                [$ftype]
            );
        }

        if (isset($filters['venue_id']) && $filters['venue_id'] !== '' && $filters['venue_id'] !== null) {
            $query->where('t.venue_id', $filters['venue_id']);
        }

        $topic = isset($filters['subject_topic']) ? trim((string) $filters['subject_topic']) : '';
        if ($topic !== '') {
            $like = '%' . addcslashes($topic, '%_\\') . '%';
            $query->where('t.subject_topic', 'like', $like);
        }

        $mod = isset($filters['subject_module']) ? trim((string) $filters['subject_module']) : '';
        if ($mod !== '') {
            $likeMod = '%' . addcslashes($mod, '%_\\') . '%';
            $query->where('smm.module_name', 'like', $likeMod);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('t.START_DATE', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('t.START_DATE', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function baseDatabaseQuery(Request $request)
    {
        $query = DB::table('topic_feedback as tf')
            ->select([
                'f.pk as faculty_id',
                'f.full_name as faculty_name',
                'f.email_id as faculty_email',
                DB::raw('IFNULL(f.Permanent_Address, "N/A") as faculty_address'),
                'c.course_name',
                't.subject_topic',

                DB::raw('ROUND(AVG(tf.content) * 20, 2) as avg_content_percent'),
                DB::raw('ROUND(AVG(tf.presentation) * 20, 2) as avg_presentation_percent'),

                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participant_count'),
                DB::raw('DATE(t.START_DATE) as session_date'),
                DB::raw('GROUP_CONCAT(DISTINCT tf.remark SEPARATOR " | ") as all_comments'),

                't.pk as timetable_pk'
            ])
            ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
            ->join('faculty_master as f', 't.faculty_master', '=', 'f.pk')
            ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
            ->where('t.course_master_pk', $request->course_id)
            ->where('t.START_DATE', '>=', Carbon::now()->subYears(2));

        /* 🔍 Filters */
        if ($request->search_param === 'faculty' && $request->filled('faculty_id')) {
            $query->where('f.pk', $request->faculty_id);
        }

        if ($request->search_param === 'topic' && $request->filled('topic_value')) {
            $query->where('t.subject_topic', 'like', "%{$request->topic_value}%");
        }

        $query->groupBy(
            'f.pk',
            'f.full_name',
            'f.email_id',
            'f.Permanent_Address',
            'c.course_name',
            't.subject_topic',
            't.START_DATE',
            't.pk'
        )
            ->orderBy('t.START_DATE', 'DESC');

        return $query;
    }


    public function getDatabaseData(Request $request)
    {
        try {
            /* ---------------- Validation ---------------- */
            $validated = $request->validate([
                'course_id'    => 'required|integer',
                'search_param' => 'nullable|string|in:all,faculty,topic',
                'faculty_id'   => 'nullable|integer',
                'topic_value'  => 'nullable|string',
                'per_page'     => 'nullable|integer',
                'page'         => 'nullable|integer',
            ]);

            /* ---------------- Base Query ---------------- */
            $query = $this->baseDatabaseQuery($request);

            /* ---------------- Pagination ---------------- */
            $perPage = $request->per_page ?? 10;
            $page    = $request->page ?? 1;

            // Total rows count
            $total = DB::table(DB::raw("({$query->toSql()}) as sub"))
                ->mergeBindings($query)
                ->count();

            // Fetch paginated data
            $data = $query
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            /* ---------------- Row Number + Encrypt ---------------- */
            $data->transform(function ($item, $index) use ($page, $perPage) {
                $item->row_num = (($page - 1) * $perPage) + $index + 1;
                $item->faculty_enc_id = encrypt($item->faculty_id);
                return $item;
            });

            /* ---------------- Response ---------------- */
            return response()->json([
                'success'      => true,
                'data'         => $data,
                'total'        => $total,
                'page'         => (int) $page,
                'per_page'     => (int) $perPage,
                'total_pages'  => ceil($total / $perPage),
            ]);
        } catch (\Exception $e) {

            \Log::error('Error in getDatabaseData', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Error loading feedback database data',
                'data'    => [],
                'total'   => 0
            ], 500);
        }
    }


    public function getTopicsForCourse(Request $request)
    {
        try {
            $request->validate([
                'course_id' => 'required|integer'
            ]);

            $topics = DB::table('timetable')
                ->where('course_master_pk', $request->course_id)
                ->whereNotNull('subject_topic')
                ->where('subject_topic', '!=', '')
                ->select('subject_topic')
                ->distinct()
                ->orderBy('subject_topic')
                ->get()
                ->pluck('subject_topic');

            return response()->json([
                'success' => true,
                'topics' => $topics
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getTopicsForCourse: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function exportDatabase(Request $request)
    {
        try {
            $validated = $request->validate([
                'course_id'    => 'required|integer',
                'export_type'  => 'required|in:excel,csv,pdf',
                'search_param' => 'nullable|string|in:all,faculty,topic',
                'faculty_id'   => 'nullable|integer',
                'topic_value'  => 'nullable|string',
            ]);

            $data = $this->baseDatabaseQuery($request)->get();

            $exportData = $data->map(function ($item, $index) {
                return [
                    'S.No.' => $index + 1,
                    'Faculty Name' => $item->faculty_name,
                    'Course Name' => $item->course_name,
                    'Faculty Address' => ($item->faculty_address ?? 'N/A') .
                        ($item->faculty_email ? "\n" . $item->faculty_email : ''),

                    'Topic' => $item->subject_topic,

                    'Content (%)' => number_format($item->avg_content_percent, 2),
                    'Presentation (%)' => number_format($item->avg_presentation_percent, 2),

                    'No. of Participants' => $item->participant_count,
                    'Session Date' => \Carbon\Carbon::parse($item->session_date)->format('d-m-Y'),
                    'Comments' => $item->all_comments ?: 'No comments',
                ];
            });

            return response()->json([
                'success'  => true,
                'data'     => $exportData,
                'filename' => 'feedback_database_' . now()->format('Y_m_d_H_i_s'),
            ]);
        } catch (\Exception $e) {

            \Log::error('Error in exportDatabase', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Export failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Shared payload for Feedback Database print / PDF / Excel (mess-style reports).
     */
    private function buildFeedbackDatabaseExportContext(Request $request): array
    {
        $request->validate([
            'course_id'    => 'required|integer',
            'search_param' => 'nullable|string|in:all,faculty,topic',
            'faculty_id'   => 'nullable|integer',
            'topic_value'  => 'nullable|string',
        ]);

        $data = $this->baseDatabaseQuery($request)->get();

        $program = DB::table('course_master')->where('pk', $request->course_id)->value('course_name') ?? '—';

        $scope = 'All records';
        if ($request->search_param === 'faculty' && $request->filled('faculty_id')) {
            $fname = DB::table('faculty_master')->where('pk', $request->faculty_id)->value('full_name');
            $scope = 'Faculty: ' . ($fname ?? '—');
        } elseif ($request->search_param === 'topic' && $request->filled('topic_value')) {
            $scope = 'Topic contains: ' . $request->topic_value;
        }

        $rows = [];
        foreach ($data as $index => $item) {
            $addr = $item->faculty_address ?? 'N/A';
            $emailLine = !empty($item->faculty_email) ? ("\n" . $item->faculty_email) : '';
            $rows[] = [
                's_no' => $index + 1,
                'faculty_name' => $item->faculty_name,
                'course_name' => $item->course_name,
                'faculty_address' => $addr . $emailLine,
                'topic' => $item->subject_topic ?? '—',
                'content_pct' => number_format((float) $item->avg_content_percent, 2),
                'presentation_pct' => number_format((float) $item->avg_presentation_percent, 2),
                'participants' => (int) $item->participant_count,
                'session_date' => Carbon::parse($item->session_date)->format('d-m-Y'),
                'comments' => $item->all_comments ? str_replace(' | ', "\n", $item->all_comments) : '—',
            ];
        }

        return [
            'rows' => $rows,
            'filters' => [
                'program' => $program,
                'scope' => $scope,
            ],
            'record_count' => count($rows),
            'export_date' => now()->format('d-m-Y H:i'),
        ];
    }

    public function printFeedbackDatabase(Request $request)
    {
        try {
            $ctx = $this->buildFeedbackDatabaseExportContext($request);

            return view('admin.feedback.feedback_database_export', array_merge($ctx, ['mode' => 'print']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.feedback.database')->with(
                'error',
                'Select a program on the Feedback Database page, then use Print, PDF, or Excel.'
            );
        } catch (\Exception $e) {
            \Log::error('printFeedbackDatabase: ' . $e->getMessage());

            return redirect()->route('admin.feedback.database')->with('error', 'Could not open the print view.');
        }
    }

    public function exportFeedbackDatabasePdf(Request $request)
    {
        try {
            $ctx = $this->buildFeedbackDatabaseExportContext($request);

            $pdf = Pdf::loadView('admin.feedback.feedback_database_export', array_merge($ctx, ['mode' => 'pdf']))
                ->setPaper('A4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'sans-serif',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'dpi' => 96,
                    'margin_top' => 8,
                    'margin_right' => 8,
                    'margin_bottom' => 8,
                    'margin_left' => 8,
                ]);

            return $pdf->download('feedback_database_' . date('Y-m-d_His') . '.pdf');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.feedback.database')->with(
                'error',
                'Select a program on the Feedback Database page, then download PDF again.'
            );
        } catch (\Exception $e) {
            \Log::error('exportFeedbackDatabasePdf: ' . $e->getMessage());

            return redirect()->route('admin.feedback.database')->with('error', 'PDF export failed.');
        }
    }

    public function exportFeedbackDatabaseExcel(Request $request)
    {
        try {
            $ctx = $this->buildFeedbackDatabaseExportContext($request);

            return Excel::download(
                new FeedbackDatabaseExport(
                    $ctx['rows'],
                    $ctx['filters'],
                    $ctx['export_date'],
                    $ctx['record_count']
                ),
                'feedback_database_' . now()->format('Y-m-d_H-i') . '.xlsx'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('admin.feedback.database')->with(
                'error',
                'Select a program on the Feedback Database page, then export Excel again.'
            );
        } catch (\Exception $e) {
            \Log::error('exportFeedbackDatabaseExcel: ' . $e->getMessage());

            return redirect()->route('admin.feedback.database')->with('error', 'Excel export failed.');
        }
    }


    // private function getDatabaseQuery(Request $request)
    // {
    //     $query = DB::table('topic_feedback as tf')
    //         ->select([
    //             'f.pk as faculty_id',
    //             'f.full_name as faculty_name',
    //             'f.email_id as faculty_email',
    //             DB::raw('IFNULL(f.Permanent_Address, "N/A") as faculty_address'),
    //             'c.course_name',
    //             't.subject_topic',
    //             DB::raw('AVG(tf.content) * 20 as avg_content_percent'),
    //             DB::raw('AVG(tf.presentation) * 20 as avg_presentation_percent'),
    //             DB::raw('COUNT(DISTINCT tf.student_master_pk) as participant_count'),
    //             DB::raw('DATE(t.START_DATE) as session_date'),
    //             DB::raw('GROUP_CONCAT(DISTINCT tf.remark SEPARATOR " | ") as all_comments')
    //         ])
    //         ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
    //         ->join('faculty_master as f', 't.faculty_master', '=', 'f.pk')
    //         ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
    //         ->where('t.course_master_pk', $request->course_id);

    //     // Apply filters
    //     if ($request->filled('search_param')) {
    //         if ($request->search_param === 'faculty' && $request->filled('faculty_id')) {
    //             $query->where('f.pk', $request->faculty_id);
    //         } elseif ($request->search_param === 'topic' && $request->filled('topic_value')) {
    //             $query->where('t.subject_topic', 'like', "%{$request->topic_value}%");
    //         }
    //     }

    //     $query->groupBy(
    //         'f.pk',
    //         'f.full_name',
    //         'f.email_id',
    //         'f.Residence_address',
    //         'f.Permanent_Address',
    //         'c.course_name',
    //         't.subject_topic',
    //         't.START_DATE'
    //     )
    //         ->orderBy('t.START_DATE', 'DESC');

    //     return $query;
    // }

    public function showFacultyAverage(Request $request)
    {
        // dd($request->all());
        $data_course_id =  get_Role_by_course();

        // Get filter parameters with defaults
        $programName = $request->input('program_name');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'current');

        // 1. Get programs from course_master table
        $currentDate = now()->toDateString();

        // Fetch programs based on selected course type
        $programsQuery = DB::table('course_master')
            ->where('active_inactive', 1)
            ->when($courseType === 'current', function ($q) use ($currentDate) {
                $q->where(function ($q2) use ($currentDate) {
                    $q2->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $currentDate);
                });
            })
            ->when($courseType === 'archived', function ($q) use ($currentDate) {
                $q->whereDate('end_date', '<', $currentDate);
            })
            ->when(!empty($data_course_id), function ($query) use ($data_course_id) {
                $query->whereIn('pk', $data_course_id);
            })
            ->orderBy('course_name');

        $programs = $programsQuery->pluck('course_name', 'pk');

        // Default fallback if no programs found
        if ($programs->isEmpty()) {
            $programs = collect([]); // Empty collection instead of dummy data
        }

        // 2. Get faculties from faculty_master
        $faculties = DB::table('faculty_master')
            ->select('full_name as name', 'pk')
            ->orderBy('full_name')
            ->pluck('name', 'pk');

        if ($faculties->isEmpty()) {
            // Fallback: Get faculty names from topic_feedback faculty_pk
            $faculties = DB::table('topic_feedback as tf')
                ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
                ->select('fm.full_name as name', 'fm.pk')
                ->distinct()
                ->orderBy('fm.full_name')
                ->pluck('name', 'fm.pk');
        }

        // Only set default program if programs exist and none selected
        // IMPORTANT FIX: Check if selected program exists in current programs list
        if (!$programName && !$programs->isEmpty()) {
            $programName = $programs->keys()->first();
        } elseif ($programName && !$programs->has($programName)) {
            // If selected program doesn't exist in current course type, reset it
            $programName = null;
        }

        // 3. Build the main query with CORRECT JOIN
        $query = DB::table('topic_feedback as tf')
            ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
            ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
            ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
            ->select(
                'tf.faculty_pk',
                'fm.full_name as faculty_name',
                'tf.topic_name',
                'cm.course_name as program_name',
                'tt.START_DATE as session_date',
                'tt.class_session',
                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participants'),
                // Presentation rating counts
                DB::raw('SUM(CASE WHEN tf.presentation = "5" THEN 1 ELSE 0 END) as presentation_5'),
                DB::raw('SUM(CASE WHEN tf.presentation = "4" THEN 1 ELSE 0 END) as presentation_4'),
                DB::raw('SUM(CASE WHEN tf.presentation = "3" THEN 1 ELSE 0 END) as presentation_3'),
                DB::raw('SUM(CASE WHEN tf.presentation = "2" THEN 1 ELSE 0 END) as presentation_2'),
                DB::raw('SUM(CASE WHEN tf.presentation = "1" THEN 1 ELSE 0 END) as presentation_1'),
                // Content rating counts
                DB::raw('SUM(CASE WHEN tf.content = "5" THEN 1 ELSE 0 END) as content_5'),
                DB::raw('SUM(CASE WHEN tf.content = "4" THEN 1 ELSE 0 END) as content_4'),
                DB::raw('SUM(CASE WHEN tf.content = "3" THEN 1 ELSE 0 END) as content_3'),
                DB::raw('SUM(CASE WHEN tf.content = "2" THEN 1 ELSE 0 END) as content_2'),
                DB::raw('SUM(CASE WHEN tf.content = "1" THEN 1 ELSE 0 END) as content_1')
            )
            ->where('tf.is_submitted', 1)
            ->whereNotNull('tf.presentation')
            ->whereNotNull('tf.content')
            ->groupBy('tf.faculty_pk', 'tf.topic_name', 'cm.course_name', 'fm.full_name', 'tt.START_DATE', 'tt.class_session');

        // Apply filters
        if (!empty($programName)) {
            $query->where('cm.pk', $programName);
        }

        $currentProgramName = null;
        if (!empty($programName) && $programs->has($programName)) {
            $currentProgramName = $programs[$programName];
        }

        if ($facultyName && $facultyName !== 'All Faculty') {
            $query->where('tf.faculty_pk', $facultyName);
        }

        if ($fromDate) {
            $query->whereDate('tf.created_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('tf.created_date', '<=', $toDate);
        }

        // IMPORTANT FIX: ALWAYS apply course type filter to main query
        if ($courseType === 'archived') {
            $query->whereDate('cm.end_date', '<', Carbon::today());
        } else { // current or default
            $query->where(function ($q) {
                $q->whereNull('cm.end_date')
                    ->orWhereDate('cm.end_date', '>=', Carbon::today());
            });
        }

        // Execute query
        $feedbackData = $query->get();

        // 4. Process each record to calculate percentages
        $processedData = $feedbackData->map(function ($item) {
            // IMPORTANT: Convert counts to integers since they're from SUM() functions
            $presentation_5 = (int)$item->presentation_5;
            $presentation_4 = (int)$item->presentation_4;
            $presentation_3 = (int)$item->presentation_3;
            $presentation_2 = (int)$item->presentation_2;
            $presentation_1 = (int)$item->presentation_1;

            $content_5 = (int)$item->content_5;
            $content_4 = (int)$item->content_4;
            $content_3 = (int)$item->content_3;
            $content_2 = (int)$item->content_2;
            $content_1 = (int)$item->content_1;

            // Calculate presentation percentage
            $presentationWeightedSum =
                (5 * $presentation_5) +
                (4 * $presentation_4) +
                (3 * $presentation_3) +
                (2 * $presentation_2) +
                (1 * $presentation_1);

            $presentationTotal =
                $presentation_5 + $presentation_4 +
                $presentation_3 + $presentation_2 +
                $presentation_1;

            // dynamic max rating used
            $presentationMaxRating = 0;
            if ($presentation_5 > 0) $presentationMaxRating = 5;
            elseif ($presentation_4 > 0) $presentationMaxRating = 4;
            elseif ($presentation_3 > 0) $presentationMaxRating = 3;
            elseif ($presentation_2 > 0) $presentationMaxRating = 2;
            elseif ($presentation_1 > 0) $presentationMaxRating = 1;

            $presentationPercentage = ($presentationTotal > 0 && $presentationMaxRating > 0)
                ? round(($presentationWeightedSum / ($presentationTotal * $presentationMaxRating)) * 100, 2)
                : 0;

            $contentWeightedSum =
                (5 * $content_5) +
                (4 * $content_4) +
                (3 * $content_3) +
                (2 * $content_2) +
                (1 * $content_1);

            $contentTotal =
                $content_5 + $content_4 +
                $content_3 + $content_2 +
                $content_1;

            $contentMaxRating = 0;
            if ($content_5 > 0) $contentMaxRating = 5;
            elseif ($content_4 > 0) $contentMaxRating = 4;
            elseif ($content_3 > 0) $contentMaxRating = 3;
            elseif ($content_2 > 0) $contentMaxRating = 2;
            elseif ($content_1 > 0) $contentMaxRating = 1;

            $contentPercentage = ($contentTotal > 0 && $contentMaxRating > 0)
                ? round(($contentWeightedSum / ($contentTotal * $contentMaxRating)) * 100, 2)
                : 0;

            return [
                'faculty_pk' => $item->faculty_pk,
                'faculty_name' => $item->faculty_name,
                'topic_name' => $item->topic_name,
                'program_name' => $item->program_name,
                'participants' => (int)$item->participants,
                'presentation_percentage' => $presentationPercentage,
                'content_percentage' => $contentPercentage,
                'session_date' => $item->session_date,
                'class_session' => $item->class_session,
                'presentation_counts' => [
                    '5' => $presentation_5,
                    '4' => $presentation_4,
                    '3' => $presentation_3,
                    '2' => $presentation_2,
                    '1' => $presentation_1,
                ],
                'content_counts' => [
                    '5' => $content_5,
                    '4' => $content_4,
                    '3' => $content_3,
                    '2' => $content_2,
                    '1' => $content_1,
                ]
            ];
        });

        return view('admin.feedback.faculty_average', [
            'feedbackData' => $processedData,
            'programs' => $programs,
            'faculties' => $faculties,
            'currentProgram' => $programName,
            'currentProgramName' => $currentProgramName,
            'currentFaculty' => $facultyName,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'courseType' => $courseType,
            'refreshTime' => now()->format('d-M-Y H:i'),
        ]);
    }



    // Add this method for Excel export
    public function exportExcel(Request $request)
    {
        // Get filter parameters
        $data_course_id = get_Role_by_course();

        $programName = $request->input('program_name');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'current');

        $currentDate = now()->toDateString();

        // Get programs for filter display
        $programsQuery = DB::table('course_master')
            ->where('active_inactive', 1)
            ->when($courseType === 'current', function ($q) use ($currentDate) {
                $q->where(function ($q2) use ($currentDate) {
                    $q2->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $currentDate);
                });
            })
            ->when($courseType === 'archived', function ($q) use ($currentDate) {
                $q->whereDate('end_date', '<', $currentDate);
            })
            ->when(!empty($data_course_id), function ($query) use ($data_course_id) {
                $query->whereIn('pk', $data_course_id);
            })
            ->orderBy('course_name');

        $programs = $programsQuery->pluck('course_name', 'pk');

        // Get faculties
        $faculties = DB::table('faculty_master')
            ->select('full_name as name', 'pk')
            ->orderBy('full_name')
            ->pluck('name', 'pk');

        // Build the query
        $query = DB::table('topic_feedback as tf')
            ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
            ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
            ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
            ->select(
                'tf.faculty_pk',
                'fm.full_name as faculty_name',
                'tf.topic_name',
                'cm.course_name as program_name',
                'tt.START_DATE as session_date',
                'tt.class_session',
                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participants'),
                DB::raw('SUM(CASE WHEN tf.presentation = "5" THEN 1 ELSE 0 END) as presentation_5'),
                DB::raw('SUM(CASE WHEN tf.presentation = "4" THEN 1 ELSE 0 END) as presentation_4'),
                DB::raw('SUM(CASE WHEN tf.presentation = "3" THEN 1 ELSE 0 END) as presentation_3'),
                DB::raw('SUM(CASE WHEN tf.presentation = "2" THEN 1 ELSE 0 END) as presentation_2'),
                DB::raw('SUM(CASE WHEN tf.presentation = "1" THEN 1 ELSE 0 END) as presentation_1'),
                DB::raw('SUM(CASE WHEN tf.content = "5" THEN 1 ELSE 0 END) as content_5'),
                DB::raw('SUM(CASE WHEN tf.content = "4" THEN 1 ELSE 0 END) as content_4'),
                DB::raw('SUM(CASE WHEN tf.content = "3" THEN 1 ELSE 0 END) as content_3'),
                DB::raw('SUM(CASE WHEN tf.content = "2" THEN 1 ELSE 0 END) as content_2'),
                DB::raw('SUM(CASE WHEN tf.content = "1" THEN 1 ELSE 0 END) as content_1')
            )
            ->where('tf.is_submitted', 1)
            ->whereNotNull('tf.presentation')
            ->whereNotNull('tf.content')
            ->groupBy('tf.faculty_pk', 'tf.topic_name', 'cm.course_name', 'fm.full_name', 'tt.START_DATE', 'tt.class_session');

        // Apply filters
        if (!empty($programName)) {
            $query->where('cm.pk', $programName);
        }

        if ($facultyName && $facultyName !== 'All Faculty') {
            $query->where('tf.faculty_pk', $facultyName);
        }

        if ($fromDate) {
            $query->whereDate('tf.created_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('tf.created_date', '<=', $toDate);
        }

        // Apply course type filter
        if ($courseType === 'archived') {
            $query->whereDate('cm.end_date', '<', $currentDate);
        } else {
            $query->where(function ($q) use ($currentDate) {
                $q->whereNull('cm.end_date')
                    ->orWhereDate('cm.end_date', '>=', $currentDate);
            });
        }

        // Get data
        $feedbackData = $query->get();

        // Process the data
        $processedData = $feedbackData->map(function ($item) {
            $presentation_5 = (int)$item->presentation_5;
            $presentation_4 = (int)$item->presentation_4;
            $presentation_3 = (int)$item->presentation_3;
            $presentation_2 = (int)$item->presentation_2;
            $presentation_1 = (int)$item->presentation_1;
            $content_5 = (int)$item->content_5;
            $content_4 = (int)$item->content_4;
            $content_3 = (int)$item->content_3;
            $content_2 = (int)$item->content_2;
            $content_1 = (int)$item->content_1;

            // Calculate presentation percentage
            $presentationWeightedSum = (5 * $presentation_5) + (4 * $presentation_4) + (3 * $presentation_3) + (2 * $presentation_2) + (1 * $presentation_1);
            $presentationTotal = $presentation_5 + $presentation_4 + $presentation_3 + $presentation_2 + $presentation_1;

            $presentationMaxRating = 0;
            if ($presentation_5 > 0) $presentationMaxRating = 5;
            elseif ($presentation_4 > 0) $presentationMaxRating = 4;
            elseif ($presentation_3 > 0) $presentationMaxRating = 3;
            elseif ($presentation_2 > 0) $presentationMaxRating = 2;
            elseif ($presentation_1 > 0) $presentationMaxRating = 1;

            $presentationPercentage = ($presentationTotal > 0 && $presentationMaxRating > 0)
                ? round(($presentationWeightedSum / ($presentationTotal * $presentationMaxRating)) * 100, 2)
                : 0;

            // Calculate content percentage
            $contentWeightedSum = (5 * $content_5) + (4 * $content_4) + (3 * $content_3) + (2 * $content_2) + (1 * $content_1);
            $contentTotal = $content_5 + $content_4 + $content_3 + $content_2 + $content_1;

            $contentMaxRating = 0;
            if ($content_5 > 0) $contentMaxRating = 5;
            elseif ($content_4 > 0) $contentMaxRating = 4;
            elseif ($content_3 > 0) $contentMaxRating = 3;
            elseif ($content_2 > 0) $contentMaxRating = 2;
            elseif ($content_1 > 0) $contentMaxRating = 1;

            $contentPercentage = ($contentTotal > 0 && $contentMaxRating > 0)
                ? round(($contentWeightedSum / ($contentTotal * $contentMaxRating)) * 100, 2)
                : 0;

            return [
                'faculty_pk' => $item->faculty_pk,
                'faculty_name' => $item->faculty_name,
                'topic_name' => $item->topic_name,
                'program_name' => $item->program_name,
                'participants' => (int)$item->participants,
                'presentation_percentage' => $presentationPercentage,
                'content_percentage' => $contentPercentage,
                'session_date' => $item->session_date,
                'class_session' => $item->class_session,
            ];
        });

        $filters = [
            'program_name' => $programName,
            'faculty_name' => $facultyName,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'course_type' => $courseType,
        ];

        $filename = 'faculty_feedback_average_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new FacultyFeedback_AvgExport($filters, $processedData, $programs, $faculties), $filename);
    }

    // Add this method for PDF export
    public function exportPdf(Request $request)
    {
        // Get filter parameters
        $data_course_id = get_Role_by_course();

        $programName = $request->input('program_name');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'current');

        $currentDate = now()->toDateString();

        // 1. Get programs for filter display (same as showFacultyAverage)
        $programsQuery = DB::table('course_master')
            ->where('active_inactive', 1)
            ->when($courseType === 'current', function ($q) use ($currentDate) {
                $q->where(function ($q2) use ($currentDate) {
                    $q2->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $currentDate);
                });
            })
            ->when($courseType === 'archived', function ($q) use ($currentDate) {
                $q->whereDate('end_date', '<', $currentDate);
            })
            ->when(!empty($data_course_id), function ($query) use ($data_course_id) {
                $query->whereIn('pk', $data_course_id);
            })
            ->orderBy('course_name');

        $programs = $programsQuery->pluck('course_name', 'pk');

        // 2. Get faculties (same as showFacultyAverage)
        $faculties = DB::table('faculty_master')
            ->select('full_name as name', 'pk')
            ->orderBy('full_name')
            ->pluck('name', 'pk');

        if ($faculties->isEmpty()) {
            $faculties = DB::table('topic_feedback as tf')
                ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
                ->select('fm.full_name as name', 'fm.pk')
                ->distinct()
                ->orderBy('fm.full_name')
                ->pluck('name', 'fm.pk');
        }

        // 3. Build the main query (EXACT same as showFacultyAverage)
        $query = DB::table('topic_feedback as tf')
            ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
            ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
            ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
            ->select(
                'tf.faculty_pk',
                'fm.full_name as faculty_name',
                'tf.topic_name',
                'cm.course_name as program_name',
                'tt.START_DATE as session_date',
                'tt.class_session',
                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participants'),
                // Presentation rating counts
                DB::raw('SUM(CASE WHEN tf.presentation = "5" THEN 1 ELSE 0 END) as presentation_5'),
                DB::raw('SUM(CASE WHEN tf.presentation = "4" THEN 1 ELSE 0 END) as presentation_4'),
                DB::raw('SUM(CASE WHEN tf.presentation = "3" THEN 1 ELSE 0 END) as presentation_3'),
                DB::raw('SUM(CASE WHEN tf.presentation = "2" THEN 1 ELSE 0 END) as presentation_2'),
                DB::raw('SUM(CASE WHEN tf.presentation = "1" THEN 1 ELSE 0 END) as presentation_1'),
                // Content rating counts
                DB::raw('SUM(CASE WHEN tf.content = "5" THEN 1 ELSE 0 END) as content_5'),
                DB::raw('SUM(CASE WHEN tf.content = "4" THEN 1 ELSE 0 END) as content_4'),
                DB::raw('SUM(CASE WHEN tf.content = "3" THEN 1 ELSE 0 END) as content_3'),
                DB::raw('SUM(CASE WHEN tf.content = "2" THEN 1 ELSE 0 END) as content_2'),
                DB::raw('SUM(CASE WHEN tf.content = "1" THEN 1 ELSE 0 END) as content_1')
            )
            ->where('tf.is_submitted', 1)
            ->whereNotNull('tf.presentation')
            ->whereNotNull('tf.content')
            ->groupBy('tf.faculty_pk', 'tf.topic_name', 'cm.course_name', 'fm.full_name', 'tt.START_DATE', 'tt.class_session');

        // Apply filters (EXACT same as showFacultyAverage)
        if (!empty($programName)) {
            $query->where('cm.pk', $programName);
        }

        $currentProgramName = null;
        if (!empty($programName) && $programs->has($programName)) {
            $currentProgramName = $programs[$programName];
        }

        if ($facultyName && $facultyName !== 'All Faculty') {
            $query->where('tf.faculty_pk', $facultyName);
        }

        if ($fromDate) {
            $query->whereDate('tf.created_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('tf.created_date', '<=', $toDate);
        }

        // Apply course type filter (EXACT same as showFacultyAverage)
        if ($courseType === 'archived') {
            $query->whereDate('cm.end_date', '<', Carbon::today());
        } else { // current or default
            $query->where(function ($q) {
                $q->whereNull('cm.end_date')
                    ->orWhereDate('cm.end_date', '>=', Carbon::today());
            });
        }

        // Execute query
        $feedbackData = $query->get();

        // Process each record to calculate percentages (EXACT same as showFacultyAverage)
        $processedData = $feedbackData->map(function ($item) {
            $presentation_5 = (int)$item->presentation_5;
            $presentation_4 = (int)$item->presentation_4;
            $presentation_3 = (int)$item->presentation_3;
            $presentation_2 = (int)$item->presentation_2;
            $presentation_1 = (int)$item->presentation_1;

            $content_5 = (int)$item->content_5;
            $content_4 = (int)$item->content_4;
            $content_3 = (int)$item->content_3;
            $content_2 = (int)$item->content_2;
            $content_1 = (int)$item->content_1;

            // Calculate presentation percentage
            $presentationWeightedSum =
                (5 * $presentation_5) +
                (4 * $presentation_4) +
                (3 * $presentation_3) +
                (2 * $presentation_2) +
                (1 * $presentation_1);

            $presentationTotal =
                $presentation_5 + $presentation_4 +
                $presentation_3 + $presentation_2 +
                $presentation_1;

            // dynamic max rating used
            $presentationMaxRating = 0;
            if ($presentation_5 > 0) $presentationMaxRating = 5;
            elseif ($presentation_4 > 0) $presentationMaxRating = 4;
            elseif ($presentation_3 > 0) $presentationMaxRating = 3;
            elseif ($presentation_2 > 0) $presentationMaxRating = 2;
            elseif ($presentation_1 > 0) $presentationMaxRating = 1;

            $presentationPercentage = ($presentationTotal > 0 && $presentationMaxRating > 0)
                ? round(($presentationWeightedSum / ($presentationTotal * $presentationMaxRating)) * 100, 2)
                : 0;

            $contentWeightedSum =
                (5 * $content_5) +
                (4 * $content_4) +
                (3 * $content_3) +
                (2 * $content_2) +
                (1 * $content_1);

            $contentTotal =
                $content_5 + $content_4 +
                $content_3 + $content_2 +
                $content_1;

            $contentMaxRating = 0;
            if ($content_5 > 0) $contentMaxRating = 5;
            elseif ($content_4 > 0) $contentMaxRating = 4;
            elseif ($content_3 > 0) $contentMaxRating = 3;
            elseif ($content_2 > 0) $contentMaxRating = 2;
            elseif ($content_1 > 0) $contentMaxRating = 1;

            $contentPercentage = ($contentTotal > 0 && $contentMaxRating > 0)
                ? round(($contentWeightedSum / ($contentTotal * $contentMaxRating)) * 100, 2)
                : 0;

            return [
                'faculty_name' => $item->faculty_name,
                'topic_name' => $item->topic_name,
                'program_name' => $item->program_name,
                'participants' => (int)$item->participants,
                'presentation_percentage' => $presentationPercentage,
                'content_percentage' => $contentPercentage,
                'session_date' => $item->session_date,
                'class_session' => $item->class_session,
            ];
        });

        // Prepare data for PDF view
        $data = [
            'feedbackData' => $processedData,
            'programs' => $programs,
            'faculties' => $faculties,
            'currentProgram' => $programName,
            'currentProgramName' => $currentProgramName,
            'currentFaculty' => $facultyName,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'courseType' => $courseType,
        ];

        $pdf = Pdf::loadView('admin.feedback.faculty_average_export', $data);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'faculty_feedback_average_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Print Faculty Feedback Average – opens LBSNAA-themed view in a new tab.
     */
    public function printFacultyAverage(Request $request)
    {
        try {
            $data_course_id = get_Role_by_course();
            $programName = $request->input('program_name');
            $facultyName = $request->input('faculty_name');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $courseType = $request->input('course_type', 'current');
            $currentDate = now()->toDateString();

            $programsQuery = DB::table('course_master')
                ->where('active_inactive', 1)
                ->when($courseType === 'current', fn($q) => $q->where(fn($q2) => $q2->whereNull('end_date')->orWhereDate('end_date', '>=', $currentDate)))
                ->when($courseType === 'archived', fn($q) => $q->whereDate('end_date', '<', $currentDate))
                ->when(!empty($data_course_id), fn($q) => $q->whereIn('pk', $data_course_id))
                ->orderBy('course_name');
            $programs = $programsQuery->pluck('course_name', 'pk');

            $faculties = DB::table('faculty_master')->select('full_name as name', 'pk')->orderBy('full_name')->pluck('name', 'pk');
            if ($faculties->isEmpty()) {
                $faculties = DB::table('topic_feedback as tf')
                    ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
                    ->select('fm.full_name as name', 'fm.pk')->distinct()->orderBy('fm.full_name')->pluck('name', 'fm.pk');
            }

            $query = DB::table('topic_feedback as tf')
                ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
                ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
                ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
                ->select(
                    'tf.faculty_pk', 'fm.full_name as faculty_name', 'tf.topic_name',
                    'cm.course_name as program_name', 'tt.START_DATE as session_date', 'tt.class_session',
                    DB::raw('COUNT(DISTINCT tf.student_master_pk) as participants'),
                    DB::raw('SUM(CASE WHEN tf.presentation = "5" THEN 1 ELSE 0 END) as presentation_5'),
                    DB::raw('SUM(CASE WHEN tf.presentation = "4" THEN 1 ELSE 0 END) as presentation_4'),
                    DB::raw('SUM(CASE WHEN tf.presentation = "3" THEN 1 ELSE 0 END) as presentation_3'),
                    DB::raw('SUM(CASE WHEN tf.presentation = "2" THEN 1 ELSE 0 END) as presentation_2'),
                    DB::raw('SUM(CASE WHEN tf.presentation = "1" THEN 1 ELSE 0 END) as presentation_1'),
                    DB::raw('SUM(CASE WHEN tf.content = "5" THEN 1 ELSE 0 END) as content_5'),
                    DB::raw('SUM(CASE WHEN tf.content = "4" THEN 1 ELSE 0 END) as content_4'),
                    DB::raw('SUM(CASE WHEN tf.content = "3" THEN 1 ELSE 0 END) as content_3'),
                    DB::raw('SUM(CASE WHEN tf.content = "2" THEN 1 ELSE 0 END) as content_2'),
                    DB::raw('SUM(CASE WHEN tf.content = "1" THEN 1 ELSE 0 END) as content_1')
                )
                ->where('tf.is_submitted', 1)->whereNotNull('tf.presentation')->whereNotNull('tf.content')
                ->groupBy('tf.faculty_pk', 'tf.topic_name', 'cm.course_name', 'fm.full_name', 'tt.START_DATE', 'tt.class_session');

            if (!empty($programName)) $query->where('cm.pk', $programName);
            $currentProgramName = (!empty($programName) && $programs->has($programName)) ? $programs[$programName] : null;
            if ($facultyName && $facultyName !== 'All Faculty') $query->where('tf.faculty_pk', $facultyName);
            if ($fromDate) $query->whereDate('tf.created_date', '>=', $fromDate);
            if ($toDate) $query->whereDate('tf.created_date', '<=', $toDate);
            if ($courseType === 'archived') {
                $query->whereDate('cm.end_date', '<', Carbon::today());
            } else {
                $query->where(fn($q) => $q->whereNull('cm.end_date')->orWhereDate('cm.end_date', '>=', Carbon::today()));
            }

            $feedbackData = $query->get();

            $processedData = $feedbackData->map(function ($item) {
                $p5=(int)$item->presentation_5; $p4=(int)$item->presentation_4; $p3=(int)$item->presentation_3;
                $p2=(int)$item->presentation_2; $p1=(int)$item->presentation_1;
                $c5=(int)$item->content_5; $c4=(int)$item->content_4; $c3=(int)$item->content_3;
                $c2=(int)$item->content_2; $c1=(int)$item->content_1;
                $pT=$p5+$p4+$p3+$p2+$p1; $pW=5*$p5+4*$p4+3*$p3+2*$p2+1*$p1;
                $pM=$p5>0?5:($p4>0?4:($p3>0?3:($p2>0?2:($p1>0?1:0))));
                $pP=($pT>0&&$pM>0)?round(($pW/($pT*$pM))*100,2):0;
                $cT=$c5+$c4+$c3+$c2+$c1; $cW=5*$c5+4*$c4+3*$c3+2*$c2+1*$c1;
                $cM=$c5>0?5:($c4>0?4:($c3>0?3:($c2>0?2:($c1>0?1:0))));
                $cP=($cT>0&&$cM>0)?round(($cW/($cT*$cM))*100,2):0;
                return [
                    'faculty_name'=>$item->faculty_name, 'topic_name'=>$item->topic_name,
                    'program_name'=>$item->program_name, 'participants'=>(int)$item->participants,
                    'presentation_percentage'=>$pP, 'content_percentage'=>$cP,
                    'session_date'=>$item->session_date, 'class_session'=>$item->class_session,
                ];
            });

            return view('admin.feedback.faculty_average_export', [
                'feedbackData' => $processedData,
                'programs' => $programs,
                'faculties' => $faculties,
                'currentProgram' => $programName,
                'currentProgramName' => $currentProgramName,
                'currentFaculty' => $facultyName,
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'courseType' => $courseType,
                'mode' => 'print',
            ]);
        } catch (\Exception $e) {
            \Log::error('Faculty Average Print Error: ' . $e->getMessage());
            return back()->with('error', 'Error generating print view: ' . $e->getMessage());
        }
    }



    public function facultyView(Request $request)
    {
        // Handle POST requests (form submissions)
        if ($request->isMethod('post')) {
            // Get all parameters from POST
            $programId = $request->input('program_id');
            $facultyName = $request->input('faculty_name');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $courseType = $request->input('course_type', 'current');
            $facultyType = $request->input('faculty_type', []);
            $page = $request->input('page', 1);
        } else {
            // GET request (initial load or refresh)
            $programId = $request->input('program_id', '');
            $facultyName = $request->input('faculty_name', '');
            $fromDate = $request->input('from_date', '');
            $toDate = $request->input('to_date', '');
            $courseType = $request->input('course_type', 'current');
            $facultyType = $request->input('faculty_type', []);
            $page = $request->input('page', 1);
        }

        // Ensure faculty_type is always an array
        if (is_string($facultyType)) {
            $facultyType = [$facultyType];
        }
        $data_course_id =  get_Role_by_course();

        // Get programs based on course type - THIS MUST BE OUTSIDE THE IF/ELSE
        $programsQuery = DB::table('course_master')
            ->select('pk as id', 'course_name', 'active_inactive', 'start_year', 'end_date');

        if ($courseType === 'current') {
            $programsQuery->where('active_inactive', 1)
                ->whereDate('end_date', '>=', Carbon::today());
        } else {
            $programsQuery->where(function ($query) {
                $query->where('active_inactive', 0)
                    ->orWhereDate('end_date', '<', Carbon::today());
            });
        }
        if (!empty($data_course_id)) {
            $programsQuery->whereIn('pk', $data_course_id);
        }

        $programs = $programsQuery->orderBy('course_name')
            ->pluck('course_name', 'id');

        if ($programs->isEmpty()) {
            $programs = collect([]);
        }

        // Define faculty types for filter checkboxes
        $facultyTypes = [
            '2' => 'Guest',
            '1' => 'Internal',
        ];

        // Get faculty suggestions - THIS ALSO MUST BE OUTSIDE THE IF/ELSE
        $facultySuggestions = collect();
        if (!empty($facultyType)) {
            $facultyQuery = DB::table('faculty_master')
                ->select('full_name', 'pk', 'faculty_type')
                ->whereIn('faculty_type', $facultyType)
                ->whereNotNull('full_name')
                ->orderBy('full_name');

            if ($facultyName) {
                $facultyQuery->where('full_name', 'LIKE', '%' . $facultyName . '%');
            }

            $facultySuggestions = $facultyQuery->limit(10)->get();
        }

        // Build query for detailed feedback data - ADD class_session TO SELECT
        $query = DB::table('topic_feedback as tf')
            ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
            ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
            ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
            ->select(
                'tf.topic_name',
                'cm.pk as program_id',
                'cm.course_name as program_name',
                'cm.active_inactive as program_status',
                'cm.end_date as program_end_date',
                'fm.full_name as faculty_name',
                'fm.faculty_type',
                'tf.faculty_pk',
                'tt.START_DATE',
                'tt.END_DATE',
                'tt.class_session', // ADD THIS - session time column
                'tf.timetable_pk',
                DB::raw('SUM(CASE WHEN tf.content = "5" THEN 1 ELSE 0 END) as content_5'),
                DB::raw('SUM(CASE WHEN tf.content = "4" THEN 1 ELSE 0 END) as content_4'),
                DB::raw('SUM(CASE WHEN tf.content = "3" THEN 1 ELSE 0 END) as content_3'),
                DB::raw('SUM(CASE WHEN tf.content = "2" THEN 1 ELSE 0 END) as content_2'),
                DB::raw('SUM(CASE WHEN tf.content = "1" THEN 1 ELSE 0 END) as content_1'),
                DB::raw('SUM(CASE WHEN tf.presentation = "5" THEN 1 ELSE 0 END) as presentation_5'),
                DB::raw('SUM(CASE WHEN tf.presentation = "4" THEN 1 ELSE 0 END) as presentation_4'),
                DB::raw('SUM(CASE WHEN tf.presentation = "3" THEN 1 ELSE 0 END) as presentation_3'),
                DB::raw('SUM(CASE WHEN tf.presentation = "2" THEN 1 ELSE 0 END) as presentation_2'),
                DB::raw('SUM(CASE WHEN tf.presentation = "1" THEN 1 ELSE 0 END) as presentation_1'),
                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participants'),
                DB::raw('GROUP_CONCAT(DISTINCT CASE 
                WHEN tf.remark IS NOT NULL 
                AND TRIM(tf.remark) != "" 
                THEN tf.remark 
                ELSE NULL 
             END SEPARATOR "|||") as remarks')
            );
        $query->where('tf.is_submitted', 1);
        if (!empty($data_course_id)) {
            $query->whereIn('cm.pk', $data_course_id);
        }

        // Group by - ADD class_session to group by
        $query->groupBy('tf.topic_name', 'cm.pk', 'cm.course_name', 'cm.active_inactive', 'cm.end_date', 'fm.full_name', 'fm.faculty_type', 'tf.faculty_pk', 'tt.START_DATE', 'tt.END_DATE', 'tt.class_session', 'tf.timetable_pk');

        // Apply filters
        if ($programId && $programId !== '') {
            $query->where('cm.pk', $programId);
        }

        if ($facultyName && $facultyName !== 'All Faculty') {
            $query->where('fm.full_name', 'LIKE', '%' . $facultyName . '%');
        }

        if (!empty($facultyType)) {
            $query->whereIn('fm.faculty_type', $facultyType);
        }

        if ($fromDate) {
            $query->whereDate('tt.START_DATE', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('tt.END_DATE', '<=', $toDate);
        }

        // Course type filter
        if ($courseType === 'archived') {
            $query->where(function ($q) {
                $q->where('cm.active_inactive', 0)
                    ->orWhereDate('cm.end_date', '<', Carbon::today());
            });
        } elseif ($courseType === 'current') {
            $query->where('cm.active_inactive', 1)
                ->whereDate('cm.end_date', '>=', Carbon::today());
        }

        // ADD ORDER BY for date and session time - UPDATED
        $query->orderBy('tt.START_DATE', 'asc')
            ->orderByRaw("
              CASE 
                  WHEN tt.class_session LIKE '%AM%' THEN 1
                  WHEN tt.class_session LIKE '%PM%' THEN 2
                  WHEN tt.class_session REGEXP '^[0-9]' THEN 3
                  ELSE 4
              END,
              tt.class_session ASC
          ");

        // Increase GROUP_CONCAT max length BEFORE getting data
        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        // Get the data
        $feedbackData = $query->get();

        // Process ALL data for display
        $processedData = $feedbackData->map(function ($item) {
            // Convert to integers
            $content_5 = (int)$item->content_5;
            $content_4 = (int)$item->content_4;
            $content_3 = (int)$item->content_3;
            $content_2 = (int)$item->content_2;
            $content_1 = (int)$item->content_1;

            $presentation_5 = (int)$item->presentation_5;
            $presentation_4 = (int)$item->presentation_4;
            $presentation_3 = (int)$item->presentation_3;
            $presentation_2 = (int)$item->presentation_2;
            $presentation_1 = (int)$item->presentation_1;

            $contentWeightedSum =
                (5 * $content_5) +
                (4 * $content_4) +
                (3 * $content_3) +
                (2 * $content_2) +
                (1 * $content_1);

            $contentTotal =
                $content_5 + $content_4 + $content_3 + $content_2 + $content_1;

            // find max rating actually used
            $contentMaxRating = 0;
            if ($content_5 > 0) $contentMaxRating = 5;
            elseif ($content_4 > 0) $contentMaxRating = 4;
            elseif ($content_3 > 0) $contentMaxRating = 3;
            elseif ($content_2 > 0) $contentMaxRating = 2;
            elseif ($content_1 > 0) $contentMaxRating = 1;

            $contentPercentage = ($contentTotal > 0 && $contentMaxRating > 0)
                ? round(($contentWeightedSum / ($contentTotal * $contentMaxRating)) * 100, 2)
                : 0;

            $presentationWeightedSum =
                (5 * $presentation_5) +
                (4 * $presentation_4) +
                (3 * $presentation_3) +
                (2 * $presentation_2) +
                (1 * $presentation_1);

            $presentationTotal =
                $presentation_5 + $presentation_4 + $presentation_3 + $presentation_2 + $presentation_1;

            // find max rating actually used
            $presentationMaxRating = 0;
            if ($presentation_5 > 0) $presentationMaxRating = 5;
            elseif ($presentation_4 > 0) $presentationMaxRating = 4;
            elseif ($presentation_3 > 0) $presentationMaxRating = 3;
            elseif ($presentation_2 > 0) $presentationMaxRating = 2;
            elseif ($presentation_1 > 0) $presentationMaxRating = 1;

            $presentationPercentage = ($presentationTotal > 0 && $presentationMaxRating > 0)
                ? round(($presentationWeightedSum / ($presentationTotal * $presentationMaxRating)) * 100, 2)
                : 0;

            // Process remarks
            // Process remarks - FIXED: Filter out empty/dot remarks
            $remarks = [];
            if (!empty($item->remarks)) {
                $rawRemarks = explode('|||', $item->remarks);
                $remarks = array_filter(array_map('trim', $rawRemarks), function ($remark) {
                    // Filter out empty remarks, single dots, and common placeholders
                    $remark = trim($remark);
                    return !empty($remark) &&
                        $remark !== '.' &&
                        $remark !== '..' &&
                        $remark !== '...' &&
                        $remark !== '-' &&
                        $remark !== '--' &&
                        strlen($remark) > 1; // At least 2 characters
                });
                $remarks = array_unique($remarks);

                // Sort remarks alphabetically or keep in natural order
                sort($remarks);
            }

            // Get faculty type display name
            $facultyTypeMap = [
                '1' => 'Internal',
                '2' => 'Guest',
            ];
            $facultyTypeDisplay = $facultyTypeMap[$item->faculty_type] ?? ucfirst($item->faculty_type);

            // Determine course status
            $courseStatus = 'Archived';
            if ($item->program_status == 1 && Carbon::parse($item->program_end_date)->gte(Carbon::today())) {
                $courseStatus = 'Current';
            }

            // Format date and extract session time
            $startDate = $item->START_DATE ? Carbon::parse($item->START_DATE) : null;
            $formattedStartDate = $startDate ? $startDate->format('d-M-Y') : '';

            // Get session time from class_session column
            $sessionTime = '';
            if (!empty($item->class_session) && trim($item->class_session) !== '') {
                $sessionTime = trim($item->class_session);
                // Clean up common formats
                $sessionTime = str_replace(['08:00 AM - 08:00 PM', '00:00 - 00:00'], '', $sessionTime);
                $sessionTime = trim($sessionTime);

                // If session time is empty after cleaning, don't show it
                if (empty($sessionTime) || $sessionTime === '-') {
                    $sessionTime = '';
                }
            }

            // Prepare time display
            $timeDisplay = '';
            if ($sessionTime) {
                $timeDisplay = "({$sessionTime})";
            }

            return [
                'topic_name' => $item->topic_name ?? '',
                'program_id' => $item->program_id ?? '',
                'program_name' => $item->program_name ?? '',
                'course_status' => $courseStatus,
                'faculty_name' => $item->faculty_name ?? '',
                'faculty_type' => $facultyTypeDisplay,
                'faculty_pk' => $item->faculty_pk ?? '',
                'start_date' => $item->START_DATE ?? '',
                'end_date' => $item->END_DATE ?? '',
                'class_session' => $item->class_session ?? '', // Add class session
                'session_time' => $sessionTime, // Add cleaned session time
                'time_display' => $timeDisplay, // Add formatted time display
                'formatted_start_date' => $formattedStartDate, // Add formatted date
                'timetable_pk' => $item->timetable_pk ?? '',
                'participants' => (int)($item->participants ?? 0),
                'content_counts' => [
                    '5' => $content_5,
                    '4' => $content_4,
                    '3' => $content_3,
                    '2' => $content_2,
                    '1' => $content_1,
                ],
                'presentation_counts' => [
                    '5' => $presentation_5,
                    '4' => $presentation_4,
                    '3' => $presentation_3,
                    '2' => $presentation_2,
                    '1' => $presentation_1,
                ],
                'content_percentage' => $contentPercentage,
                'presentation_percentage' => $presentationPercentage,
                'remarks' => $remarks,
                'raw_start_date' => $startDate ? $startDate->format('Y-m-d H:i:s') : null, // For sorting
            ];
        });

        // Sort the processed data by date (in case pagination needs it)
        $processedData = $processedData->sortBy('raw_start_date')->values();

        // Paginate the processed array data
        $perPage = 1;
        $currentPage = $page;
        $totalRecords = $processedData->count();
        $totalPages = ceil($totalRecords / $perPage);

        // Ensure current page is valid
        if ($currentPage < 1) {
            $currentPage = 1;
        } elseif ($currentPage > $totalPages && $totalPages > 0) {
            $currentPage = $totalPages;
        }

        // Slice the processed data for the current page
        $currentPageData = $processedData->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // If current page has no data but total records > 0, go to page 1
        if ($currentPageData->isEmpty() && $totalRecords > 0) {
            $currentPage = 1;
            $currentPageData = $processedData->slice(0, $perPage)->values();
        }

        // Return view with ALL necessary variables for both initial load and AJAX
        return view('admin.feedback.faculty_view', [
            'feedbackData' => $currentPageData,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'programs' => $programs,
            'facultyTypes' => $facultyTypes,
            'facultySuggestions' => $facultySuggestions,
            'currentProgram' => $programId,
            'currentFaculty' => $facultyName,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'courseType' => $courseType,
            'selectedFacultyTypes' => $facultyType,
            'refreshTime' => now()->format('d-M-Y H:i'),
        ]);
    }


    public function getFacultySuggestions(Request $request)
    {
        // Handle faculty_type parameter - it might be string or array
        $selectedTypes = $request->input('faculty_type', []);
        $searchTerm = $request->input('faculty_name', '');

        // Ensure selectedTypes is always an array
        if (is_string($selectedTypes)) {
            $selectedTypes = [$selectedTypes];
        } elseif (empty($selectedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'No faculty type selected',
                'faculties' => []
            ]);
        }

        // Validate and clean faculty types (only allow 1 and 2)
        $validTypes = array_filter($selectedTypes, function ($type) {
            return in_array($type, ['1', '2']); // Only Internal and Guest
        });

        if (empty($validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid faculty type selected',
                'faculties' => []
            ]);
        }

        // Build query
        $query = DB::table('faculty_master')
            ->select('full_name', 'faculty_type')
            ->whereIn('faculty_type', $validTypes)
            ->whereNotNull('full_name')
            ->where('full_name', '!=', '');

        if (!empty($searchTerm)) {
            $query->where('full_name', 'LIKE', '%' . $searchTerm . '%');
        }

        $faculties = $query->orderBy('full_name')->limit(20)->get();

        // Map faculty types to display names (only Internal and Guest)
        $facultyTypeMap = [
            '1' => 'Internal',
            '2' => 'Guest',
        ];

        $faculties = $faculties->map(function ($faculty) use ($facultyTypeMap) {
            return [
                'full_name' => $faculty->full_name,
                'faculty_type_display' => $facultyTypeMap[$faculty->faculty_type] ?? 'Unknown'
            ];
        });

        return response()->json([
            'success' => true,
            'faculties' => $faculties
        ]);
    }

    public function exportFacultyFeedback(Request $request)
    {
        // Get filter parameters
        $programId = $request->input('program_id');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'current');
        $facultyType = $request->input('faculty_type', []);
        $exportType = $request->input('export_type', 'excel');
        if (is_string($facultyType)) {
            $facultyType = [$facultyType];
        }
        $data_course_id = get_Role_by_course();
        $facultyTypeMap = [
            '1' => 'Internal',
            '2' => 'Guest',
        ];

        $query = DB::table('topic_feedback as tf')
            ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
            ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
            ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
            ->select(
                'tf.topic_name',
                'cm.pk as program_id',
                'cm.course_name as program_name',
                'cm.active_inactive as program_status',
                'cm.end_date as program_end_date',
                'fm.full_name as faculty_name',
                'fm.faculty_type',
                'tf.faculty_pk',
                'tt.START_DATE',
                'tt.END_DATE',
                'tt.class_session',
                'tf.timetable_pk',
                DB::raw('SUM(CASE WHEN tf.content = "5" THEN 1 ELSE 0 END) as content_5'),
                DB::raw('SUM(CASE WHEN tf.content = "4" THEN 1 ELSE 0 END) as content_4'),
                DB::raw('SUM(CASE WHEN tf.content = "3" THEN 1 ELSE 0 END) as content_3'),
                DB::raw('SUM(CASE WHEN tf.content = "2" THEN 1 ELSE 0 END) as content_2'),
                DB::raw('SUM(CASE WHEN tf.content = "1" THEN 1 ELSE 0 END) as content_1'),
                DB::raw('SUM(CASE WHEN tf.presentation = "5" THEN 1 ELSE 0 END) as presentation_5'),
                DB::raw('SUM(CASE WHEN tf.presentation = "4" THEN 1 ELSE 0 END) as presentation_4'),
                DB::raw('SUM(CASE WHEN tf.presentation = "3" THEN 1 ELSE 0 END) as presentation_3'),
                DB::raw('SUM(CASE WHEN tf.presentation = "2" THEN 1 ELSE 0 END) as presentation_2'),
                DB::raw('SUM(CASE WHEN tf.presentation = "1" THEN 1 ELSE 0 END) as presentation_1'),
                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participants'),
                DB::raw('GROUP_CONCAT(DISTINCT CASE 
                    WHEN tf.remark IS NOT NULL 
                    AND TRIM(tf.remark) != "" 
                    THEN tf.remark 
                    ELSE NULL 
                 END SEPARATOR "|||") as remarks')
            )
            ->where('tf.is_submitted', 1);

        if (!empty($data_course_id)) {
            $query->whereIn('cm.pk', $data_course_id);
        }
        $query->groupBy('tf.topic_name', 'cm.pk', 'cm.course_name', 'cm.active_inactive', 'cm.end_date', 'fm.full_name', 'fm.faculty_type', 'tf.faculty_pk', 'tt.START_DATE', 'tt.END_DATE', 'tt.class_session', 'tf.timetable_pk');

        if ($programId && $programId !== '') {
            $query->where('cm.pk', $programId);
        }

        if ($facultyName && $facultyName !== 'All Faculty') {
            $query->where('fm.full_name', 'LIKE', '%' . $facultyName . '%');
        }

        if (!empty($facultyType)) {
            $query->whereIn('fm.faculty_type', $facultyType);
        }

        if ($fromDate) {
            $query->whereDate('tt.START_DATE', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('tt.END_DATE', '<=', $toDate);
        }

        if ($courseType === 'archived') {
            $query->where(function ($q) {
                $q->where('cm.active_inactive', 0)
                    ->orWhereDate('cm.end_date', '<', Carbon::today());
            });
        } elseif ($courseType === 'current') {
            $query->where('cm.active_inactive', 1)
                ->whereDate('cm.end_date', '>=', Carbon::today());
        }
        $query->orderBy('tt.START_DATE', 'asc')
            ->orderByRaw("
              CASE 
                  WHEN tt.class_session LIKE '%AM%' THEN 1
                  WHEN tt.class_session LIKE '%PM%' THEN 2
                  WHEN tt.class_session REGEXP '^[0-9]' THEN 3
                  ELSE 4
              END,
              tt.class_session ASC
          ");

        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        $feedbackData = $query->get();

        $processedData = $feedbackData->map(function ($item) use ($facultyTypeMap) {
            $content_5 = (int)$item->content_5;
            $content_4 = (int)$item->content_4;
            $content_3 = (int)$item->content_3;
            $content_2 = (int)$item->content_2;
            $content_1 = (int)$item->content_1;

            $presentation_5 = (int)$item->presentation_5;
            $presentation_4 = (int)$item->presentation_4;
            $presentation_3 = (int)$item->presentation_3;
            $presentation_2 = (int)$item->presentation_2;
            $presentation_1 = (int)$item->presentation_1;

            $contentWeightedSum =
                (5 * $content_5) +
                (4 * $content_4) +
                (3 * $content_3) +
                (2 * $content_2) +
                (1 * $content_1);

            $contentTotal =
                $content_5 + $content_4 + $content_3 + $content_2 + $content_1;

            $contentMaxRating = 0;
            if ($content_5 > 0) $contentMaxRating = 5;
            elseif ($content_4 > 0) $contentMaxRating = 4;
            elseif ($content_3 > 0) $contentMaxRating = 3;
            elseif ($content_2 > 0) $contentMaxRating = 2;
            elseif ($content_1 > 0) $contentMaxRating = 1;

            $contentPercentage = ($contentTotal > 0 && $contentMaxRating > 0)
                ? round(($contentWeightedSum / ($contentTotal * $contentMaxRating)) * 100, 2)
                : 0;

            $presentationWeightedSum =
                (5 * $presentation_5) +
                (4 * $presentation_4) +
                (3 * $presentation_3) +
                (2 * $presentation_2) +
                (1 * $presentation_1);

            $presentationTotal =
                $presentation_5 + $presentation_4 + $presentation_3 + $presentation_2 + $presentation_1;

            $presentationMaxRating = 0;
            if ($presentation_5 > 0) $presentationMaxRating = 5;
            elseif ($presentation_4 > 0) $presentationMaxRating = 4;
            elseif ($presentation_3 > 0) $presentationMaxRating = 3;
            elseif ($presentation_2 > 0) $presentationMaxRating = 2;
            elseif ($presentation_1 > 0) $presentationMaxRating = 1;

            $presentationPercentage = ($presentationTotal > 0 && $presentationMaxRating > 0)
                ? round(($presentationWeightedSum / ($presentationTotal * $presentationMaxRating)) * 100, 2)
                : 0;

            $remarks = [];
            if (!empty($item->remarks)) {
                $rawRemarks = explode('|||', $item->remarks);
                $remarks = array_filter(array_map('trim', $rawRemarks), function ($remark) {
                    $remark = trim($remark);
                    return !empty($remark) &&
                        $remark !== '.' &&
                        $remark !== '..' &&
                        $remark !== '...' &&
                        $remark !== '-' &&
                        $remark !== '--' &&
                        strlen($remark) > 1;
                });
                $remarks = array_unique($remarks);

                sort($remarks);
            }
            $facultyTypeDisplay = $facultyTypeMap[$item->faculty_type] ?? ucfirst($item->faculty_type);

            $courseStatus = 'Archived';
            if ($item->program_status == 1 && Carbon::parse($item->program_end_date)->gte(Carbon::today())) {
                $courseStatus = 'Current';
            }

            $startDate = $item->START_DATE ? Carbon::parse($item->START_DATE) : null;
            $lectureDate = $startDate ? $startDate->format('d-M-Y') : '';

            $sessionTime = '';
            if (!empty($item->class_session) && trim($item->class_session) !== '') {
                $sessionTime = trim($item->class_session);
                $sessionTime = str_replace(['08:00 AM - 08:00 PM', '00:00 - 00:00', '00:00 to 00:00'], '', $sessionTime);
                $sessionTime = trim($sessionTime);

                if (empty($sessionTime) || $sessionTime === '-') {
                    $sessionTime = '';
                }
            }

            $timeDisplay = '';
            if ($sessionTime) {
                $timeDisplay = "({$sessionTime})";
            }

            $remarksText = implode("\n", $remarks);

            return [
                'Program Name' => $item->program_name ?? '',
                'Course Status' => $courseStatus,
                'Faculty Name' => $item->faculty_name ?? '',
                'Faculty Type' => $facultyTypeDisplay,
                'Topic' => $item->topic_name ?? '',
                'Lecture Date' => $lectureDate,
                'Time' => $timeDisplay,
                'Total Participants' => (int)($item->participants ?? 0),
                'Content - Excellent' => $content_5,
                'Content - Very Good' => $content_4,
                'Content - Good' => $content_3,
                'Content - Average' => $content_2,
                'Content - Below Average' => $content_1,
                'Content Percentage' => number_format($contentPercentage, 2) . '%',
                'Presentation - Excellent' => $presentation_5,
                'Presentation - Very Good' => $presentation_4,
                'Presentation - Good' => $presentation_3,
                'Presentation - Average' => $presentation_2,
                'Presentation - Below Average' => $presentation_1,
                'Presentation Percentage' => number_format($presentationPercentage, 2) . '%',
                'Remarks' => $remarksText,
                'Raw Start Date' => $startDate ? $startDate->format('Y-m-d H:i:s') : null, // For sorting
            ];
        })->toArray();

        usort($processedData, function ($a, $b) {
            $dateA = $a['Raw Start Date'] ? strtotime($a['Raw Start Date']) : 0;
            $dateB = $b['Raw Start Date'] ? strtotime($b['Raw Start Date']) : 0;
            return $dateA <=> $dateB;
        });

        foreach ($processedData as &$item) {
            unset($item['Raw Start Date']);
        }

        if ($exportType === 'excel') {
            return $this->exportExcelWithDesign($processedData, $request);
        } else {
            $programName = 'All Programs';
            if ($programId) {
                $program = DB::table('course_master')->where('pk', $programId)->first();
                $programName = $program ? $program->course_name : 'All Programs';
            }

            $facultyTypeDisplay = 'All Types';
            if (!empty($facultyType)) {
                if (count($facultyType) === 2) {
                    $facultyTypeDisplay = 'All Types';
                } else {
                    $facultyTypeDisplay = in_array('1', $facultyType) ? 'Internal' : 'Guest';
                }
            }

            $data = [
                'feedbackData' => $processedData,
                'filters' => [
                    'program' => $programName,
                    'faculty_name' => $facultyName ?: 'All Faculty',
                    'date_range' => ($fromDate && $toDate) ?
                        Carbon::parse($fromDate)->format('d-M-Y') . ' to ' . Carbon::parse($toDate)->format('d-M-Y') :
                        'All Dates',
                    'course_type' => $courseType === 'current' ? 'Current Courses' : 'Archived Courses',
                    'faculty_type' => $facultyTypeDisplay,
                ],
                'export_date' => now()->format('d-M-Y H:i'),
            ];

            $pdf = PDF::loadView('admin.feedback.faculty_feedback_export', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'Arial',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isPhpEnabled' => true,
                    'dpi' => 96,
                    'margin_top' => 15,
                    'margin_right' => 15,
                    'margin_bottom' => 15,
                    'margin_left' => 15,
                ]);

            return $pdf->download('faculty_feedback_' . date('Y_m_d') . '.pdf');
        }
    }

    /**
     * Print Faculty Feedback – opens LBSNAA-themed view in a new tab.
     */
    public function printFacultyFeedback(Request $request)
    {
        try {
            $programId = $request->input('program_id');
            $facultyName = $request->input('faculty_name');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $courseType = $request->input('course_type', 'current');
            $facultyType = $request->input('faculty_type', []);
            if (is_string($facultyType)) {
                $facultyType = [$facultyType];
            }
            $data_course_id = get_Role_by_course();
            $facultyTypeMap = ['1' => 'Internal', '2' => 'Guest'];

            $query = DB::table('topic_feedback as tf')
                ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
                ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
                ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
                ->select(
                    'tf.topic_name',
                    'cm.pk as program_id',
                    'cm.course_name as program_name',
                    'cm.active_inactive as program_status',
                    'cm.end_date as program_end_date',
                    'fm.full_name as faculty_name',
                    'fm.faculty_type',
                    'tf.faculty_pk',
                    'tt.START_DATE',
                    'tt.END_DATE',
                    'tt.class_session',
                    'tf.timetable_pk',
                    DB::raw('SUM(CASE WHEN tf.content = "5" THEN 1 ELSE 0 END) as content_5'),
                    DB::raw('SUM(CASE WHEN tf.content = "4" THEN 1 ELSE 0 END) as content_4'),
                    DB::raw('SUM(CASE WHEN tf.content = "3" THEN 1 ELSE 0 END) as content_3'),
                    DB::raw('SUM(CASE WHEN tf.content = "2" THEN 1 ELSE 0 END) as content_2'),
                    DB::raw('SUM(CASE WHEN tf.content = "1" THEN 1 ELSE 0 END) as content_1'),
                    DB::raw('SUM(CASE WHEN tf.presentation = "5" THEN 1 ELSE 0 END) as presentation_5'),
                    DB::raw('SUM(CASE WHEN tf.presentation = "4" THEN 1 ELSE 0 END) as presentation_4'),
                    DB::raw('SUM(CASE WHEN tf.presentation = "3" THEN 1 ELSE 0 END) as presentation_3'),
                    DB::raw('SUM(CASE WHEN tf.presentation = "2" THEN 1 ELSE 0 END) as presentation_2'),
                    DB::raw('SUM(CASE WHEN tf.presentation = "1" THEN 1 ELSE 0 END) as presentation_1'),
                    DB::raw('COUNT(DISTINCT tf.student_master_pk) as participants'),
                    DB::raw('GROUP_CONCAT(DISTINCT CASE WHEN tf.remark IS NOT NULL AND TRIM(tf.remark) != "" THEN tf.remark ELSE NULL END SEPARATOR "|||") as remarks')
                )
                ->where('tf.is_submitted', 1);

            if (!empty($data_course_id)) {
                $query->whereIn('cm.pk', $data_course_id);
            }
            $query->groupBy('tf.topic_name', 'cm.pk', 'cm.course_name', 'cm.active_inactive', 'cm.end_date', 'fm.full_name', 'fm.faculty_type', 'tf.faculty_pk', 'tt.START_DATE', 'tt.END_DATE', 'tt.class_session', 'tf.timetable_pk');

            if ($programId && $programId !== '') {
                $query->where('cm.pk', $programId);
            }
            if ($facultyName && $facultyName !== 'All Faculty') {
                $query->where('fm.full_name', 'LIKE', '%' . $facultyName . '%');
            }
            if (!empty($facultyType)) {
                $query->whereIn('fm.faculty_type', $facultyType);
            }
            if ($fromDate) {
                $query->whereDate('tt.START_DATE', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('tt.END_DATE', '<=', $toDate);
            }
            if ($courseType === 'archived') {
                $query->where(function ($q) {
                    $q->where('cm.active_inactive', 0)
                        ->orWhereDate('cm.end_date', '<', Carbon::today());
                });
            } elseif ($courseType === 'current') {
                $query->where('cm.active_inactive', 1)
                    ->whereDate('cm.end_date', '>=', Carbon::today());
            }
            $query->orderBy('tt.START_DATE', 'asc');

            DB::statement("SET SESSION group_concat_max_len = 1000000;");
            $feedbackData = $query->get();

            $processedData = $feedbackData->map(function ($item) use ($facultyTypeMap) {
                $c5 = (int) $item->content_5; $c4 = (int) $item->content_4; $c3 = (int) $item->content_3;
                $c2 = (int) $item->content_2; $c1 = (int) $item->content_1;
                $p5 = (int) $item->presentation_5; $p4 = (int) $item->presentation_4; $p3 = (int) $item->presentation_3;
                $p2 = (int) $item->presentation_2; $p1 = (int) $item->presentation_1;
                $cTotal = $c5+$c4+$c3+$c2+$c1;
                $cWeighted = 5*$c5 + 4*$c4 + 3*$c3 + 2*$c2 + 1*$c1;
                $cMax = $c5 > 0 ? 5 : ($c4 > 0 ? 4 : ($c3 > 0 ? 3 : ($c2 > 0 ? 2 : ($c1 > 0 ? 1 : 0))));
                $cPct = ($cTotal > 0 && $cMax > 0) ? round(($cWeighted / ($cTotal * $cMax)) * 100, 2) : 0;
                $pTotal = $p5+$p4+$p3+$p2+$p1;
                $pWeighted = 5*$p5 + 4*$p4 + 3*$p3 + 2*$p2 + 1*$p1;
                $pMax = $p5 > 0 ? 5 : ($p4 > 0 ? 4 : ($p3 > 0 ? 3 : ($p2 > 0 ? 2 : ($p1 > 0 ? 1 : 0))));
                $pPct = ($pTotal > 0 && $pMax > 0) ? round(($pWeighted / ($pTotal * $pMax)) * 100, 2) : 0;
                $remarks = [];
                if (!empty($item->remarks)) {
                    $raw = explode('|||', $item->remarks);
                    $remarks = array_values(array_unique(array_filter(array_map('trim', $raw), fn($r) =>
                        !empty($r) && !in_array($r, ['.','..','...','-','--']) && strlen($r) > 1
                    )));
                    sort($remarks);
                }
                $courseStatus = ($item->program_status == 1 && Carbon::parse($item->program_end_date)->gte(Carbon::today())) ? 'Current' : 'Archived';
                $startDate = $item->START_DATE ? Carbon::parse($item->START_DATE) : null;
                $sessionTime = trim($item->class_session ?? '');
                $sessionTime = str_replace(['08:00 AM - 08:00 PM','00:00 - 00:00','00:00 to 00:00'], '', $sessionTime);
                $sessionTime = trim($sessionTime);
                if (empty($sessionTime) || $sessionTime === '-') $sessionTime = '';
                return [
                    'Program Name' => $item->program_name ?? '',
                    'Course Status' => $courseStatus,
                    'Faculty Name' => $item->faculty_name ?? '',
                    'Faculty Type' => $facultyTypeMap[$item->faculty_type] ?? ucfirst($item->faculty_type),
                    'Topic' => $item->topic_name ?? '',
                    'Lecture Date' => $startDate ? $startDate->format('d-M-Y') : '',
                    'Time' => $sessionTime ? "({$sessionTime})" : '',
                    'Content - Excellent' => $c5, 'Content - Very Good' => $c4, 'Content - Good' => $c3,
                    'Content - Average' => $c2, 'Content - Below Average' => $c1,
                    'Content Percentage' => number_format($cPct, 2) . '%',
                    'Presentation - Excellent' => $p5, 'Presentation - Very Good' => $p4, 'Presentation - Good' => $p3,
                    'Presentation - Average' => $p2, 'Presentation - Below Average' => $p1,
                    'Presentation Percentage' => number_format($pPct, 2) . '%',
                    'Remarks' => implode("\n", $remarks),
                ];
            })->toArray();

            $facultyTypeDisplay = 'All Types';
            if (!empty($facultyType)) {
                $facultyTypeDisplay = count($facultyType) === 2 ? 'All Types' : (in_array('1', $facultyType) ? 'Internal' : 'Guest');
            }
            $programName = 'All Programs';
            if ($programId) {
                $prog = DB::table('course_master')->where('pk', $programId)->first();
                $programName = $prog ? $prog->course_name : 'All Programs';
            }

            return view('admin.feedback.faculty_feedback_export', [
                'feedbackData' => $processedData,
                'filters' => [
                    'program' => $programName,
                    'faculty_name' => $facultyName ?: 'All Faculty',
                    'date_range' => ($fromDate && $toDate)
                        ? Carbon::parse($fromDate)->format('d-M-Y') . ' to ' . Carbon::parse($toDate)->format('d-M-Y')
                        : 'All Dates',
                    'course_type' => $courseType === 'current' ? 'Current Courses' : 'Archived Courses',
                    'faculty_type' => $facultyTypeDisplay,
                ],
                'export_date' => now()->format('d-M-Y H:i'),
                'mode' => 'print',
            ]);
        } catch (\Exception $e) {
            \Log::error('Faculty Feedback Print Error: ' . $e->getMessage());
            return back()->with('error', 'Error generating print view: ' . $e->getMessage());
        }
    }

    private function getProgramName($programId)
    {
        $program = DB::table('course_master')
            ->where('pk', $programId)
            ->first();

        return $program ? $program->course_name : 'Unknown Program';
    }

    /**
     * Data-URI for PDF/DomPDF (embed Ashoka / LBSNAA logo like mess print reports).
     */
    private function feedbackReportBrandingImageDataUri(?string $path): ?string
    {
        if (!$path || !is_file($path)) {
            return null;
        }

        $mime = @mime_content_type($path) ?: 'image/png';
        if (str_starts_with($mime, 'image/svg')) {
            $mime = 'image/svg+xml';
        }

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
    }

    private function exportExcelWithDesign($data, $request)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $spreadsheet->getProperties()
            ->setCreator("Sargam LMS")
            ->setTitle("Faculty Feedback Report")
            ->setSubject("Faculty Feedback with Comments")
            ->setDescription("Export of faculty feedback data with detailed ratings and comments")
            ->setKeywords("faculty feedback ratings comments")
            ->setCategory("Report");

        $defaultFont = [
            'name' => 'Arial',
            'size' => 10,
        ];

        $spreadsheet->getDefaultStyle()->applyFromArray([
            'font' => $defaultFont,
        ]);

        $row = 1;

        $sheet->setCellValue('A' . $row, 'Faculty Feedback with Comments (Admin View)');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'AF2910']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        $sheet->setCellValue('A' . $row, 'Sargam | Lal Bahadur Shastri Institute of Management');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        $sheet->setCellValue('A' . $row, 'Report Generated: ' . now()->format('d-M-Y H:i'));
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['italic' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Applied Filters');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'AF2910']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8F9FA']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D7DE']
                ]
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
        ]);
        $row++;

        $programId = $request->input('program_id');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'archived');
        $facultyType = $request->input('faculty_type', []);

        foreach ($data as $index => $item) {
            $sheet->setCellValue('A' . $row, 'Course:');
            $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
            $sheet->setCellValue('B' . $row, $item['Program Name']);
            if (isset($item['Course Status'])) {
                $sheet->setCellValue('D' . $row, $item['Course Status']);
                $sheet->getStyle('D' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E9ECEF']
                    ],
                    'font' => ['size' => 9],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);
            }
            $row++;

            $sheet->setCellValue('A' . $row, 'Faculty:');
            $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
            $sheet->setCellValue('B' . $row, $item['Faculty Name']);
            if (isset($item['Faculty Type'])) {
                $sheet->setCellValue('D' . $row, $item['Faculty Type']);
                $sheet->getStyle('D' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E9ECEF']
                    ],
                    'font' => ['size' => 9],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);
            }
            $row++;

            $sheet->setCellValue('A' . $row, 'Topic:');
            $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
            $sheet->setCellValue('B' . $row, $item['Topic']);
            $row++;

            if (!empty($item['Lecture Date'])) {
                $sheet->setCellValue('A' . $row, 'Lecture Date:');
                $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);

                $dateTimeText = $item['Lecture Date'];
                if (!empty($item['Time'])) {
                    $dateTimeText .= ' ' . $item['Time'];
                }
                $sheet->setCellValue('B' . $row, $dateTimeText);
                $row++;
            }
            $row++;

            $tableStartRow = $row;
            $sheet->setCellValue('A' . $row, 'Rating');
            $sheet->setCellValue('B' . $row, 'Content *');
            $sheet->setCellValue('C' . $row, 'Presentation *');

            $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EEF4FB']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'D0D7DE']
                    ]
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
            $row++;

            // EXCELLENT ROW
            $sheet->setCellValue('A' . $row, 'Excellent');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $sheet->setCellValue('B' . $row, $item['Content - Excellent']);
            $sheet->setCellValue('C' . $row, $item['Presentation - Excellent']);
            $this->applyTableCellStyle($sheet, 'A' . $row . ':C' . $row);
            $row++;

            // VERY GOOD ROW
            $sheet->setCellValue('A' . $row, 'Very Good');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $sheet->setCellValue('B' . $row, $item['Content - Very Good']);
            $sheet->setCellValue('C' . $row, $item['Presentation - Very Good']);
            $this->applyTableCellStyle($sheet, 'A' . $row . ':C' . $row);
            $row++;

            // GOOD ROW
            $sheet->setCellValue('A' . $row, 'Good');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $sheet->setCellValue('B' . $row, $item['Content - Good']);
            $sheet->setCellValue('C' . $row, $item['Presentation - Good']);
            $this->applyTableCellStyle($sheet, 'A' . $row . ':C' . $row);
            $row++;

            // AVERAGE ROW
            $sheet->setCellValue('A' . $row, 'Average');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $sheet->setCellValue('B' . $row, $item['Content - Average']);
            $sheet->setCellValue('C' . $row, $item['Presentation - Average']);
            $this->applyTableCellStyle($sheet, 'A' . $row . ':C' . $row);
            $row++;

            // BELOW AVERAGE ROW
            $sheet->setCellValue('A' . $row, 'Below Average');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $sheet->setCellValue('B' . $row, $item['Content - Below Average']);
            $sheet->setCellValue('C' . $row, $item['Presentation - Below Average']);
            $this->applyTableCellStyle($sheet, 'A' . $row . ':C' . $row);
            $row++;

            // PERCENTAGE ROW
            $sheet->setCellValue('A' . $row, 'Percentage');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $sheet->setCellValue('B' . $row, $item['Content Percentage']);
            $sheet->setCellValue('C' . $row, $item['Presentation Percentage']);
            $sheet->getStyle('B' . $row . ':C' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $this->applyTableCellStyle($sheet, 'A' . $row . ':C' . $row);
            $row++;

            // TOTAL PARTICIPANTS NOTE
            // $sheet->setCellValue('A' . $row, '* is defined as Total Student Count: ' . $item['Total Participants']);
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['italic' => true, 'size' => 9],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
            $row++;

            // REMARKS SECTION
            if (!empty($item['Remarks'])) {
                $remarks = explode("\n", $item['Remarks']);
                if (!empty(array_filter($remarks))) {
                    $row++;
                    $sheet->setCellValue('A' . $row, 'Remarks (' . count(array_filter($remarks)) . ')');
                    $sheet->mergeCells('A' . $row . ':C' . $row);
                    $sheet->getStyle('A' . $row)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'AF2910']
                        ],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                    ]);
                    $row++;

                    $remarkStartRow = $row;
                    foreach ($remarks as $remarkIndex => $remark) {
                        if (trim($remark) !== '') {
                            $sheet->setCellValue('A' . $row, ($remarkIndex + 1) . '. ' . trim($remark));
                            $sheet->mergeCells('A' . $row . ':C' . $row);
                            $row++;
                        }
                    }

                    // Style remarks area
                    $sheet->getStyle('A' . $remarkStartRow . ':C' . ($row - 1))->applyFromArray([
                        'borders' => [
                            'outline' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'D0D7DE']
                            ],
                            'inside' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE
                            ]
                        ],
                        'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP],
                    ]);
                }
            }

            // Add separator between records (except last one)
            if ($index < count($data) - 1) {
                $row++;
                $sheet->mergeCells('A' . $row . ':C' . $row);
                $sheet->getStyle('A' . $row)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DASHED);
                $sheet->getStyle('A' . $row)->getBorders()->getTop()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('DDDDDD'));
                $row += 2;
            }
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);

        // Auto size for remarks
        $sheet->getColumnDimension('A')->setAutoSize(true);

        // Add footer
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Confidential - For Internal Use Only');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;
        $sheet->setCellValue('A' . $row, 'Generated by Sargam Faculty Feedback System');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // Create writer and save to temporary file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $filename = 'faculty_feedback_' . date('Y_m_d_H_i') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'faculty_feedback_');
        $writer->save($tempFile);

        // Return download response
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function applyTableCellStyle($sheet, $range)
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D7DE']
                ]
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
    }

    public function feedbackDetails(Request $request)
    {


        // \Log::info('Feedback Details Request: ', $request->all());
        try {
            $data_course_id =  get_Role_by_course();
            // Get filter parameters with defaults
            $programId = $request->input('program_id', '');
            $facultyName = $request->input('faculty_name', '');
            $fromDate = $request->input('from_date', '');
            $toDate = $request->input('to_date', '');
            $courseType = $request->input('course_type', 'current');
            $facultyType = $request->input('faculty_type', []);
            $page = $request->input('page', 1);

            // Ensure faculty_type is always an array
            if (is_string($facultyType)) {
                $facultyType = [$facultyType];
            }

            // Get active courses initially (for current type)
            $programsQuery = DB::table('course_master')
                ->select('pk as id', 'course_name', 'active_inactive', 'start_year', 'end_date');

            if ($courseType === 'current') {
                $programsQuery->where('active_inactive', 1)
                    ->whereDate('end_date', '>=', Carbon::today());
                if (!empty($data_course_id)) {
                    $programsQuery->whereIn('pk', $data_course_id);
                }
            } else {
                $programsQuery->where(function ($query) {
                    $query->where('active_inactive', 0)
                        ->orWhereDate('end_date', '<', Carbon::today());
                });
            }




            $programs = $programsQuery->orderBy('course_name')
                ->pluck('course_name', 'id');

            if ($programs->isEmpty()) {
                $programs = collect([]);
            }

            // Default program to an active/current course on first full page load (non-AJAX).
            // AJAX requests keep empty program_id so "All Programs" still works after reset.
            if (
                ($programId === '' || $programId === null)
                && $courseType === 'current'
                && $programs->isNotEmpty()
                && ! $request->ajax()
            ) {
                $programId = $this->defaultProgramIdForCurrentCourseFeedbackList($programs, $data_course_id);
            }

            // print_r($programs->toArray());die;
            // Define faculty types
            $facultyTypes = [
                '2' => 'Guest',
                '1' => 'Internal',
            ];

            // Get faculty suggestions if faculty type is selected
            $facultySuggestions = collect();
            if (!empty($facultyType)) {
                $facultyQuery = DB::table('faculty_master')
                    ->select('full_name', 'pk', 'faculty_type')
                    ->whereIn('faculty_type', $facultyType)
                    ->whereNotNull('full_name')
                    ->orderBy('full_name');

                if ($facultyName) {
                    $facultyQuery->where('full_name', 'LIKE', '%' . $facultyName . '%');
                }

                $facultySuggestions = $facultyQuery->limit(10)->get();
            }

            // Build main query for feedback details
            // Update the select statement in the feedbackDetails method:
            // Build main query for feedback details
            $query = DB::table('topic_feedback as tf')
                ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
                ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
                ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
                ->join('student_master as sm', 'tf.student_master_pk', '=', 'sm.pk')
                ->select(
                    'tf.pk as feedback_id',
                    'tf.content',
                    'tf.presentation',
                    'tf.remark',
                    'tf.created_date as feedback_date',
                    // Create full name from separate name fields
                    DB::raw("CONCAT(
            COALESCE(sm.first_name, ''),
            CASE 
                WHEN sm.middle_name IS NOT NULL AND sm.middle_name != '' 
                THEN CONCAT(' ', sm.middle_name) 
                ELSE '' 
            END,
            CASE 
                WHEN sm.last_name IS NOT NULL AND sm.last_name != '' 
                THEN CONCAT(' ', sm.last_name) 
                ELSE '' 
            END
        ) as ot_name"),
                    DB::raw("COALESCE( sm.generated_OT_code) as ot_code"),
                    'tf.student_master_pk',
                    'cm.pk as program_id',
                    'cm.course_name as program_name',
                    'cm.active_inactive as program_status',
                    'cm.end_date as program_end_date',
                    'fm.full_name as faculty_name',
                    'fm.faculty_type',
                    'tf.faculty_pk',
                    'tt.START_DATE',
                    'tt.END_DATE',
                    'tt.subject_topic as topic_name',
                    'tf.timetable_pk'
                )

                ->where('tf.is_submitted', 1);
            if (!empty($data_course_id)) {
                $query->whereIn('cm.pk', $data_course_id);
            }
            $query->whereNotNull('tf.presentation')
                ->whereNotNull('tf.content')
                ->where('tf.presentation', '!=', '')
                ->where('tf.content', '!=', '');

            // Rest of the method remains the same...

            // Apply filters
            if ($programId && $programId !== '') {
                $query->where('cm.pk', $programId);
            }

            if ($facultyName && $facultyName !== 'All Faculty') {
                $query->where('fm.full_name', 'LIKE', '%' . $facultyName . '%');
            }

            if (!empty($facultyType)) {
                $query->whereIn('fm.faculty_type', $facultyType);
            }

            if ($fromDate) {
                $query->whereDate('tt.START_DATE', '>=', $fromDate);
            }

            if ($toDate) {
                $query->whereDate('tt.END_DATE', '<=', $toDate);
            }

            // Course type filter
            if ($courseType === 'archived') {
                $query->where(function ($q) {
                    $q->where('cm.active_inactive', 0)
                        ->orWhereDate('cm.end_date', '<', Carbon::today());
                });
            } elseif ($courseType === 'current') {
                $query->where('cm.active_inactive', 1)
                    ->whereDate('cm.end_date', '>=', Carbon::today());
            }
            if (hasRole('Internal Faculty') || hasRole('Guest Faculty')) {
                $facultyPk = (Auth::user()->user_id);
                $query->where('fm.employee_master_pk', $facultyPk);
            }

            // Order by
            $query->orderBy('tt.START_DATE', 'DESC')
                ->orderBy('fm.full_name')
                ->orderBy('sm.first_name');

            // Get total count for pagination
            $totalRecords = $query->count();

            // Apply pagination - 10 records per page
            $perPage = 10;
            $currentPage = $page;
            $totalPages = ceil($totalRecords / $perPage);

            // Ensure current page is valid
            if ($currentPage < 1) {
                $currentPage = 1;
            } elseif ($currentPage > $totalPages && $totalPages > 0) {
                $currentPage = $totalPages;
            }

            // Get paginated data
            $feedbackData = $query->offset(($currentPage - 1) * $perPage)
                ->limit($perPage)
                ->get();

            // Process data for display
            $processedData = $feedbackData->map(function ($item) {
                // Get faculty type display name
                $facultyTypeMap = [
                    '1' => 'Internal',
                    '2' => 'Guest',
                ];
                $facultyTypeDisplay = $facultyTypeMap[$item->faculty_type] ?? ucfirst($item->faculty_type);

                // Determine course status
                $courseStatus = 'Archived';
                if ($item->program_status == 1 && Carbon::parse($item->program_end_date)->gte(Carbon::today())) {
                    $courseStatus = 'Current';
                }

                return [
                    'feedback_id' => $item->feedback_id ?? '',
                    'ot_name' => $item->ot_name ?? '',
                    'ot_code' => $item->ot_code ?? '',
                    'content' => $item->content ?? '',
                    'presentation' => $item->presentation ?? '',
                    'remark' => $item->remark ?? '',
                    'feedback_date' => $item->feedback_date ? Carbon::parse($item->feedback_date)->format('d-M-Y H:i') : '',
                    'program_id' => $item->program_id ?? '',
                    'program_name' => $item->program_name ?? '',
                    'course_status' => $courseStatus,
                    'faculty_name' => $item->faculty_name ?? '',
                    'faculty_type' => $facultyTypeDisplay,
                    'start_date' => $item->START_DATE ? Carbon::parse($item->START_DATE)->format('d-M-Y H:i') : '',
                    'end_date' => $item->END_DATE ? Carbon::parse($item->END_DATE)->format('H:i') : '',
                    'topic_name' => $item->topic_name ?? '',
                ];
            });

            // Group data by program, faculty, and topic for display
            $groupedData = $processedData->groupBy(function ($item) {
                return $item['program_name'] . '|' . $item['faculty_name'] . '|' . $item['topic_name'];
            });
            // print_r($groupedData);

            // Prepare response based on request type
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'groupedData' => $groupedData,
                    'currentPage' => $currentPage,
                    'totalPages' => $totalPages,
                    'totalRecords' => $totalRecords,
                    'programs' => $programs,
                    'facultyTypes' => $facultyTypes,
                    'facultySuggestions' => $facultySuggestions,
                    'currentProgram' => $programId,
                    'currentFaculty' => $facultyName,
                    'fromDate' => $fromDate,
                    'toDate' => $toDate,
                    'courseType' => $courseType,
                    'selectedFacultyTypes' => $facultyType,
                    'refreshTime' => now()->format('d-M-Y H:i'),
                ]);
            }

            return view('admin.feedback.feedback_details', [
                'groupedData' => $groupedData,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalRecords' => $totalRecords,
                'programs' => $programs,
                'facultyTypes' => $facultyTypes,
                'facultySuggestions' => $facultySuggestions,
                'currentProgram' => $programId,
                'currentFaculty' => $facultyName,
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'courseType' => $courseType,
                'selectedFacultyTypes' => $facultyType,
                'refreshTime' => now()->format('d-M-Y H:i'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in feedbackDetails: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error loading data: ' . $e->getMessage()
                ], 500);
            }

            return view('admin.feedback.feedback_details', [
                'groupedData' => collect(),
                'currentPage' => 1,
                'totalPages' => 0,
                'totalRecords' => 0,
                'programs' => collect(),
                'facultyTypes' => [],
                'facultySuggestions' => collect(),
                'currentProgram' => '',
                'currentFaculty' => '',
                'fromDate' => '',
                'toDate' => '',
                'courseType' => 'current',
                'selectedFacultyTypes' => [],
                'refreshTime' => now()->format('d-M-Y H:i'),
            ]);
        }
    }

    public function exportFeedbackDetails(Request $request)
    {
        try {
            // Get filter parameters
            $programId = $request->input('program_id');
            $facultyName = $request->input('faculty_name');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $courseType = $request->input('course_type', 'current');
            $facultyType = $request->input('faculty_type', []);
            $exportType = $request->input('export_type', 'excel');

            // Ensure faculty_type is always an array
            if (is_string($facultyType)) {
                $facultyType = [$facultyType];
            }

            // Build query for export (same as feedbackDetails but without pagination)
            $query = DB::table('topic_feedback as tf')
                ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
                ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
                ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
                ->join('student_master as sm', 'tf.student_master_pk', '=', 'sm.pk')
                ->select(
                    'tf.pk as feedback_id',
                    'tf.content',
                    'tf.presentation',
                    'tf.remark',
                    'tf.created_date as feedback_date',
                    DB::raw("COALESCE(sm.display_name, 
                    CONCAT(
                        COALESCE(sm.first_name, ''),
                        ' ',
                        COALESCE(sm.last_name, '')
                    )
                ) as ot_name"),
                    DB::raw("COALESCE(sm.generated_OT_code) as ot_code"),
                    'cm.course_name as program_name',
                    'cm.active_inactive as program_status',
                    'cm.end_date as program_end_date',
                    'fm.full_name as faculty_name',
                    'fm.faculty_type',
                    'tt.START_DATE',
                    'tt.END_DATE',
                    'tt.subject_topic as topic_name'
                )
                ->where('tf.is_submitted', 1)
                ->whereNotNull('tf.presentation')
                ->whereNotNull('tf.content')
                ->where('tf.presentation', '!=', '')
                ->where('tf.content', '!=', '');

            // Apply filters
            if ($programId && $programId !== '') {
                $query->where('cm.pk', $programId);
            }

            if ($facultyName && $facultyName !== 'All Faculty') {
                $query->where('fm.full_name', 'LIKE', '%' . $facultyName . '%');
            }

            if (!empty($facultyType)) {
                $query->whereIn('fm.faculty_type', $facultyType);
            }

            if ($fromDate) {
                $query->whereDate('tt.START_DATE', '>=', $fromDate);
            }

            if ($toDate) {
                $query->whereDate('tt.END_DATE', '<=', $toDate);
            }

            // Course type filter
            if ($courseType === 'archived') {
                $query->where(function ($q) {
                    $q->where('cm.active_inactive', 0)
                        ->orWhereDate('cm.end_date', '<', Carbon::today());
                });
            } elseif ($courseType === 'current') {
                $query->where('cm.active_inactive', 1)
                    ->whereDate('cm.end_date', '>=', Carbon::today());
            }

            // Order by
            $query->orderBy('tt.START_DATE', 'DESC')
                ->orderBy('fm.full_name')
                ->orderByRaw("COALESCE(sm.display_name, CONCAT(COALESCE(sm.first_name, ''), ' ', COALESCE(sm.last_name, '')))");

            // Get all data for export (no pagination)
            $feedbackData = $query->get();

            // Process data for export
            $processedData = $feedbackData->map(function ($item) {
                // Get faculty type display name
                $facultyTypeMap = [
                    '1' => 'Internal',
                    '2' => 'Guest',
                ];
                $facultyTypeDisplay = $facultyTypeMap[$item->faculty_type] ?? ucfirst($item->faculty_type);

                // Determine course status
                $courseStatus = 'Archived';
                if ($item->program_status == 1 && Carbon::parse($item->program_end_date)->gte(Carbon::today())) {
                    $courseStatus = 'Current';
                }

                // Format dates
                $sessionDate = '';
                $sessionTime = '';
                if ($item->START_DATE) {
                    $sessionDate = Carbon::parse($item->START_DATE)->format('d-M-Y');
                    $sessionTime = Carbon::parse($item->START_DATE)->format('H:i');
                    if ($item->END_DATE) {
                        $sessionTime .= ' - ' . Carbon::parse($item->END_DATE)->format('H:i');
                    }
                }

                $feedbackDate = $item->feedback_date ? Carbon::parse($item->feedback_date)->format('d-M-Y H:i') : '';

                return [
                    'S.No.' => '', // Will be populated in export
                    'Course Name' => $item->program_name ?? '',
                    'Course Status' => $courseStatus,
                    'Faculty Name' => $item->faculty_name ?? '',
                    'Faculty Type' => $facultyTypeDisplay,
                    'Topic' => $item->topic_name ?? '',
                    'Session Date' => $sessionDate,
                    'Session Time' => $sessionTime,
                    'OT Name' => $item->ot_name ?? '',
                    'OT Code' => $item->ot_code ?? '',
                    'Content Rating' => $item->content ?? '',
                    'Presentation Rating' => $item->presentation ?? '',
                    'Remarks' => $item->remark ?? '',
                    'Feedback Date' => $feedbackDate,
                ];
            });

            // Export based on type
            if ($exportType === 'excel') {
                return $this->exportExcelFeedbackDetails($processedData, $request);
            } else {
                return $this->exportPdfFeedbackDetails($processedData, $request);
            }
        } catch (\Exception $e) {
            \Log::error('Error in exportFeedbackDetails: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Export failed: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    private function exportExcelFeedbackDetails($data, $request)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("Sargam LMS")
            ->setTitle("Faculty Feedback Detailed Report")
            ->setSubject("Detailed feedback report with all individual responses")
            ->setDescription("Export of all individual faculty feedback responses")
            ->setKeywords("feedback faculty students detailed report")
            ->setCategory("Report");

        // Set default font
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $programId = $request->input('program_id');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'current');
        $facultyType = $request->input('faculty_type', []);
        $facultyTypeText = !empty($facultyType) ?
            (count($facultyType) === 2 ? 'All Types' : (in_array('1', $facultyType) ? 'Internal' : 'Guest')) :
            'All Types';
        $dateRange = ($fromDate && $toDate) ?
            Carbon::parse($fromDate)->format('d-M-Y') . ' to ' . Carbon::parse($toDate)->format('d-M-Y') :
            'All Dates';
        $programLabel = $programId ? $this->getProgramName($programId) : 'All Programs';

        $row = 1;
        $sheet->getRowDimension(1)->setRowHeight(52);
        $sheet->getColumnDimension('A')->setWidth(11);

        $emblemPath = public_path('images/ashoka.png');
        if (is_file($emblemPath)) {
            $drawingEmblem = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawingEmblem->setName('Emblem');
            $drawingEmblem->setDescription('Government emblem');
            $drawingEmblem->setPath($emblemPath);
            $drawingEmblem->setHeight(48);
            $drawingEmblem->setCoordinates('A1');
            $drawingEmblem->setWorksheet($sheet);
        }

        $logoPath = public_path('admin_assets/images/logos/logo.png');
        if (is_file($logoPath)) {
            $drawingLogo = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawingLogo->setName('LBSNAA Logo');
            $drawingLogo->setDescription('LBSNAA');
            $drawingLogo->setPath($logoPath);
            $drawingLogo->setHeight(48);
            $drawingLogo->setCoordinates('M1');
            $drawingLogo->setWorksheet($sheet);
        }

        $sheet->mergeCells('B1:K1');
        $sheet->setCellValue('B1', 'Government of India');
        $sheet->getStyle('B1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '004A93']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM],
        ]);

        $sheet->mergeCells('B2:K2');
        $sheet->setCellValue('B2', 'OFFICER\'S MESS LBSNAA MUSSOORIE');
        $sheet->getStyle('B2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1A1A1A']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);

        $sheet->mergeCells('B3:K3');
        $sheet->setCellValue('B3', 'Lal Bahadur Shastri National Academy of Administration');
        $sheet->getStyle('B3')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP],
        ]);

        $sheet->getStyle('A3:N3')->applyFromArray([
            'borders' => ['bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                'color' => ['rgb' => '004A93'],
            ]],
        ]);

        $row = 5;
        $sheet->setCellValue('A' . $row, 'Faculty Feedback with Comments — All Details');
        $sheet->mergeCells('A' . $row . ':N' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '1A1A1A']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        $printed = now()->format('d-M-Y H:i');
        $metaLine = 'Program: ' . $programLabel
            . '  |  Course status: ' . ($courseType === 'current' ? 'Current courses' : 'Archived courses')
            . '  |  Dates: ' . $dateRange
            . '  |  Faculty: ' . ($facultyName ?: '—')
            . '  |  Faculty type: ' . $facultyTypeText
            . '  |  Total records: ' . count($data)
            . '  |  Printed: ' . $printed;

        $sheet->setCellValue('A' . $row, $metaLine);
        $sheet->mergeCells('A' . $row . ':N' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '333333']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);
        $row += 2;

        // COLUMN HEADERS
        $headers = [
            'S.No.',
            'Course Name',
            'Course Status',
            'Faculty Name',
            'Faculty Type',
            'Topic',
            'Session Date',
            'Session Time',
            'OT Name',
            'OT Code',
            'Content Rating',
            'Presentation Rating',
            'Remarks',
            'Feedback Date'
        ];

        $headerRow = $row;
        foreach ($headers as $index => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . $headerRow;
            $sheet->setCellValue($cell, $header);
        }

        // Style headers (LBSNAA / print theme blue)
        $sheet->getStyle('A' . $headerRow . ':N' . $headerRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '004A93']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        // DATA ROWS
        $ratingColors = [
            '5' => '198754',
            '4' => '20c997',
            '3' => 'ffc107',
            '2' => 'fd7e14',
            '1' => 'dc3545',
        ];

        foreach ($data as $index => $item) {
            $item['S.No.'] = $index + 1;

            foreach ($headers as $colIndex => $header) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $row;
                $value = $item[$header] ?? '';
                $sheet->setCellValue($cell, $value);

                // Apply special formatting for rating columns
                if ($header === 'Content Rating' || $header === 'Presentation Rating') {
                    $rating = strval($value);
                    if (isset($ratingColors[$rating])) {
                        $sheet->getStyle($cell)->applyFromArray([
                            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $ratingColors[$rating]]],
                            'font' => ['bold' => true, 'color' => ['rgb' => in_array($rating, ['3']) ? '000000' : 'FFFFFF']],
                            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                        ]);
                    }
                }

                // Wrap text for remarks
                if ($header === 'Remarks') {
                    $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
                }
            }

            // Add borders to the row
            $sheet->getStyle('A' . $row . ':N' . $row)->applyFromArray([
                'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            ]);

            $row++;

            // Add a blank row after every 10 records for readability
            if (($index + 1) % 10 === 0) {
                $row++;
            }
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);  // S.No.
        $sheet->getColumnDimension('B')->setWidth(25); // Course Name
        $sheet->getColumnDimension('C')->setWidth(12); // Course Status
        $sheet->getColumnDimension('D')->setWidth(25); // Faculty Name
        $sheet->getColumnDimension('E')->setWidth(12); // Faculty Type
        $sheet->getColumnDimension('F')->setWidth(30); // Topic
        $sheet->getColumnDimension('G')->setWidth(12); // Session Date
        $sheet->getColumnDimension('H')->setWidth(15); // Session Time
        $sheet->getColumnDimension('I')->setWidth(25); // OT Name
        $sheet->getColumnDimension('J')->setWidth(15); // OT Code
        $sheet->getColumnDimension('K')->setWidth(15); // Content Rating
        $sheet->getColumnDimension('L')->setWidth(18); // Presentation Rating
        $sheet->getColumnDimension('M')->setWidth(40); // Remarks
        $sheet->getColumnDimension('N')->setWidth(18); // Feedback Date

        // Auto-size remarks column
        $sheet->getColumnDimension('M')->setAutoSize(true);

        // Freeze header row
        $sheet->freezePane('A' . ($headerRow + 1));

        // Add footer
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Confidential — For internal use only | LBSNAA Mussoorie | Sargam Faculty Feedback');
        $sheet->mergeCells('A' . $row . ':N' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // Create writer and save
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'feedback_details_' . date('Y_m_d_H_i') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'feedback_details_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function exportPdfFeedbackDetails($data, $request)
    {
        // Add serial numbers to data
        $data = $data->map(function ($item, $index) {
            $item['S.No.'] = $index + 1;
            return $item;
        });

        // Get filter values for PDF
        $programId = $request->input('program_id');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'current');
        $facultyType = $request->input('faculty_type', []);

        $filters = [
            'course_status' => $courseType === 'current' ? 'Current courses' : 'Archived courses',
            'program' => $programId ? $this->getProgramName($programId) : 'All Programs',
            'faculty_name' => $facultyName ?: '—',
            'faculty_type' => !empty($facultyType) ?
                (count($facultyType) === 2 ? 'All types' : (in_array('1', $facultyType) ? 'Internal' : 'Guest')) :
                'All types',
            'date_range' => ($fromDate && $toDate) ?
                Carbon::parse($fromDate)->format('d-M-Y') . ' to ' . Carbon::parse($toDate)->format('d-M-Y') :
                '— to —',
            'total_records' => count($data),
        ];

        $exportDate = now()->format('d-M-Y H:i');

        $pdfData = [
            'data' => $data,
            'emblem_src' => $this->feedbackReportBrandingImageDataUri(public_path('images/ashoka.png')),
            'logo_src' => $this->feedbackReportBrandingImageDataUri(public_path('admin_assets/images/logos/logo.png')),
            'filters' => $filters,
            'export_date' => $exportDate,
            'rating_colors' => [
                '5' => '#198754',
                '4' => '#20c997',
                '3' => '#ffc107',
                '2' => '#fd7e14',
                '1' => '#dc3545',
            ],
        ];

        // Load PDF view
        $pdf = PDF::loadView('admin.feedback.feedback_details_pdf', $pdfData)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'dpi' => 96,
                'margin_top' => 20,
                'margin_right' => 10,
                'margin_bottom' => 15,
                'margin_left' => 10,
            ]);

        return $pdf->download('feedback_details_' . date('Y_m_d_H_i') . '.pdf');
    }


    public function pendingStudents()
    {
        try {
            // Get active courses (currently running: end_date >= today)
            $activeCourses = Cache::remember('pending_feedback_active_courses', 3600, function () {
                return DB::table('course_master')
                    ->where('active_inactive', 1)
                    ->whereDate('end_date', '>=', now()->toDateString())
                    ->orderBy('course_name')
                    ->pluck('course_name', 'pk');
            });

            // Get archived courses (ended or deactivated)
            $archiveCourses = Cache::remember('pending_feedback_archive_courses', 3600, function () {
                return DB::table('course_master')
                    ->where(function ($q) {
                        $q->whereDate('end_date', '<', now()->toDateString())
                          ->orWhere('active_inactive', 0);
                    })
                    ->orderBy('course_name')
                    ->pluck('course_name', 'pk');
            });

            // Keep combined courses for backward compat
            $courses = $activeCourses;

            $sessions = Cache::remember('pending_feedback_sessions', 3600, function () {
                return DB::table('timetable')
                    ->select('pk', 'subject_topic', 'START_DATE')
                    ->where('active_inactive', 1)
                    ->where('feedback_checkbox', 1)
                    ->orderBy('START_DATE', 'desc')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $label = $item->subject_topic;
                        if ($item->START_DATE) {
                            $label .= ' (' . date('d-m-Y', strtotime($item->START_DATE)) . ')';
                        }
                        return [$item->pk => $label];
                    });
            });

            // Determine active course (latest course with pending feedback sessions)
            $activeCourse = DB::table('timetable as t')
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
                ->where('t.feedback_checkbox', 1)
                ->where('t.START_DATE', '<=', now())
                ->where('c.active_inactive', 1)
                ->orderBy('t.START_DATE', 'desc')
                ->value('c.pk');

            return view('admin.feedback.pending_students', compact('courses', 'activeCourses', 'archiveCourses', 'sessions', 'activeCourse'));
        } catch (\Exception $e) {
            \Log::error('Pending Students View Error: ' . $e->getMessage());
            return back()->with('error', 'Error loading page: ' . $e->getMessage());
        }
    }

    /**
     * Pending rows per timetable row (faculty slots minus submitted feedback).
     */
    private function pendingStudentsPendingExpressionSql(): string
    {
        return '(CASE WHEN JSON_VALID(t.faculty_master) THEN JSON_LENGTH(t.faculty_master) ELSE 1 END - COALESCE(tf.submitted_count, 0))';
    }

    /**
     * Base join for pending-feedback-by-student (one row per student × session with attendance).
     */
    private function buildPendingStudentsGroupedBaseQuery(Request $request): \Illuminate\Database\Query\Builder
    {
        $feedbackSub = DB::raw("(
            SELECT timetable_pk, student_master_pk, COUNT(*) as submitted_count
            FROM topic_feedback
            WHERE is_submitted = 1
            GROUP BY timetable_pk, student_master_pk
        ) as tf");

        $query = DB::table('timetable as t')
            ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
            ->join('student_master_course__map as smcm', function ($join) {
                $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                    ->where('smcm.active_inactive', 1);
            })
            ->join('student_master as sm', 'sm.pk', '=', 'smcm.student_master_pk')
            ->join('course_student_attendance as csa', function ($join) {
                $join->on('csa.timetable_pk', '=', 't.pk')
                    ->on('csa.Student_master_pk', '=', 'sm.pk')
                    ->where('csa.status', '1');
            })
            ->leftJoin($feedbackSub, function ($join) {
                $join->on('tf.timetable_pk', '=', 't.pk')
                    ->on('tf.student_master_pk', '=', 'sm.pk');
            })
            ->where('t.feedback_checkbox', 1)
            ->where('t.START_DATE', '<=', now());

        if ($request->filled('course_pk')) {
            $query->where('t.course_master_pk', $request->course_pk);
        } elseif ($request->input('course_type') === 'archive') {
            $query->where(function ($q) {
                $q->whereDate('c.end_date', '<', now()->toDateString())
                    ->orWhere('c.active_inactive', 0);
            });
        } else {
            $query->where('c.active_inactive', 1)
                ->whereDate('c.end_date', '>=', now()->toDateString());
        }

        if ($request->filled('session_id')) {
            $query->where('t.pk', $request->session_id);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('t.START_DATE', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('t.START_DATE', '<=', $request->to_date);
        }

        return $query;
    }

    /**
     * One row per student with aggregate counts and course list (for HAVING / pagination).
     */
    private function buildPendingStudentsAggregateSubquery(Request $request): \Illuminate\Database\Query\Builder
    {
        $pExpr = $this->pendingStudentsPendingExpressionSql();

        $aggSub = (clone $this->buildPendingStudentsGroupedBaseQuery($request))
            ->select([
                'sm.pk as student_pk',
                DB::raw("TRIM(CONCAT(COALESCE(sm.first_name,''),' ',COALESCE(sm.middle_name,''),' ',COALESCE(sm.last_name,''))) as student_name"),
                'sm.email',
                'sm.generated_OT_code',
                DB::raw("SUM(CASE WHEN {$pExpr} > 0 THEN 1 ELSE 0 END) as feedback_not_given"),
                DB::raw("SUM(CASE WHEN {$pExpr} <= 0 THEN 1 ELSE 0 END) as feedback_given"),
                DB::raw("GROUP_CONCAT(DISTINCT c.course_name ORDER BY c.course_name SEPARATOR ', ') as course_summary_build"),
            ])
            ->groupBy('sm.pk', 'sm.first_name', 'sm.middle_name', 'sm.last_name', 'sm.email', 'sm.generated_OT_code');

        $state = $request->input('filter_feedback_state', 'not_given');
        if ($state === 'given') {
            $aggSub->havingRaw("SUM(CASE WHEN {$pExpr} > 0 THEN 1 ELSE 0 END) = 0")
                ->havingRaw("SUM(CASE WHEN {$pExpr} <= 0 THEN 1 ELSE 0 END) >= 1");
        } else {
            $aggSub->havingRaw("SUM(CASE WHEN {$pExpr} > 0 THEN 1 ELSE 0 END) >= 1");
        }

        return $aggSub;
    }

    /**
     * Attach session rows for each aggregate row (same order as $aggRows).
     *
     * @param  \Illuminate\Support\Collection<int, object>  $aggRows
     */
    private function mergePendingGroupedAggregatesWithDetailRows(Request $request, $aggRows): array
    {
        if ($aggRows->isEmpty()) {
            return [];
        }

        $pks = $aggRows->pluck('student_pk')->all();

        $detailRows = $this->buildPendingStudentsGroupedBaseQuery($request)
            ->whereIn('sm.pk', $pks)
            ->select([
                'sm.pk as student_pk',
                't.pk as timetable_pk',
                't.subject_topic as session_name',
                't.START_DATE as date',
                't.class_session as time',
                'c.course_name',
                DB::raw('CASE WHEN JSON_VALID(t.faculty_master) THEN JSON_LENGTH(t.faculty_master) ELSE 1 END as faculty_count'),
                DB::raw('COALESCE(tf.submitted_count, 0) as submitted_count'),
            ])
            ->orderByRaw("TRIM(CONCAT(COALESCE(sm.first_name,''),' ',COALESCE(sm.middle_name,''),' ',COALESCE(sm.last_name,'')))")
            ->orderBy('t.START_DATE')
            ->get();

        $studentsByPk = [];
        foreach ($aggRows as $r) {
            $studentsByPk[$r->student_pk] = [
                'student_name' => $r->student_name,
                'email' => $r->email,
                'ot_code' => $r->generated_OT_code,
                'feedback_given' => (int) $r->feedback_given,
                'feedback_not_given' => (int) $r->feedback_not_given,
                'sessions' => [],
            ];
        }

        foreach ($detailRows as $row) {
            $pending = (int) $row->faculty_count - (int) $row->submitted_count;
            $status = $pending > 0 ? 'not_given' : 'given';
            $pk = $row->student_pk;
            if (!isset($studentsByPk[$pk])) {
                continue;
            }
            $studentsByPk[$pk]['sessions'][] = [
                'session_name' => $row->session_name,
                'date' => $row->date ? date('d-m-Y', strtotime($row->date)) : '—',
                'time' => $row->time ?? '—',
                'course_name' => $row->course_name,
                'feedback_status' => $status,
            ];
        }

        $ordered = [];
        foreach ($aggRows as $r) {
            $ordered[] = $studentsByPk[$r->student_pk];
        }

        return $this->enrichGroupedStudentsWithCourseSummary($ordered);
    }

    /**
     * Return student-grouped pending feedback data for accordion view.
     * Uses SQL aggregation + pagination (no full scan into PHP).
     */
    public function pendingStudentsGroupedData(Request $request)
    {
        try {
            $aggSub = $this->buildPendingStudentsAggregateSubquery($request);

            $outer = DB::query()->fromSub($aggSub, 'agg_students');

            if ($request->filled('search')) {
                $raw = '%' . addcslashes(mb_strtolower(trim($request->search)), '%_\\') . '%';
                $outer->where(function ($q) use ($raw) {
                    $q->whereRaw('LOWER(agg_students.student_name) LIKE ?', [$raw])
                        ->orWhereRaw('LOWER(agg_students.email) LIKE ?', [$raw])
                        ->orWhereRaw('LOWER(agg_students.generated_OT_code) LIKE ?', [$raw])
                        ->orWhereRaw('LOWER(COALESCE(agg_students.course_summary_build, "")) LIKE ?', [$raw]);
                });
            }

            $totalStudents = (clone $outer)->count();

            $sortBy = $request->input('sort_by', 'student_name');
            $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
            $sortMap = [
                'student_name' => 'student_name',
                'course_summary' => 'course_summary_build',
                'feedback_given' => 'feedback_given',
                'feedback_not_given' => 'feedback_not_given',
            ];
            $sortCol = $sortMap[$sortBy] ?? 'student_name';

            $perPage = max(1, min((int) ($request->per_page ?: 20), 100));
            $page = max(1, (int) ($request->page ?: 1));
            $totalPages = $totalStudents > 0 ? (int) ceil($totalStudents / $perPage) : 1;
            $page = min($page, max($totalPages, 1));
            $offset = ($page - 1) * $perPage;

            $pageRows = (clone $outer)->orderBy($sortCol, $sortDir)->offset($offset)->limit($perPage)->get();

            if ($pageRows->isEmpty()) {
                return response()->json([
                    'students' => [],
                    'total' => $totalStudents,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => $totalPages,
                ]);
            }

            $paginatedStudents = $this->mergePendingGroupedAggregatesWithDetailRows($request, $pageRows);

            return response()->json([
                'students' => $paginatedStudents,
                'total' => $totalStudents,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
            ]);
        } catch (\Exception $e) {
            \Log::error('Pending Students Grouped Data Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load grouped data'], 500);
        }
    }



    public function getSessionsByCourse(Request $request)
    {
        try {
            $courseId = $request->course_pk;

            if (!$courseId) {
                return response()->json([]);
            }

            $sessions = DB::table('timetable')
                ->select('pk', 'subject_topic', 'START_DATE')
                ->where('course_master_pk', $courseId)
                ->where('active_inactive', 1)
                ->where('feedback_checkbox', 1)
                ->orderBy('START_DATE', 'desc')
                ->get()
                ->map(function ($item) {
                    $item->label = $item->subject_topic;
                    if ($item->START_DATE) {
                        $item->label .= ' (' . date('d-m-Y', strtotime($item->START_DATE)) . ')';
                    }
                    return $item;
                });

            return response()->json($sessions);
        } catch (\Exception $e) {
            \Log::error('Get Sessions Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load sessions'], 500);
        }
    }




    public function exportPendingStudentsPDF(Request $request)
    {
        try {
            set_time_limit(600);
            ini_set('memory_limit', '2048M');

            $data = $this->buildGroupedExportData($request);

            if (empty($data['students'])) {
                return back()->with('error', 'No pending feedback records found.');
            }

            $data['mode'] = 'pdf';

            $pdf = Pdf::loadView('admin.feedback.pending_students_export', $data)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'sans-serif',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'dpi' => 96,
                    'margin_top' => 8,
                    'margin_right' => 8,
                    'margin_bottom' => 8,
                    'margin_left' => 8,
                    'enable_php' => false,
                    'enable_javascript' => false,
                    'enable_css_float' => true,
                ]);

            return $pdf->download('pending_feedback_' . date('Y-m-d_His') . '.pdf');
        } catch (\Exception $e) {
            \Log::error('PDF Export Error: ' . $e->getMessage());
            $errorMessage = 'Failed to export PDF: ' . $e->getMessage();
            if (str_contains($e->getMessage(), 'memory')) {
                $errorMessage = 'Memory limit exceeded. Please apply more filters to reduce the dataset size.';
            }
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Print view — renders the same template in browser for printing.
     */
    public function printPendingStudents(Request $request)
    {
        try {
            $data = $this->buildGroupedExportData($request);
            $data['mode'] = 'print';

            return view('admin.feedback.pending_students_export', $data);
        } catch (\Exception $e) {
            \Log::error('Print View Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate print view: ' . $e->getMessage());
        }
    }

    /**
     * Helper method to get session name
     */
    private function getSessionName($sessionId)
    {
        if (!$sessionId) return 'All Sessions';

        static $sessionCache = [];

        if (!isset($sessionCache[$sessionId])) {
            $sessionCache[$sessionId] = DB::table('timetable')
                ->where('pk', $sessionId)
                ->value('subject_topic');
        }

        return $sessionCache[$sessionId] ?? 'Unknown Session';
    }

    /**
     * Export to Excel — student-grouped view with LBSNAA header
     */
    public function exportPendingStudentsExcel(Request $request)
    {
        try {
            $data = $this->buildGroupedExportData($request);

            if (empty($data['students'])) {
                return back()->with('error', 'No pending feedback records found.');
            }

            return Excel::download(
                new PendingFeedbackExport($data['students'], $data['filters'], $data['export_date'], false),
                'pending_feedback_summary_' . now()->format('Y-m-d_H-i') . '.xlsx'
            );
        } catch (\Exception $e) {
            \Log::error('Excel Export Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Excel export with per-session rows under each student (accordion-style detail).
     */
    public function exportPendingStudentsExcelDetailed(Request $request)
    {
        try {
            $data = $this->buildGroupedExportData($request);

            if (empty($data['students'])) {
                return back()->with('error', 'No pending feedback records found.');
            }

            return Excel::download(
                new PendingFeedbackExport($data['students'], $data['filters'], $data['export_date'], true),
                'pending_feedback_with_details_' . now()->format('Y-m-d_H-i') . '.xlsx'
            );
        } catch (\Exception $e) {
            \Log::error('Excel Detailed Export Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Build student-grouped data array for PDF / Excel / Print exports.
     */
    private function buildGroupedExportData(Request $request): array
    {
        $aggSub = $this->buildPendingStudentsAggregateSubquery($request);
        $rows = $aggSub->orderBy('student_name')->get();
        $students = $this->mergePendingGroupedAggregatesWithDetailRows($request, $rows);

        return [
            'students' => $students,
            'export_date' => now()->format('d-m-Y H:i:s'),
            'filters' => [
                'course' => $this->getCourseName($request->course_pk),
                'session' => $request->session_id ? $this->getSessionName($request->session_id) : 'All Sessions',
                'from_date' => $request->from_date ? date('d-m-Y', strtotime($request->from_date)) : 'All',
                'to_date' => $request->to_date ? date('d-m-Y', strtotime($request->to_date)) : 'All',
                'course_scope' => $request->input('course_type') === 'archive' ? 'Archive' : 'Active',
                'feedback_state' => $this->formatFeedbackStateFilterLabel($request->input('filter_feedback_state', 'not_given')),
            ],
        ];
    }

    private function formatFeedbackStateFilterLabel(?string $value): string
    {
        return match ($value) {
            'given' => 'Feedback: Given (all completed in scope)',
            default => 'Feedback: Not given (has pending)',
        };
    }

    /**
     * Unique course names per student (for table, Excel, PDF).
     */
    private function enrichGroupedStudentsWithCourseSummary(array $students): array
    {
        foreach ($students as &$s) {
            $uniq = [];
            foreach ($s['sessions'] ?? [] as $sess) {
                if (!empty($sess['course_name'])) {
                    $uniq[$sess['course_name']] = true;
                }
            }
            $s['course_summary'] = empty($uniq) ? '—' : implode(', ', array_keys($uniq));
        }
        unset($s);

        return $students;
    }

   
    private function buildExportQuery()
    {
        //  Pre-aggregated feedback (FAST)
        $feedbackSub = DB::raw("
        (
            SELECT 
                timetable_pk,
                student_master_pk,
                COUNT(*) as submitted_count
            FROM topic_feedback
            WHERE is_submitted = 1
            GROUP BY timetable_pk, student_master_pk
        ) as tf
    ");

        return DB::table('timetable as t')

            ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')

            ->join('venue_master as v', 't.venue_id', '=', 'v.venue_id')

            ->join('student_master_course__map as smcm', function ($join) {
                $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                    ->where('smcm.active_inactive', 1);
            })

            ->join('student_master as sm', 'sm.pk', '=', 'smcm.student_master_pk')

            ->join('course_student_attendance as csa', function ($join) {
                $join->on('csa.timetable_pk', '=', 't.pk')
                    ->on('csa.Student_master_pk', '=', 'sm.pk')
                    ->where('csa.status', '1');
            })

            //  FAST feedback join
            ->leftJoin($feedbackSub, function ($join) {
                $join->on('tf.timetable_pk', '=', 't.pk')
                    ->on('tf.student_master_pk', '=', 'sm.pk');
            })

            ->select([
                't.pk as timetable_pk',
                't.subject_topic',
                'c.course_name',
                'v.venue_name',

                //  simplified (correct instead of wrong JSON[0])
                DB::raw("'Multiple Faculty' as faculty_name"),

                DB::raw("TRIM(CONCAT(
                COALESCE(sm.first_name, ''),
                ' ',
                COALESCE(sm.middle_name, ''),
                ' ',
                COALESCE(sm.last_name, '')
            )) as student_name"),

                'sm.email',
                'sm.contact_no',
                'sm.generated_OT_code',

                't.START_DATE as from_date',
                't.END_DATE as to_date',
                't.class_session',

                //  correct pending logic
                DB::raw("
                (
                    CASE 
                        WHEN JSON_VALID(t.faculty_master) 
                        THEN JSON_LENGTH(t.faculty_master)
                        ELSE 1
                    END
                )
                -
                COALESCE(tf.submitted_count, 0)
                as pending_count
            ")
            ])

            ->where('t.feedback_checkbox', 1)

            ->where('t.START_DATE', '<=', now())

            ->whereRaw("
            TIMESTAMP(
                t.END_DATE,
                STR_TO_DATE(
                    TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                    '%h:%i %p'
                )
            ) <= NOW()
        ")

            //  only export pending rows
            ->havingRaw('pending_count > 0');
    }

    /**
     * Apply filters to export query
     */
    private function applyExportFilters($query, Request $request)
    {
        // Log incoming filters for debugging
        \Log::info('Export Filters Applied:', $request->all());

        // Course filter
        if ($request->filled('course_pk')) {
            $query->where('t.course_master_pk', $request->course_pk);
        }

        // Session filter (timetable pk)
        if ($request->filled('session_id')) {
            $query->where('t.pk', $request->session_id);
        }

        // Date filters
        if ($request->filled('from_date')) {
            $query->whereDate('t.START_DATE', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('t.START_DATE', '<=', $request->to_date);
        }

        // Global search term (from DataTables search box)
        if ($request->filled('ot_name') && !empty($request->ot_name)) {
            $searchTerm = $request->ot_name;
            $query->where(function ($q) use ($searchTerm) {
                $q->where(DB::raw("TRIM(CONCAT(
                COALESCE(sm.first_name, ''),
                ' ',
                COALESCE(sm.middle_name, ''),
                ' ',
                COALESCE(sm.last_name, '')
            ))"), 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('sm.email', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('sm.contact_no', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('sm.generated_OT_code', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('t.subject_topic', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('c.course_name', 'LIKE', '%' . $searchTerm . '%');
            });
        }
    }

    /**
     * Get course name helper
     */
    private function getCourseName($coursePk)
    {
        if (!$coursePk) {
            return 'All Courses';
        }

        $course = DB::table('course_master')
            ->where('pk', $coursePk)
            ->first();

        return $course ? $course->course_name : 'Unknown Course';
    }

    /**
     * DataTable AJAX endpoint
     */
    public function pendingStudentsDataTable(PendingFeedbackDataTable $dataTable)
    {
        try {
            return $dataTable->ajax();
        } catch (\Exception $e) {
            \Log::error('DataTable Error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error loading data: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Display pending feedback summary by user
     */
    /**
     * Pending Feedback Summary View
     */
    public function pendingFeedbackSummary(PendingFeedbackSummaryDataTable $dataTable)
    {
        try {
            $currentDate = Carbon::now()->format('Y-m-d');
            $roleCourseIds = get_Role_by_course();

            $coursesActive = Cache::remember(
                'pending_feedback_courses_active_' . md5($currentDate . json_encode($roleCourseIds)),
                3600,
                function () use ($currentDate, $roleCourseIds) {
                    $q = DB::table('course_master')
                        ->where('active_inactive', 1)
                        ->where(function ($sub) use ($currentDate) {
                            $sub->whereNull('end_date')
                                ->orWhere('end_date', '>=', $currentDate);
                        })
                        ->orderBy('course_name');

                    if (!empty($roleCourseIds)) {
                        $q->whereIn('pk', $roleCourseIds);
                    }

                    return $q->pluck('course_name', 'pk');
                }
            );

            $coursesArchive = Cache::remember(
                'pending_feedback_courses_archive_' . md5($currentDate . json_encode($roleCourseIds)),
                3600,
                function () use ($currentDate, $roleCourseIds) {
                    $q = DB::table('course_master')
                        ->where('active_inactive', 1)
                        ->whereNotNull('end_date')
                        ->where('end_date', '<', $currentDate)
                        ->orderBy('course_name');

                    if (!empty($roleCourseIds)) {
                        $q->whereIn('pk', $roleCourseIds);
                    }

                    return $q->pluck('course_name', 'pk');
                }
            );

            $defaultCoursePk = $coursesActive->keys()->first();

            $sessions = Cache::remember('pending_feedback_sessions', 3600, function () {
                return DB::table('timetable')
                    ->select('pk', 'subject_topic', 'START_DATE')
                    ->where('active_inactive', 1)
                    ->where('feedback_checkbox', 1)
                    ->orderBy('START_DATE', 'desc')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $label = $item->subject_topic;
                        if ($item->START_DATE) {
                            $label .= ' (' . date('d-m-Y', strtotime($item->START_DATE)) . ')';
                        }
                        return [$item->pk => $label];
                    });
            });

            return $dataTable->render('admin.feedback.pending_feedback_summary', [
                'coursesActive' => $coursesActive,
                'coursesArchive' => $coursesArchive,
                'defaultCoursePk' => $defaultCoursePk,
                'sessions' => $sessions,
            ]);
        } catch (\Exception $e) {
            \Log::error('Pending Feedback Summary View Error: ' . $e->getMessage());
            return back()->with('error', 'Error loading page: ' . $e->getMessage());
        }
    }

    /**
     * Get sessions by course for summary (AJAX)
     */
    public function getSessionsByCourseForSummary(Request $request)
    {
        try {
            $courseId = $request->course_pk;

            if (!$courseId) {
                return response()->json([]);
            }

            $sessions = DB::table('timetable')
                ->select('pk', 'subject_topic', 'START_DATE')
                ->where('course_master_pk', $courseId)
                ->where('active_inactive', 1)
                ->where('feedback_checkbox', 1)
                ->orderBy('START_DATE', 'desc')
                ->get()
                ->map(function ($item) {
                    $item->label = $item->subject_topic;
                    if ($item->START_DATE) {
                        $item->label .= ' (' . date('d-m-Y', strtotime($item->START_DATE)) . ')';
                    }
                    return $item;
                });

            return response()->json($sessions);
        } catch (\Exception $e) {
            \Log::error('Get Sessions Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load sessions'], 500);
        }
    }

    /**
     * DataTable AJAX endpoint for summary
     */
    public function pendingFeedbackSummaryDataTable(PendingFeedbackSummaryDataTable $dataTable)
    {
        try {
            return $dataTable->ajax();
        } catch (\Exception $e) {
            \Log::error('Summary DataTable Error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'Error loading data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export Summary to Excel
     */
    public function exportPendingSummaryExcel(Request $request)
    {
        try {
            $query = $this->buildSummaryExportQuery();
            $this->applySummaryExportFilters($query, $request);

            $students = $query->orderBy('pending_count', 'desc')->get();

            if ($students->isEmpty()) {
                return back()->with('error', 'No pending feedback records found.');
            }

            return Excel::download(
                new PendingFeedbackSummaryExport($students, $request->all()),
                'pending_feedback_summary_' . now()->format('Y-m-d_H-i') . '.xlsx'
            );
        } catch (\Exception $e) {
            \Log::error('Summary Excel Export Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Export summary to Excel with one row per session (pending breakdown).
     */
    public function exportPendingSummaryExcelDetailed(Request $request)
    {
        try {
            $query = $this->buildSummaryDetailExportQuery();
            $this->applySummaryExportFilters($query, $request);

            $students = $query
                ->orderBy('sm.first_name')
                ->orderBy('sm.last_name')
                ->orderBy('c.course_name')
                ->orderBy('t.START_DATE')
                ->orderBy('t.pk')
                ->get();

            if ($students->isEmpty()) {
                return back()->with('error', 'No pending feedback records found.');
            }

            return Excel::download(
                new PendingFeedbackSummaryExport($students, $request->all(), true),
                'pending_feedback_summary_with_details_' . now()->format('Y-m-d_H-i') . '.xlsx'
            );
        } catch (\Exception $e) {
            \Log::error('Summary Excel Detailed Export Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Export Summary to PDF
     */
    /**
     * Export Summary to PDF
     */
    public function exportPendingSummaryPDF(Request $request)
    {
        try {
            set_time_limit(300);
            ini_set('memory_limit', '1024M');

            $query = $this->buildSummaryExportQuery();
            $this->applySummaryExportFilters($query, $request);

            $students = $query->orderBy('pending_count', 'desc')->get();

            if ($students->isEmpty()) {
                return back()->with('error', 'No pending feedback records found.');
            }

            // Get course name if course filter is applied
            $courseName = null;
            if ($request->filled('filter_course_pk')) {
                $course = DB::table('course_master')
                    ->where('pk', $request->filter_course_pk)
                    ->first();
                $courseName = $course ? $course->course_name : null;
            }

            // Get session name if session filter is applied
            $sessionName = null;
            if ($request->filled('filter_session_id')) {
                $session = DB::table('timetable')
                    ->where('pk', $request->filter_session_id)
                    ->first();
                if ($session) {
                    $sessionName = $session->subject_topic;
                    if ($session->START_DATE) {
                        $sessionName .= ' (' . date('d-m-Y', strtotime($session->START_DATE)) . ')';
                    }
                }
            }

            $pdf = Pdf::loadView('admin.feedback.pending_feedback_summary_pdf', [
                'students' => $students,
                'export_date' => now()->format('d-m-Y H:i:s'),
                'total_count' => $students->count(),
                'filters' => $request->all(),
                'courseName' => $courseName,
                'sessionName' => $sessionName  // Pass session name to view
            ])->setPaper('A4', 'landscape');

            return $pdf->download('pending_feedback_summary_' . now()->format('Ymd_His') . '.pdf');
        } catch (\Exception $e) {
            \Log::error('Summary PDF Export Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to export PDF: ' . $e->getMessage());
        }
    }

    private function buildSummaryExportQuery()
    {
        //  Pre-aggregated feedback subquery
        $feedbackSub = DB::raw("
        (
            SELECT 
                timetable_pk,
                student_master_pk,
                COUNT(*) as submitted_count
            FROM topic_feedback
            WHERE is_submitted = 1
            GROUP BY timetable_pk, student_master_pk
        ) as tf
    ");

        return DB::table('timetable as t')

            ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')

            ->join('student_master_course__map as smcm', function ($join) {
                $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                    ->where('smcm.active_inactive', 1);
            })

            ->join('student_master as sm', 'sm.pk', '=', 'smcm.student_master_pk')

            ->join('course_student_attendance as csa', function ($join) {
                $join->on('csa.timetable_pk', '=', 't.pk')
                    ->on('csa.Student_master_pk', '=', 'sm.pk')
                    ->where('csa.status', '1');
            })

            //  FAST feedback join
            ->leftJoin($feedbackSub, function ($join) {
                $join->on('tf.timetable_pk', '=', 't.pk')
                    ->on('tf.student_master_pk', '=', 'sm.pk');
            })

            ->select([
                DB::raw("CONCAT(
                COALESCE(sm.first_name, ''),
                ' ',
                COALESCE(sm.middle_name, ''),
                ' ',
                COALESCE(sm.last_name, '')
            ) as user_name"),

                'sm.email',
                'sm.contact_no',
                'c.course_name',

                DB::raw("'Multiple Sessions' as session_info"),

                DB::raw("CONCAT(
                DATE_FORMAT(MIN(t.START_DATE), '%d-%m-%Y'),
                ' to ',
                DATE_FORMAT(MAX(t.START_DATE), '%d-%m-%Y')
            ) as date_range"),

                //  FINAL CORRECT + FAST pending count
                DB::raw("
                SUM(
                    (
                        CASE 
                            WHEN JSON_VALID(t.faculty_master) 
                            THEN JSON_LENGTH(t.faculty_master)
                            ELSE 1
                        END
                    )
                    -
                    COALESCE(tf.submitted_count, 0)
                ) as pending_count
            ")
            ])

            ->where('t.feedback_checkbox', 1)

            //  TIME FILTER (correct version)
            ->whereRaw("
            TIMESTAMP(
                t.END_DATE,
                STR_TO_DATE(
                    TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                    '%h:%i %p'
                )
            ) <= NOW()
        ")

            ->groupBy(
                'sm.pk',
                'sm.first_name',
                'sm.middle_name',
                'sm.last_name',
                'sm.email',
                'sm.contact_no',
                'c.course_name'
            )

            //  only export users with pending
            ->havingRaw('pending_count > 0');
    }

    /**
     * Per-session rows for detailed Excel export (same filters as summary, no student+course aggregation).
     */
    private function buildSummaryDetailExportQuery()
    {
        $feedbackSub = DB::raw("
        (
            SELECT 
                timetable_pk,
                student_master_pk,
                COUNT(*) as submitted_count
            FROM topic_feedback
            WHERE is_submitted = 1
            GROUP BY timetable_pk, student_master_pk
        ) as tf
    ");

        return DB::table('timetable as t')

            ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')

            ->join('student_master_course__map as smcm', function ($join) {
                $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                    ->where('smcm.active_inactive', 1);
            })

            ->join('student_master as sm', 'sm.pk', '=', 'smcm.student_master_pk')

            ->join('course_student_attendance as csa', function ($join) {
                $join->on('csa.timetable_pk', '=', 't.pk')
                    ->on('csa.Student_master_pk', '=', 'sm.pk')
                    ->where('csa.status', '1');
            })

            ->leftJoin($feedbackSub, function ($join) {
                $join->on('tf.timetable_pk', '=', 't.pk')
                    ->on('tf.student_master_pk', '=', 'sm.pk');
            })

            ->select([
                DB::raw("CONCAT(
                COALESCE(sm.first_name, ''),
                ' ',
                COALESCE(sm.middle_name, ''),
                ' ',
                COALESCE(sm.last_name, '')
            ) as user_name"),

                'sm.email',
                'sm.contact_no',
                'c.course_name',

                DB::raw("
                CONCAT(
                    COALESCE(t.subject_topic, 'Session'),
                    ' (',
                    DATE_FORMAT(t.START_DATE, '%d-%m-%Y'),
                    ')'
                ) as session_info
            "),

                DB::raw("DATE_FORMAT(t.START_DATE, '%d-%m-%Y') as date_range"),

                DB::raw("
                (
                    CASE 
                        WHEN JSON_VALID(t.faculty_master) 
                        THEN JSON_LENGTH(t.faculty_master)
                        ELSE 1
                    END
                )
                -
                COALESCE(tf.submitted_count, 0)
                as pending_count
            "),

                't.class_session',
                't.pk as timetable_pk',
            ])

            ->where('t.feedback_checkbox', 1)

            ->whereRaw("
            TIMESTAMP(
                t.END_DATE,
                STR_TO_DATE(
                    TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                    '%h:%i %p'
                )
            ) <= NOW()
        ")

            ->whereRaw("
            (
                (
                    CASE 
                        WHEN JSON_VALID(t.faculty_master) 
                        THEN JSON_LENGTH(t.faculty_master)
                        ELSE 1
                    END
                )
                - COALESCE(tf.submitted_count, 0)
            ) > 0
        ");
    }

    /**
     * Restrict summary data to active (current/ongoing) or archive programs when no specific course is chosen.
     */
    private function applySummaryProgramScopeToQuery($query, Request $request): void
    {
        $scope = $request->input('filter_course_scope', 'active');
        $currentDate = Carbon::now()->format('Y-m-d');

        if ($scope === 'archive') {
            $query->whereNotNull('c.end_date')
                ->where('c.end_date', '<', $currentDate);
        } else {
            $query->where(function ($q) use ($currentDate) {
                $q->whereNull('c.end_date')
                    ->orWhere('c.end_date', '>=', $currentDate);
            });
        }
    }

    /**
     * Apply filters to summary export query
     */
    /**
     * Apply filters to summary export query
     */
    private function applySummaryExportFilters($query, Request $request)
    {
        // Apply course filter
        if ($request->filled('filter_course_pk')) {
            $query->where('t.course_master_pk', $request->filter_course_pk);
        } elseif (!$request->filled('filter_session_id')) {
            $this->applySummaryProgramScopeToQuery($query, $request);
        }

        // Apply session filter
        if ($request->filled('filter_session_id')) {
            $query->where('t.pk', $request->filter_session_id);
        }

        // Apply user name filter
        if ($request->filled('filter_user_name')) {
            $query->where(function ($q) use ($request) {
                $q->where('sm.display_name', 'LIKE', '%' . $request->filter_user_name . '%')
                    ->orWhere('sm.first_name', 'LIKE', '%' . $request->filter_user_name . '%')
                    ->orWhere('sm.last_name', 'LIKE', '%' . $request->filter_user_name . '%');
            });
        }

        // Apply email filter
        if ($request->filled('filter_email')) {
            $query->where('sm.email', 'LIKE', '%' . $request->filter_email . '%');
        }

        // Apply date filters
        if ($request->filled('filter_from_date')) {
            $query->whereDate('t.START_DATE', '>=', $request->filter_from_date);
        }

        if ($request->filled('filter_to_date')) {
            $query->whereDate('t.START_DATE', '<=', $request->filter_to_date);
        }

        // Apply global search (ot_name is the DataTable search input)
        if ($request->filled('ot_name')) {
            $searchTerm = $request->ot_name;
            $query->where(function ($q) use ($searchTerm) {
                $q->where(DB::raw("CONCAT(
                COALESCE(sm.first_name, ''),
                ' ',
                COALESCE(sm.middle_name, ''),
                ' ',
                COALESCE(sm.last_name, '')
            )"), 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('sm.email', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('c.course_name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('sm.first_name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('sm.last_name', 'LIKE', '%' . $searchTerm . '%');
            });
        }
    }


    /**
     * Get pending students optimized for export
     */
    private function getPendingStudentsOptimized($course_pk = null)
    {
        // Step 1: Get filtered timetable IDs
        $timetableQuery = DB::table('timetable as t')
            ->select('t.pk')
            ->where('t.feedback_checkbox', 1)
            ->whereRaw("
                TIMESTAMP(
                    t.END_DATE,
                    STR_TO_DATE(
                        TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                        '%h:%i %p'
                    )
                ) <= CONVERT_TZ(NOW(), '+00:00', '+05:30')
            ");

        if ($course_pk) {
            $timetableQuery->where('t.course_master_pk', $course_pk);
        }

        $timetableIds = $timetableQuery->pluck('pk')->toArray();

        if (empty($timetableIds)) {
            return collect();
        }

        // Step 2: Process in chunks for memory efficiency
        $chunkSize = 1000;
        $results = collect();

        foreach (array_chunk($timetableIds, $chunkSize) as $chunk) {
            $query = DB::table('timetable as t')
                ->select([
                    't.pk as timetable_pk',
                    't.subject_topic',
                    'c.course_name',
                    'v.venue_name',
                    'f.full_name as faculty_name',
                    DB::raw("TRIM(CONCAT(sm.first_name,' ',IFNULL(sm.middle_name,''),' ',IFNULL(sm.last_name,''))) as student_name"),
                    'sm.email',
                    'sm.contact_no',
                    'sm.generated_OT_code',
                    't.START_DATE as from_date',
                    DB::raw("DATE_FORMAT(t.START_DATE, '%d-%m-%Y') as formatted_date"),
                    't.class_session'
                ])
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
                ->join('venue_master as v', 't.venue_id', '=', 'v.venue_id')
                ->leftJoin('faculty_master as f', function ($join) {
                    $join->whereRaw("
                        f.pk = (
                            CASE 
                                WHEN JSON_VALID(t.faculty_master) 
                                THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(t.faculty_master, '$[0]')) AS UNSIGNED)
                                ELSE CAST(t.faculty_master AS UNSIGNED)
                            END
                        )
                    ");
                })
                ->join('student_master_course__map as smcm', function ($join) {
                    $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                        ->where('smcm.active_inactive', 1);
                })
                ->join('student_master as sm', 'sm.pk', '=', 'smcm.student_master_pk')
                ->join('course_student_attendance as csa', function ($join) {
                    $join->on('csa.timetable_pk', '=', 't.pk')
                        ->on('csa.Student_master_pk', '=', 'sm.pk')
                        ->where('csa.status', '1');
                })
                ->leftJoin('topic_feedback as tf', function ($join) {
                    $join->on('tf.timetable_pk', '=', 't.pk')
                        ->on('tf.student_master_pk', '=', 'sm.pk')
                        ->where('tf.is_submitted', 1);
                })
                ->whereIn('t.pk', $chunk)
                ->whereNull('tf.pk')
                ->orderBy('t.START_DATE', 'asc');

            $chunkResults = $query->get();

            // FIXED: Ensure we merge properly
            if ($chunkResults && count($chunkResults) > 0) {
                $results = $results->merge($chunkResults);
            }

            // Clear memory
            unset($chunkResults);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        return $results;
    }

    /**
     * Generate PDF
     */
    private function generatePDF($students, $course_name)
    {
        // FIXED: Ensure $students is a collection, not a string
        if (!($students instanceof Collection)) {
            if (is_array($students)) {
                $students = collect($students);
            } elseif (is_string($students)) {
                $students = collect();
            } else {
                $students = collect();
            }
        }

        $data = [
            'students' => $students,
            'course_name' => $course_name,
            'export_date' => now()->format('d-m-Y H:i:s'),
            'total_count' => $students->count()
        ];

        return PDF::loadView('admin.feedback.pending_students_pdf', $data)
            ->setPaper('A4', 'landscape')
            ->setOption('defaultFont', 'sans-serif')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);
    }

    /**
     * Get course name helper
     */
    /**
     * Get course name helper - SIMPLIFIED VERSION
     */
    // private function getCourseName($course_pk)
    // {
    //     if (!$course_pk) {
    //         return 'All Courses';
    //     }

    //     try {
    //         // Direct query without caching to avoid issues
    //         $result = DB::table('course_master')
    //             ->where('pk', $course_pk)
    //             ->value('course_name'); // Use value() to get just the course_name

    //         return $result ?: 'All Courses';
    //     } catch (\Exception $e) {
    //         \Log::error('Error getting course name for pk ' . $course_pk . ': ' . $e->getMessage());
    //         return 'All Courses';
    //     }
    // }

    /**
     * Simple slug creation function (alternative to Str::slug)
     */
    private function createSlug($text)
    {
        // Replace spaces and special characters with underscores
        $text = preg_replace('/[^a-zA-Z0-9]+/', '_', $text);

        // Convert to lowercase
        $text = strtolower($text);

        // Trim underscores from start and end
        $text = trim($text, '_');

        // Replace multiple underscores with single
        $text = preg_replace('/_+/', '_', $text);

        return $text;
    }

    /**
     * Get statistics for dashboard
     */
    public function getPendingStats(Request $request)
    {
        $cacheKey = 'pending_feedback_stats_' . ($request->course_pk ?? 'all');

        $stats = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = DB::table('timetable as t')
                ->select([
                    DB::raw('COUNT(DISTINCT t.pk) as total_sessions'),
                    DB::raw('COUNT(DISTINCT sm.pk) as total_students'),
                    DB::raw('COUNT(DISTINCT c.pk) as total_courses')
                ])
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
                ->join('student_master_course__map as smcm', 'smcm.course_master_pk', '=', 'c.pk')
                ->join('student_master as sm', 'sm.pk', '=', 'smcm.student_master_pk')
                ->join('course_student_attendance as csa', function ($join) {
                    $join->on('csa.timetable_pk', '=', 't.pk')
                        ->on('csa.Student_master_pk', '=', 'sm.pk')
                        ->where('csa.status', '1');
                })
                ->leftJoin('topic_feedback as tf', function ($join) {
                    $join->on('tf.timetable_pk', '=', 't.pk')
                        ->on('tf.student_master_pk', '=', 'sm.pk')
                        ->where('tf.is_submitted', 1);
                })
                ->where('t.feedback_checkbox', 1)
                ->whereRaw("
                    TIMESTAMP(
                        t.END_DATE,
                        STR_TO_DATE(
                            TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                            '%h:%i %p'
                        )
                    ) <= CONVERT_TZ(NOW(), '+00:00', '+05:30')
                ")
                ->whereNull('tf.pk');

            if ($request->filled('course_pk')) {
                $query->where('t.course_master_pk', $request->course_pk);
            }

            $result = $query->first();

            // FIXED: Ensure we return an object, not a string
            return $result ?: (object) [
                'total_sessions' => 0,
                'total_students' => 0,
                'total_courses' => 0
            ];
        });

        return response()->json($stats);
    }

    /**
     * Pick a default course pk for Feedback Details when "Current courses" is selected and no program filter is set.
     * Matches dashboard "active course" idea (active, end_date not past) and role-scoped list when possible.
     *
     * @param  \Illuminate\Support\Collection<string|int, mixed>  $programs
     */
    private function defaultProgramIdForCurrentCourseFeedbackList($programs, array $roleCourseIds): string
    {
        $keys = $programs->keys()->map(static fn ($k) => (string) $k)->values()->all();

        if ($keys === []) {
            return '';
        }

        if (count($roleCourseIds) === 1) {
            $only = (string) $roleCourseIds[0];
            if (in_array($only, $keys, true)) {
                return $only;
            }
        }

        if (count($keys) === 1) {
            return $keys[0];
        }

        $preferred = DB::table('course_master')
            ->whereIn('pk', $keys)
            ->where('active_inactive', 1)
            ->where('start_year', '<', now())
            ->whereDate('end_date', '>=', Carbon::today())
            ->orderBy('course_name')
            ->value('pk');

        return $preferred !== null ? (string) $preferred : $keys[0];
    }
}
