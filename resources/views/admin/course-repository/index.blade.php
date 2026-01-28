@extends('admin.layouts.master')

@section('title', 'Course Repositories | Lal Bahadur')

@section('setup_content')
<style>
    /* Chevron-style divider */
.breadcrumb-divider-chevron {
    --bs-breadcrumb-divider: "›";
}

/* Accessible link styling */
.breadcrumb-link {
    color: #495057;
    text-decoration: none;
    font-weight: 500;
}

.breadcrumb-link:hover,
.breadcrumb-link:focus {
    color: #0d6efd;
    text-decoration: underline;
}

/* Ensure wrapping on small screens */
.breadcrumb {
    flex-wrap: wrap;
    row-gap: 0.25rem;
}

/* Active item emphasis */
.breadcrumb-item.active {
    color: #0d6efd;
}
.upload-zone {
    display: block;
    border: 2px dashed #cfe2ff;
    border-radius: 8px;
    padding: 2rem;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    background-color: #f8fbff;
}

.upload-zone:hover,
.upload-zone:focus-within {
    border-color: #0d6efd;
    background-color: #eef5ff;
}

.upload-icon {
    font-size: 40px;
    color: #0d6efd;
    margin-bottom: 0.5rem;
}

.upload-content p {
    margin-bottom: 0.25rem;
}
.upload-zone {
    display: block;
    border: 2px dashed #cfe2ff;
    border-radius: 12px;
    padding: 2rem;
    background-color: #f8fbff;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}

.upload-zone:hover,
.upload-zone:focus-within {
    background-color: #eef5ff;
}

.upload-zone-danger {
    border-color: #f5c2c7;
}

.upload-zone-danger:hover,
.upload-zone-danger:focus-within {
    border-color: #dc3545;
    background-color: #fff5f5;
}

.upload-icon {
    font-size: 42px;
    margin-bottom: 0.5rem;
}


</style>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3 px-4">
            <ol class="breadcrumb mb-0 breadcrumb-divider-chevron">
                
                <li class="breadcrumb-item">
                    <a href="{{ route('course-repository.index') }}"
                       class="breadcrumb-link">
                        Academics
                    </a>
                </li>

                <li class="breadcrumb-item">
                    <a href="{{ route('course-repository.index') }}"
                       class="breadcrumb-link">
                        MCTP
                    </a>
                </li>

                <li class="breadcrumb-item">
                    <a href="{{ route('course-repository.index') }}"
                       class="breadcrumb-link">
                        Course Repository Admin
                    </a>
                </li>

                <li class="breadcrumb-item active fw-semibold text-primary"
                    aria-current="page">
                    Course Repository
                </li>

            </ol>
        </div>
    </div>
</nav>


    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-4 p-md-5">
            <!-- Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <a href="javascript:void(0)" onclick="window.history.back()" class="p-2" aria-label="Go back">
                        <span class="material-icons material-symbols-rounded text-dark fs-6 text-primary">arrow_back_ios</span>
                    </a>
                    <div>
                        <h3 class="mb-0 fw-semibold text-primary">Course Repository</h3>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">

<!-- Upload Documents -->
<button type="button"
        class="btn btn-outline-primary btn-sm d-flex align-items-center"
        data-bs-toggle="modal"
        data-bs-target="#uploadModal"
        aria-label="Upload documents">
    <span class="material-icons material-symbols-rounded me-1 fs-6"
          aria-hidden="true">upload</span>
    Upload Documents
</button>

<!-- Add Category (Primary Action) -->
<button type="button"
        class="btn btn-primary btn-sm d-flex align-items-center"
        data-bs-toggle="modal"
        data-bs-target="#createModal"
        aria-label="Add new category">
    <span class="material-icons material-symbols-rounded me-1 fs-6"
          aria-hidden="true">add</span>
    Add Category
</button>

</div>

            </div>

            @if ($repositories->isEmpty())
                <div class="text-center py-5 px-3 rounded-3" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: #e2e8f0;">
                        <span class="material-icons material-symbols-rounded text-muted" style="font-size: 2.5rem;">folder_open</span>
                    </div>
                    <p class="text-muted mb-2 fw-medium">No categories yet</p>
                    <p class="text-muted small mb-3">Get started by creating your first category.</p>
                    <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <span class="material-icons material-symbols-rounded me-1">add_circle</span> Create category
                    </a>
                </div>
            @else
                <div class="table-responsive rounded-3 overflow-hidden border">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="col">#</th>
                                <th class="col">Image</th>
                                <th class="col">Category</th>
                                <th class="col">Sub-categories</th>
                                <th class="col">Documents</th>
                                <th class="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($repositories as $key => $repo)
                            <tr class="border-bottom border-light">
                                <td class="ps-4 text-muted">{{ $repositories->firstItem() + $key }}</td>
                                <td>
                                    @if($repo->category_image && \Storage::disk('public')->exists($repo->category_image))
                                        <img src="{{ asset('storage/' . $repo->category_image) }}" alt="{{ $repo->course_repository_name }}"
                                             class="rounded object-fit-cover" style="width: 48px; height: 48px;">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded bg-light text-muted small" style="width: 48px; height: 48px;">
                                            <span class="material-icons material-symbols-rounded">image</span>
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('course-repository.show', $repo->pk) }}" class="text-decoration-none fw-medium text-dark">
                                        {{ $repo->course_repository_name }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('course-repository.show', $repo->pk) }}" class="text-primary border-bottom">
                                        {{ $repo->children->count() }} - sub-categories
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('course-repository.show', $repo->pk) }}" class="text-primary border-bottom">{{ $repo->getDocumentCount() }} - documents</a>
                                </td>
                                <td class="pe-4">
                                    <div class="d-flex gap-1" role="group" aria-label="Category actions">
                                        <a href="javascript:void(0)" class="edit-repo"
                                           data-pk="{{ $repo->pk }}"
                                           data-name="{{ $repo->course_repository_name }}"
                                           data-details="{{ $repo->course_repository_details }}"
                                           data-image="{{ $repo->category_image }}"
                                           aria-label="Edit category" class="text-primary">
                                            <span class="material-icons material-symbols-rounded">edit</span>
                                        </a>
                                        <a class="delete-repo"
                                                data-pk="{{ $repo->pk }}"
                                                aria-label="Delete category" class="text-primary ms-2">
                                            <span class="material-icons material-symbols-rounded">delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mt-4 pt-3 border-top">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <p class="text-muted small mb-0">
                            Showing <span class="fw-medium">{{ $repositories->firstItem() }}</span>–<span class="fw-medium">{{ $repositories->lastItem() }}</span> of <span class="fw-medium">{{ $repositories->total() }}</span> categories
                        </p>
                        <div class="d-flex align-items-center gap-2">
                            <label for="per_page" class="text-muted small mb-0">Rows per page</label>
                            <select id="per_page" class="form-select form-select-sm" style="width: auto;" aria-label="Rows per page">
                                @foreach([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 15) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <nav aria-label="Course repository pagination" class="mt-2 mt-sm-0">
                        {{ $repositories->links('pagination::bootstrap-5') }}
                    </nav>
                </div>
                <script>
                document.getElementById('per_page')?.addEventListener('change', function() {
                    const url = new URL(window.location.href);
                    url.searchParams.set('per_page', this.value);
                    url.searchParams.set('page', '1');
                    window.location.href = url.toString();
                });
                </script>
            @endif
        </div>
    </div>
</div>

<!-- Create/Edit Category Modal -->
<div class="modal fade" id="createModal" tabindex="-1"
     aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-sm">

            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title fw-semibold d-flex align-items-center"
                    id="createModalLabel">
                    <span class="material-icons material-symbols-rounded me-2 text-primary">
                        add_circle
                    </span>
                    Create New Category <span class="text-danger ms-1">*</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>

            <!-- Form -->
            <form method="POST"
                  action="{{ route('course-repository.store') }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="modal-body p-4">

                    <!-- Category Name -->
                    <div class="mb-3">
                        <label for="modal_course_repository_name"
                               class="form-label fw-medium">
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control"
                               id="modal_course_repository_name"
                               name="course_repository_name"
                               placeholder="Enter category name"
                               required>
                    </div>

                    <!-- Description (Optional – matches screenshot style) -->
                    <div class="mb-4">
                        <label for="category_description"
                               class="form-label fw-medium">
                            Description
                        </label>
                        <textarea class="form-control"
                                  id="category_description"
                                  name="description"
                                  rows="3"
                                  placeholder="Enter short description (optional)"></textarea>
                    </div>

                    <!-- Upload Section -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            Upload Photo Attachment <span class="text-danger">*</span>
                        </label>

                        <label for="modal_category_image"
                               class="upload-zone">
                            <div class="upload-content text-center">
                                <span class="material-icons material-symbols-rounded upload-icon">
                                    cloud_upload
                                </span>
                                <p class="mb-1 fw-medium">
                                    <span class="text-primary">Click to upload</span>
                                    or drag and drop
                                </p>
                                <small class="text-muted">
                                    JPEG, PNG, JPG, GIF (Max 2MB)
                                </small>
                            </div>

                            <input type="file"
                                   id="modal_category_image"
                                   name="category_image"
                                   accept="image/jpeg,image/png,image/jpg,image/gif"
                                   hidden
                                   required>
                        </label>

                        <!-- Preview -->
                        <div class="mt-3 d-flex align-items-center gap-3">
                            <img id="preview_create"
                                 alt="Image preview"
                                 class="img-thumbnail d-none"
                                 style="max-width: 120px;">
                        </div>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light border-top">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        Back
                    </button>
                    <button type="submit"
                            class="btn btn-success d-flex align-items-center">
                        <span class="material-icons material-symbols-rounded me-1">
                            check_circle
                        </span>
                        Save
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('modal_category_image')
    .addEventListener('change', function (e) {

        const file = e.target.files[0];
        const preview = document.getElementById('preview_create');

        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
</script>
<script>
document.getElementById('file')
    .addEventListener('change', function () {
        if (this.files.length > 0) {
            const label = this.closest('.upload-zone')
                .querySelector('p');
            label.textContent = this.files[0].name;
        }
    });
</script>


<!-- Edit Category Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" id="editModalLabel"><span class="material-icons material-symbols-rounded me-1">edit</span> <strong>Edit Category</strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_course_repository_name" class="form-label"><strong>Category Name *</strong></label>
                        <input type="text" class="form-control" id="edit_course_repository_name" name="course_repository_name" 
                               required placeholder="Enter category name">
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_repository_details" class="form-label"><strong>Details</strong></label>
                        <textarea class="form-control" id="edit_course_repository_details" name="course_repository_details" 
                                  rows="3" placeholder="Enter description (optional)"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category_image" class="form-label"><strong>Category Image</strong></label>
                        <div id="current_image_container" style="margin-bottom: 10px; display: none;">
                            <p class="text-muted"><small>Current Image:</small></p>
                            <img id="current_image" src="" alt="Current" style="max-width: 100px; border-radius: 4px;">
                        </div>
                        <input type="file" class="form-control" id="edit_category_image" name="category_image" 
                               accept="image/jpeg,image/png,image/jpg,image/gif">
                        <small class="text-muted d-block mt-1">Supported formats: JPEG, PNG, JPG, GIF (Max 2MB)</small>
                        <img id="preview_edit" src="" alt="Preview" style="max-width: 100px; margin-top: 10px; display: none;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                    <button type="submit" class="btn btn-success">
                        <span class="material-icons material-symbols-rounded me-1">check_circle</span> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1"
     aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">

            <!-- Header -->
            <div class="modal-header bg-danger bg-gradient text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-semibold d-flex align-items-center"
                    id="uploadModalLabel">
                    <span class="material-icons material-symbols-rounded me-2 fs-5">
                        cloud_upload
                    </span>
                    Upload Document
                </h5>
                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>

            <!-- Form -->
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf

                <div class="modal-body p-4">

                    <!-- Upload File -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Select File <span class="text-danger">*</span>
                        </label>

                        <label for="file"
                               class="upload-zone upload-zone-danger">

                            <div class="text-center">
                                <span class="material-icons material-symbols-rounded upload-icon text-danger">
                                    upload_file
                                </span>
                                <p class="mb-1 fw-medium">
                                    <span class="text-danger">Click to upload</span>
                                    or drag and drop
                                </p>
                                <small class="text-muted">
                                    Maximum file size: 100 MB
                                </small>
                            </div>

                            <input type="file"
                                   id="file"
                                   name="file"
                                   accept="*/*"
                                   required
                                   hidden>
                        </label>
                    </div>

                    <!-- File Title -->
                    <div class="mb-3">
                        <div class="form-floating">
                            <input type="text"
                                   class="form-control"
                                   id="file_title"
                                   name="file_title"
                                   placeholder="File Title">
                            <label for="file_title">
                                <span class="material-icons material-symbols-rounded me-1 fs-6">
                                    title
                                </span>
                                File Title (Optional)
                            </label>
                        </div>
                        <small class="text-muted d-block mt-2">
                            Provide a descriptive title for easier identification
                        </small>
                    </div>

                </div>

                <!-- Footer -->
                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button"
                            class="btn btn-outline-secondary rounded-pill px-4"
                            data-bs-dismiss="modal">
                        Back
                    </button>
                    <button type="submit"
                            class="btn btn-success rounded-pill px-4 shadow-sm">
                        <span class="material-icons material-symbols-rounded me-1 fs-6">
                            check_circle
                        </span>
                        Save
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>


@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Image preview for create modal
    document.getElementById('modal_category_image')?.addEventListener('change', function(e) {
        const preview = document.getElementById('preview_create');
        const file = e.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                preview.src = event.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });
    
    // Image preview for edit modal
    document.getElementById('edit_category_image')?.addEventListener('change', function(e) {
        const preview = document.getElementById('preview_edit');
        const file = e.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                preview.src = event.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });
    
    // Edit button functionality
    document.querySelectorAll('.edit-repo').forEach(btn => {
        btn.addEventListener('click', function() {
            const pk = this.getAttribute('data-pk');
            const name = this.getAttribute('data-name');
            const details = this.getAttribute('data-details');
            const image = this.getAttribute('data-image');
            
            // Clear any previous image
            document.getElementById('preview_edit').style.display = 'none';
            document.getElementById('edit_category_image').value = '';
            
            // Populate edit form
            document.getElementById('edit_course_repository_name').value = name;
            document.getElementById('edit_course_repository_details').value = details || '';
            
            // Show current image if exists
            const currentImageContainer = document.getElementById('current_image_container');
            if (image && image.trim() !== '') {
                document.getElementById('current_image').src = '/storage/' + image;
                currentImageContainer.style.display = 'block';
            } else {
                currentImageContainer.style.display = 'none';
            }
            
            // Update form action
            const editForm = document.getElementById('editForm');
            editForm.action = `/course-repository/${pk}`;
            
            // Show modal
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        });
    });
    
    // Create form submit with SweetAlert
    document.getElementById('createForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-icons material-symbols-rounded me-1">schedule</span> Saving...';
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Category created successfully',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to create category'
                });
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="material-icons material-symbols-rounded me-1">check_circle</span> Save';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to create category'
            });
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="material-icons material-symbols-rounded me-1">check_circle</span> Save';
        });
    });
    
    // Edit form submit with SweetAlert
    document.getElementById('editForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-icons material-symbols-rounded me-1">schedule</span> Updating...';
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Category updated successfully',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to update category'
                });
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="material-icons material-symbols-rounded me-1">check_circle</span> Save';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to update category'
            });
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="material-icons material-symbols-rounded me-1">check_circle</span> Save';
        });
    });
    
    // Delete button functionality with SweetAlert
    document.querySelectorAll('.delete-repo').forEach(btn => {
        btn.addEventListener('click', function() {
            const pk = this.getAttribute('data-pk');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create a form and submit it
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/course-repository/${pk}`;
                    
                    const csrfToken = document.querySelector('[name="_token"]').value;
                    
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="DELETE">
                    `;
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
    
    // Upload form functionality with SweetAlert
    document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const parentPk = '{{ $parentRepository->pk ?? 0 }}';
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-icons material-symbols-rounded me-1">schedule</span> Uploading...';
        
        const url = parentPk && parentPk != '0' 
            ? `/course-repository/${parentPk}/upload-document`
            : '/course-repository/0/upload-document';
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Document uploaded successfully',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.error || 'Upload failed'
                });
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="material-icons material-symbols-rounded me-1">check_circle</span> Save';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Upload failed'
            });
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="material-icons material-symbols-rounded me-1">check_circle</span> Save';
        });
    });
});
</script>
@endsection
