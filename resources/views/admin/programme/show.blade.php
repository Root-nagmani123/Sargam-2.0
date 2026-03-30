@extends('admin.layouts.timetable')

@section('title', 'Course Details')

@section('content')
    @php
        $assistantCount = count($assistantCoordinatorsData ?? []);
        $totalFaculty = $assistantCount + 1;
        $coordinatorPhoto = $coordinatorFaculty?->photo_uplode_path ?: 'default-profile.jpg';
        $coordinatorName = $coordinatorName ?: 'Coordinator not assigned';
        $startDate = filled($course->start_year) ? \Carbon\Carbon::parse($course->start_year)->format('d M Y') : null;
        $endDate = filled($course->end_date) ? \Carbon\Carbon::parse($course->end_date)->format('d M Y') : null;
        $updatedAt = \Carbon\Carbon::parse($course->updated_at ?? now())->format('d M Y, h:i A');
        $generatedAt = \Carbon\Carbon::now()->format('d F Y, h:i A');
    @endphp

    <main class="container-fluid px-3 px-lg-4 pb-3" id="main-content" role="main" tabindex="-1">

        <section class="card border-0 shadow-sm rounded-4 overflow-hidden programme-shell">
            <div class="programme-hero px-3 px-xl-4 py-3 py-xl-4">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-8">
                        <span class="badge rounded-1 text-bg-light text-primary-emphasis px-3 py-2 fw-semibold">
                            <i class="bi bi-mortarboard-fill me-1"></i> Programme Overview
                        </span>

                        <h1 class="h2 fw-semibold text-white mt-2 mb-2">{{ $course->course_name }}</h1>

                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge rounded-1 bg-white text-primary px-3 py-2">
                                {{ $course->couse_short_name ?: 'Short name unavailable' }}
                            </span>
                            <span class="badge rounded-1 text-bg-dark border border-white border-opacity-25 px-3 py-2">
                                <i class="bi bi-calendar3 me-1"></i>{{ $course->course_year }}
                            </span>
                            @if ($course->course_code)
                                <span class="badge rounded-1 text-bg-dark border border-white border-opacity-25 px-3 py-2">
                                    <i class="bi bi-upc-scan me-1"></i>{{ $course->course_code }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="d-flex flex-wrap justify-content-xl-end gap-2 no-print page-actions">
                            <a href="{{ route('programme.index') }}" class="btn btn-light fw-semibold text-white">
                                <i class="bi bi-arrow-left me-2"></i>Back to Programs
                            </a>
                            <button type="button" class="btn btn-outline-light fw-semibold text-white" data-print-trigger>
                                <i class="bi bi-printer me-2"></i>Print
                            </button>
                            <a href="{{ route('programme.download.pdf', ['id' => encrypt($course->pk)]) }}"
                                class="btn btn-danger fw-semibold text-white">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-xl-4">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100 hover-lift">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="metric-icon rounded-3 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center fs-4">
                                        <i class="bi bi-calendar-event"></i>
                                    </span>
                                    <div>
                                        <div class="small text-uppercase fw-semibold text-body-secondary">Course Year</div>
                                        <div class="fw-semibold">{{ $course->course_year }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100 hover-lift">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="metric-icon rounded-3 bg-info-subtle text-info d-inline-flex align-items-center justify-content-center fs-4">
                                        <i class="bi bi-people-fill"></i>
                                    </span>
                                    <div>
                                        <div class="small text-uppercase fw-semibold text-body-secondary">Faculty Team</div>
                                        <div class="fw-semibold">{{ $totalFaculty }} Members</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100 hover-lift">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="metric-icon rounded-3 bg-success-subtle text-success d-inline-flex align-items-center justify-content-center fs-4">
                                        <i class="bi bi-clock-history"></i>
                                    </span>
                                    <div>
                                        <div class="small text-uppercase fw-semibold text-body-secondary">Duration</div>
                                        <div class="fw-semibold">{{ $course->duration ?: 'Not available' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100 hover-lift">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="metric-icon rounded-3 bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center fs-4">
                                        <i class="bi bi-book-half"></i>
                                    </span>
                                    <div>
                                        <div class="small text-uppercase fw-semibold text-body-secondary">Course Type</div>
                                        <div class="fw-semibold">{{ $course->course_type ?: 'Not specified' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 align-items-start">
                    <div class="col-xl-8">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-header bg-white border-0 pt-3 px-3 px-lg-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <h2 class="h5 mb-0 fw-semibold text-primary">Course Information</h2>
                                    </div>
                                    <span class="badge rounded-1 text-bg-primary px-3 py-2">Active</span>
                                </div>
                            </div>
                            <div class="card-body px-3 px-lg-4 pb-3">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="detail-tile h-100 rounded-4 border bg-body-tertiary p-2">
                                            <div class="small text-uppercase fw-semibold text-body-secondary mb-1">Course Name</div>
                                            <div class="fw-semibold">{{ $course->course_name }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-tile h-100 rounded-4 border bg-body-tertiary p-2">
                                            <div class="small text-uppercase fw-semibold text-body-secondary mb-1">Short Name</div>
                                            <div class="fw-semibold">{{ $course->couse_short_name ?: 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-tile h-100 rounded-4 border bg-body-tertiary p-2">
                                            <div class="small text-uppercase fw-semibold text-body-secondary mb-1">Duration</div>
                                            <div class="fw-semibold">{{ $course->duration ?: 'Not available' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-tile h-100 rounded-4 border bg-body-tertiary p-2">
                                            <div class="small text-uppercase fw-semibold text-body-secondary mb-1">Course Type</div>
                                            <div class="fw-semibold">{{ $course->course_type ?: 'Not specified' }}</div>
                                        </div>
                                    </div>
                                </div>

                                @if ($course->description)
                                    <div class="rounded-4 border bg-body-tertiary p-3 mt-3">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge rounded-1 text-bg-primary-subtle text-primary-emphasis px-3 py-2">Description</span>
                                        </div>
                                        <p class="mb-0 text-body-secondary">{{ $course->description }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-white border-0 pt-3 px-3 px-lg-4">
                                <h2 class="h5 mb-1 fw-semibold">Record Summary</h2>
                                <p class="mb-0 small text-body-secondary">Helpful context for administration and export.</p>
                            </div>
                            <div class="card-body px-3 px-lg-4 pb-2">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item px-0 py-1 d-flex justify-content-between gap-2">
                                        <span class="text-body-secondary">Course Code</span>
                                        <span class="fw-semibold text-end">{{ $course->course_code ?: 'N/A' }}</span>
                                    </div>
                                    <div class="list-group-item px-0 py-1 d-flex justify-content-between gap-2">
                                        <span class="text-body-secondary">Status</span>
                                        <span class="badge rounded-1 text-bg-success">Active</span>
                                    </div>
                                    <div class="list-group-item px-0 py-1 d-flex justify-content-between gap-2">
                                        <span class="text-body-secondary">Start Date</span>
                                        <span class="fw-semibold text-end">{{ $startDate ?: 'Not available' }}</span>
                                    </div>
                                    <div class="list-group-item px-0 py-1 d-flex justify-content-between gap-2">
                                        <span class="text-body-secondary">End Date</span>
                                        <span class="fw-semibold text-end">{{ $endDate ?: 'Not available' }}</span>
                                    </div>
                                    <div class="list-group-item px-0 pt-1 pb-0 d-flex justify-content-between gap-2 border-0">
                                        <span class="text-body-secondary">Last Updated</span>
                                        <span class="fw-semibold text-end">{{ $updatedAt }}</span>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-3 no-print">
                                    <button type="button" class="btn btn-primary fw-semibold" data-print-trigger>
                                        <i class="bi bi-printer me-2"></i>Print Details
                                    </button>
                                    <a href="{{ route('programme.download.pdf', ['id' => encrypt($course->pk)]) }}"
                                        class="btn btn-outline-primary fw-semibold">
                                        <i class="bi bi-file-earmark-arrow-down me-2"></i>Download PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-top my-4"></div>

                <section aria-labelledby="faculty-title">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h2 class="h4 mb-0 fw-semibold text-primary" id="faculty-title">Faculty Team</h2>
                        </div>
                        <span class="badge rounded-1 text-bg-primary px-3 py-2">{{ $totalFaculty }} Members</span>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
                        <div class="card-body p-3 p-lg-4">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4 col-xl-3 text-center">
                                    <img src="{{ asset('storage/' . $coordinatorPhoto) }}"
                                        alt="Photo of {{ $coordinatorName }}"
                                        class="rounded-circle border border-4 border-warning-subtle shadow-sm coordinator-avatar mx-auto">
                                </div>
                                <div class="col-md-8 col-xl-9">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <span class="badge rounded-1 text-bg-warning px-3 py-2">
                                            <i class="bi bi-award-fill me-1"></i>Course Coordinator
                                        </span>
                                        <span class="badge rounded-1 text-bg-light border px-3 py-2">Primary Contact</span>
                                    </div>

                                    <h3 class="h4 fw-semibold mb-2">{{ $coordinatorName }}</h3>

                                    @if ($coordinatorFaculty?->designation)
                                        <p class="mb-2 text-body-secondary">
                                            <i class="bi bi-briefcase me-2 text-primary"></i>{{ $coordinatorFaculty->designation }}
                                        </p>
                                    @endif

                                    @if ($coordinatorFaculty?->department)
                                        <p class="mb-0 text-body-secondary">
                                            <i class="bi bi-building me-2 text-primary"></i>{{ $coordinatorFaculty->department }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h3 class="h5 mb-0 fw-semibold">Assistant Coordinators</h3>
                        <span class="badge rounded-1 text-bg-secondary px-3 py-2">{{ $assistantCount }}</span>
                    </div>

                    @forelse ($assistantCoordinatorsData ?? [] as $assistant)
                        @if ($loop->first)
                            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 row-cols-xxl-5 g-3">
                        @endif

                        <div class="col">
                            <div class="card border-0 shadow-sm rounded-4 h-100 hover-lift">
                                <div class="card-body text-center p-3">
                                    <img src="{{ asset('storage/' . ($assistant['photo'] ?? 'default-profile.jpg')) }}"
                                        alt="Photo of {{ $assistant['name'] }}"
                                        class="rounded-circle border border-3 border-primary-subtle assistant-avatar mx-auto mb-3">

                                    <h4 class="h5 fw-semibold mb-2">{{ $assistant['name'] }}</h4>
                                    <span class="badge rounded-1 text-bg-primary-subtle text-primary-emphasis px-3 py-1 mb-2">
                                        {{ $assistant['role'] }}
                                    </span>

                                    @if ($assistant['designation'] ?? false)
                                        <p class="small text-body-secondary mb-2">{{ $assistant['designation'] }}</p>
                                    @endif

                                    @if ($assistant['department'] ?? false)
                                        <p class="small text-body-secondary mb-2">{{ $assistant['department'] }}</p>
                                    @endif

                                    @if ($assistant['email'] ?? false)
                                        <a href="mailto:{{ $assistant['email'] }}" class="btn btn-outline-primary btn-sm fw-semibold">
                                            <i class="bi bi-envelope me-1"></i>Email
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($loop->last)
                            </div>
                        @endif
                    @empty
                        <div class="card border-0 bg-body-tertiary rounded-4">
                            <div class="card-body text-center py-4">
                                <i class="bi bi-people display-5 text-body-secondary mb-3"></i>
                                <h3 class="h5 fw-semibold mb-2">No assistant coordinators assigned</h3>
                                <p class="mb-0 text-body-secondary">This programme currently lists only the primary course coordinator.</p>
                            </div>
                        </div>
                    @endforelse
                </section>

                @if ($course->objectives || $course->learning_outcomes || $course->prerequisites)
                    <div class="border-top my-4"></div>

                    <section aria-labelledby="additional-info-title">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h2 class="h4 mb-0 fw-semibold text-primary" id="additional-info-title">Additional Information</h2>
                            </div>
                        </div>

                        <div class="row g-3">
                            @if ($course->objectives)
                                <div class="col-lg-4">
                                    <div class="card border-0 shadow-sm rounded-4 h-100">
                                        <div class="card-body p-3">
                                            <span class="badge rounded-1 text-bg-info-subtle text-info-emphasis px-3 py-2 mb-3">
                                                <i class="bi bi-bullseye me-1"></i>Course Objectives
                                            </span>
                                            <p class="mb-0 text-body-secondary">{{ $course->objectives }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($course->learning_outcomes)
                                <div class="col-lg-4">
                                    <div class="card border-0 shadow-sm rounded-4 h-100">
                                        <div class="card-body p-3">
                                            <span class="badge rounded-1 text-bg-success-subtle text-success-emphasis px-3 py-2 mb-3">
                                                <i class="bi bi-check-circle me-1"></i>Learning Outcomes
                                            </span>
                                            <p class="mb-0 text-body-secondary">{{ $course->learning_outcomes }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($course->prerequisites)
                                <div class="col-lg-4">
                                    <div class="card border-0 shadow-sm rounded-4 h-100">
                                        <div class="card-body p-3">
                                            <span class="badge rounded-1 text-bg-warning-subtle text-warning-emphasis px-3 py-2 mb-3">
                                                <i class="bi bi-list-check me-1"></i>Prerequisites
                                            </span>
                                            <p class="mb-0 text-body-secondary">{{ $course->prerequisites }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif
            </div>

            <div class="card-footer border-0 bg-body-tertiary px-3 px-xl-4 py-3">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <div class="small text-uppercase fw-semibold text-body-secondary mb-1">Last Updated</div>
                        <div class="fw-semibold">{{ $updatedAt }}</div>
                    </div>

                    <div>
                        <div class="small text-uppercase fw-semibold text-body-secondary mb-1">Generated</div>
                        <div class="fw-semibold">{{ $generatedAt }}</div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 no-print">
                        <button type="button" class="btn btn-outline-primary fw-semibold" data-print-trigger>
                            <i class="bi bi-printer me-2"></i>Print Details
                        </button>
                        <a href="#main-content" class="btn btn-light border fw-semibold">
                            <i class="bi bi-arrow-up me-2"></i>Back to Top
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-print-trigger]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const originalHtml = this.dataset.originalHtml || this.innerHTML;
                    this.dataset.originalHtml = originalHtml;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Preparing...';
                    this.disabled = true;

                    window.setTimeout(() => {
                        window.print();
                        this.innerHTML = originalHtml;
                        this.disabled = false;
                    }, 400);
                });
            });

            document.querySelectorAll('img').forEach(function(image) {
                image.addEventListener('error', function() {
                    this.src = "{{ asset('images/user-placeholder.png') }}";
                    this.alt = 'Image not available';
                });
            });

            document.querySelectorAll('a[href="#main-content"]').forEach(function(link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    const mainContent = document.getElementById('main-content');

                    if (mainContent) {
                        mainContent.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                        mainContent.focus({
                            preventScroll: true
                        });
                    }
                });
            });
        });
    </script>

    <style>
        .programme-shell {
            background: #fff;
        }

        .programme-hero {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 45%, #084298 100%);
        }

        .metric-icon {
            width: 3rem;
            height: 3rem;
            flex-shrink: 0;
        }

        .coordinator-avatar {
            width: 148px;
            height: 148px;
            object-fit: cover;
        }

        .assistant-avatar {
            width: 96px;
            height: 96px;
            object-fit: cover;
        }

        .detail-tile,
        .hover-lift {
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }

        .hover-lift:hover,
        .detail-tile:hover {
            transform: translateY(-4px);
            box-shadow: 0 1rem 2.5rem rgba(13, 110, 253, 0.08) !important;
            border-color: rgba(13, 110, 253, 0.2) !important;
        }

        @media (max-width: 991.98px) {
            .page-actions .btn {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .detail-tile,
            .hover-lift,
            html {
                scroll-behavior: auto;
            }

            .detail-tile,
            .hover-lift {
                transition: none !important;
            }

            .hover-lift:hover,
            .detail-tile:hover {
                transform: none;
            }
        }

        @media print {
            body {
                background: #fff !important;
                color: #212529 !important;
            }

            .no-print,
            .modern-breadcrumb-wrapper {
                display: none !important;
            }

            .programme-shell,
            .card,
            .detail-tile {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }

            .programme-hero {
                background: #f8f9fa !important;
                color: #212529 !important;
            }

            .programme-hero .text-white,
            .programme-hero .badge,
            .programme-hero .text-white-50 {
                color: #212529 !important;
            }

            .programme-hero .badge {
                border: 1px solid #dee2e6 !important;
                background: #fff !important;
            }

            img {
                max-width: 160px !important;
            }
        }
    </style>
@endsection
