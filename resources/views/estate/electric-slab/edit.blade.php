@extends('admin.layouts.master')

@section('title', 'Edit Electric Slab')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Edit Electric Slab" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="mb-3">Edit Electric Slab</h4>
            <hr>
            <form action="{{ route('estate.electric-slab.update', $electricSlab->pk) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="slab_name" class="form-label">Slab Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('slab_name') is-invalid @enderror" 
                                   id="slab_name" name="slab_name" value="{{ old('slab_name', $electricSlab->slab_name) }}" required>
                            @error('slab_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="units_from" class="form-label">Units From <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control @error('units_from') is-invalid @enderror" 
                                   id="units_from" name="units_from" value="{{ old('units_from', $electricSlab->units_from) }}" required>
                            @error('units_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="units_to" class="form-label">Units To <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control @error('units_to') is-invalid @enderror" 
                                   id="units_to" name="units_to" value="{{ old('units_to', $electricSlab->units_to) }}" required>
                            @error('units_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="rate_per_unit" class="form-label">Rate per Unit (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control @error('rate_per_unit') is-invalid @enderror" 
                                   id="rate_per_unit" name="rate_per_unit" value="{{ old('rate_per_unit', $electricSlab->rate_per_unit) }}" required>
                            @error('rate_per_unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fixed_charge" class="form-label">Fixed Charge (₹)</label>
                            <input type="number" step="0.01" class="form-control @error('fixed_charge') is-invalid @enderror" 
                                   id="fixed_charge" name="fixed_charge" value="{{ old('fixed_charge', $electricSlab->fixed_charge) }}">
                            @error('fixed_charge')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="effective_from" class="form-label">Effective From <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('effective_from') is-invalid @enderror" 
                                   id="effective_from" name="effective_from" 
                                   value="{{ old('effective_from', $electricSlab->effective_from ? $electricSlab->effective_from->format('Y-m-d') : '') }}" required>
                            @error('effective_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="effective_to" class="form-label">Effective To</label>
                            <input type="date" class="form-control @error('effective_to') is-invalid @enderror" 
                                   id="effective_to" name="effective_to" 
                                   value="{{ old('effective_to', $electricSlab->effective_to ? $electricSlab->effective_to->format('Y-m-d') : '') }}">
                            @error('effective_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Update</button>
                        <a href="{{ route('estate.electric-slab.index') }}" class="btn btn-secondary"><i class="ti ti-x"></i> Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
