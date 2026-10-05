@extends('admin.layouts.master')

@section('title', 'Edit Country')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // The list's switch saves inactive as 0 but this form only offers 1 / 2; read
    // anything other than 1 as Inactive so saving can't silently re-activate it.
    $countryStatus = (string) old('active_inactive', (int) ($country->active_inactive ?? 1) === 1 ? 1 : 2);
@endphp
<div class="container-fluid mst-page">
    <x-breadcrum title="Edit Country" />
    <x-session_message />

    <form method="POST" action="{{ route('master.country.update', $country->pk) }}">
        @csrf
        @method('PUT')

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Country Details</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="countryName" class="mst-form-label d-block">
                            Country Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="countryName" name="country_name"
                               class="form-control mst-control @error('country_name') is-invalid @enderror"
                               value="{{ old('country_name', $country->country_name) }}"
                               maxlength="255" required aria-required="true">
                        @error('country_name')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="countryStatus" class="mst-form-label d-block">
                            Status <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="active_inactive" id="countryStatus"
                                class="form-select mst-control mst-searchable @error('active_inactive') is-invalid @enderror"
                                data-placeholder="Select Status" required aria-required="true">
                            <option value="1" @selected($countryStatus === '1')>Active</option>
                            <option value="2" @selected($countryStatus === '2')>Inactive</option>
                        </select>
                        @error('active_inactive')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('master.country.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
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
