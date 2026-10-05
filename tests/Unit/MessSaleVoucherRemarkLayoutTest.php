<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;

/**
 * Covers the Remark column layout on the Sale Voucher Report (Category-wise Print Slip).
 *
 * One buyer section on that report flattens every voucher for the buyer into a single table,
 * so several distinct vouchers commonly land on it with the same request date. The remark cell
 * is merged with a rowspan across consecutive rows; the merge key therefore has to include the
 * voucher, not just the date. Keyed on the date alone, a remark typed on one voucher is rendered
 * across the rows of every other voucher issued that day.
 *
 * Deliberately database-free, matching the other unit tests here: these helpers are pure, and the
 * application's legacy tables have no factories to seed a voucher from.
 *
 * Run with:  php vendor/bin/phpunit --filter=MessSaleVoucherRemarkLayout
 */
class MessSaleVoucherRemarkLayoutTest extends TestCase
{
    /**
     * Build a display row of the shape mess_cw_slip_section_display_rows() produces.
     */
    private function row(string $requestNo, string $issueDate, string $remarks): object
    {
        $voucher = new class($requestNo, $remarks, $issueDate)
        {
            public $request_no;

            public $remarks;

            public $issue_date;

            public function __construct($requestNo, $remarks, $issueDate)
            {
                $this->request_no = $requestNo;
                $this->remarks = $remarks;
                $this->issue_date = Carbon::createFromFormat('d-m-Y', $issueDate)->startOfDay();
            }

            public function getKey()
            {
                return 0;
            }
        };

        return (object) [
            'kind' => 'item',
            'voucher' => $voucher,
            'item' => (object) ['issue_date' => null],
        ];
    }

    /**
     * @param  array<int, array{show: bool, rowspan: int, remark: string}>  $layout
     * @return array<int, string>
     */
    private function remarks(array $layout): array
    {
        return array_map(static fn ($cell) => $cell['remark'], $layout);
    }

    public function test_remark_stays_on_its_own_voucher_when_vouchers_share_a_request_date(): void
    {
        $rows = collect([
            $this->row('SV-017715', '21-09-2026', 'Birthday party'),
            $this->row('SV-017714', '21-09-2026', ''),
            $this->row('SV-001117', '21-09-2026', ''),
        ]);

        $layout = mess_cw_slip_section_remark_layout($rows);

        $this->assertSame(
            ['21-09-2026 → Birthday party', '—', '—'],
            $this->remarks($layout),
            "A remark entered on one voucher must not appear on other vouchers sharing its date."
        );

        foreach ($layout as $cell) {
            $this->assertTrue($cell['show']);
            $this->assertSame(1, $cell['rowspan']);
        }
    }

    public function test_multiple_items_on_one_voucher_still_merge_into_a_single_remark_cell(): void
    {
        $rows = collect([
            $this->row('SV-000900', '21-09-2026', 'Party'),
            $this->row('SV-000900', '21-09-2026', 'Party'),
            $this->row('SV-000900', '21-09-2026', 'Party'),
        ]);

        $layout = mess_cw_slip_section_remark_layout($rows);

        $this->assertTrue($layout[0]['show']);
        $this->assertFalse($layout[1]['show']);
        $this->assertFalse($layout[2]['show']);
        $this->assertSame(3, $layout[0]['rowspan']);
        $this->assertSame('21-09-2026 → Party', $layout[0]['remark']);
    }

    public function test_same_voucher_number_from_different_sources_is_not_merged(): void
    {
        // Selling Voucher 17 and Kitchen Issue 17 share a numeric id but are distinct vouchers.
        $rows = collect([
            $this->row('SV-000017', '21-09-2026', 'Mess remark'),
            $this->row('KI-000017', '21-09-2026', ''),
        ]);

        $layout = mess_cw_slip_section_remark_layout($rows);

        $this->assertSame(['21-09-2026 → Mess remark', '—'], $this->remarks($layout));
    }

    public function test_one_voucher_spanning_two_dates_splits_its_remark_per_date(): void
    {
        $rows = collect([
            $this->row('SV-000900', '21-09-2026', 'Party'),
            $this->row('SV-000900', '21-08-2026', 'Party'),
        ]);

        $layout = mess_cw_slip_section_remark_layout($rows);

        $this->assertSame(
            ['21-09-2026 → Party', '21-08-2026 → Party'],
            $this->remarks($layout)
        );
        $this->assertSame(1, $layout[0]['rowspan']);
        $this->assertSame(1, $layout[1]['rowspan']);
    }
}
