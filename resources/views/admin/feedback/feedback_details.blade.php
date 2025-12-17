@extends('admin.layouts.master')

@section('title', 'Faculty Feedback with Comments All Details - Sargam | Lal Bahadur')

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
        <x-breadcrum title="Faculty Feedback with Comments All Details"></x-breadcrum>
        <div class="card filter-card">
            <div class="card-header">Options</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3">
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
                    </div>
                    <div class="col-lg-3">
                        <div class="mb-3">
                            <label for="program" class="form-label">Program Name</label>
                            <select id="program" class="form-select">
                                <option>ITP-126 2025 MAR</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="mb-3">
                            <label for="fromDate" class="form-label">From Date</label>
                            <input type="date" id="fromDate" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="mb-3">
                            <label for="toDate" class="form-label">To Date</label>
                            <input type="date" id="toDate" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-3">
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
                    </div>
                    <div class="col-lg-3">
                        <div class="mb-4">
                            <label for="facultyName" class="form-label">Faculty Name</label>
                            <input type="text" id="facultyName" class="form-control" />
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary w-50">Apply</button>
                    <button class="btn btn-outline-secondary w-50">Reset</button>
                </div>
            </div>
        </div>
        <div class="row g-3">

            <!-- MAIN CONTENT -->
            <main class="col-lg-12 col-md-12">
                <div class="card content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="page-title">Faculty Feedback with Comments All Details</span>
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
                                        <th scope="col">OT Name</th>
                                         <th scope="col">OT Code</th>
                                          <th scope="col">Content</th>
                                        <th scope="col">Presentation</th>
                                        <th scope="col">Remarks</th>
                                        
                                    </tr>
                                </thead>
                                <tbody class="align-middle text-dark">
                                    <tr>
                                        <td>Vinita Gupta</td>
                                        <td>A01</td>
                                        <td>3</td>
                                        <td>3</td>
                                        <td>Not much interested</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @endsection