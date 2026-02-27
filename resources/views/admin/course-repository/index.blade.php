@extends('admin.layouts.master')

@section('title', 'Course Repositories | Lal Bahadur')

@section('setup_content')
<style>
    /* Chevron-style divider */
    .breadcrumb-divider-chevron {
        --bs-breadcrumb-divider: ">";
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

    /* Modal Header - Blue Gradient */
    .upload-modal-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        padding: 1.5rem !important;
    }

    .upload-modal-header .header-icon-circle {
        width: 40px;
        height: 40px;
        min-width: 40px;
        border-radius: 50%;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.5rem;
    }

    .upload-modal-header .header-icon-circle .material-icons,
    .upload-modal-header .header-icon-circle .material-symbols-rounded {
        color: #0d6efd;
        font-size: 1.3rem !important;
    }

    .upload-modal-header .modal-title {
        color: #fff;
        font-weight: 600;
        font-size: 1.25rem;
        margin: 0;
    }

    .upload-modal-header .btn-close-white {
        opacity: 0.9;
    }

    /* Upload Zone */
    .upload-zone-ref {
        display: block;
        border: 2px dashed #b6d4fe;
        border-radius: 12px;
        background-color: #f8fbff;
        cursor: pointer;
        transition: border-color 0.2s, background-color 0.2s;
        min-height: 180px;
        padding: 0;
    }

    .upload-zone-ref:hover,
    .upload-zone-ref:focus-within {
        border-color: #0d6efd;
        background-color: #eef5ff;
    }

    .upload-zone-ref.upload-dragover {
        border-color: #0d6efd;
        background-color: #eef5ff;
    }

    .upload-zone-inner {
        cursor: pointer;
        height: 100%;
    }

    .upload-icon-ref {
        font-size: 48px;
        display: block;
        color: #0d6efd;
    }

    .upload-zone-ref .upload-cta {
        color: #0d6efd;
    }

    /* Form Controls */
    .form-control-lg {
        border-color: #e0e0e0;
        border-radius: 0.5rem;
    }

    .form-control-lg:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    /* Buttons */
    .btn-cancel-ref {
        background-color: #fff;
        border: 1px solid #dc3545;
        color: #dc3545;
        border-radius: 0.5rem;
        font-weight: 500;
    }

    .btn-cancel-ref:hover {
        background-color: #fff5f5;
        border-color: #dc3545;
        color: #b02a37;
    }
</style>
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
    </nav>


    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-4 p-md-5">
            <!-- Header -->
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <a href="javascript:void(0)" onclick="window.history.back()" class="p-2" aria-label="Go back">
                        <span
                            class="material-icons material-symbols-rounded text-dark fs-6 text-primary">arrow_back_ios</span>
                    </a>
                    <div>
                        <h3 class="mb-0 fw-semibold text-primary">Course Repository</h3>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">

                    <!-- Upload Documents -->
                    <!-- <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center"
                        data-bs-toggle="modal" data-bs-target="#uploadModal" aria-label="Upload documents">
                        <span class="material-icons material-symbols-rounded me-1 fs-6" aria-hidden="true">upload</span>
                        Upload Documents
                    </button> -->

                    <!-- Add Category (Primary Action) -->
                    <button type="button" class="btn btn-primary btn-sm d-flex align-items-center"
                        data-bs-toggle="modal" data-bs-target="#createModal" aria-label="Add new category">
                        <span class="material-icons material-symbols-rounded me-1 fs-6" aria-hidden="true">add</span>
                        Add Category
                    </button>

                </div>

            </div>

            @if ($repositories->isEmpty())
            <div class="text-center py-5 px-3 rounded-3"
                style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                    style="width: 80px; height: 80px; background: #e2e8f0;">
                    <span class="material-icons material-symbols-rounded text-muted"
                        style="font-size: 2.5rem;">folder_open</span>
                </div>
                <p class="text-muted mb-2 fw-medium">No categories yet</p>
                <p class="text-muted small mb-3">Get started by creating your first category.</p>
                <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#createModal">
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
                                <img src="{{ asset('storage/' . $repo->category_image) }}"
                                    alt="{{ $repo->course_repository_name }}" class="rounded object-fit-cover"
                                    style="width: 48px; height: 48px;">
                                @else
                                <span
                                    class="d-inline-flex align-items-center justify-content-center rounded bg-light text-muted small"
                                    style="width: 48px; height: 48px;">
                                    <span class="material-icons material-symbols-rounded">image</span>
                                </span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('course-repository.show', $repo->pk) }}"
                                    class="text-decoration-none fw-medium text-dark">
                                    {{ $repo->course_repository_name }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('course-repository.show', $repo->pk) }}"
                                    class="text-primary border-bottom">
                                    {{ $repo->children->count() }} - sub-categories
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('course-repository.show', $repo->pk) }}"
                                    class="text-primary border-bottom">{{ $repo->getDocumentCount() }} - documents</a>
                            </td>
                            <td class="pe-4">
                                <div class="d-flex gap-1" role="group" aria-label="Category actions">
                                    <a href="javascript:void(0)" class="edit-repo text-primary" data-pk="{{ $repo->pk }}"
                                        data-name="{{ $repo->course_repository_name }}"
                                        data-details="{{ $repo->course_repository_details }}"
                                        data-image="{{ $repo->category_image }}" aria-label="Edit category">
                                        <span class="material-icons material-symbols-rounded">edit</span>
                                    </a>
                                    <a class="delete-repo text-primary ms-2" data-pk="{{ $repo->pk }}" aria-label="Delete category">
                                        <span class="material-icons material-symbols-rounded">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div
                class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mt-4 pt-3 border-top">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <p class="text-muted small mb-0">
                        Showing <span class="fw-medium">{{ $repositories->firstItem() }}</span>–<span
                            class="fw-medium">{{ $repositories->lastItem() }}</span> of <span
                            class="fw-medium">{{ $repositories->total() }}</span> categories
                    </p>
                    <div class="d-flex align-items-center gap-2">
                        <label for="per_page" class="text-muted small mb-0">Rows per page</label>
                        <select id="per_page" class="form-select form-select-sm" style="width: auto;"
                            aria-label="Rows per page">
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

<!-- Create Category Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" id="createModalLabel"><i class="bi bi-plus-lg"></i> <strong>Create New Category *</strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Form -->
            <form method="POST" id="createForm" action="{{ route('course-repository.store') }}"
                enctype="multipart/form-data">
                @csrf

                <div class="modal-body p-4">

                    <!-- Category Name -->
                    <div class="mb-3">
                        <label for="modal_course_repository_name" class="form-label"><strong>Category Name *</strong></label>
                        <input type="text" class="form-control" id="modal_course_repository_name" name="course_repository_name" 
                               required placeholder="Enter Name">
                    </div>

                    <!-- Category Image Section -->
                    <div class="mb-0">
                        <label class="form-label fw-medium text-dark mb-3 d-block">
                            Category Image
                        </label>

                        <label for="modal_category_image" class="upload-zone-ref d-block mb-0">
                            <div class="upload-zone-inner d-flex align-items-center justify-content-center">
                                <div class="text-center">
                                    <span class="material-icons material-symbols-rounded upload-icon-ref mb-2 d-block">
                                        cloud_upload
                                    </span>
                                    <p class="mb-1 fw-medium upload-cta">
                                        Click to upload or drag and drop
                                    </p>
                                    <small class="text-muted">
                                        JPEG, PNG, JPG, GIF (Max 2MB)
                                    </small>
                                </div>
                            </div>

                            <input type="file" id="modal_category_image" name="category_image"
                                accept="image/jpeg,image/png,image/jpg,image/gif" class="visually-hidden" required>
                        </label>

                        <!-- Preview -->
                        <div class="mt-3">
                            <img id="preview_create" alt="Image preview" class="img-fluid rounded-2 d-none"
                                style="max-width: 120px; object-fit: cover;">
                        </div>
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

            <!-- Form -->
            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="modal-body p-4">

                    <!-- Category Name -->
                    <div class="mb-3">
                        <label for="edit_course_repository_name" class="form-label fw-medium text-dark mb-2">
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-lg"
                            id="edit_course_repository_name" name="course_repository_name"
                            placeholder="Enter category name" required>
                    </div>

                    <!-- Details (Optional) -->
                    <div class="mb-3">
                        <label for="edit_course_repository_details" class="form-label fw-medium text-dark mb-2">
                            Details <span class="text-muted small">(Optional)</span>
                        </label>
                        <textarea class="form-control form-control-lg"
                            id="edit_course_repository_details" name="course_repository_details" rows="3"
                            placeholder="Enter category details"></textarea>
                    </div>

                    <!-- Category Image Section -->
                    <div class="mb-0">
                        <label class="form-label fw-medium text-dark mb-3 d-block">
                            Category Image
                        </label>

                        <!-- Current Image Display -->
                        <div id="current_image_container" class="mb-3" style="display: none;">
                            <p class="text-muted small mb-2">Current Image:</p>
                            <img id="current_image" src="" alt="Current" class="img-fluid rounded-2"
                                style="max-width: 120px; object-fit: cover;">
                        </div>

                        <!-- Upload Zone -->
                        <label for="edit_category_image" class="upload-zone-ref d-block mb-0">
                            <div class="upload-zone-inner d-flex align-items-center justify-content-center">
                                <div class="text-center">
                                    <span class="material-icons material-symbols-rounded upload-icon-ref mb-2 d-block">
                                        cloud_upload
                                    </span>
                                    <p class="mb-1 fw-medium upload-cta">
                                        Click to upload or drag and drop
                                    </p>
                                    <small class="text-muted">
                                        JPEG, PNG, JPG, GIF (Max 2MB)
                                    </small>
                                </div>
                            </div>

                            <input type="file" class="visually-hidden" id="edit_category_image" name="category_image"
                                accept="image/jpeg,image/png,image/jpg,image/gif">
                        </label>

                        <!-- Preview -->
                        <div class="mt-3">
                            <img id="preview_edit" src="" alt="Preview" class="img-fluid rounded-2 d-none"
                                style="max-width: 120px; object-fit: cover;">
                        </div>
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

                <div class="modal-body p-4">

                    <!-- File Title (Optional) -->
                    <div class="mb-3">
                        <label for="file_title" class="form-label fw-medium text-dark mb-2">
                            File Title <span class="text-muted small">(Optional)</span>
                        </label>
                        <input type="text" class="form-control form-control-lg" id="file_title"
                            name="file_title" placeholder="Enter file title">
                    </div>

                    <!-- Select File -->
                    <div class="mb-0">
                        <label class="form-label fw-medium text-dark mb-3 d-block">
                            Select File <span class="text-danger">*</span>
                        </label>

                        <div id="uploadDropzone" class="upload-zone-ref">
                            <label for="file" class="upload-zone-inner d-flex align-items-center justify-content-center mb-0">
                                <input type="file" id="file" name="file" accept="*/*" required class="visually-hidden">
                                <div class="text-center">
                                    <span class="material-icons material-symbols-rounded upload-icon-ref mb-2 d-block">
                                        cloud_upload
                                    </span>
                                    <p class="mb-1 fw-medium upload-cta" id="uploadZoneText">
                                        Click to upload or drag and drop
                                    </p>
                                    <p class="mb-0 small fw-medium text-success d-none" id="uploadFileName"></p>
                                    <small class="text-muted">
                                        PDF, DOC, DOCX, XLS, XLSX, images, etc. (Max 100 MB)
                                    </small>
                                </div>
                            </label>
                        </div>
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
(function() {
    // Use Laravel-generated URLs so prefix (e.g. /admin) is correct
    var courseRepoUpdateUrlTemplate = "{{ route('course-repository.update', ['pk' => '___PK___']) }}";
    var courseRepoDestroyUrlTemplate = "{{ route('course-repository.destroy', ['pk' => '___PK___']) }}";
    window.getCourseRepoUpdateUrl = function(pk) { return courseRepoUpdateUrlTemplate.replace('___PK___', pk); };
    window.getCourseRepoDestroyUrl = function(pk) { return courseRepoDestroyUrlTemplate.replace('___PK___', pk); };
})();
document.addEventListener('DOMContentLoaded', function() {

    // Image preview for create modal
    document.getElementById('modal_category_image')?.addEventListener('change', function(e) {
        const preview = document.getElementById('preview_create');
        const file = e.target.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                preview.src = event.target.result;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            preview.classList.add('d-none');
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
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            preview.classList.add('d-none');
        }
    });

    // Edit button functionality
    document.querySelectorAll('.edit-repo').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
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
            const currentImageEl = document.getElementById('current_image');
            if (currentImageContainer && currentImageEl) {
                if (image && image.trim() !== '') {
                    currentImageEl.src = '/storage/' + image;
                    currentImageContainer.style.display = 'block';
                } else {
                    currentImageContainer.style.display = 'none';
                }
            }

            // Update form action (use Laravel route so URL is correct)
            const editForm = document.getElementById('editForm');
            editForm.action = window.getCourseRepoUpdateUrl(pk);

            // Show modal
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        });
    });

    // Drag and drop for create modal
    const createModalImageInput = document.getElementById('modal_category_image');
    if (createModalImageInput) {
        const createUploadZone = document.querySelector('#createModal .upload-zone-ref');
        if (createUploadZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(event => {
                createUploadZone.addEventListener(event, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
            ['dragenter', 'dragover'].forEach(event => {
                createUploadZone.addEventListener(event, () => {
                    createUploadZone.classList.add('upload-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(event => {
                createUploadZone.addEventListener(event, () => {
                    createUploadZone.classList.remove('upload-dragover');
                });
            });
            createUploadZone.addEventListener('drop', (e) => {
                const files = e.dataTransfer?.files;
                if (files?.length) {
                    const dt = new DataTransfer();
                    dt.items.add(files[0]);
                    createModalImageInput.files = dt.files;
                    createModalImageInput.dispatchEvent(new Event('change'));
                }
            });
        }
    }

    // Drag and drop for edit modal
    const editModalImageInput = document.getElementById('edit_category_image');
    if (editModalImageInput) {
        const editUploadZone = document.querySelector('#editModal .upload-zone-ref');
        if (editUploadZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(event => {
                editUploadZone.addEventListener(event, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
            ['dragenter', 'dragover'].forEach(event => {
                editUploadZone.addEventListener(event, () => {
                    editUploadZone.classList.add('upload-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(event => {
                editUploadZone.addEventListener(event, () => {
                    editUploadZone.classList.remove('upload-dragover');
                });
            });
            editUploadZone.addEventListener('drop', (e) => {
                const files = e.dataTransfer?.files;
                if (files?.length) {
                    const dt = new DataTransfer();
                    dt.items.add(files[0]);
                    editModalImageInput.files = dt.files;
                    editModalImageInput.dispatchEvent(new Event('change'));
                }
            });
        }
    }
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
                    submitBtn.innerHTML =
                        '<span class="material-icons material-symbols-rounded me-1">check_circle</span> Save';
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
            .then(function(response) {
                return response.text().then(function(text) {
                    var data = null;
                    try {
                        data = text ? JSON.parse(text) : {};
                    } catch (e) {
                        return { ok: response.ok, status: response.status, message: text || 'Server error' };
                    }
                    return { ok: response.ok, status: response.status, data: data, raw: text };
                });
            })
            .then(function(result) {
                if (result.data && result.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: result.data.message || 'Category updated successfully',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        location.reload();
                    });
                    return;
                }
                var errMsg = 'Failed to update category';
                if (result.data) {
                    if (result.data.message) errMsg = result.data.message;
                    if (result.data.errors && typeof result.data.errors === 'object') {
                        var first = Object.keys(result.data.errors).map(function(k) { return result.data.errors[k][0]; })[0];
                        if (first) errMsg = first;
                    }
                } else if (result.status === 419) {
                    errMsg = 'Session expired. Please refresh the page and try again.';
                } else if (result.status === 422) {
                    errMsg = 'Validation failed. Please check your input.';
                } else if (result.message && result.message.length < 200) {
                    errMsg = result.message;
                }
                Swal.fire({ icon: 'error', title: 'Error!', text: errMsg });
                submitBtn.disabled = false;
                submitBtn.innerHTML =
                    '<span class="material-icons material-symbols-rounded me-1">check_circle</span> Save';
            })
            .catch(function(error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to update category. Please try again.'
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
        btn.addEventListener('click', function(e) {
            e.preventDefault();
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
                    // Create a form and submit it (use Laravel route so URL is correct)
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = window.getCourseRepoDestroyUrl(pk);

                    var csrfToken = (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || (document.querySelector('[name="_token"]') && document.querySelector('[name="_token"]').value);
                    if (!csrfToken) {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Security token missing. Please refresh the page.' });
                        return;
                    }
                    form.innerHTML = '<input type="hidden" name="_token" value="' + csrfToken + '"><input type="hidden" name="_method" value="DELETE">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });

    // Upload modal – file input, drag-and-drop, filename display
    (function initUploadModal() {
        const fileInput = document.getElementById('file');
        const fileTitleInput = document.getElementById('file_title');
        const dropzone = document.getElementById('uploadDropzone');
        const zoneText = document.getElementById('uploadZoneText');
        const fileNameEl = document.getElementById('uploadFileName');
        const uploadModal = document.getElementById('uploadModal');

        function setFileLabel(name) {
            if (name) {
                zoneText.classList.add('d-none');
                fileNameEl.textContent = 'Selected: ' + name;
                fileNameEl.classList.remove('d-none');
            } else {
                zoneText.classList.remove('d-none');
                fileNameEl.classList.add('d-none');
                fileNameEl.textContent = '';
            }
        }

        function clearUploadZone() {
            if (fileInput) fileInput.value = '';
            if (fileTitleInput) fileTitleInput.value = '';
            setFileLabel(null);
            dropzone?.classList.remove('upload-dragover');
        }

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                setFileLabel(this.files.length ? this.files[0].name : null);
            });
        }

        if (dropzone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(ev) {
                dropzone.addEventListener(ev, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
            ['dragenter', 'dragover'].forEach(function(ev) {
                dropzone.addEventListener(ev, function() {
                    dropzone.classList.add('upload-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function(ev) {
                dropzone.addEventListener(ev, function() {
                    dropzone.classList.remove('upload-dragover');
                });
            });
            dropzone.addEventListener('drop', function(e) {
                const files = e.dataTransfer?.files;
                if (files?.length && fileInput) {
                    var dt = new DataTransfer();
                    dt.items.add(files[0]);
                    fileInput.files = dt.files;
                    setFileLabel(files[0].name);
                }
            });
        }

        if (uploadModal) {
            uploadModal.addEventListener('hidden.bs.modal', clearUploadZone);
        }
    })();

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
                    submitBtn.innerHTML =
                        '<span class="material-icons material-symbols-rounded me-1">check_circle</span> Save';
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
