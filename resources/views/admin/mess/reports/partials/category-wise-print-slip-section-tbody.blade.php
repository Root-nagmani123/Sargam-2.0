@php $sectionTotal = 0; @endphp
@foreach($sectionVouchers as $voucher)
    @php
        $requestNo = $voucher->request_no ?? ('SV-' . str_pad($voucher->id ?? $voucher->pk ?? 0, 6, '0', STR_PAD_LEFT));
        $requestDate = $voucher->issue_date ? $voucher->issue_date->format('d-m-Y') : 'N/A';
        $rowCount = max(1, $voucher->items->count());
    @endphp
    @if($voucher->items->isEmpty())
        <tr>
            <td class="text-center">{{ $requestNo }}</td>
            <td>{{ $voucher->remarks ?? '—' }}</td>
        </tr>
    @else
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
                @if($dompdfSafeTables)
                    <td class="text-center align-middle">{{ $requestNo }}</td>
                    <td>{{ $itemName }}</td>
                    <td class="text-center">{{ $itemIssueDateFormatted }}</td>
                    <td class="text-end">{{ number_format($netQty, 2) }}</td>
                    <td class="text-end">{{ number_format($rate, 2) }}</td>
                    <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                    @if($itemIndex === 0)
                        <td class="align-middle" rowspan="{{ $rowCount }}">{{ $voucher->remarks ?? '—' }}</td>
                    @endif
                @else
                    @if($itemIndex === 0)
                        <td class="text-center align-middle" rowspan="{{ $rowCount }}">{{ $requestNo }}</td>
                    @endif
                    <td>{{ $itemName }}</td>
                    <td class="text-center">{{ $itemIssueDateFormatted }}</td>
                    <td class="text-end">{{ number_format($netQty, 2) }}</td>
                    <td class="text-end">{{ number_format($rate, 2) }}</td>
                    <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                    @if($itemIndex === 0)
                        <td class="align-middle" rowspan="{{ $rowCount }}">{{ $voucher->remarks ?? '—' }}</td>
                    @endif
                @endif
            </tr>
        @endforeach
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
