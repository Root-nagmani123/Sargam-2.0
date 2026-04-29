<table>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center; font-weight:700;">LBSNAA MUSSOORIE</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center;">FC Travel Plans — Joining (Joining Date report)</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center;">{{ $filterDescription }}</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center;">Printed: {{ $generatedAt }} | Total records: {{ $tableRows->count() }}</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center;">Sargam LMS</td>
    </tr>

    <tr><td colspan="{{ $colCount }}"></td></tr>

    <tr>
        <th>S.No.</th>
        <th>Name</th>
        <th>Code</th>
        <th>Mobile</th>
        <th>Arrival date</th>
        <th>Activity slot</th>
        <th>Activity slot<br>time</th>
        <th>Mode of journey</th>
        <th>Flight No / Train No /<br>Vehicle No</th>
        <th>Arrival time at Dehradun<br>Airport/Railway Station</th>
        <th>Whether Require Academy Vehicle<br>From Dehradun Airport/Railway Station to Academy</th>
        <th>Service</th>
        <th>Submitted</th>
    </tr>

    @forelse($tableRows as $row)
        <tr>
            <td>{{ $row['sno'] }}</td>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['code'] }}</td>
            <td>{{ $row['mobile'] }}</td>
            <td>{{ $row['arrival_date'] }}</td>
            <td>{{ $row['slot'] }}</td>
            <td>{{ $row['time_slot'] }}</td>
            <td>{{ $row['mode'] }}</td>
            <td>{{ $row['vehicle_no'] }}</td>
            <td>{{ $row['dehradun_time'] }}</td>
            <td>{{ $row['require_vehicle'] }}</td>
            <td>{{ $row['service'] }}</td>
            <td>{{ $row['submitted'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="{{ $colCount }}">No records found</td>
        </tr>
    @endforelse
</table>
