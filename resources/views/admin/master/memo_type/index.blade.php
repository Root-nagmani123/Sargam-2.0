@extends('admin.layouts.master')

@section('title', 'Memo Type Master')

@section('setup_content')
<div class="container-fluid">
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Memo Type Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <!-- Add Group Mapping -->
                                <!-- <a href="javascript:void(0);" id="showMemoAlert"
                                    class="btn btn-primary d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">add</i>
                                    Add Memo Type
                                </a> -->
                                <button id="showMemoAlert" class="btn btn-primary">
                                    Add Memo Type
                                </button>
                            </div>
                        </div>
                    </div>
                    <hr>

                    {!! $dataTable->table(['class' => 'table']) !!}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}


<script>
document.getElementById('showMemoAlert').addEventListener('click', function () {

    Swal.fire({
        title: '<strong>Add Memo Type</strong>',
        html: `
            <form id="memoTypeForm" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <!-- Memo Type Name -->
                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Memo Type Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text" name="memo_type_name" id="memo_type_name" class="form-control">
                        <small class="text-danger d-none" id="memo_type_name_error">Required</small>
                    </div>
                </div>

                <!-- Upload Document -->
                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Upload Document
                    </label>
                    <div class="col">
                        <input type="file" name="memo_doc_upload" id="memo_doc_upload"
                               class="form-control" accept=".pdf,.doc,.docx">
                        <small class="text-danger d-none" id="memo_doc_upload_error"></small>
                    </div>
                </div>

                <!-- Status -->
                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <select name="active_inactive" id="active_inactive" class="form-control">
                            <option value="">Select</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                        <small class="text-danger d-none" id="active_inactive_error">Required</small>
                    </div>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        allowOutsideClick: () => !Swal.isLoading(),

        preConfirm: () => {
            const popup = Swal.getPopup();

            const name = popup.querySelector('#memo_type_name');
            const status = popup.querySelector('#active_inactive');

            const nameError = popup.querySelector('#memo_type_name_error');
            const statusError = popup.querySelector('#active_inactive_error');

            // Reset errors
            [nameError, statusError].forEach(e => e.classList.add('d-none'));

            let isValid = true;

            if (!name.value.trim()) {
                nameError.classList.remove('d-none');
                isValid = false;
            }

            if (!status.value) {
                statusError.classList.remove('d-none');
                isValid = false;
            }

            if (!isValid) {
                return false; // ⛔ prevent submit
            }

            const formData = new FormData(popup.querySelector('#memoTypeForm'));

            Swal.showLoading();

            return fetch("{{ route('master.memo.type.master.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .catch(error => {
                if (error.errors) {
                    if (error.errors.memo_type_name) {
                        nameError.textContent = error.errors.memo_type_name[0];
                        nameError.classList.remove('d-none');
                    }
                    if (error.errors.active_inactive) {
                        statusError.textContent = error.errors.active_inactive[0];
                        statusError.classList.remove('d-none');
                    }
                } else {
                    Swal.showValidationMessage('Server error or session expired');
                }
            });
        }
    }).then(result => {
        if (result.isConfirmed && result.value?.status) {
            Swal.fire('Success', result.value.message, 'success');

            // Reload DataTable
            if ($.fn.DataTable.isDataTable('#memotypemaster-table')) {
                $('#memotypemaster-table').DataTable().ajax.reload(null, false);
            }
        }
    });
});

$(document).on('click', '.editMemo', function () {

    const pk      = $(this).data('pk');
    const name    = $(this).data('name');
    const status  = $(this).data('status');
    const fileUrl = $(this).data('file');
    const BASE_URL = "{{ url('/') }}";

    Swal.fire({
        title: '<strong>Edit Memo Type</strong>',
        html: `
            <form id="memoTypeForm" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="pk" value="${pk}">

                <!-- Memo Type Name -->
                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Memo Type Name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text" name="memo_type_name" id="memo_type_name"
                               class="form-control" value="${name}">
                        <small class="text-danger d-none" id="memo_type_name_error">Required</small>
                    </div>
                </div>

                <!-- Upload Document -->
                <div class="row mb-2">
                <label class="col-auto fw-semibold">Replace Document</label>
                <div class="col">
                    <input type="file" name="memo_doc_upload" id="memo_doc_upload"
                           class="form-control" accept=".pdf,.doc,.docx">
                    
                    <small class="text-danger d-none" id="memo_doc_upload_error"></small>
                    ${fileUrl ? `<div class="mt-1">
                                    <a href="${BASE_URL}/${fileUrl}" target="_blank" class="text-primary">
                                        View Existing Document
                                    </a>
                                  </div>` : ''}
                </div>
        
                <!-- Status -->
                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <select name="active_inactive" id="active_inactive" class="form-control">
                            <option value="">Select</option>
                            <option value="1" ${status == 1 ? 'selected' : ''}>Active</option>
                            <option value="2" ${status == 2 ? 'selected' : ''}>Inactive</option>
                        </select>
                        <small class="text-danger d-none" id="active_inactive_error">Required</small>
                    </div>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Update',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        allowOutsideClick: () => !Swal.isLoading(),

        preConfirm: () => {
                const popup = Swal.getPopup();

                const nameInput  = popup.querySelector('#memo_type_name');
                const fileInput  = popup.querySelector('#memo_doc_upload');
                const statusInput = popup.querySelector('#active_inactive');

                const nameError   = popup.querySelector('#memo_type_name_error');
                const fileError   = popup.querySelector('#memo_doc_upload_error');
                const statusError = popup.querySelector('#active_inactive_error');

                // Hide all errors first
                [nameError, fileError, statusError].forEach(e => e.classList.add('d-none'));

                let valid = true;

                // Memo name required
                if (!nameInput.value.trim()) {
                    nameError.classList.remove('d-none');
                    valid = false;
                }

                
                if (!statusInput.value) {
                    statusError.classList.remove('d-none');
                    valid = false;
                }

                if (!valid) return false;

                const formData = new FormData(popup.querySelector('#memoTypeForm'));

                Swal.showLoading();

                return fetch("{{ route('master.memo.type.master.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .catch(() => {
                    Swal.showValidationMessage('Server error occurred');
                });
            }

    }).then(result => {
        if (result.isConfirmed && result.value?.status) {
            Swal.fire('Updated!', result.value.message, 'success');

            if ($.fn.DataTable.isDataTable('#memotypemaster-table')) {
                $('#memotypemaster-table').DataTable().ajax.reload(null, false);
            }
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
        text: 'This record is permanent deleted',
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

@endpush