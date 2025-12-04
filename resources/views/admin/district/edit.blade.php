@extends('admin.layouts.master')

@section('title', 'Edit District - Sargam | Lal Bahadur')

@section('setup_content')

    <div class="container-fluid">
        <x-breadcrum title="District" />
        <x-session_message />

        <!-- start Vertical Steps Example -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Edit District</h4>
                <hr>
                <form action="{{ route('master.district.update', $district->pk) }}" method="POST">
                    @csrf

                    <div class="row">
                        <!-- State Dropdown -->
                         <div class="col-6">
                        <div class="mb-3">
                            <label for="country_master_pk" class="form-label">Select Country</label>
                            <select class="form-select" id="country_master_pk" name="country_master_pk" required>
                                <option value="">-- Select Country --</option>
                                @foreach($countries as $country)
                                <option value="{{ $country->pk }}"
                                    {{ old('country_master_pk', $district->country_master_pk) == $country->pk ? 'selected' : '' }}>
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
                                <label class="form-label" for="state">State:</label>
                                <select class="form-select" id="state" name="state_master_pk" required>
                                    <option value="">Select State</option>
                                    @foreach($states as $state)
                                        <option value="{{ $state->pk }}" {{ $state->pk == old('state_master_pk', $district->state_master_pk) ? 'selected' : '' }}>
                                            {{ $state->state_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('state_master_pk')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- District Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="district_name">District Name:</label>
                                <input type="text" class="form-control" id="district_name" name="district_name"
                                    value="{{ old('district_name', $district->district_name) }}" required>
                                @error('district_name')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                        <div class="mb-3">
                            <label for="active_inactive" class="form-label">Status <span style="color:red;">*</span></label>
                            <select name="active_inactive" class="form-select" required>
                                <option value="1" {{ (old('active_inactive', $district->active_inactive ?? 1) == 1) ? 'selected' : '' }}>Active</option>
                                <option value="2" {{ (old('active_inactive', $district->active_inactive ?? 2) == 2) ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('active_inactive')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                    </div>

                    <hr>
                    <div class="mb-3">
                        <button class="btn btn-primary float-end" type="submit">Update</button>
                    </div>
                </form>


            </div>
        </div>
        <!-- end Vertical Steps Example -->
    </div>


@endsection