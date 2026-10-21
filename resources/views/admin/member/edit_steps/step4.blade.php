@php
    $countryOptions = App\Models\Country::getCountryList();
    $currentStateOptions = App\Models\State::where('country_master_pk', $member->country_master_pk)->get()->pluck('state_name', 'pk');
    $currentDistrictOptions = App\Models\District::where('state_master_pk', $member->state_master_pk)->get()->pluck('district_name', 'pk');
    $currentCityOptions = App\Models\City::where([
        'district_master_pk' => $member->state_district_mapping_pk,
        'state_master_pk' => $member->state_master_pk,
    ])->get()->pluck('city_name', 'pk');

    $stateOptions = App\Models\State::getStateList();
    $districtOptions = App\Models\District::getDistrictList();
    $cityOptions = App\Models\City::getCityList();
@endphp

<div class="mbrw-section">
    <h6 class="mbrw-section-title">Current Address</h6>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <x-select name="country" label="Country" placeholder="Select Country" formLabelClass="form-label" formSelectClass="form-select" :options="$countryOptions ?? []" :value="$member->country_master_pk" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-select name="state" label="State" placeholder="Select State" formLabelClass="form-label" formSelectClass="form-select" :options="$currentStateOptions ?? []" :value="$member->state_master_pk" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-select name="district" label="District" placeholder="Select District" formLabelClass="form-label" formSelectClass="form-select" :options="$currentDistrictOptions ?? []" :value="$member->state_district_mapping_pk" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-select name="city" label="City" placeholder="Select City" formLabelClass="form-label" formSelectClass="form-select" :options="$currentCityOptions ?? []" :value="$member->city ?? ''" labelRequired="true" />
        </div>
    </div>

    {{-- other city name --}}
    <div class="col-md-6 d-none" id="otherCityContainer">
        <div class="mb-3">
            <x-input name="other_city" label="Other City Name" placeholder="eg. Dehradun" formLabelClass="form-label" formInputClass="form-control" />
        </div>
    </div>
    {{-- /other city name --}}

    <div class="col-md-6">
        <div class="mb-3">
            <x-input
                name="address"
                label="Address"
                placeholder="eg. 3005 Cranberry, Wareham MA"
                formLabelClass="form-label"
                formInputClass="form-control"
                value="{{ $member->current_address ?? '' }}"
                labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="postal" label="Postal Code" placeholder="eg. 123456" formLabelClass="form-label" formInputClass="form-control" value="{{ $member->zipcode ?? '' }}" labelRequired="true" />
        </div>
    </div>
</div>

<div class="mbrw-section">
    <h6 class="mbrw-section-title">Permanent Address</h6>
    {{-- Copies the current address across; wired globally in
         public/admin_assets/js/custom.js — keep the name and the id. --}}
    <div class="mbrw-section-aside form-check mb-0">
        <input type="checkbox" name="styled_max_checkbox" class="form-check-input" id="customCheck4"
            aria-invalid="false">
        <label class="form-check-label" for="customCheck4">Keep same as Current Address</label>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <x-select name="permanentcountry" label="Country" placeholder="Select Country" formLabelClass="form-label" formSelectClass="form-select" :options="$countryOptions ?? []" :value="$member->pcountry_master_pk ?? ''" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-select name="permanentstate" label="State" placeholder="Select State" formLabelClass="form-label" formSelectClass="form-select" :options="$stateOptions ?? []" :value="$member->pstate_master_pk ?? ''" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-select name="permanentdistrict" label="District" placeholder="Select District" formLabelClass="form-label" formSelectClass="form-select" :options="$districtOptions ?? []" :value="$member->pstate_district_mapping_pk ?? ''" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-select name="permanentcity" label="City" placeholder="Select City" formLabelClass="form-label" formSelectClass="form-select" :options="$cityOptions ?? []" :value="$member->pcity ?? ''" labelRequired="true" />
        </div>
    </div>

    {{-- other permanent city name --}}
    <div class="col-md-6 d-none" id="permanentOtherCityContainer">
        <div class="mb-3">
            <x-input name="permanent_other_city" label="Other Permanent City Name" placeholder="eg. Dehradun" formLabelClass="form-label" formInputClass="form-control" />
        </div>
    </div>
    {{-- /other permanent city name --}}

    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="permanentaddress">Address <span class="text-danger">*</span></label>
            <input type="text" name="permanentaddress" id="permanentaddress" class="form-control"
                placeholder="eg. 3005 Cranberry, Wareham MA" value="{{ $member->permanent_address ?? '' }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="permanentpostal">Postal Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="permanentpostal" name="permanentpostal"
                placeholder="eg. 123456" value="{{ $member->pzipcode ?? '' }}">
        </div>
    </div>
</div>

<div class="mbrw-section">
    <h6 class="mbrw-section-title">Communication Details</h6>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="personalemail">Personal Email <span class="text-danger">*</span></label>
            <input type="email" name="personalemail" id="personalemail" class="form-control"
                placeholder="eg. yourmail@mail.com" value="{{ $member->email ?? '' }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="officialemail">Official Email <span class="text-danger">*</span></label>
            <input type="email" name="officialemail" id="officialemail" class="form-control"
                placeholder="eg. yourmail@companyname.com" value="{{ $member->officalemail ?? '' }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="mnumber">Mobile Number <span class="text-danger">*</span></label>
            <input type="number" name="mnumber" id="mnumber" class="form-control only-numbers"
                placeholder="eg. 1234567890" value="{{ $member->mobile ?? '' }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="emergencynumber">Emergency Contact Number</label>
            <input type="number" class="form-control only-numbers" id="emergencynumber" name="emergencynumber"
                placeholder="eg. 1234567890" value="{{ $member->emergency_contact_no ?? '' }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="landlinenumber">Landline Number</label>
            <input type="number" class="form-control only-numbers" id="landlinenumber" name="landlinenumber"
                placeholder="eg. 011 1234567" value="{{ $member->landline_contact_no ?? '' }}">
        </div>
    </div>
</div>
