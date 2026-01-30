@extends('admin.layouts.timetable')
@section('title', 'Course Details')
@section('content')
    <main class="container-fluid px-4 py-4" id="main-content" role="main">
        <!-- Action Bar -->
        <div class="no-print mb-4">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-6">
                    <nav aria-label="Breadcrumb">
                        <ol class="breadcrumb mb-0 bg-body-secondary rounded-3 px-3 py-2 d-inline-flex">
                            <li class="breadcrumb-item"><a href="{{ route('programme.index') }}" class="text-decoration-none text-primary">Programs</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Course Details</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-12 col-md-6 text-md-end">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end" role="group" aria-label="Course actions">
                        <a href="{{ route('programme.index') }}" class="btn btn-outline-primary rounded-pill shadow-sm">
                            <i class="bi bi-arrow-left me-1"></i> Back to Programs
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill shadow-sm" aria-label="Print this page">
                            <i class="bi bi-printer me-1"></i> Print
                        </button>
                        <a href="{{ route('programme.download.pdf', ['id' => encrypt($course->pk)]) }}"
                           class="btn btn-danger rounded-pill shadow-sm"
                           aria-label="Download PDF version">
                            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Header Card -->
        <div class="card border-0 shadow rounded-4 mb-4 overflow-hidden programme-show-card">
            <div class="card-header bg-gradient-primary text-white py-4 rounded-top-4">
                <div class="row align-items-center g-3">
                    <div class="col-md-8">
                        <h1 class="card-title h2 mb-2 fw-bold lh-sm">{{ $course->course_name }}</h1>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge bg-white text-primary fs-6 px-3 py-2 rounded-pill">{{ $course->couse_short_name }}</span>
                            <span class="badge bg-white bg-opacity-25 text-white fs-6 px-3 py-2 rounded-pill">
                                <i class="bi bi-calendar3 me-1"></i>{{ $course->course_year }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="text-white-50 small">
                            <i class="bi bi-upc me-1"></i>Course Code: {{ $course->course_code ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Details -->
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4">
                    <!-- Course Information -->
                    <div class="col-lg-8">
                        <section aria-labelledby="course-info-title" class="programme-info-section">
                            <h2 class="h5 mb-4 fw-bold d-flex align-items-center text-primary">
                                <span class="programme-section-icon rounded-3 p-2 bg-primary bg-opacity-10 me-3"><i class="bi bi-info-square"></i></span>
                                <span id="course-info-title">Course Information</span>
                            </h2>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-body-secondary small text-uppercase mb-1">Course Name</label>
                                    <div class="border-start border-3 border-primary ps-3 py-2 bg-body-secondary rounded-end rounded-3 text-break">
                                        {{ $course->course_name }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-body-secondary small text-uppercase mb-1">Short Name</label>
                                    <div class="border-start border-3 border-primary ps-3 py-2 bg-body-secondary rounded-end rounded-3">
                                        {{ $course->couse_short_name }}
                                    </div>
                                </div>
                                @if($course->duration)
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-body-secondary small text-uppercase mb-1">Duration</label>
                                    <div class="border-start border-3 border-primary ps-3 py-2 bg-body-secondary rounded-end rounded-3">
                                        {{ $course->duration }}
                                    </div>
                                </div>
                                @endif
                                @if($course->course_type)
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-body-secondary small text-uppercase mb-1">Course Type</label>
                                    <div class="border-start border-3 border-primary ps-3 py-2 bg-body-secondary rounded-end rounded-3">
                                        {{ $course->course_type }}
                                    </div>
                                </div>
                                @endif
                            </div>

                            @if($course->description)
                            <div class="mt-4">
                                <label class="form-label fw-semibold text-body-secondary small text-uppercase mb-2">Course Description</label>
                                <div class="border-start border-3 border-info ps-3 py-3 bg-info bg-opacity-10 rounded-end rounded-3">
                                    {{ $course->description }}
                                </div>
                            </div>
                            @endif
                        </section>
                    </div>

                    <!-- Quick Stats -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden border-start border-4 border-primary">
                            <div class="card-header bg-primary bg-opacity-10 border-0 py-3">
                                <h3 class="h6 mb-0 fw-bold d-flex align-items-center">
                                    <i class="bi bi-graph-up-arrow me-2 text-primary"></i>Course Overview
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush programme-overview-list">
                                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 py-3 px-4">
                                        <span class="text-body-secondary">Course Year</span>
                                        <span class="badge bg-primary rounded-pill">{{ $course->course_year }}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                        <span class="text-body-secondary">Status</span>
                                        <span class="badge bg-success rounded-pill">Active</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                        <span class="text-body-secondary">Total Faculty</span>
                                        <span class="badge bg-info rounded-pill">{{ count($assistantCoordinatorsData) + 1 }}</span>
                                    </div>
                                    @if($course->start_year)
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                        <span class="text-body-secondary">Start Date</span>
                                        <span class="fw-semibold small">{{ \Carbon\Carbon::parse($course->start_year)->format('d M Y') }}</span>
                                    </div>
                                    @endif
                                    @if($course->end_date)
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                        <span class="text-body-secondary">End Date</span>
                                        <span class="fw-semibold small">{{ \Carbon\Carbon::parse($course->end_date)->format('d M Y') }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-5 opacity-25">

                <!-- Faculty Section -->
                <section aria-labelledby="faculty-title" class="programme-faculty-section">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <h2 class="h4 fw-bold d-flex align-items-center text-primary mb-0" id="faculty-title">
                            <span class="programme-section-icon rounded-3 p-2 bg-primary bg-opacity-10 me-3"><i class="bi bi-people-fill"></i></span>
                            Course Faculty Team
                        </h2>
                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ count($assistantCoordinatorsData) + 1 }} Members</span>
                    </div>

                    <!-- Course Coordinator -->
                    <div class="row mb-5">
                        <div class="col-12">
                            <h3 class="h5 mb-3 text-body-secondary d-flex align-items-center">
                                <i class="bi bi-award-fill me-2 text-warning"></i>Course Coordinator
                            </h3>
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-start border-4 border-warning coordinator-card">
                                <div class="card-body p-4">
                                    <div class="row align-items-center g-4">
                                        <div class="col-md-3 text-center">
                                            <div class="position-relative d-inline-block">
                                                <img src="{{ asset('storage/' . ($coordinatorFaculty->photo_uplode_path ?? 'default-profile.jpg')) }}"
                                                     alt="Photo of {{ $coordinatorName }}"
                                                     onerror="this.src='{{ asset('images/user-placeholder.png') }}'"
                                                     class="rounded-circle border border-3 border-warning object-fit-cover programme-coordinator-img">
                                                <span class="position-absolute bottom-0 end-0 badge bg-warning text-dark rounded-circle p-2 shadow">
                                                    <i class="bi bi-star-fill"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                            <h4 class="fw-bold mb-2">{{ $coordinatorName }}</h4>
                                            <div class="mb-3">
                                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Primary Coordinator</span>
                                            </div>
                                            @if($coordinatorFaculty->designation ?? false)
                                            <p class="text-body-secondary mb-2 d-flex align-items-center">
                                                <i class="bi bi-briefcase me-2 text-primary"></i>{{ $coordinatorFaculty->designation }}
                                            </p>
                                            @endif
                                            @if($coordinatorFaculty->department ?? false)
                                            <p class="text-body-secondary mb-0 d-flex align-items-center">
                                                <i class="bi bi-building me-2 text-primary"></i>{{ $coordinatorFaculty->department }}
                                            </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assistant Coordinators -->
                    @if(count($assistantCoordinatorsData) > 0)
                    <div class="row">
                        <div class="col-12">
                            <h3 class="h5 mb-4 text-body-secondary d-flex align-items-center">
                                <i class="bi bi-person-badge me-2 text-primary"></i>Assistant Coordinators
                                <span class="badge bg-secondary rounded-pill ms-2">{{ count($assistantCoordinatorsData) }}</span>
                            </h3>
                            <div class="row g-4">
                                @foreach($assistantCoordinatorsData as $index => $assistant)
                                <div class="col-md-6 col-lg-4 col-xl-3">
                                    <div class="card h-100 border-0 shadow-sm rounded-4 programme-assistant-card overflow-hidden">
                                        <div class="card-body text-center p-4">
                                            <div class="mb-3">
                                                <img src="{{ asset('storage/' . $assistant['photo']) }}"
                                                     alt="Photo of {{ $assistant['name'] }}"
                                                     onerror="this.src='{{ asset('images/user-placeholder.png') }}'"
                                                     class="rounded-circle border border-2 border-primary object-fit-cover programme-assistant-img">
                                            </div>
                                            <h5 class="card-title fw-semibold mb-2">{{ $assistant['name'] }}</h5>
                                            <p class="card-text mb-2">
                                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill">
                                                    {{ $assistant['role'] }}
                                                </span>
                                            </p>
                                            @if($assistant['designation'] ?? false)
                                            <p class="small text-body-secondary mb-0">{{ $assistant['designation'] }}</p>
                                            @endif
                                        </div>
                                        <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                                            <div class="d-flex justify-content-center gap-2">
                                                @if($assistant['email'] ?? false)
                                                <a href="mailto:{{ $assistant['email'] }}"
                                                   class="btn btn-sm btn-outline-primary rounded-pill"
                                                   aria-label="Email {{ $assistant['name'] }}">
                                                    <i class="bi bi-envelope"></i>
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-5 bg-body-secondary rounded-4">
                        <i class="bi bi-people display-4 text-body-secondary mb-3 opacity-50"></i>
                        <p class="text-body-secondary mb-0">No assistant coordinators assigned</p>
                    </div>
                    @endif
                </section>

                <!-- Additional Information (if available) -->
                @if($course->objectives || $course->learning_outcomes || $course->prerequisites)
                <hr class="my-5 opacity-25">
                <section aria-labelledby="additional-info-title">
                    <h2 class="h4 fw-bold mb-4 text-primary d-flex align-items-center" id="additional-info-title">
                        <span class="programme-section-icon rounded-3 p-2 bg-primary bg-opacity-10 me-3"><i class="bi bi-journal-text"></i></span>
                        Additional Information
                    </h2>
                    <div class="row g-4">
                        @if($course->objectives)
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden border-top border-3 border-info">
                                <div class="card-header bg-info bg-opacity-10 border-0 py-3">
                                    <h3 class="h6 fw-bold mb-0 d-flex align-items-center">
                                        <i class="bi bi-bullseye me-2 text-info"></i>Course Objectives
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-body-secondary mb-0">{{ $course->objectives }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($course->learning_outcomes)
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden border-top border-3 border-success">
                                <div class="card-header bg-success bg-opacity-10 border-0 py-3">
                                    <h3 class="h6 fw-bold mb-0 d-flex align-items-center">
                                        <i class="bi bi-check2-circle me-2 text-success"></i>Learning Outcomes
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-body-secondary mb-0">{{ $course->learning_outcomes }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($course->prerequisites)
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden border-top border-3 border-warning">
                                <div class="card-header bg-warning bg-opacity-10 border-0 py-3">
                                    <h3 class="h6 fw-bold mb-0 d-flex align-items-center">
                                        <i class="bi bi-list-check me-2 text-warning"></i>Prerequisites
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-body-secondary mb-0">{{ $course->prerequisites }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </section>
                @endif
            </div>

            <!-- Footer Actions -->
            <div class="card-footer bg-body-secondary border-0 py-3 px-4 rounded-bottom-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="text-body-secondary small d-flex align-items-center">
                        <i class="bi bi-clock-history me-1"></i>
                        Last updated: {{ \Carbon\Carbon::parse($course->updated_at ?? now())->format('d M Y, h:i A') }}
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-outline-primary btn-sm rounded-pill">
                            <i class="bi bi-printer me-1"></i> Print Details
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Footer -->
        <footer class="mt-5 pt-4 border-top text-center text-muted small no-print">
            <div class="row g-2">
                <div class="col-md-6 text-md-start">
                    <p class="mb-1 d-flex align-items-center justify-content-md-start justify-content-center">
                        <i class="bi bi-shield-check me-1"></i>
                        Official Course Information System
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1">
                        Generated on {{ \Carbon\Carbon::now()->format('d F Y, h:i A') }}
                        <span class="mx-2 opacity-50">|</span>
                        System Version: 2.1
                    </p>
                </div>
            </div>
            <p class="mt-3 mb-0">
                <a href="#main-content" class="text-decoration-none text-primary d-inline-flex align-items-center rounded-pill px-3 py-2 programme-back-top">
                    <i class="bi bi-arrow-up-circle me-1"></i>Back to top
                </a>
            </p>
        </footer>
    </main>
    
    <!-- Enhanced JavaScript for Better UX -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Accessibility: Focus management
            const mainContent = document.getElementById('main-content');
            if (mainContent && window.location.hash === '#main-content') {
                mainContent.focus();
            }
            
            // Print optimization
            const printBtn = document.querySelector('[onclick="window.print()"]');
            if (printBtn) {
                printBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Add loading state
                    const originalText = this.innerHTML;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Preparing for print...';
                    this.disabled = true;
                    
                    setTimeout(() => {
                        window.print();
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 500);
                });
            }
            
            // Image error handling
            document.querySelectorAll('img').forEach(img => {
                img.addEventListener('error', function() {
                    this.src = '{{ asset("images/user-placeholder.png") }}';
                    this.alt = 'Image not available';
                });
            });
            
            // Smooth scroll to top
            document.querySelectorAll('a[href="#main-content"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('main-content').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                });
            });
        });
        
        // GIGW Compliance: Keyboard navigation
        document.addEventListener('keydown', function(e) {
            // Focus trap for modals if any
            if (e.key === 'Escape') {
                // Close any open modals or dropdowns
                const openDropdowns = document.querySelectorAll('.show');
                openDropdowns.forEach(dropdown => {
                    bootstrap.Dropdown.getInstance(dropdown)?.hide();
                });
            }
        });
    </script>
    
    <!-- Print Styles -->
    <style media="print">
        body {
            font-size: 12pt;
            background: white !important;
            color: black !important;
        }
        .no-print {
            display: none !important;
        }
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        .bg-gradient-primary {
            background: #004a93 !important;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .badge {
            border: 1px solid #ddd;
        }
        img {
            max-width: 200px !important;
        }
        a {
            color: black !important;
            text-decoration: none !important;
        }
        footer {
            border-top: 2px solid #ddd !important;
            margin-top: 20px !important;
        }
    </style>
    
    <!-- Additional Styles for Modern Look -->
    <style>
        :root {
            --programme-primary: #004a93;
            --programme-primary-light: #e3f2fd;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--programme-primary) 0%, #003366 100%);
        }
        
        .programme-coordinator-img {
            width: 140px;
            height: 140px;
        }
        
        .programme-assistant-img {
            width: 100px;
            height: 100px;
        }
        
        .programme-assistant-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .programme-assistant-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
        }
        
        .programme-back-top:hover {
            background-color: rgba(0, 74, 147, 0.08);
        }
        
        /* Accessibility: Focus styles */
        a:focus-visible, button:focus-visible, [tabindex]:focus-visible {
            outline: 3px solid var(--programme-primary);
            outline-offset: 2px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .programme-show-card .card-header h1 {
                font-size: 1.35rem;
            }
            .programme-coordinator-img {
                width: 120px;
                height: 120px;
            }
        }
        
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .programme-show-card {
                border: 2px solid #000 !important;
            }
        }
        
        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            .programme-assistant-card {
                transition: none;
            }
            .programme-assistant-card:hover {
                transform: none;
            }
        }
    </style>
@endsection