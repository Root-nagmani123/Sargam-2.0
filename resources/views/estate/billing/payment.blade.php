@extends('admin.layouts.master')

@section('title', 'Add Payment')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Add Payment" />
    
    <!-- Bill Summary -->
    <div class="card mb-3" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="mb-3">Bill Summary</h4>
            <div class="row">
                <div class="col-md-4">
                    <strong>Employee:</strong> {{ $billing->possession->employee->name ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Unit:</strong> {{ $billing->possession->unit->unit_name ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Bill Period:</strong> {{ date('F', mktime(0, 0, 0, $billing->bill_month, 1)) }} {{ $billing->bill_year }}
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-4">
                    <h5>Total Amount: <span class="text-primary">₹{{ number_format($billing->total_amount, 2) }}</span></h5>
                </div>
                <div class="col-md-4">
                    <h5>Paid Amount: <span class="text-success">₹{{ number_format($billing->paid_amount, 2) }}</span></h5>
                </div>
                <div class="col-md-4">
                    <h5>Balance: <span class="text-danger">₹{{ number_format($billing->balance_amount, 2) }}</span></h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Form -->
    <div class="card" style="border-left: 4px solid #28a745;">
        <div class="card-body">
            <h4 class="mb-3">Record Payment</h4>
            <hr>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <form action="{{ route('estate.billing.payment.store', $billing->pk) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                   id="payment_date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" 
                                   id="amount" name="amount" value="{{ old('amount', $billing->balance_amount) }}" 
                                   max="{{ $billing->balance_amount }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Maximum payable: ₹{{ number_format($billing->balance_amount, 2) }}</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="payment_mode" class="form-label">Payment Mode <span class="text-danger">*</span></label>
                            <select class="form-select @error('payment_mode') is-invalid @enderror" 
                                    id="payment_mode" name="payment_mode" required>
                                <option value="">Select Payment Mode</option>
                                <option value="Cash" {{ old('payment_mode') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option value="Cheque" {{ old('payment_mode') == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="DD" {{ old('payment_mode') == 'DD' ? 'selected' : '' }}>Demand Draft</option>
                                <option value="Online Transfer" {{ old('payment_mode') == 'Online Transfer' ? 'selected' : '' }}>Online Transfer</option>
                                <option value="UPI" {{ old('payment_mode') == 'UPI' ? 'selected' : '' }}>UPI</option>
                                <option value="Card" {{ old('payment_mode') == 'Card' ? 'selected' : '' }}>Card</option>
                            </select>
                            @error('payment_mode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="transaction_reference" class="form-label">Transaction Reference / Cheque No.</label>
                            <input type="text" class="form-control @error('transaction_reference') is-invalid @enderror" 
                                   id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference') }}">
                            @error('transaction_reference')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control @error('remarks') is-invalid @enderror" 
                                      id="remarks" name="remarks" rows="3">{{ old('remarks') }}</textarea>
                            @error('remarks')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-success"><i class="ti ti-cash"></i> Record Payment</button>
                        <a href="{{ route('estate.billing.show', $billing->pk) }}" class="btn btn-secondary"><i class="ti ti-x"></i> Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
