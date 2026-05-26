<?php

namespace App\Http\Controllers\FC;

use App\DataTables\FC\FcActivitiesHomeDataTable;
use App\Http\Controllers\Controller;
use App\Models\FC\FcActivityMaster;
use App\Models\FC\FcForm;
use App\Models\FC\FcOtDetail;
use App\Services\FC\FcPostArrivalAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FcActivityHomeController extends Controller
{
    public function __construct(private FcPostArrivalAccessService $access)
    {
    }

    public function index(): View
    {
        return view('admin.fc-activities.home.index', [
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
            'c_code' => trim((string) ($form->courseMaster?->course_name ?? $form->form_name)),
            'c_name' => $form->form_name.($form->courseMaster?->course_name ? ' — '.$form->courseMaster->course_name : ''),
        ])->values();

        return response()->json($courses);
    }

    public function ajaxOts(Request $request): JsonResponse
    {
        $course = $request->query('course', '');
        $ots = FcOtDetail::active()->byCourse($course)->select('user_id', 'otname', 'otcode')->orderBy('otname')->get();

        return response()->json($ots);
    }

    public function ajaxOtName(Request $request): JsonResponse
    {
        $otcode = $request->query('otcode', '');
        $course = trim((string) $request->query('course', ''));
        $ot = FcOtDetail::where('otcode', $otcode)->first();

        if (! $ot) {
            return response()->json(['name' => '', 'warning' => false]);
        }

        return response()->json([
            'name' => $ot->otname,
            'user_id' => $ot->user_id,
            'warning' => $course !== '' ? $ot->hasPreHistory($course) : $ot->hasPreHistory(),
        ]);
    }

    public function ajaxHouse(Request $request): JsonResponse
    {
        $otcode = $request->query('otcode', '');
        $ot = FcOtDetail::where('otcode', $otcode)->select('house', 'housen')->first();

        return response()->json([
            'house' => $ot?->house ?? '',
            'housen' => $ot?->housen ?? '',
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
