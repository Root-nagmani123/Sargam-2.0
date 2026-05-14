@extends('admin.layouts.master')

@section('title', 'Feedback Database - Sargam | Lal Bahadur')

@section('setup_content')
<style>
    /* --- Course toggle --- */
    .fdb-course-radio + label { background: transparent; color: #495057; border: none !important; font-weight: 600; padding: 8px 24px; border-radius: 8px; cursor: pointer; transition: background .2s,color .2s; }
    .fdb-course-radio:checked + label { background: #1b3a5c !important; color: #fff !important; border-radius: 8px !important; }
    /* --- Filter toolbar --- */
    .fdb-filter-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .fdb-filter-row .btn-outline-secondary { font-size: 0.8125rem; border-radius: 6px; color: #495057; padding: 5px 14px; background: #fff; }
    .fdb-filter-row .form-select { font-size: 0.8125rem; border-radius: 6px; border-color: #dee2e6; }
    .fdb-reset-btn { border: 1.5px solid #dc3545; color: #dc3545; background: transparent; border-radius: 6px; font-size: 0.8125rem; padding: 5px 14px; font-weight: 500; white-space: nowrap; }
    .fdb-reset-btn:hover { background: #dc3545; color: #fff; }
    .fdb-search-btn { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .fdb-search-btn:hover { background: #e9ecef; }
    /* --- Table --- */
    #feedbackTable thead th { background-color: #f8f9fa; font-size: 0.8125rem; font-weight: 600; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 12px 14px; white-space: nowrap; }
    #feedbackTable tbody td { font-size: 0.875rem; padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid #f1f3f5; color: #212529; }
    #feedbackTable tbody tr:hover td { background-color: #fafbfc; }
    /* --- Pagination --- */
    .fdb-pagination .page-link { color: #1b3a5c; border-radius: 4px !important; margin: 0 2px; border: none; background: transparent; font-size: 0.8125rem; padding: 5px 10px; }
    .fdb-pagination .page-link:hover { background: #f1f3f5; }
    .fdb-pagination .page-item.active .page-link { background-color: #1b3a5c; border-color: #1b3a5c; color: #fff; }
    .fdb-pagination .page-item.disabled .page-link { opacity: .35; }
    #fdbPaginationCell { display: flex; align-items: center; flex-wrap: wrap; gap: 2px; }
    /* --- Eye icon comments btn --- */
    .fdb-eye-btn { background: none; border: none; color: #6c757d; padding: 4px 8px; border-radius: 6px; cursor: pointer; transition: color .15s; }
    .fdb-eye-btn:hover { color: #1b3a5c; background: #eef2f7; }
    /* --- Loading --- */
    #fdbLoadingSpinner { display: none; position: fixed; inset: 0; z-index: 1090; align-items: center; justify-content: center; background: rgba(0,0,0,.06); backdrop-filter: blur(2px); }
    #fdbLoadingSpinner.fdb-loading { display: flex !important; }
    @media print { .no-print { display: none !important; } }
</style>

<div class="container-fluid">
    <x-breadcrum title="Feedback Database"></x-breadcrum>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div id="fdbLoadingSpinner">
        <div style="background:#fff;padding:1.5rem 2rem;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);text-align:center;">
            <div class="spinner-border text-primary mb-3" role="status" style="width:2.5rem;height:2.5rem;"><span class="visually-hidden">Loading...</span></div>
            <p class="mb-0 fw-medium text-secondary small">Loading feedback data...</p>
        </div>
    </div>

    {{-- Top toolbar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 no-print">
        <div class="d-flex align-items-center" role="group" aria-label="Course status">
            @php $courseType = $courseType ?? 'current'; @endphp
            <input class="btn-check fdb-course-radio" type="radio" name="course_type" value="current"
                id="fdbCurrent" autocomplete="off" {{ $courseType === 'current' ? 'checked' : '' }}>
            <label for="fdbCurrent">Active</label>
            <input class="btn-check fdb-course-radio" type="radio" name="course_type" value="archived"
                id="fdbArchived" autocomplete="off" {{ $courseType === 'archived' ? 'checked' : '' }}>
            <label for="fdbArchived">Archived</label>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button type="button" id="feedbackDbPrintBtn"
                class="btn btn-outline-primary text-decoration-none d-inline-flex align-items-center gap-1">
                <span class="material-symbols-rounded" style="font-size:18px;">print</span>
                <span class="fw-semibold">Print</span>
            </button>
            <a href="#" id="feedbackDbExcelLink"
                class="btn btn-outline-primary text-decoration-none d-inline-flex align-items-center gap-1">
                <span class="material-symbols-rounded" style="font-size:18px;">download</span>
                <span class="fw-semibold">Download</span>
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-3 p-lg-4">

            {{-- Filter bar --}}
            <div class="fdb-filter-row mb-3 no-print">
                <span class="text-muted fw-semibold small">Filters</span>

                {{-- Program Name --}}
                <select class="form-select form-select-sm" id="courseSelect" name="course_id" style="max-width:190px;">
                    <option value="">Program Name</option>
                    @if (isset($courses) && $courses->count() > 0)
                        @foreach ($courses as $course)
                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                        @endforeach
                    @else
                        <option value="" disabled>No courses available</option>
                    @endif
                </select>

                {{-- Filter By --}}
                <select class="form-select form-select-sm" id="searchParam" name="search_param" style="max-width:145px;">
                    <option value="all">Filter By</option>
                    <option value="faculty">Faculty</option>
                    <option value="topic">Topic</option>
                </select>

                {{-- Faculty Filter (hidden by default) --}}
                <div class="dynamic-filter-container d-none" id="facultyFilterContainer">
                    <select class="form-select form-select-sm" id="facultyFilter" name="faculty_id" style="max-width:190px;">
                        <option value="">All Faculties</option>
                        @if (isset($faculties) && $faculties->count() > 0)
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->pk }}">{{ $faculty->full_name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                {{-- Topic Filter (hidden by default) --}}
                <div class="dynamic-filter-container d-none" id="topicFilterContainer" style="max-width:220px;">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="topicFilter" name="topic_value" placeholder="Type topic name...">
                        <button class="btn btn-outline-secondary" type="button" id="clearTopicBtn">
                            <span class="material-symbols-rounded" style="font-size:14px;line-height:1;">close</span>
                        </button>
                    </div>
                </div>

                <button type="button" class="fdb-reset-btn" id="clearFiltersBtn">Reset Filters</button>
                <button type="button" class="fdb-search-btn ms-auto" title="Search">
                    <span class="material-symbols-rounded" style="font-size:18px;color:#6c757d;">search</span>
                </button>
            </div>

            {{-- Content container --}}
            <div class="table-responsive position-relative" id="tableContainer">
                {{-- Loading overlay (inline) --}}
                <div class="loading-overlay" id="loadingOverlay" style="display:none;position:absolute;inset:0;background:rgba(255,255,255,.75);z-index:10;justify-content:center;align-items:center;">
                    <div class="spinner-border text-primary" role="status" style="width:2.5rem;height:2.5rem;"><span class="visually-hidden">Loading...</span></div>
                </div>
                <table class="table table-hover align-middle mb-0" id="feedbackTable">
                    <thead>
                        <tr>
                            <th style="width:55px">S. No.</th>
                            <th>Faculty Name</th>
                            <th>Course</th>
                            <th>Faculty Address</th>
                            <th>Topic</th>
                            <th style="width:90px">Comments</th>
                        </tr>
                    </thead>
                    <tbody id="feedbackTableBody">
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <span class="material-symbols-rounded d-block mb-2" style="font-size:2.5rem;opacity:.35;">database</span>
                                Select a program to load feedback data
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Bottom row --}}
            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2 no-print" id="paginationSection" style="display:none !important;">
                <div id="fdbPaginationCell"></div>
                <div class="d-flex align-items-center gap-1">
                    <span class="text-muted small">Showing</span>
                    <select id="perPageSelect" class="form-select form-select-sm" style="width:78px;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200" selected>200</option>
                    </select>
                    <span id="fdbTotalInfo" class="text-muted small">of 0 items</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Comments Modal --}}
<div class="modal fade" id="commentsModal" tabindex="-1" aria-labelledby="commentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background:#1b3a5c;color:#fff;">
                <h6 class="modal-title mb-0" id="commentsModalLabel">
                    <span class="material-symbols-rounded me-2" style="font-size:18px;vertical-align:middle;">comment</span>Feedback Comments
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="commentsContent"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const FEEDBACK_DB_EXPORT_ROUTES = {
        print: @json(route('admin.feedback.database.print')),
        pdf:   @json(route('admin.feedback.database.export.pdf')),
        excel: @json(route('admin.feedback.database.export.excel')),
    };
    const FEEDBACK_DB_COURSES_URL = @json(route('admin.feedback.database.courses'));

    $(document).ready(function() {
        if (window.feedbackPageLoaded) return;
        window.feedbackPageLoaded = true;

        let currentPage = 1;
        let perPage = 200;
        let totalRecords = 0;
        let currentFilters = { course_id: '', search_param: 'all', faculty_id: '', topic_value: '' };
        let courseType = @json($courseType ?? 'current');
        let debounceTimer;

        if (!checkRequiredElements()) { console.error('Required elements not found'); return; }

        initializeEventListeners();
        autoSelectFirstCourse();
        syncFeedbackDbExportLinks();
        syncFeedbackDbCourseTypeUrl();

        function checkRequiredElements() {
            for (const s of ['#courseSelect','#searchParam','#feedbackTableBody','#loadingOverlay']) {
                if (!$(s).length) { console.error('Required element not found: '+s); return false; }
            }
            return true;
        }

        function autoSelectFirstCourse() {
            const courseSelect = $('#courseSelect');
            if (!courseSelect.length) return;
            const firstOpt = courseSelect.find('option:not(:first):not([disabled])').first();
            if (firstOpt.length > 0) {
                const courseId = firstOpt.val();
                const courseName = firstOpt.text();
                courseSelect.val(courseId);
                currentFilters.course_id = courseId;
                $('#feedbackTableBody').html(`<tr><td colspan="6" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"><span class="visually-hidden">Loading...</span></div>Loading feedback data for <strong>${courseName}</strong>...</td></tr>`);
                loadFeedbackData();
            } else {
                showInitialMessage();
                syncFeedbackDbExportLinks();
            }
        }

        function initializeEventListeners() {
            safeBind('#courseSelect', 'change', function(e) {
                e.preventDefault();
                const courseId = $(this).val();
                if (courseId) { currentFilters.course_id = courseId; currentPage = 1; loadFeedbackData(); }
                else { showInitialMessage(); syncFeedbackDbExportLinks(); }
            });

            safeBind('#searchParam', 'change', function(e) {
                e.preventDefault();
                const searchParam = $(this).val();
                currentFilters.search_param = searchParam;
                $('.dynamic-filter-container').addClass('d-none').removeClass('d-block');
                if (searchParam === 'faculty') {
                    showElement('#facultyFilterContainer');
                    currentFilters.faculty_id = $('#facultyFilter').val();
                    currentFilters.topic_value = ''; $('#topicFilter').val('');
                } else if (searchParam === 'topic') {
                    showElement('#topicFilterContainer');
                    currentFilters.topic_value = $('#topicFilter').val();
                    currentFilters.faculty_id = ''; $('#facultyFilter').val('');
                } else {
                    currentFilters.faculty_id = ''; currentFilters.topic_value = '';
                    $('#facultyFilter').val(''); $('#topicFilter').val('');
                }
                if (currentFilters.course_id) { currentPage = 1; loadFeedbackData(); }
            });

            safeBind('#facultyFilter', 'change', function(e) {
                e.preventDefault();
                currentFilters.faculty_id = $(this).val();
                if (currentFilters.course_id) { currentPage = 1; loadFeedbackData(); }
            });

            safeBind('#topicFilter', 'input', function(e) {
                e.preventDefault();
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    currentFilters.topic_value = $(this).val();
                    if (currentFilters.course_id && currentFilters.topic_value.length >= 2) { currentPage = 1; loadFeedbackData(); }
                }, 500);
            });

            safeBind('#clearTopicBtn', 'click', function(e) {
                e.preventDefault();
                $('#topicFilter').val(''); currentFilters.topic_value = '';
                if (currentFilters.course_id) { currentPage = 1; loadFeedbackData(); }
            });

            safeBind('#clearFiltersBtn', 'click', function(e) { e.preventDefault(); clearAllFilters(); });

            safeBind('#perPageSelect', 'change', function(e) {
                e.preventDefault();
                perPage = $(this).val(); currentPage = 1;
                if (currentFilters.course_id) loadFeedbackData();
            });

            safeBind('#feedbackDbPrintBtn', 'click', function(e) {
                e.preventDefault();
                if (!currentFilters.course_id) { alert('Please select a program first.'); return; }
                const q = buildFeedbackDbExportQuery();
                window.open(FEEDBACK_DB_EXPORT_ROUTES.print + (q ? ('?'+q) : ''), '_blank', 'noopener');
            });

            $('input[name="course_type"]').on('change', function() { reloadCourseListForType(); });
        }

        function reloadCourseListForType() {
            const ct = document.querySelector('input[name="course_type"]:checked')?.value || 'current';
            courseType = ct;
            showLoading(true);
            fetch(FEEDBACK_DB_COURSES_URL + '?course_type=' + encodeURIComponent(ct), {
                headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
            })
            .then(r => { if (!r.ok) throw new Error('Bad response'); return r.json(); })
            .then(data => {
                showLoading(false);
                if (!data.success) { alert('Could not load programs for this course type.'); return; }
                const sel = $('#courseSelect');
                sel.empty().append('<option value="">Program Name</option>');
                if (data.courses && data.courses.length > 0) {
                    data.courses.forEach(function(c) {
                        sel.append('<option value="'+c.pk+'">'+$('<div/>').text(c.course_name||'').html()+'</option>');
                    });
                } else { sel.append('<option value="" disabled>No courses available</option>'); }
                currentFilters = { course_id:'', search_param:'all', faculty_id:'', topic_value:'' };
                $('#searchParam').val('all'); $('#facultyFilter').val(''); $('#topicFilter').val('');
                $('.dynamic-filter-container').addClass('d-none').removeClass('d-block');
                currentPage = 1;
                autoSelectFirstCourse(); syncFeedbackDbExportLinks(); syncFeedbackDbCourseTypeUrl();
            })
            .catch(function(err) { console.error(err); showLoading(false); alert('Could not load programs.'); });
        }

        function syncFeedbackDbCourseTypeUrl() {
            try { const u = new URL(window.location.href); u.searchParams.set('course_type', courseType||'current'); window.history.replaceState({},  '', u.toString()); } catch(e){}
        }

        function buildFeedbackDbExportQuery() {
            if (!currentFilters.course_id) return '';
            const params = new URLSearchParams();
            params.set('course_id', currentFilters.course_id);
            params.set('search_param', currentFilters.search_param || 'all');
            if (currentFilters.faculty_id) params.set('faculty_id', currentFilters.faculty_id);
            if (currentFilters.topic_value) params.set('topic_value', currentFilters.topic_value);
            return params.toString();
        }

        function syncFeedbackDbExportLinks() {
            const q = buildFeedbackDbExportQuery();
            const $excel = $('#feedbackDbExcelLink');
            const $print = $('#feedbackDbPrintBtn');
            if (!q) {
                $excel.attr('href','#').addClass('disabled'); $print.prop('disabled',true).addClass('disabled'); return;
            }
            $excel.attr('href', FEEDBACK_DB_EXPORT_ROUTES.excel+'?'+q).removeClass('disabled');
            $print.prop('disabled',false).removeClass('disabled');
        }

        function safeBind(selector, event, handler) {
            const el = $(selector);
            if (el.length) el.off(event).on(event, handler);
            else console.warn('Element not found for binding: '+selector);
        }

        function showElement(selector) {
            const el = $(selector);
            if (el.length) el.removeClass('d-none').addClass('d-block');
        }

        function clearAllFilters() {
            $('#courseSelect').val(''); $('#searchParam').val('all');
            $('#facultyFilter').val(''); $('#topicFilter').val('');
            $('.dynamic-filter-container').addClass('d-none').removeClass('d-block');
            currentFilters = { course_id:'', search_param:'all', faculty_id:'', topic_value:'' };
            currentPage = 1;
            showInitialMessage(); syncFeedbackDbExportLinks();
        }

        function showInitialMessage() {
            const hasCourses = $('#courseSelect option').length > 1;
            const msg = hasCourses
                ? '<span class="material-symbols-rounded d-block mb-2" style="font-size:2.5rem;opacity:.35;">database</span>Select a program to view feedback data'
                : '<span class="material-symbols-rounded d-block mb-2" style="font-size:2.5rem;opacity:.35;">error</span>No programs available. Please add courses first.';
            $('#feedbackTableBody').html('<tr><td colspan="6" class="text-center text-muted py-5">'+msg+'</td></tr>');
            $('#paginationSection').hide();
        }

        function loadFeedbackData() {
            if (!currentFilters.course_id) { showInitialMessage(); syncFeedbackDbExportLinks(); return; }
            showLoading(true);
            const params = new URLSearchParams({...currentFilters, page: currentPage, per_page: perPage});
            fetch('/faculty/database/data?'+params.toString())
            .then(r => r.json())
            .then(data => {
                if (data.success) { renderTable(data.data); updatePagination(data); }
                else showErrorMessage(data.error || 'Error loading data');
                showLoading(false); syncFeedbackDbExportLinks();
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Error loading data. Please try again.');
                showLoading(false); syncFeedbackDbExportLinks();
            });
        }

        function renderTable(data) {
            const tbody = $('#feedbackTableBody');
            if (!tbody.length) return;
            tbody.empty();
            if (!data || data.length === 0) { showNoDataMessage(); $('#paginationSection').hide(); return; }
            data.forEach((item, index) => {
                const sno = ((currentPage - 1) * perPage) + index + 1;
                const address = item.faculty_address || 'N/A';
                const commentBtn = item.all_comments
                    ? `<button class="fdb-eye-btn view-comments-btn" data-comments="${escapeHtml(item.all_comments)}" title="View comments"><span class="material-symbols-rounded" style="font-size:20px;">visibility</span></button>`
                    : `<span class="text-muted" style="font-size:.875rem;">—</span>`;
                tbody.append(`<tr>
                    <td>${sno}</td>
                    <td><a href="javascript:void(0)" class="fw-semibold text-decoration-none faculty-link" data-faculty-id="${item.faculty_enc_id||''}" style="color:#1b3a5c;">${item.faculty_name}</a></td>
                    <td>${item.course_name}</td>
                    <td><small class="text-body-secondary">${address}</small></td>
                    <td><small>${item.subject_topic}</small></td>
                    <td class="text-center">${commentBtn}</td>
                </tr>`);
            });

            $('.view-comments-btn').off('click').on('click', function(e) {
                e.preventDefault();
                const comments = $(this).data('comments');
                const modalEl = document.getElementById('commentsModal');
                if (modalEl) {
                    $('#commentsContent').html('<div style="max-height:400px;overflow-y:auto;">'+
                        comments.split(' | ').map((c,i) => `<div class="d-flex gap-2 align-items-start border-bottom pb-2 mb-2"><span class="badge bg-primary bg-opacity-10 text-primary mt-1" style="min-width:22px;">${i+1}</span><p class="mb-0 text-body-secondary" style="font-size:.88rem;">${c}</p></div>`).join('')+
                    '</div>');
                    new bootstrap.Modal(modalEl).show();
                }
            });

            $('.faculty-link').off('click').on('click', function(e) {
                e.preventDefault();
                const fid = $(this).data('faculty-id');
                if (fid) window.open('/faculty/show/'+fid, '_blank');
            });
        }

        function updatePagination(data) {
            totalRecords = data.total;
            const totalPages = Math.ceil(totalRecords / perPage);
            const info = document.getElementById('fdbTotalInfo');
            if (info) info.textContent = 'of '+totalRecords+' items';

            const paginationSection = $('#paginationSection');
            const cell = document.getElementById('fdbPaginationCell');
            if (!cell) return;
            if (totalPages <= 1) { paginationSection.hide(); cell.innerHTML=''; return; }

            paginationSection.show().css('display','flex');
            let items = '';
            items += '<li class="page-item '+(currentPage===1?'disabled':'')+'"><a class="page-link" href="javascript:void(0)" data-page="'+(currentPage-1)+'">&#8249;</a></li>';
            const maxPages=5, start=Math.max(1,currentPage-Math.floor(maxPages/2)), end=Math.min(totalPages,start+maxPages-1);
            const actualStart = Math.max(1, end-maxPages+1);
            if (actualStart>1) items+='<li class="page-item disabled"><a class="page-link">&#8230;</a></li>';
            for (let i=actualStart;i<=end;i++) items+='<li class="page-item '+(i===currentPage?'active':'')+'"><a class="page-link" href="javascript:void(0)" data-page="'+i+'">'+i+'</a></li>';
            if (end<totalPages) items+='<li class="page-item disabled"><a class="page-link">&#8230;</a></li>';
            items+='<li class="page-item '+(currentPage===totalPages?'disabled':'')+'"><a class="page-link" href="javascript:void(0)" data-page="'+(currentPage+1)+'">&#8250;</a></li>';
            cell.innerHTML='<ul class="pagination fdb-pagination flex-wrap gap-1 mb-0">'+items+'</ul>';

            $('.page-link[data-page]').off('click').on('click', function(e) {
                e.preventDefault();
                const page = parseInt($(this).data('page'));
                if (page && page>=1 && page<=totalPages) { currentPage=page; loadFeedbackData(); }
            });
        }

        function showLoading(show) {
            if (show) $('#loadingOverlay').css('display','flex'); else $('#loadingOverlay').hide();
        }

        function showNoDataMessage() {
            $('#feedbackTableBody').html('<tr><td colspan="6" class="text-center text-muted py-5"><span class="material-symbols-rounded d-block mb-2" style="font-size:2.5rem;opacity:.35;">search</span>No feedback data found for the selected criteria</td></tr>');
        }

        function showErrorMessage(message) {
            $('#feedbackTableBody').html('<tr><td colspan="6" class="text-center text-danger py-5"><span class="material-symbols-rounded d-block mb-2" style="font-size:2.5rem;opacity:.5;">error</span>'+message+'</td></tr>');
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});
        }

        function escapeHtml(text) {
            const div = document.createElement('div'); div.textContent = text; return div.innerHTML;
        }
    });
</script>
@endsection