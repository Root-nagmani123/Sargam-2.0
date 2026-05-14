@php
    $printedOn = now()->format('d-m-Y H:i');

    // Emblem
    $emblemSrc = $emblemSrc ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';

    // LBSNAA logo
    $lbsnaaLogoSrc = $lbsnaaLogoSrc ?? 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
    foreach ([public_path('images/lbsnaa_logo.jpg'), public_path('images/lbsnaa_logo.png')] as $logoPath) {
        if (is_file($logoPath) && is_readable($logoPath)) {
            $raw = @file_get_contents($logoPath);
            if ($raw !== false) {
                $mime = str_ends_with(strtolower($logoPath), '.png') ? 'image/png' : 'image/jpeg';
                $lbsnaaLogoSrc = 'data:' . $mime . ';base64,' . base64_encode($raw);
                break;
            }
        }
    }
    if (str_starts_with($lbsnaaLogoSrc, 'http')) {
        foreach ([
            public_path('admin_assets/images/logos/logo.png'),
            public_path('admin_assets/images/logos/logo.svg'),
        ] as $localLogoPath) {
            if (is_file($localLogoPath) && is_readable($localLogoPath)) {
                $raw = @file_get_contents($localLogoPath);
                if ($raw !== false) {
                    $ext = strtolower(pathinfo($localLogoPath, PATHINFO_EXTENSION));
                    $mime = match ($ext) {
                        'svg' => 'image/svg+xml',
                        'png' => 'image/png',
                        'jpg', 'jpeg' => 'image/jpeg',
                        default => null,
                    };
                    if ($mime) {
                        $lbsnaaLogoSrc = 'data:' . $mime . ';base64,' . base64_encode($raw);
                        break;
                    }
                }
            }
        }
    }

    $rows = $rows ?? [];
    $rowCount = count($rows);

    // Build filter summary
    $filterParts = [];
    if (!empty($filterSummary['course_name'])) {
        $filterParts[] = 'Course: ' . $filterSummary['course_name'];
    }
    if (!empty($filterSummary['faculty_name'])) {
        $filterParts[] = 'Faculty: ' . $filterSummary['faculty_name'];
    }
    if (!empty($filterSummary['faculty_type'])) {
        $filterParts[] = 'Faculty Type: ' . $filterSummary['faculty_type'];
    }
    if (!empty($filterSummary['venue_name'])) {
        $filterParts[] = 'Venue: ' . $filterSummary['venue_name'];
    }
    if (!empty($filterSummary['subject_topic'])) {
        $filterParts[] = 'Topic: ' . $filterSummary['subject_topic'];
    }
    if (!empty($filterSummary['module_name'])) {
        $filterParts[] = 'Module: ' . $filterSummary['module_name'];
    }
    if (!empty($filterSummary['date_from'])) {
        $filterParts[] = 'From: ' . date('d-M-Y', strtotime($filterSummary['date_from']));
    }
    if (!empty($filterSummary['date_to'])) {
        $filterParts[] = 'To: ' . date('d-M-Y', strtotime($filterSummary['date_to']));
    }
    $filterLine = !empty($filterParts) ? implode(' | ', $filterParts) : 'No filters applied';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Timetable Session Report - LBSNAA MUSSOORIE</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 10mm 12mm 10mm;
        }
        * { box-sizing: border-box; }
        html { font-size: 9pt; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 9pt;
            margin: 0;
            padding: 0;
            color: #212529;
            background: #fff;
            line-height: 1.4;
        }

        /* ── Header ── */
        .pdf-header {
            border-bottom: 2.5px solid #0b4a7e;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .pdf-header table { width: 100%; border-collapse: collapse; }
        .pdf-header td { border: 0; padding: 0; vertical-align: middle; }
        .pdf-header .hdr-left { width: 50px; }
        .pdf-header .hdr-left img { width: 40px; height: 40px; }
        .pdf-header .hdr-center { padding-left: 10px; }
        .pdf-header .hdr-right { width: 50px; text-align: right; }
        .pdf-header .hdr-right img { width: 40px; height: 40px; }
        .brand-1 { font-size: 7pt; text-transform: uppercase; letter-spacing: 0.06em; color: #0b4a7e; font-weight: 600; }
        .brand-2 { font-size: 9.5pt; font-weight: 700; text-transform: uppercase; color: #111; margin-top: 2px; }
        .brand-3 { font-size: 7.5pt; color: #555; margin-top: 2px; }

        /* ── Report title block ── */
        .report-title-block {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title {
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
            margin: 0 0 5px;
        }
        .report-date-pill {
            display: inline-block;
            background: #0b4a7e;
            color: #fff;
            font-weight: 600;
            font-size: 8pt;
            padding: 3px 12px;
            border-radius: 10px;
        }

        /* ── Meta section ── */
        .report-meta {
            font-size: 8pt;
            margin-bottom: 8px;
            line-height: 1.5;
            color: #334155;
        }
        .report-meta .meta-label { font-weight: 700; color: #0f172a; }

        /* ── Main data table ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            padding: 3px 4px;
            border: 1px solid #d1d5db;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
        }

        /* Header row */
        .data-table thead th {
            background: #0b4a7e;
            color: #fff;
            font-weight: 600;
            font-size: 7pt;
            text-align: left;
            white-space: nowrap;
        }
        .data-table thead th.text-center { text-align: center; }

        /* Item rows */
        .data-table tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: #6c757d; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }

        .footer {
            border-top: 1px solid #dee2e6;
            font-size: 7pt;
            color: #666;
            text-align: center;
            padding-top: 4px;
            margin-top: 6px;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="pdf-header">
        <table>
            <tr>
                <td class="hdr-left">
                    <img src="{{ $emblemSrc }}" alt="Emblem of India">
                </td>
                <td class="hdr-center">
                    <div class="brand-1">Government of India</div>
                    <div class="brand-2">LBSNAA MUSSOORIE</div>
                    <div class="brand-3">Lal Bahadur Shastri National Academy of Administration</div>
                </td>
                <td class="hdr-right">
                    <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo">
                </td>
            </tr>
        </table>
    </div>

    {{-- Title --}}
    <div class="report-title-block">
        <h1 class="report-title">Timetable Session Report</h1>
        <div class="report-date-pill">{{ $filterLine }}</div>
    </div>

    {{-- Meta --}}
    <div class="report-meta">
        <span class="meta-label">Printed on:</span> {{ $printedOn }}<br>
        <span class="meta-label">Total records:</span> {{ $rowCount }}
    </div>

    @php
        $cols = $visibleColumns ?? [];
        $colCount = count($cols);
    @endphp

    @if(empty($rows))
        <p style="font-size:10pt; color:#555;">No records found for the selected filters.</p>
    @else
        <table class="data-table">
            <thead>
            <tr>
                @foreach($cols as $col)
                    <th @if($col['key'] === 'sno') class="text-center" @endif>{{ $col['label'] }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($rows as $index => $row)
                <tr>
                    @foreach($cols as $col)
                        @if($col['key'] === 'sno')
                            <td class="text-center">{{ $index + 1 }}</td>
                        @else
                            <td>{{ $row[$col['key']] ?? '' }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <small>LBSNAA Mussoorie &mdash; Timetable Session Report</small>
    </div>
</body>
</html>
