@extends('admin.layouts.master')
@section('title', 'ADD Selling Voucher')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>ADD Selling Voucher</h4>
        <a href="{{ route('admin.mess.material-management.index') }}" class="btn btn-secondary">Back to List</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.mess.material-management.store') }}" method="POST" id="sellingVoucherForm">
        @csrf

        {{-- Client Type --}}
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold">Client Type <span class="text-danger">*</span></h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3">
                    @foreach($clientTypes as $slug => $label)
                        <div class="form-check">
                            <input class="form-check-input client-type-radio" type="radio" name="client_type_slug" id="ct_{{ $slug }}" value="{{ $slug }}" {{ old('client_type_slug') === $slug ? 'checked' : '' }} required>
                            <label class="form-check-label" for="ct_{{ $slug }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Conditional fields by Client Type --}}
        <div class="card mb-4" id="clientDetailsCard">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold">Client Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Payment Type <span class="text-danger">*</span></label>
                        <select name="payment_type" class="form-select" required>
                            <option value="1" {{ old('payment_type', '1') == '1' ? 'selected' : '' }}>Credit</option>
                            <option value="0" {{ old('payment_type') == '0' ? 'selected' : '' }}>Cash</option>
                            <option value="2" {{ old('payment_type') == '2' ? 'selected' : '' }}>Online</option>
                        </select>
                        <small class="text-muted" id="paymentTypeHint">Employee / OT / Course: Credit only</small>
                    </div>
                    <div class="col-md-4" id="clientNameWrap">
                        <label class="form-label">Client Name <span class="text-danger">*</span></label>
                        <select name="client_type_pk" class="form-select" id="clientNameSelect">
                            <option value="">Select Client Name</option>
                            @foreach($clientNamesByType as $type => $list)
                                @foreach($list as $c)
                                    <option value="{{ $c->id }}" data-type="{{ $c->client_type }}" data-name="{{ $c->client_name }}">{{ $c->client_name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="client_name" class="form-control" value="{{ old('client_name') }}" placeholder="Client / section / role name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Transfer From Store <span class="text-danger">*</span></label>
                        <select name="inve_store_master_pk" class="form-select" required>
                            <option value="">Select Store</option>
                            @foreach($stores as $store)
                                <option value="{{ $store['id'] }}" {{ old('inve_store_master_pk') == $store['id'] ? 'selected' : '' }}>{{ $store['store_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Item Selling Details (same as Create Purchase Order) --}}
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Item Selling Details</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addItemRow">+ Add Item</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" id="itemsTable">
                        <thead style="background-color: #af2910;">
                            <tr>
                                <th style="color: #fff; min-width: 160px;">Item <span class="text-white">*</span></th>
                                <th style="color: #fff; width: 80px;">Unit</th>
                                <th style="color: #fff; width: 100px;">Available Qty <span class="text-white">*</span></th>
                                <th style="color: #fff; width: 100px;">Issue Qty <span class="text-white">*</span></th>
                                <th style="color: #fff; width: 90px;">Left Qty</th>
                                <th style="color: #fff; width: 90px;">Rate <span class="text-white">*</span></th>
                                <th style="color: #fff; width: 100px;">Total</th>
                                <th style="width: 50px; color: #fff;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr class="sv-item-row">
                                <td>
                                    <select name="items[0][item_subcategory_id]" class="form-select form-select-sm sv-item-select" required>
                                        <option value="">Select Item</option>
                                        @foreach($itemSubcategories as $s)
                                            <option value="{{ $s['id'] }}" data-unit="{{ e($s['unit_measurement'] ?? '') }}">{{ e($s['item_name'] ?? '—') }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="items[0][unit]" class="form-control form-control-sm sv-unit" readonly placeholder="—"></td>
                                <td><input type="number" name="items[0][available_quantity]" class="form-control form-control-sm sv-avail bg-light" step="0.01" min="0" value="0" placeholder="0" readonly></td>
                                <td>
                                    <input type="number" name="items[0][quantity]" class="form-control form-control-sm sv-qty" step="0.01" min="0.01" placeholder="0" required>
                                    <div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div>
                                </td>
                                <td><input type="text" class="form-control form-control-sm sv-left bg-light" readonly placeholder="0"></td>
                                <td><input type="number" name="items[0][rate]" class="form-control form-control-sm sv-rate" step="0.01" min="0" placeholder="0" required></td>
                                <td><input type="text" class="form-control form-control-sm sv-total bg-light" readonly placeholder="0.00"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger sv-remove-row" disabled title="Remove">×</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light d-flex justify-content-end align-items-center">
                <span class="fw-semibold">Grand Total:</span>
                <span class="fs-5 text-primary fw-bold ms-2" id="grandTotal">₹0.00</span>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save Selling Voucher</button>
            <a href="{{ route('admin.mess.material-management.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
(function() {
    const itemSubcategories = @json($itemSubcategories);
    let rowIndex = 1;

    function enforceQtyWithinAvailable(row) {
        if (!row) return;
        const availEl = row.querySelector('.sv-avail');
        const qtyEl = row.querySelector('.sv-qty');
        if (!availEl || !qtyEl) return;

        const avail = parseFloat(availEl.value) || 0;
        const qtyRaw = qtyEl.value;
        const qty = parseFloat(qtyRaw);

        qtyEl.max = String(avail);

        if (qtyRaw === '' || Number.isNaN(qty)) {
            qtyEl.setCustomValidity('');
            qtyEl.classList.remove('is-invalid');
            return;
        }

        if (qty > avail) {
            qtyEl.setCustomValidity('Issue Qty cannot exceed Available Qty.');
            qtyEl.classList.add('is-invalid');
        } else {
            qtyEl.setCustomValidity('');
            qtyEl.classList.remove('is-invalid');
        }
    }

    function getRowHtml(index) {
        const options = itemSubcategories.map(s =>
            '<option value="' + s.id + '" data-unit="' + (s.unit_measurement || '').replace(/"/g, '&quot;') + '">' + (s.item_name || '—').replace(/</g, '&lt;') + '</option>'
        ).join('');
        return '<tr class="sv-item-row">' +
            '<td><select name="items[' + index + '][item_subcategory_id]" class="form-select form-select-sm sv-item-select" required><option value="">Select Item</option>' + options + '</select></td>' +
            '<td><input type="text" name="items[' + index + '][unit]" class="form-control form-control-sm sv-unit" readonly placeholder="—"></td>' +
            '<td><input type="number" name="items[' + index + '][available_quantity]" class="form-control form-control-sm sv-avail bg-light" step="0.01" min="0" value="0" placeholder="0" readonly></td>' +
            '<td><input type="number" name="items[' + index + '][quantity]" class="form-control form-control-sm sv-qty" step="0.01" min="0.01" placeholder="0" required><div class="invalid-feedback">Issue Qty cannot exceed Available Qty.</div></td>' +
            '<td><input type="text" class="form-control form-control-sm sv-left bg-light" readonly placeholder="0"></td>' +
            '<td><input type="number" name="items[' + index + '][rate]" class="form-control form-control-sm sv-rate" step="0.01" min="0" placeholder="0" required></td>' +
            '<td><input type="text" class="form-control form-control-sm sv-total bg-light" readonly placeholder="0.00"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger sv-remove-row" title="Remove">×</button></td>' +
            '</tr>';
    }

    function updateUnit(row) {
        const sel = row.querySelector('.sv-item-select');
        const opt = sel && sel.options[sel.selectedIndex];
        const unitInp = row.querySelector('.sv-unit');
        if (unitInp) unitInp.value = opt && opt.dataset.unit ? opt.dataset.unit : '';
    }

    function calcRow(row) {
        const avail = parseFloat(row.querySelector('.sv-avail').value) || 0;
        const qty = parseFloat(row.querySelector('.sv-qty').value) || 0;
        const rate = parseFloat(row.querySelector('.sv-rate').value) || 0;
        const left = Math.max(0, avail - qty);
        const total = qty * rate;
        row.querySelector('.sv-left').value = left;
        row.querySelector('.sv-total').value = total.toFixed(2);
        enforceQtyWithinAvailable(row);
    }

    function updateGrandTotal() {
        let sum = 0;
        document.querySelectorAll('#itemsBody .sv-item-row').forEach(row => {
            const t = row.querySelector('.sv-total');
            if (t && t.value) sum += parseFloat(t.value) || 0;
        });
        const el = document.getElementById('grandTotal');
        if (el) el.textContent = '₹' + sum.toFixed(2);
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('#itemsBody .sv-item-row');
        rows.forEach((row, i) => {
            const btn = row.querySelector('.sv-remove-row');
            if (btn) btn.disabled = rows.length <= 1;
        });
    }

    document.getElementById('addItemRow').addEventListener('click', function() {
        const tbody = document.getElementById('itemsBody');
        tbody.insertAdjacentHTML('beforeend', getRowHtml(rowIndex));
        rowIndex++;
        updateRemoveButtons();
    });

    document.getElementById('itemsBody').addEventListener('change', function(e) {
        if (e.target.classList.contains('sv-item-select')) {
            const row = e.target.closest('.sv-item-row');
            if (row) { updateUnit(row); calcRow(row); updateGrandTotal(); }
        }
    });

    document.getElementById('itemsBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('sv-avail') || e.target.classList.contains('sv-qty') || e.target.classList.contains('sv-rate')) {
            const row = e.target.closest('.sv-item-row');
            if (row) { enforceQtyWithinAvailable(row); calcRow(row); updateGrandTotal(); }
        }
    });

    document.getElementById('itemsBody').addEventListener('click', function(e) {
        if (e.target.classList.contains('sv-remove-row')) {
            const row = e.target.closest('.sv-item-row');
            if (row && document.querySelectorAll('#itemsBody .sv-item-row').length > 1) {
                row.remove();
                updateGrandTotal();
                updateRemoveButtons();
            }
        }
    });

    // Client type: restrict payment for Employee/OT/Course to Credit only
    const creditOnly = ['employee', 'ot', 'course'];
    document.querySelectorAll('.client-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const paymentSelect = document.querySelector('select[name="payment_type"]');
            const hint = document.getElementById('paymentTypeHint');
            if (creditOnly.indexOf(this.value) !== -1) {
                paymentSelect.value = '1';
                paymentSelect.querySelectorAll('option').forEach(function(opt) {
                    opt.disabled = (opt.value !== '' && opt.value !== '1');
                });
                if (hint) hint.textContent = 'Credit only for this client type';
            } else {
                paymentSelect.querySelectorAll('option').forEach(function(opt) { opt.disabled = false; });
                if (hint) hint.textContent = 'Cash / Online / Credit';
            }
            // Filter client name dropdown by type
            const clientSelect = document.getElementById('clientNameSelect');
            if (clientSelect) {
                clientSelect.querySelectorAll('option').forEach(function(opt) {
                    if (opt.value === '') { opt.hidden = false; return; }
                    opt.hidden = opt.dataset.type !== this.value;
                }.bind(this));
            }
        });
    });
    // Trigger once for initial selection
    const checked = document.querySelector('.client-type-radio:checked');
    if (checked) checked.dispatchEvent(new Event('change'));
})();
</script>
@endsection
