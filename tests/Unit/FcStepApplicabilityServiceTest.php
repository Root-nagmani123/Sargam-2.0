<?php

namespace Tests\Unit;

use App\Models\FC\FcFormStep;
use App\Services\FC\FcImportedProfileLockService;
use App\Services\FC\FcStepApplicabilityService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * The PHP half of the applicability rule.
 *
 * FcStepApplicabilityService deliberately implements one rule twice — once in PHP for the
 * trainee flow, once in SQL for reporting — and its own docblock warns that missing the SQL
 * half makes the admin report diverge from the dashboard. These tests pin the PHP half so a
 * future change has to break an assertion rather than a production page.
 *
 * No database: the roster lookup is the service's only I/O and it arrives through the
 * constructor, so it is stubbed. That is what makes these runnable today — phpunit.xml still
 * has no test connection (both DB_CONNECTION lines are commented out), so anything touching
 * the SQL half cannot be covered here. See the parity harness in the PR-256 review for the
 * end-to-end PHP-vs-SQL check that still has to be run by hand.
 */
class FcStepApplicabilityServiceTest extends TestCase
{
    /** Trainees whose roster row carries a ph_value, i.e. Special Assistant applies. */
    private function service(array $userIdsWithPhValue = []): FcStepApplicabilityService
    {
        $lock = new class($userIdsWithPhValue) extends FcImportedProfileLockService
        {
            public function __construct(private array $withPhValue) {}

            public function hasPhValue(int $userId): bool
            {
                return in_array($userId, $this->withPhValue, true);
            }
        };

        return new FcStepApplicabilityService($lock);
    }

    /**
     * A null $rule leaves the attribute UNSET rather than setting it to null — ruleFor()
     * distinguishes the two, and "unset" is what an un-migrated load actually looks like.
     */
    private function step(int $id, string $name, ?string $rule = null, string $col = 'col'): FcFormStep
    {
        $step = new FcFormStep;
        $step->id = $id;
        $step->step_name = $name;
        $step->tracker_column = $col;

        if ($rule !== null) {
            $step->applicability_rule = $rule;
        }

        return $step;
    }

    /** @param  array<int, FcFormStep>  $steps */
    private function collect(array $steps): Collection
    {
        return new Collection($steps);
    }

    // ── ruleFor ──────────────────────────────────────────────────────

    public function test_step_with_no_rule_applies_to_everyone(): void
    {
        $this->assertNull($this->service()->ruleFor($this->step(1, 'Bank Details')));
    }

    public function test_configured_rule_is_used_and_trimmed(): void
    {
        $step = $this->step(1, 'Anything', '  ph_value_present  ');

        $this->assertSame(FcStepApplicabilityService::RULE_PH_VALUE, $this->service()->ruleFor($step));
    }

    /**
     * The pre-migration fallback. On a database where applicability_rule does not exist yet
     * the attribute is absent, and behaviour must not regress to "applies to everyone".
     *
     * @dataProvider legacyStepNames
     */
    public function test_legacy_step_name_match_stands_in_for_a_missing_column(string $name, bool $expected): void
    {
        $rule = $this->service()->ruleFor($this->step(1, $name));

        $this->assertSame($expected ? FcStepApplicabilityService::RULE_PH_VALUE : null, $rule);
    }

    public static function legacyStepNames(): array
    {
        return [
            'exact'                => ['Special Assistant', true],
            'alternate spelling'   => ['Special Assistance', true],
            'case and whitespace'  => ['  SPECIAL ASSISTANT  ', true],
            'unrelated step'       => ['Health Details', false],
            'not a prefix match'   => ['Request Special Assistant', false],
        ];
    }

    /**
     * A blank rule on a loaded column is the admin's explicit "Every trainee" and must win
     * over the step's name. Deriving the rule from the name anyway made the setting
     * impossible to switch off for any step called "Special Assistant".
     */
    public function test_an_explicitly_cleared_rule_beats_the_legacy_name_match(): void
    {
        $step = new FcFormStep;
        $step->id = 1;
        $step->step_name = 'Special Assistant';
        $step->applicability_rule = '';   // column loaded, value blank

        $this->assertNull($this->service()->ruleFor($step));
        $this->assertFalse($this->service()->notApplicable($step, 500));
    }

    /**
     * …but the fallback must still fire when the column was never loaded, which is how an
     * un-migrated database (and any cached payload predating the column) looks.
     */
    public function test_name_match_still_applies_when_the_column_was_not_loaded(): void
    {
        $step = new FcFormStep;
        $step->id = 1;
        $step->step_name = 'Special Assistant';   // no applicability_rule attribute at all

        $this->assertArrayNotHasKey('applicability_rule', $step->getAttributes());
        $this->assertSame(FcStepApplicabilityService::RULE_PH_VALUE, $this->service()->ruleFor($step));
    }

    // ── notApplicable ────────────────────────────────────────────────

    public function test_conditional_step_does_not_apply_without_a_ph_value(): void
    {
        $step = $this->step(1, 'Special Assistant', FcStepApplicabilityService::RULE_PH_VALUE);

        $this->assertTrue($this->service()->notApplicable($step, 500));
        $this->assertFalse($this->service([500])->notApplicable($step, 500));
    }

    public function test_an_unknown_rule_fails_open(): void
    {
        // A rule this version does not understand must never hide a step.
        $step = $this->step(1, 'Future Step', 'some_rule_shipped_later');

        $this->assertFalse($this->service()->notApplicable($step, 500));
    }

    // ── progress / applicableSteps ───────────────────────────────────

    /**
     * The defect this service was written for: a trainee the conditional step does not apply
     * to was stuck at 6/7 forever, because nothing could ever set that tracker column.
     */
    public function test_waived_step_leaves_the_denominator_so_the_trainee_can_reach_complete(): void
    {
        $steps = $this->collect([
            $this->step(1, 'Descriptive Roll'),
            $this->step(2, 'Health Details'),
            $this->step(3, 'Special Assistant', FcStepApplicabilityService::RULE_PH_VALUE),
        ]);
        $done = [1 => true, 2 => true, 3 => false];

        $this->assertSame([2, 2], $this->service()->progress($steps, 500, $done));
        $this->assertSame([2, 3], $this->service([500])->progress($steps, 500, $done));
    }

    /**
     * The other half of the semantics: a step the trainee already filled STAYS in the
     * denominator even once the rule stops applying, so entered data is never hidden.
     */
    public function test_an_already_filled_step_is_never_waived(): void
    {
        $steps = $this->collect([
            $this->step(1, 'Descriptive Roll'),
            $this->step(2, 'Special Assistant', FcStepApplicabilityService::RULE_PH_VALUE),
        ]);

        $this->assertSame([2, 2], $this->service()->progress($steps, 500, [1 => true, 2 => true]));
        $this->assertSame(
            [1, 2],
            $this->service()->applicableSteps($steps, 500, [1 => true, 2 => true])->pluck('id')->all()
        );
    }

    public function test_unconditional_steps_all_count(): void
    {
        $steps = $this->collect([$this->step(1, 'A'), $this->step(2, 'B')]);

        $this->assertSame([1, 2], $this->service()->progress($steps, 500, [1 => true]));
    }

    // ── isCompleteForRow ─────────────────────────────────────────────

    public function test_row_is_complete_when_only_a_non_applicable_step_is_pending(): void
    {
        $steps = $this->collect([
            $this->step(1, 'Descriptive Roll', null, 'step1_done'),
            $this->step(2, 'Special Assistant', FcStepApplicabilityService::RULE_PH_VALUE, 'special_done'),
        ]);
        $row = (object) ['step1_done' => 1, 'special_done' => 0];

        $this->assertTrue($this->service()->isCompleteForRow($steps, $row, 500));
        $this->assertFalse($this->service([500])->isCompleteForRow($steps, $row, 500));
    }

    public function test_row_is_incomplete_when_an_ordinary_step_is_pending(): void
    {
        $steps = $this->collect([
            $this->step(1, 'Descriptive Roll', null, 'step1_done'),
            $this->step(2, 'Health Details', null, 'health_done'),
        ]);
        $row = (object) ['step1_done' => 1, 'health_done' => 0];

        $this->assertFalse($this->service()->isCompleteForRow($steps, $row, 500));
    }

    public function test_an_empty_step_set_is_not_complete(): void
    {
        $this->assertFalse($this->service()->isCompleteForRow($this->collect([]), (object) [], 500));
    }

    /**
     * A tracker_column that is not a bare identifier is skipped rather than interpolated —
     * the whitelist lives inside the service so a caller cannot lose the guarantee.
     */
    public function test_an_unsafe_tracker_column_cannot_hold_a_trainee_back(): void
    {
        $steps = $this->collect([
            $this->step(1, 'Descriptive Roll', null, 'step1_done'),
            $this->step(2, 'Injected', null, 'col`; DROP TABLE x; --'),
        ]);
        $row = (object) ['step1_done' => 1];

        $this->assertTrue($this->service()->isCompleteForRow($steps, $row, 500));
    }

    // ── progress/isCompleteForRow agreement ──────────────────────────

    /**
     * The two entry points must never disagree about the same trainee: the dashboard reads
     * progress(), the per-student report reads isCompleteForRow().
     */
    public function test_progress_and_is_complete_for_row_agree_across_every_combination(): void
    {
        $steps = $this->collect([
            $this->step(1, 'Descriptive Roll', null, 'a_done'),
            $this->step(2, 'Health Details', null, 'b_done'),
            $this->step(3, 'Special Assistant', FcStepApplicabilityService::RULE_PH_VALUE, 'c_done'),
        ]);

        foreach ([[], [500]] as $withPhValue) {
            $service = $this->service($withPhValue);

            for ($mask = 0; $mask < 8; $mask++) {
                $flags = [1 => (bool) ($mask & 1), 2 => (bool) ($mask & 2), 3 => (bool) ($mask & 4)];
                $row = (object) [
                    'a_done' => (int) $flags[1],
                    'b_done' => (int) $flags[2],
                    'c_done' => (int) $flags[3],
                ];

                [$done, $total] = $service->progress($steps, 500, $flags);

                $this->assertSame(
                    $done >= $total && $total > 0,
                    $service->isCompleteForRow($steps, $row, 500),
                    'progress() and isCompleteForRow() disagreed for mask '.$mask
                        .' (ph_value: '.($withPhValue ? 'yes' : 'no').')'
                );
            }
        }
    }
}
