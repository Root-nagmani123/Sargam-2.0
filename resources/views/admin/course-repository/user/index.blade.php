@extends('admin.layouts.master')

@section('title', 'Central Course Repository of LBSNAA')

@section('setup_content')
<div class="cru-page">
    <div class="container-fluid" id="main-content">
        <x-breadcrum title="Course Repository"></x-breadcrum>

        {{-- Filters --}}

        @if($repositories->count() > 0)
        @php
            $cruGridListTableId = 'cruRepoListTableIndex';
            $cruGridColumnStorageKey = 'cru-repo-list-' . $cruGridListTableId;
            $cruGridColumns = [
                ['key' => 'sno', 'label' => 'S. No.', 'locked' => true],
                ['key' => 'name', 'label' => 'Course Name', 'default' => true],
                ['key' => 'subcount', 'label' => 'Sub Categories', 'default' => true],
            ];
        @endphp
        <div class="d-flex flex-wrap align-items-end justify-content-end gap-3 mb-3">
            @include('admin.course-repository.user.partials.page-toolbar', ['showViewToggle' => true])
        </div>

        <div class="course-cards-grid mb-4 mb-md-5" id="courseCardsGrid">
            <div class="cru-view-cards card card-body">
                <div class="row g-3 g-md-4">
                    @foreach($repositories as $repository)
                        @include('admin.course-repository.user.partials.repository-card', ['repository' => $repository])
                    @endforeach
                </div>
            </div>
            @include('admin.course-repository.user.partials.repository-list-table', [
            'items' => $repositories,
            'listTableId' => $cruGridListTableId,
            'cruColumns' => $cruGridColumns,
            'cruColumnStorageKey' => $cruGridColumnStorageKey,
            ])
        </div>
        @else
        <div class="card border-0 shadow-sm rounded-4 text-center py-5 px-3">
            <div class="card-body">
                <span
                    class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-secondary mb-3 cru-empty-icon">
                    <i class="bi bi-folder2-open fs-2" aria-hidden="true"></i>
                </span>
                <h3 class="h5 fw-semibold text-dark mb-2">No categories found</h3>
                <p class="text-muted small mb-0 mx-auto" style="max-width: 28rem;">
                    Try adjusting your filters or check back later for new course repository categories.
                </p>
            </div>
        </div>
        @endif
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection