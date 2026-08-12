@extends('admin.layouts.master')
@section('title', 'Department Master - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Departments</h4>
                <a href="{{ route('admin.setup.department_master.create') }}" class="btn btn-primary" id="openCreateDepartmentMaster">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Add Department Master
                </a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0" id="departmentMasterTable">
                    <thead>
                        <tr>
                            <th style="width:70px;">S.No.</th>
                            <th>Department Name</th>
                            <th style="width:160px;">Actions</th>
                            <th style="width:110px;">Status</th>
                        </tr>
                    </thead>
                    {{-- Rows come from the server-side DataTable (see script below). --}}
                        <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="departmentMasterModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#af2910;">
                <h5 class="modal-title text-white">Department</h5>
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
    const modalEl = document.getElementById('departmentMasterModal');
    const modalBody = modalEl.querySelector('.modal-body');
    const modalTitle = modalEl.querySelector('.modal-title');

    // Server-side grid: search, sort and paging are resolved in SQL.
    const table = $('#departmentMasterTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: "{{ route('admin.setup.department_master.index') }}", type: 'GET' },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false }
        ],
        order: [],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: 'Loading data…',
            emptyTable: 'No Department found.'
        }
    });

    function loadForm(url, title){
        modalTitle.textContent = title || 'Department';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }})
            .then(r => r.text())
            .then(html => { modalBody.innerHTML = html; })
            .catch(()=>{ modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>'; });
        (new bootstrap.Modal(modalEl)).show();
    }

    document.getElementById('openCreateDepartmentMaster')?.addEventListener('click', e => {
        e.preventDefault();
        loadForm(e.currentTarget.getAttribute('href'), 'Create Department');
    });

    // Delegated: rows are re-rendered by the grid on every draw.
    document.addEventListener('click', e => {
        const link = e.target.closest('.openEditDepartment');
        if (!link) return;
        e.preventDefault();
        loadForm(link.getAttribute('href'), 'Edit Department');
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
                if(data && data.success){
                    // Rows live on the server now, so pull the fresh page.
                    table.ajax.reload(null, false);
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                }
            })
            .catch(()=>{ modalBody.insertAdjacentHTML('afterbegin','<div class="alert alert-danger">Error saving.</div>'); })
            .finally(()=>{ if(submitBtn) submitBtn.disabled = false; });
    });
});
</script>
@endpush