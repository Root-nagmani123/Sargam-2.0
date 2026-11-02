@extends('admin.layouts.master')

@section('title', 'MDO Duty Type')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="MDO Duty Type" :showBack="false">
        {{-- .add-btn opens the SweetAlert Add form (second script below). --}}
        <button type="button"
                class="btn btn-primary add-btn d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add MDO Duty Type</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#mdoDutyTypeColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="mdodutytypemaster-table"></div>
                </div>
            </div>

            {{-- Search, pager and "Showing N of M items" are relocated into the
                 slots by public/js/datatable-global-ui.js. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="mdodutytypemaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="mdoDutyTypeColumnVisibilityModal" tabindex="-1"
     aria-labelledby="mdoDutyTypeColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="mdoDutyTypeColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="mdoDutyTypeColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Form modal used by openModalWithUrl() (loads the _form partial by AJAX) -->
<div class="modal fade mst-modal" id="dutyTypeModal" tabindex="-1" aria-labelledby="dutyTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold mb-0" id="dutyTypeModalLabel">MDO Duty Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Form content is loaded here via fetch. --}}
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
            table: '#mdodutytypemaster-table',
            grid: '#mdoDutyTypeColumnToggleGrid',
            storageKey: 'sargam.mdoDutyType.hiddenCols.{{ auth()->id() ?? 'guest' }}'
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const createBtn = document.getElementById('openCreateDutyType');
        const editLinks = document.querySelectorAll('.openEditDutyType');

        function openModalWithUrl(url, title) {
            const modalEl = document.getElementById('dutyTypeModal');
            const modalTitle = modalEl.querySelector('.modal-title');
            const modalBody = modalEl.querySelector('.modal-body');
            modalTitle.textContent = title || 'MDO Duty Type';
            modalBody.innerHTML = '<div class="text-center p-4">Loading...</div>';

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(() => {
                    modalBody.innerHTML = '<div class="text-danger">Failed to load form.</div>';
                });

            const bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();
        }

        if (createBtn) {
            createBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openModalWithUrl(this.getAttribute('href'), 'Create MDO Duty Type');
            });
        }

        editLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                openModalWithUrl(this.getAttribute('href'), 'Edit MDO Duty Type');
            });

            // Handle AJAX form submit inside modal
            document.getElementById('dutyTypeModal')?.addEventListener('submit', function(e) {
                const form = e.target;
                if (form && form.tagName === 'FORM') {
                    e.preventDefault();
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) submitBtn.disabled = true;

                    fetch(form.action, {
                            method: form.method || 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: new FormData(form)
                        })
                        .then(async (res) => {
                            if (res.ok) {
                                // Try to parse JSON; fallback to text
                                const ct = res.headers.get('content-type') || '';
                                if (ct.includes('application/json')) {
                                    const data = await res.json();
                                    if (data.success || data.status === true) {
                                        // Update table without full reload
                                        updateTableAfterSave(data);
                                        bootstrap.Modal.getInstance(document.getElementById('dutyTypeModal'))?.hide();
                                        return;
                                    }
                                }
                                // Non-JSON success fallback
                                updateTableAfterSave(null);
                                bootstrap.Modal.getInstance(document.getElementById('dutyTypeModal'))?.hide();
                            } else if (res.status === 422) {
                                // Validation errors: re-render returned HTML into modal
                                const html = await res.text();
                                const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                                modalBody.innerHTML = html;
                            } else {
                                const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                                modalBody.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger">Save failed. Please try again.</div>');
                            }
                        })
                        .catch(() => {
                            const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                            modalBody.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger">Network error. Please try again.</div>');
                        })
                        .finally(() => {
                            if (submitBtn) submitBtn.disabled = false;
                        });
                }
            });
        });
    });

    function buildEditUrl(encryptedPk) {
        return `${window.location.origin}/master/mdo_duty_type/edit/${encodeURIComponent(encryptedPk)}`;
    }

    function escapeHtml(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/[&<>"']/g, function(ch) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                '\'': '&#39;'
            } [ch]);
        });
    }

    function interceptEditLink(e) {
        e.preventDefault();
        openModalWithUrl(this.getAttribute('href'), 'Edit MDO Duty Type');
    }

    function updateTableAfterSave(payload) {
        // Reload DataTable after create/update
        if (typeof $.fn.DataTable !== 'undefined') {
            const table = $('#mdodutytypemaster-table').DataTable();
            if (table) {
                table.ajax.reload(null, false); // false = don't reset pagination
            }
        }
    }


    $(document).on('change', '.plain-status-toggle', function() {
        let checkbox = $(this);
        let pk = checkbox.data('id');
        let active_inactive = checkbox.is(':checked') ? 1 : 0;
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
            if (result.isConfirmed){
                $.ajax({
                    url: "{{ route('master.mdo_duty_type.status') }}", // route
                    type: "POST",
                    data: {
                        pk: pk,
                        active_inactive: active_inactive,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {

                        $('#mdodutytypemaster-table').DataTable().ajax.reload(null, false);

                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    // Refused (e.g. a system duty type): put the switch back.
                    error: function(xhr) {
                        checkbox.prop('checked', !active_inactive);
                        Swal.fire({
                            icon: 'error',
                            title: 'Not changed',
                            text: (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong'
                        });
                    }
                });

            } else {
                // revert checkbox
                checkbox.prop('checked', !active_inactive);
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
            if (result.isConfirmed){
                $.ajax({
                    url: "{{ route('master.mdo_duty_type.delete') }}", // route
                    type: "POST",
                    data: {
                        id: pk,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $('#mdodutytypemaster-table').DataTable().ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            // delete() redirects, so the followed response has no JSON message.
                            text: (response && response.message) || 'MDO Duty Type deleted successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Not deleted',
                            text: (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong'
                        });
                    }
                });

            }
            // Nothing to revert on Cancel: the old branch here reset a
            // `checkbox` that does not exist in this handler (ReferenceError).
        });
    });
</script>

<script>
/*
 * Add / Edit — SweetAlert forms posting to master.mdo_duty_type.store, now in
 * the master modal chrome (.mst-modal scopes the field card, labels, controls
 * and the Cancel · Submit pair from public/css/master-admin.css).
 *
 * This block used to end in a half-pasted copy of the first script (the
 * add-form error callback never closed), so the browser rejected the whole
 * <script> and neither Add nor Edit opened. The pasted duplicate — including a
 * second .plain-status-toggle handler that would now double-confirm — is gone;
 * the Add / Edit handlers are otherwise as they were.
 *
 * Status stays a plain .form-select: a Select2 dropdown would open outside the
 * SweetAlert popup, under its z-index and its focus trap.
 */
const mdoSwalChrome = {
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

// One field card for both forms; Edit passes the saved values.
function mdoDutyTypeFields(name, status) {
    status = (status === undefined || status === null) ? '' : String(status);
    return `
        <div class="mst-field-card">
            <div class="mb-3">
                <label for="mdo_duty_type_name" class="mst-form-label d-block">
                    Duty Type Name <span class="mst-req" aria-hidden="true">*</span>
                </label>
                <input type="text"
                       name="mdo_duty_type_name"
                       id="mdo_duty_type_name"
                       class="form-control mst-control"
                       maxlength="255"
                       placeholder="Enter duty type name"
                       required aria-required="true"
                       value="${escapeHtml(String(name === undefined || name === null ? '' : name))}">
                <small class="text-danger d-block mt-1 d-none" id="mdo_duty_type_name_error">
                    Duty Type Name is required
                </small>
            </div>

            <div class="mb-0">
                <label for="active_inactive" class="mst-form-label d-block">
                    Status <span class="mst-req" aria-hidden="true">*</span>
                </label>
                <select name="active_inactive"
                        id="active_inactive"
                        class="form-select mst-control"
                        required aria-required="true">
                    <option value="">Select Status</option>
                    <option value="1" ${status === '1' ? 'selected' : ''}>Active</option>
                    <option value="0" ${status === '0' ? 'selected' : ''}>Inactive</option>
                </select>
                <small class="text-danger d-block mt-1 d-none" id="active_inactive_error">
                    Status is required
                </small>
            </div>
        </div>
    `;
}

$(document).on('click', '.edit-btn', function () {
    let  pk = $(this).data('id');
    let  mdo_duty_type_name = $(this).attr('data-mdo_duty_type_name');
    let  active_inactive = $(this).data('active_inactive');
    let  url = "{{ route('master.mdo_duty_type.store') }}";

    Swal.fire(Object.assign({}, mdoSwalChrome, {
        title: 'Edit MDO Duty Type',
        html: `
            <form id="EditMDODutyTypeForm" novalidate>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="id" value="${escapeHtml(String(pk))}">
                ${mdoDutyTypeFields(mdo_duty_type_name, active_inactive)}
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Update',
        cancelButtonText: 'Cancel',
        focusConfirm: false,

        preConfirm: () => {
            const typeNameInput = Swal.getPopup().querySelector('#mdo_duty_type_name');
            const active_inactiveInput = Swal.getPopup().querySelector('#active_inactive');
            const errorMsg = Swal.getPopup().querySelector('#mdo_duty_type_name_error');
            const active_inactiveMsg = Swal.getPopup().querySelector('#active_inactive_error');

            typeNameInput.classList.remove('is-invalid');
            errorMsg.classList.add('d-none');

            active_inactiveInput.classList.remove('is-invalid');
            active_inactiveMsg.classList.add('d-none');

            if (!typeNameInput.value.trim()) {
                typeNameInput.classList.add('is-invalid');
                errorMsg.classList.remove('d-none');
                return false;
            }

            if (!active_inactiveInput.value.trim()) {
                active_inactiveInput.classList.add('is-invalid');
                active_inactiveMsg.classList.remove('d-none');
                return false;
            }

            return {
                id: pk,
                mdo_duty_type_name: typeNameInput.value.trim(),
                active_inactive: active_inactiveInput.value.trim(),
                _token: "{{ csrf_token() }}"
            };
        }
    })).then((result) => {

        if (result.isConfirmed) {

            $.ajax({
                url: url,
                type: "POST",
                data: result.value,
                beforeSend: function () {
                    Swal.showLoading();
                },
                success: function (response) {

                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: response.message ?? 'Record updated successfully',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    updateTableAfterSave(null); // #mdodutytypemaster-table only
                },
                error: function (xhr) {

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message ?? 'Something went wrong'
                    });
                }
            });
        }
    });
});

// add form//

$(document).on('click', '.add-btn', function () {

    const url  = "{{ route('master.mdo_duty_type.store') }}";
    const csrf = "{{ csrf_token() }}";

    Swal.fire(Object.assign({}, mdoSwalChrome, {
        title: 'Add MDO Duty Type',
        html: `
            <form id="AddMDODutyTypeForm" novalidate>
                <input type="hidden" name="_token" value="${csrf}">
                ${mdoDutyTypeFields('', '')}
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        focusConfirm: false,

        preConfirm: () => {

            const form = $('#AddMDODutyTypeForm');
            const nameInput   = form.find('[name="mdo_duty_type_name"]');
            const statusInput = form.find('[name="active_inactive"]');

            let valid = true;

            form.find('.is-invalid').removeClass('is-invalid');
            $('#mdo_duty_type_name_error, #active_inactive_error').addClass('d-none');

            if (!nameInput.val().trim()) {
                nameInput.addClass('is-invalid');
                $('#mdo_duty_type_name_error').removeClass('d-none');
                valid = false;
            }

            if (!statusInput.val()) {
                statusInput.addClass('is-invalid');
                $('#active_inactive_error').removeClass('d-none');
                valid = false;
            }

            if (!valid) return false;

            return {
                mdo_duty_type_name: nameInput.val().trim(),
                active_inactive: statusInput.val(),
                _token: csrf
            };
        }

    })).then((result) => {

        if (!result.isConfirmed) return;

        $.ajax({
            url: url,
            type: "POST",
            data: result.value,
            beforeSend: () => Swal.showLoading(),

            success: (response) => {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: response.message ?? 'Record added successfully',
                    timer: 1500,
                    showConfirmButton: false
                });

                updateTableAfterSave(null); // #mdodutytypemaster-table only
            },

            error: (xhr) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message ?? 'Something went wrong'
                });
            }
        });
    });
});
</script>
@endpush
