@extends('admin.layouts.master')

@section('title', 'Examination Drive Configuration')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.edc-status-pending { background:#fff3cd;color:#856404; }
.edc-status-active  { background:#d1f0e0;color:#0d6832; }
.edc-status-closed  { background:#f8d7da;color:#842029; }
.edc-status-draft   { background:#e2e8f0;color:#475569; }
/* Subject/Faculty mapping table in modal */
.edc-map-table       { width:100%;border-collapse:collapse; }
.edc-map-table th    { padding:8px 10px;background:var(--ds-surface-2);font-size:12px;
                        font-weight:600;color:var(--ds-ink-muted);border-bottom:1px solid var(--ds-line); }
.edc-map-table td    { padding:6px 6px;border-bottom:1px solid var(--ds-line);vertical-align:middle; }
.edc-map-row-del     { background:#dc3545;color:#fff;border:none;border-radius:50%;
                        width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer; }
.edc-map-row-add     { background:#004384;color:#fff;border:none;border-radius:50%;
                        width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer; }
.edc-map-row-del:hover{ background:#b02a37; }
.edc-map-row-add:hover{ background:#003070; }
</style>
@endpush

@section('setup_content')
@php $isArchived = $scope === 'archived'; @endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Examination Drive Configuration" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Roll Numbers']">
        <button type="button" class="btn coe-btn-header" id="edcCreateBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Create Examination Drive</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs + Download/Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Scope">
            <a href="{{ route('admin.coe.examination-drive.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}"
               @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.examination-drive.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}"
               @if ($isArchived) aria-current="page" @endif>Archived</a>
        </nav>
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="edcDownloadBtn" class="btn programme-dt-btn-columns" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="edcPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="edcFilterCourse" class="form-select" aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="edcFilterTerm" class="form-select" aria-label="Term">
                            <option value="">Term</option>
                            @foreach ($terms as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="edcFilterPhase" class="form-select" aria-label="Phase">
                            <option value="">Phase</option>
                            @foreach ($phases as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="edcFilterType" class="form-select" aria-label="Examination Type">
                            <option value="">Examination Type</option>
                            @foreach ($examTypes as $et)<option value="{{ $et }}">{{ $et }}</option>@endforeach
                        </select>
                    </div>
                    <button type="button" id="edcResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#edcColumnVisibilityModal">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="edcDtSearch" class="programme-dt-search d-none" data-dt-search-for="edcTable"></div>
                    <button type="button" id="edcSearchToggle" class="coe-search-toggle"
                            aria-controls="edcDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="edcTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>S. NO.</th>
                                <th>DRIVE NAME</th>
                                <th>EXAMINATION TYPE</th>
                                <th>TERM</th>
                                <th>COURSE</th>
                                <th>ACADEMIC SESSION</th>
                                <th>FACULTY</th>
                                <th class="coe-col-center">SUB-COORDINATES</th>
                                <th class="coe-col-center">COMPONENTS</th>
                                <th>START DATE</th>
                                <th>END DATE</th>
                                <th class="coe-col-center">STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($drives as $drive)
                            @php
                                $statusClass = match($drive['status']) {
                                    'Pending at COE' => 'edc-status-pending',
                                    'Active'         => 'edc-status-active',
                                    'Closed'         => 'edc-status-closed',
                                    default          => 'edc-status-draft',
                                };
                                $dash = '<span class="coe-muted">-</span>';
                            @endphp
                            <tr data-coe-id="{{ $drive['id'] }}"
                                data-edc-status="{{ $drive['status'] }}"
                                data-edc-name="{{ $drive['name'] }}"
                                data-edc-type="{{ $drive['exam_type'] }}"
                                data-edc-term="{{ $drive['term'] }}"
                                data-edc-course="{{ $drive['course'] }}"
                                data-edc-session="{{ $drive['session'] }}"
                                data-edc-start="{{ $drive['start_date'] }}"
                                data-edc-end="{{ $drive['end_date'] }}"
                                data-edc-show-url="{{ route('admin.coe.examination-drive.show', $drive['id']) }}"
                                data-edc-coord-url="{{ route('admin.coe.examination-drive.coordinates', $drive['id']) }}">
                                <td></td>
                                <td>{{ $drive['name'] }}</td>
                                <td>{{ $drive['exam_type'] === '-' ? '' : $drive['exam_type'] }}
                                    {!! $drive['exam_type'] === '-' ? $dash : '' !!}</td>
                                <td>{{ $drive['term'] }}</td>
                                <td>{{ $drive['course'] }}</td>
                                <td>{{ $drive['session'] }}</td>
                                <td>{{ $drive['faculty'] }}</td>
                                <td class="coe-col-center">
                                    @if($drive['sub_coords'] > 0)
                                        <a href="{{ route('admin.coe.examination-drive.coordinates', $drive['id']) }}"
                                           class="text-primary fw-semibold">{{ $drive['sub_coords'] }}</a>
                                    @else
                                        {!! $dash !!}
                                    @endif
                                </td>
                                <td class="coe-col-center">
                                    @if($drive['components'] > 0)
                                        <a href="{{ route('admin.coe.examination-drive.show', $drive['id']) }}"
                                           class="text-primary fw-semibold">{{ $drive['components'] }}</a>
                                    @else
                                        {!! $dash !!}
                                    @endif
                                </td>
                                <td>{{ $drive['start_date'] }}</td>
                                <td>{{ $drive['end_date'] }}</td>
                                <td class="coe-col-center">
                                    <span class="coe-state {{ $statusClass }}">{{ $drive['status'] }}</span>
                                </td>
                                <td class="edc-cell-actions"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="edcTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Create / Edit Drive modal ─────────────────────────────────────────── --}}
<div class="modal fade" id="edcFormModal" tabindex="-1" aria-labelledby="edcFormModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="edcForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="edcFormModalLabel">Create Examination Drive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    {{-- Drive Name (create only) --}}
                    <div id="edcDriveNameRow" class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="coe-form-label" for="edcDriveName">Drive Name<span class="coe-req">*</span></label>
                            <input type="text" id="edcDriveName" name="drive_name" class="form-control coe-control"
                                   placeholder="eg. Foundation Exam Drive" maxlength="150" autocomplete="off">
                        </div>
                    </div>
                    {{-- Course --}}
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="coe-form-label" for="edcCourse">Course<span class="coe-req">*</span></label>
                            <select id="edcCourse" name="course" class="form-select coe-control">
                                <option value="">Select Course</option>
                                @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    {{-- Examination Type + Term --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="edcExamType">Examination Type<span class="coe-req">*</span></label>
                            <select id="edcExamType" name="exam_type" class="form-select coe-control">
                                <option value="">Select Examination Type</option>
                                @foreach ($examTypes as $et)<option value="{{ $et }}">{{ $et }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="edcTerm">Term<span class="coe-req">*</span></label>
                            <select id="edcTerm" name="term" class="form-select coe-control">
                                <option value="">Select Term</option>
                                @foreach ($terms as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    {{-- Academic Session + Status --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="edcSession">Academic Session<span class="coe-req">*</span></label>
                            <input type="text" id="edcSession" name="academic_session" class="form-control coe-control"
                                   placeholder="eg. 2026" maxlength="10" autocomplete="off">
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="edcStatus">Status<span class="coe-req">*</span></label>
                            <select id="edcStatus" name="status" class="form-select coe-control">
                                @foreach ($statuses as $s)<option value="{{ $s }}" @if($s==='Draft') selected @endif>{{ $s }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    {{-- Start Date + End Date --}}
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="edcStartDate">Start Date<span class="coe-req">*</span></label>
                            <input type="date" id="edcStartDate" name="start_date" class="form-control coe-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="edcEndDate">End Date<span class="coe-req">*</span></label>
                            <input type="date" id="edcEndDate" name="end_date" class="form-control coe-control">
                        </div>
                    </div>

                    {{-- Subject / Faculty mapping --}}
                    <div class="mb-2 fw-semibold" style="font-size:14px">Subject and Faculty Mapping</div>
                    <table class="edc-map-table" id="edcMapTable">
                        <thead>
                            <tr>
                                <th style="width:38%">Subject</th>
                                <th style="width:42%">Faculty</th>
                                <th style="width:20%">Action</th>
                            </tr>
                        </thead>
                        <tbody id="edcMapBody">
                            {{-- Rows added by JS --}}
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="edcFormSubmit">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'edcDeleteModal', 'confirmId' => 'edcDeleteConfirm'])
@include('admin.coe.partials.freeze_modal', ['phoneMasked' => '+91 ******3274'])

{{-- Column Visibility --}}
<div class="modal fade" id="edcColumnVisibilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0"><hr class="mt-0">
                <div class="row g-3" id="edcColumnToggleGrid"></div>
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

    var SUBJECTS = @json($subjects);
    var FACULTY  = @json($faculty);
    var STATUSES = @json($statuses);

    /* COL indexes — keep in step with <thead> */
    var COL = { sno:0, name:1, type:2, term:3, course:4, session:5, faculty:6, sub:7, comp:8, start:9, end:10, status:11, action:12 };

    var $table = $('#edcTable');

    /* ── Paint state-dependent action buttons ─────────────────────────── */
    function statusClass(s) {
        return {
            'Pending at COE': 'edc-status-pending',
            'Active':         'edc-status-active',
            'Closed':         'edc-status-closed'
        }[s] || 'edc-status-draft';
    }

    function paintActions($row) {
        var status   = $row.attr('data-edc-status');
        var showUrl  = $row.attr('data-edc-show-url');
        var coordUrl = $row.attr('data-edc-coord-url');
        var canEdit  = status !== 'Closed';
        var canFreeze= status === 'Active';

        var html = '<div class="coe-act-group" role="group" aria-label="Row actions">' +
            '<a href="' + coordUrl + '" class="coe-act"><span class="coe-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span><span class="coe-act__label">View</span></a>';
        if (canEdit) {
            html += '<button type="button" class="coe-act coe-act--edit"><span class="coe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span><span class="coe-act__label">Edit</span></button>';
        }
        html += '<button type="button" class="coe-act coe-act--del"><span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span><span class="coe-act__label">Delete</span></button>';
        if (canFreeze) {
            html += '<button type="button" class="coe-act coe-act--freeze"><span class="coe-act__icon"><i class="bi bi-snow" aria-hidden="true"></i></span><span class="coe-act__label">Freeze</span></button>';
        }
        html += '</div>';
        $row.find('.edc-cell-actions').html(html);
    }

    $table.find('tbody tr').each(function () { paintActions($(this)); });

    /* ── DataTable ────────────────────────────────────────────────────── */
    var dt = CoeGrid.init({
        table: '#edcTable',
        title: @json($isArchived ? 'Examination Drive Configuration – Archived' : 'Examination Drive Configuration'),
        snoCol: COL.sno,
        order: [[COL.name, 'asc']],
        noSort: [COL.sno, COL.action],
        exclude: [COL.action],
        filters: { '#edcFilterCourse': COL.course, '#edcFilterTerm': COL.term, '#edcFilterType': COL.type },
        reset: '#edcResetFilters',
        download: '#edcDownloadBtn',
        print: '#edcPrintBtn',
        searchToggle: '#edcSearchToggle',
        searchSlot: '#edcDtSearch',
        colvisGrid: '#edcColumnToggleGrid',
        colvisKey: 'coeExaminationDrive:hiddenColumns:v1',
        empty: { title: 'No Examination Drives', zeroTitle: 'No Records Found', text: 'Create the first examination drive to get started.' }
    });

    /* ── Delete ──────────────────────────────────────────────────────── */
    CoeGrid.confirmDelete({
        table: '#edcTable',
        modal: '#edcDeleteModal',
        confirm: '#edcDeleteConfirm',
        onConfirm: function ($row) {
            dt.row($row).remove().draw(false);
            Swal.fire({ icon: 'success', title: 'Deleted', text: 'Drive deleted.' });
        }
    });

    /* ── Freeze ──────────────────────────────────────────────────────── */
    CoeGrid.freeze({
        table: '#edcTable',
        trigger: '.coe-act--freeze',
        onConfirm: function ($row) {
            $row.attr('data-edc-status', 'Pending at COE');
            $row.find('.coe-state').attr('class', 'coe-state edc-status-pending').text('Pending at COE');
            paintActions($row);
            dt.row($row).invalidate('dom').draw(false);
            Swal.fire({ icon: 'success', title: 'Frozen', text: 'Drive sent to COE for approval.' });
        }
    });

    /* ── Subject/Faculty mapping rows ────────────────────────────────── */
    function subjectOptions(selected) {
        return SUBJECTS.map(function (s) {
            return '<option value="' + s + '"' + (s === selected ? ' selected' : '') + '>' + s + '</option>';
        }).join('');
    }
    function facultyOptions(selected) {
        return FACULTY.map(function (f) {
            return '<option value="' + f + '"' + (f === selected ? ' selected' : '') + '>' + f + '</option>';
        }).join('');
    }

    function buildMapRow(subject, faculty) {
        return '<tr class="edc-map-row">' +
            '<td><select class="form-select form-select-sm edc-map-subject"><option value="">Subject</option>' + subjectOptions(subject || '') + '</select></td>' +
            '<td><select class="form-select form-select-sm edc-map-faculty"><option value="">Faculty</option>' + facultyOptions(faculty || '') + '</select></td>' +
            '<td>' +
            '<button type="button" class="edc-map-row-del me-1" aria-label="Remove row"><i class="bi bi-dash" aria-hidden="true"></i></button>' +
            '<button type="button" class="edc-map-row-add" aria-label="Add row"><i class="bi bi-plus" aria-hidden="true"></i></button>' +
            '</td></tr>';
    }

    var $mapBody = $('#edcMapBody');

    function ensureOneRow() {
        if ($mapBody.find('.edc-map-row').length === 0) {
            $mapBody.append(buildMapRow('', ''));
        }
    }

    $mapBody.on('click', '.edc-map-row-del', function () {
        $(this).closest('.edc-map-row').remove();
        ensureOneRow();
    });
    $mapBody.on('click', '.edc-map-row-add', function () {
        $(this).closest('.edc-map-row').after(buildMapRow('', ''));
    });

    /* ── Form modal (Create / Edit) ──────────────────────────────────── */
    var $modal      = $('#edcFormModal');
    var $modalBody  = $modal.find('.modal-body');
    var $editingRow = null;

    var $course  = $('#edcCourse');
    var $type    = $('#edcExamType');
    var $term    = $('#edcTerm');
    var $status  = $('#edcStatus');
    [$course, $type, $term, $status].forEach(function ($s) {
        $s.select2({ width: '100%', dropdownParent: $modalBody });
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
        $modal.find('.coe-field-error').remove();
        $modal.find('.is-invalid').removeClass('is-invalid');
    }

    function openForm($row) {
        $editingRow = $row || null;
        var isEdit  = !!$row;
        $('#edcFormModalLabel').text(isEdit ? 'Edit Examination Drive' : 'Create Examination Drive');
        $('#edcFormSubmit').text('Save');
        $('#edcDriveNameRow').toggleClass('d-none', isEdit);
        clearErrors();
        $mapBody.empty();

        if (isEdit) {
            $type.val($row.attr('data-edc-type') || '').trigger('change.select2');
            $term.val($row.attr('data-edc-term') || '').trigger('change.select2');
            $course.val($row.attr('data-edc-course') || '').trigger('change.select2');
            $('#edcSession').val($row.attr('data-edc-session') || '');
            $status.val($row.attr('data-edc-status') || 'Draft').trigger('change.select2');
            // Convert dd-mm-yyyy → yyyy-mm-dd for date input
            function toIso(d) { if (!d) return ''; var p = d.split('-'); return p.length === 3 ? p[2]+'-'+p[1]+'-'+p[0] : d; }
            $('#edcStartDate').val(toIso($row.attr('data-edc-start')));
            $('#edcEndDate').val(toIso($row.attr('data-edc-end')));
            // Pre-populate two sample map rows
            $mapBody.append(buildMapRow('Law', 'Sandra Calderon'));
            $mapBody.append(buildMapRow('Leadership', 'Adrian Harrington'));
        } else {
            $('#edcDriveName').val('');
            $course.val('').trigger('change.select2');
            $type.val('').trigger('change.select2');
            $term.val('').trigger('change.select2');
            $('#edcSession').val('');
            $status.val('Draft').trigger('change.select2');
            $('#edcStartDate, #edcEndDate').val('');
            $mapBody.append(buildMapRow('Law', 'Sandra Calderon'));
            $mapBody.append(buildMapRow('Leadership', 'Adrian Harrington'));
        }

        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    }

    $('#edcCreateBtn').on('click', function () { openForm(null); });
    $table.on('click', '.coe-act--edit', function () { openForm($(this).closest('tr')); });
    $modal.on('hidden.bs.modal', function () { $editingRow = null; clearErrors(); });

    $('#edcForm').on('submit', function (e) {
        e.preventDefault();
        var ok = true;
        var isEdit = !!$editingRow;
        if (!isEdit) {
            ok = fieldError($('#edcDriveName'), $.trim($('#edcDriveName').val()) ? '' : 'Enter the drive name.') && ok;
        }
        ok = fieldError($course, $course.val() ? '' : 'Select a course.') && ok;
        ok = fieldError($type,   $type.val()   ? '' : 'Select the examination type.') && ok;
        ok = fieldError($term,   $term.val()   ? '' : 'Select a term.') && ok;
        ok = fieldError($('#edcSession'), $.trim($('#edcSession').val()) ? '' : 'Enter the academic session.') && ok;
        ok = fieldError($('#edcStartDate'), $('#edcStartDate').val() ? '' : 'Select the start date.') && ok;
        ok = fieldError($('#edcEndDate'),   $('#edcEndDate').val()   ? '' : 'Select the end date.') && ok;
        if (!ok) { return; }

        var name    = isEdit ? $editingRow.attr('data-edc-name') : $.trim($('#edcDriveName').val());
        var course  = $course.val();
        var type    = $type.val();
        var term    = $term.val();
        var session = $.trim($('#edcSession').val());
        var status  = $status.val();
        // Format date back to dd-mm-yyyy for display
        function toDmy(v) { if (!v) return ''; var p = v.split('-'); return p[2]+'-'+p[1]+'-'+p[0]; }
        var start   = toDmy($('#edcStartDate').val());
        var end_    = toDmy($('#edcEndDate').val());
        var esc     = function (t) { return $('<div>').text(t).html(); };

        if (isEdit) {
            $editingRow.attr({
                'data-edc-type': type, 'data-edc-term': term, 'data-edc-course': course,
                'data-edc-session': session, 'data-edc-status': status,
                'data-edc-start': start, 'data-edc-end': end_
            });
            var $cells = $editingRow.find('td');
            $cells.eq(COL.type).text(type);
            $cells.eq(COL.term).text(term);
            $cells.eq(COL.course).text(course);
            $cells.eq(COL.session).text(session);
            $cells.eq(COL.status).html('<span class="coe-state ' + statusClass(status) + '">' + esc(status) + '</span>');
            $cells.eq(COL.start).text(start);
            $cells.eq(COL.end).text(end_);
            paintActions($editingRow);
            dt.row($editingRow).invalidate('dom').draw(false);
            bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
            Swal.fire({ icon: 'success', title: 'Updated', text: 'Drive updated.' });
        } else {
            var newId = 1 + Math.max(0, Math.max.apply(null,
                dt.rows().nodes().to$().map(function () { return parseInt(this.getAttribute('data-coe-id'), 10) || 0; }).get()
            ));
            // shortcut: show/coord URLs are # for new browser-only rows
            var $newRow = $(dt.row.add([
                '', esc(name), esc(type), esc(term), esc(course), esc(session),
                '-', '—', '—', esc(start), esc(end_),
                '<span class="coe-state ' + statusClass(status) + '">' + esc(status) + '</span>',
                ''
            ]).draw(false).node());
            $newRow.attr({
                'data-coe-id': newId, 'data-edc-name': name, 'data-edc-type': type,
                'data-edc-term': term, 'data-edc-course': course, 'data-edc-session': session,
                'data-edc-status': status, 'data-edc-start': start, 'data-edc-end': end_,
                'data-edc-show-url': '#', 'data-edc-coord-url': '#'
            });
            $newRow.children('td').eq(COL.action).addClass('edc-cell-actions');
            paintActions($newRow);
            bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
            Swal.fire({ icon: 'success', title: 'Created', text: 'Examination drive created.' });
        }
    });
});
</script>
@endpush
