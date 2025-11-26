{{-- 


<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Student Enrollment Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .header-left {
            text-align: left;
        }

        .header-right {
            text-align: right;
        }

        .report-title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .filter-info {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #f2f2f2;
            font-weight: bold;
        }

        .course-name {
            font-weight: bold;
            font-size: 14px;
            color: #333;
        }

        .total-count {
            font-weight: bold;
            font-size: 14px;
            color: #333;
        }
    </style>
</head>

<body>
    <!-- Report Title -->
    <div class="report-title">Student Enrollment Report</div>

    <!-- Header with Course Name and Total Count -->
    <div class="header">
        <div class="header-left">
            @if($courseName)
                <div class="course-name">Course: {{ $courseName }}</div>
            @else
                <div class="course-name">All Courses</div>
            @endif
            
            @if($status !== null && $status !== '')
                <div class="filter-info">Status: {{ $status == 1 ? 'Active' : 'Inactive' }}</div>
            @else
                <div class="filter-info">Status: All</div>
            @endif
            
            <div class="filter-info">Generated on: {{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}</div>
        </div>
        <div class="header-right">
            <div class="total-count">Total Records: {{ $totalCount }}</div>
        </div>
    </div>

    <!-- Data Table -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th width="5%">S.No</th>
                <th width="15%">Student</th>
                <th width="15%">Email</th>
                <th width="15%">Course</th>
                <th width="10%">OT Code</th>
                <th width="10%">Service</th>
                <th width="10%">Status</th>
                <th width="10%">Created Date</th>
                <th width="10%">Modified Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($enrollments as $index => $enrollment)
                @php
                    $student = $enrollment->studentMaster;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')) }}</td>
                    <td>{{ $student->email ?? '-' }}</td>
                    <td>{{ $enrollment->course->course_name ?? 'N/A' }}</td>
                    <td>{{ $student->generated_OT_code ?? '-' }}</td>
                    <td>{{ $student->service->service_name ?? 'N/A' }}</td>
                    <td>{{ $enrollment->active_inactive == 1 ? 'Active' : 'Inactive' }}</td>
                    <td>{{ $enrollment->created_date ? \Carbon\Carbon::parse($enrollment->created_date)->format('d M Y, h:i A') : '-' }}</td>
                    <td>{{ $enrollment->modified_date ? \Carbon\Carbon::parse($enrollment->modified_date)->format('d M Y, h:i A') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html> --}}


<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Student Enrollment Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 10px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #333;
        }

        .header-left {
            text-align: left;
            flex: 1;
        }

        .header-right {
            text-align: right;
            flex: 1;
        }

        .report-title {
            text-align: center;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: bold;
        }

        .filter-info {
            margin-top: 3px;
            font-size: 9px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
            word-wrap: break-word;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px;
            text-align: left;
            vertical-align: top;
            font-size: 9px;
            line-height: 1.2;
        }

        th {
            background: #f2f2f2;
            font-weight: bold;
        }

        .course-name {
            font-weight: bold;
            font-size: 11px;
            color: #333;
        }

        .total-count {
            font-weight: bold;
            font-size: 11px;
            color: #333;
        }

        /* Column widths */
        .col-sno { width: 4%; }
        .col-student { width: 12%; }
        .col-email { width: 15%; }
        .col-course { width: 12%; }
        .col-ot { width: 6%; }
        .col-service { width: 12%; }
        .col-status { width: 6%; }
        .col-created { width: 10%; }
        .col-modified { width: 10%; }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        
        /* Ensure long text breaks properly */
        .break-word {
            word-wrap: break-word;
            word-break: break-all;
        }
    </style>
</head>

<body>
    <!-- Report Title -->
    <div class="report-title">Student Enrollment Report</div>

    <!-- Header with Course Name and Total Count -->
    <div class="header">
        <div class="header-left">
            @if($courseName)
                <div class="course-name">Course: {{ $courseName }}</div>
            @else
                <div class="course-name">All Courses</div>
            @endif
            
            @if($status !== null && $status !== '')
                <div class="filter-info">Status: {{ $status == 1 ? 'Active' : 'Inactive' }}</div>
            @else
                <div class="filter-info">Status: All</div>
            @endif
            
            <div class="filter-info">Generated on: {{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}</div>
        </div>
        <div class="header-right">
            <div class="total-count">Total Records: {{ $totalCount }}</div>
        </div>
    </div>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th class="col-sno text-center">S.No</th>
                <th class="col-student">Student</th>
                <th class="col-email break-word">Email</th>
                <th class="col-ot text-center">OT Code</th>
                <th class="col-service">Service</th>
                <th class="col-created">Created Date</th>
                <th class="col-modified">Modified Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($enrollments as $index => $enrollment)
                @php
                    $student = $enrollment->studentMaster;
                @endphp
                <tr>
                    <td class="col-sno text-center">{{ $index + 1 }}</td>
                    <td class="col-student break-word">
                        {{ trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')) }}
                    </td>
                    <td class="col-email break-word">{{ $student->email ?? '-' }}</td>
                    {{-- <td class="col-course break-word">{{ $enrollment->course->course_name ?? 'N/A' }}</td> --}}
                    <td class="col-ot text-center">{{ $student->generated_OT_code ?? '-' }}</td>
                    <td class="col-service break-word">{{ $student->service->service_name ?? 'N/A' }}</td>
                    {{-- <td class="col-status text-center">{{ $enrollment->active_inactive == 1 ? 'Active' : 'Inactive' }}</td> --}}
                    <td class="col-created break-word">
                        {{ $enrollment->created_date ? \Carbon\Carbon::parse($enrollment->created_date)->format('d M Y, H:i') : '-' }}
                    </td>
                    <td class="col-modified break-word">
                        {{ $enrollment->modified_date ? \Carbon\Carbon::parse($enrollment->modified_date)->format('d M Y, H:i') : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>