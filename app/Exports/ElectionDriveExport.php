<?php

namespace App\Exports;

use App\Http\Controllers\Admin\Master\ElectionDriveController;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Download for the Election Drive grid. Same columns and source rows as the
 * print sheet, honouring the Active/Archived pill and the course filter.
 */
class ElectionDriveExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    private int $serial = 0;

    public function __construct(private string $status = 'active', private $coursePk = null)
    {
    }

    public function collection()
    {
        return ElectionDriveController::driveRows($this->status, $this->coursePk);
    }

    public function headings(): array
    {
        return ['S. No.', 'Election Drive', 'Nomination Drive', 'Course Name', 'Election Publish Status', 'Result Status'];
    }

    public function map($row): array
    {
        return [
            ++$this->serial,
            $row->election_drive_name,
            $row->nomination_drive_name,
            $row->course_name,
            ((int) $row->election_publish_status === 1) ? 'Live' : 'Pending',
            ((int) $row->result_status === 1) ? 'Published' : 'Pending',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow    = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A1:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E1:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004384']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
