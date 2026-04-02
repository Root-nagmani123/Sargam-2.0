<?php

namespace App\Exports\Mess;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockSummaryViewExport implements FromView, WithStyles, WithEvents, WithTitle
{
    protected array $reportData;
    protected string $fromDate;
    protected string $toDate;
    protected string $storeType;
    protected ?string $selectedStoreName;

    public function __construct(array $reportData, string $fromDate, string $toDate, string $storeType, ?string $selectedStoreName)
    {
        $this->reportData        = $reportData;
        $this->fromDate          = $fromDate;
        $this->toDate            = $toDate;
        $this->storeType         = $storeType;
        $this->selectedStoreName = $selectedStoreName;
    }

    public function view(): View
    {
        return view('admin.mess.reports.excel.stock-summary-excel', [
            'reportData'        => $this->reportData,
            'fromDate'          => $this->fromDate,
            'toDate'            => $this->toDate,
            'storeType'         => $this->storeType,
            'selectedStoreName' => $this->selectedStoreName,
        ]);
    }

    public function title(): string
    {
        return 'Stock Summary';
    }

    public function styles(Worksheet $sheet)
    {
        // Merge header cells (rows 1–3 contain header text from the view)
        $sheet->mergeCells('A1:P1');
        $sheet->mergeCells('A2:P2');
        $sheet->mergeCells('A3:P3');

        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setSize(10);

        // Table header (row 5/6 in the view)
        $headerRange1 = 'A5:P5';
        $headerRange2 = 'A6:P6';
        $sheet->getStyle($headerRange1)->getFont()->setBold(true);
        $sheet->getStyle($headerRange2)->getFont()->setBold(true);
        $sheet->getStyle($headerRange1)->getAlignment()->setHorizontal('center');
        $sheet->getStyle($headerRange2)->getAlignment()->setHorizontal('center');

        // Borders for the table
        $lastRow    = $sheet->getHighestRow();
        $tableRange = "A5:P{$lastRow}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setARGB('FFDEE2E6');

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(6);   // SR No
        $sheet->getColumnDimension('B')->setWidth(26);  // Item Name
        $sheet->getColumnDimension('C')->setWidth(14);  // Item Code
        $sheet->getColumnDimension('D')->setWidth(10);  // Unit
        foreach (range('E', 'P') as $col) {
            $sheet->getColumnDimension($col)->setWidth(12);
        }

        // Right-align numeric columns (E to P)
        $sheet->getStyle("E5:P{$lastRow}")
            ->getAlignment()->setHorizontal('right');

        return [
            1 => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Freeze header region (after header + column titles)
                $sheet->freezePane('A7');

                // Landscape + print area (optional)
                $sheet->getPageSetup()
                    ->setOrientation(
                        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                    )
                    ->setPrintArea("A1:P{$lastRow}");
            },
        ];
    }
}

