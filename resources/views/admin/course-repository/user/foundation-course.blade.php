@extends('admin.layouts.master')

@section('title', 'Foundation Course | Course Repository Admin')

@section('setup_content')
<div class="cru-page">
    <div class="container-fluid px-3 px-md-4 pt-3 pb-0">
        <x-breadcrum title="Foundation Course"></x-breadcrum>
    </div>

    <div class="d-flex cru-layout-with-sidebar align-items-stretch w-100">

        <main class="flex-grow-1 min-vw-0">
            <div class="container-fluid px-3 px-md-4 py-4" id="main-content">
                <div class="cru-panel bg-white border rounded-3 shadow-sm p-3 p-md-4">
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
