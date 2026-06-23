@extends('admin.layouts.master')

@section('title', 'Leave Approval Requests')

@section('content')

@include('admin.leave.faculty_approval.partials.styles')

<div class="container-fluid py-3 faculty-leave-approval-page">
    <x-breadcrum title="Leave Approval Requests" />
    <x-session_message />

    <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="mb-3">
                <h2 class="h5 mb-1 fw-semibold">Leave Approval Requests</h2>
                <div class="small text-muted">Leave Approvals / Pending Requests</div>
            </div>

            <div class="row g-2 mb-3 align-items-center">
                <div class="col-md-3">
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="1" selected>Pending</option>
                        <option value="2">Approved</option>
                        <option value="3">Rejected</option>
                        <option value="">All Status</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0 approval-table" id="faculty-leave-approval-table">
                    <thead>
                        <tr>
                            <th>S. NO.</th>
                            <th>PARTICIPANT NAME</th>
                            <th>LEAVE TYPE</th>
                            <th>FROM DATE</th>
                            <th>TO DATE</th>
                            <th>TOTAL DAYS</th>
                            <th>REASON</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                </table>
            </div>

            <div class="approval-note p-3 mt-3 small">
                Note: Your approval will be recorded and the participant will be notified.
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = $('#faculty-leave-approval-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('faculty.leave-approval.index') }}",
            data: function (d) {
                d.status = $('#filter_status').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'participant_name', name: 'student.display_name' },
            { data: 'leave_type_label', name: 'leave_type' },
            { data: 'from_date_display', name: 'from_date' },
            { data: 'to_date_display', name: 'to_date' },
            { data: 'total_days_display', name: 'total_days' },
            { data: 'reason_text', name: 'reason', orderable: false },
            { data: 'action', orderable: false, searchable: false },
        ],
    });

    $('#filter_status').on('change', function () {
        table.ajax.reload();
    });

    $(document).on('click', '.faculty-leave-approve', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Approve leave?',
            text: 'This leave application will be approved.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, approve',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.post("{{ route('faculty.leave-approval.approve', ':id') }}".replace(':id', id), {
                _token: '{{ csrf_token() }}'
            }).done(function (res) {
                toastr.success(res.message);
                table.ajax.reload(null, false);
            }).fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Approval failed.');
            });
        });
    });

    $(document).on('click', '.faculty-leave-reject', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Reject leave?',
            input: 'textarea',
            inputLabel: 'Remarks (optional)',
            inputPlaceholder: 'Reason for rejection...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Reject',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.post("{{ route('faculty.leave-approval.reject', ':id') }}".replace(':id', id), {
                _token: '{{ csrf_token() }}',
                rejection_remarks: result.value || ''
            }).done(function (res) {
                toastr.success(res.message);
                table.ajax.reload(null, false);
            }).fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Rejection failed.');
            });
        });
    });
});
</script>
@endpush
