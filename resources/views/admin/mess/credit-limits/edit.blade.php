@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:wallet-money-bold" class="me-2"></iconify-icon>
            Edit Credit Limit
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.mess.credit-limits.update', $creditLimit->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">User <span class="text-danger">*</span></label>
                        <select class="form-select select2 @error('user_id') is-invalid @enderror" 
                                id="user_id" name="user_id" required>
                            <option value="">-- Select User --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->pk }}" 
                                    {{ (old('user_id', $creditLimit->user_id) == $user->pk) ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
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
                        <label for="client_type" class="form-label">Client Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('client_type') is-invalid @enderror" 
                               id="client_type" name="client_type" value="{{ old('client_type', $creditLimit->client_type) }}" 
                               placeholder="e.g., Officer, Student, Guest" required>
                        @error('client_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="credit_limit" class="form-label">Credit Limit (₹) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('credit_limit') is-invalid @enderror" 
                               id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $creditLimit->credit_limit) }}" 
                               step="0.01" min="0" placeholder="0.00" required>
                        @error('credit_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="current_balance" class="form-label">Current Balance (₹)</label>
                        <input type="number" class="form-control" id="current_balance" name="current_balance" 
                               value="{{ old('current_balance', $creditLimit->current_balance) }}" step="0.01" placeholder="0.00">
                        <small class="text-muted">Current outstanding balance</small>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea class="form-control" id="remarks" name="remarks" rows="3" 
                          placeholder="Additional notes">{{ old('remarks', $creditLimit->remarks ?? '') }}</textarea>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                           value="1" {{ old('is_active', $creditLimit->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.mess.credit-limits.index') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                    Back
                </a>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                    Update Credit Limit
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
