@extends('admin.layouts.master')

@section('title', 'OT Laptop Status')

@push('styles')
{{-- Select2 JS is global; its CSS is per page (filters + the OT picker are searchable). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
{{-- COE module layer (coe-*), shared with the question-paper screens. --}}
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
    /* Scribers Details — the dialog's OT → scriber rows. */
    .coe-modal .ols-scribers { border: 1px solid var(--coe-control-line); border-radius: .5rem; }
    .coe-modal .ols-scribers th { background: #f2f4f7; font-weight: 500; font-size: .875rem; white-space: nowrap; }
    .coe-modal .ols-scribers th,
    .coe-modal .ols-scribers td { padding: .625rem .75rem; }
    .coe-modal .ols-scribers td { min-width: 150px; }
    .coe-modal .ols-scribers td.ols-scr-type { min-width: 140px; }
    .coe-modal .ols-scribers td.ols-scr-act { min-width: 96px; white-space: nowrap; text-align: center; }
    .coe-modal .ols-scr-btn {
        width: 36px; height: 36px; border: 0; border-radius: .5rem; color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .coe-modal .ols-scr-btn--remove { background: var(--coe-danger); }
    .coe-modal .ols-scr-btn--add { background: var(--coe-brand); }
    .coe-modal .ols-scr-btn + .ols-scr-btn { margin-left: .5rem; }
</style>
@endpush

@section('setup_content')
@php
    $isArchived = $scope === 'archived';
    $dash = '<span class="coe-muted">&mdash;</span>';
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="OT Laptop Status" :showBack="false"
                 :items="['Setup', 'COE', 'Laptop Management', 'OT Laptop Status']">
        <button type="button" class="btn coe-btn-header" data-bs-toggle="modal" data-bs-target="#olsImportModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Import Laptop Status</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs (links, one URL each) · Download / Print — above the card, per §1 --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Course scope">
            <a href="{{ route('admin.coe.ot-laptop-status.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}"
               @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.ot-laptop-status.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}"
               @if ($isArchived) aria-current="page" @endif>Archived</a>
        </nav>

        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="olsDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="olsPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select id="olsFilterCourse" class="form-select" data-placeholder="Course Name" aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course }}">{{ $course }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Service has no column on screen, so it filters on <tr data-service>. --}}
                    <div class="programme-dt-filter-select">
                        <select id="olsFilterService" class="form-select" data-placeholder="Service Name" aria-label="Service Name">
                            <option value="">Service Name</option>
                            @foreach ($services as $service)
                                <option value="{{ $service }}">{{ $service }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="olsFilterBatch" class="form-select" data-placeholder="Batch" aria-label="Batch">
                            <option value="">Batch</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch }}">{{ $batch }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" id="olsResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>

                    {{-- Appears once a row is ticked. --}}
                    <button type="button" id="olsBulkDelete" class="btn coe-btn-bulk-del d-none">
                        <i class="bi bi-trash3" aria-hidden="true"></i>
                        <span>Delete Selected (<span id="olsSelectedCount">0</span>)</span>
                    </button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#olsColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="olsDtSearch" class="programme-dt-search d-none" data-dt-search-for="olsTable"></div>
                    <button type="button" id="olsSearchToggle" class="coe-search-toggle"
                            aria-controls="olsDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="olsTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="coe-col-check">
                                    <input type="checkbox" class="form-check-input m-0" id="olsCheckAll"
                                           aria-label="Select all rows">
                                </th>
                                <th scope="col">S. No.</th>
                                <th scope="col">Roll Number</th>
                                <th scope="col">Name</th>
                                <th scope="col">Code</th>
                                <th scope="col">Course</th>
                                <th scope="col">Batch</th>
                                <th scope="col">Laptop Required</th>
                                <th scope="col">Reason</th>
                                <th scope="col">OT with Disability</th>
                                <th scope="col">Disability Type</th>
                                <th scope="col">Need for Scriber</th>
                                <th scope="col">Remarks for Scriber</th>
                                <th scope="col">Primary Scriber</th>
                                <th scope="col">Secondary Scriber</th>
                                <th scope="col">Additional Remarks</th>
                                <th scope="col">Updated By</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        {{-- @foreach, not @forelse: an @empty colspan row breaks DataTables'
                             column count. DataTables renders its own empty state. --}}
                        <tbody>
                            @foreach ($rows as $row)
                                <tr data-coe-id="{{ $row['id'] }}" data-service="{{ $row['service'] }}">
                                    <td class="coe-col-check">
                                        <input type="checkbox" class="form-check-input m-0 ols-row-check"
                                               aria-label="Select {{ $row['name'] }}">
                                    </td>
                                    <td></td>
                                    <td class="coe-nowrap">{{ $row['roll'] }}</td>
                                    <td class="coe-col-wrap">{{ $row['name'] }}</td>
                                    <td>{{ $row['code'] }}</td>
                                    <td class="coe-strong">{{ $row['course'] }}</td>
                                    <td>{{ $row['batch'] }}</td>
                                    <td class="coe-yesno">{{ $row['laptop_required'] ? 'Yes' : 'No' }}</td>
                                    <td class="coe-col-truncate" @if ($row['reason']) title="{{ $row['reason'] }}" @endif>
                                        {!! $row['reason'] ? e($row['reason']) : $dash !!}
                                    </td>
                                    <td>{{ $row['disability'] ? 'Yes' : 'No' }}</td>
                                    <td>{!! $row['disability_type'] ? e($row['disability_type']) : $dash !!}</td>
                                    {{-- NULL = never asked (no disability), not "No". --}}
                                    <td>{!! $row['scriber'] === null ? $dash : ($row['scriber'] ? 'Yes' : 'No') !!}</td>
                                    <td class="coe-strong coe-col-wrap">{!! $row['scriber_remarks'] ? e($row['scriber_remarks']) : $dash !!}</td>
                                    <td class="coe-col-wrap">{!! $row['primary_scriber'] ? e($row['primary_scriber']) : $dash !!}</td>
                                    <td class="coe-col-wrap">{!! $row['secondary_scriber'] ? e($row['secondary_scriber']) : $dash !!}</td>
                                    <td class="coe-col-truncate" @if ($row['additional_remarks']) title="{{ $row['additional_remarks'] }}" @endif>
                                        {!! $row['additional_remarks'] ? e($row['additional_remarks']) : $dash !!}
                                    </td>
                                    <td>{{ $row['updated_by'] }}</td>
                                    <td>
                                        <div class="coe-act-group" role="group" aria-label="Row actions">
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

                {{-- Footer variant A — datatable-global-ui.js fills it (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="olsTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- ── Import Laptop Status: Bulk (file) · Single Record (form) ─────────── --}}
<div class="modal fade" id="olsImportModal" tabindex="-1" aria-labelledby="olsImportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="modal-header coe-modal-header">
                <h5 class="modal-title" id="olsImportModalLabel">Import Laptop Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body coe-modal-body">
                <div class="coe-seg nav" role="tablist" aria-label="Import mode">
                    <button type="button" class="coe-seg__btn active" id="olsModeBulkTab"
                            data-bs-toggle="tab" data-bs-target="#olsModeBulk" role="tab"
                            aria-controls="olsModeBulk" aria-selected="true">Import In Bulk</button>
                    <button type="button" class="coe-seg__btn" id="olsModeSingleTab"
                            data-bs-toggle="tab" data-bs-target="#olsModeSingle" role="tab"
                            aria-controls="olsModeSingle" aria-selected="false">Add Single Record</button>
                </div>

                <div class="tab-content">
                    {{-- Bulk --}}
                    <div class="tab-pane fade show active" id="olsModeBulk" role="tabpanel" aria-labelledby="olsModeBulkTab">
                        @include('admin.coe.partials.dropzone', [
                            'id' => 'olsImportFile',
                            'field' => 'laptop_status_file',
                            'accept' => collect($importExtensions)->map(fn ($e) => '.'.$e)->implode(','),
                            'hint' => 'File type: '.collect($importExtensions)->map(fn ($e) => '.'.$e)->implode(' ').' | Max size: '.$importMaxMb.' MB',
                            'icon' => 'bi-file-earmark-spreadsheet',
                        ])

                        <div class="coe-instructions">
                            <h6 class="coe-instructions__title">Instructions &amp; Excel Template</h6>
                            <ul class="coe-instructions__list">
                                <li><a href="{{ route('admin.coe.ot-laptop-status.template', ['scope' => $scope]) }}" id="olsTemplateLink">Download the template file</a>, pre-filled with the OTs of {{ $isArchived ? 'archived' : 'running' }} courses. Fill in <strong>Laptop Required</strong> (Yes/No) and <strong>Reason</strong>; rows left blank are skipped.</li>
                                <li><span class="coe-tip">Tip:</span> Keep the Participant ID column as downloaded — the import matches OTs on it. Disability and scriber answers are recorded through Add Single Record.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Single record --}}
                    <div class="tab-pane fade" id="olsModeSingle" role="tabpanel" aria-labelledby="olsModeSingleTab">
                        <form id="olsSingleForm" class="coe-form" novalidate>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="coe-form-label" for="olsCourse">Course Name<span class="coe-req">*</span></label>
                                    <select id="olsCourse" name="course" class="form-select coe-control" required>
                                        <option value="">Select Course</option>
                                        @foreach ($singleCourses as $course)
                                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="coe-form-label" for="olsOt">OT<span class="coe-req">*</span></label>
                                    {{-- Filled from the participants endpoint for the chosen Course.
                                         Several OTs can be picked; each gets the same answers. --}}
                                    <select id="olsOt" name="student_master_pks[]" class="form-select coe-control" multiple required></select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="coe-form-label" for="olsDisability">OT with Disabilities<span class="coe-req">*</span></label>
                                    <select id="olsDisability" name="has_disability" class="form-select coe-control" required>
                                        <option value="">Select</option>
                                        <option value="yes">Yes</option>
                                        <option value="no">No</option>
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <span class="coe-form-label" id="olsLaptopLabel">Laptop Required<span class="coe-req">*</span></span>
                                    <div class="coe-radio-row" role="radiogroup" aria-labelledby="olsLaptopLabel">
                                        <label class="coe-radio"><input type="radio" name="ols_laptop" value="yes"> Yes</label>
                                        <label class="coe-radio"><input type="radio" name="ols_laptop" value="no" checked> No</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="coe-form-label" for="olsReasonLaptop">
                                        Reason for Laptop<span class="coe-req ols-req-laptop d-none">*</span>
                                    </label>
                                    <textarea id="olsReasonLaptop" name="reason" class="form-control coe-control" rows="3" maxlength="500"
                                              placeholder="eg. Don't have PC" disabled></textarea>
                                </div>

                                <div class="col-sm-6 ols-if-disability d-none">
                                    <span class="coe-form-label" id="olsScriberLabel">Scriber Required<span class="coe-req">*</span></span>
                                    <div class="coe-radio-row" role="radiogroup" aria-labelledby="olsScriberLabel">
                                        <label class="coe-radio"><input type="radio" name="ols_scriber" value="yes"> Yes</label>
                                        <label class="coe-radio"><input type="radio" name="ols_scriber" value="no" checked> No</label>
                                    </div>
                                </div>
                                <div class="col-sm-6 ols-if-disability d-none">
                                    <label class="coe-form-label" for="olsReasonScriber">
                                        Reason for Scriber<span class="coe-req ols-req-scriber d-none">*</span>
                                    </label>
                                    <textarea id="olsReasonScriber" name="scriber_remarks" class="form-control coe-control" rows="3" maxlength="500"
                                              placeholder="eg. Need a fast writer" disabled></textarea>
                                </div>

                                {{-- Scribers Details — the course's OTs with disabilities, one row
                                     each. Disability Type is chosen here, per OT; Primary / Secondary
                                     Scriber come from Scriber's Master and are open only for an OT
                                     who needs a scriber. Shown whenever "OT with Disabilities" is Yes,
                                     since every OT with a disability needs a type. --}}
                                <div class="col-12 ols-if-disability d-none">
                                    <span class="coe-form-label">Scribers Details</span>
                                    <div class="ols-scribers table-responsive">
                                        <table class="table align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th scope="col">OT</th>
                                                    <th scope="col">Primary Scriber</th>
                                                    <th scope="col">Secondary Scriber</th>
                                                    <th scope="col">Disability Type<span class="coe-req">*</span></th>
                                                    <th scope="col" class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="olsScriberRows"></tbody>
                                        </table>
                                    </div>
                                    <div class="form-text">
                                        Scribers are managed in <a href="{{ route('admin.coe.scriber-master.index') }}" target="_blank" rel="noopener">Scriber's Master</a>.
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="coe-form-label" for="olsAdditionalRemarks">Additional Remarks</label>
                                    <textarea id="olsAdditionalRemarks" name="additional_remarks" class="form-control coe-control" rows="3" maxlength="500"
                                              placeholder="eg. Need a fast writer"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal-footer coe-modal-footer">
                <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn coe-btn coe-btn-primary" id="olsImportSubmit" disabled>Import Laptop Status</button>
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'olsDeleteModal', 'confirmId' => 'olsDeleteConfirm'])

{{-- Column Visibility --}}
<div class="modal fade" id="olsColumnVisibilityModal" tabindex="-1" aria-labelledby="olsColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="olsColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="olsColumnToggleGrid"></div>
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
    var COL = { check: 0, sno: 1, roll: 2, course: 5, batch: 6, action: 17 };
    var DISABILITY_TYPES = @json($disabilityTypes);
    var SCRIBERS = @json($scribers);
    var SCRIBER_ROLE = { primary: 1, secondary: 2 };
    var IMPORT_EXT = @json($importExtensions);
    var IMPORT_MAX_MB = @json($importMaxMb);
    var URLS = {
        participants: @json(route('admin.coe.ot-laptop-status.participants')),
        store: @json(route('admin.coe.ot-laptop-status.store')),
        import: @json(route('admin.coe.ot-laptop-status.import')),
        bulkDestroy: @json(route('admin.coe.ot-laptop-status.bulk-destroy')),
        destroy: @json(route('admin.coe.ot-laptop-status.destroy', ['response' => '__ID__']))
    };
    var CSRF = $('meta[name="csrf-token"]').attr('content') || '';
    var $table = $('#olsTable');

    function ajax(opts) {
        return $.ajax($.extend({ headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } }, opts));
    }

    // The server's own words on a failure; a generic line only when it sent none.
    function failText(xhr) {
        var res = xhr && xhr.responseJSON;
        if (res && res.message) { return res.message; }
        if (xhr && xhr.status === 419) { return 'Your session has expired. Reload the page and try again.'; }
        return 'Something went wrong. Please try again.';
    }
    function showError(xhr) { Swal.fire({ icon: 'error', title: 'Error', text: failText(xhr) }); }

    var dt = CoeGrid.init({
        table: '#olsTable',
        title: @json($isArchived ? 'OT Laptop Status - Archived' : 'OT Laptop Status'),
        snoCol: COL.sno,
        order: [[COL.roll, 'asc']],
        noSort: [COL.check, COL.sno, COL.action],
        exclude: [COL.check, COL.action],
        filters: { '#olsFilterCourse': COL.course, '#olsFilterBatch': COL.batch },
        rowFilters: { '#olsFilterService': 'service' },
        reset: '#olsResetFilters',
        download: '#olsDownloadBtn',
        print: '#olsPrintBtn',
        searchToggle: '#olsSearchToggle',
        searchSlot: '#olsDtSearch',
        colvisGrid: '#olsColumnToggleGrid',
        // v2: Disability Type and Additional Remarks shifted the column indexes.
        colvisKey: 'coeOtLaptopStatus:hiddenColumns:v2',
        empty: { title: 'No Laptop Status Records', zeroTitle: 'No Laptop Status Records Found', text: 'Import laptop status to get started.' }
    });

    /* ── Row selection ───────────────────────────────────────────────────────
       "Select all" ticks every row the current filters/search let through —
       not just the visible page — so Delete Selected matches what the user
       asked for. */
    function selectedRows() {
        return dt.rows({ search: 'applied' }).nodes().to$().filter(function () {
            return $(this).find('.ols-row-check').prop('checked');
        });
    }

    function syncSelection() {
        var all = dt.rows({ search: 'applied' }).nodes().to$();
        var n = selectedRows().length;
        $('#olsSelectedCount').text(n);
        $('#olsBulkDelete').toggleClass('d-none', n === 0);
        $('#olsCheckAll')
            .prop('checked', n > 0 && n === all.length)
            .prop('indeterminate', n > 0 && n < all.length);
    }

    $('#olsCheckAll').on('change', function () {
        dt.rows({ search: 'applied' }).nodes().to$().find('.ols-row-check').prop('checked', this.checked);
        syncSelection();
    });
    $table.on('change', '.ols-row-check', syncSelection);
    // A filter that hides a ticked row takes it out of the selection count.
    dt.on('search.dt', function () { setTimeout(syncSelection, 0); });

    /* ── Delete (row) · Delete Selected (bulk) ───────────────────────────────
       Rows leave the grid only after the server confirms the delete — and for
       a bulk delete, only the ids it says it actually deleted. */
    CoeGrid.confirmDelete({
        table: '#olsTable',
        modal: '#olsDeleteModal',
        confirm: '#olsDeleteConfirm',
        bulkTrigger: '#olsBulkDelete',
        onConfirm: function ($row) {
            if ($row) {
                ajax({ url: URLS.destroy.replace('__ID__', encodeURIComponent($row.attr('data-coe-id'))), method: 'DELETE' })
                    .done(function (res) {
                        dt.row($row).remove().draw(false);
                        syncSelection();
                        Swal.fire({ icon: 'success', title: 'Success', text: res.message });
                    })
                    .fail(showError);
                return;
            }

            var ids = selectedRows().map(function () { return $(this).attr('data-coe-id'); }).get();
            if (!ids.length) { return; }
            ajax({ url: URLS.bulkDestroy, method: 'POST', data: { ids: ids } })
                .done(function (res) {
                    var gone = (res.deleted || []).map(String);
                    var $gone = dt.rows().nodes().to$().filter(function () {
                        return gone.indexOf($(this).attr('data-coe-id')) !== -1;
                    });
                    dt.rows($gone).remove().draw(false);
                    syncSelection();
                    Swal.fire({ icon: 'success', title: 'Success', text: res.message });
                })
                .fail(showError);
        }
    });

    /* ── Import modal ────────────────────────────────────────────────────── */
    var importModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('olsImportModal'));
    var $submit = $('#olsImportSubmit');
    var bulkFile = null;
    var busy = false;

    function mode() { return $('#olsModeSingleTab').hasClass('active') ? 'single' : 'bulk'; }
    function syncSubmit() { $submit.prop('disabled', busy || (mode() === 'bulk' ? !bulkFile : false)); }
    function setBusy(on) { busy = on; syncSubmit(); }

    var dz = CoeGrid.dropzone({
        root: '#olsImportFile',
        accept: new RegExp('\\.(' + IMPORT_EXT.join('|') + ')$', 'i'),
        acceptText: 'Only ' + IMPORT_EXT.map(function (e) { return '.' + e; }).join(', ') + ' files are allowed.',
        maxBytes: IMPORT_MAX_MB * 1024 * 1024,
        onChange: function (file) { bulkFile = file; syncSubmit(); }
    });

    $('#olsModeBulkTab, #olsModeSingleTab').on('shown.bs.tab', syncSubmit);

    /* Single record — dependent OT list, conditional disability / scriber fields. */
    var $course = $('#olsCourse'), $ot = $('#olsOt'), $dis = $('#olsDisability');
    var $scrRows = $('#olsScriberRows');
    var $modalBody = $('#olsImportModal .modal-body');
    var otsByPk = {};
    var otList = [];   // server order (roll number), for the Scribers Details OT lists
    var otRequest = 0;

    [$course, $dis].forEach(function ($s) {
        $s.select2({ width: '100%', dropdownParent: $modalBody, minimumResultsForSearch: $s.is($course) ? 0 : Infinity });
    });
    // Multiple: the list stays open while picking, so several OTs go in one pass.
    $ot.select2({ width: '100%', dropdownParent: $modalBody, placeholder: 'Select OT', closeOnSelect: false });

    function otLabel(o) { return o.name + (o.code ? ' - ' + o.code : ''); }
    function selectedOts() { return $ot.val() || []; }

    // The OT list comes from the server per Course. A response that arrives
    // after the selection moved on is dropped, not shown.
    function fillOts() {
        var course = $course.val();
        var ticket = ++otRequest;
        otsByPk = {};
        otList = [];
        $ot.empty().trigger('change.select2');
        $scrRows.empty();
        syncScriberRows();
        if (!course) { return; }

        ajax({ url: URLS.participants, method: 'GET', data: { course: course } })
            .done(function (res) {
                if (ticket !== otRequest) { return; }
                otList = res.data || [];
                otList.forEach(function (o) {
                    otsByPk[o.pk] = o;
                    $ot.append(new Option(otLabel(o) + (o.answered ? ' (recorded)' : ''), String(o.pk)));
                });
                $ot.trigger('change.select2');
                // Rebuilt from scratch now the course's assignments are known.
                $scrRows.empty();
                syncScriberRows();
                if (!otList.length) {
                    fieldError($ot, 'No OTs on this course.');
                }
            })
            .fail(function (xhr) { if (ticket === otRequest) { showError(xhr); } });
    }
    $course.on('change', fillOts);

    function radio(name) { return $('input[name="' + name + '"]:checked').val(); }
    function setRadio(name, yes) { $('input[name="' + name + '"][value="' + (yes ? 'yes' : 'no') + '"]').prop('checked', true); }

    // With exactly one OT picked, the form opens with that OT's status, so a
    // correction starts from what is on record instead of silently
    // overwriting it; one with no status opens blank. Once a second OT is
    // added the form is left as it is — the answers on screen go to all of them.
    $ot.on('change', function () {
        var picked = selectedOts();
        if (picked.length !== 1) { syncScriberRows(true); return; }
        var o = otsByPk[picked[0]];
        var answered = !!(o && o.answered);
        $dis.val(answered ? (o.has_disability ? 'yes' : 'no') : '').trigger('change.select2');
        setRadio('ols_laptop', answered && o.laptop_required === 1);
        setRadio('ols_scriber', answered && o.scriber_required === 1);
        syncConditional();
        $('#olsReasonLaptop').val(answered ? o.reason : '');
        $('#olsReasonScriber').val(answered ? o.scriber_remarks : '');
        $('#olsAdditionalRemarks').val(answered ? o.additional_remarks : '');
    });

    function syncConditional() {
        var disability = $dis.val() === 'yes';
        var laptop = radio('ols_laptop') === 'yes';
        var scriber = disability && radio('ols_scriber') === 'yes';

        $('.ols-if-disability').toggleClass('d-none', !disability);

        // A reason is only asked for when the answer is Yes.
        $('#olsReasonLaptop').prop('disabled', !laptop).prop('required', laptop);
        $('.ols-req-laptop').toggleClass('d-none', !laptop);
        $('#olsReasonScriber').prop('disabled', !scriber).prop('required', scriber);
        $('.ols-req-scriber').toggleClass('d-none', !scriber);
        if (!laptop) { $('#olsReasonLaptop').val(''); }
        if (!scriber) { $('#olsReasonScriber').val(''); }

        syncScriberRows(true);
    }
    $dis.on('change', syncConditional);
    $('#olsSingleForm').on('change', 'input[type=radio]', syncConditional);

    /* ── Scribers Details ────────────────────────────────────────────────────
       Each row's OT list holds only the course's OTs with disabilities: the
       ones on record, plus the OTs being edited when this form says Yes. An
       OT already picked in one row is not offered in another.
       Disability Type is chosen in the row. Primary / Secondary Scriber list
       Scriber's Master by role, and are open only for an OT who needs a
       scriber — by this form's answer for the OTs being edited, by their
       record for the rest. */
    function isEdited(o) { return selectedOts().indexOf(String(o.pk)) !== -1; }

    function needsScriber(o) {
        return isEdited(o)
            ? $dis.val() === 'yes' && radio('ols_scriber') === 'yes'
            : o.scriber_required === 1;
    }

    function scriberEligibleOts() {
        return otList.filter(function (o) { return isEdited(o) ? $dis.val() === 'yes' : o.has_disability === 1; });
    }

    function scriberRowHtml() {
        var types = '<option value="">Select Type</option>';
        Object.keys(DISABILITY_TYPES).forEach(function (k) {
            types += '<option value="' + k + '">' + $('<span>').text(DISABILITY_TYPES[k]).html() + '</option>';
        });
        return '<tr>' +
            '<td><select class="form-select coe-control ols-scr-ot" aria-label="OT"></select></td>' +
            '<td><select class="form-select coe-control ols-scr-primary" aria-label="Primary Scriber"></select></td>' +
            '<td><select class="form-select coe-control ols-scr-secondary" aria-label="Secondary Scriber"></select></td>' +
            '<td class="ols-scr-type"><select class="form-select coe-control ols-scr-dtype" aria-label="Disability Type">' + types + '</select></td>' +
            '<td class="ols-scr-act">' +
                '<button type="button" class="ols-scr-btn ols-scr-btn--remove" title="Remove row" aria-label="Remove row">' +
                    '<i class="bi bi-dash-lg" aria-hidden="true"></i></button>' +
                '<button type="button" class="ols-scr-btn ols-scr-btn--add" title="Add row" aria-label="Add row">' +
                    '<i class="bi bi-plus-lg" aria-hidden="true"></i></button>' +
            '</td>' +
        '</tr>';
    }

    // A scriber select of one role. Inactive scribers are offered only to the
    // row that already holds one, so an old assignment still reads correctly.
    function fillScriberSelect($s, role, value) {
        var placeholder = role === SCRIBER_ROLE.primary ? 'Select Primary Scribers' : 'Select Secondary Scribers';
        value = value ? String(value) : '';
        $s.empty().append(new Option(placeholder, ''));
        SCRIBERS.forEach(function (sc) {
            if (sc.role !== role || (!sc.active && String(sc.pk) !== value)) { return; }
            $s.append(new Option(sc.name + (sc.active ? '' : ' (Inactive)'), String(sc.pk)));
        });
        $s.val(value);
    }

    // A row's scribers and type, from what is on record for its OT.
    function fillRowFromRecord($row, o) {
        fillScriberSelect($row.find('.ols-scr-primary'), SCRIBER_ROLE.primary, o && o.primary_scriber_pk);
        fillScriberSelect($row.find('.ols-scr-secondary'), SCRIBER_ROLE.secondary, o && o.secondary_scriber_pk);
        $row.find('.ols-scr-dtype').val(o && o.disability_type ? String(o.disability_type) : '');
    }

    function addScriberRow(otPk) {
        var $row = $(scriberRowHtml()).appendTo($scrRows);
        var o = otPk ? otsByPk[otPk] : null;
        if (o) { $row.find('.ols-scr-ot').data('pick', String(otPk)); }
        fillRowFromRecord($row, o);
        return $row;
    }

    // Rebuilds every row's OT options from the current state, keeping each
    // row's pick while it is still eligible. The table opens with every OT
    // already assigned a scriber — it is the course's whole assignment list,
    // and the server clears whoever is left out.
    //
    // autoPick (on an OT, disability or scriber answer change): each OT being
    // edited that qualifies gets a row, taking the place of an empty one. Not
    // on the table's own +/− clicks, so removing such a row sticks.
    function syncScriberRows(autoPick) {
        var eligible = scriberEligibleOts();
        if (!$scrRows.children().length) {
            eligible.forEach(function (o) {
                if (o.primary_scriber_pk || o.secondary_scriber_pk) { addScriberRow(o.pk); }
            });
            if (!$scrRows.children().length) { addScriberRow(null); }
        }

        // A seeded row's OT is still a pending data('pick'), not yet its value.
        function rowPick(el) { var p = $(el).data('pick'); return p !== undefined ? String(p) : ($(el).val() || ''); }

        if (autoPick) {
            var picked = $scrRows.find('.ols-scr-ot').map(function () { return rowPick(this); }).get();
            selectedOts().forEach(function (pk) {
                if (picked.indexOf(pk) !== -1 || !eligible.some(function (o) { return String(o.pk) === pk; })) { return; }
                $scrRows.find('.ols-scr-ot').filter(function () { return !rowPick(this); }).first().closest('tr').remove();
                addScriberRow(pk);
                picked.push(pk);
            });
        }

        var $selects = $scrRows.find('.ols-scr-ot');
        $selects.each(function () {
            var $s = $(this);
            var pick = rowPick(this);
            $s.removeData('pick');
            var takenElsewhere = $selects.not($s).map(function () { return rowPick(this); }).get();

            $s.empty().append(new Option('Select OT', ''));
            eligible.forEach(function (o) {
                var opt = new Option(otLabel(o), String(o.pk));
                opt.disabled = takenElsewhere.indexOf(String(o.pk)) !== -1;
                $s.append(opt);
            });
            $s.val(eligible.some(function (o) { return String(o.pk) === pick; }) ? pick : '');

            // No OT, no type; no scriber needed, no scribers.
            var o = otsByPk[$s.val()];
            var $row = $s.closest('tr');
            var scribe = !!o && needsScriber(o);
            $row.find('.ols-scr-dtype').prop('disabled', !o);
            $row.find('.ols-scr-primary, .ols-scr-secondary').prop('disabled', !scribe);
            if (!scribe) { $row.find('.ols-scr-primary, .ols-scr-secondary').val(''); }
        });

        // "+" only on the last row, and only while there is an OT left to add.
        var $rows = $scrRows.children();
        $rows.find('.ols-scr-btn--add').addClass('d-none');
        $rows.last().find('.ols-scr-btn--add').toggleClass('d-none', $rows.length >= eligible.length);
    }

    // Picking an OT brings in the scribers and type already on record for them.
    $scrRows.on('change', '.ols-scr-ot', function () {
        fillRowFromRecord($(this).closest('tr'), otsByPk[this.value]);
        syncScriberRows();
    });
    $scrRows.on('change', '.ols-scr-dtype', function () { if (this.value) { fieldError($scrRows, ''); } });
    $scrRows.on('click', '.ols-scr-btn--add', function () {
        addScriberRow(null);
        syncScriberRows();
    });
    // The last row is cleared rather than removed, so the table never empties.
    $scrRows.on('click', '.ols-scr-btn--remove', function () {
        var $row = $(this).closest('tr');
        if ($scrRows.children().length > 1) {
            $row.remove();
        } else {
            $row.find('.ols-scr-ot, .ols-scr-primary, .ols-scr-secondary, .ols-scr-dtype').val('');
        }
        syncScriberRows();
    });

    // Only rows with an OT are sent; the scriber selects may be left blank.
    function scriberPayload() {
        return $scrRows.children().map(function () {
            var $r = $(this);
            var ot = $r.find('.ols-scr-ot').val();
            if (!ot) { return null; }
            return {
                student_master_pk: ot,
                primary_scriber_pk: $r.find('.ols-scr-primary').val() || '',
                secondary_scriber_pk: $r.find('.ols-scr-secondary').val() || '',
                disability_type: $r.find('.ols-scr-dtype').val() || ''
            };
        }).get();
    }

    // Every OT being edited must be in the table, and every row needs a type.
    function scriberTableError() {
        var rows = scriberPayload();
        var listed = rows.map(function (r) { return String(r.student_master_pk); });
        if (selectedOts().some(function (pk) { return listed.indexOf(pk) === -1; })) {
            return 'Add every selected OT to Scribers Details.';
        }
        if (rows.some(function (r) { return !r.disability_type; })) {
            return 'Select the disability type for every OT in Scribers Details.';
        }
        return '';
    }

    function fieldError($el, msg) {
        var $wrap = $el.closest('[class*="col-"]');
        $wrap.find('.coe-field-error').remove();
        $el.toggleClass('is-invalid', !!msg);
        $wrap.find('.select2-selection').toggleClass('is-invalid', !!msg);
        if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($wrap); }
        return !msg;
    }

    // An error clears as soon as its field is answered.
    $course.add($ot).add($dis).on('change', function () { if (this.value) { fieldError($(this), ''); } });
    $('#olsReasonLaptop, #olsReasonScriber').on('input', function () { if ($.trim(this.value)) { fieldError($(this), ''); } });

    function validateSingle() {
        var ok = true;
        ok = fieldError($course, $course.val() ? '' : 'Select a course.') && ok;
        ok = fieldError($ot, selectedOts().length ? '' : 'Select at least one OT.') && ok;
        ok = fieldError($dis, $dis.val() ? '' : 'Select whether the OT has a disability.') && ok;
        ok = fieldError($scrRows, $dis.val() === 'yes' ? scriberTableError() : '') && ok;
        var $rl = $('#olsReasonLaptop'), $rs = $('#olsReasonScriber');
        ok = fieldError($rl, $rl.prop('required') && !$.trim($rl.val()) ? 'Enter the reason for the laptop.' : '') && ok;
        ok = fieldError($rs, $rs.prop('required') && !$.trim($rs.val()) ? 'Enter the reason for the scriber.' : '') && ok;
        return ok;
    }

    // Server field name => the control its 422 message belongs under.
    var FIELD_FOR = {
        course: '#olsCourse', student_master_pks: '#olsOt',
        has_disability: '#olsDisability', scribers: '#olsScriberRows',
        reason: '#olsReasonLaptop', scriber_remarks: '#olsReasonScriber',
        additional_remarks: '#olsAdditionalRemarks',
        laptop_required: 'input[name="ols_laptop"]', scriber_required: 'input[name="ols_scriber"]'
    };

    function showServerErrors(xhr) {
        var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
        var unplaced = [];
        Object.keys(errors).forEach(function (field) {
            var $el = $(FIELD_FOR[field] || '').first();
            if ($el.length) { fieldError($el, errors[field][0]); } else { unplaced.push(errors[field][0]); }
        });
        if (unplaced.length || !Object.keys(errors).length) {
            Swal.fire({ icon: 'error', title: 'Error', text: unplaced.length ? unplaced.join(' ') : failText(xhr) });
        }
    }

    function singlePayload() {
        var disability = $dis.val() === 'yes';
        var laptop = radio('ols_laptop') === 'yes';
        var data = {
            course: $course.val(),
            student_master_pks: selectedOts(),
            laptop_required: laptop ? '1' : '0',
            reason: laptop ? $.trim($('#olsReasonLaptop').val()) : '',
            has_disability: disability ? '1' : '0',
            additional_remarks: $.trim($('#olsAdditionalRemarks').val())
        };
        // The scriber pair and Scribers Details (with each OT's Disability Type)
        // are only posted when "OT with Disabilities" is Yes. scribers_present
        // tells the server the table was sent even when every row was removed.
        if (disability) {
            var scriber = radio('ols_scriber') === 'yes';
            data.scriber_required = scriber ? '1' : '0';
            data.scriber_remarks = scriber ? $.trim($('#olsReasonScriber').val()) : '';
            data.scribers_present = '1';
            data.scribers = scriberPayload();
        }
        return data;
    }

    function resetSingle() {
        $course.val('').trigger('change.select2');
        $dis.val('').trigger('change.select2');
        $scrRows.empty();
        fillOts();
        $('input[name="ols_laptop"][value="no"], input[name="ols_scriber"][value="no"]').prop('checked', true);
        $('#olsReasonLaptop, #olsReasonScriber, #olsAdditionalRemarks').val('');
        $('#olsSingleForm .coe-field-error').remove();
        $('#olsSingleForm .is-invalid').removeClass('is-invalid');
        syncConditional();
    }

    // Saved rows come back from the server on a reload rather than being drawn
    // here, so the grid can never show a row the database does not hold.
    function reloadAfter(swalPromise) {
        swalPromise.then(function () { window.location.reload(); });
    }

    function submitBulk() {
        var form = new FormData();
        form.append('file', bulkFile);
        setBusy(true);
        ajax({ url: URLS.import, method: 'POST', data: form, processData: false, contentType: false })
            .done(function (res) {
                var saved = (res.added || 0) + (res.updated || 0);
                var failures = res.failures || [];
                importModal.hide();

                var $html = $('<div class="text-start"></div>').append($('<p class="mb-2"></p>').text(res.message));
                if (failures.length) {
                    var $list = $('<ul class="small mb-0 ps-3" style="max-height:220px;overflow:auto"></ul>');
                    failures.forEach(function (f) { $list.append($('<li></li>').text(f)); });
                    $html.append($('<p class="fw-semibold mb-1"></p>').text('Rejected rows:')).append($list);
                }

                var shown = Swal.fire({
                    icon: failures.length ? (saved ? 'warning' : 'error') : 'success',
                    title: failures.length ? (saved ? 'Imported with errors' : 'Nothing imported') : 'Success',
                    html: $html[0]
                });
                if (saved) { reloadAfter(shown); }
            })
            .fail(showError)
            .always(function () { setBusy(false); });
    }

    function submitSingle() {
        if (!validateSingle()) { return; }
        setBusy(true);
        ajax({ url: URLS.store, method: 'POST', data: singlePayload() })
            .done(function (res) {
                importModal.hide();
                reloadAfter(Swal.fire({ icon: 'success', title: 'Success', text: res.message }));
            })
            .fail(function (xhr) { if (xhr.status === 422) { showServerErrors(xhr); } else { showError(xhr); } })
            .always(function () { setBusy(false); });
    }

    $submit.on('click', function () {
        if (busy) { return; }
        if (mode() === 'bulk') {
            if (bulkFile) { submitBulk(); }
            return;
        }
        submitSingle();
    });

    $('#olsImportModal').on('hidden.bs.modal', function () {
        dz.reset();
        resetSingle();
        bootstrap.Tab.getOrCreateInstance(document.getElementById('olsModeBulkTab')).show();
    });

    resetSingle();
});
</script>
@endpush
