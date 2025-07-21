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
                value="{{ $faculty->faculty_type }}"
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
                value="{{ $faculty->first_name }}"
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
                value="{{ $faculty->middle_name }}"
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
                value="{{ $faculty->last_name }}"
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
                value="{{ $faculty->full_name }}"
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
                value="{{ $faculty->gender }}"
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
                value="{{ $faculty->landline_no }}"
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
                value="{{ $faculty->mobile_no }}"
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
                value="{{ $faculty->country_master_pk }}"
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
                value="{{ $faculty->state_master_pk }}"
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
                value="{{ $faculty->state_district_mapping_pk }}"
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
                value="{{ $faculty->city_master_pk }}"
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

        <x-input
            name="email"
            label="Email :"
            placeholder="Email :"
            formLabelClass="form-label"
            value="{{ $faculty->email_id }}"
            />  
        
    </div>
    <div class="col-md-6">
        <x-input
            name="alternativeEmail"
            label="Alternate Email :"
            placeholder="Alternate Email :"
            formLabelClass="form-label"
            value="{{ $faculty->alternate_email_id }}"
            />
        
    </div>
    <div class="col-md-6 mt-3">

        
        
        <x-input 
            type="file"
            name="photo"
            label="Photo upload :"
            placeholder="Photo upload :"
            formLabelClass="form-label"
            labelRequired="true"
            helperSmallText="Please upload a recent passport-sized photo"
            />
        
            @if(!empty($faculty->photo_uplode_path))
            <br/>
            <span class="text-info text-bold">Previously Uploaded Photo</span>
            <a href="{{ asset('storage/'.$faculty->photo_uplode_path) }}" target="_blank" class="rounded-circle" title="View Photo">
                <iconify-icon icon="lets-icons:eye" width="24" height="24"></iconify-icon>
            </a>
        @endif
        <div class="mt-2">
            <img id="photoPreview" src="#" alt="Photo Preview" class="img-thumbnail d-none" style="max-width: 200px;">
        </div>
    </div>
    <div class="col-md-6 mt-3">

        

        <x-input 
            type="file"
            name="document"
            label="Document upload :"
            placeholder="Document upload :"
            formLabelClass="form-label"
            helperSmallText="CV or any other supporting document"
            />

            @if(!empty($faculty->Doc_uplode_path))
            <br/>
            <span class="text-info text-bold">Previously Uploaded Document</span>
            <a href="{{ asset('storage/'.$faculty->Doc_uplode_path) }}" target="_blank" class="rounded-circle" title="View Document">
                <iconify-icon icon="lets-icons:eye" width="24" height="24"></iconify-icon>
            </a>
        @endif

    </div>
</div>