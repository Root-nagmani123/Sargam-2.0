@extends('admin.layouts.master')

@section('title', 'Course Group Type')

@section('content')
<div class="container-fluid">

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
                                    + Add Course Group Type
                                </a>


                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap align-middle datatables">
                            <thead style="background-color: #af2910; color: #ffffff;">
                                <!-- start row -->
                                <tr>
                                    <th>S.No.</th>
                                    <th>Type Name</th>
                                    <th>Action</th>
                                    <th>Status</th>
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
                                        <a href="{{ route('master.course.group.type.edit', 
                                                    ['id' => encrypt(value: $courseGroupType->pk)]) }}"><i
                                                class="material-icons menu-icon material-symbols-rounded"
                                                style="font-size: 24px;">edit</i></a>
                                        <form
                                            title="{{ $courseGroupType->active_inactive == 1 ? 'Cannot delete active course group type' : 'Delete' }}"
                                            action="{{ route('master.course.group.type.delete', 
                                                    ['id' => encrypt($courseGroupType->pk)]) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <a href="javascript:void(0)" onclick="event.preventDefault(); 
                                                        if(confirm('Are you sure you want to delete this record?')) {
                                                            this.closest('form').submit();
                                                        }"
                                                {{ $courseGroupType->active_inactive == 1 ? 'disabled' : '' }}>
                                                <i class="material-icons menu-icon material-symbols-rounded"
                                                    style="font-size: 24px;">delete</i>
                                            </a>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="course_group_type_master" data-column="active_inactive"
                                                data-id="{{ $courseGroupType->pk }}"
                                                {{ $courseGroupType->active_inactive == 1 ? 'checked' : '' }}>
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