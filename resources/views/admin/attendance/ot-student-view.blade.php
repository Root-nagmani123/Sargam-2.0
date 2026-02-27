@extends(hasRole('Student-OT') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', 'Academic TimeTable - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section(hasRole('Student-OT') ? 'content' : 'setup_content')

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
    @endif

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
                    {{-- Course Filter - Only show in Archive mode --}}
                    @if(($archiveMode ?? 'active') === 'archive')
                    <div class="col-lg-4 col-md-6">
                        <label for="filter_course" class="form-label fw-semibold">
                            <i class="bi bi-book me-1 text-primary"></i> Course:
                        </label>
                        <select class="form-select form-select-lg select2" id="filter_course"
                            name="filter_course" aria-label="Filter by Course">
                            <option value="">-- Select Course --</option>
                                @foreach($archivedCourses as $archivedCourse)
                            <option value="{{ $archivedCourse->pk }}"
                                {{ $filterCourse == $archivedCourse->pk ? 'selected' : '' }}>
                                {{ $archivedCourse->course_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    
                    <div class="{{ ($archiveMode ?? 'active') === 'archive' ? 'col-lg-4' : 'col-lg-5' }} col-md-6">
                        <label for="filter_date" class="form-label fw-semibold">
                            <i class="bi bi-calendar-date me-1 text-primary"></i> Date:
                        </label>
                        <input type="date" class="form-control form-control-lg" id="filter_date" name="filter_date"
                            value="{{ $filterDate ?? '' }}" aria-label="Filter by Date">
                    </div>
                    <div class="{{ ($archiveMode ?? 'active') === 'archive' ? 'col-lg-4' : 'col-lg-5' }} col-md-6">
                        <label for="filter_session_time" class="form-label fw-semibold">
                            <i class="bi bi-clock-history me-1 text-primary"></i> Session Time:
                        </label>
                        <select class="form-select form-select-lg select2" id="filter_session_time"
                            name="filter_session_time" aria-label="Filter by Session Time">
                            <option value="">-- Select Session Time --</option>
                            @foreach($sessions as $session)
                            <option value="{{ $session->pk }}"
                                {{ $filterSessionTime == $session->pk ? 'selected' : '' }}>
                                {{ $session->shift_name }} ({{ $session->start_time }} - {{ $session->end_time }})
                            </option>
                            @endforeach
                            @foreach($maunalSessions as $manualSession)
                            <option value="{{ $manualSession->class_session }}"
                                {{ $filterSessionTime == $manualSession->class_session ? 'selected' : '' }}>
                                {{ $manualSession->class_session }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-12 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 fw-bold btn-lg me-2" id="applyFilters">
                            <i class="bi bi-search"></i> Apply
                        </button>
                        <button type="button" class="btn btn-outline-secondary w-100 btn-lg" id="clearFilters">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('filterForm');
        const archiveModeInput = document.getElementById('archive_mode_input');
        const filterActive = document.getElementById('filterActive');
        const filterArchive = document.getElementById('filterArchive');
        const clearFilters = document.getElementById('clearFilters');
        const applyFilters = document.getElementById('applyFilters');

        // 1. Toggle Button Logic
        function setArchiveMode(mode) {
            archiveModeInput.value = mode;
            // The form submission will handle the class updates via blade based on the new URL parameter
            form.submit();
        }

        filterActive.addEventListener('click', function() {
            if (archiveModeInput.value !== 'active') {
                setArchiveMode('active');
            }
        });

        filterArchive.addEventListener('click', function() {
            if (archiveModeInput.value !== 'archive') {
                setArchiveMode('archive');
            }
        });

        // 2. Clear Filters Logic
        clearFilters.addEventListener('click', function() {
            // Clear standard filter fields
            document.getElementById('filter_date').value = '';
            const sessionSelect = document.getElementById('filter_session_time');
            sessionSelect.value = ''; // Set to the default 'Select Session Time' option
            
            // Clear course filter if it exists (only in archive mode)
            const courseSelect = document.getElementById('filter_course');
            if (courseSelect) {
                courseSelect.value = '';
                // If select2 is initialized, trigger change
                if ($.fn.select2 && $(courseSelect).hasClass('select2-hidden-accessible')) {
                    $(courseSelect).val('').trigger('change');
                }
            }

            // Re-apply the current archive mode for context
            archiveModeInput.value = '{{ $archiveMode ?? '
            active ' }}';

            // Submit the form with cleared filters
            form.submit();
        });

        // 3. Apply Filters Logic (Ensure it explicitly submits the form)
        applyFilters.addEventListener('click', function(e) {
            e.preventDefault(); // Stop default button action
            form.submit(); // Explicitly submit the form
        });

        // 4. Accessibility (GIGW) - Ensure filter changes submit the form
        // Add listeners for changes to submit automatically, or rely on explicit 'Apply' button
        // For better control and performance, rely on the explicit 'Apply' button for main filters,
        // but the 'View Mode' toggle submits instantly.

    });
    </script>

    {{-- Attendance Details Table --}}
    <div class="card shadow">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-semibold">Attendance Details</h4>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h4 class="mb-0 fw-bold d-flex align-items-center">
                    <span>Attendance Records</span>
                </h4>
                <div class="d-flex gap-2 no-print">
                    <button type="button" class="btn btn-light btn-sm" onclick="window.print()"
                        aria-label="Print attendance records">
                        <i class="bi bi-printer-fill me-1" aria-hidden="true"></i>
                        <span>Print</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if(count($attendanceRecords) > 0)
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
            @else
            <div class="alert alert-info text-center m-4" role="alert">
                <i class="bi bi-info-circle me-2"></i> No attendance records found for the selected filters.
            </div>
            @endif
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
    </script>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    //alert('sfsdf');
    let table = $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('ot.student.attendance.data')}}",
            data: function(d) {
                d.filter_session_time = $('#filter_session_time').val();
                d.archive_mode = $('#archive_mode').val();
                d.filter_date = $('#filter_date').val();

                d.group_pk = $('#group_pk').val();
                d.course_pk = $('#course_pk').val();
                d.timetable_pk = $('#filter_date').val();
                d.student_pk = $('#student_pk').val();
            }
        }

    // Auto-submit form when filters change
    let filterTimeout;
    $('#filter_date, #filter_session_time, #filter_course').on('change', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            $('#filterForm').submit();
        }, 300); // Small delay to avoid multiple submissions
    });

    // Clear filters button
    $('#clearFilters').on('click', function() {
        $('#filter_date').val('');
        $('#filter_session_time').val('').trigger('change');
        if ($.fn.select2) {
            $('#filter_session_time').select2('val', '');
        }
        // Clear course filter if it exists (only in archive mode)
        const courseSelect = $('#filter_course');
        if (courseSelect.length) {
            courseSelect.val('').trigger('change');
            if ($.fn.select2 && courseSelect.hasClass('select2-hidden-accessible')) {
                courseSelect.select2('val', '');
            }
        }
        // Reset to active mode
        setActiveButton($('#filterActive'));
        $('#archive_mode_input').val('active');
        $('#filterForm').submit();
    });
});
</script>
@endsection