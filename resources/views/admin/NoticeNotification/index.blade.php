@extends('admin.layouts.master')

@section('title', 'Notice notification List')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@include('admin.NoticeNotification.partials.module-styles')
@endpush

@section('content')
<div class="container-fluid notice-module-page">
    <x-breadcrum title="Notice notification List"></x-breadcrum>

    <div class="card notice-card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <span class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0"
                        style="width: 2.5rem; height: 2.5rem;">
                        <i class="bi bi-megaphone-fill" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <span class="badge bg-primary-subtle text-primary fw-semibold text-uppercase mb-1">Notices</span>
                        <h4 class="card-title mb-0 text-truncate">Notice notification List</h4>
                        <p class="text-muted small mb-0 d-none d-md-block">Manage notices, display dates, and audience targeting</p>
                    </div>
                </div>
                <a href="{{ route('admin.notice.create') }}" class="btn btn-notice-save text-white rounded-3 px-3 flex-shrink-0">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                    <span>Add Notice Notification</span>
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3" role="alert">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
                    <div>
                        <strong>There were some problems with your request.</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3" role="alert">
                <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <div id="status-msg" class="mb-3"></div>

            <div class="notice-filter-panel rounded-3 p-3 p-md-4 mb-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-funnel text-primary" aria-hidden="true"></i>
                    <span class="fw-semibold text-body">Filter notices</span>
                </div>
                <form method="GET" action="{{ route('admin.notice.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label notice-form-label mb-1">
                                <i class="bi bi-tag me-1 text-primary" aria-hidden="true"></i>Notice Type
                            </label>
                            <select name="notice_category_master_pk" class="form-select js-choice" onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->pk }}" {{ request('notice_category_master_pk') == $category->pk ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label notice-form-label mb-1">
                                <i class="bi bi-book me-1 text-primary" aria-hidden="true"></i>Course
                            </label>
                            <select name="course_id" class="form-select js-choice" onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach($courses as $c)
                                <option value="{{ $c->id }}" {{ request('course_id') == $c->pk ? 'selected' : '' }}>
                                    {{ $c->course_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label notice-form-label mb-1">
                                <i class="bi bi-toggle-on me-1 text-primary" aria-hidden="true"></i>Status
                            </label>
                            <select name="status" class="form-select js-choice" onchange="this.form.submit()">
                                <option value="">All</option>
                                <option value="1" {{ request('status') == "1" ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('status') == "0" ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <a href="{{ route('admin.notice.index') }}" class="btn btn-outline-secondary w-100 rounded-3">
                                <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Reset Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <span class="text-muted small">
                    <i class="bi bi-list-ul me-1" aria-hidden="true"></i>
                    <strong class="text-body">{{ $notices->total() }}</strong> notice{{ $notices->total() !== 1 ? 's' : '' }} found
                </span>
            </div>

            <div class="table-responsive rounded-3 border">
                <table class="table table-hover align-middle mb-0 notice-table">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3" style="width: 60px;">S.N.</th>
                            <th scope="col">Notice Title</th>
                            <th scope="col">Notice Type</th>
                            <th scope="col" class="d-none d-lg-table-cell">Notice Sub Type</th>
                            <th scope="col" class="d-none d-xl-table-cell">Course Name</th>
                            <th scope="col" class="d-none d-xl-table-cell">Created By</th>
                            <th scope="col" class="d-none d-md-table-cell">Created Date</th>
                            <th scope="col">Display Date</th>
                            <th scope="col" class="d-none d-md-table-cell">Expiry Date</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center pe-3">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($notices as $index => $n)
                        @php $encId = Crypt::encrypt($n->pk); @endphp

                        <tr>
                            <td class="fw-semibold ps-3 text-muted">{{ $index + $notices->firstItem() }}</td>
                            <td>
                                <span class="fw-semibold text-body d-inline-block text-truncate" style="max-width: 220px;">
                                    {{ $n->notice_title }}
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-info-subtle text-info text-capitalize">
                                    {{ $n->category->name ?? $n->notice_type }}
                                </span>
                            </td>
                            <td class="d-none d-lg-table-cell text-muted small">
                                {{ $n->subcategory->name ?? '—' }}
                            </td>
                            <td class="d-none d-xl-table-cell">{{ $n->course->course_name ?? 'N/A' }}</td>
                            <td class="d-none d-xl-table-cell text-muted small">
                                {{ $n->user->first_name }} {{ $n->user->last_name }}
                            </td>
                            <td class="d-none d-md-table-cell text-muted small">
                                {{ \Carbon\Carbon::parse($n->created_date)->format('d-m-Y') }}
                            </td>
                            <td class="small">{{ \Carbon\Carbon::parse($n->display_date)->format('d-m-Y') }}</td>
                            <td class="d-none d-md-table-cell text-muted small">
                                {{ \Carbon\Carbon::parse($n->expiry_date)->format('d-m-Y') }}
                            </td>

                            <td class="text-center">
                                <div class="form-check form-switch d-inline-flex align-items-center justify-content-center mb-0">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="notices_notification" data-column="active_inactive"
                                        data-id="{{ $n->pk }}" {{ $n->active_inactive == 1 ? 'checked' : '' }}
                                        aria-label="Toggle notice status">
                                </div>
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                    <a href="{{ route('admin.notice.edit', $encId) }}"
                                        class="btn btn-sm btn-outline-primary notice-action-btn border-0" title="Edit" aria-label="Edit Notice">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>

                                    @if($n->active_inactive == 0)
                                    <form id="deleteForm{{ $encId }}"
                                        action="{{ route('admin.notice.destroy', $encId) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')

                                        <button type="button" class="btn btn-sm btn-outline-danger notice-action-btn border-0" title="Delete"
                                            aria-label="Delete Notice" onclick="deleteConfirm('{{ $encId }}')">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    @else
                                    <button class="btn btn-sm btn-outline-secondary notice-action-btn border-0" disabled title="Delete Disabled"
                                        aria-label="Delete disabled for active notice">
                                        <i class="bi bi-slash-circle" aria-hidden="true"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-6 d-block mb-2 opacity-50" aria-hidden="true"></i>
                                No notices found. Try adjusting filters or add a new notice.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $notices->firstItem() ?? 0 }}
                    to {{ $notices->lastItem() ?? 0 }}
                    of {{ $notices->total() }} items
                </div>

                <div>
                    {{ $notices->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Choices === 'undefined') {
            return;
        }

        document.querySelectorAll('.js-choice').forEach(function (el) {
            if (el.dataset.choicesInitialized === 'true') {
                return;
            }

            new Choices(el, {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: '',
            });

            el.dataset.choicesInitialized = 'true';
        });
    });
</script>
@endpush
