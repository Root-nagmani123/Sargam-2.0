@extends('admin.layouts.master')

@section('title', 'Edit Registration')

@section('content')
<div class="container-fluid">
    <div class="card card-body py-3 mb-4">
        <div class="row align-items-center">
            <div class="col-12 d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-0">Edit Registration</h4>
                <nav aria-label="breadcrumb" class="ms-auto">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item d-flex align-items-center">
                            <a href="{{ route('admin.registration.index') }}" class="text-muted text-decoration-none d-flex">
                                <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <span class="badge fw-medium fs-6 bg-warning-subtle text-warning">Edit Registration</span>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.registration.update', $registration->pk) }}" method="POST" novalidate>
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" id="first_name" name="first_name"
                            class="form-control @error('first_name') is-invalid @enderror"
                            value="{{ old('first_name', $registration->first_name) }}" required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="middle_name" class="form-label">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name"
                            class="form-control @error('middle_name') is-invalid @enderror"
                            value="{{ old('middle_name', $registration->middle_name) }}">
                        @error('middle_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" id="last_name" name="last_name"
                            class="form-control @error('last_name') is-invalid @enderror"
                            value="{{ old('last_name', $registration->last_name) }}" required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $registration->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="contact_no" class="form-label">Contact No</label>
                        <input type="text" id="contact_no" name="contact_no"
                            class="form-control @error('contact_no') is-invalid @enderror"
                            value="{{ old('contact_no', $registration->contact_no) }}" required>
                        @error('contact_no')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="rank" class="form-label">Rank</label>
                        <input type="text" id="rank" name="rank"
                            class="form-control @error('rank') is-invalid @enderror"
                            value="{{ old('rank', $registration->rank) }}">
                        @error('rank')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="web_auth" class="form-label">Web Auth</label>
                        <input type="text" id="web_auth" name="web_auth"
                            class="form-control @error('web_auth') is-invalid @enderror"
                            value="{{ old('web_auth', $registration->web_auth) }}">
                        @error('web_auth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr class="mt-4">

                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.registration.index') }}" class="btn btn-secondary ">Cancel</a>
                    <button type="submit" class="btn btn-warning ms-2">
                         Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
