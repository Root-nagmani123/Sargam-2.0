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
use App\Services\FC\FcRegistrationRegisteredSyncService;
use App\Services\FC\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
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
        $userId = Auth::id();
        $this->programmeContext->rememberCourseForForm($form);
        session([FcRegistrationIntentService::SESSION_FORM_ID => (int) $form->id]);

        $steps    = $form->activeSteps()->withCount(['fields', 'fieldGroups'])->get();
        $stepStatus = $this->registrationFlow->buildStepCompletionByStepId($form, $steps, $userId);

        $registrationProgress = null;
        $fcRegistrationMeta = null;
        if ($form->form_slug === 'fc-registration') {
            $registrationProgress = fc_registration_progress_view(
                $this->registrationService->getProgress($userId)
            );
            $fcRegistrationMeta = [
                'master_status' => StudentMaster::forUser($userId)->value('status'),
            ];
        }

        // Travel Plan: read travel_done from the tracker table for this form + user
        $travelDone = false;
        $trackerTable = $form->trackerStorageTable();
        if (Schema::hasTable($trackerTable) && Schema::hasColumn($trackerTable, 'travel_done')) {
            $tq = DB::table($trackerTable)->where(fc_user_col($trackerTable), fc_user_val($trackerTable, $userId));
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

        $userId = Auth::id();
        $guard = $this->guardSequentialFormAccess($form, $step, $userId);
        if ($guard) {
            return $guard;
        }

        // Legacy FC registration only: separate 14-document checklist. Other forms use dynamic file fields on the step.
        if ($this->registrationFlow->usesLegacyDocumentChecklist($form)
            && $step->isDocumentsStep()) {
            return redirect()->route('fc-reg.registration.documents');
        }

        $fields   = $step->activeFields;
        $groups   = $step->activeFieldGroups()->with('activeGroupFields')->get()->values();

        // Other Details / step 3: tabbed field groups (same detection as form-builder editor)
        if ($step->usesFieldGroups()) {
            $existingRows   = [];
            $groupLookups   = [];
            $completedGroups = [];

            foreach ($groups as $group) {
                $rows = $this->formService->getExistingGroupRows($group, $userId);
                $existingRows[$group->group_name] = $rows;
                $completedGroups[$group->group_name] = $this->formService->groupRowsHaveMeaningfulData($group, $rows);
                $fieldsForLookups = $group->activeGroupFields->isNotEmpty()
                    ? $group->activeGroupFields
                    : $group->groupFields;
                $groupLookups[$group->group_name] = $this->formService->getGroupLookupData($fieldsForLookups);
            }

            $allSteps = $form->activeSteps;
            $stepIndex = $allSteps->search(fn($s) => $s->id === $step->id);
            $prevStep = $stepIndex > 0 ? $allSteps[$stepIndex - 1] : null;
            $nextStep = $stepIndex < $allSteps->count() - 1 ? $allSteps[$stepIndex + 1] : null;

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

        // Flat fields step
        $lookups      = $this->formService->getLookupData($fields);
        $existingData = $this->formService->getExistingData($step->step_slug, $userId);

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

        $userId = Auth::id();
        $guard = $this->guardSequentialFormAccess($form, $step, $userId);
        if ($guard) {
            return $guard;
        }

        if ($this->registrationFlow->usesLegacyDocumentChecklist($form)
            && $step->isDocumentsStep()) {
            return redirect()->route('fc-reg.registration.documents');
        }

        $fields = $step->activeFields;

        if ($request->filled('upload_single')) {
            $fieldName = (string) $request->input('upload_single');
            $field = $fields->firstWhere('field_name', $fieldName);
            if (! $field || $field->field_type !== 'file') {
                return back()->with('error', 'Invalid document field.');
            }

            $single = collect([$field]);
            $this->formService->assertMultipartUploadsValid($request, $single);
            $rules = $this->formService->buildValidationRules($single);
            [$customMessages, $customAttributes] = $this->formService->validationMessagesAndAttributes($single);
            $validator = Validator::make($request->all(), $rules, $customMessages, $customAttributes);
            if ($validator->fails()) {
                return redirect()->route('fc-reg.forms.step', [$form, $step])
                    ->withErrors($validator)
                    ->withInput();
            }
            $this->formService->saveSingleFileField($step, $field, $userId, $request);

            return redirect()->route('fc-reg.forms.step', [$form, $step])
                ->with('success', $field->label.' uploaded successfully.');
        }

        $allFileFields = $fields->isNotEmpty() && $fields->every(fn ($f) => $f->field_type === 'file');

        if ($allFileFields) {
            if (! $this->formService->documentStepRequiredFilesSatisfied($step, $userId)) {
                return back()->with('error', 'Please upload all mandatory documents before continuing.');
            }
            $this->formService->syncDocumentStepCompletion($step, $userId);

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

        $validated = $this->validateFlatStepOrRedirect($request, $form, $step, $fields);
        if ($validated instanceof RedirectResponse) {
            return $validated;
        }

        if ($request->boolean('same_as_permanent')) {
            $validated['pres_address_line1'] = $validated['perm_address_line1'] ?? null;
            $validated['pres_address_line2'] = $validated['perm_address_line2'] ?? null;
            $validated['pres_city']          = $validated['perm_city'] ?? null;
            $validated['pres_state_id']      = $validated['perm_state_id'] ?? null;
            $validated['pres_pincode']       = $validated['perm_pincode'] ?? null;
            $validated['pres_country_id']    = $validated['perm_country_id'] ?? null;
            $validated['same_as_permanent']  = 1;
        }

        $this->formService->saveStepDataForStep($step, $userId, $validated, $request);

        // Navigate to next step or back to dashboard
        $allSteps  = $form->activeSteps;
        $stepIndex = $allSteps->search(fn($s) => $s->id === $step->id);
        $nextStep  = $stepIndex < $allSteps->count() - 1 ? $allSteps[$stepIndex + 1] : null;

        if ($nextStep) {
            return $this->redirectToFormStep($form, $nextStep, "{$step->step_name} saved. Please complete {$nextStep->step_name}.");
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

        $userId = Auth::id();
        $guard = $this->guardSequentialFormAccess($form, $step, $userId);
        if ($guard) {
            return $guard;
        }

        $groupFields = $group->activeGroupFields->isNotEmpty()
            ? $group->activeGroupFields
            : $group->groupFields;

        $validated = $this->validateGroupStepOrRedirect($request, $form, $step, $group, $groupFields);
        if ($validated instanceof RedirectResponse) {
            return $validated;
        }

        $rows = $validated[$group->group_name] ?? [];

        if ($group->save_mode === 'upsert' && !isset($rows[0])) {
            $rows = [$validated[$group->group_name] ?? $validated];
        }

        $this->formService->saveGroupData($group, $userId, $rows, $request);

        // Check if last group — mark step done and move to next step
        $allGroups = $step->activeFieldGroups()->orderBy('display_order')->get();
        $lastGroup = $allGroups->last();

        if ($group->id === $lastGroup->id) {
            // Mark step as complete
            if ($step->tracker_column) {
                $trackerTable = $form->trackerStorageTable();
                $uCol = fc_user_col($trackerTable);
                if (Schema::hasTable($trackerTable) && Schema::hasColumn($trackerTable, $uCol)) {
                    $trackerKey  = [$uCol => fc_user_val($trackerTable, $userId)];
                    $trackerData = [$step->tracker_column => 1, 'updated_at' => now()];
                    if (Schema::hasColumn($trackerTable, 'form_id')) {
                        $trackerKey['form_id']  = $form->id;
                        $trackerData['form_id'] = $form->id;
                    }
                    DB::table($trackerTable)->updateOrInsert($trackerKey, $trackerData);
                }
            }

            app(FcRegistrationRegisteredSyncService::class)->syncForCredentialsUser($userId, $form);

            $allSteps  = $form->activeSteps;
            $stepIndex = $allSteps->search(fn($s) => $s->id === $step->id);
            $nextStep  = $stepIndex < $allSteps->count() - 1 ? $allSteps[$stepIndex + 1] : null;

            if ($nextStep) {
                return $this->redirectToFormStep($form, $nextStep, "{$step->step_name} completed.");
            }

            return redirect()->route('fc-reg.forms.dashboard', $form)
                ->with('success', 'All steps completed!');
        }

        $groupIndex = $allGroups->search(fn ($g) => $g->id === $group->id);
        if ($groupIndex !== false && $groupIndex < $allGroups->count() - 1) {
            $nextGroup = $allGroups[$groupIndex + 1];

            return $this->redirectToFormStep(
                $form,
                $step,
                "{$group->group_label} saved. Continue with {$nextGroup->group_label}.",
                $nextGroup->group_name
            );
        }

        return $this->redirectToFormStep(
            $form,
            $step,
            "{$group->group_label} saved.",
            $group->group_name
        );
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    private function validateFlatStepOrRedirect(Request $request, FcForm $form, FcFormStep $step, $fields)
    {
        $this->formService->assertMultipartUploadsValid($request, $fields);

        $rules = $this->formService->buildValidationRules($fields);
        [$customMessages, $customAttributes] = $this->formService->validationMessagesAndAttributes($fields);
        $validator = Validator::make($request->all(), $rules, $customMessages, $customAttributes);

        if ($validator->fails()) {
            return redirect()->route('fc-reg.forms.step', [$form, $step])
                ->withErrors($validator)
                ->withInput();
        }

        return $validator->validated();
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    private function validateGroupStepOrRedirect(Request $request, FcForm $form, FcFormStep $step, FcFormFieldGroup $group, $groupFields)
    {
        $this->formService->assertMultipartUploadsValid($request, $groupFields, $group->group_name);

        $rules = $this->formService->buildGroupValidationRules($group);
        [$customMessages, $customAttributes] = $this->formService->validationMessagesAndAttributes($groupFields, $group->group_name);
        $validator = Validator::make($request->all(), $rules, $customMessages, $customAttributes);

        if ($validator->fails()) {
            return redirect()
                ->route('fc-reg.forms.step', [$form, $step, 'group' => $group->group_name])
                ->withErrors($validator)
                ->withInput();
        }

        return $validator->validated();
    }

    private function redirectToFormStep(
        FcForm $form,
        FcFormStep $step,
        string $message,
        ?string $groupName = null
    ): RedirectResponse {
        $params = [$form, $step];
        if ($groupName === null || $groupName === '') {
            $groups = $step->activeFieldGroups()->orderBy('display_order')->get()->values();
            $groupName = fc_form_first_group_name($groups);
        }
        if ($groupName !== null && $groupName !== '') {
            $params['group'] = $groupName;
        }

        return redirect()
            ->route('fc-reg.forms.step', $params)
            ->with('success', $message);
    }

    private function guardSequentialFormAccess(FcForm $form, FcFormStep $step, string $userId): ?RedirectResponse
    {
        $steps = $form->activeSteps;
        $stepStatus = $this->registrationFlow->buildStepCompletionByStepId($form, $steps, $userId);
        $isDone = $stepStatus[$step->id] ?? false;

        if ($form->form_slug === 'fc-registration') {
            if ($isDone) {
                return null;
            }
            $progress = fc_registration_progress_view($this->registrationService->getProgress($userId));
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
