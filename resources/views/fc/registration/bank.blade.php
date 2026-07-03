@extends('admin.layouts.master')
@section('title', 'Bank Details')
@section('setup_content')
<div class="row justify-content-center">
<div class="col-12 col-lg-8">

    @include('partials.step-indicator', ['current' => 4])

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white py-3 px-4">
            <h5 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-bank me-2"></i>Bank Account Details
            </h5>
            <small class="text-muted">Required for stipend disbursement during Foundation Course</small>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('fc-reg.registration.bank.save') }}" enctype="multipart/form-data">
                @csrf
                <div class="alert alert-info small py-2">
                    <i class="bi bi-info-circle me-2"></i>
                    Please ensure account is in your own name. Preferably a nationalized/scheduled bank account.
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
                               value="{{ old('bank_name', $bank?->bank_name) }}" placeholder="e.g. State Bank of India">
                        @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" name="branch_name" class="form-control @error('branch_name') is-invalid @enderror"
                               value="{{ old('branch_name', $bank?->branch_name) }}">
                        @error('branch_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">IFSC Code <span class="text-danger">*</span></label>
                        <input type="text" name="ifsc_code" class="form-control @error('ifsc_code') is-invalid @enderror"
                               value="{{ old('ifsc_code', $bank?->ifsc_code) }}" maxlength="11" style="text-transform:uppercase"
                               placeholder="e.g. SBIN0001234">
                        @error('ifsc_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Account Type <span class="text-danger">*</span></label>
                        <select name="account_type" class="form-select @error('account_type') is-invalid @enderror">
                            <option value="">Select…</option>
                            @foreach(['Savings','Current'] as $t)
                                <option value="{{ $t }}" {{ old('account_type', $bank?->account_type) == $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                        @error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Account Holder Name <span class="text-danger">*</span></label>
                        <input type="text" name="account_holder_name" class="form-control @error('account_holder_name') is-invalid @enderror"
                               value="{{ old('account_holder_name', $bank?->account_holder_name) }}">
                        @error('account_holder_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">Account Number <span class="text-danger">*</span></label>
                        <input type="text" name="account_no" class="form-control @error('account_no') is-invalid @enderror"
                               value="{{ old('account_no', $bank?->account_no) }}">
                        @error('account_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">Confirm Account Number <span class="text-danger">*</span></label>
                        <input type="text" name="account_no_confirm" class="form-control @error('account_no_confirm') is-invalid @enderror"
                               value="{{ old('account_no_confirm') }}" autocomplete="off">
                        @error('account_no_confirm')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Bank Passbook / Cancelled Cheque</label>
                        <input type="file" name="bank_passbook" accept=".jpg,.jpeg,.png,.pdf"
                               class="form-control @error('bank_passbook') is-invalid @enderror">
                        <div class="form-text">PDF/JPG, max 2MB. Front page of passbook or cancelled cheque.</div>
                        @if($bank?->bank_passbook_path)
                            <a href="{{ asset('storage/'.$bank->bank_passbook_path) }}" target="_blank" class="small text-primary mt-1 d-inline-block">
                                <i class="bi bi-file-earmark-check me-1"></i>Previously uploaded
                            </a>
                        @endif
                        @error('bank_passbook')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex justify-content-between pt-4 border-top mt-4">
                    <a href="{{ route('fc-reg.registration.step3') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to Step 3
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
