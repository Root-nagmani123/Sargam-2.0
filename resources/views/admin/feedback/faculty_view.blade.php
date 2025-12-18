@extends('admin.layouts.master')

@section('title', 'Faculty Feedback with Comments Admin View - Sargam | Lal Bahadur')

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

        .remarks-title {
            background: var(--primary);
            color: #fff;
            padding: 0.5rem 0.75rem;
            font-weight: 600;
            border-radius: 4px 4px 0 0;
        }

        .remarks-list {
            border-top: 0;
            border-radius: 0 0 4px 4px;
            padding: 1rem;
        }

        .rating-header {
            color: #af2910 !important;
            font-weight: 600;
        }

        .percentage-cell {
            font-weight: 600;
            color: var(--primary);
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
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

        .faculty-type-badge {
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 10px;
            background: #e9ecef;
            color: #495057;
        }

        /* Pagination styles */
        .pagination-info {
            font-size: 0.875rem;
        }
    </style>

    <div class="container-fluid py-3">
        <x-breadcrum title="Faculty Feedback with Comments Admin View"></x-breadcrum>
        <div class="row g-3">

            <!-- LEFT FILTER PANEL -->
            <aside class="col-lg-3 col-md-4">
                <div class="card filter-card">
                    <div class="card-header">Options</div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.feedback.faculty_view') }}" id="filterForm">
                            <fieldset class="mb-3">
                                <legend class="fs-6 fw-semibold">Course Status</legend>
                                <div class="form-check">
                                    <input class="form-check-input course-type-radio" type="radio" name="course_type" value="current"
                                        id="current" {{ ($courseType ?? 'archived') == 'current' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="current">Current Courses</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input course-type-radio" type="radio" name="course_type" value="archived"
                                        id="archived" {{ ($courseType ?? 'archived') == 'archived' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="archived">Archived Courses</label>
                                </div>
                            </fieldset>

                            <div class="mb-3">
                                <label class="form-label">Program Name</label>
                                <select class="form-select" name="program_id" id="programSelect">
                                    <option value="">All Programs</option>
                                    @foreach ($programs as $key => $program)
                                        <option value="{{ $key }}" {{ $currentProgram == $key ? 'selected' : '' }}>
                                            {{ $program }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">From Date</label>
                                <input type="date" name="from_date" class="form-control" value="{{ $fromDate ?? '' }}" />
                            </div>

                            <div class="mb-3">
                                <label class="form-label">To Date</label>
                                <input type="date" name="to_date" class="form-control" value="{{ $toDate ?? '' }}" />
                            </div>

                            <fieldset class="mb-3">
                                <legend class="fs-6 fw-semibold">Faculty Type</legend>
                                <div class="form-check">
                                    <input class="form-check-input faculty-type-checkbox" type="checkbox"
                                        name="faculty_type[]" value="2" id="faculty_type_guest"
                                        {{ in_array('2', $selectedFacultyTypes ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="faculty_type_guest">
                                        Guest
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input faculty-type-checkbox" type="checkbox"
                                        name="faculty_type[]" value="1" id="faculty_type_internal"
                                        {{ in_array('1', $selectedFacultyTypes ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="faculty_type_internal">
                                        Internal
                                    </label>
                                </div>
                            </fieldset>

                            <div class="mb-4 suggestions-container">
                                <label class="form-label">Faculty Name</label>
                                <input type="text" name="faculty_name" class="form-control"
                                    value="{{ $currentFaculty ?? '' }}" id="facultySearch" placeholder="Type to search..."
                                    autocomplete="off" />

                                <!-- Suggestions dropdown -->
                                <div class="suggestions-list" id="facultySuggestions">
                                    @if (isset($facultySuggestions) && $facultySuggestions->isNotEmpty())
                                        @foreach ($facultySuggestions as $faculty)
                                            <div class="suggestion-item" data-value="{{ $faculty->full_name }}">
                                                {{ $faculty->full_name }}
                                                @php
                                                    $typeMap = [
                                                        '1' => 'Internal',
                                                        '2' => 'Guest',
                                                    ];
                                                    $typeDisplay = $typeMap[$faculty->faculty_type] ?? ucfirst($faculty->faculty_type);
                                                @endphp
                                                <span class="faculty-type-badge ms-2">{{ $typeDisplay }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="suggestion-item text-muted">No faculty found</div>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-50">Apply</button>
                                <button type="button" class="btn btn-outline-secondary w-50"
                                    id="resetButton">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- MAIN CONTENT -->
            <main class="col-lg-9 col-md-8">
                <div class="card content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="page-title">Faculty Feedback with Comments (Admin View)</span>
                        <small class="text-muted">Data refreshed: {{ $refreshTime ?? now()->format('d-M-Y H:i') }}</small>
                    </div>

                    <div class="card-body">
                        <!-- Loading Spinner -->
                        <div class="loading-spinner" id="loadingSpinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading feedback data...</p>
                        </div>

                        <!-- Content Container -->
                        <div id="contentContainer">
                            @if ($feedbackData->isEmpty())
                                <div class="alert alert-info text-center">
                                    No feedback data found for the selected filters.
                                </div>
                            @else
                                @foreach ($feedbackData as $data)
                                    <div class="feedback-section mb-4">
                                        <!-- META INFO -->
                                        <div class="text-center mb-4">
                                            <p class="mb-1"><strong>Course:</strong> {{ $data['program_name'] }}
                                                @if(isset($data['course_status']))
                                                    <span class="faculty-type-badge ms-1">{{ $data['course_status'] }}</span>
                                                @endif
                                            </p>
                                            <p class="mb-1">
                                                <strong>Faculty:</strong> {{ $data['faculty_name'] }}
                                                <span class="faculty-type-badge ms-2">{{ $data['faculty_type'] }}</span>
                                            </p>
                                            <p class="mb-1"><strong>Topic:</strong> {{ $data['topic_name'] }}</p>
                                            @if ($data['start_date'])
                                                <p class="mb-0">
                                                    <strong>Lecture Date:</strong>
                                                    {{ \Carbon\Carbon::parse($data['start_date'])->format('d-M-Y') }}
                                                    @if ($data['end_date'])
                                                        ({{ \Carbon\Carbon::parse($data['start_date'])->format('H:i') }} –
                                                        {{ \Carbon\Carbon::parse($data['end_date'])->format('H:i') }})
                                                    @endif
                                                </p>
                                            @endif
                                        </div>

                                        <!-- FEEDBACK TABLE -->
                                        <div class="table-responsive mb-4">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Rating</th>
                                                        <th scope="col">Content <span class="text-dark">*</span></th>
                                                        <th scope="col">Presentation <span class="text-dark">*</span>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="align-middle text-dark">
                                                    <!-- Excellent -->
                                                    <tr>
                                                        <th class="rating-header" style="color:#af2910 !important;">
                                                            Excellent</th>
                                                        <td>{{ $data['content_counts']['5'] }}</td>
                                                        <td>{{ $data['presentation_counts']['5'] }}</td>
                                                    </tr>
                                                    <!-- Very Good -->
                                                    <tr>
                                                        <th class="rating-header" style="color:#af2910 !important;">Very
                                                            Good</th>
                                                        <td>{{ $data['content_counts']['4'] }}</td>
                                                        <td>{{ $data['presentation_counts']['4'] }}</td>
                                                    </tr>
                                                    <!-- Good -->
                                                    <tr>
                                                        <th class="rating-header" style="color:#af2910 !important;">Good
                                                        </th>
                                                        <td>{{ $data['content_counts']['3'] }}</td>
                                                        <td>{{ $data['presentation_counts']['3'] }}</td>
                                                    </tr>
                                                    <!-- Average -->
                                                    <tr>
                                                        <th class="rating-header" style="color:#af2910 !important;">
                                                            Average</th>
                                                        <td>{{ $data['content_counts']['2'] }}</td>
                                                        <td>{{ $data['presentation_counts']['2'] }}</td>
                                                    </tr>
                                                    <!-- Below Average -->
                                                    <tr>
                                                        <th class="rating-header" style="color:#af2910 !important;">Below
                                                            Average</th>
                                                        <td>{{ $data['content_counts']['1'] }}</td>
                                                        <td>{{ $data['presentation_counts']['1'] }}</td>
                                                    </tr>
                                                    <!-- Percentage -->
                                                    <tr class="fw-semibold">
                                                        <th class="rating-header" style="color:#af2910 !important;">
                                                            Percentage</th>
                                                        <td class="percentage-cell">
                                                            {{ number_format($data['content_percentage'], 2) }}%</td>
                                                        <td class="percentage-cell">
                                                            {{ number_format($data['presentation_percentage'], 2) }}%</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <small>* is defined as Total Student Count: {{ $data['participants'] }}</small>
                                        </div>

                                        <!-- REMARKS -->
                                        @if (!empty($data['remarks']))
                                            <div class="mb-2">
                                                <div class="remarks-title">Remarks ({{ count($data['remarks']) }})</div>
                                                <ol class="remarks-list py-2">
                                                    @foreach ($data['remarks'] as $index => $remark)
                                                        <li>{{ $remark }}</li>
                                                    @endforeach
                                                </ol>
                                            </div>
                                        @endif

                                        <hr class="my-4">
                                    </div>
                                @endforeach

                                <!-- PAGINATION - 1 RECORD PER PAGE -->
                                @if ($totalRecords > 1)
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div>
                                            <small class="text-muted pagination-info">
                                                Showing record {{ $currentPage }} of {{ $totalRecords }}
                                                (Page {{ $currentPage }} of {{ $totalPages }})
                                            </small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <!-- Previous Button -->
                                            @if ($currentPage > 1)
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="goToPage({{ $currentPage - 1 }})">
                                                    ← Previous
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                    ← Previous
                                                </button>
                                            @endif

                                            <!-- Page Indicator -->
                                            <span class="mx-2 align-self-center">
                                                Page {{ $currentPage }} of {{ $totalPages }}
                                            </span>

                                            <!-- Next Button -->
                                            @if ($currentPage < $totalPages)
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="goToPage({{ $currentPage + 1 }})">
                                                    Next →
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                    Next →
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @elseif ($totalRecords == 1)
                                    <div class="d-flex justify-content-center mt-3">
                                        <small class="text-muted pagination-info">
                                            Showing 1 record
                                        </small>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filterForm');
            const facultySearch = document.getElementById('facultySearch');
            const suggestionsList = document.getElementById('facultySuggestions');
            const facultyTypeCheckboxes = document.querySelectorAll('.faculty-type-checkbox');
            const courseTypeRadios = document.querySelectorAll('.course-type-radio');
            const programSelect = document.getElementById('programSelect');
            const resetButton = document.getElementById('resetButton');
            let debounceTimer;

            // Function to reload programs based on course type
            function reloadPrograms() {
                const courseType = document.querySelector('input[name="course_type"]:checked')?.value || 'archived';
                
                // Show loading state for program dropdown
                const originalValue = programSelect.value;
                programSelect.innerHTML = '<option value="">Loading programs...</option>';
                programSelect.disabled = true;
                
                // Trigger the form submission which will reload everything including programs
                loadFeedbackData();
                
                // After loading, re-enable the dropdown (it will be populated by the AJAX response)
                setTimeout(() => {
                    programSelect.disabled = false;
                }, 1000);
            }

            // Show/hide suggestions based on faculty type selection
            function updateFacultySuggestions() {
                const selectedTypes = Array.from(facultyTypeCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selectedTypes.length === 0) {
                    suggestionsList.style.display = 'none';
                    return;
                }

                const searchTerm = facultySearch.value.trim();

                // Debounce to avoid too many requests
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    fetchFacultySuggestions(selectedTypes, searchTerm);
                }, 300);
            }

            // Fetch faculty suggestions from server
            function fetchFacultySuggestions(selectedTypes, searchTerm = '') {
                // Create form data to properly handle array
                const formData = new FormData();
                selectedTypes.forEach(type => {
                    formData.append('faculty_type[]', type);
                });
                if (searchTerm) {
                    formData.append('faculty_name', searchTerm);
                }
                formData.append('_token', '{{ csrf_token() }}');

                fetch(`{{ route('feedback.faculty_suggestions') }}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.faculties && data.faculties.length > 0) {
                        suggestionsList.innerHTML = '';
                        data.faculties.forEach(faculty => {
                            const item = document.createElement('div');
                            item.className = 'suggestion-item';
                            item.textContent = faculty.full_name;
                            item.setAttribute('data-value', faculty.full_name);

                            const badge = document.createElement('span');
                            badge.className = 'faculty-type-badge ms-2';
                            badge.textContent = faculty.faculty_type_display;
                            item.appendChild(badge);

                            item.addEventListener('click', function() {
                                facultySearch.value = this.getAttribute('data-value');
                                suggestionsList.style.display = 'none';
                                loadFeedbackData();
                            });

                            suggestionsList.appendChild(item);
                        });
                        suggestionsList.style.display = 'block';
                    } else {
                        suggestionsList.innerHTML =
                            '<div class="suggestion-item text-muted">No faculty found</div>';
                        suggestionsList.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error fetching suggestions:', error);
                    suggestionsList.innerHTML =
                        '<div class="suggestion-item text-muted">Error loading suggestions</div>';
                    suggestionsList.style.display = 'block';
                });
            }

            // Toggle suggestions dropdown
            facultySearch.addEventListener('focus', function() {
                const selectedTypes = Array.from(facultyTypeCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selectedTypes.length > 0) {
                    updateFacultySuggestions();
                }
            });

            facultySearch.addEventListener('input', updateFacultySuggestions);

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(event) {
                if (!facultySearch.contains(event.target) && !suggestionsList.contains(event.target)) {
                    suggestionsList.style.display = 'none';
                }
            });

            // Course type radio change
            courseTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    reloadPrograms();
                });
            });

            // Faculty type checkbox change
            facultyTypeCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    facultySearch.value = ''; // Clear faculty search when type changes
                    updateFacultySuggestions();
                    loadFeedbackData();
                });
            });

            // Form submission via AJAX
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                loadFeedbackData();
            });

            // Reset button - reset form without page refresh
            resetButton.addEventListener('click', function() {
                // Reset form values
                filterForm.reset();

                // Set default course type to archived
                document.querySelector('input[name="course_type"][value="archived"]').checked = true;

                // Clear suggestions
                facultySearch.value = '';
                suggestionsList.innerHTML = '';
                suggestionsList.style.display = 'none';

                // Load data with reset filters (go to page 1)
                goToPage(1);
            });

            // Auto-load on filter change (except course type which is handled separately)
            const filterInputs = document.querySelectorAll('#filterForm select[name="program_id"], #filterForm input[type="date"]');
            filterInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // When filters change, go back to page 1
                    loadFeedbackData();
                });
            });

            // Load initial data
            loadFeedbackData();
        });

        // Function to load feedback data with current filters
        function loadFeedbackData() {
            const loadingSpinner = document.getElementById('loadingSpinner');
            const contentContainer = document.getElementById('contentContainer');
            const programSelect = document.getElementById('programSelect');
            const form = document.getElementById('filterForm');

            loadingSpinner.style.display = 'block';
            contentContainer.style.display = 'none';

            const formData = new FormData(form);
            const params = new URLSearchParams();
            
            // Convert form data to URL parameters
            for (const [key, value] of formData) {
                if (key === 'program_id') {
                    // Keep program_id as is
                    params.append(key, value);
                } else if (Array.isArray(value)) {
                    value.forEach(val => params.append(key + '[]', val));
                } else {
                    params.append(key, value);
                }
            }

            // Keep current page if it exists in URL (for pagination)
            const urlParams = new URLSearchParams(window.location.search);
            const currentPage = urlParams.get('page');
            if (currentPage) {
                params.append('page', currentPage);
            }

            fetch(`{{ route('admin.feedback.faculty_view') }}?${params.toString()}`)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Update programs dropdown
                    const newProgramSelect = doc.getElementById('programSelect');
                    if (newProgramSelect) {
                        const currentProgramId = programSelect.value;
                        programSelect.innerHTML = newProgramSelect.innerHTML;
                        // Try to preserve the selected program
                        if (currentProgramId) {
                            const optionExists = Array.from(programSelect.options).some(opt => opt.value === currentProgramId);
                            if (optionExists) {
                                programSelect.value = currentProgramId;
                            }
                        }
                    }
                    
                    // Update content
                    const newContent = doc.querySelector('#contentContainer');
                    const newRefreshTime = doc.querySelector('.card-header small');

                    if (newContent) {
                        contentContainer.innerHTML = newContent.innerHTML;
                    }

                    if (newRefreshTime) {
                        document.querySelector('.card-header small').textContent = newRefreshTime.textContent;
                    }

                    // Update URL without reloading page
                    window.history.pushState({}, '', `{{ route('admin.feedback.faculty_view') }}?${params.toString()}`);

                    loadingSpinner.style.display = 'none';
                    contentContainer.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error loading feedback data:', error);
                    loadingSpinner.style.display = 'none';
                    contentContainer.style.display = 'block';
                    contentContainer.innerHTML =
                        '<div class="alert alert-danger text-center">Error loading data. Please try again.</div>';
                });
        }

        // Simple pagination function - go to specific page
        function goToPage(pageNumber) {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            const params = new URLSearchParams();
            
            // Add all form data
            for (const [key, value] of formData) {
                if (Array.isArray(value)) {
                    value.forEach(val => params.append(key + '[]', val));
                } else {
                    params.append(key, value);
                }
            }
            
            // Add page number
            params.append('page', pageNumber);
            
            // Update URL and load data
            const newUrl = `{{ route('admin.feedback.faculty_view') }}?${params.toString()}`;
            window.history.pushState({}, '', newUrl);
            loadFeedbackData();
        }

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function() {
            loadFeedbackData();
        });
    </script>
@endsection