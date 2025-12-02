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
                            </div>
                        </div>
                    </div>
                    <hr>

                    {!! $dataTable->table(['class' => 'table table-striped table-bordered']) !!}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
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
    // Reload DataTable after create/update
    if (typeof $.fn.DataTable !== 'undefined') {
        const table = $('#mdodutytypemaster-table').DataTable();
        if (table) {
            table.ajax.reload(null, false); // false = don't reset pagination
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