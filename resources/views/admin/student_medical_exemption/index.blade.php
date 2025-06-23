@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Student Medical Exemption" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Student Medical Exemption</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('student.medical.exemption.create')}}" class="btn btn-primary">+ Add
                                    Student Medical Exemption</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table id="zero_config" class="table table-bordered table-striped w-100">
                            <thead>
                                <tr>
                                    <th class="col">#</th>
                                    <th class="col">Student</th>
                                    <th class="col">Category</th>
                                    <th class="col">Medical Speciality</th>
                                    <th class="col">From-To</th>
                                    <th class="col">OPD Type</th>
                                    <th class="col">Action</th>
                                    <th class="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row->student->display_name ?? 'N/A' }}</td>
                                    <td>{{ $row->category->exemp_category_name ?? 'N/A' }}</td>
                                    <td>{{ $row->speciality->speciality_name ?? 'N/A' }}</td>
                                    <td>{{ $row->from_date }} to {{ $row->to_date }}</td>
                                    <td>{{ $row->opd_category }}</td>
                                    <td>
                                        <a href="{{ route('student.medical.exemption.edit', ['id' => encrypt(value: $row->pk)])  }}"
                                            class="btn btn-sm btn-info">Edit</a>

                                        <form
                                            title="{{ $row->active_inactive == 1 ? 'Cannot delete active course group type' : 'Delete' }}"
                                            action="{{ route('student.medical.exemption.delete', 
                                                    ['id' => encrypt($row->pk)]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-sm" onclick="event.preventDefault(); 
                                                        if(confirm('Are you sure you want to delete this record?')) {
                                                            this.closest('form').submit();
                                                        }" {{ $row->active_inactive == 1 ? 'disabled' : '' }}>
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="student_medical_exemption" data-column="active_inactive"
                                                data-id="{{ $row->pk }}"
                                                {{ $row->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                </tr>
                                @empty

                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</div>
@endsection