@extends('admin.layouts.master')

@section('title', 'Add City')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // One value drives the Status select. The old markup defaulted each option
    // separately (?? 1 and ?? 2), so both were "selected" on Add and the browser
    // kept the last one — new cities silently defaulted to Inactive.
    // old() is an array when the field came back as name[]: keep the default, not a 500.
    $cityStatus = old('active_inactive', 1);
    $cityStatus = is_scalar($cityStatus) ? (string) $cityStatus : '1';
@endphp
<div class="container-fluid mst-page">
    <x-breadcrum title="Add City" />
    <x-session_message />

    {{-- Form root: the index opens this same form in a modal
         (public/js/master-admin.js openFormModal). Keep form-specific JS in
         the x-mst-form-init block inside it, bound to elements under root. --}}
    <div data-mst-form-root data-mst-form-title="Add City">
        <form action="{{ route('master.city.store') }}" method="POST">
            @csrf

            <div class="card mst-form-card">
                <div class="card-body">
                    <h2 class="mst-form-section-title h6">City Details</h2>

                    {{-- Country -> State -> District cascade: the script below fills
                         #state_master_pk and #district_master_pk over AJAX
                         (master.city.getStates / master.city.getDistricts). --}}
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
                                    <option value="{{ $country->pk }}" {{ old('country_master_pk') == $country->pk ? 'selected' : '' }}>
                                        {{ $country->country_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('country_master_pk')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="state_master_pk" class="mst-form-label d-block">
                                State <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="state_master_pk" id="state_master_pk"
                                    class="form-select mst-control mst-searchable @error('state_master_pk') is-invalid @enderror"
                                    data-placeholder="Select State" required aria-required="true">
                                <option value="">Select State</option>
                            </select>
                            @error('state_master_pk')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="district_master_pk" class="mst-form-label d-block">
                                District <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="district_master_pk" id="district_master_pk"
                                    class="form-select mst-control mst-searchable @error('district_master_pk') is-invalid @enderror"
                                    data-placeholder="Select District" required aria-required="true">
                                <option value="">Select District</option>
                            </select>
                            @error('district_master_pk')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="city_name" class="mst-form-label d-block">
                                City Name <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="city_name" name="city_name"
                                   class="form-control mst-control @error('city_name') is-invalid @enderror"
                                   value="{{ old('city_name') }}" placeholder="City Name"
                                   maxlength="100" required aria-required="true">
                            @error('city_name')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="cityStatus" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="active_inactive" id="cityStatus"
                                    class="form-select mst-control mst-searchable @error('active_inactive') is-invalid @enderror"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="1" @selected($cityStatus === '1')>Active</option>
                                <option value="2" @selected($cityStatus === '2')>Inactive</option>
                            </select>
                            @error('active_inactive')
                                <span class="mst-field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mst-form-footer">
                        <a href="{{ route('master.city.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                        <button type="submit" class="btn mst-btn-submit px-4">Save</button>
                    </div>
                </div>
            </div>
        </form>
        <script type="text/x-mst-form-init">
            var $state = $(root).find('#state_master_pk');
            var $district = $(root).find('#district_master_pk');

            // Rebuild a cascading select, then let Select2 repaint its selection
            // (it re-reads <option>s live but only redraws on change.select2).
            function fillSelect($select, placeholder, rows, valueKey, textKey) {
                $select.empty().append(new Option(placeholder, ''));
                $.each(rows || [], function (key, row) {
                    $select.append(new Option(row[textKey], row[valueKey]));
                });
                $select.trigger('change.select2');
            }

            function showLoading($select) {
                $select.empty().append(new Option('Loading...', ''));
                $select.trigger('change.select2');
            }

            // jQuery-bound on purpose: Select2 signals a pick with a jQuery change.
            $(root).find('#country_master_pk').on('change', function () {
                var countryId = $(this).val();
                fillSelect($district, 'Select District', [], 'pk', 'district_name');

                // Clearing the country used to leave "Loading..." in State forever.
                if (!countryId) {
                    fillSelect($state, 'Select State', [], 'pk', 'state_name');
                    return;
                }

                showLoading($state);
                $.ajax({
                    url: "{{ route('master.city.getStates') }}",
                    type: "POST",
                    data: {
                        country_id: countryId,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (states) {
                        fillSelect($state, 'Select State', states, 'pk', 'state_name');
                    }
                });
            });

            $state.on('change', function () {
                var stateId = $(this).val();

                if (!stateId) {
                    fillSelect($district, 'Select District', [], 'pk', 'district_name');
                    return;
                }

                showLoading($district);
                $.ajax({
                    url: "{{ route('master.city.getDistricts') }}",
                    type: "POST",
                    data: {
                        state_id: stateId,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (districts) {
                        fillSelect($district, 'Select District', districts, 'pk', 'district_name');
                    }
                });
            });
        </script>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
@endpush
