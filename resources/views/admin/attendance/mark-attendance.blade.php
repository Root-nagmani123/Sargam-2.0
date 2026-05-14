@extends('admin.layouts.master')

@section('title', 'Attendance')
@section('css')
<style>
    /* ========================================================
       MARK ATTENDANCE PAGE
    ======================================================== */

    /* --- Filter pills --- */
    .att-pill { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #dee2e6; border-radius: 6px; background: #fff; padding: 5px 12px; font-size: 0.8125rem; color: #495057; white-space: nowrap; cursor: default; }
    .att-pill .att-pill-label { font-size: .72rem; font-weight: 600; color: #6c757d; display: block; line-height: 1; margin-bottom: 2px; }
    .att-pill .att-pill-value { font-size: 0.8125rem; color: #212529; font-weight: 500; }

    /* --- Table --- */
    #studentAttendanceTable thead th { background-color: #f8f9fa; font-size: 0.8125rem; font-weight: 600; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 10px 14px; white-space: nowrap; border-top: none; }
    #studentAttendanceTable tbody td { font-size: 0.875rem; padding: 11px 14px; vertical-align: middle; border-bottom: 1px solid #f1f3f5; color: #212529; }
    #studentAttendanceTable tbody tr:hover td { background-color: #fafbfc; }
    #studentAttendanceTable.dataTable { border-collapse: collapse !important; }

    /* --- Attendance status badges --- */
    .att-badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
    .att-badge-present    { background: #d1e7dd; color: #0a3622; }
    .att-badge-late       { background: #fff3cd; color: #664d03; }
    .att-badge-absent     { background: #f8d7da; color: #58151c; }
    .att-badge-notmarked  { background: #fef3cd; color: #856404; }
    .att-badge-mdo        { background: #cfe2ff; color: #084298; }
    .att-badge-exempt     { background: #e2d9f3; color: #432874; }

    /* --- Action icon btn --- */
    .att-icon-btn { display: inline-flex; align-items: center; justify-content: center; background: none; border: none; padding: 4px 8px; border-radius: 6px; text-decoration: none; transition: background .15s; cursor: pointer; }
    .att-icon-btn:hover { background: #eef2f7; }

    /* --- DataTables chrome --- */
    .dataTables_wrapper .dataTables_filter { display: none; }
    .dataTables_wrapper .dataTables_length { display: none; }
    .dataTables_wrapper .dataTables_info   { font-size: 0.8125rem; color: #6c757d; }
    /* Pagination */
    .dataTables_wrapper .dataTables_paginate { margin-top: 2px; }
    .dataTables_wrapper .dataTables_paginate .paginate_button { border: none !important; border-radius: 4px !important; margin: 0 1px; font-size: 0.8125rem !important; padding: 5px 10px !important; color: #1b3a5c !important; background: transparent !important; box-shadow: none !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { background: #1b3a5c !important; color: #fff !important; border: none !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #f1f3f5 !important; color: #1b3a5c !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover { opacity: .35; background: transparent !important; }

    /* Per-page select in bottom row */
    #smaLengthSelect { height: 30px; font-size: 0.8125rem; border-radius: 6px; border: 1px solid #dee2e6; padding: 0 8px; }

    /* --- Mark Attendance Modal --- */
    #studentMarkModal .modal-content { border-radius: 16px !important; }
    #studentMarkModal .sma-att-radio .form-check-input { width: 18px; height: 18px; accent-color: #1b3a5c; cursor: pointer; margin-top: 0; }
    #studentMarkModal .sma-att-radio .form-check-label { font-size: 0.875rem; cursor: pointer; }
    #studentMarkModal .sma-switch-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f1f3; }
    #studentMarkModal .sma-switch-row:last-child { border-bottom: none; }
    #studentMarkModal .sma-switch-row .form-check { margin: 0; padding: 0; }
    #studentMarkModal .sma-switch-row .form-check-input { width: 44px; height: 24px; cursor: pointer; }
    #studentMarkModal .sma-switch-row .form-check-input:checked { background-color: #6f4cec; border-color: #6f4cec; }
    #studentMarkModal #smaMdo:checked { background-color: #1b3a5c; border-color: #1b3a5c; }

    @media print { .no-print { display: none !important; } }
</style>
@endsection

@section('setup_content')
<div class="container-fluid py-3 px-3 px-lg-4">
    @if(hasRole('Admin') || hasRole('Training-Induction'))
    <x-breadcrum title="Mark Attendance Of Officer Trainees">
        <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-2 d-inline-flex align-items-center gap-1">
            <span class="material-symbols-rounded" style="font-size:16px;">arrow_back</span> Back
        </a>
    </x-breadcrum>
    <x-session_message />
    @endif
    @if(hasRole('Internal Faculty'))
    <x-breadcrum title="Mark Attendance Of Your Assigned Officer Trainees">
        <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-2 d-inline-flex align-items-center gap-1">
            <span class="material-symbols-rounded" style="font-size:16px;">arrow_back</span> Back
        </a>
    </x-breadcrum>
    <x-session_message />
    @endif

    {{-- Top toolbar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 no-print">
        <div>{{-- spacer --}}</div>
        <a href="{{ route('attendance.export', ['group_pk' => $group_pk, 'course_pk' => $course_pk, 'timetable_pk' => $courseGroup->timetable_pk]) }}"
            class="btn btn-outline-primary text-decoration-none d-inline-flex align-items-center gap-1">
            <span class="material-symbols-rounded" style="font-size:18px;color:#1b3a5c;">download</span>
            <span class="fw-semibold">Download</span>
        </a>
    </div>

    {{-- Card --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-3 p-lg-4">

            {{-- Filter pills --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
                <span class="text-muted fw-semibold small">Filters</span>

                <div class="att-pill">
                    <div>
                        <span class="att-pill-label">Course Name</span>
                        <span class="att-pill-value">{{ Str::limit(optional($courseGroup->course)->course_name ?? 'N/A', 32) }}</span>
                    </div>
                    <span class="material-symbols-rounded" style="font-size:16px;color:#6c757d;">expand_more</span>
                </div>

                <div class="att-pill">
                    <div>
                        <span class="att-pill-label">Time Period</span>
                        <span class="att-pill-value">
                            @if(!empty(optional($courseGroup->timetable)->START_DATE))
                                {{ \Carbon\Carbon::parse($courseGroup->timetable->START_DATE)->format('d/m/Y') }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    <span class="material-symbols-rounded" style="font-size:16px;color:#6c757d;">expand_more</span>
                </div>
            </div>

            {{-- DataTable --}}
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table w-100']) !!}
            </div>

            {{-- Bottom row: pagination left | per-page + total right --}}
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3 no-print" id="smaBottomRow">
                <div id="smaPaginationHolder"></div>
                <div class="d-flex align-items-center gap-1">
                    <span class="small text-muted">Showing</span>
                    <select id="smaLengthSelect" class="form-select form-select-sm" style="width:80px;">
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200" selected>200</option>
                        <option value="500">500</option>
                        <option value="-1">All</option>
                    </select>
                    <span class="small text-muted" id="smaTotalInfo"></span>
                </div>
            </div>

            {{-- Back / Save buttons --}}
            <div class="d-flex justify-content-end align-items-center flex-wrap gap-2 mt-3 no-print">
            </div>

        </div>
    </div>
</div>

{{-- ====================================================
     Mark Attendance Modal (single student)
===================================================== --}}
<div class="modal fade" id="studentMarkModal" tabindex="-1" aria-labelledby="studentMarkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content border-0 shadow-lg">

            {{-- Header --}}
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="studentMarkModalLabel">Mark Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body px-4 pt-3 pb-0">

                {{-- Student name/code (shown when available) --}}
                <div class="mb-3 d-none" id="smaStudentHeader">
                    <strong id="smaStudentName" class="d-block" style="font-size:0.875rem;color:#1b3a5c;"></strong>
                    <span id="smaStudentCode" class="text-muted" style="font-size:0.75rem;"></span>
                </div>

                {{-- Attendance radios --}}
                <p class="fw-semibold mb-2" style="font-size:0.875rem;">Attendance<span class="text-danger">*</span></p>
                <div class="d-flex align-items-center gap-4 mb-3 sma-att-radio">
                    <div class="form-check d-flex align-items-center gap-1 m-0 p-0">
                        <input class="form-check-input m-0" type="radio" name="sma_att" id="smaPresent" value="1" checked>
                        <label class="form-check-label" for="smaPresent">Present</label>
                    </div>
                    <div class="form-check d-flex align-items-center gap-1 m-0 p-0">
                        <input class="form-check-input m-0" type="radio" name="sma_att" id="smaLate" value="2">
                        <label class="form-check-label" for="smaLate">Late</label>
                    </div>
                    <div class="form-check d-flex align-items-center gap-1 m-0 p-0">
                        <input class="form-check-input m-0" type="radio" name="sma_att" id="smaAbsent" value="3">
                        <label class="form-check-label" for="smaAbsent">Absent</label>
                    </div>
                </div>

                {{-- MDO Duty toggle --}}
                <div class="sma-switch-row">
                    <label class="form-label mb-0" for="smaMdo" style="font-size:0.9rem;">MDO Duty</label>
                    <div class="form-check form-switch m-0 p-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="smaMdo">
                    </div>
                </div>

                {{-- Exemptions section title --}}
                <p class="fw-bold mb-0 pt-3" style="font-size:0.9375rem;">Exemptions</p>

                {{-- Medical Exemptions toggle --}}
                <div class="sma-switch-row">
                    <label class="form-label mb-0" for="smaMedical" style="font-size:0.9rem;">Medical Exemptions</label>
                    <div class="form-check form-switch m-0 p-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="smaMedical">
                    </div>
                </div>

                {{-- Other Exemptions toggle --}}
                <div class="sma-switch-row">
                    <label class="form-label mb-0" for="smaOther" style="font-size:0.9rem;">Other Exemptions</label>
                    <div class="form-check form-switch m-0 p-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="smaOther">
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="modal-footer border-0 px-4 pb-4 pt-3 gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-secondary px-4 rounded-2"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn px-4 rounded-2 fw-semibold" id="smaSaveBtn"
                    style="background:#1b3a5c;color:#fff;">Mark Attendance</button>
            </div>

        </div>
    </div>
</div>

@endsection

@section('scripts')
{!! $dataTable->scripts() !!}
<script>
(function () {
    var currentStudentId  = null;
    var groupPk           = '{{ $group_pk }}';
    var coursePk          = '{{ $course_pk }}';
    var timetablePk       = '{{ $courseGroup->timetable_pk }}';

    // Move DataTables pagination into our custom bottom-row container after each draw
    var dt;
    $(document).on('init.dt', '#studentAttendanceTable', function () {
        dt = $(this).DataTable();
        movePagination();
        updateTotalInfo();

        // Per-page select
        $('#smaLengthSelect').off('change').on('change', function () {
            dt.page.len(parseInt($(this).val())).draw();
        });
    });
    $(document).on('draw.dt', '#studentAttendanceTable', function () {
        movePagination();
        updateTotalInfo();
        // Keep select in sync
        var len = dt ? dt.page.len() : 200;
        $('#smaLengthSelect').val(len == -1 ? '-1' : len);
    });

    function movePagination() {
        var $pag = $('#studentAttendanceTable_wrapper .dataTables_paginate');
        if ($pag.length) $('#smaPaginationHolder').html('').append($pag);
    }

    function updateTotalInfo() {
        if (!dt) return;
        var info = dt.page.info();
        var total = info.recordsTotal || info.recordsDisplay || 0;
        $('#smaTotalInfo').text('of ' + total.toLocaleString() + ' items');
    }

    // Select-all checkbox
    $(document).on('change', '#selectAllStudents', function () {
        $('.student-checkbox').prop('checked', this.checked);
    });

    // Open modal when fingerprint button is clicked
    $(document).on('click', '.att-mark-student-btn', function () {
        currentStudentId = $(this).data('student-id');
        var name = $(this).data('student-name') || '';
        var code = $(this).data('student-code') || '';
        var att  = parseInt($(this).data('att')) || 1;
        var mdo  = parseInt($(this).data('mdo')) || 0;
        var med  = parseInt($(this).data('med')) || 0;
        var oth  = parseInt($(this).data('oth')) || 0;

        if (name) {
            $('#smaStudentName').text(name);
            $('#smaStudentCode').text(code ? '(' + code + ')' : '');
            $('#smaStudentHeader').removeClass('d-none');
        } else {
            $('#smaStudentHeader').addClass('d-none');
        }

        $('input[name="sma_att"]').prop('checked', false);
        $('input[name="sma_att"][value="' + att + '"]').prop('checked', true);
        $('#smaMdo').prop('checked', mdo === 1);
        $('#smaMedical').prop('checked', med === 1);
        $('#smaOther').prop('checked', oth === 1);

        $('#smaSaveBtn').prop('disabled', false).text('Mark Attendance');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('studentMarkModal')).show();
    });

    // Save button
    $('#smaSaveBtn').on('click', function () {
        if (!currentStudentId) return;

        var status = $('input[name="sma_att"]:checked').val() || '1';
        if ($('#smaOther').is(':checked'))        status = '7';
        else if ($('#smaMedical').is(':checked')) status = '6';
        else if ($('#smaMdo').is(':checked'))     status = '4';

        var studentData = {};
        studentData[currentStudentId] = status;

        $('#smaSaveBtn').prop('disabled', true).text('Saving...');

        $.ajax({
            url: '{{ route("attendance.save") }}',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                group_pk:     groupPk,
                course_pk:    coursePk,
                timetable_pk: timetablePk,
                student:      studentData
            },
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('studentMarkModal')).hide();
                $('#smaSaveBtn').prop('disabled', false).text('Mark Attendance');
                if (dt) dt.ajax.reload(null, false);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Saved!', text: 'Attendance marked successfully.', timer: 1800, showConfirmButton: false });
                }
            },
            error: function (xhr) {
                $('#smaSaveBtn').prop('disabled', false).text('Mark Attendance');
                var msg = 'Failed to save attendance.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                } else { alert(msg); }
            }
        });
    });
})();
</script>
@endsection