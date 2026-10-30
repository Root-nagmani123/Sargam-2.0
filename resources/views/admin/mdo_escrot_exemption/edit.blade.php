@extends('admin.layouts.master')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('title', 'MDO Escrot Exemption')

@section('setup_content')
@php
    $meeDuty = old_string('mdo_duty_type_master_pk', $mdoDutyType->mdo_duty_type_master_pk ?? '');
    $meeFaculty = old('faculty_master_pk', $mdoDutyType->faculty_master_pk ?? '');
    $meeFaculty = is_array($meeFaculty) ? (string) ($meeFaculty[0] ?? '') : (string) $meeFaculty;
@endphp
<div class="container-fluid mst-page mee-form-page">
    <x-breadcrum title="{{ !empty($mdoDutyType) ? 'Edit MDO/Escort Exemption' : 'Create MDO/Escort Exemption' }}" />
    <x-session_message />

    <form action="{{ route('mdo-escrot-exemption.update') }}" method="POST" id="mdoDutyTypeForm">
        @csrf
        @if(!empty($mdoDutyType))
        <input type="hidden" name="pk" value="{{ encrypt($mdoDutyType->pk) }}">
        @endif

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Duty Details</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <span class="mst-form-label d-block" id="meeStudentLabel">Student Name</span>
                        <div class="mst-readonly d-flex align-items-center gap-2" aria-labelledby="meeStudentLabel">
                            <i class="bi bi-person-circle text-primary" aria-hidden="true"></i>
                            <span class="fw-semibold">{{ $mdoDutyType->studentMaster->display_name ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6 d-none d-md-block"></div>

                    <div class="col-md-6">
                        <label for="mdo_duty_type_master_pk" class="mst-form-label d-block">
                            Duty Type <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="mdo_duty_type_master_pk" id="mdo_duty_type_master_pk"
                            class="form-select mst-control mst-searchable @error('mdo_duty_type_master_pk') is-invalid @enderror"
                            data-placeholder="Select Duty Type" required aria-required="true"
                            @error('mdo_duty_type_master_pk') aria-describedby="meeDutyError" @enderror>
                            <option value="">Select</option>
                            @foreach ($MDODutyTypeMaster as $value => $label)
                            <option value="{{ $value }}" @selected($meeDuty === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('mdo_duty_type_master_pk')
                            <span class="mst-field-error" id="meeDutyError">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Shown by toggleFacultyField() when the duty type is Escort
                         (jQuery .show()/.hide(), so no display utility class here).
                         The script re-initialises its Select2 when it is shown. --}}
                    <div class="col-md-6" id="faculty_field_container" style="display: none;">
                        <label for="faculty_master_pk" class="mst-form-label d-block">
                            Faculty <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="faculty_master_pk" id="faculty_master_pk"
                            class="form-select mst-control mst-searchable @error('faculty_master_pk') is-invalid @enderror"
                            data-placeholder="Select Faculty"
                            @error('faculty_master_pk') aria-describedby="meeFacultyError" @enderror>
                            <option value="">Select</option>
                            @foreach ($facultyMaster as $value => $label)
                            <option value="{{ $value }}" @selected($meeFaculty === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('faculty_master_pk')
                            <span class="mst-field-error" id="meeFacultyError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="mdo_date" class="mst-form-label d-block">
                            Date <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="date" name="mdo_date" id="mdo_date"
                            class="form-control mst-control @error('mdo_date') is-invalid @enderror"
                            value="{{ old('mdo_date', format_date($mdoDutyType->mdo_date, 'Y-m-d') ?? '') }}" required aria-required="true"
                            @error('mdo_date') aria-describedby="meeDateError" @enderror>
                        @error('mdo_date')
                            <span class="mst-field-error" id="meeDateError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="Time_from" class="mst-form-label d-block">
                            From Time <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="time" name="Time_from" id="Time_from"
                            class="form-control mst-control @error('Time_from') is-invalid @enderror"
                            value="{{ old('Time_from', $mdoDutyType->Time_from ?? '') }}" required aria-required="true"
                            @error('Time_from') aria-describedby="meeTimeFromError" @enderror>
                        @error('Time_from')
                            <span class="mst-field-error" id="meeTimeFromError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="Time_to" class="mst-form-label d-block">
                            To Time <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="time" name="Time_to" id="Time_to"
                            class="form-control mst-control @error('Time_to') is-invalid @enderror"
                            value="{{ old('Time_to', $mdoDutyType->Time_to ?? '') }}" required aria-required="true"
                            @error('Time_to') aria-describedby="meeTimeToError" @enderror>
                        @error('Time_to')
                            <span class="mst-field-error" id="meeTimeToError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('mdo-escrot-exemption.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4">Update</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
<script>
$(document).ready(function() {
    // Function to toggle faculty field based on duty type
    function toggleFacultyField() {
        const dutyTypeSelect = $('#mdo_duty_type_master_pk');
        const facultyContainer = $('#faculty_field_container');
        const selectedDutyType = dutyTypeSelect.val();

        // Get all duty type options to find Escort
        let escortDutyTypeId = null;
        dutyTypeSelect.find('option').each(function() {
            const optionText = $(this).text().toLowerCase().trim();
            if (optionText === 'escort') {
                escortDutyTypeId = $(this).val();
            }
        });

        // Show faculty field if Escort is selected
        if (selectedDutyType && selectedDutyType == escortDutyTypeId) {
            facultyContainer.show();
            $('#faculty_master_pk').attr('required', true);
            // Reinitialize select2 now that it is visible (MstAdmin.searchable
            // destroys the old instance first, as the bare .select2() call did,
            // but keeps the shared width / placeholder / dropdownParent).
            MstAdmin.searchable('#faculty_master_pk');
        } else {
            facultyContainer.hide();
            $('#faculty_master_pk').val('').trigger('change');
            $('#faculty_master_pk').removeAttr('required');
        }
    }

    // Initialize after select2 is ready
    setTimeout(function() {
        toggleFacultyField();
    }, 100);

    // Toggle when duty type changes
    $('#mdo_duty_type_master_pk').on('change', function() {
        toggleFacultyField();
    });
});
</script>
@endsection
