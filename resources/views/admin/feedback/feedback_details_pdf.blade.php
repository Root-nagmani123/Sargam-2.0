<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Faculty Feedback Detailed Report</title>
    <style>
        @page {
            margin: 15px 10px;
            header: html_header;
            footer: html_footer;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #004a93;
            padding-bottom: 5px;
        }

        .title {
            color: #004a93;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .subtitle {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .timestamp {
            font-size: 8px;
            color: #666;
            font-style: italic;
        }

        .filters {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 6px;
            margin-bottom: 8px;
            font-size: 7px;
            border-radius: 3px;
        }

        .filters table {
            width: 100%;
            border-collapse: collapse;
        }

        .filters td {
            padding: 1px 3px;
            vertical-align: top;
        }

        .filter-label {
            font-weight: bold;
            color: #004a93;
            white-space: nowrap;
            width: 15%;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            margin-top: 5px;
        }

        .main-table th {
            background-color: #004a93;
            color: white;
            font-weight: bold;
            padding: 4px 2px;
            text-align: center;
            border: 1px solid #004a93;
            vertical-align: middle;
        }

        .main-table td {
            padding: 3px 2px;
            border: 1px solid #ddd;
            vertical-align: top;
            word-wrap: break-word;
        }

        .main-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .main-table tr:hover {
            background-color: #f5f5f5;
        }

        .sno {
            text-align: center;
            width: 3%;
            font-weight: bold;
        }

        .course-name {
            width: 10%;
        }

        .course-status {
            width: 6%;
            text-align: center;
        }

        .faculty-name {
            width: 10%;
        }

        .faculty-type {
            width: 6%;
            text-align: center;
        }

        .topic {
            width: 12%;
        }

        .session-date {
            width: 6%;
            text-align: center;
        }

        .session-time {
            width: 8%;
            text-align: center;
        }

        .ot-name {
            width: 10%;
        }

        .ot-code {
            width: 6%;
            text-align: center;
        }

        .content-rating,
        .presentation-rating {
            width: 5%;
            text-align: center;
            font-weight: bold;
        }

        .remarks {
            width: 15%;
        }

        .feedback-date {
            width: 8%;
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 1px 4px;
            font-size: 7px;
            border-radius: 8px;
            text-align: center;
            min-width: 40px;
        }

        .badge-current {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .badge-archived {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        .badge-internal {
            background-color: #cfe2ff;
            color: #084298;
            border: 1px solid #b6d4fe;
        }

        .badge-guest {
            background-color: #fff3cd;
            color: #664d03;
            border: 1px solid #ffecb5;
        }

        .rating-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
        }

        /* Column-specific styles */
        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        /* Ensure table fits on page */
        table {
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        /* Compact row height */
        tr {
            height: 20px;
        }

        /* Page number in footer */
        .page-number {
            text-align: right;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>

<body>
    <!-- Header for each page -->
    <htmlpageheader name="header">
        <div class="header">
            <div class="title">Faculty Feedback Detailed Report - All Individual Responses</div>
            <div class="subtitle">Sargam | Lal Bahadur Shastri Institute of Management</div>
            <div class="timestamp">Report Generated: {{ $export_date }}</div>
        </div>

        <div class="filters">
            <table>
                <tr>
                    <td class="filter-label">Course Status:</td>
                    <td width="25%">{{ $filters['course_status'] }}</td>
                    <td class="filter-label">Program:</td>
                    <td width="25%">{{ $filters['program'] }}</td>
                    <td class="filter-label">Total Records:</td>
                    <td width="10%">{{ $filters['total_records'] }}</td>
                </tr>
                <tr>
                    <td class="filter-label">Faculty Name:</td>
                    <td>{{ $filters['faculty_name'] }}</td>
                    <td class="filter-label">Faculty Type:</td>
                    <td>{{ $filters['faculty_type'] }}</td>
                    <td class="filter-label">Date Range:</td>
                    <td>{{ $filters['date_range'] }}</td>
                </tr>
            </table>
        </div>
    </htmlpageheader>

    <!-- Content -->
    <table class="main-table">
        <thead>
            <tr>
                <th class="sno">S.No.</th>
                <th class="course-name">Course Name</th>
                {{-- <th class="course-status">Course Status</th> --}}
                <th class="faculty-name">Faculty Name</th>
                {{-- <th class="faculty-type">Faculty Type</th> --}}
                <th class="ot-name">OT Name</th>
                <th class="topic">Topic</th>
                <th class="ot-code">OT Code</th>
                <th class="content-rating">Content</th>
                <th class="presentation-rating">Presentation</th>
                <th class="remarks">Remarks</th>
                <th class="session-date">Session Date</th>
                <th class="session-time">Session Time</th>
                <th class="feedback-date">Feedback Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $item)
                <tr>
                    <td class="sno text-center">{{ $item['S.No.'] }}</td>
                    <td class="course-name">{{ $item['Course Name'] }}</td>
                    <td class="faculty-name">{{ $item['Faculty Name'] }}</td>
                    <td class="ot-name">{{ $item['OT Name'] }}</td>
                    <td class="topic">{{ $item['Topic'] }}</td>
                    <td class="ot-code text-center">{{ $item['OT Code'] }}</td>
                    <td class="content-rating text-center">
                        @php
                            $contentRating = $item['Content Rating'];
                            $bgColor = isset($rating_colors[$contentRating])
                                ? $rating_colors[$contentRating]
                                : '#ffffff';
                            $textColor = $contentRating == '3' ? '#000000' : '#ffffff';
                        @endphp
                        <span class="rating-badge"
                            style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
                            {{ $contentRating }}
                        </span>
                    </td>
                    <td class="presentation-rating text-center">
                        @php
                            $presentationRating = $item['Presentation Rating'];
                            $bgColor = isset($rating_colors[$presentationRating])
                                ? $rating_colors[$presentationRating]
                                : '#ffffff';
                            $textColor = $presentationRating == '3' ? '#000000' : '#ffffff';
                        @endphp
                        <span class="rating-badge"
                            style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
                            {{ $presentationRating }}
                        </span>
                    </td>
                    <td class="remarks">{{ $item['Remarks'] }}</td>
                    <td class="session-date text-center">{{ $item['Session Date'] }}</td>
                    <td class="session-time text-center">{{ $item['Session Time'] }}</td>
                    <td class="feedback-date text-center">{{ $item['Feedback Date'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Footer for each page -->
    <htmlpagefooter name="footer">
        <div style="text-align: center; font-size: 8px; color: #666; padding-top: 5px; border-top: 1px solid #ddd;">
            Confidential - For Internal Use Only | Generated by Sargam Faculty Feedback System
            <div class="page-number">Page {PAGENO} of {nbpg}</div>
        </div>
    </htmlpagefooter>
</body>

</html>
