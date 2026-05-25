@php
    $fr = $fr ?? $feedbackReportRoutes ?? \App\Support\FeedbackReportRouteRegistry::forRequest();
@endphp
@extends('admin.layouts.master')

@section('title', 'Average Rating - Course / Topic wise - Sargam | Lal Bahadur')

@section('setup_content')
<style>
    /* --- Course toggle --- */
    .fv-course-radio + label { background: transparent; color: #495057; border: none !important; font-weight: 600; padding: 8px 24px; border-radius: 8px; cursor: pointer; transition: background .2s,color .2s; }
    .fv-course-radio:checked + label { background: #1b3a5c !important; color: #fff !important; border-radius: 8px !important; }
    /* --- Filter toolbar --- */
    .fv-filter-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .fv-filter-row .btn-outline-secondary { font-size: 0.8125rem; border-radius: 6px; color: #495057; padding: 5px 14px; background: #fff; }
    .fv-filter-row .form-select { font-size: 0.8125rem; border-radius: 6px; border-color: #dee2e6; }
    .fv-reset-btn { border: 1.5px solid #dc3545; color: #dc3545; background: transparent; border-radius: 6px; font-size: 0.8125rem; padding: 5px 14px; font-weight: 500; white-space: nowrap; }
    .fv-reset-btn:hover { background: #dc3545; color: #fff; }
    .fv-search-btn { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .fv-search-btn:hover { background: #e9ecef; }
    /* --- Table --- */
    #fvTable thead th { background-color: #f8f9fa; font-size: 0.8125rem; font-weight: 600; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 12px 14px; white-space: nowrap; }
    #fvTable tbody td { font-size: 0.875rem; padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid #f1f3f5; color: #212529; }
    #fvTable tbody tr:hover td { background-color: #fafbfc; }
    /* --- Pagination --- */
    .fv-pagination .page-link { color: #1b3a5c; border-radius: 4px !important; margin: 0 2px; border: none; background: transparent; font-size: 0.8125rem; padding: 5px 10px; }
    .fv-pagination .page-link:hover { background: #f1f3f5; }
    .fv-pagination .page-item.active .page-link { background-color: #1b3a5c; border-color: #1b3a5c; color: #fff; }
    .fv-pagination .page-item.disabled .page-link { opacity: .35; }
    #fvPaginationCell { display: flex; align-items: center; flex-wrap: wrap; gap: 2px; }
    /* --- Misc --- */
    .faculty-type-badge { font-size: .7rem; font-weight: 500; padding: .2rem .5rem; border-radius: 50rem; background: #e9ecef; color: #495057; border: 1px solid #dee2e6; }
    .suggestions-container { position: relative; }
    .suggestions-list { position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 1080; display: none; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
    .suggestion-item { padding: .5rem .85rem; cursor: pointer; border-bottom: 1px solid #f1f1f1; font-size: .875rem; }
    .suggestion-item:hover { background: #f8f9fa; }
    .suggestion-item:last-child { border-bottom: none; }
    #fvLoadingSpinner { display: none; position: fixed; inset: 0; z-index: 1090; align-items: center; justify-content: center; background: rgba(0,0,0,.06); backdrop-filter: blur(2px); }
    #fvLoadingSpinner.fv-loading { display: flex !important; }
    @media print { .no-print { display: none !important; } }
</style>

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <x-breadcrum title="Average Rating - Course / Topic wise"></x-breadcrum>
    <div class="row g-3">

        <!-- LEFT FILTER PANEL -->
        <aside class="col-lg-3 col-md-4">
            <div class="card filter-card">
                <div class="card-header">Options</div>
                <div class="card-body">
                    <form method="POST" action="{{ $fr['comments_submit'] }}" id="filterForm">
                        @csrf
                        <input type="hidden" name="page" id="pageInput" value="{{ $currentPage ?? 1 }}">

                        <fieldset class="mb-3">
                            <legend class="fs-6 fw-semibold">Course Status</legend>
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

                        <div class="mb-3">
                            <label class="form-label">Program Name</label>
                            <select class="form-select" name="program_id" id="programSelect">
                                <option value="">All Programs</option>
                                @php
                                $programs = $programs ?? collect([]);
                                $currentProgram = $currentProgram ?? '';
                                @endphp
                                @foreach ($programs as $key => $program)
                                <option value="{{ $key }}" {{ $currentProgram == $key ? 'selected' : '' }}>
                                    {{ $program }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">To</label>
                            <input type="date" id="fvToDate" class="form-control form-control-sm" value="{{ $toDate ?? '' }}">
                        </div>
                    </div>
                </div>

                {{-- Faculty Type --}}
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Faculty Type</button>
                    <div class="dropdown-menu p-3" style="min-width:160px;">
                        @php $selectedFacultyTypes = $selectedFacultyTypes ?? []; @endphp
                        <div class="form-check mb-2">
                            <input class="form-check-input faculty-type-checkbox" type="checkbox" value="2" id="fvGuest" {{ in_array('2', $selectedFacultyTypes) ? 'checked' : '' }}>
                            <label class="form-check-label" for="fvGuest">Guest</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input faculty-type-checkbox" type="checkbox" value="1" id="fvInternal" {{ in_array('1', $selectedFacultyTypes) ? 'checked' : '' }}>
                            <label class="form-check-label" for="fvInternal">Internal</label>
                        </div>
                    </div>
                </div>

                {{-- Faculty Name search --}}
                <div class="suggestions-container" style="max-width:220px;">
                    <input type="text" id="facultySearch" class="form-control form-control-sm"
                        value="{{ $currentFaculty ?? '' }}" placeholder="Search faculty..." autocomplete="off">
                    <div class="suggestions-list" id="facultySuggestions">
                        @php $facultySuggestions = $facultySuggestions ?? collect([]); @endphp
                        @if ($facultySuggestions->isNotEmpty())
                            @foreach ($facultySuggestions as $faculty)
                                <div class="suggestion-item" data-value="{{ $faculty->full_name }}">
                                    {{ $faculty->full_name }}
                                    @php $typeMap=['1'=>'Internal','2'=>'Guest']; $typeDisplay=$typeMap[$faculty->faculty_type]??ucfirst($faculty->faculty_type); @endphp
                                    <span class="faculty-type-badge ms-2">{{ $typeDisplay }}</span>
                                </div>
                            @endforeach
                        @else
                            <div class="suggestion-item text-muted small">No faculty found</div>
                        @endif
                    </div>
                </div>

                <button type="button" class="fv-reset-btn" id="resetButton">Reset Filters</button>
                <button type="button" class="fv-search-btn ms-auto" id="applyFiltersBtn" title="Apply filters">
                    <span class="material-symbols-rounded" style="font-size:18px;color:#6c757d;">search</span>
                </button>
            </div>

            {{-- Hidden form - backend POST structure preserved exactly --}}
            <form method="POST" action="{{ route('admin.feedback.faculty_view') }}" id="filterForm" style="display:none;">
                @csrf
                <input type="hidden" name="course_type"  id="fvHiddenCourseType" value="{{ $courseType ?? 'current' }}">
                <input type="hidden" name="program_id"   id="fvHiddenProgram"    value="{{ $currentProgram ?? '' }}">
                <input type="hidden" name="from_date"    id="fvHiddenFrom"       value="{{ $fromDate ?? '' }}">
                <input type="hidden" name="to_date"      id="fvHiddenTo"         value="{{ $toDate ?? '' }}">
                <input type="hidden" name="faculty_name" id="fvHiddenFaculty"    value="{{ $currentFaculty ?? '' }}">
                <input type="hidden" name="page"         id="pageInput"          value="{{ $currentPage ?? 1 }}">
            </form>

            {{-- Content container --}}
            <div id="contentContainer">
                @php
                    $feedbackData = $feedbackData ?? collect([]);
                    $currentPage  = $currentPage  ?? 1;
                    $totalRecords = $totalRecords  ?? 0;
                    $totalPages   = $totalPages    ?? 0;
                @endphp
                <span id="fvMeta" data-page="{{ $currentPage }}" data-total="{{ $totalRecords }}" data-pages="{{ $totalPages }}" style="display:none;"></span>
                @if ($feedbackData->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <span class="material-symbols-rounded d-block mb-2" style="font-size:2.5rem;opacity:.35;">rate_review</span>
                        No feedback data found for the selected filters.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="fvTable">
                            <thead>
                                <tr>
                                    <th style="width:55px">S. No.</th>
                                    <th>Faculty</th>
                                    <th>Topic</th>
                                    <th>Program Name</th>
                                    <th>Content</th>
                                    <th>Presentation</th>
                                    <th>Lecture Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($feedbackData as $i => $data)
                                    @php
                                        $cp = $data['content_percentage'] ?? 0;
                                        $pp = $data['presentation_percentage'] ?? 0;
                                        $cpColor = $cp >= 70 ? '#198754' : ($cp >= 40 ? '#fd7e14' : '#dc3545');
                                        $ppColor = $pp >= 70 ? '#198754' : ($pp >= 40 ? '#fd7e14' : '#dc3545');
                                    @endphp
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            {{ $data['faculty_name'] ?? '' }}
                                            @if (!empty($data['faculty_type']))
                                                <span class="faculty-type-badge ms-1">{{ $data['faculty_type'] }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $data['topic_name'] ?? '' }}</td>
                                        <td>{{ $data['program_name'] ?? '' }}</td>
                                        <td><span style="color:{{ $cpColor }};font-size:.8125rem;font-weight:600;">{{ number_format($cp, 1) }}%</span></td>
                                        <td><span style="color:{{ $ppColor }};font-size:.8125rem;font-weight:600;">{{ number_format($pp, 1) }}%</span></td>
                                        <td>
                                            <small class="text-body-secondary">
                                                @if (!empty($data['formatted_start_date']))
                                                    {{ $data['formatted_start_date'] }}
                                                @elseif (!empty($data['start_date']))
                                                    {{ \Carbon\Carbon::parse($data['start_date'])->format('d-M-Y') }}
                                                @else
                                                    -
                                                @endif
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Bottom row --}}
            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2 no-print" id="fvBottomRow">
                <div id="fvPaginationCell"></div>
                <div class="d-flex align-items-center gap-1">
                    <span class="text-muted small">Showing</span>
                    <select id="fvPerPage" class="form-select form-select-sm" style="width:78px;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200" selected>200</option>
                    </select>
                    <span id="fvTotalInfo" class="text-muted small">of {{ $totalRecords }} items</span>
                </div>
            </div>
        </div>
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
    const pageInput = document.getElementById('pageInput');
    let debounceTimer;

    // Function to reload programs based on course type
    function reloadPrograms() {
        const courseType = document.querySelector('input[name="course_type"]:checked')?.value || 'archived';

        // Show loading state for program dropdown
        programSelect.innerHTML = '<option value="">Loading programs...</option>';
        programSelect.disabled = true;

        // Reset to page 1 when course type changes
        goToPage(1);
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
        const params = new URLSearchParams();

        selectedTypes.forEach(type => {
            params.append('faculty_type[]', type);
        });

        if (searchTerm) {
            params.append('faculty_name', searchTerm);
        }

        fetch(`{{ $fr['comments_suggestions'] }}?${params.toString()}`, {
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
                            goToPage(1); // Reset to page 1
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
            goToPage(1); // Reset to page 1
        });
    });

    // Form submission via AJAX - prevent default and handle via AJAX
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        goToPage(1); // Always start from page 1 on form submit
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

        // Reset program dropdown to show all programs
        programSelect.innerHTML = '<option value="">Loading programs...</option>';
        programSelect.disabled = true;

        // Reset page to 1
        pageInput.value = 1;

        // Load data with reset filters (go to page 1)
        goToPage(1);
    });

    // Auto-load on filter change
    const filterInputs = document.querySelectorAll(
        '#filterForm select[name="program_id"], #filterForm input[type="date"]');
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            goToPage(1); // Reset to page 1 when filters change
        });
    });

    // Load initial data with current page
    loadFeedbackData({{ $currentPage ?? 1 }});
});

// Function to load feedback data with current filters
function loadFeedbackData(page = 1) {
    const loadingSpinner = document.getElementById('loadingSpinner');
    const contentContainer = document.getElementById('contentContainer');
    const programSelect = document.getElementById('programSelect');
    const form = document.getElementById('filterForm');
    const pageInput = document.getElementById('pageInput');

    spinner.classList.add('fv-loading');
    contentContainer.style.opacity = '0.5';
    pageInput.value = page;

    var formData = new FormData(form);
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) formData.append('_token', csrfToken);

    fetch(`{{ $fr['comments_submit'] }}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

        // Update programs dropdown (original logic)
        var newSel = doc.getElementById('programSelect');
        if (newSel) {
            var curVal = programSelect.value;
            programSelect.innerHTML = newSel.innerHTML;
            if (curVal && Array.from(programSelect.options).some(function(o){ return o.value===curVal; })) {
                programSelect.value = curVal;
            }
        }

            // Update content
            const newContent = doc.querySelector('#contentContainer');
            const newRefreshTime = doc.querySelector('.card-header small');

            if (newContent) {
                contentContainer.innerHTML = newContent.innerHTML;
            }

            if (newRefreshTime) {
                const refreshElement = document.querySelector('.card-header small');
                if (refreshElement) {
                    refreshElement.textContent = newRefreshTime.textContent;
                }
            }

            // Update page input with current page from response
            const newPageInput = doc.getElementById('pageInput');
            if (newPageInput) {
                pageInput.value = newPageInput.value;
            }

            // Update URL to clean version without parameters
            const cleanUrl = `{{ $fr['comments'] }}`;
            if (window.location.href !== cleanUrl) {
                window.history.replaceState({}, '', cleanUrl);
            }

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
    fvSyncForm();
    loadFeedbackData(pageNumber);
}

function exportToExcel() {
    var spinner = document.getElementById('fvLoadingSpinner');
    var form = document.getElementById('filterForm');
    spinner.classList.add('fv-loading');
    var formData = new FormData(form);
    formData.append('export_type', 'excel');
    formData.append('page', 'all');

    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }

    fetch(`{{ $fr['comments_export'] }}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.blob();
        })
        .then(blob => {
            // Create download link
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `faculty_feedback_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            // Reset loading message
            loadingSpinner.style.display = 'none';
            loadingSpinner.querySelector('p').textContent = 'Loading feedback data...';
        })
        .catch(error => {
            console.error('Error exporting to Excel:', error);
            loadingSpinner.style.display = 'none';
            loadingSpinner.querySelector('p').textContent = 'Loading feedback data...';
            alert('Error exporting to Excel. Please try again.');
        });
}

function exportToPDF() {
    var spinner = document.getElementById('fvLoadingSpinner');
    var form = document.getElementById('filterForm');
    spinner.classList.add('fv-loading');
    var formData = new FormData(form);
    formData.append('export_type', 'pdf');
    formData.append('page', 'all'); // Export all pages

    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }

    fetch(`{{ $fr['comments_export'] }}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.blob();
        })
        .then(blob => {
            // Create download link
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `faculty_feedback_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            loadingSpinner.style.display = 'none';
        })
        .catch(error => {
            console.error('Error exporting to PDF:', error);
            loadingSpinner.style.display = 'none';
            alert('Error exporting to PDF. Please try again.');
        });
}

function printReport() {
    var form = document.getElementById('filterForm');
    var formData = new FormData(form);
    var params = new URLSearchParams();
    for (var pair of formData.entries()) params.append(pair[0], pair[1]);
    params.append('page', 'all');

    const printUrl = `{{ $fr['comments_print'] }}?${params.toString()}`;
    window.open(printUrl, '_blank');
}
</script>
@endsection