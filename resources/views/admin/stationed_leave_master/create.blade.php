@extends('admin.layouts.master')

@section('title', ($isEditing ?? false) ? 'Edit Stationed Leave' : 'Configure Stationed Leave')

@push('styles')
{{-- Shared with the Stationed Leave listing: same module (docs/new-design-index-page.md §7). --}}
<link rel="stylesheet"
    href="{{ asset('css/stationed-leave-admin.css') }}?v={{ @filemtime(public_path('css/stationed-leave-admin.css')) }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')

@php
    $approvalRequired = old('is_faculty_approval_required', $config ? (int) $config->is_faculty_approval_required : 1);
    $existingRows = collect(old('faculty_rows', []));
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
@endphp

<div class="container-fluid stationed-leave-config">
    <x-breadcrum :title="($isEditing ?? false) ? 'Edit Stationed Leave' : 'Configure Stationed Leave'" :showBack="true" />

    <x-session_message />

    @if ($errors->has('faculty_rows'))
        <div class="alert alert-danger">{{ $errors->first('faculty_rows') }}</div>
    @endif

    @if ($errors->has('is_faculty_approval_required'))
        <div class="alert alert-danger">{{ $errors->first('is_faculty_approval_required') }}</div>
    @endif

    @if (!($isEditing ?? false) && $courses->isEmpty())
        <div class="alert alert-warning">
            All eligible courses already have a stationed leave configuration. Use Edit on the list page to update an existing record.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.stationed-leave-master.store') }}" id="stationed-leave-form">
        @csrf

        {{-- Course & schedule (§3d) --}}
        <div class="sl-card">
            <div class="sl-section">
                <h2 class="sl-section-title">Course &amp; Schedule</h2>
            </div>

                <div class="row g-4">
                    <div class="col-12 col-md-3">
                        <label for="course_master_pk" class="sl-field-label">Select Course <span class="sl-req">*</span></label>
                        <select id="course_master_pk" name="course_master_pk" class="sl-control" required
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
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="effective_from" class="sl-field-label">Effective From <span class="sl-req">*</span></label>
                        <input type="date" id="effective_from" name="effective_from" class="sl-control" required
                            placeholder="Select the date"
                            value="{{ old('effective_from', $effectiveFrom ? \Carbon\Carbon::parse($effectiveFrom)->format('Y-m-d') : '') }}"
                            @if($isEditing ?? false) readonly @endif>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="apply_cutoff_time" class="sl-field-label">PT Start Time</label>
                        <input type="time" id="apply_cutoff_time" name="apply_cutoff_time" class="sl-control"
                            placeholder="Select the time"
                            value="{{ $cutoffValue }}">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="pt_end_time_display" class="sl-field-label">PT End Time</label>
                        <input type="time" id="pt_end_time_display" class="sl-control"
                            value="{{ $ptEndTimeValue }}"
                            readonly>
                    </div>

            </div>
        </div>

        <div class="sl-card">
            <div class="sl-section">
                <h2 class="sl-section-title">Approval</h2>
            </div>

                <div class="mb-3">
                    <label class="sl-field-label">Approval required <span class="sl-req">*</span></label>
                    <div class="sl-approval-radios">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="approval_required_choice"
                                id="approval_yes" value="1" {{ (int) $approvalRequired === 1 ? 'checked' : '' }}>
                            <label class="form-check-label" for="approval_yes">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="approval_required_choice"
                                id="approval_no" value="0" {{ (int) $approvalRequired === 0 ? 'checked' : '' }}>
                            <label class="form-check-label" for="approval_no">No</label>
                        </div>
                    </div>
                    {{-- Disabled on "No" so it isn't submitted (controller treats presence as "required"). --}}
                    <input type="hidden" name="is_faculty_approval_required" id="approval_required_hidden" value="1"
                        {{ (int) $approvalRequired === 0 ? 'disabled' : '' }}>
                </div>

                </div>

                <div id="faculty-approval-section" class="sl-card">
                    <div class="sl-section">
                        <h2 class="sl-section-title">Faculty Approval List</h2>
                        <button type="button" class="btn btn-add-faculty d-inline-flex align-items-center gap-2"
                            data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                            <i class="bi bi-person-plus" aria-hidden="true"></i>
                            <span>Add Faculty</span>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table config-table align-middle mb-0" id="faculty-approval-table">
                            <thead>
                                <tr>
                                    <th style="width:6%;">S. No.</th>
                                    <th style="width:24%;">Faculty Name</th>
                                    <th style="width:20%;">Designation</th>
                                    <th style="width:26%;">Email</th>
                                    <th style="width:14%;" class="text-center">Approval Authority</th>
                                    <th style="width:10%;" class="text-end">Action</th>
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
                                        <td>{{ $row['designation'] }}</td>
                                        <td>{{ $row['email'] }}</td>
                                        <td class="text-center">
                                            <input type="hidden" name="faculty_rows[{{ $index }}][is_approval_authority]" value="0">
                                            <input class="form-check-input sl-authority-check" type="checkbox"
                                                name="faculty_rows[{{ $index }}][is_approval_authority]" value="1"
                                                {{ (int) ($row['is_approval_authority'] ?? 0) === 1 ? 'checked' : '' }}>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="sl-remove-btn remove-faculty-row" title="Remove" aria-label="Remove">
                                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="faculty-empty-row">
                                        <td colspan="6" class="text-center text-muted py-4">No faculty added yet. Click "Add Faculty".</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>


        {{-- Footer: an equal, flush-right pair, same treatment as the modals (§3d). --}}
        <div class="sl-form-footer">
            <a href="{{ route('admin.stationed-leave-master.index') }}" class="btn sl-btn-cancel">Cancel</a>
            <button type="submit" class="btn sl-btn-submit"
                @if(!($isEditing ?? false) && $courses->isEmpty()) disabled @endif>
                {{ ($isEditing ?? false) ? 'Update Stationed Leave' : 'Save Stationed Leave' }}
            </button>
        </div>
    </form>
</div>

<div class="modal fade sl-modal" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="sl-modal-header">
                <h5 class="sl-modal-title" id="addFacultyModalLabel">Add Faculty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="sl-modal-body">
                <div class="sl-field-card">
                <label for="faculty_picker" class="sl-field-label">Select Faculty <span class="text-muted small fw-normal">(you can select multiple)</span></label>
                <select id="faculty_picker" class="sl-control" multiple>
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
            <div class="sl-modal-footer">
                <button type="button" class="btn sl-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn sl-btn-submit" id="confirmAddFaculty">Add Faculty</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(function () {
    let rowIndex = {{ max($existingRows->count(), 0) }};
    let facultyChoices = null;

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

    $('#course_master_pk').on('change', function () {
        setEffectiveFromCourseStart();
        setPtTimingFromCourse();
    });

    if ($('#course_master_pk').val() && !$('#effective_from').val()) {
        setEffectiveFromCourseStart();
    }

    /* ── Faculty rows ── */
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

            const rowHtml = `
                <tr data-faculty-pk="${facultyPk}">
                    <td class="row-serial"></td>
                    <td>
                        ${$option.data('name')}
                        <input type="hidden" name="faculty_rows[${rowIndex}][faculty_master_pk]" value="${facultyPk}">
                    </td>
                    <td>${$option.data('designation')}</td>
                    <td>${$option.data('email')}</td>
                    <td class="text-center">
                        <input type="hidden" name="faculty_rows[${rowIndex}][is_approval_authority]" value="0">
                        <input class="form-check-input sl-authority-check" type="checkbox"
                            name="faculty_rows[${rowIndex}][is_approval_authority]" value="1">
                    </td>
                    <td class="text-end">
                        <button type="button" class="sl-remove-btn remove-faculty-row" title="Remove" aria-label="Remove">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">remove</i>
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
            $('#faculty-rows-body').html(`
                <tr id="faculty-empty-row">
                    <td colspan="6" class="text-center text-muted py-4">No faculty added yet. Click "Add Faculty".</td>
                </tr>
            `);
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
