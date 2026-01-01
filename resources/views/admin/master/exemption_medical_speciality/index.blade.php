@extends('admin.layouts.master')

@section('title', 'Exemption Medical Speciality Master')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Exemption Medical Speciality Master"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Exemption Medical Speciality Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <button id="showAlert" class="btn btn-primary">+ Add Speciality</button>
                                <!-- <a href="{{route('master.exemption.medical.speciality.create')}}"
                                    class="btn btn-primary">+ Add Speciality</a> -->
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">

                        <div class="table-responsive">
                            <table class="table" id="exemption_med_spec_mst">
                                <thead>
                                    <tr>
                                        <th class="col">#</th>
                                        <th class="col">Speciality Name</th>
                                        <th class="col">Created Date</th>
                                        <th class="col">Status</th>
                                        <th class="col">Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
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
        let table = $('#exemption_med_spec_mst').DataTable({
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
                text: "This status is changed permanently!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes',
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
        title: '<strong>Add Exemption Medical Speciality</strong>',
        html: `
            <form id="exemptionmedicalSpecilityForm"
                  action="{{ route('master.exemption.medical.speciality.store') }}"
                  method="POST">

                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="pk" value="${pk}">

                <!--speciality_name-->
                <div class="row mb-2 align-items-center">
                    <label class="col-auto col-form-label fw-semibold">
                        Speciality Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text"
                               name="speciality_name"
                               id="speciality_name"
                               class="form-control">
                        <small class="text-danger d-none" id="speciality_name_error1">
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
            const speciality_name       = popup.querySelector('#speciality_name');
            const status                = popup.querySelector('#status');
            const speciality_nameErr    = popup.querySelector('#speciality_name_error1');
            const statusErr             = popup.querySelector('#status_error');

            // Reset validation state
            [speciality_name, status].forEach(el => el.classList.remove('is-invalid'));
            [speciality_nameErr, statusErr].forEach(el => el.classList.add('d-none'));

            let isValid = true;
            if (!speciality_name.value.trim()){
                speciality_name.classList.add('is-invalid');
                speciality_nameErr.classList.remove('d-none');
                isValid = false;
            }

            if (!status.value){
                status.classList.add('is-invalid');
                statusErr.classList.remove('d-none');
                isValid = false;
            }

            return isValid;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('exemptionmedicalSpecilityForm').submit();
        }
    });
});
</script>
<script>
    $(document).on('click', '.edit-btn', function() {

        let pk = $(this).data('id');
         let speciality_name = $(this).data('speciality_name');
         let status = $(this).data('active_inactive');

        let url = "{{ route('master.exemption.medical.speciality.MedSpecExupdate') }}";

        Swal.fire({
            title: '<strong>Edit Course Group Type</strong>',
            html: `
            <form id="exemptionMedicaleditForm"
                  action="${url}"
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
                               name="speciality_name"
                               id="speciality_name"
                               class="form-control" value="${speciality_name}">
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

            const speciality_name   = popup.querySelector('#speciality_name');
            const status     = popup.querySelector('#status');

            const speciality_nameErr    = popup.querySelector('#speciality_name_error');
            const statusErr  = popup.querySelector('#status_error');

            // Reset validation state
            [speciality_name, status].forEach(el => el.classList.remove('is-invalid'));
            [speciality_nameErr, statusErr].forEach(el => el.classList.add('d-none'));

            let isValid = true;

            if (!speciality_name.value.trim()) {
                speciality_name.classList.add('is-invalid');
                speciality_nameErr.classList.remove('d-none');
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
                document.getElementById('exemptionMedicaleditForm').submit();
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