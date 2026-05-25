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
                <div class="mb-3">
                    <div class="table-responsive">
                        <table class="table" id="exemptionMedicalSpecialityTable">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Speciality Name</th>
                                    <th class="col">Created Date</th>
                                    <th class="col">Status</th>
                                    <th class="col">Actions</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                        </table>
                    </div>
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
    $(function() {
        const tableSelector = '#exemptionMedicalSpecialityTable';
        let table;

        if ($.fn.DataTable.isDataTable(tableSelector)) {
            table = $(tableSelector).DataTable();
        } else {
            table = $(tableSelector).DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                ajax: {
                    url: "{{ route('master.exemption.medical.speciality.exemption_med_spec_mst') }}",
                    data: function(d) {
                        d.pk = $('#pk').val();
                        d.active_inactive = $('#active_inactive').val();
                        // console.log('jjj');
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'speciality_name',
                        name: 'speciality_name'
                    },
                    {
                        data: 'created_date',
                        name: 'created_date'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]

            });
        }

        $(document).on('change', '.plain-status-toggle', function() {
            var checkbox = $(this); // save reference
            var pk = checkbox.data('id');
           // alert(pk);
            var active_inactive = checkbox.is(':checked') ? 1 : 0;
          //  alert(active_inactive);
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
                    // Set hidden input values if needed
                    $('#pk').val(pk);
                    $('#active_inactive').val(active_inactive);
                    table.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: 'Status has been updated successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
                else if (result.dismiss === Swal.DismissReason.cancel) {
                    checkbox.prop('checked', !active_inactive);
                    Swal.fire({
                        icon: 'info',
                        title: 'Cancelled',
                        text: 'Status change has been cancelled.',
                        timer: 1500,
                        showConfirmButton: false
                    });
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

    }); //endclose
</script>
<button id="showAlert">Add</button>

<script>
document.getElementById('showAlert').addEventListener('click', function () {
    Swal.fire({
        title: '<strong>Add Exemption medical speciality</strong>',
        html: `
            <form id="exemptionCategoryForm">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <!-- Type Name -->
                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Type Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text" name="speciality_name" id="speciality_name" class="form-control">
                        <small class="text-danger d-none" id="speciality_name_error">
                            Type Name is required
                        </small>
                    </div>
                </div>

                <!-- Status -->
                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <select name="active_inactive" id="status" class="form-control">
                            <option value="">-- Select Status --</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <small class="text-danger d-none" id="status_error">
                            Status is required
                        </small>
                    </div>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Submit',
        showLoaderOnConfirm: true,
        focusConfirm: false,

        preConfirm: () => {
            const popup = Swal.getPopup();

            const typeName  = popup.querySelector('#speciality_name');
            const status    = popup.querySelector('#status');

            const typeErr   = popup.querySelector('#speciality_name_error');
            const statusErr = popup.querySelector('#status_error');

            // reset
            [typeName, status].forEach(el => el.classList.remove('is-invalid'));
            [typeErr, statusErr].forEach(el => el.classList.add('d-none'));

            let valid = true;

            if (!typeName.value.trim()) {
                typeName.classList.add('is-invalid');
                typeErr.classList.remove('d-none');
                valid = false;
            }

            if (!status.value) {
                status.classList.add('is-invalid');
                statusErr.classList.remove('d-none');
                valid = false;
            }

            if (!valid) return false;

            // AJAX request
            return fetch("{{ route('master.exemption.medical.speciality.store') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    speciality_name: typeName.value,
                    status: status.value
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Validation failed');
                    });
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(error.message);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Exemption category added successfully',
                timer: 1500,
                showConfirmButton: false
            });

            // reload datatable / page if needed
             $('#exemptionMedicalSpecialityTable').DataTable().ajax.reload();
        }
    });
});
</script>
<script>
$(document).on('click', '.edit-btn', function () {

    let id              = $(this).data('id');
    let speciality_name = $(this).data('speciality_name');
    let status          = $(this).data('active_inactive');

    Swal.fire({
        title: '<strong><small>Edit Exemption medical speciality</small></strong>',
        html: `
            <form id="exemptionCategoryeditForm">
                <input type="hidden" id="id" value="${id}">
                <input type="hidden" id="csrf" value="{{ csrf_token() }}">

                <!-- Type Name -->
                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Type Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text" id="speciality_name" name="speciality_name" class="form-control"
                               value="${speciality_name}">
                        <small class="text-danger d-none" id="speciality_name_error">
                            Type Name is required
                        </small>
                    </div>
                </div>

                <!-- Status -->
                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <select id="status" name="status" class="form-control">
                            <option value="">-- Select Status --</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <small class="text-danger d-none" id="status_error">
                            Status is required
                        </small>
                    </div>
                </div>
            </form>
        `,
        didOpen: () => {
            $('#status').val(status);
        },
        showCancelButton: true,
        confirmButtonText: 'Update',
        showLoaderOnConfirm: true,
        focusConfirm: false,

        preConfirm: () => {

            const popup = Swal.getPopup();
            const nameEl   = popup.querySelector('#speciality_name');
            const statusEl = popup.querySelector('#status');
            const nameErr  = popup.querySelector('#speciality_name_error');
            const statErr  = popup.querySelector('#status_error');
            // reset
            [nameEl, statusEl].forEach(el => el.classList.remove('is-invalid'));
            [nameErr, statErr].forEach(el => el.classList.add('d-none'));

            let valid = true;

            if (!nameEl.value.trim()) {
                nameEl.classList.add('is-invalid');
                nameErr.classList.remove('d-none');
                valid = false;
            }

            if (!statusEl.value) {
                statusEl.classList.add('is-invalid');
                statErr.classList.remove('d-none');
                valid = false;
            }

            if (!valid) return false;

            // AJAX call
            return $.ajax({
                url: "{{ route('master.exemption.medical.speciality.store') }}",
                type: "POST",
                dataType: "json",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id,
                    speciality_name: nameEl.value,
                    status: statusEl.value
                }
            }).catch(xhr => {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    if (errors.medical_speciality_name) {
                        nameEl.classList.add('is-invalid');
                        nameErr.textContent = errors.medical_speciality_name[0];
                        nameErr.classList.remove('d-none');
                    }
                } else {
                    Swal.showValidationMessage('Something went wrong!');
                }
            });
        }
    }).then((result) => {

        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: result.value.message,
                timer: 1500,
                showConfirmButton: false
            });

            // Reload datatable if exists
            $('#exemptionMedicalSpecialityTable').DataTable().ajax.reload(null, false);
        }
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