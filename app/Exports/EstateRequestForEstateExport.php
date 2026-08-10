<?php

namespace App\Exports;

use App\DataTables\EstateRequestForEstateDataTable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Branded .xlsx for the Request For Estate listing.
 *
 * The Print view (admin/estate/request_for_estate_print.blade.php) renders from
 * the SAME columnDefs(), so the two downloads always carry the same header row
 * and the same column set — including whatever the user hid in the on-screen
 * "Columns" modal, which arrives as ?cols=.
 */
class EstateRequestForEstateExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle,
    WithCustomStartCell
{
    /** Rows used by the LBSNAA banner before the table's heading row. */
    protected int $headerRows = 5;

    protected Collection $rows;

    /** Ordered column keys to emit (subset of columnDefs()). */
    protected array $cols;

    protected string $filterLine;

    protected string $generatedAt;

    /**
     * The one definition of the export's columns, in table order.
     *
     * Keys are the slugs the index page's Column Visibility modal sends in ?cols=.
     * The table's Action column is deliberately absent — it has never been
     * exportable, so hiding it changes nothing.
     *
     *   heading — column title in both the sheet and the print view
     *   center  — centre-align in both
     *   width   — print-view column width (percent of the table)
     *   value   — cell value for one row; receives ($row, $serial)
     */
    public static function columnDefs(): array
    {
        return [
            'sno' => [
                'heading' => 'S. No.',
                'center' => true,
                'width' => 6,
                'value' => fn ($row, int $serial) => $serial,
            ],
            'req_id' => [
                'heading' => 'Request ID',
                'center' => false,
                'width' => 16,
                'value' => fn ($row) => $row->req_id ?: '-',
            ],
            'req_date' => [
                'heading' => 'Request Date',
                'center' => true,
                'width' => 14,
                'value' => fn ($row) => $row->req_date ? Carbon::parse($row->req_date)->format('d-m-Y') : '-',
            ],
            'name_id' => [
                'heading' => 'Name & ID',
                'center' => false,
                'width' => 32,
                'value' => function ($row) {
                    $name = trim((string) ($row->emp_name ?? ''));
                    $id = trim((string) ($row->employee_id ?? ''));
                    if ($name === '' && $id === '') {
                        return '-';
                    }

                    return $id !== '' ? trim($name . ' - ' . $id) : $name;
                },
            ],
            'status' => [
                'heading' => 'Status',
                'center' => true,
                'width' => 14,
                'value' => fn ($row) => EstateRequestForEstateDataTable::statusLabel($row),
            ],
            'change_req_status' => [
                'heading' => 'Change Request Status',
                'center' => true,
                'width' => 18,
                'value' => fn ($row) => EstateRequestForEstateDataTable::changeRequestLabel($row->change_req_status) ?? '-',
            ],
        ];
    }

    /**
     * Narrow a raw `?cols=` value to the known keys, in definition order.
     * An empty / unparseable value means "everything".
     *
     * @return string[]
     */
    public static function resolveCols(?string $raw): array
    {
        $all = array_keys(static::columnDefs());
        $requested = array_filter(array_map('trim', explode(',', (string) $raw)));
        if (! $requested) {
            return $all;
        }

        $picked = array_values(array_intersect($all, $requested));

        return $picked ?: $all;
    }

    /**
     * @param  string[]|null  $cols  Ordered columns to emit; null = all of them.
     */
    public function __construct(Collection $rows, string $filterLine, string $generatedAt, ?array $cols = null)
    {
        $this->rows = $rows;
        $this->filterLine = $filterLine;
        $this->generatedAt = $generatedAt;
        $this->cols = $cols ?: array_keys(static::columnDefs());
    }

    public function title(): string
    {
        return 'Request For Estate';
    }

    public function startCell(): string
    {
        return 'A' . ($this->headerRows + 1);
    }

    public function headings(): array
    {
        $defs = static::columnDefs();

        return array_map(fn (string $key) => $defs[$key]['heading'], $this->cols);
    }

    public function array(): array
    {
        $defs = static::columnDefs();
        $out = [];
        $serial = 0;

        foreach ($this->rows as $row) {
            $serial++;
            $line = [];
            foreach ($this->cols as $key) {
                $line[] = ($defs[$key]['value'])($row, $serial);
            }
            $out[] = $line;
        }

        return $out;
    }

    /** Sheet letter of the last emitted column. */
    protected function lastColLetter(): string
    {
        return Coordinate::stringFromColumnIndex(max(1, count($this->cols)));
    }

    /** Sheet letter for a column key, or null when it isn't being emitted. */
    protected function colLetter(string $key): ?string
    {
        $i = array_search($key, $this->cols, true);

        return $i === false ? null : Coordinate::stringFromColumnIndex($i + 1);
    }

    public function styles(Worksheet $sheet)
    {
        $defs = static::columnDefs();
        $lastRow = $sheet->getHighestRow();
        $lastCol = $this->lastColLetter();
        $headingRow = $this->headerRows + 1;
        $firstDataRow = $headingRow + 1;

        $sheet->getStyle("A{$headingRow}:{$lastCol}{$headingRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004A93']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '003366']],
            ],
        ]);

        if ($lastRow >= $firstDataRow) {
            $sheet->getStyle("A{$firstDataRow}:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font' => ['size' => 10],
            ]);

            // Zebra striping + the status cell tinted the way its on-screen pill is.
            $statusCol = $this->colLetter('status');
            $row = $firstDataRow;
            foreach ($this->rows as $record) {
                if (($row - $firstDataRow) % 2 === 1) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                    ]);
                }

                if ($statusCol !== null) {
                    [$bg, $fg] = match (EstateRequestForEstateDataTable::statusLabel($record)) {
                        'Allotted' => ['ECFDF3', '027A48'],
                        'Pending' => ['FFFAEB', 'B54708'],
                        'Returned' => ['EFF8FF', '175CD3'],
                        'Rejected' => ['FEF3F2', 'B42318'],
                        default => ['F2F4F7', '475467'],
                    };
                    $sheet->getStyle("{$statusCol}{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $fg]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }

                $row++;
            }

            foreach ($this->cols as $key) {
                if (empty($defs[$key]['center'])) {
                    continue;
                }
                if (($letter = $this->colLetter($key)) !== null) {
                    $sheet->getStyle("{$letter}{$firstDataRow}:{$letter}{$lastRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        }

        $sheet->freezePane('A' . $firstDataRow);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $this->lastColLetter();

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', 'REQUEST FOR ESTATE');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '004A93']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->mergeCells("A3:{$lastCol}3");
                $sheet->setCellValue('A3', $this->filterLine . '  |  Generated: ' . $this->generatedAt);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A4:{$lastCol}4");
                $sheet->setCellValue('A4', 'Total Records: ' . $this->rows->count());
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FA']],
                ]);

                $sheet->getRowDimension(5)->setRowHeight(6);

                $sheet->getStyle("A1:{$lastCol}4")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003366']],
                    ],
                ]);

                $logoPath = public_path('images/lbsnaa_logo.jpg');
                if (file_exists($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('LBSNAA Logo');
                    $drawing->setDescription('LBSNAA Logo');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(50);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(5);
                    $drawing->setOffsetY(2);
                    $drawing->setWorksheet($sheet);
                }
            },
        ];
    }
}
