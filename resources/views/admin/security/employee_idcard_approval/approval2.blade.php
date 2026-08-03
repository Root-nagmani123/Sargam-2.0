@extends('admin.layouts.master')
@section('title', 'Requested ID Card')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Requested ID Card'])
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h4 class="mb-0">Requested ID Card</h4>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                  {{--  <a href="{{ route('admin.security.employee_idcard_approval.approval1') }}" class="btn btn-outline-primary btn-sm">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">arrow_back</i>
                        Approval I 
                    </a>--}}
                    <a href="{{ route('admin.security.employee_idcard_approval.all') }}" class="btn btn-secondary btn-sm">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">list</i>
                        All Requests
                    </a>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0 fw-semibold">Filters & Search</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.security.employee_idcard_approval.approval2') }}" id="filterForm" class="mb-0">
                        <div class="row g-3 align-items-end">
                            <div class="col-xl-4 col-lg-4 col-md-6">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control" placeholder="Search by name, designation, ID no/type, request type, contact, date..." value="{{ request('search', '') }}">
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-6">
                                <label class="form-label">Request Date From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from', \App\Http\Controllers\Admin\Security\EmployeeIDCardApprovalController::DEFAULT_REQUEST_DATE_FROM) }}">
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-6">
                                <label class="form-label">Request Date To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-xl-2 col-lg-2 col-md-3">
                                <label for="per_page" class="form-label">Show Entries</label>
                                <select name="per_page" id="per_page" class="form-select">
                                    @foreach([10, 25, 50, 100] as $n)
                                        <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-12 col-md-9 d-flex gap-2 justify-content-lg-start justify-content-md-end">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">search</i> Search
                                </button>
                                <a href="{{ route('admin.security.employee_idcard_approval.approval2') }}" class="btn btn-outline-secondary">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">restart_alt</i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Export Buttons -->
            <div class="d-flex justify-content-end gap-2 mb-3">
                <div class="dropdown">
                    <button class="btn btn-outline-success dropdown-toggle d-flex align-items-center gap-2 px-3 py-2" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">download</i>
                        Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="exportDropdown">
                        <li><h6 class="dropdown-header text-muted small text-uppercase">Export with Current Filters</h6></li>
                        <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.employee_idcard_approval.export', array_merge(['stage' => '2', 'format' => 'xlsx'], request()->query())) }}"><i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i> Excel</a></li>
                        <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.employee_idcard_approval.export', array_merge(['stage' => '2', 'format' => 'pdf'], request()->query())) }}"><i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;">picture_as_pdf</i> PDF</a></li>
                    </ul>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <input type="hidden" id="activeTabInput" name="tab" value="{{ $activeTab ?? 'new' }}" form="filterForm">

            <ul class="nav nav-pills mb-3 approval2-tabs flex-wrap" id="approval2Tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ ($activeTab ?? 'new') === 'new' ? 'active' : '' }}" id="new-request-tab" data-bs-toggle="tab" data-bs-target="#new-request-panel" type="button" role="tab" aria-controls="new-request-panel" aria-selected="{{ ($activeTab ?? 'new') === 'new' ? 'true' : 'false' }}" data-tab-key="new">
                        New Request
                        <span class="badge bg-white text-primary ms-1">{{ $newRequests->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ ($activeTab ?? 'new') === 'for_approval' ? 'active' : '' }}" id="for-approval-tab" data-bs-toggle="tab" data-bs-target="#for-approval-panel" type="button" role="tab" aria-controls="for-approval-panel" aria-selected="{{ ($activeTab ?? 'new') === 'for_approval' ? 'true' : 'false' }}" data-tab-key="for_approval">
                        processed request
                        <span class="badge bg-secondary ms-1">{{ $forApprovalRequests->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ ($activeTab ?? 'new') === 'issued' ? 'active' : '' }}" id="issued-tab" data-bs-toggle="tab" data-bs-target="#issued-panel" type="button" role="tab" aria-controls="issued-panel" aria-selected="{{ ($activeTab ?? 'new') === 'issued' ? 'true' : 'false' }}" data-tab-key="issued">
                        <i class="material-icons material-symbols-rounded" style="font-size:16px;vertical-align:middle;">verified</i>
                        Issued
                        <span class="badge bg-secondary ms-1">{{ $issuedRequests->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ ($activeTab ?? 'new') === 'rejected' ? 'active' : '' }}" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected-panel" type="button" role="tab" aria-controls="rejected-panel" aria-selected="{{ ($activeTab ?? 'new') === 'rejected' ? 'true' : 'false' }}" data-tab-key="rejected">
                        <i class="material-icons material-symbols-rounded" style="font-size:16px;vertical-align:middle;">cancel</i>
                        Rejected
                        <span class="badge bg-secondary ms-1">{{ $rejectedRequests->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'new' ? 'show active' : '' }}" id="new-request-panel" role="tabpanel" aria-labelledby="new-request-tab" style="{{ ($activeTab ?? 'new') === 'new' ? 'display:block;' : 'display:none;' }}">
                    @include('admin.security.employee_idcard_approval._approval_table_shell', ['tableId' => 'idNewTable'])
                </div>
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'for_approval' ? 'show active' : '' }}" id="for-approval-panel" role="tabpanel" aria-labelledby="for-approval-tab" style="{{ ($activeTab ?? 'new') === 'for_approval' ? 'display:block;' : 'display:none;' }}">
                    @include('admin.security.employee_idcard_approval._approval_table_shell', ['tableId' => 'idForApprovalTable'])
                </div>

                {{-- Issued: all approved rows (Permanent / Duplicate / Contractual). Card print confirms physical issue. --}}
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'issued' ? 'show active' : '' }}" id="issued-panel" role="tabpanel" aria-labelledby="issued-tab" style="{{ ($activeTab ?? 'new') === 'issued' ? 'display:block;' : 'display:none;' }}">
                    @include('admin.security.employee_idcard_approval._approval_table_shell', ['tableId' => 'idIssuedTable'])
                </div>

                {{-- Rejected only --}}
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'rejected' ? 'show active' : '' }}" id="rejected-panel" role="tabpanel" aria-labelledby="rejected-tab" style="{{ ($activeTab ?? 'new') === 'rejected' ? 'display:block;' : 'display:none;' }}">
                    @include('admin.security.employee_idcard_approval._approval_table_shell', ['tableId' => 'idRejectedTable'])
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rejection Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small" id="rejectModalEmployeeName"></p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Enter Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.approval2-tabs .nav-link {
    color: #495057;
    border: 1px solid transparent;
    border-radius: 8px;
    padding: 0.45rem 0.9rem;
    font-weight: 500;
}
.approval2-tabs .nav-link:hover {
    color: #004a93;
    background-color: #f1f5f9;
}
.approval2-tabs .nav-link.active {
    background-color: #004a93;
    color: #fff;
    border-color: #004a93;
}
.approval2-tabs .nav-link.active .badge {
    background-color: #fff !important;
    color: #004a93 !important;
}
</style>
@endpush

@push('scripts')
<script>
// Default tab behavior: if no `tab` query is provided, always open "New Request".
document.addEventListener('DOMContentLoaded', function () {
    try {
        var url = new URL(window.location.href);
        var tab = url.searchParams.get('tab');
        var validTabs = ['new', 'for_approval', 'issued', 'rejected'];
        if (tab === 'archive') { tab = 'issued'; }
        var tabKey = validTabs.indexOf(tab) !== -1 ? tab : 'new';
        var tabInput = document.getElementById('activeTabInput');
        if (tabInput) tabInput.value = tabKey;

        var tabBtns = {
            'new': document.getElementById('new-request-tab'),
            'for_approval': document.getElementById('for-approval-tab'),
            'issued': document.getElementById('issued-tab'),
            'rejected': document.getElementById('rejected-tab'),
        };
        var panels = {
            'new': document.getElementById('new-request-panel'),
            'for_approval': document.getElementById('for-approval-panel'),
            'issued': document.getElementById('issued-panel'),
            'rejected': document.getElementById('rejected-panel'),
        };

        validTabs.forEach(function (key) {
            var isActive = key === tabKey;
            if (tabBtns[key]) {
                tabBtns[key].classList.toggle('active', isActive);
                tabBtns[key].setAttribute('aria-selected', isActive ? 'true' : 'false');
            }
            if (panels[key]) {
                panels[key].classList.toggle('show', isActive);
                panels[key].classList.toggle('active', isActive);
                panels[key].style.display = isActive ? 'block' : 'none';
            }
        });
    } catch (e) {}
});

// Reject button opens the modal. Delegated on document since rows are rendered by the DataTable ajax call.
document.addEventListener('click', function (event) {
    var btn = event.target.closest('.reject-btn');
    if (!btn) {
        return;
    }
    document.getElementById('rejectModalEmployeeName').textContent = 'Rejecting: ' + (btn.dataset.name || '');
    document.getElementById('rejectForm').action = btn.dataset.url || '#';
    document.getElementById('rejection_reason').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
});

// "Card print" checkbox (Issued tab) — confirm before marking a request as physically issued.
document.addEventListener('change', function (event) {
    if (!event.target.classList.contains('archive-check-input')) {
        return;
    }
    var checkbox = event.target;
    if (!checkbox.checked) {
        return;
    }
    var form = checkbox.closest('form.archive-check-form');
    if (!form) {
        checkbox.checked = false;
        return;
    }
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Card printed?',
            text: 'Mark this record as physically issued (date will be recorded).',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, mark issued',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            } else {
                checkbox.checked = false;
            }
        });
        return;
    }
    if (!window.confirm('Mark this approved request as card printed / physically issued?')) {
        checkbox.checked = false;
        return;
    }
    form.submit();
});

// Auto-submit when per_page changes
document.getElementById('per_page').addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});

// Keep selected tab in query so pagination and filters stay consistent.
document.querySelectorAll('#approval2Tabs .nav-link').forEach(function(btn) {
    btn.addEventListener('shown.bs.tab', function() {
        var tabKey = this.dataset.tabKey || 'new';
        var tabInput = document.getElementById('activeTabInput');
        if (tabInput) tabInput.value = tabKey;
        var panels = {
            'new': document.getElementById('new-request-panel'),
            'for_approval': document.getElementById('for-approval-panel'),
            'issued': document.getElementById('issued-panel'),
            'rejected': document.getElementById('rejected-panel'),
        };
        ['new', 'for_approval', 'issued', 'rejected'].forEach(function (key) {
            if (panels[key]) {
                var isActive = key === tabKey;
                panels[key].style.display = isActive ? 'block' : 'none';
                panels[key].classList.toggle('show', isActive);
                panels[key].classList.toggle('active', isActive);
            }
        });
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
        ensureApproval2TabDataTableInitialized(tabKey);
    });
});

// --- Server-side DataTables: one per tab, lazy-initialized on first display ---
(function () {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return;
    var $ = window.jQuery;

    var urlParams = new URLSearchParams(window.location.search);
    var searchFilter = urlParams.get('search') || '';
    var dateFromFilter = urlParams.get('date_from') || '{{ \App\Http\Controllers\Admin\Security\EmployeeIDCardApprovalController::DEFAULT_REQUEST_DATE_FROM }}';
    var dateToFilter = urlParams.get('date_to') || '';

    var approval2DatatableUrl = '{{ route('admin.security.employee_idcard_approval.approval2_datatable') }}';
    var approval2TabTableIds = {
        new: 'idNewTable',
        for_approval: 'idForApprovalTable',
        issued: 'idIssuedTable',
        rejected: 'idRejectedTable',
    };
    var approval2TabDataTables = {};

    var approval2TableColumns = [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'name', name: 'name', orderable: false, searchable: false },
        { data: 'designation', name: 'designation', orderable: false, searchable: false },
        { data: 'id_card_number', name: 'id_card_number', orderable: false, searchable: false },
        { data: 'card_type', name: 'card_type', orderable: false, searchable: false },
        { data: 'request_type_badge', name: 'request_type_badge', orderable: false, searchable: false },
        { data: 'photo', name: 'photo', orderable: false, searchable: false },
        { data: 'contact_no', name: 'contact_no', orderable: false, searchable: false },
        { data: 'actions', name: 'actions', orderable: false, searchable: false },
        { data: 'request_date_fmt', name: 'request_date_fmt', orderable: false, searchable: false },
        { data: 'requested_section', name: 'requested_section', orderable: false, searchable: false }
    ];

    window.ensureApproval2TabDataTableInitialized = function (tabKey) {
        if (approval2TabDataTables[tabKey]) {
            approval2TabDataTables[tabKey].columns.adjust();
            return;
        }
        var tableId = approval2TabTableIds[tabKey];
        var $table = $('#' + tableId);
        if (!$table.length) return;

        approval2TabDataTables[tabKey] = $table.DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ordering: false,
            pageLength: 10,
            ajax: {
                url: approval2DatatableUrl,
                type: 'GET',
                data: function (d) {
                    d.tab_key = tabKey;
                    d.search = searchFilter;
                    d.date_from = dateFromFilter;
                    d.date_to = dateToFilter;
                }
            },
            columns: approval2TableColumns,
            language: {
                zeroRecords: 'No requests found for Approval II.',
                processing: 'Loading...'
            },
            dom: '<"row align-items-center mb-2"<"col-12 col-md-4"l>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>'
        });
    };

    ensureApproval2TabDataTableInitialized('{{ $activeTab ?? 'new' }}');
})();
</script>
@endpush
