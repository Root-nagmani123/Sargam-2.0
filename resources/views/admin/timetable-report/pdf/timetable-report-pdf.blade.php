@php
    $printedOn = now()->format('d/m/Y') . ' ' . now()->format('g:i:s A');

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
            margin: 6mm;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 7pt;
            margin: 0;
            padding: 0;
            color: #222;
            background: #fff;
        }

        .lbsnaa-header-wrap {
            border-bottom: 2px solid #004a93;
            margin-bottom: 10px;
            padding: 2px 0 8px;
        }
        .branding-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .branding-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }
        .branding-logo-left {
            width: 42px;
        }
        .branding-text {
            text-align: left;
            padding: 0 10px 0 2px;
            line-height: 1.25;
        }
        .branding-logo-right {
            width: 200px;
            text-align: right;
        }
        .lbsnaa-brand-line-1 {
            font-size: 8pt;
            color: #004a93;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .lbsnaa-brand-line-2 {
            font-size: 13pt;
            color: #222;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .lbsnaa-brand-line-3 {
            font-size: 10pt;
            color: #555;
            margin-top: 2px;
        }
        .header-img-left {
            width: 34px;
            height: 34px;
        }
        .header-img-right {
            width: 165px;
            height: auto;
        }

        .report-header-block {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title-center {
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0 0 6px;
            color: #212529;
        }
        .report-date-bar {
            background: #004a93;
            color: #fff;
            padding: 6px 12px;
            text-align: center;
            font-weight: 600;
            font-size: 9pt;
            display: inline-block;
        }

        .report-meta-print {
            font-size: 7pt;
            margin: 4px 0 6px;
            line-height: 1.35;
            text-align: left;
        }
        .report-meta-print .meta-line {
            margin-bottom: 3px;
            word-wrap: break-word;
        }

        table.timetable-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
            margin-bottom: 8px;
            table-layout: fixed;
        }
        table.timetable-data th,
        table.timetable-data td {
            padding: 2px 3px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
        }
        table.timetable-data thead th {
            background: #d3d6d9;
            font-weight: 600;
            text-align: left;
            font-size: 6.5pt;
        }
        table.timetable-data thead th.text-center {
            text-align: center;
        }
        table.timetable-data .text-center {
            text-align: center;
        }
        table.timetable-data tbody tr:nth-child(even) td {
            background: #fafbfc;
        }

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

<div class="lbsnaa-header-wrap">
    <table class="branding-table">
        <tr>
            <td class="branding-logo-left">
                <img src="{{ $emblemSrc }}" alt="Emblem of India" class="header-img-left">
            </td>
            <td class="branding-text">
                <div class="lbsnaa-brand-line-1">Government of India</div>
                <div class="lbsnaa-brand-line-2">LBSNAA MUSSOORIE</div>
                <div class="lbsnaa-brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
            </td>
            <td class="branding-logo-right">
                <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo" class="header-img-right">
            </td>
        </tr>
    </table>
</div>

<div class="report-header-block">
    <h1 class="report-title-center">Timetable Session Report</h1>
    <div class="report-date-bar">{{ $filterLine }}</div>
</div>

<div class="report-meta-print">
    <div class="meta-line"><strong>Printed on:</strong> {{ $printedOn }}</div>
    <div class="meta-line"><strong>Total records:</strong> {{ $rowCount }}</div>
</div>

@php
    $cols = $visibleColumns ?? [];
    $colCount = count($cols);
@endphp

@if(empty($rows))
    <p style="font-size:10pt; color:#555;">No records found for the selected filters.</p>
@else
    <table class="timetable-data">
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
