@extends('admin.layouts.master')
@section('title', 'Useful Links - Sargam | Lal Bahadur Shastri')
@section('setup_content')
    <div class="container-fluid">
        <x-breadcrum title="Useful Links" />
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h4 class="mb-0">Useful Links</h4>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-primary" id="usefulLinksSaveOrder" disabled>
                            Save Order
                        </button>
                        <a href="{{ route('admin.setup.useful_links.create') }}" class="btn btn-primary" id="openCreateUsefulLink">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                            Add Useful Link
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table mb-0" id="usefulLinksTable">
                        <thead>
                            <tr>
                                <th style="width:70px;">S.No.</th>
                                <th>Label</th>
                                <th>URL</th>
                                <th>File</th>
                                <th style="width:90px;">Order</th>
                                <th style="width:160px;">Open</th>
                                <th style="width:140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usefulLinks as $index => $link)
                                <tr data-usefullink-id="{{ $link->id }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $link->label }}</td>
                                    <td style="max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                                        title="{{ $link->url }}">
                                        {{ $link->url ?: '-' }}
                                    </td>
                                    <td>
                                        @if ($link->file_path)
                                            <a href="{{ asset('storage/' . $link->file_path) }}" target="_blank">
                                                View File
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="usefullink-drag-handle text-muted" draggable="true" title="Drag to reorder"
                                            style="cursor: grab;">
                                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">drag_handle</i>
                                        </span>
                                    </td>
                                    <td>
                                        {{ $link->target_blank ? 'New Tab' : 'Same Tab' }}
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('admin.setup.useful_links.edit', encrypt($link->id)) }}"
                                                class="text-primary openEditUsefulLink" title="Edit">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size:22px;">edit</i>
                                            </a>

                                            <form action="{{ route('admin.setup.useful_links.delete', encrypt($link->id)) }}"
                                                method="POST"
                                                onsubmit="return confirm('Delete this useful link?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link p-0 text-primary" title="Delete">
                                                    <i class="material-icons material-symbols-rounded"
                                                        style="font-size:22px;">delete</i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No Useful Links found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="small text-muted mt-3">
                    Tip: Drag the handle in the "Order" column, then click `Save Order`.
                </div>
            </div>
        </div>
    </div>
@endsection

<div class="modal fade" id="usefulLinksModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#af2910;">
                <h5 class="modal-title text-white">Useful Link</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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

@push('scripts')
<style>
    #usefulLinksTable tbody tr.dragging-usefullink-row {
        opacity: 0.55;
    }

    #usefulLinksTable tbody tr.drag-over-usefullink-row {
        outline: 2px solid #0d6efd;
        outline-offset: -2px;
        background: rgba(13, 110, 253, 0.05);
    }

    #usefulLinksTable .usefullink-drag-handle {
        user-select: none;
    }
</style>
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

        usefulLinksSaveOrder.addEventListener('click', async () => {
            if (!tbody) return;
            const order = Array.from(tbody.querySelectorAll('tr[data-usefullink-id]'))
                .map(tr => parseInt(tr.dataset.usefullinkId, 10))
                .filter(Boolean);

            if (order.length < 2) return;

            usefulLinksSaveOrder.disabled = true;
            usefulLinksSaveOrder.textContent = 'Saving...';

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
                usefulLinksSaveOrder.textContent = 'Save Order';
                alert(err.message || 'Save Order failed');
            }
        });
    }

    function loadForm(url, title) {
        modalTitle.textContent = title || 'Useful Link';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => { modalBody.innerHTML = html; })
            .catch(() => {
                modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>';
            });

        (new bootstrap.Modal(modalEl)).show();
    }

    document.getElementById('openCreateUsefulLink')?.addEventListener('click', e => {
        e.preventDefault();
        loadForm(e.currentTarget.getAttribute('href'), 'Create Useful Link');
    });

    document.querySelectorAll('.openEditUsefulLink').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            loadForm(e.currentTarget.getAttribute('href'), 'Edit Useful Link');
        });
    });
});
</script>
@endpush

