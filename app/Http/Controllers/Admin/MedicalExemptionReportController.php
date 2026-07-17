<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\MedicalExemptionReportDetailExport;
use App\Exports\MedicalExemptionReportSummaryExport;
use App\Models\CourseMaster;
use App\Models\ExemptionCategoryMaster;
use App\Models\MedicalCaseMaster;
use App\Models\StudentMaster;
use App\Models\StudentMedicalExemption;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

/**
 * Read-only "Medical Exemption Report".
 *
 *  • index()  — one row per Officer Trainee (student × course), with a count of
 *               their medical-exemption records; drills down into detail().
 *  • detail() — a single OT's individual exemption records, headed by per–medical
 *               case stat cards (IPD / OPD / After OPD / Referral / PT Exemption …).
 *
 * Both levels share the same filter set and export (Print handled client-side,
 * Excel + PDF server-side) as the Student Medical Exemption module.
 */
class MedicalExemptionReportController extends Controller
{
    /* ===================================================================
     | Summary listing (one row per OT + course)
     =================================================================== */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of($this->summaryQuery($request))
                ->addIndexColumn()
                ->addColumn('ot_name', function ($row) {
                    $name = trim((string) ($row->display_name ?? '')) ?: 'N/A';
                    $label = $row->generated_OT_code ? $name . ' - ' . $row->generated_OT_code : $name;

                    return '<a href="' . $this->detailUrl($row->student_master_pk, $row->course_master_pk) . '" '
                        . 'class="mer-ot-link">' . e($label) . '</a>';
                })
                ->addColumn('exemptions', function ($row) {
                    $count = str_pad((string) ($row->exemption_count ?? 0), 2, '0', STR_PAD_LEFT);

                    return '<a href="' . $this->detailUrl($row->student_master_pk, $row->course_master_pk) . '" '
                        . 'class="mer-count-link">' . $count . '</a>';
                })
                ->rawColumns(['ot_name', 'exemptions'])
                ->make(true);
        }

        // Active tab = running courses; Archive tab = ended courses (mirrors Course
        // Master). The course-filter dropdown swaps between these as the tab changes.
        $courses = CourseMaster::where('active_inactive', '1')
            ->where('end_date', '>', now())
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);

        $archivedCourses = CourseMaster::where('active_inactive', '1')
            ->where('end_date', '<', now())
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);

        return view('admin.medical_exemption_report.index', compact('courses', 'archivedCourses'));
    }

    /**
     * Grouped base query for the summary DataTable: student × course with a count.
     * Filters (course / period / free-text) are applied before the GROUP BY, then
     * the whole thing is wrapped as a sub-select so Yajra can page/order/count it.
     */
    private function summaryQuery(Request $request)
    {
        $status = $request->get('status', 'active');
        $currentDate = now()->format('Y-m-d');

        $sub = DB::table('student_medical_exemption as sme')
            ->join('student_master as s', 's.pk', '=', 'sme.student_master_pk')
            ->join('course_master as c', 'c.pk', '=', 'sme.course_master_pk')
            ->when($status === 'active', fn ($q) => $q->whereDate('c.end_date', '>=', $currentDate))
            ->when($status === 'archive', fn ($q) => $q->whereDate('c.end_date', '<', $currentDate))
            ->when($request->filled('course_id'), fn ($q) => $q->where('sme.course_master_pk', $request->course_id))
            ->when($request->filled('from_date') && $request->filled('to_date'),
                fn ($q) => $q->whereBetween('sme.from_date', [$request->from_date, $request->to_date]))
            ->when($request->filled('from_date') && ! $request->filled('to_date'),
                fn ($q) => $q->whereDate('sme.from_date', '>=', $request->from_date))
            ->when(! $request->filled('from_date') && $request->filled('to_date'),
                fn ($q) => $q->whereDate('sme.from_date', '<=', $request->to_date))
            ->when($request->filled('custom_search'), function ($q) use ($request) {
                $search = $request->custom_search;
                $q->where(function ($w) use ($search) {
                    $w->where('s.display_name', 'like', "%{$search}%")
                        ->orWhere('s.generated_OT_code', 'like', "%{$search}%")
                        ->orWhere('c.course_name', 'like', "%{$search}%");
                });
            })
            ->groupBy('sme.student_master_pk', 'sme.course_master_pk', 's.display_name', 's.generated_OT_code', 'c.course_name')
            ->select(
                'sme.student_master_pk',
                'sme.course_master_pk',
                's.display_name',
                's.generated_OT_code',
                'c.course_name',
                DB::raw('COUNT(sme.pk) as exemption_count')
            );

        return DB::query()->fromSub($sub, 't');
    }

    /* ===================================================================
     | OT detail (individual exemption records + stat cards)
     =================================================================== */
    public function detail(Request $request, $student, $course)
    {
        [$studentId, $courseId] = $this->decodeKeys($student, $course);

        if ($request->ajax()) {
            return DataTables::of($this->detailQuery($request, $studentId, $courseId))
                ->addIndexColumn()
                ->addColumn('date', fn ($r) => $r->from_date ? Carbon::parse($r->from_date)->format('d/m/Y') : '—')
                ->addColumn('medical_case', fn ($r) => $r->opd_category ?: '—')
                ->addColumn('category', fn ($r) => optional($r->category)->exemp_category_name ?: '—')
                ->addColumn('remarks', fn ($r) => $r->Description ?: '—')
                ->rawColumns([])
                ->make(true);
        }

        $studentModel = StudentMaster::findOrFail($studentId);
        $courseModel = CourseMaster::find($courseId);

        // Stat cards: total records per medical case for this OT (unfiltered totals).
        $caseCounts = StudentMedicalExemption::where('student_master_pk', $studentId)
            ->where('course_master_pk', $courseId)
            ->select('opd_category', DB::raw('COUNT(*) as total'))
            ->groupBy('opd_category')
            ->pluck('total', 'opd_category');

        $medicalCases = MedicalCaseMaster::where('active_inactive', 1)->orderBy('pk')->pluck('case_name');
        $stats = $medicalCases->map(fn ($case) => [
            'label' => $case,
            'count' => (int) ($caseCounts[$case] ?? 0),
        ])->values();

        $categories = ExemptionCategoryMaster::where('active_inactive', '1')->get(['pk', 'exemp_category_name']);

        return view('admin.medical_exemption_report.detail', [
            'student'      => $studentModel,
            'course'       => $courseModel,
            'stats'        => $stats,
            'medicalCases' => $medicalCases,
            'categories'   => $categories,
            'studentToken' => $student,
            'courseToken'  => $course,
        ]);
    }

    /** Record-level query for one OT, with the detail page's own filter set. */
    private function detailQuery(Request $request, $studentId, $courseId)
    {
        return StudentMedicalExemption::with(['category'])
            ->where('student_master_pk', $studentId)
            ->where('course_master_pk', $courseId)
            ->when($request->filled('from_date') && $request->filled('to_date'),
                fn ($q) => $q->whereBetween('from_date', [$request->from_date, $request->to_date]))
            ->when($request->filled('from_date') && ! $request->filled('to_date'),
                fn ($q) => $q->whereDate('from_date', '>=', $request->from_date))
            ->when(! $request->filled('from_date') && $request->filled('to_date'),
                fn ($q) => $q->whereDate('from_date', '<=', $request->to_date))
            ->when($request->filled('category_id'), fn ($q) => $q->where('exemption_category_master_pk', $request->category_id))
            ->when($request->filled('medical_case'), fn ($q) => $q->where('opd_category', $request->medical_case))
            ->when($request->filled('custom_search'), function ($q) use ($request) {
                $search = $request->custom_search;
                $q->where(function ($w) use ($search) {
                    $w->where('opd_category', 'like', "%{$search}%")
                        ->orWhere('Description', 'like', "%{$search}%")
                        ->orWhereHas('category', fn ($c) => $c->where('exemp_category_name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('from_date', 'desc');
    }

    /* ===================================================================
     | Exports
     =================================================================== */
    public function export(Request $request)
    {
        $format = $this->normaliseFormat($request->get('format'));
        $export = new MedicalExemptionReportSummaryExport(
            $request->get('course_id'),
            $request->get('custom_search'),
            $request->get('from_date'),
            $request->get('to_date'),
            $this->parseColumns($request->get('columns')),
            $request->get('status', 'active')
        );
        $fileName = 'medical-exemption-report-' . now()->format('Y-m-d_H-i-s');

        if ($format === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            $pdf = Pdf::loadView('admin.medical_exemption_report.export_pdf', array_merge([
                'headings'    => $export->activeHeadings(),
                'rows'        => $export->pdfRows(),
                'reportTitle' => 'Medical Exemption Report',
                'filterLine'  => $export->filterLine(),
                'printedOn'   => now()->format('d-m-Y H:i'),
            ], $this->pdfHeaderAssets()))
                ->setPaper('a4', 'portrait')
                ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'dpi' => 96]);

            return $pdf->download($fileName . '.pdf');
        }

        return Excel::download($export, $fileName . '.xlsx', ExcelFormat::XLSX);
    }

    public function detailExport(Request $request, $student, $course)
    {
        [$studentId, $courseId] = $this->decodeKeys($student, $course);

        $studentModel = StudentMaster::findOrFail($studentId);
        $courseModel = CourseMaster::find($courseId);
        $otName = trim((string) ($studentModel->display_name ?? '')) ?: 'Officer Trainee';

        $format = $this->normaliseFormat($request->get('format'));
        $export = new MedicalExemptionReportDetailExport(
            $studentId,
            $courseId,
            $otName,
            $request->get('category_id'),
            $request->get('medical_case'),
            $request->get('custom_search'),
            $request->get('from_date'),
            $request->get('to_date'),
            $this->parseColumns($request->get('columns'))
        );
        $fileName = 'medical-exemption-' . preg_replace('/[^A-Za-z0-9]+/', '-', $otName) . '-' . now()->format('Y-m-d_H-i-s');

        if ($format === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            $pdf = Pdf::loadView('admin.medical_exemption_report.detail_export_pdf', array_merge([
                'headings'    => $export->activeHeadings(),
                'rows'        => $export->pdfRows(),
                'reportTitle' => $otName . '’s Medical Exemption Report',
                'courseName'  => optional($courseModel)->course_name ?? '',
                'filterLine'  => $export->filterLine(),
                'printedOn'   => now()->format('d-m-Y H:i'),
            ], $this->pdfHeaderAssets()))
                ->setPaper('a4', 'landscape')
                ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'dpi' => 96]);

            return $pdf->download($fileName . '.pdf');
        }

        return Excel::download($export, $fileName . '.xlsx', ExcelFormat::XLSX);
    }

    /* ===================================================================
     | Helpers
     =================================================================== */
    private function detailUrl($studentPk, $coursePk): string
    {
        return route('medical.exemption.report.detail', [
            'student' => encrypt($studentPk),
            'course'  => encrypt($coursePk),
        ]);
    }

    /** Decode the encrypted route keys, aborting cleanly on a tampered URL. */
    private function decodeKeys($student, $course): array
    {
        try {
            return [decrypt($student), decrypt($course)];
        } catch (\Throwable $e) {
            abort(404);
        }
    }

    /**
     * Parse the `columns=0,1,…` param (built from the live table's visible columns)
     * into a list of integer indexes, or null when nothing usable is supplied.
     *
     * @return array<int,int>|null
     */
    private function parseColumns($raw): ?array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $cols = array_values(array_unique(array_filter(
            array_map('intval', explode(',', $raw)),
            static fn ($v) => $v >= 0
        )));

        return $cols !== [] ? $cols : null;
    }

    private function normaliseFormat($format): string
    {
        $format = strtolower((string) $format);

        return in_array($format, ['pdf', 'excel', 'xlsx', 'csv'], true)
            ? ($format === 'pdf' ? 'pdf' : 'excel')
            : 'excel';
    }

    /**
     * Branded LBSNAA header images as embedded data-URIs, shared by both PDF views
     * (mirrors the Student Medical Exemption export header).
     *
     * @return array{logoLeft:?string,logoRight:?string,titleHindi:?string}
     */
    private function pdfHeaderAssets(): array
    {
        $toDataUri = static function (string $path): ?string {
            if (! is_file($path) || ! is_readable($path)) {
                return null;
            }
            $raw = @file_get_contents($path);
            if ($raw === false) {
                return null;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        };

        $rightLogo = public_path('admin_assets/images/logos/constitution-75.png');
        if (! is_file($rightLogo)) {
            $rightLogo = public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png');
        }

        return [
            'logoLeft'   => $toDataUri(public_path('admin_assets/images/logos/logo_new.png')),
            'logoRight'  => $toDataUri($rightLogo),
            'titleHindi' => $toDataUri(public_path('admin_assets/images/logos/lbsnaa-title-hi.png')),
        ];
    }
}
