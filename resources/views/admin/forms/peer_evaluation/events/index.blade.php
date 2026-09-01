@extends('admin.layouts.master')

@section('title', 'Manage Events')

@push('styles')
{{-- Module stylesheet (pe-*), scoped .pe-page / .pe-modal. See
     docs/new-design-index-page.md "Where the CSS lives". --}}
<link rel="stylesheet"
      href="{{ asset('css/peer-evaluation-admin.css') }}?v={{ @filemtime(public_path('css/peer-evaluation-admin.css')) ?: time() }}">
{{-- Select2 for the modals' Course Name select: course_master has 145 rows, which
     is not scannable in a plain <select>. Only the CSS is per-page - the library's
     JS is global (admin/layouts/footer.blade.php). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
@endpush

@section('setup_content')
<div class="container-fluid pe-page">
    <x-breadcrum title="Manage Events" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="peAddBtn" data-bs-toggle="modal" data-bs-target="#peAddEventModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Event</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1.
         The hrefs are re-stamped by peSyncExportLinks() with the grid's current
         course filter, search term and visible columns, so a download is always
         what the user is looking at rather than the unfiltered table. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pe-secondary-actions">
        {{-- Status pills, left of the exports. Archived = the event's End Date has
             passed; Active = running, upcoming, or (legacy rows) undated. --}}
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
            role="group" aria-label="Filter events by status">
            <li class="nav-item" role="presentation">
                <button type="button"
                        class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $statusFilter === 'active' ? 'active' : '' }}"
                        data-pe-status="active"
                        aria-pressed="{{ $statusFilter === 'active' ? 'true' : 'false' }}"
                        @if($statusFilter === 'active') aria-current="true" @endif>Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button"
                        class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $statusFilter === 'archive' ? 'active' : '' }}"
                        data-pe-status="archive"
                        aria-pressed="{{ $statusFilter === 'archive' ? 'true' : 'false' }}"
                        @if($statusFilter === 'archive') aria-current="true" @endif>Archived</button>
            </li>
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
        <div class="dropdown">
            <button type="button" id="peDownloadToggle"
                    class="btn pe-export-btn dropdown-toggle border-0"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="peDownloadToggle">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 pe-export-link"
                       data-format="csv"
                       data-base="{{ route('admin.peer.events.export', ['format' => 'csv']) }}"
                       href="{{ route('admin.peer.events.export', ['format' => 'csv']) }}">
                        <i class="bi bi-filetype-csv" aria-hidden="true"></i> CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 pe-export-link"
                       data-format="excel"
                       data-base="{{ route('admin.peer.events.export', ['format' => 'excel']) }}"
                       href="{{ route('admin.peer.events.export', ['format' => 'excel']) }}">
                        <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Excel (.xlsx)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 pe-export-link"
                       data-format="pdf"
                       data-base="{{ route('admin.peer.events.export', ['format' => 'pdf']) }}"
                       href="{{ route('admin.peer.events.export', ['format' => 'pdf']) }}">
                        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF
                    </a>
                </li>
            </ul>
        </div>

        {{-- Print is a server-rendered view, not window.print(): it shares the
             export action's query so the sheet and the CSV can't drift apart. --}}
        <a href="{{ route('admin.peer.events.export', ['format' => 'print']) }}"
           id="pePrintLink" class="btn pe-export-btn pe-export-link border-0"
           data-format="print"
           data-base="{{ route('admin.peer.events.export', ['format' => 'print']) }}"
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
                        <select id="peCourseFilter" class="form-select js-pe-select2" aria-label="Filter by course">
                            <option value="">Course Name</option>
                            @foreach($courses as $id => $name)
                                <option value="{{ $id }}" @selected((string) $id === $courseFilter)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Only meaningful once something is filtered; peSyncFilterUi() shows it. --}}
                    <button type="button"
                            class="btn programme-dt-btn-reset {{ filled($courseFilter) ? '' : 'd-none' }}"
                            id="peRemoveFilter">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="peBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#peColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- datatable-global-ui.js moves DataTables' own filter in here. --}}
                    <div id="peDtSearch" class="programme-dt-search" data-dt-search-for="peerEventsTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                {{-- Footer variant A — the global enhancer fills this with the pager
                     and "Showing [10] of N items". --}}
                <div id="peDtFooter"
                     class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="peerEventsTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- ─────────────────────────── Add Event ─────────────────────────── --}}
<div class="modal fade" id="peAddEventModal" tabindex="-1" aria-labelledby="peAddEventLabel"
     aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pe-modal border-0 shadow">
            {{-- novalidate: the submit handler owns validation so it can render the
                 server's field errors in the same place as its own. --}}
            <form id="peAddEventForm" action="{{ route('admin.peer.events.store') }}" method="POST" novalidate>
                @csrf
                <div class="modal-header pe-modal-header">
                    <h5 class="modal-title" id="peAddEventLabel">Add Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pe-modal-body">
                    @include('admin.forms.peer_evaluation.events._form_fields', ['prefix' => 'peAdd', 'courses' => $modalCourses])
                </div>
                <div class="modal-footer pe-modal-footer">
                    <button type="button" class="btn pe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn pe-btn-submit">Add Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─────────────────────────── Edit Event ────────────────────────── --}}
<div class="modal fade" id="peEditEventModal" tabindex="-1" aria-labelledby="peEditEventLabel"
     aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pe-modal border-0 shadow">
            <form id="peEditEventForm" action="" method="POST" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-header pe-modal-header">
                    <h5 class="modal-title" id="peEditEventLabel">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pe-modal-body">
                    @include('admin.forms.peer_evaluation.events._form_fields', ['prefix' => 'peEdit', 'courses' => $modalCourses])
                </div>
                <div class="modal-footer pe-modal-footer">
                    <button type="button" class="btn pe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn pe-btn-submit">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ───────────────────────── Delete confirm ──────────────────────── --}}
<div class="modal fade" id="peDeleteModal" tabindex="-1" aria-labelledby="peDeleteTitle"
     aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pe-confirm border-0 shadow-lg">
            <div class="modal-body text-center px-4 px-md-5 py-5">
                <div class="pe-confirm-icon mb-4" role="img" aria-hidden="true">
                    <i class="bi bi-exclamation-lg"></i>
                </div>
                <h2 class="pe-confirm-title mb-3" id="peDeleteTitle">Confirm Delete?</h2>
                <p class="pe-confirm-message mb-4" id="peDeleteMessage"></p>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <button type="button" class="btn pe-confirm-btn pe-confirm-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn pe-confirm-btn pe-confirm-ok" id="peDeleteConfirm">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─────────────────────── Column visibility ─────────────────────── --}}
<div class="modal fade" id="peColumnVisibilityModal" tabindex="-1"
     aria-labelledby="peColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="peColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                {{-- Chips are generated from the table's own headers, so the two can
                     never drift apart. #peerEventColumnToggleGrid is registered in
                     custom.css's colvis-item ID lists. --}}
                <div class="row g-3" id="peerEventColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- One of @push('scripts') / @section('scripts') only — the master layout
     renders BOTH, so using both would double-render this block. --}}
@push('scripts')
{!! $dataTable->scripts() !!}
<script>
(function ($) {
    'use strict';

    var TABLE_ID = 'peerEventsTable';
    var COLVIS_KEY = 'peerEventsGrid:hiddenColumns:v1';

    // Grid column index -> the export column key the server understands.
    // Positional, and '' marks a column the export doesn't carry (S. No. is
    // regenerated server-side; Action is chrome). Adding a table column means
    // editing this array too.
    var EXPORT_COLUMN_KEYS = ['sno', 'course_name', 'event_name', 'created_date', 'start_date', 'end_date', ''];

    var dt = null;
    var currentStatus = @json($statusFilter);
    var COURSES_BY_STATUS_URL = @json(route('admin.peer.events.courses-by-status'));

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

        var $grid = $('#peerEventColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();

        table.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'pecolvis_' + idx;
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
        var course = $('#peCourseFilter').val() || '';
        var search = dt ? (dt.search() || '') : '';

        var cols = [];
        if (dt) {
            dt.columns().every(function () {
                var key = EXPORT_COLUMN_KEYS[this.index()];
                if (key && this.visible()) { cols.push(key); }
            });
        }

        $('.pe-export-link').each(function () {
            var url = new URL($(this).data('base'), window.location.origin);
            // Always stamped, including 'active': the export defaults to active,
            // but being explicit keeps a copied link meaning what it showed.
            url.searchParams.set('status_filter', currentStatus);
            if (course) { url.searchParams.set('course_filter', course); }
            if (search) { url.searchParams.set('q', search); }
            if (cols.length && cols.length !== EXPORT_COLUMN_KEYS.filter(Boolean).length) {
                url.searchParams.set('cols', cols.join(','));
            }
            this.href = url.toString();
        });
    }

    /* ── Modal helpers ─────────────────────────────────────────────── */

    function clearErrors($form) {
        $form.find('.pe-error').removeClass('is-shown').text('');
        $form.find('.pe-control').removeClass('is-invalid');
    }

    function showErrors($form, errors) {
        $.each(errors, function (field, messages) {
            var $field = $form.find('[name="' + field + '"]');
            $field.addClass('is-invalid');
            $field.closest('.pe-field').find('.pe-error').addClass('is-shown')
                .text($.isArray(messages) ? messages[0] : messages);
        });
    }

    // Selects and date inputs render grey until they hold a real value, so an
    // empty one reads as a placeholder rather than as a chosen value.
    function syncPlaceholderState($form) {
        $form.find('select.pe-control, input[type="date"].pe-control').each(function () {
            $(this).toggleClass('pe-placeholder', !$(this).val());
        });
    }

    /* Active / Archived badge beside a course, in the list AND on the closed
       control. Returns a jQuery object on purpose: a string would be escaped by
       Select2 and the markup would show up as text. */
    function courseOption(state) {
        if (!state.id) { return state.text; }

        var $option = $(state.element);
        var status = $option.data('status');
        if (!status) { return state.text; }

        return $('<span class="pe-course-opt"></span>')
            .append($('<span class="pe-course-opt__name"></span>').text(state.text))
            .append(
                $('<span class="pe-course-opt__badge"></span>')
                    .addClass(status === 'active' ? 'is-active' : 'is-archived')
                    .text($option.data('status-label') || status)
            );
    }

    /* Select2 on the Course Name select.
       dropdownParent must be the modal or the search box can't take focus. Init is
       idempotent so re-opening the modal doesn't stack instances. */
    function initCourseSelect2($modal) {
        if (!$.fn.select2) { return; }

        $modal.find('select[name="course_id"]').each(function () {
            var $sel = $(this);
            if ($sel.data('select2')) { return; }
            $sel.select2({
                width: '100%',
                dropdownParent: $modal,
                placeholder: 'Select Course',
                allowClear: false,
                templateResult: courseOption,
                templateSelection: courseOption
            });
        });
    }

    /* An event lives inside its course, so Start / End Date are clamped to the
       course's own window. This is a hint only - PeerEventController re-derives
       the window from course_master and rejects anything outside it, because the
       min/max attributes are trivially bypassed.

       clearOutOfRange is false while a form is being POPULATED (edit): a stored
       event that predates the course's current dates should still load, and let
       the server say so on save. It is true when the USER picks a course, where
       silently keeping dates from the previous course would be worse. */
    function applyCourseDateBounds($form, clearOutOfRange) {
        var $option = $form.find('select[name="course_id"] option:selected');
        var min = $option.data('start-date') || '';
        var max = $option.data('end-date') || '';

        $form.find('input[name="start_date"], input[name="end_date"]').each(function () {
            var $input = $(this);

            if (min) { $input.attr('min', min); } else { $input.removeAttr('min'); }
            if (max) { $input.attr('max', max); } else { $input.removeAttr('max'); }

            var value = $input.val();
            if (clearOutOfRange && value && ((min && value < min) || (max && value > max))) {
                $input.val('');
            }
        });

        $form.find('.pe-course-window').remove();
        if (min || max) {
            $form.find('input[name="end_date"]').closest('.pe-field')
                .append(
                    $('<div class="pe-course-window"></div>')
                        .text('Within the course: ' + (min ? dmy(min) : '—') + ' to ' + (max ? dmy(max) : '—'))
                );
        }

        syncPlaceholderState($form);
    }

    // ISO (what the <option> carries and the date input uses) -> d/m/Y (what the
    // rest of this screen shows).
    function dmy(iso) {
        var parts = String(iso).split('-');
        return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : iso;
    }

    // Select2 renders its own box, so a programmatic value change only shows up
    // after this event. Safe to call when Select2 isn't attached.
    function refreshCourseSelect2($form) {
        $form.find('select[name="course_id"]').trigger('change.select2');
    }

    function toast(message) {
        if (window.Swal) {
            // icon:'success' renders as the global top-right toast card.
            window.Swal.fire({ icon: 'success', title: message });
        }
    }

    function submitForm($form, method) {
        var $submit = $form.find('button[type="submit"]');

        clearErrors($form);
        $submit.prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json'
        }).done(function (res) {
            $form.closest('.modal').each(function () {
                var inst = bootstrap.Modal.getInstance(this);
                if (inst) { inst.hide(); }
            });
            if (dt) { dt.ajax.reload(null, false); }
            toast((res && res.message) || 'Saved successfully.');
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                showErrors($form, xhr.responseJSON.errors);
                return;
            }
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.';
            if (window.Swal) {
                window.Swal.fire({ icon: 'error', title: 'Error', text: msg });
            } else {
                window.alert(msg);
            }
        }).always(function () {
            $submit.prop('disabled', false);
        });
    }

    /* ── Wiring ────────────────────────────────────────────────────── */

    $(function () {
        // Yajra initialises the table from the script block above; wait for it
        // rather than initialising a second time (which would duplicate headers).
        var attempts = 0;
        (function waitForTable() {
            if ($.fn.DataTable.isDataTable('#' + TABLE_ID)) {
                dt = $('#' + TABLE_ID).DataTable();
                setupColumns(dt);
                syncExportLinks();

                // The search box lives in #peDtSearch, relocated by the global
                // enhancer, so listen on the table's own event instead.
                $('#' + TABLE_ID).on('search.dt', syncExportLinks);
                return;
            }
            if (++attempts < 60) { window.setTimeout(waitForTable, 100); }
        })();

        // Filter row Select2. The Archived pill lists 143 courses, which is not
        // scannable in a plain <select>. No dropdownParent - it isn't in a modal.
        if ($.fn.select2) {
            $('#peCourseFilter').select2({
                width: '100%',
                placeholder: 'Course Name',
                allowClear: true
            });
        }

        // Course filter → server-side, via the grid's own XHR.
        $('#' + TABLE_ID).on('preXhr.dt', function (e, settings, data) {
            data.status_filter = currentStatus;
            var course = $('#peCourseFilter').val();
            if (course) { data.course_filter = course; }
        });

        /* Active / Archived pills.
           Switching scope rebuilds the Filter dropdown from the server, because a
           course whose events are all archived must not be offered on the Active
           tab -- picking it could only ever produce an empty grid. */
        $('.programme-status-pill').on('click', function () {
            var $pill = $(this);
            var status = $pill.data('pe-status');
            if (status === currentStatus) { return; }

            currentStatus = status;
            $('.programme-status-pill')
                .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
            $pill.addClass('active').attr('aria-pressed', 'true').attr('aria-current', 'true');

            $.getJSON(COURSES_BY_STATUS_URL, { status: status })
                .done(function (res) {
                    var $sel = $('#peCourseFilter');
                    // Rebuild from the placeholder down; the old scope's courses
                    // are not valid choices in the new one.
                    $sel.find('option:not(:first)').remove();
                    // res.courses is an ordered list of {id, name} — see
                    // PeerEventController::coursesByStatus() for why it isn't a map.
                    $.each((res && res.courses) || [], function (i, course) {
                        $sel.append($('<option>', { value: course.id, text: course.name }));
                    });
                    $sel.val('');
                    // Select2 re-reads <option>s live, but its rendered selection
                    // only refreshes on this event.
                    $sel.trigger('change.select2');
                    $('#peRemoveFilter').addClass('d-none');
                })
                .always(function () {
                    // Reload even if the options call failed - the grid must still
                    // follow the pill the user just pressed.
                    if (dt) { dt.ajax.reload(); }
                    syncExportLinks();
                });
        });

        $('#peCourseFilter').on('change', function () {
            $('#peRemoveFilter').toggleClass('d-none', !$(this).val());
            if (dt) { dt.ajax.reload(); }
            syncExportLinks();
        });

        $('#peRemoveFilter').on('click', function () {
            $('#peCourseFilter').val('').trigger('change.select2').trigger('change');
        });

        /* Add */
        $('#peAddEventModal').on('show.bs.modal', function () {
            var $form = $('#peAddEventForm');
            $form[0].reset();
            clearErrors($form);
            // Pre-select whatever course the grid is filtered to — that is almost
            // always the one the user is adding an event for.
            var course = $('#peCourseFilter').val();
            if (course) { $form.find('[name="course_id"]').val(course); }
            syncPlaceholderState($form);
            refreshCourseSelect2($form);
            applyCourseDateBounds($form, false);
        });

        $('#peAddEventModal').on('shown.bs.modal', function () {
            initCourseSelect2($(this));
            refreshCourseSelect2($('#peAddEventForm'));
        });

        $('#peEditEventModal').on('shown.bs.modal', function () {
            initCourseSelect2($(this));
            refreshCourseSelect2($('#peEditEventForm'));
        });

        $('#peAddEventForm').on('submit', function (e) {
            e.preventDefault();
            submitForm($(this));
        });

        /* Edit — everything the modal needs travels on the row button. */
        $(document).on('click', '.pe-edit-btn', function () {
            var $btn = $(this);
            var $form = $('#peEditEventForm');

            $form.attr('action', '{{ route('admin.peer.events.update', ['id' => '__ID__']) }}'.replace('__ID__', $btn.data('id')));
            clearErrors($form);
            $form.find('[name="course_id"]').val($btn.data('course-id'));
            $form.find('[name="event_name"]').val($btn.data('event-name'));
            $form.find('[name="start_date"]').val($btn.data('start-date'));
            $form.find('[name="end_date"]').val($btn.data('end-date'));
            $form.find('[name="description"]').val($btn.data('description'));
            syncPlaceholderState($form);
            refreshCourseSelect2($form);
            // false: an event stored before the course's dates were last edited
            // must still load. The server is what rejects it, on save.
            applyCourseDateBounds($form, false);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('peEditEventModal')).show();
        });

        $('#peEditEventForm').on('submit', function (e) {
            e.preventDefault();
            submitForm($(this));
        });

        // Keep the grey/solid placeholder state in step with what the user picks.
        $('#peAddEventForm, #peEditEventForm').on('change', 'select.pe-control, input[type="date"].pe-control', function () {
            $(this).toggleClass('pe-placeholder', !$(this).val());
        });

        // A different course means a different allowed window, so re-clamp and
        // drop any date the new course can't hold.
        $('#peAddEventForm, #peEditEventForm').on('change', 'select[name="course_id"]', function () {
            applyCourseDateBounds($(this).closest('form'), true);
        });

        /* Delete */
        var pendingDeleteId = null;

        $(document).on('click', '.pe-delete-btn', function () {
            var $btn = $(this);
            pendingDeleteId = $btn.data('id');
            $('#peDeleteMessage').text(
                'Are you sure you want to delete ' + ($btn.data('event-name') || 'this event') +
                '? This action can\'t be undone.'
            );
            bootstrap.Modal.getOrCreateInstance(document.getElementById('peDeleteModal')).show();
        });

        $('#peDeleteConfirm').on('click', function () {
            if (!pendingDeleteId) { return; }
            var $ok = $(this);
            $ok.prop('disabled', true);

            $.ajax({
                url: '{{ route('admin.peer.events.destroy', ['id' => '__ID__']) }}'.replace('__ID__', pendingDeleteId),
                type: 'POST',
                data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                dataType: 'json'
            }).done(function (res) {
                var inst = bootstrap.Modal.getInstance(document.getElementById('peDeleteModal'));
                if (inst) { inst.hide(); }
                if (dt) { dt.ajax.reload(null, false); }
                toast((res && res.message) || 'Event deleted successfully.');
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not delete the event.';
                if (window.Swal) {
                    window.Swal.fire({ icon: 'error', title: 'Error', text: msg });
                } else {
                    window.alert(msg);
                }
            }).always(function () {
                $ok.prop('disabled', false);
                pendingDeleteId = null;
            });
        });
    });
})(jQuery);
</script>
@endpush
