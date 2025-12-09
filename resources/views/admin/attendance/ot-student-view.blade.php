@extends('admin.layouts.master')

@section('title', 'My Attendance')

@section('setup_content')
<style>
.btn-group[role="group"] .btn {
    transition: all 0.3s ease-in-out;
    border-radius: 0;
}

.btn-group[role="group"] .btn:first-child {
    border-top-left-radius: 50rem !important;
    border-bottom-left-radius: 50rem !important;
}

.btn-group[role="group"] .btn:last-child {
    border-top-right-radius: 50rem !important;
    border-bottom-right-radius: 50rem !important;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
}

.btn-group .btn.active {
    box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 123, 255, 0.3);
}

.btn:focus-visible {
    outline: 3px solid #0d6efd;
    outline-offset: 2px;
}

.btn-outline-secondary {
    color: #333;
    border-color: #999;
}

.btn-outline-secondary:hover {
    background-color: #f8f9fa;
    border-color: #666;
}

/* Wrapper with soft glass look */
.filter-wrapper {
    background: #fff;
    border-radius: 18px;
    padding: 0;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07);
    border: 1px solid #e6e9ef;
    overflow: hidden;
}

/* Header */
.filter-header {
    background: #f8f9fb;
    padding: 16px 22px;
    border-bottom: 1px solid #e9ecef;
}

/* Body */
.filter-body {
    padding: 26px 22px 30px;
}

/* Floating field */
.floating-field {
    position: relative;
}

.floating-field label {
    position: absolute;
    top: 50%;
    left: 40px;
    transform: translateY(-50%);
    background: #fff;
    padding: 0 4px;
    color: #6c757d;
    pointer-events: none;
    transition: 0.2s ease;
}

.floating-field input:not(:placeholder-shown)+label,
.floating-field input:focus+label,
.floating-field select:focus+label {
    top: -8px;
    font-size: 12px;
    color: var(--bs-primary);
}

.floating-field .form-control,
.floating-field .form-select {
    padding-left: 42px;
    border-radius: 12px;
    height: 48px;
}

.field-icon {
    position: absolute;
    top: 50%;
    left: 14px;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 16px;
}

/* Segmented Switch */
.segment-control {
    position: relative;
    display: inline-flex;
    background: #f1f3f5;
    border-radius: 50px;
    padding: 5px;
    width: auto;
}

.seg-btn {
    background: transparent;
    border: 0;
    padding: 10px 24px;
    font-weight: 600;
    border-radius: 40px;
    position: relative;
    z-index: 2;
    color: #495057;
    transition: 0.25s ease;
}

.seg-btn.active {
    color: #fff;
}

.seg-highlight {
    position: absolute;
    top: 5px;
    bottom: 5px;
    width: calc(50% - 5px);
    background: var(--bs-primary);
    border-radius: 40px;
    transition: transform 0.25s ease;
    z-index: 1;
}

.seg-btn:nth-child(1).active~.seg-highlight {
    transform: translateX(0%);
}

.seg-btn:nth-child(2).active~.seg-highlight {
    transform: translateX(100%);
}

/* Focus visible (GIGW compliant) */
input:focus,
select:focus,
button:focus-visible {
    outline: 3px solid rgba(0, 97, 193, 0.35) !important;
    box-shadow: none !important;
}

/* Main table styling */
.modern-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

/* Header */
.modern-table thead th {
    background: #b32222;
    /* Matching your screenshot */
    color: #fff;
    font-weight: 600;
    padding: 14px;
    font-size: 14.5px;
    white-space: nowrap;
}

.modern-table tbody td {
    padding: 14px;
    font-size: 14px;
    border-bottom: 1px solid #ececec;
    vertical-align: middle;
}

/* Round corners like screenshot */
.modern-table thead tr:first-child th:first-child {
    border-top-left-radius: 12px;
}

.modern-table thead tr:first-child th:last-child {
    border-top-right-radius: 12px;
}

/* Hover (GIGW minimal contrast safe) */
.modern-table tbody tr:hover {
    background: #fafafa !important;
}

/* Attendance Status Colors (matching screenshot) */
.status-present {
    color: #2e8b57 !important;
    font-weight: 600;
}

.status-late {
    color: #d98c00 !important;
    font-weight: 600;
}

.status-absent {
    color: #b30000 !important;
    font-weight: 600;
}

/* Icon button styling */
.icon-btn {
    color: #444;
    font-size: 18px;
    line-height: 1;
    padding: 2px 6px;
}

.icon-btn:hover {
    color: #000;
}

/* GIGW Focus outline */
*:focus-visible {
    outline: 3px solid rgba(0, 73, 150, 0.45) !important;
    border-radius: 4px;
}
</style>
<div class="container-fluid">
    <x-breadcrum title="My Attendance Record" />
    <x-session_message />

    {{-- Student Information Header --}}
    <div class="card shadow mb-4" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <strong>Course Name:</strong>
                    <span class="text-primary">
                        {{ $course->course_name ?? 'N/A' }}
                    </span>
                </div>
                <div class="col-md-4">
                    <strong>Student Name:</strong>
                    <span class="text-primary">
                        {{ $student->display_name ?? 'N/A' }}
                    </span>
                </div>
                <div class="col-md-4">
                    <strong>OT Code:</strong>
                    <span class="text-primary">
                        {{ $student->generated_OT_code ?? 'N/A' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="filter-wrapper mb-4">
        <div class="filter-header">
            <h5 class="fw-bold m-0">
                <i class="bi bi-funnel-fill text-primary me-2"></i>
                Smart Filters
            </h5>
        </div>

        <div class="filter-body">
            <form method="GET" action="{{ route('attendance.OT.student_mark.student', [
                    'group_pk' => $group_pk,
                    'course_pk' => $course_pk,
                    'timetable_pk' => $timetable_pk,
                    'student_pk' => $student_pk]) }}" id="filterForm">

                <input type="hidden" name="archive_mode" id="archive_mode_input" value="{{ $archiveMode ?? 'active' }}">

                <!-- Animated Segmented Switch -->
                <div class="d-flex justify-content-end">
                    <div class="segment-control mb-4 d-inline-flex align-items-center gap-2" role="group"
                        aria-label="Active or Archive Filter">

                        <button type="button"
                            class="seg-btn {{ ($archiveMode ?? 'active') === 'active' ? 'active' : '' }}"
                            id="filterActive">
                            <i class="bi bi-check-circle me-1"></i> Active
                        </button>

                        <button type="button"
                            class="seg-btn {{ ($archiveMode ?? 'active') === 'archive' ? 'active' : '' }}"
                            id="filterArchive">
                            <i class="bi bi-archive me-1"></i> Archive
                        </button>

                        <div class="seg-highlight"></div>
                    </div>
                </div>


                <div class="row g-4 mx-auto">

                    <!-- Date -->
                    <div class="col-md-3">
                        <div class="floating-field">
                            <i class="bi bi-calendar-event field-icon"></i>
                            <input type="date" id="filter_date" name="filter_date" class="form-control"
                                value="{{ $filterDate ?? '' }}">
                        </div>
                    </div>

                    <!-- Session -->
                    <div class="col-md-3">
                        <div class="floating-field">
                            <i class="bi bi-clock field-icon"></i>
                            <select id="filter_session_time" name="filter_session_time" class="select2 form-control">
                                <option value=""></option>
                                @foreach($sessions as $session)
                                <option value="{{ $session->pk }}"
                                    {{ $filterSessionTime == $session->pk ? 'selected' : '' }}>
                                    {{ $session->shift_name }} ({{ $session->start_time }} - {{ $session->end_time }})
                                </option>
                                @endforeach
                                @foreach($maunalSessions as $manualSession)
                                <option value="{{ $manualSession->class_session }}"
                                    {{ $filterSessionTime == $manualSession->class_session ? 'selected' : '' }}>
                                    {{ $manualSession->class_session }}
                                </option>
                                @endforeach
                            </select>
                            <label for="filter_session_time">Session Time</label>
                        </div>
                    </div>

                    <!-- Clear -->
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-dark w-100 fw-semibold py-2" id="clearFilters">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>




    {{-- Attendance Details Table --}}
    <div class="card shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-semibold">Attendance Details</h4>
                <a href="{{ route('attendance.index') }}" class="btn btn-secondary btn-sm">
                    Back
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            @if(count($attendanceRecords) > 0)
            <div class="table-responsive">
                <table class="table modern-table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Sessions</th>
                            <th>Venue</th>
                            <th>Group</th>
                            <th>Topic</th>
                            <th>Faculty</th>
                            <th>Attendance</th>
                            <th>Duty Type (MDO/Escort)</th>
                            <th>Exemption</th>
                            <th>Doc/Comment</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($attendanceRecords as $record)
                        <tr>
                            <td class="text-nowrap">
                                {{ $record['date'] }}
                            </td>

                            <td>{{ $record['session_time'] }}</td>
                            <td>{{ $record['venue'] }}</td>
                            <td>{{ $record['group'] }}</td>
                            <td>{{ $record['topic'] }}</td>
                            <td>{{ $record['faculty'] }}</td>

                            <td>
                                @if($record['attendance_status'] == 'Present')
                                <span class="status-present">Present</span>
                                @elseif($record['attendance_status'] == 'Late')
                                <span class="status-late">Late</span>
                                @elseif($record['attendance_status'] == 'Absent')
                                <span class="status-absent">Absent</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td>{{ $record['duty_type'] ?? '-' }}</td>

                            <td>{{ $record['exemption_type'] ?? '-' }}</td>

                            <td class="text-nowrap">
                                @if($record['exemption_document'])
                                <a href="{{ asset('storage/' . $record['exemption_document']) }}" target="_blank"
                                    class="icon-btn me-2">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ asset('storage/' . $record['exemption_document']) }}" download
                                    class="icon-btn">
                                    <i class="bi bi-download"></i>
                                </a>
                                @elseif($record['exemption_comment'])
                                {{ $record['exemption_comment'] }}
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
            @else
            <div class="p-4 text-center text-muted">
                No attendance records found.
            </div>
            @endif
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize select2 if available
    if ($.fn.select2) {
        $('.select2').select2({
            placeholder: 'Select Session Time',
            allowClear: true
        });
    }

    // Active/Archive toggle button handlers
    $('#filterActive').on('click', function() {
        setActiveButton($(this));
        $('#archive_mode_input').val('active');
        $('#filterForm').submit();
    });

    $('#filterArchive').on('click', function() {
        setActiveButton($(this));
        $('#archive_mode_input').val('archive');
        $('#filterForm').submit();
    });

    // Function to set active button styling
    function setActiveButton(activeBtn) {
        // Reset all buttons to outline style
        $('#filterActive')
            .removeClass('btn-success active text-white')
            .addClass('btn-outline-success')
            .attr('aria-pressed', 'false');

        $('#filterArchive')
            .removeClass('btn-secondary active text-white')
            .addClass('btn-outline-secondary')
            .attr('aria-pressed', 'false');

        // Set the active button
        if (activeBtn.attr('id') === 'filterActive') {
            activeBtn.removeClass('btn-outline-success')
                .addClass('btn-success text-white active')
                .attr('aria-pressed', 'true');
        } else if (activeBtn.attr('id') === 'filterArchive') {
            activeBtn.removeClass('btn-outline-secondary')
                .addClass('btn-secondary text-white active')
                .attr('aria-pressed', 'true');
        }
    }

    // Auto-submit form when filters change
    let filterTimeout;
    $('#filter_date, #filter_session_time').on('change', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            $('#filterForm').submit();
        }, 300); // Small delay to avoid multiple submissions
    });

    // Clear filters button
    $('#clearFilters').on('click', function() {
        $('#filter_date').val('');
        $('#filter_session_time').val('').trigger('change');
        if ($.fn.select2) {
            $('#filter_session_time').select2('val', '');
        }
        // Reset to active mode
        setActiveButton($('#filterActive'));
        $('#archive_mode_input').val('active');
        $('#filterForm').submit();
    });
});
</script>
@endsection