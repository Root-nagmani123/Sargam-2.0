@extends('admin.layouts.master')

@section('title', 'Central Course Repository of LBSNAA | Lal Bahadur')

@section('setup_content')
<div class="container-fluid cru-page px-3 px-md-4 py-4" id="main-content">
    <x-breadcrum title="Course Repository"></x-breadcrum>

    <div class="cru-panel bg-white border rounded-3 shadow-sm p-3 p-md-4">
        @include('admin.course-repository.user.partials.page-toolbar', ['showViewToggle' => true])

        @include('admin.course-repository.user.partials.filter-card', [
            'route' => route('admin.course-repository.user.index'),
            'courses' => $courses,
            'subjects' => $subjects,
            'faculties' => $faculties,
            'filters' => $filters,
        ])

        <div class="course-cards-grid" id="courseCardsGrid">
            <div class="row g-3 g-md-4">
                @forelse($repositories as $repository)
                    @include('admin.course-repository.user.partials.repository-card', ['repository' => $repository])
                @empty
                    <div class="col-12">
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-6 d-block mb-2" aria-hidden="true"></i>
                            <p class="mb-0 fw-semibold text-dark">No course repositories found.</p>
                            <p class="small mb-0 mt-1">Try adjusting your filters or reset to see all categories.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection
