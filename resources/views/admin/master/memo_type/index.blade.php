@extends('admin.layouts.master')

@section('title', 'Memo Type Master')

@section('setup_content')
<style>
/* ─── Hide DataTable default controls ─── */
#memotypemaster-table_wrapper .dataTables_length,
#memotypemaster-table_wrapper .dataTables_filter,
#memotypemaster-table_wrapper .dataTables_info { display: none !important; }

/* ─── Paginate styling ─── */
#memotypemaster-table_wrapper .dataTables_paginate { margin-top: 0 !important; display: flex; align-items: center; gap: 2px; }
#memotypemaster-table_wrapper .paginate_button { border: none !important; background: transparent !important; padding: 5px 10px; border-radius: 4px; font-size: 0.8125rem; cursor: pointer; color: #495057 !important; }
#memotypemaster-table_wrapper .paginate_button:hover:not(.disabled) { background: #f1f3f5 !important; }
#memotypemaster-table_wrapper .paginate_button.current,
#memotypemaster-table_wrapper .paginate_button.current:hover { background: #1b3a5c !important; color: #fff !important; border-radius: 4px; }
#memotypemaster-table_wrapper .paginate_button.disabled { opacity: 0.35; cursor: default; }
#memotypemaster-table_wrapper .ellipsis { padding: 5px 4px; color: #adb5bd; }

/* ─── Custom Search ─── */
.mt-search-wrap { position: relative; width: 260px; }
.mt-search-wrap .form-control { border-radius: 8px; border: 1px solid #dee2e6; padding-left: 38px; font-size: 0.875rem; }
.mt-search-wrap .mt-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #adb5bd; font-size: 18px; pointer-events: none; }

/* ─── Status Badges ─── */
.mt-badge-active { display: inline-block; background-color: #d1fae5; color: #065f46; border-radius: 50rem; padding: 4px 14px; font-size: 0.8rem; font-weight: 600; }
.mt-badge-inactive { display: inline-block; background-color: #fee2e2; color: #991b1b; border-radius: 50rem; padding: 4px 14px; font-size: 0.8rem; font-weight: 600; }

/* ─── Table ─── */
#memotypemaster-table thead th { background-color: #f8f9fa; font-size: 0.8125rem; font-weight: 600; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 12px 16px; white-space: nowrap; }
#memotypemaster-table tbody td { font-size: 0.875rem; padding: 12px 16px; vertical-align: middle; border-bottom: 1px solid #f1f3f5; color: #212529; }
#memotypemaster-table tbody tr:hover td { background-color: #fafbfc; }

/* ─── Action Buttons ─── */
.mt-action-btn { background: none; border: none; padding: 3px 5px; cursor: pointer; line-height: 1; display: inline-flex; align-items: center; text-decoration: none; }
.mt-action-btn .material-symbols-rounded { font-size: 20px; }
.mt-action-btn:hover { opacity: 0.7; }

/* ─── Modal ─── */
.mt-modal .modal-content { border: 1.5px dashed #6ea8fe; border-radius: 12px; }
.mt-modal .modal-header { border-bottom: none; padding: 20px 24px 6px; }
.mt-modal .modal-body   { padding: 8px 24px 16px; }
.mt-modal .modal-footer { border-top: none; padding: 0 24px 20px; }
.mt-modal .modal-title  { font-size: 1rem; font-weight: 700; color: #1b3a5c; }
.mt-modal .form-label   { font-size: 0.875rem; font-weight: 500; margin-bottom: 4px; color: #495057; }
.mt-modal .form-control,
.mt-modal .form-select  { border-radius: 6px; font-size: 0.875rem; border: 1px solid #dee2e6; padding: 8px 12px; }
.mt-modal .form-control:focus,
.mt-modal .form-select:focus { border-color: #6ea8fe; box-shadow: 0 0 0 3px rgba(110,168,254,0.15); }
</style>

<div class="container-fluid">
    <x-breadcrum title="Memo Type Master" subtitle="List of memo types">
        <button type="button" data-bs-toggle="modal" data-bs-target="#addMtModal"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Memo Type</span>
        </button>
    </x-breadcrum>

    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4">

                {{-- Custom Search --}}
                <div class="d-flex justify-content-end mb-3">
                    <div class="mt-search-wrap">
                        <i class="material-icons material-symbols-rounded mt-search-icon">search</i>
                        <input type="text" id="mtSearch" class="form-control" placeholder="Search">
                    </div>
                </div>

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table mb-0']) !!}
                </div>

                {{-- Bottom: pagination (moved here via JS) + per-page + total --}}
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div id="mtPaginationCell"></div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="text-muted small">Showing</span>
                        <select id="mtPerPage" class="form-select form-select-sm" style="width:78px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                        <span id="mtTotalInfo" class="text-muted small">of 0 items</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ ADD MODAL ═══════════════════ --}}
<div class="modal fade mt-modal" id="addMtModal" tabindex="-1"
    aria-labelledby="addMtModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMtModalLabel">Add Memo Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addMtForm" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="mb-3">
                        <label for="add_mt_name" class="form-label">Memo Type Name <span class="text-danger">*</span></label>
                        <input type="text" id="add_mt_name" name="memo_type_name" class="form-control" placeholder="eg. General Medicine">
                        <div class="invalid-feedback">Memo Type Name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_mt_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="add_mt_status" name="active_inactive" class="form-select">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                        <div class="invalid-feedback">Status is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_mt_file" class="form-label">Attachment</label>
                        <input type="file" id="add_mt_file" name="memo_doc_upload" class="form-control" accept=".pdf,.doc,.docx">
                        <div class="invalid-feedback" id="add_mt_file_error"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn fw-semibold px-4" id="addMtSubmit"
                    style="background:#1b3a5c;color:#fff;border-color:#1b3a5c;">Create Memo Type</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ EDIT MODAL ═══════════════════ --}}
<div class="modal fade mt-modal" id="editMtModal" tabindex="-1"
    aria-labelledby="editMtModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMtModalLabel">Edit Memo Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editMtForm" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" id="edit_mt_pk">
                    <div class="mb-3">
                        <label for="edit_mt_name" class="form-label">Memo Type Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit_mt_name" name="memo_type_name" class="form-control" placeholder="eg. General Medicine">
                        <div class="invalid-feedback">Memo Type Name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_mt_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="edit_mt_status" name="active_inactive" class="form-select">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                        <div class="invalid-feedback">Status is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_mt_file" class="form-label">Replace Document</label>
                        <input type="file" id="edit_mt_file" name="memo_doc_upload" class="form-control" accept=".pdf,.doc,.docx">
                        <div id="editMtExistingDoc" class="mt-1 d-none">
                            <a id="editMtFileLink" href="#" target="_blank" class="text-primary small">View Existing Document</a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn fw-semibold px-4" id="editMtSubmit"
                    style="background:#1b3a5c;color:#fff;border-color:#1b3a5c;">Update</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
$(function () {

    var table = $('#memotypemaster-table').DataTable();

    // ── Move DT pagination into custom bottom row ──
    var $paginate = $('#memotypemaster-table_wrapper .dataTables_paginate');
    if ($paginate.length) {
        $paginate.appendTo('#mtPaginationCell');
    }

    // ── Draw event: update per-page select + total info ──
    $('#memotypemaster-table').on('draw.dt', function () {
        var info = table.page.info();
        $('#mtTotalInfo').text('of ' + info.recordsTotal + ' items');
        $('#mtPerPage').val(table.page.len());
    });

    // ── Custom Search ──
    $('#mtSearch').on('input', function () {
        clearTimeout(window._mtSearchTimer);
        var q = $(this).val();
        window._mtSearchTimer = setTimeout(function () { table.search(q).draw(); }, 350);
    });

    // ── Per-page change ──
    $('#mtPerPage').on('change', function () {
        table.page.len(parseInt($(this).val())).draw();
    });

    // ── Edit btn → populate and open Bootstrap modal ──
    $(document).on('click', '.editMemo', function () {
        var pk     = $(this).data('pk');
        var name   = $(this).data('name');
        var status = $(this).data('status');
        var file   = $(this).data('file');

        $('#edit_mt_pk').val(pk);
        $('#edit_mt_name').val(name).removeClass('is-invalid');
        $('#edit_mt_status').val(status).removeClass('is-invalid');
        $('#edit_mt_file').val('').removeClass('is-invalid');

        if (file) {
            $('#editMtFileLink').attr('href', file);
            $('#editMtExistingDoc').removeClass('d-none');
        } else {
            $('#editMtExistingDoc').addClass('d-none');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editMtModal')).show();
    });

    // ── ADD: submit (same fetch logic as original) ──
    $('#addMtSubmit').on('click', function () {
        var $name   = $('#add_mt_name');
        var $status = $('#add_mt_status');
        var valid   = true;

        $name.removeClass('is-invalid');
        $status.removeClass('is-invalid');
        if (!$name.val().trim()) { $name.addClass('is-invalid');   valid = false; }
        if (!$status.val())      { $status.addClass('is-invalid'); valid = false; }
        if (!valid) return;

        var $btn     = $(this).prop('disabled', true).text('Saving...');
        var formData = new FormData(document.getElementById('addMtForm'));

        fetch("{{ route('master.memo.type.master.store') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function (response) {
            if (!response.ok) {
                return response.json().then(function (err) { return Promise.reject(err); });
            }
            return response.json();
        })
        .then(function (data) {
            $btn.prop('disabled', false).text('Create Memo Type');
            if (data.status) {
                bootstrap.Modal.getInstance(document.getElementById('addMtModal')).hide();
                Swal.fire({ icon: 'success', title: 'Success', text: data.message, timer: 1500, showConfirmButton: false });
                table.ajax.reload(null, false);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message ?? 'Something went wrong' });
            }
        })
        .catch(function (error) {
            $btn.prop('disabled', false).text('Create Memo Type');
            if (error && error.errors) {
                if (error.errors.memo_type_name) {
                    $name.addClass('is-invalid').next('.invalid-feedback').text(error.errors.memo_type_name[0]);
                }
                if (error.errors.active_inactive) {
                    $status.addClass('is-invalid').next('.invalid-feedback').text(error.errors.active_inactive[0]);
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Server error or session expired' });
            }
        });
    });

    document.getElementById('addMtModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addMtForm').reset();
        $('#addMtForm .form-control, #addMtForm .form-select').removeClass('is-invalid');
    });

    // ── EDIT: submit (same fetch logic as original) ──
    $('#editMtSubmit').on('click', function () {
        var $name   = $('#edit_mt_name');
        var $status = $('#edit_mt_status');
        var valid   = true;

        $name.removeClass('is-invalid');
        $status.removeClass('is-invalid');
        if (!$name.val().trim()) { $name.addClass('is-invalid');   valid = false; }
        if (!$status.val())      { $status.addClass('is-invalid'); valid = false; }
        if (!valid) return;

        var $btn     = $(this).prop('disabled', true).text('Saving...');
        var formData = new FormData(document.getElementById('editMtForm'));

        fetch("{{ route('master.memo.type.master.store') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            $btn.prop('disabled', false).text('Update');
            if (data.status) {
                bootstrap.Modal.getInstance(document.getElementById('editMtModal')).hide();
                Swal.fire({ icon: 'success', title: 'Updated!', text: data.message, timer: 1500, showConfirmButton: false });
                table.ajax.reload(null, false);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message ?? 'Something went wrong' });
            }
        })
        .catch(function () {
            $btn.prop('disabled', false).text('Update');
            Swal.fire({ icon: 'error', title: 'Error', text: 'Server error occurred' });
        });
    });

    document.getElementById('editMtModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('editMtForm').reset();
        $('#editMtForm .form-control, #editMtForm .form-select').removeClass('is-invalid');
        $('#editMtExistingDoc').addClass('d-none');
    });

    // ── Delete (same $.ajax as original) ──
    $(document).on('click', '.deleteBtn', function (e) {
        e.preventDefault();
        var btn = $(this);
        var url = btn.data('url');
        var pk  = btn.data('pk');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This record will be permanently deleted!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function () { btn.prop('disabled', true); },
                    success: function (res) {
                        if (res.status) {
                            Swal.fire({ icon: 'success', title: 'Deleted!', text: res.message, timer: 1500, showConfirmButton: false });
                            table.ajax.reload(null, false);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error!', text: res.message });
                            btn.prop('disabled', false);
                        }
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error!', text: 'Something went wrong.' });
                        btn.prop('disabled', false);
                    }
                });
            }
        });
    });

});
</script>

@if(session('success'))
<script>
    Swal.fire({ icon: 'success', title: 'Success', text: "{{ session('success') }}" });
</script>
@endif
@if(session('error'))
<script>
    Swal.fire({ icon: 'error', title: 'Error', text: "{{ session('error') }}" });
</script>
@endif
@endpush