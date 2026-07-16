@php
    $headings    = $headings ?? [];
    $rows        = $rows ?? collect();
    $filterLine  = $filterLine ?? '';
    $printedOn   = $printedOn ?? now()->format('d-m-Y H:i');
    $reportTitle = $reportTitle ?? 'Medical Exemption Report';
    $courseName  = $courseName ?? '';
    $logoLeft    = $logoLeft ?? null;
    $logoRight   = $logoRight ?? null;
    $titleHindi  = $titleHindi ?? null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} — LBSNAA</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 10mm; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; padding: 0; color: #1f2937; font-size: 9px; }

        table.pdf-hdr { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        table.pdf-hdr td { vertical-align: middle; }
        table.pdf-hdr .logo { width: 82px; text-align: center; }
        table.pdf-hdr .logo img { max-height: 56px; max-width: 78px; }
        table.pdf-hdr .center { text-align: center; padding: 0 6px; }
        .inst-hi-img { height: 15px; width: auto; margin-bottom: 1px; }
        .inst-en { font-size: 13px; font-weight: bold; color: #102a43; line-height: 1.25; }
        .course-line { font-size: 9px; font-weight: bold; color: #243b53; margin-top: 3px; }

        .report-title {
            text-align: center; font-size: 15px; font-weight: bold; color: #004a93;
            margin: 6px 0 4px; padding-bottom: 5px; border-bottom: 2px solid #004a93;
        }
        .meta { font-size: 8px; color: #444; margin: 0 0 8px; text-align: center; }

        table.data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data-table th, table.data-table td {
            border: 0.8px solid #8fa3bd; padding: 5px 6px; text-align: left;
            vertical-align: top; word-break: break-word; overflow-wrap: break-word;
        }
        table.data-table thead th {
            background: #004a93; color: #fff; font-weight: bold; font-size: 9px;
            text-align: center; border-color: #004a93;
        }
        table.data-table tbody tr:nth-child(even) { background: #eef2f8; }

        .col-sno { width: 6%; text-align: center; }
        .col-date { width: 12%; text-align: center; }
        .col-case { width: 18%; }
        .col-category { width: 22%; }
        .col-remarks { width: 42%; }
        .cell-center { text-align: center; }

        .footer { margin-top: 10px; text-align: center; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 7;
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $w    = $fontMetrics->getTextWidth($text, $font, $size);
            $pdf->page_text($pdf->get_width() - $w - 20, $pdf->get_height() - 18, $text, $font, $size, array(0.4, 0.4, 0.4));
        }
    </script>

    <table class="pdf-hdr">
        <tr>
            <td class="logo">@if($logoLeft)<img src="{{ $logoLeft }}" alt="">@endif</td>
            <td class="center">
                @if($titleHindi)<img class="inst-hi-img" src="{{ $titleHindi }}" alt="">@endif
                <div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>
                @if($courseName)<div class="course-line">{{ $courseName }}</div>@endif
            </td>
            <td class="logo">@if($logoRight)<img src="{{ $logoRight }}" alt="">@endif</td>
        </tr>
    </table>

    <div class="report-title">{{ $reportTitle }}</div>

    <div class="meta">
        @if($filterLine)<div>{{ $filterLine }}</div>@endif
        <div>Generated on: {{ $printedOn }} &nbsp;|&nbsp; Total records: {{ $rows->count() }}</div>
    </div>

    @php
        $classMap = [
            'Date' => 'col-date',
            'Medical Case' => 'col-case',
            'Exemption Category' => 'col-category',
            'Remarks' => 'col-remarks',
        ];
        $centerHeadings = ['Date'];
    @endphp
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-sno">S.No.</th>
                @foreach($headings as $heading)
                    <th class="{{ $classMap[$heading] ?? '' }}">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                @php $cells = array_values((array) $row); @endphp
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    @foreach($cells as $ci => $value)
                        @php
                            $heading = $headings[$ci] ?? '';
                            $colClass = $classMap[$heading] ?? '';
                            $isCenter = in_array($heading, $centerHeadings, true);
                        @endphp
                        <td class="{{ $colClass }}{{ $isCenter ? ' cell-center' : '' }}">{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) + 1 }}" style="text-align:center;">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">LBSNAA — {{ $reportTitle }}</div>
</body>
</html>
