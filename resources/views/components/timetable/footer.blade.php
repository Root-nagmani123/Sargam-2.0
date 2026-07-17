@props(['venueLine' => '', 'notes' => [], 'colspan' => 1])

{{--
    Emits rows into the timetable table itself so the venue line and notes sit
    inside the same bordered box as the grid, the way the printed sheet reads.
--}}
@if($venueLine !== '')
    <tr>
        <td class="tt-foot-venue" colspan="{{ $colspan }}"><b>VENUES:</b> {{ $venueLine }}</td>
    </tr>
@endif

@if(count($notes))
    <tr>
        <td class="tt-foot-notes" colspan="{{ $colspan }}">
            <table class="tt-notes">
                @foreach($notes as $note)
                    <tr>
                        @if($loop->first)
                            <td class="tt-notes-label" rowspan="{{ count($notes) }}">Note:</td>
                        @endif
                        <td class="tt-notes-num">{{ $loop->iteration }}.</td>
                        <td class="tt-notes-text">{{ $note }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
@endif
