@extends('admin.layouts.master')
<<<<<<< HEAD
@section('title', 'Edit Material Management')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Edit Material Management #{{ $kitchenIssue->pk }}</h4>
        <a href="{{ route('admin.mess.material-management.index') }}" class="btn btn-secondary">Back to List</a>
=======
@section('title', 'Edit Kitchen Issue')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Edit Kitchen Issue #{{ $kitchenIssue->pk }}</h4>
        <a href="{{ route('admin.mess.kitchen-issues.index') }}" class="btn btn-secondary">Back to List</a>
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
    </div>
    
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <div class="card">
        <div class="card-body">
<<<<<<< HEAD
            <form action="{{ route('admin.mess.material-management.update', $kitchenIssue->pk) }}" method="POST">
=======
            <form action="{{ route('admin.mess.kitchen-issues.update', $kitchenIssue->pk) }}" method="POST">
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6 mb-3">
<<<<<<< HEAD
                        <label class="form-label">Transfer From Store <span class="text-danger">*</span></label>
<<<<<<< HEAD
=======
                        <label class="form-label">Store <span class="text-danger">*</span></label>
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
                        <select name="inve_store_master_pk" class="form-select" required>
=======
                        <select name="inve_store_master_pk" class="form-select select2" required>
>>>>>>> 1d23f418 (mess select2)
                            <option value="">Select Store</option>
                            @foreach($stores as $store)
                                <option value="{{ $store['id'] }}" {{ (old('inve_store_master_pk', $kitchenIssue->inve_store_master_pk) == $store['id']) ? 'selected' : '' }}>
                                    {{ $store['store_name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Item <span class="text-danger">*</span></label>
                        <select name="inve_item_master_pk" class="form-select select2" required>
                            <option value="">Select Item</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" {{ (old('inve_item_master_pk', $kitchenIssue->inve_item_master_pk) == $item->id) ? 'selected' : '' }}>
                                    {{ $item->item_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="quantity" class="form-control" value="{{ old('quantity', $kitchenIssue->quantity) }}" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="unit_price" class="form-control" value="{{ old('unit_price', $kitchenIssue->unit_price) }}" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Request Date <span class="text-danger">*</span></label>
                        <input type="date" name="request_date" class="form-control" value="{{ old('request_date', $kitchenIssue->request_date ? \Carbon\Carbon::parse($kitchenIssue->request_date)->format('Y-m-d') : '') }}" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Requested Store</label>
                        <select name="requested_store_id" class="form-select select2">
                            <option value="">Select Requested Store</option>
                            @foreach($stores as $store)
                                <option value="{{ $store['id'] }}" {{ (old('requested_store_id', $kitchenIssue->requested_store_id) == $store['id']) ? 'selected' : '' }}>
                                    {{ $store['store_name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Payment Type <span class="text-danger">*</span></label>
                        <select name="payment_type" class="form-select select2" required>
                            <option value="0" {{ old('payment_type', $kitchenIssue->payment_type) == '0' ? 'selected' : '' }}>Unpaid</option>
                            <option value="1" {{ old('payment_type', $kitchenIssue->payment_type) == '1' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
<<<<<<< HEAD
=======
                        <label class="form-label">Transfer To</label>
                        <input type="text" name="transfer_to" class="form-control" value="{{ old('transfer_to', $kitchenIssue->transfer_to) }}">
                    </div>
                    
                    <div class="col-md-6 mb-3">
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
                        <label class="form-label">Client Type</label>
                        <select name="client_type" class="form-select select2">
                            <option value="">Select Client Type</option>
                            <option value="2" {{ old('client_type', $kitchenIssue->client_type) == '2' ? 'selected' : '' }}>Student</option>
                            <option value="5" {{ old('client_type', $kitchenIssue->client_type) == '5' ? 'selected' : '' }}>Employee</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3">{{ old('remarks', $kitchenIssue->remarks) }}</textarea>
                </div>
                
                <div class="text-end">
<<<<<<< HEAD
                    <button type="submit" class="btn btn-primary">Update Material Management</button>
                    <a href="{{ route('admin.mess.material-management.index') }}" class="btn btn-secondary">Cancel</a>
=======
                    <button type="submit" class="btn btn-primary">Update Kitchen Issue</button>
                    <a href="{{ route('admin.mess.kitchen-issues.index') }}" class="btn btn-secondary">Cancel</a>
>>>>>>> 824e914f (feat(kitchen-management-and-report): kitchen management and report module included)
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
