@extends('admin.layouts.master')

@section('title', 'Add Other Estate Request - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Request for Others</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add Other Estate Request</li>
        </ol>
    </nav>

    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Add Other Estate Request</h2>
        <a href="{{ route('admin.estate.request-for-others') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <!-- Form Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted mb-4">Please Other Estate Request</p>
            <hr class="mb-4">

            <form>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employee_name" class="form-label">Employee Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="employee_name" name="employee_name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="father_name" class="form-label">Father Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="father_name" name="father_name" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="section" name="section" required>
                    </div>
                    <div class="col-md-6">
                        <label for="doj_academy" class="form-label">DOJ in Academy <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="doj_academy" name="doj_academy" required>
                            <span class="input-group-text">
                                <i class="bi bi-calendar"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-2"></i>Save
                    </button>
                    <a href="{{ route('admin.estate.request-for-others') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
