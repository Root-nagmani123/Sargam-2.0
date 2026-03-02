@extends('admin.layouts.master')
@section('title', 'Request For Duplicate ID Card - Sargam')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="{{ isset($edit_id) ? 'Edit Duplicate ID Card Request' : 'Request For Duplicate ID Card' }}"></x-breadcrum>

    <form action="{{ isset($edit_id) ? route('admin.duplicate_idcard.update', $edit_id) : route('admin.duplicate_idcard.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        @csrf
        @if(isset($edit_id))
            @method('POST')
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if(isset($edit_id))
                    <div class="alert alert-info mb-4" role="alert">
                        <strong>Note:</strong> In edit mode, only "Reason for Duplicate Card" and supporting documents can be modified. Other fields are read-only.
                    </div>
                @endif

                <div class="row g-3 align-items-start">
                    <div class="col-md-4">
                        <label class="form-label">ID Card Type <span class="text-danger">*</span></label>
                        <select name="id_card_type" id="id_card_type" class="form-select" {{ isset($edit_id) ? 'disabled' : 'required' }}>
                            <option value="">--Select--</option>
                            <option value="Permanent" {{ old('id_card_type', $data['id_card_type'] ?? '')==='Permanent' ? 'selected':'' }}>Permanent</option>
                            <option value="Contractual" {{ old('id_card_type', $data['id_card_type'] ?? '')==='Contractual' ? 'selected':'' }}>Contractual</option>
                            <option value="Family" {{ old('id_card_type', $data['id_card_type'] ?? '')==='Family' ? 'selected':'' }}>Family</option>
                        </select>
                        @error('id_card_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">ID Card Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="id_card_number" id="id_card_number" class="form-control" value="{{ old('id_card_number', $data['id_card_number'] ?? '') }}" placeholder="Enter ID Card Number" {{ isset($edit_id) ? 'readonly' : 'required' }}>
                            @if(!isset($edit_id))
                                <button type="button" class="btn btn-outline-primary" id="btnFetchByCard">Fetch</button>
                            @endif
                        </div>
                        @error('id_card_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <small class="text-muted d-block">Enter existing ID card number and click Fetch to auto-fill employee details.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Upload Photo <span class="text-danger">*</span></label>
                        @if(isset($edit_id))
                            <div class="form-control-plaintext">
                                @if($data['photo_path'] ?? null)
                                    <a href="{{ asset('storage/' . $data['photo_path']) }}" target="_blank" class="btn btn-sm btn-outline-primary">View Photo</a>
                                @else
                                    <span class="text-muted">No photo uploaded</span>
                                @endif
                            </div>
                        @else
                            <input type="file" name="photo" class="form-control" accept=".jpeg,.jpg,.png,.gif" required>
                            <small class="text-muted d-block">Allowed: JPG, PNG, GIF. Max size: 2 MB</small>
                        @endif
                        @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">ID Proof <span class="text-danger">*</span></label>
                        <select name="id_proof" class="form-select" {{ isset($edit_id) ? 'disabled' : 'required' }}>
                            @foreach($idProofOptions as $k => $label)
                                <option value="{{ $k }}" {{ (int)old('id_proof', $data['id_proof'] ?? 1)===(int)$k ? 'selected':'' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('id_proof')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Upload Aadhar Copy <span class="text-danger">*</span></label>
                        @if(isset($edit_id))
                            <div class="form-control-plaintext">
                                @if($existing_docs['aadhar_doc'] ?? null)
                                    <a href="{{ asset('storage/idcard/dup_docs/' . $existing_docs['aadhar_doc']) }}" target="_blank" class="btn btn-sm btn-outline-primary">View Document</a>
                                @else
                                    <span class="text-muted">No document uploaded</span>
                                @endif
                            </div>
                        @else
                            <input type="file" name="aadhar_doc" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png" required>
                            <small class="text-muted d-block">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @endif
                        @error('aadhar_doc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4"></div>

                    <div class="col-md-6">
                        <label class="form-label">Employee Name <span class="text-danger">*</span></label>
                        <input type="text" name="employee_name" id="employee_name" class="form-control" value="{{ old('employee_name', $data['employee_name'] ?? ($me?->first_name ? trim(($me->first_name ?? '').' '.($me->last_name ?? '')) : '')) }}" {{ isset($edit_id) ? 'readonly' : 'required' }}>
                        @error('employee_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Designation</label>
                        <input type="text" name="designation" id="designation" class="form-control" value="{{ old('designation', $data['designation'] ?? ($me?->designation?->designation_name ?? '')) }}" {{ isset($edit_id) ? 'readonly' : '' }}>
                        @error('designation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Date Of Birth</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" value="{{ old('date_of_birth', $data['date_of_birth'] ?? ($me?->dob ?? '')) }}" {{ isset($edit_id) ? 'readonly' : '' }}>
                        @error('date_of_birth')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" id="blood_group" class="form-select" {{ isset($edit_id) ? 'disabled' : 'required' }}>
                            <option value="">--Select--</option>
                            <option value="O+ve" {{ old('blood_group', $data['blood_group'] ?? '')==='O+ve'?'selected':'' }}>O+ve</option>
                            <option value="O+" {{ old('blood_group', $data['blood_group'] ?? '')==='O+'?'selected':'' }}>O+</option>
                            <option value="O-" {{ old('blood_group', $data['blood_group'] ?? '')==='O-'?'selected':'' }}>O-</option>
                            
                            <option value="A+" {{ old('blood_group', $data['blood_group'] ?? '')==='A+'?'selected':'' }}>A+</option>
                            <option value="A-" {{ old('blood_group', $data['blood_group'] ?? '')==='A-'?'selected':'' }}>A-</option>
                            <option value="B+" {{ old('blood_group', $data['blood_group'] ?? '')==='B+'?'selected':'' }}>B+</option>
                            <option value="B-" {{ old('blood_group', $data['blood_group'] ?? '')==='B-'?'selected':'' }}>B-</option>
                            <option value="AB+" {{ old('blood_group', $data['blood_group'] ?? '')==='AB+'?'selected':'' }}>AB+</option>
                            <option value="AB-" {{ old('blood_group', $data['blood_group'] ?? '')==='AB-'?'selected':'' }}>AB-</option>
                        </select>
                        @error('blood_group')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile_number" id="mobile_number" class="form-control" value="{{ old('mobile_number', $data['mobile_number'] ?? ($me?->mobile ?? '')) }}" {{ isset($edit_id) ? 'readonly' : '' }}>
                        @error('mobile_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Father Name</label>
                        <input type="text" name="father_name" id="father_name" class="form-control" value="{{ old('father_name', $data['father_name'] ?? '') }}" {{ isset($edit_id) ? 'readonly' : '' }}>
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

                    <!-- Damage Card - Damage Proof Photo -->
                    <div class="col-md-6" id="damage_doc_section" style="display: none;">
                        <label class="form-label">Upload Damage Proof / Photo <span class="text-danger">*</span></label>
                        <input type="file" name="damage_doc" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png">
                        <small class="text-muted d-block">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @error('damage_doc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Card Lost - FIR Document -->
                    <div class="col-md-6" id="fir_doc_section" style="display: none;">
                        <label class="form-label">Upload FIR Copy / Document Proof <span class="text-danger">*</span></label>
                        <input type="file" name="fir_doc" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png">
                        <small class="text-muted d-block">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @error('fir_doc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Service Extended - Extension Proof -->
                    <div class="col-md-6" id="service_ext_section" style="display: none;">
                        <label class="form-label">Upload Service Extension / Renewal Proof <span class="text-danger">*</span></label>
                        <input type="file" name="service_ext" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png">
                        <small class="text-muted d-block">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
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
                        <input type="file" name="name_proof" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png">
                        <small class="text-muted d-block">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @error('name_proof')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Designation Change - Official Order -->
                    <div class="col-md-6" id="designation_order_section" style="display: none;">
                        <label class="form-label">Upload Official Order / Transfer Letter <span class="text-danger">*</span></label>
                        <input type="file" name="designation_order" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png">
                        <small class="text-muted d-block">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @error('designation_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">ID-Card Valid From</label>
                        <input type="date" name="card_valid_from" id="card_valid_from" class="form-control" value="{{ old('card_valid_from') }}">
                        @error('card_valid_from')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ID-Card Valid Upto</label>
                        <input type="date" name="card_valid_to" id="card_valid_to" class="form-control" value="{{ old('card_valid_to') }}">
                        @error('card_valid_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-success">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">save</i>
                            {{ isset($edit_id) ? 'Update' : 'Save' }}
                        </button>
                        <a href="{{ route('admin.duplicate_idcard.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    console.log('📝 Duplicate ID Card Script Loading...');
    console.log('📝 Document Ready State:', document.readyState);

    function initDuplicatePrefetch() {
        const btnFetch = document.getElementById('btnFetchByCard');
        const cardInput = document.getElementById('id_card_number');
        const typeSelect = document.getElementById('id_card_type');
        if (!btnFetch || !cardInput || !typeSelect) {
            return;
        }

        function showToast(message, isError) {
            alert(message);
        }

        btnFetch.addEventListener('click', function () {
            const cardNo = cardInput.value.trim();
            const type = typeSelect.value || 'Permanent';
            if (!cardNo) {
                showToast('Please enter ID Card Number first.', true);
                cardInput.focus();
                return;
            }
            btnFetch.disabled = true;
            fetch("{{ route('admin.duplicate_idcard.lookup') }}?id_card_number=" + encodeURIComponent(cardNo) + "&id_card_type=" + encodeURIComponent(type), {
                headers: { 'Accept': 'application/json' }
            })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                .then(function (result) {
                    btnFetch.disabled = false;
                    if (!result.ok || !result.data || !result.data.success) {
                        const msg = (result.data && result.data.message) ? result.data.message : 'Could not fetch ID card details.';
                        showToast(msg, true);
                        return;
                    }
                    const d = result.data.data || {};
                    const setVal = function (id, value) {
                        const el = document.getElementById(id);
                        if (el && (el.tagName === 'INPUT' || el.tagName === 'SELECT')) {
                            el.value = value || '';
                        }
                    };
                    setVal('employee_name', d.employee_name || '');
                    setVal('designation', d.designation || '');
                    setVal('date_of_birth', d.date_of_birth || '');
                    setVal('blood_group', d.blood_group || '');
                    setVal('mobile_number', d.mobile_number || '');
                    setVal('father_name', d.father_name || '');
                    setVal('card_valid_from', d.card_valid_from || '');
                    setVal('card_valid_to', d.card_valid_to || '');
                    showToast('ID card details fetched successfully. Please select duplicate reason and upload required document.', false);
                })
                .catch(function () {
                    btnFetch.disabled = false;
                    showToast('Error while fetching ID card details. Please try again.', true);
                });
        });
    }

    function initCardReasonToggle() {
        console.log('🔧 initCardReasonToggle() called');
        
        const reasonSelect = document.getElementById('card_reason_select');
        console.log('🔍 reasonSelect element found:', reasonSelect ? 'YES ✅' : 'NO ❌');
        
        if (!reasonSelect) {
            console.error('❌ card_reason_select element not found!');
            return;
        }

        let lastValue = '';

        function toggleDocumentSections() {
            console.log('🔄 toggleDocumentSections() called');
            
            // Hide all conditional sections first
            const sections = {
                'damage_doc_section': null,
                'fir_doc_section': null,
                'service_ext_section': null,
                'new_name_section': null,
                'name_proof_section': null,
                'designation_order_section': null
            };

            // Hide all
            Object.keys(sections).forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });

            // Clear required attribute from all conditional fields
            const damageDoc = document.querySelector('input[name="damage_doc"]');
            const firDoc = document.querySelector('input[name="fir_doc"]');
            const serviceExt = document.querySelector('input[name="service_ext"]');
            const newEmpName = document.querySelector('input[name="new_employee_name"]');
            const nameProof = document.querySelector('input[name="name_proof"]');
            const designOrder = document.querySelector('input[name="designation_order"]');

            if (damageDoc) damageDoc.removeAttribute('required');
            if (firDoc) firDoc.removeAttribute('required');
            if (serviceExt) serviceExt.removeAttribute('required');
            if (newEmpName) newEmpName.removeAttribute('required');
            if (nameProof) nameProof.removeAttribute('required');
            if (designOrder) designOrder.removeAttribute('required');

            // Get current value
            const reason = reasonSelect.value;
            console.log('✅ Selected reason:', reason);
            
            // Show appropriate section based on reason
            if (reason === 'Damage Card') {
                console.log('🔴 Showing Damage Card section');
                const el = document.getElementById('damage_doc_section');
                if (el) {
                    el.style.display = 'block';
                    console.log('✅ damage_doc_section display set to block');
                }
                if (damageDoc) {
                    damageDoc.setAttribute('required', 'required');
                    damageDoc.focus();
                }
            } else if (reason === 'Card Lost') {
                console.log('🟠 Showing Card Lost section');
                const el = document.getElementById('fir_doc_section');
                if (el) el.style.display = 'block';
                if (firDoc) firDoc.setAttribute('required', 'required');
            } else if (reason === 'Service Extended') {
                console.log('🟡 Showing Service Extended section');
                const el = document.getElementById('service_ext_section');
                if (el) el.style.display = 'block';
                if (serviceExt) serviceExt.setAttribute('required', 'required');
            } else if (reason === 'Change in Name') {
                console.log('🟢 Showing Change in Name section');
                const el1 = document.getElementById('new_name_section');
                const el2 = document.getElementById('name_proof_section');
                if (el1) el1.style.display = 'block';
                if (el2) el2.style.display = 'block';
                if (newEmpName) newEmpName.setAttribute('required', 'required');
                if (nameProof) nameProof.setAttribute('required', 'required');
            } else if (reason === 'Designation Change') {
                console.log('🔵 Showing Designation Change section');
                const el = document.getElementById('designation_order_section');
                if (el) el.style.display = 'block';
                if (designOrder) designOrder.setAttribute('required', 'required');
            } else {
                console.log('⚪ No matching reason condition');
            }
        }

        // Attach normal event listeners
        reasonSelect.addEventListener('change', function(e) {
            console.log('🎯 CHANGE EVENT FIRED!');
            toggleDocumentSections();
        });

        reasonSelect.addEventListener('input', function(e) {
            console.log('🎯 INPUT EVENT FIRED!');
            toggleDocumentSections();
        });

        // POLLING APPROACH: Check for value changes every 100ms
        // This works even if events are blocked by other libraries
        console.log('📌 Starting polling to detect dropdown changes...');
        setInterval(function() {
            const currentValue = reasonSelect.value;
            if (currentValue !== lastValue) {
                console.log('🔄 POLLING DETECTED VALUE CHANGE:', lastValue, '→', currentValue);
                lastValue = currentValue;
                toggleDocumentSections();
            }
        }, 100);

        // Also check via MutationObserver for attribute changes
        console.log('📌 Setting up MutationObserver...');
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                    console.log('👁️ MutationObserver detected value attribute change');
                    toggleDocumentSections();
                }
            });
        });

        observer.observe(reasonSelect, {
            attributes: true,
            attributeFilter: ['value']
        });

        // Trigger on page load if there's a selected value
        console.log('🔍 Checking if reason already selected on page load...');
        if (reasonSelect.value) {
            console.log('✅ Initial value found:', reasonSelect.value);
            lastValue = reasonSelect.value;
            toggleDocumentSections();
        } else {
            console.log('⚪ No initial value selected');
        }
    }

    console.log('📍 Script execution point 1: registering DOMContentLoaded listener');
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('📍 DOMContentLoaded fired');
        initCardReasonToggle();
        initDuplicatePrefetch();
    });
    
    // Also try immediately in case DOM is already ready
    console.log('📍 Script execution point 2: checking readyState');
    if (document.readyState === 'loading') {
        console.log('📍 Document still loading, waiting for DOMContentLoaded');
    } else {
        console.log('📍 Document already loaded, calling initCardReasonToggle / initDuplicatePrefetch immediately');
        initCardReasonToggle();
        initDuplicatePrefetch();
    }
    
    console.log('📝 Script initialization complete!');
</script>
@endsection

