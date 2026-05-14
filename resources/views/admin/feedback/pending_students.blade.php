@extends('admin.layouts.master')

@section('title', 'Pending Feedback - Students')

@section('setup_content')
<style>
    /* --- Course toggle --- */
    .ps-course-radio + label { background: transparent; color: #495057; border: none !important; font-weight: 600; padding: 8px 24px; border-radius: 8px; cursor: pointer; transition: background .2s,color .2s; }
    .ps-course-radio:checked + label { background: #1b3a5c !important; color: #fff !important; border-radius: 8px !important; }
    /* --- Filter toolbar --- */
    .ps-filter-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .ps-filter-row .btn-outline-secondary { font-size: 0.8125rem; border-radius: 6px; color: #495057; padding: 5px 14px; background: #fff; }
    .ps-filter-row .form-select { font-size: 0.8125rem; border-radius: 6px; border-color: #dee2e6; }
    .ps-reset-btn { border: 1.5px solid #dc3545; color: #dc3545; background: transparent; border-radius: 6px; font-size: 0.8125rem; padding: 5px 14px; font-weight: 500; white-space: nowrap; }
    .ps-reset-btn:hover { background: #dc3545; color: #fff; }
    .ps-search-btn { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ps-search-btn:hover { background: #e9ecef; }
    /* --- Table --- */
    #psTable thead th { background-color: #f8f9fa; font-size: 0.8125rem; font-weight: 600; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 12px 14px; white-space: nowrap; cursor: pointer; user-select: none; }
    #psTable thead th:hover { background-color: #e9ecef; }
    #psTable thead th.sort-active { color: #1b3a5c; }
    #psTable tbody td { font-size: 0.875rem; padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid #f1f3f5; color: #212529; }
    #psTable tbody tr:hover td { background-color: #fafbfc; }
    .ps-name-link { color: #1b3a5c; font-weight: 600; text-decoration: none; cursor: pointer; }
    .ps-name-link:hover { text-decoration: underline; }
    /* --- Detail view --- */
    #studentDetailView { display: none; }
    #detailTable thead th { background-color: #f8f9fa; font-size: 0.8125rem; font-weight: 600; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 12px 14px; white-space: nowrap; }
    #detailTable tbody td { font-size: 0.875rem; padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid #f1f3f5; color: #212529; }
    #detailTable tbody tr:hover td { background-color: #fafbfc; }
    .ps-back-btn { background: none; border: none; color: #1b3a5c; padding: 0; font-size: 1.25rem; cursor: pointer; display: inline-flex; align-items: center; }
    .ps-back-btn:hover { color: #0d2440; }
    /* --- Pagination --- */
    .ps-pagination .page-link { color: #1b3a5c; border-radius: 4px !important; margin: 0 2px; border: none; background: transparent; font-size: 0.8125rem; padding: 5px 10px; }
    .ps-pagination .page-link:hover { background: #f1f3f5; }
    .ps-pagination .page-item.active .page-link { background-color: #1b3a5c; border-color: #1b3a5c; color: #fff; }
    .ps-pagination .page-item.disabled .page-link { opacity: .35; }
    #psPaginationCell { display: flex; align-items: center; flex-wrap: wrap; gap: 2px; }
    /* --- Loading --- */
    #psLoadingSpinner { display: none; position: fixed; inset: 0; z-index: 1090; align-items: center; justify-content: center; background: rgba(0,0,0,.06); backdrop-filter: blur(2px); }
    #psLoadingSpinner.ps-loading { display: flex !important; }
    .select2-container { display: block !important; }
    .select2-container--open { z-index: 9999 !important; }
    .select2-dropdown { z-index: 9999 !important; }
    .ps-filter-row .select2-container { width: 100% !important; }
    .ps-filter-row .select2-container--default .select2-selection--single { height: 31px; padding: 0.25rem 0.5rem; font-size: 0.8125rem; border-color: #dee2e6; border-radius: 6px; }
    .ps-filter-row .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5; color: #495057; padding-left: 2px; padding-right: 20px; }
    .ps-filter-row .select2-container--default .select2-selection--single .select2-selection__arrow { height: 29px; }
    .ps-filter-row .select2-container--default .select2-selection--single .select2-selection__clear { margin-right: 18px; }
    @media print { .no-print { display: none !important; } }
</style>

<div class="container-fluid">
    <x-breadcrum title="Pending Feedback – Students"></x-breadcrum>
    <x-session_message />

    <div id="psLoadingSpinner">
        <div style="background:#fff;padding:1.5rem 2rem;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);text-align:center;">
            <div class="spinner-border text-primary mb-3" role="status" style="width:2.5rem;height:2.5rem;"><span class="visually-hidden">Loading...</span></div>
            <p class="mb-0 fw-medium text-secondary small">Loading student data...</p>
        </div>
    </div>

    {{-- Top toolbar --}}
    <div id="psTopToolbar" class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 no-print">
        <div class="d-flex align-items-center" role="group" aria-label="Course scope">
            <input class="btn-check ps-course-radio" type="radio" name="course_type_scope" value="active"
                id="psScopeActive" autocomplete="off" checked>
            <label for="psScopeActive">
                Active
                <span class="badge rounded-pill ms-1" style="background:#1b3a5c;font-size:.7rem;" id="activeBadge">{{ count($activeCourses ?? []) }}</span>
            </label>
            <input class="btn-check ps-course-radio" type="radio" name="course_type_scope" value="archive"
                id="psScopeArchive" autocomplete="off">
            <label for="psScopeArchive">
                Archived
                <span class="badge rounded-pill ms-1 bg-secondary" style="font-size:.7rem;" id="archiveBadge">{{ count($archiveCourses ?? []) }}</span>
            </label>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button type="button" id="btnPrint"
                class="btn btn-link text-decoration-none text-body d-inline-flex align-items-center gap-1 px-0">
                <span class="material-symbols-rounded" style="font-size:18px;">print</span>
                <span class="fw-semibold">Print</span>
            </button>
            <button type="button" id="exportExcelSummary"
                class="btn btn-link text-decoration-none text-body d-inline-flex align-items-center gap-1 px-0">
                <span class="material-symbols-rounded" style="font-size:18px;">download</span>
                <span class="fw-semibold">Download</span>
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-3 p-lg-4">

            {{-- Filter bar --}}
            <div class="ps-filter-row mb-3 no-print">
                <span class="text-muted fw-semibold small">Filters</span>

                {{-- Course --}}
                <div style="width:185px;flex-shrink:0;">
                    <select class="form-select form-select-sm select2-course" id="filter_course_pk">
                        <option value="">Courses</option>
                        @foreach ($courses ?? [] as $id => $name)
                            <option value="{{ $id }}" {{ isset($activeCourse) && $activeCourse == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Session --}}
                <div style="width:165px;flex-shrink:0;">
                    <select class="form-select form-select-sm select2-session" id="filter_session_id">
                        <option value="">Session</option>
                        @foreach ($sessions ?? [] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Time Period --}}
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Time Period</button>
                    <div class="dropdown-menu p-3" style="min-width:300px;">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">From</label>
                            <input type="date" id="filter_from_date" class="form-control form-control-sm" autocomplete="off">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">To</label>
                            <input type="date" id="filter_to_date" class="form-control form-control-sm" autocomplete="off">
                        </div>
                    </div>
                </div>

                {{-- Feedback status --}}
                <select class="form-select form-select-sm" id="filter_feedback_state" style="max-width:145px;">
                    <option value="not_given" selected>Feedback</option>
                    <option value="not_given">Not given</option>
                    <option value="given">Given</option>
                </select>

                <button type="button" class="ps-reset-btn" id="btnResetFilters">Reset Filters</button>
                <button type="button" class="ps-search-btn ms-auto" id="btnApplyFilters" title="Apply filters">
                    <span class="material-symbols-rounded" style="font-size:18px;color:#6c757d;">search</span>
                </button>
            </div>

            {{-- Student list view --}}
            <div id="studentListView">
                <div id="studentAccordionContainer">
                    <div class="text-center py-5 text-muted">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <p class="mt-3 mb-0 small fw-medium">Loading student data...</p>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2 no-print" id="paginationSection" style="display:none !important;">
                    <div id="psPaginationCell"></div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="text-muted small">Showing</span>
                        <select id="perPageSelect" class="form-select form-select-sm" style="width:78px;">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                        <span id="psTotalInfo" class="text-muted small">of 0 items</span>
                    </div>
                </div>
            </div>

            {{-- Student detail view (shown on name click) --}}
            <div id="studentDetailView">
                {{-- Detail top bar --}}
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div class="d-flex align-items-center gap-1">
                        <button class="ps-back-btn" id="detailBackBtn" title="Back to student list">
                            <span class="material-symbols-rounded" style="font-size:1.5rem;">arrow_back</span>
                        </button>
                        <div>
                            <div class="text-muted small" style="font-size:.75rem;">Home / Academic / Session Feedback Report / <span class="text-body fw-medium">Faculty Feedback Database</span></div>
                            <h5 class="fw-bold mb-0 mt-1" id="detailStudentTitle" style="color:#212529;"></h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" id="detailPrintBtn">
                            <span class="material-symbols-rounded" style="font-size:16px;">print</span> Print
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" id="detailDownloadBtn">
                            <span class="material-symbols-rounded" style="font-size:16px;">download</span> Download
                        </button>
                    </div>
                </div>
                {{-- Session table --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="detailTable">
                        <thead>
                            <tr>
                                <th style="width:55px;">S. No.</th>
                                <th>Session Name</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th style="width:160px;">Feedback Status</th>
                            </tr>
                        </thead>
                        <tbody id="detailTableBody"></tbody>
                    </table>
                </div>
                {{-- Detail bottom row --}}
                <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2 no-print">
                    <div id="detailPaginationCell"></div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="text-muted small">Showing</span>
                        <select id="detailPerPage" class="form-select form-select-sm" style="width:78px;">
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200" selected>200</option>
                        </select>
                        <span id="detailTotalInfo" class="text-muted small">of 0 items</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    function escapeHtml(text) {
        if (!text) return '—';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function destroySelect2IfAny($el) {
        if ($el && $el.length && $el.data('select2')) $el.select2('destroy');
    }

    function initCourseSelect2() {
        var $el = $('#filter_course_pk');
        destroySelect2IfAny($el);
        $el.select2({ placeholder:"Courses", allowClear:true, width:'100%', dropdownParent:$('body'), language:{noResults:function(){return "No courses found";}} });
    }

    function initSessionSelect2() {
        var $el = $('#filter_session_id');
        destroySelect2IfAny($el);
        $el.select2({ placeholder:"Session", allowClear:true, width:'100%', dropdownParent:$('body'), language:{noResults:function(){return "No sessions found";}} });
    }

    var originalSessions = $('#filter_session_id').html();
    var originalCourses  = $('#filter_course_pk').html();

    initCourseSelect2();
    initSessionSelect2();

    var activeCoursesData  = @json($activeCourses ?? []);
    var archiveCoursesData = @json($archiveCourses ?? []);
    var currentTab = 'active';

    function buildCourseOptions(courseMap, preselectPk) {
        var html = '<option value="">Courses</option>';
        $.each(courseMap, function(pk, name) {
            var sel = (preselectPk && String(pk) === String(preselectPk)) ? ' selected' : '';
            html += '<option value="'+pk+'"'+sel+'>'+$('<span>').text(name).html()+'</option>';
        });
        return html;
    }

    function switchCourseList(tab, preselectPk) {
        currentTab = tab;
        var courseMap = (tab === 'active') ? activeCoursesData : archiveCoursesData;
        var opts = buildCourseOptions(courseMap, preselectPk || null);
        var $course = $('#filter_course_pk');
        destroySelect2IfAny($course);
        $course.html(opts);
        initCourseSelect2();
        var pk = preselectPk != null && preselectPk !== '' ? String(preselectPk) : '';
        var toSelect = (pk && courseMap[pk] !== undefined) ? pk : '';
        $course.val(toSelect).trigger('change');
        originalCourses = $course.html();
    }

    $('input[name="course_type_scope"]').on('change', function() {
        var tab = $(this).val() === 'archive' ? 'archive' : 'active';
        switchCourseList(tab);
        var $sess = $('#filter_session_id');
        destroySelect2IfAny($sess);
        $sess.html('<option value="">Session</option>');
        initSessionSelect2();
        $sess.val('').trigger('change');
        originalSessions = $sess.html();
        $('#filter_from_date, #filter_to_date').val('');
        $('#filter_feedback_state').val('not_given');
        loadGroupedData();
    });

    // ── State ──
    var currentPage = 1;
    var perPage = 20;
    var sortBy = 'student_name';
    var sortDir = 'asc';
    var searchTerm = '';

    function loadGroupedData(page) {
        page = page || 1;
        currentPage = page;

        var params = {
            course_pk: $('#filter_course_pk').val(),
            session_id: $('#filter_session_id').val(),
            from_date: $('#filter_from_date').val(),
            to_date: $('#filter_to_date').val(),
            course_type: currentTab,
            filter_feedback_state: $('#filter_feedback_state').val(),
            page: page,
            per_page: perPage,
            sort_by: sortBy,
            sort_dir: sortDir,
            search: searchTerm
        };

        $('#studentAccordionContainer').html(
            '<div class="text-center py-5 text-muted"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3 mb-0 small fw-medium">Loading student data...</p></div>'
        );

        $.ajax({
            url: "{{ route('admin.feedback.pending.grouped') }}",
            type: "GET",
            data: params,
            dataType: 'json',
            success: function(response) {
                renderAccordion(response);
                updateTotalCount(response.total || 0);
                renderPagination(response);
            },
            error: function(xhr, status, error) {
                console.error('Grouped data error:', error);
                $('#studentAccordionContainer').html(
                    '<div class="text-center py-5 text-danger"><span class="material-symbols-rounded d-block mb-2" style="font-size:2.5rem;opacity:.5;">error_outline</span><p class="fw-semibold mb-1">Failed to load data</p><p class="text-muted small">Please try again or adjust your filters.</p></div>'
                );
                updateTotalCount(0);
            }
        });
    }

    var allStudentsData = [];

    function renderAccordion(data) {
        if (!data.students || data.students.length === 0) {
            $('#studentAccordionContainer').html(
                '<div class="text-center py-5 text-muted"><span class="material-symbols-rounded d-block mb-2" style="font-size:2.5rem;opacity:.35;">search_off</span><p class="fw-semibold mb-1">No records found</p><p class="small">Try adjusting your filters.</p></div>'
            );
            $('#paginationSection').hide();
            return;
        }

        allStudentsData = data.students;

        var html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" id="psTable"><thead><tr>';
        html += '<th style="width:55px">S. No.</th>';
        html += buildSortHeader('Student Name', 'student_name');
        html += buildSortHeader('Course', 'course_summary');
        html += buildSortHeader('Feedback Given', 'feedback_given', true);
        html += buildSortHeader('Feedback Not Given', 'feedback_not_given', true);
        html += '</tr></thead><tbody>';

        var startIndex = ((data.page || 1) - 1) * (data.per_page || 20);

        $.each(data.students, function(index, student) {
            var globalIndex = startIndex + index;
            html += '<tr>';
            html += '<td>'+(globalIndex+1)+'</td>';
            html += '<td><a href="javascript:void(0)" class="ps-name-link" data-index="'+index+'">'+escapeHtml(student.student_name)+'</a>';
            if (student.email) html += '<br><small class="text-body-secondary">'+escapeHtml(student.email)+'</small>';
            html += '</td>';
            html += '<td><small>'+escapeHtml(student.course_summary||'—')+'</small></td>';
            html += '<td class="text-center"><span class="badge rounded-pill bg-success-subtle text-success fw-semibold px-3">'+student.feedback_given+'</span></td>';
            html += '<td class="text-center"><span class="badge rounded-pill bg-danger-subtle text-danger fw-semibold px-3">'+student.feedback_not_given+'</span></td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        $('#studentAccordionContainer').html(html);

        // Student name click → show detail view
        $('#studentAccordionContainer').off('click', '.ps-name-link').on('click', '.ps-name-link', function(e) {
            e.preventDefault();
            var idx = parseInt($(this).data('index'));
            var student = allStudentsData[idx];
            if (student) showStudentDetail(student);
        });
    }

    function showStudentDetail(student) {
        $('#detailStudentTitle').text(student.student_name + '\'s Feedback Database');

        var sessions = student.sessions || [];
        var tbody = '';
        if (sessions.length === 0) {
            tbody = '<tr><td colspan="5" class="text-center text-muted py-4">No sessions found.</td></tr>';
        } else {
            $.each(sessions, function(i, session) {
                var statusBadge = session.feedback_status === 'given'
                    ? '<span class="badge rounded-pill bg-success-subtle text-success fw-semibold px-3">Given</span>'
                    : '<span class="badge rounded-pill bg-danger-subtle text-danger fw-semibold px-3">Not Given</span>';
                tbody += '<tr>';
                tbody += '<td>'+(i+1)+'</td>';
                tbody += '<td>'+escapeHtml(session.session_name)+'</td>';
                tbody += '<td>'+escapeHtml(session.date)+'</td>';
                tbody += '<td>'+escapeHtml(session.time)+'</td>';
                tbody += '<td>'+statusBadge+'</td>';
                tbody += '</tr>';
            });
        }
        $('#detailTableBody').html(tbody);
        $('#detailTotalInfo').text('of '+sessions.length+' items');

        // Hide list, show detail
        $('#psTopToolbar').hide();
        $('.ps-filter-row').hide();
        $('#studentListView').hide();
        $('#studentDetailView').show();
    }

    function hideStudentDetail() {
        $('#studentDetailView').hide();
        $('#studentListView').show();
        $('.ps-filter-row').show();
        $('#psTopToolbar').show();
    }

    function updateTotalCount(total) {
        var info = document.getElementById('psTotalInfo');
        if (info) info.textContent = 'of '+total.toLocaleString()+' items';
    }

    function buildSortHeader(label, field, center) {
        var cls = center ? 'text-center' : '';
        var icon = 'swap_vert';
        var activeClass = '';
        if (sortBy === field) { icon = sortDir==='asc'?'arrow_upward':'arrow_downward'; activeClass=' sort-active'; }
        return '<th class="sortable-header '+cls+activeClass+'" data-sort="'+field+'">'+label+' <i class="material-symbols-rounded sort-icon" style="font-size:.85rem;vertical-align:middle;opacity:.25;">'+icon+'</i></th>';
    }

    $(document).on('click', '.sortable-header', function() {
        var field = $(this).data('sort');
        if (sortBy===field) sortDir=sortDir==='asc'?'desc':'asc'; else { sortBy=field; sortDir='asc'; }
        loadGroupedData(1);
    });

    function renderPagination(data) {
        var totalPages = data.total_pages || 1;
        var page = data.page || 1;
        var total = data.total || 0;
        var pp = data.per_page || 20;
        var info = document.getElementById('psTotalInfo');
        if (info) info.textContent = 'of '+total.toLocaleString()+' items';

        var cell = document.getElementById('psPaginationCell');
        var section = $('#paginationSection');
        if (!cell) return;
        if (totalPages <= 1) { section.hide(); cell.innerHTML=''; return; }

        section.show().css('display','flex');
        var items='';
        items+='<li class="page-item'+(page<=1?' disabled':'')+'"><a class="page-link" href="#" data-page="'+(page-1)+'">&lsaquo;</a></li>';
        var start=Math.max(1,page-2), end=Math.min(totalPages,page+2);
        var actualStart=Math.max(1,end-4);
        if(actualStart>1) items+='<li class="page-item disabled"><a class="page-link">&hellip;</a></li>';
        for(var p=actualStart;p<=end;p++) items+='<li class="page-item'+(p===page?' active':'')+'"><a class="page-link" href="#" data-page="'+p+'">'+p+'</a></li>';
        if(end<totalPages) items+='<li class="page-item disabled"><a class="page-link">&hellip;</a></li>';
        items+='<li class="page-item'+(page>=totalPages?' disabled':'')+'"><a class="page-link" href="#" data-page="'+(page+1)+'">&rsaquo;</a></li>';
        cell.innerHTML='<ul class="pagination ps-pagination flex-wrap gap-1 mb-0">'+items+'</ul>';

        $('#psPaginationCell .page-link[data-page]').off('click').on('click', function(e) {
            e.preventDefault();
            var pg=parseInt($(this).data('page'));
            if(pg&&pg>=1&&pg<=totalPages&&pg!==page) { loadGroupedData(pg); $('html,body').animate({scrollTop:$('#studentAccordionContainer').offset().top-80},200); }
        });
    }

    // ── Filter handlers ──
    $('#btnApplyFilters').on('click', function() { loadGroupedData(); });

    $('#btnResetFilters').on('click', function() {
        var $course=$('#filter_course_pk');
        destroySelect2IfAny($course); $course.html(originalCourses); initCourseSelect2(); $course.val('').trigger('change');
        var $sess=$('#filter_session_id');
        destroySelect2IfAny($sess); $sess.html(originalSessions); initSessionSelect2(); $sess.val('').trigger('change');
        $('#filter_from_date, #filter_to_date').val('');
        $('#filter_feedback_state').val('not_given');
        loadGroupedData();
    });

    $('#filter_course_pk').on('change', function() {
        var courseId=$(this).val();
        var $sessionSelect=$('#filter_session_id');
        if(!courseId) {
            destroySelect2IfAny($sessionSelect); $sessionSelect.html(originalSessions); initSessionSelect2(); $sessionSelect.val('').trigger('change');
            loadGroupedData(); return;
        }
        destroySelect2IfAny($sessionSelect); $sessionSelect.html('<option value="">Loading sessions...</option>'); initSessionSelect2(); $sessionSelect.val('').trigger('change');
        $.ajax({
            url: "{{ route('admin.get.sessions.by.course') }}", type:"GET", data:{course_pk:courseId}, dataType:'json',
            success: function(response) {
                var options='<option value="">Session</option>';
                if(response&&response.length>0) {
                    $.each(response,function(i,s){ var label=s.subject_topic+(s.START_DATE?' ('+s.START_DATE+')':''); options+='<option value="'+s.pk+'">'+$('<span>').text(label).html()+'</option>'; });
                } else options='<option value="">No sessions found</option>';
                destroySelect2IfAny($sessionSelect); $sessionSelect.html(options); initSessionSelect2(); $sessionSelect.val('').trigger('change');
                loadGroupedData();
            },
            error: function() {
                destroySelect2IfAny($sessionSelect); $sessionSelect.html('<option value="">Error loading sessions</option>'); initSessionSelect2(); $sessionSelect.val('').trigger('change');
                loadGroupedData();
            }
        });
    });

    $('#filter_session_id').on('change', function() { loadGroupedData(); });

    var dateTimeout;
    $('#filter_from_date, #filter_to_date').on('change', function() { clearTimeout(dateTimeout); dateTimeout=setTimeout(function(){loadGroupedData();},500); });
    $('#filter_feedback_state').on('change', function() { loadGroupedData(1); });

    $('#perPageSelect').on('change', function() { perPage=parseInt($(this).val()); loadGroupedData(1); });

    // ── Export handlers ──
    function submitExportForm(actionUrl) {
        var form=$('<form>',{method:'POST',action:actionUrl,style:'display:none'});
        $('<input>').attr({type:'hidden',name:'_token',value:"{{ csrf_token() }}"}).appendTo(form);
        var filters={course_pk:$('#filter_course_pk').val(),session_id:$('#filter_session_id').val(),from_date:$('#filter_from_date').val(),to_date:$('#filter_to_date').val(),course_type:currentTab,filter_feedback_state:$('#filter_feedback_state').val()};
        $.each(filters,function(k,v){ if(v!==undefined&&v!==null&&v!=='') $('<input>').attr({type:'hidden',name:k,value:v}).appendTo(form); });
        $('body').append(form); form.submit(); setTimeout(function(){form.remove();},1000);
    }

    $('#exportPDF').on('click', function(e) { e.preventDefault(); submitExportForm("{{ route('admin.feedback.export.pdf') }}"); });
    $('#exportExcelSummary').on('click', function(e) { e.preventDefault(); submitExportForm("{{ route('admin.feedback.export.excel') }}"); });
    $('#exportExcelDetailed').on('click', function(e) { e.preventDefault(); submitExportForm("{{ route('admin.feedback.export.excel.detailed') }}"); });

    $('#detailBackBtn').on('click', function() { hideStudentDetail(); });
    $('#detailPrintBtn').on('click', function() { window.print(); });
    $('#detailDownloadBtn').on('click', function() {
        submitExportForm("{{ route('admin.feedback.export.excel') }}");
    });

    $('#btnPrint').on('click', function(e) {
        e.preventDefault();
        var params=$.param({course_pk:$('#filter_course_pk').val(),session_id:$('#filter_session_id').val(),from_date:$('#filter_from_date').val(),to_date:$('#filter_to_date').val(),course_type:currentTab,filter_feedback_state:$('#filter_feedback_state').val()});
        window.open("{{ route('admin.feedback.print') }}?"+params,'_blank');
    });

    // ── Initial load ──
    var activeCourseId='{{ $activeCourse ?? '' }}';
    switchCourseList('active', activeCourseId);
    if(!activeCourseId) loadGroupedData();

    console.log('Ready - Pending Feedback');
});
</script>
@endpush