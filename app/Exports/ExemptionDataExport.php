<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class ExemptionDataExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $records;
    protected $fields;
    protected $headings;

    public function __construct($records)
    {
        $this->records = $records;

        // Fields to extract from each record
        $this->fields = [
            'user_name',
            'contact_no',
            'web_auth',
            'Exemption_name',
            'medical_exemption_doc',
            'application_type',
            'exemption_count',
            'created_date',
        ];

        // Column headings for Excel export
        $this->headings = [
            'User Name',
            'Contact No',
            'Web Code',
            'Exemption Category',
            'Medical Document',
            'Application Type',
            'Exemption Count',
            'Submitted On',
        ];
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->records as $record) {
            $row = [];

            foreach ($this->fields as $field) {
                if ($field === 'medical_exemption_doc') {
                    $value = !empty($record->$field) ? 'Available' : 'Not Available';
                } elseif ($field === 'created_date') {
                    $value = Carbon::parse($record->created_date)->format('d-m-Y');
                } elseif ($field === 'application_type') {
                    $value = match ((int) $record->application_type) {
                        1 => 'Registration',
                        2 => 'Exemption',
                        default => 'Unknown',
                    };
                } else {
                    $value = $record->$field ?? 'N/A';
                }

                $row[] = $value;
            }

            $data[] = $row;
        }

        return $data;
    }

    public function headings(): array
    {
        return $this->headings;
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
