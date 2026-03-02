@extends('admin.layouts.master')

@section('title', 'Faculty MDO/Escort Exception View - Sargam | Lal Bahadur')

@section('setup_content')
<style>
    /* GIGW Compliant Modern Design */
    :root {
        --primary-blue: #004a93;
        --primary-light: #0066cc;
        --light-bg: #e8eef7;
        --very-light: #f0f3f7;
        --text-primary: #1f2937;
        --text-secondary: #4b5563;
        --text-muted: #6b7280;
        --border-color: #d1d5db;
        --divider: #e5e7eb;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --error-color: #ef4444;
        --transition: all 0.3s ease-in-out;
    }
    
    /* Summary Card Enhancement */
    .exception-summary-card {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-light) 100%);
        border-radius: 12px;
        padding: 24px;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 74, 147, 0.15);
        margin-bottom: 32px;
        transition: var(--transition);
    }
    
    .exception-summary-card:hover {
        box-shadow: 0 8px 24px rgba(0, 74, 147, 0.25);
        transform: translateY(-2px);
    }
    
    .summary-stat {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .summary-stat-label {
        font-size: 13px;
        font-weight: 500;
        opacity: 0.9;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    
    .summary-stat-value {
        font-size: 32px;
        font-weight: 700;
        letter-spacing: -1px;
    }
    
    /* Table Header Enhancement */
    .table-header {
        background: var(--very-light);
        border-bottom: 2px solid var(--primary-blue);
        padding: 16px;
        border-radius: 8px 8px 0 0;
        margin-bottom: 0;
    }
    
    .table-header h6 {
        margin: 0;
        color: var(--primary-blue);
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .table-header .material-icons {
        font-size: 20px;
    }
    
    /* Modern Table Styling */
    .exception-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        margin: 0;
        background: white;
    }
    
    .exception-table thead {
        background: var(--very-light);
    }
    
    .exception-table thead th {
        background: var(--very-light);
        color: var(--primary-blue);
        font-weight: 600;
        font-size: 13px;
        padding: 14px 16px;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid var(--primary-blue);
        white-space: nowrap;
    }
    
    .exception-table tbody td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--divider);
        color: var(--text-primary);
        font-size: 14px;
    }
    
    .exception-table tbody tr {
        transition: var(--transition);
    }
    
    .exception-table tbody tr:hover {
        background: var(--very-light);
    }
    
    .exception-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* Faculty/Course Section Container */
    .section-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 24px;
        overflow: hidden;
        border-top: 4px solid var(--primary-blue);
        transition: var(--transition);
    }
    
    .section-container:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    }
    
    /* Faculty Header */
    .faculty-header {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-light) 100%);
        color: white;
        padding: 18px 24px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .faculty-header h5 {
        margin: 0;
        font-weight: 600;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .faculty-header .material-icons {
        font-size: 22px;
    }
    
    /* Course Section Header */
    .course-section {
        border-bottom: 1px solid var(--divider);
        padding: 20px 24px;
        background: white;
        position: relative;
    }
    
    .course-section:last-child {
        border-bottom: none;
    }
    
    .course-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
    }
    
    .course-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--text-primary);
        font-weight: 600;
        font-size: 15px;
    }
    
    .course-title .material-icons {
        font-size: 20px;
        color: var(--primary-blue);
    }
    
    /* Badge Enhancement */
    .exception-badge {
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
        color: white;
        font-weight: 700;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);
    }
    
    .exception-badge .material-icons {
        font-size: 16px;
    }
    
    /* Student Records Table */
    .student-records-section {
        padding: 20px 24px;
    }
    
    .student-records-section .table-header {
        margin-left: -24px;
        margin-right: -24px;
        margin-top: -20px;
        width: calc(100% + 48px);
        border-radius: 0;
        margin-bottom: 18px;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 48px 24px;
        background: var(--very-light);
        border-radius: 8px;
    }
    
    .empty-state .material-icons {
        font-size: 56px;
        color: var(--text-muted);
        margin-bottom: 16px;
        opacity: 0.6;
    }
    
    .empty-state p {
        color: var(--text-muted);
        font-size: 15px;
        margin: 0;
    }
    
    /* Faculty View Container */
    .faculty-view-container {
        padding: 24px 0;
    }
    
    /* Filter Section Enhancement */
    .filter-section {
        background: white;
        padding: 20px 24px;
        border-radius: 8px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    .filter-section .form-label {
        color: var(--text-primary);
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    
    .filter-section .form-select {
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 10px 12px;
        font-size: 14px;
        transition: var(--transition);
        min-height: 40px;
    }
    
    .filter-section .form-select:hover {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.1);
    }
    
    .filter-section .form-select:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.1);
    }
    
    /* Responsive Adjustments - Tablet and below (desktop unchanged) */
    @media (max-width: 991px) {
        .container-fluid {
            padding-left: 16px;
            padding-right: 16px;
        }
        
        .card-body.p-4 {
            padding: 1rem !important;
        }
        
        .row.mb-4.align-items-center .col-6 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .row.mb-4.align-items-center .col-6:first-child {
            margin-bottom: 12px;
        }
        
        .row.mb-4 .d-flex.justify-content-end {
            justify-content: flex-start !important;
        }
        
        .exception-summary-card {
            padding: 20px 16px;
            margin-bottom: 24px;
        }
        
        .exception-summary-card .row.g-4 .col-md-6 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 16px;
        }
        
        .exception-summary-card .row.g-4 .col-md-6:last-child {
            margin-bottom: 0;
        }
        
        .filter-section {
            padding: 16px;
        }
        
        .filter-section .col-md-4 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .section-container {
            margin-bottom: 20px;
        }
        
        .course-section {
            padding: 16px;
        }
        
        .student-records-section {
            padding: 16px;
        }
        
        .student-records-section .table-header {
            margin-left: -16px;
            margin-right: -16px;
            margin-top: -16px;
            width: calc(100% + 32px);
            padding: 12px 16px;
        }
        
        .table-responsive {
            margin-left: -16px;
            margin-right: -16px;
            padding-left: 16px;
            padding-right: 16px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .exception-table {
            min-width: 800px;
        }
        
        .exception-table thead th,
        .exception-table tbody td {
            font-size: 12px;
            padding: 10px 12px;
            white-space: normal;
        }
        
        .exception-table thead th {
            white-space: nowrap;
        }
        
        .exception-table td[style*="max-width"] {
            max-width: 180px !important;
        }
    }
    
    @media (max-width: 768px) {
        .summary-stat-value {
            font-size: 24px;
        }
        
        .summary-stat-label {
            font-size: 12px;
        }
        
        .course-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        
        .exception-badge {
            align-self: flex-start;
        }
        
        .faculty-header {
            padding: 14px 16px;
            gap: 8px;
        }
        
        .faculty-header h5 {
            font-size: 14px;
        }
        
        .faculty-header .material-icons {
            font-size: 20px;
        }
        
        h4 {
            font-size: 1.1rem !important;
        }
        
        h4 .material-icons {
            font-size: 22px !important;
        }
    }
    
    @media (max-width: 576px) {
        .container-fluid {
            padding-left: 12px;
            padding-right: 12px;
        }
        
        .card-body.p-4 {
            padding: 0.75rem !important;
        }
        
        .exception-summary-card {
            padding: 16px 12px;
            margin-bottom: 20px;
        }
        
        .summary-stat-value {
            font-size: 20px;
        }
        
        .summary-stat-label {
            font-size: 11px;
        }
        
        .filter-section {
            padding: 12px;
        }
        
        .section-container {
            margin-bottom: 16px;
        }
        
        .table-header {
            padding: 12px 16px;
        }
        
        .table-header h6 {
            font-size: 12px;
        }
        
        .course-section {
            padding: 12px;
        }
        
        .student-records-section {
            padding: 12px;
        }
        
        .student-records-section .table-header {
            margin-left: -12px;
            margin-right: -12px;
            margin-top: -12px;
            width: calc(100% + 24px);
        }
        
        .table-responsive {
            margin-left: -12px;
            margin-right: -12px;
            padding-left: 12px;
            padding-right: 12px;
        }
        
        .exception-table thead th,
        .exception-table tbody td {
            font-size: 11px;
            padding: 8px 10px;
        }
        
        .exception-table {
            min-width: 700px;
        }
        
        .exception-badge {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn {
            padding: 8px 14px;
            font-size: 13px;
        }
        
        .empty-state {
            padding: 32px 16px;
        }
        
        .empty-state .material-icons {
            font-size: 48px;
        }
    }
    
    /* Print Styles */
    @media print {
        * {
            margin: 0;
            padding: 0;
        }
        
        body {
            background: white;
            color: var(--text-primary);
        }
        
        .container-fluid,
        .card-body {
            box-shadow: none !important;
            border: none !important;
            background: white !important;
        }
        
        .breadcrumb,
        .filter-section,
        .btn,
        button,
        .d-flex.justify-content-end {
            display: none !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        hr {
            border: 1px solid var(--divider) !important;
            margin: 20px 0 !important;
        }
        
        .exception-summary-card {
            box-shadow: none;
            border: 1px solid var(--border-color);
            background: white !important;
            color: var(--text-primary) !important;
            page-break-inside: avoid;
        }
        
        .section-container {
            box-shadow: none;
            border: 1px solid var(--border-color);
            page-break-inside: avoid;
            background: white !important;
        }
        
        .exception-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin: 10px 0 !important;
        }
        
        .exception-table thead th {
            background: var(--very-light) !important;
            border: 1px solid var(--border-color) !important;
            padding: 10px !important;
        }
        
        .exception-table tbody td {
            border: 1px solid var(--divider) !important;
            padding: 10px !important;
        }
        
        .table-header {
            background: var(--very-light) !important;
            border: 1px solid var(--border-color) !important;
            page-break-inside: avoid;
        }
        
        .faculty-header {
            background: var(--very-light) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
            page-break-inside: avoid;
        }
        
        .course-section {
            page-break-inside: avoid;
            border: 1px solid var(--border-color) !important;
        }
        
        h4, h5, h6 {
            color: var(--text-primary) !important;
            page-break-after: avoid;
        }
        
        .summary-stat-value,
        .exception-badge {
            color: var(--text-primary) !important;
        }
    }
    
    /* Print Window Specific */
    .print-hide {
        display: none !important;
    }
</style>

<div class="container-fluid">
    <x-breadcrum title="Faculty MDO/Escort Exception View"></x-breadcrum>
    <div class="card" style="border-left: 4px solid var(--primary-blue); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
        <div class="card-body p-4">
            <div class="row mb-4 align-items-center">
                <div class="col-6">
                    <h4 style="color: var(--text-primary); font-weight: 600;">
                        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 28px; vertical-align: middle; margin-right: 8px; color: var(--primary-blue);">assignment_ind</i>
                        Faculty MDO/Escort Exception View
                    </h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" onclick="printContent()" style="background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-light) 100%); border: none; border-radius: 6px; padding: 10px 16px; transition: var(--transition);">
                            <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px;">print</i>
                            Print
                        </button>
                    </div>
                </div>
            </div>
            
            <hr style="border-color: var(--divider);">
            
            @php
                // Check if this is a faculty login view
                $isFacultyView = isset($isFacultyView) && $isFacultyView === true;
            @endphp
            
            @if($isFacultyView && isset($courseMaster))
                <!-- Course Filter -->
                <div class="filter-section">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="course_filter" class="form-label">
                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 18px; vertical-align: middle; margin-right: 6px;">filter_alt</i>
                                Select Course
                            </label>
                            <select id="course_filter" class="form-select">
                                <option value="">-- All Courses --</option>
                                @foreach ($courseMaster as $id => $name)
                                    <option value="{{ $id }}" {{ isset($courseFilter) && $courseFilter == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endif
            
            @if($isFacultyView)
                <!-- Faculty Login View -->
                @if(isset($hasData) && $hasData && count($studentData) > 0)
                    <!-- Total Exceptions Summary -->
                    <div class="exception-summary-card">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="summary-stat">
                                    <span class="summary-stat-label">
                                        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 16px; vertical-align: middle;">assignment</i>
                                        Total Number of Exceptions
                                    </span>
                                    <span class="summary-stat-value">{{ $totalExceptions ?? 0 }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="summary-stat">
                                    <span class="summary-stat-label">
                                        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 16px; vertical-align: middle;">group</i>
                                        Total Students with Exceptions
                                    </span>
                                    <span class="summary-stat-value">{{ count($studentData) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student Data Table -->
                    <div class="section-container">
                        <div class="table-header">
                            <h6>
                                <i class="material-icons menu-icon material-symbols-rounded">group</i>
                                Student Exceptions
                            </h6>
                        </div>
                        <div class="student-records-section">
                            <div class="table-responsive">
                                <table class="exception-table">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>OT Code</th>
                                            <th>Email</th>
                                            <th>Faculty</th>
                                            <th>Course</th>
                                            <th>Date</th>
                                            <th>Duty Type</th>
                                            <th>Time</th>
                                            <th>Description</th>
                                            <th style="text-align: center;">Total Exceptions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $displayedRows = 0;
                                        @endphp
                                        @foreach($studentData as $student)
                                            @if(count($student['exemptions']) > 0)
                                                @foreach($student['exemptions'] as $exemption)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $student['student_name'] }}</strong>
                                                        </td>
                                                        <td>{{ $student['ot_code'] }}</td>
                                                        <td>{{ $student['email'] ?? 'N/A' }}</td>
                                                        <td>{{ $exemption['faculty'] ?? 'N/A' }}</td>
                                                        <td>{{ $exemption['course_name'] ?? 'N/A' }}</td>
                                                        <td>{{ $exemption['date'] ? \Carbon\Carbon::parse($exemption['date'])->format('d/m/Y') : 'N/A' }}</td>
                                                        <td>{{ $exemption['duty_type'] ?? 'N/A' }}</td>
                                                        <td>{{ $exemption['time'] ?? 'N/A' }}</td>
                                                        <td style="max-width: 250px; word-wrap: break-word;">
                                                            {{ $exemption['description'] && $exemption['description'] !== 'N/A' ? $exemption['description'] : '-' }}
                                                        </td>
                                                        <td style="text-align: center;">
                                                            @if($loop->first)
                                                                <span class="exception-badge">
                                                                    <i class="material-icons menu-icon material-symbols-rounded">assignment</i>
                                                                    {{ $student['total_exception_count'] }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @php $displayedRows++; @endphp
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($displayedRows === 0)
                                <div class="empty-state">
                                    <i class="material-icons menu-icon material-symbols-rounded">info</i>
                                    <p>No records found</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <!-- No records found -->
                    <div class="alert alert-info text-center">
                        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 48px;">info</i>
                        <p class="mt-2 fs-5">No records found</p>
                    </div>
                @endif
            @else
                <!-- Admin View -->
                <!-- Faculty Data with Hierarchical Table View -->
                @if(isset($facultyData) && count($facultyData) > 0)
                    @foreach($facultyData as $faculty)
                        <div class="section-container">
                            <!-- Faculty Header -->
                            <div class="faculty-header">
                                <i class="material-icons menu-icon material-symbols-rounded">person</i>
                                <h5 class="text-white">{{ $faculty['faculty_name'] }}</h5>
                            </div>
                            
                            <!-- Course Sections with Tables -->
                            @foreach($faculty['courses'] as $course)
                                <div class="course-section">
                                    <!-- Course Header -->
                                    <div class="course-header">
                                        <div class="course-title">
                                            <i class="material-icons menu-icon material-symbols-rounded">book</i>
                                            {{ $course['course_name'] }}
                                        </div>
                                        <div class="exception-badge">
                                            <i class="material-icons menu-icon material-symbols-rounded">assignment</i>
                                            {{ $course['duty_count'] }} Exception(s)
                                        </div>
                                    </div>
                                    
                                    <!-- Course Data Table -->
                                    @if($course['student_duties'] && count($course['student_duties']) > 0)
                                        <div class="table-responsive">
                                            <table class="exception-table">
                                                <thead>
                                                    <tr>
                                                        <th>Student Name</th>
                                                        <th>OT Code</th>
                                                        <th>Date</th>
                                                        <th>Duty Type</th>
                                                        <th>Time</th>
                                                        <th>Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($course['student_duties'] as $duty)
                                                        <tr>
                                                            <td><strong>{{ $duty['student_name'] }}</strong></td>
                                                            <td>{{ $duty['ot_code'] }}</td>
                                                            <td>{{ $duty['date'] ? \Carbon\Carbon::parse($duty['date'])->format('d/m/Y') : 'N/A' }}</td>
                                                            <td>{{ $duty['duty_type'] }}</td>
                                                            <td>{{ $duty['time'] }}</td>
                                                            <td style="max-width: 300px; word-wrap: break-word;">{{ $duty['description'] ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="empty-state" style="padding: 24px;">
                                            <p>No exceptions found for this course.</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @else
                    <div class="empty-state">
                        <i class="material-icons menu-icon material-symbols-rounded">info</i>
                        <p>No faculty data found matching the selected filters.</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // Print Content Function - Always Available
        function printContent() {
            // Get the card body content
            var printContent = document.querySelector('.card-body').innerHTML;
            var originalContent = document.body.innerHTML;
            
            // Create new window for printing
            var printWindow = window.open('', '', 'width=900,height=600');
            
            // Build the print document with styles
            var printDocument = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Faculty MDO/Escort Exception Report</title>
                    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
                    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@400;500;600;700&display=swap" rel="stylesheet">
                    <style>
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        
                        body {
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                            color: #1f2937;
                            background: white;
                            padding: 20px;
                            line-height: 1.5;
                        }
                        
                        .card-body {
                            background: white;
                            border: none;
                        }
                        
                        h4 {
                            color: #004a93;
                            margin: 20px 0 15px 0;
                            font-size: 22px;
                            font-weight: 600;
                        }
                        
                        h5, h6 {
                            color: #004a93;
                            margin: 15px 0 10px 0;
                            font-weight: 600;
                        }
                        
                        hr {
                            border: none;
                            border-bottom: 1px solid #e5e7eb;
                            margin: 15px 0;
                        }
                        
                        .exception-summary-card {
                            background: #f0f3f7;
                            border: 1px solid #d1d5db;
                            border-left: 4px solid #004a93;
                            border-radius: 8px;
                            padding: 20px;
                            margin: 20px 0;
                            page-break-inside: avoid;
                        }
                        
                        .row {
                            display: flex;
                            gap: 20px;
                            margin: 0;
                        }
                        
                        .col-md-6 {
                            flex: 1;
                        }
                        
                        .summary-stat {
                            display: flex;
                            flex-direction: column;
                            gap: 8px;
                        }
                        
                        .summary-stat-label {
                            font-size: 12px;
                            font-weight: 600;
                            color: #6b7280;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                        }
                        
                        .summary-stat-value {
                            font-size: 28px;
                            font-weight: 700;
                            color: #004a93;
                        }
                        
                        .section-container {
                            background: white;
                            border: 1px solid #d1d5db;
                            border-radius: 8px;
                            margin: 20px 0;
                            overflow: hidden;
                            page-break-inside: avoid;
                        }
                        
                        .table-header {
                            background: #f0f3f7;
                            border-bottom: 2px solid #004a93;
                            padding: 14px 16px;
                            margin: 0;
                        }
                        
                        .table-header h6 {
                            margin: 0;
                            color: #004a93;
                            font-size: 13px;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            display: flex;
                            align-items: center;
                            gap: 10px;
                        }
                        
                        .faculty-header {
                            background: #004a93;
                            color: white;
                            padding: 16px;
                            display: flex;
                            align-items: center;
                            gap: 12px;
                            page-break-inside: avoid;
                        }
                        
                        .faculty-header h5 {
                            margin: 0;
                            color: white;
                            font-size: 16px;
                        }
                        
                        .course-section {
                            border-bottom: 1px solid #e5e7eb;
                            padding: 16px;
                            background: white;
                            page-break-inside: avoid;
                        }
                        
                        .course-section:last-child {
                            border-bottom: none;
                        }
                        
                        .course-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            margin-bottom: 15px;
                            page-break-inside: avoid;
                        }
                        
                        .course-title {
                            display: flex;
                            align-items: center;
                            gap: 10px;
                            color: #1f2937;
                            font-weight: 600;
                            font-size: 15px;
                        }
                        
                        .exception-badge {
                            background: #f59e0b;
                            color: white;
                            font-weight: 700;
                            padding: 6px 12px;
                            border-radius: 20px;
                            font-size: 12px;
                            display: inline-flex;
                            align-items: center;
                            gap: 6px;
                        }
                        
                        .exception-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 0;
                            font-size: 13px;
                        }
                        
                        .exception-table thead {
                            background: #f0f3f7;
                        }
                        
                        .exception-table thead th {
                            background: #f0f3f7;
                            color: #004a93;
                            font-weight: 600;
                            font-size: 12px;
                            padding: 12px;
                            text-align: left;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            border-bottom: 1px solid #d1d5db;
                            white-space: nowrap;
                        }
                        
                        .exception-table tbody td {
                            padding: 12px;
                            border-bottom: 1px solid #e5e7eb;
                            color: #1f2937;
                        }
                        
                        .exception-table tbody tr:last-child td {
                            border-bottom: none;
                        }
                        
                        .material-icons {
                            font-size: 18px;
                            vertical-align: middle;
                            font-weight: normal;
                        }
                        
                        .table-responsive {
                            overflow-x: auto;
                        }
                        
                        .empty-state {
                            text-align: center;
                            padding: 40px 20px;
                            color: #6b7280;
                        }
                        
                        .alert {
                            padding: 15px;
                            border-radius: 6px;
                            margin: 20px 0;
                            border: 1px solid #dbeafe;
                            background: #eff6ff;
                            color: #1e40af;
                        }
                        
                        .text-center {
                            text-align: center;
                        }
                        
                        strong {
                            font-weight: 600;
                            color: #1f2937;
                        }
                        
                        @media print {
                            body {
                                padding: 0;
                            }
                            
                            .section-container {
                                page-break-inside: avoid;
                                border: none;
                                margin: 20px 0;
                            }
                            
                            table {
                                page-break-inside: avoid;
                            }
                            
                            tr {
                                page-break-inside: avoid;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="card-body">
                        \${printContent}
                    </div>
                </body>
                </html>
            `;
            
            // Write to the new window
            printWindow.document.write(printDocument);
            printWindow.document.close();
            
            // Wait for content to load, then print
            setTimeout(function() {
                printWindow.focus();
                printWindow.print();
            }, 250);
        }
        
        // Course filter handler for faculty view
        $(document).ready(function() {
            if ($('#course_filter').length > 0) {
                $('#course_filter').on('change', function() {
                    var courseFilter = $(this).val();
                    var url = new URL(window.location.href);
                    
                    if (courseFilter) {
                        url.searchParams.set('course_filter', courseFilter);
                    } else {
                        url.searchParams.delete('course_filter');
                    }
                    
                    window.location.href = url.toString();
                });
            }
        });
    </script>
@endpush

@endsection

