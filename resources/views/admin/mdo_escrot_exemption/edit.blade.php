@extends('admin.layouts.master')
@section('css')
{{--
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.8.1/themes/prism.min.css" rel="stylesheet" /> --}}
<link rel="stylesheet" href="{{asset('admin_assets/css/dual-listbox.css')}}">
<style>
/* MDO Escrot Exemption Edit - Responsive */
@media (max-width: 991.98px) {
    .mdo-edit-page .card-body { padding: 1rem !important; }
    .mdo-edit-page .card-title { font-size: 1.1rem; }
}

@media (max-width: 767.98px) {
    .mdo-edit-page.container-fluid { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
    .mdo-edit-page .card-body { padding: 0.75rem !important; }
    .mdo-edit-page .form-actions { flex-direction: column; align-items: stretch !important; }
    .mdo-edit-page .form-actions .btn { width: 100%; justify-content: center; }
}

@media (max-width: 575.98px) {
    .mdo-edit-page.container-fluid { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
    .mdo-edit-page .card-body { padding: 0.5rem !important; }
    .mdo-edit-page .card-title { font-size: 1rem; }
    .mdo-edit-page .alert .fs-5 { font-size: 0.9375rem !important; }
    .mdo-edit-page .form-label { font-size: 0.9rem; }
}

@media (max-width: 375px) {
    .mdo-edit-page.container-fluid { padding-left: 0.375rem !important; padding-right: 0.375rem !important; }
    .mdo-edit-page .card-body { padding: 0.5rem !important; }
}

/* Select2 responsive - full width on mobile */
@media (max-width: 575.98px) {
    .mdo-edit-page .select2-container { width: 100% !important; }
}

/* Prevent date/time inputs from overflowing */
.mdo-edit-page input[type="date"],
.mdo-edit-page input[type="time"] {
    max-width: 100%;
}
</style>
@endsection
@section('title', 'MDO Escrot Exemption')

@section('setup_content')

<div class="container-fluid mdo-edit-page">
    <x-breadcrum title="MDO Escrot Exemption" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($mdoDutyType) ? 'Edit MDO/Escort Exemption' : 'Create MDO/Escort Exemption' }}
            </h4>
            <hr>

            <div class="row mb-4">
                <div class="col-12 col-md-8">
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="material-icons me-2">person</i>
                        <div>
                            <strong>Student Name:</strong>
                            <span class="ms-2 fs-5">{{ $mdoDutyType->studentMaster->display_name ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('mdo-escrot-exemption.update') }}" method="POST" id="mdoDutyTypeForm">
                @csrf
                @if(!empty($mdoDutyType))
                <input type="hidden" name="pk" value="{{ encrypt($mdoDutyType->pk) }}">
                @endif
                <div class="row g-2 g-md-3">

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="mb-3">

                            <x-select name="mdo_duty_type_master_pk" id="mdo_duty_type_master_pk" label="Duty Type :" formLabelClass="form-label"
                                formSelectClass="select2 "
                                value="{{ old('mdo_duty_type_master_pk', $mdoDutyType->mdo_duty_type_master_pk ?? '') }}"
                                :options="$MDODutyTypeMaster" labelRequired="true" />
                        </div>

                    </div>
                    <div class="col-12 col-sm-6 col-md-3" id="faculty_field_container" style="display: none;">
                        <div class="mb-3">
                            <x-select name="faculty_master_pk" id="faculty_master_pk" label="Faculty :" formLabelClass="form-label"
                                formSelectClass="select2"
                                value="{{ old('faculty_master_pk', $mdoDutyType->faculty_master_pk ?? '') }}"
                                :options="$facultyMaster" labelRequired="true" />
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="mb-3">
                            <x-input type="date" name="mdo_date" label="Select Date & Time :"
                                placeholder="Select Date & Time : " formLabelClass="form-label" 
                                value="{{ old('mdo_date', format_date($mdoDutyType->mdo_date, 'Y-m-d') ?? '') }}" labelRequired="true"/>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">

                        <x-input type="time" name="Time_from" label="From Time :" placeholder="From Time : "
                            formLabelClass="form-label" labelRequired="true"
                            value="{{ old('Time_from', $mdoDutyType->Time_from ?? '') }}" />

                    </div>
                    <div class="col-12 col-sm-6 col-md-3">

                        <x-input type="time" name="Time_to" label="To Time :" placeholder="To Time : "
                            formLabelClass="form-label" labelRequired="true"
                            value="{{ old('Time_to', $mdoDutyType->Time_to ?? '') }}" />

                    </div>
                </div>
                <hr>
                
                <div class="mb-3 form-actions d-flex flex-wrap justify-content-end gap-2">
                    <a href="{{ route('mdo-escrot-exemption.index') }}" class="btn btn-secondary hstack gap-2">
                        <i class="material-icons menu-icon">arrow_back</i>
                        Back
                    </a>
                    <button class="btn btn-primary hstack gap-2" type="submit">
                        <i class="material-icons menu-icon">save</i>
                        Update
                    </button>
                </div>

            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>
@endsection

@section('scripts')
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
            if (typeof DropdownSearch !== 'undefined') {
                DropdownSearch.reinit('#faculty_master_pk', { placeholder: 'Select faculty...', allowClear: true });
            }
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