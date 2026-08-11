@extends('admin.layouts.master')
@section('title', 'Quick Links')
@section('setup_content')
    <div class="container-fluid">
        <x-breadcrum title="Quick Links">
            <div class="gap-2">
                <button type="button" class="btn btn-outline-primary" id="quickLinksSaveOrder" disabled>
                            Save Order
                        </button>
                        <a href="{{ route('admin.setup.quick_links.create') }}" class="btn btn-primary" id="openCreateQuickLink">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                            Add Quick Link
                        </a>
            </div>
        </x-breadcrum>
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">
                    @php
                        // Column map for the server-side grid (see admin/layouts/footer auto-init).
                        $quickLinkDtColumns = json_encode([
                            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                            ['data' => 'label', 'name' => 'label'],
                            ['data' => 'url', 'name' => 'url'],
                            ['data' => 'order', 'name' => 'position', 'searchable' => false],
                            ['data' => 'open', 'name' => 'target_blank', 'searchable' => false],
                            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
                        ], JSON_HEX_APOS | JSON_HEX_QUOT);
                    @endphp
                    <table class="table datatable" id="quickLinksTable" data-export="false"
                        data-server-side="true"
                        data-ajax-url="{{ route('admin.setup.quick_links.index') }}"
                        data-order='[]'
                        data-columns='{!! $quickLinkDtColumns !!}'>
                        <thead>
                            <tr>
                                <th style="width:70px;">S.No.</th>
                                <th>Label</th>
                                <th>URL</th>
                                <th style="width:90px;">Order</th>
                                <th style="width:160px;">Open</th>
                                <th style="width:140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

<!-- Modal -->
<div class="modal fade" id="quickLinksModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#af2910;">
                <h5 class="modal-title text-white">Quick Link</h5>
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
    /* Quick Links drag-and-drop visuals */
    #quickLinksTable tbody tr.dragging-quicklink-row {
        opacity: 0.55;
    }

    #quickLinksTable tbody tr.drag-over-quicklink-row {
        outline: 2px solid #0d6efd;
        outline-offset: -2px;
        background: rgba(13, 110, 253, 0.05);
    }

    #quickLinksTable .quicklink-drag-handle {
        user-select: none;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('quickLinksModal');
    const modalBody = modalEl.querySelector('.modal-body');
    const modalTitle = modalEl.querySelector('.modal-title');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const bulkReorderUrl = "{{ route('admin.setup.quick_links.bulk-reorder') }}";

    const quickLinksTable = document.getElementById('quickLinksTable');
    const quickLinksSaveOrder = document.getElementById('quickLinksSaveOrder');

    // Drag & drop ordering (pick and drop).
    if (quickLinksTable && quickLinksSaveOrder) {
        const tbody = quickLinksTable.querySelector('tbody');
        let draggedRow = null;
        let initialOrder = [];

        const setDirty = (dirty) => {
            quickLinksSaveOrder.disabled = !dirty;
        };

        setDirty(false);

        tbody.addEventListener('dragstart', (e) => {
            const handle = e.target.closest('.quicklink-drag-handle');
            if (!handle) return;
            draggedRow = handle.closest('tr[data-quicklink-id]');
            if (!draggedRow) return;

            // Capture initial order ids when drag starts.
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
            // Dirty state is set only after an actual drop.
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

            // Remove highlights
            tbody.querySelectorAll('tr.drag-over-quicklink-row').forEach(r => r.classList.remove('drag-over-quicklink-row'));

            if (!dropRow || dropRow === draggedRow) return;

            const rect = dropRow.getBoundingClientRect();
            if (e.clientY > rect.top + rect.height / 2) {
                dropRow.after(draggedRow);
            } else {
                dropRow.before(draggedRow);
            }

            // Enable Save Order only if order changed.
            const newOrder = Array.from(tbody.querySelectorAll('tr[data-quicklink-id]'))
                .map(tr => parseInt(tr.dataset.quicklinkId, 10))
                .filter(Boolean);

            const changed = initialOrder.length &&
                JSON.stringify(initialOrder) !== JSON.stringify(newOrder);

            setDirty(!!changed);
        });

        quickLinksSaveOrder.addEventListener('click', async () => {
            if (!tbody) return;
            const rows = Array.from(tbody.querySelectorAll('tr[data-quicklink-id]'));
            const order = rows
                .map(tr => parseInt(tr.dataset.quicklinkId, 10))
                .filter(Boolean);

            // Rows are paginated server-side, so they are shuffled within the position
            // slots the visible rows already occupy.
            const positions = rows
                .map(tr => parseInt(tr.dataset.position, 10))
                .filter(Number.isFinite)
                .sort((a, b) => a - b);

            const payload = positions.length === order.length ? { order, positions } : { order };

            if (order.length < 2) return;

            quickLinksSaveOrder.disabled = true;
            quickLinksSaveOrder.textContent = 'Saving...';

            try {
                const res = await fetch(bulkReorderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                if (!res.ok) {
                    const msg = await res.text();
                    throw new Error(msg || 'Failed to save order');
                }

                location.reload();
            } catch (err) {
                quickLinksSaveOrder.disabled = false;
                quickLinksSaveOrder.textContent = 'Save Order';
                alert(err.message || 'Save Order failed');
            }
        });
    }

    function loadForm(url, title) {
        modalTitle.textContent = title || 'Quick Link';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => { modalBody.innerHTML = html; })
            .catch(() => {
                modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>';
            });

        (new bootstrap.Modal(modalEl)).show();
    }

    document.getElementById('openCreateQuickLink')?.addEventListener('click', e => {
        e.preventDefault();
        loadForm(e.currentTarget.getAttribute('href'), 'Create Quick Link');
    });

    // Delegated: rows are rendered by the server-side DataTable on every draw.
    document.addEventListener('click', e => {
        const link = e.target.closest('.openEditQuickLink');
        if (!link) return;
        e.preventDefault();
        loadForm(link.getAttribute('href'), 'Edit Quick Link');
    });
});
</script>
@endpush

