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
                                    Add Course Group Type
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
                                    <th class="col">S.No.</th>
                                    <th class="col">Type Name</th>
                                    <th class="col">Status</th>
                                    <th class="col">Action</th>
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
    <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Row actions">

        <!-- Edit Action -->
        <a
            href="{{ route('master.course.group.type.edit', ['id' => encrypt($courseGroupType->pk)]) }}"
            class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
            aria-label="Edit course group type"
        >
            <i class="material-icons material-symbols-rounded"
               style="font-size:18px;" aria-hidden="true">
                edit
            </i>
            <span class="d-none d-md-inline">Edit</span>
        </a>

        <!-- Delete Action -->
        <form
            action="{{ route('master.course.group.type.delete', ['id' => encrypt($courseGroupType->pk)]) }}"
            method="POST"
            class="d-inline"
        >
            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                aria-label="Delete course group type"
                {{ $courseGroupType->active_inactive == 1 ? 'disabled aria-disabled=true' : '' }}
                onclick="return confirm('Are you sure you want to delete this record?');"
            >
                <i class="material-icons material-symbols-rounded"
                   style="font-size:18px;" aria-hidden="true">
                    delete
                </i>
                <span class="d-none d-md-inline">Delete</span>
            </button>
        </form>

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