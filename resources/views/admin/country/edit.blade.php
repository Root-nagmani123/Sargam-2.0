@extends('admin.layouts.master')

@section('title', 'Country - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Edit Country</h4>
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
            <h4 class="card-title mb-3">Country</h4>
            <hr>
            <form method="POST" action="{{ route('country.update', $country->pk) }}">
            @csrf
            @method('PUT')
                <div class="row">
                    <div id="country_fields" class="my-2"></div>
                    <div class="row" id="country_fields">
                        <div class="col-sm-10">
                            <label for="Schoolname" class="form-label">Country Name :</label>
                            <div class="mb-3">
                            <input type="text" class="form-control" name="country_name" value="{{ $country->country_name }}" required>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label for="Schoolname" class="form-label"></label>
                            <!-- <div class="mb-3">
                                <button onclick="country_fields();" class="btn btn-success fw-medium" type="button">
                                    <i class="material-icons menu-icon">add</i>
                                </button>
                            </div> -->
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                    <i class="material-icons menu-icon">send</i>
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection