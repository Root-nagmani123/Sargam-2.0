@extends('admin.layouts.master')
@section('title', 'Approval I - Employee ID Card Requests')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Approval I - Employee ID Card'])
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-body-tertiary border-bottom-0 pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1 fw-semibold">Approval I</h4>
                    <p class="mb-0 small text-body-secondary">Review and act on pending employee ID card requests.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                   {{--
                    <a href="{{ route('admin.security.employee_idcard_approval.approval2') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">arrow_forward</i>
                        <span>Approval II</span>
                    </a>
                    <a href="{{ route('admin.security.employee_idcard_approval.all') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">list</i>
                        <span>All Requests</span>
                    </a>
                   --}}
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filters Section -->
            <div class="card border-0 bg-body-tertiary mb-4">
                <div class="card-header bg-transparent border-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">
                                <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">tune</i>
                            </span>
                            Filters &amp; Search
                        </h6>
                        @if(request()->anyFilled(['search', 'date_from', 'date_to']))
                            <span class="badge bg-secondary-subtle text-secondary-emphasis small">Filters applied</span>
                        @endif
                    </div>
                </div>
                <div class="card-body pt-3">
                    <form method="GET" action="{{ route('admin.security.employee_idcard_approval.approval1') }}" id="filterForm" class="mb-0">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-3">
                                <label for="search" class="form-label small fw-medium text-body-secondary">Search</label>
                                <input
                                    type="text"
                                    name="search"
                                    id="search"
                                    class="form-control form-control-sm"
                                    placeholder="Search by employee name, ID card no..."
                                    value="{{ request('search', '') }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-medium text-body-secondary">Request Date From</label>
                                <input
                                    type="date"
                                    name="date_from"
                                    class="form-control form-control-sm"
                                    value="{{ request('date_from') }}">
                            </div>
                                <div class="col-6 col-md-3">
                                <label class="form-label small fw-medium text-body-secondary">Request Date To</label>
                                <input
                                    type="date"
                                    name="date_to"
                                    class="form-control form-control-sm"
                                    value="{{ request('date_to') }}">
                            </div>
                            <div class="col-6 col-md-2">
                                <label for="per_page" class="form-label small fw-medium text-body-secondary">Show entries</label>
                                <select name="per_page" id="per_page" class="form-select form-select-sm">
                                    @foreach([10, 25, 50, 100] as $n)
                                        <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-1 d-flex gap-2 justify-content-md-end">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 flex-md-grow-0 d-flex align-items-center justify-content-center gap-2">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">search</i>
                                    <span>Search</span>
                                </button>
                                <a href="{{ route('admin.security.employee_idcard_approval.approval1') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center">
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
                    <button class="btn btn-outline-success btn-sm dropdown-toggle d-flex align-items-center gap-2 px-3 py-2" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">download</i>
                        <span>Export</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="exportDropdown">
                        <li>
                            <h6 class="dropdown-header text-muted small text-uppercase">Export with current filters</h6>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.employee_idcard_approval.export', array_merge(['stage' => '1', 'format' => 'xlsx'], request()->query())) }}">
                                <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i>
                                <span>Excel</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.employee_idcard_approval.export', array_merge(['stage' => '1', 'format' => 'pdf'], request()->query())) }}">
                                <i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;">picture_as_pdf</i>
                                <span>PDF</span>
                            </a>
                        </li>
                    </ul>
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

            @include('admin.security.employee_idcard_approval._approval_table', ['requests' => $requests, 'approvalStage' => 1])

            <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} entries</small>
                <div class="ms-auto">
                    {{ $requests->withQueryString()->links() }}
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
    document.getElementById('filterForm').submit();
});
</script>
@endpush
