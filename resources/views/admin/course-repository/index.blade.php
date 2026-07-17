@extends('admin.layouts.master')

@section('title', 'Course Repositories | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/course-repository-admin.css') }}">
@endpush

@section('setup_content')
<div class="container-fluid cr-admin pb-3">
    <!-- Breadcrumb -->
    <x-breadcrum title="Course Repository" :showBack="false">
        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#createModal"
            aria-label="Add new category"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Category</span>
        </a>
    </x-breadcrum>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            @if ($repositories->isEmpty())
            <div class="text-center py-5 px-3 cr-admin-empty">
                <div
                    class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 cr-admin-empty-icon">
                    <i class="bi bi-folder2-open display-6 text-secondary" aria-hidden="true"></i>
                </div>
                <p class="text-secondary mb-2 fw-semibold">No categories yet</p>
                <p class="text-muted small mb-3">Get started by creating your first category.</p>
                <a href="javascript:void(0)" class="btn btn-primary rounded-1 px-4 fw-semibold"
                    data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Create Category
                </a>
            </div>
            @else
            <div
                class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnCrColumns"
                        data-bs-toggle="modal" data-bs-target="#crColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="crDtSearch" class="programme-dt-search" data-dt-search-for="crCategoriesTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="crCategoriesTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Category</th>
                                <th>Sub-Category</th>
                                <th>Attachment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($repositories as $key => $repo)

                            @php
                            $subCount = $repo->children->count();
                            $docCount = $repo->getDocumentCount();
                            @endphp

                            <tr>
                                <td>{{ $key + 1 }}</td>

                                <td>
                                    <div class="d-flex align-items-center gap-3">

                                        @if(filled($repo->category_image) &&
                                        \Storage::disk('public')->exists($repo->category_image))
                                        <img src="{{ asset('storage/' . $repo->category_image) }}"
                                            class="rounded-circle object-fit-cover flex-shrink-0" width="40"
                                            height="40" alt="">
                                        @else
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0"
                                            style="width:40px;height:40px;">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                        @endif

                                        <a href="{{ route('course-repository.show', $repo->pk) }}"
                                            class="cr-link-category">
                                            {{ $repo->course_repository_name }}
                                        </a>

                                    </div>
                                </td>

                                <td>
                                    <a href="{{ route('course-repository.show', $repo->pk) }}"
                                        class="cr-link-subcategory {{ $subCount == 0 ? 'cr-link-muted' : '' }}">
                                        {{ $subCount }} Sub-Category
                                    </a>
                                </td>

                                <td>
                                    <a href="{{ route('course-repository.show', $repo->pk) }}"
                                        class="cr-link-documents {{ $docCount == 0 ? 'cr-link-muted' : '' }}">
                                        See {{ str_pad($docCount, 2, '0', STR_PAD_LEFT) }} Attachment
                                    </a>
                                </td>

                                <td>
                                    <div class="d-inline-flex align-items-center gap-2">

                                        <button type="button" class="programme-action-btn edit-repo"
                                            data-pk="{{ $repo->pk }}"
                                            data-name="{{ $repo->course_repository_name }}"
                                            data-details="{{ $repo->course_repository_details }}"
                                            data-image="{{ $repo->category_image }}" title="Edit"
                                            aria-label="Edit category">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </button>

                                        <button type="button"
                                            class="programme-action-btn programme-action-btn--danger delete-repo"
                                            data-pk="{{ $repo->pk }}" title="Delete" aria-label="Delete category">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>

                                    </div>
                                </td>
                            </tr>

                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="crDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="crCategoriesTable"></div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="crColumnVisibilityModal" tabindex="-1" aria-labelledby="crColumnVisibilityLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="crColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="crColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Category Modal -->
<div class="modal fade cr-design-modal" id="createModal" tabindex="-1" aria-labelledby="createModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered cr-design-modal-sm">
        <div class="modal-content">

            <!-- Modal Header - Blue Gradient -->
            <div class="modal-header upload-modal-header text-white border-0 py-4 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" id="createModalLabel">
                    Create New Category
                </h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form method="POST" id="createForm" action="{{ route('course-repository.store') }}"
                enctype="multipart/form-data">
                @csrf

                <div class="modal-body">

                    <div class="mb-3">
                        <label for="modal_course_repository_name" class="form-label">
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="modal_course_repository_name"
                            name="course_repository_name" placeholder="eg. E-Office" required>
                    </div>

                    <div class="mb-3">
                        <label for="modal_course_repository_details" class="form-label">
                            Description <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <textarea class="form-control" id="modal_course_repository_details"
                            name="course_repository_details" rows="3"
                            placeholder="Add a description"></textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label d-block">Thumbnail Image</label>
                        @include('admin.course-repository.partials.cr-design-file', [
                        'inputId' => 'modal_category_image',
                        'inputName' => 'category_image',
                        'required' => true,
                        'accept' => 'image/jpeg,image/png,image/jpg,image/gif',
                        ])
                        <div class="form-text small text-muted mt-1">JPEG, PNG, JPG, GIF (Max 2MB)</div>
                        <div class="mt-2">
                            <img id="preview_create" alt="Image preview" class="img-thumbnail rounded-1 d-none"
                                style="max-width: 120px; object-fit: cover;">
                        </div>
                    </div>

                </div>

                <div class="modal-footer cr-admin-modal-footer d-flex justify-content-end">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
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

            <!-- Modal Header - Blue Gradient -->
            <div class="modal-header upload-modal-header text-white border-0 py-4 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" id="editModalLabel">
                    <span class="header-icon-circle">
                        <span class="material-icons material-symbols-rounded">edit</span>
                    </span>
                    Edit Category
                </h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="modal-body">

                    <div class="mb-3">
                        <label for="edit_course_repository_name" class="form-label">
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="edit_course_repository_name"
                            name="course_repository_name" placeholder="eg. E-Office" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_course_repository_details" class="form-label">
                            Description <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <textarea class="form-control" id="edit_course_repository_details"
                            name="course_repository_details" rows="3"
                            placeholder="Add a description"></textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label d-block">Attachment</label>

                        <!-- Current Image Display -->
                        <div id="current_image_container" class="mb-3" style="display: none;">
                            <p class="text-muted small mb-2">Current Image:</p>
                            <img id="current_image" src="" alt="Current" class="img-fluid rounded-2"
                                style="max-width: 120px; object-fit: cover;">
                        </div>

                        @include('admin.course-repository.partials.cr-design-file', [
                        'inputId' => 'edit_category_image',
                        'inputName' => 'category_image',
                        'accept' => 'image/jpeg,image/png,image/jpg,image/gif',
                        ])
                        <div class="form-text small text-muted mt-1">JPEG, PNG, JPG, GIF (Max 2MB)</div>

                        <!-- Preview -->
                        <div class="mt-3">
                            <img id="preview_edit" src="" alt="Preview" class="img-fluid rounded-2 d-none"
                                style="max-width: 120px; object-fit: cover;">
                        </div>
                    </div>

                </div>

                <div class="modal-footer cr-admin-modal-footer d-flex justify-content-end">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade cr-design-modal" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable cr-design-modal-sm">
        <div class="modal-content">

            <!-- Header: blue gradient -->
            <div class="modal-header upload-modal-header text-white border-0 py-4 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" id="uploadModalLabel">
                    <span class="header-icon-circle">
                        <span class="material-icons material-symbols-rounded">cloud_upload</span>
                    </span>
                    Upload Document
                </h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form id="uploadForm" enctype="multipart/form-data">
                @csrf

                <div class="modal-body">

                    <div class="mb-3">
                        <label for="file_title" class="form-label">
                            File Title <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <input type="text" class="form-control" id="file_title" name="file_title"
                            placeholder="eg. Week-01">
                    </div>

                    <div class="mb-0">
                        <label class="form-label d-block">
                            Document Upload <span class="text-danger">*</span>
                        </label>
                        @include('admin.course-repository.partials.cr-design-file', [
                        'inputId' => 'file',
                        'inputName' => 'file',
                        'required' => true,
                        'accept' => '*/*',
                        ])
                        <div class="form-text small text-muted mt-1">PDF, DOC, DOCX, XLS, XLSX, images, etc. (Max 100
                            MB)</div>
                    </div>

                </div>

                <div class="modal-footer cr-admin-modal-footer d-flex justify-content-end">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Document</button>
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
    window.getCourseRepoUpdateUrl = function(pk) {
        return courseRepoUpdateUrlTemplate.replace('___PK___', pk);
    };
    window.getCourseRepoDestroyUrl = function(pk) {
        return courseRepoDestroyUrlTemplate.replace('___PK___', pk);
    };
})();

/* ── Category list: client-side DataTable on the shared programme-dt design system.
      The global enhancer (js/datatable-global-ui.js) moves the search box and the
      pagination/length/info into #crDtSearch / #crDtFooter. ── */
$(function() {
    var $table = $('#crCategoriesTable');
    if (!$table.length || $.fn.dataTable.isDataTable($table)) {
        return;
    }

    var crTable = $table.DataTable({
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
    crTable.on('draw.dt', function() {
        var start = crTable.page.info().start;
        crTable.column(0, {
            page: 'current'
        }).nodes().each(function(cell, i) {
            cell.innerHTML = start + i + 1;
        });
    });

    /* ── Column show / hide ── */
    var crColStorageKey = 'courseRepositoryGrid:hiddenColumns:v1';

    function crGetHiddenCols() {
        try {
            var raw = localStorage.getItem(crColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function crPersistHiddenCols(arr) {
        try {
            localStorage.setItem(crColStorageKey, JSON.stringify(arr));
        } catch (e) {}
    }

    var hidden = crGetHiddenCols();

    crTable.columns().every(function() {
        this.visible(hidden.indexOf(this.index()) === -1, false);
    });
    crTable.columns.adjust();

    var $grid = $('#crColumnToggleGrid').empty();

    crTable.columns().every(function() {
        var idx = this.index();
        var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
        if (!title) {
            return;
        }

        var inputId = 'crcolvis_' + idx;
        var $cb = $('<input type="checkbox" class="form-check-input m-0">')
            .attr('id', inputId)
            .prop('checked', hidden.indexOf(idx) === -1);

        $cb.on('change', function() {
            var h = crGetHiddenCols();
            var pos = h.indexOf(idx);
            if (this.checked) {
                if (pos !== -1) h.splice(pos, 1);
            } else if (pos === -1) {
                h.push(idx);
            }
            crPersistHiddenCols(h);
            crTable.column(idx).visible(this.checked, false);
            crTable.columns.adjust();
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
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.edit-repo');
        if (!btn) {
            return;
        }
        e.preventDefault();

        const pk = btn.getAttribute('data-pk');
        const name = btn.getAttribute('data-name');
        const details = btn.getAttribute('data-details');
        const image = btn.getAttribute('data-image');

        // Clear any previous image
        document.getElementById('preview_edit').classList.add('d-none');
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
        document.getElementById('editForm').action = window.getCourseRepoUpdateUrl(pk);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
    });


    document.getElementById('createForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML =
            '<span class="material-icons material-symbols-rounded me-1">schedule</span> Saving...';

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
                submitBtn.innerHTML =
                    '<span class="material-icons material-symbols-rounded me-1">check_circle</span> Save';
            });
    });

    // Edit form submit with SweetAlert
    document.getElementById('editForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML =
            '<span class="material-icons material-symbols-rounded me-1">schedule</span> Updating...';

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
                        return {
                            ok: response.ok,
                            status: response.status,
                            message: text || 'Server error'
                        };
                    }
                    return {
                        ok: response.ok,
                        status: response.status,
                        data: data,
                        raw: text
                    };
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
                        var first = Object.keys(result.data.errors).map(function(k) {
                            return result.data.errors[k][0];
                        })[0];
                        if (first) errMsg = first;
                    }
                } else if (result.status === 419) {
                    errMsg = 'Session expired. Please refresh the page and try again.';
                } else if (result.status === 422) {
                    errMsg = 'Validation failed. Please check your input.';
                } else if (result.message && result.message.length < 200) {
                    errMsg = result.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errMsg
                });
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
                submitBtn.innerHTML =
                    '<span class="material-icons material-symbols-rounded me-1">check_circle</span> Save';
            });
    });

    // Delete button functionality with SweetAlert
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-repo');
        if (!btn) {
            return;
        }
        e.preventDefault();

        const pk = btn.getAttribute('data-pk');

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

                var csrfToken = (document.querySelector(
                    'meta[name="csrf-token"]') && document.querySelector(
                        'meta[name="csrf-token"]').getAttribute('content')) || (
                    document.querySelector('[name="_token"]') && document
                    .querySelector('[name="_token"]').value);
                if (!csrfToken) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Security token missing. Please refresh the page.'
                    });
                    return;
                }
                form.innerHTML = '<input type="hidden" name="_token" value="' +
                    csrfToken +
                    '"><input type="hidden" name="_method" value="DELETE">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    (function initUploadModal() {
        const fileInput = document.getElementById('file');
        const fileTitleInput = document.getElementById('file_title');
        const uploadModal = document.getElementById('uploadModal');

        function clearUploadZone() {
            if (fileInput) {
                fileInput.value = '';
                fileInput.dispatchEvent(new Event('change'));
            }
            if (fileTitleInput) fileTitleInput.value = '';
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
        submitBtn.innerHTML =
            '<span class="material-icons material-symbols-rounded me-1">schedule</span> Uploading...';

        const url = parentPk && parentPk != '0' ?
            `/course-repository/${parentPk}/upload-document` :
            '/course-repository/0/upload-document';

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
                submitBtn.innerHTML =
                    '<span class="material-icons material-symbols-rounded me-1">check_circle</span> Save';
            });
    });
});
</script>
@include('admin.course-repository.partials.cr-design-scripts')
@include('admin.course-repository.partials.single-click-links')
@endsection
