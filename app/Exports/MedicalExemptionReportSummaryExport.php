<?php

namespace App\Exports;

use App\Models\CourseMaster;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Styled .xlsx of the Medical Exemption Report summary — one row per Officer
 * Trainee (student × course) with a count of their exemptions. The header block +
 * table styling mirror the Student Medical Exemption export for a consistent look.
 */
class MedicalExemptionReportSummaryExport implements FromCollection, WithColumnWidths, WithEvents, WithTitle
{
    protected $courseFilter;
    protected $search;
    protected $fromDateFilter;
    protected $toDateFilter;

    /** On-screen data-column indexes (1..3) the user left visible, or null for all. */
    protected ?array $visibleColumns;

    /** Course-status tab: 'active' (running) or 'archive' (ended). */
    protected string $status;

    protected int $rowCount = 0;

    public function __construct($courseFilter = null, $search = null, $fromDateFilter = null, $toDateFilter = null, ?array $visibleColumns = null, string $status = 'active')
    {
        $this->courseFilter = $courseFilter;
        $this->search = $search;
        $this->fromDateFilter = $fromDateFilter;
        $this->toDateFilter = $toDateFilter;
        $this->visibleColumns = ($visibleColumns === null || $visibleColumns === []) ? null : array_values($visibleColumns);
        $this->status = $status === 'archive' ? 'archive' : 'active';
    }

    /**
     * Data columns keyed by their on-screen table index (S.No at index 0 is always
     * kept as the row-number column, so it is not part of this toggle list).
     *
     * @return array<int,array{key:string,heading:string,width:int,align:?string}>
     */
    private function dataColumns(): array
    {
        return [
            1 => ['key' => 'ot_name',         'heading' => 'OT Name',            'width' => 40, 'align' => null],
            2 => ['key' => 'course_name',     'heading' => 'Course Name',        'width' => 42, 'align' => null],
            3 => ['key' => 'exemption_count', 'heading' => 'Medical Exemptions', 'width' => 20, 'align' => 'center'],
        ];
    }

    /** The visible subset of {@see dataColumns()}, in order (all when unfiltered). */
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
        $data = $this->records()->values()->map(function ($row, $index) use ($active) {
            $full = $this->fullRow($row);
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

        return $this->records()->values()->map(function ($row) use ($active) {
            $full = $this->fullRow($row);
            $out = [];
            foreach ($active as $col) {
                $out[$col['key']] = $full[$col['key']] ?? '';
            }

            return $out;
        });
    }

    /** Full display values for one grouped record, keyed for column selection. */
    private function fullRow($row): array
    {
        return [
            'ot_name'         => $row->ot_name,
            'course_name'     => $row->course_name,
            'exemption_count' => str_pad((string) $row->exemption_count, 2, '0', STR_PAD_LEFT),
        ];
    }

    public function title(): string
    {
        return 'Medical Exemption Report';
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 8]; // S.No
        foreach ($this->activeDataColumns() as $i => $col) {
            $widths[Coordinate::stringFromColumnIndex($i + 2)] = $col['width'];
        }

        return $widths;
    }

    /** Grouped student × course rows, honouring the report filters. */
    private function records(): Collection
    {
        $currentDate = now()->format('Y-m-d');

        return DB::table('student_medical_exemption as sme')
            ->join('student_master as s', 's.pk', '=', 'sme.student_master_pk')
            ->join('course_master as c', 'c.pk', '=', 'sme.course_master_pk')
            ->when($this->status === 'active', fn ($q) => $q->whereDate('c.end_date', '>=', $currentDate))
            ->when($this->status === 'archive', fn ($q) => $q->whereDate('c.end_date', '<', $currentDate))
            ->when($this->courseFilter, fn ($q) => $q->where('sme.course_master_pk', $this->courseFilter))
            ->when($this->fromDateFilter && $this->toDateFilter,
                fn ($q) => $q->whereBetween('sme.from_date', [$this->fromDateFilter, $this->toDateFilter]))
            ->when($this->fromDateFilter && ! $this->toDateFilter,
                fn ($q) => $q->whereDate('sme.from_date', '>=', $this->fromDateFilter))
            ->when(! $this->fromDateFilter && $this->toDateFilter,
                fn ($q) => $q->whereDate('sme.from_date', '<=', $this->toDateFilter))
            ->when($this->search, function ($q) {
                $search = $this->search;
                $q->where(function ($w) use ($search) {
                    $w->where('s.display_name', 'like', "%{$search}%")
                        ->orWhere('s.generated_OT_code', 'like', "%{$search}%")
                        ->orWhere('c.course_name', 'like', "%{$search}%");
                });
            })
            ->groupBy('s.display_name', 's.generated_OT_code', 'c.course_name')
            ->orderBy('s.display_name')
            ->select(
                DB::raw("TRIM(CONCAT(COALESCE(s.display_name, ''), CASE WHEN s.generated_OT_code IS NOT NULL AND s.generated_OT_code <> '' THEN CONCAT(' - ', s.generated_OT_code) ELSE '' END)) as ot_name"),
                'c.course_name',
                DB::raw('COUNT(sme.pk) as exemption_count')
            )
            ->get();
    }

    public function filterLine(): string
    {
        $parts = ['Status: ' . ($this->status === 'archive' ? 'Archived' : 'Active')];
        if ($this->courseFilter) {
            $course = CourseMaster::find($this->courseFilter);
            if ($course) {
                $parts[] = 'Course: ' . $course->course_name;
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
                    ['text' => 'Medical Exemption Report', 'style' => 'title'],
                ];
                if (($filterLine = $this->filterLine()) !== '') {
                    $metaLines[] = ['text' => $filterLine, 'style' => 'meta'];
                }
                $metaLines[] = [
                    'text'  => 'Generated on: ' . now()->format('d-m-Y H:i') . '   |   Total OTs: ' . $this->rowCount,
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
                    $sheet->getStyle($bodyRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
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
