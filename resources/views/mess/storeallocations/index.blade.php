@extends('admin.layouts.master')
@section('title', 'Mess Store Allocation')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Mess Store Allocation</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStoreAllocationModal">
            Add Mess Store Allocation
        </button>
    </div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead style="background-color: #af2910;">
                <tr>
                    <th style="color: #fff; border-color: #af2910; width: 60px;">S.No</th>
                    <th style="color: #fff; border-color: #af2910;">Store Name</th>
                    <th style="color: #fff; border-color: #af2910;">Item Name</th>
                    <th style="color: #fff; border-color: #af2910;">Item Type</th>
                    <th style="color: #fff; border-color: #af2910;">Number of Items</th>
                    <th style="color: #fff; border-color: #af2910;">Date</th>
                    <th style="color: #fff; border-color: #af2910; min-width: 180px;">Action</th>
                </tr>
            </thead>
            <tbody>
            @php $sn = 0; @endphp
            @foreach($allocations as $allocation)
                @foreach($allocation->items as $item)
                <tr>
                    <td>{{ ++$sn }}</td>
                    <td>{{ $allocation->subStore->sub_store_name ?? 'N/A' }}</td>
                    <td>{{ $item->itemSubcategory->item_name ?? 'N/A' }}</td>
                    <td>{{ optional($item->itemSubcategory->category)->category_name ?? 'N/A' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $allocation->allocation_date ? $allocation->allocation_date->format('d-m-Y') : '—' }}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-warning btn-edit-allocation" data-allocation-id="{{ $allocation->id }}" title="Edit">Edit</button>
                        <form action="{{ route('admin.mess.storeallocations.destroy', $allocation->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this store allocation?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            @endforeach
            @if($allocations->isEmpty())
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No store allocations found. Click "Add Mess Store Allocation" to add one.</td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

{{-- Create Store Allocation Modal --}}
<style>
#createStoreAllocationModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#createStoreAllocationModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#createStoreAllocationModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
#editStoreAllocationModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#editStoreAllocationModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#editStoreAllocationModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
</style>
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
                                    <select name="sub_store_id" class="form-select" required>
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
                                                <select name="items[0][item_subcategory_id]" class="form-select form-select-sm alloc-item-select" required>
                                                    <option value="">Select Item</option>
                                                    @foreach($itemSubcategories as $sub)
                                                        <option value="{{ $sub['id'] }}" data-unit="{{ e($sub['unit_measurement']) }}">{{ $sub['item_name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm alloc-qty" step="0.01" min="0.01" placeholder="0" required></td>
                                            <td><input type="text" name="items[0][unit]" class="form-control form-control-sm alloc-unit" readonly placeholder="—"></td>
                                            <td><input type="number" name="items[0][unit_price]" class="form-control form-control-sm alloc-unit-price" step="0.01" min="0" placeholder="0" required></td>
                                            <td><input type="text" class="form-control form-control-sm alloc-line-total bg-light" readonly placeholder="0.00"></td>
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
                                    <select name="sub_store_id" id="editSubStoreId" class="form-select" required>
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
    const itemSubcategories = @json($itemSubcategories);
    const editBaseUrl = "{{ url('admin/mess/storeallocations') }}";
    let createRowIndex = 1;
    let editRowIndex = 0;

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
                <select name="items[${index}][item_subcategory_id]" class="form-select form-select-sm alloc-item-select" required>
                    <option value="">Select Item</option>
                    ${options}
                </select>
            </td>
            <td><input type="number" name="items[${index}][quantity]" class="form-control form-control-sm alloc-qty" step="0.01" min="0.01" placeholder="0" value="${qty}" required></td>
            <td><input type="text" name="items[${index}][unit]" class="form-control form-control-sm alloc-unit" readonly placeholder="—" value="${unit}"></td>
            <td><input type="number" name="items[${index}][unit_price]" class="form-control form-control-sm alloc-unit-price" step="0.01" min="0" placeholder="0" value="${price}" required></td>
            <td><input type="text" class="form-control form-control-sm alloc-line-total bg-light" readonly placeholder="0.00" value="${lineTotal}"></td>
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
                row.remove();
                updateCreateRemoveButtons();
            }
        }
    });

    document.querySelector('.table-responsive').addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-edit-allocation');
        if (!btn) return;
        e.preventDefault();
        const id = btn.getAttribute('data-allocation-id');
        fetch(editBaseUrl + '/' + id + '/edit', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                const a = data.allocation;
                const items = data.items || [];
                document.getElementById('editAllocationForm').action = editBaseUrl + '/' + id;
                document.getElementById('editSubStoreId').value = a.sub_store_id || '';
                document.getElementById('editAllocationDate').value = a.allocation_date || '';
                const tbody = document.getElementById('editAllocationItemsBody');
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
                updateEditRemoveButtons();
                new bootstrap.Modal(document.getElementById('editStoreAllocationModal')).show();
            })
            .catch(err => { console.error(err); alert('Failed to load store allocation.'); });
    });

    document.getElementById('addEditAllocationItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('editAllocationItemsBody');
        tbody.insertAdjacentHTML('beforeend', getItemRowHtml(editRowIndex, null));
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
})();
</script>
@endsection
