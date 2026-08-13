<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Shared engine for the "one FC registration step, one row per trainee" reports —
 * Vision Statement, Special Assistant, and any later step of the same shape.
 *
 * Those steps all look the same from a reporting angle: a handful of identifying columns
 * (who is this trainee) followed by the few fields that step collects, one row each. Only the
 * second half differs, so a subclass supplies just that: its columns, their SQL, and which
 * column decides whether the trainee has filled the step in.
 *
 * The JOINS are not re-derived here. FcDescriptiveDataQuery::scopedBase() already resolves a
 * trainee across every id shape the FC schema uses (credentials pk / roster pk / username) and
 * joins uc, frm and service_master accordingly — the part that is genuinely easy to get subtly
 * wrong. Re-implementing it would give reports that disagree about who is on a course.
 */
abstract class FcStepReport
{
    /** Long text is cut to this in the on-screen table only; exports always carry it whole. */
    public const TABLE_PREVIEW_CHARS = 220;

    /** Route/storage key, e.g. 'vision-statement'. Also namespaces the saved column selection. */
    abstract public function key(): string;

    /** Human title for the page heading, PDF and Excel sheet. */
    abstract public function title(): string;

    /** One-line description under the heading. */
    abstract public function subtitle(): string;

    /**
     * The step's own columns, after the shared identity block.
     *
     * `long`  — prose; truncated on screen, wrapped in Excel, given its own block in the PDF.
     * `file`  — an upload; rendered as a link and exported as a URL.
     *
     * @return array<string,array<string,mixed>>
     */
    abstract public function reportColumns(): array;

    /**
     * SQL per report column key, in the same order as reportColumns().
     *
     * @return array<string,string>
     */
    abstract protected function reportExpressions(FcForm $form): array;

    /**
     * Extra joins this report needs on top of scopedBase().
     *
     * Takes the form because a step's table is not always keyed on the trainee alone —
     * fc_pre_history holds one row per trainee PER COURSE, so its join has to be scoped or it
     * would return two rows for anyone who has been on two courses.
     */
    protected function applyJoins(Builder $query, FcForm $form): void
    {
        // Most reports read straight off s1 and need nothing extra.
    }

    /**
     * The columns that decide whether the trainee has filled this step in — qualified, e.g.
     * `sa.physical_impairment_info`. Empty means the report has no status filter.
     *
     * An array rather than one assembled expression: applyFilters() turns it into one predicate
     * per column, so the SQL stays `TRIM(col)` instead of TRIM(COALESCE(CONCAT(col, col, ...))).
     * Same result, one function deep instead of three, and index-usable the moment any of these
     * columns is indexed. (G8 — partial: TRIM still wraps the column. The complete fix is a
     * boolean completion flag maintained on write, which is a change to the form save path.)
     *
     * @return list<string>
     */
    abstract public function statusColumns(): array;

    /** Labels for the status filter, e.g. ['submitted' => 'Submitted only', ...]. */
    public function statusLabels(): array
    {
        return ['submitted' => 'Submitted only', 'pending' => 'Not submitted only'];
    }

    /** Columns on student_master_firsts that free-text search should cover, beyond the defaults. */
    protected function extraSearchColumns(): array
    {
        return [];
    }

    /**
     * Does this course's form actually map the step this report reads?
     *
     * BOTH tables are checked. A plain step field lives in fc_form_fields, but a repeating or
     * sectioned block — Pre-Medical History is one — is declared in fc_form_group_fields with
     * its table on the GROUP. Checking only the first would report a mapped step as unmapped.
     */
    public function formMapsStep(FcForm $form): bool
    {
        $probe = $this->probeField();
        if (! fc_schema_has_column($probe['table'], $probe['column'])) {
            return false;
        }

        $asField = DB::table('fc_form_fields as f')
            ->join('fc_form_steps as s', 'f.step_id', '=', 's.id')
            ->where('s.form_id', $form->id)
            ->where('f.is_active', 1)
            ->where('s.is_active', 1)
            ->where('f.target_table', $probe['table'])
            ->where('f.target_column', $probe['column'])
            ->exists();

        if ($asField) {
            return true;
        }

        return DB::table('fc_form_group_fields as gf')
            ->join('fc_form_field_groups as g', 'gf.group_id', '=', 'g.id')
            ->join('fc_form_steps as s', 'g.step_id', '=', 's.id')
            ->where('s.form_id', $form->id)
            ->where('gf.is_active', 1)
            ->where('g.is_active', 1)
            ->where('s.is_active', 1)
            ->where('g.target_table', $probe['table'])
            ->where('gf.target_column', $probe['column'])
            ->exists();
    }

    /** @return array{table: string, column: string} one field that proves the step is mapped */
    abstract protected function probeField(): array;

    // ── Shared identity block ────────────────────────────────────────────────

    /** @return array<string,array<string,mixed>> */
    public function basicColumns(): array
    {
        return [
            'login_username' => ['label' => 'Username',     'orderable' => false],
            'display_name'   => ['label' => 'Display Name', 'orderable' => true],
            'service'        => ['label' => 'Service',      'orderable' => true],
            'rank'           => ['label' => 'Rank',         'orderable' => true],
            'email'          => ['label' => 'Email Id',     'orderable' => true],
            'mobile_no'      => ['label' => 'Mobile No',    'orderable' => true],
        ];
    }

    /** @return array<string,array<string,mixed>> */
    public function columns(): array
    {
        return array_merge($this->basicColumns(), $this->reportColumns());
    }

    /**
     * The upload columns, if any — these are what the bulk document archive collects.
     *
     * @return array<string,array<string,mixed>>
     */
    public function fileColumns(): array
    {
        return array_filter($this->reportColumns(), fn ($c) => ! empty($c['file']));
    }

    public function hasFileColumns(): bool
    {
        return $this->fileColumns() !== [];
    }

    /**
     * The trainees of one course, selecting only the requested columns (all of them by default).
     *
     * Narrowing is not cosmetic here: these steps carry free-text fields of up to 1,500
     * characters, which dominate the response. Hiding one in the Columns menu should stop it
     * being fetched, not just stop it being displayed.
     *
     * ORDER BY is unaffected — orderSql() names real columns and expressions, never the SELECT
     * aliases, so a hidden column can still be sorted on.
     *
     * @param  array<string,array<string,mixed>>|null  $columns  null = every column
     */
    public function build(FcForm $form, ?array $columns = null): Builder
    {
        $query = app(FcDescriptiveDataQuery::class)->scopedBase($form);
        $this->applyJoins($query, $form);

        $expressions = array_merge($this->basicExpressions($form), $this->reportExpressions($form));

        $wanted = $columns === null ? $expressions : array_intersect_key($expressions, $columns);
        if ($wanted === []) {
            $wanted = $expressions;
        }

        $select = [];
        foreach ($wanted as $key => $sql) {
            $select[] = DB::raw($sql.' as `'.$key.'`');
        }

        return $query->select($select);
    }

    /**
     * The same rows with no SELECT list — for counting.
     *
     * The report's own joins ARE applied: a filter or search can name the step's table (Special
     * Assistant's status filter reads sa.physical_impairment_info), so counting off a bare
     * scopedBase() would fail with an unknown-column error. They cannot change the count, being
     * LEFT JOINs on uniquely-indexed columns.
     */
    public function countBase(FcForm $form): Builder
    {
        $query = app(FcDescriptiveDataQuery::class)->scopedBase($form);
        $this->applyJoins($query, $form);

        return $query->selectRaw('1');
    }

    /**
     * The query behind the bulk document archive.
     *
     * Deliberately not build(): the archive writes files, not cells, so it reads only what names
     * a folder (username / rank / exam year) plus the upload paths themselves — not the long
     * free-text columns that make a row of this report expensive.
     *
     * `link_id` is s1's user key, exposed so the caller can chunkById() on a UNIQUE column.
     */
    public function documentArchiveQuery(FcForm $form): Builder
    {
        $query = app(FcDescriptiveDataQuery::class)->scopedBase($form);
        $this->applyJoins($query, $form);

        $tracker = $form->trackerStorageTable();
        $s1Col = fc_user_col('student_master_firsts');

        $select = [
            DB::raw("`s1`.`{$s1Col}` as `link_id`"),
            DB::raw(fc_report_login_username_sql($tracker, $tracker).' as `login_username`'),
            DB::raw($this->displayNameSql().' as `display_name`'),
        ];

        // rank / exam year live on the roster, only joined when the tracker keys on user_id.
        $select[] = DB::raw($this->rankSql($form).' as `reg_rank`');
        $select[] = ($this->rosterIsJoined($form) && fc_schema_has_column('fc_registration_master', 'exam_year'))
            ? DB::raw("NULLIF(TRIM(`frm`.`exam_year`), '') as `exam_year`")
            : DB::raw('NULL as `exam_year`');

        $expressions = $this->reportExpressions($form);
        foreach (array_keys($this->fileColumns()) as $key) {
            $select[] = DB::raw($expressions[$key].' as `'.$key.'`');
        }

        return $query->select($select);
    }

    /** SQL for one report column, so callers can build predicates without knowing its table alias. */
    public function columnSql(FcForm $form, string $key): ?string
    {
        return $this->reportExpressions($form)[$key] ?? null;
    }

    /** @return array<string,string> */
    protected function basicExpressions(FcForm $form): array
    {
        $tracker = $form->trackerStorageTable();

        return [
            'login_username' => fc_report_login_username_sql($tracker, $tracker),
            'display_name' => $this->displayNameSql(),
            'service' => $this->serviceSql($form),
            'rank' => $this->rankSql($form),
            'email' => "NULLIF(TRIM(`s1`.`email`), '')",
            'mobile_no' => "NULLIF(TRIM(`s1`.`mobile_no`), '')",
        ];
    }

    /**
     * full_name when the course stores one, else the three name parts — the same rule the photo
     * archive uses, so a trainee is named identically wherever they appear.
     */
    public function displayNameSql(): string
    {
        $parts = [];
        if (fc_schema_has_column('student_master_firsts', 'full_name')) {
            $parts[] = "NULLIF(TRIM(`s1`.`full_name`), '')";
        }
        $parts[] = "NULLIF(CONCAT_WS(' ', "
            ."NULLIF(TRIM(`s1`.`first_name`), ''), "
            ."NULLIF(TRIM(`s1`.`middle_name`), ''), "
            ."NULLIF(TRIM(`s1`.`last_name`), '')), '')";

        return 'COALESCE('.implode(', ', $parts).')';
    }

    /** Short name first, then full name, from whichever of the two service sources is populated. */
    public function serviceSql(FcForm $form): string
    {
        return app(FcDescriptiveDataQuery::class)->serviceNameSql([
            'sources' => [
                's1' => fc_schema_has_column('student_master_firsts', 'service_id'),
                'frm' => $this->rosterIsJoined($form) && fc_schema_has_column('fc_registration_master', 'service_master_pk'),
            ],
        ]);
    }

    /** Rank lives on the roster, which is only joined when the tracker keys on user_id. */
    public function rankSql(FcForm $form): string
    {
        return ($this->rosterIsJoined($form) && fc_schema_has_column('fc_registration_master', 'rank'))
            ? "NULLIF(TRIM(`frm`.`rank`), '')"
            : 'NULL';
    }

    protected function rosterIsJoined(FcForm $form): bool
    {
        return fc_user_col($form->trackerStorageTable()) === 'user_id'
            && fc_schema_has_table('fc_registration_master');
    }

    /** SQL for ORDER BY on one column, or null when the column cannot be ordered. */
    public function orderSql(string $key, FcForm $form): ?string
    {
        return match ($key) {
            'display_name' => $this->displayNameSql(),
            'service' => $this->serviceSql($form),
            'rank' => $this->rankSql($form),
            'email' => 's1.email',
            'mobile_no' => 's1.mobile_no',
            default => $this->reportOrderSql($key, $form),
        };
    }

    /** Subclasses override to make their own columns sortable. */
    protected function reportOrderSql(string $key, FcForm $form): ?string
    {
        return null;
    }

    // ── Filters ──────────────────────────────────────────────────────────────

    /**
     * Completed / pending filter + free-text search.
     *
     * `status` exists because these reports answer two different questions with the same rows:
     * "read what they wrote" wants only the trainees who filled the step in, and "who still has
     * not" wants exactly the others. Default is everyone, so nothing is hidden by surprise.
     */
    public function applyFilters(Builder $query, Request $request): void
    {
        $status = (string) $request->input('f_status', '');
        $columns = $this->statusColumns();

        if ($columns !== [] && $status === 'submitted') {
            // Any one column with content. Equivalent to the old concatenated form: TRIM over
            // the joined string is empty exactly when every part trims to empty.
            $query->where(function ($sub) use ($columns) {
                foreach ($columns as $column) {
                    $sub->orWhereRaw("TRIM({$column}) <> ''");
                }
            });
        } elseif ($columns !== [] && $status === 'pending') {
            // Every column empty or NULL. TRIM(NULL) is NULL, so the null case is named.
            $query->where(function ($sub) use ($columns) {
                foreach ($columns as $column) {
                    $sub->whereRaw("({$column} IS NULL OR TRIM({$column}) = '')");
                }
            });
        }

        $this->applySearch($query, $request);
    }

    /**
     * Identifying columns plus the step's own text — someone looking for "who mentioned a
     * wheelchair" is a real use of these reports, so the free text is searchable too.
     */
    public function applySearch(Builder $query, Request $request): void
    {
        $term = trim((string) ($request->input('search_term') ?: $request->input('search.value') ?: ''));
        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';
        $extra = $this->extraSearchColumns();

        $query->where(function ($sub) use ($like, $extra) {
            foreach (['first_name', 'middle_name', 'last_name', 'full_name', 'email', 'mobile_no'] as $column) {
                if (fc_schema_has_column('student_master_firsts', $column)) {
                    $sub->orWhere("s1.{$column}", 'like', $like);
                }
            }
            foreach ($extra as $qualified) {
                $sub->orWhere($qualified, 'like', $like);
            }
            $sub->orWhere('uc.user_name', 'like', $like);
        });
    }

    /**
     * Narrow the columns to the ones still ticked in the Columns menu.
     *
     * Unknown keys are dropped, and an empty/unrecognised list means "everything" — so an export
     * link built before the menu existed, or hit directly, still returns the full report rather
     * than a sheet of nothing but S.No.
     *
     * @return array<string,array<string,mixed>>
     */
    public function visibleColumns(Request $request): array
    {
        $all = $this->columns();
        $raw = trim((string) $request->input('cols', ''));
        if ($raw === '') {
            return $all;
        }

        $keys = array_filter(array_map('trim', explode(',', $raw)), fn ($k) => $k !== '');
        $visible = array_intersect_key($all, array_flip($keys));

        return $visible === [] ? $all : $visible;
    }
}
