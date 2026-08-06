@extends('admin.layouts.master')
@section('title', 'Descriptive Data Report')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<style>
    #ddColMenu { max-height: 340px; overflow-y: auto; min-width: 250px; }
    #ddColMenu .form-check-label { cursor: pointer; }
    #descriptiveDataTable_wrapper .dataTables_filter { text-align: right; }
    #descriptiveDataTable_wrapper .dataTables_filter input { width: auto; min-width: 220px; }
    .dd-course-picker .choices-wrap { flex: 0 1 380px; max-width: 380px; min-width: 0; }
    .dd-course-picker .choices { margin-bottom: 0; width: 100%; }
    .dd-course-picker .choices__inner {
        min-height: 31px; padding: 4px 26px 4px 8px; font-size: .875rem;
        background: #fff; border: 1px solid #dee2e6; border-radius: .25rem; line-height: 1.4;
    }
    .dd-course-picker .choices__list--single { padding: 0; }
    .dd-course-picker .choices[data-type*="select-one"]::after { right: 10px; }
    .choices__list--dropdown .choices__item { font-size: .875rem; }
    /* ~25 columns: keep the table scrolling inside its card instead of the page. */
    #descriptiveDataTable td, #descriptiveDataTable th { font-size: .8rem; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid px-3">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-table me-2"></i>Descriptive Data Report
            </h4>
            <small class="text-muted">
                The Descriptive Roll fields in tabular form — course wise, with Excel and PDF export.
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <div class="dropdown" id="ddColDropdown">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-1"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Show / hide columns">
                    <i class="bi bi-layout-three-columns"></i> Columns
                </button>
                <ul class="dropdown-menu dropdown-menu-end py-2" id="ddColMenu"></ul>
            </div>
            <a href="#" id="btnExportExcel" class="btn btn-sm btn-success {{ $form ? '' : 'd-none' }}"
               title="Export the rows currently filtered, as .xlsx">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="#" id="btnExportCsv" class="btn btn-sm btn-outline-success {{ $form ? '' : 'd-none' }}"
               title="Export as CSV — streamed, no row limit. Use this for large courses.">
                <i class="bi bi-filetype-csv me-1"></i>CSV
            </a>
            <a href="#" id="btnExportPdf" class="btn btn-sm btn-danger {{ $form ? '' : 'd-none' }}"
               title="Export the rows currently filtered, as PDF (landscape)">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
        </div>
    </div>

    <x-session_message />

    {{-- Course + field filters --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:8px;">
        <div class="card-body py-3 px-3">
            <div class="row g-2 align-items-end" id="ddFilterRow">
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="filter_form_id" class="form-label small mb-1">Course <span class="text-danger">*</span></label>
                    <div class="d-flex align-items-center gap-2 dd-course-picker">
                        <div class="choices-wrap">
                            {{-- The empty option is deliberate: it gives the native select a
                                 valid "nothing chosen" value before Choices populates it. --}}
                            <select id="filter_form_id" class="form-select form-select-sm">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Field filters are rendered by JS so the initial load and a course switch
                     go through exactly one code path. --}}
                <div class="col-6 col-md-3 col-lg-2">
                    <button type="button" id="btnResetFilters" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-lg me-1"></i>Reset
                    </button>
                </div>
            </div>
            <small class="text-muted d-block mt-2">
                Select a course to load its fields. Columns differ per course because each form maps its own fields.
            </small>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="card-body p-3">
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table table-bordered table-striped table-hover text-nowrap align-middle mb-0', 'style' => 'width:100%;', 'data-sargam-dt-ui' => 'false']) !!}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(function () {
    var ajaxUrl     = "{{ route('admin.reports.descriptive-data') }}";
    var metaUrl     = "{{ route('admin.reports.descriptive-data.columns') }}";
    var excelBase   = "{{ route('admin.reports.descriptive-data.export.excel') }}";
    var csvBase     = "{{ route('admin.reports.descriptive-data.export.csv') }}";
    var pdfBase     = "{{ route('admin.reports.descriptive-data.export.pdf') }}";
    var ALL_COURSES = @json(collect($forms)->map(fn ($f) => ['id' => (string) $f->id, 'label' => ($f->course_name ?: $f->form_name)])->values());
    var preselectId = "{{ (int) request('form_id') ?: '' }}";

    // Metadata for the course already on screen. A course switch fetches the same shape from
    // metaUrl, so the initial render and every later one share one code path.
    var META = {
        fields: @json($fieldsJson ?? []),
        filterOptions: @json((object) ($filterOptions ?? []))
    };

    var table = null;

    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }
    function currentFormId() { return $('#filter_form_id').val() || ''; }

    function filterParams() {
        var params = { form_id: currentFormId() };
        $('.dd-filter').each(function () {
            var v = $(this).val();
            if (v) { params[$(this).data('param')] = v; }
        });
        return params;
    }

    function visibleColumnKeys() {
        if (!table) { return []; }
        var keys = [];
        table.columns().every(function () {
            var name = this.dataSrc();
            if (name && name !== 'DT_RowIndex' && this.visible()) { keys.push(name); }
        });
        return keys;
    }

    // ── Rendering driven entirely by META ─────────────────────────────────────
    function renderFilters() {
        $('#ddFilterRow .dd-filter-col').remove();
        var $anchor = $('#btnResetFilters').closest('.col-6');

        META.fields.forEach(function (f) {
            if (!f.filter) { return; }
            var html;
            if (f.filter === 'date_range') {
                html = '<div class="col-6 col-md-3 col-lg-2 dd-filter-col">' +
                       '<label class="form-label small mb-1">' + esc(f.label) + ' (From)</label>' +
                       '<input type="date" class="form-control form-control-sm dd-filter" data-param="f_' + esc(f.key) + '_from"></div>' +
                       '<div class="col-6 col-md-3 col-lg-2 dd-filter-col">' +
                       '<label class="form-label small mb-1">' + esc(f.label) + ' (To)</label>' +
                       '<input type="date" class="form-control form-control-sm dd-filter" data-param="f_' + esc(f.key) + '_to"></div>';
            } else {
                var opts = (META.filterOptions || {})[f.key] || [];
                var o = '<option value="">All</option>';
                opts.forEach(function (item) {
                    o += '<option value="' + esc(item.value) + '">' + esc(item.label) + '</option>';
                });
                html = '<div class="col-6 col-md-3 col-lg-2 dd-filter-col">' +
                       '<label class="form-label small mb-1">' + esc(f.label) + '</label>' +
                       '<select class="form-select form-select-sm dd-filter" data-param="f_' + esc(f.key) + '">' + o + '</select></div>';
            }
            $(html).insertBefore($anchor);
        });
    }

    function renderHeader() {
        var h = '<thead><tr><th class="text-center" style="width:50px;">S.No.</th><th>Username</th>';
        META.fields.forEach(function (f) { h += '<th>' + esc(f.label) + '</th>'; });
        $('#descriptiveDataTable').html(h + '</tr></thead><tbody></tbody>');
    }

    function columnDefs() {
        var cols = [
            { data: 'DT_RowIndex',    name: 'DT_RowIndex',    orderable: false, searchable: false, className: 'text-center', width: '50px' },
            { data: 'login_username', name: 'login_username', orderable: false, searchable: false }
        ];
        META.fields.forEach(function (f) {
            cols.push({ data: f.key, name: f.key, searchable: false, orderable: !!f.orderable });
        });
        return cols;
    }

    function initTable() {
        table = $('#descriptiveDataTable').DataTable({
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
                searchPlaceholder: 'Search name / mobile / email / ID',
                lengthMenu: 'Showing _MENU_',
                info: 'of _TOTAL_ items',
                infoEmpty: 'of 0 items',
                infoFiltered: 'of _MAX_ items',
                emptyTable: currentFormId() ? 'No students found.' : 'Select a course to begin.',
                zeroRecords: 'No students found.',
                paginate: {
                    previous: '<i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">chevron_left</i>',
                    next: '<i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">chevron_right</i>'
                }
            },
            ajax: { url: ajaxUrl, type: 'GET', data: function (d) { $.extend(d, filterParams()); } },
            columns: columnDefs(),
            dom: "<'row mb-2 align-items-center'<'col-sm-6'l><'col-sm-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row mt-2 align-items-center'<'col-sm-5'i><'col-sm-7'p>>"
        });
        table.on('draw', syncExportButtons);
    }

    // Full rebuild for the new column set. destroy(true) is NOT used: it removes the <table>
    // element itself, and renderHeader() needs it to still be in the DOM.
    function rebuild() {
        if (table) { table.destroy(); table = null; }
        renderFilters();
        renderHeader();
        initTable();
        buildColMenu();
        syncExportButtons();
    }

    function loadCourse(formId) {
        if (!formId) {
            META = { fields: [], filterOptions: {} };
            rebuild();
            return;
        }
        $.getJSON(metaUrl, { form_id: formId })
            .done(function (meta) {
                META = { fields: meta.fields || [], filterOptions: meta.filterOptions || {} };
                rebuild();
            })
            .fail(function () {
                // Metadata is what defines the columns; without it a reload is the only
                // honest fallback, and it surfaces the real error to the user.
                window.location = ajaxUrl + '?form_id=' + encodeURIComponent(formId);
            });
    }

    // ── Course picker: populated BEFORE the table, so the first AJAX carries form_id ──
    var courseChoices = new Choices('#filter_form_id', {
        shouldSort: false, searchEnabled: true, searchResultLimit: 100,
        searchPlaceholderValue: 'Search course...', itemSelectText: '', allowHTML: false,
        placeholder: true, placeholderValue: '— Select Course —'
    });

    function populateCourses(selectId) {
        // The first entry MUST be a real empty-valued option, not just Choices' visual
        // placeholder. The underlying <select> ships with no options at all, so once Choices
        // fills it the browser auto-selects the FIRST one — Choices went on displaying
        // "— Select Course —" while $(select).val() already returned the first course's id.
        // The table then loaded that course's rows against a header built for no course:
        // one stray record under just S.No. and Username.
        var opts = [{ value: '', label: '— Select Course —', placeholder: true, selected: !selectId }]
            .concat(ALL_COURSES.map(function (c) { return { value: c.id, label: c.label }; }));

        courseChoices.clearStore();
        courseChoices.setChoices(opts, 'value', 'label', true);
        courseChoices.setChoiceByValue(selectId ? String(selectId) : '');
    }

    function syncExportButtons() {
        var id = currentFormId();
        var $x = $('#btnExportExcel'), $p = $('#btnExportPdf'), $c = $('#btnExportCsv');
        if (!id) { $x.addClass('d-none'); $p.addClass('d-none'); $c.addClass('d-none'); return; }
        var qs = $.param($.extend(filterParams(), {
            search_term: table ? (table.search() || '') : '',
            cols: visibleColumnKeys().join(',')
        }));
        $x.removeClass('d-none').attr('href', excelBase + '?' + qs);
        $c.removeClass('d-none').attr('href', csvBase + '?' + qs);
        $p.removeClass('d-none').attr('href', pdfBase + '?' + qs);
    }

    function buildColMenu() {
        var $menu = $('#ddColMenu').empty();
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
                syncExportButtons();
            });
            $li.find('label').on('click', function (e) {
                e.preventDefault();
                var $cb = $(this).closest('.form-check').find('input');
                $cb.prop('checked', !$cb.prop('checked')).trigger('change');
            });
            $menu.append($li);
        });
    }

    // Course change rebuilds in place — no page reload.
    document.getElementById('filter_form_id').addEventListener('change', function () {
        loadCourse(currentFormId());
    });

    // Delegated: the filter controls are re-created on every course switch.
    $('#ddFilterRow').on('change', '.dd-filter', function () {
        if (table) { table.ajax.reload(); }
        syncExportButtons();
    });

    $('#btnResetFilters').on('click', function () {
        $('.dd-filter').val('');
        if (table) { table.search(''); table.ajax.reload(); }
        syncExportButtons();
    });

    populateCourses(preselectId);
    rebuild();
});
</script>
@endpush

