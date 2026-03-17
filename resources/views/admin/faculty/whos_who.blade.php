@extends('admin.layouts.master')

@section('title', "Who's Who - Sargam | Lal Bahadur Shastri National Academy of Administration")

@section('setup_content')

<div class="container-fluid py-3 py-md-4 px-3 px-md-4">
    <x-breadcrum title="Who's Who"></x-breadcrum>

    <!-- Filter Section (sticky on scroll) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden" id="filterBarSticky">
        <div class="card-header bg-body-tertiary border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h2 class="h6 mb-0 fw-semibold text-body-emphasis d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill text-primary fs-5"></i>
                    Find Participants
                </h2>
                <p class="small text-body-secondary mb-0">Use smart filters, sorting and paging to quickly locate any participant.</p>
            </div>
            <button type="button" class="btn btn-outline-secondary  d-inline-flex align-items-center gap-1" id="resetFilters">
                <i class="bi bi-arrow-clockwise"></i>
                <span>Reset all</span>
            </button>
        </div>
        <div class="card-body p-4 pt-3">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-6 g-3 align-items-end">
                <div class="col">
                    <label for="courseTypeFilter" class="form-label fw-semibold small text-body-secondary text-uppercase">Course Status</label>
                    <select class="form-select  rounded-1" id="courseTypeFilter">
                        <option value="active" selected>Active</option>
                        <option value="archive">Archive</option>
                    </select>
                </div>
                <div class="col">
                    <label for="nameFilter" class="form-label fw-semibold small text-body-secondary text-uppercase">Name</label>
                    <input type="text" class="form-control rounded-1" id="nameFilter" placeholder="Search by name">
                </div>
                <div class="col">
                    <label for="courseFilter" class="form-label fw-semibold small text-body-secondary text-uppercase">Course Name</label>
                    <select class="form-select  rounded-1" id="courseFilter" data-loaded-type="active">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col">
                    <label for="cadreFilter" class="form-label fw-semibold small text-body-secondary text-uppercase">Cadre</label>
                    <select class="form-select  rounded-1" id="cadreFilter">
                        <option value="">All Cadres</option>
                    </select>
                </div>
                <div class="col">
                    <label for="counsellorFilter" class="form-label fw-semibold small text-body-secondary text-uppercase">Counsellor</label>
                    <select class="form-select  rounded-1" id="counsellorFilter">
                        <option value="">All Counsellors</option>
                    </select>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-4 g-3 align-items-end mt-3 pt-3 border-to1">
                <div class="col">
                    <label for="sortBy" class="form-label fw-semibold small text-body-secondary text-uppercase">Sort By</label>
                    <select class="form-select  rounded-1" id="sortBy">
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                        <option value="roll_asc">Roll Number (Low to High)</option>
                        <option value="roll_desc">Roll Number (High to Low)</option>
                        <option value="service_asc">Service (A-Z)</option>
                        <option value="service_desc">Service (Z-A)</option>
                        <option value="course_asc">Course (A-Z)</option>
                        <option value="course_desc">Course (Z-A)</option>
                    </select>
                </div>
                <div class="col">
                    <label for="categoryFilter" class="form-label fw-semibold small text-body-secondary text-uppercase">Service / Category</label>
                    <select class="form-select  rounded-1" id="categoryFilter">
                        <option value="">All Services</option>
                    </select>
                </div>
                <div class="col">
                    <label for="statusFilter" class="form-label fw-semibold small text-body-secondary text-uppercase">Status</label>
                    <select class="form-select  rounded-1" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="col col-md-6 d-flex align-items-end">
                    <label for="perPage" class="form-label fw-semibold small text-body-secondary text-uppercase">Items Per Page</label>
                    <select class="form-select  rounded-1 w-auto" id="perPage">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary strip (participants count + course) -->
    <div id="summaryStrip" class="d-none mb-3">
        <div class="d-flex flex-wrap align-items-center gap-3 py-2 px-3 rounded-3 bg-body-tertiary borde1">
            <span class="d-flex align-items-center gap-2 fw-semibold text-body-emphasis">
                <i class="bi bi-people-fill text-primary"></i>
                <span id="summaryCount">0 participants</span>
            </span>
            <span id="summaryCourse" class="d-none small text-body-secondary">
                <i class="bi bi-mortarboard-fill me-1"></i>
                <span id="summaryCourseName">Course name</span>
            </span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="small text-body-secondary">View:</span>
                <div class="btn-group btn-group-sm" role="group" aria-label="View mode">
                    <input type="radio" class="btn-check" name="viewMode" id="viewModeFull" value="full" checked>
                    <label class="btn btn-outline-primary rounded-2" for="viewModeFull">Full</label>
                    <input type="radio" class="btn-check" name="viewMode" id="viewModeCompact" value="compact">
                    <label class="btn btn-outline-primary rounded-2" for="viewModeCompact">Compact</label>
                </div>
            </div>
        </div>
    </div>

    <!-- Static Information Row - Stat cards -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card border-0 rounded-3 h-100 shadow-sm bg-primary bg-opacity-10 border-start border-4 border-primary">
                <div class="card-body py-3 px-4">
                    <span class="d-block small text-uppercase fw-semibold text-body-secondary mb-1">Tutor Group</span>
                    <span class="fs-5 fw-bold text-body-emphasis" id="tutorGroup">0</span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 rounded-3 h-100 shadow-sm bg-success bg-opacity-10 border-start border-4 border-success">
                <div class="card-body py-3 px-4">
                    <span class="d-block small text-uppercase fw-semibold text-body-secondary mb-1">Tutor Name</span>
                    <span class="fs-6 fw-semibold text-body-emphasis" id="tutorName">Prem Kumar V R & Sachiv Kumar</span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 rounded-3 h-100 shadow-sm bg-info bg-opacity-10 border-start border-4 border-info">
                <div class="card-body py-3 px-4">
                    <span class="d-block small text-uppercase fw-semibold text-body-secondary mb-1">House Name</span>
                    <span class="fs-5 fw-bold text-body-emphasis" id="houseName">Stok Kangri</span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 rounded-3 h-100 shadow-sm bg-warning bg-opacity-10 border-start border-4 border-warning">
                <div class="card-body py-3 px-4">
                    <span class="d-block small text-uppercase fw-semibold text-body-secondary mb-1">House Tutors</span>
                    <span class="fs-6 fw-semibold text-body-emphasis" id="houseTutors">Shelesh Nawal</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Students List Container -->
    <div id="studentsContainer">
        <div class="d-flex justify-content-center align-items-center py-5" id="loadingSpinner">
            <div class="text-center">
                <div class="spinner-border text-primary mb-2" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-body-secondary small mb-0">Loading students…</p>
            </div>
        </div>
    </div>

    <!-- Pagination Container -->
    <div id="paginationContainer" class="d-none mt-4 mb-4">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body py-3 px-4">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-md-6 col-lg-5">
                        <div id="paginationInfo" class="d-flex align-items-center"></div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-7">
                        <nav aria-label="Students pagination" class="d-flex justify-content-md-end justify-content-center">
                            <ul class="pagination pagination-sm mb-0 flex-wrap justify-content-center" id="paginationList"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Template (Hidden) -->
<template id="profileTemplate">
    <article class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <!-- Profile Header -->
        <header class="card-header border-0 bg-gradient d-flex flex-wrap align-items-center gap-3 py-3 px-4"
                style="background: linear-gradient(135deg, #004a93, #0d6efd);">
            <div class="d-flex align-items-center gap-3 flex-grow-1">
                <div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center"
                     style="width: 44px; height: 44px;">
                    <span class="fw-semibold fs-5" id="profileInitials">A</span>
                </div>
                <div class="text-body-emphasis">
                    <h2 class="h5 mb-0 fw-semibold" id="profileName">Participant Name</h2>
                    <p class="mb-0 small">
                        <span id="profileService">Service</span>
                        <span class="mx-1">•</span>
                        <span id="profileCourseCode">Course Code</span>
                    </p>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                <span class="badge bg-body-tertiary text-primary px-3 py-2 rounded-pill fw-semibold" id="profileRoll">Roll</span>
                <span class="badge bg-body-tertiary text-primary border border-0 rounded-pill px-3 py-2 fw-semibold"
                      id="profileIdBadge">ID</span>
                <a href="#" class="btn btn-body-tertiary  rounded-pill fw-semibold d-flex align-items-center gap-1"
                   id="profileHistoryBtn" title="View participant history">
                    <i class="bi bi-clock-history"></i>
                    <span>History</span>
                </a>
            </div>
        </header>

        <!-- Profile Body -->
        <div class="card-body p-4">
            <!-- Compact view: single row (hidden by default, shown when view = compact) -->
            <div class="profile-card-compact-block d-none align-items-center gap-3 py-2 flex-wrap">
                <div class="ratio ratio-1x1 rounded-3 overflow-hidden bg-body-secondary flex-shrink-0" style="width: 48px; height: 48px;">
                    <img src="" alt="" class="img-fluid object-fit-cover" id="profileCompactImage">
                </div>
                <div class="flex-grow-1 min-w-0">
                    <span class="fw-semibold text-body-emphasis d-block" id="profileCompactName">Name</span>
                    <span class="small text-body-secondary"><span id="profileCompactService">Service</span> · <span id="profileCompactCourseCode">Course</span></span>
                </div>
                <span class="badge bg-primary-subtle text-primary rounded-pill" id="profileCompactRoll">Roll</span>
                <span class="badge bg-body-tertiary text-body-emphasis rounded-pill" id="profileCompactIdBadge">ID</span>
                <a href="#" class="btn btn-outline-primary btn-sm rounded-pill ms-auto" id="profileCompactHistoryBtn" title="View history"><i class="bi bi-clock-history"></i> History</a>
            </div>
            <!-- Full view: full detail (default) -->
            <div class="profile-card-detail-block">
            <!-- Tutor & House info (chips row) -->
            <section class="mb-4 pb-3 border-botto1">
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill bg-body-tertiary border text-body-secondary d-flex align-items-center gap-1 px-3 py-2">
                        <i class="bi bi-people-fill text-primary"></i>
                        <span class="small text-uppercase fw-semibold">Tutor Group:</span>
                        <span class="fw-semibold text-body-emphasis" id="profileTutorGroup">0</span>
                    </span>
                    <span class="badge rounded-pill bg-body-tertiary border text-body-secondary d-flex align-items-center gap-1 px-3 py-2">
                        <i class="bi bi-person-workspace text-success"></i>
                        <span class="small text-uppercase fw-semibold">Tutor:</span>
                        <span class="fw-semibold text-body-emphasis small" id="profileTutorName">N/A</span>
                    </span>
                    <span class="badge rounded-pill bg-body-tertiary border text-body-secondary d-flex align-items-center gap-1 px-3 py-2">
                        <i class="bi bi-house-door-fill text-info"></i>
                        <span class="small text-uppercase fw-semibold">House:</span>
                        <span class="fw-semibold text-body-emphasis" id="profileHouseName">N/A</span>
                    </span>
                    <span class="badge rounded-pill bg-body-tertiary border text-body-secondary d-flex align-items-center gap-1 px-3 py-2">
                        <i class="bi bi-people text-warning"></i>
                        <span class="small text-uppercase fw-semibold">House Tutors:</span>
                        <span class="fw-semibold text-body-emphasis small" id="profileHouseTutors">N/A</span>
                    </span>
                </div>
            </section>

            <div class="row g-4">
                <!-- Profile Image & quick facts -->
                <aside class="col-12 col-md-3 col-lg-2">
                    <div class="d-flex flex-column align-items-center gap-3">
                        <div class="ratio ratio-1x1 rounded-4 overflow-hidden bg-body-secondary shadow-sm w-100"
                             style="max-width: 160px;">
                            <img src="" alt="Profile Image" class="img-fluid object-fit-cover" id="profileImage">
                        </div>
                        <div class="w-100 d-flex flex-column gap-1">
                            <div class="d-flex align-items-center justify-content-between small text-body-secondary">
                                <span class="text-uppercase fw-semibold">Batch</span>
                                <span class="fw-semibold text-body-emphasis" id="profileBatch">—</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between small text-body-secondary">
                                <span class="text-uppercase fw-semibold">Room</span>
                                <span class="fw-semibold text-body-emphasis" id="profileRoom">—</span>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Profile Content -->
                <div class="col-12 col-md-9 col-lg-10">
                    <div class="row g-4">
                        <!-- Left Column -->
                        <div class="col-lg-6">
                            <!-- Course Information -->
                            <section class="mb-4">
                                <h3 class="h6 fw-bold text-primary text-uppercase mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-mortarboard-fill"></i>
                                    <span>Course Information</span>
                                </h3>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Course Name</span>
                                        <span class="fw-medium text-body-emphasis" id="profileCourseName">FC-100</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Course Code</span>
                                        <span class="fw-medium text-body-emphasis" id="profileCourseCode">FC-100</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Attempts</span>
                                        <span class="fw-medium text-body-emphasis" id="profileAttempts">2</span>
                                    </div>
                                </div>
                            </section>

                            <!-- Basic Information -->
                            <section class="mb-4">
                                <h3 class="h6 fw-bold text-primary text-uppercase mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-person-vcard-fill"></i>
                                    <span>Basic Information</span>
                                </h3>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Date of Birth</span>
                                        <span class="fw-medium text-body-emphasis" id="profileDob">—</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Domicile State</span>
                                        <span class="fw-medium text-body-emphasis" id="profileDomicile">—</span>
                                    </div>
                                    <div class="col-12">
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Highest Stream</span>
                                        <span class="fw-medium text-body-emphasis" id="profileStream">—</span>
                                    </div>
                                </div>
                            </section>

                            <!-- Hobbies & Interests -->
                            <section>
                                <h3 class="h6 fw-bold text-primary text-uppercase mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-stars"></i>
                                    <span>Hobbies & Interests</span>
                                </h3>
                                <ul class="list-unstyled d-flex flex-column gap-2 mb-0" id="profileHobbies">
                                    <li class="p-2 rounded-3 bg-body-tertiary border-start border-3 border-primary">
                                        <span class="small text-body-emphasis">Sample hobby</span>
                                    </li>
                                </ul>
                            </section>
                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-6">
                            <!-- Contact Information -->
                            <section class="mb-4">
                                <h3 class="h6 fw-bold text-primary text-uppercase mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-envelope-fill"></i>
                                    <span>Contact Information</span>
                                </h3>
                                <div class="d-flex flex-column gap-2">
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Email Address</span>
                                        <span class="fw-medium text-body-emphasis" id="profileEmail">—</span>
                                    </div>
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Contact Number</span>
                                        <span class="fw-medium text-body-emphasis" id="profileContact">—</span>
                                    </div>
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Last Service</span>
                                        <span class="fw-medium text-body-emphasis" id="profileLastService">—</span>
                                    </div>
                                </div>
                            </section>

                            <!-- Educational Qualifications -->
                            <section>
                                <h3 class="h6 fw-bold text-primary text-uppercase mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-journal-bookmark-fill"></i>
                                    <span>Educational Qualifications</span>
                                </h3>
                                <ul class="list-unstyled d-flex flex-column gap-2 mb-0" id="profileEducation">
                                    <li class="p-3 rounded-3 bg-body-tertiary border-start border-3 border-success">
                                        <div class="fw-semibold text-body-emphasis mb-1">Degree</div>
                                        <div class="small text-body-secondary">Institution</div>
                                        <div class="small text-body-secondary fst-italic mt-1">Year</div>
                                    </li>
                                </ul>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </article>
</template>

<style>
.whos-who-filter-sticky { position: sticky; top: 0; z-index: 1020; background: var(--bs-body-bg); transition: box-shadow 0.2s ease; }
.whos-who-filter-sticky.is-scrolled { box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.08); }
.profile-view-compact .profile-card-detail-block { display: none !important; }
.profile-view-compact .profile-card-compact-block { display: flex !important; }
</style>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentsContainer = document.getElementById('studentsContainer');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const paginationContainer = document.getElementById('paginationContainer');
    const paginationList = document.getElementById('paginationList');
    const paginationInfo = document.getElementById('paginationInfo');
    const nameFilter = document.getElementById('nameFilter');
    const courseFilter = document.getElementById('courseFilter');
    const courseTypeFilter = document.getElementById('courseTypeFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const sortBy = document.getElementById('sortBy');
    const perPageSelect = document.getElementById('perPage');
    const resetFilters = document.getElementById('resetFilters');

    let currentPage = 1;
    let perPage = 10;
    let totalPages = 1;
    let totalStudents = 0;
    let allProfiles = [];
    let currentSort = 'name_asc';
    // Static tutor/house info shown on each participant card (updated by loadStaticInfo)
    let lastStaticInfo = { tutorGroup: '0', tutorName: 'N/A', houseName: 'N/A', houseTutors: 'N/A' };
    let lastRenderedStudents = [];
    let lastRenderedPagination = null;
    let viewMode = 'full'; // 'full' | 'compact'

    // Function to render all students
    function renderStudents(students, pagination, customMessage = null) {
        if (!students || students.length === 0) {
            const message = customMessage || 'Please adjust your filters to find students.';
            document.getElementById('summaryStrip').classList.add('d-none');
            studentsContainer.innerHTML = `
                <div class="text-center py-5">
                    <div class="rounded-3 bg-body-tertiary d-inline-flex p-4 mb-3">
                        <i class="bi bi-person-x display-4 text-body-secondary"></i>
                    </div>
                    <h4 class="mt-2 fw-semibold text-body-emphasis">No Students Found</h4>
                    <p class="text-body-secondary mb-0">${message}</p>
                </div>
            `;
            paginationContainer.classList.add('d-none');
            return;
        }

        // Update summary strip
        const summaryStrip = document.getElementById('summaryStrip');
        const summaryCount = document.getElementById('summaryCount');
        const summaryCourse = document.getElementById('summaryCourse');
        const summaryCourseName = document.getElementById('summaryCourseName');
        if (summaryStrip && summaryCount) {
            summaryStrip.classList.remove('d-none');
            const total = pagination ? pagination.total : students.length;
            summaryCount.textContent = total === 1 ? '1 participant' : total + ' participants';
            if (summaryCourse && summaryCourseName && courseFilter) {
                const courseText = courseFilter.options[courseFilter.selectedIndex]?.text || '';
                if (courseText && courseFilter.value) {
                    summaryCourseName.textContent = courseText;
                    summaryCourse.classList.remove('d-none');
                } else {
                    summaryCourse.classList.add('d-none');
                }
            }
        }

        // Update pagination info and cache for re-render when static info loads
        if (pagination) {
            currentPage = pagination.current_page;
            totalPages = pagination.total_pages;
            totalStudents = pagination.total;
            lastRenderedStudents = students;
            lastRenderedPagination = pagination;
        }

        // Clear container
        studentsContainer.innerHTML = '';

        // Render each student
        students.forEach((profile, index) => {
            const template = document.getElementById('profileTemplate');
            const clone = template.content.cloneNode(true);

            // Set profile data
            clone.getElementById('profileName').textContent = profile.name;
            clone.getElementById('profileRoll').textContent = profile.roll;
            clone.getElementById('profileService').textContent = profile.service;
            clone.getElementById('profileImage').src = profile.image;
            clone.getElementById('profileImage').alt = profile.name;
            clone.getElementById('profileIdBadge').textContent = profile.id;
            clone.getElementById('profileCourseName').textContent = profile.courseName || 'N/A';
            clone.getElementById('profileCourseCode').textContent = profile.courseCode || 'N/A';
            clone.getElementById('profileBatch').textContent = profile.batch || 'N/A';
            clone.getElementById('profileDob').textContent = profile.dob;
            clone.getElementById('profileDomicile').textContent = profile.domicile;
            clone.getElementById('profileAttempts').textContent = profile.attempts;
            clone.getElementById('profileStream').textContent = profile.stream;
            clone.getElementById('profileRoom').textContent = profile.room;
            clone.getElementById('profileEmail').textContent = profile.email;
            clone.getElementById('profileContact').textContent = profile.contact;
            clone.getElementById('profileLastService').textContent = profile.lastService;
            // Tutor & House info on every card (from lastStaticInfo)
            clone.getElementById('profileTutorGroup').textContent = lastStaticInfo.tutorGroup;
            clone.getElementById('profileTutorName').textContent = lastStaticInfo.tutorName;
            clone.getElementById('profileHouseName').textContent = lastStaticInfo.houseName;
            clone.getElementById('profileHouseTutors').textContent = lastStaticInfo.houseTutors;
            // History button – link to participant history
            const historyBtn = clone.getElementById('profileHistoryBtn');
            historyBtn.href = profile.historyUrl || '#';

            // Initials for header avatar (querySelector works on fragment)
            const initialsEl = clone.querySelector('#profileInitials');
            if (initialsEl && profile.name) {
                const parts = profile.name.trim().split(/\s+/);
                initialsEl.textContent = parts.length >= 2 ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase() : (profile.name[0] || '?').toUpperCase();
            }

            // Compact block content (same data)
            const compactImg = clone.querySelector('#profileCompactImage');
            const compactName = clone.querySelector('#profileCompactName');
            const compactService = clone.querySelector('#profileCompactService');
            const compactCourseCode = clone.querySelector('#profileCompactCourseCode');
            const compactRoll = clone.querySelector('#profileCompactRoll');
            const compactIdBadge = clone.querySelector('#profileCompactIdBadge');
            const compactHistoryBtn = clone.querySelector('#profileCompactHistoryBtn');
            if (compactImg) compactImg.src = profile.image; if (compactImg) compactImg.alt = profile.name;
            if (compactName) compactName.textContent = profile.name;
            if (compactService) compactService.textContent = profile.service || 'N/A';
            if (compactCourseCode) compactCourseCode.textContent = profile.courseCode || profile.courseName || 'N/A';
            if (compactRoll) compactRoll.textContent = profile.roll || '—';
            if (compactIdBadge) compactIdBadge.textContent = profile.id || '—';
            if (compactHistoryBtn) compactHistoryBtn.href = profile.historyUrl || '#';

            // Apply view mode: compact adds class to hide detail and show compact row
            const article = clone.querySelector('article');
            if (article && viewMode === 'compact') article.classList.add('profile-view-compact');

            // Render hobbies
            const hobbiesList = clone.getElementById('profileHobbies');
            hobbiesList.innerHTML = '';
            if (profile.hobbies && profile.hobbies.length > 0) {
                profile.hobbies.forEach(hobby => {
                    const li = document.createElement('li');
                    li.className = 'p-2 rounded-2 bg-body-tertiary border-start border-primary border-3';
                    li.innerHTML = `<span class="small text-body-emphasis">${hobby}</span>`;
                    hobbiesList.appendChild(li);
                });
            } else {
                hobbiesList.innerHTML = '<li class="p-2 text-body-secondary small">No hobbies listed</li>';
            }

            // Render education
            const educationList = clone.getElementById('profileEducation');
            educationList.innerHTML = '';
            if (profile.education && profile.education.length > 0) {
                profile.education.forEach(edu => {
                    const li = document.createElement('li');
                    li.className = 'p-3 rounded-2 bg-body-tertiary border-start border-success border-3';
                    li.innerHTML = `
                        <div class="fw-semibold text-body-emphasis mb-1">${edu.degree}</div>
                        <div class="small text-body-secondary">${edu.institution}</div>
                        <div class="small text-body-secondary fst-italic mt-1">${edu.year}</div>
                    `;
                    educationList.appendChild(li);
                });
            } else {
                educationList.innerHTML = '<li class="p-3 text-body-secondary small">No education details available</li>';
            }

            studentsContainer.appendChild(clone);
        });

        // Render pagination
        renderPagination(pagination);
    }

    // Function to render pagination using Bootstrap 5.3+ components
    function renderPagination(pagination) {
        if (!pagination || pagination.total_pages <= 1) {
            paginationContainer.classList.add('d-none');
            return;
        }

        paginationContainer.classList.remove('d-none');
        paginationList.innerHTML = '';
        paginationInfo.innerHTML = '';

        const currentPage = pagination.current_page;
        const totalPages = pagination.total_pages;

        // Pagination Info with Bootstrap 5 styling
        paginationInfo.innerHTML = `
            <div class="d-flex align-items-center gap-2 small">
                <i class="bi bi-info-circle text-primary"></i>
                <span class="text-body-secondary">Showing <strong class="text-body-emphasis">${pagination.from}</strong> to <strong class="text-body-emphasis">${pagination.to}</strong> of <strong class="text-body-emphasis">${pagination.total}</strong> students</span>
            </div>
        `;

        // Previous button with Bootstrap 5.3+ styling
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `
            <a class="page-link" href="#" data-page="${currentPage - 1}" ${currentPage === 1 ? 'tabindex="-1" aria-disabled="true"' : ''} aria-label="Previous page">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </a>
        `;
        paginationList.appendChild(prevLi);

        // Page numbers with Bootstrap 5 styling
        const maxVisible = 7;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);

        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        // First page
        if (startPage > 1) {
            const firstLi = document.createElement('li');
            firstLi.className = 'page-item';
            firstLi.innerHTML = `<a class="page-link" href="#" data-page="1">1</a>`;
            paginationList.appendChild(firstLi);
            if (startPage > 2) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                ellipsisLi.setAttribute('aria-disabled', 'true');
                ellipsisLi.innerHTML = `<span class="page-link">…</span>`;
                paginationList.appendChild(ellipsisLi);
            }
        }

        // Page range with Bootstrap 5.3+ active state
        for (let i = startPage; i <= endPage; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
            if (i === currentPage) {
                pageLi.setAttribute('aria-current', 'page');
                pageLi.innerHTML = `<span class="page-link">${i}</span>`;
            } else {
                pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
            }
            paginationList.appendChild(pageLi);
        }

        // Last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                ellipsisLi.setAttribute('aria-disabled', 'true');
                ellipsisLi.innerHTML = `<span class="page-link">…</span>`;
                paginationList.appendChild(ellipsisLi);
            }
            const lastLi = document.createElement('li');
            lastLi.className = 'page-item';
            lastLi.innerHTML = `<a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>`;
            paginationList.appendChild(lastLi);
        }

        // Next button with Bootstrap 5.3+ styling
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `
            <a class="page-link" href="#" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'tabindex="-1" aria-disabled="true"' : ''} aria-label="Next page">
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </a>
        `;
        paginationList.appendChild(nextLi);

        // Add click handlers
        paginationList.querySelectorAll('a[data-page]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                if (page >= 1 && page <= totalPages && page !== currentPage) {
                    filterProfiles(page);
                }
            });
        });

        // Pagination info is now shown in the card header, not here
    }

    // Function to fetch students from API
    async function fetchStudents(page = 1) {
        const name = nameFilter.value.trim();
        const courseId = courseFilter.value;
        const category = categoryFilter.value;
        const status = statusFilter.value;
        const sortValue = sortBy.value || 'name_asc';

        // Show loading state
        loadingSpinner.classList.remove('d-none');
        loadingSpinner.classList.add('d-flex');
        studentsContainer.innerHTML = '';
        paginationContainer.classList.add('d-none');

        try {
            const params = new URLSearchParams();
            if (name) params.append('name', name);
            if (courseId && courseId !== '' && courseId !== null) {
                params.append('course_id', courseId);
                console.log('Filtering by course ID:', courseId, 'Course Name:', courseFilter.options[courseFilter.selectedIndex]?.text);
            } else {
                console.log('No course filter applied - showing all students');
            }
            if (category && category !== '') params.append('category', category);
            if (status && status !== '') params.append('status', status);
            params.append('course_type', courseTypeFilter && courseTypeFilter.value ? courseTypeFilter.value : 'active');
            params.append('page', page);
            params.append('per_page', perPage);
            params.append('sort_by', sortValue);

            const apiUrl = '{{ route("admin.faculty.whos-who.students") }}?' + params.toString();
            console.log('Fetching students from:', apiUrl);
            
            const response = await fetch(apiUrl);
            const data = await response.json();
            
            console.log('API Response:', data);

            if (data.success) {
                if (data.students && data.students.length > 0) {
                    allProfiles = data.students;
                    const courseName = courseFilter.options[courseFilter.selectedIndex]?.text || 'All Courses';
                    console.log(`✓ Found ${data.pagination.total} total students (showing ${data.students.length} on page ${data.pagination.current_page}) for: ${courseName}`);
                    renderStudents(data.students, data.pagination);
                } else {
                    allProfiles = [];
                    const selectedCourse = courseFilter.options[courseFilter.selectedIndex]?.text || 'selected course';
                    console.log(`✗ No students found for: ${selectedCourse} (ID: ${courseId || 'All'})`);
                    renderStudents([], null, courseId ? `No students found for "${selectedCourse}"` : 'No students found');
                }
            } else {
                console.error('API Error:', data.message || 'Unknown error');
                allProfiles = [];
                renderStudents([], null, data.message || 'Error loading student data');
            }
        } catch (error) {
            console.error('Error fetching students:', error);
            studentsContainer.innerHTML = `
                <div class="text-center py-5">
                    <div class="rounded-3 bg-danger bg-opacity-10 d-inline-flex p-4 mb-3">
                        <i class="bi bi-exclamation-triangle display-4 text-danger"></i>
                    </div>
                    <h4 class="mt-2 fw-semibold text-body-emphasis">Error Loading Data</h4>
                    <p class="text-body-secondary">Please try again later.</p>
                    <p class="text-danger small mt-2">${error.message}</p>
                </div>
            `;
        } finally {
            loadingSpinner.classList.add('d-none');
            loadingSpinner.classList.remove('d-flex');
        }
    }

    // Function to filter profiles (now uses API)
    function filterProfiles(page = 1) {
        currentPage = page;
        fetchStudents(page);
    }

    // Function to handle per page change
    function handlePerPageChange() {
        perPage = parseInt(perPageSelect.value);
        currentPage = 1; // Reset to first page
        filterProfiles(1);
    }

    // Function to handle sort change
    function handleSortChange() {
        currentSort = sortBy.value;
        currentPage = 1; // Reset to first page when sorting changes
        filterProfiles(1);
    }

    // Function to reset filters
    function resetAllFilters() {
        nameFilter.value = '';
        courseFilter.value = '';
        categoryFilter.value = '';
        statusFilter.value = '';
        sortBy.value = 'name_asc';
        perPageSelect.value = '10';
        perPage = 10;
        currentPage = 1;
        currentSort = 'name_asc';
        filterProfiles(1);
    }

    // Function to load courses dynamically (can be replaced with API call)
    // Function to load courses dynamically from API (only if not already loaded)
    async function loadCourses() {
        try {
            const courseSelect = document.getElementById('courseFilter');
            
            // Check if courses are already loaded (more than just "All Courses")
            if (courseSelect.options.length > 1) {
                console.log('Courses already loaded, skipping reload');
                return Promise.resolve();
            }
            
            const response = await fetch('{{ route("admin.faculty.whos-who.courses") }}');
            const data = await response.json();
            
            if (data.success && data.courses) {
                // Keep "All Courses" option
                const allCoursesOption = courseSelect.querySelector('option[value=""]');
                const currentValue = courseSelect.value; // Save current selection
                
                // Clear and rebuild options
                courseSelect.innerHTML = '';
                
                // Add "All Courses" option
                const allOption = document.createElement('option');
                allOption.value = '';
                allOption.textContent = 'All Courses';
                courseSelect.appendChild(allOption);
                
                // Add course options
                data.courses.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.pk;
                    option.textContent = course.course_name;
                    courseSelect.appendChild(option);
                });
                
                // Restore previous selection if it exists
                if (currentValue) {
                    courseSelect.value = currentValue;
                }
                
                console.log('Courses loaded:', data.courses.length);
            }
            return Promise.resolve();
        } catch (error) {
            console.error('Error loading courses:', error);
            return Promise.reject(error);
        }
    }

    // Function to load static info
    async function loadStaticInfo() {
        try {
            const courseId = courseFilter.value;
            const params = courseId ? '?course_id=' + courseId : '';
            const response = await fetch('{{ route("admin.faculty.whos-who.static-info") }}' + params);
            const data = await response.json();
            
            if (data.success && data.data) {
                lastStaticInfo = {
                    tutorGroup: data.data.tutorGroup !== undefined ? String(data.data.tutorGroup) : 'N/A',
                    tutorName: data.data.tutorName !== undefined ? String(data.data.tutorName) : 'N/A',
                    houseName: data.data.houseName !== undefined ? String(data.data.houseName) : 'N/A',
                    houseTutors: data.data.houseTutors !== undefined ? String(data.data.houseTutors) : 'N/A'
                };
                document.getElementById('tutorGroup').textContent = lastStaticInfo.tutorGroup;
                document.getElementById('tutorName').textContent = lastStaticInfo.tutorName;
                document.getElementById('houseName').textContent = lastStaticInfo.houseName;
                document.getElementById('houseTutors').textContent = lastStaticInfo.houseTutors;
                // Re-render current student cards so they show updated tutor/house info
                if (lastRenderedStudents.length > 0 && lastRenderedPagination) {
                    renderStudents(lastRenderedStudents, lastRenderedPagination);
                }
            }
        } catch (error) {
            console.error('Error loading static info:', error);
        }
    }

    // Debounce function for name input
    let nameInputTimeout;
    function debounceNameInput() {
        clearTimeout(nameInputTimeout);
        nameInputTimeout = setTimeout(() => {
            filterProfiles();
        }, 500); // Wait 500ms after user stops typing
    }

    // Event listeners
    nameFilter.addEventListener('input', debounceNameInput);
    
    // Course filter - immediately fetch students when course changes
    courseFilter.addEventListener('change', function() {
        const selectedCourseId = courseFilter.value;
        const selectedCourseName = courseFilter.options[courseFilter.selectedIndex]?.text || '';
        console.log('Course changed:', selectedCourseId, selectedCourseName);
        
        // Reset to first page when course changes
        currentPage = 1;
        
        // Clear name filter when course changes to show all students in that course
        // nameFilter.value = ''; // Uncomment if you want to clear name filter on course change
        
        // Immediately fetch students for the selected course
        filterProfiles(1);
        loadStaticInfo();
    });
    
    categoryFilter.addEventListener('change', function() {
        currentPage = 1;
        filterProfiles(1);
    });
    
    statusFilter.addEventListener('change', function() {
        currentPage = 1;
        filterProfiles(1);
    });
    
    // Sorting and pagination controls
    sortBy.addEventListener('change', handleSortChange);
    perPageSelect.addEventListener('change', handlePerPageChange);
    
    resetFilters.addEventListener('click', resetAllFilters);

    // View mode toggle: re-render with same data
    document.querySelectorAll('input[name="viewMode"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            viewMode = this.value;
            if (lastRenderedStudents.length > 0 && lastRenderedPagination) {
                renderStudents(lastRenderedStudents, lastRenderedPagination);
            }
        });
    });

    // Sticky filter bar: add shadow when scrolled
    (function() {
        const stickyEl = document.getElementById('filterBarSticky');
        if (!stickyEl) return;
        function updateSticky() {
            if (window.scrollY > 20) stickyEl.classList.add('is-scrolled');
            else stickyEl.classList.remove('is-scrolled');
        }
        window.addEventListener('scroll', updateSticky, { passive: true });
        updateSticky();
    })();

    // Initial load - courses are already loaded from backend, just fetch students
    // Only reload courses if dropdown is empty
    if (courseFilter.options.length <= 1) {
        loadCourses().then(() => {
            // Fetch students after courses are loaded
            filterProfiles(1);
            loadStaticInfo();
        }).catch(() => {
            // Even if courses fail to load, try to fetch students
            filterProfiles(1);
            loadStaticInfo();
        });
    } else {
        // Courses already loaded, just fetch students
        filterProfiles(1);
        loadStaticInfo();
    }
});
</script>
@endpush
