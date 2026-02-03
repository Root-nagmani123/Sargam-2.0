@extends('admin.layouts.master')

@section('setup_content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Monthly Bill</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.mess.monthly-bills.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                
                <form method="POST" action="{{ route('admin.mess.monthly-bills.update', $bill->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Bill Number</label>
                                    <input type="text" class="form-control" value="{{ $bill->bill_number }}" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>User</label>
                                    <input type="text" class="form-control" 
                                           value="{{ $bill->user ? (trim(($bill->user->first_name ?? '') . ' ' . ($bill->user->last_name ?? '')) ?: $bill->user->user_name) : 'N/A' }}" 
                                           readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Month/Year</label>
                                    <input type="text" class="form-control" 
                                           value="{{ date('F Y', mktime(0, 0, 0, $bill->month, 1, $bill->year)) }}" 
                                           readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Total Amount</label>
                                    <input type="text" class="form-control" 
                                           value="₹{{ number_format($bill->total_amount, 2) }}" 
                                           readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Paid Amount</label>
                                    <input type="number" step="0.01" name="paid_amount" 
                                           class="form-control @error('paid_amount') is-invalid @enderror" 
                                           value="{{ old('paid_amount', $bill->paid_amount) }}" 
                                           max="{{ $bill->total_amount }}">
                                    @error('paid_amount')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">Current: ₹{{ number_format($bill->paid_amount, 2) }}</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                                        <option value="pending" {{ old('status', $bill->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="paid" {{ old('status', $bill->status) == 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="partial" {{ old('status', $bill->status) == 'partial' ? 'selected' : '' }}>Partial</option>
                                        <option value="overdue" {{ old('status', $bill->status) == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                    </select>
                                    @error('status')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Paid Date</label>
                                    <input type="date" name="paid_date" 
                                           class="form-control @error('paid_date') is-invalid @enderror" 
                                           value="{{ old('paid_date', $bill->paid_date ? $bill->paid_date->format('Y-m-d') : '') }}">
                                    @error('paid_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Balance</label>
                                    <input type="text" class="form-control" 
                                           value="₹{{ number_format($bill->balance, 2) }}" 
                                           readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Bill
                        </button>
                        <a href="{{ route('admin.mess.monthly-bills.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>

    </div>
</div>
@endsection
