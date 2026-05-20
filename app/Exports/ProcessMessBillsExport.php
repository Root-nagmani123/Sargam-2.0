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
    public const EXPORT_COLUMNS = [
        0 => 'S.No.',
        1 => 'Buyer Name',
        2 => 'Slip No.',
        3 => 'Invoice Date',
        4 => 'Client Type',
        5 => 'Total',
        6 => 'Payment Type',
        7 => 'Status',
    ];

    protected array $reportRows;
    protected string $dateFrom;
    protected string $dateTo;
    protected array $headings;
    protected string $lastCol;

    public function __construct(array $reportRows, string $dateFrom, string $dateTo, ?array $headings = null)
    {
        $this->reportRows = $reportRows;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->headings = $headings ?? array_values(self::EXPORT_COLUMNS);
        $colCount = max(1, count($this->headings));
        $this->lastCol = self::columnLetterFromCount($colCount);
    }

    public static function parseVisibleColumnIndexes(?string $param): array
    {
        $all = array_keys(self::EXPORT_COLUMNS);
        if ($param === null || trim($param) === '') {
            return $all;
        }
        $indexes = array_values(array_unique(array_map('intval', array_filter(
            explode(',', $param),
            static fn ($v) => $v !== '' && is_numeric(trim($v))
        ))));
        $indexes = array_values(array_intersect($indexes, $all));

        return $indexes ?: $all;
    }

    public static function headingsForIndexes(array $indexes): array
    {
        return array_map(static fn ($i) => self::EXPORT_COLUMNS[$i] ?? '', $indexes);
    }

    public static function filterRowByIndexes(array $row, array $indexes): array
    {
        return array_map(static fn ($i) => $row[$i] ?? '', $indexes);
    }

    public static function columnLetterFromCount(int $count): string
    {
        $index = max(0, $count - 1);
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26) - 1;
        }

        return $letter ?: 'A';
    }

    public function collection(): Collection
    {
        return collect($this->reportRows);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function registerEvents(): array
    {
        $lastCol = $this->lastCol;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($lastCol) {
                $sheet = $event->sheet->getDelegate();

                $sheet->setCellValue('A1', 'Process Mess Bills');
                $sheet->mergeCells('A1:' . $lastCol . '1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A2', 'Period: ' . $this->dateFrom . ' to ' . $this->dateTo);
                $sheet->mergeCells('A2:' . $lastCol . '2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A3', 'Generated on: ' . now()->format('d-m-Y H:i:s'));
                $sheet->mergeCells('A3:' . $lastCol . '3');
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A4', '');

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
