@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <iconify-icon icon="solar:pen-bold" class="me-2"></iconify-icon>
            Edit Finance Booking
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
        
        <form method="POST" action="{{ route('admin.mess.finance-bookings.update', $booking->id) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Booking Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="booking_amount" 
                               class="form-control @error('booking_amount') is-invalid @enderror" 
                               value="{{ old('booking_amount', $booking->amount) }}" 
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
                               value="{{ old('booking_date', $booking->booking_date->format('Y-m-d')) }}" 
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
                                  rows="3">{{ old('remarks', $booking->remarks) }}</textarea>
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
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                    Update Booking
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
