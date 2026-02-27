@extends('admin.layouts.master')

@section('title', 'Exemption categories')

@section('setup_content')
<style>
/* Exemption categories - modern Bootstrap 5 UI */
.exemption-categories-card {
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-start: 4px solid #004a93;
    transition: box-shadow 0.2s ease;
}
.exemption-categories-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}
#exceptiongetcategory tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
}
#exceptiongetcategory tbody tr {
    transition: background-color 0.15s ease;
}
#exceptiongetcategory tbody tr:hover {
    background-color: rgba(0, 74, 147, 0.03);
}
#exceptiongetcategory thead th {
    font-weight: 600;
    color: var(--bs-body-color);
    padding: 1rem 0.75rem;
    border-bottom: 1px solid var(--bs-border-color);
}
.btn-add-exemption-category {
    border-radius: 0.5rem;
    font-weight: 600;
    padding: 0.5rem 1.25rem;
    transition: all 0.2s ease;
}
.btn-add-exemption-category:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 74, 147, 0.3);
}
#exceptiongetcategory_wrapper .dataTables_filter input,
#exceptiongetcategory_wrapper .dataTables_length select {
    border-radius: 0.5rem;
    padding: 0.35rem 0.75rem;
    border: 1px solid var(--bs-border-color);
}
#exceptiongetcategory_wrapper .dataTables_filter input:focus {
    border-color: #004a93;
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.15);
}
@media (prefers-reduced-motion: reduce) {
    .exemption-categories-card, .btn-add-exemption-category { transition: none; }
    .btn-add-exemption-category:hover { transform: none; }
}

/* Responsive - Tablet (768px - 991px) */
@media (max-width: 991.98px) {
    .exemption-categories-index .datatables .table-scroll-wrapper {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    .exemption-categories-index .datatables #exceptiongetcategory {
        min-width: 500px;
    }
    .exemption-categories-index .datatables #exceptiongetcategory th,
    .exemption-categories-index .datatables #exceptiongetcategory td {
        padding: 8px 10px;
        font-size: 0.9rem;
    }
}

/* Responsive - Small tablet / large phone (576px - 767px) */
@media (max-width: 767.98px) {
    .exemption-categories-index .datatables .card-body {
        padding: 1rem !important;
    }
    .exemption-categories-index .datatables #exceptiongetcategory th,
    .exemption-categories-index .datatables #exceptiongetcategory td {
        padding: 6px 8px;
        font-size: 0.85rem;
    }
    /* Stack DataTables length & search nicely on small screens */
    .exemption-categories-index #exceptiongetcategory_wrapper .dataTables_wrapper .row {
        flex-direction: column;
        gap: 0.5rem;
    }
    .exemption-categories-index #exceptiongetcategory_wrapper .dataTables_length,
    .exemption-categories-index #exceptiongetcategory_wrapper .dataTables_filter {
        text-align: left !important;
    }
    .exemption-categories-index #exceptiongetcategory_wrapper .dataTables_filter input {
        width: 100%;
        max-width: 100%;
    }
}

/* Responsive - Phone (max 575px) */
@media (max-width: 575.98px) {
    .exemption-categories-index.container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    /* Header + "Add Exemption categories" button alignment */
    .exemption-categories-index .exemption-header-row {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }
    .exemption-categories-index .exemption-header-row > * {
        width: 100%;
    }
    .exemption-categories-index .exemption-header-row .btn-add-exemption-category {
        width: 100%;
        justify-content: center;
    }

    .exemption-categories-index .datatables .card-body {
        padding: 0.75rem !important;
    }
    .exemption-categories-index .datatables .table-scroll-wrapper {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .exemption-categories-index .datatables #exceptiongetcategory th,
    .exemption-categories-index .datatables #exceptiongetcategory td {
        padding: 6px 8px;
        font-size: 0.8125rem;
    }
    .exemption-categories-index .datatables #exceptiongetcategory_wrapper .dataTables_length,
    .exemption-categories-index .datatables #exceptiongetcategory_wrapper .dataTables_filter {
        text-align: left !important;
    }
    .exemption-categories-index .datatables #exceptiongetcategory_wrapper .dataTables_length select {
        margin: 0 0.5rem 0 0;
    }
}

/* Swal popup forms - responsive */
@media (max-width: 575.98px) {
    .swal2-popup {
        width: 100% !important;
        max-width: 100% !important;
        padding-left: 1.25rem !important;
        padding-right: 1.25rem !important;
    }

    .swal2-popup #exemptionCategoryForm,
    .swal2-popup #exemptionCategoryeditForm {
        max-width: 100%;
        width: 100%;
    }

    .swal2-popup #exemptionCategoryForm .mb-3,
    .swal2-popup #exemptionCategoryeditForm .mb-3 {
        text-align: left;
    }

    .swal2-popup #exemptionCategoryForm .form-control,
    .swal2-popup #exemptionCategoryeditForm .form-control,
    .swal2-popup #exemptionCategoryForm .form-select,
    .swal2-popup #exemptionCategoryeditForm .form-select {
        width: 100%;
    }
}
</style>
<div class="container-fluid exemption-categories-index">
    <x-breadcrum title="Exemption categories" />
    <div class="datatables">
        <div class="card exemption-categories-card border-0 overflow-hidden">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-4 exemption-header-row">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 p-2 bg-primary bg-opacity-10">
                            <i class="material-icons material-symbols-rounded text-primary" style="font-size: 1.5rem;">category</i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-dark">Exemption categories</h4>
                            <p class="mb-0 small text-muted mt-1">Manage exemption category configurations</p>
                        </div>
                    </div>
                    <button id="showAlert" class="btn btn-primary btn-add-exemption-category d-inline-flex align-items-center gap-2">
                        <i class="material-icons material-symbols-rounded" style="font-size: 1.25rem;">add</i>
                        <span>Add Exemption categories</span>
                    </button>
                </div>
                <div class="table-responsive table-scroll-wrapper">
                    <table class="table text-nowrap align-middle mb-0" id="exceptiongetcategory">
                        <thead>
                            <tr>
                                <th class="col">S.No.</th>
                                <th class="col">Category name</th>
                                <th class="col">Short Name</th>
                                <th class="col">Status</th>
                                <th class="col">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="pk" value="">
<input type="hidden" id="active_inactive" value="">
@endsection
@section('scripts')
<script>
    $(function() {
        let table = $('#exceptiongetcategory').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
			order: [[0, 'desc']], 
            ajax: {
                url: "{{ route('master.exemption.category.master.getcategory') }}",
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
                    data: 'exemp_category_name',
                    name: 'exemp_category_name'
                },
                {
                    data: 'ShortName',
                    name: 'ShortName'
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
            var actionText = active_inactive ? 'activate' : 'deactivate';
            var confirmBtnText = active_inactive ? 'Yes, activate' : 'Yes, deactivate';
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
<script>
document.getElementById('showAlert').addEventListener('click', function () {

    Swal.fire({
        title: '<strong>Add Exemption Category</strong>',
        html: `
            <form id="exemptionCategoryForm" class="text-start">

                <div class="mb-3">
                    <label for="exemp_category_name" class="form-label fw-semibold">
                        Type Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="exemp_category_name" class="form-control form-control-sm" placeholder="Enter category name">
                    <small class="text-danger d-none" id="exemp_category_name_error">Required</small>
                </div>

                <div class="mb-3">
                    <label for="exemp_cat_short_name" class="form-label fw-semibold">
                        Short Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="exemp_cat_short_name" class="form-control form-control-sm" placeholder="Enter short name">
                    <small class="text-danger d-none" id="exemp_cat_short_name_error">Required</small>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <select id="status" class="form-select form-select-sm">
                        <option value="">Select status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <small class="text-danger d-none" id="status_error">Required</small>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Submit',
        focusConfirm: false,

        preConfirm: () => {

            // Fields
            const name = document.getElementById('exemp_category_name');
            const shortName = document.getElementById('exemp_cat_short_name');
            const status = document.getElementById('status');

            // Errors
            let isValid = true;
            document.querySelectorAll('small.text-danger').forEach(e => e.classList.add('d-none'));

            if (!name.value.trim()) {
                document.getElementById('exemp_category_name_error').classList.remove('d-none');
                name.focus();
                isValid = false;
            }

            else if (!shortName.value.trim()) {
                document.getElementById('exemp_cat_short_name_error').classList.remove('d-none');
                shortName.focus();
                isValid = false;
            }

            else if (!status.value) {
                document.getElementById('status_error').classList.remove('d-none');
                status.focus();
                isValid = false;
            }

            if (!isValid) {
                return false; // ❌ stop submission
            }

            // ✅ submit only if valid
            const formData = new FormData();
            formData.append('exemp_category_name', name.value);
            formData.append('exemp_cat_short_name', shortName.value);
            formData.append('status', status.value);

            return fetch("{{ route('master.exemption.category.master.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .catch(() => {
                Swal.showValidationMessage('Server Error or Session Expired');
            });
        }
    }).then(result => {
        if (result.isConfirmed && result.value?.status) {
            Swal.fire('Success', result.value.message, 'success');
            $('#exceptiongetcategory').DataTable().ajax.reload();
        }
    });

});


</script>
<script>
    $(document).on('click', '.edit-btn', function () {

    let pk = $(this).data('id');
    let exemp_category_name = $(this).data('exemp_category_name');
    let exemp_cat_short_name = $(this).data('exemp_cat_short_name');
    let status = $(this).data('active_inactive');

    Swal.fire({
        title: '<strong>Edit Exemption Category</strong>',
        html: `
            <form id="exemptionCategoryeditForm" class="text-start">

                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="pk" value="${pk}">

                <div class="mb-3">
                    <label for="exemp_category_name" class="form-label fw-semibold">
                        Type Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           name="exemp_category_name"
                           id="exemp_category_name"
                           class="form-control form-control-sm"
                           value="${exemp_category_name}"
                           placeholder="Enter category name">
                    <small class="text-danger d-none" id="exemp_category_name_error">Required</small>
                </div>

                <div class="mb-3">
                    <label for="exemp_cat_short_name" class="form-label fw-semibold">
                        Short Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           name="exemp_cat_short_name"
                           id="exemp_cat_short_name"
                           class="form-control form-control-sm"
                           value="${exemp_cat_short_name}"
                           placeholder="Enter short name">
                    <small class="text-danger d-none" id="exemp_cat_short_name_error">Required</small>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">Select status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <small class="text-danger d-none" id="status_error">Required</small>
                </div>

            </form>
        `,
        didOpen: () => {
            $('#status').val(status);
        },
        showCancelButton: true,
        confirmButtonText: 'Update',
        focusConfirm: false,

        preConfirm: () => {

            const popup = Swal.getPopup();
            const form = popup.querySelector('#exemptionCategoryeditForm');

            const typeName = form.querySelector('#exemp_category_name');
            const shortName = form.querySelector('#exemp_cat_short_name');
            const statusEl = form.querySelector('#status');

            let valid = true;

            if (!typeName.value.trim()) valid = false;
            if (!shortName.value.trim()) valid = false;
            if (!statusEl.value) valid = false;

            if (!valid) {
                Swal.showValidationMessage('All fields are required');
                return false;
            }

            const formData = new FormData(form);

            return fetch("{{ route('master.exemption.category.master.store') }}", {
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.text())
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch {
                    throw new Error(text);
                }
            })
            .catch(() => {
                Swal.showValidationMessage('Server error or session expired');
            });
        }

    }).then(result => {
        if (result.isConfirmed && result.value?.status) {
            Swal.fire('Updated!', result.value.message, 'success');
            $('#exceptiongetcategory').DataTable().ajax.reload();
        }
    });
});

$(document).on('click', '.deleteBtn', function (e) {
    e.preventDefault();

    const btn = $(this);
    const url = btn.data('url');
    const pk  = btn.data('pk');

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
                data: {
                    _method: 'DELETE',
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function () {
                    btn.prop('disabled', true);
                },
                success: function (res) {
                    if (res.status) {
                        Swal.fire('Deleted!', res.message, 'success');

                        // ✅ Reload DataTable without page reload
                        $('#memotypemaster-table')
                            .DataTable()
                            .ajax.reload(null, false);
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