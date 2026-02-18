@extends('admin.layouts.master')

@section('title', 'Estate Possession View - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
   <x-breadcrum title="Estate Possession View"></x-breadcrum>
    <div class="card p-4">
        <div class="card-body rounded-3" style="border: 1px solid #D1D5DC;">
            <p class="mb-4" style="color: #4D4D4D;font-weight: 500;font-size: 16px;">Please add Request Details</p>
            <form method="POST" action="{{ route('admin.estate.possession-view.store') }}" id="possessionForm">
                @csrf
                @if(isset($record) && $record)
                    <input type="hidden" name="id" value="{{ $record->pk }}">
                @endif

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="requester_name" class="form-label">Requester Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="requester_name" name="requester_name" required>
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Requester Name
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="request_id" class="form-label">Request ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="request_id" name="request_id" value="1234" required>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Request ID
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                        <select class="form-select" id="section" name="section" required>
                            <option value="own_id_card">Own ID Card</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Section
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label for="estate_campus_master_pk" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_campus_master_pk" name="estate_campus_master_pk" required>
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Estate Name
                        </small>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="estate_unit_type_master_pk" class="form-label">Unit Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                            <option value="">Select</option>
                            @foreach($unitTypes as $ut)
                                <option value="{{ $ut->pk }}" {{ (isset($record) && $record->estate_unit_type_master_pk == $ut->pk) || old('estate_unit_type_master_pk') == $ut->pk ? 'selected' : '' }}>{{ $ut->unit_type }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit Type
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label for="estate_block_master_pk" class="form-label">Building Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_block_master_pk" name="estate_block_master_pk" required>
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Select Building Name</small>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="estate_unit_sub_type_master_pk" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_unit_sub_type_master_pk" name="estate_unit_sub_type_master_pk" required>
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit Sub Type
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label for="estate_house_master_pk" class="form-label">House No. <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_house_master_pk" name="estate_house_master_pk" required>
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Select House No.</small>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="allotment_date" class="form-label">Allotment Date</label>
                        <input type="date" class="form-control" id="allotment_date" name="allotment_date" value="{{ old('allotment_date', isset($record) && $record->allotment_date ? $record->allotment_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="possession_date_oth" class="form-label">Possession Date</label>
                        <input type="date" class="form-control" id="possession_date_oth" name="possession_date_oth" value="{{ old('possession_date_oth', isset($record) && $record->possession_date_oth ? $record->possession_date_oth->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label for="meter_reading_oth" class="form-label">Electric Meter Reading</label>
                        <input type="number" class="form-control" id="meter_reading_oth" name="meter_reading_oth" value="{{ old('meter_reading_oth', isset($record) ? $record->meter_reading_oth : '') }}" min="0">
                        <small class="text-muted"><i class="bi bi-info-circle"></i> Electric Meter Reading</small>
                    </div>

                </div>
                <div class="alert alert-danger mb-4">
                    <small>*Required Fields: All marked fields are mandatory for registration</small>
                </div>
                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save
                    </button>
                    <a href="{{ route('admin.estate.possession-for-others') }}" class="btn btn-outline-primary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
