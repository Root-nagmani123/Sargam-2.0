@extends('admin.layouts.master')
@section('title', 'Pending Vehicle Pass Approvals')
@section('content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Pending Vehicle Pass Approvals']) 
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Pending Vehicle Pass Approvals</h4>
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

            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.security.vehicle_pass_approval.index') }}" class="row g-3 align-items-end">
                       <div class="col-md-2">
                            <label for="wheeler" class="form-label">Vehicle Type</label>
                            <select name="wheeler" id="wheeler" class="form-select">
                                @php $wh = $wheeler ?? request('wheeler', 'tw'); @endphp
                                <option value="tw" {{ $wh === 'tw' ? 'selected' : '' }}>Two Wheeler</option>
                                <option value="fw" {{ $wh === 'fw' ? 'selected' : '' }}>Four Wheeler</option>
                                <option value="all" {{ $wh === 'all' ? 'selected' : '' }}>Both</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control"
                                   placeholder="Search by Employee / Vehicle"
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
                        
                        <div class="col-md-12 d-flex justify-content-end gap-2">
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

            <div class="datatables">
                <div class="table-responsive">
                    <table id="vehicle-pass-approval-table" class="table text-nowrap align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Employee Name</th>
                                <th scope="col">Vehicle Number</th>
                                <th scope="col">Vehicle Type</th>
                                <th scope="col">Status</th>
                                <th scope="col">Vehicle Pass No</th>
                                <th scope="col">Employee ID</th>
                                <th scope="col">Applied On</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                    <tbody>
                        @forelse($pendingApplications as $app)
                            <tr>
                             <td>{{ $app->employee_name ?? '--' }}</td>
                                <td><strong>{{ $app->vehicle_number ?? '--' }}</strong></td>
                                <td>{{ $app->vehicle_type ?? '--' }}</td>
                                <td>
                                    <span class="badge bg-{{ $app->status_class ?? 'secondary' }}">
                                        {{ $app->status ?? '--' }}
                                    </span>
                                </td>
                                <td>{{ $app->vehicle_pass_no ?? '--' }}</td>
                                <td>{{ $app->employee_id ?? '--' }}</td>
                                <td>{{ $app->created_date ? \Carbon\Carbon::parse($app->created_date)->format('d-m-Y H:i') : '--' }}</td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        @php
                                            $encryptId = encrypt($app->id);
                                        @endphp
                                        <a href="{{ route('admin.security.vehicle_pass_approval.show', $encryptId) }}" 
                                           class="btn  btn-info bg-transparent border-0 text-primary p-0" title="View Details">
                                            <i class="material-icons material-symbols-rounded">visibility</i>
                                        </a>
                                        @if($app->can_approve ?? false)
                                            <button type="button" class="btn  btn-success btn-veh-approve bg-transparent border-0 text-primary p-0" 
                                                    data-encrypted-id="{{ $encryptId }}" title="Approve">
                                                <i class="material-icons material-symbols-rounded">check_circle</i>
                                            </button>
                                            <button type="button" class="btn  btn-danger btn-veh-reject bg-transparent border-0 text-primary p-0" 
                                                data-encrypted-id="{{ $encryptId }}" title="Reject">
                                            <i class="material-icons material-symbols-rounded">cancel</i>
                                            </button>
                                        @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No pending applications found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    </table>
                </div>
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
})();
</script>
@endpush
