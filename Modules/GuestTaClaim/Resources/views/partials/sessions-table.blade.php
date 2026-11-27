{{-- params: sessions (array of rows), editable (bool), prefix (input name prefix, e.g. 'sessions') or null for read-only --}}
@php($fmt = \Modules\GuestTaClaim\Support\Format::class)
<div class="gtc-table-wrap">
    <table class="table gtc-table mb-0">
        <thead><tr><th>Date</th><th>Time</th><th>Topic</th><th>Venue</th><th>Status</th>@if ($editable || $prefix)<th class="no-print"></th>@endif</tr></thead>
        <tbody>
        @foreach ($sessions as $i => $s)
            @php($taken = ($s['status'] ?? 'taken') === 'taken')
            @php($confirmed = $s['confirmed'] ?? true)
            <tr class="{{ $confirmed && ! $taken ? 'is-cancelled' : '' }}" data-session-confirmed="{{ $confirmed ? '1' : '0' }}">
                <td class="text-nowrap" data-show="date">{{ $fmt::date($s['session_date']) }}</td>
                <td class="text-nowrap" data-show="time">{{ $s['start_time'] }}–{{ $s['end_time'] }}</td>
                <td data-show="topic">{{ $s['topic'] }}</td>
                <td data-show="venue">{{ $s['venue'] }}</td>
                <td class="keep"><span class="gtc-chip {{ ! $confirmed ? 't-amber' : ($taken ? 't-green' : 't-grey') }}" data-show="status">{{ ! $confirmed ? 'Awaiting confirmation' : ($taken ? 'Taken' : 'Cancelled') }}</span></td>
                @if ($editable || $prefix)
                    <td class="keep no-print text-end">
                        @if ($prefix)
                            @foreach (['source_session_id', 'session_date', 'start_time', 'end_time', 'topic', 'venue', 'status'] as $k)
                                <input type="hidden" name="{{ $prefix }}[{{ $i }}][{{ $k }}]" value="{{ $s[$k] ?? '' }}" data-field="{{ $k }}">
                            @endforeach
                        @endif
                        @if ($editable)
                           <a href="{{ url('calendar/event/' . encrypt($s['source_session_id']) . '/edit') }}"
                            class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        @endif
                    </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
