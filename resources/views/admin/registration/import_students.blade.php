@extends('admin.layouts.master')

@section('title', 'Migrate FC Students')

@push('styles')
    <style>
        #fc-migrate-page .enrollment-dt-wrap .dataTables_filter {
            text-align: right;
        }

        #fc-migrate-page .enrollment-dt-wrap .dataTables_filter label {
            font-weight: 600;
        }

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
                    <div class="col-md-8">
                        <h4 class="mb-1">Migrate FC Students</h4>
                        <p class="mb-0 text-muted small">
                            View all Excel-imported roster rows, or switch to <strong>Ready to migrate</strong> to promote eligible trainees
                            into <strong>student_master</strong> and <strong>user_credentials</strong>.
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
                            <span class="text-muted small ms-2" id="migrateTabCounts">
                                Eligible: <strong id="migrateRecordCount">0</strong>
                                · Selected: <strong id="migrateSelectedCount">0</strong>
                            </span>
                            <span class="text-muted small ms-2 d-none" id="importedTabCounts">
                                Imported: <strong id="importedRecordCount">0</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="fcMigrateTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-imported" data-bs-toggle="tab"
                            data-bs-target="#pane-imported" type="button" role="tab"
                            aria-controls="pane-imported" aria-selected="true">
                            All imported records
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-migrate" data-bs-toggle="tab"
                            data-bs-target="#pane-migrate" type="button" role="tab"
                            aria-controls="pane-migrate" aria-selected="false">
                            Ready to migrate
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="fcMigrateTabContent">
                    <div class="tab-pane fade show active" id="pane-imported" role="tabpanel"
                        aria-labelledby="tab-imported" tabindex="0">
                        <p class="text-muted small mb-3">
                            Every row in <strong>fc_registration_master</strong> from Excel import.
                            Status shows progress: imported → credentials → forms complete → ready to migrate → migrated.
                        </p>
                        <div class="table-responsive enrollment-dt-wrap">
                            {!! $importedDataTable->table(['class' => 'table table-striped table-hover table-sm align-middle w-100']) !!}
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pane-migrate" role="tabpanel"
                        aria-labelledby="tab-migrate" tabindex="0">
                        <form method="POST" action="{{ route('admin.migrate.fc') }}" id="migrateForm">
                            @csrf
                            <input type="hidden" name="selected_pks" id="selectedPksInput" value="">

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <h5 class="mb-0">
                                    Eligible records (<span id="migrateRecordCountHeader">0</span>)
                                </h5>
                                <button type="submit" class="btn btn-primary btn-sm" id="migrateSubmitBtn">
                                    <i class="bi bi-database-up"></i> Migrate selected
                                </button>
                            </div>

                            <p class="text-muted small mb-3">
                                <strong>is_registered = 1</strong>, credentials staged, not yet in <strong>user_credentials</strong>.
                            </p>

                            <div class="table-responsive enrollment-dt-wrap">
                                {!! $dataTable->table(['class' => 'table table-striped table-hover table-sm align-middle w-100']) !!}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {!! $importedDataTable->scripts() !!}
    {!! $dataTable->scripts() !!}
    <script>
        $(function() {
            var migrateRoot = document.getElementById('fc-migrate-page');
            if (migrateRoot && typeof window.initChoicesBootstrap5In === 'function') {
                window.initChoicesBootstrap5In(migrateRoot);
            }

            var migrateTable = window.LaravelDataTables && window.LaravelDataTables['fcMigrateStudentsTable'];
            var importedTable = window.LaravelDataTables && window.LaravelDataTables['fcImportedRosterTable'];
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

            function syncImportedRecordCount() {
                if (!importedTable) {
                    return;
                }
                var info = importedTable.page.info();
                $('#importedRecordCount').text(info.recordsTotal || 0);
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

            function reloadTables() {
                if (importedTable) {
                    importedTable.ajax.reload(null, false);
                }
                migrateTable.ajax.reload(null, false);
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
                    $('#migrateTabCounts').toggleClass('d-none', !isMigrate);
                    $('#importedTabCounts').toggleClass('d-none', isMigrate);
                    if (!isMigrate && importedTable) {
                        importedTable.columns.adjust();
                    } else if (isMigrate) {
                        migrateTable.columns.adjust();
                    }
                });
            });

            if (importedTable) {
                importedTable.on('init.dt draw.dt', function() {
                    requestAnimationFrame(syncImportedRecordCount);
                });
            }

            migrateTable.on('init.dt draw.dt', function() {
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

            requestAnimationFrame(function() {
                syncImportedRecordCount();
                syncMigrateCheckboxState();
            });
        });
    </script>
@endpush
