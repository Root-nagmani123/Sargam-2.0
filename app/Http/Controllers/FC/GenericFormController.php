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
use App\Services\FC\FcImportedProfileLockService;
use App\Services\FC\HindiTransliterationService;
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
        private FcImportedProfileLockService $importedProfileLock,
        private HindiTransliterationService $hindiTransliteration,
    ) {}

    // ── Form Dashboard — list steps for a form ───────────────────────
    public function formDashboard(FcForm $form): View
    {
        $userId = Auth::id();
        $this->programmeContext->rememberCourseForForm($form);
        session([FcRegistrationIntentService::SESSION_FORM_ID => (int) $form->id]);

        $steps    = $form->activeSteps()->withCount(['fields', 'fieldGroups'])->get();
        $stepStatus = $this->registrationFlow->buildStepCompletionByStepId($form, $steps, $userId);

        // Special Assistant is available only when the academy has set a ph_value on the
        // trainee's roster row. When absent, the step is shown disabled and — because it is
        // optional — is treated as non-blocking so later steps stay accessible.
        $gatedStepMeta = [];
        foreach ($steps as $s) {
            if ($this->specialAssistantGatedOff($s, (int) $userId)) {
                $gatedStepMeta[$s->id] = 'Not applicable for you';
            }
        }

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
            'gatedStepMeta',
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

        // Other Details / step 3: tabbed field groups (same detection as form-builder editor).
        // $groups is only needed on this branch, so it is loaded inside it — flat steps
        // (the majority) would otherwise pay an extra field-groups query for nothing.
        if ($step->usesFieldGroups()) {
            $groups         = $step->activeFieldGroups()->with('activeGroupFields')->get()->values();
            $existingRows   = [];
            $groupLookups   = [];
            $completedGroups = [];
            $disabledGroupFields = [];
            $optionalSubjects = app(\App\Services\FC\FcOptionalSubjectService::class);

            foreach ($groups as $group) {
                $rows = $this->formService->getExistingGroupRows($group, $userId);
                $existingRows[$group->group_name] = $rows;
                $completedGroups[$group->group_name] = $this->formService->groupRowsHaveMeaningfulData($group, $rows);
                $fieldsForLookups = $group->activeGroupFields->isNotEmpty()
                    ? $group->activeGroupFields
                    : $group->groupFields;
                $groupLookups[$group->group_name] = $this->formService->getGroupLookupData($fieldsForLookups);

                // IFoS trainees get both optional subjects (IFoS list); others get the CSE
                // list on the first only, with the second dropdown disabled.
                $disabledGroupFields[$group->group_name] = $optionalSubjects->applyGroupOverrides(
                    $groupLookups[$group->group_name],
                    (int) $userId
                );
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
                'disabledGroupFields',
                'allSteps',
                'prevStep',
                'nextStep'
            ))->with('districtOptions', $this->formService->getDistrictMasterOptions());
        }

        // Flat fields step
        $lookups      = $this->formService->getLookupData($fields);
        $existingData = $this->formService->getExistingDataForStep($step, $userId);
        $districtOptions = $this->formService->getDistrictMasterOptions();

        // Academy-provided identity fields (from the first Excel upload) are prefilled and locked.
        $lockedFields = $this->importedProfileLock->lockedValuesForFields($fields, $userId);

        // Suggest a Hindi (Devanagari) full name transliterated from the English name,
        // but only when the field exists and is still empty — the trainee can edit it.
        $prefillValues = $this->hindiFullNameSuggestion($fields, $lockedFields, $existingData);

        $allSteps  = $form->activeSteps;
        $stepIndex = $allSteps->search(fn($s) => $s->id === $step->id);
        $prevStep  = $stepIndex > 0 ? $allSteps[$stepIndex - 1] : null;
        $nextStep  = $stepIndex < $allSteps->count() - 1 ? $allSteps[$stepIndex + 1] : null;

        return view('forms.step-fields', [
            'form'            => $form,
            'step'            => $step,
            'fields'          => $fields,
            'lookups'         => $lookups,
            'existingData'    => $existingData,
            'districtOptions' => $districtOptions,
            'lockedFields'    => $lockedFields,
            'prefillValues'   => $prefillValues,
            'allSteps'        => $allSteps,
            'prevStep'        => $prevStep,
            'nextStep'        => $nextStep,
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
            $existingData = $this->formService->getExistingDataForStep($step, $userId);
            $this->formService->assertMultipartUploadsValid($request, $single);
            $rules = $this->formService->buildValidationRules($single, $step, $userId, $existingData);
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

            $nextStep = $this->nextApplicableStep($form, $step, $userId);

            if ($nextStep) {
                return redirect()->route('fc-reg.forms.step', [$form, $nextStep])
                    ->with('success', "{$step->step_name} completed.");
            }

            return $this->redirectAfterFinalStep($form, $userId, "{$step->step_name} saved.");
        }

        // Normalise PAN fields to uppercase before validation. The input only *looks*
        // uppercase (CSS text-transform); the posted value keeps the typed case, so a
        // lowercase entry would otherwise fail the uppercase [A-Z] PAN regex.
        foreach ($fields as $field) {
            if ($field->field_type === 'file') {
                continue;
            }
            $isPan = strtolower((string) $field->field_name) === 'pan_card'
                || str_contains(strtolower((string) $field->field_name), 'pan')
                || str_contains(strtolower((string) $field->label), 'pan');
            if ($isPan && $request->filled($field->field_name)) {
                $request->merge([
                    $field->field_name => strtoupper(trim((string) $request->input($field->field_name))),
                ]);
            }
        }

        // Force academy-provided identity fields to their imported values before
        // validating/saving, so a locked field can never be overwritten (even if the
        // read-only input is tampered with or a disabled control is not posted).
        $lockedFields = $this->importedProfileLock->lockedValuesForFields($fields, $userId);
        if ($lockedFields !== []) {
            $request->merge($lockedFields);
        }

        $validated = $this->validateFlatStepOrRedirect($request, $form, $step, $fields);
        if ($validated instanceof RedirectResponse) {
            return $validated;
        }

        if ($request->boolean('same_as_permanent')) {
            $validated['pres_address_line1'] = $validated['perm_address_line1'] ?? null;
            $validated['pres_address_line2'] = $validated['perm_address_line2'] ?? null;
            $validated['pres_country_id']    = $validated['perm_country_id'] ?? null;
            $validated['pres_state_id']      = $validated['perm_state_id'] ?? null;
            $validated['pres_district']      = $validated['perm_district'] ?? null;
            $validated['pres_city']          = $validated['perm_city'] ?? null;
            $validated['pres_city_name']     = $validated['perm_city_name'] ?? null;
            $validated['pres_pincode']       = $validated['perm_pincode'] ?? null;
            $validated['same_as_permanent']  = 1;
        }

        $this->formService->saveStepDataForStep($step, $userId, $validated, $request);

        // Navigate to the next applicable step (skipping "not applicable" ones);
        // Travel Plan closes the flow once every step is done.
        $nextStep = $this->nextApplicableStep($form, $step, $userId);

        if ($nextStep) {
            return $this->redirectToFormStep($form, $nextStep, "{$step->step_name} saved. Please complete {$nextStep->step_name}.");
        }

        return $this->redirectAfterFinalStep($form, $userId, "{$step->step_name} saved.");
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

            $nextStep = $this->nextApplicableStep($form, $step, $userId);

            if ($nextStep) {
                return $this->redirectToFormStep($form, $nextStep, "{$step->step_name} completed.");
            }

            return $this->redirectAfterFinalStep($form, $userId, "{$step->step_name} completed.");
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
    /**
     * Build a one-off Hindi transliteration suggestion for a `full_name_hindi` field,
     * used to prefill (not lock) the field when it is empty.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\FC\FcFormField>  $fields
     * @param  array<string, mixed>  $lockedFields
     * @return array<string, string>  [field_name => suggested Hindi value]
     */
    private function hindiFullNameSuggestion($fields, array $lockedFields, ?object $existingData): array
    {
        $hindiField = $fields->firstWhere('target_column', 'full_name_hindi');
        if (! $hindiField) {
            return [];
        }

        // Respect an already-entered/saved Hindi name — never overwrite it.
        if (filled(data_get($existingData, 'full_name_hindi'))) {
            return [];
        }

        // Source the English name from the locked identity values, falling back to any saved values.
        $parts = [];
        foreach (['first_name', 'middle_name', 'last_name'] as $col) {
            $val = $lockedFields[$col] ?? data_get($existingData, $col);
            if (is_string($val) && trim($val) !== '') {
                $parts[] = trim($val);
            }
        }

        $english = implode(' ', $parts);
        if ($english === '') {
            return [];
        }

        $hindi = $this->hindiTransliteration->toHindi($english);

        return $hindi !== null ? [$hindiField->field_name => $hindi] : [];
    }

    private function validateFlatStepOrRedirect(Request $request, FcForm $form, FcFormStep $step, $fields)
    {
        $userId = Auth::id();
        $existingData = $this->formService->getExistingDataForStep($step, $userId);

        $this->formService->assertMultipartUploadsValid($request, $fields);

        $rules = $this->formService->buildValidationRules($fields, $step, $userId, $existingData);
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
        $userId = Auth::id();
        $existingRows = $this->formService->getExistingGroupRows($group, $userId);

        $this->formService->assertMultipartUploadsValid($request, $groupFields, $group->group_name);

        $rules = $this->formService->buildGroupValidationRules($group, $userId, $existingRows);
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

        // Special Assistant may only be opened when the trainee has a ph_value; otherwise
        // it is not applicable and cannot be viewed or saved (matches the disabled card).
        // Carry the trainee forward to the next step they can actually fill instead of
        // dropping them back on the dashboard, which broke the step-by-step flow.
        if ($this->specialAssistantGatedOff($step, (int) $userId)) {
            $nextStep = $this->nextApplicableStep($form, $step, $userId);
            if ($nextStep) {
                return $this->redirectToFormStep($form, $nextStep, 'Special Assistant is not applicable for you.');
            }

            return $this->redirectAfterFinalStep($form, $userId, 'Special Assistant is not applicable for you.');
        }

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
            // A Special Assistant step that is gated off (no ph_value) is optional, so it
            // never blocks the steps that follow it.
            if (! ($stepStatus[$steps[$i]->id] ?? false)
                && ! $this->specialAssistantGatedOff($steps[$i], (int) $userId)) {
                return redirect()->route('fc-reg.forms.dashboard', $form)
                    ->with('error', 'Please complete the previous steps first.');
            }
        }

        return null;
    }

    /**
     * A step is the "Special Assistant" step when its name is Special Assistant /
     * Special Assistance (spelling varies across FC form templates).
     */
    private function isSpecialAssistantStep(FcFormStep $step): bool
    {
        return str_starts_with(strtolower(trim((string) $step->step_name)), 'special assist');
    }

    /**
     * The Special Assistant step is "gated off" (disabled and skippable) for a trainee
     * who has no ph_value on their fc_registration_master roster row.
     */
    private function specialAssistantGatedOff(FcFormStep $step, int $userId): bool
    {
        return $this->isSpecialAssistantStep($step)
            && ! $this->importedProfileLock->hasPhValue($userId);
    }

    /**
     * The next step the trainee can actually fill, skipping any that are gated off
     * (e.g. Special Assistant when they have no ph_value). Without this the flow
     * lands on a "not applicable" step and bounces back to the dashboard.
     */
    private function nextApplicableStep(FcForm $form, FcFormStep $step, $userId): ?FcFormStep
    {
        $allSteps  = $form->activeSteps;
        $stepIndex = $allSteps->search(fn ($s) => $s->id === $step->id);

        if ($stepIndex === false) {
            return null;
        }

        for ($i = $stepIndex + 1; $i < $allSteps->count(); $i++) {
            if (! $this->specialAssistantGatedOff($allSteps[$i], (int) $userId)) {
                return $allSteps[$i];
            }
        }

        return null;
    }

    /**
     * Travel Plan is not an admin-configurable step, but it closes the flow for any
     * form that has a Bank Details step — the same rule the dashboard card uses.
     */
    private function travelPlanPending(FcForm $form, $userId): bool
    {
        if (! $form->activeSteps->firstWhere('tracker_column', 'bank_done')) {
            return false;
        }

        if (($form->form_slug ?? '') === 'fc-registration') {
            $progress = fc_registration_progress_view($this->registrationService->getProgress($userId));

            return ! ($progress['steps']['travel'] ?? false);
        }

        $trackerTable = $form->trackerStorageTable();
        if (! Schema::hasTable($trackerTable) || ! Schema::hasColumn($trackerTable, 'travel_done')) {
            return false;
        }

        $tq = DB::table($trackerTable)->where(fc_user_col($trackerTable), fc_user_val($trackerTable, $userId));
        if (Schema::hasColumn($trackerTable, 'form_id')) {
            $tq->where('form_id', $form->id);
        }

        return ! (bool) ($tq->value('travel_done') ?? false);
    }

    /**
     * Where to go once the last applicable step is saved: on to the Travel Plan while
     * it is still pending, otherwise back to the form dashboard.
     */
    private function redirectAfterFinalStep(FcForm $form, $userId, string $message): RedirectResponse
    {
        if ($this->travelPlanPending($form, $userId)) {
            return redirect()->route('fc-reg.registration.travel')
                ->with('success', trim($message).' Please complete your Travel Plan.');
        }

        return redirect()->route('fc-reg.forms.dashboard', $form)
            ->with('success', trim($message).' All steps completed!');
    }
}
