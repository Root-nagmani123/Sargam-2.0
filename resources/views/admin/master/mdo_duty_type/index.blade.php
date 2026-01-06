@extends('admin.layouts.master')

@section('title', 'MDO Duty Type')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="MDO Duty Type"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <!-- left column empty or header title above -->
                            <h4>MDO Duty Type</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <!-- Add Group Mapping -->
                                <a href="{{route('master.mdo_duty_type.create')}}" id="openCreateDutyType"
                                    class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#mdoDutyTypeModal">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">add</i>
                                    Add MDO Duty Type
                                </a>
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

        Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure? You want to deactivate this item?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, deactivate',
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
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                });

            } else {
                // revert checkbox
                checkbox.prop('checked', !active_inactive);
            }
        });
    });
</script>

<script>
    $(document).on('click', '.edit-btn', function() {
        let pk = $(this).data('id');
        //alert(pk);
        let mdo_duty_type_name = $(this).data('mdo_duty_type_name');
        let url = "{{ route('master.mdo_duty_type.store') }}";

        Swal.fire({
            title: '<strong>Edit MDO Duty Type</strong>',
            html: `
            <form id="EditMDODutyTypeForm"
                  action="${url}"
              method="POST">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="id" value="${pk}">
            <div class="row mb-1 align-items-center">
                <label class="col-auto col-form-label fw-semibold">
                    Type Name <span class="text-danger">*</span>
                </label>
                <div class="col">
                    <input type="text"
                           name="mdo_duty_type_name"
                           id="mdo_duty_type_name"
                           class="form-control"
                           value="${mdo_duty_type_name}">
                    <small class="text-danger d-none" id="mdo_duty_type_name_error">
                        Type Name is required
                    </small>
                </div>
            </div>
        </form>
        `,
            showCancelButton: true,
            confirmButtonText: 'Update',
            focusConfirm: false,

            preConfirm: () => {
                const typeNameInput = Swal.getPopup().querySelector('#mdo_duty_type_name');
                const errorMsg = Swal.getPopup().querySelector('#mdo_duty_type_name_error');

                typeNameInput.classList.remove('is-invalid');
                errorMsg.classList.add('d-none');

                if (!typeNameInput.value.trim()) {
                    typeNameInput.classList.add('is-invalid');
                    errorMsg.classList.remove('d-none');
                    return false;
                }
                return true;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('EditMDODutyTypeForm').submit();
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
<!-- Modal -->
<div class="modal fade" id="dutyTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">MDO Duty Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Form content will be loaded here via fetch -->
            </div>
        </div>
    </div>
</div>
@endpush