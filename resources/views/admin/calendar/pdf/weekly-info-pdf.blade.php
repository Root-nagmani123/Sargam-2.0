@php
    use Carbon\Carbon;
    $brand  = '#2f5496';
    $brandD = '#1f3864';
    $weekDates = $weekStart->format('d M') . ' – ' . $weekStart->copy()->addDays(4)->format('d M Y');
    $infoVal = function ($v) { $v = trim((string) $v); return $v !== '' ? $v : '—'; };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Course Information &amp; Faculty for the Week</title>
    <style>
        @page { margin: 22px 26px 36px 26px; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            font-family: "DejaVu Sans", sans-serif;
            color: #1a1a1a; font-size: 9.5px; line-height: 1.35;
        }

        /* ---------- Header ---------- */
        .doc-head { width: 100%; border-collapse: collapse; }
        .doc-head td { vertical-align: middle; }
        .doc-head .logo { width: 50px; text-align: center; }
        .doc-head .logo img { height: 44px; width: auto; }
        .doc-head .mid { text-align: center; padding: 0 8px; }
        .doc-head .academy { color: {{ $brandD }}; font-size: 13px; font-weight: bold; margin: 0; }
        .doc-head .prog { color: #333; font-size: 9.5px; font-weight: bold; margin: 2px 0 0 0; }
        .doc-head .period { color: #555; font-size: 8.5px; margin: 1px 0 0 0; }
        .head-rule { border: none; border-top: 1.6px solid {{ $brandD }}; margin: 5px 0 10px 0; }

        /* ---------- Section title ---------- */
        .sec {
            background: {{ $brand }}; color: #fff; font-size: 10px; font-weight: bold;
            text-transform: uppercase; letter-spacing: .4px; padding: 4px 8px; margin: 12px 0 0 0;
        }

        /* ---------- Course information ---------- */
        table.info { width: 100%; border-collapse: collapse; }
        table.info td { border: 0.7px solid #c4ccd6; padding: 4px 7px; vertical-align: top; }
        table.info td.lbl {
            width: 22%; background: #eef2f8; color: #333; font-weight: bold; font-size: 8.8px;
        }
        table.info td.val { width: 28%; }

        /* ---------- Resource persons ---------- */
        table.fac { width: 100%; border-collapse: collapse; margin-top: 0; }
        table.fac th, table.fac td { border: 0.7px solid #9aa6b4; padding: 4px 5px; vertical-align: top; }
        table.fac thead th {
            background: {{ $brand }}; color: #fff; font-size: 9px; font-weight: bold; text-align: left;
        }
        table.fac td { font-size: 8.8px; }
        table.fac .c-sno  { width: 6%; text-align: center; }
        table.fac .c-name { width: 26%; }
        table.fac .c-desg { width: 34%; }
        table.fac .c-topic{ width: 22%; }
        table.fac .c-date { width: 12%; text-align: center; }
        table.fac tbody tr:nth-child(even) td { background: #f6f8fb; }
        .fac-name { font-weight: bold; color: #111; }
        .empty { text-align: center; color: #888; padding: 16px; font-size: 9px; }

        /* ---------- Signature / footer ---------- */
        .sign { width: 100%; border-collapse: collapse; margin-top: 26px; }
        .sign td { width: 50%; vertical-align: bottom; font-size: 9px; }
        .sign .right { text-align: right; }
        .sign .line { display: inline-block; border-top: 0.8px solid #555; padding-top: 2px; min-width: 150px; font-weight: bold; }
        .page-footer {
            position: fixed; bottom: -24px; left: 0; right: 0;
            color: #777; font-size: 7px; text-align: center;
        }
    </style>
</head>
<body>

    {{-- Header --}}
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

    {{-- Course Information --}}
    <div class="sec">Course Information</div>
    <table class="info">
        <tr>
            <td class="lbl">Programme Name</td>
            <td class="val">{{ $infoVal($programmeName) }}</td>
            <td class="lbl">Batch / Code</td>
            <td class="val">{{ $infoVal($shortName) }}</td>
        </tr>
        <tr>
            <td class="lbl">Period</td>
            <td class="val">{{ $infoVal($period) }}</td>
            <td class="lbl">Week</td>
            <td class="val">Week {{ str_pad((string) $weekNumber, 2, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <td class="lbl">Week Dates</td>
            <td class="val">{{ $weekDates }}</td>
            <td class="lbl">Venue</td>
            <td class="val">{{ $infoVal($topVenue) }}</td>
        </tr>
        <tr>
            <td class="lbl">No. of Participants</td>
            <td class="val">{{ $participants !== null && $participants > 0 ? $participants : '—' }}</td>
            <td class="lbl">Director</td>
            <td class="val">{{ $infoVal($director) }}</td>
        </tr>
        <tr>
            <td class="lbl">Course Coordinator</td>
            <td class="val">{{ $infoVal($coordinator) }}</td>
            <td class="lbl">Joint Director</td>
            <td class="val">{{ $infoVal($jointDirector) }}</td>
        </tr>
        <tr>
            <td class="lbl">Assistant Coordinator</td>
            <td class="val" colspan="3">{{ $infoVal($assistantCoordinator) }}</td>
        </tr>
        @if(trim((string) $participantsProfile) !== '')
            <tr>
                <td class="lbl">Participants Profile</td>
                <td class="val" colspan="3">{{ $participantsProfile }}</td>
            </tr>
        @endif
    </table>

    @if(trim((string) $mentionOfWeek) !== '')
        <div class="sec">Mention of the Week</div>
        <table class="info">
            <tr><td class="val" colspan="4" style="white-space: pre-line;">{{ $mentionOfWeek }}</td></tr>
        </table>
    @endif

    {{-- Resource Persons / Faculty for the Week --}}
    <div class="sec">Resource Persons / Faculty for the Week</div>
    @if(empty($facultyRows))
        <table class="fac"><tr><td class="empty">No resource persons scheduled for this week.</td></tr></table>
    @else
        <table class="fac">
            <thead>
                <tr>
                    <th class="c-sno">S.No</th>
                    <th class="c-name">Name</th>
                    <th class="c-desg">Designation &amp; Organisation</th>
                    <th class="c-topic">Topic</th>
                    <th class="c-date">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($facultyRows as $i => $fr)
                    <tr>
                        <td class="c-sno">{{ $i + 1 }}</td>
                        <td class="c-name"><span class="fac-name">{{ $fr['name'] !== '' ? $fr['name'] : '—' }}</span></td>
                        <td class="c-desg">{{ $fr['designation'] !== '' ? $fr['designation'] : '—' }}</td>
                        <td class="c-topic">{{ $fr['topic'] }}</td>
                        <td class="c-date">{{ $fr['date']->format('d.m.Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Signatures --}}
    <table class="sign">
        <tr>
            <td>
                <span class="line">{{ trim((string) $coordinator) !== '' ? $coordinator : 'Course Coordinator' }}</span>
                @if(trim((string) $coordinator) !== '')<div style="font-size:8px;color:#666;">Course Coordinator</div>@endif
            </td>
            <td class="right">
                <span class="line">{{ trim((string) $jointDirector) !== '' ? $jointDirector : ($infoVal($director) !== '—' ? $director : 'Joint Director / Director') }}</span>
                @if(trim((string) $jointDirector) !== '' || trim((string) $director) !== '')<div style="font-size:8px;color:#666;">{{ trim((string) $jointDirector) !== '' ? 'Joint Director' : 'Director' }}</div>@endif
            </td>
        </tr>
    </table>

    <div class="page-footer">
        Lal Bahadur Shastri National Academy of Administration, Mussoorie &nbsp;&middot;&nbsp; Faculty for the Week &nbsp;&middot;&nbsp; Generated {{ Carbon::now()->format('d M Y, h:i A') }}
    </div>
</body>
</html>
