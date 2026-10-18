@php
    $rows = $rows ?? collect();
    $filterLine = $filterLine ?? '';
    $printedOn = $printedOn ?? now()->format('d-m-Y H:i');
    $reportTitle = $reportTitle ?? 'Stationed Leave Master Report';
    $logo = $logo ?? null;
    // Columns the grid was showing (keys of StationedLeaveMasterExport::COLUMNS).
    $allColumns = \App\Exports\StationedLeaveMasterExport::COLUMNS;
    $columns = !empty($columns) ? $columns : array_keys($allColumns);
    $colClass = [
        'sno' => 'col-sno', 'course' => 'col-course', 'effective_from' => 'col-date',
        'pt_timing' => 'col-timing', 'approval' => 'col-approval', 'faculty_count' => 'col-count', 'status' => 'col-status',
    ];
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
        .col-course { width: 28%; }
        .col-date { width: 13%; text-align: center; }
        .col-timing { width: 13%; text-align: center; }
        .col-approval { width: 15%; text-align: center; }
        .col-count { width: 13%; text-align: center; }
        .col-status { width: 12%; text-align: center; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; color: #fff; font-weight: bold; }
        .badge-active { background: #198754; }
        .badge-inactive { background: #6c757d; }

        .footer { margin-top: 8px; text-align: center; font-size: 7px; color: #666; }
    </style>
</head>
<body>

    {{-- Page numbers are stamped from the controller via the canvas API (App\Traits\StampsPdfPageNumbers); a text/php block here would require isPhpEnabled. --}}

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
                @foreach($columns as $key)
                    <th class="{{ $colClass[$key] }}">{{ $allColumns[$key][0] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    @foreach($columns as $key)
                        @if($key === 'status')
                            @php $isActive = (int) $row->active_inactive === 1; @endphp
                            <td class="col-status"><span class="badge badge-{{ $isActive ? 'active' : 'inactive' }}">{{ $isActive ? 'Active' : 'Inactive' }}</span></td>
                        @else
                            <td class="{{ $colClass[$key] }}">{{ \App\Exports\StationedLeaveMasterExport::cellValue($key, $row, $index + 1) }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align:center;">No stationed leave configuration found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">LBSNAA — {{ $reportTitle }}</div>
</body>
</html>
