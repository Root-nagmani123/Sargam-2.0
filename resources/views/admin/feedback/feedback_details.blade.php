@extends('admin.layouts.master')

@section('title', 'Faculty Feedback with Comments All Details - Sargam | Lal Bahadur')

@section('setup_content')
    <style>
        :root {
            --primary: #af2910;
            --primary-rgb: 175, 41, 16;
            --secondary: #f4f6f9;
            --accent: #f2b705;
            --success: #198754;
            --border: #e2e8f0;
            --text-dark: #1e293b;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --radius: 0.75rem;
            --radius-lg: 1rem;
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
            border: 0;
            border-radius: var(--radius-lg);
            background: #fff;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .filter-card .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, #8a1f0d 100%);
            color: #fff;
            font-weight: 600;
            padding: 1rem 1.25rem;
            border: 0;
        }

        .content-card {
            border: 0;
            border-radius: var(--radius-lg);
            background: #fff;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .content-card .card-header {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            font-weight: 600;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }

        .rating-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 28px;
            padding: 0 6px;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .rating-5 { background: #198754; color: #fff; }
        .rating-4 { background: #20c997; color: #fff; }
        .rating-3 { background: #ffc107; color: #000; }
        .rating-2 { background: #fd7e14; color: #fff; }
        .rating-1 { background: #dc3545; color: #fff; }

        .faculty-type-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
            background: #e2e8f0;
            color: #475569;
            font-weight: 500;
        }

        .session-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-weight: 500;
        }

        .suggestions-container { position: relative; }

        .suggestions-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 0.25rem;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            max-height: 220px;
            overflow-y: auto;
            z-index: 1050;
            display: none;
            box-shadow: var(--shadow-lg);
        }

        .suggestion-item {
            padding: 0.6rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s ease;
        }

        .suggestion-item:hover { background: #f8fafc; }
        .suggestion-item:last-child { border-bottom: none; }

        #loadingSpinner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            background: rgba(255, 255, 255, 0.92);
            padding: 1.5rem 2rem;
            backdrop-filter: blur(6px);
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }

        #loadingSpinner.loading-hidden {
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* Pagination - modern card-style wrapper */
        .pagination-wrapper {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem 1.25rem;
            margin-top: 1.5rem;
        }

        .pagination-info {
            font-size: 0.8125rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }

        .pagination-info strong { color: var(--text-dark); }

        .pagination {
            gap: 0.35rem;
            margin: 0;
        }

        .pagination .page-item .page-link {
            min-width: 2.5rem;
            height: 2.5rem;
            padding: 0 0.65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-weight: 500;
            transition: color 0.15s ease, background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .pagination .page-item:not(.disabled) .page-link:hover {
            color: #fff;
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 2px 4px rgba(175, 41, 16, 0.25);
        }

        .pagination .page-item.active .page-link {
            color: #fff;
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 2px 4px rgba(175, 41, 16, 0.3);
        }

        .pagination .page-item.disabled .page-link {
            color: #94a3b8;
            background: #f1f5f9;
            border-color: var(--border);
            cursor: not-allowed;
            pointer-events: none;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }

        .nav-tabs .nav-link {
            color: var(--text-dark);
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            padding: 12px 24px;
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent;
            background-color: #f8f9fa;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            background-color: transparent;
            border-bottom-color: var(--primary);
        }

        /* Session header card */
        .session-header {
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            box-shadow: var(--shadow-sm);
        }

        .form-control:focus, .form-select:focus {
            border-color: rgba(var(--primary-rgb), 0.5);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.15);
        }

        .btn-group .btn { border-radius: 0.5rem; }

        /* Responsive - tablet and below (desktop unchanged) */
        @media (max-width: 991px) {
            .filter-card .card-body,
            .content-card .card-body {
                padding: 1rem !important;
            }
            .session-header {
                padding: 1rem !important;
            }
            .session-header .col-md-4 {
                margin-bottom: 0.75rem;
            }
            .session-header .col-md-4:last-child {
                margin-bottom: 0;
            }
            .pagination .page-item .page-link {
                min-width: 2.25rem;
                height: 2.25rem;
                padding: 0 0.5rem;
                font-size: 0.8125rem;
            }
        }

        @media (max-width: 767px) {
            .container-fluid {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }
            .filter-card .card-body,
            .content-card .card-body {
                padding: 0.75rem !important;
            }
            .filter-card .card-header,
            .content-card .card-header {
                padding: 0.75rem 1rem !important;
                font-size: 0.9375rem;
            }
            .content-card .card-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 0.5rem;
            }
            .content-card .card-header small {
                font-size: 0.75rem;
            }
            .session-header {
                padding: 0.75rem !important;
            }
            .session-header h6 {
                font-size: 0.9375rem;
            }
            .feedback-actions {
                flex-direction: column;
                align-items: stretch !important;
            }
            .feedback-actions .btn-group {
                flex-direction: column;
                width: 100%;
            }
            .feedback-actions .btn-group .btn {
                width: 100%;
                justify-content: center;
            }
            .feedback-actions .btn-outline-secondary,
            .feedback-actions .btn-warning {
                width: 100%;
            }
            .table-feedback {
                font-size: 0.8125rem;
            }
            .table-feedback th,
            .table-feedback td {
                padding: 0.5rem 0.4rem;
            }
            .rating-badge {
                min-width: 24px;
                height: 24px;
                font-size: 0.75rem;
            }
            .pagination-wrapper {
                padding: 0.75rem 1rem;
                margin-top: 1rem;
            }
            .pagination-info {
                flex-direction: column;
                gap: 0.5rem !important;
                font-size: 0.75rem;
            }
            .pagination .page-item .page-link {
                min-width: 2rem;
                height: 2rem;
                padding: 0 0.4rem;
                font-size: 0.75rem;
            }
            .empty-state {
                padding: 2rem 1rem;
            }
            .empty-state i {
                font-size: 2.5rem;
            }
            .empty-state h5 {
                font-size: 1rem;
            }
            .suggestions-list {
                max-height: 180px;
                max-width: 100%;
                left: 0;
                right: 0;
            }
        }

        @media (max-width: 575px) {
            .page-title {
                font-size: 1rem;
            }
            .filter-card .card-body {
                padding: 0.5rem !important;
            }
            fieldset legend,
            .form-label {
                font-size: 0.8125rem;
            }
            .form-control,
            .form-select {
                font-size: 0.875rem;
            }
            .session-header .d-flex.align-items-center.gap-2 {
                flex-wrap: wrap;
            }
            .table-feedback th:nth-child(n+4),
            .table-feedback td:nth-child(n+4) {
                font-size: 0.75rem;
            }
            .pagination .page-item:not(:first-child):not(:last-child) .page-link {
                min-width: 1.75rem;
                height: 1.75rem;
                padding: 0 0.35rem;
            }
            .pagination .page-item .page-link i {
                font-size: 0.7rem;
            }
        }
    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="container-fluid py-3">
        <x-breadcrum title="Faculty Feedback with Comments All Details"></x-breadcrum>

        <!-- Loading Spinner (hidden by default; JS toggles class loading-hidden) -->
        <div id="loadingSpinner" class="loading-hidden d-flex flex-column align-items-center justify-content-center gap-3">
            <div class="spinner-border text-primary" style="width: 2.5rem; height: 2.5rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mb-0 small text-body-secondary">Loading feedback data...</p>
        </div>

        <!-- FILTER PANEL -->
        <div class="card filter-card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
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
                        <div class="col-lg-2 col-md-6 suggestions-container">
                            <label for="facultySearch" class="form-label fw-medium">Faculty Name</label>
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

                <div class="d-flex flex-wrap align-items-center gap-2 mt-4 pt-3 border-top feedback-actions">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </button>
                        <button type="button" class="btn btn-danger" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf me-1"></i> Export PDF
                        </button>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" id="resetButton">
                        <i class="fas fa-redo me-1"></i> Reset Filters
                    </button>
                    <a href="{{ route('admin.feedback.pending.students') }}" class="btn btn-warning">
                        <i class="fas fa-user-clock me-1"></i> Pending Feedback (Students)
                    </a>
                </div>
            </div>

            <!-- Content card -->
            <div class="card content-card mt-0 border-0 rounded-0 shadow-none" style="box-shadow: none;">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                    <span class="page-title mb-0 text-primary">Faculty Feedback with Comments All Details</span>
                    <small class="text-body-secondary"><i class="fas fa-sync-alt me-1"></i> {{ $refreshTime ?? now()->format('d-M-Y H:i') }}</small>
                </div>

                <div class="card-body p-4">
                    <div id="contentContainer">
                    @if ($groupedData->isEmpty())
                        <div class="empty-state rounded-3 bg-light py-5">
                            <i class="fas fa-clipboard-list d-block"></i>
                            <h5 class="fw-semibold mt-2">No feedback data found</h5>
                            <p class="text-body-secondary mb-0">Try adjusting your filters to see results.</p>
                        </div>
                    @else
                        @foreach ($groupedData as $groupKey => $group)
                            @php
                                [$programName, $facultyName, $topicName] = explode('|', $groupKey);
                                $firstRecord = $group->first();
                            @endphp

                            <!-- Session Header -->
                            <div class="session-header mb-4 p-4">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="fas fa-book-open text-primary"></i>
                                            <span class="fw-semibold text-body-secondary small">Course</span>
                                        </div>
                                        <h6 class="mb-0 fw-semibold">{{ $programName }}</h6>
                                        <span class="session-badge mt-1">{{ $firstRecord['course_status'] ?? 'Unknown' }}</span>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="fas fa-chalkboard-teacher text-primary"></i>
                                            <span class="fw-semibold text-body-secondary small">Faculty</span>
                                        </div>
                                        <h6 class="mb-0 fw-semibold">{{ $facultyName }}</h6>
                                        <span class="faculty-type-badge mt-1">{{ $firstRecord['faculty_type'] ?? '' }}</span>
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
                                <table class="table table-feedback align-middle mb-0">
                                    <thead>
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
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item['ot_name'] }}</td>
                                                <td>{{ $item['ot_code'] }}</td>
                                                <td>
                                                    <span class="rating-badge rating-{{ $item['content'] }}">
                                                        {{ $item['content'] }}
                                                    </span>
                                                </td>
                                                <td>
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
                            <div class="pagination-wrapper">
                                <div class="pagination-info text-center d-flex flex-wrap justify-content-center align-items-center gap-2 gap-md-3">
                                    <span>
                                        <strong>Page {{ $currentPage }}</strong> of {{ $totalPages }}
                                    </span>
                                    <span class="text-body-secondary">·</span>
                                    <span>
                                        Showing {{ ($currentPage - 1) * 10 + 1 }}–{{ min($currentPage * 10, $totalRecords) }} of <strong>{{ $totalRecords }}</strong> records
                                    </span>
                                </div>
                                <nav aria-label="Feedback pagination">
                                    <ul class="pagination justify-content-center flex-wrap mb-0">
                                        <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                            <a class="page-link" href="javascript:void(0)" onclick="goToPage(1)" aria-label="First page" title="First">
                                                <i class="fas fa-angle-double-left" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                        <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                            <a class="page-link" href="javascript:void(0)" onclick="goToPage({{ $currentPage - 1 }})" aria-label="Previous page" title="Previous">
                                                <i class="fas fa-angle-left" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                        @php
                                            $startPage = max(1, $currentPage - 2);
                                            $endPage = min($totalPages, $currentPage + 2);
                                        @endphp
                                        @for ($i = $startPage; $i <= $endPage; $i++)
                                            <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                                                <a class="page-link" href="javascript:void(0)" onclick="goToPage({{ $i }})" aria-label="Page {{ $i }}" aria-current="{{ $i == $currentPage ? 'page' : 'false' }}">{{ $i }}</a>
                                            </li>
                                        @endfor
                                        <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
                                            <a class="page-link" href="javascript:void(0)" onclick="goToPage({{ $currentPage + 1 }})" aria-label="Next page" title="Next">
                                                <i class="fas fa-angle-right" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                        <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
                                            <a class="page-link" href="javascript:void(0)" onclick="goToPage({{ $totalPages }})" aria-label="Last page" title="Last">
                                                <i class="fas fa-angle-double-right" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        @endif
                    @endif
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

                // Show/hide loader using class so it always hides (Bootstrap d-flex uses !important)
                function showLoader() {
                    if (loadingSpinner) loadingSpinner.classList.remove('loading-hidden');
                    if (contentContainer) contentContainer.style.opacity = '0.5';
                }
                function hideLoader() {
                    if (loadingSpinner) loadingSpinner.classList.add('loading-hidden');
                    if (contentContainer) contentContainer.style.opacity = '1';
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

                        Object.entries(data.groupedData).forEach(([groupKey, group]) => {
                            const [programName, facultyName, topicName] = groupKey.split('|');
                            const firstRecord = group[0];

                            html += `
                    <div class="session-header mb-4 p-4">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fas fa-book-open text-primary"></i>
                                    <span class="fw-semibold text-body-secondary small">Course</span>
                                </div>
                                <h6 class="mb-0 fw-semibold">${programName}</h6>
                                <span class="session-badge mt-1">${firstRecord.course_status || 'Unknown'}</span>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fas fa-chalkboard-teacher text-primary"></i>
                                    <span class="fw-semibold text-body-secondary small">Faculty</span>
                                </div>
                                <h6 class="mb-0 fw-semibold">${facultyName}</h6>
                                <span class="faculty-type-badge mt-1">${firstRecord.faculty_type || ''}</span>
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
                        <table class="table table-feedback align-middle mb-0">
                            <thead>
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
                                        <td class="text-center">
                                            <span class="rating-badge rating-${item.content}">${item.content}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="rating-badge rating-${item.presentation}">${item.presentation}</span>
                                        </td>
                                        <td>
                                            ${item.remark ? `<div class="remark-text">${item.remark}</div>` : `<span class="text-muted fst-italic">No remarks</span>`}
                                        </td>
                                        <td>
                                            <small class="text-body-secondary">${item.feedback_date || ''}</small>
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
                        const refreshElement = document.querySelector('.content-card .card-header small');
                        if (refreshElement && data.refreshTime) {
                            refreshElement.innerHTML = `<i class="fas fa-sync-alt me-1"></i> ${data.refreshTime}`;
                        }
                    } else {
                        contentContainer.innerHTML = `
                <div class="empty-state rounded-3 bg-light py-5">
                    <i class="fas fa-clipboard-list d-block"></i>
                    <h5 class="fw-semibold mt-2">No feedback data found</h5>
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
            <div class="pagination-wrapper">
                <div class="pagination-info text-center d-flex flex-wrap justify-content-center align-items-center gap-2 gap-md-3">
                    <span><strong>Page ${currentPage}</strong> of ${totalPages}</span>
                    <span class="text-body-secondary">·</span>
                    <span>Showing ${startRec}–${endRec} of <strong>${totalRecords}</strong> records</span>
                </div>
                <nav aria-label="Feedback pagination">
                    <ul class="pagination justify-content-center flex-wrap mb-0">
                        <li class="page-item ${currentPage == 1 ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="goToPage(1)" aria-label="First page" title="First">
                                <i class="fas fa-angle-double-left" aria-hidden="true"></i>
                            </a>
                        </li>
                        <li class="page-item ${currentPage == 1 ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${currentPage - 1})" aria-label="Previous page" title="Previous">
                                <i class="fas fa-angle-left" aria-hidden="true"></i>
                            </a>
                        </li>
        `;

                    const startPage = Math.max(1, currentPage - 2);
                    const endPage = Math.min(totalPages, currentPage + 2);

                    for (let i = startPage; i <= endPage; i++) {
                        const ariaCurrent = i === currentPage ? ' aria-current="page"' : ' aria-current="false"';
                        pagination += `
                        <li class="page-item ${i === currentPage ? 'active' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${i})" aria-label="Page ${i}"${ariaCurrent}>${i}</a>
                        </li>
        `;
                    }

                    pagination += `
                        <li class="page-item ${currentPage == totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${currentPage + 1})" aria-label="Next page" title="Next">
                                <i class="fas fa-angle-right" aria-hidden="true"></i>
                            </a>
                        </li>
                        <li class="page-item ${currentPage == totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${totalPages})" aria-label="Last page" title="Last">
                                <i class="fas fa-angle-double-right" aria-hidden="true"></i>
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

                // Faculty search with suggestions (only if faculty search exists)
                if (facultySearch) {
                    facultySearch.addEventListener('focus', fetchFacultySuggestions);
                    facultySearch.addEventListener('input', fetchFacultySuggestions);
                }

                // Hide suggestions when clicking outside
                document.addEventListener('click', function(event) {
                    if (suggestionsList && facultySearch && !facultySearch.contains(event.target) && !suggestionsList.contains(event.target)) {
                        suggestionsList.style.display = 'none';
                    }
                });

                // Suggestion click
                if (suggestionsList) {
                    suggestionsList.addEventListener('click', function(event) {
                        if (event.target.classList.contains('suggestion-item') && facultySearch) {
                            facultySearch.value = event.target.getAttribute('data-value') || '';
                            suggestionsList.style.display = 'none';
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
                        if (suggestionsList) suggestionsList.style.display = 'none';
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

                setTimeout(() => {
                    if (loadingSpinner) loadingSpinner.classList.add('loading-hidden');
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

                setTimeout(() => {
                    if (loadingSpinner) loadingSpinner.classList.add('loading-hidden');
                }, 2000);
            }
        </script>
    @endsection
