@extends('admin.layouts.master')

@section('title', 'MDO Duty Type')

@section('content')
<div class="container-fluid">
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <!-- left column empty or header title above -->
                            <h4>MDO Duty Type</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">

                                <!-- Add Group Mapping -->
                                <a href="{{route('master.mdo_duty_type.create')}}" id="openCreateDutyType"
                                    class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#mdoDutyTypeModal">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">add</i>
                                    Add MDO Duty Type
                                </a>
                                <!-- Search Expand -->
                                <div class="search-expand d-flex align-items-center">
                                    <a href="javascript:void(0)" id="searchToggle">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 24px;">search</i>
                                    </a>

                                    <input type="text" class="form-control search-input ms-2" id="searchInput"
                                        placeholder="Search…" aria-label="Search">
                                </div>

                            </div>
                        </div>
                    </div>
                    <hr>
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="col">S.No.</th>
                                <th class="col">Duty Type Name</th>
                                <th class="col">Actions</th>
                                <th class="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mdoDutyTypes as $index => $duty)
                            <tr data-pk="{{ $duty->pk }}">
                                <td>{{ $mdoDutyTypes->firstItem() + $index }}</td>
                                <td>{{ $duty->mdo_duty_type_name }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('master.mdo_duty_type.edit', encrypt($duty->pk)) }}"
                                            title="Edit" class="openEditDutyType" data-id="{{ encrypt($duty->pk) }}" data-bs-toggle="modal" data-bs-target="#mdoDutyTypeModal">
                                            <i class="material-icons material-symbols-rounded"
                                                style="font-size:24px">edit</i>
                                        </a>
                                        <form action="{{ route('master.mdo_duty_type.delete', encrypt($duty->pk)) }}"
                                            method="POST" onsubmit="return confirm('Delete this duty type?')">
                                            @csrf
                                            <button type="submit" class="btn btn-link p-0" title="Delete">
                                                <i class="material-icons material-symbols-rounded" style="font-size:24px">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                   data-table="mdo_duty_type_master" data-column="active_inactive" data-id="{{ $duty->pk }}" {{ ($duty->active_inactive == 1 ? 'checked' : '') }}>
                        </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No duty types found</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                        <div class="text-muted small mb-2">
                            Showing {{ $mdoDutyTypes->firstItem() }}
                            to {{ $mdoDutyTypes->lastItem() }}
                            of {{ $mdoDutyTypes->total() }} items
                        </div>

                        <div>
                            {{ $mdoDutyTypes->links('pagination::bootstrap-5') }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection

@push('scripts')
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const createBtn = document.getElementById('openCreateDutyType');
    const editLinks = document.querySelectorAll('.openEditDutyType');

    function openModalWithUrl(url, title){
        const modalEl = document.getElementById('dutyTypeModal');
        const modalTitle = modalEl.querySelector('.modal-title');
        const modalBody = modalEl.querySelector('.modal-body');
        modalTitle.textContent = title || 'MDO Duty Type';
        modalBody.innerHTML = '<div class="text-center p-4">Loading...</div>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
            .then(res => res.text())
            .then(html => {
                modalBody.innerHTML = html;
            })
            .catch(() => {
                modalBody.innerHTML = '<div class="text-danger">Failed to load form.</div>';
            });

        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    }

    if(createBtn){
        createBtn.addEventListener('click', function(e){
            e.preventDefault();
            openModalWithUrl(this.getAttribute('href'), 'Create MDO Duty Type');
        });
    }

    editLinks.forEach(link => {
        link.addEventListener('click', function(e){
            e.preventDefault();
            openModalWithUrl(this.getAttribute('href'), 'Edit MDO Duty Type');
        });

        // Handle AJAX form submit inside modal
        document.getElementById('dutyTypeModal')?.addEventListener('submit', function(e){
            const form = e.target;
            if (form && form.tagName === 'FORM') {
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;

                fetch(form.action, {
                    method: form.method || 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form)
                })
                .then(async (res) => {
                    if (res.ok) {
                        // Try to parse JSON; fallback to text
                        const ct = res.headers.get('content-type') || '';
                        if (ct.includes('application/json')) {
                            const data = await res.json();
                            if (data.success || data.status === true) {
                                // Update table without full reload
                                updateTableAfterSave(data);
                                bootstrap.Modal.getInstance(document.getElementById('dutyTypeModal'))?.hide();
                                return;
                            }
                        }
                        // Non-JSON success fallback
                        updateTableAfterSave(null);
                        bootstrap.Modal.getInstance(document.getElementById('dutyTypeModal'))?.hide();
                    } else if (res.status === 422) {
                        // Validation errors: re-render returned HTML into modal
                        const html = await res.text();
                        const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                        modalBody.innerHTML = html;
                    } else {
                        const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                        modalBody.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger">Save failed. Please try again.</div>');
                    }
                })
                .catch(() => {
                    const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                    modalBody.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger">Network error. Please try again.</div>');
                })
                .finally(() => {
                    if (submitBtn) submitBtn.disabled = false;
                });
            }
        });
    });
});

function buildEditUrl(encryptedPk){
    return `${window.location.origin}/master/mdo_duty_type/edit/${encodeURIComponent(encryptedPk)}`;
}
function escapeHtml(str){
    if(typeof str !== 'string') return '';
    return str.replace(/[&<>"']/g, function(ch){
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[ch]);
    });
}
function interceptEditLink(e){
    e.preventDefault();
    openModalWithUrl(this.getAttribute('href'), 'Edit MDO Duty Type');
}
function updateTableAfterSave(payload){
    if(!payload || !payload.data){ return; }
    const { action, data } = payload;
    const tbody = document.querySelector('table tbody');
    if(!tbody) return;

    if(action === 'create') {
        // Insert new row at top
        const newRow = document.createElement('tr');
        newRow.setAttribute('data-pk', data.pk);
        newRow.innerHTML = `
            <td>1</td>
            <td>${escapeHtml(data.mdo_duty_type_name)}</td>
            <td>
                <div class=\"d-flex gap-2\">
                    <a href=\"${buildEditUrl(data.encrypted_pk)}\" title=\"Edit\" class=\" openEditDutyType\" data-id=\"${data.encrypted_pk}\">
                        <i class=\"material-icons material-symbols-rounded\" style=\"font-size:24px\">edit</i>
                    </a>
                    <form action=\"${window.location.origin + '/master/mdo_duty_type/delete/' + encodeURIComponent(data.encrypted_pk)}\" method=\"POST\" onsubmit=\"return confirm('Delete this duty type?')\">
                        <input type=\"hidden\" name=\"_token\" value=\"{{ csrf_token() }}\">
                        <button type=\"submit\" class=\"btn btn-link p-0\" title=\"Delete\">
                            <i class=\"material-icons material-symbols-rounded\" style=\"font-size:24px\">delete</i>
                        </button>
                    </form>
                </div>
            </td>
            <td>
                <div class=\"form-check form-switch d-inline-block\">
                    <input class=\"form-check-input status-toggle\" type=\"checkbox\" role=\"switch\" data-table=\"mdo_duty_type_master\" data-column=\"active_inactive\" data-id=\"${data.pk}\" ${data.active_inactive == 1 ? 'checked' : ''}>
                </div>
            </td>`;
        tbody.prepend(newRow);
        // Recalculate serial numbers for all rows
        Array.from(tbody.querySelectorAll('tr')).forEach((r,i)=>{
            const cell = r.querySelector('td');
            if(cell) cell.textContent = (i+1 + {{ $mdoDutyTypes->firstItem() }} - 1); // keep pagination base
        });
        // Bind edit link
        newRow.querySelector('.openEditDutyType')?.addEventListener('click', interceptEditLink);
    } else if(action === 'update') {
        // Find existing row by pk
        let targetRow = tbody.querySelector(`tr[data-pk='${data.pk}']`);
        if(targetRow){
            const tds = targetRow.querySelectorAll('td');
            if(tds[1]) tds[1].textContent = data.mdo_duty_type_name;
            // Status toggle remains; adjust checked state
            const statusInput = targetRow.querySelector('input.status-toggle');
            if(statusInput){ statusInput.checked = data.active_inactive == 1; }
        }
    }
}
</script>

<!-- Modal -->
<div class="modal fade" id="dutyTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">MDO Duty Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Form content will be loaded here via fetch -->
            </div>
        </div>
    </div>
</div>
@endpush