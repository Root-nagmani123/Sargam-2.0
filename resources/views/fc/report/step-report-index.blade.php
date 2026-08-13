@extends('admin.layouts.master')
@section('title', $report->title().' Report')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<style>
    #vsColMenu { max-height: 420px; overflow-y: auto; min-width: 250px; }
    #vsColMenu .form-check-label { cursor: pointer; }
    .vs-course-picker .choices-wrap { flex: 0 1 380px; max-width: 380px; min-width: 0; }
    .vs-course-picker .choices { margin-bottom: 0; width: 100%; }
    .vs-course-picker .choices__inner {
        min-height: 31px; padding: 4px 26px 4px 8px; font-size: .875rem;
        background: #fff; border: 1px solid #dee2e6; border-radius: .25rem; line-height: 1.4;
    }
    .vs-course-picker .choices__list--single { padding: 0; }
    .vs-course-picker .choices[data-type*="select-one"]::after { right: 10px; }
    .choices__list--dropdown .choices__item { font-size: .875rem; }
    #stepReportTable td, #stepReportTable th { font-size: .8rem; vertical-align: top; }
    /* Prose columns wrap; the identity columns never do. */
    #stepReportTable td.vs-long { white-space: normal; min-width: 300px; }
    #stepReportTable th:not(.vs-long), #stepReportTable td:not(.vs-long) { white-space: nowrap; }
    .vs-cell .vs-more { font-size: .75rem; text-decoration: none; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid px-3">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-clipboard-data me-2"></i>{{ $report->title() }} Report
            </h4>
            <small class="text-muted">{{ $report->subtitle() }}</small>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <div class="dropdown">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-1"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Show / hide columns">
                    <i class="bi bi-layout-three-columns"></i> Columns
                </button>
                <ul class="dropdown-menu dropdown-menu-end py-2" id="vsColMenu"></ul>
            </div>
            <a href="#" id="vsExportExcel" class="btn btn-sm btn-success {{ $form ? '' : 'd-none' }}"
               title="Export the rows currently filtered, as .xlsx">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="#" id="vsExportPdf" class="btn btn-sm btn-danger {{ $form ? '' : 'd-none' }}"
               title="Export the rows currently filtered, as PDF">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            @if ($report->hasFileColumns())
                <a href="#" id="vsExportDocs" class="btn btn-sm btn-outline-primary {{ $form ? '' : 'd-none' }}"
                   title="Download every uploaded document for the filtered trainees as one ZIP, one folder per trainee named Username_Rank_ExamYear">
                    <i class="bi bi-file-earmark-zip me-1"></i>Documents (ZIP)
                </a>
            @endif
        </div>
    </div>

    <x-session_message />
    <div id="vsTableError" class="alert alert-warning d-none py-2 px-3 mb-3" role="alert"></div>
    @if (! $mapsStep)
        <div class="alert alert-info py-2 px-3 mb-3" role="alert">
            This course's form does not collect {{ $report->title() }} data, so those columns will be empty.
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3" style="border-radius:8px;">
        <div class="card-body py-3 px-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="vs_form_id" class="form-label small mb-1">Course <span class="text-danger">*</span></label>
                    <div class="d-flex align-items-center gap-2 vs-course-picker">
                        <div class="choices-wrap">
                            <select id="vs_form_id" class="form-select form-select-sm">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Status</label>
                    {{-- The report answers two questions with the same rows: read what they
                         wrote, or chase the people who have not filled it in. Default: everyone. --}}
                    <select id="vs_status" class="form-select form-select-sm">
                        <option value="">All trainees</option>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2 border-top">
                <small class="text-muted mb-0">
                    Select a course to load its trainees. Search matches names, contact details and the text of this step.
                </small>
                <button type="button" id="vsResetFilters" class="btn btn-outline-secondary btn-sm px-3 flex-shrink-0">
                    <i class="bi bi-x-lg me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="card-body p-3">
            {!! $dataTable->table(['class' => 'table table-bordered table-striped table-hover align-middle mb-0', 'style' => 'width:100%;', 'data-sargam-dt-ui' => 'false']) !!}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(function () {
    var ajaxUrl     = "{{ route('admin.reports.'.$reportKey) }}";
    var excelBase   = "{{ route('admin.reports.'.$reportKey.'.export.excel') }}";
    var pdfBase     = "{{ route('admin.reports.'.$reportKey.'.export.pdf') }}";
    // Empty when this report has no upload columns, which also keeps the button hidden.
    var docsBase    = "{{ $report->hasFileColumns() ? route('admin.reports.'.$reportKey.'.export.documents') : '' }}";
    var STORE_KEY   = "fcStepReport.{{ $reportKey }}.hiddenCols.";
    var ALL_COURSES = @json(collect($forms)->map(fn ($f) => ['id' => (string) $f->id, 'label' => ($f->course_name ?: $f->form_name)])->values());
    var COLUMNS     = @json($columnsJson);
    var preselectId = "{{ (int) request('form_id') ?: '' }}";

    var table = null;

    function currentFormId() { return $('#vs_form_id').val() || ''; }
    function filterParams() { return { form_id: currentFormId(), f_status: $('#vs_status').val() || '' }; }

    // ── Remembered column selection ───────────────────────────────────────────
    // Stored per course and per report, as HIDDEN keys: a column added later then shows up by
    // default instead of staying out of a saved list that predates it. Every call is wrapped —
    // localStorage throws in private-browsing modes and when the quota is full.
    function colStoreKey() {
        var id = currentFormId();
        return id ? STORE_KEY + id : '';
    }

    function savedHiddenColumns() {
        var key = colStoreKey();
        if (!key) { return null; }
        try {
            var raw = window.localStorage.getItem(key);
            var arr = raw ? JSON.parse(raw) : null;
            return Array.isArray(arr) ? arr : null;
        } catch (e) { return null; }
    }

    function saveHiddenColumns() {
        var key = colStoreKey();
        if (!key || !table) { return; }
        var hidden = [];
        table.columns().every(function () {
            var name = this.dataSrc();
            if (name && name !== 'DT_RowIndex' && !this.visible()) { hidden.push(name); }
        });
        try { window.localStorage.setItem(key, JSON.stringify(hidden)); } catch (e) {}
    }

    function visibleColumnKeys() {
        // Before the table exists (the first draw of a page load) the saved selection is the
        // only record of what is visible — without this the first request after a refresh would
        // ask for every column and then immediately redraw.
        if (!table) {
            var hidden = savedHiddenColumns();
            if (!hidden || !hidden.length) { return []; }
            return COLUMNS.map(function (c) { return c.key; })
                .filter(function (k) { return hidden.indexOf(k) === -1; });
        }
        var keys = [];
        table.columns().every(function () {
            var name = this.dataSrc();
            if (name && name !== 'DT_RowIndex' && this.visible()) { keys.push(name); }
        });
        return keys;
    }

    function columnDefs() {
        // Restore the remembered selection here rather than toggling after init: a column
        // created hidden is never drawn, so a refresh does not flash the full table first.
        var hidden = savedHiddenColumns() || [];
        var cols = [{ data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center', width: '50px' }];
        COLUMNS.forEach(function (c) {
            cols.push({
                data: c.key, name: c.key, searchable: false, orderable: !!c.orderable,
                className: c.long ? 'vs-long' : '',
                visible: hidden.indexOf(c.key) === -1
            });
        });
        return cols;
    }

    $.fn.dataTable.ext.errMode = 'none';

    function describeAjaxError(xhr) {
        if (!xhr || xhr.status === 0) { return 'Could not reach the server. Check your connection and try again.'; }
        if (xhr.status === 401 || xhr.status === 419) { return 'Your session has expired. Please log in again — this page will reload.'; }
        if (xhr.status === 403) { return 'You do not have access to this report.'; }
        var ct = (xhr.getResponseHeader && xhr.getResponseHeader('Content-Type')) || '';
        if (xhr.status === 302 || ct.indexOf('text/html') === 0) { return 'Your session has expired. Please log in again — this page will reload.'; }
        if (xhr.status >= 500) { return 'The report failed to load (server error ' + xhr.status + '). Try again, or narrow the filters.'; }
        return 'The report could not be loaded (HTTP ' + xhr.status + ').';
    }

    function sessionHasExpired(xhr) {
        if (!xhr) { return false; }
        var ct = (xhr.getResponseHeader && xhr.getResponseHeader('Content-Type')) || '';
        return xhr.status === 401 || xhr.status === 419 || xhr.status === 302 || ct.indexOf('text/html') === 0;
    }

    function renderHeader() {
        var h = '<thead><tr><th class="text-center" style="width:50px;">S.No.</th>';
        COLUMNS.forEach(function (c) {
            h += '<th' + (c.long ? ' class="vs-long"' : '') + '>' + $('<div>').text(c.label).html() + '</th>';
        });
        $('#stepReportTable').html(h + '</tr></thead><tbody></tbody>');
    }

    function initTable() {
        table = $('#stepReportTable').DataTable({
            // Keep DataTables' own server-side ordering. Without this datatable-global-ui.js
            // disables it and substitutes a sorter that only reorders the rows already on
            // screen — and that substitute stops decorating the header after a rebuild, which
            // is what makes the sort arrows disappear.
            sargamServerOrder: true,
            processing: true,
            serverSide: true,
            searching: true,
            ordering: true,
            order: [],
            autoWidth: false,
            responsive: false,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                processing: 'Loading…',
                search: '',
                searchPlaceholder: 'Search name / email / mobile / text',
                lengthMenu: 'Showing _MENU_',
                info: 'of _TOTAL_ items',
                infoEmpty: 'of 0 items',
                infoFiltered: 'of _MAX_ items',
                emptyTable: currentFormId() ? 'No trainees found.' : 'Select a course to begin.',
                zeroRecords: 'No trainees found.',
                paginate: {
                    previous: '<i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">chevron_left</i>',
                    next: '<i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">chevron_right</i>'
                }
            },
            ajax: {
                url: ajaxUrl,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: function (d) {
                    var keys = visibleColumnKeys();
                    $.extend(d, filterParams(), keys.length ? { cols: keys.join(',') } : {});
                }
            },
            columns: columnDefs(),
            // The horizontal scroller wraps the TABLE only, so the search box, length menu and
            // pagination stay at page width instead of scrolling sideways with a wide table.
            dom: "<'row mb-2 align-items-center'<'col-sm-6'l><'col-sm-6'f>>" +
                 "<'row'<'col-sm-12'<'table-responsive'tr>>>" +
                 "<'row mt-2 align-items-center'<'col-sm-5'i><'col-sm-7'p>>"
        });

        table.on('draw', syncExportButtons);
        table.on('error.dt', function (e, settings) {
            var xhr = settings && settings.jqXHR;
            $('#vsTableError').removeClass('d-none').text(describeAjaxError(xhr));
            if (sessionHasExpired(xhr)) {
                setTimeout(function () { window.location.reload(); }, 2500);
            }
        });
    }

    function buildColMenu() {
        var $menu = $('#vsColMenu').empty();
        if (!table) { return; }

        table.columns().every(function () {
            var col = this;
            var title = ($(col.header()).text() || '').trim();
            if (!title || title.toLowerCase() === 's.no.') { return; }

            var $li = $('<li class="px-3 py-1"><div class="form-check mb-0">' +
                '<input type="checkbox" class="form-check-input me-2"' + (col.visible() ? ' checked' : '') + '>' +
                '<label class="form-check-label"></label></div></li>');
            $li.find('label').text(title);
            $li.find('input').on('change', function () {
                col.visible($(this).prop('checked'));
                saveHiddenColumns();
                syncExportButtons();
                reloadForColumnChange();
            });
            $li.find('label').on('click', function (e) {
                e.preventDefault();
                var $cb = $(this).closest('.form-check').find('input');
                $cb.prop('checked', !$cb.prop('checked')).trigger('change');
            });
            $menu.append($li);
        });
    }

    // Showing a hidden column needs a redraw — the server did not send its data on the previous
    // draw. Debounced so ticking several in a row costs one request.
    var colReloadTimer = null;
    function reloadForColumnChange() {
        clearTimeout(colReloadTimer);
        colReloadTimer = setTimeout(function () {
            if (table) { table.ajax.reload(null, false); }
        }, 200);
    }

    function syncExportButtons() {
        var id = currentFormId();
        var $x = $('#vsExportExcel'), $p = $('#vsExportPdf'), $z = $('#vsExportDocs');
        if (!id) { $x.addClass('d-none'); $p.addClass('d-none'); $z.addClass('d-none'); return; }

        var base = $.extend(filterParams(), { search_term: table ? (table.search() || '') : '' });
        var qs = $.param($.extend({}, base, { cols: visibleColumnKeys().join(',') }));
        $x.removeClass('d-none').attr('href', excelBase + '?' + qs);
        $p.removeClass('d-none').attr('href', pdfBase + '?' + qs);

        // The document archive follows the filters but not the visible columns — it contains
        // files, so `cols` means nothing to it.
        if (docsBase) {
            $z.removeClass('d-none').attr('href', docsBase + '?' + $.param(base));
        }
    }

    function rebuild() {
        if (table) { table.destroy(); table = null; }
        renderHeader();
        initTable();
        buildColMenu();
        syncExportButtons();
    }

    var courseChoices = new Choices('#vs_form_id', {
        shouldSort: false, searchEnabled: true, searchResultLimit: 100,
        searchPlaceholderValue: 'Search course...', itemSelectText: '', allowHTML: false,
        placeholder: true, placeholderValue: '— Select Course —'
    });

    function populateCourses(selectId) {
        // A real empty-valued first option, not just Choices' visual placeholder — otherwise the
        // browser auto-selects the first course while Choices still shows the placeholder.
        var opts = [{ value: '', label: '— Select Course —', placeholder: true, selected: !selectId }]
            .concat(ALL_COURSES.map(function (c) { return { value: c.id, label: c.label }; }));
        courseChoices.clearStore();
        courseChoices.setChoices(opts, 'value', 'label', true);
        courseChoices.setChoiceByValue(selectId ? String(selectId) : '');
    }

    // Expand truncated prose in place.
    $('#stepReportTable').on('click', '.vs-more', function () {
        var $cell = $(this).closest('.vs-cell');
        $cell.find('.vs-short').addClass('d-none');
        $cell.find('.vs-full').removeClass('d-none');
        $(this).remove();
    });

    document.getElementById('vs_form_id').addEventListener('change', function () { rebuild(); });

    $('#vs_status').on('change', function () {
        if (table) { table.ajax.reload(); }
        syncExportButtons();
    });

    // Reset returns the page to the state it loads in — course, filter, search and paging all
    // rebuilt from scratch rather than blanked in place, so nothing can keep showing a stale
    // value after its underlying state has been cleared.
    $('#vsResetFilters').on('click', function () {
        $('#vs_status').val('');
        populateCourses(preselectId);
        rebuild();
    });

    populateCourses(preselectId);
    rebuild();
});
</script>
@endpush
