@extends('admin.layouts.timetable')

@section('title', 'Central Course Repository of LBSNAA | Lal Bahadur')

@section('content')

<!-- Main Content -->
<div class="container-fluid px-4 py-4" id="main-content">
    <!-- Title Section with Back Button -->
    <div class="title-section mb-4">
        <div class="d-flex align-items-center gap-3">
            <button type="button" 
                    onclick="window.history.back()" 
                    class="btn-back btn btn-link p-0 text-decoration-none"
                    aria-label="Go back">
                <i class="bi bi-arrow-left fs-4 text-dark"></i>
            </button>
            <h1 class="h2 mb-0 fw-bold text-dark">Central Course Repository of LBSNAA</h1>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card filter-card shadow-sm mb-4">
        <div class="card-body p-4" style="background-color: #FBF8F8;">
            <form method="GET" action="{{ route('admin.course-repository.user.filter-data') }}" id="filterForm">
                <div class="row g-3 align-items-end">
                    <!-- Date Filter -->
                    <div class="col-md-2">
                        <label for="filter_date" class="form-label fw-semibold mb-2">Date</label>
                        <div class="input-group">
                            <input type="date" 
                                   class="form-control" 
                                   id="filter_date" 
                                   name="date" 
                                   value="{{ $filters['date'] ?? '' }}">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-calendar3"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Course Filter -->
                    <div class="col-md-2">
                        <label for="filter_course" class="form-label fw-semibold mb-2">Course</label>
                        <select class="form-select" id="filter_course" name="course">
                            <option value="">Select Course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->pk }}" {{ $filters['course'] == $course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Subject Filter -->
                    <div class="col-md-2">
                        <label for="filter_subject" class="form-label fw-semibold mb-2">Subject</label>
                        <select class="form-select" id="filter_subject" name="subject">
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->pk }}" {{ $filters['subject'] == $subject->pk ? 'selected' : '' }}>
                                    {{ $subject->subject_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                   

                    <!-- Faculty Filter -->
                    <div class="col-md-2">
                        <label for="filter_faculty" class="form-label fw-semibold mb-2">Faculty</label>
                        <select class="form-select" id="filter_faculty" name="faculty">
                            <option value="">Select Faculty</option>
                            @foreach($faculties as $faculty)
                                <option value="{{ $faculty->pk }}" {{ $filters['faculty'] == $faculty->pk ? 'selected' : '' }}>
                                    {{ $faculty->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Apply Button -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            Apply Filters
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.course-repository.user.index') }}" class="btn btn-primary w-100 fw-semibold">
                            Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Course Cards Grid -->
    <div class="course-cards-grid">
        <div class="row g-4">
            @forelse($repositories as $repository)
                    <div class="col-md-4 col-lg-4">
                    <div class="card course-card shadow-sm h-100">
                        <div class="card-img-wrapper">
                            @php
                                $imageUrl = null;
                                if($repository->documents && $repository->documents->count() > 0) {
                                    $firstDoc = $repository->documents->first();
                                    if($firstDoc && $firstDoc->document_path) {
                                        $imageUrl = Storage::exists($firstDoc->document_path) 
                                            ? Storage::url($firstDoc->document_path) 
                                            : null;
                                    }
                                }
                                // Use placeholder if no image found
                                if(!$imageUrl) {
                                    $imageUrl = 'https://via.placeholder.com/400x200/004a93/ffffff?text=' . urlencode($repository->course_repository_name);
                                }
                            @endphp
                            <img src="{{ $imageUrl }}" 
                                 alt="{{ $repository->course_repository_name }}"
                                 class="card-img-top"
                                 loading="lazy">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-center fw-bold mb-3">{{ $repository->course_repository_name }}</h5>
                            <div class="mt-auto">
                                @php
                                    // Determine the appropriate route based on repository name
                                    $repositoryName = strtolower($repository->course_repository_name);
                                    $routeUrl = '#';
                                    
                                    if (strpos($repositoryName, 'foundation course') !== false) {
                                        // Route to Foundation Course listing page
                                        $routeUrl = route('admin.course-repository.user.foundation-course');
                                    } else {
                                        // Use user-specific repository view route
                                        $routeUrl = route('admin.course-repository.user.show', $repository->pk);
                                    }
                                @endphp
                                <a href="{{ $routeUrl }}" 
                                   class="btn btn-outline-primary w-100 fw-semibold">
                                    Click Here
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle me-2"></i>
                        No course repositories found.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Link to CSS -->
<link rel="stylesheet" href="{{ asset('css/course-repository-user.css') }}">
@endsection