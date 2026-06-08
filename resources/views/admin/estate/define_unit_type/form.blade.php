@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Unit Type - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Protocol</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.define-unit-type.index') }}">Define Unit Type</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $item ? 'Edit' : 'Add' }} Unit Type</li>
        </ol>
    </nav>

    <x-session_message />

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold mb-1">{{ $item ? 'Edit Unit Type' : 'Add Unit Type' }}</h1>
            <p class="text-muted small mb-4">{{ $item ? 'Update unit type.' : 'Please add the unit type.' }}</p>
            <hr class="my-4">

            <form action="{{ $item ? route('admin.estate.define-unit-type.update', $item->pk) : route('admin.estate.define-unit-type.store') }}" method="POST">
                @csrf
                @if($item) @method('PUT') @endif

                <div class="mb-4">
                    <label for="unit_type" class="form-label">Unit Type <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control @error('unit_type') is-invalid @enderror" id="unit_type" name="unit_type" value="{{ old('unit_type', $item->unit_type ?? '') }}" required maxlength="255" placeholder="Enter unit type">
                        <span class="input-group-text" title="Information"><i class="bi bi-info-circle text-primary"></i></span>
                    </div>
                    @error('unit_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('admin.estate.define-unit-type.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
