@php
    $displayRows = mess_cw_slip_section_display_rows($sectionVouchers);
    $sectionTotal = 0;
    $firstV = $sectionVouchers->first();
    $cbBuyerName = trim((string) ($firstV->client_name ?? ($firstV->clientTypeCategory?->client_name ?? '')));
    $cbSlug = (string) ($firstV->client_type_slug ?? 'employee');
    $combinedSlipNo = mess_combined_bill_slip_no($cbBuyerName, $cbSlug);
    $combinedRemarks = $sectionVouchers
        ->map(fn ($v) => trim((string) ($v->remarks ?? '')))
        ->filter()
        ->unique()
        ->implode('; ');
    if ($combinedRemarks === '') {
        $combinedRemarks = '—';
    }
    $totalRows = $displayRows->count();
    $rowIdx = 0;
@endphp
@foreach($displayRows as $row)
    @if($row->kind === 'empty')
        @php
            $voucher = $row->voucher;
            $requestDate = $voucher->issue_date ? $voucher->issue_date->format('d-m-Y') : 'N/A';
        @endphp
        <tr>
            @if($dompdfSafeTables)
                <td class="text-center align-middle">{{ $combinedSlipNo }}</td>
                <td class="align-middle">{{ $combinedRemarks }}</td>
                <td>—</td>
                <td class="text-center">{{ $requestDate }}</td>
                <td class="text-end">—</td>
                <td class="text-end">—</td>
                <td class="text-end">—</td>
                <td class="align-middle">{{ $combinedRemarks }}</td>
            @else
                @if($rowIdx === 0)
                    <td class="text-center align-middle" rowspan="{{ $totalRows }}">{{ $combinedSlipNo }}</td>
                    <td class="align-middle" rowspan="{{ $totalRows }}">{{ $combinedRemarks }}</td>
                @endif
                <td>—</td>
                <td class="text-center">{{ $requestDate }}</td>
                <td class="text-end">—</td>
                <td class="text-end">—</td>
                <td class="text-end">—</td>
                @if($rowIdx === 0)
                    <td class="align-middle" rowspan="{{ $totalRows }}">{{ $combinedRemarks }}</td>
                @endif
            @endif
        </tr>
        @php $rowIdx++; @endphp
    @else
        @php
            $voucher = $row->voucher;
            $item = $row->item;
            $requestDate = $voucher->issue_date ? $voucher->issue_date->format('d-m-Y') : 'N/A';
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
            @if($dompdfSafeTables)
                <td class="text-center align-middle">{{ $combinedSlipNo }}</td>
                <td>{{ $itemName }}</td>
                <td class="text-center">{{ $itemIssueDateFormatted }}</td>
                <td class="text-end">{{ number_format($netQty, 2) }}</td>
                <td class="text-end">{{ number_format($rate, 2) }}</td>
                <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                <td class="align-middle">{{ $combinedRemarks }}</td>
            @else
                @if($rowIdx === 0)
                    <td class="text-center align-middle" rowspan="{{ $totalRows }}">{{ $combinedSlipNo }}</td>
                @endif
                <td>{{ $itemName }}</td>
                <td class="text-center">{{ $itemIssueDateFormatted }}</td>
                <td class="text-end">{{ number_format($netQty, 2) }}</td>
                <td class="text-end">{{ number_format($rate, 2) }}</td>
                <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                @if($rowIdx === 0)
                    <td class="align-middle" rowspan="{{ $totalRows }}">{{ $combinedRemarks }}</td>
                @endif
            @endif
        </tr>
        @php $rowIdx++; @endphp
    @endif
@endforeach
<tr class="total-row">
    @if($dompdfSafeTables)
        <td></td><td></td><td></td><td></td>
        <td class="text-end"><strong>TOTAL</strong></td>
        <td class="text-end"><strong>{{ number_format($sectionTotal, 2) }}</strong></td>
        <td></td>
    @else
        <td colspan="4"></td>
        <td class="text-end"><strong>TOTAL</strong></td>
        <td class="text-end"><strong>{{ number_format($sectionTotal, 2) }}</strong></td>
        <td></td>
    @endif
</tr>
