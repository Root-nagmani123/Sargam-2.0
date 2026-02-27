@extends('admin.layouts.master')

@section('title', 'Faculty Feedback with Comments Admin View - Sargam | Lal Bahadur')

@section('setup_content')

    <!-- GIGW + LBSNAA / SARGAM THEME -->
    <style>
        :root {
            /* LBSNAA / SARGAM inspired palette */
            --primary: #af2910;
            /* Deep institutional blue */
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

        /* Accessibility */
        label {
            font-weight: 500;
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

                            <!-- META INFO -->
                            <div class="text-center mb-4">
                                <p class="mb-1"><strong>Course:</strong> ITP-126 2025 MAR</p>
                                <p class="mb-1"><strong>Faculty:</strong> Prof. Sunita Rani</p>
                                <p class="mb-1"><strong>Topic:</strong> Leadership Module</p>
                                <p class="mb-0"><strong>Lecture Date:</strong> 17-Feb-2025 (14:30 – 16:55)</p>
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
