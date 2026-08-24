<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DirectoryGridExport;
use App\Http\Controllers\Concerns\NormalisesDataTablesRequest;
use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\EmployeeMaster;
use App\Models\StudentMasterCourseMap;
use App\Support\ExportCsvHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class DirectoryController extends Controller
{
    use NormalisesDataTablesRequest;

    /** Rows-per-page values the OT grid footer offers (docs/new-design-index-page.md §4B). */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100, 200];

    /**
     * Sort key => the column ORDER BY actually uses. DataTables sends a column
     * index; NormalisesDataTablesRequest resolves it to one of these keys via the
     * `name` each column declares, and anything unrecognised falls back to 'name'.
     *
     * Only the grid's own columns are here. Course Name is constant (the grid is
     * always scoped to one course) and the Room columns hold a placeholder, so
     * neither is worth a header caret.
     */
    private const OT_SORTABLE_COLUMNS = [
        'name' => 'sm.display_name',
        'ot_code' => 'sm.generated_OT_code',
        'email' => 'sm.email',
        'cadre' => 'cad.cadre_name',
    ];

    /**
     * Same contract for the LBSNAA grid. Designation and Section are joined, but
     * both master tables are small (145 / 227 rows) against 443 active employees,
     * so ordering through them is cheap — unlike the 65k-row Centcom case.
     */
    private const LBSNAA_SORTABLE_COLUMNS = [
        'name' => 'employee_master.first_name',
        'designation' => 'd.designation_name',
        'section' => 'dept.department_name',
        'mobile' => 'employee_master.mobile',
        'email' => 'employee_master.officalemail',
    ];

    /**
     * Rows past which the PDF is truncated. DomPDF's memory use grows steeply
     * with row count (measured ~276MB at 1k rows), and a truncated report that
     * says so beats a 500 halfway through a download.
     */
    private const PDF_ROW_CAP = 1500;

    /**
     * LBSNAA Directory shell. The grid is drawn by DataTables against
     * lbsnaaData() and the downloads by lbsnaaExport(); this action only renders
     * chrome plus the two filter option lists.
     */
    public function lbsnaa(Request $request)
    {
        return view('admin.directory.lbsnaa', [
            'sections' => $this->lbsnaaSectionOptions(),
            'designations' => $this->lbsnaaDesignationOptions(),
            // Neither is (int)-cast or defaulted to 0: department_master holds a
            // real row at pk 0 ("NIAR"), so `0 === (int) $pk` matched it and the
            // dropdown opened on NIAR instead of its "Section" placeholder on
            // every plain page load. null means "no filter came in on the URL".
            'selectedSection' => $request->filled('section') ? (string) $request->input('section') : null,
            'selectedDesignation' => $request->filled('designation') ? (string) $request->input('designation') : null,
        ]);
    }

    /**
     * DataTables server-side feed for the LBSNAA grid.
     *
     * The page previously rendered all 443 active employees into the markup and
     * let a client-side DataTable paginate them; search, sort and paging are all
     * SQL now, so only the page on screen reaches the browser.
     */
    public function lbsnaaData(Request $request): JsonResponse
    {
        $paging = $this->normaliseDataTablesRequest($request, self::PER_PAGE_OPTIONS[0], self::PER_PAGE_OPTIONS);
        $search = $this->resolveDirectorySearch($request);
        $sort = $this->resolveDirectorySort($request, self::LBSNAA_SORTABLE_COLUMNS);
        $filters = $this->resolveLbsnaaFilters($request);

        $filtered = $this->lbsnaaEmployeesQuery($search, $filters);
        $recordsFiltered = (int) $filtered->toBase()->getCountForPagination();
        // recordsTotal is the count BEFORE the search term; the extra query only
        // runs when a term is present (without one the two are identical).
        $recordsTotal = $search === ''
            ? $recordsFiltered
            : (int) $this->lbsnaaEmployeesQuery('', $filters)->toBase()->getCountForPagination();

        $rows = $filtered
            ->orderBy(self::LBSNAA_SORTABLE_COLUMNS[$sort['key']], $sort['dir'])
            ->orderBy('employee_master.last_name')
            ->offset($paging['start'])
            ->limit($paging['perPage'])
            ->get()
            ->values()
            ->map(fn ($employee, int $i) => [
                'sno' => $paging['start'] + $i + 1,
                'identity' => view('admin.directory._lbsnaa_row_identity', compact('employee'))->render(),
                'designation' => e((string) ($employee->designation_name ?: '-')),
                'section' => e((string) ($employee->department_name ?: '-')),
                'address' => e((string) ($employee->current_address ?: '-')),
                'office_ext' => e((string) ($employee->office_extension_no ?: '-')),
                'mobile' => e((string) ($employee->mobile ?: '-')),
                'residence' => e((string) ($employee->residence_no ?: '-')),
                'email' => e((string) ($employee->officalemail ?: $employee->email ?: '-')),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * LBSNAA Directory download / print, in the same five flavours as the OT
     * grid and off the same query, so a download can never disagree with the
     * screen. `full` ignores ?cols= and dumps every column.
     */
    public function lbsnaaExport(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print', 'full'], true), 404);

        $search = $this->resolveDirectorySearch($request);
        $sort = $this->resolveDirectorySort($request, self::LBSNAA_SORTABLE_COLUMNS);
        $filters = $this->resolveLbsnaaFilters($request);

        $defs = $this->lbsnaaExportColumnDefs();
        $columns = $format === 'full'
            ? $defs
            : $this->resolveDirectoryExportColumns($request, $defs);

        $rows = $this->lbsnaaEmployeesQuery($search, $filters)
            ->orderBy(self::LBSNAA_SORTABLE_COLUMNS[$sort['key']], $sort['dir'])
            ->orderBy('employee_master.last_name')
            ->get();

        $note = null;
        if ($format === 'pdf' && $rows->count() > self::PDF_ROW_CAP) {
            $note = 'Showing the first ' . number_format(self::PDF_ROW_CAP)
                . ' of ' . number_format($rows->count())
                . ' records — download the Excel for the complete list.';
            $rows = $rows->take(self::PDF_ROW_CAP)->values();
        }

        $filterLine = implode('  |  ', array_filter([
            $filters['sectionName'] ? 'Section: ' . $filters['sectionName'] : null,
            $filters['designationName'] ? 'Designation: ' . $filters['designationName'] : null,
            $search !== '' ? 'Search: ' . $search : null,
        ]));

        return $this->renderDirectoryExport(
            $format,
            $rows,
            $columns,
            $format === 'full' ? 'LBSNAA Directory — Full Details' : 'LBSNAA Directory',
            $filterLine,
            $note,
            $format === 'full' ? 'LBSNAADirectory_FullDetails_' : 'LBSNAADirectory_'
        );
    }

    /** Sections that actually have an active employee — 145 rows, 54 in use. */
    private function lbsnaaSectionOptions()
    {
        return EmployeeMaster::query()
            ->join('department_master as dept', 'employee_master.department_master_pk', '=', 'dept.pk')
            ->where('employee_master.status', 1)
            ->distinct()
            ->orderBy('dept.department_name')
            ->pluck('dept.department_name', 'dept.pk');
    }

    /** Designations that actually have an active employee — 227 rows, 125 in use. */
    private function lbsnaaDesignationOptions()
    {
        return EmployeeMaster::query()
            ->join('designation_master as d', 'employee_master.designation_master_pk', '=', 'd.pk')
            ->where('employee_master.status', 1)
            ->distinct()
            ->orderBy('d.designation_name')
            ->pluck('d.designation_name', 'd.pk');
    }

    /**
     * The two toolbar filters, validated against the option lists so a
     * hand-typed pk can't widen the grid past what the dropdowns offer — and so
     * the export header band can name them.
     *
     * @return array{section: int, designation: int, sectionName: ?string, designationName: ?string}
     */
    private function resolveLbsnaaFilters(Request $request): array
    {
        $sections = $this->lbsnaaSectionOptions();
        $designations = $this->lbsnaaDesignationOptions();

        // "No filter" cannot be spelled 0 here: department_master holds a real
        // row at pk 0 ("NIAR"), so the old `(int) $request->query('section', 0)`
        // default resolved to NIAR and stamped "Section: NIAR" on the header band
        // of every unfiltered export. An absent or blank param never reaches the
        // name lookup at all.
        $sectionParam = $request->query('section');
        $designationParam = $request->query('designation');

        $section = ($sectionParam === null || $sectionParam === '') ? null : (int) $sectionParam;
        $designation = ($designationParam === null || $designationParam === '') ? null : (int) $designationParam;

        $sectionName = $section === null ? null : ($sections[$section] ?? null);
        $designationName = $designation === null ? null : ($designations[$designation] ?? null);

        return [
            'section' => $sectionName === null ? 0 : (int) $section,
            'designation' => $designationName === null ? 0 : (int) $designation,
            'sectionName' => $sectionName,
            'designationName' => $designationName,
        ];
    }

    /**
     * One hydration for the grid, the count and the export, so the three can
     * never disagree about which employees belong to a filter.
     *
     * @param  array{section: int, designation: int}  $filters
     */
    private function lbsnaaEmployeesQuery(string $search, array $filters): Builder
    {
        return EmployeeMaster::query()
            ->leftJoin('designation_master as d', 'employee_master.designation_master_pk', '=', 'd.pk')
            ->leftJoin('department_master as dept', 'employee_master.department_master_pk', '=', 'dept.pk')
            ->where('employee_master.status', 1)
            ->when($filters['section'] > 0, fn ($q) => $q->where('employee_master.department_master_pk', $filters['section']))
            ->when($filters['designation'] > 0, fn ($q) => $q->where('employee_master.designation_master_pk', $filters['designation']))
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('employee_master.first_name', 'like', "%{$search}%")
                    ->orWhere('employee_master.middle_name', 'like', "%{$search}%")
                    ->orWhere('employee_master.last_name', 'like', "%{$search}%")
                    ->orWhere('employee_master.email', 'like', "%{$search}%")
                    ->orWhere('employee_master.officalemail', 'like', "%{$search}%")
                    ->orWhere('employee_master.mobile', 'like', "%{$search}%")
                    ->orWhere('d.designation_name', 'like', "%{$search}%")
                    ->orWhere('dept.department_name', 'like', "%{$search}%");
            }))
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
    }

    /**
     * Canonical LBSNAA export columns, in report order. One definition drives
     * the CSV, the .xlsx, the PDF and the print sheet.
     *
     * @return array<string, array{heading:string, width:string, align:string, value:callable}>
     */
    private function lbsnaaExportColumnDefs(): array
    {
        $name = fn ($row) => trim(implode(' ', array_filter([
            $row->first_name, $row->middle_name, $row->last_name,
        ]))) ?: '-';

        return [
            'sno' => ['heading' => 'S.No.', 'width' => '5%', 'align' => 'center',
                'value' => fn ($row, int $i) => $i + 1],
            'name' => ['heading' => 'Name', 'width' => '16%', 'align' => 'left', 'value' => $name],
            'designation' => ['heading' => 'Designation', 'width' => '15%', 'align' => 'left',
                'value' => fn ($row) => $row->designation_name ?: '-'],
            'section' => ['heading' => 'Section', 'width' => '13%', 'align' => 'left',
                'value' => fn ($row) => $row->department_name ?: '-'],
            'address' => ['heading' => 'Address', 'width' => '16%', 'align' => 'left',
                'value' => fn ($row) => $row->current_address ?: '-'],
            'office_ext' => ['heading' => 'Office Ext.', 'width' => '7%', 'align' => 'center',
                'value' => fn ($row) => $row->office_extension_no ?: '-'],
            'mobile' => ['heading' => 'Mobile', 'width' => '9%', 'align' => 'left',
                'value' => fn ($row) => $row->mobile ?: '-'],
            'residence' => ['heading' => 'Residence', 'width' => '8%', 'align' => 'left',
                'value' => fn ($row) => $row->residence_no ?: '-'],
            // Official address first, personal as the fallback — same as the grid.
            'email' => ['heading' => 'Email ID', 'width' => '18%', 'align' => 'left',
                'value' => fn ($row) => $row->officalemail ?: ($row->email ?: '-')],
        ];
    }

    /**
     * OT Directory shell. The grid is drawn by DataTables against otData() and
     * the downloads are served by otExport(); this action only renders chrome.
     */
    public function ot(Request $request)
    {
        $status = $this->resolveOtStatus($request);
        $courses = $this->otCourses($status);
        $selectedCourseId = $this->resolveOtCourseId($request, $courses);

        return view('admin.directory.ot', [
            'courses' => $courses,
            'selectedCourseId' => $selectedCourseId,
            'status' => $status,
        ]);
    }

    /**
     * DataTables server-side feed for the OT grid: search, sort and paging are
     * all SQL, so only the page on screen ever reaches the browser.
     */
    public function otData(Request $request): JsonResponse
    {
        $paging = $this->normaliseDataTablesRequest($request, self::PER_PAGE_OPTIONS[0], self::PER_PAGE_OPTIONS);
        $search = $this->resolveDirectorySearch($request);
        $sort = $this->resolveDirectorySort($request, self::OT_SORTABLE_COLUMNS);

        $status = $this->resolveOtStatus($request);
        $selectedCourseId = $this->resolveOtCourseId($request, $this->otCourses($status));

        if ($selectedCourseId <= 0) {
            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        // recordsTotal is the count BEFORE the search term; the extra query only
        // runs when a term is present (without one the two are identical).
        $filtered = $this->otStudentsQuery($selectedCourseId, $search);
        $recordsFiltered = (int) $filtered->toBase()->getCountForPagination();
        $recordsTotal = $search === ''
            ? $recordsFiltered
            : (int) $this->otStudentsQuery($selectedCourseId, '')->toBase()->getCountForPagination();

        $rows = $filtered
            ->orderBy(self::OT_SORTABLE_COLUMNS[$sort['key']], $sort['dir'])
            ->offset($paging['start'])
            ->limit($paging['perPage'])
            ->get()
            ->values()
            ->map(fn ($student, int $i) => [
                'sno' => $paging['start'] + $i + 1,
                'identity' => view('admin.directory._ot_row_identity', compact('student'))->render(),
                'room_no' => '-',
                'room_ext' => '-',
                'email' => e((string) ($student->email ?: '-')),
                'course' => e((string) ($student->course_name ?: '-')),
                'cadre' => e((string) ($student->cadre_name ?: '-')),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Active / Archived split, same rule the Course Master grid uses
     * (CourseMasterDataTable: end_date >= today is current + upcoming,
     * end_date < today is expired). Anything unrecognised reads as Active.
     */
    private function resolveOtStatus(Request $request): string
    {
        return $request->input('status') === 'archive' ? 'archive' : 'active';
    }

    /** The programmes one tab offers, newest first. */
    private function otCourses(string $status)
    {
        $today = now()->toDateString();

        return CourseMaster::query()
            ->where('active_inactive', 1)
            ->when(
                $status === 'archive',
                fn ($query) => $query->where('end_date', '<', $today),
                fn ($query) => $query->where('end_date', '>=', $today)
            )
            ->orderByDesc('end_date')
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'couse_short_name']);
    }

    /**
     * The programme the grid is scoped to. A course_id carried over from the
     * other tab isn't in this tab's list — drop it rather than render rows the
     * tab says don't belong here — and fall back to the tab's newest programme.
     */
    private function resolveOtCourseId(Request $request, $courses): int
    {
        $courseId = (int) $request->input('course_id', 0);

        if ($courseId > 0 && ! $courses->contains('pk', $courseId)) {
            $courseId = 0;
        }

        if ($courseId <= 0 && $courses->isNotEmpty()) {
            $courseId = (int) $courses->first()->pk;
        }

        return $courseId;
    }

    /**
     * The search term for either grid, read from either name: DataTables' own
     * request arrives as
     * ?q (the trait rewrites search[value] onto it), while an export link carries
     * ?q directly. `search` stays accepted so a hand-typed URL still works.
     */
    private function resolveDirectorySearch(Request $request): string
    {
        $raw = $request->query('q', $request->query('search', ''));

        return trim((string) (is_array($raw) ? ($raw['value'] ?? '') : $raw));
    }

    /**
     * ?sort / ?dir, whitelisted against one grid's sortable map. An unknown key
     * sorts by name rather than reaching the query — the value lands in an
     * ORDER BY, so it must never come straight off the request.
     *
     * @param  array<string, string>  $map
     * @return array{key: string, dir: string}
     */
    private function resolveDirectorySort(Request $request, array $map): array
    {
        $key = (string) $request->query('sort', 'name');
        $dir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        return [
            'key' => isset($map[$key]) ? $key : 'name',
            'dir' => $dir,
        ];
    }

    /**
     * One hydration for the grid, the count and the export, so the three can
     * never disagree about which rows belong to a programme.
     */
    private function otStudentsQuery(int $courseId, string $search): Builder
    {
        return StudentMasterCourseMap::query()
            ->join('student_master as sm', 'student_master_course__map.student_master_pk', '=', 'sm.pk')
            ->join('course_master as cm', 'student_master_course__map.course_master_pk', '=', 'cm.pk')
            ->leftJoin('cadre_master as cad', 'sm.cadre_master_pk', '=', 'cad.pk')
            ->where('student_master_course__map.active_inactive', 1)
            ->where('sm.status', 1)
            ->where('cm.active_inactive', 1)
            ->where('cm.pk', $courseId)
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('sm.display_name', 'like', "%{$search}%")
                    ->orWhere('sm.generated_OT_code', 'like', "%{$search}%")
                    ->orWhere('sm.email', 'like', "%{$search}%")
                    ->orWhere('cad.cadre_name', 'like', "%{$search}%");
            }))
            ->select([
                'sm.pk',
                'sm.display_name',
                'sm.generated_OT_code',
                'sm.email',
                'sm.photo_path',
                'cm.course_name',
                'cad.cadre_name',
            ]);
    }

    /**
     * Canonical OT export columns, in report order. One definition drives the
     * CSV, the .xlsx, the PDF and the print sheet, so the four cannot disagree
     * about which columns a report has or what order they are in.
     *
     * `width` / `align` are emitted INLINE by both blades — DomPDF ignores
     * <colgroup> and its own element rule beats a class selector.
     *
     * @return array<string, array{heading:string, width:string, align:string, value:callable}>
     */
    private function otExportColumnDefs(): array
    {
        return [
            'sno' => ['heading' => 'S.No.', 'width' => '6%', 'align' => 'center',
                'value' => fn ($row, int $i) => $i + 1],
            'name' => ['heading' => 'Name', 'width' => '20%', 'align' => 'left',
                'value' => fn ($row) => trim((string) $row->display_name) ?: '-'],
            'ot_code' => ['heading' => 'OT Code', 'width' => '9%', 'align' => 'center',
                'value' => fn ($row) => $row->generated_OT_code ?: '-'],
            // Not stored on student_master yet — the grid shows the same placeholder.
            'room_no' => ['heading' => 'Room No.', 'width' => '8%', 'align' => 'center',
                'value' => fn () => '-'],
            'room_ext' => ['heading' => 'Room Extension No.', 'width' => '11%', 'align' => 'center',
                'value' => fn () => '-'],
            'email' => ['heading' => 'Email ID', 'width' => '22%', 'align' => 'left',
                'value' => fn ($row) => $row->email ?: '-'],
            'course' => ['heading' => 'Course Name', 'width' => '12%', 'align' => 'left',
                'value' => fn ($row) => $row->course_name ?: '-'],
            'cadre' => ['heading' => 'Cadre Name', 'width' => '12%', 'align' => 'left',
                'value' => fn ($row) => $row->cadre_name ?: '-'],
        ];
    }

    /**
     * Turn the Columns modal's ?cols= list into a whitelisted, canonically
     * ordered set of export columns. Empty / absent / unrecognised => every
     * column. Intersecting against the defs (rather than trusting ?cols=) keeps
     * the canonical order, so a hand-edited URL can neither reorder the report
     * nor inject a column.
     *
     * @param  array<string, array{heading:string, width:string, align:string, value:callable}>  $defs
     * @return array<string, array{heading:string, width:string, align:string, value:callable}>
     */
    private function resolveDirectoryExportColumns(Request $request, array $defs): array
    {
        $wanted = array_filter(array_map('trim', explode(',', (string) $request->query('cols', ''))));

        if (empty($wanted)) {
            return $defs;
        }

        $resolved = array_intersect_key($defs, array_flip($wanted));

        return $resolved ?: $defs;
    }

    /**
     * OT Directory download / print, in five flavours.
     *
     * All of them read the SAME status / course_id / q / sort / dir the grid feed
     * does, so a download can never disagree with what is on screen. `full` is
     * the one exception on columns: it ignores ?cols= and dumps every column.
     */
    public function otExport(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print', 'full'], true), 404);

        $status = $this->resolveOtStatus($request);
        $courses = $this->otCourses($status);
        $courseId = $this->resolveOtCourseId($request, $courses);
        $search = $this->resolveDirectorySearch($request);
        $sort = $this->resolveDirectorySort($request, self::OT_SORTABLE_COLUMNS);

        // "Full Details (Excel)" is the everything dump — the Columns modal must
        // not be able to thin it out, that is the whole point of the menu item.
        $columns = $format === 'full'
            ? $this->otExportColumnDefs()
            : $this->resolveDirectoryExportColumns($request, $this->otExportColumnDefs());

        $rows = $courseId > 0
            ? $this->otStudentsQuery($courseId, $search)
                ->orderBy(self::OT_SORTABLE_COLUMNS[$sort['key']], $sort['dir'])
                ->get()
            : new Collection();

        $note = null;
        if ($format === 'pdf' && $rows->count() > self::PDF_ROW_CAP) {
            $note = 'Showing the first ' . number_format(self::PDF_ROW_CAP)
                . ' of ' . number_format($rows->count())
                . ' records — download the Excel for the complete list.';
            $rows = $rows->take(self::PDF_ROW_CAP)->values();
        }

        $programme = $courses->firstWhere('pk', $courseId);
        $programmeName = $programme
            ? trim((string) ($programme->couse_short_name ?: $programme->course_name))
            : null;

        $filterLine = implode('  |  ', array_filter([
            $programmeName ? 'Programme: ' . $programmeName : null,
            $status === 'archive' ? 'Archived' : 'Active',
            $search !== '' ? 'Search: ' . $search : null,
        ]));

        return $this->renderDirectoryExport(
            $format,
            $rows,
            $columns,
            $format === 'full' ? 'OT Directory — Full Details' : 'OT Directory',
            $filterLine,
            $note,
            $format === 'full' ? 'OTDirectory_FullDetails_' : 'OTDirectory_'
        );
    }

    /**
     * Serve one resolved report in whichever of the five formats was asked for.
     *
     * Shared by both directory grids so the formats can't drift apart per page:
     * one set of blades, one .xlsx class, one CSV band.
     *
     * @param  \Illuminate\Support\Collection  $rows
     * @param  array<string, array{heading:string, width:string, align:string, value:callable}>  $columns
     */
    private function renderDirectoryExport(
        string $format,
        $rows,
        array $columns,
        string $title,
        string $filterLine,
        ?string $note,
        string $slug
    ) {
        $exportDate = now()->format('d-m-Y h:i A');
        $stamp = now()->format('YmdHis');

        if ($format === 'print') {
            return view('admin.directory.partials.export_print', compact('columns', 'rows', 'title', 'filterLine', 'exportDate'));
        }

        if ($format === 'excel' || $format === 'full') {
            return Excel::download(
                new DirectoryGridExport($rows, $columns, $exportDate, $filterLine, $title),
                $slug . $stamp . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            return Pdf::loadView('admin.directory.partials.export_pdf', compact('columns', 'rows', 'title', 'filterLine', 'exportDate', 'note'))
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // The page-number script at the end of the view needs this.
                    'isPhpEnabled' => true,
                ])
                ->download($slug . $stamp . '.pdf');
        }

        // Same band the .xlsx and the print/PDF headers carry, so the CSV names
        // the applied filters too.
        $csvBand = ExportCsvHeader::rows($title, $filterLine ?: null, $exportDate, $rows->count(), $note);

        return response()->streamDownload(function () use ($columns, $rows, $csvBand) {
            $handle = fopen('php://output', 'w');
            foreach ($csvBand as $bandRow) {
                fputcsv($handle, $bandRow);
            }
            fputcsv($handle, array_values(array_map(fn ($col) => $col['heading'], $columns)));
            foreach ($rows as $index => $row) {
                fputcsv($handle, array_values(array_map(fn ($col) => $col['value']($row, $index), $columns)));
            }
            fclose($handle);
        }, $slug . $stamp . '.csv');
    }
}

