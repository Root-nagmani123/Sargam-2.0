<?php

namespace App\Exports;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Second sheet: flat rows for filtering / mail-merge (one row per session).
 *
 * @implements FromArray<int, list<string>>
 */
class WeekTimetableSessionsSheetExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    /**
     * @param  list<list<string>>  $rowsWithHeader  Row 0 = headings; rest = data
     */
    public function __construct(
        protected array $rowsWithHeader,
        protected string $title = 'Sessions'
    ) {
    }

    public function array(): array
    {
        return $this->rowsWithHeader;
    }

    public function title(): string
    {
        $clean = Str::limit(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $this->title), 31, '');

        return $clean !== '' ? $clean : 'Sessions';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 12,
            'C' => 16,
            'D' => 10,
            'E' => 42,
            'F' => 28,
            'G' => 22,
            'H' => 16,
            'I' => 26,
            'J' => 12,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'J';
                $highestRow = max(1, (int) $sheet->getHighestRow());

                $sheet->getStyle("A1:{$lastCol}{$highestRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);

                $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastCol}1")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE8EEF5');

                if ($highestRow > 1) {
                    $sheet->freezePane('A2');
                    $sheet->getStyle("A2:{$lastCol}{$highestRow}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);
                }
            },
        ];
    }
}
