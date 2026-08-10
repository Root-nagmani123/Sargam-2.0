<?php

namespace Tests\Unit;

use App\Support\EstateMeterReadingUnits as Units;
use PHPUnit\Framework\TestCase;

/**
 * Pins the consumed-unit rules for estate electricity bills.
 *
 * Previous month's closing reading is 4000 throughout, so a month's consumption should be
 * (entered reading - 4000) unless the meter was replaced.
 */
class EstateMeterReadingUnitsTest extends TestCase
{
    private const PREV = 4000;

    private function units(?int $savedCurr, ?int $entered, bool $isEditCorrection, bool $meterReplaced = false): int
    {
        return Units::consumed(
            $entered,
            Units::baseline($savedCurr, self::PREV, $isEditCorrection),
            $meterReplaced
        );
    }

    /** Direct page: nothing saved yet, so units are measured from last month. */
    public function test_direct_page_first_entry_bills_from_previous_month(): void
    {
        $this->assertSame(500, $this->units(null, 4500, false));
    }

    /** Direct page re-save keeps its long-standing "later reading" semantics — only the increment. */
    public function test_direct_page_resave_bills_only_the_increment(): void
    {
        $this->assertSame(200, $this->units(5000, 5200, false));
    }

    /**
     * The bug this guards: correcting 5000 down to 4500 used to bill 0 units (and a zero charge)
     * because the value being corrected away was still used as the baseline.
     */
    public function test_edit_correction_downward_bills_from_previous_month_not_zero(): void
    {
        $this->assertSame(0, $this->units(5000, 4500, false), 'pre-fix behaviour, kept as documentation');
        $this->assertSame(500, $this->units(5000, 4500, true));
    }

    /** A second correction must not compound: still measured from last month. */
    public function test_repeat_edit_correction_stays_anchored_to_previous_month(): void
    {
        $this->assertSame(600, $this->units(4500, 4600, true));
    }

    /** Correcting upward bills the whole month, not the difference from the wrong value. */
    public function test_edit_correction_upward_bills_the_whole_month(): void
    {
        $this->assertSame(1300, $this->units(5000, 5300, true));
    }

    /** Edit opened on a row with no saved reading behaves exactly like a first entry. */
    public function test_edit_on_unsaved_row_matches_first_entry(): void
    {
        $this->assertSame(
            $this->units(null, 4500, false),
            $this->units(null, 4500, true)
        );
    }

    /** A replaced meter starts at zero, so its reading is the consumption — in both flows. */
    public function test_replaced_meter_bills_the_reading_itself(): void
    {
        $this->assertSame(45, $this->units(5000, 45, false, true));
        $this->assertSame(45, $this->units(5000, 45, true, true));
    }

    /** A reading below the baseline never produces a negative charge. */
    public function test_reading_below_baseline_floors_at_zero(): void
    {
        $this->assertSame(0, $this->units(null, 3000, false));
    }

    /** No reading entered means nothing consumed. */
    public function test_missing_reading_yields_zero(): void
    {
        $this->assertSame(0, $this->units(5000, null, true));
    }
}
