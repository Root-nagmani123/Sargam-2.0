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
    $printedOn = now()->format('d-m-Y H:i');

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
        @page { size: A4 landscape; margin: 12mm 10mm; }
        * { box-sizing: border-box; }
        html { font-size: 9pt; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 9pt;
            margin: 0; padding: 0;
            color: #212529;
            background: #fff;
            line-height: 1.4;
        }

        /* ── Header ── */
        .pdf-header {
            border-bottom: 2.5px solid #0b4a7e;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .pdf-header table { width: 100%; border-collapse: collapse; }
        .pdf-header td { border: 0; padding: 0; vertical-align: middle; }
        .pdf-header .hdr-left { width: 50px; }
        .pdf-header .hdr-left img { width: 40px; height: 40px; }
        .pdf-header .hdr-center { padding-left: 10px; }
        .pdf-header .hdr-right { width: 50px; text-align: right; }
        .pdf-header .hdr-right img { width: 40px; height: 40px; }
        .brand-1 { font-size: 7pt; text-transform: uppercase; letter-spacing: 0.06em; color: #0b4a7e; font-weight: 600; }
        .brand-2 { font-size: 9.5pt; font-weight: 700; text-transform: uppercase; color: #111; margin-top: 2px; }
        .brand-3 { font-size: 7.5pt; color: #555; margin-top: 2px; }

        /* ── Report title block ── */
        .report-title-block {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title {
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
            margin: 0 0 5px;
        }
        .report-date-pill {
            display: inline-block;
            background: #0b4a7e;
            color: #fff;
            font-weight: 600;
            font-size: 8pt;
            padding: 3px 12px;
            border-radius: 10px;
        }

        /* ── Meta section ── */
        .report-meta {
            font-size: 8pt;
            margin-bottom: 8px;
            line-height: 1.5;
            color: #334155;
        }
        .report-meta .meta-label { font-weight: 700; color: #0f172a; }

        /* ── Data table ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 10px;
        }
        .data-table th,
        .data-table td {
            padding: 4px 6px;
            border: 1px solid #d1d5db;
            vertical-align: middle;
        }
        .data-table thead th {
            background: #0b4a7e;
            color: #fff;
            font-weight: 600;
            font-size: 8pt;
            text-align: left;
            white-space: nowrap;
        }
        .data-table thead th.text-end { text-align: right; }
        .data-table thead th.text-center { text-align: center; }
        .data-table .text-end { text-align: right; }
        .data-table .text-center { text-align: center; }
        .data-table tbody tr:nth-child(even) td { background: #f9fafb; }

        /* ── Group / section titles ── */
        .view-section-heading {
            margin-top: 12px;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 9pt;
            color: #0b4a7e;
            border-bottom: 2px solid #0b4a7e;
            padding-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .group-hdr td {
            background: #eef2f6;
            color: #0f172a;
            font-weight: 700;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-color: #cbd5e1;
            padding: 5px 6px;
        }

        .no-data {
            font-size: 8pt;
            margin: 10px 0;
            color: #64748b;
            padding: 12px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            text-align: center;
        }

        .footer {
            border-top: 1px solid #dee2e6;
            font-size: 7pt;
            color: #64748b;
            text-align: center;
            padding-top: 5px;
            margin-top: 10px;
        }

        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>

{{-- Header --}}
<div class="pdf-header">
    <table>
        <tr>
            <td class="hdr-left">
                <img src="{{ $emblemSrc }}" alt="Emblem of India">
            </td>
            <td class="hdr-center">
                <div class="brand-1">Government of India</div>
                <div class="brand-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                <div class="brand-3">Lal Bahadur Shastri National Academy of Administration</div>
            </td>
            <td class="hdr-right">
                <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo">
            </td>
        </tr>
    </table>
</div>

{{-- Title --}}
<div class="report-title-block">
    <h1 class="report-title">Item Report</h1>
    <div class="report-date-pill">{{ $periodBar }}</div>
</div>

{{-- Meta --}}
<div class="report-meta">
    <span class="meta-label">View:</span> {{ $viewLabel }}<br>
    <span class="meta-label">Store:</span> {{ $storeLabel }}<br>
    <span class="meta-label">Items:</span> {{ $itemsLabel }}<br>
    <span class="meta-label">Generated on:</span> {{ $printedOn }}
</div>

{{-- Data --}}
@forelse($viewTypeSections as $section)
    @if(count($viewTypeSections) > 1)
        <div class="view-section-heading">{{ $section['viewLabel'] }}</div>
    @endif

    @if($section['viewType'] === 'item_wise')
        @if(empty($section['reportData']))
            <p class="no-data">No data found for the selected date range.</p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width:34px;">S.No.</th>
                        <th>Item Name</th>
                        <th style="width:48px;">Unit</th>
                        <th class="text-end">Total Purchase Qty</th>
                        <th class="text-end">Avg Purchase Price</th>
                        <th class="text-end">Total Sale Qty</th>
                        <th class="text-end">Avg Sale Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($section['reportData'] as $index => $row)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $row['item_name'] }}</td>
                            <td>{{ $row['unit'] }}</td>
                            <td class="text-end">{{ number_format($row['purchase_qty'], 2) }}</td>
                            <td class="text-end">{{ $row['avg_purchase_price'] !== null ? '₹' . number_format($row['avg_purchase_price'], 2) : '—' }}</td>
                            <td class="text-end">{{ number_format($row['sale_qty'], 2) }}</td>
                            <td class="text-end">{{ $row['avg_sale_price'] !== null ? '₹' . number_format($row['avg_sale_price'], 2) : '—' }}</td>
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
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width:34px;">S.No.</th>
                        <th>Item Name</th>
                        <th style="width:48px;">Unit</th>
                        <th class="text-end">Total Purchase Qty</th>
                        <th class="text-end">Avg Purchase Price</th>
                        <th class="text-end">Total Sale Qty</th>
                        <th class="text-end">Avg Sale Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedData as $group)
                        <tr class="group-hdr">
                            <td colspan="7">{{ $group['category_name'] }}</td>
                        </tr>
                        @foreach($group['items'] as $idx => $row)
                            <tr>
                                <td class="text-center">{{ $idx + 1 }}</td>
                                <td>{{ $row['item_name'] }}</td>
                                <td>{{ $row['unit'] }}</td>
                                <td class="text-end">{{ number_format($row['purchase_qty'], 2) }}</td>
                                <td class="text-end">{{ isset($row['avg_purchase_price']) && $row['avg_purchase_price'] !== null ? '₹' . number_format($row['avg_purchase_price'], 2) : '—' }}</td>
                                <td class="text-end">{{ number_format($row['sale_qty'], 2) }}</td>
                                <td class="text-end">{{ isset($row['avg_sale_price']) && $row['avg_sale_price'] !== null ? '₹' . number_format($row['avg_sale_price'], 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
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
