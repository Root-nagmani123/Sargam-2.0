@extends('admin.layouts.master')

@section('title', 'Registration List')

@section('setup_content')
    <style>
        .highlight-row td {
            background-color: #ffe6e6 !important;
        }
        #fcRegColToggleMenu .form-check-label {
            cursor: pointer;
        }
        #fcRegColToggleMenu {
            max-height: 320px;
            overflow-y: auto;
            min-width: 220px;
        }
        #fcregistrationmasterlistdatable-table .dtr-control,
        #fcregistrationmasterlistdatable-table th.dtr-control,
        #fcregistrationmasterlistdatable-table td.dtr-control,
        #fcregistrationmasterlistdatable-table tr.child {
            display: none !important;
        }
        .fc-reg-panel {
            border: 1px solid rgba(0, 74, 147, 0.12);
            border-radius: 10px;
            background: #fafbfd;
            padding: 1rem 1.15rem 1.1rem;
        }
        .fc-reg-panel__title {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #004a93;
            margin-bottom: 0.85rem;
        }
        .fc-reg-panel .form-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.35rem;
        }
        .fc-reg-tools-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem 0;
        }
        @media (min-width: 768px) {
            .fc-reg-tools-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0;
                align-items: stretch;
            }
            .fc-reg-tools-col {
                padding: 0 1.1rem;
                border-right: 1px solid rgba(0, 74, 147, 0.1);
            }
            .fc-reg-tools-col:first-child {
                padding-left: 0;
            }
            .fc-reg-tools-col:last-child {
                padding-right: 0;
                border-right: none;
            }
        }
        .fc-reg-tool-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            height: 100%;
        }
        .fc-reg-tool-group__label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            line-height: 1.2;
            min-height: 1.2rem;
        }
        .fc-reg-tool-body {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            min-height: 2.125rem;
            margin-top: auto;
        }
        .fc-reg-tool-body .form-control,
        .fc-reg-tool-body .form-select {
            flex: 1 1 auto;
            min-width: 0;
        }
        .fc-reg-tool-body .btn {
            flex-shrink: 0;
            white-space: nowrap;
        }
        .fc-reg-tool-body--nowrap {
            flex-wrap: nowrap;
        }
        .fc-reg-panel__divider {
            border: 0;
            border-top: 1px solid rgba(0, 74, 147, 0.1);
            margin: 1rem 0;
            opacity: 1;
        }
        .fc-reg-actions-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
        @media (min-width: 576px) {
            .fc-reg-actions-row {
                grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
                gap: 0.65rem;
            }
        }
        .fc-reg-actions-row .btn {
            min-height: 2.125rem;
        }
        .fc-reg-course-tabs .btn.active {
            box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 123, 255, 0.25);
        }
    </style>
    <div class="container-fluid">

        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                {{-- Filters --}}
                <div class="fc-reg-panel mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div class="fc-reg-panel__title mb-0">Filter records</div>
                        <div class="btn-group shadow-sm rounded-1 fc-reg-course-tabs" role="group" aria-label="Programme status">
                            <button type="button" class="btn btn-success btn-sm px-3 fw-semibold active"
                                id="fcRegFilterActive" aria-pressed="true">
                                <i class="bi bi-check-circle me-1"></i> Active
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold"
                                id="fcRegFilterArchive" aria-pressed="false">
                                <i class="bi bi-archive me-1"></i> Archived
                            </button>
                        </div>
                    </div>
                    <form id="registrationFilterForm">
                        <input type="hidden" id="course_status_filter" value="active">
                        <div class="row g-3 align-items-end">
                            <div class="col-sm-6 col-lg-3">
                                <label for="course_name" class="form-label">Programme / Course</label>
                                <select id="course_name" class="form-select form-select-sm">
                                    <option value="">-- All Courses --</option>
                                    @foreach ($courses as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="exemption_category" class="form-label">Exemption Category</label>
                                <select id="exemption_category" class="form-select form-select-sm">
                                    <option value="">-- All Categories --</option>
                                    @foreach ($exemptionCategories as $id => $name)
                                        <option value="{{ $name }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="application_type" class="form-label">Application Type</label>
                                <select id="application_type" class="form-select form-select-sm">
                                    <option value="">-- All Types --</option>
                                    @foreach ($applicationTypes as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="service_master" class="form-label">Service</label>
                                <select id="service_master" class="form-select form-select-sm">
                                    <option value="">-- All Services --</option>
                                    @foreach ($serviceMasters as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="year" class="form-label">Year</label>
                                <select id="year" class="form-select form-select-sm">
                                    <option value="">-- All Years --</option>
                                    @foreach ($years as $key => $year)
                                        <option value="{{ $key }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label for="group_type" class="form-label">Service Type</label>
                                <select id="group_type" class="form-select form-select-sm">
                                    <option value="">-- All Groups --</option>
                                    <option value="A" {{ request('group_type') == 'A' ? 'selected' : '' }}>A</option>
                                    <option value="B" {{ request('group_type') == 'B' ? 'selected' : '' }}>B</option>
                                    <option value="NULL" {{ request('group_type') == 'NULL' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3 d-grid">
                                <button type="button" id="resetFilters" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Import, templates, export & list actions --}}
                <div class="fc-reg-panel mb-3">
                    <div class="fc-reg-panel__title">Import, templates &amp; export</div>
                    <div class="fc-reg-tools-grid">
                        <div class="fc-reg-tools-col">
                            <div class="fc-reg-tool-group">
                                <span class="fc-reg-tool-group__label">Bulk import</span>
                                <div class="fc-reg-tool-body">
                                    <a href="{{ route('admin.registration.import.form') }}" class="btn btn-secondary btn-sm">
                                        <i class="bi bi-upload me-1"></i> Bulk Upload
                                    </a>
                                    <button type="button" class="btn btn-outline-success btn-sm"
                                        onclick="window.location='{{ route('fc.download.fctemplate') }}'">
                                        <i class="bi bi-download me-1"></i> Template 1
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm"
                                        onclick="window.location='{{ route('fc.download.template') }}'">
                                        <i class="bi bi-download me-1"></i> Template 2
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="fc-reg-tools-col">
                            <div class="fc-reg-tool-group">
                                <span class="fc-reg-tool-group__label">Preview upload</span>
                                <form action="{{ route('fc.preview.upload') }}" method="POST" enctype="multipart/form-data"
                                    class="fc-reg-tool-body fc-reg-tool-body--nowrap w-100">
                                    @csrf
                                    <input type="file" name="file" class="form-control form-control-sm"
                                        accept=".xlsx,.xls,.csv" required>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i> Preview
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="fc-reg-tools-col">
                            <div class="fc-reg-tool-group">
                                <span class="fc-reg-tool-group__label">Export filtered list</span>
                                <form id="exportForm" action="{{ route('admin.registration.export') }}" method="GET"
                                    class="fc-reg-tool-body fc-reg-tool-body--nowrap w-100">
                                    <input type="hidden" name="course_name" id="export_course_name">
                                    <input type="hidden" name="exemption_category" id="export_exemption_category">
                                    <input type="hidden" name="application_type" id="export_application_type">
                                    <input type="hidden" name="service_master" id="export_service_master">
                                    <input type="hidden" name="year" id="export_year">
                                    <input type="hidden" name="group_type" id="export_group_type">
                                    <input type="hidden" name="show_duplicates" id="export_show_duplicates">
                                    <select name="format" id="format" class="form-select form-select-sm" style="max-width: 6.5rem;">
                                        <option value="">Format</option>
                                        <option value="xlsx">Excel</option>
                                        <option value="csv">CSV</option>
                                        <option value="pdf">PDF</option>
                                    </select>
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-box-arrow-up me-1"></i> Export
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <hr class="fc-reg-panel__divider">

                    <div class="fc-reg-actions-row">
                        <form id="bulkDeactivateForm" action="{{ route('admin.registration.deactivate.filtered') }}"
                            method="POST" class="mb-0">
                            @csrf
                            <input type="hidden" name="group_type" id="deactivate_group_type">
                            <button type="submit" class="btn btn-danger btn-sm w-100" id="deactivateButton" disabled>
                                <i class="bi bi-slash-circle me-1"></i> Deactivate Service Type Records
                            </button>
                        </form>
                        <button type="button" id="showDuplicatesBtn" class="btn btn-warning btn-sm w-100">
                            <i class="bi bi-files me-1"></i> Show Duplicates
                        </button>
                    </div>
                </div>

                {{-- Columns (moved beside Search after DataTable init) --}}
                <div class="d-flex justify-content-end mb-2" id="fcRegColToolbarSlot">
                    <div class="dropdown" data-bs-auto-close="outside" id="fcRegColDropdown">
                        <button type="button"
                            class="btn btn-outline-secondary btn-sm dropdown-toggle d-inline-flex align-items-center gap-1"
                            data-bs-toggle="dropdown" aria-expanded="false" title="Show / hide columns">
                            <i class="bi bi-layout-three-columns"></i> Columns
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end py-2" id="fcRegColToggleMenu"></ul>
                    </div>
                </div>

                <div class="table-responsive">
                    {{ $dataTable->table(['class' => 'table table-hover table-bordered text-nowrap align-middle mb-0 dt-legacy-layout', 'id' => 'fcregistrationmasterlistdatable-table']) }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>window.fcRegMasterListColVisInit = function() {};</script>
    {{ $dataTable->scripts() }}

    <script>
        (function() {
            var showDuplicates = false;
            var colStorageKey = 'fcRegistrationMasterList:columns:v2';
            var $tableEl = $('#fcregistrationmasterlistdatable-table');
            var dt = null;
            var colToolbarMoved = false;

            function getDt() {
                if (!dt && $.fn.DataTable.isDataTable($tableEl)) {
                    dt = $tableEl.DataTable();
                }
                if (!dt && window.LaravelDataTables) {
                    dt = window.LaravelDataTables['fcregistrationmasterlistdatable-table'] || null;
                }
                return dt;
            }

            function isColToggleable(col) {
                var header = ($(col.header()).text() || '').trim();
                if (!header) {
                    return false;
                }
                return col.dataSrc() !== 'email_count';
            }

            function adjustScroll() {
                var api = getDt();
                if (api) {
                    api.columns.adjust();
                }
            }

            function buildColMenu() {
                var api = getDt();
                if (!api) {
                    return;
                }
                var $menu = $('#fcRegColToggleMenu').empty();
                api.columns().every(function(i) {
                    var col = this;
                    if (!isColToggleable(col)) {
                        return;
                    }
                    var header = ($(col.header()).text() || '').trim();
                    var $li = $('<li class="px-3 py-1"><div class="form-check mb-0">' +
                        '<input type="checkbox" class="form-check-input me-2 fc-reg-col-cb" data-col="' + i + '">' +
                        '<label class="form-check-label">' + header + '</label></div></li>');
                    $li.find('input').prop('checked', col.visible()).on('change', function(e) {
                        e.stopPropagation();
                        var apiRef = getDt();
                        if (!apiRef) {
                            return;
                        }
                        var idx = $(this).data('col');
                        apiRef.column(idx).visible($(this).prop('checked'));
                        adjustScroll();
                        persistCols();
                    });
                    $li.find('label').on('click', function(e) {
                        e.preventDefault();
                        var $cb = $(this).closest('.form-check').find('input');
                        $cb.prop('checked', !$cb.prop('checked')).trigger('change');
                    });
                    $menu.append($li);
                });
            }

            function persistCols() {
                var api = getDt();
                if (!api) {
                    return;
                }
                var state = {};
                api.columns().every(function(i) {
                    state[i] = this.visible();
                });
                try {
                    localStorage.setItem(colStorageKey, JSON.stringify(state));
                } catch (e) {}
            }

            function restoreCols() {
                var api = getDt();
                if (!api) {
                    return;
                }
                var raw;
                try {
                    raw = localStorage.getItem(colStorageKey);
                } catch (e) {
                    return;
                }
                if (!raw) {
                    return;
                }
                var state;
                try {
                    state = JSON.parse(raw);
                } catch (e) {
                    return;
                }
                if (!state || typeof state !== 'object') {
                    return;
                }
                Object.keys(state).forEach(function(k) {
                    var idx = parseInt(k, 10);
                    if (!isNaN(idx)) {
                        api.column(idx).visible(!!state[k], false);
                    }
                });
                api.columns.adjust();
            }

            function mountColToolbar() {
                if (colToolbarMoved) {
                    return;
                }
                var $dropdown = $('#fcRegColDropdown');
                var $filter = $tableEl.closest('.dataTables_wrapper').find('.dataTables_filter');
                if ($dropdown.length && $filter.length) {
                    $filter.addClass('d-flex align-items-center justify-content-end flex-wrap gap-2');
                    $filter.append($dropdown);
                    $('#fcRegColToolbarSlot').addClass('d-none');
                    colToolbarMoved = true;
                }
            }

            function highlightDuplicates() {
                var api = getDt();
                if (!api) {
                    return;
                }
                api.rows({ page: 'current' }).every(function() {
                    var data = this.data();
                    var $row = $(this.node());
                    var emailCount = parseInt(data.email_count || 0, 10);
                    if (emailCount > 1 && data.email && String(data.email).trim() !== '') {
                        $row.addClass('highlight-row');
                    } else {
                        $row.removeClass('highlight-row');
                    }
                });
            }

            function syncGroupType() {
                $('#deactivate_group_type').val($('#group_type').val());
            }

            function toggleDeactivateButton() {
                var groupVal = $('#group_type').val();
                $('#deactivate_group_type').val(groupVal);
                $('#deactivateButton').prop('disabled', groupVal !== 'B');
            }

            window.fcRegMasterListColVisInit = function() {
                restoreCols();
                buildColMenu();
                mountColToolbar();
                adjustScroll();
            };

            var courseStatusFilter = 'active';

            function setFcRegCourseTab($btn) {
                var $group = $btn.closest('.btn-group');
                $group.find('.btn').removeClass('btn-success active').addClass('btn-outline-secondary');
                $btn.removeClass('btn-outline-secondary').addClass('btn-success active');
                $group.find('.btn').attr('aria-pressed', 'false');
                $btn.attr('aria-pressed', 'true');
            }

            function loadProgrammeCourses(status, reloadTable) {
                courseStatusFilter = status;
                $('#course_status_filter').val(status);
                $.get('{{ route('programme.get.courses.by.status') }}', { status: status }, function(res) {
                    if (!res.success) {
                        return;
                    }
                    var $sel = $('#course_name');
                    $sel.find('option:not(:first)').remove();
                    $.each(res.courses, function(pk, name) {
                        $sel.append($('<option>', { value: pk, text: name }));
                    });
                    $sel.val('');
                    if (reloadTable !== false) {
                        var api = getDt();
                        if (api) {
                            api.ajax.reload();
                        }
                    }
                });
            }

            $(document).ready(function() {
                dt = getDt();

                $('#fcRegFilterActive').on('click', function() {
                    setFcRegCourseTab($(this));
                    loadProgrammeCourses('active');
                });

                $('#fcRegFilterArchive').on('click', function() {
                    setFcRegCourseTab($(this));
                    loadProgrammeCourses('archive');
                });

                $('#showDuplicatesBtn').on('click', function() {
                    showDuplicates = !showDuplicates;
                    $(this).toggleClass('btn-warning btn-success')
                        .text(showDuplicates ? 'Show All' : 'Show Duplicates');
                    var api = getDt();
                    if (api) {
                        api.ajax.reload();
                    }
                });

                $('#course_name, #exemption_category, #application_type, #service_master, #year, #group_type')
                    .on('change', function() {
                        var api = getDt();
                        if (api) {
                            api.ajax.reload();
                        }
                        if (this.id === 'group_type') {
                            syncGroupType();
                            toggleDeactivateButton();
                        }
                    });

                $('#resetFilters').on('click', function() {
                    $('#registrationFilterForm select:not(#course_status_filter)').val('');
                    setFcRegCourseTab($('#fcRegFilterActive'));
                    loadProgrammeCourses('active');
                    syncGroupType();
                    toggleDeactivateButton();
                });

                $tableEl.on('preXhr.dt', function(e, settings, data) {
                    data.show_duplicates = showDuplicates ? 1 : 0;
                    data.course_name = $('#course_name').val();
                    data.course_status_filter = courseStatusFilter;
                    data.exemption_category = $('#exemption_category').val();
                    data.application_type = $('#application_type').val();
                    data.service_master = $('#service_master').val();
                    data.year = $('#year').val();
                    data.group_type = $('#group_type').val();
                });

                $tableEl.on('draw.dt', function() {
                    highlightDuplicates();
                    buildColMenu();
                    mountColToolbar();
                    adjustScroll();
                });

                if (getDt()) {
                    window.fcRegMasterListColVisInit();
                } else {
                    var tries = 0;
                    var timer = setInterval(function() {
                        tries++;
                        if (getDt() || tries > 50) {
                            clearInterval(timer);
                            if (getDt()) {
                                window.fcRegMasterListColVisInit();
                            }
                        }
                    }, 100);
                }

                syncGroupType();
                toggleDeactivateButton();

                $('#exportForm').on('submit', function() {
                    $('#export_course_name').val($('#course_name').val());
                    $('#export_exemption_category').val($('#exemption_category').val());
                    $('#export_application_type').val($('#application_type').val());
                    $('#export_service_master').val($('#service_master').val());
                    $('#export_year').val($('#year').val());
                    $('#export_group_type').val($('#group_type').val());
                    $(this).find('input[name="show_duplicates"], input[name="course_status_filter"]').remove();
                    $('<input>', { type: 'hidden', name: 'course_status_filter', value: courseStatusFilter }).appendTo(this);
                    $('<input>', {
                        type: 'hidden',
                        name: 'show_duplicates',
                        value: showDuplicates ? 1 : 0
                    }).appendTo(this);
                });

                $('#format').on('change', function() {
                    $('#exportForm button[type="submit"]').prop('disabled', !$(this).val());
                }).trigger('change');
            });
        })();
    </script>
@endpush
