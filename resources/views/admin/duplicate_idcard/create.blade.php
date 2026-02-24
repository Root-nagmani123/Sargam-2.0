@extends('admin.layouts.master')
@section('title', 'Request For Duplicate ID Card - Sargam')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Request For Duplicate ID Card"></x-breadcrum>

    <form action="{{ route('admin.duplicate_idcard.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        @csrf

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row g-3 align-items-start">
                    <div class="col-md-4">
                        <label class="form-label">ID Card Type <span class="text-danger">*</span></label>
                        <select name="id_card_type" class="form-select" required>
                            <option value="">--Select--</option>
                            <option value="Permanent" {{ old('id_card_type')==='Permanent' ? 'selected':'' }}>Permanent</option>
                            <option value="Contractual" {{ old('id_card_type')==='Contractual' ? 'selected':'' }}>Contractual</option>
                            <option value="Family" {{ old('id_card_type')==='Family' ? 'selected':'' }}>Family</option>
                        </select>
                        @error('id_card_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">ID Card Number <span class="text-danger">*</span></label>
                        <input type="text" name="id_card_number" class="form-control" value="{{ old('id_card_number') }}" placeholder="Enter ID Card Number" required>
                        @error('id_card_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Upload Photo <span class="text-danger">*</span></label>
                        <input type="file" name="photo" class="form-control" accept="image/*" required>
                        @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">ID Proof <span class="text-danger">*</span></label>
                        <select name="id_proof" class="form-select" required>
                            @foreach($idProofOptions as $k => $label)
                                <option value="{{ $k }}" {{ (int)old('id_proof',1)===(int)$k ? 'selected':'' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('id_proof')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Upload Aadhar Copy <span class="text-danger">*</span></label>
                        <input type="file" name="aadhar_doc" class="form-control" required>
                        @error('aadhar_doc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4"></div>

                    <div class="col-md-6">
                        <label class="form-label">Employee Name <span class="text-danger">*</span></label>
                        <input type="text" name="employee_name" class="form-control" value="{{ old('employee_name', $me?->first_name ? trim(($me->first_name ?? '').' '.($me->last_name ?? '')) : '') }}" required>
                        @error('employee_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Designation</label>
                        <input type="text" name="designation" class="form-control" value="{{ old('designation', $me?->designation?->designation_name ?? '') }}">
                        @error('designation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Date Of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $me?->dob ?? '') }}">
                        @error('date_of_birth')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-select" required>
                            <option value="">--Select--</option>
                            <option value="O+ve" {{ old('blood_group')==='O+ve'?'selected':'' }}>O+ve</option>
                            <option value="O+" {{ old('blood_group')==='O+'?'selected':'' }}>O+</option>
                            <option value="O-" {{ old('blood_group')==='O-'?'selected':'' }}>O-</option>
                            
                            <option value="A+" {{ old('blood_group')==='A+'?'selected':'' }}>A+</option>
                            <option value="A-" {{ old('blood_group')==='A-'?'selected':'' }}>A-</option>
                            <option value="B+" {{ old('blood_group')==='B+'?'selected':'' }}>B+</option>
                            <option value="B-" {{ old('blood_group')==='B-'?'selected':'' }}>B-</option>
                            <option value="AB+" {{ old('blood_group')==='AB+'?'selected':'' }}>AB+</option>
                            <option value="AB-" {{ old('blood_group')==='AB-'?'selected':'' }}>AB-</option>
                        </select>
                        @error('blood_group')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile_number" class="form-control" value="{{ old('mobile_number', $me?->mobile ?? '') }}">
                        @error('mobile_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Father Name</label>
                        <input type="text" name="father_name" class="form-control" value="{{ old('father_name') }}">
                        @error('father_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Reason for Applying Duplicate Card <span class="text-danger">*</span></label>
                        <select name="card_reason" id="card_reason_select" class="form-select" required>
                            <option value="">--Select--</option>
                            <option value="Damage Card" {{ old('card_reason')==='Damage Card'?'selected':'' }}>Damage Card</option>
                            <option value="Card Lost" {{ old('card_reason')==='Card Lost'?'selected':'' }}>Card Lost</option>
                            <option value="Service Extended" {{ old('card_reason')==='Service Extended'?'selected':'' }}>Service Extended</option>
                            <option value="Change in Name" {{ old('card_reason')==='Change in Name'?'selected':'' }}>Change in Name</option>
                            <option value="Designation Change" {{ old('card_reason')==='Designation Change'?'selected':'' }}>Designation Change</option>
                        </select>
                        @error('card_reason')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Card Lost - FIR Document -->
                    <div class="col-md-6" id="fir_doc_section" style="display: none;">
                        <label class="form-label">Upload FIR Copy / Document Proof <span class="text-danger">*</span></label>
                        <input type="file" name="fir_doc" class="form-control">
                        @error('fir_doc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Service Extended - Extension Proof -->
                    <div class="col-md-6" id="service_ext_section" style="display: none;">
                        <label class="form-label">Upload Service Extension / Renewal Proof <span class="text-danger">*</span></label>
                        <input type="file" name="service_ext" class="form-control">
                        @error('service_ext')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Change in Name - New Name & Name Proof -->
                    <div class="col-md-6" id="new_name_section" style="display: none;">
                        <label class="form-label">New Employee Name <span class="text-danger">*</span></label>
                        <input type="text" name="new_employee_name" class="form-control" value="{{ old('new_employee_name') }}" placeholder="Enter new name">
                        @error('new_employee_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6" id="name_proof_section" style="display: none;">
                        <label class="form-label">Upload Name Change Proof <span class="text-danger">*</span></label>
                        <input type="file" name="name_proof" class="form-control">
                        @error('name_proof')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Designation Change - Official Order -->
                    <div class="col-md-6" id="designation_order_section" style="display: none;">
                        <label class="form-label">Upload Official Order / Transfer Letter <span class="text-danger">*</span></label>
                        <input type="file" name="designation_order" class="form-control">
                        @error('designation_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">ID-Card Valid From</label>
                        <input type="date" name="card_valid_from" class="form-control" value="{{ old('card_valid_from') }}">
                        @error('card_valid_from')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ID-Card Valid Upto</label>
                        <input type="date" name="card_valid_to" class="form-control" value="{{ old('card_valid_to') }}">
                        @error('card_valid_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-success">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">save</i>
                            Save
                        </button>
                        <a href="{{ route('admin.duplicate_idcard.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.getElementById('card_reason_select').addEventListener('change', function() {
        // Hide all conditional sections first
        document.getElementById('fir_doc_section').style.display = 'none';
        document.getElementById('service_ext_section').style.display = 'none';
        document.getElementById('new_name_section').style.display = 'none';
        document.getElementById('name_proof_section').style.display = 'none';
        document.getElementById('designation_order_section').style.display = 'none';

        // Clear required attribute from all conditional fields
        document.querySelector('input[name="fir_doc"]').removeAttribute('required');
        document.querySelector('input[name="service_ext"]').removeAttribute('required');
        document.querySelector('input[name="new_employee_name"]').removeAttribute('required');
        document.querySelector('input[name="name_proof"]').removeAttribute('required');
        document.querySelector('input[name="designation_order"]').removeAttribute('required');

        // Show and set required for selected reason
        const reason = this.value;
        if (reason === 'Card Lost') {
            document.getElementById('fir_doc_section').style.display = 'block';
            document.querySelector('input[name="fir_doc"]').setAttribute('required', 'required');
        } else if (reason === 'Service Extended') {
            document.getElementById('service_ext_section').style.display = 'block';
            document.querySelector('input[name="service_ext"]').setAttribute('required', 'required');
        } else if (reason === 'Change in Name') {
            document.getElementById('new_name_section').style.display = 'block';
            document.getElementById('name_proof_section').style.display = 'block';
            document.querySelector('input[name="new_employee_name"]').setAttribute('required', 'required');
            document.querySelector('input[name="name_proof"]').setAttribute('required', 'required');
        } else if (reason === 'Designation Change') {
            document.getElementById('designation_order_section').style.display = 'block';
            document.querySelector('input[name="designation_order"]').setAttribute('required', 'required');
        }
    });

    // Trigger on page load if there's old data
    if (document.getElementById('card_reason_select').value) {
        document.getElementById('card_reason_select').dispatchEvent(new Event('change'));
    }
</script>
@endsection

