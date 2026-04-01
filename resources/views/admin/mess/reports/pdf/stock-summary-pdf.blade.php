@php
    $fromLabel = $fromDate ? date('d-F-Y', strtotime($fromDate)) : null;
    $toLabel = $toDate ? date('d-F-Y', strtotime($toDate)) : null;
    $storeLabel = $selectedStoreName ?? ($storeType === 'main' ? "Officer's Main Mess(Primary)" : 'All Sub Stores');
    $fromText = $fromLabel ?? 'Start';
    $toText = $toLabel ?? 'End';
    $dateRangeLine = 'Stock Summary Report Between ' . $fromText . ' To ' . $toText;
    $printedOn = now()->format('d/m/Y') . ' ' . now()->format('g:i:s A');
    $emblemSrc = $emblemSrc ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    $lbsnaaLogoSrc = $lbsnaaLogoSrc ?? 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stock Summary Report - OFFICER'S MESS LBSNAA MUSSOORIE</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 5mm 6mm;
        }

        html, body {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
            overflow: visible;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #222;
            background: white;
            position: relative;
        }

        /* Avoid position:fixed watermarks in Dompdf — they often clip/shift the canvas */
        .pdf-page-wrap {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            position: relative;
        }

        .lbsnaa-header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #004a93;
            padding-bottom: 0;
            margin-bottom: 12px;
        }

        .lbsnaa-header-table td {
            vertical-align: middle;
            padding: 4px 8px 10px 0;
            border: none;
        }

        .hdr-logo {
            width: 48px;
            text-align: center;
        }

        .hdr-logo img {
            height: 40px;
            width: auto;
            display: block;
            margin: 0 auto;
        }

        .brand-line-1 {
            font-size: 10.5pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #004a93;
            font-weight: 600;
        }

        .brand-line-2 {
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #222;
            margin-top: 2px;
        }

        .brand-line-3 {
            font-size: 10pt;
            color: #555;
            margin-top: 2px;
        }

        .print-report-title {
            font-size: 12.5pt;
            font-weight: 700;
            margin: 0 0 6px 0;
        }

        .report-meta {
            font-size: 10.5pt;
            margin-bottom: 10px;
        }

        .report-meta span {
            display: inline-block;
            margin-right: 12px;
            margin-bottom: 2px;
            max-width: 100%;
        }

        table.data-table {
            width: 100%;
            max-width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 9px;
            margin-top: 4px;
            page-break-inside: auto;
        }

        table.data-table thead {
            display: table-header-group;
        }

        table.data-table th,
        table.data-table td {
            padding: 3px 4px;
            border: 1px solid #dee2e6;
            word-wrap: break-word;
            overflow-wrap: break-word;
            vertical-align: middle;
        }

        table.data-table td:nth-child(2) {
            word-break: break-word;
        }

        table.data-table thead th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .totals-row td {
            background-color: #e9ecef !important;
            font-weight: bold;
        }

        table.data-table tbody tr:nth-child(even) td {
            background: #fafbfc;
        }

        .no-data {
            font-size: 12px;
            margin-top: 8px;
        }

        .footer {
            border-top: 1px solid #dee2e6;
            font-size: 10px;
            color: #666;
            text-align: center;
            padding-top: 6px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="pdf-page-wrap">

<table class="lbsnaa-header-table">
    <tr>
        <td class="hdr-logo">
            <img src="{{ $emblemSrc }}" alt="">
        </td>
        <td>
            <div class="brand-line-1">Government of India</div>
            <div class="brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
            <div class="brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
        </td>
        <td class="hdr-logo">
            <img src="{{ $lbsnaaLogoSrc }}" alt="">
        </td>
    </tr>
</table>

<div class="print-report-title">Stock Summary Report</div>
<div class="report-meta">
    <span><strong>Period:</strong> {{ $dateRangeLine }}</span>
    <span><strong>Store:</strong> {{ $storeLabel }}</span>
    <span><strong>Printed on:</strong> {{ $printedOn }}</span>
</div>

@if(empty($reportData))
    <p class="no-data">No stock movement found for the selected period.</p>
@else
    @php
        $rows = is_array($reportData) ? $reportData : $reportData->all();
        $totals = [
            'opening_amount' => (float) collect($rows)->sum(fn ($r) => (float) ($r['opening_amount'] ?? 0)),
            'purchase_amount' => (float) collect($rows)->sum(fn ($r) => (float) ($r['purchase_amount'] ?? 0)),
            'sale_amount' => (float) collect($rows)->sum(fn ($r) => (float) ($r['sale_amount'] ?? 0)),
            'closing_amount' => (float) collect($rows)->sum(fn ($r) => (float) ($r['closing_amount'] ?? 0)),
        ];
    @endphp

    <table class="data-table">
        <colgroup>
            <col style="width: 3%;">
            <col style="width: 14%;">
            <col style="width: 6%;">
            <col style="width: 5%;">
            <col style="width: 5.9%;">
            <col style="width: 5.9%;">
            <col style="width: 6.2%;">
            <col style="width: 5.9%;">
            <col style="width: 5.9%;">
            <col style="width: 6.2%;">
            <col style="width: 5.9%;">
            <col style="width: 5.9%;">
            <col style="width: 6.2%;">
            <col style="width: 5.9%;">
            <col style="width: 5.9%;">
            <col style="width: 6.2%;">
        </colgroup>
        <thead>
        <tr>
            <th class="text-center">Sr.</th>
            <th class="text-center">Item</th>
            <th class="text-center">Code</th>
            <th class="text-center">Unit</th>
            <th class="text-center">Op. Qty</th>
            <th class="text-center">Op. Rate</th>
            <th class="text-center">Op. Amt</th>
            <th class="text-center">Pur. Qty</th>
            <th class="text-center">Pur. Rate</th>
            <th class="text-center">Pur. Amt</th>
            <th class="text-center">Sale Qty</th>
            <th class="text-center">Sale Rate</th>
            <th class="text-center">Sale Amt</th>
            <th class="text-center">Cl. Qty</th>
            <th class="text-center">Cl. Rate</th>
            <th class="text-center">Cl. Amt</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item['item_name'] ?? '' }}</td>
                <td class="text-center">{{ $item['item_code'] ?? '-' }}</td>
                <td class="text-center">{{ $item['unit'] ?? '-' }}</td>
                <td class="text-end">{{ number_format((float) ($item['opening_qty'] ?? 0), 2) }}</td>
                <td class="text-end">&#8377;{{ number_format((float) ($item['opening_rate'] ?? 0), 2) }}</td>
                <td class="text-end">&#8377;{{ number_format((float) ($item['opening_amount'] ?? 0), 2) }}</td>
                <td class="text-end">{{ number_format((float) ($item['purchase_qty'] ?? 0), 2) }}</td>
                <td class="text-end">&#8377;{{ number_format((float) ($item['purchase_rate'] ?? 0), 2) }}</td>
                <td class="text-end">&#8377;{{ number_format((float) ($item['purchase_amount'] ?? 0), 2) }}</td>
                <td class="text-end">{{ number_format((float) ($item['sale_qty'] ?? 0), 2) }}</td>
                <td class="text-end">&#8377;{{ number_format((float) ($item['sale_rate'] ?? 0), 2) }}</td>
                <td class="text-end">&#8377;{{ number_format((float) ($item['sale_amount'] ?? 0), 2) }}</td>
                <td class="text-end">{{ number_format((float) ($item['closing_qty'] ?? 0), 2) }}</td>
                <td class="text-end">&#8377;{{ number_format((float) ($item['closing_rate'] ?? 0), 2) }}</td>
                <td class="text-end">&#8377;{{ number_format((float) ($item['closing_amount'] ?? 0), 2) }}</td>
            </tr>
        @endforeach
        <tr class="totals-row">
            <td colspan="4" class="text-end"><strong>Total</strong></td>
            <td class="text-end">-</td>
            <td class="text-end">-</td>
            <td class="text-end"><strong>&#8377;{{ number_format($totals['opening_amount'], 2) }}</strong></td>
            <td class="text-end">-</td>
            <td class="text-end">-</td>
            <td class="text-end"><strong>&#8377;{{ number_format($totals['purchase_amount'], 2) }}</strong></td>
            <td class="text-end">-</td>
            <td class="text-end">-</td>
            <td class="text-end"><strong>&#8377;{{ number_format($totals['sale_amount'], 2) }}</strong></td>
            <td class="text-end">-</td>
            <td class="text-end">-</td>
            <td class="text-end"><strong>&#8377;{{ number_format($totals['closing_amount'], 2) }}</strong></td>
        </tr>
        </tbody>
    </table>
@endif

<div class="footer">
    <small>Officer's Mess LBSNAA Mussoorie &mdash; Stock Summary Report</small>
</div>

</div>
</body>
</html>
