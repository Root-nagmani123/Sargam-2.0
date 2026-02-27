<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $receiptId = $bill->id ?? $bill->pk ?? '—';
        $storeName = $bill->resolved_store_name ?? '—';
        $dateFrom = isset($bill->date_from) && $bill->date_from ? $bill->date_from->format('d-m-Y') : ($bill->issue_date ? $bill->issue_date->format('d-m-Y') : '—');
        $dateTo = isset($bill->date_to) && $bill->date_to ? $bill->date_to->format('d-m-Y') : ($bill->issue_date ? $bill->issue_date->format('d-m-Y') : '—');
        $purchaseDate = $bill->issue_date ? $bill->issue_date->format('d-m-Y') : $dateFrom;
        $totalAmount = (float) $bill->net_total;
        $paidAmount = (float) ($paidAmount ?? 0);
        $dueAmount = (float) ($dueAmount ?? max(0, $totalAmount - $paidAmount));
        $paymentStatusLabel = $paymentStatusLabel ?? ($paidAmount >= $totalAmount ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Unpaid'));
    @endphp
    <title>Bill Receipt #{{ $receiptId }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; font-size: 14px; padding: 24px; max-width: 720px; margin: 0 auto; color: #333; }
        .receipt-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
        .receipt-logo { display: inline-flex; align-items: center; gap: 0.35rem; }
        .receipt-logo-icon { width: 20px; height: 20px; background: #c00; -webkit-print-color-adjust: exact; print-color-adjust: exact; clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%); }
        .receipt-logo-text { font-size: 1.1rem; font-weight: 700; color: #0a3d6b; letter-spacing: 0.02em; }
        .receipt-date { font-size: 0.9rem; color: #555; }
        .receipt-center { text-align: center; margin: 1rem 0; padding: 0 0.5rem; }
        .receipt-title { font-size: 1.35rem; font-weight: 700; color: #0a3d6b; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 0.25rem; }
        .receipt-subtitle { font-size: 1rem; font-weight: 700; color: #c00; text-transform: uppercase; letter-spacing: 0.02em; margin-bottom: 0.5rem; }
        .receipt-period { font-size: 0.95rem; font-weight: 600; color: #0a3d6b; }
        hr { border: 0; border-top: 1px solid #333; margin: 0.75rem 0; opacity: 0.25; }
        .client-row { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; padding: 0.25rem 0; }
        .client-row .client-label { font-weight: 700; color: #333; }
        .client-row .client-value { font-weight: 500; }
        .bill-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; margin: 0.75rem 0; }
        .bill-table th, .bill-table td { padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e0e0e0; }
        .bill-table th { font-weight: 700; color: #333; background: transparent; border-bottom: 1px solid #333; }
        .bill-table td { border-bottom: 1px solid #e8e8e8; }
        .bill-table .text-end { text-align: right; }
        .bill-table th.text-end { text-align: right; }
        .receipt-bottom { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; }
        .payment-summary { text-align: right; min-width: 200px; }
        .payment-summary .summary-row { display: flex; justify-content: flex-end; align-items: baseline; gap: 0.5rem; margin-bottom: 0.2rem; }
        .payment-summary .summary-label { font-weight: 600; color: #333; }
        .payment-summary .summary-value { font-weight: 500; min-width: 3rem; text-align: right; }
        .action-bar { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #dee2e6; display: flex; gap: 0.75rem; flex-wrap: wrap; }
        .action-bar button { padding: 0.5rem 1.25rem; font-weight: 600; border-radius: 6px; border: none; font-size: 0.95rem; cursor: pointer; }
        .btn-print { background: #0a3d6b; color: #fff; }
        .btn-close { background: #6c757d; color: #fff; }
        @media print {
            body { padding: 12px; max-width: 100%; }
            .action-bar { display: none !important; }
            .receipt-title { color: #0a3d6b; }
            .receipt-logo-icon { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="receipt-top">
        <div class="receipt-logo">
            <span class="receipt-logo-icon"></span>
            <span class="receipt-logo-text">Sargam</span>
        </div>
        <span class="receipt-date">Date {{ now()->format('d-m-Y') }} {{ now()->format('H:i') }}</span>
    </div>

    <div class="receipt-center">
        <div class="receipt-title">OFFICER'S MESS LBSNAA MUSSOORIE</div>
        <div class="receipt-subtitle">MESS BILLS</div>
        <div class="receipt-period">Client Bill From Period {{ $dateFrom }} To {{ $dateTo }}</div>
    </div>

    <hr>

    <div class="client-row">
        <span><span class="client-label">Client Name</span>: <span class="client-value">{{ $bill->client_name ?? ($bill->clientTypeCategory?->client_name ?? '—') }}</span></span>
        <span><span class="client-label">Client Type</span>: <span class="client-value">{{ $bill->client_type_display ?? ($bill->client_type_label ?? ($bill->clientTypeCategory ? ucfirst($bill->clientTypeCategory->client_type ?? '') : ucfirst($bill->client_type_slug ?? '—'))) }}</span></span>
    </div>

    <hr>

    <table class="bill-table">
        <thead>
            <tr>
                <th>Store Name</th>
                <th>Item Name</th>
                <th>Purchase Date</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Issue Qty</th>
                <th class="text-end">Return Qty</th>
                <th class="text-end">Net Qty</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bill->items as $item)
            @php
                $issueQty = (float) ($item->quantity ?? 0);
                $returnQty = (float) ($item->return_quantity ?? 0);
                $netQty = max(0, $issueQty - $returnQty);
                $rate = (float) ($item->rate ?? 0);
                $itemAmount = $netQty * $rate;
            @endphp
            <tr>
                <td>{{ $storeName }}</td>
                <td>{{ $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—') }}</td>
                <td>{{ $purchaseDate }}</td>
                <td class="text-end">{{ number_format($rate, 2) }}</td>
                <td class="text-end">{{ number_format($issueQty, 2) }}</td>
                <td class="text-end">{{ number_format($returnQty, 2) }}</td>
                <td class="text-end">{{ number_format($netQty, 2) }}</td>
                <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="receipt-bottom">
        <div></div>
        <div class="payment-summary">
            <div class="summary-row"><span class="summary-label">Paid Amount</span><span class="summary-value">{{ number_format($paidAmount, 2) }}</span></div>
            <div class="summary-row"><span class="summary-label">Total Amount</span><span class="summary-value">{{ number_format($totalAmount, 2) }}</span></div>
            <div class="summary-row"><span class="summary-label">Due Amount</span><span class="summary-value">{{ number_format($dueAmount, 2) }}</span></div>
            <div class="summary-row"><span class="summary-label">Payment Status</span><span class="summary-value">{{ $paymentStatusLabel }}</span></div>
        </div>
    </div>

    <div class="action-bar no-print">
        <button type="button" class="btn-print" onclick="window.print()">Print</button>
        <button type="button" class="btn-close" onclick="closeReceipt()">Close</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var style = document.createElement('style');
            style.textContent = '@media print { .no-print { display: none !important; } }';
            document.head.appendChild(style);
        });
        function closeReceipt() {
            window.close();
            setTimeout(function() {
                window.location.href = '{{ route("admin.dashboard") }}';
            }, 150);
        }
    </script>
</body>
</html>
