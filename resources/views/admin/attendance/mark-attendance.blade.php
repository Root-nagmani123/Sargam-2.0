@extends('admin.layouts.master')

@section('title', 'Attendance')
@section('css')
    <style>
        table.table-bordered.dataTable td:nth-child(4) {
            padding: 0 !important;
        }
    </style>

@endsection
@section('content')
    <form action="{{ route('attendance.save') }}" method="post">
        @csrf
        <div class="container-fluid">

            <x-breadcrum title="Mark Attendance Of Officer Trainees" />
            <x-session_message />

            <input type="hidden" name="group_pk" id="group_pk" value="{{ $group_pk }}">
            <input type="hidden" name="course_pk" id="course_pk" value="{{ $course_pk }}">

            {{-- Session Summary --}}
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Through this page you can manage Attendance of Officer Trainees</h5>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <strong>Major Subject:</strong> 
                            <span class="text-primary">
                                {{ optional($courseGroup->course)->course_name }}
                            </span>
                        </div>

                        <div class="col-md-3">
                            <strong>Topic Name:</strong> 
                            <span class="text-primary">
                                {{ optional($courseGroup->timetable)->subject_topic }}
                            </span>
                        </div>
                        
                        <div class="col-md-3">
                            <strong>Faculty Name:</strong> 
                            <span class="text-primary">{{ $courseGroup->timetable->faculty->full_name }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Topic Date:</strong> 
                            <span class="text-primary">
                                {{ Carbon\Carbon::parse($courseGroup->timetable->mannual_starttime)->format('Y-m-d') }}
                            </span>
                        </div>
                        <div class="col-md-3">
                            <strong>Session Time:</strong>
                            <span class="text-primary">
                                {{ Carbon\Carbon::parse($courseGroup->timetable->classSession->start_time)->format('Y-m-d') }}
                                to
                                {{ Carbon\Carbon::parse($courseGroup->timetable->classSession->end_time)->format('Y-m-d') }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="alert customize-alert rounded-pill alert-success bg-success text-white mt-4 mb-0 border-0 fade show text-center fw-bold">
                        Attendance has been Marked for the Session
                    </div>
                </div>
            </div>

            {{-- Attendance Table --}}
            <div class="card shadow">
                <div class="card-body">

                    <div class="table-responsive" id="attendanceTableDiv">
                        <table id="studentAttendanceTable"
                            class="table table-bordered table-hover align-middle text-center">
                            <thead class="table-primary text-uppercase">
                                <tr>
                                    <th>S.No.</th>
                                    <th>OT Name</th>
                                    <th>OT Code</th>
                                    <th>Attendance</th>
                                    <th>MDO Duty</th>
                                    <th>Escort Duty</th>
                                    <th>Medical Exemption</th>
                                    <th>Other Exemption</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <button type="submit">save</button>
                </div>
            </div>
        </div>
@endsection