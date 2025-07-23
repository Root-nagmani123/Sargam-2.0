<p>Current Address</p>
<hr>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="address" label="Address :" formLabelClass="form-label" formInputClass="form-control" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $countryOptions = App\Models\Country::getCountryList();
            @endphp
            <x-select name="country" label="Country :" formLabelClass="form-label" formSelectClass="form-select" :options="$countryOptions ?? []" labelRequired="true" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $stateOptions = App\Models\State::getStateList();
            @endphp
            <x-select name="state" label="State :" formLabelClass="form-label" formSelectClass="form-select" :options="$stateOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $districtOptions = App\Models\District::getDistrictList();
            @endphp
            <x-select name="district" label="District :" formLabelClass="form-label" formSelectClass="form-select" :options="$districtOptions ?? []" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $cityOptions = App\Models\City::getCityList();
            @endphp
            <x-select name="city" label="City :" formLabelClass="form-label" formSelectClass="form-select" :options="$cityOptions ?? []" labelRequired="true" />
        </div>
    </div>

    {{-- other city name --}}
    <div class="col-md-6 d-none" id="otherCityContainer">
        <div class="mb-3">
            <x-input name="other_city" label="Other City Name :" formLabelClass="form-label" formInputClass="form-control" />
        </div>
    </div>
    {{-- /other city name --}}
    
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="postal" label="Postal Code :" formLabelClass="form-label" formInputClass="form-control" labelRequired="true" />
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
            <input type="text" name="permanentaddress" id="permanentaddress" class="form-control">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $countryOptions = App\Models\Country::getCountryList();
            @endphp
            <x-select name="permanentcountry" label="Country :" formLabelClass="form-label" formSelectClass="form-select" :options="$countryOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $stateOptions = App\Models\State::getStateList();
            @endphp
            <x-select name="permanentstate" label="State :" formLabelClass="form-label" formSelectClass="form-select" :options="$stateOptions ?? []" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $districtOptions = App\Models\District::getDistrictList();
            @endphp
            <x-select name="permanentdistrict" label="District :" formLabelClass="form-label" formSelectClass="form-select" :options="$districtOptions ?? []" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $cityOptions = App\Models\City::getCityList();
            @endphp
            <x-select name="permanentcity" label="City :" formLabelClass="form-label" formSelectClass="form-select" :options="$cityOptions ?? []" labelRequired="true" />
        </div>
    </div>
    
    {{-- other permanent city name --}}
    <div class="col-md-6 d-none" id="permanentOtherCityContainer">
        <div class="mb-3">
            <x-input name="permanent_other_city" label="Other Permanent City Name :" formLabelClass="form-label" formInputClass="form-control" />
        </div>
    </div>
    {{-- /other permanent city name --}}

    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="permanentpostal">Postal Code : <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="permanentpostal" name="permanentpostal">
        </div>
    </div>
</div>
<p>Communication Details</p>
<hr>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="personalemail">Personal Email : <span class="text-danger">*</span></label>
            <input type="email" name="personalemail" id="personalemail" class="form-control">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="officialemail">Official Email : <span class="text-danger">*</span></label>
           <input type="email" name="officialemail" id="officialemail" class="form-control">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="mnumber">Mobile Number : <span class="text-danger">*</span></label>
            <input type="number" name="mnumber" id="mnumber" class="form-control only-numbers">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="emergencynumber ">Emergency Contact Number :</label>
            <input type="number" class="form-control only-numbers" id="emergencynumber" name="emergencycontact">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="landlinenumber">Landline Number :</label>
            <input type="number" class="form-control only-numbers" id="landlinenumber" name="landlinenumber">
        </div>
    </div>
</div>