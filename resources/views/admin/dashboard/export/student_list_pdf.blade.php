@php
    $reportTitle    = $reportTitle ?? 'Student List';
    $logoLeft       = $logoLeft ?? null;
    $logoRight      = $logoRight ?? null;
    $titleHindi     = $titleHindi ?? null;
    $courseName     = $courseName ?? '';
    $courseDuration = $courseDuration ?? '';
    $filterSummary  = $filterSummary ?? '';

    // Optional per-column weights. When supplied the table switches to a FIXED
    // layout with explicit widths, which is what stops a wide report (the OT /
    // Participants List has 15 columns) from pushing its last columns off the
    // right edge of the page — DomPDF will not shrink an auto-layout table below
    // its content's natural width, so it simply overflows and clips.
    $columnWidths = $columnWidths ?? null;
    if (is_array($columnWidths) && count($columnWidths) === count($headings ?? []) && array_sum($columnWidths) > 0) {
        $weightTotal = array_sum($columnWidths);
        $columnPercents = array_map(fn ($w) => round(($w / $weightTotal) * 100, 4), $columnWidths);
    } else {
        $columnPercents = null;
    }

    // Wide reports need smaller type to stay readable inside the fixed widths.
    // Only reports that supply widths are re-styled — every existing caller keeps
    // the original 8pt / 4px look.
    $columnCount = count($headings ?? []);
    $tableFont = ($columnPercents && $columnCount >= 13) ? '6.2pt' : '8pt';
    $cellPad   = ($columnPercents && $columnCount >= 13) ? '2px 3px' : '4px';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} — LBSNAA</title>
    <style>
        @page { margin: 12mm 10mm; }
        * { box-sizing: border-box; font-family: DejaVu Sans, Arial, Helvetica, sans-serif; }
        body { margin: 0; color: #1f2937; background: #fff; }

        /* ===== Institution header (matches the official LBSNAA report layout) ===== */
        table.pdf-hdr { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        table.pdf-hdr td { vertical-align: middle; }
        table.pdf-hdr .logo { width: 78px; text-align: center; }
        table.pdf-hdr .logo img { max-height: 52px; max-width: 74px; }
        table.pdf-hdr .center { text-align: center; padding: 0 6px; }
        /* Devanagari title is a pre-shaped image — DomPDF can't shape Indic text. */
        .inst-hi-img { height: 16px; width: auto; margin-bottom: 2px; }
        .inst-en { font-size: 13px; font-weight: bold; color: #102a43; line-height: 1.25; }
        .course-line { font-size: 10px; font-weight: bold; color: #243b53; margin-top: 3px; }
        .course-dates { font-size: 9px; color: #486581; margin-top: 1px; }

        .report-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            color: #004a93;
            margin: 6px 0 4px;
            padding-bottom: 5px;
            border-bottom: 2px solid #004a93;
        }

        .meta { font-size: 8pt; color: #444; margin: 0 0 8px; text-align: center; }

        table.data-table { width: 100%; border-collapse: collapse; font-size: {{ $tableFont }}; }
        @if($columnPercents)
        /* Fixed layout keeps every column inside the page width. */
        table.data-table { table-layout: fixed; }
        @endif
        table.data-table thead th {
            background: #004a93;
            color: #fff;
            border: 1px solid #003a75;
            padding: {{ $cellPad }};
            text-align: left;
            font-weight: bold;
            word-wrap: break-word;
        }
        table.data-table tbody td {
            border: 1px solid #e5e7eb;
            padding: {{ $cellPad }};
            vertical-align: top;
            /* Long emails and joined names must wrap, never widen the column. */
            word-wrap: break-word;
        }
        table.data-table tbody tr:nth-child(even) td { background: #fafafa; }
        .empty { text-align: center; padding: 18px; color: #6b7280; }
    </style>
</head>
<body>

    {{-- Institution header --}}
    <table class="pdf-hdr">
        <tr>
            <td class="logo">@if($logoLeft)<img src="{{ $logoLeft }}" alt="">@endif</td>
            <td class="center">
                @if($titleHindi)<img class="inst-hi-img" src="{{ $titleHindi }}" alt="">@endif
                <div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>
                @if($courseName)
                    <div class="course-line">{{ $courseName }}</div>
                @endif
                @if($courseDuration)
                    <div class="course-dates">({{ $courseDuration }})</div>
                @endif
            </td>
            <td class="logo">@if($logoRight)<img src="{{ $logoRight }}" alt="">@endif</td>
        </tr>
    </table>

    <div class="report-title">{{ $reportTitle }}</div>

    <div class="meta">
        @if($filterSummary)<div>Filters: {{ $filterSummary }}</div>@endif
        <div>Generated on: {{ $generatedAt }} &nbsp;|&nbsp; Total records: {{ count($rows) }}</div>
    </div>

    <table class="data-table">
        @if($columnPercents)
        <colgroup>
            @foreach($columnPercents as $pct)
            <col style="width: {{ $pct }}%">
            @endforeach
        </colgroup>
        @endif
        <thead>
            <tr>
                @foreach($headings as $heading)
                <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                @foreach($row as $cell)
                <td>{{ $cell }}</td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td class="empty" colspan="{{ max(count($headings), 1) }}">No records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
