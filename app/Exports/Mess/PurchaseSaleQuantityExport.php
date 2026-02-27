<?php

namespace App\Exports\Mess;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchaseSaleQuantityExport implements FromCollection, WithHeadings
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection(): Collection
    {
        $rows = [];
        foreach ($this->reportData as $index => $row) {
            $rows[] = [
                $index + 1,
                $row['category_name'] ?? '—',
                $row['item_name'] ?? '—',
                $row['unit'] ?? '—',
                number_format($row['purchase_qty'] ?? 0, 2),
                $row['avg_purchase_price'] !== null ? number_format($row['avg_purchase_price'], 2) : '—',
                number_format($row['sale_qty'] ?? 0, 2),
                $row['avg_sale_price'] !== null ? number_format($row['avg_sale_price'], 2) : '—',
            ];
        }
        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'S. No.',
            'Category',
            'Item Name',
            'Unit',
            'Total Purchase Qty',
            'Avg Purchase Price',
            'Total Sale Qty',
            'Avg Sale Price',
        ];
    }
}
