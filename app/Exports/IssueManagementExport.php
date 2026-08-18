<?php

namespace App\Exports;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * All Requests → .xlsx
 *
 * Columns are handed in already resolved by
 * IssueManagementController::resolveExportColumns(), which is the same array the
 * CSV, the PDF and the print view use — so hiding a column in the grid's Columns
 * modal drops it from every format, and the four can't drift apart.
 *
 * Styled to match the print/PDF header: logo, navy institution band, report
 * title, generated stamp, record count, then a navy table header over zebra rows.
 *
 * FromQuery + chunking, not FromArray: issue_log_management is 65k rows and
 * hydrating them all at once exhausts memory. ShouldAutoSize is deliberately
 * absent for the same reason — it measures every cell. Widths are set by hand.
 */
class IssueManagementExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithTitle,
    WithCustomStartCell,
    WithCustomChunkSize
{
    /** Rows the branded header occupies before the data table starts. */
    private const HEADER_ROWS = 5;

    /** Column key => sheet width, in characters. */
    private const COLUMN_WIDTHS = [
        'id' => 10,
        'date' => 18,
        'category' => 22,
        'description' => 60,
        'complainant' => 26,
        'nodal' => 26,
        'priority' => 14,
        'status' => 16,
    ];

    /** Total matching rows before the row cap, filled in by query(). */
    private int $matchedTotal = 0;

    /**
     * @param  \Closure(): Builder  $baseQuery  the grid's own filtered query
     * @param  array<string, array{heading:string, class:string, value:callable}>  $columns
     */
    public function __construct(
        private Closure $baseQuery,
        private array $columns = [],
        private string $exportDate = '',
        private int $limit = 0,
        private string $reportTitle = 'All Requests',
        private ?string $filterLine = null
    ) {
        if ($this->exportDate === '') {
            $this->exportDate = now()->format('d-m-Y h:i A');
        }
    }

    public function title(): string
    {
        // Excel sheet names cannot hold : \ / ? * [ ] and stop at 31 chars.
        return mb_substr(preg_replace('~[:\\\\/?*\[\]]~', '-', $this->reportTitle), 0, 31);
    }

    public function startCell(): string
    {
        return 'A' . (self::HEADER_ROWS + 1);
    }

    public function headings(): array
    {
        return array_values(array_map(fn ($col) => $col['heading'], $this->columns));
    }

    /**
     * One sheet row, in the resolved column order.
     */
    public function map($issue): array
    {
        return array_values(array_map(fn ($col) => $col['value']($issue), $this->columns));
    }

    public function chunkSize(): int
    {
        // Small chunks: 65k+ rows with four relations each.
        return 500;
    }

    /**
     * The grid's own filtered query, capped at $limit rows.
     *
     * The cap is a whereIn over the top-N primary keys rather than a limit():
     * Laravel-Excel walks this query with chunk(), which sets its own
     * limit/offset per chunk and would silently drop any limit set here.
     */
    public function query(): Builder
    {
        $query = ($this->baseQuery)()->with(['category', 'priority', 'creator', 'nodal_officer']);

        if ($this->limit > 0) {
            $this->matchedTotal = (int) (clone $query)->toBase()->getCountForPagination();
            if ($this->matchedTotal > $this->limit) {
                $ids = (clone $query)->toBase()->limit($this->limit)->pluck('pk')->all();
                $query->whereIn('pk', $ids);
            }
        }

        return $query;
    }

    private function lastColLetter(): string
    {
        return Coordinate::stringFromColumnIndex(max(1, count($this->columns)));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = $this->lastColLetter();
                $dataHeaderRow = self::HEADER_ROWS + 1;
                // Rows already on the sheet — cheaper and safer than re-counting.
                $lastRow = max($dataHeaderRow, $sheet->getHighestDataRow());
                $bodyRows = $lastRow - $dataHeaderRow;

                // ── Branded header ──
                $sheet->mergeCells("A1:{$last}1");
                $sheet->setCellValue('A1', 'LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                $sheet->mergeCells("A2:{$last}2");
                $sheet->setCellValue('A2', mb_strtoupper($this->reportTitle));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->mergeCells("A3:{$last}3");
                $sheet->setCellValue('A3', $this->metaLine());
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['rgb' => '555555']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A4:{$last}4");
                $sheet->setCellValue('A4', 'Total Records: ' . number_format($bodyRows));
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F8']],
                ]);

                $sheet->getStyle("A1:{$last}4")->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003366']]],
                ]);

                $sheet->getRowDimension(5)->setRowHeight(6);

                // ── Data table ──
                $sheet->getStyle("A{$dataHeaderRow}:{$last}{$dataHeaderRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003366']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($dataHeaderRow)->setRowHeight(22);

                if ($bodyRows > 0) {
                    $sheet->getStyle("A{$dataHeaderRow}:{$last}{$lastRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                    ]);

                    // Range styling is what makes a large sheet crawl, so both of
                    // these stop at 5,000 rows; the data is identical either way.
                    if ($bodyRows <= 5000) {
                        $sheet->getStyle('A' . ($dataHeaderRow + 1) . ":{$last}{$lastRow}")
                            ->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
                    }

                    // Zebra striping, matching the print/PDF output.
                    if ($bodyRows <= 5000) {
                        for ($r = $dataHeaderRow + 1; $r <= $lastRow; $r++) {
                            if (($r - $dataHeaderRow) % 2 === 0) {
                                $sheet->getStyle("A{$r}:{$last}{$r}")->applyFromArray([
                                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                                ]);
                            }
                        }
                    }
                }

                // Widths + centring, by column key (no ShouldAutoSize on 65k rows).
                $index = 1;
                foreach ($this->columns as $key => $col) {
                    $letter = Coordinate::stringFromColumnIndex($index);
                    $sheet->getColumnDimension($letter)->setWidth(self::COLUMN_WIDTHS[$key] ?? 18);
                    if ($key === 'id') {
                        $sheet->getStyle("{$letter}{$dataHeaderRow}:{$letter}{$lastRow}")
                            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                    $index++;
                }

                // ── Logo, floated over the header band ──
                $logoPath = public_path('images/lbsnaa_logo.jpg');
                if (is_file($logoPath) && is_readable($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('LBSNAA');
                    $drawing->setDescription('LBSNAA');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(46);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(6);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                }

                // Keep the branded header and the column titles on screen while scrolling.
                $sheet->freezePane('A' . ($dataHeaderRow + 1));
            },
        ];
    }

    /**
     * "Search: foo  |  Status: Pending  |  Generated: …" for the header band.
     */
    private function metaLine(): string
    {
        $parts = array_filter([$this->filterLine, 'Generated: ' . $this->exportDate]);

        if ($this->limit > 0 && $this->matchedTotal > $this->limit) {
            $parts[] = 'First ' . number_format($this->limit) . ' of ' . number_format($this->matchedTotal)
                . ' matching requests — narrow the filters for the rest';
        }

        return implode('  |  ', $parts);
    }
}
