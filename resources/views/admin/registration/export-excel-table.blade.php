<table>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center; font-weight:700;">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center;">Government of India — Mussoorie, Uttarakhand</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center; font-weight:600;">{{ $reportHeading }}</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center;">{{ $filterDescription }}</td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center;">
            Generated: {{ $generatedAt }} | Exported: {{ $tableRows->count() }}
            @if($truncated ?? false)
                of {{ $totalMatching }} (max row limit applied)
            @endif
        </td>
    </tr>
    <tr>
        <td colspan="{{ $colCount }}" style="text-align:center;">Sargam 2.0</td>
    </tr>
    <tr><td colspan="{{ $colCount }}"></td></tr>
    <tr>
        @foreach($columns as $col)
            <th>{{ $col['label'] }}</th>
        @endforeach
    </tr>
    @forelse($tableRows as $row)
        <tr>
            @foreach($columns as $col)
                <td>{{ $row[$col['key']] ?? '—' }}</td>
            @endforeach
        </tr>
    @empty
        <tr>
            <td colspan="{{ $colCount }}">No records for the selected filters.</td>
        </tr>
    @endforelse
</table>
