<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StudentListReportExport;
use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\EmployeeMaster;
use App\Models\StudentMasterCourseMap;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class DirectoryController extends Controller
{
    /**
     * Canonical OT-directory export columns, in report order: key => header label.
     * The Columns modal on admin/directory/ot sends the visible subset as ?cols=,
     * and the export intersects against THIS list — so a hand-edited ?cols= can
     * neither reorder the report nor invent a column. Keys must stay aligned with
     * OT_EXPORT_COLUMN_KEYS in ot.blade.php.
     */
    private const OT_EXPORT_COLUMNS = [
        'sno' => 'S.No.',
        'name' => 'Name',
        'ot_code' => 'OT Code',
        'room_no' => 'Room No.',
        'room_ext' => 'Room Extension No.',
        'email' => 'Email ID',
        'course' => 'Course Name',
        'cadre' => 'Cadre Name',
    ];

    /**
     * Same contract as OT_EXPORT_COLUMNS, for the LBSNAA staff directory. Keys
     * must stay aligned with LBS_EXPORT_COLUMN_KEYS in lbsnaa.blade.php.
     */
    private const LBSNAA_EXPORT_COLUMNS = [
        'sno' => 'S.No.',
        'name' => 'Name',
        'designation' => 'Designation',
        'section' => 'Section',
        'address' => 'Address',
        'office_ext' => 'Office Extension',
        'mobile' => 'Mobile',
        'residence' => 'Residence No.',
        'email' => 'Email ID',
    ];

    /**
     * The employee's displayed name, assembled in SQL so ORDER BY matches what
     * the Name column actually shows. NULLIF(TRIM(...), '') drops the blank
     * middle names that would otherwise leave a double space mid-sort.
     */
    private const EMPLOYEE_NAME_SQL = "TRIM(CONCAT_WS(' ',"
        . " NULLIF(TRIM(employee_master.first_name), ''),"
        . " NULLIF(TRIM(employee_master.middle_name), ''),"
        . " NULLIF(TRIM(employee_master.last_name), '')))";

    /**
     * Download formats offered by both directories. Print / PDF / Excel share one
     * branded report layout; `csv` stays a flat machine-readable data file.
     */
    private const EXPORT_FORMATS = ['csv', 'excel', 'pdf', 'print'];

    public function lbsnaa(Request $request)
    {
        // Active / Inactive split. EmployeeMaster::scopeActive() is the single
        // source of truth for what "active" means (status = 1); everything else
        // is a former or otherwise inactive record.
        $status = (string) $request->input('status', 'active');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $selectedDepartment = (int) $request->input('department_id', 0);
        $selectedDesignation = (int) $request->input('designation_id', 0);
        // Only the exports read this now — on screen the search is the DataTables
        // filter, which stamps its term onto the download links.
        $search = trim((string) $request->input('search', ''));
        $export = (string) $request->input('export', '');

        $scopeStatus = fn ($query) => $query->when(
            $status === 'active',
            fn ($q) => $q->where('employee_master.status', 1),
            fn ($q) => $q->where('employee_master.status', '<>', 1)
        );

        // Section / Designation options are drawn from the employees actually in
        // this tab — the raw masters hold 145 and 227 rows, most of them unused.
        $departments = $scopeStatus(
            EmployeeMaster::query()
                ->join('department_master as dept', 'employee_master.department_master_pk', '=', 'dept.pk')
                ->whereNotNull('dept.department_name')
                ->where('dept.department_name', '<>', '')
        )->distinct()->orderBy('dept.department_name')->pluck('dept.department_name', 'dept.pk');

        $designations = $scopeStatus(
            EmployeeMaster::query()
                ->join('designation_master as d', 'employee_master.designation_master_pk', '=', 'd.pk')
                ->whereNotNull('d.designation_name')
                ->where('d.designation_name', '<>', '')
        )->distinct()->orderBy('d.designation_name')->pluck('d.designation_name', 'd.pk');

        // A filter carried over from the other tab would silently return nothing.
        if ($selectedDepartment > 0 && !$departments->has($selectedDepartment)) {
            $selectedDepartment = 0;
        }
        if ($selectedDesignation > 0 && !$designations->has($selectedDesignation)) {
            $selectedDesignation = 0;
        }

        $employeesQuery = $scopeStatus(
            EmployeeMaster::query()
                ->leftJoin('designation_master as d', 'employee_master.designation_master_pk', '=', 'd.pk')
                ->leftJoin('department_master as dept', 'employee_master.department_master_pk', '=', 'dept.pk')
        )
            ->when($selectedDepartment > 0, fn ($q) => $q->where('employee_master.department_master_pk', $selectedDepartment))
            ->when($selectedDesignation > 0, fn ($q) => $q->where('employee_master.designation_master_pk', $selectedDesignation))
            ->select([
                'employee_master.pk',
                'employee_master.first_name',
                'employee_master.middle_name',
                'employee_master.last_name',
                'employee_master.current_address',
                'employee_master.office_extension_no',
                'employee_master.mobile',
                'employee_master.residence_no',
                'employee_master.email',
                'employee_master.officalemail',
                'employee_master.profile_picture',
                'd.designation_name',
                'dept.department_name',
            ]);

        if ($search !== '') {
            $employeesQuery->where(function ($query) use ($search) {
                $query->where('employee_master.first_name', 'like', "%{$search}%")
                    ->orWhere('employee_master.middle_name', 'like', "%{$search}%")
                    ->orWhere('employee_master.last_name', 'like', "%{$search}%")
                    ->orWhere('employee_master.email', 'like', "%{$search}%")
                    ->orWhere('employee_master.officalemail', 'like', "%{$search}%")
                    ->orWhere('employee_master.mobile', 'like', "%{$search}%")
                    ->orWhere('employee_master.office_extension_no', 'like', "%{$search}%")
                    ->orWhere('d.designation_name', 'like', "%{$search}%")
                    ->orWhere('dept.department_name', 'like', "%{$search}%");
            });
        }

        // Alphabetical by the name as displayed. Sorting on the raw first_name
        // puts " BALVIR" (one record carries a leading space) ahead of every A,
        // and ignores the middle/last name entirely on shared first names.
        $employeesQuery->orderByRaw(self::EMPLOYEE_NAME_SQL . ' asc');

        if (in_array($export, self::EXPORT_FORMATS, true)) {
            return $this->exportResponse(
                $export,
                $employeesQuery,
                self::LBSNAA_EXPORT_COLUMNS,
                $this->resolveExportCols(self::LBSNAA_EXPORT_COLUMNS, (string) $request->input('cols', '')),
                fn (object $row, int $serial) => $this->employeeRowValues($row, $serial),
                'LBSNAA Directory',
                $this->lbsnaaFilterSummary(
                    $status,
                    (string) ($departments[$selectedDepartment] ?? ''),
                    (string) ($designations[$selectedDesignation] ?? ''),
                    $search
                ),
                'lbsnaa_directory_' . now()->format('Ymd_His')
            );
        }

        // The whole filtered set: sorting, searching and paging are DataTables'
        // job on this page. Section/Designation/status stay server-side so the
        // payload only ever carries the bucket you asked for.
        $employees = $employeesQuery->get();

        $employees->transform(function ($row) {
            $row->full_name = $this->employeeName($row);
            $row->initials = $this->initialsFor($row->full_name);
            // The official address is the directory answer; the personal one is
            // only a fallback.
            $row->contact_email = $row->officalemail ?: $row->email;
            $row->office_extension_no = $this->trimDecimalZeros($row->office_extension_no);
            $row->residence_no = $this->trimDecimalZeros($row->residence_no);
            $row->mobile = $this->trimDecimalZeros($row->mobile);

            return $row;
        });

        return view('admin.directory.lbsnaa', compact(
            'employees',
            'departments',
            'designations',
            'selectedDepartment',
            'selectedDesignation',
            'search',
            'status'
        ));
    }

    public function ot(Request $request)
    {
        // Active / Archived split, same rule as Course Master
        // (CourseMasterDataTable::query()): a course is archived once its end_date
        // has passed. The tab decides which courses the Program Name filter offers.
        $status = (string) $request->input('status', 'active');
        if (!in_array($status, ['active', 'archive'], true)) {
            $status = 'active';
        }

        $today = now()->toDateString();
        $courses = CourseMaster::query()
            ->where('active_inactive', 1)
            ->when(
                $status === 'archive',
                fn ($query) => $query->where('end_date', '<', $today),
                fn ($query) => $query->where('end_date', '>=', $today)
            )
            ->orderByDesc('end_date')
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'couse_short_name']);

        $selectedCourseId = (int) $request->input('course_id', 0);
        // Only the exports read this now — on screen the search is the DataTables
        // filter, which stamps its term onto the download links.
        $search = trim((string) $request->input('search', ''));
        $sort = (string) $request->input('sort', 'name_asc');
        $export = (string) $request->input('export', '');
        // TRIM(): one student_master row carries a leading space in display_name,
        // which would otherwise sort ahead of every letter. Both halves come from
        // this whitelist, never from the request, so the raw order is safe.
        $sortMap = [
            'name_asc' => ['TRIM(sm.display_name)', 'asc'],
            'name_desc' => ['TRIM(sm.display_name)', 'desc'],
            'ot_code_asc' => ['TRIM(sm.generated_OT_code)', 'asc'],
            'ot_code_desc' => ['TRIM(sm.generated_OT_code)', 'desc'],
        ];
        [$sortColumn, $sortDirection] = $sortMap[$sort] ?? $sortMap['name_asc'];

        // A course_id carried over from the other tab would list trainees that
        // contradict the tab you're looking at — drop it and fall back to the
        // first course in this bucket.
        if ($selectedCourseId > 0 && !$courses->contains('pk', $selectedCourseId)) {
            $selectedCourseId = 0;
        }
        if ($selectedCourseId <= 0 && $courses->isNotEmpty()) {
            $selectedCourseId = (int) $courses->first()->pk;
        }

        $students = collect();
        if ($selectedCourseId > 0) {
            $studentsQuery = StudentMasterCourseMap::query()
                ->join('student_master as sm', 'student_master_course__map.student_master_pk', '=', 'sm.pk')
                ->join('course_master as cm', 'student_master_course__map.course_master_pk', '=', 'cm.pk')
                ->leftJoin('cadre_master as cad', 'sm.cadre_master_pk', '=', 'cad.pk')
                ->where('student_master_course__map.active_inactive', 1)
                ->where('sm.status', 1)
                ->where('cm.active_inactive', 1)
                ->where('cm.pk', $selectedCourseId)
                ->select([
                    'sm.pk',
                    'sm.display_name',
                    'sm.generated_OT_code',
                    'sm.email',
                    'sm.photo_path',
                    'cm.course_name',
                    // The table cell shows the short name (the full one is a
                    // 100+ char sentence that wraps every row to ~10 lines);
                    // the full name stays as the cell's tooltip and in exports.
                    'cm.couse_short_name',
                    'cad.cadre_name',
                ]);

            if ($search !== '') {
                $studentsQuery->where(function ($query) use ($search) {
                    $query->where('sm.display_name', 'like', "%{$search}%")
                        ->orWhere('sm.generated_OT_code', 'like', "%{$search}%")
                        ->orWhere('sm.email', 'like', "%{$search}%")
                        ->orWhere('cad.cadre_name', 'like', "%{$search}%");
                });
            }

            $studentsQuery = $studentsQuery->orderByRaw("{$sortColumn} {$sortDirection}");

            if (in_array($export, self::EXPORT_FORMATS, true)) {
                $header = $this->buildReportHeaderData($selectedCourseId);

                return $this->exportResponse(
                    $export,
                    $studentsQuery,
                    self::OT_EXPORT_COLUMNS,
                    $this->resolveExportCols(self::OT_EXPORT_COLUMNS, (string) $request->input('cols', '')),
                    fn (object $row, int $serial) => $this->otRowValues($row, $serial),
                    'OT Directory',
                    $this->otFilterSummary($status, (string) $header['courseName'], $search),
                    'ot_directory_' . now()->format('Ymd_His'),
                    $selectedCourseId
                );
            }

            // The whole course: sorting, searching and paging are DataTables' job
            // on this page. The tab + programme filter stay server-side so the
            // payload only ever carries the course you asked for.
            $students = $studentsQuery->get();

            $students->transform(function ($row) {
                $row->course_name = $this->decodeEntities((string) $row->course_name);
                $row->initials = $this->initialsFor((string) $row->display_name);

                return $row;
            });
        }

        return view('admin.directory.ot', compact('students', 'courses', 'selectedCourseId', 'search', 'sort', 'status'));
    }

    /** employee_master stores the name in three columns. */
    private function employeeName(object $row): string
    {
        return trim(
            trim((string) ($row->first_name ?? '')) . ' '
            . trim((string) ($row->middle_name ?? '')) . ' '
            . trim((string) ($row->last_name ?? ''))
        );
    }

    /**
     * employee_master.office_extension_no is a varchar holding float-formatted
     * text ("2347.0"), so extensions render with a bogus decimal. Only a trailing
     * ".0" tail is stripped — a blanket numeric cast would eat the leading zeros
     * that 95 residence numbers and 331 mobile numbers legitimately carry.
     */
    private function trimDecimalZeros(?string $value): string
    {
        $value = trim((string) $value);

        return preg_match('/^(-?\d+)\.0+$/', $value, $m) ? $m[1] : $value;
    }

    /**
     * course_master.course_name is stored DOUBLE HTML-escaped ("UT &amp;amp; NE
     * States"), so printing it raw shows the entity text on screen and in the
     * export. Decode to a fixed point; Blade then applies the single escape it
     * is meant to. Capped so a pathological value can't spin.
     */
    private function decodeEntities(string $value): string
    {
        for ($i = 0; $i < 3; $i++) {
            $decoded = html_entity_decode($value, ENT_QUOTES);
            if ($decoded === $value) {
                break;
            }
            $value = $decoded;
        }

        return $value;
    }

    /**
     * Monogram for the avatar placeholder: first letter of the first name plus
     * first letter of the last. A single-word name yields one letter, an empty
     * one yields '' (the view then shows a person glyph instead).
     */
    private function initialsFor(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (!$parts) {
            return '';
        }

        $initials = mb_substr($parts[0], 0, 1);
        if (count($parts) > 1) {
            $initials .= mb_substr(end($parts), 0, 1);
        }

        // "-" is the placeholder both directories use for a missing name.
        return preg_match('/^\p{L}/u', $initials) ? mb_strtoupper($initials) : '';
    }

    /**
     * Turn the Columns modal's `?cols=` list into an ordered, validated column list.
     * Intersecting against the canonical keys (rather than trusting the request)
     * preserves report order and drops anything unrecognised. Empty/absent = all.
     *
     * @param  array<string, string>  $columns  Canonical key => header label.
     * @return list<string>
     */
    private function resolveExportCols(array $columns, string $cols): array
    {
        $known = array_keys($columns);
        $wanted = array_filter(array_map('trim', explode(',', $cols)));

        if (!$wanted) {
            return $known;
        }

        $resolved = array_values(array_intersect($known, $wanted));

        return $resolved ?: $known;
    }

    /**
     * One OT export row, keyed by export column. Shared by every format so the
     * CSV, the workbook, the PDF and the print sheet can never drift apart.
     *
     * @return array<string, string|int>
     */
    private function otRowValues(object $row, int $serial): array
    {
        return [
            'sno' => $serial,
            'name' => $row->display_name ?: '-',
            'ot_code' => $row->generated_OT_code ?: '-',
            // Room No. / Room Extension No. have no source column yet — the
            // on-screen table shows "-" for them too. Keep the two in step.
            'room_no' => '-',
            'room_ext' => '-',
            'email' => $row->email ?: '-',
            'course' => $row->course_name ? $this->decodeEntities($row->course_name) : '-',
            'cadre' => $row->cadre_name ?: '-',
        ];
    }

    /**
     * One LBSNAA export row, keyed by export column.
     *
     * @return array<string, string|int>
     */
    private function employeeRowValues(object $row, int $serial): array
    {
        return [
            'sno' => $serial,
            'name' => $this->employeeName($row) ?: '-',
            'designation' => $row->designation_name ?: '-',
            'section' => $row->department_name ?: '-',
            'address' => $row->current_address ?: '-',
            'office_ext' => $this->trimDecimalZeros($row->office_extension_no) ?: '-',
            'mobile' => $this->trimDecimalZeros($row->mobile) ?: '-',
            'residence' => $this->trimDecimalZeros($row->residence_no) ?: '-',
            // The official address is the directory answer; the personal one is
            // only a fallback.
            'email' => ($row->officalemail ?: $row->email) ?: '-',
        ];
    }

    /**
     * Dispatch a Download choice for either directory. `csv` streams row-by-row
     * off a cursor; the three report formats need the whole set in memory to lay
     * out a paginated document.
     *
     * @param  array<string, string>  $columns    Canonical key => header label.
     * @param  list<string>           $cols       Selected keys, in report order.
     * @param  callable(object, int): array<string, string|int>  $rowValues
     */
    private function exportResponse(
        string $format,
        $query,
        array $columns,
        array $cols,
        callable $rowValues,
        string $reportTitle,
        string $filterSummary,
        string $fileBase,
        ?int $courseId = null
    ) {
        $headings = array_map(fn ($key) => $columns[$key], $cols);

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($query, $cols, $headings, $rowValues) {
                $output = fopen('php://output', 'w');
                fputcsv($output, $headings);
                $serial = 1;
                foreach ($query->cursor() as $row) {
                    $values = $rowValues($row, $serial++);
                    fputcsv($output, array_map(fn ($key) => $values[$key], $cols));
                }
                fclose($output);
            }, "{$fileBase}.csv");
        }

        // A whole directory laid out as one document — same allowance the
        // dashboard student-list report uses.
        ini_set('memory_limit', '512M');

        $rows = [];
        $serial = 1;
        foreach ($query->cursor() as $row) {
            $values = $rowValues($row, $serial++);
            $rows[] = array_map(fn ($key) => $values[$key], $cols);
        }

        $header = $this->buildReportHeaderData($courseId);
        $generatedAt = now()->format('d-m-Y H:i');

        // The print + PDF blades and StudentListReportExport are generic report
        // shells (headings/rows + institution header) shared with the dashboard
        // student list — reused so every report stays visually identical.
        $payload = array_merge([
            'headings' => $headings,
            'rows' => $rows,
            'generatedAt' => $generatedAt,
            'filterSummary' => $filterSummary,
            'reportTitle' => $reportTitle,
        ], $header);

        if ($format === 'print') {
            return view('admin.dashboard.export.student_list_print', $payload);
        }

        if ($format === 'pdf') {
            return Pdf::loadView('admin.dashboard.export.student_list_pdf', $payload)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isPhpEnabled' => true,
                    'dpi' => 96,
                ])
                ->download("{$fileBase}.pdf");
        }

        // "Excel" is a branded .xlsx, not the tab-delimited .xls these pages used
        // to emit: a flat file cannot carry the logos, the merged academy titles
        // or the blue report band that Print and PDF show.
        return Excel::download(
            new StudentListReportExport(
                $headings,
                $rows,
                $reportTitle,
                (string) $header['courseName'],
                (string) $header['courseDuration'],
                $filterSummary,
                $generatedAt,
                count($rows),
            ),
            "{$fileBase}.xlsx",
            ExcelWriter::XLSX
        );
    }

    /**
     * Logos (+ the course line, where a report has one) for the report header,
     * matching the dashboard student list so every report prints the same
     * institution block.
     *
     * @return array{logoLeft:?string, logoRight:?string, titleHindi:?string, courseName:string, courseDuration:string}
     */
    private function buildReportHeaderData(?int $courseId = null): array
    {
        // DomPDF and PhpSpreadsheet both need the bytes, not a URL.
        $toDataUri = static function (string $path): ?string {
            if (!is_file($path) || !is_readable($path)) {
                return null;
            }
            $raw = @file_get_contents($path);
            if ($raw === false) {
                return null;
            }
            $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        };

        $courseName = '';
        $courseDuration = '';
        if ($courseId && $courseId > 0 && $course = CourseMaster::find($courseId)) {
            $courseName = $this->decodeEntities((string) ($course->course_name ?? ''));
            $start = !empty($course->start_date) ? Carbon::parse($course->start_date)->format('j F Y') : '';
            $end = !empty($course->end_date) ? Carbon::parse($course->end_date)->format('j F Y') : '';
            $courseDuration = ($start && $end) ? $start . ' to ' . $end : '';
        }

        return [
            'logoLeft' => $toDataUri(public_path('admin_assets/images/logos/logo_new.png')),
            'logoRight' => $toDataUri(public_path('admin_assets/images/logos/constitution-75.png'))
                ?: $toDataUri(public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png')),
            'titleHindi' => $toDataUri(public_path('admin_assets/images/logos/lbsnaa-title-hi.png')),
            'courseName' => $courseName,
            'courseDuration' => $courseDuration,
        ];
    }

    /** Human-readable line describing which filters produced the OT report. */
    private function otFilterSummary(string $status, string $courseName, string $search): string
    {
        $parts = [$status === 'archive' ? 'Archived programmes' : 'Active programmes'];
        if ($courseName !== '') {
            $parts[] = 'Programme: ' . $courseName;
        }
        if ($search !== '') {
            $parts[] = 'Search: "' . $search . '"';
        }

        return implode(' | ', $parts);
    }

    /** Human-readable line describing which filters produced the LBSNAA report. */
    private function lbsnaaFilterSummary(string $status, string $section, string $designation, string $search): string
    {
        $parts = [$status === 'inactive' ? 'Inactive staff' : 'Active staff'];
        if ($section !== '') {
            $parts[] = 'Section: ' . $section;
        }
        if ($designation !== '') {
            $parts[] = 'Designation: ' . $designation;
        }
        if ($search !== '') {
            $parts[] = 'Search: "' . $search . '"';
        }

        return implode(' | ', $parts);
    }
}
