@extends('admin.layouts.master')

@section('title', 'Faculty Feedback with Comments All Details')

@section('setup_content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="container-fluid py-3">
        <x-breadcrum title="Faculty Feedback with Comments All Details"></x-breadcrum>

        <!-- Loading Spinner (hidden by default; JS toggles d-none) -->
        <div id="loadingSpinner" class="d-none position-fixed top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-3 bg-white bg-opacity-90">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mb-0 small text-body-secondary">Loading feedback data...</p>
        </div>

        <!-- FILTER PANEL -->
        <div class="card shadow rounded-3 mb-4 border-0 overflow-hidden">
            <div class="card-header bg-primary text-white fw-semibold py-3 px-4 d-flex align-items-center gap-2 border-0">
                <i class="fas fa-filter"></i>
                <span>Feedback Details</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Course Status -->
                    <div class="col-lg-2 col-md-6">
                        <fieldset class="mb-0">
                            <legend class="fs-6 fw-semibold mb-2 text-body-secondary">Course Status</legend>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input course-type-radio" type="radio" name="course_type"
                                    value="current" id="current"
                                    {{ ($courseType ?? 'current') == 'current' ? 'checked' : '' }}>
                                <label class="form-check-label" for="current">Current</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input course-type-radio" type="radio" name="course_type"
                                    value="archived" id="archived"
                                    {{ ($courseType ?? 'current') == 'archived' ? 'checked' : '' }}>
                                <label class="form-check-label" for="archived">Archived</label>
                            </div>
                        </fieldset>
                    </div>

                    <!-- Program Name -->
                    <div class="col-lg-2 col-md-6">
                        <label for="programSelect" class="form-label fw-medium">Program Name</label>
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
                    <div class="col-lg-2 col-md-6">
                        <label for="fromDate" class="form-label fw-medium">From Date</label>
                        <input type="date" id="fromDate" class="form-control" name="from_date"
                            value="{{ $fromDate ?? '' }}" />
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label for="toDate" class="form-label fw-medium">To Date</label>
                        <input type="date" id="toDate" class="form-control" name="to_date"
                            value="{{ $toDate ?? '' }}" />
                    </div>

                    <!-- Faculty Type -->
                    @if (!hasRole('Internal Faculty') && !hasRole('Guest Faculty'))
                        <div class="col-lg-2 col-md-6">
                            <fieldset class="mb-0">
                                <legend class="fs-6 fw-semibold mb-2 text-body-secondary">Faculty Type</legend>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input faculty-type-checkbox" type="checkbox"
                                        name="faculty_type[]" value="2" id="faculty_type_guest"
                                        {{ in_array('2', $selectedFacultyTypes ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="faculty_type_guest">Guest</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input faculty-type-checkbox" type="checkbox"
                                        name="faculty_type[]" value="1" id="faculty_type_internal"
                                        {{ in_array('1', $selectedFacultyTypes ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="faculty_type_internal">Internal</label>
                                </div>
                            </fieldset>
                        </div>

                        <!-- Faculty Name -->
                        <div class="col-lg-2 col-md-6 position-relative">
                            <label for="facultySearch" class="form-label fw-medium">Faculty Name</label>
                            <input type="text" id="facultySearch" class="form-control" name="faculty_name"
                                value="{{ $currentFaculty ?? '' }}" placeholder="Type to search..." autocomplete="off" />

                            <!-- Suggestions dropdown -->
                            <div class="position-absolute top-100 start-0 w-100 mt-1 list-group list-group-flush border rounded shadow overflow-auto d-none" id="facultySuggestions">
                                @if ($facultySuggestions->isNotEmpty())
                                    @foreach ($facultySuggestions as $faculty)
                                        <div class="list-group-item list-group-item-action suggestion-item" data-value="{{ $faculty->full_name }}" role="button">
                                            {{ $faculty->full_name }}
                                            @php
                                                $typeMap = ['1' => 'Internal', '2' => 'Guest'];
                                                $typeDisplay =
                                                    $typeMap[$faculty->faculty_type] ?? ucfirst($faculty->faculty_type);
                                            @endphp
                                            <span class="badge bg-secondary ms-2">{{ $typeDisplay }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="list-group-item text-muted">No faculty found</div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-4 pt-3 border-top">
                    <div class="btn-group flex-wrap" role="group">
                        <button type="button" class="btn btn-success rounded-2" onclick="exportToExcel()">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </button>
                        <button type="button" class="btn btn-danger rounded-2" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf me-1"></i> Export PDF
                        </button>
                    </div>
                    <button type="button" class="btn btn-outline-secondary rounded-2" id="resetButton">
                        <i class="fas fa-redo me-1"></i> Reset Filters
                    </button>
                    <a href="{{ route('admin.feedback.pending.students') }}" class="btn btn-warning rounded-2">
                        <i class="fas fa-user-clock me-1"></i> Pending Feedback (Students)
                    </a>
                </div>
            </div>

            <!-- Content card -->
            <div class="card shadow-sm border-0 rounded-0 mt-0">
                <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 px-4 border-bottom fw-semibold">
                    <span class="mb-0 text-primary fs-6">Faculty Feedback with Comments All Details</span>
                    <small class="text-body-secondary"><i class="fas fa-sync-alt me-1"></i> {{ $refreshTime ?? now()->format('d-M-Y H:i') }}</small>
                </div>

                <div class="card-body p-4">
                    <div id="contentContainer">
                    @if ($groupedData->isEmpty())
                        <div class="text-center rounded-3 bg-light py-5 px-3 text-body-secondary">
                            <i class="fas fa-clipboard-list display-4 text-secondary mb-3 d-block"></i>
                            <h5 class="fw-semibold mt-2 text-dark">No feedback data found</h5>
                            <p class="text-body-secondary mb-0">Try adjusting your filters to see results.</p>
                        </div>
                    @else
                        @foreach ($groupedData as $groupKey => $group)
                            @php
                                [$programName, $facultyName, $topicName] = explode('|', $groupKey);
                                $firstRecord = $group->first();
                            @endphp

                            <!-- Session Header -->
                            <div class="border rounded bg-light shadow-sm mb-4 p-4">
                                <div class="row g-3 align-items-start">
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="fas fa-book-open text-primary"></i>
                                            <span class="fw-semibold text-body-secondary small">Course</span>
                                        </div>
                                        <h6 class="mb-0 fw-semibold">{{ $programName }}</h6>
                                        <span class="badge bg-primary rounded-pill mt-1">{{ $firstRecord['course_status'] ?? 'Unknown' }}</span>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="fas fa-chalkboard-teacher text-primary"></i>
                                            <span class="fw-semibold text-body-secondary small">Faculty</span>
                                        </div>
                                        <h6 class="mb-0 fw-semibold">{{ $facultyName }}</h6>
                                        <span class="badge bg-secondary rounded-pill mt-1">{{ $firstRecord['faculty_type'] ?? '' }}</span>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="fas fa-tag text-primary"></i>
                                            <span class="fw-semibold text-body-secondary small">Topic</span>
                                        </div>
                                        <h6 class="mb-0 fw-semibold">{{ $topicName }}</h6>
                                        @if (!empty($firstRecord['start_date']))
                                            <small class="text-body-secondary d-block mt-1">
                                                Session: {{ $firstRecord['start_date'] }}
                                                @if (!empty($firstRecord['end_date']))
                                                    – {{ $firstRecord['end_date'] }}
                                                @endif
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <!-- Feedback Table -->
                            <div class="table-responsive mb-5 rounded-3 overflow-hidden border">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>OT Name</th>
                                            <th>OT Code</th>
                                            <th>Content</th>
                                            <th>Presentation</th>
                                            <th>Remarks</th>
                                            <th>Feedback Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($group as $index => $item)
                                            @php
                                                $ratingClass = match((int)($item['content'] ?? 0)) {
                                                    5 => 'bg-success',
                                                    4 => 'bg-info',
                                                    3 => 'bg-warning text-dark',
                                                    2 => 'bg-secondary',
                                                    1 => 'bg-danger',
                                                    default => 'bg-secondary',
                                                };
                                                $presClass = match((int)($item['presentation'] ?? 0)) {
                                                    5 => 'bg-success',
                                                    4 => 'bg-info',
                                                    3 => 'bg-warning text-dark',
                                                    2 => 'bg-secondary',
                                                    1 => 'bg-danger',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item['ot_name'] }}</td>
                                                <td>{{ $item['ot_code'] }}</td>
                                                <td>
                                                    <span class="badge {{ $ratingClass }}">{{ $item['content'] }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $presClass }}">{{ $item['presentation'] }}</span>
                                                </td>
                                                <td>
                                                    @if (!empty($item['remark']))
                                                        <div>{{ $item['remark'] }}</div>
                                                    @else
                                                        <span class="text-muted fst-italic">No remarks</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <small class="text-body-secondary">{{ $item['feedback_date'] }}</small>
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
                            <div class="border rounded bg-light p-3 mt-4">
                                <div class="small text-center d-flex flex-wrap justify-content-center align-items-center gap-2 gap-md-3 mb-2">
                                    <span><strong>Page {{ $currentPage }}</strong> of {{ $totalPages }}</span>
                                    <span class="text-body-secondary">·</span>
                                    <span>Showing {{ ($currentPage - 1) * 10 + 1 }}–{{ min($currentPage * 10, $totalRecords) }} of <strong>{{ $totalRecords }}</strong> records</span>
                                </div>
                                <nav aria-label="Feedback pagination">
                                    <ul class="pagination pagination-sm justify-content-center flex-wrap mb-0 gap-1">
                                        <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                            <a class="page-link rounded" href="javascript:void(0)" onclick="goToPage({{ $currentPage - 1 }})" aria-label="Previous page" title="Previous">
                                                <span class="material-icons material-symbols-rounded fs-6" aria-hidden="true">chevron_left</span>
                                            </a>
                                        </li>
                                        @php
                                            $startPage = max(1, $currentPage - 2);
                                            $endPage = min($totalPages, $currentPage + 2);
                                        @endphp
                                        @for ($i = $startPage; $i <= $endPage; $i++)
                                            <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                                                <a class="page-link rounded" href="javascript:void(0)" onclick="goToPage({{ $i }})" aria-label="Page {{ $i }}" aria-current="{{ $i == $currentPage ? 'page' : 'false' }}">{{ $i }}</a>
                                            </li>
                                        @endfor
                                        <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
                                            <a class="page-link rounded" href="javascript:void(0)" onclick="goToPage({{ $currentPage + 1 }})" aria-label="Next page" title="Next">
                                                <span class="material-icons material-symbols-rounded fs-6" aria-hidden="true">chevron_right</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        @endif
                    @endif
                            </div>
                        </div>

                        <!-- Feedback Not Given Tab -->
                        <div class="tab-pane fade" id="not-given-content" role="tabpanel" 
                            aria-labelledby="not-given-tab">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th width="10%">Serial No.</th>
                                            <th width="30%">OT/Participant Name</th>
                                            <th width="20%">OT/Participant Code</th>
                                            <th width="30%">Email</th>
                                            <th width="10%">Mobile No.</th>
                                        </tr>
                                    </thead>
                                    <tbody id="notGivenTableBody">
                                        @if (isset($feedbackNotGiven) && $feedbackNotGiven->isNotEmpty())
                                            @foreach ($feedbackNotGiven as $index => $participant)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $participant->name ?? 'N/A' }}</td>
                                                    <td>{{ $participant->code ?? 'N/A' }}</td>
                                                    <td>{{ $participant->email ?? 'N/A' }}</td>
                                                    <td>{{ $participant->mobile_no ?? 'N/A' }}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">
                                                    <div class="empty-state">
                                                        <i class="fas fa-check-circle"></i>
                                                        <h5>All participants have provided feedback</h5>
                                                        <p class="text-muted">No pending feedback found.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination for Not Given Tab -->
                            @if (isset($feedbackNotGiven) && $feedbackNotGiven->total() > 10)
                                <nav aria-label="Feedback not given pagination">
                                    <ul class="pagination justify-content-center">
                                        {{ $feedbackNotGiven->links() }}
                                    </ul>
                                </nav>
                            @endif
                        </div>
                    </div>
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

                function showLoader() {
                    if (loadingSpinner) loadingSpinner.classList.remove('d-none');
                    if (contentContainer) contentContainer.classList.add('opacity-50');
                }
                function hideLoader() {
                    if (loadingSpinner) loadingSpinner.classList.add('d-none');
                    if (contentContainer) contentContainer.classList.remove('opacity-50');
                }

                // Function to load feedback data with current filters
                function loadFeedbackData(page = 1) {
                    currentPage = page;
                    showLoader();

                    try {
                        // Collect filter values (facultySearch may be null for Internal/Guest Faculty)
                        const params = new URLSearchParams();
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        if (csrfToken) params.append('_token', csrfToken);

                        const programSelect = document.getElementById('programSelect');
                        const fromDate = document.getElementById('fromDate');
                        const toDate = document.getElementById('toDate');
                        params.append('program_id', programSelect ? programSelect.value || '' : '');
                        params.append('faculty_name', facultySearch ? facultySearch.value || '' : '');
                        params.append('from_date', fromDate ? fromDate.value || '' : '');
                        params.append('to_date', toDate ? toDate.value || '' : '');
                        params.append('page', page);

                        const courseType = document.querySelector('input[name="course_type"]:checked');
                        if (courseType) params.append('course_type', courseType.value);

                        document.querySelectorAll('.faculty-type-checkbox:checked').forEach(cb => {
                            params.append('faculty_type[]', cb.value);
                        });

                        fetch('{{ route('admin.feedback.feedback_details') }}?' + params.toString(), {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('HTTP ' + response.status);
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
                        .finally(() => { hideLoader(); });
                    } catch (err) {
                        console.error('Error preparing request:', err);
                        hideLoader();
                        if (contentContainer) showError('Error loading data. Please try again.');
                    }
                }

                // Function to update content with new data
                function updateContent(data) {
                    console.log('Updating content with data:', data); // Debug log
                    if (data.groupedData && Object.keys(data.groupedData).length > 0) {
                        let html = '';

                        function ratingClass(r) {
                            const n = parseInt(r, 10);
                            if (n === 5) return 'bg-success';
                            if (n === 4) return 'bg-info';
                            if (n === 3) return 'bg-warning text-dark';
                            if (n === 2) return 'bg-secondary';
                            if (n === 1) return 'bg-danger';
                            return 'bg-secondary';
                        }
                        Object.entries(data.groupedData).forEach(([groupKey, group]) => {
                            const [programName, facultyName, topicName] = groupKey.split('|');
                            const firstRecord = group[0];

                            html += `
                    <div class="border rounded bg-light shadow-sm mb-4 p-4">
                        <div class="row g-3 align-items-start">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fas fa-book-open text-primary"></i>
                                    <span class="fw-semibold text-body-secondary small">Course</span>
                                </div>
                                <h6 class="mb-0 fw-semibold">${programName}</h6>
                                <span class="badge bg-primary rounded-pill mt-1">${firstRecord.course_status || 'Unknown'}</span>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fas fa-chalkboard-teacher text-primary"></i>
                                    <span class="fw-semibold text-body-secondary small">Faculty</span>
                                </div>
                                <h6 class="mb-0 fw-semibold">${facultyName}</h6>
                                <span class="badge bg-secondary rounded-pill mt-1">${firstRecord.faculty_type || ''}</span>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fas fa-tag text-primary"></i>
                                    <span class="fw-semibold text-body-secondary small">Topic</span>
                                </div>
                                <h6 class="mb-0 fw-semibold">${topicName}</h6>
                                ${firstRecord.start_date ? `
                                    <small class="text-body-secondary d-block mt-1">
                                        Session: ${firstRecord.start_date}
                                        ${firstRecord.end_date ? `– ${firstRecord.end_date}` : ''}
                                    </small>
                                ` : ''}
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-5 rounded-3 overflow-hidden border">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>OT Name</th>
                                    <th>OT Code</th>
                                    <th>Content</th>
                                    <th>Presentation</th>
                                    <th>Remarks</th>
                                    <th>Feedback Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${group.map((item, index) => `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${item.ot_name || ''}</td>
                                        <td>${item.ot_code || ''}</td>
                                        <td><span class="badge ${ratingClass(item.content)}">${item.content}</span></td>
                                        <td><span class="badge ${ratingClass(item.presentation)}">${item.presentation}</span></td>
                                        <td>${item.remark ? `<div>${item.remark}</div>` : `<span class="text-muted fst-italic">No remarks</span>`}</td>
                                        <td><small class="text-body-secondary">${item.feedback_date || ''}</small></td>
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
                        const refreshElement = document.querySelector('.content-card .card-header small');
                        if (refreshElement && data.refreshTime) {
                            refreshElement.innerHTML = `<i class="fas fa-sync-alt me-1"></i> ${data.refreshTime}`;
                        }
                    } else {
                        contentContainer.innerHTML = `
                <div class="text-center rounded-3 bg-light py-5 px-3 text-body-secondary">
                    <i class="fas fa-clipboard-list display-4 text-secondary mb-3 d-block"></i>
                    <h5 class="fw-semibold mt-2 text-dark">No feedback data found</h5>
                    <p class="text-body-secondary mb-0">Try adjusting your filters to see results.</p>
                </div>
            `;
                    }
                }

                // Function to generate pagination HTML (matches Blade structure)
                function generatePagination(currentPage, totalPages, totalRecords) {
                    const startRec = ((currentPage - 1) * 10) + 1;
                    const endRec = Math.min(currentPage * 10, totalRecords);

                    let pagination = `
            <div class="border rounded bg-light p-3 mt-4">
                <div class="small text-center d-flex flex-wrap justify-content-center align-items-center gap-2 gap-md-3 mb-2">
                    <span><strong>Page ${currentPage}</strong> of ${totalPages}</span>
                    <span class="text-body-secondary">·</span>
                    <span>Showing ${startRec}–${endRec} of <strong>${totalRecords}</strong> records</span>
                </div>
                <nav aria-label="Feedback pagination">
                    <ul class="pagination pagination-sm justify-content-center flex-wrap mb-0 gap-1">
                        <li class="page-item ${currentPage == 1 ? 'disabled' : ''}">
                            <a class="page-link rounded" href="javascript:void(0)" onclick="goToPage(${currentPage - 1})" aria-label="Previous page" title="Previous">
                                <span class="material-icons material-symbols-rounded fs-6" aria-hidden="true">chevron_left</span>
                            </a>
                        </li>
        `;

                    const startPage = Math.max(1, currentPage - 2);
                    const endPage = Math.min(totalPages, currentPage + 2);

                    for (let i = startPage; i <= endPage; i++) {
                        const ariaCurrent = i === currentPage ? ' aria-current="page"' : ' aria-current="false"';
                        pagination += `
                        <li class="page-item ${i === currentPage ? 'active' : ''}">
                            <a class="page-link rounded" href="javascript:void(0)" onclick="goToPage(${i})" aria-label="Page ${i}"${ariaCurrent}>${i}</a>
                        </li>
        `;
                    }

                    pagination += `
                        <li class="page-item ${currentPage == totalPages ? 'disabled' : ''}">
                            <a class="page-link rounded" href="javascript:void(0)" onclick="goToPage(${currentPage + 1})" aria-label="Next page" title="Next">
                                <span class="material-icons material-symbols-rounded fs-6" aria-hidden="true">chevron_right</span>
                            </a>
                        </li>
                    </ul>
                </nav>
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
                    <div class="list-group-item list-group-item-action suggestion-item" data-value="${faculty.full_name}" role="button">
                        ${faculty.full_name}
                        <span class="badge bg-secondary ms-2">${faculty.faculty_type_display}</span>
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
                        if (suggestionsList) suggestionsList.classList.add('d-none');
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
                                    suggestionsList.classList.remove('d-none');
                                } else {
                                    suggestionsList.innerHTML =
                                        '<div class="list-group-item text-muted">No faculty found</div>';
                                    suggestionsList.classList.remove('d-none');
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

                // Faculty search with suggestions (only if faculty search exists)
                if (facultySearch) {
                    facultySearch.addEventListener('focus', fetchFacultySuggestions);
                    facultySearch.addEventListener('input', fetchFacultySuggestions);
                }

                // Hide suggestions when clicking outside
                document.addEventListener('click', function(event) {
                    if (suggestionsList && facultySearch && !facultySearch.contains(event.target) && !suggestionsList.contains(event.target)) {
                        suggestionsList.classList.add('d-none');
                    }
                });

                // Suggestion click (support clicking on child elements like badge)
                if (suggestionsList) {
                    suggestionsList.addEventListener('click', function(event) {
                        const item = event.target.closest('.suggestion-item');
                        if (item && facultySearch) {
                            facultySearch.value = item.getAttribute('data-value') || '';
                            suggestionsList.classList.add('d-none');
                            loadFeedbackData(1);
                        }
                    });
                }

                // Reset button
                if (resetButton) {
                    resetButton.addEventListener('click', function() {
                        document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                        document.querySelectorAll('input[type="radio"]').forEach(rb => {
                            if (rb.value === 'current') rb.checked = true;
                        });
                        document.querySelectorAll('select').forEach(select => select.value = '');
                        document.querySelectorAll('input[type="date"]').forEach(input => input.value = '');
                        if (facultySearch) facultySearch.value = '';
                        if (suggestionsList) suggestionsList.classList.add('d-none');
                        loadFeedbackData(1);
                    });
                }

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
                if (loadingSpinner) loadingSpinner.classList.remove('loading-hidden');

                // Collect current filter values
                const params = new URLSearchParams();
                params.append('export_type', 'excel');

                const facultySearchEl = document.getElementById('facultySearch');
                params.append('program_id', document.getElementById('programSelect')?.value || '');
                params.append('faculty_name', facultySearchEl ? facultySearchEl.value || '' : '');
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
                form.classList.add('d-none');

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

                setTimeout(() => {
                    if (loadingSpinner) loadingSpinner.classList.add('d-none');
                }, 2000);
            }

            function exportToPDF() {
                const loadingSpinner = document.getElementById('loadingSpinner');
                if (loadingSpinner) loadingSpinner.classList.remove('loading-hidden');

                // Collect current filter values
                const params = new URLSearchParams();
                params.append('export_type', 'pdf');

                const facultySearchEl = document.getElementById('facultySearch');
                params.append('program_id', document.getElementById('programSelect')?.value || '');
                params.append('faculty_name', facultySearchEl ? facultySearchEl.value || '' : '');
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
                form.classList.add('d-none');

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

                setTimeout(() => {
                    if (loadingSpinner) loadingSpinner.classList.add('d-none');
                }, 2000);
            }
        </script>
    @endsection
