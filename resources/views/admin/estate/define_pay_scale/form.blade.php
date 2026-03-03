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

                <div class="mb-4">
                    <label for="salary_grade" class="form-label">Salary Grade / Pay Scale <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('salary_grade') is-invalid @enderror" id="salary_grade" name="salary_grade" value="{{ old('salary_grade', $item->salary_grade ?? '') }}" required placeholder="e.g. 5200-20200 (level-01)">
                    @error('salary_grade')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
