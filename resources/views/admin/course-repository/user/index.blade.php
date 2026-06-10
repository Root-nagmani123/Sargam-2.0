@extends('admin.layouts.master')

@section('title', 'Central Course Repository of LBSNAA | Lal Bahadur')

@section('setup_content')
<div class="cru-page">
    <div class="container-fluid px-3 px-md-4 py-4" id="main-content">
        <x-breadcrum title="Course Repository"></x-breadcrum>

        {{-- Filters --}}
        <div class="card filter-card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-0 px-3 px-md-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary cru-filter-icon-wrap">
                        <i class="bi bi-funnel-fill" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 class="h6 mb-0 fw-semibold text-dark">Filter repositories</h2>
                        <p class="mb-0 small text-muted">Refine categories by date, course, subject, week, or faculty</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4 pt-3">
                <form method="GET" action="{{ route('admin.course-repository.user.index') }}" id="filterForm">
                    <div class="row g-3 align-items-end cru-filter-row">
                        <div class="col-6 col-md-4 col-lg cru-filter-col">
                            <label for="filter_date" class="form-label small fw-semibold text-secondary mb-1">Date</label>
                            <div class="input-group input-group-sm">
                                <input type="date"
                                       class="form-control"
                                       id="filter_date"
                                       name="date"
                                       value="{{ $filters['date'] ?? '' }}">
                                <span class="input-group-text bg-white">
                                    <i class="bi bi-calendar3 text-muted" aria-hidden="true"></i>
                                </span>
                            </div>
                        </div>

                        <div class="col-6 col-md-4 col-lg cru-filter-col">
                            <label for="filter_course" class="form-label small fw-semibold text-secondary mb-1">Course</label>
                            <select class="form-select form-select-sm" id="filter_course" name="course">
                                <option value="">Select Course</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->pk }}" {{ ($filters['course'] ?? '') == $course->pk ? 'selected' : '' }}>
                                        {{ $course->course_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-4 col-lg cru-filter-col">
                            <label for="filter_subject" class="form-label small fw-semibold text-secondary mb-1">Subject</label>
                            <select class="form-select form-select-sm" id="filter_subject" name="subject">
                                <option value="">Select Subject</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->pk }}" {{ ($filters['subject'] ?? '') == $subject->pk ? 'selected' : '' }}>
                                        {{ $subject->subject_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-4 col-lg cru-filter-col">
                            <label for="filter_week" class="form-label small fw-semibold text-secondary mb-1">Week</label>
                            <select class="form-select form-select-sm" id="filter_week" name="week">
                                <option value="">Select Week</option>
                                @for($i = 1; $i <= 52; $i++)
                                    <option value="{{ $i }}" {{ ($filters['week'] ?? '') == $i ? 'selected' : '' }}>
                                        Week {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-6 col-md-4 col-lg cru-filter-col">
                            <label for="filter_faculty" class="form-label small fw-semibold text-secondary mb-1">Faculty</label>
                            <select class="form-select form-select-sm" id="filter_faculty" name="faculty">
                                <option value="">Select Faculty</option>
                                @foreach($faculties as $faculty)
                                    <option value="{{ $faculty->pk }}" {{ ($filters['faculty'] ?? '') == $faculty->pk ? 'selected' : '' }}>
                                        {{ $faculty->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-4 col-lg-auto cru-filter-col">
                            <label class="form-label small fw-semibold text-secondary mb-1 d-none d-lg-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold cru-btn-primary d-inline-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <span>Apply Filters</span>
                            </button>
                        </div>
                        <div class="col-6 col-md-4 col-lg-auto cru-filter-col">
                            <label class="form-label small fw-semibold text-secondary mb-1 d-none d-lg-block">&nbsp;</label>
                            <a href="{{ route('admin.course-repository.user.index') }}"
                               class="btn btn-outline-secondary btn-sm w-100 fw-semibold d-inline-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-x-circle" aria-hidden="true"></i>
                                <span>Clear Filters</span>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($repositories->count() > 0)
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <p class="mb-0 small text-muted">
                    <i class="bi bi-journal-bookmark me-1" aria-hidden="true"></i>
                    <span class="fw-semibold text-dark">{{ $repositories->count() }}</span>
                    {{ Str::plural('category', $repositories->count()) }} available
                </p>
                @include('admin.course-repository.user.partials.page-toolbar', ['showViewToggle' => true])
            </div>

            <div class="course-cards-grid" id="courseCardsGrid">
                <div class="cru-view-cards">
                    <div class="row g-3 g-md-4">
                        @foreach($repositories as $repository)
                            @include('admin.course-repository.user.partials.repository-card', ['repository' => $repository])
                        @endforeach
                    </div>
                </div>
                @include('admin.course-repository.user.partials.repository-list-table', [
                    'items' => $repositories,
                    'listTableId' => 'cruRepoListTableIndex',
                ])
            </div>
        @else
            <div class="card border-0 shadow-sm rounded-4 text-center py-5 px-3">
                <div class="card-body">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-secondary mb-3 cru-empty-icon">
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
