<?php

namespace App\Http\Controllers;

use App\Models\CourseMaster;
use App\Models\FinalFinding;
use App\Models\OtActivity;
use App\Models\OtDetail;
use App\Models\PathReport;
use App\Models\PreHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * MedicalController
 *
 * Converted from:
 *   report_med.php     → index()     — select course + enter OT code to pull up report
 *   view_otreport.php  → show()      — full medical card for an OT (AJAX-loaded in original)
 *   upload_report.php  → upload()    — save pathology PDF + final findings text
 *
 * Key logic preserved:
 *   BMI calculation:  bmi = (weight / height²) * 10000
 *   BMI colour codes: <18.5=yellow(Underweight), 18.5-25=green(Normal),
 *                     25-30=orange(Overweight), ≥30=red(Obesity)
 *   Pre-history red flag: shown if pre_history record exists for OT
 *
 * Routes:
 *   GET  /medical              → index()
 *   GET  /medical/report       → show()   ?course=&ot=
 *   POST /medical/upload       → upload()
 */
class MedicalController extends Controller
{
    // ── index: report_med.php ─────────────────────────────────────────────────
    public function index(): View
    {
        $courses = CourseMaster::active()->select('c_code','c_name')->distinct()->get();
        return view('medical.index', compact('courses'));
    }

    // ── show: view_otreport.php ───────────────────────────────────────────────
    /**
     * Original: separate query per activity type (height, weight, spo2, pulse, bp, preremarks)
     * Optimised: single query for all activities, then keyed lookup.
     *
     * BMI formula from original:
     *   $divind = $row2[0] * $row2[0];   // height²
     *   $bmi = number_format(($row3[0] / $divind) * 10000, 1);
     */
    public function show(Request $request): View
    {
        $course = $request->query('course', '');
        $otcode = $request->query('ot', '');

        // Original: select * from ot_details where otcode='$ot' AND course='$cname'
        $ot = OtDetail::where('otcode', $otcode)
            ->where('course', $course)
            ->firstOrFail();

        // Original: select * from pre_history where userid='$row[1]' AND course='$cname'
        $preHistory = PreHistory::where('userid', $ot->username)
            ->where('course', $course)
            ->first();

        // Original: 6 separate activity queries → single optimised query
        $actCodes   = ['height','weight','spo2','pulse','bp','preremarks'];
        $activities = OtActivity::where('username', $ot->username)
            ->where('course', $course)
            ->whereIn('activity', $actCodes)
            ->pluck('activityval', 'activity');

        $height = (float) ($activities['height'] ?? 0);
        $weight = (float) ($activities['weight'] ?? 0);

        // BMI calculation — exact formula from view_otreport.php
        $bmi    = 0;
        if ($height > 0) {
            $bmi = round(($weight / ($height * $height)) * 10000, 1);
        }

        // BMI classification (from view_otreport.php conditional blocks)
        $bmiClass = match(true) {
            $bmi > 0 && $bmi < 18.5  => ['label' => 'Underweight', 'color' => 'warning'],
            $bmi >= 18.5 && $bmi < 25 => ['label' => 'Normal',      'color' => 'success'],
            $bmi >= 25 && $bmi < 30   => ['label' => 'Overweight',  'color' => 'orange'],
            $bmi >= 30                => ['label' => 'Obesity',      'color' => 'danger'],
            default                   => ['label' => '',             'color' => 'secondary'],
        };

        // Original: select path_report,submit_dt from path_report where userid=... AND course=...
        $pathReports  = PathReport::where('userid', $ot->username)->where('course', $course)->get();

        // Original: select findings,submit_dt from final_findings where userid=... AND course=...
        $finalFindings = FinalFinding::where('userid', $ot->username)->where('course', $course)->get();

        return view('medical.report', compact(
            'ot','course','preHistory','activities',
            'height','weight','bmi','bmiClass',
            'pathReports','finalFindings'
        ));
    }

    // ── upload: upload_report.php ─────────────────────────────────────────────
    /**
     * Original logic (preserved exactly):
     *   Case 1: both empty  → echo 'ok' (no-op)
     *   Case 2: no file, has findings → update existing path_report row + insert findings
     *   Case 3: file exists, no findings → insert path_report only
     *   Case 4: both exist  → insert path_report + insert findings
     *
     * Security: original had no auth check for submitedby — added Auth::user()
     * Security: original used $_POST['txtotcode'] as otcode, no validation
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'otcode'       => 'required|string',
            'course'       => 'required|string',
            'file1'        => 'nullable|file|mimes:pdf|max:10240',
            'textfindings' => 'nullable|string|max:5000',
        ]);

        $ot = OtDetail::where('otcode', $request->otcode)->firstOrFail();

        $pathreport = null;
        $findings   = $request->input('textfindings', '');

        // Handle PDF upload (original: uploads/path_report/{filename})
        if ($request->hasFile('file1') && $request->file('file1')->isValid()) {
            $file = $request->file('file1');
            $path = $file->storeAs(
                'path_report',
                $file->getClientOriginalName(),
                'public'
            );
            $pathreport = 'storage/' . $path;
        }

        // Case 1: nothing to save
        if (!$pathreport && !$findings) {
            return response()->json(['status' => 'ok']);
        }

        // Case 2: findings only → update existing path_report row
        if (!$pathreport && $findings) {
            $existing = PathReport::where('userid', $ot->username)
                ->where('course', $request->course)
                ->first();

            PathReport::updateOrCreate(
                ['userid' => $ot->username, 'course' => $request->course],
                ['path_report' => $existing->path_report ?? null, 'status' => 1, 'submit_dt' => now()]
            );

            FinalFinding::create([
                'userid'      => $ot->username,
                'findings'    => ucwords($findings),
                'course'      => $request->course,
                'submited_by' => Auth::user()->username ?? '',
                'status'      => 1,
                'submit_dt'   => now(),
            ]);
        }

        // Case 3: file only
        elseif ($pathreport && !$findings) {
            PathReport::create([
                'userid'      => $ot->username,
                'path_report' => $pathreport,
                'course'      => $request->course,
                'status'      => 1,
                'submit_dt'   => now(),
            ]);
        }

        // Case 4: both
        else {
            PathReport::create([
                'userid'      => $ot->username,
                'path_report' => $pathreport,
                'course'      => $request->course,
                'status'      => 1,
                'submit_dt'   => now(),
            ]);

            FinalFinding::create([
                'userid'      => $ot->username,
                'findings'    => ucwords($findings),
                'course'      => $request->course,
                'submited_by' => Auth::user()->username ?? '',
                'status'      => 1,
                'submit_dt'   => now(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
