@extends('admin.layouts.master')

@section('title', 'Quick Links')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/quick-links-admin.css') }}?v={{ @filemtime(public_path('css/quick-links-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid ql-page py-4">
    <x-breadcrum title="Quick Links">
        <div class="d-flex flex-wrap align-items-center ql-toolbar-actions">
            <button type="button" id="quickLinksSaveOrder" disabled
                class="btn ql-btn-outline">
                <i class="bi bi-save" aria-hidden="true"></i>
                <span>Save Order</span>
            </button>
            <a href="{{ route('admin.setup.quick_links.create') }}" id="openCreateQuickLink"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Add Quick Link</span>
            </a>
        </div>
    </x-breadcrum>

    <x-session_message />

    <div class="card ql-dt-card shadow-sm rounded-3 overflow-hidden border-0">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="qlDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="quickLinksTable"></div>
            </div>

            <div class="programme-dt-panel ql-dt-panel">
                <div class="ql-table-outer">
                    <div class="table-responsive ql-dt-scroll">
                        <table class="table table-hover align-middle mb-0 programme-dt-table datatable border-0"
                            id="quickLinksTable"
                            data-export="false"
                            data-ordering="false">
                            <thead>
                                <tr>
                                    <th scope="col">S. No.</th>
                                    <th scope="col">Label</th>
                                    <th scope="col">URL</th>
                                    <th scope="col" style="width:90px;">Order</th>
                                    <th scope="col" style="width:120px;">Open in</th>
                                    <th scope="col" style="width:120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($quickLinks as $index => $link)
                                    <tr data-quicklink-id="{{ $link->id }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $link->label }}</td>
                                        <td title="{{ $link->url }}">{{ $link->url }}</td>
                                        <td>
                                            <span class="quicklink-drag-handle text-muted" draggable="true" title="Drag to reorder"
                                                style="cursor: grab;">
                                                <i class="bi bi-grip-vertical" aria-hidden="true"></i>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="ql-open-badge">{{ $link->target_blank ? 'New Tab' : 'Same Tab' }}</span>
                                        </td>
                                        <td>
                                            <div class="ql-actions">
                                                <a href="{{ route('admin.setup.quick_links.edit', encrypt($link->id)) }}"
                                                    class="openEditQuickLink ql-action-btn ql-action-edit"
                                                    title="Edit"
                                                    aria-label="Edit {{ $link->label }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </a>

                                                <form action="{{ route('admin.setup.quick_links.delete', encrypt($link->id)) }}"
                                                    method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Delete this quick link?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="ql-action-btn ql-action-delete"
                                                        title="Delete"
                                                        aria-label="Delete {{ $link->label }}">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="ql-empty-state text-center">
                                            <i class="bi bi-link-45deg display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                            <h5 class="fw-semibold text-dark mb-1">No Quick Links Found</h5>
                                            <p class="text-secondary mb-0">Add a quick link to get started.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="qlDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3"
                    data-dt-footer-for="quickLinksTable"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="quickLinksModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered ql-quicklinks-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0 fw-bold">Add Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-4 placeholder-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('quickLinksModal');
    const modalBody = modalEl.querySelector('.modal-body');
    const modalTitle = modalEl.querySelector('.modal-title');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const bulkReorderUrl = "{{ route('admin.setup.quick_links.bulk-reorder') }}";

    const quickLinksTable = document.getElementById('quickLinksTable');
    const quickLinksSaveOrder = document.getElementById('quickLinksSaveOrder');

    if (quickLinksTable && quickLinksSaveOrder) {
        const tbody = quickLinksTable.querySelector('tbody');
        let draggedRow = null;
        let initialOrder = [];

        const setDirty = (dirty) => {
            quickLinksSaveOrder.disabled = !dirty;
        };

        setDirty(false);

        if (tbody) {
            tbody.addEventListener('dragstart', (e) => {
                const handle = e.target.closest('.quicklink-drag-handle');
                if (!handle) return;
                draggedRow = handle.closest('tr[data-quicklink-id]');
                if (!draggedRow) return;

                initialOrder = Array.from(tbody.querySelectorAll('tr[data-quicklink-id]'))
                    .map(tr => parseInt(tr.dataset.quicklinkId, 10))
                    .filter(Boolean);

                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', draggedRow.dataset.quicklinkId);
                draggedRow.classList.add('dragging-quicklink-row');
            });

            tbody.addEventListener('dragend', () => {
                if (draggedRow) draggedRow.classList.remove('dragging-quicklink-row');
                draggedRow = null;
            });

            tbody.addEventListener('dragover', (e) => {
                e.preventDefault();
                const overRow = e.target.closest('tr[data-quicklink-id]');
                if (!overRow || !draggedRow) return;
                overRow.classList.add('drag-over-quicklink-row');
            });

            tbody.addEventListener('dragleave', (e) => {
                const row = e.target.closest('tr[data-quicklink-id]');
                if (row) row.classList.remove('drag-over-quicklink-row');
            });

            tbody.addEventListener('drop', (e) => {
                e.preventDefault();
                const dropRow = e.target.closest('tr[data-quicklink-id]');

                if (!draggedRow) return;

                tbody.querySelectorAll('tr.drag-over-quicklink-row').forEach(r => r.classList.remove('drag-over-quicklink-row'));

                if (!dropRow || dropRow === draggedRow) return;

                const rect = dropRow.getBoundingClientRect();
                if (e.clientY > rect.top + rect.height / 2) {
                    dropRow.after(draggedRow);
                } else {
                    dropRow.before(draggedRow);
                }

                const newOrder = Array.from(tbody.querySelectorAll('tr[data-quicklink-id]'))
                    .map(tr => parseInt(tr.dataset.quicklinkId, 10))
                    .filter(Boolean);

                const changed = initialOrder.length &&
                    JSON.stringify(initialOrder) !== JSON.stringify(newOrder);

                setDirty(!!changed);
            });
        }

        quickLinksSaveOrder.addEventListener('click', async () => {
            if (!tbody) return;
            const order = Array.from(tbody.querySelectorAll('tr[data-quicklink-id]'))
                .map(tr => parseInt(tr.dataset.quicklinkId, 10))
                .filter(Boolean);

            if (order.length < 2) return;

            quickLinksSaveOrder.disabled = true;
            const labelSpan = quickLinksSaveOrder.querySelector('span');
            const originalLabel = labelSpan ? labelSpan.textContent : 'Save Order';
            if (labelSpan) labelSpan.textContent = 'Saving...';

            try {
                const res = await fetch(bulkReorderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ order })
                });

                if (!res.ok) {
                    const msg = await res.text();
                    throw new Error(msg || 'Failed to save order');
                }

                location.reload();
            } catch (err) {
                quickLinksSaveOrder.disabled = false;
                if (labelSpan) labelSpan.textContent = originalLabel;
                alert(err.message || 'Save Order failed');
            }
        });
    }

    function loadForm(url, title) {
        modalTitle.textContent = title || 'Add Link';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => { modalBody.innerHTML = html; })
            .catch(() => {
                modalBody.innerHTML = '<div class="alert alert-danger mb-0 rounded-3">Failed to load form.</div>';
            });

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    document.addEventListener('click', (e) => {
        const createBtn = e.target.closest('#openCreateQuickLink');
        if (createBtn) {
            e.preventDefault();
            loadForm(createBtn.getAttribute('href'), 'Add Link');
            return;
        }

        const editLink = e.target.closest('.openEditQuickLink');
        if (editLink) {
            e.preventDefault();
            loadForm(editLink.getAttribute('href'), 'Edit Link');
        }
    });

    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('#quickLinkForm');
        if (!form) return;

        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalSubmitText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
        }

        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.ql-field-error').forEach(el => el.remove());

        try {
            const res = await fetch(form.getAttribute('action'), {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (res.ok) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
                location.reload();
                return;
            }

            if (res.status === 422) {
                const data = await res.json();
                const errors = data?.errors || {};
                Object.entries(errors).forEach(([field, messages]) => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const err = document.createElement('small');
                        err.className = 'text-danger d-block ql-field-error';
                        err.textContent = messages[0];
                        input.closest('.mb-3, .mb-4')?.appendChild(err);
                    }
                });
                return;
            }

            throw new Error('Save failed. Please try again.');
        } catch (err) {
            alert(err.message || 'Save failed. Please try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalSubmitText;
            }
        }
    });

    @if (request('open_quick_link_modal') === 'add')
        loadForm("{{ route('admin.setup.quick_links.create') }}", 'Add Link');
    @elseif (request('open_quick_link_modal') === 'edit' && request('quick_link_id'))
        loadForm("{{ route('admin.setup.quick_links.edit', request('quick_link_id')) }}", 'Edit Link');
    @endif
});
</script>
@endpush
