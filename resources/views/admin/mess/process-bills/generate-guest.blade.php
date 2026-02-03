@extends('admin.layouts.master')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Generate Invoice Guest/Others</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="#">Mess Management</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Generate Invoice Guest/Others</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Form -->
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.process-bills.guest-list') }}" id="guestFilterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Date From <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="{{ request('start_date', date('Y-m-01')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="end_date" class="form-label">Date To <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="{{ request('end_date', date('Y-m-t')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="invoice_date" name="invoice_date" 
                                   value="{{ request('invoice_date', date('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="payment_mode" class="form-label">Mode Of Payment <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_mode" name="payment_mode" required>
                                <option value="cash" {{ request('payment_mode') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="online" {{ request('payment_mode') == 'online' ? 'selected' : '' }}>Online</option>
                                <option value="card" {{ request('payment_mode') == 'card' ? 'selected' : '' }}>Card</option>
                                <option value="myself" {{ request('payment_mode', 'myself') == 'myself' ? 'selected' : '' }}>MySelf</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-search"></i> Show
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Guest Bills Table -->
    <div class="card mt-3" id="guestBillsTable" style="display: none;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title">Guest/Others Bills</h5>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>S. NO.</th>
                            <th>BUYER NAME</th>
                            <th>BILL NO.</th>
                            <th>GUEST NAME</th>
                            <th>SECTION</th>
                            <th>PROGRAMME NAME</th>
                            <th>TOTAL</th>
                        </tr>
                    </thead>
                    <tbody id="guestBillsBody">
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="7" class="text-end">Total Amount</td>
                            <td id="totalAmount">0.0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-3">
                <p class="text-muted small">Showing <span id="entriesCount">0</span> entries</p>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-success" onclick="generateGuestInvoices()">
                    <i class="ti ti-file-invoice"></i> Generate Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for invoice generation -->
<form id="generateInvoiceForm" method="POST" action="{{ route('admin.mess.process-bills.generate-guest') }}" style="display: none;">
    @csrf
    <input type="hidden" name="start_date" id="form_start_date">
    <input type="hidden" name="end_date" id="form_end_date">
    <input type="hidden" name="invoice_date" id="form_invoice_date">
    <input type="hidden" name="payment_mode" id="form_payment_mode">
    <div id="selectedGuestBillsContainer"></div>
</form>

@endsection

@push('scripts')
<script>
    // Load guest bills when form is submitted
    document.getElementById('guestFilterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadGuestBills();
    });

    function loadGuestBills() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const paymentMode = document.getElementById('payment_mode').value;

        // Show loading state
        document.getElementById('guestBillsTable').style.display = 'block';
        document.getElementById('guestBillsBody').innerHTML = '<tr><td colspan="8" class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';

        // Fetch guest bills via AJAX
        fetch(`{{ route('admin.mess.material-management.records') }}?start_date=${startDate}&end_date=${endDate}&client_type=guest`)
            .then(response => response.json())
            .then(data => {
                displayGuestBills(data);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('guestBillsBody').innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error loading data</td></tr>';
            });
    }

    function displayGuestBills(bills) {
        const tbody = document.getElementById('guestBillsBody');
        tbody.innerHTML = '';

        if (bills.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No guest bills found for the selected criteria.</td></tr>';
            document.getElementById('totalAmount').textContent = '0.00';
            document.getElementById('entriesCount').textContent = '0';
            return;
        }

        let totalAmount = 0;

        bills.forEach((bill, index) => {
            totalAmount += parseFloat(bill.total_amount || 0);

            const row = `
                <tr>
                    <td>
                        <input type="checkbox" name="selected_guest_bills[]" value="${bill.bill_no}" class="form-check-input guest-bill-checkbox">
                    </td>
                    <td>${index + 1}</td>
                    <td>${bill.buyer_name || 'N/A'}</td>
                    <td>${bill.bill_no}</td>
                    <td>${bill.guest_name || 'N/A'}</td>
                    <td>${bill.section || 'N/A'}</td>
                    <td>${bill.programme_name || 'N/A'}</td>
                    <td>${parseFloat(bill.total_amount || 0).toFixed(2)}</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });

        document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
        document.getElementById('entriesCount').textContent = bills.length;
    }

    // Select All functionality
    document.getElementById('selectAll')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.guest-bill-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Generate guest invoices
    function generateGuestInvoices() {
        const checkedBoxes = document.querySelectorAll('.guest-bill-checkbox:checked');
        
        if (checkedBoxes.length === 0) {
            alert('Please select at least one bill to generate invoice.');
            return;
        }

        if (!confirm('Are you sure you want to generate invoices for the selected guest bills?')) {
            return;
        }

        // Set form values
        document.getElementById('form_start_date').value = document.getElementById('start_date').value;
        document.getElementById('form_end_date').value = document.getElementById('end_date').value;
        document.getElementById('form_invoice_date').value = document.getElementById('invoice_date').value;
        document.getElementById('form_payment_mode').value = document.getElementById('payment_mode').value;

        // Clear previous selections
        document.getElementById('selectedGuestBillsContainer').innerHTML = '';

        // Add selected bills to the form
        checkedBoxes.forEach((checkbox) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_bills[]';
            input.value = checkbox.value;
            document.getElementById('selectedGuestBillsContainer').appendChild(input);
        });

        // Submit the form
        document.getElementById('generateInvoiceForm').submit();
    }

    // Auto-load if parameters are present
    if (window.location.search.includes('start_date')) {
        loadGuestBills();
    }
</script>
@endpush
