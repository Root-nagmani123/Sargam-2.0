<?php

namespace Tests\Unit;

use App\Services\COE\MarksModeration;
use PHPUnit\Framework\TestCase;

/** SOP COE/11: Mi = (UMi − Xj) × σG / σj + μG, worked by hand below. */
class CoeMarksModerationTest extends TestCase
{
    public function test_two_evaluators_are_moderated_onto_the_group_mean_and_spread(): void
    {
        // A: 60, 70, 80 → Xj 70, σj √(200/3) ≈ 8.165
        // B: 40, 50     → Xj 45, σj 5
        // G: all five   → μG 60, σG √200 ≈ 14.142
        $out = MarksModeration::compute([
            'a1' => ['marks' => 60, 'evaluator' => 'A'],
            'a2' => ['marks' => 70, 'evaluator' => 'A'],
            'a3' => ['marks' => 80, 'evaluator' => 'A'],
            'b1' => ['marks' => 40, 'evaluator' => 'B'],
            'b2' => ['marks' => 50, 'evaluator' => 'B'],
        ], 100);

        $this->assertEqualsWithDelta(60.0, $out['group']['mean'], 1e-9);
        $this->assertEqualsWithDelta(sqrt(200), $out['group']['sd'], 1e-9);
        $this->assertSame(['a1' => 42.68, 'a2' => 60.0, 'a3' => 77.32, 'b1' => 45.86, 'b2' => 74.14], $out['moderated']);
    }

    public function test_a_single_evaluator_leaves_every_mark_as_it_is(): void
    {
        $out = MarksModeration::compute([
            1 => ['marks' => 55.5, 'evaluator' => 'A'],
            2 => ['marks' => 62, 'evaluator' => 'A'],
            3 => ['marks' => 71.25, 'evaluator' => 'A'],
        ], 80);

        $this->assertSame([1 => 55.5, 2 => 62.0, 3 => 71.25], $out['moderated']);
    }

    public function test_an_evaluator_with_no_spread_is_only_shifted_to_the_group_mean(): void
    {
        // A gave everyone 50 (σj = 0); μG = (50 + 50 + 30 + 70) / 4 = 50.
        $out = MarksModeration::compute([
            1 => ['marks' => 50, 'evaluator' => 'A'],
            2 => ['marks' => 50, 'evaluator' => 'A'],
            3 => ['marks' => 30, 'evaluator' => 'B'],
            4 => ['marks' => 70, 'evaluator' => 'B'],
        ], 100);

        $this->assertSame(50.0, $out['moderated'][1]);
        $this->assertSame(50.0, $out['moderated'][2]);
    }

    public function test_moderated_marks_stay_within_zero_and_max(): void
    {
        // B's lone top mark lands far above A's spread → capped at max.
        $out = MarksModeration::compute([
            1 => ['marks' => 10, 'evaluator' => 'A'],
            2 => ['marks' => 90, 'evaluator' => 'A'],
            3 => ['marks' => 49, 'evaluator' => 'B'],
            4 => ['marks' => 50, 'evaluator' => 'B'],
        ], 50);

        $this->assertSame(50.0, max($out['moderated']));
        $this->assertGreaterThanOrEqual(0.0, min($out['moderated']));
    }
}
