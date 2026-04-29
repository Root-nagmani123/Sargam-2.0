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

            <!-- Filters Section -->
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
                                       placeholder="Search by Employee Name, ID Card No..." value="{{ request('search', '') }}">
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
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="" {{ $statusFilter === '' ? 'selected' : '' }}>All</option>
                                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
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

            @include('admin.security.employee_idcard_approval._approval_table', ['requests' => $requests, 'approvalStage' => 3])

            <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} entries</small>
                {{ $requests->withQueryString()->links() }}
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

// Auto-submit when per_page changes
document.getElementById('per_page').addEventListener('change', function() {
    document.getElementById('filterForm3').submit();
});
</script>
@endpush
@endsection

