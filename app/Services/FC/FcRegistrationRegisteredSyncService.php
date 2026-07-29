<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use App\Models\FC\StudentMaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Keeps fc_registration_master.is_registered and application_type in sync with trainee progress:
 * both set to 1 only when the first two active steps are complete (not at credential creation).
 */
class FcRegistrationRegisteredSyncService
{
    /**
     * The only fc_form_steps columns the completion + applicability checks read (G1 — never
     * SELECT *). Owned by FcStepApplicabilityService: it is the applicability_rule column that
     * has to be selected conditionally, so the guard belongs next to the code that reads it
     * rather than in a second copy here.
     *
     * @return list<string>
     */
    private function stepColumns(): array
    {
        return FcStepApplicabilityService::stepColumns();
    }

    public function syncForCredentialsUser(int $userCredentialsPk, ?FcForm $form = null): void
    {
        try {
            if (! fc_schema_has_table('fc_registration_master')
                || ! fc_schema_has_column('fc_registration_master', 'is_registered')) {
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

            // The roster row's course is the authoritative link to a form, and it works for
            // staged users too (FcForm::resolveForUserId() bails out on a negative id, which
            // is why staged trainees used to fall through to the hardcoded legacy check).
            // It is also unambiguous: resolving via student_masters.form_id picks an
            // arbitrary row for a trainee who has tracker rows under more than one form.
            $form = $form ?? $this->resolveFormFromRosterCourse($registration);

            if (! $form && ! FcRosterAuthService::isStagedUserId($userCredentialsPk)) {
                $form = FcForm::resolveForUserId($userCredentialsPk);
            }

            $wasRegistered = (int) ($registration->is_registered ?? 0) === 1;
            $isRegistered = $this->firstTwoStepsComplete($userCredentialsPk, $form);
            // No form resolved, but this deployment tracks steps per form: the legacy
            // step1_done/step2_done assumption does not hold (a form's second step may use
            // any tracker column — form 21 uses step3_done), so guessing here would write a
            // 0 over a correct 1 and silently un-register a complete trainee. Leave the row
            // untouched instead; a later sync that can resolve the form will set it.
            if (! $form && $this->tracksStepsPerForm()) {
                Log::info('fc_registration_master.is_registered sync skipped: no form resolved', [
                    'user_credentials_pk' => $userCredentialsPk,
                    'roster_pk' => $registration->pk ?? null,
                ]);

                return;
            }

            // Both rules are derived from ONE steps query + ONE tracker read (G4): the
            // strict rule needs the same step-completion map the first-two-steps rule does,
            // so evaluating them separately would duplicate every query.
            //
            // application_type always tracks the first two steps — it answers "registration
            // or exemption?", which is settled early, and hasCompletedRegistration() reads
            // it, so the exemption and front-page guards keep their existing timing no
            // matter which is_registered rule the form uses.
            //
            // is_registered follows the form's rule. Opt-in forms require every APPLICABLE
            // step (not every active step — a step that does not apply to the trainee can
            // never be completed and would lock them out of migrate-students for good).
            [$firstTwoDone, $allApplicableDone] = $this->evaluateProgress($userCredentialsPk, $form);

            $isRegistered = ($form && $form->registrationRequiresAllSteps())
                ? $allApplicableDone
                : $firstTwoDone;

            $isExemption = (int) ($registration->application_type ?? 0) === FcRosterApplicationGuardService::APPLICATION_EXEMPTION;

            $update = ['is_registered' => $isRegistered ? 1 : 0];

            if (! $isExemption && fc_schema_has_column('fc_registration_master', 'application_type')) {
                $update['application_type'] = $firstTwoDone
                    ? FcRosterApplicationGuardService::APPLICATION_REGISTRATION
                    : FcRosterApplicationGuardService::APPLICATION_NA;
            }

            DB::table('fc_registration_master')
                ->where('pk', $registration->pk)
                ->update($update);

            // A3 SMS once when trainee first becomes registered (best-effort; does not affect sync).
            if ($isRegistered && ! $wasRegistered) {
                $programmeName = trim((string) ($form?->form_name ?? ''));
                if ($programmeName === '') {
                    $programmeName = (string) config('gupshup.default_programme_name', 'Foundation Course');
                }

                app(FcNotifyService::class)->registrationSuccessful(
                    $registration->contact_no ?? null,
                    trim((string) ($registration->display_name ?? '')),
                    $programmeName,
                    trim((string) ($registration->user_id ?? '')),
                    isset($registration->pk) ? (int) $registration->pk : null,
                );
            }
        } catch (\Throwable $e) {
            Log::warning('fc_registration_master.is_registered sync failed', [
                'user_credentials_pk' => $userCredentialsPk,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The active form for the course the trainee is enrolled on
     * (fc_registration_master.course_master_pk → fc_forms.course_master_pk).
     *
     * The roster row is already loaded by the caller, so this costs a single fc_forms
     * lookup and only the columns the model needs (G1).
     */
    private function resolveFormFromRosterCourse(object $registration): ?FcForm
    {
        $coursePk = (int) ($registration->course_master_pk ?? 0);

        if ($coursePk < 1
            || ! fc_schema_has_table('fc_forms')
            || ! fc_schema_has_column('fc_forms', 'course_master_pk')) {
            return null;
        }

        return FcForm::query()
            ->where('course_master_pk', $coursePk)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Whether step completion is tracked per form (the consolidation-table layout). When it
     * is, a form MUST be resolved before is_registered can be judged; the hardcoded
     * step1_done/step2_done fallback is only valid on single-form legacy deployments.
     */
    private function tracksStepsPerForm(): bool
    {
        return fc_schema_has_table('student_masters')
            && fc_schema_has_column('student_masters', 'form_id');
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

    /**
     * Both completion rules from a single steps query and a single tracker read.
     *
     *  - first two active steps done  → drives application_type (and is_registered on
     *    forms using the default rule)
     *  - every APPLICABLE step done   → drives is_registered on opt-in forms. Applicability
     *    comes from FcStepApplicabilityService, the same rule the trainee dashboard and the
     *    admin report use, so a step that does not apply is excluded from the denominator
     *    rather than blocking completion forever.
     *
     * Only the step columns these checks read are selected (G1), and the two rules share
     * one step-completion map rather than each building their own (G4).
     *
     * @return array{0: bool, 1: bool} [firstTwoDone, allApplicableDone]
     */
    private function evaluateProgress(int $userCredentialsPk, ?FcForm $form): array
    {
        if (! $form) {
            return [$this->legacyFirstTwoStepsComplete($userCredentialsPk), false];
        }

        /** @var Collection<int, \App\Models\FC\FcFormStep> $steps */
        $steps = $form->activeSteps()
            ->orderBy('step_number')
            ->get($this->stepColumns());

        if ($steps->isEmpty()) {
            return [false, false];
        }

        // One tracker read covers every step when the tracker is form-scoped (the modern
        // consolidation-table layout), so asking about all steps instead of two is free.
        $status = app(FcRegistrationFlowService::class)
            ->buildStepCompletionByStepId($form, $steps, $userCredentialsPk);

        $firstTwoDone = $steps->count() >= 2
            && ($status[$steps[0]->id] ?? false)
            && ($status[$steps[1]->id] ?? false);

        [$done, $applicable] = app(FcStepApplicabilityService::class)
            ->progress($steps, $userCredentialsPk, $status);

        return [$firstTwoDone, $applicable > 0 && $done >= $applicable];
    }

    private function legacyFirstTwoStepsComplete(int $userCredentialsPk): bool
    {
        if (! fc_schema_has_table('student_masters')) {
            return false;
        }

        $master = StudentMaster::forUser($userCredentialsPk)->first();

        return $master
            && (bool) $master->step1_done
            && (bool) $master->step2_done;
    }
}
