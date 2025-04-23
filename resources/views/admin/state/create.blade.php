@extends('admin.layouts.master')

@section('title', 'State - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Add State</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                State
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
            <h4 class="card-title mb-3">State</h4>
            <hr>
            <form action="{{ route('state.store') }}" method="POST">
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
                                <a href="{{ route('state.index') }}" class="btn btn-secondary">Back</a>
                            </div>
                        </form>

        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection