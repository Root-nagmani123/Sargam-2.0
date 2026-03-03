@extends('admin.layouts.master')

@section('title', 'Estate Possession for Others - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Estate Possession for Others"></x-breadcrum>
    <x-session_message />

    <!-- Page Card -->
    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5" id="possessionCardBody">
            <!-- Header -->
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-semibold mb-1">Estate Possession for Others</h1>
                </div>
                <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                    <a href="{{ route('admin.estate.update-meter-reading-of-other') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 text-decoration-none">
                        <span>Update Reading</span>
                    </a>
                    <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-primary d-inline-flex align-items-center gap-2" title="Add possession">
                        <span>Add Possession</span>
                    </a>
                </div>
            </div>

            <hr class="my-4">
            <div class="table-responsive overflow-auto estate-possession-table-wrap rounded-3">
                {!! $dataTable->table(['class' => 'table text-nowrap mb-0 w-100']) !!}
            </div>
            <div id="estate-possession-caption" class="visually-hidden">Estate Possession for Others list</div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deletePossessionModal" tabindex="-1" aria-labelledby="deletePossessionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="deletePossessionModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0">Are you sure you want to delete this possession record? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-2 d-inline-flex align-items-center gap-2" id="confirmDeleteBtn">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table-responsive {
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #estatePossessionTable_wrapper .dataTables_scrollBody,
    #estatePossessionTable_wrapper .dataTables_scroll {
        max-width: 100%;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    #estatePossessionTable thead th {
        font-weight: 600;
        white-space: nowrap;
    }
    @media (max-width: 991.98px) {        .estate-possession-table-wrap,
        #estatePossessionTable_wrapper .dataTables_scrollBody {
            max-height: 60vh;
            overflow-y: auto !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }
    }
    @media (max-width: 575.98px) {
        .estate-possession-table-wrap,
        #estatePossessionTable_wrapper .dataTables_scrollBody {
            max-height: 55vh;
        }
    }
</style>
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    $(document).ready(function() {
        let deleteUrl = '';

        $(document).on('click', '.btn-delete-possession', function(e) {
            e.preventDefault();
            deleteUrl = $(this).data('url');
            $('#deletePossessionModal').modal('show');
        });

        $('#confirmDeleteBtn').on('click', function() {
            if (!deleteUrl) return;
            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                },
                success: function(response) {
                    $('#deletePossessionModal').modal('hide');
                    if (response.success) {
                        $('#estatePossessionTable').DataTable().ajax.reload(null, false);
                        const alert = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2 flex-shrink-0" aria-hidden="true"></i><span class="flex-grow-1">' + response.message + '</span><button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert"></button></div>';
                        $('#possessionCardBody').find('.alert-success').remove();
                        $('#possessionCardBody').prepend(alert);
                        setTimeout(function() { $('.alert-success').fadeOut(); }, 3000);
                    }
                },
                error: function(xhr) {
                    $('#deletePossessionModal').modal('hide');
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to delete.';
                    const alert = '<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0" aria-hidden="true"></i><span class="flex-grow-1">' + msg + '</span><button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert"></button></div>';
                    $('#possessionCardBody').find('.alert-danger').remove();
                    $('#possessionCardBody').prepend(alert);
                }
            });
            deleteUrl = '';
        });
    });
    </script>
@endpush
