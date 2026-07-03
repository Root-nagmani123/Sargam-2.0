<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use App\Models\FC\StudentMaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps fc_registration_master.is_registered and application_type in sync with trainee progress:
 * both set to 1 only when the first two active steps are complete (not at credential creation).
 */
class FcRegistrationRegisteredSyncService
{
    public function syncForCredentialsUser(int $userCredentialsPk, ?FcForm $form = null): void
    {
        try {
            if (! Schema::hasTable('fc_registration_master')
                || ! Schema::hasColumn('fc_registration_master', 'is_registered')) {
                return;
            }

            if (FcRosterAuthService::isStagedUserId($userCredentialsPk)) {
                $registration = DB::table('fc_registration_master')
                    ->where('pk', FcRosterAuthService::rosterPkFromStagedUserId($userCredentialsPk))
                    ->first();
                if (! $registration) {
                    return;
                }
            } else {
                $credential = DB::table('user_credentials')->where('pk', $userCredentialsPk)->first();
                if (! $credential) {
                    return;
                }

                $registration = $this->resolveRegistrationRow($credential);
                if (! $registration) {
                    return;
                }
            }

            $form = $form ?? app(FcRegistrationFlowService::class)->activeFormFromSession();
            if (! $form && ! FcRosterAuthService::isStagedUserId($userCredentialsPk)) {
                $form = FcForm::resolveForUserId($userCredentialsPk);
            }

            $isRegistered = $this->firstTwoStepsComplete($userCredentialsPk, $form);
            $isExemption = (int) ($registration->application_type ?? 0) === FcRosterApplicationGuardService::APPLICATION_EXEMPTION;

            $update = ['is_registered' => $isRegistered ? 1 : 0];

            if (! $isExemption && Schema::hasColumn('fc_registration_master', 'application_type')) {
                $update['application_type'] = $isRegistered
                    ? FcRosterApplicationGuardService::APPLICATION_REGISTRATION
                    : FcRosterApplicationGuardService::APPLICATION_NA;
            }

            DB::table('fc_registration_master')
                ->where('pk', $registration->pk)
                ->update($update);
        } catch (\Throwable $e) {
            Log::warning('fc_registration_master.is_registered sync failed', [
                'user_credentials_pk' => $userCredentialsPk,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function resolveRegistrationRow(object $credential): ?object
    {
        $loginName = trim((string) ($credential->user_name ?? ''));
        if ($loginName !== '') {
            $row = DB::table('fc_registration_master')
                ->where('user_id', $loginName)
                ->orderByDesc('pk')
                ->first();
            if ($row) {
                return $row;
            }
        }

        $mobile = trim((string) ($credential->mobile_no ?? ''));
        if ($mobile !== '') {
            return DB::table('fc_registration_master')
                ->where('contact_no', $mobile)
                ->orderByDesc('pk')
                ->first();
        }

        return null;
    }

    private function firstTwoStepsComplete(int $userCredentialsPk, ?FcForm $form): bool
    {
        if ($form) {
            return $this->dynamicFormFirstTwoStepsComplete($form, $userCredentialsPk);
        }

        return $this->legacyFirstTwoStepsComplete($userCredentialsPk);
    }

    private function dynamicFormFirstTwoStepsComplete(FcForm $form, int $userCredentialsPk): bool
    {
        /** @var Collection<int, \App\Models\FC\FcFormStep> $steps */
        $steps = $form->activeSteps()->orderBy('step_number')->take(2)->get();
        if ($steps->count() < 2) {
            return false;
        }

        $flow = app(FcRegistrationFlowService::class);
        $status = $flow->buildStepCompletionByStepId($form, $steps, $userCredentialsPk);

        return ($status[$steps[0]->id] ?? false) && ($status[$steps[1]->id] ?? false);
    }

    private function legacyFirstTwoStepsComplete(int $userCredentialsPk): bool
    {
        if (! Schema::hasTable('student_masters')) {
            return false;
        }

        $master = StudentMaster::forUser($userCredentialsPk)->first();

        return $master
            && (bool) $master->step1_done
            && (bool) $master->step2_done;
    }
}
