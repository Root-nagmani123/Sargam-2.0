@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Student Medical Exemption" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Student Medical Exemption</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
<<<<<<< HEAD
<<<<<<< HEAD
                                <a href="{{route('student.medical.exemption.create')}}" class="btn btn-primary">+ Add
                                    Student Medical Exemption</a>
=======
                                <a href="{{route('student.medical.exemption.create')}}" class="btn btn-primary">+ Add Student Medical Exemption</a>
>>>>>>> 234cd48 (Exemption Category and Exemption Medical Speciality)
=======
                                <a href="{{route('student.medical.exemption.create')}}" class="btn btn-primary">+ Add
                                    Student Medical Exemption</a>
>>>>>>> 33db3ab (student-medical-exemption work)
                            </div>
                        </div>
                    </div>
                    <hr>
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> 33db3ab (student-medical-exemption work)
                    <div class="table-responsive">
                        <table id="zero_config" class="table table-bordered table-striped w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student</th>
                                        <th>Category</th>
                                        <th>Medical Speciality</th>
                                        <th>From-To</th>
                                        <th>OPD Type</th>
                                        <th>Status</th>
                                        <th>Action</th>
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
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input status-toggle" type="checkbox"
                                                    role="switch" data-table="student_medical_exemption"
                                                    data-column="active_inactive" data-id="{{ $row->pk }}"
                                                    {{ $row->active_inactive == 1 ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('student.medical.exemption.edit', ['id' => encrypt(value: $row->pk)])  }}"
                                                class="btn btn-sm btn-info">Edit</a>
<<<<<<< HEAD

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

                                    </tr>
                                    @empty

                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

=======
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
=======
>>>>>>> 33db3ab (student-medical-exemption work)

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

                                    </tr>
                                    @empty

                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
<<<<<<< HEAD
>>>>>>> 234cd48 (Exemption Category and Exemption Medical Speciality)
=======

>>>>>>> 33db3ab (student-medical-exemption work)
                </div>
            </div>
        </div>
    </div>
<<<<<<< HEAD
</div>
@endsection
=======
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD
</div>
@endsection
=======
    </div>
    @endsection
>>>>>>> 234cd48 (Exemption Category and Exemption Medical Speciality)
=======
</div>
@endsection
>>>>>>> 33db3ab (student-medical-exemption work)
=======
    </div>
    @endsection
>>>>>>> 234cd48 (Exemption Category and Exemption Medical Speciality)
=======
</div>
@endsection
>>>>>>> 33db3ab (student-medical-exemption work)
=======
    </div>
    @endsection
>>>>>>> 234cd48 (Exemption Category and Exemption Medical Speciality)
<<<<<<< HEAD
>>>>>>> 14c43bc (Exemption Category and Exemption Medical Speciality)
=======
=======
</div>
@endsection
>>>>>>> 33db3ab (student-medical-exemption work)
<<<<<<< HEAD
>>>>>>> 259ed71 (student-medical-exemption work)
=======
=======
</div>
@endsection
>>>>>>> cebb19a (resolve conflict)
<<<<<<< HEAD
>>>>>>> 5dbca3d (resolve conflict)
=======
=======
    </div>
    @endsection
>>>>>>> 234cd48 (Exemption Category and Exemption Medical Speciality)
<<<<<<< HEAD
>>>>>>> c55af26 (Exemption Category and Exemption Medical Speciality)
=======
=======
</div>
@endsection
>>>>>>> 33db3ab (student-medical-exemption work)
<<<<<<< HEAD
>>>>>>> fee5ee9 (student-medical-exemption work)
=======
=======
</div>
@endsection
>>>>>>> cebb19a (resolve conflict)
>>>>>>> 09f1872 (resolve conflict)
