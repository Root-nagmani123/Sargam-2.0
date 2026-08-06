<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Resolves the Descriptive Data report's logical columns to real tables/columns for ONE form.
 *
 * Why this exists: the FC registration schema is form-driven. Every form maps its own fields
 * through fc_form_fields.target_table/target_column, so two active courses genuinely differ —
 * form 21 ("Foundation Course 101") stores Nationality in student_master_seconds.nationality
 * and splits the trainee's name into first/middle/last, while form 1 has neither column mapped
 * and keeps a single full_name. A report with a hardcoded column list therefore renders empty
 * cells on one course and errors on another.
 *
 * So a logical column is emitted only when BOTH are true:
 *   1. the selected form actually maps that table.column (fc_form_fields), and
 *   2. the column exists in the database (fc_schema_has_column).
 * Anything else is silently dropped from that course's report rather than faked.
 *
 * Lookup metadata (religion_master.pk -> religion_name, caste_category_master.pk -> Seat_name,
 * ...) is read from the same form definition instead of being restated here, so renaming a
 * lookup in the form builder does not need a code change.
 */
class FcDescriptiveDataFieldResolver
{
    /** Cache the per-form resolution; the form definition only changes in the form builder. */
    private const CACHE_TTL_MINUTES = 60;

    /**
     * The report's columns, in display order.
     *
     * `source` is the join alias: s1 = student_master_firsts, s2 = student_master_seconds.
     * `columns` lists every physical column the field needs — a 'concat' field needs all of
     * them present before it can be shown at all.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function definition(): array
    {
        return [
            // ── Personal details ──────────────────────────────────────────
            'first_name'      => ['label' => 'First Name',            'group' => 'Personal Details', 'source' => 's1', 'columns' => ['first_name'],     'type' => 'text'],
            'middle_name'     => ['label' => 'Middle Name',           'group' => 'Personal Details', 'source' => 's1', 'columns' => ['middle_name'],    'type' => 'text'],
            'last_name'       => ['label' => 'Last Name / Surname',   'group' => 'Personal Details', 'source' => 's1', 'columns' => ['last_name'],      'type' => 'text'],

            // ── Service. NOT form-mapped: no FC form declares these in steps 1-2, they come
            //    off the registration roster. `derived` fields are exempt from the
            //    "form must map it" rule and are gated on schema availability instead. ──
            'service'         => ['label' => 'Service',                'group' => 'Service Details', 'type' => 'derived', 'derived' => 'service', 'filter' => 'service'],
            'rank'            => ['label' => 'Rank',                   'group' => 'Service Details', 'type' => 'derived', 'derived' => 'rank'],
            'gender'          => ['label' => 'Gender',                'group' => 'Personal Details', 'source' => 's1', 'columns' => ['gender'],         'type' => 'text',   'filter' => 'select'],
            'date_of_birth'   => ['label' => 'Date Of Birth',         'group' => 'Personal Details', 'source' => 's1', 'columns' => ['date_of_birth'],  'type' => 'date',   'filter' => 'date_range'],
            'nationality'     => ['label' => 'Nationality',           'group' => 'Personal Details', 'source' => 's2', 'columns' => ['nationality'],    'type' => 'text',   'filter' => 'select'],
            'background'      => ['label' => 'Background',            'group' => 'Personal Details', 'source' => 's1', 'columns' => ['background'],     'type' => 'text'],
            'marital_status'  => ['label' => 'Marital Status',        'group' => 'Personal Details', 'source' => 's2', 'columns' => ['marital_status'], 'type' => 'text',   'filter' => 'select'],
            'religion'        => ['label' => 'Religion',              'group' => 'Personal Details', 'source' => 's2', 'columns' => ['religion_id'],    'type' => 'lookup'],
            'category'        => ['label' => 'Category',              'group' => 'Personal Details', 'source' => 's2', 'columns' => ['category_id'],    'type' => 'lookup', 'filter' => 'lookup'],
            'pan_card'        => ['label' => 'PAN Card',              'group' => 'Personal Details', 'source' => 's1', 'columns' => ['pan_card'],       'type' => 'text'],
            'aadhar_number'   => ['label' => 'Aadhar Number',         'group' => 'Personal Details', 'source' => 's1', 'columns' => ['aadhar_number'],  'type' => 'text'],
            'passport_no'     => ['label' => 'Passport No',           'group' => 'Personal Details', 'source' => 's1', 'columns' => ['passport_no'],    'type' => 'text'],

            // ── Birth place ───────────────────────────────────────────────
            'birth_state'     => ['label' => 'Birth State',           'group' => 'Birth Place Details', 'source' => 's2', 'columns' => ['birth_state_id'],  'type' => 'lookup', 'filter' => 'lookup'],
            'birth_district'  => ['label' => 'Birth District',        'group' => 'Birth Place Details', 'source' => 's2', 'columns' => ['birth_district'],  'type' => 'text'],
            'birth_area_type' => ['label' => 'Category (Area Type)',  'group' => 'Birth Place Details', 'source' => 's2', 'columns' => ['birth_area_type'], 'type' => 'text'],
            'birth_city'      => ['label' => 'Village / City',        'group' => 'Birth Place Details', 'source' => 's2', 'columns' => ['birth_city'],      'type' => 'text'],

            // ── Contact ───────────────────────────────────────────────────
            'mobile_no'       => ['label' => 'Mobile No',             'group' => 'Contact Details', 'source' => 's1', 'columns' => ['mobile_no'],      'type' => 'text'],
            'alt_mobile_no'   => ['label' => 'Alternate Mobile No',   'group' => 'Contact Details', 'source' => 's1', 'columns' => ['alt_mobile_no'],  'type' => 'text'],
            'email'           => ['label' => 'Email Id',              'group' => 'Contact Details', 'source' => 's1', 'columns' => ['email'],          'type' => 'text'],
            'alt_email'       => ['label' => 'Alternate Email Id',    'group' => 'Contact Details', 'source' => 's1', 'columns' => ['alt_email'],      'type' => 'text'],

            // ── Parents. Built from the split name columns when the form maps them, else
            //    the single legacy column on student_master_firsts. ──────────
            'father_name'     => ['label' => "Father's Full Name",    'group' => 'Family Details', 'source' => 's2', 'columns' => ['father_first_name', 'father_middle_name', 'father_last_name'], 'type' => 'concat', 'fallback' => ['source' => 's1', 'columns' => ['fathers_name']]],
            'mother_name'     => ['label' => "Mother's Full Name",    'group' => 'Family Details', 'source' => 's2', 'columns' => ['mother_first_name', 'mother_middle_name', 'mother_last_name'], 'type' => 'concat', 'fallback' => ['source' => 's1', 'columns' => ['mothers_name']]],

            // ── Address (permanent), assembled into one readable cell ──────
            'perm_address'    => ['label' => 'Permanent Address',     'group' => 'Address', 'source' => 's2', 'columns' => ['perm_address_line1', 'perm_city', 'perm_district', 'perm_pincode'], 'type' => 'address', 'lookup_column' => 'perm_state_id'],

            // ── Uploads, rendered as links ────────────────────────────────
            'photo_path'      => ['label' => 'Photo',                 'group' => 'Uploads', 'source' => 's1', 'columns' => ['photo_path'],     'type' => 'file'],
            'signature_path'  => ['label' => 'Signature',             'group' => 'Uploads', 'source' => 's1', 'columns' => ['signature_path'], 'type' => 'file'],
        ];
    }

    private const SOURCE_TABLES = [
        's1' => 'student_master_firsts',
        's2' => 'student_master_seconds',
    ];

    /**
     * The columns this form can actually show, each resolved to a physical table/column
     * (+ lookup metadata where the form declares one).
     *
     * @return array<string,array<string,mixed>>
     */
    public function forForm(FcForm $form): array
    {
        $key = self::cacheKey('fields', (int) $form->id);

        try {
            $cached = Cache::get($key);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
            // Cache store unavailable — resolve inline rather than fail the report.
        }

        $resolved = $this->resolve($form);

        try {
            Cache::put($key, $resolved, now()->addMinutes(self::CACHE_TTL_MINUTES));
        } catch (\Throwable $e) {
            // Not worth failing the report over.
        }

        return $resolved;
    }

    /**
     * Invalidate everything this report caches for one form — the column resolution AND the
     * filter dropdown values. Called from the form builder whenever a field changes; without
     * it a newly-mapped column stays invisible for up to the TTL.
     *
     * Implemented by bumping a per-form generation counter that both cache keys embed, rather
     * than forgetting keys directly: the filter-options key also hashes the resolved field
     * list, which cannot be reconstructed here, and this project's cache driver is `file`,
     * which does not support tags. Incrementing one counter orphans every derived key at once.
     */
    public static function forgetForm(int $formId): void
    {
        if ($formId <= 0) {
            return;
        }

        try {
            Cache::forever(self::generationKey($formId), self::generation($formId) + 1);
        } catch (\Throwable $e) {
            // Best effort — a stale entry expires on its own TTL anyway.
        }
    }

    /** Cache key for one of this report's per-form payloads, pinned to the current generation. */
    public static function cacheKey(string $bucket, int $formId, string $suffix = ''): string
    {
        return 'fc_desc_data:'.$bucket.':'.$formId
            .':g'.self::generation($formId)
            .':'.self::definitionFingerprint()
            .($suffix !== '' ? ':'.$suffix : '');
    }

    private static function generationKey(int $formId): string
    {
        return 'fc_desc_data_gen:'.$formId;
    }

    private static function generation(int $formId): int
    {
        try {
            return (int) (Cache::get(self::generationKey($formId), 0));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Keyed on the definition itself so editing definition() invalidates every cached form. */
    private static function definitionFingerprint(): string
    {
        return substr(md5(serialize(array_keys(self::definition()))), 0, 8);
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function resolve(FcForm $form): array
    {
        $declared = $this->declaredFields($form);
        $out = [];

        $frmJoined = $this->registrationMasterIsJoined($form);

        foreach (self::definition() as $key => $def) {
            if (($def['type'] ?? '') === 'derived') {
                $entry = $this->resolveDerived($key, $def, $frmJoined);
                if ($entry !== null) {
                    $out[$key] = $entry;
                }
                continue;
            }

            $source = $def['source'];
            $columns = $def['columns'];

            if (! $this->allUsable($source, $columns, $declared)) {
                // Try the legacy single-column fallback (e.g. fathers_name) before giving up.
                $fallback = $def['fallback'] ?? null;
                if ($fallback === null || ! $this->allUsable($fallback['source'], $fallback['columns'], $declared)) {
                    continue;
                }
                $source = $fallback['source'];
                $columns = $fallback['columns'];
                $def['type'] = 'text';
            }

            $entry = [
                'key' => $key,
                'label' => $def['label'],
                'group' => $def['group'],
                'type' => $def['type'],
                'table' => self::SOURCE_TABLES[$source],
                'alias' => $source,
                'columns' => $columns,
                'filter' => $def['filter'] ?? null,
            ];

            // A lookup/address column needs the form's own lookup metadata to render a name
            // instead of a numeric id.
            $lookupColumn = $def['lookup_column'] ?? $columns[0];
            $meta = $declared[self::SOURCE_TABLES[$source].'.'.$lookupColumn] ?? null;
            if ($meta && $meta->lookup_table && $meta->lookup_value_column && $meta->lookup_label_column
                && fc_schema_has_table($meta->lookup_table)
                && fc_schema_has_column($meta->lookup_table, $meta->lookup_value_column)
                && fc_schema_has_column($meta->lookup_table, $meta->lookup_label_column)
                // A LEFT JOIN on a non-unique column MULTIPLIES rows — the same trainee would
                // appear once per matching lookup row, silently, in the table and every export.
                // The form builder lets an admin point lookup_value_column at any column, so
                // this is checked rather than assumed. Every current lookup joins on a PK.
                && $this->columnIsUniquelyIndexed($meta->lookup_table, $meta->lookup_value_column)) {
                $entry['lookup'] = [
                    'table' => $meta->lookup_table,
                    'value' => $meta->lookup_value_column,
                    'label' => $meta->lookup_label_column,
                    'column' => $lookupColumn,
                ];
            } elseif ($entry['type'] === 'lookup') {
                // Declared as a lookup but the target is missing — show the raw value rather
                // than emit a join against a table that does not exist.
                $entry['type'] = 'text';
                $entry['filter'] = $entry['filter'] === 'lookup' ? 'select' : $entry['filter'];
            }

            $out[$key] = $entry;
        }

        return $out;
    }

    /**
     * Is this column safe to LEFT JOIN on — i.e. covered by a single-column PRIMARY or UNIQUE
     * index, so at most one row can match?
     *
     * Multi-column unique indexes do not count: uniqueness of (a, b) says nothing about a.
     * Cached for an hour and keyed per table; the answer only changes with a schema migration.
     */
    private function columnIsUniquelyIndexed(string $table, string $column): bool
    {
        try {
            $unique = Cache::remember('fc_desc_data_uniqcols:'.$table, now()->addHour(), function () use ($table) {
                // $table is already allowlisted by fc_schema_has_table() before we get here.
                $rows = DB::select('SHOW INDEX FROM `'.str_replace('`', '', $table).'` WHERE Non_unique = 0');

                $byIndex = [];
                foreach ($rows as $row) {
                    $byIndex[$row->Key_name][] = $row->Column_name;
                }

                // Keep only indexes made of exactly one column.
                return array_values(array_map(
                    fn ($cols) => $cols[0],
                    array_filter($byIndex, fn ($cols) => count($cols) === 1)
                ));
            });
        } catch (\Throwable $e) {
            // Cannot prove uniqueness → do not risk duplicating rows.
            return false;
        }

        return in_array($column, (array) $unique, true);
    }

    /**
     * Service / Rank come off the registration roster, not the form definition, so they are
     * resolved from schema availability alone.
     *
     * @return array<string,mixed>|null  null when the underlying data cannot be reached
     */
    private function resolveDerived(string $key, array $def, bool $frmJoined): ?array
    {
        $entry = [
            'key' => $key,
            'label' => $def['label'],
            'group' => $def['group'],
            'type' => 'derived',
            'derived' => $def['derived'],
            'table' => null,
            'alias' => null,
            'columns' => [],
            'filter' => $def['filter'] ?? null,
        ];

        if ($def['derived'] === 'service') {
            if (! fc_schema_has_table('service_master')) {
                return null;
            }

            // Either source will do; the SELECT coalesces whichever is populated.
            $fromS1 = fc_schema_has_column('student_master_firsts', 'service_id');
            $fromFrm = $frmJoined && fc_schema_has_column('fc_registration_master', 'service_master_pk');
            if (! $fromS1 && ! $fromFrm) {
                return null;
            }

            $entry['sources'] = ['s1' => $fromS1, 'frm' => $fromFrm];
            // Ordering by the resolved name is meaningful; the expression is built in
            // FcDescriptiveDataQuery so the two cannot disagree.
            $entry['orderable'] = true;

            return $entry;
        }

        if ($def['derived'] === 'rank') {
            if (! $frmJoined || ! fc_schema_has_column('fc_registration_master', 'rank')) {
                return null;
            }

            $entry['alias'] = 'frm';
            $entry['columns'] = ['rank'];
            $entry['orderable'] = true;

            return $entry;
        }

        return null;
    }

    /**
     * Is `frm` (fc_registration_master) actually in the query?
     *
     * fc_report_apply_tracker_user_resolution() only joins it when the tracker keys on
     * user_id — a legacy username-keyed form (e.g. form 16) never gets the alias, and naming
     * frm there would be invalid SQL. Checked rather than assumed.
     */
    private function registrationMasterIsJoined(FcForm $form): bool
    {
        return fc_user_col($form->trackerStorageTable()) === 'user_id'
            && fc_schema_has_table('fc_registration_master');
    }

    /** Every column must be mapped by the form AND present in the database. */
    private function allUsable(string $source, array $columns, array $declared): bool
    {
        $table = self::SOURCE_TABLES[$source] ?? null;
        if ($table === null || ! fc_schema_has_table($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! isset($declared[$table.'.'.$column]) || ! fc_schema_has_column($table, $column)) {
                return false;
            }
        }

        return true;
    }

    /**
     * "table.column" => field row, for every active field this form maps.
     *
     * Explicit column list and a single query (G1/G4) — the report never asks the form
     * definition anything again after this.
     *
     * @return array<string,object>
     */
    private function declaredFields(FcForm $form): array
    {
        $rows = DB::table('fc_form_fields as f')
            ->join('fc_form_steps as s', 'f.step_id', '=', 's.id')
            ->where('s.form_id', $form->id)
            ->where('f.is_active', 1)
            ->where('s.is_active', 1)
            ->whereNotNull('f.target_table')
            ->whereNotNull('f.target_column')
            ->get([
                'f.target_table',
                'f.target_column',
                'f.lookup_table',
                'f.lookup_value_column',
                'f.lookup_label_column',
            ]);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->target_table.'.'.$row->target_column] = $row;
        }

        return $map;
    }
}
