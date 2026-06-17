@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Estate Block/Building - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Protocol</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.define-block-building.index') }}">Define Block/Building</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $item ? 'Edit' : 'Add' }} Estate Block/Building</li>
        </ol>
    </nav>

    <x-session_message />

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold mb-1">{{ $item ? 'Edit Estate Block/Building' : 'Add Estate Block/Building' }}</h1>
            <p class="text-muted small mb-4">{{ $item ? 'Update block/building.' : 'Please Add Estate Block/Building.' }}</p>
            <hr class="my-4">

            <form action="{{ $item ? route('admin.estate.define-block-building.update', $item->pk) : route('admin.estate.define-block-building.store') }}" method="POST">
                @csrf
                @if($item) @method('PUT') @endif

                <div class="mb-4">
                    <label for="block_name" class="form-label">Block Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control @error('block_name') is-invalid @enderror" id="block_name" name="block_name" value="{{ old('block_name', $item->block_name ?? '') }}" required maxlength="255" placeholder="Enter block/building name">
                        <span class="input-group-text" title="Information"><i class="bi bi-info-circle text-primary"></i></span>
                    </div>
                    @error('block_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('admin.estate.define-block-building.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
