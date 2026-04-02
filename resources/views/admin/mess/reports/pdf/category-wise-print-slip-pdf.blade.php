@php
    $fromLabel = $fromDateFormatted ?? null;
    $toLabel = $toDateFormatted ?? null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sale Voucher Report</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            margin: 16px 18px;
            color: #222;
        }
        .page {
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: auto;
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
        .page-header-col.logo {
            width: 60px;
            text-align: center;
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
        .buyer-header {
            margin: 6px 0 4px;
            padding: 4px 6px;
            border-radius: 3px;
            background: #f3f6fb;
            border: 1px solid #cfd8ea;
            font-size: 8px;
            display: flex;
            justify-content: space-between;
        }
        .buyer-header strong {
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 16px;
            margin-bottom: 8px;
        }
        th, td {
            padding: 2px 4px;
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
        .total-row {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .grand-total-row {
            background-color: #d8e4ef;
            font-weight: bold;
            border-top: 2px solid #004a93;
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
@if($sectionsToShow->isEmpty())
    <p>No selling vouchers found for the selected filters.</p>
@else
    @foreach($sectionsToShow as $groupedSections)
        <div class="page">
            <div class="page-header">
                <div class="page-header-top">
                   
                    <div class="page-header-col page-header-title">
                        <h1>OFFICER'S MESS LBSNAA MUSSOORIE</h1>
                        <h2>Sale Voucher Report</h2>
                        <div class="page-header-sub">Lal Bahadur Shastri National Academy of Administration</div>
                    </div>
                </div>
                <div class="meta-row">
                    @php
                        $fromText = $fromLabel ?? 'Start';
                        $toText = $toLabel ?? 'End';
                    @endphp
                    <span><strong>Period:</strong>
                        @if($fromLabel || $toLabel)
                            Between {{ $fromText }} To {{ $toText }}
                        @else
                            All Dates
                        @endif
                    </span>
                    <span><strong>Generated on:</strong> {{ now()->format('d-m-Y H:i') }}</span>
                </div>
            </div>

            @forelse($groupedSections as $groupKey => $sectionVouchers)
                @php
                    $first = $sectionVouchers->first();
                    $buyerName = $first->client_name ?? ($first->clientTypeCategory->client_name ?? 'N/A');
                    $clientTypeLabel = $first->clientTypeCategory
                        ? ucfirst($first->clientTypeCategory->client_type)
                        : ucfirst($first->client_type_slug ?? 'N/A');
                    $slug = $first->client_type_slug ?? '';
                    $typeSuffix = ($slug === 'employee') ? 'Employee' : (($slug === 'ot') ? 'OT' : ucfirst($slug));
                    if (!$typeSuffix) $typeSuffix = 'N/A';
                    $courseDisplay = null;
                    if ($slug === 'course' && !empty($courseMasterPk) && isset($otCourses) && $otCourses->isNotEmpty()) {
                        $selectedCourse = $otCourses->firstWhere('pk', $courseMasterPk);
                        if ($selectedCourse) {
                            $courseDisplay = $selectedCourse->course_name;
                        }
                    }
                @endphp

                <div class="buyer-header">
                    <span><strong>Buyer Name :</strong> {{ $buyerName }}- {{ $typeSuffix }}</span>
                    <span><strong>Client Type :</strong> {{ $clientTypeLabel }}@if($courseDisplay) <strong>[{{ $courseDisplay }}]</strong>@endif</span>
                </div>

                <table style="font-size: 14px;">
                    <thead>
                    <tr>
                        <th>Slip No.</th>
                        <th style="width: 150px;">Buyer Name</th>
                        <th>Remark</th>
                        <th>Item Name</th>
                        <th>Request Date</th>
                        <th style="text-align: right;">Quantity</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php $sectionTotal = 0; @endphp
                    @foreach($sectionVouchers as $voucher)
                        @php
                            $requestNo = $voucher->request_no ?? ('SV-' . str_pad($voucher->id ?? $voucher->pk ?? 0, 6, '0', STR_PAD_LEFT));
                            $requestDate = $voucher->issue_date ? $voucher->issue_date->format('d-m-Y') : 'N/A';
                            $rowCount = $voucher->items->count();
                        @endphp
                        @foreach($voucher->items as $itemIndex => $item)
                            @php
                                $issueQty = (float) ($item->quantity ?? 0);
                                $returnQty = (float) ($item->return_quantity ?? 0);
                                $netQty = max(0, $issueQty - $returnQty);
                                $rate = (float) ($item->rate ?? 0);
                                $itemAmount = $netQty * $rate;
                                $sectionTotal += $itemAmount;
                                $itemName = $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? 'N/A');
                                $itemIssueDate = $item->issue_date ?? null;
                                $itemIssueDateFormatted = $itemIssueDate
                                    ? ($itemIssueDate instanceof \Carbon\Carbon
                                        ? $itemIssueDate->format('d-m-Y')
                                        : \Carbon\Carbon::parse($itemIssueDate)->format('d-m-Y'))
                                    : $requestDate;
                            @endphp
                            <tr>
                                @if($itemIndex === 0)
                                    <td class="text-center" rowspan="{{ $rowCount }}">{{ $requestNo }}</td>
                                    <td rowspan="{{ $rowCount }}">{{ $buyerName }}</td>
                                    <td rowspan="{{ $rowCount }}">{{ $voucher->remarks ?? '—' }}</td>
                                @endif
                                <td>{{ $itemName }}</td>
                                <td class="text-center">{{ $itemIssueDateFormatted }}</td>
                                <td class="text-end">{{ number_format($netQty, 2) }}</td>
                                <td class="text-end">{{ number_format($rate, 2) }}</td>
                                <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="total-row">
                        <td colspan="6"></td>
                        <td class="text-end"><strong>TOTAL</strong></td>
                        <td class="text-end"><strong>{{ number_format($sectionTotal, 2) }}</strong></td>
                    </tr>
                    </tbody>
                </table>
            @empty
                <p>No selling vouchers found for the selected filters.</p>
            @endforelse

            <div class="footer">
                <small>Officer's Mess LBSNAA Mussoorie &mdash; Sale Voucher Report</small>
            </div>
        </div>
    @endforeach

        <div class="page">
            <div class="page-header">
                <div class="page-header-top">
                    <div class="page-header-col page-header-title">
                        <h1>OFFICER'S MESS LBSNAA MUSSOORIE</h1>
                        <h2>Sale Voucher Report</h2>
                        <div class="page-header-sub">Grand total — all buyers</div>
                    </div>
                </div>
                <div class="meta-row">
                    @php
                        $fromTextGt = $fromLabel ?? 'Start';
                        $toTextGt = $toLabel ?? 'End';
                    @endphp
                    <span><strong>Period:</strong>
                        @if($fromLabel || $toLabel)
                            Between {{ $fromTextGt }} To {{ $toTextGt }}
                        @else
                            All Dates
                        @endif
                    </span>
                    <span><strong>Generated on:</strong> {{ now()->format('d-m-Y H:i') }}</span>
                </div>
            </div>
            <table style="font-size: 14px;">
                <thead>
                <tr>
                    <th>Slip No.</th>
                    <th style="width: 150px;">Buyer Name</th>
                    <th>Remark</th>
                    <th>Item Name</th>
                    <th>Request Date</th>
                    <th style="text-align: right;">Quantity</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
                </thead>
                <tbody>
                <tr class="grand-total-row">
                    <td colspan="6"></td>
                    <td class="text-end"><strong>GRAND TOTAL</strong></td>
                    <td class="text-end"><strong>{{ number_format($grandTotal ?? 0, 2) }}</strong></td>
                </tr>
                </tbody>
            </table>
            <div class="footer">
                <small>Officer's Mess LBSNAA Mussoorie &mdash; Sale Voucher Report</small>
            </div>
        </div>
@endif
</body>
</html>

