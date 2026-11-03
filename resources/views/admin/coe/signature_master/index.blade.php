@extends('admin.layouts.master')

@section('title', 'Signature Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.sg-preview { max-width: 100%; max-height: 260px; object-fit: contain; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Signature Master" :showBack="false"
                 :items="['Setup', 'COE', 'Document Management', 'Signature Master']">
        <button type="button" class="btn coe-btn-header" id="sgAddBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add Signature</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3 mb-3">
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="sgDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="sgPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="sgFilterStatus" class="form-select" data-placeholder="Status" aria-label="Status">
                            <option value="">Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="button" id="sgResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#sgColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="sgDtSearch" class="programme-dt-search d-none" data-dt-search-for="sgTable"></div>
                    <button type="button" id="sgSearchToggle" class="coe-search-toggle"
                            aria-controls="sgDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="sgTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Designation Name</th>
                                <th scope="col">Person Name</th>
                                <th scope="col">Signature</th>
                                <th scope="col" class="coe-col-center">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        {{-- @foreach, not @forelse: DataTables renders its own empty state. --}}
                        <tbody>
                            @foreach ($rows as $row)
                                <tr data-coe-id="{{ $row['id'] }}" data-coe-active="{{ $row['active'] ? '1' : '0' }}" data-row='@json($row)'>
                                    <td></td>
                                    <td class="coe-col-wrap">{{ $row['designation'] }}</td>
                                    <td class="coe-col-wrap">{{ $row['person'] }}</td>
                                    <td><a href="#" class="sg-view-file">See attachment</a></td>
                                    <td class="coe-col-center" data-search="{{ $row['active'] ? 'Active' : 'Inactive' }}">
                                        <span class="coe-state coe-state--{{ $row['active'] ? 'active' : 'inactive' }}">
                                            {{ $row['active'] ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="coe-act-group" role="group" aria-label="Row actions">
                                            <button type="button" class="coe-act coe-act--edit">
                                                <span class="coe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Edit</span>
                                            </button>
                                            <label class="coe-act coe-act--toggle {{ $row['active'] ? 'is-on' : 'is-off' }}">
                                                <span class="coe-act__icon"><input type="checkbox" role="switch"
                                                    class="form-check-input plain-status-toggle coe-switch" @checked($row['active'])
                                                    aria-label="{{ $row['active'] ? 'Deactivate' : 'Activate' }}"></span>
                                                <span class="coe-act__label">{{ $row['active'] ? 'Deactivate' : 'Activate' }}</span>
                                            </label>
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
                     data-dt-footer-for="sgTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Add / Edit — one dialog; the title and submit caption change. --}}
<div class="modal fade" id="sgFormModal" tabindex="-1" aria-labelledby="sgFormModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="sgForm" novalidate enctype="multipart/form-data">
                <input type="hidden" name="pk" id="sgPk">
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="sgFormModalLabel">Add Signature</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="coe-form-label" for="sgDesignation">Designation Name<span class="coe-req">*</span></label>
                            <input type="text" id="sgDesignation" name="designation_name" class="form-control coe-control"
                                   maxlength="100" placeholder="eg. AAO" autocomplete="off" required>
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="sgPerson">Person Name<span class="coe-req">*</span></label>
                            <input type="text" id="sgPerson" name="person_name" class="form-control coe-control"
                                   maxlength="150" placeholder="eg. Ram Kumar" autocomplete="off" required>
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="sgFile">Upload Signature<span class="coe-req" id="sgFileReq">*</span></label>
                            <input type="file" id="sgFile" name="signature" class="form-control coe-control"
                                   accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                            <div class="form-text" id="sgFileHint">JPG or PNG, up to 2 MB.</div>
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="sgStatus">Status<span class="coe-req">*</span></label>
                            <select id="sgStatus" name="active_inactive" class="form-select coe-control" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary" id="sgSubmit">Add Signature</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- See attachment --}}
<div class="modal fade" id="sgPreviewModal" tabindex="-1" aria-labelledby="sgPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="modal-header coe-modal-header coe-modal-header--rule">
                <h5 class="modal-title" id="sgPreviewLabel">Signature</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body coe-modal-body text-center">
                <img id="sgPreviewImg" class="sg-preview" alt="Signature">
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'sgDeleteModal', 'confirmId' => 'sgDeleteConfirm'])
@include('admin.coe.partials.confirm_status_modal')

{{-- Column Visibility --}}
<div class="modal fade" id="sgColumnVisibilityModal" tabindex="-1" aria-labelledby="sgColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="sgColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="sgColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    /* Column indexes — keep in step with the <thead>. */
    var COL = { sno: 0, designation: 1, signature: 3, status: 4, action: 5 };
    var URLS = {
        store: @json(route('admin.coe.signature-master.store')),
        toggle: @json(route('admin.coe.signature-master.toggle-status', ['signature' => '__ID__'])),
        destroy: @json(route('admin.coe.signature-master.destroy', ['signature' => '__ID__']))
    };
    var CSRF = $('meta[name="csrf-token"]').attr('content') || '';

    function ajax(opts) {
        return $.ajax($.extend({ headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } }, opts));
    }
    function failText(xhr) {
        var res = xhr && xhr.responseJSON;
        if (res && res.message) { return res.message; }
        if (xhr && xhr.status === 419) { return 'Your session has expired. Reload the page and try again.'; }
        return 'Something went wrong. Please try again.';
    }
    function showError(xhr) { Swal.fire({ icon: 'error', title: 'Error', text: failText(xhr) }); }
    function idOf($row) { return encodeURIComponent($row.attr('data-coe-id')); }

    var dt = CoeGrid.init({
        table: '#sgTable',
        title: 'Signature Master',
        snoCol: COL.sno,
        order: [[COL.designation, 'asc']],
        noSort: [COL.sno, COL.signature, COL.action],
        exclude: [COL.signature, COL.action],
        filters: { '#sgFilterStatus': COL.status },
        reset: '#sgResetFilters',
        download: '#sgDownloadBtn',
        print: '#sgPrintBtn',
        searchToggle: '#sgSearchToggle',
        searchSlot: '#sgDtSearch',
        colvisGrid: '#sgColumnToggleGrid',
        colvisKey: 'coeSignatureMaster:hiddenColumns:v1',
        empty: { title: 'No Signatures', zeroTitle: 'No Signatures Found', text: 'Add a signature to get started.' }
    });

    /* ── See attachment ── */
    $('#sgTable').on('click', '.sg-view-file', function (e) {
        e.preventDefault();
        var row = JSON.parse($(this).closest('tr').attr('data-row'));
        $('#sgPreviewLabel').text(row.designation + ' — ' + row.person);
        $('#sgPreviewImg').attr('src', row.file_url);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('sgPreviewModal')).show();
    });

    /* ── Activate / Deactivate ── */
    CoeGrid.confirmStatus({
        table: '#sgTable',
        onConfirm: function ($row, makeActive) {
            ajax({ url: URLS.toggle.replace('__ID__', idOf($row)), method: 'PATCH', data: { active: makeActive ? 1 : 0 } })
                .done(function (res) {
                    Swal.fire({ icon: 'success', title: 'Success', text: res.message })
                        .then(function () { window.location.reload(); });
                })
                .fail(showError);
        }
    });

    /* ── Delete ── */
    CoeGrid.confirmDelete({
        table: '#sgTable',
        modal: '#sgDeleteModal',
        confirm: '#sgDeleteConfirm',
        onConfirm: function ($row) {
            if (!$row) { return; }
            ajax({ url: URLS.destroy.replace('__ID__', idOf($row)), method: 'DELETE' })
                .done(function (res) {
                    dt.row($row).remove().draw(false);
                    Swal.fire({ icon: 'success', title: 'Success', text: res.message });
                })
                .fail(showError);
        }
    });

    /* ── Add / Edit ── */
    var formModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('sgFormModal'));
    var $form = $('#sgForm'), $submit = $('#sgSubmit'), $status = $('#sgStatus'), $file = $('#sgFile');
    var editing = false, busy = false;

    $status.select2({ width: '100%', dropdownParent: $('#sgFormModal .modal-body'), minimumResultsForSearch: Infinity });

    function fieldError($el, msg) {
        var $wrap = $el.closest('[class*="col-"]');
        $wrap.find('.coe-field-error').remove();
        $el.toggleClass('is-invalid', !!msg);
        if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($wrap); }
        return !msg;
    }
    function clearErrors() {
        $form.find('.coe-field-error').remove();
        $form.find('.is-invalid').removeClass('is-invalid');
    }

    function openForm(row) {
        clearErrors();
        editing = !!row;
        $form[0].reset();
        $('#sgPk').val(row ? row.id : '');
        $('#sgDesignation').val(row ? row.designation : '');
        $('#sgPerson').val(row ? row.person : '');
        $status.val(row ? (row.active ? '1' : '0') : '1').trigger('change.select2');
        $('#sgFileReq').toggle(!editing);
        $('#sgFileHint').text(editing ? 'JPG or PNG, up to 2 MB. Leave empty to keep the current signature.' : 'JPG or PNG, up to 2 MB.');
        $('#sgFormModalLabel').text(row ? 'Edit Signature' : 'Add Signature');
        $submit.text(row ? 'Update Signature' : 'Add Signature');
        formModal.show();
    }

    $('#sgAddBtn').on('click', function () { openForm(null); });
    $('#sgTable').on('click', '.coe-act--edit', function () {
        openForm(JSON.parse($(this).closest('tr').attr('data-row')));
    });
    $form.on('input change', 'input, select', function () { if ($.trim(this.value)) { fieldError($(this), ''); } });

    function validate() {
        var ok = true, f = $file[0].files[0];
        ok = fieldError($('#sgDesignation'), $.trim($('#sgDesignation').val()) ? '' : 'Enter the designation name.') && ok;
        ok = fieldError($('#sgPerson'), $.trim($('#sgPerson').val()) ? '' : 'Enter the person name.') && ok;
        var fileMsg = '';
        if (!f && !editing) { fileMsg = 'Upload the signature.'; }
        else if (f && !/\.(jpe?g|png)$/i.test(f.name)) { fileMsg = 'The signature must be a JPG or PNG image.'; }
        else if (f && f.size > 2 * 1024 * 1024) { fileMsg = 'The signature must not be larger than 2 MB.'; }
        ok = fieldError($file, fileMsg) && ok;
        return ok;
    }

    var FIELD_FOR = {
        designation_name: '#sgDesignation', person_name: '#sgPerson', signature: '#sgFile', active_inactive: '#sgStatus'
    };

    $form.on('submit', function (e) {
        e.preventDefault();
        if (busy || !validate()) { return; }
        busy = true;
        $submit.prop('disabled', true);

        ajax({ url: URLS.store, method: 'POST', data: new FormData($form[0]), processData: false, contentType: false })
            .done(function (res) {
                formModal.hide();
                Swal.fire({ icon: 'success', title: 'Success', text: res.message })
                    .then(function () { window.location.reload(); });
            })
            .fail(function (xhr) {
                var errors = (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) || null;
                if (!errors) { showError(xhr); return; }
                var unplaced = [];
                Object.keys(errors).forEach(function (field) {
                    var $el = $(FIELD_FOR[field] || '').first();
                    if ($el.length) { fieldError($el, errors[field][0]); } else { unplaced.push(errors[field][0]); }
                });
                if (unplaced.length) { Swal.fire({ icon: 'error', title: 'Error', text: unplaced.join(' ') }); }
            })
            .always(function () { busy = false; $submit.prop('disabled', false); });
    });
});
</script>
@endpush
