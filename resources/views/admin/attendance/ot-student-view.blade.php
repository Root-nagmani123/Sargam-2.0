@extends(hasRole('Student-OT') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', 'Academic TimeTable - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<div class="container-fluid">
     @if(hasRole('Training') || hasRole('Admin') ||  hasRole('Training-MCTP') || hasRole('IST'))
    <x-breadcrum title="My Attendance Record" />
    <x-session_message />
    @endif

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
                    'student_pk' => $student_pk]) }}" id="filterForm">

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


                <div class="row g-4">
                    <div class="coursehide col-lg-4 col-md-6">
                        <label for="filter_course" class="form-label">Course </label>
                        <select class="form-control" id="filter_course" name="filter_course">
                            <option value="">All Courses</option>
                            @foreach($archivedCourses as $archivedCourse)
                            <option value="{{ $archivedCourse->pk }}">
                                {{ $archivedCourse->course_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="archive-column col-lg-4 col-md-6">
                        <label for="filter_date" class="form-label">
                            <span>Date</span>
                        </label>
                        <input type="date" class="form-control" id="filter_date" name="filter_date"
                            value="{{ $filterDate ?? '' }}" max="{{ date('Y-m-d') }}"
                            aria-label="Filter attendance records by date" aria-describedby="date-help">
                        <small id="date-help" class="form-text text-muted mt-1 d-block">
                            Select a specific date to view attendance
                        </small>
                    </div>
                    <div class="archive-column col-lg-4 col-md-6">
                        <label for="filter_session_time" class="form-label">
                            <i class="bi bi-clock-fill text-primary" aria-hidden="true"></i>
                            <span>Session Time</span>
                        </label>
                        <select class="form-control" id="filter_session_time" name="filter_session_time">
                            <option value="">All Sessions</option>
                            @foreach($maunalSessions as $manualSession)
                            <option value="{{ $manualSession->class_session }}">
                                {{ $manualSession->class_session }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-12 d-flex align-items-end gap-3 mt-4 no-print">
                        <button type="button" class="btn btn-outline-primary flex-grow-1" id="clearFilters"
                            aria-label="Clear all filters">
                            <span>Clear Filters</span>
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>



    {{-- Enhanced Attendance Details Table --}}
    <div class="card" style="border-left: 4px solid #004a93;">
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
        },
        columns: [{
                data: 'DT_RowIndex',
                orderable: false,
                searchable: false // ⭐ IMPORTANT
            },
            {
                data: 'date',
                name: 'date'
            },
            {
                data: 'venue',
                name: 'venue'
            },
            {
                data: 'group',
                name: 'group'
            },
            {
                data: 'topic',
                name: 'topic'
            },
            {
                data: 'faculty',
                name: 'faculty'
            },
            {
                data: 'attendance_status',
                name: 'attendance_status'
            },
            {
                data: 'duty_type',
                name: 'duty_type'
            },
            {
                data: 'exemption_type',
                name: 'exemption_type'
            }
        ]
    });


    $('#filter_date').on('change', function() {
        var date = $('#filter_date').val();
        table.ajax.reload();
    });

    $('#filterArchive_active').on('click', function() {
        let archive_mode = $(this).attr('aria-pressed');
        //   alert(archive_mode);
        table.ajax.reload();
    });

    $('#filterArchive').on('click', function() {
        let archive_mode = $(this).attr('aria-pressed');
        //    alert(archive_mode);
        table.ajax.reload();
    });

    $('#filter_course').on('change', function() {
        //alert('sfsdf');
        let course_pk = $('#filter_course').val();
        //    alert(archive_mode);
        table.ajax.reload();
    });

    $('#filter_session_time').on('change', function() {
        //alert('sfsdf');
        let filter_session_time = $('#filter_session_time').val();
        //    alert(archive_mode);
        table.ajax.reload();
    });

    $('#clearFilters').on('click', function() {
        // Reset filters
        $('#filter_date').val('');
        $('#filter_session_time').val('');
        $('#filter_course').val('');
        $('#archive_mode').val('');

        // Reload full data (preloaded AJAX)
        table.ajax.reload(null, true); // true = go to page 1
    });

});


$(document).ready(function() {
    // Initialize state
    $('.coursehide').hide();
    $('#filterArchive_active').addClass('bg-primary text-white shadow-sm');

    // Archive button click handler
    $('#filterArchive').on('click', function() {
        let isArchiveActive = $(this).attr('aria-pressed');
        if (isArchiveActive == 'false') {
            // Update layout
            $('.archive-column')
                .removeClass('col-lg-5')
                .addClass('col-lg-4');

            // Update button states
            $('#filterArchive_active')
                .removeClass('bg-primary text-white shadow-sm')
                .attr('aria-pressed', 'false');
            $(this)
                .addClass('bg-primary text-white shadow-sm')
                .attr('aria-pressed', 'true');

            // Show course filter with animation
            $('.coursehide').slideDown(300);

            // Announce to screen readers
            announceToScreenReader('Switched to archived records view');
        }
    });

    // Active button click handler
    $('#filterArchive_active').on('click', function() {
        let isArchiveActive = $(this).attr('aria-pressed');
        if (isArchiveActive == 'false') {
            // Update layout
            $('.archive-column').removeClass('col-lg-4');

            // Hide course filter with animation
            $('.coursehide').slideUp(300);

            // Update button states
            $('#filterArchive')
                .removeClass('bg-primary text-white shadow-sm')
                .attr('aria-pressed', 'false');
            $(this)
                .addClass('bg-primary text-white shadow-sm')
                .attr('aria-pressed', 'true');

            // Announce to screen readers
            announceToScreenReader('Switched to active records view');
        }
    });

    // Accessibility: Announce messages to screen readers
    function announceToScreenReader(message) {
        let announcement = $('<div>', {
            'role': 'status',
            'aria-live': 'polite',
            'aria-atomic': 'true',
            'class': 'sr-only position-absolute',
            'text': message
        });
        $('body').append(announcement);
        setTimeout(function() {
            announcement.remove();
        }, 1000);
    }

    // Enhance select2 for accessibility
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