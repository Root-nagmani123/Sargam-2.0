@extends('admin.layouts.master')

@section('title', 'Course Group Type')

@section('setup_content')
<div class="container-fluid px-3 px-md-4 px-lg-5 py-3 py-md-4 course-group-type-index">
    <x-breadcrum title="Course Group Type"></x-breadcrum>
    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden border-start border-4 border-primary admin-card">
            <div class="card-body p-4 p-lg-5">
                <section class="row align-items-center mb-4 g-3 row-gap-2" role="region" aria-labelledby="courseGroupTypeHeading">
                    <div class="col-12 col-md-6 col-lg-4">
                        <h1 id="courseGroupTypeHeading" class="h4 fw-bold mb-2 mb-md-0 d-flex align-items-center gap-2">
                            <span class="rounded-2 p-2 bg-primary bg-opacity-10">
                                <i class="material-icons material-symbols-rounded text-primary fs-4">category</i>
                            </span>
                            <span>Course Group Type</span>
                        </h1>
                        <p class="mb-0 small text-body-secondary mt-1">Manage course group type configurations</p>
                    </div>
                    <div class="col-12 col-md-6 col-lg-8">
                        <div class="d-flex flex-wrap justify-content-md-end">
                            <button id="showAlert" type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold shadow-sm" aria-label="Add course group type">
                                <i class="material-icons material-symbols-rounded fs-5">add</i>
                                <span>Add Course Group Type</span>
                            </button>
                        </div>
                    </div>
                </section>
                <div class="border-top pt-4 mt-2"></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="coursegrouptype">
                        <thead>
                            <tr>
                                <th>S.No.</th>
                                <th>Course group type name</th>
                                <th>Status</th>
                                <th>Action</th>
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
        title: '<strong class="text-body">Add Course Group Type</strong>',
        html: `
        <form id="courseGroupTypeForm" class="text-start">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">
                    Type Name <span class="text-danger">*</span>
                </label>
                <input type="text" name="type_name" id="type_name"
                       class="form-control form-control-lg rounded-2"
                       placeholder="Enter type name">
                <div class="invalid-feedback" id="type_name_error">
                    Type Name is required
                </div>
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
            typeNameInput.classList.remove('is-invalid');
            if (!typeNameInput.value.trim()) {
                typeNameInput.classList.add('is-invalid');
                return false;
            }
            return { type_name: typeNameInput.value.trim() };
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
        title: '<strong class="text-body">Edit Course Group Type</strong>',
        html: `
        <form id="courseGroupTypeEditForm" class="text-start">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="id" value="${pk}">
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">
                    Type Name <span class="text-danger">*</span>
                </label>
                <input type="text" name="type_name" id="type_name"
                       class="form-control form-control-lg rounded-2"
                       value="${typeName.replace(/"/g, '&quot;')}">
                <div class="invalid-feedback" id="type_name_error">
                    Type Name is required
                </div>
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
            typeNameInput.classList.remove('is-invalid');
            if (!typeNameInput.value.trim()) {
                typeNameInput.classList.add('is-invalid');
                return false;
            }
            return { id: pk, type_name: typeNameInput.value.trim() };
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