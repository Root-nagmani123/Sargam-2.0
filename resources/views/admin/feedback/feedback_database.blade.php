@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@section('setup_content')
<style>
:root {
    --lbsnaa-blue: #0b4f8a;
    --lbsnaa-light: #eef4fb;
    --sargam-accent: #f2b705;
    --border: #d0d7de;
    --text-dark: #1f2937;
}

body {
    background: #f4f6f9;
    font-size: 14px;
    /* GIGW */
    color: var(--text-dark);
}

.page-header {
    background: #fff;
    border-bottom: 2px solid var(--lbsnaa-blue);
}

.page-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--lbsnaa-blue);
}

.filter-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 8px;
}

.filter-card label {
    font-weight: 500;
}

.link-primary {
    color: var(--lbsnaa-blue);
    font-weight: 500;
}

.pagination .page-link {
    color: var(--lbsnaa-blue);
}

.pagination .active .page-link {
    background: var(--lbsnaa-blue);
    border-color: var(--lbsnaa-blue);
}

.btn-primary {
    background: var(--lbsnaa-blue);
    border-color: var(--lbsnaa-blue);
}

.btn-primary:hover {
    background: #083e6c;
}
</style>
</head>

<body>

    <!-- HEADER -->

    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 40px; height: 40px; background: var(--lbsnaa-blue); 
                       display: flex; align-items: center; justify-content: center; 
                       border-radius: 6px; color: white; font-weight: bold;">
                        S
                    </div>
                    <h4 class="page-title">Faculty Feedback Database</h4>
                </div>
                <div class="export-btn-group">
                    <button class="btn btn-outline-primary btn-sm" id="exportExcelBtn">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </button>
                    <button class="btn btn-outline-primary btn-sm" id="exportPdfBtn">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                    </button>
                </div>
            </div>
            <hr class="my-2">
            <div class="row g-3 align-items-end">
                <!-- Course Filter -->
                <div class="col-lg-3 col-md-4">
                    <label class="form-label">Program Name <span class="text-danger">*</span></label>
                    <select class="form-select" id="courseSelect" name="course_id">
                        <option value="">Select Program</option>
                        @if (isset($courses) && $courses->count() > 0)
                        @foreach ($courses as $course)
                        <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                        @endforeach
                        @else
                        <option value="" disabled>No courses available</option>
                        @endif
                    </select>
                </div>

                <!-- Search Parameter Filter -->
                <div class="col-lg-3 col-md-4">
                    <label class="form-label">Filter By</label>
                    <select class="form-select" id="searchParam" name="search_param">
                        <option value="all">All Records</option>
                        <option value="faculty">Faculty</option>
                        <option value="topic">Topic</option>
                    </select>
                </div>

                <!-- Faculty Filter Container (Hidden by default) -->
                <div class="col-lg-3 col-md-4 dynamic-filter-container d-none" id="facultyFilterContainer">
                    <label class="form-label">Select Faculty</label>
                    <select class="form-select" id="facultyFilter" name="faculty_id">
                        <option value="">All Faculties</option>
                        @if (isset($faculties) && $faculties->count() > 0)
                        @foreach ($faculties as $faculty)
                        <option value="{{ $faculty->pk }}">{{ $faculty->full_name }}</option>
                        @endforeach
                        @endif
                    </select>
                </div>

                <!-- Topic Filter Container (Hidden by default) -->
                <div class="col-lg-3 col-md-4 dynamic-filter-container d-none" id="topicFilterContainer">
                    <label class="form-label">Enter Topic</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="topicFilter" name="topic_value"
                            placeholder="Type topic name...">
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="clearTopicBtn">
                            <i class="material-icons menu-icon material-symbols-rounded">close</i>
                        </button>
                    </div>
                </div>

                <!-- Clear Filters Button -->
                <div class="col-lg-2 col-md-3">
                    <button type="button" class="btn btn-outline-secondary w-100" id="clearFiltersBtn">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </button>
                </div>
            </div>
            <hr class="my-2">
            <!-- TABLE CONTROLS -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <label class="me-2">Show</label>
                    <select class="form-select d-inline-block w-auto" id="perPageSelect">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <label class="ms-2">entries</label>
                </div>
                <div>
                    <label class="me-2">Search within table:</label>
                    <input type="text" class="form-control d-inline-block w-auto" id="tableSearch"
                        placeholder="Type to search...">
                </div>
            </div>

            <!-- TABLE -->
            <div class="table-responsive position-relative" id="tableContainer">
                <!-- Loading Overlay -->
                <div class="loading-overlay" id="loadingOverlay">
                    <div class="loading-spinner"></div>
                </div>
                <table class="table table-hover bg-white" id="feedbackTable">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Faculty Name</th>
                            <th>Course Name</th>
                            <th>Faculty Address</th>
                            <th>Topic</th>
                            <th>Content (%)</th>
                            <th>Presentation (%)</th>
                            <th>No. of Participants</th>
                            <th>Session Date</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody id="feedbackTableBody">
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="bi bi-database me-2"></i>
                                Select a program to load feedback data
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="d-flex justify-content-between align-items-center mt-4" id="paginationSection"
                style="display: none;">
                <small class="text-muted" id="paginationInfo">Showing 0 to 0 of 0 entries</small>
                <nav aria-label="Feedback pagination">
                    <ul class="pagination mb-0" id="paginationLinks">
                        <!-- Dynamic pagination links will be inserted here -->
                    </ul>
                </nav>
            </div>
        </div>
        <hr class="my-2">

        <!-- FILTERS -->
        <div class="card filter-card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">Program Name <span class="text-danger">*</span></label>
                        <select class="form-select">
                            <option>ITP-127 2025 May</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">Search Parameter <span class="text-danger">*</span></label>
                        <select class="form-select">
                            <option>All</option>
                            <option>Faculty</option>
                            <option>Topic</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <button class="btn btn-primary w-100">View</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLE CONTROLS -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <label class="me-2">Show</label>
                <select class="form-select d-inline-block w-auto">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                </select>
                <label class="ms-2">entries</label>
            </div>
            <div>
                <label class="me-2">Search within table:</label>
                <input type="text" class="form-control d-inline-block w-auto">
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-responsive">
            <table class="table bg-white">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Faculty Name</th>
                        <th>Course Name</th>
                        <th>Faculty Address</th>
                        <th>Topic</th>
                        <th>Content (%)</th>
                        <th>Presentation (%)</th>
                        <th>No. of Participants</th>
                        <th>Session Date</th>
                        <th>Comments</th>
                        <th>Document</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center">1</td>
                        <td><a href="#" class="link-primary">Shweta Teotia</a></td>
                        <td>ITP-127 2025 May</td>
                        <td>Officer of Director(Admin), Gujarat<br>shweta.teotia@ias.nic.in</td>
                        <td>Renewable Energy for Powering Viksit Bharat</td>
                        <td class="text-center">97.14</td>
                        <td class="text-center">98.02</td>
                        <td class="text-center">80</td>
                        <td class="text-center">16-05-2025</td>
                        <td class="text-center"><a href="#" class="link-primary">View</a></td>
                        <td class="text-center"><a href="#" class="link-primary">View</a></td>
                    </tr>
                    <tr>
                        <td class="text-center">2</td>
                        <td><a href="#" class="link-primary">Vijay Suryawanshi</a></td>
                        <td>ITP-127 2025 May</td>
                        <td>padivcomkon@gmail.com</td>
                        <td>Role of Civic Authorities in Urban Planning</td>
                        <td class="text-center">92.89</td>
                        <td class="text-center">91.78</td>
                        <td class="text-center">80</td>
                        <td class="text-center">16-05-2025</td>
                        <td class="text-center"><a href="#" class="link-primary">View</a></td>
                        <td class="text-center"><a href="#" class="link-primary">View</a></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">Showing 1 to 10 of 25 entries</small>
            <nav aria-label="Feedback pagination">
                <ul class="pagination mb-0">
                    <li class="page-item disabled"><span class="page-link">Previous</span></li>
                    <li class="page-item active"><span class="page-link">1</span></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                </ul>
            </nav>
        </div>

    </div>
    @endsection
