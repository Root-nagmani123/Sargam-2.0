@extends('admin.layouts.master')

@section('title', 'Course Group Type')

@section('setup_content')
<style>
    .disabled-link {
        pointer-events: none;
        /* click बंद */
        opacity: 0.5;
        /* disabled look */
        cursor: not-allowed;
    }
    .dropdown-item {
    padding: 10px 14px;
    font-size: 14px;
    transition: background 0.2s ease;
}

.dropdown-item:hover {
    background-color: #f5f6f8;
}

.dropdown-item i {
    font-size: 18px;
}

</style>
<div class="container-fluid">
    <x-breadcrum title="Course Group Type"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h4 class="mb-0">Course Group Type</h4>
                        </div>

                        <div class="col-6">
                            <div class="d-flex justify-content-end gap-2 align-items-center">
                                <button id="showAlert" class="btn btn-primary">Add Course Group Type</button>
                                <!-- Add Button -->
                                <!-- <a href="{{ route('master.course.group.type.create') }}" class="btn btn-primary">
                                    <i class="material-icons menu-icon me-1">add</i> Add Course Group Type
                                </a> -->


                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table" id="coursegrouptype">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th>S.No.</th>
                                    <th>Type Name</th>
                                    <th>Status</th>
                                    <th>Action</th>
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
        let table = $('#coursegrouptype').DataTable({
            processing: true,
            serverSide: true,
            searching: true, 
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
                    searchable: false
                },
                {
                    data: 'type_name',
                    name: 'type_name'
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
                // else if (result.dismiss === Swal.DismissReason.cancel) {
                //     // Revert the checkbox state
                //     checkbox.prop('checked', !active_inactive);

                //     // Show cancel message
                //     Swal.fire({
                //         icon: 'info',
                //         title: 'Cancelled',
                //         text: 'Status change has been cancelled.',
                //         timer: 1500,
                //         showConfirmButton: false
                //     });
                // }
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
    document.getElementById('showAlert').addEventListener('click', function() {
        Swal.fire({
            title: '<strong>Add Course Group Type</strong>',
            html: `
        <form id="courseGroupTypeForm"
              action="{{ route('master.course.group.type.store') }}"
              method="POST">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            
            <div class="row mb-1 align-items-center">
                <label class="col-auto col-form-label fw-semibold">
                    Type Name <span class="text-danger">*</span>
                </label>
                <div class="col">
                    <input type="text" 
                           name="type_name" 
                           id="type_name" 
                           class="form-control">
                    <small class="text-danger d-none" id="type_name_error">
                        Type Name is required
                    </small>
                </div>
            </div>
        </form>
        `,
            showCancelButton: true,
            confirmButtonText: 'Submit',
            focusConfirm: false,

            preConfirm: () => {
                const typeNameInput = Swal.getPopup().querySelector('#type_name');
                const errorMsg = Swal.getPopup().querySelector('#type_name_error');

                // reset state
                typeNameInput.classList.remove('is-invalid');
                errorMsg.classList.add('d-none');

                if (!typeNameInput.value.trim()) {
                    typeNameInput.classList.add('is-invalid');
                    errorMsg.classList.remove('d-none');
                    return false;
                }

                return true;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('courseGroupTypeForm').submit();
            }
        });
    });
</script>

<!-- EDIT FORM  -->

<script>
    $(document).on('click', '.edit-btn', function() {

        let pk = $(this).data('id');
        //alert(pk);
        let typeName = $(this).data('type-name');

        let url = "{{ route('master.course.group.type.updatestatus') }}";

        Swal.fire({
            title: '<strong>Edit Course Group Type</strong>',
            html: `
            <form id="courseGroupTypeEditForm"
                  action="${url}"
              method="POST">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="pk" value="${pk}">
            <div class="row mb-1 align-items-center">
                <label class="col-auto col-form-label fw-semibold">
                    Type Name <span class="text-danger">*</span>
                </label>
                <div class="col">
                    <input type="text"
                           name="type_name"
                           id="type_name"
                           class="form-control"
                           value="${typeName}">
                    <small class="text-danger d-none" id="type_name_error">
                        Type Name is required
                    </small>
                </div>
            </div>
        </form>
        `,
            showCancelButton: true,
            confirmButtonText: 'Update',
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
                return true;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('courseGroupTypeEditForm').submit();
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