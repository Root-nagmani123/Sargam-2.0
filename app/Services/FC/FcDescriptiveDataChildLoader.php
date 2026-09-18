<?php

namespace App\Services\FC;

use Illuminate\Support\Facades\DB;

/**
 * Fills in the Descriptive Data report's repeating sections — Educational Details, Languages
 * Known, Previous Job Experience, Academic Distinction, Hobbies, Dress Code, Spouse.
 *
 * Why these are not joined
 * ------------------------
 * Each of these tables holds MANY rows per trainee. A LEFT JOIN would multiply the driving
 * row: a trainee with three qualifications would appear three times in the table and three
 * times in every export, silently. The report's whole contract is one row per trainee.
 *
 * Why this is not a correlated subquery either
 * --------------------------------------------
 * A GROUP_CONCAT subquery per column would run once per output row per column — on a
 * 10,000-row Excel export with the Educational Details block ticked that is 90,000 executions.
 * This runs ONE query per child table for the whole page/export instead, so a 10,000-row
 * export costs the same seven extra queries as a 25-row page.
 *
 * Ordering guarantee
 * ------------------
 * Every column of one section is built from the same child rows in the same order (by primary
 * key), so the n-th item in "Degree" belongs with the n-th item in "University / Board Name".
 * Blank values are kept as "-" rather than dropped, or the columns would fall out of step.
 */
class FcDescriptiveDataChildLoader
{
    /** Separator between the child rows collapsed into one cell. */
    public const SEPARATOR = ' | ';

    /**
     * The extra SELECT the report needs before hydrate() can work: the key the child tables
     * hang off. It is s1's user key, not the tracker's — the two differ on courses whose
     * tracker stores a roster pk, and joining through s1 is how the rest of this report
     * already resolves a trainee.
     */
    public const LINK_COLUMN = 'child_link_id';

    /** Do any of these fields need this loader at all? */
    public function needed(array $fields): bool
    {
        foreach ($fields as $field) {
            if (($field['type'] ?? '') === 'child') {
                return true;
            }
        }

        return false;
    }

    /**
     * Writes one property per child field onto every row, in place.
     *
     * @param  iterable<int,object>  $rows  rows already read from the report query
     * @param  array<string,array<string,mixed>>  $fields  the resolved field set
     */
    public function hydrate(iterable $rows, array $fields): void
    {
        $childFields = array_filter($fields, fn ($f) => ($f['type'] ?? '') === 'child');
        if ($childFields === []) {
            return;
        }

        // Materialise once: $rows may be a lazy collection and it is walked twice.
        $rows = is_array($rows) ? $rows : iterator_to_array($rows);
        if ($rows === []) {
            return;
        }

        // Group the requested columns by child table so each table costs one query.
        $byTable = [];
        foreach ($childFields as $key => $field) {
            $byTable[$field['child']['table']][$key] = $field;
        }

        foreach ($byTable as $table => $tableFields) {
            $this->hydrateTable($rows, $table, $tableFields);
        }
    }

    /**
     * @param  array<int,object>  $rows
     * @param  array<string,array<string,mixed>>  $tableFields
     */
    private function hydrateTable(array $rows, string $table, array $tableFields): void
    {
        $userColumn = (string) reset($tableFields)['child']['user_column'];

        // Two link shapes exist: most child tables key on the same user id as
        // student_master_firsts, but student_cloth_size_master_details keys on the login
        // username. Both values are already on the row.
        $rowProperty = $userColumn === 'username' ? 'login_username' : self::LINK_COLUMN;

        $keys = [];
        foreach ($rows as $row) {
            $value = $row->{$rowProperty} ?? null;
            if ($value !== null && $value !== '') {
                $keys[(string) $value] = true;
            }
        }

        // Default every cell first, so a trainee with no child rows gets '' rather than an
        // undefined property that the exports would render as the string "null".
        foreach ($rows as $row) {
            foreach (array_keys($tableFields) as $key) {
                $row->{$key} = '';
            }
        }

        if ($keys === []) {
            return;
        }

        $grouped = $this->fetch($table, $userColumn, array_keys($keys), $tableFields);

        foreach ($rows as $row) {
            $link = (string) ($row->{$rowProperty} ?? '');
            $childRows = $grouped[$link] ?? [];
            if ($childRows === []) {
                continue;
            }

            foreach ($tableFields as $key => $field) {
                $parts = [];
                foreach ($childRows as $childRow) {
                    $parts[] = $this->formatValue($childRow->{$key} ?? null, $field);
                }
                // A single child row is the common case (Hobbies, Dress Code, Spouse) — do not
                // decorate it with a separator it does not need.
                $row->{$key} = implode(self::SEPARATOR, $parts);
            }
        }
    }

    /**
     * One query for the whole page/export: the child rows for every trainee on it, with any
     * lookup ids already resolved to names.
     *
     * @param  list<string>  $keys
     * @param  array<string,array<string,mixed>>  $tableFields
     * @return array<string,list<object>>  keyed by the trainee's link value
     */
    private function fetch(string $table, string $userColumn, array $keys, array $tableFields): array
    {
        $query = DB::table($table);

        // G1: named columns only. The link value is aliased so it cannot collide with a
        // field key, and the primary key gives the deterministic child-row order.
        $select = [DB::raw("`{$table}`.`{$userColumn}` as `__link`")];
        $orderBy = fc_schema_has_column($table, 'id') ? "{$table}.id" : "{$table}.{$userColumn}";

        foreach ($tableFields as $key => $field) {
            $column = $field['child']['column'];
            $lookup = $field['child']['lookup'] ?? null;

            if ($lookup === null) {
                $select[] = DB::raw("`{$table}`.`{$column}` as `{$key}`");
                continue;
            }

            // One join per column, aliased by field key so two columns pointing at the same
            // master table (Optional Subject First / Second) do not collide.
            $joinAlias = 'clk_'.$key;
            $query->leftJoin(
                "{$lookup['table']} as {$joinAlias}",
                "{$joinAlias}.{$lookup['value']}",
                '=',
                "{$table}.{$column}"
            );
            $select[] = DB::raw("`{$joinAlias}`.`{$lookup['label']}` as `{$key}`");
        }

        $out = [];
        foreach (
            $query->whereIn("{$table}.{$userColumn}", $keys)
                ->orderBy($orderBy)
                ->select($select)
                ->cursor() as $row
        ) {
            $out[(string) $row->__link][] = $row;
        }

        return $out;
    }

    /** Blank stays "-" so the columns of one section line up row-for-row. */
    private function formatValue($value, array $field): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '' || str_starts_with($value, '0000')) {
            return '-';
        }

        // Checkbox columns store 1/0; the PDF prints them as Yes/No and so does this.
        if (($field['child']['format'] ?? null) === 'bool') {
            return in_array($value, ['1', 'y', 'yes', 'true', 'on'], true) ? 'Yes' : 'No';
        }

        if (($field['child']['format'] ?? null) === 'date') {
            try {
                return \Carbon\Carbon::parse($value)->format('d-m-Y');
            } catch (\Throwable $e) {
                return $value;
            }
        }

        return $value;
    }
}
