<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Faculty Feedback Average Report - LBSNAA</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        @media print {
            body { margin: 0; padding: 0; background: #fff !important; font-size: 10px; }
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
                background: #004a93 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table.data-table thead th {
                background: #004a93 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .percentage-good { color: #198754 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .percentage-average { color: #b45309 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .percentage-low { color: #dc3545 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            tr:nth-child(even) td {
                background: #f7f9fc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10px;
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

        /* ── Program Title ── */
        .program-title {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            color: #004a93;
            margin-bottom: 8px;
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
            padding: 6px 5px;
            border: 1px solid #003060;
            text-align: left;
            white-space: nowrap;
        }

        table.data-table thead th.text-center { text-align: center; }

        table.data-table tbody td {
            padding: 5px 5px;
            border: 1px solid #dde2ea;
            vertical-align: middle;
        }

        table.data-table tbody tr:nth-child(even) td {
            background: #f7f9fc;
        }

        .text-center { text-align: center; }

        .percentage-good { color: #198754; font-weight: 700; }
        .percentage-average { color: #b45309; font-weight: 700; }
        .percentage-low { color: #dc3545; font-weight: 700; }

        .faculty-name { font-weight: 600; color: #004a93; }

        /* ── Summary Stats ── */
        .summary-stats {
            margin-top: 10px;
            margin-bottom: 6px;
            font-size: 9px;
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
    <button class="btn-secondary" onclick="window.close()">✖ Close</button>
</div>
@endif

<!-- ── LBSNAA Header (mess-report style) ── -->
<div class="report-header">
    <table class="lbsnaa-branding-table">
        <tr>
            <td class="lbsnaa-branding-emblem">
                <img src="{{ $emblemSrc }}" alt="Emblem" class="lbsnaa-header-logo">
            </td>
            <td class="lbsnaa-branding-lines">
                <div class="lbsnaa-brand-line-1">Government of India</div>
                <div class="lbsnaa-brand-line-2">Lal Bahadur Shastri National Academy of Administration</div>
                <div class="lbsnaa-brand-line-3">Mussoorie, Uttarakhand</div>
            </td>
            <td class="lbsnaa-branding-logo">
                <img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA" class="lbsnaa-header-logo-right">
            </td>
        </tr>
    </table>

    <div class="report-title-bar">
        Faculty Feedback Average Report
    </div>
</div>

<!-- ── Filter Details ── -->
<div class="report-details-row">
    <span><strong>Course Status:</strong> {{ ucfirst($courseType ?? 'current') }} Courses</span>
    @if (!empty($currentProgramName))
        <span><strong>Program:</strong> {{ $currentProgramName }}</span>
    @endif
    @if (!empty($currentFaculty) && isset($faculties[$currentFaculty]))
        <span><strong>Faculty:</strong> {{ $faculties[$currentFaculty] }}</span>
    @endif
    @if (!empty($fromDate))
        <span><strong>From:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }}</span>
    @endif
    @if (!empty($toDate))
        <span><strong>To:</strong> {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</span>
    @endif
    <span><strong>Total Records:</strong> {{ $feedbackData->count() }}</span>
    <span><strong>Generated:</strong> {{ now()->format('d-M-Y H:i') }}</span>
</div>

@if (!empty($currentProgramName))
    <div class="program-title">{{ $currentProgramName }}</div>
@endif

<!-- ── Summary Stats ── -->
@if ($feedbackData->isNotEmpty())
    <div class="summary-stats">
        <span class="stat-pill"><strong>{{ $feedbackData->count() }}</strong> Sessions</span>
        <span class="stat-pill">Avg Content: <strong>{{ number_format($feedbackData->avg('content_percentage'), 2) }}%</strong></span>
        <span class="stat-pill">Avg Presentation: <strong>{{ number_format($feedbackData->avg('presentation_percentage'), 2) }}%</strong></span>
        <span class="stat-pill">Total Participants: <strong>{{ $feedbackData->sum('participants') }}</strong></span>
    </div>
@endif

<!-- ── Data Table ── -->
@if ($feedbackData->isEmpty())
    <div style="text-align:center; padding:40px; color:#666; background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px; margin-top:20px;">
        <p style="font-size:12px; font-weight:600; color:#004a93; margin:0;">No feedback data found for the selected filters.</p>
    </div>
@else
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width:35px">Sl.</th>
                <th>Faculty Name</th>
                <th>Topic</th>
                <th>Program</th>
                <th class="text-center">Content (%)</th>
                <th class="text-center">Presentation (%)</th>
                <th class="text-center">Participants</th>
                <th>Session Date</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($feedbackData as $index => $data)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="faculty-name">{{ $data['faculty_name'] }}</td>
                    <td>{{ $data['topic_name'] }}</td>
                    <td>{{ $data['program_name'] }}</td>
                    <td class="text-center {{ $data['content_percentage'] >= 80 ? 'percentage-good' : ($data['content_percentage'] >= 60 ? 'percentage-average' : 'percentage-low') }}">
                        {{ number_format($data['content_percentage'], 2) }}%
                    </td>
                    <td class="text-center {{ $data['presentation_percentage'] >= 80 ? 'percentage-good' : ($data['presentation_percentage'] >= 60 ? 'percentage-average' : 'percentage-low') }}">
                        {{ number_format($data['presentation_percentage'], 2) }}%
                    </td>
                    <td class="text-center">{{ $data['participants'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($data['session_date'])->format('d-M-Y') }}</td>
                    <td>{{ $data['class_session'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<!-- ── Footer ── -->
<div class="report-footer">
    <span class="institution">Lal Bahadur Shastri National Academy of Administration, Mussoorie</span>
    &nbsp;|&nbsp; Faculty Feedback Average Report &nbsp;|&nbsp; Generated: {{ now()->format('d-M-Y H:i') }}
</div>

@if ($isPrint)
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 400);
    });
</script>
@endif

</body>
</html>
