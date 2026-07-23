@php
    $reportTitle    = $reportTitle ?? 'Student List';
    $logoLeft       = $logoLeft ?? null;
    $logoRight      = $logoRight ?? null;
    $titleHindi     = $titleHindi ?? null;
    $courseName     = $courseName ?? '';
    $courseDuration = $courseDuration ?? '';
    $filterSummary  = $filterSummary ?? '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reportTitle }} - LBSNAA MUSSOORIE</title>
    <style>
        @page { size: A4 landscape; margin: 12mm 10mm; }
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        html, body { margin: 0; padding: 0; color: #1f2937; background: #e9edf2; }

        /* On screen the report sits on a centred "paper" sheet; print resets it. */
        .print-sheet {
            width: 277mm;
            max-width: 100%;
            margin: 18px auto;
            padding: 16mm 12mm;
            background: #fff;
            box-shadow: 0 6px 24px rgba(16, 24, 40, 0.18);
        }

        /* ===== Institution header (matches the official LBSNAA report layout) ===== */
        table.pdf-hdr { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.pdf-hdr td { vertical-align: middle; }
        table.pdf-hdr .logo { width: 90px; text-align: center; }
        table.pdf-hdr .logo img { max-height: 60px; max-width: 84px; }
        table.pdf-hdr .center { text-align: center; padding: 0 8px; }
        .inst-hi-img { height: 20px; width: auto; margin-bottom: 3px; }
        .inst-en { font-size: 17px; font-weight: bold; color: #102a43; line-height: 1.25; }
        .course-line { font-size: 12px; font-weight: bold; color: #243b53; margin-top: 4px; }
        .course-dates { font-size: 11px; color: #486581; margin-top: 1px; }

        .report-title-block {
            text-align: center;
            margin: 8px 0 10px;
        }
        .report-title {
            font-size: 17px;
            font-weight: 700;
            color: #004a93;
            margin: 0 0 5px;
            padding-bottom: 6px;
            border-bottom: 2px solid #004a93;
        }
        .report-meta { font-size: 11px; color: #555; }

        table.data-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.data-table thead th {
            background: #004a93;
            color: #fff;
            border: 1px solid #003a75;
            padding: 5px 4px;
            text-align: left;
            font-weight: bold;
        }
        table.data-table tbody td {
            border: 1px solid #e5e7eb;
            padding: 4px;
            vertical-align: top;
        }
        table.data-table tbody tr:nth-child(even) td { background: #fafafa; }
        .empty { text-align: center; padding: 18px; color: #6b7280; }

        /* Floating toolbar — never printed. */
        .print-toolbar {
            position: fixed;
            top: 14px;
            right: 18px;
            display: flex;
            gap: 8px;
            z-index: 10;
        }
        .print-toolbar button {
            font: 600 13px/1 Arial, sans-serif;
            padding: 9px 16px;
            border-radius: 8px;
            border: 1px solid #004a93;
            cursor: pointer;
        }
        .print-toolbar .btn-print { background: #004a93; color: #fff; }
        .print-toolbar .btn-close { background: #fff; color: #004a93; }

        @media print {
            html, body { background: #fff; }
            .print-sheet { width: auto; margin: 0; padding: 0; box-shadow: none; }
            .print-toolbar { display: none !important; }
            table.data-table thead { display: table-header-group; }
            table.data-table tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" class="btn-print" onclick="window.print()">Print</button>
        <button type="button" class="btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="print-sheet">
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

        <div class="report-title-block">
            <h1 class="report-title">{{ $reportTitle }}</h1>
            <div class="report-meta">
                @if($filterSummary)Filters: {{ $filterSummary }} &nbsp;|&nbsp; @endif
                Generated on: {{ $generatedAt }}
                &nbsp;|&nbsp; Total records: {{ count($rows) }}
            </div>
        </div>

        <table class="data-table">
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
    </div>

    <script>
        // Open the print dialog automatically once the report has rendered.
        window.addEventListener('load', function () {
            window.focus();
            window.setTimeout(function () { window.print(); }, 250);
        });
    </script>
</body>
</html>
