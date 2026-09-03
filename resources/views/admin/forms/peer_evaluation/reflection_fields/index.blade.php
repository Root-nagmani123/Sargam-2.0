@extends('admin.layouts.master')

@section('title', 'Manage Reflection Fields')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/peer-evaluation-admin.css') }}?v={{ @filemtime(public_path('css/peer-evaluation-admin.css')) ?: time() }}">
{{-- Select2 on every dropdown on this page - the filter row and both modals.
     course_master has 145 rows, and the Event/Group lists grow with the data, so a
     plain <select> stops being scannable fast. Only the CSS is per-page: the
     library's JS is global (admin/layouts/footer.blade.php). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/select2-theme.css') }}?v={{ @filemtime(public_path('css/select2-theme.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid pe-page">
    <x-breadcrum title="Manage Reflection Fields" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="prfAddBtn" data-bs-toggle="modal" data-bs-target="#prfAddModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Field</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Two different "status" ideas live on this page, deliberately:
           - these PILLS scope by the COURSE's status, exactly like Course Master
           - the Status COLUMN + switch is the reflection field's own on/off flag
         A field with no course is global and shows under BOTH pills. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pe-secondary-actions">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
            role="group" aria-label="Filter by course status">
            <li class="nav-item" role="presentation">
                <button type="button"
                        class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $statusFilter === 'active' ? 'active' : '' }}"
                        data-prf-status="active"
                        aria-pressed="{{ $statusFilter === 'active' ? 'true' : 'false' }}"
                        @if($statusFilter === 'active') aria-current="true" @endif>Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button"
                        class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $statusFilter === 'archive' ? 'active' : '' }}"
                        data-prf-status="archive"
                        aria-pressed="{{ $statusFilter === 'archive' ? 'true' : 'false' }}"
                        @if($statusFilter === 'archive') aria-current="true" @endif>Archived</button>
            </li>
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
        <div class="dropdown">
            <button type="button" id="prfDownloadToggle" class="btn pe-export-btn dropdown-toggle border-0"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="prfDownloadToggle">
                @foreach ([['csv', 'bi-filetype-csv', 'CSV'], ['excel', 'bi-file-earmark-excel', 'Excel (.xlsx)'], ['pdf', 'bi-file-earmark-pdf', 'PDF']] as [$fmt, $icon, $label])
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 prf-export-link"
                       data-format="{{ $fmt }}"
                       data-base="{{ route('admin.peer.reflection-fields.export', ['format' => $fmt]) }}"
                       href="{{ route('admin.peer.reflection-fields.export', ['format' => $fmt]) }}">
                        <i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}
                    </a>
                </li>
                @endforeach
            </ul>
        </div>

        {{-- Print is a server-rendered view, not window.print(): it shares the
             export action's query so the sheet and the CSV can't drift apart. --}}
        <a href="{{ route('admin.peer.reflection-fields.export', ['format' => 'print']) }}"
           class="btn pe-export-btn prf-export-link border-0" data-format="print"
           data-base="{{ route('admin.peer.reflection-fields.export', ['format' => 'print']) }}"
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
                        <select id="prfCourseFilter" class="form-select js-prf-select2" aria-label="Filter by course">
                            <option value="">Course Name</option>
                            @foreach($courses as $id => $name)
                                <option value="{{ $id }}" @selected((string) $id === $courseFilter)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="prfEventFilter" class="form-select js-prf-select2" aria-label="Filter by event">
                            <option value="">Event Name</option>
                            @foreach($events as $id => $name)
                                <option value="{{ $id }}" @selected((string) $id === $eventFilter)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button"
                            class="btn programme-dt-btn-reset {{ (filled($courseFilter) || filled($eventFilter)) ? '' : 'd-none' }}"
                            id="prfRemoveFilter">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="prfBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#prfColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="prfDtSearch" class="programme-dt-search" data-dt-search-for="peerReflectionFieldsTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="prfDtFooter"
                     class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="peerReflectionFieldsTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- ───────────────────────── Add Field ───────────────────────── --}}
<div class="modal fade" id="prfAddModal" tabindex="-1" aria-labelledby="prfAddLabel"
     aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pe-modal border-0 shadow">
            <form id="prfAddForm" action="{{ route('admin.peer.reflection-fields.store') }}" method="POST" novalidate>
                @csrf
                <div class="modal-header pe-modal-header">
                    <h5 class="modal-title" id="prfAddLabel">Add Reflection Field</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pe-modal-body">
                    @include('admin.forms.peer_evaluation.reflection_fields._form_fields', ['prefix' => 'prfAdd', 'courses' => $modalCourses, 'multiple' => true])
                </div>
                <div class="modal-footer pe-modal-footer">
                    <button type="button" class="btn pe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn pe-btn-submit">Add Field</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ───────────────────────── Edit Field ──────────────────────── --}}
<div class="modal fade" id="prfEditModal" tabindex="-1" aria-labelledby="prfEditLabel"
     aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pe-modal border-0 shadow">
            <form id="prfEditForm" action="" method="POST" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-header pe-modal-header">
                    <h5 class="modal-title" id="prfEditLabel">Edit Reflection Field</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pe-modal-body">
                    @include('admin.forms.peer_evaluation.reflection_fields._form_fields', ['prefix' => 'prfEdit', 'courses' => $modalCourses])
                </div>
                <div class="modal-footer pe-modal-footer">
                    <button type="button" class="btn pe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn pe-btn-submit">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─────────────────────── Delete confirm ────────────────────── --}}
<div class="modal fade" id="prfDeleteModal" tabindex="-1" aria-labelledby="prfDeleteTitle"
     aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pe-confirm border-0 shadow-lg">
            <div class="modal-body text-center px-4 px-md-5 py-5">
                <div class="pe-confirm-icon mb-4" role="img" aria-hidden="true">
                    <i class="bi bi-exclamation-lg"></i>
                </div>
                <h2 class="pe-confirm-title mb-3" id="prfDeleteTitle">Confirm Delete?</h2>
                <p class="pe-confirm-message mb-4" id="prfDeleteMessage"></p>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <button type="button" class="btn pe-confirm-btn pe-confirm-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn pe-confirm-btn pe-confirm-ok" id="prfDeleteConfirm">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ────────────────────── Column visibility ──────────────────── --}}
<div class="modal fade" id="prfColumnVisibilityModal" tabindex="-1"
     aria-labelledby="prfColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="prfColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                {{-- #peerReflectionFieldColumnToggleGrid is registered in custom.css's
                     colvis-item ID lists (three separate selector groups). --}}
                <div class="row g-3" id="peerReflectionFieldColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- One of @push('scripts') / @section('scripts') only - the master layout renders
     BOTH, so using both would double-render this block. --}}
@push('scripts')
{!! $dataTable->scripts() !!}
<script>
(function ($) {
    'use strict';

    var TABLE_ID = 'peerReflectionFieldsTable';
    var COLVIS_KEY = 'peerReflectionFieldsGrid:hiddenColumns:v1';
    var OPTIONS_URL = @json(route('admin.peer.reflection-fields.options'));

    // Grid column index -> export column key. Positional; '' marks a column the
    // export doesn't carry. Adding a table column means editing this too.
    var EXPORT_COLUMN_KEYS = ['sno', 'course_name', 'event_name', 'field_label', 'created_date', 'status', ''];

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

        var $grid = $('#peerReflectionFieldColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();

        table.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'prfcolvis_' + idx;
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
                syncExportLinks();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    /* ── Export links follow the grid ──────────────────────────────── */

    function syncExportLinks() {
        var course = $('#prfCourseFilter').val() || '';
        var event = $('#prfEventFilter').val() || '';
        var search = dt ? (dt.search() || '') : '';

        var cols = [];
        if (dt) {
            dt.columns().every(function () {
                var key = EXPORT_COLUMN_KEYS[this.index()];
                if (key && this.visible()) { cols.push(key); }
            });
        }

        $('.prf-export-link').each(function () {
            var url = new URL($(this).data('base'), window.location.origin);
            // Always stamped, including 'active': being explicit keeps a copied
            // link meaning what it showed.
            url.searchParams.set('status_filter', currentStatus);
            if (course) { url.searchParams.set('course_filter', course); }
            if (event) { url.searchParams.set('event_filter', event); }
            if (search) { url.searchParams.set('q', search); }
            if (cols.length && cols.length !== EXPORT_COLUMN_KEYS.filter(Boolean).length) {
                url.searchParams.set('cols', cols.join(','));
            }
            this.href = url.toString();
        });
    }

    /* ── Select2 ───────────────────────────────────────────────────── */

    // Init is idempotent so repeated calls (modal re-open, option rebuild) don't
    // stack instances. $parent matters inside a modal: without dropdownParent the
    // search box can't take focus.
    function initSelect2($scope, $parent) {
        if (!$.fn.select2) { return; }

        $scope.find('select.js-prf-select2').each(function () {
            var $sel = $(this);
            if ($sel.data('select2')) { return; }
            var opts = {
                width: '100%',
                placeholder: $sel.find('option:first').text() || 'Select',
                allowClear: !$sel.prop('required')
            };
            if ($parent && $parent.length) { opts.dropdownParent = $parent; }

            // The course picker carries an Active / Archived pill on every row.
            // The optgroup headings already split the list, but they scroll out of
            // reach on a long one, and the closed control has no heading at all -
            // so the pill is what says which half the picked course came from.
            if ($sel.hasClass('js-prf-course-status')) {
                opts.templateResult = renderCourseOption;
                opts.templateSelection = renderCourseOption;
                opts.escapeMarkup = function (markup) { return markup; };
            }

            $sel.select2(opts);
        });
    }

    // Select2 hands the <option> over as .element; an optgroup heading and a "no
    // results" row have none, so guard before reaching for the attribute.
    function renderCourseOption(state) {
        var text = $('<span>').text(state.text || '');
        if (!state.id || !state.element) { return text; }

        var status = $(state.element).data('status');
        if (!status) { return text; }

        var isActive = String(status) === 'active';
        var $pill = $('<span>')
            .addClass('pec-course-pill ' + (isActive ? 'pec-course-pill--active' : 'pec-course-pill--archived'))
            .text(isActive ? 'Active' : 'Archived');

        return $('<span>').addClass('pec-course-option').append(text).append($pill);
    }

    // Select2 renders its own box, so a programmatic value change (or a rebuilt
    // option list) only shows up after this event. Safe when Select2 isn't attached.
    function refreshSelect2($scope) {
        $scope.find('select.js-prf-select2').trigger('change.select2');
    }

    /* ── Dependent dropdowns ───────────────────────────────────────── */

    // Rebuild $event (and optionally $group) for the given course/event.
    // res.events / res.groups are ordered LISTS of {id, name} - see
    // PeerReflectionFieldController::options() for why they aren't maps.
    function loadOptions(opts) {
        var params = { course_id: opts.courseId || '', event_id: opts.eventId || '' };

        // Only the FILTER row sends the pill; the modals must reach every
        // course/event regardless of which tab is showing.
        if (opts.scoped) { params.status = currentStatus; }

        return $.getJSON(OPTIONS_URL, params).done(function (res) {
            if (opts.$course && opts.$course.length && res && res.courses) {
                fill(opts.$course, res.courses, opts.keepCourse);
            }
            if (opts.$event && opts.$event.length) {
                fill(opts.$event, (res && res.events) || [], opts.keepEvent);
            }
            if (opts.$group && opts.$group.length) {
                fill(opts.$group, (res && res.groups) || [], opts.keepGroup);
            }
        });
    }

    function fill($sel, list, keep) {
        $sel.find('option:not(:first)').remove();
        $.each(list, function (i, item) {
            $sel.append($('<option>', { value: item.id, text: item.name }));
        });
        $sel.val(keep && $sel.find('option[value="' + keep + '"]').length ? keep : '');
        // Select2 re-reads <option>s live, but its rendered selection only
        // refreshes on this event.
        $sel.trigger('change.select2');
    }

    /* ── Modal helpers ─────────────────────────────────────────────── */

    // Whole state derived from the DOM after every change, never by nudging the
    // neighbouring card - that is how a clone ends up inheriting stale state.
    function syncFieldCards() {
        var $cards = $('#prfFieldsContainer .prf-field-card');
        var last = $cards.length - 1;

        $cards.each(function (index) {
            var $card = $(this);
            $card.attr('data-index', index);
            $card.find('.prf-label').attr('name', 'fields[' + index + '][field_label]');
            // Plain show/hide, not .d-none: Bootstrap's display utilities are
            // !important and would win over a class toggle here.
            $card.find('.pec-card-btn--remove').toggle($cards.length > 1);
            $card.find('.pec-card-btn--add').toggle(index === last);
        });
    }

    // Back to a single empty row - used when the Add modal opens.
    function resetFieldCards() {
        $('#prfFieldsContainer .prf-field-card').not(':first').remove();
        $('#prfFieldsContainer').find('.prf-label').val('');
        $('#prfFieldsContainer').find('.pec-card-error').removeClass('is-shown').text('');
        $('#prfFieldsError').removeClass('is-shown').text('');
        syncFieldCards();
    }

    function clearErrors($form) {
        $form.find('.pe-error').removeClass('is-shown').text('');
        $form.find('.pe-control').removeClass('is-invalid');
    }

    function showErrors($form, errors) {
        $.each(errors, function (field, messages) {
            var msg = $.isArray(messages) ? messages[0] : messages;

            // fields.0.field_label -> that row's own error slot, so the message
            // lands on the label it is about rather than on the first row.
            var m = /^fields\.(\d+)\./.exec(field);
            if (m) {
                var $card = $('#prfFieldsContainer .prf-field-card').eq(parseInt(m[1], 10));
                $card.find('.prf-label').addClass('is-invalid');
                $card.find('.pec-card-error').addClass('is-shown').text(msg);
                return;
            }
            if (field === 'fields') {
                $('#prfFieldsError').addClass('is-shown').text(msg);
                return;
            }

            var $field = $form.find('[name="' + field + '"]');
            $field.addClass('is-invalid');
            $field.closest('.pe-field').find('.pe-error').addClass('is-shown').text(msg);
        });
    }
    function syncPlaceholderState($form) {
        $form.find('select.pe-control').each(function () {
            $(this).toggleClass('pe-placeholder', !$(this).val());
        });
    }

    function toast(message) {
        if (window.Swal) {
            // icon:'success' renders as the global top-right toast card.
            window.Swal.fire({ icon: 'success', title: message });
        }
    }

    function submitForm($form) {
        var $submit = $form.find('button[type="submit"]');

        clearErrors($form);
        $submit.prop('disabled', true);

        $.ajax({ url: $form.attr('action'), type: 'POST', data: $form.serialize(), dataType: 'json' })
            .done(function (res) {
                $form.closest('.modal').each(function () {
                    var inst = bootstrap.Modal.getInstance(this);
                    if (inst) { inst.hide(); }
                });
                if (dt) { dt.ajax.reload(null, false); }
                toast((res && res.message) || 'Saved successfully.');
            })
            .fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showErrors($form, xhr.responseJSON.errors);
                    return;
                }
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.';
                if (window.Swal) { window.Swal.fire({ icon: 'error', title: 'Error', text: msg }); }
                else { window.alert(msg); }
            })
            .always(function () { $submit.prop('disabled', false); });
    }

    /* ── Wiring ────────────────────────────────────────────────────── */

    $(function () {
        // Filter row: no dropdownParent, they are not inside a modal.
        initSelect2($('.programme-dt-toolbar'), null);

        // Hides the remove button on the lone starting card; without this both
        // buttons show until the first add/remove.
        syncFieldCards();

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
            var course = $('#prfCourseFilter').val();
            var event = $('#prfEventFilter').val();
            if (course) { data.course_filter = course; }
            if (event) { data.event_filter = event; }
        });

        /* Active / Archived pills. Switching scope rebuilds BOTH filter dropdowns
           from the server: a course whose fields are all on archived courses must
           not be offered on the Active tab, or picking it yields an empty grid. */
        $('.programme-status-pill').on('click', function () {
            var $pill = $(this);
            var status = $pill.data('prf-status');
            if (status === currentStatus) { return; }

            currentStatus = status;
            $('.programme-status-pill')
                .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
            $pill.addClass('active').attr('aria-pressed', 'true').attr('aria-current', 'true');

            loadOptions({
                scoped: true,
                $course: $('#prfCourseFilter'),
                $event: $('#prfEventFilter')
            }).always(function () {
                $('#prfRemoveFilter').addClass('d-none');
                // Reload even if the options call failed - the grid must follow
                // the pill the user just pressed.
                if (dt) { dt.ajax.reload(); }
                syncExportLinks();
            });
        });

        function filtersChanged() {
            var any = !!($('#prfCourseFilter').val() || $('#prfEventFilter').val());
            $('#prfRemoveFilter').toggleClass('d-none', !any);
            if (dt) { dt.ajax.reload(); }
            syncExportLinks();
        }

        // Changing the course narrows the Event list, so rebuild it first and only
        // reload the grid once the new options are in - otherwise the grid would
        // reload twice and briefly filter on an event from the old course.
        $('#prfCourseFilter').on('change', function () {
            loadOptions({ scoped: true, courseId: $(this).val(), $event: $('#prfEventFilter') })
                .always(filtersChanged);
        });

        $('#prfEventFilter').on('change', filtersChanged);

        $('#prfRemoveFilter').on('click', function () {
            $('#prfCourseFilter').val('').trigger('change.select2');
            $('#prfEventFilter').val('').trigger('change.select2');
            loadOptions({ scoped: true, courseId: '', $event: $('#prfEventFilter') })
                .always(filtersChanged);
        });

        /* Modals: course -> event -> group are dependent, so they are wired in
           that order regardless of the order they are laid out in. */
        $('#prfAddForm, #prfEditForm').on('change', 'select[name="course_id"]', function () {
            var $form = $(this).closest('form');
            loadOptions({
                courseId: $(this).val(),
                $event: $form.find('select[name="event_id"]'),
                $group: $form.find('select[name="group_id"]')
            }).always(function () { syncPlaceholderState($form); });
        });

        $('#prfAddForm, #prfEditForm').on('change', 'select[name="event_id"]', function () {
            var $form = $(this).closest('form');
            loadOptions({
                courseId: $form.find('select[name="course_id"]').val(),
                eventId: $(this).val(),
                $group: $form.find('select[name="group_id"]')
            }).always(function () { syncPlaceholderState($form); });
        });

        $('#prfAddForm, #prfEditForm').on('change', 'select.pe-control', function () {
            $(this).toggleClass('pe-placeholder', !$(this).val());
        });

        /* Add */
        $('#prfAddModal').on('show.bs.modal', function () {
            var $form = $('#prfAddForm');
            $form[0].reset();
            clearErrors($form);
            // reset() blanks the inputs but the extra rows are DOM, not values.
            resetFieldCards();
            // Seed from whatever the grid is filtered to - almost always the scope
            // the user is adding a field for.
            var course = $('#prfCourseFilter').val();
            var event = $('#prfEventFilter').val();
            $form.find('[name="course_id"]').val(course || '');
            loadOptions({
                courseId: course, eventId: event,
                $event: $form.find('[name="event_id"]'), keepEvent: event,
                $group: $form.find('[name="group_id"]')
            }).always(function () {
                syncPlaceholderState($form);
                refreshSelect2($form);
            });
        });

        $('#prfAddModal, #prfEditModal').on('shown.bs.modal', function () {
            initSelect2($(this), $(this));
            refreshSelect2($(this).find('form'));
        });

        $('#prfFieldsContainer').on('click', '.pec-card-btn--add', function () {
            var $clone = $('#prfFieldsContainer .prf-field-card').first().clone();
            $clone.find('.prf-label').val('').removeClass('is-invalid').removeAttr('id');
            $clone.find('.pec-card-error').removeClass('is-shown').text('');
            // Cloned from the FIRST card, so any hidden button state it carried is
            // re-derived by syncFieldCards() rather than inherited.
            $clone.find('.pec-card-btn').show();
            $('#prfFieldsContainer').append($clone);
            syncFieldCards();
            $clone.find('.prf-label').trigger('focus');
        });

        $('#prfFieldsContainer').on('click', '.pec-card-btn--remove', function () {
            if ($('#prfFieldsContainer .prf-field-card').length <= 1) { return; }
            $(this).closest('.prf-field-card').remove();
            syncFieldCards();
        });

        $('#prfAddForm').on('submit', function (e) { e.preventDefault(); submitForm($(this)); });

        /* Edit - everything the modal needs travels on the row button. */
        $(document).on('click', '.prf-edit-btn', function () {
            var $btn = $(this);
            var $form = $('#prfEditForm');

            $form.attr('action', '{{ route('admin.peer.reflection-fields.update', ['id' => '__ID__']) }}'.replace('__ID__', $btn.data('id')));
            clearErrors($form);
            $form.find('[name="field_label"]').val($btn.data('field-label'));
            $form.find('[name="course_id"]').val($btn.data('course-id') || '');

            // Populate before prefilling: assigning .val() to a <select> whose
            // options don't exist yet is silently dropped.
            loadOptions({
                courseId: $btn.data('course-id'),
                eventId: $btn.data('event-id'),
                $event: $form.find('[name="event_id"]'), keepEvent: String($btn.data('event-id') || ''),
                $group: $form.find('[name="group_id"]'), keepGroup: String($btn.data('group-id') || '')
            }).always(function () {
                syncPlaceholderState($form);
                refreshSelect2($form);
            });

            bootstrap.Modal.getOrCreateInstance(document.getElementById('prfEditModal')).show();
        });

        $('#prfEditForm').on('submit', function (e) { e.preventDefault(); submitForm($(this)); });

        /* Delete */
        var pendingDeleteId = null;

        $(document).on('click', '.prf-delete-btn', function () {
            var $btn = $(this);
            pendingDeleteId = $btn.data('id');
            $('#prfDeleteMessage').text(
                'Are you sure you want to delete ' + ($btn.data('field-label') || 'this reflection field') +
                '? This action can\'t be undone.'
            );
            bootstrap.Modal.getOrCreateInstance(document.getElementById('prfDeleteModal')).show();
        });

        $('#prfDeleteConfirm').on('click', function () {
            if (!pendingDeleteId) { return; }
            var $ok = $(this);
            $ok.prop('disabled', true);

            $.ajax({
                url: '{{ route('admin.peer.reflection-fields.destroy', ['id' => '__ID__']) }}'.replace('__ID__', pendingDeleteId),
                type: 'POST',
                data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                dataType: 'json'
            })
                .done(function (res) {
                    var inst = bootstrap.Modal.getInstance(document.getElementById('prfDeleteModal'));
                    if (inst) { inst.hide(); }
                    if (dt) { dt.ajax.reload(null, false); }
                    toast((res && res.message) || 'Reflection field deleted successfully.');
                })
                .fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not delete the reflection field.';
                    if (window.Swal) { window.Swal.fire({ icon: 'error', title: 'Error', text: msg }); }
                    else { window.alert(msg); }
                })
                .always(function () { $ok.prop('disabled', false); pendingDeleteId = null; });
        });

        /* The status switch is driven entirely by the global handler in
           admin_assets/js/custom.js (SweetAlert confirm -> POST admin/toggle-status).
           We write no toggle JS - only the redraw, because the badge and the switch
           live in different columns and hand-mirroring them would drift. */
        $(document).ajaxSuccess(function (event, xhr, settings) {
            var url = (settings && settings.url) ? settings.url : '';
            if (url.indexOf('toggle-status') === -1 && url.indexOf('toggleStatus') === -1) { return; }
            setTimeout(function () { if (dt) { dt.ajax.reload(null, false); } }, 600);
        });
    });
})(jQuery);
</script>
@endpush
