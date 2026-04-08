<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Pending Feedback Report - LBSNAA</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        @media print {
            body { margin: 0; padding: 0; background: #fff !important; font-size: 11px; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }

            .report-header {
                margin-top: 0;
                margin-bottom: 10px;
                padding-bottom: 6px;
                border-bottom: 2px solid #2c3e50;
            }

            .lbsnaa-header-logo { width: 34px; height: 34px; }
            .lbsnaa-header-logo-right { width: 80px; max-height: 40px; }

            .report-title-bar {
                background: #2c3e50 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table.data-table thead th {
                background: #2c3e50 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .student-row td {
                background: #e8eef6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .badge-given, .badge-not-given {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 12px;
            color: #222;
            background: #fff;
        }

        /* ── Mess-style LBSNAA Header ── */
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

        /* ── Filter / Meta Row ── */
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

        /* ── Summary Stats ── */
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

        /* ── Data Table ── */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-top: 4px;
            page-break-inside: auto;
        }

        table.data-table tr {
            page-break-inside: avoid;
        }

        table.data-table thead {
            display: table-header-group;
        }

        table.data-table thead th {
            background: #004a93;
            color: #fff;
            font-weight: 600;
            font-size: 9px;
            padding: 5px 4px;
            border: 1px solid #003060;
            text-align: left;
        }

        table.data-table thead th.text-center { text-align: center; }

        table.data-table tbody td {
            padding: 4px 4px;
            border: 1px solid #dde2ea;
            vertical-align: middle;
        }

        table.data-table tbody tr:nth-child(even) td {
            background: #f7f9fc;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }

        .badge-given {
            background: #198754;
            color: #fff;
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 8px;
            font-weight: 600;
        }

        .badge-not-given {
            background: #dc3545;
            color: #fff;
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 8px;
            font-weight: 600;
        }

        .student-row td {
            background: #e8eef6 !important;
            font-weight: 600;
            font-size: 9px;
        }

        .session-row td {
            font-size: 8px;
            color: #444;
        }

        .session-row td.session-indent {
            padding-left: 14px !important;
        }

        /* ── Footer ── */
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

        /* ── Print Actions ── */
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
@endphp

@if ($isPrint)
<div class="print-actions no-print">
    <button onclick="window.print()">🖨️ Print Report</button>
    <button class="btn-secondary" onclick="window.close()">✕ Close</button>
</div>
@endif

<!-- ── Mess-style Report Header ── -->
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
        Pending Student Feedback Report
    </div>
</div>

<!-- ── Filter Details ── -->
<div class="report-details-row">
    <span><strong>Course:</strong> {{ $filters['course'] ?? 'All Courses' }}</span>
    <span><strong>Session:</strong> {{ $filters['session'] ?? 'All Sessions' }}</span>
    <span><strong>Period:</strong> {{ $filters['from_date'] ?? 'All' }} — {{ $filters['to_date'] ?? 'All' }}</span>
    <span><strong>Generated:</strong> {{ $export_date ?? now()->format('d-m-Y H:i:s') }}</span>
</div>

<!-- ── Summary ── -->
<div class="summary-stats">
    @php
        $totalGiven = 0;
        $totalNotGiven = 0;
        foreach ($students ?? [] as $student) {
            $totalGiven += $student['feedback_given'];
            $totalNotGiven += $student['feedback_not_given'];
        }
    @endphp
    <span class="stat-pill"><strong>{{ count($students ?? []) }}</strong> Students</span>
    <span class="stat-pill" style="border-color:#198754;"><strong style="color:#198754;">{{ $totalGiven }}</strong> Given</span>
    <span class="stat-pill" style="border-color:#dc3545;"><strong style="color:#dc3545;">{{ $totalNotGiven }}</strong> Not Given</span>
</div>

@if (count($students ?? []) > 0)
<table class="data-table">
    <thead>
        <tr>
            <th class="text-center" width="5%">#</th>
            <th width="22%">Student Name</th>
            <th class="text-center" width="8%">Given</th>
            <th class="text-center" width="8%">Not Given</th>
            <th width="25%">Session Name</th>
            <th class="text-center" width="10%">Date</th>
            <th class="text-center" width="10%">Time</th>
            <th class="text-center" width="12%">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($students as $index => $student)
            @php $sessionCount = count($student['sessions']); @endphp

            {{-- Student summary row --}}
            <tr class="student-row">
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    {{ $student['student_name'] }}
                    @if (!empty($student['email']))
                        <br><span style="font-size:7px; color:#666; font-weight:normal;">{{ $student['email'] }}</span>
                    @endif
                </td>
                <td class="text-center"><span class="badge-given">{{ $student['feedback_given'] }}</span></td>
                <td class="text-center"><span class="badge-not-given">{{ $student['feedback_not_given'] }}</span></td>
                <td colspan="4" style="font-size:8px; color:#555; font-weight:normal;">
                    {{ $sessionCount }} session(s) attended
                </td>
            </tr>

            {{-- Session detail rows --}}
            @foreach ($student['sessions'] as $session)
            <tr class="session-row">
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="session-indent">{{ $session['session_name'] ?? '—' }}</td>
                <td class="text-center">{{ $session['date'] ?? '—' }}</td>
                <td class="text-center">{{ $session['time'] ?? '—' }}</td>
                <td class="text-center">
                    @if ($session['feedback_status'] === 'given')
                        <span class="badge-given">Given</span>
                    @else
                        <span class="badge-not-given">Not Given</span>
                    @endif
                </td>
            </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
@else
    <p style="text-align:center; font-size:12px; padding:30px; color:#666;">No pending feedback records found for the selected filters.</p>
@endif

<!-- ── Footer ── -->
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
