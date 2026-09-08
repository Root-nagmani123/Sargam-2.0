<?php

namespace App\Exports;

use App\Http\Controllers\Admin\Master\NominationDriveController;
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
 * Download for the Nomination Drive grid. Same columns and same source rows as
 * the print sheet, honouring the Active/Archived pill and the course filter.
 */
class NominationDriveExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    private int $serial = 0;

    public function __construct(
        private string $status = 'active',
        private $coursePk = null
    ) {
    }

    public function collection()
    {
        return NominationDriveController::driveRows($this->status, $this->coursePk);
    }

    public function headings(): array
    {
        return [
            'S. No.',
            'Nomination Drive',
            'Course Name',
            'Start Date',
            'End Date',
            'Nomination Accept Status',
            'Nomination Withdraw Status',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->serial,
            $row->drive_name,
            $row->course_name,
            $row->start_date ? date('d-m-Y', strtotime($row->start_date)) : '',
            $row->end_date ? date('d-m-Y', strtotime($row->end_date)) : '',
            ((int) $row->nomination_accept_status === 1) ? 'Enable' : 'Disable',
            ((int) $row->nomination_withdraw_status === 1) ? 'Enable' : 'Disable',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow    = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle("A1:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D1:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004384']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
