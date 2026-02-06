<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Receipt - Bill #{{ $bill->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; padding: 20px; }
        .receipt-header { text-align: center; margin-bottom: 24px; border-bottom: 1px solid #ddd; padding-bottom: 16px; }
        .receipt-title { font-size: 20px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
        th { background: #f5f5f5; font-weight: 600; }
        .text-end { text-align: right; }
        .total-row { font-weight: bold; background: #f9f9f9; }
        .print-btn { margin-top: 20px; }
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="receipt-header">
        <div class="receipt-title">Sargam - Mess Bill Receipt</div>
        <div class="text-muted small">Receipt #{{ $bill->id }}</div>
    </div>

    <div class="mb-3">
        <strong>Buyer:</strong> {{ $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—') }}<br>
        <strong>Client Type:</strong> {{ $bill->clientTypeCategory ? ucfirst($bill->clientTypeCategory->client_type ?? '') : ucfirst($bill->client_type_slug ?? '—') }}<br>
        <strong>Invoice Date:</strong> {{ $bill->issue_date ? $bill->issue_date->format('d-m-Y') : ($bill->date_from ? $bill->date_from->format('d-m-Y') : '—') }}<br>
        <strong>Store:</strong> {{ $bill->store->store_name ?? '—' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bill->items as $item)
                <tr>
                    <td>{{ $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—') }}</td>
                    <td class="text-end">{{ $item->quantity }}</td>
                    <td class="text-end">{{ number_format($item->rate ?? 0, 2) }}</td>
                    <td class="text-end">{{ number_format($item->amount ?? 0, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-end">Total</td>
                <td class="text-end">{{ number_format($bill->total_amount ?? $bill->items->sum('amount'), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="print-btn">
        <button onclick="window.print()" class="btn btn-primary">Print</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <script>
        if (typeof $ !== 'undefined') {
            $('.btn').addClass('btn').css({ padding: '8px 16px', marginRight: '8px', cursor: 'pointer' });
        }
    </script>
</body>
</html>
