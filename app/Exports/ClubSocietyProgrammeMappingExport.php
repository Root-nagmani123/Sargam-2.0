<?php

namespace App\Exports;

use App\Http\Controllers\Admin\Master\ClubSocietyProgrammeMappingController;
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
 * Download for the Club/ Society Programme Mapping grid. Rows come from the same
 * helper the print sheet uses, so Excel and paper always agree, and both honour
 * the Active/Archived pill and the course filter that were on screen.
 */
class ClubSocietyProgrammeMappingExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    private int $serial = 0;

    public function __construct(
        private string $status = 'active',
        private $coursePk = null
    ) {
    }

    public function collection()
    {
        return ClubSocietyProgrammeMappingController::mappingRows($this->status, $this->coursePk);
    }

    public function headings(): array
    {
        return [
            'S. No.',
            'Course Name',
            'Club/Society/Association',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->serial,
            $row->course_name,
            $row->club_names,
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

        // The club list is long — wrap it instead of letting autosize make a
        // single unreadable column.
        $sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension('C')->setAutoSize(false)->setWidth(80);

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
