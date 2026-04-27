<?php

namespace App\Http\Controllers;

use App\Models\ActivityMaster;
use App\Models\CourseMaster;
use App\Models\OtActivity;
use App\Models\OtDetail;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * HomeController
 *
 * Converted from: home.php + all load_*.php AJAX files
 *
 * Original AJAX (XMLHttpRequest) → Laravel JSON API endpoints
 *
 * Routes:
 *   GET  /home                     → index()
 *   GET  /ajax/courses             → ajaxCourses()     (load_course.php)
 *   GET  /ajax/ots?course=         → ajaxOts()         (load_ot.php)
 *   GET  /ajax/ot-name?otcode=     → ajaxOtName()      (load_otname.php)
 *   GET  /ajax/house?otcode=       → ajaxHouse()       (load_house.php)
 *   GET  /ajax/activities?ccode=   → ajaxActivities()  (showactivity.php)
 */
class HomeController extends Controller
{
    public function __construct(private ActivityService $svc) {}

    // ── Home Dashboard ────────────────────────────────────────────────────────
    /**
     * home.php:
     *   - Shows staff name + department from user_login
     *   - Lists all activities submitted by this staff (submitedby = $uname)
     *   - Renders department-specific sidebar links
     */
    public function index(): View
    {
        $user = Auth::user();

        // Original: select * from otactivity_details where submitedby='$uname' AND status=1 order by sr_no DESC
        $activities = OtActivity::with(['ot', 'activityMaster'])
            ->where('submitedby', $user->username)
            ->where('status', 1)
            ->latest('id')
            ->get()
            ->map(function ($act) {
                return [
                    'activityid'  => $act->activityid,
                    'otname'      => $act->ot->otname ?? '',
                    'otcode'      => $act->ot->otcode ?? '',
                    'course'      => $act->course,
                    'activity'    => $act->activityMaster->menun ?? $act->activity,
                    'activityval' => $act->activityval,
                    'activitydt'  => $act->activitydt,
                ];
            });

        return view('home.index', [
            'user'       => $user,
            'activities' => $activities,
        ]);
    }

    // ── AJAX: load_course.php → returns courses as JSON ───────────────────────
    public function ajaxCourses(): JsonResponse
    {
        $courses = CourseMaster::active()
            ->select('c_code', 'c_name')
            ->distinct()
            ->get();

        return response()->json($courses);
    }

    // ── AJAX: load_ot.php → OTs for a course ─────────────────────────────────
    public function ajaxOts(Request $request): JsonResponse
    {
        $course = $request->query('course', '');
        $ots    = OtDetail::active()
            ->byCourse($course)
            ->select('username', 'otname', 'otcode')
            ->orderBy('otname')
            ->get();

        return response()->json($ots);
    }

    // ── AJAX: load_otname.php → OT name + pre-history flag ───────────────────
    /**
     * Original: if pre_history record exists, show red flag "(Consultation required)"
     */
    public function ajaxOtName(Request $request): JsonResponse
    {
        $otcode = $request->query('otcode', '');
        $ot     = OtDetail::where('otcode', $otcode)->first();

        if (!$ot) {
            return response()->json(['name' => '', 'warning' => false]);
        }

        return response()->json([
            'name'    => $ot->otname,
            'username'=> $ot->username,
            'warning' => $ot->hasPreHistory(),  // red flag logic from load_otname.php
        ]);
    }

    // ── AJAX: load_house.php → house details for an OT ───────────────────────
    public function ajaxHouse(Request $request): JsonResponse
    {
        $otcode = $request->query('otcode', '');
        $ot     = OtDetail::where('otcode', $otcode)
            ->select('house', 'housen')
            ->first();

        return response()->json([
            'house'  => $ot->house  ?? '',
            'housen' => $ot->housen ?? '',
        ]);
    }

    // ── AJAX: showactivity.php → activity types for a course ─────────────────
    public function ajaxActivities(Request $request): JsonResponse
    {
        $ccode = $request->query('ccode', '');
        $activities = ActivityMaster::active()
            ->forCourse($ccode)
            ->select('menuid', 'menun')
            ->get();

        return response()->json($activities);
    }
}
