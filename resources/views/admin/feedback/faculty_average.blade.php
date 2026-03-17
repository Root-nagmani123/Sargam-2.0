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
                        <div class="btn-group" role="group" style="margin-left: auto; gap: 10px;">
                            <!-- Excel Export -->
                            <a href="{{ route('feedback.average.export.excel', [
                                'course_type' => $courseType,
                                'program_name' => $currentProgram,
                                'faculty_name' => $currentFaculty,
                                'from_date' => $fromDate,
                                'to_date' => $toDate,
                            ]) }}"
                                class="btn btn-success" target="_blank" title="Export to Excel"
                                style="padding: 10px 25px; font-size: 15px; font-weight: 600; border-radius: 6px;">
                                <i class="fas fa-file-excel" style="margin-right: 8px;"></i> Export Excel
                            </a>

                            <!-- PDF Export -->
                            <a href="{{ route('feedback.average.export.pdf', [
                                'course_type' => $courseType,
                                'program_name' => $currentProgram,
                                'faculty_name' => $currentFaculty,
                                'from_date' => $fromDate,
                                'to_date' => $toDate,
                            ]) }}"
                                class="btn btn-danger" target="_blank" title="Export to PDF"
                                style="padding: 10px 25px; font-size: 15px; font-weight: 600; border-radius: 6px;">
                                <i class="fas fa-file-pdf" style="margin-right: 8px;"></i> Export PDF
                            </a>
                        </div>

                        {{-- <small class="text-muted">Data refreshed: {{ $refreshTime ?? now()->format('d-M-Y H:i') }}</small> --}}
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

        // Function to update export links with current filter values
        function updateExportLinks() {
            // Get current values from the filter form
            const courseType = document.querySelector('input[name="course_type"]:checked')?.value || 'current';
            const programName = document.querySelector('select[name="program_name"]')?.value || '';
            const facultyName = document.querySelector('select[name="faculty_name"]')?.value || '';
            const fromDate = document.querySelector('input[name="from_date"]')?.value || '';
            const toDate = document.querySelector('input[name="to_date"]')?.value || '';

            // Get the base URLs
            const excelBaseUrl = "{{ route('feedback.average.export.excel') }}";
            const pdfBaseUrl = "{{ route('feedback.average.export.pdf') }}";

            // Find all export links
            const exportLinks = document.querySelectorAll('.btn-group a');

            exportLinks.forEach(link => {
                if (link.href.includes('export-excel')) {
                    // Update Excel link
                    const url = new URL(excelBaseUrl, window.location.origin);
                    url.searchParams.set('course_type', courseType);
                    url.searchParams.set('program_name', programName);
                    url.searchParams.set('faculty_name', facultyName);
                    url.searchParams.set('from_date', fromDate);
                    url.searchParams.set('to_date', toDate);
                    link.href = url.toString();
                    link.title = `Export to Excel (Program: ${programName})`;
                } else if (link.href.includes('export-pdf')) {
                    // Update PDF link
                    const url = new URL(pdfBaseUrl, window.location.origin);
                    url.searchParams.set('course_type', courseType);
                    url.searchParams.set('program_name', programName);
                    url.searchParams.set('faculty_name', facultyName);
                    url.searchParams.set('from_date', fromDate);
                    url.searchParams.set('to_date', toDate);
                    link.href = url.toString();
                    link.title = `Export to PDF (Program: ${programName})`;
                }
            });

            console.log('Export links updated with:', {
                courseType,
                programName,
                facultyName,
                fromDate,
                toDate
            });
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

            // Add cache busting parameter
            params.append('_', Date.now());

            // Make AJAX request
            fetch(`{{ route('feedback.average') }}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    // Parse the HTML response
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Update program dropdown first (important for course type changes)
                    const newProgramSelect = doc.querySelector('select[name="program_name"]');
                    if (newProgramSelect) {
                        const currentProgramSelect = document.querySelector('select[name="program_name"]');
                        currentProgramSelect.innerHTML = newProgramSelect.innerHTML;
                    }

                    // Extract the table content from the response
                    const newTableContainer = doc.querySelector('#tableContainer');
                    const newProgramTitle = doc.querySelector('.text-center h6');
                    const newRefreshTime = doc.querySelector('.card-header small');

                    // Update the table container
                    if (newTableContainer) {
                        document.getElementById('tableContainer').innerHTML = newTableContainer.innerHTML;
                    }

                    // Update program title if it exists
                    if (newProgramTitle) {
                        // Check if title already exists
                        let programTitleElement = document.querySelector('.text-center h6');
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

                    // Update refresh time
                    if (newRefreshTime) {
                        document.querySelector('.card-header small').textContent = newRefreshTime.textContent;
                    }

                    // IMPORTANT: Update export links with current filter values
                    updateExportLinks();

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

        // Function to load programs when course type changes
        function loadProgramsByCourseType(courseType) {
            fetch(`{{ route('feedback.average') }}?course_type=${courseType}&_=${Date.now()}`, {
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
                        currentProgramSelect.innerHTML = newProgramSelect.innerHTML;
                    }
                })
                .catch(error => console.error('Error loading programs:', error));
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Set default to "current" courses if not already set
            const currentCourseRadio = document.querySelector('input[name="course_type"][value="current"]');
            const archivedCourseRadio = document.querySelector('input[name="course_type"][value="archived"]');

            // If no course type is selected, default to "current"
            if (!currentCourseRadio.checked && !archivedCourseRadio.checked) {
                currentCourseRadio.checked = true;
            }

            // Update export links on page load
            setTimeout(() => {
                updateExportLinks();
            }, 200);

            // Handle course type change separately (special handling)
            document.querySelectorAll('input[name="course_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const courseType = this.value;

                    // Show loading spinner
                    document.getElementById('loadingSpinner').style.display = 'block';
                    document.getElementById('tableContainer').style.display = 'none';

                    // First load programs for the selected course type
                    loadProgramsByCourseType(courseType);

                    // Then load feedback data with a slight delay to ensure programs are updated
                    setTimeout(() => {
                        loadFeedbackData();
                    }, 300);

                    // Update export links after course type change
                    setTimeout(() => {
                        updateExportLinks();
                    }, 600);
                });
            });

            // Handle other filter changes (select, date inputs)
            const filterInputs = document.querySelectorAll(
                '#filterForm select:not([name="course_type"]), #filterForm input[type="date"]'
            );

            filterInputs.forEach(input => {
                // Remove any existing event listeners and add new one
                input.removeEventListener('change', loadFeedbackData);
                input.addEventListener('change', function() {
                    loadFeedbackData();
                    // Update export links after filter change
                    setTimeout(updateExportLinks, 300);
                });
            });

            // Handle program dropdown changes specifically
            const programSelect = document.querySelector('select[name="program_name"]');
            if (programSelect) {
                programSelect.addEventListener('change', function() {
                    // Update export links immediately when program changes
                    setTimeout(updateExportLinks, 100);
                });
            }

            // Handle faculty dropdown changes
            const facultySelect = document.querySelector('select[name="faculty_name"]');
            if (facultySelect) {
                facultySelect.addEventListener('change', function() {
                    setTimeout(updateExportLinks, 100);
                });
            }

            // Handle date inputs changes
            const fromDateInput = document.querySelector('input[name="from_date"]');
            const toDateInput = document.querySelector('input[name="to_date"]');

            if (fromDateInput) {
                fromDateInput.addEventListener('change', function() {
                    setTimeout(updateExportLinks, 100);
                });
            }

            if (toDateInput) {
                toDateInput.addEventListener('change', function() {
                    setTimeout(updateExportLinks, 100);
                });
            }

            // Handle form submit to prevent page refresh
            document.getElementById('filterForm').addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent form submission
                loadFeedbackData(); // Load data via AJAX
            });

            // Initial load of data
            // Small delay to ensure DOM is fully ready
            setTimeout(() => {
                loadFeedbackData();
            }, 100);
        });

        // Optional: Add debounce function for better performance if needed
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Also update export links after any AJAX content is loaded
        document.addEventListener('ajaxComplete', function() {
            setTimeout(updateExportLinks, 300);
        });
    </script>
@endsection
