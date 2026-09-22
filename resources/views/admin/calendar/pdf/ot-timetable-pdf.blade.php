{{--
    Printed weekly timetable.

    Laid out to match the academy's issued sheet: Legal portrait, black rules on
    white, pale-olive header and break bands, and a TIME x GROUP axis down the
    left. Geometry and colours were measured off the issued PDF - see the
    comments on the individual rules before changing them.

    Shared by CalendarController::downloadTimetablePdf(), otDownloadPdf() and
    weeklyTimetablePdf(); all three feed it buildWeeksGrid() output.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Time Table</title>
    <style>
        /* Legal portrait, 18pt side margins: the issued sheet's table is
           770px wide on an 816px page at 96dpi. */
        @page { margin: 27pt 18pt 20pt 18pt; }

        /* Helvetica matches Arial's metrics, which the issued sheet is set in.
           DejaVu is far wider and would reflow every cell. The Devanagari
           title is a pre-shaped image, so no Indic-capable font is needed. */
        * { font-family: Helvetica, Arial, sans-serif; }

        body { margin: 0; color: #000; font-size: 11pt; line-height: 1.16; }

        /* ===== Academy header ===== */
        .hdr { width: 100%; border-collapse: collapse; }
        .hdr td { vertical-align: middle; padding: 0; }
        .hdr .logo-l { width: 110px; text-align: left; }
        .hdr .logo-r { width: 110px; text-align: right; }
        .hdr .logo-l img { height: 76pt; width: auto; }
        .hdr .logo-r img { width: 94pt; height: auto; }
        .hdr .mid { text-align: center; padding: 0 4pt; }

        .inst-hi-img { height: 17pt; width: auto; }
        .inst-en     { font-weight: bold; margin-top: 6pt; }
        .course-line { font-weight: bold; margin-top: 6pt; }
        .course-dates{ font-weight: bold; margin-top: 6pt; }
        .week-line   { margin-top: 3pt; }

        /* ===== Grid ===== */
        table.grid {
            width: 100%; border-collapse: collapse; table-layout: fixed;
            margin-top: 3pt;
        }
        table.grid th, table.grid td {
            border: 0.5pt solid #000; vertical-align: middle; text-align: center;
        }

        /* Header and break bands share the one accent the sheet uses. */
        table.grid thead th {
            background: #EAF1DD; font-weight: bold; padding: 2pt 2pt;
        }
        td.band {
            background: #EAF1DD; font-weight: bold; padding: 9pt 3pt;
        }

        td.time { padding: 3pt 1pt; }
        td.grp  { padding: 3pt 1pt; word-wrap: break-word; }
        td.grp.long { font-size: 7pt; line-height: 1.1; }
        td.day  { padding: 4pt 3pt; }

        /* One session inside a day cell. The sheet sets the topic plain, the
           session taker bold in brackets, and the coordinator's initials
           plain beneath. */
        .cell + .cell { margin-top: 9pt; }
        .cell .fac  { font-weight: bold; margin-top: 11pt; }
        .cell .ini  { margin-top: 7pt; }

        td.day.dense                { font-size: 9pt; }
        td.day.dense .cell + .cell  { margin-top: 4pt; }
        td.day.dense .cell .fac     { margin-top: 0; }
        td.day.dense .cell .ini     { margin-top: 0; }

        /* DomPDF cannot break a cell across pages, so a band running a dozen
           parallel classes at one hour has to be squeezed onto the page it
           starts on or its closing rule falls off the sheet. */
        td.day.denser               { font-size: 7.5pt; }
        td.day.denser .cell + .cell { margin-top: 2.5pt; }

        .empty { text-align: center; padding: 28pt; }
        .note  { margin-top: 5pt; }
        .pto   { text-align: right; font-weight: bold; margin-top: 4pt; }
    </style>
</head>
<body>

@if(count($weeks) === 0)
    <div class="empty">No sessions scheduled for this period.</div>
@else
    @foreach($weeks as $week)
        @php
            $dayCount = max(1, count($week['days']));
            $lead     = $week['showGroupCol'] ? 15.4 : 8.1;   // TIME (+ GROUP) %
            $dayWidth = round((100 - $lead) / $dayCount, 3);
        @endphp
        <div @if(!$loop->first) style="page-break-before: always;" @endif>

            <table class="hdr">
                <tr>
                    <td class="logo-l">@if($logoLeft)<img src="{{ $logoLeft }}" alt="">@endif</td>
                    <td class="mid">
                        @if($titleHindi)<img class="inst-hi-img" src="{{ $titleHindi }}" alt="">@endif
                        <div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>
                        @if($course && $course->course_name)
                            <div class="course-line">
                                {{ $course->course_name }}@if(!empty($course->couse_short_name) && $course->couse_short_name !== $course->course_name) ({{ $course->couse_short_name }})@endif
                            </div>
                        @endif
                        @if($courseDuration)
                            <div class="course-dates">({{ $courseDuration }})</div>
                        @endif
                        <div class="week-line">Time Table: Week-{{ str_pad((string) $week['weekNumber'], 2, '0', STR_PAD_LEFT) }}</div>
                    </td>
                    <td class="logo-r">@if($logoRight)<img src="{{ $logoRight }}" alt="">@endif</td>
                </tr>
            </table>

            <table class="grid">
                {{-- Widths ride on the header cells: DomPDF ignores
                     <colgroup> widths but honours these. --}}
                <thead>
                    <tr>
                        <th style="width: 8.1%;">TIME</th>
                        @if($week['showGroupCol'])<th style="width: 7.3%;">GROUP</th>@endif
                        @foreach($week['days'] as $day)
                            <th style="width: {{ $dayWidth }}%;">{{ $day['dayName'] }}<br>{{ $day['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($week['rows'] as $row)
                        <tr>
                            @if($row['type'] === 'band')
                                {{-- A break band covers TIME, GROUP and every day column
                                     still open at this row; days held by a rowspan from
                                     above keep their own cell, so the band stops short. --}}
                                @foreach($row['segments'] as $seg)
                                    <td class="band" colspan="{{ $seg['colspan'] }}">{{ $seg['label'] }}</td>
                                @endforeach
                            @else
                                @if($row['showTime'])
                                    <td class="time" rowspan="{{ $row['timeRowspan'] }}" colspan="{{ $row['timeColspan'] }}">
                                        @if($row['to'] !== '')
                                            {{ $row['from'] }}<br>to<br>{{ $row['to'] }}
                                        @else
                                            {{ $row['from'] }}
                                        @endif
                                    </td>
                                @endif

                                @if($week['showGroupCol'] && $row['timeColspan'] === 1)
                                    <td class="grp @if(mb_strlen($row['groupLabel']) > 3) long @endif">{{ $row['groupLabel'] }}</td>
                                @endif

                                @foreach($week['days'] as $day)
                                    @php $c = $row['cells'][$day['key']]; @endphp
                                    @continue($c['state'] === 'skip')
                                    @php $n = count($c['events']); @endphp
                                    <td class="day @if($n > 8) dense denser @elseif($n > 2) dense @endif" rowspan="{{ $c['rowspan'] }}">
                                        @foreach($c['events'] as $ev)
                                            <div class="cell">
                                                <div>{{ $ev['topic'] }}</div>
                                                @if($multiCourse && !empty($ev['course']))
                                                    <div>{{ $ev['course'] }}</div>
                                                @endif
                                                @if(!empty($ev['faculty']))
                                                    <div class="fac">({{ $ev['faculty'] }})</div>
                                                @endif
                                                @if(!empty($ev['initials']))
                                                    <div class="ini">({{ $ev['initials'] }})</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </td>
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if(!empty($footerNote))
                <div class="note"><b>Note:</b> {{ $footerNote }}</div>
            @endif
            @if(!$loop->last)
                <div class="pto">P.T.O.</div>
            @endif
        </div>
    @endforeach
@endif
</body>
</html>
