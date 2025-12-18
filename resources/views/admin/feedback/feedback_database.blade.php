@extends('admin.layouts.master')

@section('title', 'Feedback Database - Sargam | Lal Bahadur')

@section('setup_content')
<style>

.export-btn-group {
    min-width: 130px;
}

.loading-overlay {
    display: none;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: auto;
    height: auto;
    background: transparent;
    z-index: 10;
}

#feedbackTableBody {
    min-height: 300px;
}



.loading-spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid var(--lbsnaa-blue);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

.table-row-hover:hover {
    background-color: #f8f9fa;
    transition: background-color 0.2s;
}

.percentage-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.percentage-excellent {
    background-color: #d1f7c4;
    color: #0d4629;
}

.percentage-good {
    background-color: #ffeab6;
    color: #663c00;
}

.percentage-average {
    background-color: #ffd8d8;
    color: #7c0a02;
}

/* Filter dropdown styling */
.dynamic-filter-container {
    transition: all 0.3s ease;
}

.dynamic-filter-container.hidden {
    opacity: 0;
    height: 0;
    overflow: hidden;
    margin: 0;
    padding: 0;
}

.dynamic-filter-container.visible {
    opacity: 1;
    height: auto;
}
</style>

<div class="container-fluid">
    <x-breadcrum title="Feedback Database"></x-breadcrum>


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
    </div>
</div>

<!-- Comments Modal -->
<div class="modal fade" id="commentsModal" tabindex="-1" aria-labelledby="commentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commentsModalLabel">Feedback Comments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="commentsContent"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css"></script>

<script>
$(document).ready(function() {
    // Prevent duplicate execution
    if (window.feedbackPageLoaded) {
        console.log('Script already loaded, skipping');
        return;
    }
    window.feedbackPageLoaded = true;

    console.log('=== FEEDBACK PAGE INITIALIZATION ===');

    let currentPage = 1;
    let perPage = 10;
    let totalRecords = 0;
    let currentFilters = {
        course_id: '',
        search_param: 'all',
        faculty_id: '',
        topic_value: ''
    };
    let debounceTimer;

    // Check if required elements exist
    if (!checkRequiredElements()) {
        console.error('Required elements not found');
        return;
    }

    // Initialize
    initializeEventListeners();
    autoSelectFirstCourse();

    function checkRequiredElements() {
        const requiredElements = [
            '#courseSelect',
            '#searchParam',
            '#feedbackTableBody',
            '#loadingOverlay'
        ];

        for (const selector of requiredElements) {
            if (!$(selector).length) {
                console.error(`Required element not found: ${selector}`);
                return false;
            }
        }
        return true;
    }

    function autoSelectFirstCourse() {
        const courseSelect = $('#courseSelect');
        if (!courseSelect.length) return;

        const firstCourseOption = courseSelect.find('option:not(:first):not([disabled])').first();

        if (firstCourseOption.length > 0) {
            const courseId = firstCourseOption.val();
            const courseName = firstCourseOption.text();

            courseSelect.val(courseId);
            currentFilters.course_id = courseId;

            $('#feedbackTableBody').html(`
                <tr>
                    <td colspan="10" class="text-center text-muted py-3">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        Loading feedback data for "${courseName}"...
                    </td>
                </tr>
            `);

            loadFeedbackData();
        } else {
            showInitialMessage();
        }
    }

    function initializeEventListeners() {
        // Safely bind events only if elements exist
        safeBind('#courseSelect', 'change', function(e) {
            e.preventDefault();
            const courseId = $(this).val();
            if (courseId) {
                currentFilters.course_id = courseId;
                currentPage = 1;
                loadFeedbackData();
            } else {
                showInitialMessage();
            }
        });

        safeBind('#searchParam', 'change', function(e) {
            e.preventDefault();
            const searchParam = $(this).val();
            currentFilters.search_param = searchParam;

            $('.dynamic-filter-container').addClass('d-none').removeClass('d-block');

            if (searchParam === 'faculty') {
                showElement('#facultyFilterContainer');
                currentFilters.faculty_id = $('#facultyFilter').val();
                currentFilters.topic_value = '';
                $('#topicFilter').val('');
            } else if (searchParam === 'topic') {
                showElement('#topicFilterContainer');
                currentFilters.topic_value = $('#topicFilter').val();
                currentFilters.faculty_id = '';
                $('#facultyFilter').val('');
            } else {
                currentFilters.faculty_id = '';
                currentFilters.topic_value = '';
                $('#facultyFilter').val('');
                $('#topicFilter').val('');
            }

            if (currentFilters.course_id) {
                currentPage = 1;
                loadFeedbackData();
            }
        });

        safeBind('#facultyFilter', 'change', function(e) {
            e.preventDefault();
            currentFilters.faculty_id = $(this).val();
            if (currentFilters.course_id) {
                currentPage = 1;
                loadFeedbackData();
            }
        });

        safeBind('#topicFilter', 'input', function(e) {
            e.preventDefault();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                currentFilters.topic_value = $(this).val();
                if (currentFilters.course_id && currentFilters.topic_value.length >= 2) {
                    currentPage = 1;
                    loadFeedbackData();
                }
            }, 500);
        });

        safeBind('#clearTopicBtn', 'click', function(e) {
            e.preventDefault();
            $('#topicFilter').val('');
            currentFilters.topic_value = '';
            if (currentFilters.course_id) {
                currentPage = 1;
                loadFeedbackData();
            }
        });

        safeBind('#clearFiltersBtn', 'click', function(e) {
            e.preventDefault();
            clearAllFilters();
        });

        safeBind('#perPageSelect', 'change', function(e) {
            e.preventDefault();
            perPage = $(this).val();
            currentPage = 1;
            if (currentFilters.course_id) {
                loadFeedbackData();
            }
        });

        safeBind('#tableSearch', 'keyup', function(e) {
            e.preventDefault();
            const searchText = $(this).val().toLowerCase();
            $('#feedbackTableBody tr').each(function() {
                const rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.includes(searchText));
            });
        });

        safeBind('#exportExcelBtn', 'click', function(e) {
            e.preventDefault();
            exportData('excel');
        });

        safeBind('#exportPdfBtn', 'click', function(e) {
            e.preventDefault();
            exportData('pdf');
        });
    }

    // Helper function to safely bind events
    function safeBind(selector, event, handler) {
        const element = $(selector);
        if (element.length) {
            element.off(event).on(event, handler);
        } else {
            console.warn(`Element not found for binding: ${selector}`);
        }
    }

    // Helper function to safely show elements
    function showElement(selector) {
        const element = $(selector);
        if (element.length) {
            element.removeClass('d-none').addClass('d-block');
        }
    }

    function clearAllFilters() {
        $('#courseSelect').val('');
        $('#searchParam').val('all');
        $('#facultyFilter').val('');
        $('#topicFilter').val('');

        $('.dynamic-filter-container').addClass('d-none').removeClass('d-block');

        currentFilters = {
            course_id: '',
            search_param: 'all',
            faculty_id: '',
            topic_value: ''
        };
        currentPage = 1;

        showInitialMessage();
    }

    function showInitialMessage() {
        const hasCourses = $('#courseSelect option').length > 1;

        if (hasCourses) {
            $('#feedbackTableBody').html(`
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
                        <i class="bi bi-database me-2"></i>
                        Select a program to view feedback data
                    </td>
                </tr>
            `);
        } else {
            $('#feedbackTableBody').html(`
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        No programs available. Please add courses first.
                    </td>
                </tr>
            `);
        }
        $('#paginationSection').hide();
    }

    function loadFeedbackData() {
        if (!currentFilters.course_id) {
            showInitialMessage();
            return;
        }

        showLoading(true);

        const params = new URLSearchParams({
            ...currentFilters,
            page: currentPage,
            per_page: perPage
        });

        const apiUrl = `/faculty/database/data?${params.toString()}`;

        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderTable(data.data);
                    updatePagination(data);
                } else {
                    showErrorMessage(data.error || 'Error loading data');
                }
                showLoading(false);
            })
            .catch(error => {
                console.error('Error loading feedback data:', error);
                showErrorMessage('Error loading data. Please try again.');
                showLoading(false);
            });
    }

    function renderTable(data) {
        const tbody = $('#feedbackTableBody');
        if (!tbody.length) return;

        tbody.empty();

        if (!data || data.length === 0) {
            showNoDataMessage();
            $('#paginationSection').hide();
            return;
        }

        data.forEach((item, index) => {
            const row = `
                <tr class="table-row-hover">
                    <td class="text-center">${((currentPage - 1) * perPage) + index + 1}</td>
                    <td>
                                    <a href="javascript:void(0)" class="link-primary faculty-link" 
   data-faculty-id="${item.faculty_enc_id || ''}"
   title="View faculty details">
    ${item.faculty_name}
</a>
                    </td>
                    <td>${item.course_name}</td>
                    <td>
                        <small>
                            ${item.faculty_address || 'N/A'}
                            ${item.faculty_email ? `<br><a href="mailto:${item.faculty_email}" class="text-muted">${item.faculty_email}</a>` : ''}
                        </small>
                    </td>
                    <td>
                        <small class="text-truncate" style="max-width: 200px; display: block;" 
                               title="${item.subject_topic}">
                            ${item.subject_topic}
                        </small>
                    </td>
                    <td class="text-center">
                        <span class="percentage-badge ${getPercentageClass(item.avg_content_percent)}">
                            ${formatPercentage(item.avg_content_percent)}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="percentage-badge ${getPercentageClass(item.avg_presentation_percent)}">
                            ${formatPercentage(item.avg_presentation_percent)}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary">${item.participant_count}</span>
                    </td>
                    <td class="text-center">
                        <small>${formatDate(item.session_date)}</small>
                    </td>
                    <td class="text-center">
                        ${item.all_comments ? 
                            `<button class="btn btn-sm btn-outline-primary view-comments-btn" 
                                                         data-comments="${escapeHtml(item.all_comments)}">
                                                    <i class="bi bi-chat-text"></i> View
                                                </button>` : 
                            '<span class="text-muted">No comments</span>'
                        }
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        // Safely bind comments modal
        $('.view-comments-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const comments = $(this).data('comments');
            const modalElement = document.getElementById('commentsModal');
            if (modalElement) {
                $('#commentsContent').html(`
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">All Feedback Comments:</h6>
                            <div style="max-height: 300px; overflow-y: auto;">
                                ${comments.split(' | ').map(comment => `
                                                        <div class="border-bottom pb-2 mb-2">
                                                            <p class="mb-1">${comment}</p>
                                                        </div>
                                                    `).join('')}
                            </div>
                        </div>
                    </div>
                `);
                new bootstrap.Modal(modalElement).show();
            }
        });

        // Faculty link handlers
        $('.faculty-link').off('click').on('click', function(e) {
            e.preventDefault();
            const facultyId = $(this).data('faculty-id');
            if (facultyId) {
                window.open(`/faculty/show/${facultyId}`, '_blank');
            }
        });
    }

    // [Keep all other functions but add null checks...]
    function updatePagination(data) {
        const paginationSection = $('#paginationSection');
        if (!paginationSection.length) return;

        totalRecords = data.total;
        const totalPages = Math.ceil(totalRecords / perPage);

        $('#paginationInfo').text(
            `Showing ${((currentPage - 1) * perPage) + 1} to ${Math.min(currentPage * perPage, totalRecords)} of ${totalRecords} entries`
        );

        const paginationLinks = $('#paginationLinks');
        if (!paginationLinks.length) return;

        paginationLinks.empty();

        if (totalPages <= 1) {
            paginationSection.hide();
            return;
        }

        paginationSection.show();

        // Previous button
        const prevLi = $(`<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" data-page="${currentPage - 1}">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>`);
        paginationLinks.append(prevLi);

        // Page numbers
        const maxPagesToShow = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

        if (endPage - startPage + 1 < maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            const pageLi = $(`<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="javascript:void(0)" data-page="${i}">${i}</a>
            </li>`);
            paginationLinks.append(pageLi);
        }

        // Next button
        const nextLi = $(`<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" data-page="${currentPage + 1}">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>`);
        paginationLinks.append(nextLi);

        // Add click handlers
        $('.page-link').off('click').on('click', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page && page >= 1 && page <= totalPages) {
                currentPage = page;
                loadFeedbackData();
            }
        });
    }

    async function exportData(format) {
        if (!currentFilters.course_id) {
            alert('Please select a program first');
            return;
        }

        showLoading(true);

        try {
            const params = new URLSearchParams({
                ...currentFilters,
                export_type: format
            });

            const response = await fetch(`/faculty/database/export?${params.toString()}`);
            const data = await response.json();

            if (data.success) {
                if (format === 'excel') {
                    await exportToExcel(data.data, data.filename);
                } else if (format === 'pdf') {
                    await exportToPdf(data.data, data.filename);
                }
            } else {
                alert('Error exporting data: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Export error:', error);
            alert('Error exporting data');
        } finally {
            showLoading(false);
        }
    }

    function exportToExcel(data, filename) {
        const worksheet = XLSX.utils.json_to_sheet(data);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Feedback Database');

        // Style headers
        const range = XLSX.utils.decode_range(worksheet['!ref']);
        for (let C = range.s.c; C <= range.e.c; C++) {
            const cellAddress = XLSX.utils.encode_cell({
                r: 0,
                c: C
            });
            if (worksheet[cellAddress]) {
                worksheet[cellAddress].s = {
                    font: {
                        bold: true
                    },
                    fill: {
                        fgColor: {
                            rgb: "FFCC00"
                        }
                    },
                    alignment: {
                        horizontal: "center",
                        vertical: "center"
                    }
                };
            }
        }

        XLSX.writeFile(workbook, `${filename}.xlsx`);
    }

    function exportToPdf(data, filename) {
        const {
            jsPDF
        } = window.jspdf;

        const doc = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: 'a3'
        });

        const pageWidth = doc.internal.pageSize.getWidth();
        const today = new Date().toLocaleDateString('en-GB');
        const totalRecords = data.length;

        doc.setFontSize(16);
        doc.setTextColor(11, 79, 138);
        doc.text('Feedback Database Report', pageWidth / 2, 15, {
            align: 'center'
        });

        // Program / Course (LEFT)
        doc.setFontSize(10);
        doc.setTextColor(80, 80, 80);
        const program = getFiltersSummary().replace(/^Filters:\s*/i, '');
        doc.text(`Program: ${program}`, 10, 25);

        // Date & Total Records (BELOW Program – LEFT)
        doc.setFontSize(9);
        doc.text(`Date: ${today}`, 10, 31);
        doc.text(`Total Records: ${totalRecords}`, 10, 36);
        const tableData = data.map(row => [
            row['S.No.'],
            row['Faculty Name'],
            row['Course Name'],
            row['Faculty Address'],
            row['Topic'],
            row['Content %'],
            row['Presentation %'],
            row['No. of Participants'],
            row['Session Date'],
            row['Comments']
        ]);

        doc.autoTable({
            head: [
                [
                    'S.No.', 'Faculty Name', 'Course', 'Faculty Address', 'Topic',
                    'Content %', 'Pres. %', 'Participants', 'Session Date', 'Comments'
                ]
            ],
            body: tableData,
            startY: 42,
            theme: 'grid',

            // Compact rows
            styles: {
                fontSize: 8,
                cellPadding: 2,
                overflow: 'linebreak'
            },

            headStyles: {
                fillColor: [11, 79, 138],
                textColor: 255,
                fontSize: 8,
                halign: 'center'
            },

            // Full-width table
            margin: {
                left: 6,
                right: 6
            },

            columnStyles: {
                0: {
                    cellWidth: 14,
                    halign: 'center'
                },
                1: {
                    cellWidth: 40
                },
                2: {
                    cellWidth: 42
                },
                3: {
                    cellWidth: 55
                },
                4: {
                    cellWidth: 60
                },
                5: {
                    cellWidth: 20,
                    halign: 'center'
                },
                6: {
                    cellWidth: 20,
                    halign: 'center'
                },
                7: {
                    cellWidth: 25,
                    halign: 'center'
                },
                8: {
                    cellWidth: 28,
                    halign: 'center'
                },
                9: {
                    cellWidth: 70
                }
            }
        });

        doc.save(`${filename}.pdf`);
    }



    function getFiltersSummary() {
        let summary = [];
        if (currentFilters.course_id) {
            const courseName = $('#courseSelect option:selected').text();
            summary.push(`Program: ${courseName}`);
        }
        if (currentFilters.search_param === 'faculty' && currentFilters.faculty_id) {
            const facultyName = $('#facultyFilter option:selected').text();
            summary.push(`Faculty: ${facultyName}`);
        }
        if (currentFilters.search_param === 'topic' && currentFilters.topic_value) {
            summary.push(`Topic: ${currentFilters.topic_value}`);
        }
        return summary.length > 0 ? `Filters: ${summary.join(' | ')}` : 'All Records';
    }

    function showLoading(show) {
        if (show) {
            $('#loadingOverlay').fadeIn(200);
        } else {
            $('#loadingOverlay').fadeOut(200);
        }
    }

    function showNoDataMessage() {
        $('#feedbackTableBody').html(`
            <tr>
                <td colspan="10" class="text-center text-muted py-5">
                    <i class="bi bi-search me-2"></i>
                    No feedback data found for the selected criteria
                </td>
            </tr>
        `);
    }

    function showErrorMessage(message) {
        $('#feedbackTableBody').html(`
            <tr>
                <td colspan="10" class="text-center text-danger py-5">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${message}
                </td>
            </tr>
        `);
    }

    function formatPercentage(value) {
        const num = parseFloat(value) || 0;
        return num.toFixed(2) + '%';
    }

    function getPercentageClass(value) {
        const num = parseFloat(value) || 0;
        if (num >= 90) return 'percentage-excellent';
        if (num >= 80) return 'percentage-good';
        return 'percentage-average';
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endsection