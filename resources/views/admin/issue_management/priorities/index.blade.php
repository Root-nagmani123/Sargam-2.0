@extends('admin.layouts.master')

@section('title', 'Issue Priorities - Sargam | Lal Bahadur')

@section('css')
<style>
.modal-body { background-color: #fff !important; color: #212529 !important; }
.modal-content { background-color: #fff !important; }
</style>
@endsection

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Issue Priorities" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6">
                        <h4 class="mb-0">Issue Priorities</h4>
                    </div>
                    <div class="col-6 text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPriorityModal">
                            <iconify-icon icon="ep:circle-plus-filled"></iconify-icon> Add Priority
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
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive datatables">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="20%">Priority Name</th>
                                <th width="35%">Description</th>
                                <th width="10%">Status</th>
                                <th width="20%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($priorities as $priority)
                            <tr>
                                <td>{{ $priority->pk }}</td>
                                <td>{{ $priority->priority }}</td>
                                <td>{{ $priority->description ?? '-' }}</td>
                                <td>
                                    @if($priority->status == 1)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning"
                                            onclick="editPriority({{ $priority->pk }}, {{ json_encode($priority->priority) }}, {{ json_encode($priority->description ?? '') }}, {{ $priority->status }})">
                                        <iconify-icon icon="solar:pen-bold"></iconify-icon> Edit
                                    </button>
                                    <form action="{{ route('admin.issue-priorities.destroy', $priority->pk) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this priority?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <iconify-icon icon="solar:trash-bin-trash-bold"></iconify-icon> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No priorities found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $priorities->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Priority Modal -->
<div class="modal fade" id="addPriorityModal" tabindex="-1" aria-labelledby="addPriorityModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('admin.issue-priorities.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="background:#004a93;">
                    <h5 class="modal-title text-white" id="addPriorityModalLabel">Add New Priority</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('priority') is-invalid @enderror"
                               id="priority" name="priority" placeholder="e.g. High, Medium, Low" required>
                        @error('priority')
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
                    <button type="submit" class="btn bg-success-subtle text-success waves-effect text-start">Submit</button>
                    <button type="button" class="btn bg-danger-subtle text-danger waves-effect text-start" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Priority Modal -->
<div class="modal fade" id="editPriorityModal" tabindex="-1" aria-labelledby="editPriorityModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="editPriorityForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header" style="background:#004a93;">
                    <h5 class="modal-title text-white" id="editPriorityModalLabel">Edit Priority</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_priority" class="form-label">Priority Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_priority" name="priority" required>
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
                    <button type="submit" class="btn bg-success-subtle text-success waves-effect text-start">Update</button>
                    <button type="button" class="btn bg-danger-subtle text-danger waves-effect text-start" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function editPriority(id, name, description, status) {
    document.getElementById('edit_priority').value = name || '';
    document.getElementById('edit_description').value = description || '';
    document.getElementById('edit_status').value = status;
    
    const form = document.getElementById('editPriorityForm');
    form.action = "{{ url('admin/issue-priorities') }}/" + id;
    
    const modal = new bootstrap.Modal(document.getElementById('editPriorityModal'));
    modal.show();
}
</script>
@endsection
