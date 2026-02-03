@extends('layouts.master')

@section('title', 'Create New Bill')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create New Bill</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.mess.billing.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                
                <form method="POST" action="{{ route('admin.mess.billing.store') }}" id="billForm">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Sale Date <span class="text-danger">*</span></label>
                                    <input type="date" name="sale_date" class="form-control @error('sale_date') is-invalid @enderror" 
                                           value="{{ old('sale_date', date('Y-m-d')) }}" required>
                                    @error('sale_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Store <span class="text-danger">*</span></label>
                                    <select name="store_id" id="storeSelect" class="form-control @error('store_id') is-invalid @enderror" required>
                                        <option value="">Select Store</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                                {{ $store->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('store_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Buyer Type <span class="text-danger">*</span></label>
                                    <select name="buyer_type" id="buyerTypeSelect" class="form-control @error('buyer_type') is-invalid @enderror" required>
                                        <option value="">Select Buyer Type</option>
                                        <option value="2" {{ old('buyer_type') == '2' ? 'selected' : '' }}>OT</option>
                                        <option value="3" {{ old('buyer_type') == '3' ? 'selected' : '' }}>Section</option>
                                        <option value="4" {{ old('buyer_type') == '4' ? 'selected' : '' }}>Guest</option>
                                        <option value="5" {{ old('buyer_type') == '5' ? 'selected' : '' }}>Employee</option>
                                        <option value="6" {{ old('buyer_type') == '6' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('buyer_type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6" id="buyerSelectDiv" style="display: none;">
                                <div class="form-group">
                                    <label>Buyer <span class="text-danger">*</span></label>
                                    <select name="buyer_id" id="buyerSelect" class="form-control">
                                        <option value="">Select Buyer</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6" id="buyerNameDiv" style="display: none;">
                                <div class="form-group">
                                    <label>Buyer Name <span class="text-danger">*</span></label>
                                    <input type="text" name="buyer_name" class="form-control" placeholder="Enter buyer name">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Payment Mode <span class="text-danger">*</span></label>
                                    <select name="payment_mode" class="form-control @error('payment_mode') is-invalid @enderror" required>
                                        <option value="cash" {{ old('payment_mode') == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="cheque" {{ old('payment_mode') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                        <option value="credit" {{ old('payment_mode') == 'credit' ? 'selected' : '' }}>Credit</option>
                                    </select>
                                    @error('payment_mode')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Payment Type <span class="text-danger">*</span></label>
                                    <select name="payment_type" class="form-control @error('payment_type') is-invalid @enderror" required>
                                        <option value="1" {{ old('payment_type') == '1' ? 'selected' : '' }}>Paid</option>
                                        <option value="2" {{ old('payment_type') == '2' ? 'selected' : '' }}>Credit/Due</option>
                                    </select>
                                    @error('payment_type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5>Items</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="40%">Item</th>
                                        <th width="15%">Available Qty</th>
                                        <th width="15%">Quantity</th>
                                        <th width="15%">Rate</th>
                                        <th width="15%">Amount</th>
                                        <th width="5%">
                                            <button type="button" class="btn btn-sm btn-success" id="addRow">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr class="item-row">
                                        <td>
                                            <select name="items[0][item_id]" class="form-control item-select" required>
                                                <option value="">Select Item</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control available-qty" readonly>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="items[0][quantity]" 
                                                   class="form-control quantity-input" min="0.01" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="items[0][rate]" 
                                                   class="form-control rate-input" min="0" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control amount-display" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger remove-row">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <td colspan="4" class="text-right"><strong>Total Amount:</strong></td>
                                        <td colspan="2">
                                            <strong>₹<span id="totalAmount">0.00</span></strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Bill
                        </button>
                        <a href="{{ route('admin.mess.billing.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let rowCount = 1;
let availableItems = [];

$(document).ready(function() {
    // Load items when store is selected
    $('#storeSelect').on('change', function() {
        let storeId = $(this).val();
        if (storeId) {
            loadItemsByStore(storeId);
        } else {
            availableItems = [];
            $('.item-select').html('<option value="">Select Item</option>');
        }
    });
    
    // Load buyers when buyer type is selected
    $('#buyerTypeSelect').on('change', function() {
        let buyerType = $(this).val();
        
        if (buyerType == 6) {
            // Show name input for "Other" type
            $('#buyerSelectDiv').hide();
            $('#buyerNameDiv').show();
            $('#buyerSelect').prop('required', false);
            $('input[name="buyer_name"]').prop('required', true);
        } else if (buyerType) {
            // Show buyer select for other types
            $('#buyerNameDiv').hide();
            $('#buyerSelectDiv').show();
            $('input[name="buyer_name"]').prop('required', false);
            $('#buyerSelect').prop('required', true);
            loadBuyers(buyerType);
        } else {
            $('#buyerSelectDiv').hide();
            $('#buyerNameDiv').hide();
        }
    });
    
    // Add new row
    $('#addRow').on('click', function() {
        let newRow = `
            <tr class="item-row">
                <td>
                    <select name="items[${rowCount}][item_id]" class="form-control item-select" required>
                        <option value="">Select Item</option>
                        ${getItemOptions()}
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control available-qty" readonly>
                </td>
                <td>
                    <input type="number" step="0.01" name="items[${rowCount}][quantity]" 
                           class="form-control quantity-input" min="0.01" required>
                </td>
                <td>
                    <input type="number" step="0.01" name="items[${rowCount}][rate]" 
                           class="form-control rate-input" min="0" required>
                </td>
                <td>
                    <input type="text" class="form-control amount-display" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-row">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#itemsBody').append(newRow);
        rowCount++;
    });
    
    // Remove row
    $(document).on('click', '.remove-row', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
        } else {
            alert('At least one item is required');
        }
    });
    
    // Load item details when item is selected
    $(document).on('change', '.item-select', function() {
        let itemId = $(this).val();
        let row = $(this).closest('tr');
        
        if (itemId) {
            let item = availableItems.find(i => i.id == itemId);
            if (item) {
                row.find('.available-qty').val(item.quantity);
                row.find('.rate-input').val(item.price);
                calculateRowAmount(row);
            }
        } else {
            row.find('.available-qty').val('');
            row.find('.rate-input').val('');
            row.find('.amount-display').val('');
            calculateTotal();
        }
    });
    
    // Calculate amount on quantity/rate change
    $(document).on('input', '.quantity-input, .rate-input', function() {
        let row = $(this).closest('tr');
        calculateRowAmount(row);
    });
});

function loadItemsByStore(storeId) {
    $.ajax({
        url: '{{ route("admin.mess.billing.getItemsByStore") }}',
        type: 'GET',
        data: { store_id: storeId },
        success: function(response) {
            if (response.success) {
                availableItems = response.items;
                updateAllItemSelects();
            }
        },
        error: function() {
            alert('Error loading items');
        }
    });
}

function loadBuyers(buyerType) {
    $.ajax({
        url: '{{ route("admin.mess.billing.findBuyers") }}',
        type: 'GET',
        data: { buyer_type: buyerType },
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Select Buyer</option>';
                response.buyers.forEach(function(buyer) {
                    let displayName = buyer.first_name && buyer.last_name 
                        ? buyer.first_name + ' ' + buyer.last_name 
                        : (buyer.user_name || 'Unknown');
                    options += `<option value="${buyer.pk}">${displayName}</option>`;
                });
                $('#buyerSelect').html(options);
            }
        },
        error: function() {
            alert('Error loading buyers');
        }
    });
}

function getItemOptions() {
    let options = '';
    availableItems.forEach(function(item) {
        options += `<option value="${item.id}">${item.item_name}</option>`;
    });
    return options;
}

function updateAllItemSelects() {
    let options = '<option value="">Select Item</option>' + getItemOptions();
    $('.item-select').html(options);
}

function calculateRowAmount(row) {
    let quantity = parseFloat(row.find('.quantity-input').val()) || 0;
    let rate = parseFloat(row.find('.rate-input').val()) || 0;
    let amount = quantity * rate;
    row.find('.amount-display').val(amount.toFixed(2));
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    $('.amount-display').each(function() {
        let value = parseFloat($(this).val()) || 0;
        total += value;
    });
    $('#totalAmount').text(total.toFixed(2));
}
</script>
@endpush
@endsection
