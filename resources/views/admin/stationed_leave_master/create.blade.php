@extends('admin.layouts.master')

@section('title', 'Configure Stationed Leave')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
<style>
    /* Values the form derives from Course Master read as read-only. Same rule
       on the PT Exemption and Stationed Leave forms (.lm-page). */
    .mst-page.lm-page .mst-control[readonly] {
        background: var(--ds-surface-2);
    }

    .mst-page.lm-page .sl-authority-check {
        float: none;
        margin: 0;
        cursor: pointer;
    }

    /* Faculty picker (Choices.js) inside the Add Faculty modal. */
    #addFacultyModal .choices {
        width: 100%;
        margin-bottom: 0;
    }

    #addFacultyModal .choices[data-type*="select-one"]::after {
        display: none;
    }

    #addFacultyModal .choices__list--dropdown,
    #addFacultyModal .choices__list[aria-expanded] {
        z-index: 1060;
    }
</style>
@endpush

@section('setup_content')
@php
    // Faculty lookup used to label rows that come back from old() input, which
    // only carries faculty_master_pk / is_approval_authority.
    $facultyLookup = collect($faculties)->keyBy('pk');

    $approvalRequired = old('is_faculty_approval_required', $config ? (int) $config->is_faculty_approval_required : 1);
    $existingRows = collect(old('faculty_rows', []))->map(function ($row) use ($facultyLookup) {
        $known = $facultyLookup->get((int) ($row['faculty_master_pk'] ?? 0), []);

        return [
            'faculty_master_pk' => $row['faculty_master_pk'] ?? '',
            'name' => $row['name'] ?? ($known['name'] ?? 'N/A'),
            'designation' => $row['designation'] ?? ($known['designation'] ?? 'N/A'),
            'email' => $row['email'] ?? ($known['email'] ?? 'N/A'),
            'is_approval_authority' => (int) ($row['is_approval_authority'] ?? 0),
        ];
    })->values();
    if ($existingRows->isEmpty() && $approvers->isNotEmpty()) {
        $existingRows = $approvers->map(function ($row) {
            $faculty = $row->faculty;
            $name = trim($faculty->full_name ?? implode(' ', array_filter([
                $faculty->first_name ?? null,
                $faculty->middle_name ?? null,
                $faculty->last_name ?? null,
            ])));

            return [
                'faculty_master_pk' => $row->faculty_master_pk,
                'name' => $name ?: 'N/A',
                'designation' => $faculty->current_designation ?? 'N/A',
                'email' => $faculty->email_id ?? 'N/A',
                'is_approval_authority' => (int) $row->is_approval_authority,
            ];
        });
    }

    $selectedCourse = $courses->firstWhere('pk', (int) old('course_master_pk', $courseMasterPk));

    $cutoffValue = old(
        'apply_cutoff_time',
        filled($selectedCourse?->pt_start_time)
            ? \Carbon\Carbon::parse($selectedCourse->pt_start_time)->format('H:i')
            : ($config?->apply_cutoff_time
                ? \Carbon\Carbon::parse($config->apply_cutoff_time)->format('H:i')
                : '')
    );

    $ptEndTimeValue = filled($selectedCourse?->pt_end_time)
        ? \Carbon\Carbon::parse($selectedCourse->pt_end_time)->format('H:i')
        : '';

    $noCourses = !($isEditing ?? false) && $courses->isEmpty();
@endphp

<div class="container-fluid mst-page lm-page slm-form-page">
    <x-breadcrum title="Configure Stationed Leave" :showBack="true" />

    <x-session_message />

    @if ($noCourses)
        <div class="alert alert-warning rounded-1" role="alert">
            All eligible courses already have a stationed leave configuration. Use Edit on the list page to update an existing record.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.stationed-leave-master.store') }}" id="stationed-leave-form">
        @csrf

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Course &amp; PT Timing</h2>

                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="course_master_pk" class="mst-form-label d-block">
                            Select Course <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select id="course_master_pk" name="course_master_pk"
                                class="form-select mst-control mst-searchable @error('course_master_pk') is-invalid @enderror"
                                data-placeholder="Select Course" required aria-required="true"
                                @if(($isEditing ?? false) || $courses->isEmpty()) disabled @endif>
                            <option value="">Select Course</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->pk }}"
                                    data-start-date="{{ filled($course->start_year) ? \Carbon\Carbon::parse($course->start_year)->format('Y-m-d') : '' }}"
                                    data-pt-start-time="{{ filled($course->pt_start_time) ? \Carbon\Carbon::parse($course->pt_start_time)->format('H:i') : '' }}"
                                    data-pt-end-time="{{ filled($course->pt_end_time) ? \Carbon\Carbon::parse($course->pt_end_time)->format('H:i') : '' }}"
                                    {{ (string) old('course_master_pk', $courseMasterPk) === (string) $course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                        @if($isEditing ?? false)
                            <input type="hidden" name="course_master_pk" value="{{ old('course_master_pk', $courseMasterPk) }}">
                        @endif
                        @error('course_master_pk')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="effective_from" class="mst-form-label d-block">
                            Effective From <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="date" id="effective_from" name="effective_from"
                               class="form-control mst-control @error('effective_from') is-invalid @enderror"
                               required aria-required="true" placeholder="Select the date"
                               value="{{ old('effective_from', $effectiveFrom ? \Carbon\Carbon::parse($effectiveFrom)->format('Y-m-d') : '') }}"
                               @if($isEditing ?? false) readonly @endif>
                        @error('effective_from')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="apply_cutoff_time" class="mst-form-label d-block">PT Start Time</label>
                        <input type="time" id="apply_cutoff_time" name="apply_cutoff_time"
                               class="form-control mst-control @error('apply_cutoff_time') is-invalid @enderror"
                               placeholder="Select the time" value="{{ $cutoffValue }}">
                        @error('apply_cutoff_time')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="pt_end_time_display" class="mst-form-label d-block">PT End Time</label>
                        <input type="time" id="pt_end_time_display" class="form-control mst-control"
                               value="{{ $ptEndTimeValue }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Faculty Approval</h2>

                <fieldset class="mb-3">
                    <legend class="mst-form-label d-block float-none w-auto mb-2">
                        Approval Required <span class="mst-req" aria-hidden="true">*</span>
                    </legend>
                    <div class="d-flex flex-wrap align-items-center gap-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="radio" name="approval_required_choice"
                                   id="approval_yes" value="1" {{ (int) $approvalRequired === 1 ? 'checked' : '' }}>
                            <label class="form-check-label" for="approval_yes">Yes</label>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="radio" name="approval_required_choice"
                                   id="approval_no" value="0" {{ (int) $approvalRequired === 0 ? 'checked' : '' }}>
                            <label class="form-check-label" for="approval_no">No</label>
                        </div>
                    </div>
                    {{-- Disabled on "No" so it isn't submitted (controller treats presence as "required"). --}}
                    <input type="hidden" name="is_faculty_approval_required" id="approval_required_hidden" value="1"
                           {{ (int) $approvalRequired === 0 ? 'disabled' : '' }}>
                    @error('is_faculty_approval_required')
                        <span class="mst-field-error">{{ $message }}</span>
                    @enderror
                </fieldset>

                <div id="faculty-approval-section">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h3 class="h6 fw-semibold mb-0">Faculty Approval List</h3>
                        <button type="button" class="btn btn-outline-primary rounded-1 fw-semibold d-inline-flex align-items-center gap-2"
                                data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                            <i class="bi bi-person-plus" aria-hidden="true"></i>
                            <span>Add Faculty</span>
                        </button>
                    </div>

                    <div class="programme-dt-panel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="faculty-approval-table">
                                <caption class="visually-hidden">Faculty who can approve stationed leave</caption>
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-nowrap">S. No.</th>
                                        <th scope="col">Faculty Name</th>
                                        <th scope="col">Designation</th>
                                        <th scope="col">Email</th>
                                        <th scope="col" class="text-center text-nowrap">Approval Authority</th>
                                        <th scope="col" class="text-nowrap">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="faculty-rows-body">
                                    @forelse ($existingRows as $index => $row)
                                        <tr data-faculty-pk="{{ $row['faculty_master_pk'] }}">
                                            <td class="row-serial">{{ $index + 1 }}</td>
                                            <td>
                                                {{ $row['name'] }}
                                                <input type="hidden" name="faculty_rows[{{ $index }}][faculty_master_pk]" value="{{ $row['faculty_master_pk'] }}">
                                            </td>
                                            <td class="mst-col-wrap">{{ $row['designation'] }}</td>
                                            <td>{{ $row['email'] }}</td>
                                            <td class="text-center">
                                                <input type="hidden" name="faculty_rows[{{ $index }}][is_approval_authority]" value="0">
                                                <input class="form-check-input sl-authority-check" type="checkbox"
                                                       name="faculty_rows[{{ $index }}][is_approval_authority]" value="1"
                                                       aria-label="Approval authority: {{ $row['name'] }}"
                                                       {{ (int) ($row['is_approval_authority'] ?? 0) === 1 ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <button type="button" class="mst-field-btn mst-field-btn--remove remove-faculty-row"
                                                        title="Remove" aria-label="Remove {{ $row['name'] }}">
                                                    <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="faculty-empty-row" class="mst-empty">
                                            <td colspan="6">No faculty added yet. Click "Add Faculty".</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @error('faculty_rows')
                        <span class="mst-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('admin.stationed-leave-master.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4" @if($noCourses) disabled @endif>Save</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade mst-modal" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="addFacultyModalLabel">Add Faculty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mst-field-card">
                    <label for="faculty_picker" class="mst-form-label d-block">
                        Select Faculty <span class="text-muted fw-normal">(you can select multiple)</span>
                    </label>
                    {{-- Choices.js multi-select (initialised on shown.bs.modal), not Select2. --}}
                    <select id="faculty_picker" class="form-select mst-control" multiple>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty['pk'] }}"
                                data-name="{{ $faculty['name'] }}"
                                data-designation="{{ $faculty['designation'] }}"
                                data-email="{{ $faculty['email'] }}">
                                {{ $faculty['name'] }} ({{ $faculty['designation'] }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn mst-btn-submit px-4" id="confirmAddFaculty">Add</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
<script>
$(function () {
    let rowIndex = {{ max($existingRows->count(), 0) }};
    let facultyChoices = null;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function initFacultyPicker() {
        const el = document.getElementById('faculty_picker');
        if (!el || facultyChoices || typeof Choices === 'undefined') {
            return;
        }

        facultyChoices = new Choices(el, {
            searchEnabled: true,
            searchPlaceholderValue: 'Search faculty...',
            shouldSort: false,
            removeItemButton: true,
            itemSelectText: '',
            allowHTML: false,
            placeholder: true,
            placeholderValue: '-- Select Faculty --',
            position: 'bottom',
            classNames: {
                containerOuter: ['choices', 'w-100'],
                containerInner: ['choices__inner', 'form-select'],
            },
        });
        el._choicesInstance = facultyChoices;
    }

    $('#addFacultyModal').on('shown.bs.modal', initFacultyPicker);

    function resetFacultyPicker() {
        if (facultyChoices) {
            facultyChoices.removeActiveItems();
        } else {
            $('#faculty_picker').val(null);
        }
    }

    /* ── Approval Yes/No ── */
    function syncApprovalState() {
        const yes = $('#approval_yes').is(':checked');
        $('#approval_required_hidden').prop('disabled', !yes);
        $('#faculty-approval-section').toggle(yes);
    }

    $('input[name="approval_required_choice"]').on('change', syncApprovalState);
    syncApprovalState();

    /* ── Effective from auto-fill ── */
    function setEffectiveFromCourseStart() {
        const startDate = $('#course_master_pk option:selected').data('startDate');
        if (startDate) {
            $('#effective_from').val(startDate);
        }
    }

    function setPtTimingFromCourse() {
        const selected = $('#course_master_pk option:selected');
        const ptStartTime = selected.data('ptStartTime');
        if (ptStartTime) {
            $('#apply_cutoff_time').val(ptStartTime);
        }
        $('#pt_end_time_display').val(selected.data('ptEndTime') || '');
    }

    // jQuery binding: the course select is searchable (Select2 fires a jQuery change).
    $('#course_master_pk').on('change', function () {
        setEffectiveFromCourseStart();
        setPtTimingFromCourse();
    });

    if ($('#course_master_pk').val() && !$('#effective_from').val()) {
        setEffectiveFromCourseStart();
    }

    /* ── Faculty rows ── */
    const emptyRowHtml = '<tr id="faculty-empty-row" class="mst-empty">'
        + '<td colspan="6">No faculty added yet. Click "Add Faculty".</td></tr>';

    function refreshSerialNumbers() {
        $('#faculty-rows-body tr').not('#faculty-empty-row').each(function (idx) {
            $(this).find('.row-serial').text(idx + 1);
        });
    }

    function getSelectedFacultyIds() {
        const ids = [];
        $('#faculty-rows-body tr[data-faculty-pk]').each(function () {
            ids.push(String($(this).data('faculty-pk')));
        });
        return ids;
    }

    $('#confirmAddFaculty').on('click', function () {
        const $selected = $('#faculty_picker option:selected');

        if ($selected.length === 0) {
            toastr.error('Please select at least one faculty.');
            return;
        }

        const existingIds = getSelectedFacultyIds();
        let addedCount = 0;
        let duplicateCount = 0;

        $selected.each(function () {
            const $option = $(this);
            const facultyPk = $option.val();

            if (!facultyPk) {
                return;
            }

            if (existingIds.includes(String(facultyPk))) {
                duplicateCount++;
                return;
            }

            existingIds.push(String(facultyPk));
            $('#faculty-empty-row').remove();

            const pk = escapeHtml(facultyPk);
            const name = escapeHtml($option.data('name'));

            const rowHtml = `
                <tr data-faculty-pk="${pk}">
                    <td class="row-serial"></td>
                    <td>
                        ${name}
                        <input type="hidden" name="faculty_rows[${rowIndex}][faculty_master_pk]" value="${pk}">
                    </td>
                    <td class="mst-col-wrap">${escapeHtml($option.data('designation'))}</td>
                    <td>${escapeHtml($option.data('email'))}</td>
                    <td class="text-center">
                        <input type="hidden" name="faculty_rows[${rowIndex}][is_approval_authority]" value="0">
                        <input class="form-check-input sl-authority-check" type="checkbox"
                            name="faculty_rows[${rowIndex}][is_approval_authority]" value="1"
                            aria-label="Approval authority: ${name}">
                    </td>
                    <td>
                        <button type="button" class="mst-field-btn mst-field-btn--remove remove-faculty-row"
                            title="Remove" aria-label="Remove ${name}">
                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#faculty-rows-body').append(rowHtml);
            rowIndex++;
            addedCount++;
        });

        if (duplicateCount > 0) {
            toastr.warning(duplicateCount + ' faculty already added and skipped.');
        }

        if (addedCount === 0) {
            return;
        }

        refreshSerialNumbers();
        resetFacultyPicker();
        bootstrap.Modal.getInstance(document.getElementById('addFacultyModal')).hide();
    });

    $(document).on('click', '.remove-faculty-row', function () {
        $(this).closest('tr').remove();
        refreshSerialNumbers();

        if ($('#faculty-rows-body tr[data-faculty-pk]').length === 0) {
            $('#faculty-rows-body').html(emptyRowHtml);
        }
    });

    $('#stationed-leave-form').on('submit', function () {
        const approvalRequired = $('#approval_yes').is(':checked');

        if (!approvalRequired) {
            return true;
        }

        if ($('#faculty-rows-body tr[data-faculty-pk]').length === 0) {
            toastr.error('Please add at least one faculty when approval is required.');
            return false;
        }

        if ($('#faculty-rows-body .sl-authority-check:checked').length === 0) {
            toastr.error('Please mark at least one faculty as approval authority.');
            return false;
        }

        return true;
    });

    refreshSerialNumbers();
});
</script>
@endpush
