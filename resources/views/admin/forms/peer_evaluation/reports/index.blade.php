@extends('admin.layouts.master')

@section('title', 'Evaluation Reports')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/peer-evaluation-admin.css') }}?v={{ @filemtime(public_path('css/peer-evaluation-admin.css')) ?: time() }}">
{{-- Select2 on every filter dropdown; its JS is global (layouts/footer.blade.php). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/select2-theme.css') }}?v={{ @filemtime(public_path('css/select2-theme.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid pe-page per-page">
    <x-breadcrum title="Evaluation Reports" :showBack="false" />

    <x-session_message />

    {{-- Headline tiles. Computed from the grid's own query wrapped as a subquery,
         so they always agree with the rows below - under every filter. --}}
    @php
        // Built in PHP, not inline in @foreach: Blade's directive-argument parser
        // trips over a multi-line array literal with nested brackets.
        $tiles = [
            ['icon' => 'bi-people',             'tone' => 'blue',   'value' => number_format($stats['total_ots']),          'label' => 'Total Officer Trainees'],
            ['icon' => 'bi-person-check',       'tone' => 'indigo', 'value' => number_format($stats['given']),              'label' => 'Peer Evaluation Given'],
            ['icon' => 'bi-file-earmark-text',  'tone' => 'sky',    'value' => number_format($stats['total_evaluations']),  'label' => 'Total Peer Evaluations'],
            ['icon' => 'bi-file-earmark-x',     'tone' => 'pink',   'value' => number_format($stats['not_given']),          'label' => 'Peer Evaluation Not Given'],
            ['icon' => 'bi-star-fill',          'tone' => 'navy',   'value' => $stats['avg_score'] === null ? '-' : number_format($stats['avg_score'], 2), 'label' => 'Overall Average Score'],
        ];
    @endphp

    <div class="row g-3 mb-3 per-stats">
        @foreach ($tiles as $tile)
        <div class="col-6 col-lg">
            <div class="per-stat">
                <span class="per-stat__icon per-stat__icon--{{ $tile['tone'] }}" aria-hidden="true">
                    <i class="bi {{ $tile['icon'] }}"></i>
                </span>
                <span class="per-stat__body">
                    <span class="per-stat__value">{{ $tile['value'] }}</span>
                    <span class="per-stat__label">{{ $tile['label'] }}</span>
                </span>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Active / Archived scope by the COURSE's status, same rule as Course Master. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pe-secondary-actions">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
            role="group" aria-label="Filter by course status">
            @foreach ([['active', 'Active'], ['archive', 'Archived']] as [$value, $label])
            <li class="nav-item" role="presentation">
                <button type="button"
                        class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $statusFilter === $value ? 'active' : '' }}"
                        data-per-status="{{ $value }}"
                        aria-pressed="{{ $statusFilter === $value ? 'true' : 'false' }}"
                        @if($statusFilter === $value) aria-current="true" @endif>{{ $label }}</button>
            </li>
            @endforeach
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
            <div class="dropdown">
                <button type="button" id="perDownloadToggle" class="btn pe-export-btn dropdown-toggle border-0"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="perDownloadToggle">
                    @foreach ([['csv', 'bi-filetype-csv', 'CSV'], ['excel', 'bi-file-earmark-excel', 'Excel (.xlsx)'], ['pdf', 'bi-file-earmark-pdf', 'PDF']] as [$fmt, $icon, $label])
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 per-export-link"
                           data-format="{{ $fmt }}"
                           data-base="{{ route('admin.peer.reports.export', ['format' => $fmt]) }}"
                           href="{{ route('admin.peer.reports.export', ['format' => $fmt]) }}">
                            <i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            <a href="{{ route('admin.peer.reports.export', ['format' => 'print']) }}"
               class="btn pe-export-btn per-export-link border-0" data-format="print"
               data-base="{{ route('admin.peer.reports.export', ['format' => 'print']) }}"
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
                        <select id="perCourseFilter" class="form-select js-per-select2" aria-label="Filter by course">
                            <option value="">Course Name</option>
                            @foreach($courses as $id => $name)
                                <option value="{{ $id }}" @selected((string) $id === $courseFilter)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="perEventFilter" class="form-select js-per-select2" aria-label="Filter by event">
                            <option value="">Event Name</option>
                            @foreach($events as $id => $name)
                                <option value="{{ $id }}" @selected((string) $id === $eventFilter)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="perGroupFilter" class="form-select js-per-select2" aria-label="Filter by group">
                            <option value="">Group Name</option>
                            @foreach($groups as $id => $name)
                                <option value="{{ $id }}" @selected((string) $id === $groupFilter)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="perSubmissionFilter" class="form-select js-per-select2" aria-label="Filter by submission status">
                            <option value="">Status</option>
                            <option value="submitted" @selected($submissionFilter === 'submitted')>Submitted</option>
                            <option value="pending" @selected($submissionFilter === 'pending')>Pending</option>
                        </select>
                    </div>

                    <button type="button"
                            class="btn programme-dt-btn-reset {{ (filled($courseFilter) || filled($eventFilter) || filled($groupFilter) || filled($submissionFilter)) ? '' : 'd-none' }}"
                            id="perRemoveFilter">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="perBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#perColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="perDtSearch" class="programme-dt-search" data-dt-search-for="peerEvaluationReportsTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="perDtFooter"
                     class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="peerEvaluationReportsTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- ────────────────────── Column visibility ──────────────────── --}}
<div class="modal fade" id="perColumnVisibilityModal" tabindex="-1"
     aria-labelledby="perColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="perColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="peerEvaluationReportColumnToggleGrid"></div>
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

    var TABLE_ID = 'peerEvaluationReportsTable';
    var COLVIS_KEY = 'peerEvaluationReportsGrid:hiddenColumns:v1';
    var OPTIONS_URL = @json(route('admin.peer.reports.options'));

    // Grid column index -> export column key. Positional, and built server-side so
    // the dynamic criteria columns line up with what the export expects.
    var EXPORT_COLUMN_KEYS = @json($exportColumnKeys);

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

        var $grid = $('#peerEvaluationReportColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();

        table.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'percolvis_' + idx;
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
        var search = dt ? (dt.search() || '') : '';
        var cols = [];

        if (dt) {
            dt.columns().every(function () {
                var key = EXPORT_COLUMN_KEYS[this.index()];
                if (key && this.visible()) { cols.push(key); }
            });
        }

        $('.per-export-link').each(function () {
            var url = new URL($(this).data('base'), window.location.origin);
            url.searchParams.set('status_filter', currentStatus);

            [['course_filter', '#perCourseFilter'], ['event_filter', '#perEventFilter'],
             ['group_filter', '#perGroupFilter'], ['submission_filter', '#perSubmissionFilter']]
                .forEach(function (pair) {
                    var v = $(pair[1]).val();
                    if (v) { url.searchParams.set(pair[0], v); }
                });

            if (search) { url.searchParams.set('q', search); }
            if (cols.length && cols.length !== EXPORT_COLUMN_KEYS.filter(Boolean).length) {
                url.searchParams.set('cols', cols.join(','));
            }
            this.href = url.toString();
        });
    }

    /* ── Select2 ───────────────────────────────────────────────────── */

    function initSelect2($scope) {
        if (!$.fn.select2) { return; }
        $scope.find('select.js-per-select2').each(function () {
            var $sel = $(this);
            if ($sel.data('select2')) { return; }
            $sel.select2({
                width: '100%',
                placeholder: $sel.find('option:first').text() || 'Select',
                allowClear: true
            });
        });
    }

    /* ── Dependent dropdowns ───────────────────────────────────────── */

    function loadOptions(opts) {
        var params = {
            course_id: opts.courseId || '',
            event_id: opts.eventId || '',
            status: currentStatus
        };

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
        // Select2 re-reads <option>s live but only re-renders on this event.
        $sel.trigger('change.select2');
    }

    /* ── Wiring ────────────────────────────────────────────────────── */

    $(function () {
        initSelect2($('.programme-dt-toolbar'));

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
            [['course_filter', '#perCourseFilter'], ['event_filter', '#perEventFilter'],
             ['group_filter', '#perGroupFilter'], ['submission_filter', '#perSubmissionFilter']]
                .forEach(function (pair) {
                    var v = $(pair[1]).val();
                    if (v) { data[pair[0]] = v; }
                });
        });

        function anyFilter() {
            return !!($('#perCourseFilter').val() || $('#perEventFilter').val()
                || $('#perGroupFilter').val() || $('#perSubmissionFilter').val());
        }

        // The tiles are rendered server-side, so a filter change has to reload the
        // page for them to stay truthful. Reloading also rebuilds the dependent
        // dropdowns and the criteria columns in one go.
        function applyFilters() {
            var url = new URL(window.location.href);
            url.searchParams.set('status_filter', currentStatus);

            [['course_filter', '#perCourseFilter'], ['event_filter', '#perEventFilter'],
             ['group_filter', '#perGroupFilter'], ['submission_filter', '#perSubmissionFilter']]
                .forEach(function (pair) {
                    var v = $(pair[1]).val();
                    if (v) { url.searchParams.set(pair[0], v); }
                    else { url.searchParams.delete(pair[0]); }
                });

            window.location.href = url.toString();
        }

        $('.programme-status-pill').on('click', function () {
            var status = $(this).data('per-status');
            if (status === currentStatus) { return; }
            currentStatus = status;
            // Changing scope invalidates every filter below it, so drop them.
            $('#perCourseFilter, #perEventFilter, #perGroupFilter, #perSubmissionFilter').val('');
            applyFilters();
        });

        // Course narrows Event and Group; Event narrows Group. Rebuild in place so
        // the user sees the narrowed list before the page reloads.
        $('#perCourseFilter').on('change', function () {
            loadOptions({
                courseId: $(this).val(),
                $event: $('#perEventFilter'),
                $group: $('#perGroupFilter')
            }).always(applyFilters);
        });

        $('#perEventFilter').on('change', function () {
            loadOptions({
                courseId: $('#perCourseFilter').val(),
                eventId: $(this).val(),
                $group: $('#perGroupFilter')
            }).always(applyFilters);
        });

        $('#perGroupFilter, #perSubmissionFilter').on('change', applyFilters);

        $('#perRemoveFilter').on('click', function () {
            $('#perCourseFilter, #perEventFilter, #perGroupFilter, #perSubmissionFilter')
                .val('').trigger('change.select2');
            applyFilters();
        });

        $('#perRemoveFilter').toggleClass('d-none', !anyFilter());
    });
})(jQuery);
</script>
@endpush
