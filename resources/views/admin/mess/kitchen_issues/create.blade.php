@extends('admin.layouts.master')

@section('title', 'Create Material Management - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Create Material Management" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">Add Material Management</h4>
            <hr>
            <form action="{{ route('admin.mess.material-management.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="inve_store_master_pk" class="form-label">Store/Mess <span class="text-danger">*</span></label>
                            <select class="form-select select2 @error('inve_store_master_pk') is-invalid @enderror"
                                id="inve_store_master_pk" name="inve_store_master_pk" required>
                                <option value="">Select Store</option>
                                @foreach($stores as $store)
                                <option value="{{ $store->pk }}" {{ old('inve_store_master_pk') == $store->pk ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('inve_store_master_pk')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="inve_item_master_pk" class="form-label">Item <span class="text-danger">*</span></label>
                            <select class="form-select select2 @error('inve_item_master_pk') is-invalid @enderror"
                                id="inve_item_master_pk" name="inve_item_master_pk" required>
                                <option value="">Select Item</option>
                                @foreach($items as $item)
                                <option value="{{ $item->pk }}" {{ old('inve_item_master_pk') == $item->pk ? 'selected' : '' }}>
                                    {{ $item->item_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('inve_item_master_pk')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control @error('quantity') is-invalid @enderror"
                                id="quantity" name="quantity" value="{{ old('quantity') }}" required>
                            @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="unit_price" class="form-label">Unit Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control @error('unit_price') is-invalid @enderror"
                                id="unit_price" name="unit_price" value="{{ old('unit_price') }}" required>
                            @error('unit_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="payment_type" class="form-label">Payment Type <span class="text-danger">*</span></label>
                            <select class="form-select select2 @error('payment_type') is-invalid @enderror"
                                id="payment_type" name="payment_type" required>
                                <option value="">Select Payment Type</option>
                                <option value="0" {{ old('payment_type') == '0' ? 'selected' : '' }}>Cash</option>
                                <option value="1" {{ old('payment_type') == '1' ? 'selected' : '' }}>Credit</option>
                                <option value="2" {{ old('payment_type') == '2' ? 'selected' : '' }}>Debit</option>
                                <option value="5" {{ old('payment_type') == '5' ? 'selected' : '' }}>Account</option>
                            </select>
                            @error('payment_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="client_type" class="form-label">Client Type</label>
                            <select class="form-select select2 @error('client_type') is-invalid @enderror"
                                id="client_type" name="client_type">
                                <option value="">Select Client Type</option>
                                <option value="2" {{ old('client_type') == '2' ? 'selected' : '' }}>Student</option>
                                <option value="5" {{ old('client_type') == '5' ? 'selected' : '' }}>Employee</option>
                            </select>
                            @error('client_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="client_name" class="form-label">Client Name</label>
                            <input type="text" class="form-control @error('client_name') is-invalid @enderror"
                                id="client_name" name="client_name" value="{{ old('client_name') }}">
                            @error('client_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="issue_date" class="form-label">Issue Date</label>
                            <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                                id="issue_date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}">
                            @error('issue_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks"
                                name="remarks" rows="3">{{ old('remarks') }}</textarea>
                            @error('remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3 text-end gap-3">
                    <button type="submit" class="btn btn-primary">Save Material Management</button>
                    <a href="{{ route('admin.mess.material-management.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
