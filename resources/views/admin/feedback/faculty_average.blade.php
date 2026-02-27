@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@section('setup_content')

    <style>
        /* ===============================
           GIGW + LBSNAA / SARGAM THEME
           =============================== */
        :root {
            --primary: #0b4f8a;   /* LBSNAA Blue */
            --secondary: #f4f6f9;
            --accent: #f2b705;    /* SARGAM accent */
            --border: #d0d7de;
            --text-dark: #1f2937;
        }

        body {
            background: var(--secondary);
            font-size: 14px; /* GIGW minimum readable */
            color: var(--text-dark);
        }

        /* FILTER PANEL */
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

        /* CONTENT CARD */
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

        /* PAGINATION */
        .pagination .page-link {
            color: var(--primary);
        }

        .pagination .active .page-link {
            background: var(--primary);
            border-color: var(--primary);
        }

        /* BUTTONS */
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

                    <fieldset class="mb-3">
                        <legend class="fs-6 fw-semibold">Course Status</legend>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="courseType" checked>
                            <label class="form-check-label">Archived Courses</label>
                        </div>
                    </fieldset>

                    <div class="mb-3">
                        <label class="form-label">Program Name</label>
                        <select class="form-select">
                            <option>Phase-I 2024</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Faculty Name</label>
                        <select class="form-select">
                            <option>Gaurab Kumar Sahu</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" />
                    </div>

                    <div class="mb-4">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" />
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary w-50">Apply</button>
                        <button class="btn btn-outline-secondary w-50">Reset</button>
                    </div>
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
                            @if ($currentProgram)
                                <div class="text-center mb-3">
                                    <h6 class="fw-semibold mb-0">{{ $currentProgram }}</h6>
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

                <div class="card-body">

                    <div class="text-center mb-3">
                        <h6 class="fw-semibold mb-0">Phase-I 2024</h6>
                    </div>

                    <!-- TABLE -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Faculty</th>
                                    <th>Topic</th>
                                    <th>Content (%)</th>
                                    <th>Presentation (%)</th>
                                    <th>Participants</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="faculty-name">Gaurab Kumar Sahu</td>
                                    <td>Oriya</td>
                                    <td class="percentage-good">96.67</td>
                                    <td class="percentage-good">94.24</td>
                                    <td class="text-center">6</td>
                                </tr>
                                <tr>
                                    <td class="faculty-name">Sudhir Krishna</td>
                                    <td>Role, Responsibilities & Importance of ULBs</td>
                                    <td class="percentage-average">77.39</td>
                                    <td class="percentage-average">76.00</td>
                                    <td class="text-center">114</td>
                                </tr>
                                <tr>
                                    <td class="faculty-name">A.S. Ramachandra</td>
                                    <td>Admission & Confession, Dying Declaration</td>
                                    <td>85.98</td>
                                    <td>85.00</td>
                                    <td class="text-center">157</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">Page 1 of 7</small>
                        <nav aria-label="Feedback pagination">
                            <ul class="pagination mb-0">
                                <li class="page-item disabled"><span class="page-link">«</span></li>
                                <li class="page-item active"><span class="page-link">1</span></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">»</a></li>
                            </ul>
                        </nav>
                    </div>

                </div>
            </div>
        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@endsection
