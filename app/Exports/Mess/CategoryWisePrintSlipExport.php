<?php

namespace App\Exports\Mess;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Mirrors the on-screen / PDF category-wise print slip: same buyer grouping, columns, dates, and totals.
 */
class CategoryWisePrintSlipExport implements FromCollection, WithStyles, WithEvents, WithTitle, WithCustomStartCell
{
    /** @var \Illuminate\Support\Collection */
    protected $allBuyersSections;

    protected ?string $fromDate;

    protected ?string $toDate;

    protected ?string $courseMasterPk;

    /** @var \Illuminate\Support\Collection|null */
    protected $otCourses;

    public function __construct(
        $allBuyersSections,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $courseMasterPk = null,
        $otCourses = null
    ) {
        $this->allBuyersSections = $allBuyersSections;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->courseMasterPk = $courseMasterPk;
        $this->otCourses = $otCourses ?? collect();
    }

    public function collection(): Collection
    {
        $rows = [];

        foreach ($this->allBuyersSections as $groupedSections) {
            foreach ($groupedSections as $sectionVouchers) {
                $first = $sectionVouchers->first();
                $buyerName = $first->client_name ?? ($first->clientTypeCategory->client_name ?? 'N/A');
                $clientTypeLabel = $first->clientTypeCategory
                    ? ucfirst($first->clientTypeCategory->client_type)
                    : ucfirst($first->client_type_slug ?? 'N/A');
                $slug = $first->client_type_slug ?? '';
                $typeSuffix = ($slug === 'employee') ? 'Employee' : (($slug === 'ot') ? 'OT' : ucfirst($slug));
                if (! $typeSuffix) {
                    $typeSuffix = 'N/A';
                }

                $courseDisplay = null;
                if ($slug === 'course' && $this->courseMasterPk && $this->otCourses->isNotEmpty()) {
                    $selectedCourse = $this->otCourses->firstWhere('pk', $this->courseMasterPk);
                    if ($selectedCourse) {
                        $courseDisplay = $selectedCourse->course_name;
                    }
                }
                $clientTypeHeader = $clientTypeLabel . ($courseDisplay ? ' [' . $courseDisplay . ']' : '');

                $rows[] = ['BUYER NAME : ' . $buyerName . '- ' . $typeSuffix, '', '', '', '', '', '', ''];
                $rows[] = ['CLIENT TYPE : ' . $clientTypeHeader, '', '', '', '', '', '', ''];
                $rows[] = ['Slip No.', 'Buyer Name', 'Remark', 'Item Name', 'Request Date', 'Quantity', 'Price', 'Amount'];

                $sectionTotal = 0.0;

                foreach ($sectionVouchers as $voucher) {
                    $requestNo = $voucher->request_no ?? ('SV-' . str_pad($voucher->id ?? $voucher->pk ?? 0, 6, '0', STR_PAD_LEFT));
                    $requestDate = $voucher->issue_date ? $voucher->issue_date->format('d-m-Y') : 'N/A';
                    $rowCount = $voucher->items->count();

                    foreach ($voucher->items as $itemIndex => $item) {
                        $issueQty = (float) ($item->quantity ?? 0);
                        $returnQty = (float) ($item->return_quantity ?? 0);
                        $netQty = max(0, $issueQty - $returnQty);
                        $rate = (float) ($item->rate ?? 0);
                        $itemAmount = $netQty * $rate;
                        $sectionTotal += $itemAmount;

                        $itemName = $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? 'N/A');
                        $itemIssueDate = $item->issue_date ?? null;
                        $itemIssueDateFormatted = $itemIssueDate
                            ? ($itemIssueDate instanceof \Carbon\Carbon
                                ? $itemIssueDate->format('d-m-Y')
                                : Carbon::parse($itemIssueDate)->format('d-m-Y'))
                            : $requestDate;

                        $row = ['', '', '', '', '', '', '', ''];
                        if ($itemIndex === 0) {
                            $row[0] = $requestNo;
                            $row[1] = $buyerName;
                            $row[2] = $voucher->remarks ?? '—';
                        }
                        $row[3] = $itemName;
                        $row[4] = $itemIssueDateFormatted;
                        $row[5] = number_format($netQty, 2);
                        $row[6] = number_format($rate, 2);
                        $row[7] = number_format($itemAmount, 2);
                        $rows[] = $row;
                    }
                }

                $rows[] = ['', '', '', '', '', '', 'TOTAL', number_format($sectionTotal, 2)];
            }
        }

        return collect($rows);
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function title(): string
    {
        return 'Sale Voucher Report';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');
        $sheet->mergeCells('A4:H4');

        $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3:A4')->getFont()->setSize(10);

        $lastRow = $sheet->getHighestRow();
        $tableRange = "A5:H{$lastRow}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setARGB('FFDEE2E6');

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(26);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(14);

        $sheet->getStyle("F5:H{$lastRow}")->getAlignment()->setHorizontal('right');
        $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("E5:E{$lastRow}")->getAlignment()->setHorizontal('center');

        for ($row = 5; $row <= $lastRow; $row++) {
            $a = (string) $sheet->getCell("A{$row}")->getValue();
            if (str_starts_with($a, 'Slip No.')) {
                $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
            }
            $g = (string) $sheet->getCell("G{$row}")->getValue();
            if ($g === 'TOTAL') {
                $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:H{$row}")
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFF3F4F6');
            }
        }

        return [
            1 => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $from = $this->fromDate
                    ? Carbon::parse($this->fromDate)->format('d-F-Y')
                    : 'Start';
                $to = $this->toDate
                    ? Carbon::parse($this->toDate)->format('d-F-Y')
                    : 'End';

                $sheet->setCellValue('A1', "OFFICER'S MESS LBSNAA MUSSOORIE");
                $sheet->setCellValue('A2', 'Sale Voucher Report');
                $sheet->setCellValue('A3', "Between {$from} To {$to}");
                $sheet->setCellValue('A4', '');

                $lastRow = $sheet->getHighestRow();
                for ($row = 5; $row <= $lastRow; $row++) {
                    $a = (string) $sheet->getCell("A{$row}")->getValue();
                    if (str_starts_with($a, 'BUYER NAME :') || str_starts_with($a, 'CLIENT TYPE :')) {
                        $sheet->mergeCells("A{$row}:H{$row}");
                        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal('left');
                        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    }
                }

                $sheet->freezePane('A5');
                $sheet->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPrintArea("A1:H{$lastRow}");
            },
        ];
    }
}
