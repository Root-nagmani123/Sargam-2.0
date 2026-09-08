<?php

namespace App\Exports;

use App\Http\Controllers\Admin\Master\OfficeBearerController;
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
 * Download for the Officer Bearers grid. The screen shows Role / Name / OT Code;
 * the sheet adds Course and Club/Society, which the on-screen filters carry
 * implicitly but a detached spreadsheet cannot.
 */
class OfficeBearerExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    private int $serial = 0;

    public function __construct(
        private string $status = 'active',
        private $coursePk = null,
        private $clubPk = null
    ) {
    }

    public function collection()
    {
        return OfficeBearerController::rows($this->status, $this->coursePk, $this->clubPk);
    }

    public function headings(): array
    {
        return ['S No.', 'Course Name', 'Club/Society/Association', 'Role', 'Officer Bearer Name', 'OT Code'];
    }

    public function map($row): array
    {
        return [
            ++$this->serial,
            $row->course_name,
            $row->club_society_name,
            $row->role_name,
            $row->officer_bearer_name,
            $row->ot_code,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow    = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A1:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F1:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004384']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
