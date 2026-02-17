@extends('admin.layouts.master')

@section('title', 'Attendance')
@section('css')
<style>
table.table-bordered.dataTable td:nth-child(4) { padding: 0 !important; }
.mark-attendance .card, .mark-attendance .btn { transition: box-shadow 0.15s ease-in-out, border-color 0.15s ease-in-out; }
</style>
@endsection
@section('setup_content')
<form action="{{ route('attendance.save') }}" method="post">
    @csrf
    <div class="container-fluid mark-attendance px-2 px-md-3 px-lg-4">
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
        <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3 overflow-hidden mb-4">
            <div class="card-body p-3 p-md-4">
                @if(hasRole('Admin') || hasRole('Training-Induction'))
                <p class="mb-3 text-body-primary small">Through this page you can manage Attendance of Officer Trainees</p>
                <hr class="my-3 border-secondary opacity-25">
                @endif
                <div class="row g-3 g-md-4">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="p-3 rounded-3 bg-body-tertiary bg-opacity-50 h-100">
                            <span class="d-block small fw-semibold text-body-primary mb-1">Course Name</span>
                            <span class="text-primary text-break fw-medium">{{ optional($courseGroup->course)->course_name }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="p-3 rounded-3 bg-body-tertiary bg-opacity-50 h-100">
                            <span class="d-block small fw-semibold text-body-primary mb-1">Topic Name</span>
                            <span class="text-primary text-break fw-medium">{{ optional($courseGroup->timetable)->subject_topic }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="p-3 rounded-3 bg-body-tertiary bg-opacity-50 h-100">
                            <span class="d-block small fw-semibold text-body-primary mb-1">Faculty Name</span>
                            <span class="text-primary text-break fw-medium">{{ optional($courseGroup->timetable)->faculty->full_name ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="p-3 rounded-3 bg-body-tertiary bg-opacity-50 h-100">
                            <span class="d-block small fw-semibold text-body-primary mb-1">Topic Date</span>
                            <span class="text-primary fw-medium">
                                @if(!empty(optional($courseGroup->timetable)->START_DATE))
                                {{ \Carbon\Carbon::parse($courseGroup->timetable->START_DATE)->format('d-m-Y') }}
                                @else
                                N/A
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="p-3 rounded-3 bg-body-tertiary bg-opacity-50 h-100">
                            <span class="d-block small fw-semibold text-body-primary mb-1">Session Time</span>
                            <span class="text-primary fw-medium">{{ optional($courseGroup->timetable)->class_session ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                    <h2 class="h5 card-title mb-0 d-flex align-items-center gap-2">
                        <span class="rounded-2 bg-primary bg-opacity-10 p-2 d-inline-flex">
                            <i class="bi bi-list-check text-primary" aria-hidden="true"></i>
                        </span>
                        Attendance
                    </h2>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm rounded-1 px-3 focus-ring focus-ring-secondary">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
                        </a>
                        <a href="{{ route('attendance.export', ['group_pk' => $group_pk, 'course_pk' => $course_pk, 'timetable_pk' => $courseGroup->timetable_pk]) }}"
                            class="btn btn-success btn-sm rounded-1 px-3 focus-ring focus-ring-success">
                            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
                            <span class="d-none d-sm-inline">Export to Excel</span><span class="d-sm-none">Export</span>
                        </a>
                        @if($currentPath === 'mark')
                        <button type="submit" class="btn btn-primary btn-sm rounded-1 px-3 focus-ring focus-ring-primary">
                            <i class="bi bi-check2 me-1" aria-hidden="true"></i>Save
                        </button>
                        @endif
                    </div>
                </div>
                <hr class="my-0 mb-3">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table align-middle mb-0']) !!}
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
    {!! $dataTable->scripts() !!}
    @endsection