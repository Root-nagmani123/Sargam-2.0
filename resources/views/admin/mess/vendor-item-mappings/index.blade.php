@extends('admin.layouts.master')

@section('title', 'Vendor Mapping - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Vendor Mapping" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <div class="row">
                    <div class="col-6"><h4>Vendor Mapping</h4></div>
                    <div class="col-6">
                        <div class="d-flex justify-content-end mb-3">
                            <a href="{{ route('admin.mess.vendor-item-mappings.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm" id="openCreateVendorMapping">
                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
                                Add Vendor Mapping
                            </a>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-bordered table-hover align-middle w-100']) !!}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="vendorMappingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background: #004a93;">
                <h5 class="modal-title text-white">Vendor Mapping</h5>
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
{!! $dataTable->scripts() !!}
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('vendorMappingModal');
    var modalBody = modalEl.querySelector('.modal-body');
    var modalTitle = modalEl.querySelector('.modal-title');

    function initVendorMappingForm() {}

    function loadForm(url, title) {
        modalTitle.textContent = title || 'Vendor Mapping';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                modalBody.innerHTML = html;
                initVendorMappingForm();
            })
            .catch(function() { modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>'; });
        (new bootstrap.Modal(modalEl)).show();
    }

    document.getElementById('openCreateVendorMapping') && document.getElementById('openCreateVendorMapping').addEventListener('click', function(e) {
        e.preventDefault();
        loadForm(this.getAttribute('href'), 'Add Vendor Mapping');
    });

    document.addEventListener('click', function(e) {
        var link = e.target.closest('.openEditVendorMapping');
        if (link) {
            e.preventDefault();
            loadForm(link.getAttribute('href'), 'Edit Vendor Mapping');
        }
    });

    modalEl.addEventListener('submit', function(e) {
        var form = e.target;
        if (form.tagName !== 'FORM') return;
        e.preventDefault();
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
        fetch(form.action, {
            method: form.method || 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: new FormData(form)
        })
            .then(function(res) {
                if (res.status === 422) {
                    return res.text().then(function(html) {
                        modalBody.innerHTML = html;
                        initVendorMappingForm();
                    });
                }
                if (!res.ok) throw new Error('Save failed');
                return res.json();
            })
            .then(function(data) {
                if (data && data.success) {
                    if (data.reload) {
                        window.location.reload();
                    } else {
                        bootstrap.Modal.getInstance(modalEl) && bootstrap.Modal.getInstance(modalEl).hide();
                    }
                }
            })
            .catch(function() {
                modalBody.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger">Error saving. Please try again.</div>');
            })
            .finally(function() {
                if (submitBtn) submitBtn.disabled = false;
            });
    });
});
</script>
@endpush
