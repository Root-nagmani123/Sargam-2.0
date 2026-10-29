@extends('admin.layouts.master')

@section('title', 'Memo Conclusion Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="Memo Conclusion Master" :showBack="false">
        {{-- #showConclusionAlert opens the SweetAlert Add form below. --}}
        <button type="button" id="showConclusionAlert"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Memo Conclusion</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#memoConclusionColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="memoconclusionmaster-table"></div>
                </div>
            </div>

            {{-- Search, pager and "Showing N of M items" are relocated into the
                 slots by public/js/datatable-global-ui.js. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="memoconclusionmaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="memoConclusionColumnVisibilityModal" tabindex="-1"
     aria-labelledby="memoConclusionColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="memoConclusionColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="memoConclusionColumnToggleGrid"></div>
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
        table: '#memoconclusionmaster-table',
        grid: '#memoConclusionColumnToggleGrid',
        storageKey: 'sargam.memoConclusionMaster.hiddenCols.{{ auth()->id() ?? 'guest' }}'
    });
});

/*
 * Add / Edit stay SweetAlert forms posting to the same JSON store route; they
 * now wear the master modal chrome — .mst-modal scopes the field card, labels,
 * controls and the Cancel · Submit pair from public/css/master-admin.css.
 * The Status select stays a plain .form-select: a Select2 dropdown would open
 * outside the SweetAlert popup, under its z-index and its focus trap.
 */
const conclusionSwalChrome = {
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

function conclusionEsc(value) {
    return String(value === undefined || value === null ? '' : value).replace(/[&<>"']/g, function (ch) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
}

/*
 * One field card for both forms; Edit passes the saved values.
 * Inactive posts 0: store() saves `active_inactive ? 1 : 0`, so the old "2"
 * was truthy and silently saved the record as Active. 0 is also what the grid
 * switch writes, so a deactivated row re-opens as Inactive.
 */
function conclusionFormHtml(record) {
    const isEdit = !!record;
    const status = isEdit ? String(record.status ?? '') : '';

    return `
        <form id="conclusionForm" novalidate>
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            ${isEdit ? `<input type="hidden" name="id" value="${conclusionEsc(record.pk)}">` : ''}

            <div class="mst-field-card">
                <div class="mb-3">
                    <label for="discussion_name" class="mst-form-label d-block">
                        Conclusion Name <span class="mst-req" aria-hidden="true">*</span>
                    </label>
                    <input type="text" name="discussion_name" id="discussion_name"
                           class="form-control mst-control" maxlength="100"
                           placeholder="Enter conclusion name" required aria-required="true"
                           value="${isEdit ? conclusionEsc(record.name) : ''}">
                    <small class="text-danger d-block mt-1 d-none" id="discussion_name_error">Required</small>
                </div>

                <div class="mb-3">
                    <label for="pt_discusion" class="mst-form-label d-block">PT Discussion</label>
                    <input type="text" name="pt_discusion" id="pt_discusion"
                           class="form-control mst-control" placeholder="Enter PT discussion"
                           value="${isEdit ? conclusionEsc(record.pt) : ''}">
                </div>

                <div class="mb-0">
                    <label for="active_inactive" class="mst-form-label d-block">
                        Status <span class="mst-req" aria-hidden="true">*</span>
                    </label>
                    <select name="active_inactive" id="active_inactive" class="form-select mst-control"
                            required aria-required="true">
                        <option value="">Select Status</option>
                        <option value="1" ${status === '1' ? 'selected' : ''}>Active</option>
                        <option value="0" ${status !== '' && status !== '1' ? 'selected' : ''}>Inactive</option>
                    </select>
                    <small class="text-danger d-block mt-1 d-none" id="active_inactive_error">Required</small>
                </div>
            </div>
        </form>
    `;
}

// Client-side required check + POST to the store route. Server field errors
// are shown under their fields and keep the popup open.
function conclusionSubmit() {
    const popup = Swal.getPopup();

    const discussion = popup.querySelector('#discussion_name');
    const status = popup.querySelector('#active_inactive');

    const discussionError = popup.querySelector('#discussion_name_error');
    const statusError = popup.querySelector('#active_inactive_error');

    // reset errors
    [discussionError, statusError].forEach(e => e.classList.add('d-none'));
    discussionError.textContent = 'Required';
    statusError.textContent = 'Required';

    let isValid = true;

    if (!discussion.value.trim()) {
        discussionError.classList.remove('d-none');
        isValid = false;
    }

    if (!status.value) {
        statusError.classList.remove('d-none');
        isValid = false;
    }

    if (!isValid) {
        return false;
    }

    const formData = new FormData(popup.querySelector('#conclusionForm'));

    Swal.showLoading();

    return fetch("{{ route('master.memo.conclusion.master.store') }}", {
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
            if (error.errors.discussion_name) {
                discussionError.textContent = error.errors.discussion_name[0];
                discussionError.classList.remove('d-none');
            }
            if (error.errors.active_inactive) {
                statusError.textContent = error.errors.active_inactive[0];
                statusError.classList.remove('d-none');
            }
            if (error.errors.pt_discusion) {
                Swal.showValidationMessage(error.errors.pt_discusion[0]);
            }
        } else {
            Swal.showValidationMessage((error && error.message) || 'Server error or session expired');
        }
        return false; // keep the popup open so the errors can be read
    });
}

function conclusionReloadTable() {
    if ($.fn.DataTable.isDataTable('#memoconclusionmaster-table')) {
        $('#memoconclusionmaster-table').DataTable().ajax.reload(null, false);
    }
}

document.getElementById('showConclusionAlert').addEventListener('click', function () {
    Swal.fire(Object.assign({}, conclusionSwalChrome, {
        title: 'Add Memo Conclusion',
        html: conclusionFormHtml(null),
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: conclusionSubmit
    })).then(result => {
        if (result.isConfirmed && result.value?.status) {
            Swal.fire('Success', result.value.message, 'success');
            conclusionReloadTable();
        }
    });
});

$(document).on('click', '.editshowConclusionAlert', function () {
    const $btn = $(this);

    Swal.fire(Object.assign({}, conclusionSwalChrome, {
        title: 'Edit Memo Conclusion',
        html: conclusionFormHtml({
            pk: $btn.data('pk'),
            name: $btn.attr('data-discussion_name'),
            pt: $btn.attr('data-pt_discusion'),
            status: $btn.data('active_inactive')
        }),
        showCancelButton: true,
        confirmButtonText: 'Update',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: conclusionSubmit
    })).then(result => {
        if (result.isConfirmed && result.value?.status) {
            Swal.fire('Success', result.value.message, 'success');
            conclusionReloadTable();
        }
    });
});

$(document).on('click', '.deleteBtn', function () {

    const url = $(this).data('url');

    Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone!',
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
                success: function (res) {
                    if (res.status) {
                        Swal.fire('Deleted!', res.message, 'success');

                        // Reload DataTable only
                        conclusionReloadTable();
                    } else {
                        Swal.fire('Error!', res.message, 'error');
                    }
                },
                error: function (xhr) {
                    Swal.fire('Error!', (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.', 'error');
                }
            });

        }
    });
});
</script>
@endpush
