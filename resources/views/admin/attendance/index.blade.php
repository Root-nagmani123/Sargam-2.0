@extends('admin.layouts.master')

@section('title', 'Attendance')

@section('setup_content')
<style>
/* Responsive dropdown options - viewport-based height on small screens */
@media (max-width: 575.98px) {
    #programmeOptionsList { max-height: min(220px, 45vh) !important; }
}
/* Smooth transitions and hover states */
.attendance-index .form-control, .attendance-index .form-select, .attendance-index .btn { transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out; }
.attendance-index .programme-option:hover { background-color: var(--bs-primary-bg-subtle); }
</style>
<div class="container-fluid attendance-index px-2 px-sm-3 px-lg-4">
    <x-breadcrum title="Attendance" />

    <div class="card border-0 shadow-sm border-start border-4 border-primary overflow-hidden rounded-3">
        <div class="card-body p-3 p-sm-4 p-lg-5">

            <!-- Header -->
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 gap-md-3 mb-3 mb-md-4">
                <h2 class="h5 mb-0 fw-bold text-primary text-uppercase fs-6 fs-md-5 d-flex align-items-center gap-2">
                    <span class="rounded-2 bg-primary bg-opacity-10 p-2 d-inline-flex">
                        <i class="bi bi-calendar-check text-primary" aria-hidden="true"></i>
                    </span>
                    Attendance
                </h2>
            </div>

            <hr class="my-3 my-md-4 border-secondary opacity-25">

            <!-- Filters -->
            <div class="row g-3 g-md-4 mb-3 mb-md-4">
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" id="programme-select-wrapper">
                    <label for="programmeDropdownBtn" class="form-label fw-semibold small text-body-secondary">
                        <i class="bi bi-mortarboard-fill me-1 opacity-75" aria-hidden="true"></i>Course Name
                    </label>
                    <input type="hidden" name="course_master_pk" id="programme" value="{{ count($courseMasters) === 1 ? $courseMasters[0]['pk'] : '' }}" required>
                    <div class="dropdown">
                        <button type="button" class="form-control form-control-sm text-start d-flex align-items-center justify-content-between border focus-ring focus-ring-primary rounded-2" id="programmeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="listbox">
                            <span id="programmeDisplay" class="text-truncate {{ count($courseMasters) === 1 ? '' : 'text-body-secondary' }}">{{ count($courseMasters) === 1 ? $courseMasters[0]['course_name'] : 'Search or select course...' }}</span>
                            <i class="bi bi-chevron-down ms-2 flex-shrink-0 opacity-50" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu w-100 shadow-lg rounded-2 border-0 py-0 overflow-hidden" id="programmeDropdownMenu">
                            <div class="p-2 bg-body-tertiary">
                                <input type="text" class="form-control form-control-sm rounded-pill border-0 focus-ring focus-ring-primary" id="programmeSearch" placeholder="Type to search..." autocomplete="off">
                            </div>
                            <div class="dropdown-divider my-0"></div>
                            <ul class="list-unstyled mb-0 py-2 overflow-auto" id="programmeOptionsList" role="listbox" style="max-height: 220px;">
                                @foreach($courseMasters as $course)
                                <li class="dropdown-item programme-option py-2 px-3 rounded-1" role="option" data-value="{{ $course['pk'] }}" tabindex="-1">{{ $course['course_name'] }}</li>
                                @endforeach
                            </ul>
                            <div class="dropdown-item text-body-secondary small text-center py-3 d-none" id="programmeNoResults">No course found</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <label for="from_date" class="form-label fw-semibold small text-body-secondary">From Date</label>
                    <input type="date" class="form-control form-control-sm border focus-ring focus-ring-primary rounded-2" id="from_date" name="from_date" value="{{ date('Y-m-d') }}">
                </div>

                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <label for="to_date" class="form-label fw-semibold small text-body-secondary">To Date</label>
                    <input type="date" class="form-control form-control-sm border focus-ring focus-ring-primary rounded-2" id="to_date" name="to_date" value="{{ date('Y-m-d') }}">
                </div>

                <div class="col-12 col-lg-4 col-xl-3">
                    <label class="form-label fw-semibold small text-body-secondary d-block mb-2">Attendance Type</label>
                    <div class="btn-group btn-group-sm w-100 w-sm-auto flex-sm-nowrap rounded-2 overflow-hidden border" role="group" aria-label="Attendance type">
                        <input type="radio" class="btn-check" name="attendance_type" id="full_day" value="full_day" checked autocomplete="off">
                        <label class="btn btn-outline-primary rounded-0 border-0 flex-fill flex-sm-grow-0" for="full_day">Full Day</label>
                        <input type="radio" class="btn-check" name="attendance_type" id="manual" value="manual" autocomplete="off">
                        <label class="btn btn-outline-primary rounded-0 border-0 flex-fill flex-sm-grow-0" for="manual">Manual</label>
                        <input type="radio" class="btn-check" name="attendance_type" id="normal" value="normal" autocomplete="off">
                        <label class="btn btn-outline-primary rounded-0 border-0 flex-fill flex-sm-grow-0" for="normal">Normal</label>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 d-none" id="normal_session_container">
                    <label for="session" class="form-label fw-semibold small text-body-secondary">Normal Session</label>
                    <select name="session" id="session" class="form-select form-select-sm border focus-ring focus-ring-primary rounded-2 select2">
                        <option value="">Select Session</option>
                        @foreach($sessions as $session)
                        <option value="{{ $session['pk'] }}">{{ $session['shift_name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 d-none" id="manual_session_container">
                    <label for="manual_session" class="form-label fw-semibold small text-body-secondary">Manual Session</label>
                    <select name="manual_session" id="manual_session" class="form-select form-select-sm border focus-ring focus-ring-primary rounded-2 select2">
                        <option value="">Select Session</option>
                        @foreach($maunalSessions as $maunalSession)
                        <option value="{{ $maunalSession['class_session'] }}">
                            {{ $maunalSession['class_session'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="my-3 my-md-4 border-secondary opacity-25">

            <!-- Actions -->
            <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 mb-md-4">
                <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-2 focus-ring focus-ring-secondary" id="resetAttendance" type="button">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                    <span>Reset</span>
                </button>
            </div>

            <!-- Table -->
            <div id="attendanceTableCard" class="rounded-3 border overflow-hidden">
                <div class="table-responsive overflow-x-auto" id="attendanceTableDiv">
                    <table id="attendanceTable" class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-nowrap fw-semibold">#</th>
                                <th scope="col" class="text-nowrap fw-semibold">Course Name</th>
                                <th scope="col" class="text-nowrap fw-semibold">Date</th>
                                <th scope="col" class="text-nowrap fw-semibold">Session</th>
                                <th scope="col" class="text-nowrap fw-semibold">Venue</th>
                                <th scope="col" class="text-nowrap fw-semibold">Group</th>
                                <th scope="col" class="text-nowrap fw-semibold">Topic</th>
                                <th scope="col" class="text-nowrap fw-semibold">Faculty</th>
                                <th scope="col" class="text-nowrap fw-semibold text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(hasRole('Admin') || hasRole('Training-Induction'))
                            <tr id="defaultMessageRow">
                                <td colspan="9" class="text-center py-5 px-3">
                                    <div class="text-body-secondary">
                                        <span class="d-inline-flex rounded-circle bg-body-tertiary p-3 mb-3">
                                            <i class="bi bi-funnel fs-4 opacity-75" aria-hidden="true"></i>
                                        </span>
                                        <p class="mb-0 fw-medium">Apply filter to mark attendance</p>
                                        <p class="mb-0 small opacity-75 mt-1">Select a course and date range above</p>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @if(hasRole('Internal Faculty'))
                            <tr id="defaultMessageRow">
                                <td colspan="9" class="text-center py-5 px-3">
                                    <div class="text-body-secondary">
                                        <span class="d-inline-flex rounded-circle bg-body-tertiary p-3 mb-3">
                                            <i class="bi bi-funnel fs-4 opacity-75" aria-hidden="true"></i>
                                        </span>
                                        <p class="mb-0 fw-medium">Apply filter to see attendance</p>
                                        <p class="mb-0 small opacity-75 mt-1">Select a course and date range above</p>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Course Name – Searchable dropdown (no Select2)
    (function initCourseSearchDropdown() {
        var $hidden = $('#programme');
        var $btn = $('#programmeDropdownBtn');
        var $display = $('#programmeDisplay');
        var $search = $('#programmeSearch');
        var $list = $('#programmeOptionsList');
        var $noResults = $('#programmeNoResults');
        var $menu = $('#programmeDropdownMenu');
        var placeholder = 'Search or select course...';

        function filterOptions() {
            var q = ($search.val() || '').toLowerCase();
            var visible = 0;
            $list.find('.programme-option').each(function() {
                var text = $(this).text().toLowerCase();
                var match = !q || text.indexOf(q) >= 0;
                $(this).toggleClass('d-none', !match);
                if (match) visible++;
            });
            $noResults.toggleClass('d-none', visible > 0);
        }

        function selectOption(val, text) {
            $hidden.val(val || '');
            $display.text(text || placeholder);
            $display.toggleClass('text-body-secondary', !val);
            $search.val('');
            filterOptions();
            $hidden.trigger('change');
        }

        $search.on('input', filterOptions);
        $search.on('keydown', function(e) {
            if (e.key === 'Escape') {
                var dd = bootstrap.Dropdown.getInstance($btn[0]);
                if (dd) dd.hide();
                $search.blur();
            }
        });

        $list.on('click', '.programme-option', function(e) {
            e.preventDefault();
            selectOption($(this).data('value'), $(this).text());
            var dd = bootstrap.Dropdown.getInstance($btn[0]);
            if (dd) dd.hide();
        });

        $btn.on('shown.bs.dropdown', function() {
            $search.focus();
        });
        $btn.on('hidden.bs.dropdown', function() {
            $search.val('');
            filterOptions();
        });

        $(document).on('click', '#resetAttendance', function() {
            selectOption('', placeholder);
        });
    })();

    // Set today's date if not already set
    var today = new Date().toISOString().split('T')[0];
    if (!$('#from_date').val()) $('#from_date').val(today);
    if (!$('#to_date').val()) $('#to_date').val(today);

    // Auto-trigger search on page load if dates are set
    var fromDate = $('#from_date').val(), toDate = $('#to_date').val();
    if (fromDate && toDate) {
        setTimeout(function() { performAttendanceSearch(); }, 100);
    }
});
</script>
@endsection