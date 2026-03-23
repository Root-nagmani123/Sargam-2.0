@extends('admin.layouts.master')
@section('title', 'Pending Family ID Card Approvals')
@section('setup_content')
@php
    $familyApprovalReturn = in_array(request('return'), ['approval2', 'approval3'], true) ? request('return') : null;
    $familyMembersQs = ['from' => 'family_approval'];
    if ($familyApprovalReturn) {
        $familyMembersQs['return'] = $familyApprovalReturn;
    }
    $familyMembersQueryString = '?' . http_build_query($familyMembersQs);
@endphp
<div class="container-fluid">
    <x-breadcrum title="Pending Family ID Card Approvals"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Pending Family ID Card Approvals</h4>
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
                    <form method="GET" action="{{ route('admin.security.family_idcard_approval.index') }}" class="row g-3 align-items-end">
                        @if($familyApprovalReturn)
                            <input type="hidden" name="return" value="{{ $familyApprovalReturn }}">
                        @endif
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control"
                                   placeholder="Search by Submitted By / Employee ID"
                                   value="{{ $search ?? request('search', '') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Applied From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control"
                                   value="{{ $dateFrom ?? request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Applied To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control"
                                   value="{{ $dateTo ?? request('date_to') }}">
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

            <div class="table-responsive">
                <table class="table text-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Submitted By</th>
                            <th>Employee Type</th>
                            <th>Employee ID</th>
                            <th>Member Count</th>
                            <th>Status</th>
                            <th>Applied On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $group)
                            <tr>
                                <td><strong>{{ $group->submitted_by ?? '--' }}</strong></td>
                                <td>
                                    @if(isset($group->employee_type) && $group->employee_type === 'Contractual Employee')
                                        <span class="badge bg-warning">Contractual</span>
                                    @else
                                        <span class="badge bg-info">Permanent</span>
                                    @endif
                                </td>
                                <td><code>{{ $group->emp_id_apply ?? '--' }}</code></td>
                                <td>
                                    <span class="badge bg-primary">{{ $group->member_count }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $group->phase_class ?? 'secondary' }}"
                                          title="{{ $group->phase_label ?? 'Unknown' }}">
                                        {{ $group->phase_label ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td>{{ $group->created_date ? \Carbon\Carbon::parse($group->created_date)->format('d-m-Y H:i') : '--' }}</td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="{{ route('admin.family_idcard.members', $group->first_id) }}{{ $familyMembersQueryString }}"
                                           class="btn  btn-outline-info bg-transparent border-0 text-primary p-0" title="View Members">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i>
                                        </a>
                                        @if($group->can_approve ?? false)
                                            <form action="{{ route('admin.security.family_idcard_approval.approve_group', encrypt($group->first_id)) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn  btn-outline-success bg-transparent border-0 text-primary p-0" title="Approve"
                                                        onclick="return confirm('Are you sure you want to approve?')">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">check_circle</i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn  btn-outline-danger bg-transparent border-0 text-primary p-0" title="Reject"
                                                    data-encrypted-id="{{ encrypt($group->first_id) }}"
                                                    data-member-count="{{ $group->member_count }}"
                                                    onclick="openRejectModal(this)">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">cancel</i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No pending Family ID Card applications found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $groups->links() }}
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

@push('scripts')
<script>
function openRejectModal(btn) {
    const encryptedId = btn.getAttribute('data-encrypted-id');
    const memberCount = btn.getAttribute('data-member-count') || 'all';
    const url = "{{ route('admin.security.family_idcard_approval.reject_group', ':id') }}".replace(':id', encryptedId);
    document.getElementById('rejectForm').action = url;
    document.getElementById('reject_remarks').value = '';
    document.getElementById('rejectMemberInfo').textContent = 'This will reject ' + memberCount + ' family member(s) in this application.';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endpush
