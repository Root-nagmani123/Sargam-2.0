<?php

namespace Tests\Unit;

use App\Support\EstateMeterReadingUnits as Units;
use PHPUnit\Framework\TestCase;

/**
 * Pins the consumed-unit rules for estate electricity bills.
 *
 * A new month is measured from the previous month's saved reading; with nothing saved, the previous
 * period (last month / possession opening) supplies the baseline. Saving a month's own row again —
 * Edit or the Update Meter Reading dialog — measures from that month's opening reading (F-049 Rule A).
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

    /** A new month saved from the previous month's row bills the rise above that row's saved reading. */
    public function test_new_month_bills_the_rise_above_the_previous_months_reading(): void
    {
        $this->assertSame(200, $this->units(5000, 5200));
    }

    /** Rule A, upward re-save of a month's own row: 5000 raised to 5200 bills 1200 from the 4000 opening, not 200. */
    public function test_upward_resave_of_own_month_bills_from_its_opening_reading(): void
    {
        $this->assertSame(1200, Units::consumed(5200, Units::baseline(5000, self::PREV, true), false));
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
     * A new month entered below the previous month's reading yields zero units, not a negative charge
     * (the validator refuses such a save before it gets here).
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
