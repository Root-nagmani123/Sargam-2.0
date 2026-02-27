@extends('admin.layouts.master')

@section('title', 'Memo Type Master')

@section('setup_content')
<div class="container-fluid px-3 px-md-4 px-lg-5 py-3 py-md-4 memo-type-index">
    <x-breadcrum title="Memo Type Master" />
    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden border-start border-4 border-primary admin-card">
            <div class="card-body p-4 p-lg-5">
                <section class="row align-items-center mb-4 g-3 row-gap-2" role="region" aria-labelledby="memoTypeHeading">
                    <div class="col-12 col-md-6 col-lg-4">
                        <h1 id="memoTypeHeading" class="h4 fw-bold mb-2 mb-md-0 d-flex align-items-center gap-2">
                            <span class="rounded-2 p-2 bg-primary bg-opacity-10">
                                <i class="material-icons material-symbols-rounded text-primary fs-4">category</i>
                            </span>
                            <span>Memo Type Master</span>
                        </h1>
                        <p class="mb-0 small text-body-secondary mt-1">Manage memo type configurations</p>
                    </div>
                    <div class="col-12 col-md-6 col-lg-8">
                        <div class="d-flex flex-wrap justify-content-md-end">
                            <button id="showMemoAlert" type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold shadow-sm" aria-label="Add memo type">
                                <i class="material-icons material-symbols-rounded fs-5">add</i>
                                <span>Add Memo Type</span>
                            </button>
                        </div>
                    </div>
                </section>
                <div class="border-top pt-4 mt-2"></div>
                <div class="table-responsive overflow-x-auto">
                    {!! $dataTable->table(['class' => 'table table-striped table-hover align-middle mb-0 w-100']) !!}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
<script>
(function() {
    // Ensure memo type table shows all data in single row (no responsive child rows)
    $(function() {
        var $t = $('#memotypemaster-table');
        if ($.fn.DataTable && $t.length) {
            var check = setInterval(function() {
                if ($.fn.DataTable.isDataTable($t)) {
                    clearInterval(check);
                    try {
                        var api = $t.DataTable();
                        if (api.responsive && typeof api.responsive.disable === 'function') {
                            api.responsive.disable();
                        }
                    } catch (e) {}
                }
            }, 50);
            setTimeout(function() { clearInterval(check); }, 3000);
        }
    });
})();
</script>
<script>
document.getElementById('showMemoAlert').addEventListener('click', function () {

    Swal.fire({
        title: '<strong>Add Memo Type</strong>',
        html: `
            <form id="memoTypeForm" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <!-- Memo Type Name -->
                <div class="row g-2 mb-2 align-items-center">
                    <label class="col-12 col-sm-auto form-label fw-semibold mb-0">
                        Memo Type Name <span class="text-danger">*</span>
                    </label>
                    <div class="col-12 col-sm">
                        <input type="text" name="memo_type_name" id="memo_type_name" class="form-control form-control-sm">
                        <small class="text-danger d-none" id="memo_type_name_error">Required</small>
                    </div>
                </div>

                <!-- Upload Document -->
                <div class="row g-2 mb-2 align-items-center">
                    <label class="col-12 col-sm-auto form-label fw-semibold mb-0">
                        Upload Document
                    </label>
                    <div class="col-12 col-sm">
                        <input type="file" name="memo_doc_upload" id="memo_doc_upload"
                               class="form-control form-control-sm" accept=".pdf,.doc,.docx">
                        <small class="text-danger d-none" id="memo_doc_upload_error"></small>
                    </div>
                </div>

                <!-- Status -->
                <div class="row g-2 mb-2 align-items-center">
                    <label class="col-12 col-sm-auto form-label fw-semibold mb-0">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col-12 col-sm">
                        <select name="active_inactive" id="active_inactive" class="form-select form-select-sm">
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

    Swal.fire({
        title: '<strong>Edit Memo Type</strong>',
        html: `
            <form id="memoTypeForm" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="pk" value="${pk}">

                <!-- Memo Type Name -->
                <div class="row g-2 mb-2 align-items-center">
                    <label class="col-12 col-sm-auto form-label fw-semibold mb-0">
                        Memo Type Name <span class="text-danger">*</span>
                    </label>
                    <div class="col-12 col-sm">
                        <input type="text" name="memo_type_name" id="memo_type_name"
                               class="form-control form-control-sm" value="${name}">
                        <small class="text-danger d-none" id="memo_type_name_error">Required</small>
                    </div>
                </div>

                <!-- Upload Document -->
                <div class="row g-2 mb-2 align-items-center">
                    <label class="col-12 col-sm-auto form-label fw-semibold mb-0">Replace Document</label>
                    <div class="col-12 col-sm">
                        <input type="file" name="memo_doc_upload" id="memo_doc_upload"
                               class="form-control form-control-sm" accept=".pdf,.doc,.docx">
                        <small class="text-danger d-none" id="memo_doc_upload_error"></small>
                        ${fileUrl ? `<div class="mt-1"><a href="${fileUrl}" target="_blank" class="text-primary text-decoration-none">View Existing Document</a></div>` : ''}
                    </div>
                </div>

                <!-- Status -->
                <div class="row g-2 mb-2 align-items-center">
                    <label class="col-12 col-sm-auto form-label fw-semibold mb-0">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col-12 col-sm">
                        <select name="active_inactive" id="active_inactive" class="form-select form-select-sm">
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