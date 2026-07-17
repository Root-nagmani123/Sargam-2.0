@extends('admin.layouts.master')
@section('title', 'Health Risk Factors Report')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<style>
    #hrColMenu { max-height: 320px; overflow-y: auto; min-width: 220px; }
    #hrColMenu .form-check-label { cursor: pointer; }
    #healthRiskReportTable_wrapper .dataTables_filter { text-align: right; }
    #healthRiskReportTable_wrapper .dataTables_filter input { width: auto; min-width: 220px; }
    .hr-course-picker .choices-wrap { flex: 0 1 380px; max-width: 380px; min-width: 0; }
    .hr-course-picker .choices { margin-bottom: 0; width: 100%; }
    .hr-course-picker .choices__inner {
        min-height: 31px; padding: 4px 26px 4px 8px; font-size: .875rem;
        background: #fff; border: 1px solid #dee2e6; border-radius: .25rem; line-height: 1.4;
    }
    .hr-course-picker .choices__list--single { padding: 0; }
    .hr-course-picker .choices[data-type*="select-one"]::after { right: 10px; }
    .choices__list--dropdown .choices__item { font-size: .875rem; }
</style>
@endpush

@section('setup_content')
@php $healthCols = \App\DataTables\FC\FcHealthRiskReportDataTable::HEALTH_COLUMNS; @endphp
<div class="container-fluid px-3">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-heart-pulse me-2"></i>Health Risk Factors Report
            </h4>
            <small class="text-muted">Health vulnerabilities / risk factors submitted by trainees — course wise.</small>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <div class="dropdown" id="hrColDropdown">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-1"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Show / hide columns">
                    <i class="bi bi-layout-three-columns"></i> Columns
                </button>
                <ul class="dropdown-menu dropdown-menu-end py-2" id="hrColMenu"></ul>
            </div>
            <button type="button" id="hrPrintBtn" class="btn btn-sm btn-outline-dark" title="Print report">
                <i class="bi bi-printer me-1"></i>Print
            </button>
            <a href="#" id="hrPdfLink" target="_blank" rel="noopener" class="btn btn-sm btn-danger" title="Export to PDF">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="#" id="hrExcelLink" class="btn btn-sm btn-success" title="Export to Excel">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
        </div>
    </div>

    <x-session_message />

    {{-- Course filter --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:8px;">
        <div class="card-body py-3 px-3">
            <div class="row g-2">
                <div class="col-12 col-md-8 col-lg-6">
                    <label for="filter_form_id" class="form-label small mb-1">Course <span class="text-danger">*</span></label>
                    <div class="d-flex align-items-center gap-2 hr-course-picker">
                        <div class="choices-wrap">
                            <select id="filter_form_id" class="form-select form-select-sm"></select>
                        </div>
                        <button type="button" id="btnResetFilters" class="btn btn-outline-secondary btn-sm px-3 flex-shrink-0">
                            <i class="bi bi-x-lg me-1"></i>Reset
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1">Choose Active / Archived, pick a course, then use the Search box above the table.</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
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
    var ajaxUrl = "{{ route('admin.reports.health-risk') }}";
    var ALL_COURSES = @json(collect($forms)->map(fn ($f) => ['id' => (string) $f->id, 'label' => ($f->course_name ?: $f->form_name)])->values());
    var preselectId = "{{ (int) request('form_id') ?: '' }}";
    function currentFormId() { return $('#filter_form_id').val() || ''; }

    var table = $('#healthRiskReportTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ordering: true,
        order: [],
        autoWidth: false,
        responsive: false,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        language: {
            processing: 'Loading…',
            search: '', searchPlaceholder: 'Search',
            lengthMenu: 'Show _MENU_',
            info: 'Showing _START_–_END_ of _TOTAL_ students',
            infoEmpty: 'Showing 0 of 0 students',
            infoFiltered: '',
            emptyTable: 'No students found.', zeroRecords: 'No students found.',
            paginate: { previous: '‹', next: '›' }
        },
        ajax: { url: ajaxUrl, type: 'GET', data: function (d) { d.form_id = currentFormId(); } },
        columns: [
            { data: 'DT_RowIndex',    name: 'DT_RowIndex',    orderable: false, searchable: false, className: 'text-center', width: '50px' },
            { data: 'login_username', name: 'login_username', orderable: false },
            { data: 'full_name',      name: 'full_name' },
            { data: 'service_code',   name: 'service_code',   searchable: false },
@foreach($healthCols as $col => $title)
            { data: '{{ $col }}', name: '{{ $col }}', orderable: false, searchable: false },
@endforeach
        ],
        dom: "<'row mb-2 align-items-center'<'col-sm-6'l><'col-sm-6'f>>rt<'row mt-2 align-items-center'<'col-sm-5'i><'col-sm-7'p>>"
    });

    // ── Choices.js searchable course dropdown ──
    var courseChoices = new Choices('#filter_form_id', {
        shouldSort: false, searchEnabled: true, searchResultLimit: 100,
        searchPlaceholderValue: 'Search course...', itemSelectText: '', allowHTML: false,
        placeholder: true, placeholderValue: '— Select Course —'
    });

    function populateCourses(selectId) {
        var opts = ALL_COURSES.map(function (c) { return { value: c.id, label: c.label }; });
        courseChoices.clearStore();
        courseChoices.setChoices(opts, 'value', 'label', true);
        if (selectId) { courseChoices.setChoiceByValue(String(selectId)); }
    }

    function buildColMenu() {
        var $menu = $('#hrColMenu').empty();
        table.columns().every(function () {
            var col = this, title = ($(col.header()).text() || '').trim();
            if (!title || title.toLowerCase() === 's.no.') { return; }
            var $li = $('<li class="px-3 py-1"><div class="form-check mb-0">' +
                '<input type="checkbox" class="form-check-input me-2"' + (col.visible() ? ' checked' : '') + '>' +
                '<label class="form-check-label">' + title + '</label></div></li>');
            $li.find('input').on('change', function () { col.visible($(this).prop('checked')); });
            $li.find('label').on('click', function (e) {
                e.preventDefault();
                var $cb = $(this).closest('.form-check').find('input');
                $cb.prop('checked', !$cb.prop('checked')).trigger('change');
            });
            $menu.append($li);
        });
    }

    // ── Exports (Print / PDF / Excel) — respect course, search and visible columns ──
    var EXPORT_ROUTES = {
        print: "{{ route('admin.reports.health-risk.print') }}",
        pdf:   "{{ route('admin.reports.health-risk.export.pdf') }}",
        excel: "{{ route('admin.reports.health-risk.export.excel') }}"
    };

    function buildExportQuery() {
        var params = new URLSearchParams();
        params.set('form_id', currentFormId());
        params.set('search', table.search() || '');
        var visible = [];
        table.columns().every(function (index) { if (this.visible()) { visible.push(index); } });
        params.set('visible_columns', visible.join(','));
        return params.toString();
    }

    function syncExportLinks() {
        var qs = '?' + buildExportQuery();
        $('#hrPdfLink').attr('href', EXPORT_ROUTES.pdf + qs);
        $('#hrExcelLink').attr('href', EXPORT_ROUTES.excel + qs);
    }

    $('#hrPrintBtn').on('click', function () {
        window.open(EXPORT_ROUTES.print + '?' + buildExportQuery(), '_blank', 'noopener');
    });

    // Keep export links current on draw / filter / column toggle.
    table.on('draw column-visibility', syncExportLinks);

    // Course selection (Choices updates the underlying <select>).
    document.getElementById('filter_form_id').addEventListener('change', function () {
        table.ajax.reload();
        syncExportLinks();
    });

    $('#btnResetFilters').on('click', function () {
        populateCourses('');
        table.search('');
        table.ajax.reload();
        syncExportLinks();
    });

    // Populate the dropdown (honour a preselected course).
    populateCourses(preselectId);

    buildColMenu();
    syncExportLinks();
});
</script>
@endpush
