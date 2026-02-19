@extends('admin.layouts.master')

@section('title', "Who's Who")

@section('setup_content')

<div class="container-fluid py-3 py-md-4 px-3 px-md-4">
    <x-breadcrum title="Who's Who"></x-breadcrum>

    <!-- Filter Section -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-6 g-3 align-items-end">
                <div class="col">
                    <label for="courseTypeFilter" class="form-label fw-semibold small text-body-secondary text-uppercase">Course Status</label>
                    <select class="form-select form-select-sm rounded-2 border-secondary-subtle" id="courseTypeFilter">
                        <option value="active" selected>Active</option>
                        <option value="archive">Archive</option>
                    </select>
                </div>
                <div class="col">
                    <label for="nameFilter" class="form-label fw-semibold small text-body-secondary text-uppercase">Name</label>
                    <input type="text" class="form-control form-control-sm rounded-2 border-secondary-subtle" id="nameFilter" placeholder="Search by name">
                </div>
                <div class="col">
                    <label for="courseFilter" class="form-label fw-semibold small text-body-secondary text-uppercase">Course Name</label>
                    <select class="form-select form-select-sm rounded-2 border-secondary-subtle" id="courseFilter" data-loaded-type="active">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col">
                    <label for="cadreFilter" class="form-label fw-semibold small text-body-secondary text-uppercase">Cadre</label>
                    <select class="form-select form-select-sm rounded-2 border-secondary-subtle" id="cadreFilter">
                        <option value="">All Cadres</option>
                    </select>
                </div>
                <div class="col">
                    <label for="counsellorFilter" class="form-label fw-semibold small text-body-secondary text-uppercase">Counsellor</label>
                    <select class="form-select form-select-sm rounded-2 border-secondary-subtle" id="counsellorFilter">
                        <option value="">All Counsellors</option>
                    </select>
                </div>
                <div class="col">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 rounded-2 fw-medium" id="resetFilters">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset
                    </button>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-3 g-3 align-items-end mt-3 pt-3 border-top border-secondary-subtle">
                <div class="col">
                    <label for="sortBy" class="form-label fw-semibold small text-body-secondary text-uppercase">Sort By</label>
                    <select class="form-select form-select-sm rounded-2 border-secondary-subtle" id="sortBy">
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
                    <label for="perPage" class="form-label fw-semibold small text-body-secondary text-uppercase">Items Per Page</label>
                    <select class="form-select form-select-sm rounded-2 border-secondary-subtle w-auto" id="perPage">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col col-md-6 d-flex align-items-end">
                    <p class="text-body-secondary small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Use filters and sorting to find students
                    </p>
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
    <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
        <!-- Profile Header -->
        <div class="card-header text-bg-primary border-0 py-3 py-md-4 px-4 rounded-0">
            <div class="row align-items-center g-2 g-md-3">
                <div class="col-12 col-md-3">
                    <h2 class="h5 mb-0 fw-bold text-white" id="profileName">Aakash Garg</h2>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <span class="badge bg-light text-primary fs-6 px-3 py-2 rounded-2 fw-semibold" id="profileRoll">Roll 5</span>
                </div>
                <div class="col-6 col-md-3 text-md-end">
                    <span class="fw-medium opacity-90 small" id="profileService">INDIAN ADMINISTRATIVE SERVICE</span>
                </div>
                <div class="col-12 col-md-3 text-md-end mt-2 mt-md-0">
                    <a href="#" class="btn btn-light btn-sm rounded-2 fw-semibold" id="profileHistoryBtn" title="View participant history">
                        <i class="bi bi-clock-history me-1"></i> History
                    </a>
                </div>
            </div>
        </div>

        <!-- Profile Body -->
        <div class="card-body p-4">
            <!-- Tutor & House info (on every participant card) -->
            <div class="row row-cols-2 row-cols-md-4 g-2 g-md-3 mb-4 pb-3 border-bottom border-secondary-subtle">
                <div class="col">
                    <span class="d-block small text-uppercase fw-semibold text-body-secondary">Tutor Group</span>
                    <span class="fw-semibold text-body-emphasis" id="profileTutorGroup">0</span>
                </div>
                <div class="col">
                    <span class="d-block small text-uppercase fw-semibold text-body-secondary">Tutor Name</span>
                    <span class="fw-semibold text-body-emphasis small" id="profileTutorName">N/A</span>
                </div>
                <div class="col">
                    <span class="d-block small text-uppercase fw-semibold text-body-secondary">House Name</span>
                    <span class="fw-semibold text-body-emphasis" id="profileHouseName">N/A</span>
                </div>
                <div class="col">
                    <span class="d-block small text-uppercase fw-semibold text-body-secondary">House Tutors</span>
                    <span class="fw-semibold text-body-emphasis small" id="profileHouseTutors">N/A</span>
                </div>
            </div>

            <div class="row g-4">
                <!-- Profile Image Section -->
                <div class="col-12 col-md-3 col-lg-2">
                    <div class="d-flex flex-column align-items-center gap-2">
                        <div class="ratio ratio-1x1 rounded-3 overflow-hidden bg-body-secondary" style="max-width: 140px;">
                            <img src="" alt="Profile Image" class="img-fluid object-fit-cover" id="profileImage">
                        </div>
                        <span class="badge text-bg-danger rounded-2 px-3 py-2 fw-semibold" id="profileIdBadge">O30</span>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="col-12 col-md-9 col-lg-10">
                    <div class="row g-4">
                        <!-- Left Column -->
                        <div class="col-lg-6">
                            <!-- Course Information -->
                            <div class="mb-4">
                                <h3 class="h6 fw-bold text-primary text-uppercase mb-3 pb-2 border-bottom border-2 border-primary-subtle">Course Information</h3>
                                <div class="d-flex flex-column gap-2">
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Course Name</span>
                                        <span class="fw-medium text-body-emphasis" id="profileCourseName">FC-100</span>
                                    </div>
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Course Code</span>
                                        <span class="fw-medium text-body-emphasis" id="profileCourseCode">FC-100</span>
                                    </div>
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Batch</span>
                                        <span class="fw-medium text-body-emphasis" id="profileBatch">2024-2025</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Information -->
                            <div class="mb-4">
                                <h3 class="h6 fw-bold text-primary text-uppercase mb-3 pb-2 border-bottom border-2 border-primary-subtle">Basic Information</h3>
                                <div class="d-flex flex-column gap-2">
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Date of Birth</span>
                                        <span class="fw-medium text-body-emphasis" id="profileDob">9/22/2000</span>
                                    </div>
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Domicile State</span>
                                        <span class="fw-medium text-body-emphasis" id="profileDomicile">DELHI</span>
                                    </div>
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">No. of Attempts</span>
                                        <span class="fw-medium text-body-emphasis" id="profileAttempts">2</span>
                                    </div>
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Highest Stream</span>
                                        <span class="fw-medium text-body-emphasis" id="profileStream">Engineering</span>
                                    </div>
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Room No.</span>
                                        <span class="fw-medium text-body-emphasis" id="profileRoom">SW-309</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Hobbies & Interests -->
                            <div>
                                <h3 class="h6 fw-bold text-primary text-uppercase mb-3 pb-2 border-bottom border-2 border-primary-subtle">Hobbies & Interests</h3>
                                <ul class="list-unstyled d-flex flex-column gap-2 mb-0" id="profileHobbies">
                                    <li class="p-2 rounded-2 bg-body-tertiary border-start border-primary border-3">
                                        <span class="small text-body-emphasis">Formula 1 Racing</span>
                                    </li>
                                    <li class="p-2 rounded-2 bg-body-tertiary border-start border-primary border-3">
                                        <span class="small text-body-emphasis">Science Fiction</span>
                                    </li>
                                    <li class="p-2 rounded-2 bg-body-tertiary border-start border-primary border-3">
                                        <span class="small text-body-emphasis">Movies</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-6">
                            <!-- Contact Information -->
                            <div class="mb-4">
                                <h3 class="h6 fw-bold text-primary text-uppercase mb-3 pb-2 border-bottom border-2 border-primary-subtle">Contact Information</h3>
                                <div class="d-flex flex-column gap-2">
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Email Address</span>
                                        <span class="fw-medium text-body-emphasis" id="profileEmail">aakashgarg01@gmail.com</span>
                                    </div>
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Contact Number</span>
                                        <span class="fw-medium text-body-emphasis" id="profileContact">8800372932</span>
                                    </div>
                                    <div>
                                        <span class="d-block small text-uppercase fw-semibold text-body-secondary">Last Service</span>
                                        <span class="fw-medium text-body-emphasis" id="profileLastService">N/A</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Educational Qualifications -->
                            <div>
                                <h3 class="h6 fw-bold text-primary text-uppercase mb-3 pb-2 border-bottom border-2 border-primary-subtle">Educational Qualifications</h3>
                                <ul class="list-unstyled d-flex flex-column gap-2 mb-0" id="profileEducation">
                                    <li class="p-3 rounded-2 bg-body-tertiary border-start border-success border-3">
                                        <div class="fw-semibold text-body-emphasis mb-1">Bachelors of Technology (B.Tech)</div>
                                        <div class="small text-body-secondary">Maharaja Agrasen Institute of Technology</div>
                                        <div class="small text-body-secondary fst-italic mt-1">To year 2022</div>
                                    </li>
                                    <li class="p-3 rounded-2 bg-body-tertiary border-start border-success border-3">
                                        <div class="fw-semibold text-body-emphasis mb-1">Senior Secondary (12th)</div>
                                        <div class="small text-body-secondary">CRPF Public School, Rohini</div>
                                        <div class="small text-body-secondary fst-italic mt-1">To year 2018</div>
                                    </li>
                                    <li class="p-3 rounded-2 bg-body-tertiary border-start border-success border-3">
                                        <div class="fw-semibold text-body-emphasis mb-1">Higher Secondary (10th)</div>
                                        <div class="small text-body-secondary">Gitarattan Jindal Public School, Rohini</div>
                                        <div class="small text-body-secondary fst-italic mt-1">To year 2016</div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

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
    const cadreFilter = document.getElementById('cadreFilter');
    const counsellorFilter = document.getElementById('counsellorFilter');
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

    // Function to render all students
    function renderStudents(students, pagination, customMessage = null) {
        if (!students || students.length === 0) {
            const message = customMessage || 'Please adjust your filters to find students.';
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

    // Get selected course type (active or archive)
    function getCourseType() {
        return courseTypeFilter ? courseTypeFilter.value : 'active';
    }

    // Function to fetch students from API
    async function fetchStudents(page = 1) {
        const name = nameFilter.value.trim();
        const courseId = courseFilter.value;
        const courseType = getCourseType();
        const cadreId = cadreFilter.value;
        const groupId = counsellorFilter.value;
        const sortValue = sortBy.value || 'name_asc';

        // Show loading state
        loadingSpinner.classList.remove('d-none');
        loadingSpinner.classList.add('d-flex');
        studentsContainer.innerHTML = '';
        paginationContainer.classList.add('d-none');

        try {
            const params = new URLSearchParams();
            params.append('course_type', courseType);
            if (name) params.append('name', name);
            if (courseId && courseId !== '' && courseId !== null) {
                params.append('course_id', courseId);
                console.log('Filtering by course ID:', courseId, 'Course Name:', courseFilter.options[courseFilter.selectedIndex]?.text);
            } else {
                console.log('No course filter applied - showing all students');
            }
            if (cadreId && cadreId !== '') params.append('cadre_id', cadreId);
            if (groupId && groupId !== '') params.append('group_id', groupId);
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
        courseTypeFilter.value = 'active';
        loadCourses(true).then(() => {
            loadCadres();
            loadCounsellorGroups();
            cadreFilter.value = '';
            counsellorFilter.value = '';
            sortBy.value = 'name_asc';
            perPageSelect.value = '10';
            perPage = 10;
            currentPage = 1;
            currentSort = 'name_asc';
            filterProfiles(1);
            loadStaticInfo();
        }).catch(() => {
            loadCadres();
            loadCounsellorGroups();
            cadreFilter.value = '';
            counsellorFilter.value = '';
            sortBy.value = 'name_asc';
            perPageSelect.value = '10';
            perPage = 10;
            currentPage = 1;
            currentSort = 'name_asc';
            filterProfiles(1);
        });
    }

    // Function to load courses dynamically (can be replaced with API call)
    // Load cadres based on course type and course
    async function loadCadres() {
        try {
            const courseType = getCourseType();
            const courseId = courseFilter.value || '';
            const params = new URLSearchParams({ course_type: courseType });
            if (courseId) params.append('course_id', courseId);
            const response = await fetch('{{ route("admin.faculty.whos-who.cadres") }}?' + params);
            const data = await response.json();
            if (data.success && data.cadres) {
                const currentVal = cadreFilter.value;
                cadreFilter.innerHTML = '<option value="">All Cadres</option>';
                data.cadres.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.pk;
                    opt.textContent = c.cadre_name;
                    cadreFilter.appendChild(opt);
                });
                if (currentVal && [...cadreFilter.options].some(o => o.value === currentVal)) {
                    cadreFilter.value = currentVal;
                }
            }
        } catch (e) {
            console.error('Error loading cadres:', e);
        }
    }

    // Load counsellor groups based on course type and course
    async function loadCounsellorGroups() {
        try {
            const courseType = getCourseType();
            const courseId = courseFilter.value || '';
            const params = new URLSearchParams({ course_type: courseType });
            if (courseId) params.append('course_id', courseId);
            const response = await fetch('{{ route("admin.faculty.whos-who.counsellor-groups") }}?' + params);
            const data = await response.json();
            if (data.success && data.groups) {
                const currentVal = counsellorFilter.value;
                counsellorFilter.innerHTML = '<option value="">All Counsellors</option>';
                data.groups.forEach(g => {
                    const opt = document.createElement('option');
                    opt.value = g.group_pk;
                    opt.textContent = (g.counsellor_type_name ? g.counsellor_type_name + ' - ' : '') + g.group_name;
                    counsellorFilter.appendChild(opt);
                });
                if (currentVal && [...counsellorFilter.options].some(o => o.value === currentVal)) {
                    counsellorFilter.value = currentVal;
                }
            }
        } catch (e) {
            console.error('Error loading counsellor groups:', e);
        }
    }

    // Function to load courses dynamically from API based on course type (active/archive)
    async function loadCourses(forceReload = false) {
        try {
            const courseSelect = document.getElementById('courseFilter');
            const courseType = getCourseType();
            
            // Check if courses are already loaded for this type (unless force reload)
            if (!forceReload && courseSelect.options.length > 1 && courseSelect.dataset.loadedType === courseType) {
                console.log('Courses already loaded for', courseType, ', skipping reload');
                return Promise.resolve();
            }
            
            const response = await fetch('{{ route("admin.faculty.whos-who.courses") }}?course_type=' + courseType);
            const data = await response.json();
            
            if (data.success && data.courses) {
                const currentValue = courseSelect.value;
                
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
                
                // Store loaded type for cache check
                courseSelect.dataset.loadedType = courseType;
                
                // Reset to "All Courses" when type changes (handled by caller for forceReload)
                if (forceReload) {
                    courseSelect.value = '';
                } else if (currentValue) {
                    courseSelect.value = currentValue;
                }
                
                console.log('Courses loaded for', courseType + ':', data.courses.length);
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

    // Course Status dropdown - reload courses and fetch students when course type changes
    courseTypeFilter.addEventListener('change', function() {
        const courseType = this.value;
        console.log('Course type changed to:', courseType);
        currentPage = 1;
        loadCourses(true).then(() => {
            loadCadres();
            loadCounsellorGroups();
            filterProfiles(1);
            loadStaticInfo();
        }).catch(() => {
            loadCadres();
            loadCounsellorGroups();
            filterProfiles(1);
            loadStaticInfo();
        });
    });
    
    // Course filter - reload cadre/counsellor options and fetch students when course changes
    courseFilter.addEventListener('change', function() {
        const selectedCourseId = courseFilter.value;
        const selectedCourseName = courseFilter.options[courseFilter.selectedIndex]?.text || '';
        console.log('Course changed:', selectedCourseId, selectedCourseName);
        currentPage = 1;
        loadCadres();
        loadCounsellorGroups();
        filterProfiles(1);
        loadStaticInfo();
    });
    
    cadreFilter.addEventListener('change', function() {
        currentPage = 1;
        filterProfiles(1);
    });
    
    counsellorFilter.addEventListener('change', function() {
        currentPage = 1;
        filterProfiles(1);
    });
    
    // Sorting and pagination controls
    sortBy.addEventListener('change', handleSortChange);
    perPageSelect.addEventListener('change', handlePerPageChange);
    
    resetFilters.addEventListener('click', resetAllFilters);

    // Initial load - courses are already loaded from backend
    function doInitialLoad() {
        loadCadres();
        loadCounsellorGroups();
        filterProfiles(1);
        loadStaticInfo();
    }
    if (courseFilter.options.length <= 1) {
        loadCourses().then(doInitialLoad).catch(doInitialLoad);
    } else {
        doInitialLoad();
    }
});
</script>
@endpush
