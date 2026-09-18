<?php

namespace App\Exports;

use App\Models\ExaminationDrive;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExaminationDriveExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    public function __construct(private Collection $rows)
    {
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Examination Type',
            'Term',
            'Course',
            'Phase',
            'Academic Session',
            'Start Date',
            'End Date',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->examinationType->exam_type_name ?? '-',
            $row->term->term_name ?? '-',
            $row->course->couse_short_name ?? $row->course->course_name ?? '-',
            $row->phase,
            $row->academic_session,
            $row->start_date ? Carbon::parse($row->start_date)->format('d-m-Y') : '',
            $row->end_date ? Carbon::parse($row->end_date)->format('d-m-Y') : '',
            ExaminationDrive::STATUS_LABELS[(int) $row->status] ?? 'Draft',
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
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '003366'],
                ],
            ],
        ];
    }
}
