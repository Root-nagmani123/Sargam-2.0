@extends('admin.layouts.master')

@section('title', 'Complaint Category')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
.master-filter-select {
    height: 40px; width: 150px; padding: 0 2rem 0 0.875rem; font-size: 0.9375rem;
    color: #344054; background-color: #fff; border: 1px solid #d0d5dd; border-radius: 8px;
}
.master-filter-select:focus { border-color: #004a93; box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12); }
.master-action-toggle { display: inline-flex; align-items: center; margin: 0 0.25rem 0 0.15rem; }
.master-action-toggle .form-check-input { margin: 0; cursor: pointer; }
.programme-action-group .material-symbols-rounded { font-size: 18px; line-height: 1; }
/* Description can be long — let it wrap instead of stretching the row. */
#masterTable td.category-desc { white-space: normal; min-width: 240px; max-width: 420px; }
/* Add-modal repeatable rows */
#categoryFieldsContainer .category-field-group + .category-field-group {
    margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--ds-line, #dee2e6);
}
</style>
@endpush

@section('content')
<div class="container-fluid issue-category-index-page py-3">
    <x-breadcrum title="Complaint Category">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Category</span>
        </button>
    </x-breadcrum>
    <x-session_message />

    <div class="d-flex flex-wrap justify-content-end align-items-center gap-3 mb-3">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="masterPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i> <span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>
                    <select id="masterStatusFilter" class="form-select master-filter-select" aria-label="Status">
                        <option value="all">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <button type="button" class="btn programme-dt-btn-reset" id="masterResetFilters">Reset Filters</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="masterBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#masterColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="masterTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap align-middle programme-dt-table" id="masterTable">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Sub-Categories</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                @php $isActive = (int) $category->status === 1; @endphp
                                <tr data-status="{{ $isActive ? 1 : 0 }}">
                                    <td class="fw-medium ps-3">{{ $loop->iteration }}</td>
                                    <td>{{ $category->issue_category }}</td>
                                    <td class="category-desc">{{ $category->description ?: '--' }}</td>
                                    <td>
                                        <span class="badge rounded-1 bg-info">{{ $category->subCategories->count() }}</span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-1 master-status-badge bg-{{ $isActive ? 'success' : 'secondary' }}">
                                            {{ $isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Category actions">
                                            <button type="button" class="programme-action-btn master-edit-btn" title="Edit"
                                                    data-pk="{{ $category->pk }}"
                                                    data-name="{{ $category->issue_category }}"
                                                    data-description="{{ $category->description }}"
                                                    data-status="{{ $isActive ? 1 : 0 }}">
                                                <i class="material-icons material-symbols-rounded" aria-hidden="true">edit</i>
                                            </button>

                                            <span class="master-action-toggle form-check form-switch mb-0" title="Toggle status">
                                                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                    data-table="issue_category_master" data-column="status"
                                                    data-id="{{ $category->pk }}"
                                                    {{ $isActive ? 'checked' : '' }}>
                                            </span>

                                            {{-- destroy() refuses to delete an active category, so mirror that here. --}}
                                            @if($isActive)
                                                <button type="button" class="programme-action-btn" disabled aria-disabled="true"
                                                        title="Set the category to Inactive before deleting">
                                                    <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
                                                </button>
                                            @else
                                                <form action="{{ route('admin.issue-categories.destroy', $category->pk) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="programme-action-btn programme-action-btn--danger" title="Delete">
                                                        <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 table-empty-state">
                                        <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                            <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">folder_off</i>
                                            <p class="mb-1 fw-semibold text-body-emphasis">No categories found.</p>
                                            <small class="text-body-secondary mb-3">Get started by creating your first complaint category.</small>
                                            <button type="button" class="btn btn-primary rounded-1 px-4 py-2"
                                                    data-bs-toggle="modal" data-bs-target="#addCategoryModal">Add Your First Category</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="masterTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- ============ Add modal (supports several categories at once) ============ --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('admin.issue-categories.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_form" value="create">
                <div class="modal-header border-0 pb-2">
                    <div>
                        <h5 class="modal-title fw-bold">Define Complaint Category</h5>
                        <p class="text-muted mb-0 small">Add one or more complaint categories</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div id="categoryFieldsContainer">
                        <div class="category-field-group" data-index="0">
                            <div class="mb-3">
                                <label class="form-label">Complaint <span class="text-danger">*</span></label>
                                <input type="text" class="form-control complaint-field"
                                       name="categories[0][issue_category]" placeholder="Enter complaint" required>
                            </div>
                            <div>
                                <label class="form-label">Description</label>
                                <div class="d-flex align-items-start gap-2">
                                    <input type="text" class="form-control description-field"
                                           name="categories[0][description]" placeholder="Enter description">
                                    <button type="button" class="btn btn-outline-danger remove-field-btn" title="Remove" style="display:none;">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @error('categories.*.issue_category')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror

                    <button type="button" class="btn btn-sm btn-outline-primary rounded-1 mt-3" id="categoryAddRow">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add another
                    </button>

                    <p class="text-muted small mb-0 mt-3">
                        <span class="text-danger">*</span> Required. New categories are created as <strong>Active</strong>.
                    </p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ Edit modal ============ --}}
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="masterEditForm" action="" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="_pk" id="masterEditPk" value="{{ old('_pk') }}">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="issue_category" id="masterEditName" class="form-control"
                               value="{{ old('_form') === 'edit' ? old('issue_category') : '' }}" required>
                        @error('issue_category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="masterEditDescription" class="form-control" rows="3"
                                  placeholder="Enter category description (optional)">{{ old('_form') === 'edit' ? old('description') : '' }}</textarea>
                        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="masterEditStatus" class="form-select" required>
                            <option value="1" {{ old('_form') === 'edit' && old('status') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('_form') === 'edit' && old('status') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <div class="form-text">You can also flip this with the switch in the Actions column.</div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.partials._master_columns_modal')
@endsection

@push('scripts')
{{-- Add modal: repeatable category rows (store() accepts a categories[] array). --}}
<script>
$(function () {
    var fieldIndex = 1;

    function reindex() {
        $('#categoryFieldsContainer .category-field-group').each(function (i) {
            $(this).attr('data-index', i);
            $(this).find('.complaint-field').attr('name', 'categories[' + i + '][issue_category]');
            $(this).find('.description-field').attr('name', 'categories[' + i + '][description]');
        });
        fieldIndex = $('#categoryFieldsContainer .category-field-group').length;
        // The first row can only be removed once a second exists.
        var multiple = fieldIndex > 1;
        $('#categoryFieldsContainer .remove-field-btn').toggle(multiple);
    }

    $('#categoryAddRow').on('click', function () {
        var $group = $('#categoryFieldsContainer .category-field-group').first().clone();
        $group.find('input').val('').removeClass('is-invalid');
        $('#categoryFieldsContainer').append($group);
        reindex();
    });

    $('#categoryFieldsContainer').on('click', '.remove-field-btn', function () {
        if ($('#categoryFieldsContainer .category-field-group').length <= 1) { return; }
        $(this).closest('.category-field-group').remove();
        reindex();
    });

    // Reset the form each time the modal closes.
    $('#addCategoryModal').on('hidden.bs.modal', function () {
        $('#categoryFieldsContainer .category-field-group:not(:first)').remove();
        $('#categoryFieldsContainer input').val('').removeClass('is-invalid');
        reindex();
    });

    reindex();
});
</script>

@include('admin.partials._master_form_scripts', [
    'updateUrl'   => route('admin.issue-categories.update', ['id' => '__ID__']),
    'createModal' => 'addCategoryModal',
    'editModal'   => 'editCategoryModal',
    'fields'      => [
        'name'        => '#masterEditName',
        'description' => '#masterEditDescription',
        'status'      => '#masterEditStatus',
    ],
])

@include('admin.partials._master_list_scripts', [
    'reportTitle'  => 'Complaint Category',
    'storageKey'   => 'issueCategoryGrid:hiddenColumns:v1',
    'statusColumn' => 4,
    'actionColumn' => 5,
    'printColumns' => [
        ['label' => 'Category Name', 'index' => 1],
        ['label' => 'Description', 'index' => 2],
        ['label' => 'Sub-Categories', 'index' => 3],
    ],
])
@endpush
