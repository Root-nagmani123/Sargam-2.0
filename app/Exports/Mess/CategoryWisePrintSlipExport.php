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
        foreach ($this->vouchers as $voucher) {
            $requestNo = $voucher->request_no ?? ('SV-' . str_pad($voucher->id ?? $voucher->pk, 6, '0', STR_PAD_LEFT));
            $issueDate = $voucher->issue_date ? (is_object($voucher->issue_date) ? $voucher->issue_date->format('d/m/Y') : $voucher->issue_date) : 'N/A';
            $buyerName = $voucher->client_name ?? ($voucher->clientTypeCategory->client_name ?? 'N/A');
            $clientType = $voucher->clientTypeCategory
                ? ucfirst($voucher->clientTypeCategory->client_type ?? '')
                : ucfirst($voucher->client_type_slug ?? 'N/A');

            $items = $voucher->items ?? collect();
            foreach ($items as $item) {
                $serialNo++;
                $itemName = $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? 'N/A');
                $qty = $item->quantity ?? 0;
                $rate = $item->rate ?? 0;
                $amount = $qty * $rate;

                $rows[] = [
                    $serialNo,
                    $buyerName,
                    $clientType,
                    $requestNo,
                    $issueDate,
                    $itemName,
                    number_format($qty, 2),
                    $item->unit ?? '—',
                    number_format($rate, 2),
                    number_format($amount, 2),
                ];
            }
        }
        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'S. No.',
            'Buyer Name',
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
        return 'Category Wise Print Slip';
    }

    public function styles(Worksheet $sheet)
    {
        // Merge header cells (rows 1–4 contain header text we set in AfterSheet)
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');
        $sheet->mergeCells('A4:J4');

        $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3:A4')->getFont()->setSize(10);

        // Table header (row 6 in the sheet)
        $headerRange = 'A6:J6';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal('center');

        // Borders for the table
        $lastRow    = $sheet->getHighestRow();
        $tableRange = "A6:J{$lastRow}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setARGB('FFDEE2E6');

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(8);  // S. No.
        $sheet->getColumnDimension('B')->setWidth(26); // Buyer Name
        $sheet->getColumnDimension('C')->setWidth(18); // Client Type
        $sheet->getColumnDimension('D')->setWidth(18); // Request No.
        $sheet->getColumnDimension('E')->setWidth(14); // Issue Date
        $sheet->getColumnDimension('F')->setWidth(28); // Item Name
        $sheet->getColumnDimension('G')->setWidth(10); // Quantity
        $sheet->getColumnDimension('H')->setWidth(10); // Unit
        $sheet->getColumnDimension('I')->setWidth(12); // Rate
        $sheet->getColumnDimension('J')->setWidth(14); // Amount

        // Right-align numeric columns (G to J)
        $sheet->getStyle("G6:J{$lastRow}")
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
                $sheet->setCellValue('A2', 'Print Slip - Category Wise');
                $sheet->setCellValue('A3', "Print Slip - Category Wise Between {$from} To {$to}");
                $sheet->setCellValue('A4', 'Buyer-wise selling voucher details');

                // Freeze header region (after header + column titles)
                $sheet->freezePane('A7');

                // Optional: fit to page
                $lastRow = $sheet->getHighestRow();
                $sheet->getPageSetup()
                    ->setOrientation(
                        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                    )
                    ->setPrintArea("A1:J{$lastRow}");
            },
        ];
    }
}
