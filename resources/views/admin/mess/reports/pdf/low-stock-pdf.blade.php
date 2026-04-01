@php
    $tillLabel = $tillDate ? date('d-F-Y', strtotime($tillDate)) : null;
    $storeLabel = $selectedStoreName ?? 'All Stores';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Low Stock Report</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            margin: 16px 18px;
            color: #222;
        }
        .page-header {
            border-bottom: 2px solid #004a93;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .page-header-top {
            display: table;
            width: 100%;
        }
        .page-header-col {
            display: table-cell;
            vertical-align: middle;
        }
        .logo-col {
            width: 44px;
        }
        .logo-col img {
            width: 34px;
            height: 34px;
            object-fit: contain;
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
            font-size: 8px;
            margin-top: 6px;
        }
        th, td {
            padding: 3px 4px;
            border: 1px solid #dde2ea;
        }
        thead th {
            background: #e6ecf5;
            font-weight: 600;
        }
        tbody tr:nth-child(even) td {
            background: #fafbfc;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .row-danger td {
            background: #fdeaea;
        }
        .footer {
            border-top: 1px solid #dde2ea;
            font-size: 7px;
            color: #666;
            text-align: center;
            padding-top: 3px;
            margin-top: 6px;
        }
    </style>
</head>
<body>
<div class="page-header">
    <div class="page-header-top">
        <div class="page-header-col logo-col">
            @if(!empty($lbsnaaLogoSrc))
                <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo">
            @endif
        </div>
        <div class="page-header-col page-header-title">
            <h1>OFFICER'S MESS LBSNAA MUSSOORIE</h1>
            <h2>Low Stock Report</h2>
            <div class="page-header-sub">Lal Bahadur Shastri National Academy of Administration</div>
        </div>
    </div>
    <div class="meta-row">
        <span>
            <strong>Till Date:</strong>
            {{ $tillLabel ?? '-' }}
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

@if(empty($items))
    <p>No items are currently below their minimum stock level for the selected filters.</p>
@else
    <table>
        <thead>
        <tr>
            <th class="text-center" style="width: 34px;">Sr.</th>
            <th>Item Name</th>
            <th class="text-center" style="width: 72px;">Unit</th>
            <th class="text-end" style="width: 90px;">Available Qty</th>
            <th class="text-end" style="width: 90px;">Alert Qty</th>
            <th class="text-center" style="width: 92px;">Status</th>
        </tr>
        </thead>
        <tbody>
        @foreach($items as $index => $row)
            @php
                $remaining = $row['remaining_quantity'] ?? 0;
                $alert = $row['alert_quantity'] ?? 0;
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
    </table>
@endif

<div class="footer">
    <small>Officer's Mess LBSNAA Mussoorie - Low Stock Report</small>
</div>
</body>
</html>
