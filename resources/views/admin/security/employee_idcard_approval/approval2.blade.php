@extends('admin.layouts.master')
@section('title', 'Approval II - Employee ID Card Requests')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Approval II - Employee ID Card'])
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h4 class="mb-0">Approval II</h4>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('admin.security.employee_idcard_approval.approval1') }}" class="btn btn-outline-primary btn-sm">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">arrow_back</i>
                        Approval I
                    </a>
                    <a href="{{ route('admin.security.employee_idcard_approval.all') }}" class="btn btn-secondary btn-sm">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">list</i>
                        All Requests
                    </a>
                </div>
            </div>

            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select name="card_type" class="form-select form-select-sm">
                        <option value="">Select ID Card Type</option>
                        @foreach(\App\Models\EmployeeIDCardRequest::distinct()->whereNotNull('card_type')->pluck('card_type') as $ct)
                            <option value="{{ $ct }}" {{ request('card_type') == $ct ? 'selected' : '' }}>{{ $ct }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search here..." value="{{ request('search') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </div>
            </form>

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

            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px;"><input type="checkbox" id="selectAll"></th>
                            <th style="width:60px;">S.No.</th>
                            <th>Request date</th>
                            <th>Employee Name</th>
                            <th>Approved By A1</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $index => $req)
                            <tr>
                                <td><input type="checkbox" class="row-check" value="{{ $req->id }}"></td>
                                <td>{{ $requests->firstItem() + $index }}</td>
                                <td>{{ $req->created_at ? $req->created_at->format('d/m/Y') : '--' }}</td>
                                <td>{{ $req->name }}</td>
                                <td>
                                    @if($req->approver1)
                                        <span class="badge bg-success">{{ $req->approver1->name }}</span>
                                        <br><small class="text-muted">{{ $req->approved_by_a1_at ? $req->approved_by_a1_at->format('d/m/Y') : '' }}</small>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.security.employee_idcard_approval.show', encrypt($req->id)) }}"
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i>
                                        </a>
                                        <a href="{{ route('admin.employee_idcard.show', $req->id) }}"
                                           class="btn btn-sm btn-outline-secondary" title="Full Details">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">open_in_new</i>
                                        </a>
                                        <form action="{{ route('admin.security.employee_idcard_approval.approve2', encrypt($req->id)) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">check_circle</i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger reject-btn" title="Reject"
                                                data-name="{{ $req->name }}"
                                                data-url="{{ route('admin.security.employee_idcard_approval.reject2', encrypt($req->id)) }}">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">cancel</i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No pending requests for Approval II.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">Show {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} entries</small>
                {{ $requests->links() }}
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
</script>
@endpush
