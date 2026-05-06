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
    StudentConfirmMaster, JbpUser
};
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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
    public function joiningDocumentChecklistForDisplay(string $username): Collection
    {
        $masters = FcJoiningRelatedDocumentsMaster::where('is_active', 1)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $uploadedByMasterId = FcJoiningRelatedDocumentsDetailsMaster::where('username', $username)
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
     * Resolve additional dynamic flat fields (configured in form builder) for reports/PDF.
     * Excludes legacy hardcoded columns already rendered in static report blocks.
     *
     * @return Collection<int, object{step_slug:string, step_name:string, label:string, value:string}>
     */
    public function additionalDynamicFlatFieldsForDisplay(string $username): Collection
    {
        $form = FcForm::activeRegistrationDynamicForm();
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
                    $tableRows[$table] = DB::table($table)->where('username', $username)->first();
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
    public function buildPdfSectionsFromFormDefinition(string $username): array
    {
        $form = FcForm::activeRegistrationDynamicForm();
        if (! $form) {
            return [];
        }

        $steps = $form->activeSteps()
            ->whereNotIn('step_slug', ['bank', 'documents'])
            ->with(['activeFields', 'activeFieldGroups.activeGroupFields'])
            ->orderBy('step_number')
            ->get();

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
                    $column = $field->target_column ?: $field->field_name;
                    $raw = null;
                    if ($table && $column) {
                        if (! array_key_exists($table, $tableRows)) {
                            $tableRows[$table] = DB::table($table)->where('username', $username)->first();
                        }
                        $row = $tableRows[$table];
                        $raw = $row->{$column} ?? null;
                    }

                    $stepRows[] = [
                        'group' => $sectionTitle,
                        'en' => (string) ($field->label ?: $field->field_name),
                        'hi' => $this->hindiLabel((string) ($field->label ?: $field->field_name)),
                        'value' => $this->formatDynamicValue($field, $raw, $lookupCache),
                    ];
                }

                $sections[] = [
                    'key' => strtolower((string) $step->step_slug).'_flat',
                    'title_en' => (string) $step->step_name,
                    'title_hi' => $this->hindiSectionTitle((string) $step->step_slug, (string) $step->step_name),
                    'type' => 'fields',
                    'rows' => $stepRows,
                    'sort_order' => ((int) $step->step_number * 1000) + 10,
                ];
            }

            foreach ($step->activeFieldGroups as $group) {
                /** @var FcFormFieldGroup $group */
                $groupFields = $group->activeGroupFields->sortBy('display_order')->values();
                if ($groupFields->isEmpty()) {
                    continue;
                }
                $rows = DB::table($group->target_table)->where('username', $username)->get();
                $body = [];
                foreach ($rows as $r) {
                    $line = [];
                    foreach ($groupFields as $gf) {
                        /** @var FcFormGroupField $gf */
                        $line[] = $this->formatDynamicValue($gf, $r->{$gf->target_column} ?? null, $lookupCache);
                    }
                    $body[] = $line;
                }
                if (empty($body)) {
                    $body[] = array_fill(0, $groupFields->count(), '-');
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

        usort($sections, fn (array $a, array $b) => ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0)));
        foreach ($sections as &$sec) {
            unset($sec['sort_order']);
        }
        unset($sec);

        return $sections;
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

    private function hindiSectionTitle(string $stepSlug, string $fallback): string
    {
        return match ($stepSlug) {
            'step1' => 'मूल जानकारी',
            'step2' => 'व्यक्तिगत विवरण',
            'step3' => 'अन्य विवरण',
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
