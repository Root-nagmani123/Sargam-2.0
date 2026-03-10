@extends('admin.layouts.master')
@section('title', 'Pending Vehicle Pass Approvals')
@section('setup_content')
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

            <div class="datatables">
                <div class="table-responsive">
                    <table id="vehicle-pass-approval-table" class="table text-nowrap align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Employee Name</th>
                                <th scope="col">Vehicle Number</th>
                                <th scope="col">Vehicle Type</th>
                                <th scope="col">Request Type</th>
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
                                    @if(isset($app->request_type) && $app->request_type === 'duplicate')
                                        <span class="badge bg-warning">Duplicate</span>
                                    @else
                                        <span class="badge bg-info">Regular</span>
                                    @endif
                                </td>
                                <td>{{ $app->vehicle_pass_no ?? '--' }}</td>
                                <td>{{ $app->employee_id ?? '--' }}</td>
                               
                                <td>{{ $app->created_date ? \Carbon\Carbon::parse($app->created_date)->format('d-m-Y H:i') : '--' }}</td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        @php
                                            $encryptId = isset($app->request_type) && $app->request_type === 'duplicate' 
                                                ? encrypt('dup-' . $app->id) 
                                                : encrypt($app->id);
                                        @endphp
                                        <a href="{{ route('admin.security.vehicle_pass_approval.show', $encryptId) }}" 
                                           class="btn  btn-info bg-transparent border-0 text-primary p-0" title="View Details">
                                            <i class="material-icons material-symbols-rounded">visibility</i>
                                        </a>
                                        <button type="button" class="btn  btn-success btn-veh-approve bg-transparent border-0 text-primary p-0" 
                                                data-encrypted-id="{{ $encryptId }}" title="Approve">
                                            <i class="material-icons material-symbols-rounded">check_circle</i>
                                        </button>
                                        <button type="button" class="btn  btn-danger btn-veh-reject bg-transparent border-0 text-primary p-0" 
                                            data-encrypted-id="{{ $encryptId }}" title="Reject">
                                        <i class="material-icons material-symbols-rounded">cancel</i>
                                    </button>
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

    // DataTables: sorting, search, pagination (page length 10)
    $(document).ready(function () {
        var $table = $('#vehicle-pass-approval-table');
        if ($table.length && typeof $.fn.DataTable !== 'undefined' && !$.fn.DataTable.isDataTable($table)) {
            $table.DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50], [10, 25, 50]],
                order: [[0, 'asc']],
                ordering: true,
                searching: true,
                dom: '<"row align-items-center mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row align-items-center mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                language: {
                    search: '',
                    searchPlaceholder: 'Search applications...',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries',
                    infoFiltered: '(filtered from _MAX_ total)',
                    paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' },
                    zeroRecords: 'No matching applications found'
                },
                columnDefs: [{ orderable: false, targets: 7 }],
                drawCallback: function () {
                    if (typeof window.adjustAllDataTables === 'function') {
                        try { window.adjustAllDataTables(); } catch (e) {}
                    }
                }
            });
            var wrapper = $table.closest('.datatables');
            if (wrapper.length) {
                wrapper.find('.dataTables_length select').addClass('form-select form-select-sm');
                wrapper.find('.dataTables_filter input').addClass('form-control').attr('placeholder', 'Search applications...');
                wrapper.find('.dataTables_info').addClass('small text-muted');
                wrapper.find('.dataTables_paginate').addClass('small');
            }
        }
    });
})();
</script>
@endpush
