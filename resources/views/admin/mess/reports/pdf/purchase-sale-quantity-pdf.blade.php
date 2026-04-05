@php
    try {
        $fromLabel = $fromDate ? \Carbon\Carbon::parse($fromDate)->format('d-m-Y') : null;
        $toLabel = $toDate ? \Carbon\Carbon::parse($toDate)->format('d-m-Y') : null;
    } catch (\Throwable $e) {
        $fromLabel = $fromDate ? (string) $fromDate : null;
        $toLabel = $toDate ? (string) $toDate : null;
    }
    $viewLabel = $combinedViewLabel ?? 'Item-wise';
    $viewTypeSections = $viewTypeSections ?? [];
    $storeLabel = (isset($selectedStoreName) && $selectedStoreName !== '') ? $selectedStoreName : 'All Stores';
    $itemsLabel = (isset($selectedItemNamesLabel) && $selectedItemNamesLabel !== '') ? $selectedItemNamesLabel : 'All Items';
    $printedOn = now()->format('d/m/Y') . ' ' . now()->format('g:i:s A');

    $emblemSrc = $emblemSrc ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    $lbsnaaLogoSrc = $lbsnaaLogoSrc ?? 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';

    $periodBar = 'From ' . ($fromLabel ?? '—') . ' To ' . ($toLabel ?? '—');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Item Report - OFFICER'S MESS LBSNAA MUSSOORIE</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            margin: 0;
            padding: 0;
            color: #222;
            background: #fff;
        }

        /* Official header — three columns, colours aligned with institution layout */
        .lbsnaa-header-wrap {
            border-bottom: 3px solid #003366;
            margin-bottom: 10px;
            padding: 4px 0 10px;
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
            width: 48px;
        }
        .branding-text {
            text-align: center;
            padding: 0 12px;
            line-height: 1.25;
        }
        /* Single flat row for Dompdf — nested <table> inside <td> often triggers "Frame not found in cellmap". */
        .branding-seal-cell {
            width: 52px;
            vertical-align: middle;
            padding: 0 4px 0 0;
        }
        .lbsnaa-brand-line-1 {
            font-size: 8pt;
            color: #0070c0;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
        }
        .lbsnaa-brand-line-2 {
            font-size: 12pt;
            color: #111;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 3px;
        }
        .lbsnaa-brand-line-3 {
            font-size: 9pt;
            color: #4a5a6a;
            margin-top: 3px;
            font-weight: normal;
        }
        .header-img-left {
            width: 40px;
            height: 40px;
            object-fit: contain;
            display: block;
        }
        .header-img-right-seal {
            width: 44px;
            height: 44px;
            object-fit: contain;
            display: block;
        }
        .branding-right-text {
            text-align: left;
            padding-left: 8px;
            line-height: 1.2;
        }
        .branding-hindi {
            font-size: 8pt;
            color: #7b2d26;
            font-weight: 600;
        }
        .branding-en-side {
            font-size: 7pt;
            color: #7b2d26;
            margin-top: 2px;
        }

        .report-header-block {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title-center {
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0 0 6px;
            color: #212529;
        }
        .report-date-bar {
            background: #003366;
            color: #fff;
            padding: 6px 12px;
            text-align: center;
            font-weight: 600;
            font-size: 9pt;
            display: inline-block;
        }

        .report-meta-print {
            font-size: 8pt;
            margin: 8px 0 10px;
            line-height: 1.45;
            text-align: left;
        }
        .report-meta-print .meta-line {
            margin-bottom: 3px;
            word-wrap: break-word;
        }

        table.purchase-sale-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 8px;
        }
        table.purchase-sale-data th,
        table.purchase-sale-data td {
            padding: 4px 6px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }
        table.purchase-sale-data thead th {
            background: #d3d6d9;
            font-weight: 600;
            text-align: left;
        }
        table.purchase-sale-data thead th.text-center {
            text-align: center;
        }
        table.purchase-sale-data thead th.text-end {
            text-align: right;
        }
        table.purchase-sale-data .text-center {
            text-align: center;
        }
        table.purchase-sale-data .text-end {
            text-align: right;
        }
        table.purchase-sale-data tbody tr:nth-child(even) td {
            background: #fafbfc;
        }

        .group-title {
            margin-top: 8px;
            margin-bottom: 4px;
            font-weight: 700;
            font-size: 9pt;
            color: #003366;
        }

        .view-section-heading {
            margin-top: 10px;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 10pt;
            color: #003366;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 4px;
        }

        .no-data {
            font-size: 9pt;
            margin: 10px 0;
            color: #555;
        }

        .footer {
            border-top: 1px solid #dee2e6;
            font-size: 7pt;
            color: #666;
            text-align: center;
            padding-top: 5px;
            margin-top: 6px;
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
            <td class="branding-seal-cell">
                <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA" class="header-img-right-seal">
            </td>
            <td class="branding-right-text">
                <div class="branding-hindi">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी</div>
                <div class="branding-en-side">Lal Bahadur Shastri National Academy of Administration</div>
            </td>
        </tr>
    </table>
</div>

<div class="report-header-block">
    <h1 class="report-title-center">Item Report</h1>
    <div class="report-date-bar">{{ $periodBar }}</div>
</div>

<div class="report-meta-print">
    <div class="meta-line"><strong>View:</strong> {{ $viewLabel }}</div>
    <div class="meta-line"><strong>Store:</strong> {{ $storeLabel }}</div>
    <div class="meta-line"><strong>Items:</strong> {{ $itemsLabel }}</div>
    <div class="meta-line"><strong>Generated on:</strong> {{ $printedOn }}</div>
</div>

@forelse($viewTypeSections as $section)
    @if(count($viewTypeSections) > 1)
        <div class="view-section-heading">{{ $section['viewLabel'] }}</div>
    @endif
    @if($section['viewType'] === 'item_wise')
        @if(empty($section['reportData']))
            <p class="no-data">No data found for the selected date range.</p>
        @else
            <table class="purchase-sale-data">
                <thead>
                <tr>
                    <th style="width: 34px; text-align: center;">S. No.</th>
                    <th>Item Name</th>
                    <th style="width: 48px;">Unit</th>
                    <th style="text-align: right;">Total Purchase Qty</th>
                    <th style="text-align: right;">Avg Purchase Price</th>
                    <th style="text-align: right;">Total Sale Qty</th>
                    <th style="text-align: right;">Avg Sale Price</th>
                </tr>
                </thead>
                <tbody>
                @foreach($section['reportData'] as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $row['item_name'] }}</td>
                        <td>{{ $row['unit'] }}</td>
                        <td class="text-end">{{ number_format($row['purchase_qty'], 2) }}</td>
                        <td class="text-end">
                            {{ $row['avg_purchase_price'] !== null ? '₹' . number_format($row['avg_purchase_price'], 2) : '—' }}
                        </td>
                        <td class="text-end">{{ number_format($row['sale_qty'], 2) }}</td>
                        <td class="text-end">
                            {{ $row['avg_sale_price'] !== null ? '₹' . number_format($row['avg_sale_price'], 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    @else
        @php $groupedData = $section['groupedData'] ?? []; @endphp
        @if(empty($groupedData))
            <p class="no-data">No data found for the selected filters.</p>
        @else
            @foreach($groupedData as $group)
                <div class="group-title">{{ $group['category_name'] }}</div>
                <table class="purchase-sale-data">
                    <thead>
                    <tr>
                        <th style="width: 34px; text-align: center;">S. No.</th>
                        <th>Item Name</th>
                        <th style="width: 48px;">Unit</th>
                        <th style="text-align: right;">Total Purchase Qty</th>
                        <th style="text-align: right;">Avg Purchase Price</th>
                        <th style="text-align: right;">Total Sale Qty</th>
                        <th style="text-align: right;">Avg Sale Price</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($group['items'] as $idx => $row)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>{{ $row['item_name'] }}</td>
                            <td>{{ $row['unit'] }}</td>
                            <td class="text-end">{{ number_format($row['purchase_qty'], 2) }}</td>
                            <td class="text-end">
                                {{ isset($row['avg_purchase_price']) && $row['avg_purchase_price'] !== null ? '₹' . number_format($row['avg_purchase_price'], 2) : '—' }}
                            </td>
                            <td class="text-end">{{ number_format($row['sale_qty'], 2) }}</td>
                            <td class="text-end">
                                {{ isset($row['avg_sale_price']) && $row['avg_sale_price'] !== null ? '₹' . number_format($row['avg_sale_price'], 2) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endforeach
        @endif
    @endif
@empty
    <p class="no-data">No data found for the selected filters.</p>
@endforelse

<div class="footer">
    <small>Officer's Mess LBSNAA Mussoorie — Item Report (Purchase / Sale Quantity)</small>
</div>
</body>
</html>
