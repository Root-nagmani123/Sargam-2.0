@extends('admin.layouts.master')

@section('title', "Who's Who - Sargam | Lal Bahadur Shastri National Academy of Administration")

@section('setup_content')

@push('styles')
<style>
    .whos-who-card {
        border: 1px solid #e2c9a8 !important;
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        transition: box-shadow 0.2s ease;
    }
    .whos-who-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }
    .whos-who-card .card-header {
        background: linear-gradient(135deg, #f5d4a8 0%, #e8b87a 100%);
        border-bottom: 2px solid #d4a574;
        padding: 1rem 1.5rem;
    }
    .whos-who-card .whos-who-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: #2c1810;
        letter-spacing: 0.01em;
    }
    .whos-who-card .whos-who-header-meta {
        font-size: 0.95rem;
        color: #3d2817;
    }
    .whos-who-card .whos-who-header-meta strong {
        font-weight: 600;
        color: #1a1008;
    }
    .whos-who-card .card-body {
        padding: 1.5rem 1.75rem;
        background: #fff;
    }
    .whos-who-card .whos-who-photo-wrap {
        flex-shrink: 0;
        width: 150px;
    }
    .whos-who-card .whos-who-photo {
        width: 150px;
        height: 180px;
        object-fit: cover;
        border: 3px solid #e8dcc8;
        border-radius: 0.375rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        background: #f8f9fa;
    }
    .whos-who-card .whos-who-index {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.25rem;
        height: 2.25rem;
        margin-top: 0.75rem;
        padding: 0 0.5rem;
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        background: #8b4513;
        border-radius: 50%;
    }
    .whos-who-card .whos-who-field {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 0.35rem 0.5rem;
        padding: 0.65rem 0;
        font-size: 1rem;
        line-height: 1.45;
        border-bottom: 1px solid #f0ebe3;
    }
    .whos-who-card .whos-who-field:last-child {
        border-bottom: none;
    }
    .whos-who-card .whos-who-field-label {
        flex: 0 0 auto;
        min-width: 8.5rem;
        font-weight: 600;
        color: #5c4a3a;
    }
    .whos-who-card .whos-who-field-value {
        flex: 1 1 auto;
        font-weight: 500;
        color: #1a1a2e;
        word-break: break-word;
    }
    .whos-who-card .whos-who-address {
        margin-top: 1.25rem;
        padding: 1rem 1.25rem;
        font-size: 1rem;
        line-height: 1.55;
        background: #faf8f5;
        border: 1px solid #ebe4d8;
        border-radius: 0.375rem;
    }
    .whos-who-card .whos-who-address strong {
        color: #5c4a3a;
        margin-right: 0.35rem;
    }
    @media (min-width: 992px) {
        .whos-who-card .whos-who-header-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem 1.5rem;
        }
        .whos-who-card .whos-who-header-row .whos-who-code {
            margin-left: auto;
        }
    }

    .whos-who-page-header {
        background: #fff;
        border-bottom: 2.5px solid #0b4a7e;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
    }
    .whos-who-page-header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .whos-who-page-header-left {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 0;
    }
    .whos-who-page-header-left img {
        width: 48px;
        height: 48px;
        object-fit: contain;
        flex-shrink: 0;
    }
    .whos-who-page-header .brand-1 {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #0b4a7e;
        font-weight: 600;
        line-height: 1.2;
    }
    .whos-who-page-header .brand-2 {
        font-size: 1.15rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #111;
        line-height: 1.2;
        margin-top: 2px;
    }
    .whos-who-page-header .brand-3 {
        font-size: 0.8rem;
        color: #555;
        line-height: 1.3;
        margin-top: 2px;
    }
    .whos-who-page-header-right img {
        width: 48px;
        height: 48px;
        object-fit: contain;
    }
</style>
@endpush

<div class="container-fluid py-4">
    <x-breadcrum title="Who's Who"></x-breadcrum>
    <x-session_message />

    <div class="whos-who-page-header">
        <div class="whos-who-page-header-inner">
            <div class="whos-who-page-header-left">
                <img src="{{ asset('admin_assets/images/logos/ashoka.png') }}" alt="Emblem of India">
                <div>
                    <div class="brand-1">Government of India</div>
                    <div class="brand-2">LBSNAA MUSSOORIE</div>
                    <div class="brand-3">Lal Bahadur Shastri National Academy of Administration</div>
                </div>
            </div>
            <div class="whos-who-page-header-right">
                <img src="{{ asset('admin_assets/images/logos/logo_new.png') }}" alt="LBSNAA Logo"
                     onerror="this.onerror=null;this.src='{{ asset('admin_assets/images/logos/logo.png') }}';">
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="nameFilter" class="form-label fw-semibold">Search</label>
                    <input type="text" class="form-control" id="nameFilter" placeholder="Name or OT code">
                </div>
                <div class="col-md-3">
                    <label for="courseFilter" class="form-label fw-semibold">Course Name</label>
                    <select class="form-select" id="courseFilter">
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
                        @foreach($cadres as $cadre)
                            <option value="{{ $cadre->pk }}">{{ $cadre->cadre_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="serviceFilter" class="form-label fw-semibold">Service</label>
                    <select class="form-select" id="serviceFilter">
                        <option value="">All Services</option>
                        @foreach($services as $service)
                            <option value="{{ $service->pk }}">{{ $service->service_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" id="resetFilters">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset
                    </button>
                    <button type="button" class="btn btn-primary flex-grow-1" id="downloadPdfBtn">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                    </button>
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
    <div class="card whos-who-card mb-4">
        <div class="card-header">
            <div class="whos-who-header-row whos-who-header-meta mb-2">
                <span class="whos-who-name" id="profileName">Aakash Garg</span>
                <span><strong>Rank:</strong> <span id="profileRank">22</span></span>
                <span><strong>Cadre:</strong> <span id="profileCadre">Uttarakhand</span></span>
                <span class="whos-who-code"><strong>Code:</strong> <span id="profileCode">A74</span></span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-3 whos-who-header-meta">
                <span><strong>Counsellor Name:</strong> <span id="profileCounsellor">N/A</span></span>
                <span><strong>House:</strong> <span id="profileHouse">N/A</span></span>
            </div>
        </div>

        <div class="card-body">
            <div class="d-flex flex-column flex-md-row gap-4 align-items-start">
                <div class="whos-who-photo-wrap text-center mx-auto mx-md-0">
                    <img src="" alt="Profile Image" class="whos-who-photo" id="profileImage">
                    <div class="whos-who-index" id="profileIndex">1</div>
                </div>

                <div class="flex-grow-1 w-100">
                    <div class="row g-0 g-lg-4">
                        <div class="col-md-6">
                            <div class="whos-who-field">
                                <span class="whos-who-field-label">Contact No:</span>
                                <span class="whos-who-field-value" id="profileContact">8800372932</span>
                            </div>
                            <div class="whos-who-field">
                                <span class="whos-who-field-label">Date of Birth:</span>
                                <span class="whos-who-field-value" id="profileDob">15-May-00</span>
                            </div>
                            <div class="whos-who-field">
                                <span class="whos-who-field-label">Domicile State:</span>
                                <span class="whos-who-field-value" id="profileDomicile">DELHI</span>
                            </div>
                            <div class="whos-who-field">
                                <span class="whos-who-field-label">Highest Stream:</span>
                                <span class="whos-who-field-value" id="profileStream">Arts</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="whos-who-field">
                                <span class="whos-who-field-label">Room No:</span>
                                <span class="whos-who-field-value" id="profileRoom">MN- 104</span>
                            </div>
                            <div class="whos-who-field">
                                <span class="whos-who-field-label">Email:</span>
                                <span class="whos-who-field-value" id="profileEmail">aakashgarg01@gmail.com</span>
                            </div>
                            <div class="whos-who-field">
                                <span class="whos-who-field-label">District:</span>
                                <span class="whos-who-field-value" id="profileDistrict">N/A</span>
                            </div>
                            <div class="whos-who-field">
                                <span class="whos-who-field-label">Category:</span>
                                <span class="whos-who-field-value" id="profileCategory">GENERAL</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="whos-who-address">
                <strong>Address:</strong><span id="profileAddress">N/A</span>
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
    const cadreFilter = document.getElementById('cadreFilter');
    const serviceFilter = document.getElementById('serviceFilter');
    const resetFilters = document.getElementById('resetFilters');

    let currentPage = 1;
    let perPage = 10;
    let totalPages = 1;
    let totalStudents = 0;
    let allProfiles = [];

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

            const serialNumber = pagination
                ? ((pagination.current_page - 1) * pagination.per_page) + index + 1
                : index + 1;

            clone.getElementById('profileName').textContent = profile.name;
            clone.getElementById('profileRank').textContent = profile.rank ?? 'N/A';
            clone.getElementById('profileCadre').textContent = profile.cadre ?? 'N/A';
            clone.getElementById('profileCode').textContent = profile.code ?? 'N/A';
            clone.getElementById('profileCounsellor').textContent = profile.counsellor ?? 'N/A';
            clone.getElementById('profileHouse').textContent = profile.house ?? 'N/A';
            clone.getElementById('profileImage').src = profile.image;
            clone.getElementById('profileImage').alt = profile.name;
            clone.getElementById('profileIndex').textContent = serialNumber;
            clone.getElementById('profileContact').textContent = profile.contact ?? 'N/A';
            clone.getElementById('profileDob').textContent = profile.dob ?? 'N/A';
            clone.getElementById('profileDomicile').textContent = profile.domicile ?? 'N/A';
            clone.getElementById('profileStream').textContent = profile.stream ?? 'N/A';
            clone.getElementById('profileRoom').textContent = profile.room ?? 'N/A';
            clone.getElementById('profileEmail').textContent = profile.email ?? 'N/A';
            clone.getElementById('profileDistrict').textContent = profile.district ?? 'N/A';
            clone.getElementById('profileCategory').textContent = profile.category ?? 'N/A';
            clone.getElementById('profileAddress').textContent = profile.address ?? 'N/A';

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

    // Function to fetch students from API
    async function fetchStudents(page = 1) {
        const name = nameFilter.value.trim();
        const courseId = courseFilter.value;
        const cadreId = cadreFilter.value;
        const serviceId = serviceFilter.value;

        // Show loading state
        loadingSpinner.classList.remove('d-none');
        loadingSpinner.classList.add('d-flex');
        studentsContainer.innerHTML = '';
        paginationContainer.classList.add('d-none');

        try {
            const params = new URLSearchParams();
            if (name) params.append('name', name);
            if (courseId) params.append('course_id', courseId);
            if (cadreId) params.append('cadre_id', cadreId);
            if (serviceId) params.append('service_id', serviceId);
            params.append('page', page);
            params.append('per_page', perPage);
            params.append('sort_by', 'name_asc');

            const apiUrl = '{{ route("admin.faculty.whos-who.students") }}?' + params.toString();
            
            const response = await fetch(apiUrl);
            const data = await response.json();
            

            if (data.success) {
                if (data.students && data.students.length > 0) {
                    allProfiles = data.students;
                    const courseName = courseFilter.options[courseFilter.selectedIndex]?.text || 'All Courses';
                    renderStudents(data.students, data.pagination);
                } else {
                    allProfiles = [];
                    const selectedCourse = courseFilter.options[courseFilter.selectedIndex]?.text || 'selected course';
                    renderStudents([], null, courseId ? `No students found for "${selectedCourse}"` : 'No students found');
                }
            } else {
                allProfiles = [];
                renderStudents([], null, data.message || 'Error loading student data');
            }
        } catch (error) {
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

    function resetAllFilters() {
        nameFilter.value = '';
        courseFilter.value = '';
        cadreFilter.value = '';
        serviceFilter.value = '';
        currentPage = 1;
        filterProfiles(1);
    }

    // Function to load courses dynamically (can be replaced with API call)
    // Function to load courses dynamically from API (only if not already loaded)
    async function loadCourses() {
        try {
            const courseSelect = document.getElementById('courseFilter');
            
            // Check if courses are already loaded (more than just "All Courses")
            if (courseSelect.options.length > 1) {
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
                
            }
            return Promise.resolve();
        } catch (error) {
            return Promise.reject(error);
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
    
    courseFilter.addEventListener('change', function() {
        currentPage = 1;
        filterProfiles(1);
    });

    cadreFilter.addEventListener('change', function() {
        currentPage = 1;
        filterProfiles(1);
    });

    serviceFilter.addEventListener('change', function() {
        currentPage = 1;
        filterProfiles(1);
    });

    resetFilters.addEventListener('click', resetAllFilters);

    document.getElementById('downloadPdfBtn').addEventListener('click', function() {
        const params = new URLSearchParams();
        const name = nameFilter.value.trim();
        if (name) params.append('name', name);
        if (courseFilter.value) params.append('course_id', courseFilter.value);
        if (cadreFilter.value) params.append('cadre_id', cadreFilter.value);
        if (serviceFilter.value) params.append('service_id', serviceFilter.value);
        window.open('{{ route("admin.faculty.whos-who.download-pdf") }}?' + params.toString(), '_blank');
    });

    // Initial load - courses are already loaded from backend, just fetch students
    // Only reload courses if dropdown is empty
    if (courseFilter.options.length <= 1) {
        loadCourses().then(() => {
            filterProfiles(1);
        }).catch(() => {
            filterProfiles(1);
        });
    } else {
        filterProfiles(1);
    }
});
</script>
@endpush
