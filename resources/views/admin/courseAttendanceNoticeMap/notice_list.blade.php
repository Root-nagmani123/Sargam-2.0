@extends('admin.layouts.master')

@section('title', 'Notice List')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    .notice-list-page .nl-status {
        font-weight: 600;
    }
    .notice-list-page .nl-status--present { color: #16a34a; }
    .notice-list-page .nl-status--late    { color: #d97706; }
    .notice-list-page .nl-status--absent  { color: #dc2626; }

    .notice-list-page .nl-row-notice {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        color: var(--bs-primary);
        text-decoration: none;
        line-height: 1;
        font-size: .8rem;
    }
    .notice-list-page .nl-row-notice .material-icons {
        font-size: 1.4rem;
    }
    .notice-list-page .nl-row-notice:hover {
        color: #003d7a;
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid notice-list-page py-2 py-md-3">

    <x-breadcrum title="Notice List" :showBack="true" />
    <x-session_message />

    @php
        $count           = isset($students) ? count($students) : 0;
        $subjectMasterId = optional($courseGroup->timetable)->subject_master_pk;
        $topicId         = optional($courseGroup->timetable)->pk;
        $venueId         = optional($courseGroup->timetable)->venue_id;
        $classSession    = optional($courseGroup->timetable)->class_session;

        $resolvedFacultyIds     = get_timetable_faculty_ids(optional($courseGroup)->timetable);
        $resolvedFacultyPayload = optional($courseGroup->timetable)->faculty_master;
        if (empty($resolvedFacultyPayload) && !empty($resolvedFacultyIds)) {
            $resolvedFacultyPayload = json_encode($resolvedFacultyIds);
        }

        // The timetable's class_session holds the session time window (e.g. "10:35 to 11:30")
        // — shown in the Attendance column for every OT in this session.
        $sessionRange = trim((string) $classSession) !== '' ? $classSession : null;

        // Attendance status → coloured suffix appended to the OT name.
        $statusMeta = [
            1 => ['label' => 'Present', 'class' => 'nl-status--present'],
            2 => ['label' => 'Late',    'class' => 'nl-status--late'],
            3 => ['label' => 'Absent',  'class' => 'nl-status--absent'],
        ];
    @endphp

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-3 p-md-4">

            <form action="{{ route('notice.direct.save') }}" method="POST" id="noticeListForm">
                @csrf
                <input type="hidden" name="subject_master_id" value="{{ $subjectMasterId }}">
                <input type="hidden" name="course_master_pk" value="{{ $course_pk }}">
                <input type="hidden" name="topic_id" value="{{ $topicId }}">
                <input type="hidden" name="venue_id" value="{{ $venueId }}">
                <input type="hidden" name="class_session_master_pk" value="{{ $classSession }}">
                <input type="hidden" name="faculty_master_pk" value="{{ $resolvedFacultyPayload }}">

                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 mb-3">
                    {{-- Bulk controls: hidden until at least one OT is selected --}}
                    <div id="noticeBulkBar" class="d-none flex-wrap align-items-end gap-3">
                        @if(isset($noticeTemplates) && $noticeTemplates->count())
                            <div>
                                <label for="noticeTemplateSelect" class="form-label mb-1 fw-semibold small">Notice Template</label>
                                <select name="memo_notice_template_pk" id="noticeTemplateSelect" class="form-select form-select-sm" style="min-width:220px;">
                                    @foreach($noticeTemplates as $tpl)
                                        <option value="{{ $tpl->pk }}">{{ $tpl->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2" id="noticeSendAllBtn" disabled>
                            <i class="material-icons material-icons-rounded" style="font-size:18px;">send</i>
                            <span>Send Notice to All</span>
                        </button>
                    </div>

                    <div class="programme-dt-search ms-lg-auto" data-dt-search-for="noticeListTable"></div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        <table id="noticeListTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:48px;">
                                        <input type="checkbox" id="noticeSelectAll" class="form-check-input" aria-label="Select all">
                                    </th>
                                    <th>S. No.</th>
                                    <th>OT Name</th>
                                    <th>OT Code</th>
                                    <th>Attendance</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            {{-- Rows come from the server-side DataTable (see script below). --}}
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="noticeListTable"></div>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var $table = $('#noticeListTable');
    var dataUrl = "{{ route('send.notice.list.page', ['group_pk' => $group_pk, 'course_pk' => $course_pk, 'timetable_pk' => $timetable_pk]) }}";

    // Selected OTs survive paging: rows leave the DOM when the server sends the next page.
    var selectedRows = {};

    // Server-side DataTable — the global enhancer (datatable-global-ui.js) relocates
    // the search + pagination + "Showing N of M items" into the provided slots.
    var dt = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: dataUrl, type: 'GET' },
        columns: [
            { data: 'select', name: 'select', orderable: false, searchable: false, className: 'text-center' },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'ot_name', name: 'display_name', className: 'fw-medium' },
            { data: 'ot_code', name: 'generated_OT_code' },
            { data: 'attendance', name: 'attendance', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        ordering: true,
        order: [],
        language: {
            processing: 'Loading data…',
            emptyTable: 'No students found for this session.'
        },
        drawCallback: function () {
            // Re-apply the selection to the rows that just arrived.
            $('#noticeListTable .notice-row-check').each(function () {
                this.checked = Object.prototype.hasOwnProperty.call(selectedRows, String(this.value));
            });
            syncSelectAllCheckbox();
            syncSendAll();
        }
    });

    function syncSendAll() {
        var any = Object.keys(selectedRows).length > 0;
        $('#noticeSendAllBtn').prop('disabled', !any);
        // Reveal the template + "Send Notice to All" controls only while a selection exists.
        $('#noticeBulkBar').toggleClass('d-none', !any).toggleClass('d-flex', any);
    }

    function syncSelectAllCheckbox() {
        var $all = $('#noticeListTable .notice-row-check');
        $('#noticeSelectAll').prop('checked', $all.length > 0 && $all.filter(':checked').length === $all.length);
    }

    // Select-all covers every row of the current result set, not just the visible page.
    $('#noticeSelectAll').on('change', function () {
        var checked = this.checked;
        if (!checked) {
            selectedRows = {};
            $('#noticeListTable .notice-row-check').prop('checked', false);
            syncSendAll();
            return;
        }

        var params = $.extend({}, dt.ajax.params(), { start: 0, length: -1 });
        $.getJSON(dataUrl, params, function (res) {
            (res.data || []).forEach(function (row) {
                var $cell = $('<div>').html(row.select);
                var student = $cell.find('.notice-row-check').val();
                var attPk = $cell.find('.notice-row-att').val();
                if (student) {
                    selectedRows[String(student)] = attPk || '';
                }
            });
            $('#noticeListTable .notice-row-check').prop('checked', true);
            syncSendAll();
        });
    });

    // Keep select-all + Send button in sync when a row is toggled.
    $('#noticeListTable').on('change', '.notice-row-check', function () {
        var student = String(this.value);
        if (this.checked) {
            selectedRows[student] = $(this).closest('td').find('.notice-row-att').val() || '';
        } else {
            delete selectedRows[student];
        }
        syncSelectAllCheckbox();
        syncSendAll();
    });

    // Bulk "Send Notice to All (selected)": build a clean POST so rows paged out of
    // the DOM by the server-side grid are still included.
    $('#noticeListForm').on('submit', function (e) {
        e.preventDefault();
        var $src = $(this);

        var selected = Object.keys(selectedRows).map(function (student) {
            return { student: student, attPk: selectedRows[student] };
        });
        if (!selected.length) { return; }

        var $f = $('<form>', { method: 'POST', action: $src.attr('action') }).hide();
        $f.append($('<input type="hidden" name="_token">').val($src.find('input[name="_token"]').val()));
        ['subject_master_id', 'course_master_pk', 'topic_id', 'venue_id', 'class_session_master_pk', 'faculty_master_pk', 'memo_notice_template_pk']
            .forEach(function (n) {
                $f.append($('<input type="hidden">').attr('name', n).val($src.find('[name="' + n + '"]').val()));
            });
        selected.forEach(function (s) {
            $f.append($('<input type="hidden" name="selected_student_list[]">').val(s.student));
            $f.append($('<input type="hidden">').attr('name', 'attendance_pk_' + s.student).val(s.attPk));
        });
        $('body').append($f);
        $f.trigger('submit');
    });

    // Per-row "Notice": send a notice to just that OT.
    $('#noticeListTable').on('click', '.js-row-notice', function (e) {
        e.preventDefault();
        var student = $(this).data('student');
        var attPk = $(this).data('attendance');
        var $src = $('#noticeListForm');
        if (!$src.length || student == null) { return; }

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
