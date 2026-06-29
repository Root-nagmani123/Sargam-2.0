@extends('admin.layouts.master')

@section('title', 'Stationed Leave Configuration - Sargam | Lal Bahadur')

@section('setup_content')
<style>
    .stationed-leave-config .config-table thead th {
        background-color: #004a93;
        color: #fff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.03em;
        border: none;
        vertical-align: middle;
    }
    .stationed-leave-config .config-table tbody td {
        vertical-align: middle;
    }
    .stationed-leave-config .btn-save-config {
        background-color: #198754;
        border-color: #198754;
        color: #fff;
        min-width: 160px;
    }
    .stationed-leave-config .btn-save-config:hover {
        background-color: #157347;
        border-color: #146c43;
        color: #fff;
    }
    .stationed-leave-config .btn-add-faculty {
        border-color: #004a93;
        color: #004a93;
    }
    .stationed-leave-config .btn-add-faculty:hover {
        background-color: #004a93;
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

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

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
@endphp

<div class="container-fluid py-3 stationed-leave-config">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.stationed-leave-master.index') }}">Leave Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Stationed Leave Settings</li>
        </ol>
    </nav>

    <x-session_message />

    @if ($errors->has('faculty_rows'))
        <div class="alert alert-danger">{{ $errors->first('faculty_rows') }}</div>
    @endif

    @if ($errors->has('is_faculty_approval_required'))
        <div class="alert alert-danger">{{ $errors->first('is_faculty_approval_required') }}</div>
    @endif

    <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                <h2 class="h5 mb-0 fw-semibold text-body">Stationed Leave Configuration</h2>
                <a href="{{ route('admin.stationed-leave-master.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="material-icons material-symbols-rounded align-middle" style="font-size:18px;">arrow_back</i>
                    Back to List
                </a>
            </div>

            <form method="POST" action="{{ route('admin.stationed-leave-master.store') }}" id="stationed-leave-form">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="course_master_pk" class="form-label fw-semibold">Select Course <span class="text-danger">*</span></label>
                        <select id="course_master_pk" name="course_master_pk" class="form-select" required>
                            <option value="">-- Select Course --</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->pk }}"
                                    data-start-date="{{ filled($course->start_year) ? \Carbon\Carbon::parse($course->start_year)->format('Y-m-d') : '' }}"
                                    {{ (string) old('course_master_pk', $courseMasterPk) === (string) $course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="effective_from" class="form-label fw-semibold">Effective From <span class="text-danger">*</span></label>
                        <input type="date" id="effective_from" name="effective_from" class="form-control" required
                            value="{{ old('effective_from', $effectiveFrom ? \Carbon\Carbon::parse($effectiveFrom)->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="apply_cutoff_time" class="form-label fw-semibold">PT Timing <span class="text-danger">*</span></label>
                        @php
                            $cutoffValue = old(
                                'apply_cutoff_time',
                                $config?->apply_cutoff_time
                                    ? \Carbon\Carbon::parse($config->apply_cutoff_time)->format('H:i')
                                    : '06:00'
                            );
                        @endphp
                        <input type="time" id="apply_cutoff_time" name="apply_cutoff_time" class="form-control @error('apply_cutoff_time') is-invalid @enderror" required
                            value="{{ $cutoffValue }}">
                        @error('apply_cutoff_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Officer trainees cannot apply for the same day's leave after this time.</div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold d-block">Approval Required</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_faculty_approval_required"
                            name="is_faculty_approval_required" value="1"
                            {{ (int) $approvalRequired === 1 ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_faculty_approval_required">
                            Require approval from faculty
                        </label>
                    </div>
                </div>

                <h3 class="h6 fw-semibold mb-3">Faculty Approval List</h3>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered config-table mb-0" id="faculty-approval-table">
                        <thead>
                            <tr>
                                <th style="width:8%;">S. No.</th>
                                <th style="width:24%;">Faculty Name</th>
                                <th style="width:20%;">Designation</th>
                                <th style="width:24%;">Email</th>
                                <th style="width:14%;">Approval Authority</th>
                                <th style="width:10%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="faculty-rows-body">
                            @forelse ($existingRows as $index => $row)
                                <tr data-faculty-pk="{{ $row['faculty_master_pk'] }}">
                                    <td class="row-serial">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input faculty-select-check" type="checkbox" checked disabled>
                                            <label class="form-check-label">{{ $row['name'] }}</label>
                                        </div>
                                        <input type="hidden" name="faculty_rows[{{ $index }}][faculty_master_pk]" value="{{ $row['faculty_master_pk'] }}">
                                    </td>
                                    <td>{{ $row['designation'] }}</td>
                                    <td>{{ $row['email'] }}</td>
                                    <td class="text-center">
                                        <input type="hidden" name="faculty_rows[{{ $index }}][is_approval_authority]" value="0">
                                        <input class="form-check-input approval-authority-check" type="checkbox"
                                            name="faculty_rows[{{ $index }}][is_approval_authority]" value="1"
                                            {{ (int) ($row['is_approval_authority'] ?? 0) === 1 ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-faculty-row" title="Delete">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
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

                <div class="mb-4">
                    <button type="button" class="btn btn-outline-primary btn-add-faculty d-inline-flex align-items-center gap-1"
                        data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">add</i>
                        Add Faculty
                    </button>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-save-config d-inline-flex align-items-center gap-1 px-4">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">save</i>
                        Save Configuration
                    </button>
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
                    <div class="form-check mb-0">
                        <input class="form-check-input faculty-select-check" type="checkbox" checked disabled>
                        <label class="form-check-label">${$option.data('name')}</label>
                    </div>
                    <input type="hidden" name="faculty_rows[${rowIndex}][faculty_master_pk]" value="${facultyPk}">
                </td>
                <td>${$option.data('designation')}</td>
                <td>${$option.data('email')}</td>
                <td class="text-center">
                    <input type="hidden" name="faculty_rows[${rowIndex}][is_approval_authority]" value="0">
                    <input class="form-check-input approval-authority-check" type="checkbox"
                        name="faculty_rows[${rowIndex}][is_approval_authority]" value="1">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-faculty-row" title="Delete">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
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
                    <td colspan="6" class="text-center text-muted py-4">No faculty added yet. Click "Add Faculty".</td>
                </tr>
            `);
        }
    });

    $('#stationed-leave-form').on('submit', function () {
        const approvalRequired = $('#is_faculty_approval_required').is(':checked');

        if (!approvalRequired) {
            return true;
        }

        if ($('#faculty-rows-body tr[data-faculty-pk]').length === 0) {
            toastr.error('Please add at least one faculty when approval is required.');
            return false;
        }

        if ($('.approval-authority-check:checked').length === 0) {
            toastr.error('Please mark at least one faculty as approval authority.');
            return false;
        }

        return true;
    });

    refreshSerialNumbers();
});
</script>
@endpush
