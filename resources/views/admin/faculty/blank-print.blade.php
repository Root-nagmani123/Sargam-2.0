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
    body {
        background: #fff !important;
        -webkit-print-color-adjust: exact !important;
    }

    .btn, nav, header, footer, .navbar, .sidebar {
        display: none !important;
    }

    .card {
        box-shadow: none !important;
        page-break-inside: avoid;
    }
     input, select, textarea {
        border: 1px solid #000 !important;
        padding: 4px !important;
        border-radius: 4px !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* If using Bootstrap form-control */
    .form-control {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
}


.photo-box {
    width: 200px;            /* set your size */
    height: 200px;           /* same as width → perfect square */
    border: 2px dashed #999; /* border style */
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 6px;
    background-color: #f8f8f8;
    font-size: 18px;
    color: #666;
}

#experience_fields {
    margin-bottom: 25px;
}
.input-underline {
    border: none !important;
    border-bottom: 1px solid #000 !important;
    border-radius: 0 !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
}

.input-underline:focus {
    border-bottom: 1px solid #000 !important;
    box-shadow: none !important;
}

// print functionality
</style>
<div class="container-fluid">
    <x-breadcrum title="Faculty" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    {{-- <div class="card" id="facultyForm" data-store-url="{{ route('faculty.store') }}" data-index-url="{{ route('faculty.index') }}">
        <div class="card-body"> --}}

            <form id="printFacultyBlankForm" class="printFacultyBlankForm">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Personal Information</h4>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
								<x-input
									name="facultytype"
									label="Faculty Type :"
									placeholder=""
									formLabelClass="form-label"
									required="true"
									labelRequired="true"
									formInputClass="only-letters"
									id="facultytype"
								/>
                                </div>
                            </div>

							<div class="col-md-6">
							<div class="mb-3">
								<x-input
									name="firstName"
									label="First Name :"
									placeholder=""
									formLabelClass="form-label"
									required="true"
									labelRequired="true"
									formInputClass="only-letters "
									id="firstName"
								/>
								<div id="suggestionList" class="list-group position-absolute" style="z-index: 1000; width:100%; display:none;"></div>
							</div>
						</div>


                            <div class="col-md-6">
                                <div class="mb-3">

                                    <x-input
                                        name="middlename"
                                        label="Middle Name :"
                                        placeholder=""
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
                                        placeholder=""
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

						<label class="form-label d-block">Gender : <span class="text-danger">*</span></label>

						@foreach ($genderList as $value => $label)
							<div class="form-check form-check-inline">
								<input
									class="form-check-input"
									type="checkbox"
									name="gender[]"
									id="gender_{{ $value }}"
									value="{{ $value }}"
									{{ (is_array(old('gender')) && in_array($value, old('gender'))) ? 'checked' : '' }}
								>
								<label class="form-check-label" for="gender_{{ $value }}">
									{{ $label }}
								</label>
							</div>
						@endforeach
					</div>
				</div>

                            <div class="col-md-6">
                                <div class="mb-3">

                                    <x-input
                                        type="text"
                                        name="landline"
                                        label="Landline Number"
                                        placeholder=""
                                        formLabelClass="form-label"

                                        />

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <x-input
                                        type="text"
                                        name="mobile"
                                        label="Mobile Number :"
                                        placeholder=""
										labelRequired="true"
                                        formLabelClass="form-label"

                                        />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">

										 <x-input
                                        name="country"
                                        label="Country :"
                                        placeholder=""
                                        formLabelClass="form-label"
                                        labelRequired="true"
                                         />
										 </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">

                                    <x-input
                                        name="state"
                                        label="State :"
                                        placeholder=""
                                        formLabelClass="form-label"
										labelRequired="true"
                                        />

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <x-input
                                        name="district"
                                        label="District :"
                                        placeholder=""
                                        formLabelClass="form-label"
                                        />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">

                                    <x-input
                                        name="city"
                                        label="City :"
                                        placeholder=""
                                        formLabelClass="form-label"
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
                                    placeholder=""
                                    formLabelClass="form-label"
                                    />
                                </div>

                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                <x-input
                                    name="permanent_address"
                                    label="Permanent Address :"
                                    placeholder=""
                                    formLabelClass="form-label"
                                    />
                                </div>

                            </div>
                            <div class="col-md-6">

                                <x-input
                                    name="email"
                                    label="Email :"
                                    placeholder=""
                                    formLabelClass="form-label"
                                    labelRequired="true"
                                    />

                            </div>
                            <div class="col-md-6">
                                <x-input
                                    name="alternativeEmail"
                                    label="Alternate Email :"
                                    placeholder=""
                                    formLabelClass="form-label"
                                    />

                            </div>
                            <div class="col-md-6 mt-3">
                               <div class="photo-box">
								<span>Photo</span>
							</div>

                                <!-- Preview Container -->
                                <div class="mt-2">
                                    <img id="photoPreview" src="#" alt="Photo Preview" class="img-thumbnail d-none" style="max-width: 200px;">
                                </div>
                            </div>

                            <div class="col-md-6 mt-3">

                                <x-input
                                    type="text"
                                    name="document"
                                    label="Documents :"
                                    placeholder=""
                                    formLabelClass="form-label"
                                    labelRequired="true"
                                    helperSmallText=""
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

							<div class="row fw-bold mb-2">
								<div class="col">Degree</div>
								<div class="col">University/Institution Name</div>
								<div class="col">Year of Passing</div>
								<div class="col">Percentage/CGPA</div>
								<div class="col">Certificates/Documents</div>
							</div>

							<div class="row g-2 mb-2">
    <div class="col">
        <x-input name="degree[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="university_institution_name[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="year_of_passing[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="percentage_CGPA[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="certificate[]" label="" placeholder=""  />
    </div>
</div>

<div class="row g-2 mb-2">
    <div class="col">
        <x-input name="degree[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="university_institution_name[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="year_of_passing[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="percentage_CGPA[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="certificate[]" label="" placeholder=""  />
    </div>
</div>

<div class="row g-2 mb-2">
    <div class="col">
        <x-input name="degree[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="university_institution_name[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="year_of_passing[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="percentage_CGPA[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="certificate[]" label="" placeholder=""  />
    </div>
</div>

<div class="row g-2 mb-2">
    <div class="col">
        <x-input name="degree[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="university_institution_name[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="year_of_passing[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="percentage_CGPA[]" label="" placeholder="" />
    </div>

    <div class="col">
        <x-input name="certificate[]" label="" placeholder=""  />
    </div>
</div>





                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Experience Details</h4>
                            <hr>
                            <div id="experience_fields" class="my-4"></div>
                         <!-- Experience Block 1 -->
<div class="row mb-4" id="experience_fields_1">

    <div class="col-md-4">
        <x-input name="experience[]" label="Years of Experience :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4">
        <x-input name="specialization[]" label="Area of Specialization :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4">
        <x-input name="institution[]" label="Previous Institutions :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input name="position[]" label="Position Held :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input type="number" name="duration[]" label="Duration :" formLabelClass="form-label" min="0" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input name="work[]" label="Nature of Work :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

</div>



<!-- Experience Block 2 -->
<div class="row mb-4" id="experience_fields_2">

    <div class="col-md-4">
        <x-input name="experience[]" label="Years of Experience :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4">
        <x-input name="specialization[]" label="Area of Specialization :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4">
        <x-input name="institution[]" label="Previous Institutions :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input name="position[]" label="Position Held :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input type="number" name="duration[]" label="Duration :" formLabelClass="form-label" min="0" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input name="work[]" label="Nature of Work :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

</div>



<!-- Experience Block 3 -->
<div class="row mb-4" id="experience_fields_3">

    <div class="col-md-4">
        <x-input name="experience[]" label="Years of Experience :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4">
        <x-input name="specialization[]" label="Area of Specialization :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4">
        <x-input name="institution[]" label="Previous Institutions :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input name="position[]" label="Position Held :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input type="number" name="duration[]" label="Duration :" formLabelClass="form-label" min="0" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input name="work[]" label="Nature of Work :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

</div>



<!-- Experience Block 4 -->
<div class="row mb-4" id="experience_fields_4">

    <div class="col-md-4">
        <x-input name="experience[]" label="Years of Experience :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4">
        <x-input name="specialization[]" label="Area of Specialization :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4">
        <x-input name="institution[]" label="Previous Institutions :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input name="position[]" label="Position Held :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input type="number" name="duration[]" label="Duration :" formLabelClass="form-label" min="0" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input name="work[]" label="Nature of Work :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

</div>



<!-- Experience Block 5 -->
<div class="row mb-4" id="experience_fields_5">

    <div class="col-md-4">
        <x-input name="experience[]" label="Years of Experience :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4">
        <x-input name="specialization[]" label="Area of Specialization :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4">
        <x-input name="institution[]" label="Previous Institutions :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input name="position[]" label="Position Held :" formLabelClass="form-label" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input type="number" name="duration[]" label="Duration :" formLabelClass="form-label" min="0" required="true" labelRequired="true"/>
    </div>

    <div class="col-md-4 mt-3">
        <x-input name="work[]" label="Nature of Work :" formLabelClass="form-label" required="true" labelRequired="true"/>
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
                                        placeholder=""
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
                                        placeholder=""
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
                                        placeholder=""
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
                                        placeholder=""
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

                                        name="researchpublications"
                                        label="Research Publications :"
                                        placeholder=""
                                        formLabelClass="form-label"
                                        required="true"
                                        helperSmallText="Please fill your research publications, if any"
                                        />

                                </div>
                                <div class="col-6">

                                    <x-input

                                        name="professionalmemberships"
                                        label="Professional Memberships :"
                                        placeholder=""
                                        formLabelClass="form-label"
                                        required="true"
                                        helperSmallText="Please fill your professional memberships, if any"
                                        />

                                </div>
                                <div class="col-6 mt-3">

                                    <x-input

                                        name="recommendationdetails"
                                        label="Reference/Recommendation Details :"
                                        placeholder=""
                                        formLabelClass="form-label"
                                        required="true"
                                        helperSmallText="Please fill your reference/recommendation details, if any"
                                        />

                                </div>
                                <div class="col-6 mt-3">
                                    <x-input

                                        name="joiningdate"
                                        label="Joining Date :"
                                        placeholder=""
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
                        <div class="row">
                            <div class="col-12">
                                <label for="sector" class="form-label">Current Sector :</label>
                                <div class="mb-3">
					<div class="form-check form-check-inline">
							<input class="form-check-input success" type="checkbox" name="current_sector[]"
								   id="success-checkbox" value="1">
							<label class="form-check-label" for="success-checkbox">Government Sector</label>
						</div>

						<div class="form-check form-check-inline">
							<input class="form-check-input success" type="checkbox" name="current_sector[]"
								   id="success2-checkbox" value="2">
							<label class="form-check-label" for="success2-checkbox">Private Sector</label>
						</div>
					</div>

                            </div>
                            <div class="col-12">

                                <!--<label for="expertise" class="form-label">Area of Expertise :</label>-->
                                <div class="mb-3">
                                    {{-- faculties --}}
                                    	<x-input
                                        name="expertise"
                                        label="Area of Expertise :"
                                        placeholder=""
                                        formLabelClass="form-label"
                                        required="true"

                                        />
                                </div>
                            </div>
                        </div>
                        <hr>
		<div class="d-flex justify-content-end align-items-center gap-2 mb-3">

		<button onclick="printFacultyBlankForm()" class="btn btn-success d-flex align-items-center gap-2" type="button">
            <!--<i class="material-icons menu-icon">print</i>-->
				Print
			</button>

			<a href="{{ route('faculty.index') }}" class="btn btn-secondary d-flex align-items-center gap-2">
				<!--<i class="material-icons menu-icon">arrow_back</i>-->
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
/*window.onload = function() {
    window.print();
};*/


function printFacultyBlankForm() {

    const printSource = document.querySelector('.printFacultyBlankForm');
    if (!printSource) {
        console.error("Print form not found!");
        return;
    }

    const printArea = printSource.cloneNode(true);
    const printWindow = window.open('', '', 'width=1200');
    const headHTML = document.querySelector('head').innerHTML;

    printWindow.document.write(`
        <html>
            <head>
                ${headHTML}
                <style>
                    body {
                        background: #fff !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    .btn, .no-print, nav, header, footer, .navbar, .sidebar {
                        display: none !important;
                    }
                    .card {
                        page-break-inside: avoid;
                        box-shadow: none !important;
                    }
                </style>
            </head>
            <body>
                ${printArea.outerHTML}
            </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.onload = function () {
        printWindow.print();
        printWindow.close();
    };
}


</script>

@endsection
