@extends('admin.layouts.master')
@section('title', 'Step 2 – Personal Details')

@section('setup_content')
<div class="row justify-content-center">
<div class="col-12 col-xl-10">

    @include('partials.step-indicator', ['current' => 2])

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h5 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-card-list me-2"></i>Step 2: Personal Details
            </h5>
            <small class="text-muted">Category, address, emergency contact &amp; family details</small>
        </div>

        <div class="card-body p-4">
            <form method="POST" action="{{ route('fc-reg.registration.step2.save') }}">
                @csrf

                <!-- ── Personal Classification ──────────────────── -->
                <h6 class="section-heading">Classification</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                            <option value="">Select…</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" {{ old('category_id', $step2?->category_id) == $c->id ? 'selected' : '' }}>
                                    {{ $c->category_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Religion <span class="text-danger">*</span></label>
                        <select name="religion_id" class="form-select @error('religion_id') is-invalid @enderror">
                            <option value="">Select…</option>
                            @foreach($religions as $r)
                                <option value="{{ $r->id }}" {{ old('religion_id', $step2?->religion_id) == $r->id ? 'selected' : '' }}>
                                    {{ $r->religion_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('religion_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Marital Status <span class="text-danger">*</span></label>
                        <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                            <option value="">Select…</option>
                            @foreach(['Single','Married','Divorced','Widowed'] as $ms)
                                <option value="{{ $ms }}" {{ old('marital_status', $step2?->marital_status) == $ms ? 'selected' : '' }}>{{ $ms }}</option>
                            @endforeach
                        </select>
                        @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Blood Group <span class="text-danger">*</span></label>
                        <select name="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
                            <option value="">Select…</option>
                            @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                                <option value="{{ $bg }}" {{ old('blood_group', $step2?->blood_group) == $bg ? 'selected' : '' }}>{{ $bg }}</option>
                            @endforeach
                        </select>
                        @error('blood_group')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Height (cm)</label>
                        <input type="number" name="height_cm" class="form-control" min="50" max="300"
                               value="{{ old('height_cm', $step2?->height_cm) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Weight (kg)</label>
                        <input type="number" name="weight_kg" class="form-control" min="20" max="300"
                               value="{{ old('weight_kg', $step2?->weight_kg) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Identification Mark 1</label>
                        <input type="text" name="identification_mark1" class="form-control"
                               value="{{ old('identification_mark1', $step2?->identification_mark1) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Identification Mark 2</label>
                        <input type="text" name="identification_mark2" class="form-control"
                               value="{{ old('identification_mark2', $step2?->identification_mark2) }}">
                    </div>
                </div>

                <!-- ── Permanent Address ─────────────────────────── -->
                <h6 class="section-heading">Permanent Address</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Address Line 1 <span class="text-danger">*</span></label>
                        <input type="text" name="perm_address_line1" class="form-control @error('perm_address_line1') is-invalid @enderror"
                               value="{{ old('perm_address_line1', $step2?->perm_address_line1) }}">
                        @error('perm_address_line1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Address Line 2</label>
                        <input type="text" name="perm_address_line2" class="form-control"
                               value="{{ old('perm_address_line2', $step2?->perm_address_line2) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">City <span class="text-danger">*</span></label>
                        <input type="text" name="perm_city" class="form-control @error('perm_city') is-invalid @enderror"
                               value="{{ old('perm_city', $step2?->perm_city) }}">
                        @error('perm_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">State <span class="text-danger">*</span></label>
                        <select name="perm_state_id" class="form-select @error('perm_state_id') is-invalid @enderror">
                            <option value="">Select…</option>
                            @foreach($states as $s)
                                <option value="{{ $s->id }}" {{ old('perm_state_id', $step2?->perm_state_id) == $s->id ? 'selected' : '' }}>{{ $s->state_name }}</option>
                            @endforeach
                        </select>
                        @error('perm_state_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Pincode <span class="text-danger">*</span></label>
                        <input type="text" name="perm_pincode" maxlength="6" class="form-control @error('perm_pincode') is-invalid @enderror"
                               value="{{ old('perm_pincode', $step2?->perm_pincode) }}">
                        @error('perm_pincode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Country <span class="text-danger">*</span></label>
                        <select name="perm_country_id" class="form-select">
                            @foreach($countries as $c)
                                <option value="{{ $c->id }}" {{ old('perm_country_id', $step2?->perm_country_id ?? 1) == $c->id ? 'selected' : '' }}>{{ $c->country_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- ── Present Address ──────────────────────────── -->
                <div class="d-flex align-items-center gap-3 mb-3">
                    <h6 class="section-heading mb-0">Present Address</h6>
                    <div class="form-check ms-2 mb-0">
                        <input type="checkbox" class="form-check-input" id="sameAsPermanent"
                               name="same_as_permanent" value="1" {{ old('same_as_permanent') ? 'checked' : '' }}
                               onchange="togglePresentAddress(this)">
                        <label class="form-check-label small" for="sameAsPermanent">Same as Permanent</label>
                    </div>
                </div>
                <div id="presentAddressBlock" class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Address Line 1 <span class="text-danger">*</span></label>
                        <input type="text" name="pres_address_line1" class="form-control"
                               value="{{ old('pres_address_line1', $step2?->pres_address_line1) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Address Line 2</label>
                        <input type="text" name="pres_address_line2" class="form-control"
                               value="{{ old('pres_address_line2', $step2?->pres_address_line2) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">City <span class="text-danger">*</span></label>
                        <input type="text" name="pres_city" class="form-control"
                               value="{{ old('pres_city', $step2?->pres_city) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">State <span class="text-danger">*</span></label>
                        <select name="pres_state_id" class="form-select">
                            <option value="">Select…</option>
                            @foreach($states as $s)
                                <option value="{{ $s->id }}" {{ old('pres_state_id', $step2?->pres_state_id) == $s->id ? 'selected' : '' }}>{{ $s->state_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Pincode <span class="text-danger">*</span></label>
                        <input type="text" name="pres_pincode" maxlength="6" class="form-control"
                               value="{{ old('pres_pincode', $step2?->pres_pincode) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Country <span class="text-danger">*</span></label>
                        <select name="pres_country_id" class="form-select">
                            @foreach($countries as $c)
                                <option value="{{ $c->id }}" {{ old('pres_country_id', $step2?->pres_country_id ?? 1) == $c->id ? 'selected' : '' }}>{{ $c->country_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- ── Emergency Contact ─────────────────────────── -->
                <h6 class="section-heading">Emergency Contact</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="emergency_contact_name" class="form-control @error('emergency_contact_name') is-invalid @enderror"
                               value="{{ old('emergency_contact_name', $step2?->emergency_contact_name) }}">
                        @error('emergency_contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Relation <span class="text-danger">*</span></label>
                        <input type="text" name="emergency_contact_relation" class="form-control @error('emergency_contact_relation') is-invalid @enderror"
                               value="{{ old('emergency_contact_relation', $step2?->emergency_contact_relation) }}" placeholder="Father / Mother / Spouse…">
                        @error('emergency_contact_relation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Mobile <span class="text-danger">*</span></label>
                        <input type="tel" name="emergency_contact_mobile" maxlength="10" class="form-control @error('emergency_contact_mobile') is-invalid @enderror"
                               value="{{ old('emergency_contact_mobile', $step2?->emergency_contact_mobile) }}">
                        @error('emergency_contact_mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <!-- ── Father's Profession ──────────────────────── -->
                <h6 class="section-heading">Father's Profession</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Profession Category</label>
                        <select name="father_profession_id" class="form-select">
                            <option value="">Select…</option>
                            @foreach($fatherProfessions as $fp)
                                <option value="{{ $fp->id }}" {{ old('father_profession_id', $step2?->father_profession_id) == $fp->id ? 'selected' : '' }}>
                                    {{ $fp->profession_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Occupation Details</label>
                        <input type="text" name="father_occupation_details" class="form-control"
                               value="{{ old('father_occupation_details', $step2?->father_occupation_details) }}">
                    </div>
                </div>

                <!-- ── Buttons ───────────────────────────────────── -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('fc-reg.registration.step1') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to Step 1
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        Save &amp; Continue <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
function togglePresentAddress(checkbox) {
    document.getElementById('presentAddressBlock').style.opacity = checkbox.checked ? '0.4' : '1';
    document.querySelectorAll('#presentAddressBlock input, #presentAddressBlock select').forEach(el => {
        el.disabled = checkbox.checked;
    });
}
// Init on load
document.addEventListener('DOMContentLoaded', function() {
    const cb = document.getElementById('sameAsPermanent');
    if (cb && cb.checked) togglePresentAddress(cb);
});
</script>
@endpush

@push('styles')
<style>
.section-heading {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #6c757d;
    font-weight: 600;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}
</style>
@endpush
