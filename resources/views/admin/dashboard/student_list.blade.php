@extends('admin.layouts.master')

@section('title', 'Student List - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    .student-list-page .sl-filter-select {
        width: 200px;
        flex: 0 0 auto;
        height: 40px;
        border-radius: 8px;
        font-size: 0.9375rem;
        color: #344054;
        padding: 0.5rem 2.25rem 0.5rem 0.875rem;
        background-position: right 0.75rem center;
    }

    /* Inside the +3 Filters popover the selects span the menu width. */
    .student-list-page .sl-more-menu .sl-filter-select { width: 100%; }

    .student-list-page .sl-filter-select:focus {
        border-color: #004a93;
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12);
    }

    .student-list-page .sl-daterange-wrap { position: relative; }

    .student-list-page .sl-daterange-input {
        width: 230px;
        height: 48px;
        padding: 1.15rem 0.875rem 0.25rem;
        cursor: pointer;
        background-image: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.875rem;
    }

    .student-list-page .sl-daterange-label {
        position: absolute;
        top: 5px;
        left: 0.875rem;
        font-size: 0.7rem;
        color: #667085;
        pointer-events: none;
    }

    .student-list-page .sl-more-menu { min-width: 240px; }
    .student-list-page .sl-more-menu .form-label { margin-bottom: 0.25rem; }
    .student-list-page .sl-more-filters.dropdown-toggle::after { display: none; }

    .student-list-page .sl-toolbar-btn {
        height: 40px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0 1.1rem;
        font-size: 0.9375rem;
        font-weight: 500;
        color: #004a93;
        border-radius: 8px;
        background: #fff;
    }

    .student-list-page .sl-toolbar-btn:hover { background: #f9fafb; }
    .student-list-page .sl-toolbar-btn i { font-size: 1rem; line-height: 1; }

    .student-list-page .sl-more-filters {
        color: #004a93;
        font-weight: 600;
        font-size: 0.9375rem;
        text-decoration: none;
        white-space: nowrap;
    }
    .student-list-page .sl-more-filters:hover { text-decoration: underline; }

    .student-list-page .programme-dt-table tbody td a.sl-count {
        color: #004a93;
        font-weight: 600;
        text-decoration: none;
        white-space: wrap;
    }
    .student-list-page .programme-dt-table tbody td a.sl-count:hover { text-decoration: underline; }

    .student-list-page .sl-extra-filters { border: 1px dashed #e4e7ec; border-radius: 8px; background: #fcfcfd; }
</style>
@endpush

@section('content')
@php $filters = $filters ?? []; @endphp
<div class="container-fluid student-list-page">
    <x-breadcrum title="Student List" :showBack="true" />
    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white" role="group" aria-label="Attendance status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ ($filters['attendance'] ?? 'present') !== 'absent' ? 'active' : '' }}"
                    data-attendance="present">Present</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ ($filters['attendance'] ?? '') === 'absent' ? 'active' : '' }}"
                    data-attendance="absent">Absent</button>
            </li>
        </ul>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn sl-toolbar-btn" id="studentListPrintBtn">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </button>
            <div class="dropdown">
                <button type="button" class="btn sl-toolbar-btn dropdown-toggle" id="studentListDownloadBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="studentListDownloadBtn">
                    <li><button type="button" class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="studentListDownloadCsv"><i class="bi bi-filetype-csv text-success"></i><span>Download CSV</span></button></li>
                    <li><button type="button" class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="studentListDownloadPdf"><i class="bi bi-filetype-pdf text-danger"></i><span>Download PDF</span></button></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>
                    @if($availableCourses->isNotEmpty())
                    <select id="courseFilter" class="form-select sl-filter-select" aria-label="Filter by course">
                        <option value="">Course</option>
                        @foreach($availableCourses as $course)
                            <option value="{{ $course['pk'] }}" {{ (string)($filters['course_id'] ?? '') === (string)$course['pk'] ? 'selected' : '' }}>{{ $course['course_name'] }}</option>
                        @endforeach
                    </select>
                    @endif
                    <select id="dutyTypeFilter" class="form-select sl-filter-select" aria-label="Filter by duty type">
                        <option value="">Duty Type</option>
                        @foreach(($dutyTypes ?? []) as $dt)
                            <option value="{{ $dt->pk }}" {{ (string)($filters['duty_type'] ?? '') === (string)$dt->pk ? 'selected' : '' }}>{{ $dt->mdo_duty_type_name }}</option>
                        @endforeach
                    </select>
                    <div class="sl-daterange-wrap">
                        <span class="sl-daterange-label">Time Period</span>
                        <input type="text" id="timePeriodFilter" class="form-control sl-filter-select sl-daterange-input"
                            placeholder="Select dates" autocomplete="off" readonly aria-label="Filter by time period"
                            value="{{ (!empty($filters['from_date']) && !empty($filters['to_date'])) ? \Carbon\Carbon::parse($filters['from_date'])->format('d/m/Y').' - '.\Carbon\Carbon::parse($filters['to_date'])->format('d/m/Y') : '' }}">
                    </div>
                    <div class="dropdown">
                        <a href="javascript:void(0)" class="sl-more-filters dropdown-toggle" id="moreFiltersBtn"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">+3 Filters</a>
                        <div class="dropdown-menu sl-more-menu p-3 shadow-sm border rounded-3" aria-labelledby="moreFiltersBtn">
                            <div class="mb-2">
                                <label for="roleFilter" class="form-label small fw-semibold text-secondary">ACC</label>
                                <select id="roleFilter" class="form-select sl-filter-select w-100">
                                    <option value="">All</option>
                                    <option value="cc_acc" {{ ($filters['role_filter'] ?? '') === 'cc_acc' ? 'selected' : '' }}>CC/ACC</option>
                                    @if(isset($counsellorTypes) && $counsellorTypes->isNotEmpty())
                                        @foreach($counsellorTypes as $type)
                                            <option value="{{ $type->type_pk }}" {{ (string)($filters['role_filter'] ?? '') === (string)$type->type_pk ? 'selected' : '' }}>{{ $type->counsellor_type_name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="mb-2">
                                <label for="cadreFilter" class="form-label small fw-semibold text-secondary">Cadre</label>
                                <select id="cadreFilter" class="form-select sl-filter-select w-100">
                                    <option value="">All</option>
                                    @foreach(($cadreOptions ?? []) as $cadre)
                                        <option value="{{ $cadre }}" {{ (string)($filters['cadre'] ?? '') === (string)$cadre ? 'selected' : '' }}>{{ $cadre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="houseFilter" class="form-label small fw-semibold text-secondary">House Name</label>
                                <select id="houseFilter" class="form-select sl-filter-select w-100">
                                    <option value="">All</option>
                                    @foreach(($houseOptions ?? []) as $house)
                                        <option value="{{ $house }}" {{ (string)($filters['house'] ?? '') === (string)$house ? 'selected' : '' }}>{{ $house }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">Reset Filters</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnStudentColumns" data-bs-toggle="modal" data-bs-target="#studentColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="studentDtSearch" class="programme-dt-search" data-dt-search-for="studentListTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table programme-dt-table" id="studentListTable">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>OT Code</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Cadre</th>
                                <th>House Name</th>
                                <th>Total Duty (Count)</th>
                                <th>Total Medical Exemption Count</th>
                                <th>Total PT Exemption Count</th>
                                <th>Total Stationed Leave Count</th>
                                <th>Total Notice/Memo</th>
                                <th>Total Discipline Memo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $index => $studentMap)
                                @php
                                    $student = $studentMap->studentMaster;
                                    $course = $studentMap->course;
                                    $groupName = $studentMap->groupMapping->groupTypeMasterCourseMasterMap->group_name ?? null;
                                    $cadreName = $student->cadre->cadre_name ?? 'N/A';
                                    $houseName = $studentMap->house_name ?? 'N/A';
                                    $detailUrl = route('admin.dashboard.students.detail', encrypt($student->pk));
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $student->generated_OT_code ?? 'N/A' }}</td>
                                    <td><a href="{{ $detailUrl }}" class="sl-count">{{ $student->display_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) }}</a></td>
                                    <td>{{ $student->email ?? 'N/A' }}</td>
                                    <td>{{ $cadreName }}</td>
                                    <td>{{ $houseName }}</td>
                                    <td><a href="{{ $detailUrl }}" class="sl-count">{{ str_pad((string) ($studentMap->total_duty_count ?? 0), 2, '0', STR_PAD_LEFT) }}</a></td>
                                    <td><a href="{{ $detailUrl }}" class="sl-count">{{ str_pad((string) ($studentMap->total_medical_exception_count ?? 0), 2, '0', STR_PAD_LEFT) }}</a></td>
                                    <td><a href="{{ $detailUrl }}" class="sl-count">{{ str_pad((string) ($studentMap->total_pt_exemption_count ?? 0), 2, '0', STR_PAD_LEFT) }}</a></td>
                                    <td><a href="{{ $detailUrl }}" class="sl-count">{{ str_pad((string) ($studentMap->total_stationed_leave_count ?? 0), 2, '0', STR_PAD_LEFT) }}</a></td>
                                    <td><a href="{{ $detailUrl }}" class="sl-count">{{ str_pad((string) ($studentMap->total_notice_count ?? 0), 2, '0', STR_PAD_LEFT) }}</a></td>
                                    <td><a href="{{ $detailUrl }}" class="sl-count">{{ str_pad((string) ($studentMap->total_memo_count ?? 0), 2, '0', STR_PAD_LEFT) }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="12" class="text-center text-muted py-4">Data not found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="studentDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="studentListTable"></div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="studentColumnVisibilityModal" tabindex="-1" aria-labelledby="studentColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="studentColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="studentColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(document).ready(function() {
        const filters = @json($filters ?? []);
        const allGroupNames = @json($groupNames ?? []);
        const baseUrl = "{{ route('admin.dashboard.students') }}";

        /* ── Reload-based server-side filtering ── */
        function applyFilter(changes) {
            const p = new URLSearchParams(window.location.search);
            Object.entries(changes).forEach(([k, v]) => {
                if (v === '' || v === null || v === undefined) { p.delete(k); }
                else { p.set(k, v); }
            });
            window.location.search = p.toString();
        }

        $('.programme-status-tabs .programme-status-pill').on('click', function() {
            applyFilter({ attendance: $(this).data('attendance') });
        });
        $('#courseFilter').on('change', function() { applyFilter({ course_id: this.value }); });
        $('#dutyTypeFilter').on('change', function() { applyFilter({ duty_type: this.value }); });
        $('#roleFilter').on('change', function() { applyFilter({ role_filter: this.value }); });
        $('#cadreFilter').on('change', function() { applyFilter({ cadre: this.value }); });
        $('#houseFilter').on('change', function() { applyFilter({ house: this.value }); });
        $('#resetFilters').on('click', function() { window.location.href = baseUrl; });

        /* ── Time Period date-range ── */
        const $period = $('#timePeriodFilter');
        $period.daterangepicker({
            autoUpdateInput: false,
            opens: 'left',
            locale: { format: 'DD-MM-YYYY', cancelLabel: 'Clear', applyLabel: 'Apply' },
            ranges: {
                'Today': [moment(), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last 3 Months': [moment().subtract(3, 'months').startOf('day'), moment()],
                'This Year': [moment().startOf('year'), moment().endOf('year')],
            },
        });
        $period.on('apply.daterangepicker', function(ev, picker) {
            applyFilter({ from_date: picker.startDate.format('YYYY-MM-DD'), to_date: picker.endDate.format('YYYY-MM-DD') });
        });
        $period.on('cancel.daterangepicker', function() {
            applyFilter({ from_date: '', to_date: '' });
        });

        /* ── DataTable: client-side search / pagination / columns over the filtered set ── */
        let dataTable = null;
        @if($students->isNotEmpty())
            dataTable = $('#studentListTable').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                order: [[0, 'asc']],
                language: { emptyTable: 'Data not found.' },
                responsive: false,
            });
        @endif

        $('#studentListPrintBtn').on('click', function() { window.print(); });

        function buildExportUrl(format) {
            const params = new URLSearchParams(window.location.search);
            params.delete('attendance');
            const search = dataTable ? dataTable.search() : '';
            if (search) params.set('search', search);
            const base = format === 'csv'
                ? "{{ route('admin.dashboard.students.export', ['format' => 'csv']) }}"
                : "{{ route('admin.dashboard.students.export', ['format' => 'pdf']) }}";
            const q = params.toString();
            return q ? `${base}?${q}` : base;
        }
        $('#studentListDownloadCsv').on('click', function(e) { e.preventDefault(); window.location.href = buildExportUrl('csv'); });
        $('#studentListDownloadPdf').on('click', function(e) { e.preventDefault(); window.open(buildExportUrl('pdf'), '_blank'); });

        /* ---------------- Column show / hide ---------------- */
        const studentColStorageKey = 'studentListGrid:hiddenColumns:v1';
        function studentGetHiddenCols() {
            try { const raw = localStorage.getItem(studentColStorageKey); const arr = raw ? JSON.parse(raw) : []; return Array.isArray(arr) ? arr : []; }
            catch (e) { return []; }
        }
        function studentPersistHiddenCols(arr) { try { localStorage.setItem(studentColStorageKey, JSON.stringify(arr)); } catch (e) {} }
        function setupStudentColumns(dt) {
            if (!dt) { return; }
            const hidden = studentGetHiddenCols();
            dt.columns().every(function() { const idx = this.index(); this.visible(hidden.indexOf(idx) === -1, false); });
            dt.columns.adjust();
            const $grid = $('#studentColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();
            dt.columns().every(function() {
                const idx = this.index();
                const title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }
                const inputId = 'studentcolvis_' + idx;
                const $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                const $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>').attr('for', inputId);
                const $cb = $('<input type="checkbox" class="form-check-input m-0">').attr('id', inputId).prop('checked', hidden.indexOf(idx) === -1);
                $cb.on('change', function() {
                    const h = studentGetHiddenCols();
                    const pos = h.indexOf(idx);
                    if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else { if (pos === -1) h.push(idx); }
                    studentPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });
                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }
        if (dataTable) { setupStudentColumns(dataTable); }
    });
</script>
@endpush

@endsection
