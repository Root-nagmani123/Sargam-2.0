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
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" 
                                onclick="window.history.back()" 
                                class="btn-back btn btn-link p-0 text-decoration-none"
                                aria-label="Go back">
                            <i class="bi bi-arrow-left fs-4 text-dark"></i>
                        </button>
                        <h1 class="h2 mb-0 fw-bold text-dark">Foundation Course-{{ $courseCode }}</h1>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="javascript:void(0)" class="btn btn-outline-primary">
                            <i class="bi bi-upload me-1"></i> Upload Documents
                        </a>
                        <a href="javascript:void(0)" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> Add Category
                        </a>
                    </div>
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

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" id="foundationTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab" aria-controls="active" aria-selected="true">
                        Active
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive" type="button" role="tab" aria-controls="archive" aria-selected="false">
                        Archive
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="foundationTabContent">
                <!-- Active Tab -->
                <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
                    @if($repositories->isEmpty())
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle me-2"></i>
                            No materials found.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead style="background-color: #dc3545; color: white;">
                                    <tr>
                                        <th class="text-center fw-bold">S.No.</th>
                                        <th class="text-center fw-bold">Sub Category Name</th>
                                        <th class="text-center fw-bold">Details</th>
                                        <th class="text-center fw-bold">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($repositories as $index => $repository)
                                    <tr class="{{ $loop->odd ? 'table-light' : '' }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.course-repository.user.show', $repository->pk) }}" class="text-decoration-none fw-semibold">
                                                {{ $repository->course_repository_name }}
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.course-repository.user.show', $repository->pk) }}" class="text-decoration-none text-primary">
                                                {{ $repository->children->count() }} sub-categories
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.course-repository.user.show', $repository->pk) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Archive Tab -->
                <div class="tab-pane fade" id="archive" role="tabpanel" aria-labelledby="archive-tab">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-archive me-2"></i>
                        No archived materials found.
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<link rel="stylesheet" href="{{ asset('css/course-repository-user.css') }}">
@endsection