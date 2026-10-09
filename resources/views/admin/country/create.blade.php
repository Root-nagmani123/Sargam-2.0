@extends('admin.layouts.master')

@section('title', 'Add Country')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // One card per posted name, so a rejected submit comes back with every row.
    $countryNames = old('country_name', ['']);
    $countryNames = is_array($countryNames) && count($countryNames) ? array_values($countryNames) : [''];
    $countryStatus = (string) old('active_inactive', 1);
@endphp
<div class="container-fluid mst-page">
    <x-breadcrum title="Add Country" />
    <x-session_message />

    {{-- Form root: the index opens this same form in a modal
         (public/js/master-admin.js openFormModal). Keep form-specific JS in
         the x-mst-form-init block inside it, bound to elements under root. --}}
    <div data-mst-form-root data-mst-form-title="Add Country">
        <form action="{{ route('master.country.store') }}" method="POST">
            @csrf

            <div class="card mst-form-card">
                <div class="card-body">
                    <h2 class="mst-form-section-title h6">Country Details</h2>

                    {{-- country_name[] — LocationController::countryStore() saves one
                         row per name with the shared status below. --}}
                    <div id="countryFields">
                        @foreach ($countryNames as $i => $countryName)
                            <div class="mst-field-card mst-repeat">
                                <div class="row g-3 align-items-end">
                                    <div class="col">
                                        <label for="countryName{{ $i }}" class="mst-form-label d-block">
                                            Country Name <span class="mst-req" aria-hidden="true">*</span>
                                        </label>
                                        <input type="text" id="countryName{{ $i }}" name="country_name[]"
                                               class="form-control mst-control @error('country_name.' . $i) is-invalid @enderror"
                                               placeholder="Country Name" maxlength="100"
                                               value="{{ $countryName }}" required aria-required="true">
                                        @error('country_name.' . $i)
                                            <span class="mst-field-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-auto mst-field-actions">
                                        <button type="button" class="mst-field-btn mst-field-btn--remove" aria-label="Remove this country">
                                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="mst-field-btn mst-field-btn--add" aria-label="Add another country">
                                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="row g-3 mt-1">
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
                        <button type="submit" class="btn mst-btn-submit px-4">Save</button>
                    </div>
                </div>
            </div>
        </form>
        <script type="text/x-mst-form-init">
            MstAdmin.repeatable({ container: $(root).find('#countryFields') });
        </script>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
@endpush
