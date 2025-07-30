@extends('admin.layouts.master')

@section('title', 'Exemption Medical Speciality Master')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Exemption Medical Speciality Master" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Exemption Medical Speciality Master</h4>
                        </div>
                        @can('master.exemption.medical.speciality.index')
                            <div class="col-6">
                                <div class="float-end gap-2">
                                    <a href="{{route('master.exemption.medical.speciality.create')}}"
                                        class="btn btn-primary">+ Add Speciality</a>
                                </div>
                            </div>    
                        @endcan
                        
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">

                        <div class="table-responsive">
                            <table class="table table-bordered" id="zero_config" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th class="col">#</th>
                                        <th class="col">Speciality Name</th>
                                        <th class="col">Created Date</th>
                                        <th class="col">Status</th>
                                        <th class="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($specialities as $index => $speciality)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $speciality->speciality_name }}</td>
                                        <td>{{ $speciality->created_date }}</td>

                                        <td>
                                            @can('master.exemption.medical.speciality.active_inactive')
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input status-toggle" type="checkbox"
                                                        role="switch" data-table="exemption_medical_speciality_master"
                                                        data-column="active_inactive" data-id="{{ $speciality->pk }}"
                                                        {{ $speciality->active_inactive == 1 ? 'checked' : '' }}>
                                                </div>
                                            @endcan
                                            
                                        </td>

                                        <td>
                                            @can('master.exemption.medical.speciality.edit')
                                                <a href="{{ route('master.exemption.medical.speciality.edit', 
                                                    ['id' => encrypt(value: $speciality->pk)]) }}"
                                                class="btn btn-primary btn-sm">Edit</a>
                                            @endcan
                                            @can('master.exemption.medical.speciality.delete')
                                                <form
                                                    title="{{ $speciality->active_inactive == 1 ? 'Cannot delete active course group type' : 'Delete' }}"
                                                    action="{{ route('master.exemption.medical.speciality.delete', 
                                                        ['id' => encrypt($speciality->pk)]) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="event.preventDefault(); 
                                                            if(confirm('Are you sure you want to delete this record?')) {
                                                                this.closest('form').submit();
                                                            }" {{ $speciality->active_inactive == 1 ? 'disabled' : '' }}>
                                                        Delete
                                                    </button>
                                                </form>
                                            @endcan
                                            
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No specialities found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection