@extends('admin.layouts.master')

@section('title', 'Faculty Feedback Average - Sargam | Lal Bahadur')

@section('css')
    @parent
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <style>
        .faculty-average-page .choices__inner {
            min-height: calc(2.25rem + 2px);
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
            background-color: #fff;
        }

        .faculty-average-page .choices__list--single .choices__item {
            padding: 0;
            margin: 0;
        }

        .faculty-average-page .choices__list--dropdown {
            border-radius: 0.375rem;
            border-color: #ced4da;
        }

        .faculty-average-page .choices.is-focused .choices__inner,
        .faculty-average-page .choices.is-open .choices__inner {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
@endsection

@section('setup_content')
    <style>
        /* Keep your existing CSS styles */
        :root {
            --primary: #0b4f8a;
            --secondary: #f4f6f9;
            --accent: #f2b705;
            --border: #d0d7de;
            --text-dark: #1f2937;
        }

        body {
            background: var(--secondary);
            font-size: 14px;
            color: var(--text-dark);
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

        .faculty-name {
            font-weight: 600;
            color: #0b4f8a;
        }

        .percentage-good {
            color: #198754;
            font-weight: 600;
        }

        .percentage-average {
            color: #b45309;
            font-weight: 600;
        }

        .percentage-low {
            color: #dc3545;
            font-weight: 600;
        }

        .pagination .page-link {
            color: var(--primary);
        }

        .pagination .active .page-link {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: #083e6c;
        }

        /* Loading spinner */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        /* Responsive styles - only apply below desktop breakpoint */
        @media (max-width: 991.98px) {
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            .filter-card .card-body {
                padding: 1rem;
            }
            .content-card .card-header {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .content-card .card-header small {
                font-size: 0.75rem;
                width: 100%;
            }
        }

        @media (max-width: 767.98px) {
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
                padding-top: 0.75rem;
                padding-bottom: 0.75rem;
            }
            .row.g-3 {
                --bs-gutter-x: 0.75rem;
                --bs-gutter-y: 0.75rem;
            }
            .filter-card .card-header {
                font-size: 0.95rem;
                padding: 0.6rem 1rem;
            }
            .filter-card .card-body {
                padding: 0.75rem 1rem;
            }
            .filter-card fieldset {
                margin-bottom: 0.75rem !important;
            }
            .filter-card .form-label {
                font-size: 0.875rem;
            }
            .filter-card .form-select,
            .filter-card .form-control {
                font-size: 0.875rem;
            }
            .filter-card .d-flex.gap-2 {
                flex-direction: column;
            }
            .filter-card .d-flex.gap-2 .btn {
                width: 100% !important;
            }
            .content-card .card-header {
                flex-direction: column;
                align-items: flex-start !important;
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
            }
            .content-card .card-header small {
                font-size: 0.7rem;
            }
            .content-card .card-body {
                padding: 0.75rem;
            }
            .table-responsive {
                margin-left: -0.75rem;
                margin-right: -0.75rem;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .table-responsive .table {
                font-size: 0.8rem;
                margin-bottom: 0;
            }
            .table-responsive .table th,
            .table-responsive .table td {
                padding: 0.5rem 0.4rem;
                white-space: nowrap;
            }
            .table-responsive .table th:nth-child(2),
            .table-responsive .table td:nth-child(2) {
                max-width: 140px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .table-responsive .faculty-name {
                font-size: 0.8rem;
            }
            .text-center.mb-3 h6 {
                font-size: 0.9rem;
            }
            .d-flex.justify-content-between.align-items-center.mt-3 {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start !important;
            }
        }

        @media (max-width: 575.98px) {
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            .filter-card .card-body {
                padding: 0.6rem 0.75rem;
            }
            .content-card .card-body {
                padding: 0.5rem;
            }
            .table-responsive {
                margin-left: -0.5rem;
                margin-right: -0.5rem;
            }
            .table-responsive .table {
                font-size: 0.75rem;
            }
            .table-responsive .table th,
            .table-responsive .table td {
                padding: 0.4rem 0.35rem;
            }
            .table-responsive .table th:nth-child(6),
            .table-responsive .table td:nth-child(6) {
                min-width: 90px;
            }
        }
    </style>
    <div class="container-fluid faculty-average-page py-3">
        <x-breadcrum title="Faculty Feedback Average"></x-breadcrum>

        <div class="row g-3">
            <!-- LEFT FILTER PANEL -->
            <aside class="col-lg-3 col-md-4">
                <div class="card filter-card">
                    <div class="card-header">Options</div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('feedback.average') }}" id="filterForm">
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
                                <select class="form-select" name="program_name">
                                    <option value="">All Programs</option>
                                    @foreach ($programs as $key => $program)
                                        <option value="{{ $key }}" {{ $currentProgram == $key ? 'selected' : '' }}>
                                            {{ $program }}
                                        </option>
                                    @endforeach
                                </select>

                            </div>

                            <div class="mb-3">
                                <label class="form-label">Faculty Name</label>
                                <select class="form-select" name="faculty_name">
                                    <option value="">All Faculty</option>
                                    @foreach ($faculties as $key => $faculty)
                                        <option value="{{ $key }}" {{ $currentFaculty == $key ? 'selected' : '' }}>
                                            {{ $faculty }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="from_date" value="{{ $fromDate ?? '' }}" />
                            </div>

                            <div class="mb-4">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="to_date" value="{{ $toDate ?? '' }}" />
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-50">Apply</button>
                                <button type="button" class="btn btn-outline-secondary w-50"
                                    onclick="resetFilters()">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- MAIN CONTENT -->
            <main class="col-lg-9 col-md-8">
                <div class="card content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Faculty Feedback Average</span>
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

                        <!-- Table Container (initially visible) -->
                        <div id="tableContainer">
                            @if (!empty($currentProgramName))
                                <div class="text-center mb-3">
                                    <h6 class="fw-semibold mb-0">{{ $currentProgramName }}</h6>
                                </div>
                            @endif

                            @if ($feedbackData->isEmpty())
                                <div class="alert alert-info text-center">
                                    No feedback data found for the selected filters.
                                </div>
                            @else
                                <!-- TABLE -->
                                <div class="table-responsive">
                                    <table class="table table-hover" id="feedbackTable">
                                        <thead>
                                            <tr>
                                                <th>Faculty</th>
                                                <th>Topic</th>
                                                <th>Content (%)</th>
                                                <th>Presentation (%)</th>
                                                <th>Participants</th>
                                                <th>Session Date & Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($feedbackData as $data)
                                                <tr>
                                                    <td class="faculty-name">{{ $data['faculty_name'] }}</td>
                                                    <td>{{ Str::limit($data['topic_name'], 40) }}</td>
                                                    <td
                                                        class="{{ $data['content_percentage'] >= 90 ? 'percentage-good' : ($data['content_percentage'] >= 70 ? 'percentage-average' : 'percentage-low') }}">
                                                        {{ number_format($data['content_percentage'], 2) }}
                                                    </td>
                                                    <td
                                                        class="{{ $data['presentation_percentage'] >= 90 ? 'percentage-good' : ($data['presentation_percentage'] >= 70 ? 'percentage-average' : 'percentage-low') }}">
                                                        {{ number_format($data['presentation_percentage'], 2) }}
                                                    </td>
                                                    <td class="text-center">{{ $data['participants'] }}</td>
                                                    <td>
                                                        <div class="fw-semibold">
                                                            {{ \Carbon\Carbon::parse($data['session_date'])->format('d M Y') }}
                                                        </div>
                                                        <div class="text-muted small">
                                                            {{ $data['class_session'] }}
                                                        </div>
                                                    </td>

                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- PAGINATION (if needed) -->
                                @if ($feedbackData->count() > 20)
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <small class="text-muted">Showing {{ $feedbackData->count() }} records</small>
                                        <nav aria-label="Feedback pagination">
                                            <ul class="pagination mb-0">
                                                <!-- Add pagination logic if needed -->
                                            </ul>
                                        </nav>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        function resetFilters() {
            // Reset form to default values
            document.getElementById('filterForm').reset();
            // Set default to "current" courses
            document.querySelector('input[name="course_type"][value="current"]').checked = true;
            // Submit the form
            document.getElementById('filterForm').submit();
        }

        // AJAX function to load data without page refresh
        function loadFeedbackData() {
            // Show loading spinner
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('tableContainer').style.display = 'none';

            // Get form data
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);

            // Convert FormData to URL parameters
            const params = new URLSearchParams();
            for (const [key, value] of formData) {
                params.append(key, value);
            }

            // Make AJAX request
            fetch(`{{ route('feedback.average') }}?${params.toString()}`)
                .then(response => response.text())
                .then(html => {
                    // Parse the HTML response
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Extract the table content from the response
                    const newTableContainer = doc.querySelector('#tableContainer');
                    const newProgramTitle = doc.querySelector('.text-center h6');
                    const newRefreshTime = doc.querySelector('.card-header small');

                    // Update the DOM
                    if (newTableContainer) {
                        document.getElementById('tableContainer').innerHTML = newTableContainer.innerHTML;
                    }

                    if (newProgramTitle) {
                        const programTitleElement = document.querySelector('.text-center h6');
                        if (programTitleElement) {
                            programTitleElement.textContent = newProgramTitle.textContent;
                        } else {
                            // Create title if it doesn't exist
                            const titleDiv = document.createElement('div');
                            titleDiv.className = 'text-center mb-3';
                            titleDiv.innerHTML = `<h6 class="fw-semibold mb-0">${newProgramTitle.textContent}</h6>`;
                            document.querySelector('#tableContainer').prepend(titleDiv);
                        }
                    }

                    if (newRefreshTime) {
                        document.querySelector('.card-header small').textContent = newRefreshTime.textContent;
                    }

                    // Hide loading spinner and show table
                    document.getElementById('loadingSpinner').style.display = 'none';
                    document.getElementById('tableContainer').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error loading feedback data:', error);
                    document.getElementById('loadingSpinner').style.display = 'none';
                    document.getElementById('tableContainer').style.display = 'block';
                    document.getElementById('tableContainer').innerHTML =
                        '<div class="alert alert-danger text-center">Error loading data. Please try again.</div>';
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Load initial data on page load (for current courses)
            // This ensures data is shown immediately

            // Set default to "current" courses if not already set
            const currentCourseRadio = document.querySelector('input[name="course_type"][value="current"]');
            const archivedCourseRadio = document.querySelector('input[name="course_type"][value="archived"]');

            // If no course type is selected, default to "current"
            if (!currentCourseRadio.checked && !archivedCourseRadio.checked) {
                currentCourseRadio.checked = true;
            }

            // Remove auto-submit on filter change
            const filterInputs = document.querySelectorAll(
                '#filterForm select, #filterForm input[type="radio"], #filterForm input[type="date"]');
            filterInputs.forEach(input => {
                // Remove any existing change event listeners
                input.removeEventListener('change', loadFeedbackData);
                // Add new event listener for AJAX loading
                input.addEventListener('change', loadFeedbackData);
            });

            // Also handle form submit to prevent page refresh
            document.getElementById('filterForm').addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent form submission
                loadFeedbackData(); // Load data via AJAX
            });
        });

        // Initialize Choices.js on selects in this page
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Choices === 'undefined') return;

            document.querySelectorAll('.faculty-average-page select').forEach(function (el) {
                if (el.dataset.choicesInitialized === 'true') return;

                new Choices(el, {
                    allowHTML: false,
                    searchPlaceholderValue: 'Search...',
                    removeItemButton: !!el.multiple,
                    shouldSort: false,
                    placeholder: true,
                    placeholderValue: el.getAttribute('placeholder') || el.options[0]?.text || 'Select an option',
                });

                el.dataset.choicesInitialized = 'true';
            });
        });
        document.querySelectorAll('input[name="course_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const courseType = this.value;
                fetch(`{{ route('feedback.average') }}?course_type=${courseType}`)
                    .then(res => res.text())
                    .then(html => {
                        // Extract new program options from response
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newSelect = doc.querySelector('select[name="program_name"]');
                        if (newSelect) {
                            document.querySelector('select[name="program_name"]').innerHTML = newSelect
                                .innerHTML;
                        }
                    });
            });
        });
    </script>
@endsection
