@extends('admin.layouts.master')

@section('title', 'Course Repositories | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Course Repository" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Course Repository</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                                    + Add New Category
                                </a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <hr>
                    
                    @if ($repositories->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                            <p class="text-muted mt-3">
                                No repositories found. <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#createModal">Create your first repository</a>
                            </p>
                        </div>
                    @else
                        <div id="zero_config_table">
                            <div class="table-responsive" style="overflow-x: auto">
                                <table class="table text-nowrap mb-0 align-middle" id="zero_config">
                                    <thead>
                                        <tr>
                                            <th class="text-center">S.No.</th>
                                            <th class="text-center">Image</th>
                                            <th class="text-center">Category Name</th>
                                            <th class="text-center">Details</th>
                                            <th class="text-center">Sub-Categories</th>
                                            <th class="text-center">Documents</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($repositories as $key => $repo)
                                        <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                            <td class="text-center">{{ $repositories->firstItem() + $key }}</td>
                                            <td class="text-center">
                                                @if($repo->category_image && \Storage::disk('public')->exists($repo->category_image))
                                                    <img src="{{ asset('storage/' . $repo->category_image) }}" alt="Category Image" 
                                                         style="max-width: 60px; max-height: 60px; border-radius: 4px;" 
                                                         class="img-thumbnail">
                                                @else
                                                    <span class="badge bg-secondary">No Image</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('course-repository.show', $repo->pk) }}" class="text-decoration-none">
                                                    {{ $repo->course_repository_name }}
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                {{ Str::limit($repo->course_repository_details ?? 'N/A', 50) }}
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary rounded-pill">{{ $repo->children->count() }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success rounded-pill">{{ $repo->getDocumentCount() }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Category actions">
                                                    <!-- Edit -->
                                                    <a href="javascript:void(0)" 
                                                       class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 edit-repo"
                                                       data-pk="{{ $repo->pk }}"
                                                       data-name="{{ $repo->course_repository_name }}"
                                                       data-details="{{ $repo->course_repository_details }}"
                                                       data-image="{{ $repo->category_image }}"
                                                       aria-label="Edit category">
                                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
                                                        <span class="d-none d-md-inline">Edit</span>
                                                    </a>

                                                    <!-- Delete -->
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 delete-repo"
                                                        data-pk="{{ $repo->pk }}"
                                                        aria-label="Delete category">
                                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                                        <span class="d-none d-md-inline">Delete</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-3">
                            {{ $repositories->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- end Zero Configuration -->
</div>

<!-- Create/Edit Category Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" id="createModalLabel"><i class="fas fa-plus"></i> <strong>Create New Category</strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createForm" method="POST" action="{{ route('course-repository.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal_course_repository_name" class="form-label"><strong>Category Name *</strong></label>
                        <input type="text" class="form-control" id="modal_course_repository_name" name="course_repository_name" 
                               required placeholder="Enter category name">
                    </div>
                    <div class="mb-3">
                        <label for="modal_course_repository_details" class="form-label"><strong>Details</strong></label>
                        <textarea class="form-control" id="modal_course_repository_details" name="course_repository_details" 
                                  rows="3" placeholder="Enter description (optional)"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="modal_category_image" class="form-label"><strong>Category Image</strong></label>
                        <input type="file" class="form-control" id="modal_category_image" name="category_image" 
                               accept="image/jpeg,image/png,image/jpg,image/gif">
                        <small class="text-muted d-block mt-1">Supported formats: JPEG, PNG, JPG, GIF (Max 2MB)</small>
                        <img id="preview_create" src="" alt="Preview" style="max-width: 100px; margin-top: 10px; display: none;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" id="editModalLabel"><i class="fas fa-edit"></i> <strong>Edit Category</strong></h5>
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
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #dc3545; color: white;">
                <h5 class="modal-title" id="uploadModalLabel"><i class="fas fa-upload"></i> Upload Document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body"> 
                    <div class="mb-3">
                        <label for="file" class="form-label"><strong>Select File *</strong></label>
                        <input type="file" class="form-control" id="file" name="file" required accept="*/*">
                        <small class="text-muted d-block mt-1">Max size: 100MB</small>
                    </div> 
                    <div class="mb-3">
                        <label for="file_title" class="form-label"><strong>File Title</strong></label>
                        <input type="text" class="form-control" id="file_title" name="file_title" 
                               placeholder="Enter file title (optional)">
                        <small class="text-muted d-block mt-1">Provide a descriptive title for the document</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
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
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
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
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save';
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
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save';
        });
    });
    
    // Edit form submit with SweetAlert
    document.getElementById('editForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
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
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Update';
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
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Update';
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
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        
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
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save';
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
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save';
        });
    });
});
</script>
@endsection

