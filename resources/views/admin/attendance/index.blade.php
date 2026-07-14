@extends('admin.layouts.master')

@section('title', 'Attendance')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

@section('setup_content')
<style>
/* Choices + Bootstrap: form-select adds BS chevron; Choices adds its own — drop BS background only. */
.attendance-choices-bootstrap .choices__inner.form-select {
    background-image: none !important;
}

.attendance-choices-bootstrap .choices__list--dropdown.dropdown-menu .choices__list,
.attendance-choices-bootstrap .choices__list[aria-expanded].dropdown-menu .choices__list {
    max-height: 280px;
}

/* Course column: short label, full name via custom tooltip */
#attendanceTable .attendance-course-cell {
    cursor: help;
    text-decoration: underline dotted;
    text-underline-offset: 0.15em;
}

/* Attendance DataTable chrome (search / pagination / showing) */
.attendance-page #attendanceTable_wrapper .att-search .dataTables_filter { margin: 0; }
.attendance-page #attendanceTable_wrapper .att-search input {
    min-width: 260px;
    max-width: 100%;
}
.attendance-page #attendanceTable_wrapper .att-count .dataTables_info,
.attendance-page #attendanceTable_wrapper .att-count .dataTables_length { color: #667085; font-size: 0.875rem; padding: 0; margin: 0; }
.attendance-page #attendanceTable_wrapper .att-count .dataTables_length label { margin: 0; display: inline-flex; align-items: center; gap: 0.4rem; }
.attendance-page #attendanceTable_wrapper .att-count .dataTables_length select { width: auto; display: inline-block; }
.attendance-page #attendanceTable_wrapper .dataTables_paginate { margin: 0; }
.attendance-page #attendanceTable_wrapper .dataTables_paginate .pagination { margin: 0; }

/* Custom tooltip (course full name) */
#attendanceCustomTooltip {
    position: fixed;
    z-index: 9999;
    display: none;
    pointer-events: none;
    max-width: 360px;
    background: rgba(0, 0, 0, 0.88);
    color: #fff;
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 12.5px;
    line-height: 1.25;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
    word-break: break-word;
    white-space: normal;
}

/* Edit-attendance action = stacked pencil icon + label (blue link) */
.attendance-page td .btn-link.text-primary {
    --bs-btn-color: #004a93;
    --bs-btn-hover-color: #003c78;
    min-width: 88px;
}

.attendance-page td .btn-link.text-primary:hover .small {
    text-decoration: underline;
}

/* Download CSV button (right of the Active/Archived tabs) */
.attendance-page .attendance-download-btn {
    height: 40px;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0 1.1rem;
    font-size: 0.9375rem;
    font-weight: 500;
    color: #004a93;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    background: #fff;
}

.attendance-page .attendance-download-btn:hover {
    background: #f2f7fc;
    border-color: #004a93;
    color: #004a93;
}

.attendance-page .attendance-download-btn i {
    font-size: 1rem;
    line-height: 1;
}

/* Time Period date-range input */
.attendance-page .attendance-daterange-wrap {
    position: relative;
    width: 215px;
}

.attendance-page .attendance-daterange-input {
    width: 215px;
    min-height: 40px;
    padding: 0.5rem 0.875rem 0.5rem 2.25rem;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    font-size: 0.9375rem;
    color: #344054;
    cursor: pointer;
    background: #fff;
}

.attendance-page .attendance-daterange-input::placeholder {
    color: #667085;
}

.attendance-page .attendance-daterange-input:focus {
    border-color: #004a93;
    box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12);
    outline: 0;
}

.attendance-page .attendance-daterange-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #667085;
    font-size: 1rem;
    pointer-events: none;
}

/* Smaller screens: sticky header while scrolling the table body horizontally. */
@media (max-width: 767.98px) {
    #attendanceTableDiv thead th {
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .attendance-page .attendance-daterange-wrap,
    .attendance-page .attendance-daterange-input {
        width: 100%;
    }
}
</style>


<div class="container-fluid attendance-page py-3">
    <x-breadcrum title="Attendance" />

    {{-- Active / Archived courses (same split as Course Master) --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs attendance-status-tabs bg-white mb-0" role="group" aria-label="Filter courses by status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                    id="attFilterActive" data-att-status="active" aria-pressed="true" aria-current="true">Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                    id="attFilterArchive" data-att-status="archive" aria-pressed="false">Archived</button>
            </li>
        </ul>
        <button type="button" id="attendanceDownload" class="btn attendance-download-btn border-0">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Filter toolbar (programme-dt design system) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar attendance-choices-bootstrap">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    {{-- Course --}}
                    <div class="programme-dt-filter-select">
                        <select name="course_master_pk" id="programme" class="form-select js-attendance-choice" required>
                            <option value="">Course Name</option>
                            @foreach($courseMasters as $course)
                            @php
                                $courseFullName = trim((string) ($course['course_name'] ?? ''));
                                $courseShortName = trim((string) ($course['couse_short_name'] ?? $course['course_short_name'] ?? ''));
                                $courseLabel = $courseShortName !== '' ? $courseShortName : $courseFullName;
                            @endphp
                            <option value="{{ $course['pk'] }}"
                                title="{{ $courseFullName !== '' ? $courseFullName : $courseLabel }}"
                                {{ count($courseMasters) === 1 ? 'selected' : '' }}>
                                {{ $courseLabel }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Attendance Type --}}
                    <div class="programme-dt-filter-select">
                        <select id="attendance_type" class="form-select js-attendance-choice" aria-label="Attendance Type">
                            <option value="full_day" selected>Full Day</option>
                            <option value="manual">Manual</option>
                            <option value="normal">Normal</option>
                        </select>
                    </div>

                    {{-- Time Period (date-range picker) --}}
                    <div class="attendance-daterange-wrap">
                        <i class="bi bi-calendar3 attendance-daterange-icon" aria-hidden="true"></i>
                        <input type="text" id="time_period" class="form-control attendance-daterange-input"
                            placeholder="Time Period" autocomplete="off" readonly aria-label="Filter by date range">
                    </div>

                    {{-- Normal Session (conditional) --}}
                    <div class="programme-dt-filter-select" id="normal_session_container" style="display:none;">
                        <select name="session" id="session" class="form-select js-attendance-choice" aria-label="Normal Session">
                            <option value="">Select Session</option>
                            @foreach($sessions as $session)
                            <option value="{{ $session['pk'] }}">{{ $session['shift_name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Manual Session (conditional) --}}
                    <div class="programme-dt-filter-select" id="manual_session_container" style="display:none;">
                        <select name="manual_session" id="manual_session" class="form-select js-attendance-choice" aria-label="Manual Session">
                            <option value="">Select Session</option>
                            @foreach($maunalSessions as $maunalSession)
                            <option value="{{ $maunalSession['class_session'] }}">
                                {{ $maunalSession['class_session'] }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="resetAttendance">
                        Reset Filters
                    </button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnAttendanceColumns"
                        data-bs-toggle="modal" data-bs-target="#attendanceColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="attendanceDtSearch" class="programme-dt-search" data-dt-search-for="attendanceTable"></div>
                </div>
            </div>

            <!-- Reset Button -->
            <div class="text-end mb-3 mb-lg-4">
                <button class="btn btn-outline-secondary px-4 py-2 shadow-sm d-inline-flex align-items-center rounded-pill"
                    id="resetAttendance" type="button">
                    <span class="material-symbols-rounded me-2 fs-6">refresh</span>
                    Reset
                </button>
            </div>

            <div id="attendanceTableCard">
                <div class="programme-dt-panel">
                <div id="attendanceTableDiv" class="table-responsive">
                    <table id="attendanceTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Topic</th>
                                <th>Date</th>
                                <th>Session</th>
                                <th>Venue</th>
                                <th>Group</th>
                                <th>Course Name</th>
                                <th>Faculty</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(hasRole('Super Admin') || hasRole('Training Induction Admin'))
                            <tr id="defaultMessageRow">
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-muted small">
                                        <p class="mb-1 fs-6 fw-semibold text-secondary">Apply filters to mark attendance.</p>
                                        <p class="mb-0">Choose course, time period, and attendance type to load sessions.</p>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @if(hasRole('Internal Faculty'))
                            <tr id="defaultMessageRow">
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-muted small">
                                        <p class="mb-1 fs-6 fw-semibold text-secondary">Apply filters to view attendance.</p>
                                        <p class="mb-0">Select the required details to display attendance records.</p>
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

<div id="attendanceCustomTooltip" role="tooltip" aria-hidden="true"></div>

{{-- Column Visibility Modal --}}
<div class="modal fade" id="attendanceColumnVisibilityModal" tabindex="-1" aria-labelledby="attendanceColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="attendanceColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="attendanceColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

{{-- Active / Archived course tabs: swap the Course dropdown between the
     running and the ended course sets. --}}
<script>
$(function () {
    var activeCourses = @json($courseMasters ?? []);
    var archivedCourses = @json($archivedCourseMasters ?? []);
    var el = document.getElementById('programme');
    var pills = document.querySelectorAll('.attendance-status-tabs [data-att-status]');
    if (!el || !pills.length) return;

    function courseLabel(c) {
        var full = (c.course_name || '').toString().trim();
        var short = (c.couse_short_name || c.course_short_name || '').toString().trim();
        return short !== '' ? short : full;
    }
    function buildChoices(list) {
        var out = [{ value: '', label: 'Course Name', selected: true }];
        (list || []).forEach(function (c) {
            out.push({ value: String(c.pk), label: courseLabel(c) });
        });
        return out;
    }
    function applyList(list) {
        var inst = el._choicesInstance;
        if (inst && typeof inst.setChoices === 'function') {
            inst.clearStore();
            inst.setChoices(buildChoices(list), 'value', 'label', true);
            inst.setChoiceByValue('');
        } else {
            // Native <select> fallback (Choices not initialised).
            el.innerHTML = buildChoices(list).map(function (c) {
                return '<option value="' + c.value + '">' + c.label + '</option>';
            }).join('');
            el.value = '';
        }
        // Reset to placeholder — no course selected, so no search runs yet.
        $(el).trigger('change');
    }

    pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            if (this.classList.contains('active')) return;
            pills.forEach(function (p) {
                p.classList.remove('active');
                p.setAttribute('aria-pressed', 'false');
                p.removeAttribute('aria-current');
            });
            this.classList.add('active');
            this.setAttribute('aria-pressed', 'true');
            this.setAttribute('aria-current', 'true');
            applyList(this.dataset.attStatus === 'archive' ? archivedCourses : activeCourses);
        });
    });
});
</script>

{{-- Choices init for all filter selects --}}
<script>
$(document).ready(function() {
    if (typeof Choices !== 'undefined') {
        var attendanceChoiceOpts = {
            searchEnabled: true,
            shouldSort: false,
            itemSelectText: '',
            allowHTML: false,
            classNames: {
                containerOuter: ['choices', 'w-100'],
                containerInner: ['choices__inner', 'form-select'],
                input: ['choices__input', 'form-control', 'form-control-sm', 'border-0', 'shadow-none', 'my-1'],
                inputCloned: ['choices__input--cloned'],
                list: ['choices__list'],
                listItems: ['choices__list--multiple'],
                listSingle: ['choices__list--single'],
                listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
                item: ['choices__item', 'dropdown-item', 'rounded-0'],
                itemSelectable: ['choices__item--selectable'],
                itemDisabled: ['choices__item--disabled', 'disabled'],
                itemChoice: ['choices__item--choice'],
                description: ['choices__description', 'small', 'text-muted'],
                placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
                group: ['choices__group'],
                groupHeading: ['choices__heading', 'dropdown-header', 'text-uppercase', 'small'],
                button: ['choices__button'],
                activeState: ['is-active'],
                focusState: ['is-focused'],
                openState: ['is-open'],
                disabledState: ['is-disabled'],
                highlightedState: ['is-highlighted', 'active'],
                flippedState: ['is-flipped'],
                loadingState: ['is-loading'],
                invalidState: ['is-invalid'],
                notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small', 'py-2'],
                addChoice: ['choices__item--selectable', 'add-choice'],
                noResults: ['has-no-results'],
                noChoices: ['has-no-choices'],
            }
        };
        document.querySelectorAll('.js-attendance-choice').forEach(function(el) {
            if (el.dataset.choicesInitialized === 'true') {
                return;
            }
            // Short lists don't need a search box.
            var opts = attendanceChoiceOpts;
            if (el.id === 'attendance_type') {
                opts = $.extend({}, attendanceChoiceOpts, { searchEnabled: false });
            }
            el._choicesInstance = new Choices(el, opts);
            el.dataset.choicesInitialized = 'true';
        });
    }

    // Auto-trigger search on page load if dates are set (Time Period defaults to Today)
    let fromDate = $('#from_date').val();
    let toDate = $('#to_date').val();
    if (fromDate && toDate) {
        setTimeout(function() {
            performAttendanceSearch();
        }, 100);
    }
});
</script>

{{-- Time Period date-range picker → hidden from_date / to_date, then re-run the search --}}
<script>
$(function () {
    var $period = $('#time_period');
    if (!$period.length || typeof $period.daterangepicker !== 'function') {
        return;
    }

    function fmtDisplay(m) { return m.format('DD-MM-YYYY'); }

    // Reflect the current hidden range in the input text (no search).
    function paintInput(startM, endM) {
        $period.val(fmtDisplay(startM) + ' – ' + fmtDisplay(endM));
    }

    // Default = today (preserves the page's auto-search-on-load behaviour).
    var startToday = moment();
    $('#from_date').val(startToday.format('YYYY-MM-DD'));
    $('#to_date').val(startToday.format('YYYY-MM-DD'));
    paintInput(startToday, startToday);

    $period.daterangepicker({
        autoUpdateInput: false,
        opens: 'right',
        startDate: startToday,
        endDate: startToday,
        locale: {
            format: 'DD-MM-YYYY',
            separator: ' – ',
            cancelLabel: 'Clear',
            applyLabel: 'Apply',
        },
    });

    $period.on('apply.daterangepicker', function (ev, picker) {
        paintInput(picker.startDate, picker.endDate);
        $('#from_date').val(picker.startDate.format('YYYY-MM-DD'));
        $('#to_date').val(picker.endDate.format('YYYY-MM-DD'));
        if (typeof performAttendanceSearch === 'function') {
            performAttendanceSearch();
        }
    });

    // Clear → no date filter (all time), then re-run the search.
    $period.on('cancel.daterangepicker', function () {
        $period.val('');
        $('#from_date').val('');
        $('#to_date').val('');
        if (typeof performAttendanceSearch === 'function') {
            performAttendanceSearch();
        }
    });

    // The Reset button (custom.js) reseeds from/to to today and runs its own
    // single search — here we only sync the picker's display + selection.
    $(document).on('attendance:reset-daterange', function () {
        var t = moment();
        paintInput(t, t);
        $period.data('daterangepicker').setStartDate(t);
        $period.data('daterangepicker').setEndDate(t);
    });

    // Download the currently-filtered list as CSV (same filters the table uses).
    $('#attendanceDownload').on('click', function () {
        var params = $.param({
            programme: $('#programme').val() || '',
            from_date: $('#from_date').val() || '',
            to_date: $('#to_date').val() || '',
            attendance_type: $('#attendance_type').val() || '',
            session_value: ($('#attendance_type').val() === 'normal')
                ? ($('#session').val() || '')
                : ($('#attendance_type').val() === 'manual' ? ($('#manual_session').val() || '') : ''),
        });
        window.location.href = "{{ route('attendance.export_list') }}" + '?' + params;
    });
});
</script>

{{-- Column show / hide (rebuilt each time the server-side table is (re)created) --}}
<script>
$(function () {
    var storageKey = 'attendanceGrid:hiddenColumns:v1';

    function getHidden() {
        try {
            var raw = localStorage.getItem(storageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }
    function persist(arr) {
        try { localStorage.setItem(storageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupAttendanceColumns(dt) {
        if (!dt) return;
        var hidden = getHidden();

        dt.columns().every(function () {
            this.visible(hidden.indexOf(this.index()) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#attendanceColumnToggleGrid');
        if (!$grid.length) return;
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) return;

            var inputId = 'attcolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = getHidden();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                persist(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    $(document).on('init.dt', function (e, settings) {
        if (settings.nTable && settings.nTable.id === 'attendanceTable') {
            setupAttendanceColumns(new $.fn.dataTable.Api(settings));
        }
    });
});
</script>

{{-- Course full-name tooltip --}}
<script>
(function () {
    const tooltip = document.getElementById('attendanceCustomTooltip');
    if (!tooltip) return;

    let rafId = null;
    let visible = false;

    function placeTooltip(x, y) {
        const offset = 14;
        let left = x + offset;
        let top = y + offset;

        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';

        const rect = tooltip.getBoundingClientRect();
        if (rect.right > window.innerWidth - 8) {
            left = x - rect.width - offset;
            tooltip.style.left = left + 'px';
        }
        if (rect.bottom > window.innerHeight - 8) {
            top = y - rect.height - offset;
            tooltip.style.top = top + 'px';
        }
    }

    $(document).on('mouseenter', '.attendance-course-cell', function (e) {
        const content = this.getAttribute('data-tooltip');
        if (!content) return;

        tooltip.textContent = content;
        tooltip.style.display = 'block';
        tooltip.setAttribute('aria-hidden', 'false');
        visible = true;
        placeTooltip(e.clientX, e.clientY);
    });

    $(document).on('mousemove', '.attendance-course-cell', function (e) {
        if (!visible) return;
        if (rafId) return;

        rafId = requestAnimationFrame(function () {
            rafId = null;
            placeTooltip(e.clientX, e.clientY);
        });
    });

    $(document).on('mouseleave', '.attendance-course-cell', function () {
        tooltip.style.display = 'none';
        tooltip.setAttribute('aria-hidden', 'true');
        visible = false;
    });

    $(window).on('scroll', function () {
        if (!visible) return;
        tooltip.style.display = 'none';
        tooltip.setAttribute('aria-hidden', 'true');
        visible = false;
    });
})();
</script>
@endsection
