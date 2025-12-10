@extends('admin.layouts.master')

@section('title', 'Attendance')
@section('css')
    <style>
        table.table-bordered.dataTable td:nth-child(4) {
            padding: 0 !important;
        }
    </style>
@endsection
@section('setup_content')
    <form action="{{ route('attendance.save') }}" method="post">
        @csrf
        <div class="container-fluid">

            <x-breadcrum title="Mark Attendance Of Officer Trainees" />
            <x-session_message />

            <input type="hidden" name="group_pk" id="group_pk" value="{{ $group_pk }}">
            <input type="hidden" name="course_pk" id="course_pk" value="{{ $course_pk }}">
            <input type="hidden" name="timetable_pk" id="timetable_pk" value="{{ $courseGroup->timetable_pk }}">

        {{-- Session Summary --}}
        <div class="card shadow mb-4">
            <div class="card-body">
                <h5 class="mb-3">Through this page you can manage Attendance of Officer Trainees</h5>
                <hr>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <strong>Course Name:</strong>
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
                            <span class="text-primary">{{ optional($courseGroup->timetable)->faculty->full_name ?? '' }}</span>
                        </div>
                        <div class="col-md-3">
    <strong>Topic Date:</strong>
    <span class="text-primary">
        @if(!empty(optional($courseGroup->timetable)->START_DATE))
            {{ \Carbon\Carbon::parse($courseGroup->timetable->START_DATE)->format('d-m-Y') }}
        @else
            N/A
        @endif
    </span>
</div>

                        <div class="col-md-3">
                            <strong>Session Time:</strong>
                            <span class="text-primary">
                                {{ optional($courseGroup->timetable)->class_session ?? 'N/A' }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="alert customize-alert rounded-pill alert-success bg-success text-white mt-4 mb-0 border-0 fade show text-center fw-bold">
                        Attendance has been Marked for the Session
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title">Attendance</h4>
                        <div class="">
                            <a href="{{ route('attendance.index') }}" class="btn btn-secondary">Back</a>
                            @if($currentPath === 'mark')
                            <button type="submit" class="btn btn-primary ">save</button>
                            
                            @endif
                        </div>
                    </div>
                    <hr>
                    {!! $dataTable->table(['class' => 'table  table-bordered table-striped table-hover']) !!}
                </div>
            </div>
        </div>
@endsection
@section('scripts')
    {!! $dataTable->scripts() !!}
@endsection