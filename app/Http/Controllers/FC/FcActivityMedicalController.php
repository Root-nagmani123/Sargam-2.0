<?php

namespace App\Http\Controllers\FC;

use App\Exports\FcActivityMedicalListExport;
use App\Http\Controllers\Controller;
use App\Models\FC\FcActivityMaster;
use App\Models\FC\FcFinalFinding;
use App\Models\FC\FcOtActivity;
use App\Models\FC\FcOtDetail;
use App\Models\FC\FcPathReport;
use App\Models\FC\FcPreHistory;
use App\Services\FC\FcPostArrivalAccessService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class FcActivityMedicalController extends Controller
{
    public function __construct(private FcPostArrivalAccessService $access)
    {
    }

    private function authorizeMedical(): void
    {
        if (! $this->access->canAccessMedicalModule()) {
            abort(403, 'Medical module not available for this account.');
        }
    }

    public function index(): View
    {
        $this->authorizeMedical();

        // Active courses: has at least one fc_ot_details row OR active_inactive=1.
        // Inactive/archived: active_inactive != 1 but has historical fc_ot_details data.
        $allCourses = \Illuminate\Support\Facades\DB::table('course_master')
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'active_inactive']);

        // Course pks that actually appear in fc_ot_details (for showing archived ones with data)
        $pksInOt = \Illuminate\Support\Facades\DB::table('fc_ot_details')
            ->whereNotNull('course_master_pk')
            ->where('status', 1)
            ->distinct()
            ->pluck('course_master_pk')
            ->flip();

        $activeCourses   = $allCourses->filter(fn ($c) => $c->active_inactive == 1)->values();
        $archivedCourses = $allCourses->filter(fn ($c) => $c->active_inactive != 1 && $pksInOt->has($c->pk))->values();

        return view('admin.fc-activities.medical.index', compact('activeCourses', 'archivedCourses'));
    }

    public function dataTable(Request $request): JsonResponse
    {
        $this->authorizeMedical();

        $query = $this->medicalOtListQuery($request);

        return DataTables::eloquent($query)
            ->editColumn('otcode', fn (FcOtDetail $o) => '<code>'.e($o->otcode).'</code>')
            ->addColumn('pre_history_badge', function (FcOtDetail $o) {
                return $o->pre_history_exists
                    ? '<span class="badge bg-light text-dark border">Yes</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary border">No</span>';
            })
            ->addColumn('consultation', function (FcOtDetail $o) {
                $id = (int) $o->id;
                $checked = $o->consultation_required ? ' checked' : '';
                $url = e(route('fc-reg.admin.activities.medical.consultation'));

                return '<div class="form-check mb-0 py-0">'
                    .'<input class="form-check-input js-medical-consultation" type="checkbox" '
                    .'data-url="'.$url.'" data-id="'.$id.'"'.$checked.' '
                    .'id="med-consult-'.$id.'" aria-label="Consultation required">'
                    .'<label class="form-check-label small text-nowrap ps-1" for="med-consult-'.$id.'">Consultation</label>'
                    .'</div>';
            })
            ->addColumn('actions', function (FcOtDetail $o) {
                $preHistoryUrl = route('fc-reg.admin.activities.medical.pre-history', [
                    'course_master_pk' => $o->course_master_pk,
                    'otcode' => $o->otcode,
                ]);
                $reportUrl = route('fc-reg.admin.activities.medical.show', [
                    'course_master_pk' => $o->course_master_pk,
                    'ot' => $o->otcode,
                ]);

                return '<button type="button" class="btn btn-outline-primary btn-sm js-medical-prehistory" data-url="'.e($preHistoryUrl).'" data-otname="'.e($o->otname).'" data-otcode="'.e($o->otcode).'">Pre-medical</button>'
                    .' <a class="btn btn-outline-secondary btn-sm" href="'.e($reportUrl).'" target="_blank" rel="noopener">Full report</a>';
            })
            ->rawColumns(['otcode', 'pre_history_badge', 'consultation', 'actions'])
            ->toJson();
    }

    /**
     * Printable HTML (browser print / new tab). Uses current filter query params.
     */
    public function exportPrint(Request $request): View
    {
        $this->authorizeMedical();

        $ctx = $this->buildMedicalExportContext($request);

        return view('admin.fc-activities.medical.export-report', array_merge($ctx, [
            'autoprint' => $request->boolean('autoprint'),
            'forPdf' => false,
        ]));
    }

    /**
     * PDF download — same data and branding as print.
     */
    public function exportPdf(Request $request)
    {
        $this->authorizeMedical();

        @ini_set('memory_limit', '256M');
        @set_time_limit(120);

        $ctx = $this->buildMedicalExportContext($request);

        $pdf = Pdf::loadView('admin.fc-activities.medical.export-report', array_merge($ctx, [
            'autoprint' => false,
            'forPdf' => true,
        ]))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 96,
            ]);

        $fileName = 'fc-medical-trainees-'.now()->format('Y-m-d_His').'.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Excel export with header banner (Maatwebsite Excel).
     */
    public function exportExcel(Request $request)
    {
        $this->authorizeMedical();

        $rows = $this->medicalExportTableRows($request);
        $filterDescription = $this->medicalExportFilterDescription($request);
        $fileName = 'fc-medical-trainees-'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(
            new FcActivityMedicalListExport($rows, $filterDescription),
            $fileName
        );
    }

    private function medicalOtListQuery(Request $request): Builder
    {
        $query = FcOtDetail::query()
            ->active()
            ->select('fc_ot_details.*')
            ->selectRaw("COALESCE(cm.course_name, '') as course_display_name")
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'fc_ot_details.course_master_pk')
            ->withExists([
                'preHistory' => function ($q) {
                    $q->whereColumn('fc_pre_history.course_master_pk', 'fc_ot_details.course_master_pk')
                        ->whereNotNull('fc_pre_history.course_master_pk');
                },
            ]);

        $this->applyMedicalListFilters($query, $request);

        return $query;
    }

    private function applyMedicalListFilters(Builder $query, Request $request): void
    {
        if ($request->filled('course_filter')) {
            $c = trim((string) $request->input('course_filter'));
            if ($c !== '' && ctype_digit($c)) {
                $query->where('fc_ot_details.course_master_pk', (int) $c);
            }
        }

        if ($request->filled('service_filter')) {
            $s = trim((string) $request->input('service_filter'));
            if ($s !== '') {
                $query->where('fc_ot_details.service', 'like', '%'.$s.'%');
            }
        }

        if ($request->filled('consultation_filter') && in_array((string) $request->input('consultation_filter'), ['0', '1'], true)) {
            $query->where('fc_ot_details.consultation_required', (int) $request->input('consultation_filter'));
        }
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, emblemSrc: string, logoSrc: string, filterLine: string, title: string}
     */
    private function buildMedicalExportContext(Request $request): array
    {
        $tableRows = $this->medicalExportTableRows($request);

        return [
            'rows' => $tableRows->all(),
            'emblemSrc' => $this->medicalExportEmblemDataUri(),
            'logoSrc' => $this->medicalExportLogoDataUri(),
            'filterLine' => $this->medicalExportFilterDescription($request),
            'title' => 'FC Activities — Medical trainees',
        ];
    }

    /**
     * @return Collection<int, array{sno:int, otname:string, otcode:string, course:string, service:string, pre_history:string, consultation:string}>
     */
    private function medicalExportTableRows(Request $request): Collection
    {
        return $this->medicalOtListQuery($request)
            ->orderBy('fc_ot_details.otname')
            ->get()
            ->values()
            ->map(function (FcOtDetail $o, int $i) {
                return [
                    'sno' => $i + 1,
                    'otname' => (string) $o->otname,
                    'otcode' => (string) $o->otcode,
                    'course' => (string) $o->course_display_name,
                    'service' => (string) $o->service,
                    'pre_history' => $o->pre_history_exists ? 'Yes' : 'No',
                    'consultation' => $o->consultation_required ? 'Yes' : 'No',
                ];
            });
    }

    private function medicalExportFilterDescription(Request $request): string
    {
        $parts = [];
        if ($request->filled('course_filter')) {
            $c = trim($request->string('course_filter'));
            if (ctype_digit($c)) {
                $name = \Illuminate\Support\Facades\DB::table('course_master')->where('pk', (int)$c)->value('course_name');
                $parts[] = 'Course: '.($name ?: $c);
            } else {
                $parts[] = 'Course: '.$c;
            }
        }
        if ($request->filled('service_filter')) {
            $parts[] = 'Service contains: '.$request->string('service_filter');
        }
        if ($request->filled('consultation_filter') && $request->string('consultation_filter') !== '') {
            $parts[] = 'Consultation marked: '.($request->string('consultation_filter') === '1' ? 'Yes' : 'No');
        }

        return $parts === [] ? 'All active trainees (no filters)' : implode(' | ', $parts);
    }

    private function medicalExportEmblemDataUri(): string
    {
        foreach ([public_path('images/ashoka.png'), public_path('images/lbsnaa_logo.png')] as $path) {
            if (is_file($path) && is_readable($path)) {
                $raw = @file_get_contents($path);
                if ($raw !== false) {
                    $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

                    return 'data:'.$mime.';base64,'.base64_encode($raw);
                }
            }
        }

        $url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->connectTimeout(6)->get($url);
            if ($response->successful() && strlen($response->body()) > 100) {
                return 'data:image/png;base64,'.base64_encode($response->body());
            }
        } catch (\Throwable $e) {
        }

        return $url;
    }

    private function medicalExportLogoDataUri(): string
    {
        foreach ([public_path('images/lbsnaa_logo.jpg'), public_path('images/lbsnaa_logo.png')] as $path) {
            if (is_file($path) && is_readable($path)) {
                $raw = @file_get_contents($path);
                if ($raw !== false) {
                    $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

                    return 'data:'.$mime.';base64,'.base64_encode($raw);
                }
            }
        }
        foreach ([public_path('admin_assets/images/logos/logo.png'), public_path('admin_assets/images/logos/logo.svg')] as $localPath) {
            if (is_file($localPath) && is_readable($localPath)) {
                $raw = @file_get_contents($localPath);
                if ($raw !== false) {
                    $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
                    $mime = match ($ext) {
                        'svg' => 'image/svg+xml',
                        'png' => 'image/png',
                        default => 'image/jpeg',
                    };

                    return 'data:'.$mime.';base64,'.base64_encode($raw);
                }
            }
        }

        return '';
    }

    public function updateConsultation(Request $request): JsonResponse
    {
        $this->authorizeMedical();

        $validated = $request->validate([
            'fc_ot_detail_id' => 'required|integer|exists:fc_ot_details,id',
            'consultation_required' => 'required|boolean',
        ]);

        $ot = FcOtDetail::query()
            ->active()
            ->whereKey($validated['fc_ot_detail_id'])
            ->firstOrFail();

        $required = filter_var($validated['consultation_required'], FILTER_VALIDATE_BOOLEAN);
        $marker = Auth::user()->user_name ?? '' ?? '';

        $ot->forceFill([
            'consultation_required' => $required,
            'consultation_required_at' => $required ? now() : null,
            'consultation_marked_by' => $required ? $marker : null,
        ])->save();

        return response()->json([
            'ok' => true,
            'consultation_required' => (bool) $ot->consultation_required,
        ]);
    }

    public function preHistoryPreview(Request $request): JsonResponse
    {
        $this->authorizeMedical();

        $request->validate([
            'course_master_pk' => 'required|integer|exists:course_master,pk',
            'otcode' => 'required|string|max:100',
        ]);

        $ot = FcOtDetail::query()
            ->active()
            ->where('otcode', $request->string('otcode'))
            ->where('course_master_pk', $request->integer('course_master_pk'))
            ->firstOrFail();

        $preHistory = $this->resolvePreHistoryForOt($ot->user_id, $ot->course_master_pk ? (int) $ot->course_master_pk : null);

        $html = view('admin.fc-activities.medical.partials.pre-history-block', compact('preHistory'))->render();

        return response()->json([
            'html' => $html,
            'otname' => $ot->otname,
            'otcode' => $ot->otcode,
            'course_master_pk' => $ot->course_master_pk,
        ]);
    }

    public function show(Request $request): View
    {
        $this->authorizeMedical();

        $courseMasterPk = (int) $request->query('course_master_pk', 0);
        $otcode = trim((string) $request->query('ot', ''));

        $ot = FcOtDetail::query()
            ->where('otcode', $otcode)
            ->where('course_master_pk', $courseMasterPk)
            ->orderByDesc('status')
            ->orderByDesc('id')
            ->first();

        if ($ot === null) {
            abort(404, 'No OT record found for this OT code and course.');
        }

        // Derive the course name string for filtering related tables that still use varchar course.
        $course = $courseMasterPk > 0
            ? (string) (DB::table('course_master')->where('pk', $courseMasterPk)->value('course_name') ?? '')
            : '';

        $medId = $this->access->medicalDepartmentId();
        $masters = ($medId !== null)
            ? FcActivityMaster::query()->active()->where('department_id', $medId)->ordered()->get()
            : collect();

        $actCodes = $masters->pluck('menuid')->all();

        $preHistory = $this->resolvePreHistoryForOt($ot->user_id, $ot->course_master_pk ? (int) $ot->course_master_pk : null);

        $activityRows = FcOtActivity::query()
            ->where(fc_user_col('fc_otactivity_details'), $ot->user_id)
            ->where(function ($q) use ($course) {
                $q->where('course', $course)
                    ->orWhereRaw('TRIM(course) = ?', [trim($course)]);
            })
            ->when($actCodes !== [], fn ($q) => $q->whereIn('activity', $actCodes))
            ->orderBy('id')
            ->get();

        $byActivity = $activityRows->groupBy('activity');

        $heightKey = $masters->firstWhere('menuid', 'height')?->menuid ?? 'height';
        $weightKey = $masters->firstWhere('menuid', 'weight')?->menuid ?? 'weight';

        $latestHeightRow = $this->latestActivityRow($byActivity, $heightKey);
        $latestWeightRow = $this->latestActivityRow($byActivity, $weightKey);
        $firstHeightRow = $this->firstActivityRow($byActivity, $heightKey);
        $firstWeightRow = $this->firstActivityRow($byActivity, $weightKey);

        $height = (float) ($latestHeightRow->activityval ?? 0);
        $weight = (float) ($latestWeightRow->activityval ?? 0);
        $bmi = $height > 0 ? round(($weight / ($height * $height)) * 10000, 1) : 0;
        $bmiClass = $this->classifyBmi($bmi);

        $firstHeight = (float) ($firstHeightRow->activityval ?? 0);
        $firstWeight = (float) ($firstWeightRow->activityval ?? 0);
        $firstBmi = $firstHeight > 0 ? round(($firstWeight / ($firstHeight * $firstHeight)) * 10000, 1) : 0;
        $firstBmiClass = $this->classifyBmi($firstBmi);

        $heightCount = $byActivity->get($heightKey)?->count() ?? 0;
        $weightCount = $byActivity->get($weightKey)?->count() ?? 0;

        $bmiComparison = null;
        if ($firstHeight > 0 && $firstWeight > 0 && $height > 0 && $weight > 0) {
            $hasMultipleReadings = $heightCount > 1 || $weightCount > 1;
            $valuesChanged = abs($firstHeight - $height) > 0.00001 || abs($firstWeight - $weight) > 0.00001;
            if ($hasMultipleReadings || $valuesChanged) {
                $bmiComparison = [
                    'first' => [
                        'height' => $firstHeight,
                        'weight' => $firstWeight,
                        'bmi' => $firstBmi,
                        'class' => $firstBmiClass,
                    ],
                    'latest' => [
                        'height' => $height,
                        'weight' => $weight,
                        'bmi' => $bmi,
                        'class' => $bmiClass,
                    ],
                    'delta_bmi' => round($bmi - $firstBmi, 1),
                ];
            }
        }

        $pathReports = FcPathReport::where(fc_user_col('fc_path_report'), $ot->user_id)->where('course', $course)->get();
        $finalFindings = FcFinalFinding::where(fc_user_col('fc_final_findings'), $ot->user_id)->where('course', $course)->get();

        $vitalsOrdered = $masters->map(function (FcActivityMaster $m) use ($byActivity) {
            $rows = $byActivity->get($m->menuid, collect());
            $readings = $rows->map(function (FcOtActivity $r) {
                $when = trim((string) $r->activitydt);
                if ($when === '' && $r->created_at) {
                    $when = $r->created_at->format('d-m-Y H:i');
                }

                return [
                    'id' => (int) $r->id,
                    'value' => $r->activityval,
                    'when' => $when !== '' ? $when : '—',
                    'by' => $r->submitedby ? (string) $r->submitedby : '—',
                ];
            })->values()->all();

            $compare = $this->vitalReadingsNumericCompare($readings);

            return [
                'menuid' => $m->menuid,
                'label' => $m->menun,
                'entry_policy' => $m->entry_policy,
                'readings' => $readings,
                'reading_count' => count($readings),
                'latest_value' => $rows->sortByDesc('id')->first()?->activityval,
                'compare' => $compare,
            ];
        });

        $activities = $byActivity->map(fn ($rows) => $rows->sortByDesc('id')->first()?->activityval)->all();

        return view('admin.fc-activities.medical.report', compact(
            'ot',
            'course',
            'preHistory',
            'activities',
            'vitalsOrdered',
            'height',
            'weight',
            'bmi',
            'bmiClass',
            'bmiComparison',
            'pathReports',
            'finalFindings'
        ));
    }

    private function classifyBmi(float $bmi): string
    {
        return match (true) {
            $bmi > 0 && $bmi < 18.5 => 'Underweight',
            $bmi >= 18.5 && $bmi < 25 => 'Normal',
            $bmi >= 25 && $bmi < 30 => 'Overweight',
            $bmi >= 30 => 'Obesity',
            default => '',
        };
    }

    /**
     * @param \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, FcOtActivity>> $byActivity
     */
    private function firstActivityRow($byActivity, string $menuid): ?FcOtActivity
    {
        $rows = $byActivity->get($menuid);
        if (! $rows || $rows->isEmpty()) {
            return null;
        }

        return $rows->sortBy('id')->first();
    }

    /**
     * @param \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, FcOtActivity>> $byActivity
     */
    private function latestActivityRow($byActivity, string $menuid): ?FcOtActivity
    {
        $rows = $byActivity->get($menuid);
        if (! $rows || $rows->isEmpty()) {
            return null;
        }

        return $rows->sortByDesc('id')->first();
    }

    /**
     * @param  array<int, array{value: mixed, when: string, by: string, id: int}>  $readings
     * @return array{first: float, last: float, delta: float}|null
     */
    private function vitalReadingsNumericCompare(array $readings): ?array
    {
        $nums = [];
        foreach ($readings as $r) {
            $v = trim((string) ($r['value'] ?? ''));
            if ($v === '') {
                continue;
            }
            if (is_numeric($v)) {
                $nums[] = (float) $v;
            }
        }
        if (count($nums) < 2) {
            return null;
        }
        $first = $nums[0];
        $last = $nums[count($nums) - 1];

        return [
            'first' => $first,
            'last' => $last,
            'delta' => round($last - $first, 2),
        ];
    }

    /**
     * Pre-history from registration is keyed by userid + course_master_pk.
     * Prefer exact pk match; if missing or pk is null, fall back to the latest row for this user.
     */
    private function resolvePreHistoryForOt(int $userId, ?int $courseMasterPk): ?FcPreHistory
    {
        if ($courseMasterPk) {
            $exact = FcPreHistory::forUser($userId)->where('course_master_pk', $courseMasterPk)->first();
            if ($exact) {
                return $exact;
            }
        }

        return FcPreHistory::forUser($userId)->orderByDesc('updated_at')->orderByDesc('id')->first();
    }

    public function upload(Request $request): JsonResponse
    {
        $this->authorizeMedical();

        $request->validate([
            'otcode' => 'required|string',
            'course_master_pk' => 'required|integer|exists:course_master,pk',
            'file1' => 'nullable|file|mimes:pdf|max:10240',
            'textfindings' => 'nullable|string|max:5000',
        ]);

        $ot = FcOtDetail::where('otcode', $request->otcode)->firstOrFail();
        $courseName = (string) (DB::table('course_master')
            ->where('pk', $request->integer('course_master_pk'))
            ->value('course_name') ?? '');
        $pathreport = null;
        $findings = $request->input('textfindings', '');

        if ($request->hasFile('file1') && $request->file('file1')->isValid()) {
            $file = $request->file('file1');
            // Store under a server-generated name (never the client filename) to
            // avoid double-extension / overwrite issues (CWE-434). Extension is
            // derived from the validated file content, not the client-supplied name.
            $path = $file->storeAs('fc/path_report', $ot->user_id.'_'.uniqid('', true).'.'.$file->extension(), 'public');
            $pathreport = 'storage/' . $path;
        }

        if (! $pathreport && ! $findings) {
            return response()->json(['status' => 'ok']);
        }

        if (! $pathreport && $findings) {
            $existing = FcPathReport::where(fc_user_col('fc_path_report'), $ot->user_id)->where('course', $courseName)->first();
            FcPathReport::updateOrCreate(
                [fc_user_col('fc_path_report') => $ot->user_id, 'course' => $courseName],
                ['path_report' => $existing->path_report ?? null, 'status' => 1, 'submit_dt' => now()]
            );
            FcFinalFinding::create([
                fc_user_col('fc_final_findings') => $ot->user_id,
                'findings' => ucwords($findings),
                'course' => $courseName,
                'submited_by' => Auth::user()->user_name ?? '' ?? '',
                'status' => 1,
                'submit_dt' => now(),
            ]);
        } elseif ($pathreport && ! $findings) {
            FcPathReport::create([
                fc_user_col('fc_path_report') => $ot->user_id,
                'path_report' => $pathreport,
                'course' => $courseName,
                'status' => 1,
                'submit_dt' => now(),
            ]);
        } else {
            FcPathReport::create([
                fc_user_col('fc_path_report') => $ot->user_id,
                'path_report' => $pathreport,
                'course' => $courseName,
                'status' => 1,
                'submit_dt' => now(),
            ]);
            FcFinalFinding::create([
                fc_user_col('fc_final_findings') => $ot->user_id,
                'findings' => ucwords($findings),
                'course' => $courseName,
                'submited_by' => Auth::user()->user_name ?? '' ?? '',
                'status' => 1,
                'submit_dt' => now(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
