<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\FacultyMaster;
use App\Models\VenueMaster;
use App\Models\CourseGroupTypeMaster;
use App\Exports\TimetableReportExport;
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
    public function index()
    {
        $currentDate = now()->toDateString();

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

        $faculties    = FacultyMaster::select('pk', 'full_name', 'faculty_code')->orderBy('full_name')->get();
        $venues       = VenueMaster::where('active_inactive', 1)->select('venue_id', 'venue_name')->orderBy('venue_name')->get();
        $courseGroups = CourseGroupTypeMaster::select('pk', 'type_name')->orderBy('type_name')->get();

        return view('admin.timetable-report.index', compact(
            'activeCourses',
            'archivedCourses',
            'faculties',
            'venues',
            'courseGroups'
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

        // Active / Archive course mode (same logic as feedback_average)
        $courseMode = $request->get('course_mode', 'active');
        $currentDate = now()->toDateString();
        if ($courseMode === 'active') {
            $query->where('c.active_inactive', 1)
                  ->where(function ($q) use ($currentDate) {
                      $q->whereNull('c.end_date')
                        ->orWhereDate('c.end_date', '>=', $currentDate);
                  });
        } elseif ($courseMode === 'archive') {
            $query->where('c.active_inactive', 1)
                  ->whereDate('c.end_date', '<', $currentDate);
        }

        if ($request->filled('course_pk')) {
            $query->where('t.course_master_pk', $request->course_pk);
        }

        if ($request->filled('faculty_pk')) {
            $facultyPk = $request->faculty_pk;
            $query->where(function ($q) use ($facultyPk) {
                $q->whereRaw("JSON_CONTAINS(COALESCE(NULLIF(t.faculty_master, ''), '[]'), CAST(? AS JSON))", [$facultyPk])
                  ->orWhere('t.faculty_master', $facultyPk);
            });
        }

        if ($request->filled('subject_topic')) {
            $query->where('t.subject_topic', 'LIKE', '%' . $request->subject_topic . '%');
        }

        if ($request->filled('faculty_type')) {
            $facultyType = $request->faculty_type;
            $query->whereExists(function ($sub) use ($facultyType) {
                $sub->select(DB::raw(1))
                    ->from(DB::raw("JSON_TABLE(COALESCE(NULLIF(t.faculty_master, ''), '[]'), '\$[*]' COLUMNS(fid INT PATH '\$')) jt_filter"))
                    ->join('faculty_master as fm_filter', 'jt_filter.fid', '=', 'fm_filter.pk')
                    ->where('fm_filter.faculty_type', $facultyType);
            });
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
        $totalCount    = DB::table('timetable')->count();
        $filteredCount = $query->count();

        // ── Ordering ──
        $query->orderByDesc('t.START_DATE');

        // ── Pagination ──
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $rows = $query->get();

        // ── Transform rows: resolve JSON faculty_master & group_name ──
        $data = [];
        $sno  = $start + 1;

        foreach ($rows as $row) {
            // Resolve faculty names, codes, types from JSON array
            $facultyNames = 'No Faculty Assigned';
            $facultyCodes = 'N/A';
            $facultyTypes = 'N/A';

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

            if (!empty($fids)) {
                $facultyRows = DB::table('faculty_master')
                    ->whereIn('pk', $fids)
                    ->select('full_name', 'faculty_code', 'faculty_type')
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
                }
            }

            // Resolve group names from JSON array
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
                'group_name'       => $groupNames,
                'class_session'    => $row->class_session ?? '',
                'venue_name'       => $row->venue_name ?? 'N/A',
            ];
        }

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
                't.faculty_master', 't.group_name', 't.class_session',
                'c.course_name', 'c.couse_short_name',
                'cgtm.type_name as course_group_type',
                'vm.venue_name', 'sm.subject_name', 'smm.module_name'
            );

        $courseMode   = $request->get('course_mode', 'active');
        $currentDate = now()->toDateString();

        if ($courseMode === 'active') {
            $query->where('c.active_inactive', 1)
                  ->where(function ($q) use ($currentDate) {
                      $q->whereNull('c.end_date')
                        ->orWhereDate('c.end_date', '>=', $currentDate);
                  });
        } elseif ($courseMode === 'archive') {
            $query->where('c.active_inactive', 1)
                  ->whereDate('c.end_date', '<', $currentDate);
        }

        if ($request->filled('course_pk')) {
            $query->where('t.course_master_pk', $request->course_pk);
        }
        if ($request->filled('faculty_pk')) {
            $facultyPk = $request->faculty_pk;
            $query->where(function ($q) use ($facultyPk) {
                $q->whereRaw("JSON_CONTAINS(COALESCE(NULLIF(t.faculty_master, ''), '[]'), CAST(? AS JSON))", [$facultyPk])
                  ->orWhere('t.faculty_master', $facultyPk);
            });
        }
        if ($request->filled('subject_topic')) {
            $query->where('t.subject_topic', 'LIKE', '%' . $request->subject_topic . '%');
        }
        if ($request->filled('faculty_type')) {
            $facultyType = $request->faculty_type;
            $query->whereExists(function ($sub) use ($facultyType) {
                $sub->select(DB::raw(1))
                    ->from(DB::raw("JSON_TABLE(COALESCE(NULLIF(t.faculty_master, ''), '[]'), '\$[*]' COLUMNS(fid INT PATH '\$')) jt_filter"))
                    ->join('faculty_master as fm_filter', 'jt_filter.fid', '=', 'fm_filter.pk')
                    ->where('fm_filter.faculty_type', $facultyType);
            });
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

        $query->orderByDesc('t.START_DATE');
        $rows = $query->get();

        // Transform rows
        $data = [];
        foreach ($rows as $row) {
            $facultyNames = 'No Faculty Assigned';
            $facultyCodes = 'N/A';
            $facultyTypes = 'N/A';

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
            if (!empty($fids)) {
                $facultyRows = DB::table('faculty_master')
                    ->whereIn('pk', $fids)
                    ->select('full_name', 'faculty_code', 'faculty_type')
                    ->get();
                if ($facultyRows->isNotEmpty()) {
                    $facultyNames = $facultyRows->pluck('full_name')->implode(', ');
                    $facultyCodes = $facultyRows->pluck('faculty_code')->implode(', ');
                    $facultyTypes = $facultyRows->map(function ($f) {
                        return match ((int) $f->faculty_type) {
                            1 => 'Internal', 2 => 'Guest', 3 => 'Research', default => 'Unknown',
                        };
                    })->implode(', ');
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
                'start_date'        => $row->START_DATE,
                'end_date'          => $row->END_DATE,
                'course_name'       => $row->course_name ?? 'N/A',
                'course_group_type' => $row->course_group_type ?? 'N/A',
                'subject_name'      => $row->subject_name ?? 'N/A',
                'module_name'       => $row->module_name ?? 'N/A',
                'subject_topic'     => $row->subject_topic ?? '',
                'faculty_name'      => $facultyNames,
                'faculty_code'      => $facultyCodes,
                'faculty_type'      => $facultyTypes,
                'group_name'        => $groupNames,
                'class_session'     => $row->class_session ?? '',
                'venue_name'        => $row->venue_name ?? 'N/A',
            ];
        }

        // Build filter summary for display
        $filterSummary = [
            'course_mode'   => $courseMode,
            'subject_topic' => $request->subject_topic,
            'module_name'   => $request->module_name,
            'date_from'     => $request->date_from,
            'date_to'       => $request->date_to,
        ];

        if ($request->filled('course_pk')) {
            $filterSummary['course_name'] = CourseMaster::where('pk', $request->course_pk)->value('course_name') ?? '';
        }
        if ($request->filled('faculty_pk')) {
            $filterSummary['faculty_name'] = FacultyMaster::where('pk', $request->faculty_pk)->value('full_name') ?? '';
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
