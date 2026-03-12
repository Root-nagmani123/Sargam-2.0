<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $receiptNo = $receiptNo ?? $invoiceNo ?? '—';
        $invoiceNo = $invoiceNo ?? $receiptNo ?? '—';
        $storeName = $bill->resolved_store_name ?? '—';
        $dateFrom = isset($bill->date_from) && $bill->date_from ? $bill->date_from->format('d-m-Y') : ($bill->issue_date ? $bill->issue_date->format('d-m-Y') : '—');
        $dateTo = isset($bill->date_to) && $bill->date_to ? $bill->date_to->format('d-m-Y') : ($bill->issue_date ? $bill->issue_date->format('d-m-Y') : '—');
        $purchaseDate = $bill->issue_date ? $bill->issue_date->format('d-m-Y') : $dateFrom;
        $totalAmount = (float) $bill->net_total;
        $paidAmount = (float) ($paidAmount ?? 0);
        $dueAmount = (float) ($dueAmount ?? max(0, $totalAmount - $paidAmount));
        $paymentStatusLabel = $paymentStatusLabel ?? ($paidAmount >= $totalAmount ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Unpaid'));
    @endphp
    <title>Bill Receipt {{ $receiptNo }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 13px;
            padding: 16px 24px;
            max-width: 900px;
            margin: 0 auto;
            color: #212529;
            background-color: #ffffff;
        }
        .lbsnaa-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-bottom: .75rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #004a93;
        }
        .lbsnaa-header-left {
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .lbsnaa-header-text {
            line-height: 1.2;
        }
        .brand-line-1 {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #004a93;
        }
        .brand-line-2 {
            font-size: 1.05rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #212529;
        }
        .brand-line-3 {
            font-size: .8rem;
            color: #555;
        }
        .receipt-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .receipt-logo {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .receipt-logo-icon {
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, #af2910 0%, #7b1a0a 100%);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
        }
        .receipt-logo-text {
            font-size: 1rem;
            font-weight: 700;
            color: #0a3d6b;
            letter-spacing: 0.02em;
        }
        .receipt-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .receipt-center {
            text-align: center;
            margin: 0.75rem 0 0.5rem;
            padding: 0 0.5rem;
        }
        .receipt-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0a3d6b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 0.15rem;
        }
        .receipt-subtitle {
            font-size: .9rem;
            font-weight: 600;
            color: #af2910;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.35rem;
        }
        .receipt-period {
            font-size: 0.9rem;
            font-weight: 600;
            color: #0a3d6b;
        }
        hr {
            border: 0;
            border-top: 1px solid #dee2e6;
            margin: 0.75rem 0;
        }
        .client-row {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 0.15rem 0;
        }
        .client-row .client-label {
            font-weight: 600;
            color: #343a40;
        }
        .client-row .client-value {
            font-weight: 500;
        }
        .bill-table-wrapper {
            margin-top: 0.5rem;
            border-radius: .5rem;
            border: 1px solid #dee2e6;
            overflow: hidden;
        }
        .bill-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .bill-table thead {
            background-color: #f3f6fb;
        }
        .bill-table th,
        .bill-table td {
            padding: 0.45rem 0.6rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .bill-table th {
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
        }
        .bill-table tbody tr:nth-child(even) {
            background-color: #fcfcfd;
        }
        .bill-table .text-end {
            text-align: right;
        }
        .bill-table th.text-end {
            text-align: right;
        }
        .receipt-bottom {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }
        .payment-summary {
            text-align: right;
            min-width: 220px;
        }
        .payment-summary .summary-row {
            display: flex;
            justify-content: flex-end;
            align-items: baseline;
            gap: 0.5rem;
            margin-bottom: 0.2rem;
        }
        .payment-summary .summary-label {
            font-weight: 600;
            color: #343a40;
        }
        .payment-summary .summary-value {
            font-weight: 500;
            min-width: 3rem;
            text-align: right;
        }
        .action-bar {
            margin-top: 1.25rem;
            padding-top: 0.75rem;
            border-top: 1px solid #dee2e6;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .action-bar button {
            padding: 0.4rem 1.1rem;
            font-weight: 600;
            border-radius: 999px;
            border: none;
            font-size: 0.85rem;
            cursor: pointer;
        }
        .btn-print {
            background: linear-gradient(180deg, #0a6bb5 0%, #0a3d6b 100%);
            color: #fff;
        }
        .btn-close {
            background: #6c757d;
            color: #fff;
        }
        @media print {
            body {
                padding: 10mm 12mm;
                max-width: 100%;
            }
            .action-bar {
                display: none !important;
            }
            .lbsnaa-header {
                margin-bottom: .5rem;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="lbsnaa-header">
        <div class="lbsnaa-header-left">
            <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg"
                 alt="Emblem of India" height="42">
            <div class="lbsnaa-header-text">
                <div class="brand-line-1">Government of India</div>
                <div class="brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                <div class="brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
            </div>
        </div>
        <div class="lbsnaa-header-right">
            <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png"
                 alt="LBSNAA Logo" height="40">
        </div>
    </div>

    <div class="receipt-top">
        <div class="receipt-logo">
            <span class="receipt-logo-icon"></span>
            <span class="receipt-logo-text">Sargam</span>
        </div>
        <span class="receipt-date">
            Date {{ now()->format('d-m-Y') }} {{ now()->format('H:i') }}
        </span>
    </div>

    <div class="receipt-center">
        <div class="receipt-title">Mess Bill Receipt</div>
        <div class="receipt-subtitle">Client Bill Statement</div>
        <div class="receipt-period">From {{ $dateFrom }} To {{ $dateTo }}</div>
    </div>

    <hr>

    <div class="client-row">
        <span><span class="client-label">Receipt No</span>: <span class="client-value">{{ $receiptNo }}</span></span>
        <span><span class="client-label">Invoice No</span>: <span class="client-value">{{ $invoiceNo }}</span></span>
    </div>
    <div class="client-row">
        <span><span class="client-label">Client Name</span>: <span class="client-value">{{ $bill->client_name ?? ($bill->clientTypeCategory?->client_name ?? '—') }}</span></span>
        <span><span class="client-label">Client Type</span>: <span class="client-value">{{ $bill->client_type_display ?? ($bill->client_type_label ?? ($bill->clientTypeCategory ? ucfirst($bill->clientTypeCategory->client_type ?? '') : ucfirst($bill->client_type_slug ?? '—'))) }}</span></span>
    </div>

    <hr>

    <div class="bill-table-wrapper">
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
                    <td>{{ $item->store_name ?? $storeName }}</td>
                    <td>{{ $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—') }}</td>
                    <td>{{ $item->purchase_date ?? $purchaseDate }}</td>
                    <td class="text-end">{{ number_format($rate, 2) }}</td>
                    <td class="text-end">{{ number_format($issueQty, 2) }}</td>
                    <td class="text-end">{{ number_format($returnQty, 2) }}</td>
                    <td class="text-end">{{ number_format($netQty, 2) }}</td>
                    <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

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
