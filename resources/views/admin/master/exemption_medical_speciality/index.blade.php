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
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.exemption.medical.speciality.create')}}"
                                    class="btn btn-primary">+ Add Speciality</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                       <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Speciality Name</th>

                                    <th>Created Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($specialities as $index => $speciality)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $speciality->speciality_name }}</td>

                                    <td>{{ $speciality->created_date }}</td>

                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="exemption_medical_speciality_master"
                                                data-column="active_inactive" data-id="{{ $speciality->pk }}"
                                                {{ $speciality->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>

                                    <td>
                                        <a href="{{ route('master.exemption.medical.speciality.edit', 
                                                    ['id' => encrypt(value: $speciality->pk)]) }}"
                                            class="btn btn-primary btn-sm">Edit</a>
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
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No specialities found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection