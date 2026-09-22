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
        .hdr .logo-l img { height: 74.25pt; width: auto; }
        .hdr .logo-r img { width: 94pt; height: auto; }
        .hdr .mid { text-align: center; padding: 0 4pt; }

        /* The issued sheet prints the Devanagari title 309px wide; this artwork
           has a slightly different aspect, so the width is matched and the height
           lands 2px short of its 21px. */
        .inst-hi-img { height: 16.6pt; width: auto; }
        .inst-en     { font-weight: bold; margin-top: 6pt; }
        .course-line { font-weight: bold; margin-top: 6pt; }
        .course-dates{ font-weight: bold; margin-top: 6pt; }
        .week-line   { font-weight: bold; margin-top: 6pt; }

        /* ===== Grid ===== */
        table.grid {
            width: 100%; border-collapse: collapse; table-layout: fixed;
            margin-top: 3pt;
        }
        table.grid th, table.grid td {
            border: 0.5pt solid #000; vertical-align: middle; text-align: center;
        }

        /* The issued sheet prints "GROUP" 47px wide in a 56px column; at the
           11pt body size it comes out 52px, overflowing a cell DomPDF does not
           clip. 10pt reproduces the 47px exactly. */
        table.grid thead th.grp-h { font-size: 10pt; }

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
        /* break-word keeps a long single-token name - "(AAKANKSHA
           KULSHRESTHA)" in a narrow column - inside its own cell. Without it
           DomPDF paints the overflow straight over the neighbouring day. */
        td.day  { padding: 4pt 3pt; word-wrap: break-word; }

        /* One session inside a day cell. The sheet sets the topic plain, the
           session taker bold in brackets, and the coordinator's initials
           plain beneath.

           The two gaps are measured off the issued sheet, where they are whole
           blank lines: on its 17px line the taker sits 52px below the topic and
           the initials 34px below the taker - two blank lines and one, against
           this 12.76pt line. */
        .cell + .cell { margin-top: 9pt; }
        .cell .fac  { font-weight: bold; margin-top: 26.25pt; }
        .cell .ini  { margin-top: 12.75pt; }

        /* Which groups the session is for, where the GROUP column does not
           already say so. Set inline after the topic rather than on a line of
           its own: nearly every session names a group, and a line each would
           add an inch to a full sheet and push it onto a second page - where
           DomPDF cannot carry a rowspan over and the columns come apart. */
        .cell .grp-line { font-size: 8pt; font-style: italic; }

        td.day.dense                { font-size: 9pt; }
        td.day.dense .cell + .cell  { margin-top: 4pt; }
        td.day.dense .cell .fac     { margin-top: 0; }
        td.day.dense .cell .ini     { margin-top: 0; }
        td.day.dense .cell .grp-line{ font-size: 7pt; }

        /* DomPDF cannot break a cell across pages, so a band running a dozen
           parallel classes at one hour has to be squeezed onto the page it
           starts on or its closing rule falls off the sheet. */
        td.day.denser               { font-size: 7.5pt; }
        td.day.denser .cell + .cell { margin-top: 2.5pt; }

        /* The two rows that close the issued sheet: both sit inside the
           grid, left-aligned and smaller than a session, and the notes keep
           their numbering on separate lines. */
        table.grid td.venues    { text-align: left; padding: 2pt 4pt; font-size: 9pt; font-weight: bold; }
        table.grid td.note-cell { text-align: left; padding: 2pt 4pt; font-size: 9pt; font-weight: bold; }
        /* Hanging indents, so a note that wraps keeps its text clear of the
           numbering - the first line hangs by the width of "Note: 1. ". */
        table.grid td.note-cell .note-line      { padding-left: 14pt; text-indent: -14pt; }
        table.grid td.note-cell .note-line.lead { padding-left: 40pt; text-indent: -40pt; }

        .empty { text-align: center; padding: 28pt; }
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
            // Measured off the issued sheet: on its 770px table TIME is 62px
            // (8.05%) and GROUP 56px (7.27%). The lead is fixed, so a week
            // carrying the weekend narrows the day columns and leaves TIME able
            // to hold "0940 to 1040". The issued sheet lets its five day columns
            // vary (123-138px, auto-fitted); they are set equal here.
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
                        @if($week['showGroupCol'])<th class="grp-h" style="width: 7.3%;">GROUP</th>@endif
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
                                                <div>{{ $ev['topic'] }}@if(!empty($ev['groupNames']))<span class="grp-line"> [{{ $ev['groupNames'] }}]</span>@endif</div>
                                                @if($multiCourse && !empty($ev['course']))
                                                    <div>{{ $ev['course'] }}</div>
                                                @endif
                                                @if(!empty($ev['faculty']))
                                                    {{-- One pair of brackets per session taker. --}}
                                                    <div class="fac">
                                                        @foreach((array) $ev['faculty'] as $facName)
                                                            <div>({{ $facName }})</div>
                                                        @endforeach
                                                    </div>
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

                    {{-- The issued sheet closes the grid with these two rows,
                         inside its border and full width: where each group sits,
                         then the week's notes. --}}
                    @php $cols = 1 + ($week['showGroupCol'] ? 1 : 0) + count($week['days']); @endphp
                    @if(!empty($week['venueLine']))
                        <tr><td class="venues" colspan="{{ $cols }}"><b>VENUES:</b> {{ $week['venueLine'] }}</td></tr>
                    @endif
                    @if(!empty($footerNote))
                        <tr>
                            <td class="note-cell" colspan="{{ $cols }}">
                                @php
                                    // One <div> per note, so a numbered list keeps
                                    // its hanging indent instead of running on.
                                    $notes = preg_split('/\r\n|\r|\n/', trim($footerNote));
                                    $notes = array_values(array_filter(array_map('trim', $notes), static fn ($n) => $n !== ''));
                                @endphp
                                @foreach($notes as $i => $noteLine)
                                    <div class="note-line @if($i === 0)lead @endif">@if($i === 0)Note: @endif{{ $noteLine }}</div>
                                @endforeach
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
            @if(!$loop->last)
                <div class="pto">P.T.O.</div>
            @endif
        </div>
    @endforeach
@endif
</body>
</html>
