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
    $orientation = $orientation ?? 'landscape';
    $centreColumns = $centreColumns ?? [];

    // DomPDF cannot fetch remote images; embed the logo as a data URI.
    $logoData = null;
    $logoPath = public_path('images/lbsnaa_logo.jpg');
    if (is_file($logoPath) && filesize($logoPath) < 2 * 1024 * 1024) {
        $logoData = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
    }

    $columnCount = max(1, count($headings));
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} — LBSNAA</title>
    <style>
        @page { size: A4 {{ $orientation }}; margin: 10mm 8mm 14mm; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; padding: 0; color: #1f2937; font-size: 8px; }

        /* ---- Institution header ---- */
        table.pdf-hdr { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.pdf-hdr td { vertical-align: middle; }
        table.pdf-hdr .logo { width: 66px; text-align: center; }
        table.pdf-hdr .logo img { max-height: 46px; max-width: 62px; }
        .inst-en { font-size: 12.5px; font-weight: bold; color: #003366; line-height: 1.25; text-align: center; }
        .inst-sub { font-size: 8px; color: #486581; text-align: center; margin-top: 1px; }

        .report-title {
            text-align: center; font-size: 13px; font-weight: bold; color: #004a93;
            margin: 6px 0 3px; padding-bottom: 4px; border-bottom: 2px solid #004a93;
        }

        .meta {
            font-size: 7.5px; color: #444; text-align: center;
            background: #f0f4fa; border: 0.6px solid #cbd6e6;
            padding: 3px 6px; margin: 0 0 7px;
        }

        table.data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data-table th, table.data-table td {
            border: 0.7px solid #9bb0c9; padding: 3px 4px;
            text-align: left; vertical-align: top;
            word-wrap: break-word; overflow-wrap: break-word; word-break: break-word;
        }
        table.data-table thead { display: table-header-group; }
        table.data-table thead th {
            background: #003366; color: #fff; font-size: 7.5px; font-weight: bold;
            text-align: center; vertical-align: middle;
        }
        table.data-table tbody td { font-size: 7.5px; }
        table.data-table tbody tr:nth-child(even) td { background: #f5f8fc; }
        table.data-table td.is-centre { text-align: center; }
        table.data-table tr { page-break-inside: avoid; }

        .empty { text-align: center; padding: 16px; color: #667085; font-style: italic; }

        .pdf-footer {
            position: fixed; bottom: -8mm; left: 0; right: 0;
            font-size: 7px; color: #667085; border-top: 0.6px solid #dee2e6; padding-top: 3px;
        }
        .pdf-footer .left { float: left; }
        .pdf-footer .right { float: right; }
    </style>
</head>
<body>
    <div class="pdf-footer">
        <span class="left">Lal Bahadur Shastri National Academy of Administration — {{ $reportTitle }}</span>
        <span class="right">Printed on {{ $printedOn }}</span>
    </div>

    <table class="pdf-hdr">
        <tr>
            <td class="logo">
                @if($logoData)<img src="{{ $logoData }}" alt="LBSNAA">@endif
            </td>
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
            <thead>
                <tr>
                    @foreach($headings as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach(array_values((array) $row) as $i => $cell)
                            <td class="{{ in_array($i, $centreColumns, true) ? 'is-centre' : '' }}">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
