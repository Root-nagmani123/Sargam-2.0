@extends('admin.layouts.master')

@section('title', 'Course Group Type')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Course Group Type" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Course Group Type</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.course.group.type.create')}}" class="btn btn-primary">+ Add Course Group Type</a>
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
                                    <th>Type Name</th>
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
                                                <a 
                                                    href="{{ route('master.course.group.type.edit', 
                                                    ['id' => encrypt(value: $courseGroupType->pk)]) }}"
                                                    class="btn btn-primary btn-sm"
                                                >Edit</a>
                                                <form 
                                                    action="{{ route('master.course.group.type.delete', 
                                                    ['id' => encrypt($courseGroupType->pk)]) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="event.preventDefault(); 
                                                        if(confirm('Are you sure you want to delete this record?')) {
                                                            this.closest('form').submit();
                                                        }">
                                                        Delete
                                                    </button>
                                                </form>
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                        data-table="course_group_type_master" data-column="active_inactive" data-id="{{ $courseGroupType->pk }}" {{ $courseGroupType->active_inactive == 1 ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    
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