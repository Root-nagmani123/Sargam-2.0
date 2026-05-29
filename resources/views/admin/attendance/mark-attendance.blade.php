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
        @if(hasRole('Super Admin') || hasRole('Training Induction Admin'))
        <x-breadcrum title="Mark Attendance Of Officer Trainees" />
        <x-session_message />
        @endif 
        @if(hasRole('Internal Faculty'))
        <x-breadcrum title="Mark Attendance Of Your Assigned Officer Trainees" />
        <x-session_message />
        @endif

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mark-attendance-admin.css') }}?v={{ @filemtime(public_path('css/mark-attendance-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $topicRaw = optional($courseGroup->timetable)->subject_topic ?? '';
    $topicPlain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $topicRaw)));
    $topicShort = \Illuminate\Support\Str::limit($topicPlain, 42, '...');
    $pageHeading = ($topicShort !== '' ? $topicShort : 'Topic') . "'s Attendance";
    $courseName = optional($courseGroup->course)->course_name ?? 'N/A';
    $sessionDate = !empty(optional($courseGroup->timetable)->START_DATE)
        ? \Carbon\Carbon::parse($courseGroup->timetable->START_DATE)->format('d/m/Y')
        : 'N/A';
@endphp

<form action="{{ route('attendance.save') }}" method="post" class="mark-attendance-page">
    @csrf

    <div class="container-fluid mark-att-master-page">
        <input type="hidden" name="group_pk" id="group_pk" value="{{ $group_pk }}">
        <input type="hidden" name="course_pk" id="course_pk" value="{{ $course_pk }}">
        <input type="hidden" name="timetable_pk" id="timetable_pk" value="{{ $courseGroup->timetable_pk }}">
        <x-breadcrum title="Topic wise Attendance">
        @if($currentPath === 'mark')
                    @if(!empty($allMarked) && $allMarked)
                        <button type="submit"
                            class="btn btn-success d-inline-flex align-items-center justify-content-center fw-semibold shadow-sm text-nowrap mark-att-btn-mark"
                            disabled
                            aria-disabled="true">
                            <i class="bi bi-check-circle-fill flex-shrink-0" aria-hidden="true"></i>
                            <span>Attendance Already Marked</span>
                        </button>
                    @else
                        <button type="submit"
                            class="btn btn-primary d-inline-flex align-items-center justify-content-center fw-semibold shadow-sm text-nowrap mark-att-btn-mark">
                            <i class="bi bi-clipboard2-check flex-shrink-0" aria-hidden="true"></i>
                            <span>Mark Attendance</span>
                        </button>
                    @endif
                @endif
    </x-breadcrum>
<div class="d-flex justify-content-end mb-2">
<a href="{{ route('attendance.export', ['group_pk' => $group_pk, 'course_pk' => $course_pk, 'timetable_pk' => $courseGroup->timetable_pk]) }}"
                    class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center fw-semibold shadow-sm text-nowrap mark-att-btn-download">
                    <i class="bi bi-download flex-shrink-0" aria-hidden="true"></i>
                    <span>Download</span>
                </a>
</div>
        <x-session_message />
        <div class="card mark-att-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar mark-att-dt-toolbar w-100">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filters</span>

        {{-- Session Summary --}}
        <div class="card shadow mb-4">
            <div class="card-body">
                @if(hasRole('Super Admin') || hasRole('Training Induction Admin'))
                <h5 class="mb-3">Through this page you can manage Attendance of Officer Trainees</h5>
                
                <hr>
@endif
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
                            <span class="text-primary">{{ get_timetable_faculty_names(optional($courseGroup)->timetable) }}</span>
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

                <div class="programme-dt-panel mark-att-dt-panel">
                    <div class="table-responsive mark-att-dt-scroll">
                        {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table mark-attendance-dt-table']) !!}
                    </div>
                    <div id="markAttDtFooter"
                        class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                        data-dt-footer-for="studentAttendanceTable"></div>
                </div>
            </div>
        </div>
    </div>
    @endsection
    @section('script')
    {!! $dataTable->scripts() !!}
    @endsection
