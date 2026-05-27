@extends('admin.layouts.master')

@section('title', 'Notice notification List')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Notice notification List"></x-breadcrum>

    <div class="card">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary fw-semibold text-uppercase">Notices</span>
                <h4 class="card-title mb-0">Notice notification List</h4>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.notice.create') }}" class="btn btn-primary">
                    <span class="material-symbols-rounded align-middle me-1">add</span>
                    <span class="align-middle">Add Notice Notification</span>
                </a>
            </div>
        </div>

        <div class="card-body">
            @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <strong>There were some problems with your request.</strong>
                <ul class="mb-0 mt-2 ps-3">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <div id="status-msg" class="mb-3"></div>

            <div class="bg-light rounded-3 p-3 mb-4">
                <form method="GET" action="{{ route('admin.notice.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Notice Type</label>
                            <select name="notice_type" class="form-select form-select-sm js-choice" onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach($types as $type)
                                <option value="{{ $type }}" {{ request('notice_type') == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Course</label>
                            <select name="course_id" class="form-select form-select-sm js-choice" onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach($courses as $c)
                                <option value="{{ $c->id }}" {{ request('course_id') == $c->pk ? 'selected' : '' }}>
                                    {{ $c->course_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select form-select-sm js-choice" onchange="this.form.submit()">
                                <option value="">All</option>
                                <option value="1" {{ request('status') == "1" ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('status') == "0" ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex gap-2">
                            <div class="flex-grow-1">
                                <label class="form-label fw-semibold d-block">&nbsp;</label>
                                <a href="{{ route('admin.notice.index') }}" class="btn btn-outline-secondary w-100">
                                    Reset Filters
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle text-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 60px;">S.N.</th>
                            <th scope="col">Notice Title</th>
                            <th scope="col">Notice Type</th>
                            <th scope="col">Course Name</th>
                            <th scope="col">Created By</th>
                            <th scope="col">Created Date</th>
                            <th scope="col">Display Date</th>
                            <th scope="col">Expiry Date</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($notices as $index => $n)
                        @php $encId = Crypt::encrypt($n->pk); @endphp

                        <tr>
                            <td class="fw-semibold">{{ $index + $notices->firstItem() }}</td>
                            <td class="fw-semibold text-truncate" style="max-width: 260px;">
                                {{ $n->notice_title }}
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-info-subtle text-info text-capitalize">
                                    {{ $n->notice_type }}
                                </span>
                            </td>
                            <td>{{ $n->course->course_name ?? 'N/A' }}</td>
                            <td>{{ $n->user->first_name }} {{ $n->user->last_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($n->created_date)->format('d-m-Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($n->display_date)->format('d-m-Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($n->expiry_date)->format('d-m-Y') }}</td>

                            <td class="text-center">
                                <div class="form-check form-switch d-inline-flex align-items-center justify-content-center">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="notices_notification" data-column="active_inactive"
                                        data-id="{{ $n->pk }}" {{ $n->active_inactive == 1 ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-center d-flex justify-content-center">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('admin.notice.edit', $encId) }}"
                                        class="btn btn-sm btn-outline-primary btn-transparent border-0 p-0" title="Edit" aria-label="Edit Notice">
                                        <span class="material-symbols-rounded fs-5">edit</span>
                                    </a>

                                    @if($n->active_inactive == 0)
                                    <form id="deleteForm{{ $encId }}"
                                        action="{{ route('admin.notice.destroy', $encId) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')

                                        <button type="button" class="btn btn-sm btn-outline-danger btn-transparent border-0 p-0" title="Delete"
                                            aria-label="Delete Notice" onclick="deleteConfirm('{{ $encId }}')">
                                            <span class="material-symbols-rounded fs-5">delete</span>
                                        </button>
                                    </form>
                                    @else
                                    <button class="btn btn-sm btn-outline-secondary btn-transparent border-0 p-0" disabled title="Delete Disabled">
                                        <span class="material-symbols-rounded fs-5">block</span>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $notices->firstItem() ?? 0 }}
                    to {{ $notices->lastItem() }}
                    of {{ $notices->total() }} items
                </div>

                <div>
                    {{ $notices->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
</div>

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

@endsection