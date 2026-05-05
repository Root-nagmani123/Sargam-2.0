@extends('admin.layouts.master')
@section('title', 'Approval III - Employee ID Card Requests')
@section('content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Approval III - Employee ID Card'])
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h4 class="mb-0">Approval III (Final Approval)</h4>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('admin.security.employee_idcard_approval.approval2') }}" class="btn btn-outline-primary btn-sm">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">arrow_back</i>
                        Approval II
                    </a>
                    <a href="{{ route('admin.security.employee_idcard_approval.all') }}" class="btn btn-secondary btn-sm">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">list</i>
                        All Requests
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0 fw-semibold">Filters & Search</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.security.employee_idcard_approval.approval3') }}" id="filterForm3" class="mb-0">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" name="search" id="search" class="form-control"
                                       placeholder="Search: name, designation, ID card no/type, request type (Fresh/Duplicate), contact, status, request date..."
                                       value="{{ request('search', '') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Request Date From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Request Date To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="per_page" class="form-label">Show Entries</label>
                                <select name="per_page" id="per_page" class="form-select">
                                    @foreach([10, 25, 50, 100] as $n)
                                        <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">search</i> Search
                                </button>
                                <a href="{{ route('admin.security.employee_idcard_approval.approval3') }}" class="btn btn-outline-secondary">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">restart_alt</i>
                                </a>
                            </div>
                        </div>
                    </form>
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

            <input type="hidden" id="activeTabInput3" name="tab" value="{{ $activeTab ?? 'new' }}" form="filterForm3">

            <ul class="nav nav-pills mb-3 approval2-tabs flex-wrap" id="approval3Tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ ($activeTab ?? 'new') === 'new' ? 'active' : '' }}" id="new-request-tab3" data-bs-toggle="tab" data-bs-target="#new-request-panel3" type="button" role="tab" aria-controls="new-request-panel3" aria-selected="{{ ($activeTab ?? 'new') === 'new' ? 'true' : 'false' }}" data-tab-key="new">
                        New Request
                        <span class="badge bg-white text-primary ms-1">{{ $newRequests->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ ($activeTab ?? 'new') === 'for_approval' ? 'active' : '' }}" id="for-approval-tab3" data-bs-toggle="tab" data-bs-target="#for-approval-panel3" type="button" role="tab" aria-controls="for-approval-panel3" aria-selected="{{ ($activeTab ?? 'new') === 'for_approval' ? 'true' : 'false' }}" data-tab-key="for_approval">
                        processed request
                        <span class="badge bg-secondary ms-1">{{ $forApprovalRequests->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ ($activeTab ?? 'new') === 'issued' ? 'active' : '' }}" id="issued-tab3" data-bs-toggle="tab" data-bs-target="#issued-panel3" type="button" role="tab" aria-controls="issued-panel3" aria-selected="{{ ($activeTab ?? 'new') === 'issued' ? 'true' : 'false' }}" data-tab-key="issued">
                        <i class="material-icons material-symbols-rounded" style="font-size:16px;vertical-align:middle;">verified</i>
                        Issued
                        <span class="badge bg-secondary ms-1">{{ $issuedRequests->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ ($activeTab ?? 'new') === 'rejected' ? 'active' : '' }}" id="rejected-tab3" data-bs-toggle="tab" data-bs-target="#rejected-panel3" type="button" role="tab" aria-controls="rejected-panel3" aria-selected="{{ ($activeTab ?? 'new') === 'rejected' ? 'true' : 'false' }}" data-tab-key="rejected">
                        <i class="material-icons material-symbols-rounded" style="font-size:16px;vertical-align:middle;">cancel</i>
                        Rejected
                        <span class="badge bg-secondary ms-1">{{ $rejectedRequests->total() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'new' ? 'show active' : '' }}" id="new-request-panel3" role="tabpanel" aria-labelledby="new-request-tab3" style="{{ ($activeTab ?? 'new') === 'new' ? 'display:block;' : 'display:none;' }}">
                    @include('admin.security.employee_idcard_approval._approval_table', ['requests' => $newRequests, 'approvalStage' => 3])
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Showing {{ $newRequests->firstItem() ?? 0 }} to {{ $newRequests->lastItem() ?? 0 }} of {{ $newRequests->total() }} entries</small>
                        {{ $newRequests->appends(array_merge(request()->query(), ['tab' => 'new']))->links() }}
                    </div>
                </div>
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'for_approval' ? 'show active' : '' }}" id="for-approval-panel3" role="tabpanel" aria-labelledby="for-approval-tab3" style="{{ ($activeTab ?? 'new') === 'for_approval' ? 'display:block;' : 'display:none;' }}">
                    @include('admin.security.employee_idcard_approval._approval_table', ['requests' => $forApprovalRequests, 'approvalStage' => 3])
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Showing {{ $forApprovalRequests->firstItem() ?? 0 }} to {{ $forApprovalRequests->lastItem() ?? 0 }} of {{ $forApprovalRequests->total() }} entries</small>
                        {{ $forApprovalRequests->appends(array_merge(request()->query(), ['tab' => 'for_approval']))->links() }}
                    </div>
                </div>
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'issued' ? 'show active' : '' }}" id="issued-panel3" role="tabpanel" aria-labelledby="issued-tab3" style="{{ ($activeTab ?? 'new') === 'issued' ? 'display:block;' : 'display:none;' }}">
                    @if($issuedRequests->total() === 0)
                        <div class="text-center text-muted py-5">
                            <i class="material-icons material-symbols-rounded" style="font-size:48px;opacity:.3;">verified</i>
                            <p class="mt-2 mb-0">No issued records found.</p>
                        </div>
                    @else
                        @include('admin.security.employee_idcard_approval._approval_table', ['requests' => $issuedRequests, 'approvalStage' => 3])
                        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted">Showing {{ $issuedRequests->firstItem() ?? 0 }} to {{ $issuedRequests->lastItem() ?? 0 }} of {{ $issuedRequests->total() }} entries</small>
                            {{ $issuedRequests->appends(array_merge(request()->query(), ['tab' => 'issued']))->links() }}
                        </div>
                    @endif
                </div>
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'rejected' ? 'show active' : '' }}" id="rejected-panel3" role="tabpanel" aria-labelledby="rejected-tab3" style="{{ ($activeTab ?? 'new') === 'rejected' ? 'display:block;' : 'display:none;' }}">
                    @if($rejectedRequests->total() === 0)
                        <div class="text-center text-muted py-5">
                            <i class="material-icons material-symbols-rounded" style="font-size:48px;opacity:.3;">cancel</i>
                            <p class="mt-2 mb-0">No rejected records found.</p>
                        </div>
                    @else
                        @include('admin.security.employee_idcard_approval._approval_table', ['requests' => $rejectedRequests, 'approvalStage' => 3])
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
document.querySelectorAll('.reject-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('rejectModalEmployeeName').textContent = 'Rejecting: ' + (this.dataset.name || '');
        document.getElementById('rejectForm').action = this.dataset.url || '#';
        document.getElementById('rejection_reason').value = '';
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    });
});

document.getElementById('per_page').addEventListener('change', function() {
    document.getElementById('filterForm3').submit();
});

document.addEventListener('DOMContentLoaded', function () {
    try {
        var url = new URL(window.location.href);
        var tab = url.searchParams.get('tab');
        var validTabs = ['new', 'for_approval', 'issued', 'rejected'];
        if (tab === 'archive') { tab = 'issued'; }
        var tabKey = validTabs.indexOf(tab) !== -1 ? tab : 'new';
        var tabInput = document.getElementById('activeTabInput3');
        if (tabInput) tabInput.value = tabKey;

        var tabBtns = {
            'new': document.getElementById('new-request-tab3'),
            'for_approval': document.getElementById('for-approval-tab3'),
            'issued': document.getElementById('issued-tab3'),
            'rejected': document.getElementById('rejected-tab3'),
        };
        var panels = {
            'new': document.getElementById('new-request-panel3'),
            'for_approval': document.getElementById('for-approval-panel3'),
            'issued': document.getElementById('issued-panel3'),
            'rejected': document.getElementById('rejected-panel3'),
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

document.querySelectorAll('#approval3Tabs .nav-link').forEach(function(btn) {
    btn.addEventListener('shown.bs.tab', function() {
        var tabKey = this.dataset.tabKey || 'new';
        var tabInput = document.getElementById('activeTabInput3');
        if (tabInput) tabInput.value = tabKey;
        var panels = {
            'new': document.getElementById('new-request-panel3'),
            'for_approval': document.getElementById('for-approval-panel3'),
            'issued': document.getElementById('issued-panel3'),
            'rejected': document.getElementById('rejected-panel3'),
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
@endsection

