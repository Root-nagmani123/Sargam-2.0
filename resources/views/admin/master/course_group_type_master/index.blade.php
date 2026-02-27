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
            responsive: true,
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
