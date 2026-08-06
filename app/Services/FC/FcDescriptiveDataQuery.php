<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The single query behind the Descriptive Data report — the on-screen DataTable, the Excel
 * export and the PDF export all build from here.
 *
 * Deliberately one builder rather than three: the exports must return exactly the rows the
 * admin is looking at, and three hand-maintained copies of the same joins and filters drift
 * apart (an export that silently ignores a filter is worse than one that fails).
 *
 * Guideline notes:
 *  - G1 every column is named; there is no select * anywhere in this class.
 *  - G5 lookups are LEFT JOINs, so a 400-row page costs one query, not 400.
 *  - G8 the date-of-birth filter is a plain range on the column (no DATE()/whereDate), and
 *    the search never wraps a column in LOWER() — MySQL's default collation is already
 *    case-insensitive, so LOWER() would only cost the index.
 */
class FcDescriptiveDataQuery
{
    /**
     * Filters are NOT applied here — call applyFilters() next. Keeping them apart lets the
     * DataTable hand the search box to Yajra while the exports apply the same predicates
     * from plain request input, without either path rebuilding the joins.
     *
     * @param  array<string,array<string,mixed>>  $fields  from FcDescriptiveDataFieldResolver
     */
    public function build(FcForm $form, array $fields): Builder
    {
        $tracker = $form->trackerStorageTable();
        $userKey = fc_user_col($tracker);

        $query = $this->scopedBase($form);

        $select = [
            DB::raw(fc_report_login_username_sql($tracker, $tracker).' as login_username'),
            DB::raw("`{$tracker}`.`{$userKey}` as route_user_id"),
        ];

        // One join per DISTINCT lookup table+column pair, aliased by field key so two fields
        // pointing at state_master (birth state, permanent-address state) do not collide.
        $needsChildLink = false;
        foreach ($fields as $key => $field) {
            // Repeating sections are not selectable SQL — they are batched in afterwards by
            // FcDescriptiveDataChildLoader. Joining them here would multiply the rows.
            if (($field['type'] ?? '') === 'child') {
                $needsChildLink = true;
                continue;
            }

            $select[] = $this->selectExpression($key, $field, $query);
        }

        // Only carried when something actually needs it, so the common query is unchanged.
        if ($needsChildLink) {
            $s1Col = fc_user_col('student_master_firsts');
            $select[] = DB::raw("`s1`.`{$s1Col}` as `".FcDescriptiveDataChildLoader::LINK_COLUMN.'`');
        }

        return $query->select($select);
    }

    /**
     * The trainees of ONE course, joined to s1/s2, with no SELECT list yet.
     *
     * Shared by build() and by the filter-dropdown queries so the two cannot disagree about
     * who is in the course — a dropdown built from a different row set than the table it
     * filters offers values that return nothing.
     */
    public function scopedBase(FcForm $form): Builder
    {
        $tracker = $form->trackerStorageTable();

        $query = DB::table($tracker);

        // Scope to this course exactly the way every other FC report does.
        if (fc_schema_has_column($tracker, 'form_id')) {
            $query->where("{$tracker}.form_id", $form->id);
        }

        fc_report_apply_tracker_user_resolution($query, $tracker, $tracker);
        fc_report_join_student_master_firsts($query, $tracker, $tracker);

        // student_master_seconds hangs off s1's user key, not the tracker's: s1 is already
        // resolved against every id shape (credentials pk / roster pk / username) by the
        // helper above, so joining through it keeps this report's row set identical to the
        // PDF's instead of re-deriving the resolution and disagreeing on edge cases.
        $s1Col = fc_user_col('student_master_firsts');
        $s2Col = fc_user_col('student_master_seconds');
        $query->leftJoin('student_master_seconds as s2', "s2.{$s2Col}", '=', "s1.{$s1Col}");

        // Language Details table, joined the same way. Guarded because it is the only source
        // table that a deployment could plausibly not have; its user_id carries a UNIQUE
        // index, so this cannot multiply the driving row.
        if (fc_schema_has_table('student_knowledge_hindi_masters')) {
            $s3Col = fc_user_col('student_knowledge_hindi_masters');
            $query->leftJoin('student_knowledge_hindi_masters as s3', "s3.{$s3Col}", '=', "s1.{$s1Col}");
        }

        $this->joinServiceMaster($query, $tracker);

        return $query;
    }

    /**
     * service_master, joined from both places a service id can live.
     *
     * On the courses seen so far student_master_firsts.service_id is empty and the value is
     * only on the roster (fc_registration_master.service_master_pk), so both are joined and
     * the SELECT coalesces them.
     *
     * The CAST on the roster column is unavoidable: it is varchar(255) against an integer PK.
     * It costs nothing here — that column carries no index to defeat (verified), and the
     * indexed side of the comparison is service_master.pk, a 38-row primary key. The real fix
     * is the column type, not the query (see the collation/type note in the review guide).
     */
    private function joinServiceMaster(Builder $query, string $tracker): void
    {
        if (! fc_schema_has_table('service_master')) {
            return;
        }

        if (fc_schema_has_column('student_master_firsts', 'service_id')) {
            $query->leftJoin('service_master as svc', 'svc.pk', '=', 's1.service_id');
        }

        if ($this->hasRegistrationMaster($tracker) && fc_schema_has_column('fc_registration_master', 'service_master_pk')) {
            $query->leftJoin(
                'service_master as svc_frm',
                DB::raw('CAST(frm.service_master_pk AS UNSIGNED)'),
                '=',
                'svc_frm.pk'
            );
        }
    }

    /** `frm` is only joined by the resolution helper when the tracker keys on user_id. */
    private function hasRegistrationMaster(string $tracker): bool
    {
        return fc_user_col($tracker) === 'user_id' && fc_schema_has_table('fc_registration_master');
    }

    /**
     * SQL for the resolved service name — short name first, then full name, from whichever
     * of the two sources is populated. Shared by the SELECT, the ORDER BY and the dropdown.
     */
    public function serviceNameSql(array $field): string
    {
        $parts = [];
        if (! empty($field['sources']['s1'])) {
            $parts[] = "NULLIF(TRIM(svc.service_short_name), '')";
            $parts[] = "NULLIF(TRIM(svc.service_name), '')";
        }
        if (! empty($field['sources']['frm'])) {
            $parts[] = "NULLIF(TRIM(svc_frm.service_short_name), '')";
            $parts[] = "NULLIF(TRIM(svc_frm.service_name), '')";
        }

        return $parts === [] ? 'NULL' : 'COALESCE('.implode(', ', $parts).')';
    }

    /**
     * Distinct values present in THIS course for one filterable column.
     *
     * Previously these were read straight off student_master_seconds with no course scope, so
     * a dropdown listed every value any trainee in the academy had ever entered — picking one
     * that nobody on this course has returned zero rows and read as a broken filter. It was
     * also an unbounded scan of a table that grows with every trainee ever registered.
     *
     * @param  \Illuminate\Database\Query\Builder  $base  from scopedBase(), cloned per column
     * @return list<array{value: string, label: string}>
     */
    public function distinctFilterValues(Builder $base, array $field, int $limit = 200): array
    {
        $query = clone $base;

        if (($field['filter'] ?? null) === 'service') {
            // id + name pairs for the services actually present on this course.
            $idSql = 'COALESCE('
                .(! empty($field['sources']['s1']) ? 'svc.pk, ' : '')
                .(! empty($field['sources']['frm']) ? 'svc_frm.pk' : 'NULL')
                .')';
            $nameSql = $this->serviceNameSql($field);

            return $query->whereRaw("{$nameSql} IS NOT NULL")
                ->distinct()
                ->orderByRaw("{$nameSql} asc")
                ->limit($limit)
                ->pluck(DB::raw("{$nameSql} as nm"), DB::raw("{$idSql} as vid"))
                ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])
                ->values()
                ->all();
        }

        if (isset($field['lookup'])) {
            // Resolve to the label so the dropdown reads as names, and only for ids actually
            // used on this course.
            $lk = $field['lookup'];
            $query->join(
                "{$lk['table']} as lkf",
                "lkf.{$lk['value']}",
                '=',
                "{$field['alias']}.{$lk['column']}"
            );

            return $query->whereNotNull("lkf.{$lk['label']}")
                ->where("lkf.{$lk['label']}", '!=', '')
                ->distinct()
                ->orderBy("lkf.{$lk['label']}")
                ->limit($limit)
                ->pluck("lkf.{$lk['label']}", "lkf.{$lk['value']}")
                ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])
                ->values()
                ->all();
        }

        $column = "{$field['alias']}.{$field['columns'][0]}";

        return $query->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->limit($limit)
            ->pluck($column)
            ->map(fn ($v) => ['value' => (string) $v, 'label' => (string) $v])
            ->values()
            ->all();
    }

    /**
     * Builds the SELECT fragment for one logical column, adding its lookup join when needed.
     */
    private function selectExpression(string $key, array $field, Builder $query)
    {
        if (($field['type'] ?? '') === 'derived') {
            if ($field['derived'] === 'service') {
                return DB::raw($this->serviceNameSql($field)." as `{$key}`");
            }

            // rank — a plain roster column.
            return DB::raw("NULLIF(TRIM(`{$field['alias']}`.`{$field['columns'][0]}`), '') as `{$key}`");
        }

        $alias = $field['alias'];

        if (isset($field['lookup'])) {
            $joinAlias = 'lk_'.$key;
            $lk = $field['lookup'];
            $query->leftJoin(
                "{$lk['table']} as {$joinAlias}",
                "{$joinAlias}.{$lk['value']}",
                '=',
                "{$alias}.{$lk['column']}"
            );

            if ($field['type'] === 'address') {
                // Address = the free-text parts plus the resolved state name, joined with
                // ", " and with the empty parts dropped so a half-filled address does not
                // print as "abc, , , 110001".
                $parts = array_map(
                    fn ($c) => "NULLIF(TRIM(`{$alias}`.`{$c}`), '')",
                    $field['columns']
                );
                $parts[] = "NULLIF(TRIM(`{$joinAlias}`.`{$lk['label']}`), '')";

                return DB::raw('CONCAT_WS(\', \', '.implode(', ', $parts).") as `{$key}`");
            }

            return DB::raw("NULLIF(TRIM(`{$joinAlias}`.`{$lk['label']}`), '') as `{$key}`");
        }

        if ($field['type'] === 'concat' || ($field['type'] === 'address' && count($field['columns']) > 1)) {
            $parts = array_map(
                fn ($c) => "NULLIF(TRIM(`{$alias}`.`{$c}`), '')",
                $field['columns']
            );

            return DB::raw('CONCAT_WS(\' \', '.implode(', ', $parts).") as `{$key}`");
        }

        return DB::raw("`{$alias}`.`{$field['columns'][0]}` as `{$key}`");
    }

    /**
     * Applies the report's filters + free-text search to an already-built query.
     *
     * Kept separate from build() so the DataTable can let Yajra own the search box while the
     * exports apply the same predicates from plain request input.
     *
     * @param  array<string,array<string,mixed>>  $fields
     */
    public function applyFilters(Builder $query, array $fields, Request $request): void
    {
        foreach ($fields as $key => $field) {
            $filter = $field['filter'] ?? null;
            if ($filter === null || ($field['type'] ?? '') === 'child') {
                continue;
            }

            if ($filter === 'date_range') {
                $this->applyDateRange($query, $field, $request, $key);
                continue;
            }

            $value = $request->input('f_'.$key);
            if ($value === null || $value === '') {
                continue;
            }

            if ($filter === 'service') {
                // Match the id in EITHER source, the same way the SELECT coalesces them —
                // filtering only one would silently hide trainees whose service is on the
                // other. Bound parameter, not interpolated.
                $query->where(function ($sub) use ($value, $field) {
                    $matched = false;
                    if (! empty($field['sources']['s1'])) {
                        $sub->orWhere('s1.service_id', $value);
                        $matched = true;
                    }
                    if (! empty($field['sources']['frm'])) {
                        $sub->orWhereRaw('CAST(frm.service_master_pk AS UNSIGNED) = ?', [(int) $value]);
                        $matched = true;
                    }
                    if (! $matched) {
                        $sub->whereRaw('1 = 0');
                    }
                });
                continue;
            }

            // A lookup filter matches the stored id, not the rendered label — an exact id
            // comparison uses the column's index; matching the joined name would not.
            $column = isset($field['lookup']) ? $field['lookup']['column'] : $field['columns'][0];
            $query->where("{$field['alias']}.{$column}", $value);
        }

        $this->applySearch($query, $fields, $request);
    }

    /**
     * G8: a half-open range on the raw column, so an index on date_of_birth stays usable.
     * whereDate() would wrap it in DATE() and force a scan.
     */
    private function applyDateRange(Builder $query, array $field, Request $request, string $key): void
    {
        $column = "{$field['alias']}.{$field['columns'][0]}";
        $from = trim((string) $request->input('f_'.$key.'_from', ''));
        $to = trim((string) $request->input('f_'.$key.'_to', ''));

        if ($from !== '' && $this->isDate($from)) {
            $query->where($column, '>=', $from);
        }
        if ($to !== '' && $this->isDate($to)) {
            $query->where($column, '<=', $to);
        }
    }

    private function isDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }

    /**
     * Free-text search across the identifying columns only — name, contact, and the
     * government ids. Searching every column would mean OR-ing ~25 LIKEs per row.
     *
     * @param  array<string,array<string,mixed>>  $fields
     */
    public function applySearch(Builder $query, array $fields, Request $request): void
    {
        $term = trim((string) ($request->input('search_term') ?: $request->input('search.value') ?: ''));
        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';
        $searchable = array_intersect_key($fields, array_flip([
            'first_name', 'middle_name', 'last_name', 'mobile_no', 'alt_mobile_no',
            'email', 'alt_email', 'pan_card', 'aadhar_number', 'passport_no',
            'father_name', 'mother_name',
        ]));

        $query->where(function ($sub) use ($searchable, $like) {
            foreach ($searchable as $field) {
                foreach ($field['columns'] as $column) {
                    $sub->orWhere("{$field['alias']}.{$column}", 'like', $like);
                }
            }
            // The login/username the rest of the FC reports search on.
            $sub->orWhere('uc.user_name', 'like', $like);
        });
    }
}
