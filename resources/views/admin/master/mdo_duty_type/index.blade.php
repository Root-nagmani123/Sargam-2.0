@extends('admin.layouts.master')

@section('title', 'MDO Duty Type')

@section('setup_content')
<style>
/* ─── Hide DataTable default controls ─── */
#mdodutytypemaster-table_wrapper .dataTables_length,
#mdodutytypemaster-table_wrapper .dataTables_filter,
#mdodutytypemaster-table_wrapper .dataTables_info,
#mdodutytypemaster-table_wrapper .dataTables_paginate { display: none !important; }

/* ─── Custom Search ─── */
.mdt-search-wrap {
    position: relative;
    width: 260px;
}
.mdt-search-wrap .form-control {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding-left: 38px;
    font-size: 0.875rem;
}
.mdt-search-wrap .mdt-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 18px;
    pointer-events: none;
}

/* ─── Status Badges ─── */
.mdt-badge-active {
    display: inline-block;
    background-color: #d1fae5;
    color: #065f46;
    border-radius: 10px !important;
    padding: 4px 14px;
    font-size: 0.8rem;
    font-weight: 600;
}
.mdt-badge-inactive {
    display: inline-block;
    background-color: #fee2e2;
    color: #991b1b;
    border-radius: 10px !important;
    padding: 4px 14px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* ─── Table ─── */
#mdodutytypemaster-table thead th {
    background-color: #f8f9fa;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6c757d;
    border-bottom: 2px solid #dee2e6;
    padding: 12px 16px;
    white-space: nowrap;
}
#mdodutytypemaster-table tbody td {
    font-size: 0.875rem;
    padding: 12px 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    color: #212529;
}
#mdodutytypemaster-table tbody tr:hover td { background-color: #fafbfc; }

/* ─── Action Buttons ─── */
.mdt-action-btn {
    background: none;
    border: none;
    padding: 3px 5px;
    cursor: pointer;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    text-decoration: none;
}
.mdt-action-btn .material-symbols-rounded { font-size: 20px; }
.mdt-action-btn:hover { opacity: 0.7; }

/* ─── Page Info ─── */
#mdtPageInfo { font-size: 0.8125rem; color: #6c757d; }

/* ─── Modal ─── */
.mdt-modal .modal-content {
    border: 1.5px dashed #6ea8fe;
    border-radius: 12px;
}
.mdt-modal .modal-header { border-bottom: none; padding: 20px 24px 6px; }
.mdt-modal .modal-body   { padding: 8px 24px 16px; }
.mdt-modal .modal-footer { border-top: none; padding: 0 24px 20px; }
.mdt-modal .modal-title  { font-size: 1rem; font-weight: 700; color: #1b3a5c; }
.mdt-modal .form-label   { font-size: 0.875rem; font-weight: 500; margin-bottom: 4px; color: #495057; }
.mdt-modal .form-control,
.mdt-modal .form-select  { border-radius: 6px; font-size: 0.875rem; border: 1px solid #dee2e6; padding: 8px 12px; }
.mdt-modal .form-control:focus,
.mdt-modal .form-select:focus { border-color: #6ea8fe; box-shadow: 0 0 0 3px rgba(110,168,254,0.15); }
</style>

<div class="container-fluid">
    <x-breadcrum title="MDO Duty Type" subtitle="List of MDO duty types">
        <button type="button" data-bs-toggle="modal" data-bs-target="#addMdtModal"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add MDO Duty Type</span>
        </button>
    </x-breadcrum>

    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4">

                {{-- Custom Search --}}
                <div class="d-flex justify-content-end mb-3">
                    <div class="mdt-search-wrap">
                        <i class="material-icons material-symbols-rounded mdt-search-icon">search</i>
                        <input type="text" id="mdtSearch" class="form-control" placeholder="Search">
                    </div>
                </div>

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table mb-0']) !!}
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div id="mdtPageInfo" class="text-muted small"></div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ ADD MODAL ═══════════════════ --}}
<div class="modal fade mdt-modal" id="addMdtModal" tabindex="-1"
    aria-labelledby="addMdtModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMdtModalLabel">Add MDO Duty Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addMdtForm" novalidate>
                    <div class="mb-3">
                        <label for="add_mdo_duty_type_name" class="form-label">Duty Type Name <span class="text-danger">*</span></label>
                        <input type="text" id="add_mdo_duty_type_name" class="form-control" placeholder="eg. General Medicine">
                        <div class="invalid-feedback">Duty Type Name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_mdt_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="add_mdt_status" class="form-select">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback">Status is required.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn fw-semibold px-4" id="addMdtSubmit"
                    style="background:#1b3a5c;color:#fff;border-color:#1b3a5c;">Add</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ EDIT MODAL ═══════════════════ --}}
<div class="modal fade mdt-modal" id="editMdtModal" tabindex="-1"
    aria-labelledby="editMdtModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMdtModalLabel">Edit MDO Duty Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editMdtForm" novalidate>
                    <input type="hidden" id="edit_mdt_id">
                    <div class="mb-3">
                        <label for="edit_mdo_duty_type_name" class="form-label">Duty Type Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit_mdo_duty_type_name" class="form-control" placeholder="eg. General Medicine">
                        <div class="invalid-feedback">Duty Type Name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_mdt_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="edit_mdt_status" class="form-select">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback">Status is required.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn fw-semibold px-4" id="editMdtSubmit"
                    style="background:#1b3a5c;color:#fff;border-color:#1b3a5c;">Update</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
$(function () {

    var table = $('#mdodutytypemaster-table').DataTable();

    // ── drawCallback for page info ──
    $('#mdodutytypemaster-table').on('draw.dt', function () {
        var api   = table.page.info();
        var start = api.start + 1;
        var end   = api.end;
        var total = api.recordsTotal;
        $('#mdtPageInfo').text(
            api.recordsDisplay > 0
                ? 'Showing ' + start + '\u2013' + end + ' of ' + total + ' items'
                : 'Showing 0 of ' + total + ' items'
        );
    });

    // ── Custom Search ──
    $('#mdtSearch').on('input', function () {
        clearTimeout(window._mdtSearchTimer);
        var q = $(this).val();
        window._mdtSearchTimer = setTimeout(function () { table.search(q).draw(); }, 350);
    });

    // ── Status Toggle (unchanged) ──
    $(document).on('change', '.plain-status-toggle', function () {
        let checkbox        = $(this);
        let pk              = checkbox.data('id');
        let active_inactive = checkbox.is(':checked') ? 1 : 0;
        let actionText      = active_inactive ? 'activate' : 'deactivate';
        let confirmBtnText  = active_inactive ? 'Yes, activate' : 'Yes, deactivate';
        let confirmBtnColor = active_inactive ? '#28a745' : '#d33';

        Swal.fire({
            title: 'Are you sure?',
            text: `Are you sure you want to ${actionText} this item?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: confirmBtnColor,
            cancelButtonColor: '#3085d6',
            confirmButtonText: confirmBtnText,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('master.mdo_duty_type.status') }}",
                    type: 'POST',
                    data: { pk: pk, active_inactive: active_inactive, _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        table.ajax.reload(null, false);
                        Swal.fire({ icon: 'success', title: 'Updated!', text: response.message, timer: 1500, showConfirmButton: false });
                    }
                });
            } else {
                checkbox.prop('checked', !active_inactive);
            }
        });
    });

    // ── Delete (unchanged) ──
    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        let pk = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This record will be permanently deleted!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("master.mdo_duty_type.delete") }}',
                    type: 'POST',
                    data: { id: pk, _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        table.ajax.reload(null, false);
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: response.message, timer: 1500, showConfirmButton: false });
                    }
                });
            }
        });
    });

    // ── Edit btn → open Bootstrap modal ──
    $(document).on('click', '.edit-btn', function () {
        var pk                 = $(this).data('id');
        var mdo_duty_type_name = $(this).data('mdo_duty_type_name');
        var active_inactive    = $(this).data('active_inactive');

        $('#edit_mdt_id').val(pk);
        $('#edit_mdo_duty_type_name').val(mdo_duty_type_name).removeClass('is-invalid');
        $('#edit_mdt_status').val(active_inactive).removeClass('is-invalid');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editMdtModal')).show();
    });

    // ── ADD: submit (unchanged $.ajax logic) ──
    $('#addMdtSubmit').on('click', function () {
        var $name   = $('#add_mdo_duty_type_name');
        var $status = $('#add_mdt_status');
        var valid   = true;

        $name.removeClass('is-invalid');
        $status.removeClass('is-invalid');
        if (!$name.val().trim()) { $name.addClass('is-invalid');   valid = false; }
        if (!$status.val())      { $status.addClass('is-invalid'); valid = false; }
        if (!valid) return;

        var $btn = $(this).prop('disabled', true).text('Saving...');

        $.ajax({
            url: '{{ route("master.mdo_duty_type.store") }}',
            type: 'POST',
            data: {
                mdo_duty_type_name: $name.val().trim(),
                active_inactive: $status.val(),
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                $btn.prop('disabled', false).text('Add');
                bootstrap.Modal.getInstance(document.getElementById('addMdtModal')).hide();
                Swal.fire({ icon: 'success', title: 'Saved!', text: response.message ?? 'Record added successfully', timer: 1500, showConfirmButton: false });
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).text('Add');
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message ?? 'Something went wrong' });
            }
        });
    });

    document.getElementById('addMdtModal').addEventListener('hidden.bs.modal', function () {
        $('#addMdtForm')[0].reset();
        $('#addMdtForm .form-control, #addMdtForm .form-select').removeClass('is-invalid');
    });

    // ── EDIT: submit (unchanged $.ajax logic) ──
    $('#editMdtSubmit').on('click', function () {
        var $name   = $('#edit_mdo_duty_type_name');
        var $status = $('#edit_mdt_status');
        var valid   = true;

        $name.removeClass('is-invalid');
        $status.removeClass('is-invalid');
        if (!$name.val().trim()) { $name.addClass('is-invalid');   valid = false; }
        if (!$status.val())      { $status.addClass('is-invalid'); valid = false; }
        if (!valid) return;

        var $btn = $(this).prop('disabled', true).text('Saving...');

        $.ajax({
            url: '{{ route("master.mdo_duty_type.store") }}',
            type: 'POST',
            data: {
                id: $('#edit_mdt_id').val(),
                mdo_duty_type_name: $name.val().trim(),
                active_inactive: $status.val(),
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                $btn.prop('disabled', false).text('Update');
                bootstrap.Modal.getInstance(document.getElementById('editMdtModal')).hide();
                Swal.fire({ icon: 'success', title: 'Updated!', text: response.message ?? 'Record updated successfully', timer: 1500, showConfirmButton: false });
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).text('Update');
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message ?? 'Something went wrong' });
            }
        });
    });

    document.getElementById('editMdtModal').addEventListener('hidden.bs.modal', function () {
        $('#editMdtForm')[0].reset();
        $('#editMdtForm .form-control, #editMdtForm .form-select').removeClass('is-invalid');
    });

});
</script>

@if(session('success'))
<script>
    Swal.fire({ icon: 'success', title: 'Success', text: "{{ session('success') }}" });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({ icon: 'error', title: 'Error', text: "{{ session('error') }}" });
</script>
@endif
@endpush