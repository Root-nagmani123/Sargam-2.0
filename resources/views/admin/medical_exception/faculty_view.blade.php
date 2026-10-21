@extends('admin.layouts.master')

@section('title', 'Medical Exemption Faculty View')

@push('styles')
<style>
/* Medical Exemption Faculty View — page-scoped polish on the programme-dt
   toolbar (custom.css) and --ds-* tokens (sargam-app.css). */
.mef-page .mef-filter-control {
    min-height: 40px;
    font-size: 0.9375rem;
    border: 1px solid #d0d5dd;
    border-radius: var(--ds-radius-card);
}
.mef-page .mef-filter-control:focus {
    border-color: var(--ds-primary);
    box-shadow: var(--ds-focus-ring);
}
.mef-page #mefTable td {
    white-space: nowrap;
    vertical-align: middle;
    color: var(--ds-ink);
}
.mef-page #mefTable .mef-topic {
    white-space: normal;
    min-width: 180px;
    max-width: 280px;
}
.mef-page .mef-count {
    display: inline-block;
    min-width: 2rem;
    padding: 0.15rem 0.6rem;
    border-radius: 50rem;
    font-weight: 600;
    text-align: center;
    color: var(--ds-primary);
    background: rgba(var(--bs-primary-rgb), 0.08);
}
@media (max-width: 575.98px) {
    .mef-page .programme-dt-filter-select { width: 100%; }
}
</style>
@endpush

@section('setup_content')

<div class="container-fluid mef-page py-3">
    <x-breadcrum title="Medical Exemption Faculty View"></x-breadcrum>

    {{-- Download / Print — the shared ExportsMasterGrid formats, as on the Master
         listings. The page's course/date filters ride on every link; the grid's
         search (?q=) and hidden columns (?cols=) are stamped on by
         mefUpdateExportLinks(). Print is a server-rendered branded page. --}}
    @php
        $mefExport = fn (string $format) => route('medical.exception.faculty.view.export', array_filter([
            'format'    => $format,
            'course'    => $courseFilter ?? null,
            'date_from' => $dateFromFilter ?? null,
        ]));
    @endphp
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <div class="dropdown">
            <button type="button" id="mefDownloadToggle"
                    class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mefDownloadToggle">
                <li>
                    <a class="dropdown-item" id="mefCsvLink" href="{{ $mefExport('csv') }}">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" id="mefExcelLink" href="{{ $mefExport('excel') }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" id="mefPdfLink" href="{{ $mefExport('pdf') }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF
                    </a>
                </li>
            </ul>
        </div>

        <a href="{{ $mefExport('print') }}" id="mefPrintLink" target="_blank" rel="noopener"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="ds-card">
        <div class="ds-card-body p-4">

            {{-- Filter toolbar (programme-dt design system, as on Attendance) --}}
            <div class="d-flex flex-column flex-xl-row flex-xl-wrap align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <form class="d-flex flex-wrap align-items-center gap-3" role="search"
                    aria-label="Medical exemption filters" method="GET"
                    action="{{ route('medical.exception.faculty.view') }}">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                        <select name="course" id="filter_course" class="form-select mef-filter-control"
                            aria-label="Course Name" onchange="this.form.submit()">
                            <option value="">Course Name</option>
                            @foreach($courses ?? [] as $course)
                                <option value="{{ $course->pk }}" {{ (isset($courseFilter) && $courseFilter == $course->pk) ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <input type="date" name="date_from" id="filter_date_from"
                            class="form-control mef-filter-control" aria-label="Date From"
                            title="Exemptions from this date" value="{{ $dateFromFilter ?? '' }}"
                            onchange="this.form.submit()">
                    </div>

                    <a href="{{ route('medical.exception.faculty.view') }}"
                        class="btn programme-dt-btn-reset d-inline-flex align-items-center">Reset Filters</a>
                </form>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-xl-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="mefBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#mefColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="mefDtSearch" class="programme-dt-search" data-dt-search-for="mefTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="mefTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S.No.</th>
                                <th scope="col">Course Name</th>
                                <th scope="col">Faculty Name</th>
                                <th scope="col">Topics</th>
                                <th scope="col">Student Name</th>
                                <th scope="col">Medical Exemption Date</th>
                                <th scope="col">Medical Exemption Time</th>
                                <th scope="col">OT Code</th>
                                <th scope="col">Medical Document</th>
                                <th scope="col">Application Type</th>
                                <th scope="col">Exemption Count</th>
                                <th scope="col">Submitted On</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- No @empty row: a single colspan cell breaks DataTables' column count. --}}
                            @foreach($data ?? [] as $index => $record)
                                @php
                                    $from = $record->from_date ? \Carbon\Carbon::parse($record->from_date) : null;
                                    $submitted = $record->submitted_on ? \Carbon\Carbon::parse($record->submitted_on) : null;
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $record->course_name ?? 'N/A' }}</td>
                                    <td>{{ $record->faculty_name ?? 'N/A' }}</td>
                                    <td class="mef-topic">{{ $record->topics ?? 'N/A' }}</td>
                                    <td>{{ $record->student_name ?? 'N/A' }}</td>
                                    <td data-order="{{ $from?->timestamp ?? 0 }}">{{ $from ? $from->format('d-m-Y') : 'N/A' }}</td>
                                    <td>{{ $from ? $from->format('h:i A') : 'N/A' }}</td>
                                    <td>{{ $record->ot_code ?? 'N/A' }}</td>
                                    <td>
                                        @if($record->medical_document)
                                            <a href="{{ asset('storage/' . $record->medical_document) }}" target="_blank" rel="noopener"
                                                class="d-inline-flex align-items-center gap-1 fw-semibold text-primary text-decoration-none">
                                                <i class="bi bi-eye" aria-hidden="true"></i> View
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $record->application_type ?? 'N/A' }}</td>
                                    <td><span class="mef-count">{{ $record->exemption_count ?? 0 }}</span></td>
                                    <td data-order="{{ $submitted?->timestamp ?? 0 }}">{{ $submitted ? $submitted->format('d-m-Y h:i A') : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="mefDtFooter"
                class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                data-dt-footer-for="mefTable"></div>
        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="mefColumnVisibilityModal" tabindex="-1"
     aria-labelledby="mefColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="mefColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="mefColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    if (!$.fn.DataTable) return;

    // Search box + footer are relocated into the slots above by datatable-global-ui.js.
    var dt = $('#mefTable').DataTable({
        order: [],
        pageLength: 10,
        autoWidth: false,
        columnDefs: [{ targets: 0, orderable: false, searchable: false }],
        language: { emptyTable: 'No medical exemption records found.' }
    });

    // Keep S.No. sequential after client-side sort/search/paging.
    function mefRenumber() {
        var start = dt.page.info().start;
        dt.rows({ page: 'current', order: 'applied' }).nodes().each(function (row, i) {
            $(row).children('td').eq(0).text(start + i + 1);
        });
    }
    dt.on('draw', mefRenumber);

    /* ---------- Export links follow the grid ----------
     * Positional map: header index -> export key in
     * MedicalExceptionFacultyViewController::exportColumnDefs(). '' = not in the
     * export (Medical Document). Adding a table column means adding it here too. */
    var MEF_EXPORT_COLUMN_KEYS = ['sno', 'course', 'faculty', 'topics', 'student', 'date', 'time',
        'ot_code', '', 'application_type', 'exemption_count', 'submitted_on'];
    var MEF_EXPORT_COL_COUNT = MEF_EXPORT_COLUMN_KEYS.filter(Boolean).length;
    var MEF_EXPORT_LINK_IDS = ['mefCsvLink', 'mefExcelLink', 'mefPdfLink', 'mefPrintLink'];

    function mefUpdateExportLinks() {
        var keys = [];
        dt.columns().every(function () {
            var key = MEF_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });
        var term = dt.search() || '';

        MEF_EXPORT_LINK_IDS.forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');

            params.delete('q');
            if (term !== '') { params.set('q', term); }
            params.delete('cols');
            // No ?cols= while nothing is hidden — the server reads that as "every column".
            if (keys.length !== MEF_EXPORT_COL_COUNT) { params.set('cols', keys.join(',')); }

            var qs = params.toString();
            link.href = base + (qs ? '?' + qs : '');
        });
    }
    dt.on('search.dt', mefUpdateExportLinks);

    /* ---------- Column visibility (stores LABELS, not indices — docs/column-visibility.md) ---------- */
    var MEF_COLVIS_KEY = 'sargam.medicalExemptionFaculty.hiddenCols.{{ auth()->id() ?? 'guest' }}';

    function mefReadHiddenCols() {
        try {
            var arr = JSON.parse(window.localStorage.getItem(MEF_COLVIS_KEY) || '[]');
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function mefSaveHiddenCols(hidden) {
        try {
            window.localStorage.setItem(MEF_COLVIS_KEY, JSON.stringify(hidden));
        } catch (e) { /* private mode — the preference just won't persist */ }
    }

    (function mefBuildColumnGrid() {
        var hidden = mefReadHiddenCols();
        var $grid = $('#mefColumnToggleGrid').empty();

        dt.columns().every(function () {
            var idx = this.index();
            var label = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!label) { return; }

            this.visible(hidden.indexOf(label) === -1, false);

            var inputId = 'mefColvis_' + idx;
            var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                .attr({ id: inputId, 'data-col': idx, 'data-label': label })
                .prop('checked', hidden.indexOf(label) === -1);

            $checkbox.on('change', function () {
                var current = mefReadHiddenCols();
                var pos = current.indexOf(label);
                if (this.checked) {
                    if (pos !== -1) { current.splice(pos, 1); }
                } else if (pos === -1) {
                    current.push(label);
                }
                mefSaveHiddenCols(current);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
                mefUpdateExportLinks();
            });

            $grid.append(
                $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                    $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                        .attr({ 'for': inputId, title: label })
                        .append($checkbox)
                        .append($('<span></span>').text(label))
                )
            );
        });

        dt.columns.adjust();
    })();

    mefUpdateExportLinks();
});
</script>
@endpush
