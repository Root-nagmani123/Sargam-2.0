<?php

namespace App\Services\FC;

use App\Models\FC\{
    StudentMaster, StudentMasterFirst, StudentMasterSecond,
    StudentMasterQualificationDetails, NewRegistrationBankDetailsMaster,
    FcJoiningRelatedDocumentsDetailsMaster, FcJoiningRelatedDocumentsMaster,
    StudentConfirmMaster, JbpUser
};
use Illuminate\Support\Facades\Hash;

class RegistrationService
{
    /**
     * Return a progress array for the dashboard progress bar.
     * Mirrors the logic used in HomeController.java to determine step completion.
     */
    public function getProgress(string $username): array
    {
        $master = StudentMaster::where('username', $username)->first();
        $step1  = StudentMasterFirst::where('username', $username)->where('step1_completed', 1)->exists();
        $step2  = StudentMasterSecond::where('username', $username)->where('step2_completed', 1)->exists();
        $step3  = $master?->step3_done ?? false;
        $bank   = NewRegistrationBankDetailsMaster::where('username', $username)->exists();
        $travel = (bool) ($master?->travel_done ?? false);
        $docs   = $this->allMandatoryDocsUploaded($username);
        $confirmed = StudentConfirmMaster::where('username', $username)->where('declaration_accepted', 1)->exists();

        $steps = [
            'step1'     => $step1,
            'step2'     => $step2,
            'step3'     => $step3,
            'bank'      => $bank,
            'travel'    => $travel,
            'documents' => $docs,
            'confirmed' => $confirmed,
        ];

        $done       = collect($steps)->filter()->count();
        $total      = count($steps);
        $percentage = (int) ($done / $total * 100);

        return [
            'steps'      => $steps,
            'done'       => $done,
            'total'      => $total,
            'percentage' => $percentage,
            'status'     => $master?->status ?? 'NOT_STARTED',
        ];
    }

    /**
     * Create a new FC user account (called from admin or seeder).
     */
    public function createFcUser(string $username, string $password, string $email = null): JbpUser
    {
        return JbpUser::create([
            'username' => $username,
            'password' => Hash::make($password),
            'email'    => $email,
            'role'     => 'FC',
            'enabled'  => 1,
        ]);
    }

    /**
     * Check if all mandatory documents have been uploaded.
     */
    public function allMandatoryDocsUploaded(string $username): bool
    {
        $mandatoryIds = FcJoiningRelatedDocumentsMaster::where('is_active', 1)
            ->where('is_mandatory', 1)->pluck('id');

        if ($mandatoryIds->isEmpty()) {
            return false;
        }

        $uploadedIds = FcJoiningRelatedDocumentsDetailsMaster::where('username', $username)
            ->where('is_uploaded', 1)->pluck('document_master_id');

        return $mandatoryIds->diff($uploadedIds)->isEmpty();
    }

    /**
     * Get all data for a student (used in admin report / print view).
     */
    public function getFullStudentProfile(string $username): array
    {
        return [
            'step1'        => StudentMasterFirst::where('username', $username)->with(['session','service','allottedState'])->first(),
            'step2'        => StudentMasterSecond::where('username', $username)->with(['category','religion','permState','presState'])->first(),
            'master'       => StudentMaster::where('username', $username)->first(),
            'bank'         => NewRegistrationBankDetailsMaster::where('username', $username)->first(),
            'documents'    => FcJoiningRelatedDocumentsDetailsMaster::where('username', $username)->with('documentMaster')->get(),
            'confirmed'    => StudentConfirmMaster::where('username', $username)->first(),
            'progress'     => $this->getProgress($username),
        ];
    }
}
