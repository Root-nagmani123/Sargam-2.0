@extends('admin.layouts.master')
@section('title', 'Document Verification Report')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<style>
    #dvColMenu { max-height: 320px; overflow-y: auto; min-width: 220px; }
    #dvColMenu .form-check-label { cursor: pointer; }
    #documentVerificationTable_wrapper .dataTables_filter { text-align: right; }
    #documentVerificationTable_wrapper .dataTables_filter input { width: auto; min-width: 220px; }
    .dv-course-picker .choices-wrap { flex: 0 1 380px; max-width: 380px; min-width: 0; }
    .dv-course-picker .choices { margin-bottom: 0; width: 100%; }
    .dv-course-picker .choices__inner {
        min-height: 31px; padding: 4px 26px 4px 8px; font-size: .875rem;
        background: #fff; border: 1px solid #dee2e6; border-radius: .25rem; line-height: 1.4;
    }
    .dv-course-picker .choices__list--single { padding: 0; }
    .dv-course-picker .choices[data-type*="select-one"]::after { right: 10px; }
    .choices__list--dropdown .choices__item { font-size: .875rem; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid px-3">

    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-file-earmark-check me-2"></i>Document Verification Report
            </h4>
            <small class="text-muted">
                Verify trainees' uploaded joining documents — course wise. Open a student to mark documents verified.
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <div class="dropdown" id="dvColDropdown">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-1"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Show / hide columns">
                    <i class="bi bi-layout-three-columns"></i> Columns
                </button>
                <ul class="dropdown-menu dropdown-menu-end py-2" id="dvColMenu"></ul>
            </div>
            <a href="{{ route('admin.reports.documents') }}"
               id="btnDocChecklist"
               class="btn btn-sm btn-outline-primary {{ $form ? '' : 'd-none' }}"
               title="Open the course-wide Document Checklist matrix">
                <i class="bi bi-grid-3x3-gap me-1"></i>Checklist Matrix
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
                    <div class="d-flex align-items-center gap-2 dv-course-picker">
                        <div class="choices-wrap">
                            <select id="filter_form_id" class="form-select form-select-sm"></select>
                        </div>
                        <button type="button" id="btnResetFilters" class="btn btn-outline-secondary btn-sm px-3 flex-shrink-0">
                            <i class="bi bi-x-lg me-1"></i>Reset
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1">Select a course, then use the Search box above the table to find a student.</small>
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
    var ajaxUrl      = "{{ route('admin.reports.document-verification') }}";
    var checklistUrl = "{{ route('admin.reports.documents') }}";
    var ALL_COURSES  = @json(collect($forms)->map(fn ($f) => ['id' => (string) $f->id, 'label' => ($f->course_name ?: $f->form_name)])->values());
    var preselectId  = "{{ (int) request('form_id') ?: '' }}";

    function currentFormId() { return $('#filter_form_id').val() || ''; }

    var table = $('#documentVerificationTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ordering: true,
        order: [],
        autoWidth: false,
        responsive: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        language: {
            processing: 'Loading…',
            search: '',
            searchPlaceholder: 'Search',
            lengthMenu: 'Showing _MENU_',
            info: 'of _TOTAL_ items',
            infoEmpty: 'of 0 items',
            infoFiltered: 'of _MAX_ items',
            emptyTable: 'No students found.',
            zeroRecords: 'No students found.',
            paginate: {
                previous: '<i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">chevron_left</i>',
                next: '<i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">chevron_right</i>'
            }
        },
        ajax: {
            url: ajaxUrl,
            type: 'GET',
            data: function (d) {
                d.form_id = currentFormId();
            }
        },
        columns: [
            { data: 'DT_RowIndex',    name: 'DT_RowIndex',    orderable: false, searchable: false, className: 'text-center', width: '50px' },
            { data: 'login_username', name: 'login_username', orderable: false },
            { data: 'full_name',      name: 'full_name' },
            { data: 'service_code',   name: 'service_code',   searchable: false },
            { data: 'allotted_state', name: 'allotted_state', searchable: false },
            { data: 'mobile_no',      name: 'mobile_no' },
            { data: 'action',         name: 'action',         orderable: false, searchable: false, className: 'text-center' }
        ],
        dom: "<'row mb-2 align-items-center'<'col-sm-6'l><'col-sm-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row mt-2 align-items-center'<'col-sm-5'i><'col-sm-7'p>>"
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

    // Keep the "Checklist Matrix" link in sync with the active course.
    function syncChecklistButton() {
        var id = currentFormId();
        var $btn = $('#btnDocChecklist');
        if (!id) { $btn.addClass('d-none'); return; }
        $btn.removeClass('d-none')
            .attr('href', checklistUrl + '?form_id=' + encodeURIComponent(id));
    }

    // Custom "Columns" show/hide menu.
    function buildColMenu() {
        var $menu = $('#dvColMenu').empty();
        table.columns().every(function () {
            var col   = this;
            var title = ($(col.header()).text() || '').trim();
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

    document.getElementById('filter_form_id').addEventListener('change', function () {
        syncChecklistButton();
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function () {
        populateCourses('');
        table.search('');
        syncChecklistButton();
        table.ajax.reload();
    });

    table.on('draw', function () { syncChecklistButton(); });

    populateCourses(preselectId);
    buildColMenu();
    syncChecklistButton();
});
</script>
@endpush
