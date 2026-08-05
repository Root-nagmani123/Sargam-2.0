@extends('admin.layouts.master')

@section('title', 'Migrate FC Students')

@push('styles')
    <style>
        #fc-migrate-page thead th {
            white-space: nowrap;
            vertical-align: middle;
        }

        #fc-migrate-page #fcMigrateStudentsTable thead tr:first-child th {
            vertical-align: middle;
        }

        #fc-migrate-page .migrate-select-th,
        #fc-migrate-page .migrate-select-td {
            width: 48px;
            min-width: 48px;
            max-width: 48px;
            text-align: center !important;
            vertical-align: middle !important;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        #fc-migrate-page .migrate-select-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 1.25rem;
        }

        #fc-migrate-page .migrate-select-cell .form-check-input {
            margin: 0;
            float: none;
            cursor: pointer;
        }

        /* White checkbox on dark blue table header (#004a93) */
        #fc-migrate-page #fcMigrateStudentsTable thead .migrate-select-th .migrate-header-checkbox,
        #fc-migrate-page #fcMigrateStudentsTable thead .migrate-select-th .js-migrate-select-all {
            width: 1.125rem;
            height: 1.125rem;
            min-width: 1.125rem;
            min-height: 1.125rem;
            margin: 0;
            cursor: pointer;
            background-color: #ffffff !important;
            border: 2px solid #ffffff !important;
            box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.35);
            accent-color: #004a93;
            --bs-form-check-bg: #ffffff;
        }

        #fc-migrate-page #fcMigrateStudentsTable thead .migrate-select-th .migrate-header-checkbox:checked,
        #fc-migrate-page #fcMigrateStudentsTable thead .migrate-select-th .js-migrate-select-all:checked {
            background-color: #ffffff !important;
            border-color: #ffffff !important;
            --bs-form-check-bg-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23004a93' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10l3 3l6-6'/%3e%3c/svg%3e");
        }

        #fc-migrate-page #fcMigrateStudentsTable thead .migrate-select-th.sorting,
        #fc-migrate-page #fcMigrateStudentsTable thead .migrate-select-th.sorting_asc,
        #fc-migrate-page #fcMigrateStudentsTable thead .migrate-select-th.sorting_desc {
            background-image: none !important;
            cursor: default;
            padding-right: 0.5rem !important;
        }

        #fc-migrate-page #fcMigrateStudentsTable tbody .migrate-select-td .form-check-input {
            accent-color: #004a93;
        }

        #fc-migrate-page #filter_course + .choices,
        #fc-migrate-page #filter_services + .choices {
            max-width: 100%;
        }

        #fc-migrate-page #filter_course + .choices .choices__inner,
        #fc-migrate-page #filter_services + .choices .choices__inner {
            min-height: calc(1.5em + 0.75rem + 2px);
        }

        #fc-migrate-page #filter_services + .choices .choices__list--multiple .choices__item {
            margin-bottom: 0;
            padding: 0.125rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Export toolbar — same placement as FC status grid (datatable-tools) */
        #fc-migrate-page .fc-dt-toolbar-wrap,
        #fc-migrate-page .fc-migrate-export-toolbar {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        #fc-migrate-page .dataTables_wrapper .dataTables_filter {
            text-align: right;
        }

        #fc-migrate-page .dataTables_wrapper .dataTables_filter label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 0;
            font-weight: 600;
        }

        #fc-migrate-page .dataTables_wrapper .dataTables_filter {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 8px;
        }

        #fc-migrate-page .dataTables_wrapper .dataTables_filter .fc-dt-toolbar-wrap {
            display: inline-flex;
        }

        #fc-migrate-page .dataTables_wrapper .dataTables_filter input {
            height: 32px;
            margin-left: 0 !important;
        }

        #fc-migrate-page .fc-dt-toolbar-wrap .btn {
            border-radius: 6px !important;
            font-size: 12px;
            padding: 6px 10px;
            line-height: 1.2;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        #fc-migrate-page .fc-dt-toolbar-wrap .btn .material-icons {
            font-size: 16px;
        }

        #fc-migrate-page .fc-dt-toolbar-wrap .btn-group .btn {
            border-radius: 0 !important;
        }

        #fc-migrate-page .fc-dt-toolbar-wrap .btn-group .btn:first-child {
            border-top-left-radius: 6px !important;
            border-bottom-left-radius: 6px !important;
        }

        #fc-migrate-page .fc-dt-toolbar-wrap .btn-group .btn:last-child {
            border-top-right-radius: 6px !important;
            border-bottom-right-radius: 6px !important;
        }

        /* Keep inactive tab hidden if DataTables redraw disturbs Bootstrap .show */
        #fc-migrate-page #fcMigrateTabContent > .tab-pane:not(.show) {
            display: none !important;
        }

        #fc-migrate-page #fcMigrateTabs .nav-link {
            color: #555;
            border-radius: 6px 6px 0 0;
        }

        #fc-migrate-page #fcMigrateTabs .nav-link.active {
            color: #004a93;
            font-weight: 600;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
    </style>
@endpush

@section('setup_content')
    @include('admin.partials.choices-bootstrap5')
    <div id="fc-migrate-page" class="container-fluid choices-bs-scope">
        <x-breadcrum title="Migrate FC Students" />

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @elseif(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <h4 class="mb-1">Migrate FC Students</h4>
                        <p class="mb-0 text-muted small">
                            <strong>Migrated</strong> lists roster rows already in <strong>user_credentials</strong>.
                            <strong>Ready to migrate</strong> promotes eligible trainees into <strong>student_master</strong> and <strong>user_credentials</strong>.
                            <strong>Print</strong>, <strong>PDF</strong>, and <strong>Excel</strong> are next to the table search (same as activity status grid).
                        </p>
                    </div>
                </div>
                <hr class="mb-0">

                <div class="pt-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="filter_course" class="form-label">Course</label>
                            <select id="filter_course" class="form-select" data-placeholder="All courses" data-search="true">
                                <option value="">All courses</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->pk }}"
                                        data-short="{{ e($course->couse_short_name ?? '') }}">
                                        {{ $course->course_name }}@if ($course->couse_short_name) ({{ $course->couse_short_name }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="filter_services" class="form-label">Service</label>
                            <select id="filter_services" class="form-select" name="filter_services[]" multiple
                                data-placeholder="All services" data-search="true">
                                @foreach ($services as $service)
                                    <option value="{{ $service->pk }}"
                                        data-short="{{ e($service->service_short_name ?? '') }}">
                                        {{ $service->service_name }}@if ($service->service_short_name) ({{ $service->service_short_name }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="filter_search" class="form-label">Quick search</label>
                            <input type="text" id="filter_search" class="form-control"
                                placeholder="Username, email, mobile, OT code, course/service short name…">
                        </div>

                        <div class="col-md-12 d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" id="filterBtn" class="btn btn-primary btn-sm">
                                <i class="fas fa-filter"></i> Apply filters
                            </button>
                            <button type="button" id="resetFilterBtn" class="btn btn-outline-secondary btn-sm">
                                Reset
                            </button>
                            <span class="text-muted small ms-2 d-none" id="migrateTabCounts">
                                Eligible: <strong id="migrateRecordCount">0</strong>
                                · Selected: <strong id="migrateSelectedCount">0</strong>
                            </span>
                            <span class="text-muted small ms-2" id="migratedTabCounts">
                                Migrated: <strong id="migratedRecordCount">0</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="fcMigrateTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-migrated" data-bs-toggle="tab"
                            data-bs-target="#pane-migrated" type="button" role="tab"
                            aria-controls="pane-migrated" aria-selected="false">
                            Migrated
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-migrate" data-bs-toggle="tab"
                            data-bs-target="#pane-migrate" type="button" role="tab"
                            aria-controls="pane-migrate" aria-selected="true">
                            Ready to migrate
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="fcMigrateTabContent">
                    <div class="tab-pane fade" id="pane-migrated" role="tabpanel"
                        aria-labelledby="tab-migrated" tabindex="0">
                        <p class="text-muted small mb-3">
                            Roster rows already linked to <strong>user_credentials</strong>
                            (match on username, mobile, or email). These trainees have been migrated.
                        </p>
                        <div class="d-none fc-export-toolbar-source" id="fcExportBarMigrated" aria-hidden="true">
                            @include('admin.registration.partials.migrate-export-toolbar')
                        </div>
                        <div class="table-responsive enrollment-dt-wrap">
                            {!! $migratedDataTable->table(['class' => 'table table-striped table-hover table-sm align-middle w-100 dt-legacy-layout', 'data-sargam-dt-ui' => 'false']) !!}
                        </div>
                    </div>

                    <div class="tab-pane fade show active" id="pane-migrate" role="tabpanel"
                        aria-labelledby="tab-migrate" tabindex="0">
                        <form method="POST" action="{{ route('admin.migrate.fc') }}" id="migrateForm">
                            @csrf
                            <input type="hidden" name="selected_pks" id="selectedPksInput" value="">

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <h5 class="mb-0">
                                    Eligible records (<span id="migrateRecordCountHeader">0</span>)
                                </h5>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <div class="d-none fc-export-toolbar-source" id="fcExportBarEligible" aria-hidden="true">
                                        @include('admin.registration.partials.migrate-export-toolbar')
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm" id="migrateSubmitBtn">
                                        <i class="bi bi-database-up"></i> Migrate selected
                                    </button>
                                </div>
                            </div>

                            <p class="text-muted small mb-3">
                                <strong>is_registered = 1</strong>, credentials staged, and no row in <strong>user_credentials</strong>
                                with the same <strong>username</strong>, <strong>mobile</strong>, or <strong>email</strong> as this roster row.
                            </p>

                            <div class="table-responsive enrollment-dt-wrap">
                                {!! $dataTable->table(['class' => 'table table-striped table-hover table-sm align-middle w-100 dt-legacy-layout', 'data-sargam-dt-ui' => 'false']) !!}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {!! $migratedDataTable->scripts() !!}
    {!! $dataTable->scripts() !!}
    <script>
        $(function() {
            var tabCountsUrl = @json(route('students.tab.counts'));
            var eligibleTableBootstrapped = false;

            var exportBases = {
                migrated: {
                    print: @json(route('students.export.print', ['list' => 'migrated'])),
                    pdf: @json(route('students.export.pdf', ['list' => 'migrated'])),
                    excel: @json(route('students.export.excel', ['list' => 'migrated']))
                },
                eligible: {
                    print: @json(route('students.export.print', ['list' => 'eligible'])),
                    pdf: @json(route('students.export.pdf', ['list' => 'eligible'])),
                    excel: @json(route('students.export.excel', ['list' => 'eligible']))
                }
            };

            function activeExportList() {
                return document.getElementById('tab-migrate')?.classList.contains('active') ? 'eligible' : 'migrated';
            }

            function filterCourseValue() {
                var el = document.getElementById('filter_course');
                if (!el) {
                    return '';
                }
                if (el._choicesBs && typeof el._choicesBs.getValue === 'function') {
                    // getValue(true) returns the raw value string for single-select (not {value, label})
                    var picked = el._choicesBs.getValue(true);
                    if (picked !== null && picked !== undefined && picked !== '') {
                        return String(picked);
                    }
                }
                return String($(el).val() || '');
            }

            function migrateExportQueryString() {
                var p = new URLSearchParams();
                var c = filterCourseValue();
                var s = $('#filter_services').val();
                var q = ($('#filter_search').val() || '').trim();
                if (c) {
                    p.set('course_filter', c);
                }
                if (s && s.length) {
                    if (Array.isArray(s)) {
                        s.forEach(function (id) { p.append('filter_services[]', id); });
                    } else {
                        p.set('filter_services', s);
                    }
                }
                if (q) {
                    p.set('filter_search', q);
                }
                return p.toString();
            }

            function refreshMigrateExportLinks() {
                var list = activeExportList();
                var bases = exportBases[list];
                var suffix = migrateExportQueryString();
                suffix = suffix ? ('?' + suffix) : '';
                $('.js-migrate-export-print').attr('href', bases.print + suffix);
                $('.js-migrate-export-pdf').attr('href', bases.pdf + suffix);
                $('.js-migrate-export-excel').attr('href', bases.excel + suffix);
            }

            function activeTableId() {
                return activeExportList() === 'eligible' ? 'fcMigrateStudentsTable' : 'fcMigratedRosterTable';
            }

            function isMigrateTabActive() {
                return document.getElementById('tab-migrate')?.classList.contains('active') === true;
            }

            function setFcMigrateActiveTab(which) {
                var eligibleOn = which === 'eligible';
                var $tabMigrated = $('#tab-migrated');
                var $tabMigrate = $('#tab-migrate');
                var $paneMigrated = $('#pane-migrated');
                var $paneMigrate = $('#pane-migrate');

                $tabMigrated.toggleClass('active', !eligibleOn).attr('aria-selected', !eligibleOn ? 'true' : 'false');
                $tabMigrate.toggleClass('active', eligibleOn).attr('aria-selected', eligibleOn ? 'true' : 'false');

                if (eligibleOn) {
                    $paneMigrated.removeClass('show active');
                    $paneMigrate.addClass('show active');
                } else {
                    $paneMigrate.removeClass('show active');
                    $paneMigrated.addClass('show active');
                }

                // Hard-hide the inactive list's whole DataTables wrapper (table + info +
                // pagination). Bootstrap's .show toggle on the panes is not enough here:
                // a DataTables redraw can disturb it, leaving the other list's pagination
                // (e.g. the 473-row Migrated pager) rendered under the active list.
                $('#fcMigratedRosterTable').closest('.dataTables_wrapper').toggle(!eligibleOn);
                $('#fcMigrateStudentsTable').closest('.dataTables_wrapper').toggle(eligibleOn);
            }

            function enforceFcMigrateTabs() {
                setFcMigrateActiveTab(isMigrateTabActive() ? 'eligible' : 'migrated');
            }

            var initialMigrateTab = @json(
                session('success')
                    ? 'migrated'
                    : (request()->query('tab') === 'migrated' ? 'migrated' : 'eligible')
            );
            setFcMigrateActiveTab(initialMigrateTab);

            function ensureDataTableInPane(tableId, paneId) {
                var $pane = document.getElementById(paneId);
                if (!$pane) {
                    return;
                }
                var $wrap = $($pane).find('.enrollment-dt-wrap').first();
                var $table = $('#' + tableId);
                if (!$wrap.length || !$table.length) {
                    return;
                }
                var $wrapper = $table.closest('.dataTables_wrapper');
                if ($wrapper.length && $wrapper.closest($pane).length === 0) {
                    $wrap.append($wrapper);
                }
            }

            function mountMigrateExportToolbar(retries) {
                retries = retries || 0;
                var tableId = activeTableId();
                var isMigrate = activeExportList() === 'eligible';
                var $source = isMigrate ? $('#fcExportBarEligible .fc-migrate-export-toolbar').first() : $('#fcExportBarMigrated .fc-migrate-export-toolbar').first();
                var $filter = $('#' + tableId).closest('.dataTables_wrapper').find('.dataTables_filter');
                if (!$filter.length) {
                    if (retries < 15) {
                        setTimeout(function () { mountMigrateExportToolbar(retries + 1); }, 120);
                    }
                    return;
                }
                $filter.find('.fc-dt-toolbar-clone').remove();
                if ($source.length) {
                    var $clone = $source.clone();
                    $clone.addClass('fc-dt-toolbar-clone').removeClass('fc-migrate-export-toolbar');
                    $filter.append($clone);
                }
                refreshMigrateExportLinks();
            }

            refreshMigrateExportLinks();
            $('#filter_course, #filter_services').on('change', refreshMigrateExportLinks);
            $('#filter_search').on('input', refreshMigrateExportLinks);

            var migrateRoot = document.getElementById('fc-migrate-page');
            if (migrateRoot && typeof window.initChoicesBootstrap5In === 'function') {
                window.initChoicesBootstrap5In(migrateRoot);
            }

            var migrateTable = window.LaravelDataTables && window.LaravelDataTables['fcMigrateStudentsTable'];
            var migratedTable = window.LaravelDataTables && window.LaravelDataTables['fcMigratedRosterTable'];
            if (!migrateTable) {
                return;
            }

            function $migrateWrapper() {
                return $('#fcMigrateStudentsTable').closest('.dataTables_wrapper');
            }

            function $migrateRowChecks() {
                return $('#fcMigrateStudentsTable tbody .migrate-row-checkbox');
            }

            function $migrateHeaderChecks() {
                return $migrateWrapper().find('thead .js-migrate-select-all');
            }

            function syncFilterCountVisibility() {
                var migrateOn = isMigrateTabActive();
                $('#migrateTabCounts').toggleClass('d-none', !migrateOn);
                $('#migratedTabCounts').toggleClass('d-none', migrateOn);
            }

            function syncMigratedRecordCount() {
                if (!migratedTable) {
                    return;
                }
                var info = migratedTable.page.info();
                $('#migratedRecordCount').text(info.recordsTotal || 0);
            }

            function syncMigrateCheckboxState() {
                var info = migrateTable.page.info();
                $('#migrateRecordCount, #migrateRecordCountHeader').text(info.recordsTotal || 0);

                var $rows = $migrateRowChecks();
                var total = $rows.length;
                var selected = $rows.filter(':checked').length;
                var allChecked = total > 0 && selected === total;

                $('#migrateSelectedCount').text(selected);
                $migrateHeaderChecks().each(function() {
                    this.checked = allChecked;
                    this.indeterminate = selected > 0 && selected < total;
                });
            }

            function setAllMigrateRowsChecked(checked) {
                $migrateRowChecks().prop('checked', !!checked);
                syncMigrateCheckboxState();
            }

            function refreshTabCounts() {
                var qs = migrateExportQueryString();
                var url = tabCountsUrl + (qs ? ('?' + qs) : '');
                return $.getJSON(url).done(function (data) {
                    if (data && typeof data.migrated !== 'undefined') {
                        $('#migratedRecordCount').text(data.migrated);
                    }
                    if (data && typeof data.eligible !== 'undefined') {
                        $('#migrateRecordCount, #migrateRecordCountHeader').text(data.eligible);
                    }
                });
            }

            function bootstrapEligibleTableIfNeeded(done) {
                if (eligibleTableBootstrapped) {
                    if (typeof done === 'function') {
                        done();
                    }
                    return;
                }
                // resetPaging = true: the eligible table carries deferLoading:1, so its
                // pre-ajax paging state is a placeholder. Its first real load must land
                // on page 1 with pagination rebuilt from the true recordsTotal, otherwise
                // a stale/placeholder page count survives the first draw.
                migrateTable.ajax.reload(function () {
                    eligibleTableBootstrapped = true;
                    syncMigrateCheckboxState();
                    if (typeof done === 'function') {
                        done();
                    }
                }, true);
            }

            function reloadTables() {
                var migrateActive = isMigrateTabActive();
                refreshTabCounts();

                function afterReload() {
                    enforceFcMigrateTabs();
                    syncFilterCountVisibility();
                    if (migrateActive) {
                        ensureDataTableInPane('fcMigrateStudentsTable', 'pane-migrate');
                        migrateTable.columns.adjust();
                        mountMigrateExportToolbar(0);
                    } else if (migratedTable) {
                        ensureDataTableInPane('fcMigratedRosterTable', 'pane-migrated');
                        migratedTable.columns.adjust();
                        mountMigrateExportToolbar(0);
                    }
                }

                if (migrateActive) {
                    if (eligibleTableBootstrapped) {
                        // resetPaging = true: a filter/search/reset must jump back to
                        // page 1, otherwise a stale page (e.g. page 17) survives when
                        // the result set shrinks to a single page.
                        migrateTable.ajax.reload(function () {
                            syncMigrateCheckboxState();
                            afterReload();
                        }, true);
                    } else {
                        bootstrapEligibleTableIfNeeded(afterReload);
                    }
                    return;
                }

                if (migratedTable) {
                    migratedTable.ajax.reload(function () {
                        syncMigratedRecordCount();
                        afterReload();
                    }, true);
                }
            }

            $('#filterBtn').on('click', function() {
                reloadTables();
            });

            $('#resetFilterBtn').on('click', function() {
                $('#filter_course').val('');
                $('#filter_search').val('');
                var courseEl = document.getElementById('filter_course');
                var servicesEl = document.getElementById('filter_services');
                if (servicesEl) {
                    $('#filter_services').val([]);
                    if (typeof window.reinitChoicesBootstrap5 === 'function') {
                        window.reinitChoicesBootstrap5(servicesEl);
                    } else if (servicesEl._choicesBs && typeof servicesEl._choicesBs.removeActiveItems === 'function') {
                        servicesEl._choicesBs.removeActiveItems();
                    }
                }
                if (courseEl && typeof window.reinitChoicesBootstrap5 === 'function') {
                    window.reinitChoicesBootstrap5(courseEl);
                }
                reloadTables();
            });

            $('#filter_search').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    reloadTables();
                }
            });

            document.querySelectorAll('#fcMigrateTabs button[data-bs-toggle="tab"]').forEach(function(btn) {
                btn.addEventListener('shown.bs.tab', function(e) {
                    var isMigrate = e.target && e.target.id === 'tab-migrate';
                    setFcMigrateActiveTab(isMigrate ? 'eligible' : 'migrated');
                    syncFilterCountVisibility();
                    refreshMigrateExportLinks();
                    if (!isMigrate && migratedTable) {
                        ensureDataTableInPane('fcMigratedRosterTable', 'pane-migrated');
                        migratedTable.columns.adjust();
                        mountMigrateExportToolbar(0);
                    } else if (isMigrate) {
                        bootstrapEligibleTableIfNeeded(function () {
                            ensureDataTableInPane('fcMigrateStudentsTable', 'pane-migrate');
                            migrateTable.columns.adjust();
                            mountMigrateExportToolbar(0);
                        });
                    }
                });
            });

            if (migratedTable) {
                migratedTable.on('init.dt', function () {
                    ensureDataTableInPane('fcMigratedRosterTable', 'pane-migrated');
                    if (!isMigrateTabActive()) {
                        mountMigrateExportToolbar(0);
                    }
                });
                migratedTable.on('draw.dt', function() {
                    requestAnimationFrame(syncMigratedRecordCount);
                });
            }

            migrateTable.on('draw.dt', function() {
                requestAnimationFrame(syncMigrateCheckboxState);
            });

            $migrateWrapper().on('change', 'thead .js-migrate-select-all', function() {
                setAllMigrateRowsChecked(this.checked);
            });

            $('#fcMigrateStudentsTable').on('change', 'tbody .migrate-row-checkbox', function() {
                syncMigrateCheckboxState();
            });

            $('#migrateForm').on('submit', function(e) {
                var pks = [];
                $('#fcMigrateStudentsTable tbody .migrate-row-checkbox:checked').each(function() {
                    pks.push($(this).val());
                });

                if (!pks.length) {
                    e.preventDefault();
                    alert('Please select at least one eligible record to migrate.');
                    return false;
                }

                if (!confirm('Migrate ' + pks.length + ' selected record(s)?')) {
                    e.preventDefault();
                    return false;
                }

                $('#selectedPksInput').val(pks.join(','));
            });

            syncFilterCountVisibility();
            refreshTabCounts();

            requestAnimationFrame(function() {
                setFcMigrateActiveTab(initialMigrateTab);
                mountMigrateExportToolbar(0);
                if (migratedTable) {
                    syncMigratedRecordCount();
                }
            });
        });
    </script>
@endpush
