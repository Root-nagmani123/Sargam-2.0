@extends('admin.layouts.master')
@section('title', 'FC Travel — Arrival slots')

@section('setup_content')
<div class="container-fluid px-3">
    <style>
        .slot-page-note {
            background: linear-gradient(90deg, #f0f6ff 0%, #f8fbff 100%);
            border: 1px solid #d8e7ff;
            border-radius: 10px;
            padding: 12px 14px;
        }
        .slot-section-card .card-header {
            border-bottom: 1px solid #edf2f7;
        }
        .slot-bulk-table thead th {
            white-space: nowrap;
            font-size: 12px;
            color: #374151;
        }
        .slot-help-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #dbeafe;
            background: #eff6ff;
            color: #1e40af;
            border-radius: 999px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;"><i class="bi bi-clock-history me-2"></i>Arrival time slots</h4>
        <a href="{{ route('admin.travel.index') }}" class="btn btn-sm btn-outline-secondary">Back to travel plans</a>
    </div>
    <div class="slot-page-note mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <p class="text-muted small mb-0">Create slots date-wise (date + label + optional time range). <strong>Max</strong> = headcount per slot (leave empty = no limit, 0 = hidden for users).</p>
            <span class="slot-help-chip"><i class="bi bi-info-circle"></i>Users can only select dates/slots created here</span>
        </div>
    </div>
    @if(session('success'))<div class="alert alert-success py-2 small">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger py-2 small">{{ session('error') }}</div>@endif

    <div class="card border-0 shadow-sm mb-4 slot-section-card">
        <div class="card-header bg-white">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h6 class="mb-0 fw-semibold">Add Slots</h6>
                    <small class="text-muted">Use quick add for one slot or bulk add for multiple slots in one save.</small>
                </div>
                <div class="btn-group btn-group-sm" role="group" aria-label="Slot add mode">
                    <button type="button" class="btn btn-primary" id="toggleSingleBtn">Single Add</button>
                    <button type="button" class="btn btn-outline-primary" id="toggleBulkBtn">Bulk Add</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3" id="singleAddSection">
                <h6 class="small text-uppercase text-muted mb-2">Quick add (single slot)</h6>
                <form method="POST" action="{{ route('admin.travel.slots.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Date <span class="text-danger">*</span></label>
                        <input type="date" name="slot_date" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Label <span class="text-danger">*</span></label>
                        <input type="text" name="slot_label" id="singleSlotLabel" class="form-control form-control-sm" required placeholder="8AM-9AM" maxlength="100">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">From</label>
                        <input type="time" name="time_start" id="singleTimeStart" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">To</label>
                        <input type="time" name="time_end" id="singleTimeEnd" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">Max</label>
                        <input type="number" name="max_capacity" class="form-control form-control-sm" min="0" placeholder="Blank = no limit">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">Sort</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" value="0" min="0">
                    </div>
                    <div class="col-md-2 form-check align-self-end mb-1">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="nAct" checked>
                        <label class="form-check-label small" for="nAct">Active</label>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check2-circle me-1"></i>Save slot</button>
                    </div>
                </form>
            </div>

            <hr class="my-3" id="sectionDivider">

            <div id="bulkAddSection">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <h6 class="small text-uppercase text-muted mb-0">Bulk add (multiple slots in one click)</h6>
                    <div class="d-flex gap-2 align-items-center">
                        <small class="text-muted">Tip: Use one row per slot/time window.</small>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addSlotRowBtn"><i class="bi bi-plus-lg me-1"></i>Add bulk row</button>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.travel.slots.store') }}" id="bulkSlotsForm">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-2 slot-bulk-table" id="bulkSlotsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Label</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Max</th>
                                    <th>Sort</th>
                                    <th>Active</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-secondary">
                                    <td colspan="8" class="small text-muted">
                                        <i class="bi bi-lightbulb me-1"></i>
                                        Fill details in each row, then click <strong>Save all rows</strong>. This is an input row, not sample data.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-save2 me-1"></i>Save all rows</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="resetBulkRowsBtn"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset rows</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column gap-2">
    @forelse($slots as $slot)
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2">
                <form method="POST" action="{{ route('admin.travel.slots.update', $slot) }}" class="row g-2 align-items-end">
                    @csrf
                    @method('PUT')
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Date</label>
                        <input type="date" name="slot_date" class="form-control form-control-sm" value="{{ $slot->slot_date?->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Label</label>
                        <input type="text" name="slot_label" class="form-control form-control-sm" value="{{ $slot->slot_label }}" required maxlength="100">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">From</label>
                        <input type="time" name="time_start" class="form-control form-control-sm" value="{{ $slot->time_start ? \Illuminate\Support\Str::substr($slot->time_start, 0, 5) : '' }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">To</label>
                        <input type="time" name="time_end" class="form-control form-control-sm" value="{{ $slot->time_end ? \Illuminate\Support\Str::substr($slot->time_end, 0, 5) : '' }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">Max</label>
                        <input type="number" name="max_capacity" class="form-control form-control-sm" min="0" value="{{ $slot->max_capacity }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">Sort</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $slot->sort_order }}" min="0">
                    </div>
                    <div class="col-md-2 form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="act{{ $slot->id }}" {{ $slot->is_active ? 'checked' : '' }}>
                        <label class="form-check-label small" for="act{{ $slot->id }}">Active</label>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.travel.slots.destroy', $slot) }}" class="mt-1" onsubmit="return confirm('Delete this slot?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-muted">No slots yet. Add one above.</p>
    @endforelse
    </div>
</div>

<script>
    (function () {
        const addBtn = document.getElementById('addSlotRowBtn');
        const tableBody = document.querySelector('#bulkSlotsTable tbody');
        const resetBtn = document.getElementById('resetBulkRowsBtn');
        const singleSection = document.getElementById('singleAddSection');
        const bulkSection = document.getElementById('bulkAddSection');
        const divider = document.getElementById('sectionDivider');
        const toggleSingleBtn = document.getElementById('toggleSingleBtn');
        const toggleBulkBtn = document.getElementById('toggleBulkBtn');
        if (!addBtn || !tableBody || !resetBtn || !singleSection || !bulkSection || !divider || !toggleSingleBtn || !toggleBulkBtn) return;

        function setMode(mode) {
            const isSingle = mode === 'single';
            singleSection.style.display = isSingle ? '' : 'none';
            divider.style.display = isSingle ? 'none' : '';
            bulkSection.style.display = isSingle ? 'none' : '';

            toggleSingleBtn.classList.toggle('btn-primary', isSingle);
            toggleSingleBtn.classList.toggle('btn-outline-primary', !isSingle);
            toggleBulkBtn.classList.toggle('btn-primary', !isSingle);
            toggleBulkBtn.classList.toggle('btn-outline-primary', isSingle);
        }

        function makeRow(index) {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="date" name="slots[${index}][slot_date]" class="form-control form-control-sm" required></td>
                <td><input type="text" name="slots[${index}][slot_label]" class="form-control form-control-sm js-slot-label" maxlength="100" required placeholder="8AM-9AM"></td>
                <td><input type="time" name="slots[${index}][time_start]" class="form-control form-control-sm js-time-start"></td>
                <td><input type="time" name="slots[${index}][time_end]" class="form-control form-control-sm js-time-end"></td>
                <td><input type="number" name="slots[${index}][max_capacity]" class="form-control form-control-sm" min="0" placeholder="Blank = no limit"></td>
                <td><input type="number" name="slots[${index}][sort_order]" class="form-control form-control-sm" min="0" value="0"></td>
                <td>
                    <div class="form-check m-0">
                        <input type="hidden" name="slots[${index}][is_active]" value="0">
                        <input type="checkbox" name="slots[${index}][is_active]" value="1" class="form-check-input" checked>
                    </div>
                </td>
                <td><button type="button" class="btn btn-sm btn-outline-danger js-remove-row">Remove</button></td>
            `;
            return tr;
        }

        function formatTime12h(value) {
            if (!value || !value.includes(':')) return '';
            const [hh, mm] = value.split(':');
            const h = parseInt(hh, 10);
            if (Number.isNaN(h)) return '';
            const suffix = h >= 12 ? 'PM' : 'AM';
            const hour = h % 12 || 12;
            return `${String(hour).padStart(2, '0')}:${mm} ${suffix}`;
        }

        function buildLabel(startVal, endVal) {
            const s = formatTime12h(startVal);
            const e = formatTime12h(endVal);
            if (s && e) return `${s} - ${e}`;
            if (s) return s;
            return '';
        }

        function wireAutoLabel(labelInput, startInput, endInput) {
            if (!labelInput || !startInput || !endInput) return;

            function refreshLabel() {
                const isManual = labelInput.dataset.manual === '1';
                if (isManual) return;
                labelInput.value = buildLabel(startInput.value, endInput.value);
            }

            labelInput.addEventListener('input', function () {
                labelInput.dataset.manual = labelInput.value.trim() ? '1' : '0';
            });

            startInput.addEventListener('change', refreshLabel);
            endInput.addEventListener('change', refreshLabel);
            refreshLabel();
        }

        function reindexRows() {
            const rows = Array.from(tableBody.querySelectorAll('tr'));
            rows.forEach((row, idx) => {
                row.querySelectorAll('input').forEach((input) => {
                    input.name = input.name.replace(/slots\[\d+\]/, `slots[${idx}]`);
                });
            });
        }

        function addRow() {
            const hintRow = tableBody.querySelector('tr.table-secondary');
            if (hintRow) {
                hintRow.remove();
            }
            const row = makeRow(tableBody.querySelectorAll('tr').length);
            tableBody.appendChild(row);
            wireAutoLabel(
                row.querySelector('.js-slot-label'),
                row.querySelector('.js-time-start'),
                row.querySelector('.js-time-end')
            );
        }

        addBtn.addEventListener('click', addRow);
        resetBtn.addEventListener('click', function () {
            tableBody.innerHTML = '';
            tableBody.insertAdjacentHTML('beforeend', `
                <tr class="table-secondary">
                    <td colspan="8" class="small text-muted">
                        <i class="bi bi-lightbulb me-1"></i>
                        Fill details in each row, then click <strong>Save all rows</strong>. This is an input row, not sample data.
                    </td>
                </tr>
            `);
            addRow();
        });
        tableBody.addEventListener('click', function (e) {
            if (e.target.classList.contains('js-remove-row')) {
                e.target.closest('tr')?.remove();
                reindexRows();
            }
        });

        toggleSingleBtn.addEventListener('click', function () { setMode('single'); });
        toggleBulkBtn.addEventListener('click', function () { setMode('bulk'); });

        wireAutoLabel(
            document.getElementById('singleSlotLabel'),
            document.getElementById('singleTimeStart'),
            document.getElementById('singleTimeEnd')
        );
        setMode('single');
        addRow();
    })();
</script>
@endsection
