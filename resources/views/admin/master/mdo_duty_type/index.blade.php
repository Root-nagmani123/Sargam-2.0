@extends('admin.layouts.master')

@section('title', 'MDO Duty Type')

@section('content')
<style>
/* Header style - deep red + rounded corners */
table.mdodutytable thead th {
    background-color: #b32826 !important;
    color: #fff !important;
    font-weight: 600 !important;
    border: none !important;
    padding: 14px;
    text-align: center !important;
}

table.mdodutytable thead th:first-child {
    border-top-left-radius: 12px;
}

table.mdodutytable thead th:last-child {
    border-top-right-radius: 12px;
}

/* Body rows - clean white, no borders */
table.mdodutytable tbody tr {
    border: none !important;
}

table.mdodutytable tbody td {
    border: none !important;
    padding: 18px 12px !important;
    text-align: center !important;
    font-size: 15px;
    color: #000;
}

/* Remove DataTable default border */
table.dataTable.no-footer {
    border-bottom: none !important;
}

/* Remove row hover highlight (optional) */
table.mdodutytable tbody tr:hover {
    background: #f8f8f8 !important;
}
</style>
<div class="container-fluid">

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>MDO Duty Type</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <button class="btn btn-primary" onclick="openCreateModal()">
                                    + Add MDO Duty Type
                                </button>
                            </div>
                        </div>
                    </div>
                    <hr>
                    {{ $dataTable->table(['class' => 'table table align-middle w-100 mdodutytable table-striped table-bordered']) }}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>
<!-- Create / Edit Modal -->
<div class="modal fade" id="mdoDutyTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="mdoModalTitle">Create MDO Duty Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="mdoDutyTypeForm" method="POST">
                    @csrf

                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name :</label>
                        <input type="text" class="form-control" name="mdo_duty_type_name" id="mdo_name"
                            placeholder="Enter Duty Type Name" required>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary" id="modalSubmitBtn">
                            Save
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<script>
// Create Modal
function openCreateModal() {
    document.getElementById('mdoModalTitle').innerText = "Create MDO Duty Type";
    document.getElementById('modalSubmitBtn').innerText = "Save";

    // form action
    document.getElementById('mdoDutyTypeForm').action = "{{ route('master.mdo_duty_type.store') }}";

    // clear fields
    document.getElementById('edit_id').value = "";
    document.getElementById('mdo_name').value = "";

    // open modal
    new bootstrap.Modal(document.getElementById('mdoDutyTypeModal')).show();
}

// Edit Modal
function openEditModal(id, name) {
    document.getElementById('mdoModalTitle').innerText = "Edit MDO Duty Type";
    document.getElementById('modalSubmitBtn').innerText = "Update";

    // form action
    document.getElementById('mdoDutyTypeForm').action = "{{ route('master.mdo_duty_type.store') }}";

    // fill fields
    document.getElementById('edit_id').value = id;
    document.getElementById('mdo_name').value = name;

    // open modal
    new bootstrap.Modal(document.getElementById('mdoDutyTypeModal')).show();
}
</script>


@endsection
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

@push('scripts')
{{ $dataTable->scripts() }}
@endpush