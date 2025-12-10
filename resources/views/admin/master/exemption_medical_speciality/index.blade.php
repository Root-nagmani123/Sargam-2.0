@extends('admin.layouts.master')

@section('title', 'Exemption Medical Speciality Master')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Exemption Medical Speciality Master"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
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
                    <div class="table-responsive">

                        <div class="table-responsive">
                            <table class="table">
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
                                        <td>{{ $specialities->firstItem() + $index }}</td>
                                        <td>{{ $speciality->speciality_name }}</td>
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
                                            <div class="dropdown">
                                                <a
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="material-icons menu-icon">more_horiz</i>
                                                </a>

                                                <ul class="dropdown-menu dropdown-menu-end">

                                                    <!-- Edit -->
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('master.exemption.medical.speciality.edit', ['id' => encrypt($speciality->pk)]) }}">
                                                            <i class="material-icons material-symbols-rounded me-2"
                                                                style="font-size: 18px;">edit</i>
                                                            Edit
                                                        </a>
                                                    </li>

                                                    <!-- Delete -->
                                                    <li>
                                                        @if($speciality->active_inactive == 1)
                                                        <button class="dropdown-item text-muted" disabled
                                                            title="Cannot delete active record">
                                                            <i class="material-icons material-symbols-rounded me-2"
                                                                style="font-size: 18px;">delete</i>
                                                            Delete (Disabled)
                                                        </button>
                                                        @else
                                                        <form action="{{ route('master.exemption.medical.speciality.delete', 
                                        ['id' => encrypt($speciality->pk)]) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')

                                                            <button class="dropdown-item text-danger" onclick="event.preventDefault();
                                if(confirm('Are you sure you want to delete this record?')) {
                                    this.closest('form').submit();
                                }">
                                                                <i class="material-icons material-symbols-rounded me-2"
                                                                    style="font-size: 18px;">delete</i>
                                                                Delete
                                                            </button>
                                                        </form>
                                                        @endif
                                                    </li>

                                                </ul>
                                            </div>
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

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                Showing {{ $specialities->firstItem() ?? 0 }} to {{ $specialities->lastItem() ?? 0 }} of
                                {{ $specialities->total() }} entries
                            </div>
                            <div>
                                {{ $specialities->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection