<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcForm;
use App\Models\FC\FcFormStep;
use App\Models\FC\FcFormFieldGroup;
use App\Models\FC\StudentMaster;
use App\Services\FC\DynamicFormService;
use App\Services\FC\FcProgrammeContextService;
use App\Services\FC\FcRegistrationFlowService;
use App\Services\FC\FcRegistrationIntentService;
use App\Services\FC\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class GenericFormController extends Controller
{
    public function __construct(
        private DynamicFormService $formService,
        private RegistrationService $registrationService,
        private FcProgrammeContextService $programmeContext,
        private FcRegistrationFlowService $registrationFlow,
    ) {}

    // ── Form Dashboard — list steps for a form ───────────────────────
    public function formDashboard(FcForm $form): View
    {
        $username = Auth::user()->username;
        $this->programmeContext->rememberCourseForForm($form);
        session([FcRegistrationIntentService::SESSION_FORM_ID => (int) $form->id]);

        $steps    = $form->activeSteps()->withCount(['fields', 'fieldGroups'])->get();
        $stepStatus = $this->registrationFlow->buildStepCompletionByStepId($form, $steps, $username);

        $registrationProgress = null;
        $fcRegistrationMeta = null;
        if ($form->form_slug === 'fc-registration') {
            $registrationProgress = fc_registration_progress_view(
                $this->registrationService->getProgress($username)
            );
            $fcRegistrationMeta = [
                'master_status' => StudentMaster::where('username', $username)->value('status'),
            ];
        }

        // Travel Plan: read travel_done from the tracker table for this form + user
        $travelDone = false;
        $trackerTable = $form->trackerStorageTable();
        if (Schema::hasTable($trackerTable) && Schema::hasColumn($trackerTable, 'travel_done')) {
            $tq = DB::table($trackerTable)->where($form->user_identifier ?: 'username', $username);
            if (Schema::hasColumn($trackerTable, 'form_id')) {
                $tq->where('form_id', $form->id);
            }
            $travelDone = (bool) ($tq->value('travel_done') ?? false);
        }

        return view('forms.dashboard', compact(
            'form',
            'steps',
            'stepStatus',
            'registrationProgress',
            'fcRegistrationMeta',
            'travelDone'
        ));
    }

    // ── Show a step ──────────────────────────────────────────────────
    public function showStep(FcForm $form, FcFormStep $step): View|RedirectResponse
    {
        if ($step->form_id !== $form->id) {
            abort(404);
        }

        $username = Auth::user()->username;
        $guard = $this->guardSequentialFormAccess($form, $step, $username);
        if ($guard) {
            return $guard;
        }

        // Legacy FC registration only: separate 14-document checklist. Other forms use dynamic file fields on the step.
        if ($this->registrationFlow->usesLegacyDocumentChecklist($form)
            && ($step->step_slug ?? '') === 'documents') {
            return redirect()->route('fc-reg.registration.documents');
        }

        $fields   = $step->activeFields;
        $groups   = $step->activeFieldGroups()->with('activeGroupFields')->get();

        // If step has groups (like step3 pattern), use groups view
        if ($groups->isNotEmpty()) {
            $existingRows   = [];
            $groupLookups   = [];
            $completedGroups = [];

            foreach ($groups as $group) {
                $rows = $this->formService->getExistingGroupRows($group, $username);
                $existingRows[$group->group_name] = $rows;
                $completedGroups[$group->group_name] = $rows->isNotEmpty();
                $fieldsForLookups = $group->activeGroupFields->isNotEmpty()
                    ? $group->activeGroupFields
                    : $group->groupFields;
                $groupLookups[$group->group_name] = $this->formService->getGroupLookupData($fieldsForLookups);
            }

            $allSteps = $form->activeSteps;
            $stepIndex = $allSteps->search(fn($s) => $s->id === $step->id);
            $prevStep = $stepIndex > 0 ? $allSteps[$stepIndex - 1] : null;
            $nextStep = $stepIndex < $allSteps->count() - 1 ? $allSteps[$stepIndex + 1] : null;

            // FC registration "Other details": dedicated step-3 layout (step indicator + same group tabs as builder).
            if (($form->form_slug ?? '') === 'fc-registration' && ($step->step_slug ?? '') === 'step3') {
                return view('fc.registration.dynamic-step3', compact(
                    'form',
                    'step',
                    'groups',
                    'existingRows',
                    'groupLookups',
                    'completedGroups',
                    'allSteps',
                    'prevStep',
                    'nextStep'
                ));
            }

            return view('forms.step-groups', compact(
                'form', 'step', 'groups', 'existingRows', 'groupLookups', 'completedGroups',
                'allSteps', 'prevStep', 'nextStep'
            ));
        }

        // Flat fields step
        $lookups      = $this->formService->getLookupData($fields);
        $existingData = $this->formService->getExistingData($step->step_slug, $username);

        $allSteps  = $form->activeSteps;
        $stepIndex = $allSteps->search(fn($s) => $s->id === $step->id);
        $prevStep  = $stepIndex > 0 ? $allSteps[$stepIndex - 1] : null;
        $nextStep  = $stepIndex < $allSteps->count() - 1 ? $allSteps[$stepIndex + 1] : null;

        return view('forms.step-fields', [
            'form'         => $form,
            'step'         => $step,
            'fields'       => $fields,
            'lookups'      => $lookups,
            'existingData' => $existingData,
            'allSteps'     => $allSteps,
            'prevStep'     => $prevStep,
            'nextStep'     => $nextStep,
        ]);
    }

    // ── Save flat fields step ────────────────────────────────────────
    public function saveStep(Request $request, FcForm $form, FcFormStep $step): RedirectResponse
    {
        if ($step->form_id !== $form->id) {
            abort(404);
        }

        $username = Auth::user()->username;
        $guard = $this->guardSequentialFormAccess($form, $step, $username);
        if ($guard) {
            return $guard;
        }

        if ($this->registrationFlow->usesLegacyDocumentChecklist($form)
            && ($step->step_slug ?? '') === 'documents') {
            return redirect()->route('fc-reg.registration.documents');
        }

        $fields = $step->activeFields;

        if ($request->filled('upload_single')) {
            $fieldName = (string) $request->input('upload_single');
            $field = $fields->firstWhere('field_name', $fieldName);
            if (! $field || $field->field_type !== 'file') {
                return back()->with('error', 'Invalid document field.');
            }

            $rules = $this->formService->buildValidationRules(collect([$field]));
            $request->validate($rules);
            $this->formService->saveSingleFileField($step, $field, $username, $request);

            return redirect()->route('fc-reg.forms.step', [$form, $step])
                ->with('success', $field->label.' uploaded successfully.');
        }

        $allFileFields = $fields->isNotEmpty() && $fields->every(fn ($f) => $f->field_type === 'file');

        if ($allFileFields) {
            if (! $this->formService->documentStepRequiredFilesSatisfied($step, $username)) {
                return back()->with('error', 'Please upload all mandatory documents before continuing.');
            }
            $this->formService->syncDocumentStepCompletion($step, $username);

            $allSteps  = $form->activeSteps;
            $stepIndex = $allSteps->search(fn ($s) => $s->id === $step->id);
            $nextStep  = $stepIndex < $allSteps->count() - 1 ? $allSteps[$stepIndex + 1] : null;

            if ($nextStep) {
                return redirect()->route('fc-reg.forms.step', [$form, $nextStep])
                    ->with('success', "{$step->step_name} completed.");
            }

            return redirect()->route('fc-reg.forms.dashboard', $form)
                ->with('success', "{$step->step_name} saved. All steps completed!");
        }

        $rules     = $this->formService->buildValidationRules($fields);
        $validated = $request->validate($rules);

        if ($request->boolean('same_as_permanent')) {
            $validated['pres_address_line1'] = $validated['perm_address_line1'] ?? null;
            $validated['pres_address_line2'] = $validated['perm_address_line2'] ?? null;
            $validated['pres_city']          = $validated['perm_city'] ?? null;
            $validated['pres_state_id']      = $validated['perm_state_id'] ?? null;
            $validated['pres_pincode']       = $validated['perm_pincode'] ?? null;
            $validated['pres_country_id']    = $validated['perm_country_id'] ?? null;
            $validated['same_as_permanent']  = 1;
        }

        $this->formService->saveStepDataForStep($step, $username, $validated, $request);

        // Navigate to next step or back to dashboard
        $allSteps  = $form->activeSteps;
        $stepIndex = $allSteps->search(fn($s) => $s->id === $step->id);
        $nextStep  = $stepIndex < $allSteps->count() - 1 ? $allSteps[$stepIndex + 1] : null;

        if ($nextStep) {
            return redirect()->route('fc-reg.forms.step', [$form, $nextStep])
                ->with('success', "{$step->step_name} saved. Please complete {$nextStep->step_name}.");
        }

        return redirect()->route('fc-reg.forms.dashboard', $form)
            ->with('success', "{$step->step_name} saved. All steps completed!");
    }

    // ── Save group data ──────────────────────────────────────────────
    public function saveGroup(Request $request, FcForm $form, FcFormFieldGroup $group): RedirectResponse
    {
        $step = $group->step;
        if ($step->form_id !== $form->id) {
            abort(404);
        }

        $username = Auth::user()->username;
        $guard = $this->guardSequentialFormAccess($form, $step, $username);
        if ($guard) {
            return $guard;
        }

        $rules     = $this->formService->buildGroupValidationRules($group);
        $validated = $request->validate($rules);

        $rows = $validated[$group->group_name] ?? [];

        if ($group->save_mode === 'upsert' && !isset($rows[0])) {
            $rows = [$validated[$group->group_name] ?? $validated];
        }

        $this->formService->saveGroupData($group, $username, $rows, $request);

        // Check if last group — mark step done and move to next step
        $allGroups = $step->activeFieldGroups()->orderBy('display_order')->get();
        $lastGroup = $allGroups->last();

        if ($group->id === $lastGroup->id) {
            // Mark step as complete
            if ($step->tracker_column) {
                $trackerTable = $form->trackerStorageTable();
                if (Schema::hasTable($trackerTable) && Schema::hasColumn($trackerTable, $form->user_identifier)) {
                    $trackerKey  = [$form->user_identifier => $username];
                    $trackerData = [$step->tracker_column => 1, 'updated_at' => now()];
                    if (Schema::hasColumn($trackerTable, 'form_id')) {
                        $trackerKey['form_id']  = $form->id;
                        $trackerData['form_id'] = $form->id;
                    }
                    DB::table($trackerTable)->updateOrInsert($trackerKey, $trackerData);
                }
            }

            $allSteps  = $form->activeSteps;
            $stepIndex = $allSteps->search(fn($s) => $s->id === $step->id);
            $nextStep  = $stepIndex < $allSteps->count() - 1 ? $allSteps[$stepIndex + 1] : null;

            if ($nextStep) {
                return redirect()->route('fc-reg.forms.step', [$form, $nextStep])
                    ->with('success', "{$step->step_name} completed.");
            }

            return redirect()->route('fc-reg.forms.dashboard', $form)
                ->with('success', 'All steps completed!');
        }

        return back()->with('success', "{$group->group_label} saved.");
    }

    private function guardSequentialFormAccess(FcForm $form, FcFormStep $step, string $username): ?RedirectResponse
    {
        $steps = $form->activeSteps;
        $stepStatus = $this->registrationFlow->buildStepCompletionByStepId($form, $steps, $username);
        $isDone = $stepStatus[$step->id] ?? false;

        if ($form->form_slug === 'fc-registration') {
            if ($isDone) {
                return null;
            }
            $progress = fc_registration_progress_view($this->registrationService->getProgress($username));
            if (! fc_registration_dynamic_form_step_accessible($step->step_slug, $progress['steps'], false)) {
                return redirect()->route('fc-reg.forms.dashboard', $form)
                    ->with('error', 'Please complete the previous steps first.');
            }

            return null;
        }

        $idx = $steps->search(fn ($s) => $s->id === $step->id);
        if ($idx === false) {
            return redirect()->route('fc-reg.forms.dashboard', $form)
                ->with('error', 'Invalid step.');
        }
        for ($i = 0; $i < $idx; $i++) {
            if (! ($stepStatus[$steps[$i]->id] ?? false)) {
                return redirect()->route('fc-reg.forms.dashboard', $form)
                    ->with('error', 'Please complete the previous steps first.');
            }
        }

        return null;
    }
}
