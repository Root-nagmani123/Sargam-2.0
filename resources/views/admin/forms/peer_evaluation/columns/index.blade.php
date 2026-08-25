@extends('admin.layouts.master')

@section('title', 'Manage Evaluation Columns')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/peer-evaluation-admin.css') }}?v={{ @filemtime(public_path('css/peer-evaluation-admin.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
@endpush

@section('setup_content')
<div class="container-fluid pe-page pec-page">
    <x-breadcrum title="Manage Evaluation Columns" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="pecAddBtn" data-bs-toggle="modal" data-bs-target="#pecAddModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Columns</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pe-secondary-actions">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
            role="group" aria-label="Filter by course status">
            @foreach ([['active', 'Active'], ['archive', 'Archived']] as [$value, $label])
            <li class="nav-item" role="presentation">
                <button type="button"
                        class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $statusFilter === $value ? 'active' : '' }}"
                        data-pec-status="{{ $value }}"
                        aria-pressed="{{ $statusFilter === $value ? 'true' : 'false' }}"
                        @if($statusFilter === $value) aria-current="true" @endif>{{ $label }}</button>
            </li>
            @endforeach
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
            <div class="dropdown">
                <button type="button" id="pecDownloadToggle" class="btn pe-export-btn dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="pecDownloadToggle">
                    @foreach ([['csv', 'bi-filetype-csv', 'CSV'], ['excel', 'bi-file-earmark-excel', 'Excel (.xlsx)'], ['pdf', 'bi-file-earmark-pdf', 'PDF']] as [$fmt, $icon, $label])
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 pec-export-link"
                           data-format="{{ $fmt }}"
                           data-base="{{ route('admin.peer.columns.export', ['format' => $fmt]) }}"
                           href="{{ route('admin.peer.columns.export', ['format' => $fmt]) }}">
                            <i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            <a href="{{ route('admin.peer.columns.export', ['format' => 'print']) }}"
               class="btn pe-export-btn pec-export-link" data-format="print"
               data-base="{{ route('admin.peer.columns.export', ['format' => 'print']) }}"
               target="_blank" rel="noopener" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </a>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="pecCourseFilter" class="form-select js-pec-select2" aria-label="Filter by course">
                            <option value="">Course Name</option>
                            @foreach($courses as $id => $name)
                                <option value="{{ $id }}" @selected((string) $id === $courseFilter)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="pecTypeFilter" class="form-select js-pec-select2" aria-label="Filter by rating type">
                            <option value="">Rating Type</option>
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}" @selected($value === $typeFilter)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button"
                            class="btn programme-dt-btn-reset {{ (filled($courseFilter) || filled($typeFilter)) ? '' : 'd-none' }}"
                            id="pecRemoveFilter">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="pecBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#pecColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="pecDtSearch" class="programme-dt-search" data-dt-search-for="peerEvaluationColumnsTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="pecDtFooter"
                     class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="peerEvaluationColumnsTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- ───────────────────────── Add Columns ───────────────────────── --}}
<div class="modal fade" id="pecAddModal" tabindex="-1" aria-labelledby="pecAddLabel"
     aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content pe-modal border-0 shadow">
            {{-- novalidate: the submit handler owns validation so it can render the
                 server's field errors in the same place as its own, and so a
                 half-filled repeat card doesn't dead-end the form. --}}
            <form id="pecAddForm" action="{{ route('admin.peer.columns.store') }}" method="POST" novalidate>
                @csrf
                <div class="modal-header pe-modal-header">
                    <h5 class="modal-title" id="pecAddLabel">Add Column</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pe-modal-body">
                    <div class="pe-field mb-3">
                        <label class="pe-form-label" for="pecAddCourseId">Course Name</label>
                        <select class="form-select pe-control js-pec-select2" id="pecAddCourseId" name="course_id">
                            <option value="">Select Course</option>
                            @foreach($modalCourses as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <div class="pe-error"></div>
                    </div>

                    <div class="pe-field mb-3">
                        <label class="pe-form-label" for="pecAddEventId">Event Name</label>
                        <select class="form-select pe-control js-pec-select2" id="pecAddEventId" name="event_id">
                            <option value="">Select Event</option>
                        </select>
                        <div class="pe-error"></div>
                    </div>

                    <div class="pe-field mb-3">
                        <label class="pe-form-label" for="pecAddGroupId">Group Name<span class="pe-req">*</span></label>
                        <select class="form-select pe-control js-pec-select2" id="pecAddGroupId" name="group_id" required>
                            <option value="">Select Group</option>
                        </select>
                        <div class="pe-error"></div>
                    </div>

                    <div class="pe-field mb-3">
                        <span class="pe-form-label d-block">Evaluation Type<span class="pe-req">*</span></span>
                        <div class="d-flex flex-wrap gap-4 pec-radio-row">
                            @foreach($types as $value => $label)
                                <label class="d-inline-flex align-items-center gap-2 mb-0">
                                    <input class="form-check-input m-0" type="radio" name="evaluation_type"
                                           value="{{ $value }}" @checked($loop->first)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="pe-error"></div>
                    </div>

                    <hr class="my-3">
                    <h6 class="pec-section-title mb-3">Columns</h6>

                    {{-- Repeatable cards. Whole state is derived from the DOM after
                         every change by syncColumnCards() - never by nudging the
                         previous/next card, which is how a clone ends up inheriting
                         hidden state. --}}
                    <div id="pecColumnsContainer">
                        <div class="pec-column-card" data-index="0">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-5">
                                    <label class="pe-form-label">Column Name<span class="pe-req">*</span></label>
                                    <input type="text" class="form-control pe-control pec-name"
                                           name="columns[0][column_name]" placeholder="eg. Presentation Skills"
                                           maxlength="255" required>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="pe-form-label">Max Marks<span class="pe-req">*</span></label>
                                    <input type="number" class="form-control pe-control pec-max"
                                           name="columns[0][max_marks]" value="10.00" step="0.01" min="0.01" max="9999.99" required>
                                </div>
                                <div class="col-12 col-md-3">
                                    <span class="pe-form-label d-block">Remarks<span class="pe-req">*</span></span>
                                    <div class="d-flex gap-3 pec-radio-row">
                                        <label class="d-inline-flex align-items-center gap-2 mb-0">
                                            <input class="form-check-input m-0 pec-remarks" type="radio"
                                                   name="columns[0][has_remarks]" value="1" checked>
                                            <span>Yes</span>
                                        </label>
                                        <label class="d-inline-flex align-items-center gap-2 mb-0">
                                            <input class="form-check-input m-0 pec-remarks" type="radio"
                                                   name="columns[0][has_remarks]" value="0">
                                            <span>No</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-1">
                                    <div class="pec-card-actions">
                                        <button type="button" class="pec-card-btn pec-card-btn--remove" title="Remove this column"
                                                aria-label="Remove this column">
                                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="pec-card-btn pec-card-btn--add" title="Add another column"
                                                aria-label="Add another column">
                                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="pe-error pec-card-error"></div>
                        </div>
                    </div>
                    <div class="pe-error" id="pecColumnsError"></div>
                </div>
                <div class="modal-footer pe-modal-footer">
                    <button type="button" class="btn pe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn pe-btn-submit">Add Column</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ───────────────────────── Edit Column ───────────────────────── --}}
<div class="modal fade" id="pecEditModal" tabindex="-1" aria-labelledby="pecEditLabel"
     aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pe-modal border-0 shadow">
            <form id="pecEditForm" action="" method="POST" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-header pe-modal-header">
                    <h5 class="modal-title" id="pecEditLabel">Edit Column</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pe-modal-body">
                    <div class="pe-field mb-3">
                        <label class="pe-form-label" for="pecEditName">Column Name<span class="pe-req">*</span></label>
                        <input type="text" class="form-control pe-control" id="pecEditName" name="column_name"
                               maxlength="255" required>
                        <div class="pe-error"></div>
                    </div>

                    <div class="pe-field mb-3">
                        <label class="pe-form-label" for="pecEditMax">Max Marks<span class="pe-req">*</span></label>
                        <input type="number" class="form-control pe-control" id="pecEditMax" name="max_marks"
                               step="0.01" min="0.01" max="9999.99" required>
                        <div class="pe-error"></div>
                    </div>

                    <div class="pe-field mb-3">
                        <span class="pe-form-label d-block">Evaluation Type<span class="pe-req">*</span></span>
                        <div class="d-flex flex-wrap gap-4 pec-radio-row">
                            @foreach($types as $value => $label)
                                <label class="d-inline-flex align-items-center gap-2 mb-0">
                                    <input class="form-check-input m-0" type="radio" name="evaluation_type" value="{{ $value }}">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="pe-error"></div>
                    </div>

                    <div class="pe-field mb-3">
                        <span class="pe-form-label d-block">Remarks<span class="pe-req">*</span></span>
                        <div class="d-flex gap-3 pec-radio-row">
                            <label class="d-inline-flex align-items-center gap-2 mb-0">
                                <input class="form-check-input m-0" type="radio" name="has_remarks" value="1">
                                <span>Yes</span>
                            </label>
                            <label class="d-inline-flex align-items-center gap-2 mb-0">
                                <input class="form-check-input m-0" type="radio" name="has_remarks" value="0">
                                <span>No</span>
                            </label>
                        </div>
                        <div class="pe-error"></div>
                    </div>

                    {{-- Buffer marks belong to the GROUP, so this saves separately -
                         see the submit handler. Only meaningful for Distribute
                         Marks, hence hidden for the other type. --}}
                    <div class="pe-field mb-2 d-none" id="pecEditBufferWrap">
                        <label class="pe-form-label" for="pecEditBuffer">
                            Buffer Marks for OTs<span class="pe-req">*</span>
                        </label>
                        <input type="number" class="form-control pe-control" id="pecEditBuffer" name="buffer_marks"
                               step="0.01" min="0" max="999999.99">
                        <small class="text-body-secondary d-block mt-1">
                            Applies to the whole group, so every Distribute Marks column shares it.
                        </small>
                        <div class="pe-error"></div>
                    </div>
                </div>
                <div class="modal-footer pe-modal-footer">
                    <button type="button" class="btn pe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn pe-btn-submit">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─────────────────────── Delete confirm ──────────────────────── --}}
<div class="modal fade" id="pecDeleteModal" tabindex="-1" aria-labelledby="pecDeleteTitle"
     aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pe-confirm border-0 shadow-lg">
            <div class="modal-body text-center px-4 px-md-5 py-5">
                <div class="pe-confirm-icon mb-4" role="img" aria-hidden="true">
                    <i class="bi bi-exclamation-lg"></i>
                </div>
                <h2 class="pe-confirm-title mb-3" id="pecDeleteTitle">Confirm Delete?</h2>
                <p class="pe-confirm-message mb-4" id="pecDeleteMessage"></p>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <button type="button" class="btn pe-confirm-btn pe-confirm-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn pe-confirm-btn pe-confirm-ok" id="pecDeleteConfirm">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ────────────────────── Column visibility ──────────────────── --}}
<div class="modal fade" id="pecColumnVisibilityModal" tabindex="-1"
     aria-labelledby="pecColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="pecColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="peerEvaluationColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
(function ($) {
    'use strict';

    var TABLE_ID = 'peerEvaluationColumnsTable';
    var COLVIS_KEY = 'peerEvaluationColumnsGrid:hiddenColumns:v1';
    var OPTIONS_URL = @json(route('admin.peer.columns.options'));
    var GROUPS_URL = @json(route('admin.peer.columns.groups', ['event' => '__ID__']));
    var COLUMNS_URL = @json(route('admin.peer.columns.columns', ['group' => '__ID__']));
    var UPDATE_URL = @json(route('admin.peer.columns.update', ['id' => '__ID__']));
    var DESTROY_URL = @json(route('admin.peer.columns.destroy', ['id' => '__ID__']));
    var BUFFER_URL = @json(route('admin.peer.columns.buffer', ['group' => '__ID__']));
    var CSRF = @json(csrf_token());
    var EXPORT_COLUMN_KEYS = ['sno', 'course_name', '', 'event_name', ''];

    var dt = null;
    var currentStatus = @json($statusFilter);

    /* ── Column visibility ─────────────────────────────────────────── */

    function readHiddenCols() {
        try {
            var raw = window.localStorage.getItem(COLVIS_KEY);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return []; // private mode / storage disabled / corrupt value
        }
    }

    function saveHiddenCols(arr) {
        try { window.localStorage.setItem(COLVIS_KEY, JSON.stringify(arr)); } catch (e) { /* not persisted */ }
    }

    function setupColumns(table) {
        var hidden = readHiddenCols();

        table.columns().every(function () {
            this.visible(hidden.indexOf(this.index()) === -1, false);
        });
        table.columns.adjust();

        var $grid = $('#peerEvaluationColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();

        table.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'peccolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = readHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) { h.splice(pos, 1); }
                } else if (pos === -1) {
                    h.push(idx);
                }
                saveHiddenCols(h);
                table.column(idx).visible(this.checked, false);
                table.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    /* ── Export links follow the filters ───────────────────────────── */

    function syncExportLinks() {
        var search = dt ? (dt.search() || '') : '';

        $('.pec-export-link').each(function () {
            var url = new URL($(this).data('base'), window.location.origin);
            url.searchParams.set('status_filter', currentStatus);

            var course = $('#pecCourseFilter').val();
            var type = $('#pecTypeFilter').val();
            if (course) { url.searchParams.set('course_filter', course); }
            if (type) { url.searchParams.set('type_filter', type); }
            if (search) { url.searchParams.set('q', search); }

            this.href = url.toString();
        });
    }

    /* ── Select2 ───────────────────────────────────────────────────── */

    function initSelect2($scope, $parent) {
        if (!$.fn.select2) { return; }
        $scope.find('select.js-pec-select2').each(function () {
            var $sel = $(this);
            if ($sel.data('select2')) { return; }
            var opts = {
                width: '100%',
                placeholder: $sel.find('option:first').text() || 'Select',
                allowClear: !$sel.prop('required')
            };
            if ($parent && $parent.length) { opts.dropdownParent = $parent; }
            $sel.select2(opts);
        });
    }

    function fill($sel, list, keep) {
        $sel.find('option:not(:first)').remove();
        $.each(list, function (i, item) {
            $sel.append($('<option>', { value: item.id, text: item.name }));
        });
        $sel.val(keep && $sel.find('option[value="' + keep + '"]').length ? keep : '');
        // Select2 re-reads <option>s live but only re-renders on this event.
        $sel.trigger('change.select2');
    }

    function loadOptions(opts) {
        return $.getJSON(OPTIONS_URL, {
            course_id: opts.courseId || '',
            event_id: opts.eventId || ''
        }).done(function (res) {
            if (opts.$event && opts.$event.length) { fill(opts.$event, (res && res.events) || [], opts.keepEvent); }
            if (opts.$group && opts.$group.length) { fill(opts.$group, (res && res.groups) || [], opts.keepGroup); }
        });
    }

    /* ── Nested levels ─────────────────────────────────────────────── */

    // Level 1 -> 2. DataTables owns the child row, so ask it rather than injecting
    // a <tr> by hand, or sorting/paging would strand the injected markup.
    function toggleEvent($btn) {
        var eventId = $btn.data('event-id');
        var row = dt.row($btn.closest('tr'));

        if (row.child.isShown()) {
            row.child.hide();
            setToggleState($btn, false);
            return;
        }

        $btn.prop('disabled', true);
        $.getJSON(GROUPS_URL.replace('__ID__', eventId))
            .done(function (res) {
                row.child($('<div class="pec-child-wrap"></div>').html(res.html)).show();
                setToggleState($btn, true);
            })
            .fail(function () { notifyError('Could not load the groups for this event.'); })
            .always(function () { $btn.prop('disabled', false); });
    }

    // Level 2 -> 3. These rows are plain markup inside the child row, so the
    // placeholder <tr> rendered by _groups.blade.php is filled in directly.
    function toggleGroup($btn) {
        var groupId = $btn.data('group-id');
        var $child = $('[data-group-child="' + groupId + '"]');

        if (!$child.hasClass('d-none')) {
            $child.addClass('d-none').find('td').empty();
            setToggleState($btn, false);
            return;
        }

        $btn.prop('disabled', true);
        $.getJSON(COLUMNS_URL.replace('__ID__', groupId))
            .done(function (res) {
                $child.removeClass('d-none').find('td').html(res.html);
                setToggleState($btn, true);
            })
            .fail(function () { notifyError('Could not load the columns for this group.'); })
            .always(function () { $btn.prop('disabled', false); });
    }

    // One helper for both levels: the chevron flips and the caption becomes Close.
    function setToggleState($btn, open) {
        $btn.attr('aria-expanded', open ? 'true' : 'false');
        $btn.find('.pe-act__icon i')
            .toggleClass('bi-chevron-down', !open)
            .toggleClass('bi-chevron-up', open);
        $btn.find('.pe-act__label').text(open ? 'Close' : 'View');
    }

    /* ── Repeatable column cards ───────────────────────────────────── */

    // Whole state derived from the DOM after every change - never by nudging the
    // neighbouring card, which is how a clone inherits stale state.
    function syncColumnCards() {
        var $cards = $('#pecColumnsContainer .pec-column-card');
        var last = $cards.length - 1;

        $cards.each(function (index) {
            var $card = $(this);
            $card.attr('data-index', index);
            $card.find('.pec-name').attr('name', 'columns[' + index + '][column_name]');
            $card.find('.pec-max').attr('name', 'columns[' + index + '][max_marks]');
            $card.find('.pec-remarks').attr('name', 'columns[' + index + '][has_remarks]');
            // Plain show/hide, not .d-none: Bootstrap's display utilities are
            // !important, so a class toggle here would fight jQuery.
            $card.find('.pec-card-btn--remove').toggle($cards.length > 1);
            $card.find('.pec-card-btn--add').toggle(index === last);
        });
    }

    /* ── Shared helpers ────────────────────────────────────────────── */

    function clearErrors($form) {
        $form.find('.pe-error').removeClass('is-shown').text('');
        $form.find('.pe-control').removeClass('is-invalid');
    }

    function showErrors($form, errors) {
        $.each(errors, function (field, messages) {
            var msg = $.isArray(messages) ? messages[0] : messages;

            // columns.0.column_name -> that card's own error slot.
            var m = /^columns\.(\d+)\./.exec(field);
            if (m) {
                var $card = $('#pecColumnsContainer .pec-column-card').eq(parseInt(m[1], 10));
                $card.find('.pec-card-error').addClass('is-shown').text(msg);
                return;
            }
            if (field === 'columns') {
                $('#pecColumnsError').addClass('is-shown').text(msg);
                return;
            }

            var $field = $form.find('[name="' + field + '"]').first();
            $field.addClass('is-invalid');
            $field.closest('.pe-field').find('.pe-error').first().addClass('is-shown').text(msg);
        });
    }

    function toast(message) {
        // icon:'success' renders as the global top-right toast card.
        if (window.Swal) { window.Swal.fire({ icon: 'success', title: message }); }
    }

    function notifyError(message) {
        if (window.Swal) { window.Swal.fire({ icon: 'error', title: 'Error', text: message }); }
        else { window.alert(message); }
    }

    // Re-fetch whichever level-3 pane is open, so a change is visible without
    // collapsing and reopening the tree.
    function refreshOpenGroup(groupId) {
        var $btn = $('.pec-toggle-group[data-group-id="' + groupId + '"]');
        if (!$btn.length || $btn.attr('aria-expanded') !== 'true') { return; }

        $.getJSON(COLUMNS_URL.replace('__ID__', groupId)).done(function (res) {
            $('[data-group-child="' + groupId + '"]').find('td').html(res.html);
        });
    }

    /* ── Wiring ────────────────────────────────────────────────────── */

    $(function () {
        initSelect2($('.programme-dt-toolbar'), null);

        var attempts = 0;
        (function waitForTable() {
            if ($.fn.DataTable.isDataTable('#' + TABLE_ID)) {
                dt = $('#' + TABLE_ID).DataTable();
                setupColumns(dt);
                syncExportLinks();
                $('#' + TABLE_ID).on('search.dt', syncExportLinks);
                return;
            }
            if (++attempts < 60) { window.setTimeout(waitForTable, 100); }
        })();

        $('#' + TABLE_ID).on('preXhr.dt', function (e, settings, data) {
            data.status_filter = currentStatus;
            var course = $('#pecCourseFilter').val();
            var type = $('#pecTypeFilter').val();
            if (course) { data.course_filter = course; }
            if (type) { data.type_filter = type; }
        });

        function filtersChanged() {
            $('#pecRemoveFilter').toggleClass('d-none',
                !($('#pecCourseFilter').val() || $('#pecTypeFilter').val()));
            if (dt) { dt.ajax.reload(); }
            syncExportLinks();
        }

        $('.programme-status-pill[data-pec-status]').on('click', function () {
            var status = $(this).data('pec-status');
            if (status === currentStatus) { return; }

            currentStatus = status;
            $('.programme-status-pill[data-pec-status]')
                .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
            $(this).addClass('active').attr('aria-pressed', 'true').attr('aria-current', 'true');

            // Scope changed, so the course list below it is stale.
            $.getJSON(OPTIONS_URL, { status: currentStatus }).done(function (res) {
                if (res && res.courses) { fill($('#pecCourseFilter'), res.courses, ''); }
            }).always(filtersChanged);
        });

        $('#pecCourseFilter, #pecTypeFilter').on('change', filtersChanged);

        $('#pecRemoveFilter').on('click', function () {
            $('#pecCourseFilter, #pecTypeFilter').val('').trigger('change.select2');
            filtersChanged();
        });

        /* Nested expansion - delegated, because both levels are injected. */
        $('#' + TABLE_ID + ' tbody').on('click', '.pec-toggle, .pec-expand', function () {
            var $btn = $(this).hasClass('pec-toggle')
                ? $(this)
                : $('.pec-toggle[data-event-id="' + $(this).data('event-id') + '"]');
            toggleEvent($btn);
        });

        $(document).on('click', '.pec-toggle-group, .pec-expand-group', function () {
            var $btn = $(this).hasClass('pec-toggle-group')
                ? $(this)
                : $('.pec-toggle-group[data-group-id="' + $(this).data('group-id') + '"]');
            toggleGroup($btn);
        });

        /* Rate Peers / Distribute Marks tabs inside a group. */
        $(document).on('click', '.pec-type-tabs .programme-status-pill', function () {
            var $pill = $(this);
            var type = $pill.data('pec-type');
            var groupId = $pill.data('pec-group');

            $pill.closest('.pec-type-tabs').find('.programme-status-pill')
                .removeClass('active').attr('aria-pressed', 'false');
            $pill.addClass('active').attr('aria-pressed', 'true');

            $('[data-pec-pane][data-pec-group="' + groupId + '"]').each(function () {
                $(this).toggleClass('d-none', $(this).data('pec-pane') !== type);
            });
        });

        /* Add modal */
        $('#pecAddModal').on('show.bs.modal', function () {
            var $form = $('#pecAddForm');
            $form[0].reset();
            clearErrors($form);
            $('#pecColumnsContainer .pec-column-card').not(':first').remove();
            syncColumnCards();

            var course = $('#pecCourseFilter').val();
            $form.find('[name="course_id"]').val(course || '');
            loadOptions({
                courseId: course,
                $event: $form.find('[name="event_id"]'),
                $group: $form.find('[name="group_id"]')
            });
        });

        $('#pecAddModal, #pecEditModal').on('shown.bs.modal', function () {
            initSelect2($(this), $(this));
            $(this).find('select.js-pec-select2').trigger('change.select2');
        });

        $('#pecAddForm').on('change', 'select[name="course_id"]', function () {
            var $form = $(this).closest('form');
            loadOptions({
                courseId: $(this).val(),
                $event: $form.find('[name="event_id"]'),
                $group: $form.find('[name="group_id"]')
            });
        });

        $('#pecAddForm').on('change', 'select[name="event_id"]', function () {
            var $form = $(this).closest('form');
            loadOptions({
                courseId: $form.find('[name="course_id"]').val(),
                eventId: $(this).val(),
                $group: $form.find('[name="group_id"]')
            });
        });

        $('#pecColumnsContainer').on('click', '.pec-card-btn--add', function () {
            var $clone = $('#pecColumnsContainer .pec-column-card').first().clone();
            $clone.find('input[type="text"], input[type="number"]').val('');
            $clone.find('.pec-max').val('10.00');
            $clone.find('.pec-card-error').removeClass('is-shown').text('');
            // Clone of the FIRST card, so any hidden state it carried is reset by
            // syncColumnCards() rather than inherited.
            $clone.find('.pec-card-btn').show();

            // Rename the radios while the clone is still DETACHED. A clone carries
            // card 0's `name`, so the instant it is appended the browser merges the
            // two into one radio group and keeps only the last checked one -
            // silently clearing card 0's selection. Renaming after the fact is too
            // late: the state is already gone.
            var nextIndex = $('#pecColumnsContainer .pec-column-card').length;
            $clone.find('.pec-remarks').attr('name', 'columns[' + nextIndex + '][has_remarks]')
                .prop('checked', false).filter('[value="1"]').prop('checked', true);

            $('#pecColumnsContainer').append($clone);
            syncColumnCards();
        });

        $('#pecColumnsContainer').on('click', '.pec-card-btn--remove', function () {
            if ($('#pecColumnsContainer .pec-column-card').length <= 1) { return; }
            $(this).closest('.pec-column-card').remove();
            syncColumnCards();
        });

        $('#pecAddForm').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');

            clearErrors($form);
            $submit.prop('disabled', true);

            $.ajax({ url: $form.attr('action'), type: 'POST', data: $form.serialize(), dataType: 'json' })
                .done(function (res) {
                    var inst = bootstrap.Modal.getInstance(document.getElementById('pecAddModal'));
                    if (inst) { inst.hide(); }
                    var groupId = $form.find('[name="group_id"]').val();
                    if (dt) { dt.ajax.reload(null, false); }
                    refreshOpenGroup(groupId);
                    toast((res && res.message) || 'Columns added successfully.');
                })
                .fail(function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        showErrors($form, xhr.responseJSON.errors);
                        return;
                    }
                    notifyError((xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.');
                })
                .always(function () { $submit.prop('disabled', false); });
        });

        /* Edit */
        $(document).on('click', '.pec-edit-btn', function () {
            var $btn = $(this);
            var $form = $('#pecEditForm');

            clearErrors($form);
            $form.attr('action', UPDATE_URL.replace('__ID__', $btn.data('id')));
            $form.data('group-id', $btn.data('group-id'));
            $form.find('[name="column_name"]').val($btn.data('column-name'));
            $form.find('[name="max_marks"]').val($btn.data('max-marks'));
            $form.find('[name="has_remarks"][value="' + ($btn.data('has-remarks') ? 1 : 0) + '"]').prop('checked', true);
            $form.find('[name="evaluation_type"][value="' + $btn.data('evaluation-type') + '"]').prop('checked', true);
            $form.find('[name="buffer_marks"]').val($btn.data('buffer-marks'));
            toggleBufferField($form);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('pecEditModal')).show();
        });

        // Buffer marks only mean anything for Distribute Marks.
        function toggleBufferField($form) {
            var type = $form.find('[name="evaluation_type"]:checked').val();
            $('#pecEditBufferWrap').toggleClass('d-none', type !== 'distribute_marks');
        }

        $('#pecEditForm').on('change', '[name="evaluation_type"]', function () {
            toggleBufferField($('#pecEditForm'));
        });

        $('#pecEditForm').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var groupId = $form.data('group-id');
            var buffer = $form.find('[name="buffer_marks"]').val();
            var isDistribute = $form.find('[name="evaluation_type"]:checked').val() === 'distribute_marks';

            clearErrors($form);
            $submit.prop('disabled', true);

            $.ajax({ url: $form.attr('action'), type: 'POST', data: $form.serialize(), dataType: 'json' })
                .done(function (res) {
                    // Buffer marks live on the group, so they save separately. Only
                    // posted when the type actually uses them.
                    var done = function () {
                        var inst = bootstrap.Modal.getInstance(document.getElementById('pecEditModal'));
                        if (inst) { inst.hide(); }
                        refreshOpenGroup(groupId);
                        toast((res && res.message) || 'Column updated successfully.');
                    };

                    if (isDistribute && groupId && buffer !== '') {
                        $.ajax({
                            url: BUFFER_URL.replace('__ID__', groupId),
                            type: 'POST',
                            data: { _method: 'PUT', _token: CSRF, buffer_marks: buffer },
                            dataType: 'json'
                        }).always(done);
                    } else {
                        done();
                    }
                })
                .fail(function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        showErrors($form, xhr.responseJSON.errors);
                        return;
                    }
                    notifyError((xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.');
                })
                .always(function () { $submit.prop('disabled', false); });
        });

        /* Delete */
        var pendingDeleteId = null;
        var pendingDeleteGroup = null;

        $(document).on('click', '.pec-delete-btn', function () {
            var $btn = $(this);
            pendingDeleteId = $btn.data('id');
            pendingDeleteGroup = $btn.closest('.pec-level--columns').find('[data-pec-group]').first().data('pec-group');
            $('#pecDeleteMessage').text(
                'Are you sure you want to delete ' + ($btn.data('column-name') || 'this column') +
                '? This action can\'t be undone.'
            );
            bootstrap.Modal.getOrCreateInstance(document.getElementById('pecDeleteModal')).show();
        });

        $('#pecDeleteConfirm').on('click', function () {
            if (!pendingDeleteId) { return; }
            var $ok = $(this);
            $ok.prop('disabled', true);

            $.ajax({
                url: DESTROY_URL.replace('__ID__', pendingDeleteId),
                type: 'POST',
                data: { _method: 'DELETE', _token: CSRF },
                dataType: 'json'
            })
                .done(function (res) {
                    var inst = bootstrap.Modal.getInstance(document.getElementById('pecDeleteModal'));
                    if (inst) { inst.hide(); }
                    refreshOpenGroup(pendingDeleteGroup);
                    toast((res && res.message) || 'Column deleted successfully.');
                })
                .fail(function (xhr) {
                    notifyError((xhr.responseJSON && xhr.responseJSON.message) || 'Could not delete the column.');
                })
                .always(function () { $ok.prop('disabled', false); pendingDeleteId = null; });
        });

        /* The status switch is the global handler's (admin_assets/js/custom.js).
           We write no toggle JS - only the redraw of the open pane. */
        $(document).ajaxSuccess(function (event, xhr, settings) {
            var url = (settings && settings.url) ? settings.url : '';
            if (url.indexOf('toggle-status') === -1 && url.indexOf('toggleStatus') === -1) { return; }
            setTimeout(function () {
                $('.pec-toggle-group[aria-expanded="true"]').each(function () {
                    refreshOpenGroup($(this).data('group-id'));
                });
            }, 600);
        });

        syncColumnCards();
    });
})(jQuery);
</script>
@endpush
