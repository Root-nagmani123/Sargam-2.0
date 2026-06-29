@php
    use Carbon\Carbon;
    $brand  = '#2f5496';   // header band
    $brandD = '#1f3864';   // rules / accents
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Time table — Week {{ $weekNumber }}</title>
    <style>
        @page { margin: 20px 22px 34px 22px; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            font-family: "DejaVu Sans", sans-serif;
            color: #1a1a1a; font-size: 9px; line-height: 1.3;
        }

        /* ---------- Academy header ---------- */
        .doc-head { width: 100%; border-collapse: collapse; }
        .doc-head td { vertical-align: middle; }
        .doc-head .logo { width: 48px; text-align: center; }
        .doc-head .logo img { height: 42px; width: auto; }
        .doc-head .mid { text-align: center; padding: 0 8px; }
        .doc-head .academy { color: {{ $brandD }}; font-size: 12.5px; font-weight: bold; margin: 0; }
        .doc-head .prog { color: #333; font-size: 9px; font-weight: bold; margin: 2px 0 0 0; }
        .doc-head .period { color: #555; font-size: 8px; margin: 1px 0 0 0; }
        .head-rule { border: none; border-top: 1.6px solid {{ $brandD }}; margin: 5px 0 0 0; }

        /* ---------- Title bar: Venue | Time table | Week ---------- */
        .titlebar { width: 100%; border-collapse: collapse; margin: 7px 0 8px 0; }
        .titlebar td { vertical-align: bottom; }
        .titlebar .venue { font-size: 9px; }
        .titlebar .ttl { text-align: center; font-size: 18px; font-weight: bold; font-style: italic; color: #111; }
        .titlebar .wk { text-align: right; font-size: 9px; font-weight: bold; }

        /* ---------- Timetable grid ---------- */
        table.tt { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.tt th, table.tt td { border: 0.7px solid #9aa6b4; padding: 4px 4px; }
        table.tt thead th {
            background: {{ $brand }}; color: #fff; text-align: center;
            font-size: 9.5px; font-weight: bold; padding: 6px 4px; vertical-align: middle;
        }
        table.tt thead th .date { display: block; font-size: 8px; font-weight: normal; margin-top: 1px; }
        col.time-col { width: 9.5%; }
        col.day-col  { width: 18.1%; }

        td.time-cell {
            text-align: center; font-weight: bold; font-size: 9px; color: {{ $brandD }};
            vertical-align: middle; background: #eef2f8;
        }
        td.time-cell .t-to { font-weight: normal; font-size: 8px; color: #555; }

        td.day-cell { text-align: center; vertical-align: middle; }
        .sess { padding: 1px 0; }
        .sess + .sess { border-top: 0.6px dashed #c4ccd6; margin-top: 3px; padding-top: 4px; }
        .sess .topic { font-size: 9px; color: #111; }
        .sess .fac { font-weight: bold; font-size: 9px; color: #111; margin-top: 2px; }
        .sess .ven { font-size: 7.8px; font-style: italic; color: #666; margin-top: 1px; }

        td.break-cell {
            text-align: center; font-weight: bold; font-style: italic;
            font-size: 9.5px; color: {{ $brandD }}; background: #f1f4f9; vertical-align: middle;
        }

        .empty { text-align: center; color: #888; padding: 26px; font-size: 10px; }

        /* ---------- Footer ---------- */
        .foot-note { font-size: 8px; color: #333; margin-top: 7px; }
        .pto { text-align: right; font-size: 8.5px; font-weight: bold; font-style: italic; margin-top: 2px; }
        .page-footer {
            position: fixed; bottom: -22px; left: 0; right: 0;
            color: #777; font-size: 7px; text-align: center;
        }
    </style>
</head>
<body>

    {{-- Academy header --}}
    <table class="doc-head">
        <tr>
            <td class="logo">@if(!empty($emblemSrc))<img src="{{ $emblemSrc }}" alt="Emblem of India">@endif</td>
            <td class="mid">
                <p class="academy">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
                @if(!empty($programmeName))<p class="prog">{{ $programmeName }}</p>@endif
                @if(!empty($period))<p class="period">{{ $period }}</p>@endif
            </td>
            <td class="logo">@if(!empty($lbsnaaLogoSrc))<img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo">@endif</td>
        </tr>
    </table>
    <hr class="head-rule">

    {{-- Title bar --}}
    <table class="titlebar">
        <tr>
            <td class="venue" style="width:33%;">@if(!empty($topVenue))<strong>Venue:</strong> {{ $topVenue }}@endif</td>
            <td class="ttl" style="width:34%;">Time table</td>
            <td class="wk" style="width:33%;">Week: {{ str_pad((string) $weekNumber, 2, '0', STR_PAD_LEFT) }}</td>
        </tr>
    </table>

    @if(empty($rows))
        <p class="empty"><strong>No sessions scheduled for this week.</strong></p>
    @else
        <table class="tt">
            <colgroup>
                <col class="time-col">
                @foreach($days as $dayName)<col class="day-col">@endforeach
            </colgroup>
            <thead>
                <tr>
                    <th>Time</th>
                    @foreach($days as $idx => $dayName)
                        @php $colDate = $weekStart->copy()->addDays($idx - 1); @endphp
                        <th>{{ $dayName }}<span class="date">{{ $colDate->format('d.m.Y') }}</span></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td class="time-cell">
                            @if(!empty($row['endLbl']))
                                {{ $row['startLbl'] }}<br><span class="t-to">to</span><br>{{ $row['endLbl'] }}
                            @else
                                {{ $row['startLbl'] }}
                            @endif
                        </td>

                        @if($row['isBreak'])
                            <td class="break-cell" colspan="5">{{ $row['breakLabel'] }}</td>
                        @else
                            @foreach($days as $idx => $dayName)
                                <td class="day-cell">
                                    @if(!empty($row['byDay'][$idx]))
                                        @foreach($row['byDay'][$idx] as $sess)
                                            <div class="sess">
                                                <div class="topic">{{ $sess['topic'] }}</div>
                                                @if($sess['faculty'] !== '')<div class="fac">[{{ $sess['faculty'] }}]</div>@endif
                                                @if($sess['venue'] !== '' && $sess['venue'] !== $topVenue)<div class="ven">{{ $sess['venue'] }}</div>@endif
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                            @endforeach
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="pto">P.T.O.</p>
    @endif

    <div class="page-footer">
        Lal Bahadur Shastri National Academy of Administration, Mussoorie &nbsp;&middot;&nbsp; Generated {{ Carbon::now()->format('d M Y, h:i A') }}
    </div>
</body>
</html>
