@extends('admin.layouts.master')

@section('title', 'Unique Participant Code')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.coe-btn-danger { background:#dc3545;color:#fff;border:none; }
.coe-btn-danger:hover { background:#b02a37;color:#fff; }
.coe-btn-freeze { background:#198754;color:#fff;border:none; }
.coe-btn-freeze:hover { background:#146c43;color:#fff; }
.coe-state--pending { background:#fff3cd;color:#856404; }
/* A course's code count opens that OT's codes for the course. */
.upc-count-link { color: var(--coe-brand, #004a93); text-decoration: underline; font-weight: 600; }
.upc-count-link:hover { color: var(--coe-brand-hover, #003b75); }
</style>
@endpush

@section('setup_content')
@php
    $isArchived = $scope === 'archived';
    $menu = request('menu');
    $otUrl = fn (int $student, ?int $course = null) => route('admin.coe.unique-participant-code.ot', array_filter([
        'student' => $student, 'course' => $course, 'scope' => $isArchived ? 'archived' : null, 'menu' => $menu,
    ]));
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Unique Participant Code" :showBack="false"
                 :items="['Setup', 'COE', 'Master Data', 'Unique Participant Code']">
        <button type="button" class="btn coe-btn-header" id="upcGenerateBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Generate Unique Code</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs · bulk buttons · Download / Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Course scope">
            <a href="{{ route('admin.coe.unique-participant-code.index', array_filter(['menu' => $menu])) }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}"
               @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.unique-participant-code.index', array_filter(['scope' => 'archived', 'menu' => $menu])) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}"
               @if ($isArchived) aria-current="page" @endif>Archived</a>
        </nav>

        <div class="d-flex flex-wrap gap-2 align-items-center coe-secondary-actions">
            {{-- Act on every code of the ticked OTs; frozen codes are kept (no unfreeze, no delete). --}}
            <button type="button" id="upcDeleteAllBtn" class="btn coe-btn coe-btn-danger">Delete Selected</button>
            <button type="button" id="upcFreezeAllBtn" class="btn coe-btn coe-btn-freeze">Freeze Selected</button>
            @include('admin.coe.partials.print_brand')
            <button type="button" id="upcDownloadBtn" class="btn programme-dt-btn-columns" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="upcPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    {{-- An OT matches a course when they hold codes on it. --}}
                    <div class="programme-dt-filter-select">
                        <select id="upcFilterCourse" class="form-select" data-placeholder="Course Name" aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($otCourses as $course)
                                <option value="{{ $course['key'] }}">{{ $course['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select coe-filter-narrow">
                        <select id="upcFilterStatus" class="form-select" data-placeholder="Status" aria-label="Status">
                            <option value="">Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Frozen">Frozen</option>
                        </select>
                    </div>

                    <button type="button" id="upcResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#upcColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="upcDtSearch" class="programme-dt-search d-none" data-dt-search-for="upcTable"></div>
                    <button type="button" id="upcSearchToggle" class="coe-search-toggle"
                            aria-controls="upcDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="upcTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="coe-col-check">
                                    <input type="checkbox" class="form-check-input m-0" id="upcCheckAll"
                                           aria-label="Select all OTs">
                                </th>
                                <th scope="col">S. No.</th>
                                <th scope="col">OT Name</th>
                                <th scope="col">User Name</th>
                                @foreach ($otCourses as $course)
                                    <th scope="col" class="coe-col-center">{{ $course['label'] }}</th>
                                @endforeach
                                <th scope="col" class="coe-col-center">STATUS</th>
                                <th scope="col">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($otRows as $ot)
                                <tr data-upc-courses="|{{ implode('|', array_keys($ot['courses'])) }}|"
                                    data-upc-ids="{{ implode(',', $ot['ids']) }}">
                                    <td class="coe-col-check">
                                        <input type="checkbox" class="form-check-input m-0 upc-row-check"
                                               aria-label="Select {{ $ot['ot_name'] }}">
                                    </td>
                                    <td></td>
                                    <td class="coe-strong">{{ $ot['ot_name'] }}</td>
                                    <td>{{ $ot['user_name'] }}</td>
                                    @foreach ($otCourses as $course)
                                        @php $held = count($ot['courses'][$course['key']] ?? []); @endphp
                                        <td class="coe-col-center" data-order="{{ $held }}">
                                            @if ($held)
                                                <a href="{{ $otUrl($ot['student_pk'], (int) $course['key']) }}" class="upc-count-link"
                                                   title="View the codes for {{ $course['label'] }}">{{ str_pad((string) $held, 2, '0', STR_PAD_LEFT) }}</a>
                                            @else
                                                <span class="text-body-secondary">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="coe-col-center" data-search="{{ $ot['status'] }}">
                                        <span class="coe-state coe-state--{{ $ot['status'] === 'Frozen' ? 'active' : 'pending' }}">{{ $ot['status'] }}</span>
                                    </td>
                                    <td>
                                        <div class="coe-act-group" role="group" aria-label="Row actions">
                                            <a href="{{ $otUrl($ot['student_pk']) }}" class="coe-act text-decoration-none">
                                                <span class="coe-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">View</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="upcTable"></div>
            </div>

        </div>
    </div>
</div>

@include('admin.coe.unique_participant_code._generate')

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'upcDeleteModal', 'confirmId' => 'upcDeleteConfirm'])

{{-- Column Visibility --}}
<div class="modal fade" id="upcColumnVisibilityModal" tabindex="-1" aria-labelledby="upcColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="upcColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="upcColumnToggleGrid"></div>
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

    var $table = $('#upcTable');
    var lastCol = $('#upcTable thead th').length - 1;
    var statusCol = lastCol - 1;

    var dt = CoeGrid.init({
        table: '#upcTable',
        title: @json($isArchived ? 'Unique Participant Code - Archived' : 'Unique Participant Code'),
        snoCol: 1,
        order: [[2, 'asc']],
        noSort: [0, 1, lastCol],
        exclude: [0, lastCol],
        rowCheck: '.upc-row-check',
        filters: { '#upcFilterStatus': statusCol },
        reset: '#upcResetFilters',
        download: '#upcDownloadBtn',
        print: '#upcPrintBtn',
        searchToggle: '#upcSearchToggle',
        searchSlot: '#upcDtSearch',
        colvisGrid: '#upcColumnToggleGrid',
        colvisKey: 'coeUniqueParticipantCode:otColumns:v1',
        empty: { title: 'No Participant Codes', zeroTitle: 'No Records Found',
                 text: 'Generate unique codes for a course and subject to get started.' }
    });

    /* Course Name: an OT matches when they hold codes on the course picked. */
    var $course = $('#upcFilterCourse');
    if (window.DropdownSearch) {
        window.DropdownSearch.init($course[0], { placeholder: $course.data('placeholder'), allowClear: false });
    }
    $.fn.dataTable.ext.search.push(function (settings, data, index) {
        if (settings.nTable.id !== 'upcTable' || !$course.val()) { return true; }
        var node = dt.row(index).node();
        return !!node && (node.getAttribute('data-upc-courses') || '').indexOf('|' + $course.val() + '|') !== -1;
    });
    $course.on('change', function () { dt.draw(); });
    $('#upcResetFilters').on('click', function () { $course.val('').trigger('change.select2'); dt.draw(); });

    /* ── Row selection: the ticked OTs among the rows the filters leave ───── */
    function selectedRows() {
        return dt.rows({ search: 'applied' }).nodes().to$().filter(function () {
            return $(this).find('.upc-row-check').prop('checked');
        });
    }
    function selectedCodeIds() {
        var ids = [];
        selectedRows().each(function () {
            (this.getAttribute('data-upc-ids') || '').split(',').forEach(function (id) { if (id) { ids.push(parseInt(id, 10)); } });
        });
        return ids;
    }
    function syncCheckAll() {
        var n = selectedRows().length;
        var all = dt.rows({ search: 'applied' }).nodes().to$().length;
        $('#upcCheckAll').prop('checked', n > 0 && n === all).prop('indeterminate', n > 0 && n < all);
    }
    $('#upcCheckAll').on('change', function () {
        dt.rows({ search: 'applied' }).nodes().to$().find('.upc-row-check').prop('checked', this.checked);
        syncCheckAll();
    });
    $table.on('change', '.upc-row-check', syncCheckAll);
    dt.on('search.dt', function () { setTimeout(syncCheckAll, 0); });

    function send(url, data) {
        return $.ajax({
            url: url, type: 'POST', data: data, dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '', 'Accept': 'application/json' }
        });
    }
    function showError(xhr) {
        var j = xhr.responseJSON || {};
        Swal.fire({ icon: 'error', title: 'Could not complete',
            text: xhr.status === 419 ? 'Your session has expired. Reload the page and try again.' : (j.message || 'The request failed. Please try again.') });
    }
    // Counts and statuses change: reload onto the same tab.
    function doneThenReload(title) {
        return function (res) { Swal.fire({ icon: 'success', title: title, text: res.message }).then(function () { window.location.reload(); }); };
    }

    // The bulk buttons are always shown; with nothing ticked they say so instead of acting.
    // Bound before CoeGrid.confirmDelete so this runs first and can stop its handler.
    function requireSelection(e) {
        if (selectedRows().length) { return true; }
        e.stopImmediatePropagation();
        Swal.fire({ icon: 'info', title: 'Nothing selected', text: 'Select at least one OT first.' });
        return false;
    }
    $('#upcDeleteAllBtn').on('click', requireSelection);
    $('#upcFreezeAllBtn').on('click', function (e) {
        if (!requireSelection(e)) { return; }
        send(@json(route('admin.coe.unique-participant-code.freeze')), { ids: selectedCodeIds() })
            .done(doneThenReload('Frozen')).fail(showError);
    });

    CoeGrid.confirmDelete({
        table: '#upcTable',
        modal: '#upcDeleteModal',
        confirm: '#upcDeleteConfirm',
        bulkTrigger: '#upcDeleteAllBtn',
        onConfirm: function () {
            return send(@json(route('admin.coe.unique-participant-code.bulk-destroy')), { ids: selectedCodeIds() })
                .done(doneThenReload('Deleted')).fail(showError);
        }
    });
});
</script>
@endpush
