@extends('admin.layouts.master')

@section('title', 'Attendance')
@section('css')
<style>
table.table-bordered.dataTable td:nth-child(4) {
    padding: 0 !important;
}

/* Responsive adjustments */
@media (max-width: 575.98px) {
    .card-body {
        padding: 1rem !important;
    }
    
    .btn {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    .card-title {
        font-size: 1.25rem;
    }
}

@media (min-width: 576px) and (max-width: 767.98px) {
    .card-body {
        padding: 1.25rem !important;
    }
}
</style>
@endsection
@section('setup_content')
<form action="{{ route('attendance.save') }}" method="post">
    @csrf
    <div class="container-fluid px-2 px-md-3 px-lg-4">
        @if(hasRole('Admin') || hasRole('Training-Induction'))
        <x-breadcrum title="Mark Attendance Of Officer Trainees" />
        <x-session_message />
        @endif 
        @if(hasRole('Internal Faculty'))
        <x-breadcrum title="Mark Attendance Of Your Assigned Officer Trainees" />
        <x-session_message />
        @endif

        <input type="hidden" name="group_pk" id="group_pk" value="{{ $group_pk }}">
        <input type="hidden" name="course_pk" id="course_pk" value="{{ $course_pk }}">
        <input type="hidden" name="timetable_pk" id="timetable_pk" value="{{ $courseGroup->timetable_pk }}">

        {{-- Session Summary --}}
        <div class="card shadow mb-4">
            <div class="card-body p-3 p-md-4">
                @if(hasRole('Admin') || hasRole('Training-Induction'))
                <h5 class="mb-3">Through this page you can manage Attendance of Officer Trainees</h5>
                
                <hr>
@endif
                <div class="row g-3 g-md-4">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="d-flex flex-column">
                            <strong class="mb-1">Course Name:</strong>
                            <span class="text-primary text-break">
                                {{ optional($courseGroup->course)->course_name }}
                            </span>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="d-flex flex-column">
                            <strong class="mb-1">Topic Name:</strong>
                            <span class="text-primary text-break">
                                {{ optional($courseGroup->timetable)->subject_topic }}
                            </span>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="d-flex flex-column">
                            <strong class="mb-1">Faculty Name:</strong>
                            <span class="text-primary text-break">
                                {{ optional($courseGroup->timetable)->faculty->full_name ?? '' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="d-flex flex-column">
                            <strong class="mb-1">Topic Date:</strong>
                            <span class="text-primary">
                                @if(!empty(optional($courseGroup->timetable)->START_DATE))
                                {{ \Carbon\Carbon::parse($courseGroup->timetable->START_DATE)->format('d-m-Y') }}
                                @else
                                N/A
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="d-flex flex-column">
                            <strong class="mb-1">Session Time:</strong>
                            <span class="text-primary">
                                {{ optional($courseGroup->timetable)->class_session ?? 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="alert customize-alert rounded-pill alert-success bg-success text-white mt-4 mb-0 border-0 fade show text-center fw-bold">
                    Attendance has been Marked for the Session
                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
                    <h4 class="card-title mb-0">Attendance</h4>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto">
                        <a href="{{ route('attendance.index') }}" class="btn btn-secondary flex-fill flex-md-none">Back</a>
                        <a href="{{ route('attendance.export', ['group_pk' => $group_pk, 'course_pk' => $course_pk, 'timetable_pk' => $courseGroup->timetable_pk]) }}"
                            class="btn btn-success flex-fill flex-md-none">
                            <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-sm-inline">Export to Excel</span><span class="d-sm-none">Export</span>
                        </a>
                        @if($currentPath === 'mark')
                        <button type="submit" class="btn btn-primary flex-fill flex-md-none">Save</button>
                        @endif
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-striped table-hover']) !!}
                </div>
            </div>
        </div>
    </div>
    @endsection
    @section('scripts')
    {!! $dataTable->scripts() !!}
    @endsection