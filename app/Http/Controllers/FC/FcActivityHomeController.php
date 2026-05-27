<?php

namespace App\Http\Controllers\FC;

use App\DataTables\FC\FcActivitiesHomeDataTable;
use App\Http\Controllers\Controller;
use App\Models\FC\FcActivityMaster;
use App\Models\FC\FcForm;
use App\Services\FC\FcActivityStudentResolver;
use App\Services\FC\FcPostArrivalAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FcActivityHomeController extends Controller
{
    public function __construct(
        private FcPostArrivalAccessService $access,
        private FcActivityStudentResolver $trainees
    ) {
    }

    public function index(): View
    {
        $forms = FcForm::query()
            ->with('courseMaster:pk,course_name')
            ->where('is_active', true)
            ->orderBy('form_name')
            ->get(['id', 'form_name', 'form_slug', 'course_master_pk']);

        return view('admin.fc-activities.home.index', [
            'forms' => $forms,
            'showSetupLinks' => $this->access->canManageActivitySetup(),
            'canAccessMedical' => $this->access->canAccessMedicalModule(),
        ]);
    }

    /**
     * Server-side list of activities entered by the current user (scoped by department access).
     * Query params: DataTables standard + filter_form_id, filter_otcode, filter_activity.
     */
    public function dataTable(FcActivitiesHomeDataTable $dataTable): JsonResponse
    {
        return $dataTable->ajax();
    }

    public function ajaxCourses(): JsonResponse
    {
        $forms = FcForm::query()
            ->with('courseMaster:pk,course_name')
            ->where('is_active', true)
            ->orderBy('form_name')
            ->get(['id', 'form_name', 'form_slug', 'course_master_pk']);

        $courses = $forms->map(fn (FcForm $form) => [
            'form_id' => $form->id,
            'course_master_pk' => $form->course_master_pk,
            'c_code' => trim((string) ($form->courseMaster?->course_name ?? $form->form_name)),
            'c_name' => $form->form_name.($form->courseMaster?->course_name ? ' — '.$form->courseMaster->course_name : ''),
        ])->values();

        return response()->json($courses);
    }

    public function ajaxOts(Request $request): JsonResponse
    {
        $ots = $this->trainees->listForActivityGrids()
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'otname' => $row->otname,
                'otcode' => $row->otcode,
            ])
            ->values();

        return response()->json($ots);
    }

    public function ajaxOtName(Request $request): JsonResponse
    {
        $otcode = $request->query('otcode', '');
        $course = trim((string) $request->query('course', ''));
        $courseMasterPk = (int) $request->query('course_master_pk', 0);

        $trainee = $this->trainees->findByOtCode($otcode, $courseMasterPk > 0 ? $courseMasterPk : null);
        if (! $trainee && $courseMasterPk > 0) {
            $trainee = $this->trainees->findByOtCode($otcode, null);
        }

        if (! $trainee) {
            return response()->json([
                'name' => '',
                'house' => '',
                'housen' => '',
                'found' => false,
                'warning' => false,
            ]);
        }

        try {
            $this->trainees->syncMedicalOtDetail($trainee, $course !== '' ? $course : null);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'name' => $trainee->otname,
            'house' => $trainee->house ?? '',
            'housen' => $trainee->housen ?? '',
            'user_id' => $trainee->credentials_pk,
            'has_credentials' => $trainee->credentials_pk !== null,
            'found' => true,
            'warning' => $this->trainees->hasPreHistoryForTrainee(
                $trainee,
                $course !== '' ? $course : null
            ),
        ]);
    }

    public function ajaxHouse(Request $request): JsonResponse
    {
        $otcode = $request->query('otcode', '');
        $courseMasterPk = (int) $request->query('course_master_pk', 0);
        $trainee = $this->trainees->findByOtCode($otcode, $courseMasterPk > 0 ? $courseMasterPk : null);
        if (! $trainee && $courseMasterPk > 0) {
            $trainee = $this->trainees->findByOtCode($otcode, null);
        }

        return response()->json([
            'house' => $trainee->house ?? '',
            'housen' => $trainee->housen ?? '',
            'found' => $trainee !== null,
        ]);
    }

    public function ajaxActivities(Request $request): JsonResponse
    {
        $ccode = trim((string) $request->query('ccode', ''));

        $base = FcActivityMaster::query()
            ->active()
            ->forDepartmentIds($this->access->departmentIdsForActivityEntry())
            ->ordered()
            ->select(['menuid', 'menun', 'entry_policy']);

        $activities = (clone $base)
            ->when($ccode !== '', fn ($q) => $q->forCourse($ccode))
            ->get();

        if ($activities->isEmpty()) {
            $activities = (clone $base)->get();
        }

        return response()->json($activities);
    }
}
