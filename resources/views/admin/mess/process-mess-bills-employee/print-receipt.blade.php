<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php $receiptId = $bill->id ?? $bill->pk ?? '—'; @endphp
    <title>Mess Bill Receipt #{{ $receiptId }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, Arial, sans-serif; font-size: 14px; padding: 24px; max-width: 720px; margin: 0 auto; color: #333; }
        .receipt-header { text-align: center; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 2px solid #004a93; }
        .receipt-title { font-size: 1.5rem; font-weight: 700; color: #004a93; margin-bottom: 4px; }
        .receipt-subtitle { font-size: 0.8rem; color: #666; }
        .receipt-no { font-size: 0.9rem; font-weight: 600; color: #333; margin-top: 8px; }
        .info-block { background: #f8f9fa; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
        .info-block .row { display: flex; flex-wrap: wrap; gap: 12px 24px; }
        .info-block .label { font-weight: 600; color: #555; }
        .info-block .value { color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 0 0 20px; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        th, td { border: 1px solid #dee2e6; padding: 10px 12px; text-align: left; }
        th { background: #004a93; color: #fff; font-weight: 600; font-size: 0.85rem; }
        tbody tr:nth-child(even) { background: #f8f9fa; }
        .text-end { text-align: right; }
        .total-row { font-weight: 700; background: #e7f1ff !important; font-size: 1.05rem; }
        .total-row td { border-top: 2px solid #004a93; padding: 12px; }
        .action-bar { margin-top: 24px; display: flex; gap: 12px; flex-wrap: wrap; }
        .action-bar button { padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; font-size: 14px; }
        .btn-print { background: #004a93; color: #fff; }
        .btn-print:hover { background: #003a73; }
        .btn-close { background: #6c757d; color: #fff; }
        .btn-close:hover { background: #5a6268; }
        @media print {
            .action-bar { display: none !important; }
            body { padding: 0; max-width: 100%; }
            .receipt-header { border-bottom-color: #000; }
            .receipt-title { color: #000; }
        }
    </style>
</head>
<body>
    <div class="receipt-header">
        <div class="receipt-title">Sargam — Employee Mess Bill</div>
        <div class="receipt-subtitle">Billing &amp; Finance</div>
        <div class="receipt-no">Receipt #{{ $receiptId }}</div>
    </div>

    <div class="info-block">
        <div class="row">
            <div><span class="label">Buyer:</span> <span class="value">{{ $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—') }}</span></div>
            <div><span class="label">Client Type:</span> <span class="value">{{ $bill->clientTypeCategory ? ucfirst($bill->clientTypeCategory->client_type ?? '') : ucfirst($bill->client_type_slug ?? '—') }}</span></div>
            <div><span class="label">Invoice Date:</span> <span class="value">{{ $bill->issue_date ? $bill->issue_date->format('d-m-Y') : (isset($bill->date_from) && $bill->date_from ? $bill->date_from->format('d-m-Y') : '—') }}</span></div>
            <div><span class="label">Store:</span> <span class="value">{{ $bill->store->store_name ?? '—' }}</span></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Rate (₹)</th>
                <th class="text-end">Amount (₹)</th>
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
                <td colspan="3" class="text-end">Total (₹)</td>
                <td class="text-end">₹ {{ number_format($bill->total_amount ?? $bill->items->sum('amount'), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="action-bar">
        <button type="button" class="btn-print" onclick="window.print()">Print</button>
        <button type="button" class="btn-close" onclick="window.close()">Close</button>
    </div>
</body>
</html>
