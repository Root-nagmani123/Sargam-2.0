@extends('admin.layouts.master')
@section('title', 'Requested ID Card')
@section('content')
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
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from', '2026-03-01') }}">
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
                        <span class="badge bg-white text-primary ms-1">{{ $newRequests->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ ($activeTab ?? 'new') === 'for_approval' ? 'active' : '' }}" id="for-approval-tab" data-bs-toggle="tab" data-bs-target="#for-approval-panel" type="button" role="tab" aria-controls="for-approval-panel" aria-selected="{{ ($activeTab ?? 'new') === 'for_approval' ? 'true' : 'false' }}" data-tab-key="for_approval">
                        processed request
                        <span class="badge bg-secondary ms-1">{{ $forApprovalRequests->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ ($activeTab ?? 'new') === 'issued' ? 'active' : '' }}" id="issued-tab" data-bs-toggle="tab" data-bs-target="#issued-panel" type="button" role="tab" aria-controls="issued-panel" aria-selected="{{ ($activeTab ?? 'new') === 'issued' ? 'true' : 'false' }}" data-tab-key="issued">
                        <i class="material-icons material-symbols-rounded" style="font-size:16px;vertical-align:middle;">verified</i>
                        Issued
                        <span class="badge bg-secondary ms-1">{{ $issuedRequests->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ ($activeTab ?? 'new') === 'rejected' ? 'active' : '' }}" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected-panel" type="button" role="tab" aria-controls="rejected-panel" aria-selected="{{ ($activeTab ?? 'new') === 'rejected' ? 'true' : 'false' }}" data-tab-key="rejected">
                        <i class="material-icons material-symbols-rounded" style="font-size:16px;vertical-align:middle;">cancel</i>
                        Rejected
                        <span class="badge bg-secondary ms-1">{{ $rejectedRequests->total() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'new' ? 'show active' : '' }}" id="new-request-panel" role="tabpanel" aria-labelledby="new-request-tab" style="{{ ($activeTab ?? 'new') === 'new' ? 'display:block;' : 'display:none;' }}">
                    @include('admin.security.employee_idcard_approval._approval_table', ['requests' => $newRequests, 'approvalStage' => 2])
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Showing {{ $newRequests->firstItem() ?? 0 }} to {{ $newRequests->lastItem() ?? 0 }} of {{ $newRequests->total() }} entries</small>
                        {{ $newRequests->appends(array_merge(request()->query(), ['tab' => 'new']))->links() }}
                    </div>
                </div>
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'for_approval' ? 'show active' : '' }}" id="for-approval-panel" role="tabpanel" aria-labelledby="for-approval-tab" style="{{ ($activeTab ?? 'new') === 'for_approval' ? 'display:block;' : 'display:none;' }}">
                    @include('admin.security.employee_idcard_approval._approval_table', ['requests' => $forApprovalRequests, 'approvalStage' => 2])
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Showing {{ $forApprovalRequests->firstItem() ?? 0 }} to {{ $forApprovalRequests->lastItem() ?? 0 }} of {{ $forApprovalRequests->total() }} entries</small>
                        {{ $forApprovalRequests->appends(array_merge(request()->query(), ['tab' => 'for_approval']))->links() }}
                    </div>
                </div>

                {{-- Issued: all approved rows (Permanent / Duplicate / Contractual). Card print confirms physical issue. --}}
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'issued' ? 'show active' : '' }}" id="issued-panel" role="tabpanel" aria-labelledby="issued-tab" style="{{ ($activeTab ?? 'new') === 'issued' ? 'display:block;' : 'display:none;' }}">
                    @if($issuedRequests->total() === 0)
                        <div class="text-center text-muted py-5">
                            <i class="material-icons material-symbols-rounded" style="font-size:48px;opacity:.3;">verified</i>
                            <p class="mt-2 mb-0">No issued records found.</p>
                        </div>
                    @else
                        @include('admin.security.employee_idcard_approval._approval_table', ['requests' => $issuedRequests, 'approvalStage' => 2])
                        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted">Showing {{ $issuedRequests->firstItem() ?? 0 }} to {{ $issuedRequests->lastItem() ?? 0 }} of {{ $issuedRequests->total() }} entries</small>
                            {{ $issuedRequests->appends(array_merge(request()->query(), ['tab' => 'issued']))->links() }}
                        </div>
                    @endif
                </div>

                {{-- Rejected only --}}
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'rejected' ? 'show active' : '' }}" id="rejected-panel" role="tabpanel" aria-labelledby="rejected-tab" style="{{ ($activeTab ?? 'new') === 'rejected' ? 'display:block;' : 'display:none;' }}">
                    @if($rejectedRequests->total() === 0)
                        <div class="text-center text-muted py-5">
                            <i class="material-icons material-symbols-rounded" style="font-size:48px;opacity:.3;">cancel</i>
                            <p class="mt-2 mb-0">No rejected records found.</p>
                        </div>
                    @else
                        @include('admin.security.employee_idcard_approval._approval_table', ['requests' => $rejectedRequests, 'approvalStage' => 2])
                        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted">Showing {{ $rejectedRequests->firstItem() ?? 0 }} to {{ $rejectedRequests->lastItem() ?? 0 }} of {{ $rejectedRequests->total() }} entries</small>
                            {{ $rejectedRequests->appends(array_merge(request()->query(), ['tab' => 'rejected']))->links() }}
                        </div>
                    @endif
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

document.querySelectorAll('.reject-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('rejectModalEmployeeName').textContent = 'Rejecting: ' + (this.dataset.name || '');
        document.getElementById('rejectForm').action = this.dataset.url || '#';
        document.getElementById('rejection_reason').value = '';
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    });
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
    });
});
</script>
@endpush
