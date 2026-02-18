@extends('admin.layouts.master')

@section('title', 'Attendance')
@section('css')
<style>
table.table-bordered.dataTable td:nth-child(4) { padding: 0 !important; }
.mark-attendance .card { transition: box-shadow 0.2s ease, border-color 0.2s ease; }
.mark-attendance .card:hover { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08); }
.mark-attendance .btn { transition: transform 0.15s ease, box-shadow 0.15s ease; }
.mark-attendance .btn:hover { transform: translateY(-1px); }
.mark-attendance .session-tile { transition: background-color 0.2s ease, border-color 0.2s ease; }
.mark-attendance .session-tile:hover { background-color: rgba(var(--bs-primary-rgb), 0.06) !important; border-start-color: var(--bs-primary) !important; }
.mark-attendance .table thead th { font-weight: 600; letter-spacing: 0.02em; font-size: 0.8125rem; }
.mark-attendance .table tbody tr { transition: background-color 0.15s ease; }
</style>
@endsection
@section('setup_content')
<form action="{{ route('attendance.save') }}" method="post">
    @csrf
    <div class="container-fluid mark-attendance px-2 px-md-3 px-lg-4 pb-4">
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
            <div class="card-header border-0 bg-transparent pt-3 pb-0 px-3 px-md-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="rounded-2 bg-primary bg-opacity-10 text-primary p-2 d-inline-flex" aria-hidden="true">
                        <i class="bi bi-calendar3-range fs-5"></i>
                    </span>
                    <div>
                        <h3 class="h6 mb-0 fw-semibold text-body-emphasis">Session Details</h3>
                        <p class="small text-body-secondary mb-0 mt-0">Summary of the current session</p>
                    </div>
                </div>
                @if(hasRole('Admin') || hasRole('Training-Induction'))
                <p class="mt-3 mb-0 text-body-secondary small opacity-90">Through this page you can manage attendance of Officer Trainees.</p>
                @endif
            </div>
            <div class="card-body pt-2 pb-3 p-md-4">
                <div class="row g-3 g-md-4">
                    <div class="col-12 col-sm-6 col-xl">
                        <div class="session-tile p-3 rounded-3 border border-1 border-start border-3 border-primary border-opacity-25 bg-body-tertiary bg-opacity-50 h-100">
                            <span class="d-flex align-items-center gap-2 small fw-semibold text-body-secondary text-uppercase mb-2">
                                <i class="bi bi-journal-bookmark-fill text-primary opacity-75"></i>Course Name
                            </span>
                            <span class="text-body-emphasis text-break fw-medium d-block">{{ optional($courseGroup->course)->course_name }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl">
                        <div class="session-tile p-3 rounded-3 border border-1 border-start border-3 border-primary border-opacity-25 bg-body-tertiary bg-opacity-50 h-100">
                            <span class="d-flex align-items-center gap-2 small fw-semibold text-body-secondary text-uppercase mb-2">
                                <i class="bi bi-tag-fill text-primary opacity-75"></i>Topic Name
                            </span>
                            <span class="text-body-emphasis text-break fw-medium d-block">{{ optional($courseGroup->timetable)->subject_topic }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl">
                        <div class="session-tile p-3 rounded-3 border border-1 border-start border-3 border-primary border-opacity-25 bg-body-tertiary bg-opacity-50 h-100">
                            <span class="d-flex align-items-center gap-2 small fw-semibold text-body-secondary text-uppercase mb-2">
                                <i class="bi bi-person-badge-fill text-primary opacity-75"></i>Faculty Name
                            </span>
                            <span class="text-body-emphasis text-break fw-medium d-block">{{ optional($courseGroup->timetable)->faculty->full_name ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl">
                        <div class="session-tile p-3 rounded-3 border border-1 border-start border-3 border-primary border-opacity-25 bg-body-tertiary bg-opacity-50 h-100">
                            <span class="d-flex align-items-center gap-2 small fw-semibold text-body-secondary text-uppercase mb-2">
                                <i class="bi bi-calendar-event-fill text-primary opacity-75"></i>Topic Date
                            </span>
                            <span class="text-body-emphasis fw-medium d-block">
                                @if(!empty(optional($courseGroup->timetable)->START_DATE))
                                {{ \Carbon\Carbon::parse($courseGroup->timetable->START_DATE)->format('d-m-Y') }}
                                @else
                                N/A
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl">
                        <div class="session-tile p-3 rounded-3 border border-1 border-start border-3 border-primary border-opacity-25 bg-body-tertiary bg-opacity-50 h-100">
                            <span class="d-flex align-items-center gap-2 small fw-semibold text-body-secondary text-uppercase mb-2">
                                <i class="bi bi-clock-fill text-primary opacity-75"></i>Session Time
                            </span>
                            <span class="text-body-emphasis fw-medium d-block">{{ optional($courseGroup->timetable)->class_session ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
            <div class="card-header border-0 border-bottom bg-body-tertiary bg-opacity-50 py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3">
                    <h2 class="h5 card-title mb-0 d-flex align-items-center gap-2 fw-semibold text-body-emphasis">
                        <span class="rounded-2 bg-primary bg-opacity-10 p-2 d-inline-flex">
                            <i class="bi bi-list-check text-primary" aria-hidden="true"></i>
                        </span>
                        Attendance List
                    </h2>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm rounded-1" aria-label="Back to attendance list">Back
                        </a>
                        <a href="{{ route('attendance.export', ['group_pk' => $group_pk, 'course_pk' => $course_pk, 'timetable_pk' => $courseGroup->timetable_pk]) }}"
                            class="btn btn-success btn-sm rounded-1 px-3" aria-label="Export to Excel">
                            <span class="d-none d-sm-inline">Export to Excel</span><span class="d-sm-none">Export</span>
                        </a>
                        @if($currentPath === 'mark')
                        <button type="submit" class="btn btn-primary btn-sm rounded-1 px-3">Save Attendance
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0']) !!}
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
    {!! $dataTable->scripts() !!}
    @endsection