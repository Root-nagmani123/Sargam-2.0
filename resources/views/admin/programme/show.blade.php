@extends('admin.layouts.timetable')
@section('title', 'Course Details')
@section('content')
    <main class="container-fluid px-3 px-md-4 py-4" id="main-content" role="main">
        <!-- Action Bar -->
        <div class="mb-4 d-print-none">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                <nav aria-label="Breadcrumb">
                    <ol class="breadcrumb mb-0 align-items-center flex-wrap gap-1 gap-md-2">
                        <li class="breadcrumb-item">
                            <a href="{{ route('programme.index') }}" class="text-decoration-none text-primary d-inline-flex align-items-center fw-medium">
                                <i class="bi bi-arrow-left-short fs-5"></i> Programs
                            </a>
                        </li>
                        <li class="breadcrumb-item active text-truncate fw-semibold" aria-current="page">Course Details</li>
                    </ol>
                </nav>
                <div class="d-flex flex-wrap gap-2" role="group" aria-label="Course actions">
                    <a href="{{ route('programme.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm" aria-label="Print this page">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                    <a href="{{ route('programme.download.pdf', ['id' => encrypt($course->pk)]) }}"
                       class="btn btn-danger btn-sm d-inline-flex align-items-center"
                       aria-label="Download PDF">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- Course Header -->
        <header class="card border-0 shadow-sm mb-4 overflow-hidden rounded-3">
            <div class="card-body p-0">
                <div class="text-bg-primary p-4 p-lg-5">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-lg-8">
                            <h1 class="h2 h3-lg mb-2 fw-bold lh-sm text-white">{{ $course->course_name }}</h1>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="badge bg-white bg-opacity-25 text-white">{{ $course->couse_short_name }}</span>
                                <span class="badge bg-white bg-opacity-15 text-white">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $course->course_year }}
                                </span>
                                @if($course->course_code)
                                <span class="text-white-50 small">
                                    <i class="bi bi-upc me-1"></i>{{ $course->course_code }}
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-lg-4 text-lg-end">
                            <p class="text-white-50 small mb-0">
                                Last updated: {{ \Carbon\Carbon::parse($course->updated_at ?? now())->format('d M Y, h:i A') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="row g-4">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Course Information -->
                <section class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4" aria-labelledby="course-info-title">
                    <div class="card-header bg-body-secondary border-0 py-3">
                        <h2 class="h5 mb-0 fw-bold d-flex align-items-center gap-2" id="course-info-title">
                            <span class="rounded-2 p-2 bg-primary bg-opacity-10 text-primary"><i class="bi bi-info-square"></i></span>
                            Course Information
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label small text-body-secondary text-uppercase fw-semibold mb-1">Course Name</label>
                                <p class="mb-0 border-start border-3 border-primary ps-3 py-2 bg-body-tertiary rounded-end">{{ $course->course_name }}</p>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small text-body-secondary text-uppercase fw-semibold mb-1">Short Name</label>
                                <p class="mb-0 border-start border-3 border-primary ps-3 py-2 bg-body-tertiary rounded-end">{{ $course->couse_short_name }}</p>
                            </div>
                            @if($course->duration)
                            <div class="col-sm-6">
                                <label class="form-label small text-body-secondary text-uppercase fw-semibold mb-1">Duration</label>
                                <p class="mb-0 border-start border-3 border-primary ps-3 py-2 bg-body-tertiary rounded-end">{{ $course->duration }}</p>
                            </div>
                            @endif
                            @if($course->course_type)
                            <div class="col-sm-6">
                                <label class="form-label small text-body-secondary text-uppercase fw-semibold mb-1">Course Type</label>
                                <p class="mb-0 border-start border-3 border-primary ps-3 py-2 bg-body-tertiary rounded-end">{{ $course->course_type }}</p>
                            </div>
                            @endif
                        </div>
                        @if($course->description)
                        <div class="mt-4 pt-3 border-top">
                            <label class="form-label small text-body-secondary text-uppercase fw-semibold mb-2">Description</label>
                            <div class="border-start border-3 border-info ps-3 py-3 bg-info bg-opacity-10 rounded-end">
                                {{ $course->description }}
                            </div>
                        </div>
                        @endif
                    </div>
                </section>

                <!-- Faculty Section -->
                <section class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4" aria-labelledby="faculty-title">
                    <div class="card-header bg-body-secondary border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h2 class="h5 mb-0 fw-bold d-flex align-items-center gap-2" id="faculty-title">
                            <span class="rounded-2 p-2 bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></span>
                            Course Faculty Team
                        </h2>
                        <span class="badge bg-primary rounded-pill">{{ count($assistantCoordinatorsData) + 1 }} Members</span>
                    </div>
                    <div class="card-body p-4">
                        <!-- Coordinator -->
                        <h3 class="h6 text-body-secondary mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-award-fill text-warning"></i> Course Coordinator
                        </h3>
                        <div class="card border-0 bg-warning bg-opacity-10 border-start border-4 border-warning rounded-3 mb-4">
                            <div class="card-body p-4">
                                <div class="row align-items-center g-4">
                                    <div class="col-auto">
                                        <div class="position-relative">
                                            <img src="{{ asset('storage/' . ($coordinatorFaculty->photo_uplode_path ?? 'default-profile.jpg')) }}"
                                                 alt="Photo of {{ $coordinatorName }}"
                                                 onerror="this.src='{{ asset('images/user-placeholder.png') }}'"
                                                 class="rounded-circle border border-3 border-warning object-fit-cover"
                                                 width="80" height="80">
                                            <span class="position-absolute bottom-0 end-0 badge bg-warning text-dark rounded-circle p-1">
                                                <i class="bi bi-star-fill small"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <h4 class="fw-bold mb-2">{{ $coordinatorName }}</h4>
                                        <span class="badge bg-warning text-dark mb-2">Primary Coordinator</span>
                                        @if($coordinatorFaculty->designation ?? false)
                                        <p class="text-body-secondary mb-1 small d-flex align-items-center gap-2">
                                            <i class="bi bi-briefcase text-primary"></i>{{ $coordinatorFaculty->designation }}
                                        </p>
                                        @endif
                                        @if($coordinatorFaculty->department ?? false)
                                        <p class="text-body-secondary mb-0 small d-flex align-items-center gap-2">
                                            <i class="bi bi-building text-primary"></i>{{ $coordinatorFaculty->department }}
                                        </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assistant Coordinators -->
                        @if(count($assistantCoordinatorsData) > 0)
                        <h3 class="h6 text-body-secondary mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-person-badge text-primary"></i> Assistant Coordinators
                            <span class="badge bg-secondary rounded-pill">{{ count($assistantCoordinatorsData) }}</span>
                        </h3>
                        <div class="row g-3">
                            @foreach($assistantCoordinatorsData as $assistant)
                            <div class="col-sm-6 col-xl-4">
                                <div class="card h-100 border border-body-secondary border-opacity-25 rounded-3 overflow-hidden">
                                    <div class="card-body text-center p-4">
                                        <img src="{{ asset('storage/' . $assistant['photo']) }}"
                                             alt="Photo of {{ $assistant['name'] }}"
                                             onerror="this.src='{{ asset('images/user-placeholder.png') }}'"
                                             class="rounded-circle border border-2 border-primary object-fit-cover mb-3"
                                             width="64" height="64">
                                        <h5 class="h6 fw-semibold mb-2">{{ $assistant['name'] }}</h5>
                                        <span class="badge bg-primary bg-opacity-10 text-primary mb-2">{{ $assistant['role'] }}</span>
                                        @if($assistant['designation'] ?? false)
                                        <p class="small text-body-secondary mb-3">{{ $assistant['designation'] }}</p>
                                        @endif
                                        @if($assistant['email'] ?? false)
                                        <a href="mailto:{{ $assistant['email'] }}" class="btn btn-sm btn-outline-primary rounded-pill" aria-label="Email {{ $assistant['name'] }}">
                                            <i class="bi bi-envelope me-1"></i> Email
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-5 bg-body-tertiary rounded-3">
                            <i class="bi bi-people display-6 text-body-secondary opacity-50"></i>
                            <p class="text-body-secondary mb-0 mt-2">No assistant coordinators assigned</p>
                        </div>
                        @endif
                    </div>
                </section>

                <!-- Additional Information -->
                @if($course->objectives || $course->learning_outcomes || $course->prerequisites)
                <section class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4" aria-labelledby="additional-info-title">
                    <div class="card-header bg-body-secondary border-0 py-3">
                        <h2 class="h5 mb-0 fw-bold d-flex align-items-center gap-2" id="additional-info-title">
                            <span class="rounded-2 p-2 bg-primary bg-opacity-10 text-primary"><i class="bi bi-journal-text"></i></span>
                            Additional Information
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            @if($course->objectives)
                            <div class="col-md-4">
                                <div class="card h-100 border-0 bg-info bg-opacity-10 border-top border-3 border-info rounded-3">
                                    <div class="card-body">
                                        <h3 class="h6 fw-bold mb-2 d-flex align-items-center gap-2 text-info">
                                            <i class="bi bi-bullseye"></i> Objectives
                                        </h3>
                                        <p class="card-text text-body-secondary small mb-0">{{ $course->objectives }}</p>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($course->learning_outcomes)
                            <div class="col-md-4">
                                <div class="card h-100 border-0 bg-success bg-opacity-10 border-top border-3 border-success rounded-3">
                                    <div class="card-body">
                                        <h3 class="h6 fw-bold mb-2 d-flex align-items-center gap-2 text-success">
                                            <i class="bi bi-check2-circle"></i> Learning Outcomes
                                        </h3>
                                        <p class="card-text text-body-secondary small mb-0">{{ $course->learning_outcomes }}</p>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($course->prerequisites)
                            <div class="col-md-4">
                                <div class="card h-100 border-0 bg-warning bg-opacity-10 border-top border-3 border-warning rounded-3">
                                    <div class="card-body">
                                        <h3 class="h6 fw-bold mb-2 d-flex align-items-center gap-2 text-warning text-dark">
                                            <i class="bi bi-list-check"></i> Prerequisites
                                        </h3>
                                        <p class="card-text text-body-secondary small mb-0">{{ $course->prerequisites }}</p>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </section>
                @endif
            </div>

            <!-- Sidebar: Course Overview -->
            <aside class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden sticky-lg-top" style="top: 1rem;">
                    <div class="card-header bg-primary bg-opacity-10 border-0 py-3">
                        <h3 class="h6 mb-0 fw-bold d-flex align-items-center gap-2 text-primary">
                            <i class="bi bi-graph-up-arrow"></i> Course Overview
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 py-3 px-4">
                                <span class="text-body-secondary">Course Year</span>
                                <span class="badge bg-primary rounded-pill">{{ $course->course_year }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                <span class="text-body-secondary">Status</span>
                                <span class="badge bg-success rounded-pill">Active</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                <span class="text-body-secondary">Total Faculty</span>
                                <span class="badge bg-info rounded-pill">{{ count($assistantCoordinatorsData) + 1 }}</span>
                            </li>
                            @if($course->start_year)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                <span class="text-body-secondary">Start Date</span>
                                <span class="fw-semibold small">{{ \Carbon\Carbon::parse($course->start_year)->format('d M Y') }}</span>
                            </li>
                            @endif
                            @if($course->end_date)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                                <span class="text-body-secondary">End Date</span>
                                <span class="fw-semibold small">{{ \Carbon\Carbon::parse($course->end_date)->format('d M Y') }}</span>
                            </li>
                            @endif
                        </ul>
                    </div>
                    <div class="card-footer bg-body-secondary border-0 py-3 px-4 d-print-none">
                        <button type="button" onclick="window.print()" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-printer me-1"></i> Print Details
                        </button>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Footer -->
        <footer class="mt-5 pt-4 border-top text-center text-body-secondary small no-print">
            <div class="row g-2 justify-content-center">
                <div class="col-auto d-flex align-items-center gap-1">
                    <i class="bi bi-shield-check"></i> Official Course Information System
                </div>
                <div class="col-12 col-md-auto">
                    Generated on {{ \Carbon\Carbon::now()->format('d F Y, h:i A') }} &middot; System Version: 2.1
                </div>
            </div>
            <p class="mt-3 mb-0">
                <a href="#main-content" class="text-decoration-none text-primary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-arrow-up-circle"></i> Back to top
                </a>
            </p>
        </footer>
    </main>
@endsection
