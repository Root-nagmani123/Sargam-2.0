<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcActivityMaster;
use App\Models\FC\FcOtActivity;
use App\Models\FC\FcOtDetail;
use App\Models\FC\SessionMaster;
use App\Services\FC\FcPostArrivalAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FcActivityHomeController extends Controller
{
    public function __construct(private FcPostArrivalAccessService $access)
    {
    }

    public function index(): View
    {
        $user = Auth::user();

        $deptIds = $this->access->departmentIdsForActivityEntry();
        $q = FcOtActivity::query()
            ->with(['ot', 'activityMaster.department'])
            ->where('submitedby', $user->username)
            ->where('status', 1)
            ->latest('id');
        if ($deptIds !== null) {
            if ($deptIds === []) {
                $q->whereRaw('0 = 1');
            } else {
                $q->whereHas('activityMaster', fn ($q2) => $q2->whereIn('department_id', $deptIds));
            }
        }

        $activities = $q->get();

        return view('admin.fc-activities.home.index', [
            'activities' => $activities,
            'user' => $user,
            'showSetupLinks' => $this->access->canManageActivitySetup(),
            'canAccessMedical' => $this->access->canAccessMedicalModule(),
        ]);
    }

    public function ajaxCourses(): JsonResponse
    {
        $courses = SessionMaster::query()
            ->where('is_active', 1)
            ->selectRaw('session_name as c_code, session_name as c_name')
            ->get();

        return response()->json($courses);
    }

    public function ajaxOts(Request $request): JsonResponse
    {
        $course = $request->query('course', '');
        $ots = FcOtDetail::active()->byCourse($course)->select('username', 'otname', 'otcode')->orderBy('otname')->get();

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
            'username' => $ot->username,
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
