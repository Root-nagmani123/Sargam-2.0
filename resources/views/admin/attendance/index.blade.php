@extends('admin.layouts.master')

@section('title', 'Attendance')

@section('setup_content')
<style>
.form-label {
    font-size: 0.92rem;
    color: #333;
    /* High contrast */
}

.form-control,
.form-select {
    min-height: 40px;
    border-radius: 6px;
}

.form-control:focus,
.form-select:focus {
    outline: 3px solid #0059b3 !important;
    /* Visible focus outline for accessibility */
    outline-offset: 2px;
}

.btn-primary {
    background-color: #004a93;
    border-color: #004a93;
}

.btn-primary:focus {
    outline: 3px solid #003366;
    outline-offset: 2px;
}

h4 {
    color: #003366;
    letter-spacing: 0.3px;
}

hr {
    border-top: 1px solid #dce1e7;
}
</style>


<div class="container-fluid">
    <x-breadcrum title="Attendance" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body py-4">

            <!-- Title -->
            <div class="row align-items-center mb-2">
                <div class="col-12">
                    <h4 class="fw-bold text-primary mb-0">Attendance</h4>
                </div>
            </div>

            <hr class="mt-3 mb-4">

            <!-- Filter Rows -->
            <div class="row g-4">

                <!-- Course -->
                <div class="col-md-3">
                    <label for="programme" class="form-label fw-semibold">Course Name</label>
                    <select name="course_master_pk" id="programme" class="form-select shadow-sm select2" required>
                        <option value="">Select Course</option>
                        @foreach($courseMasters as $course)
                        <option value="{{ $course['pk'] }}" {{ count($courseMasters) === 1 ? 'selected' : '' }}>
                            {{ $course['course_name'] }}
                        </option>
                        @endforeach

                    </select>
                </div>

                <!-- From Date -->
                <div class="col-md-3">
                    <label for="from_date" class="form-label fw-semibold">From Date</label>
                    <input type="date" class="form-control shadow-sm" id="from_date" name="from_date"
                        placeholder="From Date">
                </div>

                <!-- To Date -->
                <div class="col-md-3">
                    <label for="to_date" class="form-label fw-semibold">To Date</label>
                    <input type="date" class="form-control shadow-sm" id="to_date" name="to_date" placeholder="To Date">
                </div>

                <!-- Attendance Type -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Attendance Type</label>
                    <div class="d-flex flex-wrap gap-3">

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="attendance_type" id="full_day"
                                value="full_day" checked>
                            <label class="form-check-label" for="full_day">Full Day</label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="attendance_type" id="manual"
                                value="manual">
                            <label class="form-check-label" for="manual">Manual</label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="attendance_type" id="normal"
                                value="normal">
                            <label class="form-check-label" for="normal">Normal</label>
                        </div>

                    </div>
                </div>

                <!-- Normal Session -->
                <div class="col-md-3" id="normal_session_container" style="display:none;">
                    <label for="session" class="form-label fw-semibold">Normal Session</label>
                    <select name="session" id="session" class="form-select shadow-sm select2">
                        <option value="">Select Session</option>
                        @foreach($sessions as $session)
                        <option value="{{ $session['pk'] }}">{{ $session['shift_name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Manual Session -->
                <div class="col-md-3" id="manual_session_container" style="display:none;">
                    <label for="manual_session" class="form-label fw-semibold">Manual Session</label>
                    <select name="manual_session" id="manual_session" class="form-select shadow-sm select2">
                        <option value="">Select Session</option>
                        @foreach($maunalSessions as $maunalSession)
                        <option value="{{ $maunalSession['class_session'] }}">
                            {{ $maunalSession['class_session'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="mt-4">

            <!-- Search Button -->
            <div class="text-end mb-4">
                <button class="btn btn-primary px-4 py-2 shadow-sm d-inline-flex align-items-center"
                    id="searchAttendance" type="button">
                    <span class="material-symbols-rounded me-2 fs-6">search</span>
                    Search
                </button>
            </div>

            <div id="attendanceTableCard">
                <div class="table-responsive" id="attendanceTableDiv">
                    <table id="attendanceTable" class="table text-nowrap">
                        <thead>
                            <tr>
                                <th class="col">#</th>
                                <th class="col">Course Name</th>
                                <th class="col">Date</th>
                                <th class="col">Session</th>
                                <th class="col">Venue</th>
                                <th class="col">Group</th>
                                <th class="col">Topic</th>
                                <th class="col">Faculty</th>
                                <th class="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(hasRole('Admin') || hasRole('Training-Induction'))
                            <tr id="defaultMessageRow">
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <p class="mb-2" style="font-size: 1rem;">Apply filter to mark attendance.</p>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @if(hasRole('Internal Faculty'))
                            <tr id="defaultMessageRow">
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <p class="mb-2" style="font-size: 1rem;">Apply filter to see attendance.</p>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endsection