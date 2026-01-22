@extends('admin.layouts.master')

@section('title', 'Edit Area')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Edit Area" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="mb-3">Edit Area</h4>
            <hr>
            <form action="{{ route('estate.area.update', $area->pk) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="estate_campus_master_pk" class="form-label">Campus <span class="text-danger">*</span></label>
                            <select class="form-select @error('estate_campus_master_pk') is-invalid @enderror" 
                                    id="estate_campus_master_pk" name="estate_campus_master_pk" required>
                                <option value="">Select Campus</option>
                                @foreach($campuses as $campus)
                                    <option value="{{ $campus->pk }}" {{ old('estate_campus_master_pk', $area->estate_campus_master_pk) == $campus->pk ? 'selected' : '' }}>
                                        {{ $campus->campus_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('estate_campus_master_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="area_name" class="form-label">Area Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('area_name') is-invalid @enderror" 
                                   id="area_name" name="area_name" value="{{ old('area_name', $area->area_name) }}" required>
                            @error('area_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $area->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy"></i> Update
                        </button>
                        <a href="{{ route('estate.area.index') }}" class="btn btn-secondary">
                            <i class="ti ti-x"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
