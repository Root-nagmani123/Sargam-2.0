@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <iconify-icon icon="solar:document-add-bold" class="me-2"></iconify-icon>
            Create Monthly Bill
        </h5>
        <a href="{{ route('admin.mess.monthly-bills.index') }}" class="btn btn-secondary btn-sm">
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
        
        <form method="POST" action="{{ route('admin.mess.monthly-bills.store') }}">
            @csrf
            <div class="row">
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
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Month/Year <span class="text-danger">*</span></label>
                        <input type="month" name="month_year" 
                               class="form-control @error('month_year') is-invalid @enderror" 
                               value="{{ old('month_year', date('Y-m')) }}" 
                               required>
                        @error('month_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Total Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="total_amount" 
                               class="form-control @error('total_amount') is-invalid @enderror" 
                               value="{{ old('total_amount') }}" 
                               required>
                        @error('total_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="">Select Status</option>
                            <option value="unpaid" {{ old('status', 'unpaid') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="partial" {{ old('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Paid Amount</label>
                        <input type="number" step="0.01" name="paid_amount" 
                               class="form-control @error('paid_amount') is-invalid @enderror" 
                               value="{{ old('paid_amount', 0) }}">
                        @error('paid_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Paid Date</label>
                        <input type="date" name="paid_date" 
                               class="form-control @error('paid_date') is-invalid @enderror" 
                               value="{{ old('paid_date') }}">
                        @error('paid_date')
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
                <a href="{{ route('admin.mess.monthly-bills.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                    Create Bill
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
