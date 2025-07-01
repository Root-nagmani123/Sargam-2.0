<p>Current Address</p>
<hr>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <x-input 
            name="address" 
            label="Address :" 
            formLabelClass="form-label" 
            formInputClass="form-control" 
            value="{{ $member->current_address ?? '' }}"
            labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $countryOptions = App\Models\Country::getCountryList();
            @endphp
            <x-select name="country" label="Country :" formLabelClass="form-label" formSelectClass="form-select" :options="$countryOptions ?? []" :value="$member->country_master_pk" labelRequired="true" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $stateOptions = App\Models\State::getStateList();
            @endphp
            <x-select name="state" label="State :" formLabelClass="form-label" formSelectClass="form-select" :options="$stateOptions ?? []" :value="$member->state_master_pk" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $districtOptions = App\Models\District::getDistrictList();
            @endphp
            <x-select name="district" label="District :" formLabelClass="form-label" formSelectClass="form-select" :options="$districtOptions ?? []" :value="$member->state_district_mapping_pk" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $cityOptions = App\Models\City::getCityList();
            @endphp
            <x-select name="city" label="City :" formLabelClass="form-label" formSelectClass="form-select" :options="$cityOptions ?? []" :value="$member->city ?? ''" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="postal" label="Postal Code :" formLabelClass="form-label" formInputClass="form-control" value="{{ $member->zipcode ?? '' }}" labelRequired="true" />
        </div>
    </div>
</div>
<fieldset>
    <div class="form-check py-2">
        <input type="checkbox" name="styled_max_checkbox" data-validation-maxchecked-maxchecked="2"
            data-validation-maxchecked-message="Don't be greedy!" required=""
            class="form-check-input" id="customCheck4" aria-invalid="false">
        <label class="form-check-label" for="customCheck4">Current & Permanent Address both are same</label>
    </div>
</fieldset>
<p>Permanent Address</p>
<hr>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="permanentaddress">Address : <span class="text-danger">*</span></label>
            <input type="text" name="permanentaddress" id="permanentaddress" class="form-control" value="{{ $member->permanent_address ?? '' }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $countryOptions = App\Models\Country::getCountryList();
            @endphp
            <x-select name="permanentcountry" label="Country :" formLabelClass="form-label" formSelectClass="form-select" :options="$countryOptions ?? []" :value="$member->pcountry_master_pk ?? ''" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $stateOptions = App\Models\State::getStateList();
            @endphp
            <x-select name="permanentstate" label="State :" formLabelClass="form-label" formSelectClass="form-select" :options="$stateOptions ?? []" :value="$member->pstate_master_pk ?? ''" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $districtOptions = App\Models\District::getDistrictList();
            @endphp
            <x-select name="permanentdistrict" label="District :" formLabelClass="form-label" formSelectClass="form-select" :options="$districtOptions ?? []" :value="$member->pstate_district_mapping_pk ?? ''" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $cityOptions = App\Models\City::getCityList();
            @endphp
            <x-select name="permanentcity" label="City :" formLabelClass="form-label" formSelectClass="form-select" :options="$cityOptions ?? []" :value="$member->pcity ?? ''" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="permanentpostal">Postal Code : <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="permanentpostal" name="permanentpostal" value="{{ $member->pzipcode ?? '' }}">
        </div>
    </div>
</div>
<p>Communication Details</p>
<hr>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="personalemail">Personal Email : <span class="text-danger">*</span></label>
            <input type="email" name="personalemail" id="personalemail" class="form-control" value="{{ $member->email ?? '' }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="officialemail">Official Email : <span class="text-danger">*</span></label>
           <input type="email" name="officialemail" id="officialemail" class="form-control" value="{{ $member->officalemail ?? '' }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="mnumber">Mobile Number : <span class="text-danger">*</span></label>
            <input type="number" name="mnumber" id="mnumber" class="form-control" value="{{ $member->mobile ?? '' }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="emergencynumber ">Emergency Contact Number :</label>
            <input type="number" class="form-control" id="emergencynumber" name="emergencynumber" value="{{ $member->emergency_contact_no ?? '' }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="landlinenumber">Landline Number :</label>
            <input type="number" class="form-control" id="landlinenumber" name="landlinenumber" value="{{ $member->landline_contact_no ?? '' }}">
        </div>
    </div>
</div>