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

    /** Report-side alias for the roster row resolved by login username. */
    private const ROSTER_ALIAS = 'frm_ph';

    private const ROSTER_TABLE = 'fc_registration_master';

    /** @var array<int, bool> Per-request memo of hasPhValue(), keyed by user id. */
    private array $phValueMemo = [];

    public function __construct(private FcImportedProfileLockService $importedProfileLock) {}

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

        return $this->legacyRuleFromStepName($step);
    }

    /** Whether any step in the set carries an applicability rule. */
    public function hasConditionalSteps(Collection $steps): bool
    {
        return $steps->contains(fn (FcFormStep $s) => $this->ruleFor($s) !== null);
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

    private function hasPhValue(int $userId): bool
    {
        return $this->phValueMemo[$userId]
            ??= $this->importedProfileLock->hasPhValue($userId);
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
     * Join whatever the report needs to evaluate applicability per row. No-op when no
     * step in the form is conditional, so unconditional forms gain no extra join.
     *
     * Mirrors fc_report_apply_tracker_user_resolution(): the tracker's user column may
     * hold either a user_credentials pk or a legacy login username, so the roster row is
     * reached through `uc` in the first case and directly in the second.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  Collection<int, FcFormStep>  $steps
     */
    public function applyReportJoins($query, Collection $steps, string $trackerTable, ?string $alias = null): void
    {
        if (! $this->reportRuleIsResolvable($steps)) {
            return;
        }

        $t = $alias ?? $trackerTable;
        $a = self::ROSTER_ALIAS;

        if (fc_user_col($trackerTable) === 'user_id') {
            // `uc` is joined on uc.pk = tracker.user_id by the resolution helper; the
            // roster row is keyed by login username.
            $query->leftJoin(self::ROSTER_TABLE." as {$a}", function ($join) use ($a) {
                $join->on(DB::raw("`{$a}`.`user_id`"), '=', DB::raw($this->rosterUserIdProbeSql('uc.user_name')));
            });

            return;
        }

        $u = fc_user_col($trackerTable);
        $query->leftJoin(self::ROSTER_TABLE." as {$a}", function ($join) use ($a, $t, $u) {
            $join->on(DB::raw("`{$a}`.`user_id`"), '=', DB::raw($this->rosterUserIdProbeSql("`{$t}`.`{$u}`")));
        });
    }

    /**
     * SQL predicate that is true when the step's rule is satisfied for the row, i.e.
     * the step genuinely applies to that trainee. Returns null when the step always
     * applies or the rule cannot be resolved in SQL (both mean "always applies").
     *
     * @param  Collection<int, FcFormStep>  $steps  every step on the form
     */
    public function ruleSqlFor(FcFormStep $step, Collection $steps, string $trackerTable): ?string
    {
        $rule = $this->ruleFor($step);

        if ($rule === null || ! $this->reportRuleIsResolvable($steps)) {
            return null;
        }

        if ($rule !== self::RULE_PH_VALUE) {
            return null; // Unknown rule — fail open, exactly as ruleSatisfied() does.
        }

        $a = self::ROSTER_ALIAS;

        // `frm` (roster joined by pk) only exists on the user_id path; a tracker row may
        // still be keyed by roster pk if it predates FcReconcileRosterIds / the
        // migrate-students rekey.
        //
        // That pk fallback is gated on `uc.user_name IS NULL` so it can only fire when the
        // id is NOT a credentials pk — otherwise a credentials pk that happens to equal an
        // unrelated roster pk would import a stranger's ph_value. Mirrors exactly the
        // guard in FcImportedProfileLockService::rosterRow(), which falls back to pk only
        // when fc_user_name_for_id() resolves nothing.
        return fc_user_col($trackerTable) === 'user_id'
            ? "COALESCE(`{$a}`.`ph_value`, CASE WHEN `uc`.`user_name` IS NULL THEN `frm`.`ph_value` END) IS NOT NULL"
            : "`{$a}`.`ph_value` IS NOT NULL";
    }

    /**
     * `1` when the step counts towards this row's denominator, `0` when waived.
     *
     * @param  Collection<int, FcFormStep>  $steps
     */
    public function stepCountsSql(FcFormStep $step, Collection $steps, string $trackerTable, ?string $alias = null): string
    {
        $t = $alias ?? $trackerTable;
        $col = $step->tracker_column;
        $ruleSql = $this->ruleSqlFor($step, $steps, $trackerTable);

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
            $col = $step->tracker_column;
            $ruleSql = $this->ruleSqlFor($step, $steps, $trackerTable);

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

        $query->where(function ($outer) use ($steps, $t, $trackerTable) {
            foreach ($steps as $step) {
                $col = $step->tracker_column;
                $ruleSql = $this->ruleSqlFor($step, $steps, $trackerTable);

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
            $col = $step->tracker_column;

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

        foreach ($steps as $step) {
            $col = $step->tracker_column;
            $countsSql = $this->stepCountsSql($step, $steps, $trackerTable);

            $selects[] = DB::raw("SUM(CASE WHEN `{$trackerTable}`.`{$col}` = 1 THEN 1 ELSE 0 END) as `done_{$col}`");
            $selects[] = DB::raw("SUM({$countsSql}) as `appl_{$col}`");
        }

        // complete = zero applicable-but-pending steps on the row
        $pendingParts = $steps
            ->map(function (FcFormStep $s) use ($steps, $trackerTable) {
                $col = $s->tracker_column;
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

        foreach ($steps as $step) {
            $col = $step->tracker_column;
            $summary[$col] = [
                'done' => (int) ($row->{'done_'.$col} ?? 0),
                'applicable' => (int) ($row->{'appl_'.$col} ?? 0),
            ];
        }

        return $summary;
    }

    /**
     * The summary query counts the tracker table directly (no report joins), so it must
     * reach the roster itself when a conditional step is present.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  Collection<int, FcFormStep>  $steps
     */
    private function applySummaryJoins($query, Collection $steps, string $trackerTable): void
    {
        if (! $this->reportRuleIsResolvable($steps)) {
            return;
        }

        $a = self::ROSTER_ALIAS;
        $u = fc_user_col($trackerTable);

        if ($u !== 'user_id') {
            $query->leftJoin(self::ROSTER_TABLE." as {$a}", function ($join) use ($a, $trackerTable, $u) {
                $join->on(DB::raw("`{$a}`.`user_id`"), '=', DB::raw($this->rosterUserIdProbeSql("`{$trackerTable}`.`{$u}`")));
            });

            return;
        }

        $query->leftJoin('user_credentials as uc', 'uc.pk', '=', "{$trackerTable}.user_id")
            ->leftJoin(self::ROSTER_TABLE." as {$a}", function ($join) use ($a) {
                $join->on(DB::raw("`{$a}`.`user_id`"), '=', DB::raw($this->rosterUserIdProbeSql('uc.user_name')));
            })
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
        static $needsConversion = null;

        if ($needsConversion === null) {
            $needsConversion = $this->rosterUserIdCharset() === 'latin1';
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
