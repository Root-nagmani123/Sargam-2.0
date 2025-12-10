@extends('admin.layouts.master')

@section('title', 'Course Group Type')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Course Group Type"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h4 class="mb-0">Course Group Type</h4>
                        </div>

                        <div class="col-6">
                            <div class="d-flex justify-content-end gap-2 align-items-center">

                                <!-- Add Button -->
                                <a href="{{ route('master.course.group.type.create') }}" class="btn btn-primary">
                                    <i class="material-icons menu-icon me-1">add</i> Add Course Group Type
                                </a>


                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th>S.No.</th>
                                    <th>Type Name</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (!empty($courseGroupTypeMaster) && count($courseGroupTypeMaster) > 0)
                                @foreach ($courseGroupTypeMaster as $courseGroupType)
                                <tr class="odd">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $courseGroupType->type_name ?? 'N/A' }}</td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="course_group_type_master" data-column="active_inactive"
                                                data-id="{{ $courseGroupType->pk }}"
                                                {{ $courseGroupType->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <a class="text-dark" href="#" role="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="material-icons menu-icon">more_horiz</i>
                                            </a>

                                            <ul class="dropdown-menu dropdown-menu-end">

                                                <!-- Edit -->
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ route('master.course.group.type.edit', ['id' => encrypt($courseGroupType->pk)]) }}">
                                                        <i class="material-icons material-symbols-rounded me-2"
                                                            style="font-size: 18px;">edit</i>
                                                        Edit
                                                    </a>
                                                </li>

                                                <!-- Delete -->
                                                <li>
                                                    <form
                                                        action="{{ route('master.course.group.type.delete', ['id' => encrypt($courseGroupType->pk)]) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button class="dropdown-item text-danger"
                                                            {{ $courseGroupType->active_inactive == 1 ? 'disabled' : '' }}
                                                            onclick="event.preventDefault();
                            if(this.hasAttribute('disabled')) return;
                            if(confirm('Are you sure you want to delete this record?')) {
                                this.closest('form').submit();
                            }">
                                                            <i class="material-icons material-symbols-rounded me-2"
                                                                style="font-size: 18px;">delete</i>
                                                            Delete
                                                        </button>
                                                    </form>
                                                </li>

                                            </ul>
                                        </div>
                                    </td>

                                </tr>
                                @endforeach
                                @else

                                @endif

                            </tbody>
                        </table>

                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                        <div class="text-muted small mb-2">
                            Showing {{ $courseGroupTypeMaster->firstItem() }}
                            to {{ $courseGroupTypeMaster->lastItem() }}
                            of {{ $courseGroupTypeMaster->total() }} items
                        </div>

                        <div>
                            {{ $courseGroupTypeMaster->links('vendor.pagination.custom') }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>



@endsection