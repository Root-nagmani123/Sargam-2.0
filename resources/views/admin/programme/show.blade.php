@extends('admin.layouts.master')

@section('title', 'Course Details')

@section('setup_content')
    @php
        $assistantCount = count($assistantCoordinatorsData ?? []);
        $totalFaculty = $assistantCount + 1;
        $coordinatorPhoto = $coordinatorFaculty?->photo_uplode_path ?: 'default-profile.jpg';
        $coordinatorName = $coordinatorName ?: 'Coordinator not assigned';
        $startDate = filled($course->start_year) ? \Carbon\Carbon::parse($course->start_year)->format('d M Y') : null;
        $endDate = filled($course->end_date) ? \Carbon\Carbon::parse($course->end_date)->format('d M Y') : null;
        $updatedAt = \Carbon\Carbon::parse($course->updated_at ?? now())->format('d M Y, h:i A');
        $generatedAt = \Carbon\Carbon::now()->format('d F Y, h:i A');
        $courseStatusLabel = (int) ($course->active_inactive ?? 1) === 1 ? 'Active' : 'Inactive';
        $primaryContactsCount = str_pad((string) 1, 2, '0', STR_PAD_LEFT);
        $createdByLabel = filled(data_get($course, 'created_by')) ? (string) data_get($course, 'created_by') : 'N/A';
    @endphp

    <main class="container-fluid programme-view-page" id="main-content" role="main" tabindex="-1">

        {{-- Page header --}}
        <div class="card border rounded-3 shadow-sm mb-4 bg-white">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap align-items-start gap-3">
                    <a href="{{ route('programme.index') }}"
                        class="btn btn-light border flex-shrink-0 d-inline-flex align-items-center justify-content-center programme-view-back border-0 p-0 bg-transparent"
                        aria-label="Back to course list">
                        <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">arrow_back</i>
                    </a>
                    <div class="flex-grow-1 min-w-0">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb small mb-2 text-body-secondary">
                                <li class="breadcrumb-item">Home</li> /
                                <li class="breadcrumb-item">Academic</li> /
                                <li class="breadcrumb-item">
                                    <a href="{{ route('programme.index') }}"
                                        class="link-secondary link-underline-opacity-0 link-underline-opacity-100-hover">Course
                                        Master</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">View Course</li> /
                            </ol>
                        </nav>
                        <h1 class="h3 fw-bold text-dark mb-0 text-break">{{ $course->course_name }}</h1>
                    </div>
                    <div class="d-flex flex-wrap gap-2 ms-auto no-print">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-3 px-3 fw-semibold"
                            data-print-trigger>
                            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">print</i>Print
                        </button>
                        <a href="{{ route('programme.download.pdf', ['id' => encrypt($course->pk)]) }}"
                            class="btn btn-primary btn-sm rounded-3 px-3 fw-semibold shadow-sm">
                            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">file_present</i>PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick stats --}}
        <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-5 g-3 mb-4">
            <div class="col">
                <div class="card border rounded-3 shadow-sm h-100">
                    <div class="card-body py-3 d-flex align-items-center gap-1">
                        <span
                            class="rounded-3 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0 programme-view-stat-icon">
                            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">calendar_month</i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">Course Year</div>
                            <div class="fw-bold text-dark text-truncate">{{ $course->course_year ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card border rounded-3 shadow-sm h-100">
                    <div class="card-body py-3 d-flex align-items-center gap-1">
                        <span
                            class="rounded-3 bg-success-subtle text-success d-inline-flex align-items-center justify-content-center flex-shrink-0 programme-view-stat-icon">
                            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">people</i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">Total Members</div>
                            <div class="fw-bold text-dark">{{ $totalFaculty }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card border rounded-3 shadow-sm h-100">
                    <div class="card-body py-3 d-flex align-items-center gap-1">
                        <span
                            class="rounded-3 bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center flex-shrink-0 programme-view-stat-icon">
                            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">folder</i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">Course Category</div>
                            <div class="fw-bold text-dark text-truncate">{{ $course->course_category ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card border rounded-3 shadow-sm h-100">
                    <div class="card-body py-3 d-flex align-items-center gap-1">
                        <span
                            class="rounded-3 bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center flex-shrink-0 programme-view-stat-icon">
                            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">description</i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">Course Type</div>
                            <div class="fw-bold text-dark text-truncate">{{ $course->course_type ?: 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card border rounded-3 shadow-sm h-100">
                    <div class="card-body py-3 d-flex align-items-center gap-1">
                        <span
                            class="rounded-3 bg-info-subtle text-info d-inline-flex align-items-center justify-content-center flex-shrink-0 programme-view-stat-icon">
                            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">check_circle</i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">Status</div>
                            <div class="fw-bold text-dark">{{ $courseStatusLabel }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            {{-- Left column --}}
            <div class="col-lg-8">
                <div class="card border rounded-3 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-dark mb-3">Course Information</h2>
                        <hr class="text-body-secondary opacity-25 my-0 mb-4">

                        <div class="row g-4">
                            <div class="col-sm-6">
                                <div class="small text-body-secondary mb-1">Course Code</div>
                                <div class="fw-bold text-dark">{{ $course->course_code ?: 'N/A' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-body-secondary mb-1">Course Name</div>
                                <div class="fw-bold text-dark text-break">{{ $course->course_name }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-body-secondary mb-1">Course Category</div>
                                <div class="fw-bold text-dark">{{ $course->course_category ?? 'N/A' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-body-secondary mb-1">Course Type</div>
                                <div class="fw-bold text-dark">{{ $course->course_type ?: 'N/A' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-body-secondary mb-1">Duration</div>
                                <div class="fw-bold text-dark">{{ $course->duration ?: 'N/A' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-body-secondary mb-1">Coordinator</div>
                                <div class="fw-bold text-dark">{{ $coordinatorName }}</div>
                            </div>
                        </div>

                        @if ($course->description)
                            <hr class="my-4 text-body-secondary opacity-25">
                            <div class="small text-body-secondary mb-1">Description</div>
                            <p class="mb-0 text-body-secondary">{{ $course->description }}</p>
                        @endif
                    </div>
                </div>

                <div class="card border rounded-3 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-dark mb-1">Faculty Team</h2>
                        <hr class="text-body-secondary opacity-25 my-3">

                        <p class="small text-body-secondary mb-2">Primary Contacts: {{ $primaryContactsCount }}</p>
                        <div class="rounded-3 bg-primary-subtle border border-primary-subtle p-3 mb-4">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <img src="{{ asset('storage/' . $coordinatorPhoto) }}" alt="Photo of {{ $coordinatorName }}"
                                    class="rounded-circle border border-2 border-white shadow-sm flex-shrink-0 programme-view-avatar">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-dark">{{ $coordinatorName }}</div>
                                    <div class="small text-body-secondary">Course Coordinator</div>
                                </div>
                                <span class="badge rounded-1 bg-white text-primary border px-3 py-2 fw-semibold ms-auto">
                                    Primary Contact
                                </span>
                            </div>
                        </div>

                        <p class="small text-body-secondary mb-2">Assistant Coordinators:
                            {{ str_pad((string) $assistantCount, 2, '0', STR_PAD_LEFT) }}</p>
                        @forelse ($assistantCoordinatorsData ?? [] as $assistant)
                            <div
                                class="rounded-3 bg-body-secondary bg-opacity-50 border p-3 {{ $loop->last ? 'mb-0' : 'mb-3' }}">
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <img src="{{ asset('storage/' . ($assistant['photo'] ?? 'default-profile.jpg')) }}"
                                        alt="Photo of {{ $assistant['name'] }}"
                                        class="rounded-circle border border-2 border-white shadow-sm flex-shrink-0 programme-view-avatar-sm">
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-bold text-dark">{{ $assistant['name'] }}</div>
                                        <div class="small text-body-secondary">{{ $assistant['role'] }}</div>
                                        @if ($assistant['designation'] ?? false)
                                            <div class="small text-body-secondary">{{ $assistant['designation'] }}</div>
                                        @endif
                                        @if ($assistant['department'] ?? false)
                                            <div class="small text-body-secondary">{{ $assistant['department'] }}</div>
                                        @endif
                                        @if ($assistant['email'] ?? false)
                                            <a href="mailto:{{ $assistant['email'] }}"
                                                class="small link-primary fw-semibold">Email</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3 bg-body-secondary bg-opacity-50 border p-3 text-body-secondary small">
                                No assistant coordinators assigned.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Right column --}}
            <div class="col-lg-4">
                <div class="card border rounded-3 shadow-sm programme-view-summary">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-dark mb-3">Record Summary</h2>
                        <hr class="text-body-secondary opacity-25 my-0 mb-4">

                        <div class="vstack gap-3">
                            <div>
                                <div class="small text-body-secondary mb-1">Course Code</div>
                                <div class="fw-bold text-dark">{{ $course->course_code ?: 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="small text-body-secondary mb-1">Created By</div>
                                <div class="fw-bold text-dark">{{ $createdByLabel }}</div>
                            </div>
                            <div>
                                <div class="small text-body-secondary mb-1">Start Date</div>
                                <div class="fw-bold text-dark">{{ $startDate ?: 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="small text-body-secondary mb-1">End Date</div>
                                <div class="fw-bold text-dark">{{ $endDate ?: 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="small text-body-secondary mb-1">Last Updated</div>
                                <div class="fw-bold text-dark">{{ $updatedAt }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($course->objectives || $course->learning_outcomes || $course->prerequisites)
            <div class="row g-4 mt-1">
                <div class="col-12">
                    <h2 class="h5 fw-bold text-dark mb-3">Additional Information</h2>
                </div>
                @if ($course->objectives)
                    <div class="col-md-4">
                        <div class="card border rounded-3 shadow-sm h-100">
                            <div class="card-body p-3">
                                <span class="badge rounded-1 bg-info-subtle text-info-emphasis mb-2">Course
                                    Objectives</span>
                                <p class="mb-0 small text-body-secondary">{{ $course->objectives }}</p>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($course->learning_outcomes)
                    <div class="col-md-4">
                        <div class="card border rounded-3 shadow-sm h-100">
                            <div class="card-body p-3">
                                <span class="badge rounded-1 bg-success-subtle text-success-emphasis mb-2">Learning
                                    Outcomes</span>
                                <p class="mb-0 small text-body-secondary">{{ $course->learning_outcomes }}</p>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($course->prerequisites)
                    <div class="col-md-4">
                        <div class="card border rounded-3 shadow-sm h-100">
                            <div class="card-body p-3">
                                <span class="badge rounded-1 bg-warning-subtle text-warning-emphasis mb-2">Prerequisites</span>
                                <p class="mb-0 small text-body-secondary">{{ $course->prerequisites }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-print-trigger]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const originalHtml = this.dataset.originalHtml || this.innerHTML;
                    this.dataset.originalHtml = originalHtml;
                    this.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Preparing...';
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
        .programme-view-stat-icon {
            width: 2.5rem;
            height: 2.5rem;
            font-size: 1.1rem;
        }

        .programme-view-back {
            width: 2.75rem;
            height: 2.75rem;
        }

        .programme-view-avatar {
            width: 3.5rem;
            height: 3.5rem;
            object-fit: cover;
        }

        .programme-view-avatar-sm {
            width: 3rem;
            height: 3rem;
            object-fit: cover;
        }

        @media print {
            body {
                background: #fff !important;
                color: #212529 !important;
            }

            .no-print {
                display: none !important;
            }

            .programme-view-page .card,
            .programme-view-summary {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }

            img {
                max-width: 120px !important;
            }
        }
    </style>
@endsection
