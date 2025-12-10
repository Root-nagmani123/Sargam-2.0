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
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('attendance.OT.student_mark.student', [
                'group_pk' => $group_pk,
                'course_pk' => $course_pk,
                'timetable_pk' => $timetable_pk,
                'student_pk' => $student_pk
            ]) }}" id="filterForm">
                <input type="hidden" name="archive_mode" id="archive_mode_input" value="{{ $archiveMode ?? 'active' }}">
                
                <!-- Active/Archive Toggle Buttons -->
                <div class="row mb-3">
                    <div class="col-12 text-end">
                        <div class="btn-group shadow-sm rounded-pill overflow-hidden" role="group"
                            aria-label="Attendance Status Filter">
                            <button type="button" class="btn {{ ($archiveMode ?? 'active') === 'active' ? 'btn-success active text-white' : 'btn-outline-success' }} px-4 fw-semibold" 
                                    id="filterActive" aria-pressed="{{ ($archiveMode ?? 'active') === 'active' ? 'true' : 'false' }}">
                                <i class="bi bi-check-circle me-1"></i> Active
                            </button>
                            <button type="button" class="btn {{ ($archiveMode ?? 'active') === 'archive' ? 'btn-secondary active text-white' : 'btn-outline-secondary' }} px-4 fw-semibold" 
                                    id="filterArchive" aria-pressed="{{ ($archiveMode ?? 'active') === 'archive' ? 'true' : 'false' }}">
                                <i class="bi bi-archive me-1"></i> Archive
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
            @if(count($attendanceRecords) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Session Time</th>
                                <th>Venue</th>
                                <th>Group</th>
                                <th>Topic</th>
                                <th>Faculty</th>
                                <th>Attendance Status</th>
                                <th>Duty Type</th>
                                <th>Exemption</th>
                                <th>Document/Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attendanceRecords as $record)
                                <tr>
                                    <td>{{ $record['date'] }}</td>
                                    <td>{{ $record['session_time'] }}</td>
                                    <td>{{ $record['venue'] }}</td>
                                    <td>{{ $record['group'] }}</td>
                                    <td>{{ $record['topic'] }}</td>
                                    <td>{{ $record['faculty'] }}</td>
                                    <td>
                                        @if($record['attendance_status'] == 'Present')
                                            <span class="badge bg-success">{{ $record['attendance_status'] }}</span>
                                        @elseif($record['attendance_status'] == 'Late')
                                            <span class="badge bg-warning">{{ $record['attendance_status'] }}</span>
                                        @elseif($record['attendance_status'] == 'Absent')
                                            <span class="badge bg-danger">{{ $record['attendance_status'] }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $record['attendance_status'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record['duty_type'])
                                            <span class="badge bg-info">{{ $record['duty_type'] }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record['exemption_type'])
                                            <span class="badge bg-primary">{{ $record['exemption_type'] }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record['exemption_document'])
                                            <a href="{{ asset('storage/' . $record['exemption_document']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-file"></i> View Document
                                            </a>
                                            @if($record['exemption_comment'])
                                                <br><small class="text-muted mt-1 d-block">{{ Str::limit($record['exemption_comment'], 50) }}</small>
                                            @endif
                                        @elseif($record['exemption_comment'])
                                            <small class="text-muted">{{ $record['exemption_comment'] }}</small>
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
                <div class="alert alert-info text-center">
                    <p class="mb-0">No attendance records found for the selected filters.</p>
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
