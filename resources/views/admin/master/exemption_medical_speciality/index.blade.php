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
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> ed5bd5b (Exemption Category and Exemption Medical Speciality)
=======
>>>>>>> c55af26 (Exemption Category and Exemption Medical Speciality)
=======
>>>>>>> fee5ee9 (student-medical-exemption work)
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
=======
                       <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
<<<<<<< HEAD
<<<<<<< HEAD
=======
                        <table class="table table-bordered">
>>>>>>> 14c43bc (Exemption Category and Exemption Medical Speciality)
=======
                       <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
>>>>>>> 259ed71 (student-medical-exemption work)
=======
>>>>>>> ed5bd5b (Exemption Category and Exemption Medical Speciality)
=======
=======
                        <table class="table table-bordered">
>>>>>>> 234cd48 (Exemption Category and Exemption Medical Speciality)
<<<<<<< HEAD
>>>>>>> c55af26 (Exemption Category and Exemption Medical Speciality)
=======
=======
                       <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
>>>>>>> 33db3ab (student-medical-exemption work)
>>>>>>> fee5ee9 (student-medical-exemption work)
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Speciality Name</th>
<<<<<<< HEAD
<<<<<<< HEAD
>>>>>>> 48a903a (Exemption Category and Exemption Medical Speciality)
=======
=======
>>>>>>> c55af26 (Exemption Category and Exemption Medical Speciality)
>>>>>>> 3277777 (Exemption Category and Exemption Medical Speciality)
>>>>>>> ed5bd5b (Exemption Category and Exemption Medical Speciality)

                                        <td>{{ $speciality->created_date }}</td>

                                        <td>
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input status-toggle" type="checkbox"
                                                    role="switch" data-table="exemption_medical_speciality_master"
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
<<<<<<< HEAD
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
                            </table>
                        </div>
=======
=======

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
>>>>>>> 234cd48 (Exemption Category and Exemption Medical Speciality)
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
<<<<<<< HEAD
<<<<<<< HEAD
>>>>>>> 48a903a (Exemption Category and Exemption Medical Speciality)
=======
>>>>>>> 3277777 (Exemption Category and Exemption Medical Speciality)
>>>>>>> ed5bd5b (Exemption Category and Exemption Medical Speciality)
=======
>>>>>>> 3277777 (Exemption Category and Exemption Medical Speciality)
=======
>>>>>>> 234cd48 (Exemption Category and Exemption Medical Speciality)
>>>>>>> c55af26 (Exemption Category and Exemption Medical Speciality)
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection