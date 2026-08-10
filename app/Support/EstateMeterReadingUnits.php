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
     * Normally the saved current reading wins: re-saving a row is treated as taking a later reading,
     * so only the increment is billed. In the "Edit from List Meter Reading" flow the saved value is
     * the one being corrected away, so it is not a baseline — the previous period is.
     *
     * @param  int|null  $savedCurrentReading  curr_month_elec_red already stored on the row, null when unset
     * @param  int  $previousPeriodReading  last_month_elec_red, or the possession's opening reading
     */
    public static function baseline(?int $savedCurrentReading, int $previousPeriodReading, bool $isEditCorrection): int
    {
        return ($savedCurrentReading !== null && ! $isEditCorrection)
            ? $savedCurrentReading
            : $previousPeriodReading;
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
