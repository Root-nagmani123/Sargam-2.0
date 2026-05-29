<div class="mw-section-title">Current Address</div>
<hr class="mw-section-divider">
<div class="row g-3 mw-step-grid">
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="address" label="Address" placeholder="Street, area, landmark" formLabelClass="form-label" formInputClass="form-control" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $countryOptions = App\Models\Country::getCountryList();
            @endphp
            <x-select name="country" label="Country" formLabelClass="form-label" formSelectClass="form-select" :options="$countryOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $stateOptions = App\Models\State::getStateList();
            @endphp
            <x-select name="state" label="State" formLabelClass="form-label" formSelectClass="form-select" :options="$stateOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $districtOptions = App\Models\District::getDistrictList();
            @endphp
            <x-select name="district" label="District" formLabelClass="form-label" formSelectClass="form-select" :options="$districtOptions ?? []" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $cityOptions = App\Models\City::getCityList();
            @endphp
            <x-select name="city" label="City" formLabelClass="form-label" formSelectClass="form-select" :options="$cityOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6 d-none" id="otherCityContainer">
        <div class="mb-3">
            <x-input name="other_city" label="Other City Name" placeholder="Enter city name" formLabelClass="form-label" formInputClass="form-control" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="postal" label="Postal Code" placeholder="eg. 110001" formLabelClass="form-label" formInputClass="form-control" labelRequired="true" />
        </div>
    </div>
</div>

<fieldset class="mw-same-address-check">
    <div class="form-check">
        <input type="checkbox" name="styled_max_checkbox" data-validation-maxchecked-maxchecked="2"
            data-validation-maxchecked-message="Don't be greedy!"
            class="form-check-input" id="customCheck4" aria-invalid="false">
        <label class="form-check-label" for="customCheck4">Current &amp; Permanent Address both are same</label>
    </div>
</fieldset>

<div class="mw-section-title">Permanent Address</div>
<hr class="mw-section-divider">
<div class="row g-3 mw-step-grid">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="permanentaddress">Address <span class="text-danger">*</span></label>
            <input type="text" name="permanentaddress" id="permanentaddress" class="form-control" placeholder="Street, area, landmark">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $countryOptions = App\Models\Country::getCountryList();
            @endphp
            <x-select name="permanentcountry" label="Country" formLabelClass="form-label" formSelectClass="form-select" :options="$countryOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $stateOptions = App\Models\State::getStateList();
            @endphp
            <x-select name="permanentstate" label="State" formLabelClass="form-label" formSelectClass="form-select" :options="$stateOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $districtOptions = App\Models\District::getDistrictList();
            @endphp
            <x-select name="permanentdistrict" label="District" formLabelClass="form-label" formSelectClass="form-select" :options="$districtOptions ?? []" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $cityOptions = App\Models\City::getCityList();
            @endphp
            <x-select name="permanentcity" label="City" formLabelClass="form-label" formSelectClass="form-select" :options="$cityOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6 d-none" id="permanentOtherCityContainer">
        <div class="mb-3">
            <x-input name="permanent_other_city" label="Other Permanent City Name" placeholder="Enter city name" formLabelClass="form-label" formInputClass="form-control" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="permanentpostal">Postal Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="permanentpostal" name="permanentpostal" placeholder="eg. 110001">
        </div>
    </div>
</div>

<div class="mw-section-title">Communication Details</div>
<hr class="mw-section-divider">
<div class="row g-3 mw-step-grid">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="personalemail">Personal Email <span class="text-danger">*</span></label>
            <input type="email" name="personalemail" id="personalemail" class="form-control" placeholder="name@email.com">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="officialemail">Official Email <span class="text-danger">*</span></label>
            <input type="email" name="officialemail" id="officialemail" class="form-control" placeholder="name@organisation.gov.in">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="mnumber">Mobile Number <span class="text-danger">*</span></label>
            <input type="number" name="mnumber" id="mnumber" class="form-control only-numbers" placeholder="eg. 9876543210">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="emergencynumber">Emergency Contact Number</label>
            <input type="number" class="form-control only-numbers" id="emergencynumber" name="emergencycontact" placeholder="eg. 9876543210">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="landlinenumber">Landline Number</label>
            <input type="number" class="form-control only-numbers" id="landlinenumber" name="landlinenumber" placeholder="eg. 0112345678">
        </div>
    </div>
</div>
