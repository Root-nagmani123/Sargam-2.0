<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Timetable Sessions Report — LBSNAA</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        @media print {
            body { margin: 0; padding: 0; background: #fff !important; font-size: 7px; }
            .no-print { display: none !important; }

            .report-header {
                margin-top: 0;
                margin-bottom: 6px;
                padding-bottom: 4px;
                border-bottom: 2px solid #004a93;
            }

            table.data-table thead th {
                background: #004a93 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table.data-table tbody tr:nth-child(even) td {
                background: #f7f9fc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 7px;
            margin: 0;
            padding: 10px;
            color: #222;
            background: #fff;
        }

        .report-header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #004a93;
        }

        .lbsnaa-branding-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .lbsnaa-branding-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .lbsnaa-branding-emblem {
            width: 50px;
            text-align: left;
        }

        .lbsnaa-branding-emblem img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .lbsnaa-branding-lines {
            text-align: center;
            padding: 0 8px;
        }

        .lbsnaa-brand-line-1 {
            font-size: 9px;
            color: #004a93;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .lbsnaa-brand-line-2 {
            font-size: 14px;
            color: #222;
            font-weight: 700;
            margin-top: 2px;
        }

        .lbsnaa-brand-line-3 {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }

        .lbsnaa-branding-logo {
            width: 90px;
            text-align: right;
        }

        .lbsnaa-branding-logo img {
            width: 80px;
            max-height: 44px;
            object-fit: contain;
        }

        .report-title-bar {
            background-color: #004a93;
            color: #fff;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 6px;
            letter-spacing: 0.03em;
        }

        .report-details-row {
            padding: 5px 8px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            margin-top: 6px;
            margin-bottom: 8px;
            font-size: 8px;
            color: #333;
            text-align: left;
        }

        .report-details-row strong {
            color: #004a93;
        }

        .summary-stats {
            margin-bottom: 6px;
            font-size: 9px;
        }

        .summary-stats .stat-pill {
            display: inline-block;
            background: #e8eef6;
            border: 1px solid #c0cde0;
            padding: 2px 8px;
            margin-right: 8px;
            font-size: 8px;
        }

        .summary-stats .stat-pill strong {
            color: #003366;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5px;
            margin-top: 4px;
            page-break-inside: auto;
        }

        table.data-table thead {
            display: table-header-group;
        }

        table.data-table thead th {
            background: #004a93;
            color: #fff;
            font-weight: 600;
            font-size: 6.5px;
            padding: 3px 2px;
            border: 1px solid #003366;
            vertical-align: middle;
        }

        table.data-table tbody td {
            border: 1px solid #dee2e6;
            padding: 2px 2px;
            vertical-align: top;
            word-break: break-word;
        }

        .text-center { text-align: center; }

        .report-footer {
            border-top: 1px solid #004a93;
            font-size: 8px;
            color: #666;
            text-align: center;
            padding-top: 5px;
            margin-top: 8px;
        }

        .report-footer .institution {
            color: #004a93;
            font-weight: 600;
        }

        .print-actions {
            text-align: center;
            margin-bottom: 10px;
            padding: 8px;
        }

        .print-actions button {
            background: #004a93;
            color: #fff;
            border: none;
            padding: 8px 24px;
            font-size: 13px;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 5px;
            font-family: Arial, sans-serif;
        }

        .print-actions button:hover { background: #003366; }
        .print-actions button.btn-secondary { background: #6c757d; }
        .print-actions button.btn-secondary:hover { background: #5a6268; }
    </style>
</head>
<body>

@php
    $isPrint = ($mode ?? '') === 'print';
    $emblemSrc = $isPrint
        ? asset('images/lbsnaa_logo.jpg')
        : public_path('images/lbsnaa_logo.jpg');
    $lbsnaaLogoSrc = $isPrint
        ? asset('admin_assets/images/logos/logo.png')
        : public_path('admin_assets/images/logos/logo.png');
    $rows = $rows ?? [];
    $filter_lines = $filter_lines ?? [];
    $record_count = $record_count ?? count($rows);
@endphp

@if ($isPrint)
<div class="print-actions no-print">
    <button type="button" onclick="window.print()">Print Report</button>
    <button type="button" class="btn-secondary" onclick="window.close()">Close</button>
</div>
@endif

<div class="report-header">
    <table class="lbsnaa-branding-table">
        <tr>
            <td class="lbsnaa-branding-emblem">
                <img src="{{ $emblemSrc }}" alt="Emblem">
            </td>
            <td class="lbsnaa-branding-lines">
                <div class="lbsnaa-brand-line-1">Government of India</div>
                <div class="lbsnaa-brand-line-2">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</div>
                <div class="lbsnaa-brand-line-3">Mussoorie, Uttarakhand</div>
            </td>
            <td class="lbsnaa-branding-logo">
                <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA">
            </td>
        </tr>
    </table>
    <div class="report-title-bar">
        Timetable Sessions Report
    </div>
</div>

<div class="report-details-row">
    @foreach ($filter_lines as $line)
        <div><strong>•</strong> {{ $line }}</div>
    @endforeach
    <div style="margin-top:4px;"><strong>Generated:</strong> {{ $export_date ?? now()->format('d-m-Y H:i') }}</div>
</div>

<div class="summary-stats">
    <span class="stat-pill"><strong>{{ $record_count }}</strong> row(s)</span>
</div>

@if (count($rows) > 0)
<table class="data-table">
    <thead>
        <tr>
            <th class="text-center" style="width:2%">#</th>
            <th style="width:6%">Start</th>
            <th style="width:6%">End</th>
            <th style="width:9%">Topic</th>
            <th style="width:8%">Faculty</th>
            <th style="width:4%">Code</th>
            <th style="width:5%">Type</th>
            <th style="width:8%">Course</th>
            <th style="width:4%">Short</th>
            <th style="width:5%">Prog.</th>
            <th style="width:6%">Groups</th>
            <th style="width:6%">Session</th>
            <th style="width:6%">Venue</th>
            <th style="width:6%">Subject</th>
            <th style="width:7%">Module</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $r)
        <tr>
            <td class="text-center">{{ $r['s_no'] }}</td>
            <td>{{ $r['start_date'] }}</td>
            <td>{{ $r['end_date'] }}</td>
            <td>{{ $r['subject_topic'] }}</td>
            <td>{{ $r['faculty_name'] }}</td>
            <td>{{ $r['faculty_code'] }}</td>
            <td>{{ $r['faculty_type'] }}</td>
            <td>{{ $r['course_name'] }}</td>
            <td>{{ $r['course_short'] }}</td>
            <td>{{ $r['prog_type'] }}</td>
            <td>{{ $r['groups'] }}</td>
            <td>{{ $r['class_session'] }}</td>
            <td>{{ $r['venue'] }}</td>
            <td>{{ $r['subject'] }}</td>
            <td>{{ $r['module'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
    <p style="text-align:center; font-size:11px; padding:24px; color:#666;">No timetable rows match the selected filters.</p>
@endif

<div class="report-footer">
    <small>This is a computer-generated report — No signature required</small><br>
    <small class="institution">Lal Bahadur Shastri National Academy of Administration, Mussoorie</small>
</div>

@if ($isPrint)
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 300);
    });
</script>
@endif

</body>
</html>
