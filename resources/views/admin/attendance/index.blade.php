@extends('admin.layouts.master')

@section('title', 'Attendance')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}?v={{ @filemtime(public_path('css/attendance-admin.css')) ?: time() }}">
<style>
    #attendanceTable a.att-fingerprint-btn,
    #attendanceTable_wrapper a.att-fingerprint-btn {
        width: 2.25rem !important;
        height: 2.25rem !important;
        padding: 0 !important;
        border-radius: 50% !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: #004a93 !important;
        color: #fff !important;
        text-decoration: none !important;
        border: 0 !important;
    }
    #attendanceTable a.att-fingerprint-btn--marked,
    #attendanceTable_wrapper a.att-fingerprint-btn--marked {
        background: #039855 !important;
    }
    #attendanceTable a.att-fingerprint-btn .bi-fingerprint,
    #attendanceTable_wrapper a.att-fingerprint-btn .bi-fingerprint {
        font-size: 1.2rem !important;
        line-height: 1 !important;
        color: #fff !important;
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid attendance-master-page py-3 px-3 px-lg-4">
    <x-breadcrum title="Attendance" />

    <x-session_message />

    <div class="card attendance-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar attendance-dt-toolbar w-100">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                        <label for="programme" class="visually-hidden">Course Name</label>
                        <select name="course_master_pk" id="programme" class="form-select form-select-sm att-filter-select" required aria-label="Course Name">
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

                    <div class="programme-dt-filter-select">
                        <label for="attendance_type_ui" class="visually-hidden">Attendance Type</label>
                        <select id="attendance_type_ui" class="form-select form-select-sm att-filter-select" aria-label="Attendance Type">
                            <option value="full_day" selected>Full Day</option>
                            <option value="manual">Manual</option>
                            <option value="normal">Normal</option>
                        </select>
                    </div>

                    <div class="visually-hidden" aria-hidden="true">
                        <input class="form-check-input" type="radio" name="attendance_type" id="full_day" value="full_day" checked>
                        <input class="form-check-input" type="radio" name="attendance_type" id="manual" value="manual">
                        <input class="form-check-input" type="radio" name="attendance_type" id="normal" value="normal">
                    </div>

                    <div class="programme-dt-filter-select att-time-period-filter position-relative">
                        <input type="hidden" id="from_date" name="from_date" value="{{ date('Y-m-d') }}">
                        <input type="hidden" id="to_date" name="to_date" value="{{ date('Y-m-d') }}">
                        <label for="att_time_period_picker" class="visually-hidden">Time Period</label>
                        <input type="text"
                            id="att_time_period_picker"
                            class="form-control form-control-sm att-time-period-input"
                            placeholder="Time Period"
                            value=""
                            readonly
                            autocomplete="off"
                            aria-label="Time period">
                        <i class="bi bi-calendar3 att-time-period-icon" aria-hidden="true"></i>
                    </div>

                    <div class="programme-dt-filter-select" id="normal_session_container" style="display:none;">
                        <label for="session" class="visually-hidden">Normal Session</label>
                        <select name="session" id="session" class="form-select form-select-sm att-filter-select" aria-label="Normal Session">
                            <option value="">Normal Session</option>
                            @foreach($sessions as $session)
                            <option value="{{ $session['pk'] }}">{{ $session['shift_name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select" id="manual_session_container" style="display:none;">
                        <label for="manual_session" class="visually-hidden">Manual Session</label>
                        <select name="manual_session" id="manual_session" class="form-select form-select-sm att-filter-select" aria-label="Manual Session">
                            <option value="">Manual Session</option>
                            @foreach($maunalSessions as $maunalSession)
                            <option value="{{ $maunalSession['class_session'] }}">
                                {{ $maunalSession['class_session'] }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset flex-shrink-0" id="resetAttendance">
                        Reset Filters
                    </button>
                </div>

                <div class="att-table-search-slot ms-xl-auto flex-shrink-0">
                    <div class="dropdown">
                        <button type="button"
                            class="btn att-search-trigger"
                            id="attSearchTrigger"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            aria-expanded="false"
                            aria-label="Search table">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3 att-search-menu">
                            <label class="form-label small text-secondary mb-2" for="attDtSearchInput">Search</label>
                            <div id="attDtSearch" class="att-dt-search-host" data-dt-search-for="attendanceTable"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="attendanceTableCard" class="programme-dt-panel attendance-dt-panel">
                <div id="attendanceTableDiv" class="table-responsive attendance-dt-scroll">
                    <table id="attendanceTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table attendance-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap att-col-sno">S. No.</th>
                                <th scope="col" class="att-col-topic">Topic</th>
                                <th scope="col" class="text-nowrap">Date</th>
                                <th scope="col" class="text-nowrap">Session</th>
                                <th scope="col">Venue</th>
                                <th scope="col">Group</th>
                                <th scope="col" class="text-nowrap">Course Name</th>
                                <th scope="col">Faculty</th>
                                <th scope="col" class="text-center text-nowrap att-col-action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(hasRole('Super Admin') || hasRole('Training Induction Admin'))
                            <tr id="defaultMessageRow">
                                <td colspan="9" class="text-center attendance-dt-empty">
                                    <div class="py-3 text-secondary">
                                        <i class="bi bi-table d-block mb-3 att-empty-icon" aria-hidden="true"></i>
                                        <p class="mb-0 fw-medium">Apply filters to mark attendance</p>
                                        <p class="mb-0 small text-muted mt-1">Choose course, dates, and attendance type to load sessions.</p>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @if(hasRole('Internal Faculty'))
                            <tr id="defaultMessageRow">
                                <td colspan="9" class="text-center attendance-dt-empty">
                                    <div class="py-3 text-secondary">
                                        <i class="bi bi-table d-block mb-3 att-empty-icon" aria-hidden="true"></i>
                                        <p class="mb-0 fw-medium">Apply filters to view attendance</p>
                                        <p class="mb-0 small text-muted mt-1">Select the required details to display attendance records.</p>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div id="attDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="attendanceTable"></div>
            </div>
        </div>
    </div>

    <div id="attendanceCustomTooltip" role="tooltip" aria-hidden="true"></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
(function () {
    function formatYmd(date) {
        if (!date) return '';
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function syncAttendanceTypeUi() {
        var val = $('input[name="attendance_type"]:checked').val() || 'full_day';
        $('#attendance_type_ui').val(val);
    }

    function setAttendanceTypeFromUi(val) {
        $('input[name="attendance_type"][value="' + val + '"]').prop('checked', true).trigger('change');
    }

    function reloadAttendanceIfReady() {
        if ($.fn.DataTable.isDataTable('#attendanceTable')) {
            attendanceTable.ajax.reload();
            return;
        }
        if ($('#programme').val() && $('#from_date').val() && $('#to_date').val()) {
            performAttendanceSearch();
        }
    }

  window.attTimePeriodPicker = null;

    $(document).ready(function () {
        var today = new Date();
        var todayStr = formatYmd(today);

        if (!$('#from_date').val()) $('#from_date').val(todayStr);
        if (!$('#to_date').val()) $('#to_date').val(todayStr);

        if (typeof flatpickr !== 'undefined') {
            window.attTimePeriodPicker = flatpickr('#att_time_period_picker', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd.m.Y',
                defaultDate: [today, today],
                showMonths: 2,
                locale: { rangeSeparator: ' to ' },
                onChange: function (selectedDates) {
                    if (selectedDates[0]) {
                        $('#from_date').val(formatYmd(selectedDates[0])).trigger('change');
                    }
                    if (selectedDates.length > 1 && selectedDates[1]) {
                        $('#to_date').val(formatYmd(selectedDates[1])).trigger('change');
                    } else if (selectedDates[0]) {
                        $('#to_date').val(formatYmd(selectedDates[0])).trigger('change');
                    }
                }
            });
        }

        syncAttendanceTypeUi();

        $('#attendance_type_ui').on('change', function () {
            var val = $(this).val() || 'full_day';
            setAttendanceTypeFromUi(val);
        });

        $(document).on('change', 'input[name="attendance_type"]', function () {
            syncAttendanceTypeUi();
            reloadAttendanceIfReady();
        });

        $(document).on('change', '#session, #manual_session', function () {
            reloadAttendanceIfReady();
        });

        $(document).on('click', '#resetAttendance', function () {
            setTimeout(function () {
                syncAttendanceTypeUi();
                $('#attendance_type_ui').val('full_day');
                var t = new Date();
                var ts = formatYmd(t);
                $('#from_date').val(ts);
                $('#to_date').val(ts);
                if (window.attTimePeriodPicker) {
                    window.attTimePeriodPicker.setDate([t, t], true);
                }
            }, 0);
        });

        if ($('#programme').val() && $('#from_date').val() && $('#to_date').val()) {
            performAttendanceSearch();
        }
    });
})();

    window.enhanceAttendanceFingerprintActions = function () {
        $('#attendanceTable tbody td:last-child a, #attendanceTable_wrapper tbody td:last-child a').each(function () {
            var $link = $(this);
            if ($link.hasClass('att-fingerprint-btn') && $link.find('.bi-fingerprint').length) {
                return;
            }
            var label = $.trim($link.text()) || $link.attr('aria-label') || $link.attr('title') || 'Open attendance';
            var href = $link.attr('href') || '#';
            var isMarked = $link.hasClass('btn-success');
            var stateClass = isMarked ? 'att-fingerprint-btn--marked' : 'att-fingerprint-btn--default';
            $link.attr('href', href)
                .attr('title', label)
                .attr('aria-label', label)
                .removeClass('btn btn-primary btn-success btn-sm 1')
                .addClass('att-fingerprint-btn ' + stateClass)
                .html('<i class="bi bi-fingerprint att-fingerprint-bi" aria-hidden="true"></i><span class="visually-hidden">' + $('<div>').text(label).html() + '</span>');
        });
    };

    $(document).on('draw.dt', '#attendanceTable', window.enhanceAttendanceFingerprintActions);
</script>

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
            tooltip.style.left = (x - rect.width - offset) + 'px';
        }
        if (rect.bottom > window.innerHeight - 8) {
            tooltip.style.top = (y - rect.height - offset) + 'px';
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
        if (!visible || rafId) return;
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
@endpush
