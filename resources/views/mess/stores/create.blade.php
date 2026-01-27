@extends('admin.layouts.master')
@section('title', 'Add Mess Store')
@section('setup_content')
<div class="container-fluid">
    <h4>Add Mess Store</h4>
    <form method="POST" action="{{ route('admin.mess.stores.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Store Code *</label>
                <input type="text" name="store_code" class="form-control" required value="{{ old('store_code') }}">
                @error('store_code')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label>Store Name *</label>
                <input type="text" name="store_name" class="form-control" required value="{{ old('store_name') }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Location</label>
                <input type="text" name="location" class="form-control" value="{{ old('location') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label>Status *</label>
                <select name="status" class="form-control" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Incharge Name</label>
                <input type="text" name="incharge_name" class="form-control" value="{{ old('incharge_name') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label>Incharge Contact</label>
                <input type="text" name="incharge_contact" class="form-control" value="{{ old('incharge_contact') }}">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('admin.mess.stores.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
