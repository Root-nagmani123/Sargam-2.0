@extends('admin.layouts.master')
@section('title', 'Pending Vehicle Pass Approvals')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Pending Vehicle Pass Approvals']) 
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Pending Vehicle Pass Approvals</h4>
                <a href="{{ route('admin.security.vehicle_pass_approval.all') }}" class="btn btn-secondary">
                    View All Applications
                </a>
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

            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Vehicle Number</th>
                            <th>Request Type</th>
                            <th>Employee ID</th>
                            <th>Vehicle Type</th>
                            <th>Applied On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingApplications as $app)
                            <tr>
                                <td><strong>{{ $app->vehicle_number ?? '--' }}</strong></td>
                                <td>
                                    @if(isset($app->request_type) && $app->request_type === 'duplicate')
                                        <span class="badge bg-warning">Duplicate</span>
                                    @else
                                        <span class="badge bg-info">Regular</span>
                                    @endif
                                </td>
                                <td><code>{{ $app->employee_id ?? '--' }}</code></td>
                                <td>{{ $app->vehicle_type ?? '--' }}</td>
                                <td>{{ $app->created_date ? \Carbon\Carbon::parse($app->created_date)->format('d-m-Y H:i') : '--' }}</td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        @php
                                            $encryptId = isset($app->request_type) && $app->request_type === 'duplicate' 
                                                ? encrypt('dup-' . $app->id) 
                                                : encrypt($app->id);
                                        @endphp
                                        <a href="{{ route('admin.security.vehicle_pass_approval.show', $encryptId) }}" 
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i> View
                                        </a>
                                        <button type="button" class="btn btn-sm btn-success" 
                                                onclick="approveApplication('{{ $encryptId }}')" title="Approve">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">check_circle</i> Approve
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="rejectApplication('{{ $encryptId }}')" title="Reject">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">cancel</i> Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No pending applications found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $pendingApplications->links() }}
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
                            <option value="1">Forward for Card Printing</option>
                            <option value="2">Card Ready</option>
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

@push('scripts')
<script>
function approveApplication(encryptedId) {
    const url = "{{ route('admin.security.vehicle_pass_approval.approve', ':id') }}".replace(':id', encryptedId);
    $('#approveForm').attr('action', url);
    $('#approveModal').modal('show');
}

function rejectApplication(encryptedId) {
    const url = "{{ route('admin.security.vehicle_pass_approval.reject', ':id') }}".replace(':id', encryptedId);
    $('#rejectForm').attr('action', url);
    $('#rejectModal').modal('show');
}
</script>
@endpush
