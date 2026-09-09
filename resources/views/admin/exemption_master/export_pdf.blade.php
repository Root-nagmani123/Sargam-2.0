@php
    $rows = $rows ?? collect();
    $filterLine = $filterLine ?? '';
    $printedOn = $printedOn ?? now()->format('d-m-Y H:i');
    $reportTitle = $reportTitle ?? 'PT Exemption Master Report';
    $logo = $logo ?? null;
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
        body { margin: 0; padding: 0; color: #1f2937; font-size: 9px; }

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
            font-size: 9px;
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
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        table.data-table thead th {
            background: #003366;
            color: #fff;
            font-weight: bold;
            font-size: 8.5px;
            text-align: center;
            border-color: #002244;
        }
        table.data-table tbody tr:nth-child(even) { background: #f4f7fb; }

        .col-sno { width: 6%; text-align: center; }
        .col-course { width: 30%; }
        .col-date { width: 13%; text-align: center; }
        .col-timing { width: 13%; text-align: center; }
        .col-gender { width: 12%; text-align: center; }
        .col-count { width: 15%; text-align: center; }
        .col-status { width: 11%; text-align: center; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; color: #fff; font-weight: bold; }
        .badge-active { background: #198754; }
        .badge-inactive { background: #6c757d; }

        .footer { margin-top: 8px; text-align: center; font-size: 7px; color: #666; }
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
                <th class="col-sno">S. No.</th>
                <th class="col-course">Course</th>
                <th class="col-date">Effective From</th>
                <th class="col-timing">PT Timing</th>
                <th class="col-gender">Gender</th>
                <th class="col-count">PT Exemption Count (Days)</th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                @php
                    $cutoffTime = $row->course->pt_start_time ?? $row->apply_cutoff_time;
                    $isActive = (int) $row->active_inactive === 1;
                @endphp
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    <td class="col-course">{{ $row->course->course_name ?? 'N/A' }}</td>
                    <td class="col-date">{{ $row->effective_from ? $row->effective_from->format('d-m-Y') : 'N/A' }}</td>
                    <td class="col-timing">{{ blank($cutoffTime) ? 'N/A' : \Carbon\Carbon::parse($cutoffTime)->format('h:i A') }}</td>
                    <td class="col-gender">{{ $row->gender }}</td>
                    <td class="col-count">{{ number_format((float) $row->exemption_days, 1) }} Days</td>
                    <td class="col-status"><span class="badge badge-{{ $isActive ? 'active' : 'inactive' }}">{{ $isActive ? 'Active' : 'Inactive' }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;">No PT exemption configuration found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">LBSNAA — {{ $reportTitle }}</div>
</body>
</html>
