@extends('admin.layouts.master')

@section('title', 'Medical Exception Faculty View - Sargam | Lal Bahadur')

@section('setup_content')
<style>
.table-responsive {
    overflow-x: auto;
}

/* Accessibility helpers per GIGW */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.table {
    width: 100%;
}

.table thead th {
    background-color: #0b5ed7;
    /* high-contrast header */
    color: #fff;
}

.table tbody tr:nth-child(even) {
    background-color: #f5f7fa;
}

.form-control:focus,
.btn:focus,
select:focus,
input:focus {
    outline: 3px solid #005a9e;
    outline-offset: 2px;
}

.caption-muted {
    color: #333;
    font-weight: 600;
}

.print-btn i {
    margin-right: 6px;
}
</style>

<div class="container-fluid">
    <x-breadcrum title="Medical Exception Faculty View"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-6">
                    <h4>Medical Exception Faculty View</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <button type="button" class="btn btn-info d-flex align-items-center" onclick="window.print()">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">print</i>
                            Print
                        </button>
                    </div>
                </div>
            </div>
            <form class="row" role="search" aria-label="Medical exception filters">
                <div class="col-3">
                    <div class="mb-3">
                        <label for="filter_course" class="form-label">Course Name</label>
                        <select name="course" id="filter_course" class="form-control"
                            aria-describedby="filter_course_help">
                            <option value="">Select</option>
                            <option value="A01">A01</option>
                            <option value="A02">A02</option>
                        </select>
                        <small id="filter_course_help" class="form-text text-muted">Choose the course to filter
                            records.</small>
                    </div>
                </div>
                <div class="col-4" aria-label="Date range">
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="filter_date_from" class="form-label">Date From</label>
                                <input type="date" name="date_from" id="filter_date_from" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="filter_date_to" class="form-label">Date To</label>
                                <input type="date" name="date_to" id="filter_date_to" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-4" aria-label="Time range">
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="filter_time_from" class="form-label">Time From</label>
                                <input type="time" name="time_from" id="filter_time_from" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="filter_time_to" class="form-label">Time To</label>
                                <input type="time" name="time_to" id="filter_time_to" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-1">
                    <div class="mb-3">
                        <label for="filter_ot" class="form-label">OT Code</label>
                        <select name="ot_code" id="filter_ot" class="form-control" aria-describedby="filter_ot_help">
                            <option value="">Select</option>
                            <option value="A01">A01</option>
                            <option value="A02">A02</option>
                        </select>
                        <small id="filter_ot_help" class="form-text text-muted">Filter by OT code.</small>
                    </div>
                </div>
            </form>

            <hr>

            @php
            // Check if this is a faculty login view
            $isFacultyView = isset($isFacultyView) && $isFacultyView === true;
            @endphp

            @if($isFacultyView)

            @if(isset($hasData) && $hasData)

            <!-- Course Summary Table -->
            <div class="table-responsive" aria-live="polite">
                <table class="table" role="table">
                    <caption class="caption-muted">Medical Exceptions by Course</caption>
                    <thead>
                        <tr>
                            <th scope="col">Course Name</th>
                            <th scope="col">CC</th>
                            <th scope="col">Total Students</th>
                            <th scope="col">Students on Medical Exception</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($coursePaginator as $course)
                        <tr>
                            <td>{{ $course['course_name'] }}</td>
                            <td>{{ $course['cc_name'] ?? 'N/A' }}</td>
                            <td>{{ $course['total_students'] }}</td>
                            <td>{{ $course['total_exemption_count'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination: Course Summary -->
            <nav aria-label="Course pagination" class="mt-2">
                <ul class="pagination pagination-sm">
                    @php
                        $courseCurrent = $coursePaginator->currentPage();
                        $courseLast = $coursePaginator->lastPage();
                        $studentsCurrent = isset($studentsPaginator) ? $studentsPaginator->currentPage() : 1;
                    @endphp
                    <li class="page-item {{ $courseCurrent <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['course_page' => max(1, $courseCurrent - 1), 'students_page' => $studentsCurrent]) }}" aria-label="Previous" aria-disabled="{{ $courseCurrent <= 1 ? 'true' : 'false' }}">&laquo;</a>
                    </li>
                    @for($p = 1; $p <= $courseLast; $p++)
                        <li class="page-item {{ $p === $courseCurrent ? 'active' : '' }}" aria-current="{{ $p === $courseCurrent ? 'page' : 'false' }}">
                            <a class="page-link" href="{{ request()->fullUrlWithQuery(['course_page' => $p, 'students_page' => $studentsCurrent]) }}">{{ $p }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $courseCurrent >= $courseLast ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['course_page' => min($courseLast, $courseCurrent + 1), 'students_page' => $studentsCurrent]) }}" aria-label="Next" aria-disabled="{{ $courseCurrent >= $courseLast ? 'true' : 'false' }}">&raquo;</a>
                    </li>
                </ul>
            </nav>

            <!-- Detailed Students Table -->
            <div class="table-responsive mt-4">
                <table class="table" role="table">
                    <caption class="caption-muted">Students under Medical Exception</caption>
                    <thead>
                        <tr>
                            <th scope="col">Course Name</th>
                            <th scope="col">OT Code</th>
                            <th scope="col">Student Name</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($studentsPaginator as $row)
                        <tr>
                            <td>{{ $row['course_name'] }}</td>
                            <td>{{ $row['generated_OT_code'] }}</td>
                            <td>{{ $row['display_name'] }}</td>
                            <td>{{ $row['status'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination: Students Detail -->
            <nav aria-label="Students pagination" class="mt-2">
                <ul class="pagination pagination-sm">
                    @php
                        $studentsCurrent = $studentsPaginator->currentPage();
                        $studentsLast = $studentsPaginator->lastPage();
                        $courseCurrent = isset($coursePaginator) ? $coursePaginator->currentPage() : 1;
                    @endphp
                    <li class="page-item {{ $studentsCurrent <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['students_page' => max(1, $studentsCurrent - 1), 'course_page' => $courseCurrent]) }}" aria-label="Previous" aria-disabled="{{ $studentsCurrent <= 1 ? 'true' : 'false' }}">&laquo;</a>
                    </li>
                    @for($p = 1; $p <= $studentsLast; $p++)
                        <li class="page-item {{ $p === $studentsCurrent ? 'active' : '' }}" aria-current="{{ $p === $studentsCurrent ? 'page' : 'false' }}">
                            <a class="page-link" href="{{ request()->fullUrlWithQuery(['students_page' => $p, 'course_page' => $courseCurrent]) }}">{{ $p }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $studentsCurrent >= $studentsLast ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['students_page' => min($studentsLast, $studentsCurrent + 1), 'course_page' => $courseCurrent]) }}" aria-label="Next" aria-disabled="{{ $studentsCurrent >= $studentsLast ? 'true' : 'false' }}">&raquo;</a>
                    </li>
                </ul>
            </nav>

            @else
            <div class="alert alert-info text-center" role="status">
                <span class="sr-only">Information:</span>
                <i class="material-icons material-symbols-rounded fs-1" aria-hidden="true">info</i>
                <div class="mt-2">No records found</div>
            </div>

            @endif

            @else
            <!-- Admin View as tables -->
            @if(isset($facultyData) && count($facultyData) > 0)

            @php
                $adminCourseCurrent = isset($adminCoursePaginator) ? $adminCoursePaginator->currentPage() : 1;
                $adminCourseLast = isset($adminCoursePaginator) ? $adminCoursePaginator->lastPage() : 1;
                $adminStudentsCurrent = isset($adminStudentsPaginator) ? $adminStudentsPaginator->currentPage() : 1;
                $adminStudentsLast = isset($adminStudentsPaginator) ? $adminStudentsPaginator->lastPage() : 1;
            @endphp

            <!-- Faculty Course Summary -->
            <div class="table-responsive" aria-live="polite">
                <table class="table" role="table">
                    <caption class="caption-muted">Faculty Courses and Medical Exceptions</caption>
                    <thead>
                        <tr>
                            <th scope="col">Faculty Name</th>
                            <th scope="col">Course Name</th>
                            <th scope="col">CC</th>
                            <th scope="col">ACC</th>
                            <th scope="col">Total Students</th>
                            <th scope="col">Students on Medical Exception</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adminCoursePaginator as $row)
                        <tr>
                            <td>{{ $row['faculty_name'] }}</td>
                            <td>{{ $row['course_name'] }}</td>
                            <td>{{ $row['cc'] }}</td>
                            <td>{{ $row['acc'] }}</td>
                            <td>{{ $row['total_students'] }}</td>
                            <td>{{ $row['exemption_count'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination: Admin Course Summary -->
            <nav aria-label="Admin course pagination" class="mt-2">
                <ul class="pagination pagination-sm">
                    <li class="page-item {{ $adminCourseCurrent <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['admin_course_page' => max(1, $adminCourseCurrent - 1), 'admin_students_page' => $adminStudentsCurrent]) }}" aria-label="Previous" aria-disabled="{{ $adminCourseCurrent <= 1 ? 'true' : 'false' }}">&laquo;</a>
                    </li>
                    @for($p = 1; $p <= $adminCourseLast; $p++)
                        <li class="page-item {{ $p === $adminCourseCurrent ? 'active' : '' }}" aria-current="{{ $p === $adminCourseCurrent ? 'page' : 'false' }}">
                            <a class="page-link" href="{{ request()->fullUrlWithQuery(['admin_course_page' => $p, 'admin_students_page' => $adminStudentsCurrent]) }}">{{ $p }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $adminCourseCurrent >= $adminCourseLast ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['admin_course_page' => min($adminCourseLast, $adminCourseCurrent + 1), 'admin_students_page' => $adminStudentsCurrent]) }}" aria-label="Next" aria-disabled="{{ $adminCourseCurrent >= $adminCourseLast ? 'true' : 'false' }}">&raquo;</a>
                    </li>
                </ul>
            </nav>

            <!-- Detailed Student Listing -->
            <div class="table-responsive mt-4">
                <table class="table" role="table">
                    <caption class="caption-muted">Students by Faculty and Course</caption>
                    <thead>
                        <tr>
                            <th scope="col">Faculty Name</th>
                            <th scope="col">Course Name</th>
                            <th scope="col">OT Code</th>
                            <th scope="col">Student Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adminStudentsPaginator as $row)
                        <tr>
                            <td>{{ $row['faculty_name'] }}</td>
                            <td>{{ $row['course_name'] }}</td>
                            <td>{{ $row['generated_OT_code'] }}</td>
                            <td>{{ $row['display_name'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination: Admin Students Detail -->
            <nav aria-label="Admin students pagination" class="mt-2">
                <ul class="pagination pagination-sm">
                    <li class="page-item {{ $adminStudentsCurrent <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['admin_students_page' => max(1, $adminStudentsCurrent - 1), 'admin_course_page' => $adminCourseCurrent]) }}" aria-label="Previous" aria-disabled="{{ $adminStudentsCurrent <= 1 ? 'true' : 'false' }}">&laquo;</a>
                    </li>
                    @for($p = 1; $p <= $adminStudentsLast; $p++)
                        <li class="page-item {{ $p === $adminStudentsCurrent ? 'active' : '' }}" aria-current="{{ $p === $adminStudentsCurrent ? 'page' : 'false' }}">
                            <a class="page-link" href="{{ request()->fullUrlWithQuery(['admin_students_page' => $p, 'admin_course_page' => $adminCourseCurrent]) }}">{{ $p }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $adminStudentsCurrent >= $adminStudentsLast ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['admin_students_page' => min($adminStudentsLast, $adminStudentsCurrent + 1), 'admin_course_page' => $adminCourseCurrent]) }}" aria-label="Next" aria-disabled="{{ $adminStudentsCurrent >= $adminStudentsLast ? 'true' : 'false' }}">&raquo;</a>
                    </li>
                </ul>
            </nav>

            @else
            <div class="alert alert-info text-center" role="status">
                <span class="sr-only">Information:</span>
                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 48px;"
                    aria-hidden="true">info</i>
                <p class="mt-2">No faculty data found matching the selected filters.</p>
            </div>
            @endif
            @endif
        </div>
    </div>
</div>

@endsection