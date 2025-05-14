@extends('admin.layouts.master')

@section('title', 'District - Sargam | Lal Bahadur')

@section('content')

    <div class="container-fluid">
        <x-breadcrum title="District" />
        <x-session_message />

        <!-- start Vertical Steps Example -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Create District</h4>
                <hr>
                <form action="{{ route('master.district.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <!-- State Dropdown -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="state">State:</label>
                                <select class="form-select" id="state" name="state_master_pk" required>
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
                        </div>

                        <!-- District Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="district_name">District Name:</label>
                                <input type="text" class="form-control" id="district_name" name="district_name"
                                    value="{{ old('district_name') }}" required>
                                @error('district_name')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="mb-3">
                        <button class="btn btn-primary hstack gap-2 float-end" type="submit">
                            <i class="material-icons menu-icon">send</i> Submit
                        </button>
                    </div>
                </form>


            </div>
        </div>
        <!-- end Vertical Steps Example -->
    </div>


@endsection