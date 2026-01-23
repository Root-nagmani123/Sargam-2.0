@extends('admin.layouts.timetable')

@section('title', 'Foundation Course-' . $courseCode . ' | Course Repository Admin')

@section('content')
<div class="d-flex">
    <!-- Left Sidebar -->
    <aside class="course-sidebar-wrapper">
        <x-course-sidebar />
    </aside>

    <!-- Main Content -->
    <main class="flex-grow-1">
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
                    <h1 class="h2 mb-0 fw-bold text-dark">Foundation Course-{{ $courseCode }}</h1>
                </div>
            </div>

            <!-- Filter Card -->
            @include('admin.course-repository.user.partials.filter-card', [
                'route' => route('admin.course-repository.user.foundation-course.detail', $courseCode),
                'courses' => $courses,
                'subjects' => $subjects,
                'faculties' => $faculties,
                'filters' => $filters,
            ])

            <!-- Material Cards Grid -->
            <div class="material-cards-grid mt-4">
                <div class="row g-4">
                    @php
                        $cardColors = ['bg-success-subtle', 'bg-warning-subtle', 'bg-info-subtle', 'bg-primary-subtle'];
                        $colorIndex = 0;
                    @endphp
                    @forelse($repositories as $repository)
                        <div class="col-md-6 col-lg-6">
                            <div class="card material-card shadow-sm h-100 {{ $cardColors[$colorIndex % count($cardColors)] }}">
                                <div class="card-body">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="material-icon-wrapper">
                                            <i class="bi bi-folder-fill"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="card-title fw-bold mb-2">{{ $repository->course_repository_name }}</h5>
                                            <p class="text-muted small mb-0">
                                                {{ $repository->documents->count() }} Files • {{ $repository->children->count() }} Resources
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @php $colorIndex++; @endphp
                    @empty
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                No materials found.
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </main>
</div>

<link rel="stylesheet" href="{{ asset('css/course-repository-user.css') }}">
@endsection