{{-- Examination Drive → PDF (DomPDF). Same branded header/table language as the
     other admin export PDFs (see admin.issue_management.priorities.export_pdf). --}}
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
    <title>Examination Drive Configuration — LBSNAA</title>
    <style>
        @page { size: A4 landscape; margin: 12mm 10mm; }
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

        .col-sno    { width: 6%;  text-align: center; }
        .col-type   { width: 16%; }
        .col-term   { width: 12%; }
        .col-course { width: 12%; }
        .col-phase  { width: 10%; text-align: center; }
        .col-session{ width: 10%; text-align: center; }
        .col-date   { width: 12%; text-align: center; }
        .col-status { width: 10%; text-align: center; }

        .empty { text-align: center; padding: 16px; color: #6b7280; }
        .foot  { margin-top: 8px; text-align: center; font-size: 7px; color: #6b7280; }
    </style>
</head>
<body>

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

    <div class="report-title">EXAMINATION DRIVE CONFIGURATION</div>
    <div class="meta">Generated: {{ $exportDate }}</div>
    <div class="total">Total Records: {{ number_format($rows->count()) }}</div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="col-sno">S. No.</th>
                <th class="col-type">Examination Type</th>
                <th class="col-term">Term</th>
                <th class="col-course">Course</th>
                <th class="col-phase">Phase</th>
                <th class="col-session">Academic Session</th>
                <th class="col-date">Start Date</th>
                <th class="col-date">End Date</th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    <td class="col-type">{{ $row->examinationType->exam_type_name ?? '-' }}</td>
                    <td class="col-term">{{ $row->term->term_name ?? '-' }}</td>
                    <td class="col-course">{{ $row->course->couse_short_name ?? $row->course->course_name ?? '-' }}</td>
                    <td class="col-phase">{{ $row->phase }}</td>
                    <td class="col-session">{{ $row->academic_session }}</td>
                    <td class="col-date">{{ $row->start_date ? \Carbon\Carbon::parse($row->start_date)->format('d-m-Y') : '' }}</td>
                    <td class="col-date">{{ $row->end_date ? \Carbon\Carbon::parse($row->end_date)->format('d-m-Y') : '' }}</td>
                    <td class="col-status">{{ \App\Models\ExaminationDrive::STATUS_LABELS[(int) $row->status] ?? 'Draft' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No examination drives to export</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="foot">Sargam 2.0 · Lal Bahadur Shastri National Academy of Administration</div>
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $size = 7;
            $w = $fontMetrics->getTextWidth($text, $font, $size);
            $pdf->page_text($pdf->get_width() - $w - 28, $pdf->get_height() - 24, $text, $font, $size, [0.42, 0.45, 0.5]);
        }
    </script>
</body>
</html>
