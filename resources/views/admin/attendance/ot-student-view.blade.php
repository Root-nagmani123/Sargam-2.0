@extends('admin.layouts.master')

@section('title', 'My Attendance')

@section('setup_content')
<!-- Skip to main content - GIGW Accessibility Requirement -->
<a href="#main-content" class="skip-to-main" aria-label="Skip to main content">Skip to main content</a>

<div class="container-fluid" id="main-content" role="main">
    <x-breadcrum title="My Attendance Record" />
    <x-session_message />

    {{-- Modern Student Information Header --}}
    <div class="card mb-4" style="border-left: 4px solid #004a93;">
        <div class="card-body p-4">
            <h5 class="mb-0 fw-bold d-flex align-items-center">Student Information</h5>
            <hr class="my-2">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="info-badge h-100">
                        <div class="d-flex align-items-center mb-2">
                            <span class="small text-uppercase fw-semibold"
                                style="letter-spacing: 0.5px;">Course</span>
                        </div>
                        <p class="mb-0 fw-bold text-muted">
                            {{ $course->course_name ?? 'N/A' }}
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-badge h-100">
                        <div class="d-flex align-items-center mb-2">
                            <span class="small text-uppercase fw-semibold"
                                style="letter-spacing: 0.5px;">Student Name</span>
                        </div>
                        <p class="mb-0 fw-bold text-muted">
                            {{ $student->display_name ?? 'N/A' }}
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-badge h-100">
                        <div class="d-flex align-items-center mb-2">
                            <span class="small text-uppercase fw-semibold"
                                style="letter-spacing: 0.5px;">OT Code</span>
                        </div>
                        <p class="mb-0 fw-bold text-muted">
                            {{ $student->generated_OT_code ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Enhanced Filter Form --}}
    <div class="card mb-4" style="border-left: 4px solid #004a93;">
        <div class="card-body p-4">
            <h5 class="mb-0 fw-bold d-flex align-items-center">
                <span>Attendance Filters</span>
            </h5>
            <hr class="my-2">
            <form method="GET" action="{{ route('attendance.OT.student_mark.student', [
            'group_pk' => $group_pk,
            'course_pk' => $course_pk,
            'timetable_pk' => $timetable_pk,
            'student_pk' => $student_pk
        ]) }}" id="filterForm">
                <input type="hidden" name="archive_mode" id="archive_mode_input" value="{{ $archiveMode ?? 'active' }}">

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="btn-group border border-2 border-primary rounded-pill overflow-hidden w-100 w-md-auto"
                            role="group" aria-label="Attendance Status Filter">
                            <button type="button" class="btn btn-sm text-decoration-none px-4 py-2 fw-semibold"
                                id="filterArchive_active" aria-pressed="true"
                                aria-label="Show active attendance records">Active Records
                            </button>
                            <button type="button" class="btn btn-sm text-decoration-none px-4 py-2 fw-semibold"
                                id="filterArchive" aria-pressed="false" aria-label="Show archived attendance records">
                                Archived Records
                            </button>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-4">
                    {{-- Course Filter - Only show in Archive mode --}}
                    @if(($archiveMode ?? 'active') === 'archive')
                    <div class="col-lg-4 col-md-6">
                        <label for="filter_course" class="form-label fw-semibold">
                            <i class="bi bi-book me-1 text-primary"></i> Course:
                        </label>
                        <select class="form-select form-select-lg select2" id="filter_course"
                            name="filter_course" aria-label="Filter by Course">
                            <option value="">-- Select Course --</option>
                            @foreach($archivedCourses as $archivedCourse)
                            <option value="{{ $archivedCourse->pk }}"
                                {{ $filterCourse == $archivedCourse->pk ? 'selected' : '' }}>
                                {{ $archivedCourse->course_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    
                    <div class="{{ ($archiveMode ?? 'active') === 'archive' ? 'col-lg-4' : 'col-lg-5' }} col-md-6">
                        <label for="filter_date" class="form-label fw-semibold">
                            <i class="bi bi-calendar-date me-1 text-primary"></i> Date:
                        </label>
                        <input type="date" class="form-control form-control-lg" id="filter_date" name="filter_date"
                            value="{{ $filterDate ?? '' }}" aria-label="Filter by Date">
                    </div>
                    <div class="{{ ($archiveMode ?? 'active') === 'archive' ? 'col-lg-4' : 'col-lg-5' }} col-md-6">
                        <label for="filter_session_time" class="form-label fw-semibold">
                            <i class="bi bi-clock-history me-1 text-primary"></i> Session Time:
                        </label>
                        <select class="form-select form-select-lg select2" id="filter_session_time"
                            name="filter_session_time" aria-label="Filter by Session Time">
                            <option value="">-- Select Session Time --</option>
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
                    </div>
                    <div class="col-lg-2 col-md-12 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 fw-bold btn-lg me-2" id="applyFilters">
                            <i class="bi bi-search"></i> Apply
                        </button>
                        <button type="button" class="btn btn-outline-secondary w-100 btn-lg" id="clearFilters">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('filterForm');
        const archiveModeInput = document.getElementById('archive_mode_input');
        const filterActive = document.getElementById('filterActive');
        const filterArchive = document.getElementById('filterArchive');
        const clearFilters = document.getElementById('clearFilters');
        const applyFilters = document.getElementById('applyFilters');

        // 1. Toggle Button Logic
        function setArchiveMode(mode) {
            archiveModeInput.value = mode;
            // The form submission will handle the class updates via blade based on the new URL parameter
            form.submit();
        }

        filterActive.addEventListener('click', function() {
            if (archiveModeInput.value !== 'active') {
                setArchiveMode('active');
            }
        });

        filterArchive.addEventListener('click', function() {
            if (archiveModeInput.value !== 'archive') {
                setArchiveMode('archive');
            }
        });

        // 2. Clear Filters Logic
        clearFilters.addEventListener('click', function() {
            // Clear standard filter fields
            document.getElementById('filter_date').value = '';
            const sessionSelect = document.getElementById('filter_session_time');
            sessionSelect.value = ''; // Set to the default 'Select Session Time' option
            
            // Clear course filter if it exists (only in archive mode)
            const courseSelect = document.getElementById('filter_course');
            if (courseSelect) {
                courseSelect.value = '';
                // If select2 is initialized, trigger change
                if ($.fn.select2 && $(courseSelect).hasClass('select2-hidden-accessible')) {
                    $(courseSelect).val('').trigger('change');
                }
            }

            // Re-apply the current archive mode for context
            archiveModeInput.value = '{{ $archiveMode ?? '
            active ' }}';

            // Submit the form with cleared filters
            form.submit();
        });

        // 3. Apply Filters Logic (Ensure it explicitly submits the form)
        applyFilters.addEventListener('click', function(e) {
            e.preventDefault(); // Stop default button action
            form.submit(); // Explicitly submit the form
        });

        // 4. Accessibility (GIGW) - Ensure filter changes submit the form
        // Add listeners for changes to submit automatically, or rely on explicit 'Apply' button
        // For better control and performance, rely on the explicit 'Apply' button for main filters,
        // but the 'View Mode' toggle submits instantly.

    });
    </script>

    {{-- Attendance Details Table --}}
    <div class="card shadow">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-semibold">Attendance Details</h4>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h4 class="mb-0 fw-bold d-flex align-items-center">
                    <span>Attendance Records</span>
                </h4>
                <div class="d-flex gap-2 no-print">
                    <button type="button" class="btn btn-light btn-sm" onclick="window.print()"
                        aria-label="Print attendance records">
                        <i class="bi bi-printer-fill me-1" aria-hidden="true"></i>
                        <span>Print</span>
                    </button>
                </div>
            </div>
            <hr class="my-2">

            <input type="hidden" name="group_pk" id="group_pk" value="{{ $group_pk }}">
            <input type="hidden" name="course_pk" id="course_pk" value="{{ $course_pk }}">
            <input type="hidden" name="timetable_pk" id="timetable_pk" value="{{ $timetable_pk }}">
            <input type="hidden" name="student_pk" id="student_pk" value="{{ $student_pk }}">
            <div class="table-responsive">
                <table class="table" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Group</th>
                            <th>Topic</th>
                            <th>Faculty</th>
                            <th>Attendance Status</th>
                            <th>Duty Type</th>
                            <th>Exemption</th>
                            <th>Document / Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data populated by DataTables -->
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>



@endsection
@section('scripts')

<script>
$(function() {
    //alert('sfsdf');
    let table = $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('ot.student.attendance.data')}}",
            data: function(d) {
                d.filter_session_time = $('#filter_session_time').val();
                d.archive_mode = $('#archive_mode').val();
                d.filter_date = $('#filter_date').val();

                d.group_pk = $('#group_pk').val();
                d.course_pk = $('#course_pk').val();
                d.timetable_pk = $('#filter_date').val();
                d.student_pk = $('#student_pk').val();
            }
        }

    // Auto-submit form when filters change
    let filterTimeout;
    $('#filter_date, #filter_session_time, #filter_course').on('change', function() {
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
        // Clear course filter if it exists (only in archive mode)
        const courseSelect = $('#filter_course');
        if (courseSelect.length) {
            courseSelect.val('').trigger('change');
            if ($.fn.select2 && courseSelect.hasClass('select2-hidden-accessible')) {
                courseSelect.select2('val', '');
            }
        }
        // Reset to active mode
        setActiveButton($('#filterActive'));
        $('#archive_mode_input').val('active');
        $('#filterForm').submit();
    });
});
</script>

@endsection