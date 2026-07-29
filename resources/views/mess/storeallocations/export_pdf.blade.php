@php
    $headings    = $headings ?? [];
    $rows        = $rows ?? collect();
    $filterLine  = $filterLine ?? '';
    $printedOn   = $printedOn ?? now()->format('d-m-Y H:i');
    $reportTitle = $reportTitle ?? 'Mess Store Allocation';
    $courseName     = $courseName ?? '';
    $courseDuration = $courseDuration ?? '';
    $logoLeft    = $logoLeft ?? null;
    $logoRight   = $logoRight ?? null;
    $titleHindi  = $titleHindi ?? null;

    $headingClassMap = [
        'S.No.'           => 'col-sno',
        'Store Name'      => 'col-store',
        'Item Name'       => 'col-name',
        'Item Type'       => 'col-type',
        'Number of Items' => 'col-qty',
        'Date'            => 'col-date',
        'Total'           => 'col-total',
    ];
    $centerHeadings = ['S.No.', 'Number of Items', 'Date'];
    $rightHeadings = ['Total'];
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
        table.pdf-hdr .logo { width: 82px; text-align: center; }
        table.pdf-hdr .logo img { max-height: 54px; max-width: 78px; }
        table.pdf-hdr .center { text-align: center; padding: 0 6px; }
        .inst-hi-img { height: 15px; width: auto; margin-bottom: 1px; }
        .inst-en { font-size: 12.5px; font-weight: bold; color: #102a43; line-height: 1.25; }
        .course-line { font-size: 9.5px; font-weight: bold; color: #243b53; margin-top: 3px; }
        .course-dates { font-size: 8.5px; color: #486581; margin-top: 1px; }

        .report-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #004a93;
            margin: 7px 0 4px;
            padding-bottom: 5px;
            border-bottom: 2px solid #004a93;
        }

        .meta { font-size: 7.5px; color: #444; margin: 0 0 7px; text-align: center; }

        table.data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data-table th,
        table.data-table td {
            border: 0.8px solid #8fa3bd;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        table.data-table thead th {
            background: #004a93;
            color: #fff;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
            border-color: #004a93;
        }
        table.data-table tbody tr:nth-child(even) { background: #eef2f8; }

        .col-sno { width: 6%; }
        .col-store { width: 22%; }
        .col-name { width: 24%; }
        .col-type { width: 17%; }
        .col-qty { width: 11%; }
        .col-date { width: 10%; }
        .col-total { width: 10%; }
        .cell-center { text-align: center; }
        .cell-right { text-align: right; }

        .footer { margin-top: 8px; text-align: center; font-size: 7.5px; color: #666; }
    </style>
</head>
<body>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 8;
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $w    = $fontMetrics->getTextWidth($text, $font, $size);
            $pdf->page_text($pdf->get_width() - $w - 20, $pdf->get_height() - 20, $text, $font, $size, array(0.4, 0.4, 0.4));
        }
    </script>

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
        @if($filterLine)<div>{{ $filterLine }}</div>@endif
        <div>Generated on: {{ $printedOn }} &nbsp;|&nbsp; Total records: {{ $rows->count() }}</div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th class="{{ $headingClassMap[$heading] ?? '' }}">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                @php $cells = array_values((array) $row); @endphp
                <tr>
                    @foreach($cells as $ci => $value)
                        @php
                            $heading = $headings[$ci] ?? '';
                            $colClass = $headingClassMap[$heading] ?? '';
                            $isCenter = in_array($heading, $centerHeadings, true);
                            $isRight = in_array($heading, $rightHeadings, true);
                            $alignClass = $isCenter ? ' cell-center' : ($isRight ? ' cell-right' : '');
                        @endphp
                        <td class="{{ $colClass }}{{ $alignClass }}">{!! nl2br(e($value)) !!}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($headings), 1) }}" style="text-align:center;">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">LBSNAA — {{ $reportTitle }} Report</div>
</body>
</html>
