<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use App\Models\FC\FcFormStep;
use App\Models\FC\StudentMaster;
use App\Models\FC\StudentTravelPlanMaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Routes trainees between legacy FC registration (/fc-reg/registration/*)
 * and per-form dynamic registration (/fc-reg/forms/{form}/*).
 */
class FcRegistrationFlowService
{
    public function activeFormFromSession(): ?FcForm
    {
        $formId = (int) session(FcRegistrationIntentService::SESSION_FORM_ID, 0);

        if ($formId < 1) {
            $userId = Auth::id();
            if ($userId && Schema::hasTable('student_masters') && Schema::hasColumn('student_masters', 'form_id')) {
                $formId = (int) (StudentMaster::where(fc_user_col('student_masters'), fc_user_val('student_masters', $userId))->value('form_id') ?? 0);
            }
        }

        if ($formId < 1) {
            return null;
        }

        return FcForm::query()->whereKey($formId)->where('is_active', true)->first();
    }

    public function rememberActiveFormInSession(FcForm $form): void
    {
        session([FcRegistrationIntentService::SESSION_FORM_ID => (int) $form->id]);
    }

    public function isBankCompleteForTravel(int $userId): bool
    {
        $form = $this->activeFormFromSession();
        if ($form) {
            $steps = $form->activeSteps()->get();
            $status = $this->buildStepCompletionByStepId($form, $steps, $userId);
            foreach ($steps as $step) {
                if (($step->tracker_column ?? '') === 'bank_done'
                    || str_contains(strtolower((string) ($step->step_slug ?? '')), 'bank')) {
                    return $status[$step->id] ?? false;
                }
            }
        }

        return (bool) StudentMaster::where(fc_user_col('student_masters'), fc_user_val('student_masters', $userId))->value('bank_done');
    }

    public function isTravelComplete(int $userId, ?FcForm $form = null): bool
    {
        $form = $form ?? $this->activeFormFromSession();

        if ($form) {
            $trackerTable = $form->trackerStorageTable();
            $userKey = fc_user_col($trackerTable);
            if (Schema::hasTable($trackerTable) && Schema::hasColumn($trackerTable, 'travel_done')) {
                $query = DB::table($trackerTable)->where($userKey, fc_user_val($trackerTable, $userId));
                if (Schema::hasColumn($trackerTable, 'form_id')) {
                    $query->where('form_id', $form->id);
                }
                if ((bool) $query->value('travel_done')) {
                    return true;
                }
            }
        }

        $plan = StudentTravelPlanMaster::where(fc_user_col('student_travel_plan_masters'), fc_user_val('student_travel_plan_masters', $userId))->first();

        return (bool) ($plan?->is_submitted)
            || (bool) StudentMaster::where(fc_user_col('student_masters'), fc_user_val('student_masters', $userId))->value('travel_done');
    }

    /**
     * @param  Collection<int, FcFormStep>  $steps
     * @return array<int, bool> keyed by fc_form_steps.id
     */
    public function buildStepCompletionByStepId(FcForm $form, Collection $steps, int $userId): array
    {
        $trackerTable = $form->trackerStorageTable();
        $masterRow = null;
        $trackerIsFormScoped = fc_schema_has_table($trackerTable)
            && fc_schema_has_column($trackerTable, 'form_id');

        if ($steps->contains(fn ($s) => filled($s->tracker_column))
            && fc_schema_has_table($trackerTable)
            && fc_schema_has_column($trackerTable, fc_user_col($trackerTable))) {
            $userKey = fc_user_col($trackerTable);
            $trackerQuery = DB::table($trackerTable)->where($userKey, fc_user_val($trackerTable, $userId));
            if ($trackerIsFormScoped) {
                $trackerQuery->where('form_id', $form->id);
            }
            $masterRow = $trackerQuery->first();
        }

        $stepStatus = [];
        foreach ($steps as $step) {
            $stepStatus[$step->id] = false;

            if (! $trackerIsFormScoped && $step->target_table) {
                $t = $step->target_table;
                $row = DB::table($t)->where(fc_user_col($t), fc_user_val($t, $userId))->first();
                if ($row && $step->completion_column && isset($row->{$step->completion_column})) {
                    $stepStatus[$step->id] = (bool) $row->{$step->completion_column};
                }
            }

            if (! $stepStatus[$step->id] && $step->tracker_column && $masterRow !== null
                && property_exists($masterRow, $step->tracker_column)) {
                $stepStatus[$step->id] = (bool) $masterRow->{$step->tracker_column};
            }
        }

        return $stepStatus;
    }

    /**
     * Step breadcrumb for the travel page when opened from a dynamic form (e.g. 99th FC).
     *
     * @return array{form: FcForm, items: list<array{label: string, url: ?string, done: bool, current: bool}>}|null
     */
    public function buildTravelStepNav(FcForm $form, int $userId): array
    {
        $steps = $form->activeSteps()->get();
        $stepStatus = $this->buildStepCompletionByStepId($form, $steps, $userId);
        $travelDone = $this->isTravelComplete($userId, $form);

        $items = [];
        $travelInserted = false;

        foreach ($steps as $si => $step) {
            $rawDone = $stepStatus[$step->id] ?? false;
            $prevAllDone = true;
            for ($pi = 0; $pi < $si; $pi++) {
                if (! ($stepStatus[$steps[$pi]->id] ?? false)) {
                    $prevAllDone = false;
                    break;
                }
            }
            $displayDone = $rawDone && ($si === 0 || $prevAllDone);

            $items[] = [
                'label' => $step->step_name,
                'url' => route('fc-reg.forms.step', [$form, $step]),
                'done' => $displayDone,
                'current' => false,
            ];

            if (($step->tracker_column ?? '') === 'bank_done' && ! $travelInserted) {
                $items[] = [
                    'label' => 'Travel Plan',
                    'url' => route('fc-reg.registration.travel'),
                    'done' => $travelDone,
                    'current' => true,
                ];
                $travelInserted = true;
            }
        }

        if (! $travelInserted) {
            $items[] = [
                'label' => 'Travel Plan',
                'url' => route('fc-reg.registration.travel'),
                'done' => $travelDone,
                'current' => true,
            ];
        }

        return ['form' => $form, 'items' => $items];
    }

    public function usesLegacyDocumentChecklist(FcForm $form): bool
    {
        return ($form->form_slug ?? '') === 'fc-registration';
    }

    public function documentsStep(FcForm $form): ?FcFormStep
    {
        return $form->activeSteps()
            ->get()
            ->first(function (FcFormStep $step) {
                if (($step->tracker_column ?? '') === 'docs_done') {
                    return true;
                }

                $slug = strtolower((string) ($step->step_slug ?? ''));

                return str_contains($slug, 'document');
            });
    }

    public function isDynamicDocumentsComplete(FcForm $form, int $userId): bool
    {
        if ($this->usesLegacyDocumentChecklist($form)) {
            return app(RegistrationService::class)->allMandatoryDocsUploaded($userId);
        }

        $trackerTable = $form->trackerStorageTable();
        $userKey = fc_user_col($trackerTable);

        if (Schema::hasTable($trackerTable) && Schema::hasColumn($trackerTable, 'docs_done')) {
            $query = DB::table($trackerTable)->where($userKey, fc_user_val($trackerTable, $userId));
            if (Schema::hasColumn($trackerTable, 'form_id')) {
                $query->where('form_id', $form->id);
            }
            if ((bool) $query->value('docs_done')) {
                return true;
            }
        }

        $docsStep = $this->documentsStep($form);
        if ($docsStep && filled($docsStep->completion_column) && filled($docsStep->target_table)) {
            $t = $docsStep->target_table;
            $row = DB::table($t)->where(fc_user_col($t), fc_user_val($t, $userId))->first();
            if ($row && ! empty($row->{$docsStep->completion_column})) {
                return true;
            }
        }

        return false;
    }

    public function redirectAfterTravelSubmit(int $userId, string $successMessage = 'Travel plan submitted.'): RedirectResponse
    {
        $form = $this->activeFormFromSession();

        if ($form) {
            if ($this->isDynamicDocumentsComplete($form, $userId)) {
                return redirect()->route('fc-reg.forms.dashboard', $form)
                    ->with('success', $successMessage);
            }

            if (! $this->usesLegacyDocumentChecklist($form)) {
                $docsStep = $this->documentsStep($form);
                if ($docsStep) {
                    return redirect()->route('fc-reg.forms.step', [$form, $docsStep])
                        ->with('success', $successMessage.' Please complete joining documents on your registration form.');
                }
            }

            return redirect()->route('fc-reg.forms.dashboard', $form)
                ->with('success', $successMessage);
        }

        if (StudentMaster::where(fc_user_col('student_masters'), fc_user_val('student_masters', $userId))->value('docs_done')) {
            return redirect()->route('fc-reg.registration.status')
                ->with('success', $successMessage);
        }

        return redirect()->route('fc-reg.registration.documents')
            ->with('success', $successMessage.' You may now upload documents.');
    }

    /**
     * @return array{backUrl: string, backLabel: string, continueUrl: ?string, continueLabel: ?string}
     */
    public function travelViewContext(int $userId): array
    {
        $form = $this->activeFormFromSession();

        if ($form) {
            $backUrl = route('fc-reg.forms.dashboard', $form);
            $backLabel = 'Back to '.$form->form_name;

            if ($this->isDynamicDocumentsComplete($form, $userId)) {
                return [
                    'backUrl' => $backUrl,
                    'backLabel' => $backLabel,
                    'continueUrl' => null,
                    'continueLabel' => null,
                ];
            }

            if (! $this->usesLegacyDocumentChecklist($form)) {
                $docsStep = $this->documentsStep($form);
                if ($docsStep) {
                    return [
                        'backUrl' => $backUrl,
                        'backLabel' => $backLabel,
                        'continueUrl' => route('fc-reg.forms.step', [$form, $docsStep]),
                        'continueLabel' => 'Continue to Joining Documents',
                    ];
                }
            }

            return [
                'backUrl' => $backUrl,
                'backLabel' => $backLabel,
                'continueUrl' => null,
                'continueLabel' => null,
            ];
        }

        return [
            'backUrl' => route('fc-reg.registration.bank'),
            'backLabel' => 'Back to Bank',
            'continueUrl' => route('fc-reg.registration.documents'),
            'continueLabel' => 'Continue to Documents',
        ];
    }
}
