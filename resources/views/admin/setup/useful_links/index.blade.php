@extends('admin.layouts.master')

@section('title', 'Useful Links')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/useful-links-admin.css') }}?v={{ @filemtime(public_path('css/useful-links-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid ul-page py-4">
    <x-breadcrum title="Useful Links">
        <div class="d-flex flex-wrap align-items-center ul-toolbar-actions">
            <button type="button" id="usefulLinksSaveOrder" disabled class="btn ul-btn-outline">
                <i class="bi bi-save" aria-hidden="true"></i>
                <span>Save Order</span>
            </button>
            <a href="{{ route('admin.setup.useful_links.create') }}" id="openCreateUsefulLink"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Add Useful Link</span>
            </a>
        </div>
    </x-breadcrum>

    <x-session_message />

    <div class="card ul-dt-card shadow-sm rounded-3 overflow-hidden border-0">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="ulDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="usefulLinksTable"></div>
            </div>

            <div class="programme-dt-panel ul-dt-panel">
                <div class="ul-table-outer">
                    <div class="table-responsive ul-dt-scroll">
                        <table class="table table-hover align-middle mb-0 programme-dt-table datatable border-0"
                            id="usefulLinksTable"
                            data-export="false"
                            data-ordering="false">
                            <thead>
                                <tr>
                                    <th scope="col">S. No.</th>
                                    <th scope="col">Label</th>
                                    <th scope="col">URL</th>
                                    <th scope="col">Attachment</th>
                                    <th scope="col" style="width:90px;">Order</th>
                                    <th scope="col" style="width:120px;">URL open in</th>
                                    <th scope="col" style="width:120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($usefulLinks as $index => $link)
                                    <tr data-usefullink-id="{{ $link->id }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $link->label }}</td>
                                        <td title="{{ $link->url }}">{{ $link->url ?: '—' }}</td>
                                        <td>
                                            @if ($link->file_path)
                                                <a href="{{ asset('storage/' . $link->file_path) }}" target="_blank" rel="noopener">
                                                    View File
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="usefullink-drag-handle text-muted" draggable="true" title="Drag to reorder"
                                                style="cursor: grab;">
                                                <i class="bi bi-grip-vertical" aria-hidden="true"></i>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="ul-open-badge">{{ $link->target_blank ? 'New Tab' : 'Same Tab' }}</span>
                                        </td>
                                        <td>
                                            <div class="ul-actions">
                                                <a href="{{ route('admin.setup.useful_links.edit', encrypt($link->id)) }}"
                                                    class="openEditUsefulLink ul-action-btn ul-action-edit"
                                                    title="Edit"
                                                    aria-label="Edit {{ $link->label }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </a>

                                                <form action="{{ route('admin.setup.useful_links.delete', encrypt($link->id)) }}"
                                                    method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Delete this useful link?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="ul-action-btn ul-action-delete"
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
                                        <td colspan="7" class="ul-empty-state text-center">
                                            <i class="bi bi-link-45deg display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                            <h5 class="fw-semibold text-dark mb-1">No Useful Links Found</h5>
                                            <p class="text-secondary mb-0">Add a useful link to get started.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="ulDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3"
                    data-dt-footer-for="usefulLinksTable"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="usefulLinksModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered ul-useful-links-modal-dialog">
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
    const modalEl = document.getElementById('usefulLinksModal');
    const modalBody = modalEl.querySelector('.modal-body');
    const modalTitle = modalEl.querySelector('.modal-title');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const bulkReorderUrl = "{{ route('admin.setup.useful_links.bulk-reorder') }}";

    const usefulLinksTable = document.getElementById('usefulLinksTable');
    const usefulLinksSaveOrder = document.getElementById('usefulLinksSaveOrder');

    if (usefulLinksTable && usefulLinksSaveOrder) {
        const tbody = usefulLinksTable.querySelector('tbody');
        let draggedRow = null;
        let initialOrder = [];

        const setDirty = (dirty) => {
            usefulLinksSaveOrder.disabled = !dirty;
        };

        setDirty(false);

        if (tbody) {
            tbody.addEventListener('dragstart', (e) => {
                const handle = e.target.closest('.usefullink-drag-handle');
                if (!handle) return;
                draggedRow = handle.closest('tr[data-usefullink-id]');
                if (!draggedRow) return;

                initialOrder = Array.from(tbody.querySelectorAll('tr[data-usefullink-id]'))
                    .map(tr => parseInt(tr.dataset.usefullinkId, 10))
                    .filter(Boolean);

                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', draggedRow.dataset.usefullinkId);
                draggedRow.classList.add('dragging-usefullink-row');
            });

            tbody.addEventListener('dragend', () => {
                if (draggedRow) draggedRow.classList.remove('dragging-usefullink-row');
                draggedRow = null;
            });

            tbody.addEventListener('dragover', (e) => {
                e.preventDefault();
                const overRow = e.target.closest('tr[data-usefullink-id]');
                if (!overRow || !draggedRow) return;
                overRow.classList.add('drag-over-usefullink-row');
            });

            tbody.addEventListener('dragleave', (e) => {
                const row = e.target.closest('tr[data-usefullink-id]');
                if (row) row.classList.remove('drag-over-usefullink-row');
            });

            tbody.addEventListener('drop', (e) => {
                e.preventDefault();
                const dropRow = e.target.closest('tr[data-usefullink-id]');

                if (!draggedRow) return;

                tbody.querySelectorAll('tr.drag-over-usefullink-row').forEach(r => r.classList.remove('drag-over-usefullink-row'));

                if (!dropRow || dropRow === draggedRow) return;

                const rect = dropRow.getBoundingClientRect();
                if (e.clientY > rect.top + rect.height / 2) {
                    dropRow.after(draggedRow);
                } else {
                    dropRow.before(draggedRow);
                }

                const newOrder = Array.from(tbody.querySelectorAll('tr[data-usefullink-id]'))
                    .map(tr => parseInt(tr.dataset.usefullinkId, 10))
                    .filter(Boolean);

                const changed = initialOrder.length &&
                    JSON.stringify(initialOrder) !== JSON.stringify(newOrder);

                setDirty(!!changed);
            });
        }

        usefulLinksSaveOrder.addEventListener('click', async () => {
            if (!tbody) return;
            const order = Array.from(tbody.querySelectorAll('tr[data-usefullink-id]'))
                .map(tr => parseInt(tr.dataset.usefullinkId, 10))
                .filter(Boolean);

            if (order.length < 2) return;

            usefulLinksSaveOrder.disabled = true;
            const labelSpan = usefulLinksSaveOrder.querySelector('span');
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
                usefulLinksSaveOrder.disabled = false;
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
        const createBtn = e.target.closest('#openCreateUsefulLink');
        if (createBtn) {
            e.preventDefault();
            loadForm(createBtn.getAttribute('href'), 'Add Link');
            return;
        }

        const editLink = e.target.closest('.openEditUsefulLink');
        if (editLink) {
            e.preventDefault();
            loadForm(editLink.getAttribute('href'), 'Edit Link');
        }
    });

    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('#usefulLinkForm');
        if (!form) return;

        e.preventDefault();

        const urlInput = form.querySelector('#usefulLinkUrl');
        const fileInput = form.querySelector('#usefulLinkFile');
        const errorEl = form.querySelector('#urlFileValidationError');
        const removeFileCheckbox = form.querySelector('#removeFile');
        const hasExistingFile = form.dataset.hasExistingFile === '1';

        const clearValidationError = () => {
            if (urlInput) urlInput.classList.remove('is-invalid');
            if (fileInput) fileInput.classList.remove('is-invalid');
            if (errorEl) errorEl.textContent = '';
        };

        const hasUrl = !!(urlInput && urlInput.value.trim() !== '');
        const hasNewFile = !!(fileInput && fileInput.files && fileInput.files.length > 0);
        const keepsExistingFile = hasExistingFile && (!removeFileCheckbox || !removeFileCheckbox.checked);

        clearValidationError();
        if (!hasUrl && !hasNewFile && !keepsExistingFile) {
            if (urlInput) urlInput.classList.add('is-invalid');
            if (fileInput) fileInput.classList.add('is-invalid');
            if (errorEl) errorEl.textContent = 'Please provide either URL or file.';
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalSubmitText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
        }

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
                const urlOrFileError = errors.url_or_file?.[0] || errors.url?.[0] || errors.file?.[0];

                if (urlOrFileError) {
                    if (urlInput) urlInput.classList.add('is-invalid');
                    if (fileInput) fileInput.classList.add('is-invalid');
                    if (errorEl) errorEl.textContent = urlOrFileError;
                }
                return;
            }

            throw new Error('Save failed. Please try again.');
        } catch (err) {
            if (errorEl) {
                errorEl.textContent = err.message || 'Save failed. Please try again.';
            } else {
                alert(err.message || 'Save failed. Please try again.');
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalSubmitText;
            }
        }
    });

    @if (request('open_useful_link_modal') === 'add')
        loadForm("{{ route('admin.setup.useful_links.create') }}", 'Add Link');
    @elseif (request('open_useful_link_modal') === 'edit' && request('useful_link_id'))
        loadForm("{{ route('admin.setup.useful_links.edit', request('useful_link_id')) }}", 'Edit Link');
    @endif
});
</script>
@endpush
