@extends('admin.layouts.master')

@section('title', 'Possession Details - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Possession Details"></x-breadcrum>
    <x-estate-workflow-stepper current="possession-details" />

    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5" id="possessionDetailsCardBody">
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center justify-content-between gap-3 mb-4 no-print">
                <div>
                    <h1 class="h4 fw-semibold mb-1">Possession Details</h1>
                    <p class="text-muted small mb-0">LBSNAA employee possession records (allotted via HAC Approved flow).</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.possession-details.create') }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2" title="Add possession">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add</span>
                    </a>
                    <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2">
                        <i class="bi bi-arrow-right-circle"></i>
                        <span>Update Reading</span>
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2 flex-shrink-0" aria-hidden="true"></i>
                    <span class="flex-grow-1">{{ session('success') }}</span>
                    <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0" aria-hidden="true"></i>
                    <span class="flex-grow-1">{{ session('error') }}</span>
                    <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <hr class="my-4">
            <div class="table-responsive overflow-auto rounded-3">
                {!! $dataTable->table(['class' => 'table table-bordered table-striped table-hover align-middle text-nowrap mb-0 w-100', 'style' => 'min-width: 1200px;', 'id' => 'estatePossessionDetailsTable', 'aria-describedby' => 'estate-possession-details-caption']) !!}
            </div>
            <div id="estate-possession-details-caption" class="visually-hidden">Possession details list</div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deletePossessionDetailsModal" tabindex="-1" aria-labelledby="deletePossessionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="deletePossessionDetailsModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0">Are you sure you want to delete this possession details record? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-2 d-inline-flex align-items-center gap-2" id="confirmDeletePossessionDetailsBtn">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #estatePossessionDetailsTable_wrapper .dataTables_length label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    #estatePossessionDetailsTable_wrapper .dataTables_length select {
        width: auto;
        min-width: 4.5rem;
        padding: 0.25rem 2rem 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: var(--bs-border-radius);
        border: 1px solid var(--bs-border-color, #dee2e6);
    }
    #estatePossessionDetailsTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    #estatePossessionDetailsTable_wrapper .dataTables_filter input {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
    }
    #estatePossessionDetailsTable_wrapper thead th {
        background-color: var(--bs-primary);
        color: #fff;
        font-weight: 600;
        border-color: var(--bs-primary);
        padding: 0.75rem;
        white-space: nowrap;
    }
</style>
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    $(document).ready(function() {
        let deleteUrl = '';

        $(document).on('click', '.btn-delete-possession-details', function(e) {
            e.preventDefault();
            deleteUrl = $(this).data('url');
            $('#deletePossessionDetailsModal').modal('show');
        });

        $('#confirmDeletePossessionDetailsBtn').on('click', function() {
            if (!deleteUrl) return;

            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    $('#deletePossessionDetailsModal').modal('hide');
                    if (response.success) {
                        $('#estatePossessionDetailsTable').DataTable().ajax.reload(null, false);
                        var alert = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2 flex-shrink-0"></i><span class="flex-grow-1">' + response.message + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('#possessionDetailsCardBody').find('.alert-success').remove();
                        $('#possessionDetailsCardBody').prepend(alert);
                        setTimeout(function() { $('.alert-success').fadeOut(); }, 3000);
                    }
                },
                error: function(xhr) {
                    $('#deletePossessionDetailsModal').modal('hide');
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to delete.';
                    var alert = '<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0"></i><span class="flex-grow-1">' + msg + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    $('#possessionDetailsCardBody').find('.alert-danger').remove();
                    $('#possessionDetailsCardBody').prepend(alert);
                }
            });

            deleteUrl = '';
        });
    });
    </script>
@endpush
