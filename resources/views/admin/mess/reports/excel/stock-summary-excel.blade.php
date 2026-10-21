<table>
    {{-- LBSNAA-style header --}}
    <tr>
        <td colspan="16" style="text-align:center; font-weight:700;">
            OFFICER'S MESS LBSNAA MUSSOORIE
        </td>
    </tr>
    <tr>
        <td colspan="16" style="text-align:center;">
            Stock Summary Report
        </td>
    </tr>
    <tr>
        <td colspan="16" style="text-align:center;">
            Stock Summary Report Between
            {{ \Carbon\Carbon::parse($fromDate)->format('d-F-Y') }}
            To
            {{ \Carbon\Carbon::parse($toDate)->format('d-F-Y') }}
            &nbsp; | &nbsp;
            Store:
            {{ $selectedStoreName ?? ($storeType == 'main' ? "Officer's Main Mess(Primary)" : 'All Sub Stores') }}
        </td>
    </tr>

    {{-- Blank row --}}
    <tr><td colspan="16"></td></tr>

    {{-- Header rows matching the on-screen report --}}
    <tr>
        <th rowspan="2">SR. No</th>
        <th rowspan="2">Item Name</th>
        <th rowspan="2">Item Code</th>
        <th rowspan="2">Unit</th>
        <th colspan="3">Opening</th>
        <th colspan="3">Purchase</th>
        <th colspan="3">Sale</th>
        <th colspan="3">Closing</th>
    </tr>
    <tr>
        {{-- Opening --}}
        <th>Qty</th>
        <th>Rate</th>
        <th>Amount</th>
        {{-- Purchase --}}
        <th>Qty</th>
        <th>Rate</th>
        <th>Amount</th>
        {{-- Sale --}}
        <th>Qty</th>
        <th>Rate</th>
        <th>Amount</th>
        {{-- Closing --}}
        <th>Qty</th>
        <th>Rate</th>
        <th>Amount</th>
    </tr>

    {{-- Body --}}
    @forelse($reportData as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item['item_name'] }}</td>
            <td>{{ $item['item_code'] ?? '—' }}</td>
            <td>{{ $item['unit'] ?? '—' }}</td>

            {{-- Opening --}}
            <td>{{ $item['opening_qty'] }}</td>
            <td>{{ number_format((float) ($item['opening_rate'] ?? 0), 2) }}</td>
            <td>{{ number_format((float) ($item['opening_amount'] ?? 0), 2) }}</td>

            {{-- Purchase --}}
            <td>{{ $item['purchase_qty'] }}</td>
            <td>{{ number_format((float) ($item['purchase_rate'] ?? 0), 2) }}</td>
            <td>{{ number_format((float) ($item['purchase_amount'] ?? 0), 2) }}</td>

            {{-- Sale --}}
            <td>{{ $item['sale_qty'] }}</td>
            <td>{{ number_format((float) ($item['sale_rate'] ?? 0), 2) }}</td>
            <td>{{ number_format((float) ($item['sale_amount'] ?? 0), 2) }}</td>

            {{-- Closing --}}
            <td>{{ $item['closing_qty'] }}</td>
            <td>{{ number_format((float) ($item['closing_rate'] ?? 0), 2) }}</td>
            <td>{{ number_format((float) ($item['closing_amount'] ?? 0), 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="16">No stock movement found for the selected period</td>
        </tr>
    @endforelse
</table>

