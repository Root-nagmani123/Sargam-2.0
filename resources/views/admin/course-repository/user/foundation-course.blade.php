@extends('admin.layouts.timetable')

@section('title', 'Foundation Course | Course Repository Admin')

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
                        <span class="material-icons material-symbols-rounded fs-4 text-dark">arrow_back</span>
                    </button>
                    <h1 class="h2 mb-0 fw-bold text-dark">Foundation Course</h1>
                </div>
            </div>

            <!-- Filter Card -->
            @include('admin.course-repository.user.partials.filter-card', [
                'route' => route('admin.course-repository.user.foundation-course'),
                'courses' => $courses,
                'subjects' => $subjects,
                'faculties' => $faculties,
                'filters' => $filters,
            ])

            <!-- Material Cards Grid -->
            <div class="material-cards-grid mt-4">
                <div class="row g-4">
                    @forelse($repositories as $repository)
                        <div class="col-md-4 col-lg-4">
                            <div class="card material-card shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="material-icon-wrapper">
                                            <span class="material-icons material-symbols-rounded">folder</span>
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
                    @empty
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <span class="material-icons material-symbols-rounded me-2">info</span>
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