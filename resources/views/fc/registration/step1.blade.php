@extends('admin.layouts.master')
@section('title', 'Step 1 – Basic Information')

@section('setup_content')
<div class="row justify-content-center">
<div class="col-12 col-xl-10">

    <!-- Step indicator -->
    @include('partials.step-indicator', ['current' => 1])

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h5 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-person-fill me-2"></i>Step 1: Basic Information
            </h5>
            <small class="text-muted">Personal details, service allotment &amp; contact information</small>
        </div>

        <div class="card-body p-4">
            <form method="POST" action="{{ route('fc-reg.registration.step1.save') }}" enctype="multipart/form-data">
                @csrf

                <!-- ── Section: Personal ──────────────────────────── -->
                <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2" style="font-size:0.78rem;letter-spacing:1px;">
                    Personal Details
                </h6>
                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name', $step1?->full_name) }}" placeholder="As per UPSC records">
                        @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Father's Name <span class="text-danger">*</span></label>
                        <input type="text" name="fathers_name" class="form-control @error('fathers_name') is-invalid @enderror"
                               value="{{ old('fathers_name', $step1?->fathers_name) }}">
                        @error('fathers_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Mother's Name <span class="text-danger">*</span></label>
                        <input type="text" name="mothers_name" class="form-control @error('mothers_name') is-invalid @enderror"
                               value="{{ old('mothers_name', $step1?->mothers_name) }}">
                        @error('mothers_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                               value="{{ old('date_of_birth', $step1?->date_of_birth?->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}">
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                            <option value="">Select…</option>
                            @foreach(['Male','Female','Other'] as $g)
                                <option value="{{ $g }}" {{ old('gender', $step1?->gender) == $g ? 'selected' : '' }}>{{ $g }}</option>
                            @endforeach
                        </select>
                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <!-- ── Section: Service ───────────────────────────── -->
                <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2" style="font-size:0.78rem;letter-spacing:1px;">
                    Service & Session Details
                </h6>
                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">FC Session <span class="text-danger">*</span></label>
                        <select name="session_id" class="form-select @error('session_id') is-invalid @enderror">
                            <option value="">Select session…</option>
                            @foreach($sessions as $s)
                                <option value="{{ $s->id }}" {{ old('session_id', $step1?->session_id) == $s->id ? 'selected' : '' }}>
                                    {{ $s->session_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('session_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Service <span class="text-danger">*</span></label>
                        <select name="service_id" class="form-select @error('service_id') is-invalid @enderror">
                            <option value="">Select service…</option>
                            @foreach($services as $s)
                                <option value="{{ $s->pk }}" {{ old('service_id', $step1?->service_id) == $s->pk ? 'selected' : '' }}>
                                    {{ $s->service_name }} ({{ $s->service_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('service_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Cadre <span class="text-danger">*</span></label>
                        <input type="text" name="cadre" class="form-control @error('cadre') is-invalid @enderror"
                               value="{{ old('cadre', $step1?->cadre) }}" placeholder="e.g. AGMUT, UP, Bihar…">
                        @error('cadre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Allotted State / UT <span class="text-danger">*</span></label>
                        <select name="allotted_state_id" class="form-select @error('allotted_state_id') is-invalid @enderror">
                            <option value="">Select state…</option>
                            @foreach($states as $s)
                                <option value="{{ $s->pk }}" {{ old('allotted_state_id', $step1?->allotted_state_id) == $s->pk ? 'selected' : '' }}>
                                    {{ $s->state_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('allotted_state_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <!-- ── Section: Contact ───────────────────────────── -->
                <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2" style="font-size:0.78rem;letter-spacing:1px;">
                    Contact Information
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">+91</span>
                            <input type="tel" name="mobile_no" class="form-control @error('mobile_no') is-invalid @enderror"
                                   maxlength="10" value="{{ old('mobile_no', $step1?->mobile_no) }}" placeholder="10-digit mobile">
                            @error('mobile_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $step1?->email) }}" placeholder="Official email preferred">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <!-- ── Section: Photo & Signature ─────────────────── -->
                <h6 class="text-uppercase text-muted fw-semibold mb-3 border-bottom pb-2" style="font-size:0.78rem;letter-spacing:1px;">
                    Photo &amp; Signature Upload
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Passport Photo</label>
                        <input type="file" name="photo" accept="image/jpeg,image/jpg,image/png"
                               class="form-control @error('photo') is-invalid @enderror">
                        <div class="form-text">JPEG/PNG, max 500KB. White background.</div>
                        @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($step1?->photo_path)
                            <img src="{{ asset('storage/'.$step1->photo_path) }}" alt="Photo"
                                 class="mt-2 rounded border" style="height:80px;">
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Signature</label>
                        <input type="file" name="signature" accept="image/jpeg,image/jpg,image/png"
                               class="form-control @error('signature') is-invalid @enderror">
                        <div class="form-text">JPEG/PNG, max 200KB. On white paper.</div>
                        @error('signature')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($step1?->signature_path)
                            <img src="{{ asset('storage/'.$step1->signature_path) }}" alt="Signature"
                                 class="mt-2 rounded border bg-white p-1" style="height:50px;">
                        @endif
                    </div>
                </div>

                <!-- ── Action Buttons ──────────────────────────────── -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('fc-reg.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Dashboard
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
