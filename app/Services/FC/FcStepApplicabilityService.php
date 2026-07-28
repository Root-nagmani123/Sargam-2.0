<?php

namespace App\Services\FC;

use App\Models\FC\FcFormStep;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Single owner of "does this step apply to this trainee?".
 *
 * The rule used to exist as three drifting copies of a step-name match
 * (GenericFormController, FcRegistrationFlowService, and nowhere at all on the
 * reporting side) — which is why a trainee for whom Special Assistant does not
 * apply was permanently stuck at 6/7 "Incomplete" on both the trainee dashboard
 * and the admin report: no code path could ever set that tracker column.
 *
 * Progress semantics, applied identically in PHP and in SQL:
 *
 *   waived(step)      = rule not satisfied AND tracker column <> 1
 *   denominator       = number of steps that are NOT waived
 *   numerator         = number of steps whose tracker column = 1
 *   complete          = no step exists with (column <> 1 AND rule satisfied)
 *
 * A step the trainee already filled always counts in the denominator, even if the
 * rule later stops applying to them — data they entered is never hidden.
 */
class FcStepApplicabilityService
{
    /** Step applies only when the roster row carries fc_registration_master.ph_value. */
    public const RULE_PH_VALUE = 'ph_value_present';

    /** Alias for the roster row inside the applicability EXISTS subquery (scoped to it). */
    private const ROSTER_ALIAS = 'frm_ph';

    /**
     * A tracker_column is interpolated into raw SQL, so it must be a bare identifier.
     * The value is admin-writable through the form builder.
     */
    private const COLUMN_PATTERN = '/^[a-zA-Z0-9_]+$/';

    private const ROSTER_TABLE = 'fc_registration_master';

    /** @var array<string, bool> Per-request memo of hasConditionalSteps(), keyed by step-id set. */
    private array $conditionalMemo = [];

    public function __construct(private FcImportedProfileLockService $importedProfileLock) {}

    // ── Step loading ─────────────────────────────────────────────────

    /**
     * The fc_form_steps columns an applicability-aware consumer needs — never SELECT * (G1).
     *
     * Covers what ruleFor() reads plus what FcRegistrationFlowService::buildStepCompletionByStepId()
     * needs to resolve completion, since every caller that evaluates applicability also evaluates
     * completion.
     *
     * applicability_rule is appended ONLY when the column exists, so this is safe on a database
     * where 2026_07_27_000000 has not run yet: ruleFor() then falls back to the legacy step-name
     * match, which is why step_name is always selected. Selecting the column unguarded would throw
     * SQLSTATE[42S22] whenever code is deployed ahead of its migrations.
     *
     * @param  list<string>  $extra  Extra columns a specific caller renders (e.g. 'icon').
     * @return list<string>
     */
    public static function stepColumns(array $extra = []): array
    {
        $columns = array_merge([
            'id', 'form_id', 'step_number', 'step_name',
            'target_table', 'completion_column', 'tracker_column',
        ], $extra);

        if (fc_schema_has_column('fc_form_steps', 'applicability_rule')) {
            $columns[] = 'applicability_rule';
        }

        return array_values(array_unique($columns));
    }

    // ── Rule resolution ──────────────────────────────────────────────

    /**
     * The applicability rule configured on a step, or null when the step always applies.
     *
     * Falls back to the legacy step-name match when the applicability_rule column has
     * not been migrated yet (or when a cached form-structure payload predates it), so
     * behaviour never regresses on an un-migrated database.
     */
    public function ruleFor(FcFormStep $step): ?string
    {
        $rule = $step->applicability_rule ?? null;

        if (is_string($rule) && trim($rule) !== '') {
            return trim($rule);
        }

        // Fall back to the legacy name match ONLY when the column was not loaded at all —
        // i.e. the migration has not run, or the payload predates it. A column that IS
        // present and blank is an admin's explicit "Every trainee" and must be honoured;
        // deriving the rule from the step name anyway made that setting impossible to turn
        // off for any step called "Special Assistant", and quietly undid the same choice the
        // backfill migration is careful not to overwrite.
        if (array_key_exists('applicability_rule', $step->getAttributes())) {
            return null;
        }

        return $this->legacyRuleFromStepName($step);
    }

    /**
     * Whether any step in the set carries an applicability rule.
     *
     * Memoised per step set: reportRuleIsResolvable() calls this, and ruleSqlFor() calls
     * that once per step — so building one report expression rescanned the whole collection
     * once for every step in it. Cheap at 7 steps, but pointlessly quadratic.
     */
    public function hasConditionalSteps(Collection $steps): bool
    {
        $key = $steps->map(fn (FcFormStep $s) => (string) ($s->id ?? spl_object_id($s)))->implode(',');

        // ??= and not isset(): a memoised `false` must not be recomputed.
        return $this->conditionalMemo[$key] ??= $steps->contains(fn (FcFormStep $s) => $this->ruleFor($s) !== null);
    }

    // ── PHP evaluation (trainee-facing flow) ─────────────────────────

    /**
     * Whether the step simply does not apply to this trainee. Such a step is shown
     * disabled, never blocks the steps after it, and is excluded from progress.
     */
    public function notApplicable(FcFormStep $step, int $userId): bool
    {
        $rule = $this->ruleFor($step);

        return $rule !== null && ! $this->ruleSatisfied($rule, $userId);
    }

    /**
     * Steps that count towards this trainee's progress denominator.
     *
     * @param  Collection<int, FcFormStep>  $steps
     * @param  array<int, bool>  $stepStatus  keyed by fc_form_steps.id
     * @return Collection<int, FcFormStep>
     */
    public function applicableSteps(Collection $steps, int $userId, array $stepStatus = []): Collection
    {
        return $steps->reject(fn (FcFormStep $s) => $this->isWaived($s, $userId, $stepStatus));
    }

    /**
     * Progress for the trainee dashboard: [done, denominator].
     *
     * @param  Collection<int, FcFormStep>  $steps
     * @param  array<int, bool>  $stepStatus  keyed by fc_form_steps.id
     * @return array{0: int, 1: int}
     */
    public function progress(Collection $steps, int $userId, array $stepStatus): array
    {
        $done = $steps->filter(fn (FcFormStep $s) => (bool) ($stepStatus[$s->id] ?? false))->count();

        return [$done, $this->applicableSteps($steps, $userId, $stepStatus)->count()];
    }

    /**
     * A step is waived when its rule does not apply AND the trainee has not already
     * filled it. Waived steps drop out of the denominator entirely.
     *
     * @param  array<int, bool>  $stepStatus
     */
    private function isWaived(FcFormStep $step, int $userId, array $stepStatus = []): bool
    {
        if (! empty($stepStatus[$step->id])) {
            return false;
        }

        return $this->notApplicable($step, $userId);
    }

    private function ruleSatisfied(string $rule, int $userId): bool
    {
        return match ($rule) {
            self::RULE_PH_VALUE => $this->hasPhValue($userId),
            // An unknown rule must never hide a step — fail open.
            default => true,
        };
    }

    /**
     * No memo here on purpose — FcImportedProfileLockService already caches the roster row
     * per user id (rosterRowCache), so a second cache keyed on the same id bought nothing
     * and gave the same fact two places to go stale.
     */
    private function hasPhValue(int $userId): bool
    {
        return $this->importedProfileLock->hasPhValue($userId);
    }

    /**
     * Pre-migration fallback: Special Assistant / Special Assistance (spelling varies
     * across FC form templates) was the only conditional step, matched by name.
     */
    private function legacyRuleFromStepName(FcFormStep $step): ?string
    {
        $name = strtolower(trim((string) ($step->step_name ?? '')));

        return str_starts_with($name, 'special assist') ? self::RULE_PH_VALUE : null;
    }

    // ── SQL evaluation (reporting) ───────────────────────────────────

    /**
     * The step's tracker column, or null when it is not a bare identifier safe to
     * interpolate into raw SQL.
     *
     * Every current caller already whitelists with the same pattern before handing steps to
     * this service, so this changes nothing today. It exists because the guarantee belonged
     * outside the class that depends on it: these expressions build raw SQL, the value is
     * admin-writable through the form builder, and a future caller that forgets to filter
     * should get a skipped step rather than an injection point.
     */
    private function safeTrackerColumn(FcFormStep $step): ?string
    {
        $col = (string) ($step->tracker_column ?? '');

        return preg_match(self::COLUMN_PATTERN, $col) === 1 ? $col : null;
    }

    /**
     * Whether the roster row reached by $probeExpression carries a ph_value.
     *
     * A correlated EXISTS, deliberately NOT a join. fc_registration_master.user_id has only
     * the non-unique index frm_user_id_idx — nothing stops two roster rows sharing a login
     * username, and 50 rows already hold an empty user_id, so the column is plainly not
     * treated as a key. A LEFT JOIN on it would duplicate the trainee's row in the overview
     * list and, worse, inflate the COUNT(*)/SUM() aggregates in overviewSummary(), which
     * count post-join rows. EXISTS can never multiply the driving row.
     *
     * It also reads more truthfully than the join did: "some roster row for this trainee has
     * a ph_value", rather than "the arbitrary row the join happened to pick has one".
     *
     * The probe value (not the indexed column) carries the collation conversion, so the
     * index seek is preserved — see rosterUserIdProbeSql().
     */
    private function rosterHasPhValueSql(string $probeExpression): string
    {
        $probe = $this->rosterUserIdProbeSql($probeExpression);
        $a = self::ROSTER_ALIAS;

        return 'EXISTS (SELECT 1 FROM `'.self::ROSTER_TABLE."` AS `{$a}`"
            ." WHERE `{$a}`.`user_id` = {$probe} AND `{$a}`.`ph_value` IS NOT NULL)";
    }

    /**
     * SQL predicate that is true when the step's rule is satisfied for the row, i.e.
     * the step genuinely applies to that trainee. Returns null when the step always
     * applies or the rule cannot be resolved in SQL (both mean "always applies").
     *
     * @param  Collection<int, FcFormStep>  $steps  every step on the form
     */
    public function ruleSqlFor(FcFormStep $step, Collection $steps, string $trackerTable, ?string $alias = null): ?string
    {
        $rule = $this->ruleFor($step);

        if ($rule === null || ! $this->reportRuleIsResolvable($steps)) {
            return null;
        }

        if ($rule !== self::RULE_PH_VALUE) {
            return null; // Unknown rule — fail open, exactly as ruleSatisfied() does.
        }

        $t = $alias ?? $trackerTable;
        $u = fc_user_col($trackerTable);

        if ($u !== 'user_id') {
            // Tracker holds the login username directly.
            return $this->rosterHasPhValueSql("`{$t}`.`{$u}`");
        }

        // Tracker holds a user_credentials pk, so the roster is reached by login username
        // through `uc` (joined by fc_report_apply_tracker_user_resolution / applySummaryJoins).
        //
        // `frm` is the roster joined by pk — a tracker row may still be keyed by roster pk if
        // it predates FcReconcileRosterIds / the migrate-students rekey. That pk fallback is
        // gated on `uc.user_name IS NULL` so it can only fire when the id is NOT a credentials
        // pk — otherwise a credentials pk that happens to equal an unrelated roster pk would
        // import a stranger's ph_value. Mirrors exactly the guard in
        // FcImportedProfileLockService::rosterRow(), which falls back to pk only when
        // fc_user_name_for_id() resolves nothing.
        // NULL *or* blank — a user_credentials row with an empty user_name resolves no login
        // name at all, exactly as a missing row does. Mirrors the widened test in
        // FcImportedProfileLockService::rosterRow(); the two must agree or the trainee
        // dashboard and the admin report disagree about who a conditional step applies to.
        return '('.$this->rosterHasPhValueSql('uc.user_name')
            ." OR ((`uc`.`user_name` IS NULL OR TRIM(`uc`.`user_name`) = '') AND `frm`.`ph_value` IS NOT NULL))";
    }

    /**
     * `1` when the step counts towards this row's denominator, `0` when waived.
     *
     * @param  Collection<int, FcFormStep>  $steps
     */
    public function stepCountsSql(FcFormStep $step, Collection $steps, string $trackerTable, ?string $alias = null): string
    {
        $t = $alias ?? $trackerTable;
        $col = $this->safeTrackerColumn($step);
        $ruleSql = $col === null ? null : $this->ruleSqlFor($step, $steps, $trackerTable, $alias);

        // Unusable column, or the step always applies — either way it always counts.
        if ($ruleSql === null) {
            return '1';
        }

        return "CASE WHEN `{$t}`.`{$col}` = 1 OR ({$ruleSql}) THEN 1 ELSE 0 END";
    }

    /**
     * `1` when the step is waived for the row (does not apply and is not already filled),
     * `0` otherwise. Drives the "—" cell in the admin step columns.
     *
     * @param  Collection<int, FcFormStep>  $steps
     */
    public function stepWaivedSql(FcFormStep $step, Collection $steps, string $trackerTable, ?string $alias = null): string
    {
        $counts = $this->stepCountsSql($step, $steps, $trackerTable, $alias);

        return $counts === '1' ? '0' : "(1 - ({$counts}))";
    }

    /**
     * SUM() expression for the per-row denominator (steps that are not waived).
     *
     * @param  Collection<int, FcFormStep>  $steps
     */
    public function applicableCountSql(Collection $steps, string $trackerTable, ?string $alias = null): string
    {
        if ($steps->isEmpty()) {
            return '0';
        }

        return $steps
            ->map(fn (FcFormStep $s) => $this->stepCountsSql($s, $steps, $trackerTable, $alias))
            ->implode(' + ');
    }

    /**
     * Constrain a query to rows where every applicable step is done.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  Collection<int, FcFormStep>  $steps
     */
    public function whereComplete($query, Collection $steps, string $trackerTable, ?string $alias = null): void
    {
        $t = $alias ?? $trackerTable;

        foreach ($steps as $step) {
            $col = $this->safeTrackerColumn($step);

            if ($col === null) {
                continue; // Cannot be tested in SQL — do not constrain on it.
            }

            $ruleSql = $this->ruleSqlFor($step, $steps, $trackerTable, $alias);

            if ($ruleSql === null) {
                $query->where("{$t}.{$col}", 1);

                continue;
            }

            // Done, or the step does not apply to this trainee.
            $query->where(function ($q) use ($t, $col, $ruleSql) {
                $q->where("{$t}.{$col}", 1)->orWhereRaw("NOT ({$ruleSql})");
            });
        }
    }

    /**
     * Constrain a query to rows with at least one applicable step still pending.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  Collection<int, FcFormStep>  $steps
     */
    public function whereIncomplete($query, Collection $steps, string $trackerTable, ?string $alias = null): void
    {
        $t = $alias ?? $trackerTable;

        $query->where(function ($outer) use ($steps, $t, $trackerTable, $alias) {
            foreach ($steps as $step) {
                $col = $this->safeTrackerColumn($step);

                if ($col === null) {
                    continue; // Cannot be tested in SQL — contributes no "pending" branch.
                }

                $ruleSql = $this->ruleSqlFor($step, $steps, $trackerTable, $alias);

                $outer->orWhere(function ($q) use ($t, $col, $ruleSql) {
                    $q->where(function ($pending) use ($t, $col) {
                        $pending->where("{$t}.{$col}", '!=', 1)
                            ->orWhereNull("{$t}.{$col}");
                    });

                    if ($ruleSql !== null) {
                        $q->whereRaw($ruleSql);
                    }
                });
            }
        });
    }

    /**
     * Whether the roster-backed rule can be evaluated in SQL at all. When the roster
     * table is missing, every step is treated as applicable — the same fail-open
     * behaviour the PHP path has.
     *
     * @param  Collection<int, FcFormStep>  $steps
     */
    private function reportRuleIsResolvable(Collection $steps): bool
    {
        return $this->hasConditionalSteps($steps)
            && fc_schema_has_table(self::ROSTER_TABLE)
            && fc_schema_has_column(self::ROSTER_TABLE, 'ph_value');
    }

    /**
     * Whether a single trainee has completed every step that applies to them. Used by
     * the per-student report page, which reads one tracker row rather than a query.
     *
     * @param  Collection<int, FcFormStep>  $steps
     */
    public function isCompleteForRow(Collection $steps, object $trackerRow, int $userId): bool
    {
        if ($steps->isEmpty()) {
            return false;
        }

        foreach ($steps as $step) {
            $col = $this->safeTrackerColumn($step);

            if ($col === null) {
                continue; // No usable tracker column — cannot hold the trainee back.
            }

            if ((int) ($trackerRow->{$col} ?? 0) === 1) {
                continue;
            }

            if ($this->notApplicable($step, $userId)) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * One-query summary for the admin overview cards: total rows, complete, incomplete,
     * and the per-step done count with its applicable denominator.
     *
     * Replaces a per-step count() inside a foreach (G5) with a single aggregate.
     *
     * @param  Collection<int, FcFormStep>  $steps
     * @return array<string, int|array{done: int, applicable: int}>
     */
    public function overviewSummary(Collection $steps, string $trackerTable, ?int $formId = null): array
    {
        $selects = [DB::raw('COUNT(*) as total_rows')];

        // Steps whose tracker column is not a bare identifier are dropped: they cannot be
        // aggregated in SQL, and must never be interpolated into it.
        $sqlSteps = $steps->filter(fn (FcFormStep $s) => $this->safeTrackerColumn($s) !== null)->values();

        foreach ($sqlSteps as $step) {
            $col = $this->safeTrackerColumn($step);
            $countsSql = $this->stepCountsSql($step, $steps, $trackerTable);

            $selects[] = DB::raw("SUM(CASE WHEN `{$trackerTable}`.`{$col}` = 1 THEN 1 ELSE 0 END) as `done_{$col}`");
            $selects[] = DB::raw("SUM({$countsSql}) as `appl_{$col}`");
        }

        // complete = zero applicable-but-pending steps on the row
        $pendingParts = $sqlSteps
            ->map(function (FcFormStep $s) use ($steps, $trackerTable) {
                $col = $this->safeTrackerColumn($s);
                $ruleSql = $this->ruleSqlFor($s, $steps, $trackerTable);
                $pending = "(`{$trackerTable}`.`{$col}` <> 1 OR `{$trackerTable}`.`{$col}` IS NULL)";
                $applies = $ruleSql === null ? '' : " AND ({$ruleSql})";

                return "CASE WHEN {$pending}{$applies} THEN 1 ELSE 0 END";
            })
            ->implode(' + ');

        if ($pendingParts !== '') {
            $selects[] = DB::raw("SUM(CASE WHEN ({$pendingParts}) = 0 THEN 1 ELSE 0 END) as complete_rows");
        }

        $query = DB::table($trackerTable);

        if ($formId !== null && fc_schema_has_column($trackerTable, 'form_id')) {
            $query->where('form_id', $formId);
        }

        $this->applySummaryJoins($query, $steps, $trackerTable);

        $row = $query->select($selects)->first();

        $total = (int) ($row->total_rows ?? 0);
        $complete = $steps->isEmpty() ? 0 : (int) ($row->complete_rows ?? 0);

        $summary = [
            'total' => $total,
            'complete' => $complete,
            'incomplete' => max(0, $total - $complete),
        ];

        foreach ($sqlSteps as $step) {
            $col = $this->safeTrackerColumn($step);
            $summary[$col] = [
                'done' => (int) ($row->{'done_'.$col} ?? 0),
                'applicable' => (int) ($row->{'appl_'.$col} ?? 0),
            ];
        }

        return $summary;
    }

    /**
     * The summary query counts the tracker table directly (no report joins), so on the
     * user_id path it must reach `uc` and `frm` itself — ruleSqlFor() names both.
     *
     * Only these two joins, and both are on a primary key (uc.pk / frm.pk = tracker.user_id),
     * so neither can multiply a tracker row. That matters here more than anywhere else: this
     * query's COUNT(*) and SUM() are computed over joined rows, so a fan-out would silently
     * inflate the "total / complete / incomplete" cards. The roster lookup that CANNOT be
     * proven 1:1 is done as an EXISTS inside the expression instead — see rosterHasPhValueSql().
     *
     * Nothing is needed on the legacy-username path: the EXISTS probes the tracker column directly.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  Collection<int, FcFormStep>  $steps
     */
    private function applySummaryJoins($query, Collection $steps, string $trackerTable): void
    {
        if (! $this->reportRuleIsResolvable($steps) || fc_user_col($trackerTable) !== 'user_id') {
            return;
        }

        $query->leftJoin('user_credentials as uc', 'uc.pk', '=', "{$trackerTable}.user_id")
            ->leftJoin(self::ROSTER_TABLE.' as frm', 'frm.pk', '=', "{$trackerTable}.user_id");
    }

    /**
     * Right-hand side of a join against fc_registration_master.user_id.
     *
     * That column is latin1_swedish_ci while user_credentials.user_name is utf8mb4. Left
     * alone, MySQL widens the LATIN1 side to compare them — which wraps the indexed
     * column and turns frm_user_id_idx into a 450-row scan (EXPLAIN: type=index,
     * key used but every row read). Converting the PROBE VALUE down to latin1 instead
     * leaves the indexed column bare and restores a type=ref, rows=1 seek. Verified
     * lossless: no user_credentials.user_name contains a non-ASCII character.
     *
     * This is a workaround for a schema defect. The real remedy is to convert
     * fc_registration_master to utf8mb4, which would fix this join, the existing uc_frm
     * join, and the CONVERT/COLLATE wrappers scattered across the estate reports. The
     * charset check below is deliberate: once the schema is fixed, this returns the
     * plain column and the workaround disappears on its own.
     */
    private function rosterUserIdProbeSql(string $expression): string
    {
        // Memoised only once the charset is actually KNOWN. rosterUserIdCharset() returns
        // null when the lookup fails (cache store down, DB blip); memoising that would pin
        // the un-converted probe for the whole process — indefinitely under a queue worker
        // or Octane — silently disabling the index optimisation this method exists for.
        // Leaving the memo unset means the next call re-probes and self-heals.
        static $needsConversion = null;

        if ($needsConversion === null) {
            $charset = $this->rosterUserIdCharset();

            if ($charset === null) {
                return $expression; // Unknown this time — probe plainly, retry next call.
            }

            $needsConversion = $charset === 'latin1';
        }

        return $needsConversion ? "CONVERT({$expression} USING latin1)" : $expression;
    }

    /**
     * Cached, because information_schema contends under load — the same reason
     * fc_schema_columns() caches Schema::getColumnListing(). Shares its TTL, and a cache
     * failure degrades to a plain join rather than an exception.
     */
    private function rosterUserIdCharset(): ?string
    {
        $ttl = (int) config('fc.schema_cache_ttl', 86400);

        try {
            return Cache::remember(
                'fc_roster_user_id_charset',
                $ttl > 0 ? $ttl : 86400,
                static fn () => DB::table('information_schema.COLUMNS')
                    ->whereRaw('TABLE_SCHEMA = DATABASE()')
                    ->where('TABLE_NAME', self::ROSTER_TABLE)
                    ->where('COLUMN_NAME', 'user_id')
                    ->value('CHARACTER_SET_NAME')
            );
        } catch (\Throwable $e) {
            return null; // Unknown → join plainly; correctness is unaffected either way.
        }
    }
}
