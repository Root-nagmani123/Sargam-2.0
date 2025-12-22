@extends('admin.layouts.master')

@section('title', 'My Attendance')

@section('css')

<style>
:root {
    /* GIGW-compliant Government Color Palette */
    --gigw-primary: #0056a3;
    --gigw-primary-dark: #003d7a;
    --gigw-primary-light: #e3f2fd;
    --gigw-secondary: #f57c00;
    --gigw-success: #2e7d32;
    --gigw-info: #0277bd;
    --gigw-warning: #f57f17;
    --gigw-danger: #c62828;
    --gigw-light: #f5f7fa;
    --gigw-dark: #263238;
    --gigw-border: #dce1e6;
    --gigw-shadow: rgba(0, 86, 163, 0.08);
    --gigw-hover: rgba(0, 86, 163, 0.04);
}

/* Modern Card Styling */
.modern-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--gigw-shadow);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background: #ffffff;
}

.modern-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px var(--gigw-shadow);
}

.modern-card-header {
    background: linear-gradient(135deg, var(--gigw-primary) 0%, var(--gigw-primary-dark) 100%);
    border: none;
    border-radius: 16px 16px 0 0 !important;
    padding: 1.5rem;
    color: white;
}

.info-badge {
    background: linear-gradient(135deg, #ffffff15, #ffffff25);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 1rem 1.5rem;
    transition: all 0.3s ease;
}

.info-badge:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
}

/* Enhanced Button Styling */
.btn-group[role="group"] .btn {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 0;
    font-weight: 500;
    letter-spacing: 0.3px;
    position: relative;
    overflow: hidden;
}

.btn-group[role="group"] .btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-group[role="group"] .btn:hover::before {
    width: 300px;
    height: 300px;
}

.btn-group[role="group"] .btn:first-child {
    border-top-left-radius: 50rem !important;
    border-bottom-left-radius: 50rem !important;
}

.btn-group[role="group"] .btn:last-child {
    border-top-right-radius: 50rem !important;
    border-bottom-right-radius: 50rem !important;
}

.btn-group .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px var(--gigw-shadow);
}

.btn-group .btn.active {
    box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 86, 163, 0.3);
}

/* WCAG 2.1 AA Compliant Focus States */
.btn:focus-visible,
.form-control:focus,
.form-select:focus {
    outline: 3px solid var(--gigw-primary);
    outline-offset: 3px;
    box-shadow: 0 0 0 0.25rem rgba(0, 86, 163, 0.25);
}

.btn-modern-primary {
    background: linear-gradient(135deg, var(--gigw-primary) 0%, var(--gigw-primary-dark) 100%);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px var(--gigw-shadow);
    transition: all 0.3s ease;
}

.btn-modern-primary:hover {
    background: linear-gradient(135deg, var(--gigw-primary-dark) 0%, var(--gigw-primary) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px var(--gigw-shadow);
    color: white;
}

.btn-modern-outline {
    background: white;
    border: 2px solid var(--gigw-primary);
    color: var(--gigw-primary);
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.btn-modern-outline:hover {
    background: var(--gigw-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px var(--gigw-shadow);
}

/* Enhanced Form Controls */
.modern-form-control,
.modern-form-select {
    border: 2px solid var(--gigw-border);
    border-radius: 12px;
    padding: 0.875rem 1.25rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
}

.modern-form-control:focus,
.modern-form-select:focus {
    border-color: var(--gigw-primary);
    background: var(--gigw-primary-light);
    box-shadow: 0 0 0 0.25rem rgba(0, 86, 163, 0.1);
}

.form-label-modern {
    font-weight: 600;
    color: var(--gigw-dark);
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Enhanced Table Styling */
.modern-table {
    border-collapse: separate;
    border-spacing: 0;
}

.modern-table thead {
    background: linear-gradient(135deg, var(--gigw-primary) 0%, var(--gigw-primary-dark) 100%);
    color: white;
}

.modern-table thead th {
    padding: 1.25rem 1rem;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
    white-space: nowrap;
}

.modern-table thead th:first-child {
    border-top-left-radius: 12px;
}

.modern-table thead th:last-child {
    border-top-right-radius: 12px;
}

.modern-table tbody tr {
    transition: all 0.3s ease;
    background: white;
}

.modern-table tbody tr:hover {
    background: var(--gigw-hover);
    transform: scale(1.01);
    box-shadow: 0 4px 12px var(--gigw-shadow);
}

.modern-table tbody td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid var(--gigw-border);
    vertical-align: middle;
    font-size: 0.95rem;
}

/* Modern Badge Styling */
.badge-modern {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    letter-spacing: 0.3px;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.badge-present {
    background: linear-gradient(135deg, #2e7d32 0%, #388e3c 100%);
    color: white;
}

.badge-absent {
    background: linear-gradient(135deg, #c62828 0%, #d32f2f 100%);
    color: white;
}

.badge-leave {
    background: linear-gradient(135deg, #f57f17 0%, #f9a825 100%);
    color: white;
}

/* Responsive Styles */
@media (max-width: 1200px) {
    .modern-table {
        font-size: 0.9rem;
    }
    
    .modern-table th, .modern-table td {
        padding: 1rem 0.75rem;
    }
    
    .badge-modern {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
    
    .modern-card-header {
        padding: 1.25rem;
    }
}

@media (max-width: 991px) {
    .row.g-3, .row.g-4 {
        gap: 1rem !important;
    }
    
    .col-lg-5, .col-lg-2, .col-md-6, .col-md-4 {
        width: 100%;
        margin-bottom: 1rem;
    }
    
    .table {
        font-size: 0.85rem;
    }
    
    .table th, .table td {
        padding: 0.5rem 0.3rem;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.6rem;
    }
    
    .btn-sm {
        padding: 0.35rem 0.6rem;
        font-size: 0.75rem;
    }
    
    .btn-lg {
        padding: 0.6rem 1rem;
        font-size: 0.95rem;
    }
    
    .d-flex.justify-content-between {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .form-control-lg, .form-select-lg {
        font-size: 0.95rem;
        padding: 0.6rem;
    }
    
    h4.card-title {
        font-size: 1.1rem;
    }
    
    h5 {
        font-size: 1rem;
    }
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem !important;
    }
    
    .modern-card-header {
        padding: 0.875rem 1rem !important;
        border-radius: 12px 12px 0 0 !important;
    }
    
    .modern-table {
        font-size: 0.8rem;
    }
    
    .modern-table th, .modern-table td {
        padding: 0.75rem 0.5rem;
        word-break: break-word;
    }
    
    .modern-card {
        border-radius: 10px;
    }
    
    .form-label-modern {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .modern-form-control,
    .modern-form-select {
        font-size: 0.9rem;
        padding: 0.75rem 1rem;
        border-radius: 10px;
    }
    
    .btn-modern-primary,
    .btn-modern-outline {
        padding: 0.625rem 1.5rem;
        border-radius: 10px;
    }
    
    .col-lg-2.d-flex,
    .col-md-12.d-flex {
        width: 100%;
        flex-direction: row;
        gap: 0.5rem;
    }
    
    .col-lg-2.d-flex .btn {
        flex: 1;
    }
    
    .badge-modern {
        font-size: 0.7rem;
        padding: 0.35rem 0.6rem;
        border-radius: 6px;
    }
    
    .bi {
        font-size: 0.9rem;
    }
    
    .info-badge {
        padding: 0.75rem 1rem;
        border-radius: 10px;
    }
}

@media (max-width: 576px) {
    .d-flex.justify-content-between.align-items-center {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.75rem;
    }
    
    .modern-table {
        font-size: 0.75rem;
    }
    
    .modern-table th, .modern-table td {
        padding: 0.625rem 0.375rem;
        white-space: normal;
        min-width: 80px;
    }
    
    .modern-table th:first-child,
    .modern-table td:first-child {
        position: sticky;
        left: 0;
        background: white;
        z-index: 1;
        box-shadow: 2px 0 4px var(--gigw-shadow);
    }
    
    .modern-table thead th {
        position: sticky;
        top: 0;
        background: linear-gradient(135deg, var(--gigw-primary) 0%, var(--gigw-primary-dark) 100%);
        z-index: 2;
    }
    
    .modern-card {
        border-radius: 8px;
    }
    
    .modern-card-header {
        border-radius: 8px 8px 0 0 !important;
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .col-md-4 {
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }
    
    .btn {
        padding: 0.35rem 0.7rem;
        font-size: 0.8rem;
        width: 100%;
    }
    
    .btn-sm {
        padding: 0.3rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .btn-lg {
        padding: 0.45rem 0.8rem;
        font-size: 0.85rem;
    }
    
    .badge-modern {
        font-size: 0.65rem;
        padding: 0.3rem 0.5rem;
        border-radius: 5px;
    }
    
    strong {
        font-size: 0.85rem;
    }
    
    .text-primary {
        font-size: 0.85rem;
    }
    
    .modern-form-control,
    .modern-form-select {
        font-size: 0.85rem;
        padding: 0.625rem 0.875rem;
        border-radius: 8px;
    }
    
    .btn-modern-primary,
    .btn-modern-outline {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
    }
    
    .btn-group[role="group"] {
        width: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .btn-group[role="group"] .btn {
        font-size: 0.8rem;
        padding: 0.5rem;
        border-radius: 0 !important;
        width: 100%;
    }
    
    .btn-group[role="group"] .btn:first-child {
        border-top-left-radius: 0.5rem !important;
        border-top-right-radius: 0.5rem !important;
        border-bottom-left-radius: 0 !important;
    }
    
    .btn-group[role="group"] .btn:last-child {
        border-top-right-radius: 0 !important;
        border-bottom-left-radius: 0.5rem !important;
        border-bottom-right-radius: 0.5rem !important;
    }
    
    .form-label-modern {
        font-size: 0.85rem;
        gap: 0.375rem;
    }
    
    .table-responsive {
        margin-bottom: 1rem;
    }
    
    .text-muted.small {
        font-size: 0.7rem;
    }
    
    .bi {
        font-size: 0.85rem;
    }
    
    .card-header h4,
    .card-header h5 {
        font-size: 0.9rem;
    }
    
    hr {
        margin: 1rem 0;
    }
    
    .rounded-4 {
        border-radius: 0.75rem !important;
    }
    
    .fw-bold {
        font-weight: 600 !important;
    }
    
    .col-lg-2.d-flex {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .col-lg-2.d-flex .btn {
        width: 100%;
        margin: 0;
    }
}

/* GIGW Accessibility Compliance - WCAG 2.1 AA */
.modern-form-control:focus,
.modern-form-select:focus,
.btn:focus,
.btn:focus-visible {
    outline: 3px solid var(--gigw-primary);
    outline-offset: 3px;
    box-shadow: 0 0 0 0.25rem rgba(0, 86, 163, 0.25);
}

/* Skip to main content link - GIGW requirement */
.skip-to-main {
    position: absolute;
    left: -9999px;
    z-index: 999;
    padding: 1rem 1.5rem;
    background-color: var(--gigw-primary);
    color: white;
    text-decoration: none;
    border-radius: 0 0 8px 0;
    font-weight: 600;
}

.skip-to-main:focus {
    left: 0;
    top: 0;
}

/* Screen reader only content */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

/* Loading Animation */
@keyframes shimmer {
    0% { background-position: -1000px 0; }
    100% { background-position: 1000px 0; }
}

.loading-shimmer {
    animation: shimmer 2s infinite;
    background: linear-gradient(to right, #f0f0f0 4%, #e0e0e0 25%, #f0f0f0 36%);
    background-size: 1000px 100%;
}

/* Smooth transitions */
.btn, .badge-modern, .modern-card, .modern-form-control, .modern-form-select {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Print Styles - GIGW Requirement */
@media print {
    .no-print,
    .btn,
    .skip-to-main,
    .modern-card-header {
        display: none !important;
    }
    
    .modern-card {
        box-shadow: none;
        border: 1px solid #000;
    }
    
    .modern-table {
        page-break-inside: auto;
    }
    
    .modern-table tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    body {
        font-size: 12pt;
    }
}

/* High Contrast Mode Support - GIGW Accessibility */
@media (prefers-contrast: high) {
    :root {
        --gigw-primary: #0046a3;
        --gigw-border: #000;
    }
    
    .modern-card {
        border: 2px solid #000;
    }
    
    .modern-table tbody tr:hover {
        background: #000;
        color: #fff;
    }
}

/* Reduced Motion Support - WCAG 2.1 */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Ensure pagination responsive */
.pagination {
    flex-wrap: wrap;
    gap: 0.25rem;
}

.pagination .page-link {
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    margin: 0 0.125rem;
    border: 2px solid var(--gigw-border);
    color: var(--gigw-primary);
    font-weight: 500;
    transition: all 0.3s ease;
}

.pagination .page-link:hover {
    background: var(--gigw-primary);
    color: white;
    border-color: var(--gigw-primary);
    transform: translateY(-2px);
}

.pagination .page-item.active .page-link {
    background: var(--gigw-primary);
    border-color: var(--gigw-primary);
}

@media (max-width: 576px) {
    .pagination .page-link {
        padding: 0.35rem 0.5rem;
        font-size: 0.75rem;
    }
}

.table-responsive {
    -webkit-overflow-scrolling: touch;
    min-height: 0;
}
</style>
@endsection

@section('setup_content')
<!-- Skip to main content - GIGW Accessibility Requirement -->
<a href="#main-content" class="skip-to-main" aria-label="Skip to main content">Skip to main content</a>

<div class="container-fluid" id="main-content" role="main">
    <x-breadcrum title="My Attendance Record" />
    <x-session_message />

    {{-- Modern Student Information Header --}}
    <div class="modern-card mb-4" role="region" aria-label="Student Information">
        <div class="modern-card-header">
            <h5 class="mb-0 fw-bold d-flex align-items-center text-white">
                <i class="bi bi-person-badge me-2" aria-hidden="true"></i>
                <span>Student Information</span>
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="info-badge h-100">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-book-fill text-white me-2" style="font-size: 1.5rem;" aria-hidden="true"></i>
                            <span class="text-white-50 small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Course</span>
                        </div>
                        <h6 class="mb-0 text-white fw-bold" style="font-size: 1.1rem;">
                            {{ $course->course_name ?? 'N/A' }}
                        </h6>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-badge h-100">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-person-fill text-white me-2" style="font-size: 1.5rem;" aria-hidden="true"></i>
                            <span class="text-white-50 small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Student Name</span>
                        </div>
                        <h6 class="mb-0 text-white fw-bold" style="font-size: 1.1rem;">
                            {{ $student->display_name ?? 'N/A' }}
                        </h6>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-badge h-100">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-hash text-white me-2" style="font-size: 1.5rem;" aria-hidden="true"></i>
                            <span class="text-white-50 small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">OT Code</span>
                        </div>
                        <h6 class="mb-0 text-white fw-bold" style="font-size: 1.1rem;">
                            {{ $student->generated_OT_code ?? 'N/A' }}
                        </h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Enhanced Filter Form --}}
   <div class="modern-card mb-4" role="search" aria-label="Attendance Filters">
        <div class="modern-card-header">
            <h5 class="mb-0 fw-bold d-flex align-items-center text-white">
                <i class="bi bi-funnel-fill me-2" aria-hidden="true"></i>
                <span>Attendance Filters</span>
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="GET" action="{{ route('attendance.OT.student_mark.student', [
            'group_pk' => $group_pk,
            'course_pk' => $course_pk,
            'timetable_pk' => $timetable_pk,
            'student_pk' => $student_pk
        ]) }}" id="filterForm">
                <input type="hidden" name="archive_mode" id="archive_mode_input" value="{{ $archiveMode ?? 'active' }}">

                <div class="row mb-4">
                    <div class="col-12">
                        <label class="form-label-modern text-muted">
                            <i class="bi bi-eye-fill" aria-hidden="true"></i>
                            <span>View Mode</span>
                        </label>
                        <div class="btn-group border border-2 border-primary rounded-pill overflow-hidden w-100 w-md-auto" role="group"
                            aria-label="Attendance Status Filter">
                            <button type="button"
                                class="btn btn-sm text-decoration-none px-4 py-2 fw-semibold"
                                id="filterArchive_active"
                                aria-pressed="true"
                                aria-label="Show active attendance records">
                                <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i> Active Records
                            </button>
                            <button type="button"
                                class="btn btn-sm text-decoration-none px-4 py-2 fw-semibold"
                                id="filterArchive"
                                aria-pressed="false"
                                aria-label="Show archived attendance records">
                                <i class="bi bi-archive-fill me-2" aria-hidden="true"></i> Archived Records
                            </button>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-4">
                    <div class="coursehide col-lg-4 col-md-6">
                        <label for="filter_course" class="form-label-modern">
                            <i class="bi bi-book-fill text-primary" aria-hidden="true"></i>
                            <span>Course</span>
                        </label>
                         <select class="modern-form-select select2" id="filter_course"
                            name="filter_course" aria-label="Filter attendance records by course">
                            <option value="">All Courses</option>
                            @foreach($archivedCourses as $archivedCourse)
                            <option value="{{ $archivedCourse->pk }}">
                                {{ $archivedCourse->course_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
               
                    
                    <div class="archive-column col-lg-4 col-md-6">
                        <label for="filter_date" class="form-label-modern">
                            <i class="bi bi-calendar-event-fill text-primary" aria-hidden="true"></i>
                            <span>Date</span>
                        </label>
                        <input type="date" 
                            class="modern-form-control" 
                            id="filter_date" 
                            name="filter_date"
                            value="{{ $filterDate ?? '' }}" 
                            max="{{ date('Y-m-d') }}" 
                            aria-label="Filter attendance records by date"
                            aria-describedby="date-help">
                        <small id="date-help" class="form-text text-muted mt-1 d-block">
                            Select a specific date to view attendance
                        </small>
                    </div>
                    <div class="archive-column col-lg-4 col-md-6">
                        <label for="filter_session_time" class="form-label-modern">
                            <i class="bi bi-clock-fill text-primary" aria-hidden="true"></i>
                            <span>Session Time</span>
                        </label>
                        <select class="modern-form-select select2" 
                            id="filter_session_time"
                            name="filter_session_time" 
                            aria-label="Filter attendance records by session time">
                            <option value="">All Sessions</option>
                            @foreach($maunalSessions as $manualSession)
                            <option value="{{ $manualSession->class_session }}">
                                {{ $manualSession->class_session }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-12 d-flex align-items-end gap-3 mt-4 no-print">
                        <button type="button" 
                            class="btn btn-modern-outline flex-grow-1" 
                            id="clearFilters"
                            aria-label="Clear all filters">
                            <i class="bi bi-x-circle-fill me-2" aria-hidden="true"></i>
                            <span>Clear Filters</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>



    {{-- Enhanced Attendance Details Table --}}
    <div class="modern-card" role="region" aria-label="Attendance Details Table">
        <div class="modern-card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h4 class="mb-0 fw-bold text-white d-flex align-items-center">
                    <i class="bi bi-table me-2" aria-hidden="true"></i>
                    <span>Attendance Records</span>
                </h4>
                <div class="d-flex gap-2 no-print">
                    <button type="button" 
                        class="btn btn-light btn-sm" 
                        onclick="window.print()"
                        aria-label="Print attendance records">
                        <i class="bi bi-printer-fill me-1" aria-hidden="true"></i>
                        <span>Print</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
          <input type="hidden" name="group_pk" id="group_pk" value="{{ $group_pk }}">
          <input type="hidden" name="course_pk" id="course_pk" value="{{ $course_pk }}">
          <input type="hidden" name="timetable_pk" id="timetable_pk" value="{{ $timetable_pk }}">
          <input type="hidden" name="student_pk" id="student_pk" value="{{ $student_pk }}">
            <div class="table-responsive">
                <table style="width:100%; min-width:1580px;" 
                    class="modern-table table align-middle mb-0" 
                    id="attendanceTable"
                    role="table"
                    aria-label="Student attendance records">
                    <thead role="rowgroup">
                        <tr role="row"> 
                            <th scope="col" role="columnheader" aria-label="Serial number">#</th>
                            <th scope="col" role="columnheader" class="text-nowrap">Date & Time</th>
                            <th scope="col" role="columnheader">Venue</th>
                            <th scope="col" role="columnheader">Group</th>
                            <th scope="col" role="columnheader">Topic</th>
                            <th scope="col" role="columnheader">Faculty</th>
                            <th scope="col" role="columnheader" class="text-center text-nowrap">Attendance Status</th>
                            <th scope="col" role="columnheader" class="text-center text-nowrap">Duty Type</th>
                            <th scope="col" role="columnheader" class="text-center">Exemption</th>
                            <th scope="col" role="columnheader" class="text-center">Document / Comment</th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                        <!-- Data populated by DataTables -->
                    </tbody>
                </table>
            </div>
           
        </div>
    </div>

</div>



@endsection
@section('scripts')

<script>
$(function () {
    //alert('sfsdf');
    let table = $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('ot.student.attendance.data')}}",
            data: function (d) {
                d.filter_session_time = $('#filter_session_time').val();
                d.archive_mode = $('#archive_mode').val();
                d.filter_date = $('#filter_date').val();

                d.group_pk = $('#group_pk').val();
                d.course_pk = $('#course_pk').val();
                d.timetable_pk = $('#filter_date').val();
                d.student_pk = $('#student_pk').val();
            }
        },
        columns: [
    {
        data: 'DT_RowIndex',
        orderable: false,
        searchable: false   // ⭐ IMPORTANT
    },
    { data: 'date', name: 'date' },
    { data: 'venue', name: 'venue' },
    { data: 'group', name: 'group' },
    { data: 'topic', name: 'topic' },
    { data: 'faculty', name: 'faculty' },
    { data: 'attendance_status', name: 'attendance_status' },
    { data: 'duty_type', name: 'duty_type' },
    { data: 'exemption_type', name: 'exemption_type' }
]
    });


    $('#filter_date').on('change', function() {
         var date = $('#filter_date').val();
        table.ajax.reload();
    });

    $('#filterArchive_active').on('click', function () {
        let archive_mode = $(this).attr('aria-pressed');
      //   alert(archive_mode);
        table.ajax.reload();
    });

    $('#filterArchive').on('click', function () {
        let archive_mode = $(this).attr('aria-pressed');
     //    alert(archive_mode);
        table.ajax.reload();
    });

    $('#filter_course').on('change', function () {
        //alert('sfsdf');
        let course_pk =  $('#filter_course').val();
     //    alert(archive_mode);
        table.ajax.reload();
    });

    $('#filter_session_time').on('change', function () {
        //alert('sfsdf');
        let filter_session_time =  $('#filter_session_time').val();
     //    alert(archive_mode);
        table.ajax.reload();
    });
    
  $('#clearFilters').on('click', function () {
        // Reset filters
        $('#filter_date').val('');
        $('#filter_session_time').val('');
        $('#filter_course').val('');
        $('#archive_mode').val('');

        // Reload full data (preloaded AJAX)
        table.ajax.reload(null, true); // true = go to page 1
    });
    
});


$(document).ready(function () {
    // Initialize state
    $('.coursehide').hide();
    $('#filterArchive_active').addClass('bg-primary text-white shadow-sm');
    
    // Archive button click handler
    $('#filterArchive').on('click', function () {
        let isArchiveActive = $(this).attr('aria-pressed');
        if(isArchiveActive == 'false'){
            // Update layout
            $('.archive-column')
                .removeClass('col-lg-5')
                .addClass('col-lg-4');
            
            // Update button states
            $('#filterArchive_active')
                .removeClass('bg-primary text-white shadow-sm')
                .attr('aria-pressed', 'false');
            $(this)
                .addClass('bg-primary text-white shadow-sm')
                .attr('aria-pressed', 'true');
            
            // Show course filter with animation
            $('.coursehide').slideDown(300);
            
            // Announce to screen readers
            announceToScreenReader('Switched to archived records view');
        }
    });
    
    // Active button click handler
    $('#filterArchive_active').on('click', function () {
        let isArchiveActive = $(this).attr('aria-pressed');
        if(isArchiveActive == 'false'){
            // Update layout
            $('.archive-column').removeClass('col-lg-4');
            
            // Hide course filter with animation
            $('.coursehide').slideUp(300);
            
            // Update button states
            $('#filterArchive')
                .removeClass('bg-primary text-white shadow-sm')
                .attr('aria-pressed', 'false');
            $(this)
                .addClass('bg-primary text-white shadow-sm')
                .attr('aria-pressed', 'true');
            
            // Announce to screen readers
            announceToScreenReader('Switched to active records view');
        }
    });
    
    // Accessibility: Announce messages to screen readers
    function announceToScreenReader(message) {
        let announcement = $('<div>', {
            'role': 'status',
            'aria-live': 'polite',
            'aria-atomic': 'true',
            'class': 'sr-only position-absolute',
            'text': message
        });
        $('body').append(announcement);
        setTimeout(function() {
            announcement.remove();
        }, 1000);
    }
    
    // Enhance select2 for accessibility
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownAutoWidth: true,
            language: {
                noResults: function() {
                    return 'No results found';
                },
                searching: function() {
                    return 'Searching...';
                }
            }
        });
    }
});
</script>

@endsection