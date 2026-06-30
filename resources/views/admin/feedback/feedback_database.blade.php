@php
    $fr = $fr ?? $feedbackReportRoutes ?? \App\Support\FeedbackReportRouteRegistry::forRequest();
@endphp
@extends('admin.layouts.master')

@section('title', 'Feedback Database - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<style>
#courseSelect + .choices .choices__inner,
#facultyFilter + .choices .choices__inner {
    min-height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 2.25rem 0.375rem 0.75rem;
    font-size: 0.85rem;
    border: 1px solid #d0d7de;
    border-radius: var(--bs-border-radius, 0.375rem);
    background-color: #fff;
    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
}
#courseSelect + .choices .choices__inner:focus-within,
#facultyFilter + .choices .choices__inner:focus-within {
    border-color: #0b4f8a;
    box-shadow: 0 0 0 0.2rem rgba(11,79,138,.12);
}
#courseSelect + .choices .choices__input,
#facultyFilter + .choices .choices__input {
    font-size: 0.85rem;
}
</style>
@endpush

@section('setup_content')
    <style>
        :root {
            --fb-primary: #0b4f8a;
            --fb-primary-light: #eef4fb;
            --fb-border: #d0d7de;
        }

        .filter-card {
            border: 0;
            border-radius: var(--bs-border-radius-lg);
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            overflow: visible;
        }

        .filter-card .choices__list--dropdown,
        .filter-card .choices__list[aria-expanded] {
            z-index: 1050 !important;
        }

        .filter-card .card-header {
            background: var(--fb-primary);
            color: #fff;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.7rem 1rem;
            border: 0;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .filter-card .card-body {
            padding: 1.1rem 1rem;
        }

        .filter-card .form-label {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--bs-secondary-color);
            margin-bottom: 0.25rem;
        }

        .filter-card .form-select,
        .filter-card .form-control {
            font-size: 0.85rem;
            border-color: var(--fb-border);
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        .filter-card .form-select:focus,
        .filter-card .form-control:focus {
            border-color: var(--fb-primary);
            box-shadow: 0 0 0 0.2rem rgba(11,79,138,.12);
        }

        .content-card {
            border: 0;
            border-radius: var(--bs-border-radius-lg);
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            overflow: hidden;
        }

        .content-card .card-header {
            background: var(--fb-primary-light);
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.7rem 1rem;
            border-bottom: 1px solid rgba(11,79,138,.1);
        }

        #feedbackDatabaseTable {
            font-size: 0.85rem;
        }

        #feedbackDatabaseTable thead th {
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--bs-secondary-color);
            border-bottom: 2px solid rgba(11,79,138,.15);
            padding: 0.65rem 0.75rem;
            white-space: nowrap;
            vertical-align: middle;
        }

        #feedbackDatabaseTable tbody td {
            padding: 0.65rem 0.75rem;
            vertical-align: middle;
            border-color: var(--bs-border-color-translucent);
        }

        #feedbackDatabaseTable tbody tr:hover {
            background-color: rgba(11,79,138,.03) !important;
        }

        .percentage-badge {
            display: inline-block;
            min-width: 3.6rem;
            padding: 0.25em 0.55em;
            border-radius: var(--bs-border-radius-pill);
            font-size: 0.8rem;
            font-weight: 700;
            text-align: center;
        }
        .percentage-excellent { background: rgba(25,135,84,.1); color: #146c43; }
        .percentage-good      { background: rgba(180,83,9,.1);  color: #92400e; }
        .percentage-average   { background: rgba(220,53,69,.1);  color: #b02a37; }

        /* DataTables overrides */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            font-size: 0.85rem;
            border: 1px solid var(--fb-border);
            border-radius: 0.375rem;
            padding: 0.3rem 0.6rem;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--fb-primary);
            box-shadow: 0 0 0 0.2rem rgba(11,79,138,.12);
            outline: none;
        }
        .dataTables_wrapper .dataTables_info {
            font-size: 0.82rem;
            color: var(--bs-secondary-color);
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            font-size: 0.82rem !important;
            border-radius: 0.375rem !important;
            margin: 0 0.1rem !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--fb-primary) !important;
            border-color: var(--fb-primary) !important;
            color: #fff !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
            background: var(--fb-primary-light) !important;
            color: var(--fb-primary) !important;
            border-color: var(--fb-border) !important;
        }
        .dt-buttons .btn { font-size: 0.8rem; }
    </style>

    <div class="container-fluid py-3 feedback-database-page">
        <x-breadcrum title="Feedback Database"></x-breadcrum>

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- ── TOP FILTER BAR ── --}}
        <div class="card filter-card mb-3">
            <div class="card-header">
                <i class="fas fa-sliders-h"></i> Filters
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    @php
                        $courseType = $courseType ?? 'current';
                    @endphp
                    {{-- Active / Archived --}}
                    <div class="col-12">
                        <label class="form-label">Course list</label>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="course_type" value="current"
                                       id="fdbCourseCurrent" {{ $courseType === 'current' ? 'checked' : '' }}>
                                <label class="form-check-label" for="fdbCourseCurrent">Active (current) courses</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="course_type" value="archived"
                                       id="fdbCourseArchived" {{ $courseType === 'archived' ? 'checked' : '' }}>
                                <label class="form-check-label" for="fdbCourseArchived">Archived courses</label>
                            </div>
                        </div>
                    </div>

                    {{-- Program Name --}}
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">Program Name</label>
                        <select id="courseSelect" name="course_id">
                            <option value="">All Programs</option>
                            @if (isset($courses) && $courses->count() > 0)
                                @foreach ($courses as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- Faculty Filter --}}
                    <div class="col-lg-3 col-md-4" id="facultyFilterContainer">
                        <label class="form-label">Faculty</label>
                        <select id="facultyFilter" name="faculty_id">
                            <option value="">All Faculties</option>
                        </select>
                    </div>

                    {{-- Topic Filter --}}
                    <div class="col-lg-3 col-md-4" id="topicFilterContainer">
                        <label class="form-label">Topic</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="topicFilter" name="topic_value"
                                placeholder="Type topic name...">
                            <button class="btn btn-outline-secondary btn-sm" type="button" id="clearTopicBtn" title="Clear topic">
                                <i class="material-icons menu-icon material-symbols-rounded">close</i>
                            </button>
                        </div>
                    </div>

                    {{-- Clear Filters --}}
                    <div class="col-lg-3 col-md-4">
                        <button type="button" class="btn btn-outline-secondary w-100" id="clearFiltersBtn">
                            <i class="bi bi-x-circle me-1"></i> Clear Filters
                        </button>
                    </div>
                </div>

                {{-- Conditional Filter Row --}}
                <div class="row g-3 align-items-end mt-1" id="conditionalFilterContainer">
                    <div class="col-lg-2 col-md-3">
                        <label class="form-label">Score Filter</label>
                        <select class="form-select" id="conditionalField">
                            <option value="">None</option>
                            <option value="content">Content</option>
                            <option value="presentation">Presentation</option>
                            <option value="average">Average</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-2">
                        <label class="form-label">Operator</label>
                        <select class="form-select" id="conditionalOperator">
                            <option value=">=">≥ (>=)</option>
                            <option value="<=">≤ (<=)</option>
                            <option value=">">＞ (>)</option>
                            <option value="<">＜ (<)</option>
                            <option value="=">=</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label class="form-label">Value (%)</label>
                        <input type="number" class="form-control" id="conditionalValue" placeholder="0-100" min="0" max="100" step="0.01">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── CONTENT TABLE CARD ── --}}
        <div class="card content-card">
            <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <span class="d-flex align-items-center gap-2">
                    <i class="fas fa-database text-primary"></i>
                    Faculty Feedback Database
                </span>
                <div class="d-flex flex-wrap gap-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary rounded-1 px-3 d-inline-flex align-items-center gap-1 dropdown-toggle"
                                type="button" id="colVisBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Toggle columns">
                            <span class="material-symbols-rounded" style="font-size: 1rem;">view_column</span>
                            <span>Columns</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" id="colVisMenu" style="min-width:180px;"></ul>
                    </div>
                    <button type="button" id="feedbackDbPrintBtn"
                            class="btn btn-sm btn-outline-primary rounded-1 px-3 d-inline-flex align-items-center gap-1"
                            title="Print report">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">print</span>
                        <span>Print</span>
                    </button>
                    <a href="#" id="feedbackDbPdfLink" target="_blank" rel="noopener"
                       class="btn btn-sm btn-outline-danger rounded-1 px-3 d-inline-flex align-items-center gap-1"
                       title="Download PDF">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">picture_as_pdf</span>
                        <span>PDF</span>
                    </a>
                    <a href="#" id="feedbackDbExcelLink"
                       class="btn btn-sm btn-success rounded-1 px-3 d-inline-flex align-items-center gap-1 shadow-sm"
                       title="Export to Excel">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">table_view</span>
                        <span>Excel</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3">
                {{ $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100'], true) }}
            </div>
        </div>
    </div>

    {{-- Comments Modal --}}
    <div class="modal fade" id="commentsModal" tabindex="-1" aria-labelledby="commentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background: var(--fb-primary); color: #fff;">
                    <h6 class="modal-title mb-0" id="commentsModalLabel"><i class="fas fa-comments me-2"></i>Feedback Comments</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="commentsContent"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
{{ $dataTable->scripts() }}
<script>
    const FEEDBACK_DB_COURSES_URL = @json($fr['database_courses']);
    const FEEDBACK_DB_FACULTIES_URL = @json($fr['database_faculties']);
    const FEEDBACK_DB_EXPORT_ROUTES = {
        print: @json($fr['database_print']),
        pdf: @json($fr['database_export_pdf']),
        excel: @json($fr['database_export_excel']),
    };

    $(document).ready(function() {
        let debounceTimer;
        let courseType = @json($courseType ?? 'current');

        // Get the DataTable instance
        const dtTable = window.LaravelDataTables['feedbackDatabaseTable'];

        // ── Choices.js helpers ──
        function makeChoicesConfig(placeholder) {
            return {
                shouldSort: false,
                searchEnabled: true,
                searchResultLimit: 100,
                searchPlaceholderValue: placeholder,
                itemSelectText: '',
                allowHTML: false,
                classNames: {
                    containerInner: ['choices__inner', 'shadow-sm'],
                    input: ['choices__input', 'form-control', 'form-control-sm', 'border-0', 'shadow-none', 'my-1'],
                    inputCloned: ['choices__input--cloned'],
                    listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
                    item: ['choices__item', 'dropdown-item', 'rounded-0'],
                    itemSelectable: ['choices__item--selectable'],
                    itemDisabled: ['choices__item--disabled', 'disabled'],
                    itemChoice: ['choices__item--choice'],
                    placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
                    highlightedState: ['is-highlighted', 'active'],
                    notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small', 'py-2']
                }
            };
        }

        function initCourseChoices() {
            const el = document.getElementById('courseSelect');
            if (!el || typeof window.Choices === 'undefined') return;
            if (el._choicesInstance) { el._choicesInstance.destroy(); el._choicesInstance = null; }
            el._choicesInstance = new Choices(el, makeChoicesConfig('Search programs...'));
            el.addEventListener('change', function() {
                loadFacultyForCourse(el.value);
                dtTable.draw();
            });
        }

        function initFacultyChoices() {
            const el = document.getElementById('facultyFilter');
            if (!el || typeof window.Choices === 'undefined') return;
            if (el._choicesInstance) { el._choicesInstance.destroy(); el._choicesInstance = null; }
            el._choicesInstance = new Choices(el, makeChoicesConfig('Search faculty...'));
            el.addEventListener('change', function() {
                dtTable.draw();
            });
        }

        function loadFacultyForCourse(courseId) {
            let url = FEEDBACK_DB_FACULTIES_URL + '?';
            if (courseId) {
                url += 'course_id=' + encodeURIComponent(courseId);
            }
            const previousFacultyId = $('#facultyFilter').val();
            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                const el = document.getElementById('facultyFilter');
                if (!el) return;
                if (el._choicesInstance) { el._choicesInstance.destroy(); el._choicesInstance = null; }
                el.innerHTML = '<option value="">All Faculties</option>';
                let previousExists = false;
                if (data.success && data.faculties.length > 0) {
                    data.faculties.forEach(function(f) {
                        const opt = document.createElement('option');
                        opt.value = f.pk;
                        opt.textContent = f.full_name;
                        if (String(f.pk) === String(previousFacultyId)) previousExists = true;
                        el.appendChild(opt);
                    });
                }
                if (previousFacultyId && previousExists) {
                    el.value = previousFacultyId;
                }
                initFacultyChoices();
                if (previousFacultyId && previousExists && el._choicesInstance) {
                    el._choicesInstance.setChoiceByValue(String(previousFacultyId));
                }
            })
            .catch(err => console.error('loadFacultyForCourse error:', err));
        }

        // ── Topic filter with debounce ──
        $('#topicFilter').on('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const val = $(this).val();
                if (val.length >= 2 || val.length === 0) {
                    dtTable.draw();
                }
            }, 500);
        });

        $('#clearTopicBtn').on('click', function() {
            $('#topicFilter').val('');
            dtTable.draw();
        });

        // ── Conditional filter ──
        $('#conditionalField').on('change', function() {
            dtTable.draw();
        });
        $('#conditionalOperator').on('change', function() {
            if ($('#conditionalField').val() && $('#conditionalValue').val()) {
                dtTable.draw();
            }
        });
        $('#conditionalValue').on('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if ($('#conditionalField').val()) {
                    dtTable.draw();
                }
            }, 500);
        });

        // ── Clear all ──
        $('#clearFiltersBtn').on('click', function() {
            const cEl = document.getElementById('courseSelect');
            if (cEl._choicesInstance) { cEl._choicesInstance.setChoiceByValue(''); } else { $('#courseSelect').val(''); }
            const fEl = document.getElementById('facultyFilter');
            if (fEl && fEl._choicesInstance) { fEl._choicesInstance.setChoiceByValue(''); } else { $('#facultyFilter').val(''); }
            $('#topicFilter').val('');
            $('#conditionalField').val('');
            $('#conditionalOperator').val('>=');
            $('#conditionalValue').val('');
            dtTable.draw();
        });

        // ── Course type switch ──
        $('input[name="course_type"]').on('change', function() {
            courseType = $(this).val();
            const url = FEEDBACK_DB_COURSES_URL + '?course_type=' + encodeURIComponent(courseType);
            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const rawSel = document.getElementById('courseSelect');
                if (rawSel._choicesInstance) { rawSel._choicesInstance.destroy(); rawSel._choicesInstance = null; }
                rawSel.innerHTML = '<option value="">All Programs</option>';
                if (data.courses && data.courses.length > 0) {
                    data.courses.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.pk;
                        opt.textContent = c.course_name || '';
                        rawSel.appendChild(opt);
                    });
                }
                initCourseChoices();
                loadFacultyForCourse('');
                dtTable.draw();
                try {
                    const u = new URL(window.location.href);
                    u.searchParams.set('course_type', courseType);
                    window.history.replaceState({}, '', u.toString());
                } catch(e) {}
            })
            .catch(err => console.error(err));
        });

        // ── Comments modal handler (delegated) ──
        $(document).on('click', '.view-comments-btn', function(e) {
            e.preventDefault();
            const comments = $(this).data('comments');
            if (!comments) return;
            const modalEl = document.getElementById('commentsModal');
            $('#commentsContent').html(
                '<div style="max-height:400px;overflow-y:auto;">' +
                String(comments).split(' | ').map((c, i) =>
                    '<div class="d-flex gap-2 align-items-start border-bottom pb-2 mb-2">' +
                        '<span class="badge bg-primary bg-opacity-10 text-primary mt-1" style="min-width:22px;">' + (i+1) + '</span>' +
                        '<p class="mb-0 text-body-secondary" style="font-size:0.88rem;">' + $('<span>').text(c).html() + '</p>' +
                    '</div>'
                ).join('') + '</div>'
            );
            new bootstrap.Modal(modalEl).show();
        });

        // ── Export links ──
        function buildExportQuery() {
            const params = new URLSearchParams();
            const courseId = $('#courseSelect').val();
            const facultyId = $('#facultyFilter').val();
            const topicVal = $('#topicFilter').val();
            const condField = $('#conditionalField').val();
            const condOp = $('#conditionalOperator').val();
            const condVal = $('#conditionalValue').val();
            if (courseId) params.set('course_id', courseId);
            if (facultyId) params.set('faculty_id', facultyId);
            if (topicVal) params.set('topic_value', topicVal);
            if (condField) params.set('cond_field', condField);
            if (condOp) params.set('cond_operator', condOp);
            if (condVal) params.set('cond_value', condVal);

            // Pass DataTable global search term
            const searchVal = dtTable.search();
            if (searchVal) params.set('search_term', searchVal);

            // Pass visible columns
            const visibleCols = [];
            dtTable.columns().every(function(index) {
                if (this.visible()) {
                    const title = $(this.header()).text().trim();
                    if (title) visibleCols.push(index);
                }
            });
            params.set('visible_columns', visibleCols.join(','));

            return params.toString();
        }

        function syncExportLinks() {
            const q = buildExportQuery();
            const qs = q ? '?' + q : '';
            $('#feedbackDbPdfLink').attr('href', FEEDBACK_DB_EXPORT_ROUTES.pdf + qs);
            $('#feedbackDbExcelLink').attr('href', FEEDBACK_DB_EXPORT_ROUTES.excel + qs);
        }

        $('#feedbackDbPrintBtn').on('click', function(e) {
            e.preventDefault();
            const q = buildExportQuery();
            window.open(FEEDBACK_DB_EXPORT_ROUTES.print + (q ? '?' + q : ''), '_blank', 'noopener');
        });

        // Sync export links on any filter change
        dtTable.on('draw', function() {
            syncExportLinks();
        });

        // ── Column visibility ──
        function buildColVisMenu() {
            const menu = $('#colVisMenu');
            menu.empty();
            dtTable.columns().every(function(index) {
                const col = this;
                const title = $(col.header()).text().trim();
                if (!title || index === 0) return; // skip S.No.
                const visible = col.visible();
                menu.append(
                    '<li><a class="dropdown-item d-flex align-items-center gap-2 colvis-item" href="javascript:void(0)" data-col="' + index + '">' +
                    '<input type="checkbox" class="form-check-input m-0" ' + (visible ? 'checked' : '') + '> ' +
                    '<span>' + title + '</span></a></li>'
                );
            });
        }

        $(document).on('click', '.colvis-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const colIdx = $(this).data('col');
            const col = dtTable.column(colIdx);
            const newState = !col.visible();
            col.visible(newState);
            $(this).find('input[type="checkbox"]').prop('checked', newState);
            syncExportLinks();
        });

        // Prevent checkbox click from bubbling (avoid double toggle)
        $(document).on('click', '.colvis-item input[type="checkbox"]', function(e) {
            e.stopPropagation();
            const item = $(this).closest('.colvis-item');
            const colIdx = item.data('col');
            const col = dtTable.column(colIdx);
            const newState = !col.visible();
            col.visible(newState);
            $(this).prop('checked', newState);
            syncExportLinks();
        });

        // Build menu after table init
        dtTable.on('init', function() {
            buildColVisMenu();
        });

        // ── Initialize ──
        initCourseChoices();
        initFacultyChoices();
        loadFacultyForCourse('');
        syncExportLinks();
    });
</script>
@endsection
