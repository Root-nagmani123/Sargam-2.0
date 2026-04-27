<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcFinalFinding;
use App\Models\FC\FcOtActivity;
use App\Models\FC\FcOtDetail;
use App\Models\FC\FcPathReport;
use App\Models\FC\FcPreHistory;
use App\Models\FC\SessionMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FcActivityMedicalController extends Controller
{
    public function index(): View
    {
        $courses = SessionMaster::query()
            ->where('is_active', 1)
            ->selectRaw('session_name as c_code, session_name as c_name')
            ->get();
        return view('admin.fc-activities.medical.index', compact('courses'));
    }

    public function show(Request $request): View
    {
        $course = $request->query('course', '');
        $otcode = $request->query('ot', '');

        $ot = FcOtDetail::where('otcode', $otcode)->where('course', $course)->firstOrFail();
        $preHistory = FcPreHistory::where('userid', $ot->username)->where('course', $course)->first();
        $actCodes = ['height', 'weight', 'spo2', 'pulse', 'bp', 'preremarks'];
        $activities = FcOtActivity::where('username', $ot->username)
            ->where('course', $course)
            ->whereIn('activity', $actCodes)
            ->pluck('activityval', 'activity');

        $height = (float) ($activities['height'] ?? 0);
        $weight = (float) ($activities['weight'] ?? 0);
        $bmi = $height > 0 ? round(($weight / ($height * $height)) * 10000, 1) : 0;
        $bmiClass = match (true) {
            $bmi > 0 && $bmi < 18.5 => 'Underweight',
            $bmi >= 18.5 && $bmi < 25 => 'Normal',
            $bmi >= 25 && $bmi < 30 => 'Overweight',
            $bmi >= 30 => 'Obesity',
            default => '',
        };

        $pathReports = FcPathReport::where('userid', $ot->username)->where('course', $course)->get();
        $finalFindings = FcFinalFinding::where('userid', $ot->username)->where('course', $course)->get();

        return view('admin.fc-activities.medical.report', compact(
            'ot',
            'course',
            'preHistory',
            'activities',
            'height',
            'weight',
            'bmi',
            'bmiClass',
            'pathReports',
            'finalFindings'
        ));
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'otcode' => 'required|string',
            'course' => 'required|string',
            'file1' => 'nullable|file|mimes:pdf|max:10240',
            'textfindings' => 'nullable|string|max:5000',
        ]);

        $ot = FcOtDetail::where('otcode', $request->otcode)->firstOrFail();
        $pathreport = null;
        $findings = $request->input('textfindings', '');

        if ($request->hasFile('file1') && $request->file('file1')->isValid()) {
            $file = $request->file('file1');
            $path = $file->storeAs('fc/path_report', $file->getClientOriginalName(), 'public');
            $pathreport = 'storage/' . $path;
        }

        if (! $pathreport && ! $findings) {
            return response()->json(['status' => 'ok']);
        }

        if (! $pathreport && $findings) {
            $existing = FcPathReport::where('userid', $ot->username)->where('course', $request->course)->first();
            FcPathReport::updateOrCreate(
                ['userid' => $ot->username, 'course' => $request->course],
                ['path_report' => $existing->path_report ?? null, 'status' => 1, 'submit_dt' => now()]
            );
            FcFinalFinding::create([
                'userid' => $ot->username,
                'findings' => ucwords($findings),
                'course' => $request->course,
                'submited_by' => Auth::user()->username ?? '',
                'status' => 1,
                'submit_dt' => now(),
            ]);
        } elseif ($pathreport && ! $findings) {
            FcPathReport::create([
                'userid' => $ot->username,
                'path_report' => $pathreport,
                'course' => $request->course,
                'status' => 1,
                'submit_dt' => now(),
            ]);
        } else {
            FcPathReport::create([
                'userid' => $ot->username,
                'path_report' => $pathreport,
                'course' => $request->course,
                'status' => 1,
                'submit_dt' => now(),
            ]);
            FcFinalFinding::create([
                'userid' => $ot->username,
                'findings' => ucwords($findings),
                'course' => $request->course,
                'submited_by' => Auth::user()->username ?? '',
                'status' => 1,
                'submit_dt' => now(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
