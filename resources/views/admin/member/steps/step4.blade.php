<div class="row g-3">
    <div class="col-12">
        <p class="text-body-secondary small mb-0 fw-medium">Current Address</p>
        <hr class="my-2">
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="address" label="Address" formLabelClass="form-label fw-medium" formInputClass="form-control" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php $countryOptions = App\Models\Country::getCountryList(); @endphp
            <x-select name="country" label="Country" formLabelClass="form-label fw-medium" formSelectClass="form-select" :options="$countryOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php $stateOptions = App\Models\State::getStateList(); @endphp
            <x-select name="state" label="State" formLabelClass="form-label fw-medium" formSelectClass="form-select" :options="$stateOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php $districtOptions = App\Models\District::getDistrictList(); @endphp
            <x-select name="district" label="District" formLabelClass="form-label fw-medium" formSelectClass="form-select" :options="$districtOptions ?? []" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php $cityOptions = App\Models\City::getCityList(); @endphp
            <x-select name="city" label="City" formLabelClass="form-label fw-medium" formSelectClass="form-select" :options="$cityOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6 d-none" id="otherCityContainer">
        <div class="mb-0">
            <x-input name="other_city" label="Other City Name" formLabelClass="form-label fw-medium" formInputClass="form-control" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="postal" label="Postal Code" formLabelClass="form-label fw-medium" formInputClass="form-control" labelRequired="true" />
        </div>
    </div>
</div>

<fieldset class="mt-3">
    <div class="form-check border rounded-2 px-3 py-2 bg-body-secondary bg-opacity-25">
        <input type="checkbox" name="styled_max_checkbox" data-validation-maxchecked-maxchecked="2"
            data-validation-maxchecked-message="Don't be greedy!" required=""
            class="form-check-input" id="customCheck4" aria-invalid="false">
        <label class="form-check-label fw-medium" for="customCheck4">Current &amp; Permanent Address are the same</label>
    </div>
</fieldset>

<div class="row g-3 mt-1">
    <div class="col-12">
        <p class="text-body-secondary small mb-0 fw-medium">Permanent Address</p>
        <hr class="my-2">
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <label class="form-label fw-medium" for="permanentaddress">Address <span class="text-danger">*</span></label>
            <input type="text" name="permanentaddress" id="permanentaddress" class="form-control">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php $countryOptions = App\Models\Country::getCountryList(); @endphp
            <x-select name="permanentcountry" label="Country" formLabelClass="form-label fw-medium" formSelectClass="form-select" :options="$countryOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php $stateOptions = App\Models\State::getStateList(); @endphp
            <x-select name="permanentstate" label="State" formLabelClass="form-label fw-medium" formSelectClass="form-select" :options="$stateOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php $districtOptions = App\Models\District::getDistrictList(); @endphp
            <x-select name="permanentdistrict" label="District" formLabelClass="form-label fw-medium" formSelectClass="form-select" :options="$districtOptions ?? []" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php $cityOptions = App\Models\City::getCityList(); @endphp
            <x-select name="permanentcity" label="City" formLabelClass="form-label fw-medium" formSelectClass="form-select" :options="$cityOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6 d-none" id="permanentOtherCityContainer">
        <div class="mb-0">
            <x-input name="permanent_other_city" label="Other Permanent City Name" formLabelClass="form-label fw-medium" formInputClass="form-control" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <label class="form-label fw-medium" for="permanentpostal">Postal Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="permanentpostal" name="permanentpostal">
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12">
        <p class="text-body-secondary small mb-0 fw-medium">Communication Details</p>
        <hr class="my-2">
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <label class="form-label fw-medium" for="personalemail">Personal Email <span class="text-danger">*</span></label>
            <input type="email" name="personalemail" id="personalemail" class="form-control">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <label class="form-label fw-medium" for="officialemail">Official Email <span class="text-danger">*</span></label>
            <input type="email" name="officialemail" id="officialemail" class="form-control">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <label class="form-label fw-medium" for="mnumber">Mobile Number <span class="text-danger">*</span></label>
            <input type="number" name="mnumber" id="mnumber" class="form-control only-numbers">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <label class="form-label fw-medium" for="emergencynumber">Emergency Contact Number</label>
            <input type="number" class="form-control only-numbers" id="emergencynumber" name="emergencycontact">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <label class="form-label fw-medium" for="landlinenumber">Landline Number</label>
            <input type="number" class="form-control only-numbers" id="landlinenumber" name="landlinenumber">
        </div>
    </div>
</div>