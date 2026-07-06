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
use Illuminate\Validation\ValidationException;

class DynamicFormService
{
    /**
     * Tables with at most one row per user (unique user_id). Must use upsert, not replace_all inserts.
     *
     * @var list<string>
     */
    private const SINGLE_ROW_PER_USER_TABLES = [
        'student_master_hobbies_details',
        'student_knowledge_hindi_masters',
        'student_master_spouse_masters',
        'student_cloth_size_master_details',
        'student_master_seconds',
    ];

    /** @var array<string, string|null> */
    protected static array $columnTypeCache = [];

    /** @var array<string, string> Per-table user-column name cache (migration-safe). */
    protected static array $userColCache = [];

    /**
     * Resolve the user-identifier column name for a given table.
     * Delegates to the global fc_user_col() helper.
     */
    private function userCol(string $table): string
    {
        return fc_user_col($table);
    }

    /**
     * Resolve the correct user-identifier VALUE for a given table.
     * Post-migration returns $userId (int); pre-migration resolves the
     * username string via fc_user_val() (looks up user_credentials.user_name).
     */
    private function userVal(string $table, int $userId): string|int
    {
        return fc_user_val($table, $userId);
    }

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
     * When $step and $userId are provided, file fields with an existing upload are optional on re-submit.
     */
    public function buildValidationRules(
        Collection $fields,
        ?FcFormStep $step = null,
        ?int $userId = null,
        ?object $existingData = null
    ): array {
        if ($existingData === null && $step !== null && $userId !== null) {
            $existingData = $this->getExistingData($step->step_slug, $userId);
        }

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

            $rules[$field->field_name] = $this->resolveFieldValidationRules($field, $existingData);
        }

        return $rules;
    }

    /**
     * Detect PHP upload errors and post_max_size truncation before Laravel validation runs.
     *
     * @param  string|null  $nestedPrefix  Group name when validating repeatable rows (e.g. pre_medical_history).
     */
    public function assertMultipartUploadsValid(Request $request, Collection $fields, ?string $nestedPrefix = null): void
    {
        $fileFields = $fields->where('field_type', 'file');
        if ($fileFields->isEmpty()) {
            return;
        }

        $messages = [];
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        $postMaxBytes = fc_ini_size_to_bytes((string) ini_get('post_max_size'));

        if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            $msg = 'The upload is too large for the server (limit '.($postMaxBytes >= 1048576 ? round($postMaxBytes / 1048576).' MB' : round($postMaxBytes / 1024).' KB').'). Reduce the file size and try again.';
            foreach ($fileFields as $field) {
                $messages[$this->validationErrorKey($field->field_name, $nestedPrefix)] = $msg;
            }
            throw ValidationException::withMessages($messages);
        }

        if ($this->multipartTextPayloadLost($request, $fields, $nestedPrefix)) {
            $maxLabel = $this->fileUploadMaxLabel($fileFields->first());
            $msg = "Your form data was lost because the file exceeds the allowed size ({$maxLabel}). Please use a smaller file and submit again.";
            foreach ($fileFields as $field) {
                $messages[$this->validationErrorKey($field->field_name, $nestedPrefix)] = $msg;
            }
            throw ValidationException::withMessages($messages);
        }

        foreach ($fileFields as $field) {
            $errorKey = $this->validationErrorKey($field->field_name, $nestedPrefix);
            $uploaded = $this->resolveUploadedFile($request, $field->field_name, $nestedPrefix);

            if ($uploaded) {
                $phpError = $uploaded->getError();
                if ($phpError !== UPLOAD_ERR_OK) {
                    $messages[$errorKey] = $this->uploadErrorMessage($phpError, $field);
                }

                continue;
            }

            $phpError = $this->rawUploadErrorCode($request, $field->field_name, $nestedPrefix);
            if ($phpError !== null && $phpError !== UPLOAD_ERR_NO_FILE) {
                $messages[$errorKey] = $this->uploadErrorMessage($phpError, $field);
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * Custom attribute names and messages for flat / group field validation.
     *
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    public function validationMessagesAndAttributes(Collection $fields, ?string $nestedPrefix = null): array
    {
        $attributes = [];
        $messages = [];

        foreach ($fields as $field) {
            $key = $this->validationErrorKey($field->field_name, $nestedPrefix);
            $attributes[$key] = $field->label;

            if ($field->field_type === 'file') {
                $maxLabel = $this->fileUploadMaxLabel($field);
                $messages[$key.'.max'] = "{$field->label} must not be larger than {$maxLabel}.";
                $messages[$key.'.uploaded'] = "{$field->label} could not be uploaded. It may exceed {$maxLabel}.";
                $messages[$key.'.mimes'] = "{$field->label} must be a ".str_replace('|', ', ', (string) ($field->file_extensions ?? 'allowed file')).'.';
            }

            $col = strtolower((string) ($field->target_column ?? $field->field_name ?? ''));
            if ($field->field_type === 'date' && in_array($col, ['date_of_birth', 'dob'], true)) {
                $messages[$key.'.before_or_equal'] = "{$field->label} must be at least 15 years ago.";
            }
        }

        return [$messages, $attributes];
    }

    /**
     * Build validation rules for a repeatable group's rows.
     */
    public function buildGroupValidationRules(
        FcFormFieldGroup $group,
        ?int $userId = null,
        ?Collection $existingRows = null
    ): array {
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

        if ($existingRows === null && $userId !== null) {
            $existingRows = $this->getExistingGroupRows($group, $userId);
        }

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

            $existingPath = null;
            if ($field->field_type === 'file' && $existingRows && $existingRows->isNotEmpty()) {
                $col = $field->target_column ?: $field->field_name;
                $existingPath = $existingRows->first()->{$col} ?? null;
            }

            $rules[$key] = $this->resolveFieldValidationRules($field, null, $existingPath);
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
            if (! $field->lookup_table) {
                continue;
            }

            $table = $this->normalizeLookupTable($field->lookup_table);
            if (! Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table);

            if ($table === 'state_master' && Schema::hasColumn($table, 'country_master_pk')) {
                $query->select('*');
            }

            if ($field->lookup_order_column) {
                $query->orderBy($field->lookup_order_column);
            } elseif ($field->lookup_label_column) {
                $query->orderBy($field->lookup_label_column);
            }

            $lookups[$field->field_name] = $query->get();
        }

        return $lookups;
    }

    /**
     * District options from state_district_mapping for cascading address fields.
     *
     * @return Collection<int, object>
     */
    public function getDistrictMasterOptions(): Collection
    {
        if (! Schema::hasTable('state_district_mapping')) {
            return collect();
        }

        $query = DB::table('state_district_mapping')
            ->select('pk', 'district_name', 'state_master_pk', 'country_master_pk');

        if (Schema::hasColumn('state_district_mapping', 'active_inactive')) {
            $query->where('active_inactive', 1);
        }

        return $query->orderBy('district_name')->get();
    }

    public function getExistingDataForStep(FcFormStep $step, int $userId): ?object
    {
        return $this->getExistingData($step->step_slug, $userId);
    }

    /**
     * Get lookup data for group fields.
     */
    public function getGroupLookupData(Collection $groupFields): array
    {
        $lookups = [];
        foreach ($groupFields as $field) {
            $table = $this->normalizeLookupTable((string) ($field->lookup_table ?? ''));

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
    public function getExistingData(string $stepSlug, int $userId): ?object
    {
        $step = $this->getStep($stepSlug);
        if (! $step) {
            return null;
        }

        // A flat step can write to several tables (e.g. Descriptive Roll →
        // student_master_firsts + student_master_seconds + knowledge_hindi).
        // Merge every table its fields target so ALL values prefill on
        // review/edit, not just the step's primary target_table.
        $fields = $step->activeFields;

        $tables = [];
        if (filled($step->target_table)) {
            $tables[$step->target_table] = true;
        }
        foreach ($fields as $f) {
            $tbl = $f->target_table ?: $step->target_table;
            if (filled($tbl)) {
                $tables[$tbl] = true;
            }
        }

        $rowsByTable = [];
        foreach (array_keys($tables) as $tbl) {
            if (! Schema::hasTable($tbl)) {
                continue;
            }
            $uVal = $this->userVal($tbl, $userId);
            if ($uVal === '' || $uVal === null) {
                continue;
            }
            $row = DB::table($tbl)->where($this->userCol($tbl), $uVal)->first();
            if ($row) {
                $rowsByTable[$tbl] = $row;
            }
        }

        if ($rowsByTable === []) {
            return null;
        }

        // Base on the primary target-table row (keeps completion flags etc.).
        $merged = (object) ((array) ($rowsByTable[$step->target_table] ?? []));

        // Each field pulls from its own table — correct even if two tables
        // share a column name.
        foreach ($fields as $f) {
            $col = $f->target_column;
            if (! $col || $col === '_skip') {
                continue;
            }
            $tbl = $f->target_table ?: $step->target_table;
            $row = $rowsByTable[$tbl] ?? null;
            if ($row !== null) {
                $arr = (array) $row;
                if (array_key_exists($col, $arr)) {
                    $merged->{$col} = $arr[$col];
                }
            }
        }

        return $merged;
    }

    /**
     * Get existing rows for a repeatable group.
     */
    public function getExistingGroupRows(FcFormFieldGroup $group, int $userId): Collection
    {
        if ($group->target_table === 'fc_pre_history') {
            $course = $this->registrationPreMedicalCourse($userId);
            $courseMasterPk = app(\App\Services\FC\FcActivityStudentResolver::class)->courseMasterPkFromName($course) ?: null;
            $col = $this->userCol('fc_pre_history');
            $q = DB::table('fc_pre_history')
                ->where($col, $this->userVal('fc_pre_history', $userId));
            if ($courseMasterPk) {
                $q->where('course_master_pk', $courseMasterPk);
            }
            $row = $q->first();

            return $row ? collect([$row]) : collect();
        }

        $t = $group->target_table;
        $uCol = $this->userCol($t);
        $uVal = $this->userVal($t, $userId);
        if ($uVal === '' || $uVal === null) {
            return collect();
        }

        return collect(DB::table($t)->where($uCol, $uVal)->get());
    }

    /**
     * Whether a group tab should show the green "completed" state (at least one stored field value).
     *
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     */
    public function groupRowsHaveMeaningfulData(FcFormFieldGroup $group, Collection $rows): bool
    {
        if ($rows->isEmpty()) {
            return false;
        }

        $fields = $group->activeGroupFields->isNotEmpty()
            ? $group->activeGroupFields
            : $group->groupFields;

        foreach ($rows as $row) {
            foreach ($fields as $field) {
                $col = $field->target_column;
                if (! $col || $col === '_skip') {
                    continue;
                }
                if ($this->isMeaningfulStoredValue($row->{$col} ?? null, $field)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Session/course label for fc_pre_history (matches RegistrationStep3Controller).
     */
    public function registrationPreMedicalCourse(int $userId): string
    {
        $first = StudentMasterFirst::with('session')->where(fc_user_col('student_master_firsts'), fc_user_val('student_master_firsts', $userId))->first();

        $sessionName = trim((string) ($first?->session?->session_name ?? ''));
        if ($sessionName !== '') {
            return $sessionName;
        }

        // Fallback: resolve course name from course_master via the roster record.
        // This handles cases where student_master_firsts.session_id is null (e.g. pre-migration
        // staged-login users who belong to a dynamic-form course set on fc_registration_master).
        $rosterPk = $userId < 0 ? abs($userId) : null;
        if ($rosterPk === null && \Illuminate\Support\Facades\Schema::hasTable('fc_registration_master')) {
            // Positive userId post-migration: find roster via user_credentials.user_name
            $userName = \Illuminate\Support\Facades\DB::table('user_credentials')
                ->where('pk', $userId)
                ->value('user_name');
            if ($userName) {
                $rosterPk = (int) (\Illuminate\Support\Facades\DB::table('fc_registration_master')
                    ->where('user_id', $userName)
                    ->value('pk') ?? 0);
            }
        }

        if ($rosterPk && \Illuminate\Support\Facades\Schema::hasTable('fc_registration_master')) {
            $courseMasterPk = \Illuminate\Support\Facades\DB::table('fc_registration_master')
                ->where('pk', $rosterPk)
                ->value('course_master_pk');
            if ($courseMasterPk && \Illuminate\Support\Facades\Schema::hasTable('course_master')) {
                $courseName = trim((string) (\Illuminate\Support\Facades\DB::table('course_master')
                    ->where('pk', $courseMasterPk)
                    ->value('course_name') ?? ''));
                if ($courseName !== '') {
                    return $courseName;
                }
            }
        }

        return '';
    }

    /**
     * Route validated data to correct target tables and save.
     * Handles file uploads as well.
     */
    public function saveStepData(string $stepSlug, int $userId, array $validatedData, $request = null, bool $markStepComplete = true): void
    {
        $step   = $this->getStep($stepSlug);
        if (! $step) {
            return;
        }

        $this->saveStepDataForStep($step, $userId, $validatedData, $request, $markStepComplete);
    }

    /**
     * Upload one document field (per-row Upload button) without marking the step complete.
     */
    public function saveSingleFileField(FcFormStep $step, FcFormField $field, int $userId, $request): void
    {
        if ($field->field_type !== 'file' || ! $request || ! $request->hasFile($field->field_name)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field->field_name => ['Please choose a file to upload.'],
            ]);
        }

        $targetTable = $field->target_table ?: $step->target_table;
        $path = $this->storeUploadedFieldFile($step, $field, $userId, $request->file($field->field_name));
        $uCol = $this->userCol($targetTable);
        $uVal = $this->userVal($targetTable, $userId);

        DB::table($targetTable)->updateOrInsert(
            [$uCol => $uVal],
            [
                $field->target_column => $path,
                'updated_at' => now(),
            ]
        );

        $existing = DB::table($targetTable)->where($uCol, $uVal)->first();
        if ($existing && empty($existing->created_at)) {
            DB::table($targetTable)->where($uCol, $uVal)->update(['created_at' => now()]);
        }
    }

    public function documentStepRequiredFilesSatisfied(FcFormStep $step, int $userId): bool
    {
        $fileFields = $step->activeFields->filter(fn ($f) => $f->field_type === 'file' && $f->is_required);
        if ($fileFields->isEmpty()) {
            return true;
        }

        $t = $step->target_table;
        $row = DB::table($t)->where($this->userCol($t), $this->userVal($t, $userId))->first();
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
    public function syncDocumentStepCompletion(FcFormStep $step, int $userId): void
    {
        if (! $this->documentStepRequiredFilesSatisfied($step, $userId)) {
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

        $t = $step->target_table;
        DB::table($t)->updateOrInsert([$this->userCol($t) => $this->userVal($t, $userId)], $data);

        if ($step->tracker_column && $form) {
            $trackerTable = $form->trackerStorageTable();
            $userKey = fc_user_col($trackerTable);
            $trackerKey = [$userKey => fc_user_val($trackerTable, $userId)];
            $trackerData = [$step->tracker_column => 1, 'updated_at' => now()];
            if (Schema::hasColumn($trackerTable, 'form_id')) {
                $trackerKey['form_id'] = $form->id;
                $trackerData['form_id'] = $form->id;
            }
            DB::table($trackerTable)->updateOrInsert($trackerKey, $trackerData);
        }

        app(FcRegistrationRegisteredSyncService::class)->syncForCredentialsUser($userId, $form);
    }

    public function saveStepDataForStep(FcFormStep $step, int $userId, array $validatedData, $request = null, bool $markStepComplete = true): void
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
                        $userId,
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
        DB::transaction(function () use ($tableData, $userId, $step, $markStepComplete) {
            $form = $step->form;

            foreach ($tableData as $table => $data) {
                $uCol = $this->userCol($table);
                $uVal = $this->userVal($table, $userId);
                $data[$uCol] = $uVal;

                $existing = DB::table($table)->where($uCol, $uVal)->first();
                if ($existing) {
                    foreach ($data as $col => $val) {
                        if (in_array($col, [$uCol, 'updated_at', 'created_at'], true)) {
                            continue;
                        }
                        if (($val === null || $val === '') && filled($existing->{$col} ?? null)) {
                            unset($data[$col]);
                        }
                    }
                }

                if ($markStepComplete && $table === $step->target_table && $step->completion_column) {
                    $data[$step->completion_column] = 1;
                }

                DB::table($table)->updateOrInsert(
                    [$uCol => $uVal],
                    array_merge($data, ['updated_at' => now()])
                );

                $existing = DB::table($table)->where($uCol, $uVal)->first();
                if ($existing && empty($existing->created_at)) {
                    DB::table($table)->where($uCol, $uVal)->update(['created_at' => now()]);
                }
            }

            if ($markStepComplete && $step->tracker_column) {
                $trackerData = [$step->tracker_column => 1, 'updated_at' => now()];

                if ($step->step_slug === 'step1' || str_contains((string) $step->step_slug, 'step1')) {
                    $step1Data = DB::table('student_master_firsts')->where(fc_user_col('student_master_firsts'), fc_user_val('student_master_firsts', $userId))->first();
                    if ($step1Data) {
                        $trackerData['full_name']    = $step1Data->full_name ?? null;
                        $trackerData['session_id']   = $step1Data->session_id ?? null;
                        $trackerData['cadre']        = $step1Data->cadre ?? null;
                        $serviceCode = DB::table('service_master')->where('pk', $step1Data->service_id ?? 0)->value('service_short_name');
                        $trackerData['service_code'] = $serviceCode;
                    }
                }

                $trackerTable = $form->trackerStorageTable();
                $userKey = fc_user_col($trackerTable);
                $trackerKey = [$userKey => fc_user_val($trackerTable, $userId)];
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
                $this->syncDocumentStepCompletion($step, $userId);
            }

            app(FcRegistrationRegisteredSyncService::class)->syncForCredentialsUser($userId, $step->form);
        }
    }

    private function storeUploadedFieldFile(FcFormStep $step, FcFormField $field, int $userId, $file): string
    {
        $slug = (string) $step->step_slug;
        $subfolder = match (true) {
            str_contains($slug, 'document') => 'documents',
            $slug === 'bank' || str_contains($slug, 'bank') => 'bank',
            default => '',
        };
        $dir = 'uploads/'.fc_upload_path_segment($userId).($subfolder !== '' ? '/'.$subfolder : '');

        return $file->storeAs(
            $dir,
            $field->field_name.'_'.time().'.'.$file->extension(),
            'public'
        );
    }

    /**
     * Save repeatable group data (delete-and-recreate or upsert).
     */
    public function saveGroupData(FcFormFieldGroup $group, int $userId, array $rows, ?Request $request = null): void
    {
        $fields = $group->activeGroupFields->isNotEmpty()
            ? $group->activeGroupFields
            : $group->groupFields;

        if ($group->target_table === 'fc_pre_history') {
            $this->saveFcPreHistoryGroup($group, $userId, $rows, $fields, $request);

            return;
        }

        foreach ($rows as $i => $_row) {
            foreach ($fields as $field) {
                if ($field->field_type === 'checkbox' && count($field->decoded_options) > 0) {
                    $rows[$i][$field->field_name] = $rows[$i][$field->field_name] ?? [];
                }
            }
        }

        DB::transaction(function () use ($group, $userId, $rows, $fields) {
            $gt = $group->target_table;
            $uCol = $this->userCol($gt);
            $uVal = $this->userVal($gt, $userId);
            $useUpsert = $group->save_mode === 'upsert'
                || in_array($gt, self::SINGLE_ROW_PER_USER_TABLES, true);

            if (! $useUpsert) {
                DB::table($gt)->where($uCol, $uVal)->delete();

                foreach ($rows as $row) {
                    $data = [$uCol => $uVal, 'created_at' => now(), 'updated_at' => now()];
                    foreach ($fields as $field) {
                        $value = $row[$field->field_name] ?? null;
                        $data[$field->target_column] = $this->normalizeGroupFieldStoredValue($field, $value);
                    }
                    DB::table($gt)->insert($data);
                }
            } else {
                // upsert mode (single-row tables like spouse, hobbies, dress sizes)
                $row = count($rows) > 1
                    ? $this->collapseGroupRowsForUpsert($rows, $fields)
                    : ($rows[0] ?? []);
                $data = [$uCol => $uVal, 'updated_at' => now()];
                $hasMeaningful = false;

                foreach ($fields as $field) {
                    $value = $row[$field->field_name] ?? null;
                    $stored = $this->normalizeGroupFieldStoredValue($field, $value);
                    $data[$field->target_column] = $stored;
                    if ($this->isMeaningfulStoredValue($stored, $field)) {
                        $hasMeaningful = true;
                    }
                }

                if (! $hasMeaningful) {
                    DB::table($gt)->where($uCol, $uVal)->delete();

                    return;
                }

                DB::table($gt)->updateOrInsert([$uCol => $uVal], $data);
                $existing = DB::table($gt)->where($uCol, $uVal)->first();
                if ($existing && ! $existing->created_at) {
                    DB::table($gt)->where($uCol, $uVal)->update(['created_at' => now()]);
                }
            }
        });
    }

    /**
     * When a single-row table receives multiple repeatable rows, merge values per field.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function collapseGroupRowsForUpsert(array $rows, Collection $fields): array
    {
        $merged = [];

        foreach ($fields as $field) {
            $parts = [];
            foreach ($rows as $row) {
                $value = $row[$field->field_name] ?? null;
                $stored = $this->normalizeGroupFieldStoredValue($field, $value);
                if ($this->isMeaningfulStoredValue($stored, $field)) {
                    $parts[] = is_array($stored) ? implode(', ', $stored) : (string) $stored;
                }
            }

            if ($parts !== []) {
                $merged[$field->field_name] = count($parts) === 1 ? $parts[0] : implode(', ', $parts);
            }
        }

        return $merged;
    }

    /**
     * @param  mixed  $value
     */
    private function validationErrorKey(string $fieldName, ?string $nestedPrefix): string
    {
        if ($nestedPrefix) {
            return "{$nestedPrefix}.0.{$fieldName}";
        }

        return $fieldName;
    }

    private function multipartTextPayloadLost(Request $request, Collection $fields, ?string $nestedPrefix): bool
    {
        $nonFileFields = $fields->where('field_type', '!=', 'file');
        if ($nonFileFields->isEmpty()) {
            return false;
        }

        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        if ($contentLength < 1024) {
            return false;
        }

        $hasAnyFileAttempt = false;
        foreach ($fields->where('field_type', 'file') as $field) {
            if ($this->rawUploadErrorCode($request, $field->field_name, $nestedPrefix) !== null) {
                $hasAnyFileAttempt = true;
                break;
            }
        }

        if (! $hasAnyFileAttempt) {
            return false;
        }

        foreach ($nonFileFields as $field) {
            if ($nestedPrefix) {
                $rows = $request->input($nestedPrefix);
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        if (is_array($row) && filled($row[$field->field_name] ?? null)) {
                            return false;
                        }
                    }
                }
            } elseif ($request->filled($field->field_name)) {
                return false;
            }
        }

        return true;
    }

    private function resolveUploadedFile(Request $request, string $fieldName, ?string $nestedPrefix): ?\Illuminate\Http\UploadedFile
    {
        if ($nestedPrefix) {
            $rows = $request->file($nestedPrefix);
            if (! is_array($rows)) {
                return null;
            }
            $first = $rows[0] ?? null;

            return is_array($first) ? ($first[$fieldName] ?? null) : null;
        }

        $file = $request->file($fieldName);

        return $file instanceof \Illuminate\Http\UploadedFile ? $file : null;
    }

    private function rawUploadErrorCode(Request $request, string $fieldName, ?string $nestedPrefix): ?int
    {
        if ($nestedPrefix) {
            $files = $_FILES[$nestedPrefix] ?? null;
            if (! is_array($files) || ! isset($files['error'][0][$fieldName])) {
                return null;
            }

            return (int) $files['error'][0][$fieldName];
        }

        if (! isset($_FILES[$fieldName]['error'])) {
            return null;
        }

        return (int) $_FILES[$fieldName]['error'];
    }

    private function uploadErrorMessage(int $phpError, FcFormField|FcFormGroupField $field): string
    {
        $maxLabel = $this->fileUploadMaxLabel($field);

        return match ($phpError) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "{$field->label} is too large. Maximum allowed size is {$maxLabel}.",
            UPLOAD_ERR_PARTIAL => "{$field->label} was only partially uploaded. Please try again.",
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => "{$field->label} could not be saved on the server. Please contact support.",
            default => "{$field->label} could not be uploaded. Please try again.",
        };
    }

    private function fileUploadMaxLabel(FcFormField|FcFormGroupField $field): string
    {
        if (! empty($field->file_max_kb)) {
            $kb = (int) $field->file_max_kb;

            return $kb >= 1024 && $kb % 1024 === 0 ? ($kb / 1024).' MB' : $kb.' KB';
        }

        if ($field->validation_rules && preg_match('/max:(\d+)/', $field->validation_rules, $m)) {
            $kb = (int) $m[1];

            return $kb >= 1024 && $kb % 1024 === 0 ? ($kb / 1024).' MB' : $kb.' KB';
        }

        return '5 MB';
    }

    /**
     * @param  FcFormField|FcFormGroupField  $field
     */
    private function resolveFieldValidationRules(
        FcFormField|FcFormGroupField $field,
        ?object $existingData = null,
        mixed $existingFilePath = null
    ): string {
        if ($field->field_type === 'file') {
            if ($existingFilePath === null && $existingData !== null) {
                $col = $field->target_column ?: $field->field_name;
                $existingFilePath = $existingData->{$col} ?? null;
            }

            return $this->buildFileValidationRules($field, filled($existingFilePath));
        }

        $rules = $field->validation_rules
            ?: ($field->is_required ? 'required' : 'nullable');

        if ($field instanceof FcFormGroupField) {
            $rules = $this->normalizeLanguageMasterExistsInRules($rules, $field);
        }

        $rules = $this->normalizeExistsTableInRules($rules);
        $rules = $this->appendFieldTypeValidationRules($field, $rules);
        $rules = $this->appendDateOfBirthRule($field, $rules);

        return $this->cleanValidationRules($rules);
    }

    /**
     * @param  FcFormField|FcFormGroupField  $field
     */
    private function buildFileValidationRules(FcFormField|FcFormGroupField $field, bool $hasExistingUpload): string
    {
        $rules = $field->validation_rules ?: ($field->is_required ? 'required' : 'nullable');

        if ($hasExistingUpload) {
            $rules = preg_replace('/\brequired\b/', 'nullable', $rules) ?? $rules;
        }

        $maxKb = $this->resolveFileMaxKb($field);
        $ext = $field->file_extensions ?: 'jpeg,jpg,png,pdf';

        if (! preg_match('/\b(file|image)\b/', $rules)) {
            $rules .= str_contains(strtolower($ext), 'pdf') && ! str_contains(strtolower($ext), 'jpg')
                ? '|file'
                : '|image';
        }

        if (! preg_match('/\bmimes:/', $rules)) {
            $rules .= '|mimes:'.str_replace(' ', '', $ext);
        }

        if (preg_match('/max:\d+/', $rules)) {
            $rules = preg_replace('/max:\d+/', 'max:'.$maxKb, $rules) ?? $rules;
        } else {
            $rules .= '|max:'.$maxKb;
        }

        return $this->cleanValidationRules($rules);
    }

    /**
     * @param  FcFormField|FcFormGroupField  $field
     */
    private function resolveFileMaxKb(FcFormField|FcFormGroupField $field): int
    {
        if (! empty($field->file_max_kb)) {
            return (int) $field->file_max_kb;
        }

        if ($field->validation_rules && preg_match('/max:(\d+)/', $field->validation_rules, $m)) {
            return (int) $m[1];
        }

        return 5120;
    }

    private function normalizeLookupTable(string $table): string
    {
        $aliases = [
            'language_masters' => 'language_master',
            'country_masters' => 'country_master',
            'state_masters' => 'state_master',
            'district_masters' => 'district_master',
            'state_district_masters' => 'state_district_mapping',
            'qualification_masters' => 'qualification_master',
            'religion_masters' => 'religion_master',
            'category_masters' => 'category_master',
        ];

        return $aliases[$table] ?? $table;
    }

    private function normalizeExistsTableInRules(string $rules): string
    {
        $map = [
            'language_masters' => 'language_master',
            'country_masters' => 'country_master',
            'state_masters' => 'state_master',
            'district_masters' => 'district_master',
            'state_district_masters' => 'state_district_mapping',
        ];

        foreach ($map as $from => $to) {
            $rules = preg_replace('/exists:\s*'.preg_quote($from, '/').'\s*,/i', 'exists:'.$to.',', $rules) ?? $rules;
        }

        return $rules;
    }

    /**
     * @param  FcFormField|FcFormGroupField  $field
     */
    private function appendFieldTypeValidationRules(FcFormField|FcFormGroupField $field, string $rules): string
    {
        if ($field->field_type === 'number') {
            if (! preg_match('/\b(numeric|integer|digits|decimal)\b/', $rules)) {
                $rules .= '|numeric';
            }

            return $rules;
        }

        if (in_array($field->field_type, ['text', 'textarea'], true)) {
            if (! preg_match('/\b(regex|alpha_num|alpha_dash)\b/', $rules)) {
                $rules .= '|string';
            }
        }

        if ($field->field_type === 'email' && ! preg_match('/\bemail\b/', $rules)) {
            $rules .= '|email';
        }

        if (preg_match('/\balpha_num\b/', $rules) === 0
            && preg_match('/regex:.*\[A-Z\]\{5\}/i', $rules)) {
            // PAN-style fields already have regex
        } elseif (str_contains(strtolower((string) $field->field_name), 'pan')
            && ! preg_match('/\bregex:/', $rules)) {
            $rules .= '|regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/';
        }

        return $rules;
    }

    /**
     * @param  FcFormField|FcFormGroupField  $field
     */
    private function appendDateOfBirthRule(FcFormField|FcFormGroupField $field, string $rules): string
    {
        $col = strtolower((string) ($field->target_column ?? $field->field_name ?? ''));
        if ($field->field_type !== 'date' || ! in_array($col, ['date_of_birth', 'dob'], true)) {
            return $rules;
        }

        $maxDob = now()->subYears(15)->format('Y-m-d');
        if (preg_match('/\bbefore_or_equal:/', $rules)) {
            return preg_replace('/before_or_equal:[^\|]+/', 'before_or_equal:'.$maxDob, $rules) ?? $rules;
        }
        if (preg_match('/\bbefore:/', $rules) && ! preg_match('/\bbefore_or_equal:/', $rules)) {
            return preg_replace('/before:[^\|]+/', 'before_or_equal:'.$maxDob, $rules) ?? $rules;
        }

        return $rules.'|date|before_or_equal:'.$maxDob;
    }

    private function cleanValidationRules(string $rules): string
    {
        $rules = trim($rules, '|');
        $rules = preg_replace('/\|{2,}/', '|', $rules) ?? $rules;

        return $rules;
    }

    private function isMeaningfulStoredValue(mixed $value, FcFormGroupField $field): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if ($field->field_type === 'checkbox') {
            $opts = $field->decoded_options ?? [];
            if (count($opts) > 0) {
                if (is_string($value)) {
                    $decoded = json_decode($value, true);

                    return is_array($decoded) && $decoded !== [];
                }

                return is_array($value) && $value !== [];
            }

            return (bool) $value;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
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
        int $userId,
        array $rows,
        Collection $fields,
        ?Request $request
    ): void {
        $course = $this->registrationPreMedicalCourse($userId);
        $row = $rows[0] ?? [];
        $uCol = $this->userCol('fc_pre_history');
        $uVal = $this->userVal('fc_pre_history', $userId);

        foreach ($fields as $field) {
            if ($field->field_type === 'checkbox' && count($field->decoded_options) > 0) {
                $row[$field->field_name] = $row[$field->field_name] ?? [];
            }
        }

        $courseMasterPk = app(\App\Services\FC\FcActivityStudentResolver::class)->courseMasterPkFromName($course) ?: null;

        $existing = FcPreHistory::where($uCol, $uVal)
            ->when($courseMasterPk, fn ($q) => $q->where('course_master_pk', $courseMasterPk))
            ->first();

        $payload = [
            $uCol             => $uVal,
            'course_master_pk'=> $courseMasterPk,
            'status'          => 1,
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
                        $userId.'_'.uniqid('', true).'.'.$file->getClientOriginalExtension(),
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
            [$uCol => $uVal, 'course_master_pk' => $courseMasterPk],
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

        if (in_array($field->field_type, ['select', 'radio'], true) && $field->lookup_table) {
            $type = $this->getColumnType($table, $column);
            if ($type !== null && in_array($type, ['smallint', 'integer', 'int', 'bigint', 'tinyint'], true)) {
                return is_numeric($value) ? (int) $value : $value;
            }
        }

        $type = $this->getColumnType($table, $column);
        if ($type === null) {
            return $value;
        }

        if ($field->field_type === 'checkbox' && count($field->decoded_options ?? []) === 0) {
            return $this->coerceToBoolInt($value) ? 1 : 0;
        }

        if (in_array($type, ['smallint', 'integer', 'int', 'bigint'], true)) {
            return is_numeric($value) ? (int) $value : $value;
        }

        if (in_array($type, ['tinyint', 'boolean', 'bool'], true)) {
            if (str_ends_with($column, '_id') || $field->lookup_table) {
                return is_numeric($value) ? (int) $value : $value;
            }

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
