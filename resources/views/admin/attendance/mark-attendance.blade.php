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

/* Update-attendance action = stacked icon + label (blue link) */
.att-mark-page .att-action-icon {
    display: inline-flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 2px; padding: 0.25rem 0.5rem; border-radius: 8px; color: #004a93;
    text-decoration: none; border: 0; background: transparent; cursor: pointer;
    line-height: 1.1; transition: background-color .15s ease;
}
.att-mark-page .att-action-icon i { font-size: 1.25rem; }
.att-mark-page .att-action-label { font-size: 0.72rem; font-weight: 600; white-space: nowrap; }
.att-mark-page .att-action-icon:hover { background: #eef3f9; }
.att-mark-page .att-action-icon:hover .att-action-label { text-decoration: underline; }

/* Breadcrumb */
.att-mark-page .att-crumb a { color: #667085; text-decoration: none; }
.att-mark-page .att-crumb a:hover { color: #004a93; text-decoration: underline; }
.att-mark-page .att-crumb-sep { color: #cbd2da; margin: 0 0.15rem; }
.att-mark-page .att-crumb-current { color: #101828; font-weight: 600; }

/* Session info cards (Course / Topic / Faculty / Date / Session Time) */
.att-mark-page .att-info-cards {
    display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.75rem;
}
.att-mark-page .att-info-card {
    display: flex; align-items: flex-start; gap: 0.65rem;
    background: #fff; border: 1px solid #e4e7ec; border-radius: 12px;
    padding: 0.85rem 0.95rem; min-width: 0;
}
.att-mark-page .att-info-ico {
    flex-shrink: 0; width: 2rem; height: 2rem; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    background: #eff4fb; color: #004a93; font-size: 1rem;
}
.att-mark-page .att-info-body { min-width: 0; }
.att-mark-page .att-info-label { font-size: 0.75rem; color: #667085; margin-bottom: 0.15rem; }
.att-mark-page .att-info-value {
    font-size: 0.9rem; font-weight: 700; color: #101828; line-height: 1.25;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
@media (max-width: 991.98px) { .att-mark-page .att-info-cards { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 575.98px) { .att-mark-page .att-info-cards { grid-template-columns: repeat(2, 1fr); } }

/* Context filter boxes (Course Name / Time Period) — display styled like dropdowns */
.att-mark-page .att-filter-box {
    display: inline-flex; align-items: center; gap: 0.6rem;
    height: 48px; padding: 0 0.85rem; background: #fff;
    border: 1px solid #d0d5dd; border-radius: 8px; color: #344054; max-width: 260px;
}
.att-mark-page .att-filter-label { font-size: 0.72rem; color: #667085; }
.att-mark-page .att-filter-value {
    font-size: 0.9rem; font-weight: 600; color: #101828;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.att-mark-page .att-filter-caret { color: #98a2b3; font-size: 0.8rem; margin-left: auto; }

/* Inline "Update Attendance Status" radios — keep them tidy in a narrow column */
.att-mark-page #studentAttendanceTable td .form-check-inline { margin-right: 0.85rem; }

.att-mark-page .att-footer {
    border-top: 1px solid #e4e7ec; padding: 0.875rem 1rem; color: #667085; font-size: 0.875rem;
}
</style>
@endpush

@section('setup_content')
@php
    $courseName = optional($courseGroup->course)->course_name ?? 'OT';
    $topicRaw = optional($courseGroup->timetable)->subject_topic;
    $topicName = $topicRaw ? trim(preg_replace('/\s+/u', ' ', strip_tags((string) $topicRaw))) : 'N/A';
    $topicName = $topicName !== '' ? $topicName : 'N/A';
    $facultyName = ($facultyName ?? '') !== '' ? $facultyName : 'N/A';
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
                    <nav class="att-crumb" aria-label="breadcrumb">
                        <a href="{{ route('admin.dashboard') }}">Home</a>
                        <span class="att-crumb-sep">/</span> Time Table
                        <span class="att-crumb-sep">/</span> <a href="{{ route('attendance.index') }}">Attendance</a>
                        <span class="att-crumb-sep">/</span> <span class="att-crumb-current">Topic wise Attendance</span>
                    </nav>
                    <h4 class="att-title" title="{{ $courseName }}">{{ $courseName }}'s Attendance</h4>
                </div>
            </div>
            @if($canMark)
            <button type="button" class="btn btn-primary att-btn-primary px-4" id="markAllBtn">
                <i class="bi bi-check2-circle me-1"></i> Mark Attendance
            </button>
            @endif
        </div>
    </div>

    {{-- Session info cards --}}
    @php
        $infoCards = [
            ['label' => 'Course Name',  'value' => $courseName],
            ['label' => 'Topic Name',   'value' => $topicName],
            ['label' => 'Faculty Name', 'value' => $facultyName],
            ['label' => 'Topic Date',   'value' => $sessionDate],
            ['label' => 'Session Time', 'value' => $sessionTime],
        ];
    @endphp
    <div class="att-info-cards mb-3">
        @foreach($infoCards as $card)
        <div class="att-info-card">
            <span class="att-info-ico"><i class="bi bi-journal-text"></i></span>
            <div class="att-info-body">
                <div class="att-info-label">{{ $card['label'] }}</div>
                <div class="att-info-value" title="{{ $card['value'] }}">{{ $card['value'] }}</div>
            </div>
        </div>
        @endforeach
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

                {{-- Filter bar (context for this session — Course + Time Period) --}}
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="fw-semibold text-body-secondary me-1">Filters</span>
                    <div class="att-filter-box" title="{{ $courseName }}">
                        <span class="att-filter-label">Course Name</span>
                        <span class="att-filter-value">{{ \Illuminate\Support\Str::limit($courseName, 26) }}</span>
                        <i class="bi bi-chevron-down att-filter-caret" aria-hidden="true"></i>
                    </div>
                    <div class="att-filter-box">
                        <span class="att-filter-label">Time Period</span>
                        <span class="att-filter-value">{{ $sessionDate }}</span>
                        <i class="bi bi-chevron-down att-filter-caret" aria-hidden="true"></i>
                    </div>
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

    // "Mark Attendance" saves every OT's inline Present/Late/Absent selection at once.
    $('#markAllBtn').on('click', function () {
        var form = document.getElementById('attMarkForm');
        if (form) { form.submit(); }
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

    /* ── Per-OT save (Update Attendance action) ── */
    var STATUS_MAP = {
        0: ['Not Marked', 'att-nm'], 1: ['Present', 'att-present'], 2: ['Late', 'att-late'],
        3: ['Absent', 'att-absent'], 4: ['MDO', 'att-duty'], 5: ['Escort', 'att-duty'],
        6: ['Medical', 'att-exempt'], 7: ['Other', 'att-exempt']
    };

    function updateOtBadge(pk, status) {
        var m = STATUS_MAP[status] || STATUS_MAP[0];
        $('.att-badge[data-ot="' + pk + '"]').text(m[0]).attr('class', 'att-badge ' + m[1]);
        $('.js-mark-ot[data-ot="' + pk + '"]').attr('data-status', status);
        var $radio = $('input[name="student[' + pk + ']"][value="' + status + '"]');
        if ($radio.length) { $radio.prop('checked', true); }
    }

    // "Update Attendance" saves THIS OT's inline Present/Late/Absent selection via AJAX
    // and refreshes its Current Status pill — no modal.
    $(document).on('click', '.js-mark-ot', function () {
        var pk = String($(this).data('ot'));
        var $checked = $('input[name="student[' + pk + ']"]:checked');
        var status = $checked.length ? (parseInt($checked.val(), 10) || 1) : 1;

        var data = {
            _token: '{{ csrf_token() }}',
            group_pk: $('#group_pk').val(),
            course_pk: $('#course_pk').val(),
            timetable_pk: $('#timetable_pk').val()
        };
        data['student[' + pk + ']'] = status;

        var $btn = $(this).prop('disabled', true);
        $.post('{{ route("attendance.save") }}', data)
            .done(function () {
                updateOtBadge(pk, status);
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

        // Locked (always visible): S. No. and the Action column (last).
        var LOCKED = [idxs[0], idxs[idxs.length - 1]];
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
