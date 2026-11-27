<?php

namespace Modules\GuestTaClaim\Services;

class ClaimCalculator
{
    /**
     * @param array<int, array{fare_paid?:float|int|string, fare_admissible?:float|int|string, accommodation?:float|int|string}> $allowances
     * @return array{fare_paid:float, fare_admissible:float, accommodation:float}
     */
    public function travellingAllowanceTotals(array $allowances): array
    {
        return [
            'fare_paid' => round(array_sum(array_map(fn ($row) => (float) ($row['fare_paid'] ?? 0), $allowances)), 2),
            'fare_admissible' => round(array_sum(array_map(fn ($row) => (float) ($row['fare_admissible'] ?? 0), $allowances)), 2),
            'accommodation' => round(array_sum(array_map(fn ($row) => (float) ($row['accommodation'] ?? 0), $allowances)), 2),
        ];
    }

    /**
     * @param array<int, array{status:string}> $sessions
     * @return array{sessions_taken:int, honorarium_amount:float, da_amount:float, total_amount:float}
     */
    public function compute(array $sessions, float $rate, float $fare, float $conveyance, int $daDays, float $daRate, int $daPercentage = 100): array
    {
        $taken = count(array_filter($sessions, fn ($s) => ($s['status'] ?? 'taken') === 'taken'));
        $honorarium = round($taken * $rate, 2);
        $da = round($daDays * $daRate * $daPercentage / 100, 2);

        return [
            'sessions_taken' => $taken,
            'honorarium_amount' => $honorarium,
            'da_amount' => $da,
            'total_amount' => round($honorarium + $fare + $conveyance + $da, 2),
        ];
    }
}
