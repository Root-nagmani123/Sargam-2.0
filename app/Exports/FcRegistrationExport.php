<?php

// namespace App\Exports;

// use App\Models\FcRegistrationMaster;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithStyles;
// use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Border;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;

// class FcRegistrationExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
// {
//     public function collection()
//     {
//         return FcRegistrationMaster::select(
//             'service_master_pk',
//             'schema_id',
//             'display_name',
//             'first_name',
//             'middle_name',
//             'last_name',
//             'email',
//             'contact_no',
//             'rank',
//             'web_auth',
//             'exam_year'
//         )->get();
//     }

//     public function headings(): array
//     {
//         return [
//             'Service Master PK',
//             'Schema ID',
//             'Display Name',
//             'First Name',
//             'Middle Name',
//             'Last Name',
//             'Email',
//             'Contact No',
//             'Rank',
//             'Web Auth',
//             'Exam Year',
//         ];
//     }

//     public function styles(Worksheet $sheet)
//     {
//         $lastRow = $sheet->getHighestRow();
//         $lastColumn = $sheet->getHighestColumn();

//         // Apply border + alignment for all cells
//         $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
//             ->applyFromArray([
//                 'borders' => [
//                     'allBorders' => [
//                         'borderStyle' => Border::BORDER_THIN,
//                         'color' => ['argb' => 'FF000000'], // Black
//                     ],
//                 ],
//                 'alignment' => [
//                     'horizontal' => Alignment::HORIZONTAL_CENTER,
//                     'vertical'   => Alignment::VERTICAL_CENTER,
//                 ],
//             ]);

//         // Apply header row style (row 1)
//         return [
//             1 => [
//                 'font' => ['bold' => true],
//                 'fill' => [
//                     'fillType'   => Fill::FILL_SOLID,
//                     'startColor' => ['rgb' => 'FFCC00'], // Light Yellow
//                 ],
//             ],
//         ];
//     }
// }


namespace App\Exports;

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
    protected $registrations;

    //  accept filtered data
    public function __construct($registrations)
    {
        $this->registrations = $registrations;
    }

    public function collection()
    {
        // Map all records to match heading order
        return $this->registrations->map(function ($row) {
            // dd($row);
            return [
                'course_name'       => $row->course_master_pk ?? '',
                'exemption_name'    => $row->exemption_name ?? '',
                'application_type'  => $row->application_type == 1 ? 'Registration' : ($row->application_type == 2 ? 'Exemption' : ''),
                'service_master_pk' => $row->service_short_name ?? '', // joined name
                'group_type'        => $row->group_type ?? '',
                'cadre_name'        => $row->cadre_name ?? '',   // moved before schema_id
                'schema_id'         => $row->schema_id ?? '',
                'display_name'      => $row->display_name ?? '',
                'first_name'        => $row->first_name ?? '',
                'middle_name'       => $row->middle_name ?? '',
                'last_name'         => $row->last_name ?? '',
                'email'             => $row->email ?? '',
                'contact_no'        => $row->contact_no ?? '',
                'rank'              => $row->rank ?? '',
                'dob'               => $row->dob ?? '',
                'web_auth'          => $row->web_auth ?? '',
                'exam_year'         => $row->exam_year ?? '',

            ];
        });
    }
    public function headings(): array
    {
        return [
            'Course Name',
            'Exemption Category',
            ' Application Type',
            'Service',
            'Group Type',
            'Cadre',
            'Schema ID',
            'Display Name',
            'First Name',
            'Middle Name',
            'Last Name',
            'Email',
            'Contact No',
            'Rank',
            'DOB',
            'Web Auth',
            'Exam Year',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFCC00'],
                ],
            ],
        ];
    }
}
