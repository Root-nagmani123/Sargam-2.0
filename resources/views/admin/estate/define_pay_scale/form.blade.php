@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Pay Scale - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Protocol</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.define-pay-scale.index') }}">Define Pay Scale</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $item ? 'Edit' : 'Add' }} Pay Scale</li>
        </ol>
    </nav>

    <x-session_message />

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold mb-1">{{ $item ? 'Edit Pay Scale' : 'Add Pay Scale' }}</h1>
            <p class="text-muted small mb-4">Used in Eligibility Unit Mapping.</p>
            <hr class="my-4">

            <form action="{{ $item ? route('admin.estate.define-pay-scale.update', $item->pk) : route('admin.estate.define-pay-scale.store') }}" method="POST">
                @csrf
                @if($item) @method('PUT') @endif

                <div class="mb-3">
                    <label for="pay_scale_range" class="form-label">Pay Scale Range <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('pay_scale_range') is-invalid @enderror" id="pay_scale_range" name="pay_scale_range" value="{{ old('pay_scale_range', $item->pay_scale_range ?? '') }}" required placeholder="e.g. 5200-20200">
                    @error('pay_scale_range')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="pay_scale_level" class="form-label">Pay Scale Level <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('pay_scale_level') is-invalid @enderror" id="pay_scale_level" name="pay_scale_level" value="{{ old('pay_scale_level', $item->pay_scale_level ?? '') }}" required placeholder="e.g. level-01, Level-13A">
                    @error('pay_scale_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label for="display_label" class="form-label">Display Label (optional)</label>
                    <input type="text" class="form-control @error('display_label') is-invalid @enderror" id="display_label" name="display_label" value="{{ old('display_label', $item->display_label ?? '') }}" placeholder="e.g. 5200-20200 (level-01)">
                    @error('display_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('admin.estate.define-pay-scale.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
