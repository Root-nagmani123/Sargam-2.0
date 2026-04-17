<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Faculty Feedback with Comments — All Details | LBSNAA</title>
    <style>
        @page {
            margin: 10mm 8mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 7px;
            color: #212529;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-header {
            width: 100%;
            border-bottom: 3px solid #004a93;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .print-header table {
            width: 100%;
            border-collapse: collapse;
        }

        .print-header img {
            height: 42px;
            width: auto;
            object-fit: contain;
            display: block;
        }

        .header-text .line1 {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #004a93;
            font-weight: 700;
            margin: 0;
            padding: 0;
        }

        .header-text .line2 {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1a1a1a;
            margin: 2px 0 0;
            padding: 0;
        }

        .header-text .line3 {
            font-size: 8px;
            color: #555;
            margin: 2px 0 0;
            padding: 0;
        }

        .report-title-block {
            text-align: center;
            margin-bottom: 8px;
        }

        .report-title-block h1 {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 0;
            color: #1a1a1a;
        }

        .report-meta {
            font-size: 7px;
            line-height: 1.5;
            margin: 0 0 10px;
            color: #333;
            text-align: center;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5px;
            margin-top: 4px;
        }

        .main-table th {
            background-color: #004a93;
            color: #fff;
            font-weight: 700;
            padding: 4px 2px;
            text-align: center;
            border: 1px solid #004a93;
            vertical-align: middle;
        }

        .main-table th.text-left {
            text-align: left;
        }

        .main-table td {
            padding: 3px 2px;
            border: 1px solid #bbb;
            vertical-align: top;
            word-wrap: break-word;
        }

        .main-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .text-center {
            text-align: center;
        }

        .rating-badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: 700;
            min-width: 16px;
            text-align: center;
            font-size: 6.5px;
        }

        .page-footer {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #dee2e6;
            font-size: 7px;
            color: #666;
            text-align: center;
        }

        table {
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    <div class="print-header">
        <table>
            <tr>
                <td style="width: 52px; vertical-align: middle;">
                    @if (!empty($emblem_src))
                        <img src="{{ $emblem_src }}" alt="Emblem">
                    @endif
                </td>
                <td class="header-text" style="vertical-align: middle; padding: 0 8px;">
                    <p class="line1">Government of India</p>
                    <p class="line2">OFFICER'S MESS LBSNAA MUSSOORIE</p>
                    <p class="line3">Lal Bahadur Shastri National Academy of Administration</p>
                </td>
                <td style="width: 52px; vertical-align: middle; text-align: right;">
                    @if (!empty($logo_src))
                        <img src="{{ $logo_src }}" alt="LBSNAA Logo">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="report-title-block">
        <h1>Faculty Feedback with Comments — All Details</h1>
    </div>

    <div class="report-meta">
        <strong>Program:</strong> {{ $filters['program'] }}
        &nbsp;|&nbsp; <strong>Course status:</strong> {{ $filters['course_status'] }}
        &nbsp;|&nbsp; <strong>Dates:</strong> {{ $filters['date_range'] }}
        &nbsp;|&nbsp; <strong>Faculty:</strong> {{ $filters['faculty_name'] }}
        &nbsp;|&nbsp; <strong>Faculty type:</strong> {{ $filters['faculty_type'] }}
        &nbsp;|&nbsp; <strong>Total records:</strong> {{ $filters['total_records'] }}
        &nbsp;|&nbsp; <strong>Printed:</strong> {{ $export_date }}
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 2.5%;">#</th>
                <th class="text-left" style="width: 9%;">Course</th>
                <th style="width: 4%;">Status</th>
                <th class="text-left" style="width: 8%;">Faculty</th>
                <th style="width: 4%;">Type</th>
                <th class="text-left" style="width: 9%;">Topic</th>
                <th class="text-left" style="width: 6%;">Session Dt.</th>
                <th class="text-left" style="width: 7%;">Session Time</th>
                <th class="text-left" style="width: 8%;">OT Name</th>
                <th style="width: 4%;">OT Code</th>
                <th style="width: 3.5%;">Cont.</th>
                <th style="width: 3.5%;">Pres.</th>
                <th class="text-left" style="width: 14%;">Remarks</th>
                <th style="width: 7%;">FB Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $item)
                <tr>
                    <td class="text-center">{{ $item['S.No.'] }}</td>
                    <td>{{ $item['Course Name'] }}</td>
                    <td class="text-center">{{ $item['Course Status'] }}</td>
                    <td>{{ $item['Faculty Name'] }}</td>
                    <td class="text-center">{{ $item['Faculty Type'] }}</td>
                    <td>{{ $item['Topic'] }}</td>
                    <td class="text-center">{{ $item['Session Date'] }}</td>
                    <td class="text-center">{{ $item['Session Time'] }}</td>
                    <td>{{ $item['OT Name'] }}</td>
                    <td class="text-center">{{ $item['OT Code'] }}</td>
                    <td class="text-center">
                        @php
                            $contentRating = (string) ($item['Content Rating'] ?? '');
                            $bgColor = $rating_colors[$contentRating] ?? '#e9ecef';
                            $textColor = $contentRating === '3' ? '#000000' : '#ffffff';
                        @endphp
                        <span class="rating-badge"
                            style="background-color: {{ $bgColor }}; color: {{ $textColor }};">{{ $contentRating }}</span>
                    </td>
                    <td class="text-center">
                        @php
                            $presentationRating = (string) ($item['Presentation Rating'] ?? '');
                            $bgColor2 = $rating_colors[$presentationRating] ?? '#e9ecef';
                            $textColor2 = $presentationRating === '3' ? '#000000' : '#ffffff';
                        @endphp
                        <span class="rating-badge"
                            style="background-color: {{ $bgColor2 }}; color: {{ $textColor2 }};">{{ $presentationRating }}</span>
                    </td>
                    <td>{{ $item['Remarks'] }}</td>
                    <td class="text-center">{{ $item['Feedback Date'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-footer">
        Confidential — For internal use only | LBSNAA Mussoorie | Sargam Faculty Feedback
    </div>
</body>

</html>
