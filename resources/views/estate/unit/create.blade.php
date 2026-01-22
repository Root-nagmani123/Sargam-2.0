@extends('admin.layouts.master')

@section('title', 'Add Unit')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Add Unit" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="mb-3">Add New Unit</h4>
            <hr>
            <form action="{{ route('estate.unit.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="estate_campus_master_pk" class="form-label">Campus <span class="text-danger">*</span></label>
                            <select class="form-select @error('estate_campus_master_pk') is-invalid @enderror" 
                                    id="estate_campus_master_pk" name="estate_campus_master_pk" required>
                                <option value="">Select Campus</option>
                                @foreach($campuses as $campus)
                                    <option value="{{ $campus->pk }}" {{ old('estate_campus_master_pk') == $campus->pk ? 'selected' : '' }}>
                                        {{ $campus->campus_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('estate_campus_master_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="estate_area_master_pk" class="form-label">Area <span class="text-danger">*</span></label>
                            <select class="form-select @error('estate_area_master_pk') is-invalid @enderror" 
                                    id="estate_area_master_pk" name="estate_area_master_pk" required>
                                <option value="">Select Area</option>
                            </select>
                            @error('estate_area_master_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="estate_block_master_pk" class="form-label">Block <span class="text-danger">*</span></label>
                            <select class="form-select @error('estate_block_master_pk') is-invalid @enderror" 
                                    id="estate_block_master_pk" name="estate_block_master_pk" required>
                                <option value="">Select Block</option>
                            </select>
                            @error('estate_block_master_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="estate_unit_type_master_pk" class="form-label">Unit Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('estate_unit_type_master_pk') is-invalid @enderror" 
                                    id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                                <option value="">Select Unit Type</option>
                                @foreach($unitTypes as $unitType)
                                    <option value="{{ $unitType->pk }}" {{ old('estate_unit_type_master_pk') == $unitType->pk ? 'selected' : '' }}>
                                        {{ $unitType->unit_type }}
                                    </option>
                                @endforeach
                            </select>
                            @error('estate_unit_type_master_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="unit_name" class="form-label">Unit Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('unit_name') is-invalid @enderror" 
                                   id="unit_name" name="unit_name" value="{{ old('unit_name') }}" required>
                            @error('unit_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="house_address" class="form-label">House Address</label>
                            <input type="text" class="form-control @error('house_address') is-invalid @enderror" 
                                   id="house_address" name="house_address" value="{{ old('house_address') }}">
                            @error('house_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" 
                                   id="capacity" name="capacity" value="{{ old('capacity', 0) }}">
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control @error('quantity') is-invalid @enderror" 
                                   id="quantity" name="quantity" value="{{ old('quantity', 1) }}">
                            @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="estate_value" class="form-label">Estate Value (₹)</label>
                            <input type="number" step="0.01" class="form-control @error('estate_value') is-invalid @enderror" 
                                   id="estate_value" name="estate_value" value="{{ old('estate_value') }}">
                            @error('estate_value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="rent" class="form-label">Rent (₹)</label>
                            <input type="number" step="0.01" class="form-control @error('rent') is-invalid @enderror" 
                                   id="rent" name="rent" value="{{ old('rent') }}">
                            @error('rent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_rent_applicable" 
                                   name="is_rent_applicable" value="1" {{ old('is_rent_applicable', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_rent_applicable">
                                Is Rent Applicable
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" 
                                   name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                        <a href="{{ route('estate.unit.index') }}" class="btn btn-secondary"><i class="ti ti-x"></i> Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#estate_campus_master_pk').on('change', function() {
        var campusId = $(this).val();
        $('#estate_area_master_pk').html('<option value="">Select Area</option>');
        $('#estate_block_master_pk').html('<option value="">Select Block</option>');
        
        if(campusId) {
            $.ajax({
                url: "{{ route('estate.area.index') }}?campus_fk=" + campusId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $.each(data.data, function(key, value) {
                        $('#estate_area_master_pk').append('<option value="'+ value.pk +'">'+ value.area_name +'</option>');
                    });
                }
            });
        }
    });
    
    $('#estate_area_master_pk').on('change', function() {
        var areaId = $(this).val();
        $('#estate_block_master_pk').html('<option value="">Select Block</option>');
        
        if(areaId) {
            $.ajax({
                url: "{{ route('estate.block.index') }}?area_fk=" + areaId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $.each(data.data, function(key, value) {
                        $('#estate_block_master_pk').append('<option value="'+ value.pk +'">'+ value.block_name +'</option>');
                    });
                }
            });
        }
    });
});
</script>
@endpush
