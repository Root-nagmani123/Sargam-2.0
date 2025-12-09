@extends('admin.layouts.master')

@section('title', 'Edit Module - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">
     <x-breadcrum title="Subject module" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">Edit Module</h4>
            <hr>
            <form action="{{ route('subject-module.update', $module->pk) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-6">
                        <label for="module_name" class="form-label">Module Name :</label>
                        <div class="mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   id="module_name" 
                                   name="module_name" 
                                   placeholder="Enter Module Name"
                                   value="{{ old('module_name', $module->module_name) }}" required>
                            @error('module_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6">
                        <label for="active_inactive" class="form-label">Status :</label>
                        <div class="mb-3">
                            <select class="form-select" id="active_inactive" name="active_inactive" required>
                                <option value="1" {{ old('active_inactive', $module->active_inactive) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('active_inactive', $module->active_inactive) == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('active_inactive')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>
                <div class="mb-3 text-end gap-3">
                    <button class="btn btn-primary" type="submit">Update</button>
                    <a href="{{ route('subject-module.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>

    <!-- end Vertical Steps Example -->
</div>

@endsection
