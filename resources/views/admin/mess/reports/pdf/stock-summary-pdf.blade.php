@php
    $fromLabel = $fromDate ? date('d-F-Y', strtotime($fromDate)) : null;
    $toLabel = $toDate ? date('d-F-Y', strtotime($toDate)) : null;
    $storeLabel = $selectedStoreName ?? ($storeType === 'main' ? "Officer's Main Mess(Primary)" : 'All Sub Stores');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stock Summary Report</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm 10mm;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
        }
        
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            margin: 16px 18px;
            color: #222;
            max-width: 297mm;
            background: white;
        }
        .page-header {
            border-bottom: 2px solid #004a93;
            padding-bottom: 6px;
            margin-bottom: 10px;
            page-break-after: avoid;
        }
        .page-header-top {
            display: table;
            width: 100%;
        }
        .page-header-col {
            display: table-cell;
            vertical-align: middle;
        }
        .page-header-title {
            text-align: center;
        }
        .page-header-title h1 {
            font-size: 12px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .page-header-title h2 {
            font-size: 10px;
            margin: 2px 0 0;
            text-transform: uppercase;
            color: #004a93;
        }
        .page-header-sub {
            font-size: 8px;
            margin-top: 2px;
            color: #555;
        }
        .meta-row {
            font-size: 8px;
            margin-top: 6px;
        }
        .meta-row span {
            display: inline-block;
            margin-right: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
            margin-top: 6px;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        thead {
            display: table-header-group;
        }
        tfoot {
            display: table-footer-group;
        }
        th, td {
            padding: 2px 3px;
            border: 1px solid #dde2ea;
        }
        thead th {
            background: #e6ecf5;
            font-weight: 600;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .totals-row {
            background-color: #e9ecef;
            font-weight: bold;
        }
        tbody tr:nth-child(even) td {
            background: #fafbfc;
        }
        .footer {
            border-top: 1px solid #dde2ea;
            font-size: 7px;
            color: #666;
            text-align: center;
            padding-top: 3px;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<div class="page-header">
    <div class="page-header-top">
        <div class="page-header-col page-header-title">
            <h1>OFFICER'S MESS LBSNAA MUSSOORIE</h1>
            <h2>Stock Summary Report</h2>
            <div class="page-header-sub">Lal Bahadur Shastri National Academy of Administration</div>
        </div>
    </div>
    <div class="meta-row">
        @php
            $fromText = $fromLabel ?? 'Start';
            $toText = $toLabel ?? 'End';
        @endphp
        <span>
            <strong>Period:</strong>
            Between {{ $fromText }} To {{ $toText }}
        </span>
        <span>
            <strong>Store:</strong>
            {{ $storeLabel }}
        </span>
        <span>
            <strong>Generated on:</strong> {{ now()->format('d-m-Y H:i') }}
        </span>
    </div>
</div>

@if(empty($reportData))
    <p>No stock movement found for the selected period.</p>
@else
    @php
        $totals = [
            'opening_amount' => collect($reportData)->sum('opening_amount'),
            'purchase_amount' => collect($reportData)->sum('purchase_amount'),
            'sale_amount' => collect($reportData)->sum('sale_amount'),
            'closing_amount' => collect($reportData)->sum('closing_amount'),
        ];
    @endphp

    <table>
        <thead>
        <tr>
            <th rowspan="2" class="text-center" style="width: 28px;">Sr.</th>
            <th rowspan="2" class="text-center" style="min-width: 120px;">Item Name</th>
            <th rowspan="2" class="text-center" style="min-width: 80px;">Item Code</th>
            <th rowspan="2" class="text-center" style="min-width: 50px;">Unit</th>
            <th colspan="3" class="text-center">Opening</th>
            <th colspan="3" class="text-center">Purchase</th>
            <th colspan="3" class="text-center">Sale</th>
            <th colspan="3" class="text-center">Closing</th>
        </tr>
        <tr>
            <th class="text-center">Qty</th>
            <th class="text-center">Rate</th>
            <th class="text-center">Amount</th>
            <th class="text-center">Qty</th>
            <th class="text-center">Rate</th>
            <th class="text-center">Amount</th>
            <th class="text-center">Qty</th>
            <th class="text-center">Rate</th>
            <th class="text-center">Amount</th>
            <th class="text-center">Qty</th>
            <th class="text-center">Rate</th>
            <th class="text-center">Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach($reportData as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item['item_name'] }}</td>
                <td>{{ $item['item_code'] ?? '—' }}</td>
                <td>{{ $item['unit'] ?? '—' }}</td>
                <td class="text-end">{{ number_format($item['opening_qty'], 2) }}</td>
                <td class="text-end">{{ number_format($item['opening_rate'], 2) }}</td>
                <td class="text-end">{{ number_format($item['opening_amount'], 2) }}</td>
                <td class="text-end">{{ number_format($item['purchase_qty'], 2) }}</td>
                <td class="text-end">{{ number_format($item['purchase_rate'], 2) }}</td>
                <td class="text-end">{{ number_format($item['purchase_amount'], 2) }}</td>
                <td class="text-end">{{ number_format($item['sale_qty'], 2) }}</td>
                <td class="text-end">{{ number_format($item['sale_rate'], 2) }}</td>
                <td class="text-end">{{ number_format($item['sale_amount'], 2) }}</td>
                <td class="text-end">{{ number_format($item['closing_qty'], 2) }}</td>
                <td class="text-end">{{ number_format($item['closing_rate'], 2) }}</td>
                <td class="text-end">{{ number_format($item['closing_amount'], 2) }}</td>
            </tr>
        @endforeach
        <tr class="totals-row">
            <td colspan="4" class="text-end"><strong>Total</strong></td>
            <td class="text-end">—</td>
            <td class="text-end">—</td>
            <td class="text-end"><strong>{{ number_format($totals['opening_amount'], 2) }}</strong></td>
            <td class="text-end">—</td>
            <td class="text-end">—</td>
            <td class="text-end"><strong>{{ number_format($totals['purchase_amount'], 2) }}</strong></td>
            <td class="text-end">—</td>
            <td class="text-end">—</td>
            <td class="text-end"><strong>{{ number_format($totals['sale_amount'], 2) }}</strong></td>
            <td class="text-end">—</td>
            <td class="text-end">—</td>
            <td class="text-end"><strong>{{ number_format($totals['closing_amount'], 2) }}</strong></td>
        </tr>
        </tbody>
    </table>
@endif

<div class="footer">
    <small>Officer's Mess LBSNAA Mussoorie &mdash; Stock Summary Report</small>
</div>
</body>
</html>

