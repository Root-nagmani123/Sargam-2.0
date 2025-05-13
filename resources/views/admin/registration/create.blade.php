@extends('admin.layouts.master')

@section('title', 'Create Form - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Create Form</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="{{ route('forms.index') }}">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Forms
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Start Form Card -->
    <div class="card">
        <div class="card-body">
            <hr>
    
            <form action="{{ route('forms.store') }}" method="POST">
                @csrf
                <div class="row">
                    <!-- Form Name -->
                    <div class="col-sm-6">
                        <label for="name" class="form-label">Form Name:</label>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter Form Name" required>
                        </div>
                    </div>
    
                    <!-- Short Name -->
                    <div class="col-sm-6">
                        <label for="shortname" class="form-label">Short Name:</label>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="shortname" name="shortname" placeholder="Enter Short Name" required>
                        </div>
                    </div>
    
                    <!-- Description -->
                    <div class="col-sm-12">
                        <label for="description" class="form-label">Description:</label>
                        <div class="mb-3">
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter Description" required></textarea>
                        </div>
                    </div>
    
                    <!-- Course Start Date -->
                    <div class="col-sm-6">
                        <label for="course_sdate" class="form-label">Course Start Date:</label>
                        <div class="mb-3">
                            <input type="date" class="form-control" id="course_sdate" name="course_sdate" required>
                        </div>
                    </div>
    
                    <!-- Course End Date -->
                    <div class="col-sm-6">
                        <label for="course_edate" class="form-label">Course End Date:</label>
                        <div class="mb-3">
                            <input type="date" class="form-control" id="course_edate" name="course_edate" required>
                        </div>
                    </div>
    
                    <!-- Visibility Toggle -->
                    <div class="col-sm-12">
                        <label class="form-label">Visible:</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="visible" name="visible">
                            <label class="form-check-label" for="visible">Enable visibility for this form</label>
                        </div>
                    </div>
                </div>
    
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                        <i class="material-icons menu-icon">send</i> Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- End Form Card -->
</div>

@endsection
