@extends('admin.layouts.master')

@section('title', 'Edit Module - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Edit Module</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Module
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Edit Module</h4>
            <hr>
            <form action="{{ route('subject-module.update', $module->pk) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-sm-10">
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

                    <div class="col-sm-10">
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
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                        <i class="material-icons menu-icon">send</i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- end Vertical Steps Example -->
</div>

@endsection
