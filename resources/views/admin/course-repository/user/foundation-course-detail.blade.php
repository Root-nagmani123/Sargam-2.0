@extends('admin.layouts.master')

@section('title', 'Foundation Course-' . $courseCode . ' | Course Repository Admin')

@section('setup_content')
<div class="cru-page">
    <div class="container-fluid px-3 px-md-4 pt-3 pb-0">
        <x-breadcrum title="Foundation Course-{{ $courseCode }}"></x-breadcrum>
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
                                    'cardRoute' => route('admin.course-repository.user.show', $repository->pk),
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
