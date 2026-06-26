@extends('admin.layouts.master')

@section('title', 'PT Exemption Master - Sargam | Lal Bahadur')

@section('setup_content')
<style>
    .pt-exemption-page .datatables,
    .pt-exemption-page .card,
    .pt-exemption-page .card-body {
        min-width: 0;
    }
    .pt-exemption-page #exemption-master-table_wrapper {
        width: 100%;
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 991.98px) {
        .pt-exemption-page #exemption-master-table_wrapper {
            max-height: min(70dvh, 32rem);
            overflow: auto !important;
        }
    }
    @media (min-width: 992px) {
        .pt-exemption-page #exemption-master-table_wrapper {
            overflow-x: auto;
        }
    }
</style>
<div class="container-fluid py-3 pt-exemption-page">
    <x-breadcrum title="PT Exemption Master" />
    <x-session_message />
    <section class="datatables" aria-labelledby="pt-exemption-heading">
        <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
            <div class="card-body p-3 p-md-4">
                <header class="row align-items-center g-3 mb-3 mb-md-4 pb-3 border-bottom border-light">
                    <div class="col-12 col-md">
                        <h2 id="pt-exemption-heading" class="h5 mb-0 fw-semibold text-body">PT Exemption Master</h2>
                        <p class="small text-secondary mb-0 mt-1 d-none d-md-block">
                            Configure PT exemption day limits per course and gender.
                        </p>
                    </div>
                    <div class="col-12 col-md-auto">
                        <a href="{{ route('admin.pt-exemption-master.create') }}"
                            class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 w-100 w-md-auto px-4 py-2">
                            <i class="material-icons material-symbols-rounded fs-5" aria-hidden="true">add</i>
                            <span>Add Exemption</span>
                        </a>
                    </div>
                </header>
                <p class="small text-secondary d-lg-none mb-2" role="note">
                    Scroll inside the table area to see all rows and columns.
                </p>
                <div class="w-100 min-w-0" role="region" aria-label="PT exemption master listing">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 text-nowrap table-light" id="exemption-master-table">
                            <thead class="table-light">
                                <tr>
                                    <th>SR NO.</th>
                                    <th>COURSE</th>
                                    <th>EFFECTIVE FROM</th>
                                    <th>PT TIMING</th>
                                    <th>GENDER</th>
                                    <th>PT EXEMPTION COUNT (DAYS)</th>
                                    <th>STATUS</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = $('#exemption-master-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('admin.pt-exemption-master.index') }}",
            data: function (d) {
                d.pk = $('#pk').val();
                d.active_inactive = $('#active_inactive').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'course_name', name: 'course.course_name' },
            { data: 'effective_from_display', name: 'effective_from' },
            { data: 'apply_cutoff_time_display', name: 'apply_cutoff_time', orderable: false, searchable: false },
            { data: 'gender', name: 'gender' },
            { data: 'exemption_days_display', name: 'exemption_days' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: {
            emptyTable: 'No PT exemption configuration found.',
        },
    });

    $(document).on('change', '.exemption-status-toggle', function () {
        const id = $(this).data('id');
        const active = $(this).is(':checked') ? 1 : 0;
        const $toggle = $(this);

        $.ajax({
            url: "{{ route('admin.pt-exemption-master.status', ':id') }}".replace(':id', id),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                active_inactive: active,
            },
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

    $(document).on('click', '.exemption-delete-btn', function () {
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
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: "{{ route('admin.pt-exemption-master.destroy', ':id') }}".replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                },
                success: function (res) {
                    toastr.success(res.message || 'Record deleted successfully.');
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to delete record.';
                    toastr.error(message);
                },
            });
        });
    });
});
</script>
@endpush

<input type="hidden" id="pk" value="">
<input type="hidden" id="active_inactive" value="">
