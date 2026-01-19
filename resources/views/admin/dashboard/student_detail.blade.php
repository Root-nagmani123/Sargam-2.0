@extends('admin.layouts.master')

@section('title', 'Student Details - Sargam | Lal Bahadur')

@section('content')
<style>
    .student-info-table th {
        color: #1a1a1a !important;
        font-weight: 600;
    }
    .card table th {
        color: #1a1a1a !important;
        font-weight: 600;
    }
</style>
<div class="container-fluid">
    <x-breadcrum title="Student Details"></x-breadcrum>
    <x-session_message />

    <!-- Student Basic Information -->
    <div class="card mb-4" style="border-left: 4px solid #004a93;">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Student Information</h6>
        </div>
        <div class="card-body py-3">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-borderless student-info-table mb-0">
                        <tr>
                            <th width="15%" class="py-1">Student Name:</th>
                            <td class="py-1">{{ $student->display_name ?? ($student->first_name ?? '') . ' ' . ($student->last_name ?? '') }}</td>
                            <th width="15%" class="py-1">OT Code:</th>
                            <td class="py-1">{{ $student->generated_OT_code ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="py-1">Email:</th>
                            <td class="py-1">{{ $student->email ?? 'N/A' }}</td>
                            <th class="py-1">Service:</th>
                            <td class="py-1">{{ $student->service->service_name ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center" style="border-left: 4px solid #dc3545; cursor: pointer;" onclick="scrollToSection('medicalExceptionsSection')">
                <div class="card-body">
                    <h3 class="text-danger">{{ $medicalExemptions->count() }}</h3>
                    <p class="mb-0">Medical Exceptions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center" style="border-left: 4px solid #ffc107; cursor: pointer;" onclick="scrollToSection('dutiesSection')">
                <div class="card-body">
                    <h3 class="text-warning">{{ $duties->count() }}</h3>
                    <p class="mb-0">Duties Assigned</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center" style="border-left: 4px solid #17a2b8; cursor: pointer;" onclick="scrollToSection('noticesSection')">
                <div class="card-body">
                    <h3 class="text-info">{{ $notices->count() }}</h3>
                    <p class="mb-0">Notices Received</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center" style="border-left: 4px solid #6c757d; cursor: pointer;" onclick="scrollToSection('memosSection')">
                <div class="card-body">
                    <h3 class="text-secondary">{{ $memos->count() }}</h3>
                    <p class="mb-0">Memos Issued</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrolled Courses -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-book me-2"></i>Enrolled Courses</h5>
            <div>
                <button type="button" class="btn btn-sm btn-success" onclick="exportTableToExcel('enrolledCoursesTable', 'Enrolled_Courses')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="printTable('enrolledCoursesTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($enrolledCourses->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover" id="enrolledCoursesTable">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Enrollment Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrolledCourses as $enrollment)
                                <tr>
                                    <td>{{ $enrollment->course->course_name ?? 'N/A' }}</td>
                                    <td>{{ $enrollment->created_date ? \Carbon\Carbon::parse($enrollment->created_date)->format('d M Y') : 'N/A' }}</td>
                                    <td>
                                        <span class="badge {{ $enrollment->active_inactive ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $enrollment->active_inactive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No courses enrolled.</p>
            @endif
        </div>
    </div>

    <!-- Medical Exceptions -->
    <div class="card mb-4" id="medicalExceptionsSection">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Medical Exceptions ({{ $medicalExemptions->count() }})</h5>
            <div>
                <button type="button" class="btn btn-sm btn-success" onclick="exportTableToExcel('medicalExemptionsTable', 'Medical_Exceptions')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="printTable('medicalExemptionsTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($medicalExemptions->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover" id="medicalExemptionsTable">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>From Date</th>
                                <th>To Date</th>
                                <th>Category</th>
                                <th>Speciality</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($medicalExemptions as $exemption)
                                <tr>
                                    <td>{{ $exemption->course->course_name ?? 'N/A' }}</td>
                                    <td>{{ $exemption->from_date ? \Carbon\Carbon::parse($exemption->from_date)->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $exemption->to_date ? \Carbon\Carbon::parse($exemption->to_date)->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $exemption->category->exemption_category_name ?? 'N/A' }}</td>
                                    <td>{{ $exemption->speciality->exemption_medical_speciality_name ?? 'N/A' }}</td>
                                    <td>{{ $exemption->Description ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No medical exceptions found.</p>
            @endif
        </div>
    </div>

    <!-- Duties Assigned -->
    <div class="card mb-4" id="dutiesSection">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Duties Assigned ({{ $duties->count() }})</h5>
            <div>
                <button type="button" class="btn btn-sm btn-success" onclick="exportTableToExcel('dutiesTable', 'Duties_Assigned')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="printTable('dutiesTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($duties->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover" id="dutiesTable">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Duty Type</th>
                                <th>Date</th>
                                <th>Time From</th>
                                <th>Time To</th>
                                <th>Faculty</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($duties as $duty)
                                <tr>
                                    <td>{{ $duty->courseMaster->course_name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $duty->mdoDutyTypeMaster->mdo_duty_type_name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>{{ $duty->mdo_date ? \Carbon\Carbon::parse($duty->mdo_date)->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $duty->Time_from ?? 'N/A' }}</td>
                                    <td>{{ $duty->Time_to ?? 'N/A' }}</td>
                                    <td>{{ $duty->facultyMaster->full_name ?? 'N/A' }}</td>
                                    <td>{{ $duty->Remark ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No duties assigned.</p>
            @endif
        </div>
    </div>

    <!-- Notices Received -->
    <div class="card mb-4" id="noticesSection">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notices Received ({{ $notices->count() }})</h5>
            <div>
                <button type="button" class="btn btn-sm btn-success" onclick="exportTableToExcel('noticesTable', 'Notices_Received')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="printTable('noticesTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($notices->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover" id="noticesTable">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Session Date</th>
                                <th>Topic</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notices as $notice)
                                <tr>
                                    <td>{{ $notice->course_name ?? 'N/A' }}</td>
                                    <td>{{ $notice->session_date ? \Carbon\Carbon::parse($notice->session_date)->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $notice->topic ?? 'N/A' }}</td>
                                    <td>
                                        @if($notice->status == 1)
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($notice->status == 2)
                                            <span class="badge bg-danger">Escalated</span>
                                        @else
                                            <span class="badge bg-secondary">Unknown</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No notices received.</p>
            @endif
        </div>
    </div>

    <!-- Memos Issued -->
    <div class="card mb-4" id="memosSection">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Memos Issued ({{ $memos->count() }})</h5>
            <div>
                <button type="button" class="btn btn-sm btn-success" onclick="exportTableToExcel('memosTable', 'Memos_Issued')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="printTable('memosTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($memos->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover" id="memosTable">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Session Date</th>
                                <th>Topic</th>
                                <th>Conclusion Type</th>
                                <th>Status</th>
                                <th>Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($memos as $memo)
                                <tr>
                                    <td>{{ $memo->course_name ?? 'N/A' }}</td>
                                    <td>{{ $memo->session_date ? \Carbon\Carbon::parse($memo->session_date)->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $memo->topic ?? 'N/A' }}</td>
                                    <td>{{ $memo->conclusion_type ?? 'N/A' }}</td>
                                    <td>
                                        @if($memo->status == 1)
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($memo->status == 2)
                                            <span class="badge bg-success">Resolved</span>
                                        @else
                                            <span class="badge bg-secondary">Unknown</span>
                                        @endif
                                    </td>
                                    <td>{{ $memo->response ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No memos issued.</p>
            @endif
        </div>
    </div>

    <!-- Attendance Summary -->
    @if($attendanceSummary && $attendanceSummary->total_sessions > 0)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Attendance Summary</h5>
            <div>
                <button type="button" class="btn btn-sm btn-success" onclick="exportTableToExcel('attendanceSummaryTable', 'Attendance_Summary')">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="printTable('attendanceSummaryTable')">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Hidden table for export/print -->
            <table class="table table-bordered" id="attendanceSummaryTable" style="display: none;">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Present</td>
                        <td>{{ $attendanceSummary->present_count ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Late</td>
                        <td>{{ $attendanceSummary->late_count ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Absent</td>
                        <td>{{ $attendanceSummary->absent_count ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Not Marked</td>
                        <td>{{ $attendanceSummary->not_marked_count ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Marked Sessions</td>
                        <td>{{ $attendanceSummary->total_sessions ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Total Sessions</td>
                        <td>{{ $attendanceSummary->total_expected_sessions ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>
            <!-- Display cards -->
            <div class="row">
                <div class="col-md-2">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-success">{{ $attendanceSummary->present_count ?? 0 }}</h4>
                        <small>Present</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-warning">{{ $attendanceSummary->late_count ?? 0 }}</h4>
                        <small>Late</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-danger">{{ $attendanceSummary->absent_count ?? 0 }}</h4>
                        <small>Absent</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-secondary">{{ $attendanceSummary->not_marked_count ?? 0 }}</h4>
                        <small>Not Marked</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-info">{{ $attendanceSummary->total_sessions ?? 0 }}</h4>
                        <small>Marked Sessions</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-primary">{{ $attendanceSummary->total_expected_sessions ?? 0 }}</h4>
                        <small>Total Sessions</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Back Button -->
    <div class="mb-4">
        <a href="{{ route('admin.dashboard.students') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Student List
        </a>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    // Function to scroll to section smoothly
    function scrollToSection(sectionId) {
        const element = document.getElementById(sectionId);
        if (!element) {
            console.error('Element not found:', sectionId);
            return;
        }

        // Try multiple scroll methods for better compatibility
        try {
            // Method 1: Use scrollIntoView (most reliable)
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
                inline: 'nearest'
            });

            // Method 2: Also try scrolling the container if scrollIntoView doesn't work well
            setTimeout(() => {
                // Find scrollable container
                let scrollContainer = document.querySelector('.body-wrapper');
                if (!scrollContainer) {
                    scrollContainer = document.querySelector('.page-wrapper');
                }
                
                if (scrollContainer && scrollContainer !== window) {
                    const containerRect = scrollContainer.getBoundingClientRect();
                    const elementRect = element.getBoundingClientRect();
                    const relativeTop = elementRect.top - containerRect.top + scrollContainer.scrollTop;
                    const offset = 100;
                    const targetScroll = Math.max(0, relativeTop - offset);
                    
                    scrollContainer.scrollTo({
                        top: targetScroll,
                        behavior: 'smooth'
                    });
                } else {
                    // Fallback to window scroll
                    const elementTop = element.getBoundingClientRect().top + window.pageYOffset;
                    const offset = 100;
                    const targetPosition = Math.max(0, elementTop - offset);
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            }, 100);
        } catch (e) {
            console.error('Scroll error:', e);
            // Fallback: simple scroll
            element.scrollIntoView({ behavior: 'smooth' });
        }
        
        // Highlight the section briefly
        setTimeout(() => {
            element.style.transition = 'box-shadow 0.3s ease';
            element.style.boxShadow = '0 0 20px rgba(0, 74, 147, 0.5)';
            setTimeout(() => {
                element.style.boxShadow = '';
            }, 2000);
        }, 500);
    }

    // Function to export table to Excel
    function exportTableToExcel(tableId, fileName) {
        const table = document.getElementById(tableId);
        if (!table) {
            alert('Table not found!');
            return;
        }

        // Clone the table
        const clonedTable = table.cloneNode(true);
        
        // Remove badges and convert to plain text
        clonedTable.querySelectorAll('.badge').forEach(badge => {
            badge.parentElement.textContent = badge.textContent.trim();
        });

        // Create workbook and worksheet
        const wb = XLSX.utils.table_to_book(clonedTable, {sheet: fileName});
        
        // Generate filename with student name and current date
        const studentName = '{{ $student->display_name ?? ($student->first_name ?? "") . " " . ($student->last_name ?? "") }}'.replace(/[^a-z0-9]/gi, '_');
        const date = new Date().toISOString().split('T')[0];
        const finalFileName = `${studentName}_${fileName}_${date}.xlsx`;
        
        // Save file
        XLSX.writeFile(wb, finalFileName);
    }

    // Function to print table
    function printTable(tableId) {
        const table = document.getElementById(tableId);
        if (!table) {
            alert('Table not found!');
            return;
        }

        // Get table title from card header
        const card = table.closest('.card');
        const title = card ? card.querySelector('.card-header h5')?.textContent || 'Table' : 'Table';
        
        // Clone table for printing
        const clonedTable = table.cloneNode(true);
        
        // Remove badges and convert to plain text for print
        clonedTable.querySelectorAll('.badge').forEach(badge => {
            const text = badge.textContent.trim();
            badge.outerHTML = text;
        });
        
        // Create print window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Print ${title}</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            margin: 20px;
                        }
                        h2 {
                            color: #004a93;
                            margin-bottom: 20px;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 10px;
                        }
                        th, td {
                            border: 1px solid #ddd;
                            padding: 8px;
                            text-align: left;
                        }
                        th {
                            background-color: #004a93;
                            color: white;
                            font-weight: bold;
                        }
                        tr:nth-child(even) {
                            background-color: #f2f2f2;
                        }
                        @media print {
                            body { margin: 0; }
                            @page { margin: 1cm; }
                        }
                    </style>
                </head>
                <body>
                    <h2>${title}</h2>
                    <p><strong>Student:</strong> {{ $student->display_name ?? ($student->first_name ?? '') . ' ' . ($student->last_name ?? '') }} | <strong>OT Code:</strong> {{ $student->generated_OT_code ?? 'N/A' }}</p>
                    <p><strong>Print Date:</strong> ${new Date().toLocaleString()}</p>
                    ${clonedTable.outerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
        }, 250);
    }
</script>
@endpush

@endsection

