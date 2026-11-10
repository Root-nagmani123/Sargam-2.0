<?php

namespace App\Services\COE;

/**
 * SOP COE/11 (01-07-2015), Moderation of Marks given for assignments:
 *
 *     Mi = (UMi − Xj) × σG / σj + μG
 *
 *   UMi  unmoderated marks of the i-th OT
 *   Xj   mean of the marks awarded by evaluator j to the OTs in their group
 *   σj   standard deviation of the marks awarded by evaluator j
 *   μG   mean of the marks awarded to all OTs for the assignment
 *   σG   standard deviation of the marks awarded to all OTs for the assignment
 *
 * One assignment is one column (drive:subject:component). Absent OTs are left
 * out of every statistic. σ is the population standard deviation (all OTs of the
 * group, not a sample). Where the SOP leaves a case open:
 *   σj = 0  (evaluator gave everyone the same mark, or has one OT) — the marks
 *           are only shifted to the group mean: Mi = UMi − Xj + μG.
 *   result  rounded to 2 decimals and kept within 0..max marks.
 * With a single evaluator, Xj = μG and σj = σG, so every mark stays as it is.
 */
final class MarksModeration
{
    /**
     * @param  array<array-key, array{marks: float, evaluator: string}>  $scores  present OTs only
     * @return array{group: array{mean: float, sd: float, n: int}, evaluators: array<string, array{mean: float, sd: float, n: int}>, moderated: array<array-key, float>}
     */
    public static function compute(array $scores, float $max): array
    {
        $byEvaluator = [];
        foreach ($scores as $s) {
            $byEvaluator[$s['evaluator']][] = (float) $s['marks'];
        }

        $group = self::stats(array_map(fn ($s) => (float) $s['marks'], array_values($scores)));
        $evaluators = array_map([self::class, 'stats'], $byEvaluator);

        $moderated = [];
        foreach ($scores as $key => $s) {
            $e = $evaluators[$s['evaluator']];
            $m = $e['sd'] > 0
                ? ($s['marks'] - $e['mean']) * $group['sd'] / $e['sd'] + $group['mean']
                : $s['marks'] - $e['mean'] + $group['mean'];
            $moderated[$key] = round(min(max($m, 0.0), $max), 2);
        }

        return ['group' => $group, 'evaluators' => $evaluators, 'moderated' => $moderated];
    }

    /**
     * @param  float[]  $marks
     * @return array{mean: float, sd: float, n: int}
     */
    public static function stats(array $marks): array
    {
        $n = count($marks);
        if ($n === 0) {
            return ['mean' => 0.0, 'sd' => 0.0, 'n' => 0];
        }
        $mean = array_sum($marks) / $n;
        $variance = array_sum(array_map(fn ($m) => ($m - $mean) ** 2, $marks)) / $n;

        return ['mean' => $mean, 'sd' => sqrt($variance), 'n' => $n];
    }
}
