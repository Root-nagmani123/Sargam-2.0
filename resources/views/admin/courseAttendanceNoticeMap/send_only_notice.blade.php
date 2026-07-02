@extends('admin.layouts.master')

@section('title', 'Send Direct Notice')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
<link rel="stylesheet" href="{{ asset('css/notice-memo-discipline.css') }}?v={{ @filemtime(public_path('css/notice-memo-discipline.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid send-notice-page py-2 py-md-3">
    <x-breadcrum title="Send Direct Notice" />
    <x-session_message />

    @include('admin.partials.memo_global_search')

    {{-- Tabs --}}
    <div class="py-3">
        <div class="sn-tabs">
            <a href="{{ route('send.notice.management.index') }}" class="sn-tab js-nav-tab active">Send Direct
                Notice</a>
            <a href="{{ route('memo.notice.management.index') }}" class="sn-tab js-nav-tab">Send Memo / Notice</a>
            <a href="{{ route('memo.discipline.index') }}" class="sn-tab js-nav-tab">Send Discipline Memo</a>
        </div>
    </div>

    {{-- Filters + Table --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="sn-filter-bar mb-3">
                <span class="sn-filter-label">Filters</span>

                {{-- Course Name --}}
                <select id="snCourse" class="sn-control" aria-label="Course Name">
                    <option value="">Course Name</option>
                    @foreach($courseMasters as $course)
                    @php
                    $full = trim((string) ($course['course_name'] ?? ''));
                    $short = trim((string) ($course['couse_short_name'] ?? ''));
                    $label = $short !== '' ? $short : $full;
                    @endphp
                    <option value="{{ $course['pk'] }}" title="{{ $full !== '' ? $full : $label }}">{{ $label }}
                    </option>
                    @endforeach
                </select>

                {{-- Time Period --}}
                <select id="snTimePeriod" class="sn-control" aria-label="Time Period">
                    <option value="">Time Period</option>
                    <option value="today" selected>Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="custom">Custom Range</option>
                </select>
                <input type="text" id="snDateRange" class="sn-date-range d-none" placeholder="Select date range"
                    aria-label="Custom date range" readonly>
                <input type="hidden" id="snFromDate" value="{{ date('Y-m-d') }}">
                <input type="hidden" id="snToDate" value="{{ date('Y-m-d') }}">

                {{-- Attendance Type --}}
                <select id="snAttendanceType" class="sn-control" aria-label="Attendance Type">
                    <option value="">Attendance Type</option>
                    <option value="full_day">Full Day</option>
                    <option value="manual">Manual</option>
                    <option value="normal">Normal</option>
                </select>

                {{-- Session (revealed for manual / normal) --}}
                <select id="snNormalSession" class="sn-control d-none" aria-label="Normal session">
                    <option value="">Select Session</option>
                    @foreach($sessions as $session)
                    <option value="{{ $session['pk'] }}">{{ $session['shift_name'] }}</option>
                    @endforeach
                </select>
                <select id="snManualSession" class="sn-control d-none" aria-label="Manual session">
                    <option value="">Select Session</option>
                    @foreach($maunalSessions as $maunalSession)
                    <option value="{{ $maunalSession['class_session'] }}">{{ $maunalSession['class_session'] }}</option>
                    @endforeach
                </select>

                <button type="button" id="snReset" class="sn-reset">Reset Filters</button>

                <div class="ms-auto d-flex align-items-center gap-2">
                    {{-- Columns toggle --}}
                    <button type="button" class="sn-icon-btn" data-bs-toggle="modal" data-bs-target="#snColumnModal">
                        <i class="bi bi-layout-three-columns"></i> Columns
                    </button>
                    {{-- Search --}}
                    <input type="search" id="snSearch" class="sn-search-input" placeholder="Search...">
                </div>
            </div>

            <div class="table-responsive">
                <table id="sendNoticeTable" class="table table-hover align-middle w-100 mb-0">
                    <thead>
                        <tr>
                            <th>S. No.</th>
                            <th>Course Name</th>
                            <th>Date</th>
                            <th>Session</th>
                            <th>Venue</th>
                            <th>Group</th>
                            <th>Topic</th>
                            <th>Faculty</th>
                            <th>Total OT</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Column Visibility modal --}}
    <div class="modal fade sn-colvis-modal" id="snColumnModal" tabindex="-1" aria-labelledby="snColumnModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="snColumnModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="sn-colvis-grid" id="snColumnGrid"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn-close-colvis" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Notice List modal (opens when a row's "Notice" action is clicked) --}}
    <div class="modal fade notice-list-modal" id="noticeListModal" tabindex="-1" aria-labelledby="noticeListModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn-back" data-bs-dismiss="modal" aria-label="Back">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <h5 class="modal-title" id="noticeListModalLabel">Notice List</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="noticeListModalBody"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
$(function() {
    var csrfToken = "{{ csrf_token() }}";

    // ── Choices.js filter dropdowns ──
    var choicesMap = {};

    function initChoice(id, searchable) {
        var el = document.getElementById(id);
        if (!el || typeof Choices === 'undefined') return;
        choicesMap[id] = new Choices(el, {
            searchEnabled: !!searchable,
            shouldSort: false,
            itemSelectText: '',
            allowHTML: false,
            classNames: {
                containerInner: ['choices__inner', 'form-select', 'shadow-sm']
            }
        });
    }
    initChoice('snCourse', true);
    initChoice('snTimePeriod', false);
    initChoice('snAttendanceType', false);
    initChoice('snNormalSession', true);
    initChoice('snManualSession', true);

    function choiceContainer(id) {
        var inst = choicesMap[id];
        return (inst && inst.containerOuter) ? inst.containerOuter.element : document.getElementById(id);
    }

    function showControl(id, show) {
        var c = choiceContainer(id);
        if (c) c.classList.toggle('d-none', !show);
    }

    function resetChoice(id) {
        var inst = choicesMap[id];
        if (inst) {
            try {
                inst.setChoiceByValue('');
            } catch (e) {
                $('#' + id).val('');
            }
        } else {
            $('#' + id).val('');
        }
    }

    function currentSessionValue() {
        var type = $('#snAttendanceType').val();
        if (type === 'normal') return $('#snNormalSession').val() || '';
        if (type === 'manual') return $('#snManualSession').val() || '';
        return '';
    }

    var table = $('#sendNoticeTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        pageLength: 10,
        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100]
        ],
        order: [],
        dom: 'rt<"d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3"i<"d-flex align-items-center gap-2"lp>>',
        language: {
            processing: '<div class="spinner-border text-primary" role="status"></div>',
            emptyTable: 'No sessions found.',
            info: 'Showing _START_ to _END_ of _TOTAL_ items',
            infoEmpty: 'Showing 0 items',
            infoFiltered: '',
            paginate: {
                previous: '‹',
                next: '›'
            }
        },
        ajax: {
            url: "{{ route('attendance.get.attendance.list') }}",
            type: 'POST',
            data: function(d) {
                d._token = csrfToken;
                d.programme = $('#snCourse').val();
                d.from_date = $('#snFromDate').val();
                d.to_date = $('#snToDate').val();
                d.attendance_type = $('#snAttendanceType').val();
                d.session_value = currentSessionValue();
                d.page_context = 'send_notice';
            }
        },
        columns: [{
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'programme_name',
                name: 'programme_name'
            },
            {
                data: 'mannual_starttime',
                name: 'mannual_starttime'
            },
            {
                data: 'session_time',
                name: 'session_time'
            },
            {
                data: 'venue_name',
                name: 'venue_name'
            },
            {
                data: 'group_name',
                name: 'group_name'
            },
            {
                data: 'subject_topic',
                name: 'subject_topic'
            },
            {
                data: 'faculty_name',
                name: 'faculty_name'
            },
            {
                data: 'eligible_ot',
                name: 'eligible_ot',
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
        ]
    });

    // Session selects start hidden (Choices wraps them, so toggle the wrapper).
    showControl('snNormalSession', false);
    showControl('snManualSession', false);

    // Debounced reload so a Reset (which fires several programmatic changes) hits ajax once.
    var reloadTimer = null;

    function reloadTable() {
        clearTimeout(reloadTimer);
        reloadTimer = setTimeout(function() {
            table.ajax.reload();
        }, 200);
    }

    // ── Custom search (debounced) ──
    var searchTimer = null;
    $('#snSearch').on('keyup', function() {
        var val = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            table.search(val).draw();
        }, 350);
    });

    // ── Filters trigger reload (Choices fires native change on the underlying select) ──
    $('#snCourse, #snAttendanceType, #snNormalSession, #snManualSession, #snFromDate, #snToDate')
        .on('change', function() {
            reloadTable();
        });

    // ── Attendance type: reveal the matching session select ──
    $('#snAttendanceType').on('change', function() {
        var type = $(this).val();
        resetChoice('snNormalSession');
        resetChoice('snManualSession');
        showControl('snNormalSession', type === 'normal');
        showControl('snManualSession', type === 'manual');
    });

    // ── Time period: presets set the from/to dates ──
    function fmt(d) {
        return d.toISOString().split('T')[0];
    }

    // Dual-month range calendar for the "Custom Range" option.
    var snRangePicker = (typeof flatpickr !== 'undefined') ? flatpickr('#snDateRange', {
        mode: 'range',
        showMonths: 2,
        dateFormat: 'Y-m-d',
        maxDate: 'today',
        onReady: function(selectedDates, dateStr, instance) {
            instance.calendarContainer.classList.add('sn-flatpickr');
        },
        onChange: function(selectedDates) {
            if (selectedDates.length === 2) {
                $('#snFromDate').val(fmt(selectedDates[0]));
                $('#snToDate').val(fmt(selectedDates[1]));
                reloadTable();
            }
        }
    }) : null;

    $('#snTimePeriod').on('change', function() {
        var v = $(this).val();
        var today = new Date();
        var from = '',
            to = '';
        $('#snDateRange').addClass('d-none');

        if (v === 'today') {
            from = to = fmt(today);
        } else if (v === 'week') {
            var ws = new Date(today);
            ws.setDate(today.getDate() - today.getDay());
            from = fmt(ws);
            to = fmt(today);
        } else if (v === 'month') {
            from = fmt(new Date(today.getFullYear(), today.getMonth(), 1));
            to = fmt(today);
        } else if (v === 'custom') {
            $('#snDateRange').removeClass('d-none');
            if (snRangePicker) snRangePicker.open();
            return; // wait for the user to pick a range
        }
        if (snRangePicker) snRangePicker.clear();
        $('#snFromDate').val(from);
        $('#snToDate').val(to);
        reloadTable();
    });

    // ── Reset ──
    $('#snReset').on('click', function() {
        resetChoice('snCourse');
        resetChoice('snAttendanceType');
        resetChoice('snNormalSession');
        resetChoice('snManualSession');
        showControl('snNormalSession', false);
        showControl('snManualSession', false);
        if (snRangePicker) snRangePicker.clear();
        $('#snDateRange').addClass('d-none');
        // Time Period resets back to its default (Today), not "all".
        var todayStr = fmt(new Date());
        try {
            choicesMap['snTimePeriod'].setChoiceByValue('today');
        } catch (e) {
            $('#snTimePeriod').val('today');
        }
        $('#snFromDate').val(todayStr);
        $('#snToDate').val(todayStr);
        $('#snSearch').val('');
        table.search(''); // cleared term is sent on the reload below
        reloadTable();
    });

    // ── Column Visibility modal ──
    var headers = ['S. No.', 'Course Name', 'Date', 'Session', 'Venue', 'Group', 'Topic', 'Faculty',
        'Total OT', 'Action'
    ];
    var $grid = $('#snColumnGrid');
    headers.forEach(function(label, i) {
        var id = 'snCol' + i;
        $grid.append(
            '<label class="sn-colvis-chip" for="' + id + '" title="' + label + '">' +
            '<input type="checkbox" class="form-check-input sn-col-toggle" id="' + id +
            '" data-col="' + i + '" checked> ' +
            '<span>' + label + '</span></label>'
        );
    });
    $grid.on('change', '.sn-col-toggle', function() {
        table.column($(this).data('col')).visible(this.checked);
    });

    // Guarantee a full page reload when switching tabs
    $(document).on('click', '.js-nav-tab', function(e) {
        if ($(this).hasClass('active')) {
            return;
        }
        var href = this.getAttribute('href');
        if (href) {
            e.preventDefault();
            window.location.assign(href);
        }
    });
});
</script>

<script>
/* ── Notice List modal (Send Direct Notice) ── */
$(function () {
    var noticeModalEl = document.getElementById('noticeListModal');
    var noticeModal = (noticeModalEl && window.bootstrap) ? new bootstrap.Modal(noticeModalEl) : null;

    function syncSendAll() {
        var any = $('#noticeListModalBody .notice-row-check:checked').length > 0;
        $('#noticeSendAllBtn').prop('disabled', !any);
    }

    // Open the modal and load the session's OT list.
    $(document).on('click', '.js-open-notice', function (e) {
        e.preventDefault();
        var url = this.getAttribute('href');
        var $body = $('#noticeListModalBody');
        $body.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');
        if (noticeModal) noticeModal.show();
        $.get(url).done(function (html) {
            $body.html(html);
            syncSendAll();
        }).fail(function () {
            $body.html('<div class="text-center text-danger py-5">Failed to load the notice list.</div>');
        });
    });

    // Select-all toggles every row checkbox.
    $(document).on('change', '#noticeSelectAll', function () {
        $('#noticeListModalBody .notice-row-check').prop('checked', this.checked);
        syncSendAll();
    });

    // Keep select-all + Send button in sync when a row is toggled.
    $(document).on('change', '.notice-row-check', function () {
        var $all = $('#noticeListModalBody .notice-row-check');
        $('#noticeSelectAll').prop('checked', $all.length > 0 && $all.filter(':checked').length === $all.length);
        syncSendAll();
    });

    // Client-side search over OT name / code.
    $(document).on('input', '#noticeSearch', function () {
        var q = this.value.toLowerCase();
        $('#noticeListModalBody tr.notice-row').each(function () {
            $(this).toggle((($(this).data('search') || '') + '').indexOf(q) > -1);
        });
    });
    // Enter in search must not submit the form.
    $(document).on('keydown', '#noticeSearch', function (e) {
        if (e.key === 'Enter') e.preventDefault();
    });

    // Per-row "Notice": send a notice to just that OT (build a one-off POST).
    $(document).on('click', '.js-row-notice', function (e) {
        e.preventDefault();
        var student = $(this).data('student');
        var attPk = $(this).data('attendance');
        var $src = $('#noticeListForm');
        if (!$src.length || student == null) return;

        var $f = $('<form>', { method: 'POST', action: $src.attr('action') }).hide();
        $f.append($('<input type="hidden" name="_token">').val($src.find('input[name="_token"]').val()));
        ['subject_master_id', 'course_master_pk', 'topic_id', 'venue_id', 'class_session_master_pk', 'faculty_master_pk', 'memo_notice_template_pk']
            .forEach(function (n) {
                $f.append($('<input type="hidden">').attr('name', n).val($src.find('[name="' + n + '"]').val()));
            });
        $f.append($('<input type="hidden" name="selected_student_list[]">').val(student));
        $f.append($('<input type="hidden">').attr('name', 'attendance_pk_' + student).val(attPk));
        $('body').append($f);
        $f.trigger('submit');
    });
});
</script>
@endpush
