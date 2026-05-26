<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\{
    StudentMaster, StudentMasterFirst, StudentMasterSecond,
    NewRegistrationBankDetailsMaster, FcJoiningRelatedDocumentsDetailsMaster,
    StudentConfirmMaster, StudentMasterIncompletMaster
};
use App\Services\FC\FcCourseEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Registration status page – matches registrationStatus.html & HomeController.java
 */
class RegistrationStatusController extends Controller
{
    public function show()
    {
        $userId = Auth::id();
        $master    = StudentMaster::forUser($userId)->first();
        $step1     = StudentMasterFirst::forUser($userId)->first();
        $step2     = StudentMasterSecond::forUser($userId)->first();
        $bank      = NewRegistrationBankDetailsMaster::forUser($userId)->first();
        $documents = FcJoiningRelatedDocumentsDetailsMaster::forUser($userId)
            ->with('documentMaster')->get();
        $confirmation = StudentConfirmMaster::forUser($userId)->first();

        $stepFlagMap = [
            'step1'     => 'step1_done',
            'step2'     => 'step2_done',
            'step3'     => 'step3_done',
            'bank'      => 'bank_done',
            'travel'    => 'travel_done',
            'documents' => 'docs_done',
        ];
        $progressSteps = [];
        foreach ($stepFlagMap as $key => $column) {
            $progressSteps[$key] = (bool) ($master?->{$column} ?? false);
        }
        $progressSteps['confirmed'] = (bool) ($confirmation?->declaration_accepted ?? false);

        $doneCount   = collect($progressSteps)->filter()->count();
        $totalSteps  = count($progressSteps);
        $progress    = [
            'done'       => $doneCount,
            'total'      => $totalSteps,
            'percentage' => $totalSteps > 0 ? (int) round($doneCount / $totalSteps * 100) : 0,
            'steps'      => $progressSteps,
        ];

        return view('fc.registration.status', compact(
            'master','step1','step2','bank','documents','confirmation','progress'
        ));
    }

    public function confirm(Request $request, FcCourseEnrollmentService $enrollment)
    {
        $userId = Auth::id();
        $master   = StudentMaster::forUser($userId)->first();

        if (! $master || $master->status !== 'SUBMITTED') {
            return back()->with('error', 'Please complete all registration steps before confirming.');
        }

        $request->validate([
            'declaration' => 'required|accepted',
        ]);

        StudentConfirmMaster::updateOrCreate([fc_user_col('student_confirm_masters') => fc_user_val('student_confirm_masters', $userId)], [
            'declaration_accepted' => 1,
            'confirmed_at'         => now(),
            'ip_address'           => $request->ip(),
        ]);

        $enrollResult = $enrollment->enrollTrainee($userId);
        $flash = 'Declaration accepted. Your registration is now confirmed.';
        if ($enrollResult['enrolled']) {
            $flash .= ' You are enrolled in the linked programme.';
        } elseif ($enrollResult['message']) {
            $flash .= ' Note: '.$enrollResult['message'];
        }

        return redirect()->route('fc-reg.registration.status')
            ->with('success', $flash);
    }
}
