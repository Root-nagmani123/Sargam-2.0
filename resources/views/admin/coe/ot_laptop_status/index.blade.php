@extends('admin.layouts.master')

@section('title', 'OT Laptop Status')

@push('styles')
{{-- Select2 JS is global; its CSS is per page (filters + the OT picker are searchable). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
{{-- COE module layer (coe-*), shared with the question-paper screens. --}}
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
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
                                <th scope="col">Need for Scriber</th>
                                <th scope="col">Remarks for Scriber</th>
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
                                    <td>{{ $row['scriber'] ? 'Yes' : 'No' }}</td>
                                    <td class="coe-strong coe-col-wrap">{!! $row['scriber_remarks'] ? e($row['scriber_remarks']) : $dash !!}</td>
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
    <div class="modal-dialog modal-dialog-centered">
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
                                <li><a href="#" id="olsTemplateLink">Download the template file</a> to ensure your Excel file has the correct format for importing members.</li>
                                <li><span class="coe-tip">Tip:</span> Make sure your Excel file matches the template format exactly to avoid import errors.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Single record --}}
                    <div class="tab-pane fade" id="olsModeSingle" role="tabpanel" aria-labelledby="olsModeSingleTab">
                        <form id="olsSingleForm" class="coe-form" novalidate>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="coe-form-label" for="olsCourse">Course Name<span class="coe-req">*</span></label>
                                    <select id="olsCourse" class="form-select coe-control" required>
                                        <option value="">Select Course</option>
                                        @foreach ($courses as $course)
                                            <option value="{{ $course }}">{{ $course }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="coe-form-label" for="olsBatch">Batch<span class="coe-req">*</span></label>
                                    <select id="olsBatch" class="form-select coe-control" required>
                                        <option value="">Select Batch</option>
                                        @foreach ($batches as $batch)
                                            <option value="{{ $batch }}">{{ $batch }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="coe-form-label" for="olsOt">OT<span class="coe-req">*</span></label>
                                    {{-- Filled from the JSON list for the chosen Course + Batch. --}}
                                    <select id="olsOt" class="form-select coe-control" required>
                                        <option value="">Select OT</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="coe-form-label" for="olsDisability">OT with Disabilities<span class="coe-req">*</span></label>
                                    <select id="olsDisability" class="form-select coe-control" required>
                                        <option value="no" selected>No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <span class="coe-form-label" id="olsLaptopLabel">Laptop Required<span class="coe-req">*</span></span>
                                    <div class="coe-radio-row" role="radiogroup" aria-labelledby="olsLaptopLabel">
                                        <label class="coe-radio"><input type="radio" name="ols_laptop" value="yes"> Yes</label>
                                        <label class="coe-radio"><input type="radio" name="ols_laptop" value="no" checked> No</label>
                                    </div>
                                </div>
                                <div class="col-sm-6 ols-if-disability d-none">
                                    <span class="coe-form-label" id="olsScriberLabel">Scriber Required<span class="coe-req">*</span></span>
                                    <div class="coe-radio-row" role="radiogroup" aria-labelledby="olsScriberLabel">
                                        <label class="coe-radio"><input type="radio" name="ols_scriber" value="yes"> Yes</label>
                                        <label class="coe-radio"><input type="radio" name="ols_scriber" value="no" checked> No</label>
                                    </div>
                                </div>

                                <div class="col-12 ols-reason-laptop-col">
                                    <label class="coe-form-label" for="olsReasonLaptop">
                                        Reason for Laptop<span class="coe-req ols-req-laptop d-none">*</span>
                                    </label>
                                    <textarea id="olsReasonLaptop" class="form-control coe-control" rows="2" maxlength="255"
                                              placeholder="eg. Don't have PC" disabled></textarea>
                                </div>
                                <div class="col-sm-6 ols-if-disability d-none">
                                    <label class="coe-form-label" for="olsReasonScriber">
                                        Reason for Scriber<span class="coe-req ols-req-scriber d-none">*</span>
                                    </label>
                                    <textarea id="olsReasonScriber" class="form-control coe-control" rows="2" maxlength="255"
                                              placeholder="eg. Need a fast writer" disabled></textarea>
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
    var COL = { check: 0, sno: 1, roll: 2, course: 5, batch: 6, action: 13 };
    var OTS = @json($ots);
    var IMPORT_EXT = @json($importExtensions);
    var IMPORT_MAX_MB = @json($importMaxMb);
    var $table = $('#olsTable');
    var DELETE_ACTION = '<div class="coe-act-group" role="group" aria-label="Row actions">' +
        '<button type="button" class="coe-act coe-act--del">' +
        '<span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>' +
        '<span class="coe-act__label">Delete</span></button></div>';

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
        colvisKey: 'coeOtLaptopStatus:hiddenColumns:v1',
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
       DESIGN PREVIEW: no backend yet, so rows are removed in the browser only.
       When the endpoint exists, DELETE first and redraw from the response. */
    CoeGrid.confirmDelete({
        table: '#olsTable',
        modal: '#olsDeleteModal',
        confirm: '#olsDeleteConfirm',
        bulkTrigger: '#olsBulkDelete',
        onConfirm: function ($row) {
            var $targets = $row ? $row : selectedRows();
            var n = $targets.length;
            dt.rows($targets).remove().draw(false);
            syncSelection();
            Swal.fire({ icon: 'success', title: 'Success', text: n === 1 ? 'Record deleted.' : n + ' records deleted.' });
        }
    });

    /* ── Import modal ────────────────────────────────────────────────────── */
    var importModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('olsImportModal'));
    var $submit = $('#olsImportSubmit');
    var bulkFile = null;

    function mode() { return $('#olsModeSingleTab').hasClass('active') ? 'single' : 'bulk'; }
    function syncSubmit() { $submit.prop('disabled', mode() === 'bulk' ? !bulkFile : false); }

    var dz = CoeGrid.dropzone({
        root: '#olsImportFile',
        accept: new RegExp('\\.(' + IMPORT_EXT.join('|') + ')$', 'i'),
        acceptText: 'Only ' + IMPORT_EXT.map(function (e) { return '.' + e; }).join(', ') + ' files are allowed.',
        maxBytes: IMPORT_MAX_MB * 1024 * 1024,
        onChange: function (file) { bulkFile = file; syncSubmit(); }
    });

    $('#olsModeBulkTab, #olsModeSingleTab').on('shown.bs.tab', syncSubmit);

    // The template the import expects — generated here so it can't go stale
    // against a file on disk. Same header order as the grid.
    $('#olsTemplateLink').on('click', function (e) {
        e.preventDefault();
        var header = ['Roll Number', 'Course', 'Batch', 'Laptop Required (Yes/No)', 'Reason for Laptop',
            'OT with Disability (Yes/No)', 'Scriber Required (Yes/No)', 'Reason for Scriber'];
        var blob = new Blob([header.join(',') + '\r\n'], { type: 'text/csv;charset=utf-8' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'laptop-status-template.csv';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 0);
    });

    /* Single record — dependent OT list, conditional scriber fields. */
    var $course = $('#olsCourse'), $batch = $('#olsBatch'), $ot = $('#olsOt'), $dis = $('#olsDisability');
    var $modalBody = $('#olsImportModal .modal-body');

    [$course, $batch, $dis].forEach(function ($s) {
        $s.select2({ width: '100%', dropdownParent: $modalBody });
    });
    $ot.select2({ width: '100%', dropdownParent: $modalBody, placeholder: 'Select OT' });
    // The searchbox reads "Search OT", as designed.
    $ot.on('select2:open', function () {
        $('.select2-container--open .select2-search__field').attr('placeholder', 'Search OT');
    });

    function fillOts() {
        var course = $course.val(), batch = $batch.val();
        $ot.empty().append(new Option('', '', true, true));
        OTS.filter(function (o) { return (!course || o.course === course) && (!batch || o.batch === batch); })
            .forEach(function (o) {
                var opt = new Option(o.name + ' -' + o.code, String(o.id));
                $ot.append(opt);
            });
        $ot.trigger('change.select2');
    }
    $course.on('change', fillOts);
    $batch.on('change', fillOts);

    function radio(name) { return $('input[name="' + name + '"]:checked').val(); }

    function syncConditional() {
        var disability = $dis.val() === 'yes';
        var laptop = radio('ols_laptop') === 'yes';
        var scriber = disability && radio('ols_scriber') === 'yes';

        $('.ols-if-disability').toggleClass('d-none', !disability);
        // Two reasons side by side when both can apply, otherwise one full width.
        $('.ols-reason-laptop-col').toggleClass('col-12', !disability).toggleClass('col-sm-6', disability);

        // A reason is only asked for when the answer is Yes.
        $('#olsReasonLaptop').prop('disabled', !laptop).prop('required', laptop);
        $('.ols-req-laptop').toggleClass('d-none', !laptop);
        $('#olsReasonScriber').prop('disabled', !scriber).prop('required', scriber);
        $('.ols-req-scriber').toggleClass('d-none', !scriber);
        if (!laptop) { $('#olsReasonLaptop').val(''); }
        if (!scriber) { $('#olsReasonScriber').val(''); }
    }
    $dis.on('change', syncConditional);
    $('#olsSingleForm').on('change', 'input[type=radio]', syncConditional);

    function fieldError($el, msg) {
        var $wrap = $el.closest('[class*="col-"]');
        $wrap.find('.coe-field-error').remove();
        $el.toggleClass('is-invalid', !!msg);
        $wrap.find('.select2-selection').toggleClass('is-invalid', !!msg);
        if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($wrap); }
        return !msg;
    }

    // An error clears as soon as its field is answered.
    $course.add($batch).add($ot).on('change', function () { if (this.value) { fieldError($(this), ''); } });
    $('#olsReasonLaptop, #olsReasonScriber').on('input', function () { if ($.trim(this.value)) { fieldError($(this), ''); } });

    function validateSingle() {
        var ok = true;
        ok = fieldError($course, $course.val() ? '' : 'Select a course.') && ok;
        ok = fieldError($batch, $batch.val() ? '' : 'Select a batch.') && ok;
        ok = fieldError($ot, $ot.val() ? '' : 'Select an OT.') && ok;
        var $rl = $('#olsReasonLaptop'), $rs = $('#olsReasonScriber');
        ok = fieldError($rl, $rl.prop('required') && !$.trim($rl.val()) ? 'Enter the reason for the laptop.' : '') && ok;
        ok = fieldError($rs, $rs.prop('required') && !$.trim($rs.val()) ? 'Enter the reason for the scriber.' : '') && ok;
        return ok;
    }

    function resetSingle() {
        $course.val('').trigger('change.select2');
        $batch.val('').trigger('change.select2');
        $dis.val('no').trigger('change.select2');
        fillOts();
        $('input[name="ols_laptop"][value="no"], input[name="ols_scriber"][value="no"]').prop('checked', true);
        $('#olsReasonLaptop, #olsReasonScriber').val('');
        $('#olsSingleForm .coe-field-error').remove();
        $('#olsSingleForm .is-invalid').removeClass('is-invalid');
        syncConditional();
    }

    function addRow() {
        var ot = OTS.find(function (o) { return String(o.id) === $ot.val(); });
        var laptop = radio('ols_laptop') === 'yes';
        var disability = $dis.val() === 'yes';
        var scriber = disability && radio('ols_scriber') === 'yes';
        var dash = '<span class="coe-muted">&mdash;</span>';
        var esc = function (t) { return $('<div>').text(t).html(); };
        var reason = $.trim($('#olsReasonLaptop').val());
        var remarks = $.trim($('#olsReasonScriber').val());

        var node = dt.row.add([
            '<input type="checkbox" class="form-check-input m-0 ols-row-check" aria-label="Select ' + esc(ot.name) + '">',
            '',
            esc(ot.roll),
            esc(ot.name),
            esc(ot.code),
            esc(ot.course),
            esc(ot.batch),
            laptop ? 'Yes' : 'No',
            reason ? esc(reason) : dash,
            disability ? 'Yes' : 'No',
            scriber ? 'Yes' : 'No',
            remarks ? esc(remarks) : dash,
            'Admin',
            DELETE_ACTION
        ]).draw(false).node();

        // Same cell classes as the server-rendered rows.
        var $cells = $(node).attr('data-service', ot.service).children('td');
        $cells.eq(COL.check).addClass('coe-col-check');
        $cells.eq(2).addClass('coe-nowrap');
        $cells.eq(3).addClass('coe-col-wrap');
        $cells.eq(5).addClass('coe-strong');
        $cells.eq(7).addClass('coe-yesno');
        $cells.eq(8).addClass('coe-col-truncate').attr('title', reason || null);
        $cells.eq(11).addClass('coe-strong coe-col-wrap');
    }

    $submit.on('click', function () {
        if (mode() === 'bulk') {
            if (!bulkFile) { return; }
            // DESIGN PREVIEW: no import endpoint yet — say so rather than fake a result.
            Swal.fire({
                icon: 'info',
                title: 'Import not connected yet',
                text: '"' + bulkFile.name + '" passed the file checks. The import runs once the backend is wired up.',
                confirmButtonColor: '#004384'
            });
            return;
        }

        if (!validateSingle()) { return; }
        addRow();
        importModal.hide();
        Swal.fire({ icon: 'success', title: 'Success', text: 'Laptop status added.' });
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
