<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Pending Feedback Summary Report</title>
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
            font-size: 10px;
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
            font-size: 10px;
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
        
        .badge-high {
            color: #dc3545;
            font-weight: bold;
            font-size: 11px;
        }
        
        .badge-medium {
            color: #ff9800;
            font-weight: bold;
            font-size: 11px;
        }
        
        .badge-low {
            color: #28a745;
            font-weight: bold;
            font-size: 11px;
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
    </style>
</head>
<body>
<div class="page-header">
    <div class="page-header-top">
        <div class="page-header-col page-header-title">
            <h1>LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</h1>
            <h2>PENDING FEEDBACK SUMMARY REPORT</h2>
            <div class="page-header-sub">Student Feedback Status Report</div>
        </div>
    </div>
    <div class="meta-row">
        @php
            if (!empty($filters['filter_from_date']) && !empty($filters['filter_to_date'])) {
                $periodText = date('d-M-Y', strtotime($filters['filter_from_date'])) . ' To ' . date('d-M-Y', strtotime($filters['filter_to_date']));
            } elseif (!empty($filters['filter_from_date'])) {
                $periodText = 'From ' . date('d-M-Y', strtotime($filters['filter_from_date']));
            } elseif (!empty($filters['filter_to_date'])) {
                $periodText = 'Until ' . date('d-M-Y', strtotime($filters['filter_to_date']));
            }
        @endphp
        @php
            $summaryScope = ($filters['filter_course_scope'] ?? 'active') === 'archive' ? 'Archive programs' : 'Active (current) programs';
        @endphp
        @if(!empty($courseName))
        <span><strong>Program / Course:</strong> {{ $courseName }}</span>
        @else
        <span><strong>Program / Course:</strong> All {{ $summaryScope }}</span>
        @endif
        @if(!empty($sessionName))
        <span><strong>Session:</strong> {{ $sessionName }}</span>
        @endif
        @if(!empty($periodText))
        <span><strong>Period:</strong> {{ $periodText }}</span>
        @endif
        <span><strong>Generated on:</strong> {{ $export_date ?? now()->format('d-m-Y H:i') }}</span>
    </div>
</div>

@if(empty($students) || $students->isEmpty())
    <p style="font-size: 12px;">No pending feedback records found.</p>
@else
    @php
        $totalPending = $students->sum('pending_count');
    @endphp
    
    <div class="summary-stats">
        <span><strong>Total Students:</strong> {{ $total_count }}</span>
        <span><strong>Total Pending Feedbacks:</strong> {{ $totalPending }}</span>
    </div>

     <table>
        <thead>
         <tr>
            <th class="text-center" style="width: 40px;">S.No.</th>
            <th class="text-start" style="min-width: 110px;">User Name</th>
            <th class="text-start" style="min-width: 130px;">Email</th>
            <th class="text-center" style="width: 70px;">Contact</th>
            <th class="text-start" style="min-width: 110px;">Program / Course</th>
            <th class="text-start" style="min-width: 100px;">Session Info</th>
            <th class="text-start" style="min-width: 100px;">Date Range</th>
            <th class="text-center" style="width: 70px;">Pending Count</th>
         </tr>
        </thead>
        <tbody>
        @foreach($students as $index => $student)
            @php
                $count = $student->pending_count ?? 0;
                $badgeClass = $count > 10 ? 'badge-high' : ($count > 5 ? 'badge-medium' : 'badge-low');
                // Use session name from controller if session filter applied
                $sessionInfo = !empty($sessionName) ? $sessionName : ($student->session_info ?? 'Multiple Sessions');
            @endphp
             <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-start">{{ $student->user_name ?? '—' }}</td>
                <td class="text-start">{{ $student->email ?? '—' }}</td>
                <td class="text-center">{{ $student->contact_no ?? '—' }}</td>
                <td class="text-start">{{ $student->course_name ?? '—' }}</td>
                <td class="text-start">{{ $sessionInfo }}</td>
                <td class="text-start">{{ $student->date_range ?? '—' }}</td>
                <td class="text-center"><span class="{{ $badgeClass }}">{{ $count }}</span></td>
             </tr>
        @endforeach
        </tbody>
     </table>
@endif

<div class="footer">
    <small>This is a computer-generated report - No signature required</small><br>
    <small>Lal Bahadur Shastri National Academy of Administration, Mussoorie</small>
</div>
</body>
</html>