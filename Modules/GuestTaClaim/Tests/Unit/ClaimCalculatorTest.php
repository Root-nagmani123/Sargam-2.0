<?php

namespace Modules\GuestTaClaim\Tests\Unit;

use Modules\GuestTaClaim\Services\ClaimCalculator;
use Modules\GuestTaClaim\Support\Format;
use PHPUnit\Framework\TestCase;

class ClaimCalculatorTest extends TestCase
{
    public function test_cancelled_sessions_are_not_paid(): void
    {
        $r = (new ClaimCalculator)->compute(
            [['status' => 'taken'], ['status' => 'cancelled'], ['status' => 'taken']],
            3000, 9800, 800, 2, 1500,
        );

        $this->assertSame(2, $r['sessions_taken']);
        $this->assertSame(6000.0, $r['honorarium_amount']);
        $this->assertSame(3000.0, $r['da_amount']);
        $this->assertSame(19600.0, $r['total_amount']);
    }

    public function test_seventy_percent_da_rate_is_applied_to_the_claim_total(): void
    {
        $r = (new ClaimCalculator)->compute(
            [['status' => 'taken']],
            3000, 1000, 500, 2, 1500, 70,
        );

        $this->assertSame(2100.0, $r['da_amount']);
        $this->assertSame(6600.0, $r['total_amount']);
    }

    public function test_multiple_travelling_allowances_are_aggregated_separately(): void
    {
        $totals = (new ClaimCalculator)->travellingAllowanceTotals([
            ['fare_paid' => 1200, 'fare_admissible' => 1000, 'accommodation' => 250],
            ['fare_paid' => 1800, 'fare_admissible' => 1500, 'accommodation' => 400],
        ]);

        $this->assertSame([
            'fare_paid' => 3000.0,
            'fare_admissible' => 2500.0,
            'accommodation' => 650.0,
        ], $totals);
    }

    public function test_indian_money_format(): void
    {
        $this->assertSame('₹12,34,567', Format::money(1234567));
        $this->assertSame('₹950', Format::money(950));
    }
}
