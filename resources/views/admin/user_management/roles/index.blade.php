@extends('admin.layouts.master')

@section('title', 'Roles - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid roles-index py-2">
    <x-breadcrum title="Roles"></x-breadcrum>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="material-symbols-rounded">check_circle</i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="datatables">
        <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3 overflow-hidden">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex flex-column flex-md-row flex-wrap justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="mb-0 fw-semibold text-primary">Roles</h4>
                        <span class="badge bg-primary bg-opacity-10 text-primary fs-6 fw-normal">User roles & permissions</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button"
                                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 shadow-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#createRoleModal"
                                aria-label="Add new role">
                            <i class="material-symbols-rounded fs-5" aria-hidden="true">add</i>
                            Add Role
                        </button>
                    </div>
                </div>

                <div class="table-responsive rounded-2">
                    {{ $dataTable->table(['class' => 'table align-middle mb-0']) }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create Role Modal --}}
<div class="modal fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold text-primary" id="createRoleModalLabel">
                    <i class="material-symbols-rounded me-2 align-middle">add_circle</i>Add Role
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <form action="{{ route('admin.roles.store') }}" method="POST" id="createRoleForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="create_name">Role name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="create_name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="e.g. admin, editor"
                               autocomplete="off">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-medium" for="create_display_name">Display name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('display_name') is-invalid @enderror"
                               id="create_display_name"
                               name="display_name"
                               value="{{ old('display_name') }}"
                               placeholder="e.g. Administrator"
                               autocomplete="off">
                        @error('display_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0 gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="createRoleForm" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i class="material-symbols-rounded fs-6">check</i>Submit
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Role Modal --}}
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold text-primary" id="editRoleModalLabel">
                    <i class="material-symbols-rounded me-2 align-middle">edit</i>Edit Role
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <form id="editRoleForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="edit_name">Role name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control"
                               id="edit_name"
                               name="name"
                               placeholder="e.g. admin, editor"
                               required
                               autocomplete="off">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-medium" for="edit_display_name">Display name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control"
                               id="edit_display_name"
                               name="display_name"
                               placeholder="e.g. Administrator"
                               required
                               autocomplete="off">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0 gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editRoleForm" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i class="material-symbols-rounded fs-6">save</i>Update
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{ $dataTable->scripts() }}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
document.addEventListener("DOMContentLoaded", function () {

    // Delete role confirmation
    $(document).on("submit", "form.delete-role-form", function(e) {
        e.preventDefault();
        let form = this;
        let row = $(this).closest("tr");
        let toggle = row.find(".status-toggle");
        let isActive = toggle.is(":checked");

        if (isActive) {
            Swal.fire({
                icon: 'error',
                title: 'Cannot Delete!',
                text: 'Active role cannot be deleted. Please deactivate the role first.',
                confirmButtonColor: '#d33',
            });
            return false;
        }

        Swal.fire({
            title: "Are you sure?",
            text: "This role will be permanently deleted!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    });

    // Edit role: open modal and fill form
    $(document).on("click", ".edit-role-btn", function() {
        var btn = $(this);
        var editUrl = btn.data("edit-url");
        var name = btn.data("name");
        var displayName = btn.data("display-name");

        var form = document.getElementById("editRoleForm");
        var modalEl = document.getElementById("editRoleModal");
        if (form && modalEl) {
            form.action = editUrl;
            document.getElementById("edit_name").value = name || "";
            document.getElementById("edit_display_name").value = displayName || "";
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    });

    // Show create modal when page has validation errors (redirect back from store)
    @if($errors->any())
    (function() {
        var createModalEl = document.getElementById("createRoleModal");
        if (createModalEl) {
            var modal = new bootstrap.Modal(createModalEl);
            modal.show();
        }
    })();
    @endif
});
</script>

@endpush