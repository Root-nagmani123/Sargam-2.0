@extends('admin.layouts.master')

@section('title', 'Foundation Course-' . $courseCode . ' | Course Repository Admin')

@section('content')
<div class="d-flex">
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
                            <span class="material-icons material-symbols-rounded fs-4 text-dark">arrow_back</span>
                        </button>
                        <h1 class="h2 mb-0 fw-bold text-dark">Foundation Course-{{ $courseCode }}</h1>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="javascript:void(0)" class="btn btn-outline-primary">
                            <span class="material-icons material-symbols-rounded me-1">cloud_upload</span> Upload Documents
                        </a>
                        <a href="javascript:void(0)" class="btn btn-primary">
                            <span class="material-icons material-symbols-rounded me-1">add_circle</span> Add Category
                        </a>
                    </div>
                </div>
            </div>

    <div class="d-flex cru-layout-with-sidebar align-items-stretch w-100">

        <main class="flex-grow-1 min-vw-0">
            <div class="container-fluid px-3 px-md-4 py-4" id="main-content">
                <div class="cru-panel bg-white border rounded-3 shadow-sm p-3 p-md-4">
                    @if(isset($courses) && isset($subjects) && isset($faculties))
                    @include('admin.course-repository.user.partials.filter-card', [
                        'route' => route('admin.course-repository.user.foundation-course.detail', $courseCode),
                        'courses' => $courses,
                        'subjects' => $subjects,
                        'faculties' => $faculties,
                        'filters' => $filters,
                    ])
                    @endif

                    @include('admin.course-repository.user.partials.page-toolbar', ['showViewToggle' => true])

                    @if($repositories->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox display-6 d-block mb-2" aria-hidden="true"></i>
                        <p class="mb-0">No materials found.</p>
                    </div>
                    @else
                    <div class="course-cards-grid" id="courseCardsGrid">
                        <div class="row g-3 g-md-4">
                            @foreach($repositories as $repository)
                                @include('admin.course-repository.user.partials.repository-card', [
                                    'repository' => $repository,
                                    'cardRoute' => route('admin.course-repository.user.show', encrypt($repository->pk)),
                                ])
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </main>
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection
