@extends('admin.layouts.master')

@section('title', 'Faculty Feedback Average - Sargam | Lal Bahadur')

@section('setup_content')
<style>
    /* --- Course toggle --- */
    .fa-course-radio + label { background: transparent; color: #495057; border: none !important; font-weight: 600; padding: 8px 24px; border-radius: 8px; cursor: pointer; transition: background .2s,color .2s; }
    .fa-course-radio:checked + label { background: #1b3a5c !important; color: #fff !important; border-radius: 8px !important; }
    /* --- Filter toolbar --- */
    .fa-filter-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .fa-filter-row .btn-outline-secondary { font-size: 0.8125rem; border-radius: 6px; color: #495057; padding: 5px 14px; background: #fff; }
    .fa-filter-row .form-select { font-size: 0.8125rem; border-radius: 6px; border-color: #dee2e6; }
    .fa-reset-btn { border: 1.5px solid #dc3545; color: #dc3545; background: transparent; border-radius: 6px; font-size: 0.8125rem; padding: 5px 14px; font-weight: 500; white-space: nowrap; }
    .fa-reset-btn:hover { background: #dc3545; color: #fff; }
    .fa-search-btn { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .fa-search-btn:hover { background: #e9ecef; }
    /* --- Table --- */
    #faTable thead th { background-color: #f8f9fa; font-size: 0.8125rem; font-weight: 600; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 12px 14px; white-space: nowrap; }
    #faTable tbody td { font-size: 0.875rem; padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid #f1f3f5; color: #212529; }
    #faTable tbody tr:hover td { background-color: #fafbfc; }
    /* --- Pagination --- */
    .fa-pagination .page-link { color: #1b3a5c; border-radius: 4px !important; margin: 0 2px; border: none; background: transparent; font-size: 0.8125rem; padding: 5px 10px; }
    .fa-pagination .page-link:hover { background: #f1f3f5; }
    .fa-pagination .page-item.active .page-link { background-color: #1b3a5c; border-color: #1b3a5c; color: #fff; }
    .fa-pagination .page-item.disabled .page-link { opacity: .35; }
    #faPaginationCell { display: flex; align-items: center; flex-wrap: wrap; gap: 2px; }
    /* --- Misc --- */
    #faLoadingSpinner { display: none; position: fixed; inset: 0; z-index: 1090; align-items: center; justify-content: center; background: rgba(0,0,0,.06); backdrop-filter: blur(2px); }
    #faLoadingSpinner.fa-loading { display: flex !important; }
    @media print { .no-print { display: none !important; } }
</style>

<div class="container-fluid py-3 px-3 px-lg-4">
    <x-breadcrum title="Faculty Feedback Average"></x-breadcrum>

    <div id="faLoadingSpinner">
        <div style="background:#fff;padding:1.5rem 2rem;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);text-align:center;">
            <div class="spinner-border text-primary mb-3" role="status" style="width:2.5rem;height:2.5rem;"><span class="visually-hidden">Loading...</span></div>
            <p class="mb-0 fw-medium text-secondary small">Loading feedback data...</p>
        </div>
    </div>

    {{-- Top toolbar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 no-print">
        <div class="d-flex align-items-center" role="group" aria-label="Course status">
            <input class="btn-check fa-course-radio" type="radio" name="course_type_toggle" value="current"
                id="faCurrent" autocomplete="off" {{ ($courseType ?? 'current') == 'current' ? 'checked' : '' }}>
            <label for="faCurrent">Active</label>
            <input class="btn-check fa-course-radio" type="radio" name="course_type_toggle" value="archived"
                id="faArchived" autocomplete="off" {{ ($courseType ?? 'current') == 'archived' ? 'checked' : '' }}>
            <label for="faArchived">Archived</label>
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
            <div class="fa-filter-row mb-3 no-print">
                <span class="text-muted fw-semibold small">Filters</span>

                {{-- Program Name --}}
                <select class="form-select form-select-sm" id="faProgramSelect" name="program_name" style="max-width:175px;">
                    <option value="">Program Na...</option>
                    @foreach ($programs as $key => $program)
                        <option value="{{ $key }}" {{ ($currentProgram ?? '') == $key ? 'selected' : '' }}>{{ $program }}</option>
                    @endforeach
                </select>

                {{-- Faculty Name --}}
                <select class="form-select form-select-sm" id="faFacultySelect" name="faculty_name" style="max-width:175px;">
                    <option value="">Faculty Name</option>
                    @foreach ($faculties as $key => $faculty)
                        <option value="{{ $key }}" {{ ($currentFaculty ?? '') == $key ? 'selected' : '' }}>{{ $faculty }}</option>
                    @endforeach
                </select>

                {{-- Time Period --}}
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Time Period</button>
                    <div class="dropdown-menu p-3" style="min-width:300px;">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">From</label>
                            <input type="date" id="faFromDate" class="form-control form-control-sm" value="{{ $fromDate ?? '' }}">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">To</label>
                            <input type="date" id="faToDate" class="form-control form-control-sm" value="{{ $toDate ?? '' }}">
                        </div>
                    </div>
                </div>

                <button type="button" class="fa-reset-btn" id="faResetButton">Reset Filters</button>
                <button type="button" class="fa-search-btn ms-auto" id="faApplyBtn" title="Apply filters">
                    <span class="material-symbols-rounded" style="font-size:18px;color:#6c757d;">search</span>
                </button>
            </div>

            {{-- Hidden form - preserves original GET structure --}}
            <form method="GET" action="{{ route('feedback.average') }}" id="filterForm" style="display:none;">
                <input type="hidden" name="course_type"   id="faHiddenCourseType" value="{{ $courseType ?? 'current' }}">
                <input type="hidden" name="program_name"  id="faHiddenProgram"    value="{{ $currentProgram ?? '' }}">
                <input type="hidden" name="faculty_name"  id="faHiddenFaculty"    value="{{ $currentFaculty ?? '' }}">
                <input type="hidden" name="from_date"     id="faHiddenFrom"       value="{{ $fromDate ?? '' }}">
                <input type="hidden" name="to_date"       id="faHiddenTo"         value="{{ $toDate ?? '' }}">
            </form>

            {{-- Content container --}}
            <div id="tableContainer">
                @if ($feedbackData->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <span class="material-symbols-rounded d-block mb-2" style="font-size:2.5rem;opacity:.35;">rate_review</span>
                        No records found. Try adjusting your filters.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="faTable">
                            <thead>
                                <tr>
                                    <th style="width:55px">S. No.</th>
                                    <th>Faculty</th>
                                    <th>Topic</th>
                                    <th>Program Name</th>
                                    <th>Content %</th>
                                    <th>Presentation %</th>
                                    <th>Session Date and Time</th>
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
                                        <td>{{ $data['faculty_name'] ?? '' }}</td>
                                        <td>{{ $data['topic_name'] ?? '' }}</td>
                                        <td>{{ $data['program_name'] ?? '-' }}</td>
                                        <td><span style="color:{{ $cpColor }};font-size:.8125rem;font-weight:600;">{{ number_format($cp, 2) }}</span></td>
                                        <td><span style="color:{{ $ppColor }};font-size:.8125rem;font-weight:600;">{{ number_format($pp, 2) }}</span></td>
                                        <td>
                                            <small class="text-body-secondary">
                                                {{ \Carbon\Carbon::parse($data['session_date'])->format('d-M-Y') }}
                                                @if (!empty($data['class_session']))
                                                    {{ $data['class_session'] }}
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
            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2 no-print">
                <div id="faPaginationCell"></div>
                <div class="d-flex align-items-center gap-1">
                    <span class="text-muted small">Showing</span>
                    <select id="faPerPage" class="form-select form-select-sm" style="width:78px;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200" selected>200</option>
                    </select>
                    <span id="faTotalInfo" class="text-muted small">of {{ $feedbackData->count() }} items</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function faSyncForm() {
    var courseRadio = document.querySelector('.fa-course-radio:checked');
    document.getElementById('faHiddenCourseType').value = courseRadio ? courseRadio.value : 'current';
    document.getElementById('faHiddenProgram').value   = document.getElementById('faProgramSelect')?.value || '';
    document.getElementById('faHiddenFaculty').value   = document.getElementById('faFacultySelect')?.value || '';
    document.getElementById('faHiddenFrom').value      = document.getElementById('faFromDate')?.value || '';
    document.getElementById('faHiddenTo').value        = document.getElementById('faToDate')?.value   || '';
}

function faGetParams() {
    faSyncForm();
    var form = document.getElementById('filterForm');
    var data = new FormData(form);
    var params = new URLSearchParams();
    for (var pair of data.entries()) params.append(pair[0], pair[1]);
    return params;
}

function updateExportLinks() {
    // Preserved original logic: update export anchor hrefs dynamically
    var courseType   = document.querySelector('.fa-course-radio:checked')?.value || 'current';
    var programName  = document.getElementById('faProgramSelect')?.value || '';
    var facultyName  = document.getElementById('faFacultySelect')?.value || '';
    var fromDate     = document.getElementById('faFromDate')?.value || '';
    var toDate       = document.getElementById('faToDate')?.value   || '';

    var excelBaseUrl = "{{ route('feedback.average.export.excel') }}";
    var pdfBaseUrl   = "{{ route('feedback.average.export.pdf') }}";

    document.querySelectorAll('.export-link-excel').forEach(function(link) {
        var url = new URL(excelBaseUrl, window.location.origin);
        url.searchParams.set('course_type',  courseType);
        url.searchParams.set('program_name', programName);
        url.searchParams.set('faculty_name', facultyName);
        url.searchParams.set('from_date',    fromDate);
        url.searchParams.set('to_date',      toDate);
        link.href = url.toString();
    });
    document.querySelectorAll('.export-link-pdf').forEach(function(link) {
        var url = new URL(pdfBaseUrl, window.location.origin);
        url.searchParams.set('course_type',  courseType);
        url.searchParams.set('program_name', programName);
        url.searchParams.set('faculty_name', facultyName);
        url.searchParams.set('from_date',    fromDate);
        url.searchParams.set('to_date',      toDate);
        link.href = url.toString();
    });
}

function exportToExcel() {
    var params = faGetParams();
    var url = "{{ route('feedback.average.export.excel') }}?" + params.toString();
    window.open(url, '_blank');
}

function exportToPDF() {
    var params = faGetParams();
    var url = "{{ route('feedback.average.export.pdf') }}?" + params.toString();
    window.open(url, '_blank');
}

function printReport() {
    var params = faGetParams();
    var url = "{{ route('feedback.average.print') }}?" + params.toString();
    window.open(url, '_blank');
}

function loadProgramsByCourseType(courseType) {
    fetch("{{ route('feedback.average') }}?course_type=" + courseType + "&_=" + Date.now(), {
        headers: {'Cache-Control':'no-cache'}
    })
    .then(function(r){ return r.text(); })
    .then(function(html){
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var newSel = doc.getElementById('faProgramSelect');
        var curSel = document.getElementById('faProgramSelect');
        if (newSel && curSel) curSel.innerHTML = newSel.innerHTML;
    })
    .catch(function(err){ console.error('Error loading programs:', err); });
}

function faUpdateBottomRow(totalRecords) {
    var info = document.getElementById('faTotalInfo');
    if (info) info.textContent = 'of ' + totalRecords + ' items';
}

function loadFeedbackData() {
    faSyncForm();
    var spinner  = document.getElementById('faLoadingSpinner');
    var container = document.getElementById('tableContainer');
    spinner.classList.add('fa-loading');
    container.style.opacity = '0.5';

    var params = faGetParams();
    params.append('_', Date.now());

    fetch("{{ route('feedback.average') }}?" + params.toString(), {
        headers: {'X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}
    })
    .then(function(r){ if (!r.ok) throw new Error(r.status); return r.text(); })
    .then(function(html){
        var doc = new DOMParser().parseFromString(html, 'text/html');

        // Update program dropdown (original logic)
        var newProg = doc.getElementById('faProgramSelect');
        var curProg = document.getElementById('faProgramSelect');
        if (newProg && curProg) {
            var savedVal = curProg.value;
            curProg.innerHTML = newProg.innerHTML;
            if (savedVal && Array.from(curProg.options).some(function(o){ return o.value===savedVal; })) curProg.value = savedVal;
        }

        // Update table content
        var newContainer = doc.getElementById('tableContainer');
        if (newContainer) container.innerHTML = newContainer.innerHTML;

        // Update total count
        var totalEl = doc.getElementById('faTotalInfo');
        var localInfo = document.getElementById('faTotalInfo');
        if (totalEl && localInfo) localInfo.textContent = totalEl.textContent;

        updateExportLinks();
        spinner.classList.remove('fa-loading');
        container.style.opacity = '1';
    })
    .catch(function(err){
        console.error('Error:', err);
        container.innerHTML = '<div class="alert alert-danger text-center">Error loading data. Please try again.</div>';
        spinner.classList.remove('fa-loading');
        container.style.opacity = '1';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var programSel = document.getElementById('faProgramSelect');
    var facultySel = document.getElementById('faFacultySelect');
    var fromDate   = document.getElementById('faFromDate');
    var toDate     = document.getElementById('faToDate');
    var resetBtn   = document.getElementById('faResetButton');
    var applyBtn   = document.getElementById('faApplyBtn');

    document.querySelectorAll('.fa-course-radio').forEach(function(r) {
        r.addEventListener('change', function() {
            var courseType = this.value;
            document.getElementById('faLoadingSpinner').classList.add('fa-loading');
            document.getElementById('tableContainer').style.opacity = '0.5';
            loadProgramsByCourseType(courseType);
            setTimeout(function() { loadFeedbackData(); }, 300);
        });
    });

    if (programSel) programSel.addEventListener('change', function() { loadFeedbackData(); });
    if (facultySel) facultySel.addEventListener('change', function() { loadFeedbackData(); });
    if (fromDate)   fromDate.addEventListener('change', function() { loadFeedbackData(); });
    if (toDate)     toDate.addEventListener('change', function() { loadFeedbackData(); });
    if (applyBtn)   applyBtn.addEventListener('click', function() { loadFeedbackData(); });

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            document.querySelectorAll('.fa-course-radio').forEach(function(r){ r.checked=(r.value==='current'); });
            if (programSel) programSel.value = '';
            if (facultySel) facultySel.value = '';
            if (fromDate)   fromDate.value   = '';
            if (toDate)     toDate.value     = '';
            loadFeedbackData();
        });
    }

    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadFeedbackData();
    });

    setTimeout(function() { updateExportLinks(); }, 200);
    setTimeout(function() { loadFeedbackData(); }, 100);
});
</script>
@endsection