<?php

namespace App\Exports\Mess;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockBalanceTillDateExport implements FromCollection, WithCustomStartCell, WithHeadings, WithEvents
{
    protected array $reportData;
    protected string $tillDate;
    protected ?string $storeName;

    public function __construct(array $reportData, string $tillDate, ?string $storeName = null)
    {
        $this->reportData = $reportData;
        $this->tillDate   = $tillDate;
        $this->storeName  = $storeName;
    }

    /** Rows 1–3: LBSNAA banner in AfterSheet; headings at row 5 (no insertNewRowBefore — PhpSpreadsheet cellmap bug). */
    public function startCell(): string
    {
        return 'A5';
    }

    public function collection(): Collection
    {
        $rows = [];
        foreach ($this->reportData as $index => $item) {
            $rows[] = [
                $index + 1,
                $item['item_code'] ?? '—',
                $item['item_name'] ?? '—',
                $item['unit'] ?? '—',
                number_format($item['remaining_qty'] ?? $item['remaining_quantity'] ?? 0, 2),
                number_format($item['rate'] ?? 0, 2),
                number_format($item['amount'] ?? 0, 2),
            ];
        }
        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'S. No.',
            'Item Code',
            'Item Name',
            'Unit',
            'Remaining Quantity',
            'Avg Rate',
            'Amount',
        ];
    }

    /**
     * Add LBSNAA-style header rows above the table and
     * configure print settings so the header repeats on every page.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                // Merge LBSNAA banner rows; headings/data start at row 5 via startCell().
                $sheet->mergeCells('A1:G1');
                $sheet->mergeCells('A2:G2');
                $sheet->mergeCells('A3:G3');

                $formattedDate = \Carbon\Carbon::parse($this->tillDate)->format('d-F-Y');
                $storeLabel    = $this->storeName ?: 'All Stores';

                // Row 1: Mess name
                $sheet->setCellValue('A1', "OFFICER'S MESS LBSNAA MUSSOORIE");

                // Row 2: Report title
                $sheet->setCellValue('A2', 'Stock Balance as of Till Date');

                // Row 3: Date + store
                $sheet->setCellValue(
                    'A3',
                    "As on {$formattedDate} | Store: {$storeLabel}"
                );

                // Basic styling for header
                $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A3')->getFont()->setSize(10);

                // Column headings on row 5; data from row 6.
                $lastRow    = $sheet->getHighestRow();
                $headerRow  = 5;
                $tableRange = "A{$headerRow}:G{$lastRow}";

                // Borders for the table.
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                    ->getColor()->setARGB('FFDEE2E6');

                // Header row styling.
                $sheet->getStyle("A{$headerRow}:G{$headerRow}")
                    ->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:G{$headerRow}")
                    ->getAlignment()->setHorizontal('center');

                // Column widths.
                $sheet->getColumnDimension('A')->setWidth(6);   // S. No.
                $sheet->getColumnDimension('B')->setWidth(14);  // Item Code
                $sheet->getColumnDimension('C')->setWidth(26);  // Item Name
                $sheet->getColumnDimension('D')->setWidth(10);  // Unit
                $sheet->getColumnDimension('E')->setWidth(16);  // Remaining Quantity
                $sheet->getColumnDimension('F')->setWidth(12);  // Avg Rate
                $sheet->getColumnDimension('G')->setWidth(16);  // Amount

                // Right-align numeric columns (E–G).
                $sheet->getStyle("E{$headerRow}:G{$lastRow}")
                    ->getAlignment()->setHorizontal('right');

                // Freeze pane below header + column titles.
                $sheet->freezePane("A" . ($headerRow + 1));

                // Repeat header rows on every printed page.
                $sheet->getPageSetup()
                    ->setRowsToRepeatAtTopByStartAndEnd(1, $headerRow);
            },
        ];
    }
}
