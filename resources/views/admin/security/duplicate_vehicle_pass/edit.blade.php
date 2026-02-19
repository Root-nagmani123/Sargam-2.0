@extends('admin.layouts.master')
@section('title', 'Edit Duplicate Vehicle Pass Request - Sargam')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Edit Duplicate Vehicle Pass Request"></x-breadcrum>

    <form action="{{ route('admin.security.duplicate_vehicle_pass.update', $req->id) }}" method="POST" enctype="multipart/form-data" class="needs-validation" id="dupVehPassForm" novalidate>
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">Request details</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="vehicle_number" class="form-label">Vehicle Number <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_number" id="vehicle_number" class="form-control" value="{{ old('vehicle_number', $req->vehicle_number) }}" placeholder="Enter Vehicle Number" required>
                        @error('vehicle_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="vehicle_pass_no" class="form-label">Vehicle Pass No: <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_pass_no" id="vehicle_pass_no" class="form-control" value="{{ old('vehicle_pass_no', $req->vehicle_pass_no) }}" placeholder="Vehicle Pass Number" required>
                        @error('vehicle_pass_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="id_card_number" class="form-label">Id Card Number <span class="text-danger">*</span></label>
                        <input type="text" name="id_card_number" id="id_card_number" class="form-control" value="{{ old('id_card_number', $req->id_card_number) }}" placeholder="Enter Id Card Number">
                        @error('id_card_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="emp_master_pk" class="form-label">Name <span class="text-danger">*</span></label>
                        <select name="emp_master_pk" id="emp_master_pk" class="form-select" required>
                            <option value="">-- Select Employee --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->pk }}"
                                    data-designation="{{ $emp->designation }}"
                                    data-department="{{ $emp->department }}"
                                    data-emp-id="{{ $emp->emp_id }}"
                                    {{ (string)old('emp_master_pk', $req->emp_master_pk) === (string)$emp->pk ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('emp_master_pk')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="designation" class="form-label">Designation <span class="text-danger">*</span></label>
                        <input type="text" name="designation" id="designation" class="form-control" value="{{ old('designation', $req->designation) }}" placeholder="Employee Designation" required>
                        @error('designation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                        <input type="text" name="department" id="department" class="form-control" value="{{ old('department', $req->department) }}" placeholder="Employee Department" required>
                        @error('department')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="vehicle_type" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                        <select name="vehicle_type" id="vehicle_type" class="form-select" required>
                            <option value="">-- Select --</option>
                            @foreach($vehicleTypes as $vt)
                                <option value="{{ $vt->pk }}" {{ (string)old('vehicle_type', $req->vehicle_type) === (string)$vt->pk ? 'selected' : '' }}>{{ $vt->vehicle_type }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date', $req->start_date?->format('Y-m-d')) }}" required>
                        @error('start_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date', $req->end_date?->format('Y-m-d')) }}" required>
                        @error('end_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="reason_for_duplicate" class="form-label">Reason For Duplicate Card <span class="text-danger">*</span></label>
                        <select name="reason_for_duplicate" id="reason_for_duplicate" class="form-select" required>
                            <option value="">-- Select --</option>
                            @foreach(\App\Models\DuplicateVehiclePassRequest::reasonOptions() as $val => $label)
                                <option value="{{ $val }}" {{ old('reason_for_duplicate', $req->reason_for_duplicate) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason_for_duplicate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            @foreach(\App\Models\DuplicateVehiclePassRequest::statusOptions() as $val => $label)
                                <option value="{{ $val }}" {{ old('status', $req->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="doc_upload" class="form-label">Upload Document</label>
                        <input type="file" name="doc_upload" id="doc_upload" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        @if($req->doc_upload)
                            <small class="text-muted">Current: <a href="{{ asset('storage/' . $req->doc_upload) }}" target="_blank">Download</a></small>
                        @else
                            <small class="text-muted">PDF or image (max 2MB)</small>
                        @endif
                        @error('doc_upload')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="2">{{ old('remarks', $req->remarks) }}</textarea>
                        @error('remarks')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success">Update</button>
                    <a href="{{ route('admin.security.duplicate_vehicle_pass.show', $req->id) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function() {
    var sel = document.getElementById('emp_master_pk');
    var designation = document.getElementById('designation');
    var department = document.getElementById('department');
    var idCard = document.getElementById('id_card_number');

    function fillFromEmployee() {
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) return;
        designation.value = opt.getAttribute('data-designation') || designation.value;
        department.value = opt.getAttribute('data-department') || department.value;
        if (idCard && !idCard.value) idCard.value = opt.getAttribute('data-emp-id') || '';
    }
    if (sel) { sel.addEventListener('change', fillFromEmployee); fillFromEmployee(); }

    document.getElementById('dupVehPassForm')?.addEventListener('submit', function() {
        var end = document.getElementById('end_date').value, start = document.getElementById('start_date').value;
        if (end && start && new Date(end) < new Date(start)) { alert('End Date must be on or after Start Date.'); return false; }
        return true;
    });
})();
</script>
@endpush
@endsection
