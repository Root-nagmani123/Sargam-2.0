@extends('admin.layouts.master')

@section('title', 'Edit Event')

@section('setup_content')

<div class="container-fluid cal-create-event-page py-2">

    <x-breadcrum title="Edit Event" :items="[
        ['label' => 'Home', 'url' => url('/')],
        ['label' => 'Time Table'],
        ['label' => 'Calendar', 'url' => route('calendar.index')],
        ['label' => 'Edit Event'],
    ]" />

    @if($errors->any())
        <div class="alert alert-danger rounded-3 shadow-sm" role="alert">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="editEventForm" method="POST" action="{{ route('calendar.event.update', encrypt($event->pk)) }}" novalidate>
        @csrf

        {{-- ============ Basic Information ============ --}}
        <section class="card cal-ce-card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <h2 class="cal-ce-section-title h6 fw-bold mb-3">Basic Information</h2>
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <label for="start_datetime" class="form-label required">Date</label>
                        <input type="date" name="start_datetime" id="start_datetime"
                            class="form-control" required
                            value="{{ old('start_datetime', \Carbon\Carbon::parse($event->START_DATE)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label for="Course_name" class="form-label required">Course Name</label>
                        <select name="Course_name" id="Course_name" class="form-select" required>
                            <option value="">Select Course Name</option>
                            @foreach($courseMaster as $course)
                                <option value="{{ $course->pk }}"
                                    @selected(old('Course_name', $event->course_master_pk) == $course->pk)>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label for="group_type" class="form-label required">Group Type</label>
                        <select name="group_type" id="group_type" class="form-select" required>
                            <option value="">Select Group Type</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label required">Group Type Name</label>
                        <div id="type_name_container" class="cal-ce-group-names border rounded-2 p-3">
                            <div class="text-center text-muted small py-2" id="groupTypePlaceholder">
                                Select a Group Type first
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="subject_module" class="form-label required">Module Name</label>
                        <select name="subject_module" id="subject_module" class="form-select" required>
                            <option value="">Select Module Name</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->pk }}"
                                    @selected(old('subject_module', $event->subject_module_master_pk) == $subject->pk)>
                                    {{ $subject->module_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="subject_name" class="form-label required">Subject Name</label>
                        <select name="subject_name" id="subject_name" class="form-select" required>
                            <option value="">Select Subject Name</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="sector" class="form-label">Sector Name</label>
                        <select name="sector" id="sector" class="form-select">
                            <option value="">Select Sector</option>
                            @foreach($sectors as $sector)
                                <option value="{{ $sector->pk }}"
                                    @selected(old('sector', $event->sector_pk) == $sector->pk)>
                                    {{ $sector->sector_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="topic" class="form-label required">Topic</label>
                        <input type="text" name="topic" id="topic" class="form-control"
                            placeholder=""
                            value="{{ old('topic', $event->subject_topic) }}">
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ Venue ============ --}}
        <section class="card cal-ce-card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <h2 class="cal-ce-section-title h6 fw-bold mb-3">Venue</h2>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="vanue" class="form-label required">Location</label>
                        <select name="vanue" id="vanue" class="form-select" required>
                            <option value="">Select Location</option>
                            @foreach($venueMaster as $loc)
                                <option value="{{ $loc->venue_id }}"
                                    @selected(old('vanue', $event->venue_id) == $loc->venue_id)>
                                    {{ $loc->venue_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ Faculty (dynamic rows) ============ --}}
        <section class="card cal-ce-card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <h2 class="cal-ce-section-title h6 fw-bold mb-3">Faculty</h2>
                <div id="facultyRows">
                    {{-- rows injected by JS from event data --}}
                </div>
                <div class="d-flex justify-content-end mt-2">
                    <button type="button" id="btnAddFaculty"
                        class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1 rounded-2">
                        <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
                        <span>Add Faculty</span>
                    </button>
                </div>
            </div>
        </section>

        {{-- ============ Schedule ============ --}}
        <section class="card cal-ce-card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <h2 class="cal-ce-section-title h6 fw-bold mb-3">Schedule</h2>
                <div class="row g-3">
                    <div class="col-12">
                        <span class="form-label required d-block">Shift Name</span>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check mb-0">
                                <input type="radio" name="shift_type" id="normalShift" value="1"
                                    class="form-check-input"
                                    @checked(old('shift_type', $event->session_type) == '1' || old('shift_type', $event->session_type) == 1)>
                                <label class="form-check-label" for="normalShift">Normal Shift</label>
                            </div>
                            <div class="form-check mb-0">
                                <input type="radio" name="shift_type" id="manualShift" value="2"
                                    class="form-check-input"
                                    @checked(old('shift_type', $event->session_type) == '2' || old('shift_type', $event->session_type) == 2)>
                                <label class="form-check-label" for="manualShift">Manual Shift</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12" id="shiftSelectWrap">
                        <label for="shift" class="form-label required">Shift</label>
                        <select name="shift" id="shift" class="form-select">
                            <option value="">Select Shift</option>
                            @foreach($classSessionMaster as $shift)
                                <option value="{{ $shift->shift_time }}"
                                    @selected(old('shift', $event->class_session) == $shift->shift_time)>
                                    {{ $shift->shift_name }} ({{ $shift->shift_time }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 d-none" id="manualShiftFields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="start_time" class="form-label required">Start Time</label>
                                @php
                                    $manualStart = '';
                                    $manualEnd   = '';
                                    if ($event->session_type == 2 && $event->class_session) {
                                        $parts = explode(' - ', $event->class_session, 2);
                                        try { $manualStart = \Carbon\Carbon::parse(trim($parts[0]))->format('H:i'); } catch (\Throwable $e) {}
                                        try { $manualEnd   = \Carbon\Carbon::parse(trim($parts[1] ?? ''))->format('H:i'); } catch (\Throwable $e) {}
                                    }
                                @endphp
                                <input type="time" name="start_time" id="start_time" class="form-control"
                                    value="{{ old('start_time', $manualStart) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="end_time" class="form-label required">End Time</label>
                                <input type="time" name="end_time" id="end_time" class="form-control"
                                    value="{{ old('end_time', $manualEnd) }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Break (Tea/Lunch/Snacks) --}}
                <hr class="my-4">
                <h3 class="cal-ce-subsection-title h6 fw-semibold mb-3">Break (Tea/Lunch/Snacks)</h3>
                <div class="row g-3">
                    <div class="col-12">
                        <span class="form-label d-block">Break Type</span>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check mb-0">
                                <input type="radio" name="break_type" id="breakTea" value="tea"
                                    class="form-check-input"
                                    @checked(old('break_type', $event->break_type) == 'tea')>
                                <label class="form-check-label" for="breakTea">Tea</label>
                            </div>
                            <div class="form-check mb-0">
                                <input type="radio" name="break_type" id="breakLunch" value="lunch"
                                    class="form-check-input"
                                    @checked(old('break_type', $event->break_type) == 'lunch')>
                                <label class="form-check-label" for="breakLunch">Lunch</label>
                            </div>
                            <div class="form-check mb-0">
                                <input type="radio" name="break_type" id="breakSnacks" value="snacks"
                                    class="form-check-input"
                                    @checked(old('break_type', $event->break_type) == 'snacks')>
                                <label class="form-check-label" for="breakSnacks">Snacks</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="break_start_time" class="form-label">Break Start Time</label>
                        <input type="time" name="break_start_time" id="break_start_time" class="form-control"
                            value="{{ old('break_start_time', $event->break_start_time) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="break_end_time" class="form-label">Break End Time</label>
                        <input type="time" name="break_end_time" id="break_end_time" class="form-control"
                            value="{{ old('break_end_time', $event->break_end_time) }}">
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ Additional Options ============ --}}
        <section class="card cal-ce-card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <h2 class="cal-ce-section-title h6 fw-bold mb-3">Additional Options</h2>
                <div class="cal-ce-option-row d-flex align-items-center justify-content-between border rounded-2 px-3 py-2">
                    <span class="fw-medium">Bio Attendance</span>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="bio_attendanceCheckbox"
                            name="bio_attendanceCheckbox" value="1"
                            @checked(old('bio_attendanceCheckbox') !== null ? old('bio_attendanceCheckbox') : $event->Bio_attendance)>
                        <label class="form-check-label visually-hidden" for="bio_attendanceCheckbox">Bio Attendance</label>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ Footer actions ============ --}}
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('calendar.index') }}" class="btn btn-outline-secondary rounded-2 px-4">Cancel</a>
            <button type="submit" class="btn btn-primary rounded-2 px-4">Update Event</button>
        </div>
    </form>
</div>

{{-- Faculty row template --}}
<template id="facultyRowTemplate">
    <div class="cal-ce-faculty-row border rounded-3 p-3 mb-3" data-faculty-row>
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label required">Faculty Name</label>
                <select name="faculty[__IDX__]" class="form-select cal-ce-faculty-select" required>
                    <option value="">Select Faculty</option>
                    @foreach($facultyMaster as $faculty)
                        <option value="{{ $faculty->pk }}" data-faculty_type="{{ $faculty->faculty_type }}">{{ $faculty->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label required">Faculty Type</label>
                <select name="faculty_row_type[__IDX__]" class="form-select cal-ce-faculty-type" required>
                    <option value="">Select Faculty Type</option>
                    <option value="1">Internal</option>
                    <option value="2">Guest</option>
                    <option value="3">Research</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label required">Role</label>
                <select name="faculty_role[__IDX__]" class="form-select" required>
                    <option value="">Select Role</option>
                    @foreach($facultyRoles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="d-flex flex-wrap align-items-center gap-3 cal-ce-feedback-wrap" data-feedback-wrap
                          title="Feedback is available for the Teaching role only">
                        <span class="text-muted small fw-medium"><b>Feedback:</b></span>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="faculty_feedback_remark[__IDX__]" id="fb_remark___IDX__" value="remark">
                            <label class="form-check-label" for="fb_remark___IDX__">Remark</label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="faculty_feedback_rating[__IDX__]" id="fb_rating___IDX__" value="rating">
                            <label class="form-check-label" for="fb_rating___IDX__">Rating</label>
                        </div>
                    </span>
                    <button type="button" class="btn btn-outline-danger btn-sm ms-auto cal-ce-remove-faculty d-inline-flex align-items-center justify-content-center" title="Remove faculty" aria-label="Remove faculty">
                        <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">remove</i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<style>
    .cal-ce-section-title { color: #004a93; }
    .cal-create-event-page .choices { margin-bottom: 0; }
    .cal-create-event-page .choices__inner {
        min-height: calc(1.5em + .75rem + 2px);
        padding: .375rem .75rem;
        background: #fff;
        border: 1px solid var(--bs-border-color, #ced4da);
        border-radius: .5rem;
        font-size: 1rem;
    }
    .cal-create-event-page .choices__list--dropdown,
    .cal-create-event-page .choices__list[aria-expanded] { z-index: 1100; }
    .cal-create-event-page .is-focused .choices__inner {
        border-color: #86b7fe;
        box-shadow: 0 0 0 .25rem rgba(13,110,253,.25);
    }
    .cal-ce-subsection-title { color: #1f2937; }
    .cal-ce-card { background: #fff; }
    .cal-create-event-page .form-label.required::after { content: " *"; color: #dc3545; }
    .cal-ce-faculty-row { background: #f6f9fe; }
    .cal-create-event-page .form-control,
    .cal-create-event-page .form-select { border-radius: .5rem; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
(function () {
    const API = {
        groupTypes:   "{{ route('calendar.get.group.types') }}",
        subjectNames: "{{ route('calendar.get.subject.name') }}",
    };

    {{-- Pre-fill data from saved event (old() takes priority on validation failure) --}}
    const eventData = {!! \Illuminate\Support\Js::from([
        'course_master_pk'          => $event->course_master_pk,
        'course_group_type_master'  => $event->course_group_type_master,
        'group_name'                => $event->group_name,
        'subject_module_master_pk'  => $event->subject_module_master_pk,
        'subject_master_pk'         => $event->subject_master_pk,
        'faculty_details'           => $event->faculty_details ?: $event->Faculty_feedback,
        'faculty_master'            => $event->faculty_master,
        'faculty_type'              => $event->faculty_type,
        'class_session'             => $event->class_session,
        'session_type'              => $event->session_type,
    ]) !!};

    const oldData = {!! \Illuminate\Support\Js::from([
        'faculty'          => old('faculty'),
        'faculty_row_type' => old('faculty_row_type'),
        'faculty_role'     => old('faculty_role'),
        'faculty_feedback_remark' => old('faculty_feedback_remark'),
        'faculty_feedback_rating' => old('faculty_feedback_rating'),
        'type_names'       => old('type_names'),
        'group_type'       => old('group_type'),
        'subject_name'     => old('subject_name'),
    ]) !!};

    /* ---------------- Choices.js helpers ---------------- */
    function initChoices(el) {
        if (!el || typeof window.Choices === 'undefined' || el._choices) return el ? el._choices : null;
        el._choices = new Choices(el, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search...',
            shouldSort: false,
            itemSelectText: '',
            allowHTML: false,
            placeholder: true,
            classNames: { containerInner: ['choices__inner', 'form-select'] },
        });
        return el._choices;
    }

    function setSelectValue(el, value) {
        if (!el) return;
        if (el._choices) {
            el._choices.setChoiceByValue(String(value));
        } else {
            el.value = value;
        }
    }

    function replaceChoices(el, options, placeholder, selectedValue) {
        const list = [{ value: '', label: placeholder, placeholder: true, selected: !selectedValue }]
            .concat(options.map((o) => ({
                value: String(o.value),
                label: o.label,
                selected: selectedValue != null && String(selectedValue) === String(o.value),
            })));
        if (el._choices) {
            el._choices.setChoices(list, 'value', 'label', true);
        } else {
            el.innerHTML = '';
            list.forEach((o) => {
                const opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.label;
                if (o.selected) opt.selected = true;
                el.appendChild(opt);
            });
        }
    }

    /* ---------------- Faculty rows ---------------- */
    const facultyRows  = document.getElementById('facultyRows');
    const rowTemplate  = document.getElementById('facultyRowTemplate').innerHTML;
    let rowSeq = 0;

    function addFacultyRow(preset) {
        const idx     = rowSeq++;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = rowTemplate.replace(/__IDX__/g, idx);
        const row = wrapper.firstElementChild;
        facultyRows.appendChild(row);

        const fac  = row.querySelector('.cal-ce-faculty-select');
        const type = row.querySelector('.cal-ce-faculty-type');
        const role = row.querySelector('select[name^="faculty_role"]');

        [fac, type, role].forEach(initChoices);

        if (preset) {
            if (fac  && preset.faculty != null) setSelectValue(fac,  preset.faculty);
            if (type && preset.type    != null) setSelectValue(type, preset.type);
            if (role && preset.role    != null) setSelectValue(role, preset.role);
            if (preset.feedback) {
                if (preset.feedback === 'remark' || preset.feedback === 'both') {
                    const fb = row.querySelector(`input[name="faculty_feedback_remark[${idx}]"]`);
                    if (fb) fb.checked = true;
                }
                if (preset.feedback === 'rating' || preset.feedback === 'both') {
                    const fb = row.querySelector(`input[name="faculty_feedback_rating[${idx}]"]`);
                    if (fb) fb.checked = true;
                }
            }
        }
        applyFeedbackState(row);
        updateRemoveButtons();
        return row;
    }

    function applyFeedbackState(row) {
        const role = row.querySelector('select[name^="faculty_role"]');
        const isTeaching = !!role && role.value === 'Teaching';
        row.querySelectorAll('input[name^="faculty_feedback"]').forEach(inp => { inp.disabled = !isTeaching; });
        if (!isTeaching) {
            row.querySelectorAll('input[name^="faculty_feedback"]').forEach(inp => { inp.checked = false; });
        }
        const wrap = row.querySelector('[data-feedback-wrap]');
        if (wrap) wrap.classList.toggle('d-none', !isTeaching);
    }

    function updateRemoveButtons() {
        const rows = facultyRows.querySelectorAll('[data-faculty-row]');
        rows.forEach(row => {
            const btn = row.querySelector('.cal-ce-remove-faculty');
            if (btn) btn.disabled = rows.length === 1;
        });
    }

    facultyRows.addEventListener('change', function (e) {
        if (e.target.classList.contains('cal-ce-faculty-select')) {
            const opt  = e.target.options[e.target.selectedIndex];
            const t    = opt?.dataset?.faculty_type;
            const row  = e.target.closest('[data-faculty-row]');
            const typeSel = row?.querySelector('.cal-ce-faculty-type');
            if (typeSel) setSelectValue(typeSel, t || '');
        }
        if (e.target.matches('select[name^="faculty_role"]')) {
            const row = e.target.closest('[data-faculty-row]');
            if (row && e.target.value === 'Teaching') {
                const duplicate = Array.from(facultyRows.querySelectorAll('select[name^="faculty_role"]'))
                    .find(s => s !== e.target && s.value === 'Teaching');
                if (duplicate) {
                    alert('Only one faculty can have the Teaching role.');
                    e.target.value = '';
                    if (e.target._choices) e.target._choices.setChoiceByValue('');
                }
            }
            if (row) applyFeedbackState(row);
        }
    });

    facultyRows.addEventListener('click', function (e) {
        const btn = e.target.closest('.cal-ce-remove-faculty');
        if (!btn) return;
        const rows = facultyRows.querySelectorAll('[data-faculty-row]');
        if (rows.length > 1) {
            btn.closest('[data-faculty-row]').remove();
            updateRemoveButtons();
        }
    });

    document.getElementById('btnAddFaculty').addEventListener('click', () => addFacultyRow());

    // Seed rows: old() input on validation error → else event's saved faculty_details
    (function seedFacultyRows() {
        const facs = oldData.faculty;
        if (facs && typeof facs === 'object') {
            Object.keys(facs).forEach(k => {
                addFacultyRow({
                    faculty:  facs[k],
                    type:     oldData.faculty_row_type?.[k] ?? null,
                    role:     oldData.faculty_role?.[k]     ?? null,
                    feedback: (oldData.faculty_feedback_remark?.[k] ? (oldData.faculty_feedback_rating?.[k] ? 'both' : 'remark') : (oldData.faculty_feedback_rating?.[k] ? 'rating' : null)),
                });
            });
            return;
        }

        // Pre-fill from saved event data
        const details = Array.isArray(eventData.faculty_details) && eventData.faculty_details.length
            ? eventData.faculty_details
            : null;

        if (details) {
            details.forEach(d => {
                addFacultyRow({
                    faculty:  d.faculty_pk,
                    type:     d.faculty_type,
                    role:     d.role,
                    feedback: d.feedback,
                });
            });
        } else {
            // Fallback: plain faculty list without detail rows
            const fIds = Array.isArray(eventData.faculty_master) ? eventData.faculty_master : [];
            fIds.forEach(fId => addFacultyRow({ faculty: fId, type: eventData.faculty_type, role: null, feedback: null }));
        }

        if (!facultyRows.querySelector('[data-faculty-row]')) addFacultyRow();
    })();

    /* ---------------- Dependent dropdowns ---------------- */
    const courseSelect       = document.getElementById('Course_name');
    const groupTypeSelect    = document.getElementById('group_type');
    const typeNameContainer  = document.getElementById('type_name_container');
    const moduleSelect       = document.getElementById('subject_module');
    const subjectSelect      = document.getElementById('subject_name');

    let groupedCache = {};

    async function loadGroupTypes(preselectGroup, preselectNames) {
        const courseId = courseSelect.value;
        replaceChoices(groupTypeSelect, [], 'Select Group Type');
        typeNameContainer.innerHTML = '<div class="text-center text-muted small py-2">Select a Group Type first</div>';
        if (!courseId) return;
        try {
            const res  = await fetch(`${API.groupTypes}?course_id=${courseId}`);
            const data = await res.json();
            groupedCache = {};
            data.forEach(item => {
                (groupedCache[item.group_type_name] = groupedCache[item.group_type_name] || []).push(item);
            });
            const opts = Object.keys(groupedCache).map(key => ({
                value: key,
                label: groupedCache[key][0].type_name,
            }));
            replaceChoices(groupTypeSelect, opts, 'Select Group Type', preselectGroup);
            if (preselectGroup && groupedCache[preselectGroup]) {
                renderGroupNames(groupedCache[preselectGroup], preselectNames);
            }
        } catch (err) {
            console.error('Error loading group types:', err);
        }
    }

    function renderGroupNames(groups, preselectNames) {
        if (!groups || !groups.length) {
            typeNameContainer.innerHTML = '<div class="text-center text-muted small py-2">No groups found</div>';
            return;
        }
        const selected = Array.isArray(preselectNames)
            ? preselectNames.map(String)
            : (preselectNames && typeof preselectNames === 'object' ? Object.values(preselectNames).map(String) : []);
        let html = '<div class="row g-2">';
        groups.forEach(group => {
            const checked = selected.includes(String(group.pk)) ? 'checked' : '';
            html += `
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="type_names[]" value="${group.pk}" id="type_${group.pk}" ${checked}>
                        <label class="form-check-label" for="type_${group.pk}">${group.group_name} (${group.type_name})</label>
                    </div>
                </div>`;
        });
        html += '</div>';
        typeNameContainer.innerHTML = html;
    }

    courseSelect.addEventListener('change', () => loadGroupTypes());
    groupTypeSelect.addEventListener('change', () => renderGroupNames(groupedCache[groupTypeSelect.value] || []));

    async function loadSubjectNames(preselect) {
        const moduleId = moduleSelect.value;
        replaceChoices(subjectSelect, [], 'Select Subject Name');
        if (!moduleId) return;
        try {
            const res  = await fetch(`${API.subjectNames}?data_id=${moduleId}`);
            const data = await res.json();
            const opts = data.map(s => ({ value: s.pk, label: s.subject_name }));
            replaceChoices(subjectSelect, opts, 'Select Subject Name', preselect);
        } catch (err) {
            console.error('Error loading subject names:', err);
        }
    }
    moduleSelect.addEventListener('change', () => loadSubjectNames());

    /* ---------------- Init Choices on static selects ---------------- */
    ['Course_name', 'group_type', 'subject_module', 'subject_name', 'sector', 'vanue', 'shift']
        .forEach(id => initChoices(document.getElementById(id)));

    /* ---------------- Explicitly restore shift after Choices.js init ---------------- */
    if (eventData.session_type == 1 && eventData.class_session) {
        setSelectValue(document.getElementById('shift'), eventData.class_session);
    }

    /* ---------------- Load dependent dropdowns with saved values ---------------- */
    const preselectGroup   = oldData.group_type   ?? eventData.course_group_type_master;
    const preselectNames   = oldData.type_names   ?? eventData.group_name;
    const preselectSubject = oldData.subject_name ?? eventData.subject_master_pk;

    if (courseSelect.value) {
        loadGroupTypes(preselectGroup, preselectNames);
    }
    if (moduleSelect.value) {
        loadSubjectNames(preselectSubject);
    }

    /* ---------------- Shift toggle ---------------- */
    const shiftSelectWrap  = document.getElementById('shiftSelectWrap');
    const manualShiftFields = document.getElementById('manualShiftFields');

    function applyShiftType() {
        const type     = document.querySelector('input[name="shift_type"]:checked')?.value || '1';
        const isNormal = type === '1';
        shiftSelectWrap.classList.toggle('d-none', !isNormal);
        manualShiftFields.classList.toggle('d-none', isNormal);
    }
    document.querySelectorAll('input[name="shift_type"]').forEach(r => r.addEventListener('change', applyShiftType));
    applyShiftType();

    /* ---------------- Feedback toggle ---------------- */
    const feedbackToggle  = document.getElementById('feedback_checkbox');
    const feedbackOptions = document.getElementById('feedbackOptions');
    if (feedbackToggle && feedbackOptions) {
        feedbackToggle.addEventListener('change', function () {
            feedbackOptions.classList.toggle('d-none', !this.checked);
        });
    }

    /* ---------------- Validation: at least one group name ---------------- */
    document.getElementById('editEventForm').addEventListener('submit', function (e) {
        const anyName = typeNameContainer.querySelector('input[name="type_names[]"]:checked');
        if (!anyName) {
            e.preventDefault();
            typeNameContainer.classList.add('border-danger');
            typeNameContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
})();
</script>
@endpush
