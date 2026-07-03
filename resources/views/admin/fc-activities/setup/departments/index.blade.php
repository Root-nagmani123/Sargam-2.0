@extends('admin.layouts.master')
@section('title', 'Activity departments')
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
    <style>
        /* FC department staff multiselect — same stack as admin/mess/process-mess-bills-employee (Choices.js) */
        .fc-dept-setup-scope .choices { margin-bottom: 0; font-size: 0.875rem; max-width: 100%; }
        .fc-dept-setup-scope .choices .choices__inner {
            min-height: calc(1.5em + 0.5rem + 2px);
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius);
        }
        .fc-dept-setup-scope .choices.is-focused .choices__inner,
        .fc-dept-setup-scope .choices.is-open .choices__inner {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.2);
        }
        .fc-dept-setup-scope .choices__list--dropdown,
        .fc-dept-setup-scope .choices__list[aria-expanded] {
            z-index: 2050;
            border-radius: var(--bs-border-radius);
            box-shadow: var(--bs-box-shadow);
        }
        .fc-dept-setup-scope .choices__list--dropdown .choices__item--selectable.is-highlighted,
        .fc-dept-setup-scope .choices__list[aria-expanded] .choices__item--selectable.is-highlighted {
            background-color: var(--bs-primary-bg-subtle);
            color: var(--bs-primary);
        }
        .fc-dept-setup-scope .choices[data-type*="select-multiple"] .choices__button {
            border-left: 1px solid rgba(0, 0, 0, 0.12);
            margin-left: 0.35rem;
            padding-left: 0.35rem;
        }
        /* Staff + Action: compact columns, right-aligned counts — avoids a wide empty band before icons */
        .fc-dept-setup-scope .fc-dept-staff-col,
        .fc-dept-setup-scope .fc-dept-action-col {
            white-space: nowrap;
        }
    </style>
@endpush
@section('setup_content')
<div class="fc-dept-setup-scope">
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
            <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Sort</th><th>Status</th><th>Activities</th><th class="text-end fc-dept-staff-col">Staff</th><th class="text-end fc-dept-action-col">Action</th></tr></thead>
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
                    <p class="small text-muted mb-2"><span class="text-danger" aria-hidden="true">*</span> Required fields.</p>
                    <div class="mb-2">
                        <label class="form-label small" for="deptCreateCode">Code (slug) <span class="text-danger" title="Required">*</span></label>
                        <input name="code" id="deptCreateCode" class="form-control form-control-sm @error('code') is-invalid @enderror" value="{{ old('_form') === 'dept_create' ? old('code') : '' }}" required aria-required="true" pattern="[a-z0-9_]+" placeholder="e.g. medical" title="Lowercase letters, digits, underscores only" aria-describedby="deptCreateCodeHelp">
                        <div id="deptCreateCodeHelp" class="form-text">Short machine-friendly code: lowercase letters, digits, and underscores only (no spaces). Used as the department key in the system; choose something stable, e.g. <code class="small">medical</code> or <code class="small">it_support</code>.</div>
                        @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="deptCreateName">Name <span class="text-danger" title="Required">*</span></label>
                        <input name="name" id="deptCreateName" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('_form') === 'dept_create' ? old('name') : '' }}" required aria-required="true" maxlength="150">
                        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="deptCreateSort">Sort order</label>
                        <input type="number" name="sort_order" id="deptCreateSort" class="form-control form-control-sm @error('sort_order') is-invalid @enderror" value="{{ old('_form') === 'dept_create' ? old('sort_order', 0) : 0 }}" min="0">
                        @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label small" for="deptCreateUsers">Assigned staff (Post-Arrival Activities)</label>
                        <select name="assigned_user_pks[]" id="deptCreateUsers" class="form-select form-select-sm choices-select fc-dept-staff-choices" multiple data-placeholder="Search and select staff…" data-search="true">
                            @foreach($staffForAssignment as $su)
                                @php
                                    $staffLabel = trim(($su->first_name ?? '').' '.($su->last_name ?? '')) ?: $su->user_name;
                                @endphp
                                <option value="{{ $su->pk }}" data-login="{{ e($su->user_name) }}" @selected(in_array((int) $su->pk, array_map('intval', (array) old('assigned_user_pks', [])), true))>{{ $staffLabel }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Search by name or login. Chips show the display name. Only selected users see this department’s activities on the main FC Activities page.</div>
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
                    <p class="small text-muted mb-2"><span class="text-danger" aria-hidden="true">*</span> Required fields.</p>
                    <div class="mb-2">
                        <label class="form-label small" for="deptEditCode">Code <span class="text-danger" title="Required">*</span></label>
                        <input name="code" id="deptEditCode" class="form-control form-control-sm @error('code') is-invalid @enderror" value="{{ old('_form') === 'dept_edit' ? old('code') : '' }}" required aria-required="true" pattern="[a-z0-9_]+" title="Lowercase letters, digits, underscores only">
                        @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="deptEditName">Name <span class="text-danger" title="Required">*</span></label>
                        <input name="name" id="deptEditName" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('_form') === 'dept_edit' ? old('name') : '' }}" required aria-required="true" maxlength="150">
                        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="deptEditSort">Sort order</label>
                        <input type="number" name="sort_order" id="deptEditSort" class="form-control form-control-sm @error('sort_order') is-invalid @enderror" value="{{ old('_form') === 'dept_edit' ? old('sort_order', 0) : 0 }}" min="0">
                        @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="deptEditStatus">Status <span class="text-danger" title="Required">*</span></label>
                        <select name="status" id="deptEditStatus" class="form-select form-select-sm @error('status') is-invalid @enderror" required aria-required="true">
                            <option value="1" @selected(old('_form') === 'dept_edit' && (string)old('status', '1') === '1')>Active</option>
                            <option value="0" @selected(old('_form') === 'dept_edit' && (string)old('status') === '0')>Inactive</option>
                        </select>
                        @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label small" for="deptEditUsers">Assigned staff (Post-Arrival Activities)</label>
                        <select name="assigned_user_pks[]" id="deptEditUsers" class="form-select form-select-sm choices-select fc-dept-staff-choices" multiple data-placeholder="Search and select staff…" data-search="true">
                            @foreach($staffForAssignment as $su)
                                @php
                                    $staffLabel = trim(($su->first_name ?? '').' '.($su->last_name ?? '')) ?: $su->user_name;
                                @endphp
                                <option value="{{ $su->pk }}" data-login="{{ e($su->user_name) }}" @selected(old('_form') === 'dept_edit' && in_array((int) $su->pk, array_map('intval', (array) old('assigned_user_pks', [])), true))>{{ $staffLabel }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Search by name or login. Chips show the display name.</div>
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

</div>

@include('admin.fc-activities.partials.datatable-tools')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
(function () {
    function normalizeChoicesSearchText(text) {
        return String(text || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function applyFcDeptStaffChoicesSearchFilter(instance, rawQuery) {
        if (!instance || !instance.dropdown || !instance.dropdown.element) return;
        var selectEl = instance.passedElement && instance.passedElement.element ? instance.passedElement.element : null;
        if (!selectEl) return;
        var dropdownEl = instance.dropdown.element;
        var query = normalizeChoicesSearchText(rawQuery);
        var choiceItems = dropdownEl.querySelectorAll('.choices__item--choice');
        if (!choiceItems || !choiceItems.length) return;

        choiceItems.forEach(function (item) {
            if (item.classList.contains('choices__placeholder')) return;
            var label = normalizeChoicesSearchText(item.textContent || '');
            var value = normalizeChoicesSearchText(item.getAttribute('data-value') || '');
            var login = '';
            var dv = item.getAttribute('data-value');
            if (dv) {
                var opt = null;
                for (var oi = 0; oi < selectEl.options.length; oi++) {
                    if (String(selectEl.options[oi].value) === String(dv)) {
                        opt = selectEl.options[oi];
                        break;
                    }
                }
                if (opt && opt.dataset && opt.dataset.login) {
                    login = normalizeChoicesSearchText(opt.dataset.login);
                }
            }
            var show = !query || label.indexOf(query) !== -1 || value.indexOf(query) !== -1 || login.indexOf(query) !== -1;
            item.style.display = show ? '' : 'none';
        });
    }

    function destroyDeptStaffChoices(el) {
        if (!el) return;
        if (el.choicesInstance) {
            try {
                el.choicesInstance.destroy();
            } catch (e) {}
            el.choicesInstance = null;
        }
        el.dataset.choicesInitialized = 'false';
    }

    function initDeptStaffChoices(el) {
        if (!el || typeof window.Choices === 'undefined') return;
        if (el.dataset.choicesInitialized === 'true') return;

        var placeholder = el.getAttribute('data-placeholder') || 'Search…';
        var useCustomSearch = el.getAttribute('data-search') !== 'false';

        var instance = new Choices(el, {
            searchEnabled: true,
            searchChoices: !useCustomSearch,
            removeItemButton: true,
            itemSelectText: '',
            shouldSort: false,
            position: 'bottom',
            placeholder: true,
            placeholderValue: placeholder,
            allowHTML: false,
            closeDropdownOnSelect: false
        });

        if (instance.containerOuter && instance.containerOuter.element && instance.containerOuter.element.classList) {
            instance.containerOuter.element.classList.add('ts-wrapper');
        }
        if (instance.dropdown && instance.dropdown.element && instance.dropdown.element.classList) {
            instance.dropdown.element.classList.add('ts-dropdown');
        }

        if (useCustomSearch) {
            function applySearchFilterAfterRender() {
                var typed = (instance.input && instance.input.element) ? (instance.input.element.value || '') : '';
                requestAnimationFrame(function () {
                    applyFcDeptStaffChoicesSearchFilter(instance, typed);
                });
            }
            el.addEventListener('showDropdown', applySearchFilterAfterRender);
            if (instance.input && instance.input.element) {
                instance.input.element.addEventListener('input', applySearchFilterAfterRender);
                instance.input.element.addEventListener('keyup', applySearchFilterAfterRender);
            }
        }

        el.dataset.choicesInitialized = 'true';
        el.choicesInstance = instance;
    }

    function setStaffMultiSelectValues(selectEl, pkList) {
        if (!selectEl) return;
        var set = {};
        (pkList || []).forEach(function (v) {
            set[String(v)] = true;
        });
        Array.from(selectEl.options).forEach(function (o) {
            o.selected = !!set[o.value];
        });
    }

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

    var modalDeptCreate = document.getElementById('modalDeptCreate');
    if (modalDeptCreate) {
        modalDeptCreate.addEventListener('hidden.bs.modal', function () {
            destroyDeptStaffChoices(document.getElementById('deptCreateUsers'));
        });
        modalDeptCreate.addEventListener('shown.bs.modal', function () {
            var el = document.getElementById('deptCreateUsers');
            destroyDeptStaffChoices(el);
            initDeptStaffChoices(el);
        });
    }

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
            var usersEl = document.getElementById('deptEditUsers');
            destroyDeptStaffChoices(usersEl);
            var chosen = (row.assigned_user_pks || []).map(function (x) { return String(x); });
            setStaffMultiSelectValues(usersEl, chosen);
        });
        modalDeptEdit.addEventListener('hidden.bs.modal', function () {
            destroyDeptStaffChoices(document.getElementById('deptEditUsers'));
        });
        modalDeptEdit.addEventListener('shown.bs.modal', function () {
            var el = document.getElementById('deptEditUsers');
            destroyDeptStaffChoices(el);
            initDeptStaffChoices(el);
        });
    }
})();

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
        scrollX: false,
        autoWidth: false,
        dom: '<"row align-items-center mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row align-items-center mt-2"<"col-md-5"i><"col-md-7"p>>',
        columnDefs: [
            { targets: 5, className: 'text-end text-nowrap fc-dept-staff-col', width: '4rem' },
            { targets: -1, className: 'text-end text-nowrap fc-dept-action-col', width: '5.25rem' }
        ],
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
