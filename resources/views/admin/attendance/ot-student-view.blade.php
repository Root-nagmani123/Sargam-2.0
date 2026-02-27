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

/* Wrapper with soft glass look */
.filter-wrapper {
    background: #fff;
    border-radius: 18px;
    padding: 0;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07);
    border: 1px solid #e6e9ef;
    overflow: hidden;
}

/* Header */
.filter-header {
    background: #f8f9fb;
    padding: 16px 22px;
    border-bottom: 1px solid #e9ecef;
}

/* Body */
.filter-body {
    padding: 26px 22px 30px;
}

/* Floating field */
.floating-field {
    position: relative;
}

.floating-field label {
    position: absolute;
    top: 50%;
    left: 40px;
    transform: translateY(-50%);
    background: #fff;
    padding: 0 4px;
    color: #6c757d;
    pointer-events: none;
    transition: 0.2s ease;
}

.floating-field input:not(:placeholder-shown)+label,
.floating-field input:focus+label,
.floating-field select:focus+label {
    top: -8px;
    font-size: 12px;
    color: var(--bs-primary);
}

.floating-field .form-control,
.floating-field .form-select {
    padding-left: 42px;
    border-radius: 12px;
    height: 48px;
}

.field-icon {
    position: absolute;
    top: 50%;
    left: 14px;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 16px;
}

/* Segmented Switch */
.segment-control {
    position: relative;
    display: inline-flex;
    background: #f1f3f5;
    border-radius: 50px;
    padding: 5px;
    width: auto;
}

.seg-btn {
    background: transparent;
    border: 0;
    padding: 10px 24px;
    font-weight: 600;
    border-radius: 40px;
    position: relative;
    z-index: 2;
    color: #495057;
    transition: 0.25s ease;
}

.seg-btn.active {
    color: #fff;
}

.seg-highlight {
    position: absolute;
    top: 5px;
    bottom: 5px;
    width: calc(50% - 5px);
    background: var(--bs-primary);
    border-radius: 40px;
    transition: transform 0.25s ease;
    z-index: 1;
}

.seg-btn:nth-child(1).active~.seg-highlight {
    transform: translateX(0%);
}

.seg-btn:nth-child(2).active~.seg-highlight {
    transform: translateX(100%);
}

/* Focus visible (GIGW compliant) */
input:focus,
select:focus,
button:focus-visible {
    outline: 3px solid rgba(0, 97, 193, 0.35) !important;
    box-shadow: none !important;
}

/* Main table styling */
.modern-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

/* Header */
.modern-table thead th {
    background: #b32222;
    /* Matching your screenshot */
    color: #fff;
    font-weight: 600;
    padding: 14px;
    font-size: 14.5px;
    white-space: nowrap;
}

.modern-table tbody td {
    padding: 14px;
    font-size: 14px;
    border-bottom: 1px solid #ececec;
    vertical-align: middle;
}

/* Round corners like screenshot */
.modern-table thead tr:first-child th:first-child {
    border-top-left-radius: 12px;
}

.modern-table thead tr:first-child th:last-child {
    border-top-right-radius: 12px;
}

/* Hover (GIGW minimal contrast safe) */
.modern-table tbody tr:hover {
    background: #fafafa !important;
}

/* Attendance Status Colors (matching screenshot) */
.status-present {
    color: #2e8b57 !important;
    font-weight: 600;
}

.status-late {
    color: #d98c00 !important;
    font-weight: 600;
}

.status-absent {
    color: #b30000 !important;
    font-weight: 600;
}

/* Icon button styling */
.icon-btn {
    color: #444;
    font-size: 18px;
    line-height: 1;
    padding: 2px 6px;
}

.icon-btn:hover {
    color: #000;
}

/* GIGW Focus outline */
*:focus-visible {
    outline: 3px solid rgba(0, 73, 150, 0.45) !important;
    border-radius: 4px;
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

    {{-- Filter Form --}}
    <div class="filter-wrapper mb-4">
        <div class="filter-header">
            <h5 class="fw-bold m-0">
                <i class="bi bi-funnel-fill text-primary me-2"></i>
                Smart Filters
            </h5>
        </div>

        <div class="filter-body">
            <form method="GET" action="{{ route('attendance.OT.student_mark.student', [
                    'group_pk' => $group_pk,
                    'course_pk' => $course_pk,
                    'timetable_pk' => $timetable_pk,
                    'student_pk' => $student_pk]) }}" id="filterForm">

                <input type="hidden" name="archive_mode" id="archive_mode_input" value="{{ $archiveMode ?? 'active' }}">

                <!-- Animated Segmented Switch -->
                <div class="d-flex justify-content-end">
                    <div class="segment-control mb-4 d-inline-flex align-items-center gap-2" role="group"
                        aria-label="Active or Archive Filter">

                        <button type="button"
                            class="seg-btn {{ ($archiveMode ?? 'active') === 'active' ? 'active' : '' }}"
                            id="filterActive">
                            <i class="bi bi-check-circle me-1"></i> Active
                        </button>

                        <button type="button"
                            class="seg-btn {{ ($archiveMode ?? 'active') === 'archive' ? 'active' : '' }}"
                            id="filterArchive">
                            <i class="bi bi-archive me-1"></i> Archive
                        </button>

                        <div class="seg-highlight"></div>
                    </div>
                </div>


                <div class="row g-4 mx-auto">

                    <!-- Date -->
                    <div class="col-md-3">
                        <div class="floating-field">
                            <i class="bi bi-calendar-event field-icon"></i>
                            <input type="date" id="filter_date" name="filter_date" class="form-control"
                                value="{{ $filterDate ?? '' }}">
                        </div>
                    </div>

                    <!-- Session -->
                    <div class="col-md-3">
                        <div class="floating-field">
                            <i class="bi bi-clock field-icon"></i>
                            <select id="filter_session_time" name="filter_session_time" class="select2 form-control">
                                <option value=""></option>
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
                            <label for="filter_session_time">Session Time</label>
                        </div>
                    </div>

                    <!-- Clear -->
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-dark w-100 fw-semibold py-2" id="clearFilters">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>




    {{-- Attendance Details Table --}}
    <div class="card shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-semibold">Attendance Details</h4>
                <a href="{{ route('attendance.index') }}" class="btn btn-secondary btn-sm">
                    Back
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            @if(count($attendanceRecords) > 0)
            <div class="table-responsive">
                <table class="table modern-table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Sessions</th>
                            <th>Venue</th>
                            <th>Group</th>
                            <th>Topic</th>
                            <th>Faculty</th>
                            <th>Attendance</th>
                            <th>Duty Type (MDO/Escort)</th>
                            <th>Exemption</th>
                            <th>Doc/Comment</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($attendanceRecords as $record)
                        <tr>
                            <td class="text-nowrap">
                                {{ $record['date'] }}
                            </td>

                            <td>{{ $record['session_time'] }}</td>
                            <td>{{ $record['venue'] }}</td>
                            <td>{{ $record['group'] }}</td>
                            <td>{{ $record['topic'] }}</td>
                            <td>{{ $record['faculty'] }}</td>

                            <td>
                                @if($record['attendance_status'] == 'Present')
                                <span class="status-present">Present</span>
                                @elseif($record['attendance_status'] == 'Late')
                                <span class="status-late">Late</span>
                                @elseif($record['attendance_status'] == 'Absent')
                                <span class="status-absent">Absent</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td>{{ $record['duty_type'] ?? '-' }}</td>

                            <td>{{ $record['exemption_type'] ?? '-' }}</td>

                            <td class="text-nowrap">
                                @if($record['exemption_document'])
                                <a href="{{ asset('storage/' . $record['exemption_document']) }}" target="_blank"
                                    class="icon-btn me-2">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ asset('storage/' . $record['exemption_document']) }}" download
                                    class="icon-btn">
                                    <i class="bi bi-download"></i>
                                </a>
                                @elseif($record['exemption_comment'])
                                {{ $record['exemption_comment'] }}
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
            @else
            <div class="p-4 text-center text-muted">
                No attendance records found.
            </div>
            @endif
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize select2 if available
    if ($.fn.select2) {
        $('.select2').select2({
            placeholder: 'Select Session Time',
            allowClear: true
        });
    }

    // Active/Archive toggle button handlers
    $('#filterActive').on('click', function() {
        setActiveButton($(this));
        $('#archive_mode_input').val('active');
        $('#filterForm').submit();
    });

    $('#filterArchive').on('click', function() {
        setActiveButton($(this));
        $('#archive_mode_input').val('archive');
        $('#filterForm').submit();
    });

    // Function to set active button styling
    function setActiveButton(activeBtn) {
        // Reset all buttons to outline style
        $('#filterActive')
            .removeClass('btn-success active text-white')
            .addClass('btn-outline-success')
            .attr('aria-pressed', 'false');

        $('#filterArchive')
            .removeClass('btn-secondary active text-white')
            .addClass('btn-outline-secondary')
            .attr('aria-pressed', 'false');

        // Set the active button
        if (activeBtn.attr('id') === 'filterActive') {
            activeBtn.removeClass('btn-outline-success')
                .addClass('btn-success text-white active')
                .attr('aria-pressed', 'true');
        } else if (activeBtn.attr('id') === 'filterArchive') {
            activeBtn.removeClass('btn-outline-secondary')
                .addClass('btn-secondary text-white active')
                .attr('aria-pressed', 'true');
        }
    }

    // Auto-submit form when filters change
    let filterTimeout;
    $('#filter_date, #filter_session_time').on('change', function() {
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
        // Reset to active mode
        setActiveButton($('#filterActive'));
        $('#archive_mode_input').val('active');
        $('#filterForm').submit();
    });
});
</script>
@endsection