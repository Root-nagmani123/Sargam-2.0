@extends('admin.layouts.master')

@section('title', 'Topic wise Attendance')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mark-attendance-admin.css') }}?v={{ @filemtime(public_path('css/mark-attendance-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $topicRaw = optional($courseGroup->timetable)->subject_topic ?? '';
    $topicPlain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $topicRaw)));
    $topicShort = \Illuminate\Support\Str::limit($topicPlain, 42, '...');
    $pageHeading = ($topicShort !== '' ? $topicShort : 'Topic') . "'s Attendance";
    $courseName = optional($courseGroup->course)->course_name ?? 'N/A';
    $sessionDate = !empty(optional($courseGroup->timetable)->START_DATE)
        ? \Carbon\Carbon::parse($courseGroup->timetable->START_DATE)->format('d/m/Y')
        : 'N/A';
@endphp

<form action="{{ route('attendance.save') }}" method="post" class="mark-attendance-page">
    @csrf

    <div class="container-fluid mark-att-master-page">
        <input type="hidden" name="group_pk" id="group_pk" value="{{ $group_pk }}">
        <input type="hidden" name="course_pk" id="course_pk" value="{{ $course_pk }}">
        <input type="hidden" name="timetable_pk" id="timetable_pk" value="{{ $courseGroup->timetable_pk }}">
        <x-breadcrum title="Topic wise Attendance">
        @if($currentPath === 'mark')
                    @if(!empty($allMarked) && $allMarked)
                        <button type="submit"
                            class="btn btn-success d-inline-flex align-items-center justify-content-center fw-semibold shadow-sm text-nowrap mark-att-btn-mark"
                            disabled
                            aria-disabled="true">
                            <i class="bi bi-check-circle-fill flex-shrink-0" aria-hidden="true"></i>
                            <span>Attendance Already Marked</span>
                        </button>
                    @else
                        <button type="submit"
                            class="btn btn-primary d-inline-flex align-items-center justify-content-center fw-semibold shadow-sm text-nowrap mark-att-btn-mark">
                            <i class="bi bi-clipboard2-check flex-shrink-0" aria-hidden="true"></i>
                            <span>Mark Attendance</span>
                        </button>
                    @endif
                @endif
    </x-breadcrum>
<div class="d-flex justify-content-end mb-2">
<a href="{{ route('attendance.export', ['group_pk' => $group_pk, 'course_pk' => $course_pk, 'timetable_pk' => $courseGroup->timetable_pk]) }}"
                    class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center fw-semibold shadow-sm text-nowrap mark-att-btn-download">
                    <i class="bi bi-download flex-shrink-0" aria-hidden="true"></i>
                    <span>Download</span>
                </a>
</div>
        <x-session_message />
        <div class="card mark-att-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar mark-att-dt-toolbar w-100">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filters</span>

                        <div class="programme-dt-filter-select mark-att-course-filter">
                            <label for="mark_att_course_display" class="visually-hidden">Course Name</label>
                            <select id="mark_att_course_display" class="form-select form-select-sm" disabled aria-label="Course Name">
                                <option value="" selected>{{ $courseName }}</option>
                            </select>
                        </div>

                        <div class="programme-dt-filter-select mark-att-period-filter">
                            <label for="mark_att_period_display" class="visually-hidden">Time Period</label>
                            <select id="mark_att_period_display" class="form-select form-select-sm" disabled aria-label="Time Period">
                                <option value="" selected>{{ $sessionDate }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="mark-att-table-search-slot ms-xl-auto flex-shrink-0">
                        <div class="dropdown">
                            <button type="button"
                                class="btn mark-att-search-trigger"
                                id="markAttSearchTrigger"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-label="Search table">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3 mark-att-search-menu">
                                <label for="markAttTableSearch" class="form-label small text-secondary mb-2">Search</label>
                                <div id="markAttDtSearch" class="mark-att-dt-search-host" data-dt-search-for="studentAttendanceTable"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="programme-dt-panel mark-att-dt-panel">
                    <div class="table-responsive mark-att-dt-scroll">
                        {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table mark-attendance-dt-table']) !!}
                    </div>
                    <div id="markAttDtFooter"
                        class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                        data-dt-footer-for="studentAttendanceTable"></div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.attendance.partials.mark-attendance-row-modal', ['allMarked' => $allMarked ?? false])
</form>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
(function () {
    var activeStudentId = null;
    var $modal = $('#markAttendanceRowModal');
    var isLocked = String($modal.data('mark-att-locked')) === '1';

    function statusBadgeHtml(val) {
        var map = {
            '1': ['Present', 'mark-att-status-badge--present'],
            '2': ['Late', 'mark-att-status-badge--late'],
            '3': ['Absent', 'mark-att-status-badge--absent']
        };
        var item = map[String(val)] || ['Not Marked', 'mark-att-status-badge--neutral'];
        return '<span class="mark-att-status-badge ' + item[1] + '">' + item[0] + '</span>';
    }

    function mdoLabelForValue(val) {
        if (String(val) === '4') {
            return 'Yes';
        }
        if (val === '0' || val === '' || val == null) {
            return 'NA';
        }
        return 'No';
    }

    function updateMdoColumn($tr, val) {
        $tr.find('.mark-att-mdo-text').text(mdoLabelForValue(val));
    }

    function getStudentRow(studentId) {
        return $('#studentAttendanceTable')
            .find('input[type=radio][name="student[' + studentId + ']"]')
            .first()
            .closest('tr');
    }

    function getRowCheckedValue($tr) {
        var $checked = $tr.find('input[type=radio][name^="student["]:checked');
        return $checked.length ? String($checked.val()) : '0';
    }

    function setModalLockedState(locked) {
        $modal.find('input, button#markAttModalApplyBtn').prop('disabled', locked);
        if (locked) {
            $('#markAttModalApplyBtn').addClass('disabled');
        } else {
            $('#markAttModalApplyBtn').removeClass('disabled');
        }
    }

    function resetExemptionToggles() {
        $('#markAttModalMdo, #markAttModalMedical, #markAttModalOther').prop('checked', false);
    }

    function populateModalFromRow(studentId) {
        var $tr = getStudentRow(studentId);
        if (!$tr.length) {
            return;
        }

        var val = getRowCheckedValue($tr);
        resetExemptionToggles();

        if (val === '4') {
            $('#markAttModalMdo').prop('checked', true);
            $('input[name="mark_att_modal_status"][value="1"]').prop('checked', true);
        } else if (val === '6') {
            $('#markAttModalMedical').prop('checked', true);
            $('input[name="mark_att_modal_status"][value="1"]').prop('checked', true);
        } else if (val === '7') {
            $('#markAttModalOther').prop('checked', true);
            $('input[name="mark_att_modal_status"][value="1"]').prop('checked', true);
        } else if (['1', '2', '3'].includes(val)) {
            $('input[name="mark_att_modal_status"][value="' + val + '"]').prop('checked', true);
        } else {
            $('input[name="mark_att_modal_status"][value="1"]').prop('checked', true);
        }
    }

    function resolveApplyValue() {
        if ($('#markAttModalOther').is(':checked')) {
            return '7';
        }
        if ($('#markAttModalMedical').is(':checked')) {
            return '6';
        }
        if ($('#markAttModalMdo').is(':checked')) {
            return '4';
        }
        return $('input[name="mark_att_modal_status"]:checked').val() || '1';
    }

    function applyModalToRow() {
        if (!activeStudentId || isLocked) {
            return;
        }

        var $tr = getStudentRow(activeStudentId);
        if (!$tr.length) {
            return;
        }

        var status = resolveApplyValue();
        var $radio = $tr.find('input[type=radio][name="student[' + activeStudentId + ']"][value="' + status + '"]');

        if ($radio.length) {
            $radio.prop('checked', true).trigger('change');
        }

        if (['1', '2', '3'].includes(String(status))) {
            $tr.find('td.mark-att-current-col').html(statusBadgeHtml(status));
        }

        updateMdoColumn($tr, status);

        var modalEl = document.getElementById('markAttendanceRowModal');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }
    }

    $(document).on('change', '#studentAttendanceTable input[type=radio][name^="student["]', function () {
        var val = String($(this).val());
        var $tr = $(this).closest('tr');

        if (['1', '2', '3'].includes(val)) {
            $tr.find('td.mark-att-current-col').html(statusBadgeHtml(val));
        }

        updateMdoColumn($tr, val);
    });

    $(document).on('click', '#markAttModalMdo, #markAttModalMedical, #markAttModalOther', function () {
        if (!$(this).is(':checked')) {
            return;
        }
        $('#markAttModalMdo, #markAttModalMedical, #markAttModalOther').not(this).prop('checked', false);
    });

    $(document).on('click', '.mark-att-fingerprint-btn', function (e) {
        e.preventDefault();

        if (isLocked) {
            return;
        }

        activeStudentId = $(this).data('student-id');
        var studentName = $(this).data('student-name') || 'Student';
        $('#markAttModalStudentContext').text('Editing attendance for ' + studentName);

        populateModalFromRow(activeStudentId);
        setModalLockedState(false);

        var modalEl = document.getElementById('markAttendanceRowModal');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });

    $(document).on('hidden.bs.modal', '#markAttendanceRowModal', function () {
        activeStudentId = null;
    });

    $('#markAttModalApplyBtn').on('click', function (e) {
        e.preventDefault();
        applyModalToRow();
    });
})();
</script>
@endpush
