@php
    $tillLabel = $tillDate ? date('d-F-Y', strtotime($tillDate)) : '-';
    $storeLabel = $selectedStoreName ?? 'All Stores';
    $printedOn = now()->format('d/m/Y') . ' ' . now()->format('g:i:s A');
    $emblemSrc = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';

    $lbsnaaLogoSrc = 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
    foreach ([public_path('images/lbsnaa_logo.jpg'), public_path('images/lbsnaa_logo.png')] as $logoPath) {
        if (is_file($logoPath) && is_readable($logoPath)) {
            $raw = @file_get_contents($logoPath);
            if ($raw !== false) {
                $mime = str_ends_with(strtolower($logoPath), '.png') ? 'image/png' : 'image/jpeg';
                $lbsnaaLogoSrc = 'data:' . $mime . ';base64,' . base64_encode($raw);
                break;
            }
        }
    }
    if (str_starts_with($lbsnaaLogoSrc, 'http')) {
        foreach ([
            public_path('admin_assets/images/logos/logo.png'),
            public_path('admin_assets/images/logos/logo.svg'),
        ] as $localLogoPath) {
            if (is_file($localLogoPath) && is_readable($localLogoPath)) {
                $raw = @file_get_contents($localLogoPath);
                if ($raw !== false) {
                    $ext = strtolower(pathinfo($localLogoPath, PATHINFO_EXTENSION));
                    $mime = match ($ext) {
                        'svg' => 'image/svg+xml',
                        'png' => 'image/png',
                        'jpg', 'jpeg' => 'image/jpeg',
                        default => null,
                    };
                    if ($mime) {
                        $lbsnaaLogoSrc = 'data:' . $mime . ';base64,' . base64_encode($raw);
                        break;
                    }
                }
            }
        }
    }

    $itemsList = is_array($items ?? null) ? $items : [];
    $itemCount = count($itemsList);
    $outOfStockCount = 0;
    $belowMinimumCount = 0;
    foreach ($itemsList as $r) {
        $rem = (float) ($r['remaining_quantity'] ?? 0);
        $alt = (float) ($r['alert_quantity'] ?? 0);
        if ($rem <= 0) {
            $outOfStockCount++;
        } elseif ($rem <= $alt) {
            $belowMinimumCount++;
        }
    }

    $dateRangeBar = 'Low Stock Report As Of ' . $tillLabel;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Low Stock Report - OFFICER'S MESS LBSNAA MUSSOORIE</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
            color: #222;
            background: #fff;
        }

        /* Branding — aligned with stock-purchase-details-pdf & category-wise mess PDFs */
        .lbsnaa-header-wrap {
            border-bottom: 2px solid #004a93;
            margin-bottom: 12px;
            padding: 2px 0 8px;
        }
        .branding-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .branding-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }
        .branding-logo-left {
            width: 42px;
        }
        .branding-text {
            text-align: left;
            padding: 0 10px 0 2px;
            line-height: 1.25;
        }
        .branding-logo-right {
            width: 200px;
            text-align: right;
        }
        .lbsnaa-brand-line-1 {
            font-size: 8pt;
            color: #004a93;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .lbsnaa-brand-line-2 {
            font-size: 13pt;
            color: #222;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .lbsnaa-brand-line-3 {
            font-size: 10pt;
            color: #555;
            margin-top: 2px;
        }
        .header-img-left {
            width: 34px;
            height: 34px;
        }
        .header-img-right {
            width: 165px;
            height: auto;
        }

        .report-header-block {
            text-align: center;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title-center {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0 0 8px;
            color: #212529;
        }
        .report-date-bar {
            background: #004a93;
            color: #fff;
            padding: 8px 12px;
            text-align: center;
            font-weight: 600;
            font-size: 10pt;
            display: inline-block;
        }
        .report-store-line {
            font-size: 10pt;
            font-weight: 600;
            margin-top: 8px;
            color: #212529;
        }
        .text-muted {
            color: #6c757d;
            font-weight: 600;
        }

        .report-meta-print {
            font-size: 9pt;
            margin: 10px 0 12px;
            line-height: 1.45;
            text-align: left;
        }
        .report-meta-print .meta-line {
            margin-bottom: 4px;
            word-wrap: break-word;
        }

        /* Data table — same treatment as table.stock-purchase-data */
        table.low-stock-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 10px;
        }
        table.low-stock-data th,
        table.low-stock-data td {
            padding: 5px 8px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }
        table.low-stock-data thead th {
            background: #d3d6d9;
            font-weight: 600;
            text-align: left;
        }
        table.low-stock-data thead th.text-center {
            text-align: center;
        }
        table.low-stock-data thead th.text-end {
            text-align: right;
        }
        table.low-stock-data .text-center {
            text-align: center;
        }
        table.low-stock-data .text-end {
            text-align: right;
        }
        table.low-stock-data tbody tr:nth-child(even) td {
            background: #fafbfc;
        }
        table.low-stock-data tbody tr.row-danger td {
            background: #fdeaea !important;
        }
        table.low-stock-data tfoot td {
            font-weight: 700;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }

        .no-data {
            font-size: 10pt;
            margin: 10px 0 12px;
            color: #555;
        }

        .footer {
            border-top: 1px solid #dee2e6;
            font-size: 8pt;
            color: #666;
            text-align: center;
            padding-top: 6px;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<div class="lbsnaa-header-wrap">
    <table class="branding-table">
        <tr>
            <td class="branding-logo-left">
                <img src="{{ $emblemSrc }}" alt="Emblem of India" class="header-img-left">
            </td>
            <td class="branding-text">
                <div class="lbsnaa-brand-line-1">Government of India</div>
                <div class="lbsnaa-brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                <div class="lbsnaa-brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
            </td>
            <td class="branding-logo-right">
                <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo" class="header-img-right">
            </td>
        </tr>
    </table>
</div>

<div class="report-header-block">
    <h1 class="report-title-center">Low Stock Report</h1>
    <div class="report-date-bar">{{ $dateRangeBar }}</div>
    <div class="report-store-line">
        <span class="text-muted">Store:</span>
        <span>{{ $storeLabel }}</span>
    </div>
</div>

<div class="report-meta-print">
    <div class="meta-line"><strong>Printed on:</strong> {{ $printedOn }}</div>
    @if($itemCount > 0)
        <div class="meta-line">
            <strong>Summary:</strong>
            Total items {{ $itemCount }}
            &nbsp;|&nbsp; Out of stock {{ $outOfStockCount }}
            &nbsp;|&nbsp; Below minimum {{ $belowMinimumCount }}
        </div>
    @endif
</div>

@if(empty($itemsList))
    <p class="no-data">No items are currently below their minimum stock level for the selected filters.</p>
@else
    <table class="low-stock-data">
        <thead>
        <tr>
            <th class="text-center" style="width: 40px;">Sr.</th>
            <th>Item name</th>
            <th class="text-center" style="width: 68px;">Unit</th>
            <th class="text-end" style="width: 92px;">Available qty</th>
            <th class="text-end" style="width: 92px;">Alert qty</th>
            <th class="text-center" style="width: 100px;">Status</th>
        </tr>
        </thead>
        <tbody>
        @foreach($itemsList as $index => $row)
            @php
                $remaining = (float) ($row['remaining_quantity'] ?? 0);
                $alert = (float) ($row['alert_quantity'] ?? 0);
            @endphp
            <tr class="{{ $remaining < $alert ? 'row-danger' : '' }}">
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row['item_name'] ?? '-' }}</td>
                <td class="text-center">{{ $row['unit'] ?? 'Unit' }}</td>
                <td class="text-end">{{ number_format($remaining, 2) }}</td>
                <td class="text-end">{{ number_format($alert, 2) }}</td>
                <td class="text-center">
                    @if($remaining <= 0)
                        Out of Stock
                    @elseif($remaining <= $alert)
                        Below Minimum
                    @else
                        OK
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="6" class="text-end">Total items: {{ $itemCount }}</td>
        </tr>
        </tfoot>
    </table>
@endif

<div class="footer">
    <small>Officer's Mess LBSNAA Mussoorie — Low Stock Report</small>
</div>
</body>
</html>
