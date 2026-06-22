@extends('admin.layouts.master')

@section('title', 'My Leave Applications')

@section('content')

@include('admin.leave.partials.styles')

<div class="container-fluid py-3 leave-module">
    <x-breadcrum title="My Leave Applications" />
    <x-session_message />

    <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h2 class="h5 mb-1 fw-semibold">My Leave Applications</h2>
                            <div class="small text-muted">Leave / My Leave</div>
                        </div>
                        <a href="{{ route('leave.apply') }}" class="btn btn-primary btn-sm">Apply Leave</a>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <select id="filter_leave_type" class="form-select form-select-sm">
                                <option value="">All Leave Types</option>
                                <option value="PT_EXEMPTION">PT Exemption</option>
                                <option value="STATIONED_LEAVE">Stationed Leave</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="filter_status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="0">Draft</option>
                                <option value="1">Pending</option>
                                <option value="2">Approved</option>
                                <option value="3">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="my-leave-table">
                            <thead class="table-light">
                                <tr>
                                    <th>S. NO.</th>
                                    <th>LEAVE TYPE</th>
                                    <th>FROM DATE</th>
                                    <th>TO DATE</th>
                                    <th>TOTAL DAYS</th>
                                    <th>STATUS</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    <div class="info-alert p-3 mt-3 small">
                        You can edit or delete only those leave applications which are in <strong>Pending</strong> or <strong>Draft</strong> status.
                    </div>
                </div>
            </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = $('#my-leave-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('leave.my-leave') }}",
            data: function (d) {
                d.leave_type = $('#filter_leave_type').val();
                d.status = $('#filter_status').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'leave_type_label', name: 'leave_type' },
            { data: 'from_date_display', name: 'from_date' },
            { data: 'to_date_display', name: 'to_date' },
            { data: 'total_days_display', name: 'total_days' },
            { data: 'status_badge', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false },
        ],
    });

    $('#filter_leave_type, #filter_status').on('change', function () {
        table.ajax.reload();
    });

    $(document).on('click', '.leave-delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This leave application will be deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ route('leave.destroy', ':id') }}".replace(':id', id),
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function (res) {
                    toastr.success(res.message);
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Delete failed.');
                }
            });
        });
    });
});
</script>
@endpush
