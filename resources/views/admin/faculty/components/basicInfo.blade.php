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

                value="{{ $faculty->faculty_type }}"
                />
        </div>
    </div>
    <div class="col-md-6 {{ $faculty->faculty_type != '1' ? 'd-none' : '' }}" id="facultyPaContainer">
        <div class="mb-3">
            <x-input
                name="faculty_pa"
                label="Faculty (PA) :"
                placeholder="Faculty (PA)"
                formLabelClass="form-label"
                value="{{ $faculty->faculty_pa }}"
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

                formInputClass="only-letters"
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

                value="{{ $faculty->middle_name }}"
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

                value="{{ $faculty->last_name }}"
                formInputClass="only-letters"
                />

        </div>
    </div>
    <!--<div class="col-md-6">
        <div class="mb-3">

            <x-input
                name="fullname"
                label="Full Name :"
                placeholder="Full Name"
                formLabelClass="form-label"

                value="{{ $faculty->full_name }}"
                />

        </div>
    </div>-->
            <div class="col-md-3">
                <div class="mb-3">
                    <x-input
                        name="fullname"
                        label="Full Name :"
                        placeholder="Full Name"
                        formLabelClass="form-label"
                        value="{{ $faculty->full_name }}"

                        />

                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <x-input
                        name="faculty_code"
                        label="Faculty Code :"
                        placeholder="Faculty Code"
                        formLabelClass="form-label"
                        class="bg-light"
                        value="{{ $faculty->faculty_code }}"
                        readonly
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

                value="{{ $faculty->landline_no }}"
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

                value="{{ $faculty->mobile_no }}"
                formInputClass="only-numbers"
                />
        </div>
    </div>
    <div class="col-md-6">
	<div class="mb-3">
		<x-input
			name="current_designation"
			label="Current Designation :"
			placeholder="Current Designation"
			formLabelClass="form-label"
			 value="{{ $faculty->current_designation }}"
		/>
	</div>
	</div>
    <div class="col-md-6">
	<div class="mb-3">
		<x-input
			name="current_department"
			label="Current Department :"
			placeholder="Current Department"
			formLabelClass="form-label"
			 value="{{ $faculty->current_department}}"
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

                value="{{ $faculty->city_master_pk }}"
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
                                    value="{{ $faculty->Residence_address }}"
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
                                    value="{{ $faculty->Permanent_Address }}"
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
            label="Photo Upload:"
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
