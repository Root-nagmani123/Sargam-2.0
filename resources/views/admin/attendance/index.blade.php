@extends('admin.layouts.master')

@section('title', 'Attendance')

@section('content')
    <div class="container-fluid">

        <x-breadcrum title="Attendance" />
        <x-session_message />

        <div class="">
            <!-- start Zero Configuration -->
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <h4>Attendance</h4>
                        </div>
                    </div>
                    <hr>
                    <div class="row">

                        <div class="col-sm-6">
                            <label for="programme" class="form-label">Programme :</label>
                            <div class="mb-3">
                                <select name="course_master_pk" id="programme" class="form-select select2" required>
                                    <option value="">Select Programme</option>
                                    @foreach($courseMasters as $course)
                                        <option value="{{ $course['pk'] }}">{{ $course['course_name'] }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Select Programme</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="from_date" class="form-label">From Date :</label>
                            <div class="mb-3">
                                <input type="date" class="form-control" id="from_date" name="from_date"
                                    placeholder="From Date">
                                <small class="form-text text-muted">Select From Date</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <label for="to_date" class="form-label">To Date :</label>
                            <div class="mb-3">
                                <input type="date" class="form-control" id="to_date" name="to_date" placeholder="To Date">
                                <small class="form-text text-muted">Select To Date</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="view_type" class="form-label">View Type :</label>
                            <div class="mb-3">
                                <select name="view_type" id="view_type" class="form-select" required>
                                    <option value="1">All</option>
                                    <option value="2">Attendance Taken</option>
                                    <option value="3">Attendance Not Taken</option>
                                </select>
                                <small class="form-text text-muted">Select View Type</small>
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


            </div>

            <div class="card d-none" id="attendanceTableCard">
                <div class="card-body">
                    <div class="table-responsive" id="attendanceTableDiv">
                        <table id="attendanceTable" class="table table-bordered table-striped table-hover ">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Programme Name</th>
                                    <th>Date</th>
                                    <th>Session</th>
                                    <th>Venue</th>
                                    <th>Group</th>
                                    <th>Topic</th>
                                    <th>Faculty</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
            <!-- end Zero Configuration -->
        </div>
    </div>


@endsection