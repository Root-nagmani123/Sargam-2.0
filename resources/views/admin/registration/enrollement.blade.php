@extends('admin.layouts.master')

@section('title', 'Enrollment - Sargam | Lal Bahadur')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card" style="border-left: 4px solid #004a93;">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-6">
                                <h4>Enroll to New Course</h4>
                            </div>
                        </div>
                        <hr>

                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('enrollment.store') }}" id="enrollmentForm">
                            @csrf
                            <input type="hidden" name="selected_students" id="selectedStudents" value="">

                            <div class="row">
                                <!-- New Course Column -->
                                <div class="col-md-4">
                                    <label for="course_master_pk" class="form-label">Select New Course:</label>
                                    <div class="mb-3">
                                        <select id="course_master_pk"
                                            class="form-select select2 @error('course_master_pk') is-invalid @enderror"
                                            name="course_master_pk" required>
                                            <option value="">Select Course</option>
                                            @if (isset($courses) && count($courses) > 0)
                                                @foreach ($courses as $course)
                                                    <option value="{{ $course->pk }}"
                                                        {{ old('course_master_pk') == $course->pk ? 'selected' : '' }}>
                                                        {{ $course->course_name }}
                                                        @if ($course->couse_short_name)
                                                            ({{ $course->couse_short_name }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="">No courses available</option>
                                            @endif
                                        </select>

                                        @error('course_master_pk')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Previous Courses Column -->
                                <div class="col-md-4">
                                    <label for="previous_courses" class="form-label">Select Previous Courses:</label>
                                    <div class="mb-3">
                                        <select id="previous_courses"
                                            class="form-select select2 @error('previous_courses') is-invalid @enderror"
                                            name="previous_courses[]" multiple>
                                            @if (isset($previousCourses) && count($previousCourses) > 0)
                                                @foreach ($previousCourses as $previousCourse)
                                                    @if ($previousCourse->course)
                                                        <option value="{{ $previousCourse->course->pk }}"
                                                            {{-- @php @dd( $previousCourse->course->pk);die; @endphp --}}
                                                            {{ in_array($previousCourse->pk, old('previous_courses', [])) ? 'selected' : '' }}>
                                                            {{ $previousCourse->course->course_name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </select>
                                        <small class="form-text text-muted">Select multiple courses</small>

                                        @error('previous_courses')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Services Column -->
                                <div class="col-md-4">
                                    <label for="services" class="form-label">Select Service:</label>
                                    <div class="mb-3">
                                        <select id="services"
                                            class="form-select select2 @error('services') is-invalid @enderror"
                                            name="services[]" multiple>
                                            @if (isset($services) && count($services) > 0)
                                                @foreach ($services as $service)
                                                    <option value="{{ $service->pk }}"
                                                        {{ in_array($service->pk, old('services', [])) ? 'selected' : '' }}>
                                                        {{ $service->service_name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <small class="form-text text-muted">Select multiple services</small>

                                        @error('services')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Student Data Table Section -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                Student List (<span id="studentCount">0</span>)
                                            </h5>
                                            <div class="d-flex">
                                                <input type="text" id="studentSearch"
                                                    class="form-control form-control-sm me-2"
                                                    placeholder="Search students..." style="width: 200px;">
                                                <button type="button" id="filterBtn" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-filter"></i> Filter
                                                </button>

                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-hover" id="studentTable">
                                                    <thead>
                                                        <tr>
                                                            <th width="50px">
                                                                <input type="checkbox" id="selectAll" checked>
                                                            </th>
                                                             <th>Student PK</th> 
                                                             <th>Course PK</th>
                                                             <th>Course Name</th>
                                                            <th>Student Name</th>
                                                            <th>OT Code</th>
                                                            <th>Service</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr id="noDataRow">
                                                            <td colspan="5" class="text-center">Select previous courses
                                                                and services to load students</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <div class="mt-3 d-flex justify-content-center">
                                                    <ul class="pagination" id="paginationContainer"></ul>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    Enroll
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function updateSelectedStudents() {
            var selectedStudents = [];
            $('.student-checkbox:checked').each(function() {
                selectedStudents.push($(this).val());
            });
            $('#selectedStudents').val(selectedStudents.join(','));

            // Also update selected count if you want
            $('#studentCount').text($('.student-checkbox').length); // total students
            $('#selectedCount').text($('.student-checkbox:checked').length); // selected students
        }
        $(document).ready(function() {
            // Select all checkbox functionality
            $('#selectAll').change(function() {
                $('.student-checkbox').prop('checked', $(this).prop('checked'));
                updateSelectedStudents();
            });

            // Update selected students when individual checkboxes change
            $(document).on('change', '.student-checkbox', function() {
                if (!$(this).prop('checked')) {
                    $('#selectAll').prop('checked', false);
                } else {
                    // Check if all checkboxes are checked
                    if ($('.student-checkbox:checked').length === $('.student-checkbox').length) {
                        $('#selectAll').prop('checked', true);
                    }
                }
                updateSelectedStudents();
            });

            // Search functionality
            $('#studentSearch').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#studentTable tbody tr').filter(function() {
                    // Skip the no data row
                    if ($(this).attr('id') === 'noDataRow') {
                        return true; // Always show the no data row
                    }
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });


            //     // Function to update the hidden field with selected student IDs
            function updateSelectedStudents() {
                var selectedStudents = [];
                $('.student-checkbox:checked').each(function() {
                    selectedStudents.push($(this).val());
                });
                $('#selectedStudents').val(selectedStudents.join(','));
            }

            // Update form submission to include selected students
            $('#enrollmentForm').on('submit', function() {
                updateSelectedStudents();
            });
        });


        // Filter button functionality
        $('#filterBtn').click(function() {
            loadStudents();
        });

        // Function to load students based on filters
        function loadStudents() {
            var previousCourses = $('#previous_courses').val();
            var services = $('#services').val();

            if (!previousCourses || previousCourses.length === 0) {
                alert('Please select at least one previous course');
                return;
            }

            // Show loading indicator
            $('#studentTable tbody').html('<tr><td colspan="5" class="text-center">Loading students...</td></tr>');

            $.ajax({
                url: '{{ route('enrollment.filterStudents') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    previous_courses: previousCourses,
                    services: services
                },
                success: function(response) {
                    if (response.success) {
                        populateStudentTable(response.students);
                    } else {
                        $('#studentTable tbody').html('<tr><td colspan="5" class="text-center">' + response
                            .message + '</td></tr>');
                    }
                },
                error: function(xhr) {
                    $('#studentTable tbody').html(
                        '<tr><td colspan="5" class="text-center">Error loading students</td></tr>');
                    console.error('Error:', xhr.responseText);
                }
            });
        }

        function populateStudentTable(students) {
            var tableBody = $('#studentTable tbody');
            tableBody.empty();

            if (students.length === 0) {
                tableBody.html(
                    '<tr id="noDataRow"><td colspan="5" class="text-center">No students found for the selected filters</td></tr>'
                );
                $('#studentCount').text(0); // reset count
                return;
            }

            students.forEach(function(student) {
                var row = '<tr>' +
                    '<td><input type="checkbox" name="students[]" value="' + student.student_pk +
                    '" class="student-checkbox" checked></td>' +
                    '<td>' + student.student_pk + '</td>' + 
                    '<td>' + student.course_pk + '</td>' +
                    '<td>' + student.course_name + '</td>' +
                    '<td>' + student.student_name + '</td>' +
                    '<td>' + student.ot_code + '</td>' +
                    '<td>' + (student.service_name ?? '') + '</td>' +
                    '</tr>';

                tableBody.append(row);
            });

            // Ensure select all is checked
            $('#selectAll').prop('checked', true);
            updateSelectedStudents();

            // 🔹 Update student count
            $('#studentCount').text(students.length);
        }
    </script>
@endsection
