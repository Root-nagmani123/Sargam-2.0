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
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /** Rows-per-page choices offered by the footer select. */
    private const PER_PAGE_OPTIONS = ['10', '25', '50', '100', '200', 'all'];

    /**
     * Download formats offered by the OT directory. Print / PDF / Excel share one
     * branded report layout; `csv` stays a flat machine-readable data file.
     */
    private const OT_EXPORT_FORMATS = ['csv', 'excel', 'pdf', 'print'];

    public function lbsnaa(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $sort = (string) $request->input('sort', 'name_asc');
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 10;
        }
        $export = (string) $request->input('export', '');
        $sortMap = [
            'name_asc' => ['employee_master.first_name', 'asc'],
            'name_desc' => ['employee_master.first_name', 'desc'],
            'designation_asc' => ['d.designation_name', 'asc'],
            'designation_desc' => ['d.designation_name', 'desc'],
        ];
        [$sortColumn, $sortDirection] = $sortMap[$sort] ?? $sortMap['name_asc'];

        $employeesQuery = EmployeeMaster::query()
            ->leftJoin('designation_master as d', 'employee_master.designation_master_pk', '=', 'd.pk')
            ->leftJoin('department_master as dept', 'employee_master.department_master_pk', '=', 'dept.pk')
            ->where('employee_master.status', 1)
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
                    ->orWhere('d.designation_name', 'like', "%{$search}%")
                    ->orWhere('dept.department_name', 'like', "%{$search}%");
            });
        }

        $employeesQuery = $employeesQuery
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('employee_master.last_name');

        if (in_array($export, ['csv', 'excel'], true)) {
            return $this->streamEmployeesExport($employeesQuery->cursor(), $export);
        }

        $employees = $employeesQuery->get();

        return view('admin.directory.lbsnaa', compact('employees'));
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
        $search = trim((string) $request->input('search', ''));
        $sort = (string) $request->input('sort', 'name_asc');

        // The footer select round-trips as a string ('all' is a valid choice), so
        // keep the raw option for the view and derive the numeric page size here.
        $perPage = (string) $request->input('per_page', '10');
        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = '10';
        }
        $rowsPerPage = $perPage === 'all' ? 100000 : (int) $perPage;

        $export = (string) $request->input('export', '');
        $sortMap = [
            'name_asc' => ['sm.display_name', 'asc'],
            'name_desc' => ['sm.display_name', 'desc'],
            'ot_code_asc' => ['sm.generated_OT_code', 'asc'],
            'ot_code_desc' => ['sm.generated_OT_code', 'desc'],
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

        $students = new LengthAwarePaginator([], 0, $rowsPerPage);
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

            $studentsQuery = $studentsQuery->orderBy($sortColumn, $sortDirection);

            if (in_array($export, self::OT_EXPORT_FORMATS, true)) {
                return $this->otExport(
                    $studentsQuery,
                    $export,
                    $this->resolveOtExportCols((string) $request->input('cols', '')),
                    $selectedCourseId,
                    $status,
                    $search
                );
            }

            $students = $studentsQuery
                ->paginate($rowsPerPage)
                ->withQueryString();

            $students->getCollection()->transform(function ($row) {
                $row->course_name = $this->decodeEntities((string) $row->course_name);
                $row->initials = $this->initialsFor((string) $row->display_name);

                return $row;
            });
        }

        return view('admin.directory.ot', compact('students', 'courses', 'selectedCourseId', 'search', 'sort', 'perPage', 'status'));
    }

    private function streamEmployeesExport(iterable $rows, string $export): StreamedResponse
    {
        $isExcel = $export === 'excel';
        $filename = $isExcel ? 'lbsnaa-directory.xls' : 'lbsnaa-directory.csv';
        $delimiter = $isExcel ? "\t" : ',';

        return response()->streamDownload(function () use ($rows, $delimiter) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['S.No.', 'Name', 'Designation', 'Section', 'Address', 'Office Extension', 'Mobile', 'Residence', 'Email'], $delimiter);
            $index = 1;
            foreach ($rows as $row) {
                $name = trim(($row->first_name ?? '') . ' ' . ($row->middle_name ?? '') . ' ' . ($row->last_name ?? ''));
                $email = $row->officalemail ?: $row->email;
                fputcsv($output, [
                    $index++,
                    $name ?: '-',
                    $row->designation_name ?: '-',
                    $row->department_name ?: '-',
                    $row->current_address ?: '-',
                    $row->office_extension_no ?: '-',
                    $row->mobile ?: '-',
                    $row->residence_no ?: '-',
                    $email ?: '-',
                ], $delimiter);
            }
            fclose($output);
        }, $filename);
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
     * first letter of the last. display_name is one field, so it's split on
     * whitespace — a single-word name yields one letter, an empty one yields ''
     * (the view then shows a person glyph instead).
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

        // "-" is the placeholder the directory uses for a missing name.
        return preg_match('/^\p{L}/u', $initials) ? mb_strtoupper($initials) : '';
    }

    /**
     * Turn the Columns modal's `?cols=` list into an ordered, validated column list.
     * Intersecting against the canonical keys (rather than trusting the request)
     * preserves report order and drops anything unrecognised. Empty/absent = all.
     *
     * @return list<string>
     */
    private function resolveOtExportCols(string $cols): array
    {
        $known = array_keys(self::OT_EXPORT_COLUMNS);
        $wanted = array_filter(array_map('trim', explode(',', $cols)));

        if (!$wanted) {
            return $known;
        }

        $resolved = array_values(array_intersect($known, $wanted));

        return $resolved ?: $known;
    }

    /**
     * One export row, keyed by export column. Shared by every format so the CSV,
     * the workbook, the PDF and the print sheet can never drift apart.
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
     * Dispatch a Download choice. `csv` streams row-by-row off a cursor; the three
     * report formats need the whole set in memory to lay out a paginated document.
     *
     * @param  list<string>  $cols  Export column keys, in report order.
     */
    private function otExport(
        $query,
        string $format,
        array $cols,
        int $courseId,
        string $status,
        string $search
    ) {
        if ($format === 'csv') {
            return $this->streamOtExport($query->cursor(), $cols);
        }

        // A whole course of trainees laid out as one document — same allowance the
        // dashboard student-list report uses.
        ini_set('memory_limit', '512M');

        $headings = array_map(fn ($key) => self::OT_EXPORT_COLUMNS[$key], $cols);
        $rows = [];
        $serial = 1;
        foreach ($query->cursor() as $row) {
            $values = $this->otRowValues($row, $serial++);
            $rows[] = array_map(fn ($key) => $values[$key], $cols);
        }

        $header = $this->buildOtExportHeaderData($courseId);
        $reportTitle = 'OT Directory';
        $generatedAt = now()->format('d-m-Y H:i');
        $filterSummary = $this->otFilterSummary($status, (string) $header['courseName'], $search);
        $fileBase = 'ot_directory_' . now()->format('Ymd_His');

        // The print + PDF blades and StudentListReportExport are generic report
        // shells (headings/rows + institution header) shared with the dashboard
        // student list — reused so the two reports stay visually identical.
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

        // "Excel" is a branded .xlsx, not the tab-delimited .xls this page used to
        // emit: a flat file cannot carry the logos, the merged academy titles or
        // the blue report band that Print and PDF show.
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
     * Logos + course line for the report header, matching the dashboard student
     * list so both reports print the same institution block.
     *
     * @return array{logoLeft:?string, logoRight:?string, titleHindi:?string, courseName:string, courseDuration:string}
     */
    private function buildOtExportHeaderData(int $courseId): array
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
        if ($courseId > 0 && $course = CourseMaster::find($courseId)) {
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

    /** Human-readable line describing which filters produced the report. */
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

    /**
     * @param  list<string>  $cols  Export column keys, in report order.
     */
    private function streamOtExport(iterable $rows, array $cols): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $cols) {
            $output = fopen('php://output', 'w');
            fputcsv($output, array_map(fn ($key) => self::OT_EXPORT_COLUMNS[$key], $cols));
            $serial = 1;
            foreach ($rows as $row) {
                $values = $this->otRowValues($row, $serial++);
                fputcsv($output, array_map(fn ($key) => $values[$key], $cols));
            }
            fclose($output);
        }, 'ot_directory_' . now()->format('Ymd_His') . '.csv');
    }
}

