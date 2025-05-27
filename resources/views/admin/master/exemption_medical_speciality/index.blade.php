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
                                <a href="{{route('master.exemption.medical.speciality.create')}}" class="btn btn-primary">+ Add Speciality</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <table class="table table-bordered">
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
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection