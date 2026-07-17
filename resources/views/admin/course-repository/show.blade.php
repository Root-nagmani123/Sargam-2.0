@extends('admin.layouts.master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet"
    href="{{ asset('css/course-repository-admin.css') }}?v={{ @filemtime(public_path('css/course-repository-admin.css')) ?: time() }}">
<style>
    /* Course Name dropdown (Choices.js): keep the control at the column width and
       let long course names wrap onto a second line inside the open menu instead
       of overflowing. */
    .cr-course-choices .choices,
    .cr-course-choices .choices__inner,
    .cr-course-choices .choices__list--dropdown {
        width: 100%;
        max-width: 100%;
    }
    .cr-course-choices .choices__list--dropdown .choices__item,
    .cr-course-choices .choices__list[aria-expanded] .choices__item {
        white-space: normal !important;
        overflow-wrap: anywhere;
        word-break: break-word;
        line-height: 1.3;
    }
    /* Selected value stays on one line (ellipsis) so the closed field height is stable. */
    .cr-course-choices .choices__list--single .choices__item {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Edit-modal current-file preview card (thumbnail-or-icon + name + × to replace). */
    .cr-edit-preview-thumb {
        width: 44px;
        height: 44px;
        object-fit: cover;
        background: #f8f9fa;
    }
    .cr-edit-preview-icon i {
        font-size: 1.9rem;
        line-height: 1;
        color: #6c757d;
    }
    .cr-edit-preview .btn-close {
        padding: 0.3rem;
        background-size: 0.65em;
    }
</style>
@endpush

@section('title', ($repository->course_repository_name ?? 'Repository Details') . ' | Lal Bahadur')

@section('setup_content')
@include('admin.partials.choices-bootstrap5')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('category_image_create');
    if (el) {
        el.addEventListener('change', function(e) {
            var file = e.target.files[0];
            var preview = document.getElementById('preview_create_show');
            if (!file || !preview) return;
            var reader = new FileReader();
            reader.onload = function() {
                preview.src = reader.result;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>

<div class="container-fluid cr-admin pb-3">
    @php
    $crumbItems = [
    ['label' => 'Home', 'url' => route('admin.dashboard')],
    ['label' => 'Course Repository', 'url' => route('course-repository.index')],
    ];
    if (!empty($ancestors)) {
    foreach ($ancestors as $ancestor) {
    $crumbItems[] = [
    'label' => $ancestor->course_repository_name,
    'url' => route('course-repository.show', $ancestor->pk),
    ];
    }
    }
    $crumbItems[] = ['label' => $repository->course_repository_name, 'url' => null];
    @endphp

    <x-breadcrum :title="$repository->course_repository_name" :items="$crumbItems">
        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 cr-repo-breadcrumb-toolbar"
            role="toolbar" aria-label="Repository actions">
            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#uploadModal"
                aria-label="Upload documents"
                class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold text-nowrap shadow-sm">
                <i class="bi bi-upload" aria-hidden="true"></i>
                <span class="d-none d-sm-inline">Upload Documents</span>
                <span class="d-inline d-sm-none">Upload</span>
            </a>
            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#createModal"
                aria-label="Add new sub category"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold text-nowrap shadow-sm">
                <i class="bi bi-folder-plus" aria-hidden="true"></i>
                <span class="d-none d-sm-inline">Add Sub Category</span>
                <span class="d-inline d-sm-none">Add</span>
            </a>
        </div>
    </x-breadcrum>

    {{-- Every failure path in downloadDocument() (and the create/update/delete actions)
         does redirect()->back()->with('error', ...). Without this component that flash
         is rendered nowhere, so a failed download just bounced the user back to this
         page with no message — indistinguishable from "the button reloaded the page". --}}
    <x-session_message />

    @if($repository->children->count() == 0 && $documents->count() == 0)
    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="text-center py-5 px-3 cr-admin-empty">
                <div
                    class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 cr-admin-empty-icon">
                    <i class="bi bi-folder-x display-6 text-secondary" aria-hidden="true"></i>
                </div>
                <h5 class="text-secondary mb-2 fw-semibold">No Content Found</h5>
                <p class="text-muted mb-4 small">Start by adding a sub-category or uploading a document.</p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="javascript:void(0)" class="btn btn-primary rounded-1 px-4 fw-semibold"
                        data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="bi bi-folder-plus me-1" aria-hidden="true"></i>Add Sub Category
                    </a>
                    <a href="javascript:void(0)" class="btn btn-outline-primary rounded-1 px-4 fw-semibold"
                        data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-upload me-1" aria-hidden="true"></i>Upload Document
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Sub-Categories Section -->
    @if($repository->children->count() > 0)
    <div class="card overflow-hidden rounded-3 mb-4">
        <div class="card-body p-3 p-md-4">

            <div
                class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <h2 class="cr-admin-section-title mb-0">Sub-Categories</h2>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" data-bs-toggle="modal"
                        data-bs-target="#childColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="child_repositories"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table"
                        id="child_repositories">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Category</th>
                                <th>Details</th>
                                <th>Sub-Categories</th>
                                <th>Documents</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                            <tbody>
                                @foreach ($repository->children as $index => $child)

                                <tr>

                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        <div class="d-flex align-items-center gap-3">

                                            @if(filled($child->category_image) &&
                                            \Storage::disk('public')->exists($child->category_image))

                                            <img src="{{ asset('storage/' . $child->category_image) }}" alt=""
                                                class="rounded-circle object-fit-cover flex-shrink-0" width="40"
                                                height="40">

                                            @else

                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0"
                                                style="width:40px;height:40px;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>

                                            @endif

                                            <a href="{{ route('course-repository.show', $child->pk) }}"
                                                class="cr-link-category">
                                                {{ $child->course_repository_name }}
                                            </a>

                                        </div>
                                    </td>

                                    <td>
                                        <span class="text-muted small">
                                            {{ Str::limit($child->course_repository_details ?? 'N/A', 60) }}
                                        </span>
                                    </td>

                                    <td>
                                        <a href="{{ route('course-repository.show', $child->pk) }}"
                                            class="cr-link-subcategory {{ $child->children->count() == 0 ? 'cr-link-muted' : '' }}">
                                            {{ $child->children->count() }} Sub-Categories
                                        </a>
                                    </td>

                                    <td>
                                        @php $childDocCount = $child->getDocumentCount(); @endphp
                                        <a href="{{ route('course-repository.show', $child->pk) }}"
                                            class="cr-link-documents {{ $childDocCount == 0 ? 'cr-link-muted' : '' }}">
                                            View {{ str_pad($childDocCount, 2, '0', STR_PAD_LEFT) }}
                                            Attachments
                                        </a>
                                    </td>

                                    <td>

                                        <div class="d-inline-flex align-items-center gap-2">

                                            <button type="button" class="programme-action-btn edit-repo"
                                                data-pk="{{ $child->pk }}"
                                                data-name="{{ $child->course_repository_name }}"
                                                data-details="{{ $child->course_repository_details }}"
                                                data-image="{{ $child->category_image }}"
                                                data-attachment="{{ $child->category_attachment }}" title="Edit"
                                                aria-label="Edit sub-category">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </button>

                                            <button type="button"
                                                class="programme-action-btn programme-action-btn--danger delete-repo"
                                                data-pk="{{ $child->pk }}" title="Delete"
                                                aria-label="Delete sub-category">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>

                                        </div>

                                    </td>

                                </tr>

                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                        data-dt-footer-for="child_repositories"></div>
                </div>

            </div>
        </div>
        @endif

        <!-- Documents Section -->
        @if($documents->count() > 0)
        <div class="card overflow-hidden rounded-3">
            <div class="card-body p-3 p-md-4">

                <div
                    class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                    <h2 class="cr-admin-section-title mb-0">Documents</h2>
                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <button type="button" class="btn programme-dt-btn-columns" data-bs-toggle="modal"
                            data-bs-target="#documentsColumnVisibilityModal" title="Show / hide columns">
                            <span>Columns</span>
                            <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                        </button>
                        <div class="programme-dt-search" data-dt-search-for="documents"></div>
                    </div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="documents">
                            <thead>
                                <tr>
                                    <th scope="col">S. No.</th>
                                    <th scope="col">Document Name</th>
                                    <th scope="col">File Title</th>
                                    <th scope="col">Course Name</th>
                                    <th scope="col">Subject</th>
                                    <th scope="col">Topic</th>
                                    <th scope="col">Session Date</th>
                                    <th scope="col">Sector</th>
                                    <th scope="col">Ministry</th>
                                    <th scope="col">Author</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                        <tbody>
                            @foreach ($documents as $index => $doc)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                <td class="cru-col-dsno">{{ $loop->iteration }}</td>
                                <td class="cru-col-docname">
                                    <i class="bi bi-file-earmark-pdf text-danger me-1" aria-hidden="true"></i>
                                    <span
                                        class="fw-semibold">{{ Str::limit($doc->upload_document ?? 'N/A', 30) }}</span>
                                </td>
                                <td class="cru-col-filetitle">{{ Str::limit($doc->file_title ?? 'N/A', 25) }}</td>
                                <td class="cru-col-coursename">
                                    @if($doc->fallback_course)
                                    {{ $doc->fallback_course }}
                                    @else
                                    N/A
                                    @endif
                                </td>
                                <td class="text-center cru-col-subject">
                                    <small class="text-muted">
                                        @if($doc->fallback_subject)
                                        {{ Str::limit($doc->fallback_subject, 20) }}
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center cru-col-topic">
                                    <small class="text-muted">
                                        @if($doc->fallback_topic)
                                        {{ Str::limit($doc->fallback_topic, 20) }}
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center cru-col-sessiondate">
                                    <small class="text-muted">
                                        @if($doc->detail && $doc->detail->session_date)
                                        {{ \Carbon\Carbon::parse($doc->detail->session_date)->format('d M Y') }}
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center cru-col-sector">
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
                                <td class="text-center cru-col-ministry">
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
                                <td class="text-center cru-col-author">
                                    <small class="text-muted">
                                        @if($doc->fallback_author)
                                        {{ Str::limit($doc->fallback_author, 15) }}
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="cru-col-daction">
                                    <div class="d-inline-flex align-items-center gap-2" role="group"
                                        aria-label="Document actions">
                                        <button type="button" class="programme-action-btn edit-doc"
                                            data-pk="{{ $doc->pk }}" data-bs-toggle="tooltip" title="Edit"
                                            aria-label="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                                        <a href="{{ route('course-repository.document.download', $doc->pk) }}?file={{ urlencode($doc->upload_document) }}"
                                            class="programme-action-btn" data-bs-toggle="tooltip" title="Download"
                                            aria-label="Download">
                                            <i class="bi bi-download" aria-hidden="true"></i>
                                        </a>
                                        <button type="button"
                                            class="programme-action-btn programme-action-btn--danger delete-doc"
                                            data-pk="{{ $doc->pk }}" data-bs-toggle="tooltip" title="Delete"
                                            aria-label="Delete">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                        data-dt-footer-for="documents"></div>
                </div>

            </div>
        </div>
        @endif
</div>

<!-- Create Category Modal -->
<div class="modal fade cr-design-modal" id="createModal" tabindex="-1" aria-labelledby="createModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered cr-design-modal-sm">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Add Sub Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="createForm" method="POST" action="{{ route('course-repository.store') }}"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="parent_type" value="{{ $repository->pk }}">

                <div class="modal-body">

                    <div class="mb-3">
                        <label for="course_repository_name" class="form-label">
                            Sub-Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="course_repository_name"
                            name="course_repository_name" placeholder="eg. E-Office" required>
                    </div>

                    <div class="mb-3">
                        <label for="course_repository_details" class="form-label">Description</label>
                        <textarea class="form-control" id="course_repository_details" name="course_repository_details"
                            rows="3" placeholder="Add description"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-flex align-items-center gap-1">
                            Thumbnail Image
                            <i class="bi bi-info-circle-fill text-primary" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="JPEG, PNG, JPG or GIF · Max 2MB" role="img"
                                aria-label="Allowed formats: JPEG, PNG, JPG, GIF up to 2MB"></i>
                        </label>
                        @include('admin.course-repository.partials.cr-design-file', [
                        'inputId' => 'category_image_create',
                        'inputName' => 'category_image',
                        'required' => true,
                        'accept' => 'image/jpeg,image/png,image/jpg,image/gif',
                        ])
                        <div class="form-text small text-muted mt-1">JPEG, PNG, JPG, GIF (Max 2MB)</div>
                        <div class="mt-2">
                            <img id="preview_create_show" class="img-thumbnail rounded-1 shadow-sm d-none"
                                alt="Category image preview" style="max-width: 120px;">
                        </div>
                    </div>

                </div>

                <div class="modal-footer cr-admin-modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-primary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Add Sub-Category</button>
                </div>
            </form>

        </div>
    </div>
</div>


<!-- Edit Category Modal -->
<div class="modal fade cr-design-modal" id="editModal" tabindex="-1" aria-labelledby="editModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered cr-design-modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Sub Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_course_repository_name" class="form-label">
                            Sub-Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="edit_course_repository_name"
                            name="course_repository_name" placeholder="eg. E-Office" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_repository_details" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_course_repository_details"
                            name="course_repository_details" rows="3"
                            placeholder="eg. Enter description...."></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label d-flex align-items-center gap-1">
                            Thumbnail Image
                            <i class="bi bi-info-circle-fill text-primary" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="JPEG, PNG, JPG or GIF · Max 2MB" role="img"
                                aria-label="Allowed formats: JPEG, PNG, JPG, GIF up to 2MB"></i>
                        </label>
                        <div id="current_image_container_show" class="mb-2" style="display: none;">
                            <p class="text-muted mb-1 small">Current image</p>
                            <img id="current_image_show" src="" alt="Current" class="img-thumbnail rounded-1 shadow-sm"
                                style="max-width: 120px;">
                        </div>
                        @include('admin.course-repository.partials.cr-design-file', [
                        'inputId' => 'category_image_edit',
                        'inputName' => 'category_image',
                        'accept' => 'image/jpeg,image/png,image/jpg,image/gif',
                        ])
                        <div class="form-text small text-muted mt-1">JPEG, PNG, JPG, GIF (Max 2MB)</div>
                        <div class="mt-2">
                            <img id="preview_edit_show" src="" alt="Preview" class="img-thumbnail rounded-1 shadow-sm"
                                style="max-width: 120px; display: none;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer cr-admin-modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-primary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update Sub-Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade cr-design-modal" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered cr-design-modal-upload">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadForm" method="POST" action="javascript:void(0);" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="upload_edit_pk" id="upload_edit_pk" value="">
                <div id="uploadFormErrors" class="alert alert-danger d-none mx-3 mt-3 mb-0" role="alert"></div>
                <div id="uploadEditNotice" class="alert alert-info d-none mx-3 mt-3 mb-0 py-2 small" role="status">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span>
                            <i class="bi bi-info-circle me-1"></i>
                            Current file:
                            <a id="uploadEditCurrentFile" class="fw-semibold text-decoration-underline" href="#"
                                target="_blank" rel="noopener" style="color:#0d6efd;"></a>
                        </span>
                        {{-- A real, labelled button (not a bare btn-link glyph, which was there
                             but invisible against the notice text). type="button" because this
                             sits inside #uploadForm and a default submit button would submit the
                             form instead of deleting. Reuses the existing .delete-doc delegated
                             handler (document-level); its data-pk is filled by setEditChrome() on
                             edit-open and cleared on reset so a stray click never targets pk "". --}}
                        <button type="button" id="uploadEditDeleteBtn"
                            class="delete-doc btn btn-sm btn-danger d-inline-flex align-items-center gap-1"
                            data-pk="" title="Delete this document" aria-label="Delete this document">
                            <i class="bi bi-trash" aria-hidden="true"></i> Delete
                        </button>
                    </div>

                    {{-- Preview of the currently-uploaded file with a × to clear it. The ×
                         does NOT delete anything server-side — it swaps the UI into "pick a
                         new file" mode and focuses the file input; the replacement is
                         committed on save once a new file is chosen. (The red Delete button
                         above is the separate "remove this document entirely" action.)
                         Populated by setEditChrome(); img falls back to a type icon when the
                         file isn't an image or can't load (e.g. file missing from storage). --}}
                    <div id="uploadEditPreview"
                        class="cr-edit-preview d-none position-relative d-inline-flex align-items-center gap-2 mt-2 p-2 pe-4 bg-white border rounded">
                        <img id="uploadEditPreviewImg" src="" alt="Current file preview"
                            class="cr-edit-preview-thumb rounded border d-none"
                            onerror="this.classList.add('d-none');var ic=document.getElementById('uploadEditPreviewIcon');if(ic)ic.classList.remove('d-none');">
                        <span id="uploadEditPreviewIcon" class="cr-edit-preview-icon d-none">
                            <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                        </span>
                        <span id="uploadEditPreviewName" class="text-truncate" style="max-width:260px;"></span>
                        <button type="button" id="uploadEditPreviewClear"
                            class="btn-close position-absolute top-0 end-0 m-1"
                            aria-label="Remove current file and upload a new one"
                            title="Remove — then choose a new file below"></button>
                    </div>
                    <div id="uploadEditReplacingHint" class="text-success mt-2 d-none">
                        <i class="bi bi-check-circle me-1"></i>
                        Current file removed. Choose a new file in the "Document Upload" box below to replace it.
                    </div>

                    <div class="mt-1">
                        Choose a new file only if you want to replace it. The "Document Upload" box below only
                        shows a file once you pick a <em>new</em> one to replace it with — browsers can't
                        pre-fill a file input with the existing file's name.
                    </div>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label d-block">Document Type <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input category-radio" type="radio" name="category"
                                    id="category_course" value="Course" checked>
                                <label class="form-check-label" for="category_course">Course</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input category-radio" type="radio" name="category"
                                    id="category_other" value="Other">
                                <label class="form-check-label" for="category_other">Other</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input category-radio" type="radio" name="category"
                                    id="category_institutional" value="Institutional">
                                <label class="form-check-label" for="category_institutional">Institutional</label>
                            </div>
                        </div>
                    </div>

                    <p class="cr-design-overview-title">Overview</p>
                    <hr class="cr-design-divider">

                    <div class="card cr-upload-form-panel mb-0">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-semibold text-dark">Course Repository of LBSNAA</h6>
                        </div>
                        <div class="card-body p-0 bg-white">

                            <!-- Course Category Fields -->
                            <div id="courseFields" class="category-fields">
                                <div class="mb-3">
                                    <label class="form-label d-block">Document Location <span
                                            class="text-danger">*</span></label>
                                    <div class="btn-group cr-course-status-group" role="group"
                                        aria-label="Course Status Filter">
                                        <input type="radio" class="btn-check" name="course_status" id="btnActiveCourses"
                                            value="active" checked>
                                        <label class="btn btn-outline-success btn-sm"
                                            for="btnActiveCourses">Active</label>
                                        <input type="radio" class="btn-check" name="course_status"
                                            id="btnArchivedCourses" value="archived">
                                        <label class="btn btn-outline-secondary btn-sm"
                                            for="btnArchivedCourses">Archived</label>
                                    </div>
                                </div>

                                <!-- Row 1: Course Name & Major Subject Name -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6 choices-bs-scope cr-course-choices">
                                        <label for="course_name" class="form-label">
                                            Course Name <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select form-select-sm" id="course_name" name="course_name" required data-no-choices>
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
                                            name="keywords_course" placeholder="eg. Lorem ipsum dolor sit amet"
                                            required>
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
                                    <label for="video_link_course" class="form-label">Video Link</label>
                                    <input type="url" class="form-control" id="video_link_course"
                                        name="video_link_course" placeholder="https://www.youtube.com/watch?v=...">
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-info-circle me-1"></i> Enter video URL (YouTube, Vimeo, etc.)
                                    </small>
                                </div>

                                <!-- Document Upload -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        Document Upload <span class="text-danger">*</span>
                                        <i class="bi bi-info-circle text-muted ms-1"
                                            title="Max 10MB. jpg, jpeg, png, pdf, doc, docx" aria-hidden="true"></i>
                                    </label>

                                    <div class="table-responsive" id="course_attachments_container">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th style="width: 4%;">#</th>
                                                    <th>Title</th>
                                                    <th>File</th>
                                                    <th style="width: 6%;"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="course_attachments_tbody">
                                                <tr class="attachment-row cr-upload-attach-row">
                                                    <td class="row-number">1</td>
                                                    <td>
                                                        <input type="text" class="form-control"
                                                            name="attachment_titles[]" placeholder="eg. Week-01">
                                                    </td>
                                                    <td>
                                                        <input type="file" class="form-control" name="attachments[]"
                                                            accept="*/*">
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button"
                                                            class="btn cr-btn-remove-row delete-attachment"
                                                            style="display: none;" aria-label="Remove row">
                                                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-2">
                                        <button type="button" class="btn cr-btn-add-row add-attachment-course"
                                            data-category="course" aria-label="Add attachment row">
                                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Other Category Fields -->
                            <div id="otherFields" class="category-fields" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label d-block">Document Location <span
                                            class="text-danger">*</span></label>
                                    <div class="btn-group cr-course-status-group" role="group"
                                        aria-label="Other Course Status Filter">
                                        <input type="radio" class="btn-check" name="course_status_other"
                                            id="btnActiveCoursesOther" value="active" checked>
                                        <label class="btn btn-outline-success btn-sm"
                                            for="btnActiveCoursesOther">Active</label>
                                        <input type="radio" class="btn-check" name="course_status_other"
                                            id="btnArchivedCoursesOther" value="archived">
                                        <label class="btn btn-outline-secondary btn-sm"
                                            for="btnArchivedCoursesOther">Archived</label>
                                    </div>
                                </div>

                                <!-- Row 1: Course Name & Major Subject Name -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="course_name_other" class="form-label">
                                            Course Name <span class="text-danger">*</span>
                                        </label>
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
                                            name="keywords_other" placeholder="eg. Lorem ipsum dolor sit amet">
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
                                    <label for="video_link_other" class="form-label">Video Link</label>
                                    <input type="url" class="form-control" id="video_link_other" name="video_link_other"
                                        placeholder="https://www.youtube.com/watch?v=...">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        Document Upload <span class="text-danger">*</span>
                                        <i class="bi bi-info-circle text-muted ms-1" aria-hidden="true"></i>
                                    </label>

                                    <div class="table-responsive" id="other_attachments_container">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th style="width: 4%;">#</th>
                                                    <th>Title</th>
                                                    <th>File</th>
                                                    <th style="width: 6%;"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="other_attachments_tbody">
                                                <tr class="attachment-row cr-upload-attach-row">
                                                    <td class="row-number">1</td>
                                                    <td>
                                                        <input type="text" class="form-control"
                                                            name="attachment_titles_other[]"
                                                            placeholder="eg. Document-01">
                                                    </td>
                                                    <td>
                                                        <input type="file" class="form-control"
                                                            name="attachments_other[]" accept="*/*">
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button"
                                                            class="btn cr-btn-remove-row delete-attachment"
                                                            style="display: none;" aria-label="Remove row">
                                                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-2">
                                        <button type="button" class="btn cr-btn-add-row add-attachment-other"
                                            data-category="other" aria-label="Add attachment row">
                                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Institutional Category Fields -->
                            <div id="institutionalFields" class="category-fields" style="display: none;">

                                <div class="mb-3">
                                    <label for="Key_words_institutional" class="form-label">
                                        Keywords <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="Key_words_institutional"
                                        name="Key_words_institutional" placeholder="eg. Lorem ipsum dolor sit amet">
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
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
                                    <div class="col-md-6">
                                        <label for="ministry_master_institutional" class="form-label">Ministry <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" id="ministry_master_institutional"
                                            name="ministry_master_institutional">
                                            <option value="" selected>Select</option>
                                            @foreach(($ministries ?? []) as $ministry)
                                            <option value="{{ $ministry->pk }}"
                                                data-sector="{{ $ministry->sector_master_pk }}" style="display:none;">
                                                {{ $ministry->ministry_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label d-block">
                                        Document Upload <span class="text-danger">*</span>
                                        <i class="bi bi-info-circle text-muted ms-1" aria-hidden="true"></i>
                                    </label>
                                    @include('admin.course-repository.partials.cr-design-file', [
                                    'inputId' => 'attachments_institutional',
                                    'inputName' => 'attachments_institutional[]',
                                    'inputClass' => 'file-input-institutional',
                                    'accept' => '*/*',
                                    'multiple' => true,
                                    ])
                                    <div class="selected-files-institutional mt-2 text-start small text-muted"
                                        style="display:none;"></div>
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

                <div class="modal-footer cr-admin-modal-footer d-flex justify-content-end">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="uploadBtn">Add Document</button>
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
                        <span class="badge bg-primary rounded-1 ms-auto">{{ $documents->count() ?? 0 }}</span>
                    </label>
                    <label class="list-group-item">
                        <input class="form-check-input me-1" type="checkbox" value="categories" checked>
                        <span class="material-symbols-outlined me-1"
                            style="font-size: 16px;">folder</span>Sub-Categories
                        <span
                            class="badge bg-success rounded-1 ms-auto">{{ $repository->children->count() ?? 0 }}</span>
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

<!-- Column Visibility — Sub-Categories & Documents tables -->
@foreach ([
    ['id' => 'childColumnVisibilityModal', 'grid' => 'childColumnToggleGrid', 'label' => 'Sub-Categories'],
    ['id' => 'documentsColumnVisibilityModal', 'grid' => 'documentsColumnToggleGrid', 'label' => 'Documents'],
] as $colvis)
<div class="modal fade" id="{{ $colvis['id'] }}" tabindex="-1" aria-labelledby="{{ $colvis['id'] }}Label"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="{{ $colvis['id'] }}Label">
                    Column Visibility — {{ $colvis['label'] }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="{{ $colvis['grid'] }}"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4"
                    data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endforeach

@include('admin.course-repository.partials.single-click-links')
@endsection

@section('scripts')
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

        // Edit mode: when the modal was opened from a document's Edit button, send an
        // update instead of creating new records. submit() returns true once it takes over.
        if (window.crDocEdit && window.crDocEdit.isEditing()) {
            if (window.crDocEdit.submit(form, {
                    showError: showUploadError
                })) {
                return;
            }
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

        var repositoryPk = @json($repository->pk);
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

// ===== Course Name -> Choices.js (searchable, fixed width, wraps long names) =====
// Choices owns the rendered dropdown, so the native data-status show/hide trick no
// longer works; we keep a canonical snapshot of every course and rebuild the
// Choices list (filtered by Active/Archived) on demand instead.
//
// These MUST stay at script scope, NOT inside the crDocEdit IIFE below. Two separate
// scopes use them: the IIFE (edit/prefill, via syncCourseChoiceForEdit) and the
// DOMContentLoaded block further down (Choices init + the Active/Archived radio
// handler). While they were declared inside the IIFE, `var` made them private to it,
// so the DOMContentLoaded block threw "courseChoices is not defined" on first touch —
// which aborted that whole callback, and every handler it had yet to register
// (including the Active/Archived radios) silently never bound.
var courseChoices = null;
var courseOptionsAll = []; // [{ value, label, status }]

function getCheckedCourseStatus() {
    var checked = document.querySelector('input[name="course_status"]:checked');
    return checked ? checked.value : 'active';
}

// Rebuild the Course Name choices to only those matching `status`.
// When `keepValue` is passed, that course is re-selected after the rebuild.
function applyCourseStatusChoices(status, keepValue) {
    if (!courseChoices) return;
    var list = [{ value: '', label: 'Select', placeholder: true, selected: !keepValue }];
    courseOptionsAll.forEach(function (o) {
        if (o.status === status) {
            list.push({ value: o.value, label: o.label });
        }
    });
    courseChoices.setChoices(list, 'value', 'label', true); // replace existing
    if (keepValue !== undefined && keepValue !== null && keepValue !== '') {
        try { courseChoices.setChoiceByValue(String(keepValue)); } catch (e) { /* ignore */ }
    }
}

// Used by the edit/prefill flow: make sure the saved course is present and selected,
// syncing the Active/Archived radio so the visible list matches.
function syncCourseChoiceForEdit(pk, label) {
    if (!courseChoices || pk === null || pk === undefined || pk === '') return;
    pk = String(pk);
    var found = null;
    for (var i = 0; i < courseOptionsAll.length; i++) {
        if (String(courseOptionsAll[i].value) === pk) { found = courseOptionsAll[i]; break; }
    }
    var status = found ? found.status : getCheckedCourseStatus();
    if (!found) {
        courseOptionsAll.push({ value: pk, label: label || pk, status: status });
    }
    var radio = document.querySelector('input[name="course_status"][value="' + status + '"]');
    if (radio && !radio.checked) radio.checked = true;
    applyCourseStatusChoices(status, pk);
}

// ===== DOCUMENT EDIT MODE — reuses the Upload modal so the form looks identical to "Add" =====
window.crDocEdit = (function() {
    'use strict';

    function $id(id) {
        return document.getElementById(id);
    }

    // Attachment rows can repeat (name="attachments[]" etc.) when added via the
    // "+" button, so pick the input that actually has a file selected rather
    // than assuming it's always the first one in DOM order.
    function findFileInput(name) {
        var inputs = document.querySelectorAll('#uploadForm input[name="' + name + '"]');
        for (var i = 0; i < inputs.length; i++) {
            if (inputs[i].files && inputs[i].files.length > 0) return inputs[i];
        }
        return inputs[0] || null;
    }

    function wait(ms) {
        return new Promise(function(resolve) {
            setTimeout(resolve, ms);
        });
    }

    function fireChange(el) {
        if (el) el.dispatchEvent(new Event('change', {
            bubbles: true
        }));
    }

    // NOTE: courseChoices / courseOptionsAll / getCheckedCourseStatus /
    // applyCourseStatusChoices / syncCourseChoiceForEdit used to be declared HERE. They
    // now live at script scope, above this IIFE, because the DOMContentLoaded block
    // below uses them too and a `var` in here is private to this function.

    // Set a <select> value, injecting a labeled option if it isn't present.
    function setSelectValue(selectId, value, label) {
        var sel = $id(selectId);
        if (!sel || value === null || value === undefined || value === '') return sel;
        value = String(value);
        var present = false;
        for (var i = 0; i < sel.options.length; i++) {
            if (String(sel.options[i].value) === value) {
                present = true;
                break;
            }
        }
        if (!present) {
            var opt = document.createElement('option');
            opt.value = value;
            opt.textContent = label || value;
            sel.appendChild(opt);
        }
        sel.value = value;
        return sel;
    }

    // Wait for an async-loaded option to appear, then select it (and fire change so the
    // next cascade level loads). Falls back to injecting a labeled option after timeout.
    function selectWhenReady(selectId, value, label, timeoutMs) {
        return new Promise(function(resolve) {
            if (value === null || value === undefined || value === '') {
                resolve(false);
                return;
            }
            value = String(value);
            var start = (window.performance && performance.now) ? performance.now() : Date.now();
            (function check() {
                var sel = $id(selectId);
                if (sel) {
                    for (var i = 0; i < sel.options.length; i++) {
                        if (String(sel.options[i].value) === value) {
                            sel.value = value;
                            fireChange(sel);
                            resolve(true);
                            return;
                        }
                    }
                }
                var now = (window.performance && performance.now) ? performance.now() : Date.now();
                if (now - start > (timeoutMs || 3500)) {
                    var s = setSelectValue(selectId, value, label);
                    fireChange(s);
                    resolve(false);
                    return;
                }
                setTimeout(check, 80);
            })();
        });
    }

    function setVal(id, value) {
        var el = $id(id);
        if (el) el.value = (value === null || value === undefined) ? '' : value;
    }

    // Like selectWhenReady, but skips the poll-for-up-to-3.5s wait when the backend
    // already told us (via the *_resolved flag) that no matching master record exists —
    // e.g. a legacy imported document whose subject/topic/ministry foreign keys don't
    // match a local row, so the AJAX-loaded option was never going to appear. Waiting
    // out the full timeout per field made editing such documents take several seconds.
    //
    // Deliberately does NOT fireChange in the unresolved case: the next cascade level
    // (e.g. subject -> topics, topic -> session date/author) would fetch against this
    // fabricated id, get an empty result, and its handler resets the downstream <select>
    // to a bare "Select" placeholder. If that fetch resolves after we've already
    // prefilled the downstream field from saved data, it silently wipes it back to
    // empty — which is why Author Name (etc.) came back blank for these documents even
    // though the value was set correctly moments earlier.
    function selectFast(selectId, value, label, resolved, timeoutMs) {
        if (resolved === false) {
            setSelectValue(selectId, value, label);
            return Promise.resolve(false);
        }
        return selectWhenReady(selectId, value, label, timeoutMs);
    }

    // Pre-fill the Course-category section (cascading dropdowns).
    function prefillCourse(d) {
        var courseSel = setSelectValue('course_name', d.course_master_pk, d.course_name);
        syncCourseChoiceForEdit(d.course_master_pk, d.course_name); // keep Choices UI in sync
        // Same reasoning as selectFast: when the saved course doesn't match a local
        // record, loading subjects for it is pointless AND dangerous — that fetch
        // resolves during the wait(450) below and would wipe the subject/topic values
        // we're about to fast-fill from saved data.
        if (d.course_resolved !== false) fireChange(courseSel); // -> loads subjects
        return Promise.resolve()
            .then(function() {
                return selectFast('subject_name', d.subject_pk, d.subject_name, d.subject_resolved);
            }) // -> loads topics
            .then(function() {
                return selectFast('timetable_name', d.topic_pk, d.topic_name, d.topic_resolved);
            }) // -> loads session/author
            .then(function() {
                return wait(450);
            }) // let session/author auto-fill settle, then override with saved values
            .then(function() {
                setVal('session_date', d.session_date);
                setSelectValue('author_name', d.author_name, d.author_label);
                var sectorSel = setSelectValue('sector_master', d.sector_master_pk, d.sector_name);
                fireChange(sectorSel); // -> loads ministries
                return selectFast('ministry_master', d.ministry_master_pk, d.ministry_name, d.ministry_resolved);
            })
            .then(function() {
                // keywords + video LAST — cascade change handlers overwrite keywords
                setVal('keywords_course', d.keyword);
                setVal('video_link_course', d.videolink);
                setVal('session_date', d.session_date);
            });
    }

    // Pre-fill the Other-category section (mostly free-text inputs).
    function prefillOther(d) {
        setSelectValue('course_name_other', d.course_master_pk, d.course_name);
        setVal('major_subject_other', d.subject_pk);
        setVal('topic_name_other', d.topic_pk);
        setVal('session_date_other', d.session_date);
        setVal('author_name_other', d.author_name);
        var sectorSel = setSelectValue('sector_master_other', d.sector_master_pk, d.sector_name);
        fireChange(sectorSel); // -> loads ministries (Other)
        return selectFast('ministry_master_other', d.ministry_master_pk, d.ministry_name, d.ministry_resolved)
            .then(function() {
                setVal('keywords_other', d.keyword);
                setVal('video_link_other', d.videolink);
            });
    }

    // Pre-fill the Institutional-category section.
    function prefillInstitutional(d) {
        setVal('Key_words_institutional', d.keyword);
        var sectorSel = setSelectValue('sector_master_institutional', d.sector_master_pk, d.sector_name);
        fireChange(sectorSel);
        return selectFast('ministry_master_institutional', d.ministry_master_pk, d.ministry_name, d.ministry_resolved);
    }

    // Put the document title into the first attachment row of the active category.
    function setTitleRow(category, fileTitle) {
        var name = category === 'Other' ? 'attachment_titles_other[]' : 'attachment_titles[]';
        var titleInput = document.querySelector('#uploadForm input[name="' + name + '"]');
        if (titleInput) titleInput.value = fileTitle || '';
    }

    function selectCategory(category) {
        var map = {
            Course: 'category_course',
            Other: 'category_other',
            Institutional: 'category_institutional'
        };
        var radio = $id(map[category] || 'category_course');
        if (radio) {
            radio.checked = true;
            fireChange(radio); // toggles which category-fields section is visible
        }
    }

    // Choose a Bootstrap-Icons file glyph from the file extension.
    function fileIconClass(fileName) {
        var ext = String(fileName || '').split('.').pop().toLowerCase();
        if (ext === 'pdf') return 'bi-file-earmark-pdf';
        if (ext === 'doc' || ext === 'docx') return 'bi-file-earmark-word';
        if (ext === 'xls' || ext === 'xlsx' || ext === 'csv') return 'bi-file-earmark-excel';
        if (ext === 'ppt' || ext === 'pptx') return 'bi-file-earmark-ppt';
        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].indexOf(ext) !== -1) return 'bi-file-earmark-image';
        return 'bi-file-earmark-text';
    }
    function isImageFile(fileName) {
        var ext = String(fileName || '').split('.').pop().toLowerCase();
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].indexOf(ext) !== -1;
    }

    // Fill (or clear) the current-file preview card: thumbnail for images, type icon
    // otherwise. Cleared in create mode. The "replacing" hint is reset every time so a
    // reopened modal never shows a stale "file removed" message.
    function setEditFilePreview(on, fileName, fileUrl) {
        var wrap = $id('uploadEditPreview');
        var img = $id('uploadEditPreviewImg');
        var icon = $id('uploadEditPreviewIcon');
        var nameEl = $id('uploadEditPreviewName');
        var hint = $id('uploadEditReplacingHint');

        if (hint) hint.classList.add('d-none');
        if (wrap) wrap.classList.toggle('d-none', !on);
        if (nameEl) nameEl.textContent = on ? (fileName || '') : '';

        if (!on) {
            if (img) { img.classList.add('d-none'); img.removeAttribute('src'); }
            if (icon) icon.classList.add('d-none');
            return;
        }
        if (icon) {
            var i = icon.querySelector('i');
            if (i) i.className = 'bi ' + fileIconClass(fileName);
        }
        if (isImageFile(fileName) && fileUrl && img && icon) {
            img.src = fileUrl;              // onerror in markup falls back to the icon
            img.classList.remove('d-none');
            icon.classList.add('d-none');
        } else {
            if (img) { img.classList.add('d-none'); img.removeAttribute('src'); }
            if (icon) icon.classList.remove('d-none');
        }
    }

    function setEditChrome(on, currentFile, pk, fileUrl) {
        var title = $id('uploadModalLabel');
        if (title) title.textContent = on ? 'Edit Document' : 'Upload Document';
        var btn = $id('uploadBtn');
        if (btn) btn.textContent = on ? 'Update Document' : 'Add Document';
        var notice = $id('uploadEditNotice');
        if (notice) notice.classList.toggle('d-none', !on);
        var cur = $id('uploadEditCurrentFile');
        if (cur) {
            cur.textContent = currentFile || '';
            cur.href = (on && pk) ? ('/course-repository/document/' + pk + '/download') : '#';
        }
        // Point the inline delete icon at THIS document. Cleared in "create" mode so
        // the shared .delete-doc handler (which bails on an empty data-pk) can't fire.
        var del = $id('uploadEditDeleteBtn');
        if (del) del.setAttribute('data-pk', (on && pk) ? pk : '');
        setEditFilePreview(on, currentFile, fileUrl);

        // A document being edited has exactly one file. The "Add More Attachment"
        // (+) rows exist only for the bulk "Add Document" flow — if left visible
        // during edit, a file picked into row 2+ is silently ignored by submit()
        // (which only reads the first attachments[] input), so the update
        // "succeeds" without the new file ever reaching the server. Collapse any
        // leftover extra rows and hide "add row" while editing.
        ['course_attachments_tbody', 'other_attachments_tbody'].forEach(function(tbodyId) {
            var tbody = $id(tbodyId);
            if (!tbody) return;
            var rows = tbody.querySelectorAll('.attachment-row');
            for (var i = 1; i < rows.length; i++) rows[i].remove();
        });
        Array.prototype.forEach.call(
            document.querySelectorAll('.add-attachment-course, .add-attachment-other'),
            function(b) { b.style.display = on ? 'none' : ''; }
        );
    }

    // Reset the upload modal back to "create" mode.
    function reset() {
        var pkEl = $id('upload_edit_pk');
        if (pkEl) pkEl.value = '';
        setEditChrome(false, '');
        var form = $id('uploadForm');
        if (form) delete form.dataset.currentFileTitle;
    }

    // Populate the upload modal from the document's data and open it in edit mode.
    function enter(data) {
        var form = $id('uploadForm');
        if (form) {
            try {
                form.reset();
            } catch (e) {}
            // Same reason as the show.bs.modal handler: form.reset() empties the Course
            // Name Choices list. Rebuild it before prefilling, so editing an Other /
            // Institutional document (neither re-syncs it) still leaves a populated
            // dropdown behind the Course radio. prefillCourse() re-applies it with the
            // saved course selected.
            applyCourseStatusChoices(getCheckedCourseStatus());
            form.dataset.currentFileTitle = data.file_title || '';
        }
        var pkEl = $id('upload_edit_pk');
        if (pkEl) pkEl.value = data.pk;

        var category = data.category || 'Course';
        selectCategory(category);
        setEditChrome(true, data.upload_document || '', data.pk, data.file_url || '');
        setTitleRow(category, data.file_title);

        var d = data.detail || {};
        var done;
        if (category === 'Other') done = prefillOther(d);
        else if (category === 'Institutional') done = prefillInstitutional(d);
        else done = prefillCourse(d);

        var modalEl = $id('uploadModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).show();
        }
        return done;
    }

    function isEditing() {
        var pkEl = $id('upload_edit_pk');
        return !!(pkEl && pkEl.value);
    }

    // Build the update payload from the upload form and POST it. Returns true if it handled submit.
    function submit(form, helpers) {
        var pkEl = $id('upload_edit_pk');
        var pk = pkEl ? pkEl.value : '';
        if (!pk) return false; // not in edit mode — let the normal upload flow run

        helpers = helpers || {};
        var showError = helpers.showError || function(m) {
            alert(m);
        };
        var category = (document.querySelector('input[name="category"]:checked') || {}).value || 'Course';

        var fd = new FormData();
        fd.append('_token', (form.querySelector('[name="_token"]') || {}).value || '');
        fd.append('category', category);

        // Map per-category fields to the controller's expected names
        var fileTitle, fileInput;
        if (category === 'Course') {
            fd.append('course_name', (($id('course_name') || {}).value) || '');
            fd.append('subject_name', (($id('subject_name') || {}).value) || '');
            fd.append('timetable_name', (($id('timetable_name') || {}).value) || '');
            fd.append('session_date', (($id('session_date') || {}).value) || '');
            fd.append('author_name', (($id('author_name') || {}).value) || '');
            fd.append('sector_master', (($id('sector_master') || {}).value) || '');
            fd.append('ministry_master', (($id('ministry_master') || {}).value) || '');
            fd.append('keywords', (($id('keywords_course') || {}).value) || '');
            fd.append('video_link', (($id('video_link_course') || {}).value) || '');
            var t1 = document.querySelector('#uploadForm input[name="attachment_titles[]"]');
            fileTitle = t1 ? t1.value : '';
            fileInput = findFileInput('attachments[]');
        } else if (category === 'Other') {
            fd.append('course_name', (($id('course_name_other') || {}).value) || '');
            fd.append('subject_name', (($id('major_subject_other') || {}).value) || '');
            fd.append('timetable_name', (($id('topic_name_other') || {}).value) || '');
            fd.append('session_date', (($id('session_date_other') || {}).value) || '');
            fd.append('author_name', (($id('author_name_other') || {}).value) || '');
            fd.append('sector_master', (($id('sector_master_other') || {}).value) || '');
            fd.append('ministry_master', (($id('ministry_master_other') || {}).value) || '');
            fd.append('keywords', (($id('keywords_other') || {}).value) || '');
            fd.append('video_link', (($id('video_link_other') || {}).value) || '');
            var t2 = document.querySelector('#uploadForm input[name="attachment_titles_other[]"]');
            fileTitle = t2 ? t2.value : '';
            fileInput = findFileInput('attachments_other[]');
        } else {
            fd.append('keywords', (($id('Key_words_institutional') || {}).value) || '');
            fd.append('sector_master', (($id('sector_master_institutional') || {}).value) || '');
            fd.append('ministry_master', (($id('ministry_master_institutional') || {}).value) || '');
            fileTitle = (form.dataset.currentFileTitle || ''); // institutional has no title field
            fileInput = document.querySelector('#uploadForm input[name="attachments_institutional[]"]');
        }

        // Keep the existing title if the row was cleared
        if (!fileTitle && form.dataset.currentFileTitle) fileTitle = form.dataset.currentFileTitle;
        fd.append('file_title', fileTitle || '');

        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            fd.append('document_file', fileInput.files[0]);
        }

        var csrfEl = document.querySelector('[name="_token"]') || document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfEl ? (csrfEl.getAttribute('content') || csrfEl.value) : null;

        var submitBtn = $id('uploadBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
        }
        var restoreBtn = function() {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.dataset.originalText || 'Update Document';
            }
        };

        fetch('/course-repository/document/' + pk + '/update', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                },
                body: fd
            })
            .then(function(r) {
                return r.json().then(function(data) {
                    return {
                        ok: r.ok,
                        data: data
                    };
                });
            })
            .then(function(result) {
                if (result.ok && result.data && result.data.success) {
                    var modalEl = $id('uploadModal');
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var inst = bootstrap.Modal.getInstance(modalEl);
                        if (inst) inst.hide();
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'Document has been updated.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        location.reload();
                    }
                    return;
                }
                restoreBtn();
                var errMsg = (result.data && result.data.error) || 'Update failed';
                if (result.data && result.data.errors && typeof result.data.errors === 'object') {
                    var parts = [];
                    Object.keys(result.data.errors).forEach(function(field) {
                        var val = result.data.errors[field];
                        parts.push(Array.isArray(val) ? val.join(' ') : val);
                    });
                    if (parts.length) errMsg = parts.join(' | ');
                }
                showError(errMsg);
            })
            .catch(function() {
                restoreBtn();
                showError('Network error. Please try again.');
            });

        return true; // handled
    }

    return {
        enter: enter,
        reset: reset,
        submit: submit,
        isEditing: isEditing
    };
})();

document.addEventListener('DOMContentLoaded', function() {
    // ===== EDIT/DELETE BUTTONS - Register first so they work even if other code throws =====
    document.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-repo');
        if (editBtn) {
            e.preventDefault();
            e.stopPropagation();
            try {
                const pk = editBtn.getAttribute('data-pk');
                const image = editBtn.getAttribute('data-image');
                const attachment = editBtn.getAttribute('data-attachment');
                const nameInput = document.getElementById('edit_course_repository_name');
                const detailsInput = document.getElementById('edit_course_repository_details');
                if (nameInput) nameInput.value = editBtn.getAttribute('data-name') || '';
                if (detailsInput) detailsInput.value = editBtn.getAttribute('data-details') || '';
                const currentImageContainer = document.getElementById('current_image_container_show');
                const currentImage = document.getElementById('current_image_show');
                if (image && image !== 'null' && image !== '' && currentImage &&
                    currentImageContainer) {
                    currentImage.src = '/storage/' + image;
                    currentImageContainer.style.display = 'block';
                } else if (currentImageContainer) currentImageContainer.style.display = 'none';
                const previewImage = document.getElementById('preview_edit_show');
                if (previewImage) previewImage.style.display = 'none';

                // Current attachment link
                const currentAttachmentContainer = document.getElementById(
                    'current_attachment_container_show');
                const currentAttachment = document.getElementById('current_attachment_show');
                if (attachment && attachment !== 'null' && attachment !== '' && currentAttachment &&
                    currentAttachmentContainer) {
                    currentAttachment.href = '/storage/' + attachment;
                    currentAttachmentContainer.style.display = 'block';
                } else if (currentAttachmentContainer) currentAttachmentContainer.style.display =
                    'none';

                // Reset the edit file-input labels so a stale filename isn't shown
                ['category_image_edit', 'category_attachment_edit'].forEach(function(id) {
                    const inp = document.getElementById(id);
                    if (inp) inp.value = '';
                    const lbl = document.getElementById(id + '_label');
                    if (lbl) lbl.textContent = 'No file chosen';
                });
                const editForm = document.getElementById('editForm');
                if (editForm && pk) editForm.action = '/course-repository/' + pk;
                const editModalEl = document.getElementById('editModal');
                if (editModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    (new bootstrap.Modal(editModalEl)).show();
                }
            } catch (err) {
                console.warn('Edit error:', err);
            }
            return;
        }
        // "×" on the current-file preview: switch to "pick a new file" mode. This is a
        // client-side affordance only — nothing is deleted server-side. The old file is
        // kept unless the user actually chooses a new one before saving, which is why the
        // hint tells them to pick a file below.
        const clearPreviewBtn = e.target.closest('#uploadEditPreviewClear');
        if (clearPreviewBtn) {
            e.preventDefault();
            e.stopPropagation();
            var preview = document.getElementById('uploadEditPreview');
            if (preview) preview.classList.add('d-none');
            var hint = document.getElementById('uploadEditReplacingHint');
            if (hint) hint.classList.remove('d-none');
            // Focus + reveal the first VISIBLE file input (the active category's row).
            var fileInputs = document.querySelectorAll('#uploadForm input[type="file"]');
            for (var fi = 0; fi < fileInputs.length; fi++) {
                if (fileInputs[fi].offsetParent !== null) { // visible
                    try { fileInputs[fi].scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e2) {}
                    fileInputs[fi].focus();
                    break;
                }
            }
            return;
        }

        const editDocBtn = e.target.closest('.edit-doc');
        if (editDocBtn) {
            e.preventDefault();
            e.stopPropagation();
            const pk = editDocBtn.getAttribute('data-pk');
            if (!pk || !window.crDocEdit) return;

            // Reset any stale error and fetch the document + its detail, then open the
            // Upload modal pre-filled in edit mode (identical form to "Add Document").
            const errEl = document.getElementById('uploadFormErrors');
            if (errEl) {
                errEl.classList.add('d-none');
                errEl.innerHTML = '';
            }

            fetch('/course-repository/document/' + pk + '/edit-data', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(res) {
                    if (res && res.success && res.data) {
                        window.crDocEdit.enter(res.data);
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: (res && res.error) || 'Could not load document'
                            });
                        } else {
                            alert((res && res.error) || 'Could not load document');
                        }
                    }
                })
                .catch(function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not load document'
                        });
                    } else {
                        alert('Could not load document');
                    }
                });
            return;
        }
        const deleteRepoBtn = e.target.closest('.delete-repo');
        if (deleteRepoBtn) {
            e.preventDefault();
            e.stopPropagation();
            const pk = deleteRepoBtn.getAttribute('data-pk');
            const tokenEl = document.querySelector('[name="_token"]') || document.querySelector(
                'meta[name="csrf-token"]');
            const token = tokenEl ? (tokenEl.getAttribute('content') || tokenEl.value) : null;
            if (!pk || !token) return;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/course-repository/' + pk;
                        var csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = token;
                        var methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'DELETE';
                        form.appendChild(csrfInput);
                        form.appendChild(methodInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            } else {
                if (confirm('Are you sure you want to delete this category?')) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/course-repository/' + pk;
                    var csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = token;
                    var methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    form.appendChild(csrfInput);
                    form.appendChild(methodInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
            return;
        }
        const deleteDocBtn = e.target.closest('.delete-doc');
        if (deleteDocBtn) {
            e.preventDefault();
            e.stopPropagation();
            const pk = deleteDocBtn.getAttribute('data-pk');
            if (!pk) return;
            try {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            var csrfEl = document.querySelector('[name="_token"]') || document
                                .querySelector('meta[name="csrf-token"]');
                            var csrfToken = csrfEl ? (csrfEl.getAttribute('content') || csrfEl
                                .value) : null;
                            if (!csrfToken) return;
                            fetch('/course-repository/document/' + pk, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': csrfToken,
                                        'Content-Type': 'application/json'
                                    }
                                })
                                .then(function(r) {
                                    return r.json();
                                })
                                .then(function(data) {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Deleted!',
                                            text: 'Document has been deleted.',
                                            showConfirmButton: false,
                                            timer: 1500
                                        }).then(function() {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error!',
                                            text: data.error || 'Delete failed'
                                        });
                                    }
                                })
                                .catch(function() {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: 'Delete failed'
                                    });
                                });
                        }
                    });
                } else {
                    if (confirm('Are you sure you want to delete this document?')) {
                        showToast('success', 'Document deleted successfully');
                    }
                }
            } catch (err) {
                console.warn('Delete document error:', err);
            }
        }
    });

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

    const repositoryPk = @json($repository->pk);

    // ===== COURSE FILTERING LOGIC =====
    const courseStatusRadios = document.querySelectorAll('input[name="course_status"]');
    const courseSelect = document.getElementById('course_name');

    // Turn the Course Name select into a searchable Choices dropdown. We snapshot
    // every option (incl. archived) up front, then drive the visible list from that
    // snapshot so the Active/Archived filter and edit-prefill keep working.
    if (courseSelect && typeof Choices !== 'undefined' && !courseChoices) {
        Array.prototype.forEach.call(courseSelect.options, function (opt) {
            if (opt.value === '') return; // skip the placeholder option
            courseOptionsAll.push({
                value: opt.value,
                label: (opt.textContent || '').trim(),
                status: opt.getAttribute('data-status') || 'active'
            });
        });
        try {
            courseChoices = new Choices(courseSelect, {
                searchEnabled: true,
                shouldSort: false,
                allowHTML: false,
                itemSelectText: '',
                placeholder: true,
                placeholderValue: 'Select',
                searchPlaceholderValue: 'Search…',
                position: 'bottom'
            });
            // Start filtered to the currently selected status (Active by default).
            applyCourseStatusChoices(getCheckedCourseStatus());
        } catch (e) {
            console.warn('Course Name Choices init failed', e);
            courseChoices = null;
        }
    }

    if (courseStatusRadios.length > 0 && courseSelect) {
        courseStatusRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const status = this.value;

                if (courseChoices) {
                    // Rebuild the Choices list for this status and clear the selection.
                    applyCourseStatusChoices(status);
                } else {
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
                }

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

    // Category radio button change handler
    document.querySelectorAll('.category-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const category = this.value;

            // Hide all category-specific fields and document tables
            document.querySelectorAll('.category-fields').forEach(field => {
                field.style.display = 'none';
            });

            // Show the selected category fields (null-safe element access)
            const setShow = (id, show) => {
                const el = document.getElementById(id);
                if (el) el.style.display = show ? 'block' : 'none';
            };
            const setRequired = (id, required) => {
                const el = document.getElementById(id);
                if (!el) return;
                if (required) el.setAttribute('required', 'required');
                else el.removeAttribute('required');
            };
            if (category === 'Course') {
                setShow('courseFields', true);
                setShow('courseVideoLink', true);
                setShow('courseAttachments', true);
                setRequired('keywords_course', true);
                setRequired('keywords_other', false);
                setRequired('Key_words_institutional', false);
            } else if (category === 'Other') {
                setShow('otherFields', true);
                setRequired('keywords_course', false);
                setRequired('keywords_other', true);
                setRequired('Key_words_institutional', false);
            } else if (category === 'Institutional') {
                setShow('institutionalFields', true);
                setRequired('keywords_course', false);
                setRequired('keywords_other', false);
                setRequired('Key_words_institutional', true);
            }
        });
    });

    // Set initial state (Course is default).
    // Null-safe: these elements only exist when the upload modal is rendered;
    // a hard .style access on a missing element would throw and abort the rest
    // of this DOMContentLoaded callback (breaking later handlers like edit submit).
    const cf = document.getElementById('courseFields');
    if (cf) cf.style.display = 'block';
    const cvl = document.getElementById('courseVideoLink');
    if (cvl) cvl.style.display = 'block';
    const ca = document.getElementById('courseAttachments');
    if (ca) ca.style.display = 'block';

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

    // NOTE: Course -> Subject and Subject -> Topic cascading used to be bound a SECOND
    // time here (onCourseChange/onGroupChange, via the groups/timetables endpoints),
    // duplicating the courseSelect/subjectSelect addEventListener blocks above (which use
    // the purpose-built subjects/{coursePk} and topics/{subjectPk} endpoints). Both
    // handlers fired on every change and independently replaced the same <select>'s
    // innerHTML, so whichever AJAX call resolved last "won" — and since replacing a
    // <select>'s innerHTML drops whatever was selected, a value the first handler (or the
    // edit-prefill flow) had just set could get silently reset to the placeholder by the
    // second handler resolving late. Same root cause as the session-date bug noted below,
    // one cascade level up. Removed rather than reconciled, since the addEventListener
    // versions already implement the same behavior correctly.

    // Update keywords for Other category - COMMENTED OUT (using the main function defined above)
    // This prevents duplicate function definitions that override the correct version

    // NOTE: Session date + author auto-fill on topic change is handled by the
    // topicSelect.addEventListener('change', ...) block above (fetches
    // /course-repository/session-dates and /course-repository/authors-by-topic, and
    // writes session_date in the Y-m-d format the native <input type="date"> requires).
    // A second, duplicate jQuery handler used to be bound here too, fetching the same
    // data via a different endpoint and writing the date as dd-mm-yyyy. Both handlers
    // fired on every topic change; whichever AJAX call resolved last won. Since
    // dd-mm-yyyy is invalid for <input type="date">, when the duplicate handler resolved
    // last it silently cleared the field the other handler had just filled in correctly
    // — the "session date appears then disappears" bug. Removed rather than reformatted,
    // since the other handler already covers the same behavior correctly.

    // Bind keyword update to dropdown changes for Course category
    $('#course_name').on('change', updateKeywords);
    $('#subject_name').on('change', updateKeywords);
    $('#timetable_name').on('change', updateKeywords);
    $('#session_date').on('change', updateKeywords);
    $('#author_name').on('change', updateKeywords);
    $('#sector_master').on('change', updateKeywords);
    $('#ministry_master').on('change', updateKeywords);

    // NOTE: Sector -> Ministry (Course category) used to be bound a SECOND time here too,
    // hitting the SAME ministries-by-sector endpoint as the sectorSelect
    // addEventListener block above and then unconditionally calling
    // $ministrySelect.val('') afterward — clearing the selection every time, including a
    // value the edit-prefill flow had just set moments earlier. Same duplicate-handler
    // bug as the Course/Subject cascades noted above; removed for the same reason.

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
        // Opened via an "Upload Documents" button (has relatedTarget) -> force create mode.
        // Programmatic .show() from the document Edit flow has no relatedTarget, so edit
        // mode and its pre-filled values are preserved.
        uploadModalElement.addEventListener('show.bs.modal', function(ev) {
            if (ev.relatedTarget && window.crDocEdit) {
                window.crDocEdit.reset();
                const f = document.getElementById('uploadForm');
                if (f) {
                    try {
                        f.reset();
                    } catch (e) {}
                    // Choices listens for the form's reset event and restores the store it
                    // captured before we filled it, so f.reset() leaves Course Name with
                    // nothing but the placeholder — the list only came back once an
                    // Active/Archived click rebuilt it. Rebuild it here instead, for the
                    // status the radios were just reset to (Active).
                    applyCourseStatusChoices(getCheckedCourseStatus());
                }
            }
        });
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
            var removeBtn = newRow.querySelector('.remove-row');
            if (removeBtn) {
                removeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    newRow.remove();
                    updateRowNumbersForCategory(tableBody);
                });
            }
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
        newRow.className = 'attachment-row cr-upload-attach-row';
        newRow.innerHTML = `
            <td class="row-number">${rowCount}</td>
            <td>
                <input type="text" class="form-control"
                    name="attachment_titles[]" placeholder="eg. Week-${rowCount}">
            </td>
            <td>
                <input type="file" class="form-control"
                    name="attachments[]" accept="*/*">
            </td>
            <td class="text-center">
                <button type="button" class="btn cr-btn-remove-row delete-attachment" aria-label="Remove row">
                    <i class="bi bi-dash-lg" aria-hidden="true"></i>
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
        newRow.className = 'attachment-row cr-upload-attach-row';
        newRow.innerHTML = `
            <td class="row-number">${rowCount}</td>
            <td>
                <input type="text" class="form-control"
                    name="attachment_titles_other[]" placeholder="eg. Document-${rowCount}">
            </td>
            <td>
                <input type="file" class="form-control"
                    name="attachments_other[]" accept="*/*">
            </td>
            <td class="text-center">
                <button type="button" class="btn cr-btn-remove-row delete-attachment" aria-label="Remove row">
                    <i class="bi bi-dash-lg" aria-hidden="true"></i>
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
@include('admin.course-repository.partials.cr-design-scripts')

{{-- Sub-Categories + Documents lists: client-side DataTables on the shared
     programme-dt design system. The global enhancer (js/datatable-global-ui.js)
     moves each table's search box and pagination into the matching
     [data-dt-search-for] / [data-dt-footer-for] slots. --}}
<script>
$(function() {
    var TABLES = [{
            id: 'child_repositories',
            grid: '#childColumnToggleGrid',
            storageKey: 'courseRepositoryShow:hiddenColumns:child:v1'
        },
        {
            id: 'documents',
            grid: '#documentsColumnToggleGrid',
            storageKey: 'courseRepositoryShow:hiddenColumns:documents:v1'
        }
    ];

    function getHidden(key) {
        try {
            var arr = JSON.parse(localStorage.getItem(key) || '[]');
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function persistHidden(key, arr) {
        try {
            localStorage.setItem(key, JSON.stringify(arr));
        } catch (e) {}
    }

    TABLES.forEach(function(cfg) {
        var $table = $('#' + cfg.id);
        if (!$table.length || $.fn.dataTable.isDataTable($table)) {
            return;
        }

        var dt = $table.DataTable({
            responsive: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, 'All']
            ],
            order: [],
            columnDefs: [{
                    targets: 0,
                    orderable: false,
                    searchable: false
                },
                {
                    targets: -1,
                    orderable: false,
                    searchable: false
                }
            ]
        });

        // Server order is preserved, so S. No. must follow the visible page.
        dt.on('draw.dt', function() {
            var start = dt.page.info().start;
            dt.column(0, {
                page: 'current'
            }).nodes().each(function(cell, i) {
                cell.innerHTML = start + i + 1;
            });
        });

        var hidden = getHidden(cfg.storageKey);
        dt.columns().every(function() {
            this.visible(hidden.indexOf(this.index()) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $(cfg.grid).empty();
        if (!$grid.length) {
            return;
        }

        dt.columns().every(function() {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) {
                return;
            }

            var inputId = 'colvis_' + cfg.id + '_' + idx;
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function() {
                var h = getHidden(cfg.storageKey);
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else if (pos === -1) {
                    h.push(idx);
                }
                persistHidden(cfg.storageKey, h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $('<div class="col-12 col-sm-6 col-md-4"></div>')
                .append(
                    $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId)
                    .append($cb)
                    .append($('<span></span>').text(title))
                )
                .appendTo($grid);
        });
    });
});
</script>
@endsection