@extends('admin.layouts.master')

@section('title', 'Faculty Feedback Average - Sargam | Lal Bahadur')

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
    </style>
    <div class="container-fluid py-3">
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
                                    <input class="form-check-input" type="radio" name="course_type" value="archived"
                                        id="archived" {{ ($courseType ?? 'current') == 'archived' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="archived">Archived Courses</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="course_type" value="current"
                                        id="current" {{ ($courseType ?? 'current') == 'current' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="current">Current Courses</label>
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
                                <select class="form-select select2" name="faculty_name">
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
