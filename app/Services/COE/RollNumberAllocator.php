<?php

namespace App\Services\COE;

use App\Models\COE\RollNumberAllocation;
use Illuminate\Support\Collection;

/**
 * Orders participants for roll-number allocation under the seating rules in the
 * Examination module doc:
 *
 *   - participants from the same service are not given consecutive roll numbers;
 *   - for FC Phase-I/II, participants from the same theatre/stream are not consecutive;
 *   - female participants are not grouped together — male and female alternate
 *     "wherever possible".
 *
 * ── Why this is deterministic ──
 * The doc says an allocation that does not satisfy the conditions should be regenerated.
 * Re-rolling only helps if the ordering is random, and a random seating plan cannot be
 * reproduced when someone asks six months later why a participant sat where they did.
 * So this uses the standard largest-remaining-group heuristic instead: at every step it
 * takes the service with the most participants still unplaced, which is what keeps the
 * big services from bunching up at the end. Where that heuristic cannot avoid a clash,
 * no reshuffle of the same input can either — the whole group is one service, or one
 * gender — so rather than regenerating in a loop the allocator RETURNS the residual
 * clashes and lets the caller tell the administrator what could not be satisfied.
 *
 * ── The theatre rule ──
 * It is accepted and reported, never silently applied: this schema carries no
 * theatre/stream mapping for a participant (course_group_type_master has Language /
 * Tutor / House groups, none of which is a theatre), so there is nothing to compare two
 * participants on. The flag is stored on the allocation so the rule starts working the
 * day that mapping exists, and the report says plainly that it did not run.
 */
class RollNumberAllocator
{
    public const RULE_AVOID_SAME_SERVICE = 'avoid_same_service';
    public const RULE_AVOID_CONSECUTIVE_THEATRE = 'avoid_consecutive_theatre';
    public const RULE_ALTERNATE_GENDER = 'alternate_gender';

    /**
     * Does this strategy honour the allocation rules?
     *
     * The sequential strategies deliberately do not: ordering BY service is the exact
     * opposite of keeping services apart, so applying both would be a contradiction. The
     * screen hides the rule checkboxes for them, and this is the server-side half of the
     * same decision.
     */
    public function appliesRules(string $strategy): bool
    {
        return in_array($strategy, [
            RollNumberAllocation::STRATEGY_ALLOCATION_RULES,
            RollNumberAllocation::STRATEGY_RANDOM_WITH_RULES,
        ], true);
    }

    /**
     * Does this strategy take the serial from the participant's OT code instead of from a
     * starting number and a position?
     *
     * Only Sequential by OT Code does. Everything else numbers positionally — the first
     * participant gets the starting number, the next gets one more — so the administrator
     * chooses where the run begins. Here the code decides: A01 is 1, D31 is 31, and a
     * starting number could only push the serials away from the codes they mirror. The
     * screen hides the field on the strength of this, and generate() reads it too, so the
     * two cannot disagree about whether the field was meant to be there.
     */
    public function derivesSerialFromOtCode(string $strategy): bool
    {
        return $strategy === RollNumberAllocation::STRATEGY_SEQUENTIAL_BY_OT_CODE;
    }

    /**
     * The serial an OT code carries, or null when it carries none.
     *
     * The digits at the END of the code are the number — "A01" is 1, "D31" is 31,
     * "AB-007" is 7. Leading zeros are format, not value, so they are dropped here and
     * re-applied by RollNumberAllocation::formatRollNumber() at the run's own padding.
     * A code with no trailing digits, or one whose digits are all zero, has no serial:
     * 0 is not a roll number, and guessing one would put a participant somewhere the code
     * never said.
     */
    public function serialFromOtCode(?string $otCode): ?int
    {
        if (! preg_match('/(\d+)\s*$/', (string) $otCode, $m)) {
            return null;
        }

        $serial = (int) $m[1];

        return $serial > 0 ? $serial : null;
    }

    /**
     * Order participants for numbering.
     *
     * @param  Collection<int, object>  $participants  each needs pk, service_master_pk, gender, display_name, service_name
     * @param  array{avoid_same_service?: bool, avoid_consecutive_theatre?: bool, alternate_gender?: bool}  $rules
     * @param  int|null  $seed  only used by Random with Rules; supplied by tests so a random run can be reproduced
     * @return array{order: array<int, object>, report: array<string, array<string, mixed>>}
     */
    public function allocate(
        Collection $participants,
        array $rules,
        string $strategy = RollNumberAllocation::STRATEGY_ALLOCATION_RULES,
        ?int $seed = null
    ): array {
        $appliesRules = $this->appliesRules($strategy);
        $avoidSameService = $appliesRules && (bool) ($rules[self::RULE_AVOID_SAME_SERVICE] ?? false);
        $alternateGender = $appliesRules && (bool) ($rules[self::RULE_ALTERNATE_GENDER] ?? false);

        $ordered = $this->seedOrder($participants, $strategy, $seed);

        $order = $avoidSameService || $alternateGender
            ? $this->orderByRules($ordered, $avoidSameService, $alternateGender)
            : $ordered->all();

        return [
            'order' => $order,
            'report' => $this->report($order, $rules, $strategy),
        ];
    }

    /**
     * The starting order the chosen strategy asks for.
     *
     * Everything below ends in a pk tiebreaker so that equal names, equal services and an
     * unshuffled input can never leave the order dependent on how the rows came back from
     * the database.
     *
     * @param  Collection<int, object>  $participants
     * @return Collection<int, object>
     */
    private function seedOrder(Collection $participants, string $strategy, ?int $seed): Collection
    {
        switch ($strategy) {
            case RollNumberAllocation::STRATEGY_RANDOM_WITH_RULES:
                // The one strategy that is deliberately NOT reproducible: the doc says an
                // allocation that does not satisfy the conditions should be regenerated,
                // and regenerating only helps if the starting order changes. A seed can be
                // passed in so a run can still be replayed in a test.
                return $participants->shuffle($seed)->values();

            case RollNumberAllocation::STRATEGY_SEQUENTIAL_BY_SERVICE:
                return $participants
                    ->sortBy([
                        fn ($a, $b) => strcasecmp((string) ($a->service_name ?? ''), (string) ($b->service_name ?? '')),
                        fn ($a, $b) => strcasecmp((string) ($a->display_name ?? ''), (string) ($b->display_name ?? '')),
                        fn ($a, $b) => (int) $a->pk <=> (int) $b->pk,
                    ])
                    ->values();

            case RollNumberAllocation::STRATEGY_SEQUENTIAL_BY_OT_CODE:
                // Sorted on the NUMBER the code carries, not the code's text: by text
                // "A9" would follow "A10", and the serial each participant ends up with
                // comes from that number anyway. The code itself breaks a tie between two
                // codes of the same number (A01 vs B01) so the order stays stable, and
                // a code with no number sorts last rather than colliding at zero —
                // generate() refuses the run over those before it gets this far.
                return $participants
                    ->sortBy([
                        fn ($a, $b) => ($this->serialFromOtCode($a->generated_OT_code ?? null) ?? PHP_INT_MAX)
                            <=> ($this->serialFromOtCode($b->generated_OT_code ?? null) ?? PHP_INT_MAX),
                        fn ($a, $b) => strcasecmp((string) ($a->generated_OT_code ?? ''), (string) ($b->generated_OT_code ?? '')),
                        fn ($a, $b) => (int) $a->pk <=> (int) $b->pk,
                    ])
                    ->values();

            case RollNumberAllocation::STRATEGY_SEQUENTIAL_BY_NAME:
                return $participants
                    ->sortBy([
                        fn ($a, $b) => strcasecmp((string) ($a->display_name ?? ''), (string) ($b->display_name ?? '')),
                        fn ($a, $b) => (int) $a->pk <=> (int) $b->pk,
                    ])
                    ->values();

            default:
                // Allocation Rules — a stable input order so two runs over the same
                // participants produce the same plan.
                return $participants->sortBy(fn ($participant) => (int) $participant->pk)->values();
        }
    }

    /**
     * Greedy placement: strongest constraint first, largest remaining service next.
     *
     * @param  Collection<int, object>  $participants
     * @return array<int, object>
     */
    private function orderByRules(Collection $participants, bool $avoidSameService, bool $alternateGender): array
    {
        // Bucketed by service so "which service has the most left" is a count, not a scan.
        $buckets = $participants
            ->groupBy(fn ($participant) => (string) ($participant->service_master_pk ?? ''))
            ->map(fn (Collection $group) => $group->values()->all())
            ->all();

        $order = [];
        $previous = null;
        $total = $participants->count();

        for ($placed = 0; $placed < $total; $placed++) {
            $key = $this->pickBucket($buckets, $previous, $avoidSameService, $alternateGender);

            // array_shift keeps each bucket in its stable pk order.
            $participant = array_shift($buckets[$key]);
            if ($buckets[$key] === []) {
                unset($buckets[$key]);
            }

            $order[] = $participant;
            $previous = $participant;
        }

        return $order;
    }

    /**
     * Choose which service bucket the next participant comes from.
     *
     * Three filters, in the priority the doc gives them:
     *
     *   1. Never repeat the previous service while another one still has participants.
     *      The doc states this one absolutely — "are not seated together".
     *   2. Among what is left, take the service with the most participants still
     *      unplaced. This is the heuristic that SERVES rule 1: drain the big services
     *      early and they cannot bunch up once the small ones run out. Deferring it
     *      would trade a guaranteed clash later for a cosmetic gain now.
     *   3. Only then prefer the opposite gender. The doc qualifies that rule with
     *      "wherever possible", so it is the one that gives way — it picks between
     *      services that are already equally good for rule 1, never against it.
     *
     * @param  array<string, array<int, object>>  $buckets
     */
    private function pickBucket(array $buckets, ?object $previous, bool $avoidSameService, bool $alternateGender): string
    {
        $keys = array_keys($buckets);

        // 1. The absolute rule.
        $allowed = $keys;
        if ($avoidSameService && $previous !== null) {
            $previousKey = (string) ($previous->service_master_pk ?? '');
            $withoutPrevious = array_values(array_diff($keys, [$previousKey]));
            // Only enforce while something else is left — the last participants of a
            // single-service group have nowhere else to go.
            if ($withoutPrevious !== []) {
                $allowed = $withoutPrevious;
            }
        }

        // 2. The largest-remaining heuristic that keeps rule 1 satisfiable.
        $mostRemaining = max(array_map(fn (string $key) => count($buckets[$key]), $allowed));
        $candidates = array_values(array_filter(
            $allowed,
            fn (string $key) => count($buckets[$key]) === $mostRemaining
        ));

        // 3. Gender alternation, between equally good candidates only.
        if ($alternateGender && $previous !== null && count($candidates) > 1) {
            $previousGender = $this->normaliseGender($previous->gender ?? null);
            if ($previousGender !== '') {
                $withOpposite = array_values(array_filter(
                    $candidates,
                    fn (string $key) => $this->normaliseGender($buckets[$key][0]->gender ?? null) !== $previousGender
                ));
                if ($withOpposite !== []) {
                    $candidates = $withOpposite;
                }
            }
        }

        // The service key breaks any remaining tie, so the choice never depends on
        // PHP's array ordering and two runs over the same participants agree.
        sort($candidates, SORT_STRING);

        return $candidates[0];
    }

    /**
     * What the finished order actually achieved, per rule.
     *
     * The adjacency counts are measured either way — a Sequential run still reports how
     * many neighbours share a service, because that is useful to know even when nobody
     * asked for it to be avoided. What changes is `applied`, so the caller does not tell
     * an administrator a rule failed that was never switched on.
     *
     * @param  array<int, object>  $order
     * @param  array<string, bool>  $rules
     * @return array<string, array<string, mixed>>
     */
    private function report(array $order, array $rules, string $strategy): array
    {
        $appliesRules = $this->appliesRules($strategy);
        $sequentialNote = 'The ' . RollNumberAllocation::strategyLabel($strategy)
            . ' strategy orders participants directly, so the allocation rules were not applied.';

        $sameService = 0;
        $sameGender = 0;

        for ($i = 1, $count = count($order); $i < $count; $i++) {
            $previous = $order[$i - 1];
            $current = $order[$i];

            if ((string) ($previous->service_master_pk ?? '') === (string) ($current->service_master_pk ?? '')) {
                $sameService++;
            }

            $previousGender = $this->normaliseGender($previous->gender ?? null);
            $currentGender = $this->normaliseGender($current->gender ?? null);
            if ($previousGender !== '' && $previousGender === $currentGender) {
                $sameGender++;
            }
        }

        return [
            'strategy' => $strategy,
            'applies_rules' => $appliesRules,
            self::RULE_AVOID_SAME_SERVICE => [
                'requested' => (bool) ($rules[self::RULE_AVOID_SAME_SERVICE] ?? false),
                'applied' => $appliesRules && (bool) ($rules[self::RULE_AVOID_SAME_SERVICE] ?? false),
                'violations' => $sameService,
                'reason' => $appliesRules ? null : $sequentialNote,
            ],
            self::RULE_AVOID_CONSECUTIVE_THEATRE => [
                'requested' => (bool) ($rules[self::RULE_AVOID_CONSECUTIVE_THEATRE] ?? false),
                'applied' => false,
                'violations' => null,
                'reason' => $appliesRules
                    ? 'No theatre/stream mapping exists for a participant in this schema, so the rule has nothing to compare.'
                    : $sequentialNote,
            ],
            self::RULE_ALTERNATE_GENDER => [
                'requested' => (bool) ($rules[self::RULE_ALTERNATE_GENDER] ?? false),
                'applied' => $appliesRules && (bool) ($rules[self::RULE_ALTERNATE_GENDER] ?? false),
                'violations' => $sameGender,
                'reason' => $appliesRules ? null : $sequentialNote,
            ],
        ];
    }

    /** Gender is a free-text char(10) in student_master — compare it case- and space-insensitively. */
    private function normaliseGender(?string $gender): string
    {
        return strtolower(trim((string) $gender));
    }
}
