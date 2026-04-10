<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Faculty Feedback Database — LBSNAA</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        @media print {
            body { margin: 0; padding: 0; background: #fff !important; font-size: 8px; }
            .no-print { display: none !important; }

            .report-header {
                margin-top: 0;
                margin-bottom: 8px;
                padding-bottom: 6px;
                border-bottom: 2px solid #2c3e50;
            }

            table.data-table thead th {
                background: #2c3e50 !important;
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
            font-size: 9px;
            margin: 0;
            padding: 12px;
            color: #222;
            background: #fff;
        }

        .report-header {
            text-align: center;
            margin-bottom: 10px;
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
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 6px;
            letter-spacing: 0.03em;
        }

        .report-details-row {
            padding: 6px 8px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            margin-top: 8px;
            margin-bottom: 10px;
            font-size: 9px;
            color: #333;
        }

        .report-details-row span {
            display: inline-block;
            margin-right: 16px;
        }

        .report-details-row strong {
            color: #004a93;
        }

        .summary-stats {
            margin-bottom: 8px;
            font-size: 10px;
        }

        .summary-stats .stat-pill {
            display: inline-block;
            background: #e8eef6;
            border: 1px solid #c0cde0;
            padding: 2px 10px;
            margin-right: 10px;
            font-size: 9px;
        }

        .summary-stats .stat-pill strong {
            color: #003366;
            font-size: 10px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
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
            font-size: 8px;
            padding: 4px 3px;
            border: 1px solid #003060;
            text-align: left;
        }

        table.data-table thead th.text-center { text-align: center; }

        table.data-table tbody td {
            padding: 3px 3px;
            border: 1px solid #dde2ea;
            vertical-align: top;
            word-break: break-word;
        }

        table.data-table tbody tr:nth-child(even) td {
            background: #f7f9fc;
        }

        .text-center { text-align: center; }

        .report-footer {
            border-top: 1px solid #004a93;
            font-size: 8px;
            color: #666;
            text-align: center;
            padding-top: 5px;
            margin-top: 10px;
        }

        .report-footer .institution {
            color: #004a93;
            font-weight: 600;
        }

        .print-actions {
            text-align: center;
            margin-bottom: 12px;
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
    $filters = $filters ?? [];
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
        Faculty Feedback Database Report
    </div>
</div>

<div class="report-details-row">
    <span><strong>Program:</strong> {{ $filters['program'] ?? '—' }}</span>
    <span><strong>Scope:</strong> {{ $filters['scope'] ?? 'All records' }}</span>
    <span><strong>Generated:</strong> {{ $export_date ?? now()->format('d-m-Y H:i') }}</span>
</div>

<div class="summary-stats">
    <span class="stat-pill"><strong>{{ $record_count }}</strong> session row(s)</span>
</div>

@if (count($rows) > 0)
<table class="data-table">
    <thead>
        <tr>
            <th class="text-center" style="width:3%">#</th>
            <th style="width:11%">Faculty</th>
            <th style="width:11%">Course</th>
            <th style="width:14%">Faculty address</th>
            <th style="width:14%">Topic</th>
            <th class="text-center" style="width:6%">Content %</th>
            <th class="text-center" style="width:6%">Pres. %</th>
            <th class="text-center" style="width:6%">Participants</th>
            <th class="text-center" style="width:7%">Session date</th>
            <th style="width:22%">Comments</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $r)
        <tr>
            <td class="text-center">{{ $r['s_no'] }}</td>
            <td>{{ $r['faculty_name'] }}</td>
            <td>{{ $r['course_name'] }}</td>
            <td>{!! nl2br(e($r['faculty_address'])) !!}</td>
            <td>{{ $r['topic'] }}</td>
            <td class="text-center">{{ $r['content_pct'] }}</td>
            <td class="text-center">{{ $r['presentation_pct'] }}</td>
            <td class="text-center">{{ $r['participants'] }}</td>
            <td class="text-center">{{ $r['session_date'] }}</td>
            <td>{!! nl2br(e($r['comments'])) !!}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
    <p style="text-align:center; font-size:12px; padding:30px; color:#666;">No feedback records found for the selected program and filters.</p>
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
