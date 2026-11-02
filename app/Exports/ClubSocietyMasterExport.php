<?php

namespace App\Exports;

use App\Models\ClubSocietyMaster;
use App\Support\Concerns\BindsExportCellsAsText;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Download for the Define Club/ Society grid. Columns mirror the on-screen grid
 * (S. No. + Club/Society/Association) so the sheet and the printed page agree.
 */
class ClubSocietyMasterExport extends DefaultValueBinder implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping, WithCustomValueBinder
{
    // Names are typed by users; bind text as TYPE_STRING so "=..." never becomes a live formula.
    use BindsExportCellsAsText;

    private int $serial = 0;

    public function collection()
    {
        return ClubSocietyMaster::orderBy('pk')->get();
    }

    public function headings(): array
    {
        return [
            'S. No.',
            'Club/Society/Association',
            'Status',
        ];
    }

    public function map($clubSociety): array
    {
        return [
            ++$this->serial,
            $clubSociety->club_society_name,
            ((int) $clubSociety->active_inactive === 1) ? 'Active' : 'Inactive',
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
