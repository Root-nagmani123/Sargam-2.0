@php
    $headings    = $headings ?? [];
    $rows        = $rows ?? collect();
    $filterLine  = $filterLine ?? '';
    $printedOn   = $printedOn ?? now()->format('d-m-Y H:i');
    $reportTitle = $reportTitle ?? 'Discipline Memo Report';
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
        .col-program { width: 11%; }
        .col-name { width: 10%; }
        .col-code { width: 7%; }
        .col-cadre { width: 8%; }
        .col-date { width: 6%; text-align: center; }
        .col-infraction { width: 9%; }
        .col-marks { width: 6%; text-align: center; }
        .col-remarks { width: 12%; }
        .col-status { width: 6%; text-align: center; }

        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; color: #fff; font-weight: bold; }
        .badge-recorded { background: #198754; }
        .badge-sent { background: #ffc107; color: #212529; }
        .badge-closed { background: #6c757d; }

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
        <thead>
            <tr>
                <th class="col-sno">#</th>
                <th class="col-program">Program Name</th>
                <th class="col-name">Student Name</th>
                <th class="col-code">OT/Participant Code</th>
                <th class="col-cadre">Cadre</th>
                <th class="col-date">Date of Infraction</th>
                <th class="col-infraction">Infraction</th>
                <th class="col-marks">Submitted</th>
                <th class="col-marks">Final</th>
                <th class="col-remarks">Remarks</th>
                <th class="col-remarks">Conclusion Remark</th>
                <th class="col-date">Created Date</th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                @php
                    $cells = array_values((array) $row);
                    $status = $cells[11] ?? '';
                    $badgeClass = match ($status) {
                        'Recorded' => 'badge-recorded',
                        'Memo Sent' => 'badge-sent',
                        default => 'badge-closed',
                    };
                @endphp
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    <td class="col-program">{{ $cells[0] ?? '' }}</td>
                    <td class="col-name">{{ $cells[1] ?? '' }}</td>
                    <td class="col-code">{{ $cells[2] ?? '' }}</td>
                    <td class="col-cadre">{{ $cells[3] ?? '' }}</td>
                    <td class="col-date">{{ $cells[4] ?? '' }}</td>
                    <td class="col-infraction">{{ $cells[5] ?? '' }}</td>
                    <td class="col-marks">{{ $cells[6] ?? '' }}</td>
                    <td class="col-marks">{{ $cells[7] ?? '' }}</td>
                    <td class="col-remarks">{{ $cells[8] ?? '' }}</td>
                    <td class="col-remarks">{{ $cells[9] ?? '' }}</td>
                    <td class="col-date">{{ $cells[10] ?? '' }}</td>
                    <td class="col-status"><span class="badge {{ $badgeClass }}">{{ $status }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align:center;">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">LBSNAA — {{ $reportTitle }}</div>
</body>
</html>
