@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <iconify-icon icon="solar:wallet-money-bold" class="me-2"></iconify-icon>
            Create Finance Booking
        </h5>
        <a href="{{ route('admin.mess.finance-bookings.index') }}" class="btn btn-secondary btn-sm">
            <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
            Back to List
        </a>
    </div>
    
    <div class="card-body">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if($invoices->isEmpty())
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>No Invoices Available!</strong> Please create invoices first before creating finance bookings.
                <a href="{{ route('admin.mess.invoices.create') }}" class="alert-link">Create Invoice</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        <form method="POST" action="{{ route('admin.mess.finance-bookings.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Invoice <span class="text-danger">*</span></label>
                        <select name="invoice_id" class="form-select @error('invoice_id') is-invalid @enderror" required {{ $invoices->isEmpty() ? 'disabled' : '' }}>
                            <option value="">{{ $invoices->isEmpty() ? 'No invoices available' : 'Select Invoice' }}</option>
                            @foreach($invoices as $invoice)
                                <option value="{{ $invoice->id }}" {{ old('invoice_id') == $invoice->id ? 'selected' : '' }}>
                                    Invoice #{{ $invoice->invoice_no ?? $invoice->id }} 
                                    @if($invoice->vendor)
                                        - {{ $invoice->vendor->name }}
                                    @endif
                                    - ₹{{ number_format($invoice->amount, 2) }}
                                    @if($invoice->payment_status)
                                        ({{ ucfirst($invoice->payment_status) }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('invoice_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($invoices->isEmpty())
                            <small class="form-text text-muted">Create an invoice first to proceed with finance booking.</small>
                        @endif
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">User <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            <option value="">Select User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->pk }}" {{ old('user_id') == $user->pk ? 'selected' : '' }}>
                                    {{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->user_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Booking Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="booking_amount" 
                               class="form-control @error('booking_amount') is-invalid @enderror" 
                               value="{{ old('booking_amount') }}" 
                               required>
                        @error('booking_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Booking Date <span class="text-danger">*</span></label>
                        <input type="date" name="booking_date" 
                               class="form-control @error('booking_date') is-invalid @enderror" 
                               value="{{ old('booking_date', date('Y-m-d')) }}" 
                               required>
                        @error('booking_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" 
                                  class="form-control @error('remarks') is-invalid @enderror" 
                                  rows="3">{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.mess.finance-bookings.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary" {{ $invoices->isEmpty() ? 'disabled' : '' }}>
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                    Create Booking
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
