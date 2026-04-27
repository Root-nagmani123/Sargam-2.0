<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcActivityMaster;
use App\Models\FC\FcOtActivity;
use App\Models\FC\FcOtDetail;
use App\Models\FC\SessionMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FcActivityHomeController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $activities = FcOtActivity::with(['ot', 'activityMaster'])
            ->where('submitedby', $user->username)
            ->where('status', 1)
            ->latest('id')
            ->get();

        return view('admin.fc-activities.home.index', compact('activities', 'user'));
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
        $ot = FcOtDetail::where('otcode', $otcode)->first();

        if (! $ot) {
            return response()->json(['name' => '', 'warning' => false]);
        }

        return response()->json([
            'name' => $ot->otname,
            'username' => $ot->username,
            'warning' => $ot->hasPreHistory(),
        ]);
    }

    public function ajaxHouse(Request $request): JsonResponse
    {
        $otcode = $request->query('otcode', '');
        $ot = FcOtDetail::where('otcode', $otcode)->select('house', 'housen')->first();
        return response()->json([
            'house' => $ot->house ?? '',
            'housen' => $ot->housen ?? '',
        ]);
    }

    public function ajaxActivities(Request $request): JsonResponse
    {
        $ccode = trim((string) $request->query('ccode', ''));

        $activities = FcActivityMaster::active()
            ->when($ccode !== '', function ($q) use ($ccode) {
                // Course names are session labels; trim-match avoids whitespace mismatches.
                $q->whereRaw('TRIM(ccode) = ?', [$ccode]);
            })
            ->select('menuid', 'menun')
            ->get();

        // Safe fallback: if no exact course match, show all active activities.
        if ($activities->isEmpty()) {
            $activities = FcActivityMaster::active()
                ->select('menuid', 'menun')
                ->get();
        }

        return response()->json($activities);
    }
}
