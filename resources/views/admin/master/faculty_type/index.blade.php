@extends('admin.layouts.master')

@section('title', 'Faculty Type')

@section('setup_content')
<div class="container-fluid faculty-type-index">
    <x-breadcrum title="Faculty Type" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card shadow-sm" style="border-left: 4px solid #004a93;">
            <div class="card-body p-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div>
                        <h4 class="fw-semibold text-dark mb-1">Faculty Type</h4>
                        <p class="text-muted small mb-0">Manage faculty type categories and classifications</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{route('master.faculty.type.master.create')}}"
                            class="btn btn-primary px-4 py-2 rounded-1 shadow-sm d-flex align-items-center gap-2 transition-all">
                            <i class="material-icons menu-icon material-symbols-rounded" 
                               style="font-size: 20px; vertical-align: middle;">add</i>
                            <span>Add Faculty Type</span>
                        </a>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="table-responsive rounded overflow-auto">
                    {{ $dataTable->table(['class' => 'table text-nowrap mb-0 align-middle']) }}
                </div>
                
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

<style>
    .faculty-type-index .card {
        transition: box-shadow 0.3s ease;
    }
    
    .faculty-type-index .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .faculty-type-index .btn-primary {
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .faculty-type-index .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 74, 147, 0.3) !important;
    }
    
    /* Table styling */
    .faculty-type-index .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .faculty-type-index .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .faculty-type-index .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.5px;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .faculty-type-index .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .faculty-type-index .table tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }
    
    .faculty-type-index .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* Action buttons styling */
    .faculty-type-index .table tbody td .btn {
        transition: all 0.2s ease;
        margin: 0 2px;
    }
    
    .faculty-type-index .table tbody td .btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .faculty-type-index .table tbody td .btn-outline-primary:hover {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }
    
    .faculty-type-index .table tbody td .btn-outline-danger:hover {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }
    
    .faculty-type-index .table tbody td .btn-outline-secondary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* Form switch styling */
    .faculty-type-index .form-check-input {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .faculty-type-index .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }
    
    .faculty-type-index .form-check-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
    }
    
    /* DataTables specific styling */
    .faculty-type-index #facultytypemaster-table {
        width: 100% !important;
        min-width: 320px;
    }
    
    .faculty-type-index #facultytypemaster-table th,
    .faculty-type-index #facultytypemaster-table td {
        white-space: nowrap;
        min-width: 0;
    }
    
    /* Hide DataTables responsive control column if it ever appears */
    .faculty-type-index #facultytypemaster-table .dtr-control,
    .faculty-type-index #facultytypemaster-table th.dtr-control,
    .faculty-type-index #facultytypemaster-table td.dtr-control {
        display: none !important;
        width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        visibility: hidden !important;
        position: absolute !important;
        left: -9999px !important;
    }
    
    /* DataTables Controls Styling */
    .faculty-type-index .dataTables_wrapper {
        padding-top: 1rem;
    }
    
    /* Search Box Styling */
    .faculty-type-index .dataTables_filter {
        margin-bottom: 1rem;
    }
    
    .faculty-type-index .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
        color: #495057;
    }
    
    .faculty-type-index .dataTables_filter input[type="search"] {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        min-width: 250px;
    }
    
    .faculty-type-index .dataTables_filter input[type="search"]:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    /* Length Selector Styling */
    .faculty-type-index .dataTables_length {
        margin-bottom: 1rem;
    }
    
    .faculty-type-index .dataTables_length label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
        color: #495057;
    }
    
    .faculty-type-index .dataTables_length select {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.375rem 2rem 0.375rem 0.75rem;
        font-size: 0.875rem;
        background-color: #fff;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .faculty-type-index .dataTables_length select:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    /* Info Display Styling */
    .faculty-type-index .dataTables_info {
        padding-top: 0.75rem;
        font-size: 0.875rem;
        color: #6c757d;
        font-weight: 500;
    }
    
    /* Pagination Styling */
    .faculty-type-index .dataTables_paginate {
        padding-top: 0.75rem;
    }
    
    .faculty-type-index .dataTables_paginate .paginate_button {
        padding: 0.375rem 0.75rem;
        margin-left: 0.25rem;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        color: #0d6efd !important;
        background-color: #fff;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        text-decoration: none !important;
    }
    
    .faculty-type-index .dataTables_paginate .paginate_button:hover {
        background-color: #e9ecef;
        border-color: #dee2e6;
        color: #0a58ca !important;
    }
    
    .faculty-type-index .dataTables_paginate .paginate_button.current {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff !important;
    }
    
    .faculty-type-index .dataTables_paginate .paginate_button.current:hover {
        background-color: #0b5ed7;
        border-color: #0a58ca;
        color: #fff !important;
    }
    
    .faculty-type-index .dataTables_paginate .paginate_button.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* Buttons Toolbar Styling */
    .faculty-type-index .dt-buttons {
        margin-bottom: 1rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .faculty-type-index .dt-button {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 0.375rem;
        border: 1px solid #dee2e6;
        background-color: #fff;
        color: #212529;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }
    
    .faculty-type-index .dt-button:hover {
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }
    
    /* Table Header Sorting Indicators */
    .faculty-type-index #facultytypemaster-table thead th.sorting,
    .faculty-type-index #facultytypemaster-table thead th.sorting_asc,
    .faculty-type-index #facultytypemaster-table thead th.sorting_desc {
        cursor: pointer;
        position: relative;
        padding-right: 2rem;
    }
    
    .faculty-type-index #facultytypemaster-table thead th.sorting:before,
    .faculty-type-index #facultytypemaster-table thead th.sorting:after,
    .faculty-type-index #facultytypemaster-table thead th.sorting_asc:before,
    .faculty-type-index #facultytypemaster-table thead th.sorting_asc:after,
    .faculty-type-index #facultytypemaster-table thead th.sorting_desc:before,
    .faculty-type-index #facultytypemaster-table thead th.sorting_desc:after {
        content: "";
        position: absolute;
        right: 0.5rem;
        width: 0;
        height: 0;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
    }
    
    .faculty-type-index #facultytypemaster-table thead th.sorting:before,
    .faculty-type-index #facultytypemaster-table thead th.sorting_asc:before,
    .faculty-type-index #facultytypemaster-table thead th.sorting_desc:before {
        top: 0.75rem;
        border-bottom: 4px solid #adb5bd;
    }
    
    .faculty-type-index #facultytypemaster-table thead th.sorting:after,
    .faculty-type-index #facultytypemaster-table thead th.sorting_asc:after,
    .faculty-type-index #facultytypemaster-table thead th.sorting_desc:after {
        bottom: 0.75rem;
        border-top: 4px solid #adb5bd;
    }
    
    .faculty-type-index #facultytypemaster-table thead th.sorting_asc:before {
        border-bottom-color: #0d6efd;
    }
    
    .faculty-type-index #facultytypemaster-table thead th.sorting_desc:after {
        border-top-color: #0d6efd;
    }
    
    /* Responsive DataTables Controls */
    @media (max-width: 768px) {
        .faculty-type-index .dataTables_wrapper .dataTables_filter,
        .faculty-type-index .dataTables_wrapper .dataTables_length {
            text-align: left;
            margin-bottom: 1rem;
        }
        
        .faculty-type-index .dataTables_filter input[type="search"] {
            min-width: 200px;
            width: 100%;
        }
        
        .faculty-type-index .dt-buttons {
            flex-direction: column;
        }
        
        .faculty-type-index .dt-button {
            width: 100%;
        }
        
        .faculty-type-index .dataTables_wrapper .dataTables_info,
        .faculty-type-index .dataTables_wrapper .dataTables_paginate {
            text-align: center;
            margin-top: 1rem;
        }
        
        .faculty-type-index .d-flex.justify-content-between {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .faculty-type-index .btn-primary {
            width: 100%;
            justify-content: center;
        }
        
        .faculty-type-index .card-body {
            padding: 1rem !important;
        }
        
        .faculty-type-index .table thead th,
        .faculty-type-index .table tbody td {
            padding: 0.75rem 0.5rem;
            font-size: 0.875rem;
        }
    }
    
    @media (max-width: 576px) {
        .faculty-type-index .table thead th {
            font-size: 0.75rem;
            padding: 0.5rem 0.5rem;
        }
        
        .faculty-type-index .table tbody td {
            padding: 0.5rem 0.5rem;
            font-size: 0.8125rem;
        }
    }
</style>

@endsection

@push('scripts')
{{ $dataTable->scripts() }}
<script>
(function() {
    // Handle delete button clicks
    $(document).on('click', '#facultytypemaster-table tbody form button[type="submit"]', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const deleteUrl = form.attr('action');
        
        if (confirm('Are you sure you want to delete this record?')) {
            $.ajax({
                url: deleteUrl,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        const alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            response.message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>';
                        
                        $('.faculty-type-index').prepend(alertHtml);
                        
                        // Reload DataTable
                        if ($.fn.DataTable.isDataTable('#facultytypemaster-table')) {
                            $('#facultytypemaster-table').DataTable().ajax.reload(null, false);
                        }
                        
                        // Auto-hide alert after 3 seconds
                        setTimeout(function() {
                            $('.alert-success').fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'An error occurred while deleting. Please try again.';
                    const alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>';
                    $('.faculty-type-index').prepend(alertHtml);
                    
                    setTimeout(function() {
                        $('.alert-danger').fadeOut(function() {
                            $(this).remove();
                        });
                    }, 5000);
                }
            });
        }
    });
    
    function removeResponsiveControl() {
        var sel = '.faculty-type-index #facultytypemaster-table';
        $(sel + ' .dtr-control, ' + sel + ' th.dtr-control, ' + sel + ' td.dtr-control').remove();
    }
    
    $(document).ready(function() {
        setTimeout(removeResponsiveControl, 100);
        $(document).on('preInit.dt', function(e, settings) {
            if (settings.nTable.id === 'facultytypemaster-table') {
                $(settings.nTable).on('draw.dt', removeResponsiveControl);
            }
        });
        if ($.fn.DataTable.isDataTable('#facultytypemaster-table')) {
            $('#facultytypemaster-table').on('draw.dt', removeResponsiveControl);
        }
    });
    $(window).on('load', function() { setTimeout(removeResponsiveControl, 200); });
})();
</script>
@endpush