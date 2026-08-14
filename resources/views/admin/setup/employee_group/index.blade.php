@extends('admin.layouts.master')
@section('title', 'Employee Group ')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Employee Groups</h4>
                <a href="{{ route('admin.setup.employee_group.create') }}" class="btn btn-primary" id="openCreateEmployeeGroup">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Add Employee Group
                </a>
            </div>
            {{-- Toolbar (new-design-index-page.md §2): search right-aligned; this
                 grid has no filters, so the row carries the search alone. --}}
            <x-programme-dt-toolbar :action="url()->current()"
                placeholder="Search group" label="Search by employee group name" />

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" data-sargam-dt-ui="false" id="employeeGroupTable">
                    <thead>
                        <tr>
                            <th style="width:70px;">S.No.</th>
                            <th>Employee Group Name</th>
                            <th style="width:160px;">Actions</th>
                            <th style="width:110px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employeeGroups as $index => $eg)
                            <tr data-pk="{{ $eg->pk }}">
                                <td>{{ $employeeGroups->firstItem() + $index }}</td>
                                <td>{{ $eg->emp_group_name }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.setup.employee_group.edit', encrypt($eg->pk)) }}" class="text-success openEditEmployeeGroup" title="Edit">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                        </a>
                                        <form action="{{ route('admin.setup.employee_group.delete', encrypt($eg->pk)) }}" method="POST" onsubmit="return confirm('Delete this Employee Group?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 text-danger" title="Delete"><i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                             <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                 data-table="employee_group_master" data-column="active_inactive" data-id="{{ $eg->pk }}" {{ $eg->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No Employee Groups found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-programme-dt-footer :paginator="$employeeGroups" per-page-id="empGroupPerPage" />
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="employeeGroupModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#af2910;">
                <h5 class="modal-title text-white">Employee Group</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-4 placeholder-loading d-none">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('employeeGroupModal');
    const modalBody = modalEl.querySelector('.modal-body');
    const modalTitle = modalEl.querySelector('.modal-title');

    function loadForm(url, title){
        modalTitle.textContent = title || 'Employee Group';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }})
            .then(r => r.text())
            .then(html => { modalBody.innerHTML = html; })
            .catch(()=>{ modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>'; });
        (new bootstrap.Modal(modalEl)).show();
    }

    document.getElementById('openCreateEmployeeGroup')?.addEventListener('click', e => {
        e.preventDefault();
        loadForm(e.currentTarget.getAttribute('href'), 'Create Employee Group');
    });

    document.querySelectorAll('.openEditEmployeeGroup').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            loadForm(e.currentTarget.getAttribute('href'), 'Edit Employee Group');
        });
    });

    modalEl.addEventListener('submit', function(e){
        const form = e.target;
        if(form.tagName !== 'FORM') return;
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        if(submitBtn) submitBtn.disabled = true;
        fetch(form.action, { method: form.method || 'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:new FormData(form) })
            .then(async res => {
                if(res.status === 422){
                    const html = await res.text();
                    modalBody.innerHTML = html; return;
                }
                if(!res.ok){ throw new Error('Save failed'); }
                const data = await res.json();
                if(data && data.success){ updateTable(data); bootstrap.Modal.getInstance(modalEl)?.hide(); }
            })
            .catch(()=>{ modalBody.insertAdjacentHTML('afterbegin','<div class="alert alert-danger">Error saving.</div>'); })
            .finally(()=>{ if(submitBtn) submitBtn.disabled = false; });
    });

    function buildEditUrl(encrypted){ return `${window.location.origin}/admin/setup/employee-group/edit/${encodeURIComponent(encrypted)}`; }

    function updateTable(payload){
        if(!payload || !payload.data) return;
        const { action, data } = payload;
        const tbody = document.querySelector('#employeeGroupTable tbody');
        if(!tbody) return;
        if(action === 'create'){
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-pk', data.pk);
            newRow.innerHTML = `
                <td>1</td>
                <td>${escapeHtml(data.emp_group_name)}</td>
                <td>
                    <div class=\"d-flex gap-2\">
                        <a href=\"${buildEditUrl(data.encrypted_pk)}\" class=\"text-success openEditEmployeeGroup\" title=\"Edit\"><i class=\"material-icons material-symbols-rounded\" style=\"font-size:22px;\">edit</i></a>
                        <form action=\"${window.location.origin}/admin/setup/employee-group/delete/${encodeURIComponent(data.encrypted_pk)}\" method=\"POST\" onsubmit=\"return confirm('Delete this Employee Group?')\">
                            <input type=\"hidden\" name=\"_token\" value=\"{{ csrf_token() }}\">
                            <input type=\"hidden\" name=\"_method\" value=\"DELETE\">
                            <button type=\"submit\" class=\"btn btn-link p-0 text-danger\" title=\"Delete\"><i class=\"material-icons material-symbols-rounded\" style=\"font-size:22px;\">delete</i></button>
                        </form>
                    </div>
                </td>
                <td><div class=\"form-check form-switch d-inline-block\"><input class=\"form-check-input status-toggle\" type=\"checkbox\" role=\"switch\" data-table=\"employee_group_master\" data-column=\"active_inactive\" data-id=\"${data.pk}\" checked></div></td>`;
            tbody.prepend(newRow);
            reindexSerials(tbody);
            newRow.querySelector('.openEditEmployeeGroup')?.addEventListener('click', interceptEdit);
        } else if(action === 'update') {
            const row = tbody.querySelector(`tr[data-pk='${data.pk}']`);
            if(row){ row.querySelectorAll('td')[1].textContent = data.emp_group_name; }
        }
    }

    function reindexSerials(tbody){ Array.from(tbody.querySelectorAll('tr')).forEach((r,i)=>{ const cell=r.querySelector('td'); if(cell) cell.textContent=i+1; }); }
    function interceptEdit(e){ e.preventDefault(); loadForm(this.getAttribute('href'),'Edit Employee Group'); }
    function escapeHtml(str){ return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
});
</script>
@endpush