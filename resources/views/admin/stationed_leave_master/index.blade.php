@extends('admin.layouts.master')

@section('title', 'Stationed Leave Master - Sargam | Lal Bahadur')

@section('setup_content')
<style>
    .stationed-leave-page .datatables,
    .stationed-leave-page .card,
    .stationed-leave-page .card-body {
        min-width: 0;
    }
    .stationed-leave-page #stationed-leave-table_wrapper {
        width: 100%;
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
    }
</style>
<div class="container-fluid py-3 stationed-leave-page">
    <x-breadcrum title="Stationed Leave Master" />
    <x-session_message />
    <section class="datatables">
        <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
            <div class="card-body p-3 p-md-4">
                <header class="row align-items-center g-3 mb-3 mb-md-4 pb-3 border-bottom border-light">
                    <div class="col-12 col-md">
                        <h2 class="h5 mb-0 fw-semibold text-body">Stationed Leave Master</h2>
                        <p class="small text-secondary mb-0 mt-1 d-none d-md-block">
                            Configure stationed leave approval settings per course.
                        </p>
                    </div>
                    <div class="col-12 col-md-auto">
                        <a href="{{ route('admin.stationed-leave-master.create') }}"
                            class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 w-100 w-md-auto px-4 py-2">
                            <i class="material-icons material-symbols-rounded fs-5">add</i>
                            <span>Add Configuration</span>
                        </a>
                    </div>
                </header>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 text-nowrap table-light" id="stationed-leave-table">
                        <thead class="table-light">
                            <tr>
                                <th>SR NO.</th>
                                <th>COURSE</th>
                                <th>EFFECTIVE FROM</th>
                                <th>PT TIMING</th>
                                <th>APPROVAL REQUIRED</th>
                                <th>FACULTY COUNT</th>
                                <th>STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = $('#stationed-leave-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        order: [[0, 'desc']],
        ajax: "{{ route('admin.stationed-leave-master.index') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'course_name', name: 'course.course_name' },
            { data: 'effective_from_display', name: 'effective_from' },
            { data: 'apply_cutoff_time_display', name: 'apply_cutoff_time', orderable: false, searchable: false },
            { data: 'approval_required_display', name: 'is_faculty_approval_required' },
            { data: 'faculty_count_display', name: 'approvers_count' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: { emptyTable: 'No stationed leave configuration found.' },
    });

    $(document).on('change', '.stationed-leave-status-toggle', function () {
        const id = $(this).data('id');
        const active = $(this).is(':checked') ? 1 : 0;
        const $toggle = $(this);

        $.ajax({
            url: "{{ route('admin.stationed-leave-master.status', ':id') }}".replace(':id', id),
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', active_inactive: active },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message);
                    table.ajax.reload(null, false);
                }
            },
            error: function () {
                $toggle.prop('checked', !active);
                toastr.error('Failed to update status.');
            }
        });
    });

    $(document).on('click', '.stationed-leave-delete-btn', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This record will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ route('admin.stationed-leave-master.destroy', ':id') }}".replace(':id', id),
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function (res) {
                    toastr.success(res.message || 'Record deleted successfully.');
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to delete record.');
                },
            });
        });
    });
});
</script>
@endpush
