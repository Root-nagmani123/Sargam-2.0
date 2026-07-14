<?php

namespace App\Exports;

use App\Models\StudentMedicalExemption;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithEvents, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Carbon\Carbon;

/**
 * Styled Excel (.xlsx) export for the Student Medical Exemption listing.
 *
 * A plain CSV can't carry colour/borders/logos, so this builds a formatted
 * workbook whose header block + table styling mirror the Print / PDF layout:
 * institution logos, blue title band, blue column header, bordered zebra rows.
 */
class StudentMedicalExemptionExport implements FromCollection, WithColumnWidths, WithEvents, WithTitle
{
    protected $filter;
    protected $courseFilter;
    protected $fromDateFilter;
    protected $toDateFilter;
    protected $search;
    protected $opdCategoryFilter;
    protected $exemptionCategoryFilter;

    /**
     * Indexes (0-based, matching the listing table's data columns 0..11) the user
     * left visible via the Column Visibility modal, or null when every column shows.
     * Columns 12 (Document) and 13 (Action) are never exported, so callers pass at
     * most 0..11 here.
     *
     * @var array<int,int>|null
     */
    protected ?array $visibleColumns;

    /** Data-row count, captured while streaming the collection (for the meta line). */
    protected int $rowCount = 0;

    public function __construct($filter = 'active', $courseFilter = null, $search = null, $fromDateFilter = null, $toDateFilter = null, $opdCategoryFilter = null, $exemptionCategoryFilter = null)
    {
        $this->filter = $filter;
        $this->courseFilter = $courseFilter;
        $this->search = $search;
        $this->fromDateFilter = $fromDateFilter;
        $this->toDateFilter = $toDateFilter;
        $this->opdCategoryFilter = $opdCategoryFilter;
        $this->exemptionCategoryFilter = $exemptionCategoryFilter;
    }

    public function collection()
    {
        // Prepend a running S.No. so the rows line up with the PDF / Print layouts,
        // then narrow each row to the columns the user left visible.
        $data = $this->recordsQuery()
            ->values()
            ->map(fn ($record, $index) => array_values(
                $this->pickActive(array_merge(['s_no' => $index + 1], $this->mapRecord($record)))
            ));

        $this->rowCount = $data->count();

        return $data;
    }

    public function pdfRows(): Collection
    {
        return $this->recordsQuery()
            ->values()
            ->map(fn ($record, $index) => array_values(
                $this->pickActive(array_merge(['s_no' => $index + 1], $this->mapRecord($record)))
            ));
    }

    public function title(): string
    {
        return 'Medical Exemption';
    }

    /**
     * Column widths tuned to the content, roughly matching the print proportions.
     * Only the active columns are written, mapped onto sequential sheet letters so
     * a hidden column never leaves a gap.
     */
    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->activeColumns() as $i => $col) {
            $widths[Coordinate::stringFromColumnIndex($i + 1)] = $col['width'];
        }

        return $widths;
    }

    /**
     * Build the branded header block + table styling after the data is written.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $columnHeadings = $this->activeHeadings();
                $colCount = count($columnHeadings);                       // ≤ 12
                $lastCol = Coordinate::stringFromColumnIndex($colCount);  // e.g. 'L'

                // --- Meta lines shown above the table (same content as Print/PDF) ---
                $metaLines = [];
                $metaLines[] = ['text' => 'Lal Bahadur Shastri National Academy of Administration, Mussoorie', 'style' => 'inst'];
                $metaLines[] = ['text' => 'Student Medical Exemption', 'style' => 'title'];

                $courseLine = $this->exportCourseLine();
                if ($courseLine !== '') {
                    $metaLines[] = ['text' => $courseLine, 'style' => 'course'];
                }

                $filterLine = $this->exportFilterLine();
                if ($filterLine !== '') {
                    $metaLines[] = ['text' => $filterLine, 'style' => 'meta'];
                }

                $metaLines[] = [
                    'text'  => 'Generated on: ' . now()->format('d-m-Y H:i') . '   |   Total records: ' . $this->rowCount,
                    'style' => 'meta',
                ];
                $metaLines[] = ['text' => '', 'style' => 'spacer'];

                // FromCollection wrote the data at row 1..N. Insert the header block +
                // the column-heading row above it, then fill/style them.
                $headerRows = count($metaLines) + 1;          // meta lines + column heading row
                $sheet->insertNewRowBefore(1, $headerRows);

                $headingRow = count($metaLines) + 1;          // row that holds S.No./Date/…
                $firstDataRow = $headingRow + 1;
                $lastDataRow = $headingRow + max($this->rowCount, 0);

                $sheet->setShowGridlines(false);

                // --- Meta rows: merge across the table width and style per role ---
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
                            $sheet->getRowDimension($r)->setRowHeight(42); // room for the logos
                            break;
                        case 'title':
                            $font->setBold(true)->setSize(16)->getColor()->setRGB('004A93');
                            $sheet->getStyle($range)->getBorders()->getBottom()
                                ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('004A93');
                            $sheet->getRowDimension($r)->setRowHeight(24);
                            break;
                        case 'course':
                            $font->setBold(true)->setSize(10)->getColor()->setRGB('243B53');
                            break;
                        case 'spacer':
                            $sheet->getRowDimension($r)->setRowHeight(6);
                            break;
                        default: // meta
                            $font->setSize(9)->getColor()->setRGB('555555');
                    }
                }

                // --- Column-heading row: blue band, white bold, centred, bordered ---
                foreach ($columnHeadings as $ci => $heading) {
                    $sheet->setCellValueByColumnAndRow($ci + 1, $headingRow, $heading);
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

                // --- Data rows: borders, top-align + wrap, zebra striping ---
                if ($this->rowCount > 0) {
                    $bodyRange = "A{$firstDataRow}:{$lastCol}{$lastDataRow}";
                    $sheet->getStyle($bodyRange)->getFont()->setSize(9);
                    $sheet->getStyle($bodyRange)->getAlignment()
                        ->setVertical(Alignment::VERTICAL_TOP)
                        ->setWrapText(true);
                    // S.No. + Days columns read best centred — locate them among the
                    // active columns so hiding an earlier column shifts the centring too.
                    foreach ($this->activeColumns() as $i => $col) {
                        if (($col['align'] ?? null) === 'center') {
                            $letter = Coordinate::stringFromColumnIndex($i + 1);
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

                // --- Borders around the whole table (heading + body) ---
                $tableBottom = max($lastDataRow, $headingRow);
                $sheet->getStyle("A{$headingRow}:{$lastCol}{$tableBottom}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('8FA3BD');

                // --- Institution logos, floated over the first (institution) row ---
                $this->placeLogo($sheet, public_path('admin_assets/images/logos/logo_new.png'), 'A1', 6);

                $rightLogo = public_path('admin_assets/images/logos/constitution-75.png');
                if (! is_file($rightLogo)) {
                    $rightLogo = public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png');
                }
                $this->placeLogo($sheet, $rightLogo, $lastCol . '1', 2);
            },
        ];
    }

    /** Anchor an image (if it exists) over the given cell, sized to the header row. */
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

    private function recordsQuery()
    {
        $query = StudentMedicalExemption::with(['student', 'category', 'speciality', 'course', 'employee', 'creator']);

        // Filter by course status (Active/Archive)
        $currentDate = now()->format('Y-m-d');

        if ($this->filter === 'active') {
            $query->whereHas('course', function ($q) use ($currentDate) {
                $q->where('end_date', '>', $currentDate);
            });
        } elseif ($this->filter === 'archive') {
            $query->whereHas('course', function ($q) use ($currentDate) {
                $q->where('end_date', '<', $currentDate);
            });
        }

        if ($this->courseFilter) {
            $query->where('course_master_pk', $this->courseFilter);
        }

        if ($this->opdCategoryFilter) {
            $query->where('opd_category', $this->opdCategoryFilter);
        }

        if ($this->exemptionCategoryFilter) {
            $query->where('exemption_category_master_pk', $this->exemptionCategoryFilter);
        }

        if ($this->fromDateFilter || $this->toDateFilter) {
            if ($this->fromDateFilter && $this->toDateFilter) {
                $query->where(function ($q) {
                    $q->where('to_date', '>=', $this->fromDateFilter)
                      ->orWhereNull('to_date');
                })
                ->where('from_date', '<=', $this->toDateFilter);
            } elseif ($this->fromDateFilter) {
                $query->where(function ($q) {
                    $q->where('to_date', '>=', $this->fromDateFilter)
                      ->orWhereNull('to_date');
                });
            } elseif ($this->toDateFilter) {
                $query->where('from_date', '<=', $this->toDateFilter);
            }
        }

        if ($this->search && $this->search != '') {
            $query->where(function ($q) {
                $q->whereHas('student', function ($studentQuery) {
                    $studentQuery->where('display_name', 'like', '%' . $this->search . '%')
                                 ->orWhere('generated_OT_code', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('course', function ($courseQuery) {
                    $courseQuery->where('course_name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('employee', function ($employeeQuery) {
                    $employeeQuery->where('first_name', 'like', '%' . $this->search . '%')
                                  ->orWhere('last_name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('category', function ($categoryQuery) {
                    $categoryQuery->where('exemp_category_name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('speciality', function ($specialityQuery) {
                    $specialityQuery->where('speciality_name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('opd_category', 'like', '%' . $this->search . '%')
                ->orWhere('Description', 'like', '%' . $this->search . '%')
                ->orWhere('pt_outdoor_advise', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('pk', 'desc')->get();
    }

    private function mapRecord($record): array
    {
        $from = $record->from_date ? Carbon::parse($record->from_date) : null;
        $to = $record->to_date ? Carbon::parse($record->to_date) : null;

        $studentName = optional($record->student)->display_name ?? 'N/A';
        $studentOt = optional($record->student)->generated_OT_code;
        $officerTrainee = ($studentOt && $studentName !== 'N/A')
            ? "{$studentName} - {$studentOt}"
            : $studentName;

        $doctorName = 'N/A';
        if ($record->employee && $record->employee->first_name) {
            $doctorName = trim($record->employee->first_name . ' ' . ($record->employee->last_name ?? ''));
        }

        $createdByName = 'N/A';
        if ($record->creator) {
            $name = trim(($record->creator->first_name ?? '') . ' ' . ($record->creator->last_name ?? ''));
            $createdByName = $name !== '' ? $name : ($record->creator->user_name ?? 'N/A');
        }

        $createdOn = $record->created_date ? Carbon::parse($record->created_date)->format('d/m/Y h:i A') : 'N/A';

        $duration = ($from ? $from->format('d/m/Y H:i') : 'N/A')
            . ' - '
            . ($to ? $to->format('d/m/Y H:i') : 'N/A');

        // Same order / names as the on-screen table. Document + Action are excluded
        // from every export so the Excel / PDF match the printed layout column-for-column
        // (Print hides both via .sme-col-no-print).
        return [
            'date' => $from ? $from->format('d/m/Y') : 'N/A',
            'officer_trainee' => $officerTrainee,
            'course' => optional($record->course)->course_name ?? 'N/A',
            'doctor_name' => $doctorName,
            'created_by' => $createdByName,
            'created_on' => $createdOn,
            'medical_speciality' => optional($record->speciality)->speciality_name ?? 'N/A',
            'duration' => $duration,
            'days' => $record->days ?? 'N/A',
            'category' => optional($record->category)->exemp_category_name ?? 'N/A',
            'opd_category' => $record->opd_category ?? 'N/A',
            'pt_outdoor_advise' => $record->pt_outdoor_advise ?: '-',
            'description' => $record->Description ?: '-',
        ];
    }

    /**
     * The flat list of data-column headings (used by the PDF/Print layouts and the
     * Excel column-header band). Must match the listing table headers (minus Action).
     *
     * @return array<int, string>
     */
    public function columnHeadings(): array
    {
        return [
            'Date',
            'Officer Trainee',
            'Course',
            'Doctor Name',
            'Created By',
            'Created On',
            'Medical Speciality',
            'Duration',
            'Days',
            'Category',
            'Medical Case',
            'PT/ Outdoor Advise',
            'Diagnosis / Remarks',
        ];
    }

    /**
     * "Course Name (start to end)" for the selected course filter, or ''.
     */
    private function exportCourseLine(): string
    {
        if (empty($this->courseFilter)) {
            return '';
        }

        $course = \App\Models\CourseMaster::find($this->courseFilter);
        if (! $course) {
            return '';
        }

        $line = (string) ($course->course_name ?? '');
        $start = ! empty($course->start_date ?? $course->start_year ?? null)
            ? Carbon::parse($course->start_date ?? $course->start_year)->format('j F Y') : '';
        $end = ! empty($course->end_date)
            ? Carbon::parse($course->end_date)->format('j F Y') : '';
        if ($start && $end) {
            $line = trim($line . ' (' . $start . ' to ' . $end . ')');
        }

        return $line;
    }

    /**
     * "Applied Filters: …" summary line, mirroring the Print header. Returns ''
     * when only the default status is active.
     */
    private function exportFilterLine(): string
    {
        $parts = ['Status: ' . ($this->filter === 'archive' ? 'Archived' : 'Active')];

        if ($this->search) {
            $parts[] = 'Search: ' . $this->search;
        }
        if ($this->fromDateFilter || $this->toDateFilter) {
            $parts[] = 'Period: ' . ($this->fromDateFilter ?: '…') . ' to ' . ($this->toDateFilter ?: '…');
        }

        return 'Applied Filters:   ' . implode('   |   ', $parts);
    }
}
