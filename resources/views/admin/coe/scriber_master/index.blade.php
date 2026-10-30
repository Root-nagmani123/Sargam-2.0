@extends('admin.layouts.master')

@section('title', "Scriber's Master")

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $isArchived = $scope === 'archived';
    $dash = '<span class="coe-muted">&mdash;</span>';
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Scriber's Master" :showBack="false"
                 :items="['Setup', 'COE', 'Master Data', 'Scriber\'s Master']">
        <button type="button" class="btn coe-btn-header" id="smAddBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add Scriber</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs (Active = status Active, Archived = Inactive) · Download / Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Scriber scope">
            <a href="{{ route('admin.coe.scriber-master.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}"
               @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.scriber-master.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}"
               @if ($isArchived) aria-current="page" @endif>Archived</a>
        </nav>

        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="smDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="smPrintBtn" class="btn programme-dt-btn-columns" title="Print">
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
                        <select id="smFilterRole" class="form-select" data-placeholder="Role" aria-label="Role">
                            <option value="">Role</option>
                            @foreach ($roles as $roleLabel)
                                <option value="{{ $roleLabel }}">{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="smFilterStatus" class="form-select" data-placeholder="Status" aria-label="Status">
                            <option value="">Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <button type="button" id="smResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#smColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="smDtSearch" class="programme-dt-search d-none" data-dt-search-for="smTable"></div>
                    <button type="button" id="smSearchToggle" class="coe-search-toggle"
                            aria-controls="smDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="smTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Name</th>
                                <th scope="col">Phone Number</th>
                                <th scope="col">Email</th>
                                <th scope="col">Scriber Role</th>
                                <th scope="col">Remarks</th>
                                <th scope="col" class="coe-col-center">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        {{-- @foreach, not @forelse: an @empty colspan row breaks DataTables'
                             column count. DataTables renders its own empty state. --}}
                        <tbody>
                            @foreach ($rows as $row)
                                <tr data-coe-id="{{ $row['id'] }}" data-row='@json($row)'>
                                    <td></td>
                                    <td class="coe-col-wrap">{{ $row['name'] }}</td>
                                    <td class="coe-nowrap">+91 {{ $row['phone'] }}</td>
                                    <td class="coe-col-wrap">{{ $row['email'] }}</td>
                                    <td>{{ $row['role_label'] }}</td>
                                    <td class="coe-col-truncate" @if ($row['remarks']) title="{{ $row['remarks'] }}" @endif>
                                        {!! $row['remarks'] ? e($row['remarks']) : $dash !!}
                                    </td>
                                    <td class="coe-col-center">
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
                     data-dt-footer-for="smTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Add / Edit — one dialog; the title and submit caption change. --}}
<div class="modal fade" id="smFormModal" tabindex="-1" aria-labelledby="smFormModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="smForm" novalidate>
                <input type="hidden" name="pk" id="smPk">
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="smFormModalLabel">Add Scriber</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="coe-form-label" for="smName">Scriber Name<span class="coe-req">*</span></label>
                            <input type="text" id="smName" name="scriber_name" class="form-control coe-control"
                                   maxlength="150" placeholder="eg. Atul Kumar" autocomplete="off" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="smPhone">Phone Number<span class="coe-req">*</span></label>
                            <input type="tel" id="smPhone" name="phone_number" class="form-control coe-control"
                                   maxlength="10" inputmode="numeric" placeholder="eg. 9374929320" autocomplete="off" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="smEmail">Email ID<span class="coe-req">*</span></label>
                            <input type="email" id="smEmail" name="email" class="form-control coe-control"
                                   maxlength="150" placeholder="eg. atulkumar@user.com" autocomplete="off" required>
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="smRole">Scriber Role<span class="coe-req">*</span></label>
                            <select id="smRole" name="scriber_role" class="form-select coe-control" required>
                                <option value="">Select Role</option>
                                @foreach ($roles as $roleKey => $roleLabel)
                                    <option value="{{ $roleKey }}">{{ $roleLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="smRemarks">Additional Remarks</label>
                            <textarea id="smRemarks" name="remarks" class="form-control coe-control" rows="3" maxlength="500"
                                      placeholder="eg. Proficient in Writing"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="coe-form-label" for="smStatus">Status<span class="coe-req">*</span></label>
                            <select id="smStatus" name="active_inactive" class="form-select coe-control" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary" id="smSubmit">Add Scriber</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'smDeleteModal', 'confirmId' => 'smDeleteConfirm'])

{{-- Column Visibility --}}
<div class="modal fade" id="smColumnVisibilityModal" tabindex="-1" aria-labelledby="smColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="smColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="smColumnToggleGrid"></div>
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
    var COL = { sno: 0, name: 1, role: 4, status: 6, action: 7 };
    var URLS = {
        store: @json(route('admin.coe.scriber-master.store')),
        destroy: @json(route('admin.coe.scriber-master.destroy', ['scriber' => '__ID__']))
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

    var dt = CoeGrid.init({
        table: '#smTable',
        title: @json($isArchived ? "Scriber's Master - Archived" : "Scriber's Master"),
        snoCol: COL.sno,
        order: [[COL.name, 'asc']],
        noSort: [COL.sno, COL.action],
        exclude: [COL.action],
        filters: { '#smFilterRole': COL.role, '#smFilterStatus': COL.status },
        reset: '#smResetFilters',
        download: '#smDownloadBtn',
        print: '#smPrintBtn',
        searchToggle: '#smSearchToggle',
        searchSlot: '#smDtSearch',
        colvisGrid: '#smColumnToggleGrid',
        colvisKey: 'coeScriberMaster:hiddenColumns:v1',
        empty: { title: 'No Scribers', zeroTitle: 'No Scribers Found', text: 'Add a scriber to get started.' }
    });

    /* ── Delete ──────────────────────────────────────────────────────────────
       The row leaves the grid only after the server confirms. A scriber still
       assigned to an OT is refused, with the server's reason. */
    CoeGrid.confirmDelete({
        table: '#smTable',
        modal: '#smDeleteModal',
        confirm: '#smDeleteConfirm',
        onConfirm: function ($row) {
            if (!$row) { return; }
            ajax({ url: URLS.destroy.replace('__ID__', encodeURIComponent($row.attr('data-coe-id'))), method: 'DELETE' })
                .done(function (res) {
                    dt.row($row).remove().draw(false);
                    Swal.fire({ icon: 'success', title: 'Success', text: res.message });
                })
                .fail(showError);
        }
    });

    /* ── Add / Edit ──────────────────────────────────────────────────────── */
    var formModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('smFormModal'));
    var $form = $('#smForm'), $submit = $('#smSubmit');
    var $role = $('#smRole'), $status = $('#smStatus');
    var $modalBody = $('#smFormModal .modal-body');
    var busy = false;

    [$role, $status].forEach(function ($s) {
        $s.select2({ width: '100%', dropdownParent: $modalBody, minimumResultsForSearch: Infinity });
    });

    function fieldError($el, msg) {
        var $wrap = $el.closest('[class*="col-"]');
        $wrap.find('.coe-field-error').remove();
        $el.toggleClass('is-invalid', !!msg);
        $wrap.find('.select2-selection').toggleClass('is-invalid', !!msg);
        if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($wrap); }
        return !msg;
    }
    function clearErrors() {
        $form.find('.coe-field-error').remove();
        $form.find('.is-invalid').removeClass('is-invalid');
    }

    function openForm(row) {
        clearErrors();
        $('#smPk').val(row ? row.id : '');
        $('#smName').val(row ? row.name : '');
        $('#smPhone').val(row ? row.phone : '');
        $('#smEmail').val(row ? row.email : '');
        $role.val(row ? String(row.role) : '').trigger('change.select2');
        $('#smRemarks').val(row && row.remarks ? row.remarks : '');
        $status.val(row ? (row.active ? '1' : '0') : '1').trigger('change.select2');
        $('#smFormModalLabel').text(row ? 'Edit Scriber' : 'Add Scriber');
        $submit.text(row ? 'Update Scriber' : 'Add Scriber');
        formModal.show();
    }

    $('#smAddBtn').on('click', function () { openForm(null); });
    $('#smTable').on('click', '.coe-act--edit', function () {
        openForm(JSON.parse($(this).closest('tr').attr('data-row')));
    });

    // Digits only, as typed or pasted.
    $('#smPhone').on('input', function () { this.value = this.value.replace(/\D/g, '').slice(0, 10); });
    $form.on('input change', 'input, textarea, select', function () { if ($.trim(this.value)) { fieldError($(this), ''); } });

    function validate() {
        var ok = true;
        var email = $.trim($('#smEmail').val());
        ok = fieldError($('#smName'), $.trim($('#smName').val()) ? '' : 'Enter the scriber name.') && ok;
        ok = fieldError($('#smPhone'), /^[0-9]{10}$/.test($('#smPhone').val()) ? '' : 'Enter a 10-digit phone number.') && ok;
        ok = fieldError($('#smEmail'), !email ? 'Enter the email ID.' : (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? '' : 'Enter a valid email ID.')) && ok;
        ok = fieldError($role, $role.val() ? '' : 'Select the scriber role.') && ok;
        return ok;
    }

    // Server field name => the control its 422 message belongs under.
    var FIELD_FOR = {
        scriber_name: '#smName', phone_number: '#smPhone', email: '#smEmail',
        scriber_role: '#smRole', remarks: '#smRemarks', active_inactive: '#smStatus'
    };

    $form.on('submit', function (e) {
        e.preventDefault();
        if (busy || !validate()) { return; }
        busy = true;
        $submit.prop('disabled', true);

        ajax({ url: URLS.store, method: 'POST', data: $form.serialize() })
            .done(function (res) {
                formModal.hide();
                // Rows come back from the server on a reload — a status change
                // also moves the scriber to the other tab.
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
