@extends('admin.layouts.master')

@section('title', 'Course Repositories | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb Navigation -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('course-repository.index') }}" class="text-decoration-none text-muted">Academics</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('course-repository.index') }}" class="text-decoration-none text-muted">MCTP</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('course-repository.index') }}" class="text-decoration-none text-muted">Course Repository Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Course Repository</li>
                </ol>
            </nav>
            <button class="btn btn-link p-0" aria-label="Search">
                <i class="bi bi-search fs-5"></i>
            </button>
        </div>
    </div>

    <div class="datatables">
        <div class="card shadow-sm" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <!-- Page Title and Actions -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" onclick="window.history.back()" class="btn btn-link p-0 text-decoration-none">
                            <i class="bi bi-arrow-left fs-4 text-dark"></i>
                        </button>
                        <h4 class="mb-0 fw-bold">Course Repository</h4>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="javascript:void(0)" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="bi bi-upload me-1"></i> Upload Documents
                        </a>
                        <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                            <i class="bi bi-plus-lg me-1"></i> Add Category
                        </a>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3" id="repositoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab" aria-controls="active" aria-selected="true">
                            Active
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive" type="button" role="tab" aria-controls="archive" aria-selected="false">
                            Archive
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="repositoryTabContent">
                    <!-- Active Tab -->
                    <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
                        @if ($repositories->isEmpty())
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                                <p class="text-muted mt-3">
                                    No repositories found. <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#createModal">Create your first repository</a>
                                </p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead style="background-color: #dc3545; color: white;">
                                        <tr>
                                            <th class="text-center fw-bold">S.No.</th>
                                            <th class="text-center fw-bold">Category Name</th>
                                            <th class="text-center fw-bold">Details</th>
                                            <th class="text-center fw-bold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($repositories as $key => $repo)
                                        <tr class="{{ $loop->odd ? 'table-light' : '' }}">
                                            <td class="text-center">{{ $repositories->firstItem() + $key }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('course-repository.show', $repo->pk) }}" class="text-decoration-none fw-semibold">
                                                    {{ $repo->course_repository_name }}
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('course-repository.show', $repo->pk) }}" class="text-decoration-none text-primary">
                                                    {{ $repo->children->count() }} sub-categories
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-inline-flex align-items-center gap-2">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-link p-1 edit-repo"
                                                            data-pk="{{ $repo->pk }}"
                                                            data-name="{{ $repo->course_repository_name }}"
                                                            data-details="{{ $repo->course_repository_details }}"
                                                            aria-label="Edit category">
                                                        <i class="bi bi-pencil-fill" style="font-size: 18px;"></i>
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-sm btn-link p-1 delete-repo"
                                                            data-pk="{{ $repo->pk }}"
                                                            aria-label="Delete category">
                                                        <i class="bi bi-trash-fill" style="font-size: 18px;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div></div>
                                <nav aria-label="Page navigation">
                                    {{ $repositories->links('pagination::bootstrap-5') }}
                                </nav>
                                <div class="text-muted small">
                                    Showing {{ $repositories->firstItem() }} to {{ $repositories->lastItem() }} of {{ $repositories->total() }} items
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Archive Tab -->
                    <div class="tab-pane fade" id="archive" role="tabpanel" aria-labelledby="archive-tab">
                        <div class="text-center py-5">
                            <i class="bi bi-archive" style="font-size: 48px; color: #ccc;"></i>
                            <p class="text-muted mt-3">No archived repositories found.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Category Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" id="createModalLabel"><i class="bi bi-plus-lg"></i> <strong>Create New Category *</strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createForm" method="POST" action="{{ route('course-repository.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal_course_repository_name" class="form-label"><strong>Category Name *</strong></label>
                        <input type="text" class="form-control" id="modal_course_repository_name" name="course_repository_name" 
                               required placeholder="Enter Name">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Save
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
                <h5 class="modal-title" id="editModalLabel"><i class="bi bi-pencil-fill"></i> <strong>Edit Category</strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" method="POST">
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Save
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
                <h5 class="modal-title" id="uploadModalLabel"><i class="bi bi-upload"></i> Upload Document</h5>
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
                        <i class="bi bi-check-lg"></i> Save
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
    
    // Edit button functionality
    document.querySelectorAll('.edit-repo').forEach(btn => {
        btn.addEventListener('click', function() {
            const pk = this.getAttribute('data-pk');
            const name = this.getAttribute('data-name');
            const details = this.getAttribute('data-details');
            
            // Populate edit form
            document.getElementById('edit_course_repository_name').value = name;
            document.getElementById('edit_course_repository_details').value = details || '';
            
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
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
        
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
                submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Save';
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
            submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Save';
        });
    });
    
    // Edit form submit with SweetAlert
    document.getElementById('editForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Updating...';
        
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
                submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Save';
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
            submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Save';
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
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';
        
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
                submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Save';
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
            submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Save';
        });
    });
});
</script>
@endsection
