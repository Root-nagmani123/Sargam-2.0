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

    $filters = [];
    $filters[] = 'Status: ' . $statusLabel;
    if (!empty($courseLabel)) {
        $filters[] = 'Course: ' . $courseLabel;
    }
    if (!empty($groupTypeLabel)) {
        $filters[] = 'Group Type: ' . $groupTypeLabel;
    }
    if (!empty($facultyLabel)) {
        $filters[] = 'Faculty: ' . $facultyLabel;
    }
    if (!empty($searchValue)) {
        $filters[] = 'Search: "' . $searchValue . '"';
    }
    $filterLine = count($filters) ? implode('  |  ', $filters) : 'No filters applied';
    $rowCount = $rows->count();
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Course Group Mapping - LBSNAA MUSSOORIE</title>
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

        /* Header */
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
        .brand-1 {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #0b4a7e;
            font-weight: 600;
        }
        .brand-2 {
            font-size: 9.5pt;
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

        /* Report title block */
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

        /* Meta row */
        .report-meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8pt;
            color: #334155;
        }
        .report-meta-table td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }
        .report-meta-table .meta-right { text-align: right; }
        .meta-label { font-weight: 700; color: #0f172a; }

        /* Data table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            padding: 4px 6px;
            border: 1px solid #d1d5db;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
        }
        .data-table thead th {
            background: #0b4a7e;
            color: #fff;
            font-weight: 600;
            font-size: 7.5pt;
            text-transform: uppercase;
            text-align: left;
        }
        .data-table thead th.text-center { text-align: center; }

        .text-center { text-align: center; }

        .status-active {
            color: #28a745;
            font-weight: 700;
        }
        .status-inactive {
            color: #b42318;
            font-weight: 700;
        }

        .empty-row td {
            text-align: center;
            color: #777;
            padding: 16px;
            font-style: italic;
        }

        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
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
        <h1 class="report-title">Course Group Mapping</h1>
        <div class="report-date-pill">{{ $filterLine }}</div>
    </div>

    <table class="report-meta-table">
        <tr>
            <td>
                <span class="meta-label">Printed on:</span> {{ $printedOn }}
            </td>
            <td class="meta-right">
                <span class="meta-label">Total records:</span> {{ $rowCount }}
            </td>
        </tr>
    </table>

    @php
        // Fallback to a default column set if none was passed (keeps the view safe
        // when rendered outside the controller's export flow).
        $columns = $columns ?? [
            ['title' => 'S.No.',        'align' => 'center', 'width' => 5],
            ['title' => 'Course Name',  'align' => 'left',   'width' => 24],
            ['title' => 'Group Type',   'align' => 'left',   'width' => 14],
            ['title' => 'Group Name',   'align' => 'left',   'width' => 16],
            ['title' => 'Faculty',      'align' => 'left',   'width' => 20],
            ['title' => 'Student Name', 'align' => 'center', 'width' => 10],
            ['title' => 'Status',       'align' => 'center', 'width' => 11],
        ];
        $columns = array_values($columns);
        $colCount = count($columns);
    @endphp
    <table class="data-table">
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th style="width: {{ $column['width'] ?? '' }}%;" class="{{ ($column['align'] ?? 'left') === 'center' ? 'text-center' : '' }}">
                        {{ $column['title'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $column)
                        @php
                            $value = isset($column['value']) ? ($column['value'])($row, $loop->parent->iteration) : '';
                            $isStatus = ($column['title'] ?? '') === 'Status';
                            $isActive = (int) ($row->active_inactive ?? 0) === 1;
                        @endphp
                        <td class="{{ ($column['align'] ?? 'left') === 'center' ? 'text-center' : '' }}">
                            @if($isStatus)
                                <span class="{{ $isActive ? 'status-active' : 'status-inactive' }}">{{ $value }}</span>
                            @else
                                {{ $value !== '' && $value !== null ? $value : '-' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ $colCount }}">No group mappings found for the applied filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
