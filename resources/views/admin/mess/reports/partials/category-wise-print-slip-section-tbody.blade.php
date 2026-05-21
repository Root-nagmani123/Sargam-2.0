@php
    $displayRows = mess_cw_slip_section_display_rows($sectionVouchers);
    $remarkLayout = mess_cw_slip_section_remark_layout($displayRows);
    $sectionTotal = 0;
    $firstV = $sectionVouchers->first();
    $cbBuyerName = trim((string) ($firstV->client_name ?? ($firstV->clientTypeCategory?->client_name ?? '')));
    $cbSlug = (string) ($firstV->client_type_slug ?? 'employee');
    $combinedSlipNo = mess_combined_bill_slip_no($cbBuyerName, $cbSlug);
    $totalRows = $displayRows->count();
    $rowIdx = 0;
@endphp
@foreach($displayRows as $loopIdx => $row)
    @php
        $remarkCell = $remarkLayout[$loopIdx] ?? ['show' => true, 'rowspan' => 1, 'remark' => '—'];
    @endphp
    @if($row->kind === 'empty')
        @php
            $voucher = $row->voucher;
            $requestDate = mess_cw_slip_row_display_date($row);
        @endphp
        <tr>
            @if($dompdfSafeTables)
                <td class="text-center align-middle">{{ $combinedSlipNo }}</td>
                <td>—</td>
                <td class="text-center">{{ $requestDate }}</td>
                <td class="text-end">—</td>
                <td class="text-end">—</td>
                <td class="text-end">—</td>
                <td class="align-middle">{{ $remarkCell['remark'] }}</td>
            @else
                @if($rowIdx === 0)
                    <td class="text-center align-middle" rowspan="{{ $totalRows }}">{{ $combinedSlipNo }}</td>
                @endif
                <td>—</td>
                <td class="text-center">{{ $requestDate }}</td>
                <td class="text-end">—</td>
                <td class="text-end">—</td>
                <td class="text-end">—</td>
                @if($remarkCell['show'])
                    <td class="align-middle" rowspan="{{ $remarkCell['rowspan'] }}">{{ $remarkCell['remark'] }}</td>
                @endif
            @endif
        </tr>
        @php $rowIdx++; @endphp
    @else
        @php
            $voucher = $row->voucher;
            $item = $row->item;
            $itemIssueDateFormatted = mess_cw_slip_row_display_date($row);
            $issueQty = (float) ($item->quantity ?? 0);
            $returnQty = (float) ($item->return_quantity ?? 0);
            $netQty = max(0, $issueQty - $returnQty);
            $rate = (float) ($item->rate ?? 0);
            $itemAmount = $netQty * $rate;
            $sectionTotal += $itemAmount;
            $itemName = $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? 'N/A');
        @endphp
        <tr>
            @if($dompdfSafeTables)
                <td class="text-center align-middle">{{ $combinedSlipNo }}</td>
                <td>{{ $itemName }}</td>
                <td class="text-center">{{ $itemIssueDateFormatted }}</td>
                <td class="text-end">{{ number_format($netQty, 2) }}</td>
                <td class="text-end">{{ number_format($rate, 2) }}</td>
                <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                <td class="align-middle">{{ $remarkCell['remark'] }}</td>
            @else
                @if($rowIdx === 0)
                    <td class="text-center align-middle" rowspan="{{ $totalRows }}">{{ $combinedSlipNo }}</td>
                @endif
                <td>{{ $itemName }}</td>
                <td class="text-center">{{ $itemIssueDateFormatted }}</td>
                <td class="text-end">{{ number_format($netQty, 2) }}</td>
                <td class="text-end">{{ number_format($rate, 2) }}</td>
                <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                @if($remarkCell['show'])
                    <td class="align-middle" rowspan="{{ $remarkCell['rowspan'] }}">{{ $remarkCell['remark'] }}</td>
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
