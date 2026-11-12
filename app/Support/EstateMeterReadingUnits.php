<?php

namespace App\Support;

/**
 * Consumed-unit arithmetic for estate electricity meter readings.
 *
 * Extracted from EstateController::storeMeterReadings() so the billing rules can be pinned by tests.
 * The expressions are unchanged — this class is the single definition, not a new rule.
 */
class EstateMeterReadingUnits
{
    /**
     * Opening reading a month's consumption is measured from.
     *
     * Saving a new month from the previous month's row: that row's saved reading is the new month's opening.
     * When the row has no saved reading yet, the previous period supplies the opening reading.
     *
     * PR #349 F-049, Rule A: saving a month's own row again (List Meter Reading → Edit, or the Update Meter
     * Reading dialog) replaces that month's reading, so the month is measured from its opening reading.
     *
     * @param  int|null  $savedCurrentReading  curr_month_elec_red already stored on the form row, null when unset
     * @param  int  $previousPeriodReading  last_month_elec_red, or the possession's opening reading
     * @param  bool  $correctingOwnMonth  the form row is the target month's own row
     */
    public static function baseline(?int $savedCurrentReading, int $previousPeriodReading, bool $correctingOwnMonth = false): int
    {
        return $savedCurrentReading !== null && ! $correctingOwnMonth ? $savedCurrentReading : $previousPeriodReading;
    }

    /**
     * Units consumed in the period.
     *
     * A replaced meter starts from zero, so its reading *is* the consumption. Otherwise units are the
     * rise above the baseline; a reading below the baseline yields 0 rather than a negative charge.
     */
    public static function consumed(?int $newReading, int $baseline, bool $meterReplaced): int
    {
        if ($meterReplaced) {
            return $newReading !== null ? $newReading : 0;
        }

        return ($newReading !== null && $newReading >= $baseline) ? ($newReading - $baseline) : 0;
    }
}
