@extends('admin.layouts.master')

@section('title', 'Caste Master')

@section('setup_content')
<div class="container-fluid caste-category-index">
    <x-breadcrum title="Caste Master"></x-breadcrum>
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card shadow-sm" style="border-left: 4px solid #004a93;">
            <div class="card-body p-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div>
                        <h4 class="fw-semibold text-dark mb-1">Caste Master</h4>
                        <p class="text-muted small mb-0">Manage and organize caste categories</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" 
                                class="btn btn-primary px-4 py-2 rounded-1 shadow-sm d-flex align-items-center gap-2 transition-all"
                                data-bs-toggle="modal" 
                                data-bs-target="#casteCategoryModal"
                                onclick="openCasteCategoryModal('{{ route('master.caste.category.create') }}', 'Create Caste Category')">
                            <i class="material-icons menu-icon material-symbols-rounded" 
                               style="font-size: 20px; vertical-align: middle;">add</i>
                            <span>Add Caste</span>
                        </button>
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
    .caste-category-index .card {
        transition: box-shadow 0.3s ease;
        border-radius: 0.5rem;
    }
    
    .caste-category-index .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .caste-category-index .btn-primary {
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .caste-category-index .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 74, 147, 0.3) !important;
    }
    
    /* Table: full row visible on all screens, horizontal scroll on small */
    .caste-category-index .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .caste-category-index #castecategorymaster-table {
        width: 100% !important;
        min-width: 320px;
    }
    
    .caste-category-index #castecategorymaster-table th,
    .caste-category-index #castecategorymaster-table td {
        white-space: nowrap;
        min-width: 0;
    }
    
    .caste-category-index .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .caste-category-index .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.5px;
    }
    
    .caste-category-index .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .caste-category-index .table tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }
    
    .caste-category-index .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* Action buttons styling */
    .caste-category-index .table tbody td .btn,
    .caste-category-index .table tbody td a {
        transition: all 0.2s ease;
        margin: 0 2px;
    }
    
    .caste-category-index .table tbody td a:hover:not(:disabled) {
        transform: translateY(-1px);
        opacity: 0.8;
    }
    
    .caste-category-index .table tbody td .btn-outline-primary:hover {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }
    
    .caste-category-index .table tbody td .btn-outline-danger:hover {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }
    
    /* Hide DataTables responsive control column if it ever appears */
    .caste-category-index #castecategorymaster-table .dtr-control,
    .caste-category-index #castecategorymaster-table th.dtr-control,
    .caste-category-index #castecategorymaster-table td.dtr-control {
        display: none !important;
        width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        visibility: hidden !important;
        position: absolute !important;
        left: -9999px !important;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .caste-category-index .d-flex.justify-content-between {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .caste-category-index .btn-primary {
            width: 100%;
            justify-content: center;
        }
        
        .caste-category-index .card-body {
            padding: 1rem !important;
        }
        
        .caste-category-index #castecategorymaster-table th,
        .caste-category-index #castecategorymaster-table td {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }
        
        /* Stack DataTables controls on mobile */
        .caste-category-index #castecategorymaster-table_wrapper .row:first-child,
        .caste-category-index #castecategorymaster-table_wrapper .dt-row:first-child {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }
        
        .caste-category-index #castecategorymaster-table_wrapper .dataTables_length,
        .caste-category-index #castecategorymaster-table_wrapper .dataTables_filter {
            text-align: left !important;
            margin-bottom: 0;
            display: block;
            width: 100%;
        }
        
        .caste-category-index #castecategorymaster-table_wrapper .dataTables_length label,
        .caste-category-index #castecategorymaster-table_wrapper .dataTables_filter label {
            width: 100%;
            margin-bottom: 0.5rem;
        }
        
        .caste-category-index #castecategorymaster-table_wrapper .dataTables_filter input {
            width: 100% !important;
            min-width: 100% !important;
        }
    }
    
    @media (max-width: 576px) {
        .caste-category-index .table thead th {
            font-size: 0.75rem;
            padding: 0.5rem 0.5rem;
        }
        
        .caste-category-index .table tbody td {
            padding: 0.5rem 0.5rem;
            font-size: 0.8125rem;
        }
        
        .caste-category-index .table tbody td .btn,
        .caste-category-index .table tbody td a {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    }
    
    /* Modal styling */
    .caste-category-index .modal-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .caste-category-index .modal-footer {
        border-top: 1px solid #dee2e6;
    }
</style>

<!-- Caste Category Modal -->
<div class="modal fade" id="casteCategoryModal" tabindex="-1" aria-labelledby="casteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="casteCategoryModalLabel">Create Caste Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="casteCategoryModalBody">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="submit" form="casteCategoryForm" class="btn btn-primary btn-sm" id="saveCasteCategoryBtn" style="font-size: 14px;">
                    <span class="btn-text">Save</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
{{ $dataTable->scripts() }}
<script>
(function() {
    function removeResponsiveControl() {
        var sel = '.caste-category-index #castecategorymaster-table';
        $(sel + ' .dtr-control, ' + sel + ' th.dtr-control, ' + sel + ' td.dtr-control').remove();
    }
    
    // Open modal with form
    window.openCasteCategoryModal = function(url, title) {
        const modalEl = document.getElementById('casteCategoryModal');
        const modalTitle = modalEl.querySelector('#casteCategoryModalLabel');
        const modalBody = modalEl.querySelector('#casteCategoryModalBody');
        
        modalTitle.textContent = title || 'Caste Category';
        modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.text())
        .then(html => {
            modalBody.innerHTML = html;
            // Update button text based on form state
            setTimeout(function() {
                const form = document.getElementById('casteCategoryForm');
                if (form && form.querySelector('input[name="pk"]')) {
                    const btnText = document.querySelector('#saveCasteCategoryBtn .btn-text');
                    if (btnText) btnText.textContent = 'Update';
                } else {
                    const btnText = document.querySelector('#saveCasteCategoryBtn .btn-text');
                    if (btnText) btnText.textContent = 'Save';
                }
            }, 100);
        })
        .catch(() => {
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>';
        });
    };
    
    // Handle form submission
    $(document).on('submit', '#casteCategoryForm', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#saveCasteCategoryBtn');
        const btnText = submitBtn.find('.btn-text');
        const originalText = btnText.text();
        
        submitBtn.prop('disabled', true);
        btnText.html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
        
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method') || 'POST',
            data: form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    const alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        response.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>';
                    
                    // Insert alert at top of page
                    $('.caste-category-index').prepend(alertHtml);
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('casteCategoryModal'));
                    if (modal) modal.hide();
                    
                    // Reload DataTable
                    if ($.fn.DataTable.isDataTable('#castecategorymaster-table')) {
                        $('#castecategorymaster-table').DataTable().ajax.reload(null, false);
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
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON?.errors || {};
                    let errorHtml = '<div class="alert alert-danger"><ul class="mb-0">';
                    
                    $.each(errors, function(key, value) {
                        errorHtml += '<li>' + value[0] + '</li>';
                    });
                    errorHtml += '</ul></div>';
                    
                    $('#casteCategoryModalBody').prepend(errorHtml);
                } else {
                    const message = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                    $('#casteCategoryModalBody').prepend('<div class="alert alert-danger">' + message + '</div>');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                submitBtn.find('.btn-text').text(originalText);
            }
        });
    });
    
    // Handle edit button clicks in DataTable
    $(document).on('click', '.edit-caste-category', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        openCasteCategoryModal(url, 'Edit Caste Category');
    });
    
    // Clear modal on close and update button text
    $('#casteCategoryModal').on('hidden.bs.modal', function() {
        $('#casteCategoryModalBody').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        $('#saveCasteCategoryBtn').find('.btn-text').text('Save');
        $('#casteCategoryModalLabel').text('Create Caste Category');
    });
    
    // Handle delete button clicks
    $(document).on('click', '.delete-caste-category', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const casteName = $(this).closest('tr').find('td:eq(1)').text().trim();
        
        if (confirm('Are you sure you want to delete "' + casteName + '"?')) {
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success || response.deleted) {
                        // Show success message
                        const alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            (response.message || 'Caste category deleted successfully.') +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>';
                        
                        $('.caste-category-index').prepend(alertHtml);
                        
                        // Reload DataTable
                        if ($.fn.DataTable.isDataTable('#castecategorymaster-table')) {
                            $('#castecategorymaster-table').DataTable().ajax.reload(null, false);
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
                    const message = xhr.responseJSON?.message || 'An error occurred while deleting the caste category.';
                    alert(message);
                }
            });
        }
    });
    
    $(document).ready(function() {
        setTimeout(removeResponsiveControl, 100);
        $(document).on('preInit.dt', function(e, settings) {
            if (settings.nTable.id === 'castecategorymaster-table') {
                $(settings.nTable).on('draw.dt', removeResponsiveControl);
            }
        });
        if ($.fn.DataTable.isDataTable('#castecategorymaster-table')) {
            $('#castecategorymaster-table').on('draw.dt', removeResponsiveControl);
        }
    });
    $(window).on('load', function() { setTimeout(removeResponsiveControl, 200); });
})();
</script>
@endpush