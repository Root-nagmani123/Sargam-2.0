@extends('admin.layouts.master')

@section('title', 'Student - Course Mapping')

@section('content')
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row">
                <div class="col-6">
                    <h4>Student - Course Mapping</h4>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="mb-3">
                    <strong>Total Students:</strong> {{ $totalStudents }} <br>
                    <strong>Showing:</strong> {{ $filteredCount }}
                </div>

                <div class="mb-3 d-flex justify-content-left">
                    <form method="GET" action="{{ route('student.courses') }}" id="courseFilterForm">
                        <div class="form-group">
                            <label for="course_id">Filter by Course</label>
                            <select name="course_id" id="course_id" class="form-control"
                                onchange="document.getElementById('courseFilterForm').submit()">
                                <option value="">-- All Courses --</option>
                                @foreach ($courses as $id => $name)
                                    <option value="{{ $id }}" {{ $courseId == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th>Modified Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $serial = 1; @endphp
                                @foreach ($students as $student)
                                    @foreach ($student->courses as $course)
                                        <tr>
                                            <td>{{ $serial++ }}</td>
                                            <td>{{ $student->first_name }} {{ $student->middle_name }}
                                                {{ $student->last_name }}</td>
                                            <td>{{ $course->course_name }}</td>
                                            <td>{{ $student->service->service_name ?? 'N/A' }}</td>
                                            <td>{{ $course->pivot->active_inactive == 1 ? 'Active' : 'Inactive' }}</td>
                                            <td>{{ $course->pivot->created_date }}</td>
                                            <td>{{ $course->pivot->modified_date }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Laravel pagination --}}
                <div>
                    {{-- {{ $students->links() }} --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#zero_config')) {
                $('#zero_config').DataTable().destroy();
            }

            $('#zero_config').DataTable({
                "pageLength": 10,
                "ordering": true,
                "searching": true,
                "lengthChange": true
            });
        });
    </script>
@endsection
