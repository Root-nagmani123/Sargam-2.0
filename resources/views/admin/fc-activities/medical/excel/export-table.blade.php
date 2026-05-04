<table>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center; font-weight:700;">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center;">Government of India — Mussoorie, Uttarakhand</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center; font-weight:600;">FC Activities — Medical trainees list</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center;">{{ $filterDescription }}</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center;">Generated: {{ $generatedAt }} | Records: {{ $tableRows->count() }}</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center;">Sargam 2.0</td>
    </tr>
    <tr><td colspan="{{ $colCount }}"></td></tr>
    <tr>
        <th>S.No.</th>
        <th>OT name</th>
        <th>OT code</th>
        <th>Course</th>
        <th>Service</th>
        <th>Pre-history</th>
        <th>Consultation</th>
    </tr>
    @forelse($tableRows as $row)
        <tr>
            <td>{{ $row['sno'] }}</td>
            <td>{{ $row['otname'] }}</td>
            <td>{{ $row['otcode'] }}</td>
            <td>{{ $row['course'] }}</td>
            <td>{{ $row['service'] }}</td>
            <td>{{ $row['pre_history'] }}</td>
            <td>{{ $row['consultation'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="{{ $colCount }}">No records for the selected filters.</td>
        </tr>
    @endforelse
</table>
