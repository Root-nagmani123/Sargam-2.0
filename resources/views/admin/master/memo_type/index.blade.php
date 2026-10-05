@extends('admin.layouts.master')

@section('title', 'Memo Type Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="Memo Type Master" :showBack="false">
        {{-- #showMemoAlert opens the SweetAlert Add form below. --}}
        <button type="button" id="showMemoAlert"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Memo Type</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#memoTypeColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="memotypemaster-table"></div>
                </div>
            </div>

            {{-- Search, pager and "Showing N of M items" are relocated into the
                 slots by public/js/datatable-global-ui.js. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="memotypemaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="memoTypeColumnVisibilityModal" tabindex="-1"
     aria-labelledby="memoTypeColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="memoTypeColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="memoTypeColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>

<script>
$(function () {
    MstAdmin.columnVisibility({
        table: '#memotypemaster-table',
        grid: '#memoTypeColumnToggleGrid',
        storageKey: 'sargam.memoTypeMaster.hiddenCols.{{ auth()->id() ?? 'guest' }}'
    });
});

/*
 * Add / Edit stay SweetAlert forms posting to the same JSON store route; they
 * now wear the master modal chrome — .mst-modal scopes the field card, labels,
 * controls and the Cancel · Submit pair from public/css/master-admin.css.
 * The Status select stays a plain .form-select: a Select2 dropdown would open
 * outside the SweetAlert popup, under its z-index and its focus trap.
 */
const memoTypeSwalChrome = {
    buttonsStyling: false,
    reverseButtons: true,
    customClass: {
        popup: 'mst-modal',
        title: 'text-start fs-5 fw-bold border-bottom pb-3',
        htmlContainer: 'text-start fs-6 m-0 px-4 pt-3',
        actions: 'w-100 justify-content-end gap-2 px-4',
        confirmButton: 'btn mst-btn-submit px-4',
        cancelButton: 'btn mst-btn-cancel px-4',
        validationMessage: 'mx-4'
    }
};

function memoTypeEsc(value) {
    return String(value === undefined || value === null ? '' : value).replace(/[&<>"']/g, function (ch) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
}

// One field card for both forms; Edit passes the saved values.
function memoTypeFormHtml(record) {
    const isEdit = !!record;
    // A row switched off from the grid is stored as 0 — it is Inactive too.
    const status = isEdit ? String(record.status ?? '') : '';
    const fileUrl = isEdit ? (record.fileUrl || '') : '';

    return `
        <form id="memoTypeForm" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            ${isEdit ? `<input type="hidden" name="pk" value="${memoTypeEsc(record.pk)}">` : ''}

            <div class="mst-field-card">
                <div class="mb-3">
                    <label for="memo_type_name" class="mst-form-label d-block">
                        Memo Type Name <span class="mst-req" aria-hidden="true">*</span>
                    </label>
                    <input type="text" name="memo_type_name" id="memo_type_name"
                           class="form-control mst-control" maxlength="100"
                           placeholder="Enter memo type name" required aria-required="true"
                           value="${isEdit ? memoTypeEsc(record.name) : ''}">
                    <small class="text-danger d-block mt-1 d-none" id="memo_type_name_error">Required</small>
                </div>

                <div class="mb-3">
                    <label for="memo_doc_upload" class="mst-form-label d-block">
                        ${isEdit ? 'Replace Document' : 'Upload Document'}
                    </label>
                    <input type="file" name="memo_doc_upload" id="memo_doc_upload"
                           class="form-control mst-control" accept=".pdf,.doc,.docx"
                           aria-describedby="memo_doc_upload_hint">
                    <small class="text-muted d-block mt-1" id="memo_doc_upload_hint">PDF or Word (.doc, .docx), up to 2 MB.</small>
                    <small class="text-danger d-block mt-1 d-none" id="memo_doc_upload_error"></small>
                    ${fileUrl ? `<a href="${memoTypeEsc(fileUrl)}" target="_blank" rel="noopener"
                                    class="d-inline-flex align-items-center gap-1 mt-2 text-primary fw-medium">
                                    <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                                    <span>View Existing Document</span>
                                 </a>` : ''}
                </div>

                <div class="mb-0">
                    <label for="active_inactive" class="mst-form-label d-block">
                        Status <span class="mst-req" aria-hidden="true">*</span>
                    </label>
                    <select name="active_inactive" id="active_inactive" class="form-select mst-control"
                            required aria-required="true">
                        <option value="">Select Status</option>
                        <option value="1" ${status === '1' ? 'selected' : ''}>Active</option>
                        <option value="2" ${status !== '' && status !== '1' ? 'selected' : ''}>Inactive</option>
                    </select>
                    <small class="text-danger d-block mt-1 d-none" id="active_inactive_error">Required</small>
                </div>
            </div>
        </form>
    `;
}

// Client-side required check + POST to the store route. Server field errors
// are shown under their fields and keep the popup open.
function memoTypeSubmit() {
    const popup = Swal.getPopup();

    const name = popup.querySelector('#memo_type_name');
    const status = popup.querySelector('#active_inactive');

    const nameError = popup.querySelector('#memo_type_name_error');
    const fileError = popup.querySelector('#memo_doc_upload_error');
    const statusError = popup.querySelector('#active_inactive_error');

    // Reset errors
    [nameError, fileError, statusError].forEach(e => e.classList.add('d-none'));
    nameError.textContent = 'Required';
    statusError.textContent = 'Required';

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
        return false; // prevent submit
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
        if (error && error.errors) {
            if (error.errors.memo_type_name) {
                nameError.textContent = error.errors.memo_type_name[0];
                nameError.classList.remove('d-none');
            }
            if (error.errors.memo_doc_upload) {
                fileError.textContent = error.errors.memo_doc_upload[0];
                fileError.classList.remove('d-none');
            }
            if (error.errors.active_inactive) {
                statusError.textContent = error.errors.active_inactive[0];
                statusError.classList.remove('d-none');
            }
        } else {
            Swal.showValidationMessage((error && error.message) || 'Server error or session expired');
        }
        return false; // keep the popup open so the errors can be read
    });
}

function memoTypeReloadTable() {
    if ($.fn.DataTable.isDataTable('#memotypemaster-table')) {
        $('#memotypemaster-table').DataTable().ajax.reload(null, false);
    }
}

document.getElementById('showMemoAlert').addEventListener('click', function () {
    Swal.fire(Object.assign({}, memoTypeSwalChrome, {
        title: 'Add Memo Type',
        html: memoTypeFormHtml(null),
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: memoTypeSubmit
    })).then(result => {
        if (result.isConfirmed && result.value?.status) {
            Swal.fire('Success', result.value.message, 'success');
            memoTypeReloadTable();
        }
    });
});

$(document).on('click', '.editMemo', function () {
    const $btn = $(this);

    Swal.fire(Object.assign({}, memoTypeSwalChrome, {
        title: 'Edit Memo Type',
        html: memoTypeFormHtml({
            pk: $btn.data('pk'),
            name: $btn.attr('data-name'),
            status: $btn.data('status'),
            // data-file already carries the full asset('storage/…') URL.
            fileUrl: $btn.attr('data-file')
        }),
        showCancelButton: true,
        confirmButtonText: 'Update',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: memoTypeSubmit
    })).then(result => {
        if (result.isConfirmed && result.value?.status) {
            Swal.fire('Updated!', result.value.message, 'success');
            memoTypeReloadTable();
        }
    });
});

$(document).on('click', '.deleteBtn', function (e) {
    e.preventDefault();

    const btn = $(this);
    const url = btn.data('url');

    Swal.fire({
        title: 'Are you sure?',
        text: 'This record is permanent deleted',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
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

                        // Reload DataTable without page reload
                        memoTypeReloadTable();
                    } else {
                        Swal.fire('Error!', res.message, 'error');
                        btn.prop('disabled', false);
                    }
                },
                error: function (xhr) {
                    Swal.fire('Error!', (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.', 'error');
                    btn.prop('disabled', false);
                }
            });

        }
    });
});
</script>
@endpush
