@extends('admin.layouts.master')

@push('styles')
<style>
/* Fallback styles for missing CSS files */
.fallback-styles {
    /* This ensures basic styling even if external CSS fails to load */
}

/* Modal Scrolling Enhancement */
#uploadModal .modal-dialog-scrollable .modal-body {
    max-height: calc(100vh - 250px) !important;
    overflow-y: auto !important;
    overflow-x: hidden !important; 
    padding: 1.5rem;
}

#uploadModal .modal-dialog-scrollable .modal-header {
    position: sticky;
    top: 0;
    z-index: 1020;
    background-color: white;
    flex-shrink: 0;
}

#uploadModal .modal-dialog-scrollable .modal-footer {
    position: sticky;
    bottom: 0;
    z-index: 1020;
    background-color: white;
    flex-shrink: 0;
}

#uploadModal .modal-dialog-scrollable {
    max-height: calc(100vh - 1rem);
}

#uploadModal .modal-content {
    max-height: calc(100vh - 1rem);
    display: flex;
    flex-direction: column;
}

/* Custom scrollbar styling */
#uploadModal .modal-body::-webkit-scrollbar {
    width: 10px;
}

#uploadModal .modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

#uploadModal .modal-body::-webkit-scrollbar-thumb {
    background: #0d6efd;
    border-radius: 10px;
}

#uploadModal .modal-body::-webkit-scrollbar-thumb:hover {
    background: #0b5ed7;
}

/* Attachments table container scrolling */
#uploadModal #course_attachments_container,
#uploadModal #other_attachments_container {
    max-height: 300px !important;
    overflow-y: auto !important;
    overflow-x: auto !important;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    position: relative;
}

#uploadModal #course_attachments_container table,
#uploadModal #other_attachments_container table {
    margin-bottom: 0;
}

#uploadModal #course_attachments_container::-webkit-scrollbar,
#uploadModal #other_attachments_container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

#uploadModal #course_attachments_container::-webkit-scrollbar-track,
#uploadModal #other_attachments_container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

#uploadModal #course_attachments_container::-webkit-scrollbar-thumb,
#uploadModal #other_attachments_container::-webkit-scrollbar-thumb {
    background: #6c757d;
    border-radius: 10px;
}

#uploadModal #course_attachments_container::-webkit-scrollbar-thumb:hover,
#uploadModal #other_attachments_container::-webkit-scrollbar-thumb:hover {
    background: #5a6268;
}
</style>

@endpush

@section('title', ($repository->course_repository_name ?? 'Repository Details') . ' | Lal Bahadur')

@section('setup_content')
<style>
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
    border-color: #0d6efd;
    background-color: #eef5ff;
}

.upload-icon {
    font-size: 42px;
    color: #0d6efd;
    margin-bottom: 0.5rem;
}
</style>
<script>
document.getElementById('category_image_create')
    .addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('preview_create_show');

        if (!file) return;

        const reader = new FileReader();
        reader.onload = () => {
            preview.src = reader.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
</script>

<div class="container-fluid">
    <!-- Breadcrumb Navigation -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <nav aria-label="breadcrumb" class="flex-grow-1">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('course-repository.index') }}"
                            class="text-decoration-none text-muted d-flex align-items-center">
                            <span class="material-icons material-symbols-rounded me-1"
                                style="font-size: 18px;">home</span> Academics
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('course-repository.index') }}"
                            class="text-decoration-none text-muted">MCTP</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('course-repository.index') }}" class="text-decoration-none text-muted">Course
                            Repository Admin</a>
                    </li>
                    @if (!empty($ancestors))
                    @foreach ($ancestors as $ancestor)
                    <li class="breadcrumb-item">
                        <a href="{{ route('course-repository.show', $ancestor->pk) }}"
                            class="text-decoration-none text-muted">{{ $ancestor->course_repository_name }}</a>
                    </li>
                    @endforeach
                    @endif
                    <li class="breadcrumb-item active" aria-current="page">
                        <span class="fw-semibold text-primary">{{ $repository->course_repository_name }}</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="datatables">
        <div class="card border-0 shadow-lg modern-card">
            <div class="card-body p-4">
                <!-- Page Title and Actions -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <a href="javascript:void(0)" type="button" onclick="window.history.back()"
                            class="text-primary p-2 back-btn">
                            <span class="material-icons material-symbols-rounded">arrow_back_ios</span>
                        </a>
                        <div>
                            <h3 class="mb-0 fw-bold text-primary">{{ $repository->course_repository_name }}</h3>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3">

                        <!-- Upload Documents (Secondary Action) -->
                        <button type="button"
                            class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2 px-4 py-2 rounded-pill fw-medium shadow-sm btn-hover-lift"
                            data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <span class="material-icons material-symbols-rounded fs-6">
                                upload
                            </span>
                            <span>Upload Documents</span>
                        </button>

                        <!-- Add Category (Primary Action) -->
                        <button type="button"
                            class="btn btn-primary btn-sm d-flex align-items-center gap-2 px-4 py-2 rounded-pill fw-medium shadow btn-hover-lift">
                            <span class="material-icons material-symbols-rounded fs-6">
                                add
                            </span>
                            <span>Add Category</span>
                        </button>

                    </div>

                </div>

                @if($repository->children->count() == 0 && $documents->count() == 0)
                <!-- Empty State -->
                <div class="text-center py-5 my-5">
                    <div class="empty-state-icon mb-3">
                        <span class="material-icons material-symbols-rounded"
                            style="font-size: 64px; color: #dee2e6;">folder_off</span>
                    </div>
                    <h5 class="text-muted mb-2">No Content Found</h5>
                    <p class="text-muted mb-4">Start by adding a category or uploading a document to get started.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="javascript:void(0)" class="btn btn-primary btn-sm rounded" data-bs-toggle="modal"
                            data-bs-target="#createModal">
                            Add Category
                        </a>
                        <a href="" class="btn btn-outline-primary btn-sm rounded">Upload Document
                        </a>
                    </div>
                </div>
                @else
                <!-- Child Repositories Section -->
                @if($repository->children->count() > 0)
                <div class="mb-4">
                    <div class="table-responsive">
                        <table class="table" id="child_repositories">
                            <thead>
                                <tr>
                                    <th class="col">#</th>
                                    <th class="col">Image</th>
                                    <th class="col">Sub Category Name</th>
                                    <th class="col">Details</th>
                                    <th class="col">Sub-Categories</th>
                                    <th class="col">Documents</th>
                                    <th class="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($repository->children as $index => $child)
                                <tr>
                                    <td>
                                        {{ $loop->iteration }}
                                    </td>
                                    <td>
                                        @if($child->category_image &&
                                        \Storage::disk('public')->exists($child->category_image))
                                        <img src="{{ asset('storage/' . $child->category_image) }}" alt="Category Image"
                                            class="rounded-2 shadow-sm"
                                            style="width: 60px; height: 60px; object-fit: cover;">
                                        @else
                                        <div class="bg-light rounded-2 d-flex align-items-center justify-content-center"
                                            style="width: 60px; height: 60px;">
                                            <i class="material-icons material-symbols-rounded text-muted"
                                                style="font-size: 24px;">image</i>
                                        </div>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('course-repository.show', $child->pk) }}"
                                            class="text-decoration-none fw-semibold text-dark hover-primary">
                                            {{ $child->course_repository_name }}
                                        </a>
                                    </td>
                                    <td>
                                        <span
                                            class="text-muted small">{{ Str::limit($child->course_repository_details ?? 'N/A', 50) }}</span>
                                    </td>
                                    <td>{{ $child->children->count() }} - sub-categories
                                    </td>
                                    <td>{{ $child->getDocumentCount() }} - documents
                                    </td>
                                    <td>
                                        <div class="btn-group d-flex gap-2 text-primary" role="group">
                                            <!-- <a href="{{ route('course-repository.show', $child->pk) }}"
                                                class="text-primary"
                                                data-bs-toggle="tooltip" title="View">
                                                <span class="material-icons material-symbols-rounded">visibility</span>
                                            </a> -->
                                            <a href="javascript:void(0)" class="text-primary edit-repo"
                                                data-pk="{{ $child->pk }}"
                                                data-name="{{ $child->course_repository_name }}"
                                                data-details="{{ $child->course_repository_details }}"
                                                data-image="{{ $child->category_image }}" data-bs-toggle="tooltip"
                                                title="Edit">
                                                <span class=" material-icons material-symbols-rounded">edit</span>
                                            </a>
                                            <a href="javascript:void(0)" class="text-primary delete-repo"
                                                data-pk="{{ $child->pk }}" data-bs-toggle="tooltip" title="Delete">
                                                <span class="material-icons material-symbols-rounded">delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Documents Section -->
                @if($documents->count() > 0)
                <div class="table-responsive">
                    <table class="table" id="documents">
                        <thead>
                            <tr>
                                <th class="col text-center">S.No.</th>
                                <th class="col text-center">Document Name</th>
                                <th class="col text-center">File Title</th>
                                <th class="col text-center">Course Name</th>
                                <th class="col text-center">Subject</th>
                                <th class="col text-center">Topic</th>
                                <th class="col text-center">Session Date</th>
                                <th class="col text-center">Sector</th>
                                <th class="col text-center">Ministry</th>
                                <th class="col text-center">Author</th>
                                <th class="col text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $index => $doc)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <i class="fas fa-file-alt text-primary me-2"></i>
                                    <strong>{{ Str::limit($doc->upload_document ?? 'N/A', 30) }}</strong>
                                </td>
                                <td>{{ Str::limit($doc->file_title ?? 'N/A', 25) }}</td>
                                <td>
                                    @if($doc->detail)
                                    @if($doc->detail->course)
                                    {{ $doc->detail->course->course_name }}
                                    @elseif($doc->detail->course_master_pk)
                                    {{ $doc->detail->course_master_pk }}
                                    @else
                                    N/A
                                    @endif
                                    @endif
                                </td>
                                <td class="text-center">
                                    <small class="text-muted">
                                        @if($doc->detail)
                                        @if($doc->detail->timetable)
                                        {{ Str::limit($doc->detail->timetable->topic_name, 20) }}
                                        @elseif($doc->detail->topic_pk)
                                        {{ Str::limit($doc->detail->topic_pk, 20) }}
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted">
                                        @if($doc->detail)
                                        @if($doc->detail->timetable)
                                        {{ Str::limit($doc->detail->timetable->topic_name, 20) }}
                                        @elseif($doc->detail->topic_pk)
                                        {{ Str::limit($doc->detail->topic_pk, 20) }}
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted">
                                        @if($doc->detail && $doc->detail->session_date)
                                        {{ \Carbon\Carbon::parse($doc->detail->session_date)->format('d M Y') }}
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted">
                                        @if($doc->detail)
                                        @if($doc->detail->sector)
                                        {{ Str::limit($doc->detail->sector->sector_name, 15) }}
                                        @elseif($doc->detail->sector_master_pk)
                                        {{ Str::limit($doc->detail->sector_master_pk, 15) }}
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted">
                                        @if($doc->detail)
                                        @if($doc->detail->ministry)
                                        {{ Str::limit($doc->detail->ministry->ministry_name, 15) }}
                                        @elseif($doc->detail->ministry_master_pk)
                                        {{ Str::limit($doc->detail->ministry_master_pk, 15) }}
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted">
                                        @if($doc->detail)
                                        @if($doc->detail->author)
                                        {{ Str::limit($doc->detail->author->full_name, 15) }}
                                        @elseif($doc->detail->author_name)
                                        {{ Str::limit($doc->detail->author_name, 15) }}
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('course-repository.document.download', $doc->pk) }}"
                                            class="text-primary" data-bs-toggle="tooltip" title="Download">
                                            <span class="material-icons material-symbols-rounded">download</span>
                                        </a>
                                        <a class="text-primary delete-doc" data-pk="{{ $doc->pk }}"
                                            data-bs-toggle="tooltip" title="Delete">
                                            <span class="material-icons material-symbols-rounded">delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            @endif
        </div>
    </div>
</div>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">

            <!-- Header -->
            <div class="modal-header bg-primary bg-gradient text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-semibold d-flex align-items-center" id="createModalLabel">
                    <span class="material-icons material-symbols-rounded me-2 fs-5">
                        add_circle
                    </span>
                    Create New Category
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <!-- Form -->
            <form id="createForm" method="POST" action="{{ route('course-repository.store') }}"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="parent_type" value="{{ $repository->pk }}">

                <div class="modal-body p-4">

                    <!-- Category Name -->
                    <div class="mb-4">
                        <div class="form-floating">
                            <input type="text" class="form-control form-control-lg" id="course_repository_name"
                                name="course_repository_name" placeholder="Category Name" required>
                            <label for="course_repository_name">
                                <span class="material-icons material-symbols-rounded me-1 fs-6">
                                    folder
                                </span>
                                Category Name <span class="text-danger">*</span>
                            </label>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="mb-4">
                        <div class="form-floating">
                            <textarea class="form-control" id="course_repository_details"
                                name="course_repository_details" placeholder="Details" style="height: 110px"></textarea>
                            <label for="course_repository_details">
                                <span class="material-icons material-symbols-rounded me-1 fs-6">
                                    subject
                                </span>
                                Details (Optional)
                            </label>
                        </div>
                    </div>

                    <!-- Upload Section -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Category Image
                        </label>

                        <label for="category_image_create" class="upload-zone">

                            <div class="text-center">
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

                            <input type="file" id="category_image_create" name="category_image"
                                accept="image/jpeg,image/png,image/jpg,image/gif" hidden>
                        </label>

                        <!-- Preview -->
                        <div class="mt-3">
                            <img id="preview_create_show" class="img-thumbnail shadow-sm d-none"
                                alt="Category image preview" style="max-width: 150px;">
                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        Save Category
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>


<!-- Edit Category Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-semibold" id="editModalLabel">
                    <span class="material-icons material-symbols-rounded me-2" style="font-size: 20px;">edit</span>Edit
                    Category
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_course_repository_name" class="form-label fw-semibold">
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-lg" id="edit_course_repository_name"
                            name="course_repository_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_repository_details" class="form-label fw-semibold">Details</label>
                        <textarea class="form-control" id="edit_course_repository_details"
                            name="course_repository_details" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="category_image_edit" class="form-label fw-semibold">Category Image</label>
                        <div id="current_image_container_show" class="mb-3" style="display: none;">
                            <p class="text-muted mb-2 small"><strong>Current Image:</strong></p>
                            <img id="current_image_show" src="" alt="Current"
                                style="max-width: 150px; border-radius: 8px;" class="img-thumbnail shadow-sm">
                        </div>
                        <input type="file" class="form-control" id="category_image_edit" name="category_image"
                            accept="image/jpeg,image/png,image/jpg,image/gif">
                        <small class="text-muted d-block mt-2">
                            <span class="material-icons material-symbols-rounded me-1"
                                style="font-size: 14px;">info</span>Supported formats: JPEG, PNG, JPG, GIF (Max 2MB)
                        </small>
                        <div class="mt-3">
                            <img id="preview_edit_show" src="" alt="Preview"
                                style="max-width: 150px; display: none; border-radius: 8px;"
                                class="img-thumbnail shadow-sm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        <span class="material-icons material-symbols-rounded me-1"
                            style="font-size: 16px;">cancel</span>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <span class="material-icons material-symbols-rounded me-1"
                            style="font-size: 16px;">check_circle</span>Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white border-bottom px-4 py-3">
                <h5 class="modal-title fw-semibold text-dark" id="uploadModalLabel">
                    <span class="material-icons material-symbols-rounded me-2 text-primary"
                        style="font-size: 22px;">cloud_upload</span>Upload Document
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadForm" method="POST" action="javascript:void(0);" enctype="multipart/form-data" novalidate>
                @csrf
                <div id="uploadFormErrors" class="alert alert-danger d-none mx-4 mt-3 mb-0" role="alert"></div>
                <div class="modal-body p-4 bg-light"
                    style="max-height: calc(100vh - 250px); overflow-y: auto; overflow-x: hidden;">
                    <!-- Category Type Selection - Radio Buttons -->
                    <div class="mb-3">
                        <div class="d-flex gap-4 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input category-radio" type="radio" name="category"
                                    id="category_course" value="Course" checked>
                                <label class="form-check-label fw-medium" for="category_course">
                                    Course
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input category-radio" type="radio" name="category"
                                    id="category_other" value="Other">
                                <label class="form-check-label fw-medium" for="category_other">
                                    Other
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input category-radio" type="radio" name="category"
                                    id="category_institutional" value="Institutional">
                                <label class="form-check-label fw-medium" for="category_institutional">
                                    Institutional
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Course Repository Form Card -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-semibold text-dark">Course Repository of LBSNAA</h6>
                        </div>
                        <div class="card-body p-4 bg-white">

                            <!-- Course Category Fields -->
                            <div id="courseFields" class="category-fields">
                                <!-- Row 1: Course Name & Major Subject Name -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="course_name" class="form-label">
                                            Course Name <span class="text-danger">*</span>
                                        </label>
                                        <!-- Active/Archive Toggle -->
                                        <!-- Active/Archive Toggle -->
                                        <div class="btn-group w-100 mb-3" role="group"
                                            aria-label="Course Status Filter">
                                            <input type="radio" class="btn-check" name="course_status"
                                                id="btnActiveCourses" value="active" checked>
                                            <label class="btn btn-outline-success" for="btnActiveCourses">
                                                <span class="material-icons material-symbols-rounded me-1"
                                                    style="font-size: 16px;">check_circle</span>Active Courses
                                            </label>

                                            <input type="radio" class="btn-check" name="course_status"
                                                id="btnArchivedCourses" value="archived">
                                            <label class="btn btn-outline-secondary" for="btnArchivedCourses">
                                                <span class="material-icons material-symbols-rounded me-1"
                                                    style="font-size: 16px;">archive</span>Archived Courses
                                            </label>
                                        </div>
                                        <select class="form-select" id="course_name" name="course_name" required>
                                            <option value="" selected>Select</option>
                                            @foreach(($activeCourses ?? []) as $course)
                                            <option value="{{ $course->pk }}" data-status="active">
                                                {{ $course->course_name }}</option>
                                            @endforeach
                                            @foreach(($archivedCourses ?? []) as $course)
                                            <option value="{{ $course->pk }}" data-status="archived"
                                                style="display:none;">{{ $course->course_name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Select Course Name
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="subject_name" class="form-label">
                                            Major Subject Name <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="subject_name" name="subject_name" required>
                                            <option value="" selected>Select</option>
                                        </select>
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Select Major Subject Name
                                        </small>
                                    </div>
                                </div>

                                <!-- Row 2: Topic Name & Session Date -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="timetable_name" class="form-label">
                                            Topic Name <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="timetable_name" name="timetable_name" required>
                                            <option value="" selected>Select</option>
                                        </select>
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Select Topic Name
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="session_date" class="form-label">
                                            Session Date <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-select" id="session_date" name="session_date"
                                            placeholder="ABCD12345" required>
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Select Session Date
                                        </small>
                                    </div>
                                </div>

                                <!-- Row 3: Author Name & Keywords -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="author_name" class="form-label">
                                            Author Name <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="author_name" name="author_name" required>
                                            <option value="" selected>Select</option>
                                            @foreach(($authors ?? []) as $author)
                                            <option value="{{ $author->pk }}">{{ $author->full_name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Select Author Name
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="keywords_course" class="form-label">
                                            Keywords <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="keywords_course"
                                            name="keywords_course" placeholder="ABCD12345" required>
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Enter Keyword
                                        </small>
                                    </div>
                                </div>

                                <!-- Row 4: Sector & Ministry -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="sector_master" class="form-label">
                                            Sector <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="sector_master" name="sector_master" required>
                                            <option value="" selected>Select</option>
                                            @foreach(($sectors ?? []) as $sector)
                                            <option value="{{ $sector->pk }}">{{ $sector->sector_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="ministry_master" class="form-label">
                                            Ministry <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="ministry_master" name="ministry_master"
                                            required>
                                            <option value="" selected>Select</option>
                                            @foreach(($ministries ?? []) as $ministry)
                                            <option value="{{ $ministry->pk }}"
                                                data-sector="{{ $ministry->sector_master_pk }}" style="display:none;">
                                                {{ $ministry->ministry_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Video Link -->
                                <div class="mb-4">
                                    <label for="video_link_course" class="form-label">
                                        <span class="material-icons material-symbols-rounded me-2"
                                            style="font-size: 18px; vertical-align: middle;">video_library</span>
                                        Video Link
                                    </label>
                                    <input type="url" class="form-control" id="video_link_course"
                                        name="video_link_course" placeholder="https://www.youtube.com/watch?v=...">
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-info-circle me-1"></i> Enter video URL (YouTube, Vimeo, etc.)
                                    </small>
                                </div>

                                <!-- Attachments with Titles -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <span class="material-icons material-symbols-rounded me-2"
                                            style="font-size: 18px; vertical-align: middle;">attach_file</span>
                                        Attachments <span class="text-danger">*</span>
                                    </label>

                                    <!-- Attachments Table -->
                                    <div class="table-responsive" id="course_attachments_container"
                                        style="max-height: 300px; overflow-y: auto; overflow-x: auto;">
                                        <table class="table table-bordered table-hover mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th style="width: 5%;">S.No.</th>
                                                    <th>Attachment Title</th>
                                                    <th>Upload File</th>
                                                    <th style="width: 8%;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="course_attachments_tbody">
                                                <tr class="attachment-row">
                                                    <td class="row-number">1</td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm"
                                                            name="attachment_titles[]" placeholder="e.g., Week-01">
                                                    </td>
                                                    <td>
                                                        <input type="file" class="form-control form-control-sm"
                                                            name="attachments[]" accept="*/*">
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button"
                                                            class="btn btn-sm btn-danger delete-attachment"
                                                            style="display: none;">
                                                            <span class="material-icons material-symbols-rounded"
                                                                style="font-size: 16px;">delete</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Add More Button -->
                                    <div class="mt-3">
                                        <button type="button"
                                            class="btn btn-outline-primary btn-sm add-attachment-course"
                                            data-category="course">
                                            <span class="material-icons material-symbols-rounded me-1"
                                                style="font-size: 16px;">add_circle</span>
                                            Add More Attachment
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Other Category Fields -->
                            <div id="otherFields" class="category-fields" style="display: none;">
                                <!-- Row 1: Course Name & Major Subject Name -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="course_name_other" class="form-label">
                                            Course Name <span class="text-danger">*</span>
                                        </label>
                                        <!-- Active/Archive Toggle for Other Category -->
                                        <div class="btn-group w-100 mb-2" role="group"
                                            aria-label="Other Course Status Filter">
                                            <input type="radio" class="btn-check" name="course_status_other"
                                                id="btnActiveCoursesOther" value="active" checked>
                                            <label class="btn btn-outline-success btn-sm" for="btnActiveCoursesOther">
                                                <i class="bi bi-check-circle me-1"></i>Active Courses
                                            </label>

                                            <input type="radio" class="btn-check" name="course_status_other"
                                                id="btnArchivedCoursesOther" value="archived">
                                            <label class="btn btn-outline-secondary btn-sm"
                                                for="btnArchivedCoursesOther">
                                                <i class="bi bi-archive me-1"></i>Archived Courses
                                            </label>
                                        </div>
                                        <select class="form-select" id="course_name_other" name="course_name_other">
                                            <option value="" selected>Select</option>
                                            @foreach(($activeCourses ?? []) as $course)
                                            <option value="{{ $course->pk }}" data-status="active">
                                                {{ $course->course_name }}</option>
                                            @endforeach
                                            @foreach(($archivedCourses ?? []) as $course)
                                            <option value="{{ $course->pk }}" data-status="archived"
                                                style="display:none;">{{ $course->course_name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Select Course Name
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="major_subject_other" class="form-label">
                                            Major Subject Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="major_subject_other"
                                            name="major_subject_other" placeholder="Select">
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Select Major Subject Name
                                        </small>
                                    </div>
                                </div>

                                <!-- Row 2: Topic Name & Session Date -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="topic_name_other" class="form-label">
                                            Topic Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="topic_name_other"
                                            name="topic_name_other" placeholder="Select">
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Select Topic Name
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="session_date_other" class="form-label">
                                            Session Date <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control" id="session_date_other"
                                            name="session_date_other" placeholder="ABCD12345">
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Select Session Date
                                        </small>
                                    </div>
                                </div>

                                <!-- Row 3: Author Name & Keywords -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="author_name_other" class="form-label">
                                            Author Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="author_name_other"
                                            name="author_name_other" placeholder="Select">
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Select Author Name
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="keywords_other" class="form-label">
                                            Keywords <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="keywords_other"
                                            name="keywords_other" placeholder="ABCD12345">
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            <i class="bi bi-info-circle me-1"></i> Enter Keyword
                                        </small>
                                    </div>
                                </div>

                                <!-- Row 4: Sector & Ministry -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="sector_master_other" class="form-label">
                                            Sector <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="sector_master_other" name="sector_master_other">
                                            <option value="" selected>Select</option>
                                            @foreach(($sectors ?? []) as $sector)
                                            <option value="{{ $sector->pk }}">{{ $sector->sector_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="ministry_master_other" class="form-label">
                                            Ministry <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="ministry_master_other"
                                            name="ministry_master_other">
                                            <option value="" selected>Select</option>
                                            @foreach(($ministries ?? []) as $ministry)
                                            <option value="{{ $ministry->pk }}"
                                                data-sector="{{ $ministry->sector_master_pk }}" style="display:none;">
                                                {{ $ministry->ministry_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Video Link -->
                                <div class="mb-4">
                                    <label for="video_link_other" class="form-label">
                                        <span class="material-icons material-symbols-rounded me-2"
                                            style="font-size: 18px; vertical-align: middle;">video_library</span>
                                        Video Link
                                    </label>
                                    <input type="url" class="form-control" id="video_link_other" name="video_link_other"
                                        placeholder="https://www.youtube.com/watch?v=...">
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-info-circle me-1"></i> Enter video URL (YouTube, Vimeo, etc.)
                                    </small>
                                </div>

                                <!-- Attachments with Titles -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <span class="material-icons material-symbols-rounded me-2"
                                            style="font-size: 18px; vertical-align: middle;">attach_file</span>
                                        Attachments <span class="text-danger">*</span>
                                    </label>

                                    <!-- Attachments Table -->
                                    <div class="table-responsive" id="other_attachments_container"
                                        style="max-height: 300px; overflow-y: auto; overflow-x: auto;">
                                        <table class="table table-bordered table-hover mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th style="width: 5%;">S.No.</th>
                                                    <th>Attachment Title</th>
                                                    <th>Upload File</th>
                                                    <th style="width: 8%;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="other_attachments_tbody">
                                                <tr class="attachment-row">
                                                    <td class="row-number">1</td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm"
                                                            name="attachment_titles_other[]"
                                                            placeholder="e.g., Document-01">
                                                    </td>
                                                    <td>
                                                        <input type="file" class="form-control form-control-sm"
                                                            name="attachments_other[]" accept="*/*">
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button"
                                                            class="btn btn-sm btn-danger delete-attachment"
                                                            style="display: none;">
                                                            <span class="material-icons material-symbols-rounded"
                                                                style="font-size: 16px;">delete</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Add More Button -->
                                    <div class="mt-3">
                                        <button type="button"
                                            class="btn btn-outline-primary btn-sm add-attachment-other"
                                            data-category="other">
                                            <span class="material-icons material-symbols-rounded me-1"
                                                style="font-size: 16px;">add_circle</span>
                                            Add More Attachment
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Institutional Category Fields -->
                            <div id="institutionalFields" class="category-fields" style="display: none;">

                                <div class="row">
                                    <div class="col-md-6">
                                        <!-- Keywords -->
                                        <div class="mb-3">
                                            <label for="Key_words_institutional" class="form-label">
                                                Add Key words <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="Key_words_institutional"
                                                name="Key_words_institutional" placeholder="Enter Keywords">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Keywords -->
                                        <div class="mb-3">
                                            <label for="Key_words_institutional" class="form-label">
                                                Keywords <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="Key_words_institutional"
                                                name="Key_words_institutional" placeholder="Enter Keywords">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <!-- sector -->

                                        <div class="mb-3">
                                            <label for="sector_master_institutional" class="form-label">
                                                Sector <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="sector_master_institutional"
                                                name="sector_master_institutional">
                                                <option value="" selected>Select</option>
                                                @foreach(($sectors ?? []) as $sector)
                                                <option value="{{ $sector->pk }}">{{ $sector->sector_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <!-- ministries -->

                                        <div class="mb-3">
                                            <label for="ministry_master_institutional" class="form-label">Ministry <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" id="ministry_master_institutional"
                                                name="ministry_master_institutional">
                                                <option value="" selected>Select</option>
                                                @foreach(($ministries ?? []) as $ministry)
                                                <option value="{{ $ministry->pk }}"
                                                    data-sector="{{ $ministry->sector_master_pk }}"
                                                    style="display:none;">{{ $ministry->ministry_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>


                                <!-- Video Link -->
                                <!-- <div class="mb-3">
                                    <label for="keyword_institutional" class="form-label">Video Link</label>
                                    <input type="text" class="form-control" id="keyword_institutional"
                                        name="keyword_institutional" placeholder="Enter Video Link">
                                </div> -->

                                <!-- Upload Attachment -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        Upload Attachment <span class="text-danger">*</span>
                                    </label>
                                    <div class="upload-area border rounded-3 text-center p-5 bg-light position-relative"
                                        style="border-style: dashed !important; cursor: pointer;">
                                        <input type="file"
                                            class="file-input-institutional position-absolute w-100 h-100 opacity-0"
                                            name="attachments_institutional[]" accept="*/*" multiple
                                            style="top: 0; left: 0; cursor: pointer;">
                                        <div class="upload-icon mb-2">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 15V3M12 3L8 7M12 3L16 7" stroke="#0d6efd" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                                <path
                                                    d="M2 17L2.62 19.86C2.71 20.37 3.14 20.75 3.66 20.75H20.34C20.86 20.75 21.29 20.37 21.38 19.86L22 17"
                                                    stroke="#0d6efd" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                        <p class="mb-1 text-primary fw-medium">Click to upload <span
                                                class="text-muted">or drag and drop</span></p>
                                        <div class="selected-files-institutional mt-2 text-start" style="display:none;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Required Fields Note -->
                    <p class="text-muted small mb-0">
                        <span class="text-danger">*</span>Required Fields. All marked fields are mandatory for
                        registration
                    </p>
                </div>

                <div class="modal-footer border-0 bg-white px-4 py-3">
                    <button type="submit" class="btn btn-primary px-5 rounded-pill" id="uploadBtn">
                        Save
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-5 rounded-pill" data-bs-dismiss="modal">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Advanced Search Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="searchOffcanvas" aria-labelledby="searchOffcanvasLabel">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title" id="searchOffcanvasLabel">
            <span class="material-symbols-outlined me-2" style="font-size: 20px;">tune</span>Advanced Search & Filters
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="advancedSearchForm">
            <!-- Search Input -->
            <div class="mb-4">
                <div class="form-floating">
                    <input type="text" class="form-control" id="searchQuery" placeholder="Search...">
                    <label for="searchQuery">
                        <span class="material-symbols-outlined me-1" style="font-size: 16px;">search</span>Search Query
                    </label>
                </div>
            </div>

            <!-- Category Filter -->
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    <span class="material-symbols-outlined me-1" style="font-size: 18px;">filter_list</span>Filter by
                    Category
                </label>
                <div class="list-group">
                    <label class="list-group-item">
                        <input class="form-check-input me-1" type="checkbox" value="documents" checked>
                        <span class="material-symbols-outlined me-1"
                            style="font-size: 16px;">description</span>Documents
                        <span class="badge bg-primary rounded-pill ms-auto">{{ $documents->count() ?? 0 }}</span>
                    </label>
                    <label class="list-group-item">
                        <input class="form-check-input me-1" type="checkbox" value="categories" checked>
                        <span class="material-symbols-outlined me-1"
                            style="font-size: 16px;">folder</span>Sub-Categories
                        <span
                            class="badge bg-success rounded-pill ms-auto">{{ $repository->children->count() ?? 0 }}</span>
                    </label>
                </div>
            </div>

            <!-- Date Range -->
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    <span class="material-symbols-outlined me-1" style="font-size: 18px;">date_range</span>Date Range
                </label>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="dateFrom">
                            <label for="dateFrom">From</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="dateTo">
                            <label for="dateTo">To</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File Type Filter -->
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    <span class="material-symbols-outlined me-1" style="font-size: 18px;">file_present</span>File Type
                </label>
                <select class="form-select" id="fileTypeFilter">
                    <option value="">All Types</option>
                    <option value="pdf">PDF Documents</option>
                    <option value="doc">Word Documents</option>
                    <option value="ppt">Presentations</option>
                    <option value="xls">Spreadsheets</option>
                    <option value="img">Images</option>
                </select>
            </div>

            <!-- Quick Actions -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-outlined me-1" style="font-size: 16px;">search</span>Apply Filters
                </button>
                <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                    <span class="material-symbols-outlined me-1" style="font-size: 16px;">refresh</span>Clear All
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <!-- Success Toast -->
    <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <span class="material-symbols-outlined me-2" style="font-size: 16px;">check_circle</span>
                <span id="successMessage">Operation completed successfully!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>

    <!-- Error Toast -->
    <div id="errorToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <span class="material-symbols-outlined me-2" style="font-size: 16px;">warning</span>
                <span id="errorMessage">Something went wrong!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Upload Progress Modal -->
<div class="modal fade" id="uploadProgressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white border-0">
                <h5 class="modal-title">
                    <span class="material-symbols-outlined me-2" style="font-size: 20px;">cloud_upload</span>Uploading
                    Files...
                </h5>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <h6 class="mb-3">Please wait while we upload your files</h6>
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
                        style="width: 0%" id="uploadProgress"></div>
                </div>
                <small class="text-muted">
                    <span id="uploadStatus">Preparing upload...</span>
                </small>
            </div>
        </div>
    </div>
</div>

@endsection

<script>
// ===== UPLOAD FORM SUBMIT - EVENT DELEGATION (runs first, always attached) =====
document.addEventListener('submit', function uploadFormSubmitHandler(e) {
    if (!e.target || e.target.id !== 'uploadForm') return;
    e.preventDefault();

    var uploadFormErrorsEl = document.getElementById('uploadFormErrors');
    var showUploadError = function(msg) {
        var text = (typeof msg === 'string') ? msg : String(msg);
        if (uploadFormErrorsEl) {
            uploadFormErrorsEl.textContent = text;
            uploadFormErrorsEl.classList.remove('d-none');
            uploadFormErrorsEl.style.display = 'block';
            uploadFormErrorsEl.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
        try {
            alert(text);
        } catch (a) {}
    };
    var hideUploadError = function() {
        if (uploadFormErrorsEl) {
            uploadFormErrorsEl.classList.add('d-none');
            uploadFormErrorsEl.innerHTML = '';
        }
    };
    hideUploadError();

    var form = e.target;
    var submitBtn = form.querySelector('button[type="submit"]');
    var uploadModalEl = document.getElementById('uploadModal');
    var uploadModal = null;
    try {
        if (typeof bootstrap !== 'undefined' && uploadModalEl) {
            uploadModal = bootstrap.Modal.getInstance(uploadModalEl) || new bootstrap.Modal(uploadModalEl);
        }
    } catch (err) {}

    try {
        var formData = new FormData(form);
        var selectedCategory = (document.querySelector('input[name="category"]:checked') || {}).value;
        if (!selectedCategory) {
            showUploadError('Please select a category (Course, Other or Institutional).');
            return;
        }

        if (selectedCategory === 'Course') {
            var course_name = formData.get('course_name');
            var subject_name = formData.get('subject_name');
            var timetable_name = formData.get('timetable_name');
            var session_date = formData.get('session_date');
            var author_name = formData.get('author_name');
            var keywordsEl = document.getElementById('keywords_course');
            var keywords = keywordsEl ? keywordsEl.value.trim() : '';
            var sector = formData.get('sector_master');
            var ministry = formData.get('ministry_master');
            var req = [];
            if (!course_name) req.push('Course Name');
            if (!subject_name) req.push('Major Subject Name');
            if (!timetable_name) req.push('Topic Name');
            if (!session_date) req.push('Session Date');
            if (!author_name) req.push('Author Name');
            if (!keywords) req.push('Keywords');
            if (!sector) req.push('Sector');
            if (!ministry) req.push('Ministry');
            if (req.length > 0) {
                showUploadError('Please fill required fields: ' + req.join(', '));
                return;
            }
        } else if (selectedCategory === 'Other') {
            var course_name_other = formData.get('course_name_other');
            var major_subject_other = formData.get('major_subject_other');
            var topic_name_other = formData.get('topic_name_other');
            var session_date_other = formData.get('session_date_other');
            var author_name_other = formData.get('author_name_other');
            var keywordsOtherEl = document.getElementById('keywords_other');
            var keywords_other = keywordsOtherEl ? keywordsOtherEl.value.trim() : '';
            var sector_other = formData.get('sector_master_other');
            var ministry_other = formData.get('ministry_master_other');
            var req = [];
            if (!course_name_other) req.push('Course Name');
            if (!major_subject_other) req.push('Major Subject Name');
            if (!topic_name_other) req.push('Topic Name');
            if (!session_date_other) req.push('Session Date');
            if (!author_name_other) req.push('Author Name');
            if (!keywords_other) req.push('Keywords');
            if (!sector_other) req.push('Sector');
            if (!ministry_other) req.push('Ministry');
            if (req.length > 0) {
                showUploadError('Please fill required fields: ' + req.join(', '));
                return;
            }
        } else if (selectedCategory === 'Institutional') {
            var keywordsInstEl = document.getElementById('Key_words_institutional');
            var keywordsInst = keywordsInstEl ? keywordsInstEl.value.trim() : '';
            if (!keywordsInst) {
                showUploadError('Please fill Keywords.');
                return;
            }
        }

        var attachmentFiles = [];
        var attachmentTitles = [];
        if (selectedCategory === 'Course') {
            attachmentFiles = Array.prototype.slice.call(form.querySelectorAll('input[name="attachments[]"]'));
            attachmentTitles = Array.prototype.slice.call(form.querySelectorAll(
                'input[name="attachment_titles[]"]'));
        } else if (selectedCategory === 'Other') {
            attachmentFiles = Array.prototype.slice.call(form.querySelectorAll(
                'input[name="attachments_other[]"]'));
            attachmentTitles = Array.prototype.slice.call(form.querySelectorAll(
                'input[name="attachment_titles_other[]"]'));
        } else if (selectedCategory === 'Institutional') {
            attachmentFiles = Array.prototype.slice.call(form.querySelectorAll(
                'input[name="attachments_institutional[]"]'));
            attachmentTitles = [];
        }

        var validAttachmentCount = 0;
        var validationErrors = [];
        if (selectedCategory === 'Institutional') {
            attachmentFiles.forEach(function(fileInput) {
                if (fileInput.files && fileInput.files.length > 0) validAttachmentCount += fileInput
                    .files.length;
            });
            if (validAttachmentCount === 0) {
                showUploadError('Please select at least one file to upload.');
                return;
            }
        } else {
            attachmentFiles.forEach(function(fileInput, index) {
                var hasFile = fileInput.files && fileInput.files.length > 0;
                var titleEl = attachmentTitles[index];
                var hasTitle = titleEl && titleEl.value && titleEl.value.trim() !== '';
                if (hasFile && !hasTitle) validationErrors.push('Row ' + (index + 1) +
                    ': File selected but title is missing');
                else if (hasTitle && !hasFile) validationErrors.push('Row ' + (index + 1) +
                    ': Title provided but no file selected');
                else if (hasFile && hasTitle) validAttachmentCount++;
            });
            if (validationErrors.length > 0) {
                showUploadError(validationErrors.join(' | '));
                return;
            }
            if (validAttachmentCount === 0) {
                showUploadError('Please add at least one attachment with both title and file.');
                return;
            }
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        }
        if (uploadModal) try {
            uploadModal.hide();
        } catch (h) {}

        var uploadData = new FormData();
        uploadData.append('_token', (form.querySelector('[name="_token"]') || {}).value || '');
        uploadData.append('category', selectedCategory);

        if (selectedCategory === 'Course') {
            uploadData.append('course_name', formData.get('course_name') || '');
            uploadData.append('subject_name', formData.get('subject_name') || '');
            uploadData.append('timetable_name', formData.get('timetable_name') || '');
            uploadData.append('session_date', formData.get('session_date') || '');
            uploadData.append('author_name', formData.get('author_name') || '');
            uploadData.append('sector_master', formData.get('sector_master') || '');
            uploadData.append('ministry_master', formData.get('ministry_master') || '');
        } else if (selectedCategory === 'Other') {
            uploadData.append('course_name', formData.get('course_name_other') || '');
            uploadData.append('subject_name', formData.get('major_subject_other') || '');
            uploadData.append('timetable_name', formData.get('topic_name_other') || '');
            uploadData.append('session_date', formData.get('session_date_other') || '');
            uploadData.append('author_name', formData.get('author_name_other') || '');
            uploadData.append('sector_master', formData.get('sector_master_other') || '');
            uploadData.append('ministry_master', formData.get('ministry_master_other') || '');
        } else {
            uploadData.append('course_name', '');
            uploadData.append('subject_name', '');
            uploadData.append('timetable_name', '');
            uploadData.append('session_date', '');
            uploadData.append('author_name', '');
        }

        if (selectedCategory === 'Institutional') {
            attachmentFiles.forEach(function(fileInput) {
                if (fileInput.files) {
                    for (var i = 0; i < fileInput.files.length; i++) {
                        uploadData.append('attachments[]', fileInput.files[i]);
                        uploadData.append('attachment_titles[]', fileInput.files[i].name || (
                            'Document ' + (i + 1)));
                    }
                }
            });
        } else {
            attachmentFiles.forEach(function(fileInput, index) {
                if (fileInput.files && fileInput.files.length > 0) {
                    uploadData.append('attachments[]', fileInput.files[0]);
                    var title = (attachmentTitles[index] && attachmentTitles[index].value &&
                            attachmentTitles[index].value.trim()) ? attachmentTitles[index].value
                    .trim() : 'Untitled';
                    uploadData.append('attachment_titles[]', title);
                }
            });
        }

        if (selectedCategory === 'Course') {
            var kw = document.getElementById('keywords_course');
            uploadData.append('keywords', kw ? kw.value : '');
            var vc = document.getElementById('video_link_course');
            uploadData.append('video_link', vc ? vc.value : '');
        } else if (selectedCategory === 'Other') {
            var ko = document.getElementById('keywords_other');
            uploadData.append('keywords', ko ? ko.value : '');
            var vo = document.getElementById('video_link_other');
            uploadData.append('video_link', vo ? vo.value : '');
        } else {
            var ki = document.getElementById('Key_words_institutional');
            uploadData.append('keywords', ki ? ki.value : '');
            var vi = document.getElementById('keyword_institutional');
            uploadData.append('video_link', vi ? vi.value : '');
        }

        var repositoryPk = {
            {
                $repository - > pk
            }
        };
        fetch('/course-repository/' + repositoryPk + '/upload-document', {
                method: 'POST',
                body: uploadData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return {
                        ok: response.ok,
                        status: response.status,
                        data: data
                    };
                }).catch(function() {
                    return {
                        ok: false,
                        data: {
                            error: 'Server returned an invalid response. Please try again.'
                        }
                    };
                });
            })
            .then(function(result) {
                if (result.ok && result.data && result.data.success) {
                    hideUploadError();
                    form.reset();
                    alert('Upload successful!');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                    return;
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Save';
                }
                if (uploadModal) try {
                    uploadModal.show();
                } catch (s) {}
                var errMsg = (result.data && result.data.error) || 'Upload failed';
                if (result.data && result.data.errors && typeof result.data.errors === 'object') {
                    var parts = [];
                    Object.keys(result.data.errors).forEach(function(field) {
                        var val = result.data.errors[field];
                        parts.push(Array.isArray(val) ? val.join(' ') : val);
                    });
                    if (parts.length) errMsg = parts.join(' | ');
                }
                showUploadError(errMsg);
            })
            .catch(function(error) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Save';
                }
                if (uploadModal) try {
                    uploadModal.show();
                } catch (s) {}
                showUploadError('Network error. Please try again.');
            });
    } catch (err) {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Save';
        }
        showUploadError('Error: ' + (err.message || String(err)));
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // ===== DEFINE KEYWORDS FUNCTION FIRST =====
    function updateKeywords() {
        try {
            // Get dropdown values (check both value and text to filter "Select")
            const courseValue = $('#course_name').val()?.trim() || '';
            const courseName = courseValue ? $('#course_name option:selected').text().trim() : '';

            const subjectValue = $('#subject_name').val()?.trim() || '';
            const subjectName = subjectValue ? $('#subject_name option:selected').text().trim() : '';

            const topicValue = $('#timetable_name').val()?.trim() || '';
            const topicName = topicValue ? $('#timetable_name option:selected').text().trim() : '';

            const sessionDate = $('#session_date').val()?.trim() || '';

            const facultyValue = $('#author_name').val()?.trim() || '';
            const facultyName = facultyValue ? $('#author_name option:selected').text().trim() : '';

            const sectorValue = $('#sector_master').val()?.trim() || '';
            const sectorName = sectorValue ? $('#sector_master option:selected').text().trim() : '';

            const ministryValue = $('#ministry_master').val()?.trim() || '';
            const ministryName = ministryValue ? $('#ministry_master option:selected').text().trim() : '';

            console.log('updateKeywords called:', {
                courseName,
                subjectName,
                topicName,
                sessionDate,
                facultyName,
                sectorName,
                ministryName
            });

            // Build keywords string (comma-separated)
            const keywordsParts = [];

            // Only add non-empty, non-select values
            if (courseName && courseName !== '-- Select --' && courseName !== 'Select') keywordsParts.push(
                courseName);
            if (subjectName && subjectName !== '-- Select Subject --' && subjectName !== 'Select') keywordsParts
                .push(subjectName);
            if (topicName && topicName !== '-- Select Topic --' && topicName !== 'Select') keywordsParts.push(
                topicName);
            if (sessionDate) keywordsParts.push(sessionDate);
            if (facultyName && facultyName !== 'Select' && facultyName !== '-- Select --') keywordsParts.push(
                facultyName);
            if (sectorName && sectorName !== '-- Select Sector --' && sectorName !== 'Select') keywordsParts
                .push(sectorName);
            if (ministryName && ministryName !== '-- Select Ministry --' && ministryName !== 'Select')
                keywordsParts.push(ministryName);

            const keywords = keywordsParts.join(', '); // Comma-separated
            console.log('Setting keywords:', keywords);
            $('#keywords_course').val(keywords);
        } catch (error) {
            console.error('Error in updateKeywords:', error);
        }
    }

    // Keywords function for Other category
    function updateKeywordsOther() {
        try {
            // SELECT dropdowns - check value exists, then get text
            const courseValue = $('#course_name_other').val()?.trim() || '';
            const courseName = courseValue ? $('#course_name_other option:selected').text().trim() : '';

            const sectorValue = $('#sector_master_other').val()?.trim() || '';
            const sectorName = sectorValue ? $('#sector_master_other option:selected').text().trim() : '';

            const ministryValue = $('#ministry_master_other').val()?.trim() || '';
            const ministryName = ministryValue ? $('#ministry_master_other option:selected').text().trim() : '';

            // TEXT INPUTS - just get their values directly
            const subjectName = $('#major_subject_other').val()?.trim() || '';
            const topicName = $('#topic_name_other').val()?.trim() || '';
            const sessionDate = $('#session_date_other').val()?.trim() || '';
            const facultyName = $('#author_name_other').val()?.trim() || '';

            console.log('updateKeywordsOther called:', {
                courseName,
                subjectName,
                topicName,
                sessionDate,
                facultyName,
                sectorName,
                ministryName
            });

            // Build keywords string (comma-separated)
            const keywordsParts = [];

            // Only add non-empty, non-select values
            if (courseName && courseName !== '-- Select --' && courseName !== 'Select') keywordsParts.push(
                courseName);
            if (subjectName) keywordsParts.push(subjectName);
            if (topicName) keywordsParts.push(topicName);
            if (sessionDate) keywordsParts.push(sessionDate);
            if (facultyName) keywordsParts.push(facultyName);
            if (sectorName && sectorName !== '-- Select Sector --' && sectorName !== 'Select') keywordsParts
                .push(sectorName);
            if (ministryName && ministryName !== '-- Select Ministry --' && ministryName !== 'Select')
                keywordsParts.push(ministryName);

            const keywords = keywordsParts.join(', '); // Comma-separated
            console.log('Setting keywords (Other):', keywords);
            $('#keywords_other').val(keywords);
        } catch (error) {
            console.error('Error in updateKeywordsOther:', error);
        }
    }

    // Global error handler for unhandled promise rejections
    window.addEventListener('unhandledrejection', function(event) {
        console.warn('Unhandled promise rejection:', event.reason);
        // Prevent the default browser error handling
        event.preventDefault();
    });

    // Global error handler for uncaught errors
    window.addEventListener('error', function(event) {
        console.warn('Global error caught:', event.error);
        // Don't prevent default handling for critical errors
    });

    const repositoryPk = {
        {
            $repository - > pk
        }
    };

    // ===== COURSE FILTERING LOGIC =====
    const courseStatusRadios = document.querySelectorAll('input[name="course_status"]');
    const courseSelect = document.getElementById('course_name');

    if (courseStatusRadios.length > 0 && courseSelect) {
        courseStatusRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const status = this.value;
                const options = courseSelect.querySelectorAll('option');

                options.forEach(option => {
                    if (option.value === '') {
                        // Always show the empty/select option
                        option.style.display = 'block';
                    } else {
                        const optionStatus = option.getAttribute('data-status');
                        option.style.display = (optionStatus === status) ? 'block' :
                            'none';
                    }
                });

                // Reset course selection when filter changes
                courseSelect.value = '';
                // Reset dependent dropdowns
                resetSubjectDropdown();
                resetTopicDropdown();
                resetSessionDateInput();
                resetAuthorDropdown();
                // Clear keywords when radio filter changes
                updateKeywords();
            });
        });
    }

    // ===== CASCADING DROPDOWNS AJAX =====

    // Helper functions to reset dropdowns
    function resetSubjectDropdown() {
        const subjectSelect = document.getElementById('subject_name');
        subjectSelect.innerHTML = '<option value="">Select</option>';
    }

    function resetTopicDropdown() {
        const topicSelect = document.getElementById('timetable_name');
        topicSelect.innerHTML = '<option value="">Select</option>';
    }

    function resetSessionDateInput() {
        const sessionDate = document.getElementById('session_date');
        if (sessionDate) {
            sessionDate.value = '';
        }
    }

    function resetAuthorDropdown() {
        const authorSelect = document.getElementById('author_name');
        authorSelect.value = '';
    }

    // Course change - load subjects via AJAX
    if (courseSelect) {
        courseSelect.addEventListener('change', function() {
            const coursePk = this.value;
            const subjectSelect = document.getElementById('subject_name');

            resetTopicDropdown();
            resetSessionDateInput();
            resetAuthorDropdown();
            updateKeywords(); // Update keywords when course changes

            if (!coursePk) {
                resetSubjectDropdown();
                return;
            }

            // Fetch subjects for selected course
            fetch(`/course-repository/subjects/${coursePk}`)
                .then(response => response.json())
                .then(data => {
                    subjectSelect.innerHTML = '<option value="">Select</option>';
                    // Handle response - data.data because API returns {success: true, data: [...]}
                    const subjects = data.data || data || [];
                    if (Array.isArray(subjects) && subjects.length > 0) {
                        subjects.forEach(subject => {
                            const option = document.createElement('option');
                            option.value = subject.pk;
                            option.textContent = subject.subject_name;
                            subjectSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching subjects:', error);
                    resetSubjectDropdown();
                });
        });
    }

    // Subject change - load topics via AJAX
    const subjectSelect = document.getElementById('subject_name');
    if (subjectSelect) {
        subjectSelect.addEventListener('change', function() {
            const subjectPk = this.value;
            const coursePk = document.getElementById('course_name').value;
            const topicSelect = document.getElementById('timetable_name');

            resetSessionDateInput();
            resetAuthorDropdown();
            updateKeywords(); // Update keywords when subject changes

            if (!subjectPk) {
                resetTopicDropdown();
                return;
            }

            // Fetch topics for selected subject with course parameter
            fetch(`/course-repository/topics/${subjectPk}?course_master_pk=${coursePk}`)
                .then(response => response.json())
                .then(data => {
                    topicSelect.innerHTML = '<option value="">Select</option>';
                    // Handle response - data.data because API returns {success: true, data: [...]}
                    const topics = data.data || data || [];
                    if (Array.isArray(topics) && topics.length > 0) {
                        topics.forEach(topic => {
                            const option = document.createElement('option');
                            option.value = topic.pk;
                            option.textContent = topic.subject_topic || topic
                                .course_repo_topic || topic.course_repo_sub_topic;
                            topicSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching topics:', error);
                    resetTopicDropdown();
                });
        });
    }

    // Topic change - load session dates and faculty via AJAX
    const topicSelect = document.getElementById('timetable_name');
    if (topicSelect) {
        topicSelect.addEventListener('change', function() {
            const topicPk = this.value;
            const sessionDateInput = document.getElementById('session_date');
            const authorSelect = document.getElementById('author_name');

            updateKeywords(); // Update keywords when topic changes

            if (!topicPk) {
                resetSessionDateInput();
                resetAuthorDropdown();
                return;
            }

            // Fetch session dates for selected topic
            fetch(`/course-repository/session-dates?topic_pk=${topicPk}`)
                .then(response => response.json())
                .then(data => {
                    const dates = data.data || data || [];
                    if (Array.isArray(dates) && dates.length > 0) {
                        // Set first session date automatically
                        if (sessionDateInput && dates[0].session_date) {
                            sessionDateInput.value = dates[0].session_date;
                        }
                    }
                })
                .catch(error => console.error('Error fetching session dates:', error));

            // Fetch authors/faculty for selected topic
            fetch(`/course-repository/authors-by-topic?topic_pk=${topicPk}`)
                .then(response => response.json())
                .then(data => {
                    authorSelect.innerHTML = '<option value="">Select</option>';
                    const authors = data.data || data || [];
                    if (Array.isArray(authors) && authors.length > 0) {
                        authors.forEach(author => {
                            const option = document.createElement('option');
                            option.value = author.pk;
                            option.textContent = author.full_name || author.author_name;
                            authorSelect.appendChild(option);
                        });
                        // Auto-select first author if only one exists
                        if (authors.length === 1) {
                            authorSelect.value = authors[0].pk;
                        }
                    }
                })
                .catch(error => console.error('Error fetching authors:', error));
        });
    }

    // Session Date change - update keywords
    const sessionDateInput = document.getElementById('session_date');
    if (sessionDateInput) {
        sessionDateInput.addEventListener('change', function() {
            updateKeywords(); // Update keywords when session date changes
        });
    }

    // Faculty/Author change - update keywords
    const authorSelect = document.getElementById('author_name');
    if (authorSelect) {
        authorSelect.addEventListener('change', function() {
            updateKeywords(); // Update keywords when faculty changes
        });
    }

    // Sector change - load ministries via AJAX and update keywords
    const sectorSelect = document.getElementById('sector_master');
    const ministrySelect = document.getElementById('ministry_master');

    if (sectorSelect && ministrySelect) {
        sectorSelect.addEventListener('change', function() {
            const sectorPk = this.value;

            // Reset ministry dropdown
            ministrySelect.innerHTML = '<option value="">Select</option>';
            updateKeywords(); // Update keywords when sector changes

            if (!sectorPk) {
                return;
            }

            // Fetch ministries for selected sector
            fetch(`/course-repository/ministries-by-sector?sector_pk=${sectorPk}`)
                .then(response => response.json())
                .then(data => {
                    const ministries = data.data || data || [];
                    if (Array.isArray(ministries) && ministries.length > 0) {
                        ministries.forEach(ministry => {
                            const option = document.createElement('option');
                            option.value = ministry.pk;
                            option.textContent = ministry.ministry_name;
                            ministrySelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error fetching ministries:', error));
        });
    }

    // Ministry change - update keywords
    if (ministrySelect) {
        ministrySelect.addEventListener('change', function() {
            updateKeywords(); // Update keywords when ministry changes
        });
    }

    // ===== OTHER CATEGORY KEYWORDS EVENT LISTENERS =====

    // Course Name (Other) - dropdown change
    const courseSelectOther = document.getElementById('course_name_other');
    if (courseSelectOther) {
        courseSelectOther.addEventListener('change', function() {
            updateKeywordsOther();
        });
    }

    // Major Subject (Other) - text input
    const subjectInputOther = document.getElementById('major_subject_other');
    if (subjectInputOther) {
        subjectInputOther.addEventListener('input', function() {
            updateKeywordsOther();
        });
    }

    // Topic Name (Other) - text input
    const topicInputOther = document.getElementById('topic_name_other');
    if (topicInputOther) {
        topicInputOther.addEventListener('input', function() {
            updateKeywordsOther();
        });
    }

    // Session Date (Other) - date input
    const sessionDateOther = document.getElementById('session_date_other');
    if (sessionDateOther) {
        sessionDateOther.addEventListener('change', function() {
            updateKeywordsOther();
        });
    }

    // Author Name (Other) - text input
    const authorInputOther = document.getElementById('author_name_other');
    if (authorInputOther) {
        authorInputOther.addEventListener('input', function() {
            updateKeywordsOther();
        });
    }

    // Sector (Other) - dropdown with ministry AJAX
    const sectorSelectOther = document.getElementById('sector_master_other');
    const ministrySelectOther = document.getElementById('ministry_master_other');

    if (sectorSelectOther && ministrySelectOther) {
        sectorSelectOther.addEventListener('change', function() {
            const sectorPk = this.value;

            // Reset ministry dropdown
            ministrySelectOther.innerHTML = '<option value="">Select</option>';
            updateKeywordsOther();

            if (!sectorPk) return;

            // Fetch ministries for selected sector
            fetch(`/course-repository/ministries-by-sector?sector_pk=${sectorPk}`)
                .then(response => response.json())
                .then(data => {
                    const ministries = data.data || data || [];
                    if (Array.isArray(ministries) && ministries.length > 0) {
                        ministries.forEach(ministry => {
                            const option = document.createElement('option');
                            option.value = ministry.pk;
                            option.textContent = ministry.ministry_name;
                            ministrySelectOther.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error fetching ministries (Other):', error));
        });
    }

    // Ministry (Other) - dropdown change
    if (ministrySelectOther) {
        ministrySelectOther.addEventListener('change', function() {
            updateKeywordsOther();
        });
    }

    // Active/Archived radio for Other category
    const courseStatusRadiosOther = document.querySelectorAll('input[name="course_status_other"]');
    if (courseStatusRadiosOther.length > 0 && courseSelectOther) {
        courseStatusRadiosOther.forEach(radio => {
            radio.addEventListener('change', function() {
                const status = this.value;
                const options = courseSelectOther.querySelectorAll('option');

                options.forEach(option => {
                    if (option.value === '') {
                        option.style.display = 'block';
                        return;
                    }

                    const optionStatus = option.getAttribute('data-status');
                    if (status === 'active' && optionStatus === 'active') {
                        option.style.display = 'block';
                    } else if (status === 'archived' && optionStatus === 'archived') {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                });

                // Reset selection
                courseSelectOther.value = '';
                updateKeywordsOther();
            });
        });
    }

    // Initialize tooltips with error handling
    try {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    } catch (error) {
        console.warn('Tooltip initialization failed:', error);
    }

    // Modern Toast Helper Functions
    function showToast(type, message) {
        try {
            const toastElement = document.getElementById(type + 'Toast');
            const messageElement = document.getElementById(type + 'Message');

            if (!toastElement) {
                console.warn('Toast element not found:', type + 'Toast');
                return;
            }

            if (messageElement) {
                messageElement.textContent = message;
            }

            if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
                const toast = new bootstrap.Toast(toastElement, {
                    autohide: true,
                    delay: 4000
                });
                toast.show();
            }
        } catch (error) {
            console.warn('Toast display failed:', error);
        }
    }

    // Advanced Search Functionality
    const advancedSearchForm = document.getElementById('advancedSearchForm');
    if (advancedSearchForm) {
        advancedSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();

            try {
                const formData = new FormData(this);
                const searchQueryInput = document.getElementById('searchQuery');
                const searchQuery = formData.get('searchQuery') || (searchQueryInput ? searchQueryInput
                    .value : '');

                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span>Searching...';
                    submitBtn.disabled = true;

                    // Simulate search (replace with actual search logic)
                    setTimeout(() => {
                        // Filter table rows based on search criteria
                        performTableSearch(searchQuery);

                        // Restore button
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;

                        // Close offcanvas
                        const searchOffcanvasEl = document.getElementById('searchOffcanvas');
                        if (searchOffcanvasEl && typeof bootstrap !== 'undefined' && bootstrap
                            .Offcanvas) {
                            const offcanvas = bootstrap.Offcanvas.getInstance(
                                searchOffcanvasEl);
                            if (offcanvas) {
                                offcanvas.hide();
                            }
                        }

                        // Show success message
                        showToast('success', `Found results for: "${searchQuery}"`);
                    }, 1000);
                }
            } catch (error) {
                console.warn('Search functionality error:', error);
            }
        });
    }

    // Table Search Function
    function performTableSearch(query) {
        const tables = document.querySelectorAll('.modern-table tbody tr');
        let visibleCount = 0;

        tables.forEach(row => {
            const text = row.textContent.toLowerCase();
            const isVisible = query === '' || text.includes(query.toLowerCase());

            row.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                visibleCount++;
                // Add highlight animation
                row.classList.add('table-search-highlight');
                setTimeout(() => {
                    row.classList.remove('table-search-highlight');
                }, 2000);
            }
        });

        // Update search results info
        console.log(`Found ${visibleCount} results`);
    }

    // Clear Filters
    const clearFiltersBtn = document.getElementById('clearFilters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            try {
                const searchForm = document.getElementById('advancedSearchForm');
                if (searchForm) {
                    searchForm.reset();
                }
                performTableSearch('');
                showToast('success', 'Filters cleared successfully');
            } catch (error) {
                console.warn('Clear filters error:', error);
            }
        });
    }

    // Enhanced Upload Progress
    function showUploadProgress() {
        try {
            const uploadProgressModalEl = document.getElementById('uploadProgressModal');
            const progressBar = document.getElementById('uploadProgress');
            const statusText = document.getElementById('uploadStatus');

            if (!uploadProgressModalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                console.warn('Upload progress modal not available');
                return;
            }

            const modal = new bootstrap.Modal(uploadProgressModalEl);
            modal.show();

            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 15;

                if (progress >= 100) {
                    progress = 100;
                    if (progressBar) {
                        progressBar.style.width = '100%';
                    }
                    if (statusText) {
                        statusText.textContent = 'Upload completed!';
                    }

                    setTimeout(() => {
                        modal.hide();
                        showToast('success', 'Files uploaded successfully!');
                    }, 1000);

                    clearInterval(interval);
                } else {
                    if (progressBar) {
                        progressBar.style.width = progress + '%';
                    }
                    if (statusText) {
                        statusText.textContent = `Uploading... ${Math.round(progress)}%`;
                    }
                }
            }, 200);
        } catch (error) {
            console.warn('Upload progress error:', error);
        }
    }

    // Keyboard Shortcuts & Accessibility
    document.addEventListener('keydown', function(e) {
        try {
            // Ctrl/Cmd + K for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchOffcanvasEl = document.getElementById('searchOffcanvas');
                if (searchOffcanvasEl && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    const searchOffcanvas = new bootstrap.Offcanvas(searchOffcanvasEl);
                    searchOffcanvas.show();
                    setTimeout(() => {
                        const searchInput = document.getElementById('searchQuery');
                        if (searchInput) {
                            searchInput.focus();
                        }
                    }, 300);
                }
            }

            // Ctrl/Cmd + N for new category
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                const createModalEl = document.getElementById('createModal');
                if (createModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const createModal = new bootstrap.Modal(createModalEl);
                    createModal.show();
                    setTimeout(() => {
                        const nameInput = document.getElementById('course_repository_name');
                        if (nameInput) {
                            nameInput.focus();
                        }
                    }, 300);
                }
            }

            // Ctrl/Cmd + U for upload
            if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
                e.preventDefault();
                const uploadModalEl = document.getElementById('uploadModal');
                if (uploadModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const uploadModal = new bootstrap.Modal(uploadModalEl);
                    uploadModal.show();
                }
            }

            // Escape to close modals/offcanvas
            if (e.key === 'Escape') {
                // Close any open toasts
                try {
                    document.querySelectorAll('.toast.show').forEach(toast => {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
                            const toastInstance = bootstrap.Toast.getInstance(toast);
                            if (toastInstance) {
                                toastInstance.hide();
                            }
                        }
                    });
                } catch (toastError) {
                    console.warn('Toast cleanup error:', toastError);
                }
            }
        } catch (error) {
            console.warn('Keyboard shortcut error:', error);
        }
    });

    // Enhanced Form Validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                showToast('error', 'Please fill in all required fields');
            }
            form.classList.add('was-validated');
        });
    });

    // Auto-save draft functionality (for forms)
    const autoSaveForms = ['createForm', 'editForm'];
    autoSaveForms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('input', debounce(() => {
                    // Save form data to localStorage
                    const formData = new FormData(form);
                    const data = {};
                    for (let [key, value] of formData.entries()) {
                        data[key] = value;
                    }
                    localStorage.setItem(`draft_${formId}`, JSON.stringify(data));
                }, 1000));
            });
        }
    });

    // Debounce utility function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Add loading skeletons while content loads
    function showLoadingSkeletons() {
        document.querySelectorAll('.table tbody tr').forEach(row => {
            row.classList.add('skeleton');
        });

        setTimeout(() => {
            document.querySelectorAll('.skeleton').forEach(el => {
                el.classList.remove('skeleton');
            });
        }, 1000);
    }

    // Initialize page load effects
    try {
        // Add stagger animation to table rows
        document.querySelectorAll('.table tbody tr').forEach((row, index) => {
            try {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            } catch (rowError) {
                console.warn('Row animation error:', rowError);
            }
        });

        // Show helpful hints
        try {
            if (typeof localStorage !== 'undefined' && localStorage.getItem('first_visit') !== 'false') {
                setTimeout(() => {
                    showToast('success', 'Tip: Use Ctrl+K to open advanced search!');
                    localStorage.setItem('first_visit', 'false');
                }, 2000);
            }
        } catch (storageError) {
            console.warn('LocalStorage error:', storageError);
        }
    } catch (error) {
        console.warn('Page load effects error:', error);
    }

    // Image preview for create modal
    const createImageInput = document.getElementById('category_image_create');
    if (createImageInput) {
        createImageInput.addEventListener('change', function(e) {
            try {
                const preview = document.getElementById('preview_create_show');
                const file = e.target.files[0];

                if (file && preview) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else if (preview) {
                    preview.style.display = 'none';
                }
            } catch (error) {
                console.warn('Image preview error (create):', error);
            }
        });
    }

    // Image preview for edit modal
    const editImageInput = document.getElementById('category_image_edit');
    if (editImageInput) {
        editImageInput.addEventListener('change', function(e) {
            try {
                const preview = document.getElementById('preview_edit_show');
                const file = e.target.files[0];

                if (file && preview) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else if (preview) {
                    preview.style.display = 'none';
                }
            } catch (error) {
                console.warn('Image preview error (edit):', error);
            }
        });
    }

    // Basic function to clear and populate dropdown
    function populateDropdown(selectId, data, valueKey, textKey) {
        try {
            const selectElement = document.getElementById(selectId);
            if (!selectElement) {
                console.warn('Select element not found:', selectId);
                return;
            }

            // Clear existing options
            selectElement.innerHTML = '<option value="">-- Select --</option>';

            if (data && data.length > 0) {
                data.forEach(function(item) {
                    const option = document.createElement('option');
                    option.value = item[valueKey] || '';
                    option.textContent = typeof textKey === 'function' ? textKey(item) : item[
                        textKey] || '';
                    selectElement.appendChild(option);
                });
            }
        } catch (error) {
            console.warn('Populate dropdown error:', error);
        }
    }
    // Step 1: Course changes -> Load Groups
    function onCourseChange(courseSelectId, groupSelectId) {

        let coursePk = $('#' + courseSelectId).val();
        let $group = $('#' + groupSelectId);

        $group.empty().append('<option value="">-- Select --</option>');

        if (!coursePk) return;

        $.ajax({
            url: "{{ route('course-repository.groups') }}",
            type: "GET",
            data: {
                course_pk: coursePk
            },

            // 🔥 THESE 3 LINES FIX 302
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },

            success: function(response) {
                if (response.success) {
                    $.each(response.data, function(i, group) {
                        $group.append(
                            `<option value="${group.pk}">
                            ${group.subject_name}
                        </option>`
                        );
                    });
                }
            },

            error: function(xhr) {
                if (xhr.status === 401) {
                    Swal.fire('Session Expired', 'Please login again', 'warning');
                } else {
                    console.error(xhr.responseText);
                }
            }
        });
    }


    // Step 2: Group changes -> Load Timetables
    function onGroupChange(groupSelectId, timetableSelectId) {
        var groupPk = $('#' + groupSelectId).val();
        var course_master_pk = $('#course_name').val();

        // Clear timetable dropdown
        populateDropdown(timetableSelectId, [], 'pk', function(t) {
            return '';
        });

        if (!groupPk) return;
        console.log('Selected Group PK:', groupPk);

        // AJAX call to get timetables
        $.ajax({
            url: '/course-repository/timetables',
            type: 'GET',
            data: {
                group_pk: groupPk,
                course_master_pk: course_master_pk
            },
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    populateDropdown(timetableSelectId, response.data, 'pk', function(t) {
                        return t.subject_topic;
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log('Error loading timetables:', error);
            }
        });
    }

    // Category radio button change handler
    document.querySelectorAll('.category-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const category = this.value;

            // Hide all category-specific fields and document tables
            document.querySelectorAll('.category-fields').forEach(field => {
                field.style.display = 'none';
            });

            // Show the selected category fields
            if (category === 'Course') {
                document.getElementById('courseFields').style.display = 'block';
                document.getElementById('courseVideoLink').style.display = 'block';
                document.getElementById('courseAttachments').style.display = 'block';
                // Make course-specific keywords required
                document.getElementById('keywords_course').setAttribute('required', 'required');
                document.getElementById('keywords_other').removeAttribute('required');
                document.getElementById('Key_words_institutional').removeAttribute('required');
            } else if (category === 'Other') {
                document.getElementById('otherFields').style.display = 'block';
                // Make other-specific keywords required
                document.getElementById('keywords_course').removeAttribute('required');
                document.getElementById('keywords_other').setAttribute('required', 'required');
                document.getElementById('Key_words_institutional').removeAttribute('required');
            } else if (category === 'Institutional') {
                document.getElementById('institutionalFields').style.display = 'block';
                // Make institutional-specific keywords required
                document.getElementById('keywords_course').removeAttribute('required');
                document.getElementById('keywords_other').removeAttribute('required');
                document.getElementById('Key_words_institutional').setAttribute('required',
                    'required');
            }
        });
    });

    // Set initial state (Course is default)
    document.getElementById('courseFields').style.display = 'block';
    document.getElementById('courseVideoLink').style.display = 'block';
    document.getElementById('courseAttachments').style.display = 'block';

    // Active/Archive Course Toggle - Updated for btn-check
    const btnActiveCourses = document.getElementById('btnActiveCourses');
    if (btnActiveCourses) {
        btnActiveCourses.addEventListener('change', function() {
            try {
                if (this.checked) {
                    filterCourses('active', 'course_name');
                }
            } catch (error) {
                console.warn('Course toggle error (active):', error);
            }
        });
    }

    const btnArchivedCourses = document.getElementById('btnArchivedCourses');
    if (btnArchivedCourses) {
        btnArchivedCourses.addEventListener('change', function() {
            try {
                if (this.checked) {
                    filterCourses('archived', 'course_name');
                }
            } catch (error) {
                console.warn('Course toggle error (archived):', error);
            }
        });
    }

    // Active/Archive Course Toggle for Other Category
    const btnActiveCoursesOther = document.getElementById('btnActiveCoursesOther');
    if (btnActiveCoursesOther) {
        btnActiveCoursesOther.addEventListener('change', function() {
            try {
                if (this.checked) {
                    filterCourses('active', 'course_name_other');
                }
            } catch (error) {
                console.warn('Course toggle error (other active):', error);
            }
        });
    }

    const btnArchivedCoursesOther = document.getElementById('btnArchivedCoursesOther');
    if (btnArchivedCoursesOther) {
        btnArchivedCoursesOther.addEventListener('change', function() {
            try {
                if (this.checked) {
                    filterCourses('archived', 'course_name_other');
                }
            } catch (error) {
                console.warn('Course toggle error (other archived):', error);
            }
        });
    }

    function filterCourses(status, selectId) {
        try {
            const courseSelect = document.getElementById(selectId);
            if (!courseSelect) {
                console.warn('Course select element not found:', selectId);
                return;
            }

            // Reset selection
            courseSelect.value = '';

            // Hide all options except the first one (-- Select --)
            Array.from(courseSelect.options).forEach(option => {
                if (option.value === '') {
                    option.style.display = ''; // Show "-- Select --" option
                } else if (option.dataset.status === status) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });

            // Clear dependent dropdowns
            if (selectId === 'course_name') {
                const subjectSelect = document.getElementById('subject_name');
                const timetableSelect = document.getElementById('timetable_name');
                const sessionSelect = document.getElementById('session_date');

                if (subjectSelect) {
                    subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
                }
                if (timetableSelect) {
                    timetableSelect.innerHTML = '<option value="">-- Select Topic --</option>';
                }
                if (sessionSelect) {
                    sessionSelect.innerHTML = '<option value="">-- Select Session Date --</option>';
                }
                updateKeywords();
            } else {
                updateKeywordsOther();
            }
        } catch (error) {
            console.warn('Filter courses error:', error);
        }
    }

    // Bind cascading change events for Course -> Group -> Timetable
    $('#course_name').on('change', function() {
        onCourseChange('course_name', 'subject_name');
        updateKeywords();
    });

    $('#subject_name').on('change', function() {
        onGroupChange('subject_name', 'timetable_name');
        updateKeywords();
    });

    // Update keywords for Other category - COMMENTED OUT (using the main function defined above)
    // This prevents duplicate function definitions that override the correct version

    // Handle timetable selection to populate session date and author
    $('#timetable_name').on('change', function() {
        const timetablePk = $(this).val();
        const $sessionDate = $('#session_date');
        const $authorName = $('#author_name');

        // Clear the dropdowns
        $sessionDate.html('<option value="">-- Select --</option>');
        $authorName.html('<option value="">-- Select --</option>');

        if (!timetablePk) return;

        // Get the full timetable data to extract date and faculty
        $.ajax({
            url: "{{ route('course-repository.timetables') }}",
            type: "GET",
            data: {
                group_pk: $('#subject_name').val(),
                course_master_pk: $('#course_name').val()
            },
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Find the selected timetable in the response
                    const selectedTimetable = response.data.find(t => t.pk == timetablePk);
                    if (selectedTimetable) {
                        // Format date as dd-mm-yyyy for display
                        const dateFormatted = selectedTimetable.START_DATE ?
                            new Date(selectedTimetable.START_DATE).toLocaleDateString(
                                'en-GB', {
                                    day: '2-digit',
                                    month: '2-digit',
                                    year: 'numeric'
                                }).replace(/\//g, '-') :
                            '';

                        // Populate session date using jQuery for better compatibility
                        $sessionDate.empty().append(
                            $('<option></option>').val(dateFormatted).text(
                                dateFormatted)
                        ).val(dateFormatted);

                        // Populate author/faculty name if exists
                        if (selectedTimetable.faculty_name && selectedTimetable.faculty_name
                            .trim()) {
                            $authorName.empty().append(
                                $('<option></option>').val(selectedTimetable.pk).text(
                                    selectedTimetable.faculty_name)
                            ).val(selectedTimetable.pk);
                        } else {
                            // If no faculty name, show empty option
                            $authorName.html('<option value="">-- Select --</option>');
                        }

                        // Auto-update keywords after loading date and author
                        setTimeout(function() {
                            updateKeywords();
                        }, 100);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('Error loading timetable details:', error);
            }
        });
    });

    // Bind keyword update to dropdown changes for Course category
    $('#course_name').on('change', updateKeywords);
    $('#subject_name').on('change', updateKeywords);
    $('#timetable_name').on('change', updateKeywords);
    $('#session_date').on('change', updateKeywords);
    $('#author_name').on('change', updateKeywords);
    $('#sector_master').on('change', updateKeywords);
    $('#ministry_master').on('change', updateKeywords);

    // Sector change handler -> Load Ministries
    $('#sector_master').on('change', function() {
        const sectorPk = $(this).val();
        const $ministrySelect = $('#ministry_master');

        if (!sectorPk) {
            // Reset ministry dropdown
            $ministrySelect.html('<option value="">-- Select --</option>').val('');
            return;
        }

        // Fetch ministries for selected sector
        $.ajax({
            url: '{{ route("course-repository.ministries-by-sector") }}',
            type: 'GET',
            data: {
                sector_pk: sectorPk
            },
            success: function(response) {
                if (response.success) {
                    $ministrySelect.html('<option value="">-- Select --</option>');

                    response.data.forEach(function(ministry) {
                        $ministrySelect.append(
                            $('<option></option>')
                            .val(ministry.pk)
                            .text(ministry.ministry_name)
                        );
                    });

                    // Clear ministry selection and update keywords
                    $ministrySelect.val('');
                    updateKeywords();
                } else {
                    console.log('Error:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Error loading ministries:', error);
            }
        });
    });

    // Bind keyword update to fields for Other category (on keyup and change)
    $('#course_name_other').on('change', updateKeywordsOther);
    $('#major_subject_other').on('keyup change', updateKeywordsOther);
    $('#topic_name_other').on('keyup change', updateKeywordsOther);
    $('#session_date_other').on('keyup change', updateKeywordsOther);
    $('#author_name_other').on('keyup change', updateKeywordsOther);
    $('#sector_master_other').on('change', updateKeywordsOther);
    $('#ministry_master_other').on('change', updateKeywordsOther);

    // Listen for upload modal show event to trigger keywords update
    const uploadModalElement = document.getElementById('uploadModal');
    if (uploadModalElement) {
        uploadModalElement.addEventListener('shown.bs.modal', function() {
            const errEl = document.getElementById('uploadFormErrors');
            if (errEl) {
                errEl.classList.add('d-none');
                errEl.innerHTML = '';
            }
            setTimeout(function() {
                updateKeywords();
                updateKeywordsOther();
            }, 100);
        });
    }

    // Sector change handler for Other category -> Load Ministries
    $('#sector_master_other').on('change', function() {
        const sectorPk = $(this).val();
        const $ministrySelect = $('#ministry_master_other');

        if (!sectorPk) {
            // Reset ministry dropdown
            $ministrySelect.html('<option value="">-- Select --</option>').val('');
            return;
        }

        // Fetch ministries for selected sector
        $.ajax({
            url: '{{ route("course-repository.ministries-by-sector") }}',
            type: 'GET',
            data: {
                sector_pk: sectorPk
            },
            success: function(response) {
                if (response.success) {
                    $ministrySelect.html('<option value="">-- Select --</option>');

                    response.data.forEach(function(ministry) {
                        $ministrySelect.append(
                            $('<option></option>')
                            .val(ministry.pk)
                            .text(ministry.ministry_name)
                        );
                    });

                    // Clear ministry selection and update keywords
                    $ministrySelect.val('');
                    updateKeywordsOther();
                } else {
                    console.log('Error:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Error loading ministries:', error);
            }
        });
    });

    // Edit button functionality
    document.querySelectorAll('.edit-repo').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            try {
                const pk = this.getAttribute('data-pk');
                const name = this.getAttribute('data-name');
                const details = this.getAttribute('data-details');
                const image = this.getAttribute('data-image');

                // Populate edit form
                const nameInput = document.getElementById('edit_course_repository_name');
                const detailsInput = document.getElementById('edit_course_repository_details');

                if (nameInput) nameInput.value = name || '';
                if (detailsInput) detailsInput.value = details || '';

                // Show current image if exists
                const currentImageContainer = document.getElementById(
                    'current_image_container_show');
                const currentImage = document.getElementById('current_image_show');
                if (image && image !== 'null' && image !== '' && currentImage &&
                    currentImageContainer) {
                    currentImage.src = '/storage/' + image;
                    currentImageContainer.style.display = 'block';
                } else if (currentImageContainer) {
                    currentImageContainer.style.display = 'none';
                }

                // Clear preview
                const previewImage = document.getElementById('preview_edit_show');
                if (previewImage) {
                    previewImage.style.display = 'none';
                }

                // Update form action
                const editForm = document.getElementById('editForm');
                if (editForm && pk) {
                    editForm.action = `/course-repository/${pk}`;
                }

                // Show modal
                const editModalEl = document.getElementById('editModal');
                if (editModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const editModal = new bootstrap.Modal(editModalEl);
                    editModal.show();
                }
            } catch (error) {
                console.warn('Edit button functionality error:', error);
            }
        });
    });

    // Create form submit with modern UX
    const createForm = document.getElementById('createForm');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();

            try {
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');

                if (!submitBtn) {
                    console.warn('Submit button not found in create form');
                    return;
                }

                const originalText = submitBtn.innerHTML;

                // Modern loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';

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
                            // Close modal
                            const createModalEl = document.getElementById('createModal');
                            if (createModalEl && typeof bootstrap !== 'undefined' && bootstrap
                                .Modal) {
                                const modal = bootstrap.Modal.getInstance(createModalEl);
                                if (modal) {
                                    modal.hide();
                                }
                            }

                            // Show success toast
                            showToast('success', data.message || 'Category created successfully!');

                            // Reload with smooth transition
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showToast('error', data.message || 'Failed to create category');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    })
                    .catch(error => {
                        console.error('Create form error:', error);
                        showToast('error', 'Network error occurred. Please try again.');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
            } catch (error) {
                console.warn('Create form submit error:', error);
            }
        });
    }

    // Edit form submit
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


    // Modern loading state
    submitBtn.disabled = true;

    // Delete document
    document.querySelectorAll('.delete-doc').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            try {
                const pk = this.getAttribute('data-pk');

                if (typeof Swal !== 'undefined') {
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
                            const csrfToken = document.querySelector('[name="_token"]');
                            if (!csrfToken) {
                                console.warn('CSRF token not found');
                                return;
                            }

                            fetch(`/course-repository/document/${pk}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': csrfToken.value,
                                        'Content-Type': 'application/json'
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Deleted!',
                                            text: 'Document has been deleted.',
                                            showConfirmButton: false,
                                            timer: 1500
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error!',
                                            text: data.error ||
                                                'Delete failed'
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Delete error:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: 'Delete failed'
                                    });
                                });
                        }
                    });
                } else {
                    // Fallback if SweetAlert is not available
                    if (confirm('Are you sure you want to delete this document?')) {
                        showToast('success', 'Document deleted successfully');
                        // Add actual delete logic here
                    }
                }
            } catch (error) {
                console.warn('Delete document error:', error);
            }
        });
    });

    // Delete category
    document.querySelectorAll('.delete-repo').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
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
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/course-repository/${pk}`;

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = document.querySelector('[name="_token"]').value;

                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';

                    form.appendChild(csrfInput);
                    form.appendChild(methodInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });

    // Add new attachment row - Category Specific
    document.querySelectorAll('.addAttachmentRowBtn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.getAttribute('data-category');
            const tableBody = this.closest('.mb-3').querySelector(
                `.attachmentTableBody[data-category="${category}"]`);
            const rowCount = tableBody.querySelectorAll('.attachment-row').length + 1;

            const newRow = document.createElement('tr');
            newRow.className = 'attachment-row';

            // Get correct field names based on category
            let titleFieldName = 'attachment_titles[]';
            let filesFieldName = 'attachments[]';

            if (category === 'other') {
                titleFieldName = 'attachment_titles_other[]';
                filesFieldName = 'attachments_other[]';
            } else if (category === 'institutional') {
                titleFieldName = 'attachment_titles_institutional[]';
                filesFieldName = 'attachments_institutional[]';
            }

            newRow.innerHTML = `
                <td class="text-center row-number">
                    <span class="badge bg-light text-dark">${rowCount}</span>
                </td>
                <td>
                    <input type="text" class="form-control" name="${titleFieldName}" placeholder="Document title">
                </td>
                <td>
                    <input type="file" class="form-control" name="${filesFieldName}" accept="*/*">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                        <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
                    </button>
                </td>
            `;

            tableBody.appendChild(newRow);
            updateRowNumbersForCategory(tableBody);

            // Add delete handler to new row
            newRow.querySelector('.remove-row').addEventListener('click', function(e) {
                e.preventDefault();
                newRow.remove();
                updateRowNumbersForCategory(tableBody);
            });
        });
    });

    // Function to update row numbers for specific category
    function updateRowNumbersForCategory(tableBody) {
        const rows = tableBody.querySelectorAll('.attachment-row');
        rows.forEach((row, index) => {
            row.querySelector('.row-number').textContent = index + 1;

            // Show/hide delete button: hide for first row if only 1 row, show for others
            const deleteBtn = row.querySelector('.remove-row');
            if (rows.length === 1) {
                deleteBtn.style.display = 'none';
            } else {
                deleteBtn.style.display = 'block';
            }
        });
    }

    // Remove attachment row - Category Specific
    document.querySelectorAll('.attachmentTableBody').forEach(tableBody => {
        tableBody.querySelectorAll('.remove-row').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                this.closest('tr').remove();
                updateRowNumbersForCategory(tableBody);
            });
        });
    });
    // Final safety checks and initialization
    try {
        // Check if essential Bootstrap components are available
        if (typeof bootstrap === 'undefined') {
            console.warn('Bootstrap JavaScript not loaded properly');
        }

        // Check for missing DOM elements that might cause errors
        const criticalElements = [
            'createForm', 'editForm', 'uploadForm'
        ];

        criticalElements.forEach(id => {
            const element = document.getElementById(id);
            if (!element) {
                console.warn(`Critical element missing: ${id}`);
            }
        });

        // Initialize any remaining tooltips that might have been missed
        setTimeout(() => {
            try {
                const tooltips = document.querySelectorAll(
                    '[data-bs-toggle="tooltip"]:not([data-bs-original-title])');
                tooltips.forEach(el => {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                        new bootstrap.Tooltip(el);
                    }
                });
            } catch (tooltipError) {
                console.warn('Additional tooltip initialization failed:', tooltipError);
            }
        }, 1000);

        // Prevent common jQuery errors if jQuery is not loaded
        if (typeof $ === 'undefined') {
            // Create a basic jQuery-like function for compatibility
            window.$ = function(selector) {
                return {
                    val: function(value) {
                        const el = document.querySelector(selector);
                        if (el) {
                            if (value !== undefined) {
                                el.value = value;
                                return this;
                            }
                            return el.value;
                        }
                        return '';
                    },
                    html: function(html) {
                        const el = document.querySelector(selector);
                        if (el && html !== undefined) {
                            el.innerHTML = html;
                        }
                        return this;
                    },
                    find: function(subselector) {
                        const el = document.querySelector(selector);
                        return el ? el.querySelectorAll(subselector) : [];
                    },
                    each: function(callback) {
                        const els = document.querySelectorAll(selector);
                        els.forEach(callback);
                        return this;
                    }
                };
            };
        }

    } catch (finalError) {
        console.warn('Final initialization error:', finalError);
    }

    // Upload form submit is handled by document-level listener (see top of script)
});

// ===== ATTACHMENT ADD MORE FUNCTIONALITY - OUTSIDE DOMContentLoaded =====

// Update Delete Button Visibility
function updateDeleteButtons(tbodyId) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    const rows = tbody.querySelectorAll('.attachment-row');
    const deleteButtons = tbody.querySelectorAll('.delete-attachment');

    // Show delete buttons only if there's more than 1 row
    deleteButtons.forEach((btn, index) => {
        if (rows.length > 1) {
            btn.style.display = 'inline-block';
        } else {
            btn.style.display = 'none';
        }
    });
}

// Add More Attachment for Course Category
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.add-attachment-course');
    if (btn) {
        e.preventDefault();
        console.log('Course Add More clicked');

        const tbody = document.getElementById('course_attachments_tbody');
        if (!tbody) {
            console.error('course_attachments_tbody not found');
            return;
        }

        const rowCount = tbody.querySelectorAll('.attachment-row').length + 1;

        const newRow = document.createElement('tr');
        newRow.className = 'attachment-row';
        newRow.innerHTML = `
            <td class="row-number">${rowCount}</td>
            <td>
                <input type="text" class="form-control form-control-sm" 
                    name="attachment_titles[]" placeholder="e.g., Week-${rowCount}">
            </td>
            <td>
                <input type="file" class="form-control form-control-sm" 
                    name="attachments[]" accept="*/*">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger delete-attachment">
                    <span class="material-icons material-symbols-rounded" style="font-size: 16px;">delete</span>
                </button>
            </td>
        `;

        tbody.appendChild(newRow);
        updateDeleteButtons('course_attachments_tbody');
        console.log('Added row to Course attachments. Total rows:', rowCount);
    }
});

// Add More Attachment for Other Category
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.add-attachment-other');
    if (btn) {
        e.preventDefault();
        console.log('Other Add More clicked');

        const tbody = document.getElementById('other_attachments_tbody');
        if (!tbody) {
            console.error('other_attachments_tbody not found');
            return;
        }

        const rowCount = tbody.querySelectorAll('.attachment-row').length + 1;

        const newRow = document.createElement('tr');
        newRow.className = 'attachment-row';
        newRow.innerHTML = `
            <td class="row-number">${rowCount}</td>
            <td>
                <input type="text" class="form-control form-control-sm" 
                    name="attachment_titles_other[]" placeholder="e.g., Document-${rowCount}">
            </td>
            <td>
                <input type="file" class="form-control form-control-sm" 
                    name="attachments_other[]" accept="*/*">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger delete-attachment">
                    <span class="material-icons material-symbols-rounded" style="font-size: 16px;">delete</span>
                </button>
            </td>
        `;

        tbody.appendChild(newRow);
        updateDeleteButtons('other_attachments_tbody');
        console.log('Added row to Other attachments. Total rows:', rowCount);
    }
});

// Delete Attachment Row
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.delete-attachment');
    if (btn) {
        e.preventDefault();
        console.log('Delete clicked');

        const row = btn.closest('.attachment-row');
        const tbody = row.closest('tbody');
        const tbodyId = tbody.id;

        // Immediately remove the required attribute from inputs to prevent validation errors
        const inputs = row.querySelectorAll('input[required]');
        inputs.forEach(input => input.removeAttribute('required'));

        // Remove the row with animation
        row.style.opacity = '0';
        row.style.transition = 'opacity 0.3s ease-out';

        // Use requestAnimationFrame for smoother timing
        setTimeout(() => {
            // Completely remove the row from DOM
            row.remove();

            // Update row numbers
            const rows = tbody.querySelectorAll('.attachment-row');
            rows.forEach((r, index) => {
                r.querySelector('.row-number').textContent = index + 1;
            });

            // Update delete button visibility
            updateDeleteButtons(tbodyId);
            console.log('Row deleted');
        }, 300);
    }
});
</script>