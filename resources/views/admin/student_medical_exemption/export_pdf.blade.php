@php
    $headings       = $headings ?? [];
    $rows           = $rows ?? collect();
    $filterLine     = $filterLine ?? '';
    $printedOn      = $printedOn ?? now()->format('d-m-Y H:i');
    $reportTitle    = $reportTitle ?? 'Student Medical Exemption';
    $courseName     = $courseName ?? '';
    $courseDuration = $courseDuration ?? '';
    $logoLeft       = $logoLeft ?? null;
    $logoRight      = $logoRight ?? null;
    $titleHindi     = $titleHindi ?? null;
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
        body { margin: 0; padding: 0; color: #1f2937; font-size: 7px; }

        /* ===== Institution header (matches the official LBSNAA report layout) ===== */
        table.pdf-hdr { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        table.pdf-hdr td { vertical-align: middle; }
        table.pdf-hdr .logo { width: 78px; text-align: center; }
        table.pdf-hdr .logo img { max-height: 52px; max-width: 74px; }
        table.pdf-hdr .center { text-align: center; padding: 0 6px; }
        /* Devanagari title is a pre-shaped image — DomPDF can't shape Indic text. */
        .inst-hi-img { height: 14px; width: auto; margin-bottom: 1px; }
        .inst-en { font-size: 12px; font-weight: bold; color: #102a43; line-height: 1.25; }
        .course-line { font-size: 9px; font-weight: bold; color: #243b53; margin-top: 3px; }
        .course-dates { font-size: 8px; color: #486581; margin-top: 1px; }

        .report-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            color: #004a93;
            margin: 6px 0 4px;
            padding-bottom: 5px;
            border-bottom: 2px solid #004a93;
        }

        .meta { font-size: 7px; color: #444; margin: 0 0 6px; text-align: center; }

        table.data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data-table th,
        table.data-table td {
            border: 0.8px solid #8fa3bd;
            padding: 3px 4px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        table.data-table thead th {
            background: #004a93;
            color: #fff;
            font-weight: bold;
            font-size: 6.5px;
            text-align: center;
            border-color: #004a93;
        }
        table.data-table tbody tr:nth-child(even) { background: #eef2f8; }
        a.doc-link { color: #004a93; text-decoration: underline; }

        /* Give long-text columns more room; keep compact fields narrow. */
        .col-sno { width: 3%; }
        .col-date { width: 5%; }
        .col-ot { width: 9%; }
        .col-course { width: 8%; }
        .col-doctor { width: 6%; }
        .col-speciality { width: 6%; }
        .col-duration { width: 9%; }
        .col-days { width: 3%; }
        .col-category { width: 7%; }
        .col-opd { width: 6%; }
        .col-pt-advise { width: 14%; }
        .col-remarks { width: 16%; }
        .col-document { width: 5%; text-align: center; }
        .cell-wide { font-size: 6.5px; line-height: 1.35; }
        .cell-center { text-align: center; }

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

    {{-- Institution header --}}
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

    @php
        // Every column (S.No included) is driven by $headings / $rows now, so hiding
        // a column on-screen simply drops it from both here.
        $headingClassMap = [
            'S.No.' => 'col-sno',
            'Date' => 'col-date',
            'Officer Trainee' => 'col-ot',
            'Course' => 'col-course',
            'Doctor Name' => 'col-doctor',
            'Medical Speciality' => 'col-speciality',
            'Duration' => 'col-duration',
            'Days' => 'col-days',
            'Category' => 'col-category',
            'IPD/OPD/After OPD/Referral' => 'col-opd',
            'PT/ Outdoor Advise' => 'col-pt-advise',
            'Diagnosis / Remarks' => 'col-remarks',
            'Document' => 'col-document',
        ];
        $wideHeadings = [
            'PT/ Outdoor Advise',
            'Diagnosis / Remarks',
        ];
        $centerHeadings = ['S.No.', 'Days'];
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
                            $isWide = in_array($heading, $wideHeadings, true);
                            $isCenter = in_array($heading, $centerHeadings, true);
                            $isDocUrl = $heading === 'Document'
                                && is_string($value)
                                && $value !== 'NA'
                                && $value !== ''
                                && (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/'));
                        @endphp
                        <td class="{{ $colClass }}{{ $isWide ? ' cell-wide' : '' }}{{ $isCenter ? ' cell-center' : '' }}">
                            @if($isDocUrl)
                                <a class="doc-link" href="{{ $value }}" target="_blank" rel="noopener noreferrer">View</a>
                            @else
                                {{ $value }}
                            @endif
                        </td>
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
