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
                            <tbody>
                                @forelse($students as $i => $row)
                                    @php
                                        $studentId = $row->Student_master_pk;
                                        $st = $statusMeta[$row->status] ?? null;
                                    @endphp
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input notice-row-check" name="selected_student_list[]" value="{{ $studentId }}" aria-label="Select OT">
                                            <input type="hidden" class="notice-row-att" name="attendance_pk_{{ $studentId }}" value="{{ $row->pk }}">
                                        </td>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="fw-medium">{{ $row->display_name ?? 'N/A' }}@if($st) <span class="nl-status {{ $st['class'] }}">({{ $st['label'] }})</span>@endif</td>
                                        <td>{{ $row->generated_OT_code ?? 'N/A' }}</td>
                                        <td>{{ $sessionRange ?? '—' }}</td>
                                        <td class="text-center">
                                            <a href="#" class="nl-row-notice js-row-notice" data-student="{{ $studentId }}" data-attendance="{{ $row->pk }}" title="Send Notice">
                                                <i class="material-icons material-icons-rounded">send</i>
                                                <span>Notice</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">No students found for this session.</td>
                                    </tr>
                                @endforelse
                            </tbody>
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
    var hasRows = $table.find('tbody tr').length && !$table.find('tbody tr td[colspan]').length;

    // Client-side DataTable — the global enhancer (datatable-global-ui.js) relocates
    // the search + pagination + "Showing N of M items" into the provided slots.
    var dt = null;
    if (hasRows) {
        dt = $table.DataTable({
            ordering: true,
            order: [[1, 'asc']],
            columnDefs: [
                { targets: [0, 5], orderable: false, searchable: false }
            ]
        });
    }

    function syncSendAll() {
        var any = $('#noticeListTable .notice-row-check:checked').length > 0;
        $('#noticeSendAllBtn').prop('disabled', !any);
        // Reveal the template + "Send Notice to All" controls only while a selection exists.
        $('#noticeBulkBar').toggleClass('d-none', !any).toggleClass('d-flex', any);
    }

    // Select-all toggles every row checkbox (across all pages).
    $('#noticeSelectAll').on('change', function () {
        var checked = this.checked;
        if (dt) {
            $(dt.rows().nodes()).find('.notice-row-check').prop('checked', checked);
        } else {
            $('#noticeListTable .notice-row-check').prop('checked', checked);
        }
        syncSendAll();
    });

    // Keep select-all + Send button in sync when a row is toggled.
    $('#noticeListTable').on('change', '.notice-row-check', function () {
        var $all = dt ? $(dt.rows().nodes()).find('.notice-row-check') : $('#noticeListTable .notice-row-check');
        $('#noticeSelectAll').prop('checked', $all.length > 0 && $all.filter(':checked').length === $all.length);
        syncSendAll();
    });

    // Bulk "Send Notice to All (selected)": build a clean POST so rows paged out of
    // the DOM by DataTables are still included.
    $('#noticeListForm').on('submit', function (e) {
        e.preventDefault();
        var $src = $(this);

        var $rows = dt ? $(dt.rows().nodes()) : $('#noticeListTable tbody tr');
        var selected = [];
        $rows.each(function () {
            var $cb = $(this).find('.notice-row-check');
            if ($cb.prop('checked')) {
                selected.push({
                    student: $cb.val(),
                    attPk: $(this).find('.notice-row-att').val()
                });
            }
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
