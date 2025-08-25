@extends('admin.layouts.master')

@section('title', 'Student - Course Mapping')

@section('content')
    <div class="container-fluid">

        {{-- Header --}}
        <div class="card card-body py-3 mb-3">
            <div class="row align-items-center">
                <div class="col-6">
                    <h4 class="mb-0">Student - Course Mapping</h4>
                </div>
            </div>
        </div>

        {{-- Filters + Counts + Export --}}
        <div class="card mb-3 p-3">
            <div class="row align-items-end g-3">

                <!-- Course Filter -->
                <div class="col-md-3 col-sm-12">
                    <form id="filterForm" method="GET" action="{{ route('student.courses') }}">
                        <label for="course_id" class="form-label fw-bold">Filter by Course</label>
                        <select name="course_id" id="course_id" class="form-select"
                            onchange="document.getElementById('filterForm').submit();">
                            <option value="">-- All Courses --</option>
                            @foreach ($courses as $id => $name)
                                <option value="{{ $id }}"
                                    {{ (string) $courseId === (string) $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <!-- Status Filter -->
                <div class="col-md-3 col-sm-12">
                    <form id="statusForm" method="GET" action="{{ route('student.courses') }}">
                        <label for="status" class="form-label fw-bold">Filter by Status</label>
                        <select name="status" id="status" class="form-select"
                            onchange="document.getElementById('statusForm').submit();">
                            <option value="">-- All Status --</option>
                            <option value="1" {{ (string) $status === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ (string) $status === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </form>
                </div>

                <!-- Total / Showing -->
                <div class="col-md-3 col-sm-12 text-md-end">
                    <div class="fw-bold">
                        <span>Total Records: {{ $totalCount }}</span> &nbsp; | &nbsp;
                        <span>Showing: {{ $filteredCount }}</span>
                    </div>
                </div>

                <!-- Export Form -->
                <div class="col-md-3 col-sm-12">
                    <form method="GET" action="{{ route('studentEnroll.report.export') }}">
                        <input type="hidden" name="course" value="{{ $courseId }}">
                        <input type="hidden" name="status" value="{{ $status }}">

                        <label for="format" class="form-label fw-bold">Export Format</label>
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
        <div class="card">
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
                                    <td colspan="7" class="text-center text-muted">No records found.</td>
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
                        targets: [4]
                    } // Status column non-orderable
                ]
            });
        });
    </script>
@endsection
