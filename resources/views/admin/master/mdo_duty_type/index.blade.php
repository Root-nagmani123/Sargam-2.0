@extends('admin.layouts.master')

@section('title', 'MDO Duty Type')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="MDO Duty Type" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>MDO Duty Type</h4>
                        </div>
                        @can('master.mdo_duty_type.create')
                            <div class="col-6">
                                <div class="float-end gap-2">
                                    <a href="{{route('master.mdo_duty_type.create')}}" class="btn btn-primary">+ Add MDO Duty Type</a>
                                </div>
                            </div>
                        @endcan
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
                                    <th>Faculty Expertise</th>
                                    <th>Action</th>
                                    <th>Status</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (!empty($mdoDutyTypes) && count($mdoDutyTypes) > 0)
                                    @foreach ($mdoDutyTypes as $mdoDutyType)
                                        <tr class="odd">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $mdoDutyType->mdo_duty_type_name ?? 'N/A' }}</td>
                                            <td>
                                                @can('master.mdo_duty_type.edit')
                                                    <a 
                                                        href="{{ route('master.mdo_duty_type.edit', 
                                                        ['id' => encrypt($mdoDutyType->pk)]) }}"
                                                        class="btn btn-primary btn-sm"
                                                    >Edit</a>
                                                @endcan
                                                @can('master.mdo_duty_type.delete')
                                                    <form title="{{ $mdoDutyType->active_inactive == 1 ? 'Cannot delete active MDO Duty Type' : 'Delete' }}"
                                                        action="{{ route('master.mdo_duty_type.delete', 
                                                        ['id' => encrypt($mdoDutyType->pk)]) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="event.preventDefault(); 
                                                            if(confirm('Are you sure you want to delete this record?')) {
                                                                this.closest('form').submit();
                                                            }"
                                                            {{ $mdoDutyType->active_inactive == 1 ? 'disabled' : '' }}
                                                            >
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endcan
                                                
                                            </td>
                                            <td>
                                                @can('master.mdo_duty_type.active_inactive')
                                                    <div class="form-check form-switch d-inline-block">
                                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                            data-table="mdo_duty_type_master" data-column="active_inactive" data-id="{{ $mdoDutyType->pk }}" {{ $mdoDutyType->active_inactive == 1 ? 'checked' : '' }}>
                                                    </div>
                                                @endcan
                                                
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