@extends('admin.layouts.master')

@section('title', 'Word of the Day')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <x-breadcrum title="Word of the Day (Login Page)" />

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card rounded-4 border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-semibold mb-2">How it works</h6>
            <p class="small text-body-secondary mb-2">
                <strong>Rotation:</strong> Active rows with <em>no</em> scheduled date cycle in <strong>Sort order</strong> (midnight, app timezone).
                The starting point is configurable via <code class="small">WORD_OF_DAY_ANCHOR_DATE</code> / <code class="small">config/word_of_the_day.php</code>.
            </p>
            <p class="small text-body-secondary mb-2">
                <strong>Scheduled date:</strong> If set, that row overrides the rotation for that calendar day only.
            </p>
            <p class="small text-body-secondary mb-0">
                The login page caches today’s word until end of day; changes clear the cache automatically.
            </p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 py-3 px-4">
                    <h6 class="mb-0 fw-semibold">Next 7 days (preview)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Word</th>
                                    <th class="pe-4">Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rotationPreview as $row)
                                    <tr>
                                        <td class="ps-4 text-nowrap">{{ $row['date'] }}</td>
                                        <td class="small">{{ $row['line'] ?? '—' }}</td>
                                        <td class="pe-4">
                                            <span class="badge rounded-pill {{ $row['mode'] === 'scheduled' ? 'text-bg-warning' : 'text-bg-secondary' }}">
                                                {{ $row['mode'] === 'scheduled' ? 'Scheduled' : 'Rotation' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card rounded-4 border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 py-3 px-4">
                    <h6 class="mb-0 fw-semibold">Import / export</h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <p class="small text-body-secondary">CSV columns: <code>hindi_text, english_text, sort_order, active_inactive, scheduled_date</code> (header row required).</p>
                    <a href="{{ route('admin.word-of-day.export') }}" class="btn btn-outline-primary btn-sm rounded-pill mb-3 d-inline-flex align-items-center gap-1">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">download</i>
                        Download CSV
                    </a>
                    <form action="{{ route('admin.word-of-day.import') }}" method="POST" enctype="multipart/form-data" class="d-flex flex-wrap align-items-end gap-2">
                        @csrf
                        <div class="flex-grow-1" style="min-width:180px;">
                            <label class="form-label small fw-semibold mb-1">Upload CSV</label>
                            <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.txt" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill">Import</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card rounded-4 border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 py-3 px-4 d-flex flex-wrap align-items-center gap-2 justify-content-between">
            <div>
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-icons material-symbols-rounded text-primary">translate</span>
                    Entries
                </h6>
                @if($todaysWord)
                    <p class="small text-body-secondary mb-0 mt-1">
                        <strong>Today on login:</strong> {{ $todaysWord->displayLine() }}
                    </p>
                @else
                    <p class="small text-warning mb-0 mt-1">No active entry for today — add rotation entries (no schedule) or a scheduled row for today.</p>
                @endif
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="wordOfDaySaveOrder" disabled>
                    Save order
                </button>
                <button type="button" class="btn btn-primary rounded-pill d-inline-flex align-items-center gap-1" id="openCreateWordOfDay"
                    data-url="{{ route('admin.word-of-day.create') }}">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">add</i>
                    Add word
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" id="wordOfDayTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width:70px;">#</th>
                            <th>Hindi</th>
                            <th>English</th>
                            <th style="width:100px;">Order</th>
                            <th style="width:120px;">Scheduled</th>
                            <th style="width:110px;">Active</th>
                            <th style="width:100px;">Today</th>
                            <th class="pe-4" style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($words as $index => $w)
                            <tr data-word-id="{{ $w->id }}">
                                <td class="ps-4 text-body-secondary">{{ $index + 1 }}</td>
                                <td>{{ $w->hindi_text }}</td>
                                <td>{{ $w->english_text }}</td>
                                <td>
                                    <span class="wordofday-drag-handle text-muted" draggable="true" title="Drag to reorder" style="cursor:grab;">
                                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">drag_handle</i>
                                        <span class="small">{{ $w->sort_order }}</span>
                                    </span>
                                </td>
                                <td class="small">{{ $w->scheduled_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @if($w->active_inactive)
                                        <span class="badge text-bg-success rounded-pill">Yes</span>
                                    @else
                                        <span class="badge text-bg-secondary rounded-pill">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if($todaysWord && $todaysWord->id === $w->id)
                                        <span class="badge text-bg-primary rounded-pill">Today</span>
                                    @else
                                        <span class="text-body-secondary">—</span>
                                    @endif
                                </td>
                                <td class="pe-4">
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.word-of-day.edit', $w) }}" class="text-primary openEditWordOfDay" title="Edit">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                        </a>
                                        <form action="{{ route('admin.word-of-day.destroy', $w) }}" method="POST"
                                            onsubmit="return confirm('Delete this entry?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-body-secondary py-5">
                                    No entries yet. Run <code class="small">php artisan db:seed --class=WordOfTheDaySeeder</code> or use <strong>Add word</strong>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

<div class="modal fade" id="wordOfDayModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#af2910;">
                <h5 class="modal-title text-white" id="wordOfDayModalTitle">Word of the Day</h5>
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
    #wordOfDayTable tbody tr.dragging-wod-row { opacity: 0.55; }
    #wordOfDayTable tbody tr.drag-over-wod-row {
        outline: 2px solid #0d6efd;
        outline-offset: -2px;
        background: rgba(13, 110, 253, 0.05);
    }
    #wordOfDayTable .wordofday-drag-handle { user-select: none; }
</style>
<script>
(function () {
    const modalEl = document.getElementById('wordOfDayModal');
    if (!modalEl) return;

    const modalBody = modalEl.querySelector('.modal-body');
    const modalTitle = modalEl.querySelector('#wordOfDayModalTitle');
    let wordOfDayModalInstance = null;

    function getOrCreateModal() {
        if (!wordOfDayModalInstance) {
            wordOfDayModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        return wordOfDayModalInstance;
    }

    function loadWordOfDayForm(url, title) {
        modalTitle.textContent = title || 'Word of the Day';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
            .then(r => {
                if (!r.ok) throw new Error('Failed to load form');
                return r.text();
            })
            .then(html => { modalBody.innerHTML = html; })
            .catch(() => {
                modalBody.innerHTML = '<div class="alert alert-danger mb-0">Failed to load form.</div>';
            });

        getOrCreateModal().show();
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('openCreateWordOfDay')?.addEventListener('click', (e) => {
            e.preventDefault();
            const url = e.currentTarget.getAttribute('data-url');
            loadWordOfDayForm(url, 'Add Word of the Day');
        });

        document.querySelectorAll('.openEditWordOfDay').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                loadWordOfDayForm(link.getAttribute('href'), 'Edit Word of the Day');
            });
        });

        @if($errors->any() && old('_wod_context'))
        @php
            $wodCtx = old('_wod_context');
        @endphp
        @if(is_string($wodCtx) && str_starts_with($wodCtx, 'edit:'))
        loadWordOfDayForm(@json(route('admin.word-of-day.edit', (int) substr($wodCtx, 5))), 'Edit Word of the Day');
        @else
        loadWordOfDayForm(@json(route('admin.word-of-day.create')), 'Add Word of the Day');
        @endif
        @endif

        const wordTable = document.getElementById('wordOfDayTable');
        const saveOrderBtn = document.getElementById('wordOfDaySaveOrder');
        const reorderUrl = @json(route('admin.word-of-day.reorder'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (wordTable && saveOrderBtn) {
            const tbody = wordTable.querySelector('tbody');
            let draggedRow = null;
            let initialOrder = [];

            const setDirty = (dirty) => { saveOrderBtn.disabled = !dirty; };

            tbody?.addEventListener('dragstart', (e) => {
                const handle = e.target.closest('.wordofday-drag-handle');
                if (!handle) return;
                draggedRow = handle.closest('tr[data-word-id]');
                if (!draggedRow) return;
                initialOrder = Array.from(tbody.querySelectorAll('tr[data-word-id]'))
                    .map(tr => parseInt(tr.dataset.wordId, 10))
                    .filter(Boolean);
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', draggedRow.dataset.wordId);
                draggedRow.classList.add('dragging-wod-row');
            });

            tbody?.addEventListener('dragend', () => {
                if (draggedRow) draggedRow.classList.remove('dragging-wod-row');
                draggedRow = null;
            });

            tbody?.addEventListener('dragover', (e) => {
                e.preventDefault();
                const overRow = e.target.closest('tr[data-word-id]');
                if (!overRow || !draggedRow) return;
                overRow.classList.add('drag-over-wod-row');
            });

            tbody?.addEventListener('dragleave', (e) => {
                const row = e.target.closest('tr[data-word-id]');
                if (row) row.classList.remove('drag-over-wod-row');
            });

            tbody?.addEventListener('drop', (e) => {
                e.preventDefault();
                const dropRow = e.target.closest('tr[data-word-id]');
                tbody.querySelectorAll('tr.drag-over-wod-row').forEach(r => r.classList.remove('drag-over-wod-row'));
                if (!draggedRow || !dropRow || dropRow === draggedRow) return;

                const rect = dropRow.getBoundingClientRect();
                if (e.clientY > rect.top + rect.height / 2) {
                    dropRow.after(draggedRow);
                } else {
                    dropRow.before(draggedRow);
                }

                const newOrder = Array.from(tbody.querySelectorAll('tr[data-word-id]'))
                    .map(tr => parseInt(tr.dataset.wordId, 10))
                    .filter(Boolean);
                const changed = initialOrder.length && JSON.stringify(initialOrder) !== JSON.stringify(newOrder);
                setDirty(!!changed);
            });

            saveOrderBtn.addEventListener('click', async () => {
                if (!tbody) return;
                const order = Array.from(tbody.querySelectorAll('tr[data-word-id]'))
                    .map(tr => parseInt(tr.dataset.wordId, 10))
                    .filter(Boolean);
                if (order.length < 2) return;

                saveOrderBtn.disabled = true;
                saveOrderBtn.textContent = 'Saving...';

                try {
                    const res = await fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ order })
                    });
                    if (!res.ok) throw new Error(await res.text() || 'Failed');
                    location.reload();
                } catch (err) {
                    saveOrderBtn.disabled = false;
                    saveOrderBtn.textContent = 'Save order';
                    alert(err.message || 'Save order failed');
                }
            });
        }
    });
})();
</script>
@endpush
