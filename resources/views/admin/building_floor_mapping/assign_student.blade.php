@extends('admin.layouts.master')

@section('title', 'Hostel Building Assign Student')

@section('setup_content')
<div class="container-fluid assign-student-index">

    <x-breadcrum title="Assign Student Hostel">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" id="asImportOpen"
                class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap"
                data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">upload_file</i>
                <span>Assign Student Hostel via Import</span>
            </button>
        </div>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" id="asPrintBtn"
            class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 px-3 fw-semibold text-nowrap"
            style="border:0; background-color:#fff; color:var(--bs-primary);">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">print</i>
            <span>Print</span>
        </button>
        <a href="{{ route('hostel.building.map.export') }}"
            class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 px-3 fw-semibold text-nowrap"
            style="border:0; background-color:#fff; color:var(--bs-primary);">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">download</i>
            <span>Download</span>
        </a>
    </div>

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="ds-card assign-student-card">
            <div class="ds-card-body">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table align-middle mb-0 w-100']) !!}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

{{-- Assign Student Hostel via Import — 2-step wizard.
     All existing JS hooks preserved: #importExcelForm, #importFile (name=file),
     #upload_import_hostel_mapping_to_student (real import, kept hidden),
     #importErrors / #importErrorTableBody, .btn-cancel. --}}
<div class="modal fade as-import-modal" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" id="importExcelForm">
                @csrf
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-semibold" id="importModalLabel">Assign Student Hostel via Import</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-0">
                    <hr class="mt-0 mb-3">

                    {{-- Progress --}}
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="progress flex-grow-1" style="height:6px;">
                            <div class="progress-bar" id="asImportProgress" role="progressbar" style="width:50%;"></div>
                        </div>
                        <span class="small text-secondary" id="asImportProgressPct">50%</span>
                    </div>

                    {{-- Step 1: dropzone --}}
                    <div id="asImportStep1">
                        <label for="importFile" class="as-dropzone" id="asDropzone">
                            <input type="file" name="file" id="importFile" class="d-none" accept=".xlsx, .xls, .csv" required>
                            <i class="material-icons material-symbols-rounded as-dropzone-icon" aria-hidden="true">description</i>
                            <div class="as-dropzone-text">Drag or click here to upload your file</div>
                            <div class="as-dropzone-hint">
                                Allowed: .xlsx, .xls, .csv | Max ~500 MB |
                                <a href="{{ asset('admin_assets/sample/ot_hostel_excel_upload.xlsx') }}" download
                                   onclick="event.stopPropagation();">Sample File</a>
                            </div>
                            <div class="as-dropzone-file d-none" id="asSelectedFile"></div>
                        </label>
                    </div>

                    {{-- Step 2: preview --}}
                    <div id="asImportStep2" class="d-none">
                        <div class="table-responsive as-preview-wrap">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center">S. No.</th>
                                        <th>Course Name</th>
                                        <th>User Name</th>
                                        <th>Hotel Room Name</th>
                                    </tr>
                                </thead>
                                <tbody id="asPreviewBody"></tbody>
                            </table>
                        </div>
                        <div id="asPreviewNote" class="small text-secondary mt-2"></div>
                    </div>

                    {{-- Validation errors (populated by existing import JS) --}}
                    <div id="importErrors" class="alert d-none mt-3">
                        <h6 class="text-center mb-3 fw-semibold">
                            <i class="mdi mdi-alert-circle-outline"></i> Validation Errors Found
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 10%;">Row</th>
                                        <th>Errors</th>
                                    </tr>
                                </thead>
                                <tbody id="importErrorTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4 d-none me-auto" id="asImportBack">
                        <i class="material-icons material-symbols-rounded fs-6 lh-1 align-middle" aria-hidden="true">arrow_back</i> Back
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-cancel rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary rounded-3 px-4" id="asImportNext" disabled>Next</button>
                    <button type="button" class="btn btn-primary rounded-3 px-4 d-none" id="asImportAssign">Assign Students Hostel</button>
                    {{-- Real importer trigger (kept for the existing custom.js handler) --}}
                    <button type="button" id="upload_import_hostel_mapping_to_student" class="d-none">
                        <i class="mdi mdi-upload"></i> Upload &amp; Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Clean, government-portal style table presentation */
    .assign-student-index #othostelroomdetails-table thead th {
        background: var(--ds-surface-2, #f8fafc);
        color: var(--ds-ink-muted, #64748b);
        font-size: 0.8125rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        border-bottom: 1px solid var(--ds-line, #e5e7eb);
        white-space: nowrap;
        vertical-align: middle;
    }
    .assign-student-index #othostelroomdetails-table tbody td {
        font-size: 0.9rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--ds-line, #eef1f4);
    }
    .assign-student-index #othostelroomdetails-table tbody tr { transition: background-color 0.15s ease; }
    .assign-student-index #othostelroomdetails-table tbody tr:hover { background-color: rgba(var(--bs-primary-rgb, 0 74 147), 0.04); }

    /* Status pill badges (emitted by the DataTable) */
    .assign-student-index .as-badge { padding: 0.4em 0.85em; font-size: 0.78rem; font-weight: 600; letter-spacing: 0.01em; }
    .assign-student-index .as-badge-active { color: #157347; background-color: #d6f5e3; }
    .assign-student-index .as-badge-inactive { color: #b02a37; background-color: #fcdcdf; }

    /* Status toggle in the action cell — green when on */
    .assign-student-index .as-row-switch { padding-left: 2.4em; min-height: auto; }
    .assign-student-index .as-row-switch .form-check-input { width: 2.1em; height: 1.15em; cursor: pointer; margin-top: 0.15em; }
    .assign-student-index .as-row-switch .form-check-input:checked { background-color: #1fae5b; border-color: #1fae5b; }
    .assign-student-index .as-row-switch .form-check-input:focus { box-shadow: 0 0 0 0.2rem rgba(31, 174, 91, 0.2); border-color: #1fae5b; }

    /* ---- Relocated DataTables chrome: toolbar + footer ---- */
    .assign-student-index .as-toolbar { margin-bottom: var(--ds-space-3, 1rem); }
    .assign-student-index .as-footer { margin-top: var(--ds-space-3, 1rem); }

    .assign-student-index .as-toolbar .dataTables_filter { margin: 0; }
    .assign-student-index .as-toolbar .dataTables_filter label { margin: 0; font-size: 0; display: block; }
    .assign-student-index .as-toolbar .dataTables_filter input {
        width: 280px; max-width: 100%; height: 42px; margin: 0 !important;
        font-size: 0.9rem; color: var(--ds-ink, #344054);
        border: 1px solid var(--ds-line, #e5e7eb); border-radius: 0.65rem;
        padding: 0.5rem 0.9rem 0.5rem 2.4rem; background-color: #fff;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%2398a2b3'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: 0.85rem center; background-size: 1rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .assign-student-index .as-toolbar .dataTables_filter input::placeholder { color: #98a2b3; }
    .assign-student-index .as-toolbar .dataTables_filter input:focus {
        outline: 0; border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb, 0 74 147), 0.15);
    }

    .assign-student-index .as-btn {
        height: 42px; padding: 0 0.95rem; border-radius: 0.65rem;
        font-size: 0.875rem; font-weight: 600; line-height: 1;
        color: var(--ds-ink, #344054); background: #fff;
        border: 1px solid var(--ds-line, #e5e7eb);
        box-shadow: var(--ds-shadow-sm, 0 1px 2px rgba(16, 24, 40, 0.05));
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .assign-student-index .as-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: var(--bs-primary); }
    .assign-student-index .as-btn .material-symbols-rounded { font-size: 18px; }

    .assign-student-index .as-footer .dataTables_paginate { margin: 0; }
    .assign-student-index .as-footer .pagination { margin: 0; gap: 0.3rem; align-items: center; flex-wrap: wrap; }
    .assign-student-index .as-footer .page-item { margin: 0; }
    .assign-student-index .as-footer .page-link {
        min-width: 2.1rem; height: 2.1rem; display: inline-flex; align-items: center; justify-content: center;
        padding: 0 0.55rem; border: 1px solid transparent; border-radius: 0.6rem;
        background: transparent; color: #475467; font-size: 0.875rem; font-weight: 600; box-shadow: none;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .assign-student-index .as-footer .page-link:hover { background: #f1f5f9; color: var(--bs-primary); }
    .assign-student-index .as-footer .page-item.active .page-link { background: #fff; border-color: var(--bs-primary); color: var(--bs-primary); }
    .assign-student-index .as-footer .page-item.disabled .page-link { color: #cbd5e1; background: transparent; }
    .assign-student-index .as-footer .paginate_button.previous .page-link,
    .assign-student-index .as-footer .paginate_button.next .page-link { font-size: 0; }
    .assign-student-index .as-footer .paginate_button.previous .page-link::before { content: '\2039'; font-size: 1.45rem; }
    .assign-student-index .as-footer .paginate_button.next .page-link::before { content: '\203A'; font-size: 1.45rem; }
    .assign-student-index .as-footer .as-count { font-size: 0.875rem; color: var(--ds-ink-muted, #64748b); white-space: nowrap; }
    .assign-student-index .as-footer .as-count-select { width: auto; min-width: 4.25rem; border-radius: 0.5rem; font-weight: 600; }

    @media (max-width: 575.98px) {
        .assign-student-index .as-toolbar { justify-content: flex-start; }
        .assign-student-index .as-toolbar .dataTables_filter,
        .assign-student-index .as-toolbar .dataTables_filter input { width: 100%; }
        .assign-student-index .as-footer { justify-content: center; }
    }

    /* ---- Import wizard modal ---- */
    .as-import-modal .modal-content { border: 0; border-radius: 1rem; box-shadow: 0 24px 64px rgba(15, 23, 42, 0.18); }
    .as-import-modal .modal-title { font-size: 1.2rem; color: #1f2937; }
    .as-import-modal hr { color: #e5e7eb; opacity: 1; }
    .as-import-modal .progress { background: #eef2f6; border-radius: 999px; }
    .as-import-modal .progress-bar { background: var(--bs-primary); border-radius: 999px; transition: width 0.25s ease; }

    .as-dropzone {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        width: 100%; min-height: 220px; gap: 0.35rem; margin: 0; padding: 1.5rem; cursor: pointer;
        border: 2px dashed #cbd5e1; border-radius: 0.75rem; background: #fcfdfe; text-align: center;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }
    .as-dropzone:hover, .as-dropzone.as-dragover { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb, 0 74 147), 0.04); }
    .as-dropzone .as-dropzone-icon { font-size: 46px; color: #94a3b8; }
    .as-dropzone .as-dropzone-text { font-size: 0.95rem; color: #475467; font-weight: 500; }
    .as-dropzone .as-dropzone-hint { font-size: 0.8rem; color: #98a2b3; }
    .as-dropzone .as-dropzone-hint a { color: var(--bs-primary); text-decoration: none; font-weight: 600; }
    .as-dropzone .as-dropzone-file { font-size: 0.85rem; color: #157347; font-weight: 600; margin-top: 0.25rem; }

    .as-import-modal .as-preview-wrap { max-height: 340px; overflow: auto; border: 1px solid var(--ds-line, #e5e7eb); border-radius: 0.6rem; }
    .as-import-modal .as-preview-wrap thead th {
        position: sticky; top: 0; z-index: 1;
        background: var(--ds-surface-2, #f8fafc); color: var(--ds-ink-muted, #64748b);
        font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.02em;
    }
    .as-import-modal .as-preview-wrap tbody td { font-size: 0.875rem; }
</style>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
/* DataTable chrome enhancer (toolbar + footer) — frontend only. */
(function() {
    var $ = window.jQuery;
    if (!$ || !$.fn || !$.fn.DataTable) { return; }

    var TID = 'othostelroomdetails-table';

    function enhance() {
        var $table = $('#' + TID);
        if (!$table.length || !$.fn.DataTable.isDataTable($table)) { return; }
        var el = $table.get(0);
        if (el._asEnhanced) { return; }
        el._asEnhanced = true;

        var api = $table.DataTable();
        var $wrapper = $('#' + TID + '_wrapper');
        if (!$wrapper.length) { return; }

        var $responsive = $table.closest('.table-responsive');
        var $host = $responsive.length ? $responsive : $wrapper;
        var $length   = $wrapper.find('.dataTables_length');
        var $filter   = $wrapper.find('.dataTables_filter');
        var $paginate = $wrapper.find('.dataTables_paginate');
        var $topRow = $filter.closest('.row');
        var $botRow = $paginate.closest('.row');

        var $toolbar = $('<div class="as-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2"></div>');
        $toolbar.insertBefore($host);

        var modalId = TID + '-cols-modal';
        var $modal = $(
            '<div class="modal fade as-cols-modal" id="' + modalId + '" tabindex="-1" aria-hidden="true">' +
                '<div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">' +
                    '<div class="modal-header border-0 pb-2"><h5 class="modal-title fw-semibold">Column Visibility</h5>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>' +
                    '<div class="modal-body pt-0"><hr class="mt-0 mb-3"><div class="row g-2 as-cols-grid"></div></div>' +
                    '<div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-outline-primary px-4 rounded-3" data-bs-dismiss="modal">Close</button></div>' +
                '</div></div>' +
            '</div>'
        );
        var $grid = $modal.find('.as-cols-grid');
        api.columns().every(function(idx) {
            var col = this;
            var title = $(col.header()).text().trim() || ('Column ' + (idx + 1));
            var $cell = $('<div class="col-6 col-md-4"></div>');
            var $chip = $('<label class="as-col-chip d-flex align-items-center gap-2 mb-0 p-2 border rounded-3" style="cursor:pointer;"><input type="checkbox" class="form-check-input m-0"' + (col.visible() ? ' checked' : '') + '><span></span></label>');
            $chip.find('span').text(title);
            $chip.find('input').on('change', function() { col.visible($(this).is(':checked')); });
            $cell.append($chip);
            $grid.append($cell);
        });
        $('body').append($modal);

        var $colsBtn = $('<button type="button" class="btn as-btn d-inline-flex align-items-center gap-1"><i class="material-icons material-symbols-rounded" aria-hidden="true">view_column</i><span>Columns</span></button>');
        $colsBtn.on('click', function() {
            if (window.bootstrap && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).show(); }
        });
        $toolbar.append($colsBtn);

        $filter.appendTo($toolbar);
        $filter.find('input').addClass('form-control').attr('placeholder', 'Search');

        var $footer = $('<div class="as-footer d-flex flex-wrap align-items-center justify-content-between gap-3"></div>');
        $footer.insertAfter($host);
        var $lenSelect = $length.find('select').addClass('form-select form-select-sm as-count-select');
        var $count = $('<div class="as-count d-inline-flex align-items-center gap-2"></div>');
        $count.append($('<span>Showing</span>')).append($lenSelect);
        var $countText = $('<span class="as-count-text"></span>');
        $count.append($countText);
        $footer.append($paginate).append($count);

        function asUpdateCount() {
            try { var info = api.page.info(); $countText.text('of ' + info.recordsDisplay + ' items'); } catch (e) {}
        }
        api.on('draw', asUpdateCount);
        asUpdateCount();

        // ---- Print via DataTables Buttons (hidden), fallback to window.print ----
        if ($.fn.dataTable && $.fn.dataTable.Buttons) {
            try {
                var pb = new $.fn.dataTable.Buttons(api, { buttons: [{ extend: 'print', title: 'Assign Student Hostel' }] });
                var $pc = $(pb.container()).addClass('d-none');
                $wrapper.append($pc);
                $('#asPrintBtn').on('click', function() { $pc.find('.dt-button').first().trigger('click'); });
            } catch (e) { $('#asPrintBtn').on('click', function() { window.print(); }); }
        } else {
            $('#asPrintBtn').on('click', function() { window.print(); });
        }

        $length.remove();
        $topRow.remove();
        $botRow.remove();
    }

    $(document).on('init.dt', '#' + TID, function() { enhance(); });
    $(function() { enhance(); });
})();

/* Import wizard (step 1 dropzone → step 2 server preview → real import). */
(function() {
    var $ = window.jQuery;
    if (!$) { return; }

    var previewUrl = '{{ route("hostel.building.map.assign.hostel.to.student.preview") }}';

    function setStep(step) {
        if (step === 2) {
            $('#asImportStep1').addClass('d-none');
            $('#asImportStep2').removeClass('d-none');
            $('#asImportNext').addClass('d-none');
            $('#asImportAssign').removeClass('d-none');
            $('#asImportBack').removeClass('d-none');
            $('#asImportProgress').css('width', '100%');
            $('#asImportProgressPct').text('100%');
        } else {
            $('#asImportStep2').addClass('d-none');
            $('#asImportStep1').removeClass('d-none');
            $('#asImportAssign').addClass('d-none');
            $('#asImportBack').addClass('d-none');
            $('#asImportNext').removeClass('d-none');
            $('#asImportProgress').css('width', '50%');
            $('#asImportProgressPct').text('50%');
        }
    }

    // Clear any previously rendered preview/errors (used when the file changes).
    function clearPreview() {
        $('#asPreviewBody').empty();
        $('#asPreviewNote').text('');
        $('#importErrors').addClass('d-none');
        $('#importErrorTableBody').empty();
    }

    function resetWizard() {
        var form = document.getElementById('importExcelForm');
        if (form) { form.reset(); }
        $('#asSelectedFile').addClass('d-none').text('');
        $('#asPreviewBody').empty();
        $('#asPreviewNote').text('');
        $('#importErrors').addClass('d-none');
        $('#importErrorTableBody').empty();
        $('#asImportNext').prop('disabled', true);
        setStep(1);
    }

    // Dropzone interactions
    var dz = document.getElementById('asDropzone');
    var fileInput = document.getElementById('importFile');
    if (dz && fileInput) {
        ['dragenter', 'dragover'].forEach(function(ev) {
            dz.addEventListener(ev, function(e) { e.preventDefault(); dz.classList.add('as-dragover'); });
        });
        ['dragleave', 'drop'].forEach(function(ev) {
            dz.addEventListener(ev, function(e) { e.preventDefault(); dz.classList.remove('as-dragover'); });
        });
        dz.addEventListener('drop', function(e) {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                $(fileInput).trigger('change');
            }
        });
    }

    $(document).on('change', '#importFile', function() {
        // A new file invalidates any existing preview — clear it and return to step 1.
        clearPreview();
        setStep(1);
        if (this.files && this.files.length) {
            $('#asSelectedFile').removeClass('d-none').text('Selected: ' + this.files[0].name);
            $('#asImportNext').prop('disabled', false);
        } else {
            $('#asSelectedFile').addClass('d-none').text('');
            $('#asImportNext').prop('disabled', true);
        }
    });

    // Back → return to step 1 to choose a different file.
    $(document).on('click', '#asImportBack', function() {
        clearPreview();
        setStep(1);
    });

    // Next → fetch a read-only preview of the file and show step 2.
    $(document).on('click', '#asImportNext', function() {
        var fileInput = document.getElementById('importFile');
        if (!fileInput.files.length) { return; }

        var $btn = $(this).prop('disabled', true).text('Loading…');
        clearPreview();
        var formData = new FormData(document.getElementById('importExcelForm'));

        $.ajax({
            url: previewUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
            success: function(res) {
                $('#asPreviewBody').empty();
                var rows = (res && res.rows) || [];
                if (!rows.length) {
                    $('#asPreviewBody').append('<tr><td colspan="4" class="text-center py-4 text-secondary">No rows found in the file.</td></tr>');
                } else {
                    rows.forEach(function(r, i) {
                        var tr = '<tr><td class="text-center">' + (i + 1) + '</td>' +
                            '<td>' + $('<div>').text(r.course_name || '—').html() + '</td>' +
                            '<td>' + $('<div>').text(r.user_name || '—').html() + '</td>' +
                            '<td>' + $('<div>').text(r.hostel_room_name || '—').html() + '</td></tr>';
                        $('#asPreviewBody').append(tr);
                    });
                }
                if (res && res.total && res.total > rows.length) {
                    $('#asPreviewNote').text('Showing first ' + rows.length + ' of ' + res.total + ' rows. All rows will be validated on import.');
                } else {
                    $('#asPreviewNote').text('');
                }
                setStep(2);
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && xhr.responseJSON.errors.file))) || 'Unable to read the file.';
                alert(Array.isArray(msg) ? msg.join('\n') : msg);
            },
            complete: function() {
                $btn.prop('disabled', false).text('Next');
            }
        });
    });

    // Assign → run the existing (unchanged) import handler.
    $(document).on('click', '#asImportAssign', function() {
        $('#upload_import_hostel_mapping_to_student').trigger('click');
    });

    // Reset wizard whenever the modal opens/closes.
    var modalEl = document.getElementById('importModal');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', resetWizard);
        modalEl.addEventListener('hidden.bs.modal', resetWizard);
    }
})();
</script>
@endpush
