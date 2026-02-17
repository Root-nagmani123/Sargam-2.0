@extends('admin.layouts.master')

@section('title', 'Estate Possession for Others - Sargam')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Estate Possession for Others"></x-breadcrum>

    <!-- Page Header -->
    <div class="card shadow-sm" style="border-left: 4px solid #004a93;">
        <div class="card-body" id="possessionCardBody">
            <div class="row align-items-center mb-4">
                <div class="col-12 col-md-6">
                    <h2 class="mb-0">Estate Possession for Others</h2>
                </div>
                <div class="col-12 col-md-6 mt-3 mt-md-0">
                    <div class="d-flex justify-content-md-end justify-content-start gap-2">
                        <a href="{{ route('admin.estate.update-meter-reading-of-other') }}" class="btn btn-outline-primary text-decoration-none">Update Reading</a>
                        <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-success btn-sm" title="Add">
                            <i class="material-symbols-rounded">add</i>
                        </a>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Info Card -->
            <div class="alert alert-info mb-4">
                <p class="mb-0">This page displays all Possession added in the system, and provides options to manage records such as add, edit, delete, excel upload, excel download, print etc.</p>
            </div>

            <hr>
            <div class="table-responsive overflow-auto estate-possession-table-wrap">
                {!! $dataTable->table(['class' => 'table text-nowrap mb-0 w-100', 'style' => 'min-width: 1200px;', 'id' => 'estatePossessionTable']) !!}
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deletePossessionModal" tabindex="-1" aria-labelledby="deletePossessionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePossessionModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this possession record?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
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
    @media (max-width: 991.98px) {
        .estate-possession-table-wrap,
        #estatePossessionTable_wrapper .dataTables_scrollBody {
            max-height: 60vh;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
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
                        const alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">' + response.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('#possessionCardBody').find('.alert-success').remove();
                        $('#possessionCardBody').prepend(alert);
                        setTimeout(function() { $('.alert-success').fadeOut(); }, 3000);
                    }
                },
                error: function(xhr) {
                    $('#deletePossessionModal').modal('hide');
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to delete.';
                    const alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    $('#possessionCardBody').find('.alert-danger').remove();
                    $('#possessionCardBody').prepend(alert);
                }
            });
            deleteUrl = '';
        });
    });
    </script>
@endpush
