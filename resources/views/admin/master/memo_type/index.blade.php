@extends('admin.layouts.master')

@section('title', 'Memo Type Master')

@push('styles')
{{-- Module stylesheet (docs/new-design-index-page.md §7). --}}
<link rel="stylesheet"
    href="{{ asset('css/memo-masters-admin.css') }}?v={{ @filemtime(public_path('css/memo-masters-admin.css')) }}">
@endpush

@section('setup_content')
<div class="container-fluid mtm-master-page">
    <x-breadcrum title="Memo Type Master" :showBack="false">
        <button type="button" id="showMemoAlert"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Memo Type</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="datatables">
        <div class="card mtm-dt-card border-0 shadow-sm rounded-1 overflow-hidden">
            <div class="card-body p-3 p-md-4">

                {{-- Toolbar (§2). Both slots are filled by datatable-global-ui.js:
                     it moves the DataTables search box into .programme-dt-search and
                     the pager + count into .programme-dt-footer below. --}}
                <div class="programme-dt-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2 mb-4">
                    <button type="button" class="btn programme-dt-btn-columns" id="mtmColumnsToggle"
                        data-bs-toggle="modal" data-bs-target="#mtmColumnsModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="memotypemaster-table"></div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                    </div>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="memotypemaster-table"></div>
            </div>
        </div>
    </div>
</div>

{{-- Add / Edit share one modal: they look ALIKE and differ only in the title,
     the submit caption and whether a document already exists (§3c). --}}
<div class="modal fade mtm-modal" id="mtmFormModal" tabindex="-1" aria-labelledby="mtmFormModalLabel"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <form id="mtmForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="pk" id="mtm_pk" value="">

                <div class="mtm-modal-header">
                    <h5 class="mtm-modal-title" id="mtmFormModalLabel">Add Memo Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="mtm-modal-body">
                    <div id="mtmFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mtm-field-card">
                        <div class="mtm-field">
                            <label for="mtm_memo_type_name" class="mtm-field-label">
                                Memo Type Name <span class="mtm-req">*</span>
                            </label>
                            <input type="text" name="memo_type_name" id="mtm_memo_type_name"
                                class="mtm-control" maxlength="100" placeholder="eg. Show Cause"
                                autocomplete="off">
                            <small class="text-danger d-none mt-1" id="mtm_memo_type_name_error">Memo Type Name is required</small>
                        </div>

                        <div class="mtm-field">
                            <label for="mtm_memo_doc_upload" class="mtm-field-label" id="mtm_doc_label">Upload Document</label>
                            <input type="file" name="memo_doc_upload" id="mtm_memo_doc_upload"
                                class="mtm-control" accept=".pdf,.doc,.docx">
                            <small class="text-muted d-block mt-1">Accepted formats: .pdf, .doc, .docx (max 2 MB).</small>
                            <small class="text-danger d-none mt-1" id="mtm_memo_doc_upload_error"></small>
                            <a href="#" target="_blank" rel="noopener" class="mtm-existing-doc d-none" id="mtm_existing_doc">
                                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                                <span>View existing document</span>
                            </a>
                        </div>

                        <div class="mtm-field">
                            <label for="mtm_active_inactive" class="mtm-field-label">
                                Status <span class="mtm-req">*</span>
                            </label>
                            <select name="active_inactive" id="mtm_active_inactive" class="mtm-control">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="2">Inactive</option>
                            </select>
                            <small class="text-danger d-none mt-1" id="mtm_active_inactive_error">Status is required</small>
                        </div>
                    </div>
                </div>

                <div class="mtm-modal-footer">
                    <button type="button" class="btn mtm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mtm-btn-submit" id="mtmSubmit">Add Memo Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade mtm-modal" id="mtmColumnsModal" tabindex="-1" aria-labelledby="mtmColumnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="mtm-modal-header">
                <h5 class="mtm-modal-title" id="mtmColumnsModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="mtm-modal-body">
                <div class="mtm-col-grid" id="mtmColumnsGrid"></div>
            </div>
            <div class="mtm-modal-footer">
                <button type="button" class="btn mtm-btn-cancel" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}

<script>
$(function () {
    const storeUrl = "{{ route('master.memo.type.master.store') }}";
    const csrfToken = "{{ csrf_token() }}";
    const formModalEl = document.getElementById('mtmFormModal');

    // The modals are appended to <body> so their backdrop stacks above the page
    // chrome rather than inside the card.
    document.querySelectorAll('.mtm-modal').forEach(function (el) {
        if (el.parentElement && el.parentElement !== document.body) {
            document.body.appendChild(el);
        }
    });

    function showFormModal() {
        bootstrap.Modal.getOrCreateInstance(formModalEl).show();
    }

    function hideFormModal() {
        bootstrap.Modal.getOrCreateInstance(formModalEl).hide();
    }

    function reloadTable() {
        if ($.fn.DataTable.isDataTable('#memotypemaster-table')) {
            $('#memotypemaster-table').DataTable().ajax.reload(null, false);
        }
    }

    function clearErrors() {
        $('#mtmFormAlert').addClass('d-none').removeClass('alert-danger').empty();
        $('#mtmForm small.text-danger').addClass('d-none');
        $('#mtmForm .mtm-control').removeClass('is-invalid');
    }

    function fieldError(id, message) {
        const $msg = $('#' + id + '_error');
        if (message) { $msg.text(message); }
        $msg.removeClass('d-none');
        $('#' + id).addClass('is-invalid');
    }

    function resetForm(mode) {
        clearErrors();
        $('#mtmForm')[0].reset();
        $('#mtm_pk').val('');
        $('#mtm_existing_doc').addClass('d-none').attr('href', '#');
        $('#mtmFormModalLabel').text(mode === 'edit' ? 'Edit Memo Type' : 'Add Memo Type');
        $('#mtmSubmit').text(mode === 'edit' ? 'Update Memo Type' : 'Add Memo Type');
        $('#mtm_doc_label').text(mode === 'edit' ? 'Replace Document' : 'Upload Document');
    }

    $('#showMemoAlert').on('click', function () {
        resetForm('add');
        showFormModal();
    });

    $(document).on('click', '.editMemo', function () {
        const $btn = $(this);
        resetForm('edit');

        $('#mtm_pk').val($btn.data('pk'));
        $('#mtm_memo_type_name').val($btn.data('name') || '');

        // The grid's switch writes 0 for inactive while this form posts 2
        // (store() validates in:1,2), so anything that is not Active maps to
        // Inactive — otherwise a row deactivated from the grid opened with an
        // empty Status and the user had to re-pick it to save.
        $('#mtm_active_inactive').val(String($btn.data('status')) === '1' ? '1' : '2');

        const file = $btn.data('file');
        if (file) {
            $('#mtm_existing_doc').attr('href', file).removeClass('d-none');
        }

        showFormModal();
    });

    formModalEl.addEventListener('shown.bs.modal', function () {
        $('#mtm_memo_type_name').trigger('focus');
    });

    $('#mtmForm').on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        let valid = true;
        if (!$('#mtm_memo_type_name').val().trim()) {
            fieldError('mtm_memo_type_name', 'Memo Type Name is required');
            valid = false;
        }
        if (!$('#mtm_active_inactive').val()) {
            fieldError('mtm_active_inactive', 'Status is required');
            valid = false;
        }
        if (!valid) { return; }

        const $submit = $('#mtmSubmit').prop('disabled', true);
        const isEdit = !!$('#mtm_pk').val();

        fetch(storeUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: new FormData(this)
        })
        .then(res => res.json().then(body => ({ ok: res.ok, body })))
        .then(({ ok, body }) => {
            $submit.prop('disabled', false);

            if (ok && body.status) {
                hideFormModal();
                reloadTable();
                Swal.fire(isEdit ? 'Updated!' : 'Success', body.message, 'success');
                return;
            }

            // 422 → per-field messages; anything else → one alert in the modal.
            if (body.errors) {
                Object.keys(body.errors).forEach(function (key) {
                    const id = 'mtm_' + key;
                    if (document.getElementById(id)) {
                        fieldError(id, body.errors[key][0]);
                    }
                });
            } else {
                $('#mtmFormAlert').removeClass('d-none').addClass('alert-danger')
                    .text(body.message || 'Server error or session expired');
            }
        })
        .catch(function () {
            $submit.prop('disabled', false);
            $('#mtmFormAlert').removeClass('d-none').addClass('alert-danger')
                .text('Server error or session expired');
        });
    });

    $(document).on('click', '.deleteBtn', function () {
        const btn = $(this);
        const url = btn.data('url');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This record is permanently deleted',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (!result.isConfirmed) { return; }

            $.ajax({
                url: url,
                type: 'POST',
                data: { _method: 'DELETE', _token: csrfToken },
                beforeSend: function () { btn.prop('disabled', true); },
                success: function (res) {
                    if (res.status) {
                        Swal.fire('Deleted!', res.message, 'success');
                        reloadTable();
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
        });
    });

    // Column visibility chips, built from the live table once it exists.
    $('#memotypemaster-table').on('init.dt', function () {
        const table = $('#memotypemaster-table').DataTable();
        const $grid = $('#mtmColumnsGrid').empty();

        table.columns().every(function (idx) {
            const title = $.trim($(this.header()).text()) || ('Column ' + (idx + 1));
            const visible = this.visible();
            $grid.append(
                '<label class="mtm-col-chip' + (visible ? ' is-checked' : '') + '" for="mtmColToggle' + idx + '">' +
                    '<input class="form-check-input mtm-col-toggle" type="checkbox" ' + (visible ? 'checked ' : '') +
                           'id="mtmColToggle' + idx + '" data-column="' + idx + '">' +
                    '<span>' + title + '</span>' +
                '</label>'
            );
        });

        $grid.off('change.mtm').on('change.mtm', '.mtm-col-toggle', function () {
            table.column($(this).data('column')).visible(this.checked);
            $(this).closest('.mtm-col-chip').toggleClass('is-checked', this.checked);
        });
    });
});
</script>
@endpush
