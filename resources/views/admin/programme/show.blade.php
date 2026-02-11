@extends('admin.layouts.timetable')
@section('title', 'Course Details')
@section('content')
    <main class="container-fluid px-3 px-md-4 py-3 py-md-4" id="main-content" role="main">
        <!-- Action Bar - Bootstrap 5.3 (stack, focus-ring, responsive) -->
        <div class="mb-3 mb-md-4 d-print-none">
            <div class="card border border-primary-subtle shadow-sm rounded-4 overflow-hidden">
                <div class="card-body py-3 px-3 px-md-4">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-lg-7">
                            <div class="vstack gap-1">
                                <nav aria-label="Breadcrumb">
                                    <ol class="breadcrumb mb-0 d-inline-flex align-items-center gap-2 flex-wrap">
                                        <li class="breadcrumb-item">
                                            <a href="{{ route('programme.index') }}" class="text-decoration-none text-primary d-inline-flex align-items-center gap-1 fw-medium focus-ring focus-ring-primary rounded-2">
                                                <i class="bi bi-arrow-left-short fs-5"></i>
                                                <span class="d-none d-sm-inline">Programs</span>
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item active text-truncate fw-semibold text-primary-emphasis" aria-current="page">Course Details</li>
                                    </ol>
                                </nav>
                                <p class="text-body-secondary small mb-0">
                                    Viewing <span class="fw-semibold text-body-emphasis">{{ $course->couse_short_name ?? $course->course_name }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-12 col-lg-5">
                            <div class="hstack flex-wrap gap-2 justify-content-lg-end justify-content-start" role="group" aria-label="Course actions">
                                <a href="{{ route('programme.index') }}" class="btn btn-outline-primary btn-sm rounded-pill d-inline-flex align-items-center gap-1 focus-ring focus-ring-primary">
                                    <i class="bi bi-list-ul"></i>
                                    <span class="d-none d-sm-inline">All Programs</span>
                                </a>
                                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill d-inline-flex align-items-center gap-1 focus-ring focus-ring-secondary" aria-label="Print this page">
                                    <i class="bi bi-printer"></i>
                                    <span class="d-none d-sm-inline">Print</span>
                                </button>
                                <a href="{{ route('programme.download.pdf', ['id' => encrypt($course->pk)]) }}" class="btn btn-danger btn-sm rounded-pill d-inline-flex align-items-center gap-1 focus-ring focus-ring-danger" aria-label="Download PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                    <span class="d-none d-sm-inline">PDF</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Header Card - Bootstrap 5.3 (rounded-4, gradient, badges) -->
        <div class="card border-0 shadow rounded-4 overflow-hidden mb-3 mb-md-4">
            <div class="card-header bg-primary bg-gradient text-white py-3 py-md-4 border-0">
                <div class="row align-items-center g-2 g-md-3">
                    <div class="col-12 col-md-8">
                        <h1 class="card-title h4 h-md-3 mb-2 fw-bold lh-sm text-white">{{ $course->course_name }}</h1>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge bg-white bg-opacity-25 text-white px-2 py-1 px-md-3 py-md-2 rounded-pill">{{ $course->couse_short_name }}</span>
                            <span class="badge bg-white bg-opacity-15 text-white px-2 py-1 px-md-3 py-md-2 rounded-pill">
                                <i class="bi bi-calendar3 me-1"></i>{{ $course->course_year }}
                            </span>
                            @if($course->course_code)
                            <span class="badge bg-white bg-opacity-10 text-white px-2 py-1 rounded-pill small">
                                <i class="bi bi-upc me-1"></i>{{ $course->course_code }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="col-12 col-md-4 text-md-end small text-white text-opacity-90">
                        @if($course->course_code)
                        <span class="d-none d-md-inline"><i class="bi bi-upc me-1"></i>Code: {{ $course->course_code }}</span>
                        @else
                        <span><i class="bi bi-upc me-1"></i>Code: N/A</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Course Details -->
            <div class="card-body p-3 p-lg-4 bg-body">
                <!-- Summary strip - Bootstrap 5.3 subtle bg, hstack -->
                <div class="hstack flex-wrap gap-2 gap-md-3 small mb-3 px-3 py-2 rounded-3 bg-primary-subtle border border-primary-subtle">
                    <span class="text-body-emphasis fw-medium">{{ $course->course_year }}</span>
                    @if($course->duration)
                    <span class="text-body-secondary">·</span>
                    <span class="text-body-secondary">{{ $course->duration }}</span>
                    @endif
                    @if($course->course_type)
                    <span class="text-body-secondary">·</span>
                    <span class="text-body-secondary">{{ $course->course_type }}</span>
                    @endif
                    <span class="text-body-secondary">·</span>
                    <span class="text-body-secondary">{{ count($assistantCoordinatorsData) + 1 }} faculty</span>
                </div>

                <div class="row g-3 g-lg-4 align-items-start">
                    <!-- Left: Course info + Coordinator -->
                    <div class="col-12 col-lg-7">
                        <section aria-labelledby="course-info-title" class="programme-info-section mb-3 mb-md-4">
                            <h2 class="h5 mb-3 fw-bold d-flex align-items-center gap-2 text-primary border-bottom border-primary-subtle pb-2" id="course-info-title">
                                <span class="rounded-2 p-2 bg-primary-subtle text-primary"><i class="bi bi-info-square"></i></span>
                                Course Information
                            </h2>

                            <div class="row g-2 g-md-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold text-body-secondary small text-uppercase mb-1">Course Name</label>
                                    <div class="border-start border-3 border-primary ps-2 ps-md-3 py-2 rounded-end bg-body-secondary bg-opacity-50 text-break">
                                        {{ $course->course_name }}
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold text-body-secondary small text-uppercase mb-1">Short Name</label>
                                    <div class="border-start border-3 border-primary ps-2 ps-md-3 py-2 rounded-end bg-body-secondary bg-opacity-50">
                                        {{ $course->couse_short_name }}
                                    </div>
                                </div>
                                @if($course->duration)
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold text-body-secondary small text-uppercase mb-1">Duration</label>
                                    <div class="border-start border-3 border-primary ps-2 ps-md-3 py-2 rounded-end bg-body-secondary bg-opacity-50">
                                        {{ $course->duration }}
                                    </div>
                                </div>
                                @endif
                                @if($course->course_type)
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold text-body-secondary small text-uppercase mb-1">Course Type</label>
                                    <div class="border-start border-3 border-primary ps-2 ps-md-3 py-2 rounded-end bg-body-secondary bg-opacity-50">
                                        {{ $course->course_type }}
                                    </div>
                                </div>
                                @endif
                            </div>

                            @if($course->description)
                            <div class="mt-3">
                                <label class="form-label fw-semibold text-body-secondary small text-uppercase mb-2">Description</label>
                                <div class="border-start border-3 border-info ps-2 ps-md-3 py-2 py-md-3 rounded-end bg-info-subtle">
                                    {{ $course->description }}
                                </div>
                            </div>
                            @endif
                        </section>

                        <!-- Course Coordinator -->
                        <section aria-labelledby="faculty-title-left" class="programme-faculty-section">
                            <h2 class="h6 fw-bold d-flex align-items-center gap-2 text-primary border-bottom border-primary-subtle pb-2 mb-2" id="faculty-title-left">
                                <span class="rounded-2 p-2 bg-primary-subtle text-primary"><i class="bi bi-person-badge-fill"></i></span>
                                Course Coordinator
                            </h2>

                            <div class="card border border-warning-subtle shadow-sm overflow-hidden rounded-4 coordinator-card">
                                <div class="card-body p-3">
                                    <div class="row align-items-center g-3">
                                        <div class="col-auto">
                                            <div class="position-relative">
                                                <img src="{{ asset('storage/' . ($coordinatorFaculty->photo_uplode_path ?? 'default-profile.jpg')) }}"
                                                     alt="Photo of {{ $coordinatorName }}"
                                                     onerror="this.src='{{ asset('images/user-placeholder.png') }}'"
                                                     class="rounded-circle border border-3 border-warning object-fit-cover programme-coordinator-img">
                                                <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-warning text-dark border border-2 border-white p-1">
                                                    <i class="bi bi-star-fill" style="font-size: 0.6rem;"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <h3 class="h6 fw-bold mb-1">{{ $coordinatorName }}</h3>
                                            <span class="badge bg-warning bg-opacity-25 text-warning-emphasis px-2 py-1 rounded-pill small">Primary Coordinator</span>
                                            @if($coordinatorFaculty->designation ?? false)
                                            <p class="text-body-secondary mb-0 mt-1 small d-flex align-items-center gap-1">
                                                <i class="bi bi-briefcase-fill text-primary"></i>{{ $coordinatorFaculty->designation }}
                                            </p>
                                            @endif
                                            @if($coordinatorFaculty->department ?? false)
                                            <p class="text-body-secondary mb-0 small d-flex align-items-center gap-1">
                                                <i class="bi bi-building text-primary"></i>{{ $coordinatorFaculty->department }}
                                            </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Right: Overview + Assistants + Additional -->
                    <div class="col-12 col-lg-5">
                        <div class="vstack gap-3">
                            <!-- Course Overview - list-group with subtle styling -->
                            <div class="card border border-primary-subtle shadow-sm overflow-hidden rounded-4">
                                <div class="card-header bg-primary-subtle border-0 py-2 px-3">
                                    <h3 class="h6 mb-0 fw-bold d-flex align-items-center gap-2 text-primary">
                                        <i class="bi bi-graph-up-arrow"></i>Overview
                                    </h3>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 border-0 border-bottom border-primary-subtle">
                                        <span class="text-body-secondary small">Year</span>
                                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">{{ $course->course_year }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 border-0 border-bottom border-primary-subtle">
                                        <span class="text-body-secondary small">Status</span>
                                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Active</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 border-0 border-bottom border-primary-subtle">
                                        <span class="text-body-secondary small">Faculty</span>
                                        <span class="badge rounded-pill bg-info-subtle text-info-emphasis">{{ count($assistantCoordinatorsData) + 1 }}</span>
                                    </li>
                                    @if($course->start_year)
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 border-0 border-bottom border-primary-subtle">
                                        <span class="text-body-secondary small">Start</span>
                                        <span class="small fw-medium">{{ \Carbon\Carbon::parse($course->start_year)->format('d M Y') }}</span>
                                    </li>
                                    @endif
                                    @if($course->end_date)
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 border-0">
                                        <span class="text-body-secondary small">End</span>
                                        <span class="small fw-medium">{{ \Carbon\Carbon::parse($course->end_date)->format('d M Y') }}</span>
                                    </li>
                                    @endif
                                </ul>
                            </div>

                            <!-- Assistants -->
                            <div class="card border border-primary-subtle shadow-sm rounded-4">
                                <div class="card-header bg-body border-bottom border-primary-subtle py-2 px-3">
                                    <h3 class="h6 mb-0 d-flex align-items-center gap-2 text-body-emphasis">
                                        <i class="bi bi-person-badge text-primary"></i>Assistant Coordinators
                                        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">{{ count($assistantCoordinatorsData) }}</span>
                                    </h3>
                                </div>
                                <div class="card-body p-2 programme-scroll-body">
                                    @if(count($assistantCoordinatorsData) > 0)
                                    <div class="vstack gap-2">
                                        @foreach($assistantCoordinatorsData as $assistant)
                                        <div class="card border border-primary-subtle programme-assistant-card rounded-3 overflow-hidden">
                                            <div class="card-body p-2 d-flex align-items-center gap-2">
                                                <img src="{{ asset('storage/' . $assistant['photo']) }}"
                                                     alt="{{ $assistant['name'] }}"
                                                     onerror="this.src='{{ asset('images/user-placeholder.png') }}'"
                                                     class="rounded-circle border border-2 border-primary-subtle object-fit-cover programme-assistant-img flex-shrink-0">
                                                <div class="min-w-0 flex-grow-1">
                                                    <h5 class="card-title fw-semibold mb-0 small text-truncate">{{ $assistant['name'] }}</h5>
                                                    <span class="badge bg-primary-subtle text-primary-emphasis px-2 py-0 rounded-pill small">{{ $assistant['role'] }}</span>
                                                    @if($assistant['designation'] ?? false)
                                                    <p class="small text-body-secondary mb-0 text-truncate">{{ $assistant['designation'] }}</p>
                                                    @endif
                                                </div>
                                                @if($assistant['email'] ?? false)
                                                <a href="mailto:{{ $assistant['email'] }}" class="btn btn-sm btn-outline-primary rounded-pill flex-shrink-0 focus-ring focus-ring-primary" aria-label="Email {{ $assistant['name'] }}">
                                                    <i class="bi bi-envelope"></i>
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <div class="text-center py-4 rounded-3 bg-body-secondary">
                                        <i class="bi bi-people text-body-tertiary" style="font-size: 2rem;"></i>
                                        <p class="text-body-secondary small mb-0 mt-2">No assistant coordinators</p>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Additional Information -->
                            @if($course->objectives || $course->learning_outcomes || $course->prerequisites)
                            <div class="card border border-primary-subtle shadow-sm rounded-4">
                                <div class="card-header bg-body border-bottom border-primary-subtle py-2 px-3">
                                    <h3 class="h6 fw-bold mb-0 d-flex align-items-center gap-2 text-primary">
                                        <i class="bi bi-journal-text"></i>Additional Information
                                    </h3>
                                </div>
                                <div class="card-body p-2 small programme-scroll-body">
                                    @if($course->objectives)
                                    <p class="mb-2">
                                        <span class="fw-semibold text-info-emphasis d-inline-flex align-items-center gap-1"><i class="bi bi-bullseye"></i>Objectives</span><br>
                                        <span class="text-body-secondary">{{ $course->objectives }}</span>
                                    </p>
                                    @endif
                                    @if($course->learning_outcomes)
                                    <p class="mb-2">
                                        <span class="fw-semibold text-success-emphasis d-inline-flex align-items-center gap-1"><i class="bi bi-check2-circle"></i>Learning Outcomes</span><br>
                                        <span class="text-body-secondary">{{ $course->learning_outcomes }}</span>
                                    </p>
                                    @endif
                                    @if($course->prerequisites)
                                    <p class="mb-0">
                                        <span class="fw-semibold text-warning-emphasis d-inline-flex align-items-center gap-1"><i class="bi bi-list-check"></i>Prerequisites</span><br>
                                        <span class="text-body-secondary">{{ $course->prerequisites }}</span>
                                    </p>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Footer - Bootstrap 5.3 -->
            <div class="card-footer bg-body-secondary bg-opacity-50 border-0 border-top border-primary-subtle py-2 px-3 px-md-4">
                <div class="hstack flex-wrap gap-2 justify-content-between align-items-center">
                    <span class="text-body-secondary small d-flex align-items-center gap-1">
                        <i class="bi bi-clock-history"></i>
                        Updated {{ \Carbon\Carbon::parse($course->updated_at ?? now())->format('d M Y, h:i A') }}
                    </span>
                    <button type="button" onclick="window.print()" class="btn btn-outline-primary btn-sm rounded-pill focus-ring focus-ring-primary">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                </div>
            </div>
        </div>

        <!-- System Footer -->
        <footer class="mt-4 pt-3 pt-md-4 border-top border-primary-subtle text-center text-body-secondary small no-print">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-6 text-md-start">
                    <p class="mb-0 d-flex align-items-center justify-content-center justify-content-md-start gap-1">
                        <i class="bi bi-shield-check text-primary"></i>
                        Official Course Information System
                    </p>
                </div>
                <div class="col-12 col-md-6 text-md-end">
                    <p class="mb-0">
                        {{ \Carbon\Carbon::now()->format('d F Y, h:i A') }}
                        <span class="opacity-50 mx-1">·</span>
                        v2.1
                    </p>
                </div>
            </div>
            <p class="mt-3 mb-0">
                <a href="#main-content" class="btn btn-link btn-sm text-primary d-inline-flex align-items-center gap-1 rounded-pill programme-back-top focus-ring focus-ring-primary">
                    <i class="bi bi-arrow-up-circle-fill"></i>Back to top
                </a>
            </p>
        </footer>
    </main>
@endsection
