@extends('admin.layouts.master')

@section('title', 'Configure Stationed Leave')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
<style>
    .stationed-leave-config .config-table {
        border: 1px solid #e4e7ec;
        border-radius: 8px;
        overflow: hidden;
    }
    .stationed-leave-config .config-table thead th {
        background-color: #f2f4f7;
        color: #667085;
        font-weight: 600;
        font-size: 0.8125rem;
        border-bottom: 1px solid #e4e7ec;
        border-top: 0;
        padding: 0.75rem 1.25rem;
        vertical-align: middle;
    }
    .stationed-leave-config .config-table tbody td {
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid #eef2f6;
        color: #344054;
        vertical-align: middle;
    }
    .stationed-leave-config .config-table tbody tr:last-child td {
        border-bottom: 0;
    }
    .stationed-leave-config .leave-grid-label {
        font-weight: 600;
        color: #344054;
        font-size: 0.9rem;
        margin-bottom: 0.4rem;
    }
    .stationed-leave-config .sl-approval-radios {
        display: flex;
        align-items: center;
        gap: 2rem;
        min-height: 40px;
    }
    .stationed-leave-config .sl-approval-radios .form-check {
        margin: 0;
        min-height: auto;
        padding-left: 1.6rem;
    }
    .stationed-leave-config .sl-approval-radios .form-check-input {
        width: 1.05rem;
        height: 1.05rem;
        margin-top: 0.15rem;
        margin-left: -1.6rem;
        cursor: pointer;
    }
    .stationed-leave-config .sl-approval-radios .form-check-input:checked {
        background-color: #004a93;
        border-color: #004a93;
    }
    .stationed-leave-config .sl-approval-radios .form-check-label {
        font-weight: 500;
        color: #344054;
        cursor: pointer;
    }
    .stationed-leave-config .btn-add-faculty {
        border: 1px solid #004a93;
        color: #004a93;
        font-weight: 600;
        border-radius: 8px;
        padding: 0.5rem 1.1rem;
        background: #fff;
    }
    .stationed-leave-config .btn-add-faculty:hover {
        background: #f0f6ff;
        color: #004a93;
    }
    .stationed-leave-config .sl-remove-btn {
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 6px;
        background: #fef3f2;
        color: #d92d20;
        line-height: 1;
    }
    .stationed-leave-config .sl-remove-btn:hover {
        background: #fee4e2;
        color: #b42318;
    }
    .stationed-leave-config .btn-cancel-outline {
        background: #fff;
        border: 1px solid #004a93;
        color: #004a93;
        font-weight: 600;
        padding: 0.5rem 1.5rem;
        border-radius: 8px;
    }
    .stationed-leave-config .btn-cancel-outline:hover {
        background: #f0f6ff;
        color: #004a93;
    }
    .stationed-leave-config .btn-apply {
        background: #004a93;
        border-color: #004a93;
        color: #fff;
        font-weight: 600;
        padding: 0.5rem 1.5rem;
        border-radius: 8px;
    }
    .stationed-leave-config .btn-apply:hover {
        background: #003d7a;
        border-color: #003d7a;
        color: #fff;
    }

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

    $cutoffValue = old(
        'apply_cutoff_time',
        $config?->apply_cutoff_time
            ? \Carbon\Carbon::parse($config->apply_cutoff_time)->format('H:i')
            : '06:00'
    );
@endphp

<div class="container-fluid stationed-leave-config">
    <x-breadcrum title="Configure Stationed Leave" :showBack="true" />

    <x-session_message />

    @if ($errors->has('faculty_rows'))
        <div class="alert alert-danger">{{ $errors->first('faculty_rows') }}</div>
    @endif

    @if ($errors->has('is_faculty_approval_required'))
        <div class="alert alert-danger">{{ $errors->first('is_faculty_approval_required') }}</div>
    @endif

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-3 p-md-4">
            <form method="POST" action="{{ route('admin.stationed-leave-master.store') }}" id="stationed-leave-form">
                @csrf

                {{-- PT timing preserved server-side; not surfaced in this layout. --}}
                <input type="hidden" id="apply_cutoff_time" name="apply_cutoff_time" value="{{ $cutoffValue }}">

                <div class="row g-4 mb-4">
                    <div class="col-12 col-md-6">
                        <label for="course_master_pk" class="leave-grid-label d-block">Select Course <span class="text-danger">*</span></label>
                        <select id="course_master_pk" name="course_master_pk" class="form-select" required>
                            <option value="">Select Course</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->pk }}"
                                    data-start-date="{{ filled($course->start_year) ? \Carbon\Carbon::parse($course->start_year)->format('Y-m-d') : '' }}"
                                    {{ (string) old('course_master_pk', $courseMasterPk) === (string) $course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="effective_from" class="leave-grid-label d-block">Effective From <span class="text-danger">*</span></label>
                        <input type="date" id="effective_from" name="effective_from" class="form-control" required
                            placeholder="Select the date"
                            value="{{ old('effective_from', $effectiveFrom ? \Carbon\Carbon::parse($effectiveFrom)->format('Y-m-d') : '') }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="leave-grid-label d-block">Approval <span class="text-danger">*</span></label>
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

                <div id="faculty-approval-section">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h3 class="h6 fw-semibold mb-0">Faculty Approval List</h3>
                        <button type="button" class="btn btn-add-faculty d-inline-flex align-items-center gap-2"
                            data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">person_add</i>
                            Add Faculty
                        </button>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table config-table align-middle mb-0" id="faculty-approval-table">
                            <thead>
                                <tr>
                                    <th style="width:8%;">S. No.</th>
                                    <th style="width:28%;">Faculty Name</th>
                                    <th style="width:24%;">Designation</th>
                                    <th style="width:28%;">Email</th>
                                    <th style="width:12%;" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="faculty-rows-body">
                                @forelse ($existingRows as $index => $row)
                                    <tr data-faculty-pk="{{ $row['faculty_master_pk'] }}">
                                        <td class="row-serial">{{ $index + 1 }}</td>
                                        <td>
                                            {{ $row['name'] }}
                                            <input type="hidden" name="faculty_rows[{{ $index }}][faculty_master_pk]" value="{{ $row['faculty_master_pk'] }}">
                                            <input type="hidden" name="faculty_rows[{{ $index }}][is_approval_authority]" value="1">
                                        </td>
                                        <td>{{ $row['designation'] }}</td>
                                        <td>{{ $row['email'] }}</td>
                                        <td class="text-end">
                                            <button type="button" class="sl-remove-btn remove-faculty-row" title="Remove" aria-label="Remove">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">remove</i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="faculty-empty-row">
                                        <td colspan="5" class="text-center text-muted py-4">No faculty added yet. Click "Add Faculty".</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2">
                    <a href="{{ route('admin.stationed-leave-master.index') }}" class="btn btn-cancel-outline">Cancel</a>
                    <button type="submit" class="btn btn-apply">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="addFacultyModalLabel">Add Faculty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="faculty_picker" class="form-label">Select Faculty</label>
                <select id="faculty_picker" class="form-select">
                    <option value="">-- Select Faculty --</option>
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
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAddFaculty">Add</button>
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
            facultyChoices.setChoiceByValue('');
        } else {
            $('#faculty_picker').val('');
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

    $('#course_master_pk').on('change', setEffectiveFromCourseStart);

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
        const $option = $('#faculty_picker option:selected');
        const facultyPk = $option.val();

        if (!facultyPk) {
            toastr.error('Please select a faculty.');
            return;
        }

        if (getSelectedFacultyIds().includes(String(facultyPk))) {
            toastr.warning('This faculty is already added.');
            return;
        }

        $('#faculty-empty-row').remove();

        const rowHtml = `
            <tr data-faculty-pk="${facultyPk}">
                <td class="row-serial"></td>
                <td>
                    ${$option.data('name')}
                    <input type="hidden" name="faculty_rows[${rowIndex}][faculty_master_pk]" value="${facultyPk}">
                    <input type="hidden" name="faculty_rows[${rowIndex}][is_approval_authority]" value="1">
                </td>
                <td>${$option.data('designation')}</td>
                <td>${$option.data('email')}</td>
                <td class="text-end">
                    <button type="button" class="sl-remove-btn remove-faculty-row" title="Remove" aria-label="Remove">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">remove</i>
                    </button>
                </td>
            </tr>
        `;

        $('#faculty-rows-body').append(rowHtml);
        rowIndex++;
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
                    <td colspan="5" class="text-center text-muted py-4">No faculty added yet. Click "Add Faculty".</td>
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

        return true;
    });

    refreshSerialNumbers();
});
</script>
@endpush
