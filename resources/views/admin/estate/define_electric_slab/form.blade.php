@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Electric Slab - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Protocol</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.define-electric-slab.index') }}">Define Electric Slab</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $item ? 'Edit' : 'Add' }} Electric Slab</li>
        </ol>
    </nav>

    <x-session_message />

    <div class="card shadow-sm border-0 border-start border-primary border-4">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold mb-1">{{ $item ? 'Edit Electric Slab' : 'Add Electric Slab' }}</h1>
            <p class="text-muted small mb-4">{{ $item ? 'Update unit range and rate per unit.' : 'Enter unit range and rate per unit.' }}</p>
            <hr class="my-4">

            <form action="{{ $item ? route('admin.estate.define-electric-slab.update', $item->pk) : route('admin.estate.define-electric-slab.store') }}" method="POST">
                @csrf
                @if($item) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="start_unit_range" class="form-label">Start unit range <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('start_unit_range') is-invalid @enderror" id="start_unit_range" name="start_unit_range" value="{{ old('start_unit_range', $item->start_unit_range ?? '') }}" min="0" required placeholder="e.g. 1">
                        @error('start_unit_range')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="end_unit_range" class="form-label">End unit range <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('end_unit_range') is-invalid @enderror" id="end_unit_range" name="end_unit_range" value="{{ old('end_unit_range', $item->end_unit_range ?? '') }}" min="0" required placeholder="e.g. 100">
                        @error('end_unit_range')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-3">
                    <label for="rate_per_unit" class="form-label">Rate per unit <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control @error('rate_per_unit') is-invalid @enderror" id="rate_per_unit" name="rate_per_unit" value="{{ old('rate_per_unit', $item->rate_per_unit ?? '') }}" required placeholder="e.g. 1.90">
                    @error('rate_per_unit')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="mt-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="house" id="house" value="1" {{ old('house', $item->house ?? 0) ? 'checked' : '' }}>
                        <label class="form-check-label" for="house">House</label>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i> Save
                    </button>
                    <a href="{{ route('admin.estate.define-electric-slab.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
