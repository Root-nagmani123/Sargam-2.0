@extends('admin.layouts.master')

@section('title', 'Faculty')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Faculty" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    {{-- <div class="card" id="facultyForm" data-store-url="{{ route('faculty.store') }}" data-index-url="{{ route('faculty.index') }}">
        <div class="card-body"> --}}
            
            <form>
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
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div>
                            <h4 class="card-title">Qualification Details</h4>
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
                            
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Experience Details</h4>
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
                    </div>
                </div>
                
                
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <label for="sector" class="form-label">Current Sector :</label>
                                <div class="mb-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input success" type="radio" name="current_sector"
                                            id="success-radio" value="1">
                                        <label class="form-check-label" for="success-radio">Government Sector</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input success" type="radio" name="current_sector"
                                            id="success2-radio" value="2" checked>
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
@endsection