@extends('admin.layouts.master')

@section('title', 'Foundation Course | Course Repository Admin')

@section('content')
<div class="d-flex">
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

                    @include('admin.course-repository.user.partials.page-toolbar', ['showViewToggle' => true])

                    <div class="course-cards-grid" id="courseCardsGrid">
                        <div class="row g-3 g-md-4">
                            @forelse($repositories as $repository)
                                @include('admin.course-repository.user.partials.repository-card', ['repository' => $repository])
                            @empty
                                <div class="col-12 text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-6 d-block mb-2" aria-hidden="true"></i>
                                    <p class="mb-0 fw-semibold">No materials found.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection
