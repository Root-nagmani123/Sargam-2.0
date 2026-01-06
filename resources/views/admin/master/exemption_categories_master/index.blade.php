@extends('admin.layouts.master')

@section('title', 'Exemption categories')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Exemption categories" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Exemption categories</h4>
                        </div>
                        <div class="col-6">
                             <!-- <button id="showAlert" class="btn btn-primary">+ Add Exemption categories</button> -->
                            <div class="float-end gap-2">
                                 <button id="showAlert" class="btn btn-primary">+ Add Exemption categories</button>
                                <!-- <a href="{{route('master.exemption.category.master.create')}}" class="btn btn-primary">+
                                    Add Exemption categories</a> -->
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table" id="getcategory">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Name</th>
                                    <th class="col">Short Name</th>
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
        let table = $('#getcategory').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
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
<script>
document.getElementById('showAlert').addEventListener('click', function () {
    Swal.fire({
        title: '<strong>Add Exemption Category</strong>',
        html: `
            <form id="exemptionCategoryForm"
                  action="{{ route('master.exemption.category.master.store') }}"
                  method="POST">

                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="pk" value="${pk}">

                <!-- Type Name -->
                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Type Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text"
                               name="exemp_category_name"
                               id="exemp_category_name"
                               class="form-control">
                        <small class="text-danger d-none" id="exemp_category_name_error">
                            Type Name is required
                        </small>
                    </div>
                </div>

                <!-- Short Name -->
                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Short Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text"
                               name="exemp_cat_short_name"
                               id="exemp_cat_short_name"
                               class="form-control">
                        <small class="text-danger d-none" id="exemp_cat_short_name_error">
                            Short Name is required
                        </small>
                    </div>
                </div>

                <!-- Status -->
                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <select name="status"
                                id="status"
                                class="form-control">
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
        focusConfirm: false,

        preConfirm: () => {
            const popup = Swal.getPopup();

            const typeName   = popup.querySelector('#exemp_category_name');
            const shortName  = popup.querySelector('#exemp_cat_short_name');
            const status     = popup.querySelector('#status');

            const typeErr    = popup.querySelector('#exemp_category_name_error');
            const shortErr   = popup.querySelector('#exemp_cat_short_name_error');
            const statusErr  = popup.querySelector('#status_error');

            // Reset validation state
            [typeName, shortName, status].forEach(el => el.classList.remove('is-invalid'));
            [typeErr, shortErr, statusErr].forEach(el => el.classList.add('d-none'));

            let isValid = true;

            if (!typeName.value.trim()) {
                typeName.classList.add('is-invalid');
                typeErr.classList.remove('d-none');
                isValid = false;
            }

            if (!shortName.value.trim()) {
                shortName.classList.add('is-invalid');
                shortErr.classList.remove('d-none');
                isValid = false;
            }

            if (!status.value) {
                status.classList.add('is-invalid');
                statusErr.classList.remove('d-none');
                isValid = false;
            }
            return isValid;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('exemptionCategoryForm').submit();
        }
    });
});
</script>
<script>
    $(document).on('click', '.edit-btn', function() {

        let pk = $(this).data('id');
        //alert(pk);
         let exemp_category_name = $(this).data('exemp_category_name');
        let exemp_cat_short_name = $(this).data('exemp_cat_short_name');
        let status = $(this).data('active_inactive');

        let url = "{{ route('master.exemption.category.master.updatedata') }}";

        Swal.fire({
            title: '<strong>Edit Course Group Type</strong>',
            html: `
            <form id="exemptionCategoryeditForm"
                  action="${url}"
                  method="POST">

                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <!-- Type Name -->
                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Type Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text"
                               name="exemp_category_name"
                               id="exemp_category_name"
                               class="form-control" value="${exemp_category_name}">
                        <small class="text-danger d-none" id="exemp_category_name_error">
                            Type Name is required
                        </small>
                    </div>
                </div>

                <!-- Short Name -->
                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Short Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text"
                               name="exemp_cat_short_name"
                               id="exemp_cat_short_name"
                               class="form-control" value="${exemp_cat_short_name}">
                        <small class="text-danger d-none" id="exemp_cat_short_name_error">
                            Short Name is required
                        </small>
                    </div>
                </div>

                <!-- Status -->
                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <select name="status"
                                id="status"
                                class="form-control">
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
            $('#status').val(status).trigger('change');
        },
            showCancelButton: true,
            confirmButtonText: 'Update',
            focusConfirm: false,

            preConfirm: () => {
            const popup = Swal.getPopup();

            const typeName   = popup.querySelector('#exemp_category_name');
            const shortName  = popup.querySelector('#exemp_cat_short_name');
            const status     = popup.querySelector('#status');

            const typeErr    = popup.querySelector('#exemp_category_name_error');
            const shortErr   = popup.querySelector('#exemp_cat_short_name_error');
            const statusErr  = popup.querySelector('#status_error');

            // Reset validation state
            [typeName, shortName, status].forEach(el => el.classList.remove('is-invalid'));
            [typeErr, shortErr, statusErr].forEach(el => el.classList.add('d-none'));

            let isValid = true;

            if (!typeName.value.trim()) {
                typeName.classList.add('is-invalid');
                typeErr.classList.remove('d-none');
                isValid = false;
            }

            if (!shortName.value.trim()) {
                shortName.classList.add('is-invalid');
                shortErr.classList.remove('d-none');
                isValid = false;
            }

            if (!status.value) {
                status.classList.add('is-invalid');
                statusErr.classList.remove('d-none');
                isValid = false;
            }
            return isValid;
        }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('exemptionCategoryeditForm').submit();
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