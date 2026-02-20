<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockSummaryExport implements FromCollection, WithHeadings
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
                $item['item_name'],
                $item['item_code'] ?? '—',
                $item['unit'] ?? '—',
                number_format($item['opening_qty'], 2),
                number_format($item['opening_rate'], 2),
                number_format($item['opening_amount'], 2),
                number_format($item['purchase_qty'], 2),
                number_format($item['purchase_rate'], 2),
                number_format($item['purchase_amount'], 2),
                number_format($item['sale_qty'], 2),
                number_format($item['sale_rate'], 2),
                number_format($item['sale_amount'], 2),
                number_format($item['closing_qty'], 2),
                number_format($item['closing_rate'], 2),
                number_format($item['closing_amount'], 2),
            ];
        }
        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'SR No',
            'Item Name',
            'Item Code',
            'Unit',
            'Opening Qty',
            'Opening Rate',
            'Opening Amount',
            'Purchase Qty',
            'Purchase Rate',
            'Purchase Amount',
            'Sale Qty',
            'Sale Rate',
            'Sale Amount',
            'Closing Qty',
            'Closing Rate',
            'Closing Amount',
        ];
    }
}
