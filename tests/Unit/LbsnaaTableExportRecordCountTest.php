<?php

namespace Tests\Unit;

use App\Exports\LbsnaaTableExport;
use Tests\TestCase;

/**
 * "Total records" counts data rows only. The House-wise Performance export puts
 * a house band, per-trainee subtotal and Final Marks lines into the same rows,
 * and both the sheet and the PDF used to count them (PR #334 F-026).
 */
class LbsnaaTableExportRecordCountTest extends TestCase
{
    /** One house, one OT with two deductions: band, 2 records, subtotal, final. */
    private function houseRows(): array
    {
        return [
            'rows' => collect([
                ['House A', '', '', '', ''],
                [1, 'Trainee', 'OT1', 'Late', 1],
                ['', '', '', 'Absent', 2],
                ['', '', '', 'Trainee — Total Marks', 3],
                ['', '', '', 'Final Marks — House A', 3],
            ]),
            'sectionRows' => [0],
            'totalRows' => [3, 4],
        ];
    }

    public function test_the_sheet_counts_records_not_bands_or_totals(): void
    {
        $d = $this->houseRows();
        $export = new LbsnaaTableExport($d['rows'], ['S. No.', 'Name', 'OT Code', 'Category', 'Marks'], 'House wise Performance', '', [], null, $d['sectionRows'], $d['totalRows']);

        $this->assertSame(2, $export->recordCount());
    }

    public function test_a_plain_listing_counts_every_row(): void
    {
        $export = new LbsnaaTableExport(collect([[1], [2], [3]]), ['S. No.'], 'Listing');

        $this->assertSame(3, $export->recordCount());
    }

    public function test_the_pdf_counts_records_not_bands_or_totals(): void
    {
        $d = $this->houseRows();
        $html = view('admin.exports.table_pdf', [
            'headings' => ['S. No.', 'Name', 'OT Code', 'Category', 'Marks'],
            'rows' => $d['rows'],
            'reportTitle' => 'House wise Performance',
            'sectionRows' => $d['sectionRows'],
            'totalRows' => $d['totalRows'],
        ])->render();

        $this->assertStringContainsString('Total Records: 2 ', $html);
    }
}
