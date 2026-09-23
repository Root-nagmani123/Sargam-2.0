@extends('admin.layouts.master')

@section('title', 'Create New Vehicle')
@section('page_title', 'Create New Vehicle')
@section('page_subtitle', 'Add new vehicle')

@section('setup_content')

@include('protocol::partials.style')

@push('styles')
<style>
.form-section {
    background: #fff;
    padding: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 20px;
}
.form-section .section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f5f7fa;
    color: #0d4f9c;
    padding: 11px 15px;
    border-left: 4px solid #0d4f9c;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 16px;
    font-weight: 600;
}
.form-label {
    color: #374151;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 6px;
}
.form-label.required::after {
    content: ' *';
    color: #dc3545;
}
.form-control,
.form-select {
    min-height: 40px;
    border: 1px solid #d1d5db;
    border-radius: 5px;
    font-size: 14px;
}
.form-control:focus,
.form-select:focus {
    border-color: #0d4f9c;
    box-shadow: 0 0 0 .15rem rgba(13, 79, 156, .12);
}
.radio-group {
    display: flex;
    align-items: center;
    gap: 25px;
    min-height: 40px;
}
.error {
    color: #dc3545;
    font-size: 12px;
    margin-top: 4px;
}
.form-control.error,
.form-select.error {
    border-color: #dc3545;
}
.form-control.valid,
.form-select.valid {
    border-color: #198754;
}
</style>
@endpush

<div class="container-fluid profile-page">

    <x-breadcrum
        title="Add Vehicle"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            ['label' => 'Vehicle Master', 'url' => route('protocol.vehicle-master.index')],
            'Add Vehicle',
        ]"
        :showBack="true"
        :buttonUrl="route('protocol.vehicle-master.index')"
        buttonIcon="arrow_back"
    />

    <div class="card-clean p-3">

        <div class="form-header">
            <div>
                <h2><i class="fas fa-car"></i> Define Vehicle Information</h2>
                <p>Please add the vehicle details in the form below</p>
            </div>
        </div>

        <form id="vehicleForm" method="POST" action="{{ route('protocol.vehicle-master.store') }}" novalidate>
            @csrf

            {{-- VEHICLE INFORMATION --}}
            <div class="form-section">
                <div class="section-title"><i class="fas fa-car"></i> Vehicle Information</div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Vehicle Name</label>
                        <input type="text" name="vehicle_name" class="form-control" value="{{ old('vehicle_name') }}" placeholder="Enter vehicle name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Vehicle Number</label>
                        <input type="text" name="vehicle_number" class="form-control" value="{{ old('vehicle_number') }}" placeholder="Enter vehicle number">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Manufacturer</label>
                        <input type="text" name="manufacturer" class="form-control" value="{{ old('manufacturer') }}" placeholder="Enter manufacturer">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Engine Number</label>
                        <input type="text" name="engine_number" class="form-control" value="{{ old('engine_number') }}" placeholder="Enter engine number">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Chassis Number</label>
                        <input type="text" name="chassis_number" class="form-control" value="{{ old('chassis_number') }}" placeholder="Enter chassis number">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Model</label>
                        <input type="text" name="model" class="form-control" value="{{ old('model') }}" placeholder="Enter vehicle model">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Average Committed</label>
                        <div class="input-group">
                            <input type="number" name="average_committed" class="form-control" value="{{ old('average_committed') }}" min="0" step="0.01" placeholder="0.00">
                            <span class="input-group-text">km/L</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Current Reading</label>
                        <input type="number" name="current_reading" class="form-control" value="{{ old('current_reading', 0) }}" min="0" step="0.01" placeholder="Enter odometer reading">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Fuel Type</label>
                        <select name="fuel_type" class="form-select">
                            <option value="">-- Select Fuel Type --</option>
                            @foreach(['petrol'=>'Petrol','diesel'=>'Diesel','cng'=>'CNG','lpg'=>'LPG','electric'=>'Electric','hybrid'=>'Hybrid'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('fuel_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Vehicle Type</label>
                        <select name="vehicle_type" class="form-select">
                            <option value="">-- Select Vehicle Type --</option>
                            @foreach(['hatchback'=>'Hatchback','sedan'=>'Sedan','suv'=>'SUV','bus'=>'Bus','van'=>'Van','pickup_truck'=>'Pickup Truck','truck'=>'Truck','electric'=>'Electric Vehicle','hybrid'=>'Hybrid'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('vehicle_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Capacity</label>
                        <div class="input-group">
                            <input type="number" name="capacity" class="form-control" value="{{ old('capacity') }}" min="1" step="1" placeholder="Enter capacity">
                            <span class="input-group-text">Seats / Units</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Status</label>
                        <div class="radio-group">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="status" value="1" id="statusActive" @checked(old('status', '1') == '1')>
                                <label class="form-check-label" for="statusActive">Active</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="status" value="0" id="statusInactive" @checked(old('status') === '0')>
                                <label class="form-check-label" for="statusInactive">In-Active</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Manufacture Year</label>
                        <select name="manufacture_year" id="manufactureYear" class="form-select">
                            <option value="">-- Select Year --</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Maintained By</label>
                        <input type="text" name="maintained_by" class="form-control" value="{{ old('maintained_by') }}" placeholder="Enter maintained by">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Registration Number</label>
                        <input type="text" name="registration_number" class="form-control" value="{{ old('registration_number') }}" placeholder="Enter registration number">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Country</label>
                        <select name="country_id" id="countryId" class="form-select">
                            <option value="">Select Country</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">State</label>
                        <select name="state_id" id="stateId" class="form-select" disabled>
                            <option value="">Select State</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">City</label>
                        <select name="city_id" id="cityId" class="form-select" disabled>
                            <option value="">Select City</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Driver</label>
                        <select name="driver_id" class="form-select">
                            <option value="">Select Driver</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" @selected(old('driver_id') == $driver->id)>{{ $driver->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Registration Authority</label>
                        <input type="text" name="registration_authority" class="form-control" value="{{ old('registration_authority') }}" placeholder="Enter registration authority">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vehicle Part</label>
                        <input type="text" name="vehicle_part" class="form-control" value="{{ old('vehicle_part') }}" placeholder="Enter vehicle part">
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">Vehicle Notes</label>
                        <textarea name="vehicle_notes" class="form-control" rows="3" placeholder="Enter vehicle notes">{{ old('vehicle_notes') }}</textarea>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Validity in KM</label>
                        <input type="number" name="validity_km" class="form-control" value="{{ old('validity_km') }}" min="0" step="0.01" placeholder="Enter validity in KM">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Validity in Year(s)</label>
                        <input type="number" name="validity_years" class="form-control" value="{{ old('validity_years') }}" min="0" step="1" placeholder="Enter validity in years">
                    </div>
                </div>
            </div>

            {{-- FINANCIAL INFORMATION --}}
            <div class="form-section">
                <div class="section-title"><i class="fas fa-credit-card"></i> Vehicle Financial Information</div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Owned By</label>
                        <input type="text" name="owned_by" class="form-control" value="{{ old('owned_by') }}" placeholder="Enter owner name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Financed By</label>
                        <input type="text" name="financed_by" class="form-control" value="{{ old('financed_by') }}" placeholder="Enter finance company">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Total Vehicle Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="total_vehicle_cost" class="form-control" value="{{ old('total_vehicle_cost') }}" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Actual Vehicle Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="actual_vehicle_cost" class="form-control" value="{{ old('actual_vehicle_cost') }}" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Purchase / Rental</label>
                        <div class="radio-group">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="purchase_type" value="purchase" id="purchase" @checked(old('purchase_type', 'purchase') === 'purchase')>
                                <label class="form-check-label" for="purchase">Purchase</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="purchase_type" value="rent" id="rent" @checked(old('purchase_type') === 'rent')>
                                <label class="form-check-label" for="rent">Rent</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date') }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Purchase Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="purchase_cost" class="form-control" value="{{ old('purchase_cost') }}" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Margin Money</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="margin_money" class="form-control" value="{{ old('margin_money') }}" min="0" step="0.01">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Loan Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="loan_amount" class="form-control" value="{{ old('loan_amount') }}" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">EMI</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="emi" class="form-control" value="{{ old('emi') }}" min="0" step="0.01">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Rate Of Interest</label>
                        <div class="input-group">
                            <input type="number" name="rate_of_interest" class="form-control" value="{{ old('rate_of_interest') }}" min="0" max="100" step="0.01">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">First Installment Date</label>
                        <input type="date" name="first_installment_date" class="form-control" value="{{ old('first_installment_date') }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Installment Date</label>
                        <input type="date" name="last_installment_date" class="form-control" value="{{ old('last_installment_date') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vendor Name</label>
                        <input type="text" name="vendor_name" class="form-control" value="{{ old('vendor_name') }}" placeholder="Enter vendor name">
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">Purchase / Rental Notes</label>
                        <textarea name="purchase_notes" class="form-control" rows="3" placeholder="Enter purchase or rental notes">{{ old('purchase_notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- INSURANCE INFORMATION --}}
            <div class="form-section">
                <div class="section-title">
                    <div class="d-flex align-items-center gap-2">
                        <input type="checkbox" name="has_insurance" value="1" id="insuranceCheckbox" class="form-check-input m-0" @checked(old('has_insurance'))>
                        <label for="insuranceCheckbox" class="mb-0"><i class="fas fa-shield-alt"></i> Vehicle Insurance Information</label>
                    </div>
                </div>

                <div id="insuranceContent" style="{{ old('has_insurance') ? '' : 'display:none;' }}">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Insurer Name</label>
                            <input type="text" name="insurer_name" class="form-control" value="{{ old('insurer_name') }}" placeholder="Enter insurer name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Insurance Company Name</label>
                            <input type="text" name="insurance_company_name" class="form-control" value="{{ old('insurance_company_name') }}" placeholder="Enter insurance company name">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Insurance Date</label>
                            <input type="date" name="insurance_date" class="form-control" value="{{ old('insurance_date') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Insurance Expiry Date</label>
                            <input type="date" name="insurance_expiry_date" class="form-control" value="{{ old('insurance_expiry_date') }}">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Premium Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="premium_amount" class="form-control" value="{{ old('premium_amount') }}" min="0" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Insurance Notes</label>
                            <textarea name="insurance_notes" class="form-control" rows="3" placeholder="Enter insurance notes">{{ old('insurance_notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-save"><i class="fas fa-save"></i> Save</button>
                <a href="{{ route('protocol.vehicle-master.index') }}" class="btn btn-cancel"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.driverMasterUrls = {
        countries: "{{ route('protocol.countries') }}",
        states: "{{ route('protocol.states', ':country_id') }}",
        cities: "{{ route('protocol.cities', ':state_id') }}"
    };
    window.driverMasterOldValues = {
        country_id: "{{ old('country_id') }}",
        state_id: "{{ old('state_id') }}",
        city_id: "{{ old('city_id') }}"
    };
</script>
<script src="{{ asset('Modules/protocol/js/state_city.js') }}"></script>
@endpush

@push('scripts')
<script>
    $(document).ready(function () {

        /* Manufacture Year */
        const currentYear = new Date().getFullYear();
        const yearSelect = $('#manufactureYear');
        for (let year = currentYear; year >= currentYear - 50; year--) {
            yearSelect.append($('<option>', { value: year, text: year }));
        }
        const oldManufactureYear = @json(old('manufacture_year', ''));
        if (oldManufactureYear) {
            yearSelect.val(oldManufactureYear);
        }

        /* Insurance Toggle */
        function toggleInsurance() {
            if ($('#insuranceCheckbox').is(':checked')) {
                $('#insuranceContent').slideDown(200);
            } else {
                $('#insuranceContent').slideUp(200);
                $('#insuranceContent').find('.error').removeClass('error');
                $('#insuranceContent').find('label.error').remove();
            }
        }
        $('#insuranceCheckbox').on('change', toggleInsurance);
        toggleInsurance();

        /* jQuery Validation */
        $('#vehicleForm').validate({
            ignore: [],
            errorElement: 'label',
            errorClass: 'error',
            validClass: 'valid',
            errorPlacement: function (error, element) {
                if (element.parent('.input-group').length) {
                    error.insertAfter(element.parent('.input-group'));
                } else if (element.attr('type') === 'radio') {
                    error.appendTo(element.closest('.radio-group'));
                } else {
                    error.insertAfter(element);
                }
            },
            rules: {
                vehicle_name: { required: true, maxlength: 255 },
                vehicle_number: { required: true, maxlength: 255 },
                manufacturer: { required: true, maxlength: 255 },
                engine_number: { required: true, maxlength: 255 },
                chassis_number: { required: true, maxlength: 255 },
                model: { required: true, maxlength: 255 },
                average_committed: { number: true, min: 0 },
                current_reading: { required: true, number: true, min: 0 },
                fuel_type: { required: true, maxlength: 255 },
                vehicle_type: { required: true, maxlength: 255 },
                capacity: { required: true, digits: true, min: 1 },
                status: { required: true },
                manufacture_year: { digits: true, min: currentYear - 50, max: currentYear },
                maintained_by: { required: true, maxlength: 255 },
                registration_number: { required: true, maxlength: 255 },
                country_id: { required: true },
                state_id: { required: true },
                city_id: { required: true },
                driver_id: { required: true, maxlength: 255 },
                registration_authority: { required: true, maxlength: 255 },
                vehicle_part: { maxlength: 255 },
                vehicle_notes: { maxlength: 5000 },
                validity_km: { number: true, min: 0 },
                validity_years: { required: true, digits: true, min: 0 },
                owned_by: { maxlength: 255 },
                financed_by: { maxlength: 255 },
                total_vehicle_cost: { number: true, min: 0 },
                actual_vehicle_cost: { number: true, min: 0 },
                purchase_type: { required: true },
                purchase_date: { date: true },
                purchase_cost: { number: true, min: 0 },
                margin_money: { number: true, min: 0 },
                loan_amount: { number: true, min: 0 },
                emi: { number: true, min: 0 },
                rate_of_interest: { number: true, min: 0, max: 100 },
                first_installment_date: { date: true },
                last_installment_date: { date: true },
                vendor_name: { maxlength: 255 },
                purchase_notes: { maxlength: 5000 },
                insurer_name: { maxlength: 255 },
                insurance_company_name: { maxlength: 255 },
                insurance_date: { date: true },
                insurance_expiry_date: { date: true },
                premium_amount: { number: true, min: 0 },
                insurance_notes: { maxlength: 5000 }
            },
            messages: {
                vehicle_name: { required: 'Please enter vehicle name.', maxlength: 'Vehicle name cannot exceed 255 characters.' },
                vehicle_number: { required: 'Please enter vehicle number.', maxlength: 'Vehicle number cannot exceed 255 characters.' },
                manufacturer: { required: 'Please enter manufacturer.', maxlength: 'Manufacturer cannot exceed 255 characters.' },
                engine_number: { required: 'Please enter engine number.', maxlength: 'Engine number cannot exceed 255 characters.' },
                chassis_number: { required: 'Please enter chassis number.', maxlength: 'Chassis number cannot exceed 255 characters.' },
                model: { required: 'Please enter vehicle model.', maxlength: 'Model cannot exceed 255 characters.' },
                current_reading: { required: 'Please enter current reading.', number: 'Please enter a valid reading.', min: 'Reading cannot be negative.' },
                fuel_type: { required: 'Please select fuel type.' },
                vehicle_type: { required: 'Please select vehicle type.' },
                capacity: { required: 'Please enter capacity.', digits: 'Capacity must be a whole number.', min: 'Capacity must be at least 1.' },
                maintained_by: { required: 'Please enter maintained by.' },
                registration_number: { required: 'Please enter registration number.' },
                country_id: { required: 'Please select country.' },
                state_id: { required: 'Please select state.' },
                city_id: { required: 'Please select city.' },
                driver_id: { required: 'Please select driver.' },
                registration_authority: { required: 'Please enter registration authority.' },
                validity_years: { required: 'Please enter validity in years.', digits: 'Validity years must be a whole number.', min: 'Validity years cannot be negative.' },
                purchase_type: { required: 'Please select purchase or rental.' }
            },
            submitHandler: function (form) {
                if ($('#insuranceCheckbox').is(':checked')) {
                    $('input[name="insurer_name"]').rules('add', { required: true, messages: { required: 'Please enter insurer name.' } });
                    $('input[name="insurance_company_name"]').rules('add', { required: true, messages: { required: 'Please enter insurance company name.' } });
                    $('input[name="insurance_date"]').rules('add', { required: true, messages: { required: 'Please select insurance date.' } });
                    $('input[name="insurance_expiry_date"]').rules('add', { required: true, messages: { required: 'Please select insurance expiry date.' } });
                    $('input[name="premium_amount"]').rules('add', { required: true, number: true, min: 0, messages: { required: 'Please enter premium amount.', number: 'Please enter a valid premium amount.', min: 'Premium amount cannot be negative.' } });
                } else {
                    $('input[name="insurer_name"]').rules('remove', 'required');
                    $('input[name="insurance_company_name"]').rules('remove', 'required');
                    $('input[name="insurance_date"]').rules('remove', 'required');
                    $('input[name="insurance_expiry_date"]').rules('remove', 'required');
                    $('input[name="premium_amount"]').rules('remove', 'required');
                }

                if ($('#vehicleForm')[0].checkValidity()) {
                    form.submit();
                }
            }
        });

    
        // ----------------------------
        // Fetch State, City, Driver on edit
        // ----------------------------

        function fetchStates(){
            const countryId = $('#country_id').val();
            if(!countryId) return;

            $.get(`{{ route('protocol.states', ':id') }}`.replace(':id',countryId), function(res){
                const states = res.data || [];
                const options = states.map(s => `<option value="${s.pk}" ${s.pk == {{ old('state_id', $data->state_id ?? '') }} ? 'selected' : ''}>${s.state_name}</option>`).join('');
                $('#state_id').html('<option value="">Select State</option>' + options);

                // If editing, also fetch cities
                if ({{ old('state_id', $data->state_id ?? 0) }}) {
                    $('#state_id').val({{ old('state_id', $data->state_id ?? '') }});
                    fetchCities({{ old('state_id', $data->state_id ?? 0) }});
                }
            });
        }

        function fetchCities(stateId){
            $.get(`{{ route('protocol.cities', ':id') }}`.replace(':id',stateId), function(res){
                const cities = res.data || [];
                const options = cities.map(c => `<option value="${c.pk}" ${c.pk == {{ old('city_id', $data->city_id ?? '') }} ? 'selected' : ''}>${c.city_name}</option>`).join('');
                $('#city_id').html('<option value="">Select City</option>' + options);
            });
        }

        function fetchDrivers(){
            const vehicleId = $('#vehicle_id').val();
            if(!vehicleId) return;

            $.get(`{{ route('protocol.vehicle-master.get-drivers', ':id') }}`.replace(':id', vehicleId), function(res){
                const drivers = res.data || [];
                const options = drivers.map(d => `<option value="${d.pk}" ${d.pk == {{ old('driver_id', $data->driver_id ?? '') }} ? 'selected' : ''}>${d.name} (${d.driver_no})</option>`).join('');
                $('#driver_id').html('<option value="">Select Driver</option>' + options);
            });
        }

        $(document).on('change', '#country_id', fetchStates);
        $(document).on('change', '#state_id', function(){
            fetchCities($(this).val());
        });
        $(document).on('change', '#vehicle_id', fetchDrivers);

        // Initial load if editing
        @if(isset($data) && $data->pk)
            fetchStates();
            fetchDrivers();
        @endif
    });
</script>
@endpush