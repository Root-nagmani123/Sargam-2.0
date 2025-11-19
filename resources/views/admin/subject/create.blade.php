@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Add Major Subject</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Subject
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
            <h4 class="card-title mb-3">Add Subject</h4>
            <hr>
            <form action="{{ route('subject.store') }}" method="POST">
                @csrf

                <!-- Major Subject Name -->
                <div class="row">
                    <div class="col-4">
                        <div class="mb-3">
                            <label for="major_subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                            <input type="text" name="major_subject_name" id="major_subject_name" class="form-control">
                            @error('major_subject_name')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-4">
                        <!-- Short Name -->
                        <div class="mb-3">
                            <label for="short_name" class="form-label">Short Name <span class="text-danger">*</span></label>
                            <input type="text" name="short_name" id="short_name" class="form-control">
                            @error('short_name')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-4">
                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status </label>
                            <select name="status" id="status" class="form-select">
                                <option value="">-- Select Status --</option>
                                <option value="1" {{ old('status', '1') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <hr>
                <!-- Submit & Cancel Buttons -->
                <div class="mb-3 text-end gap-3">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('subject.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </form>

        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection