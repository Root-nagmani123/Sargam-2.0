@extends('admin.layouts.master')

@section('title', 'Notice notification List')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@include('admin.NoticeNotification.partials.module-styles')
@endpush

@section('content')
<div class="container-fluid notice-module-page">
    <x-breadcrum title="Notice notification List">
        <a href="{{ route('admin.notice.create') }}"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Notice Notification</span>
        </a>
    </x-breadcrum>

    <div class="card">

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

            <div class="mb-3">
                <form method="GET" action="{{ route('admin.notice.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label mb-1">Category
                            </label>
                            <select name="notice_category_master_pk" class="form-select js-choice" onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->pk }}" {{ request('notice_category_master_pk') == $cat->pk ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label mb-1">Course
                            </label>
                            <select name="course_id" class="form-select js-choice" onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach($courses as $c)
                                <option value="{{ $c->pk }}" {{ request('course_id') == $c->pk ? 'selected' : '' }}>
                                    {{ $c->course_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label mb-1">Status
                            </label>
                            <select name="status" class="form-select js-choice" onchange="this.form.submit()">
                                <option value="">All</option>
                                <option value="1" {{ request('status') == "1" ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('status') == "0" ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <a href="{{ route('admin.notice.index') }}" class="btn btn-outline-secondary w-100 rounded-1">Reset Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" class="ps-3" style="width: 60px;">S.N.</th>
                            <th scope="col">Notice Title</th>
                            <th scope="col">Category</th>
                            <th scope="col" class="d-none d-lg-table-cell">Subcategory</th>
                            <th scope="col" class="d-none d-xl-table-cell">Course Name</th>
                            <th scope="col" class="d-none d-md-table-cell">Created By</th>
                            <th scope="col" class="d-none d-lg-table-cell">Created Date</th>
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
                                <span class="fw-semibold text-body d-inline-block text-truncate" style="max-width: 220px;" title="{{ $n->notice_title }}">
                                    {{ $n->notice_title }}
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle">
                                    {{ $n->noticeCategory->name ?? $n->notice_type ?? '—' }}
                                </span>
                            </td>
                            <td class="d-none d-lg-table-cell text-muted small">{{ $n->noticeSubcategory->name ?? '—' }}</td>
                            <td class="d-none d-xl-table-cell small">{{ $n->course->course_name ?? 'N/A' }}</td>
                            <td class="d-none d-md-table-cell small">{{ $n->user->first_name }} {{ $n->user->last_name }}</td>
                            <td class="d-none d-lg-table-cell small text-muted">{{ \Carbon\Carbon::parse($n->created_date)->format('d-m-Y') }}</td>
                            <td class="small">{{ \Carbon\Carbon::parse($n->display_date)->format('d-m-Y') }}</td>
                            <td class="d-none d-md-table-cell small text-muted">{{ \Carbon\Carbon::parse($n->expiry_date)->format('d-m-Y') }}</td>
                            <td class="text-center">
                                <span class="badge rounded-1 bg-{{ $n->active_inactive == 1 ? 'success' : 'danger' }} text-white fw-semibold">
                                    {{ $n->active_inactive == 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                    
                                    <a href="{{ route('admin.notice.edit', $encId) }}"
                                        class="btn btn-sm btn-outline-primary bg-transparent border-0 p-0" title="Edit" aria-label="Edit Notice">
                                        <i class="material-icons material-symbols-rounded" aria-hidden="true">edit</i>
                                    </a>
                                    <div class="form-check form-switch d-inline-flex align-items-center justify-content-center mb-0">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="notices_notification" data-column="active_inactive"
                                        data-id="{{ $n->pk }}" {{ $n->active_inactive == 1 ? 'checked' : '' }}
                                        aria-label="Toggle notice status">
                                </div>
                                    @if($n->active_inactive == 0)
                                    <form id="deleteForm{{ $encId }}"
                                        action="{{ route('admin.notice.destroy', $encId) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')

                                        <button type="button" class="btn btn-sm btn-outline-danger bg-transparent border-0 p-0" title="Delete"
                                            aria-label="Delete Notice" onclick="deleteConfirm('{{ $encId }}')">
                                            <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
                                        </button>
                                    </form>
                                    @else
                                    <button class="btn btn-sm btn-outline-secondary bg-transparent border-0 p-0" disabled title="Delete Disabled"
                                        aria-label="Delete disabled for active notice">
                                        <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-6 d-block mb-2 opacity-50" aria-hidden="true"></i>
                                <p class="mb-2 fw-semibold">No notices found</p>
                                <p class="small mb-3">Try adjusting filters or add a new notice.</p>
                                <a href="{{ route('admin.notice.create') }}" class="btn btn-notice-save text-white btn-sm rounded-3">
                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add Notice
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
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
