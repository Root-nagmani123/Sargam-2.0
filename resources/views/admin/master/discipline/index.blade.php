@extends('admin.layouts.master')

@section('title', 'Discipline Master')

@section('setup_content')
<style>
/* ─── Hide DataTable default controls ─── */
#discipline-table_wrapper .dataTables_length,
#discipline-table_wrapper .dataTables_filter,
#discipline-table_wrapper .dataTables_info { display: none !important; }

/* ─── Paginate styling ─── */
#discipline-table_wrapper .dataTables_paginate { margin-top: 0 !important; display: flex; align-items: center; gap: 2px; }
#discipline-table_wrapper .paginate_button { border: none !important; background: transparent !important; padding: 5px 10px; border-radius: 4px; font-size: 0.8125rem; cursor: pointer; color: #495057 !important; }
#discipline-table_wrapper .paginate_button:hover:not(.disabled) { background: #f1f3f5 !important; }
#discipline-table_wrapper .paginate_button.current,
#discipline-table_wrapper .paginate_button.current:hover { background: #1b3a5c !important; color: #fff !important; border-radius: 4px; }
#discipline-table_wrapper .paginate_button.disabled { opacity: 0.35; cursor: default; }
#discipline-table_wrapper .ellipsis { padding: 5px 4px; color: #adb5bd; }

/* ─── Custom Search ─── */
.dis-search-wrap { position: relative; width: 260px; }
.dis-search-wrap .form-control { border-radius: 8px; border: 1px solid #dee2e6; padding-left: 38px; font-size: 0.875rem; }
.dis-search-wrap .dis-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #adb5bd; font-size: 18px; pointer-events: none; }

/* ─── Status Badges ─── */
.dis-badge-active { display: inline-block; background-color: #d1fae5; color: #065f46; border-radius: 10px !important; padding: 4px 14px; font-size: 0.8rem; font-weight: 600; }
.dis-badge-inactive { display: inline-block; background-color: #fee2e2; color: #991b1b; border-radius: 10px !important; padding: 4px 14px; font-size: 0.8rem; font-weight: 600; }

/* ─── Table ─── */
#discipline-table thead th { background-color: #f8f9fa; font-size: 0.8125rem; font-weight: 600; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 12px 16px; white-space: nowrap; }
#discipline-table tbody td { font-size: 0.875rem; padding: 12px 16px; vertical-align: middle; border-bottom: 1px solid #f1f3f5; color: #212529; }
#discipline-table tbody tr:hover td { background-color: #fafbfc; }

/* ─── Action Buttons ─── */
.dis-action-btn { background: none; border: none; padding: 3px 5px; cursor: pointer; line-height: 1; display: inline-flex; align-items: center; text-decoration: none; }
.dis-action-btn .material-symbols-rounded { font-size: 20px; }
.dis-action-btn:hover { opacity: 0.7; }

/* ─── Modal ─── */
.dis-modal .modal-content { border: 1.5px dashed #6ea8fe; border-radius: 12px; }
.dis-modal .modal-header { border-bottom: none; padding: 20px 24px 6px; }
.dis-modal .modal-body   { padding: 8px 24px 16px; }
.dis-modal .modal-footer { border-top: none; padding: 0 24px 20px; }
.dis-modal .modal-title  { font-size: 1rem; font-weight: 700; color: #1b3a5c; }
.dis-modal .form-label   { font-size: 0.875rem; font-weight: 500; margin-bottom: 4px; color: #495057; }
.dis-modal .form-control,
.dis-modal .form-select  { border-radius: 6px; font-size: 0.875rem; border: 1px solid #dee2e6; padding: 8px 12px; }
.dis-modal .form-control:focus,
.dis-modal .form-select:focus { border-color: #6ea8fe; box-shadow: 0 0 0 3px rgba(110,168,254,0.15); }
</style>

<div class="container-fluid">
    <x-breadcrum title="Discipline Master" subtitle="List of discipline records">
        <button type="button" data-bs-toggle="modal" data-bs-target="#addDisModal"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Discipline</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4">

                {{-- Custom Search --}}
                <div class="d-flex justify-content-end mb-3">
                    <div class="dis-search-wrap">
                        <i class="material-icons material-symbols-rounded dis-search-icon">search</i>
                        <input type="text" id="disSearch" class="form-control" placeholder="Search">
                    </div>
                </div>

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table mb-0']) !!}
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div id="disPaginationCell"></div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="text-muted small">Showing</span>
                        <select id="disPerPage" class="form-select form-select-sm" style="width:78px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                        <span id="disTotalInfo" class="text-muted small">of 0 items</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ ADD MODAL ═══════════════════ --}}
<div class="modal fade dis-modal" id="addDisModal" tabindex="-1"
    aria-labelledby="addDisModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDisModalLabel">Add Discipline</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addDisForm" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="add_dis_course" class="form-label">Select Course <span class="text-danger">*</span></label>
                        <select id="add_dis_course" name="course_master_pk" class="form-select">
                            <option value="">Select Course</option>
                            @foreach($courses as $c)
                            <option value="{{ $c->pk }}">{{ $c->course_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Course is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_dis_name" class="form-label">Discipline Name <span class="text-danger">*</span></label>
                        <input type="text" id="add_dis_name" name="discipline_name" class="form-control" placeholder="eg. 24th Mid-Career Programme Phase-III 2026">
                        <div class="invalid-feedback">Discipline Name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_dis_deduction" class="form-label">Mark Deduction <span class="text-danger">*</span></label>
                        <input type="number" id="add_dis_deduction" name="mark_deduction" class="form-control" placeholder="eg. 22.00" step="0.01" min="0">
                        <div class="invalid-feedback">Mark Deduction is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="add_dis_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="add_dis_status" name="active_inactive" class="form-select">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                        <div class="invalid-feedback">Status is required.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn fw-semibold px-4" id="addDisSubmit"
                    style="background:#1b3a5c;color:#fff;border-color:#1b3a5c;">Create Discipline</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ EDIT MODAL ═══════════════════ --}}
<div class="modal fade dis-modal" id="editDisModal" tabindex="-1"
    aria-labelledby="editDisModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDisModalLabel">Edit Discipline</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editDisForm" novalidate>
                    @csrf
                    <input type="hidden" name="id" id="edit_dis_pk">
                    <div class="mb-3">
                        <label for="edit_dis_course" class="form-label">Select Course <span class="text-danger">*</span></label>
                        <select id="edit_dis_course" name="course_master_pk" class="form-select">
                            <option value="">Select Course</option>
                            @foreach($courses as $c)
                            <option value="{{ $c->pk }}">{{ $c->course_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Course is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_dis_name" class="form-label">Discipline Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit_dis_name" name="discipline_name" class="form-control" placeholder="eg. 24th Mid-Career Programme Phase-III 2026">
                        <div class="invalid-feedback">Discipline Name is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_dis_deduction" class="form-label">Mark Deduction <span class="text-danger">*</span></label>
                        <input type="number" id="edit_dis_deduction" name="mark_deduction" class="form-control" placeholder="eg. 22.00" step="0.01" min="0">
                        <div class="invalid-feedback">Mark Deduction is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_dis_status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="edit_dis_status" name="active_inactive" class="form-select">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                        <div class="invalid-feedback">Status is required.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn fw-semibold px-4" id="editDisSubmit"
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

    var table = $('#discipline-table').DataTable();

    // ── Move DT pagination into custom bottom row ──
    var $paginate = $('#discipline-table_wrapper .dataTables_paginate');
    if ($paginate.length) { $paginate.appendTo('#disPaginationCell'); }

    // ── Draw event ──
    $('#discipline-table').on('draw.dt', function () {
        var info = table.page.info();
        $('#disTotalInfo').text('of ' + info.recordsTotal + ' items');
        $('#disPerPage').val(String(table.page.len()));
    });

    // ── Custom Search ──
    $('#disSearch').on('input', function () {
        clearTimeout(window._disSearchTimer);
        var q = $(this).val();
        window._disSearchTimer = setTimeout(function () { table.search(q).draw(); }, 350);
    });

    // ── Per-page change ──
    $('#disPerPage').on('change', function () {
        table.page.len(parseInt($(this).val(), 10)).draw();
    });

    // ── Edit btn → populate and open Bootstrap modal ──
    $(document).on('click', '.dis-edit-btn', function () {
        $('#edit_dis_pk').val($(this).data('pk'));
        $('#edit_dis_course').val($(this).data('course')).removeClass('is-invalid');
        $('#edit_dis_name').val($(this).data('name')).removeClass('is-invalid');
        $('#edit_dis_deduction').val($(this).data('deduction')).removeClass('is-invalid');
        $('#edit_dis_status').val($(this).data('status')).removeClass('is-invalid');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editDisModal')).show();
    });

    // ── ADD: client-side validate then AJAX submit ──
    $('#addDisSubmit').on('click', function () {
        var $course     = $('#add_dis_course');
        var $name       = $('#add_dis_name');
        var $deduction  = $('#add_dis_deduction');
        var $status     = $('#add_dis_status');
        var valid       = true;

        [$course, $name, $deduction, $status].forEach(function ($el) { $el.removeClass('is-invalid'); });
        if (!$course.val())    { $course.addClass('is-invalid');    valid = false; }
        if (!$name.val().trim()){ $name.addClass('is-invalid');     valid = false; }
        if ($deduction.val() === '') { $deduction.addClass('is-invalid'); valid = false; }
        if (!$status.val())    { $status.addClass('is-invalid');    valid = false; }
        if (!valid) return;

        var $btn     = $(this).prop('disabled', true).text('Saving...');
        var formData = new FormData(document.getElementById('addDisForm'));

        fetch("{{ route('master.discipline.store') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: formData
        })
        .then(function (res) {
            // Store returns redirect on success; JSON 422 on validation fail
            if (res.status === 422) { return res.json().then(function (e) { return Promise.reject(e); }); }
            // Any 2xx or 3xx (redirect) = success
            return { status: true, message: 'Discipline saved successfully.' };
        })
        .then(function (data) {
            $btn.prop('disabled', false).text('Create Discipline');
            bootstrap.Modal.getInstance(document.getElementById('addDisModal')).hide();
            Swal.fire({ icon: 'success', title: 'Saved!', text: data.message, timer: 1500, showConfirmButton: false });
            table.ajax.reload(null, false);
        })
        .catch(function (error) {
            $btn.prop('disabled', false).text('Create Discipline');
            if (error && error.errors) {
                if (error.errors.course_master_pk) { $course.addClass('is-invalid').next('.invalid-feedback').text(error.errors.course_master_pk[0]); }
                if (error.errors.discipline_name)  { $name.addClass('is-invalid').next('.invalid-feedback').text(error.errors.discipline_name[0]); }
                if (error.errors.mark_deduction)   { $deduction.addClass('is-invalid').next('.invalid-feedback').text(error.errors.mark_deduction[0]); }
                if (error.errors.active_inactive)  { $status.addClass('is-invalid').next('.invalid-feedback').text(error.errors.active_inactive[0]); }
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
            }
        });
    });

    document.getElementById('addDisModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addDisForm').reset();
        $('#addDisForm .form-control, #addDisForm .form-select').removeClass('is-invalid');
    });

    // ── EDIT: submit ──
    $('#editDisSubmit').on('click', function () {
        var $course    = $('#edit_dis_course');
        var $name      = $('#edit_dis_name');
        var $deduction = $('#edit_dis_deduction');
        var $status    = $('#edit_dis_status');
        var valid      = true;

        [$course, $name, $deduction, $status].forEach(function ($el) { $el.removeClass('is-invalid'); });
        if (!$course.val())     { $course.addClass('is-invalid');    valid = false; }
        if (!$name.val().trim()){ $name.addClass('is-invalid');      valid = false; }
        if ($deduction.val() === '') { $deduction.addClass('is-invalid'); valid = false; }
        if (!$status.val())     { $status.addClass('is-invalid');    valid = false; }
        if (!valid) return;

        var $btn     = $(this).prop('disabled', true).text('Saving...');
        var formData = new FormData(document.getElementById('editDisForm'));

        fetch("{{ route('master.discipline.store') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: formData
        })
        .then(function (res) {
            if (res.status === 422) { return res.json().then(function (e) { return Promise.reject(e); }); }
            return { status: true, message: 'Discipline updated successfully.' };
        })
        .then(function (data) {
            $btn.prop('disabled', false).text('Update');
            bootstrap.Modal.getInstance(document.getElementById('editDisModal')).hide();
            Swal.fire({ icon: 'success', title: 'Updated!', text: data.message, timer: 1500, showConfirmButton: false });
            table.ajax.reload(null, false);
        })
        .catch(function (error) {
            $btn.prop('disabled', false).text('Update');
            if (error && error.errors) {
                if (error.errors.course_master_pk) { $course.addClass('is-invalid').next('.invalid-feedback').text(error.errors.course_master_pk[0]); }
                if (error.errors.discipline_name)  { $name.addClass('is-invalid').next('.invalid-feedback').text(error.errors.discipline_name[0]); }
                if (error.errors.mark_deduction)   { $deduction.addClass('is-invalid').next('.invalid-feedback').text(error.errors.mark_deduction[0]); }
                if (error.errors.active_inactive)  { $status.addClass('is-invalid').next('.invalid-feedback').text(error.errors.active_inactive[0]); }
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
            }
        });
    });

    document.getElementById('editDisModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('editDisForm').reset();
        $('#editDisForm .form-control, #editDisForm .form-select').removeClass('is-invalid');
    });

    // ── Delete: SweetAlert + JS form submit (same backend route) ──
    $(document).on('click', '.dis-delete-btn', function () {
        var url = $(this).data('url');
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
                var $form = $('<form>', { method: 'POST', action: url }).appendTo('body');
                $form.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
                $form.append($('<input>', { type: 'hidden', name: '_method', value: 'DELETE' }));
                $form.submit();
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