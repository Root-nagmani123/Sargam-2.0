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
 * Download for one drive's nominee list (the View screen). Mirrors that screen's
 * columns and the print sheet.
 */
class NominationDriveNomineeExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    private int $serial = 0;

    public function __construct(private $drivePk)
    {
    }

    public function collection()
    {
        return NominationDriveController::nomineeRows($this->drivePk);
    }

    public function headings(): array
    {
        return [
            'S. No.',
            'Society Name',
            'Post',
            'Nominee Name',
            'OT Code',
            'No of Nominated By',
            'Required Nomination',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->serial,
            $row->club_society_name,
            $row->post_name,
            $row->nominee_name,
            $row->ot_code,
            $row->nominated_by_count,
            $row->required_nomination,
            ucfirst((string) $row->status),
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
        $sheet->getStyle("E1:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004384']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
