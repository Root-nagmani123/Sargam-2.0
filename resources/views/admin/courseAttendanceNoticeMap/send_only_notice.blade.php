@extends('admin.layouts.master')

@section('title', 'Direct Notice - Sargam | LBSNAA')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('css/send-only-notice-admin.css') }}?v={{ @filemtime(public_path('css/send-only-notice-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid son-master-page py-3 px-3 px-lg-4" id="sonNoticePage">
    <x-breadcrum title="Direct Notice" />

    <x-session_message />

    <div class="card son-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar son-dt-toolbar w-100">
                <div class="d-flex flex-wrap align-items-center gap-3 son-filters-group">
                    <span class="programme-dt-filters-label flex-shrink-0">Filters</span>

                    <div class="programme-dt-filter-select flex-shrink-0">
                        <label for="programme" class="visually-hidden">Course Name</label>
                        <select name="course_master_pk" id="programme" class="form-select son-filter-select" required aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach($courseMasters as $course)
                            <option value="{{ $course['pk'] }}">{{ $course['course_name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select son-time-period-filter position-relative flex-shrink-0">
                        <input type="hidden" id="from_date" name="from_date" value="{{ date('Y-m-d') }}">
                        <input type="hidden" id="to_date" name="to_date" value="{{ date('Y-m-d') }}">
                        <label for="son_time_period_picker" class="visually-hidden">Time Period</label>
                        <input type="text"
                            id="son_time_period_picker"
                            class="form-control son-time-period-input"
                            placeholder="Time Period"
                            value=""
                            readonly
                            autocomplete="off"
                            aria-label="Time period">
                        <i class="bi bi-chevron-down son-time-period-chevron" aria-hidden="true"></i>
                    </div>

                    <div class="programme-dt-filter-select flex-shrink-0">
                        <label for="attendance_type_ui" class="visually-hidden">Attendance Type</label>
                        <select id="attendance_type_ui" class="form-select son-filter-select" aria-label="Attendance Type">
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

                    <div class="programme-dt-filter-select flex-shrink-0" id="normal_session_container" style="display: none;">
                        <label for="session" class="visually-hidden">Normal Session</label>
                        <select name="session" id="session" class="form-select son-filter-select" aria-label="Normal Session">
                            <option value="">Normal Session</option>
                            @foreach($sessions as $session)
                            <option value="{{ $session['pk'] }}">{{ $session['shift_name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select flex-shrink-0" id="manual_session_container" style="display: none;">
                        <label for="manual_session" class="visually-hidden">Manual Session</label>
                        <select name="manual_session" id="manual_session" class="form-select son-filter-select" aria-label="Manual Session">
                            <option value="">Manual Session</option>
                            @foreach($maunalSessions as $maunalSession)
                            <option value="{{ $maunalSession['class_session'] }}">{{ $maunalSession['class_session'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset flex-shrink-0" id="resetAttendance">
                        Reset Filters
                    </button>

                    <button type="button" class="btn btn-primary d-none" id="searchAttendance" aria-hidden="true" tabindex="-1">
                        Search
                    </button>
                </div>

                <div class="son-table-search-slot ms-xl-auto flex-shrink-0">
                    <div class="dropdown">
                        <button type="button"
                            class="btn son-search-trigger"
                            id="sonSearchTrigger"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            aria-expanded="false"
                            aria-label="Search table">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3 son-search-menu">
                            <label class="form-label small text-secondary mb-2">Search</label>
                            <div id="sonDtSearch" class="son-dt-search-host" data-dt-search-for="attendanceTable"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="attendanceTableCard" class="programme-dt-panel son-dt-panel d-none">
                <div id="attendanceTableDiv" class="table-responsive son-dt-scroll">
                    <table id="attendanceTable" class="table align-middle mb-0 w-100 programme-dt-table son-dt-table" data-dt-footer="#sonDtFooter">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap son-col-sno">S. No.</th>
                                <th scope="col" class="son-col-course">Course Name</th>
                                <th scope="col" class="text-nowrap">Date</th>
                                <th scope="col" class="text-nowrap">Session</th>
                                <th scope="col" class="son-col-venue">Venue</th>
                                <th scope="col" class="text-nowrap">Group</th>
                                <th scope="col" class="son-col-topic">Topic</th>
                                <th scope="col">Faculty</th>
                                <th scope="col" class="text-center text-nowrap son-col-action">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div id="sonDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="attendanceTable"></div>
            </div>
        </div>
    </div>
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

    function reloadSonNoticeIfReady() {
        if ($.fn.DataTable.isDataTable('#attendanceTable')) {
            attendanceTable.ajax.reload(null, false);
            return;
        }
        if ($('#programme').val() && $('#from_date').val() && $('#to_date').val()) {
            performAttendanceSearch();
        }
    }

    window.enhanceSonNoticeActions = function () {
        $('#attendanceTable tbody td:last-child a, #attendanceTable_wrapper tbody td:last-child a').each(function () {
            var $link = $(this);
            var label = $.trim($link.attr('aria-label')) || $.trim($link.attr('title')) || 'Send Notice';
            var href = $link.attr('href') || '#';
            var isMarked = $link.hasClass('att-fingerprint-btn--marked');

            $link.attr('href', href)
                .attr('title', label)
                .attr('aria-label', label)
                .removeClass('att-fingerprint-btn att-fingerprint-btn--marked att-fingerprint-btn--default btn btn-primary btn-success btn-sm bg-primary text-primary border-0')
                .addClass('son-notice-play-btn')
                .toggleClass('son-notice-play-btn--marked', isMarked)
                .empty()
                .append('<i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">send</i>')
                .append($('<span class="visually-hidden"></span>').text(label));
        });
    };

    function scheduleSonNoticeActionEnhance() {
        window.enhanceSonNoticeActions();
        window.setTimeout(window.enhanceSonNoticeActions, 0);
        window.setTimeout(window.enhanceSonNoticeActions, 120);
    }

    if (document.getElementById('sonNoticePage')) {
        if (typeof window.enhanceAttendanceFingerprintActions === 'function') {
            $(document).off('draw.dt', '#attendanceTable', window.enhanceAttendanceFingerprintActions);
        }

        window.drawAttendanceTable = function () {
            if ($.fn.DataTable.isDataTable('#attendanceTable')) {
                attendanceTable.destroy();
            }

            $('#defaultMessageRow').hide();

            attendanceTable = $('#attendanceTable').DataTable({
                responsive: false,
                processing: true,
                serverSide: true,
                pageLength: 10,
                lengthChange: true,
                lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                pagingType: 'full_numbers',
                ajax: {
                    url: routes.getAttendanceList,
                    type: 'POST',
                    data: function (d) {
                        d._token = $('meta[name="csrf-token"]').attr('content');
                        d.programme = $('#programme').val();
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                        d.view_type = $('#view_type').val();
                        d.attendance_type = $('input[name="attendance_type"]:checked').val();
                        if (d.attendance_type === 'normal') {
                            d.session_value = $('#session').val();
                        } else if (d.attendance_type === 'manual') {
                            d.session_value = $('#manual_session').val();
                        }
                    }
                },
                initComplete: function () {
                    scheduleSonNoticeActionEnhance();
                    if (window.SargamDataTableUI) {
                        window.SargamDataTableUI.enhance(attendanceTable);
                    }
                },
                drawCallback: function () {
                    $('#attendanceTableCard').removeClass('d-none');
                    scheduleSonNoticeActionEnhance();
                    if (window.SargamDataTableUI) {
                        window.SargamDataTableUI.enhance(attendanceTable);
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'programme_name', name: 'programme_name' },
                    { data: 'mannual_starttime', name: 'mannual_starttime' },
                    { data: 'session_time', name: 'session_time', orderable: false, searchable: false },
                    { data: 'venue_name', name: 'venue_name' },
                    { data: 'group_name', name: 'group_name' },
                    { data: 'subject_topic', name: 'subject_topic' },
                    { data: 'faculty_name', name: 'faculty_name' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ]
            });
        };

        $(document).on('draw.dt.sonNotice', '#attendanceTable', function () {
            scheduleSonNoticeActionEnhance();
            if (window.SargamDataTableUI && attendanceTable) {
                window.SargamDataTableUI.enhance(attendanceTable);
            }
        });

        $('#searchAttendance').on('click', function () {
            performAttendanceSearch();
        });
    }

    $(document).ready(function () {
        var today = new Date();
        var todayStr = formatYmd(today);

        if (!$('#from_date').val()) $('#from_date').val(todayStr);
        if (!$('#to_date').val()) $('#to_date').val(todayStr);

        if (typeof flatpickr !== 'undefined') {
            window.sonTimePeriodPicker = flatpickr('#son_time_period_picker', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
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
            setAttendanceTypeFromUi($(this).val() || 'full_day');
        });

        $(document).on('change', 'input[name="attendance_type"]', function () {
            syncAttendanceTypeUi();
            reloadSonNoticeIfReady();
        });

        $(document).on('change', '#session, #manual_session', function () {
            reloadSonNoticeIfReady();
        });

        $(document).on('click', '#resetAttendance', function () {
            setTimeout(function () {
                syncAttendanceTypeUi();
                $('#attendance_type_ui').val('full_day');
                var t = new Date();
                var ts = formatYmd(t);
                $('#from_date').val(ts);
                $('#to_date').val(ts);
                if (window.sonTimePeriodPicker) {
                    window.sonTimePeriodPicker.setDate([t, t], true);
                }
                $('#attendanceTableCard').addClass('d-none');
            }, 0);
        });

        if ($('#programme').val() && $('#from_date').val() && $('#to_date').val()) {
            performAttendanceSearch();
        }
    });
})();
</script>
@endpush
