@extends('admin.layouts.master')

@section('title', 'OMR Upload')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="OMR Upload" :showBack="false"
                 :items="['Setup', 'COE', 'Result Management', 'OMR Upload']">
        <button type="button" class="btn coe-btn-header" id="omrUploadBtn">
            <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i><span>Upload OMR CSV</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3 mb-3">
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="omrDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="omrPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#omrColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="omrDtSearch" class="programme-dt-search d-none" data-dt-search-for="omrTable"></div>
                    <button type="button" id="omrSearchToggle" class="coe-search-toggle"
                            aria-controls="omrDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="omrTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">File Name</th>
                                <th scope="col">Remarks</th>
                                <th scope="col" class="coe-col-center">Questions</th>
                                <th scope="col" class="coe-col-center">Score in File</th>
                                <th scope="col" class="coe-col-center">Answer Sheets</th>
                                <th scope="col" class="coe-col-center">Sheets with Problems</th>
                                <th scope="col">Uploaded By</th>
                                <th scope="col">Uploaded On</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        {{-- @foreach, not @forelse: DataTables renders its own empty state. --}}
                        <tbody>
                            @foreach ($rows as $row)
                                <tr data-coe-id="{{ $row['id'] }}">
                                    <td></td>
                                    <td class="coe-col-wrap">{{ $row['file'] }}</td>
                                    <td class="coe-col-wrap">{{ $row['remarks'] ?? '—' }}</td>
                                    <td class="coe-col-center">{{ $row['questions'] }}</td>
                                    <td class="coe-col-center">{{ $row['has_score'] ? 'Yes' : 'No' }}</td>
                                    <td class="coe-col-center">{{ $row['total'] }}</td>
                                    <td class="coe-col-center">
                                        <span class="coe-state coe-state--{{ $row['errors'] > 0 ? 'inactive' : 'active' }}">{{ $row['errors'] }}</span>
                                    </td>
                                    <td class="coe-col-wrap">{{ $row['uploaded_by'] }}</td>
                                    <td data-order="{{ $row['id'] }}">{{ $row['uploaded_at'] }}</td>
                                    <td>
                                        <div class="coe-act-group" role="group" aria-label="Row actions">
                                            <a href="{{ $row['show_url'] }}" class="coe-act coe-act--edit">
                                                <span class="coe-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">View</span>
                                            </a>
                                            <button type="button" class="coe-act coe-act--del">
                                                <span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="omrTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- ── Upload ─────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="omrUploadModal" tabindex="-1" aria-labelledby="omrUploadModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="modal-header coe-modal-header">
                <h5 class="modal-title" id="omrUploadModalLabel">Upload OMR CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body coe-modal-body">
                @include('admin.coe.partials.dropzone', [
                    'id' => 'omrFile',
                    'field' => 'file',
                    'accept' => '.csv,text/csv',
                    'hint' => 'File type: .csv | Max size: '.$maxMb.' MB',
                    'icon' => 'bi-file-earmark-spreadsheet',
                ])
                <div class="mt-3">
                    <label class="coe-form-label" for="omrRemarks">Remarks</label>
                    <input type="text" id="omrRemarks" class="form-control coe-control" maxlength="255"
                           placeholder="eg. FC101 — Mid-term, Paper I" autocomplete="off">
                    <div class="coe-field-error d-none" id="omrRemarksError" role="alert"></div>
                </div>
                <div class="coe-instructions">
                    <h6 class="coe-instructions__title">Instructions</h6>
                    <ul class="coe-instructions__list">
                        <li>Upload the CSV exactly as the OMR software exported it. Columns: NCS Header, Barcode, Roll No. (the ENR_NO column), Booklet, ANS1 … ANSn, and optionally the Score as the last column. A heading line is optional; if present it must name these columns in this order.</li>
                        <li>Each sheet is saved against the participant whose roll number (COE › Roll Number) equals its Roll No. A sheet whose Roll No. matches no one is still saved, marked "Not linked".</li>
                        <li>Answers may be {{ implode(', ', str_split(\App\Services\COE\OmrCsvParser::OPTIONS)) }}, blank, or <strong>*</strong> (more than one marked). Up to {{ number_format(\App\Services\COE\OmrCsvParser::MAX_ROWS) }} sheets and {{ \App\Services\COE\OmrCsvParser::MAX_QUESTIONS }} questions per file.</li>
                        <li>Sheets that were not read cleanly are still imported and flagged in Remarks: blank or unread (*) Barcode / Roll No. / Booklet, a number with fewer or more digits than the rest, a repeated NCS Header / Barcode / Roll No., a Barcode already uploaded in an earlier file, no answer marked, or a missing score.</li>
                        <li><span class="coe-tip">Tip:</span> If any line has the wrong number of columns, an answer that is not an option, a score that is not a whole number, or characters the scanner does not write, nothing is imported and the lines are listed. The same file cannot be uploaded twice.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer coe-modal-footer">
                <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn coe-btn coe-btn-primary" id="omrUploadSubmit" disabled>Upload</button>
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'omrDeleteModal', 'confirmId' => 'omrDeleteConfirm'])
@include('admin.coe.partials.colvis_modal', ['id' => 'omrColumnVisibilityModal', 'grid' => 'omrColumnToggleGrid'])
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    /* Column indexes — keep in step with the <thead>. */
    var COL = { sno: 0, uploadedOn: 8, action: 9 };
    var URLS = {
        store: @json(route('admin.coe.omr-upload.store')),
        destroy: @json(route('admin.coe.omr-upload.destroy', ['upload' => '__ID__']))
    };
    var CSRF = $('meta[name="csrf-token"]').attr('content') || '';

    function ajax(opts) {
        return $.ajax($.extend({ headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } }, opts));
    }
    function failText(xhr) {
        var res = xhr && xhr.responseJSON;
        if (res && res.errors) { return Object.keys(res.errors).map(function (k) { return res.errors[k][0]; }).join(' '); }
        if (res && res.message) { return res.message; }
        if (xhr && xhr.status === 419) { return 'Your session has expired. Reload the page and try again.'; }
        if (xhr && xhr.status === 429) { return 'Too many uploads in a minute. Wait a moment and try again.'; }
        return 'Something went wrong. Please try again.';
    }

    var dt = CoeGrid.init({
        table: '#omrTable',
        title: 'OMR Upload',
        snoCol: COL.sno,
        order: [[COL.uploadedOn, 'desc']],
        noSort: [COL.sno, COL.action],
        exclude: [COL.action],
        download: '#omrDownloadBtn',
        print: '#omrPrintBtn',
        searchToggle: '#omrSearchToggle',
        searchSlot: '#omrDtSearch',
        colvisGrid: '#omrColumnToggleGrid',
        colvisKey: 'coeOmrUpload:hiddenColumns:v1',
        empty: { title: 'No OMR Uploads', zeroTitle: 'No Uploads Found', text: 'Upload the CSV exported by the OMR software to get started.' }
    });

    /* ── Delete ── */
    CoeGrid.confirmDelete({
        table: '#omrTable',
        modal: '#omrDeleteModal',
        confirm: '#omrDeleteConfirm',
        onConfirm: function ($row) {
            if (!$row) { return; }
            return ajax({ url: URLS.destroy.replace('__ID__', encodeURIComponent($row.attr('data-coe-id'))), method: 'DELETE' })
                .done(function (res) {
                    dt.row($row).remove().draw(false);
                    Swal.fire({ icon: 'success', title: 'Success', text: res.message });
                })
                .fail(function (xhr) { Swal.fire({ icon: 'error', title: 'Error', text: failText(xhr) }); });
        }
    });

    /* ── Upload ── */
    var uploadModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('omrUploadModal'));
    var $submit = $('#omrUploadSubmit');
    var chosen = null, busy = false;

    var dz = CoeGrid.dropzone({
        root: '#omrFile',
        accept: /\.csv$/i,
        acceptText: 'Only a .csv file can be uploaded.',
        maxBytes: @json($maxMb) * 1024 * 1024,
        onChange: function (file) {
            if (file && file.size === 0) {
                $('#omrFile .coe-dropzone__error').text('The file is empty.').removeClass('d-none');
                $('#omrFile').addClass('is-invalid');
                $('#omrFile .coe-dropzone__file').addClass('d-none');
                $('#omrFileInput').val('');
                file = null;
            }
            chosen = file;
            $submit.prop('disabled', !file || busy);
        }
    });

    /* Same rule as the server: printable text, not starting with a formula character. */
    function remarksError(value) {
        if (value.length > 255) { return 'Remarks may be at most 255 characters.'; }
        if (/[\x00-\x1F\x7F]/.test(value) || /^\s*[=+\-@]/.test(value)) { return 'Remarks may not start with = + - @ or contain control characters.'; }
        return '';
    }
    function showRemarksError(msg) {
        $('#omrRemarksError').text(msg).toggleClass('d-none', !msg);
        $('#omrRemarks').toggleClass('is-invalid', !!msg);
        return !msg;
    }
    $('#omrRemarks').on('input', function () { showRemarksError(remarksError(this.value)); });

    $('#omrUploadBtn').on('click', function () { uploadModal.show(); });
    $('#omrUploadModal').on('hidden.bs.modal', function () { dz.reset(); chosen = null; $('#omrRemarks').val(''); showRemarksError(''); });

    $submit.on('click', function () {
        if (!chosen || busy) { return; }
        if (!showRemarksError(remarksError($('#omrRemarks').val()))) { return; }
        busy = true;
        $submit.prop('disabled', true).text('Uploading…');

        var form = new FormData();
        form.append('file', chosen);
        form.append('remarks', $.trim($('#omrRemarks').val()));

        ajax({ url: URLS.store, method: 'POST', data: form, processData: false, contentType: false })
            .done(function (res) {
                uploadModal.hide();
                Swal.fire({ icon: 'success', title: 'Uploaded', text: res.message })
                    .then(function () { window.location.href = res.redirect; });
            })
            .fail(function (xhr) {
                var errors = (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) || {};
                if (errors.remarks) { showRemarksError(errors.remarks[0]); return; }
                Swal.fire({ icon: 'error', title: 'Nothing imported', text: failText(xhr) });
            })
            .always(function () {
                busy = false;
                $submit.prop('disabled', !chosen).text('Upload');
            });
    });
});
</script>
@endpush
