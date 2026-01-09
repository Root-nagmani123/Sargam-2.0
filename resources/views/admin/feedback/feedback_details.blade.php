@extends('admin.layouts.master')

@section('title', 'Faculty Feedback with Comments All Details - Sargam | Lal Bahadur')

@section('setup_content')
    <style>
        :root {
            --primary: #af2910;
            --secondary: #f4f6f9;
            --accent: #f2b705;
            --success: #198754;
            --border: #d0d7de;
            --text-dark: #1f2937;
        }

        body {
            background: var(--secondary);
            color: var(--text-dark);
            font-size: 14px;
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
        }

        .filter-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
        }

        .filter-card .card-header {
            background: var(--primary);
            color: #fff;
            font-weight: 600;
        }

        .content-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
        }

        .content-card .card-header {
            background: #eef4fb;
            font-weight: 600;
        }

        .rating-badge {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            border-radius: 4px;
            font-weight: 600;
        }

        .rating-5 {
            background: #198754;
            color: white;
        }

        .rating-4 {
            background: #20c997;
            color: white;
        }

        .rating-3 {
            background: #ffc107;
            color: #000;
        }

        .rating-2 {
            background: #fd7e14;
            color: white;
        }

        .rating-1 {
            background: #dc3545;
            color: white;
        }

        .faculty-type-badge {
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 10px;
            background: #e9ecef;
            color: #495057;
        }

        .session-badge {
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 10px;
            background: #e7f1ff;
            color: #0d6efd;
        }

        /* Faculty suggestions */
        .suggestions-container {
            position: relative;
        }

        .suggestions-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .suggestion-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
        }

        .suggestion-item:hover {
            background-color: #f8f9fa;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        /* Loading spinner */
        #loadingSpinner {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Pagination */
        .pagination .page-link {
            color: var(--primary);
            border-color: var(--border);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #dee2e6;
        }
    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="container-fluid py-3">
        <x-breadcrum title="Faculty Feedback with Comments All Details"></x-breadcrum>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" style="display: none;">
            <div class="d-flex justify-content-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <p class="mt-2 text-center">Loading feedback data...</p>
        </div>

        <!-- FILTER PANEL -->
        <div class="card filter-card mb-3">
            <div class="card-header">Feedback Details</div>
            <div class="card-body">
                <div class="row">
                    <!-- Course Status -->
                    <div class="col-lg-2 col-md-6 mb-3">
                        <fieldset>
                            <legend class="fs-6 fw-semibold mb-2">Course Status</legend>
                            <div class="form-check">
                                <input class="form-check-input course-type-radio" type="radio" name="course_type"
                                    value="current" id="current"
                                    {{ ($courseType ?? 'current') == 'current' ? 'checked' : '' }}>
                                <label class="form-check-label" for="current">Current Courses</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input course-type-radio" type="radio" name="course_type"
                                    value="archived" id="archived"
                                    {{ ($courseType ?? 'current') == 'archived' ? 'checked' : '' }}>
                                <label class="form-check-label" for="archived">Archived Courses</label>
                            </div>
                        </fieldset>
                    </div>

                    <!-- Program Name -->
                    <div class="col-lg-2 col-md-6 mb-3">
                        <label for="programSelect" class="form-label">Program Name</label>
                        <select class="form-select" id="programSelect" name="program_id">
                            <option value="">All Programs</option>
                            @foreach ($programs as $key => $program)
                                <option value="{{ $key }}" {{ $currentProgram == $key ? 'selected' : '' }}>
                                    {{ $program }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="col-lg-2 col-md-6 mb-3">
                        <label for="fromDate" class="form-label">From Date</label>
                        <input type="date" id="fromDate" class="form-control" name="from_date"
                            value="{{ $fromDate ?? '' }}" />
                    </div>

                    <div class="col-lg-2 col-md-6 mb-3">
                        <label for="toDate" class="form-label">To Date</label>
                        <input type="date" id="toDate" class="form-control" name="to_date"
                            value="{{ $toDate ?? '' }}" />
                    </div>

                    <!-- Faculty Type -->
                     @if (!hasRole('Internal Faculty') && !hasRole('Guest Faculty'))
                    <div class="col-lg-2 col-md-6 mb-3">
                        <fieldset>
                            <legend class="fs-6 fw-semibold mb-2">Faculty Type</legend>
                            <div class="form-check">
                                <input class="form-check-input faculty-type-checkbox" type="checkbox" name="faculty_type[]"
                                    value="2" id="faculty_type_guest"
                                    {{ in_array('2', $selectedFacultyTypes ?? []) ? 'checked' : '' }}>
                                <label class="form-check-label" for="faculty_type_guest">Guest</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input faculty-type-checkbox" type="checkbox" name="faculty_type[]"
                                    value="1" id="faculty_type_internal"
                                    {{ in_array('1', $selectedFacultyTypes ?? []) ? 'checked' : '' }}>
                                <label class="form-check-label" for="faculty_type_internal">Internal</label>
                            </div>
                        </fieldset>
                    </div>
                   

                    <!-- Faculty Name -->
                    <div class="col-lg-2 col-md-6 mb-3 suggestions-container">
                        <label for="facultySearch" class="form-label">Faculty Name</label>
                        <input type="text" id="facultySearch" class="form-control" name="faculty_name"
                            value="{{ $currentFaculty ?? '' }}" placeholder="Type to search..." autocomplete="off" />

                        <!-- Suggestions dropdown -->
                        <div class="suggestions-list" id="facultySuggestions">
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
                                <div class="suggestion-item text-muted">No faculty found</div>
                            @endif
                        </div>
                    </div>
                     @endif
                </div>

                <div class="d-flex align-items-center">
                    <div class="btn-group ms-2" role="group">
                        <button type="button" class="btn btn-sm btn-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf me-1"></i> Export PDF
                        </button>
                    </div>

                    <!-- Reset Button -->
                    <div class="d-flex justify-content-end ms-3">
                        <button type="button" class="btn btn-outline-secondary" id="resetButton">
                            <i class="fas fa-redo me-1"></i> Reset Filters
                        </button>
                    </div>
                </div>

            </div>

            <!-- MAIN CONTENT -->
            <div class="card content-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="page-title">Faculty Feedback with Comments All Details</span>

                    <small class="text-muted">Data refreshed: {{ $refreshTime ?? now()->format('d-M-Y H:i') }}</small>
                </div>

                <div class="card-body" id="contentContainer">
                    @if ($groupedData->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h5>No feedback data found</h5>
                            <p class="text-muted">Try adjusting your filters to see results.</p>
                        </div>
                    @else
                        @foreach ($groupedData as $groupKey => $group)
                            @php
                                [$programName, $facultyName, $topicName] = explode('|', $groupKey);
                                $firstRecord = $group->first();
                            @endphp

                            <!-- Session Header -->
                            <div class="session-header mb-4 p-3 border rounded bg-light">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6 class="mb-1"><strong>Course:</strong> {{ $programName }}</h6>
                                        <small class="text-muted d-block">
                                            <span
                                                class="session-badge">{{ $firstRecord['course_status'] ?? 'Unknown' }}</span>
                                        </small>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="mb-1"><strong>Faculty:</strong> {{ $facultyName }}</h6>
                                        <small class="text-muted d-block">
                                            <span
                                                class="faculty-type-badge">{{ $firstRecord['faculty_type'] ?? '' }}</span>
                                        </small>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="mb-1"><strong>Topic:</strong> {{ $topicName }}</h6>
                                        @if (!empty($firstRecord['start_date']))
                                            <small class="text-muted d-block">
                                                <strong>Session:</strong> {{ $firstRecord['start_date'] }}
                                                @if (!empty($firstRecord['end_date']))
                                                    - {{ $firstRecord['end_date'] }}
                                                @endif
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Feedback Table -->
                            <div class="table-responsive mb-5">
                                <table class="table table-hover table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="20%">OT Name</th>
                                            <th width="10%">OT Code</th>
                                            <th width="10%">Content</th>
                                            <th width="10%">Presentation</th>
                                            <th width="35%">Remarks</th>
                                            <th width="10%">Feedback Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($group as $index => $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item['ot_name'] }}</td>
                                                <td>{{ $item['ot_code'] }}</td>
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
                                                <td>
                                                    <small class="text-muted">{{ $item['feedback_date'] }}</small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <hr class="my-4">
                        @endforeach

                        <!-- Pagination -->
                        @if ($totalRecords > 10)
                            <nav aria-label="Feedback pagination">
                                <ul class="pagination justify-content-center">
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

                            <!-- Pagination Info -->
                            <div class="text-center text-muted mt-2">
                                <small>
                                    Showing {{ ($currentPage - 1) * 10 + 1 }} to
                                    {{ min($currentPage * 10, $totalRecords) }}
                                    of {{ $totalRecords }} records
                                </small>
                            </div>
                        @endif
                    @endif
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
                    loadingSpinner.style.display = 'block';
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
                    params.append('faculty_name', facultySearch.value || '');
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
                            loadingSpinner.style.display = 'none';
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
                    <div class="session-header mb-4 p-3 border rounded bg-light">
                        <div class="row">
                            <div class="col-md-4">
                                <h6 class="mb-1"><strong>Course:</strong> ${programName}</h6>
                                <small class="text-muted d-block">
                                    <span class="session-badge">${firstRecord.course_status || 'Unknown'}</span>
                                </small>
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-1"><strong>Faculty:</strong> ${facultyName}</h6>
                                <small class="text-muted d-block">
                                    <span class="faculty-type-badge">${firstRecord.faculty_type || ''}</span>
                                </small>
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-1"><strong>Topic:</strong> ${topicName}</h6>
                                ${firstRecord.start_date ? `
                                                            <small class="text-muted d-block">
                                                                <strong>Session:</strong> ${firstRecord.start_date}
                                                                ${firstRecord.end_date ? `- ${firstRecord.end_date}` : ''}
                                                            </small>
                                                        ` : ''}
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-5">
                        <table class="table table-hover table-bordered">
                            <thead class="#">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">OT Name</th>
                                    <th width="10%">OT Code</th>
                                    <th width="10%">Content</th>
                                    <th width="10%">Presentation</th>
                                    <th width="35%">Remarks</th>
                                    <th width="10%">Feedback Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${group.map((item, index) => `
                                                            <tr>
                                                                <td>${index + 1}</td>
                                                                <td>${item.ot_name || ''}</td>
                                                                <td>${item.ot_code || ''}</td>
                                                                <td class="text-center">
                                                                    <span class="rating-badge rating-${item.content}">
                                                                        ${item.content}
                                                                    </span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="rating-badge rating-${item.presentation}">
                                                                        ${item.presentation}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    ${item.remark ? `
                                                <div class="remark-text">${item.remark}</div>
                                            ` : `
                                                <span class="text-muted fst-italic">No remarks</span>
                                            `}
                                                                </td>
                                                                <td>
                                                                    <small class="text-muted">${item.feedback_date || ''}</small>
                                                                </td>
                                                            </tr>
                                                        `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <hr class="my-4">
                `;
                        });

                        // Add pagination if needed
                        if (data.totalRecords > 10) {
                            html += generatePagination(data.currentPage, data.totalPages, data.totalRecords);
                        }

                        contentContainer.innerHTML = html;

                        // Update refresh time
                        const refreshElement = document.querySelector('.card-header small');
                        if (refreshElement && data.refreshTime) {
                            refreshElement.textContent = `Data refreshed: ${data.refreshTime}`;
                        }
                    } else {
                        contentContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h5>No feedback data found</h5>
                    <p class="text-muted">Try adjusting your filters to see results.</p>
                </div>
            `;
                    }
                }

                // Function to generate pagination HTML
                function generatePagination(currentPage, totalPages, totalRecords) {
                    let pagination = `
            <nav aria-label="Feedback pagination">
                <ul class="pagination justify-content-center">
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
            
            <div class="text-center text-muted mt-2">
                <small>
                    Showing ${((currentPage - 1) * 10) + 1} to ${Math.min(currentPage * 10, totalRecords)} 
                    of ${totalRecords} records
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

                // Function to show error message
                function showError(message) {
                    contentContainer.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
                }

                // Function to fetch faculty suggestions
                function fetchFacultySuggestions() {
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

                // Faculty search with suggestions
                facultySearch.addEventListener('focus', fetchFacultySuggestions);
                facultySearch.addEventListener('input', fetchFacultySuggestions);

                // Hide suggestions when clicking outside
                document.addEventListener('click', function(event) {
                    if (!facultySearch.contains(event.target) && !suggestionsList.contains(event.target)) {
                        suggestionsList.style.display = 'none';
                    }
                });

                // Suggestion click
                suggestionsList.addEventListener('click', function(event) {
                    if (event.target.classList.contains('suggestion-item')) {
                        facultySearch.value = event.target.getAttribute('data-value');
                        suggestionsList.style.display = 'none';
                        loadFeedbackData(1);
                    }
                });

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
                    facultySearch.value = '';
                    suggestionsList.style.display = 'none';

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

            function exportToExcel() {
                const loadingSpinner = document.getElementById('loadingSpinner');

                // Show loading
                loadingSpinner.style.display = 'block';

                // Collect current filter values
                const params = new URLSearchParams();
                params.append('export_type', 'excel');

                // Add all current filter values
                params.append('program_id', document.getElementById('programSelect').value || '');
                params.append('faculty_name', document.getElementById('facultySearch').value || '');
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
                    loadingSpinner.style.display = 'none';
                }, 2000);
            }

            function exportToPDF() {
                const loadingSpinner = document.getElementById('loadingSpinner');

                // Show loading
                loadingSpinner.style.display = 'block';

                // Collect current filter values
                const params = new URLSearchParams();
                params.append('export_type', 'pdf');

                // Add all current filter values
                params.append('program_id', document.getElementById('programSelect').value || '');
                params.append('faculty_name', document.getElementById('facultySearch').value || '');
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
                    loadingSpinner.style.display = 'none';
                }, 2000);
            }
        </script>
    @endsection
