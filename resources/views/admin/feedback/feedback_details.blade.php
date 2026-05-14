@extends('admin.layouts.master')

@section('title', 'Faculty Feedback with Comments All Details - Sargam | Lal Bahadur')

@section('setup_content')
    <style>
        :root {
            --fb-brand: #af2910;
            --fb-brand-rgb: 175, 41, 16;
        }

        .feedback-page-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--fb-brand);
        }

        .feedback-filter-card .card-header {
            background: linear-gradient(135deg, var(--fb-brand) 0%, #8c2010 100%);
            border: 0;
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
            color: var(--fb-brand);
            border-radius: var(--bs-border-radius) !important;
            margin: 0 0.125rem;
        }

        .feedback-pagination .page-item.active .page-link {
            background-color: var(--fb-brand);
            border-color: var(--fb-brand);
            color: #fff;
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

    <div class="container-fluid py-3 px-3 px-lg-4">
        <div class="no-print">
        <x-breadcrum title="Faculty Feedback with Comments All Details"></x-breadcrum>
        </div>

        <div id="loadingSpinner">
            <div class="feedback-loading-inner text-center">
                <div class="spinner-border text-danger mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0 fw-medium text-secondary small">Loading feedback data…</p>
            </div>
        </div>

        <div class="card feedback-filter-card shadow-sm border-0 rounded-3 mb-4 no-print">
            <div class="card-header text-white py-3 px-4 rounded-top">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <i class="fas fa-filter opacity-75"></i>
                    <span class="fw-semibold">Feedback Details</span>
                    <span class="badge bg-white bg-opacity-25 text-white fw-normal ms-lg-2">Filters apply automatically on change</span>
                </div>
            </div>
            <div class="card-body p-4 bg-body-tertiary bg-opacity-50">
                <div class="row g-3 g-lg-4 align-items-end">
                    <div class="col-12 col-sm-6 col-lg-auto">
                        <span class="form-label text-uppercase small text-muted fw-semibold letter-spacing-1 d-block mb-2">Course status</span>
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
                        <label for="programSelect" class="form-label fw-semibold small mb-1">Program name</label>
                        <select class="form-select shadow-sm" id="programSelect" name="program_id">
                            <option value="">All Programs</option>
                            @foreach ($programs as $key => $program)
                                <option value="{{ $key }}" {{ $currentProgram == $key ? 'selected' : '' }}>
                                    {{ $program }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-xl-2">
                        <label for="fromDate" class="form-label fw-semibold small mb-1">From date</label>
                        <input type="date" id="fromDate" class="form-control shadow-sm" name="from_date"
                            value="{{ $fromDate ?? '' }}" />
                    </div>

                    <div class="col-6 col-md-3 col-xl-2">
                        <label for="toDate" class="form-label fw-semibold small mb-1">To date</label>
                        <input type="date" id="toDate" class="form-control shadow-sm" name="to_date"
                            value="{{ $toDate ?? '' }}" />
                    </div>

                    @if (!hasRole('Internal Faculty') && !hasRole('Guest Faculty'))
                        <div class="col-12 col-md-6 col-xl-auto">
                            <span class="form-label text-uppercase small text-muted fw-semibold d-block mb-2">Faculty type</span>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input faculty-type-checkbox" type="checkbox"
                                        name="faculty_type[]" value="2" id="faculty_type_guest"
                                        {{ in_array('2', $selectedFacultyTypes ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="faculty_type_guest">Guest</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input faculty-type-checkbox" type="checkbox"
                                        name="faculty_type[]" value="1" id="faculty_type_internal"
                                        {{ in_array('1', $selectedFacultyTypes ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="faculty_type_internal">Internal</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl suggestions-container">
                            <label for="facultySearch" class="form-label fw-semibold small mb-1">Faculty name</label>
                            <input type="text" id="facultySearch" class="form-control shadow-sm" name="faculty_name"
                                value="{{ $currentFaculty ?? '' }}" placeholder="Search by name…" autocomplete="off" />
                            <div class="suggestions-list shadow" id="facultySuggestions">
                                @if ($facultySuggestions->isNotEmpty())
                                    @foreach ($facultySuggestions as $faculty)
                                        <div class="suggestion-item" data-value="{{ $faculty->full_name }}">
                                            {{ $faculty->full_name }}
                                            @php
                                                $typeMap = ['1' => 'Internal', '2' => 'Guest'];
                                                $typeDisplay =
                                                    $typeMap[$faculty->faculty_type] ?? ucfirst($faculty->faculty_type);
                                            @endphp
                                            <span class="faculty-type-badge ms-2">{{ $typeDisplay }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="suggestion-item text-muted small">No faculty found</div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <hr class="border-opacity-50 my-4">

                <div class="d-flex flex-wrap align-items-center gap-2 pt-1">
                    <div class="btn-group shadow-sm rounded-2" role="group" aria-label="Print or download PDF">
                        <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center justify-content-center gap-1 px-3 rounded-0 rounded-start-2" onclick="printFeedbackDetails()" title="Print report or choose Save as PDF in print dialog">
                            <span class="material-symbols-rounded" style="font-size: 18px; line-height: 1;" aria-hidden="true">print</span>
                            <span>Print</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center justify-content-center gap-1 px-3 rounded-0 rounded-end-2" onclick="exportToPDF()" title="Download PDF">
                            <span class="material-symbols-rounded" style="font-size: 18px; line-height: 1;" aria-hidden="true">picture_as_pdf</span>
                            <span>PDF</span>
                        </button>
                    </div>
                    <button type="button" class="btn btn-success btn-sm rounded-2 d-inline-flex align-items-center gap-1 px-3" onclick="exportToExcel()" title="Export to Excel">
                        <span class="material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">table_view</span>
                        <span>Export Excel</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-2 d-inline-flex align-items-center gap-1 px-3" id="resetButton">
                        <span class="material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">refresh</span>
                        <span>Reset filters</span>
                    </button>
                    <a href="{{ route('admin.feedback.pending.students') }}" class="btn btn-warning btn-sm text-dark rounded-2 d-inline-flex align-items-center gap-1 px-3">
                        <span class="material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">pending_actions</span>
                        <span>Pending feedback (students)</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-body-secondary bg-opacity-75 border-bottom py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
                    <span class="feedback-page-title mb-0">Faculty Feedback with Comments — All Details</span>
                    <small class="text-body-secondary" id="feedbackRefreshTime">Data refreshed: {{ $refreshTime ?? now()->format('d-M-Y H:i') }}</small>
                </div>

                <div class="card-body p-0 p-md-3 p-lg-4">
                    <div id="contentContainer" class="px-2 px-md-0">
                    @if ($groupedData->isEmpty())
                        <div class="empty-state text-center py-5 px-3 rounded-3 bg-body-secondary bg-opacity-25 border border-dashed">
                            <i class="fas fa-clipboard-list d-block mb-3 text-body-secondary"></i>
                            <h5 class="fw-semibold text-body-secondary">No feedback data found</h5>
                            <p class="text-muted small mb-0 mx-auto" style="max-width: 28rem;">Try adjusting your filters or program selection to see results.</p>
                        </div>
                    @else
                        @foreach ($groupedData as $groupKey => $group)
                            @php
                                [$programName, $facultyName, $topicName] = explode('|', $groupKey);
                                $firstRecord = $group->first();
                            @endphp

                            <div class="session-header feedback-session-card card border-0 shadow-sm mb-4">
                                <div class="card-body p-3 p-md-4">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="small text-uppercase text-muted fw-semibold mb-1">Course</div>
                                            <div class="fw-semibold text-body">{{ $programName }}</div>
                                            <div class="mt-2">
                                                <span class="session-badge">{{ $firstRecord['course_status'] ?? 'Unknown' }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="small text-uppercase text-muted fw-semibold mb-1">Faculty</div>
                                            <div class="fw-semibold text-body">{{ $facultyName }}</div>
                                            <div class="mt-2">
                                                <span class="faculty-type-badge">{{ $firstRecord['faculty_type'] ?? '' }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="small text-uppercase text-muted fw-semibold mb-1">Topic</div>
                                            <div class="fw-semibold text-body">{{ $topicName }}</div>
                                            @if (!empty($firstRecord['start_date']))
                                                <div class="small text-muted mt-2">
                                                    <i class="fas fa-clock me-1 opacity-75"></i>
                                                    <span class="fw-medium text-body-secondary">Session:</span>
                                                    {{ $firstRecord['start_date'] }}
                                                    @if (!empty($firstRecord['end_date']))
                                                        <span class="text-muted">– {{ $firstRecord['end_date'] }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive mb-4 rounded-3 border shadow-sm">
                                <table class="table table-hover table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="small text-uppercase text-secondary">
                                            <th scope="col" class="ps-3" style="width: 4%;">#</th>
                                            <th scope="col" style="width: 18%;">OT name</th>
                                            <th scope="col" style="width: 10%;">OT code</th>
                                            <th scope="col" class="text-center" style="width: 10%;">Content</th>
                                            <th scope="col" class="text-center" style="width: 10%;">Presentation</th>
                                            <th scope="col" style="width: 33%;">Remarks</th>
                                            <th scope="col" class="pe-3" style="width: 15%;">Feedback date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        @foreach ($group as $index => $item)
                                            <tr>
                                                <td class="ps-3 text-body-secondary">{{ $loop->iteration }}</td>
                                                <td class="fw-medium">{{ $item['ot_name'] }}</td>
                                                <td><code class="small bg-body-secondary px-2 py-1 rounded">{{ $item['ot_code'] }}</code></td>
                                                <td class="text-center">
                                                    <span class="rating-badge rating-{{ $item['content'] }}">
                                                        {{ $item['content'] }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="rating-badge rating-{{ $item['presentation'] }}">
                                                        {{ $item['presentation'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if (!empty($item['remark']))
                                                        <div class="remark-text">{{ $item['remark'] }}</div>
                                                    @else
                                                        <span class="text-muted fst-italic">No remarks</span>
                                                    @endif
                                                </td>
                                                <td class="pe-3">
                                                    <small class="text-body-secondary">{{ $item['feedback_date'] }}</small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <hr class="my-4 text-secondary opacity-25">
                        @endforeach

                        <!-- Pagination -->
                        @if ($totalRecords > 10)
                            <nav aria-label="Feedback pagination" class="pb-2">
                                <ul class="pagination feedback-pagination flex-wrap justify-content-center gap-1 mb-0">
                                    <!-- First Page -->
                                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="javascript:void(0)" onclick="goToPage(1)"
                                            aria-label="First">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>

                                    <!-- Previous Page -->
                                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="javascript:void(0)"
                                            onclick="goToPage({{ $currentPage - 1 }})" aria-label="Previous">
                                            <i class="fas fa-angle-left"></i>
                                        </a>
                                    </li>

                                    <!-- Page Numbers -->
                                    @php
                                        $startPage = max(1, $currentPage - 2);
                                        $endPage = min($totalPages, $currentPage + 2);
                                    @endphp

                                    @for ($i = $startPage; $i <= $endPage; $i++)
                                        <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                                            <a class="page-link" href="javascript:void(0)"
                                                onclick="goToPage({{ $i }})">{{ $i }}</a>
                                        </li>
                                    @endfor

                                    <!-- Next Page -->
                                    <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
                                        <a class="page-link" href="javascript:void(0)"
                                            onclick="goToPage({{ $currentPage + 1 }})" aria-label="Next">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>

                                    <!-- Last Page -->
                                    <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
                                        <a class="page-link" href="javascript:void(0)"
                                            onclick="goToPage({{ $totalPages }})" aria-label="Last">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>

                            <div class="text-center text-body-secondary mt-3">
                                <small class="badge rounded-pill text-bg-light border fw-normal px-3 py-2">
                                    Showing <strong class="text-body">{{ ($currentPage - 1) * 10 + 1 }}</strong>
                                    – <strong class="text-body">{{ min($currentPage * 10, $totalRecords) }}</strong>
                                    of <strong class="text-body">{{ $totalRecords }}</strong> records
                                </small>
                            </div>
                        @endif
                    @endif
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const loadingSpinner = document.getElementById('loadingSpinner');
                const contentContainer = document.getElementById('contentContainer');
                const facultySearch = document.getElementById('facultySearch');
                const suggestionsList = document.getElementById('facultySuggestions');
                const resetButton = document.getElementById('resetButton');
                let debounceTimer;
                let currentPage = {{ $currentPage }};

                // Get all filter inputs
                const filterInputs = [
                    document.getElementById('programSelect'),
                    document.getElementById('fromDate'),
                    document.getElementById('toDate'),
                    ...document.querySelectorAll('.course-type-radio'),
                    ...document.querySelectorAll('.faculty-type-checkbox'),
                    facultySearch
                ];

                // Function to load feedback data with current filters
                function loadFeedbackData(page = 1) {
                    currentPage = page;

                    // Show loading spinner
                    loadingSpinner.classList.add('feedback-loading-visible');
                    contentContainer.style.opacity = '0.5';

                    // Collect filter values
                    const params = new URLSearchParams();

                    // Add CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (csrfToken) {
                        params.append('_token', csrfToken);
                    }

                    // Add all filter values
                    params.append('program_id', document.getElementById('programSelect').value || '');
                    params.append('faculty_name', facultySearch ? (facultySearch.value || '') : '');
                    params.append('from_date', document.getElementById('fromDate').value || '');
                    params.append('to_date', document.getElementById('toDate').value || '');
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

                    console.log('Loading data with params:', params.toString()); // Debug log

                    // Make AJAX request - GET with query parameters
                    fetch('{{ route('admin.feedback.feedback_details') }}?' + params.toString(), {
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
                            console.log('Data received:', data); // Debug log
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
                    console.log('Updating content with data:', data); // Debug log
                    if (data.groupedData && Object.keys(data.groupedData).length > 0) {
                        let html = '';

                        Object.entries(data.groupedData).forEach(([groupKey, group]) => {
                            const [programName, facultyName, topicName] = groupKey.split('|');
                            const firstRecord = group[0];

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
                                        <td class="ps-3 text-body-secondary">${index + 1}</td>
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

                        // Update refresh time
                        const refreshElement = document.getElementById('feedbackRefreshTime');
                        if (refreshElement && data.refreshTime) {
                            refreshElement.textContent = `Data refreshed: ${data.refreshTime}`;
                        }
                    } else {
                        contentContainer.innerHTML = `
                <div class="empty-state text-center py-5 px-3 rounded-3 bg-body-secondary bg-opacity-25 border border-dashed">
                    <i class="fas fa-clipboard-list d-block mb-3 text-body-secondary"></i>
                    <h5 class="fw-semibold text-body-secondary">No feedback data found</h5>
                    <p class="text-muted small mb-0 mx-auto" style="max-width:28rem">Try adjusting your filters or program selection to see results.</p>
                </div>
            `;
                    }
                }

                // Function to generate pagination HTML
                function generatePagination(currentPage, totalPages, totalRecords) {
                    let pagination = `
            <nav aria-label="Feedback pagination" class="pb-2">
                <ul class="pagination feedback-pagination flex-wrap justify-content-center gap-1 mb-0">
                    <!-- First Page -->
                    <li class="page-item ${currentPage == 1 ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0)" onclick="goToPage(1)" aria-label="First">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    
                    <!-- Previous Page -->
                    <li class="page-item ${currentPage == 1 ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0)" onclick="goToPage(${currentPage - 1})" aria-label="Previous">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
        `;

                    // Calculate page range
                    const startPage = Math.max(1, currentPage - 2);
                    const endPage = Math.min(totalPages, currentPage + 2);

                    for (let i = startPage; i <= endPage; i++) {
                        pagination += `
                <li class="page-item ${i == currentPage ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="goToPage(${i})">${i}</a>
                </li>
            `;
                    }

                    pagination += `
                    <!-- Next Page -->
                    <li class="page-item ${currentPage == totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0)" onclick="goToPage(${currentPage + 1})" aria-label="Next">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    
                    <!-- Last Page -->
                    <li class="page-item ${currentPage == totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="javascript:void(0)" onclick="goToPage(${totalPages})" aria-label="Last">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
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
                    console.log('Updating filters with data:', data); // Debug log

                    // Update program dropdown
                    const programSelect = document.getElementById('programSelect');
                    if (data.programs && Object.keys(data.programs).length > 0) {
                        let options = '<option value="">All Programs</option>';
                        Object.entries(data.programs).forEach(([key, value]) => {
                            const selected = key == data.currentProgram ? 'selected' : '';
                            options += `<option value="${key}" ${selected}>${value}</option>`;
                        });
                        programSelect.innerHTML = options;
                    } else {
                        programSelect.innerHTML = '<option value="">No programs available</option>';
                    }

                    // Update faculty suggestions if needed
                    if (data.facultySuggestions && data.facultySuggestions.length > 0) {
                        const suggestionsContainer = document.getElementById('facultySuggestions');
                        if (suggestionsContainer) {
                            let suggestions = '';
                            data.facultySuggestions.forEach(faculty => {
                                suggestions += `
                    <div class="suggestion-item" data-value="${faculty.full_name}">
                        ${faculty.full_name}
                        <span class="faculty-type-badge ms-2">${faculty.faculty_type_display}</span>
                    </div>
                `;
                            });
                            suggestionsContainer.innerHTML = suggestions;
                        }
                    }
                }

                // Function to show error message
                function showError(message) {
                    contentContainer.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 shadow-sm border-0 rounded-3" role="alert">
                <i class="fas fa-exclamation-triangle mt-1"></i>
                <div class="flex-grow-1">${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
                }

                function fetchFacultySuggestions() {
                    if (!facultySearch || !suggestionsList) {
                        return;
                    }
                    const selectedTypes = Array.from(document.querySelectorAll('.faculty-type-checkbox:checked'))
                        .map(cb => cb.value);

                    if (selectedTypes.length === 0) {
                        suggestionsList.style.display = 'none';
                        return;
                    }

                    const searchTerm = facultySearch.value.trim();

                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        const params = new URLSearchParams();
                        selectedTypes.forEach(type => params.append('faculty_type[]', type));
                        if (searchTerm) params.append('faculty_name', searchTerm);

                        fetch('{{ route('feedback.faculty_suggestions') }}?' + params.toString())
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.faculties.length > 0) {
                                    let suggestions = '';
                                    data.faculties.forEach(faculty => {
                                        suggestions += `
                                <div class="suggestion-item" data-value="${faculty.full_name}">
                                    ${faculty.full_name}
                                    <span class="faculty-type-badge ms-2">${faculty.faculty_type_display}</span>
                                </div>
                            `;
                                    });
                                    suggestionsList.innerHTML = suggestions;
                                    suggestionsList.style.display = 'block';
                                } else {
                                    suggestionsList.innerHTML =
                                        '<div class="suggestion-item text-muted">No faculty found</div>';
                                    suggestionsList.style.display = 'block';
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching suggestions:', error);
                            });
                    }, 300);
                }

                // Event Listeners

                // Filter change events (auto-load on change)
                filterInputs.forEach(input => {
                    if (input) {
                        if (input.type === 'radio' || input.type === 'checkbox') {
                            input.addEventListener('change', function() {
                                console.log(`${input.type} changed:`, input.name, input.value, input
                                    .checked);
                                loadFeedbackData(1);
                            });
                        } else {
                            input.addEventListener('change', function() {
                                console.log('Input changed:', input.name, input.value);
                                loadFeedbackData(1);
                            });

                            // For text input (faculty search), use debounce
                            if (input.type === 'text') {
                                input.addEventListener('input', function() {
                                    clearTimeout(debounceTimer);
                                    debounceTimer = setTimeout(() => {
                                        loadFeedbackData(1);
                                    }, 500);
                                });
                            }
                        }
                    }
                });

                if (facultySearch && suggestionsList) {
                    facultySearch.addEventListener('focus', fetchFacultySuggestions);
                    facultySearch.addEventListener('input', fetchFacultySuggestions);

                    document.addEventListener('click', function(event) {
                        if (!facultySearch.contains(event.target) && !suggestionsList.contains(event.target)) {
                            suggestionsList.style.display = 'none';
                        }
                    });

                    suggestionsList.addEventListener('click', function(event) {
                        const item = event.target.closest('.suggestion-item');
                        if (item && item.getAttribute('data-value')) {
                            facultySearch.value = item.getAttribute('data-value');
                            suggestionsList.style.display = 'none';
                            loadFeedbackData(1);
                        }
                    });
                }

                // Reset button
                resetButton.addEventListener('click', function() {
                    console.log('Resetting filters');
                    // Reset all filters
                    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                    document.querySelectorAll('input[type="radio"]').forEach(rb => {
                        if (rb.value === 'current') rb.checked = true;
                    });
                    document.querySelectorAll('select').forEach(select => select.value = '');
                    document.querySelectorAll('input[type="date"]').forEach(input => input.value = '');
                    if (facultySearch) {
                        facultySearch.value = '';
                    }
                    if (suggestionsList) {
                        suggestionsList.style.display = 'none';
                    }

                    // Load data with reset filters
                    loadFeedbackData(1);
                });

                // Initialize with current page
                window.goToPage = function(page) {
                    console.log('Going to page:', page);
                    if (page >= 1) {
                        loadFeedbackData(page);
                    }
                };

                // Initial load
                console.log('Initial load with page:', currentPage);
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

                var clone = root.cloneNode(true);
                clone.querySelectorAll('i, .material-icons, .material-symbols-rounded').forEach(function(el) {
                    el.remove();
                });
                clone.querySelectorAll('nav').forEach(function(el) {
                    el.remove();
                });
                clone.querySelectorAll('table').forEach(function(t) {
                    t.classList.add('data-table');
                    t.style.borderCollapse = 'collapse';
                    t.style.width = '100%';
                });

                var progSel = document.getElementById('programSelect');
                var progText = progSel && progSel.options[progSel.selectedIndex]
                    ? progSel.options[progSel.selectedIndex].text
                    : '\u2014';
                var courseEl = document.querySelector('input[name="course_type"]:checked');
                var courseLabel = courseEl && courseEl.value === 'archived' ? 'Archived courses' : 'Current courses';
                var fromEl = document.getElementById('fromDate');
                var toEl = document.getElementById('toDate');
                var fromD = fromEl ? fromEl.value : '';
                var toD = toEl ? toEl.value : '';
                var facSearch = document.getElementById('facultySearch');
                var facName = facSearch ? facSearch.value : '';
                var ft = [];
                document.querySelectorAll('.faculty-type-checkbox:checked').forEach(function(cb) {
                    ft.push(cb.value === '2' ? 'Guest' : 'Internal');
                });
                var facultyTypes = ft.length ? ft.join(', ') : 'All types';

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
                    '.rating-badge{display:inline-flex;align-items:center;justify-content:center;min-width:1.6rem;height:1.6rem;font-size:9px;font-weight:700;border-radius:3px}' +
                    '.rating-5{background:#198754;color:#fff}.rating-4{background:#20c997;color:#fff}' +
                    '.rating-3{background:#ffc107;color:#000}.rating-2{background:#fd7e14;color:#fff}.rating-1{background:#dc3545;color:#fff}' +
                    '.session-badge{font-size:9px;padding:2px 8px;border-radius:10px;background:#e7f1ff;color:#0d6efd;border:1px solid #b6d4fe}' +
                    '.faculty-type-badge{font-size:9px;padding:2px 8px;border-radius:10px;background:#e9ecef;color:#495057;border:1px solid #dee2e6}' +
                    '@page{size:A4 portrait;margin:8mm}' +
                    '@media print{body{padding:0}thead{display:table-header-group}tr{page-break-inside:avoid}}';

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
                    ' &nbsp;|&nbsp; <strong>Printed:</strong> ' + printFeedbackDetailsEscapeHtml(printed) +
                    '</div>' +
                    '<div class="feedback-print-wrap">' + clone.innerHTML + '</div>' +
                    '<script>window.addEventListener("load",function(){setTimeout(function(){window.print();},300);});<\/script>' +
                    '</body></html>';

                printWindow.document.open();
                printWindow.document.write(html);
                printWindow.document.close();
            }

            function exportToExcel() {
                const loadingSpinner = document.getElementById('loadingSpinner');

                // Show loading
                loadingSpinner.classList.add('feedback-loading-visible');

                // Collect current filter values
                const params = new URLSearchParams();
                params.append('export_type', 'excel');

                // Add all current filter values
                params.append('program_id', document.getElementById('programSelect').value || '');
                params.append('faculty_name', document.getElementById('facultySearch')?.value || '');
                params.append('from_date', document.getElementById('fromDate').value || '');
                params.append('to_date', document.getElementById('toDate').value || '');
                params.append('course_type', document.querySelector('input[name="course_type"]:checked')?.value || 'current');

                // Faculty type checkboxes
                document.querySelectorAll('.faculty-type-checkbox:checked').forEach(cb => {
                    params.append('faculty_type[]', cb.value);
                });

                // Add CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    params.append('_token', csrfToken);
                }

                // Submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('admin.feedback.feedback_details.export') }}';
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
                params.append('program_id', document.getElementById('programSelect').value || '');
                params.append('faculty_name', document.getElementById('facultySearch')?.value || '');
                params.append('from_date', document.getElementById('fromDate').value || '');
                params.append('to_date', document.getElementById('toDate').value || '');
                params.append('course_type', document.querySelector('input[name="course_type"]:checked')?.value || 'current');

                // Faculty type checkboxes
                document.querySelectorAll('.faculty-type-checkbox:checked').forEach(cb => {
                    params.append('faculty_type[]', cb.value);
                });

                // Add CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    params.append('_token', csrfToken);
                }

                // Submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('admin.feedback.feedback_details.export') }}';
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
