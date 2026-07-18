<?php

namespace App\Exports;

use App\Models\StudentMedicalExemption;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Styled .xlsx of a single Officer Trainee's medical-exemption records
 * (Date · Medical Case · Exemption Category · Remarks), matching the on-screen
 * detail report and the Student Medical Exemption export styling.
 */
class MedicalExemptionReportDetailExport implements FromCollection, WithColumnWidths, WithEvents, WithTitle
{
    protected $studentId;
    protected $courseId;
    protected string $otName;
    protected $categoryFilter;
    protected $medicalCaseFilter;
    protected $search;
    protected $fromDateFilter;
    protected $toDateFilter;

    /** On-screen data-column indexes (0..3) the user left visible, or null for all. */
    protected ?array $visibleColumns;

    protected int $rowCount = 0;

    public function __construct($studentId, $courseId, string $otName = 'Officer Trainee', $categoryFilter = null, $medicalCaseFilter = null, $search = null, $fromDateFilter = null, $toDateFilter = null, ?array $visibleColumns = null)
    {
        $this->studentId = $studentId;
        $this->courseId = $courseId;
        $this->otName = $otName;
        $this->categoryFilter = $categoryFilter;
        $this->medicalCaseFilter = $medicalCaseFilter;
        $this->search = $search;
        $this->fromDateFilter = $fromDateFilter;
        $this->toDateFilter = $toDateFilter;
        $this->visibleColumns = ($visibleColumns === null || $visibleColumns === []) ? null : array_values($visibleColumns);
    }

    /**
     * Data columns keyed by their on-screen table index. S.No is a synthetic
     * row-number column the export always prepends, so it is not in this list.
     *
     * @return array<int,array{key:string,heading:string,width:int,align:?string}>
     */
    private function dataColumns(): array
    {
        return [
            0 => ['key' => 'date',         'heading' => 'Date',               'width' => 14, 'align' => 'center'],
            1 => ['key' => 'medical_case', 'heading' => 'Medical Case',       'width' => 20, 'align' => null],
            2 => ['key' => 'category',     'heading' => 'Exemption Category', 'width' => 26, 'align' => null],
            3 => ['key' => 'remarks',      'heading' => 'Remarks',            'width' => 60, 'align' => null],
        ];
    }

    private function activeDataColumns(): array
    {
        $cols = $this->dataColumns();
        if ($this->visibleColumns === null) {
            return array_values($cols);
        }

        $filtered = [];
        foreach ($cols as $idx => $col) {
            if (in_array($idx, $this->visibleColumns, true)) {
                $filtered[] = $col;
            }
        }

        return $filtered !== [] ? $filtered : array_values($cols);
    }

    /** Data-column headings (no S.No — the PDF view renders that itself). */
    public function activeHeadings(): array
    {
        return array_column($this->activeDataColumns(), 'heading');
    }

    public function collection()
    {
        $active = $this->activeDataColumns();
        $data = $this->records()->values()->map(function ($record, $index) use ($active) {
            $full = $this->mapRecord($record);
            $out = [$index + 1]; // S.No always leads the sheet
            foreach ($active as $col) {
                $out[] = $full[$col['key']] ?? '';
            }

            return $out;
        });

        $this->rowCount = $data->count();

        return $data;
    }

    public function pdfRows(): Collection
    {
        $active = $this->activeDataColumns();

        return $this->records()->values()->map(function ($record) use ($active) {
            $full = $this->mapRecord($record);
            $out = [];
            foreach ($active as $col) {
                $out[$col['key']] = $full[$col['key']] ?? '';
            }

            return $out;
        });
    }

    public function title(): string
    {
        return 'Medical Exemption';
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 8]; // S.No
        foreach ($this->activeDataColumns() as $i => $col) {
            $widths[Coordinate::stringFromColumnIndex($i + 2)] = $col['width'];
        }

        return $widths;
    }

    private function records(): Collection
    {
        return StudentMedicalExemption::with(['category'])
            ->where('student_master_pk', $this->studentId)
            ->where('course_master_pk', $this->courseId)
            ->when($this->fromDateFilter && $this->toDateFilter,
                fn ($q) => $q->whereBetween('from_date', [$this->fromDateFilter, $this->toDateFilter]))
            ->when($this->fromDateFilter && ! $this->toDateFilter,
                fn ($q) => $q->whereDate('from_date', '>=', $this->fromDateFilter))
            ->when(! $this->fromDateFilter && $this->toDateFilter,
                fn ($q) => $q->whereDate('from_date', '<=', $this->toDateFilter))
            ->when($this->categoryFilter, fn ($q) => $q->where('exemption_category_master_pk', $this->categoryFilter))
            ->when($this->medicalCaseFilter, fn ($q) => $q->where('opd_category', $this->medicalCaseFilter))
            ->when($this->search, function ($q) {
                $search = $this->search;
                $q->where(function ($w) use ($search) {
                    $w->where('opd_category', 'like', "%{$search}%")
                        ->orWhere('Description', 'like', "%{$search}%")
                        ->orWhereHas('category', fn ($c) => $c->where('exemp_category_name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('from_date', 'desc')
            ->get();
    }

    private function mapRecord($record): array
    {
        return [
            'date'         => $record->from_date ? Carbon::parse($record->from_date)->format('d/m/Y') : 'N/A',
            'medical_case' => $record->opd_category ?: '-',
            'category'     => optional($record->category)->exemp_category_name ?: '-',
            'remarks'      => $record->Description ?: '-',
        ];
    }

    public function filterLine(): string
    {
        $parts = [];
        if ($this->medicalCaseFilter) {
            $parts[] = 'Medical Case: ' . $this->medicalCaseFilter;
        }
        if ($this->categoryFilter) {
            $cat = \App\Models\ExemptionCategoryMaster::find($this->categoryFilter);
            if ($cat) {
                $parts[] = 'Category: ' . $cat->exemp_category_name;
            }
        }
        if ($this->search) {
            $parts[] = 'Search: ' . $this->search;
        }
        if ($this->fromDateFilter || $this->toDateFilter) {
            $parts[] = 'Period: ' . ($this->fromDateFilter ?: '…') . ' to ' . ($this->toDateFilter ?: '…');
        }

        return $parts ? 'Applied Filters:   ' . implode('   |   ', $parts) : '';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $columnHeadings = array_merge(['S.No.'], $this->activeHeadings());
                $lastCol = Coordinate::stringFromColumnIndex(count($columnHeadings));

                $metaLines = [
                    ['text' => 'Lal Bahadur Shastri National Academy of Administration, Mussoorie', 'style' => 'inst'],
                    ['text' => $this->otName . '’s Medical Exemption Report', 'style' => 'title'],
                ];
                if (($filterLine = $this->filterLine()) !== '') {
                    $metaLines[] = ['text' => $filterLine, 'style' => 'meta'];
                }
                $metaLines[] = [
                    'text'  => 'Generated on: ' . now()->format('d-m-Y H:i') . '   |   Total records: ' . $this->rowCount,
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
                            $font->setBold(true)->setSize(15)->getColor()->setRGB('004A93');
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

                foreach ($columnHeadings as $ci => $heading) {
                    $sheet->setCellValueByColumnAndRow($ci + 1, $headingRow, $heading);
                }
                $headingRange = "A{$headingRow}:{$lastCol}{$headingRow}";
                $sheet->getStyle($headingRange)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($headingRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('004A93');
                $sheet->getStyle($headingRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                $sheet->getRowDimension($headingRow)->setRowHeight(24);

                if ($this->rowCount > 0) {
                    $bodyRange = "A{$firstDataRow}:{$lastCol}{$lastDataRow}";
                    $sheet->getStyle($bodyRange)->getFont()->setSize(10);
                    $sheet->getStyle($bodyRange)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    foreach ($this->activeDataColumns() as $i => $col) {
                        if (($col['align'] ?? null) === 'center') {
                            $letter = Coordinate::stringFromColumnIndex($i + 2); // +2: S.No occupies column A
                            $sheet->getStyle("{$letter}{$firstDataRow}:{$letter}{$lastDataRow}")
                                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
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

                $this->placeLogo($sheet, public_path('admin_assets/images/logos/logo_new.png'), 'A1', 6);
                $rightLogo = public_path('admin_assets/images/logos/constitution-75.png');
                if (! is_file($rightLogo)) {
                    $rightLogo = public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png');
                }
                $this->placeLogo($sheet, $rightLogo, $lastCol . '1', 2);
            },
        ];
    }

    private function placeLogo($sheet, string $path, string $coordinates, int $offsetX): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            return;
        }
        $drawing = new Drawing();
        $drawing->setPath($path);
        $drawing->setHeight(48);
        $drawing->setCoordinates($coordinates);
        $drawing->setOffsetX($offsetX);
        $drawing->setOffsetY(3);
        $drawing->setWorksheet($sheet);
    }
}
