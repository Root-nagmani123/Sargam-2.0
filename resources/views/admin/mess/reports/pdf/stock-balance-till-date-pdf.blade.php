@php
    $tillLabel = $tillDate ? date('d-F-Y', strtotime($tillDate)) : '-';
    $storeLabel = $selectedStoreName ?? 'All Stores';
    $totalAmount = collect($reportData)->sum('amount');
    $emblemSrc = $emblemSrc ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    $logoDataUri = $logoDataUri ?? null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stock Balance Till Date Report</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            margin: 14px 18px;
            color: #1f2937;
        }
        .page-header {
            border-bottom: 2px solid #004a93;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .brand-table {
            width: 100%;
            border-collapse: collapse;
        }
        .brand-table td {
            border: 0;
            vertical-align: middle;
            padding: 0;
        }
        .logo-col {
            width: 52px;
            text-align: center;
        }
        .logo-col-right {
            width: 120px;
            max-width: 35%;
            text-align: right;
            vertical-align: middle;
        }
        .brand-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }
        .title-col {
            text-align: left;
            padding-left: 4px !important;
        }
        .title-line-1 {
            font-size: 9px;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #0d4d9a;
            font-weight: 600;
        }
        .title-line-2 {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            color: #0b1f3b;
            margin-top: 1px;
            line-height: 1.05;
        }
        .title-line-3 {
            font-size: 8px;
            color: #2f4d73;
            margin-top: 2px;
        }
        .rhs-brand {
            display: inline-block;
            text-align: left;
        }
        .rhs-logo {
            width: 44px;
            height: 44px;
            max-width: 44px;
            max-height: 44px;
            object-fit: contain;
            object-position: right center;
            vertical-align: middle;
            margin-right: 0;
        }
        .rhs-text-wrap {
            display: inline-block;
            vertical-align: top;
            line-height: 1.1;
            margin-top: 1px;
        }
        .rhs-line-hi {
            font-size: 6px;
            color: #b73a2d;
            font-weight: 600;
            margin-bottom: 1px;
        }
        .rhs-line-en {
            font-size: 7px;
            color: #b73a2d;
            font-weight: 600;
            margin-bottom: 1px;
        }
        .rhs-line-en-sub {
            font-size: 6px;
            color: #b73a2d;
        }
        .meta-row {
            margin-top: 8px;
            padding: 2px 0 0;
            border: 0;
            background: transparent;
            border-radius: 0;
            font-size: 7.5px;
            color: #374151;
        }
        .meta-row span {
            display: inline-block;
            margin-right: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-top: 8px;
        }
        th, td {
            padding: 3px 4px;
            border: 1px solid #d9e1ec;
        }
        thead th {
            background: #e6eef9;
            font-weight: 600;
            text-transform: uppercase;
        }
        tbody tr:nth-child(even) td {
            background: #fbfdff;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .total-row td {
            background: #edf3fb;
            font-weight: 700;
        }
        .no-data {
            margin-top: 10px;
            font-size: 9px;
        }
        .footer {
            border-top: 1px solid #d9e1ec;
            margin-top: 8px;
            padding-top: 3px;
            font-size: 7px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="page-header">
    <table class="brand-table">
        <tr>
            <td class="logo-col">
                <img src="{{ $emblemSrc }}" alt="India Emblem" class="brand-logo">
            </td>
            <td class="title-col">
                <div class="title-line-1">Government of India</div>
                <div class="title-line-2">Officer's Mess LBSNAA Mussoorie</div>
                <div class="title-line-3">Lal Bahadur Shastri National Academy of Administration</div>
            </td>
            <td class="logo-col-right">
                <div class="rhs-brand">
                    @if($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="LBSNAA Logo" class="rhs-logo">
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="meta-row">
        <span><strong>Report:</strong> Stock Balance as of Till Date</span>
        <span><strong>Till Date:</strong> {{ $tillLabel }}</span>
        <span><strong>Store:</strong> {{ $storeLabel }}</span>
        <span><strong>Generated on:</strong> {{ now()->format('d-m-Y H:i') }}</span>
    </div>
</div>

@if(empty($reportData))
    <p class="no-data">No stock balance found for the selected filters.</p>
@else
    <table>
        <thead>
        <tr>
            <th class="text-center" style="width: 34px;">Sr.</th>
            <th style="width: 80px;">Item Code</th>
            <th>Item Name</th>
            <th class="text-end" style="width: 88px;">Remaining Quantity</th>
            <th class="text-center" style="width: 62px;">Unit</th>
            <th class="text-end" style="width: 76px;">Avg Rate</th>
            <th class="text-end" style="width: 90px;">Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach($reportData as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item['item_code'] ?? '-' }}</td>
                <td>{{ $item['item_name'] ?? '-' }}</td>
                <td class="text-end">{{ number_format($item['remaining_qty'] ?? 0, 2) }}</td>
                <td class="text-center">{{ $item['unit'] ?? 'Unit' }}</td>
                <td class="text-end">&#8377;{{ number_format($item['rate'] ?? 0, 2) }}</td>
                <td class="text-end">&#8377;{{ number_format($item['amount'] ?? 0, 2) }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="6" class="text-end">Total Amount</td>
            <td class="text-end">&#8377;{{ number_format($totalAmount, 2) }}</td>
        </tr>
        </tbody>
    </table>
@endif

<div class="footer">
    Officer's Mess LBSNAA Mussoorie - Stock Balance Till Date Report
</div>
</body>
</html>
