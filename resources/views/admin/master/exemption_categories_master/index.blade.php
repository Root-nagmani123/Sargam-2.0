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
                        <table class="table" id="exceptiongetcategory">
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
        let table = $('#exceptiongetcategory').DataTable({
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
            <form id="exemptionCategoryForm">

                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Type Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text" id="exemp_category_name" class="form-control">
                        <small class="text-danger d-none" id="exemp_category_name_error">Required</small>
                    </div>
                </div>

                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Short Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text" id="exemp_cat_short_name" class="form-control">
                        <small class="text-danger d-none" id="exemp_cat_short_name_error">Required</small>
                    </div>
                </div>

                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <select id="status" class="form-control">
                            <option value="">Select</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <small class="text-danger d-none" id="status_error">Required</small>
                    </div>
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
            <form id="exemptionCategoryeditForm">

                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="pk" value="${pk}">

                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Type Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text"
                               name="exemp_category_name"
                               id="exemp_category_name"
                               class="form-control"
                               value="${exemp_category_name}">
                        <small class="text-danger d-none" id="exemp_category_name_error">Required</small>
                    </div>
                </div>

                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Short Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text"
                               name="exemp_cat_short_name"
                               id="exemp_cat_short_name"
                               class="form-control"
                               value="${exemp_cat_short_name}">
                        <small class="text-danger d-none" id="exemp_cat_short_name_error">Required</small>
                    </div>
                </div>

                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <select name="status" id="status" class="form-control">
                            <option value="">Select</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <small class="text-danger d-none" id="status_error">Required</small>
                    </div>
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