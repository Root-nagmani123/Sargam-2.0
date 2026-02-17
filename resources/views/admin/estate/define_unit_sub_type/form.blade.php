@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Unit Sub Type - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Protocol</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.define-unit-sub-type.index') }}">Define Unit Sub Type</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $item ? 'Edit' : 'Add' }} Unit Sub Type</li>
        </ol>
    </nav>

    <x-session_message />

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold mb-1">{{ $item ? 'Edit Unit Sub Type' : 'Add Unit Sub Type' }}</h1>
            <p class="text-muted small mb-4">{{ $item ? 'Update unit sub type.' : 'Please add the unit sub type.' }}</p>
            <hr class="my-4">

            <form action="{{ $item ? route('admin.estate.define-unit-sub-type.update', $item->pk) : route('admin.estate.define-unit-sub-type.store') }}" method="POST">
                @csrf
                @if($item) @method('PUT') @endif

                <div class="mb-4">
                    <label for="unit_sub_type" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control @error('unit_sub_type') is-invalid @enderror" id="unit_sub_type" name="unit_sub_type" value="{{ old('unit_sub_type', $item->unit_sub_type ?? '') }}" required maxlength="255" placeholder="Enter unit sub type">
                        <span class="input-group-text" title="Information"><i class="bi bi-info-circle text-primary"></i></span>
                    </div>
                    @error('unit_sub_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('admin.estate.define-unit-sub-type.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
