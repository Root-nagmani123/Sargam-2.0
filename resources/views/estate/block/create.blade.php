@extends('admin.layouts.master')

@section('title', 'Add Block')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Add New Block" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="mb-3">Add New Block/Building</h4>
            <hr>
            <form action="{{ route('estate.block.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="block_name" class="form-label">Block Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('block_name') is-invalid @enderror" 
                                   id="block_name" name="block_name" value="{{ old('block_name') }}" required>
                            @error('block_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                        <a href="{{ route('estate.block.index') }}" class="btn btn-secondary"><i class="ti ti-x"></i> Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
