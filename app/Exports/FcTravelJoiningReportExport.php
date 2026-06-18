<?php

namespace App\Exports;

use App\Models\FC\StudentTravelPlanMaster;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FcTravelJoiningReportExport implements FromView, WithStyles, WithEvents, WithTitle
{
    public const COL_COUNT = 14;

    /** Rows before the table column-header row (titles + blank spacer). */
    protected int $headerRows = 6;

    public function __construct(
        protected Collection $rows,
        protected string $filterDescription
    ) {}

    public function title(): string
    {
        return 'FC Travel Joining';
    }

    public function view(): View
    {
        return view('admin.travel.excel.joining-report-excel', [
            'colCount'          => self::COL_COUNT,
            'tableRows'         => $this->mappedRows(),
            'filterDescription' => $this->filterDescription,
            'generatedAt'       => now()->format('d-m-Y H:i'),
        ]);
    }

    protected function mappedRows(): Collection
    {
        return $this->rows->values()->map(function ($r, int $idx) {
            $timeSlot = '';
            if (! empty($r->time_start) && ! empty($r->time_end)) {
                $timeSlot = substr((string) $r->time_start, 0, 5).'–'.substr((string) $r->time_end, 0, 5);
            }
            $name = trim((string) ($r->full_name ?? ''));
            if ($name === '') {
                $name = trim((string) ($r->sm_full_name ?? ''));
            }

            return [
                'sno'              => $idx + 1,
                'username'         => (string) ($r->login_username ?? $r->user_id ?? ''),
                'name'             => $name !== '' ? $name : (string) ($r->login_username ?? $r->user_id ?? ''),
                'code'             => (string) ($r->roll_no ?? ''),
                'mobile'           => (string) ($r->mobile_no ?? ''),
                'arrival_date'     => $r->joining_date ? \Carbon\Carbon::parse($r->joining_date)->format('Y-m-d') : '',
                'slot'             => (string) ($r->slot_label ?? ''),
                'time_slot'        => $timeSlot,
                'mode'             => (string) ($r->mode_of_journey ?? ''),
                'vehicle_no'       => (string) ($r->journey_vehicle_no ?? ''),
                'dehradun_time'    => (string) ($r->arrival_time_dehradun ?? ''),
                'require_vehicle'  => StudentTravelPlanMaster::interpretRequiresAcademyVehicle($r->require_academy_vehicle ?? null) ? 'Yes' : 'No',
                'service'          => (string) ($r->service_code ?? ''),
                'submitted'        => StudentTravelPlanMaster::interpretIsSubmitted($r->is_submitted ?? null) ? 'Yes' : 'Draft',
            ];
        });
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = Coordinate::stringFromColumnIndex(self::COL_COUNT);
        $dataStart = $this->headerRows + 1;
        $dataRowStart = $dataStart + 1;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->mergeCells("A4:{$lastCol}4");
        $sheet->mergeCells("A5:{$lastCol}5");
        $sheet->mergeCells("A6:{$lastCol}6");

        $sheet->getStyle("A1:A5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A3:A5')->getFont()->setSize(10);

        $sheet->getStyle("A{$dataStart}:{$lastCol}{$dataStart}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '003366'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '002244']],
            ],
        ]);
        $sheet->getRowDimension($dataStart)->setRowHeight(34);

        if ($lastRow >= $dataRowStart) {
            $sheet->getStyle("A{$dataRowStart}:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            ]);
        }

        // S.No., arrival date, slot time, mode, require vehicle, submitted
        $centerCols = ['A', 'F', 'H', 'I', 'L', 'N'];
        foreach ($centerCols as $col) {
            if ($lastRow >= $dataStart) {
                $sheet->getStyle("{$col}{$dataStart}:{$col}{$lastRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        $sheet->getStyle("A{$dataRowStart}:{$lastCol}{$lastRow}")->getFont()->setSize(10);

        $widths = [6, 16, 22, 12, 14, 13, 20, 16, 16, 24, 24, 26, 12, 10];
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($w);
        }

        $sheet->freezePane('A'.$dataRowStart);

        return [];
    }

    public function registerEvents(): array
    {
        $headerRows = $this->headerRows;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($headerRows) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastCol = Coordinate::stringFromColumnIndex(self::COL_COUNT);
                $dataStart  = $headerRows + 1;
                $dataRowStart = $dataStart + 1;

                $sheet->getPageSetup()
                    ->setRowsToRepeatAtTopByStartAndEnd(1, $dataStart)
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPrintArea("A1:{$lastCol}{$lastRow}");

                // Force Username (B), Name (C), Code (D) to text so numeric-looking
                // values like "3200" stay left-aligned and are not treated as numbers.
                for ($row = $dataRowStart; $row <= $lastRow; $row++) {
                    foreach (['B', 'C', 'D'] as $col) {
                        $cell = $sheet->getCell("{$col}{$row}");
                        $cell->setValueExplicit(
                            (string) $cell->getValue(),
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );
                    }
                }
            },
        ];
    }
}
