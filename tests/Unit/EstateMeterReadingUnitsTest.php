<?php

namespace Tests\Unit;

use App\Support\EstateMeterReadingUnits as Units;
use PHPUnit\Framework\TestCase;

/**
 * Pins the consumed-unit rules for estate electricity bills.
 *
 * The rule the screen shows: Unit = New Meter Reading − Electric Meter Reading, where the
 * "Electric Meter Reading" column is the reading already saved on the row. Only when nothing is
 * saved yet does the previous period (last month / possession opening) supply the baseline, and an
 * Edit correction of the month's own reading measures from that opening reading too (F-049).
 */
class EstateMeterReadingUnitsTest extends TestCase
{
    private const PREV = 4000;

    private function units(?int $savedCurr, ?int $entered, bool $meterReplaced = false): int
    {
        return Units::consumed($entered, Units::baseline($savedCurr, self::PREV), $meterReplaced);
    }

    /** First entry of the month: nothing saved, so units come off last month's reading. */
    public function test_first_entry_bills_from_previous_month(): void
    {
        $this->assertSame(500, $this->units(null, 4500));
    }

    /** A saved reading is the baseline, so re-saving bills only the rise above it. */
    public function test_resave_bills_the_rise_above_the_saved_reading(): void
    {
        $this->assertSame(200, $this->units(5000, 5200));
    }

    /**
     * PR #349 F-049 Rule A: correcting a month's own saved reading through List Meter Reading → Edit
     * replaces it, so the month is measured from its opening reading (4000), not the replaced 5000.
     */
    public function test_edit_correction_measures_from_the_months_opening_reading(): void
    {
        $this->assertSame(1300, Units::consumed(5300, Units::baseline(5000, self::PREV, true), false));
    }

    /** Rule A, downward: correcting 5000 down to 4500 bills 500 units, not 0. */
    public function test_downward_edit_correction_bills_the_rise_above_the_opening_reading(): void
    {
        $this->assertSame(500, Units::consumed(4500, Units::baseline(5000, self::PREV, true), false));
    }

    /**
     * Outside an Edit correction, a value below the saved reading yields zero units, not a negative
     * charge (the validator refuses such a save before it gets here).
     */
    public function test_entering_below_the_saved_reading_yields_zero_units(): void
    {
        $this->assertSame(0, $this->units(5000, 4500));
    }

    /** A replaced meter starts at zero, so its reading is the consumption. */
    public function test_replaced_meter_bills_the_reading_itself(): void
    {
        $this->assertSame(45, $this->units(5000, 45, true));
    }

    /** Below the previous-period baseline on a first entry also floors at zero. */
    public function test_reading_below_baseline_floors_at_zero(): void
    {
        $this->assertSame(0, $this->units(null, 3000));
    }

    /** No reading entered means nothing consumed. */
    public function test_missing_reading_yields_zero(): void
    {
        $this->assertSame(0, $this->units(5000, null));
    }

    /** Exactly at the baseline is zero consumption, not a negative. */
    public function test_reading_equal_to_baseline_yields_zero(): void
    {
        $this->assertSame(0, $this->units(5000, 5000));
    }
}
