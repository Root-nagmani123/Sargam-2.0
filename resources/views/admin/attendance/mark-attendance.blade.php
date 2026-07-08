@extends('admin.layouts.master')

@section('title', 'Mark Attendance')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
.att-mark-page .att-header-card { border: 0; border-radius: 14px; }
.att-mark-page .att-back {
    width: 36px; height: 36px; border-radius: 10px;
    display: inline-flex; align-items: center; justify-content: center;
    color: #101828; background: #f2f4f7; text-decoration: none; font-size: 1.15rem;
}
.att-mark-page .att-back:hover { background: #e4e7ec; }
.att-mark-page .att-crumb { font-size: 0.8rem; color: #667085; }
.att-mark-page .att-title {
    color: #101828; font-weight: 700; margin: 0;
    max-width: 46ch; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.att-mark-page .att-btn-outline {
    height: 42px; display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0 1.1rem; font-weight: 600; font-size: 0.9rem;
    color: #004a93; background: #fff; border: 1px solid #d0d5dd; border-radius: 8px;
}
.att-mark-page .att-btn-outline:hover { background: #f2f7fc; border-color: #004a93; }
.att-mark-page .att-btn-primary { height: 42px; font-weight: 600; }

/* Filter chips */
.att-mark-page .att-info {
    height: 42px; display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0 0.9rem; font-size: 0.875rem; color: #344054;
    background: #fff; border: 1px solid #d0d5dd; border-radius: 8px;
}
.att-mark-page .att-info small { color: #667085; }

/* Search */
.att-mark-page .att-search { position: relative; width: 260px; max-width: 100%; }
.att-mark-page .att-search i {
    position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #98a2b3;
}
.att-mark-page .att-search input {
    width: 100%; height: 42px; padding: 0 0.875rem 0 2.25rem;
    border: 1px solid #d0d5dd; border-radius: 8px; font-size: 0.9rem;
}

/* Table */
.att-mark-page .att-table { border-collapse: separate; border-spacing: 0; }
.att-mark-page .att-table thead th {
    background: #f2f4f7 !important; color: #475467 !important;
    font-size: 0.8125rem; font-weight: 600; padding: 0.9rem 1rem !important;
    border-bottom: 1px solid #e4e7ec !important; white-space: nowrap;
}
.att-mark-page .att-table tbody td { padding: 0.9rem 1rem !important; font-size: 0.875rem; color: #344054; border-bottom: 1px solid #eef2f6 !important; }
.att-mark-page .att-table tbody tr:hover td { background: rgba(0,74,147,0.03); }

/* Status badges */
.att-mark-page .att-badge {
    display: inline-flex; align-items: center; font-size: 0.8125rem; font-weight: 600;
    line-height: 1; padding: 0.4rem 0.9rem; border-radius: 5px;
}
.att-mark-page .att-present { color: #027a48; background: #ecfdf3; }
.att-mark-page .att-absent  { color: #b42318; background: #fef3f2; }
.att-mark-page .att-nm      { color: #b54708; background: #fffaeb; }
.att-mark-page .att-late    { color: #b54708; background: #fff6ed; }
.att-mark-page .att-duty    { color: #004a93; background: #eff8ff; }
.att-mark-page .att-exempt  { color: #475467; background: #f2f4f7; }

/* Fingerprint action */
.att-mark-page .att-action-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2rem; height: 2rem; border-radius: 8px; color: #004a93; font-size: 1.2rem;
    text-decoration: none; border: 0; background: transparent; cursor: pointer;
    transition: background-color .15s ease;
}
.att-mark-page .att-action-icon:hover { background: #eef3f9; }

/* Mark-OT modal */
#markOtModal .modal-content { border: 0; border-radius: 14px; }
#markOtModal .mark-ot-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.85rem 0; border-top: 1px solid #eef2f6;
}
#markOtModal .mark-ot-section { font-size: 0.9rem; font-weight: 700; color: #101828; margin: 1rem 0 0.25rem; }
#markOtModal .form-switch .form-check-input { width: 2.6rem; height: 1.4rem; cursor: pointer; }
#markOtModal .form-check-input:checked { background-color: #004a93; border-color: #004a93; }
#markOtModal .att-radio-group { display: flex; flex-wrap: wrap; gap: 1.25rem; }

.att-mark-page .att-footer {
    border-top: 1px solid #e4e7ec; padding: 0.875rem 1rem; color: #667085; font-size: 0.875rem;
}
</style>
@endpush

@section('setup_content')
@php
    $courseName = optional($courseGroup->course)->course_name ?? 'OT';
    $sessionDate = optional($courseGroup->timetable)->START_DATE
        ? \Carbon\Carbon::parse($courseGroup->timetable->START_DATE)->format('d/m/Y') : 'N/A';
    $sessionTime = optional($courseGroup->timetable)->class_session ?? 'N/A';
    $canMark = ($currentPath === 'mark') && (hasRole('Super Admin') || hasRole('Training Induction Admin')
        || hasRole('Training-Induction') || hasRole('Staff') || hasRole('Admin') || hasRole('Internal Faculty'));
@endphp
<div class="container-fluid att-mark-page py-3">
    <x-session_message />

    {{-- Header --}}
    <div class="card att-header-card shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 px-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('attendance.index') }}" class="att-back" aria-label="Back"><i class="bi bi-arrow-left"></i></a>
                <div>
                    <div class="att-crumb">Home / OT Attendance / Mark Attendance</div>
                    <h4 class="att-title" title="{{ $courseName }}">{{ $courseName }}'s OT Attendance</h4>
                </div>
            </div>
            @if($canMark)
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn att-btn-outline" id="markAbsentBulk">
                    <i class="bi bi-x-circle"></i> Mark Absent in Bulk
                </button>
                <button type="button" class="btn btn-primary att-btn-primary px-4" id="markPresentBulk">
                    <i class="bi bi-check2-circle"></i> Mark Present in Bulk
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- Download --}}
    <div class="d-flex justify-content-end mb-2">
        <a href="{{ route('attendance.export', ['group_pk' => $group_pk, 'course_pk' => $course_pk, 'timetable_pk' => $courseGroup->timetable_pk]) }}"
            class="att-btn-outline">
            <i class="bi bi-download"></i> Download
        </a>
    </div>

    <form action="{{ route('attendance.save') }}" method="post" id="attMarkForm">
        @csrf
        <input type="hidden" name="group_pk" id="group_pk" value="{{ $group_pk }}">
        <input type="hidden" name="course_pk" id="course_pk" value="{{ $course_pk }}">
        <input type="hidden" name="timetable_pk" id="timetable_pk" value="{{ $courseGroup->timetable_pk }}">

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-3 p-md-4">

                {{-- Filter bar --}}
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="fw-semibold text-body-secondary me-1">Filters</span>
                    <span class="att-info"><small>Course Name</small>&nbsp;{{ \Illuminate\Support\Str::limit($courseName, 28) }}</span>
                    <span class="att-info"><small>Time Period</small>&nbsp;{{ $sessionDate }}</span>
                    <span class="att-info d-none d-lg-inline-flex"><small>Session</small>&nbsp;{{ $sessionTime }}</span>
                    <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="att-btn-outline" id="btnAttColumns"
                            data-bs-toggle="modal" data-bs-target="#attColumnModal" title="Show / hide columns">
                            <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                        </button>
                        <div class="att-search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="text" id="otSearch" placeholder="Search OT name or code" autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table att-table w-100 mb-0', 'id' => 'studentAttendanceTable']) !!}
                </div>

                <div class="att-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span>Showing <strong id="otCount">0</strong> items</span>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Per-OT Mark Attendance modal (opened by the fingerprint action) --}}
<div class="modal fade att-mark-page" id="markOtModal" tabindex="-1" aria-labelledby="markOtModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="markOtModalLabel">Mark Attendance - <span id="markOtName">OT Name</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <hr class="my-3">
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label fw-semibold">Attendance <span class="text-danger">*</span></label>
                    <div class="att-radio-group">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="ot_status_radio" id="otRadioPresent" value="1" checked>
                            <label class="form-check-label" for="otRadioPresent">Present</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="ot_status_radio" id="otRadioLate" value="2">
                            <label class="form-check-label" for="otRadioLate">Late</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="ot_status_radio" id="otRadioAbsent" value="3">
                            <label class="form-check-label" for="otRadioAbsent">Absent</label>
                        </div>
                    </div>
                </div>

                <div class="mark-ot-row">
                    <span>MDO Duty</span>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="otMdo">
                    </div>
                </div>

                <div class="mark-ot-section">Exemptions</div>

                <div class="mark-ot-row">
                    <span>Medical Exemptions</span>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="otMedical">
                    </div>
                </div>

                <div class="mark-ot-row">
                    <span>Other Exemptions</span>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="otOther">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" id="otMarkSave">Mark Attendance</button>
            </div>
        </div>
    </div>
</div>

{{-- Column visibility modal --}}
<div class="modal fade" id="attColumnModal" tabindex="-1" aria-labelledby="attColumnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="attColumnModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="attColumnGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
{!! $dataTable->scripts() !!}
<script>
$(function () {
    function updateCount() {
        $('#otCount').text($('#studentAttendanceTable tbody tr').filter(':visible').not(':has(td.dataTables_empty)').length);
    }

    // Bulk mark: apply to checked rows, or ALL rows when none are checked, then save.
    function bulkMark(status) {
        var $checked = $('#studentAttendanceTable tbody .ot-check:checked');
        var $targets = $checked.length ? $checked : $('#studentAttendanceTable tbody .ot-check');
        if (!$targets.length) { return; }
        $targets.each(function () {
            var pk = this.value;
            $('.ot-status[data-ot="' + pk + '"]').val(status);
        });
        document.getElementById('attMarkForm').submit();
    }
    $('#markPresentBulk').on('click', function () { bulkMark(1); });
    $('#markAbsentBulk').on('click', function () { bulkMark(3); });

    // Header select-all
    $(document).on('change', '#otCheckAll', function () {
        $('#studentAttendanceTable tbody .ot-check').prop('checked', this.checked);
    });
    $(document).on('change', '.ot-check', function () {
        var total = $('#studentAttendanceTable tbody .ot-check').length;
        var sel = $('#studentAttendanceTable tbody .ot-check:checked').length;
        $('#otCheckAll').prop('checked', total > 0 && sel === total);
    });

    // Client-side search over the loaded rows.
    $('#otSearch').on('input', function () {
        var q = this.value.toLowerCase();
        $('#studentAttendanceTable tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) > -1);
        });
        updateCount();
    });

    $('#studentAttendanceTable').on('draw.dt', updateCount);
    setTimeout(updateCount, 400);

    /* ── Per-OT Mark Attendance modal ── */
    var STATUS_MAP = {
        0: ['Not Marked', 'att-nm'], 1: ['Present', 'att-present'], 2: ['Late', 'att-late'],
        3: ['Absent', 'att-absent'], 4: ['MDO', 'att-duty'], 5: ['Escort', 'att-duty'],
        6: ['Medical', 'att-exempt'], 7: ['Other', 'att-exempt']
    };
    var markOtModalEl = document.getElementById('markOtModal');
    var markOtModal = (window.bootstrap && markOtModalEl) ? new bootstrap.Modal(markOtModalEl) : null;
    var currentOtPk = null;

    function updateOtBadge(pk, status) {
        $('.ot-status[data-ot="' + pk + '"]').val(status);
        var m = STATUS_MAP[status] || STATUS_MAP[0];
        $('.att-badge[data-ot="' + pk + '"]').text(m[0]).attr('class', 'att-badge ' + m[1]);
        // reflect new status on the fingerprint button too
        $('.js-mark-ot[data-ot="' + pk + '"]').attr('data-status', status);
    }

    // Open the modal, prefilled from the OT's current status.
    $(document).on('click', '.js-mark-ot', function () {
        if (!markOtModal) return;
        currentOtPk = String($(this).data('ot'));
        var status = parseInt($(this).attr('data-status'), 10) || 0;
        $('#markOtName').text($(this).data('name') || '');

        $('#otMdo, #otMedical, #otOther').prop('checked', false);
        $('input[name="ot_status_radio"][value="1"]').prop('checked', true);
        if (status === 2 || status === 3) {
            $('input[name="ot_status_radio"][value="' + status + '"]').prop('checked', true);
        } else if (status === 4) { $('#otMdo').prop('checked', true); }
        else if (status === 6) { $('#otMedical').prop('checked', true); }
        else if (status === 7) { $('#otOther').prop('checked', true); }

        markOtModal.show();
    });

    // MDO / Medical / Other are mutually exclusive (single status per OT).
    $('#otMdo, #otMedical, #otOther').on('change', function () {
        if (this.checked) { $('#otMdo, #otMedical, #otOther').not(this).prop('checked', false); }
    });

    $('#otMarkSave').on('click', function () {
        if (!currentOtPk) return;
        var status;
        if ($('#otMedical').is(':checked')) status = 6;
        else if ($('#otOther').is(':checked')) status = 7;
        else if ($('#otMdo').is(':checked')) status = 4;
        else status = parseInt($('input[name="ot_status_radio"]:checked').val(), 10) || 1;

        var data = {
            _token: '{{ csrf_token() }}',
            group_pk: $('#group_pk').val(),
            course_pk: $('#course_pk').val(),
            timetable_pk: $('#timetable_pk').val()
        };
        data['student[' + currentOtPk + ']'] = status;

        var $btn = $(this).prop('disabled', true);
        $.post('{{ route("attendance.save") }}', data)
            .done(function () {
                updateOtBadge(currentOtPk, status);
                if (markOtModal) markOtModal.hide();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Attendance saved', timer: 1200, showConfirmButton: false });
                }
            })
            .fail(function () {
                if (typeof Swal !== 'undefined') { Swal.fire('Error', 'Failed to save attendance.', 'error'); }
                else { alert('Failed to save attendance.'); }
            })
            .always(function () { $btn.prop('disabled', false); });
    });

    /* ── Column show / hide (DataTables visibility API) ── */
    var COL_KEY = 'attMarkGrid:hiddenCols:v1';
    function colGetHidden() {
        try { var a = JSON.parse(localStorage.getItem(COL_KEY) || '[]'); return Array.isArray(a) ? a : []; }
        catch (e) { return []; }
    }
    function colPersist(a) { try { localStorage.setItem(COL_KEY, JSON.stringify(a)); } catch (e) {} }

    function setupAttColumns() {
        if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#studentAttendanceTable')) { return false; }
        var dt = $('#studentAttendanceTable').DataTable();
        var idxs = dt.columns().indexes().toArray();
        if (!idxs.length) { return false; }

        // Locked (always visible): checkbox, S. No., Action (last column).
        var LOCKED = [0, 1, idxs[idxs.length - 1]];
        var hidden = colGetHidden().filter(function (i) { return LOCKED.indexOf(i) === -1; });
        colPersist(hidden);

        dt.columns().every(function () {
            var idx = this.index();
            if (LOCKED.indexOf(idx) !== -1) { this.visible(true, false); return; }
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#attColumnGrid').empty();
        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (idx === 0) { title = 'Checkbox'; }
            if (!title) { title = 'Column ' + (idx + 1); }
            var locked = LOCKED.indexOf(idx) !== -1;
            var inputId = 'attcol_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>').attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', locked || hidden.indexOf(idx) === -1)
                .prop('disabled', locked);
            $cb.on('change', function () {
                if (locked) return;
                var h = colGetHidden();
                var pos = h.indexOf(idx);
                if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else { if (pos === -1) h.push(idx); }
                colPersist(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });
            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
        return true;
    }

    var colTries = 0;
    var colTimer = setInterval(function () {
        colTries++;
        if (setupAttColumns() || colTries > 40) { clearInterval(colTimer); }
    }, 200);
});
</script>
@endsection
