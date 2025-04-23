@extends('admin.layouts.master')

@section('title', 'City - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Create City</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                City
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif 

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Create City</h4>
            <hr>
            <form action="{{ route('city.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="state" class="form-label">State</label>
            <select name="state_master_pk" class="form-select" required>
                <option value="">Select State</option>
                @foreach($states as $state)
                    <option value="{{ $state->Pk }}">{{ $state->state_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="district" class="form-label">District</label>
            <select name="district_master_pk" class="form-select" required>
                <option value="">Select District</option>
                @foreach($districts as $district)
                    <option value="{{ $district->pk }}">{{ $district->district_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="city_name" class="form-label">City Name</label>
            <input type="text" name="city_name" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
        <a href="{{ route('city.index') }}" class="btn btn-secondary">Back</a>
    </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection