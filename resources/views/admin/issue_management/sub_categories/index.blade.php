@extends('admin.layouts.master')

@section('title', 'Issue Sub-Categories - Sargam | Lal Bahadur')

@section('css')
<style>
.modal-body {
    background-color: #fff !important;
    color: #212529 !important;
}
.modal-content {
    background-color: #fff !important;
}
</style>
@endsection

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Issue Sub-Categories" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6">
                        <h4 class="mb-0">Issue Sub-Categories</h4>
                    </div>
                    <div class="col-6 text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubCategoryModal">
                            <iconify-icon icon="ep:circle-plus-filled"></iconify-icon> Add Sub-Category
                        </button>
                    </div>
                </div>
                <hr>
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive datatables">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="25%">Category</th>
                                    <th width="25%">Sub-Category Name</th>
                                    <th width="30%">Description</th>
                                    <th width="10%">Status</th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subCategories as $subCategory)
                                <tr>
                                    <td>{{ $subCategory->pk }}</td>
                                    <td>{{ $subCategory->category->issue_category ?? '-' }}</td>
                                    <td>{{ $subCategory->issue_sub_category }}</td>
                                    <td>{{ $subCategory->description ?? '-' }}</td>
                                    <td>
                                        @if($subCategory->status == 1)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-inline-flex gap-2 align-items-center">
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    onclick="editSubCategory({{ $subCategory->pk }}, {{ $subCategory->issue_category_master_pk ?? 'null' }}, {{ json_encode($subCategory->issue_sub_category) }}, {{ json_encode($subCategory->description ?? '') }}, {{ $subCategory->status }})">
                                                <iconify-icon icon="solar:pen-bold"></iconify-icon> Edit
                                            </button>
                                            <form action="{{ route('admin.issue-sub-categories.destroy', $subCategory->pk) }}" 
                                                  method="POST" class="d-inline m-0" 
                                                  onsubmit="return confirm('Are you sure you want to delete this sub-category?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <iconify-icon icon="solar:trash-bin-trash-bold"></iconify-icon> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No sub-categories found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $subCategories->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Sub-Category Modal -->
<div class="modal fade" id="addSubCategoryModal" tabindex="-1" aria-labelledby="addSubCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('admin.issue-sub-categories.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="background:#004a93;">
                    <h5 class="modal-title text-white" id="addSubCategoryModalLabel">Add New Sub-Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="issue_category_fk" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select @error('issue_category_master_pk') is-invalid @enderror" 
                                id="issue_category_fk" name="issue_category_master_pk" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                            @endforeach
                        </select>
                        @error('issue_category_master_pk')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="issue_sub_category" class="form-label">Sub-Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('issue_sub_category') is-invalid @enderror" 
                               id="issue_sub_category" name="issue_sub_category" required>
                        @error('issue_sub_category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3"></textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="submit" class="btn bg-success-subtle text-success waves-effect text-start">
                        Submit
                    </button>
                    <button type="button" class="btn bg-danger-subtle text-danger waves-effect text-start" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Sub-Category Modal -->
<div class="modal fade" id="editSubCategoryModal" tabindex="-1" aria-labelledby="editSubCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="editSubCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header" style="background:#004a93;">
                    <h5 class="modal-title text-white" id="editSubCategoryModalLabel">Edit Sub-Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_issue_category_fk" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_issue_category_fk" name="issue_category_master_pk" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_issue_sub_category" class="form-label">Sub-Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_issue_sub_category" name="issue_sub_category" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="submit" class="btn bg-success-subtle text-success waves-effect text-start">
                        Update
                    </button>
                    <button type="button" class="btn bg-danger-subtle text-danger waves-effect text-start" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function editSubCategory(id, categoryId, name, description, status) {
    document.getElementById('edit_issue_category_fk').value = categoryId != null ? String(categoryId) : '';
    document.getElementById('edit_issue_sub_category').value = name;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('edit_status').value = status;
    
    const form = document.getElementById('editSubCategoryForm');
    form.action = "{{ url('admin/issue-sub-categories') }}/" + id;
    
    const modal = new bootstrap.Modal(document.getElementById('editSubCategoryModal'));
    modal.show();
}
</script>
@endsection
