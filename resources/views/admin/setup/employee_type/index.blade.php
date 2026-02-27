@extends('admin.layouts.master')
@section('title', 'Employee Type - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Employee Types</h4>
                <a href="{{ route('admin.setup.employee_type.create') }}" class="btn btn-primary" id="openCreateEmployeeType">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Add Employee Type
                </a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0" id="employeeTypeTable">
                    <thead>
                        <tr>
                            <th style="width:70px;">S.No.</th>
                            <th>Category Type Name</th>
                            <th style="width:160px;">Actions</th>
                            <th style="width:110px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employeeTypes as $index => $et)
                            <tr data-pk="{{ $et->pk }}">
                                <td>{{ $employeeTypes->firstItem() + $index }}</td>
                                <td>{{ $et->category_type_name }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.setup.employee_type.edit', encrypt($et->pk)) }}" class="text-success openEditEmployeeType" title="Edit">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                        </a>
                                        <form action="{{ route('admin.setup.employee_type.delete', encrypt($et->pk)) }}" method="POST" onsubmit="return confirm('Delete this Employee Type?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 text-danger" title="Delete"><i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                               data-table="employee_type_master" data-column="active_inactive" data-id="{{ $et->pk }}" {{ $et->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No Employee Types found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                <div class="small text-muted mb-2">Showing {{ $employeeTypes->firstItem() }} to {{ $employeeTypes->lastItem() }} of {{ $employeeTypes->total() }} items</div>
                <div>{{ $employeeTypes->links('pagination::bootstrap-5') }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="employeeTypeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#004a93;">
                <h5 class="modal-title text-white">Employee Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- AJAX form will load here -->
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
    const modalEl = document.getElementById('employeeTypeModal');
    const modalBody = modalEl.querySelector('.modal-body');
    const modalTitle = modalEl.querySelector('.modal-title');

    function loadForm(url, title){
        modalTitle.textContent = title || 'Employee Type';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }})
            .then(r => r.text())
            .then(html => { modalBody.innerHTML = html; })
            .catch(()=>{ modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>'; });
        (new bootstrap.Modal(modalEl)).show();
    }

    document.getElementById('openCreateEmployeeType')?.addEventListener('click', e => {
        e.preventDefault();
        loadForm(e.currentTarget.getAttribute('href'), 'Create Employee Type');
    });

    document.querySelectorAll('.openEditEmployeeType').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            loadForm(e.currentTarget.getAttribute('href'), 'Edit Employee Type');
        });
    });

    // Delegate submit inside modal
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

    function buildEditUrl(encrypted){ return `${window.location.origin}/admin/setup/employee-type/edit/${encodeURIComponent(encrypted)}`; }

    function updateTable(payload){
        if(!payload || !payload.data) return;
        const { action, data } = payload;
        const tbody = document.querySelector('#employeeTypeTable tbody');
        if(!tbody) return;
        if(action === 'create'){
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-pk', data.pk);
            newRow.innerHTML = `
                <td>${tbody.querySelectorAll('tr').length ? (parseInt(tbody.querySelector('tr td').textContent) - 1) : 1}</td>
                <td>${escapeHtml(data.category_type_name)}</td>
                <td>
                    <div class=\"d-flex gap-2\">
                        <a href=\"${buildEditUrl(data.encrypted_pk)}\" class=\"text-success openEditEmployeeType\" title=\"Edit\"><i class=\"material-icons material-symbols-rounded\" style=\"font-size:22px;\">edit</i></a>
                        <form action=\"${window.location.origin}/admin/setup/employee-type/delete/${encodeURIComponent(data.encrypted_pk)}\" method=\"POST\" onsubmit=\"return confirm('Delete this Employee Type?')\">
                            <input type=\"hidden\" name=\"_token\" value=\"{{ csrf_token() }}\">
                            <input type=\"hidden\" name=\"_method\" value=\"DELETE\">
                            <button type=\"submit\" class=\"btn btn-link p-0 text-danger\" title=\"Delete\"><i class=\"material-icons material-symbols-rounded\" style=\"font-size:22px;\">delete</i></button>
                        </form>
                    </div>
                </td>
                <td><div class=\"form-check form-switch d-inline-block\"><input class=\"form-check-input status-toggle\" type=\"checkbox\" role=\"switch\" data-table=\"employee_type_master\" data-column=\"active_inactive\" data-id=\"${data.pk}\" checked></div></td>`;
            tbody.prepend(newRow);
            reindexSerials(tbody);
            newRow.querySelector('.openEditEmployeeType').addEventListener('click', interceptEdit);
        } else if(action === 'update') {
            const row = tbody.querySelector(`tr[data-pk='${data.pk}']`);
            if(row){ row.querySelectorAll('td')[1].textContent = data.category_type_name; }
        }
    }

    function reindexSerials(tbody){
        Array.from(tbody.querySelectorAll('tr')).forEach((r,i)=>{ const cell = r.querySelector('td'); if(cell) cell.textContent = i+1; });
    }

    function interceptEdit(e){ e.preventDefault(); loadForm(this.getAttribute('href'),'Edit Employee Type'); }

    function escapeHtml(str){ return str.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
});
</script>
@endpush