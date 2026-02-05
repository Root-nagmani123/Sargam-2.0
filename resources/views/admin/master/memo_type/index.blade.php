@extends('admin.layouts.master')

@section('title', 'Memo Type Master')

@section('setup_content')
<style>
/* Memo Type Master - responsive (mobile/tablet only, desktop unchanged) */
@media (max-width: 991.98px) {
    .memo-type-index .datatables .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    .memo-type-index .datatables #memotypemaster-table {
        min-width: 400px;
    }
    .memo-type-index .datatables #memotypemaster-table th,
    .memo-type-index .datatables #memotypemaster-table td {
        padding: 8px 10px;
        font-size: 0.9rem;
    }
}
@media (max-width: 767.98px) {
    /* Card + table tightening */
    .memo-type-index .datatables .card-body {
        padding: 1rem !important;
    }
    .memo-type-index .datatables #memotypemaster-table th,
    .memo-type-index .datatables #memotypemaster-table td {
        padding: 6px 8px;
        font-size: 0.85rem;
    }

    /* Header: title + Add button alignment on mobile/tablet */
    .memo-type-index .memo-type-header-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }
    .memo-type-index .memo-type-header-row .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .memo-type-index .memo-type-header-row .d-flex.justify-content-end {
        justify-content: stretch !important;
    }
    .memo-type-index .memo-type-header-row .add-btn {
        width: 100%;
        justify-content: center;
    }

    /* DataTables: Show entries + Search spacing for this page */
    .memo-type-index .dataTables_length,
    .memo-type-index .dataTables_filter {
        margin-bottom: 0.5rem;
        width: 100%;
    }

    /* SweetAlert form: stack label + field on small tablets/phones */
    body:has(.memo-type-index) .swal2-container #memoTypeForm .row {
        flex-direction: column;
        align-items: stretch;
    }
    body:has(.memo-type-index) .swal2-container #memoTypeForm .col-auto {
        flex: 0 0 auto;
        margin-bottom: 0.25rem;
    }
}
@media (max-width: 575.98px) {
    .memo-type-index.container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    .memo-type-index .datatables .card-body {
        padding: 0.75rem !important;
    }
    .memo-type-index .datatables .table-responsive {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .memo-type-index .datatables #memotypemaster-table th,
    .memo-type-index .datatables #memotypemaster-table td {
        padding: 6px 8px;
        font-size: 0.8125rem;
    }

    /* DataTables controls: stack label + field nicely */
    .memo-type-index .dataTables_length label,
    .memo-type-index .dataTables_filter label {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
        font-size: 0.8125rem;
    }
    .memo-type-index .dataTables_length select {
        width: 100%;
        max-width: 100%;
        min-height: 38px;
        padding: 0.25rem 0.5rem;
        font-size: 0.8125rem;
    }
    .memo-type-index .dataTables_filter input {
        width: 100%;
        max-width: 100%;
        min-height: 38px;
        padding: 0.25rem 0.5rem;
        font-size: 0.8125rem;
    }

    /* SweetAlert popup responsive (scoped to this page via :has) */
    body:has(.memo-type-index) .swal2-container .swal2-popup {
        margin: 0.5rem;
        max-width: calc(100vw - 1rem);
        width: calc(100vw - 1rem) !important;
        padding: 1rem 0.75rem 0.75rem;
    }
    body:has(.memo-type-index) .swal2-container .swal2-title {
        font-size: 1.05rem;
        margin-bottom: 0.75rem;
    }
    body:has(.memo-type-index) .swal2-container .swal2-html-container {
        padding-left: 0.25rem;
        padding-right: 0.25rem;
        margin: 0;
    }

    /* SweetAlert Add/Edit Memo form – clean mobile layout */
    body:has(.memo-type-index) .swal2-container #memoTypeForm .row {
        flex-direction: column;
        align-items: stretch;
        margin-bottom: 0.6rem;
    }
    body:has(.memo-type-index) .swal2-container #memoTypeForm .col-auto {
        flex: 0 0 auto;
        margin-bottom: 0.25rem;
    }
    body:has(.memo-type-index) .swal2-container #memoTypeForm label {
        font-size: 0.875rem;
        margin-bottom: 0;
    }
    body:has(.memo-type-index) .swal2-container #memoTypeForm input[type="text"],
    body:has(.memo-type-index) .swal2-container #memoTypeForm input[type="file"],
    body:has(.memo-type-index) .swal2-container #memoTypeForm select {
        width: 100%;
        max-width: 100%;
        min-height: 42px;
        font-size: 0.875rem;
    }
    body:has(.memo-type-index) .swal2-container #memoTypeForm small {
        font-size: 0.75rem;
    }

    /* SweetAlert actions: buttons refined for phones */
    body:has(.memo-type-index) .swal2-container .swal2-actions {
        margin-top: 0.75rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    body:has(.memo-type-index) .swal2-container .swal2-actions .swal2-confirm,
    body:has(.memo-type-index) .swal2-container .swal2-actions .swal2-cancel {
        width: 100%;
        min-height: 40px;
        font-size: 0.9rem;
    }
}
@media (max-width: 375px) {
    .memo-type-index.container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .memo-type-index .datatables .card-body {
        padding: 0.5rem !important;
    }
    .memo-type-index .memo-type-header-row h4 {
        font-size: 1.1rem;
    }
}
</style>
<div class="container-fluid memo-type-index">
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row memo-type-header-row">
                        <div class="col-6">
                            <h4>Memo Type Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <button id="showMemoAlert" class="btn btn-primary add-btn">
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