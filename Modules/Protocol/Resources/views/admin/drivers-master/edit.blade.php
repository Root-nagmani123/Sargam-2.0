@extends('admin.layouts.master')

@section('title', 'Edit Driver')
@section('page_title', 'Edit Driver')
@section('page_subtitle', 'Update driver information')

@section('setup_content')

    @include('protocol::partials.style')

    @push('styles')
        <link rel="stylesheet" href="{{ asset('Modules/protocol/css/driver_master.css') }}">
    @endpush

    <div class="container-fluid profile-page">

        <x-breadcrum
            title="Edit Driver"
            :items="[
                'Home',
                ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
                ['label' => 'Drivers Master', 'url' => route('protocol.driver-master.index')],
                'Edit Driver',
            ]"
            :showBack="true"
            :buttonUrl="route('protocol.driver-master.index')"
            buttonIcon="arrow_back"
        />

        <div class="card-clean p-3">

            <div class="container">

                <div class="form-header">
                    <h2>
                        <i class="fas fa-user-edit"></i>
                        Edit Driver Details
                    </h2>

                    <p>
                        Update the driver's personal and employment information
                    </p>
                </div>


                <form
                    id="driverForm"
                    method="POST"
                    action="{{ route('protocol.driver-master.update', $data->id) }}"
                >

                    @csrf
                    @method('PUT')


                    {{-- ========================================================= --}}
                    {{-- DRIVER PERSONAL DETAILS --}}
                    {{-- ========================================================= --}}

                    <div class="form-section border rounded p-3">

                        <div class="form-section-header">
                            <i class="fas fa-id-card"></i>
                            Personal Details
                        </div>

                        <div class="row">


                            {{-- ===================================================== --}}
                            {{-- Driver Name --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-6 mb-3">

                                <label for="driverName" class="form-label">
                                    Driver Name
                                    <span class="required">*</span>
                                </label>

                                <input
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    id="driverName"
                                    name="name"
                                    placeholder="Enter driver name"
                                    maxlength="255"
                                    autocomplete="off"
                                    value="{{ old('name', $data->name) }}"
                                >

                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- Driver Type --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-6 mb-3">

                                <label for="driverType" class="form-label">
                                    Driver Type
                                    <span class="required">*</span>
                                </label>

                                <select
                                    class="form-select @error('type') is-invalid @enderror"
                                    id="driverType"
                                    name="type"
                                >

                                    <option value="">
                                        Select Driver Type
                                    </option>

                                    <option
                                        value="Permanent"
                                        {{ old('type', $data->type) == 'Permanent' ? 'selected' : '' }}
                                    >
                                        Permanent
                                    </option>

                                    <option
                                        value="Contract"
                                        {{ old('type', $data->type) == 'Contract' ? 'selected' : '' }}
                                    >
                                        Contract
                                    </option>

                                </select>

                                @error('type')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- Date of Birth --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-4 mb-3">

                                <label for="dob" class="form-label">
                                    Date of Birth
                                    <span class="required">*</span>
                                </label>

                                <input
                                    type="date"
                                    class="form-control @error('dob') is-invalid @enderror"
                                    id="dob"
                                    name="dob"
                                    value="{{ old('dob', $data->dob?->format('Y-m-d')) }}"
                                >

                                @error('dob')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- Driver Number --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-4 mb-3">

                                <label for="driverNumber" class="form-label">
                                    Driver Number
                                    <span class="required">*</span>
                                </label>

                                <input
                                    type="number"
                                    class="form-control @error('driver_no') is-invalid @enderror"
                                    id="driverNumber"
                                    name="driver_no"
                                    placeholder="Enter driver number"
                                    min="0"
                                    autocomplete="off"
                                    value="{{ old('driver_no', $data->driver_no) }}"
                                >

                                @error('driver_no')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- Driving Licence Number --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-4 mb-3">

                                <label for="drivingLicenceNo" class="form-label">
                                    Driving Licence No.
                                    <span class="required">*</span>
                                </label>

                                <input
                                    type="text"
                                    class="form-control text-uppercase @error('driving_licence_no') is-invalid @enderror"
                                    id="drivingLicenceNo"
                                    name="driving_licence_no"
                                    placeholder="Enter driving licence number"
                                    maxlength="255"
                                    autocomplete="off"
                                    value="{{ old('driving_licence_no', $data->driving_licence_no) }}"
                                >

                                @error('driving_licence_no')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- Licence Expiry Date --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-4 mb-3">

                                <label for="licenceExpiryDate" class="form-label">
                                    Licence Expiry Date
                                    <span class="required">*</span>
                                </label>

                                <input
                                    type="date"
                                    class="form-control @error('licence_expiry_date') is-invalid @enderror"
                                    id="licenceExpiryDate"
                                    name="licence_expiry_date"
                                    value="{{ old('licence_expiry_date', $data->licence_expiry_date?->format('Y-m-d')) }}"
                                >

                                @error('licence_expiry_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- Hiring Date --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-4 mb-3">

                                <label for="hiringDate" class="form-label">
                                    Hiring Date
                                    <span class="required">*</span>
                                </label>

                                <input
                                    type="date"
                                    class="form-control @error('hiring_date') is-invalid @enderror"
                                    id="hiringDate"
                                    name="hiring_date"
                                    value="{{ old('hiring_date', $data->hiring_date?->format('Y-m-d')) }}"
                                >

                                @error('hiring_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- Helper One --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-4 mb-3">

                                <label for="helperOne" class="form-label">
                                    Helper One
                                </label>

                                <input
                                    type="text"
                                    class="form-control @error('helper_one') is-invalid @enderror"
                                    id="helperOne"
                                    name="helper_one"
                                    placeholder="Enter helper one"
                                    maxlength="255"
                                    value="{{ old('helper_one', $data->helper_one) }}"
                                >

                                @error('helper_one')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- Helper Two --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-4 mb-3">

                                <label for="helperTwo" class="form-label">
                                    Helper Two
                                </label>

                                <input
                                    type="text"
                                    class="form-control @error('helper_two') is-invalid @enderror"
                                    id="helperTwo"
                                    name="helper_two"
                                    placeholder="Enter helper two"
                                    maxlength="255"
                                    value="{{ old('helper_two', $data->helper_two) }}"
                                >

                                @error('helper_two')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- Note About Driver --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-12 mb-3">

                                <label for="noteAboutDriver" class="form-label">
                                    Note About Driver
                                </label>

                                <textarea
                                    class="form-control @error('note_about_driver') is-invalid @enderror"
                                    id="noteAboutDriver"
                                    name="note_about_driver"
                                    rows="3"
                                    placeholder="Enter notes about driver"
                                >{{ old('note_about_driver', $data->note_about_driver) }}</textarea>

                                @error('note_about_driver')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>

                    </div>


                    {{-- ========================================================= --}}
                    {{-- CONTACT / ADDRESS DETAILS --}}
                    {{-- ========================================================= --}}

                    <div class="form-section border rounded p-3 mt-3">

                        <div class="form-section-header">
                            <i class="fas fa-address-card"></i>
                            Contact & Address Information
                        </div>

                        <div class="row">


                            {{-- ===================================================== --}}
                            {{-- Country --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-4 mb-3">

                                <label for="countryId" class="form-label">
                                    Country
                                    <span class="required">*</span>
                                </label>

                                <select
                                    class="form-select @error('country_id') is-invalid @enderror"
                                    id="countryId"
                                    name="country_id"
                                >

                                    <option value="">
                                        Select Country
                                    </option>

                                    {{-- Populated dynamically by AJAX --}}

                                </select>

                                @error('country_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- State --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-4 mb-3">

                                <label for="stateId" class="form-label">
                                    State
                                    <span class="required">*</span>
                                </label>

                                <select
                                    class="form-select @error('state_id') is-invalid @enderror"
                                    id="stateId"
                                    name="state_id"
                                    disabled
                                >

                                    <option value="">
                                        Select State
                                    </option>

                                    {{-- Populated dynamically by AJAX --}}

                                </select>

                                @error('state_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- City --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-4 mb-3">

                                <label for="cityId" class="form-label">
                                    City
                                    <span class="required">*</span>
                                </label>

                                <select
                                    class="form-select @error('city_id') is-invalid @enderror"
                                    id="cityId"
                                    name="city_id"
                                    disabled
                                >

                                    <option value="">
                                        Select City
                                    </option>

                                    {{-- Populated dynamically by AJAX --}}

                                </select>

                                @error('city_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- Postal Code --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-4 mb-3">

                                <label for="postalCode" class="form-label">
                                    Postal Code
                                    <span class="required">*</span>
                                </label>

                                <input
                                    type="text"
                                    class="form-control @error('postal_code') is-invalid @enderror"
                                    id="postalCode"
                                    name="postal_code"
                                    placeholder="Enter postal code"
                                    maxlength="20"
                                    inputmode="numeric"
                                    value="{{ old('postal_code', $data->postal_code) }}"
                                >

                                @error('postal_code')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- Email --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-4 mb-3">

                                <label for="email" class="form-label">
                                    Email
                                    <span class="required">*</span>
                                </label>

                                <input
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    id="email"
                                    name="email"
                                    placeholder="Enter email address"
                                    maxlength="150"
                                    autocomplete="off"
                                    value="{{ old('email', $data->email) }}"
                                >

                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ===================================================== --}}
                            {{-- Address --}}
                            {{-- ===================================================== --}}

                            <div class="col-md-12 mb-3">

                                <label for="address" class="form-label">
                                    Address
                                    <span class="required">*</span>
                                </label>

                                <textarea
                                    class="form-control @error('address') is-invalid @enderror"
                                    id="address"
                                    name="address"
                                    rows="3"
                                    placeholder="Enter complete address"
                                >{{ old('address', $data->address) }}</textarea>

                                @error('address')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>

                    </div>


                    {{-- ========================================================= --}}
                    {{-- BUTTONS --}}
                    {{-- ========================================================= --}}

                    <div class="d-flex gap-2 mt-4">

                        <button
                            type="submit"
                            class="btn btn-success d-inline-flex align-items-center"
                            id="submitDriverForm"
                        >

                            <i
                                class="material-icons material-symbols-rounded me-2"
                                style="font-size: 20px;"
                            >
                                update
                            </i>

                            Update

                        </button>


                        <a
                            href="{{ route('protocol.driver-master.index') }}"
                            class="btn btn-secondary d-inline-flex align-items-center"
                        >

                            <i
                                class="material-icons material-symbols-rounded me-2"
                                style="font-size: 20px;"
                            >
                                cancel
                            </i>

                            Cancel

                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection


{{-- ========================================================= --}}
{{-- JAVASCRIPT --}}
{{-- ========================================================= --}}

@push('scripts')

    <script>

        window.driverMasterUrls = {
            countries: "{{ route('protocol.countries') }}",
            states: "{{ route('protocol.states', ':country_id') }}",
            cities: "{{ route('protocol.cities', ':state_id') }}"
        };


        /*
        |--------------------------------------------------------------------------
        | Existing Driver Values
        |--------------------------------------------------------------------------
        |
        | These values are used by state_city.js to automatically load:
        |
        | Country → State → City
        |
        */

        window.driverMasterOldValues = {
            country_id: "{{ old('country_id', $data->country_id) }}",
            state_id: "{{ old('state_id', $data->state_id) }}",
            city_id: "{{ old('city_id', $data->city_id) }}"
        };

    </script>

    <script src="{{ asset('Modules/protocol/js/driver_master.js') }}"></script>
    <script src="{{ asset('Modules/protocol/js/state_city.js') }}"></script>

@endpush