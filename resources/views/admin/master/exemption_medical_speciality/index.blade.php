@extends('admin.layouts.master')

@section('title', 'Exemption medical speciality')

@section('setup_content')
<style>
/* Exemption medical speciality - responsive (mobile/tablet only, desktop unchanged) */

/* Responsive - Tablet (768px - 991px) */
@media (max-width: 991.98px) {
    .exemption-medical-speciality-index .datatables .table-scroll-wrapper {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    .exemption-medical-speciality-index .datatables #exemptionCategoryeditForm {
        min-width: 500px;
    }

    .exemption-medical-speciality-index .datatables #exemptionCategoryeditForm th,
    .exemption-medical-speciality-index .datatables #exemptionCategoryeditForm td {
        padding: 8px 10px;
        font-size: 0.9rem;
    }
}

/* Responsive - Small tablet / large phone (576px - 767px) */
@media (max-width: 767.98px) {
    .exemption-medical-speciality-index .datatables .card-body {
        padding: 1rem !important;
    }

    .exemption-medical-speciality-index .datatables #exemptionCategoryeditForm th,
    .exemption-medical-speciality-index .datatables #exemptionCategoryeditForm td {
        padding: 6px 8px;
        font-size: 0.85rem;
    }
}

/* Responsive - Phone (max 575px) */
@media (max-width: 575.98px) {
    .exemption-medical-speciality-index.container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    .exemption-medical-speciality-index .exemption-header-row {
        flex-direction: column;
        gap: 0.5rem;
    }

    .exemption-medical-speciality-index .exemption-header-row .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }

    .exemption-medical-speciality-index .exemption-header-row .float-end {
        float: none !important;
    }

    .exemption-medical-speciality-index .exemption-header-row .btn {
        width: 100%;
    }

    .exemption-medical-speciality-index .datatables .card-body {
        padding: 0.75rem !important;
    }

    .exemption-medical-speciality-index .datatables .table-scroll-wrapper {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .exemption-medical-speciality-index .datatables #exemptionCategoryeditForm th,
    .exemption-medical-speciality-index .datatables #exemptionCategoryeditForm td {
        padding: 6px 8px;
        font-size: 0.8125rem;
    }

    .exemption-medical-speciality-index .datatables #exemptionCategoryeditForm_wrapper .dataTables_length,
    .exemption-medical-speciality-index .datatables #exemptionCategoryeditForm_wrapper .dataTables_filter {
        text-align: left !important;
    }

    .exemption-medical-speciality-index .datatables #exemptionCategoryeditForm_wrapper .dataTables_length select {
        margin: 0 0.5rem 0 0;
    }
}

/* Swal popup forms - responsive */
@media (max-width: 575.98px) {
    .swal2-popup #exemptionCategoryForm .row,
    .swal2-popup #exemptionCategoryeditForm .row {
        flex-direction: column;
        align-items: stretch;
    }

    .swal2-popup #exemptionCategoryForm .row .col-auto,
    .swal2-popup #exemptionCategoryeditForm .row .col-auto {
        margin-bottom: 0.25rem;
    }

    .swal2-popup #exemptionCategoryForm .row .col,
    .swal2-popup #exemptionCategoryeditForm .row .col {
        max-width: 100%;
    }
}
</style>
<div class="container-fluid exemption-medical-speciality-index">
    <x-breadcrum title="Exemption medical speciality" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row exemption-header-row">
                        <div class="col-6">
                            <h4>Exemption medical speciality</h4>
                        </div>
                        <div class="col-6">
                             <!-- <button id="showAlert" class="btn btn-primary">+ Add Exemption categories</button> -->
                            <div class="float-end gap-2">
                                 <button id="showAlert" class="btn btn-primary">+ Add Exemption medical speciality</button>
                                <!-- <a href="{{route('master.exemption.category.master.create')}}" class="btn btn-primary">+
                                    Add Exemption categories</a> -->
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive table-scroll-wrapper">
                        <table class="table" id="exemptionCategoryeditForm">
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
        <!-- end Zero Configuration -->
    </div>
</div>
<input type="hidden" id="pk" value="">
<input type="hidden" id="active_inactive" value="">
@endsection
@section('scripts')
<script>
    $(function() {
        let table = $('#exemptionCategoryeditForm').DataTable({
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



        $(document).on('click', '.delete-btn', function(e) {
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
                    Swal.fire({
                        icon: 'success',
                        title: 'Delete!',
                        text: 'Delete has been successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // Revert the checkbox state
                    checkbox.prop('checked', !active_inactive);
                    // Show cancel message
                    Swal.fire({
                        icon: 'danger',
                        title: 'Cancelled',
                        text: 'Delete has been cancelled.',
                        timer: 1500,
                        showConfirmButton: false
                    });
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
             $('#exemptionCategoryeditForm').DataTable().ajax.reload();
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
            $('#exemptionCategoryeditForm').DataTable().ajax.reload(null, false);
        }
    });
});
</script>

@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: "{{ session('success') }}"
    });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: "{{ session('error') }}"
    });
</script>
@endif
@endsection