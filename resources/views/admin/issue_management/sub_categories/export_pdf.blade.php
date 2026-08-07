{{-- Manage Sub-Categories → PDF (DomPDF).

     Deliberately NOT sharing export_print_styles: DomPDF has no print-color-adjust,
     no @media print and only partial CSS support, and it needs base64 image data
     rather than asset() URLs. The visual language is kept identical by hand —
     same navy, same header order, same zebra. --}}
@php
    $logoFor = function (string $relative): ?string {
        $path = public_path($relative);
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }
        $mime = str_ends_with(strtolower($relative), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    };

    $emblem = $logoFor('images/ashoka.png');
    $logo   = $logoFor('images/lbsnaa_logo.jpg');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Sub-Categories — LBSNAA</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 10mm; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; padding: 0; color: #1f2937; font-size: 9px; }

        table.pdf-hdr { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.pdf-hdr td { vertical-align: middle; padding: 0; }
        table.pdf-hdr .logo { width: 120px; }
        table.pdf-hdr .logo img { height: 44px; }
        table.pdf-hdr .centre { text-align: center; }

        .inst { font-size: 12px; font-weight: bold; color: #003366; line-height: 1.3; }
        .sub  { font-size: 8px; color: #4b5563; margin-top: 2px; }
        .rule { border-bottom: 2px solid #003366; margin-bottom: 6px; }

        .report-title { text-align: center; font-size: 12px; font-weight: bold; color: #003366; margin: 5px 0 2px; }
        .meta  { text-align: center; font-size: 8px; color: #6b7280; margin-bottom: 6px; }
        .total { text-align: center; font-size: 9px; font-weight: bold; color: #003366;
                 background: #eef2f8; padding: 3px 0; margin-bottom: 6px; }

        table.data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data-table th, table.data-table td {
            border: 0.8px solid #cccccc;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.data-table thead th {
            background: #003366;
            color: #ffffff;
            font-weight: bold;
            font-size: 8.5px;
            border-color: #002244;
        }
        table.data-table tbody tr:nth-child(even) { background: #f4f7fb; }

        /* DomPDF ignores <colgroup> widths — they have to sit on the cells. */
        .col-sno      { width: 8%;  text-align: center; }
        .col-category { width: 34%; }
        .col-sub      { width: 42%; }
        .col-status   { width: 16%; text-align: center; }

        .empty { text-align: center; padding: 16px; color: #6b7280; }
        .foot  { margin-top: 8px; text-align: center; font-size: 7px; color: #6b7280; }
    </style>
</head>
<body>

    {{-- Page numbers on every page (DomPDF; needs isPhpEnabled). --}}
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $size = 7;
            $w = $fontMetrics->getTextWidth($text, $font, $size);
            $pdf->page_text($pdf->get_width() - $w - 28, $pdf->get_height() - 24, $text, $font, $size, [0.42, 0.45, 0.5]);
        }
    </script>

    <table class="pdf-hdr">
        <tr>
            <td class="logo">
                @if($emblem)<img src="{{ $emblem }}" alt="">@endif
                @if($logo)<img src="{{ $logo }}" alt="">@endif
            </td>
            <td class="centre">
                <div class="inst">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</div>
                <div class="sub">Mussoorie, Uttarakhand &nbsp;|&nbsp; Sargam 2.0</div>
            </td>
            <td class="logo"></td>
        </tr>
    </table>

    <div class="rule"></div>

    <div class="report-title">MANAGE SUB-CATEGORIES</div>
    <div class="meta">
        @if(filled($search))Search: {{ $search }} &nbsp;|&nbsp; @endif Generated: {{ $exportDate }}
    </div>
    <div class="total">Total Records: {{ number_format($rows->count()) }}</div>

    {{-- Same resolved $columns as the CSV, Excel and print view. Keyed by column,
         never by position, so a hidden column drops cleanly from all four. --}}
    <table class="data-table">
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th class="{{ $col['class'] }}">{{ $col['heading'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    @foreach ($columns as $col)
                        <td class="{{ $col['class'] }}">{{ $col['value']($row, $index) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}" class="empty">No sub-categories to export</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="foot">Sargam 2.0 · Centcom · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
