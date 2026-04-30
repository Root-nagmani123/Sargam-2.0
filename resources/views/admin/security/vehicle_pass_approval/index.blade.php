@extends('admin.layouts.master')
@section('title', 'Requested Vehicle Pass')
@section('content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Requested Vehicle Pass'])
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Requested Vehicle Pass</h4>
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

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.security.vehicle_pass_approval.index') }}" id="vehicleFilterForm" class="row g-3 align-items-end">
                        <input type="hidden" id="vehicleActiveTabInput" name="tab" value="{{ $activeTab ?? 'new' }}">
                        <div class="col-md-2">
                            <label for="wheeler" class="form-label">Vehicle Type</label>
                            <select name="wheeler" id="wheeler" class="form-select">
                                @php $wh = $wheeler ?? request('wheeler', 'tw'); @endphp
                                <option value="tw" {{ $wh === 'tw' ? 'selected' : '' }}>Two Wheeler</option>
                                <option value="fw" {{ $wh === 'fw' ? 'selected' : '' }}>Four Wheeler</option>
                                <option value="all" {{ $wh === 'all' ? 'selected' : '' }}>Both</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control"
                                   placeholder="Search by Employee / Vehicle"
                                   value="{{ $search ?? request('search', '') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Applied From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control"
                                   value="{{ $dateFrom ?? request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Applied To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control"
                                   value="{{ $dateTo ?? request('date_to') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="per_page" class="form-label">Show Entries</label>
                            <select name="per_page" id="per_page" class="form-select">
                                @foreach([10, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ (int) request('per_page', 10) === $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 d-flex justify-content-end gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">
                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">search</i>
                                Search
                            </button>
                            <a href="{{ route('admin.security.vehicle_pass_approval.index') }}" class="btn btn-outline-secondary">
                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">restart_alt</i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <ul class="nav nav-pills mb-3 approval2-tabs flex-wrap" id="vehicleApprovalTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ ($activeTab ?? 'new') === 'new' ? 'active' : '' }}" id="veh-new-tab" data-bs-toggle="tab" data-bs-target="#veh-new-panel"
                            role="tab" data-tab-key="new" aria-selected="{{ ($activeTab ?? 'new') === 'new' ? 'true' : 'false' }}">
                        New Request
                        <span class="badge bg-white text-primary ms-1">{{ $newApplications->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ ($activeTab ?? 'new') === 'for_approval' ? 'active' : '' }}" id="veh-for-tab" data-bs-toggle="tab" data-bs-target="#veh-for-panel"
                            role="tab" data-tab-key="for_approval" aria-selected="{{ ($activeTab ?? 'new') === 'for_approval' ? 'true' : 'false' }}">
                        processed request
                        <span class="badge bg-secondary ms-1">{{ $processedApplications->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ ($activeTab ?? 'new') === 'issued' ? 'active' : '' }}" id="veh-issued-tab" data-bs-toggle="tab" data-bs-target="#veh-issued-panel"
                            role="tab" data-tab-key="issued" aria-selected="{{ ($activeTab ?? 'new') === 'issued' ? 'true' : 'false' }}">
                        <i class="material-icons material-symbols-rounded" style="font-size:16px;vertical-align:middle;">verified</i>
                        Verified Issued
                        <span class="badge bg-secondary ms-1">{{ $issuedApplications->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ ($activeTab ?? 'new') === 'rejected' ? 'active' : '' }}" id="veh-rejected-tab" data-bs-toggle="tab" data-bs-target="#veh-rejected-panel"
                            role="tab" data-tab-key="rejected" aria-selected="{{ ($activeTab ?? 'new') === 'rejected' ? 'true' : 'false' }}">
                        <i class="material-icons material-symbols-rounded" style="font-size:16px;vertical-align:middle;">cancel</i>
                        Rejected
                        <span class="badge bg-secondary ms-1">{{ $rejectedApplications->total() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'new' ? 'show active' : '' }}" id="veh-new-panel" role="tabpanel"
                     style="{{ ($activeTab ?? 'new') === 'new' ? 'display:block;' : 'display:none;' }}">
                    @include('admin.security.vehicle_pass_approval._vehicle_pass_table', ['applications' => $newApplications])
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Showing {{ $newApplications->firstItem() ?? 0 }} to {{ $newApplications->lastItem() ?? 0 }} of {{ $newApplications->total() }} entries</small>
                        {{ $newApplications->appends(array_merge(request()->query(), ['tab' => 'new']))->links() }}
                    </div>
                </div>
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'for_approval' ? 'show active' : '' }}" id="veh-for-panel" role="tabpanel"
                     style="{{ ($activeTab ?? 'new') === 'for_approval' ? 'display:block;' : 'display:none;' }}">
                    @include('admin.security.vehicle_pass_approval._vehicle_pass_table', ['applications' => $processedApplications])
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Showing {{ $processedApplications->firstItem() ?? 0 }} to {{ $processedApplications->lastItem() ?? 0 }} of {{ $processedApplications->total() }} entries</small>
                        {{ $processedApplications->appends(array_merge(request()->query(), ['tab' => 'for_approval']))->links() }}
                    </div>
                </div>
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'issued' ? 'show active' : '' }}" id="veh-issued-panel" role="tabpanel"
                     style="{{ ($activeTab ?? 'new') === 'issued' ? 'display:block;' : 'display:none;' }}">
                    @if($issuedApplications->total() === 0)
                        <div class="text-center text-muted py-5">
                            <i class="material-icons material-symbols-rounded" style="font-size:48px;opacity:.3;">verified</i>
                            <p class="mt-2 mb-0">No verified / issued records found.</p>
                        </div>
                    @else
                        @include('admin.security.vehicle_pass_approval._vehicle_pass_table', ['applications' => $issuedApplications])
                        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted">Showing {{ $issuedApplications->firstItem() ?? 0 }} to {{ $issuedApplications->lastItem() ?? 0 }} of {{ $issuedApplications->total() }} entries</small>
                            {{ $issuedApplications->appends(array_merge(request()->query(), ['tab' => 'issued']))->links() }}
                        </div>
                    @endif
                </div>
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'rejected' ? 'show active' : '' }}" id="veh-rejected-panel" role="tabpanel"
                     style="{{ ($activeTab ?? 'new') === 'rejected' ? 'display:block;' : 'display:none;' }}">
                    @if($rejectedApplications->total() === 0)
                        <div class="text-center text-muted py-5">
                            <i class="material-icons material-symbols-rounded" style="font-size:48px;opacity:.3;">cancel</i>
                            <p class="mt-2 mb-0">No rejected records found.</p>
                        </div>
                    @else
                        @include('admin.security.vehicle_pass_approval._vehicle_pass_table', ['applications' => $rejectedApplications])
                        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted">Showing {{ $rejectedApplications->firstItem() ?? 0 }} to {{ $rejectedApplications->lastItem() ?? 0 }} of {{ $rejectedApplications->total() }} entries</small>
                            {{ $rejectedApplications->appends(array_merge(request()->query(), ['tab' => 'rejected']))->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approve_remarks" class="form-label">Remarks (Optional)</label>
                        <textarea class="form-control" id="approve_remarks" name="veh_approval_remarks" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="forward_status" class="form-label">Status</label>
                        <select class="form-select" id="forward_status" name="forward_status">
                            <option value="">Select Status (Optional)</option>
                            <option value="1">Forwarded</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_remarks" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_remarks" name="veh_approval_remarks" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
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
(function () {
    var approveUrlTemplate = "{{ route('admin.security.vehicle_pass_approval.approve', ['id' => '__ID__']) }}";
    var rejectUrlTemplate = "{{ route('admin.security.vehicle_pass_approval.reject', ['id' => '__ID__']) }}";

    $(document).on('click', '.btn-veh-approve', function () {
        var encryptedId = this.getAttribute('data-encrypted-id');
        if (!encryptedId) return;
        var safeId = encodeURIComponent(encryptedId);
        var url = approveUrlTemplate.replace('__ID__', safeId);
        $('#approveForm').attr('action', url);
        $('#approveModal').modal('show');
    });

    $(document).on('click', '.btn-veh-reject', function () {
        var encryptedId = this.getAttribute('data-encrypted-id');
        if (!encryptedId) return;
        var safeId = encodeURIComponent(encryptedId);
        var url = rejectUrlTemplate.replace('__ID__', safeId);
        $('#rejectForm').attr('action', url);
        $('#rejectModal').modal('show');
    });

    $('#per_page, #wheeler').on('change', function () {
        document.getElementById('vehicleFilterForm').submit();
    });

    document.addEventListener('DOMContentLoaded', function () {
        try {
            var url = new URL(window.location.href);
            var tab = url.searchParams.get('tab');
            var validTabs = ['new', 'for_approval', 'issued', 'rejected'];
            if (tab === 'archive') { tab = 'issued'; }
            var tabKey = validTabs.indexOf(tab) !== -1 ? tab : 'new';
            var tabInput = document.getElementById('vehicleActiveTabInput');
            if (tabInput) tabInput.value = tabKey;

            var tabBtns = {
                new: document.getElementById('veh-new-tab'),
                for_approval: document.getElementById('veh-for-tab'),
                issued: document.getElementById('veh-issued-tab'),
                rejected: document.getElementById('veh-rejected-tab'),
            };
            var panels = {
                new: document.getElementById('veh-new-panel'),
                for_approval: document.getElementById('veh-for-panel'),
                issued: document.getElementById('veh-issued-panel'),
                rejected: document.getElementById('veh-rejected-panel'),
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

    document.querySelectorAll('#vehicleApprovalTabs .nav-link').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            var tabKey = this.dataset.tabKey || 'new';
            var tabInput = document.getElementById('vehicleActiveTabInput');
            if (tabInput) tabInput.value = tabKey;
            var panels = {
                new: document.getElementById('veh-new-panel'),
                for_approval: document.getElementById('veh-for-panel'),
                issued: document.getElementById('veh-issued-panel'),
                rejected: document.getElementById('veh-rejected-panel'),
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
})();
</script>
@endpush
