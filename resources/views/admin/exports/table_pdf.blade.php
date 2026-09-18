@php
    /**
     * The Academy's standard PDF layout for a simple heading/row table.
     *
     * Rendered from the same $headings/$rows arrays as LbsnaaTableExport, so a
     * listing's PDF and its Excel sheet cannot disagree.
     */
    $headings = $headings ?? [];
    $rows = $rows ?? collect();
    $reportTitle = $reportTitle ?? 'Report';
    $filterLine = $filterLine ?? '';
    $printedOn = $printedOn ?? now()->format('d-m-Y H:i');
    $centreColumns = $centreColumns ?? [];
    // 0-based row indexes to render as a grouping band / as a total line.
    $sectionRows = $sectionRows ?? [];
    $totalRows = $totalRows ?? [];

    $columnCount = max(1, count($headings));

    /**
     * Column widths, as percentages.
     *
     * table-layout:fixed with no widths gives every column an equal share, which
     * on a 13-column report squeezes "Reason" to the same width as "S. No." and
     * wraps every date onto three lines. Short columns are therefore pinned to a
     * width that fits their content and the rest share what is left.
     *
     * $columnWidths may be passed to override this entirely.
     */
    // Keys are matched against the heading with periods stripped and whitespace
    // collapsed, so "S. No." and "S.No" both land on 's no'.
    $normalise = fn ($heading) => preg_replace(
        '/\s+/', ' ',
        trim(str_replace('.', '', strtolower(strip_tags((string) $heading))))
    );

    $fixedWidths = [
        's no'         => 4.0,
        'sr no'        => 4.0,
        'ot code'      => 7.0,
        'date from'    => 7.5,
        'date to'      => 7.5,
        'from date'    => 7.5,
        'to date'      => 7.5,
        'time from'    => 7.0,
        'time to'      => 7.0,
        'total days'   => 6.0,
        'marks'        => 6.0,
        'status'       => 7.5,
        'mobile number'=> 11.0,
        'display order'=> 7.0,
    ];

    // Free-text columns need several times the width of a name: splitting the
    // leftover space evenly gave "Reason" the same share as "Recorded By", and a
    // sentence in a 9%-wide column wraps to eight lines, which is what drove the
    // row height (and therefore the page count) up.
    $flexWeights = [
        'reason' => 3.0,
        'remarks' => 3.0,
        'description' => 3.0,
        'comments' => 3.0,
        'diagnosis / remarks' => 3.0,
        'conclusion remark' => 3.0,
        'course name' => 1.8,
        'email' => 1.8,
        'discipline category' => 1.8,
    ];

    if (! isset($columnWidths) || ! is_array($columnWidths) || count($columnWidths) !== $columnCount) {
        $widths = [];
        $assigned = 0.0;
        $flexible = [];

        foreach ($headings as $i => $heading) {
            $key = $normalise($heading);
            if (isset($fixedWidths[$key])) {
                $widths[$i] = $fixedWidths[$key];
                $assigned += $fixedWidths[$key];
            } else {
                $widths[$i] = null;
                $flexible[$i] = $flexWeights[$key] ?? 1.0;
            }
        }

        // Whatever is left over is split between the text columns by weight. If
        // the fixed columns already fill the page (a report that is all dates and
        // codes), fall back to an even split rather than emitting negative widths.
        $remaining = 100.0 - $assigned;
        $weightTotal = array_sum($flexible);

        if ($flexible !== [] && $remaining > 0 && $weightTotal > 0) {
            foreach ($flexible as $i => $weight) {
                $widths[$i] = $remaining * ($weight / $weightTotal);
            }
        } else {
            $widths = array_fill(0, $columnCount, 100.0 / $columnCount);
        }

        $columnWidths = $widths;
    }

    // Wide reports need smaller type to stay on one page across.
    $bodyFont = $columnCount >= 12 ? 6.8 : ($columnCount >= 9 ? 7.4 : 8.2);
    $headFont = $bodyFont + 0.2;

    // DomPDF cannot fetch remote images; embed the logo as a data URI.
    $logoData = null;
    $logoPath = public_path('images/lbsnaa_logo.jpg');
    if (is_file($logoPath) && filesize($logoPath) < 2 * 1024 * 1024) {
        $logoData = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
    }
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} — LBSNAA</title>
    <style>
        /* Paper and orientation come from setPaper() on the caller; repeating
           them in @page made DomPDF lay the table out against one size and
           paginate against another, which spilled a single row over four pages. */
        @page { margin: 10mm 8mm 12mm; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; padding: 0; color: #1f2937; font-size: {{ $bodyFont }}pt; }

        /* ---- Institution header ---- */
        table.pdf-hdr { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.pdf-hdr td { vertical-align: middle; padding: 0; }
        table.pdf-hdr td.logo { width: 60px; text-align: left; }
        table.pdf-hdr td.logo img { height: 42px; }
        .inst-en { font-size: 12pt; font-weight: bold; color: #003366; line-height: 1.2; text-align: center; }
        .inst-sub { font-size: 7.5pt; color: #486581; text-align: center; }

        .report-title {
            text-align: center; font-size: 11.5pt; font-weight: bold; color: #004a93;
            margin: 5px 0 3px; padding-bottom: 3px; border-bottom: 2px solid #004a93;
        }

        .meta {
            font-size: 7pt; color: #444; text-align: center;
            background: #f0f4fa; border: 0.6px solid #cbd6e6;
            padding: 3px 6px; margin: 0 0 6px;
        }

        table.data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data-table th, table.data-table td {
            border: 0.7px solid #9bb0c9;
            padding: 3px 4px;
            text-align: left;
            vertical-align: top;
            /* break-word, NOT break-all: break-all splits ordinary prose
               mid-syllable, which triples the height of any Reason/Remarks
               column and drops the page down to a handful of rows. This breaks
               only tokens that genuinely cannot fit, such as a long email. */
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.data-table thead { display: table-header-group; }
        table.data-table thead th {
            background: #003366; color: #fff;
            font-size: {{ $headFont }}pt; font-weight: bold;
            text-align: center; vertical-align: middle;
            word-break: normal;
        }
        table.data-table tbody td { font-size: {{ $bodyFont }}pt; }
        table.data-table tbody tr:nth-child(even) td { background: #f5f8fc; }
        table.data-table td.is-centre { text-align: center; word-break: normal; }
        table.data-table tr { page-break-inside: avoid; }

        /* Grouping band inside the table (a house name, say) and a total line —
           the same two treatments the on-screen listing uses, so a download
           reads like the page it came from. */
        table.data-table tr.is-section td {
            background: #003366 !important;
            color: #fff;
            font-weight: bold;
            font-size: {{ $headFont }}pt;
            text-align: left;
        }
        table.data-table tr.is-total td {
            background: #eef3fa !important;
            color: #003366;
            font-weight: bold;
        }

        .empty { text-align: center; padding: 16px; color: #667085; font-style: italic; }

        .pdf-foot {
            margin-top: 6px; padding-top: 3px;
            border-top: 0.6px solid #dee2e6;
            font-size: 6.5pt; color: #667085;
            width: 100%; border-collapse: collapse;
        }
        .pdf-foot td { padding: 0; border: 0; }
        .pdf-foot td.r { text-align: right; }
    </style>
</head>
<body>
    <table class="pdf-hdr">
        <tr>
            <td class="logo">@if($logoData)<img src="{{ $logoData }}" alt="LBSNAA">@endif</td>
            <td>
                <div class="inst-en">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</div>
                <div class="inst-sub">Mussoorie, Uttarakhand</div>
            </td>
            <td class="logo"></td>
        </tr>
    </table>

    <div class="report-title">{{ $reportTitle }}</div>

    <div class="meta">
        @if($filterLine){{ $filterLine }} &nbsp;|&nbsp; @endif
        Total Records: {{ $rows->count() }} &nbsp;|&nbsp; Generated: {{ $printedOn }}
    </div>

    @if($rows->isEmpty())
        <div class="empty">No records found for the selected filters.</div>
    @else
        <table class="data-table">
            <colgroup>
                @foreach($headings as $i => $heading)
                    <col style="width: {{ round($columnWidths[$i] ?? (100 / $columnCount), 2) }}%;">
                @endforeach
            </colgroup>
            {{-- Width is repeated on the header cells, not left to <colgroup>
                 alone: DomPDF honours a width on the first row's cells far more
                 reliably than a colgroup, and without it the table falls back to
                 auto-sizing and the proportions above are ignored. --}}
            <thead>
                <tr>
                    @foreach($headings as $i => $heading)
                        <th style="width: {{ round($columnWidths[$i] ?? (100 / $columnCount), 2) }}%;">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $rowIndex => $row)
                    @php
                        $isSection = in_array($rowIndex, $sectionRows, true);
                        $isTotal = in_array($rowIndex, $totalRows, true);
                        $cells = array_values((array) $row);
                    @endphp
                    <tr class="{{ $isSection ? 'is-section' : ($isTotal ? 'is-total' : '') }}">
                        @if($isSection)
                            {{-- A band spans the table; its text lives in the first cell. --}}
                            <td colspan="{{ $columnCount }}">{{ $cells[0] ?? '' }}</td>
                        @else
                            @foreach($cells as $i => $cell)
                                <td class="{{ in_array($i, $centreColumns, true) ? 'is-centre' : '' }}">{{ $cell }}</td>
                            @endforeach
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="pdf-foot">
        <tr>
            <td>Lal Bahadur Shastri National Academy of Administration — {{ $reportTitle }}</td>
            <td class="r">Printed on {{ $printedOn }}</td>
        </tr>
    </table>
</body>
</html>
