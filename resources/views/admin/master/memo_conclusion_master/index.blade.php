@extends('admin.layouts.master')

@section('title', 'Memo Conclusion Master')

@section('setup_content')
<style>
/* ─── Hide DataTable default controls ─── */
#memoconclusionmaster-table_wrapper .dataTables_length,
#memoconclusionmaster-table_wrapper .dataTables_filter,
#memoconclusionmaster-table_wrapper .dataTables_info { display: none !important; }

/* ─── Paginate styling ─── */
#memoconclusionmaster-table_wrapper .dataTables_paginate { margin-top: 0 !important; display: flex; align-items: center; gap: 2px; }
#memoconclusionmaster-table_wrapper .paginate_button { border: none !important; background: transparent !important; padding: 5px 10px; border-radius: 4px; font-size: 0.8125rem; cursor: pointer; color: #495057 !important; }
#memoconclusionmaster-table_wrapper .paginate_button:hover:not(.disabled) { background: #f1f3f5 !important; }
#memoconclusionmaster-table_wrapper .paginate_button.current,
#memoconclusionmaster-table_wrapper .paginate_button.current:hover { background: #1b3a5c !important; color: #fff !important; border-radius: 4px; }
#memoconclusionmaster-table_wrapper .paginate_button.disabled { opacity: 0.35; cursor: default; }
#memoconclusionmaster-table_wrapper .ellipsis { padding: 5px 4px; color: #adb5bd; }

/* ─── Custom Search ─── */
.mc-search-wrap { position: relative; width: 260px; }
.mc-search-wrap .form-control { border-radius: 8px; border: 1px solid #dee2e6; padding-left: 38px; font-size: 0.875rem; }
.mc-search-wrap .mc-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #adb5bd; font-size: 18px; pointer-events: none; }

/* ─── Status Badges ─── */
.mc-badge-active { display: inline-block; background-color: #d1fae5; color: #065f46; border-radius: 10px !important; padding: 4px 14px; font-size: 0.8rem; font-weight: 600; }
.mc-badge-inactive { display: inline-block; background-color: #fee2e2; color: #991b1b; border-radius: 10px !important; padding: 4px 14px; font-size: 0.8rem; font-weight: 600; }

/* ─── Table ─── */
#memoconclusionmaster-table thead th { background-color: #f8f9fa; font-size: 0.8125rem; font-weight: 600; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 12px 16px; white-space: nowrap; }
#memoconclusionmaster-table tbody td { font-size: 0.875rem; padding: 12px 16px; vertical-align: middle; border-bottom: 1px solid #f1f3f5; color: #212529; }
#memoconclusionmaster-table tbody tr:hover td { background-color: #fafbfc; }

/* ─── Action Buttons ─── */
.mc-action-btn { background: none; border: none; padding: 3px 5px; cursor: pointer; line-height: 1; display: inline-flex; align-items: center; text-decoration: none; }
.mc-action-btn .material-symbols-rounded { font-size: 20px; }
.mc-action-btn:hover { opacity: 0.7; }

/* ─── Modal ─── */
.mc-modal .modal-content { border: 1.5px dashed #6ea8fe; border-radius: 12px; }
.mc-modal .modal-header { border-bottom: none; padding: 20px 24px 6px; }
.mc-modal .modal-body   { padding: 8px 24px 16px; }
.mc-modal .modal-footer { border-top: none; padding: 0 24px 20px; }
.mc-modal .modal-title  { font-size: 1rem; font-weight: 700; color: #1b3a5c; }
.mc-modal .form-label   { font-size: 0.875rem; font-weight: 500; margin-bottom: 4px; color: #495057; }
.mc-modal .form-control,
.mc-modal .form-select  { border-radius: 6px; font-size: 0.875rem; border: 1px solid #dee2e6; padding: 8px 12px; }
.mc-modal .form-control:focus,
.mc-modal .form-select:focus { border-color: #6ea8fe; box-shadow: 0 0 0 3px rgba(110,168,254,0.15); }
</style>

<div class="container-fluid">
    <x-breadcrum title="Memo Conclusion Master" subtitle="List of memo conclusions">
        <button type="button" data-bs-toggle="modal" data-bs-target="#addMcModal"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Memo Conclusion</span>
        </button>
    </x-breadcrum>

    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4">

                {{-- Custom Search --}}
                <div class="d-flex justify-content-end mb-3">
                    <div class="mc-search-wrap">
                        <i class="material-icons material-symbols-rounded mc-search-icon">search</i>
                        <input type="text" id="mcSearch" class="form-control" placeholder="Search">
                    </div>
                </div>

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table mb-0']) !!}
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div id="mcPaginationCell"></div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Showing</span>
                        <select id="mcPerPage" class="form-select form-select-sm" style="width:78px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                        <span id="mcTotalInfo" class="text-muted small">of 0 items</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ ADD MODAL ═══════════════════ --}}
<div class="modal fade mc-modal" id="addMcModal" tabindex="-1"
    aria-labelledby="addMcModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMcModalLabel">Add Memo Conclusion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addMcForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="mb-3">
                        <label for="add_mc_discussion_name" class="form-label">Discussion Name <span class="text-danger">*</span></label>
                        <input type="text" id="add_mc_discussion_name" name="discussion_name" class="form-control" placeholder="eg. General Meeting">
                        <div class="invalid-feedback">Discussion Name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_mc_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="add_mc_status" name="active_inactive" class="form-select">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                        <div class="invalid-feedback">Status is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_mc_pt_discusion" class="form-label">PT Discussion</label>
                        <input type="text" id="add_mc_pt_discusion" name="pt_discusion" class="form-control" placeholder="eg. Lorem Ipsum Dolor">
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn fw-semibold px-4" id="addMcSubmit"
                    style="background:#1b3a5c;color:#fff;border-color:#1b3a5c;">Create Memo Conclusion</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ EDIT MODAL ═══════════════════ --}}
<div class="modal fade mc-modal" id="editMcModal" tabindex="-1"
    aria-labelledby="editMcModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMcModalLabel">Edit Memo Conclusion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editMcForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" id="edit_mc_pk">
                    <div class="mb-3">
                        <label for="edit_mc_discussion_name" class="form-label">Discussion Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit_mc_discussion_name" name="discussion_name" class="form-control" placeholder="eg. General Meeting">
                        <div class="invalid-feedback">Discussion Name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_mc_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="edit_mc_status" name="active_inactive" class="form-select">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                        <div class="invalid-feedback">Status is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_mc_pt_discusion" class="form-label">PT Discussion</label>
                        <input type="text" id="edit_mc_pt_discusion" name="pt_discusion" class="form-control" placeholder="eg. Lorem Ipsum Dolor">
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn fw-semibold px-4" id="editMcSubmit"
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

    var table = $('#memoconclusionmaster-table').DataTable();

    // ── Move DT pagination into custom bottom row ──
    var $paginate = $('#memoconclusionmaster-table_wrapper .dataTables_paginate');
    if ($paginate.length) { $paginate.appendTo('#mcPaginationCell'); }

    // ── Draw event: update total info + per-page select ──
    $('#memoconclusionmaster-table').on('draw.dt', function () {
        var info = table.page.info();
        $('#mcTotalInfo').text('of ' + info.recordsTotal + ' items');
        $('#mcPerPage').val(table.page.len());
    });

    // ── Custom Search ──
    $('#mcSearch').on('input', function () {
        clearTimeout(window._mcSearchTimer);
        var q = $(this).val();
        window._mcSearchTimer = setTimeout(function () { table.search(q).draw(); }, 350);
    });

    // ── Per-page change ──
    $('#mcPerPage').on('change', function () {
        table.page.len(parseInt($(this).val())).draw();
    });

    // ── Edit btn → populate and open Bootstrap modal ──
    $(document).on('click', '.editshowConclusionAlert', function () {
        $('#edit_mc_pk').val($(this).data('pk'));
        $('#edit_mc_discussion_name').val($(this).data('discussion_name')).removeClass('is-invalid');
        $('#edit_mc_pt_discusion').val($(this).data('pt_discusion')).removeClass('is-invalid');
        $('#edit_mc_status').val($(this).data('active_inactive')).removeClass('is-invalid');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editMcModal')).show();
    });

    // ── ADD: submit (same fetch logic as original) ──
    $('#addMcSubmit').on('click', function () {
        var $name   = $('#add_mc_discussion_name');
        var $status = $('#add_mc_status');
        var valid   = true;

        $name.removeClass('is-invalid');
        $status.removeClass('is-invalid');
        if (!$name.val().trim()) { $name.addClass('is-invalid');   valid = false; }
        if (!$status.val())      { $status.addClass('is-invalid'); valid = false; }
        if (!valid) return;

        var $btn     = $(this).prop('disabled', true).text('Saving...');
        var formData = new FormData(document.getElementById('addMcForm'));

        fetch("{{ route('master.memo.conclusion.master.store') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: formData
        })
        .then(function (response) {
            if (!response.ok) { return response.json().then(function (err) { return Promise.reject(err); }); }
            return response.json();
        })
        .then(function (data) {
            $btn.prop('disabled', false).text('Create Memo Conclusion');
            if (data.status) {
                bootstrap.Modal.getInstance(document.getElementById('addMcModal')).hide();
                Swal.fire({ icon: 'success', title: 'Success', text: data.message, timer: 1500, showConfirmButton: false });
                table.ajax.reload(null, false);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message ?? 'Something went wrong' });
            }
        })
        .catch(function (error) {
            $btn.prop('disabled', false).text('Create Memo Conclusion');
            if (error && error.errors) {
                if (error.errors.discussion_name) { $name.addClass('is-invalid').next('.invalid-feedback').text(error.errors.discussion_name[0]); }
                if (error.errors.active_inactive) { $status.addClass('is-invalid').next('.invalid-feedback').text(error.errors.active_inactive[0]); }
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Server error or session expired' });
            }
        });
    });

    document.getElementById('addMcModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addMcForm').reset();
        $('#addMcForm .form-control, #addMcForm .form-select').removeClass('is-invalid');
    });

    // ── EDIT: submit (same fetch logic as original) ──
    $('#editMcSubmit').on('click', function () {
        var $name   = $('#edit_mc_discussion_name');
        var $status = $('#edit_mc_status');
        var valid   = true;

        $name.removeClass('is-invalid');
        $status.removeClass('is-invalid');
        if (!$name.val().trim()) { $name.addClass('is-invalid');   valid = false; }
        if (!$status.val())      { $status.addClass('is-invalid'); valid = false; }
        if (!valid) return;

        var $btn     = $(this).prop('disabled', true).text('Saving...');
        var formData = new FormData(document.getElementById('editMcForm'));

        fetch("{{ route('master.memo.conclusion.master.store') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: formData
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            $btn.prop('disabled', false).text('Update');
            if (data.status) {
                bootstrap.Modal.getInstance(document.getElementById('editMcModal')).hide();
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

    document.getElementById('editMcModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('editMcForm').reset();
        $('#editMcForm .form-control, #editMcForm .form-select').removeClass('is-invalid');
    });

    // ── Delete (same $.ajax as original) ──
    $(document).on('click', '.deleteBtn', function () {
        var btn = $(this);
        var url = btn.data('url');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone!',
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
                    data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        if (res.status) {
                            Swal.fire({ icon: 'success', title: 'Deleted!', text: res.message, timer: 1500, showConfirmButton: false });
                            table.ajax.reload(null, false);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error!', text: res.message });
                        }
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error!', text: 'Something went wrong.' });
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