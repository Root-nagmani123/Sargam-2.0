{{--
    Weekly timetable, A4 landscape, print-ready.

    Renders one grid per week in $weeks, each starting a new page. Every row,
    column, group and cell comes from WeeklyTimetableBuilder, which derives them
    from that week's own events — nothing here assumes a day count, a time slot,
    a group, or a break.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $courseName ? $courseName . ' — ' : '' }}Time Table</title>
    <style>
        /* DejaVu is DomPDF's only bundled Unicode face — required for any
           non-ASCII that reaches a title, faculty name or note. */
        * { font-family: 'DejaVu Sans', sans-serif; box-sizing: border-box; }
        @page { size: A4 landscape; margin: 8mm 7mm 10mm 7mm; }
        body { margin: 0; color: #000; font-size: 8px; }

        /* ---------- Header ---------- */
        .tt-head { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .tt-head td { vertical-align: middle; }
        /* The constitution logo is wide and carries small type, so it is sized to
           stay legible in print rather than matched to the emblem's box. */
        .tt-head-logo { width: 92px; text-align: center; }
        .tt-head-logo img { max-height: 56px; max-width: 88px; }
        .tt-head-mid { text-align: center; padding: 0 6px; }
        .tt-head-hi { height: 13px; width: auto; margin-bottom: 1px; }
        .tt-head-en { font-size: 11.5px; font-weight: bold; color: #000; line-height: 1.3; }
        .tt-head-course { font-size: 9.5px; font-weight: bold; color: #000; margin-top: 2px; }
        .tt-head-dates { font-size: 9px; font-weight: bold; color: #000; margin-top: 1px; }
        .tt-head-week { font-size: 9px; font-weight: bold; color: #000; margin-top: 2px; }

        /* ---------- Grid ---------- */
        /* fixed layout: DomPDF's automatic table sizing is unreliable with rowspans. */
        .tt-grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .tt-grid th, .tt-grid td { border: 0.75px solid #000; vertical-align: middle; }
        .tt-grid thead th {
            background: #e2efd9; color: #000; text-align: center;
            font-size: 8px; font-weight: bold; line-height: 1.3; padding: 4px 2px;
        }
        .tt-th-date { display: block; font-weight: bold; font-size: 7.5px; margin-top: 1px; }

        .tt-time {
            text-align: center; font-size: 8px; font-weight: normal;
            color: #000; padding: 4px 1px; line-height: 1.5;
        }
        .tt-time-to { display: block; font-size: 7.5px; }

        .tt-group {
            text-align: center; font-size: 8.5px; font-weight: bold;
            color: #000; padding: 3px 1px;
        }

        .tt-cell { text-align: center; padding: 4px 3px; line-height: 1.3; }

        /* Merged break / full-width band. */
        .tt-band {
            text-align: center; font-weight: bold; font-size: 8.5px;
            color: #000; background: #e2efd9; padding: 4px 2px;
        }

        /* ---------- Event cell ---------- */
        .tt-ev { padding: 1px 0; }
        .tt-ev + .tt-ev { border-top: 0.5px dashed #999; margin-top: 3px; padding-top: 3px; }
        .tt-ev-title { font-size: 7.5px; color: #000; }
        /* Faculty is the emphasised line on the printed sheet. */
        .tt-ev-fac { font-size: 7.5px; font-weight: bold; color: #000; margin-top: 2px; }
        .tt-ev-ven { font-size: 7px; color: #000; margin-top: 1px; }
        .tt-ev-rem { font-size: 6.8px; color: #333; margin-top: 1px; }

        /* ---------- Footer rows (inside the grid table) ---------- */
        .tt-foot-venue { text-align: left; font-size: 7.5px; color: #000; padding: 3px 4px; }
        .tt-foot-notes { padding: 3px 4px; }
        .tt-notes { width: 100%; border-collapse: collapse; }
        /* Same font metrics on every cell in the row, or the numbers ride up
           away from the text they belong to. */
        .tt-notes td {
            border: none; vertical-align: top; padding: 0.6px 0;
            font-size: 7px; line-height: 1.4;
        }
        .tt-notes-label { width: 26px; font-weight: bold; }
        .tt-notes-num { width: 13px; font-style: italic; }
        .tt-notes-text { font-style: italic; color: #000; }

        .tt-empty { text-align: center; padding: 40px; color: #555; font-size: 11px; }
        .tt-pto { text-align: right; font-size: 8.5px; font-weight: bold; margin-top: 3px; letter-spacing: .5px; }

        /* ---------- Info sheet (page 2) ---------- */
        .is-cols { width: 100%; border-collapse: collapse; }
        .is-col { width: 50%; vertical-align: top; padding: 0 3px; }
        .is-box { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .is-box th, .is-box td { border: 0.75px solid #000; padding: 2px 4px; }
        .is-box th {
            background: #e2efd9; text-align: center; font-size: 7.5px; font-weight: bold;
        }
        .is-c { text-align: center; font-size: 7px; }
        .is-l { text-align: left; font-size: 7px; }
        .is-abbr { text-align: left; font-size: 7px; font-weight: bold; white-space: nowrap; width: 78px; }
        .is-free { font-size: 7px; line-height: 1.45; padding: 3px 5px; }

        .is-guests { padding: 3px 4px; }
        .is-guest-list { width: 100%; border-collapse: collapse; }
        .is-guest-list td { border: none; vertical-align: top; padding: 1px 0; font-size: 6.8px; line-height: 1.4; }
        .is-guest-num { width: 14px; font-weight: bold; }
        .is-guest-name { font-weight: bold; }
        .is-guest-mod { display: block; }

        .is-sign { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .is-sign td { border: none; font-size: 7.5px; vertical-align: bottom; }
        .is-sign-date { text-align: left; font-weight: bold; }
        .is-sign-who { text-align: right; }
        .is-sign-name { font-weight: bold; }
    </style>
</head>
<body>

    @forelse($weeks as $week)
        <div @if(!$loop->first) style="page-break-before: always;" @endif>
            <x-timetable.header
                :title-hindi="$titleHindi"
                :logo-left="$logoLeft"
                :logo-right="$logoRight"
                :institute-name="$instituteName"
                :course-name="$courseName"
                :course-duration="$courseDuration"
                :week-number="$week['weekNumber']"
                :range-label="$week['rangeLabel']" />

            <x-timetable.grid
                :days="$week['days']"
                :groups="$week['groups']"
                :rows="$week['rows']"
                :has-group-axis="$week['hasGroupAxis']"
                :venue-line="$week['venueLine']"
                :notes="$week['notes']" />

            @if(!empty($week['sheet']))
                <div class="tt-pto">P.T.O.</div>
            @endif
        </div>

        {{-- The info sheet is the back of this week's timetable — the page the
             P.T.O. points at — so it follows its own week, not the whole run. --}}
        @if(!empty($week['sheet']))
            <div style="page-break-before: always;">
                <x-timetable.info-sheet :sheet="$week['sheet']" />
            </div>
        @endif
    @empty
        <x-timetable.header
            :title-hindi="$titleHindi"
            :logo-left="$logoLeft"
            :logo-right="$logoRight"
            :institute-name="$instituteName"
            :course-name="$courseName"
            :course-duration="$courseDuration" />

        <div class="tt-empty">No sessions scheduled for this period.</div>
    @endforelse

    {{--
        Page numbers. Must come last: the inline PHP runs at the point the renderer
        reaches it, so at the top of the body only page 1 exists and {PAGE_COUNT}
        resolves to 1 on a multi-week export. Requires isPhpEnabled.
    --}}
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $size = 7;
            $w = $fontMetrics->getTextWidth($text, $font, $size);
            $pdf->page_text($pdf->get_width() - $w - 22, $pdf->get_height() - 18, $text, $font, $size, array(0.45, 0.45, 0.45));
        }
    </script>
</body>
</html>
