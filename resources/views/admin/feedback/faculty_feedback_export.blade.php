<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Faculty Feedback Report - LBSNAA</title>
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
                background: #004a93 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .feedback-table thead th {
                background: #eef4fb !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .rating-header {
                color: #af2910 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .percentage-cell {
                color: #af2910 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .remarks-title {
                background: #af2910 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .meta-info {
                background: #f8f9fa !important;
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

        /* ── Feedback Section ── */
        .feedback-section {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .meta-info {
            text-align: center;
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }

        .meta-info p {
            margin: 3px 0;
            font-size: 10px;
        }

        .meta-info strong {
            color: #004a93;
        }

        .faculty-type-badge {
            font-size: 8px;
            padding: 1px 5px;
            border-radius: 8px;
            background: #e9ecef;
            color: #495057;
            font-weight: 600;
            margin-left: 4px;
        }

        /* ── Feedback Table ── */
        .feedback-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10px;
        }

        .feedback-table thead th {
            background: #eef4fb;
            color: #1f2937;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #d0d7de;
            font-size: 10px;
            font-weight: 600;
        }

        .feedback-table tbody td {
            padding: 5px 8px;
            border: 1px solid #d0d7de;
            font-size: 10px;
            vertical-align: middle;
        }

        .feedback-table tbody th {
            padding: 5px 8px;
            border: 1px solid #d0d7de;
            font-size: 10px;
            vertical-align: middle;
        }

        .rating-header {
            color: #af2910 !important;
            font-weight: 600;
        }

        .percentage-cell {
            font-weight: 700;
            color: #af2910;
        }

        .percentage-row {
            font-weight: 600;
            border-top: 2px solid #d0d7de;
        }

        /* ── Remarks ── */
        .remarks-title {
            background: #af2910;
            color: #fff;
            padding: 5px 10px;
            font-weight: 600;
            font-size: 10px;
            border-radius: 3px 3px 0 0;
        }

        .remarks-list {
            border: 1px solid #d0d7de;
            border-top: 0;
            border-radius: 0 0 3px 3px;
            padding: 6px 8px 6px 22px;
            margin: 0 0 10px 0;
            background: #fff;
            font-size: 9px;
        }

        .remarks-list li {
            margin-bottom: 3px;
            line-height: 1.4;
        }

        /* ── Section Separator ── */
        .section-separator {
            border: none;
            border-top: 1px dashed #bcc5d0;
            margin: 14px 0;
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
        Average Rating – Course / Topic wise
    </div>
</div>

<!-- ── Filter Details ── -->
<div class="report-details-row">
    <span><strong>Course Status:</strong> {{ $filters['course_type'] ?? 'All' }}</span>
    <span><strong>Program:</strong> {{ $filters['program'] ?? 'All Programs' }}</span>
    <span><strong>Faculty:</strong> {{ $filters['faculty_name'] ?? 'All Faculty' }}</span>
    <span><strong>Faculty Type:</strong> {{ $filters['faculty_type'] ?? 'All Types' }}</span>
    <span><strong>Period:</strong> {{ $filters['date_range'] ?? 'All Dates' }}</span>
    <span><strong>Total Records:</strong> {{ count($feedbackData) }}</span>
    <span><strong>Generated:</strong> {{ $export_date }}</span>
</div>

<!-- ── Feedback Data ── -->
@if (count($feedbackData) > 0)
    @foreach ($feedbackData as $index => $data)
        <div class="feedback-section">
            <!-- Meta Info -->
            <div class="meta-info">
                <p><strong>Course:</strong> {{ $data['Program Name'] ?? '' }}
                    @if (isset($data['Course Status']))
                        <span class="faculty-type-badge">{{ $data['Course Status'] }}</span>
                    @endif
                </p>
                <p>
                    <strong>Faculty:</strong> {{ $data['Faculty Name'] ?? '' }}
                    <span class="faculty-type-badge">{{ $data['Faculty Type'] ?? '' }}</span>
                </p>
                <p><strong>Topic:</strong> {{ $data['Topic'] ?? '' }}</p>
                @if (!empty($data['Lecture Date']))
                    <p>
                        <strong>Lecture Date:</strong> {{ $data['Lecture Date'] }}
                        @if (!empty($data['Time']))
                            {{ $data['Time'] }}
                        @endif
                    </p>
                @endif
            </div>

            <!-- Rating Table -->
            <table class="feedback-table">
                <thead>
                    <tr>
                        <th style="width:35%">Rating</th>
                        <th style="width:32.5%">Content</th>
                        <th style="width:32.5%">Presentation</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th class="rating-header">Excellent</th>
                        <td>{{ $data['Content - Excellent'] ?? 0 }}</td>
                        <td>{{ $data['Presentation - Excellent'] ?? 0 }}</td>
                    </tr>
                    <tr>
                        <th class="rating-header">Very Good</th>
                        <td>{{ $data['Content - Very Good'] ?? 0 }}</td>
                        <td>{{ $data['Presentation - Very Good'] ?? 0 }}</td>
                    </tr>
                    <tr>
                        <th class="rating-header">Good</th>
                        <td>{{ $data['Content - Good'] ?? 0 }}</td>
                        <td>{{ $data['Presentation - Good'] ?? 0 }}</td>
                    </tr>
                    <tr>
                        <th class="rating-header">Average</th>
                        <td>{{ $data['Content - Average'] ?? 0 }}</td>
                        <td>{{ $data['Presentation - Average'] ?? 0 }}</td>
                    </tr>
                    <tr>
                        <th class="rating-header">Below Average</th>
                        <td>{{ $data['Content - Below Average'] ?? 0 }}</td>
                        <td>{{ $data['Presentation - Below Average'] ?? 0 }}</td>
                    </tr>
                    <tr class="percentage-row">
                        <th class="rating-header">Percentage</th>
                        <td class="percentage-cell">{{ $data['Content Percentage'] ?? '0.00%' }}</td>
                        <td class="percentage-cell">{{ $data['Presentation Percentage'] ?? '0.00%' }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Remarks -->
            @if (!empty($data['Remarks']))
                @php
                    $remarks = array_filter(explode("\n", $data['Remarks']), function ($r) {
                        return !empty(trim($r));
                    });
                @endphp
                @if (count($remarks) > 0)
                    <div class="remarks-title">Remarks ({{ count($remarks) }})</div>
                    <ol class="remarks-list">
                        @foreach ($remarks as $remark)
                            <li>{{ trim($remark) }}</li>
                        @endforeach
                    </ol>
                @endif
            @endif

            @if (!$loop->last)
                <hr class="section-separator">
            @endif
        </div>
    @endforeach
@else
    <div style="text-align:center; padding:40px; color:#666; background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px; margin-top:20px;">
        <p style="font-size:12px; font-weight:600; color:#004a93; margin:0;">No feedback data found for the selected filters.</p>
    </div>
@endif

<!-- ── Footer ── -->
<div class="report-footer">
    <span class="institution">Lal Bahadur Shastri National Academy of Administration, Mussoorie</span>
    &nbsp;|&nbsp; Faculty Feedback Report &nbsp;|&nbsp; Generated: {{ $export_date }}
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
