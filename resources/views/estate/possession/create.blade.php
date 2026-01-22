@extends('admin.layouts.master')

@section('title', 'Add Possession')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Add Possession" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="mb-3">Add New Possession</h4>
            <hr>
            <form action="{{ route('estate.possession.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="employee_master_pk" class="form-label">Employee <span class="text-danger">*</span></label>
                            <select class="form-select @error('employee_master_pk') is-invalid @enderror" 
                                    id="employee_master_pk" name="employee_master_pk" required>
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ old('employee_master_pk') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->name }} ({{ $employee->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_master_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="estate_unit_master_pk" class="form-label">Unit <span class="text-danger">*</span></label>
                            <select class="form-select @error('estate_unit_master_pk') is-invalid @enderror" 
                                    id="estate_unit_master_pk" name="estate_unit_master_pk" required>
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->pk }}" {{ old('estate_unit_master_pk') == $unit->pk ? 'selected' : '' }}>
                                        {{ $unit->unit_name }} - {{ $unit->campus->campus_name ?? '' }} / {{ $unit->area->area_name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('estate_unit_master_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="possession_date" class="form-label">Possession Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('possession_date') is-invalid @enderror" 
                                   id="possession_date" name="possession_date" value="{{ old('possession_date') }}" required>
                            @error('possession_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="licence_fee" class="form-label">License Fee (₹)</label>
                            <input type="number" step="0.01" class="form-control @error('licence_fee') is-invalid @enderror" 
                                   id="licence_fee" name="licence_fee" value="{{ old('licence_fee', 0) }}">
                            @error('licence_fee')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="water_charge" class="form-label">Water Charge (₹)</label>
                            <input type="number" step="0.01" class="form-control @error('water_charge') is-invalid @enderror" 
                                   id="water_charge" name="water_charge" value="{{ old('water_charge', 0) }}">
                            @error('water_charge')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="meter_no_one" class="form-label">Meter No. 1</label>
                            <input type="text" class="form-control @error('meter_no_one') is-invalid @enderror" 
                                   id="meter_no_one" name="meter_no_one" value="{{ old('meter_no_one') }}">
                            @error('meter_no_one')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="initial_reading_one" class="form-label">Initial Reading 1</label>
                            <input type="number" step="0.01" class="form-control @error('initial_reading_one') is-invalid @enderror" 
                                   id="initial_reading_one" name="initial_reading_one" value="{{ old('initial_reading_one', 0) }}">
                            @error('initial_reading_one')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="meter_no_two" class="form-label">Meter No. 2</label>
                            <input type="text" class="form-control @error('meter_no_two') is-invalid @enderror" 
                                   id="meter_no_two" name="meter_no_two" value="{{ old('meter_no_two') }}">
                            @error('meter_no_two')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="initial_reading_two" class="form-label">Initial Reading 2</label>
                            <input type="number" step="0.01" class="form-control @error('initial_reading_two') is-invalid @enderror" 
                                   id="initial_reading_two" name="initial_reading_two" value="{{ old('initial_reading_two', 0) }}">
                            @error('initial_reading_two')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control @error('remarks') is-invalid @enderror" 
                                      id="remarks" name="remarks" rows="3">{{ old('remarks') }}</textarea>
                            @error('remarks')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                        <a href="{{ route('estate.possession.index') }}" class="btn btn-secondary"><i class="ti ti-x"></i> Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
