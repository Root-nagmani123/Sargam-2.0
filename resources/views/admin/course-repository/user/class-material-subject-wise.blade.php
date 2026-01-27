@extends('admin.layouts.timetable')

@section('title', 'Class Material (Subject Wise) | Course Repository Admin')

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
                    <h1 class="h2 mb-0 fw-bold text-dark">Class Material (Subject Wise)</h1>
                </div>
            </div>

            <!-- Filter Card -->
            @include('admin.course-repository.user.partials.filter-card', [
                'route' => route('admin.course-repository.user.class-material-subject-wise', $courseCode),
                'courses' => $courses,
                'subjects' => $subjects,
                'faculties' => $faculties,
                'filters' => $filters,
            ])

            <!-- Material Cards Grid -->
            <div class="material-cards-grid mt-4">
                <div class="row g-4">
                    @php
                        $cardColors = ['bg-success-subtle', 'bg-warning-subtle', 'bg-info-subtle'];
                        $colorIndex = 0;
                    @endphp
                    @foreach(['Class Material (Subject Wise)', 'Class Material (Week Wise)', 'Hindi Reading Material'] as $cardTitle)
                        <div class="col-md-4 col-lg-4">
                            <div class="card material-card shadow-sm h-100 {{ $cardColors[$colorIndex % count($cardColors)] }}">
                                <div class="card-body">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="material-icon-wrapper">
                                            <span class="material-icons material-symbols-rounded">folder</span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="card-title fw-bold mb-2">{{ $cardTitle }}</h5>
                                            <p class="text-muted small mb-0">12 Files • 2 Resources</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @php $colorIndex++; @endphp
                    @endforeach
                </div>
            </div>

            <!-- Subjects List -->
            <div class="subjects-list mt-4">
                <div class="row g-3">
                    @foreach($subjectsList as $subject)
                        <div class="col-md-6 col-lg-4">
                            <div class="card subject-item shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="subject-icon-wrapper">
                                                <span class="material-icons material-symbols-rounded">folder</span>
                                            </div>
                                            <span class="fw-semibold">{{ $subject->subject_name }}</span>
                                        </div>
                                        <span class="material-icons material-symbols-rounded">chevron_right</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </main>
</div>

<link rel="stylesheet" href="{{ asset('css/course-repository-user.css') }}">
@endsection