<?php

namespace Tests\Unit;

use App\Support\EstateBillCharges as Charges;
use PHPUnit\Framework\TestCase;

/**
 * Pins the licence fee / water charge rules for estate bills.
 *
 * The rule the screens follow: Define House is the master, a bill takes the fee that is current when
 * it is generated (the meter-reading save), and an issued bill never moves afterwards. These tests
 * fail against the pre-fix code on two counts — an unconditional write re-priced issued bills, and a
 * master value of 0 was written over an amount the bill already carried.
 */
class EstateBillChargesTest extends TestCase
{
    /** Define House ka amount naye bill par jaata hai — yahi PR ka asli fix hai. */
    public function test_configured_master_amounts_are_frozen_on_the_bill(): void
    {
        $this->assertSame(
            ['water_charges' => 20.0, 'licence_fees' => 500.0],
            Charges::fromHouseMaster('20.00', '500', true, true)
        );
    }

    /** Decimal strings from the database become floats, not strings. */
    public function test_master_amounts_are_cast_to_float(): void
    {
        $charges = Charges::fromHouseMaster('20.50', '499.99');

        $this->assertSame(20.5, $charges['water_charges']);
        $this->assertSame(499.99, $charges['licence_fees']);
    }

    /**
     * 0 is this codebase's "not configured" sentinel — estate_house_master defaults both columns to
     * it and every display path reads `<= 0` as unset. It must not overwrite the bill's own amount.
     */
    public function test_zero_master_amount_is_treated_as_not_configured(): void
    {
        $this->assertSame([], Charges::fromHouseMaster(0, 0));
        $this->assertSame([], Charges::fromHouseMaster('0.00', '0'));
    }

    /** Missing values behave the same way — the bill keeps whatever it already holds. */
    public function test_missing_master_amounts_are_skipped(): void
    {
        $this->assertSame([], Charges::fromHouseMaster(null, null));
        $this->assertSame([], Charges::fromHouseMaster('', ''));
    }

    /** A negative amount is never billed. */
    public function test_negative_master_amount_is_skipped(): void
    {
        $this->assertSame([], Charges::fromHouseMaster(-5, -100));
    }

    /** Only one side configured → only that column is written. */
    public function test_each_column_is_decided_independently(): void
    {
        $this->assertSame(['licence_fees' => 660.0], Charges::fromHouseMaster(0, 660));
        $this->assertSame(['water_charges' => 20.0], Charges::fromHouseMaster(20, null));
    }

    /** A column the target table does not have is never written to. */
    public function test_absent_columns_are_never_written(): void
    {
        $this->assertSame(['licence_fees' => 500.0], Charges::fromHouseMaster(20, 500, false, true));
        $this->assertSame(['water_charges' => 20.0], Charges::fromHouseMaster(20, 500, true, false));
        $this->assertSame([], Charges::fromHouseMaster(20, 500, false, false));
    }

    /** Verify Selected Bills sets notify_employee_status = 1 — that bill is issued and final. */
    public function test_verified_bill_counts_as_issued(): void
    {
        $this->assertTrue(Charges::isIssuedBill(1));
        $this->assertTrue(Charges::isIssuedBill('1'));
    }

    /** Draft rows — not yet verified — are the ones a save may still price. */
    public function test_draft_bill_is_not_issued(): void
    {
        $this->assertFalse(Charges::isIssuedBill(0));
        $this->assertFalse(Charges::isIssuedBill('0'));
        $this->assertFalse(Charges::isIssuedBill(null));
    }

    /**
     * The two rules together, as the controller applies them: an issued bill keeps its amount even
     * when Define House has since changed; a draft picks the new amount up.
     */
    public function test_issued_bill_keeps_its_amount_while_a_draft_takes_the_new_one(): void
    {
        $billRow = ['licence_fees' => 1620.0];
        $master = Charges::fromHouseMaster(20, 3500);

        $issued = Charges::isIssuedBill(1) ? $billRow : array_merge($billRow, $master);
        $draft = Charges::isIssuedBill(0) ? $billRow : array_merge($billRow, $master);

        $this->assertSame(1620.0, $issued['licence_fees']);
        $this->assertSame(3500.0, $draft['licence_fees']);
    }
}
