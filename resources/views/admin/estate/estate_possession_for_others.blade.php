@extends('admin.layouts.master')

@section('title', 'Estate Possession for Other - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Estate Possession for Other"></x-breadcrum>

    <!-- Page Card - Bootstrap 5 -->
    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5" id="possessionCardBody">
            <!-- Header -->
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center justify-content-between gap-3 mb-4 no-print">
                <div>
                    <h1 class="h4 fw-semibold mb-1">Estate Possession for Other</h1>
                    <p class="text-muted small mb-0">This page displays all Possession added in the system, and provides options to manage records such as add, edit, delete, excel upload, excel download, print etc.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                    <a href="{{ route('admin.estate.update-meter-reading-of-other') }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2 text-decoration-none">
                        <i class="bi bi-speedometer2"></i>
                        <span>Update Reading</span>
                    </a>
                    <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2" title="Add possession">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add</span>
                    </a>
                    <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2" id="btnDeleteSelected" title="Delete selected" disabled>
                        <i class="bi bi-trash"></i>
                        <span class="d-none d-md-inline">Delete Selected</span>
                    </button>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Show / hide columns">
                            <i class="bi bi-columns-gap"></i>
                            <span class="d-none d-md-inline ms-1">Show / hide columns</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" id="columnToggleMenu"></ul>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" id="btnPrint" title="Print">
                        <i class="bi bi-printer"></i>
                        <span class="d-none d-md-inline">Print</span>
                    </button>
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
            <div class="estate-possession-table-wrapper table-responsive overflow-auto rounded-3">
                {!! $dataTable->table(['class' => 'table table-bordered table-striped table-hover align-middle text-nowrap mb-0 w-100', 'style' => 'min-width: 1200px;', 'id' => 'estatePossessionTable', 'aria-describedby' => 'estate-possession-caption']) !!}
            </div>
            <div id="estate-possession-caption" class="visually-hidden">Estate Possession for Others list</div>
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
    /* Bootstrap 5 DataTables styling */
    #estatePossessionTable_wrapper .dataTables_length label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    #estatePossessionTable_wrapper .dataTables_length select {
        width: auto;
        min-width: 4.5rem;
        padding: 0.25rem 2rem 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: var(--bs-border-radius);
        border: 1px solid var(--bs-border-color, #dee2e6);
    }
    #estatePossessionTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    #estatePossessionTable_wrapper .dataTables_filter input {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
    }
    #estatePossessionTable_wrapper thead th {
        background-color: var(--bs-primary);
        color: #fff;
        font-weight: 600;
        border-color: var(--bs-primary);
        padding: 0.75rem;
        white-space: nowrap;
    }
    #estatePossessionTable_wrapper tbody tr:nth-of-type(odd) {
        background-color: rgba(13, 110, 253, 0.05);
    }
    #estatePossessionTable_wrapper .dataTables_paginate .page-link {
        border-radius: var(--bs-border-radius);
        padding: 0.25rem 0.5rem;
    }
    #estatePossessionTable_wrapper .dataTables_paginate .page-item.active .page-link {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }
    @media print {
        .no-print { display: none !important; }
        #estatePossessionTable_wrapper .dataTables_length,
        #estatePossessionTable_wrapper .dataTables_filter,
        #estatePossessionTable_wrapper .dataTables_paginate { display: none !important; }
    }
    .estate-possession-table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 991.98px) {
        .estate-possession-table-wrapper,
        #estatePossessionTable_wrapper .dataTables_scrollBody {
            max-height: 60vh;
            overflow-y: auto !important;
        }
    }
</style>
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    $(document).ready(function() {
        let deleteUrl = '';
        var table = $('#estatePossessionTable').DataTable();

        // Column visibility toggle (build menu from table columns, skip checkbox and actions)
        function buildColumnToggle() {
            var menu = $('#columnToggleMenu');
            menu.empty();
            table.columns().every(function(i) {
                var col = this;
                var header = $(col.header()).text().trim();
                if (!header || header === 'Actions') return;
                var $li = $('<li><label class="dropdown-item d-flex align-items-center gap-2 cursor-pointer mb-0"><input type="checkbox" class="form-check-input column-toggle" data-column="' + i + '"> ' + header + '</label></li>');
                $li.find('input').prop('checked', col.visible());
                menu.append($li);
            });
        }
        $(document).on('change', '.column-toggle', function() {
            var colIdx = $(this).data('column');
            table.column(colIdx).visible($(this).prop('checked'));
        });
        table.on('draw', function() { buildColumnToggle(); });
        buildColumnToggle();

        $('#btnPrint').on('click', function() {
            window.print();
        });

        $(document).on('change', '.row-select-possession', function() {
            var any = $('#estatePossessionTable tbody .row-select-possession:checked').length;
            $('#btnDeleteSelected').prop('disabled', !any);
        });
        $('#btnDeleteSelected').on('click', function() {
            var ids = [];
            $('#estatePossessionTable tbody .row-select-possession:checked').each(function() {
                ids.push($(this).data('id'));
            });
            if (ids.length === 0) return;
            if (!confirm('Delete ' + ids.length + ' selected record(s)?')) return;
            var $btn = $(this);
            $btn.prop('disabled', true);
            var completed = 0;
            function doNext() {
                if (completed >= ids.length) {
                    $('#estatePossessionTable').DataTable().ajax.reload(null, false);
                    $btn.prop('disabled', true);
                    return;
                }
                $.ajax({ url: '{{ url("admin/estate/possession-delete") }}/' + ids[completed], type: 'DELETE', data: { _token: '{{ csrf_token() }}' } })
                    .always(function() {
                        completed++;
                        doNext();
                    });
            }
            doNext();
        });

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
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    $('#deletePossessionModal').modal('hide');
                    if (response.success) {
                        $('#estatePossessionTable').DataTable().ajax.reload(null, false);
                        var alert = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2 flex-shrink-0"></i><span class="flex-grow-1">' + response.message + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('#possessionCardBody').find('.alert-success').remove();
                        $('#possessionCardBody').prepend(alert);
                        setTimeout(function() { $('.alert-success').fadeOut(); }, 3000);
                    }
                },
                error: function(xhr) {
                    $('#deletePossessionModal').modal('hide');
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to delete.';
                    var alert = '<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0"></i><span class="flex-grow-1">' + msg + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    $('#possessionCardBody').find('.alert-danger').remove();
                    $('#possessionCardBody').prepend(alert);
                }
            });
            deleteUrl = '';
        });
    });
});
</script>
@endpush
