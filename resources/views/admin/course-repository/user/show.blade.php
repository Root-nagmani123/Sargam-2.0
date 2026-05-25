@extends('admin.layouts.master')

@section('title', ($repository->course_repository_name ?? 'Repository Details') . ' | Course Repository')

@section('setup_content')
<div class="cru-page">
    <div class="container-fluid px-3 px-md-4 pt-3 pb-0">
        @php
            $crumbItems = [
                ['label' => 'Home', 'url' => route('admin.dashboard')],
                ['label' => 'Academic', 'url' => null],
                ['label' => 'Course Repository', 'url' => route('admin.course-repository.user.index')],
            ];
            if (!empty($ancestors)) {
                foreach ($ancestors as $ancestor) {
                    $crumbItems[] = [
                        'label' => $ancestor->course_repository_name,
                        'url' => route('admin.course-repository.user.show', $ancestor->pk),
                    ];
                }
            }
            $crumbItems[] = $repository->course_repository_name;
        @endphp

        <x-breadcrum
            :title="$repository->course_repository_name"
            :items="$crumbItems"
        />
    </div>

    <div class="d-flex cru-layout-with-sidebar align-items-stretch w-100">
        <main class="flex-grow-1 min-vw-0">
            <div class="container-fluid px-3 px-md-4 py-4">
                <div class="cru-panel bg-white border rounded-3 shadow-sm p-3 p-md-4">
                    @if(isset($courses) && isset($subjects) && isset($faculties))
                    @include('admin.course-repository.user.partials.filter-card', [
                        'route' => route('admin.course-repository.user.show', $repository->pk),
                        'courses' => $courses,
                        'subjects' => $subjects,
                        'faculties' => $faculties,
                        'filters' => $filters ?? [],
                    ])
                    @endif

                    @if($repository->children->count() == 0 && $documents->count() == 0)
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox display-6 d-block mb-2" aria-hidden="true"></i>
                        <p class="mb-0">No sub-categories or documents found in this repository.</p>
                    </div>
                    @else
                    @if($repository->children->count() > 0)
                    @include('admin.course-repository.user.partials.page-toolbar', ['showViewToggle' => true])
                    <div class="course-cards-grid mb-4" id="courseCardsGrid">
                        <div class="row g-3 g-md-4">
                            @foreach ($repository->children as $child)
                                @include('admin.course-repository.user.partials.repository-card', ['repository' => $child])
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($documents->count() > 0)
                    @include('admin.course-repository.user.partials.documents-table', ['documents' => $documents])
                    @endif
                    @endif
                </div>
            </div>
        </main>
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection
