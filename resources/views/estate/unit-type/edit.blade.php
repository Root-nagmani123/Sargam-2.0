@extends('admin.layouts.master')

@section('title', 'Edit Unit Type')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Edit Unit Type" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="mb-3">Edit Unit Type</h4>
            <hr>
            <form action="{{ route('estate.unit-type.update', $unitType->pk) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="unit_type" class="form-label">Unit Type <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('unit_type') is-invalid @enderror" 
                                   id="unit_type" name="unit_type" value="{{ old('unit_type', $unitType->unit_type) }}" required>
                            @error('unit_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $unitType->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Update</button>
                        <a href="{{ route('estate.unit-type.index') }}" class="btn btn-secondary"><i class="ti ti-x"></i> Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
