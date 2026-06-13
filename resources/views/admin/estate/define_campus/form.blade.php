@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Campus - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Protocol</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.define-campus.index') }}">Define Estate/Campus</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $item ? 'Edit' : 'Add' }} Campus</li>
        </ol>
    </nav>

    <x-session_message />

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold mb-1">{{ $item ? 'Edit Campus' : 'Add Campus' }}</h1>
            <p class="text-muted small mb-4">{{ $item ? 'Update campus details.' : 'Please add new campus.' }}</p>
            <hr class="my-4">

            <form action="{{ $item ? route('admin.estate.define-campus.update', $item->pk) : route('admin.estate.define-campus.store') }}" method="POST">
                @csrf
                @if($item) @method('PUT') @endif

                <div class="mb-3">
                    <label for="campus_name" class="form-label">Campus <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control @error('campus_name') is-invalid @enderror" id="campus_name" name="campus_name" value="{{ old('campus_name', $item->campus_name ?? '') }}" required maxlength="255" placeholder="Enter campus name">
                        <span class="input-group-text" title="Information"><i class="bi bi-info-circle text-primary"></i></span>
                    </div>
                    @error('campus_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Description</label>
                    <div class="input-group">
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Optional description">{{ old('description', $item->description ?? '') }}</textarea>
                        <span class="input-group-text align-items-start pt-3" title="Information"><i class="bi bi-info-circle text-primary"></i></span>
                    </div>
                    @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i> Save
                    </button>
                    <a href="{{ route('admin.estate.define-campus.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
