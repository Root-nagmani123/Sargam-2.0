@extends('admin.layouts.master')

@section('title', 'Attendance')

@section('content')
    <div class="container-fluid">

        <x-breadcrum title="Attendance" />
        <x-session_message />

        <div class="card">
                <div class="card-header">
                     <div class="row">
                        <div class="col-6">
                            <h4>Attendance</h4>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-2">
                            <label for="programme" class="form-label">Course Name :</label>
                            <div class="mb-3">
                                <select name="course_master_pk" id="programme" class="form-select select2" required>
                                    <option value="">Select Course</option>
                                    @foreach($courseMasters as $course)
                                        <option value="{{ $course['pk'] }}">{{ $course['course_name'] }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Select Course</small>
                            </div>
                        </div>
                        <div class="col-2">
                            <label for="from_date" class="form-label">From Date :</label>
                            <div class="mb-3">
                                <input type="date" class="form-control" id="from_date" name="from_date"
                                    placeholder="From Date">
                                <small class="form-text text-muted">Select From Date</small>
                            </div>
                        </div>

                        <div class="col-2">
                            <label for="to_date" class="form-label">To Date :</label>
                            <div class="mb-3">
                                <input type="date" class="form-control" id="to_date" name="to_date" placeholder="To Date">
                                <small class="form-text text-muted">Select To Date</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Attendance Type :</label>
                            <div class="mb-3">
                                <div class="form-check form-check-inline" style="vertical-align: middle;">
                                    <input class="form-check-input" type="radio" name="attendance_type" id="full_day"
                                        value="full_day" checked>
                                    <label class="form-check-label" for="full_day">Full Day</label>
                                </div>
                                <div class="form-check form-check-inline" style="vertical-align: middle;">
                                    <input class="form-check-input" type="radio" name="attendance_type" id="manual"
                                        value="manual">
                                    <label class="form-check-label" for="manual">Manual</label>
                                </div>
                                <div class="form-check form-check-inline" style="vertical-align: middle;">
                                    <input class="form-check-input" type="radio" name="attendance_type" id="normal"
                                        value="normal">
                                    <label class="form-check-label" for="normal">Normal</label>
                                </div>
                            </div>
                        </div>
                         <div class="col-3" id="normal_session_container" style="display: none;">
                            <label for="session" class="form-label">Normal Session :</label>
                            <div class="mb-3">
                                <select name="session" id="session" class="form-select select2">
                                    <option value="">Select Session</option>
                                    @foreach($sessions as $session)
                                        <option value="{{ $session['pk'] }}">{{ $session['shift_name'] }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Select Session</small>
                            </div>
                        </div>

                        <div class="col-3" id="manual_session_container" style="display: none;">
                            <label for="manual_session" class="form-label">Manual Session:</label>
                            <div class="mb-3">
                                <select name="manual_session" id="manual_session" class="form-select select2">
                                    <option value="">Select Session</option>
                                    @foreach($maunalSessions as $maunalSession)
                                        <option value="{{ $maunalSession['class_session'] }}">
                                            {{ $maunalSession['class_session'] }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Select Session</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-end">
                        <button class="btn btn-primary hstack gap-6 float-end" id="searchAttendance" type="button">
                            <i class="material-icons menu-icon">
                                search
                            </i>
                            Search
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-none" id="attendanceTableCard" >
                        <div class="table-responsive" id="attendanceTableDiv">
                        <table id="attendanceTable" class="table table-bordered table-striped table-hover">
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
                        </table>
                    </div>
                    </div>
                </div>
            </div>
    </div>


@endsection