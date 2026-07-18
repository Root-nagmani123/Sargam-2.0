@extends('admin.layouts.master')

@section('title', 'Mark Attendance')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
.att-mark-page .att-header-card { border: 0; border-radius: 14px; }
.att-mark-page .att-back {
    width: 36px; height: 36px; border-radius: 10px;
    display: inline-flex; align-items: center; justify-content: center;
    color: #101828; text-decoration: none; font-size: 1.15rem;
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

/* Why this row's status is stuck on Present (OT is on duty / exempt). */
.att-mark-page .att-lock-note {
    display: flex; align-items: center; gap: 0.3rem; margin-top: 0.25rem;
    color: #004a93; font-size: 0.75rem; font-weight: 500;
}
.att-mark-page .att-lock-note i { font-size: 0.6875rem; }

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
    overflow-wrap: anywhere;
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

    {{-- Print / Download --}}
    <div class="d-flex justify-content-end gap-2 mb-2">
        <button type="button" class="att-btn-outline border-0" id="attPrintBtn">
            <i class="bi bi-printer"></i> Print
        </button>
        <div class="dropdown">
            <button type="button" class="att-btn-outline border-0 dropdown-toggle" id="attDownloadBtn"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download"></i> Download
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm py-2" aria-labelledby="attDownloadBtn">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                        href="{{ route('attendance.export', ['group_pk' => $group_pk, 'course_pk' => $course_pk, 'timetable_pk' => $courseGroup->timetable_pk, 'format' => 'pdf']) }}">
                        <i class="bi bi-file-earmark-pdf text-danger"></i> <span>Download PDF</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                        href="{{ route('attendance.export', ['group_pk' => $group_pk, 'course_pk' => $course_pk, 'timetable_pk' => $courseGroup->timetable_pk, 'format' => 'excel']) }}">
                        <i class="bi bi-file-earmark-spreadsheet text-success"></i> <span>Download Excel</span>
                    </a>
                </li>
            </ul>
        </div>
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

    /* ── Print (client-side; mirrors the .xlsx header and honours column visibility + search) ── */
    $('#attPrintBtn').on('click', function () {
        var table = document.getElementById('studentAttendanceTable');
        if (!table) { alert('Table not found!'); return; }
        var printWindow = window.open('', '_blank');
        if (!printWindow) { alert('Please allow pop-ups for this site to print the attendance sheet.'); return; }

        var clone = table.cloneNode(true);

        // Drop the interactive columns — radios and buttons mean nothing on paper.
        var drop = [];
        $(clone).find('thead th').each(function (i) {
            var t = $(this).text().trim().toLowerCase();
            if (t === 'update attendance status' || t === 'action') { drop.push(i); }
        });
        // Count the columns up front: removing cells shrinks the header as we go.
        var colCount = $(clone).find('thead th').length;
        $(clone).find('tr').each(function () {
            var cells = this.children;
            if (cells.length !== colCount) { return; } // skip the "no data" row (single colspan cell)
            for (var i = drop.length - 1; i >= 0; i--) {
                if (cells[drop[i]]) { cells[drop[i]].remove(); }
            }
        });

        var logoLeft  = @json(asset('admin_assets/images/logos/logo_new.png'));
        var logoRight = @json(file_exists(public_path('admin_assets/images/logos/constitution-75.png'))
            ? asset('admin_assets/images/logos/constitution-75.png')
            : asset('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png'));
        var titleHindi = @json(asset('admin_assets/images/logos/lbsnaa-title-hi.png'));

        // Same session context the .xlsx header block carries.
        var meta = [
            { label: 'Course',  value: @json($courseName) },
            { label: 'Topic',   value: @json($topicName) },
            { label: 'Faculty', value: @json($facultyName) }
        ].filter(function (m) { return m.value && m.value !== 'N/A'; })
         .map(function (m) { return m.label + ': ' + m.value; }).join('&nbsp; | &nbsp;');

        var session = [
            { label: 'Topic Date',   value: @json($sessionDate) },
            { label: 'Session Time', value: @json($sessionTime) }
        ].filter(function (m) { return m.value && m.value !== 'N/A'; })
         .map(function (m) { return m.label + ': ' + m.value; }).join('&nbsp; | &nbsp;');

        var printContent =
            '<!DOCTYPE html><html><head><title>Attendance Report - Print</title><style>' +
            'body{font-family:Arial,sans-serif;margin:16px;color:#1f2937;}' +
            '.pdf-hdr{width:100%;border-collapse:collapse;margin-bottom:4px;}' +
            '.pdf-hdr td{vertical-align:middle;} .pdf-hdr .logo{width:90px;text-align:center;}' +
            '.pdf-hdr .logo img{max-height:64px;max-width:84px;} .pdf-hdr .center{text-align:center;padding:0 8px;}' +
            '.pdf-hdr .inst-hi-img{height:18px;width:auto;margin-bottom:2px;}' +
            '.pdf-hdr .inst-en{font-size:16px;font-weight:bold;color:#102a43;line-height:1.25;}' +
            '.report-title{text-align:center;font-size:20px;font-weight:bold;color:#004a93;margin:8px 0 6px;padding-bottom:8px;border-bottom:2px solid #004a93;}' +
            '.print-info{margin-bottom:12px;font-size:11px;color:#666;text-align:center;line-height:1.6;}' +
            'table{width:100%;border-collapse:collapse;margin-top:10px;}' +
            'table th,table td{border:1px solid #8fa3bd;padding:6px 8px;text-align:left;font-size:12px;}' +
            'table thead th{font-weight:bold;background-color:#004a93 !important;color:#fff !important;text-align:center;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            'table tbody tr:nth-child(even){background-color:#eef2f8;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            '.att-badge{display:inline-block;padding:3px 8px;border-radius:4px;font-weight:600;font-size:11px;' +
                '-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            '.att-present{color:#027a48;background:#ecfdf3;} .att-absent{color:#b42318;background:#fef3f2;}' +
            '.att-nm{color:#b54708;background:#fffaeb;} .att-late{color:#b54708;background:#fff6ed;}' +
            '.att-duty{color:#004a93;background:#eff8ff;} .att-exempt{color:#475467;background:#f2f4f7;}' +
            '.print-footer{margin-top:18px;text-align:center;font-size:10px;color:#666;border-top:1px solid #ccc;padding-top:10px;}' +
            '@media print{@page{size:A4 landscape;margin:10mm;} body{margin:0;}}' +
            '</style></head><body onload="window.focus();window.print();">' +
            '<table class="pdf-hdr"><tr>' +
                '<td class="logo"><img src="' + logoLeft + '" alt=""></td>' +
                '<td class="center"><img class="inst-hi-img" src="' + titleHindi + '" alt="">' +
                    '<div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>' +
                '</td>' +
                '<td class="logo"><img src="' + logoRight + '" alt=""></td>' +
            '</tr></table>' +
            '<div class="report-title">Attendance Report</div>' +
            '<div class="print-info">' +
                (meta ? '<div>' + meta + '</div>' : '') +
                (session ? '<div>' + session + '</div>' : '') +
                '<div>Generated on ' + new Date().toLocaleString() + '</div>' +
            '</div>' +
            clone.outerHTML +
            '<div class="print-footer"><p>Lal Bahadur Shastri National Academy of Administration, Mussoorie</p></div>' +
            '</body></html>';

        printWindow.document.open();
        printWindow.document.write(printContent);
        printWindow.document.close();
    });

    /* ── Locked OTs (on duty / exempt for this session) ──────────────────────
       An OT with MDO, Escort/Moderator or Other duty, or a medical exemption
       overlapping this session, is always Present and the status cannot be
       changed. The radios are left enabled on purpose — a disabled input fires
       no event, so the marker would just see the click do nothing. Instead we
       put the selection back and say which duty is holding it.

       AttendanceController::save enforces the same rule; this is only the
       explanation. */
    $(document).on('change', 'input[data-att-lock]', function () {
        var reason = $(this).attr('data-att-lock');
        var pk = String($(this).attr('data-att-ot'));

        if (parseInt(this.value, 10) === 1) {
            return; // Present is the only value a locked OT may hold.
        }

        $('input[name="student[' + pk + ']"][value="1"]').prop('checked', true);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Status cannot be changed',
                text: 'This OT has ' + reason + ' for this session, so attendance stays Present.'
            });
        } else {
            alert('This OT has ' + reason + ' for this session, so attendance stays Present.');
        }
    });

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
    // Hidden columns persist by column TITLE. The old v1 key stored numeric indexes,
    // which kept hiding whichever column later landed on that index — bumping the key
    // discards that stale state.
    var COL_KEY = 'attMarkGrid:hiddenCols:v2';
    try { localStorage.removeItem('attMarkGrid:hiddenCols:v1'); } catch (e) {}

    function colTitle(col) {
        var t = $(col.header()).text().replace(/\s+/g, ' ').trim();
        return t || ('Column ' + (col.index() + 1));
    }
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
        var hidden = colGetHidden();

        dt.columns().every(function () {
            var idx = this.index();
            if (LOCKED.indexOf(idx) !== -1) { this.visible(true, false); return; }
            this.visible(hidden.indexOf(colTitle(this)) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#attColumnGrid').empty();
        dt.columns().every(function () {
            var idx = this.index();
            var title = colTitle(this);
            var locked = LOCKED.indexOf(idx) !== -1;
            var inputId = 'attcol_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>').attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', locked || hidden.indexOf(title) === -1)
                .prop('disabled', locked);
            $cb.on('change', function () {
                if (locked) return;
                var h = colGetHidden();
                var pos = h.indexOf(title);
                if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else { if (pos === -1) h.push(title); }
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
