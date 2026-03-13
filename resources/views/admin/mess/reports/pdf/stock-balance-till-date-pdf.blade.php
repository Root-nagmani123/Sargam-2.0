@php
    $tillLabel = $tillDate ? date('d-F-Y', strtotime($tillDate)) : '-';
    $storeLabel = $selectedStoreName ?? 'All Stores';
    $totalAmount = collect($reportData)->sum('amount');

    $logoDataUri = null;
    $logoPath = public_path('images/lbsnaa_logo.jpg');
    if (is_file($logoPath)) {
        $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $mime = $extension === 'png' ? 'image/png' : ($extension === 'webp' ? 'image/webp' : 'image/jpeg');
        $raw = @file_get_contents($logoPath);
        if ($raw !== false) {
            $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($raw);
        }
    }
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
            margin: 16px 18px;
            color: #1f2937;
        }
        .page-header {
            border-bottom: 2px solid #004a93;
            padding-bottom: 8px;
            margin-bottom: 10px;
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
            width: 56px;
            text-align: center;
        }
        .brand-logo {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 50%;
            border: 1px solid #d6dfec;
            padding: 2px;
        }
        .title-col {
            text-align: center;
        }
        .title-line-1 {
            font-size: 10px;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #4b5563;
        }
        .title-line-2 {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            color: #004a93;
            margin-top: 2px;
        }
        .title-line-3 {
            font-size: 9px;
            color: #374151;
            margin-top: 2px;
        }
        .meta-row {
            margin-top: 8px;
            padding: 5px 6px;
            border: 1px solid #d6dfec;
            background: #f4f8fc;
            border-radius: 4px;
            font-size: 8px;
        }
        .meta-row span {
            display: inline-block;
            margin-right: 14px;
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
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="LBSNAA Logo" class="brand-logo">
                @endif
            </td>
            <td class="title-col">
                <div class="title-line-1">Government of India</div>
                <div class="title-line-2">Officer's Mess LBSNAA Mussoorie</div>
                <div class="title-line-3">Lal Bahadur Shastri National Academy of Administration</div>
            </td>
            <td class="logo-col">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="LBSNAA Logo" class="brand-logo">
                @endif
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
            <th class="text-end" style="width: 88px;">Remaining Qty</th>
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
