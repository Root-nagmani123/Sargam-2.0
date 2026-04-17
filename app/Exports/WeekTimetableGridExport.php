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

class WeekTimetableGridExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    /**
     * @param  list<list<string>>  $sheetRows
     */
    public function __construct(
        protected array $sheetRows,
        protected string $title = 'Week timetable'
    ) {
    }

    public function array(): array
    {
        return $this->sheetRows;
    }

    public function title(): string
    {
        $clean = Str::limit(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $this->title), 31, '');

        return $clean !== '' ? $clean : 'Week timetable';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 30,
            'C' => 30,
            'D' => 30,
            'E' => 30,
            'F' => 30,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'F';
                $highestRow = max(1, (int) $sheet->getHighestRow());

                $sheet->getStyle("A1:{$lastCol}{$highestRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);

                $rows = $this->sheetRows;
                $timeRow1Based = null;
                $firstSlot1Based = null;

                foreach ($rows as $i => $r) {
                    $rb = $i + 1;
                    if (($r[0] ?? '') === 'TIME') {
                        $timeRow1Based = $rb;
                    }
                    $c0 = (string) ($r[0] ?? '');
                    if ($firstSlot1Based === null && $c0 !== '' && $c0 !== 'TIME') {
                        if (preg_match('/\d{1,2}:\d{2}/', $c0) === 1
                            && (stripos($c0, 'to') !== false || str_contains($c0, "\n"))) {
                            $firstSlot1Based = $rb;
                        }
                    }
                }

                if ($firstSlot1Based === null && $timeRow1Based !== null) {
                    $firstSlot1Based = $timeRow1Based + 2;
                }

                if ($timeRow1Based !== null && $timeRow1Based >= 2) {
                    $sheet->getStyle('A1:' . $lastCol . ($timeRow1Based - 1))->getFont()->setBold(true);
                    $sheet->getStyle("A{$timeRow1Based}:{$lastCol}" . ($timeRow1Based + 1))->getFont()->setBold(true);
                }

                if ($firstSlot1Based !== null && $firstSlot1Based > 1) {
                    $sheet->freezePane('A' . $firstSlot1Based);
                }

                if ($timeRow1Based !== null) {
                    $sheet->getStyle("A{$timeRow1Based}:{$lastCol}{$highestRow}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);
                }
            },
        ];
    }
}
