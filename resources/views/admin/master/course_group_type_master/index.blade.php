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
                                    <th class="col">S.No.</th>
                                    <th class="col">Type Name</th>
                                    <th class="col">Status</th>
                                    <th class="col">Action</th>
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

<div class="modal fade" id="courseGroupTypeModal" tabindex="-1" aria-labelledby="courseGroupTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('master.course.group.type.store') }}" method="POST" id="courseGroupTypeForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="courseGroupTypeModalLabel">Add Course Group Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="courseGroupTypeId">
                    <div class="mb-3">
                        <label for="courseGroupTypeName" class="form-label">
                            Type Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="type_name" id="courseGroupTypeName" class="form-control" placeholder="Type Name" required>
                        <div class="invalid-feedback" id="courseGroupTypeNameError"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary d-inline-flex align-items-center gap-1" type="submit" id="saveCourseGroupTypeBtn">
                        <i class="material-icons menu-icon">save</i>
                        <span id="saveCourseGroupTypeBtnText">Save</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function() {
        const modalElement = document.getElementById('courseGroupTypeModal');
        const courseGroupTypeModal = new bootstrap.Modal(modalElement);
        const form = $('#courseGroupTypeForm');
        const idInput = $('#courseGroupTypeId');
        const typeNameInput = $('#courseGroupTypeName');
        const typeNameError = $('#courseGroupTypeNameError');
        const modalTitle = $('#courseGroupTypeModalLabel');
        const saveBtn = $('#saveCourseGroupTypeBtn');
        const saveBtnText = $('#saveCourseGroupTypeBtnText');

        function resetCourseGroupTypeForm() {
            form[0].reset();
            idInput.val('');
            typeNameInput.removeClass('is-invalid');
            typeNameError.text('');
            modalTitle.text('Add Course Group Type');
            saveBtnText.text('Save');
        }

        const table = $('#coursegrouptype').DataTable({
            processing: true,
            serverSide: true,
            searching: true, 
            ajax: {
                url: "{{ route('master.course.group.type.grouptypeview') }}",
                data: function(d) {
                    d.pk = $('#pk').val();
                    d.active_inactive = $('#active_inactive').val();
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

        $('#showAlert').on('click', function() {
            resetCourseGroupTypeForm();
            courseGroupTypeModal.show();
        });

        $(document).on('click', '.edit-btn', function() {
            resetCourseGroupTypeForm();

            const pk = $(this).data('id');
            const typeName = $(this).data('type-name');

            idInput.val(pk);
            typeNameInput.val(typeName);
            modalTitle.text('Edit Course Group Type');
            saveBtnText.text('Update');

            courseGroupTypeModal.show();
        });

        form.on('submit', function(e) {
            e.preventDefault();

            typeNameInput.removeClass('is-invalid');
            typeNameError.text('');

            const typeName = (typeNameInput.val() || '').trim();
            if (!typeName) {
                typeNameInput.addClass('is-invalid');
                typeNameError.text('Type Name is required.');
                return;
            }

            typeNameInput.val(typeName);
            saveBtn.prop('disabled', true);

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.status === true) {
                        courseGroupTypeModal.hide();
                        table.ajax.reload(null, false);

                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Unable to save course group type.'
                        });
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors || {};
                        const errorMessage = errors.type_name ? errors.type_name[0] : 'Type Name is required.';
                        typeNameInput.addClass('is-invalid');
                        typeNameError.text(errorMessage);
                        return;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: xhr.responseJSON?.message || 'Something went wrong!'
                    });
                },
                complete: function() {
                    saveBtn.prop('disabled', false);
                }
            });
        });

        $(modalElement).on('hidden.bs.modal', function() {
            resetCourseGroupTypeForm();
        });

        $(document).on('change', '.plain-status-toggle', function() {
            const checkbox = $(this);
            const previousState = !checkbox.is(':checked');
            const pk = checkbox.data('id');
            const active_inactive = checkbox.is(':checked') ? 1 : 0;

            const actionText = active_inactive ? 'activate' : 'deactivate';
            const confirmBtnText = active_inactive ? 'Yes, activate' : 'Yes, deactivate';
            const confirmBtnColor = active_inactive ? '#28a745' : '#d33';

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
            const pk = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: 'This record will be permanently deleted!',
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
                        icon: 'info',
                        title: 'Cancelled',
                        text: 'Delete has been cancelled.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
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
