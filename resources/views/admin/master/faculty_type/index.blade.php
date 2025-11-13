@extends('admin.layouts.master')

@section('title', 'Faculty Type')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Faculty Type" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Faculty Type</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.faculty.type.master.create')}}" class="btn btn-primary">+ Add Faculty Type</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th>S.No.</th>
                                    <th>Full Name</th>
                                    <th>Short Name</th>
                                    <th>Action</th>
                                    <th>Status</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (!empty($facultyTypes) && count($facultyTypes) > 0)
                                    @foreach ($facultyTypes as $facultyType)
                                        <tr class="odd">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $facultyType->faculty_type_name ?? 'N/A' }}</td>
                                            <td>{{ $facultyType->shot_faculty_type_name ?? 'N/A' }}</td>
                                            <td>
                                                <a 
                                                    href="{{ route('master.faculty.type.master.edit', 
                                                    ['id' => encrypt($facultyType->pk)]) }}"
                                                    class="btn btn-primary btn-sm"
                                                >Edit</a>
                                                <form title="{{ $facultyType->active_inactive == 1 ? 'Cannot delete active Faculty Type' : 'Delete' }}"
                                                    action="{{ route('master.faculty.type.master.delete', 
                                                    ['id' => encrypt($facultyType->pk)]) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="event.preventDefault(); 
                                                        if(confirm('Are you sure you want to delete this record?')) {
                                                            this.closest('form').submit();
                                                        }"
                                                        {{ $facultyType->active_inactive == 1 ? 'disabled' : '' }}
                                                        >
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                        data-table="faculty_type_master" data-column="active_inactive" data-id="{{ $facultyType->pk }}" {{ $facultyType->active_inactive == 1 ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                        
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection