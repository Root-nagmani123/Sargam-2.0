<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use App\Models\FC\FcFormStep;
use App\Models\FC\FcFormField;
use App\Models\FC\FcFormFieldGroup;
use App\Models\FC\FcFormGroupField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DynamicFormService
{
    /**
     * Get a form by slug.
     */
    public function getForm(string $formSlug): ?FcForm
    {
        return FcForm::where('form_slug', $formSlug)->where('is_active', 1)->first();
    }

    /**
     * Get the step definition by slug.
     */
    public function getStep(string $stepSlug): ?FcFormStep
    {
        return FcFormStep::where('step_slug', $stepSlug)->where('is_active', 1)->first();
    }

    /**
     * Get active steps for a form.
     */
    public function getFormSteps(string $formSlug): Collection
    {
        $form = $this->getForm($formSlug);
        if (! $form) {
            return collect();
        }
        return $form->activeSteps;
    }

    /**
     * Get active fields for a step, ordered by display_order.
     */
    public function getStepFields(string $stepSlug): Collection
    {
        $step = $this->getStep($stepSlug);
        if (! $step) {
            return collect();
        }
        return $step->activeFields;
    }

    /**
     * Get active groups + their fields for a step (Step 3).
     */
    public function getStepGroups(string $stepSlug): Collection
    {
        $step = $this->getStep($stepSlug);
        if (! $step) {
            return collect();
        }
        return $step->activeFieldGroups()->with('activeGroupFields')->get();
    }

    /**
     * Build Laravel validation rules from field definitions.
     */
    public function buildValidationRules(Collection $fields): array
    {
        $rules = [];
        foreach ($fields as $field) {
            if ($field->validation_rules) {
                $rules[$field->field_name] = $field->validation_rules;
            } elseif ($field->is_required) {
                $rules[$field->field_name] = 'required';
            } else {
                $rules[$field->field_name] = 'nullable';
            }
        }
        return $rules;
    }

    /**
     * Build validation rules for a repeatable group's rows.
     */
    public function buildGroupValidationRules(FcFormFieldGroup $group): array
    {
        $rules  = [];
        $prefix = $group->group_name;

        if ($group->min_rows > 0) {
            $rules[$prefix] = "required|array|min:{$group->min_rows}";
        } else {
            $rules[$prefix] = 'nullable|array';
        }

        $groupFields = $group->activeGroupFields->isNotEmpty()
            ? $group->activeGroupFields
            : $group->groupFields;

        foreach ($groupFields as $field) {
            $key = "{$prefix}.*.{$field->field_name}";
            if ($field->validation_rules) {
                $rules[$key] = $this->normalizeLanguageMasterExistsInRules($field->validation_rules, $field);
            } elseif ($field->is_required) {
                $rules[$key] = 'required';
            } else {
                $rules[$key] = 'nullable';
            }
        }

        return $rules;
    }

    /**
     * Get lookup data for all select fields that use lookup_table.
     */
    public function getLookupData(Collection $fields): array
    {
        $lookups = [];
        foreach ($fields as $field) {
            if ($field->lookup_table) {
                $query = DB::table($field->lookup_table);
                if ($field->lookup_order_column) {
                    $query->orderBy($field->lookup_order_column);
                }
                $lookups[$field->field_name] = $query->get();
            }
        }
        return $lookups;
    }

    /**
     * Get lookup data for group fields.
     */
    public function getGroupLookupData(Collection $groupFields): array
    {
        $lookups = [];
        foreach ($groupFields as $field) {
            $table = $field->lookup_table;
            if ($table === 'language_masters') {
                $table = 'language_master';
            }

            if ($field->field_type === 'select' && $field->target_column === 'language_id') {
                $lookups[$field->field_name] = $this->languageMasterLookupRows($field);
                continue;
            }

            if (! $table) {
                continue;
            }

            $query = DB::table($table);

            if (property_exists($field, 'lookup_order_column') && $field->lookup_order_column) {
                $query->orderBy($field->lookup_order_column);
            } elseif (property_exists($field, 'lookup_label_column') && $field->lookup_label_column) {
                $query->orderBy($field->lookup_label_column);
            } elseif ($table === 'language_master') {
                $query->orderBy('language_name');
            }

            $lookups[$field->field_name] = $query->get();
        }
        return $lookups;
    }

    /**
     * Rows for language dropdown (language_master). Supports id or legacy pk as the primary key column.
     *
     * @return Collection<int, object>
     */
    private function languageMasterLookupRows(FcFormGroupField $field): Collection
    {
        $pkCol   = $this->languageMasterPrimaryKeyColumn();
        $wantCol = $field->lookup_value_column ?? $pkCol;
        $query   = DB::table('language_master');

        if ($wantCol !== $pkCol) {
            $query->selectRaw("`{$pkCol}` as `{$wantCol}`, language_name");
        } else {
            $query->select($pkCol, 'language_name');
        }
        $query->orderBy('language_name');

        return $query->get();
    }

    private function languageMasterPrimaryKeyColumn(): string
    {
        if (! Schema::hasTable('language_master')) {
            return 'id';
        }
        $cols = Schema::getColumnListing('language_master');
        // Legacy Sargam uses pk; some DBs have both id and pk — prefer pk when present so values match seeded rows.
        if (in_array('pk', $cols, true)) {
            return 'pk';
        }
        if (in_array('id', $cols, true)) {
            return 'id';
        }

        return 'id';
    }

    /**
     * Align exists:language_master(s),... with the real key column (legacy tables use pk, FC-only tables use id).
     */
    private function normalizeLanguageMasterExistsInRules(string $rules, FcFormGroupField $field): string
    {
        if ($field->target_column !== 'language_id' || ! preg_match('/exists:\s*language_masters?\s*,/i', $rules)) {
            return $rules;
        }
        $col = $this->languageMasterPrimaryKeyColumn();

        return preg_replace('/exists:\s*language_masters?\s*,\s*\w+/i', 'exists:language_master,' . $col, $rules);
    }

    /**
     * Fragment for manual validation (e.g. legacy Step 3 language tab).
     */
    public function languageMasterExistsRule(): string
    {
        $col = $this->languageMasterPrimaryKeyColumn();

        return 'exists:language_master,' . $col;
    }

    /**
     * Get existing data for a step from its target table.
     */
    public function getExistingData(string $stepSlug, string $username): ?object
    {
        $step = $this->getStep($stepSlug);
        if (! $step) {
            return null;
        }
        return DB::table($step->target_table)->where('username', $username)->first();
    }

    /**
     * Get existing rows for a repeatable group.
     */
    public function getExistingGroupRows(FcFormFieldGroup $group, string $username): Collection
    {
        return collect(DB::table($group->target_table)->where('username', $username)->get());
    }

    /**
     * Route validated data to correct target tables and save.
     * Handles file uploads as well.
     */
    public function saveStepData(string $stepSlug, string $username, array $validatedData, $request = null): void
    {
        $step   = $this->getStep($stepSlug);
        $fields = $step->activeFields;

        // Group fields by target_table
        $tableData = [];
        foreach ($fields as $field) {
            // Skip fields with _skip target (like confirm fields)
            if ($field->target_column === '_skip') {
                continue;
            }

            $targetTable = $field->target_table ?: $step->target_table;

            if ($field->field_type === 'file') {
                // Handle file upload
                if ($request && $request->hasFile($field->field_name)) {
                    $file = $request->file($field->field_name);
                    $subfolder = $stepSlug === 'bank' ? 'bank' : '';
                    $dir  = "uploads/{$username}" . ($subfolder ? "/{$subfolder}" : '');
                    $path = $file->storeAs(
                        $dir,
                        $field->field_name . '_' . time() . '.' . $file->extension(),
                        'public'
                    );
                    $tableData[$targetTable][$field->target_column] = $path;
                }
            } else {
                if (array_key_exists($field->field_name, $validatedData)) {
                    $value = $validatedData[$field->field_name];
                    if ($field->field_type === 'checkbox') {
                        $value = $value ? 1 : 0;
                    }
                    $tableData[$targetTable][$field->target_column] = $value;
                }
            }
        }

        // Save to each target table
        DB::transaction(function () use ($tableData, $username, $step) {
            foreach ($tableData as $table => $data) {
                $data['username'] = $username;

                // Set completion column if this is the primary target table
                if ($table === $step->target_table && $step->completion_column) {
                    $data[$step->completion_column] = 1;
                }

                DB::table($table)->updateOrInsert(
                    ['username' => $username],
                    array_merge($data, ['updated_at' => now()])
                );

                // Ensure the row actually exists (for new users the updateOrInsert above handles it,
                // but we need to set created_at for new rows)
                $existing = DB::table($table)->where('username', $username)->first();
                if ($existing && ! $existing->created_at) {
                    DB::table($table)->where('username', $username)->update(['created_at' => now()]);
                }
            }

            // Update the consolidated tracker
            if ($step->tracker_column) {
                $trackerData = [$step->tracker_column => 1, 'updated_at' => now()];

                // For step1, also sync summary fields to student_masters
                if ($step->step_slug === 'step1') {
                    $step1Data = DB::table('student_master_firsts')->where('username', $username)->first();
                    if ($step1Data) {
                        $trackerData['full_name']    = $step1Data->full_name ?? null;
                        $trackerData['session_id']   = $step1Data->session_id ?? null;
                        $trackerData['cadre']        = $step1Data->cadre ?? null;
                        $serviceCode = DB::table('service_master')->where('pk', $step1Data->service_id ?? 0)->value('service_short_name');
                        $trackerData['service_code'] = $serviceCode;
                    }
                }

                DB::table('student_masters')->updateOrInsert(
                    ['username' => $username],
                    $trackerData
                );
            }
        });
    }

    /**
     * Save repeatable group data (delete-and-recreate or upsert).
     */
    public function saveGroupData(FcFormFieldGroup $group, string $username, array $rows): void
    {
        $fields = $group->activeGroupFields;

        DB::transaction(function () use ($group, $username, $rows, $fields) {
            if ($group->save_mode === 'replace_all') {
                DB::table($group->target_table)->where('username', $username)->delete();

                foreach ($rows as $row) {
                    $data = ['username' => $username, 'created_at' => now(), 'updated_at' => now()];
                    foreach ($fields as $field) {
                        $value = $row[$field->field_name] ?? null;
                        // Convert checkbox values
                        if ($field->field_type === 'checkbox') {
                            $value = $value ? 1 : 0;
                        }
                        $data[$field->target_column] = $value;
                    }
                    DB::table($group->target_table)->insert($data);
                }
            } else {
                // upsert mode (single-row tables like spouse, hobbies)
                $data = ['username' => $username, 'updated_at' => now()];
                $row  = $rows[0] ?? [];
                foreach ($fields as $field) {
                    $value = $row[$field->field_name] ?? null;
                    if ($field->field_type === 'checkbox') {
                        $value = $value ? 1 : 0;
                    }
                    $data[$field->target_column] = $value;
                }
                DB::table($group->target_table)->updateOrInsert(
                    ['username' => $username],
                    $data
                );
                $existing = DB::table($group->target_table)->where('username', $username)->first();
                if ($existing && ! $existing->created_at) {
                    DB::table($group->target_table)->where('username', $username)->update(['created_at' => now()]);
                }
            }
        });
    }
}
