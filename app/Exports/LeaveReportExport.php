<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Styled .xlsx for both leave listings — Leave Approval (admin / faculty) and
 * My Leave Applications (officer trainee).
 *
 * The two pages differ only in which columns they export, so the rows and
 * headings are built by the controller and handed over ready-made; this class
 * only owns the workbook formatting. That keeps one styling definition instead
 * of one per page, and keeps the PDF (which renders the same arrays through
 * admin.leave.export.leave_pdf) showing exactly the same data.
 */
class LeaveReportExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected Collection $rows,
        protected array $headings,
        protected string $sheetTitle = 'Leave'
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        // Excel rejects sheet names over 31 chars or containing []:*?/\
        return mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $this->sheetTitle), 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004A93']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');

        if ($lastRow > 1) {
            $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '8FA3BD']],
                ],
            ]);
            $sheet->getStyle("A2:{$lastColumn}{$lastRow}")
                ->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        }

        return [];
    }
}
