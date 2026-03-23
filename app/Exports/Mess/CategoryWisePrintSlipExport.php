<?php

namespace App\Exports\Mess;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class CategoryWisePrintSlipExport implements FromCollection, WithHeadings, WithStyles, WithEvents, WithTitle, WithCustomStartCell
{
    /** @var \Illuminate\Support\Collection */
    protected $vouchers;
    protected ?string $fromDate;
    protected ?string $toDate;

    public function __construct($vouchers, ?string $fromDate = null, ?string $toDate = null)
    {
        $this->vouchers = $vouchers;
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
    }

    public function collection(): Collection
    {
        $rows = [];
        $serialNo = 0;
        $grandTotal = 0.0;

        // Keep grouping aligned with the UI report sections.
        $groupedVouchers = $this->vouchers->groupBy(function ($voucher) {
            return ($voucher->client_type_pk ?? '') . '-' . ($voucher->client_type_slug ?? '');
        });

        foreach ($groupedVouchers as $sectionVouchers) {
            $sectionTotal = 0.0;

            foreach ($sectionVouchers as $voucher) {
                $requestNo = $voucher->request_no ?? ('SV-' . str_pad($voucher->id ?? $voucher->pk, 6, '0', STR_PAD_LEFT));
                $issueDate = $voucher->issue_date ? (is_object($voucher->issue_date) ? $voucher->issue_date->format('d/m/Y') : $voucher->issue_date) : 'N/A';
                $buyerName = $voucher->client_name ?? ($voucher->clientTypeCategory->client_name ?? 'N/A');
                $clientType = $voucher->clientTypeCategory
                    ? ucfirst($voucher->clientTypeCategory->client_type ?? '')
                    : ucfirst($voucher->client_type_slug ?? 'N/A');
                $remarks = $voucher->remarks ?? '';

                $items = $voucher->items ?? collect();
                foreach ($items as $item) {
                    $serialNo++;
                    $itemName = $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? 'N/A');
                    $issueQty = (float) ($item->quantity ?? 0);
                    $returnQty = (float) ($item->return_quantity ?? 0);
                    $netQty = max(0, $issueQty - $returnQty);
                    $rate = (float) ($item->rate ?? 0);
                    $amount = $netQty * $rate;

                    $sectionTotal += $amount;
                    $grandTotal += $amount;

                    $rows[] = [
                        $serialNo,
                        $buyerName,
                        $remarks,
                        $clientType,
                        $requestNo,
                        $issueDate,
                        $itemName,
                        number_format($netQty, 2),
                        $item->unit ?? '—',
                        number_format($rate, 2),
                        number_format($amount, 2),
                    ];
                }
            }

            // Section total row (mirrors TOTAL row shown in UI).
            $rows[] = [
                '',
                '',
                '',
                '',
                '',
                '',
                'TOTAL',
                '',
                '',
                '',
                number_format($sectionTotal, 2),
            ];
        }

        if ($grandTotal > 0) {
            // Final report grand total row.
            $rows[] = [
                '',
                '',
                '',
                '',
                '',
                '',
                'GRAND TOTAL',
                '',
                '',
                '',
                number_format($grandTotal, 2),
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'S. No.',
            'Buyer Name',
            'Remark',
            'Client Type',
            'Request No.',
            'Issue Date',
            'Item Name',
            'Quantity',
            'Unit',
            'Rate',
            'Amount',
        ];
    }

    /**
     * Start data (headings) from row 6, leaving rows 1–4 for LBSNAA header.
     */
    public function startCell(): string
    {
        return 'A6';
    }

    public function title(): string
    {
        return 'Sale Voucher Report';
    }

    public function styles(Worksheet $sheet)
    {
        // Merge header cells (rows 1–4 contain header text we set in AfterSheet)
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');
        $sheet->mergeCells('A3:K3');
        $sheet->mergeCells('A4:K4');

        $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3:A4')->getFont()->setSize(10);

        // Table header (row 6 in the sheet)
        $headerRange = 'A6:K6';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal('center');

        // Borders for the table
        $lastRow    = $sheet->getHighestRow();
        $tableRange = "A6:K{$lastRow}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setARGB('FFDEE2E6');

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(8);  // S. No.
        $sheet->getColumnDimension('B')->setWidth(26); // Buyer Name
        $sheet->getColumnDimension('C')->setWidth(24); // Remark
        $sheet->getColumnDimension('D')->setWidth(18); // Client Type
        $sheet->getColumnDimension('E')->setWidth(18); // Request No.
        $sheet->getColumnDimension('F')->setWidth(14); // Issue Date
        $sheet->getColumnDimension('G')->setWidth(28); // Item Name
        $sheet->getColumnDimension('H')->setWidth(10); // Quantity
        $sheet->getColumnDimension('I')->setWidth(10); // Unit
        $sheet->getColumnDimension('J')->setWidth(12); // Rate
        $sheet->getColumnDimension('K')->setWidth(14); // Amount

        // Right-align numeric columns (H to K)
        $sheet->getStyle("H6:K{$lastRow}")
            ->getAlignment()->setHorizontal('right');

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
                $sheet->setCellValue('A3', "Sale Voucher Report Between {$from} To {$to}");
                $sheet->setCellValue('A4', 'Buyer-wise selling voucher details');

                // Freeze header region (after header + column titles)
                $sheet->freezePane('A7');

                // Optional: fit to page
                $lastRow = $sheet->getHighestRow();
                $sheet->getPageSetup()
                    ->setOrientation(
                        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                    )
                    ->setPrintArea("A1:K{$lastRow}");

                // Emphasize section totals and grand total rows.
                for ($row = 7; $row <= $lastRow; $row++) {
                    $label = (string) $sheet->getCell("G{$row}")->getValue();
                    if (in_array($label, ['TOTAL', 'GRAND TOTAL'], true)) {
                        $sheet->getStyle("A{$row}:K{$row}")->getFont()->setBold(true);
                        $sheet->getStyle("A{$row}:K{$row}")
                            ->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setARGB('FFF3F4F6');
                    }
                }
            },
        ];
    }
}
