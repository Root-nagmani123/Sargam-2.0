@extends('admin.layouts.master')

@section('title', 'Course Group Type')

@section('setup_content')
<style>
    .disabled-link {
        pointer-events: none;
        opacity: 0.5;
        cursor: not-allowed;
    }
    .disabled-link.btn {
        pointer-events: none !important;
    }
    .course-group-type-card {
        border-radius: 0.75rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-start: 4px solid #004a93;
        transition: box-shadow 0.2s ease;
    }
    .course-group-type-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }
    #coursegrouptype tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }
    #coursegrouptype tbody tr {
        transition: background-color 0.15s ease;
    }
    #coursegrouptype tbody tr:hover {
        background-color: rgba(0, 74, 147, 0.03);
    }
    .btn-add-course-type {
        border-radius: 0.5rem;
        font-weight: 600;
        padding: 0.5rem 1.25rem;
        transition: all 0.2s ease;
    }
    .btn-add-course-type:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 74, 147, 0.3);
    }
    #coursegrouptype_wrapper .dataTables_filter input,
    #coursegrouptype_wrapper .dataTables_length select {
        border-radius: 0.5rem;
        padding: 0.35rem 0.75rem;
        border: 1px solid #dee2e6;
    }
    #coursegrouptype_wrapper .dataTables_filter input:focus {
        border-color: #004a93;
        box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.15);
    }
    @media (prefers-reduced-motion: reduce) {
        .course-group-type-card, .btn-add-course-type { transition: none; }
        .btn-add-course-type:hover { transform: none; }
    }

    /* Responsive - Tablet (max 991px) */
    @media (max-width: 991.98px) {
        .course-group-type-index .datatables .table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }
        .course-group-type-index #coursegrouptype {
            min-width: 480px;
        }
        .course-group-type-index #coursegrouptype thead th,
        .course-group-type-index #coursegrouptype tbody td {
            padding: 0.75rem 0.5rem;
            font-size: 0.9rem;
        }
        .course-group-type-index #coursegrouptype_wrapper .dataTables_length,
        .course-group-type-index #coursegrouptype_wrapper .dataTables_filter {
            margin-bottom: 0.5rem;
        }
    }

    /* Responsive - Small tablet / large phone (max 767px) */
    @media (max-width: 767.98px) {
        .course-group-type-index .card-body {
            padding: 1rem !important;
        }
        .course-group-type-index #coursegrouptype thead th,
        .course-group-type-index #coursegrouptype tbody td {
            padding: 0.6rem 0.5rem;
            font-size: 0.875rem;
        }
        .course-group-type-index #coursegrouptype_wrapper .dataTables_wrapper .row {
            flex-direction: column;
            gap: 0.5rem;
        }
        .course-group-type-index #coursegrouptype_wrapper .dataTables_length,
        .course-group-type-index #coursegrouptype_wrapper .dataTables_filter {
            text-align: left !important;
        }
        .course-group-type-index #coursegrouptype_wrapper .dataTables_filter input {
            width: 100%;
            max-width: 100%;
        }
    }

    /* Responsive - Phone (max 575px) */
    @media (max-width: 575.98px) {
        .course-group-type-index.container-fluid {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
        .course-group-type-index .card-body {
            padding: 0.75rem !important;
        }
        .course-group-type-index .d-flex.flex-column.flex-md-row {
            flex-direction: column !important;
            gap: 0.75rem !important;
        }
        .course-group-type-index .btn-add-course-type {
            width: 100%;
            justify-content: center;
        }
        .course-group-type-index .table-responsive {
            margin-left: -0.5rem;
            margin-right: -0.5rem;
            border-radius: 0.5rem;
        }
        .course-group-type-index #coursegrouptype {
            min-width: 420px;
        }
        .course-group-type-index #coursegrouptype thead th,
        .course-group-type-index #coursegrouptype tbody td {
            padding: 0.5rem 0.4rem;
            font-size: 0.8125rem;
        }
        .course-group-type-index #coursegrouptype_wrapper .dataTables_paginate {
            flex-wrap: wrap;
            gap: 0.25rem;
        }
        .course-group-type-index #coursegrouptype_wrapper .dataTables_paginate .paginate_button {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
    }

    /* Responsive - Very small phone (max 375px) */
    @media (max-width: 375px) {
        .course-group-type-index.container-fluid {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        .course-group-type-index .card-body {
            padding: 0.5rem !important;
        }
        .course-group-type-index h4 {
            font-size: 1rem;
        }
        .course-group-type-index .small.text-muted {
            font-size: 0.75rem;
        }
        .course-group-type-index #coursegrouptype {
            min-width: 360px;
        }
    }

    /* DataTables Responsive child row - ensure expandable content is usable on touch */
    .course-group-type-index .dtr-details {
        padding: 0.5rem 0;
    }
    .course-group-type-index .dtr-details li {
        padding: 0.35rem 0;
        border-bottom: 1px solid #eee;
    }
    .course-group-type-index .dtr-details li:last-child {
        border-bottom: none;
    }
</style>
<div class="container-fluid course-group-type-index">
    <x-breadcrum title="Course Group Type"></x-breadcrum>
    <div class="datatables">
        <div class="card course-group-type-card border-0 overflow-hidden">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 p-2 bg-primary bg-opacity-10">
                            <i class="material-icons material-symbols-rounded text-primary" style="font-size: 1.5rem;">category</i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-dark">Course Group Type</h4>
                            <p class="mb-0 small text-muted mt-1">Manage course group type configurations</p>
                        </div>
                    </div>
                    <button id="showAlert" class="btn btn-primary btn-add-course-type d-inline-flex align-items-center gap-2">
                        <i class="material-icons material-symbols-rounded" style="font-size: 1.25rem;">add</i>
                        <span>Add Course Group Type</span>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="coursegrouptype">
                        <thead>
                            <tr>
                                <th class="col" style="width: 5rem;">S.No.</th>
                                <th class="col">Type Name</th>
                                <th class="col" style="width: 8rem;">Status</th>
                                <th class="col text-end" style="width: 12rem;">Action</th>
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
        let table = $('#coursegrouptype').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            responsive: true,
            ajax: {
                url: "{{ route('master.course.group.type.grouptypeview') }}",
                data: function(d) {
                    d.pk = $('#pk').val();
                    d.active_inactive = $('#active_inactive').val();
                    //  console.log(d.pk);

                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 3
                },
                {
                    data: 'type_name',
                    name: 'type_name',
                    responsivePriority: 1
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 4
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    responsivePriority: 2
                }
            ]

        });

        $(document).on('change', '.plain-status-toggle', function () {
    var checkbox = $(this);
    var previousState = !checkbox.is(':checked'); // save previous state
    var pk = checkbox.data('id');
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
        } else {
            // revert checkbox to previous state
            checkbox.prop('checked', previousState);

            Swal.fire({
                icon: 'info',
                title: 'Cancelled',
                text: 'Status change has been cancelled.',
                timer: 1200,
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
        title: '<strong class="text-dark">Add Course Group Type</strong>',
        html: `
        <form id="courseGroupTypeForm" class="text-start">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark">
                    Type Name <span class="text-danger">*</span>
                </label>
                <input type="text" name="type_name" id="type_name"
                       class="form-control form-control-lg rounded-2"
                       placeholder="Enter type name">
                <small class="text-danger d-none" id="type_name_error">
                    Type Name is required
                </small>
            </div>
        </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        customClass: { confirmButton: 'btn btn-primary px-4 rounded-2', cancelButton: 'btn btn-outline-secondary px-4 rounded-2' },
        focusConfirm: false,

        preConfirm: () => {
            const typeNameInput = Swal.getPopup().querySelector('#type_name');
            const errorMsg = Swal.getPopup().querySelector('#type_name_error');

            typeNameInput.classList.remove('is-invalid');
            errorMsg.classList.add('d-none');

            if (!typeNameInput.value.trim()) {
                typeNameInput.classList.add('is-invalid');
                errorMsg.classList.remove('d-none');
                return false;
            }

            return {
                type_name: typeNameInput.value.trim()
            };
        }
    }).then((result) => {

        if (result.isConfirmed) {

            fetch(`{{ route('master.course.group.type.store') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(result.value)
            })
            .then(response => response.json())
            .then(data => {
             
                if (data.status === true) {
                  
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message
                    });
                    $('#coursegrouptype').DataTable().ajax.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }

            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Something went wrong!'
                });
            });
        }
    });
});


</script>

<!-- EDIT FORM  -->

<script>
  
$(document).on('click', '.edit-btn', function () {

    let pk = $(this).data('id');          // encrypted id
    let typeName = $(this).data('type-name');

    let url = "{{ route('master.course.group.type.store') }}";

    Swal.fire({
        title: '<strong class="text-dark">Edit Course Group Type</strong>',
        html: `
        <form id="courseGroupTypeEditForm" class="text-start">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="id" value="${pk}">
            <div class="mb-3">
                <label class="form-label fw-semibold text-dark">
                    Type Name <span class="text-danger">*</span>
                </label>
                <input type="text" name="type_name" id="type_name"
                       class="form-control form-control-lg rounded-2"
                       value="${typeName.replace(/"/g, '&quot;')}">
                <small class="text-danger d-none" id="type_name_error">
                    Type Name is required
                </small>
            </div>
        </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Update',
        cancelButtonText: 'Cancel',
        customClass: { confirmButton: 'btn btn-primary px-4 rounded-2', cancelButton: 'btn btn-outline-secondary px-4 rounded-2' },
        focusConfirm: false,

        preConfirm: () => {
            const typeNameInput = Swal.getPopup().querySelector('#type_name');
            const errorMsg = Swal.getPopup().querySelector('#type_name_error');

            typeNameInput.classList.remove('is-invalid');
            errorMsg.classList.add('d-none');

            if (!typeNameInput.value.trim()) {
                typeNameInput.classList.add('is-invalid');
                errorMsg.classList.remove('d-none');
                return false;
            }

            return {
                id: pk,
                type_name: typeNameInput.value.trim()
            };
        }
    }).then((result) => {

        if (result.isConfirmed) {

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: result.value.id,
                    type_name: result.value.type_name
                },
                success: function (response) {

                    if (response.status === true) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            text: response.message
                        });

                        // 🔁 Reload DataTable if exists
                         $('#coursegrouptype').DataTable().ajax.reload();

                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function (xhr) {

                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        Swal.fire('Validation Error', errors.type_name[0], 'error');
                    } else {
                        Swal.fire('Error', 'Something went wrong!', 'error');
                    }
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