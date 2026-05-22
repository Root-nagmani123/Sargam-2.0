<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use App\Models\FC\FcFormStep;
use App\Models\FC\FcFormField;
use App\Models\FC\FcFormFieldGroup;
use App\Models\FC\FcFormGroupField;
use App\Models\FC\FcPreHistory;
use App\Models\FC\StudentMasterFirst;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DynamicFormService
{
    /** @var array<string, string|null> */
    protected static array $columnTypeCache = [];

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
            if ($field->field_type === 'checkbox' && count($field->decoded_options) > 0) {
                $allowed = collect($field->decoded_options)->pluck('value')->map(fn ($v) => (string) $v)->values()->all();
                if (count($allowed) > 0) {
                    $rules[$field->field_name] = trim(implode('|', array_filter([
                        $field->is_required ? 'required' : 'nullable',
                        'array',
                        $field->is_required ? 'min:1' : null,
                    ])));
                    $rules[$field->field_name.'.*'] = ['string', Rule::in($allowed)];

                    continue;
                }
            }

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
            if ($field->field_type === 'checkbox' && count($field->decoded_options) > 0) {
                $allowed = collect($field->decoded_options)->pluck('value')->map(fn ($v) => (string) $v)->values()->all();
                if (count($allowed) > 0) {
                    $rules[$key] = trim(implode('|', array_filter([
                        $field->is_required ? 'required' : 'nullable',
                        'array',
                        $field->is_required ? 'min:1' : null,
                    ])));
                    $rules["{$prefix}.*.{$field->field_name}.*"] = ['string', Rule::in($allowed)];

                    continue;
                }
            }

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
        if ($group->target_table === 'fc_pre_history') {
            $course = $this->registrationPreMedicalCourse($username);
            $row = DB::table('fc_pre_history')
                ->where('userid', $username)
                ->where('course', $course)
                ->first();

            return $row ? collect([$row]) : collect();
        }

        return collect(DB::table($group->target_table)->where('username', $username)->get());
    }

    /**
     * Session/course label for fc_pre_history (matches RegistrationStep3Controller).
     */
    public function registrationPreMedicalCourse(string $username): string
    {
        $first = StudentMasterFirst::with('session')->where('username', $username)->first();

        return trim((string) ($first?->session?->session_name ?? ''));
    }

    /**
     * Route validated data to correct target tables and save.
     * Handles file uploads as well.
     */
    public function saveStepData(string $stepSlug, string $username, array $validatedData, $request = null, bool $markStepComplete = true): void
    {
        $step   = $this->getStep($stepSlug);
        if (! $step) {
            return;
        }

        $this->saveStepDataForStep($step, $username, $validatedData, $request, $markStepComplete);
    }

    /**
     * Upload one document field (per-row Upload button) without marking the step complete.
     */
    public function saveSingleFileField(FcFormStep $step, FcFormField $field, string $username, $request): void
    {
        if ($field->field_type !== 'file' || ! $request || ! $request->hasFile($field->field_name)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field->field_name => ['Please choose a file to upload.'],
            ]);
        }

        $targetTable = $field->target_table ?: $step->target_table;
        $path = $this->storeUploadedFieldFile($step, $field, $username, $request->file($field->field_name));

        DB::table($targetTable)->updateOrInsert(
            ['username' => $username],
            [
                $field->target_column => $path,
                'updated_at' => now(),
            ]
        );

        $existing = DB::table($targetTable)->where('username', $username)->first();
        if ($existing && empty($existing->created_at)) {
            DB::table($targetTable)->where('username', $username)->update(['created_at' => now()]);
        }
    }

    public function documentStepRequiredFilesSatisfied(FcFormStep $step, string $username): bool
    {
        $fileFields = $step->activeFields->filter(fn ($f) => $f->field_type === 'file' && $f->is_required);
        if ($fileFields->isEmpty()) {
            return true;
        }

        $row = DB::table($step->target_table)->where('username', $username)->first();
        foreach ($fileFields as $field) {
            $col = $field->target_column ?: $field->field_name;
            if (! $row || blank($row->{$col} ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark document step complete when every required file field has a stored path.
     */
    public function syncDocumentStepCompletion(FcFormStep $step, string $username): void
    {
        if (! $this->documentStepRequiredFilesSatisfied($step, $username)) {
            return;
        }

        $fileFields = $step->activeFields->filter(fn ($f) => $f->field_type === 'file');
        if ($fileFields->isEmpty()) {
            return;
        }

        $form = $step->form;
        $data = ['updated_at' => now()];
        if ($step->completion_column) {
            $data[$step->completion_column] = 1;
        }

        DB::table($step->target_table)->updateOrInsert(['username' => $username], $data);

        if ($step->tracker_column && $form) {
            $trackerTable = $form->trackerStorageTable();
            $userKey = $form->user_identifier ?: 'username';
            $trackerKey = [$userKey => $username];
            $trackerData = [$step->tracker_column => 1, 'updated_at' => now()];
            if (Schema::hasColumn($trackerTable, 'form_id')) {
                $trackerKey['form_id'] = $form->id;
                $trackerData['form_id'] = $form->id;
            }
            DB::table($trackerTable)->updateOrInsert($trackerKey, $trackerData);
        }
    }

    public function saveStepDataForStep(FcFormStep $step, string $username, array $validatedData, $request = null, bool $markStepComplete = true): void
    {
        $fields = $step->activeFields;

        foreach ($fields as $field) {
            if ($field->field_type === 'checkbox' && count($field->decoded_options) > 0) {
                $validatedData[$field->field_name] = $validatedData[$field->field_name] ?? [];
            }
        }

        // Group fields by target_table
        $tableData = [];
        foreach ($fields as $field) {
            // Skip fields with _skip target (like confirm fields)
            if ($field->target_column === '_skip') {
                continue;
            }

            $targetTable = $field->target_table ?: $step->target_table;

            if ($field->field_type === 'file') {
                if ($request && $request->hasFile($field->field_name)) {
                    $tableData[$targetTable][$field->target_column] = $this->storeUploadedFieldFile(
                        $step,
                        $field,
                        $username,
                        $request->file($field->field_name)
                    );
                }
            } elseif ($field->field_type === 'checkbox' && count($field->decoded_options) > 0) {
                $arr = $validatedData[$field->field_name] ?? [];
                $arr = is_array($arr) ? $arr : [];
                $tableData[$targetTable][$field->target_column] = json_encode(array_values($arr), JSON_UNESCAPED_UNICODE);
            } else {
                if (array_key_exists($field->field_name, $validatedData)) {
                    $value = $validatedData[$field->field_name];
                    if ($field->field_type === 'checkbox') {
                        $value = $value ? 1 : 0;
                    } elseif ($field->field_type === 'number' && $value !== null && $value !== '') {
                        $value = fc_numeric_display_value($value);
                    }

                    $value = $this->normalizeValueForColumn(
                        $targetTable,
                        $field->target_column,
                        $value,
                        $field
                    );

                    if ($field->target_column === 'adjustment_required'
                        && ! in_array($field->field_type, ['radio', 'select', 'checkbox'], true)
                        && is_string($validatedData[$field->field_name] ?? null)
                        && strlen(trim((string) $validatedData[$field->field_name])) > 10
                        && empty($tableData[$targetTable]['adjustment_type'] ?? null)) {
                        $tableData[$targetTable]['adjustment_type'] = trim((string) $validatedData[$field->field_name]);
                    }

                    $tableData[$targetTable][$field->target_column] = $value;
                }
            }
        }

        // Save to each target table
        DB::transaction(function () use ($tableData, $username, $step, $markStepComplete) {
            $form = $step->form;

            foreach ($tableData as $table => $data) {
                $data['username'] = $username;

                if ($markStepComplete && $table === $step->target_table && $step->completion_column) {
                    $data[$step->completion_column] = 1;
                }

                DB::table($table)->updateOrInsert(
                    ['username' => $username],
                    array_merge($data, ['updated_at' => now()])
                );

                $existing = DB::table($table)->where('username', $username)->first();
                if ($existing && empty($existing->created_at)) {
                    DB::table($table)->where('username', $username)->update(['created_at' => now()]);
                }
            }

            if ($markStepComplete && $step->tracker_column) {
                $trackerData = [$step->tracker_column => 1, 'updated_at' => now()];

                if ($step->step_slug === 'step1' || str_contains((string) $step->step_slug, 'step1')) {
                    $step1Data = DB::table('student_master_firsts')->where('username', $username)->first();
                    if ($step1Data) {
                        $trackerData['full_name']    = $step1Data->full_name ?? null;
                        $trackerData['session_id']   = $step1Data->session_id ?? null;
                        $trackerData['cadre']        = $step1Data->cadre ?? null;
                        $serviceCode = DB::table('service_master')->where('pk', $step1Data->service_id ?? 0)->value('service_short_name');
                        $trackerData['service_code'] = $serviceCode;
                    }
                }

                $trackerTable = $form->trackerStorageTable();
                $userKey = $form->user_identifier ?: 'username';
                $trackerKey = [$userKey => $username];
                if (Schema::hasColumn($trackerTable, 'form_id')) {
                    $trackerKey['form_id'] = $form->id;
                    $trackerData['form_id'] = $form->id;
                }
                DB::table($trackerTable)->updateOrInsert(
                    $trackerKey,
                    $trackerData
                );
            }
        });

        if ($markStepComplete) {
            $fileFields = $fields->filter(fn ($f) => $f->field_type === 'file');
            if ($fileFields->count() === $fields->count() && $fileFields->isNotEmpty()) {
                $this->syncDocumentStepCompletion($step, $username);
            }
        }
    }

    private function storeUploadedFieldFile(FcFormStep $step, FcFormField $field, string $username, $file): string
    {
        $slug = (string) $step->step_slug;
        $subfolder = match (true) {
            str_contains($slug, 'document') => 'documents',
            $slug === 'bank' || str_contains($slug, 'bank') => 'bank',
            default => '',
        };
        $dir = 'uploads/'.$username.($subfolder !== '' ? '/'.$subfolder : '');

        return $file->storeAs(
            $dir,
            $field->field_name.'_'.time().'.'.$file->extension(),
            'public'
        );
    }

    /**
     * Save repeatable group data (delete-and-recreate or upsert).
     */
    public function saveGroupData(FcFormFieldGroup $group, string $username, array $rows, ?Request $request = null): void
    {
        $fields = $group->activeGroupFields->isNotEmpty()
            ? $group->activeGroupFields
            : $group->groupFields;

        if ($group->target_table === 'fc_pre_history') {
            $this->saveFcPreHistoryGroup($group, $username, $rows, $fields, $request);

            return;
        }

        foreach ($rows as $i => $_row) {
            foreach ($fields as $field) {
                if ($field->field_type === 'checkbox' && count($field->decoded_options) > 0) {
                    $rows[$i][$field->field_name] = $rows[$i][$field->field_name] ?? [];
                }
            }
        }

        DB::transaction(function () use ($group, $username, $rows, $fields) {
            if ($group->save_mode === 'replace_all') {
                DB::table($group->target_table)->where('username', $username)->delete();

                foreach ($rows as $row) {
                    $data = ['username' => $username, 'created_at' => now(), 'updated_at' => now()];
                    foreach ($fields as $field) {
                        $value = $row[$field->field_name] ?? null;
                        $data[$field->target_column] = $this->normalizeGroupFieldStoredValue($field, $value);
                    }
                    DB::table($group->target_table)->insert($data);
                }
            } else {
                // upsert mode (single-row tables like spouse, hobbies)
                $data = ['username' => $username, 'updated_at' => now()];
                $row  = $rows[0] ?? [];
                foreach ($fields as $field) {
                    $value = $row[$field->field_name] ?? null;
                    $data[$field->target_column] = $this->normalizeGroupFieldStoredValue($field, $value);
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

    /**
     * @param  mixed  $value
     * @return mixed
     */
    private function normalizeGroupFieldStoredValue(FcFormGroupField $field, $value)
    {
        if ($field->field_type === 'checkbox') {
            $opts = $field->decoded_options ?? [];
            if (count($opts) > 0) {
                $arr = is_array($value) ? $value : [];

                return json_encode(array_values($arr), JSON_UNESCAPED_UNICODE);
            }

            return $value ? 1 : 0;
        }
        if ($field->field_type === 'number' && $value !== null && $value !== '') {
            return fc_numeric_display_value($value);
        }

        return $value;
    }

    /**
     * fc_pre_history uses (userid, course) not username alone; optional file upload.
     *
     * @param  \Illuminate\Support\Collection<int, FcFormGroupField>  $fields
     */
    private function saveFcPreHistoryGroup(
        FcFormFieldGroup $group,
        string $username,
        array $rows,
        Collection $fields,
        ?Request $request
    ): void {
        $course = $this->registrationPreMedicalCourse($username);
        $row = $rows[0] ?? [];

        foreach ($fields as $field) {
            if ($field->field_type === 'checkbox' && count($field->decoded_options) > 0) {
                $row[$field->field_name] = $row[$field->field_name] ?? [];
            }
        }

        $existing = FcPreHistory::where('userid', $username)->where('course', $course)->first();

        $payload = [
            'userid' => $username,
            'course' => $course,
            'status' => 1,
        ];

        foreach ($fields as $field) {
            if ($field->field_type === 'file') {
                continue;
            }
            $value = $row[$field->field_name] ?? null;
            if ($field->field_type === 'checkbox') {
                $opts = $field->decoded_options ?? [];
                if (count($opts) > 0) {
                    $arr = is_array($value) ? $value : [];
                    $value = json_encode(array_values($arr), JSON_UNESCAPED_UNICODE);
                } else {
                    $value = $value ? 1 : 0;
                }
            } elseif ($field->field_type === 'number' && $value !== null && $value !== '') {
                $value = fc_numeric_display_value($value);
            }
            $payload[$field->target_column] = $value;
        }

        $docPath = $existing?->doc_path;
        $fileField = $fields->first(fn ($f) => $f->field_type === 'file');
        if ($request && $fileField) {
            $fileKey = $group->group_name.'.0.'.$fileField->field_name;
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                if ($file->isValid()) {
                    $stored = $file->storeAs(
                        'fc/pre_history',
                        $username.'_'.uniqid('', true).'.'.$file->getClientOriginalExtension(),
                        'public'
                    );
                    $docPath = 'storage/'.$stored;
                }
            }
        }
        if ($fileField) {
            $payload[$fileField->target_column] = $docPath;
        }

        FcPreHistory::updateOrCreate(
            ['userid' => $username, 'course' => $course],
            $payload
        );
    }

    /**
     * Coerce submitted values to match DB column types (e.g. tinyint yes/no flags).
     */
    protected function normalizeValueForColumn(
        string $table,
        string $column,
        mixed $value,
        FcFormField $field
    ): mixed {
        if ($value === null || $value === '') {
            return $value;
        }

        $type = $this->getColumnType($table, $column);
        if ($type === null) {
            return $value;
        }

        if (in_array($type, ['tinyint', 'boolean', 'bool', 'smallint', 'integer', 'int', 'bigint'], true)) {
            return $this->coerceToBoolInt($value) ? 1 : 0;
        }

        return $value;
    }

    protected function getColumnType(string $table, string $column): ?string
    {
        $key = $table.'.'.$column;
        if (! array_key_exists($key, self::$columnTypeCache)) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                self::$columnTypeCache[$key] = null;
            } else {
                self::$columnTypeCache[$key] = $this->fetchMysqlColumnType($table, $column);
            }
        }

        return self::$columnTypeCache[$key];
    }

    /**
     * Read column data type without Doctrine DBAL (Schema::getColumnType requires it).
     */
    protected function fetchMysqlColumnType(string $table, string $column): ?string
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table) || ! preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return null;
        }

        $row = DB::selectOne(
            'SELECT DATA_TYPE AS data_type
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1',
            [$table, $column]
        );

        if (! $row || empty($row->data_type)) {
            return null;
        }

        return strtolower((string) $row->data_type);
    }

    protected function coerceToBoolInt(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value !== 0;
        }

        $s = strtolower(trim((string) $value));

        if (in_array($s, ['1', 'yes', 'y', 'true', 'on'], true)) {
            return true;
        }
        if (in_array($s, ['0', 'no', 'n', 'false', 'off', 'none', 'na', 'n/a'], true)) {
            return false;
        }
        if (str_starts_with($s, 'no')) {
            return false;
        }
        if (str_starts_with($s, 'yes')) {
            return true;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $filtered ?? false;
    }
}
