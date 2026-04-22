<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\{
    StudentMaster, StudentMasterFirst, StudentMasterSecond,
    NewRegistrationBankDetailsMaster, FcJoiningRelatedDocumentsDetailsMaster,
    StudentConfirmMaster, StudentMasterIncompletMaster
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Registration status page – matches registrationStatus.html & HomeController.java
 */
class RegistrationStatusController extends Controller
{
    public function show()
    {
        $username  = Auth::user()->username;
        $master    = StudentMaster::where('username', $username)->first();
        $step1     = StudentMasterFirst::where('username', $username)->first();
        $step2     = StudentMasterSecond::where('username', $username)->first();
        $bank      = NewRegistrationBankDetailsMaster::where('username', $username)->first();
        $documents = FcJoiningRelatedDocumentsDetailsMaster::where('username', $username)
            ->with('documentMaster')->get();
        $confirmation = StudentConfirmMaster::where('username', $username)->first();

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

    public function confirm(Request $request)
    {
        $username = Auth::user()->username;
        $master   = StudentMaster::where('username', $username)->first();

        if (! $master || $master->status !== 'SUBMITTED') {
            return back()->with('error', 'Please complete all registration steps before confirming.');
        }

        $request->validate([
            'declaration' => 'required|accepted',
        ]);

        StudentConfirmMaster::updateOrCreate(['username' => $username], [
            'declaration_accepted' => 1,
            'confirmed_at'         => now(),
            'ip_address'           => $request->ip(),
        ]);

        return redirect()->route('fc-reg.registration.status')
            ->with('success', 'Declaration accepted. Your registration is now confirmed.');
    }
}
