@extends('admin.layouts.master')

@section('title', 'Attendance')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
<style>
.form-label {
    font-size: 0.92rem;
    color: #333;
    /* High contrast */
}

.form-control,
.form-select {
    min-height: 40px;
    border-radius: 6px;
}

.form-control:focus,
.form-select:focus {
    outline: 3px solid #0059b3 !important;
    /* Visible focus outline for accessibility */
    outline-offset: 2px;
}

.btn-primary {
    background-color: #004a93;
    border-color: #004a93;
}

.btn-primary:focus {
    outline: 3px solid #003366;
    outline-offset: 2px;
}

h4 {
    color: #003366;
    letter-spacing: 0.3px;
}

hr {
    border-top: 1px solid #dce1e7;
}

/* Choices + Bootstrap: form-select adds BS chevron; Choices adds its own — drop BS background only. */
.attendance-choices-bootstrap .choices__inner.form-select {
    background-color: var(--bs-body-bg);
    border: var(--bs-border-width) solid var(--bs-border-color);
    min-height: calc(1.5em + 0.75rem + var(--bs-border-width) * 2);
    padding-top: 0.375rem;
    padding-bottom: 0.375rem;
    background-image: none !important;
    padding-inline-end: 2.25rem;
}

.attendance-choices-bootstrap .choices.is-focused .choices__inner.form-select,
.attendance-choices-bootstrap .choices.is-open .choices__inner.form-select {
    border-color: var(--bs-focus-border-color);
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-focus-ring-rgb), 0.25);
}

/* Panel border; scrolling is on the inner .choices__list (library default). */
.attendance-choices-bootstrap .choices__list--dropdown.dropdown-menu,
.attendance-choices-bootstrap .choices__list[aria-expanded].dropdown-menu {
    border: var(--bs-border-width) solid var(--bs-border-color);
}

.attendance-choices-bootstrap .choices__list--dropdown.dropdown-menu .choices__list,
.attendance-choices-bootstrap .choices__list[aria-expanded].dropdown-menu .choices__list {
    max-height: 280px;
}

/* Smaller screens: hide closed-state caret; taller list + touch scroll + thin scrollbar (no arrow buttons). */
@media (max-width: 767.98px) {

    .attendance-choices-bootstrap .choices[data-type*="select-one"]::after,
    .attendance-choices-bootstrap .choices[data-type*="select-one"]::before {
        display: none !important;
    }

    .attendance-choices-bootstrap .choices__inner.form-select {
        padding-inline-end: 0.75rem;
    }

    .attendance-choices-bootstrap .choices__list--dropdown.dropdown-menu .choices__list,
    .attendance-choices-bootstrap .choices__list[aria-expanded].dropdown-menu .choices__list {
        max-height: min(50vh, 20rem) !important;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-y: contain;
        scrollbar-width: thin;
        scrollbar-color: rgba(33, 37, 41, 0.35) transparent;
    }

    .attendance-choices-bootstrap .choices__list--dropdown.dropdown-menu .choices__list::-webkit-scrollbar,
    .attendance-choices-bootstrap .choices__list[aria-expanded].dropdown-menu .choices__list::-webkit-scrollbar {
        width: 6px;
    }

    .attendance-choices-bootstrap .choices__list--dropdown.dropdown-menu .choices__list::-webkit-scrollbar-button,
    .attendance-choices-bootstrap .choices__list[aria-expanded].dropdown-menu .choices__list::-webkit-scrollbar-button {
        display: none;
        height: 0;
        width: 0;
    }

    .attendance-choices-bootstrap .choices__list--dropdown.dropdown-menu .choices__list::-webkit-scrollbar-thumb,
    .attendance-choices-bootstrap .choices__list[aria-expanded].dropdown-menu .choices__list::-webkit-scrollbar-thumb {
        background-color: rgba(33, 37, 41, 0.35);
        border-radius: 999px;
    }

    /* Attendance DataTable: scroll vertically (and horizontally); avoid Responsive “+ / chevron” row controls. */

    #attendanceTableDiv::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    #attendanceTableDiv::-webkit-scrollbar-button {
        display: none;
        width: 0;
        height: 0;
    }

    #attendanceTableDiv::-webkit-scrollbar-thumb {
        background-color: rgba(33, 37, 41, 0.35);
        border-radius: 999px;
    }

    #attendanceTableDiv thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: var(--bs-body-bg);
        box-shadow: inset 0 -1px 0 var(--bs-border-color);
    }
}

/* Course column: short label, full name via native tooltip */
#attendanceTable .attendance-course-cell {
    cursor: help;
    text-decoration: underline dotted;
    text-underline-offset: 0.15em;
}

/* Custom tooltip (course full name) */
#attendanceCustomTooltip {
    position: fixed;
    z-index: 9999;
    display: none;
    pointer-events: none;
    max-width: 360px;
    background: rgba(0, 0, 0, 0.88);
    color: #fff;
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 12.5px;
    line-height: 1.25;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
    word-break: break-word;
    white-space: normal;
}
</style>


<div class="container-fluid">
    <x-breadcrum title="Attendance" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body py-4">

            <!-- Title -->
            <div class="row align-items-center mb-2">
                <div class="col-12">
                    <h4 class="fw-bold text-primary mb-0">Attendance</h4>
                </div>
            </div>

            <hr class="mt-3 mb-4">

            <!-- Filter Rows -->
            <div class="row g-4 attendance-choices-bootstrap">

                <!-- Course -->
                <div class="col-md-3">
                    <label for="programme" class="form-label fw-semibold">Course Name</label>
                    <select name="course_master_pk" id="programme" class="form-select shadow-sm js-attendance-choice" required>
                        <option value="">Select Course</option>
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

                <!-- From Date -->
                <div class="col-md-3">
                    <label for="from_date" class="form-label fw-semibold">From Date</label>
                    <input type="date" class="form-control shadow-sm" id="from_date" name="from_date"
                        placeholder="From Date" value="{{ date('Y-m-d') }}">
                </div>

                <!-- To Date -->
                <div class="col-md-3">
                    <label for="to_date" class="form-label fw-semibold">To Date</label>
                    <input type="date" class="form-control shadow-sm" id="to_date" name="to_date" placeholder="To Date" value="{{ date('Y-m-d') }}">
                </div>

                <!-- Attendance Type -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Attendance Type</label>
                    <div class="d-flex flex-wrap gap-3">

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="attendance_type" id="full_day"
                                value="full_day" checked>
                            <label class="form-check-label" for="full_day">Full Day</label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="attendance_type" id="manual"
                                value="manual">
                            <label class="form-check-label" for="manual">Manual</label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="attendance_type" id="normal"
                                value="normal">
                            <label class="form-check-label" for="normal">Normal</label>
                        </div>

                    </div>
                </div>

                <!-- Normal Session -->
                <div class="col-md-3" id="normal_session_container" style="display:none;">
                    <label for="session" class="form-label fw-semibold">Normal Session</label>
                    <select name="session" id="session" class="form-select shadow-sm js-attendance-choice">
                        <option value="">Select Session</option>
                        @foreach($sessions as $session)
                        <option value="{{ $session['pk'] }}">{{ $session['shift_name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Manual Session -->
                <div class="col-md-3" id="manual_session_container" style="display:none;">
                    <label for="manual_session" class="form-label fw-semibold">Manual Session</label>
                    <select name="manual_session" id="manual_session" class="form-select shadow-sm js-attendance-choice">
                        <option value="">Select Session</option>
                        @foreach($maunalSessions as $maunalSession)
                        <option value="{{ $maunalSession['class_session'] }}">
                            {{ $maunalSession['class_session'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="mt-4">

            <!-- Reset Button -->
            <div class="text-end mb-4">
                <button class="btn btn-secondary px-4 py-2 shadow-sm d-inline-flex align-items-center"
                    id="resetAttendance" type="button">
                    <span class="material-symbols-rounded me-2 fs-6">refresh</span>
                    Reset
                </button>
            </div>

            <div id="attendanceTableCard">
                <div id="attendanceTableDiv">
                    <table id="attendanceTable" class="table w-100">
                        <thead>
                            <tr>
                                <th class="col">#</th>
                                <th class="col">Topic</th>
                                <th class="col">Date</th>
                                <th class="col">Session</th>
                                <th class="col">Venue</th>
                                <th class="col">Group</th>
                                <th class="col">Course Name</th>
                                <th class="col">Faculty</th>
                                <th class="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(hasRole('Admin') || hasRole('Training-Induction'))
                            <tr id="defaultMessageRow">
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <p class="mb-2" style="font-size: 1rem;">Apply filter to mark attendance.</p>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @if(hasRole('Internal Faculty'))
                            <tr id="defaultMessageRow">
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <p class="mb-2" style="font-size: 1rem;">Apply filter to see attendance.</p>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

            <div id="attendanceCustomTooltip" role="tooltip" aria-hidden="true"></div>
        @endsection

        @section('scripts')
        <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
        <script>
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
                    if (el.dataset.choicesInitialized === 'true') {
                        return;
                    }
                    el._choicesInstance = new Choices(el, attendanceChoiceOpts);
                    el.dataset.choicesInitialized = 'true';
                });
            }

            // Set today's date if not already set
            let today = new Date().toISOString().split('T')[0];
            if (!$('#from_date').val()) {
                $('#from_date').val(today);
            }
            if (!$('#to_date').val()) {
                $('#to_date').val(today);
            }
            
            // Auto-trigger search on page load if dates are set
            let fromDate = $('#from_date').val();
            let toDate = $('#to_date').val();
            if (fromDate && toDate) {
                // Small delay to ensure all elements are initialized
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