<?php

namespace App\Exports\Mess;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockPurchaseDetailsExport implements FromView, WithStyles, WithEvents, WithTitle
{
    /** @var \Illuminate\Support\Collection<int, array{vendor_id: int, vendor_name: string, vendor: mixed, orders: mixed}> */
    protected $purchaseOrdersByVendor;
    protected $fromDate;
    protected $toDate;
    /** @var \Illuminate\Support\Collection<int, \App\Models\Mess\Vendor> */
    protected $selectedVendors;
    /** @var \Illuminate\Support\Collection<int, \App\Models\Mess\Store> */
    protected $selectedStores;

    public function __construct($purchaseOrdersByVendor, $fromDate, $toDate, $selectedVendors, $selectedStores)
    {
        $this->purchaseOrdersByVendor = $purchaseOrdersByVendor;
        $this->fromDate        = $fromDate;
        $this->toDate          = $toDate;
        $this->selectedVendors = $selectedVendors;
        $this->selectedStores  = $selectedStores;
    }

    public function view(): View
    {
        return view('admin.mess.reports.excel.stock-purchase-details-excel', [
            'purchaseOrdersByVendor' => $this->purchaseOrdersByVendor,
            'fromDate'        => $this->fromDate,
            'toDate'          => $this->toDate,
            'selectedVendors' => $this->selectedVendors,
            'selectedStores'  => $this->selectedStores,
        ]);
    }

    public function title(): string
    {
        return 'Stock Purchase Details';
    }


    public function styles(Worksheet $sheet)
    {
        // Merge header cells (rows 1–5 contain header text from the view)
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');
        $sheet->mergeCells('A4:H4');
        $sheet->mergeCells('A5:H5');

        $sheet->getStyle('A1:A5')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3:A5')->getFont()->setSize(10);

        // Table header (row 7 in the view)
        $headerRange = 'A7:H7';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal('center');
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0066CC');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

        // Borders for the table
        $lastRow    = $sheet->getHighestRow();
        $tableRange = "A7:H{$lastRow}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setARGB('FFDEE2E6');

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(14);

        // Right-align numeric columns
        $sheet->getStyle("D7:H{$lastRow}")
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

                // Style bill header rows and grand total
                for ($row = 8; $row <= $lastRow; $row++) {
                    $cellValue = $sheet->getCell("A{$row}")->getValue();
                    
                    // Bill header rows (dark background, white text)
                    if (is_string($cellValue) && strpos($cellValue, 'Bill No.') !== false) {
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FF5A6268'],
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['argb' => 'FFFFFFFF'],
                            ],
                        ]);
                    }
                    
                    // Grand Total row (blue background, white text)
                    if (is_string($cellValue) && $sheet->getCell("G{$row}")->getValue() === 'Grand Total:') {
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FF004A93'],
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['argb' => 'FFFFFFFF'],
                            ],
                        ]);
                    }
                }

                // Freeze header region
                $sheet->freezePane('A8');

                // Landscape + print area (optional)
                $sheet->getPageSetup()
                    ->setOrientation(
                        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                    )
                    ->setPrintArea("A1:H{$lastRow}");
            },
        ];
    }
}
