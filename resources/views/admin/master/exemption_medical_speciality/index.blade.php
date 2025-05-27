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
<<<<<<< HEAD
                                <a href="{{route('master.exemption.medical.speciality.create')}}"
                                    class="btn btn-primary">+ Add Speciality</a>
=======
                                <a href="{{route('master.exemption.medical.speciality.create')}}" class="btn btn-primary">+ Add Speciality</a>
>>>>>>> 0df3a97 (medical exemption master work)
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <table class="table table-bordered">
<<<<<<< HEAD
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
=======
        <thead>
            <tr>
                <th>#</th>
                <th>Speciality Name</th>
                <th>Status</th>
                <th>Created Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($specialities as $index => $speciality)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $speciality->speciality_name }}</td>
                    <td>
                        @if($speciality->active_inactive == 1)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $speciality->created_date }}</td>
                    <td>
                        <a href="{{ route('master.exemption.medical.speciality.edit', encrypt($speciality->pk)) }}" class="btn btn-sm btn-info">Edit</a>
                        <form action="{{ route('master.exemption.medical.speciality.delete', encrypt($speciality->pk)) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure to delete?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No specialities found.</td></tr>
            @endforelse
        </tbody>
>>>>>>> 0df3a97 (medical exemption master work)
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection