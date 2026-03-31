@php
    $fromLabel = $fromDate ? date('d-F-Y', strtotime($fromDate)) : null;
    $toLabel = $toDate ? date('d-F-Y', strtotime($toDate)) : null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Item Report - Purchase/Sale Quantity</title>
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
            padding: 2px 3px;
            border: 1px solid #dde2ea;
        }
        thead th {
            background: #e6ecf5;
            font-weight: 600;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        tbody tr:nth-child(even) td {
            background: #fafbfc;
        }
        .group-title {
            margin-top: 10px;
            margin-bottom: 4px;
            font-weight: 600;
            color: #004a93;
        }
        .footer {
            border-top: 1px solid #dde2ea;
            font-size: 7px;
            color: #666;
            text-align: center;
            padding-top: 3px;
            margin-top: 4px;
        }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
    </style>
</head>
<body>
<div class="page-header">
    <div class="page-header-top">
        <div class="page-header-col page-header-title">
            <h1>OFFICER'S MESS LBSNAA MUSSOORIE</h1>
            <h2>Item Report - Purchase/Sale Quantity</h2>
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
            From {{ $fromText }} To {{ $toText }}
        </span>
        <span>
            <strong>View Type:</strong>
            {{ $viewType === 'item_wise' ? 'Item-wise' : ($viewType === 'subcategory_wise' ? 'Subcategory-wise' : 'Category-wise') }}
        </span>
        <span>
            <strong>Store:</strong> {{ $selectedStoreName ?? 'All Stores' }}
        </span>
        <span>
            <strong>Items:</strong> {{ $selectedItemNamesLabel ?? 'All Items' }}
        </span>
        <span>
            <strong>Generated on:</strong> {{ now()->format('d-m-Y H:i') }}
        </span>
    </div>
</div>

@if($viewType === 'item_wise')
    @if(empty($reportData))
        <p>No data found for the selected date range.</p>
    @else
        <table>
            <thead>
            <tr>
                <th style="width: 30px;" class="text-center">S. No.</th>
                <th>Item Name</th>
                <th>Unit</th>
                <th class="text-end">Total Purchase Qty</th>
                <th class="text-end">Avg Purchase Price</th>
                <th class="text-end">Total Sale Qty</th>
                <th class="text-end">Avg Sale Price</th>
            </tr>
            </thead>
            <tbody>
            @foreach($reportData as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['item_name'] }}</td>
                    <td>{{ $row['unit'] }}</td>
                    <td class="text-end">{{ number_format($row['purchase_qty'], 2) }}</td>
                    <td class="text-end">
                        {{ $row['avg_purchase_price'] !== null ? number_format($row['avg_purchase_price'], 2) : '—' }}
                    </td>
                    <td class="text-end">{{ number_format($row['sale_qty'], 2) }}</td>
                    <td class="text-end">
                        {{ $row['avg_sale_price'] !== null ? number_format($row['avg_sale_price'], 2) : '—' }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@else
    @php $groupedData = $groupedData ?? []; @endphp
    @if(empty($groupedData))
        <p>No data found for the selected filters.</p>
    @else
        @foreach($groupedData as $group)
            <div class="group-title">{{ $group['category_name'] }}</div>
            <table>
                <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">S. No.</th>
                    <th>Item Name</th>
                    <th>Unit</th>
                    <th class="text-end">Total Purchase Qty</th>
                    <th class="text-end">Avg Purchase Price</th>
                    <th class="text-end">Total Sale Qty</th>
                    <th class="text-end">Avg Sale Price</th>
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
                            {{ isset($row['avg_purchase_price']) && $row['avg_purchase_price'] !== null ? number_format($row['avg_purchase_price'], 2) : '—' }}
                        </td>
                        <td class="text-end">{{ number_format($row['sale_qty'], 2) }}</td>
                        <td class="text-end">
                            {{ isset($row['avg_sale_price']) && $row['avg_sale_price'] !== null ? number_format($row['avg_sale_price'], 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endforeach
    @endif
@endif

<div class="footer">
    <small>Officer's Mess LBSNAA Mussoorie &mdash; Item Report - Purchase/Sale Quantity</small>
</div>
</body>
</html>

