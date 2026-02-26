<?php

namespace App\Exports\Mess;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockBalanceTillDateExport implements FromCollection, WithHeadings
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection(): Collection
    {
        $rows = [];
        foreach ($this->reportData as $index => $item) {
            $rows[] = [
                $index + 1,
                $item['item_code'] ?? '—',
                $item['item_name'] ?? '—',
                $item['unit'] ?? '—',
                number_format($item['remaining_qty'] ?? $item['remaining_quantity'] ?? 0, 2),
                number_format($item['rate'] ?? 0, 2),
                number_format($item['amount'] ?? 0, 2),
            ];
        }
        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'S. No.',
            'Item Code',
            'Item Name',
            'Unit',
            'Remaining Quantity',
            'Avg Rate',
            'Amount',
        ];
    }
}
