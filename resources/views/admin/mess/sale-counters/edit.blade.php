@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:shop-bold" class="me-2"></iconify-icon>
            Edit Sale Counter
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.mess.sale-counters.update', $counter->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="counter_name" class="form-label">Counter Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('counter_name') is-invalid @enderror" 
                               id="counter_name" name="counter_name" value="{{ old('counter_name', $counter->counter_name) }}" 
                               placeholder="e.g., Main Counter, Canteen Counter" required>
                        @error('counter_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="counter_code" class="form-label">Counter Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('counter_code') is-invalid @enderror" 
                               id="counter_code" name="counter_code" value="{{ old('counter_code', $counter->counter_code) }}" 
                               placeholder="e.g., CNT001" required>
                        @error('counter_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="store_id" class="form-label">Store <span class="text-danger">*</span></label>
                        <select class="form-select @error('store_id') is-invalid @enderror" 
                                id="store_id" name="store_id" required>
                            <option value="">-- Select Store --</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" 
                                    {{ (old('store_id', $counter->store_id) == $store->id) ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('store_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               value="{{ old('location', $counter->location) }}" 
                               placeholder="e.g., Building A, Ground Floor">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                           value="1" {{ old('is_active', $counter->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.mess.sale-counters.index') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                    Back
                </a>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                    Update Counter
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
