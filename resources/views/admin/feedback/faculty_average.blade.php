@php
    $fr = $fr ?? $feedbackReportRoutes ?? \App\Support\FeedbackReportRouteRegistry::forRequest();
@endphp
@extends('admin.layouts.master')

@section('title', 'Faculty Feedback Average - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<style>
/* Choices.js — program & faculty dropdowns */
#avgProgramSelect + .choices .choices__inner,
#avgFacultySelect + .choices .choices__inner {
    min-height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 2.25rem 0.375rem 0.75rem;
    font-size: 0.85rem;
    border: 1px solid #d0d7de;
    border-radius: var(--bs-border-radius, 0.375rem);
    background-color: #fff;
    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
}
#avgProgramSelect + .choices .choices__inner:focus-within,
#avgFacultySelect + .choices .choices__inner:focus-within {
    border-color: #0b4f8a;
    box-shadow: 0 0 0 0.2rem rgba(11,79,138,.12);
}
#avgProgramSelect + .choices .choices__input,
#avgFacultySelect + .choices .choices__input {
    font-size: 0.85rem;
}
</style>
@endpush

@section('setup_content')
    <div class="container-fluid faa-master-page">
        <x-breadcrum title="Faculty Feedback Average"></x-breadcrum>

        <form method="GET" action="{{ $fr['average'] }}" id="filterForm" class="faa-form">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3 mb-4">
                <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white shadow-sm mb-0 faa-status-tabs" role="group" aria-label="Course status">
                    <li class="nav-item" role="presentation">
                        <input class="btn-check" type="radio" name="course_type" value="current" id="current"
                            {{ ($courseType ?? 'current') == 'current' ? 'checked' : '' }}>
                        <label class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill mb-0" for="current">Active</label>
                    </li>
                    <li class="nav-item" role="presentation">
                        <input class="btn-check" type="radio" name="course_type" value="archived" id="archived"
                            {{ ($courseType ?? 'current') == 'archived' ? 'checked' : '' }}>
                        <label class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill mb-0" for="archived">Archived</label>
                    </li>
                </ul>

                <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm" onclick="printReport()" title="Print report">
                        <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
                    </button>
                    <a href="{{ $fr['average_export_pdf'] }}?{{ http_build_query(array_filter(['course_type' => $courseType ?? 'current', 'program_name' => $currentProgram ?? '', 'faculty_name' => $currentFaculty ?? '', 'from_date' => $fromDate ?? '', 'to_date' => $toDate ?? ''])) }}"
                        class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm faa-pdf-export-link"
                        target="_blank"
                        title="Download PDF">
                        <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                    </a>
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-1 px-2 py-2" data-bs-toggle="dropdown" aria-label="More export options">
                            <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2">
                            <li>
                                <a class="dropdown-item rounded-1 mx-2 py-2 export-excel-link"
                                    href="{{ $fr['average_export_excel'] }}?{{ http_build_query(array_filter(['course_type' => $courseType ?? 'current', 'program_name' => $currentProgram ?? '', 'faculty_name' => $currentFaculty ?? '', 'from_date' => $fromDate ?? '', 'to_date' => $toDate ?? ''])) }}"
                                    target="_blank">
                                    <i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i>Export Excel
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ $fr['average'] }}" id="filterForm">
                            <fieldset class="mb-3">
                                <legend class="fs-6 fw-semibold">Course Status</legend>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="course_type" value="current"
                                        id="current" {{ ($courseType ?? 'current') == 'current' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="current">Current Courses</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="course_type" value="archived"
                                        id="archived" {{ ($courseType ?? 'current') == 'archived' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="archived">Archived Courses</label>
                                </div>
                            </fieldset>

                            <div class="mb-3">
                                <label class="form-label">Program Name</label>
                                <select name="program_name" id="avgProgramSelect">
                                    <option value="">All Programs</option>
                                    @foreach ($programs as $key => $program)
                                        <option value="{{ $key }}" {{ $currentProgram == $key ? 'selected' : '' }}>{{ $program }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Faculty Name</label>
                                <select name="faculty_name" id="avgFacultySelect">
                                    <option value="">All Faculty</option>
                                    @foreach ($faculties as $key => $faculty)
                                        <option value="{{ $key }}" {{ $currentFaculty == $key ? 'selected' : '' }}>{{ $faculty }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="programme-dt-filter-select faa-period-filter position-relative">
                                <input type="hidden" name="from_date" id="fromDate" value="{{ $fromDate ?? '' }}">
                                <input type="hidden" name="to_date" id="toDate" value="{{ $toDate ?? '' }}">
                                <label for="faa_time_period_picker" class="visually-hidden">Time Period</label>
                                <input type="text"
                                    id="faa_time_period_picker"
                                    class="form-control faa-time-period-input w-100"
                                    placeholder="Time Period"
                                    value=""
                                    readonly
                                    autocomplete="off"
                                    aria-label="Filter by time period">
                                <i class="bi bi-chevron-down faa-filter-chevron" aria-hidden="true"></i>
                            </div>

                            <button type="button" class="btn programme-dt-btn-reset flex-shrink-0" onclick="resetFilters()">Reset Filters</button>
                        </div>

                        <div class="faa-table-search ms-xl-auto flex-shrink-0">
                            <div class="dropdown faa-search-slot">
                                <button type="button"
                                    class="btn faa-search-trigger"
                                    id="faaSearchTrigger"
                                    data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside"
                                    aria-expanded="false"
                                    aria-label="Search table">
                                    <i class="bi bi-search" aria-hidden="true"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3 faa-table-search-menu">
                                    <label for="faaTableSearch" class="form-label small text-secondary mb-2">Search</label>
                                    <input type="search"
                                        id="faaTableSearch"
                                        class="form-control shadow-none"
                                        placeholder="Search table..."
                                        autocomplete="off"
                                        aria-label="Search table">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="loading-spinner" id="loadingSpinner">
                        <div class="faa-loading-inner text-center py-5">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mb-0 fw-medium text-secondary small">Loading feedback data…</p>
                        </div>
                    </div>

                    <div id="tableContainer">
                        @if (!empty($currentProgramName))
                            <div class="text-center mb-3 d-none faa-program-title-wrap">
                                <h6 class="fw-semibold mb-0">{{ $currentProgramName }}</h6>
                            </div>
                        @endif

                        @if ($feedbackData->isEmpty())
                            <div class="faa-empty-state text-center py-5 px-3">
                                <i class="bi bi-clipboard-data d-block mb-3 text-body-secondary faa-empty-icon" aria-hidden="true"></i>
                                <h5 class="fw-semibold text-body-secondary">No records found</h5>
                                <p class="text-muted small mb-0 mx-auto" style="max-width: 28rem;">Try adjusting your filters to see results.</p>
                            </div>
                        @else
                            <div class="programme-dt-panel faa-table-panel">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table faa-feedback-table" id="feedbackTable">
                                        <thead>
                                            <tr>
                                                <th scope="col">S. No.</th>
                                                <th scope="col">Faculty</th>
                                                <th scope="col">Topic</th>
                                                <th scope="col">Program Name</th>
                                                <th scope="col" class="text-center">Content %</th>
                                                <th scope="col" class="text-center">Presentation %</th>
                                                <th scope="col">Session Date and Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($feedbackData as $index => $data)
                                                <tr>
                                                    <td class="text-secondary faa-sno">{{ $index + 1 }}</td>
                                                    <td class="faa-faculty-name">{{ $data['faculty_name'] }}</td>
                                                    <td>{{ $data['topic_name'] }}</td>
                                                    <td class="faa-col-program">{{ $data['program_name'] }}</td>
                                                    <td class="text-center">
                                                        <span class="faa-pct {{ $data['content_percentage'] >= 90 ? 'percentage-good' : ($data['content_percentage'] >= 70 ? 'percentage-average' : 'percentage-low') }}">
                                                            {{ number_format($data['content_percentage'], 2) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="faa-pct {{ $data['presentation_percentage'] >= 90 ? 'percentage-good' : ($data['presentation_percentage'] >= 70 ? 'percentage-average' : 'percentage-low') }}">
                                                            {{ number_format($data['presentation_percentage'], 2) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-secondary text-nowrap">
                                                        {{ \Carbon\Carbon::parse($data['session_date'])->format('d-M-Y') }}
                                                        @if (!empty($data['class_session']))
                                                            {{ $data['class_session'] }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div id="faaTableFooter" class="faa-pagination-wrap programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 border-top {{ $feedbackData->isEmpty() ? 'd-none' : '' }}">
                        <nav aria-label="Faculty average pagination" class="programme-dt-pagination">
                            <ul class="pagination faa-pagination flex-wrap gap-1 mb-0" id="faaPaginationList"></ul>
                        </nav>
                        <div class="faa-records-info programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto text-secondary small">
                            <label class="d-inline-flex align-items-center gap-2 mb-0" for="faaPageSizeSelect">
                                <span>Showing</span>
                                <select class="form-select form-select-sm faa-page-size-select" id="faaPageSizeSelect" aria-label="Items per page">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                </select>
                            </label>
                            <span>of <strong class="text-body" id="faaTotalRecords">{{ number_format($feedbackData->count()) }}</strong> items</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        function faaUpdateFilterSelectStyles() {
            document.querySelectorAll('#filterForm .faa-filter-select').forEach(function(select) {
                select.classList.toggle('faa-filter-empty', !select.value);
            });
        }

        function resetFilters() {
            document.getElementById('filterForm').reset();
            document.querySelector('input[name="course_type"][value="current"]').checked = true;
            if (window.faaTimePeriodPicker) {
                window.faaTimePeriodPicker.clear();
            }
            const fromEl = document.getElementById('fromDate');
            const toEl = document.getElementById('toDate');
            if (fromEl) {
                fromEl.value = '';
            }
            if (toEl) {
                toEl.value = '';
            }
            const programSelect = document.getElementById('faaProgramSelect');
            const facultySelect = document.getElementById('faaFacultySelect');
            if (programSelect) {
                programSelect.value = '';
            }
            if (facultySelect) {
                facultySelect.value = '';
            }
            const searchDesktop = document.getElementById('faaTableSearch');
            if (searchDesktop) {
                searchDesktop.value = '';
            }
            if (typeof faaSetSearchQuery === 'function') {
                faaSetSearchQuery('');
            }
            faaUpdateFilterSelectStyles();
            loadFeedbackData();
        }

        function printReport() {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                params.append(key, value);
            }
            const printUrl = `{{ $fr['average_print'] }}?${params.toString()}`;
            window.open(printUrl, '_blank');
        }

        function updateExportLinks() {
            const courseType = document.querySelector('input[name="course_type"]:checked')?.value || 'current';
            const programName = document.querySelector('select[name="program_name"]')?.value || '';
            const facultyName = document.querySelector('select[name="faculty_name"]')?.value || '';
            const fromDate = document.querySelector('input[name="from_date"]')?.value || '';
            const toDate = document.querySelector('input[name="to_date"]')?.value || '';

            const excelBaseUrl = "{{ $fr['average_export_excel'] }}";
            const pdfBaseUrl = "{{ $fr['average_export_pdf'] }}";

            const exportLinks = document.querySelectorAll('.export-btn-group a, .export-excel-link, .faa-pdf-export-link');

            exportLinks.forEach(link => {
                const isExcel = link.classList.contains('export-excel-link') || link.href.includes('export-excel');
                const isPdf = link.classList.contains('faa-pdf-export-link') || (link.href.includes('export-pdf') && !link.classList.contains('export-excel-link'));
                const base = isExcel ? excelBaseUrl : (isPdf ? pdfBaseUrl : null);
                if (!base) {
                    return;
                }
                const url = new URL(base, window.location.origin);
                url.searchParams.set('course_type', courseType);
                url.searchParams.set('program_name', programName);
                url.searchParams.set('faculty_name', facultyName);
                url.searchParams.set('from_date', fromDate);
                url.searchParams.set('to_date', toDate);
                link.href = url.toString();
            });
        }

        let faaAllRows = [];
        let faaCurrentPage = 1;
        let faaPageSize = 200;
        let faaSearchQuery = '';

        function faaSetSearchQuery(value) {
            faaSearchQuery = value || '';
            faaCurrentPage = 1;
            faaRenderTablePage();
        }

        function faaGetSearchValue() {
            const input = document.getElementById('faaTableSearch');
            return input ? input.value.trim() : '';
        }

        function faaSyncSearchInputs(source) {
            const value = source && source.value !== undefined ? source.value.trim() : faaGetSearchValue();
            faaSetSearchQuery(value);
        }

        function faaExtractRowsFromTable() {
            const tbody = document.querySelector('#feedbackTable tbody');
            if (!tbody) {
                return [];
            }
            return Array.from(tbody.querySelectorAll('tr')).map(function(tr) {
                return tr.cloneNode(true);
            });
        }

        function faaFilterRows(rows) {
            const q = faaSearchQuery.toLowerCase();
            if (!q) {
                return rows;
            }
            return rows.filter(function(tr) {
                return tr.textContent.toLowerCase().indexOf(q) !== -1;
            });
        }

        function faaBuildPagination(totalPages) {
            const list = document.getElementById('faaPaginationList');
            if (!list) {
                return;
            }
            list.innerHTML = '';
            if (totalPages <= 1) {
                return;
            }

            const addItem = function(label, page, disabled, active) {
                const li = document.createElement('li');
                li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                const a = document.createElement('a');
                a.className = 'page-link';
                a.href = 'javascript:void(0)';
                a.setAttribute('aria-label', label);
                if (!disabled && !active) {
                    a.addEventListener('click', function() {
                        faaCurrentPage = page;
                        faaRenderTablePage();
                    });
                }
                a.innerHTML = label;
                li.appendChild(a);
                list.appendChild(li);
            };

            addItem('<i class="bi bi-chevron-left" aria-hidden="true"></i>', faaCurrentPage - 1, faaCurrentPage === 1, false);

            const maxVisible = 6;
            let start = Math.max(1, faaCurrentPage - 2);
            let end = Math.min(totalPages, start + maxVisible - 1);
            start = Math.max(1, end - maxVisible + 1);

            if (start > 1) {
                addItem('1', 1, false, faaCurrentPage === 1);
                if (start > 2) {
                    const ell = document.createElement('li');
                    ell.className = 'page-item disabled';
                    ell.innerHTML = '<span class="page-link">…</span>';
                    list.appendChild(ell);
                }
            }

            for (let i = start; i <= end; i++) {
                addItem(String(i), i, false, i === faaCurrentPage);
            }

            if (end < totalPages) {
                if (end < totalPages - 1) {
                    const ell = document.createElement('li');
                    ell.className = 'page-item disabled';
                    ell.innerHTML = '<span class="page-link">…</span>';
                    list.appendChild(ell);
                }
                addItem(String(totalPages), totalPages, false, faaCurrentPage === totalPages);
            }

            addItem('<i class="bi bi-chevron-right" aria-hidden="true"></i>', faaCurrentPage + 1, faaCurrentPage === totalPages, false);
        }

        function faaRenderTablePage() {
            const tbody = document.querySelector('#feedbackTable tbody');
            const footer = document.getElementById('faaTableFooter');
            const totalEl = document.getElementById('faaTotalRecords');

            if (!tbody || !faaAllRows.length) {
                if (footer) {
                    footer.classList.toggle('d-none', true);
                }
                return;
            }

            const filtered = faaFilterRows(faaAllRows);
            const total = filtered.length;
            const totalPages = Math.max(1, Math.ceil(total / faaPageSize));

            if (faaCurrentPage > totalPages) {
                faaCurrentPage = totalPages;
            }
            if (faaCurrentPage < 1) {
                faaCurrentPage = 1;
            }

            const start = (faaCurrentPage - 1) * faaPageSize;
            const pageRows = filtered.slice(start, start + faaPageSize);

            tbody.innerHTML = '';
            pageRows.forEach(function(tr, idx) {
                const row = tr.cloneNode(true);
                const snoCell = row.querySelector('.faa-sno');
                if (snoCell) {
                    snoCell.textContent = start + idx + 1;
                }
                tbody.appendChild(row);
            });

            if (totalEl) {
                totalEl.textContent = total.toLocaleString();
            }
            if (footer) {
                footer.classList.toggle('d-none', total === 0);
            }

            faaBuildPagination(totalPages);
        }

        function faaInitTableUi() {
            faaAllRows = faaExtractRowsFromTable();
            const pageSizeSelect = document.getElementById('faaPageSizeSelect');
            if (pageSizeSelect) {
                faaPageSize = parseInt(pageSizeSelect.value, 10) || 200;
            }
            faaCurrentPage = 1;
            faaRenderTablePage();
        }

        function loadFeedbackData() {
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('tableContainer').style.display = 'none';
            const footer = document.getElementById('faaTableFooter');
            if (footer) {
                footer.classList.add('d-none');
            }

            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            const params = new URLSearchParams();
            for (const [key, value] of formData) {
                params.append(key, value);
            }
            params.append('_', Date.now());

            fetch(`{{ $fr['average'] }}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const newProgramSelect = doc.querySelector('select[name="program_name"]');
                    if (newProgramSelect) {
                        const currentProgramSelect = document.querySelector('select[name="program_name"]');
                        const currentVal = currentProgramSelect.value;
                        // Destroy Choices instance before touching innerHTML
                        if (currentProgramSelect._choicesInstance) {
                            currentProgramSelect._choicesInstance.destroy();
                            currentProgramSelect._choicesInstance = null;
                        }
                        currentProgramSelect.innerHTML = newProgramSelect.innerHTML;
                        // Restore previously selected value if still present
                        if (currentVal) { currentProgramSelect.value = currentVal; }
                        // Reinitialise Choices.js
                        initAvgProgramChoices();
                    }

                    // Extract the table content from the response
                    const newTableContainer = doc.querySelector('#tableContainer');
                    const newProgramTitle = doc.querySelector('.faa-program-title-wrap h6, .text-center h6');

                    if (newTableContainer) {
                        document.getElementById('tableContainer').innerHTML = newTableContainer.innerHTML;
                    }

                    if (newProgramTitle) {
                        let programTitleElement = document.querySelector('.faa-program-title-wrap h6, .text-center h6');
                        if (programTitleElement) {
                            programTitleElement.textContent = newProgramTitle.textContent;
                        }
                    }

                    const newExportGroup = doc.querySelector('.export-btn-group');
                    if (newExportGroup) {
                        const currentExportGroup = document.querySelector('.export-btn-group');
                        if (currentExportGroup) {
                            currentExportGroup.innerHTML = newExportGroup.innerHTML;
                        }
                    }

                    updateExportLinks();
                    faaUpdateFilterSelectStyles();
                    faaInitTableUi();

                    document.getElementById('loadingSpinner').style.display = 'none';
                    document.getElementById('tableContainer').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error loading feedback data:', error);
                    document.getElementById('loadingSpinner').style.display = 'none';
                    document.getElementById('tableContainer').style.display = 'block';
                    document.getElementById('tableContainer').innerHTML =
                        '<div class="alert alert-danger text-center rounded-3">Error loading data. Please try again.</div>';
                });
        }

        function loadProgramsByCourseType(courseType) {
            fetch(`{{ $fr['average'] }}?course_type=${courseType}&_=${Date.now()}`, {
                    headers: {
                        'Cache-Control': 'no-cache'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newProgramSelect = doc.querySelector('select[name="program_name"]');

                    if (newProgramSelect) {
                        const currentProgramSelect = document.querySelector('select[name="program_name"]');
                        if (currentProgramSelect._choicesInstance) {
                            currentProgramSelect._choicesInstance.destroy();
                            currentProgramSelect._choicesInstance = null;
                        }
                        currentProgramSelect.innerHTML = newProgramSelect.innerHTML;
                        initAvgProgramChoices();
                    }
                })
                .catch(error => console.error('Error loading programs:', error));
        }

        document.addEventListener('DOMContentLoaded', function() {
            const currentCourseRadio = document.querySelector('input[name="course_type"][value="current"]');
            const archivedCourseRadio = document.querySelector('input[name="course_type"][value="archived"]');

            if (!currentCourseRadio.checked && !archivedCourseRadio.checked) {
                currentCourseRadio.checked = true;
            }

            if (typeof flatpickr !== 'undefined') {
                const fromDateEl = document.getElementById('fromDate');
                const toDateEl = document.getElementById('toDate');
                const initialFrom = fromDateEl?.value || '';
                const initialTo = toDateEl?.value || '';
                const defaultDates = (initialFrom && initialTo) ? [initialFrom, initialTo] : [];

                window.faaTimePeriodPicker = flatpickr('#faa_time_period_picker', {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    showMonths: 2,
                    static: false,
                    locale: { rangeSeparator: ' - ' },
                    defaultDate: defaultDates,
                    onReady: function(_selectedDates, _dateStr, instance) {
                        instance.calendarContainer.classList.add('faa-flatpickr-theme');
                    },
                    onChange: function(selectedDates) {
                        if (selectedDates.length === 2) {
                            fromDateEl.value = window.faaTimePeriodPicker.formatDate(selectedDates[0], 'Y-m-d');
                            toDateEl.value = window.faaTimePeriodPicker.formatDate(selectedDates[1], 'Y-m-d');
                            loadFeedbackData();
                        } else if (selectedDates.length === 0) {
                            fromDateEl.value = '';
                            toDateEl.value = '';
                            loadFeedbackData();
                        }
                    }
                });
            }

            const pageSizeSelect = document.getElementById('faaPageSizeSelect');
            if (pageSizeSelect) {
                pageSizeSelect.addEventListener('change', function() {
                    faaPageSize = parseInt(this.value, 10) || 200;
                    faaCurrentPage = 1;
                    faaRenderTablePage();
                });
            }

            const searchInput = document.getElementById('faaTableSearch');
            const searchTrigger = document.getElementById('faaSearchTrigger');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    faaSyncSearchInputs(this);
                });
            }

            if (searchTrigger) {
                const searchDropdown = searchTrigger.closest('.dropdown');
                if (searchDropdown) {
                    searchDropdown.addEventListener('shown.bs.dropdown', function() {
                        if (searchInput) {
                            searchInput.focus();
                        }
                    });
                }
            }

            setTimeout(updateExportLinks, 200);

            faaUpdateFilterSelectStyles();

            document.getElementById('filterForm').addEventListener('change', function(e) {
                if (e.target && (e.target.id === 'faaProgramSelect' || e.target.id === 'faaFacultySelect')) {
                    faaUpdateFilterSelectStyles();
                    loadFeedbackData();
                    setTimeout(updateExportLinks, 300);
                }
            });

            document.querySelectorAll('input[name="course_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    document.getElementById('loadingSpinner').style.display = 'block';
                    document.getElementById('tableContainer').style.display = 'none';
                    loadProgramsByCourseType(this.value);
                    setTimeout(function() {
                        loadFeedbackData();
                    }, 300);
                    setTimeout(updateExportLinks, 600);
                });
            });

            document.getElementById('filterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                loadFeedbackData();
            });

            document.getElementById('loadingSpinner').style.display = 'none';

            if (document.querySelector('#feedbackTable tbody tr')) {
                faaInitTableUi();
            }

            setTimeout(function() {
                faaUpdateFilterSelectStyles();
                loadFeedbackData();
            }, 150);
        });

        document.addEventListener('ajaxComplete', function() {
            setTimeout(updateExportLinks, 300);
        });
    </script>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
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
function initAvgProgramChoices() {
    const el = document.getElementById('avgProgramSelect');
    if (!el || typeof window.Choices === 'undefined') return;
    if (el._choicesInstance) { el._choicesInstance.destroy(); el._choicesInstance = null; }
    el._choicesInstance = new Choices(el, makeChoicesConfig('Search programs...'));
    el.addEventListener('change', function() { setTimeout(updateExportLinks, 100); });
}
function initAvgFacultyChoices() {
    const el = document.getElementById('avgFacultySelect');
    if (!el || typeof window.Choices === 'undefined') return;
    if (el._choicesInstance) { el._choicesInstance.destroy(); el._choicesInstance = null; }
    el._choicesInstance = new Choices(el, makeChoicesConfig('Search faculty...'));
    el.addEventListener('change', function() { setTimeout(updateExportLinks, 100); });
}
document.addEventListener('DOMContentLoaded', function() {
    initAvgProgramChoices();
    initAvgFacultyChoices();
});
</script>
@endpush
@endsection
