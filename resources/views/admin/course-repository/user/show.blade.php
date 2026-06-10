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
    <div class="container-fluid px-3 px-md-4 py-4" id="cru-user-main">
        <x-breadcrum :title="$repository->course_repository_name" :items="$crumbItems" />

        <div id="cruFilterResults">
            @if($childCount === 0 && $documentCount === 0)
            <div class="card border-0 shadow-sm rounded-4 text-center py-5 px-3">
                <div class="card-body">
                    <span
                        class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-secondary mb-3 cru-empty-icon">
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
            @php
                $cruGridListTableId = 'cruRepoListTableShow';
                $cruGridColumnStorageKey = 'cru-repo-list-' . $cruGridListTableId;
                $cruGridColumns = [
                    ['key' => 'sno', 'label' => 'S. No.', 'locked' => true],
                    ['key' => 'name', 'label' => 'Category', 'default' => true],
                    ['key' => 'subcount', 'label' => 'Sub Categories', 'default' => true],
                ];
            @endphp
            <div class="d-flex flex-wrap align-items-end justify-content-end gap-3 mb-3">
                @include('admin.course-repository.user.partials.page-toolbar', ['showViewToggle' => true])
            </div>

            {{-- Shared filter toolbar (with inline column show/hide) — stays visible across both card and grid views --}}
            @if(isset($courses) && isset($subjects) && isset($faculties))
            @include('admin.course-repository.user.partials.filter-card', [
            'route' => route('admin.course-repository.user.show', $repository->pk),
            'courses' => $courses,
            'subjects' => $subjects,
            'faculties' => $faculties,
            'sectors' => $sectors ?? collect(),
            'ministries' => $ministries ?? collect(),
            'filters' => $filters ?? [],
            'columnToggle' => [
                'tableId' => $cruGridListTableId,
                'storageKey' => $cruGridColumnStorageKey,
                'columns' => $cruGridColumns,
            ],
            ])
            @endif

            <div class="course-cards-grid mb-4 mb-md-5" id="courseCardsGrid">
                <div class="cru-view-cards card card-body">
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
                'listTableId' => $cruGridListTableId,
                'listRouteMode' => 'show',
                'nameColumnLabel' => 'Category',
                'cruColumns' => $cruGridColumns,
                'cruColumnStorageKey' => $cruGridColumnStorageKey,
                ])
            </div>
            @endif

            @if($documentCount > 0)

            @include('admin.course-repository.user.partials.documents-table', [
            'documents' => $documents,
            'documentsAsDetails' => true,
            ])
            @endif
            @endif
        </div>
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection