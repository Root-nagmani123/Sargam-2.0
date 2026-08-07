@extends('admin.layouts.master')

@section('title', 'My Course Participant')

@push('styles')
<style>
    /* ── My Course Participant — page-scoped chrome ──────────────────────────
       Everything else (toolbar, panel, table, footer) comes from the shared
       programme-dt system in custom.css and the --ds-* tokens in sargam-app.css. */

    .cp-page .cp-main-card {
        border: 1px solid var(--ds-line);
        border-radius: var(--ds-radius-card);
        background: var(--ds-surface);
        box-shadow: var(--ds-shadow);
    }

    /* Download (right of the Active/Archived tabs) — same shape as Attendance. */
    .cp-page .cp-download-btn {
        height: var(--ds-control-h);
        display: inline-flex;
        align-items: center;
        gap: var(--ds-space-2);
        padding: 0 1.1rem;
        font-size: 0.9375rem;
        font-weight: 500;
        color: var(--ds-primary);
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        background: var(--ds-surface);
    }

    .cp-page .cp-download-btn:hover,
    .cp-page .cp-download-btn:focus,
    .cp-page .cp-download-btn.show {
        background: #f2f7fc;
        border-color: var(--ds-primary);
        color: var(--ds-primary);
    }

    .cp-page .cp-download-btn i {
        font-size: 1rem;
        line-height: 1;
    }

    /* "Total Records" chip beside the status tabs. */
    .cp-page .cp-count-chip {
        display: inline-flex;
        align-items: center;
        gap: var(--ds-space-2);
        height: var(--ds-control-h);
        padding: 0 0.875rem;
        border: 1px solid var(--ds-line);
        border-radius: 8px;
        background: var(--ds-surface);
        font-size: 0.9375rem;
        color: var(--ds-ink-muted);
        white-space: nowrap;
    }

    .cp-page .cp-count-chip strong {
        color: var(--ds-ink);
        font-weight: 600;
    }

    /* Course names are long — give that one filter more room. */
    .cp-page .cp-filter-wide {
        width: 260px;
    }

    .cp-page .programme-dt-filter-select .form-select {
        height: var(--ds-control-h);
        font-size: 0.9375rem;
        border: 1px solid #d0d5dd;
        border-radius: 8px;
    }

    .cp-page .programme-dt-filter-select .form-select:focus {
        border-color: var(--ds-primary);
        box-shadow: var(--ds-focus-ring);
    }

    /* Import modal — quiet enterprise surface, no gradients. */
    .cp-import-modal .modal-content {
        border: 0;
        border-radius: var(--ds-radius-card);
        box-shadow: var(--ds-shadow-lg);
    }

    .cp-import-modal .modal-header {
        border-bottom: 1px solid var(--ds-line);
        padding: var(--ds-space-3) var(--ds-space-4);
    }

    .cp-import-modal .modal-footer {
        border-top: 1px solid var(--ds-line);
        padding: var(--ds-space-3) var(--ds-space-4);
    }

    .cp-import-modal .cp-import-note {
        border: 1px solid var(--ds-line);
        border-radius: var(--ds-radius-card);
        background: var(--ds-surface-2);
        padding: var(--ds-space-3);
    }

    .cp-import-modal .cp-import-note table {
        margin-bottom: 0;
        font-size: 0.875rem;
    }

    .cp-import-modal .cp-import-note thead th {
        background: #f2f4f7;
        color: #475467;
        font-size: 0.8125rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .cp-import-modal .cp-import-hint {
        display: block;
        color: var(--ds-ink-muted);
        font-size: 0.8125rem;
    }

    @media (max-width: 767.98px) {
        .cp-page .cp-filter-wide,
        .cp-page .programme-dt-filter-select,
        .cp-page .cp-count-chip {
            width: 100%;
        }
    }
</style>
@endpush

@section('setup_content')
    <div class="container-fluid cp-page py-3">
        <x-breadcrum title="My Course Participant">
            <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2"
                data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload" aria-hidden="true"></i>
                <span>Import Data</span>
            </button>
        </x-breadcrum>

        <x-session_message />

        {{-- Status pills + Download — above the card (new-design page chrome) --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                @if (!empty($showFilters) && $showFilters)
                    <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group"
                        aria-label="Filter courses by status">
                        <li class="nav-item" role="presentation">
                            <button type="button"
                                class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill cp-status-pill {{ $courseStatus === 'inactive' ? '' : 'active' }}"
                                data-cp-status="active" aria-pressed="{{ $courseStatus === 'inactive' ? 'false' : 'true' }}"
                                @if ($courseStatus !== 'inactive') aria-current="true" @endif>Active</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button type="button"
                                class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill cp-status-pill {{ $courseStatus === 'inactive' ? 'active' : '' }}"
                                data-cp-status="inactive" aria-pressed="{{ $courseStatus === 'inactive' ? 'true' : 'false' }}"
                                @if ($courseStatus === 'inactive') aria-current="true" @endif>Archived</button>
                        </li>
                    </ul>
                @endif

                <span class="cp-count-chip">Total Records
                    <strong id="filteredCount">{{ $filteredCount }}</strong>
                </span>
            </div>

            <div class="dropdown">
                <button type="button" id="cpDownloadToggle" class="btn cp-download-btn dropdown-toggle border-0"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="cpDownloadToggle">
                    <li><a href="javascript:void(0)" class="dropdown-item" data-cp-format="xlsx">
                            <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)</a></li>
                    <li><a href="javascript:void(0)" class="dropdown-item" data-cp-format="pdf">
                            <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF</a></li>
                    <li><a href="javascript:void(0)" class="dropdown-item" data-cp-format="csv">
                            <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV</a></li>
                </ul>
            </div>
        </div>

        {{-- The Download dropdown posts through this form so the export carries the
             exact filters that produced the on-screen list. --}}
        <form method="GET" action="{{ route('my.course.participant.export') }}" id="exportForm" class="d-none">
            <input type="hidden" name="course_id" id="exportCourseId">
            <input type="hidden" name="status" id="exportStatus">
            <input type="hidden" name="course_status" id="exportCourseStatus">
            <input type="hidden" name="search_term" id="exportSearchTerm">
            <input type="hidden" name="format" id="exportFormat">
        </form>

        {{-- Active/Archived toggle value, read by the table + export --}}
        <input type="hidden" id="course_status" value="{{ $courseStatus === 'inactive' ? 'inactive' : 'active' }}">

        <div class="card cp-main-card border-0">
            <div class="card-body p-4">

                {{-- Filter toolbar (programme-dt design system) --}}
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        @if (!empty($showFilters) && $showFilters)
                            <span class="programme-dt-filters-label">Filters</span>

                            <div class="programme-dt-filter-select cp-filter-wide">
                                <select name="course_id" id="course_id" class="form-select" aria-label="Filter by course">
                                    <option value="">All Courses</option>
                                    @foreach ($courses as $id => $name)
                                        <option value="{{ $id }}" {{ (string) $courseId === (string) $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="programme-dt-filter-select">
                                <select name="status" id="status" class="form-select" aria-label="Filter by enrollment status">
                                    <option value="">All Status</option>
                                    <option value="1" {{ (string) $status === '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ (string) $status === '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>

                            <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">
                                Reset Filters
                            </button>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <button type="button" class="btn programme-dt-btn-columns" id="btnCpColumns"
                            data-bs-toggle="modal" data-bs-target="#cpColumnVisibilityModal"
                            title="Show / hide columns">
                            <span>Columns</span>
                            <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                        </button>

                        <div id="cpDtSearch" class="programme-dt-search" data-dt-search-for="studentsTable"></div>
                    </div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        <table id="studentsTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                            <thead>
                                <tr>
                                    <th>S. No.</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Course Code</th>
                                    <th>OT Code</th>
                                    <th>Email</th>
                                    <th>Mobile No.</th>
                                    <th>Cadre</th>
                                    <th>Participant Group</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- DataTables populates this --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Pagination + "Showing N of M items" — filled by datatable-global-ui.js --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                    data-dt-footer-for="studentsTable"></div>
            </div>
        </div>

        {{-- Column Visibility Modal --}}
        <div class="modal fade" id="cpColumnVisibilityModal" tabindex="-1" aria-labelledby="cpColumnVisibilityLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header border-0 pb-2">
                        <h5 class="modal-title fw-bold" id="cpColumnVisibilityLabel">Column Visibility</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <hr class="mt-0">
                        <div class="row g-3" id="cpColumnToggleGrid"></div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Import Modal --}}
        <div class="modal fade cp-import-modal" id="importModal" tabindex="-1" aria-labelledby="importModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="importModalLabel">Import OT Codes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('student.enrollment.import') }}" method="POST" enctype="multipart/form-data"
                        id="importForm">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="cp-import-note mb-4">
                                <h6 class="fw-semibold mb-2">Import instructions</h6>
                                <p class="text-muted small mb-3">Your Excel file should have these columns:</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Excel Column</th>
                                                <th>Description</th>
                                                <th class="text-center">Required</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>student_master_pk</code></td>
                                                <td class="text-muted">Student ID number</td>
                                                <td class="text-center"><span class="badge programme-status-badge programme-status-badge--inactive rounded-1">Required</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>course_master_pk</code></td>
                                                <td class="text-muted">Course ID number</td>
                                                <td class="text-center"><span class="badge programme-status-badge programme-status-badge--inactive rounded-1">Required</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>OT Code</code></td>
                                                <td class="text-muted">OT Code value (max 20 chars)</td>
                                                <td class="text-center"><span class="badge programme-status-badge programme-status-badge--inactive rounded-1">Required</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="small text-muted mb-0 mt-3">
                                    <strong>Tip:</strong> Export the data first, edit the OT Code column, then import the same file back.
                                </p>
                            </div>

                            <div class="mb-2">
                                <label for="import_file" class="form-label fw-semibold">Select Excel/CSV file</label>
                                <input type="file" class="form-control" name="import_file" id="import_file"
                                    accept=".xlsx,.xls,.csv" required>
                                <div class="mt-2">
                                    <small class="cp-import-hint">Supported formats: .xlsx, .xls, .csv — maximum file size 5&nbsp;MB.</small>
                                    <small class="cp-import-hint text-danger">Do not modify the student_master_pk or course_master_pk columns.</small>
                                </div>
                            </div>

                            @if (session('import_errors'))
                                <div class="alert alert-danger border-0 mt-4 mb-0" role="alert">
                                    <h6 class="alert-heading fw-semibold mb-2">
                                        Import errors ({{ count(session('import_errors')) }})
                                    </h6>
                                    <div style="max-height: 200px; overflow-y: auto;">
                                        <ul class="mb-0 ps-3 small">
                                            @foreach (session('import_errors') as $error)
                                                <li class="mb-1">{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="importSubmitBtn">Import to OT List</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
{{-- DataTables (1.13.8 + responsive) and datatable-global-ui.js are already loaded
     by admin/layouts/footer.blade.php. Re-loading them here would replace the
     patched $.fn.dataTable and the page would lose the shared toolbar/footer chrome. --}}
<script>
    $(document).ready(function() {

        // ── Column visibility ────────────────────────────────────────────────
        // Labels are stored, never indices: adding a column shifts every index to
        // its right and would silently hide the wrong column for anyone holding a
        // saved preference. An unknown label is simply ignored (column stays visible).
        var CP_COLVIS_KEY = 'sargam.myCourseParticipant.hiddenCols.{{ auth()->id() ?? 'guest' }}';

        function cpReadHidden() {
            try {
                var raw = window.localStorage.getItem(CP_COLVIS_KEY);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return []; // private mode / storage disabled / corrupt value
            }
        }

        function cpSaveHidden(arr) {
            try {
                window.localStorage.setItem(CP_COLVIS_KEY, JSON.stringify(arr));
            } catch (e) { /* storage unavailable — the preference just won't persist */ }
        }

        function cpColumnTitle(col) {
            return $(col.header()).text().replace(/\s+/g, ' ').trim();
        }

        function cpAdjust(dt) {
            dt.columns.adjust();
            if (dt.responsive && typeof dt.responsive.recalc === 'function') {
                dt.responsive.recalc();
            }
        }

        function cpSetupColumns(dt) {
            var hidden = cpReadHidden();
            var $grid = $('#cpColumnToggleGrid');

            dt.columns().every(function() {
                if (this.index() === 0) return; // S. No. stays visible
                this.visible(hidden.indexOf(cpColumnTitle(this)) === -1, false);
            });
            cpAdjust(dt);

            if (!$grid.length) return;
            $grid.empty();

            dt.columns().every(function() {
                var idx = this.index();
                if (idx === 0) return;

                var title = cpColumnTitle(this);
                if (!title) return;

                var inputId = 'cpcolvis_' + idx;
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(title) === -1);

                $cb.on('change', function() {
                    var list = cpReadHidden();
                    var pos = list.indexOf(title);
                    if (this.checked) {
                        if (pos !== -1) list.splice(pos, 1);
                    } else if (pos === -1) {
                        list.push(title);
                    }
                    cpSaveHidden(list);
                    dt.column(idx).visible(this.checked, false);
                    cpAdjust(dt);
                });

                $grid.append(
                    $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                        $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                            .attr({ 'for': inputId, title: title })
                            .append($cb)
                            .append($('<span class="text-truncate"></span>').text(title))
                    )
                );
            });
        }

        // ── DataTable ────────────────────────────────────────────────────────
        var dataTable = $('#studentsTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ordering: false,
            autoWidth: false,
            language: {
                searchPlaceholder: 'Search name, OT code, email…'
            },
            ajax: {
                url: "{{ route('my.course.participant') }}",
                type: "GET",
                data: function(d) {
                    d.course_id = $('#course_id').val() || '';
                    d.status = $('#status').val() || '';
                    d.course_status = $('#course_status').val() || '';
                    // The controller filters on `search_term`, not DataTables' own
                    // search[value] — map the shared search box onto it, then blank
                    // the outgoing copy. Every data column here is a Yajra
                    // addColumn (no matching SQL column), so letting Yajra run its
                    // own global search on them would blow up the query. `d` is
                    // rebuilt per draw, so this does not clear the box.
                    d.search_term = (d.search && d.search.value) || '';
                    if (d.search) d.search.value = '';
                },
                dataSrc: function(json) {
                    $('#filteredCount').text(json.recordsTotal || 0);
                    return json.data || [];
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTable AJAX error:', error, thrown, xhr.responseText);
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '60px' },
                { data: 'user_name', name: 'user_name' },
                { data: 'name', name: 'name' },
                { data: 'course_code', name: 'course_code' },
                { data: 'ot_code', name: 'ot_code' },
                { data: 'email_id', name: 'email_id' },
                { data: 'mobile_no', name: 'mobile_no' },
                { data: 'cadre', name: 'cadre' },
                { data: 'participant_group', name: 'participant_group' }
            ],
            columnDefs: [
                { targets: [0], className: 'text-center' }
            ],
            drawCallback: function() {
                $('#filteredCount').text(this.api().page.info().recordsTotal);
            }
        });

        cpSetupColumns(dataTable);

        // ── Filters ──────────────────────────────────────────────────────────
        $('#course_id, #status').on('change', function() {
            dataTable.ajax.reload();
        });

        // Rebuild the course dropdown for the given Active/Archived set, then reload.
        function cpReloadCourses(courseStatus) {
            $.ajax({
                url: "{{ route('my.course.participant') }}",
                type: "GET",
                data: { course_status: courseStatus, ajax_courses: true },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    var $courseSelect = $('#course_id');
                    $courseSelect.empty().append(new Option('All Courses', ''));
                    $.each(response.courses || {}, function(id, name) {
                        $courseSelect.append(new Option(name, id));
                    });
                    $courseSelect.val('');
                    dataTable.ajax.reload();
                },
                error: function(xhr) {
                    console.error('Course dropdown AJAX error:', xhr.responseText);
                    dataTable.ajax.reload();
                }
            });
        }

        $('.cp-status-pill').on('click', function() {
            var status = $(this).data('cp-status');
            if ($('#course_status').val() === status) return;

            $('.cp-status-pill').removeClass('active')
                .attr('aria-pressed', 'false').removeAttr('aria-current');
            $(this).addClass('active')
                .attr('aria-pressed', 'true').attr('aria-current', 'true');

            $('#course_status').val(status);
            cpReloadCourses(status);
        });

        $('#resetFilters').on('click', function() {
            $('.cp-status-pill').removeClass('active')
                .attr('aria-pressed', 'false').removeAttr('aria-current');
            $('.cp-status-pill[data-cp-status="active"]').addClass('active')
                .attr('aria-pressed', 'true').attr('aria-current', 'true');

            $('#course_status').val('active');
            $('#course_id').val('');
            $('#status').val('');

            // Clear the search box the global UI moved into the toolbar slot.
            $('#cpDtSearch input').val('');
            dataTable.search('');

            cpReloadCourses('active');
        });

        // ── Export ───────────────────────────────────────────────────────────
        $('[data-cp-format]').on('click', function(e) {
            e.preventDefault();
            $('#exportFormat').val($(this).data('cp-format'));
            // Mirror the active list filters so the export matches the table.
            $('#exportCourseId').val($('#course_id').val() || '');
            $('#exportStatus').val($('#status').val() || '');
            $('#exportCourseStatus').val($('#course_status').val() || 'active');
            $('#exportSearchTerm').val(dataTable.search() || '');
            $('#exportForm')[0].submit();
        });

        // ── Import ───────────────────────────────────────────────────────────
        $('#importForm').on('submit', function(e) {
            var fileInput = $('#import_file')[0];
            var submitBtn = $('#importSubmitBtn');

            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Please select a file to upload');
                return false;
            }

            if (!/(\.xlsx|\.xls|\.csv)$/i.test(fileInput.files[0].name)) {
                e.preventDefault();
                alert('Please upload only Excel or CSV files (.xlsx, .xls, .csv)');
                return false;
            }

            if (fileInput.files[0].size > 5 * 1024 * 1024) {
                e.preventDefault();
                alert('File size must be less than 5MB');
                return false;
            }

            submitBtn.prop('disabled', true).text('Processing…');
        });

        $('#importModal').on('hidden.bs.modal', function() {
            $('#importSubmitBtn').prop('disabled', false).text('Import to OT List');
            $('#importForm')[0].reset();
        });
    });
</script>
@endpush
