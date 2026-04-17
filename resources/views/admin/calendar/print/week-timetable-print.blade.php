<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Revised Time Table Week {{ (int) ($sheetWeekNumber ?? $weekNum) }}</title>
    <style>
        @page { margin: 10mm; size: A4 landscape; }
        * { box-sizing: border-box; }
        body {
            font-family: {{ e($pdfBodyFont ?? config('week_timetable.pdf_body_font')) }};
            font-size: 8pt;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }

        .sheet-title-block {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #000;
        }
        .sheet-hi { font-size: 9pt; margin-bottom: 3px; }
        .sheet-en { font-size: 9pt; font-weight: 700; margin-bottom: 4px; }
        .sheet-programme { font-size: 9pt; font-weight: 700; margin-top: 2px; }
        .sheet-period { font-size: 8.5pt; margin-top: 2px; }
        .sheet-weekline {
            font-size: 9.5pt;
            font-weight: 700;
            margin-top: 6px;
            letter-spacing: 0.02em;
        }
        .sheet-revised-tab { font-weight: 700; }

        table.grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.grid th,
        table.grid td {
            border: 1px solid #000;
            vertical-align: top;
            padding: 2px 3px;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        table.grid thead th {
            background: #fff;
            color: #000;
            font-weight: 700;
            text-align: center;
            font-size: 7.5pt;
        }
        table.grid thead tr.day-names th { border-bottom: 1px solid #000; }
        table.grid thead tr.date-row th {
            font-weight: 700;
            font-size: 8pt;
            padding-top: 1px;
        }
        table.grid .time-col {
            width: 9%;
            min-width: 52px;
            background: #fff;
            font-weight: 700;
            text-align: center;
            font-size: 7pt;
            white-space: pre-line;
            color: #000;
        }
        table.grid tbody tr.break-notes td,
        table.grid tbody tr.break-notes th {
            font-size: 6.5pt;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
            padding: 2px 2px;
        }
        table.grid tbody tr.venue-summary-row td,
        table.grid tbody tr.venue-summary-row th {
            font-size: 6.5pt;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
            padding: 3px 4px;
        }

        .tt-wrap { width: 100%; }
        table.cell-stack { border-collapse: collapse; margin: 0 0 2px 0; width: 100%; }
        table.cell-stack:last-child { margin-bottom: 0; }
        td.cell-lbl {
            width: 14px;
            min-width: 12px;
            background: #f0f0f0;
            color: #000;
            font-weight: 700;
            text-align: center;
            vertical-align: top;
            padding: 1px 2px;
            font-size: 8pt;
            border-right: 1px solid #999;
        }
        td.cell-body { vertical-align: top; padding: 0 0 0 3px; }
        td.cell-body-full { padding-left: 2px; }

        .cardx { border: 0; padding: 0 0 2px 0; margin-bottom: 0; background: #fff; }
        .gb {
            font-size: 6.5pt;
            font-weight: 700;
            background: #333;
            color: #fff;
            padding: 1px 3px;
            margin: 0 0 2px 0;
        }
        .ttl { font-weight: 700; font-size: 7.5pt; margin-bottom: 1px; }
        .ln { font-size: 6.5pt; line-height: 1.2; }

        @media print {
            .screen-only { display: none !important; }
        }

        .sheet-footnotes {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #000;
            font-size: 6.5pt;
            line-height: 1.35;
            text-align: left;
        }
        .sheet-footnotes p { margin: 0 0 4px 0; }
    </style>
</head>
<body>
    <p class="screen-only" style="font-size:8pt;margin:6px 0;">
        Use your browser print dialog (Ctrl+P) to print or save as PDF.
    </p>

    @include('admin.calendar.partials.week-timetable-letterhead', [
        'weekNum' => $weekNum,
        'sheetWeekNumber' => $sheetWeekNumber ?? $weekNum,
        'courseTitle' => $courseTitle,
        'courseProgrammeTitle' => $courseProgrammeTitle ?? $courseTitle,
        'coursePeriodParen' => $coursePeriodParen ?? null,
    ])

    @php
        $bn = $breakNotices ?? ['Mon' => '', 'Tue' => '', 'Wed' => '', 'Thu' => '', 'Fri' => ''];
        $showBreakRow = collect($bn)->contains(fn ($v) => trim((string) $v) !== '');
        $venueLine = trim((string) ($venueSummaryLine ?? ''));
        $footnotes = $footnotes ?? [];
    @endphp

    <table class="grid" role="table">
        <thead>
            <tr class="day-names">
                <th scope="col" class="time-col" rowspan="2">TIME</th>
                @foreach ($headerDates as $h)
                    <th scope="col">{{ e($h['weekday']) }}</th>
                @endforeach
            </tr>
            <tr class="date-row">
                @foreach ($headerDates as $h)
                    <th scope="col">{{ e($h['dmy']) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if($showBreakRow)
                <tr class="break-notes">
                    <th scope="row" class="time-col"></th>
                    <td>{!! nl2br(e($bn['Mon'])) !!}</td>
                    <td>{!! nl2br(e($bn['Tue'])) !!}</td>
                    <td>{!! nl2br(e($bn['Wed'])) !!}</td>
                    <td>{!! nl2br(e($bn['Thu'])) !!}</td>
                    <td>{!! nl2br(e($bn['Fri'])) !!}</td>
                </tr>
            @endif
            @if($venueLine !== '')
                <tr class="venue-summary-row">
                    <th scope="row" class="time-col"></th>
                    <td colspan="5">{{ e($venueLine) }}</td>
                </tr>
            @endif
            @forelse ($gridRows as $row)
                <tr>
                    <th scope="row" class="time-col">{!! nl2br(e((string) $row['time'])) !!}</th>
                    <td>{!! $row['Mon'] !!}</td>
                    <td>{!! $row['Tue'] !!}</td>
                    <td>{!! $row['Wed'] !!}</td>
                    <td>{!! $row['Thu'] !!}</td>
                    <td>{!! $row['Fri'] !!}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:12px;">No sessions in this week for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($footnotes))
        <div class="sheet-footnotes">
            @foreach ($footnotes as $line)
                <p>{{ e($line) }}</p>
            @endforeach
        </div>
    @endif
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 200);
        });
    </script>
</body>
</html>
