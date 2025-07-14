@extends('admin.layouts.master')

@section('title', 'City - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">

    <x-breadcrum title="City" />
    <x-session_message />

    <!-- start Vertical Steps Example -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">Edit City</h4>
            <hr>
            <form action="{{ route('master.city.update', $city->pk) }}" method="POST">
                @csrf

                <div class="row">
                     <div class="col-6">
                        <div class="mb-3">
                            <label for="country_master_pk" class="form-label">Select Country</label>
                            <select class="form-select" id="country_master_pk" name="country_master_pk" required>
                                <option value="">-- Select Country --</option>
                                @foreach($countries as $country)
                                <option value="{{ $country->pk }}"
                                    {{ old('country_master_pk', $city->country_master_pk) == $country->pk ? 'selected' : '' }}>
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
                            <select name="state_master_pk" class="form-select" required>
                                <option value="">Select State</option>
                                @foreach($states as $state)
                                <option value="{{ $state->pk }}"
                                    {{ old('state_master_pk', $city->state_master_pk) == $state->pk ? 'selected' : '' }}>
                                    {{ $state->state_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('state_master_pk')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="district" class="form-label">District</label>
                            <select name="district_master_pk" class="form-select" required>
                                <option value="">Select District</option>
                                @foreach($districts as $district)
                                <option value="{{ $district->pk }}"
                                    {{ old('district_master_pk', $city->district_master_pk) == $district->pk ? 'selected' : '' }}>
                                    {{ $district->district_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('district_master_pk')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="city_name" class="form-label">City Name</label>
                            <input type="text" name="city_name" class="form-control"
                                value="{{ old('city_name', $city->city_name) }}" required>
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
                                    Active
                                </option>
                                <option value="2"
                                    {{ (old('active_inactive', $city->active_inactive ?? 2) == 2) ? 'selected' : '' }}>
                                    Inactive
                                </option>
                            </select>
                            @error('active_inactive')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('master.city.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </form>

        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection