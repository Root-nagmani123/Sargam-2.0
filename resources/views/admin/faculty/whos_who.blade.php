@extends('admin.layouts.master')

@section('title', "Who's Who")

@section('setup_content')

<div class="container-fluid py-4">
    <x-breadcrum title="Who's Who"></x-breadcrum>

    <!-- Filter Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="courseTypeFilter" class="form-label fw-semibold">Course Status</label>
                    <select class="form-select" id="courseTypeFilter">
                        <option value="active" selected>Active</option>
                        <option value="archive">Archive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="nameFilter" class="form-label fw-semibold">Name</label>
                    <input type="text" class="form-control" id="nameFilter" placeholder="Enter name to search">
                </div>
                <div class="col-md-2">
                    <label for="courseFilter" class="form-label fw-semibold">Course Name</label>
                    <select class="form-select" id="courseFilter" data-loaded-type="active">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="cadreFilter" class="form-label fw-semibold">Cadre</label>
                    <select class="form-select" id="cadreFilter">
                        <option value="">All Cadres</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="counsellorFilter" class="form-label fw-semibold">Counsellor</label>
                    <select class="form-select" id="counsellorFilter">
                        <option value="">All Counsellors</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary w-100" id="resetFilters">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
            
            <!-- Sorting and Per Page Controls -->
            <div class="row g-3 align-items-end mt-3 pt-3 border-top">
                <div class="col-md-4">
                    <label for="sortBy" class="form-label fw-semibold">Sort By</label>
                    <select class="form-select" id="sortBy">
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
                <div class="col-md-3">
                    <label for="perPage" class="form-label fw-semibold">Items Per Page</label>
                    <select class="form-select" id="perPage">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <div class="d-flex align-items-end h-100">
                        <div class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Use filters above and sorting options to find students
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Static Information Row -->
    <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <span class="small text-uppercase fw-bold text-secondary mb-1">TUTOR GROUP</span>
                        <span class="fs-6 fw-bold text-dark" id="tutorGroup">0</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <span class="small text-uppercase fw-bold text-secondary mb-1">TUTOR NAME</span>
                        <span class="fs-6 fw-bold text-dark" id="tutorName">Prem Kumar V R & Sachiv Kumar</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <span class="small text-uppercase fw-bold text-secondary mb-1">HOUSE NAME</span>
                        <span class="fs-6 fw-bold text-dark" id="houseName">Stok Kangri</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <span class="small text-uppercase fw-bold text-secondary mb-1">HOUSE TUTORS</span>
                        <span class="fs-6 fw-bold text-dark" id="houseTutors">Shelesh Nawal</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Students List Container -->
    <div id="studentsContainer">
        <!-- Students will be dynamically loaded here -->
        <div class="d-flex justify-content-center align-items-center py-5" id="loadingSpinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <!-- Pagination Container -->
    <div id="paginationContainer" class="d-none mt-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body py-3">
                <div class="row align-items-center g-3">
                    <div class="col-md-6 col-lg-5">
                        <div id="paginationInfo" class="d-flex align-items-center">
                            <!-- Pagination info will be added here -->
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-7">
                        <nav aria-label="Students pagination" class="d-flex justify-content-end">
                            <ul class="pagination mb-0" id="paginationList">
                                <!-- Pagination will be dynamically generated here -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Template (Hidden) -->
<template id="profileTemplate">
    <div class="card shadow-lg mb-4">
        <!-- Profile Header -->
        <div class="card-header bg-primary text-white p-4">
            <div class="row align-items-center g-3">
                <div class="col-md-4">
                    <h3 class="h4 mb-0 fw-bold text-white" id="profileName">Aakash Garg</h3>
                </div>
                <div class="col-md-4 text-center">
                    <div class="fs-5 fw-semibold" id="profileRoll">Roll 5</div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="small fw-medium opacity-90" id="profileService">INDIAN ADMINISTRATIVE SERVICE</div>
                </div>
            </div>
        </div>
        
        <!-- Profile Body -->
        <div class="card-body p-4">
            <div class="row g-4">
                <!-- Profile Image Section -->
                <div class="col-md-3 col-lg-2">
                    <div class="d-flex flex-column align-items-center gap-3">
                        <div class="ratio ratio-1x1 w-75 mx-auto">
                            <img src="" alt="Profile Image" class="img-fluid rounded border border-3 border-light shadow-sm object-fit-cover" id="profileImage">
                        </div>
                        <div class="badge bg-danger fs-6 px-3 py-2" id="profileIdBadge">O30</div>
                    </div>
                </div>
                
                <!-- Profile Content -->
                <div class="col-md-9 col-lg-10">
                    <div class="row g-4">
                        <!-- Left Column -->
                        <div class="col-lg-6">
                            <!-- Course Information -->
                            <div class="mb-4">
                                <h4 class="h5 fw-bold text-primary text-uppercase border-bottom border-primary border-2 pb-2 mb-3">Course Information</h4>
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex flex-column">
                                        <span class="small text-uppercase fw-bold text-secondary">Course Name</span>
                                        <span class="fs-6 fw-medium text-dark" id="profileCourseName">FC-100</span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="small text-uppercase fw-bold text-secondary">Course Code</span>
                                        <span class="fs-6 fw-medium text-dark" id="profileCourseCode">FC-100</span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="small text-uppercase fw-bold text-secondary">Batch</span>
                                        <span class="fs-6 fw-medium text-dark" id="profileBatch">2024-2025</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Basic Information -->
                            <div class="mb-4">
                                <h4 class="h5 fw-bold text-primary text-uppercase border-bottom border-primary border-2 pb-2 mb-3">Basic Information</h4>
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex flex-column">
                                        <span class="small text-uppercase fw-bold text-secondary">Date of Birth</span>
                                        <span class="fs-6 fw-medium text-dark" id="profileDob">9/22/2000</span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="small text-uppercase fw-bold text-secondary">Domicile State</span>
                                        <span class="fs-6 fw-medium text-dark" id="profileDomicile">DELHI</span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="small text-uppercase fw-bold text-secondary">No. of Attempts</span>
                                        <span class="fs-6 fw-medium text-dark" id="profileAttempts">2</span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="small text-uppercase fw-bold text-secondary">Highest Stream</span>
                                        <span class="fs-6 fw-medium text-dark" id="profileStream">Engineering</span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="small text-uppercase fw-bold text-secondary">Room No.</span>
                                        <span class="fs-6 fw-medium text-dark" id="profileRoom">SW-309</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hobbies & Interests -->
                            <div>
                                <h4 class="h5 fw-bold text-primary text-uppercase border-bottom border-primary border-2 pb-2 mb-3">Hobbies & Interests</h4>
                                <ul class="list-unstyled d-flex flex-column gap-2" id="profileHobbies">
                                    <li class="p-2 bg-light rounded border-start border-primary border-3">
                                        <span class="small text-dark">Formula 1 Racing</span>
                                    </li>
                                    <li class="p-2 bg-light rounded border-start border-primary border-3">
                                        <span class="small text-dark">Science Fiction</span>
                                    </li>
                                    <li class="p-2 bg-light rounded border-start border-primary border-3">
                                        <span class="small text-dark">Movies</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-lg-6">
                            <!-- Contact Information -->
                            <div class="mb-4">
                                <h4 class="h5 fw-bold text-primary text-uppercase border-bottom border-primary border-2 pb-2 mb-3">Contact Information</h4>
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex flex-column">
                                        <span class="small text-uppercase fw-bold text-secondary">EMAIL ADDRESS</span>
                                        <span class="fs-6 fw-medium text-dark" id="profileEmail">aakashgarg01@gmail.com</span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="small text-uppercase fw-bold text-secondary">CONTACT NUMBER</span>
                                        <span class="fs-6 fw-medium text-dark" id="profileContact">8800372932</span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="small text-uppercase fw-bold text-secondary">LAST SERVICE</span>
                                        <span class="fs-6 fw-medium text-dark" id="profileLastService">N/A</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Educational Qualifications -->
                            <div>
                                <h4 class="h5 fw-bold text-primary text-uppercase border-bottom border-primary border-2 pb-2 mb-3">Educational Qualifications</h4>
                                <ul class="list-unstyled d-flex flex-column gap-3" id="profileEducation">
                                    <li class="p-3 bg-light rounded border-start border-success border-3">
                                        <div class="fw-bold text-dark mb-1">Bachelors of Technology(B.Tech)</div>
                                        <div class="small text-secondary">Maharaja Agrasen Institute of Technology</div>
                                        <div class="small text-secondary fst-italic mt-1">To year 2022</div>
                                    </li>
                                    <li class="p-3 bg-light rounded border-start border-success border-3">
                                        <div class="fw-bold text-dark mb-1">Senior Secondary(12th)</div>
                                        <div class="small text-secondary">CRPF Public School, Rohini</div>
                                        <div class="small text-secondary fst-italic mt-1">To year 2018</div>
                                    </li>
                                    <li class="p-3 bg-light rounded border-start border-success border-3">
                                        <div class="fw-bold text-dark mb-1">Higher Secondary(10th)</div>
                                        <div class="small text-secondary">Gitarattan Jindal Public School, Rohini</div>
                                        <div class="small text-secondary fst-italic mt-1">To year 2016</div>
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

    // Function to render all students
    function renderStudents(students, pagination, customMessage = null) {
        if (!students || students.length === 0) {
            const message = customMessage || 'Please adjust your filters to find students.';
            studentsContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-person-x display-1 text-secondary opacity-50"></i>
                    <h4 class="mt-3">No Students Found</h4>
                    <p class="text-secondary">${message}</p>
                </div>
            `;
            paginationContainer.classList.add('d-none');
            return;
        }

        // Update pagination info
        if (pagination) {
            currentPage = pagination.current_page;
            totalPages = pagination.total_pages;
            totalStudents = pagination.total;
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

            // Render hobbies
            const hobbiesList = clone.getElementById('profileHobbies');
            hobbiesList.innerHTML = '';
            if (profile.hobbies && profile.hobbies.length > 0) {
                profile.hobbies.forEach(hobby => {
                    const li = document.createElement('li');
                    li.className = 'p-2 bg-light rounded border-start border-primary border-3';
                    li.innerHTML = `<span class="small text-dark">${hobby}</span>`;
                    hobbiesList.appendChild(li);
                });
            } else {
                hobbiesList.innerHTML = '<li class="p-2 text-muted small">No hobbies listed</li>';
            }

            // Render education
            const educationList = clone.getElementById('profileEducation');
            educationList.innerHTML = '';
            if (profile.education && profile.education.length > 0) {
                profile.education.forEach(edu => {
                    const li = document.createElement('li');
                    li.className = 'p-3 bg-light rounded border-start border-success border-3';
                    li.innerHTML = `
                        <div class="fw-bold text-dark mb-1">${edu.degree}</div>
                        <div class="small text-secondary">${edu.institution}</div>
                        <div class="small text-secondary fst-italic mt-1">${edu.year}</div>
                    `;
                    educationList.appendChild(li);
                });
            } else {
                educationList.innerHTML = '<li class="p-3 text-muted small">No education details available</li>';
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
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-info-circle text-primary"></i>
                <span class="text-muted">Showing <strong class="text-dark">${pagination.from}</strong> to <strong class="text-dark">${pagination.to}</strong> of <strong class="text-dark">${pagination.total}</strong> students</span>
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
                    <i class="bi bi-exclamation-triangle display-1 text-danger opacity-50"></i>
                    <h4 class="mt-3">Error Loading Data</h4>
                    <p class="text-secondary">Please try again later.</p>
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
                if (data.data.tutorGroup !== undefined) document.getElementById('tutorGroup').textContent = data.data.tutorGroup;
                if (data.data.tutorName !== undefined) document.getElementById('tutorName').textContent = data.data.tutorName;
                if (data.data.houseName !== undefined) document.getElementById('houseName').textContent = data.data.houseName;
                if (data.data.houseTutors !== undefined) document.getElementById('houseTutors').textContent = data.data.houseTutors;
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
