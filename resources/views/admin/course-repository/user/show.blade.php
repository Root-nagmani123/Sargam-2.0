@extends('admin.layouts.master')

@section('title', ($repository->course_repository_name ?? 'Repository Details') . ' | Course Repository')

@section('setup_content')
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

    $childCount = $repository->children->count();
    $documentCount = $documents->count();
@endphp

<div class="cru-page">
    <div class="container-fluid px-3 px-md-4 py-4" id="main-content">
        <x-breadcrum
            :title="$repository->course_repository_name"
            :items="$crumbItems"
        />

        @if(isset($courses) && isset($subjects) && isset($faculties))
            @include('admin.course-repository.user.partials.filter-card', [
                'route' => route('admin.course-repository.user.show', $repository->pk),
                'courses' => $courses,
                'subjects' => $subjects,
                'faculties' => $faculties,
                'filters' => $filters ?? [],
            ])
        @endif

        @if($childCount === 0 && $documentCount === 0)
            <div class="card border-0 shadow-sm rounded-4 text-center py-5 px-3">
                <div class="card-body">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-secondary mb-3 cru-empty-icon">
                        <i class="bi bi-inbox fs-2" aria-hidden="true"></i>
                    </span>
                    <h2 class="h5 fw-semibold text-dark mb-2">No sub-categories or documents</h2>
                    <p class="text-muted small mb-0 mx-auto" style="max-width: 28rem;">
                        Nothing is available in this repository yet. Adjust filters or explore another category.
                    </p>
                </div>
            </div>
        @else
            @if($childCount > 0)
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h2 class="h6 mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-diagram-3 text-primary" aria-hidden="true"></i>
                            Sub-categories
                        </h2>
                        <span class="badge rounded-pill text-bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-semibold">
                            {{ $childCount }} {{ Str::plural('category', $childCount) }}
                        </span>
                    </div>
                    @include('admin.course-repository.user.partials.page-toolbar', ['showViewToggle' => true])
                </div>

                <div class="course-cards-grid mb-4 mb-md-5" id="courseCardsGrid">
                    <div class="cru-view-cards">
                        <div class="row g-3 g-md-4">
                            @foreach ($repository->children as $child)
                                @include('admin.course-repository.user.partials.repository-card', [
                                    'repository' => $child,
                                    'cardRoute' => route('admin.course-repository.user.show', $child->pk),
                                ])
                            @endforeach
                        </div>
                    </div>
                    @include('admin.course-repository.user.partials.repository-list-table', [
                        'items' => $repository->children,
                        'listTableId' => 'cruRepoListTableShow',
                        'listRouteMode' => 'show',
                    ])
                </div>
            @endif

            @if($documentCount > 0)
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h2 class="h6 mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-text text-primary" aria-hidden="true"></i>
                        Documents
                    </h2>
                    <span class="badge rounded-pill text-bg-secondary-subtle text-secondary border px-3 py-2 fw-semibold">
                        {{ $documentCount }} {{ Str::plural('item', $documentCount) }}
                    </span>
                </div>

                @include('admin.course-repository.user.partials.documents-table', ['documents' => $documents])
            @endif
        @endif
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection
