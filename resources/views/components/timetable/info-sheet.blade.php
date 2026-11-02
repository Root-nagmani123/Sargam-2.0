@props(['sheet' => []])

@php
    $counsellors   = $sheet['counsellors'] ?? [];
    $facultyLegend = $sheet['facultyLegend'] ?? [];
    $venueLegend   = $sheet['venueLegend'] ?? [];
    $guests        = $sheet['guestSpeakers'] ?? [];
    $languages     = $sheet['languageVenues'] ?? [];
@endphp

{{--
    The back of the weekly sheet ("P.T.O."), laid out as the issued one: counsellors,
    outdoor block and venue legend on the left; faculty legend and language venues
    on the right; guest speakers and the signatory across the foot. A box with no
    content is left out rather than printed empty.
--}}
<table class="is-cols">
    <tr>
        <td class="is-col">
            @if(count($counsellors))
                <table class="is-box">
                    <thead>
                        <tr>
                            <th style="width: 24%;">Cadre Counsellor</th>
                            <th>Cadre</th>
                            <th style="width: 26%;">Venue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($counsellors as $row)
                            <tr>
                                <td class="is-c">{{ $row['label'] }}</td>
                                <td class="is-c">{{ $row['cadres'] }}</td>
                                <td class="is-c">{{ $row['venue'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if(($sheet['outdoorActivities'] ?? '') !== '')
                <table class="is-box">
                    <thead><tr><th>Outdoor and Other Activities</th></tr></thead>
                    <tbody>
                        <tr><td class="is-free">{!! nl2br(e($sheet['outdoorActivities'])) !!}</td></tr>
                    </tbody>
                </table>
            @endif

            @if(count($venueLegend))
                <table class="is-box">
                    <thead><tr><th colspan="2">Venues Abbreviation</th></tr></thead>
                    <tbody>
                        @foreach($venueLegend as $venue)
                            <tr>
                                <td class="is-abbr">{{ $venue['abbreviation'] }}:</td>
                                <td class="is-l">{{ $venue['name'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </td>

        <td class="is-col">
            @if(count($facultyLegend))
                <table class="is-box">
                    <thead><tr><th colspan="2">Faculty Abbreviation</th></tr></thead>
                    <tbody>
                        @foreach($facultyLegend as $faculty)
                            <tr>
                                <td class="is-abbr">{{ $faculty['abbreviation'] }} :</td>
                                <td class="is-l">{{ $faculty['name'] }}@if($faculty['code']) ({{ $faculty['code'] }})@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if(count($languages))
                <table class="is-box">
                    <thead><tr><th colspan="2">Venue for Language Classes</th></tr></thead>
                    <tbody>
                        @foreach($languages as $row)
                            <tr>
                                <td class="is-abbr">{{ $row['language'] }} :</td>
                                <td class="is-l">{{ $row['venue'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </td>
    </tr>
</table>

@if(count($guests))
    <table class="is-box">
        <thead><tr><th>Guest Speakers</th></tr></thead>
        <tbody>
            <tr>
                <td class="is-guests">
                    <table class="is-guest-list">
                        @foreach($guests as $guest)
                            <tr>
                                <td class="is-guest-num">{{ $loop->iteration }}.</td>
                                <td class="is-guest-body">
                                    <span class="is-guest-name">{{ $guest['name'] }}@if($guest['code']) ({{ $guest['code'] }})@endif{{ $guest['designation'] !== '' ? ',' : '' }}</span>@if($guest['designation']) {{ $guest['designation'] }}@endif
                                    @if($guest['moderator'])
                                        <div>Session Moderator: {{ $guest['moderator'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
@endif

@if(($sheet['signatoryName'] ?? '') !== '' || ($sheet['signatoryDate'] ?? '') !== '')
    <table class="is-sign">
        <tr>
            <td class="is-sign-date">@if(($sheet['signatoryDate'] ?? '') !== '')Date: {{ $sheet['signatoryDate'] }}@endif</td>
            <td class="is-sign-who">
                @if(($sheet['signatoryName'] ?? '') !== '')
                    <div class="is-sign-name">({{ $sheet['signatoryName'] }})</div>
                @endif
                @if(($sheet['signatoryDesignation'] ?? '') !== '')
                    <div>{{ $sheet['signatoryDesignation'] }}</div>
                @endif
            </td>
        </tr>
    </table>
@endif
