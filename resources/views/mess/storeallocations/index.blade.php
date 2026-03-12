@extends('admin.layouts.master')
@section('title', 'Mess Store Allocation')
@section('setup_content')
<div class="container-fluid mess-store-allocation-page">
    <x-breadcrum title="Mess Store Allocation"></x-breadcrum>

   <div class="card border-0 shadow-sm rounded-3">
    <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold text-dark">Mess Store Allocation</h4>
            <p class="mb-0 text-muted small">View and manage allocation of items from sub stores to mess.</p>
        </div>
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createStoreAllocationModal">
            <span class="material-symbols-rounded" style="font-size: 1.1rem;">add</span>
            <span>Add Mess Store Allocation</span>
        </button>
    </div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <hr class="my-2">

    {{-- DataTables-style top: length (left) + search (right) --}}
    <div class="row align-items-center mb-3 g-2">
        <div class="col-auto">
            <label class="col-form-label col-form-label-sm text-muted me-2">Show</label>
            <select class="form-select form-select-sm d-inline-block w-auto" id="storeAllocationPerPage">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="text-muted ms-1">entries</span>
        </div>
        <div class="col d-flex justify-content-end">
            <label class="col-form-label col-form-label-sm text-muted me-2">Search:</label>
            <input type="search" class="form-control  d-inline-block" id="storeAllocationSearch" placeholder="" style="max-width: 260px;">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table text-nowrap align-middle mb-0" id="storeAllocationTable">
            <thead>
                <tr>
                    <th style="width: 60px;">S.No</th>
                    <th class="store-alloc-sort" data-sort="store">Store Name <span class="sort-icon"></span></th>
                    <th class="store-alloc-sort" data-sort="item">Item Name <span class="sort-icon"></span></th>
                    <th class="store-alloc-sort" data-sort="type">Item Type <span class="sort-icon"></span></th>
                    <th class="store-alloc-sort text-end" data-sort="quantity">Number of Items <span class="sort-icon"></span></th>
                    <th class="store-alloc-sort" data-sort="date">Date <span class="sort-icon"></span></th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody id="storeAllocationTbody">
            @php $sn = 0; @endphp
            @foreach($allocations as $allocation)
                @foreach($allocation->items as $item)
                <tr class="store-allocation-row" data-store="{{ strtolower($allocation->subStore->sub_store_name ?? '') }}" data-item="{{ strtolower($item->itemSubcategory->item_name ?? '') }}" data-type="{{ strtolower(optional($item->itemSubcategory->category)->category_name ?? '') }}" data-quantity="{{ $item->quantity }}" data-date="{{ $allocation->allocation_date ? $allocation->allocation_date->format('Y-m-d') : '' }}">
                    <td class="col-sno">{{ ++$sn }}</td>
                    <td>{{ $allocation->subStore->sub_store_name ?? 'N/A' }}</td>
                    <td>{{ $item->itemSubcategory->item_name ?? 'N/A' }}</td>
                    <td>{{ optional($item->itemSubcategory->category)->category_name ?? 'N/A' }}</td>
                    <td class="text-end">{{ $item->quantity }}</td>
                    <td>{{ $allocation->allocation_date ? $allocation->allocation_date->format('d-m-Y') : '—' }}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-info btn-edit-allocation text-primary bg-transparent border-0" data-allocation-id="{{ $allocation->id }}" title="Edit allocation">
                            <span class="material-symbols-rounded" style="font-size: 1.1rem;">edit</span>
                        </button>
                    </td>
                </tr>
                @endforeach
            @endforeach
            @if($allocations->isEmpty())
                <tr id="storeAllocationEmptyRow">
                    <td colspan="7" class="text-center text-muted py-4">No store allocations found. Click "Add Mess Store Allocation" to add one.</td>
                </tr>
            @endif
            <tr id="storeAllocationNoMatchRow" class="d-none">
                <td colspan="7" class="text-center text-muted py-4">No matching records.</td>
            </tr>
            </tbody>
        </table>
    </div>

    {{-- DataTables-style bottom: info (left) + pagination (right) --}}
    <div class="row align-items-center mt-2 flex-nowrap">
        <div class="col text-muted small" id="storeAllocationCount">Showing 0 to 0 of 0 entries</div>
        <div class="col-auto" id="storeAllocationPaginationNav">
            <ul class="pagination pagination-sm mb-0" id="storeAllocationPagination"></ul>
        </div>
    </div>
    </div>
   </div>
</div>

{{-- Create Store Allocation Modal + DataTables-style --}}
<style>
/* DataTables-style: sortable header */
.store-alloc-sort { cursor: pointer; user-select: none; white-space: nowrap; }
.store-alloc-sort:hover { opacity: 0.9; }
.store-alloc-sort .sort-icon { color: rgba(255,255,255,0.9); font-size: 0.7em; margin-left: 2px; }
/* Bottom info + pagination bar */
#storeAllocationCount { font-size: 0.875rem; }
#storeAllocationPaginationNav .pagination { gap: 2px; }
#storeAllocationPaginationNav .page-link { padding: 0.35rem 0.6rem; }
#createStoreAllocationModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#createStoreAllocationModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#createStoreAllocationModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
#editStoreAllocationModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#editStoreAllocationModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#editStoreAllocationModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
</style>
{{-- Choices.js for enhanced dropdowns --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<div class="modal fade" id="createStoreAllocationModal" tabindex="-1" aria-labelledby="createStoreAllocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.storeallocations.store') }}" id="createAllocationForm">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="createStoreAllocationModalLabel">Add Mess Store Allocation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-4">
                        <div class="card-header bg-white py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Allocation Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Store Name <span class="text-danger">*</span></label>
                                    <select name="sub_store_id" class="form-select choices-select" data-placeholder="Select Sub Store" required>
                                        <option value="">Select Sub Store</option>
                                        @foreach($subStores as $store)
                                            <option value="{{ $store->id }}">{{ $store->sub_store_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="allocation_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addAllocationItemRow">+ Add Item</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0" id="allocationItemsTable">
                                    <thead style="background-color: #af2910;">
                                        <tr>
                                            <th style="min-width: 180px; color: #fff; border-color: #af2910;">Item Name <span class="text-white">*</span></th>
                                            <th style="min-width: 90px; color: #fff; border-color: #af2910;">Item Quantity <span class="text-white">*</span></th>
                                            <th style="min-width: 80px; color: #fff; border-color: #af2910;">Item Unit <span class="text-white">*</span></th>
                                            <th style="min-width: 100px; color: #fff; border-color: #af2910;">Unit Price <span class="text-white">*</span></th>
                                            <th style="min-width: 110px; color: #fff; border-color: #af2910;">Total Price <span class="text-white">*</span></th>
                                            <th style="width: 50px; color: #fff; border-color: #af2910;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="allocationItemsBody">
                                        <tr class="allocation-item-row">
                                            <td>
                                                <select name="items[0][item_subcategory_id]" class="form-select form-select-sm alloc-item-select choices-select" data-placeholder="Select Item" required>
                                                    <option value="">Select Item</option>
                                                    @foreach($itemSubcategories as $sub)
                                                        <option value="{{ $sub['id'] }}" data-unit="{{ e($sub['unit_measurement']) }}">{{ $sub['item_name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="number" name="items[0][quantity]" class="form-control  alloc-qty" step="0.01" min="0.01" placeholder="0" required></td>
                                            <td><input type="text" name="items[0][unit]" class="form-control  alloc-unit" readonly placeholder="—"></td>
                                            <td><input type="number" name="items[0][unit_price]" class="form-control  alloc-unit-price" step="0.01" min="0" placeholder="0" required></td>
                                            <td><input type="text" class="form-control  alloc-line-total bg-light" readonly placeholder="0.00"></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger alloc-remove-row" disabled title="Remove">×</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Store Allocation</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Store Allocation Modal --}}
<div class="modal fade" id="editStoreAllocationModal" tabindex="-1" aria-labelledby="editStoreAllocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" id="editAllocationForm" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editStoreAllocationModalLabel">Edit Mess Store Allocation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-4">
                        <div class="card-header bg-white py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Allocation Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Store Name <span class="text-danger">*</span></label>
                                    <select name="sub_store_id" id="editSubStoreId" class="form-select choices-select" data-placeholder="Select Sub Store" required>
                                        <option value="">Select Sub Store</option>
                                        @foreach($subStores as $store)
                                            <option value="{{ $store->id }}">{{ $store->sub_store_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="allocation_date" id="editAllocationDate" class="form-control" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 fw-semibold text-primary">Item Details</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addEditAllocationItemRow">+ Add Item</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead style="background-color: #af2910;">
                                        <tr>
                                            <th style="min-width: 180px; color: #fff; border-color: #af2910;">Item Name <span class="text-white">*</span></th>
                                            <th style="min-width: 90px; color: #fff; border-color: #af2910;">Item Quantity <span class="text-white">*</span></th>
                                            <th style="min-width: 80px; color: #fff; border-color: #af2910;">Item Unit <span class="text-white">*</span></th>
                                            <th style="min-width: 100px; color: #fff; border-color: #af2910;">Unit Price <span class="text-white">*</span></th>
                                            <th style="min-width: 110px; color: #fff; border-color: #af2910;">Total Price <span class="text-white">*</span></th>
                                            <th style="width: 50px; color: #fff; border-color: #af2910;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="editAllocationItemsBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Store Allocation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    // --- Store allocation table: search, sort, pagination, count (client-side) ---
    const tbody = document.getElementById('storeAllocationTbody');
    const searchInput = document.getElementById('storeAllocationSearch');
    const perPageSelect = document.getElementById('storeAllocationPerPage');
    const countEl = document.getElementById('storeAllocationCount');
    const paginationEl = document.getElementById('storeAllocationPagination');
    const emptyRow = document.getElementById('storeAllocationEmptyRow');
    const noMatchRow = document.getElementById('storeAllocationNoMatchRow');

    let currentSort = { col: null, dir: 1 }; // 1 asc, -1 desc

    function getDataRows() {
        return Array.from(document.querySelectorAll('#storeAllocationTbody tr.store-allocation-row'));
    }

    function getFilteredRows() {
        const q = (searchInput && searchInput.value) ? searchInput.value.trim().toLowerCase() : '';
        const rows = getDataRows();
        if (!q) return rows;
        return rows.filter(function(tr) {
            const store = (tr.dataset.store || '').toLowerCase();
            const item = (tr.dataset.item || '').toLowerCase();
            const type = (tr.dataset.type || '').toLowerCase();
            const quantity = String(tr.dataset.quantity || '');
            const date = (tr.dataset.date || '').toLowerCase();
            return store.includes(q) || item.includes(q) || type.includes(q) || quantity.includes(q) || date.includes(q);
        });
    }

    function sortRows(rows, col, dir) {
        const key = col === 'store' ? 'store' : col === 'item' ? 'item' : col === 'type' ? 'type' : col === 'quantity' ? 'quantity' : 'date';
        return rows.slice().sort(function(a, b) {
            let va = a.dataset[key];
            let vb = b.dataset[key];
            if (key === 'quantity') {
                va = parseFloat(va) || 0;
                vb = parseFloat(vb) || 0;
                return dir * (va - vb);
            }
            if (key === 'date') {
                return dir * ((va || '').localeCompare(vb || ''));
            }
            return dir * ((va || '').localeCompare(vb || ''));
        });
    }

    function renderTable() {
        const filtered = getFilteredRows();
        const perPage = parseInt(perPageSelect && perPageSelect.value ? perPageSelect.value : 10, 10) || 10;
        const sorted = currentSort.col ? sortRows(filtered, currentSort.col, currentSort.dir) : filtered;
        const total = sorted.length;
        let page = parseInt(document.body.getAttribute('data-store-alloc-page') || '1', 10) || 1;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        page = Math.min(page, totalPages);

        const start = (page - 1) * perPage;
        const end = start + perPage;
        const pageRows = sorted.slice(start, end);

        // Hide empty / no-match rows when we have data rows
        if (emptyRow) emptyRow.classList.add('d-none');
        if (noMatchRow) noMatchRow.classList.add('d-none');
        if (total === 0 && getDataRows().length === 0 && emptyRow) emptyRow.classList.remove('d-none');
        if (total === 0 && getDataRows().length > 0 && noMatchRow) noMatchRow.classList.remove('d-none');

        // Re-append data rows in sorted order (appendChild moves nodes from current parent to fragment)
        const fragment = document.createDocumentFragment();
        sorted.forEach(function(r) { fragment.appendChild(r); });
        // Insert sorted rows at start of tbody (before empty/noMatch placeholder rows)
        const first = tbody.firstChild;
        tbody.insertBefore(fragment, first);

        getDataRows().forEach(function(tr, i) {
            const globalIndex = sorted.indexOf(tr);
            const onPage = globalIndex >= start && globalIndex < end;
            tr.classList.toggle('d-none', !onPage);
            const snoCell = tr.querySelector('.col-sno');
            if (snoCell) snoCell.textContent = globalIndex + 1;
        });

        document.body.setAttribute('data-store-alloc-page', String(page));

        // Count text (DataTables style: "Showing X to Y of Z entries")
        if (countEl) {
            if (total === 0) {
                const totalRows = getDataRows().length;
                countEl.textContent = 'Showing 0 to 0 of ' + totalRows + ' entries';
            } else {
                countEl.textContent = 'Showing ' + (start + 1) + ' to ' + Math.min(end, total) + ' of ' + total + ' entries';
            }
        }

        // Pagination UI (DataTables-style: First, Previous, numbers, Next, Last)
        const nav = document.getElementById('storeAllocationPaginationNav');
        if (nav) nav.classList.remove('d-none');
        if (paginationEl) {
            paginationEl.innerHTML = '';
            if (total === 0) return;
            const ul = paginationEl;
            function addPageItem(label, pageNum, disabled, active) {
                const li = document.createElement('li');
                li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                const a = document.createElement('a');
                a.className = 'page-link';
                a.href = '#';
                a.setAttribute('tabindex', disabled ? '-1' : '0');
                a.textContent = label;
                a.addEventListener('click', function(e) { e.preventDefault(); if (!disabled && pageNum) { document.body.setAttribute('data-store-alloc-page', String(pageNum)); renderTable(); } });
                li.appendChild(a);
                ul.appendChild(li);
            }
            addPageItem('First', 1, page <= 1);
            addPageItem('Previous', page - 1, page <= 1);
            for (let i = 1; i <= totalPages; i++) addPageItem(String(i), i, false, i === page);
            addPageItem('Next', page + 1, page >= totalPages);
            addPageItem('Last', totalPages, page >= totalPages);
        }
    }

    function updateSortIcons() {
        document.querySelectorAll('.store-alloc-sort .sort-icon').forEach(function(span) {
            span.textContent = '';
            const th = span.closest('th');
            if (th && th.dataset.sort === currentSort.col) span.textContent = currentSort.dir === 1 ? ' ▲' : ' ▼';
        });
    }

    if (searchInput) searchInput.addEventListener('input', function() { document.body.setAttribute('data-store-alloc-page', '1'); renderTable(); });
    if (perPageSelect) perPageSelect.addEventListener('change', function() { document.body.setAttribute('data-store-alloc-page', '1'); renderTable(); });
    document.querySelectorAll('.store-alloc-sort').forEach(function(th) {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            const col = th.dataset.sort;
            if (!col) return;
            currentSort.dir = (currentSort.col === col ? -currentSort.dir : 1);
            currentSort.col = col;
            updateSortIcons();
            renderTable();
        });
    });

    if (tbody && getDataRows().length) {
        renderTable();
        updateSortIcons();
    } else if (countEl) {
        countEl.textContent = 'Showing 0 to 0 of 0 entries';
    }
})();

(function() {
    const itemSubcategories = @json($itemSubcategories);
    const editBaseUrl = "{{ url('admin/mess/storeallocations') }}";
    let createRowIndex = 1;
    let editRowIndex = 0;

    function initChoices(root) {
        if (typeof window.Choices === 'undefined') return;

        const scope = root || document;
        scope.querySelectorAll('select.choices-select').forEach(function(el) {
            if (el.dataset.choicesInitialized === 'true') return;

            const placeholder = el.getAttribute('data-placeholder') || 'Select';
            el._choices = new Choices(el, {
                shouldSort: false,
                placeholder: true,
                placeholderValue: placeholder,
                searchPlaceholderValue: 'Search...',
            });
            el.dataset.choicesInitialized = 'true';
        });
    }

    function destroyChoices(el) {
        if (!el) return;
        if (el._choices) {
            el._choices.destroy();
            el._choices = null;
        }
        delete el.dataset.choicesInitialized;
    }

    function getItemRowHtml(index, editItem) {
        const selected = editItem && editItem.item_subcategory_id ? editItem.item_subcategory_id : '';
        const options = itemSubcategories.map(s =>
            `<option value="${s.id}" data-unit="${(s.unit_measurement || '').replace(/"/g, '&quot;')}" ${s.id == selected ? 'selected' : ''}>${(s.item_name || '—').replace(/</g, '&lt;')}</option>`
        ).join('');
        const qty = editItem ? editItem.quantity : '';
        const price = editItem ? editItem.unit_price : '';
        const unit = editItem && editItem.unit ? editItem.unit.replace(/"/g, '&quot;') : '';
        const lineTotal = editItem ? editItem.total_price : '';
        return `
        <tr class="allocation-item-row edit-alloc-item-row">
            <td>
                <select name="items[${index}][item_subcategory_id]" class="form-select form-select-sm alloc-item-select choices-select" data-placeholder="Select Item" required>
                    <option value="">Select Item</option>
                    ${options}
                </select>
            </td>
            <td><input type="number" name="items[${index}][quantity]" class="form-control  alloc-qty" step="0.01" min="0.01" placeholder="0" value="${qty}" required></td>
            <td><input type="text" name="items[${index}][unit]" class="form-control  alloc-unit" readonly placeholder="—" value="${unit}"></td>
            <td><input type="number" name="items[${index}][unit_price]" class="form-control  alloc-unit-price" step="0.01" min="0" placeholder="0" value="${price}" required></td>
            <td><input type="text" class="form-control  alloc-line-total bg-light" readonly placeholder="0.00" value="${lineTotal}"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger alloc-remove-row" title="Remove">×</button></td>
        </tr>`;
    }

    function updateUnit(row) {
        const select = row.querySelector('.alloc-item-select');
        const opt = select && select.options[select.selectedIndex];
        const unitInput = row.querySelector('.alloc-unit');
        if (unitInput) unitInput.value = opt && opt.dataset.unit ? opt.dataset.unit : '';
    }

    function calcLineTotal(row) {
        const qty = parseFloat(row.querySelector('.alloc-qty').value) || 0;
        const price = parseFloat(row.querySelector('.alloc-unit-price').value) || 0;
        const total = qty * price;
        const totalInput = row.querySelector('.alloc-line-total');
        if (totalInput) totalInput.value = total.toFixed(2);
    }

    function updateCreateRemoveButtons() {
        const rows = document.querySelectorAll('#allocationItemsBody .allocation-item-row');
        rows.forEach(row => {
            const btn = row.querySelector('.alloc-remove-row');
            if (btn) btn.disabled = rows.length <= 1;
        });
    }

    function updateEditRemoveButtons() {
        const rows = document.querySelectorAll('#editAllocationItemsBody .allocation-item-row');
        rows.forEach(row => {
            const btn = row.querySelector('.alloc-remove-row');
            if (btn) btn.disabled = rows.length <= 1;
        });
    }

    document.getElementById('addAllocationItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('allocationItemsBody');
        tbody.insertAdjacentHTML('beforeend', getItemRowHtml(createRowIndex, null));
        const row = tbody.lastElementChild;
        if (row) initChoices(row);
        createRowIndex++;
        updateCreateRemoveButtons();
    });

    document.getElementById('allocationItemsBody').addEventListener('change', function(e) {
        if (e.target.classList.contains('alloc-item-select')) {
            const row = e.target.closest('.allocation-item-row');
            if (row) { updateUnit(row); calcLineTotal(row); }
        }
    });
    document.getElementById('allocationItemsBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('alloc-qty') || e.target.classList.contains('alloc-unit-price')) {
            const row = e.target.closest('.allocation-item-row');
            if (row) calcLineTotal(row);
        }
    });
    document.getElementById('allocationItemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('alloc-remove-row')) {
            const row = e.target.closest('.allocation-item-row');
            if (row && document.querySelectorAll('#allocationItemsBody .allocation-item-row').length > 1) {
                destroyChoices(row.querySelector('select.choices-select'));
                row.remove();
                updateCreateRemoveButtons();
            }
        }
    });

    document.addEventListener('mousedown', function(e) {
        const btn = e.target.closest('.btn-edit-allocation');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const id = btn.getAttribute('data-allocation-id');
        fetch(editBaseUrl + '/' + id + '/edit', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                const a = data.allocation;
                const items = data.items || [];
                document.getElementById('editAllocationForm').action = editBaseUrl + '/' + id;
                const editSubStoreSelect = document.getElementById('editSubStoreId');
                editSubStoreSelect.value = a.sub_store_id || '';
                if (editSubStoreSelect._choices && a.sub_store_id) {
                    editSubStoreSelect._choices.setChoiceByValue(String(a.sub_store_id));
                } else {
                    editSubStoreSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
                document.getElementById('editAllocationDate').value = a.allocation_date || '';
                const tbody = document.getElementById('editAllocationItemsBody');
                tbody.querySelectorAll('select.choices-select').forEach(destroyChoices);
                tbody.innerHTML = '';
                if (items.length === 0) {
                    tbody.insertAdjacentHTML('beforeend', getItemRowHtml(0, null));
                    editRowIndex = 1;
                } else {
                    items.forEach((item, i) => {
                        tbody.insertAdjacentHTML('beforeend', getItemRowHtml(i, item));
                    });
                    editRowIndex = items.length;
                }
                initChoices(document.getElementById('editStoreAllocationModal'));
                tbody.querySelectorAll('select.alloc-item-select').forEach(function(selectEl) {
                    if (selectEl._choices && selectEl.value) {
                        selectEl._choices.setChoiceByValue(String(selectEl.value));
                    }
                });
                updateEditRemoveButtons();
                new bootstrap.Modal(document.getElementById('editStoreAllocationModal')).show();
            })
            .catch(err => { console.error(err); alert('Failed to load store allocation.'); });
    }, true);

    document.getElementById('addEditAllocationItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('editAllocationItemsBody');
        tbody.insertAdjacentHTML('beforeend', getItemRowHtml(editRowIndex, null));
        const row = tbody.lastElementChild;
        if (row) initChoices(row);
        editRowIndex++;
        updateEditRemoveButtons();
    });

    document.getElementById('editAllocationItemsBody').addEventListener('change', function(e) {
        if (e.target.classList.contains('alloc-item-select')) {
            const row = e.target.closest('.allocation-item-row');
            if (row) { updateUnit(row); calcLineTotal(row); }
        }
    });
    document.getElementById('editAllocationItemsBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('alloc-qty') || e.target.classList.contains('alloc-unit-price')) {
            const row = e.target.closest('.allocation-item-row');
            if (row) calcLineTotal(row);
        }
    });
    document.getElementById('editAllocationItemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('alloc-remove-row')) {
            const row = e.target.closest('.allocation-item-row');
            if (row && document.querySelectorAll('#editAllocationItemsBody .allocation-item-row').length > 1) {
                destroyChoices(row.querySelector('select.choices-select'));
                row.remove();
                updateEditRemoveButtons();
            }
        }
    });

    const createModal = document.getElementById('createStoreAllocationModal');
    const allocTable = document.getElementById('allocationItemsTable');
    if (createModal && allocTable) {
        createModal.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && allocTable.contains(document.activeElement)) {
                e.preventDefault();
                document.getElementById('addAllocationItemRow').click();
            }
        });
    }

    // Initialize Choices.js for all modal dropdowns, including dynamic rows
    document.addEventListener('DOMContentLoaded', function() {
        initChoices(document.getElementById('createStoreAllocationModal'));
        initChoices(document.getElementById('editStoreAllocationModal'));
    });
})();
</script>
@endsection
