<?php

namespace App\Exports;

use App\Models\ClubSocietyRoleMaster;
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
 * Download for the Define Club/ Society Role grid. Columns mirror the on-screen
 * grid so the sheet and the printed page agree.
 */
class ClubSocietyRoleMasterExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    private int $serial = 0;

    public function collection()
    {
        return ClubSocietyRoleMaster::orderBy('pk')->get();
    }

    public function headings(): array
    {
        return [
            'S. No.',
            'Club/Society/Association Role',
            'Status',
        ];
    }

    public function map($role): array
    {
        return [
            ++$this->serial,
            $role->club_society_role_name,
            ((int) $role->active_inactive === 1) ? 'Active' : 'Inactive',
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
