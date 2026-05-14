@extends('admin.layouts.master')

@section('title', 'Average Rating - Course / Topic wise - Sargam | Lal Bahadur')

@section('setup_content')
<style>
    /* --- Course toggle --- */
    .fv-course-radio + label { background: transparent; color: #495057; border: none !important; font-weight: 600; padding: 8px 24px; border-radius: 8px; cursor: pointer; transition: background .2s,color .2s; }
    .fv-course-radio:checked + label { background: #1b3a5c !important; color: #fff !important; border-radius: 8px !important; }
    /* --- Filter toolbar --- */
    .fv-filter-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .fv-filter-row .btn-outline-secondary { font-size: 0.8125rem; border-radius: 6px; color: #495057; padding: 5px 14px; background: #fff; }
    .fv-filter-row .form-select { font-size: 0.8125rem; border-radius: 6px; border-color: #dee2e6; }
    .fv-reset-btn { border: 1.5px solid #dc3545; color: #dc3545; background: transparent; border-radius: 6px; font-size: 0.8125rem; padding: 5px 14px; font-weight: 500; white-space: nowrap; }
    .fv-reset-btn:hover { background: #dc3545; color: #fff; }
    .fv-search-btn { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .fv-search-btn:hover { background: #e9ecef; }
    /* --- Table --- */
    #fvTable thead th { background-color: #f8f9fa; font-size: 0.8125rem; font-weight: 600; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 12px 14px; white-space: nowrap; }
    #fvTable tbody td { font-size: 0.875rem; padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid #f1f3f5; color: #212529; }
    #fvTable tbody tr:hover td { background-color: #fafbfc; }
    /* --- Pagination --- */
    .fv-pagination .page-link { color: #1b3a5c; border-radius: 4px !important; margin: 0 2px; border: none; background: transparent; font-size: 0.8125rem; padding: 5px 10px; }
    .fv-pagination .page-link:hover { background: #f1f3f5; }
    .fv-pagination .page-item.active .page-link { background-color: #1b3a5c; border-color: #1b3a5c; color: #fff; }
    .fv-pagination .page-item.disabled .page-link { opacity: .35; }
    #fvPaginationCell { display: flex; align-items: center; flex-wrap: wrap; gap: 2px; }
    /* --- Misc --- */
    .faculty-type-badge { font-size: .7rem; font-weight: 500; padding: .2rem .5rem; border-radius: 50rem; background: #e9ecef; color: #495057; border: 1px solid #dee2e6; }
    .suggestions-container { position: relative; }
    .suggestions-list { position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 1080; display: none; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
    .suggestion-item { padding: .5rem .85rem; cursor: pointer; border-bottom: 1px solid #f1f1f1; font-size: .875rem; }
    .suggestion-item:hover { background: #f8f9fa; }
    .suggestion-item:last-child { border-bottom: none; }
    #fvLoadingSpinner { display: none; position: fixed; inset: 0; z-index: 1090; align-items: center; justify-content: center; background: rgba(0,0,0,.06); backdrop-filter: blur(2px); }
    #fvLoadingSpinner.fv-loading { display: flex !important; }
    @media print { .no-print { display: none !important; } }
</style>

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <x-breadcrum title="Average Rating - Course / Topic wise"></x-breadcrum>

    <div id="fvLoadingSpinner">
        <div style="background:#fff;padding:1.5rem 2rem;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);text-align:center;">
            <div class="spinner-border text-primary mb-3" role="status" style="width:2.5rem;height:2.5rem;"><span class="visually-hidden">Loading...</span></div>
            <p class="mb-0 fw-medium text-secondary small">Loading feedback data...</p>
        </div>
    </div>

    {{-- Top toolbar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 no-print">
        <div class="d-flex align-items-center" role="group" aria-label="Course status">
            <input class="btn-check fv-course-radio" type="radio" name="course_type" value="current"
                id="fvCurrent" autocomplete="off" {{ ($courseType ?? 'current') == 'current' ? 'checked' : '' }}>
            <label for="fvCurrent">Active</label>
            <input class="btn-check fv-course-radio" type="radio" name="course_type" value="archived"
                id="fvArchived" autocomplete="off" {{ ($courseType ?? 'current') == 'archived' ? 'checked' : '' }}>
            <label for="fvArchived">Archived</label>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn btn-outline-primary text-decoration-none d-inline-flex align-items-center gap-1" onclick="printReport()">
                <span class="material-symbols-rounded" style="font-size:18px;">print</span>
                <span class="fw-semibold">Print</span>
            </button>
            <button type="button" class="btn btn-outline-primary text-decoration-none d-inline-flex align-items-center gap-1" onclick="exportToExcel()">
                <span class="material-symbols-rounded" style="font-size:18px;">download</span>
                <span class="fw-semibold">Download</span>
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-3 p-lg-4">

            {{-- Filter bar --}}
            <div class="fv-filter-row mb-3 no-print">
                <span class="text-muted fw-semibold small">Filters</span>

                {{-- Program Name --}}
                <select class="form-select form-select-sm" id="programSelect" style="max-width:175px;">
                    <option value="">Program Na...</option>
                    @php $programs = $programs ?? collect([]); $currentProgram = $currentProgram ?? ''; @endphp
                    @foreach ($programs as $key => $program)
                        <option value="{{ $key }}" {{ $currentProgram == $key ? 'selected' : '' }}>{{ $program }}</option>
                    @endforeach
                </select>

                {{-- Time Period --}}
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Time Period</button>
                    <div class="dropdown-menu p-3" style="min-width:300px;">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">From</label>
                            <input type="date" id="fvFromDate" class="form-control form-control-sm" value="{{ $fromDate ?? '' }}">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">To</label>
                            <input type="date" id="fvToDate" class="form-control form-control-sm" value="{{ $toDate ?? '' }}">
                        </div>
                    </div>
                </div>

                {{-- Faculty Type --}}
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Faculty Type</button>
                    <div class="dropdown-menu p-3" style="min-width:160px;">
                        @php $selectedFacultyTypes = $selectedFacultyTypes ?? []; @endphp
                        <div class="form-check mb-2">
                            <input class="form-check-input faculty-type-checkbox" type="checkbox" value="2" id="fvGuest" {{ in_array('2', $selectedFacultyTypes) ? 'checked' : '' }}>
                            <label class="form-check-label" for="fvGuest">Guest</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input faculty-type-checkbox" type="checkbox" value="1" id="fvInternal" {{ in_array('1', $selectedFacultyTypes) ? 'checked' : '' }}>
                            <label class="form-check-label" for="fvInternal">Internal</label>
                        </div>
                    </div>
                </div>

                {{-- Faculty Name search --}}
                <div class="suggestions-container" style="max-width:220px;">
                    <input type="text" id="facultySearch" class="form-control form-control-sm"
                        value="{{ $currentFaculty ?? '' }}" placeholder="Search faculty..." autocomplete="off">
                    <div class="suggestions-list" id="facultySuggestions">
                        @php $facultySuggestions = $facultySuggestions ?? collect([]); @endphp
                        @if ($facultySuggestions->isNotEmpty())
                            @foreach ($facultySuggestions as $faculty)
                                <div class="suggestion-item" data-value="{{ $faculty->full_name }}">
                                    {{ $faculty->full_name }}
                                    @php $typeMap=['1'=>'Internal','2'=>'Guest']; $typeDisplay=$typeMap[$faculty->faculty_type]??ucfirst($faculty->faculty_type); @endphp
                                    <span class="faculty-type-badge ms-2">{{ $typeDisplay }}</span>
                                </div>
                            @endforeach
                        @else
                            <div class="suggestion-item text-muted small">No faculty found</div>
                        @endif
                    </div>
                </div>

                <button type="button" class="fv-reset-btn" id="resetButton">Reset Filters</button>
                <button type="button" class="fv-search-btn ms-auto" id="applyFiltersBtn" title="Apply filters">
                    <span class="material-symbols-rounded" style="font-size:18px;color:#6c757d;">search</span>
                </button>
            </div>

            {{-- Hidden form - backend POST structure preserved exactly --}}
            <form method="POST" action="{{ route('admin.feedback.faculty_view') }}" id="filterForm" style="display:none;">
                @csrf
                <input type="hidden" name="course_type"  id="fvHiddenCourseType" value="{{ $courseType ?? 'current' }}">
                <input type="hidden" name="program_id"   id="fvHiddenProgram"    value="{{ $currentProgram ?? '' }}">
                <input type="hidden" name="from_date"    id="fvHiddenFrom"       value="{{ $fromDate ?? '' }}">
                <input type="hidden" name="to_date"      id="fvHiddenTo"         value="{{ $toDate ?? '' }}">
                <input type="hidden" name="faculty_name" id="fvHiddenFaculty"    value="{{ $currentFaculty ?? '' }}">
                <input type="hidden" name="page"         id="pageInput"          value="{{ $currentPage ?? 1 }}">
            </form>

            {{-- Content container --}}
            <div id="contentContainer">
                @php
                    $feedbackData = $feedbackData ?? collect([]);
                    $currentPage  = $currentPage  ?? 1;
                    $totalRecords = $totalRecords  ?? 0;
                    $totalPages   = $totalPages    ?? 0;
                @endphp
                <span id="fvMeta" data-page="{{ $currentPage }}" data-total="{{ $totalRecords }}" data-pages="{{ $totalPages }}" style="display:none;"></span>
                @if ($feedbackData->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <span class="material-symbols-rounded d-block mb-2" style="font-size:2.5rem;opacity:.35;">rate_review</span>
                        No feedback data found for the selected filters.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="fvTable">
                            <thead>
                                <tr>
                                    <th style="width:55px">S. No.</th>
                                    <th>Faculty</th>
                                    <th>Topic</th>
                                    <th>Program Name</th>
                                    <th>Content</th>
                                    <th>Presentation</th>
                                    <th>Lecture Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($feedbackData as $i => $data)
                                    @php
                                        $cp = $data['content_percentage'] ?? 0;
                                        $pp = $data['presentation_percentage'] ?? 0;
                                        $cpColor = $cp >= 70 ? '#198754' : ($cp >= 40 ? '#fd7e14' : '#dc3545');
                                        $ppColor = $pp >= 70 ? '#198754' : ($pp >= 40 ? '#fd7e14' : '#dc3545');
                                    @endphp
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            {{ $data['faculty_name'] ?? '' }}
                                            @if (!empty($data['faculty_type']))
                                                <span class="faculty-type-badge ms-1">{{ $data['faculty_type'] }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $data['topic_name'] ?? '' }}</td>
                                        <td>{{ $data['program_name'] ?? '' }}</td>
                                        <td><span style="color:{{ $cpColor }};font-size:.8125rem;font-weight:600;">{{ number_format($cp, 1) }}%</span></td>
                                        <td><span style="color:{{ $ppColor }};font-size:.8125rem;font-weight:600;">{{ number_format($pp, 1) }}%</span></td>
                                        <td>
                                            <small class="text-body-secondary">
                                                @if (!empty($data['formatted_start_date']))
                                                    {{ $data['formatted_start_date'] }}
                                                @elseif (!empty($data['start_date']))
                                                    {{ \Carbon\Carbon::parse($data['start_date'])->format('d-M-Y') }}
                                                @else
                                                    -
                                                @endif
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Bottom row --}}
            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2 no-print" id="fvBottomRow">
                <div id="fvPaginationCell"></div>
                <div class="d-flex align-items-center gap-1">
                    <span class="text-muted small">Showing</span>
                    <select id="fvPerPage" class="form-select form-select-sm" style="width:78px;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200" selected>200</option>
                    </select>
                    <span id="fvTotalInfo" class="text-muted small">of {{ $totalRecords }} items</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var fvSyncForm = function() {
    var courseType = document.querySelector('.fv-course-radio:checked');
    document.getElementById('fvHiddenCourseType').value = courseType ? courseType.value : 'current';
    document.getElementById('fvHiddenProgram').value    = document.getElementById('programSelect')?.value || '';
    document.getElementById('fvHiddenFrom').value       = document.getElementById('fvFromDate')?.value   || '';
    document.getElementById('fvHiddenTo').value         = document.getElementById('fvToDate')?.value     || '';
    document.getElementById('fvHiddenFaculty').value    = document.getElementById('facultySearch')?.value || '';
    document.querySelectorAll('#filterForm input[name="faculty_type[]"]').forEach(function(el) { el.remove(); });
    document.querySelectorAll('.faculty-type-checkbox:checked').forEach(function(cb) {
        var inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'faculty_type[]'; inp.value = cb.value;
        document.getElementById('filterForm').appendChild(inp);
    });
};

function fvUpdateBottomRow(currentPage, totalPages, totalRecords) {
    var cell = document.getElementById('fvPaginationCell');
    var info = document.getElementById('fvTotalInfo');
    if (info) info.textContent = 'of ' + totalRecords + ' items';
    if (!cell) return;
    if (totalPages <= 1) { cell.innerHTML = ''; return; }
    var items = '';
    items += '<li class="page-item ' + (currentPage==1?'disabled':'') + '"><a class="page-link" href="javascript:void(0)" onclick="goToPage(1)">&#171;</a></li>';
    items += '<li class="page-item ' + (currentPage==1?'disabled':'') + '"><a class="page-link" href="javascript:void(0)" onclick="goToPage('+(currentPage-1)+')">&#8249;</a></li>';
    var start = Math.max(1, currentPage-2), end = Math.min(totalPages, currentPage+2);
    if (start > 1) items += '<li class="page-item disabled"><a class="page-link">&#8230;</a></li>';
    for (var i = start; i <= end; i++) {
        items += '<li class="page-item '+(i==currentPage?'active':'')+'"><a class="page-link" href="javascript:void(0)" onclick="goToPage('+i+')">'+i+'</a></li>';
    }
    if (end < totalPages) items += '<li class="page-item disabled"><a class="page-link">&#8230;</a></li>';
    items += '<li class="page-item '+(currentPage==totalPages?'disabled':'')+'"><a class="page-link" href="javascript:void(0)" onclick="goToPage('+(currentPage+1)+')">&#8250;</a></li>';
    items += '<li class="page-item '+(currentPage==totalPages?'disabled':'')+'"><a class="page-link" href="javascript:void(0)" onclick="goToPage('+totalPages+')">&#187;</a></li>';
    cell.innerHTML = '<ul class="pagination fv-pagination flex-wrap gap-1 mb-0">'+items+'</ul>';
}

function updateFacultySuggestions() {
    var facultySearch = document.getElementById('facultySearch');
    var suggestionsList = document.getElementById('facultySuggestions');
    var selectedTypes = Array.from(document.querySelectorAll('.faculty-type-checkbox:checked')).map(function(cb){ return cb.value; });
    if (selectedTypes.length === 0) { if (suggestionsList) suggestionsList.style.display='none'; return; }
    var params = new URLSearchParams();
    selectedTypes.forEach(function(t){ params.append('faculty_type[]', t); });
    var q = facultySearch ? facultySearch.value.trim() : '';
    if (q) params.append('faculty_name', q);
    fetch('{{ route('feedback.faculty_suggestions') }}?'+params.toString(), {
        method:'GET', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}
    })
    .then(function(r){ return r.json(); })
    .then(function(data){
        if (data.success && data.faculties && data.faculties.length > 0) {
            suggestionsList.innerHTML = data.faculties.map(function(f){
                return '<div class="suggestion-item" data-value="'+f.full_name+'">'+f.full_name+'<span class="faculty-type-badge ms-2">'+f.faculty_type_display+'</span></div>';
            }).join('');
        } else {
            suggestionsList.innerHTML = '<div class="suggestion-item text-muted small">No faculty found</div>';
        }
        suggestionsList.style.display = 'block';
    })
    .catch(function(){ if (suggestionsList) suggestionsList.style.display='none'; });
}

function loadFeedbackData(page) {
    page = page || 1;
    var spinner = document.getElementById('fvLoadingSpinner');
    var contentContainer = document.getElementById('contentContainer');
    var programSelect = document.getElementById('programSelect');
    var form = document.getElementById('filterForm');
    var pageInput = document.getElementById('pageInput');

    spinner.classList.add('fv-loading');
    contentContainer.style.opacity = '0.5';
    pageInput.value = page;

    var formData = new FormData(form);
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) formData.append('_token', csrfToken);

    fetch('{{ route('admin.feedback.faculty_view') }}', {
        method: 'POST',
        body: formData,
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}
    })
    .then(function(r){ if (!r.ok) throw new Error(r.status); return r.text(); })
    .then(function(html){
        var doc = new DOMParser().parseFromString(html, 'text/html');

        // Update programs dropdown (original logic)
        var newSel = doc.getElementById('programSelect');
        if (newSel) {
            var curVal = programSelect.value;
            programSelect.innerHTML = newSel.innerHTML;
            if (curVal && Array.from(programSelect.options).some(function(o){ return o.value===curVal; })) {
                programSelect.value = curVal;
            }
        }

        // Update content
        var newContent = doc.querySelector('#contentContainer');
        if (newContent) contentContainer.innerHTML = newContent.innerHTML;

        // Read pagination from embedded meta
        var meta = contentContainer.querySelector('#fvMeta');
        if (meta) {
            fvUpdateBottomRow(
                parseInt(meta.dataset.page)  || 1,
                parseInt(meta.dataset.pages) || 0,
                parseInt(meta.dataset.total) || 0
            );
        }

        spinner.classList.remove('fv-loading');
        contentContainer.style.opacity = '1';
    })
    .catch(function(err){
        console.error('Error:', err);
        contentContainer.innerHTML = '<div class="alert alert-danger text-center">Error loading data. Please try again.</div>';
        spinner.classList.remove('fv-loading');
        contentContainer.style.opacity = '1';
    });
}

function goToPage(pageNumber) {
    fvSyncForm();
    loadFeedbackData(pageNumber);
}

function exportToExcel() {
    var spinner = document.getElementById('fvLoadingSpinner');
    var form = document.getElementById('filterForm');
    spinner.classList.add('fv-loading');
    var formData = new FormData(form);
    formData.append('export_type', 'excel');
    formData.append('page', 'all');
    var tok = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (tok) formData.append('_token', tok);
    fetch('{{ route('admin.feedback.faculty_view.export') }}', {method:'POST',body:formData,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){ if (!r.ok) throw new Error(r.status); return r.blob(); })
    .then(function(blob){
        var url=URL.createObjectURL(blob); var a=document.createElement('a');
        a.href=url; a.download='faculty_feedback_'+new Date().toISOString().split('T')[0]+'.xlsx';
        document.body.appendChild(a); a.click(); URL.revokeObjectURL(url); a.remove();
        spinner.classList.remove('fv-loading');
    })
    .catch(function(){ spinner.classList.remove('fv-loading'); alert('Error exporting to Excel. Please try again.'); });
}

function exportToPDF() {
    var spinner = document.getElementById('fvLoadingSpinner');
    var form = document.getElementById('filterForm');
    spinner.classList.add('fv-loading');
    var formData = new FormData(form);
    formData.append('export_type', 'pdf');
    formData.append('page', 'all');
    var tok = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (tok) formData.append('_token', tok);
    fetch('{{ route('admin.feedback.faculty_view.export') }}', {method:'POST',body:formData,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){ if (!r.ok) throw new Error(r.status); return r.blob(); })
    .then(function(blob){
        var url=URL.createObjectURL(blob); var a=document.createElement('a');
        a.href=url; a.download='faculty_feedback_'+new Date().toISOString().split('T')[0]+'.pdf';
        document.body.appendChild(a); a.click(); URL.revokeObjectURL(url); a.remove();
        spinner.classList.remove('fv-loading');
    })
    .catch(function(){ spinner.classList.remove('fv-loading'); alert('Error exporting to PDF. Please try again.'); });
}

function printReport() {
    var form = document.getElementById('filterForm');
    var formData = new FormData(form);
    var params = new URLSearchParams();
    for (var pair of formData.entries()) params.append(pair[0], pair[1]);
    params.append('page', 'all');
    window.open('{{ route('admin.feedback.faculty_view.print') }}?'+params.toString(), '_blank');
}

document.addEventListener('DOMContentLoaded', function() {
    var facultySearch = document.getElementById('facultySearch');
    var suggestionsList = document.getElementById('facultySuggestions');
    var resetButton = document.getElementById('resetButton');
    var applyBtn = document.getElementById('applyFiltersBtn');
    var programSelect = document.getElementById('programSelect');
    var debounceTimer;

    document.querySelectorAll('.fv-course-radio').forEach(function(r){
        r.addEventListener('change', function(){ fvSyncForm(); goToPage(1); });
    });
    [programSelect, document.getElementById('fvFromDate'), document.getElementById('fvToDate')].forEach(function(el){
        if (el) el.addEventListener('change', function(){ fvSyncForm(); goToPage(1); });
    });
    document.querySelectorAll('.faculty-type-checkbox').forEach(function(cb){
        cb.addEventListener('change', function(){
            if (facultySearch) facultySearch.value = '';
            fvSyncForm(); updateFacultySuggestions(); goToPage(1);
        });
    });
    if (facultySearch && suggestionsList) {
        facultySearch.addEventListener('focus', function(){
            if (Array.from(document.querySelectorAll('.faculty-type-checkbox')).some(function(cb){ return cb.checked; })) updateFacultySuggestions();
        });
        facultySearch.addEventListener('input', function(){ clearTimeout(debounceTimer); debounceTimer=setTimeout(updateFacultySuggestions,300); });
        document.addEventListener('click', function(e){
            if (!facultySearch.contains(e.target) && !suggestionsList.contains(e.target)) suggestionsList.style.display='none';
        });
        suggestionsList.addEventListener('click', function(e){
            var item = e.target.closest('.suggestion-item');
            if (item && item.getAttribute('data-value')) {
                facultySearch.value = item.getAttribute('data-value');
                suggestionsList.style.display = 'none';
                fvSyncForm(); goToPage(1);
            }
        });
    }
    if (applyBtn) applyBtn.addEventListener('click', function(){ fvSyncForm(); goToPage(1); });
    var perPageSel = document.getElementById('fvPerPage');
    if (perPageSel) perPageSel.addEventListener('change', function(){ fvSyncForm(); goToPage(1); });
    resetButton.addEventListener('click', function(){
        document.querySelectorAll('.fv-course-radio').forEach(function(r){ r.checked=(r.value==='current'); });
        document.querySelectorAll('.faculty-type-checkbox').forEach(function(cb){ cb.checked=false; });
        if (programSelect) programSelect.value='';
        var fromEl=document.getElementById('fvFromDate'), toEl=document.getElementById('fvToDate');
        if (fromEl) fromEl.value=''; if (toEl) toEl.value='';
        if (facultySearch) facultySearch.value='';
        if (suggestionsList) suggestionsList.style.display='none';
        fvSyncForm(); goToPage(1);
    });

    fvSyncForm();
    fvUpdateBottomRow({{ $currentPage ?? 1 }}, {{ $totalPages ?? 0 }}, {{ $totalRecords ?? 0 }});
    loadFeedbackData({{ $currentPage ?? 1 }});
});
</script>
@endsection