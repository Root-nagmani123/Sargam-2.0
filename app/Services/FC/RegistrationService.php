<?php

namespace App\Services\FC;

use App\Models\FC\{
    FcForm,
    FcFormField,
    FcFormFieldGroup,
    FcFormGroupField,
    StudentMaster, StudentMasterFirst, StudentMasterSecond,
    StudentMasterQualificationDetails, NewRegistrationBankDetailsMaster,
    FcJoiningRelatedDocumentsDetailsMaster, FcJoiningRelatedDocumentsMaster,
    FcFormDocumentVerification,
    StudentConfirmMaster, StudentTravelPlanMaster,
};
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    public function __construct(
        private readonly DynamicFormService $dynamicFormService,
    ) {}

    /**
     * Return a progress array for the dashboard progress bar.
     * Mirrors the logic used in HomeController.java to determine step completion.
     */
    public function getProgress(int $userId): array
    {
        $master = StudentMaster::forUser($userId)->first();
        $step1  = StudentMasterFirst::forUser($userId)->where('step1_completed', 1)->exists();
        $step2  = StudentMasterSecond::forUser($userId)->where('step2_completed', 1)->exists();
        $step3  = $master?->step3_done ?? false;
        $bank   = NewRegistrationBankDetailsMaster::forUser($userId)->exists();
        $travel = (bool) ($master?->travel_done ?? false);
        $docs   = $this->allMandatoryDocsUploaded($userId);
        $confirmed = StudentConfirmMaster::forUser($userId)->where('declaration_accepted', 1)->exists();

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
     * Check if all mandatory documents have been uploaded.
     */
    public function allMandatoryDocsUploaded(int $userId): bool
    {
        $mandatoryIds = FcJoiningRelatedDocumentsMaster::where('is_active', 1)
            ->where('is_mandatory', 1)->pluck('id');

        if ($mandatoryIds->isEmpty()) {
            return false;
        }

        $uploadedIds = FcJoiningRelatedDocumentsDetailsMaster::forUser($userId)
            ->where('is_uploaded', 1)->pluck('document_master_id');

        return $mandatoryIds->diff($uploadedIds)->isEmpty();
    }

    /**
     * Active document masters merged with this user's upload rows (for admin / status UIs).
     * Ensures the full checklist is visible even when nothing has been uploaded yet.
     *
     * @return Collection<int, object{
     *     documentMaster: FcJoiningRelatedDocumentsMaster,
     *     document_name: string,
     *     file_path: ?string,
     *     is_uploaded: bool,
     *     is_verified: bool,
     *     remarks: ?string
     * }>
     */
    public function joiningDocumentChecklistForDisplay(int $userId): Collection
    {
        $masters = FcJoiningRelatedDocumentsMaster::where('is_active', 1)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $uploadedByMasterId = FcJoiningRelatedDocumentsDetailsMaster::forUser($userId)
            ->get()
            ->keyBy('document_master_id');

        return $masters->map(function (FcJoiningRelatedDocumentsMaster $m) use ($uploadedByMasterId) {
            $d = $uploadedByMasterId->get($m->id);

            return (object) [
                'documentMaster' => $m,
                'document_name'  => $d?->document_name ?? $m->document_name,
                'file_path'      => $d?->file_path,
                'is_uploaded'    => (bool) ($d?->is_uploaded ?? false),
                'is_verified'    => (bool) ($d?->is_verified ?? false),
                'remarks'        => $d?->remarks,
            ];
        });
    }

    /**
     * Joining document rows for dynamic forms (file fields on the form's document step).
     *
     * @return Collection<int, object{
     *     documentMaster: null,
     *     document_name: string,
     *     file_path: ?string,
     *     is_uploaded: bool,
     *     is_mandatory: bool,
     *     is_verified: bool,
     *     remarks: ?string,
     *     form_field_id: int
     * }>
     */
    public function dynamicFormDocumentsForDisplay(int $userId, FcForm $form): Collection
    {
        $docsStep = app(FcRegistrationFlowService::class)->documentsStep($form);
        if (! $docsStep) {
            return collect();
        }

        $fields = $docsStep->activeFields()
            ->where('field_type', 'file')
            ->orderBy('display_order')
            ->get();

        if ($fields->isEmpty()) {
            return collect();
        }

        $table = $docsStep->target_table ?: 'student_master_firsts';
        $row = DB::table($table)->where(fc_user_col($table), fc_user_val($table, $userId))->first();
        

        $verifications = FcFormDocumentVerification::query()
            ->where('user_id', $this->resolveStudentMasterId($userId))
            ->whereIn('form_field_id', $fields->pluck('id'))
            ->get()
            ->keyBy('form_field_id');

        return $fields->map(function (FcFormField $field) use ($row, $verifications) {
            $path = $this->readStoredFieldValue($row, $field);
            $verification = $verifications->get($field->id);

            return (object) [
                'documentMaster' => null,
                'form_field_id'  => (int) $field->id,
                'document_name'  => (string) ($field->label ?: $field->field_name),
                'file_path'      => filled($path) ? (string) $path : null,
                'is_uploaded'    => filled($path),
                'is_mandatory'   => (bool) $field->is_required,
                'is_verified'    => (bool) ($verification?->is_verified ?? false),
                'remarks'        => $verification?->remarks,
            ];
        });
    }

    /**
     * Save admin verification for a dynamic form document field.
     */
    public function saveDynamicFormDocumentVerification(
        int $userId,
        int $formFieldId,
        bool $isVerified,
        ?string $remarks
    ): string {
        $field = FcFormField::query()
            ->with('step')
            ->where('id', $formFieldId)
            ->where('field_type', 'file')
            ->where('is_active', 1)
            ->firstOrFail();

        $step = $field->step;
        $table = $field->target_table ?: $step?->target_table;
        if (! $table) {
            throw new \InvalidArgumentException('Document field storage table is not configured.');
        }

        $row = DB::table($table)->where(fc_user_col($table), fc_user_val($table, $userId))->first();
        if (! filled($this->readStoredFieldValue($row, $field))) {
            throw new \InvalidArgumentException(
                '"'.($field->label ?: $field->field_name).'" is not uploaded yet, so it cannot be verified.'
            );
        }

        

        FcFormDocumentVerification::updateOrCreate(
            [
                'user_id' => $this->resolveStudentMasterId($userId),
                'form_field_id' => $formFieldId,
            ],
            [
                'is_verified' => $isVerified,
                'verified_by_user_id' => $isVerified ? auth()->id() : null,
                'verified_at' => $isVerified ? now() : null,
                'remarks' => filled($remarks) ? $remarks : null,
            ]
        );

        return (string) ($field->label ?: $field->field_name);
    }

    /**
     * Header summary for admin student report (service, state, session, email).
     *
     * @return array{service_label:string,state_label:string,session_label:string,email:?string}
     */
    public function resolveStudentHeaderMeta(?StudentMasterFirst $step1): array
    {
        if (! $step1) {
            return [
                'service_label' => '—',
                'state_label' => '—',
                'session_label' => '—',
                'email' => null,
            ];
        }

        $serviceName = $step1->service?->service_name;
        $serviceCode = $step1->service?->service_code ?? $step1->service?->service_short_name;
        if (! $serviceName && filled($step1->service_id)) {
            $svc = DB::table('service_master')->where('pk', $step1->service_id)->first();
            $serviceName = $svc->service_name ?? null;
            $serviceCode = $serviceCode ?: ($svc->service_short_name ?? $svc->service_code ?? null);
        }

        $stateName = $step1->allottedState?->state_name;
        if (! $stateName && filled($step1->allotted_state_id)) {
            $stateName = DB::table('state_master')->where('pk', $step1->allotted_state_id)->value('state_name');
        }

        $sessionName = $step1->session?->session_name;
        if (! $sessionName && filled($step1->session_id)) {
            $sessionName = DB::table('session_masters')->where('id', $step1->session_id)->value('session_name');
        }

        $serviceLabel = $serviceName
            ? trim($serviceName.' ('.($serviceCode ?: '—').')')
            : '—';

        return [
            'service_label' => $serviceLabel,
            'state_label' => $stateName ?: '—',
            'session_label' => $sessionName ?: '—',
            'email' => filled($step1->email ?? null) ? (string) $step1->email : null,
        ];
    }

    /**
     * Resolve additional dynamic flat fields (configured in form builder) for reports/PDF.
     * Excludes legacy hardcoded columns already rendered in static report blocks.
     *
     * @return Collection<int, object{step_slug:string, step_name:string, label:string, value:string}>
     */
    public function additionalDynamicFlatFieldsForDisplay(int $userId): Collection
    {
        $form = FcForm::resolveForUserId($userId);
        if (! $form) {
            return collect();
        }

        $staticColumns = [
            'full_name', 'roll_no', 'session_id', 'service_id', 'cadre', 'allotted_state_id', 'status',
            'fathers_name', 'mothers_name', 'date_of_birth', 'gender', 'mobile_no', 'email',
            'category_id', 'religion_id', 'nationality', 'domicile_state', 'marital_status', 'blood_group',
            'height_cm', 'weight_kg', 'identification_mark1', 'identification_mark2',
            'perm_address_line1', 'perm_city', 'perm_state_id', 'perm_pincode',
            'pres_address_line1', 'pres_city', 'pres_state_id', 'pres_pincode',
            'emergency_contact_name', 'emergency_contact_relation', 'emergency_contact_mobile',
            'father_profession_id', 'father_occupation_details',
            'bank_name', 'branch_name', 'ifsc_code', 'account_no', 'account_holder_name', 'account_type',
            'bank_passbook_path',
        ];

        $steps = $form->activeSteps()->with('activeFields')->get();
        $tableRows = [];
        $lookupCache = [];
        $out = collect();

        foreach ($steps as $step) {
            if ($step->step_slug === 'documents') {
                continue;
            }

            /** @var Collection<int, FcFormField> $fields */
            $fields = $step->activeFields
                ->filter(fn (FcFormField $f) => ($f->target_column ?? '') !== '_skip')
                ->sortBy('display_order')
                ->values();

            foreach ($fields as $field) {
                $column = $field->target_column ?: $field->field_name;
                if ($column === '' || in_array($column, $staticColumns, true)) {
                    continue;
                }

                $table = $field->target_table ?: $step->target_table;
                if (! $table) {
                    continue;
                }

                if (! array_key_exists($table, $tableRows)) {
                    $tableRows[$table] = DB::table($table)->where(fc_user_col($table), fc_user_val($table, $userId))->first();
                }
                $row = $tableRows[$table];
                if (! $row || ! isset($row->{$column})) {
                    continue;
                }

                $raw = $row->{$column};
                if ($raw === null || $raw === '') {
                    continue;
                }

                $value = (string) $raw;

                if ($field->field_type === 'checkbox') {
                    $value = (bool) $raw ? 'Yes' : 'No';
                } elseif ($field->field_type === 'file') {
                    $value = basename((string) $raw);
                } elseif ($field->lookup_table && $field->lookup_value_column && $field->lookup_label_column) {
                    $lk = implode('|', [
                        $field->lookup_table,
                        $field->lookup_value_column,
                        $field->lookup_label_column,
                        (string) $raw,
                    ]);
                    if (! array_key_exists($lk, $lookupCache)) {
                        $lookupCache[$lk] = DB::table($field->lookup_table)
                            ->where($field->lookup_value_column, $raw)
                            ->value($field->lookup_label_column);
                    }
                    if (! empty($lookupCache[$lk])) {
                        $value = (string) $lookupCache[$lk];
                    }
                } else {
                    $options = $field->decoded_options;
                    if (is_array($options) && ! empty($options)) {
                        foreach ($options as $opt) {
                            if ((string) ($opt['value'] ?? '') === (string) $raw) {
                                $value = (string) ($opt['label'] ?? $raw);
                                break;
                            }
                        }
                    }
                }

                $out->push((object) [
                    'step_slug' => (string) $step->step_slug,
                    'step_name' => (string) $step->step_name,
                    'label' => (string) ($field->label ?: $field->field_name),
                    'value' => $value,
                ]);
            }
        }

        return $out->values();
    }

    /**
     * Build PDF sections dynamically from form-builder configuration for step1/step2/step3.
     * Excludes bank and document steps by design.
     *
     * @return list<array{key:string,title_en:string,title_hi:string,type:string,rows?:list<array{en:string,hi:string,value:mixed}>,columns?:list<string>,head_hi?:list<string>,body?:list<list<string>>}>
     */
    public function buildPdfSectionsFromFormDefinition(int $userId, ?FcForm $form = null): array
    {
        $form = $form ?? FcForm::resolveForUserId($userId);
        if (! $form) {
            return [];
        }

        $stepsQuery = $form->activeSteps()
            ->with(['activeFields', 'activeFieldGroups.activeGroupFields'])
            ->orderBy('step_number');

        if (($form->form_slug ?? '') === 'fc-registration') {
            $stepsQuery->whereNotIn('step_slug', ['bank', 'documents']);
        }

        $steps = $stepsQuery->get();

        $sections = [];
        $tableRows = [];
        $lookupCache = [];

        foreach ($steps as $step) {
            $flatFields = $step->activeFields
                ->filter(fn (FcFormField $f) => ($f->target_column ?? '') !== '_skip' && $f->field_type !== 'hidden')
                ->sortBy('display_order')
                ->values();

            if ($flatFields->isNotEmpty()) {
                $stepRows = [];
                foreach ($flatFields as $field) {
                    $sectionTitle = trim((string) ($field->section_heading ?? '')) ?: (string) $step->step_name;
                    $table = $field->target_table ?: $step->target_table;
                    $raw = null;
                    if ($table) {
                        if (! array_key_exists($table, $tableRows)) {
                            $tableRows[$table] = DB::table($table)->where(fc_user_col($table), fc_user_val($table, $userId))->first();
                        }
                        $raw = $this->readStoredFieldValue($tableRows[$table], $field);
                    }

                    $row = [
                        'group' => $sectionTitle,
                        'en' => (string) ($field->label ?: $field->field_name),
                        'hi' => $this->hindiLabel((string) ($field->label ?: $field->field_name)),
                        'value' => $this->formatDynamicValue($field, $raw, $lookupCache),
                    ];
                    if (($field->field_type ?? '') === 'file') {
                        $row['file_href'] = filled($raw) ? view_file_link((string) $raw) : null;
                    }
                    $stepRows[] = $row;
                }

                $stepRows = array_values(array_filter(
                    $stepRows,
                    fn (array $r) => ($r['value'] ?? '-') !== '-'
                ));

                if ($stepRows !== []) {
                    $sections[] = [
                        'key' => strtolower((string) $step->step_slug).'_flat',
                        'title_en' => (string) $step->step_name,
                        'title_hi' => $this->hindiSectionTitle((string) $step->step_slug, (string) $step->step_name),
                        'type' => 'fields',
                        'rows' => $stepRows,
                        'sort_order' => ((int) $step->step_number * 1000) + 10,
                    ];
                }
            }

            foreach ($step->activeFieldGroups as $group) {
                /** @var FcFormFieldGroup $group */
                $groupFields = $group->activeGroupFields->sortBy('display_order')->values();
                if ($groupFields->isEmpty()) {
                    continue;
                }
                $rows = $this->dynamicFormService->getExistingGroupRows($group, $userId);
                $body = [];
                foreach ($rows as $r) {
                    $line = [];
                    foreach ($groupFields as $gf) {
                        /** @var FcFormGroupField $gf */
                        $line[] = $this->formatDynamicValue($gf, $r->{$gf->target_column} ?? null, $lookupCache);
                    }
                    $body[] = $line;
                }
                if ($body === []) {
                    continue;
                }

                $sections[] = [
                    'key' => strtolower((string) $step->step_slug).'_group_'.(string) $group->id,
                    'title_en' => (string) $step->step_name.' - '.(string) $group->group_label,
                    'title_hi' => $this->hindiSectionTitle((string) $step->step_slug, (string) $step->step_name).' - '.$this->hindiLabel((string) $group->group_label),
                    'type' => 'table',
                    'columns' => $groupFields->map(fn ($f) => (string) ($f->label ?: $f->field_name))->all(),
                    'head_hi' => $groupFields->map(fn () => '')->all(),
                    'body' => $body,
                    'sort_order' => ((int) $step->step_number * 1000) + 100 + (int) $group->display_order,
                ];
            }
        }

        $travelSection = $this->buildTravelPlanPdfSection($userId);
        if ($travelSection !== null) {
            $sections[] = $travelSection;
        }

        usort($sections, fn (array $a, array $b) => ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0)));
        foreach ($sections as &$sec) {
            unset($sec['sort_order']);
        }
        unset($sec);

        return $sections;
    }

    /**
     * Admin HTML report sections (same data as PDF, includes file view links).
     *
     * @return list<array<string,mixed>>
     */
    public function buildReportSectionsFromFormDefinition(int $userId, ?FcForm $form = null): array
    {
        return $this->buildPdfSectionsFromFormDefinition($userId, $form);
    }

    /**
     * Display name for PDF header / reports (supports split name fields).
     */
    public function resolveStudentDisplayName(?StudentMasterFirst $step1): string
    {
        if (! $step1) {
            return '';
        }

        $full = trim((string) ($step1->full_name ?? ''));
        if ($full !== '') {
            return $full;
        }

        return trim(implode(' ', array_filter([
            $step1->first_name ?? null,
            $step1->middle_name ?? null,
            $step1->last_name ?? null,
        ])));
    }

    /**
     * Stored path for trainee photograph (photo_path column or form file field).
     */
    public function resolveStudentPhotoPath(?StudentMasterFirst $step1, ?FcForm $form, int $userId): ?string
    {
        $candidates = [];

        if ($step1 && filled($step1->photo_path ?? null)) {
            $candidates[] = (string) $step1->photo_path;
        }

        if ($form) {
            foreach ($form->activeSteps()->with('activeFields')->orderBy('step_number')->get() as $step) {
                $photoField = $step->activeFields->first(function (FcFormField $f) {
                    if (($f->field_type ?? '') !== 'file') {
                        return false;
                    }
                    $col = strtolower((string) ($f->target_column ?? $f->field_name ?? ''));
                    $name = strtolower((string) ($f->field_name ?? ''));

                    if ($col === 'signature_path' || str_contains($col, 'signature')) {
                        return false;
                    }

                    return $col === 'photo_path'
                        || $name === 'photo'
                        || str_contains($col, 'photograph');
                });

                if (! $photoField) {
                    continue;
                }

                $table = $photoField->target_table ?: $step->target_table;
                $column = $photoField->target_column ?: $photoField->field_name;
                if (! $table || ! $column) {
                    continue;
                }

                $row = DB::table($table)->where(fc_user_col($table), fc_user_val($table, $userId))->first();
                $stored = $row->{$column} ?? null;
                if (filled($stored)) {
                    $candidates[] = (string) $stored;
                }
            }
        }

        $uploadDir = storage_path('app/public/uploads/'.$userId);
        if (is_dir($uploadDir)) {
            $globbed = array_merge(
                glob($uploadDir.'/photo_*.*') ?: [],
                glob($uploadDir.'/photo.*') ?: []
            );
            foreach ($globbed as $full) {
                if (is_file($full)) {
                    $candidates[] = 'uploads/'.$userId.'/'.basename($full);
                }
            }
        }

        foreach (array_unique($candidates) as $path) {
            if (fc_resolve_storage_file_path($path) !== null) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array{key:string,title_en:string,title_hi:string,type:string,rows:list<array{group?:string,en:string,hi:string,value:string}>,sort_order:int}|null
     */
    public function buildTravelPlanPdfSection(int $userId): ?array
    {
        $plan = StudentTravelPlanMaster::query()
            ->where(fc_user_col('student_travel_plan_masters'), fc_user_val('student_travel_plan_masters', $userId))
            ->with('fcArrivalSlot')
            ->first();

        if (! $plan) {
            return null;
        }

        $slot = $plan->fcArrivalSlot;
        $slotLabel = $slot?->slot_label ?? '-';
        if ($slot?->time_start && $slot?->time_end) {
            $slotLabel .= ' ('.substr((string) $slot->time_start, 0, 5).'–'.substr((string) $slot->time_end, 0, 5).')';
        }

        $rows = array_values(array_filter([
            ['group' => 'Travel Plan', 'en' => 'Arrival date', 'hi' => 'आगमन तिथि', 'value' => format_date($plan->joining_date)],
            ['group' => 'Travel Plan', 'en' => 'Activity slot', 'hi' => 'गतिविधि स्लॉट', 'value' => $slotLabel],
            ['group' => 'Travel Plan', 'en' => 'Mode of journey', 'hi' => 'यात्रा का माध्यम', 'value' => (string) ($plan->mode_of_journey ?? '-')],
            ['group' => 'Travel Plan', 'en' => 'Flight / Train / Vehicle no.', 'hi' => 'फ्लाइट / ट्रेन / वाहन संख्या', 'value' => (string) ($plan->journey_vehicle_no ?? '-')],
            ['group' => 'Travel Plan', 'en' => 'Arrival time at Dehradun', 'hi' => 'देहरादून आगमन समय', 'value' => (string) ($plan->arrival_time_dehradun ?? '-')],
            ['group' => 'Travel Plan', 'en' => 'Require academy vehicle', 'hi' => 'अकादमी वाहन आवश्यक', 'value' => $plan->requiresAcademyVehicleYes() ? 'Yes / हाँ' : 'No / नहीं'],
            ['group' => 'Travel Plan', 'en' => 'Remarks', 'hi' => 'टिप्पणी', 'value' => (string) ($plan->special_requirements ?? '-')],
            ['group' => 'Travel Plan', 'en' => 'Submitted', 'hi' => 'जमा स्थिति', 'value' => $plan->is_submitted ? 'Yes / हाँ' : 'Draft / प्रारूप'],
        ], fn (array $r) => ($r['value'] ?? '-') !== '-'));

        if ($rows === []) {
            return null;
        }

        return [
            'key' => 'travel_plan',
            'title_en' => 'Travel Plan — Joining',
            'title_hi' => 'यात्रा योजना — प्रवेश',
            'type' => 'fields',
            'rows' => $rows,
            'sort_order' => 90000,
        ];
    }

    /**
     * @param  FcFormField|FcFormGroupField  $field
     * @param  array<string,mixed>  $lookupCache
     */
    private function formatDynamicValue(object $field, mixed $raw, array &$lookupCache): string
    {
        if ($raw === null || $raw === '') {
            return '-';
        }

        if ($this->fieldDisplaysAsDate($field, $raw)) {
            return format_date($raw);
        }

        if (($field->field_type ?? '') === 'checkbox') {
            return (bool) $raw ? 'Yes' : 'No';
        }

        if (($field->field_type ?? '') === 'file') {
            return basename((string) $raw);
        }

        if (! empty($field->lookup_table) && ! empty($field->lookup_value_column) && ! empty($field->lookup_label_column)) {
            $lk = implode('|', [
                (string) $field->lookup_table,
                (string) $field->lookup_value_column,
                (string) $field->lookup_label_column,
                (string) $raw,
            ]);
            if (! array_key_exists($lk, $lookupCache)) {
                $lookupCache[$lk] = $this->resolveLookupLabelSafely(
                    (string) $field->lookup_table,
                    (string) $field->lookup_value_column,
                    (string) $field->lookup_label_column,
                    $raw
                );
            }
            if (! empty($lookupCache[$lk])) {
                return (string) $lookupCache[$lk];
            }
        }

        $options = $field->decoded_options ?? [];
        if (is_array($options) && ! empty($options)) {
            foreach ($options as $opt) {
                if ((string) ($opt['value'] ?? '') === (string) $raw) {
                    return (string) ($opt['label'] ?? $raw);
                }
            }
        }

        return (string) $raw;
    }

    /**
     * @param  FcFormField|FcFormGroupField  $field
     */
    private function fieldDisplaysAsDate(object $field, mixed $raw): bool
    {
        if (($field->field_type ?? '') === 'date') {
            return true;
        }

        if ($raw instanceof \DateTimeInterface) {
            return true;
        }

        $col = strtolower((string) ($field->target_column ?? $field->field_name ?? ''));
        if (! in_array($col, [
            'date_of_birth', 'dob', 'joining_date', 'from_date', 'to_date',
            'spouse_dob', 'wedding_date', 'exam_date',
        ], true) && ! str_ends_with($col, '_date')) {
            return false;
        }

        $str = trim((string) $raw);

        return $str !== ''
            && $str !== '0000-00-00'
            && preg_match('/^\d{4}-\d{2}-\d{2}/', $str) === 1;
    }

    private function hindiSectionTitle(string $stepSlug, string $fallback): string
    {
        return match ($stepSlug) {
            'step1', '99th-step1' => 'मूल जानकारी',
            'step2', '99th-step2' => 'व्यक्तिगत विवरण',
            'step3', '99th-step3' => 'अन्य विवरण',
            '99th-bank' => 'बैंक विवरण',
            '99th-documents' => 'प्रवेश दस्तावेज़',
            '99th-health' => 'स्वास्थ्य जोखिम',
            '99th-special' => 'विशेष सहायता',
            '99th-vision' => 'दृष्टि कथन',
            default => $fallback,
        };
    }

    private function hindiLabel(string $label): string
    {
        $map = [
            'Session' => 'सत्र',
            'Service' => 'सेवा',
            'Cadre' => 'केडर',
            'Allotted State' => 'आवंटित राज्य',
            'Full Name' => 'पूरा नाम',
            "Father's Name" => 'पिता का नाम',
            "Mother's Name" => 'माता का नाम',
            'Date of Birth' => 'जन्म तिथि',
            'Gender' => 'लिंग',
            'Mobile Number' => 'मोबाइल नंबर',
            'Email Address' => 'ईमेल पता',
            'Photograph' => 'फोटो',
            'Signature' => 'हस्ताक्षर',
            'Category' => 'वर्ग',
            'Religion' => 'धर्म',
            'Domicile State' => 'अधिवास राज्य',
            'Marital Status' => 'वैवाहिक स्थिति',
            'Blood Group' => 'रक्त समूह',
            'Height (cm)' => 'ऊंचाई (सेमी)',
            'Weight (kg)' => 'वजन (किग्रा)',
            'Identification Mark 1' => 'पहचान चिह्न 1',
            'Identification Mark 2' => 'पहचान चिह्न 2',
            'Address Line 1' => 'पता पंक्ति 1',
            'Address Line 2' => 'पता पंक्ति 2',
            'City' => 'शहर',
            'State' => 'राज्य',
            'Pincode' => 'पिनकोड',
            'Country' => 'देश',
            'Same as Permanent Address' => 'स्थायी पते के समान',
            'Emergency Contact Name' => 'आपातकालीन संपर्क नाम',
            'Relation' => 'संबंध',
            'Emergency Contact Mobile' => 'आपातकालीन मोबाइल',
            "Father's Profession" => 'पिता का व्यवसाय',
            'Occupation Details' => 'कार्य विवरण',
        ];

        return $map[$label] ?? $label;
    }

    /**
     * fc_form_document_verifications.user_id has a FK to student_masters.id (not user_credentials.pk).
     * Resolve the correct student_masters.id for a given user_credentials.pk.
     */
    private function resolveStudentMasterId(int $credentialsPk): int
    {
        $id = DB::table('student_masters')->where('user_id', $credentialsPk)->value('id');

        return $id ? (int) $id : $credentialsPk;
    }

    /**
     * Read a stored column value, with fallbacks for legacy *_path column naming.
     */
    private function readStoredFieldValue(?object $row, FcFormField $field): mixed
    {
        if (! $row) {
            return null;
        }

        $column = $field->target_column ?: $field->field_name;
        $raw = $row->{$column} ?? null;
        if (filled($raw)) {
            return $raw;
        }

        if (($field->field_type ?? '') !== 'file') {
            return $raw;
        }

        if (! str_ends_with($column, '_path')) {
            $pathCol = $column.'_path';
            if (isset($row->{$pathCol}) && filled($row->{$pathCol})) {
                return $row->{$pathCol};
            }
        } else {
            $baseCol = substr($column, 0, -5);
            if ($baseCol !== '' && isset($row->{$baseCol}) && filled($row->{$baseCol})) {
                return $row->{$baseCol};
            }
        }

        return null;
    }

    private function resolveLookupLabelSafely(string $table, string $valueColumn, string $labelColumn, mixed $raw): ?string
    {
        try {
            $value = DB::table($table)->where($valueColumn, $raw)->value($labelColumn);
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        } catch (QueryException) {
            // Fall through to tolerant resolution below.
        }

        $candidateValueColumns = array_values(array_unique(array_filter([
            $valueColumn,
            $valueColumn === 'id' ? 'pk' : null,
            $valueColumn === 'pk' ? 'id' : null,
            'id',
            'pk',
        ])));

        foreach ($candidateValueColumns as $col) {
            try {
                if (! DB::getSchemaBuilder()->hasColumn($table, $col)) {
                    continue;
                }
                if (! DB::getSchemaBuilder()->hasColumn($table, $labelColumn)) {
                    continue;
                }
                $value = DB::table($table)->where($col, $raw)->value($labelColumn);
                if ($value !== null && $value !== '') {
                    return (string) $value;
                }
            } catch (QueryException) {
                continue;
            }
        }

        return null;
    }

    /**
     * Get all data for a student (used in admin report / print view).
     */
    public function getFullStudentProfile(int $userId): array
    {
        return [
            'step1'        => StudentMasterFirst::forUser($userId)->with(['session','service','allottedState'])->first(),
            'step2'        => StudentMasterSecond::forUser($userId)->with(['category','religion','permState','presState'])->first(),
            'master'       => StudentMaster::forUser($userId)->first(),
            'bank'         => NewRegistrationBankDetailsMaster::forUser($userId)->first(),
            'documents'    => FcJoiningRelatedDocumentsDetailsMaster::forUser($userId)->with('documentMaster')->get(),
            'confirmed'    => StudentConfirmMaster::forUser($userId)->first(),
            'progress'     => $this->getProgress($userId),
        ];
    }
}
