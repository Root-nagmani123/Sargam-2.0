@php
    $columns     = $columns ?? [];
    $rows        = $rows ?? collect();
    $filterLine  = $filterLine ?? '';
    $printedOn   = $printedOn ?? now()->format('d-m-Y H:i');
    $reportTitle = $reportTitle ?? 'Send Memo / Notice Report';
    $logo        = $logo ?? null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} — LBSNAA</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 8mm; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; padding: 0; color: #1f2937; font-size: 8px; }

        table.pdf-hdr { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        table.pdf-hdr td { vertical-align: middle; }
        table.pdf-hdr .logo { width: 78px; text-align: center; }
        table.pdf-hdr .logo img { max-height: 50px; max-width: 74px; }
        table.pdf-hdr .center { text-align: center; padding: 0 6px; }
        .inst-en { font-size: 14px; font-weight: bold; color: #003366; line-height: 1.3; }

        .report-title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            color: #004a93;
            margin: 4px 0 4px;
        }

        .meta {
            font-size: 8px;
            color: #555;
            text-align: center;
            margin: 0 0 3px;
        }

        .totals {
            font-size: 9px;
            font-weight: bold;
            color: #003366;
            text-align: center;
            background: #f0f4fa;
            padding: 3px 0;
            margin: 0 0 6px;
        }

        .pdf-hdr-border { border-bottom: 2px solid #003366; margin-bottom: 6px; }

        table.data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data-table th,
        table.data-table td {
            border: 0.8px solid #cccccc;
            padding: 3px 4px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        table.data-table thead th {
            background: #003366;
            color: #fff;
            font-weight: bold;
            font-size: 7.5px;
            text-align: center;
            border-color: #002244;
        }
        table.data-table tbody tr:nth-child(even) { background: #f4f7fb; }

        .col-sno { width: 3%; text-align: center; }
        .col-program { width: 12%; }
        .col-name { width: 11%; }
        .col-code { width: 8%; }
        .col-type { width: 6%; text-align: center; }
        .col-date { width: 7%; text-align: center; }
        .col-topic { width: 12%; }
        .col-status { width: 6%; text-align: center; }
        .col-conclusion { width: 9%; }
        .col-discussion { width: 10%; }
        .col-remarks { width: 16%; }

        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-weight: bold; }
        .badge-notice { background: #cfe2ff; color: #084298; }
        .badge-memo { background: #e2e3e5; color: #41464b; }
        .badge-open { background: #d1e7dd; color: #0f5132; }
        .badge-close { background: #f8d7da; color: #842029; }

        .footer { margin-top: 8px; text-align: center; font-size: 7px; color: #666; }
    </style>
</head>
<body>

    {{-- Page numbers on every page (DomPDF; needs isPhpEnabled). --}}
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 7;
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $w    = $fontMetrics->getTextWidth($text, $font, $size);
            $pdf->page_text($pdf->get_width() - $w - 20, $pdf->get_height() - 18, $text, $font, $size, array(0.4, 0.4, 0.4));
        }
    </script>

    <div class="pdf-hdr-border">
        <table class="pdf-hdr">
            <tr>
                <td class="logo">@if($logo)<img src="{{ $logo }}" alt="">@endif</td>
                <td class="center">
                    <div class="inst-en">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</div>
                </td>
                <td class="logo"></td>
            </tr>
        </table>

        <div class="report-title">{{ strtoupper($reportTitle) }}</div>

        <div class="meta">
            @if($filterLine)<div>{{ $filterLine }}  |  Generated: {{ $printedOn }}</div>@endif
        </div>

        <div class="totals">Total Records: {{ $rows->count() }}</div>
    </div>

    <table class="data-table">
        {{-- Columns are driven by $columns (MemoNoticeExport::columnDefs()) rather
             than hard-coded, so this can't drift from the Excel sheet. Cells are
             keyed by column key — never by position. --}}
        @php $colCount = count($columns) + 1; @endphp
        <thead>
            <tr>
                <th class="col-sno">#</th>
                @foreach($columns as $col)
                    <th class="{{ $col['class'] }}">{{ $col['heading'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    @foreach($columns as $col)
                        @php $value = $row[$col['key']] ?? ''; @endphp
                        @if($col['key'] === 'type')
                            <td class="{{ $col['class'] }}"><span class="badge {{ $value === 'Memo' ? 'badge-memo' : 'badge-notice' }}">{{ $value }}</span></td>
                        @elseif($col['key'] === 'status')
                            <td class="{{ $col['class'] }}"><span class="badge {{ $value === 'Open' ? 'badge-open' : 'badge-close' }}">{{ $value }}</span></td>
                        @else
                            <td class="{{ $col['class'] }}">{{ $value }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $colCount }}" style="text-align:center;">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">LBSNAA — {{ $reportTitle }}</div>
</body>
</html>
