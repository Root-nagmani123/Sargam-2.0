<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProcessMessBillsExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents
{
    protected array $reportRows;
    protected string $dateFrom;
    protected string $dateTo;

    public function __construct(array $reportRows, string $dateFrom, string $dateTo)
    {
        $this->reportRows = $reportRows;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function collection(): Collection
    {
        return collect($this->reportRows);
    }

    public function headings(): array
    {
        return [
            'S.No.',
            'Buyer Name',
            'Invoice No.',
            'Invoice Date',
            'Client Type',
            'Total',
            'Payment Type',
            'Status',
        ];
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Report header (rows 1-4)
                $sheet->setCellValue('A1', 'Process Mess Bills');
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A2', 'Period: ' . $this->dateFrom . ' to ' . $this->dateTo);
                $sheet->mergeCells('A2:H2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A3', 'Generated on: ' . now()->format('d-m-Y H:i:s'));
                $sheet->mergeCells('A3:H3');
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Blank row
                $sheet->setCellValue('A4', '');

                // Column headers styling (row 5)
                $lastCol = 'H';
                $sheet->getStyle('A5:' . $lastCol . '5')
                    ->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '4472C4'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                $lastRow = $sheet->getHighestRow();
                if ($lastRow >= 5) {
                    $sheet->getStyle('A5:' . $lastCol . $lastRow)
                        ->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['argb' => 'FF000000'],
                                ],
                            ],
                        ]);
                }

                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
