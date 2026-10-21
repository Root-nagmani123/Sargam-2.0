<table>
    {{-- LBSNAA-style header (rows 1–5) --}}
    <tr>
        <td colspan="{{ count($visibleColumns) }}" style="text-align:center; font-weight:700;">
            LBSNAA MUSSOORIE
        </td>
    </tr>
    <tr>
        <td colspan="{{ count($visibleColumns) }}" style="text-align:center;">
            Timetable Session Report
        </td>
    </tr>
    <tr>
        <td colspan="{{ count($visibleColumns) }}" style="text-align:center;">
            @php
                $filterParts = [];
                if (!empty($filterSummary['course_name'])) {
                    $filterParts[] = 'Course: ' . $filterSummary['course_name'];
                }
                if (!empty($filterSummary['faculty_name'])) {
                    $filterParts[] = 'Faculty: ' . $filterSummary['faculty_name'];
                }
                if (!empty($filterSummary['faculty_type'])) {
                    $filterParts[] = 'Faculty Type: ' . $filterSummary['faculty_type'];
                }
                if (!empty($filterSummary['venue_name'])) {
                    $filterParts[] = 'Venue: ' . $filterSummary['venue_name'];
                }
                if (!empty($filterSummary['subject_topic'])) {
                    $filterParts[] = 'Topic: ' . $filterSummary['subject_topic'];
                }
                if (!empty($filterSummary['module_name'])) {
                    $filterParts[] = 'Module: ' . $filterSummary['module_name'];
                }
                if (!empty($filterSummary['date_from'])) {
                    $filterParts[] = 'From: ' . date('d-M-Y', strtotime($filterSummary['date_from']));
                }
                if (!empty($filterSummary['date_to'])) {
                    $filterParts[] = 'To: ' . date('d-M-Y', strtotime($filterSummary['date_to']));
                }
            @endphp
            {{ !empty($filterParts) ? implode(' | ', $filterParts) : 'No filters applied' }}
        </td>
    </tr>
    <tr>
        <td colspan="{{ count($visibleColumns) }}" style="text-align:center;">
            Course Mode: {{ ucfirst($filterSummary['course_mode'] ?? 'active') }}
        </td>
    </tr>
    <tr>
        <td colspan="{{ count($visibleColumns) }}" style="text-align:center;">
            Printed on: {{ now()->format('d-m-Y H:i') }} | Total Records: {{ count($rows) }}
        </td>
    </tr>

    {{-- Blank row --}}
    <tr><td colspan="{{ count($visibleColumns) }}"></td></tr>

    {{-- Column headers --}}
    <tr>
        @foreach($visibleColumns as $col)
            <th>{{ $col['label'] }}</th>
        @endforeach
    </tr>

    {{-- Data rows --}}
    @forelse($rows as $index => $row)
        <tr>
            @foreach($visibleColumns as $col)
                @if($col['key'] === 'sno')
                    <td>{{ $index + 1 }}</td>
                @else
                    <td>{{ $row[$col['key']] ?? '' }}</td>
                @endif
            @endforeach
        </tr>
    @empty
        <tr>
            <td colspan="{{ count($visibleColumns) }}">No records found</td>
        </tr>
    @endforelse
</table>
