@extends('admin.layouts.master')

@section('title', 'Student Details - Sargam | Lal Bahadur')

@section('content')
<style>
    .student-info-table th { color: #1a1a1a !important; font-weight: 600; }
    .card table th { color: #1a1a1a !important; font-weight: 600; }
    .student-hero { background: linear-gradient(135deg, #004a93 0%, #003366 100%); }
    .metric-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .metric-card:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1); }
    .memo-card { border-left: 4px solid #fd7e14; }
    .exemption-card .badge { font-size: 0.75rem; }
    .cursor-pointer { cursor: pointer; }
</style>
<div class="container-fluid">
    <x-breadcrum title="Student Details"></x-breadcrum>
    <x-session_message />

    <!-- Student Hero Banner -->
    <div class="student-hero rounded-3 shadow-sm mb-4 p-4 text-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <small class="opacity-90 text-white d-block mb-1">Student Profile</small>
                <h1 class="h3 mb-2 fw-bold text-white">{{ $student->display_name ?? ($student->first_name ?? '') . ' ' . ($student->last_name ?? '') }}</h1>
                <p class="mb-0 small text-white">
                    {{ $student->service->service_name ?? 'N/A' }}
                    @if(isset($student->cadre) && $student->cadre)
                        • {{ $student->cadre->cadre_name ?? 'N/A' }}
                    @endif
                    • {{ $student->generated_OT_code ?? 'N/A' }}
                </p>
            </div>
            <div class="bg-dark bg-opacity-25 rounded-3 px-3 py-2 text-nowrap">
                <small class="d-block text-white-50">Status</small>
                <span class="fw-bold">Active Student</span>
            </div>
        </div>
    </div>

    <!-- Key Metrics / Summary Cards -->
    <div class="row g-3 mb-4">
        @if($attendanceSummary && $attendanceSummary->total_sessions > 0)
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer" onclick="scrollToSection('attendanceSummarySection')" role="button" tabindex="0">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-success fw-bold">{{ $attendanceSummary->present_count ?? 0 }}</h4>
                    <small class="text-body-secondary">PRESENT</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer" onclick="scrollToSection('attendanceSummarySection')" role="button" tabindex="0">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-warning fw-bold">{{ $attendanceSummary->late_count ?? 0 }}</h4>
                    <small class="text-body-secondary">LATE</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer" onclick="scrollToSection('attendanceSummarySection')" role="button" tabindex="0">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-danger fw-bold">{{ $attendanceSummary->absent_count ?? 0 }}</h4>
                    <small class="text-body-secondary">ABSENT</small>
                </div>
            </div>
        </div>
        @endif
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer" onclick="scrollToSection('medicalExceptionsSection')" role="button" tabindex="0">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-info fw-bold">{{ $medicalExemptions->count() }}</h4>
                    <small class="text-body-secondary">MEDICAL</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer" onclick="scrollToSection('dutiesSection')" role="button" tabindex="0">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-primary fw-bold">{{ $duties->count() }}</h4>
                    <small class="text-body-secondary">DUTIES</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer" onclick="scrollToSection('noticesSection')" role="button" tabindex="0">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-info fw-bold">{{ $notices->count() }}</h4>
                    <small class="text-body-secondary">NOTICES</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer" onclick="scrollToSection('memosSection')" role="button" tabindex="0">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-secondary fw-bold">{{ $memos->count() }}</h4>
                    <small class="text-body-secondary">MEMOS</small>
                </div>
            </div>
        </div>
        @if($attendanceSummary && $attendanceSummary->total_sessions > 0)
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 metric-card text-center h-100 cursor-pointer" onclick="scrollToSection('attendanceSummarySection')" role="button" tabindex="0">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-body-tertiary fw-bold">{{ $attendanceSummary->not_marked_count ?? 0 }}</h4>
                    <small class="text-body-secondary">NOT MARKED</small>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Medical Exceptions -->
            <div class="card border-0 shadow-sm rounded-3 mb-4" id="medicalExceptionsSection">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-heartbeat me-2 text-danger"></i>Medical Exceptions ({{ $medicalExemptions->count() }})</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" onclick="exportTableToExcel('medicalExemptionsTable', 'Medical_Exceptions')">
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="printTable('medicalExemptionsTable')">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    @if($medicalExemptions->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="medicalExemptionsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Course</th>
                                        <th>From Date</th>
                                        <th>To Date</th>
                                        <th>Category</th>
                                        <th>Speciality</th>
                                        <th>Description</th>
                                        <th>Document</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($medicalExemptions as $exemption)
                                        <tr>
                                            <td>{{ $exemption->course->course_name ?? 'N/A' }}</td>
                                            <td>{{ $exemption->from_date ? \Carbon\Carbon::parse($exemption->from_date)->format('d M Y h:i A') : 'N/A' }}</td>
                                            <td>{{ $exemption->to_date ? \Carbon\Carbon::parse($exemption->to_date)->format('d M Y h:i A') : 'N/A' }}</td>
                                            <td>{{ $exemption->category->exemp_category_name ?? 'N/A' }}</td>
                                            <td>{{ $exemption->speciality->speciality_name ?? 'N/A' }}</td>
                                            <td>{{ $exemption->Description ?? 'N/A' }}</td>
                                            <td class="text-center">
                                                @if($exemption->Doc_upload)
                                                    <a href="{{ asset('storage/' . $exemption->Doc_upload) }}" target="_blank"
                                                        class="btn btn-sm btn-info" title="View Document" data-bs-toggle="tooltip"
                                                        data-bs-placement="top">
                                                        <i class="fas fa-file-pdf"></i> View
                                                    </a>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No medical exceptions found.</p>
                    @endif
                </div>
            </div>

            <!-- Duties Assigned -->
            <div class="card border-0 shadow-sm rounded-3 mb-4" id="dutiesSection">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-tasks me-2 text-primary"></i>Duties Assigned ({{ $duties->count() }})</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" onclick="exportTableToExcel('dutiesTable', 'Duties_Assigned')">
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="printTable('dutiesTable')">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    @if($duties->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="dutiesTable">
                                <thead class="table-light">
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
                                            <td>{{ $duty->mdo_date ? \Carbon\Carbon::parse($duty->mdo_date)->format('d M Y h:i A') : 'N/A' }}</td>
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
                        <p class="text-muted mb-0">No duties assigned.</p>
                    @endif
                </div>
            </div>

            <!-- Notices Received -->
            <div class="card border-0 shadow-sm rounded-3 mb-4" id="noticesSection">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-bell me-2 text-warning"></i>Notices Received ({{ $notices->count() }})</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" onclick="exportTableToExcel('noticesTable', 'Notices_Received')">
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="printTable('noticesTable')">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    @if($notices->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="noticesTable">
                                <thead class="table-light">
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
                                            <td>{{ $notice->session_date ? \Carbon\Carbon::parse($notice->session_date)->format('d M Y h:i A') : 'N/A' }}</td>
                                            <td>{{ $notice->topic ?? 'N/A' }}</td>
                                            <td>
                                                @if($notice->status == 1)
                                                    <span class="badge bg-warning text-dark">Pending</span>
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
                        <p class="text-muted mb-0">No notices received.</p>
                    @endif
                </div>
            </div>

            <!-- Memos Issued -->
            <div class="card border-0 shadow-sm rounded-3 mb-4" id="memosSection">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-file-alt me-2 text-secondary"></i>Memos Issued ({{ $memos->count() }})</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" onclick="exportTableToExcel('memosTable', 'Memos_Issued')">
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="printTable('memosTable')">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    @if($memos->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="memosTable">
                                <thead class="table-light">
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
                                            <td>{{ $memo->session_date ? \Carbon\Carbon::parse($memo->session_date)->format('d M Y h:i A') : 'N/A' }}</td>
                                            <td>{{ $memo->topic ?? 'N/A' }}</td>
                                            <td>{{ $memo->conclusion_type ?? 'N/A' }}</td>
                                            <td>
                                                @if($memo->status == 1)
                                                    <span class="badge bg-warning text-dark">Pending</span>
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
                        <p class="text-muted mb-0">No memos issued.</p>
                    @endif
                </div>
            </div>

            <!-- Attendance Summary -->
            @if($attendanceSummary && $attendanceSummary->total_sessions > 0)
            <div class="card border-0 shadow-sm rounded-3 mb-4" id="attendanceSummarySection">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-calendar-check me-2 text-success"></i>Attendance Summary</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" onclick="exportTableToExcel('attendanceSummaryTable', 'Attendance_Summary')">
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="printTable('attendanceSummaryTable')">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <!-- Hidden table for export/print -->
                    <table class="table table-bordered d-none" id="attendanceSummaryTable">
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
                    <div class="row g-3">
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center p-3 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-25">
                                <h4 class="text-success mb-0 fw-bold">{{ $attendanceSummary->present_count ?? 0 }}</h4>
                                <small class="text-body-secondary">Present</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center p-3 bg-warning bg-opacity-10 rounded-3 border border-warning border-opacity-25">
                                <h4 class="text-warning mb-0 fw-bold">{{ $attendanceSummary->late_count ?? 0 }}</h4>
                                <small class="text-body-secondary">Late</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center p-3 bg-danger bg-opacity-10 rounded-3 border border-danger border-opacity-25">
                                <h4 class="text-danger mb-0 fw-bold">{{ $attendanceSummary->absent_count ?? 0 }}</h4>
                                <small class="text-body-secondary">Absent</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center p-3 bg-secondary bg-opacity-10 rounded-3 border border-secondary border-opacity-25">
                                <h4 class="text-secondary mb-0 fw-bold">{{ $attendanceSummary->not_marked_count ?? 0 }}</h4>
                                <small class="text-body-secondary">Not Marked</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center p-3 bg-info bg-opacity-10 rounded-3 border border-info border-opacity-25">
                                <h4 class="text-info mb-0 fw-bold">{{ $attendanceSummary->total_sessions ?? 0 }}</h4>
                                <small class="text-body-secondary">Marked Sessions</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center p-3 bg-primary bg-opacity-10 rounded-3 border border-primary border-opacity-25">
                                <h4 class="text-primary mb-0 fw-bold">{{ $attendanceSummary->total_expected_sessions ?? 0 }}</h4>
                                <small class="text-body-secondary">Total Sessions</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Sidebar: Student Profile & Actions -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4 sticky-top" style="top: 1rem;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                    <h5 class="mb-0 fw-semibold"><i class="fas fa-user me-2 text-primary"></i>Student Profile</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="d-flex flex-column gap-3">
                        <div>
                            <small class="text-body-secondary text-uppercase small">Full Name</small>
                            <p class="mb-0 fw-medium">{{ $student->display_name ?? ($student->first_name ?? '') . ' ' . ($student->last_name ?? '') }}</p>
                        </div>
                        <div>
                            <small class="text-body-secondary text-uppercase small">Email Address</small>
                            <p class="mb-0 fw-medium">{{ $student->email ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <small class="text-body-secondary text-uppercase small">OT Code</small>
                            <p class="mb-0 fw-medium">{{ $student->generated_OT_code ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <small class="text-body-secondary text-uppercase small">Service</small>
                            <p class="mb-0 fw-medium">{{ $student->service->service_name ?? 'N/A' }}</p>
                        </div>
                        @if(isset($student->cadre) && $student->cadre)
                        <div>
                            <small class="text-body-secondary text-uppercase small">Cadre</small>
                            <p class="mb-0 fw-medium">{{ $student->cadre->cadre_name ?? 'N/A' }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                    <h5 class="mb-0 fw-semibold">Reports & Actions</h5>
                </div>
                <div class="card-body px-4 pb-4 d-flex flex-column gap-2">
                    <a href="{{ route('admin.dashboard.students.history', encrypt($student->pk)) }}" class="btn btn-outline-secondary w-100 justify-content-start">
                        <i class="fas fa-file-alt me-2"></i>Detailed Report
                    </a>
                    <a href="{{ route('admin.dashboard.students.history', encrypt($student->pk)) }}" class="btn btn-outline-secondary w-100 justify-content-start">
                        <i class="fas fa-graduation-cap me-2"></i>Academic Transcript
                    </a>
                    <a href="mailto:{{ $student->email ?? '#' }}" class="btn btn-outline-secondary w-100 justify-content-start">
                        <i class="fas fa-envelope me-2"></i>Contact Student
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="mb-4 d-flex flex-wrap gap-2">
        <a href="{{ route('admin.dashboard.students.history', encrypt($student->pk)) }}" class="btn btn-info">
            <i class="fas fa-history me-2"></i>View Full Participant History
        </a>
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
        
        // Convert document links to text for Excel export
        clonedTable.querySelectorAll('a[target="_blank"]').forEach(link => {
            if (link.textContent.includes('View')) {
                link.outerHTML = 'Available';
            }
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
        
        // Convert document links to text for print
        clonedTable.querySelectorAll('a[target="_blank"]').forEach(link => {
            if (link.textContent.includes('View')) {
                link.outerHTML = 'Available';
            }
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

