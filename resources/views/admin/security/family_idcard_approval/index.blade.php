@extends('admin.layouts.master')
@section('title', 'Pending Family ID Card Approvals')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Pending Family ID Card Approvals"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Pending Family ID Card Approvals</h4>
                <a href="{{ route('admin.security.family_idcard_approval.all') }}" class="btn btn-secondary">
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
                            <th>Request ID</th>
                            <th>Family Member</th>
                            <th>Employee ID</th>
                            <th>Relation</th>
                            <th>Validity Period</th>
                            <th>Applied On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingApplications as $app)
                            <tr>
                                <td><code>{{ $app->fml_id_apply }}</code></td>
                                <td>
                                    <strong>{{ $app->family_name ?? '--' }}</strong>
                                </td>
                                <td>{{ $app->emp_id_apply ?? '--' }}</td>
                                <td>{{ $app->family_relation ?? '--' }}</td>
                                <td>
                                    {{ $app->card_valid_from ? $app->card_valid_from->format('d-m-Y') : '--' }}
                                    to
                                    {{ $app->card_valid_to ? $app->card_valid_to->format('d-m-Y') : '--' }}
                                </td>
                                <td>{{ $app->created_date ? $app->created_date->format('d-m-Y H:i') : '--' }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.security.family_idcard_approval.show', encrypt($app->fml_id_apply)) }}"
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i>
                                        </a>
                                        <form action="{{ route('admin.security.family_idcard_approval.approve', encrypt($app->fml_id_apply)) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Approve"
                                                    onclick="return confirm('Approve this Family ID Card?')">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">check_circle</i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger" title="Reject"
                                                data-encrypted-id="{{ encrypt($app->fml_id_apply) }}"
                                                onclick="openRejectModal(this)">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">cancel</i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No pending Family ID Card applications found.</td>
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

@push('scripts')
<script>
function openRejectModal(btn) {
    const encryptedId = btn.getAttribute('data-encrypted-id');
    const url = "{{ route('admin.security.family_idcard_approval.reject', ':id') }}".replace(':id', encryptedId);
    document.getElementById('rejectForm').action = url;
    document.getElementById('reject_remarks').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endpush
