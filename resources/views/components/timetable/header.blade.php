@props([
    'titleHindi'     => null,
    'logoLeft'       => null,
    'logoRight'      => null,
    'instituteName'  => '',
    'courseName'     => '',
    'courseDuration' => '',
    'weekNumber'     => null,
    'rangeLabel'     => '',
])

<table class="tt-head">
    <tr>
        <td class="tt-head-logo">
            @if($logoLeft)<img src="{{ $logoLeft }}" alt="">@endif
        </td>
        <td class="tt-head-mid">
            {{-- Devanagari is a pre-shaped image: DomPDF cannot shape Indic scripts. --}}
            @if($titleHindi)<img class="tt-head-hi" src="{{ $titleHindi }}" alt="">@endif
            @if($instituteName)<div class="tt-head-en">{{ $instituteName }}</div>@endif
            @if($courseName)<div class="tt-head-course">{{ $courseName }}</div>@endif
            @if($courseDuration)<div class="tt-head-dates">({{ $courseDuration }})</div>@endif
            @if($weekNumber !== null)
                <div class="tt-head-week">
                    Time Table: Week-{{ str_pad((string) $weekNumber, 2, '0', STR_PAD_LEFT) }}
                    @if($rangeLabel)<span class="tt-head-range">({{ $rangeLabel }})</span>@endif
                </div>
            @endif
        </td>
        <td class="tt-head-logo">
            @if($logoRight)<img src="{{ $logoRight }}" alt="">@endif
        </td>
    </tr>
</table>
