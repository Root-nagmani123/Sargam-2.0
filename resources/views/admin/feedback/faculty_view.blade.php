@extends('admin.layouts.master')

@section('title', 'Faculty Feedback with Comments Admin View - Sargam | Lal Bahadur')

@section('setup_content')

    <!-- GIGW + LBSNAA / SARGAM THEME -->
    <style>
        :root {
            --primary: #004a93;
            --secondary: #f4f6f9;
            /* Neutral background */
            --accent: #f2b705;
            /* Sargam accent gold */
            --success: #198754;
            --border: #d0d7de;
            --text-dark: #1f2937;
        }

        body {
            background: var(--secondary);
            color: var(--text-dark);
            font-size: 14px;
            /* GIGW: readable default */
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
        }

        /* Filter panel */
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

        /* Content card */
        .content-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
        }

        .content-card .card-header {
            background: #eef4fb;
            font-weight: 600;
        }

        /* Remarks section */
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
            color: #004a93 !important;
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

        /* Export button styles */
        .export-btn-group {
            display: flex;
            gap: 8px;
        }

        .export-btn {
            padding: 6px 12px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .export-btn i {
            font-size: 0.875rem;
        }

        /* Responsive styles - tablet and below (desktop unchanged) */
        @media (max-width: 991.98px) {
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .content-card .card-header {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .content-card .card-header .page-title {
                width: 100%;
                font-size: 1.1rem;
            }

            .content-card .card-header .d-flex.align-items-center {
                flex-wrap: wrap;
                width: 100%;
                gap: 0.5rem;
            }

            .content-card .card-header .btn-group {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .content-card .card-header small.text-muted {
                width: 100%;
                margin-left: 0 !important;
                font-size: 0.75rem;
            }

            .feedback-section .text-center.mb-4 {
                text-align: start !important;
            }

            .feedback-section .text-center.mb-4 p {
                font-size: 0.9rem;
                word-break: break-word;
                display: flex;
                align-items: flex-start;
                flex-wrap: wrap;
                gap: 0 0.25rem;
            }

            .feedback-section .text-center.mb-4 p strong {
                min-width: 7rem;
                flex-shrink: 0;
            }

            .table-responsive {
                margin-left: -0.5rem;
                margin-right: -0.5rem;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .table-responsive .table {
                font-size: 0.875rem;
            }

            .table-responsive .table th,
            .table-responsive .table td {
                padding: 0.5rem 0.4rem;
                white-space: nowrap;
            }

            .remarks-list {
                padding: 0.75rem;
                font-size: 0.875rem;
            }

            .pagination-info {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 767.98px) {
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            .container-fluid .row.g-3 {
                margin-left: -0.25rem;
                margin-right: -0.25rem;
            }

            .content-card .card-header .page-title {
                font-size: 1rem;
            }

            .content-card .card-header .btn-group .btn {
                font-size: 0.8rem;
                padding: 0.35rem 0.5rem;
            }

            .content-card .card-header .btn-group .btn i {
                margin-right: 0.25rem !important;
            }

            .feedback-section .text-center.mb-4 p {
                font-size: 0.85rem;
            }

            .feedback-section .text-center.mb-4 {
                text-align: start !important;
            }

            .feedback-section .text-center.mb-4 p {
                display: flex;
                align-items: flex-start;
                flex-wrap: wrap;
                gap: 0 0.25rem;
            }

            .feedback-section .text-center.mb-4 p strong {
                display: inline-block;
                min-width: 7rem;
                flex-shrink: 0;
            }

            .table-responsive .table {
                font-size: 0.8rem;
            }

            .table-responsive .table th,
            .table-responsive .table td {
                padding: 0.4rem 0.35rem;
            }

            .d-flex.justify-content-between.align-items-center {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .d-flex.justify-content-between.align-items-center > div:first-child {
                width: 100%;
                order: 1;
            }

            .d-flex.justify-content-between.align-items-center > div:last-child {
                width: 100%;
                justify-content: center;
                order: 2;
            }

            .d-flex.justify-content-between.align-items-center .btn-sm {
                font-size: 0.8rem;
            }

            .filter-card .card-body .d-flex.gap-2 {
                flex-direction: column;
            }

            .filter-card .card-body .d-flex.gap-2 .btn {
                width: 100% !important;
            }
        }

        @media (max-width: 575.98px) {
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            .content-card .card-header .btn-group {
                flex-direction: column;
                width: 100%;
                gap: 0.5rem;
            }

            .content-card .card-header .btn-group .btn {
                width: 100%;
                justify-content: center;
            }

            .page-title {
                font-size: 0.95rem;
            }

            .feedback-section .text-center.mb-4 p {
                font-size: 0.8rem;
            }

            .remarks-title {
                font-size: 0.9rem;
                padding: 0.4rem 0.5rem;
            }

            .remarks-list {
                padding: 0.5rem;
                font-size: 0.8rem;
            }

            .suggestions-list {
                max-height: 150px;
            }
        }
    </style>
    </head>

    <body>

    <div class="container-fluid py-3">
        <x-breadcrum title="Average Rating - Course / Topic wise"></x-breadcrum>
        <div class="row g-3">

                <!-- LEFT FILTER PANEL -->
                <aside class="col-lg-3 col-md-4">
                    <div class="card filter-card">
                        <div class="card-header">Options</div>
                        <div class="card-body">

                            <fieldset class="mb-3">
                                <legend class="fs-6 fw-semibold">Course Status</legend>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="courseType" id="current">
                                    <label class="form-check-label" for="current">Current Courses</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="courseType" id="archived" checked>
                                    <label class="form-check-label" for="archived">Archived Courses</label>
                                </div>
                            </fieldset>

                            <div class="mb-3">
                                <label for="program" class="form-label">Program Name</label>
                                <select id="program" class="form-select">
                                    <option>ITP-126 2025 MAR</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="fromDate" class="form-label">From Date</label>
                                <input type="date" id="fromDate" class="form-control" />
                            </div>

                            <div class="mb-3">
                                <label for="toDate" class="form-label">To Date</label>
                                <input type="date" id="toDate" class="form-control" />
                            </div>

                            <fieldset class="mb-3">
                                <legend class="fs-6 fw-semibold">Faculty Type</legend>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="guest">
                                    <label class="form-check-label" for="guest">Guest</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="regular">
                                    <label class="form-check-label" for="regular">Regular</label>
                                </div>
                            </fieldset>

                            <div class="mb-4">
                                <label for="facultyName" class="form-label">Faculty Name</label>
                                <input type="text" id="facultyName" class="form-control" />
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-primary w-50">Apply</button>
                                <button class="btn btn-outline-secondary w-50">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- MAIN CONTENT -->
            <main class="col-lg-9 col-md-8">
                <div class="card content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="page-title">Average Rating - Course / Topic wise</span>
                        <div class="d-flex align-items-center">
                            <!-- Export Buttons -->
                            <div class="btn-group ms-2" role="group">
                                <button type="button" class="btn btn-sm btn-success" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel me-1"></i> Export Excel
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="exportToPDF()">
                                    <i class="fas fa-file-pdf me-1"></i> Export PDF
                                </button>
                            </div>
                            <small class="text-muted ms-3">Data refreshed:
                                {{ $refreshTime ?? now()->format('d-M-Y H:i') }}</small>
                        </div>
                    </div>
                </aside>

                <!-- MAIN CONTENT -->
                <main class="col-lg-9 col-md-8">
                    <div class="card content-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="page-title">Faculty Feedback with Comments (Admin View)</span>
                            <small class="text-muted">Data refreshed: 16-Dec-2025 12:54</small>
                        </div>

                        <div class="card-body">

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
                                                        <th class="rating-header" style="color:#004a93 !important;">
                                                            Excellent</th>
                                                        <td>{{ $data['content_counts']['5'] ?? 0 }}</td>
                                                        <td>{{ $data['presentation_counts']['5'] ?? 0 }}</td>
                                                    </tr>
                                                    <!-- Very Good -->
                                                    <tr>
                                                        <th class="rating-header" style="color:#004a93 !important;">Very
                                                            Good</th>
                                                        <td>{{ $data['content_counts']['4'] ?? 0 }}</td>
                                                        <td>{{ $data['presentation_counts']['4'] ?? 0 }}</td>
                                                    </tr>
                                                    <!-- Good -->
                                                    <tr>
                                                        <th class="rating-header" style="color:#004a93 !important;">Good
                                                        </th>
                                                        <td>{{ $data['content_counts']['3'] ?? 0 }}</td>
                                                        <td>{{ $data['presentation_counts']['3'] ?? 0 }}</td>
                                                    </tr>
                                                    <!-- Average -->
                                                    <tr>
                                                        <th class="rating-header" style="color:#004a93 !important;">
                                                            Average</th>
                                                        <td>{{ $data['content_counts']['2'] ?? 0 }}</td>
                                                        <td>{{ $data['presentation_counts']['2'] ?? 0 }}</td>
                                                    </tr>
                                                    <!-- Below Average -->
                                                    <tr>
                                                        <th class="rating-header" style="color:#004a93 !important;">Below
                                                            Average</th>
                                                        <td>{{ $data['content_counts']['1'] ?? 0 }}</td>
                                                        <td>{{ $data['presentation_counts']['1'] ?? 0 }}</td>
                                                    </tr>
                                                    <!-- Percentage -->
                                                    <tr class="fw-semibold">
                                                        <th class="rating-header" style="color:#004a93 !important;">
                                                            Percentage</th>
                                                        <td class="percentage-cell">
                                                            {{ number_format($data['content_percentage'] ?? 0, 2) }}%</td>
                                                        <td class="percentage-cell">
                                                            {{ number_format($data['presentation_percentage'] ?? 0, 2) }}%
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            {{-- <small>* is defined as Total Student Count:
                                                {{ $data['participants'] ?? 0 }}</small> --}}
                                        </div>

                            <!-- FEEDBACK TABLE -->
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th scope="col">Rating</th>
                                            <th scope="col">Content <span class="text-dark">*</span></th>
                                            <th scope="col">Presentation <span class="text-dark">*</span></th>
                                        </tr>
                                    </thead>
                                    <tbody class="align-middle text-dark">
                                        <tr>
                                            <th style="color: #af2910 !important;font-weiight: 600;">Excellent</th>
                                            <td>16</td>
                                            <td>17</td>
                                        </tr>
                                        <tr>
                                            <th style="color: #af2910 !important;font-weiight: 600;">Very Good</th>
                                            <td>9</td>
                                            <td>8</td>
                                        </tr>
                                        <tr>
                                            <th style="color: #af2910 !important;font-weiight: 600;">Good</th>
                                            <td>1</td>
                                            <td>1</td>
                                        </tr>
                                        <tr>
                                            <th style="color: #af2910 !important;font-weiight: 600;">Average</th>
                                            <td>0</td>
                                            <td>0</td>
                                        </tr>
                                        <tr>
                                            <th style="color: #af2910 !important;font-weiight: 600;">Below Average</th>
                                            <td>0</td>
                                            <td>0</td>
                                        </tr>
                                        <tr class="fw-semibold">
                                            <th style="color: #af2910 !important;font-weiight: 600;">Percentage</th>
                                            <td>91.03%</td>
                                            <td>92.41%</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <small>* is defined as Total Student Count</small>
                            </div>

                            <!-- REMARKS -->
                            <div class="mb-2">
                                <div class="remarks-title">Remarks</div>
                                <ol class="remarks-list py-2">
                                    <li>Communicative and thought-provoking attitude.</li>
                                    <li>Faculty was non-judgmental and helpful.</li>
                                    <li>Very good; scope for further improvement in content.</li>
                                    <li>Excellent practical involvement.</li>
                                    <li>Got clarity on listening approach and its importance.</li>
                                    <li>The practical session was good.</li>
                                    <li>The module was useful.</li>
                                </ol>
                            </div>

                        </div>
                    </div>
                </main>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @endsection
