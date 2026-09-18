<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\{FromArray, WithColumnWidths, WithEvents, WithHeadings, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Styled .xlsx of ONE officer trainee's attendance (their session list on the
 * OT "Attendance Details" page). The header block + logos + table styling mirror
 * the admin {@see AttendanceDataExport} so both reports look identical.
 */
class OtStudentAttendanceExport implements FromArray, WithColumnWidths, WithEvents, WithHeadings, WithTitle
{
    /** Attendance status => [font colour, fill colour]; mirrors the on-screen badges. */
    private const STATUS_COLOURS = [
        'Present'    => ['027A48', 'ECFDF3'],
        'Late'       => ['B54708', 'FFF6ED'],
        'Absent'     => ['B42318', 'FEF3F2'],
        'Not Marked' => ['B54708', 'FFFAEB'],
    ];

    /** Data rows written by {@see array()}; used for the "Total Records" line and row styling. */
    protected int $rowCount = 0;

    public function __construct(
        protected array $records,
        protected $student = null,
        protected $course = null,
        protected ?string $filterDate = null,
        protected ?string $filterStatus = null
    ) {}

    public function array(): array
    {
        $data = [];
        $serial = 1;

        foreach ($this->records as $r) {
            $dateTime = trim(($r['date'] ?? '') . ' ' . ($r['session_time'] ?? ''));

            $data[] = [
                $serial++,
                $dateTime !== '' ? $dateTime : 'N/A',
                $r['venue'] ?? 'N/A',
                $r['group'] ?? 'N/A',
                $r['topic'] ?? 'N/A',
                $r['faculty'] ?? 'N/A',
                $r['attendance_status'] ?? 'N/A',
                ! empty($r['duty_type']) ? $r['duty_type'] : '-',
                ! empty($r['exemption_type']) ? $r['exemption_type'] : '-',
                ! empty($r['exemption_comment']) ? $r['exemption_comment'] : '-',
            ];
        }

        $this->rowCount = count($data);

        return $data;
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Date & Time',
            'Venue',
            'Group',
            'Topic',
            'Faculty',
            'Attendance Status',
            'Duty Type',
            'Exemption',
            'Doc/ Comment',
        ];
    }

    public function title(): string
    {
        return 'Attendance';
    }

    public function columnWidths(): array
    {
        return ['A' => 8, 'B' => 26, 'C' => 18, 'D' => 12, 'E' => 24, 'F' => 24, 'G' => 18, 'H' => 14, 'I' => 16, 'J' => 34];
    }

    /** Session context shown under the report title. */
    public function filterLine(): string
    {
        $parts = [];
        foreach ([
            'Course'  => $this->course->course_name ?? '',
            'Student' => $this->student->display_name ?? '',
            'OT Code' => $this->student->generated_OT_code ?? '',
        ] as $label => $value) {
            $value = trim((string) $value);
            if ($value !== '' && $value !== 'N/A') {
                $parts[] = $label . ': ' . $value;
            }
        }

        return $parts ? 'Applied Filters:   ' . implode('   |   ', $parts) : '';
    }

    /** Date + status filter line shown under the filter line. */
    public function sessionLine(): string
    {
        $parts = [];

        $date = trim((string) $this->filterDate);
        if ($date !== '') {
            $ts = strtotime($date);
            $parts[] = 'Date: ' . ($ts !== false ? date('d-m-Y', $ts) : $date);
        }

        $status = trim((string) $this->filterStatus);
        if ($status !== '') {
            $parts[] = 'Status: ' . $status;
        }

        return $parts ? implode('   |   ', $parts) : '';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastCol = Coordinate::stringFromColumnIndex(count($this->headings()));

                $metaLines = [
                    ['text' => 'Lal Bahadur Shastri National Academy of Administration, Mussoorie', 'style' => 'inst'],
                    ['text' => 'Attendance Report', 'style' => 'title'],
                ];
                if (($filterLine = $this->filterLine()) !== '') {
                    $metaLines[] = ['text' => $filterLine, 'style' => 'meta'];
                }
                if (($sessionLine = $this->sessionLine()) !== '') {
                    $metaLines[] = ['text' => $sessionLine, 'style' => 'meta'];
                }
                $metaLines[] = [
                    'text'  => 'Generated on: ' . now()->format('d-m-Y H:i') . '   |   Total Records: ' . $this->rowCount,
                    'style' => 'meta',
                ];
                $metaLines[] = ['text' => '', 'style' => 'spacer'];

                // WithHeadings already wrote the column headings at row 1; push them
                // down to make room for the header block above.
                $sheet->insertNewRowBefore(1, count($metaLines));

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
                    // S.No / Group / Attendance Status / Duty / Exemption centre; the
                    // rest stay left-aligned.
                    foreach (['A', 'D', 'G', 'H', 'I'] as $letter) {
                        $sheet->getStyle("{$letter}{$firstDataRow}:{$letter}{$lastDataRow}")
                            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    for ($r = $firstDataRow; $r <= $lastDataRow; $r++) {
                        if (($r - $firstDataRow) % 2 === 1) {
                            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F8');
                        }

                        // Colour-code Attendance Status (column G) to match the badges.
                        $status = trim((string) ($sheet->getCell('G' . $r)->getValue() ?? ''));
                        if (isset(self::STATUS_COLOURS[$status])) {
                            [$fontColour, $fillColour] = self::STATUS_COLOURS[$status];
                            $sheet->getStyle('G' . $r)->getFont()->setBold(true)->getColor()->setRGB($fontColour);
                            $sheet->getStyle('G' . $r)->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fillColour);
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
