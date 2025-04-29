@extends('admin.layouts.master')

@section('title', 'Faculty')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Faculty" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Create Faculty</h4>
            <hr>
            <form>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            @php 
                                $facultyTypeList = [
                                    '1' => 'Internal',
                                    '2' => 'Guest',
                                    '3' => 'Research',
                                ];
                            @endphp
                            <x-select 
                                name="facultytype" 
                                label="Faculty Type :" 
                                formLabelClass="form-label"
                                :options="$facultyTypeList"
                                required="true"
                                />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input 
                                name="firstName" 
                                label="First Name :" 
                                placeholder="First Name" 
                                formLabelClass="form-label"
                                required="true"
                                />
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
                                />

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">

                            <x-select
                                name="state"
                                label="State :"
                                placeholder="State"
                                formLabelClass="form-label"
                                :options="$state"
                                required="true"
                                />

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">

                            <x-select
                                name="district"
                                label="District :"
                                placeholder="District"
                                formLabelClass="form-label"
                                :options="$district"
                                required="true"
                                />

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">

                            <x-select 
                                name="city"
                                label="City :"
                                placeholder="City"
                                formLabelClass="form-label"
                                :options="$city"
                                required="true"
                                />

                        </div>
                    </div>
                    <div class="col-md-6">

                        <x-input
                            name="email"
                            label="Email :"
                            placeholder="Email :"
                            formLabelClass="form-label"
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
                            label="Photo upload :"
                            placeholder="Photo upload :"
                            formLabelClass="form-label"
                            />
                        
                    </div>
                    <div class="col-md-6 mt-3">

                        <x-input 
                            type="file"
                            name="document"
                            label="Document upload :"
                            placeholder="Document upload :"
                            formLabelClass="form-label"
                            />

                    </div>
                </div>
                <div>
                    <h4 class="card-subtitle mb-3 mt-3">Qualification Details</h4>
                    <hr>
                    <div id="education_fields" class="my-4"></div>
                    <div class="row" id="education_fields">
                        <div class="col-3">

                            <x-input
                                name="degree[]"
                                label="Degree :"
                                placeholder="Degree"
                                formLabelClass="form-label"
                                required="true"
                                helperSmallText="Bachelors, Masters, PhD"
                                />

                        </div>
                        <div class="col-3">
                            <x-input
                                name="university_institution_name[]"
                                label="University/Institution Name :"
                                placeholder="University/Institution Name"
                                formLabelClass="form-label"
                                required="true"
                                />
                        </div>
                        <div class="col-3">
                            <x-input
                                type="number"
                                name="year_of_passing[]"
                                label="Year of Passing :"
                                placeholder="Year of Passing"
                                formLabelClass="form-label"
                                min="1900"
                                max="{{ date('Y') }}"
                                step="1"
                                required="true"
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
                                />

                            
                        </div>
                        <div class="col-9">
                            <label for="Schoolname" class="form-label"></label>
                            <div class="mb-3 float-end">
                                <button onclick="education_fields();" class="btn btn-success fw-medium" type="button">
                                    <i class="material-icons menu-icon">add</i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <h4 class="card-subtitle mb-3 mt-3">Experience Details</h4>
                    <hr>
                    <div id="experience_fields" class="my-4"></div>
                    <div class="row" id="experience_fields">
                        <div class="col-3">
                            <x-input
                                name="experience[]"
                                label="Years of Experience :"
                                placeholder="Years of Experience"
                                formLabelClass="form-label"
                                required="true"
                                />
                        </div>
                        <div class="col-3">

                            <x-input
                                name="specialization[]"
                                label="Area of Specialization :"
                                placeholder="Area of Specialization"
                                formLabelClass="form-label"
                                required="true"
                                />

                        </div>
                        <div class="col-3">
                            <x-input
                                name="institution[]"
                                label="Previous Institutions :"
                                placeholder="Previous Institutions"
                                formLabelClass="form-label"
                                required="true"
                                />
                        </div>
                        <div class="col-3">
                            <x-input 
                                name="position[]"
                                label="Position Held :"
                                placeholder="Position Held"
                                formLabelClass="form-label"
                                required="true"
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
                                />
                        </div>
                        <div class="col-3 mt-3">
                            <x-input
                                name="work[]"
                                label="Nature of Work :"
                                placeholder="Nature of Work"
                                formLabelClass="form-label"
                                required="true"
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
                <div>
                    <h4 class="card-subtitle mb-3 mt-3">Bank Details</h4>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <x-input 
                                name="bankname"
                                label="Bank Name :"
                                placeholder="Bank Name"
                                formLabelClass="form-label"
                                required="true"
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
                                />
                            
                        </div>
                        <div class="col-6 mt-3">

                            <x-input
                                name="ifsccode"
                                label="IFSC Code :"
                                placeholder="IFSC Code"
                                formLabelClass="form-label"
                                required="true"
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
                                />
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="card-subtitle mb-3 mt-3">Other information</h4>
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
                                />
                            
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <label for="sector" class="form-label">Current Sector :</label>
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input success" type="radio" name="radio-solid-success"
                                    id="success-radio" value="option1">
                                <label class="form-check-label" for="success-radio">Goverment Sector</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input success" type="radio" name="radio-solid-success"
                                    id="success2-radio" value="option1" checked="">
                                <label class="form-check-label" for="success2-radio">Private Sector</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">

                        <label for="expertise" class="form-label">Area of Expertise :</label>
                        <div class="mb-3">
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
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="button" id="saveFacultyForm">
                        <i class="material-icons menu-icon">save</i>
                        Save
                    </button>
                    <a href="{{ route('faculty.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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
    
    document.addEventListener('DOMContentLoaded', function() {
        
        $('#saveFacultyForm').click(function (e) {
            
            const formData = new FormData();
            // remove all error class
            $('input').removeClass('is-invalid');
            $('span.text-danger').remove();
            
            let facultyType = $('select[name="facultytype"]').val();
            let firstName = $('input[name="firstName"]').val();
            let middleName = $('input[name="middlename"]').val();
            let lastName = $('input[name="lastname"]').val();
            let fullName = $('input[name="fullname"]').val();
            let gender = $('select[name="gender"]').val();
            let landline = $('input[name="landline"]').val();
            let mobile = $('input[name="mobile"]').val();
            let country = $('select[name="country"]').val();
            let state = $('select[name="state"]').val();
            let district = $('select[name="district"]').val();
            let city = $('select[name="city"]').val();
            let email = $('input[name="email"]').val();
            let alternativeEmail = $('input[name="alternativeEmail"]').val();
            let photo = $('input[name="photo"]').val();
            let document = $('input[name="document"]').val();

            formData.append('facultyType', facultyType);
            formData.append('firstName', firstName);
            formData.append('middlename', middleName);
            formData.append('lastname', lastName);
            formData.append('fullname', fullName);
            formData.append('gender', gender);
            formData.append('landline', landline);
            formData.append('mobile', mobile);
            formData.append('country', country);
            formData.append('state', state);
            formData.append('district', district);
            formData.append('city', city);
            formData.append('email', email);
            formData.append('alternativeEmail', alternativeEmail);

            // photo is file
            if ($('input[name="photo"]')[0].files.length > 0) {
                photo = $('input[name="photo"]')[0].files[0];
                formData.append('photo', photo);
            }
            // document is file
            if ($('input[name="document"]')[0].files.length > 0) {
                document = $('input[name="document"]')[0].files[0];
                formData.append('document', document);
            }

            // Qualification Details
            let degrees = [];
            let universities = [];
            let years = [];
            let percentages = [];
            let certFiles = [];

            // Collect all education fields
            $('input[name="degree[]"]').each(function(index) {
                degrees.push($(this).val());
                universities.push($('input[name="university_institution_name[]"]').eq(index).val());
                years.push($('input[name="year_of_passing[]"]').eq(index).val());
                percentages.push($('input[name="percentage_CGPA[]"]').eq(index).val());
                
                // Handle certificate files
                let certInput = $('input[name="certificate[]"]')[index];
                if (certInput.files.length > 0) {
                    certFiles.push(certInput.files[0]);
                } else {
                    certFiles.push(null);
                }
            });

            // Append all education data to formData
            degrees.forEach((degree, index) => {
                formData.append('degree[]', degree);
                formData.append('university_institution_name[]', universities[index]);
                formData.append('year_of_passing[]', years[index]);
                formData.append('percentage_CGPA[]', percentages[index]);
                
                if (certFiles[index]) {
                    formData.append('certificate[]', certFiles[index]);
                }
            });

            // Collecting the Experience Details
            let experience = [];
            let specialization = [];
            let institution = [];
            let position = [];
            let duration = [];
            let work = [];

            // Collect all experience fields
            $('input[name="experience[]"]').each(function(index) {
                experience.push($(this).val());
                specialization.push($('input[name="specialization[]"]').eq(index).val());
                institution.push($('input[name="institution[]"]').eq(index).val());
                position.push($('input[name="position[]"]').eq(index).val());
                duration.push($('input[name="duration[]"]').eq(index).val());
                work.push($('input[name="work[]"]').eq(index).val());
            });

            // Experience Details
            if (experience.length > 0) {
                experience.forEach((exp, index) => {
                    formData.append('experience[]', exp);
                    formData.append('specialization[]', specialization[index]);
                    formData.append('institution[]', institution[index]);
                    formData.append('position[]', position[index]);
                    formData.append('duration[]', duration[index]);
                    formData.append('work[]', work[index]);
                });
            }

            // Bank Details
            let bankName = $('input[name="bankname"]').val();
            let accountNumber = $('input[name="accountnumber"]').val();
            let ifscCode = $('input[name="ifsccode"]').val();
            let panNumber = $('input[name="pannumber"]').val();

            formData.append('bankname', bankName);
            formData.append('accountnumber', accountNumber);
            formData.append('ifsccode', ifscCode);
            formData.append('pannumber', panNumber);

            // Other information
            let researchPublications = $('input[name="researchpublications"]').val();
            let professionalMemberships = $('input[name="professionalmemberships"]').val();
            let recommendationDetails = $('input[name="recommendationdetails"]').val();
            let joiningDate = $('input[name="joiningdate"]').val();

            // researchPublications is file
            if ($('input[name="researchpublications"]')[0] && $('input[name="researchpublications"]')[0].files.length > 0) {
                researchPublications = $('input[name="researchpublications"]')[0].files[0];
                formData.append('researchpublications', researchPublications);
            }

            // professionalMemberships is file
            if ($('input[name="professionalmemberships"]')[0] && $('input[name="professionalmemberships"]')[0].files.length > 0) {
                professionalMemberships = $('input[name="professionalmemberships"]')[0].files[0];
                formData.append('professionalmemberships', professionalMemberships);
            }

            // recommendationDetails is file
            if ($('input[name="recommendationdetails"]')[0] && $('input[name="recommendationdetails"]')[0].files.length > 0) {
                recommendationDetails = $('input[name="recommendationdetails"]')[0].files[0];
                formData.append('recommendationdetails', recommendationDetails);
            }

            // faculties
            let faculties = [];
            $('input[name="faculties[]"]:checked').each(function() {
                faculties.push($(this).val());
            });
            faculties.forEach((faculty) => {
                formData.append('faculties[]', faculty);
            });
            // append csrf token
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('joiningdate', joiningDate);
                        

            $.ajax({
                type: 'POST',
                url: "{{ route('faculty.store') }}",
                data: formData,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    showLoader();
                },
                success: function (response) {
                    console.log('Success:', response);
                    // Handle success response
                    if (response.status == 200) {
                        window.location.href = "{{ route('faculty.index') }}";
                    } else {
                        alert(response.message);
                    }
                },
                error: function (error) {
                    console.log('Error:', error);
                    if (error.status == 422) {
                        let errors = error.responseJSON.errors;
                        
                        for (let key in errors) {
                            // Handle array fields (e.g., degree.0, university_institution_name.1)
                            if (key.includes('.')) {
                                const [fieldName, index] = key.split('.');
                                const inputField = $(`[name="${fieldName}[]"]`).eq(index);
                                
                                if (inputField.length > 0) {
                                    const errorDiv = $('<span class="text-danger mt-1"></span><br/>').text(errors[key][0]);
                                    inputField.addClass('is-invalid').after(errorDiv);
                                }
                            } 
                            // Handle regular fields
                            else {
                                const inputField = $(`[name="${key}"], select[name="${key}"]`);
                                if (inputField.length > 0) {
                                    const errorDiv = $('<span class="text-danger mt-1"></span><br/>').text(errors[key][0]);
                                    inputField.addClass('is-invalid').after(errorDiv);
                                }
                            }
                        }
                    } else {
                        alert('Something went wrong. Please try again.');
                    }
                },
                complete: function () {
                    hideLoader();
                }
            });
        })
    });
</script>
@endsection