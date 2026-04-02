@extends('admin.layouts.master')
@section('title', 'Mess Store Allocation')
@section('setup_content')
@php
    $canDeleteStoreAllocation = hasRole('Admin') || hasRole('Mess-Admin');
@endphp
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

    <div class="table-responsive">
        <table class="table datatable" data-export="false" id="storeAllocationTable">
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
                <tr class="store-allocation-row" data-store="{{ strtolower($allocation->subStore->sub_store_name ?? '') }}" data-item="{{ strtolower($item->itemSubcategory->item_name ?? '') }}" data-type="{{ strtolower(optional(optional($item->itemSubcategory)->category)->category_name ?? '') }}" data-quantity="{{ $item->quantity }}" data-date="{{ $allocation->allocation_date ? $allocation->allocation_date->format('Y-m-d') : '' }}">
                    <td class="col-sno">{{ ++$sn }}</td>
                    <td>{{ $allocation->subStore->sub_store_name ?? 'N/A' }}</td>
                    <td>{{ $item->itemSubcategory->item_name ?? 'N/A' }}</td>
                    <td>{{ optional(optional($item->itemSubcategory)->category)->category_name ?? 'N/A' }}</td>
                    <td class="text-end">{{ $item->quantity }}</td>
                    <td>{{ $allocation->allocation_date ? $allocation->allocation_date->format('d-m-Y') : '—' }}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-info btn-edit-allocation text-primary bg-transparent border-0 p-0" data-allocation-id="{{ $allocation->id }}" title="Edit allocation">
                            <span class="material-symbols-rounded" style="font-size: 1.1rem;">edit</span>
                        </button>
                        @if($canDeleteStoreAllocation)
                            <form action="{{ route('admin.mess.storeallocations.destroy', $allocation->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this store allocation?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger bg-transparent border-0 p-0 text-primary" title="Delete allocation">
                                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">delete</span>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            @endforeach
            @if($allocations->isEmpty())
                <tr id="storeAllocationEmptyRow">
                    <td class="border-0"></td>
                    <td class="border-0"></td>
                    <td class="border-0"></td>
                    <td class="text-center text-muted py-4 border-0">No store allocations found. Click "Add Mess Store Allocation" to add one.</td>
                    <td class="border-0"></td>
                    <td class="border-0"></td>
                    <td class="border-0"></td>
                </tr>
            @endif
            <tr id="storeAllocationNoMatchRow" class="d-none">
                <td class="border-0"></td>
                <td class="border-0"></td>
                <td class="border-0"></td>
                <td class="text-center text-muted py-4 border-0">No matching records.</td>
                <td class="border-0"></td>
                <td class="border-0"></td>
                <td class="border-0"></td>
            </tr>
            </tbody>
        </table>
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
#createStoreAllocationModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); scrollbar-gutter: stable; overscroll-behavior: contain; }
#editStoreAllocationModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#editStoreAllocationModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#editStoreAllocationModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); scrollbar-gutter: stable; overscroll-behavior: contain; }
/* Item Details: keep dropdown above table/layout layers */
.alloc-items-table-wrap {
    position: relative;
    overflow: visible !important;
    z-index: 1;
}
.alloc-items-table-wrap .table { margin-bottom: 0; }
#createStoreAllocationModal .modal-body .card-body,
#editStoreAllocationModal .modal-body .card-body {
    overflow: visible;
}
#createStoreAllocationModal .modal-body .card:has(#allocationItemsTable),
#editStoreAllocationModal .modal-body .card:has(#editAllocationItemsTable) {
    overflow: visible;
}
#createStoreAllocationModal #allocationItemsTable td,
#editStoreAllocationModal #editAllocationItemsTable td { vertical-align: top; }
#createStoreAllocationModal #allocationItemsTable td:first-child,
#editStoreAllocationModal #editAllocationItemsTable td:first-child { position: relative; }
#createStoreAllocationModal #allocationItemsTable .choices,
#editStoreAllocationModal #editAllocationItemsTable .choices {
    max-width: 100%;
    width: 100%;
    min-width: 0;
}
#createStoreAllocationModal #allocationItemsTable .choices__inner,
#editStoreAllocationModal #editAllocationItemsTable .choices__inner {
    min-height: 31px;
}
#createStoreAllocationModal #allocationItemsTable .choices.is-open .choices__inner,
#editStoreAllocationModal #editAllocationItemsTable .choices.is-open .choices__inner {
    min-height: 31px;
}
/* Item row open: stack above following rows (table paints lower rows on top otherwise) */
#createStoreAllocationModal #allocationItemsTable tbody tr:has(.choices.is-open),
#editStoreAllocationModal #editAllocationItemsTable tbody tr:has(.choices.is-open) {
    position: relative;
    z-index: 3000;
}
#createStoreAllocationModal #allocationItemsTable .choices.is-open,
#editStoreAllocationModal #editAllocationItemsTable .choices.is-open {
    position: relative;
    z-index: 3001;
}
/* Default modal Choices stacking (Store Name etc.) */
#createStoreAllocationModal .choices,
#editStoreAllocationModal .choices {
    position: relative;
}
#createStoreAllocationModal .choices.is-open,
#editStoreAllocationModal .choices.is-open {
    z-index: 20040;
}
#createStoreAllocationModal .choices__list--dropdown,
#editStoreAllocationModal .choices__list--dropdown {
    z-index: 20041 !important;
}
#createStoreAllocationModal #allocationItemsTable .choices__list--dropdown.alloc-item-dd-fixed,
#editStoreAllocationModal #editAllocationItemsTable .choices__list--dropdown.alloc-item-dd-fixed {
    z-index: 20050 !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
/* Dropdown open: let list escape modal-body overflow; footer stays under body layer */
#createStoreAllocationModal .modal-body.alloc-dropdown-open,
#editStoreAllocationModal .modal-body.alloc-dropdown-open {
    overflow: visible !important;
    position: relative;
    z-index: 2;
}
/* CSS fallback if JS dropdown events are missed */
#createStoreAllocationModal .modal-body:has(.choices.is-open),
#editStoreAllocationModal .modal-body:has(.choices.is-open) {
    overflow: visible !important;
}
#createStoreAllocationModal .modal-footer,
#editStoreAllocationModal .modal-footer {
    position: relative;
    z-index: 1;
}
#createStoreAllocationModal .modal-content,
#editStoreAllocationModal .modal-content {
    overflow: visible;
}
</style>
{{-- Choices.js for enhanced dropdowns --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<div class="modal fade" id="createStoreAllocationModal" tabindex="-1" aria-labelledby="createStoreAllocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.storeallocations.store') }}" id="createAllocationForm" novalidate>
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
                            <div class="alloc-items-table-wrap">
                                <table class="table table-bordered mb-0" id="allocationItemsTable" style="table-layout: fixed;">
                                    <thead>
                                        <tr>
                                            <th style="width: 280px; min-width: 280px;">Item Name <span class="text-danger">*</span></th>
                                            <th style="min-width: 90px;">Item Quantity <span class="text-danger">*</span></th>
                                            <th style="min-width: 80px;">Item Unit <span class="text-danger">*</span></th>
                                            <th style="min-width: 100px;">Unit Price <span class="text-danger">*</span></th>
                                            <th style="min-width: 110px;">Total Price <span class="text-danger">*</span></th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="allocationItemsBody">
                                        <tr class="allocation-item-row">
                                            <td style="width: 280px;">
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
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editAllocationForm" action="" novalidate>
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
                            <div class="alloc-items-table-wrap">
                                <table class="table table-bordered mb-0" id="editAllocationItemsTable" style="table-layout: fixed;">
                                    <thead>
                                        <tr>
                                            <th style="width: 280px; min-width: 280px;">Item Name <span class="text-danger">*</span></th>
                                            <th style="min-width: 90px;">Item Quantity <span class="text-danger">*</span></th>
                                            <th style="min-width: 80px;">Item Unit <span class="text-danger">*</span></th>
                                            <th style="min-width: 100px;">Unit Price <span class="text-danger">*</span></th>
                                            <th style="min-width: 110px;">Total Price <span class="text-danger">*</span></th>
                                            <th style="width: 50px;"></th>
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

    function positionAllocItemDropdownFixed(selectEl) {
        const wrap = selectEl.closest('.choices');
        if (!wrap) return;
        const inner = wrap.querySelector('.choices__inner');
        const dd = wrap.querySelector('.choices__list--dropdown');
        if (!inner || !dd) return;
        const r = inner.getBoundingClientRect();
        const spaceBelow = window.innerHeight - r.bottom - 8;
        const maxH = Math.min(320, Math.max(120, spaceBelow));
        dd.classList.add('alloc-item-dd-fixed');
        dd.style.position = 'fixed';
        dd.style.left = r.left + 'px';
        dd.style.top = (r.bottom + 2) + 'px';
        dd.style.width = Math.max(r.width, 1) + 'px';
        dd.style.maxHeight = maxH + 'px';
        dd.style.overflowY = 'auto';
        dd.style.zIndex = '20050';
        dd.style.boxSizing = 'border-box';
    }

    function clearAllocItemDropdownFixed(selectEl) {
        const wrap = selectEl.closest('.choices');
        if (!wrap) return;
        const dd = wrap.querySelector('.choices__list--dropdown');
        if (!dd) return;
        dd.classList.remove('alloc-item-dd-fixed');
        dd.style.position = '';
        dd.style.left = '';
        dd.style.top = '';
        dd.style.width = '';
        dd.style.maxHeight = '';
        dd.style.overflowY = '';
        dd.style.zIndex = '';
        dd.style.boxSizing = '';
    }

    function initChoices(root) {
        if (typeof window.Choices === 'undefined') return;

        const scope = root || document;
        scope.querySelectorAll('select.choices-select').forEach(function(el) {
            if (el.dataset.choicesInitialized === 'true') return;

            const placeholder = el.getAttribute('data-placeholder') || 'Select';
            const opts = {
                shouldSort: false,
                position: 'bottom',
                placeholder: true,
                placeholderValue: placeholder,
                searchPlaceholderValue: 'Search...',
            };
            el._choices = new Choices(el, opts);
            el.dataset.choicesInitialized = 'true';

            function setModalBodyDropdownOpen(show) {
                const mb = el.closest('.modal-body');
                if (!mb) return;
                if (show) {
                    mb.classList.add('alloc-dropdown-open');
                } else {
                    requestAnimationFrame(function() {
                        if (!mb.querySelector('.choices.is-open')) {
                            mb.classList.remove('alloc-dropdown-open');
                        }
                    });
                }
            }

            el.addEventListener('showDropdown', function() {
                setModalBodyDropdownOpen(true);
                if (!el.classList.contains('alloc-item-select')) return;
                el._allocItemReposition = function() {
                    const w = el.closest('.choices');
                    if (!w || !w.classList.contains('is-open')) return;
                    positionAllocItemDropdownFixed(el);
                };
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        positionAllocItemDropdownFixed(el);
                        window.addEventListener('resize', el._allocItemReposition);
                        document.addEventListener('scroll', el._allocItemReposition, true);
                    });
                });
            });
            el.addEventListener('hideDropdown', function() {
                if (el.classList.contains('alloc-item-select')) {
                    if (el._allocItemReposition) {
                        window.removeEventListener('resize', el._allocItemReposition);
                        document.removeEventListener('scroll', el._allocItemReposition, true);
                        el._allocItemReposition = null;
                    }
                    clearAllocItemDropdownFixed(el);
                }
                setModalBodyDropdownOpen(false);
            });
        });
    }

    function destroyChoices(el) {
        if (!el) return;
        if (el._allocItemReposition) {
            window.removeEventListener('resize', el._allocItemReposition);
            document.removeEventListener('scroll', el._allocItemReposition, true);
            el._allocItemReposition = null;
        }
        clearAllocItemDropdownFixed(el);
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
            <td style="width: 280px;">
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

    /**
     * Remove item lines with no item selected (common after "+ Add Item" without choosing an item).
     * Keeps HTML5/Choices from blocking submit with no visible invalid field in the modal.
     */
    function pruneIncompleteAllocationRows(tbodySelector) {
        const tbody = document.querySelector(tbodySelector);
        if (!tbody) return;
        let rows;
        while ((rows = Array.from(tbody.querySelectorAll('.allocation-item-row'))).length > 1) {
            const emptyRow = rows.find(function(r) {
                const sel = r.querySelector('.alloc-item-select');
                return sel && !sel.value;
            });
            if (!emptyRow) break;
            destroyChoices(emptyRow.querySelector('.alloc-item-select'));
            emptyRow.remove();
        }
    }

    function validateMessAllocationForm(form, itemsTbodySelector, e) {
        const tbody = document.querySelector(itemsTbodySelector);
        if (!tbody) return true;
        pruneIncompleteAllocationRows(itemsTbodySelector);
        if (itemsTbodySelector === '#allocationItemsBody') {
            updateCreateRemoveButtons();
        } else {
            updateEditRemoveButtons();
        }

        const subStore = form.querySelector('select[name="sub_store_id"]');
        if (!subStore || !String(subStore.value || '').trim()) {
            e.preventDefault();
            alert('Please select a store.');
            return false;
        }
        const dateInput = form.querySelector('input[name="allocation_date"]');
        if (!dateInput || !String(dateInput.value || '').trim()) {
            e.preventDefault();
            alert('Please select a date.');
            return false;
        }

        const rows = Array.from(tbody.querySelectorAll('.allocation-item-row'));
        if (!rows.length) {
            e.preventDefault();
            alert('Please add at least one item line with an item selected.');
            return false;
        }
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const sel = row.querySelector('.alloc-item-select');
            if (!sel || !String(sel.value || '').trim()) {
                e.preventDefault();
                alert('Please select an item on each line, or remove incomplete rows using the × button.');
                const wrap = sel && sel.closest('.choices');
                if (wrap && typeof wrap.scrollIntoView === 'function') {
                    wrap.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
                return false;
            }
            const qty = row.querySelector('.alloc-qty');
            const price = row.querySelector('.alloc-unit-price');
            const q = qty ? parseFloat(qty.value) : NaN;
            const p = price ? parseFloat(price.value) : NaN;
            if (!qty || qty.value === '' || !Number.isFinite(q) || q < 0.01) {
                e.preventDefault();
                alert('Enter a valid quantity (minimum 0.01) for each item.');
                qty && qty.focus();
                return false;
            }
            if (!price || price.value === '' || !Number.isFinite(p) || p < 0) {
                e.preventDefault();
                alert('Enter a valid unit price for each item.');
                price && price.focus();
                return false;
            }
        }
        return true;
    }

    document.getElementById('createAllocationForm').addEventListener('submit', function(e) {
        validateMessAllocationForm(this, '#allocationItemsBody', e);
    });

    document.getElementById('editAllocationForm').addEventListener('submit', function(e) {
        const action = this.getAttribute('action');
        if (!action || action === '') {
            e.preventDefault();
            alert('Form is not ready. Please close this dialog and open Edit again.');
            return;
        }
        validateMessAllocationForm(this, '#editAllocationItemsBody', e);
    });

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
                const editModalEl = document.getElementById('editStoreAllocationModal');
                const editBsModal = bootstrap.Modal.getOrCreateInstance(editModalEl);
                editBsModal.show();
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
