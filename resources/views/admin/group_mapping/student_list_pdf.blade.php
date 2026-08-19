@php
    $columns     = $columns ?? [];
    $rows        = $rows ?? collect();
    $group       = $group ?? [];
    $printedOn   = $printedOn ?? now()->format('d-m-Y H:i');
    $reportTitle = $reportTitle ?? 'Course Group Mapping - Student List';
    $logo        = $logo ?? null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} — LBSNAA</title>
    <style>
        {{-- Portrait, unlike the 14-column Discipline Memo report this styling is
             taken from: a 5-column student list wastes most of a landscape page. --}}
        @page { size: A4 portrait; margin: 10mm 8mm; }
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

        /* The highlighted Course Name / Course Duration / Group Type strip this
           report is built around — amber so it stands apart from the navy branding
           above and the navy table heading below. */
        table.course-strip {
            width: 100%;
            border-collapse: collapse;
            background: #fff3cd;
            border: 0.8px solid #e0a800;
            margin: 0 0 4px;
        }
        table.course-strip td {
            padding: 4px 6px;
            font-size: 9px;
            color: #663c00;
            vertical-align: top;
        }
        table.course-strip .lbl { font-weight: bold; }

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

        .col-sno { width: 6%; text-align: center; }
        .col-name { width: 30%; }
        .col-code { width: 16%; }
        /* Emails are long and unbreakable; without word-break they force the whole
           landscape table wider than the page and every other column collapses. */
        .col-email { width: 30%; word-break: break-all; }
        .col-mobile { width: 18%; }

        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }

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

        <table class="course-strip">
            <tr>
                <td style="width: 42%;"><span class="lbl">Course Name:</span> {{ $group['course_name'] ?? 'N/A' }}</td>
                <td style="width: 33%;"><span class="lbl">Course Duration:</span> {{ $group['course_duration'] ?? 'N/A' }}</td>
                <td style="width: 25%;"><span class="lbl">Group Type:</span> {{ $group['group_type'] ?? 'N/A' }}</td>
            </tr>
        </table>

        <div class="meta">
            <div>Group Name: {{ $group['group_name'] ?? 'N/A' }}  |  Faculty: {{ $group['faculty'] ?? 'N/A' }}  |  Generated: {{ $printedOn }}</div>
        </div>

        <div class="totals">Total Students: {{ $rows->count() }}</div>
    </div>

    <table class="data-table">
        {{-- Columns are driven by $columns (GroupMappingStudentListExport::columnDefs())
             rather than hard-coded, so this can't drift from the Excel sheet. Cells are
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
                        <td class="{{ $col['class'] }}">{{ $row[$col['key']] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $colCount }}" style="text-align:center;">No students found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">LBSNAA — {{ $reportTitle }}</div>
</body>
</html>
