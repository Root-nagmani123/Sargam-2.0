<?php

namespace App\Exports\Mess;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CategoryWisePrintSlipExport implements FromCollection, WithHeadings
{
    /** @var \Illuminate\Support\Collection */
    protected $vouchers;

    public function __construct($vouchers)
    {
        $this->vouchers = $vouchers;
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
}
