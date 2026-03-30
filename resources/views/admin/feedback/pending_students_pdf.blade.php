<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Pending Feedback Report</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm 10mm;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
        }
        
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            margin: 16px 18px;
            color: #222;
            max-width: 297mm;
            background: white;
        }
        
        .page-header {
            border-bottom: 2px solid #004a93;
            padding-bottom: 8px;
            margin-bottom: 12px;
            page-break-after: avoid;
        }
        
        .page-header-top {
            display: table;
            width: 100%;
        }
        
        .page-header-col {
            display: table-cell;
            vertical-align: middle;
        }
        
        .page-header-title {
            text-align: center;
        }
        
        .page-header-title h1 {
            font-size: 14px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        
        .page-header-title h2 {
            font-size: 13px;
            margin: 4px 0 0;
            text-transform: uppercase;
            color: #004a93;
            font-weight: bold;
        }
        
        .page-header-sub {
            font-size: 10px;
            margin-top: 3px;
            color: #555;
        }
        
        .meta-row {
            font-size: 10px;
            margin-top: 8px;
        }
        
        .meta-row span {
            display: inline-block;
            margin-right: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-top: 8px;
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
        
        th, td {
            padding: 6px 4px;
            border: 1px solid #dde2ea;
        }
        
        thead th {
            background: #e6ecf5;
            font-weight: 600;
            font-size: 9px;
        }
        
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        
        tbody tr:nth-child(even) td {
            background: #fafbfc;
        }
        
        .footer {
            border-top: 1px solid #dde2ea;
            font-size: 8px;
            color: #666;
            text-align: center;
            padding-top: 5px;
            margin-top: 8px;
        }
        
        .summary-stats {
            font-size: 11px;
            margin-bottom: 10px;
            padding: 6px 0;
        }
        
        .summary-stats span {
            display: inline-block;
            margin-right: 25px;
            background: #f5f5f5;
            padding: 3px 10px;
            border-radius: 3px;
        }
        
        .summary-stats strong {
            color: #004a93;
            font-size: 12px;
        }
        
        .badge-pending {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="page-header">
    <div class="page-header-top">
        <div class="page-header-col page-header-title">
            <h1>LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</h1>
            <h2>PENDING FEEDBACK REPORT</h2>
            <div class="page-header-sub">Student Feedback Status Report</div>
        </div>
    </div>
    <div class="meta-row">
        <span><strong>Course:</strong> {{ $course_name ?? 'All Courses' }}</span>
        <span><strong>Generated On:</strong> {{ $export_date ?? now()->format('d-m-Y H:i:s') }}</span>
        <span><strong>Total Records:</strong> {{ $students->count() }}</span>
    </div>
</div>

@if($students->count() > 0)
    @php
        $totalPending = $students->count();
    @endphp
    
    <div class="summary-stats">
        <span><strong>Total Pending Feedbacks:</strong> {{ $totalPending }}</span>
    </div>

    <table>
        <thead>
        <tr>
            <th class="text-center" width="4%">#</th>
            <th class="text-start" width="12%">Student Name</th>
            <th class="text-start" width="14%">Email</th>
            <th class="text-center" width="8%">Phone</th>
            <th class="text-center" width="8%">OT Code</th>
            <th class="text-start" width="12%">Course</th>
            <th class="text-start" width="12%">Session Topic</th>
            <th class="text-center" width="8%">Start Date</th>
            <th class="text-center" width="8%">End Date</th>
            <th class="text-center" width="8%">Session Time</th>
            <th class="text-center" width="8%">Generated On</th>
        </tr>
        </thead>
        <tbody>
        @foreach($students as $i => $row)
         <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td class="text-start">{{ $row->student_name ?? '—' }}</td>
            <td class="text-start">{{ $row->email ?? '—' }}</td>
            <td class="text-center">{{ $row->contact_no ?? '—' }}</td>
            <td class="text-center">{{ $row->generated_OT_code ?? '—' }}</td>
            <td class="text-start">{{ $row->course_name ?? '—' }}</td>
            <td class="text-start">{{ $row->subject_topic ?? '—' }}</td>
            <td class="text-center">{{ isset($row->from_date) ? date('d-m-Y', strtotime($row->from_date)) : '—' }}</td>
            <td class="text-center">{{ isset($row->to_date) ? date('d-m-Y', strtotime($row->to_date)) : '—' }}</td>
            <td class="text-center">{{ $row->class_session ?? '—' }}</td>
            <td class="text-center">{{ now()->format('d-m-Y H:i:s') }}</td>
         </tr>
        @endforeach
        </tbody>
     </table>

@else
    <p style="text-align:center; font-size: 12px; padding: 20px;">No pending feedback records found.</p>
@endif

<div class="footer">
    <small>This is a computer-generated report - No signature required</small><br>
    <small>Lal Bahadur Shastri National Academy of Administration, Mussoorie</small>
</div>
</body>
</html>