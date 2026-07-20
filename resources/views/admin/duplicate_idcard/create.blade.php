@extends('admin.layouts.master')
@section('title', 'Request For Duplicate ID Card')

@push('styles')
<style>
/* =====================================================================
   Duplicate ID Card request form — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   ===================================================================== */
.dupidc-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--ds-ink);
    margin: 0 0 var(--ds-space-3);
    padding-bottom: var(--ds-space-2);
    border-bottom: 1px solid var(--ds-line);
}
.dupidc-form .form-label {
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--ds-ink);
    margin-bottom: var(--ds-space-1);
    line-height: 1.35;
    display: block;
}
.dupidc-form .form-control,
.dupidc-form .form-select {
    min-height: 44px;
    border-radius: var(--ds-radius-2);
}
.dupidc-form textarea.form-control { min-height: 88px; resize: vertical; line-height: 1.5; }
.dupidc-form .form-control[readonly],
.dupidc-form .form-select:disabled {
    background: var(--bs-secondary-bg, #eef1f4);
    color: var(--ds-ink);
}
/* Field hint text stacks tightly under its input. */
.dupidc-form .form-hint { font-size: 0.75rem; line-height: 1.35; color: var(--ds-ink-muted, #667085); display: block; margin-top: 0.2rem; }
.dupidc-form-footer {
    margin-top: var(--ds-space-4);
    padding-top: var(--ds-space-3);
    border-top: 1px solid var(--ds-line);
}
</style>
@endpush

@section('content')
<div class="container-fluid duplicate-idcard-form-page py-3">
    <x-breadcrum title="{{ isset($edit_id) ? 'Edit Duplicate ID Card Request' : 'Request For Duplicate ID Card' }}"></x-breadcrum>
    <x-session_message />

    <div class="ds-card">
        <div class="ds-card-body">
            <form action="{{ isset($edit_id) ? route('admin.duplicate_idcard.update', $edit_id) : route('admin.duplicate_idcard.store') }}"
                  method="POST" enctype="multipart/form-data" class="needs-validation dupidc-form" novalidate>
                @csrf
                @if(isset($edit_id))
                    @method('POST')
                    <input type="hidden" id="dup_stored_payment_receipt" value="{{ !empty($existing_docs['payment_receipt'] ?? null) ? '1' : '0' }}" autocomplete="off">

                    <div class="alert alert-info mb-4" role="alert">
                        <strong>Note:</strong> In edit mode, only "Reason for Duplicate Card" and supporting documents can be modified. Other fields are read-only.
                    </div>
                @endif

                {{-- ============ ID Card Details ============ --}}
                <h6 class="dupidc-section-title">ID Card Details</h6>
                <div class="row g-3">
                    @php
                        $lockedType = $lockedIdCardType ?? null;
                        $selectedType = old('id_card_type', $data['id_card_type'] ?? ($lockedType ?: ''));
                    @endphp
                    <div class="col-md-4">
                        <label class="form-label">ID Card Type <span class="text-danger">*</span></label>
                        <select name="id_card_type" id="id_card_type" class="form-select" {{ isset($edit_id) ? 'disabled' : 'required' }}>
                            <option value="" disabled>--Select--</option>
                            <option value="Permanent" {{ $selectedType==='Permanent' ? 'selected':'' }} @if($lockedType === 'Contractual') disabled @endif>Permanent</option>
                            <option value="Contractual" {{ $selectedType==='Contractual' ? 'selected':'' }} @if($lockedType === 'Permanent') disabled @endif>Contractual</option>
                        </select>
                        @if($lockedType)
                            <small class="form-hint">Allowed type: <strong>{{ $lockedType }}</strong></small>
                        @endif
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
                        <small class="form-hint">For <strong>Permanent</strong>, your current ID card number is filled automatically when on file (you can change it). Otherwise enter the number and click <strong>Fetch</strong> to load details.</small>
                        @if(!empty($userDepartmentName))
                            <small class="form-hint">You may request a duplicate only for staff in <strong>your department</strong>: <strong>{{ $userDepartmentName }}</strong>. Another department’s card number will be rejected.</small>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">ID Proof <span class="text-danger">*</span></label>
                        <select name="id_proof" class="form-select" required>
                            @foreach($idProofOptions as $k => $label)
                                <option value="{{ $k }}" {{ (int)old('id_proof', $data['id_proof'] ?? 1)===(int)$k ? 'selected':'' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('id_proof')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Upload Photo <span class="text-danger">*</span></label>
                        @if(isset($edit_id))
                            @php
                                $photoPath = $data['photo_path'] ?? null;
                                $photoExists = $photoPath && \Storage::disk('public')->exists($photoPath);
                            @endphp
                            @if($photoExists)
                                <a href="{{ asset('storage/' . $photoPath) }}" target="_blank" class="btn btn-sm btn-outline-primary d-block mb-2">View Current Photo</a>
                            @else
                                <div class="form-hint mb-2">No file available in storage</div>
                            @endif
                            <input type="file" name="photo" class="form-control" accept=".jpeg,.jpg,.png,.gif">
                            <small class="form-hint">Leave empty to keep current. Allowed: JPG, PNG, GIF. Max size: 2 MB</small>
                        @else
                            <input type="file" name="photo" class="form-control" accept=".jpeg,.jpg,.png,.gif" required>
                            <small class="form-hint">Allowed: JPG, PNG, GIF. Max size: 2 MB</small>
                        @endif
                        @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Upload Aadhar Copy <span class="text-danger">*</span></label>
                        @if(isset($edit_id))
                            @php
                                $aadharDoc = $existing_docs['aadhar_doc'] ?? null;
                                $aadharPath = $aadharDoc ? 'idcard/dup_docs/' . $aadharDoc : null;
                                $aadharExists = $aadharPath && \Storage::disk('public')->exists($aadharPath);
                            @endphp
                            @if($aadharExists)
                                <a href="{{ asset('storage/' . $aadharPath) }}" target="_blank" class="btn btn-sm btn-outline-primary d-block mb-2">View Current Document</a>
                            @elseif($aadharDoc)
                                <div class="form-hint mb-2">No file available in storage</div>
                            @endif
                            <input type="file" name="aadhar_doc" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png">
                            <small class="form-hint">Leave empty to keep current. Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @else
                            <input type="file" name="aadhar_doc" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png" required>
                            <small class="form-hint">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @endif
                        @error('aadhar_doc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Contractual-specific fields: Section & Approval Authority --}}
                    @php
                        $sectionStored = old('section', $data['section'] ?? $userDepartmentName ?? '');
                        $approverStored = old('approval_authority', $data['approval_authority'] ?? '');
                    @endphp
                    <div class="col-md-4 contractual-only" style="display:none;">
                        <label class="form-label">Section</label>
                        <select id="section_contractual" class="form-select" @if(isset($edit_id)) name="" disabled @else name="section" @endif>
                            <option value="">--Select--</option>
                            @if(!empty($userDepartmentName))
                                <option value="{{ $userDepartmentName }}" {{ (string) $sectionStored === (string) $userDepartmentName ? 'selected' : '' }}>{{ $userDepartmentName }}</option>
                            @endif
                            @if($sectionStored !== '' && (string) $sectionStored !== (string) ($userDepartmentName ?? ''))
                                <option value="{{ $sectionStored }}" selected>{{ $sectionStored }}</option>
                            @endif
                        </select>
                        @if(isset($edit_id) && $sectionStored !== '')
                            <input type="hidden" name="section" value="{{ $sectionStored }}">
                        @endif
                        @error('section')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 contractual-only" style="display:none;">
                        <label class="form-label">Approval Authority <span class="text-danger">*</span></label>
                        <select id="approval_authority_contractual" class="form-select" @if(isset($edit_id)) name="" disabled @else name="approval_authority" @endif>
                            <option value="">--Select--</option>
                            @foreach($approvalAuthorityEmployees ?? [] as $emp)
                                @php $empName = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')); @endphp
                                <option value="{{ $emp->pk }}" {{ (string) $approverStored === (string) $emp->pk ? 'selected' : '' }}>
                                    {{ $empName }}{{ $emp->designation ? ' (' . $emp->designation->designation_name . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if(isset($edit_id) && $approverStored !== '' && $approverStored !== null)
                            <input type="hidden" name="approval_authority" value="{{ $approverStored }}">
                        @endif
                        @error('approval_authority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- ============ Employee Details ============ --}}
                <h6 class="dupidc-section-title mt-4">Employee Details</h6>
                <div class="row g-3">
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
                            @php $selBlood = old('blood_group', $data['blood_group'] ?? ''); @endphp
                            @foreach(['O+ve','O+','O-','A+','A-','B+','B-','AB+','AB-'] as $bg)
                                <option value="{{ $bg }}" {{ $selBlood === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                            @endforeach
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
                </div>

                {{-- ============ Duplicate Card Request ============ --}}
                <h6 class="dupidc-section-title mt-4">Duplicate Card Request</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Reason for Applying Duplicate Card <span class="text-danger">*</span></label>
                        <select name="card_reason" id="card_reason_select" class="form-select" required>
                            <option value="">--Select--</option>
                            @php $selReason = old('card_reason', $data['card_reason'] ?? ''); @endphp
                            @foreach(['Damage Card','Card Lost','Service Extended','Change in Name','Designation Change'] as $reasonOpt)
                                <option value="{{ $reasonOpt }}" {{ $selReason === $reasonOpt ? 'selected' : '' }}>{{ $reasonOpt }}</option>
                            @endforeach
                        </select>
                        @error('card_reason')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Damage Card - Damage Proof Photo -->
                    <div class="col-md-6" id="damage_doc_section" style="display: none;">
                        <label class="form-label">Upload Damage Proof / Photo <span class="text-danger">*</span></label>
                        <input type="file" name="damage_doc" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png">
                        <small class="form-hint">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @error('damage_doc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Card Lost - FIR Document -->
                    <div class="col-md-6" id="fir_doc_section" style="display: none;">
                        <label class="form-label">Upload FIR Copy / Document Proof <span class="text-danger">*</span></label>
                        @if(isset($edit_id))
                            @php
                                $firDoc = $existing_docs['fir_doc'] ?? null;
                                $firPath = $firDoc ? 'idcard/dup_docs/' . $firDoc : null;
                                $firExists = $firPath && \Storage::disk('public')->exists($firPath);
                            @endphp
                            @if($firExists)
                                <a href="{{ asset('storage/' . $firPath) }}" target="_blank" class="btn btn-sm btn-outline-primary d-block mb-2">View Current FIR Document</a>
                            @elseif($firDoc)
                                <div class="form-hint mb-2">No file available in storage</div>
                            @endif
                        @endif
                        <input type="file" name="fir_doc" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png">
                        @if(isset($edit_id))
                            <small class="form-hint">Leave empty to keep current. Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @else
                            <small class="form-hint">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @endif
                        @error('fir_doc')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Card Lost - Payment Receipt (required on new request; on edit, required only if none on file) -->
                    <div class="col-md-6" id="payment_receipt_section" style="display: none;">
                        <label class="form-label">Upload Payment Receipt <span class="text-danger">*</span></label>
                        @if(isset($edit_id))
                            @php
                                $payDoc = $existing_docs['payment_receipt'] ?? null;
                                $payPath = $payDoc ? 'idcard/dup_docs/' . $payDoc : null;
                                $payExists = $payPath && \Storage::disk('public')->exists($payPath);
                            @endphp
                            @if($payExists)
                                <a href="{{ asset('storage/' . $payPath) }}" target="_blank" class="btn btn-sm btn-outline-primary d-block mb-2">View Current Payment Receipt</a>
                            @elseif($payDoc)
                                <div class="form-hint mb-2">No file available in storage</div>
                            @endif
                        @endif
                        <input type="file" name="payment_receipt" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png">
                        <small class="form-hint">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @error('payment_receipt')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Service Extended - Extension Proof -->
                    <div class="col-md-6" id="service_ext_section" style="display: none;">
                        <label class="form-label">Upload Service Extension / Renewal Proof <span class="text-danger">*</span></label>
                        <input type="file" name="service_ext" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png">
                        <small class="form-hint">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
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
                        <small class="form-hint">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @error('name_proof')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <!-- Designation Change - Official Order -->
                    <div class="col-md-6" id="designation_order_section" style="display: none;">
                        <label class="form-label">Upload Official Order / Transfer Letter <span class="text-danger">*</span></label>
                        <input type="file" name="designation_order" class="form-control" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png">
                        <small class="form-hint">Allowed: PDF, DOC, DOCX, JPG, PNG. Max size: 5 MB</small>
                        @error('designation_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- ============ Card Validity ============ --}}
                <h6 class="dupidc-section-title mt-4">Card Validity</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">ID-Card Valid From</label>
                        <input type="date" name="card_valid_from" id="card_valid_from" class="form-control" value="{{ old('card_valid_from', $data['card_valid_from'] ?? '') }}">
                        @error('card_valid_from')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ID-Card Valid Upto</label>
                        <input type="date" name="card_valid_to" id="card_valid_to" class="form-control" value="{{ old('card_valid_to', $data['card_valid_to'] ?? '') }}">
                        @error('card_valid_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="dupidc-form-footer d-flex flex-wrap justify-content-end gap-2">
                    <a href="{{ route('admin.duplicate_idcard.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 d-inline-flex align-items-center gap-2">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">save</i>
                        {{ isset($edit_id) ? 'Update' : 'Save' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var prefetchedPermanentCardNo = @json($prefetchedPermanentCardNo ?? null);

    function notify(message, isError) {
        if (window.Swal) {
            Swal.fire({ icon: isError ? 'error' : 'success', text: message });
        } else {
            alert(message);
        }
    }

    /* ---- Fetch employee details by ID card number ---- */
    (function initDuplicatePrefetch() {
        var btnFetch = document.getElementById('btnFetchByCard');
        var cardInput = document.getElementById('id_card_number');
        var typeSelect = document.getElementById('id_card_type');
        if (!btnFetch || !cardInput || !typeSelect) { return; }

        var isFetching = false;

        function fetchByCardNumber(options) {
            options = options || {};
            if (isFetching) { return; }
            var cardNo = cardInput.value.trim();
            var type = typeSelect.value || 'Permanent';
            if (!cardNo) {
                if (!options.suppressCardEmptyPrompt) {
                    notify('Please enter ID Card Number first.', true);
                    cardInput.focus();
                }
                return;
            }
            isFetching = true;
            btnFetch.disabled = true;
            fetch("{{ route('admin.duplicate_idcard.lookup') }}?id_card_number=" + encodeURIComponent(cardNo) + "&id_card_type=" + encodeURIComponent(type), {
                headers: { 'Accept': 'application/json' }
            })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                .then(function (result) {
                    isFetching = false;
                    btnFetch.disabled = false;
                    if (!result.ok || !result.data || !result.data.success) {
                        notify((result.data && result.data.message) ? result.data.message : 'Could not fetch ID card details.', true);
                        return;
                    }
                    var d = result.data.data || {};
                    var setVal = function (id, value) {
                        var el = document.getElementById(id);
                        if (el && (el.tagName === 'INPUT' || el.tagName === 'SELECT')) { el.value = value || ''; }
                    };
                    setVal('employee_name', d.employee_name || '');
                    setVal('designation', d.designation || '');
                    setVal('date_of_birth', d.date_of_birth || '');
                    setVal('blood_group', d.blood_group || '');
                    setVal('mobile_number', d.mobile_number || '');
                    setVal('father_name', d.father_name || '');
                    setVal('card_valid_from', d.card_valid_from || '');
                    setVal('card_valid_to', d.card_valid_to || '');
                    if (!options.silentSuccess) {
                        notify('ID card details fetched successfully. Please select duplicate reason and upload required document.', false);
                    }
                })
                .catch(function () {
                    isFetching = false;
                    btnFetch.disabled = false;
                    notify('Error while fetching ID card details. Please try again.', true);
                });
        }

        /** When Permanent is chosen, put the user's approved card number on file into the field and load details. */
        function applyPermanentIdCardPrefetch() {
            if (typeSelect.value !== 'Permanent' || !prefetchedPermanentCardNo) { return; }
            if (cardInput.value.trim() !== '') { return; }
            cardInput.value = prefetchedPermanentCardNo;
            fetchByCardNumber({ silentSuccess: true, suppressCardEmptyPrompt: true });
        }

        $(typeSelect).on('change', function () {
            cardInput.value = '';
            applyPermanentIdCardPrefetch();
        });
        applyPermanentIdCardPrefetch();

        btnFetch.addEventListener('click', function () { fetchByCardNumber({}); });
        cardInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); fetchByCardNumber({}); }
        });
    })();

    /* ---- Contractual-only fields ---- */
    (function initContractualFieldsToggle() {
        var typeSelect = document.getElementById('id_card_type');
        var $contractual = $('.contractual-only');
        if (!typeSelect || !$contractual.length) { return; }
        function updateVisibility() {
            $contractual.toggle(typeSelect.value === 'Contractual');
        }
        $(typeSelect).on('change', updateVisibility);
        updateVisibility();
    })();

    /* ---- Reason → required supporting document ----
       Bound through jQuery so it also fires when a select plugin sets the value
       with .trigger('change') (a native listener alone would miss that). ---- */
    (function initCardReasonToggle() {
        var $reason = $('#card_reason_select');
        if (!$reason.length) { return; }

        var SECTIONS = ['damage_doc_section', 'fir_doc_section', 'payment_receipt_section',
                        'service_ext_section', 'new_name_section', 'name_proof_section',
                        'designation_order_section'];
        var FIELDS = ['damage_doc', 'fir_doc', 'payment_receipt', 'service_ext',
                      'new_employee_name', 'name_proof', 'designation_order'];

        function field(name) { return document.querySelector('[name="' + name + '"]'); }
        function show(id) { var el = document.getElementById(id); if (el) { el.style.display = 'block'; } }
        function require(name) { var el = field(name); if (el) { el.setAttribute('required', 'required'); } }

        function toggleDocumentSections() {
            SECTIONS.forEach(function (id) {
                var el = document.getElementById(id);
                if (el) { el.style.display = 'none'; }
            });
            FIELDS.forEach(function (name) {
                var el = field(name);
                if (el) { el.removeAttribute('required'); }
            });

            switch ($reason.val()) {
                case 'Damage Card':
                    show('damage_doc_section');
                    require('damage_doc');
                    break;
                case 'Card Lost':
                    show('fir_doc_section');
                    show('payment_receipt_section');
                    require('fir_doc');
                    // On edit, a receipt already on file means a new upload isn't mandatory.
                    var stored = document.getElementById('dup_stored_payment_receipt');
                    if (!stored || stored.value !== '1') { require('payment_receipt'); }
                    break;
                case 'Service Extended':
                    show('service_ext_section');
                    require('service_ext');
                    break;
                case 'Change in Name':
                    show('new_name_section');
                    show('name_proof_section');
                    require('new_employee_name');
                    require('name_proof');
                    break;
                case 'Designation Change':
                    show('designation_order_section');
                    require('designation_order');
                    break;
            }
        }

        $reason.on('change input', toggleDocumentSections);
        toggleDocumentSections();
    })();
});
</script>
@endpush
