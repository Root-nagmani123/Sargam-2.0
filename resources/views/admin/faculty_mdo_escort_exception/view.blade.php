@extends('admin.layouts.master')

@section('title', 'Faculty MDO/Escort Exception View - Sargam | Lal Bahadur')

@push('styles')
{{-- Module stylesheet (docs/new-design-index-page.md §7) — this page used to
     carry ~420 lines of the same rules in an inline <style> block. The second
     <style> further down belongs to the print window and stays inline. --}}
<link rel="stylesheet"
    href="{{ asset('css/faculty-escort-exception-admin.css') }}?v={{ @filemtime(public_path('css/faculty-escort-exception-admin.css')) }}">
@endpush

@section('setup_content')

<div class="container-fluid fme-page">
    <x-breadcrum title="Faculty MDO/Escort Exception View" :showBack="false">
        <button type="button" class="btn fme-export-btn" onclick="printContent()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card border-0 shadow-sm rounded-1 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            
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
                                <i class="bi bi-funnel me-1" aria-hidden="true"></i>
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
                                        <i class="bi bi-clipboard-check me-1" aria-hidden="true"></i>
                                        Total Number of Exceptions
                                    </span>
                                    <span class="summary-stat-value">{{ $totalExceptions ?? 0 }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="summary-stat">
                                    <span class="summary-stat-label">
                                        <i class="bi bi-people me-1" aria-hidden="true"></i>
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
                                <i class="bi bi-people" aria-hidden="true"></i>
                                Student Exceptions
                            </h6>
                        </div>
                        <div class="student-records-section">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 w-100 programme-dt-table exception-table">
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
                                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                                    <p>No records found</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <!-- No records found -->
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle" style="font-size: 2.5rem;" aria-hidden="true"></i>
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
                                <i class="bi bi-person-circle" aria-hidden="true"></i>
                                <h5 class="text-white mb-0">{{ $faculty['faculty_name'] }}</h5>
                            </div>
                            
                            <!-- Course Sections with Tables -->
                            @foreach($faculty['courses'] as $course)
                                <div class="course-section">
                                    <!-- Course Header -->
                                    <div class="course-header">
                                        <div class="course-title">
                                            <i class="bi bi-journal-text" aria-hidden="true"></i>
                                            {{ $course['course_name'] }}
                                        </div>
                                        <div class="exception-badge">
                                            {{ $course['duty_count'] }} Exception(s)
                                        </div>
                                    </div>
                                    
                                    <!-- Course Data Table -->
                                    @if($course['student_duties'] && count($course['student_duties']) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0 w-100 programme-dt-table exception-table">
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
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
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
                    <link href="{{ asset('admin_assets/css/material-icons-local.css') }}" rel="stylesheet">
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

