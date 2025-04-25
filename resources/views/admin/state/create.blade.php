@extends('admin.layouts.master')

@section('title', 'State ')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="State" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">State</h4>
            <hr>
            <form action="{{ route('master.state.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="state_name" class="form-label">State Name</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="state_name" 
                                    name="state_name" 
                                    value="{{ old('state_name') }}" 
                                    required
                                >
                                @error('state_name')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="country_master_pk" class="form-label">Select Country</label>
                                <select 
                                    class="form-select" 
                                    id="country_master_pk" 
                                    name="country_master_pk" 
                                    required
                                >
                                    <option value="">-- Select Country --</option>
                                    @foreach($countries as $country)
                                        <option 
                                            value="{{ $country->pk }}" 
                                            {{ old('country_master_pk') == $country->pk ? 'selected' : '' }}
                                        >
                                            {{ $country->country_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country_master_pk')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a href="{{ route('master.state.index') }}" class="btn btn-secondary">Back</a>
                            </div>
                        </form>

        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection