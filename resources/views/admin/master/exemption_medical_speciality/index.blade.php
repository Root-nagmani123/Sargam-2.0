@extends('admin.layouts.master')

@section('title', 'Exemption Medical Speciality Master')

@section('setup_content')
<div class="container-fluid">

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
                            <table class="table table-bordered" style="width: 100%;">
                                <thead style="background-color: #af2910;">
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
                                            <a href="{{ route('master.exemption.medical.speciality.edit', 
                                                    ['id' => encrypt(value: $speciality->pk)]) }}"><i
                                                class="material-icons menu-icon material-symbols-rounded"
                                                style="font-size: 24px;">edit</i></a>
                                            <form
                                                title="{{ $speciality->active_inactive == 1 ? 'Cannot delete active course group type' : 'Delete' }}"
                                                action="{{ route('master.exemption.medical.speciality.delete', 
                                                    ['id' => encrypt($speciality->pk)]) }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <a href="javascript:void(0)" onclick="event.preventDefault(); 
                                                        if(confirm('Are you sure you want to delete this record?')) {
                                                            this.closest('form').submit();
                                                        }" {{ $speciality->active_inactive == 1 ? 'disabled' : '' }}>
                                                    <i
                                                class="material-icons menu-icon material-symbols-rounded"
                                                style="font-size: 24px;">delete</i>
                                                </a>
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

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                Showing {{ $specialities->firstItem() ?? 0 }} to {{ $specialities->lastItem() ?? 0 }} of {{ $specialities->total() }} entries
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