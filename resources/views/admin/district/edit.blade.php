@extends('admin.layouts.master')

@section('title', 'Edit District')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // The grid's status switch stores 0 for Inactive, the form stores 2.
    // Map every non-1 value to the Inactive option: with no option matching 0
    // the browser pre-selected Active, so saving re-activated the record.
    $districtStatus = old_string('active_inactive', $district->active_inactive ?? 1) === '1' ? '1' : '2';
@endphp
<div class="container-fluid mst-page">
    <x-breadcrum title="Edit District" />
    <x-session_message />

    {{-- Form root: the index opens this same form in a modal
         (public/js/master-admin.js openFormModal). Keep form-specific JS in
         the x-mst-form-init block inside it, bound to elements under root. --}}
    <div data-mst-form-root data-mst-form-title="Edit District">
        <form action="{{ route('master.district.update', $district->pk) }}" method="POST">
            @csrf

            <div class="card mst-form-card">
                <div class="card-body">
                    <h2 class="mst-form-section-title h6">District Details</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="country_master_pk" class="mst-form-label d-block">
                                Country <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="country_master_pk" id="country_master_pk"
                                    class="form-select mst-control mst-searchable @error('country_master_pk') is-invalid @enderror"
                                    data-placeholder="Select Country" required aria-required="true">
                                <option value="">Select Country</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->pk }}" {{ old('country_master_pk', $district->country_master_pk) == $country->pk ? 'selected' : '' }}>
                                        {{ $country->country_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('country_master_pk')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- id="state" is kept: admin_assets/js/custom.js binds a
                             delegated change handler to #state. --}}
                        <div class="col-md-6">
                            <label for="state" class="mst-form-label d-block">
                                State <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="state_master_pk" id="state"
                                    class="form-select mst-control mst-searchable @error('state_master_pk') is-invalid @enderror"
                                    data-placeholder="Select State" required aria-required="true">
                                <option value="">Select State</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->pk }}" {{ $state->pk == old('state_master_pk', $district->state_master_pk) ? 'selected' : '' }}>
                                        {{ $state->state_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('state_master_pk')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="district_name" class="mst-form-label d-block">
                                District Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="district_name" name="district_name"
                                   class="form-control mst-control @error('district_name') is-invalid @enderror"
                                   value="{{ old('district_name', $district->district_name) }}"
                                   maxlength="100" required aria-required="true">
                            @error('district_name')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="districtStatus" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="active_inactive" id="districtStatus"
                                    class="form-select mst-control mst-searchable @error('active_inactive') is-invalid @enderror"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="1" @selected($districtStatus === '1')>Active</option>
                                <option value="2" @selected($districtStatus === '2')>Inactive</option>
                            </select>
                            @error('active_inactive')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mst-form-footer">
                        <a href="{{ route('master.district.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                        <button type="submit" class="btn mst-btn-submit px-4">Update</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
@endpush
