@extends('admin.layouts.master')
@section('title', 'Activity master')
@section('setup_content')
@php
    $departmentsActive = $departments->where('status', 1)->values();
    $createDeptPreselect = old('_form') === 'master_create' ? old('department_id') : request('department_id');
@endphp
<div class="container-fluid px-3">
    <x-breadcrum title="FC — Activity master"></x-breadcrum>
    <div class="row align-items-center g-2 mb-3">
        <div class="col-12 col-lg">
            <h4 class="fw-bold mb-0" style="color:#1a3c6e;">Activities</h4>
        </div>
        <div class="col-12 col-lg-auto">
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 justify-content-lg-end">
                <div class="input-group input-group-sm flex-grow-1 flex-sm-grow-0" style="min-width: min(100%, 16rem); max-width: 22rem;">
                    <label class="input-group-text text-muted small mb-0" for="fcMasterDeptFilter">Department</label>
                    <select id="fcMasterDeptFilter" name="department_id" class="form-select" aria-label="Department filter">
                        <option value="">All departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected((string)$deptFilter === (string)$d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-sm btn-primary text-nowrap align-self-sm-center" data-bs-toggle="modal" data-bs-target="#modalMasterCreate">Add activity</button>
            </div>
        </div>
    </div>
    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
    @if($errors->any() && old('_form') === 'master_create')
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @if($errors->any() && old('_form') === 'master_edit')
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table id="fcMasterSetupTable" class="table table-sm table-hover mb-0 w-100" data-export-title="FC Activity master">
            <thead class="table-light">
                <tr>
                    <th>Dept</th><th>Code</th><th>Label</th><th>Course</th><th>Order</th><th>Status</th><th>Joined marker</th><th>Policy</th><th></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div></div>
</div>

{{-- Create --}}
<div class="modal fade" id="modalMasterCreate" tabindex="-1" aria-labelledby="modalMasterCreateLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('fc-reg.admin.activity-setup.masters.store') }}">
                @csrf
                <input type="hidden" name="_form" value="master_create">
                <input type="hidden" name="return_department_id" id="masterReturnDeptIdCreate" value="{{ $deptFilter ?? '' }}">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalMasterCreateLabel">New activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($departmentsActive->isEmpty())
                        <p class="text-danger small mb-0">Add an active department first.</p>
                    @endif
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Department</label>
                            <select name="department_id" class="form-select form-select-sm @error('department_id') is-invalid @enderror" @if($departmentsActive->isEmpty()) disabled @else required @endif>
                                @foreach($departmentsActive as $d)
                                    <option value="{{ $d->id }}" @selected((string)$createDeptPreselect === (string)$d->id)>{{ $d->name }} ({{ $d->code }})</option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Activity code (menuid)</label>
                            <input name="menuid" class="form-control form-control-sm @error('menuid') is-invalid @enderror" value="{{ old('_form') === 'master_create' ? old('menuid') : '' }}" required pattern="[a-z0-9_]+" maxlength="30">
                            @error('menuid')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Display name</label>
                            <input name="menun" class="form-control form-control-sm @error('menun') is-invalid @enderror" value="{{ old('_form') === 'master_create' ? old('menun') : '' }}" required maxlength="100">
                            @error('menun')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Course filter (blank = all)</label>
                            <input name="ccode" class="form-control form-control-sm @error('ccode') is-invalid @enderror" value="{{ old('_form') === 'master_create' ? old('ccode') : '' }}" maxlength="120" placeholder="Session name or empty">
                            @error('ccode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Sort order</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm @error('sort_order') is-invalid @enderror" value="{{ old('_form') === 'master_create' ? old('sort_order', 0) : 0 }}" min="0">
                            @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select form-select-sm @error('status') is-invalid @enderror">
                                <option value="1" @selected(old('_form') === 'master_create' && old('status', '1') == '1')>Active</option>
                                <option value="0" @selected(old('_form') === 'master_create' && old('status') === '0')>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Entry policy</label>
                            <select name="entry_policy" class="form-select form-select-sm @error('entry_policy') is-invalid @enderror">
                                <option value="unique" @selected(old('_form') === 'master_create' && old('entry_policy', 'unique') === 'unique')>Unique</option>
                                <option value="upsert" @selected(old('_form') === 'master_create' && old('entry_policy') === 'upsert')>Upsert</option>
                                <option value="repeat" @selected(old('_form') === 'master_create' && old('entry_policy') === 'repeat')>Repeat (history)</option>
                            </select>
                            @error('entry_policy')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_joined_marker" value="1" id="jmMasterCreate" @checked(old('_form') === 'master_create' && old('is_joined_marker'))>
                                <label class="form-check-label small" for="jmMasterCreate">Joined / arrival marker</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" @if($departmentsActive->isEmpty()) disabled @endif>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit --}}
<div class="modal fade" id="modalMasterEdit" tabindex="-1" aria-labelledby="modalMasterEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="formMasterEdit" method="POST"
                action="{{ old('_form') === 'master_edit' && old('edit_master_id') ? route('fc-reg.admin.activity-setup.masters.update', old('edit_master_id')) : '#' }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="master_edit">
                <input type="hidden" name="edit_master_id" id="masterEditId" value="{{ old('_form') === 'master_edit' ? old('edit_master_id') : '' }}">
                <input type="hidden" name="return_department_id" id="masterReturnDeptIdEdit" value="{{ $deptFilter ?? '' }}">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalMasterEditLabel">Edit activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Department</label>
                            <select name="department_id" id="masterEditDept" class="form-select form-select-sm @error('department_id') is-invalid @enderror" required>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" @selected(old('_form') === 'master_edit' && (string)old('department_id') === (string)$d->id)>{{ $d->name }} ({{ $d->code }}){{ $d->status ? '' : ' — inactive' }}</option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Activity code (menuid)</label>
                            <input name="menuid" id="masterEditMenuid" class="form-control form-control-sm @error('menuid') is-invalid @enderror" value="{{ old('_form') === 'master_edit' ? old('menuid') : '' }}" required pattern="[a-z0-9_]+" maxlength="30">
                            @error('menuid')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Display name</label>
                            <input name="menun" id="masterEditMenun" class="form-control form-control-sm @error('menun') is-invalid @enderror" value="{{ old('_form') === 'master_edit' ? old('menun') : '' }}" required maxlength="100">
                            @error('menun')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Course filter</label>
                            <input name="ccode" id="masterEditCcode" class="form-control form-control-sm @error('ccode') is-invalid @enderror" value="{{ old('_form') === 'master_edit' ? old('ccode') : '' }}" maxlength="120">
                            @error('ccode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Sort order</label>
                            <input type="number" name="sort_order" id="masterEditSort" class="form-control form-control-sm @error('sort_order') is-invalid @enderror" value="{{ old('_form') === 'master_edit' ? old('sort_order', 0) : 0 }}" min="0">
                            @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Status</label>
                            <select name="status" id="masterEditStatus" class="form-select form-select-sm @error('status') is-invalid @enderror">
                                <option value="1" @selected(old('_form') === 'master_edit' && (string)old('status', '1') === '1')>Active</option>
                                <option value="0" @selected(old('_form') === 'master_edit' && (string)old('status') === '0')>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Entry policy</label>
                            <select name="entry_policy" id="masterEditPolicy" class="form-select form-select-sm @error('entry_policy') is-invalid @enderror">
                                <option value="unique" @selected(old('_form') === 'master_edit' && old('entry_policy', 'unique') === 'unique')>Unique</option>
                                <option value="upsert" @selected(old('_form') === 'master_edit' && old('entry_policy') === 'upsert')>Upsert</option>
                                <option value="repeat" @selected(old('_form') === 'master_edit' && old('entry_policy') === 'repeat')>Repeat (history)</option>
                            </select>
                            @error('entry_policy')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_joined_marker" value="1" id="jmMasterEdit" @checked(old('_form') === 'master_edit' && old('is_joined_marker'))>
                                <label class="form-check-label small" for="jmMasterEdit">Joined / arrival marker</label>
                            </div>
                        </div>
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
    var hadMasterCreateErr = @json($errors->any() && old('_form') === 'master_create');
    var hadMasterEditErr = @json($errors->any() && old('_form') === 'master_edit');

    document.addEventListener('DOMContentLoaded', function () {
        if (hadMasterCreateErr) {
            var m = document.getElementById('modalMasterCreate');
            if (m && window.bootstrap) new bootstrap.Modal(m).show();
        }
        if (hadMasterEditErr) {
            var m2 = document.getElementById('modalMasterEdit');
            if (m2 && window.bootstrap) new bootstrap.Modal(m2).show();
        }
    });

    var modalMasterEdit = document.getElementById('modalMasterEdit');
    if (modalMasterEdit) {
        modalMasterEdit.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (!btn || !btn.hasAttribute('data-master-edit')) return;
            var row = JSON.parse(btn.getAttribute('data-master-edit'));
            var form = document.getElementById('formMasterEdit');
            form.action = row.updateUrl;
            document.getElementById('masterEditId').value = row.edit_master_id;
            document.getElementById('masterEditDept').value = String(row.department_id);
            document.getElementById('masterEditMenuid').value = row.menuid;
            document.getElementById('masterEditMenun').value = row.menun;
            document.getElementById('masterEditCcode').value = row.ccode || '';
            document.getElementById('masterEditSort').value = row.sort_order;
            document.getElementById('masterEditStatus').value = String(row.status);
            document.getElementById('masterEditPolicy').value = row.entry_policy;
            document.getElementById('jmMasterEdit').checked = !!row.is_joined_marker;
        });
    }
})();

$(function () {
    var $t = $('#fcMasterSetupTable');
    var $filter = $('#fcMasterDeptFilter');
    if (!$t.length || !$.fn.DataTable) return;
    var dt = $t.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('fc-reg.admin.activity-setup.masters.data')),
            data: function (d) {
                d.department_id = $filter.val() || '';
            }
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[4, 'asc']],
        scrollX: true,
        autoWidth: false,
        dom: '<"row align-items-center mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row align-items-center mt-2"<"col-md-5"i><"col-md-7"p>>',
        columnDefs: [{ targets: -1, className: 'text-end' }],
        columns: [
            { data: 'dept_name', name: 'dept_name', orderable: false },
            { data: 'menuid', name: 'menuid' },
            { data: 'menun', name: 'menun' },
            { data: 'ccode', name: 'ccode' },
            { data: 'sort_order', name: 'sort_order' },
            { data: 'status_display', name: 'status_display', orderable: false, searchable: false },
            { data: 'joined_display', name: 'joined_display', orderable: false, searchable: false },
            { data: 'entry_policy', name: 'entry_policy' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
    $filter.on('change', function () {
        var v = this.value || '';
        $('#masterReturnDeptIdCreate, #masterReturnDeptIdEdit').val(v);
        dt.ajax.reload();
    });
});
</script>
@endpush
