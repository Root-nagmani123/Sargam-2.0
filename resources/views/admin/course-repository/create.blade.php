@extends('admin.layouts.master')

@section('title', 'Course Repositories Create | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Create New Category" />
    <div class="datatables"> 
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row justify-content-center">
                    <div class="col-md-8"> 
                        @if ($parentRepository)
                            <div class="alert alert-info mb-4" style="background-color: #e7f3ff; border-color: #b3d9ff;">
                                <i class="fas fa-info-circle"></i>
                                <strong>Parent:</strong> {{ $parentRepository->course_repository_name }}
                            </div>
                        @endif

                        <form action="{{ route('course-repository.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label for="course_repository_name" class="form-label"><strong>Category Name *</strong></label>
                                <input type="text" class="form-control @error('course_repository_name') is-invalid @enderror" 
                                       id="course_repository_name" name="course_repository_name" 
                                       value="{{ old('course_repository_name') }}" required placeholder="Enter category name">
                                @error('course_repository_name')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="course_repository_details" class="form-label"><strong>Details</strong></label>
                                <textarea class="form-control @error('course_repository_details') is-invalid @enderror" 
                                          id="course_repository_details" name="course_repository_details" 
                                          rows="3" placeholder="Enter description (optional)">{{ old('course_repository_details') }}</textarea>
                                @error('course_repository_details')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="category_image" class="form-label"><strong>Category Image</strong></label>
                                <input type="file" class="form-control @error('category_image') is-invalid @enderror" 
                                       id="category_image" name="category_image" 
                                       accept="image/jpeg,image/png,image/jpg,image/gif">
                                <small class="text-muted d-block mt-1">Supported formats: JPEG, PNG, JPG, GIF (Max 2MB)</small>
                                @error('category_image')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                                <img id="preview_image" src="" alt="Preview" style="max-width: 150px; margin-top: 10px; display: none; border-radius: 4px;" class="img-thumbnail">
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                @if ($parentRepository)
                                    <a href="{{ route('course-repository.show', $parentRepository->pk) }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </a>
                                @else
                                    <a href="{{ route('course-repository.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image preview for create form
    document.getElementById('category_image')?.addEventListener('change', function(e) {
        const preview = document.getElementById('preview_image');
        const file = e.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });
});
</script>
@endsection
