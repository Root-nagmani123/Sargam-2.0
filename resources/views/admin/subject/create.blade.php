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
            <h4 class="card-title mb-3">Add Major Subject</h4>
            <hr>
            <form action="{{ route('subject.store') }}" method="POST">
                @csrf

                <!-- Major Subject Name -->
                <div class="mb-3">
                    <label for="major_subject_name" class="form-label">Major Subject Name</label>
                    <input type="text" name="major_subject_name" id="major_subject_name" class="form-control" >
                    @error('major_subject_name')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Short Name -->
                <div class="mb-3">
                    <label for="short_name" class="form-label">Short Name</label>
                    <input type="text" name="short_name" id="short_name" class="form-control" >
                    @error('short_name')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Topic Name -->
                <div class="mb-3">
                    <label for="topic_name" class="form-label">Topic Name</label>
                    <input type="text" name="topic_name" id="topic_name" class="form-control">
                    @error('topic_name')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Subject Module -->
                <div class="mb-3">
                    <label for="subject_module" class="form-label">Subject Module</label>
                    <select name="subject_module" id="subject_module" class="form-select" >
                        <option value="">-- Select Subject Module --</option>
                        @foreach($subjects as $module)
                        <option value="{{ $module->pk }}">{{ $module->module_name }}</option>
                        @endforeach
                    </select>
                    @error('subject_module')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Status -->
                <div class="mb-3 form-check form-switch">
                    <input type="checkbox" name="status" id="status" class="form-check-input" checked>
                    <label for="status" class="form-check-label">Active</label>
                </div>

                <!-- Submit & Cancel Buttons -->
                <button type="submit" class="btn btn-success">Save</button>
                <a href="{{ route('subject.index') }}" class="btn btn-secondary">Cancel</a>
            </form>

        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection