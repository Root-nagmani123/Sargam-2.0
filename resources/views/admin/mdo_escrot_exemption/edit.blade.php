@extends('admin.layouts.master')
@section('css')
{{--
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.8.1/themes/prism.min.css" rel="stylesheet" /> --}}
<link rel="stylesheet" href="{{asset('admin_assets/css/dual-listbox.css')}}">
@endsection
@section('title', 'MDO Escrot Exemption')

@section('setup_content')

<div class="container-fluid">
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
                <div class="col-md-8">
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
                <div class="row">

                    

                    <div class="col-md-3">
                        <div class="mb-3">

                            <x-select name="mdo_duty_type_master_pk" id="mdo_duty_type_master_pk" label="Duty Type :" formLabelClass="form-label"
                                formSelectClass="select2 "
                                value="{{ old('mdo_duty_type_master_pk', $mdoDutyType->mdo_duty_type_master_pk ?? '') }}"
                                :options="$MDODutyTypeMaster" labelRequired="true" />
                        </div>

                    </div>
                    <div class="col-md-3" id="faculty_field_container" style="display: none;">
                        <div class="mb-3">
                            <x-select name="faculty_master_pk" id="faculty_master_pk" label="Faculty :" formLabelClass="form-label"
                                formSelectClass="select2"
                                value="{{ old('faculty_master_pk', $mdoDutyType->faculty_master_pk ?? '') }}"
                                :options="$facultyMaster" labelRequired="true" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <x-input type="date" name="mdo_date" label="Select Date & Time :"
                                placeholder="Select Date & Time : " formLabelClass="form-label" 
                                value="{{ old('mdo_date', format_date($mdoDutyType->mdo_date, 'Y-m-d') ?? '') }}" labelRequired="true"/>
                        </div>
                    </div>

                    <div class="col-md-3">

                        <x-input type="time" name="Time_from" label="From Time :" placeholder="From Time : "
                            formLabelClass="form-label" labelRequired="true"
                            value="{{ old('Time_from', $mdoDutyType->Time_from ?? '') }}" />

                    </div>
                    <div class="col-md-3">

                        <x-input type="time" name="Time_to" label="To Time :" placeholder="To Time : "
                            formLabelClass="form-label" labelRequired="true"
                            value="{{ old('Time_to', $mdoDutyType->Time_to ?? '') }}" />

                    </div>
                </div>
                <hr>
                
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                        <i class="material-icons menu-icon">save</i>
                        Update
                    </button>
                    <a href="{{ route('mdo-escrot-exemption.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
                        <i class="material-icons menu-icon">arrow_back</i>
                        Back
                    </a>
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
            // Reinitialize select2 if needed
            if ($('#faculty_master_pk').hasClass('select2-hidden-accessible')) {
                $('#faculty_master_pk').select2('destroy');
            }
            $('#faculty_master_pk').select2();
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