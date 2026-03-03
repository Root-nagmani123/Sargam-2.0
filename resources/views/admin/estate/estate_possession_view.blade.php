@extends('admin.layouts.master')

@section('title', 'Estate Possession View - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.possession-for-others') }}">Estate Possession for Others</a></li>
            <li class="breadcrumb-item active" aria-current="page">Estate Possession View</li>
        </ol>
    </nav>

    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <a href="{{ route('admin.estate.possession-for-others') }}" class="text-decoration-none text-dark">
                <i class="bi bi-arrow-left me-2"></i>Estate Possession View
            </a>
        </h2>
    </div>

    <!-- Form Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Please add Request Details</h5>
        </div>
        <div class="card-body">
            <form>
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
                    <div class="col-md-6">
                        <label for="estate_name" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_name" name="estate_name" required>
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Estate Name
                        </small>
                    </div>
                    <div class="col-md-6">
                        <label for="unit_type" class="form-label">Unit Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit_type" name="unit_type" required>
                            <option value="administrative_officer">Administrative Officer</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit Type
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="building_name" class="form-label">Building Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="building_name" name="building_name" value="18/10/1983" required>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Building Name
                        </small>
                    </div>
                    <div class="col-md-6">
                        <label for="unit_sub_type" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit_sub_type" name="unit_sub_type" required>
                            <option value="05/09/2013">05/09/2013</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit Sub Type
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="house_no" class="form-label">House No. <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="house_no" name="house_no" value="9356753250" required>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select House No.
                        </small>
                    </div>
                    <div class="col-md-6">
                        <label for="allotment_date" class="form-label">Allotment Date <span class="text-danger">*</span></label>
                        <select class="form-select" id="allotment_date" name="allotment_date" required>
                            <option value="9356753250">9356753250</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Allotment Date
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="possession_date" class="form-label">Possession Date</label>
                        <input type="text" class="form-control" id="possession_date" name="possession_date" value="O+ve">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Possession Date
                        </small>
                    </div>
                    <div class="col-md-6">
                        <label for="electric_meter_reading" class="form-label">Electric Meter Reading <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="electric_meter_reading" name="electric_meter_reading" value="01/01/027" required>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Electric Meter Reading
                        </small>
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
