@php
    $printedOn = $generatedAt ?? now()->format('d M Y, h:i A');

    $emblemSrc = $emblemSrc ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    foreach ([
        public_path('admin_assets/images/logos/ashoka.png'),
        public_path('images/ashoka.png'),
    ] as $emblemPath) {
        if (is_file($emblemPath) && is_readable($emblemPath)) {
            $raw = @file_get_contents($emblemPath);
            if ($raw !== false) {
                $emblemSrc = 'data:image/png;base64,' . base64_encode($raw);
                break;
            }
        }
    }

    $lbsnaaLogoSrc = $lbsnaaLogoSrc ?? 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
    foreach ([
        public_path('images/lbsnaa_logo.jpg'),
        public_path('images/lbsnaa_logo.png'),
        public_path('admin_assets/images/logos/logo_new.png'),
        public_path('admin_assets/images/logos/logo.png'),
        public_path('admin_assets/images/logos/logo.svg'),
    ] as $logoPath) {
        if (is_file($logoPath) && is_readable($logoPath)) {
            $raw = @file_get_contents($logoPath);
            if ($raw !== false) {
                $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'svg' => 'image/svg+xml',
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    default => 'image/png',
                };
                $lbsnaaLogoSrc = 'data:' . $mime . ';base64,' . base64_encode($raw);
                break;
            }
        }
    }

    $courseLabel = $courseLabel ?? 'All Courses';
    $searchMeta = !empty($searchLabel) ? 'Search: "' . $searchLabel . '"' : '';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Who's Who - LBSNAA MUSSOORIE</title>
    <style>
        @page { margin: 12mm 10mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #1a1a2e;
            margin: 0;
            padding: 0;
            line-height: 1.35;
            background: #fff;
        }

        .pdf-header {
            border-bottom: 2.5px solid #0b4a7e;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .pdf-header table { width: 100%; border-collapse: collapse; }
        .pdf-header td { border: 0; padding: 0; vertical-align: middle; }
        .pdf-header .hdr-left { width: 50px; }
        .pdf-header .hdr-left img { width: 42px; height: 42px; }
        .pdf-header .hdr-center { padding-left: 10px; }
        .pdf-header .hdr-right { width: 50px; text-align: right; }
        .pdf-header .hdr-right img { width: 42px; height: 42px; }
        .brand-1 {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #0b4a7e;
            font-weight: 600;
        }
        .brand-2 {
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #111;
            margin-top: 2px;
        }
        .brand-3 {
            font-size: 7.5pt;
            color: #555;
            margin-top: 2px;
        }

        .report-title-block {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title {
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
            margin: 0 0 6px;
        }
        .report-meta {
            font-size: 8pt;
            color: #555;
            margin-bottom: 3px;
        }
        .report-course {
            font-size: 12pt;
            font-weight: 700;
            color: #0b4a7e;
            margin-bottom: 4px;
        }

        .card {
            border: 1px solid #d4a574;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .card-header {
            background: #f4c896;
            padding: 7px 10px;
            border-bottom: 1px solid #d4a574;
        }
        .card-header .row1 {
            font-size: 9.5pt;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .card-header .row2 {
            font-size: 8.5pt;
        }
        .card-body {
            padding: 8px 10px;
        }
        .card-body table {
            width: 100%;
            border-collapse: collapse;
        }
        .card-body td {
            vertical-align: top;
            padding: 0;
        }
        .photo-cell {
            width: 78px;
            text-align: center;
            padding-right: 10px;
        }
        .photo {
            width: 72px;
            height: 88px;
            border: 2px solid #e8dcc8;
            object-fit: cover;
        }
        .photo-placeholder {
            width: 72px;
            height: 88px;
            border: 2px solid #e8dcc8;
            background: #f5f5f5;
            text-align: center;
            line-height: 88px;
            font-size: 18pt;
            color: #999;
        }
        .index-no {
            margin-top: 4px;
            font-weight: bold;
            font-size: 9pt;
        }
        .fields-table {
            width: 100%;
            border-collapse: collapse;
        }
        .fields-table td {
            padding: 2px 4px 2px 0;
            font-size: 8.5pt;
            vertical-align: top;
        }
        .fields-table .label {
            font-weight: bold;
            width: 34%;
            color: #5c4a3a;
        }
        .address {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid #ebe4d8;
            font-size: 8.5pt;
        }
        .address strong {
            color: #5c4a3a;
        }
    </style>
</head>
<body>

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

    <div class="report-title-block">
        <h1 class="report-title">Who's Who</h1>
        <div class="report-course">{{ $courseLabel }}</div>
        @if($searchMeta)
            <div class="report-meta">{{ $searchMeta }}</div>
        @endif
        <div class="report-meta">Generated: {{ $printedOn }} &nbsp;|&nbsp; Total: {{ count($students) }}</div>
    </div>

    @foreach($students as $index => $student)
        <div class="card">
            <div class="card-header">
                <div class="row1">
                    {{ $student['name'] }}
                    &nbsp;&nbsp; Rank: {{ $student['rank'] }}
                    &nbsp;&nbsp; Cadre: {{ $student['cadre'] }}
                    &nbsp;&nbsp; Code: {{ $student['code'] }}
                </div>
                <div class="row2">
                    Counsellor Name: {{ $student['counsellor'] }}
                    &nbsp;&nbsp; House: {{ $student['house'] }}
                </div>
            </div>
            <div class="card-body">
                <table>
                    <tr>
                        <td class="photo-cell">
                            @if(!empty($student['image_src']))
                                <img src="{{ $student['image_src'] }}" class="photo" alt="">
                            @else
                                <div class="photo-placeholder">{{ strtoupper(substr($student['name'] ?? 'S', 0, 1)) }}</div>
                            @endif
                            <div class="index-no">{{ $index + 1 }}</div>
                        </td>
                        <td>
                            <table class="fields-table">
                                <tr>
                                    <td class="label">Contact No:</td>
                                    <td>{{ $student['contact'] }}</td>
                                    <td class="label">Room No:</td>
                                    <td>{{ $student['room'] }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Date of Birth:</td>
                                    <td>{{ $student['dob'] }}</td>
                                    <td class="label">Email:</td>
                                    <td>{{ $student['email'] }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Domicile State:</td>
                                    <td>{{ $student['domicile'] }}</td>
                                    <td class="label">District:</td>
                                    <td>{{ $student['district'] }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Highest Stream:</td>
                                    <td>{{ $student['stream'] }}</td>
                                    <td class="label">Category:</td>
                                    <td>{{ $student['category'] }}</td>
                                </tr>
                            </table>
                            <div class="address">
                                <strong>Address:</strong> {{ $student['address'] }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach
</body>
</html>
