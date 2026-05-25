@extends('admin.layouts.master')

@section('title', 'Exemption categories')

@section('setup_content')
<style>
/* â”€â”€â”€ Hide DataTable default controls â”€â”€â”€ */
#exceptiongetcategory_wrapper .dataTables_length,
#exceptiongetcategory_wrapper .dataTables_filter,
#exceptiongetcategory_wrapper .dataTables_info,
#exceptiongetcategory_wrapper .dataTables_paginate { display: none !important; }

/* â”€â”€â”€ Custom Search â”€â”€â”€ */
.ec-search-wrap {
    position: relative;
    width: 260px;
}
.ec-search-wrap .form-control {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding-left: 38px;
    font-size: 0.875rem;
}
.ec-search-wrap .ec-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 18px;
    pointer-events: none;
}

/* â”€â”€â”€ Status Badges â”€â”€â”€ */
.badge-active {
    display: inline-block;
    background-color: #d1fae5;
    color: #065f46;
    border-radius: 50rem;
    padding: 4px 14px;
    font-size: 0.8rem;
    font-weight: 600;
}
.badge-inactive {
    display: inline-block;
    background-color: #fee2e2;
    color: #991b1b;
    border-radius: 50rem;
    padding: 4px 14px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* â”€â”€â”€ Table â”€â”€â”€ */
#exceptiongetcategory thead th {
    background-color: #f8f9fa;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6c757d;
    border-bottom: 2px solid #dee2e6;
    padding: 12px 16px;
    white-space: nowrap;
}
#exceptiongetcategory tbody td {
    font-size: 0.875rem;
    padding: 12px 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    color: #212529;
}
#exceptiongetcategory tbody tr:hover td { background-color: #fafbfc; }

/* â”€â”€â”€ Action Buttons â”€â”€â”€ */
.ec-action-btn {
    background: none;
    border: none;
    padding: 3px 5px;
    cursor: pointer;
    line-height: 1;
    display: inline-flex;
    align-items: center;
}
.ec-action-btn .material-symbols-rounded { font-size: 20px; }
.ec-action-btn:hover { opacity: 0.7; }

/* â”€â”€â”€ Page Info â”€â”€â”€ */
#ecPageInfo { font-size: 0.8125rem; color: #6c757d; }

/* â”€â”€â”€ Modal â”€â”€â”€ */
.ec-modal .modal-content {
    border: 1.5px dashed #6ea8fe;
    border-radius: 12px;
}
.ec-modal .modal-header { border-bottom: none; padding: 20px 24px 6px; }
.ec-modal .modal-body   { padding: 8px 24px 16px; }
.ec-modal .modal-footer { border-top: none; padding: 0 24px 20px; }
.ec-modal .modal-title  { font-size: 1rem; font-weight: 700; color: #1b3a5c; }
.ec-modal .form-label   { font-size: 0.875rem; font-weight: 500; margin-bottom: 4px; color: #495057; }
.ec-modal .form-control,
.ec-modal .form-select  { border-radius: 6px; font-size: 0.875rem; border: 1px solid #dee2e6; padding: 8px 12px; }
.ec-modal .form-control:focus,
.ec-modal .form-select:focus { border-color: #6ea8fe; box-shadow: 0 0 0 3px rgba(110,168,254,0.15); }
</style>

<div class="container-fluid">
    <x-breadcrum title="Exemption Categories" subtitle="List of exemption categories">
        <button type="button" data-bs-toggle="modal" data-bs-target="#addExemptionModal"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Exemption Category</span>
        </button>
    </x-breadcrum>

    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4">

                {{-- Custom Search --}}
                <div class="d-flex justify-content-end mb-3">
                    <div class="ec-search-wrap">
                        <i class="material-icons material-symbols-rounded ec-search-icon">search</i>
                        <input type="text" id="ecSearch" class="form-control" placeholder="Search">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table mb-0" id="exceptiongetcategory">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Name</th>
                                <th>Short Name</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div id="ecPageInfo" class="text-muted small"></div>
                </div>

            </div>
        </div>
    </div>
</div>

<input type="hidden" id="pk" value="">
<input type="hidden" id="active_inactive" value="">

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• ADD MODAL â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div class="modal fade ec-modal" id="addExemptionModal" tabindex="-1"
    aria-labelledby="addExemptionModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addExemptionModalLabel">Add Exemption Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addExemptionForm" novalidate>
                    <div class="mb-3">
                        <label for="add_short_name" class="form-label">Short Name <span class="text-danger">*</span></label>
                        <input type="text" id="add_short_name" class="form-control" placeholder="eg. EC082">
                        <div class="invalid-feedback">Short Name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" id="add_category_name" class="form-control" placeholder="eg. Category Pre">
                        <div class="invalid-feedback">Category Name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="add_status" class="form-select">
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
                <button type="button" class="btn fw-semibold px-4" id="addExemptionSubmit"
                    style="background:#1b3a5c;color:#fff;border-color:#1b3a5c;">Add</button>
            </div>
        </div>
    </div>
</div>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• EDIT MODAL â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div class="modal fade ec-modal" id="editExemptionModal" tabindex="-1"
    aria-labelledby="editExemptionModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editExemptionModalLabel">Edit Exemption Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editExemptionForm" novalidate>
                    <input type="hidden" id="edit_record_pk">
                    <div class="mb-3">
                        <label for="edit_short_name" class="form-label">Short Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit_short_name" class="form-control" placeholder="eg. EC082">
                        <div class="invalid-feedback">Short Name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit_category_name" class="form-control" placeholder="eg. Category Pre">
                        <div class="invalid-feedback">Category Name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="edit_status" class="form-select">
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
                <button type="button" class="btn fw-semibold px-4" id="editExemptionSubmit"
                    style="background:#1b3a5c;color:#fff;border-color:#1b3a5c;">Update</button>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
$(function () {

    // ── DataTable ──
    let table = $('#exceptiongetcategory').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        paging: false,
        info: false,
        dom: 'rt',
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('master.exemption.category.master.getcategory') }}",
            data: function (d) {
                d.pk = $('#pk').val();
                d.active_inactive = $('#active_inactive').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex',         name: 'DT_RowIndex',         orderable: false, searchable: false },
            { data: 'exemp_category_name', name: 'exemp_category_name' },
            { data: 'ShortName',           name: 'ShortName' },
            { data: 'status',              name: 'status',  orderable: false, searchable: false },
            { data: 'action',              name: 'action',  orderable: false, searchable: false }
        ],
        drawCallback: function () {
            var info  = this.api().page.info();
            var start = info.start + 1;
            var end   = info.end;
            var total = info.recordsTotal;
            $('#ecPageInfo').text(
                info.recordsDisplay > 0
                    ? 'Showing ' + start + '\u2013' + end + ' of ' + total + ' items'
                    : 'Showing 0 of ' + total + ' items'
            );
        }
    });

    // ── Custom Search ──
    $('#ecSearch').on('input', function () {
        clearTimeout(window._ecSearchTimer);
        var q = $(this).val();
        window._ecSearchTimer = setTimeout(function () { table.search(q).draw(); }, 350);
    });

    // ── Status Toggle (unchanged) ──
    $(document).on('change', '.plain-status-toggle', function () {
        var checkbox        = $(this);
        var pk              = checkbox.data('id');
        var active_inactive = checkbox.is(':checked') ? 1 : 0;
        var actionText      = active_inactive ? 'activate' : 'deactivate';
        var confirmBtnText  = active_inactive ? 'Yes, activate' : 'Yes, deactivate';
        var confirmBtnColor = active_inactive ? '#28a745' : '#d33';

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
                $('#pk').val(pk);
                $('#active_inactive').val(active_inactive);
                table.ajax.reload(function () {
                    $('#pk').val('');
                    $('#active_inactive').val('');
                    Swal.fire({ icon: 'success', title: 'Updated!', text: 'Status has been updated successfully.', timer: 1500, showConfirmButton: false });
                }, false);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                checkbox.prop('checked', !active_inactive);
                Swal.fire({ icon: 'info', title: 'Cancelled', text: 'Status change has been cancelled.', timer: 1500, showConfirmButton: false });
            }
        });
    });

    // ── delete-btn (unchanged) ──
    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        let pk = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This record will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#pk').val(pk);
                $('#active_inactive').val(2);
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Delete!', text: 'Delete has been successfully.', timer: 1500, showConfirmButton: false });
            }
        });
    });

    // ── deleteBtn AJAX (unchanged) ──
    $(document).on('click', '.deleteBtn', function (e) {
        e.preventDefault();
        const btn = $(this);
        const url = btn.data('url');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                    beforeSend: function () { btn.prop('disabled', true); },
                    success: function (res) {
                        if (res.status) {
                            Swal.fire('Deleted!', res.message, 'success');
                            $('#exceptiongetcategory').DataTable().ajax.reload(null, false);
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                            btn.prop('disabled', false);
                        }
                    },
                    error: function () {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                        btn.prop('disabled', false);
                    }
                });
            }
        });
    });

    // ── edit-btn → open Bootstrap modal ──
    $(document).on('click', '.edit-btn', function () {
        var pk                   = $(this).data('id');
        var exemp_category_name  = $(this).data('exemp_category_name');
        var exemp_cat_short_name = $(this).data('exemp_cat_short_name');
        var status               = $(this).data('active_inactive');

        $('#edit_record_pk').val(pk);
        $('#edit_category_name').val(exemp_category_name);
        $('#edit_short_name').val(exemp_cat_short_name);
        $('#edit_status').val(status);

        $('#editExemptionForm .form-control, #editExemptionForm .form-select').removeClass('is-invalid');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editExemptionModal')).show();
    });

    // ── ADD: submit ──
    $('#addExemptionSubmit').on('click', function () {
        var $shortName    = $('#add_short_name');
        var $categoryName = $('#add_category_name');
        var $status       = $('#add_status');
        var valid         = true;

        [$shortName, $categoryName, $status].forEach(function (el) { el.removeClass('is-invalid'); });
        if (!$shortName.val().trim())    { $shortName.addClass('is-invalid');    valid = false; }
        if (!$categoryName.val().trim()) { $categoryName.addClass('is-invalid'); valid = false; }
        if (!$status.val())              { $status.addClass('is-invalid');       valid = false; }
        if (!valid) return;

        var $btn = $(this).prop('disabled', true).text('Saving...');
        var formData = new FormData();
        formData.append('exemp_category_name', $categoryName.val());
        formData.append('exemp_cat_short_name', $shortName.val());
        formData.append('status', $status.val());

        fetch("{{ route('master.exemption.category.master.store') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}", 'Accept': 'application/json' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            $btn.prop('disabled', false).text('Add');
            if (data.status) {
                bootstrap.Modal.getInstance(document.getElementById('addExemptionModal')).hide();
                Swal.fire('Success', data.message, 'success');
                table.ajax.reload(null, false);
            } else {
                Swal.fire('Error', data.message || 'Something went wrong.', 'error');
            }
        })
        .catch(() => {
            $btn.prop('disabled', false).text('Add');
            Swal.fire('Error', 'Server error or session expired.', 'error');
        });
    });

    document.getElementById('addExemptionModal').addEventListener('hidden.bs.modal', function () {
        $('#addExemptionForm')[0].reset();
        $('#addExemptionForm .form-control, #addExemptionForm .form-select').removeClass('is-invalid');
    });

    // ── EDIT: submit ──
    $('#editExemptionSubmit').on('click', function () {
        var $shortName    = $('#edit_short_name');
        var $categoryName = $('#edit_category_name');
        var $status       = $('#edit_status');
        var valid         = true;

        [$shortName, $categoryName, $status].forEach(function (el) { el.removeClass('is-invalid'); });
        if (!$shortName.val().trim())    { $shortName.addClass('is-invalid');    valid = false; }
        if (!$categoryName.val().trim()) { $categoryName.addClass('is-invalid'); valid = false; }
        if (!$status.val())              { $status.addClass('is-invalid');       valid = false; }
        if (!valid) return;

        var $btn = $(this).prop('disabled', true).text('Saving...');
        var formData = new FormData();
        formData.append('_token', "{{ csrf_token() }}");
        formData.append('pk', $('#edit_record_pk').val());
        formData.append('exemp_category_name', $categoryName.val());
        formData.append('exemp_cat_short_name', $shortName.val());
        formData.append('status', $status.val());

        fetch("{{ route('master.exemption.category.master.store') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}", 'Accept': 'application/json' },
            body: formData
        })
        .then(res => res.text())
        .then(text => {
            $btn.prop('disabled', false).text('Update');
            try {
                var data = JSON.parse(text);
                if (data.status) {
                    bootstrap.Modal.getInstance(document.getElementById('editExemptionModal')).hide();
                    Swal.fire('Updated!', data.message, 'success');
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', data.message || 'Something went wrong.', 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Unexpected server response.', 'error');
            }
        })
        .catch(() => {
            $btn.prop('disabled', false).text('Update');
            Swal.fire('Error', 'Server error or session expired.', 'error');
        });
    });

    document.getElementById('editExemptionModal').addEventListener('hidden.bs.modal', function () {
        $('#editExemptionForm')[0].reset();
        $('#editExemptionForm .form-control, #editExemptionForm .form-select').removeClass('is-invalid');
    });

}); //endclose
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