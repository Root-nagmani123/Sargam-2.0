<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\FacultyMaster;
use App\Models\VenueMaster;
use App\Models\CourseGroupTypeMaster;
use App\Exports\TimetableReportExport;
use App\Services\Timetable\FacultySessionScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class TimetableReportController extends Controller
{
    /**
     * Column index to key/label map (must match DataTable columns order).
     */
    private const COLUMN_MAP = [
        0  => ['key' => 'sno',              'label' => 'Sr.'],
        1  => ['key' => 'course_name',      'label' => 'Course'],
        2  => ['key' => 'course_group_type', 'label' => 'Course Group Type'],
        3  => ['key' => 'group_name',        'label' => 'Group'],
        4  => ['key' => 'subject_name',      'label' => 'Subject'],
        5  => ['key' => 'module_name',       'label' => 'Module'],
        6  => ['key' => 'subject_topic',     'label' => 'Topic'],
        7  => ['key' => 'faculty_name',      'label' => 'Faculty'],
        8  => ['key' => 'faculty_code',      'label' => 'Faculty Code'],
        9  => ['key' => 'faculty_type',      'label' => 'Faculty Type'],
        10 => ['key' => 'class_session',     'label' => 'Session'],
        11 => ['key' => 'start_date',        'label' => 'Start Date'],
        12 => ['key' => 'end_date',          'label' => 'End Date'],
        13 => ['key' => 'venue_name',        'label' => 'Venue'],
        14 => ['key' => 'faculty_role',      'label' => 'Faculty Role'],
    ];

    /**
     * Column index -> SQL expression, for the columns that come from the
     * database. Faculty, Group and Role are resolved in PHP once the page has
     * been fetched, so there is nothing here to sort them by; the grid marks
     * those columns unsortable rather than accepting a click that does nothing.
     */
    private const SORTABLE_COLUMNS = [
        1  => 'c.course_name',
        2  => 'cgtm.type_name',
        4  => 'sm.subject_name',
        5  => 'smm.module_name',
        6  => 't.subject_topic',
        10 => 't.class_session',
        11 => 't.START_DATE',
        12 => 't.END_DATE',
        13 => 'vm.venue_name',
    ];

    /**
     * Parse visible_columns from request and return the visible column definitions.
     */
    private function getVisibleColumns(Request $request): array
    {
        if (!$request->filled('visible_columns')) {
            return self::COLUMN_MAP;
        }

        $indices = array_map('intval', explode(',', $request->visible_columns));
        $visible = [];
        foreach ($indices as $i) {
            if (isset(self::COLUMN_MAP[$i])) {
                $visible[$i] = self::COLUMN_MAP[$i];
            }
        }

        return !empty($visible) ? $visible : self::COLUMN_MAP;
    }

    /**
     * Show the timetable report page (with filter dropdowns).
     */
    public function index(Request $request)
    {
        $currentDate = now()->toDateString();

        // Which course tab the page opens on. Active unless the URL asks otherwise —
        // the dashboard's Total Sessions card counts active AND ended courses, so it
        // links here with course_mode=all and lands on the tab holding exactly the
        // rows it counted.
        $initialCourseMode = $request->get('course_mode');
        if (! in_array($initialCourseMode, ['active', 'archive', 'all'], true)) {
            $initialCourseMode = 'active';
        }

        // Same reason: the card counts Teaching-role sessions, so it links with
        // faculty_role=Teaching and the Role filter opens on that value.
        $initialRole = FacultySessionScope::normaliseRole($request->get('faculty_role'));
        $facultyRoles = FacultySessionScope::ROLES;

        $activeCourses = CourseMaster::where('active_inactive', 1)
            ->where(function ($q) use ($currentDate) {
                $q->whereNull('end_date')
                  ->orWhereDate('end_date', '>=', $currentDate);
            })
            ->select('pk', 'course_name')
            ->orderBy('course_name')
            ->get();

        $archivedCourses = CourseMaster::where('active_inactive', 1)
            ->whereDate('end_date', '<', $currentDate)
            ->select('pk', 'course_name')
            ->orderBy('course_name')
            ->get();

        // The All Courses tab is unfiltered server-side, so its Course dropdown has
        // to offer every course — including the ones neither of the other two tabs
        // lists (active_inactive = 0), which otherwise cannot be picked at all.
        $allCourses = CourseMaster::select('pk', 'course_name')
            ->orderBy('course_name')
            ->get();

        // A faculty viewer gets a one-entry Faculty dropdown — their own. data()
        // and buildExportData() enforce the same scope regardless of what is
        // posted, so this only keeps the UI honest about what they can ask for.
        $lockedFacultyPk = FacultySessionScope::lockedFacultyPk();

        $faculties = FacultyMaster::select('pk', 'full_name', 'faculty_code')
            ->when($lockedFacultyPk !== null, fn ($q) => $q->where('pk', $lockedFacultyPk))
            ->orderBy('full_name')
            ->get();
        $venues       = VenueMaster::where('active_inactive', 1)->select('venue_id', 'venue_name')->orderBy('venue_name')->get();
        $courseGroups = CourseGroupTypeMaster::select('pk', 'type_name')->orderBy('type_name')->get();

        return view('admin.timetable-report.index', compact(
            'activeCourses',
            'archivedCourses',
            'allCourses',
            'faculties',
            'venues',
            'courseGroups',
            'facultyRoles',
            'lockedFacultyPk',
            'initialCourseMode',
            'initialRole'
        ));
    }

    /**
     * Return DataTables-compatible JSON for the timetable report.
     */
    public function data(Request $request)
    {
        $draw   = (int) $request->get('draw', 0);
        $start  = max(0, (int) $request->get('start', 0));
        $length = (int) $request->get('length', 10);
        $searchValue = trim((string) data_get($request->all(), 'search.value', ''));

        // ── Base query ──
        $query = DB::table('timetable as t')
            ->leftJoin('course_master as c', 't.course_master_pk', '=', 'c.pk')
            ->leftJoin('course_group_type_master as cgtm', 't.course_group_type_master', '=', 'cgtm.pk')
            ->leftJoin('venue_master as vm', 't.venue_id', '=', 'vm.venue_id')
            ->leftJoin('subject_master as sm', 't.subject_master_pk', '=', 'sm.pk')
            ->leftJoin('subject_module_master as smm', 't.subject_module_master_pk', '=', 'smm.pk')
            ->select(
                't.pk',
                't.START_DATE',
                't.END_DATE',
                't.subject_topic',
                't.faculty_master',
                't.faculty_details',
                't.group_name',
                't.class_session',
                'c.course_name',
                'c.couse_short_name',
                'cgtm.type_name as course_group_type',
                'vm.venue_name',
                'sm.subject_name',
                'smm.module_name'
            );

        // ── Filters ──
        $this->applyFilters($request, $query);

        // ── Global search ──
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('t.subject_topic', 'LIKE', "%{$searchValue}%")
                  ->orWhere('c.course_name', 'LIKE', "%{$searchValue}%")
                  ->orWhere('vm.venue_name', 'LIKE', "%{$searchValue}%")
                  ->orWhere('sm.subject_name', 'LIKE', "%{$searchValue}%")
                  ->orWhere('smm.module_name', 'LIKE', "%{$searchValue}%");
            });
        }

        // ── Counts ──
        // recordsTotal is the "of N total" DataTables prints next to the filtered
        // number. For a locked faculty that has to be THEIR session count, not the
        // Academy's — otherwise the grid announces how many sessions exist that
        // they cannot see.
        $lockedFacultyPk = FacultySessionScope::lockedFacultyPk();

        if ($lockedFacultyPk !== null) {
            $totalQuery = DB::table('timetable as t');
            FacultySessionScope::applyFaculty($totalQuery, $lockedFacultyPk);
            $totalCount = $totalQuery->count();
        } else {
            $totalCount = DB::table('timetable')->count();
        }

        $filteredCount = $query->count();

        // ── Ordering ──
        // The grid is server-side, so a header click only sorts if the order it
        // sends is honoured here; anything not in SORTABLE_COLUMNS (the columns
        // built in PHP) falls back to newest session first.
        $orderColumn = (int) data_get($request->all(), 'order.0.column', -1);
        $orderDir = strtolower((string) data_get($request->all(), 'order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (isset(self::SORTABLE_COLUMNS[$orderColumn])) {
            $query->orderBy(DB::raw(self::SORTABLE_COLUMNS[$orderColumn]), $orderDir);
        } else {
            $query->orderByDesc('t.START_DATE');
        }

        // ── Pagination ──
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $rows = $query->get();

        $data = $this->transformRows($rows, $start + 1);

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $totalCount,
            'recordsFiltered' => $filteredCount,
            'data'            => $data,
        ]);
    }

    /**
     * Build the filtered query and return all transformed rows (no pagination).
     */
    private function buildExportData(Request $request): array
    {
        $query = DB::table('timetable as t')
            ->leftJoin('course_master as c', 't.course_master_pk', '=', 'c.pk')
            ->leftJoin('course_group_type_master as cgtm', 't.course_group_type_master', '=', 'cgtm.pk')
            ->leftJoin('venue_master as vm', 't.venue_id', '=', 'vm.venue_id')
            ->leftJoin('subject_master as sm', 't.subject_master_pk', '=', 'sm.pk')
            ->leftJoin('subject_module_master as smm', 't.subject_module_master_pk', '=', 'smm.pk')
            ->select(
                't.pk', 't.START_DATE', 't.END_DATE', 't.subject_topic',
                't.faculty_master', 't.faculty_details', 't.group_name', 't.class_session',
                'c.course_name', 'c.couse_short_name',
                'cgtm.type_name as course_group_type',
                'vm.venue_name', 'sm.subject_name', 'smm.module_name'
            );

        // The exports go through the same filter builder as the grid, so what is
        // downloaded is what was on screen.
        $applied = $this->applyFilters($request, $query);

        $query->orderByDesc('t.START_DATE');

        $data = $this->transformRows($query->get());

        // Build filter summary for display
        $filterSummary = [
            'course_mode'   => $applied['course_mode'],
            'subject_topic' => $request->subject_topic,
            'module_name'   => $request->module_name,
            'date_from'     => $request->date_from,
            'date_to'       => $request->date_to,
        ];

        if ($request->filled('course_pk')) {
            $filterSummary['course_name'] = CourseMaster::where('pk', $request->course_pk)->value('course_name') ?? '';
        }
        // $applied, not the request: a locked faculty sends no faculty_pk, and the
        // export header still has to say whose sessions these are.
        if ($applied['faculty_pk'] !== null) {
            $filterSummary['faculty_name'] = FacultyMaster::where('pk', $applied['faculty_pk'])->value('full_name') ?? '';
        }
        if ($applied['role'] !== null) {
            $filterSummary['faculty_role'] = $applied['role'];
        }
        if ($request->filled('faculty_type')) {
            $filterSummary['faculty_type'] = match ((int) $request->faculty_type) {
                1 => 'Internal', 2 => 'Guest', 3 => 'Research', default => 'Unknown',
            };
        }
        if ($request->filled('venue_id')) {
            $filterSummary['venue_name'] = VenueMaster::where('venue_id', $request->venue_id)->value('venue_name') ?? '';
        }

        return ['rows' => $data, 'filterSummary' => $filterSummary];
    }

    /**
     * Every filter the page offers, applied to a query built on the standard
     * aliases (t, c, cgtm, vm, sm, smm).
     *
     * The grid and the two exports all come through here, so a filter cannot
     * work in one place and be quietly missing from another.
     *
     * @return array{course_mode: string, faculty_pk: ?int, role: ?string}
     */
    private function applyFilters(Request $request, $query): array
    {
        // Active / Archive course mode (same logic as feedback_average). Anything
        // else — the All Courses tab — leaves both in.
        $courseMode = (string) $request->get('course_mode', 'active');
        FacultySessionScope::applyCourseMode($query, $courseMode);

        if ($request->filled('course_pk')) {
            $query->where('t.course_master_pk', $request->course_pk);
        }

        // A faculty viewer is pinned to their own sessions whatever faculty_pk the
        // request carries — the dropdown only offers them, but the parameter is
        // theirs to edit, so the lock lives here rather than in the form.
        $requested = $request->input('faculty_pk');
        $facultyPk = FacultySessionScope::lockedFacultyPk()
            ?? (($requested !== null && $requested !== '') ? (int) $requested : null);

        if ($facultyPk !== null) {
            FacultySessionScope::applyFaculty($query, $facultyPk);
        }

        // Role: with a faculty chosen it means "sessions where THEY hold that
        // role", otherwise "sessions someone holds that role in".
        $role = FacultySessionScope::normaliseRole($request->input('faculty_role'));

        if ($role !== null) {
            FacultySessionScope::applyRole($query, $role, $facultyPk);
        }

        if ($request->filled('subject_topic')) {
            $query->where('t.subject_topic', 'LIKE', '%' . $request->subject_topic . '%');
        }

        if ($request->filled('faculty_type')) {
            $this->applyFacultyTypeFilter($query, (int) $request->faculty_type);
        }

        if ($request->filled('venue_id')) {
            $query->where('t.venue_id', $request->venue_id);
        }

        if ($request->filled('module_name')) {
            $query->where('smm.module_name', 'LIKE', '%' . $request->module_name . '%');
        }

        if ($request->filled('date_from')) {
            $query->where('t.START_DATE', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('t.START_DATE', '<=', $request->date_to);
        }

        return ['course_mode' => $courseMode, 'faculty_pk' => $facultyPk, 'role' => $role];
    }

    /**
     * "Session has a faculty of this type" — Internal / Guest / Research.
     *
     * Was a JSON_TABLE join, which MySQL has and MariaDB does not: on this
     * server picking a Faculty Type threw a SQL syntax error, so the grid went
     * blank and the exports 500'd. The EXISTS below matches the same three
     * shapes faculty_master is stored in and runs on both engines.
     */
    private function applyFacultyTypeFilter($query, int $facultyType): void
    {
        $json = "COALESCE(NULLIF(t.faculty_master, ''), '[]')";

        $query->whereRaw(
            "EXISTS (
                SELECT 1 FROM faculty_master fm_filter
                WHERE fm_filter.faculty_type = ?
                  AND (
                      (JSON_VALID({$json}) AND (
                          JSON_CONTAINS({$json}, CONCAT('\"', fm_filter.pk, '\"'))
                          OR JSON_CONTAINS({$json}, CAST(fm_filter.pk AS CHAR))
                      ))
                      OR (t.faculty_master REGEXP '^[0-9]+$' AND CAST(t.faculty_master AS UNSIGNED) = fm_filter.pk)
                  )
            )",
            [$facultyType]
        );
    }

    /**
     * Resolve the JSON columns (faculty_master, faculty_details, group_name) into
     * the display strings the grid, the PDF and the Excel sheet all print.
     *
     * @param  int|null  $startingSno  First serial number, or null for the exports,
     *                                 which number their own rows.
     */
    private function transformRows($rows, ?int $startingSno = null): array
    {
        $data = [];
        $sno  = $startingSno ?? 1;

        foreach ($rows as $row) {
            $facultyNames = 'No Faculty Assigned';
            $facultyCodes = 'N/A';
            $facultyTypes = 'N/A';
            $facultyRoles = 'N/A';

            $fmRaw = $row->faculty_master;
            $fids  = [];

            if ($fmRaw !== null && $fmRaw !== '') {
                $decoded = json_decode($fmRaw, true);
                if (is_array($decoded)) {
                    $fids = array_map('intval', $decoded);
                } elseif (is_numeric($fmRaw)) {
                    $fids = [(int) $fmRaw];
                }
            }

            // faculty_pk => role, for the sessions that carry faculty_details.
            // Sessions predating that column have no role on record; they read as
            // Teaching, the same fallback the feedback counts make.
            $roleByFaculty = [];
            $details = json_decode((string) ($row->faculty_details ?? ''), true);
            if (is_array($details)) {
                foreach ($details as $d) {
                    if (!empty($d['faculty_pk'])) {
                        $roleByFaculty[(int) $d['faculty_pk']] = $d['role'] ?? '';
                    }
                }
            }

            if (!empty($fids)) {
                $facultyRows = DB::table('faculty_master')
                    ->whereIn('pk', $fids)
                    ->select('pk', 'full_name', 'faculty_code', 'faculty_type')
                    ->get();

                if ($facultyRows->isNotEmpty()) {
                    $facultyNames = $facultyRows->pluck('full_name')->implode(', ');
                    $facultyCodes = $facultyRows->pluck('faculty_code')->implode(', ');
                    $facultyTypes = $facultyRows->map(function ($f) {
                        return match ((int) $f->faculty_type) {
                            1 => 'Internal',
                            2 => 'Guest',
                            3 => 'Research',
                            default => 'Unknown',
                        };
                    })->implode(', ');
                    // Same order as the names, so the two columns read across.
                    $facultyRoles = $facultyRows->map(
                        fn ($f) => $roleByFaculty[(int) $f->pk] ?? FacultySessionScope::ROLE_TEACHING
                    )->implode(', ');
                }
            }

            $groupNames = 'No Group Assigned';
            $gnRaw      = $row->group_name;

            if ($gnRaw !== null && $gnRaw !== '') {
                $gids = json_decode($gnRaw, true);
                if (is_array($gids) && !empty($gids)) {
                    $gids      = array_map('intval', $gids);
                    $groupRows = DB::table('group_type_master_course_master_map')
                        ->whereIn('pk', $gids)
                        ->pluck('group_name');
                    if ($groupRows->isNotEmpty()) {
                        $groupNames = $groupRows->implode(', ');
                    }
                }
            }

            $data[] = [
                'sno'              => $sno++,
                'start_date'       => $row->START_DATE,
                'end_date'         => $row->END_DATE,
                'course_name'      => $row->course_name ?? 'N/A',
                'course_short'     => $row->couse_short_name ?? '',
                'course_group_type'=> $row->course_group_type ?? 'N/A',
                'subject_name'     => $row->subject_name ?? 'N/A',
                'module_name'      => $row->module_name ?? 'N/A',
                'subject_topic'    => $row->subject_topic ?? '',
                'faculty_name'     => $facultyNames,
                'faculty_code'     => $facultyCodes,
                'faculty_type'     => $facultyTypes,
                'faculty_role'     => $facultyRoles,
                'group_name'       => $groupNames,
                'class_session'    => $row->class_session ?? '',
                'venue_name'       => $row->venue_name ?? 'N/A',
            ];
        }

        return $data;
    }

    /**
     * Export PDF with LBSNAA branding.
     */
    public function exportPdf(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $export         = $this->buildExportData($request);
        $visibleColumns = $this->getVisibleColumns($request);

        $data = [
            'rows'           => $export['rows'],
            'filterSummary'  => $export['filterSummary'],
            'visibleColumns' => $visibleColumns,
            'emblemSrc'      => $this->indiaEmblemDataUri(),
            'lbsnaaLogoSrc'  => $this->lbsnaaLogoDataUri(),
        ];

        $pdf = Pdf::loadView('admin.timetable-report.pdf.timetable-report-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'dpi'                  => 96,
            ]);

        $fileName = 'timetable-session-report-' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Export Excel with LBSNAA banner.
     */
    public function exportExcel(Request $request)
    {
        $export         = $this->buildExportData($request);
        $visibleColumns = $this->getVisibleColumns($request);

        $fileName = 'timetable-session-report-' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new TimetableReportExport($export['rows'], $export['filterSummary'], $visibleColumns),
            $fileName
        );
    }

    private function indiaEmblemDataUri(): string
    {
        $url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(20)->connectTimeout(8)->get($url);
            if ($response->successful() && strlen($response->body()) > 100) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
        } catch (\Throwable $e) {
        }
        return $url;
    }

    private function lbsnaaLogoDataUri(): string
    {
        foreach ([public_path('images/lbsnaa_logo.jpg'), public_path('images/lbsnaa_logo.png')] as $path) {
            if (is_file($path) && is_readable($path)) {
                $raw = @file_get_contents($path);
                if ($raw !== false) {
                    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                    return 'data:' . $mime . ';base64,' . base64_encode($raw);
                }
            }
        }
        foreach ([
            public_path('admin_assets/images/logos/logo.png'),
            public_path('admin_assets/images/logos/logo.svg'),
        ] as $localPath) {
            if (is_file($localPath) && is_readable($localPath)) {
                $raw = @file_get_contents($localPath);
                if ($raw !== false) {
                    $ext  = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
                    $mime = match ($ext) {
                        'svg' => 'image/svg+xml', 'png' => 'image/png', default => 'image/jpeg',
                    };
                    return 'data:' . $mime . ';base64,' . base64_encode($raw);
                }
            }
        }
        return 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
    }
}
