@extends('admin.layouts.master')

@section('title', 'City - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="City" />
    <x-session_message />

    <!-- start Vertical Steps Example -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">Create City</h4>
            <hr>
            <form action="{{ route('master.city.store') }}" method="POST">
                @csrf
                <div class="row">
                     <div class="col-6">
                        <div class="mb-3">
                            <label for="country_master_pk" class="form-label">Select Country</label>
                            <select class="form-select" id="country_master_pk" name="country_master_pk" required>
                                <option value="">-- Select Country --</option>
                                @foreach($countries as $country)
                                <option value="{{ $country->pk }}"
                                    {{ old('country_master_pk') == $country->pk ? 'selected' : '' }}>
                                    {{ $country->country_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('country_master_pk')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="state" class="form-label">State</label>
                            <select name="state_master_pk" id="state_master_pk" class="form-select" required>
                            <option value="">Select State</option>
                        </select>
                            @error('state_master_pk')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="district" class="form-label">District</label>
                            <select name="district_master_pk" id="district_master_pk" class="form-select" required>
                                <option value="">Select District</option>
                            </select>
                            @error('district_master_pk')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="city_name" class="form-label">City Name</label>
                            <input type="text" name="city_name" class="form-control" value="{{ old('city_name') }}"
                                required>
                            @error('city_name')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="active_inactive" class="form-label">Status <span
                                    style="color:red;">*</span></label>
                            <select name="active_inactive" class="form-select" required>
                                <option value="1"
                                    {{ (old('active_inactive', $city->active_inactive ?? 1) == 1) ? 'selected' : '' }}>
                                    Active</option>
                                <option value="2"
                                    {{ (old('active_inactive', $city->active_inactive ?? 2) == 2) ? 'selected' : '' }}>
                                    Inactive</option>
                            </select>
                            @error('active_inactive')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <a href="{{ route('master.city.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </form>

        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection
@push('scripts')
<script>
    $(document).ready(function () {
        $('#country_master_pk').on('change', function () {
            let countryId = $(this).val();
            $('#state_master_pk').html('<option value="">Loading...</option>');
            $('#district_master_pk').html('<option value="">Select District</option>');

            if (countryId) {
                $.ajax({
                  url: "{{ route('master.city.getStates') }}",
                    type: "POST",
                    data: {
                        country_id: countryId,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (states) {
                        $('#state_master_pk').empty().append('<option value="">Select State</option>');
                        $.each(states, function (key, state) {
                            $('#state_master_pk').append('<option value="' + state.pk + '">' + state.state_name + '</option>');
                        });
                    }
                });
            }
        });

        $('#state_master_pk').on('change', function () {
            let stateId = $(this).val();
            $('#district_master_pk').html('<option value="">Loading...</option>');

            if (stateId) {
                $.ajax({
                    url: "{{ route('master.city.getDistricts') }}",
                    type: "POST",
                    data: {
                        state_id: stateId,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (districts) {
                        $('#district_master_pk').empty().append('<option value="">Select District</option>');
                        $.each(districts, function (key, district) {
                            $('#district_master_pk').append('<option value="' + district.pk + '">' + district.district_name + '</option>');
                        });
                    }
                });
            }
        });
    });
</script>
@endpush
