@extends('admin.layouts.master')
@section('title', 'Designation Master - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Designations Master</h4>
                <a href="{{ route('admin.setup.designation_master.create') }}" class="btn btn-primary" id="openCreateDesignationMaster">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Add Designation
                </a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0" id="designationMasterTable">
                    <thead>
                        <tr>
                            <th style="width:70px;">S.No.</th>
                            <th>Designation Name</th>
                            <th style="width:160px;">Actions</th>
                            <th style="width:110px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($designations as $index => $des)
                            <tr data-pk="{{ $des->pk }}">
                                <td>{{ $designations->firstItem() + $index }}</td>
                                <td>{{ $des->designation_name }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.setup.designation_master.edit', encrypt($des->pk)) }}" class="text-success openEditDesignation" title="Edit">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                        </a>
                                        <form action="{{ route('admin.setup.designation_master.delete', encrypt($des->pk)) }}" method="POST" onsubmit="return confirm('Delete this Designation?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 text-danger" title="Delete"><i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                             <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                 data-table="designation_master" data-column="active_inactive" data-id="{{ $des->pk }}" {{ $des->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No Designations found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                <div class="small text-muted mb-2">Showing {{ $designations->firstItem() }} to {{ $designations->lastItem() }} of {{ $designations->total() }} items</div>
                <div>{{ $designations->links('pagination::bootstrap-5') }}</div>
            </div>
        </div>
    </div>
</div>

        <!-- Modal -->
        <div class="modal fade" id="designationMasterModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header" style="background:#af2910;">
                        <h5 class="modal-title text-white">Designation</h5>
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
            const modalEl = document.getElementById('designationMasterModal');
            const modalBody = modalEl.querySelector('.modal-body');
            const modalTitle = modalEl.querySelector('.modal-title');

            function loadForm(url, title){
                modalTitle.textContent = title || 'Designation';
                modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }})
                    .then(r => r.text())
                    .then(html => { modalBody.innerHTML = html; })
                    .catch(()=>{ modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>'; });
                (new bootstrap.Modal(modalEl)).show();
            }

            document.getElementById('openCreateDesignationMaster')?.addEventListener('click', e => {
                e.preventDefault();
                loadForm(e.currentTarget.getAttribute('href'),'Create Designation');
            });

            document.querySelectorAll('.openEditDesignation').forEach(link => {
                link.addEventListener('click', e => {
                    e.preventDefault();
                    loadForm(e.currentTarget.getAttribute('href'),'Edit Designation');
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

            function buildEditUrl(encrypted){ return `${window.location.origin}/admin/setup/designation-master/edit/${encodeURIComponent(encrypted)}`; }
            function escapeHtml(str){ return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]); }
            function reindexSerials(tbody){ Array.from(tbody.querySelectorAll('tr')).forEach((r,i)=>{ const cell=r.querySelector('td'); if(cell) cell.textContent=i+1; }); }

            function updateTable(payload){
                if(!payload || !payload.data) return;
                const { action, data } = payload;
                const tbody = document.querySelector('#designationMasterTable tbody');
                if(!tbody) return;
                if(action === 'create'){
                    const newRow = document.createElement('tr');
                    newRow.setAttribute('data-pk', data.pk);
                    newRow.innerHTML = `
                        <td>1</td>
                        <td>${escapeHtml(data.designation_name)}</td>
                        <td>
                            <div class=\"d-flex gap-2\">
                                <a href=\"${buildEditUrl(data.encrypted_pk)}\" class=\"text-success openEditDesignation\" title=\"Edit\"><i class=\"material-icons material-symbols-rounded\" style=\"font-size:22px;\">edit</i></a>
                                <form action=\"${window.location.origin}/admin/setup/designation-master/delete/${encodeURIComponent(data.encrypted_pk)}\" method=\"POST\" onsubmit=\"return confirm('Delete this Designation?')\">
                                    <input type=\"hidden\" name=\"_token\" value=\"{{ csrf_token() }}\">
                                    <input type=\"hidden\" name=\"_method\" value=\"DELETE\">
                                    <button type=\"submit\" class=\"btn btn-link p-0 text-danger\" title=\"Delete\"><i class=\"material-icons material-symbols-rounded\" style=\"font-size:22px;\">delete</i></button>
                                </form>
                            </div>
                        </td>
                        <td><div class=\"form-check form-switch d-inline-block\"><input class=\"form-check-input status-toggle\" type=\"checkbox\" role=\"switch\" data-table=\"designation_master\" data-column=\"active_inactive\" data-id=\"${data.pk}\" checked></div></td>`;
                    tbody.prepend(newRow);
                    reindexSerials(tbody);
                    newRow.querySelector('.openEditDesignation')?.addEventListener('click', interceptEdit);
                } else if(action === 'update') {
                    const row = tbody.querySelector(`tr[data-pk='${data.pk}']`);
                    if(row){ row.querySelectorAll('td')[1].textContent = data.designation_name; }
                }
            }

            function interceptEdit(e){ e.preventDefault(); loadForm(this.getAttribute('href'),'Edit Designation'); }
        });
        </script>
        @endpush