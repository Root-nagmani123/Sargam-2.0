@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Estate Electric Slab - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="{{ $item ? 'Edit' : 'Add' }} Estate Electric Slab" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold text-dark mb-1">{{ $item ? 'Edit' : 'Add' }} Estate Electric Slab</h1>
            <p class="text-muted small mb-4">Please {{ $item ? 'update' : 'add' }} the Estate Electric Slab.</p>

            <form action="{{ $item ? route('admin.estate.define-electric-slab.update', $item->pk) : route('admin.estate.define-electric-slab.store') }}" method="POST" id="formElectricSlab">
                @csrf
                @if($item) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="start_unit_range" class="form-label">Start Unit Range <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('start_unit_range') is-invalid @enderror" id="start_unit_range" name="start_unit_range" value="{{ old('start_unit_range', $item->start_unit_range ?? '') }}" min="0" step="1" required>
                        @error('start_unit_range')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="end_unit_range" class="form-label">End Unit Range <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('end_unit_range') is-invalid @enderror" id="end_unit_range" name="end_unit_range" value="{{ old('end_unit_range', $item->end_unit_range ?? '') }}" min="0" step="1" required>
                        @error('end_unit_range')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="rate_per_unit" class="form-label">Rate Per Unit <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('rate_per_unit') is-invalid @enderror" id="rate_per_unit" name="rate_per_unit" value="{{ old('rate_per_unit', $item->rate_per_unit ?? '') }}" min="0" step="0.01" required>
                        @error('rate_per_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="estate_unit_type_master_pk" class="form-label">Merge with House <span class="text-danger">*</span></label>
                        <select class="form-select @error('estate_unit_type_master_pk') is-invalid @enderror" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                            <option value="">— Select —</option>
                            @foreach($unitTypes ?? [] as $pk => $name)
                                <option value="{{ $pk }}" {{ (string) old('estate_unit_type_master_pk', $item->estate_unit_type_master_pk ?? '') === (string) $pk ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('estate_unit_type_master_pk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i> Save
                    </button>
                    <a href="{{ route('admin.estate.define-electric-slab.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<style>
    .select2-container--open { z-index: 1060; } /* sirf khula dropdown modal ke upar; closed widget normal flow me (modal ke peeche) */
    .select2-container--default .select2-selection--single { min-height: calc(1.5em + 0.75rem + 2px); display: flex; align-items: center; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5; padding-left: 0.25rem; }
</style>
@endpush

@push('scripts')
{{-- Select2 JS globally footer (admin.layouts.footer) se load hoti hai; yahan include ki zaroorat nahi. --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $.fn.select2 === 'undefined') return;
    var ids = ['estate_unit_type_master_pk'];
    ids.forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        if ($(el).data('select2')) { try { $(el).select2('destroy'); } catch (e) {} }
        $(el).select2({ placeholder: '— Select —', allowClear: false, width: '100%' });
    });
});
</script>
@endpush
