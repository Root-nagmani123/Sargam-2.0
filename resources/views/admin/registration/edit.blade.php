@extends('admin.layouts.master')

@section('title', 'Edit Form - Sargam | Lal Bahadur')

@section('setup_content')

    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Edit Form</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="{{ route('forms.index') }}">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">
                                    <span class="badge fw-medium fs-2 bg-warning-subtle text-warning">
                                        Edit Form
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Start Edit Form Card -->
      <div class="card">
    <div class="card-body">
        <form action="{{ route('forms.update', $form->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <!-- Form Name -->
                <div class="col-md-6">
                    <label for="name" class="form-label">Course Name:</label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="{{ old('name', $form->name) }}" required>
                </div>

                <!-- Short Name -->
                <div class="col-md-6">
                    <label for="shortname" class="form-label">Form Name:</label>
                    <input type="text" class="form-control" id="shortname" name="shortname"
                           value="{{ old('shortname', $form->shortname) }}" required>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label for="description" class="form-label">Description:</label>
                    <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $form->description) }}</textarea>
                </div>

                <!-- Parent Form -->
                <div class="col-md-6">
                    <label for="parent_id" class="form-label">Parent Form:</label>
                    <select name="parent_id" id="parent_id" class="form-select">
                        <option value="">None (Top-level form)</option>
                        @foreach ($forms as $item)
                            <option value="{{ $item->id }}"
                                {{ old('parent_id', $form->parent_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Course Start Date -->
                <div class="col-md-6">
                    <label for="course_sdate" class="form-label">Course Start Date:</label>
                    <input type="date" class="form-control" id="course_sdate" name="course_sdate"
                           value="{{ old('course_sdate', $form->course_sdate) }}" required>
                </div>

                <!-- Course End Date -->
                <div class="col-md-6">
                    <label for="course_edate" class="form-label">Course End Date:</label>
                    <input type="date" class="form-control" id="course_edate" name="course_edate"
                           value="{{ old('course_edate', $form->course_edate) }}" required>
                </div>

                <!-- Visibility -->
                <div class="col-md-6 d-flex align-items-center pt-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="visible" name="visible"
                               {{ $form->visible ? 'checked' : '' }}>
                        <label class="form-check-label ms-2" for="visible">Show on Main Page</label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <hr class="mt-4">
            <div class="d-flex justify-content-end">
                <button class="btn btn-warning" type="submit">
                    <i class="material-icons menu-icon">edit</i> Update
                </button>
            </div>
        </form>
    </div>
</div>


        <!-- End Edit Form Card -->
    </div>

@endsection
