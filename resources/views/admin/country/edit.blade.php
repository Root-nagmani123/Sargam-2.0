@extends('admin.layouts.master')

@section('title', 'Country - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Country</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Country
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
            <h4 class="card-title mb-3">Edit Country</h4>
            <hr>
            <form method="POST" action="{{ route('master.country.update', $country->pk) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-sm-6">
                        <label for="country_name" class="form-label">Country Name :</label>
                        <div class="mb-3">
                            <input type="text" class="form-control" name="country_name"
                                value="{{ old('country_name', $country->country_name) }}" required>
                            @error('country_name')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                   
                    <div class="col-sm-4">
                        <div class="mb-3">
                            <label for="active_inactive" class="form-label">Status <span style="color:red;">*</span></label>
                            <select name="active_inactive" class="form-select" required>
                                <option value="1" {{ (old('active_inactive', $country->active_inactive ?? 1) == 1) ? 'selected' : '' }}>Active</option>
                                <option value="2" {{ (old('active_inactive', $country->active_inactive ?? 1) == 2) ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('active_inactive')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                        <i class="material-icons menu-icon">send</i> Update
                    </button>
                </div>
            </form>

        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection