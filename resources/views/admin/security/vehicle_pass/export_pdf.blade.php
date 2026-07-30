@php
    $headings    = $headings ?? [];
    $rows        = $rows ?? collect();
    $filterLine  = $filterLine ?? '';
    $printedOn   = $printedOn ?? now()->format('d-m-Y H:i');
    $reportTitle = $reportTitle ?? 'Vehicle Pass Request';
    $logoLeft    = $logoLeft ?? null;
    $logoRight   = $logoRight ?? null;
    $titleHindi  = $titleHindi ?? null;
    $mode        = $mode ?? 'pdf';           // 'pdf' (DomPDF) or 'print' (browser)
    $isPrint     = $mode === 'print';
    $bodyFont    = $isPrint ? 11 : 9;
    $headFont    = $isPrint ? 10 : 8;
    $centerHeadings = ['S.No.', 'Requested Date', 'Status'];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} — LBSNAA</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 8mm; }
        * { font-family: 'DejaVu Sans', Arial, sans-serif; }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; padding: {{ $isPrint ? '16px' : '0' }}; color: #1f2937; font-size: {{ $bodyFont }}px; }

        table.pdf-hdr { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        table.pdf-hdr td { vertical-align: middle; }
        table.pdf-hdr .logo { width: {{ $isPrint ? '90px' : '78px' }}; text-align: center; }
        table.pdf-hdr .logo img { max-height: {{ $isPrint ? '60px' : '52px' }}; max-width: {{ $isPrint ? '84px' : '74px' }}; }
        table.pdf-hdr .center { text-align: center; padding: 0 6px; }
        .inst-hi-img { height: {{ $isPrint ? '18px' : '14px' }}; width: auto; margin-bottom: 1px; }
        .inst-en { font-size: {{ $isPrint ? '16px' : '12px' }}; font-weight: bold; color: #102a43; line-height: 1.25; }

        .report-title {
            text-align: center;
            font-size: {{ $isPrint ? '19px' : '15px' }};
            font-weight: bold;
            color: #004a93;
            margin: 6px 0 4px;
            padding-bottom: 5px;
            border-bottom: 2px solid #004a93;
        }

        .meta { font-size: {{ $isPrint ? '10px' : '8px' }}; color: #444; margin: 0 0 6px; text-align: center; }

        table.data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data-table th,
        table.data-table td {
            border: 0.8px solid #8fa3bd;
            padding: {{ $isPrint ? '6px 8px' : '3px 4px' }};
            text-align: left;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        table.data-table thead th {
            background: #004a93 !important;
            color: #fff !important;
            font-weight: bold;
            font-size: {{ $headFont }}px;
            text-align: center;
            border-color: #004a93;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        table.data-table tbody tr:nth-child(even) {
            background: #eef2f8;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .col-sno { width: 5%; }
        .col-employee { width: 24%; }
        .col-pass { width: 15%; }
        .col-type { width: 14%; }
        .col-veh { width: 14%; }
        .col-date { width: 14%; }
        .col-status { width: 10%; }
        .cell-center { text-align: center; }

        .footer { margin-top: 8px; text-align: center; font-size: {{ $isPrint ? '9px' : '7px' }}; color: #666; }
    </style>
</head>
<body @if($isPrint) onload="window.focus(); window.print();" @endif>

    @unless($isPrint)
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
    @endunless

    {{-- Institution header --}}
    <table class="pdf-hdr">
        <tr>
            <td class="logo">@if($logoLeft)<img src="{{ $logoLeft }}" alt="">@endif</td>
            <td class="center">
                @if($titleHindi)<img class="inst-hi-img" src="{{ $titleHindi }}" alt="">@endif
                <div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>
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
        $headingClassMap = [
            'S.No.' => 'col-sno',
            'Employee Name' => 'col-employee',
            'Vehicle Pass No' => 'col-pass',
            'Vehicle Type' => 'col-type',
            'Vehicle Number' => 'col-veh',
            'Requested Date' => 'col-date',
            'Status' => 'col-status',
        ];
    @endphp
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
                        @endphp
                        <td class="{{ $colClass }}{{ $isCenter ? ' cell-center' : '' }}">{{ $value }}</td>
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
