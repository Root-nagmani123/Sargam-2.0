@extends('admin.layouts.master')

@section('title', 'Course Repositories | Lal Bahadur')

@section('setup_content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/course-repository-admin.css') }}">
<div class="container-fluid cr-admin pb-3">
    <!-- Breadcrumb -->
    <x-breadcrum title="Course Repository">
        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#createModal"
                    aria-label="Add new category"
                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold text-nowrap shadow-sm">
                    <i class="bi bi-plus" aria-hidden="true"></i>
                    <span>Add Category</span>
                </a>
    </x-breadcrum>

    <div class="card border-0 cr-admin-card overflow-hidden">
        <div class="card-body p-3 p-md-4">
            @if ($repositories->isEmpty())
            <div class="text-center py-5 px-3 cr-admin-empty">
                <div
                    class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 cr-admin-empty-icon">
                    <i class="bi bi-folder2-open display-6 text-secondary" aria-hidden="true"></i>
                </div>
                <p class="text-secondary mb-2 fw-semibold">No categories yet</p>
                <p class="text-muted small mb-3">Get started by creating your first category.</p>
                <a href="javascript:void(0)" class="btn btn-primary rounded-2 px-3" data-bs-toggle="modal"
                    data-bs-target="#createModal">
                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Create Category
                </a>
            </div>
            @else
            <div class="table-responsive">
                <table id="crCategoriesTable" class="table datatable" data-export="false">
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

                        <tr class="border-bottom">
                            <td class="ps-4 fw-medium">
                                {{ $repositories->firstItem() + $key }}
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-3">

                                    @if(filled($repo->category_image) &&
                                    \Storage::disk('public')->exists($repo->category_image))
                                    <img src="{{ asset('storage/' . $repo->category_image) }}"
                                        class="rounded-circle object-fit-cover" width="40" height="40" alt="">
                                    @else
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                        style="width:40px;height:40px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                    @endif

                                    <div>
                                        <a href="{{ route('course-repository.show', $repo->pk) }}"
                                            class="text-decoration-none text-dark fw-medium">
                                            {{ $repo->course_repository_name }}
                                        </a>
                                    </div>

                                </div>
                            </td>

                            <td>
                                <a href="{{ route('course-repository.show', $repo->pk) }}"
                                    class="text-decoration-none small fw-medium {{ $subCount == 0 ? 'text-secondary' : '' }}">
                                    {{ $subCount }} Sub-Category
                                </a>
                            </td>

                            <td>
                                <a href="{{ route('course-repository.show', $repo->pk) }}"
                                    class="text-decoration-none small fw-medium {{ $docCount == 0 ? 'text-secondary' : '' }}">
                                    See {{ str_pad($docCount, 2, '0', STR_PAD_LEFT) }} Attachment
                                </a>
                            </td>

                            <td class="text-center">
                                <div class="d-inline-flex align-items-center">

                                    <a href="javascript:void(0)"
                                        class="btn btn-sm btn-light edit-repo bg-transparent border-0 p-0"
                                        data-pk="{{ $repo->pk }}" data-name="{{ $repo->course_repository_name }}"
                                        data-details="{{ $repo->course_repository_details }}"
                                        data-image="{{ $repo->category_image }}" title="Edit">

                                        <i class="bi bi-pencil text-primary"></i>
                                    </a>

                                    <a href="javascript:void(0)"
                                        class="btn btn-sm btn-light delete-repo bg-transparent border-0 p-0"
                                        data-pk="{{ $repo->pk }}" title="Delete">

                                        <i class="bi bi-trash text-danger"></i>
                                    </a>

                                </div>
                            </td>
                        </tr>

                        @endforeach
                    </tbody>
                </table>
            </div>
            <script>
            document.getElementById('per_page')?.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', this.value);
                url.searchParams.set('page', '1');
                window.location.href = url.toString();
            });

            // —— Columns show/hide (modal design — matches Course Repository user module) ——
            (function initColumnToggle() {
                var TABLE_ID = 'crCategoriesTable';
                var STORAGE_KEY = 'cru-columns-' + TABLE_ID;
                var MODAL_ID = 'cruColVisModal-' + TABLE_ID;

                // DataTables column index → label. locked columns stay always visible.
                var COLUMNS = [{
                        idx: 0,
                        label: 'S. No.',
                        locked: true
                    },
                    {
                        idx: 1,
                        label: 'Image'
                    },
                    {
                        idx: 2,
                        label: 'Category'
                    },
                    {
                        idx: 3,
                        label: 'Sub-Category'
                    },
                    {
                        idx: 4,
                        label: 'Attachment'
                    },
                    {
                        idx: 5,
                        label: 'Actions',
                        locked: true
                    }
                ];
                var TOGGLEABLE = COLUMNS.filter(function(c) {
                    return !c.locked;
                });

                function defaults() {
                    var s = {};
                    TOGGLEABLE.forEach(function(c) {
                        s[c.idx] = true;
                    });
                    return s;
                }

                function loadState() {
                    try {
                        var raw = localStorage.getItem(STORAGE_KEY);
                        if (!raw) return defaults();
                        var parsed = JSON.parse(raw);
                        if (!parsed || typeof parsed !== 'object') return defaults();
                        var base = defaults();
                        Object.keys(base).forEach(function(k) {
                            if (typeof parsed[k] === 'boolean') base[k] = parsed[k];
                        });
                        return base;
                    } catch (e) {
                        return defaults();
                    }
                }

                function saveState(state) {
                    try {
                        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
                    } catch (e) {}
                }

                function chips() {
                    var locked = COLUMNS.filter(function(c) {
                        return c.locked;
                    }).map(function(c) {
                        return '<div class="col">' +
                            '<label class="cru-colvis-chip cru-colvis-chip-locked d-flex align-items-center gap-2 mb-0" title="Always visible">' +
                            '<input type="checkbox" class="form-check-input m-0" checked disabled>' +
                            '<span class="text-truncate">' + c.label + '</span>' +
                            '</label></div>';
                    }).join('');
                    var toggleable = TOGGLEABLE.map(function(c) {
                        return '<div class="col">' +
                            '<label class="cru-colvis-chip d-flex align-items-center gap-2 mb-0">' +
                            '<input type="checkbox" class="form-check-input m-0 cru-col-toggle-checkbox" data-col="' +
                            c.idx + '" checked>' +
                            '<span class="text-truncate">' + c.label + '</span>' +
                            '</label></div>';
                    }).join('');
                    return locked + toggleable;
                }

                function buildModal() {
                    if (document.getElementById(MODAL_ID)) return;
                    var html =
                        '<div class="modal fade cru-colvis-modal" id="' + MODAL_ID +
                        '" tabindex="-1" aria-labelledby="' + MODAL_ID + '-label" aria-hidden="true">' +
                        '<div class="modal-dialog modal-dialog-centered modal-lg">' +
                        '<div class="modal-content border-0 rounded-4 shadow">' +
                        '<div class="modal-header border-0 pb-2 px-4 pt-4">' +
                        '<h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="' + MODAL_ID +
                        '-label">' +
                        '<i class="bi bi-sliders2 text-primary" aria-hidden="true"></i> Column Visibility' +
                        '</h5>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                        '</div>' +
                        '<hr class="cru-colvis-divider mx-4 my-0">' +
                        '<div class="modal-body px-4 py-4">' +
                        '<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">' + chips() + '</div>' +
                        '</div>' +
                        '<div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-between">' +
                        '<button type="button" class="btn btn-link btn-sm text-decoration-none p-0 d-inline-flex align-items-center gap-1 cru-col-toggle-reset">' +
                        '<i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset to default' +
                        '</button>' +
                        '<button type="button" class="btn btn-outline-primary btn-sm px-4 fw-semibold" data-bs-dismiss="modal">Close</button>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</div>';
                    document.body.insertAdjacentHTML('beforeend', html);
                }

                function build() {
                    if (!(window.jQuery && jQuery.fn && jQuery.fn.dataTable)) return false;
                    var el = document.getElementById(TABLE_ID);
                    if (!el || !jQuery.fn.dataTable.isDataTable(el)) return false;

                    var api = jQuery(el).DataTable();
                    var wrapper = el.closest('.dataTables_wrapper');
                    if (!wrapper) return false;
                    if (wrapper.querySelector('.cru-column-toggle')) return true; // already built

                    buildModal();

                    // Trigger button placed after the search input.
                    var trigger =
                        '<span class="cru-column-toggle d-inline-flex align-items-center ms-2">' +
                        '<button type="button" class="btn btn-light border btn-sm d-inline-flex align-items-center gap-2 fw-semibold cru-colvis-trigger" ' +
                        'data-bs-toggle="modal" data-bs-target="#' + MODAL_ID + '" title="Show / hide columns">' +
                        '<i class="bi bi-layout-three-columns" aria-hidden="true"></i>' +
                        '<span class="d-none d-sm-inline">Columns</span>' +
                        '</button>' +
                        '</span>';
                    var filter = wrapper.querySelector('.dataTables_filter');
                    var searchInput = filter ? filter.querySelector('input') : null;
                    if (searchInput) {
                        searchInput.insertAdjacentHTML('afterend', trigger);
                    } else if (filter) {
                        filter.insertAdjacentHTML('beforeend', trigger);
                    } else {
                        wrapper.insertAdjacentHTML('afterbegin', trigger);
                    }

                    function applyState(state) {
                        TOGGLEABLE.forEach(function(c) {
                            var vis = state[c.idx] !== false;
                            api.column(c.idx).visible(vis, false);
                            var cb = document.querySelector('.cru-col-toggle-checkbox[data-col="' + c.idx +
                                '"]');
                            if (cb) cb.checked = vis;
                        });
                        api.columns.adjust();
                    }

                    applyState(loadState());

                    document.querySelectorAll('.cru-col-toggle-checkbox').forEach(function(cb) {
                        cb.addEventListener('change', function() {
                            var idx = parseInt(this.getAttribute('data-col'), 10);
                            var next = loadState();
                            // Keep at least one toggleable column visible.
                            if (!this.checked) {
                                var visibleCount = TOGGLEABLE.filter(function(c) {
                                    return next[c.idx] !== false;
                                }).length;
                                if (visibleCount <= 1) {
                                    this.checked = true;
                                    return;
                                }
                            }
                            next[idx] = this.checked;
                            api.column(idx).visible(this.checked);
                            saveState(next);
                        });
                    });

                    var resetBtn = document.querySelector('.cru-col-toggle-reset');
                    if (resetBtn) {
                        resetBtn.addEventListener('click', function() {
                            try {
                                localStorage.removeItem(STORAGE_KEY);
                            } catch (e) {}
                            applyState(defaults());
                        });
                    }
                    return true;
                }

                // The global init runs on DOMContentLoaded; retry briefly until the DataTable exists.
                var tries = 0;
                (function attempt() {
                    if (build()) return;
                    if (tries++ < 40) setTimeout(attempt, 100);
                })();
            })();

            // —— Footer: "Showing [N] of {total} items" (page-scoped; reshapes this table only) ——
            (function initFooterLayout() {
                var TABLE_ID = 'crCategoriesTable';

                function build() {
                    if (!(window.jQuery && jQuery.fn && jQuery.fn.dataTable)) return false;
                    var el = document.getElementById(TABLE_ID);
                    if (!el || !jQuery.fn.dataTable.isDataTable(el)) return false;

                    var api = jQuery(el).DataTable();
                    var wrapper = el.closest('.dataTables_wrapper');
                    if (!wrapper) return false;
                    if (wrapper.classList.contains('cr-footer-ready')) return true;

                    var lengthWrap = wrapper.querySelector('.dataTables_length');
                    var select = lengthWrap ? lengthWrap.querySelector('select') : null;
                    var info = wrapper.querySelector('.dataTables_info');
                    if (!select || !info) {
                        wrapper.classList.add('cr-footer-ready');
                        return true;
                    }

                    // Hide the default top "Show N entries" control.
                    lengthWrap.classList.add('d-none');

                    // The global init may have injected its own "Showing" label and
                    // moved the length select; this page owns the footer, so strip
                    // that duplicate before building our own layout.
                    var strayLabel = wrapper.querySelector('.dt-showing-label');
                    if (strayLabel) strayLabel.remove();

                    function render() {
                        var total = api.page.info().recordsTotal;
                        info.innerHTML = '';
                        info.classList.add('cr-footer-info', 'd-flex', 'align-items-center',
                            'justify-content-md-end', 'gap-2', 'flex-wrap');
                        var pre = document.createElement('span');
                        pre.textContent = 'Showing';
                        var post = document.createElement('span');
                        post.textContent = 'of ' + total.toLocaleString() + ' items';
                        info.appendChild(pre);
                        info.appendChild(select);
                        info.appendChild(post);
                        select.classList.add('cr-footer-select');
                    }

                    render();
                    api.on('draw', function() {
                        if (select.parentNode !== info) render();
                    });

                    wrapper.classList.add('cr-footer-ready');
                    return true;
                }

                var tries = 0;
                (function attempt() {
                    if (build()) return;
                    if (tries++ < 40) setTimeout(attempt, 100);
                })();
            })();
            </script>
            @endif
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