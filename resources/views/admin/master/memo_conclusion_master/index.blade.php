@extends('admin.layouts.master')

@section('title', 'Memo Conclusion Master')

@push('styles')
{{-- Shared with Memo Type Master: same module, same components
     (docs/new-design-index-page.md §7). The .mtm-* prefix is the memo module's,
     not one page's. --}}
<link rel="stylesheet"
    href="{{ asset('css/memo-masters-admin.css') }}?v={{ @filemtime(public_path('css/memo-masters-admin.css')) }}">
@endpush

@section('setup_content')
<div class="container-fluid mtm-master-page">
    <x-breadcrum title="Memo Conclusion Master" :showBack="false">
        <button type="button" id="showConclusionAlert"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Memo Conclusion</span>
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
                    <button type="button" class="btn programme-dt-btn-columns" id="mcnColumnsToggle"
                        data-bs-toggle="modal" data-bs-target="#mcnColumnsModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="memoconclusionmaster-table"></div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                    </div>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="memoconclusionmaster-table"></div>
            </div>
        </div>
    </div>
</div>

{{-- Add / Edit share one modal: they look ALIKE and differ only in the title and
     the submit caption (§3c). --}}
<div class="modal fade mtm-modal" id="mcnFormModal" tabindex="-1" aria-labelledby="mcnFormModalLabel"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <form id="mcnForm" novalidate>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="id" id="mcn_id" value="">

                <div class="mtm-modal-header">
                    <h5 class="mtm-modal-title" id="mcnFormModalLabel">Add Memo Conclusion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="mtm-modal-body">
                    <div id="mcnFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mtm-field-card">
                        <div class="mtm-field">
                            <label for="mcn_discussion_name" class="mtm-field-label">
                                Conclusion Name <span class="mtm-req">*</span>
                            </label>
                            <input type="text" name="discussion_name" id="mcn_discussion_name"
                                class="mtm-control" maxlength="100" placeholder="eg. Warning issued"
                                autocomplete="off">
                            <small class="text-danger d-none mt-1" id="mcn_discussion_name_error">Conclusion Name is required</small>
                        </div>

                        <div class="mtm-field">
                            <label for="mcn_pt_discusion" class="mtm-field-label">PT Discussion</label>
                            <input type="text" name="pt_discusion" id="mcn_pt_discusion"
                                class="mtm-control" placeholder="Optional" autocomplete="off">
                            <small class="text-danger d-none mt-1" id="mcn_pt_discusion_error"></small>
                        </div>

                        <div class="mtm-field">
                            <label for="mcn_active_inactive" class="mtm-field-label">
                                Status <span class="mtm-req">*</span>
                            </label>
                            <select name="active_inactive" id="mcn_active_inactive" class="mtm-control">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="2">Inactive</option>
                            </select>
                            <small class="text-danger d-none mt-1" id="mcn_active_inactive_error">Status is required</small>
                        </div>
                    </div>
                </div>

                <div class="mtm-modal-footer">
                    <button type="button" class="btn mtm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mtm-btn-submit" id="mcnSubmit">Add Memo Conclusion</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade mtm-modal" id="mcnColumnsModal" tabindex="-1" aria-labelledby="mcnColumnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="mtm-modal-header">
                <h5 class="mtm-modal-title" id="mcnColumnsModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="mtm-modal-body">
                <div class="mtm-col-grid" id="mcnColumnsGrid"></div>
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
    const storeUrl = "{{ route('master.memo.conclusion.master.store') }}";
    const csrfToken = "{{ csrf_token() }}";
    const formModalEl = document.getElementById('mcnFormModal');

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
        if ($.fn.DataTable.isDataTable('#memoconclusionmaster-table')) {
            $('#memoconclusionmaster-table').DataTable().ajax.reload(null, false);
        }
    }

    function clearErrors() {
        $('#mcnFormAlert').addClass('d-none').removeClass('alert-danger').empty();
        $('#mcnForm small.text-danger').addClass('d-none');
        $('#mcnForm .mtm-control').removeClass('is-invalid');
    }

    function fieldError(id, message) {
        const $msg = $('#' + id + '_error');
        if (message) { $msg.text(message); }
        $msg.removeClass('d-none');
        $('#' + id).addClass('is-invalid');
    }

    function resetForm(mode) {
        clearErrors();
        $('#mcnForm')[0].reset();
        $('#mcn_id').val('');
        $('#mcnFormModalLabel').text(mode === 'edit' ? 'Edit Memo Conclusion' : 'Add Memo Conclusion');
        $('#mcnSubmit').text(mode === 'edit' ? 'Update Memo Conclusion' : 'Add Memo Conclusion');
    }

    $('#showConclusionAlert').on('click', function () {
        resetForm('add');
        showFormModal();
    });

    $(document).on('click', '.editshowConclusionAlert', function () {
        const $btn = $(this);
        resetForm('edit');

        $('#mcn_id').val($btn.data('pk'));
        $('#mcn_discussion_name').val($btn.data('discussion_name') || '');
        $('#mcn_pt_discusion').val($btn.data('pt_discusion') || '');

        // Rows are stored 1 = Active / 0 = Inactive (the grid switch writes those),
        // while this form posts 1 / 2 — so anything that is not Active maps to the
        // Inactive option, otherwise Status opened blank.
        $('#mcn_active_inactive').val(String($btn.data('active_inactive')) === '1' ? '1' : '2');

        showFormModal();
    });

    formModalEl.addEventListener('shown.bs.modal', function () {
        $('#mcn_discussion_name').trigger('focus');
    });

    $('#mcnForm').on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        let valid = true;
        if (!$('#mcn_discussion_name').val().trim()) {
            fieldError('mcn_discussion_name', 'Conclusion Name is required');
            valid = false;
        }
        if (!$('#mcn_active_inactive').val()) {
            fieldError('mcn_active_inactive', 'Status is required');
            valid = false;
        }
        if (!valid) { return; }

        const $submit = $('#mcnSubmit').prop('disabled', true);
        const isEdit = !!$('#mcn_id').val();

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
                    const id = 'mcn_' + key;
                    if (document.getElementById(id)) {
                        fieldError(id, body.errors[key][0]);
                    }
                });
            } else {
                $('#mcnFormAlert').removeClass('d-none').addClass('alert-danger')
                    .text(body.message || 'Server error or session expired');
            }
        })
        .catch(function () {
            $submit.prop('disabled', false);
            $('#mcnFormAlert').removeClass('d-none').addClass('alert-danger')
                .text('Server error or session expired');
        });
    });

    $(document).on('click', '.deleteBtn', function () {
        const btn = $(this);
        const url = btn.data('url');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This record will be permanently deleted!',
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
    $('#memoconclusionmaster-table').on('init.dt', function () {
        const table = $('#memoconclusionmaster-table').DataTable();
        const $grid = $('#mcnColumnsGrid').empty();

        table.columns().every(function (idx) {
            const title = $.trim($(this.header()).text()) || ('Column ' + (idx + 1));
            const visible = this.visible();
            $grid.append(
                '<label class="mtm-col-chip' + (visible ? ' is-checked' : '') + '" for="mcnColToggle' + idx + '">' +
                    '<input class="form-check-input mcn-col-toggle" type="checkbox" ' + (visible ? 'checked ' : '') +
                           'id="mcnColToggle' + idx + '" data-column="' + idx + '">' +
                    '<span>' + title + '</span>' +
                '</label>'
            );
        });

        $grid.off('change.mcn').on('change.mcn', '.mcn-col-toggle', function () {
            table.column($(this).data('column')).visible(this.checked);
            $(this).closest('.mtm-col-chip').toggleClass('is-checked', this.checked);
        });
    });
});
</script>
@endpush
