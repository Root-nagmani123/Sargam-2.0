<?php

namespace App\Exports;

use App\Exports\Concerns\TextValueBinder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// WithStrictNullComparison: a Faculty Count of 0 is a value, not an empty cell.
class StationedLeaveMasterExport implements FromCollection, WithEvents, WithCustomValueBinder, WithStrictNullComparison
{
    /**
     * Report columns in grid order: key => [heading, Excel width, centred].
     * The keys are what the grid sends as ?cols= (see the index view), and the
     * PDF view uses the same keys, so both exports carry the same columns.
     */
    public const COLUMNS = [
        'sno'            => ['S. No.', 10, true],
        'course'         => ['Course', 28, false],
        'effective_from' => ['Effective From', 16, true],
        'pt_timing'      => ['PT Timing', 14, true],
        'approval'       => ['Approval Required', 16, true],
        'faculty_count'  => ['Faculty Count', 14, true],
        'status'         => ['Status', 12, true],
    ];

    protected int $rowCount = 0;

    /** @var list<string> */
    protected array $columns;

    /**
     * @param  list<string>  $columns  keys of COLUMNS to include; empty means all
     */
    public function __construct(
        protected Collection $rows,
        protected string $filterLine = '',
        array $columns = []
    ) {
        $this->rowCount = $rows->count();
        $this->columns = $columns !== [] ? array_values($columns) : array_keys(self::COLUMNS);
    }

    /** One cell's value — shared with the PDF view. */
    public static function cellValue(string $key, $row, int $serial)
    {
        switch ($key) {
            case 'sno':
                return $serial;
            case 'course':
                return $row->course->course_name ?? 'N/A';
            case 'effective_from':
                return $row->effective_from?->format('d-m-Y') ?? 'N/A';
            case 'pt_timing':
                $cutoffTime = $row->course->pt_start_time ?? $row->apply_cutoff_time;
                return blank($cutoffTime) ? 'N/A' : \Carbon\Carbon::parse($cutoffTime)->format('h:i A');
            case 'approval':
                return (int) $row->is_faculty_approval_required === 1 ? 'Yes' : 'No';
            case 'faculty_count':
                return (int) ($row->approvers_count ?? 0);
            case 'status':
                return (int) $row->active_inactive === 1 ? 'Active' : 'Inactive';
        }

        return '';
    }

    public function collection(): Collection
    {
        $serial = 0;

        return $this->rows->map(function ($row) use (&$serial) {
            $serial++;

            return array_map(fn ($key) => self::cellValue($key, $row, $serial), $this->columns);
        });
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $colCount = count($this->columns);
                $lastCol = Coordinate::stringFromColumnIndex($colCount);

                $metaLines = [];
                $metaLines[] = ['text' => 'Lal Bahadur Shastri National Academy of Administration, Mussoorie', 'style' => 'inst'];
                $metaLines[] = ['text' => 'Stationed Leave Master Report', 'style' => 'title'];

                if ($this->filterLine !== '') {
                    $metaLines[] = ['text' => $this->filterLine, 'style' => 'meta'];
                }

                $metaLines[] = [
                    'text' => 'Generated on: ' . now()->format('d-m-Y H:i') . '   |   Total records: ' . $this->rowCount,
                    'style' => 'meta',
                ];
                $metaLines[] = ['text' => '', 'style' => 'spacer'];

                $headerRows = count($metaLines) + 1;
                $sheet->insertNewRowBefore(1, $headerRows);

                $headingRow = count($metaLines) + 1;
                $firstDataRow = $headingRow + 1;
                $lastDataRow = $headingRow + max($this->rowCount, 0);

                $sheet->setShowGridlines(false);

                foreach ($metaLines as $i => $line) {
                    $r = $i + 1;
                    $range = "A{$r}:{$lastCol}{$r}";
                    $sheet->mergeCells($range);
                    $sheet->setCellValue("A{$r}", $line['text']);
                    $sheet->getStyle($range)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    $font = $sheet->getStyle("A{$r}")->getFont();
                    switch ($line['style']) {
                        case 'inst':
                            $font->setBold(true)->setSize(13)->getColor()->setRGB('102A43');
                            $sheet->getRowDimension($r)->setRowHeight(42);
                            break;
                        case 'title':
                            $font->setBold(true)->setSize(16)->getColor()->setRGB('004A93');
                            $sheet->getStyle($range)->getBorders()->getBottom()
                                ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('004A93');
                            $sheet->getRowDimension($r)->setRowHeight(24);
                            break;
                        case 'spacer':
                            $sheet->getRowDimension($r)->setRowHeight(6);
                            break;
                        default:
                            $font->setSize(9)->getColor()->setRGB('555555');
                    }
                }

                foreach ($this->columns as $ci => $key) {
                    $sheet->setCellValueByColumnAndRow($ci + 1, $headingRow, self::COLUMNS[$key][0]);
                }
                $headingRange = "A{$headingRow}:{$lastCol}{$headingRow}";
                $sheet->getStyle($headingRange)->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($headingRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('004A93');
                $sheet->getStyle($headingRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getRowDimension($headingRow)->setRowHeight(26);

                if ($this->rowCount > 0) {
                    $bodyRange = "A{$firstDataRow}:{$lastCol}{$lastDataRow}";
                    $sheet->getStyle($bodyRange)->getFont()->setSize(9);
                    $sheet->getStyle($bodyRange)->getAlignment()
                        ->setVertical(Alignment::VERTICAL_TOP)
                        ->setWrapText(true);

                    foreach ($this->columns as $ci => $key) {
                        if (! self::COLUMNS[$key][2]) {
                            continue;
                        }
                        $letter = Coordinate::stringFromColumnIndex($ci + 1);
                        $sheet->getStyle("{$letter}{$firstDataRow}:{$letter}{$lastDataRow}")
                            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    for ($r = $firstDataRow; $r <= $lastDataRow; $r++) {
                        if (($r - $firstDataRow) % 2 === 1) {
                            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F8');
                        }
                    }
                }

                $tableBottom = max($lastDataRow, $headingRow);
                $sheet->getStyle("A{$headingRow}:{$lastCol}{$tableBottom}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('8FA3BD');

                foreach ($this->columns as $i => $key) {
                    $sheet->getColumnDimensionByColumn($i + 1)->setWidth(self::COLUMNS[$key][1]);
                }

                $logoPath = public_path('admin_assets/images/logos/logo_new.png');
                if (is_file($logoPath) && is_readable($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('LBSNAA');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(36);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(4);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                }
            },
        ];
    }

    public function bindValue(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell, $value): bool
    {
        return (new TextValueBinder)->bindValue($cell, $value);
    }
}
