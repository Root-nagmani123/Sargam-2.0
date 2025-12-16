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
    </style>
</head>
<body>

<div class="container-fluid py-3">
     <x-breadcrum title="Faculty Average"></x-breadcrum>
    <div class="row g-3">

        <!-- LEFT FILTER PANEL -->
        <aside class="col-lg-3 col-md-4">
            <div class="card filter-card">
                <div class="card-header">Options</div>
                <div class="card-body">

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
                    <span>Feedback With Average</span>
                    <small class="text-muted">Data refreshed: 16-Dec-2025 12:58</small>
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