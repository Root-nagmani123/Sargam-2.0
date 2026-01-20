@extends('admin.layouts.master')

@section('title', 'Course Repositories Edit | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Edit Category" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <form action="{{ route('course-repository.update', $repository->pk) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="course_repository_name" class="form-label"><strong>Category Name *</strong></label>
                                <input type="text" class="form-control @error('course_repository_name') is-invalid @enderror" 
                                       id="course_repository_name" name="course_repository_name" 
                                       value="{{ old('course_repository_name', $repository->course_repository_name) }}" required>
                                @error('course_repository_name')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="course_repository_details" class="form-label"><strong>Details</strong></label>
                                <textarea class="form-control @error('course_repository_details') is-invalid @enderror" 
                                          id="course_repository_details" name="course_repository_details" 
                                          rows="3">{{ old('course_repository_details', $repository->course_repository_details) }}</textarea>
                                @error('course_repository_details')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <a href="{{ route('course-repository.show', $repository->pk) }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
