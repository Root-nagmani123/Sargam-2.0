@extends('admin.layouts.master')

@section('title', 'Notice notification List')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Notice notification List"></x-breadcrum>
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Notice notification List</h4>
                </div>
                <div class="col-6">
                    <div class="float-end gap-2">
                        <a href="{{ route('admin.notice.create') }}" class="btn btn-primary">+ Add Notice
                            Notification</a>
                    </div>
                </div>
            </div>
            <hr class="my-2">

            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            @endif

            <div id="status-msg"></div>

            <form method="GET" action="{{ route('admin.notice.index') }}" class="mb-3 mt-4">

                <div class="row">

                    <div class="col-md-3">
                        <label class="form-label">Notice Type</label>
                        <select name="notice_type" class="form-control" onchange="this.form.submit()">
                            <option value="">All</option>
                            @foreach($types as $type)
                            <option value="{{ $type }}" {{ request('notice_type') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Course</label>
                        <select name="course_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All</option>
                            @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ request('course_id') == $c->pk ? 'selected' : '' }}>
                                {{ $c->course_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="1" {{ request('status') == "1" ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('status') == "0" ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <a href="{{ route('admin.notice.index') }}" class="btn btn-secondary form-control">
                            Reset
                        </a>
                    </div>

                </div>

            </form>
            <div class="table-responsive">
                <table class="table text-nowrap">
                    <thead>
                        <tr>
                            <th>S.N.</th>
                            <th>Notice Title</th>
                            <th>Notice Type</th>
                            <th>Course Name</th>
                            <th>Created By</th>
                            <th>Created Date</th>
                            <th>Display Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($notices as $index => $n)
                        @php $encId = Crypt::encrypt($n->pk); @endphp

                        <tr>
                            <td>{{ $index + $notices->firstItem() }}</td>
                            <td>{{ $n->notice_title }}</td>
                            <td>{{ $n->notice_type }}</td>
                            <td>{{ $n->course->course_name ?? 'N/A' }}</td>
                            <td>{{ $n->user->first_name }} {{ $n->user->last_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($n->created_date)->format('d-m-Y') }}</td>

                            <td>{{ \Carbon\Carbon::parse($n->display_date)->format('d-m-Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($n->expiry_date)->format('d-m-Y') }}</td>

                            <td>
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="notices_notification" data-column="active_inactive"
                                        data-id="{{ $n->pk }}" {{ $n->active_inactive == 1 ? 'checked' : '' }}>
                                </div>

                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <a href="#" id="actionDropdown{{ $encId }}" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                                        <i class="material-icons material-symbols-rounded"
                                            style="font-size:24px;cursor:pointer;">
                                            more_horiz
                                        </i>
                                    </a>

                                    <!-- Delete -->
                                    @if($n->active_inactive == 0)
                                    <form id="deleteForm{{ $encId }}"
                                        action="{{ route('admin.notice.destroy', $encId) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')

                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                            aria-label="Delete Notice" onclick="deleteConfirm('{{ $encId }}')">
                                            <span class="material-symbols-rounded fs-5">delete</span>
                                        </button>
                                    </form>
                                    @else
                                    <button class="btn btn-sm btn-outline-secondary" disabled title="Delete Disabled">
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

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                <div class="text-muted small mb-2">
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

@endsection