@extends('admin.layouts.master')
@section('title', 'Activity departments')
@push('styles')
    <link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <style>
        .fc-dept-staff-select + .select2-container { width: 100% !important; }
        .fc-dept-staff-select + .select2-container--bootstrap-5 .select2-selection { min-height: 38px; font-size: 0.875rem; }
        /* Dropdown on body while modal open — above Bootstrap modal/backdrop (~1055) */
        .modal-open .select2-container--open,
        .modal-open .select2-dropdown { z-index: 20000 !important; }
    </style>
@endpush
@section('setup_content')
<div class="container-fluid px-3">
    <x-breadcrum title="FC — Activity departments"></x-breadcrum>
    <div class="d-flex justify-content-between mb-3">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;">Departments</h4>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalDeptCreate">Add</button>
    </div>
    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
    @if($errors->any() && old('_form') === 'dept_create')
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @if($errors->any() && old('_form') === 'dept_edit')
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table id="fcDeptSetupTable" class="table table-sm table-hover mb-0 w-100" data-export-title="FC Activity departments">
            <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Sort</th><th>Status</th><th>Activities</th><th>Staff</th><th></th></tr></thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>

{{-- Create --}}
    <div class="modal fade" id="modalDeptCreate" tabindex="-1" aria-labelledby="modalDeptCreateLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('fc-reg.admin.activity-setup.departments.store') }}">
                @csrf
                <input type="hidden" name="_form" value="dept_create">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalDeptCreateLabel">New department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">Code (slug)</label>
                        <input name="code" class="form-control form-control-sm @error('code') is-invalid @enderror" value="{{ old('_form') === 'dept_create' ? old('code') : '' }}" required pattern="[a-z0-9_]+" placeholder="e.g. medical">
                        @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Name</label>
                        <input name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('_form') === 'dept_create' ? old('name') : '' }}" required maxlength="150">
                        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Sort order</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm @error('sort_order') is-invalid @enderror" value="{{ old('_form') === 'dept_create' ? old('sort_order', 0) : 0 }}" min="0">
                        @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label small">Assigned staff (Post-Arrival Activities)</label>
                        <select name="assigned_user_pks[]" id="deptCreateUsers" class="form-select form-select-sm fc-dept-staff-select" multiple>
                            @foreach($staffForAssignment as $su)
                                <option value="{{ $su->pk }}" @selected(in_array((int) $su->pk, array_map('intval', (array) old('assigned_user_pks', [])), true))>{{ trim(($su->first_name ?? '').' '.($su->last_name ?? '')) ?: $su->user_name }} — {{ $su->user_name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Type to search. Stored by user id; labels show name and login. Only selected users see this department’s activities on the main FC Activities page.</div>
                        @error('assigned_user_pks')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit --}}
<div class="modal fade" id="modalDeptEdit" tabindex="-1" aria-labelledby="modalDeptEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="formDeptEdit" method="POST"
                action="{{ old('_form') === 'dept_edit' && old('edit_department_id') ? route('fc-reg.admin.activity-setup.departments.update', old('edit_department_id')) : '#' }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="dept_edit">
                <input type="hidden" name="edit_department_id" id="deptEditId" value="{{ old('_form') === 'dept_edit' ? old('edit_department_id') : '' }}">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalDeptEditLabel">Edit department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">Code</label>
                        <input name="code" id="deptEditCode" class="form-control form-control-sm @error('code') is-invalid @enderror" value="{{ old('_form') === 'dept_edit' ? old('code') : '' }}" required pattern="[a-z0-9_]+">
                        @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Name</label>
                        <input name="name" id="deptEditName" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('_form') === 'dept_edit' ? old('name') : '' }}" required maxlength="150">
                        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Sort order</label>
                        <input type="number" name="sort_order" id="deptEditSort" class="form-control form-control-sm @error('sort_order') is-invalid @enderror" value="{{ old('_form') === 'dept_edit' ? old('sort_order', 0) : 0 }}" min="0">
                        @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Status</label>
                        <select name="status" id="deptEditStatus" class="form-select form-select-sm @error('status') is-invalid @enderror">
                            <option value="1" @selected(old('_form') === 'dept_edit' && (string)old('status', '1') === '1')>Active</option>
                            <option value="0" @selected(old('_form') === 'dept_edit' && (string)old('status') === '0')>Inactive</option>
                        </select>
                        @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label small">Assigned staff (Post-Arrival Activities)</label>
                        <select name="assigned_user_pks[]" id="deptEditUsers" class="form-select form-select-sm fc-dept-staff-select" multiple>
                            @foreach($staffForAssignment as $su)
                                <option value="{{ $su->pk }}" @selected(old('_form') === 'dept_edit' && in_array((int) $su->pk, array_map('intval', (array) old('assigned_user_pks', [])), true))>{{ trim(($su->first_name ?? '').' '.($su->last_name ?? '')) ?: $su->user_name }} — {{ $su->user_name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Type to search; multiple staff allowed.</div>
                        @error('assigned_user_pks')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@include('admin.fc-activities.partials.datatable-tools')

@push('scripts')
<script>
(function () {
    var hadDeptCreateErr = @json($errors->any() && old('_form') === 'dept_create');
    var hadDeptEditErr = @json($errors->any() && old('_form') === 'dept_edit');

    document.addEventListener('DOMContentLoaded', function () {
        if (hadDeptCreateErr) {
            var m = document.getElementById('modalDeptCreate');
            if (m && window.bootstrap) new bootstrap.Modal(m).show();
        }
        if (hadDeptEditErr) {
            var m2 = document.getElementById('modalDeptEdit');
            if (m2 && window.bootstrap) new bootstrap.Modal(m2).show();
        }
    });

    var modalDeptEdit = document.getElementById('modalDeptEdit');
    if (modalDeptEdit) {
        modalDeptEdit.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (!btn || !btn.hasAttribute('data-dept-edit')) return;
            var row = JSON.parse(btn.getAttribute('data-dept-edit'));
            var form = document.getElementById('formDeptEdit');
            form.action = row.updateUrl;
            document.getElementById('deptEditId').value = row.edit_department_id;
            document.getElementById('deptEditCode').value = row.code;
            document.getElementById('deptEditName').value = row.name;
            document.getElementById('deptEditSort').value = row.sort_order;
            document.getElementById('deptEditStatus').value = String(row.status);
            var chosen = (row.assigned_user_pks || []).map(function (x) { return String(x); });
            if (window.jQuery && $('#deptEditUsers').length) {
                $('#deptEditUsers').val(chosen).trigger('change');
            }
        });
    }
})();

    function destroyFcDeptStaffSelect2($sel) {
        if (!window.jQuery || !$sel || !$sel.length || !$.fn.select2) return;
        if ($sel.hasClass('select2-hidden-accessible')) {
            try { $sel.select2('destroy'); } catch (e) {}
        }
    }

    function initFcDeptStaffSelect2($sel) {
        if (!window.jQuery || !$sel || !$sel.length || !$.fn.select2) return;
        destroyFcDeptStaffSelect2($sel);
        $sel.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Search and select staff…',
            allowClear: false,
            closeOnSelect: false,
            dropdownParent: $(document.body),
            minimumResultsForSearch: 0,
            language: { noResults: function () { return 'No staff match'; } }
        });
    }

    $(function () {
        var $createModal = $('#modalDeptCreate');
        var $editModal = $('#modalDeptEdit');
        $createModal.on('hidden.bs.modal', function () {
            destroyFcDeptStaffSelect2($('#deptCreateUsers'));
        });
        $editModal.on('hidden.bs.modal', function () {
            destroyFcDeptStaffSelect2($('#deptEditUsers'));
        });
        $createModal.on('shown.bs.modal', function () {
            initFcDeptStaffSelect2($('#deptCreateUsers'));
        });
        $editModal.on('shown.bs.modal', function () {
            initFcDeptStaffSelect2($('#deptEditUsers'));
        });
        if ($createModal.hasClass('show')) initFcDeptStaffSelect2($('#deptCreateUsers'));
        if ($editModal.hasClass('show')) initFcDeptStaffSelect2($('#deptEditUsers'));
    });

$(function () {
    var $t = $('#fcDeptSetupTable');
    if (!$t.length || !$.fn.DataTable) return;
    $t.DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: @json(route('fc-reg.admin.activity-setup.departments.data')) },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[2, 'asc']],
        scrollX: true,
        autoWidth: false,
        dom: '<"row align-items-center mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row align-items-center mt-2"<"col-md-5"i><"col-md-7"p>>',
        columnDefs: [{ targets: -1, className: 'text-end' }],
        columns: [
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { data: 'sort_order', name: 'sort_order' },
            { data: 'status_display', name: 'status_display', orderable: false, searchable: false },
            { data: 'masters_count', name: 'masters_count', orderable: false, searchable: false },
            { data: 'staff_count', name: 'staff_assignments_count', orderable: true, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
});
</script>
@endpush
