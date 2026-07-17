@props([
    'days'         => [],
    'groups'       => [],
    'rows'         => [],
    'hasGroupAxis' => false,
    'venueLine'    => '',
    'notes'        => [],
])

@php
    // Time + optional GROUP + one column per day that actually has events.
    $leadCols  = $hasGroupAxis ? 2 : 1;
    $dayCount  = count($days);
    $totalCols = $leadCols + $dayCount;

    $timeWidth  = 7;
    $groupWidth = $hasGroupAxis ? 5 : 0;
    $dayWidth   = $dayCount > 0 ? round((100 - $timeWidth - $groupWidth) / $dayCount, 3) : 0;
@endphp

{{--
    Widths are declared on the header cells, not on <col>. Under table-layout:fixed
    DomPDF sizes columns from the first row and ignores colgroup widths entirely —
    left on <col> alone it splits the page evenly and TIME/GROUP each eat a full
    day's worth of space.
--}}
<table class="tt-grid">
    <thead>
        <tr>
            <th style="width: {{ $timeWidth }}%;">TIME</th>
            @if($hasGroupAxis)<th style="width: {{ $groupWidth }}%;">GROUP</th>@endif
            @foreach($days as $day)
                <th style="width: {{ $dayWidth }}%;">{{ $day['name'] }}<span class="tt-th-date">{{ $day['label'] }}</span></th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        @foreach($rows as $row)
            <tr>
                @if($row['band'] !== null)
                    {{-- Bands span every column, TIME and GROUP included, so the
                         band carries its own times rather than the time cell. --}}
                    <x-timetable.break-row
                        :label="$row['band']"
                        :from="$row['from']"
                        :to="$row['to']"
                        :colspan="$totalCols" />
                @else
                    @if($row['showTime'])
                        <td class="tt-time" rowspan="{{ $row['timeRowspan'] }}">
                            {{ $row['from'] }}<span class="tt-time-to">to</span>{{ $row['to'] }}
                        </td>
                    @endif

                    @if($hasGroupAxis)
                        <td class="tt-group">{{ $row['group'] }}</td>
                    @endif

                    @foreach($days as $day)
                        @php $cell = $row['cells'][$day['key']] ?? null; @endphp
                        {{-- null means a cell above already spans this slot. --}}
                        @continue($cell === null)

                        <td class="tt-cell {{ !empty($cell['isBreak']) ? 'tt-cell-break' : '' }}"
                            rowspan="{{ $cell['rowspan'] }}">
                            <x-timetable.event-cell :events="$cell['events']" />
                        </td>
                    @endforeach
                @endif
            </tr>
        @endforeach

        {{-- Venue + notes close out the same bordered box, as on the printed sheet. --}}
        <x-timetable.footer :venue-line="$venueLine" :notes="$notes" :colspan="$totalCols" />
    </tbody>
</table>
