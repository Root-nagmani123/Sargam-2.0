@extends('admin.layouts.master')

@section('title', "Who's Who")

@push('styles')
<link rel="stylesheet" href="{{ asset('css/faculty-whos-who-admin.css') }}?v={{ @filemtime(public_path('css/faculty-whos-who-admin.css')) ?: time() }}">
@endpush

@section('setup_content')

<div class="container-fluid whos-who-page py-4">
    <x-breadcrum title="Who's Who"></x-breadcrum>
    {{-- Toolbar: Download + View Toggle --}}
            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3 mb-md-4 whos-who-toolbar">
                <button type="button" class="whos-who-btn-outline" id="downloadBtn" aria-label="Download student data">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
                <div class="btn-group whos-who-view-toggle" role="group" aria-label="View mode">
                    <button type="button" class="btn active" id="viewListBtn" data-view="list" title="List view" aria-label="List view" aria-pressed="true">
                        <i class="bi bi-list-ul" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="btn" id="viewCardBtn" data-view="card" title="Card view" aria-label="Card view" aria-pressed="false">
                        <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
    <div class="card whos-who-card shadow-sm border-0">
        <div class="card-body p-3 p-md-4">

            {{-- Filter Section --}}
            <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 mb-3 mb-md-4">
                <span class="whos-who-filters-label me-1">Filters</span>

                <label for="courseFilter" class="visually-hidden">Course Name</label>
                <select class="form-select whos-who-filter-select" id="courseFilter" aria-label="Filter by course">
                    <option value="">Course</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                    @endforeach
                </select>

                <label for="categoryFilter" class="visually-hidden">Category</label>
                <select class="form-select whos-who-filter-select" id="categoryFilter" aria-label="Filter by category">
                    <option value="">Category</option>
                    <option value="ABCD">ABCD</option>
                    <option value="EFGH">EFGH</option>
                    <option value="IJKL">IJKL</option>
                </select>

                <label for="statusFilter" class="visually-hidden">Status</label>
                <select class="form-select whos-who-filter-select" id="statusFilter" aria-label="Filter by status">
                    <option value="">Status</option>
                    <option value="ABCD">ABCD</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>

                <label for="sortBy" class="visually-hidden">Sort By</label>
                <select class="form-select whos-who-filter-select whos-who-sort-select" id="sortBy" aria-label="Sort by">
                    <option value="name_asc">Name (A-Z)</option>
                    <option value="name_desc">Name (Z-A)</option>
                    <option value="roll_asc">Roll Number (Low to High)</option>
                    <option value="roll_desc">Roll Number (High to Low)</option>
                    <option value="service_asc">Service (A-Z)</option>
                    <option value="service_desc">Service (Z-A)</option>
                    <option value="course_asc">Course (A-Z)</option>
                    <option value="course_desc">Course (Z-A)</option>
                </select>

                <button type="button" class="btn whos-who-reset-btn" id="resetFilters">
                    Reset Filters
                </button>

                <div class="whos-who-search-wrap ms-md-auto" id="searchWrap">
                    <label for="nameFilter" class="visually-hidden">Search by name</label>
                    <input type="text" class="form-control whos-who-search-input" id="nameFilter" placeholder="Search name..." autocomplete="off">
                    <button type="button" class="btn whos-who-search-btn" id="searchBtn" aria-label="Search by name">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            {{-- Per Page (hidden in filters, shown in pagination footer) --}}
            <label for="perPage" class="visually-hidden">Items per page</label>
            <select class="d-none" id="perPage" aria-hidden="true">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="200">200</option>
            </select>

            {{-- Loading Spinner --}}
            <div class="d-flex justify-content-center align-items-center py-5 d-none" id="loadingSpinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            {{-- Students List Container --}}
            <div id="studentsContainer">
                <!-- Students will be dynamically loaded here -->
            </div>

            {{-- Pagination Container --}}
            <div id="paginationContainer" class="d-none mt-4 whos-who-pagination">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 pt-3 border-top">
                    <nav aria-label="Students pagination">
                        <ul class="pagination mb-0" id="paginationList">
                            <!-- Pagination will be dynamically generated here -->
                        </ul>
                    </nav>
                    <div id="paginationInfo" class="d-flex align-items-center gap-2 text-muted small">
                        <!-- Pagination info will be added here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Template (Hidden) -->
<template id="profileTemplate">
    <div class="col-12 col-lg-6 mb-3 mb-lg-4">
        <div class="card whos-who-profile-card shadow-sm border-0 h-100">
            <div class="card-body p-3 p-md-4">
                {{-- Card Header --}}
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-2 mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <span class="whos-who-avatar-slot">
                            <img src="" alt="Profile Image" class="whos-who-avatar" id="profileImage">
                            <span class="whos-who-avatar-fallback whos-who-avatar-fallback--md d-none" id="profileImageFallback" aria-hidden="true"></span>
                        </span>
                        <div>
                            <div class="whos-who-profile-name" id="profileName">Aakash Garg</div>
                            <div class="whos-who-profile-id">
                                <span id="profileIdBadge">O30</span>
                                <span class="d-none" id="profileRoll">Roll 5</span>
                            </div>
                        </div>
                    </div>
                    <span class="whos-who-service-badge" id="profileService">INDIAN ADMINISTRATIVE SERVICE</span>
                </div>

                {{-- Card Body: 4 Quadrants --}}
                <div class="row g-3 g-md-4">
                    <div class="col-sm-6">
                        <div class="whos-who-section-title">Course Information</div>
                        <div class="whos-who-field-label">Course Name</div>
                        <div class="whos-who-field-value" id="profileCourseName">FC-100</div>
                        <div class="whos-who-field-label">Course Code</div>
                        <div class="whos-who-field-value" id="profileCourseCode">FC-100</div>
                        <div class="whos-who-field-label">Batch</div>
                        <div class="whos-who-field-value mb-0" id="profileBatch">2024-2025</div>
                    </div>

                    <div class="col-sm-6">
                        <div class="whos-who-section-title">Contact Information</div>
                        <div class="whos-who-field-label">Email Address</div>
                        <div class="whos-who-field-value" id="profileEmail">aakashgarg01@gmail.com</div>
                        <div class="whos-who-field-label">Contact Number</div>
                        <div class="whos-who-field-value" id="profileContact">8800372932</div>
                        <div class="whos-who-field-label">Last Service</div>
                        <div class="whos-who-field-value mb-0" id="profileLastService">N/A</div>
                    </div>

                    <div class="col-sm-6">
                        <div class="whos-who-section-title">Basic Information</div>
                        <div class="row g-0">
                            <div class="col-6 pe-2">
                                <div class="whos-who-field-label">Date of Birth</div>
                                <div class="whos-who-field-value" id="profileDob">9/22/2000</div>
                            </div>
                            <div class="col-6 ps-2">
                                <div class="whos-who-field-label">Domicile State</div>
                                <div class="whos-who-field-value" id="profileDomicile">DELHI</div>
                            </div>
                            <div class="col-6 pe-2">
                                <div class="whos-who-field-label">No. of Attempts</div>
                                <div class="whos-who-field-value" id="profileAttempts">2</div>
                            </div>
                            <div class="col-6 ps-2">
                                <div class="whos-who-field-label">Highest Stream</div>
                                <div class="whos-who-field-value" id="profileStream">Engineering</div>
                            </div>
                            <div class="col-12">
                                <div class="whos-who-field-label">Room No.</div>
                                <div class="whos-who-field-value mb-0" id="profileRoom">SW-309</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="whos-who-section-title">Hobbies and Interests</div>
                        <div class="whos-who-field-label">Hobbies</div>
                        <ul class="list-unstyled mb-0" id="profileHobbies">
                            <li class="whos-who-hobby-item">Formula 1 Racing</li>
                        </ul>
                    </div>
                </div>

                {{-- Educational Qualifications (preserved) --}}
                <div class="mt-3 pt-3 border-top">
                    <div class="whos-who-section-title">Educational Qualifications</div>
                    <ul class="list-unstyled mb-0" id="profileEducation">
                        <li class="whos-who-edu-item">
                            <div class="fw-semibold text-dark mb-1">Bachelors of Technology(B.Tech)</div>
                            <div class="small text-secondary">Maharaja Agrasen Institute of Technology</div>
                            <div class="small text-secondary fst-italic mt-1">To year 2022</div>
                        </li>
                    </ul>
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
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const sortBy = document.getElementById('sortBy');
    const perPageSelect = document.getElementById('perPage');
    const resetFilters = document.getElementById('resetFilters');
    const viewListBtn = document.getElementById('viewListBtn');
    const viewCardBtn = document.getElementById('viewCardBtn');
    const downloadBtn = document.getElementById('downloadBtn');
    const searchBtn = document.getElementById('searchBtn');
    const searchWrap = document.getElementById('searchWrap');

    let currentPage = 1;
    let perPage = 10;
    let totalPages = 1;
    let totalStudents = 0;
    let allProfiles = [];
    let currentSort = 'name_asc';
    let currentView = 'list';
    let lastPagination = null;

    // View toggle
    function setViewMode(mode) {
        currentView = mode;
        const isList = mode === 'list';
        viewListBtn.classList.toggle('active', isList);
        viewCardBtn.classList.toggle('active', !isList);
        viewListBtn.setAttribute('aria-pressed', isList ? 'true' : 'false');
        viewCardBtn.setAttribute('aria-pressed', !isList ? 'true' : 'false');

        if (allProfiles.length > 0) {
            renderStudents(allProfiles, lastPagination);
        }
    }

    viewListBtn.addEventListener('click', () => setViewMode('list'));
    viewCardBtn.addEventListener('click', () => setViewMode('card'));

    // Search toggle
    searchBtn.addEventListener('click', function() {
        searchWrap.classList.toggle('is-open');
        if (searchWrap.classList.contains('is-open')) {
            nameFilter.focus();
        } else if (nameFilter.value.trim()) {
            filterProfiles(1);
        }
    });

    // Download CSV (client-side, no backend change)
    downloadBtn.addEventListener('click', function() {
        if (!allProfiles.length) {
            return;
        }
        const headers = ['S.No.', 'Name', 'ID', 'Roll Number', 'Category', 'Course Code', 'Course Name', 'Batch', 'Email', 'Contact Number', 'Last Service', 'Date of Birth', 'Domicile State', 'No. of Attempts', 'Highest Stream', 'Room No.', 'Hobbies'];
        const rows = allProfiles.map((p, i) => [
            i + 1,
            p.name,
            p.id,
            p.roll,
            p.service,
            p.courseCode,
            p.courseName,
            p.batch,
            p.email,
            p.contact,
            p.lastService,
            p.dob,
            p.domicile,
            p.attempts,
            p.stream,
            p.room,
            formatHobbiesDisplay(p.hobbies)
        ]);
        const csv = [headers, ...rows]
            .map(row => row.map(cell => `"${String(cell ?? '').replace(/"/g, '""')}"`).join(','))
            .join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'whos-who-students.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function getRollDisplay(roll) {
        if (!roll) return 'N/A';
        return String(roll).replace(/^Roll\s+/i, '');
    }

    function getFirstNameInitial(name) {
        if (!name || !String(name).trim()) return '?';
        const firstName = String(name).trim().split(/\s+/)[0];
        return (firstName.charAt(0) || '?').toUpperCase();
    }

    function isMissingOrInvalidImage(url) {
        if (!url || !String(url).trim()) return true;
        const normalized = String(url).toLowerCase();
        return normalized.includes('via.placeholder.com')
            || normalized.includes('placeholder.com')
            || normalized.includes('placehold.it');
    }

    function bindAvatarFallback(imgEl, fallbackEl, name, imageUrl) {
        if (!imgEl || !fallbackEl) return;

        const initial = getFirstNameInitial(name);
        fallbackEl.textContent = initial;

        const showFallback = () => {
            imgEl.classList.add('d-none');
            fallbackEl.classList.remove('d-none');
        };

        imgEl.addEventListener('error', showFallback, { once: true });

        if (isMissingOrInvalidImage(imageUrl)) {
            showFallback();
            return;
        }

        imgEl.classList.remove('d-none');
        fallbackEl.classList.add('d-none');
        imgEl.src = imageUrl;
    }

    function buildTableAvatarHtml(profile) {
        const initial = escapeHtml(getFirstNameInitial(profile.name));
        const name = escapeHtml(profile.name);
        const imageUrl = profile.image || '';

        if (isMissingOrInvalidImage(imageUrl)) {
            return `<span class="whos-who-avatar-fallback whos-who-avatar-fallback--sm" aria-hidden="true">${initial}</span>`;
        }

        return `
            <span class="whos-who-avatar-slot">
                <img src="${escapeHtml(imageUrl)}" alt="${name}" class="whos-who-table-avatar" loading="lazy">
                <span class="whos-who-avatar-fallback whos-who-avatar-fallback--sm d-none" aria-hidden="true">${initial}</span>
            </span>
        `;
    }

    function bindTableAvatars() {
        studentsContainer.querySelectorAll('.whos-who-avatar-slot').forEach(slot => {
            const imgEl = slot.querySelector('img');
            const fallbackEl = slot.querySelector('.whos-who-avatar-fallback');
            if (!imgEl || !fallbackEl) return;

            imgEl.addEventListener('error', () => {
                imgEl.classList.add('d-none');
                fallbackEl.classList.remove('d-none');
            }, { once: true });
        });
    }

    function formatHobbiesDisplay(hobbies) {
        if (!hobbies || !hobbies.length) return 'N/A';
        return hobbies.join(', ');
    }

    function formatCellValue(value) {
        if (value === null || value === undefined || String(value).trim() === '') return 'N/A';
        return String(value);
    }

    function renderTableView(students, pagination) {
        const startIndex = pagination ? pagination.from : 1;
        let rows = '';

        students.forEach((profile, index) => {
            rows += `
                <tr>
                    <td class="whos-who-table-sn">${startIndex + index}</td>
                    <td class="whos-who-table-name-col">
                        <div class="d-flex align-items-center gap-2">
                            ${buildTableAvatarHtml(profile)}
                            <span class="whos-who-table-name">${escapeHtml(profile.name)}</span>
                        </div>
                    </td>
                    <td>${escapeHtml(getRollDisplay(profile.roll))}</td>
                    <td>${escapeHtml(formatCellValue(profile.service))}</td>
                    <td>${escapeHtml(formatCellValue(profile.courseCode))}</td>
                    <td class="whos-who-table-wrap-text">${escapeHtml(formatCellValue(profile.courseName))}</td>
                    <td>${escapeHtml(formatCellValue(profile.batch))}</td>
                    <td class="whos-who-table-wrap-text">${escapeHtml(formatCellValue(profile.email))}</td>
                    <td>${escapeHtml(formatCellValue(profile.contact))}</td>
                    <td>${escapeHtml(formatCellValue(profile.lastService))}</td>
                    <td>${escapeHtml(formatCellValue(profile.dob))}</td>
                    <td>${escapeHtml(formatCellValue(profile.domicile))}</td>
                    <td>${escapeHtml(formatCellValue(profile.attempts))}</td>
                    <td>${escapeHtml(formatCellValue(profile.stream))}</td>
                    <td>${escapeHtml(formatCellValue(profile.room))}</td>
                    <td class="whos-who-table-wrap-text">${escapeHtml(formatHobbiesDisplay(profile.hobbies))}</td>
                </tr>
            `;
        });

        studentsContainer.innerHTML = `
            <div class="whos-who-table-outer">
                <div class="whos-who-table-scroll">
                    <table class="table whos-who-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Name</th>
                                <th scope="col">Roll Number</th>
                                <th scope="col">Category</th>
                                <th scope="col">Course Code</th>
                                <th scope="col">Course Name</th>
                                <th scope="col">Batch</th>
                                <th scope="col">Email</th>
                                <th scope="col">Contact Number</th>
                                <th scope="col">Last Service</th>
                                <th scope="col">Date of Birth</th>
                                <th scope="col">Domicile State</th>
                                <th scope="col">No. of Attempts</th>
                                <th scope="col">Highest Stream</th>
                                <th scope="col">Room No.</th>
                                <th scope="col">Hobbies</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        `;

        bindTableAvatars();
    }

    function renderCardView(students) {
        const row = document.createElement('div');
        row.className = 'row';

        students.forEach((profile) => {
            const template = document.getElementById('profileTemplate');
            const clone = template.content.cloneNode(true);

            clone.getElementById('profileName').textContent = profile.name;
            clone.getElementById('profileRoll').textContent = profile.roll;
            clone.getElementById('profileService').textContent = profile.service;
            bindAvatarFallback(
                clone.getElementById('profileImage'),
                clone.getElementById('profileImageFallback'),
                profile.name,
                profile.image
            );
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

            const hobbiesList = clone.getElementById('profileHobbies');
            hobbiesList.innerHTML = '';
            if (profile.hobbies && profile.hobbies.length > 0) {
                profile.hobbies.forEach(hobby => {
                    const li = document.createElement('li');
                    li.className = 'whos-who-hobby-item';
                    li.textContent = hobby;
                    hobbiesList.appendChild(li);
                });
            } else {
                hobbiesList.innerHTML = '<li class="whos-who-hobby-item text-muted">No hobbies listed</li>';
            }

            const educationList = clone.getElementById('profileEducation');
            educationList.innerHTML = '';
            if (profile.education && profile.education.length > 0) {
                profile.education.forEach(edu => {
                    const li = document.createElement('li');
                    li.className = 'whos-who-edu-item';
                    li.innerHTML = `
                        <div class="fw-semibold text-dark mb-1">${escapeHtml(edu.degree)}</div>
                        <div class="small text-secondary">${escapeHtml(edu.institution)}</div>
                        <div class="small text-secondary fst-italic mt-1">${escapeHtml(edu.year)}</div>
                    `;
                    educationList.appendChild(li);
                });
            } else {
                educationList.innerHTML = '<li class="whos-who-edu-item text-muted small">No education details available</li>';
            }

            row.appendChild(clone);
        });

        studentsContainer.innerHTML = '';
        studentsContainer.appendChild(row);
    }

    // Function to render all students
    function renderStudents(students, pagination, customMessage = null) {
        if (!students || students.length === 0) {
            const message = customMessage || 'Please adjust your filters to find students.';
            studentsContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-person-x display-1 text-secondary opacity-50"></i>
                    <h4 class="mt-3 fw-semibold">No Students Found</h4>
                    <p class="text-secondary mb-0">${escapeHtml(message)}</p>
                </div>
            `;
            paginationContainer.classList.add('d-none');
            return;
        }

        if (pagination) {
            currentPage = pagination.current_page;
            totalPages = pagination.total_pages;
            totalStudents = pagination.total;
            lastPagination = pagination;
        }

        if (currentView === 'list') {
            renderTableView(students, pagination);
        } else {
            renderCardView(students);
        }

        renderPagination(pagination);
    }

    // Function to render pagination
    function renderPagination(pagination) {
        if (!pagination) {
            paginationContainer.classList.add('d-none');
            return;
        }

        paginationContainer.classList.remove('d-none');
        paginationList.innerHTML = '';
        paginationInfo.innerHTML = '';

        const page = pagination.current_page;
        const pages = pagination.total_pages;

        if (pages <= 1) {
            paginationList.innerHTML = '';
        } else {
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${page === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `
                <a class="page-link" href="#" data-page="${page - 1}" ${page === 1 ? 'tabindex="-1" aria-disabled="true"' : ''} aria-label="Previous page">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </a>
            `;
            paginationList.appendChild(prevLi);

            const maxVisible = 7;
            let startPage = Math.max(1, page - Math.floor(maxVisible / 2));
            let endPage = Math.min(pages, startPage + maxVisible - 1);

            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            if (startPage > 1) {
                const firstLi = document.createElement('li');
                firstLi.className = 'page-item';
                firstLi.innerHTML = `<a class="page-link" href="#" data-page="1">1</a>`;
                paginationList.appendChild(firstLi);
                if (startPage > 2) {
                    const ellipsisLi = document.createElement('li');
                    ellipsisLi.className = 'page-item disabled';
                    ellipsisLi.innerHTML = `<span class="page-link">…</span>`;
                    paginationList.appendChild(ellipsisLi);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const pageLi = document.createElement('li');
                pageLi.className = `page-item ${i === page ? 'active' : ''}`;
                if (i === page) {
                    pageLi.setAttribute('aria-current', 'page');
                    pageLi.innerHTML = `<span class="page-link">${i}</span>`;
                } else {
                    pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
                }
                paginationList.appendChild(pageLi);
            }

            if (endPage < pages) {
                if (endPage < pages - 1) {
                    const ellipsisLi = document.createElement('li');
                    ellipsisLi.className = 'page-item disabled';
                    ellipsisLi.innerHTML = `<span class="page-link">…</span>`;
                    paginationList.appendChild(ellipsisLi);
                }
                const lastLi = document.createElement('li');
                lastLi.className = 'page-item';
                lastLi.innerHTML = `<a class="page-link" href="#" data-page="${pages}">${pages}</a>`;
                paginationList.appendChild(lastLi);
            }

            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${page === pages ? 'disabled' : ''}`;
            nextLi.innerHTML = `
                <a class="page-link" href="#" data-page="${page + 1}" ${page === pages ? 'tabindex="-1" aria-disabled="true"' : ''} aria-label="Next page">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
            `;
            paginationList.appendChild(nextLi);

            paginationList.querySelectorAll('a[data-page]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetPage = parseInt(this.getAttribute('data-page'));
                    if (targetPage >= 1 && targetPage <= pages && targetPage !== page) {
                        filterProfiles(targetPage);
                    }
                });
            });
        }

        const perPageOptions = [5, 10, 25, 50, 100, 200];
        let perPageOptionsHtml = '';
        perPageOptions.forEach(val => {
            perPageOptionsHtml += `<option value="${val}" ${parseInt(perPageSelect.value) === val ? 'selected' : ''}>${val}</option>`;
        });

        paginationInfo.innerHTML = `
            <span>Showing</span>
            <select class="form-select form-select-sm whos-who-per-page-select" id="perPageFooter" aria-label="Items per page">
                ${perPageOptionsHtml}
            </select>
            <span>of <strong class="text-dark">${pagination.total}</strong> items</span>
        `;

        const perPageFooter = document.getElementById('perPageFooter');
        if (perPageFooter) {
            perPageFooter.addEventListener('change', function() {
                perPageSelect.value = this.value;
                handlePerPageChange();
            });
        }
    }

    // Function to fetch students from API
    async function fetchStudents(page = 1) {
        const name = nameFilter.value.trim();
        const courseId = courseFilter.value;
        const category = categoryFilter.value;
        const status = statusFilter.value;
        const sortValue = sortBy.value || 'name_asc';

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
                    <h4 class="mt-3 fw-semibold">Error Loading Data</h4>
                    <p class="text-secondary mb-0">Please try again later.</p>
                    <p class="text-danger small mt-2">${escapeHtml(error.message)}</p>
                </div>
            `;
        } finally {
            loadingSpinner.classList.add('d-none');
            loadingSpinner.classList.remove('d-flex');
        }
    }

    function filterProfiles(page = 1) {
        currentPage = page;
        fetchStudents(page);
    }

    function handlePerPageChange() {
        perPage = parseInt(perPageSelect.value);
        currentPage = 1;
        filterProfiles(1);
    }

    function handleSortChange() {
        currentSort = sortBy.value;
        currentPage = 1;
        filterProfiles(1);
    }

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
        searchWrap.classList.remove('is-open');
        filterProfiles(1);
    }

    async function loadCourses() {
        try {
            const courseSelect = document.getElementById('courseFilter');

            if (courseSelect.options.length > 1) {
                console.log('Courses already loaded, skipping reload');
                return Promise.resolve();
            }

            const response = await fetch('{{ route("admin.faculty.whos-who.courses") }}');
            const data = await response.json();

            if (data.success && data.courses) {
                const currentValue = courseSelect.value;

                courseSelect.innerHTML = '';

                const allOption = document.createElement('option');
                allOption.value = '';
                allOption.textContent = 'Course';
                courseSelect.appendChild(allOption);

                data.courses.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.pk;
                    option.textContent = course.course_name;
                    courseSelect.appendChild(option);
                });

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

    let nameInputTimeout;
    function debounceNameInput() {
        clearTimeout(nameInputTimeout);
        nameInputTimeout = setTimeout(() => {
            filterProfiles();
        }, 500);
    }

    nameFilter.addEventListener('input', debounceNameInput);

    nameFilter.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            filterProfiles(1);
        }
    });

    courseFilter.addEventListener('change', function() {
        const selectedCourseId = courseFilter.value;
        const selectedCourseName = courseFilter.options[courseFilter.selectedIndex]?.text || '';
        console.log('Course changed:', selectedCourseId, selectedCourseName);
        currentPage = 1;
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

    sortBy.addEventListener('change', handleSortChange);
    perPageSelect.addEventListener('change', handlePerPageChange);

    resetFilters.addEventListener('click', resetAllFilters);

    if (courseFilter.options.length <= 1) {
        loadCourses().then(() => {
            filterProfiles(1);
            loadStaticInfo();
        }).catch(() => {
            filterProfiles(1);
            loadStaticInfo();
        });
    } else {
        filterProfiles(1);
        loadStaticInfo();
    }
});
</script>
@endpush
