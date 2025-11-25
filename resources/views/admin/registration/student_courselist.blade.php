@extends('admin.layouts.master')

@section('title', 'Student - Course Mapping')

@section('content')
    <div class="container-fluid">
        <x-breadcrum title="Student - Course Mapping" />
        <x-session_message />

        {{-- Filters + Counts + Export --}}
        <div class="card mb-3 p-3" style="border-left: 4px solid #004a93;">
            <div class="row align-items-end g-3">

                <!-- Filters (Course + Status) -->
                <div class="col-md-6 col-sm-12">
                    <form id="filterForm" method="GET" action="{{ route('student.courses') }}">
                        <div class="row g-3">

                            <!-- Course Filter -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Filter by Course</label>
                                <select name="course_id" id="course_id" class="form-select">
                                    <option value="">-- All Courses --</option>
                                    @foreach ($courses as $id => $name)
                                        <option value="{{ $id }}"
                                            {{ (string) $courseId === (string) $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Filter by Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">-- All Status --</option>
                                    <option value="1" {{ (string) $status === '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ (string) $status === '0' ? 'selected' : '' }}>Inactive
                                    </option>
                                </select>
                            </div>

                        </div>
                    </form>
                </div>

                <!-- Total Count -->
                <div class="col-md-3 col-sm-12 text-md-end">
                    <div class="fw-bold mt-2 mt-md-0">
                        Total Records: {{ $filteredCount }}
                    </div>
                </div>

                <!-- Export Section -->
                <div class="col-md-3 col-sm-12">
                    <form method="GET" action="{{ route('studentEnroll.report.export') }}">
                        <input type="hidden" name="course" value="{{ $courseId }}">
                        <input type="hidden" name="status" value="{{ $status }}">

                        <label class="form-label fw-bold">Export Format</label>
                        <div class="input-group">
                            <select name="format" id="format" class="form-select">
                                <option value="">-- Select Format --</option>
                                <option value="pdf">PDF</option>
                                <option value="xlsx">Excel</option>
                                <option value="csv">CSV</option>
                            </select>
                            <button type="submit" class="btn btn-success">Export</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>


        {{-- Data Table --}}
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="zero_config" class="table table-striped table-bordered text-nowrap align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>S.No</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Service</th>
                                <th>OT Code</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Modified Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($enrollments as $index => $row)
                                @php
                                    $student = $row->studentMaster;
                                    $course = $row->course;
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')) }}
                                    </td>
                                    <td>{{ $course->course_name ?? '' }}</td>
                                    <td>{{ $student->service->service_name ?? 'N/A' }}</td>
                                    <td>{{ $student->generated_OT_code ?? '-' }}</td>
                                    <td>
                                        <span
                                            class="badge {{ (int) $row->active_inactive === 1 ? 'bg-success' : 'bg-danger' }}">
                                            {{ (int) $row->active_inactive === 1 ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($row->created_date)->format('d M Y H:i') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row->modified_date)->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            if ($.fn.DataTable.isDataTable('#zero_config')) {
                $('#zero_config').DataTable().destroy();
            }
            $('#zero_config').DataTable({
                pageLength: 10,
                ordering: true,
                searching: true,
                lengthChange: true,
                columnDefs: [{
                    orderable: false,
                    targets: [4] // Status column non-orderable
                }]
            });

            // Auto-submit when either filter changes (optional)
            $('#course_id, #status').change(function() {
                $('#filterForm').submit();
            });
        });
    </script>
@endsection
