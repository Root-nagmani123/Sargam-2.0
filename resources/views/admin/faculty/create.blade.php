@extends('admin.layouts.master')

@section('title', 'Faculty')

@section('content')
<style>
input.is-invalid {
    border-color: #dc3545;
}

#suggestionList a {
    cursor: pointer;
}
// print functionality
@media print {
    body, html {
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

    .mb-4, .mt-4, .pt-4, .py-4 {
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
// print functionality
</style>
<div class="container-fluid" id="printFacultyFormData">
    <x-breadcrum title="Faculty" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    {{-- <div class="card" id="facultyForm" data-store-url="{{ route('faculty.store') }}" data-index-url="{{ route('faculty.index') }}">
        <div class="card-body"> --}}

            <form class="facultyForm">
			  @csrf
			  <input type="hidden" name="faculty_id" id="faculty_id" value="">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Personal Information</h4>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">

                                    <x-select
                                        name="facultytype"
                                        label="Faculty Type :"
                                        formLabelClass="form-label"
                                        :options="$facultyTypeList"
                                        required="true"
                                        labelRequired="true"
                                        value="{{ $hostelFloorMapping->hostel_building_master_pk ?? '' }}"
                                        />
                                </div>
                            </div>
                            <!--<div class="col-md-6">
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

							<div class="col-md-6">
							<div class="mb-3">
								<x-input
									name="firstName"
									label="First Name :"
									placeholder="First Name"
									formLabelClass="form-label"
									required="true"
									labelRequired="true"
									title="Only letters and spaces are allowed"
									id="firstName"
									formInputClass="letters-with-space"
								/>

								<div id="suggestionList" class="list-group position-absolute" style="z-index: 1000; width:100%; display:none;"></div>
							</div>
						</div>



                            <div class="col-md-6">
                                <div class="mb-3">

                                    <x-input
                                        name="middlename"
                                        label="Middle Name :"
                                        placeholder="Middle Name"
                                        formLabelClass="form-label"
                                        required="true"
                                        formInputClass="only-letters"
                                        />


                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">

                                    <x-input
                                        name="lastname"
                                        label="Last Name :"
                                        placeholder="Last Name"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        formInputClass="only-letters"
                                        />

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">

                                    <x-input
                                        name="fullname"
                                        label="Full Name :"
                                        placeholder="Full Name"
                                        formLabelClass="form-label"
                                        required="true"
                                        />

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">

                                    @php
                                        $genderList = [

                                            'male' => 'Male',
                                            'female' => 'Female',
                                            'other' => 'Other',
                                        ];
                                    @endphp
                                    <x-select
                                        name="gender"
                                        label="Gender :"
                                        placeholder="Gender"
                                        formLabelClass="form-label"
                                        :options="$genderList"
                                        required="true"
                                        labelRequired="true"
                                        />

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">

                                    <x-input
                                        type="text"
                                        name="landline"
                                        label="Landline Number"
                                        placeholder="Landline Number"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        inputmode="numeric"
                                        pattern="\d*"
                                        formInputClass="only-numbers"
                                        />

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <x-input
                                        type="text"
                                        name="mobile"
                                        label="Mobile Number :"
                                        placeholder="Mobile Number"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        inputmode="numeric"
                                        pattern="\d*"
                                        formInputClass="only-numbers"
                                        />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <x-select
                                        name="country"
                                        label="Country :"
                                        placeholder="Country"
                                        formLabelClass="form-label"
                                        :options="$country"
                                        required="true"
                                        labelRequired="true"
                                        />

                                </div>
                            </div>
                            <div class="col-md-6">
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
						<x-select
							name="state"
							label="State :"
							placeholder="State"
							formLabelClass="form-label"
							:options="$state"


							/>


                                </div>
                            </div>
                            <div class="col-md-6">
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

						/>

                                </div>
                            </div>
                            <div class="col-md-6">
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
                :options="$city"

                />

                                </div>
                            </div>

                            <div class="col-md-6 d-none" id="otherCityContainer">
                                <div class="mb-3">

                                    <x-input
                                        name="other_city"
                                        label="Other City :"
                                        placeholder="Other City"
                                        formLabelClass="form-label"
                                        required="true"
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
                            <div class="col-md-6">
                                <div class="mb-3">
                                <x-input
                                    name="permanent_address"
                                    label="Permanent Address :"
                                    placeholder="Permanent Address :"
                                    formLabelClass="form-label"
                                    />
                                </div>

                            </div>
                            <div class="col-md-6">

                                <x-input
                                    name="email"
                                    label="Email :"
                                    placeholder="Email :"
                                    formLabelClass="form-label"
                                    labelRequired="true"
                                    />

                            </div>
                            <div class="col-md-6">
                                <x-input
                                    name="alternativeEmail"
                                    label="Alternate Email :"
                                    placeholder="Alternate Email :"
                                    formLabelClass="form-label"

                                    />

                            </div>
                            <div class="col-md-6 mt-3">
                                <x-input
                                    type="file"
                                    name="photo"
                                    label="Photo Upload:"
                                    placeholder="Photo Upload:"
                                    formLabelClass="form-label"
                                    labelRequired="true"
                                    helperSmallText="Please upload a recent passport-sized photo"
                                />

                                <!-- Preview Container -->
                                <div class="mt-2">
                                    <img id="photoPreview" src="#" alt="Photo Preview" class="img-thumbnail d-none" style="max-width: 200px;">
                                </div>
								 <div class="existing-photo"></div>
                            </div>

                            <div class="col-md-6 mt-3">

                                <x-input
                                    type="file"
                                    name="document"
                                    label="Document upload :"
                                    placeholder="Document upload :"
                                    formLabelClass="form-label"
                                    labelRequired="true"
                                    helperSmallText="CV or any other supporting document"
                                    />

                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div>
                            <h4 class="card-title">Qualification Details</h4>
                            <hr>
                            <div id="education_fields" class="my-4"></div>

                            <div class="row degree-row" id="education_fields">
                                <div class="col-3">

                                    <x-input
                                        name="degree[]"
                                        label="Degree :"
                                        placeholder="Degree"
                                        formLabelClass="form-label"
                                        required="true"
                                        helperSmallText="Bachelors, Masters, PhD"
                                        labelRequired="true"
                                        />

                                </div>
                                <div class="col-3">
                                    <x-input
                                        name="university_institution_name[]"
                                        label="University/Institution Name :"
                                        placeholder="University/Institution Name"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        />
                                </div>
                                <div class="col-3">
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

                                    <x-select
                                        name="year_of_passing[]"
                                        label="Year of Passing :"
                                        placeholder="Year of Passing"
                                        formLabelClass="form-label"
                                        :options="$years"
                                        required="true"
                                        helperSmallText="Select the year of passing"
                                        labelRequired="true"
                                    />
                                </div>
                                <div class="col-3">
                                    <x-input
                                        type="number"
                                        min="0"
                                        max="100"
                                        name="percentage_CGPA[]"
                                        label="Percentage/CGPA"
                                        placeholder="Percentage/CGPA"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        />

                                </div>
                                <div class="col-3 mt-3">

                                    <x-input
                                        type="file"
                                        name="certificate[]"
                                        label="Certificates/Documents Upload :"
                                        placeholder="Certificates/Documents Upload"
                                        formLabelClass="form-label"
                                        required="true"
                                        helperSmallText="Please upload your certificates/documents, if any"
                                        labelRequired="true"
                                        />
                                  </div>
								  <div class="existing-certificate"></div>
                                <div class="col-9">
                                    <label for="Schoolname" class="form-label"></label>
                                    <div class="mb-3 float-end">
                                        <button onclick="education_fields();" class="btn btn-success fw-medium" type="button">
                                            <i class="material-icons menu-icon">add</i>
                                        </button>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Experience Details</h4>
                            <hr>
                            <div id="experience_fields_wrapper" class="my-4"></div>
                            <div class="row experience-row" id="experience_fields">
                                <div class="col-3">
                                    <x-input
                                        name="experience[]"
                                        label="Years of Experience :"
                                        placeholder="Years of Experience"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        />
                                </div>
                                <div class="col-3">

                                    <x-input
                                        name="specialization[]"
                                        label="Area of Specialization :"
                                        placeholder="Area of Specialization"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        />

                                </div>
                                <div class="col-3">
                                    <x-input
                                        name="institution[]"
                                        label="Previous Institutions :"
                                        placeholder="Previous Institutions"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        />
                                </div>
                                <div class="col-3">
                                    <x-input
                                        name="position[]"
                                        label="Position Held :"
                                        placeholder="Position Held"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        />

                                </div>
                                <div class="col-3 mt-3">
                                    <x-input
                                        type="number"
                                        name="duration[]"
                                        label="Duration :"
                                        placeholder="Duration"
                                        formLabelClass="form-label"
                                        min="0"
                                        required="true"
                                        labelRequired="true"
                                        />
                                </div>
                                <div class="col-3 mt-3">
                                    <x-input
                                        name="work[]"
                                        label="Nature of Work :"
                                        placeholder="Nature of Work"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        />
                                </div>
                                <div class="col-6">

                                    <label for="Schoolname" class="form-label"></label>
                                    <div class="mb-3 float-end">
                                        <button onclick="experience_fields();" class="btn btn-success btn-sm" type="button">
                                            <i class="material-icons menu-icon">add</i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">

                        <div>
                            <h4 class="card-title">Bank Details</h4>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <x-input
                                        name="bankname"
                                        label="Bank Name :"
                                        placeholder="Bank Name"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        />

                                </div>
                                <div class="col-6">

                                    <x-input
                                        type="text"
                                        name="accountnumber"
                                        label="Account Number :"
                                        placeholder="Account Number"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        formInputClass="only-numbers"
                                        />

                                </div>
                                <div class="col-6 mt-3">

                                    <x-input
                                        name="ifsccode"
                                        label="IFSC Code :"
                                        placeholder="IFSC Code"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        />

                                </div>
                                <div class="col-6 mt-3">

                                    <x-input
                                        type="text"
                                        name="pannumber"
                                        label="PAN Number :"
                                        placeholder="PAN Number"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
                                        />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div>
                            <h4 class="card-title">Other information</h4>
                            <hr>
                            <div class="row">
                                <div class="col-6">

                                    <x-input
                                        type="file"
                                        name="researchpublications"
                                        label="Research Publications :"
                                        placeholder="Research Publications"
                                        formLabelClass="form-label"
                                        required="true"
                                        helperSmallText="Please upload your research publications, if any"
                                        />
										<div class="research_publications"></div>

                                </div>
                                <div class="col-6">

                                    <x-input
                                        type="file"
                                        name="professionalmemberships"
                                        label="Professional Memberships :"
                                        placeholder="Professional Memberships"
                                        formLabelClass="form-label"
                                        required="true"
                                        helperSmallText="Please upload your professional memberships, if any"
                                        />

                                </div>
                                <div class="col-6 mt-3">

                                    <x-input
                                        type="file"
                                        name="recommendationdetails"
                                        label="Reference/Recommendation Details :"
                                        placeholder="Reference/Recommendation Details"
                                        formLabelClass="form-label"
                                        required="true"
                                        helperSmallText="Please upload your reference/recommendation details, if any"
                                        />

                                </div>
                                <div class="col-6 mt-3">
                                    <x-input
                                        type="date"
                                        name="joiningdate"
                                        label="Joining Date :"
                                        placeholder="Joining Date"
                                        formLabelClass="form-label"
                                        required="true"
                                        labelRequired="true"
										value="{{ $value ?? '' }}"
                                        />

                                </div>
                            </div>
                        </div>
                    </div>
                </div>


<div class="card">
     <div class="card-body">
          <div class="row">
               <div class="col-12">
                    <label for="sector" class="form-label">Current Sector :</label>
                    <div class="mb-3">
                         <div class="form-check form-check-inline">
						 <input class="form-check-input success"
						 type="radio" name="current_sector"
                         id="success-radio" value="1">

                     <label class="form-check-label"
					 for="success-radio">Government Sector</label>
                      </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input success"
					  type="radio" name="current_sector"
                       id="success2-radio" value="2" >
                     <label class="form-check-label" for="success2-radio">Private Sector</label>
                     </div>
                       </div>
                </div>

                 <div class="col-12">

                                <label for="expertise" class="form-label">Area of Expertise :</label>
                                <div class="mb-3 expertise-row">
                                    {{-- faculties --}}
                                    <x-checkbox
                                        name="faculties[]"
                                        label="Area of Expertise :"
                                        formLabelClass="form-label"
                                        :options="$faculties"
                                        required="true"
                                        />
                                </div>
                            </div>
                        </div>
                        <hr>
		<div class="d-flex justify-content-end align-items-center gap-2 mb-3">

			<!--<button onclick="printFacultyForm()" class="btn btn-success d-flex align-items-center gap-2" type="button">
				<i class="material-icons menu-icon">print</i>
				Print
			</button>-->

			<!--<button class="btn btn-primary d-flex align-items-center gap-2" type="button" id="saveFacultyForm">
				<i class="material-icons menu-icon">save</i>
				Save
			</button>-->

			<button class="btn btn-primary d-flex align-items-center gap-2" type="button" id="saveFacultyForm">
			<i class="material-icons menu-icon">save</i>
			Save
		</button>




			<a href="{{ route('faculty.index') }}" class="btn btn-secondary d-flex align-items-center gap-2">
				<i class="material-icons menu-icon">arrow_back</i>
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

</script>
<script>
$(document).ready(function () {
    // AJAX submit for Save button
    $(document).on('click', '#saveFacultyForm', function (e) {
        e.preventDefault();
        var form = $('.facultyForm')[0];
        var formData = new FormData(form);

        // Validate required fields before submit
        var requiredFields = [
            'facultytype', 'firstName', 'middlename', 'lastname', 'fullname', 'gender', 'landline', 'mobile',
            'country', 'state', 'district', 'city', 'email', 'residence_address', 'permanent_address', 'bankname',
            'accountnumber', 'ifsccode', 'pannumber', 'joiningdate', 'current_sector'
        ];
        var missing = [];
        requiredFields.forEach(function(field) {
            if (!formData.get(field) || formData.get(field) === 'undefined') {
                missing.push(field);
            }
        });
        if (missing.length > 0) {
            alert('Please fill all required fields: ' + missing.join(', '));
            return;
        }

        $.ajax({
            url: "{{ route('faculty.store') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status) {
                    window.location.href = "{{ route('faculty.index') }}";
                } else {
                    alert(response.message || 'Error saving faculty.');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    });

    // Check Email
    $('input[name="email"]').on('blur', function () {
        let email = $(this).val();
        if (email) {
            checkUnique('email', email, $(this));
        }
    });

    // Check Mobile
    $('input[name="mobile"]').on('blur', function () {
        let mobile = $(this).val();
        if (mobile) {
            checkUnique('mobile', mobile, $(this));
        }
    });

    function checkUnique(type, value, inputElement) {
        $.ajax({
            url: "{{ route('faculty.checkUnique') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                type: type,
                value: value
            },
            success: function (response) {
                inputElement.next('.unique-error').remove(); // remove old messages
                if (response.exists) {
                    inputElement.after('<small class="text-danger unique-error">' + response.message + '</small>');
                    inputElement.addClass('is-invalid');
                } else {
                    inputElement.after('<small class="text-success unique-error">' + response.message + '</small>');
                    inputElement.removeClass('is-invalid');
                }
            }
        });
    }
});
$(document).ready(function () {

    let $input = $('#firstName');
    let $suggestionBox = $('#suggestionList');

	  $('#suggestionList').hide();



    // =============================
    // 1) Live Search Full Name
    // =============================
    $input.on('input', function () {
        let query = $(this).val().trim();
        // Always hide 'No results found' if any value is present
        if (query.length > 0) {
            $suggestionBox.hide();
        }
    });

    $input.on('keyup', function () {
        let query = $(this).val().trim();
        $suggestionBox.find('.list-group-item.disabled').remove();
        if (query.length > 1) {
            $.ajax({
                url: "{{ route('faculty.checkFullName') }}",
                type: "GET",
                data: { query: query },
                success: function (response) {
                    let html = "";
                    if (response.suggestions.length > 0) {
                        response.suggestions.forEach(function (item) {
                            html += `<a href="#" class="list-group-item list-group-item-action suggestion-item"
                                      data-id="${item.id}"
                                      data-fullname="${item.full_name}">
                                        ${item.full_name}
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
                                if ($suggestionBox.find('.list-group-item.disabled').length > 0) {
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

  function fillFacultyForm_working(faculty) {

    // Auto-fill all input fields
    $('.facultyForm input').each(function () {
        let fieldName = $(this).attr('name');

        if (faculty[fieldName] !== undefined) {
            $(this).val(faculty[fieldName]);
        }
    });

    // Auto-fill all select fields
    $('.facultyForm select').each(function () {
        let fieldName = $(this).attr('name');

        if (faculty[fieldName] !== undefined) {
            $(this).val(faculty[fieldName]).trigger('change');
        }
    });

    // Auto-fill all textarea fields
    $('.facultyForm textarea').each(function () {
        let fieldName = $(this).attr('name');

        if (faculty[fieldName] !== undefined) {
            $(this).val(faculty[fieldName]);
        }
		});

		$('.facultyForm input').each(function () {
		let fieldName = $(this).attr('name');

		if (faculty[fieldName] !== undefined) {
			$(this).val(faculty[fieldName]);
		}
		});

		$('.facultyForm input').each(function () {
		let fieldName = $(this).attr('name');
		if (faculty[fieldName] !== undefined) {
        $(this).val(faculty[fieldName]);
		}
	});


}

function fillFacultyForm(faculty) {

    // Loop through all keys returned from server
   /* Object.keys(faculty).forEach(function (key) {

        // Select input/select/textarea using ID
        let field = $('.facultyForm #' + key);

        // If field exists, fill value
        if (field.length > 0) {
            field.val(faculty[key]).trigger("change");
        }
    });*/



			//Personal Information

			// Auto-fill name

            $("input[name='firstName']").val(faculty.first_name ?? "");
            $("input[name='middlename']").val(faculty.middle_name ?? "");
            $("input[name='lastname']").val(faculty.last_name ?? "");
            $("input[name='fullname']").val(
                faculty.first_name + " " + (faculty.middle_name ?? "") + " " + faculty.last_name
            );

            // Auto-fill remaining fields
           $("select[name='facultytype']").val(faculty.faculty_type);
           $("input[name='landline']").val(faculty.landline_no);
           $("input[name='mobile']").val(faculty.mobile_no);
           $("input[name='email']").val(faculty.email_id);
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
        <a href="${photoURL}" target="_blank" class="text-primary">
            View Existing Photo
        </a>
    `);
	}

//Qualification Details

    // Clear existing dynamic rows except the first template row
    $(".degree-row:gt(0)").remove();
    $(".experience-row:gt(0)").remove();

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
                <a href="storage/${q.Certifcates_upload_path}" target="_blank" class="text-primary">
                    View Existing Certificate
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

	if (faculty.joining_date) {
    let formattedDate = new Date(faculty.joining_date).toISOString().slice(0, 10);
    $("input[name='joiningdate']").val(formattedDate);
	}

	faculty.faculty_expertise_map.forEach(item => {
    $('input[name="faculties[]"][value="' + item.faculty_expertise_pk + '"]').prop("checked", true);
});


 $('input[name="current_sector"][value="' + faculty.faculty_sector + '"]').prop("checked", true);

 //document.querySelector('input[name="current_sector"]:checked').value;
 //document.querySelectorAll('input[name="faculties[]"]:checked');


	$("#faculty_id").val(faculty.id);

	}

    // =========================================
    // 3) When User Clicks a Suggested Full Name
    // =========================================
    $(document).on('click', '.suggestion-item', function (e) {
    e.preventDefault();

    let id = $(this).data('id');
    let fullname = $(this).data('fullname');

    $('#firstName').val(fullname);
    $('#suggestionList').hide();

    // GET full faculty details
    $.ajax({
        url: "/faculty/details/" + id,
        type: "GET",
        success: function (faculty) {
			fillFacultyForm(faculty);  // <--- AUTO FILL FULL FORM

        }
    });
});




});




</script>

@endsection
