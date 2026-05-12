@extends('admin.layouts.master')

@section('title', 'Exemption Medical Speciality')

@section('setup_content')
<style>
/* ─── Hide DataTable default controls ─── */
#exemptionMedSpecTable_wrapper .dataTables_length,
#exemptionMedSpecTable_wrapper .dataTables_filter,
#exemptionMedSpecTable_wrapper .dataTables_info,
#exemptionMedSpecTable_wrapper .dataTables_paginate { display: none !important; }

/* ─── Custom Search ─── */
.ems-search-wrap {
    position: relative;
    width: 260px;
}
.ems-search-wrap .form-control {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding-left: 38px;
    font-size: 0.875rem;
}
.ems-search-wrap .ems-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 18px;
    pointer-events: none;
}

/* ─── Status Badges ─── */
.badge-active {
    display: inline-block;
    background-color: #d1fae5;
    color: #065f46;
    border-radius: 10px !important;
    padding: 4px 14px;
    font-size: 0.8rem;
    font-weight: 600;
}
.badge-inactive {
    display: inline-block;
    background-color: #fee2e2;
    color: #991b1b;
    border-radius: 10px !important;
    padding: 4px 14px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* ─── Table ─── */
#exemptionMedSpecTable thead th {
    background-color: #f8f9fa;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6c757d;
    border-bottom: 2px solid #dee2e6;
    padding: 12px 16px;
    white-space: nowrap;
}
#exemptionMedSpecTable tbody td {
    font-size: 0.875rem;
    padding: 12px 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    color: #212529;
}
#exemptionMedSpecTable tbody tr:hover td { background-color: #fafbfc; }

/* ─── Action Buttons ─── */
.ems-action-btn {
    background: none;
    border: none;
    padding: 3px 5px;
    cursor: pointer;
    line-height: 1;
    display: inline-flex;
    align-items: center;
}
.ems-action-btn .material-symbols-rounded { font-size: 20px; }
.ems-action-btn:hover { opacity: 0.7; }

/* ─── Page Info ─── */
#emsPageInfo { font-size: 0.8125rem; color: #6c757d; }

/* ─── Modal ─── */
.ems-modal .modal-content {
    border: 1.5px dashed #6ea8fe;
    border-radius: 12px;
}
.ems-modal .modal-header { border-bottom: none; padding: 20px 24px 6px; }
.ems-modal .modal-body   { padding: 8px 24px 16px; }
.ems-modal .modal-footer { border-top: none; padding: 0 24px 20px; }
.ems-modal .modal-title  { font-size: 1rem; font-weight: 700; color: #1b3a5c; }
.ems-modal .form-label   { font-size: 0.875rem; font-weight: 500; margin-bottom: 4px; color: #495057; }
.ems-modal .form-control,
.ems-modal .form-select  { border-radius: 6px; font-size: 0.875rem; border: 1px solid #dee2e6; padding: 8px 12px; }
.ems-modal .form-control:focus,
.ems-modal .form-select:focus { border-color: #6ea8fe; box-shadow: 0 0 0 3px rgba(110,168,254,0.15); }
</style>

<div class="container-fluid">
    <x-breadcrum title="Exemption Medical Speciality" subtitle="List of exemption medical specialities">
        <button type="button" data-bs-toggle="modal" data-bs-target="#addEmsModal"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Exemption Medical Speciality</span>
        </button>
    </x-breadcrum>

    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4">

                {{-- Custom Search --}}
                <div class="d-flex justify-content-end mb-3">
                    <div class="ems-search-wrap">
                        <i class="material-icons material-symbols-rounded ems-search-icon">search</i>
                        <input type="text" id="emsSearch" class="form-control" placeholder="Search">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table mb-0" id="exemptionMedSpecTable">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Speciality Name</th>
                                <th>Created Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div id="emsPageInfo" class="text-muted small"></div>
                </div>

            </div>
        </div>
    </div>
</div>

<input type="hidden" id="pk" value="">
<input type="hidden" id="active_inactive" value="">

{{-- ═══════════════════ ADD MODAL ═══════════════════ --}}
<div class="modal fade ems-modal" id="addEmsModal" tabindex="-1"
    aria-labelledby="addEmsModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEmsModalLabel">Add Exemption Medical Speciality</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addEmsForm" novalidate>
                    <div class="mb-3">
                        <label for="add_speciality_name" class="form-label">Speciality Name <span class="text-danger">*</span></label>
                        <input type="text" id="add_speciality_name" class="form-control" placeholder="eg. General Medicine">
                        <div class="invalid-feedback">Speciality Name is required.</div>
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
                <button type="button" class="btn fw-semibold px-4" id="addEmsSubmit"
                    style="background:#1b3a5c;color:#fff;border-color:#1b3a5c;">Add</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ EDIT MODAL ═══════════════════ --}}
<div class="modal fade ems-modal" id="editEmsModal" tabindex="-1"
    aria-labelledby="editEmsModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEmsModalLabel">Edit Exemption Medical Speciality</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editEmsForm" novalidate>
                    <input type="hidden" id="edit_ems_id">
                    <div class="mb-3">
                        <label for="edit_speciality_name" class="form-label">Speciality Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit_speciality_name" class="form-control" placeholder="eg. General Medicine">
                        <div class="invalid-feedback">Speciality Name is required.</div>
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
                <button type="button" class="btn fw-semibold px-4" id="editEmsSubmit"
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
    let table = $('#exemptionMedSpecTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        paging: false,
        info: false,
        dom: 'rt',
        ajax: {
            url: "{{ route('master.exemption.medical.speciality.exemption_med_spec_mst') }}",
            data: function (d) {
                d.pk = $('#pk').val();
                d.active_inactive = $('#active_inactive').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex',    name: 'DT_RowIndex',    orderable: false, searchable: false },
            { data: 'speciality_name', name: 'speciality_name' },
            { data: 'created_date',   name: 'created_date' },
            { data: 'status',         name: 'status',  orderable: false, searchable: false },
            { data: 'action',         name: 'action',  orderable: false, searchable: false }
        ],
        drawCallback: function () {
            var info  = this.api().page.info();
            var start = info.start + 1;
            var end   = info.end;
            var total = info.recordsTotal;
            $('#emsPageInfo').text(
                info.recordsDisplay > 0
                    ? 'Showing ' + start + '\u2013' + end + ' of ' + total + ' items'
                    : 'Showing 0 of ' + total + ' items'
            );
        }
    });

    // ── Custom Search ──
    $('#emsSearch').on('input', function () {
        clearTimeout(window._emsSearchTimer);
        var q = $(this).val();
        window._emsSearchTimer = setTimeout(function () { table.search(q).draw(); }, 350);
    });

    // ── Status Toggle (unchanged) ──
    $(document).on('change', '.plain-status-toggle', function () {
        var checkbox        = $(this);
        var pk              = checkbox.data('id');
        var active_inactive = checkbox.is(':checked') ? 1 : 0;

        Swal.fire({
            title: 'Are you sure?',
            text: "Are you sure? You want to deactivate this item?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, deactivate',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#pk').val(pk);
                $('#active_inactive').val(active_inactive);
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Updated!', text: 'Status has been updated successfully.', timer: 1500, showConfirmButton: false });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                checkbox.prop('checked', !active_inactive);
                Swal.fire({ icon: 'info', title: 'Cancelled', text: 'Status change has been cancelled.', timer: 1500, showConfirmButton: false });
            }
        });
    });

    // ── Delete (unchanged) ──
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

    // ── Edit btn → open Bootstrap modal ──
    $(document).on('click', '.edit-btn', function () {
        var id              = $(this).data('id');
        var speciality_name = $(this).data('speciality_name');
        var status          = $(this).data('active_inactive');

        $('#edit_ems_id').val(id);
        $('#edit_speciality_name').val(speciality_name).removeClass('is-invalid');
        $('#edit_status').val(status).removeClass('is-invalid');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editEmsModal')).show();
    });

    // ── ADD: submit (unchanged fetch logic) ──
    $('#addEmsSubmit').on('click', function () {
        var $name   = $('#add_speciality_name');
        var $status = $('#add_status');
        var valid   = true;

        $name.removeClass('is-invalid');
        $status.removeClass('is-invalid');
        if (!$name.val().trim()) { $name.addClass('is-invalid');   valid = false; }
        if (!$status.val())      { $status.addClass('is-invalid'); valid = false; }
        if (!valid) return;

        var $btn = $(this).prop('disabled', true).text('Saving...');

        fetch("{{ route('master.exemption.medical.speciality.store') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                speciality_name: $name.val(),
                status: $status.val()
            })
        })
        .then(response => {
            if (!response.ok) return response.json().then(err => { throw new Error(err.message || 'Validation failed'); });
            return response.json();
        })
        .then(data => {
            $btn.prop('disabled', false).text('Add');
            bootstrap.Modal.getInstance(document.getElementById('addEmsModal')).hide();
            Swal.fire({ icon: 'success', title: 'Saved!', text: 'Exemption medical speciality added successfully', timer: 1500, showConfirmButton: false });
            table.ajax.reload(null, false);
        })
        .catch(error => {
            $btn.prop('disabled', false).text('Add');
            Swal.fire('Error', error.message, 'error');
        });
    });

    document.getElementById('addEmsModal').addEventListener('hidden.bs.modal', function () {
        $('#addEmsForm')[0].reset();
        $('#addEmsForm .form-control, #addEmsForm .form-select').removeClass('is-invalid');
    });

    // ── EDIT: submit (unchanged $.ajax logic) ──
    $('#editEmsSubmit').on('click', function () {
        var $name   = $('#edit_speciality_name');
        var $status = $('#edit_status');
        var valid   = true;

        $name.removeClass('is-invalid');
        $status.removeClass('is-invalid');
        if (!$name.val().trim()) { $name.addClass('is-invalid');   valid = false; }
        if (!$status.val())      { $status.addClass('is-invalid'); valid = false; }
        if (!valid) return;

        var $btn = $(this).prop('disabled', true).text('Saving...');
        var id   = $('#edit_ems_id').val();

        $.ajax({
            url: "{{ route('master.exemption.medical.speciality.store') }}",
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                speciality_name: $name.val(),
                status: $status.val()
            }
        })
        .done(function (result) {
            $btn.prop('disabled', false).text('Update');
            bootstrap.Modal.getInstance(document.getElementById('editEmsModal')).hide();
            Swal.fire({ icon: 'success', title: 'Updated!', text: result.message, timer: 1500, showConfirmButton: false });
            table.ajax.reload(null, false);
        })
        .fail(function (xhr) {
            $btn.prop('disabled', false).text('Update');
            if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                if (errors && errors.medical_speciality_name) {
                    $name.addClass('is-invalid');
                    $name.siblings('.invalid-feedback').text(errors.medical_speciality_name[0]);
                }
            } else {
                Swal.fire('Error', 'Something went wrong!', 'error');
            }
        });
    });

    document.getElementById('editEmsModal').addEventListener('hidden.bs.modal', function () {
        $('#editEmsForm')[0].reset();
        $('#editEmsForm .form-control, #editEmsForm .form-select').removeClass('is-invalid');
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