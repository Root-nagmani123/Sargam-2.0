<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CourseParticipantExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $participants;

    public function __construct($participants)
    {
        $this->participants = $participants;
    }

    public function collection()
    {
        return $this->participants;
    }

    public function map($row): array
    {
        $student = $row->studentMaster ?? null;

        static $serial = 1;

        return [
            $serial++,
            $student->user_id ?? 'N/A',
            $student->display_name ?? 'N/A',
            $student->generated_OT_code ?? 'N/A',
            $student->email ?? 'N/A',
            $student->contact_no ?? 'N/A',
            ($student && $student->cadre) ? $student->cadre->cadre_name : 'N/A',
        ];
    }

    public function headings(): array
    {
        return [
            'S.No',
            'user_name',
            'Name',
            'ot code',
            'email_id',
            'mobile no',
            'cadre',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow    = $sheet->getHighestRow();

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004a93']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $sheet->getStyle("A1:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(25);

        return [];
    }

    public function title(): string
    {
        return 'Course Participants';
    }
}
