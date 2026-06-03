@extends('admin.layouts.master')
@section('title', 'Activity master')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
<style>
    /* Choices.js department dropdowns — aligned with admin/mess/process-mess-bills-employee (Choices + Bootstrap) */
    .fc-master-choices-scope .choices { margin-bottom: 0; max-width: 100%; font-size: 0.875rem; }
    .fc-master-choices-scope .choices .choices__inner {
        min-height: calc(1.5em + 0.5rem + 2px);
        padding: 0.25rem 2rem 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
    }
    .fc-master-choices-scope .choices.is-focused .choices__inner,
    .fc-master-choices-scope .choices.is-open .choices__inner {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
    }
    .fc-master-choices-scope .input-group .choices { flex: 1 1 auto; min-width: 0; }
    .fc-master-choices-scope .input-group .choices .choices__inner {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    .fc-master-choices-scope .choices__list--dropdown,
    .fc-master-choices-scope .choices__list[aria-expanded] {
        z-index: 2005;
        border-radius: var(--bs-border-radius);
        box-shadow: var(--bs-box-shadow);
    }
    .fc-master-choices-scope .choices__list--dropdown .choices__item--selectable.is-highlighted,
    .fc-master-choices-scope .choices__list[aria-expanded] .choices__item--selectable.is-highlighted {
        background-color: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
    }
</style>
@endpush
@section('setup_content')
@php
    $departmentsActive = $departments->where('status', 1)->values();
    $createDeptPreselect = old('_form') === 'master_create' ? old('department_id') : request('department_id');
@endphp
<div class="fc-master-choices-scope">
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
                    <select id="fcMasterDeptFilter" name="department_id" class="form-select form-select-sm fc-master-dept-choices" aria-label="Department filter" data-placeholder="All departments" data-search="true">
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
                    <th>Dept</th><th>Code</th><th>Label</th><th>Course</th><th>Order</th><th>Status</th><th>Policy</th><th class="text-end text-nowrap">Action</th>
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
                    @else
                        <p class="small text-muted mb-2"><span class="text-danger" aria-hidden="true">*</span> Required fields.</p>
                    @endif
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Department <span class="text-danger" title="Required">*</span></label>
                            <select name="department_id" id="masterCreateDept" class="form-select form-select-sm fc-master-dept-choices @error('department_id') is-invalid @enderror" @if($departmentsActive->isEmpty()) disabled @endif aria-required="true" data-placeholder="Select department" data-search="true">
                                @foreach($departmentsActive as $d)
                                    <option value="{{ $d->id }}" @selected((string)$createDeptPreselect === (string)$d->id)>{{ $d->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Activity code (menuid) <span class="text-danger" title="Required">*</span></label>
                            <input name="menuid" class="form-control form-control-sm @error('menuid') is-invalid @enderror" value="{{ old('_form') === 'master_create' ? old('menuid') : '' }}" required aria-required="true" pattern="[a-z0-9_]+" maxlength="30" title="Lowercase letters, digits, underscores only; max 30">
                            <small class="form-text text-muted">Lowercase letters, numbers, and underscores only; no spaces. Up to 30 characters (e.g. <code class="small">uan_update</code>).</small>
                            @error('menuid')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Display name <span class="text-danger" title="Required">*</span></label>
                            <input name="menun" class="form-control form-control-sm @error('menun') is-invalid @enderror" value="{{ old('_form') === 'master_create' ? old('menun') : '' }}" required aria-required="true" maxlength="100">
                            @error('menun')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Course</label>
                            <select name="ccode" class="form-select form-select-sm @error('ccode') is-invalid @enderror">
                                <option value="">All courses</option>
                                @php $createCcode = old('_form') === 'master_create' ? (string) old('ccode', '') : ''; @endphp
                                @foreach($courseFilterOptions as $sn)
                                    <option value="{{ $sn }}" @selected($createCcode === $sn)>{{ $sn }}</option>
                                @endforeach
                                @if($createCcode !== '' && ! $courseFilterOptions->contains($createCcode))
                                    <option value="{{ $createCcode }}" selected>{{ $createCcode }} (not in list)</option>
                                @endif
                            </select>
                            @error('ccode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Sort order</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm @error('sort_order') is-invalid @enderror" value="{{ old('_form') === 'master_create' ? old('sort_order', 0) : 0 }}" min="0">
                            @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Status <span class="text-danger" title="Required">*</span></label>
                            <select name="status" class="form-select form-select-sm @error('status') is-invalid @enderror" required aria-required="true">
                                <option value="1" @selected(old('_form') === 'master_create' && old('status', '1') == '1')>Active</option>
                                <option value="0" @selected(old('_form') === 'master_create' && old('status') === '0')>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Entry policy <span class="text-danger" title="Required">*</span></label>
                            <select name="entry_policy" class="form-select form-select-sm @error('entry_policy') is-invalid @enderror" required aria-required="true">
                                <option value="unique" @selected(old('_form') === 'master_create' && old('entry_policy', 'unique') === 'unique')>Unique</option>
                                <option value="upsert" @selected(old('_form') === 'master_create' && old('entry_policy') === 'upsert')>Upsert</option>
                                <option value="repeat" @selected(old('_form') === 'master_create' && old('entry_policy') === 'repeat')>Repeat (history)</option>
                            </select>
                            @error('entry_policy')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
                    <p class="small text-muted mb-2"><span class="text-danger" aria-hidden="true">*</span> Required fields.</p>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Department <span class="text-danger" title="Required">*</span></label>
                            <select name="department_id" id="masterEditDept" class="form-select form-select-sm fc-master-dept-choices @error('department_id') is-invalid @enderror" aria-required="true" data-placeholder="Select department" data-search="true">
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" @selected(old('_form') === 'master_edit' && (string)old('department_id') === (string)$d->id)>{{ $d->name }}{{ $d->status ? '' : ' — inactive' }}</option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Activity code (menuid) <span class="text-danger" title="Required">*</span></label>
                            <input name="menuid" id="masterEditMenuid" class="form-control form-control-sm @error('menuid') is-invalid @enderror" value="{{ old('_form') === 'master_edit' ? old('menuid') : '' }}" required aria-required="true" pattern="[a-z0-9_]+" maxlength="30" title="Lowercase letters, digits, underscores only; max 30">
                            <small class="form-text text-muted">Lowercase letters, numbers, and underscores only; no spaces. Up to 30 characters (e.g. <code class="small">uan_update</code>).</small>
                            @error('menuid')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Display name <span class="text-danger" title="Required">*</span></label>
                            <input name="menun" id="masterEditMenun" class="form-control form-control-sm @error('menun') is-invalid @enderror" value="{{ old('_form') === 'master_edit' ? old('menun') : '' }}" required aria-required="true" maxlength="100">
                            @error('menun')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Course</label>
                            <select name="ccode" id="masterEditCcode" class="form-select form-select-sm @error('ccode') is-invalid @enderror">
                                <option value="">All courses</option>
                                @foreach($courseFilterOptions as $sn)
                                    <option value="{{ $sn }}" @selected(old('_form') === 'master_edit' && (string) old('ccode', '') === $sn)>{{ $sn }}</option>
                                @endforeach
                                @if(old('_form') === 'master_edit' && (string) old('ccode', '') !== '' && ! $courseFilterOptions->contains((string) old('ccode')))
                                    <option value="{{ old('ccode') }}" selected>{{ old('ccode') }} (not in list)</option>
                                @endif
                            </select>
                            @error('ccode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Sort order</label>
                            <input type="number" name="sort_order" id="masterEditSort" class="form-control form-control-sm @error('sort_order') is-invalid @enderror" value="{{ old('_form') === 'master_edit' ? old('sort_order', 0) : 0 }}" min="0">
                            @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Status <span class="text-danger" title="Required">*</span></label>
                            <select name="status" id="masterEditStatus" class="form-select form-select-sm @error('status') is-invalid @enderror" required aria-required="true">
                                <option value="1" @selected(old('_form') === 'master_edit' && (string)old('status', '1') === '1')>Active</option>
                                <option value="0" @selected(old('_form') === 'master_edit' && (string)old('status') === '0')>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Entry policy <span class="text-danger" title="Required">*</span></label>
                            <select name="entry_policy" id="masterEditPolicy" class="form-select form-select-sm @error('entry_policy') is-invalid @enderror" required aria-required="true">
                                <option value="unique" @selected(old('_form') === 'master_edit' && old('entry_policy', 'unique') === 'unique')>Unique</option>
                                <option value="upsert" @selected(old('_form') === 'master_edit' && old('entry_policy') === 'upsert')>Upsert</option>
                                <option value="repeat" @selected(old('_form') === 'master_edit' && old('entry_policy') === 'repeat')>Repeat (history)</option>
                            </select>
                            @error('entry_policy')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
</div>

@include('admin.fc-activities.partials.datatable-tools')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
function normalizeFcMasterDeptChoicesSearchText(text) {
    return String(text || '').replace(/\s+/g, ' ').trim().toLowerCase();
}
function applyFcMasterDeptChoicesSearchFilter(instance, rawQuery) {
    if (!instance || !instance.dropdown || !instance.dropdown.element) return;
    var dropdownEl = instance.dropdown.element;
    var query = normalizeFcMasterDeptChoicesSearchText(rawQuery);
    var choiceItems = dropdownEl.querySelectorAll('.choices__item--choice');
    if (!choiceItems || !choiceItems.length) return;
    choiceItems.forEach(function (item) {
        if (item.classList.contains('choices__placeholder')) return;
        var label = normalizeFcMasterDeptChoicesSearchText(item.textContent || '');
        var value = normalizeFcMasterDeptChoicesSearchText(item.getAttribute('data-value') || '');
        var show = !query || label.indexOf(query) !== -1 || value.indexOf(query) !== -1;
        item.style.display = show ? '' : 'none';
    });
}
function initFcMasterDeptChoices() {
    if (typeof Choices === 'undefined') return;
    document.querySelectorAll('select.fc-master-dept-choices').forEach(function (el) {
        if (el.disabled) return;
        if (el._fcMasterDeptChoicesInstance) return;
        if (el.closest('.choices')) return;
        var placeholder = el.getAttribute('data-placeholder') || 'Select…';
        var useCustomSearch = el.getAttribute('data-search') !== 'false';
        var inst = new Choices(el, {
            searchEnabled: true,
            searchChoices: !useCustomSearch,
            shouldSort: false,
            allowHTML: false,
            itemSelectText: '',
            position: 'bottom',
            placeholder: true,
            placeholderValue: placeholder,
            closeDropdownOnSelect: true
        });
        el._fcMasterDeptChoicesInstance = inst;
        if (useCustomSearch) {
            function applySearchFilterAfterRender() {
                var typed = (inst.input && inst.input.element) ? (inst.input.element.value || '') : '';
                requestAnimationFrame(function () {
                    applyFcMasterDeptChoicesSearchFilter(inst, typed);
                });
            }
            el.addEventListener('showDropdown', applySearchFilterAfterRender);
            if (inst.input && inst.input.element) {
                inst.input.element.addEventListener('input', applySearchFilterAfterRender);
                inst.input.element.addEventListener('keyup', applySearchFilterAfterRender);
            }
        }
    });
}
function syncFcMasterDeptChoicesValue(selectEl) {
    if (!selectEl || !selectEl._fcMasterDeptChoicesInstance) return;
    var v = selectEl.value;
    try {
        if (v === '' || v === null || typeof v === 'undefined') {
            selectEl._fcMasterDeptChoicesInstance.setChoiceByValue('');
        } else {
            selectEl._fcMasterDeptChoicesInstance.setChoiceByValue(String(v));
        }
    } catch (err) {
        try { selectEl._fcMasterDeptChoicesInstance.setChoiceByValue(''); } catch (e2) {}
    }
}
(function () {
    var modalMasterEdit = document.getElementById('modalMasterEdit');
    if (modalMasterEdit) {
        modalMasterEdit.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (!btn || !btn.hasAttribute('data-master-edit')) return;
            var row = JSON.parse(btn.getAttribute('data-master-edit'));
            var form = document.getElementById('formMasterEdit');
            form.action = row.updateUrl;
            document.getElementById('masterEditId').value = row.edit_master_id;
            var deptEl = document.getElementById('masterEditDept');
            deptEl.value = String(row.department_id);
            syncFcMasterDeptChoicesValue(deptEl);
            document.getElementById('masterEditMenuid').value = row.menuid;
            document.getElementById('masterEditMenun').value = row.menun;
            var cSel = document.getElementById('masterEditCcode');
            var cVal = (row.ccode || '').trim();
            cSel.querySelectorAll('option[data-fc-master-ccode-fallback]').forEach(function (el) { el.remove(); });
            if (cVal && !Array.prototype.some.call(cSel.options, function (o) { return o.value === cVal; })) {
                var o = document.createElement('option');
                o.value = cVal;
                o.textContent = cVal + ' (current)';
                o.setAttribute('data-fc-master-ccode-fallback', '1');
                cSel.appendChild(o);
            }
            cSel.value = cVal || '';
            document.getElementById('masterEditSort').value = row.sort_order;
            document.getElementById('masterEditStatus').value = String(row.status);
            document.getElementById('masterEditPolicy').value = row.entry_policy;
        });
    }
})();

$(function () {
    initFcMasterDeptChoices();

    // Pre-submit validation for department (Choices.js hides the native <select> so
    // the browser cannot focus it for required validation — we guard in JS instead).
    document.getElementById('modalMasterCreate')?.querySelector('form')?.addEventListener('submit', function (e) {
        var deptEl = document.getElementById('masterCreateDept');
        if (deptEl && !deptEl.value) {
            e.preventDefault();
            deptEl.closest('.col-md-6').querySelector('.choices__inner').style.borderColor = 'var(--bs-danger)';
            deptEl.closest('.col-md-6').insertAdjacentHTML('beforeend',
                '<div class="invalid-feedback d-block js-dept-err">Please select a department.</div>');
        }
    });
    document.getElementById('formMasterEdit')?.addEventListener('submit', function (e) {
        var deptEl = document.getElementById('masterEditDept');
        var errEl = document.getElementById('formMasterEdit').querySelector('.js-dept-err');
        if (errEl) errEl.remove();
        if (deptEl && !deptEl.value) {
            e.preventDefault();
            deptEl.closest('.col-md-6').querySelector('.choices__inner').style.borderColor = 'var(--bs-danger)';
            deptEl.closest('.col-md-6').insertAdjacentHTML('beforeend',
                '<div class="invalid-feedback d-block js-dept-err">Please select a department.</div>');
        }
    });

    // Re-open modal on validation error (DOMContentLoaded is already fired at this point
    // in @@push scripts, so we must use jQuery ready which handles the already-ready case).
    if (@json($errors->any() && old('_form') === 'master_create')) {
        var mc = document.getElementById('modalMasterCreate');
        if (mc && window.bootstrap) new bootstrap.Modal(mc).show();
    }
    if (@json($errors->any() && old('_form') === 'master_edit')) {
        var me = document.getElementById('modalMasterEdit');
        if (me && window.bootstrap) new bootstrap.Modal(me).show();
    }

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
        columnDefs: [
            { targets: -1, className: 'text-end text-nowrap fc-master-action-col', width: '5.25rem' }
        ],
        columns: [
            { data: 'dept_name', name: 'dept_name', title: 'Dept', orderable: false },
            { data: 'menuid', name: 'menuid', title: 'Code' },
            { data: 'menun', name: 'menun', title: 'Label' },
            { data: 'ccode', name: 'ccode', title: 'Course' },
            { data: 'sort_order', name: 'sort_order', title: 'Order' },
            { data: 'status_display', name: 'status_display', title: 'Status', orderable: false, searchable: false },
            { data: 'entry_policy', name: 'entry_policy', title: 'Policy' },
            { data: 'action', name: 'action', title: 'Action', orderable: false, searchable: false }
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
