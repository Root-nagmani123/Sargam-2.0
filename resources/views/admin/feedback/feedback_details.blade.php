@php
    $fr = $fr ?? $feedbackReportRoutes ?? \App\Support\FeedbackReportRouteRegistry::forRequest();
@endphp
@extends('admin.layouts.master')

@section('title', 'Faculty Feedback with Comments All Details')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
@endpush

@section('setup_content')
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
        <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3 mb-4 no-print">
            <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white shadow-sm mb-0 fdt-status-tabs" role="group" aria-label="Course status">
                <li class="nav-item" role="presentation">
                    <input class="btn-check course-type-radio" type="radio" name="course_type" value="current" id="current"
                        {{ ($courseType ?? 'current') == 'current' ? 'checked' : '' }}>
                    <label class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill mb-0" for="current">Active</label>
                </li>
                <li class="nav-item" role="presentation">
                    <input class="btn-check course-type-radio" type="radio" name="course_type" value="archived" id="archived"
                        {{ ($courseType ?? 'current') == 'archived' ? 'checked' : '' }}>
                    <label class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill mb-0" for="archived">Archived</label>
                </li>
            </ul>
            <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm" onclick="printFeedbackDetails()" title="Print report">
                    <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
                </button>
                <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm" onclick="exportToPDF()" title="Download PDF">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <div class="dropdown">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-1 px-2 py-2" data-bs-toggle="dropdown" aria-label="More actions">
                        <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2">
                        <li><button type="button" class="dropdown-item rounded-1 mx-2 py-2" onclick="exportToExcel()"><i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i>Export Excel</button></li>
                        @if (empty($hidePendingFeedbackAdminLink))
                        <li><a class="dropdown-item rounded-1 mx-2 py-2" href="{{ route('admin.feedback.pending.students') }}"><i class="bi bi-hourglass-split me-2 text-primary"></i>Pending feedback</a></li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <div class="card fdt-dt-card border-0 shadow-sm rounded-3">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar fdt-filters-row no-print w-100">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filters</span>

                        <div class="programme-dt-filter-select">
                            <label for="programSelect" class="visually-hidden">Program Name</label>
                            <select class="form-select" id="programSelect" name="program_id" aria-label="Program name">
                                <option value="">Program Name</option>
                                @foreach ($programs as $key => $program)
                                    <option value="{{ $key }}" {{ $currentProgram == $key ? 'selected' : '' }}>{{ $program }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select fdt-period-filter position-relative">
                            <input type="hidden" id="fromDate" name="from_date" value="{{ $fromDate ?? '' }}">
                            <input type="hidden" id="toDate" name="to_date" value="{{ $toDate ?? '' }}">
                            <label for="fdt_time_period_picker" class="visually-hidden">Time Period</label>
                            <input type="text"
                                id="fdt_time_period_picker"
                                class="form-control fdt-time-period-input"
                                placeholder="Time Period"
                                value=""
                                readonly
                                autocomplete="off"
                                aria-label="Filter by time period">
                            <i class="bi bi-chevron-down fdt-filter-chevron" aria-hidden="true"></i>
                        </div>

                        @if (!hasRole('Internal Faculty') && !hasRole('Guest Faculty'))
                        <div class="dropdown programme-dt-filter-select position-relative">
                            <button type="button" class="btn fdt-filter-select-btn w-100 text-truncate" data-bs-toggle="dropdown" data-bs-auto-close="outside" id="fdtFacultyTypeToggle" aria-expanded="false">Faculty Type</button>
                            <i class="bi bi-chevron-down fdt-filter-chevron" aria-hidden="true"></i>
                            <div class="dropdown-menu shadow-sm border-0 rounded-3 p-3 fdt-faculty-type-menu">
                                <div class="form-check mb-2">
                                    <input class="form-check-input faculty-type-checkbox" type="checkbox" name="faculty_type[]" value="2" id="faculty_type_guest" {{ in_array('2', $selectedFacultyTypes ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="faculty_type_guest">Guest</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input faculty-type-checkbox" type="checkbox" name="faculty_type[]" value="1" id="faculty_type_internal" {{ in_array('1', $selectedFacultyTypes ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="faculty_type_internal">Internal</label>
                                </div>
                            </div>
                        </div>

                        <div class="programme-dt-filter-select fdt-faculty-filter {{ empty($selectedFacultyTypes) ? 'd-none' : '' }}" id="fdtFacultyFilterWrap">
                            <label for="facultySearch" class="visually-hidden">Faculty Name</label>
                            <select class="form-select" id="facultySearch" name="faculty_name" aria-label="Faculty name">
                                <option value="">Faculty Name</option>
                                @if (!empty($selectedFacultyTypes) && $facultySuggestions->isNotEmpty())
                                    @foreach ($facultySuggestions as $faculty)
                                        <option value="{{ $faculty->full_name }}" {{ ($currentFaculty ?? '') === $faculty->full_name ? 'selected' : '' }}>{{ $faculty->full_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        @endif

                        <button type="button" class="btn programme-dt-btn-reset flex-shrink-0" id="resetButton">Reset Filters</button>
                    </div>

                    <div class="fdt-table-search ms-xl-auto flex-shrink-0">
                        <label for="fdtTableSearch" class="programme-dt-search fdt-table-search-bar d-none d-xl-block mb-0">
                            <span class="visually-hidden">Search table</span>
                            <input type="search"
                                id="fdtTableSearch"
                                name="table_search"
                                class="form-control shadow-none"
                                placeholder="Search"
                                value=""
                                autocomplete="off"
                                aria-label="Search table">
                        </label>
                        <div class="dropdown d-xl-none fdt-search-slot">
                            <button type="button"
                                class="btn fdt-search-trigger"
                                id="fdtSearchTrigger"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-label="Search table">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3 fdt-table-search-menu">
                                <label for="fdtTableSearchMobile" class="form-label small text-secondary mb-2">Search</label>
                                <input type="search"
                                    id="fdtTableSearchMobile"
                                    class="form-control shadow-none"
                                    placeholder="Search table..."
                                    autocomplete="off"
                                    aria-label="Search table">
                            </div>
                        </div>
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

    <script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
            function fdtGetChoiceValue(el) {
                if (!el) {
                    return '';
                }
                if (el._fdtChoices) {
                    const val = el._fdtChoices.getValue(true);
                    return Array.isArray(val) ? (val[0] || '') : (val || '');
                }
                return el.value || '';
            }

            function fdtGetSelectedFacultyTypes() {
                return Array.from(document.querySelectorAll('.faculty-type-checkbox:checked')).map(function(cb) {
                    return cb.value;
                });
            }

            function fdtGetChoiceLabel(el, value) {
                if (!el || !value) {
                    return '';
                }
                const option = Array.from(el.options || []).find(opt => String(opt.value) === String(value));
                return option ? option.text : value;
            }

            function fdtGetTableSearchValue() {
                const tableSearch = document.getElementById('fdtTableSearch');
                const tableSearchMobile = document.getElementById('fdtTableSearchMobile');
                if (window.matchMedia('(min-width: 1200px)').matches && tableSearch) {
                    return tableSearch.value.trim();
                }
                if (tableSearchMobile) {
                    return tableSearchMobile.value.trim();
                }
                return tableSearch ? tableSearch.value.trim() : '';
            }

            function fdtCollectFilterParams() {
                const params = new URLSearchParams();
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    params.append('_token', csrfToken);
                }
                params.append('program_id', fdtGetChoiceValue(document.getElementById('programSelect')) || '');
                params.append('faculty_name', fdtGetChoiceValue(document.getElementById('facultySearch')) || '');
                params.append('from_date', document.getElementById('fromDate')?.value || '');
                params.append('to_date', document.getElementById('toDate')?.value || '');
                const courseType = document.querySelector('input[name="course_type"]:checked');
                if (courseType) {
                    params.append('course_type', courseType.value);
                }
                fdtGetSelectedFacultyTypes().forEach(function(type) {
                    params.append('faculty_type[]', type);
                });
                const tableSearchValue = fdtGetTableSearchValue();
                if (tableSearchValue) {
                    params.append('table_search', tableSearchValue);
                }
                return params;
            }

            function fdtEscapeHtml(text) {
                if (text === undefined || text === null) {
                    return '';
                }
                return String(text)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function fdtFormatRating(value) {
                const v = value !== undefined && value !== null ? value : '0';
                return `<span class="fdt-rating"><span class="fdt-rating-value">${v}</span><span class="fdt-rating-max">/10</span></span>`;
            }

            function fdtBuildTableRows(groupedData, startSno) {
                let rows = '';
                let sno = startSno;

                Object.entries(groupedData).forEach(function(entry) {
                    const groupKey = entry[0];
                    const group = entry[1];
                    const programName = groupKey.split('|')[0];
                    group.forEach(function(item) {
                        sno += 1;
                        rows += `
                            <tr>
                                <td class="text-secondary">${sno}</td>
                                <td><span class="fdt-ot-code">${fdtEscapeHtml(item.ot_code || '')}</span></td>
                                <td>${fdtEscapeHtml(item.ot_name || '')}</td>
                                <td class="fdt-col-program">${fdtEscapeHtml(programName)}</td>
                                <td class="text-center">${fdtFormatRating(item.content)}</td>
                                <td class="text-center">${fdtFormatRating(item.presentation)}</td>
                                <td>${item.remark ? `<div class="remark-text">${fdtEscapeHtml(item.remark)}</div>` : `<span class="text-muted">—</span>`}</td>
                                <td class="text-secondary">${fdtEscapeHtml(item.feedback_date || '')}</td>
                            </tr>`;
                    });
                });

                return rows;
            }

            function fdtBuildPrintTableHtml(groupedData) {
                return `
                    <div class="programme-dt-panel fdt-table-panel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 w-100 programme-dt-table fdt-feedback-table data-table">
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
                                <tbody>${fdtBuildTableRows(groupedData, 0)}</tbody>
                            </table>
                        </div>
                    </div>`;
            }

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
                let currentPerPage = {{ $perPage ?? 10 }};
                let fdtTimePeriodPicker = null;
                let fdtFacultyTypeChangeTimer = null;
                let fdtTableSearchTimer = null;

                const fdtPageSizeOptions = [10, 25, 50, 100];

                const fdtFacultyChoiceOpts = {
                    searchEnabled: true,
                    shouldSort: false,
                    itemSelectText: '',
                    allowHTML: false,
                    placeholder: true,
                    placeholderValue: 'Faculty Name',
                    searchPlaceholderValue: 'Search faculty...',
                    noResultsText: 'No results found',
                    position: 'bottom',
                    classNames: {
                        containerOuter: ['choices', 'w-100', 'programme-dt-filter-select'],
                        containerInner: ['choices__inner'],
                        input: ['choices__input', 'form-control', 'border-0', 'shadow-none'],
                        inputCloned: ['choices__input--cloned'],
                        listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
                        itemChoice: ['choices__item--choice'],
                        placeholder: ['choices__placeholder']
                    }
                };

                function destroyFdtChoice(el) {
                    if (!el || !el._fdtChoices) {
                        return;
                    }
                    try {
                        el._fdtChoices.destroy();
                    } catch (error) {
                        /* noop */
                    }
                    el._fdtChoices = null;
                }

                function initFdtChoice(el, opts) {
                    if (!el || typeof Choices === 'undefined') {
                        return null;
                    }
                    destroyFdtChoice(el);
                    const instance = new Choices(el, opts);
                    el._fdtChoices = instance;
                    return instance;
                }

                function setFdtChoiceValue(el, value) {
                    if (!el) {
                        return;
                    }
                    const val = value === undefined || value === null ? '' : String(value);
                    if (el._fdtChoices) {
                        el._fdtChoices.removeActiveItems();
                        if (val) {
                            el._fdtChoices.setChoiceByValue(val);
                        }
                        return;
                    }
                    el.value = val;
                }

                function setFdtMultiChoiceValues(el, values) {
                    if (!el) {
                        return;
                    }
                    const selected = values || [];
                    if (el._fdtChoices) {
                        el._fdtChoices.removeActiveItems();
                        selected.forEach(value => el._fdtChoices.setChoiceByValue(String(value)));
                        return;
                    }
                    Array.from(el.options || []).forEach(option => {
                        option.selected = selected.includes(option.value);
                    });
                }

                function refreshFdtChoice(el, opts, selectedValue) {
                    if (!el) {
                        return;
                    }
                    const isMulti = el.multiple;
                    initFdtChoice(el, opts);
                    if (isMulti) {
                        setFdtMultiChoiceValues(el, Array.isArray(selectedValue) ? selectedValue : []);
                    } else {
                        setFdtChoiceValue(el, selectedValue || '');
                    }
                }

                function updateProgramSelectStyle() {
                    if (!programSelect) {
                        return;
                    }
                    programSelect.classList.toggle('fdt-filter-empty', !programSelect.value);
                }

                if (programSelect) {
                    programSelect.addEventListener('change', updateProgramSelectStyle);
                    updateProgramSelectStyle();
                }

                function initFacultySearchChoice() {
                    if (!facultySearch || facultySearch._fdtChoices) {
                        return;
                    }
                    initFdtChoice(facultySearch, fdtFacultyChoiceOpts);
                }

                function getTableSearchValue() {
                    return fdtGetTableSearchValue();
                }

                function syncTableSearchInputs(source) {
                    const value = source ? source.value : getTableSearchValue();
                    if (tableSearch && tableSearch !== source) {
                        tableSearch.value = value;
                    }
                    if (tableSearchMobile && tableSearchMobile !== source) {
                        tableSearchMobile.value = value;
                    }
                }

                function scheduleTableSearchReload() {
                    clearTimeout(fdtTableSearchTimer);
                    fdtTableSearchTimer = setTimeout(function() {
                        loadFeedbackData(1);
                    }, 350);
                }

                if (getSelectedFacultyTypes().length > 0) {
                    initFacultySearchChoice();
                }

                if (searchTrigger) {
                    const searchDropdown = searchTrigger.closest('.dropdown');
                    if (searchDropdown) {
                        searchDropdown.addEventListener('shown.bs.dropdown', function() {
                            syncTableSearchInputs(tableSearch);
                            if (tableSearchMobile) {
                                tableSearchMobile.focus();
                            }
                        });
                    }
                }

                [tableSearch, tableSearchMobile].forEach(function(input) {
                    if (!input) {
                        return;
                    }
                    input.addEventListener('input', function() {
                        syncTableSearchInputs(input);
                        scheduleTableSearchReload();
                    });
                });

                function updateFilterDropdownLabels() {
                    const facultyToggle = document.getElementById('fdtFacultyTypeToggle');
                    if (facultyToggle) {
                        const selected = getSelectedFacultyTypes().map(function(value) {
                            return value === '2' ? 'Guest' : 'Internal';
                        });
                        facultyToggle.textContent = selected.length ? selected.join(', ') : 'Faculty Type';
                        facultyToggle.classList.toggle('fdt-filter-has-value', selected.length > 0);
                    }
                }

                function updateFacultyFilterVisibility() {
                    if (!facultyFilterWrap) {
                        updateFilterDropdownLabels();
                        return;
                    }
                    const hasTypes = getSelectedFacultyTypes().length > 0;
                    facultyFilterWrap.classList.toggle('d-none', !hasTypes);
                    if (!hasTypes && facultySearch) {
                        facultySearch.innerHTML = '<option value="">Faculty Name</option>';
                        refreshFdtChoice(facultySearch, fdtFacultyChoiceOpts, '');
                    } else if (hasTypes) {
                        initFacultySearchChoice();
                    }
                    updateFilterDropdownLabels();
                }

                function getCurrentPerPage() {
                    const footerSelect = contentContainer.querySelector('.fdt-page-size-select');
                    if (footerSelect) {
                        currentPerPage = parseInt(footerSelect.value, 10) || currentPerPage;
                    }
                    return currentPerPage;
                }

                function buildPageSizeSelectHtml(selectedSize) {
                    return fdtPageSizeOptions.map(function(size) {
                        const selected = Number(selectedSize) === size ? ' selected' : '';
                        return `<option value="${size}"${selected}>${size}</option>`;
                    }).join('');
                }

                if (typeof flatpickr !== 'undefined') {
                    const initialFrom = fromDateEl?.value || '';
                    const initialTo = toDateEl?.value || '';
                    const defaultDates = (initialFrom && initialTo) ? [initialFrom, initialTo] : [];

                    fdtTimePeriodPicker = flatpickr('#fdt_time_period_picker', {
                        mode: 'range',
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'd/m/Y',
                        showMonths: 2,
                        static: false,
                        locale: { rangeSeparator: ' - ' },
                        defaultDate: defaultDates,
                        onReady: function (_selectedDates, _dateStr, instance) {
                            instance.calendarContainer.classList.add('fdt-flatpickr-theme');
                        },
                        onChange: function (selectedDates) {
                            if (selectedDates.length === 2) {
                                if (fromDateEl) {
                                    fromDateEl.value = fdtTimePeriodPicker.formatDate(selectedDates[0], 'Y-m-d');
                                }
                                if (toDateEl) {
                                    toDateEl.value = fdtTimePeriodPicker.formatDate(selectedDates[1], 'Y-m-d');
                                }
                                loadFeedbackData(1);
                            } else if (selectedDates.length === 0) {
                                if (fromDateEl) {
                                    fromDateEl.value = '';
                                }
                                if (toDateEl) {
                                    toDateEl.value = '';
                                }
                                loadFeedbackData(1);
                            }
                        }
                    });
                }

                function formatFdtRating(value) {
                    return fdtFormatRating(value);
                }

                function buildFdtTableRows(groupedData, startSno) {
                    return fdtBuildTableRows(groupedData, startSno);
                }

                function buildFdtTableHtml(groupedData, currentPageNum, perPage) {
                    const startSno = (currentPageNum - 1) * perPage;
                    return `
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
                                    <tbody>${buildFdtTableRows(groupedData, startSno)}</tbody>
                                </table>
                            </div>
                        </div>`;
                }

                function escapeHtml(text) {
                    return fdtEscapeHtml(text);
                }

                function getTableSearchValue() {
                    return fdtGetTableSearchValue();
                }

                function getSelectedFacultyTypes() {
                    return fdtGetSelectedFacultyTypes();
                }

                function toggleFacultyDropdownVisibility() {
                    updateFacultyFilterVisibility();
                }

                function populateFacultyDropdown(faculties, selectedName) {
                    if (!facultySearch) {
                        return;
                    }
                    const current = selectedName !== undefined ? selectedName : fdtGetChoiceValue(facultySearch);
                    let options = '<option value="">Faculty Name</option>';
                    faculties.forEach(function(faculty) {
                        const name = faculty.full_name || faculty;
                        const selected = current && current === name ? ' selected' : '';
                        options += `<option value="${fdtEscapeHtml(name)}"${selected}>${fdtEscapeHtml(name)}</option>`;
                    });
                    facultySearch.innerHTML = options;
                    refreshFdtChoice(facultySearch, fdtFacultyChoiceOpts, current);
                }

                function loadFacultyDropdown(preserveSelection) {
                    const selectedTypes = getSelectedFacultyTypes();
                    updateFacultyFilterVisibility();

                    if (selectedTypes.length === 0) {
                        return Promise.resolve();
                    }

                    const previousSelection = preserveSelection ? fdtGetChoiceValue(facultySearch) : '';
                    const params = new URLSearchParams();
                    selectedTypes.forEach(type => params.append('faculty_type[]', type));

                    return fetch('{{ $fr['comments_suggestions'] }}?' + params.toString(), {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.faculties && data.faculties.length > 0) {
                                populateFacultyDropdown(
                                    data.faculties,
                                    preserveSelection ? previousSelection : ''
                                );
                            } else {
                                populateFacultyDropdown([], '');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading faculty list:', error);
                        });
                }

                // Get all filter inputs (programSelect handled via Choices listener)
                const filterInputs = [
                    programSelect,
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
                    params.append('per_page', getCurrentPerPage());

                    console.log('Loading data with params:', params.toString()); // Debug log

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
                    const perPage = Number(data.perPage || currentPerPage || 10);
                    currentPerPage = perPage;

                    if (data.groupedData && Object.keys(data.groupedData).length > 0) {
                        let html = buildFdtTableHtml(data.groupedData, data.currentPage || 1, perPage);
                        html += generateFooter(data.currentPage, data.totalPages, data.totalRecords, perPage);
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

                function generateFooter(currentPage, totalPages, totalRecords, perPage) {
                    let pageItems = '';
                    const showPagination = totalPages > 1;

                    if (showPagination) {
                        const addPage = (page, active) => {
                            pageItems += `<li class="page-item ${active ? 'active' : ''}"><a class="page-link" href="javascript:void(0)" onclick="goToPage(${page})">${page}</a></li>`;
                        };
                        const addEllipsis = () => {
                            pageItems += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
                        };

                        if (totalPages <= 7) {
                            for (let i = 1; i <= totalPages; i++) {
                                addPage(i, i === currentPage);
                            }
                        } else {
                            addPage(1, currentPage === 1);
                            if (currentPage > 3) {
                                addEllipsis();
                            }
                            const start = Math.max(2, currentPage - 1);
                            const end = Math.min(totalPages - 1, currentPage + 1);
                            for (let i = start; i <= end; i++) {
                                addPage(i, i === currentPage);
                            }
                            if (currentPage < totalPages - 2) {
                                addEllipsis();
                            }
                            addPage(totalPages, currentPage === totalPages);
                        }
                    }

                    const paginationHtml = showPagination ? `
                <nav aria-label="Feedback pagination" class="programme-dt-pagination">
                    <ul class="pagination feedback-pagination flex-wrap gap-1 mb-0">
                        <li class="page-item ${currentPage == 1 ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${currentPage - 1})" aria-label="Previous">
                                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                            </a>
                        </li>
                        ${pageItems}
                        <li class="page-item ${currentPage == totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${currentPage + 1})" aria-label="Next">
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </a>
                        </li>
                    </ul>
                </nav>` : '<div></div>';

                    return `
            <div class="fdt-pagination-wrap programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 px-3 px-md-4 py-3 border-top">
                ${paginationHtml}
                <div class="fdt-records-info programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto text-secondary small">
                    <label class="d-inline-flex align-items-center gap-2 mb-0">
                        <span>Showing</span>
                        <select class="form-select form-select-sm fdt-page-size-select" aria-label="Items per page">
                            ${buildPageSizeSelectHtml(perPage)}
                        </select>
                    </label>
                    <span>of <strong class="text-body">${Number(totalRecords).toLocaleString()}</strong> items</span>
                </div>
            </div>`;
                }

                // Function to update filters with new data
                function updateFilters(data) {
                    console.log('Updating filters with data:', data); // Debug log

                    if (programSelect) {
                        if (data.programs && Object.keys(data.programs).length > 0) {
                            let options = '<option value="">Program Name</option>';
                            Object.entries(data.programs).forEach(([key, value]) => {
                                const selected = key == data.currentProgram ? 'selected' : '';
                                options += `<option value="${key}" ${selected}>${value}</option>`;
                            });
                            programSelect.innerHTML = options;
                        } else {
                            programSelect.innerHTML = '<option value="">No programs available</option>';
                        }
                        programSelect.value = data.currentProgram || '';
                        updateProgramSelectStyle();
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
                    console.log('Resetting filters');
                    document.querySelectorAll('input[type="checkbox"].faculty-type-checkbox').forEach(function(cb) {
                        cb.checked = false;
                    });
                    document.querySelectorAll('input[type="radio"]').forEach(function(rb) {
                        if (rb.value === 'current') {
                            rb.checked = true;
                        }
                    });
                    setFdtChoiceValue(facultySearch, '');
                    if (programSelect) {
                        programSelect.value = '';
                        updateProgramSelectStyle();
                    }
                    if (fromDateEl) {
                        fromDateEl.value = '';
                    }
                    if (toDateEl) {
                        toDateEl.value = '';
                    }
                    if (fdtTimePeriodPicker) {
                        fdtTimePeriodPicker.clear();
                    }
                    if (tableSearch) {
                        tableSearch.value = '';
                    }
                    if (tableSearchMobile) {
                        tableSearchMobile.value = '';
                    }
                    updateFilterDropdownLabels();
                    toggleFacultyDropdownVisibility();
                    loadFeedbackData(1);
                });

                // Initialize with current page
                window.goToPage = function(page) {
                    if (page >= 1) {
                        loadFeedbackData(page);
                    }
                };

                toggleFacultyDropdownVisibility();
                updateFilterDropdownLabels();
                const initFacultyPromise = getSelectedFacultyTypes().length > 0
                    ? loadFacultyDropdown(true)
                    : Promise.resolve();
                initFacultyPromise.then(function() {
                    loadFeedbackData(currentPage);
                });
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
