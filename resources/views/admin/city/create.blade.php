@extends('admin.layouts.master')

@section('title', 'City - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="City" />
    <x-session_message />

    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Create City</h4>
            <hr>
            <form action="{{ route('master.city.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="state" class="form-label">State</label>
                    <select name="state_master_pk" class="form-select" required>
                        <option value="">Select State</option>
                        @foreach($states as $state)
                        <option value="{{ $state->Pk }}" {{ old('state_master_pk') == $state->Pk ? 'selected' : '' }}>
                            {{ $state->state_name }}
                        </option>
                        @endforeach
                    </select>
                    @error('state_master_pk')
                    <p class="text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="district" class="form-label">District</label>
                    <select name="district_master_pk" class="form-select" required>
                        <option value="">Select District</option>
                        @foreach($districts as $district)
                        <option value="{{ $district->pk }}"
                            {{ old('district_master_pk') == $district->pk ? 'selected' : '' }}>
                            {{ $district->district_name }}
                        </option>
                        @endforeach
                    </select>
                    @error('district_master_pk')
                    <p class="text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="city_name" class="form-label">City Name</label>
                    <input type="text" name="city_name" class="form-control" value="{{ old('city_name') }}" required>
                    @error('city_name')
                    <p class="text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="{{ route('master.city.index') }}" class="btn btn-secondary">Back</a>
            </form>

        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection