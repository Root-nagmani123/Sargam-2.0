{{-- resources/views/admin/courseAttendanceNoticeMap/memo_notice_index.blade.php --}}

@extends('admin.layouts.master')

@section('title', 'Memo/Notice Templates - Sargam | LBSNAA')

@section('content')
    <div class="container-fluid">
        <x-breadcrum title="Memo/Notice Template Management" />

        <x-session_message />

        <!-- Filter Card -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('admin.memo-notice.index') }}" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter by Course</label>
                        <select name="course_id" class="form-select">
                            <option value="">All Courses</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->pk }}"
                                    {{ request('course_id') == $course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <a href="{{ route('admin.memo-notice.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Memo/Notice Templates</h5>
                <a href="{{ route('admin.memo-notice.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Create New Template
                </a>
            </div>
            <div class="card-body">
                @if ($templates->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No templates found. Create your first template!
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Course</th>
                                    <th>Director</th>
                                    <th>Designation</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($templates as $template)
                                    <tr>
                                        <td>{{ $loop->iteration + ($templates->currentPage() - 1) * $templates->perPage() }}
                                        </td>
                                        <td>
                                            @if ($template->course)
                                                <span class="badge bg-info">{{ $template->course->course_name }}</span>
                                            @else
                                                <span class="text-muted">General</span>
                                            @endif
                                        </td>
                                        <td>{{ $template->director_name }}</td>
                                        <td>{{ $template->director_designation }}</td>

                                        <td>{{ $template->created_at->format('d M Y') }}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.memo-notice.edit', $template->id) }}"
                                                    class="btn btn-sm btn-primary py-0 px-2">
                                                    Edit
                                                </a>
                                                <form action="{{ route('admin.memo-notice.destroy', $template->id) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this template?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger py-0 px-2">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $templates->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .table th {
            background-color: #f8f9fa;
        }

        .badge {
            font-size: 0.8em;
        }

        .btn-group .btn {
            margin-right: 2px;
        }
    </style>
@endpush
