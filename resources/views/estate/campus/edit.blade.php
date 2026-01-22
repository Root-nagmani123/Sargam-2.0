@extends('admin.layouts.master')

@section('title', 'Edit Campus')

@section('setup_content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Edit Campus</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('estate.campus.update', $campus->pk) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="campus_name" class="form-label">Campus Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('campus_name') is-invalid @enderror" 
                                           id="campus_name" name="campus_name" value="{{ old('campus_name', $campus->campus_name) }}" required>
                                    @error('campus_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3">{{ old('description', $campus->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy"></i> Update
                                </button>
                                <a href="{{ route('estate.campus.index') }}" class="btn btn-secondary">
                                    <i class="ti ti-x"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
