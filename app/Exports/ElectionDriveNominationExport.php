<?php

namespace App\Exports;

use App\Http\Controllers\Admin\Master\ElectionDriveController;
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
 * Download for the View Nominations screen. The screen groups by post; the sheet
 * flattens that into a Post column so it stays one table, and applies the same
 * filters that were on screen.
 */
class ElectionDriveNominationExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithMapping
{
    private int $serial = 0;

    public function __construct(private $nominationDrivePk, private array $filters = [])
    {
    }

    public function collection()
    {
        // Groups are keyed by post; flatten while keeping the post on each row.
        return ElectionDriveController::nominationGroups($this->nominationDrivePk, $this->filters)->flatten();
    }

    public function headings(): array
    {
        return ['S. No.', 'Post', 'Club/Society/Association', 'Nominee', 'OT Code', 'Nominated By', 'Status'];
    }

    public function map($row): array
    {
        return [
            ++$this->serial,
            $row->post_name,
            $row->club_society_name,
            $row->nominee_name,
            $row->ot_code,
            $row->nominated_by_count,
            ucfirst((string) $row->status),
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
        $sheet->getStyle("E1:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004384']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
