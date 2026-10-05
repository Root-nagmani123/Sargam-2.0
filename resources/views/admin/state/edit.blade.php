@extends('admin.layouts.master')

@section('title', 'Edit State')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // The grid's status switch stores 0 for Inactive, the form stores 2.
    // Map every non-1 value to the Inactive option: with no option matching 0
    // the browser pre-selected Active, so saving re-activated the record.
    $stateStatus = (string) old('active_inactive', $state->active_inactive ?? 1) === '1' ? '1' : '2';
@endphp
<div class="container-fluid mst-page">
    <x-breadcrum title="Edit State" />
    <x-session_message />

    <form action="{{ route('master.state.update', $state->pk) }}" method="POST">
        @csrf
        @method('POST')

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">State Details</h2>

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
                                <option value="{{ $country->pk }}" {{ old('country_master_pk', $state->country_master_pk) == $country->pk ? 'selected' : '' }}>
                                    {{ $country->country_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('country_master_pk')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="state_name" class="mst-form-label d-block">
                            State Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="state_name" name="state_name"
                               class="form-control mst-control @error('state_name') is-invalid @enderror"
                               value="{{ old('state_name', $state->state_name) }}"
                               maxlength="255" required aria-required="true">
                        @error('state_name')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="stateStatus" class="mst-form-label d-block">
                            Status <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="active_inactive" id="stateStatus"
                                class="form-select mst-control mst-searchable @error('active_inactive') is-invalid @enderror"
                                data-placeholder="Select Status" required aria-required="true">
                            <option value="1" @selected($stateStatus === '1')>Active</option>
                            <option value="2" @selected($stateStatus === '2')>Inactive</option>
                        </select>
                        @error('active_inactive')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('master.state.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4">Update</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
@endpush
