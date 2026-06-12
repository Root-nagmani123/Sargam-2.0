@php
    $fr = $fr ?? $feedbackReportRoutes ?? \App\Support\FeedbackReportRouteRegistry::forRequest();
@endphp
@extends('admin.layouts.master')

@section('title', 'Faculty Feedback with Comments All Details - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<style>
#programSelect + .choices .choices__inner {
    min-height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 2.25rem 0.375rem 0.75rem;
    font-size: 0.85rem;
    border: 1px solid #d0d7de;
    border-radius: var(--bs-border-radius, 0.375rem);
    background-color: #fff;
    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
}
#programSelect + .choices .choices__inner:focus-within {
    border-color: #af2910;
    box-shadow: 0 0 0 0.2rem rgba(175,41,16,.12);
}
#programSelect + .choices .choices__input {
    font-size: 0.85rem;
}
</style>
@endpush

@section('setup_content')
    <style>
        :root {
            --fb-brand: #af2910;
            --fb-brand-rgb: 175, 41, 16;
            --fb-border: #d0d7de;
        }

        /* ── Filter Card ── */
        .filter-card {
            border: 0;
            border-radius: var(--bs-border-radius-lg);
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            overflow: visible;
        }

        .filter-card .card-header {
            background: var(--fb-brand);
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
            border-color: var(--fb-brand);
            box-shadow: 0 0 0 0.2rem rgba(var(--fb-brand-rgb), .12);
        }

        .feedback-page-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--fb-brand);
        }


        .rating-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.85rem;
            height: 1.85rem;
            padding: 0 0.35rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: var(--bs-border-radius);
        }

        .rating-5 { background-color: var(--bs-success); color: #fff; }
        .rating-4 { background-color: #20c997; color: #fff; }
        .rating-3 { background-color: var(--bs-warning); color: var(--bs-dark); }
        .rating-2 { background-color: #fd7e14; color: #fff; }
        .rating-1 { background-color: var(--bs-danger); color: #fff; }

        .faculty-type-badge {
            font-size: 0.7rem;
            font-weight: 500;
            padding: 0.2rem 0.5rem;
            border-radius: 50rem;
            background: var(--bs-secondary-bg);
            color: var(--bs-secondary-color);
            border: 1px solid var(--bs-border-color);
        }

        .session-badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.55rem;
            border-radius: 50rem;
            background: var(--bs-primary-bg-subtle);
            color: var(--bs-primary-text-emphasis);
            border: 1px solid rgba(var(--bs-primary-rgb), 0.25);
        }

        .suggestions-container { position: relative; }

        .suggestions-list {
            position: absolute;
            top: calc(100% + 0.25rem);
            left: 0;
            right: 0;
            background: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius-lg);
            max-height: 220px;
            overflow-y: auto;
            z-index: 1080;
            display: none;
            box-shadow: var(--bs-box-shadow);
        }

        .suggestion-item {
            padding: 0.5rem 0.85rem;
            cursor: pointer;
            border-bottom: 1px solid var(--bs-border-color-translucent);
            transition: background-color 0.12s ease;
        }

        .suggestion-item:hover,
        .suggestion-item:focus-visible {
            background-color: var(--bs-tertiary-bg);
        }

        .suggestion-item:last-child { border-bottom: none; }

        #loadingSpinner {
            position: fixed;
            inset: 0;
            z-index: 1090;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.06);
            backdrop-filter: blur(2px);
        }

        #loadingSpinner.feedback-loading-visible {
            display: flex !important;
        }

        #loadingSpinner .feedback-loading-inner {
            background: var(--bs-body-bg);
            padding: 1.5rem 2rem;
            border-radius: var(--bs-border-radius-xl);
            box-shadow: var(--bs-box-shadow-lg);
            border: 1px solid var(--bs-border-color-translucent);
            max-width: 90vw;
        }

        .feedback-pagination .page-link {
            font-size: 0.82rem;
            color: var(--fb-brand);
            border-color: var(--fb-border);
            min-width: 2rem;
            text-align: center;
            border-radius: 0.375rem !important;
            margin: 0 0.1rem;
        }

        .feedback-pagination .page-item.active .page-link {
            background-color: var(--fb-brand);
            border-color: var(--fb-brand);
            color: #fff;
            font-weight: 600;
        }

        .feedback-pagination .page-item.disabled .page-link {
            color: #adb5bd;
            border-color: var(--fb-border);
            background-color: #f8f9fa;
        }

        .feedback-pagination .page-link:hover:not(.active) {
            background-color: rgba(175,41,16,.08);
            color: var(--fb-brand);
        }

        .feedback-session-card {
            border-left: 4px solid rgba(var(--fb-brand-rgb), 0.85);
        }

        .empty-state i {
            font-size: 3rem;
            opacity: 0.35;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="container-fluid fdt-master-page py-3 px-3 px-lg-4">
        <div class="no-print">
            <x-breadcrum title="Faculty Feedback with Comments All Details"></x-breadcrum>
        </div>

        <div id="loadingSpinner">
            <div class="feedback-loading-inner text-center">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0 fw-medium text-secondary small">Loading feedback data…</p>
            </div>
        </div>

        <div class="card filter-card mb-3 no-print">
            <div class="card-header">
                <i class="fas fa-sliders-h"></i> Feedback Details
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-sm-6 col-lg-auto">
                        <label class="form-label d-block">Course status</label>
                        <div class="btn-group flex-wrap" role="group" aria-label="Course status">
                            <input class="btn-check course-type-radio" type="radio" name="course_type" value="current" id="current"
                                {{ ($courseType ?? 'current') == 'current' ? 'checked' : '' }}>
                            <label class="btn btn-outline-danger btn-sm px-3" for="current">
                                <i class="fas fa-play-circle me-1 opacity-75"></i>Current
                            </label>
                            <input class="btn-check course-type-radio" type="radio" name="course_type" value="archived" id="archived"
                                {{ ($courseType ?? 'current') == 'archived' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary btn-sm px-3" for="archived">
                                <i class="fas fa-archive me-1 opacity-75"></i>Archived
                            </label>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="programSelect" class="form-label">Program name</label>
                        <select class="form-select" id="programSelect" name="program_id">
                            <option value="">All Programs</option>
                            @foreach ($programs as $key => $program)
                                <option value="{{ $key }}" {{ $currentProgram == $key ? 'selected' : '' }}>
                                    {{ $program }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-xl-2">
                        <label for="fromDate" class="form-label">From date</label>
                        <input type="date" id="fromDate" class="form-control" name="from_date"
                            value="{{ $fromDate ?? '' }}" />
                    </div>

                    <div class="col-6 col-md-3 col-xl-2">
                        <label for="toDate" class="form-label">To date</label>
                        <input type="date" id="toDate" class="form-control" name="to_date"
                            value="{{ $toDate ?? '' }}" />
                    </div>

                    @if (!hasRole('Internal Faculty') && !hasRole('Guest Faculty'))
                        <div class="col-12 col-md-6 col-xl-auto">
                            <label class="form-label d-block">Faculty type</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input faculty-type-checkbox" type="checkbox"
                                        name="faculty_type[]" value="2" id="faculty_type_guest"
                                        {{ in_array('2', $selectedFacultyTypes ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="faculty_type_guest">Guest</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input faculty-type-checkbox" type="checkbox" name="faculty_type[]" value="1" id="faculty_type_internal" {{ in_array('1', $selectedFacultyTypes ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="faculty_type_internal">Internal</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl suggestions-container">
                            <label for="facultySearch" class="form-label">Faculty name</label>
                            <input type="text" id="facultySearch" class="form-control" name="faculty_name"
                                value="{{ $currentFaculty ?? '' }}" placeholder="Search by name…" autocomplete="off" />
                            <div class="suggestions-list shadow" id="facultySuggestions">
                                @if ($facultySuggestions->isNotEmpty())
                                    @foreach ($facultySuggestions as $faculty)
                                        <option value="{{ $faculty->full_name }}" {{ ($currentFaculty ?? '') === $faculty->full_name ? 'selected' : '' }}>{{ $faculty->full_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        @endif

                        <button type="button" class="btn programme-dt-btn-reset flex-shrink-0" id="resetButton">Reset Filters</button>
                    </div>

            </div>
            <div class="card-footer bg-body-tertiary border-top py-3 px-3 d-flex flex-wrap gap-2 align-items-center justify-content-end">
                <div class="btn-group rounded-1" role="group" aria-label="Print or download PDF">
                    <button type="button" class="btn btn-outline-primary rounded-1 px-3 d-inline-flex align-items-center justify-content-center gap-1 rounded-0 rounded-start-1" onclick="printFeedbackDetails()" title="Print report or choose Save as PDF in print dialog">
                        <span class="material-symbols-rounded" style="font-size: 1.1rem;">print</span>
                        <span>Print</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger rounded-1 px-3 d-inline-flex align-items-center justify-content-center gap-1 rounded-0 rounded-end-1" onclick="exportToPDF()" title="Download PDF">
                        <span class="material-symbols-rounded" style="font-size: 1.1rem;">picture_as_pdf</span>
                        <span>PDF</span>
                    </button>
                </div>
                <button type="button" class="btn btn-success rounded-1 px-3 d-inline-flex align-items-center gap-1" onclick="exportToExcel()" title="Export to Excel">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">table_view</span>
                    <span>Export Excel</span>
                </button>
                <button type="button" class="btn btn-outline-secondary rounded-1 px-3 d-inline-flex align-items-center gap-1" id="resetButton">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">refresh</span>
                    <span>Reset filters</span>
                </button>
                @if (empty($hidePendingFeedbackAdminLink))
                <a href="{{ route('admin.feedback.pending.students') }}" class="btn btn-warning text-dark rounded-1 d-inline-flex align-items-center gap-1 px-3">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">pending_actions</span>
                    <span>Pending feedback (students)</span>
                </a>
                @endif
            </div>
        </div>

                <span class="visually-hidden" id="feedbackRefreshTime">Data refreshed: {{ $refreshTime ?? now()->format('d-M-Y H:i') }}</span>

                <div id="contentContainer" class="fdt-content">
                    @if ($groupedData->isEmpty())
                        <div class="empty-state text-center py-5 px-3">
                            <i class="bi bi-clipboard-data d-block mb-3 text-body-secondary fdt-empty-icon" aria-hidden="true"></i>
                            <h5 class="fw-semibold text-body-secondary">No feedback data found</h5>
                            <p class="text-muted small mb-0 mx-auto" style="max-width: 28rem;">Try adjusting your filters or program selection to see results.</p>
                        </div>
                    @else
                        @php $rowSno = ($currentPage - 1) * ($perPage ?? 10); @endphp
                        <div class="programme-dt-panel fdt-table-panel">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 w-100 programme-dt-table fdt-feedback-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">S. No.</th>
                                            <th scope="col">OT Code</th>
                                            <th scope="col">OT Name</th>
                                            <th scope="col">Program Name</th>
                                            <th scope="col" class="text-center">Content</th>
                                            <th scope="col" class="text-center">Presentation</th>
                                            <th scope="col">Remarks</th>
                                            <th scope="col">Feedback Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($groupedData as $groupKey => $group)
                                            @php [$programName] = explode('|', $groupKey); @endphp
                                            @foreach ($group as $item)
                                                @php $rowSno++; @endphp
                                                <tr>
                                                    <td class="text-secondary">{{ $rowSno }}</td>
                                                    <td><span class="fdt-ot-code">{{ $item['ot_code'] }}</span></td>
                                                    <td>{{ $item['ot_name'] }}</td>
                                                    <td class="fdt-col-program">{{ $programName }}</td>
                                                    <td class="text-center"><span class="fdt-rating"><span class="fdt-rating-value">{{ $item['content'] }}</span><span class="fdt-rating-max">/10</span></span></td>
                                                    <td class="text-center"><span class="fdt-rating"><span class="fdt-rating-value">{{ $item['presentation'] }}</span><span class="fdt-rating-max">/10</span></span></td>
                                                    <td>@if (!empty($item['remark']))<div class="remark-text">{{ $item['remark'] }}</div>@else<span class="text-muted">—</span>@endif</td>
                                                    <td class="text-secondary">{{ $item['feedback_date'] }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="fdt-pagination-wrap programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 px-3 px-md-4 py-3 border-top">
                            @if ($totalPages > 1)
                                <nav aria-label="Feedback pagination" class="programme-dt-pagination">
                                    <ul class="pagination feedback-pagination flex-wrap gap-1 mb-0">
                                        <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                            <a class="page-link" href="javascript:void(0)" onclick="goToPage({{ $currentPage - 1 }})" aria-label="Previous">
                                                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                            </a>
                                        </li>

                                        @php
                                            $startPage = max(1, $currentPage - 2);
                                            $endPage = min($totalPages, $currentPage + 2);
                                        @endphp

                                        @for ($i = $startPage; $i <= $endPage; $i++)
                                            <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                                                <a class="page-link" href="javascript:void(0)" onclick="goToPage({{ $i }})">{{ $i }}</a>
                                            </li>
                                        @endfor

                                        <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
                                            <a class="page-link" href="javascript:void(0)" onclick="goToPage({{ $currentPage + 1 }})" aria-label="Next">
                                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            @else
                                <div></div>
                            @endif

                            <div class="fdt-records-info programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto text-secondary small">
                                <label class="d-inline-flex align-items-center gap-2 mb-0" for="fdtPageSizeSelect">
                                    <span>Showing</span>
                                    <select class="form-select form-select-sm fdt-page-size-select" id="fdtPageSizeSelect" aria-label="Items per page">
                                        @foreach ([10, 25, 50, 100] as $size)
                                            <option value="{{ $size }}" {{ ($perPage ?? 10) == $size ? 'selected' : '' }}>{{ $size }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <span>of <strong class="text-body">{{ number_format($totalRecords) }}</strong> items</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

        <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const loadingSpinner = document.getElementById('loadingSpinner');
                const contentContainer = document.getElementById('contentContainer');
                const programSelect = document.getElementById('programSelect');
                const facultySearch = document.getElementById('facultySearch');
                const facultyFilterWrap = document.getElementById('fdtFacultyFilterWrap');
                const tableSearch = document.getElementById('fdtTableSearch');
                const tableSearchMobile = document.getElementById('fdtTableSearchMobile');
                const searchTrigger = document.getElementById('fdtSearchTrigger');
                const resetButton = document.getElementById('resetButton');
                const fromDateEl = document.getElementById('fromDate');
                const toDateEl = document.getElementById('toDate');
                let currentPage = {{ $currentPage }};
                let programChoices = null;

                // Choices.js config
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

                function initProgramChoices() {
                    const el = document.getElementById('programSelect');
                    if (!el || typeof window.Choices === 'undefined') return;
                    if (programChoices) { programChoices.destroy(); programChoices = null; }
                    programChoices = new Choices(el, makeChoicesConfig('Search programs...'));
                }

                // Register change listener once (not inside initProgramChoices to avoid duplicates)
                const _programEl = document.getElementById('programSelect');
                if (_programEl) {
                    _programEl.addEventListener('change', function() {
                        loadFeedbackData(1);
                    });
                }

                // Get all filter inputs (programSelect handled via Choices listener)
                const filterInputs = [
                    document.getElementById('fromDate'),
                    document.getElementById('toDate'),
                    ...document.querySelectorAll('.course-type-radio'),
                    facultySearch
                ];

                // Function to load feedback data with current filters
                function loadFeedbackData(page = 1) {
                    currentPage = page;

                    // Show loading spinner
                    loadingSpinner.classList.add('feedback-loading-visible');
                    contentContainer.style.opacity = '0.5';

                    // Collect filter values
                    const params = fdtCollectFilterParams();
                    params.append('page', page);

                    // Course type
                    const courseType = document.querySelector('input[name="course_type"]:checked');
                    if (courseType) {
                        params.append('course_type', courseType.value);
                    }

                    // Faculty type (checkboxes)
                    const facultyTypeCheckboxes = document.querySelectorAll('.faculty-type-checkbox:checked');
                    facultyTypeCheckboxes.forEach(cb => {
                        params.append('faculty_type[]', cb.value);
                    });

                    // Make AJAX request - GET with query parameters
                    fetch('{{ $fr['details'] }}?' + params.toString(), {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                updateContent(data);
                                updateFilters(data);
                            } else {
                                throw new Error(data.error || 'Failed to load data');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading feedback data:', error);
                            showError('Error loading data. Please try again.');
                        })
                        .finally(() => {
                            loadingSpinner.classList.remove('feedback-loading-visible');
                            contentContainer.style.opacity = '1';
                        });
                }

                // Function to update content with new data
                function updateContent(data) {
                    if (data.groupedData && Object.keys(data.groupedData).length > 0) {
                        let html = '';

                        // Global row counter — continues across groups on this page
                        // e.g. page 3 starts at row 21, page 2 at row 11
                        const perPage = 10;
                        let rowCounter = (parseInt(data.currentPage, 10) - 1) * perPage;

                        Object.entries(data.groupedData).forEach(([groupKey, group]) => {
                            const [programName, facultyName, topicName] = groupKey.split('|');
                            const firstRecord = group[0];
                            // Capture counter start for this group before incrementing
                            const groupStartRow = rowCounter;
                            rowCounter += group.length;

                            html += `
                    <div class="session-header feedback-session-card card border-0 shadow-sm mb-4">
                        <div class="card-body p-3 p-md-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="small text-uppercase text-muted fw-semibold mb-1">Course</div>
                                    <div class="fw-semibold text-body">${programName}</div>
                                    <div class="mt-2"><span class="session-badge">${firstRecord.course_status || 'Unknown'}</span></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-uppercase text-muted fw-semibold mb-1">Faculty</div>
                                    <div class="fw-semibold text-body">${facultyName}</div>
                                    <div class="mt-2"><span class="faculty-type-badge">${firstRecord.faculty_type || ''}</span></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-uppercase text-muted fw-semibold mb-1">Topic</div>
                                    <div class="fw-semibold text-body">${topicName}</div>
                                    ${firstRecord.start_date ? `
                                        <div class="small text-muted mt-2">
                                            <i class="fas fa-clock me-1 opacity-75"></i>
                                            <span class="fw-medium text-body-secondary">Session:</span>
                                            ${firstRecord.start_date}
                                            ${firstRecord.end_date ? `<span class="text-muted">– ${firstRecord.end_date}</span>` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-4 rounded-3 border shadow-sm">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-uppercase text-secondary">
                                    <th scope="col" class="ps-3" style="width:4%">#</th>
                                    <th scope="col" style="width:18%">OT name</th>
                                    <th scope="col" style="width:10%">OT code</th>
                                    <th scope="col" class="text-center" style="width:10%">Content</th>
                                    <th scope="col" class="text-center" style="width:10%">Presentation</th>
                                    <th scope="col" style="width:33%">Remarks</th>
                                    <th scope="col" class="pe-3" style="width:15%">Feedback date</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                ${group.map((item, index) => `
                                    <tr>
                                        <td class="ps-3 text-body-secondary">${groupStartRow + index + 1}</td>
                                        <td class="fw-medium">${item.ot_name || ''}</td>
                                        <td><code class="small bg-body-secondary px-2 py-1 rounded">${item.ot_code || ''}</code></td>
                                        <td class="text-center">
                                            <span class="rating-badge rating-${item.content}">${item.content}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="rating-badge rating-${item.presentation}">${item.presentation}</span>
                                        </td>
                                        <td>
                                            ${item.remark ? `<div class="remark-text">${item.remark}</div>` : `<span class="text-muted fst-italic">No remarks</span>`}
                                        </td>
                                        <td class="pe-3"><small class="text-body-secondary">${item.feedback_date || ''}</small></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <hr class="my-4 text-secondary opacity-25">
                `;
                        });

                        // Add pagination if needed
                        if (data.totalRecords > 10) {
                            html += generatePagination(data.currentPage, data.totalPages, data.totalRecords);
                        }

                        contentContainer.innerHTML = html;

                        const refreshElement = document.getElementById('feedbackRefreshTime');
                        if (refreshElement && data.refreshTime) {
                            refreshElement.textContent = `Data refreshed: ${data.refreshTime}`;
                        }
                    } else {
                        contentContainer.innerHTML = `
                <div class="empty-state text-center py-5 px-3">
                    <i class="bi bi-clipboard-data d-block mb-3 text-body-secondary fdt-empty-icon" aria-hidden="true"></i>
                    <h5 class="fw-semibold text-body-secondary">No feedback data found</h5>
                    <p class="text-muted small mb-0 mx-auto" style="max-width:28rem">Try adjusting your filters or program selection to see results.</p>
                </div>
            `;
                    }
                }

                // Function to generate pagination HTML
                function generatePagination(currentPage, totalPages, totalRecords) {
                    currentPage  = parseInt(currentPage,  10);
                    totalPages   = parseInt(totalPages,   10);
                    totalRecords = parseInt(totalRecords, 10);

                    // Build the set of page numbers to display (with null = ellipsis)
                    function pageNumbers(cur, total) {
                        if (total <= 7) {
                            return Array.from({ length: total }, (_, i) => i + 1);
                        }
                        const delta  = 2;          // pages either side of current
                        const left   = cur - delta;
                        const right  = cur + delta;
                        const pages  = [];

                        // always show first page
                        pages.push(1);

                        // ellipsis after 1 if window doesn't start at 2
                        if (left > 2) pages.push(null);

                        for (let i = Math.max(2, left); i <= Math.min(total - 1, right); i++) {
                            pages.push(i);
                        }

                        // ellipsis before last if window doesn't end at total-1
                        if (right < total - 1) pages.push(null);

                        // always show last page
                        pages.push(total);

                        return pages;
                    }

                    let items = '';

                    // First + Prev
                    items += `<li class="page-item ${currentPage == 1 ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0)" onclick="goToPage(1)" aria-label="First"><i class="fas fa-angle-double-left"></i></a></li>`;
                    items += `<li class="page-item ${currentPage == 1 ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0)" onclick="goToPage(${currentPage - 1})" aria-label="Previous"><i class="fas fa-angle-left"></i></a></li>`;

                    // Numbered pages + ellipsis
                    pageNumbers(currentPage, totalPages).forEach(function(p) {
                        if (p === null) {
                            items += `<li class="page-item disabled"><span class="page-link px-1 border-0 bg-transparent text-muted">…</span></li>`;
                        } else {
                            items += `<li class="page-item ${p == currentPage ? 'active' : ''}">
                                <a class="page-link" href="javascript:void(0)" onclick="goToPage(${p})">${p}</a></li>`;
                        }
                    });

                    // Next + Last
                    items += `<li class="page-item ${currentPage == totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0)" onclick="goToPage(${currentPage + 1})" aria-label="Next"><i class="fas fa-angle-right"></i></a></li>`;
                    items += `<li class="page-item ${currentPage == totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0)" onclick="goToPage(${totalPages})" aria-label="Last"><i class="fas fa-angle-double-right"></i></a></li>`;

                    let pagination = `
            <nav aria-label="Feedback pagination" class="pb-2">
                <ul class="pagination feedback-pagination justify-content-center mb-0">
                    ${items}
                </ul>
            </nav>
            
            <div class="text-center text-body-secondary mt-3">
                <small class="badge rounded-pill text-bg-light border fw-normal px-3 py-2">
                    Showing <strong class="text-body">${((currentPage - 1) * 10) + 1}</strong>
                    – <strong class="text-body">${Math.min(currentPage * 10, totalRecords)}</strong>
                    of <strong class="text-body">${totalRecords}</strong> records
                </small>
            </div>
        `;

                    return pagination;
                }

                // Function to update filters with new data
                function updateFilters(data) {
                    // Update program dropdown via Choices.js.
                    // IMPORTANT: do NOT call setChoiceByValue here — it fires a native 'change' event
                    // which would trigger loadFeedbackData(1) and reset the page back to 1 after every
                    // AJAX response. Instead, embed selected:true in the choices array so setChoices
                    // marks the correct option internally without dispatching any change event.
                    if (programChoices && data.programs) {
                        const currentProgram = data.currentProgram ? String(data.currentProgram) : '';
                        const newChoices = [
                            { value: '', label: 'All Programs', placeholder: true, selected: currentProgram === '' }
                        ];
                        Object.entries(data.programs).forEach(([key, value]) => {
                            newChoices.push({ value: String(key), label: value, selected: String(key) === currentProgram });
                        });
                        programChoices.setChoices(newChoices, 'value', 'label', true);
                    }

                    if (data.selectedFacultyTypes && data.selectedFacultyTypes.length > 0) {
                        if (data.facultySuggestions && data.facultySuggestions.length > 0) {
                            populateFacultyDropdown(data.facultySuggestions, data.currentFaculty || '');
                        }
                    } else if (facultySearch) {
                        facultySearch.innerHTML = '<option value="">Faculty Name</option>';
                        refreshFdtChoice(facultySearch, fdtFacultyChoiceOpts, '');
                    }
                    updateFacultyFilterVisibility();
                    updateFilterDropdownLabels();
                }

                // Function to show error message
                function showError(message) {
                    contentContainer.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 shadow-sm border-0 rounded-3 mx-3 my-3" role="alert">
                <i class="bi bi-exclamation-triangle mt-1" aria-hidden="true"></i>
                <div class="flex-grow-1">${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
                }

                document.querySelectorAll('.faculty-type-checkbox').forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        clearTimeout(fdtFacultyTypeChangeTimer);
                        fdtFacultyTypeChangeTimer = setTimeout(function() {
                            updateFilterDropdownLabels();
                            loadFacultyDropdown(false).then(function() {
                                loadFeedbackData(1);
                            });
                        }, 120);
                    });
                });

                // Event Listeners

                contentContainer.addEventListener('change', function(event) {
                    if (event.target && event.target.classList.contains('fdt-page-size-select')) {
                        currentPerPage = parseInt(event.target.value, 10) || 10;
                        loadFeedbackData(1);
                    }
                });

                filterInputs.forEach(function(input) {
                    if (input) {
                        if (input.type === 'radio') {
                            input.addEventListener('change', function() {
                                loadFeedbackData(1);
                            });
                        } else {
                            input.addEventListener('change', function() {
                                loadFeedbackData(1);
                            });
                        }
                    }
                });

                // Reset button
                resetButton.addEventListener('click', function() {
                    // Reset all filters
                    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                    document.querySelectorAll('input[type="radio"]').forEach(rb => {
                        if (rb.value === 'current') rb.checked = true;
                    });
                    document.querySelectorAll('input[type="date"]').forEach(input => input.value = '');
                    if (programChoices) { programChoices.setChoiceByValue(''); }
                    if (facultySearch) { facultySearch.value = ''; }
                    if (suggestionsList) { suggestionsList.style.display = 'none'; }

                    // Load data with reset filters
                    loadFeedbackData(1);
                });

                // Initialize with current page
                window.goToPage = function(page) {
                    if (page >= 1) {
                        loadFeedbackData(page);
                    }
                };

                // Initial load
                initProgramChoices();
                loadFeedbackData(currentPage);
            });

            function printFeedbackDetailsEscapeHtml(s) {
                if (s === undefined || s === null) {
                    return '';
                }
                return String(s)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function printFeedbackDetails() {
                var root = document.getElementById('contentContainer');
                if (!root || !root.querySelector('table')) {
                    alert('No feedback data found to print.');
                    return;
                }

                var loadingSpinner = document.getElementById('loadingSpinner');
                if (loadingSpinner) {
                    loadingSpinner.classList.add('feedback-loading-visible');
                }

                var params = fdtCollectFilterParams();
                params.append('for_print', '1');
                params.append('page', '1');

                fetch('{{ $fr['details'] }}?' + params.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (!data.success || !data.groupedData || Object.keys(data.groupedData).length === 0) {
                        alert('No feedback data found to print.');
                        return;
                    }

                    var tableHtml = fdtBuildPrintTableHtml(data.groupedData);
                    var totalRecords = Number(data.totalRecords || 0);

                var progSel = document.getElementById('programSelect');
                var progVal = fdtGetChoiceValue(progSel);
                var progText = progVal ? fdtGetChoiceLabel(progSel, progVal) : '\u2014';
                var courseEl = document.querySelector('input[name="course_type"]:checked');
                var courseLabel = courseEl && courseEl.value === 'archived' ? 'Archived courses' : 'Current courses';
                var fromEl = document.getElementById('fromDate');
                var toEl = document.getElementById('toDate');
                var fromD = fromEl ? fromEl.value : '';
                var toD = toEl ? toEl.value : '';
                var facSearch = document.getElementById('facultySearch');
                var facName = facSearch ? fdtGetChoiceValue(facSearch) : '';
                var ft = fdtGetSelectedFacultyTypes().map(function(value) {
                    return value === '2' ? 'Guest' : 'Internal';
                });
                var facultyTypes = ft.length ? ft.join(', ') : 'All types';
                var tableSearchVal = fdtGetTableSearchValue();

                var printed = new Date().toLocaleDateString('en-IN') + ' ' +
                    new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });

                var emblemUrl = @json(asset('images/ashoka.png'));
                var logoUrl = @json(asset('admin_assets/images/logos/logo.png'));

                var printWindow = window.open('', '_blank');
                if (!printWindow) {
                    alert('Please allow pop-ups to print this report.');
                    return;
                }

                var title = 'Faculty Feedback with Comments \u2014 All Details';
                var metaProgram = printFeedbackDetailsEscapeHtml(progText);
                var metaFaculty = printFeedbackDetailsEscapeHtml(facName);

                var styleBlock =
                    '*,*::before,*::after{box-sizing:border-box}' +
                    'body{font-family:\"Segoe UI\",system-ui,-apple-system,sans-serif;font-size:11px;color:#212529;-webkit-print-color-adjust:exact;print-color-adjust:exact;margin:0;padding:12mm 10mm}' +
                    '.print-header{display:flex;align-items:center;gap:12px;border-bottom:3px solid #004a93;padding-bottom:10px;margin-bottom:12px}' +
                    '.print-header img{height:48px;width:auto;object-fit:contain}' +
                    '.header-text{flex:1}' +
                    '.header-text .line1{font-size:9px;text-transform:uppercase;letter-spacing:.08em;color:#004a93;font-weight:600;margin:0}' +
                    '.header-text .line2{font-size:14px;font-weight:700;text-transform:uppercase;color:#1a1a1a;margin:2px 0 0}' +
                    '.header-text .line3{font-size:9px;color:#555;margin:1px 0 0}' +
                    '.report-title-block{text-align:center;margin-bottom:10px}' +
                    '.report-title-block h2{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin:0 0 4px;color:#1a1a1a}' +
                    '.report-meta{font-size:10px;line-height:1.7;margin:8px 0 12px;color:#333}' +
                    '.report-meta strong{color:#1a1a1a}' +
                    '.feedback-print-wrap .card{border:1px solid #dee2e6;border-radius:6px;margin-bottom:12px;page-break-inside:avoid}' +
                    '.feedback-print-wrap .card-body{padding:10px 12px}' +
                    '.feedback-print-wrap hr{border:0;border-top:1px solid #dee2e6;margin:12px 0}' +
                    '.data-table{width:100%;border-collapse:collapse;font-size:10px;margin-bottom:14px}' +
                    '.data-table th,.data-table td{padding:4px 6px;border:1px solid #bbb;vertical-align:middle}' +
                    '.data-table thead th{background:#004a93;color:#fff;font-weight:600;text-align:left}' +
                    '.data-table .text-center{text-align:center}' +
                    '.fdt-rating-value{font-weight:600;color:#1a1a1a}' +
                    '.fdt-rating-max{color:#667085;font-size:9px}' +
                    '@page{size:A4 portrait;margin:8mm}' +
                    '@media print{body{padding:0}thead{display:table-header-group}tr{page-break-inside:avoid}}';

                var searchMeta = tableSearchVal
                    ? ' &nbsp;|&nbsp; <strong>Search:</strong> ' + printFeedbackDetailsEscapeHtml(tableSearchVal)
                    : '';

                var html =
                    '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>' +
                    printFeedbackDetailsEscapeHtml(title) +
                    ' - LBSNAA</title><style>' + styleBlock + '</style></head><body>' +
                    '<div class="print-header">' +
                    '<img src="' + emblemUrl + '" alt="Emblem">' +
                    '<div class="header-text">' +
                    '<p class="line1">Government of India</p>' +
                    '<p class="line2">OFFICER\'S MESS LBSNAA MUSSOORIE</p>' +
                    '<p class="line3">Lal Bahadur Shastri National Academy of Administration</p>' +
                    '</div>' +
                    '<img src="' + logoUrl + '" alt="LBSNAA Logo" onerror="this.style.display=\'none\'">' +
                    '</div>' +
                    '<div class="report-title-block"><h2>' + printFeedbackDetailsEscapeHtml(title) + '</h2></div>' +
                    '<div class="report-meta">' +
                    '<strong>Program:</strong> ' + metaProgram +
                    ' &nbsp;|&nbsp; <strong>Course status:</strong> ' + courseLabel +
                    ' &nbsp;|&nbsp; <strong>Dates:</strong> ' + (fromD || '\u2014') + ' to ' + (toD || '\u2014') +
                    ' &nbsp;|&nbsp; <strong>Faculty:</strong> ' + (metaFaculty || '\u2014') +
                    ' &nbsp;|&nbsp; <strong>Faculty type:</strong> ' + printFeedbackDetailsEscapeHtml(facultyTypes) +
                    searchMeta +
                    ' &nbsp;|&nbsp; <strong>Total records:</strong> ' + totalRecords.toLocaleString() +
                    ' &nbsp;|&nbsp; <strong>Printed:</strong> ' + printFeedbackDetailsEscapeHtml(printed) +
                    '</div>' +
                    '<div class="feedback-print-wrap">' + tableHtml + '</div>' +
                    '<script>window.addEventListener("load",function(){setTimeout(function(){window.print();},300);});<\/script>' +
                    '</body></html>';

                printWindow.document.open();
                printWindow.document.write(html);
                printWindow.document.close();
                })
                .catch(function(error) {
                    console.error('Error loading print data:', error);
                    alert('Error loading data for print. Please try again.');
                })
                .finally(function() {
                    if (loadingSpinner) {
                        loadingSpinner.classList.remove('feedback-loading-visible');
                    }
                });
            }

            function exportToExcel() {
                const loadingSpinner = document.getElementById('loadingSpinner');

                // Show loading
                loadingSpinner.classList.add('feedback-loading-visible');

                // Collect current filter values
                const params = new URLSearchParams();
                params.append('export_type', 'excel');

                // Add all current filter values
                params.append('program_id', fdtGetChoiceValue(document.getElementById('programSelect')) || '');
                params.append('faculty_name', fdtGetChoiceValue(document.getElementById('facultySearch')) || '');
                params.append('from_date', document.getElementById('fromDate').value || '');
                params.append('to_date', document.getElementById('toDate').value || '');
                params.append('course_type', document.querySelector('input[name="course_type"]:checked')?.value || 'current');

                fdtGetSelectedFacultyTypes().forEach(type => {
                    params.append('faculty_type[]', type);
                });

                // Add CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    params.append('_token', csrfToken);
                }

                // Submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ $fr['details_export'] }}';
                form.style.display = 'none';

                // Add all parameters as hidden inputs
                params.forEach((value, key) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();

                // Hide loading after a delay
                setTimeout(() => {
                    loadingSpinner.classList.remove('feedback-loading-visible');
                }, 2000);
            }

            function exportToPDF() {
                const loadingSpinner = document.getElementById('loadingSpinner');

                // Show loading
                loadingSpinner.classList.add('feedback-loading-visible');

                // Collect current filter values
                const params = new URLSearchParams();
                params.append('export_type', 'pdf');

                // Add all current filter values
                params.append('program_id', fdtGetChoiceValue(document.getElementById('programSelect')) || '');
                params.append('faculty_name', fdtGetChoiceValue(document.getElementById('facultySearch')) || '');
                params.append('from_date', document.getElementById('fromDate').value || '');
                params.append('to_date', document.getElementById('toDate').value || '');
                params.append('course_type', document.querySelector('input[name="course_type"]:checked')?.value || 'current');

                fdtGetSelectedFacultyTypes().forEach(type => {
                    params.append('faculty_type[]', type);
                });

                // Add CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    params.append('_token', csrfToken);
                }

                // Submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ $fr['details_export'] }}';
                form.style.display = 'none';

                // Add all parameters as hidden inputs
                params.forEach((value, key) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();

                // Hide loading after a delay
                setTimeout(() => {
                    loadingSpinner.classList.remove('feedback-loading-visible');
                }, 2000);
            }
        </script>
    @endsection
