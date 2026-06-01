<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Services\FC\FcRegistrationFlowService;
use App\Services\FC\RegistrationService;
use App\DataTables\FC\FcFormOverviewDataTable;
use App\Models\FC\FcForm;
use App\Models\FC\{
    StudentMasterFirst, StudentMasterSecond, StudentMaster,
    SessionMaster, ServiceMaster, StateMaster, CategoryMaster,
    NewRegistrationBankDetailsMaster, FcJoiningRelatedDocumentsDetailsMaster,
    StudentConfirmMaster, StudentMasterQualificationDetails,
    StudentMasterLanguageKnown, StudentMasterEmploymentDetails,
    FcJoiningRelatedDocumentsMaster
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\Process\Process;

class ReportController extends Controller
{
    /**
     * Limit aggregated report queries to trainees registered on a specific form.
     */
    private function constrainToForm(Request $request, $query, string $studentMasterAlias = 'sm'): void
    {
        if (! $request->filled('form_id') || ! Schema::hasColumn('student_masters', 'form_id')) {
            return;
        }

        $query->where("{$studentMasterAlias}.form_id", (int) $request->input('form_id'));
    }

    private function scopedFormFromRequest(Request $request): ?FcForm
    {
        if (! $request->filled('form_id')) {
            return null;
        }

        return FcForm::find((int) $request->input('form_id'));
    }

    /**
     * Active FC forms for aggregated report filters (scoped via student_masters.form_id).
     */
    private function reportFormsForFilter()
    {
        return FcForm::query()
            ->with('courseMaster:pk,course_name')
            ->where('is_active', true)
            ->orderBy('form_name')
            ->get(['id', 'form_name', 'form_slug', 'course_master_pk']);
    }

    // ── 1. Registration Overview (all students, progress status) ─────
    public function overview(Request $request)
    {
        $sessions = SessionMaster::orderByDesc('id')->get();
        $services = DB::table('service_master')
            ->orderBy('service_name')
            ->select('pk', 'service_name', 'service_short_name')
            ->get();
        $states   = StateMaster::orderBy('state_name')->get();

        $smUserCol = fc_user_col('student_masters');
        $scUserCol = fc_user_col('student_confirm_masters');

        $query = StudentMaster::with([
                'session',
            ]);

        fc_report_apply_tracker_user_resolution($query, 'student_masters', 'student_masters');
        fc_report_join_student_master_firsts($query, 'student_masters', 'student_masters');

        $query->leftJoin('service_master as svc', 's1.service_id', '=', 'svc.pk')
            ->leftJoin('state_masters as st', 's1.allotted_state_id', '=', 'st.id')
            ->leftJoin('student_confirm_masters as sc', function ($join) use ($smUserCol, $scUserCol) {
                if ($smUserCol === 'user_id') {
                    $join->on('sc.'.$scUserCol, '=', 'student_masters.user_id')
                        ->orOn('sc.'.$scUserCol, '=', 'uc.pk');
                    if (Schema::hasTable('fc_registration_master')) {
                        $join->orOn('sc.'.$scUserCol, '=', 'uc_frm.pk');
                    }
                } else {
                    $join->on("student_masters.{$smUserCol}", '=', "sc.{$scUserCol}");
                }
            });

        if ($smUserCol === 'user_id') {
            $query->addSelect([
                DB::raw(fc_report_route_user_id_sql('student_masters', 'student_masters').' as route_user_id'),
                DB::raw(fc_report_login_username_sql('student_masters', 'student_masters').' as login_username'),
            ]);
        } else {
            $query->addSelect([
                DB::raw('uc.pk as route_user_id'),
                DB::raw("student_masters.{$smUserCol} as login_username"),
            ]);
        }

        $query->addSelect(
                'student_masters.status',
                'student_masters.step1_done',
                'student_masters.step2_done',
                'student_masters.step3_done',
                'student_masters.bank_done',
                'student_masters.travel_done',
                'student_masters.docs_done',
                'student_masters.session_id',
                's1.full_name',
                's1.gender',
                's1.mobile_no',
                's1.email',
                's1.date_of_birth',
                'svc.service_name',
                DB::raw('COALESCE(svc.service_short_name, svc.service_name) as service_code'),
                's1.cadre',
                'st.state_name as allotted_state',
                'sc.declaration_accepted',
                'sc.confirmed_at',
                DB::raw('(CASE WHEN student_masters.step1_done=1 THEN 1 ELSE 0 END +
                          CASE WHEN student_masters.step2_done=1 THEN 1 ELSE 0 END +
                          CASE WHEN student_masters.step3_done=1 THEN 1 ELSE 0 END +
                          CASE WHEN student_masters.bank_done=1 THEN 1 ELSE 0 END +
                          CASE WHEN student_masters.travel_done=1 THEN 1 ELSE 0 END +
                          CASE WHEN student_masters.docs_done=1 THEN 1 ELSE 0 END) as steps_done')
            );

        // Filters
        if ($request->filled('session_id')) {
            $query->where('student_masters.session_id', $request->session_id);
        }
        if ($request->filled('status')) {
            $query->where('student_masters.status', $request->status);
        }
        if ($request->filled('service_id')) {
            $query->where('s1.service_id', $request->service_id);
        }
        if ($request->filled('state_id')) {
            $query->where('s1.allotted_state_id', $request->state_id);
        }
        if ($request->filled('gender')) {
            $query->where('s1.gender', $request->gender);
        }
        if ($request->filled('search')) {
            $s = '%'.$request->search.'%';
            $query->where(function ($q) use ($s, $smUserCol) {
                $q->where('s1.full_name', 'like', $s)
                    ->orWhere('s1.mobile_no', 'like', $s)
                    ->orWhere('s1.email', 'like', $s)
                    ->orWhere('uc.user_name', 'like', $s);
                if ($smUserCol !== 'user_id') {
                    $q->orWhere("student_masters.{$smUserCol}", 'like', $s);
                } else {
                    $q->orWhere('student_masters.user_id', 'like', $s)
                        ->orWhere('frm.user_id', 'like', $s)
                        ->orWhere('uc_frm.user_name', 'like', $s);
                }
            });
        }

        $students = $query->orderBy('s1.full_name')->paginate(50)->withQueryString();

        // Summary counts
        $summary = [
            'total'      => StudentMaster::count(),
            'submitted'  => StudentMaster::where('status','SUBMITTED')->count(),
            'confirmed'  => StudentConfirmMaster::where('declaration_accepted',1)->count(),
            'incomplete' => StudentMaster::where('status','INCOMPLETE')->count(),
            'step1_done' => StudentMaster::where('step1_done',1)->count(),
            'step2_done' => StudentMaster::where('step2_done',1)->count(),
            'step3_done' => StudentMaster::where('step3_done',1)->count(),
            'bank_done'   => StudentMaster::where('bank_done', 1)->count(),
            'travel_done' => StudentMaster::where('travel_done', 1)->count(),
            'docs_done'   => StudentMaster::where('docs_done', 1)->count(),
        ];

        return view('fc.report.overview', compact(
            'students','summary','sessions','services','states'
        ));
    }

    // ── 1b. Form-specific dynamic overview (any form, any step count) ──
    public function formOverview(Request $request, FcForm $form)
    {
        $dataTable    = new FcFormOverviewDataTable($form);
        $steps        = $dataTable->steps;
        $totalSteps   = $dataTable->totalSteps;
        $trackerTable = $form->trackerStorageTable();
        $userKey      = fc_user_col($trackerTable);
        $t            = $trackerTable;

        // Summary counts — always computed from DB (not paginated)
        $trackerBase = fn () => DB::table($trackerTable)
            ->when(Schema::hasColumn($trackerTable, 'form_id'), fn ($q) => $q->where('form_id', $form->id));

        $stepsDoneExpr = $totalSteps > 0
            ? $steps->map(fn ($s) => "CASE WHEN `{$t}`.`{$s->tracker_column}`=1 THEN 1 ELSE 0 END")
                    ->implode(' + ')
            : '0';

        $summary = [
            'total'      => $trackerBase()->count(),
            'submitted'  => $trackerBase()->where('status', 'SUBMITTED')->count(),
            'complete'   => $totalSteps > 0
                ? $trackerBase()->where('status', '!=', 'SUBMITTED')
                    ->whereRaw("({$stepsDoneExpr}) >= {$totalSteps}")
                    ->count()
                : 0,
            'incomplete' => $totalSteps > 0
                ? $trackerBase()->where('status', '!=', 'SUBMITTED')
                    ->whereRaw("({$stepsDoneExpr}) < {$totalSteps}")
                    ->count()
                : $trackerBase()->where('status', 'INCOMPLETE')->count(),
        ];
        foreach ($steps as $step) {
            $summary[$step->tracker_column] = $trackerBase()
                ->where($step->tracker_column, 1)->count();
        }

        $services = DB::table('service_master')
            ->orderBy('service_name')
            ->select('pk', 'service_name', 'service_short_name')
            ->get();

        // Build paginated student query with filters
        $u = $userKey;

        $query = DB::table($t);

        fc_report_apply_tracker_user_resolution($query, $t, $t);
        fc_report_join_student_master_firsts($query, $t, $t);

        $query->leftJoin('service_master as svc', 's1.service_id', '=', 'svc.pk')
            ->leftJoin('state_masters as st', 's1.allotted_state_id', '=', 'st.id');

        if ($u === 'user_id') {
            $query->addSelect([
                DB::raw(fc_report_route_user_id_sql($t, $t).' as route_user_id'),
                DB::raw(fc_report_login_username_sql($t, $t).' as login_username'),
            ]);
        } else {
            $query->addSelect([
                DB::raw('uc.pk as route_user_id'),
                DB::raw("`{$t}`.`{$u}` as login_username"),
            ]);
        }

        $query->addSelect([
                "{$t}.{$u}",
                "{$t}.status",
                's1.full_name',
                's1.mobile_no',
                DB::raw('COALESCE(svc.service_short_name, svc.service_name) as service_code'),
                's1.cadre',
                'st.state_name as allotted_state',
                DB::raw("({$stepsDoneExpr}) as steps_done"),
            ]);

        foreach ($steps as $step) {
            $query->addSelect("{$t}.{$step->tracker_column}");
        }

        // Scope to this form when the tracker table is shared
        if (Schema::hasColumn($t, 'form_id')) {
            $query->where("{$t}.form_id", $form->id);
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->where("{$t}.status", $request->input('status'));
        }
        if ($request->filled('service_id')) {
            $query->where('s1.service_id', $request->input('service_id'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($t, $u, $search) {
                $q->where('s1.full_name', 'like', "%{$search}%")
                    ->orWhere('s1.mobile_no', 'like', "%{$search}%")
                    ->orWhere('uc.user_name', 'like', "%{$search}%");
                if ($u === 'user_id') {
                    $q->orWhere("{$t}.user_id", 'like', "%{$search}%")
                        ->orWhere('frm.user_id', 'like', "%{$search}%")
                        ->orWhere('uc_frm.user_name', 'like', "%{$search}%");
                } else {
                    $q->orWhere("{$t}.{$u}", 'like', "%{$search}%");
                }
            });
        }

        $students = $query->orderBy('s1.full_name')->paginate(50)->withQueryString();

        return view(
            'fc.report.form-overview',
            compact('form', 'steps', 'totalSteps', 'summary', 'services', 'students', 'userKey')
        );
    }

    // ── 1c. Form-specific CSV export ──────────────────────────────────
    public function formExportCsv(Request $request, FcForm $form)
    {
        $dataTable    = new FcFormOverviewDataTable($form);
        $steps        = $dataTable->steps;
        $totalSteps   = $dataTable->totalSteps;
        $trackerTable = $form->trackerStorageTable();
        $userKey      = fc_user_col($trackerTable);
        $t            = $trackerTable;
        $u            = $userKey;

        $stepsDoneExpr = $totalSteps > 0
            ? $steps->map(fn ($s) => "CASE WHEN `{$t}`.`{$s->tracker_column}`=1 THEN 1 ELSE 0 END")
                    ->implode(' + ')
            : '0';

        $query = DB::table($t);

        fc_report_apply_tracker_user_resolution($query, $t, $t);
        fc_report_join_student_master_firsts($query, $t, $t);

        $query->leftJoin('service_master as svc', 's1.service_id', '=', 'svc.pk')
            ->leftJoin('state_masters as st', 's1.allotted_state_id', '=', 'st.id')
            ->select([
                DB::raw($u === 'user_id'
                    ? fc_report_login_username_sql($t, $t).' as login_username'
                    : "`{$t}`.`{$u}` as login_username"),
                DB::raw($u === 'user_id'
                    ? fc_report_route_user_id_sql($t, $t).' as route_user_id'
                    : "{$t}.{$u} as route_user_id"),
                "{$t}.status",
                's1.full_name',
                's1.mobile_no',
                DB::raw('COALESCE(svc.service_short_name, svc.service_name) as service_code'),
                's1.cadre',
                'st.state_name as allotted_state',
                DB::raw("({$stepsDoneExpr}) as steps_done"),
            ]);

        foreach ($steps as $step) {
            $query->addSelect("{$t}.{$step->tracker_column}");
        }

        if (Schema::hasColumn($t, 'form_id')) {
            $query->where("{$t}.form_id", $form->id);
        }

        if ($request->filled('status')) {
            $query->where("{$t}.status", $request->input('status'));
        }
        if ($request->filled('service_id')) {
            $query->where('s1.service_id', $request->input('service_id'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($t, $u, $search) {
                $q->where('s1.full_name', 'like', "%{$search}%")
                    ->orWhere('s1.mobile_no', 'like', "%{$search}%")
                    ->orWhere('uc.user_name', 'like', "%{$search}%");
                if ($u === 'user_id') {
                    $q->orWhere("{$t}.user_id", 'like', "%{$search}%")
                        ->orWhere('frm.user_id', 'like', "%{$search}%")
                        ->orWhere('uc_frm.user_name', 'like', "%{$search}%");
                } else {
                    $q->orWhere("{$t}.{$u}", 'like', "%{$search}%");
                }
            });
        }

        $rows = $query->orderBy('s1.full_name')->get();

        $filename = Str::slug($form->form_name) . '-' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows, $steps, $userKey, $totalSteps) {
            $handle = fopen('php://output', 'w');

            // Header row
            $cols = ['Username', 'Full Name', 'Mobile', 'Service', 'Cadre', 'State'];
            foreach ($steps as $step) {
                $cols[] = $step->step_name;
            }
            $cols[] = 'Steps Done';
            $cols[] = 'Status';
            fputcsv($handle, $cols);

            foreach ($rows as $row) {
                $line = [
                    $row->login_username ?? $row->{$userKey} ?? $row->route_user_id ?? '',
                    $row->full_name ?? '',
                    $row->mobile_no ?? '',
                    $row->service_code ?? '',
                    $row->cadre ?? '',
                    $row->allotted_state ?? '',
                ];
                foreach ($steps as $step) {
                    $line[] = ($row->{$step->tracker_column} ?? 0) ? 'Yes' : 'No';
                }
                $line[] = ($row->steps_done ?? 0) . '/' . $totalSteps;
                $line[] = $row->status ?? '';
                fputcsv($handle, $line);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── 2. Student Detail View ────────────────────────────────────────
    public function studentDetail(int $userId)
    {
        $step1 = StudentMasterFirst::forUser($userId)->with(['session', 'service', 'allottedState'])->first();
        abort_unless($step1, 404, "Student '{$userId}' not found.");

        $master = StudentMaster::forUser($userId)->first();
        $confirmation = StudentConfirmMaster::forUser($userId)->first();

        $registrationService = app(RegistrationService::class);
        $registrationFlow = app(FcRegistrationFlowService::class);
        $reportForm = FcForm::resolveForUserId($userId);
        $sections = $registrationService->buildReportSectionsFromFormDefinition($userId, $reportForm);
        $displayName = $registrationService->resolveStudentDisplayName($step1);
        $photoPath = $registrationService->resolveStudentPhotoPath($step1, $reportForm, $userId);
        $photoUrl = view_file_link($photoPath);

        $formProgress = [];
        $progressDone = 0;
        $progressTotal = 0;
        if ($reportForm) {
            $formSteps = $reportForm->activeSteps()->get();
            $stepStatus = $registrationFlow->buildStepCompletionByStepId($reportForm, $formSteps, $userId);
            foreach ($formSteps as $step) {
                $done = (bool) ($stepStatus[$step->id] ?? false);
                $formProgress[] = ['label' => $step->step_name, 'done' => $done];
                $progressTotal++;
                if ($done) {
                    $progressDone++;
                }
                if (($step->tracker_column ?? '') === 'bank_done') {
                    $travelDone = $registrationFlow->isTravelComplete($userId, $reportForm);
                    $formProgress[] = ['label' => 'Travel Plan', 'done' => $travelDone];
                    $progressTotal++;
                    if ($travelDone) {
                        $progressDone++;
                    }
                }
            }
        } else {
            $legacySteps = [
                'Step 1' => $master?->step1_done,
                'Step 2' => $master?->step2_done,
                'Step 3' => $master?->step3_done,
                'Bank' => $master?->bank_done,
                'Docs' => $master?->docs_done,
            ];
            foreach ($legacySteps as $label => $done) {
                $formProgress[] = ['label' => $label, 'done' => (bool) $done];
                $progressTotal++;
                if ($done) {
                    $progressDone++;
                }
            }
        }

        $registrationComplete = $progressTotal > 0 && $progressDone >= $progressTotal;
        $headerMeta = $registrationService->resolveStudentHeaderMeta($step1);

        if ($reportForm && ! $registrationFlow->usesLegacyDocumentChecklist($reportForm)) {
            $documents = $registrationService->dynamicFormDocumentsForDisplay($userId, $reportForm);
            $documentSource = 'dynamic';
        } else {
            $documents = $registrationService->joiningDocumentChecklistForDisplay($userId);
            $documentSource = 'legacy';
        }

        return view('fc.report.student-detail', compact(
            'userId',
            'step1',
            'master',
            'confirmation',
            'reportForm',
            'sections',
            'displayName',
            'photoUrl',
            'formProgress',
            'progressDone',
            'progressTotal',
            'registrationComplete',
            'headerMeta',
            'documents',
            'documentSource'
        ));
    }

    public function updateStudentDocumentVerification(Request $request, int $userId, int $documentMasterId)
    {
        $request->validate([
            'is_verified' => 'nullable|boolean',
            'remarks' => 'nullable|string|max:500',
        ]);

        $docMaster = FcJoiningRelatedDocumentsMaster::where('id', $documentMasterId)
            ->where('is_active', 1)
            ->firstOrFail();

        $doc = FcJoiningRelatedDocumentsDetailsMaster::forUser($userId)
            ->where('document_master_id', $documentMasterId)
            ->first();

        if (! $doc || ! $doc->is_uploaded) {
            return back()->with('error', "\"{$docMaster->document_name}\" is not uploaded yet, so it cannot be verified.");
        }

        $isVerified = (bool) $request->boolean('is_verified');
        $remarks = trim((string) $request->input('remarks', ''));

        $doc->update([
            'is_verified' => $isVerified,
            'verified_by' => $isVerified ? auth()->id() : null,
            'verified_at' => $isVerified ? now() : null,
            'remarks' => $remarks !== '' ? $remarks : null,
        ]);

        return back()->with('success', "\"{$docMaster->document_name}\" verification updated successfully.");
    }

    public function updateDynamicFormDocumentVerification(Request $request, int $userId, int $formFieldId)
    {
        $request->validate([
            'is_verified' => 'nullable|boolean',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $documentName = app(RegistrationService::class)->saveDynamicFormDocumentVerification(
                $userId,
                $formFieldId,
                (bool) $request->boolean('is_verified'),
                trim((string) $request->input('remarks', '')) ?: null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "\"{$documentName}\" verification updated successfully.");
    }

    /**
     * Bilingual “descriptive profile” PDF — prefers headless Chrome when available, else Dompdf.
     * Noto is embedded via data: URLs so fonts actually load (Dompdf ignores many file:// @font-face paths).
     */
    public function studentDetailPdf(int $userId)
    {
        $step1 = StudentMasterFirst::forUser($userId)
            ->with(['session', 'service', 'allottedState'])
            ->first();
        abort_unless($step1, 404, "Student '{$userId}' not found.");

        $step2 = StudentMasterSecond::forUser($userId)
            ->with(['category', 'religion', 'permState', 'presState', 'fatherProfession'])
            ->first();
        $master = StudentMaster::where(fc_user_col('student_masters'), fc_user_val('student_masters', $userId))->first();
        $bank = NewRegistrationBankDetailsMaster::where(fc_user_col('new_registration_bank_details_masters'), fc_user_val('new_registration_bank_details_masters', $userId))->first();
        $qualifications = DB::table('student_master_qualification_details')
            ->where(fc_user_col('student_master_qualification_details'), fc_user_val('student_master_qualification_details', $userId))
            ->get()
            ->map(function ($row) {
                $row->qualification_name = $this->fcResolveLookupLabel(
                    ['qualification_masters', 'qualification_master'],
                    ['id', 'pk'],
                    'qualification_name',
                    $row->qualification_id ?? null
                );
                $row->board_name = $this->fcResolveLookupLabel(
                    ['board_name_masters', 'board_name_master'],
                    ['id', 'pk'],
                    'board_name',
                    $row->board_id ?? null
                );
                return $row;
            });
        $employments = DB::table('student_master_employment_details')
            ->leftJoin('job_type_masters', 'student_master_employment_details.job_type_id', '=', 'job_type_masters.id')
            ->where('student_master_employment_details.' . fc_user_col('student_master_employment_details'), fc_user_val('student_master_employment_details', $userId))
            ->select('student_master_employment_details.*', 'job_type_masters.job_type_name')
            ->get();
        $languages = DB::table('student_master_language_knowns')
            ->where(fc_user_col('student_master_language_knowns'), fc_user_val('student_master_language_knowns', $userId))
            ->get()
            ->map(function ($row) {
                $row->language_name = $this->fcResolveLookupLabel(
                    ['language_master', 'language_masters'],
                    ['id', 'pk'],
                    'language_name',
                    $row->language_id ?? null
                );
                return $row;
            });
        $registrationService = app(RegistrationService::class);
        $pdfForm = FcForm::resolveForUserId($userId);
        $sections = $this->fcStudentPdfSanitizeSections(
            $registrationService->buildPdfSectionsFromFormDefinition($userId, $pdfForm)
        );
        $printedAt = $this->fcPdfSanitizeText(now()->format('d/m/Y H:i'));
        $photoPath = $registrationService->resolveStudentPhotoPath($step1, $pdfForm, $userId);
        $photoUrl = view_file_link($photoPath);
        $photoDataUri = fc_photo_data_uri($photoPath);

        $pdfFontFaceCss = $this->fcRegistrationEmbeddedFontFaceCss();
        $pdfFontFamilyCss = $pdfFontFaceCss !== ''
            ? "'FcRegPdf', 'DejaVu Sans', sans-serif"
            : "'DejaVu Sans', sans-serif";

        $viewData = [
            'sections' => $sections,
            'userId' => $userId,
            'step1' => $step1,
            'pdfFullName' => $this->fcPdfSanitizeText($registrationService->resolveStudentDisplayName($step1)),
            'pdfFormName' => $this->fcPdfSanitizeText((string) ($pdfForm?->form_name ?? '')),
            'printedAt' => $printedAt,
            'photoDataUri' => $photoDataUri,
            'photoUrl' => $photoUrl,
            'pdfFontFaceCss' => $pdfFontFaceCss,
            'pdfFontFamilyCss' => $pdfFontFamilyCss,
        ];

        $html = view('fc.report.student-detail-pdf', $viewData)->render();

        $filename = 'FC_Registration_'.$userId.'_'.now()->format('Ymd_His').'.pdf';
        $engine = strtolower((string) env('FC_REGISTRATION_PDF_ENGINE', 'auto'));

        if ($engine !== 'dompdf' && ($engine === 'chrome' || $engine === 'auto')) {
            $chromePdf = $this->fcRegistrationPdfRenderChrome($html);
            if ($chromePdf !== null) {
                return response($chromePdf, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$filename.'"',
                ]);
            }
            Log::info('FC registration PDF: using Dompdf fallback (Chrome unavailable or failed)', [
                'engine' => $engine,
                'chrome_bin' => $this->fcRegistrationChromeBinary(),
            ]);
        }

        $this->fcEnsureDompdfFontCacheDir();

        return Pdf::loadHTML($html)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', false)
            ->setPaper('a4', 'portrait')
            ->addInfo(['Title' => 'FC Registration - '.$userId])
            ->stream($filename);
    }

    /**
     * Dompdf writes .ufm/.ttf font metrics under storage/fonts when rendering @font-face (FcRegPdf).
     * Production often lacks this directory (storage/ is not in git); without it fopen(...ufm) fails.
     */
    private function fcEnsureDompdfFontCacheDir(): void
    {
        $fontDir = storage_path('fonts');

        if (! is_dir($fontDir)) {
            File::makeDirectory($fontDir, 0775, true);
        }

        if (! is_dir($fontDir) || ! is_writable($fontDir)) {
            Log::error('FC registration PDF: storage/fonts missing or not writable for Dompdf', [
                'path' => $fontDir,
                'exists' => is_dir($fontDir),
                'writable' => is_writable($fontDir),
            ]);

            abort(500, 'PDF cannot be generated: the font cache directory (storage/fonts) is missing or not writable by the web server. On the server, run: mkdir -p storage/fonts && chown -R www-data:www-data storage/fonts && chmod -R 775 storage/fonts. Alternatively install Google Chrome/Chromium for headless PDF rendering.');
        }
    }

    /**
     * Embed Noto Sans Devanagari as data: URLs so Dompdf/Chrome both load glyphs (file:// @font-face often fails in Dompdf).
     */
    private function fcRegistrationEmbeddedFontFaceCss(): string
    {
        $dir = resource_path('fonts/mpdf');
        $regular = $dir.'/NotoSansDevanagari-Regular.ttf';
        if (! is_file($regular) || ! is_readable($regular)) {
            return '';
        }
        $rData = base64_encode((string) file_get_contents($regular));
        $css = '@font-face{font-family:\'FcRegPdf\';font-style:normal;font-weight:400;src:url(data:font/ttf;charset=utf-8;base64,'.$rData.') format(\'truetype\');}';
        $bold = $dir.'/NotoSansDevanagari-Bold.ttf';
        if (is_file($bold) && is_readable($bold)) {
            $bData = base64_encode((string) file_get_contents($bold));
            $css .= '@font-face{font-family:\'FcRegPdf\';font-style:normal;font-weight:700;src:url(data:font/ttf;charset=utf-8;base64,'.$bData.') format(\'truetype\');}';
        }

        return $css;
    }

    private function fcRegistrationChromeBinary(): ?string
    {
        $fromEnv = env('FC_REGISTRATION_CHROME_BIN');
        if (is_string($fromEnv) && $fromEnv !== '' && @is_executable($fromEnv)) {
            return $fromEnv;
        }
        foreach ([
            '/usr/bin/google-chrome-stable',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/snap/bin/chromium',
        ] as $path) {
            if (@is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Headless Chrome print-to-PDF (best Hindi + Latin shaping). Returns null on failure.
     */
    private function fcRegistrationPdfRenderChrome(string $html): ?string
    {
        $bin = $this->fcRegistrationChromeBinary();
        if ($bin === null) {
            return null;
        }

        $work = storage_path('app/temp/fc-pdf');
        if (! is_dir($work)) {
            mkdir($work, 0755, true);
        }

        $id = uniqid('fcreg_', true);
        $htmlPath = $work.'/'.$id.'.html';
        $pdfPath = $work.'/'.$id.'.pdf';

        if (file_put_contents($htmlPath, $html) === false) {
            return null;
        }

        $fileUri = 'file://'.str_replace('\\', '/', $htmlPath);

        $cmd = [
            $bin,
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--no-pdf-header-footer',
            '--virtual-time-budget=25000',
            '--print-to-pdf='.$pdfPath,
            $fileUri,
        ];

        try {
            $process = new Process($cmd);
            $process->setTimeout(120);
            $process->run();
            if (! $process->isSuccessful()) {
                Log::warning('FC registration PDF: Chrome headless failed', [
                    'exit' => $process->getExitCode(),
                    'err' => $process->getErrorOutput(),
                ]);
                @unlink($htmlPath);
                @unlink($pdfPath);

                return null;
            }
        } catch (\Throwable $e) {
            Log::warning('FC registration PDF: Chrome exception', ['message' => $e->getMessage()]);
            @unlink($htmlPath);
            @unlink($pdfPath);

            return null;
        }

        @unlink($htmlPath);

        if (! is_file($pdfPath)) {
            @unlink($pdfPath);

            return null;
        }

        $binary = file_get_contents($pdfPath);
        @unlink($pdfPath);

        return $binary === false ? null : $binary;
    }

    /**
     * PDF-safe text: strip problematic invisibles (keep U+200C/U+200D for Hindi shaping),
     * fold “smart” punctuation to ASCII (PDF engines often show .notdef boxes for these).
     */
    private function fcPdfSanitizeText(string $s): string
    {
        if ($s === '') {
            return '';
        }
        if (function_exists('mb_check_encoding') && ! mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8') ?: $s;
        }

        $s = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $s);

        // BOM, ZWSP, LRM/RLM, bidi controls, word joiner, isolate chars — NOT U+200C/U+200D (needed for Devanagari).
        $s = preg_replace('/[\x{FEFF}\x{200B}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2060}\x{2066}-\x{2069}]/u', '', $s) ?? $s;

        // Unicode spaces → ASCII space
        $s = preg_replace('/[\x{00A0}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]/u', ' ', $s) ?? $s;

        $s = str_replace("\xC2\xAD", '', $s); // soft hyphen

        // Dash / minus family → ASCII hyphen (U+2014 em dash is a common tofu source in PDF engines)
        $s = preg_replace('/[\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}\x{2015}\x{2212}\x{FE58}\x{FE63}\x{FF0D}]/u', '-', $s) ?? $s;

        // Smart quotes → ASCII
        $s = str_replace(
            ["\u{2018}", "\u{2019}", "\u{201A}", "\u{201B}", "\u{2032}", "\u{2035}"],
            "'",
            $s
        );
        $s = str_replace(
            ["\u{201C}", "\u{201D}", "\u{201E}", "\u{201F}", "\u{00AB}", "\u{00BB}", "\u{2039}", "\u{203A}"],
            '"',
            $s
        );

        $s = str_replace("\u{2026}", '...', $s);

        // Bullets / operators that often lack glyphs in embedded fonts
        $s = preg_replace('/[\x{2022}\x{2023}\x{25AA}\x{25CF}\x{2219}\x{00B7}\x{30FB}]/u', '*', $s) ?? $s;
        $s = preg_replace('/[\x{2713}\x{2714}\x{2611}\x{2610}\x{2717}\x{2718}]/u', 'Y', $s) ?? $s;

        // Private-use / variation selectors (Word etc. can inject PUA that renders as boxes)
        $s = preg_replace('/[\x{E000}-\x{F8FF}\x{FE00}-\x{FE0F}]/u', '', $s) ?? $s;

        // C0 controls (except we already removed tab/newline)
        $s = preg_replace('/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}]/u', '', $s) ?? $s;

        $s = preg_replace('/ +/u', ' ', $s) ?? $s;

        if (class_exists(\Normalizer::class)) {
            $n = \Normalizer::normalize(trim($s), \Normalizer::FORM_C);

            return ($n !== false && $n !== null) ? $n : trim($s);
        }

        return trim($s);
    }

    /**
     * @param  list<array<string,mixed>>  $sections
     * @return list<array<string,mixed>>
     */
    private function fcStudentPdfSanitizeSections(array $sections): array
    {
        foreach ($sections as &$sec) {
            if (($sec['type'] ?? '') === 'fields' && ! empty($sec['rows'])) {
                foreach ($sec['rows'] as &$row) {
                    $row['en'] = $this->fcPdfSanitizeText((string) ($row['en'] ?? ''));
                    $row['hi'] = $this->fcPdfSanitizeText((string) ($row['hi'] ?? ''));
                    $row['value'] = $this->fcPdfSanitizeScalar($row['value'] ?? null);
                }
                unset($row);
            } elseif (($sec['type'] ?? '') === 'table' && ! empty($sec['body'])) {
                foreach ($sec['body'] as &$tr) {
                    foreach ($tr as $i => $cell) {
                        $tr[$i] = $this->fcPdfSanitizeScalar($cell);
                    }
                }
                unset($tr);
            }
            if (! empty($sec['columns']) && is_array($sec['columns'])) {
                foreach ($sec['columns'] as $i => $col) {
                    $sec['columns'][$i] = $this->fcPdfSanitizeText((string) $col);
                }
            }
            if (! empty($sec['head_hi']) && is_array($sec['head_hi'])) {
                foreach ($sec['head_hi'] as $i => $h) {
                    $sec['head_hi'][$i] = $this->fcPdfSanitizeText((string) $h);
                }
            }
            $sec['title_en'] = $this->fcPdfSanitizeText((string) ($sec['title_en'] ?? ''));
            $sec['title_hi'] = $this->fcPdfSanitizeText((string) ($sec['title_hi'] ?? ''));
        }
        unset($sec);

        return $sections;
    }

    private function fcPdfSanitizeScalar(mixed $v): mixed
    {
        if ($v === null || is_bool($v) || is_int($v) || is_float($v)) {
            return $v;
        }

        return $this->fcPdfSanitizeText((string) $v);
    }

    private function fcResolveLookupLabel(array $tables, array $valueColumns, string $labelColumn, mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $labelColumn)) {
                continue;
            }
            foreach ($valueColumns as $valueColumn) {
                if (! Schema::hasColumn($table, $valueColumn)) {
                    continue;
                }
                $label = DB::table($table)->where($valueColumn, $raw)->value($labelColumn);
                if ($label !== null && $label !== '') {
                    return (string) $label;
                }
            }
        }

        return null;
    }

    /**
     * Embed trainee photo (downscaled for PDF) when file exists on public disk.
     */
    private function fcRegistrationPhotoDataUri(?string $path): ?string
    {
        return fc_photo_data_uri($path);
    }

    // ── 3. Service-wise Report ────────────────────────────────────────
    public function byService(Request $request)
    {
        $forms = $this->reportFormsForFilter();
        $services = DB::table('service_master')->orderBy('service_name')->select('pk', 'service_name', 'service_short_name')->get();

        $base = DB::table('student_master_firsts as s1')
            ->join('service_master as svc','s1.service_id','=','svc.pk')
            ->leftJoin('student_masters as sm','s1.user_id','=','sm.user_id')
            ->leftJoin('student_master_seconds as s2','s1.user_id','=','s2.user_id')
            ->leftJoin('category_masters as cat','s2.category_id','=','cat.id')
            ->leftJoin('state_masters as st','s1.allotted_state_id','=','st.id')
            ->when(!empty((array) $request->input('service_ids', [])), function ($q) use ($request) {
                $serviceIds = array_values(array_filter((array) $request->input('service_ids', []), fn ($v) => $v !== null && $v !== ''));
                if (!empty($serviceIds)) {
                    $q->whereIn('s1.service_id', $serviceIds);
                }
            });

        $this->constrainToForm($request, $base);

        $base = $base->select(
                'svc.pk as service_pk',
                'svc.service_name',
                DB::raw('COALESCE(svc.service_short_name, svc.service_name) as service_code'),
                DB::raw('COUNT(s1.user_id) as total'),
                DB::raw('SUM(CASE WHEN s1.gender="Male" THEN 1 ELSE 0 END) as male'),
                DB::raw('SUM(CASE WHEN s1.gender="Female" THEN 1 ELSE 0 END) as female'),
                DB::raw('SUM(CASE WHEN sm.status="SUBMITTED" THEN 1 ELSE 0 END) as submitted'),
                DB::raw('SUM(CASE WHEN sm.docs_done=1 THEN 1 ELSE 0 END) as docs_done')
            )
            ->groupBy('svc.pk','svc.service_name','svc.service_short_name')
            ->orderBy('svc.service_name');

        if ($request->ajax()) {
            $draw = (int) $request->input('draw', 1);
            $start = max((int) $request->input('start', 0), 0);
            $length = (int) $request->input('length', 10);
            $length = $length > 0 ? $length : 10;
            $search = trim((string) data_get($request->input('search', []), 'value', ''));

            $wrapped = DB::query()->fromSub($base, 'x');
            $recordsTotal = (clone $wrapped)->count();

            if ($search !== '') {
                $like = '%'.$search.'%';
                $wrapped->where(function ($q) use ($like) {
                    $q->where('service_name', 'like', $like)
                        ->orWhere('service_code', 'like', $like);
                });
            }

            $recordsFiltered = (clone $wrapped)->count();

            $orderColumnIndex = (int) data_get($request->input('order', []), '0.column', 1);
            $orderDir = strtolower((string) data_get($request->input('order', []), '0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
            $orderable = [
                1 => 'service_name',
                2 => 'service_code',
                3 => 'total',
                4 => 'male',
                5 => 'female',
                6 => 'submitted',
                7 => 'docs_done',
            ];
            $orderBy = $orderable[$orderColumnIndex] ?? 'service_name';

            $rows = $wrapped
                ->orderBy($orderBy, $orderDir)
                ->offset($start)
                ->limit($length)
                ->get()
                ->map(function ($row) {
                    $total = (int) ($row->total ?? 0);
                    $submitted = (int) ($row->submitted ?? 0);
                    $row->pct = $total > 0 ? (int) round(($submitted / $total) * 100) : 0;
                    return $row;
                })
                ->values();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $rows,
            ]);
        }

        return view('fc.report.by-service', compact('forms', 'services') + [
            'scopedForm' => $this->scopedFormFromRequest($request),
        ]);
    }

    // ── 4. State-wise Report ──────────────────────────────────────────
    public function byState(Request $request)
    {
        $forms = $this->reportFormsForFilter();
        $states = DB::table('state_master')
            ->orderBy('state_name')
            ->selectRaw('Pk as pk, state_name')
            ->get();

        $base = DB::table('student_master_firsts as s1')
            ->join('state_master as st','s1.allotted_state_id','=','st.Pk')
            ->leftJoin('student_masters as sm','s1.user_id','=','sm.user_id')
            ->when(!empty((array) $request->input('state_ids', [])), function ($q) use ($request) {
                $stateIds = array_values(array_filter((array) $request->input('state_ids', []), fn ($v) => $v !== null && $v !== ''));
                if (!empty($stateIds)) {
                    $q->whereIn('s1.allotted_state_id', $stateIds);
                }
            });

        $this->constrainToForm($request, $base);

        $base = $base->select(
                'st.state_name',
                DB::raw('CAST(st.Pk AS CHAR) as state_code'),
                DB::raw('COUNT(s1.user_id) as total'),
                DB::raw('SUM(CASE WHEN s1.gender="Male" THEN 1 ELSE 0 END) as male'),
                DB::raw('SUM(CASE WHEN s1.gender="Female" THEN 1 ELSE 0 END) as female'),
                DB::raw('SUM(CASE WHEN sm.status="SUBMITTED" THEN 1 ELSE 0 END) as submitted')
            )
            ->groupBy('st.Pk','st.state_name')
            ->orderBy('st.state_name');

        if ($request->ajax()) {
            $draw = (int) $request->input('draw', 1);
            $start = max((int) $request->input('start', 0), 0);
            $length = (int) $request->input('length', 10);
            $length = $length > 0 ? $length : 10;
            $search = trim((string) data_get($request->input('search', []), 'value', ''));

            $wrapped = DB::query()->fromSub($base, 'x');
            $recordsTotal = (clone $wrapped)->count();

            if ($search !== '') {
                $like = '%'.$search.'%';
                $wrapped->where(function ($q) use ($like) {
                    $q->where('state_name', 'like', $like)
                        ->orWhere('state_code', 'like', $like);
                });
            }

            $recordsFiltered = (clone $wrapped)->count();

            $orderColumnIndex = (int) data_get($request->input('order', []), '0.column', 1);
            $orderDir = strtolower((string) data_get($request->input('order', []), '0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
            $orderable = [
                1 => 'state_name',
                2 => 'state_code',
                3 => 'total',
                4 => 'male',
                5 => 'female',
                6 => 'submitted',
            ];
            $orderBy = $orderable[$orderColumnIndex] ?? 'state_name';

            $rows = $wrapped
                ->orderBy($orderBy, $orderDir)
                ->offset($start)
                ->limit($length)
                ->get()
                ->values();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $rows,
            ]);
        }

        return view('fc.report.by-state', compact('forms', 'states') + [
            'scopedForm' => $this->scopedFormFromRequest($request),
        ]);
    }

    // ── 5. Document Checklist Report ──────────────────────────────────
    public function documents(Request $request)
    {
        $forms  = $this->reportFormsForFilter();
        $docMasters = FcJoiningRelatedDocumentsMaster::where('is_active',1)->orderBy('display_order')->get();

        $docUploadedSql = '(d.is_uploaded = 1 OR (d.file_path IS NOT NULL AND d.file_path != \'\'))';

        $studentsQuery = DB::table('student_master_firsts as s1')
            ->leftJoin('student_masters as sm','s1.user_id','=','sm.user_id')
            ->leftJoin('service_masters as svc','s1.service_id','=','svc.id')
            ->when($request->filled('doc_status'), function($q) use ($request, $docUploadedSql) {
                $totalMandatory = FcJoiningRelatedDocumentsMaster::where('is_active',1)->where('is_mandatory',1)->count();
                if ($request->doc_status === 'complete') {
                    $q->whereRaw("(SELECT COUNT(*) FROM fc_joining_related_documents_details_masters d
                                   WHERE d.user_id=s1.user_id AND {$docUploadedSql}
                                   AND d.document_master_id IN (SELECT id FROM fc_joining_related_documents_masters WHERE is_mandatory=1))
                                   = {$totalMandatory}");
                } else {
                    $q->whereRaw("(SELECT COUNT(*) FROM fc_joining_related_documents_details_masters d
                                   WHERE d.user_id=s1.user_id AND {$docUploadedSql}
                                   AND d.document_master_id IN (SELECT id FROM fc_joining_related_documents_masters WHERE is_mandatory=1))
                                   < {$totalMandatory}");
                }
            });

        $this->constrainToForm($request, $studentsQuery);

        $students = $studentsQuery
            ->select('s1.user_id','s1.full_name','svc.service_code','s1.cadre')
            ->orderBy('s1.full_name')
            ->get();

        $userIds = $students->pluck('user_id')->filter()->unique()->values();
        // Treat as uploaded if flagged OR a file path exists (legacy / partial saves).
        $allUploaded = $userIds->isEmpty()
            ? collect()
            : FcJoiningRelatedDocumentsDetailsMaster::whereIn('user_id', $userIds)
                ->where(function ($q) {
                    $q->where('is_uploaded', 1)
                        ->orWhere(function ($q2) {
                            $q2->whereNotNull('file_path')->where('file_path', '!=', '');
                        });
                })
                ->get()
                ->groupBy(fn ($row) => (string) $row->user_id);

        return view('fc.report.documents', compact(
            'students', 'docMasters', 'allUploaded', 'forms'
        ) + [
            'scopedForm' => $this->scopedFormFromRequest($request),
        ]);
    }

    // ── 6. Bank Details Report ────────────────────────────────────────
    public function bankDetails(Request $request)
    {
        $forms = $this->reportFormsForFilter();

        $studentsQuery = DB::table('student_master_firsts as s1')
            ->leftJoin('student_masters as sm','s1.user_id','=','sm.user_id')
            ->leftJoin('new_registration_bank_details_masters as b','s1.user_id','=','b.user_id')
            ->leftJoin('service_masters as svc','s1.service_id','=','svc.id')
            ->when($request->filled('bank_status'), function($q) use ($request) {
                if ($request->bank_status === 'filled') $q->whereNotNull('b.account_no');
                else $q->whereNull('b.account_no');
            })
            ->when($request->filled('search'), function($q) use ($request) {
                $s = '%'.$request->search.'%';
                $q->where(fn($qq) => $qq->where('s1.full_name','like',$s)->orWhere('s1.user_id','like',$s));
            });

        $this->constrainToForm($request, $studentsQuery);

        $students = $studentsQuery
            ->select(
                's1.user_id','s1.full_name','svc.service_code','s1.cadre',
                'b.bank_name','b.branch_name','b.ifsc_code',
                'b.account_no','b.account_holder_name','b.account_type','b.is_verified'
            )
            ->orderBy('s1.full_name')
            ->paginate(50)->withQueryString();

        return view('fc.report.bank-details', compact('students', 'forms') + [
            'scopedForm' => $this->scopedFormFromRequest($request),
        ]);
    }

    // ── Export to CSV ─────────────────────────────────────────────────
    public function exportCsv(Request $request, string $type)
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=fc_{$type}_".now()->format('Ymd_His').'.csv',
            'Pragma'              => 'no-cache',
        ];

        $callback = match($type) {
            'overview' => fn() => $this->exportOverviewCsv(),
            'service'  => fn() => $this->exportServiceCsv(),
            'state'    => fn() => $this->exportStateCsv(),
            'bank'     => fn() => $this->exportBankCsv(),
            default    => abort(404),
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportOverviewCsv(): void
    {
        $out = fopen('php://output','w');
        fputcsv($out, ['S.No','User ID','Full Name','Gender','Service','Service Code','Cadre',
                       'Allotted State','Mobile','Email','Step1','Step2','Step3','Bank','Travel','Docs','Status','Confirmed']);

        DB::table('student_master_firsts as s1')
          ->leftJoin('student_masters as sm','s1.user_id','=','sm.user_id')
          ->leftJoin('service_masters as svc','s1.service_id','=','svc.id')
          ->leftJoin('state_masters as st','s1.allotted_state_id','=','st.id')
          ->leftJoin('student_confirm_masters as sc','s1.user_id','=','sc.user_id')
          ->select('s1.*','svc.service_name','svc.service_code','st.state_name',
                   'sm.status','sm.step1_done','sm.step2_done','sm.step3_done',
                   'sm.bank_done','sm.travel_done','sm.docs_done','sc.declaration_accepted')
          ->orderBy('s1.full_name')
          ->chunk(200, function($rows) use ($out) {
              static $i = 0;
              foreach ($rows as $r) {
                  fputcsv($out, [
                      ++$i, $r->user_id, $r->full_name, $r->gender ?? '',
                      $r->service_name ?? '', $r->service_code ?? '', $r->cadre ?? '',
                      $r->state_name ?? '', $r->mobile_no ?? '', $r->email ?? '',
                      $r->step1_done ? 'Yes':'No', $r->step2_done ? 'Yes':'No',
                      $r->step3_done ? 'Yes':'No', $r->bank_done ? 'Yes':'No',
                      $r->travel_done ? 'Yes':'No', $r->docs_done ? 'Yes':'No', $r->status ?? 'INCOMPLETE',
                      $r->declaration_accepted ? 'Yes':'No',
                  ]);
              }
          });
        fclose($out);
    }

    private function exportServiceCsv(): void
    {
        $out = fopen('php://output','w');
        fputcsv($out, ['Service','Code','Total','Male','Female','Submitted','Docs Done']);
        DB::table('student_master_firsts as s1')
          ->join('service_masters as svc','s1.service_id','=','svc.id')
          ->leftJoin('student_masters as sm','s1.user_id','=','sm.user_id')
          ->select('svc.service_name','svc.service_code',
                   DB::raw('COUNT(*) as total'),
                   DB::raw('SUM(s1.gender="Male") as male'),
                   DB::raw('SUM(s1.gender="Female") as female'),
                   DB::raw('SUM(sm.status="SUBMITTED") as submitted'),
                   DB::raw('SUM(sm.docs_done=1) as docs_done'))
          ->groupBy('svc.id','svc.service_name','svc.service_code')
          ->orderBy('svc.service_name')
          ->each(fn($r) => fputcsv($out, [$r->service_name,$r->service_code,$r->total,$r->male,$r->female,$r->submitted,$r->docs_done]));
        fclose($out);
    }

    private function exportStateCsv(): void
    {
        $out = fopen('php://output','w');
        fputcsv($out, ['State','Code','Total','Male','Female','Submitted']);
        DB::table('student_master_firsts as s1')
          ->join('state_masters as st','s1.allotted_state_id','=','st.id')
          ->leftJoin('student_masters as sm','s1.user_id','=','sm.user_id')
          ->select('st.state_name','st.state_code',
                   DB::raw('COUNT(*) as total'),
                   DB::raw('SUM(s1.gender="Male") as male'),
                   DB::raw('SUM(s1.gender="Female") as female'),
                   DB::raw('SUM(sm.status="SUBMITTED") as submitted'))
          ->groupBy('st.id','st.state_name','st.state_code')
          ->orderBy('st.state_name')
          ->each(fn($r) => fputcsv($out, [$r->state_name,$r->state_code,$r->total,$r->male,$r->female,$r->submitted]));
        fclose($out);
    }

    private function exportBankCsv(): void
    {
        $out = fopen('php://output','w');
        fputcsv($out, ['User ID','Full Name','Service','Bank Name','Branch','IFSC','Account No','Holder Name','Type','Verified']);
        DB::table('student_master_firsts as s1')
          ->leftJoin('service_masters as svc','s1.service_id','=','svc.id')
          ->leftJoin('new_registration_bank_details_masters as b','s1.user_id','=','b.user_id')
          ->whereNotNull('b.account_no')
          ->select('s1.user_id','s1.full_name','svc.service_code','b.*')
          ->orderBy('s1.full_name')
          ->each(fn($r) => fputcsv($out, [
              $r->user_id,$r->full_name,$r->service_code ?? '',
              $r->bank_name ?? '',$r->branch_name ?? '',$r->ifsc_code ?? '',
              $r->account_no ?? '',$r->account_holder_name ?? '',
              $r->account_type ?? '',$r->is_verified ? 'Yes':'No',
          ]));
        fclose($out);
    }
}
