<?php

namespace App\Exports;

use App\Models\FcRegistrationMaster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class FcRegistrationExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return FcRegistrationMaster::select(
            'service_master_pk',
            'schema_id',
            'display_name',
            'first_name',
            'middle_name',
            'last_name',
            'email',
            'contact_no',
            'rank',
            'web_auth',
            'exam_year'
        )->get();
    }

    public function headings(): array
    {
        return [
            'Service Master PK',
            'Schema ID',
            'Display Name',
            'First Name',
            'Middle Name',
            'Last Name',
            'Email',
            'Contact No',
            'Rank',
            'Web Auth',
            'Exam Year',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        // Apply border + alignment for all cells
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'], // Black
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);

        // Apply header row style (row 1)
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFCC00'], // Light Yellow
                ],
            ],
        ];
    }
}

