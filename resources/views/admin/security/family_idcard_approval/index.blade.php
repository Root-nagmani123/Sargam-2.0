@extends('admin.layouts.master')
@section('title', 'Requested Family ID')
@section('content')
@php
    $familyApprovalReturn = in_array(request('return'), ['approval2', 'approval3'], true) ? request('return') : null;
    $familyMembersQs = ['from' => 'family_approval'];
    if ($familyApprovalReturn) {
        $familyMembersQs['return'] = $familyApprovalReturn;
    }
    $familyMembersQueryString = '?' . http_build_query($familyMembersQs);
@endphp
<div class="container-fluid">
    <x-breadcrum title="Requested Family ID"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Requested Family ID</h4>
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
                    <form method="GET" action="{{ route('admin.security.family_idcard_approval.index') }}" id="filterForm" class="row g-3 align-items-end">
                        <input type="hidden" id="activeTabInput" name="tab" value="{{ $activeTab ?? 'new' }}">
                        @if($familyApprovalReturn)
                            <input type="hidden" name="return" value="{{ $familyApprovalReturn }}">
                        @endif
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control"
                                   placeholder="Search by Submitted By / Employee ID"
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
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">search</i>
                                Search
                            </button>
                            <a href="{{ route('admin.security.family_idcard_approval.index', array_filter(['return' => $familyApprovalReturn])) }}" class="btn btn-outline-secondary">
                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">restart_alt</i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <ul class="nav nav-pills mb-2 approval2-tabs flex-wrap" id="familyApprovalTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ ($activeTab ?? 'new') === 'new' ? 'active' : '' }}" id="fam-new-tab" data-bs-toggle="tab" data-bs-target="#fam-new-panel"
                            role="tab" data-tab-key="new" aria-selected="{{ ($activeTab ?? 'new') === 'new' ? 'true' : 'false' }}"
                            title="Applications waiting for your approve or reject action at this stage.">
                        <i class="material-icons material-symbols-rounded d-inline align-middle" style="font-size:18px;">assignment_turned_in</i>
                        <span class="align-middle">Pending — your action</span>
                        <span class="badge rounded-pill bg-white text-primary ms-1">{{ $newFamilyGroups->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ ($activeTab ?? 'new') === 'for_approval' ? 'active' : '' }}" id="fam-for-tab" data-bs-toggle="tab" data-bs-target="#fam-for-panel"
                            role="tab" data-tab-key="for_approval" aria-selected="{{ ($activeTab ?? 'new') === 'for_approval' ? 'true' : 'false' }}"
                            title="Shows only requests where Level 1 is already approved. Waiting for final approval, or view-only until the other officer acts.">
                        <i class="material-icons material-symbols-rounded d-inline align-middle" style="font-size:18px;">hourglass_top</i>
                        <span class="align-middle">Pending — other stage</span>
                        <span class="badge rounded-pill bg-warning text-dark ms-1">{{ $processedFamilyGroups->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ ($activeTab ?? 'new') === 'issued' ? 'active' : '' }}" id="fam-issued-tab" data-bs-toggle="tab" data-bs-target="#fam-issued-panel"
                            role="tab" data-tab-key="issued" aria-selected="{{ ($activeTab ?? 'new') === 'issued' ? 'true' : 'false' }}"
                            title="Fully approved / issued family ID requests.">
                        <i class="material-icons material-symbols-rounded d-inline align-middle" style="font-size:18px;">check_circle</i>
                        <span class="align-middle">Approved</span>
                        <span class="badge rounded-pill bg-success ms-1">{{ $issuedFamilyGroups->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ ($activeTab ?? 'new') === 'rejected' ? 'active' : '' }}" id="fam-rejected-tab" data-bs-toggle="tab" data-bs-target="#fam-rejected-panel"
                            role="tab" data-tab-key="rejected" aria-selected="{{ ($activeTab ?? 'new') === 'rejected' ? 'true' : 'false' }}"
                            title="Applications rejected at any stage.">
                        <i class="material-icons material-symbols-rounded d-inline align-middle" style="font-size:18px;">cancel</i>
                        <span class="align-middle">Rejected</span>
                        <span class="badge rounded-pill bg-danger ms-1">{{ $rejectedFamilyGroups->total() }}</span>
                    </button>
                </li>
            </ul>
            <p class="small text-muted mb-3 border-start border-3 border-primary ps-2">
                <strong>Pending — your action</strong> = you can approve or reject now at your level.
                <strong>Pending — other stage</strong> = Level 1 is already done; application is waiting for final approval or is read-only here (no plain Level 1 queue in this tab).
                <strong>Approved</strong> and <strong>Rejected</strong> are final outcomes.
            </p>

            <div class="tab-content">
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'new' ? 'show active' : '' }}" id="fam-new-panel" role="tabpanel"
                     style="{{ ($activeTab ?? 'new') === 'new' ? 'display:block;' : 'display:none;' }}">
                    @include('admin.security.family_idcard_approval._family_approval_table', ['groups' => $newFamilyGroups, 'familyMembersQueryString' => $familyMembersQueryString])
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Showing {{ $newFamilyGroups->firstItem() ?? 0 }} to {{ $newFamilyGroups->lastItem() ?? 0 }} of {{ $newFamilyGroups->total() }} entries</small>
                        {{ $newFamilyGroups->appends(array_merge(request()->query(), ['tab' => 'new']))->links() }}
                    </div>
                </div>
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'for_approval' ? 'show active' : '' }}" id="fam-for-panel" role="tabpanel"
                     style="{{ ($activeTab ?? 'new') === 'for_approval' ? 'display:block;' : 'display:none;' }}">
                    @include('admin.security.family_idcard_approval._family_approval_table', ['groups' => $processedFamilyGroups, 'familyMembersQueryString' => $familyMembersQueryString])
                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Showing {{ $processedFamilyGroups->firstItem() ?? 0 }} to {{ $processedFamilyGroups->lastItem() ?? 0 }} of {{ $processedFamilyGroups->total() }} entries</small>
                        {{ $processedFamilyGroups->appends(array_merge(request()->query(), ['tab' => 'for_approval']))->links() }}
                    </div>
                </div>
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'issued' ? 'show active' : '' }}" id="fam-issued-panel" role="tabpanel"
                     style="{{ ($activeTab ?? 'new') === 'issued' ? 'display:block;' : 'display:none;' }}">
                    @if($issuedFamilyGroups->total() === 0)
                        <div class="text-center text-muted py-5">
                            <i class="material-icons material-symbols-rounded" style="font-size:48px;opacity:.3;">verified</i>
                            <p class="mt-2 mb-0">No approved records in this tab.</p>
                        </div>
                    @else
                        @include('admin.security.family_idcard_approval._family_approval_table', ['groups' => $issuedFamilyGroups, 'familyMembersQueryString' => $familyMembersQueryString])
                        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted">Showing {{ $issuedFamilyGroups->firstItem() ?? 0 }} to {{ $issuedFamilyGroups->lastItem() ?? 0 }} of {{ $issuedFamilyGroups->total() }} entries</small>
                            {{ $issuedFamilyGroups->appends(array_merge(request()->query(), ['tab' => 'issued']))->links() }}
                        </div>
                    @endif
                </div>
                <div class="tab-pane {{ ($activeTab ?? 'new') === 'rejected' ? 'show active' : '' }}" id="fam-rejected-panel" role="tabpanel"
                     style="{{ ($activeTab ?? 'new') === 'rejected' ? 'display:block;' : 'display:none;' }}">
                    @if($rejectedFamilyGroups->total() === 0)
                        <div class="text-center text-muted py-5">
                            <i class="material-icons material-symbols-rounded" style="font-size:48px;opacity:.3;">cancel</i>
                            <p class="mt-2 mb-0">No rejected records found.</p>
                        </div>
                    @else
                        @include('admin.security.family_idcard_approval._family_approval_table', ['groups' => $rejectedFamilyGroups, 'familyMembersQueryString' => $familyMembersQueryString])
                        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted">Showing {{ $rejectedFamilyGroups->firstItem() ?? 0 }} to {{ $rejectedFamilyGroups->lastItem() ?? 0 }} of {{ $rejectedFamilyGroups->total() }} entries</small>
                            {{ $rejectedFamilyGroups->appends(array_merge(request()->query(), ['tab' => 'rejected']))->links() }}
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
                <h5 class="modal-title">Reject Family ID Card Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted mb-2" id="rejectMemberInfo"></p>
                    <div class="mb-3">
                        <label for="reject_remarks" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_remarks" name="approval_remarks" rows="3" required></textarea>
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
    background-color: rgba(255,255,255,0.95) !important;
    color: #004a93 !important;
}
.approval2-tabs .nav-link.active .badge.bg-success {
    background-color: #198754 !important;
    color: #fff !important;
}
.approval2-tabs .nav-link.active .badge.bg-danger {
    background-color: #dc3545 !important;
    color: #fff !important;
}
.approval2-tabs .nav-link.active .badge.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}
</style>
@endpush

@push('scripts')
<script>
function openRejectModal(btn) {
    var encryptedId = btn.getAttribute('data-encrypted-id');
    var memberCount = btn.getAttribute('data-member-count') || 'all';
    var url = "{{ route('admin.security.family_idcard_approval.reject_group', ':id') }}".replace(':id', encryptedId);
    document.getElementById('rejectForm').action = url;
    document.getElementById('reject_remarks').value = '';
    document.getElementById('rejectMemberInfo').textContent = 'This will reject ' + memberCount + ' family member(s) in this application.';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

document.addEventListener('DOMContentLoaded', function () {
    var perPage = document.getElementById('per_page');
    if (perPage) {
        perPage.addEventListener('change', function () {
            document.getElementById('filterForm').submit();
        });
    }

    try {
        var url = new URL(window.location.href);
        var tab = url.searchParams.get('tab');
        var validTabs = ['new', 'for_approval', 'issued', 'rejected'];
        if (tab === 'archive') { tab = 'issued'; }
        var tabKey = validTabs.indexOf(tab) !== -1 ? tab : 'new';
        var tabInput = document.getElementById('activeTabInput');
        if (tabInput) tabInput.value = tabKey;

        var tabBtns = {
            new: document.getElementById('fam-new-tab'),
            for_approval: document.getElementById('fam-for-tab'),
            issued: document.getElementById('fam-issued-tab'),
            rejected: document.getElementById('fam-rejected-tab'),
        };
        var panels = {
            new: document.getElementById('fam-new-panel'),
            for_approval: document.getElementById('fam-for-panel'),
            issued: document.getElementById('fam-issued-panel'),
            rejected: document.getElementById('fam-rejected-panel'),
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

document.querySelectorAll('#familyApprovalTabs .nav-link').forEach(function (btn) {
    btn.addEventListener('shown.bs.tab', function () {
        var tabKey = this.dataset.tabKey || 'new';
        var tabInput = document.getElementById('activeTabInput');
        if (tabInput) tabInput.value = tabKey;
        var panels = {
            new: document.getElementById('fam-new-panel'),
            for_approval: document.getElementById('fam-for-panel'),
            issued: document.getElementById('fam-issued-panel'),
            rejected: document.getElementById('fam-rejected-panel'),
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
