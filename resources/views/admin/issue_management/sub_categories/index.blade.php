@extends('admin.layouts.master')

@section('title', 'Complaint Sub-Category - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Complaint Sub-Category" />
    
    <!-- Success/Error Messages -->
    <div id="status-msg"></div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            Complaint Sub-Category Management
                        </h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSubCategoryModal">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">add</i>
                            Add Sub-Category
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">

                    <!-- Table Section -->
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table align-middle mb-0 text-nowrap']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Sub-Category Modal -->
<div class="modal fade" id="addSubCategoryModal" tabindex="-1" aria-labelledby="addSubCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('admin.issue-sub-categories.store') }}" method="POST" id="addSubCategoryForm">
                @csrf
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);">
                    <h5 class="modal-title fw-semibold" id="addSubCategoryModalLabel">
                        <i class="material-icons material-symbols-rounded me-2">add_circle</i>
                        Add New Sub-Category
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label for="issue_category_fk" class="form-label fw-semibold">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">category</i>
                            Category <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg @error('issue_category_master_pk') is-invalid @enderror" 
                                id="issue_category_fk" name="issue_category_master_pk" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                            @endforeach
                        </select>
                        @error('issue_category_master_pk')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="issue_sub_category" class="form-label fw-semibold">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">label</i>
                            Sub-Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg @error('issue_sub_category') is-invalid @enderror" 
                               id="issue_sub_category" 
                               name="issue_sub_category" 
                               placeholder="Enter sub-category name"
                               required>
                        @error('issue_sub_category')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-top bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">close</i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">check</i>
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Sub-Category Modal -->
<div class="modal fade" id="editSubCategoryModal" tabindex="-1" aria-labelledby="editSubCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form id="editSubCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);">
                    <h5 class="modal-title fw-semibold" id="editSubCategoryModalLabel">
                        <i class="material-icons material-symbols-rounded me-2">edit</i>
                        Edit Sub-Category
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label for="edit_issue_category_fk" class="form-label fw-semibold">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">category</i>
                            Category <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg" id="edit_issue_category_fk" name="issue_category_master_pk" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="edit_issue_sub_category" class="form-label fw-semibold">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">label</i>
                            Sub-Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="edit_issue_sub_category" 
                               name="issue_sub_category" 
                               placeholder="Enter sub-category name"
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label fw-semibold">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">toggle_on</i>
                            Status
                        </label>
                        <select class="form-select form-select-lg" id="edit_status" name="status" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">close</i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">update</i>
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
{!! $dataTable->scripts() !!}
<script>
function editSubCategory(id, categoryId, name, status) {
    document.getElementById('edit_issue_category_fk').value = categoryId != null ? String(categoryId) : '';
    document.getElementById('edit_issue_sub_category').value = name;
    document.getElementById('edit_status').value = status;

    const form = document.getElementById('editSubCategoryForm');
    form.action = "{{ url('admin/issue-sub-categories') }}/" + id;

    const modal = new bootstrap.Modal(document.getElementById('editSubCategoryModal'));
    modal.show();
}

$(document).ready(function() {
    // Show success message if redirected with success
    @if(session('success'))
        $('#status-msg').html(`
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="material-icons material-symbols-rounded me-2">check_circle</i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        setTimeout(function() {
            $('#status-msg').fadeOut();
        }, 3000);
    @endif
    
    @if(session('error'))
        $('#status-msg').html(`
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="material-icons material-symbols-rounded me-2">error</i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        setTimeout(function() {
            $('#status-msg').fadeOut();
        }, 3000);
    @endif
});
</script>
@endsection
