<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Course details for {{ $course->course_name }} - Lal Bahadur Shastri National Academy of Administration">
    <title>{{ $course->course_name }} - Course Details | LBSNAA</title>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/ico" href="{{asset('admin_assets/images/logos/favicon.ico')}}">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- GIGW Compliance: Skip Navigation for Accessibility -->
    <style>
    .skip-nav {
        position: absolute;
        top: -40px;
        left: 0;
        background: #004a93;
        color: white;
        padding: 8px;
        z-index: 9999;
        text-decoration: none;
    }
    .skip-nav:focus {
        top: 0;
    }
    </style>
</head>

<body class="bg-light" style="font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;">
    
    <!-- Skip Navigation for Accessibility (GIGW Compliance) -->
    <a href="#main-content" class="skip-nav">Skip to main content</a>

    <!-- Government Header with Accessibility -->
    <header class="bg-white border-bottom border-3 border-primary" role="banner">
        <div class="container-fluid px-4">
            <div class="row align-items-center py-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary rounded-3 p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="white" class="bi bi-building" viewBox="0 0 16 16">
                                <path d="M4 2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1ZM4 5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1ZM7.5 5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1Zm2.5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1ZM4.5 8a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1Zm2.5.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm3.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1Z"/>
                                <path d="M2 1a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V1Zm11 0H3v14h3v-2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5V15h3V1Z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="h4 mb-1 fw-bold text-primary">Lal Bahadur Shastri National Academy of Administration</h1>
                            <p class="mb-0 text-muted small">Government of India</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="badge bg-secondary">Education Portal</span>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-4 py-4" id="main-content" role="main">
        <!-- Action Bar -->
        <div class="no-print mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <nav aria-label="Breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('programme.index') }}" class="text-decoration-none">Programs</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Course Details</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="btn-group" role="group" aria-label="Course actions">
                        <a href="{{ route('programme.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Programs
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-secondary" aria-label="Print this page">
                            <i class="bi bi-printer me-1"></i> Print
                        </button>
                        <a href="{{ route('programme.download.pdf', ['id' => encrypt($course->pk)]) }}" 
                           class="btn btn-danger" 
                           aria-label="Download PDF version">
                            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Header Card -->
        <div class="card border-0 shadow-lg mb-4 overflow-hidden">
            <div class="card-header bg-gradient-primary text-white py-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="card-title h2 mb-2 fw-bold">{{ $course->course_name }}</h1>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge bg-white text-primary fs-6 px-3 py-2">{{ $course->couse_short_name }}</span>
                            <span class="badge bg-light text-dark fs-6 px-3 py-2">
                                <i class="bi bi-calendar3 me-1"></i>{{ $course->course_year }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="text-white-50 small">
                            <i class="bi bi-info-circle me-1"></i>Course Code: {{ $course->course_code ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Course Details -->
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- Course Information -->
                    <div class="col-lg-8">
                        <section aria-labelledby="course-info-title">
                            <h2 class="h5 mb-3 fw-bold d-flex align-items-center text-primary">
                                <i class="bi bi-info-square me-2"></i>
                                <span id="course-info-title">Course Information</span>
                            </h2>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted small mb-1">Course Name</label>
                                    <div class="border-start border-3 border-primary ps-3 py-2 bg-light rounded-end">
                                        {{ $course->course_name }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted small mb-1">Short Name</label>
                                    <div class="border-start border-3 border-primary ps-3 py-2 bg-light rounded-end">
                                        {{ $course->couse_short_name }}
                                    </div>
                                </div>
                                @if($course->duration)
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted small mb-1">Duration</label>
                                    <div class="border-start border-3 border-primary ps-3 py-2 bg-light rounded-end">
                                        {{ $course->duration }}
                                    </div>
                                </div>
                                @endif
                                @if($course->course_type)
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted small mb-1">Course Type</label>
                                    <div class="border-start border-3 border-primary ps-3 py-2 bg-light rounded-end">
                                        {{ $course->course_type }}
                                    </div>
                                </div>
                                @endif
                            </div>
                            
                            @if($course->description)
                            <div class="mt-4">
                                <label class="form-label fw-semibold text-muted small mb-2">Course Description</label>
                                <div class="border-start border-3 border-info ps-3 py-3 bg-light rounded-end">
                                    {{ $course->description }}
                                </div>
                            </div>
                            @endif
                        </section>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="col-lg-4">
                        <div class="card border-primary h-100">
                            <div class="card-header bg-transparent border-primary">
                                <h3 class="h6 mb-0 fw-bold">
                                    <i class="bi bi-graph-up me-2"></i>Course Overview
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                                        <span class="text-muted">Course Year</span>
                                        <span class="badge bg-primary rounded-pill">{{ $course->course_year }}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-muted">Status</span>
                                        <span class="badge bg-success rounded-pill">Active</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-muted">Total Faculty</span>
                                        <span class="badge bg-info rounded-pill">{{ count($assistantCoordinatorsData) + 1 }}</span>
                                    </div>
                                    @if($course->start_date)
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-muted">Start Date</span>
                                        <span class="fw-semibold">{{ \Carbon\Carbon::parse($course->start_date)->format('d M Y') }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-5">
                
                <!-- Faculty Section -->
                <section aria-labelledby="faculty-title">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h4 fw-bold d-flex align-items-center text-primary" id="faculty-title">
                            <i class="bi bi-people-fill me-2"></i>Course Faculty Team
                        </h2>
                        <span class="badge bg-primary">{{ count($assistantCoordinatorsData) + 1 }} Members</span>
                    </div>
                    
                    <!-- Course Coordinator -->
                    <div class="row mb-5">
                        <div class="col-12">
                            <h3 class="h5 mb-3 text-muted">
                                <i class="bi bi-award-fill me-2 text-warning"></i>Course Coordinator
                            </h3>
                            <div class="card border-warning shadow-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-3 text-center">
                                            <div class="position-relative d-inline-block">
                                                <img src="{{ asset('storage/' . ($coordinatorFaculty->photo_uplode_path ?? 'default-profile.jpg')) }}" 
                                                     alt="Photo of {{ $coordinatorName }}"
                                                     onerror="this.src='{{ asset('images/user-placeholder.png') }}'"
                                                     class="rounded-circle border border-3 border-warning"
                                                     style="width: 140px; height: 140px; object-fit: cover;">
                                                <span class="position-absolute bottom-0 end-0 badge bg-warning text-dark rounded-circle p-2">
                                                    <i class="bi bi-star-fill"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                            <h4 class="fw-bold mb-2">{{ $coordinatorName }}</h4>
                                            <div class="mb-3">
                                                <span class="badge bg-warning text-dark px-3 py-2">Primary Coordinator</span>
                                            </div>
                                            @if($coordinatorFaculty->designation ?? false)
                                            <p class="text-muted mb-2">
                                                <i class="bi bi-briefcase me-2"></i>{{ $coordinatorFaculty->designation }}
                                            </p>
                                            @endif
                                            @if($coordinatorFaculty->department ?? false)
                                            <p class="text-muted mb-0">
                                                <i class="bi bi-building me-2"></i>{{ $coordinatorFaculty->department }}
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
                            <h3 class="h5 mb-4 text-muted">
                                <i class="bi bi-person-badge me-2 text-primary"></i>Assistant Coordinators
                                <span class="badge bg-secondary ms-2">{{ count($assistantCoordinatorsData) }}</span>
                            </h3>
                            <div class="row g-4">
                                @foreach($assistantCoordinatorsData as $index => $assistant)
                                <div class="col-md-6 col-lg-4 col-xl-3">
                                    <div class="card h-100 border hover-shadow-lg transition-all" style="border-color: #e0e0e0;">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <img src="{{ asset('storage/' . $assistant['photo']) }}" 
                                                     alt="Photo of {{ $assistant['name'] }}"
                                                     onerror="this.src='{{ asset('images/user-placeholder.png') }}'"
                                                     class="rounded-circle border border-2 border-primary"
                                                     style="width: 100px; height: 100px; object-fit: cover;">
                                            </div>
                                            <h5 class="card-title fw-semibold mb-2">{{ $assistant['name'] }}</h5>
                                            <p class="card-text">
                                                <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill">
                                                    {{ $assistant['role'] }}
                                                </span>
                                            </p>
                                            @if($assistant['designation'] ?? false)
                                            <p class="small text-muted mb-1">{{ $assistant['designation'] }}</p>
                                            @endif
                                        </div>
                                        <div class="card-footer bg-transparent border-top-0 pt-0">
                                            <div class="d-flex justify-content-center gap-2">
                                                @if($assistant['email'] ?? false)
                                                <a href="mailto:{{ $assistant['email'] }}" 
                                                   class="btn btn-sm btn-outline-primary" 
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
                    <div class="text-center py-5 bg-light rounded-3">
                        <i class="bi bi-people display-5 text-muted mb-3"></i>
                        <p class="text-muted mb-0">No assistant coordinators assigned</p>
                    </div>
                    @endif
                </section>
                
                <!-- Additional Information (if available) -->
                @if($course->objectives || $course->learning_outcomes || $course->prerequisites)
                <hr class="my-5">
                <section aria-labelledby="additional-info-title">
                    <h2 class="h4 fw-bold mb-4 text-primary" id="additional-info-title">
                        <i class="bi bi-journal-text me-2"></i>Additional Information
                    </h2>
                    <div class="row g-4">
                        @if($course->objectives)
                        <div class="col-md-4">
                            <div class="card h-100 border-info">
                                <div class="card-header bg-transparent border-info">
                                    <h3 class="h6 fw-bold mb-0">
                                        <i class="bi bi-bullseye me-2"></i>Course Objectives
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">{{ $course->objectives }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        @if($course->learning_outcomes)
                        <div class="col-md-4">
                            <div class="card h-100 border-success">
                                <div class="card-header bg-transparent border-success">
                                    <h3 class="h6 fw-bold mb-0">
                                        <i class="bi bi-check-circle me-2"></i>Learning Outcomes
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">{{ $course->learning_outcomes }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        @if($course->prerequisites)
                        <div class="col-md-4">
                            <div class="card h-100 border-warning">
                                <div class="card-header bg-transparent border-warning">
                                    <h3 class="h6 fw-bold mb-0">
                                        <i class="bi bi-list-check me-2"></i>Prerequisites
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">{{ $course->prerequisites }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </section>
                @endif
            </div>
            
            <!-- Footer Actions -->
            <div class="card-footer bg-light py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        Last updated: {{ \Carbon\Carbon::parse($course->updated_at ?? now())->format('d M Y, h:i A') }}
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-printer me-1"></i> Print Details
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Footer -->
        <footer class="mt-5 pt-4 border-top text-center text-muted small no-print">
            <div class="row">
                <div class="col-md-6 text-md-start">
                    <p class="mb-1">
                        <i class="bi bi-shield-check me-1"></i>
                        Official Course Information System
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1">
                        Generated on {{ \Carbon\Carbon::now()->format('d F Y, h:i A') }}
                        <span class="mx-2">|</span>
                        System Version: 2.1
                    </p>
                </div>
            </div>
            <p class="mt-3">
                <a href="#main-content" class="text-decoration-none text-primary">
                    <i class="bi bi-arrow-up-circle me-1"></i>Back to top
                </a>
            </p>
        </footer>
    </main>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
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
            --primary: #004a93;
            --primary-light: #e3f2fd;
            --secondary: #6c757d;
            --success: #198754;
            --warning: #ffc107;
            --info: #0dcaf0;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #003366 100%);
        }
        
        .hover-shadow-lg {
            transition: all 0.3s ease;
        }
        
        .hover-shadow-lg:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        .transition-all {
            transition: all 0.3s ease;
        }
        
        .border-primary-subtle {
            border-color: #b8daff !important;
        }
        
        .bg-primary-subtle {
            background-color: #e3f2fd !important;
        }
        
        /* Accessibility: Focus styles */
        a:focus, button:focus, [tabindex]:focus {
            outline: 3px solid var(--primary);
            outline-offset: 2px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card-header h1 {
                font-size: 1.5rem;
            }
            .btn-group {
                width: 100%;
                flex-wrap: wrap;
            }
            .btn-group .btn {
                flex: 1;
                margin-bottom: 5px;
            }
        }
        
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .card {
                border: 2px solid #000 !important;
            }
            .btn {
                border: 2px solid #000 !important;
            }
        }
        
        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            .hover-shadow-lg, .transition-all {
                transition: none !important;
            }
            .hover-shadow-lg:hover {
                transform: none !important;
            }
        }
    </style>
</body>
</html>