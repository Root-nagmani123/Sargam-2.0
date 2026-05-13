@extends('admin.layouts.master')

@section('title', 'Attendance')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
<style>
    /* --- Filter toolbar --- */
    .att-filter-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .att-filter-row .btn-outline-secondary { font-size: 0.8125rem; border-radius: 6px; color: #495057; padding: 5px 14px; background: #fff; }
    .att-reset-btn { border: 1.5px solid #dc3545; color: #dc3545; background: transparent; border-radius: 6px; font-size: 0.8125rem; padding: 5px 14px; font-weight: 500; white-space: nowrap; }
    .att-reset-btn:hover { background: #dc3545; color: #fff; }
    .att-search-btn { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .att-search-btn:hover { background: #e9ecef; }
    /* --- Table --- */
    #attendanceTable thead th { background-color: #f8f9fa; font-size: 0.8125rem; font-weight: 600; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 12px 14px; white-space: nowrap; }
    #attendanceTable tbody td { font-size: 0.875rem; padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid #f1f3f5; color: #212529; }
    #attendanceTable tbody tr:hover td { background-color: #fafbfc; }
    /* --- Pagination --- */
    .att-pagination .page-link { color: #1b3a5c; border-radius: 4px !important; margin: 0 2px; border: none; background: transparent; font-size: 0.8125rem; padding: 5px 10px; }
    .att-pagination .page-link:hover { background: #f1f3f5; }
    .att-pagination .page-item.active .page-link { background-color: #1b3a5c; border-color: #1b3a5c; color: #fff; }
    .att-pagination .page-item.disabled .page-link { opacity: .35; }
    /* --- Choices.js scoped to filter bar --- */
    .att-course-wrap { width: 210px; flex-shrink: 0; }
    .att-course-wrap .choices { width: 100% !important; margin: 0; }
    .att-course-wrap .choices__inner { font-size: 0.8125rem !important; border-radius: 6px !important; border: 1px solid #dee2e6 !important; min-height: 31px !important; padding: 3px 8px !important; background-image: none !important; }
    .att-course-wrap .choices__list--single { padding: 0 !important; line-height: 1.5; }
    .att-course-wrap .choices[data-type*="select-one"]::after { top: 12px !important; }
    .att-course-wrap .choices__placeholder { opacity: .6; }
    /* Session selects inside dropdown */
    .att-type-dropdown .choices { width: 100% !important; }
    .att-type-dropdown .choices__inner { font-size: 0.8125rem !important; border-radius: 6px !important; }
    /* z-index for choices dropdown */
    .choices__list--dropdown { z-index: 10000 !important; }
    /* --- Action icon btn --- */
    .att-icon-btn { display: inline-flex; align-items: center; justify-content: center; background: none; border: none; padding: 4px 8px; border-radius: 6px; text-decoration: none; transition: background .15s; cursor:pointer; }
    .att-icon-btn:hover { background: #eef2f7; }
    /* Course cell tooltip */
    #attendanceTable .attendance-course-cell { cursor: help; text-decoration: underline dotted; text-underline-offset: 0.15em; }
    #attendanceCustomTooltip { position: fixed; z-index: 9999; display: none; pointer-events: none; max-width: 360px; background: rgba(0,0,0,.88); color: #fff; padding: 8px 10px; border-radius: 8px; font-size: 12.5px; line-height: 1.25; box-shadow: 0 10px 24px rgba(0,0,0,.25); word-break: break-word; white-space: normal; }
    @media (max-width: 767.98px) {
        #attendanceTableDiv { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        #attendanceTableDiv::-webkit-scrollbar { width: 6px; height: 6px; }
        #attendanceTableDiv::-webkit-scrollbar-button { display: none; width: 0; height: 0; }
        #attendanceTableDiv::-webkit-scrollbar-thumb { background-color: rgba(33,37,41,.35); border-radius: 999px; }
        #attendanceTableDiv thead th { position: sticky; top: 0; z-index: 2; background-color: #f8f9fa; box-shadow: inset 0 -1px 0 #dee2e6; }
    }
    @media print { .no-print { display: none !important; } }
</style>

<div class="container-fluid py-3 px-3 px-lg-4 attendance-page">
    <x-breadcrum title="Attendance" />

    {{-- Top toolbar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3 mb-3 no-print">
        <button type="button" class="btn btn-outline-primary text-decoration-none d-inline-flex align-items-center gap-1" id="attDownloadBtn">
            <span class="material-symbols-rounded" style="font-size:18px;">download</span>
            <span class="fw-semibold">Download</span>
        </button>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-3 p-lg-4">

            {{-- Filter bar --}}
            <div class="att-filter-row mb-3 no-print" id="attendanceFilterBar">
                <span class="text-muted fw-semibold small">Filters</span>

                {{-- Course Name --}}
                <div class="att-course-wrap">
                    <select name="course_master_pk" id="programme" class="form-select form-select-sm js-attendance-choice" required>
                        <option value="">Course Name</option>
                        @foreach($courseMasters as $course)
                        @php
                            $courseFullName = trim((string) ($course['course_name'] ?? ''));
                            $courseShortName = trim((string) ($course['couse_short_name'] ?? $course['course_short_name'] ?? ''));
                            $courseLabel = $courseShortName !== '' ? $courseShortName : $courseFullName;
                        @endphp
                        <option value="{{ $course['pk'] }}"
                            title="{{ $courseFullName !== '' ? $courseFullName : $courseLabel }}"
                            {{ count($courseMasters) === 1 ? 'selected' : '' }}>
                            {{ $courseLabel }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Attendance Type dropdown --}}
                <div class="dropdown att-type-dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" id="attTypeDropdownBtn"
                        data-bs-auto-close="outside">
                        Attendance Type
                    </button>
                    <div class="dropdown-menu p-3" style="min-width:260px;">
                        <div class="d-flex flex-wrap gap-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="attendance_type" id="full_day" value="full_day" checked>
                                <label class="form-check-label small" for="full_day">Full Day</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="attendance_type" id="manual" value="manual">
                                <label class="form-check-label small" for="manual">Manual</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="attendance_type" id="normal" value="normal">
                                <label class="form-check-label small" for="normal">Normal</label>
                            </div>
                        </div>
                        {{-- Normal Session --}}
                        <div id="normal_session_container" style="display:none;" class="mt-2">
                            <label for="session" class="form-label small fw-semibold mb-1">Normal Session</label>
                            <select name="session" id="session" class="form-select form-select-sm js-attendance-choice">
                                <option value="">Select Session</option>
                                @foreach($sessions as $session)
                                <option value="{{ $session['pk'] }}">{{ $session['shift_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Manual Session --}}
                        <div id="manual_session_container" style="display:none;" class="mt-2">
                            <label for="manual_session" class="form-label small fw-semibold mb-1">Manual Session</label>
                            <select name="manual_session" id="manual_session" class="form-select form-select-sm js-attendance-choice">
                                <option value="">Select Session</option>
                                @foreach($maunalSessions as $maunalSession)
                                <option value="{{ $maunalSession['class_session'] }}">{{ $maunalSession['class_session'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Time Period dropdown --}}
                <div class="dropdown" data-bs-auto-close="outside">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                        Time Period
                    </button>
                    <div class="dropdown-menu p-3" style="min-width:300px;">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">From</label>
                            <input type="date" class="form-control form-control-sm" id="from_date" name="from_date" value="{{ date('Y-m-d') }}">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">To</label>
                            <input type="date" class="form-control form-control-sm" id="to_date" name="to_date" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                </div>

                <button type="button" class="att-reset-btn" id="resetAttendance">Reset Filters</button>
                <button type="button" class="att-search-btn ms-auto" id="attSearchBtn" title="Apply filters">
                    <span class="material-symbols-rounded" style="font-size:18px;color:#6c757d;">search</span>
                </button>
            </div>

            {{-- Table --}}
            <div id="attendanceTableCard">
                <div id="attendanceTableDiv" class="table-responsive">
                    <table id="attendanceTable" class="table align-middle table-attendance w-100 mb-0">
                        <thead>
                            <tr>
                                <th style="width:55px;">#</th>
                                <th>Topic</th>
                                <th>Date</th>
                                <th>Session</th>
                                <th>Venue</th>
                                <th>Group</th>
                                <th>Course Name</th>
                                <th>Faculty</th>
                                <th style="width:80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(hasRole('Admin') || hasRole('Training-Induction'))
                            <tr id="defaultMessageRow">
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <span class="material-symbols-rounded d-block mb-2" style="font-size:2.5rem;opacity:.35;">tune</span>
                                        <p class="mb-1 fw-semibold small">Apply filters to mark attendance.</p>
                                        <p class="mb-0 small">Choose course, dates, and attendance type to load sessions.</p>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @if(hasRole('Internal Faculty'))
                            <tr id="defaultMessageRow">
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <span class="material-symbols-rounded d-block mb-2" style="font-size:2.5rem;opacity:.35;">tune</span>
                                        <p class="mb-1 fw-semibold small">Apply filters to view attendance.</p>
                                        <p class="mb-0 small">Select the required details to display attendance records.</p>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="attendanceCustomTooltip" role="tooltip" aria-hidden="true"></div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
$(document).ready(function() {
    if (typeof Choices !== 'undefined') {
        var attendanceChoiceOpts = {
            searchEnabled: true,
            shouldSort: false,
            itemSelectText: '',
            allowHTML: false,
            classNames: {
                containerOuter: ['choices', 'w-100'],
                containerInner: ['choices__inner', 'form-select', 'shadow-sm'],
                input: ['choices__input', 'form-control', 'form-control-sm', 'border-0', 'shadow-none', 'my-1'],
                inputCloned: ['choices__input--cloned'],
                list: ['choices__list'],
                listItems: ['choices__list--multiple'],
                listSingle: ['choices__list--single'],
                listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
                item: ['choices__item', 'dropdown-item', 'rounded-0'],
                itemSelectable: ['choices__item--selectable'],
                itemDisabled: ['choices__item--disabled', 'disabled'],
                itemChoice: ['choices__item--choice'],
                description: ['choices__description', 'small', 'text-muted'],
                placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
                group: ['choices__group'],
                groupHeading: ['choices__heading', 'dropdown-header', 'text-uppercase', 'small'],
                button: ['choices__button'],
                activeState: ['is-active'],
                focusState: ['is-focused'],
                openState: ['is-open'],
                disabledState: ['is-disabled'],
                highlightedState: ['is-highlighted', 'active'],
                flippedState: ['is-flipped'],
                loadingState: ['is-loading'],
                invalidState: ['is-invalid'],
                notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small', 'py-2'],
                addChoice: ['choices__item--selectable', 'add-choice'],
                noResults: ['has-no-results'],
                noChoices: ['has-no-choices'],
            }
        };
        document.querySelectorAll('.js-attendance-choice').forEach(function(el) {
            if (el.dataset.choicesInitialized === 'true') return;
            el._choicesInstance = new Choices(el, attendanceChoiceOpts);
            el.dataset.choicesInitialized = 'true';
        });
    }

    // Set today's date if not already set
    let today = new Date().toISOString().split('T')[0];
    if (!$('#from_date').val()) $('#from_date').val(today);
    if (!$('#to_date').val()) $('#to_date').val(today);

    // Update Attendance Type dropdown button label on radio change
    $('input[name="attendance_type"]').on('change', function() {
        var labels = { full_day: 'Full Day', manual: 'Manual', normal: 'Normal' };
        $('#attTypeDropdownBtn').text(labels[$(this).val()] || 'Attendance Type');
    });

    // Search button triggers apply
    $('#attSearchBtn').on('click', function() {
        if (typeof performAttendanceSearch === 'function') performAttendanceSearch();
    });

    // Auto-trigger search on page load if dates are set
    let fromDate = $('#from_date').val();
    let toDate = $('#to_date').val();
    if (fromDate && toDate) {
        setTimeout(function() {
            performAttendanceSearch();
        }, 100);
    }
});
</script>

<script>
(function () {
    const tooltip = document.getElementById('attendanceCustomTooltip');
    if (!tooltip) return;

    let rafId = null;
    let visible = false;

    function placeTooltip(x, y) {
        const offset = 14;
        let left = x + offset;
        let top = y + offset;
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
        const rect = tooltip.getBoundingClientRect();
        if (rect.right > window.innerWidth - 8) {
            left = x - rect.width - offset;
            tooltip.style.left = left + 'px';
        }
        if (rect.bottom > window.innerHeight - 8) {
            top = y - rect.height - offset;
            tooltip.style.top = top + 'px';
        }
    }

    $(document).on('mouseenter', '.attendance-course-cell', function (e) {
        const content = this.getAttribute('data-tooltip');
        if (!content) return;
        tooltip.textContent = content;
        tooltip.style.display = 'block';
        tooltip.setAttribute('aria-hidden', 'false');
        visible = true;
        placeTooltip(e.clientX, e.clientY);
    });

    $(document).on('mousemove', '.attendance-course-cell', function (e) {
        if (!visible) return;
        if (rafId) return;
        rafId = requestAnimationFrame(function () {
            rafId = null;
            placeTooltip(e.clientX, e.clientY);
        });
    });

    $(document).on('mouseleave', '.attendance-course-cell', function () {
        tooltip.style.display = 'none';
        tooltip.setAttribute('aria-hidden', 'true');
        visible = false;
    });

    $(window).on('scroll', function () {
        if (!visible) return;
        tooltip.style.display = 'none';
        tooltip.setAttribute('aria-hidden', 'true');
        visible = false;
    });
})();
</script>
@endsection