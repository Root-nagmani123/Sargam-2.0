<table>
    {{-- LBSNAA-style header (rows 1–5) --}}
    <tr>
        <td colspan="8" style="text-align:center; font-weight:700;">
            OFFICER'S MESS LBSNAA MUSSOORIE
        </td>
    </tr>
    <tr>
        <td colspan="8" style="text-align:center;">
            Stock Purchase Details
        </td>
    </tr>
    <tr>
        <td colspan="8" style="text-align:center;">
            Stock Purchase Details Report Between
            {{ \Carbon\Carbon::parse($fromDate)->format('d-F-Y') }}
            To
            {{ \Carbon\Carbon::parse($toDate)->format('d-F-Y') }}
        </td>
    </tr>
    <tr>
        <td colspan="8" style="text-align:center;">
            Vendor Details:
            @if($selectedVendors->isEmpty())
                All Vendors
            @else
                @foreach($selectedVendors as $selectedVendor)
                    {{ trim(implode(' | ', array_filter([
                        'Name: ' . $selectedVendor->name,
                        !empty($selectedVendor->contact_person) ? 'Contact: ' . $selectedVendor->contact_person : null,
                        !empty($selectedVendor->phone) ? 'Phone: ' . $selectedVendor->phone : null,
                        !empty($selectedVendor->email) ? 'Email: ' . $selectedVendor->email : null,
                        !empty($selectedVendor->address) ? 'Address: ' . $selectedVendor->address : null,
                    ]))) }}@if(!$loop->last) — @endif
                @endforeach
            @endif
        </td>
    </tr>
    <tr>
        <td colspan="8" style="text-align:center;">
            Store:
            @if($selectedStores->isEmpty())
                All Stores
            @else
                {{ $selectedStores->pluck('store_name')->implode(', ') }}
            @endif
        </td>
    </tr>

    {{-- Blank row --}}
    <tr><td colspan="8"></td></tr>

    {{-- Column headers (row 7) --}}
    <tr>
        <th>Item</th>
        <th>Item Code</th>
        <th>Unit</th>
        <th>Quantity</th>
        <th>Purchase</th>
        <th>Tax %</th>
        <th>Tax Amount</th>
        <th>Total</th>
    </tr>

    {{-- Body – reuse grouping and totals like the on-screen report --}}
    @forelse($purchaseOrders as $order)
        @php
            $storeName    = $order->store ? $order->store->store_name : 'N/A';
            $billLabel    = $storeName . ' (Primary) Bill No. ' . ($order->po_number ?? $order->id) . ' (' . $order->po_date->format('d-m-Y') . ')';
            $billSubtotal = 0;
            $billTaxTotal = 0;
        @endphp

        {{-- Bill header row --}}
        <tr>
            <td colspan="8">{{ $billLabel }}</td>
        </tr>

        @foreach($order->items as $item)
            @php
                $qty        = $item->quantity ?? 0;
                $rate       = $item->unit_price ?? 0;
                $taxPercent = $item->tax_percent ?? 0;
                $subtotal   = $qty * $rate;
                $taxAmount  = round($subtotal * ($taxPercent / 100), 2);
                $total      = $subtotal + $taxAmount;
                $billSubtotal += $subtotal;
                $billTaxTotal += $taxAmount;
            @endphp
            <tr>
                <td>{{ $item->itemSubcategory->item_name ?? $item->itemSubcategory->subcategory_name ?? $item->itemSubcategory->name ?? 'N/A' }}</td>
                <td>{{ $item->itemSubcategory->item_code ?? '' }}</td>
                <td>{{ $item->unit ?? '' }}</td>
                <td>{{ $qty }}</td>
                <td>{{ $rate }}</td>
                <td>{{ $taxPercent }}</td>
                <td>{{ $taxAmount }}</td>
                <td>{{ $total }}</td>
            </tr>
        @endforeach

        @php $billTotal = $billSubtotal + $billTaxTotal; @endphp
        <tr>
            <td colspan="7" style="text-align:right; font-weight:700;">Bill Total:</td>
            <td style="font-weight:700;">{{ $billTotal }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8">No purchase details found</td>
        </tr>
    @endforelse
</table>

