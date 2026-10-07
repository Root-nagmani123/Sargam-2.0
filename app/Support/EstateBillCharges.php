<?php

namespace App\Support;

/**
 * Licence fee / water charge rules for estate bills.
 *
 * Extracted from EstateController's two meter-reading save flows so the money rules can be pinned by
 * tests. The rules themselves are the ones the screens already follow — this class is the single
 * definition of them, not a new policy:
 *
 *  - Define House (estate_house_master) is the master. A bill takes the fee that is current at the
 *    moment it is generated, i.e. when the meter reading is saved.
 *  - Once a bill is issued (verified / notified), its amounts are final. A later Define House change
 *    must not move it; only the next bill picks the new amount up.
 *  - 0, null and '' on the master mean "not configured", exactly as every display path reads them
 *    (`$licence <= 0` → fall back). Such a value never overwrites an amount already on the bill.
 */
class EstateBillCharges
{
    /**
     * Charges to freeze on a bill row, taken from Define House.
     *
     * Returns only the columns that carry a configured amount, so the caller can merge the result over
     * its update/insert payload without clearing anything the bill already holds.
     *
     * @param  mixed  $waterCharge  estate_house_master.water_charge
     * @param  mixed  $licenceFee  estate_house_master.licence_fee
     * @param  bool  $hasWaterColumn  target table has water_charges
     * @param  bool  $hasLicenceColumn  target table has licence_fees
     * @return array{water_charges?: float, licence_fees?: float}
     */
    public static function fromHouseMaster(
        $waterCharge,
        $licenceFee,
        bool $hasWaterColumn = true,
        bool $hasLicenceColumn = true
    ): array {
        $charges = [];

        if ($hasWaterColumn && self::isConfigured($waterCharge)) {
            $charges['water_charges'] = (float) $waterCharge;
        }
        if ($hasLicenceColumn && self::isConfigured($licenceFee)) {
            $charges['licence_fees'] = (float) $licenceFee;
        }

        return $charges;
    }

    /**
     * Has the bill already been issued to the employee?
     *
     * notify_employee_status = 1 is set by "Verify Selected Bills", after which the bill appears in
     * List Bill / Bill Report. Correcting a meter reading on such a row must not re-price it.
     *
     * @param  mixed  $notifyEmployeeStatus
     */
    public static function isIssuedBill($notifyEmployeeStatus): bool
    {
        return (int) ($notifyEmployeeStatus ?? 0) === 1;
    }

    /**
     * An amount counts as configured only when it is present and above zero — 0 is this codebase's
     * "not set" sentinel for these two columns, and estate_house_master defaults both to 0.
     *
     * @param  mixed  $value
     */
    private static function isConfigured($value): bool
    {
        return $value !== null && $value !== '' && (float) $value > 0;
    }
}
