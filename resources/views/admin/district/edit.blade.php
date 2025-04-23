@extends('admin.layouts.master')

@section('title', 'District - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Edit District</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    District
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Edit District</h4>
            <hr>
            <form action="{{ route('district.update', $district->pk) }}" method="POST">
                @csrf
                @method('PUT') {{-- Update request ke liye PUT method use karein --}}

                <div class="row">
                    <!-- State Dropdown -->
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label" for="state">State:</label>
                            <select class="form-select" id="state" name="state_master_pk" required>
                                <option value="">Select State</option>
                                @foreach($states as $state)
                                <option value="{{ $state->Pk }}"
                                    {{ $state->Pk == old('state_master_pk', $district->state_master_pk) ? 'selected' : '' }}>
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
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label" for="district_name">District Name:</label>
                            <input type="text" class="form-control" id="district_name" name="district_name"
                                value="{{ old('district_name', $district->district_name) }}" required>
                            @error('district_name')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary float-end" type="submit">
                        <i class="material-icons menu-icon">save</i> Update
                    </button>
                </div>
            </form>


        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection