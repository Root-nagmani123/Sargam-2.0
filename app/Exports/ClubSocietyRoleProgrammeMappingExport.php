<?php

namespace App\Exports;

use App\Http\Controllers\Admin\Master\ClubSocietyRoleProgrammeMappingController;
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
 * Download for the Club/ Society Role Programme Mapping grid. Same columns and
 * same source rows as the print sheet, and both honour the Active/Archived pill
 * and the course filter that were on screen.
 */
class ClubSocietyRoleProgrammeMappingExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    private int $serial = 0;

    public function __construct(
        private string $status = 'active',
        private $coursePk = null
    ) {
    }

    public function collection()
    {
        return ClubSocietyRoleProgrammeMappingController::mappingRows($this->status, $this->coursePk);
    }

    public function headings(): array
    {
        return [
            'S. No.',
            'Course Name',
            'Club/Society/Association',
            'Role',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->serial,
            $row->course_name,
            $row->club_society_name,
            $row->role_names,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow    = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF000000'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // The role list can be long — wrap rather than autosize into one strip.
        $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension('D')->setAutoSize(false)->setWidth(60);

        $sheet->getStyle("A1:A{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '004384'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
