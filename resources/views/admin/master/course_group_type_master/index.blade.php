@extends('admin.layouts.master')

@section('title', 'Course Group Type')

@section('content')
<style>
    /* Table row hover enhancement */
.table-hover tbody tr:hover {
    background-color: #f3f6fa !important;
}

/* Small focus outlines for accessibility */
.btn:focus, .form-check-input:focus {
    outline: 3px solid #004a93 !important;
    outline-offset: 2px;
}

/* Switch size improvement for accessibility */
.form-check-input {
    width: 2.5em;
    height: 1.3em;
    cursor: pointer;
}

</style>
<div class="container-fluid">

    <x-breadcrum title="Course Group Type" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card shadow-sm rounded-4" style="border-left: 4px solid #004a93;">
    <div class="card-body p-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <h4 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                <i class="bi bi-diagram-3 fs-5 text-primary"></i>
                Course Group Type
            </h4>

            <a href="{{ route('master.course.group.type.create') }}"
               class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-lg"></i>
                Add Course Group Type
            </a>
        </div>

        <hr>

        <!-- Table -->
        <div class="table-responsive">
            <table id="zero_config"
                class="table table-hover table-bordered align-middle"
                aria-label="Course Group Type Table">

                <thead class="table-light">
                    <tr>
                        <th width="8%">S.No.</th>
                        <th>Type Name</th>
                        <th width="25%">Action</th>
                        <th width="12%">Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($courseGroupTypeMaster as $courseGroupType)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <!-- Type -->
                            <td class="fw-semibold">
                                {{ $courseGroupType->type_name ?? 'N/A' }}
                            </td>

                            <!-- Action Buttons -->
                            <td>
                                <div class="d-flex gap-2">

                                    <!-- Edit Button -->
                                    <a href="{{ route('master.course.group.type.edit', ['id' => encrypt($courseGroupType->pk)]) }}"
                                        class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1"
                                        aria-label="Edit Course Group Type">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>

                                    <!-- Delete Button -->
                                    <form action="{{ route('master.course.group.type.delete', ['id' => encrypt($courseGroupType->pk)]) }}"
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')

                                        <button type="button"
                                            class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1"
                                            aria-label="Delete Course Group Type"
                                            onclick="event.preventDefault(); 
                                            if(confirm('Are you sure you want to delete this record?')) {
                                                this.closest('form').submit();
                                            }"
                                            {{ $courseGroupType->active_inactive == 1 ? 'disabled' : '' }}>
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>

                                </div>
                            </td>

                            <!-- Status Toggle -->
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input status-toggle"
                                           type="checkbox"
                                           role="switch"
                                           aria-label="Toggle status"
                                           data-table="course_group_type_master"
                                           data-column="active_inactive"
                                           data-id="{{ $courseGroupType->pk }}"
                                           {{ $courseGroupType->active_inactive == 1 ? 'checked' : '' }}>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-3 text-muted">
                                No records found.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

    </div>
</div>

        <!-- end Zero Configuration -->
    </div>
</div>


@endsection