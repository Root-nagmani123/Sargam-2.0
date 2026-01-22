@extends('admin.layouts.master')
@section('title', 'Edit Mess Store')
@section('setup_content')
<div class="container-fluid">
    <h4>Edit Mess Store</h4>
    <form method="POST" action="{{ route('mess.stores.update', $store->id) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Store Code *</label>
                <input type="text" name="store_code" class="form-control" required value="{{ $store->store_code }}">
                @error('store_code')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label>Store Name *</label>
                <input type="text" name="store_name" class="form-control" required value="{{ $store->store_name }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Location</label>
                <input type="text" name="location" class="form-control" value="{{ $store->location }}">
            </div>
            <div class="col-md-6 mb-3">
                <label>Status *</label>
                <select name="status" class="form-control" required>
                    <option value="active" {{ $store->status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $store->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Incharge Name</label>
                <input type="text" name="incharge_name" class="form-control" value="{{ $store->incharge_name }}">
            </div>
            <div class="col-md-6 mb-3">
                <label>Incharge Contact</label>
                <input type="text" name="incharge_contact" class="form-control" value="{{ $store->incharge_contact }}">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('mess.stores.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
