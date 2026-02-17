@extends('admin.layouts.master')

@section('title', 'Estate Request for Others - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Estate Request for Others</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="card shadow-sm" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row align-items-center mb-4">
                <div class="col-12 col-md-6">
                    <h2 class="mb-0">Estate Request for Others</h2>
                </div>
                <div class="col-12 col-md-6 mt-3 mt-md-0">
                    <div class="d-flex justify-content-md-end justify-content-start">
                        <a href="{{ route('admin.estate.add-other-estate-request') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Add Other Estate
                        </a>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <hr>
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table table-bordered table-hover align-middle', 'aria-describedby' => 'estate-request-caption']) !!}
                <div id="estate-request-caption" class="visually-hidden">Estate Request for Others list</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush
