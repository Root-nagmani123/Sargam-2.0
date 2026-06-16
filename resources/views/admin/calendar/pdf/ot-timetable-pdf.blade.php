<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Time Table</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        @page { margin: 16px 14px; }
        body { margin: 0; color: #1f2937; font-size: 8px; }

        /* ===== Header ===== */
        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .hdr td { vertical-align: middle; }
        .hdr .logo { width: 62px; text-align: center; }
        .hdr .logo img { height: 46px; }
        .hdr .center { text-align: center; padding: 0 6px; }
        .inst-en { font-size: 12px; font-weight: bold; color: #102a43; line-height: 1.25; }
        .course-line { font-size: 9px; font-weight: bold; color: #243b53; margin-top: 3px; }
        .course-dates { font-size: 8.5px; color: #486581; margin-top: 1px; }

        .ttl-row { width: 100%; border-collapse: collapse; margin: 6px 0 5px; }
        .ttl-row td { font-size: 10px; color: #102a43; }
        .ttl-row .left { text-align: left; font-weight: bold; }
        .ttl-row .ttl { text-align: center; font-size: 14px; font-weight: bold; font-style: italic; }
        .ttl-row .right { text-align: right; font-weight: bold; }

        /* ===== Grid ===== */
        table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.grid th, table.grid td { border: 1px solid #243b53; vertical-align: top; }
        table.grid thead th {
            background: #1c3a5e; color: #ffffff; text-align: center;
            font-size: 7.5px; font-weight: bold; line-height: 1.3; padding: 4px 2px;
        }
        table.grid thead th .dt { font-weight: normal; font-size: 7px; }
        table.grid .time-col { width: 48px; }
        table.grid td.time {
            background: #1c3a5e; color: #ffffff; text-align: center;
            font-weight: bold; font-size: 7.5px; vertical-align: middle; padding: 4px 2px;
        }
        table.grid td.day { padding: 4px 3px; font-size: 7px; line-height: 1.3; }

        .cell { margin-bottom: 4px; }
        .cell:last-child { margin-bottom: 0; }
        .cell-topic { font-weight: bold; color: #102a43; }
        .cell-fac { color: #243b53; }
        .cell-ven { color: #627d98; }

        .empty { text-align: center; padding: 28px; color: #6b7280; font-size: 11px; }
        .note { margin-top: 6px; font-size: 7.5px; color: #334155; }
        .pto { text-align: center; font-size: 9px; font-weight: bold; margin-top: 6px; letter-spacing: .5px; }
    </style>
</head>
<body>
@if(count($weeks) === 0)
    <div class="empty">No sessions scheduled for this period.</div>
@else
    @foreach($weeks as $week)
        <div class="week-section" @if(!$loop->first) style="page-break-before: always;" @endif>

            {{-- Institution header --}}
            <table class="hdr">
                <tr>
                    <td class="logo">@if(is_file($logoLeft))<img src="{{ $logoLeft }}" alt="">@endif</td>
                    <td class="center">
                        <div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>
                        @if($course && $course->course_name)
                            <div class="course-line">{{ $course->course_name }}</div>
                        @endif
                        @if($courseDuration)
                            <div class="course-dates">({{ $courseDuration }})</div>
                        @endif
                    </td>
                    <td class="logo">@if(is_file($logoRight))<img src="{{ $logoRight }}" alt="">@endif</td>
                </tr>
            </table>

            {{-- Venue | Time table | Week --}}
            <table class="ttl-row">
                <tr>
                    <td class="left" style="width: 33%;">@if($primaryVenue)Venue: {{ $primaryVenue }}@endif</td>
                    <td class="ttl" style="width: 34%;">Time table</td>
                    <td class="right" style="width: 33%;">Week: {{ str_pad((string) $week['weekNumber'], 2, '0', STR_PAD_LEFT) }}</td>
                </tr>
            </table>

            {{-- Weekly grid --}}
            <table class="grid">
                <thead>
                    <tr>
                        <th class="time-col">Time</th>
                        @foreach($week['days'] as $day)
                            <th>{{ $day['dayName'] }}<br><span class="dt">{{ $day['label'] }}</span></th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($week['slots'] as $slot)
                        <tr>
                            <td class="time">{{ $slot['label'] }}</td>
                            @foreach($week['days'] as $day)
                                <td class="day">
                                    @foreach($slot['cells'][$day['key']] as $cell)
                                        <div class="cell">
                                            <div class="cell-topic">{{ $cell['topic'] }}</div>
                                            @if(!empty($cell['faculty']))
                                                <div class="cell-fac">[{{ $cell['faculty'] }}]</div>
                                            @endif
                                            @if(!empty($cell['venue']))
                                                <div class="cell-ven">({{ $cell['venue'] }})</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="note">
                Generated on {{ now()->format('d M Y, h:i A') }}@if($studentName) &middot; {{ $studentName }}@endif
            </div>
            <div class="pto">P.T.O.</div>
        </div>
    @endforeach
@endif
</body>
</html>
