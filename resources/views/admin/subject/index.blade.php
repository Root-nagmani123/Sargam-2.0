@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid subject-index">
    <x-breadcrum title="Subject"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h4 class="fw-semibold text-dark mb-0">Subject</h4>
                <div class="d-flex align-items-center gap-2">
                    <!-- Add New Button -->
                    <a href="{{ route('subject.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                        <i class="material-icons menu-icon material-symbols-rounded"
                            style="font-size: 20px; vertical-align: middle;">add</i>
                        Add Subject
                    </a>
                </div>
            </div>
            <hr>

            <!-- Success/Error Messages -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="material-icons material-symbols-rounded me-2"
                    style="font-size: 20px; vertical-align: middle;">check_circle</i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="material-icons material-symbols-rounded me-2"
                    style="font-size: 20px; vertical-align: middle;">error</i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <div id="zero_config_table">

                <div class="table-responsive">
                    <table class="table text-nowrap mb-0 " id="zero_config">
                        <thead>
                            <tr>
                                <th>S.No.</th>
                                <th>Major Subject Name</th>
                                <th>Short Name</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subjects as $key => $subject)
                            <tr>
                                <td>{{ $subjects->firstItem() + $key }}</td>
                                <td>{{ $subject->subject_name }}</td>
                                <td>{{ $subject->sub_short_name }}</td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="subject_master" data-column="active_inactive"
                                            data-id="{{ $subject->pk }}"
                                            {{ $subject->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-2" role="group"
                                        aria-label="Subject actions">

                                        <!-- Edit -->
                                        <a href="{{ route('subject.edit', $subject->pk) }}"
                                            class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                            aria-label="Edit subject">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;"
                                                aria-hidden="true">edit</i>
                                            <span class="d-none d-md-inline">Edit</span>
                                        </a>

                                        <!-- Delete -->
                                        @if ($subject->active_inactive == 1)
                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                            disabled aria-disabled="true" title="Cannot delete active subject">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;"
                                                aria-hidden="true">delete</i>
                                            <span class="d-none d-md-inline">Delete</span>
                                        </button>
                                        @else
                                        <form action="{{ route('subject.destroy', $subject->pk) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                                aria-label="Delete subject">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size:18px;" aria-hidden="true">delete</i>
                                                <span class="d-none d-md-inline">Delete</span>
                                            </button>
                                        </form>
                                        @endif

                                    </div>

                                </td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <p class="text-muted mb-0">No subjects found.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
@endsection