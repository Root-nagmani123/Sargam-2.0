@extends('admin.layouts.master')

@section('title', 'Faculty')

@section('setup_content')
<style>
input.is-invalid {
    border-color: var(--bs-danger);
}

.mobile-duplicate {
    border: 2px solid var(--bs-danger) !important;
    background-color: rgba(var(--bs-danger-rgb), 0.05);
}

#suggestionList {
    max-height: 220px;
    overflow-y: auto;
    box-shadow: var(--bs-box-shadow);
    border-radius: var(--bs-border-radius);
    border: 1px solid var(--bs-border-color);
}

#suggestionList a {
    cursor: pointer;
    transition: background-color 0.15s ease;
}

#suggestionList a:hover:not(.disabled) {
    background-color: var(--bs-primary-bg-subtle);
}

#suggestionList .list-group-item.disabled {
    color: var(--bs-secondary-color);
}

.degree-row-wrapper,
.experience-row-wrapper {
    border-left: 3px solid var(--bs-primary) !important;
    transition: box-shadow 0.2s ease, background-color 0.2s ease;
}

.degree-row-wrapper:hover,
.experience-row-wrapper:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

@media print {

    body,
    html {
        margin: 0 !important;
        padding: 0 !important;
    }

    .container-fluid,
    .card,
    .card-body,
    .card-header {
        margin: 0 !important;
        padding-top: 10px !important;
        page-break-before: avoid !important;
    }

    .mb-4,
    .mt-4,
    .pt-4,
    .py-4 {
        margin: 0 !important;
        padding: 0 !important;
    }

    .shadow-sm,
    .shadow,
    .card {
        box-shadow: none !important;
    }

    .col-md-6,
    .col-12.col-md-6 {
        max-width: 50% !important;
        flex: 0 0 50% !important;
    }

    .row {
        display: flex !important;
        flex-wrap: wrap !important;
    }
}
</style>
<div class="container-fluid py-3 py-md-4" id="printFacultyFormData">
    <x-breadcrum title="Faculty" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    {{-- <div class="card" id="facultyForm" data-store-url="{{ route('faculty.store') }}"
    data-index-url="{{ route('faculty.index') }}">
    <div class="card-body"> --}}

        <form class="facultyForm">
            @csrf
            <input type="hidden" name="faculty_id" id="faculty_id" value="">
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-body-secondary border-0 py-3">
                    <h5 class="card-title mb-0 fw-semibold text-body d-flex align-items-center gap-2">
                        <span
                            class="rounded-2 bg-primary bg-opacity-10 text-primary p-2 d-inline-flex align-items-center justify-content-center">
                            <i class="material-icons menu-icon" style="font-size: 1.25rem;">person</i>
                        </span>
                        Personal Information
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">

                                <x-select name="facultytype" label="Faculty Type :" formLabelClass="form-label"
                                    :options="$facultyTypeList" required="true" labelRequired="true"
                                    value="{{ $hostelFloorMapping->hostel_building_master_pk ?? '' }}" />
                            </div>
                        </div>
                        <div class="col-12 col-md-6 d-none" id="facultyPaContainer">
                            <div class="mb-3">
                                <x-input name="faculty_pa" label="Faculty (PA) :" placeholder="Faculty (PA)"
                                    formLabelClass="form-label" />
                            </div>
                        </div>
                        <!--<div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <x-input
                                        name="firstName"
                                        label="First Name :"
                                        placeholder="First Name"
                                        formLabelClass="form-label"
                                        required="true"
                                        value="{{ $hostelFloorMapping->hostel_floor_master_pk ?? '' }}"
                                        labelRequired="true"
                                        formInputClass="only-letters"
                                        />
                                </div>
                            </div>-->

                        <div class="col-12 col-md-6 position-relative">
                            <div class="mb-3">
                                <x-input name="firstName" label="First Name :" placeholder="First Name"
                                    formLabelClass="form-label" required="true" labelRequired="true"
                                    title="Only letters and spaces are allowed" id="firstName"
                                    formInputClass="letters-with-space" />
                                <div id="suggestionList" class="list-group position-absolute start-0 end-0 mt-1"
                                    style="z-index: 1050; display:none;"></div>
                            </div>
                        </div>



                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <x-input name="middlename" label="Middle Name :" placeholder="Middle Name"
                                    formLabelClass="form-label" formInputClass="only-letters" />


                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <x-input name="lastname" label="Last Name :" placeholder="Last Name"
                                    formLabelClass="form-label" required="true" labelRequired="true"
                                    formInputClass="only-letters" />

                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="mb-3">
                                <x-input name="fullname" label="Full Name :" placeholder="Full Name"
                                    formLabelClass="form-label" required="true" labelRequired="true" />

                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="mb-3">
                                <x-input name="faculty_code" label="Faculty Code :" placeholder="Faculty Code"
                                    formLabelClass="form-label" class="bg-light" readonly />
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-3">

                                @php
                                $genderList = [

                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                                ];
                                @endphp
                                <x-select name="gender" label="Gender :" placeholder="Gender"
                                    formLabelClass="form-label" :options="$genderList" required="true"
                                    labelRequired="true" />

                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">

                                <x-input type="text" name="landline" label="Landline Number"
                                    placeholder="Landline Number" formLabelClass="form-label" inputmode="numeric"
                                    pattern="\d*" formInputClass="only-numbers" />

                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <x-input type="text" name="mobile" label="Mobile Number :" placeholder="Mobile Number"
                                    formLabelClass="form-label" required="true" labelRequired="true" inputmode="numeric"
                                    pattern="\d*" formInputClass="only-numbers" />
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <x-input name="current_designation" label="Current Designation :"
                                    placeholder="Current Designation" formLabelClass="form-label" />
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <x-input name="current_department" label="Current Department :"
                                    placeholder="Current Department" formLabelClass="form-label" />
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <x-select name="country" label="Country :" placeholder="Country"
                                    formLabelClass="form-label" :options="$country" required="true"
                                    labelRequired="true" />

                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">

                                <!--<x-select
                                        name="state"
                                        label="State :"
                                        placeholder="State"
                                        formLabelClass="form-label"
                                        {{-- :options="$state" --}}
                                        required="true"
                                        labelRequired="true"
                                        />-->
                                <x-select name="state" label="State :" placeholder="State" formLabelClass="form-label"
                                    :options="$state" required="true" labelRequired="true" />

                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">

                                <!--<x-select
                                        name="district"
                                        label="District :"
                                        placeholder="District"
                                        formLabelClass="form-label"
                                        {{-- :options="$district" --}}
                                        required="true"
                                        />-->

                                        <x-select
                                        name="district"
                                        label="District :"
                                        placeholder="District"
                                        formLabelClass="form-label"
                                        :options="$district"
                                         required="true"
                                         labelRequired="true"
                                        />

                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">

                                <!--<x-select
                                        name="city"
                                        label="City :"
                                        placeholder="City"
                                        formLabelClass="form-label"
                                        {{-- :options="$city" --}}
                                        required="true"
                                        labelRequired="true"
                                        />-->

                                        <x-select
                                        name="city"
                                        label="City :"
                                        placeholder="City"
                                        formLabelClass="form-label"
                                         required="true"
                                         labelRequired="true"
                                        :options="$city" />

                                </div>
                            </div>

                            <div class="col-md-6 d-none" id="otherCityContainer">
                                <div class="mb-3">

                                    <x-input
                                        name="other_city"
                                        label="Other City :"
                                        placeholder="Other City"
                                        formLabelClass="form-label"
                                        value=""
                                        />

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                <x-input
                                    name="residence_address"
                                    label="Residence Address :"
                                    placeholder="Residence Address :"
                                    formLabelClass="form-label"
                                    />

                                </div>

                            </div>
                        </div>

                        <div class="col-12 col-md-6 d-none" id="otherCityContainer">
                            <div class="mb-3">

                                <x-input name="other_city" label="Other City :" placeholder="Other City"
                                    formLabelClass="form-label" value="" />

                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <x-input name="residence_address" label="Residence Address :"
                                    placeholder="Residence Address :" formLabelClass="form-label" />

                            </div>

                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <x-input name="permanent_address" label="Permanent Address :"
                                    placeholder="Permanent Address :" formLabelClass="form-label" />
                            </div>

                        </div>
                        <div class="col-12 col-md-6">

                            <x-input name="email" label="Email :" placeholder="Email :" formLabelClass="form-label"
                                required="true" labelRequired="true" />

                        </div>
                        <div class="col-12 col-md-6">
                            <x-input name="alternativeEmail" label="Alternate Email :" placeholder="Alternate Email :"
                                formLabelClass="form-label" />

                        </div>
                        <div class="col-12 col-md-6">
                            <x-input type="file" name="photo" label="Photo Upload:" placeholder="Photo Upload:"
                                formLabelClass="form-label"
                                helperSmallText="Please upload a recent passport-sized photo" />

                            <!-- Preview Container -->
                            <div class="mt-2">
                                <img id="photoPreview" src="#" alt="Photo Preview" class="img-thumbnail rounded d-none"
                                    style="max-width: 200px;">
                            </div>
                            <div class="existing-photo"></div>
                        </div>

                        <div class="col-12 col-md-6">

                            <x-input type="file" name="document" label="Document upload :"
                                placeholder="Document upload :" formLabelClass="form-label"
                                helperSmallText="CV or any other supporting document" />

                            <!-- PDF Preview -->
                            <div class="d-flex align-items-start mt-2">
                                <iframe id="documentPreviewPDF" class="d-none border rounded"
                                    style="width: 200px; height: 200px;"></iframe>
                            </div>

                            <!-- Existing Document Link -->
                            <div class="existing-document mt-2"></div>
                        </div>


                    </div>
                </div>
            </div>

    <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
        <div class="card-header bg-body-secondary border-0 py-3">
            <h5 class="card-title mb-0 fw-semibold text-body d-flex align-items-center gap-2">
                <span
                    class="rounded-2 bg-primary bg-opacity-10 text-primary p-2 d-inline-flex align-items-center justify-content-center">
                    <i class="material-icons menu-icon" style="font-size: 1.25rem;">school</i>
                </span>
                Qualification Details
            </h5>
        </div>
                    <div class="card-body p-4">
                        <div>
                            <div id="education_fields">
                                <div class="row degree-row g-3">
                    <div class="col-12 col-md-6 col-lg-3">

                        <x-input name="degree[]" label="Degree :" placeholder="Degree Name" formLabelClass="form-label"
                            helperSmallText="Bachelors, Masters, PhD" />

                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <x-input name="university_institution_name[]" label="University/Institution Name :"
                            placeholder="University/Institution Name" formLabelClass="form-label" />
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        {{-- <x-input
                                        type="number"
                                        name="year_of_passing[]"
                                        label="Year of Passing :"
                                        placeholder="Year of Passing"
                                        formLabelClass="form-label"
                                        min="1900"
                                        max="{{ date('Y') }}"
                        step="1"
                        required="true"
                        /> --}}

                        <x-select name="year_of_passing[]" label="Year of Passing :" placeholder="Year of Passing"
                            formLabelClass="form-label" :options="$years"
                            helperSmallText="Select the year of passing" />
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <x-input type="number" min="0" max="100" name="percentage_CGPA[]" label="Percentage/CGPA"
                            placeholder="Percentage/CGPA" formLabelClass="form-label" />

                    </div>
                    <div class="col-12 col-md-6 col-lg-3 mt-3 mt-lg-0">
                        <x-input type="file" name="certificate[]" label="Certificates/Documents Upload :"
                            placeholder="Certificates/Documents Upload" formLabelClass="form-label"
                            helperSmallText="Please upload your certificates/documents, if any" />
                    </div>
                    <div class="existing-certificate col-12"></div>
                    <div class="col-12 col-lg-9 d-flex align-items-end justify-content-end">
                        <button onclick="education_fields();" class="btn btn-success fw-medium rounded-pill px-3"
                            type="button">
                            <i class="material-icons menu-icon me-1" style="font-size: 1.1rem;">add</i>
                            Add Qualification
                        </button>
                    </div>
                </div>
                            </div>
                        </div>
                    </div>
    </div>
    <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
        <div class="card-header bg-body-secondary border-0 py-3">
            <h5 class="card-title mb-0 fw-semibold text-body d-flex align-items-center gap-2">
                <span
                    class="rounded-2 bg-primary bg-opacity-10 text-primary p-2 d-inline-flex align-items-center justify-content-center">
                    <i class="material-icons menu-icon" style="font-size: 1.25rem;">work</i>
                </span>
                Experience Details
            </h5>
        </div>
        <div class="card-body p-4">
            <div id="experience_fields">
                <div class="row experience-row g-3">
                <div class="col-12 col-md-6 col-lg-3">
                    <x-input name="experience[]" label="Years of Experience :" placeholder="Years of Experience"
                        formLabelClass="form-label" />
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <x-input name="specialization[]" label="Area of Specialization :"
                        placeholder="Area of Specialization blade file" formLabelClass="form-label" />
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <x-input name="institution[]" label="Previous Institutions :" placeholder="Previous Institutions"
                        formLabelClass="form-label" />
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <x-input name="position[]" label="Position Held :" placeholder="Position Held"
                        formLabelClass="form-label" />
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <x-input type="number" name="duration[]" label="Duration :" placeholder="Duration"
                        formLabelClass="form-label" min="0" />
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <x-input name="work[]" label="Nature of Work :" placeholder="Nature of Work"
                        formLabelClass="form-label" />
                </div>
                <div class="col-12 col-lg-6 d-flex align-items-end justify-content-end">
                    <button onclick="experience_fields();" class="btn btn-success rounded-pill px-3" type="button">
                        <i class="material-icons menu-icon me-1" style="font-size: 1.1rem;">add</i>
                        Add Experience
                    </button>
                </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
        <div class="card-header bg-body-secondary border-0 py-3">
            <h5 class="card-title mb-0 fw-semibold text-body d-flex align-items-center gap-2">
                <span
                    class="rounded-2 bg-primary bg-opacity-10 text-primary p-2 d-inline-flex align-items-center justify-content-center">
                    <i class="material-icons menu-icon" style="font-size: 1.25rem;">account_balance</i>
                </span>
                Bank Details
            </h5>
        </div>
        <div class="card-body p-4">
            <div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <x-input name="bankname" label="Bank Name :" placeholder="Bank Name"
                            formLabelClass="form-label" />
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input type="text" name="accountnumber" label="Account Number :" placeholder="Account Number"
                            formLabelClass="form-label" formInputClass="only-numbers" />
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input name="ifsccode" label="IFSC Code :" placeholder="IFSC Code"
                            formLabelClass="form-label" />
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input type="text" name="pannumber" label="PAN Number :" placeholder="PAN Number"
                            formLabelClass="form-label" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
        <div class="card-header bg-body-secondary border-0 py-3">
            <h5 class="card-title mb-0 fw-semibold text-body d-flex align-items-center gap-2">
                <span
                    class="rounded-2 bg-primary bg-opacity-10 text-primary p-2 d-inline-flex align-items-center justify-content-center">
                    <i class="material-icons menu-icon" style="font-size: 1.25rem;">info</i>
                </span>
                Other information
            </h5>
        </div>
        <div class="card-body p-4">
            <div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <x-input type="file" name="researchpublications" label="Research Publications :"
                            placeholder="Research Publications" formLabelClass="form-label"
                            helperSmallText="Please upload your research publications, if any" />
                        <div class="research_publications"></div>
                        <div class="mt-2">
                            <iframe id="researchPreview" class="d-none rounded border"
                                style="width:100%; height:250px;"></iframe>
                        </div>
                        <div class="existing-research mt-2"></div>
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input type="file" name="professionalmemberships" label="Professional Memberships :"
                            placeholder="Professional Memberships" formLabelClass="form-label"
                            helperSmallText="Please upload your professional memberships, if any" />
                        <div class="mt-2">
                            <iframe id="membershipPreview" class="d-none rounded border"
                                style="width:100%; height:250px;"></iframe>
                        </div>
                        <div class="existing-membership mt-2"></div>
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input type="file" name="recommendationdetails" label="Reference/Recommendation Details :"
                            placeholder="Reference/Recommendation Details" formLabelClass="form-label"
                            helperSmallText="Please upload your reference/recommendation details, if any" />
                        <div class="mt-2">
                            <iframe id="referencePreview" class="d-none rounded border"
                                style="width:100%; height:250px;"></iframe>
                        </div>
                        <div class="existing-reference mt-2"></div>
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input type="date" name="joiningdate" label="Joining Date :" placeholder="Joining Date"
                            formLabelClass="form-label" value="{{ $value ?? '' }}" />
                    </div>
                </div>
            </div>
        </div>
    </div>


<div class="card">
     <div class="card-body">
          <div class="row">
               <div class="col-12">
                    <label for="sector" class="form-label">Current Sector : <span class="text-danger">*</span></label>
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input success" type="radio" name="current_sector"
                                id="success-radio" value="1">
                            <label class="form-check-label" for="success-radio">Government Sector</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input success" type="radio" name="current_sector"
                                id="success2-radio" value="2">
                            <label class="form-check-label" for="success2-radio">Private Sector</label>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label for="expertise" class="form-label fw-medium">Area of Expertise :</label>
                    <div class="mb-0 expertise-row">
                        <x-checkbox name="faculties[]" label="Area of Expertise :" formLabelClass="form-label"
                            :options="$faculties" />
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex flex-wrap justify-content-end align-items-center gap-2">

                <button class="btn btn-primary d-flex align-items-center gap-2 rounded-pill px-4" type="button"
                    id="saveFacultyForm">
                    <i class="material-icons menu-icon" style="font-size: 1.1rem;">save</i>
                    Save
                </button>
                <a href="{{ route('faculty.index') }}"
                    class="btn btn-outline-secondary d-flex align-items-center gap-2 rounded-pill px-4">
                    <i class="material-icons menu-icon" style="font-size: 1.1rem;">arrow_back</i>
                    Back
                </a>
            </div>
        </div>
    </div>

    </form>
    {{-- </div>
    </div> --}}
    <!-- end Vertical Steps Example -->
</div>


@endsection

@section('scripts')
<script>
// Show/Hide Faculty (PA) field based on Faculty Type
$(document).ready(function() {
    function toggleFacultyPaField() {
        var facultyType = $('select[name="facultytype"]').val();
        if (facultyType == '1') { // Internal
            $('#facultyPaContainer').removeClass('d-none');
        } else {
            $('#facultyPaContainer').addClass('d-none');
            $('input[name="faculty_pa"]').val(''); // Clear the field when hidden
        }
    }

    // Initial check on page load
    toggleFacultyPaField();

    // On change of faculty type
    $('select[name="facultytype"]').on('change', function() {
        toggleFacultyPaField();
    });
});
</script>
<script>
let isMobileDuplicate = false;
$(document).ready(function() {

    /*if (isMobileDuplicate) {
    toastr.error("Cannot save. Mobile number already exists.");
    return;
}*/

    if (isMobileDuplicate && !facultyId) {
        toastr.error("Mobile already exists. Please search and update the existing faculty.");
        return;
    }


    // Check Email
    $('input[name="email"]').on('blur', function() {
        let email = $(this).val();
        if (email) {
            checkUnique('email', email, $(this));
        }
    });

    // Check Mobile
    $('input[name="mobile"]').on('blur', function() {
        let mobile = $(this).val();
        if (mobile) {
            checkUnique('mobile', mobile, $(this));
        }
    });

    /*
   // ENABLE SAVE BUTTON WHEN USER EDITS MOBILE
    $('input[name="mobile"]').on('input', function () {
    $("#saveFacultyForm").prop('disabled', false);
    isMobileDuplicate = false;
    });
    */

    function checkUnique(type, value, inputElement) {



        $.ajax({
            url: "{{ route('faculty.checkUnique') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                type: type,
                value: value
            },
            success: function(response) {
                inputElement.next('.unique-error').remove(); // remove old messages
                inputElement.removeClass('mobile-duplicate');

                if (response.exists) {
                    inputElement.after('<small class="text-danger unique-error">' + response
                        .message + '</small>');
                    // inputElement.addClass('is-invalid');
                    inputElement.addClass('is-invalid mobile-duplicate');

                    /*if (type === 'mobile') {
                    isMobileDuplicate = true;
                    $("#saveFacultyForm").prop('disabled', true);
                }
				*/
                    if (type === 'mobile') {
                        isMobileDuplicate = true;
                        toastr.warning("Mobile exists. You can update other details.");
                    }

                } else {
                    inputElement.after('<small class="text-success unique-error">' + response
                        .message + '</small>');
                    inputElement.removeClass('is-invalid');

                    if (type === 'mobile') {
                        isMobileDuplicate = false;
                        $("#saveFacultyForm").prop('disabled', false);


                    }
                }
            }
        });
    }
});


$(document).ready(function() {

    let $input = $('#firstName');
    let $suggestionBox = $('#suggestionList');



    // ====== ADD THIS BLOCK HERE (before fillFacultyForm) ======
    $("input[name='document']").on("change", function(e) {
        const file = e.target.files[0];

        // Reset preview
        $("#documentPreviewPDF").addClass("d-none").attr("src", "");
        $(".existing-document").html("");

        if (!file) return;

        const type = file.type;
        const fileURL = URL.createObjectURL(file);

        // PDF preview
        if (type === "application/pdf") {
            $("#documentPreviewPDF")
                .attr("src", fileURL)
                .removeClass("d-none");
        }
        // DOC/DOCX — no preview
        else if (
            type === "application/msword" ||
            type === "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        ) {
            $(".existing-document").html(`
                <span class="text-info">DOC File Selected: ${file.name}</span>
            `);
        } else {
            alert("Only PDF or DOC/DOCX allowed.");
            $(this).val("");
        }
    });
    // ====== END BLOCK ======


    //$('#suggestionList').hide();

    // Hide suggestions when typing
    $input.on("keydown", function() {
        setTimeout(function() {
            $suggestionBox.hide();
        }, 100);
    });

    // Hide suggestions when mouse leaves or user tabs to next input
    $input.on("blur", function() {
        setTimeout(function() {
            $suggestionBox.hide();
        }, 200);
    });

    // Hide when mouse leaves suggestion dropdown
    $suggestionBox.on("mouseleave", function() {
        setTimeout(function() {
            $suggestionBox.hide();
        }, 200);
    });


    // =============================
    // 1) Live Search Full Name
    // =============================
    $input.on('input', function() {
        let query = $(this).val().trim();
        // Always hide 'No results found' if any value is present
        if (query.length > 0) {
            $suggestionBox.hide();
        }
    });

    $input.on('keyup', function() {
        let query = $(this).val().trim();
        $suggestionBox.find('.list-group-item.disabled').remove();
        if (query.length > 1) {
            $.ajax({
                url: "{{ route('faculty.checkFullName') }}",
                type: "GET",
                data: {
                    query: query
                },
                success: function(response) {
                    let html = "";
                    if (response.suggestions.length > 0) {
                        response.suggestions.forEach(function(item) {
                            let displayText = item.full_name;
                            if (item.faculty_code) {
                                displayText += ` (${item.faculty_code})`;
                            }
                            html += `<a href="#" class="list-group-item list-group-item-action suggestion-item"
                                      data-id="${item.id}"
                                      data-fullname="${item.full_name}">
                                        ${displayText}
                                     </a>`;
                        });
                    } else {
                        html = '<a class="list-group-item disabled">No results found</a>';
                    }
                    $suggestionBox.html(html).show();
                    // If "No results found" is shown, hide it after 1.5 seconds
                    if (response.suggestions.length === 0) {
                        setTimeout(function() {
                            // Only hide if still showing and still no suggestions
                            if ($suggestionBox.find('.list-group-item.disabled')
                                .length > 0) {
                                $suggestionBox.hide();
                            }
                        }, 1500);
                    }
                }
            });
        } else {
            $suggestionBox.hide();
        }
    });

    function lockNameFields() {
        $("input[name='firstName'], input[name='middlename'], input[name='lastname']")
        //.prop("readonly", true)
        //.addClass("bg-light");
    }

    function fillFacultyForm(faculty) {

        //Personal Information

        // Auto-fill name
        lockNameFields();


        $("input[name='firstName']").val(faculty.first_name ?? "");
        $("input[name='middlename']").val(faculty.middle_name ?? "");
        $("input[name='lastname']").val(faculty.last_name ?? "");
        $("input[name='fullname']").val(
            faculty.first_name + " " + (faculty.middle_name ?? "") + " " + faculty.last_name
        );

        // Auto-fill remaining fields
        $("select[name='facultytype']").val(faculty.faculty_type).trigger('change');
        $("input[name='faculty_pa']").val(faculty.faculty_pa ?? '');
        $("input[name='faculty_code']").val(faculty.faculty_code);
        $("input[name='landline']").val(faculty.landline_no);
        $("input[name='mobile']").val(faculty.mobile_no);
        $("input[name='email']").val(faculty.email_id);


        $("input[name='current_designation']").val(faculty.current_designation);
        $("input[name='current_department']").val(faculty.current_department);

        $("input[name='alternativeEmail']").val(faculty.alternate_email_id);

        $("select[name='gender']").val(faculty.gender);

        // --- COUNTRY ---

        $("select[name='country']").val(faculty.country_master.pk);


        // --- STATE ---

        $("select[name='state']").val(faculty.state_master.Pk);


        // --- DISTRICT ---

        $("select[name='district']").val(faculty.district_master.pk);


        // --- CITY ---

        $("select[name='city']").val(faculty.city_master.pk);
        $("input[name='residence_address']").val(faculty.Residence_address);


        $("input[name='permanent_address']").val(faculty.Permanent_Address);


        if (faculty.photo_uplode_path) {
            const photoURL = `/storage/${faculty.photo_uplode_path}`;

            // show inside <img id="photoPreview">
            $("#photoPreview").attr("src", photoURL);

            // show "view existing photo" link
            $(".existing-photo").html(`
        <a href="${photoURL}"
            target="_blank"
            class="text-primary d-inline-flex align-items-center gap-1">
                View Existing Photo
                <iconify-icon icon="lets-icons:eye" width="20" height="20"></iconify-icon>
            </a>
    `);
        }

        if (faculty.Doc_uplode_path) {
            const docURL = `/storage/${faculty.Doc_uplode_path}`;

            $(".existing-document").html(`
        <a href="${docURL}" target="_blank" class="text-primary d-inline-flex align-items-center gap-1">
            View Existing Document <iconify-icon icon="lets-icons:eye" width="20" height="20"></iconify-icon>
        </a>
    `);

            // Reset preview (new upload only)
            $("#documentPreviewPDF").addClass("d-none").attr("src", "");
        }

        if (faculty.Professional_Memberships_doc_upload_path) {
            const docURL = `/storage/${faculty.Professional_Memberships_doc_upload_path}`;

            $(".existing-membership").html(`
        <a href="${docURL}" target="_blank" class="text-primary d-inline-flex align-items-center gap-1">
            View Membership Document <iconify-icon icon="lets-icons:eye" width="20" height="20"></iconify-icon>
        </a>
    `);

            // Reset preview (new upload only)
            $("#membershipPreview").addClass("d-none").attr("src", "");
        }

        if (faculty.Reference_Recommendation) {
            const docURL = `/storage/${faculty.Reference_Recommendation}`;

            $(".existing-reference").html(`
        <a href="${docURL}"
   target="_blank"
   class="text-primary d-inline-flex align-items-center gap-1 fw-medium">
    View Reference Document
    <iconify-icon icon="lets-icons:eye" width="20" height="20"></iconify-icon>
</a>

    `);

            // Reset preview (new upload only)
            $("#referencePreview").addClass("d-none").attr("src", "");
        }


        //Qualification Details

        // Clear existing dynamic (appended) rows (wrappers contain the degree-row)
        $(".degree-row-wrapper").remove();
        $(".experience-row-wrapper").remove();

        // Add rows if needed for qualifications
        for (let i = 1; i < faculty.faculty_qualification_map.length; i++) {
            education_fields();
        }
        faculty.faculty_qualification_map.forEach(function(q, index) {
            const row = $(".degree-row").eq(index);
            row.find("input[name='degree[]']").val(q.Degree_name);
            row.find("input[name='university_institution_name[]']").val(q.University_Institution_Name);
            row.find("select[name='year_of_passing[]']").val(q.Year_of_passing).trigger('change');
            row.find("input[name='percentage_CGPA[]']").val(q.Percentage_CGPA);
            if (q.Certifcates_upload_path) {
                row.find(".existing-certificate").html(`
           <a href="storage/${q.Certifcates_upload_path}"
            target="_blank"
            class="text-primary d-inline-flex align-items-center gap-1 fw-medium">
                View Existing Certificate
                <iconify-icon icon="lets-icons:eye" width="20" height="20"></iconify-icon>
            </a>

        `);
            }
        });

        // Add rows if needed for experience
        for (let i = 1; i < faculty.faculty_experience_map.length; i++) {
            experience_fields();
        }
        faculty.faculty_experience_map.forEach(function(exp, index) {
            const row = $(".experience-row").eq(index);
            row.find("input[name='experience[]']").val(exp.Years_Of_Experience);
            row.find("input[name='specialization[]']").val(exp.Specialization);
            row.find("input[name='institution[]']").val(exp.pre_Institutions);
            row.find("input[name='position[]']").val(exp.Position_hold);
            row.find("input[name='duration[]']").val(exp.duration);
            row.find("input[name='work[]']").val(exp.Nature_of_Work);
        });

        //Bank Details
        $("input[name='bankname']").val(faculty.bank_name);
        $("input[name='accountnumber']").val(faculty.Account_No);
        $("input[name='ifsccode']").val(faculty.IFSC_Code);
        $("input[name='pannumber']").val(faculty.PAN_No);

        if (faculty.Rech_Publi_Upload_path) {
            const docURL = `/storage/${faculty.Rech_Publi_Upload_path}`;

            $(".existing-research").html(`
        <a href="${docURL}" target="_blank" class="text-primary d-inline-flex align-items-center gap-1 fw-medium">
            View Existing Document <iconify-icon icon="lets-icons:eye" width="20" height="20"></iconify-icon>
        </a>
    `);

            // Reset preview (new upload only)
            $("#researchPreview").addClass("d-none").attr("src", "");
        }


        if (faculty.Rech_Publi_Upload_path) {
            const docURL = `/storage/${faculty.Rech_Publi_Upload_path}`;

            $(".existing-research").html(`
        <a href="${docURL}" target="_blank" class="text-primary d-inline-flex align-items-center gap-1 fw-medium">
            View Existing Document <iconify-icon icon="lets-icons:eye" width="20" height="20"></iconify-icon>
        </a>
    `);

            // Reset preview (new upload only)
            $("#researchPreview").addClass("d-none").attr("src", "");
        }


        if (faculty.joining_date) {
            let formattedDate = new Date(faculty.joining_date).toISOString().slice(0, 10);
            $("input[name='joiningdate']").val(formattedDate);
        }

        faculty.faculty_expertise_map.forEach(item => {
            $('input[name="faculties[]"][value="' + item.faculty_expertise_pk + '"]').prop("checked",
                true);
        });


        $('input[name="current_sector"][value="' + faculty.faculty_sector + '"]').prop("checked", true);

        //document.querySelector('input[name="current_sector"]:checked').value;
        //document.querySelectorAll('input[name="faculties[]"]:checked');


        $("#faculty_id").val(faculty.pk);

    }

    // =========================================
    // 3) When User Clicks a Suggested Full Name
    // =========================================
    $(document).on('click', '.suggestion-item', function(e) {
        e.preventDefault();

        let id = $(this).data('id');
        let fullname = $(this).data('fullname');

        $('#firstName').val(fullname);

        $('#suggestionList').hide();

        // GET full faculty details
        $.ajax({
            url: "/faculty/details/" + id,
            type: "GET",
            success: function(faculty) {
                fillFacultyForm(faculty); // <--- AUTO FILL FULL FORM


            }
        });
    });




});
</script>

@endsection