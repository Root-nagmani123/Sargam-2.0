@extends('admin.layouts.master')

@section('title', 'Class Session Master')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Class Session Master" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Class Session Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.class.session.create')}}" class="btn btn-primary">+ Add Class Session</a>
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
                                    <th>Shift Name</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Action</th>
                                    <th>Status</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (!empty($classSessionMaster) && count($classSessionMaster) > 0)
                                    @foreach ($classSessionMaster as $classSession)
                                        <tr class="odd">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $classSession->shift_name ?? 'N/A' }}</td>
                                            <td>{{ $classSession->start_time ?? 'N/A' }}</td>
                                            <td>{{ $classSession->end_time ?? 'N/A' }}</td>
                                            <td>
                                                <a 
                                                    href="{{ route('master.class.session.edit', 
                                                    ['id' => encrypt(value: $classSession->pk)]) }}"
                                                    class="btn btn-primary btn-sm"
                                                >Edit</a>

                                                

                                                <form title="{{ $classSession->active_inactive == 1 ? 'Cannot delete active session' : 'Delete' }}"
                                                    action="{{ route('master.class.session.delete', 
                                                    ['id' => encrypt($classSession->pk)]) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" 
                                                            class="btn btn-danger btn-sm" 
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="top" 
                                                            
                                                            onclick="event.preventDefault(); 
                                                                    if(confirm('Are you sure you want to delete this record?')) {
                                                                        this.closest('form').submit();
                                                                    }"
                                                            {{ $classSession->active_inactive == 1 ? 'disabled' : '' }}>
                                                        Delete
                                                    </button>

                                                </form>
                                            </td>
                                            <td>
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                        data-table="class_session_master" data-column="active_inactive" data-id="{{ $classSession->pk }}" {{ $classSession->active_inactive == 1 ? 'checked' : '' }}>
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