@extends('admin.layouts.master')

@section('title', 'Faculty MDO/Escort Exception View')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
<style>
/* Faculty MDO / Escort Exception View — page-only pieces on top of the shared
 * mst-* / ds-* layers. Scoped to .fme-page, --ds-* tokens only. */

/* One collapsible card per faculty: the summary row is the scannable index
 * (name + course / exception counts); the course tables sit inside. */
.mst-page.fme-page .fme-faculty { padding: 0; overflow: hidden; }
.mst-page.fme-page .fme-faculty > summary {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--ds-space-2) var(--ds-space-3);
    padding: var(--ds-space-3) var(--ds-space-4);
    cursor: pointer;
    list-style: none;
}
.mst-page.fme-page .fme-faculty > summary::-webkit-details-marker { display: none; }
.mst-page.fme-page .fme-faculty > summary:hover { background: var(--ds-surface-2); }
.mst-page.fme-page .fme-faculty > summary:focus-visible { outline: 0; box-shadow: inset var(--ds-focus-ring); }
.mst-page.fme-page .fme-faculty[open] > summary { border-bottom: 1px solid var(--ds-line); }
.mst-page.fme-page .fme-faculty__name {
    flex: 1 1 16rem;
    min-width: 0;
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--ds-ink);
}
.mst-page.fme-page .fme-chevron {
    color: var(--ds-ink-muted);
    transition: transform 0.15s ease;
}
.mst-page.fme-page .fme-faculty[open] .fme-chevron { transform: rotate(180deg); }
.mst-page.fme-page .fme-course { padding: var(--ds-space-3) var(--ds-space-4) var(--ds-space-4); }
.mst-page.fme-page .fme-course + .fme-course { border-top: 1px solid var(--ds-line); }
.mst-page.fme-page .fme-course__head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--ds-space-2);
    margin-bottom: var(--ds-space-3);
}
.mst-page.fme-page .fme-course__title {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--ds-ink);
}

/* Faculty-login table: one row per exception. Every other student's rows
 * get a soft tint so a student's exceptions read as one block — no rowspans,
 * which would shift the programme-dt column styling on continuation rows. */
.mst-page.fme-page .programme-dt-table tbody tr.fme-group-alt > td {
    background-color: var(--ds-surface-2);
}

/* Long-text columns wrap (.mst-col-wrap) but keep a floor, so the table
 * scrolls sideways instead of squeezing them to a word per line. */
.mst-page.fme-page .fme-table .mst-col-wrap { min-width: 11rem; }

/* These are data tables: the last column is a value (Description / Total),
 * not a master grid's action stack, so undo master-admin.css's right-aligned
 * nowrap last column (which would stop long descriptions wrapping). */
.mst-page.fme-page .programme-dt-table.fme-table th:last-child,
.mst-page.fme-page .programme-dt-table.fme-table td:last-child {
    text-align: left;
    white-space: normal;
}

/* Wide enough tracks that Apply + Reset Filters share one row. */
.mst-page.fme-page .mst-filter-grid { grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr)); }

@media print {
    .mst-page.fme-page .fme-faculty > summary .fme-chevron { display: none; }
}
</style>
@endpush

@section('setup_content')
@php
    // Check if this is a faculty login view
    $isFacultyView = isset($isFacultyView) && $isFacultyView === true;

    // Admin view: headline counts derived from the rows the controller built
    // (display only — nothing is re-queried or re-filtered here).
    $fmeFaculties = $isFacultyView ? [] : ($facultyData ?? []);
    $fmeCourseIds = [];
    $fmeExceptionTotal = 0;
    foreach ($fmeFaculties as $fmeFac) {
        foreach ($fmeFac['courses'] as $fmeCourse) {
            $fmeCourseIds[$fmeCourse['course_id']] = true;
            $fmeExceptionTotal += (int) $fmeCourse['duty_count'];
        }
    }
    // A long list starts collapsed so the faculty names read as an index;
    // a short one (e.g. one faculty filtered) starts open.
    $fmeOpenByDefault = count($fmeFaculties) <= 5;
    $fmeHasFilter = $isFacultyView ? filled($courseFilter ?? null) : (filled($facultyFilter ?? null) || filled($courseFilter ?? null));
@endphp
<div class="container-fluid mst-page fme-page">
    <x-breadcrum title="Faculty MDO/Escort Exception View" :showBack="false"></x-breadcrum>

    <x-session_message />

    {{-- Print sits above the card, right-aligned (docs/new-design-index-page.md §1). --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 mst-secondary-actions">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" onclick="printContent()" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    {{-- Filters: GET to this same route; the controller reads the same
         course_filter / faculty_filter parameters it always has. --}}
    @if($isFacultyView && isset($courseMaster))
        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Filters</h2>
                <form method="GET" action="{{ route('faculty.mdo.escort.exception.view') }}" class="mst-filter-grid" role="search" aria-label="Filter exceptions">
                    <div>
                        <label for="course_filter" class="mst-form-label d-block">Course</label>
                        <select id="course_filter" name="course_filter" class="form-select mst-control mst-searchable" data-placeholder="All Courses">
                            <option value="">-- All Courses --</option>
                            @foreach ($courseMaster as $id => $name)
                                <option value="{{ $id }}" {{ isset($courseFilter) && $courseFilter == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mst-filter-actions">
                        <button type="submit" class="btn mst-btn-submit px-4">Apply</button>
                        <a href="{{ route('faculty.mdo.escort.exception.view') }}" class="btn programme-dt-btn-reset">Reset Filters</a>
                    </div>
                </form>
            </div>
        </div>
    @elseif(!$isFacultyView)
        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Filters</h2>
                <form method="GET" action="{{ route('faculty.mdo.escort.exception.view') }}" class="mst-filter-grid" role="search" aria-label="Filter exceptions">
                    <div>
                        <label for="fmeFacultyFilter" class="mst-form-label d-block">Faculty</label>
                        <select id="fmeFacultyFilter" name="faculty_filter" class="form-select mst-control mst-searchable" data-placeholder="All Faculty">
                            <option value="">All Faculty</option>
                            @foreach (($allFaculties ?? []) as $id => $name)
                                <option value="{{ $id }}" {{ isset($facultyFilter) && $facultyFilter == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="fmeCourseFilter" class="mst-form-label d-block">Course</label>
                        <select id="fmeCourseFilter" name="course_filter" class="form-select mst-control mst-searchable" data-placeholder="All Courses">
                            <option value="">All Courses</option>
                            @foreach (($allCourses ?? []) as $id => $name)
                                <option value="{{ $id }}" {{ isset($courseFilter) && $courseFilter == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mst-filter-actions">
                        <button type="submit" class="btn mst-btn-submit px-4">Apply</button>
                        <a href="{{ route('faculty.mdo.escort.exception.view') }}" class="btn programme-dt-btn-reset">Reset Filters</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Everything inside #fmeReport is what printContent() prints. --}}
    <div id="fmeReport">
        @if($isFacultyView)
            <!-- Faculty Login View -->
            @if(isset($hasData) && $hasData && count($studentData) > 0)
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-4">
                        <div class="ds-stat-card h-100">
                            <div>
                                <p class="ds-stat-label">Total Number of Exceptions</p>
                                <div class="ds-stat-value">{{ $totalExceptions ?? 0 }}</div>
                            </div>
                            <span class="ds-stat-icon" aria-hidden="true"><i class="bi bi-clipboard-check"></i></span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="ds-stat-card h-100">
                            <div>
                                <p class="ds-stat-label">Total Students with Exceptions</p>
                                <div class="ds-stat-value">{{ count($studentData) }}</div>
                            </div>
                            <span class="ds-stat-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                        </div>
                    </div>
                </div>

                <div class="card overflow-hidden rounded-3">
                    <div class="card-body p-3 p-md-4">
                        <h2 class="mst-form-section-title h6">Student Exceptions</h2>
                        @php $displayedRows = 0; @endphp
                        <div class="programme-dt-panel">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 w-100 programme-dt-table fme-table">
                                    <caption class="visually-hidden">Escort exceptions of the students in your courses</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col" class="text-nowrap">S. No.</th>
                                            <th scope="col">Student Name</th>
                                            <th scope="col" class="text-nowrap">OT Code</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Faculty</th>
                                            <th scope="col">Course</th>
                                            <th scope="col" class="text-nowrap">Date</th>
                                            <th scope="col" class="text-nowrap">Duty Type</th>
                                            <th scope="col" class="text-nowrap">Time</th>
                                            <th scope="col">Description</th>
                                            <th scope="col" class="text-nowrap text-center">Total Exceptions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($studentData as $student)
                                            @if(count($student['exemptions']) > 0)
                                                @foreach($student['exemptions'] as $exemption)
                                                    @php $displayedRows++; @endphp
                                                    <tr class="{{ $loop->parent->odd ? '' : 'fme-group-alt' }}">
                                                        <td>{{ $displayedRows }}</td>
                                                        <td><strong>{{ $student['student_name'] }}</strong></td>
                                                        <td class="text-nowrap">{{ $student['ot_code'] }}</td>
                                                        <td class="text-nowrap">{{ $student['email'] ?? 'N/A' }}</td>
                                                        <td class="mst-col-wrap">{{ $exemption['faculty'] ?? 'N/A' }}</td>
                                                        <td class="mst-col-wrap">{{ $exemption['course_name'] ?? 'N/A' }}</td>
                                                        <td class="text-nowrap">{{ $exemption['date'] ? \Carbon\Carbon::parse($exemption['date'])->format('d/m/Y') : 'N/A' }}</td>
                                                        <td class="text-nowrap">{{ $exemption['duty_type'] ?? 'N/A' }}</td>
                                                        <td class="text-nowrap">{{ $exemption['time'] ?? 'N/A' }}</td>
                                                        <td class="mst-col-wrap">
                                                            {{ $exemption['description'] && $exemption['description'] !== 'N/A' ? $exemption['description'] : '-' }}
                                                        </td>
                                                        <td class="text-center">
                                                            @if($loop->first)
                                                                <span class="status-pill badge rounded-1 bg-warning-subtle"
                                                                      title="{{ $student['total_exception_count'] }} exception(s) for {{ $student['student_name'] }}">
                                                                    {{ $student['total_exception_count'] }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @endforeach
                                        @if($displayedRows === 0)
                                            <tr class="mst-empty">
                                                <td colspan="11">No records found</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- No records found -->
                <div class="card overflow-hidden rounded-3">
                    <div class="card-body p-3 p-md-4">
                        <div class="ds-empty-state text-center text-muted py-5">
                            <i class="bi bi-info-circle fs-1 d-block mb-2" aria-hidden="true"></i>
                            <p class="mb-0">No records found</p>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <!-- Admin View: faculty → course → students -->
            @if(isset($facultyData) && count($facultyData) > 0)
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-4">
                        <div class="ds-stat-card h-100">
                            <div>
                                <p class="ds-stat-label">Faculty with Exceptions</p>
                                <div class="ds-stat-value">{{ count($facultyData) }}</div>
                            </div>
                            <span class="ds-stat-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="ds-stat-card h-100">
                            <div>
                                <p class="ds-stat-label">Courses</p>
                                <div class="ds-stat-value">{{ count($fmeCourseIds) }}</div>
                            </div>
                            <span class="ds-stat-icon" aria-hidden="true"><i class="bi bi-journal-bookmark"></i></span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="ds-stat-card h-100">
                            <div>
                                <p class="ds-stat-label">Total Exceptions</p>
                                <div class="ds-stat-value">{{ $fmeExceptionTotal }}</div>
                            </div>
                            <span class="ds-stat-icon" aria-hidden="true"><i class="bi bi-clipboard-check"></i></span>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 fme-list-tools">
                    <h2 class="h6 fw-bold mb-0">
                        Exceptions by Faculty
                        @if($fmeHasFilter)
                            <span class="fw-normal text-muted">(filtered)</span>
                        @endif
                    </h2>
                    <div class="d-flex flex-wrap gap-2 no-print">
                        <button type="button" class="btn programme-dt-btn-columns" id="fmeExpandAll" aria-controls="fmeFacultyList">
                            <i class="bi bi-arrows-expand" aria-hidden="true"></i><span>Expand all</span>
                        </button>
                        <button type="button" class="btn programme-dt-btn-columns" id="fmeCollapseAll" aria-controls="fmeFacultyList">
                            <i class="bi bi-arrows-collapse" aria-hidden="true"></i><span>Collapse all</span>
                        </button>
                    </div>
                </div>

                <div id="fmeFacultyList">
                    @foreach($facultyData as $faculty)
                        @php
                            $fmeFacultyTotal = collect($faculty['courses'])->sum('duty_count');
                        @endphp
                        <details class="mst-card fme-faculty" @if($fmeOpenByDefault) open @endif>
                            <summary>
                                <h3 class="fme-faculty__name">{{ $faculty['faculty_name'] }}</h3>
                                <span class="mst-chips">
                                    <span class="mst-chip">{{ count($faculty['courses']) }} {{ \Illuminate\Support\Str::plural('course', count($faculty['courses'])) }}</span>
                                    <span class="status-pill badge rounded-1 bg-warning-subtle align-self-center">{{ $fmeFacultyTotal }} Exception(s)</span>
                                </span>
                                <i class="bi bi-chevron-down fme-chevron" aria-hidden="true"></i>
                            </summary>

                            @foreach($faculty['courses'] as $course)
                                <section class="fme-course" aria-label="{{ $course['course_name'] }}">
                                    <div class="fme-course__head">
                                        <h4 class="fme-course__title">
                                            <i class="bi bi-journal-text text-primary me-1" aria-hidden="true"></i>{{ $course['course_name'] }}
                                        </h4>
                                        <span class="status-pill badge rounded-1 bg-primary-subtle">{{ $course['duty_count'] }} Exception(s)</span>
                                    </div>

                                    @if($course['student_duties'] && count($course['student_duties']) > 0)
                                        <div class="programme-dt-panel">
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0 w-100 programme-dt-table fme-table">
                                                    <caption class="visually-hidden">Escort exceptions — {{ $faculty['faculty_name'] }}, {{ $course['course_name'] }}</caption>
                                                    <thead>
                                                        <tr>
                                                            <th scope="col" class="text-nowrap">S. No.</th>
                                                            <th scope="col">Student Name</th>
                                                            <th scope="col" class="text-nowrap">OT Code</th>
                                                            <th scope="col" class="text-nowrap">Date</th>
                                                            <th scope="col" class="text-nowrap">Duty Type</th>
                                                            <th scope="col" class="text-nowrap">Time</th>
                                                            <th scope="col">Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($course['student_duties'] as $duty)
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>
                                                                <td><strong>{{ $duty['student_name'] }}</strong></td>
                                                                <td class="text-nowrap">{{ $duty['ot_code'] }}</td>
                                                                <td class="text-nowrap">{{ $duty['date'] ? \Carbon\Carbon::parse($duty['date'])->format('d/m/Y') : 'N/A' }}</td>
                                                                <td class="text-nowrap">{{ $duty['duty_type'] }}</td>
                                                                <td class="text-nowrap">{{ $duty['time'] }}</td>
                                                                <td class="mst-col-wrap">{{ $duty['description'] ?? '-' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">No exceptions found for this course.</p>
                                    @endif
                                </section>
                            @endforeach
                        </details>
                    @endforeach
                </div>
            @else
                <div class="card overflow-hidden rounded-3">
                    <div class="card-body p-3 p-md-4">
                        <div class="ds-empty-state text-center text-muted py-5">
                            <i class="bi bi-info-circle fs-1 d-block mb-2" aria-hidden="true"></i>
                            <p class="mb-0">No faculty data found matching the selected filters.</p>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
<script>
    // Print the report (KPIs + tables) in a clean window. Every faculty card is
    // expanded first so the printout always carries all of the rows.
    function printContent() {
        var report = document.getElementById('fmeReport');
        if (!report) {
            return;
        }
        var clone = report.cloneNode(true);
        clone.querySelectorAll('details').forEach(function (d) { d.setAttribute('open', ''); });
        clone.querySelectorAll('.no-print, .bi').forEach(function (el) { el.remove(); });

        var printWindow = window.open('', '', 'width=900,height=600');
        if (!printWindow) {
            return;
        }

        var css = ''
            + '*{box-sizing:border-box}'
            + 'body{font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif;color:#1f2937;background:#fff;margin:0;padding:20px;line-height:1.5;font-size:13px}'
            + 'h1{color:#004384;font-size:20px;margin:0 0 4px}'
            + '.fme-print-meta{color:#667085;margin:0 0 16px}'
            + '.row{display:flex;flex-wrap:wrap;gap:12px;margin:0 0 16px}'
            + '.row>div{flex:1 1 0}'
            + '.ds-stat-card{border:1px solid #d1d5db;border-left:4px solid #004384;border-radius:8px;padding:10px 14px}'
            + '.ds-stat-label{margin:0;font-size:11px;font-weight:600;color:#667085;text-transform:uppercase;letter-spacing:.04em}'
            + '.ds-stat-value{font-size:22px;font-weight:700;color:#004384}'
            + 'h2{font-size:14px;margin:12px 0 8px}'
            + '.card,.mst-card,details{border:1px solid #d1d5db;border-radius:8px;margin:0 0 14px;page-break-inside:avoid;padding:0}'
            + '.card-body{padding:12px}'
            + 'summary{display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:10px 12px;background:#f0f3f7;border-bottom:1px solid #d1d5db;list-style:none}'
            + 'summary::-webkit-details-marker{display:none}'
            + 'h3{margin:0;font-size:15px;color:#004384;flex:1 1 auto}'
            + 'h4{margin:0;font-size:13px}'
            + '.fme-course{padding:10px 12px}'
            + '.fme-course__head{display:flex;justify-content:space-between;align-items:center;margin:0 0 8px}'
            + '.mst-chip,.badge{display:inline-block;border:1px solid #d1d5db;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:600;margin-left:6px}'
            + 'table{width:100%;border-collapse:collapse}'
            + 'th{background:#f0f3f7;color:#004384;font-size:11px;text-transform:uppercase;letter-spacing:.04em;text-align:left;padding:6px 8px;border:1px solid #d1d5db}'
            + 'td{padding:6px 8px;border:1px solid #e5e7eb;vertical-align:top}'
            + 'tr{page-break-inside:avoid}'
            + '.visually-hidden{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}'
            + '.text-center{text-align:center}'
            + '.text-muted{color:#667085}';

        var title = 'Faculty MDO/Escort Exception Report';
        printWindow.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' + title + '</title><style>' + css + '</style></head><body>'
            + '<h1>' + title + '</h1>'
            + '<p class="fme-print-meta">Printed ' + new Date().toLocaleString() + '</p>'
            + clone.innerHTML
            + '</body></html>');
        printWindow.document.close();

        // Wait for content to load, then print
        setTimeout(function () {
            printWindow.focus();
            printWindow.print();
        }, 250);
    }

    $(document).ready(function () {
        // Course filter handler for faculty view (applies on change, as before;
        // the Apply button submits the same parameter).
        if ($('#course_filter').length > 0) {
            $('#course_filter').on('change', function () {
                var courseFilter = $(this).val();
                var url = new URL(window.location.href);

                if (courseFilter) {
                    url.searchParams.set('course_filter', courseFilter);
                } else {
                    url.searchParams.delete('course_filter');
                }

                window.location.href = url.toString();
            });
        }

        $('#fmeExpandAll').on('click', function () {
            $('#fmeFacultyList details').attr('open', '');
        });
        $('#fmeCollapseAll').on('click', function () {
            $('#fmeFacultyList details').removeAttr('open');
        });
    });
</script>
@endpush
